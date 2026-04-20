<?php
/**
 * Core functions for Asset Control plugin
 * 
 * @package Asset_Control
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main core class
 */
class Asset_Control_Core {
    
    /**
     * Meta key prefix for disabled assets
     */
    const META_PREFIX = '_asset_control_disabled_';
    
    /**
     * Transient name prefix
     */
    const TRANSIENT_PREFIX = 'asset_control_assets_';
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Set default options if needed
        add_option( 'asset_control_version', ASSET_CONTROL_VERSION );
        
        // Clear any existing transients
        self::clear_all_transients();
        
        // Flush rewrite rules for REST API
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear transients
        self::clear_all_transients();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Clear all plugin transients
     */
    public static function clear_all_transients() {
        global $wpdb;
        
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_asset_control_%'" );
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_asset_control_%'" );
    }
    
    /**
     * Get disabled assets for a specific page/context
     * 
     * @param string $context The context identifier (post type, page ID, etc.)
     * @param string $type Asset type: 'scripts' or 'styles'
     * @return array Array of disabled asset handles
     */
    public static function get_disabled_assets( $context, $type = 'scripts' ) {
        $meta_key = self::META_PREFIX . $type;
        $disabled = get_post_meta( $context, $meta_key, true );
        
        if ( ! is_array( $disabled ) ) {
            $disabled = array();
        }
        
        return $disabled;
    }
    
    /**
     * Update disabled assets for a specific page/context
     * 
     * @param string $context The context identifier
     * @param string $type Asset type: 'scripts' or 'styles'
     * @param array $handles Array of asset handles to disable
     * @return bool Success status
     */
    public static function update_disabled_assets( $context, $type, $handles ) {
        $meta_key = self::META_PREFIX . $type;
        
        // Sanitize handles
        $handles = array_map( 'sanitize_text_field', $handles );
        $handles = array_filter( $handles ); // Remove empty values
        
        return update_post_meta( $context, $meta_key, $handles );
    }
    
    /**
     * Add a single disabled asset handle
     * 
     * @param string $context The context identifier
     * @param string $type Asset type: 'scripts' or 'styles'
     * @param string $handle The asset handle to disable
     * @return bool Success status
     */
    public static function add_disabled_asset( $context, $type, $handle ) {
        $disabled = self::get_disabled_assets( $context, $type );
        
        if ( ! in_array( $handle, $disabled, true ) ) {
            $disabled[] = $handle;
            return self::update_disabled_assets( $context, $type, $disabled );
        }
        
        return false;
    }
    
    /**
     * Remove a single disabled asset handle
     * 
     * @param string $context The context identifier
     * @param string $type Asset type: 'scripts' or 'styles'
     * @param string $handle The asset handle to enable
     * @return bool Success status
     */
    public static function remove_disabled_asset( $context, $type, $handle ) {
        $disabled = self::get_disabled_assets( $context, $type );
        
        if ( in_array( $handle, $disabled, true ) ) {
            $disabled = array_diff( $disabled, array( $handle ) );
            return self::update_disabled_assets( $context, $type, $disabled );
        }
        
        return false;
    }
    
    /**
     * Check if an asset is disabled for a context
     * 
     * @param string $context The context identifier
     * @param string $type Asset type: 'scripts' or 'styles'
     * @param string $handle The asset handle
     * @return bool True if disabled
     */
    public static function is_asset_disabled( $context, $type, $handle ) {
        $disabled = self::get_disabled_assets( $context, $type );
        return in_array( $handle, $disabled, true );
    }
    
    /**
     * Get file size from URL (with caching)
     * 
     * @param string $url The asset URL
     * @return string Formatted file size or empty string
     */
    public static function get_file_size( $url ) {
        // Try to get from transient first
        $cache_key = 'size_' . md5( $url );
        $cached = get_transient( 'asset_control_' . $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }
        
        $size = '';
        
        // Only check local files
        if ( strpos( $url, site_url() ) === 0 || strpos( $url, '/' ) === 0 ) {
            $path = str_replace( site_url(), ABSPATH, $url );
            $path = str_replace( '//', '/', $path );
            
            if ( file_exists( $path ) ) {
                $bytes = filesize( $path );
                $size = self::format_file_size( $bytes );
            }
        }
        
        // Cache for 1 hour
        set_transient( 'asset_control_' . $cache_key, $size, HOUR_IN_SECONDS );
        
        return $size;
    }
    
    /**
     * Format file size in human readable format
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted size
     */
    public static function format_file_size( $bytes ) {
        if ( $bytes <= 0 ) {
            return '';
        }
        
        $units = array( 'B', 'KB', 'MB', 'GB' );
        $bytes = max( $bytes, 0 );
        $pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow = min( $pow, count( $units ) - 1 );
        $bytes /= pow( 1024, $pow );
        
        return round( $bytes, 2 ) . ' ' . $units[ $pow ];
    }
    
    /**
     * Get current context for asset management
     * 
     * @return array Context information
     */
    public static function get_current_context() {
        $context = array(
            'type' => 'unknown',
            'id' => 0,
            'label' => '',
            'url' => home_url( '/' ),
        );
        
        if ( is_front_page() ) {
            $context['type'] = 'front_page';
            $context['label'] = __( 'Homepage', 'asset-control' );
            $context['url'] = home_url( '/' );
        } elseif ( is_home() ) {
            $context['type'] = 'blog_page';
            $context['label'] = __( 'Blog Page', 'asset-control' );
            $context['url'] = get_permalink( get_option( 'page_for_posts' ) );
        } elseif ( is_singular() ) {
            $post_id = get_queried_object_id();
            $context['type'] = 'singular';
            $context['id'] = $post_id;
            $context['label'] = get_the_title( $post_id );
            $context['url'] = get_permalink( $post_id );
        } elseif ( is_category() ) {
            $term = get_queried_object();
            $context['type'] = 'category';
            $context['id'] = $term->term_id;
            $context['label'] = sprintf( __( 'Category: %s', 'asset-control' ), $term->name );
            $context['url'] = get_term_link( $term );
        } elseif ( is_tag() ) {
            $term = get_queried_object();
            $context['type'] = 'tag';
            $context['id'] = $term->term_id;
            $context['label'] = sprintf( __( 'Tag: %s', 'asset-control' ), $term->name );
            $context['url'] = get_term_link( $term );
        } elseif ( is_archive() ) {
            $context['type'] = 'archive';
            $context['label'] = get_the_archive_title();
            $context['url'] = get_archives_link();
        } elseif ( is_search() ) {
            $context['type'] = 'search';
            $context['label'] = __( 'Search Results', 'asset-control' );
            $context['url'] = get_search_link();
        } elseif ( is_404() ) {
            $context['type'] = '404';
            $context['label'] = __( '404 Page', 'asset-control' );
            $context['url'] = home_url( '/404' );
        }
        
        return $context;
    }
    
    /**
     * Get all available pages/post types for selection
     * 
     * @return array Array of pages with their info
     */
    public static function get_available_pages() {
        $pages = array();
        
        // Add homepage
        $pages[] = array(
            'id' => 'front_page',
            'title' => __( 'Homepage', 'asset-control' ),
            'type' => 'front_page',
            'url' => home_url( '/' ),
        );
        
        // Add blog page if exists
        if ( get_option( 'page_for_posts' ) ) {
            $blog_id = get_option( 'page_for_posts' );
            $pages[] = array(
                'id' => $blog_id,
                'title' => get_the_title( $blog_id ),
                'type' => 'blog_page',
                'url' => get_permalink( $blog_id ),
            );
        }
        
        // Add regular pages
        $regular_pages = get_posts( array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ) );
        
        foreach ( $regular_pages as $page ) {
            // Skip if already added (like blog page)
            if ( wp_list_filter( $pages, array( 'id' => $page->ID ) ) ) {
                continue;
            }
            
            $pages[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'type' => 'page',
                'url' => get_permalink( $page->ID ),
            );
        }
        
        // Add custom post types
        $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'objects' );
        
        foreach ( $post_types as $post_type ) {
            $samples = get_posts( array(
                'post_type' => $post_type->name,
                'post_status' => 'publish',
                'numberposts' => 5,
                'orderby' => 'date',
                'order' => 'DESC',
            ) );
            
            foreach ( $samples as $sample ) {
                $pages[] = array(
                    'id' => $sample->ID,
                    'title' => sprintf( '[%s] %s', $post_type->label, $sample->post_title ),
                    'type' => $post_type->name,
                    'url' => get_permalink( $sample->ID ),
                );
            }
        }
        
        /**
         * Filter available pages for asset control
         * 
         * @param array $pages Array of available pages
         */
        return apply_filters( 'asset_control_available_pages', $pages );
    }
}
