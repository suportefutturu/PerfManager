<?php
/**
 * Settings Handler Class
 *
 * Registers and handles plugin settings via WordPress Settings API.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

namespace LoadLessWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Settings
 *
 * @since 1.0.0
 */
class Settings {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Register plugin settings.
     *
     * @since 1.0.0
     */
    public function register_settings(): void {
        // Register settings.
        register_setting(
            'loadless_wp_options_group',
            'loadless_wp_enabled',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        register_setting(
            'loadless_wp_options_group',
            'loadless_wp_show_core_assets',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => false,
            ]
        );

        register_setting(
            'loadless_wp_options_group',
            'loadless_wp_default_view',
            [
                'type'              => 'string',
                'sanitize_callback' => [ $this, 'sanitize_default_view' ],
                'default'           => 'all',
            ]
        );

        register_setting(
            'loadless_wp_options_group',
            'loadless_wp_items_per_page',
            [
                'type'              => 'integer',
                'sanitize_callback' => [ $this, 'sanitize_items_per_page' ],
                'default'           => 20,
            ]
        );

        register_setting(
            'loadless_wp_options_group',
            'loadless_wp_allowed_post_types',
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_post_types' ],
                'default'           => [ 'page', 'post' ],
            ]
        );

        // Add settings section.
        add_settings_section(
            'loadless_wp_general_section',
            __( 'General Settings', 'loadless-wp' ),
            [ $this, 'render_general_section_description' ],
            'loadless-wp-settings'
        );

        // Add settings fields.
        add_settings_field(
            'loadless_wp_enabled',
            __( 'Plugin Status', 'loadless-wp' ),
            [ $this, 'render_enabled_field' ],
            'loadless-wp-settings',
            'loadless_wp_general_section'
        );

        add_settings_field(
            'loadless_wp_show_core_assets',
            __( 'Show Core Assets', 'loadless-wp' ),
            [ $this, 'render_show_core_assets_field' ],
            'loadless-wp-settings',
            'loadless_wp_general_section'
        );

        add_settings_field(
            'loadless_wp_default_view',
            __( 'Default Asset View', 'loadless-wp' ),
            [ $this, 'render_default_view_field' ],
            'loadless-wp-settings',
            'loadless_wp_general_section'
        );

        add_settings_field(
            'loadless_wp_items_per_page',
            __( 'Items Per Page', 'loadless-wp' ),
            [ $this, 'render_items_per_page_field' ],
            'loadless-wp-settings',
            'loadless_wp_general_section'
        );

        add_settings_field(
            'loadless_wp_allowed_post_types',
            __( 'Allowed Post Types', 'loadless-wp' ),
            [ $this, 'render_allowed_post_types_field' ],
            'loadless-wp-settings',
            'loadless_wp_general_section'
        );
    }

    /**
     * Render section description.
     *
     * @since 1.0.0
     */
    public function render_general_section_description(): void {
        echo '<p>' . esc_html__( 'Configure the general settings for LoadLess WP.', 'loadless-wp' ) . '</p>';
    }

    /**
     * Render enabled field.
     *
     * @since 1.0.0
     */
    public function render_enabled_field(): void {
        $value = get_option( 'loadless_wp_enabled', true );
        ?>
        <label>
            <input type="checkbox" name="loadless_wp_enabled" value="1" <?php checked( $value, true ); ?> />
            <?php esc_html_e( 'Enable LoadLess WP functionality', 'loadless-wp' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'When disabled, no assets will be dequeued but you can still manage settings.', 'loadless-wp' ); ?>
        </p>
        <?php
    }

    /**
     * Render show core assets field.
     *
     * @since 1.0.0
     */
    public function render_show_core_assets_field(): void {
        $value = get_option( 'loadless_wp_show_core_assets', false );
        ?>
        <label>
            <input type="checkbox" name="loadless_wp_show_core_assets" value="1" <?php checked( $value, true ); ?> />
            <?php esc_html_e( 'Show WordPress core assets', 'loadless-wp' ); ?>
        </label>
        <p class="description">
            <?php esc_html_e( 'When enabled, WordPress core scripts and styles will be displayed in the asset manager.', 'loadless-wp' ); ?>
        </p>
        <?php
    }

    /**
     * Render default view field.
     *
     * @since 1.0.0
     */
    public function render_default_view_field(): void {
        $value = get_option( 'loadless_wp_default_view', 'all' );
        ?>
        <select name="loadless_wp_default_view">
            <option value="all" <?php selected( $value, 'all' ); ?>>
                <?php esc_html_e( 'All Assets', 'loadless-wp' ); ?>
            </option>
            <option value="script" <?php selected( $value, 'script' ); ?>>
                <?php esc_html_e( 'Scripts Only', 'loadless-wp' ); ?>
            </option>
            <option value="style" <?php selected( $value, 'style' ); ?>>
                <?php esc_html_e( 'Styles Only', 'loadless-wp' ); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e( 'Select which type of assets to display by default.', 'loadless-wp' ); ?>
        </p>
        <?php
    }

    /**
     * Render items per page field.
     *
     * @since 1.0.0
     */
    public function render_items_per_page_field(): void {
        $value = get_option( 'loadless_wp_items_per_page', 20 );
        ?>
        <input type="number" name="loadless_wp_items_per_page" value="<?php echo esc_attr( $value ); ?>" min="5" max="100" step="5" />
        <p class="description">
            <?php esc_html_e( 'Number of assets to display per page (5-100).', 'loadless-wp' ); ?>
        </p>
        <?php
    }

    /**
     * Render allowed post types field.
     *
     * @since 1.0.0
     */
    public function render_allowed_post_types_field(): void {
        $value = get_option( 'loadless_wp_allowed_post_types', [ 'page', 'post' ] );
        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        ?>
        <fieldset>
            <?php foreach ( $post_types as $post_type ) : ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox" name="loadless_wp_allowed_post_types[]" value="<?php echo esc_attr( $post_type->name ); ?>" <?php echo in_array( $post_type->name, $value, true ) ? 'checked' : ''; ?> />
                    <?php echo esc_html( $post_type->label ); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description">
            <?php esc_html_e( 'Select which post types should have asset management enabled.', 'loadless-wp' ); ?>
        </p>
        <?php
    }

    /**
     * Sanitize default view option.
     *
     * @since 1.0.0
     *
     * @param string $value The value to sanitize.
     * @return string Sanitized value.
     */
    public function sanitize_default_view( string $value ): string {
        $allowed = [ 'all', 'script', 'style' ];
        return in_array( $value, $allowed, true ) ? $value : 'all';
    }

    /**
     * Sanitize items per page option.
     *
     * @since 1.0.0
     *
     * @param mixed $value The value to sanitize.
     * @return int Sanitized value.
     */
    public function sanitize_items_per_page( $value ): int {
        $value = absint( $value );
        return max( 5, min( 100, $value ) );
    }

    /**
     * Sanitize allowed post types option.
     *
     * @since 1.0.0
     *
     * @param array $value The value to sanitize.
     * @return array Sanitized value.
     */
    public function sanitize_post_types( array $value ): array {
        $allowed_post_types = get_post_types( [ 'public' => true ], 'names' );
        $sanitized          = [];

        foreach ( $value as $post_type ) {
            $post_type = sanitize_text_field( $post_type );
            if ( in_array( $post_type, $allowed_post_types, true ) ) {
                $sanitized[] = $post_type;
            }
        }

        return ! empty( $sanitized ) ? $sanitized : [ 'page', 'post' ];
    }
}

// Initialize settings handler.
new Settings();
