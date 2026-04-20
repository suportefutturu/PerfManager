<?php
/**
 * Plugin Name: LoadLess WP
 * Plugin URI: https://example.com/loadless-wp
 * Description: Manage and optimize scripts and styles on a per-page basis to improve WordPress performance. Features Gutenberg block, shortcode support, and a comprehensive settings page.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: LoadLess WP Team
 * Author URI: https://example.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: loadless-wp
 * Domain Path: /languages
 */

namespace LoadLessWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'LOADLESS_WP_VERSION', '1.0.0' );
define( 'LOADLESS_WP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOADLESS_WP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LOADLESS_WP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class - Singleton Pattern
 *
 * @since 1.0.0
 */
final class LoadLessWP {

    /**
     * Single instance of the class.
     *
     * @var LoadLessWP
     */
    private static $instance = null;

    /**
     * Get instance of the class.
     *
     * @return LoadLessWP
     */
    public static function get_instance(): LoadLessWP {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Private to enforce singleton.
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Prevent cloning of the class.
     */
    private function __clone() {}

    /**
     * Prevent unserialization of the class.
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Load required files.
     *
     * @since 1.0.0
     */
    private function load_dependencies(): void {
        $files = [
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-rest-api.php',
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-admin.php',
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-dequeue.php',
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-settings.php',
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-blocks.php',
            LOADLESS_WP_PLUGIN_DIR . 'includes/class-shortcodes.php',
        ];

        foreach ( $files as $file ) {
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }
    }

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks(): void {
        // Activation and deactivation hooks.
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        // Load text domain for internationalization.
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
    }

    /**
     * Activation hook callback.
     *
     * @since 1.0.0
     */
    public function activate(): void {
        // Flush rewrite rules for REST API routes.
        flush_rewrite_rules();

        // Set default options if not exist.
        $defaults = [
            'loadless_wp_enabled'           => true,
            'loadless_wp_show_core_assets'  => false,
            'loadless_wp_default_view'      => 'all',
            'loadless_wp_items_per_page'    => 20,
        ];

        foreach ( $defaults as $option => $value ) {
            if ( ! get_option( $option ) ) {
                update_option( $option, $value );
            }
        }
    }

    /**
     * Deactivation hook callback.
     *
     * @since 1.0.0
     */
    public function deactivate(): void {
        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain for translations.
     *
     * @since 1.0.0
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'loadless-wp',
            false,
            dirname( LOADLESS_WP_PLUGIN_BASENAME ) . '/languages'
        );
    }
}

// Initialize the plugin.
LoadLessWP::get_instance();
