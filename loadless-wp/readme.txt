=== LoadLess WP ===
Contributors: loadlesswp
Tags: scripts, styles, performance, optimization, assets, gutenberg, block
Tested up to: 6.7
Stable tag: 1.0.0
Requires at least: 5.8
Requires PHP: 7.4
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Optimize your WordPress site by managing scripts and styles on a per-page basis. Features Gutenberg block, shortcode support, and comprehensive settings.

== Description ==

**LoadLess WP** helps you optimize your WordPress website's performance by letting you manage the scripts and styles loaded on each page or post. Identify and disable assets that you don't need on specific pages, reducing page load times and enhancing user experience.

= Key Features =

* **Per-Page Asset Management**: View and toggle scripts and styles for any page or post
* **Gutenberg Block Support**: Native block editor integration for asset management
* **Shortcode Support**: Use `[loadless_wp]` shortcode to display asset info on frontend
* **Comprehensive Settings Page**: Full Settings API integration with configurable options
* **Security First**: Nonce verification, capability checks, and proper data sanitization
* **Pagination & Search**: Easily navigate through large numbers of assets
* **Filter by Type**: View all assets, scripts only, or styles only
* **Bulk Operations**: Enable or disable multiple assets at once
* **Internationalization Ready**: Full translation support with text domain
* **Developer Friendly**: Object-oriented architecture, well-documented code

= How It Works =

1. Select a page or post from the admin interface
2. View all scripts and styles loaded on that page
3. Toggle assets on/off using intuitive switches
4. Changes are saved per-page and take effect immediately

= Security Features =

* All REST API endpoints require `manage_options` capability
* Nonce verification on all AJAX requests
* Input sanitization using WordPress core functions
* Output escaping with `esc_html()`, `esc_url()`, and `wp_kses()`
* Proper capability checks before displaying sensitive information

= Shortcodes =

Use the following shortcodes in your posts or pages:

* `[loadless_wp]` - Display asset management info for current post
* `[loadless_wp post_id="123"]` - Display info for specific post ID
* `[loadless_wp show_link="false"]` - Hide the admin link

= Gutenberg Block =

Add the "LoadLess WP Asset Manager" block to any post or page to display asset information directly in your content.

= REST API Endpoints =

The plugin provides the following REST API endpoints:

* `GET /wp-json/loadless-wp/v1/pages` - Get list of all pages
* `GET /wp-json/loadless-wp/v1/assets?page_id=X` - Get assets for a page
* `POST /wp-json/loadless-wp/v1/toggle-asset` - Toggle an asset
* `POST /wp-json/loadless-wp/v1/bulk-toggle` - Bulk toggle assets

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/loadless-wp` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **LoadLess WP** > **Asset Manager** in the WordPress admin dashboard.
4. Select a page and manage the assets as needed.

== Frequently Asked Questions ==

= How does this plugin work? =

The plugin scans the scripts and styles enqueued on each page and displays them in a list. You can toggle specific assets on or off to control their loading on that specific page only.

= Does this plugin modify files permanently? =

No, it only disables assets dynamically for specific pages during runtime. It doesn't alter any files or permanently remove assets from your site.

= Will it break my site if I disable certain assets? =

It's possible. Be cautious when disabling assets. Make sure to test your pages after making changes. The plugin includes a confirmation dialog to warn you before disabling assets.

= Is this plugin compatible with caching plugins? =

Yes, but you may need to clear your cache after making changes for them to take effect. Some aggressive caching plugins might cache the entire page including which assets are loaded.

= Why don't I see scripts from all the plugins I installed? =

The plugin scans scripts and styles enqueued using `wp_enqueue_script()` and `wp_enqueue_style()`. Scripts that are loaded dynamically or use other methods will not be detected.

= Can I manage assets for custom post types? =

Yes! Go to LoadLess WP > Settings and select which post types should have asset management enabled.

= Does this work with the block editor (Gutenberg)? =

Yes! The plugin includes a native Gutenberg block that you can add to any post or page.

= Is my data secure? =

Yes. The plugin uses WordPress security best practices including nonce verification, capability checks, input sanitization, and output escaping.

== Screenshots ==

1. Asset Manager interface showing scripts and styles with toggle controls
2. Settings page with configurable options
3. Gutenberg block in the editor
4. Frontend display using shortcode

== Changelog ==

= 1.0.0 =
* Initial release
* Complete rewrite with object-oriented architecture
* Singleton pattern for main plugin class
* Settings API integration for configuration
* Gutenberg block support with block.json
* Shortcode support ([loadless_wp])
* REST API with proper authentication
* Enhanced security with nonces and capability checks
* Full internationalization support
* Pagination and search functionality
* Bulk asset operations
* Comprehensive uninstall cleanup

== Upgrade Notice ==

= 1.0.0 =
Initial release. Install and activate to start managing your assets.

== License ==

This plugin is licensed under the GPL v3 or later. See [License URI](https://www.gnu.org/licenses/gpl-3.0.html) for details.

== Credits ==

Built following WordPress coding standards and best practices.
