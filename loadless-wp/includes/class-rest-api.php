<?php
/**
 * REST API Handler Class
 *
 * Handles all REST API routes for the LoadLess WP plugin.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

namespace LoadLessWP;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class REST_API
 *
 * @since 1.0.0
 */
class REST_API {

    /**
     * Namespace for REST API routes.
     *
     * @var string
     */
    private $namespace = 'loadless-wp/v1';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     */
    public function register_routes(): void {
        // Get pages endpoint.
        register_rest_route(
            $this->namespace,
            '/pages',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_pages' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        // Get assets endpoint.
        register_rest_route(
            $this->namespace,
            '/assets',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_assets' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'page_id'    => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'description'       => __( 'Page ID to fetch assets for.', 'loadless-wp' ),
                    ],
                    'page'       => [
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                        'description'       => __( 'Current page for pagination.', 'loadless-wp' ),
                    ],
                    'per_page'   => [
                        'type'              => 'integer',
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                        'description'       => __( 'Number of items per page.', 'loadless-wp' ),
                    ],
                    'search'     => [
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Search term for filtering assets.', 'loadless-wp' ),
                    ],
                    'asset_type' => [
                        'type'              => 'string',
                        'default'           => 'all',
                        'sanitize_callback' => 'sanitize_text_field',
                        'enum'              => [ 'all', 'script', 'style' ],
                        'description'       => __( 'Filter by asset type.', 'loadless-wp' ),
                    ],
                ],
            ]
        );

        // Toggle asset endpoint.
        register_rest_route(
            $this->namespace,
            '/toggle-asset',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'toggle_asset' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'page_id'    => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'description'       => __( 'Page ID to update.', 'loadless-wp' ),
                    ],
                    'asset_type' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'enum'              => [ 'script', 'style' ],
                        'description'       => __( 'Type of asset (script/style).', 'loadless-wp' ),
                    ],
                    'handle'     => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Asset handle to toggle.', 'loadless-wp' ),
                    ],
                    'enabled'    => [
                        'required'          => true,
                        'type'              => 'boolean',
                        'sanitize_callback' => 'rest_sanitize_boolean',
                        'description'       => __( 'Whether to enable or disable the asset.', 'loadless-wp' ),
                    ],
                ],
            ]
        );

        // Bulk toggle assets endpoint.
        register_rest_route(
            $this->namespace,
            '/bulk-toggle',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'bulk_toggle' ],
                'permission_callback' => [ $this, 'check_permissions' ],
                'args'                => [
                    'page_id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'description'       => __( 'Page ID to update.', 'loadless-wp' ),
                    ],
                    'assets'  => [
                        'required'          => true,
                        'type'              => 'array',
                        'description'       => __( 'Array of assets to toggle.', 'loadless-wp' ),
                        'items'             => [
                            'type'       => 'object',
                            'properties' => [
                                'handle'     => [ 'type' => 'string' ],
                                'asset_type' => [ 'type' => 'string' ],
                                'enabled'    => [ 'type' => 'boolean' ],
                            ],
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Check user permissions.
     *
     * @since 1.0.0
     *
     * @return bool True if user has permission, false otherwise.
     */
    public function check_permissions(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get list of pages.
     *
     * @since 1.0.0
     *
     * @return WP_REST_Response|WP_Error
     */
    public function get_pages() {
        $args = [
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ];

        $query = new \WP_Query( $args );
        $pages = [];

        foreach ( $query->posts as $post ) {
            $pages[] = [
                'id'    => $post->ID,
                'title' => get_the_title( $post ),
                'type'  => get_post_type( $post ),
            ];
        }

        return new WP_REST_Response(
            [
                'success' => true,
                'data'    => $pages,
            ],
            200
        );
    }

    /**
     * Get registered assets for a page.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_assets( WP_REST_Request $request ) {
        $page_id    = $request->get_param( 'page_id' );
        $page       = $request->get_param( 'page' );
        $per_page   = $request->get_param( 'per_page' );
        $search     = $request->get_param( 'search' );
        $asset_type = $request->get_param( 'asset_type' );

        // Validate post exists.
        $post = get_post( $page_id );
        if ( ! $post ) {
            return new WP_Error(
                'not_found',
                __( 'Page not found.', 'loadless-wp' ),
                [ 'status' => 404 ]
            );
        }

        // Set up global post to simulate loading the page.
        $GLOBALS['post'] = $post;
        setup_postdata( $post );

        // Enqueue scripts and styles to populate registries.
        wp_enqueue_scripts();

        // Access global scripts and styles registries.
        global $wp_scripts, $wp_styles;

        $scripts = [];
        $styles  = [];

        // Collect all registered scripts.
        if ( ! empty( $wp_scripts->registered ) ) {
            foreach ( $wp_scripts->registered as $handle => $script ) {
                $is_enqueued = in_array( $handle, $wp_scripts->queue, true );
                $scripts[]   = [
                    'handle'    => $handle,
                    'src'       => $script->src ?? '',
                    'type'      => 'script',
                    'enqueued'  => $is_enqueued,
                    'deps'      => $script->deps ?? [],
                    'version'   => $script->ver ?? '',
                    'in_footer' => $script->args ?? false,
                ];
            }
        }

        // Collect all registered styles.
        if ( ! empty( $wp_styles->registered ) ) {
            foreach ( $wp_styles->registered as $handle => $style ) {
                $is_enqueued = in_array( $handle, $wp_styles->queue, true );
                $styles[]    = [
                    'handle'   => $handle,
                    'src'      => $style->src ?? '',
                    'type'     => 'style',
                    'enqueued' => $is_enqueued,
                    'deps'     => $style->deps ?? [],
                    'version'  => $style->ver ?? '',
                    'media'    => $style->args ?? 'all',
                ];
            }
        }

        // Merge based on filter.
        $all_assets = [];
        if ( 'all' === $asset_type || 'script' === $asset_type ) {
            $all_assets = array_merge( $all_assets, $scripts );
        }
        if ( 'all' === $asset_type || 'style' === $asset_type ) {
            $all_assets = array_merge( $all_assets, $styles );
        }

        // Filter out core assets if setting is disabled.
        $show_core = get_option( 'loadless_wp_show_core_assets', false );
        if ( ! $show_core ) {
            $all_assets = array_filter(
                $all_assets,
                function ( $asset ) {
                    if ( empty( $asset['src'] ) ) {
                        return false;
                    }
                    // Keep only non-core assets (plugins/themes).
                    return strpos( $asset['src'], '/wp-includes/' ) === false
                        && strpos( $asset['src'], '/wp-admin/' ) === false;
                }
            );
        }

        // Re-index after filtering.
        $all_assets = array_values( $all_assets );

        // Apply search filter.
        if ( ! empty( $search ) ) {
            $search_lower = strtolower( $search );
            $all_assets   = array_filter(
                $all_assets,
                function ( $asset ) use ( $search_lower ) {
                    return stripos( $asset['handle'], $search_lower ) !== false
                        || stripos( $asset['type'], $search_lower ) !== false
                        || stripos( $asset['src'], $search_lower ) !== false;
                }
            );
            $all_assets = array_values( $all_assets );
        }

        // Get disabled assets.
        $disabled_scripts = get_post_meta( $page_id, '_disabled_scripts', true );
        $disabled_styles  = get_post_meta( $page_id, '_disabled_styles', true );

        $disabled_scripts = is_array( $disabled_scripts ) ? $disabled_scripts : [];
        $disabled_styles  = is_array( $disabled_styles ) ? $disabled_styles : [];

        // Add enabled state to each asset.
        $all_assets = array_map(
            function ( $asset ) use ( $disabled_scripts, $disabled_styles ) {
                if ( 'script' === $asset['type'] ) {
                    $asset['enabled'] = ! in_array( $asset['handle'], $disabled_scripts, true );
                } else {
                    $asset['enabled'] = ! in_array( $asset['handle'], $disabled_styles, true );
                }
                return $asset;
            },
            $all_assets
        );

        // Pagination.
        $total_items = count( $all_assets );
        $total_pages = $per_page > 0 ? ceil( $total_items / $per_page ) : 1;
        $offset      = ( $page - 1 ) * $per_page;

        $paginated_assets = array_slice( $all_assets, $offset, $per_page );

        // Reset global post data.
        wp_reset_postdata();

        return new WP_REST_Response(
            [
                'success' => true,
                'data'    => $paginated_assets,
                'meta'    => [
                    'total_items' => $total_items,
                    'total_pages' => $total_pages,
                    'current_page' => (int) $page,
                    'per_page'     => (int) $per_page,
                ],
            ],
            200
        );
    }

    /**
     * Toggle an asset for a page.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function toggle_asset( WP_REST_Request $request ) {
        $page_id    = $request->get_param( 'page_id' );
        $asset_type = $request->get_param( 'asset_type' );
        $handle     = $request->get_param( 'handle' );
        $enabled    = $request->get_param( 'enabled' );

        if ( ! $page_id || empty( $handle ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Invalid page ID or handle.', 'loadless-wp' ),
                [ 'status' => 400 ]
            );
        }

        // Verify nonce for additional security.
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( $nonce && ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error(
                'invalid_nonce',
                __( 'Security check failed.', 'loadless-wp' ),
                [ 'status' => 403 ]
            );
        }

        $meta_key = 'script' === $asset_type ? '_disabled_scripts' : '_disabled_styles';

        // Get current disabled assets with sanitization.
        $disabled_assets = get_post_meta( $page_id, $meta_key, true );
        if ( ! is_array( $disabled_assets ) ) {
            $disabled_assets = [];
        }

        // Sanitize handle.
        $handle = sanitize_text_field( $handle );

        // Update disabled assets list.
        if ( $enabled ) {
            // Remove from disabled if enabling.
            $disabled_assets = array_diff( $disabled_assets, [ $handle ] );
        } else {
            // Add to disabled if disabling.
            if ( ! in_array( $handle, $disabled_assets, true ) ) {
                $disabled_assets[] = $handle;
            }
        }

        // Re-index array.
        $disabled_assets = array_values( $disabled_assets );

        // Save to database.
        update_post_meta( $page_id, $meta_key, $disabled_assets );

        return new WP_REST_Response(
            [
                'success'        => true,
                'message'        => $enabled
                    ? __( 'Asset enabled successfully.', 'loadless-wp' )
                    : __( 'Asset disabled successfully.', 'loadless-wp' ),
                'updated_assets' => $disabled_assets,
            ],
            200
        );
    }

    /**
     * Bulk toggle multiple assets.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function bulk_toggle( WP_REST_Request $request ) {
        $page_id = $request->get_param( 'page_id' );
        $assets  = $request->get_param( 'assets' );

        if ( ! $page_id || ! is_array( $assets ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Invalid data provided.', 'loadless-wp' ),
                [ 'status' => 400 ]
            );
        }

        $disabled_scripts = get_post_meta( $page_id, '_disabled_scripts', true );
        $disabled_styles  = get_post_meta( $page_id, '_disabled_styles', true );

        $disabled_scripts = is_array( $disabled_scripts ) ? $disabled_scripts : [];
        $disabled_styles  = is_array( $disabled_styles ) ? $disabled_styles : [];

        foreach ( $assets as $asset ) {
            $handle     = sanitize_text_field( $asset['handle'] ?? '' );
            $asset_type = sanitize_text_field( $asset['asset_type'] ?? '' );
            $enabled    = rest_sanitize_boolean( $asset['enabled'] ?? true );

            if ( empty( $handle ) ) {
                continue;
            }

            if ( 'script' === $asset_type ) {
                if ( $enabled ) {
                    $disabled_scripts = array_diff( $disabled_scripts, [ $handle ] );
                } else {
                    if ( ! in_array( $handle, $disabled_scripts, true ) ) {
                        $disabled_scripts[] = $handle;
                    }
                }
            } elseif ( 'style' === $asset_type ) {
                if ( $enabled ) {
                    $disabled_styles = array_diff( $disabled_styles, [ $handle ] );
                } else {
                    if ( ! in_array( $handle, $disabled_styles, true ) ) {
                        $disabled_styles[] = $handle;
                    }
                }
            }
        }

        update_post_meta( $page_id, '_disabled_scripts', array_values( $disabled_scripts ) );
        update_post_meta( $page_id, '_disabled_styles', array_values( $disabled_styles ) );

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => __( 'Assets updated successfully.', 'loadless-wp' ),
            ],
            200
        );
    }
}

// Initialize the REST API handler.
new REST_API();
