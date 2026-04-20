<?php
/**
 * Shortcodes Handler Class
 *
 * Registers and handles shortcodes for LoadLess WP.
 *
 * @package LoadLessWP
 * @since 1.0.0
 */

namespace LoadLessWP;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class Shortcodes
 *
 * @since 1.0.0
 */
class Shortcodes {

    /**
     * Constructor.
     */
    public function __construct() {
        add_shortcode( 'loadless_wp', [ $this, 'render_shortcode' ] );
        add_shortcode( 'loadless-wp', [ $this, 'render_shortcode' ] );
    }

    /**
     * Render shortcode output.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render_shortcode( array $atts ): string {
        // Check permissions - only show to users who can manage options.
        if ( ! current_user_can( 'manage_options' ) ) {
            return '';
        }

        // Parse attributes with defaults.
        $atts = shortcode_atts(
            [
                'post_id' => get_the_ID(),
                'show_link' => 'true',
            ],
            $atts,
            'loadless_wp'
        );

        // Sanitize post_id.
        $post_id = absint( $atts['post_id'] );

        if ( ! $post_id ) {
            return '<p>' . esc_html__( 'No post ID available.', 'loadless-wp' ) . '</p>';
        }

        // Get disabled assets for this post.
        $disabled_scripts = get_post_meta( $post_id, '_disabled_scripts', true );
        $disabled_styles  = get_post_meta( $post_id, '_disabled_styles', true );

        $disabled_scripts = is_array( $disabled_scripts ) ? $disabled_scripts : [];
        $disabled_styles  = is_array( $disabled_styles ) ? $disabled_styles : [];

        // Allowed HTML tags for wp_kses.
        $allowed_html = [
            'div'    => [ 'class' => [], 'data-post-id' => [] ],
            'h3'     => [],
            'ul'     => [],
            'li'     => [],
            'strong' => [],
            'span'   => [ 'class' => [] ],
            'p'      => [],
            'a'      => [ 'href' => [], 'class' => [] ],
        ];

        ob_start();
        ?>
        <div class="loadless-wp-shortcode" data-post-id="<?php echo esc_attr( $post_id ); ?>">
            <h3><?php esc_html_e( 'Asset Management', 'loadless-wp' ); ?></h3>

            <?php if ( ! empty( $disabled_scripts ) ) : ?>
                <div class="loadless-wp-section">
                    <strong><?php esc_html_e( 'Disabled Scripts:', 'loadless-wp' ); ?></strong>
                    <ul>
                        <?php foreach ( array_slice( $disabled_scripts, 0, 5 ) as $handle ) : ?>
                            <li><?php echo esc_html( sanitize_text_field( $handle ) ); ?></li>
                        <?php endforeach; ?>
                        <?php if ( count( $disabled_scripts ) > 5 ) : ?>
                            <li>
                                <span class="loadless-wp-more">
                                    <?php
                                    printf(
                                        /* translators: %d: number of additional items */
                                        esc_html( _n( '+%d more', '+%d more', count( $disabled_scripts ) - 5, 'loadless-wp' ) ),
                                        esc_html( count( $disabled_scripts ) - 5 )
                                    );
                                    ?>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $disabled_styles ) ) : ?>
                <div class="loadless-wp-section">
                    <strong><?php esc_html_e( 'Disabled Styles:', 'loadless-wp' ); ?></strong>
                    <ul>
                        <?php foreach ( array_slice( $disabled_styles, 0, 5 ) as $handle ) : ?>
                            <li><?php echo esc_html( sanitize_text_field( $handle ) ); ?></li>
                        <?php endforeach; ?>
                        <?php if ( count( $disabled_styles ) > 5 ) : ?>
                            <li>
                                <span class="loadless-wp-more">
                                    <?php
                                    printf(
                                        /* translators: %d: number of additional items */
                                        esc_html( _n( '+%d more', '+%d more', count( $disabled_styles ) - 5, 'loadless-wp' ) ),
                                        esc_html( count( $disabled_styles ) - 5 )
                                    );
                                    ?>
                                </span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ( empty( $disabled_scripts ) && empty( $disabled_styles ) ) : ?>
                <p><?php esc_html_e( 'No assets are currently disabled for this page.', 'loadless-wp' ); ?></p>
            <?php endif; ?>

            <?php if ( 'true' === $atts['show_link'] ) : ?>
                <p class="loadless-wp-link">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=loadless-wp-manager' ) ); ?>">
                        <?php esc_html_e( 'Manage all assets in admin', 'loadless-wp' ); ?> &rarr;
                    </a>
                </p>
            <?php endif; ?>
        </div>
        <?php
        return wp_kses( ob_get_clean(), $allowed_html );
    }
}

// Initialize shortcodes handler.
new Shortcodes();
