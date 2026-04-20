<?php
/**
 * Uninstall handler for LoadLess WP
 *
 * Cleans up all plugin data when the plugin is uninstalled.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin options.
$plugin_options = [
    'loadless_wp_enabled',
    'loadless_wp_show_core_assets',
    'loadless_wp_default_view',
    'loadless_wp_items_per_page',
    'loadless_wp_allowed_post_types',
];

foreach ( $plugin_options as $option ) {
    delete_option( $option );
}

// Cleanup all post meta for '_disabled_scripts' and '_disabled_styles'.
$meta_keys = [ '_disabled_scripts', '_disabled_styles' ];

// Get all posts (all types).
$args = [
    'post_type'   => 'any',
    'post_status' => 'any',
    'fields'      => 'ids',
    'numberposts' => -1,
];

$posts = get_posts( $args );

// Delete metadata for each post.
foreach ( $posts as $post_id ) {
    foreach ( $meta_keys as $meta_key ) {
        delete_post_meta( $post_id, $meta_key );
    }
}

// Clear any transients created by the plugin.
delete_transient( 'loadless_wp_pages_cache' );
