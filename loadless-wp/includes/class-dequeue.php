<?php
/**
 * Dequeue Handler Class
 *
 * Handles dequeuing of scripts and styles based on per-page settings.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

namespace LoadLessWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Dequeue
 *
 * @since 1.0.0
 */
class Dequeue {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_assets' ], 100 );
    }

    /**
     * Dequeue assets based on page settings.
     *
     * @since 1.0.0
     */
    public function dequeue_assets(): void {
        // Check if plugin is enabled.
        if ( ! get_option( 'loadless_wp_enabled', true ) ) {
            return;
        }

        // Don't run in admin area.
        if ( is_admin() ) {
            return;
        }

        // Get current post ID.
        $post_id = get_queried_object_id();

        if ( ! $post_id ) {
            return;
        }

        // Check if post type is allowed.
        $post_type      = get_post_type( $post_id );
        $allowed_types  = get_option( 'loadless_wp_allowed_post_types', [ 'page', 'post' ] );

        if ( ! $post_type || ! in_array( $post_type, $allowed_types, true ) ) {
            return;
        }

        // Get disabled scripts and styles for this page.
        $disabled_scripts = get_post_meta( $post_id, '_disabled_scripts', true );
        $disabled_styles  = get_post_meta( $post_id, '_disabled_styles', true );

        // Ensure arrays.
        $disabled_scripts = is_array( $disabled_scripts ) ? $disabled_scripts : [];
        $disabled_styles  = is_array( $disabled_styles ) ? $disabled_styles : [];

        // Sanitize handles before dequeuing.
        $disabled_scripts = array_map( 'sanitize_text_field', $disabled_scripts );
        $disabled_styles  = array_map( 'sanitize_text_field', $disabled_styles );

        // Dequeue scripts.
        foreach ( $disabled_scripts as $handle ) {
            if ( ! empty( $handle ) && wp_script_is( $handle, 'enqueued' ) ) {
                wp_dequeue_script( $handle );
            }
        }

        // Dequeue styles.
        foreach ( $disabled_styles as $handle ) {
            if ( ! empty( $handle ) && wp_style_is( $handle, 'enqueued' ) ) {
                wp_dequeue_style( $handle );
            }
        }
    }
}

// Initialize dequeue handler.
new Dequeue();
