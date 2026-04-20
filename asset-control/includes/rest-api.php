<?php
/**
 * REST API for Asset Control plugin
 * 
 * @package Asset_Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API handler class
 */
class Asset_Control_REST_API {
    
    /**
     * Namespace for API routes
     */
    const NAMESPACE = 'asset-control/v1';
    
    /**
     * Initialize REST API routes
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Get available pages
        register_rest_route( self::NAMESPACE, '/pages', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'get_pages' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
        ) );
        
        // Get assets for a specific page
        register_rest_route( self::NAMESPACE, '/assets', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'get_assets' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Page ID or context identifier', 'asset-control' ),
                ),
                'page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'sanitize_callback' => 'absint',
                    'description' => __( 'Current page number for pagination', 'asset-control' ),
                ),
                'per_page' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'minimum' => 5,
                    'maximum' => 100,
                    'sanitize_callback' => 'absint',
                    'description' => __( 'Number of items per page', 'asset-control' ),
                ),
                'search' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Search term to filter assets', 'asset-control' ),
                ),
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'all',
                    'enum' => array( 'all', 'script', 'style' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Filter by asset type', 'asset-control' ),
                ),
            ),
        ) );
        
        // Toggle asset enabled/disabled state
        register_rest_route( self::NAMESPACE, '/toggle', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( __CLASS__, 'toggle_asset' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Page ID or context identifier', 'asset-control' ),
                ),
                'asset_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array( 'script', 'style' ),
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Type of asset (script or style)', 'asset-control' ),
                ),
                'handle' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => __( 'Asset handle', 'asset-control' ),
                ),
                'enabled' => array(
                    'required' => true,
                    'type' => 'boolean',
                    'sanitize_callback' => 'rest_sanitize_boolean',
                    'description' => __( 'Whether the asset should be enabled', 'asset-control' ),
                ),
            ),
        ) );
        
        // Bulk toggle assets
        register_rest_route( self::NAMESPACE, '/bulk-toggle', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( __CLASS__, 'bulk_toggle_assets' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'assets' => array(
                    'required' => true,
                    'type' => 'array',
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'handle' => array( 'type' => 'string' ),
                            'type' => array( 'type' => 'string', 'enum' => array( 'script', 'style' ) ),
                            'enabled' => array( 'type' => 'boolean' ),
                        ),
                    ),
                    'sanitize_callback' => array( __CLASS__, 'sanitize_assets_array' ),
                ),
            ),
        ) );
        
        // Get statistics for a page
        register_rest_route( self::NAMESPACE, '/stats', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'check_permissions' ),
            'args' => array(
                'page_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
    }
    
    /**
     * Check user permissions
     * 
     * @return bool True if user has permission
     */
    public static function check_permissions() {
        return current_user_can( 'manage_options' );
    }
    
    /**
     * Get available pages
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function get_pages( $request ) {
        $pages = Asset_Control_Core::get_available_pages();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $pages,
        ), 200 );
    }
    
    /**
     * Get assets for a specific page
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function get_assets( $request ) {
        $page_id = $request->get_param( 'page_id' );
        $current_page = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        $search = $request->get_param( 'search' );
        $filter_type = $request->get_param( 'type' );
        
        // Convert front_page string to appropriate context
        $context_id = $page_id === 'front_page' ? 'front_page' : absint( $page_id );
        
        // Simulate page load to get enqueued assets
        self::simulate_page_load( $context_id );
        
        global $wp_scripts, $wp_styles;
        
        $assets = array();
        
        // Collect scripts
        if ( $filter_type === 'all' || $filter_type === 'script' ) {
            if ( ! empty( $wp_scripts->queue ) ) {
                foreach ( $wp_scripts->queue as $handle ) {
                    if ( isset( $wp_scripts->registered[ $handle ] ) ) {
                        $registered = $wp_scripts->registered[ $handle ];
                        $src = $registered->src ?? '';
                        
                        // Skip empty sources (inline scripts)
                        if ( empty( $src ) ) {
                            continue;
                        }
                        
                        $full_url = self::get_full_url( $src );
                        
                        $assets[] = array(
                            'handle' => $handle,
                            'type' => 'script',
                            'src' => $src,
                            'full_url' => $full_url,
                            'size' => Asset_Control_Core::get_file_size( $full_url ),
                            'dependencies' => $registered->deps ?? array(),
                            'version' => $registered->ver ?? '',
                            'enabled' => ! Asset_Control_Core::is_asset_disabled( $context_id, 'scripts', $handle ),
                        );
                    }
                }
            }
        }
        
        // Collect styles
        if ( $filter_type === 'all' || $filter_type === 'style' ) {
            if ( ! empty( $wp_styles->queue ) ) {
                foreach ( $wp_styles->queue as $handle ) {
                    if ( isset( $wp_styles->registered[ $handle ] ) ) {
                        $registered = $wp_styles->registered[ $handle ];
                        $src = $registered->src ?? '';
                        
                        // Skip empty sources (inline styles)
                        if ( empty( $src ) ) {
                            continue;
                        }
                        
                        $full_url = self::get_full_url( $src );
                        
                        $assets[] = array(
                            'handle' => $handle,
                            'type' => 'style',
                            'src' => $src,
                            'full_url' => $full_url,
                            'size' => Asset_Control_Core::get_file_size( $full_url ),
                            'dependencies' => $registered->deps ?? array(),
                            'version' => $registered->ver ?? '',
                            'enabled' => ! Asset_Control_Core::is_asset_disabled( $context_id, 'styles', $handle ),
                        );
                    }
                }
            }
        }
        
        // Apply search filter
        if ( ! empty( $search ) ) {
            $search_lower = strtolower( $search );
            $assets = array_filter( $assets, function( $asset ) use ( $search_lower ) {
                return strpos( strtolower( $asset['handle'] ), $search_lower ) !== false ||
                       strpos( strtolower( $asset['src'] ), $search_lower ) !== false ||
                       strpos( strtolower( $asset['type'] ), $search_lower ) !== false;
            } );
        }
        
        // Reset array keys
        $assets = array_values( $assets );
        
        // Pagination
        $total_items = count( $assets );
        $total_pages = ceil( $total_items / $per_page );
        $offset = ( $current_page - 1 ) * $per_page;
        $paginated_assets = array_slice( $assets, $offset, $per_page );
        
        // Count enabled/disabled
        $enabled_count = count( array_filter( $assets, function( $a ) { return $a['enabled']; } ) );
        $disabled_count = $total_items - $enabled_count;
        
        wp_reset_postdata();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $paginated_assets,
            'meta' => array(
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $current_page,
                'per_page' => $per_page,
                'enabled_count' => $enabled_count,
                'disabled_count' => $disabled_count,
            ),
        ), 200 );
    }
    
    /**
     * Toggle a single asset
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function toggle_asset( $request ) {
        $page_id = $request->get_param( 'page_id' );
        $asset_type = $request->get_param( 'asset_type' );
        $handle = $request->get_param( 'handle' );
        $enabled = $request->get_param( 'enabled' );
        
        $context_id = $page_id === 'front_page' ? 'front_page' : absint( $page_id );
        $meta_type = $asset_type === 'script' ? 'scripts' : 'styles';
        
        if ( $enabled ) {
            $success = Asset_Control_Core::remove_disabled_asset( $context_id, $meta_type, $handle );
        } else {
            $success = Asset_Control_Core::add_disabled_asset( $context_id, $meta_type, $handle );
        }
        
        // Clear transients for this page
        delete_transient( 'asset_control_assets_' . md5( $context_id ) );
        
        return new WP_REST_Response( array(
            'success' => true,
            'message' => $enabled 
                ? sprintf( __( 'Asset "%s" enabled', 'asset-control' ), $handle )
                : sprintf( __( 'Asset "%s" disabled', 'asset-control' ), $handle ),
        ), 200 );
    }
    
    /**
     * Bulk toggle multiple assets
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function bulk_toggle_assets( $request ) {
        $page_id = $request->get_param( 'page_id' );
        $assets = $request->get_param( 'assets' );
        
        $context_id = $page_id === 'front_page' ? 'front_page' : absint( $page_id );
        
        $updated = 0;
        $errors = 0;
        
        foreach ( $assets as $asset ) {
            $meta_type = $asset['type'] === 'script' ? 'scripts' : 'styles';
            
            if ( $asset['enabled'] ) {
                if ( Asset_Control_Core::remove_disabled_asset( $context_id, $meta_type, $asset['handle'] ) ) {
                    $updated++;
                }
            } else {
                if ( Asset_Control_Core::add_disabled_asset( $context_id, $meta_type, $asset['handle'] ) ) {
                    $updated++;
                }
            }
        }
        
        // Clear transients
        delete_transient( 'asset_control_assets_' . md5( $context_id ) );
        
        return new WP_REST_Response( array(
            'success' => true,
            'message' => sprintf( _n( '%d asset updated', '%d assets updated', $updated, 'asset-control' ), $updated ),
            'updated' => $updated,
        ), 200 );
    }
    
    /**
     * Get statistics for a page
     * 
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public static function get_stats( $request ) {
        $page_id = $request->get_param( 'page_id' );
        $context_id = $page_id === 'front_page' ? 'front_page' : absint( $page_id );
        
        self::simulate_page_load( $context_id );
        
        global $wp_scripts, $wp_styles;
        
        $scripts_count = ! empty( $wp_scripts->queue ) ? count( $wp_scripts->queue ) : 0;
        $styles_count = ! empty( $wp_styles->queue ) ? count( $wp_styles->queue ) : 0;
        
        $disabled_scripts = Asset_Control_Core::get_disabled_assets( $context_id, 'scripts' );
        $disabled_styles = Asset_Control_Core::get_disabled_assets( $context_id, 'styles' );
        
        wp_reset_postdata();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => array(
                'total_scripts' => $scripts_count,
                'total_styles' => $styles_count,
                'total_assets' => $scripts_count + $styles_count,
                'disabled_scripts' => count( $disabled_scripts ),
                'disabled_styles' => count( $disabled_styles ),
                'disabled_total' => count( $disabled_scripts ) + count( $disabled_styles ),
            ),
        ), 200 );
    }
    
    /**
     * Simulate page load to get enqueued assets
     * 
     * @param mixed $context_id Page ID or context
     */
    private static function simulate_page_load( $context_id ) {
        if ( $context_id === 'front_page' ) {
            // Set up as front page
            query_posts( array( 'page_id' => get_option( 'page_on_front' ) ) );
        } elseif ( is_numeric( $context_id ) && $context_id > 0 ) {
            // Set up specific post/page
            $post = get_post( $context_id );
            if ( $post ) {
                $GLOBALS['post'] = $post;
                setup_postdata( $post );
            }
        }
        
        // Run wp_enqueue_scripts action
        do_action( 'wp_enqueue_scripts' );
    }
    
    /**
     * Get full URL from relative path
     * 
     * @param string $src Source URL/path
     * @return string Full URL
     */
    private static function get_full_url( $src ) {
        if ( strpos( $src, 'http' ) === 0 ) {
            return $src;
        }
        
        if ( strpos( $src, '//' ) === 0 ) {
            return set_url_scheme( $src );
        }
        
        return site_url( $src );
    }
    
    /**
     * Sanitize assets array for bulk operations
     * 
     * @param array $assets Assets array
     * @return array Sanitized array
     */
    public static function sanitize_assets_array( $assets ) {
        if ( ! is_array( $assets ) ) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ( $assets as $asset ) {
            if ( ! is_array( $asset ) ) {
                continue;
            }
            
            $sanitized[] = array(
                'handle' => sanitize_text_field( $asset['handle'] ?? '' ),
                'type' => in_array( $asset['type'] ?? '', array( 'script', 'style' ) ) ? $asset['type'] : 'script',
                'enabled' => rest_sanitize_boolean( $asset['enabled'] ?? true ),
            );
        }
        
        return $sanitized;
    }
}

// Initialize REST API
Asset_Control_REST_API::init();
