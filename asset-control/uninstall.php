<?php
/**
 * Uninstall handler for Asset Control plugin
 * 
 * Removes all plugin data from the database when the plugin is deleted.
 * 
 * @package Asset_Control
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin options
delete_option( 'asset_control_version' );

// Delete all transients
global $wpdb;
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_asset_control_%'" );
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_asset_control_%'" );

// Delete all post meta for disabled assets
$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '_asset_control_disabled_%'" );

// Clear any cached data
wp_cache_flush();
