<?php
/**
 * Plugin Name: Asset Control
 * Plugin URI: https://example.com/asset-control
 * Description: Permite visualizar, gerenciar e controlar quais scripts (CSS e JS) são carregados em cada página do site, melhorando o desempenho e velocidade.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asset-control
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ASSET_CONTROL_VERSION', '1.0.0' );
define( 'ASSET_CONTROL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASSET_CONTROL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASSET_CONTROL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class Asset_Control {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core functions
        require_once ASSET_CONTROL_PLUGIN_DIR . 'includes/core.php';
        
        // Admin functions
        require_once ASSET_CONTROL_PLUGIN_DIR . 'includes/admin.php';
        
        // REST API
        require_once ASSET_CONTROL_PLUGIN_DIR . 'includes/rest-api.php';
        
        // Dequeue functionality
        require_once ASSET_CONTROL_PLUGIN_DIR . 'includes/dequeue.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation hook
        register_activation_hook( __FILE__, array( 'Asset_Control_Core', 'activate' ) );
        
        // Deactivation hook
        register_deactivation_hook( __FILE__, array( 'Asset_Control_Core', 'deactivate' ) );
    }
}

// Initialize the plugin
function asset_control_init() {
    return Asset_Control::get_instance();
}
add_action( 'plugins_loaded', 'asset_control_init' );
