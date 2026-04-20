<?php
/**
 * Dequeue functionality for Asset Control plugin
 * 
 * @package Asset_Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle dequeuing of disabled assets on frontend
 */
class Asset_Control_Dequeue {
    
    /**
     * Initialize dequeue hooks
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue_assets' ), 100 );
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_menu' ), 100 );
    }
    
    /**
     * Dequeue disabled assets based on current page context
     */
    public static function dequeue_assets() {
        // Don't run in admin
        if ( is_admin() ) {
            return;
        }
        
        $context = self::get_current_context_id();
        
        if ( ! $context ) {
            return;
        }
        
        // Get disabled scripts
        $disabled_scripts = Asset_Control_Core::get_disabled_assets( $context, 'scripts' );
        
        // Get disabled styles
        $disabled_styles = Asset_Control_Core::get_disabled_assets( $context, 'styles' );
        
        // Dequeue scripts
        foreach ( $disabled_scripts as $handle ) {
            wp_dequeue_script( $handle );
            wp_deregister_script( $handle );
        }
        
        // Dequeue styles
        foreach ( $disabled_styles as $handle ) {
            wp_dequeue_style( $handle );
            wp_deregister_style( $handle );
        }
        
        /**
         * Action fired after assets have been dequeued
         * 
         * @param string $context Current context ID
         * @param array $disabled_scripts Array of disabled script handles
         * @param array $disabled_styles Array of disabled style handles
         */
        do_action( 'asset_control_after_dequeue', $context, $disabled_scripts, $disabled_styles );
    }
    
    /**
     * Get current context ID for asset management
     * 
     * @return string|false Context ID or false if not applicable
     */
    private static function get_current_context_id() {
        // Front page
        if ( is_front_page() ) {
            return 'front_page';
        }
        
        // Singular posts/pages
        if ( is_singular() ) {
            return get_queried_object_id();
        }
        
        // Category archives
        if ( is_category() ) {
            $term = get_queried_object();
            return 'category_' . $term->term_id;
        }
        
        // Tag archives
        if ( is_tag() ) {
            $term = get_queried_object();
            return 'tag_' . $term->term_id;
        }
        
        // Other archives - use post type
        if ( is_archive() ) {
            $post_type = get_post_type();
            return 'archive_' . $post_type;
        }
        
        // Search results
        if ( is_search() ) {
            return 'search';
        }
        
        // 404 page
        if ( is_404() ) {
            return '404';
        }
        
        return false;
    }
    
    /**
     * Add admin bar menu for quick access
     * 
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public static function add_admin_bar_menu( $wp_admin_bar ) {
        // Only for admins
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Add main menu item
        $wp_admin_bar->add_node( array(
            'id' => 'asset-control',
            'title' => '<span class="ab-icon dashicons dashicons-performance"></span><span class="ab-label">' . __( 'Asset Control', 'asset-control' ) . '</span>',
            'href' => admin_url( 'admin.php?page=asset-control' ),
            'parent' => 'top-secondary',
        ) );
        
        // Add "Manage this page" submenu when viewing a specific page
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $post_type = get_post_type_object( get_post_type( $post_id ) );
            
            $wp_admin_bar->add_node( array(
                'id' => 'asset-control-manage',
                'title' => sprintf( __( 'Manage %s Assets', 'asset-control' ), $post_type->labels->singular_name ),
                'href' => admin_url( 'admin.php?page=asset-control' ) . '#page-' . $post_id,
                'parent' => 'asset-control',
            ) );
        }
        
        // Add "View all pages" submenu
        $wp_admin_bar->add_node( array(
            'id' => 'asset-control-all',
            'title' => __( 'All Pages', 'asset-control' ),
            'href' => admin_url( 'admin.php?page=asset-control' ),
            'parent' => 'asset-control',
        ) );
    }
}

// Initialize dequeue functionality
Asset_Control_Dequeue::init();
