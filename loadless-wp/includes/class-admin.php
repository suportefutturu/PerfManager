<?php
/**
 * Admin Handler Class
 *
 * Handles admin menu, assets, and settings page for LoadLess WP.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

namespace LoadLessWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Admin
 *
 * @since 1.0.0
 */
class Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'plugin_action_links_' . LOADLESS_WP_PLUGIN_BASENAME, [ $this, 'add_plugin_links' ] );
    }

    /**
     * Add admin menu page.
     *
     * @since 1.0.0
     */
    public function add_admin_menu(): void {
        add_menu_page(
            __( 'LoadLess WP Settings', 'loadless-wp' ),
            __( 'LoadLess WP', 'loadless-wp' ),
            'manage_options',
            'loadless-wp',
            [ $this, 'render_settings_page' ],
            'dashicons-performance',
            100
        );

        add_submenu_page(
            'loadless-wp',
            __( 'Asset Manager', 'loadless-wp' ),
            __( 'Asset Manager', 'loadless-wp' ),
            'manage_options',
            'loadless-wp-manager',
            [ $this, 'render_manager_page' ]
        );

        add_submenu_page(
            'loadless-wp',
            __( 'Settings', 'loadless-wp' ),
            __( 'Settings', 'loadless-wp' ),
            'manage_options',
            'loadless-wp-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( string $hook ): void {
        // Only load on plugin pages.
        if ( false === strpos( $hook, 'loadless-wp' ) ) {
            return;
        }

        // Get version for cache busting.
        $version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : LOADLESS_WP_VERSION;

        // Enqueue admin CSS.
        wp_enqueue_style(
            'loadless-wp-admin',
            LOADLESS_WP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            $version
        );

        // Enqueue admin JS only on manager page.
        if ( 'toplevel_page_loadless-wp-manager' === $hook || 'loadless-wp_page_loadless-wp-manager' === $hook ) {
            wp_enqueue_script(
                'loadless-wp-admin',
                LOADLESS_WP_PLUGIN_URL . 'assets/js/admin.js',
                [ 'wp-element', 'wp-api-fetch', 'wp-components', 'wp-i18n' ],
                $version,
                true
            );

            wp_set_script_translations( 'loadless-wp-admin', 'loadless-wp', LOADLESS_WP_PLUGIN_DIR . 'languages' );

            wp_localize_script(
                'loadless-wp-admin',
                'loadlessWPSettings',
                [
                    'apiUrl'   => rest_url( 'loadless-wp/v1' ),
                    'nonce'    => wp_create_nonce( 'wp_rest' ),
                    'strings'  => [
                        'confirmDisable' => __( 'Disabling this asset may break functionality. Are you sure?', 'loadless-wp' ),
                        'loading'        => __( 'Loading...', 'loadless-wp' ),
                        'error'          => __( 'An error occurred.', 'loadless-wp' ),
                    ],
                ]
            );
        }
    }

    /**
     * Add plugin action links.
     *
     * @since 1.0.0
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_plugin_links( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=loadless-wp-settings' ) ),
            __( 'Settings', 'loadless-wp' )
        );

        $manager_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'admin.php?page=loadless-wp-manager' ) ),
            __( 'Manage Assets', 'loadless-wp' )
        );

        array_unshift( $links, $settings_link, $manager_link );

        return $links;
    }

    /**
     * Render the settings page.
     *
     * @since 1.0.0
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'loadless_wp_options_group' );
                do_settings_sections( 'loadless-wp-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the asset manager page.
     *
     * @since 1.0.0
     */
    public function render_manager_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <div id="loadless-wp-manager-root"></div>
        </div>
        <?php
    }
}

// Initialize admin handler.
new Admin();
