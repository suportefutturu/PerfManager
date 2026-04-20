<?php
/**
 * Classe de desinstalação do plugin.
 *
 * @package PerfManager\Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Core;

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Gerencia a desinstalação do plugin.
 */
final class Uninstall
{
    /**
     * Método executado na desinstalação do plugin.
     */
    public static function uninstall(): void
    {
        global $wpdb;

        self::delete_all_post_meta();
        self::delete_options();
        self::clear_scheduled_hooks();
    }

    /**
     * Remove todo o post meta do plugin.
     */
    private static function delete_all_post_meta(): void
    {
        $meta_keys = ['_disabled_scripts', '_disabled_styles'];

        $posts = get_posts([
            'post_type' => 'any',
            'post_status' => 'any',
            'fields' => 'ids',
            'numberposts' => -1,
        ]);

        foreach ($posts as $post_id) {
            foreach ($meta_keys as $meta_key) {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }

    /**
     * Remove opções do plugin.
     */
    private static function delete_options(): void
    {
        delete_option('perfmanager_version');
        delete_option('perfmanager_settings');
    }

    /**
     * Limpa hooks agendados.
     */
    private static function clear_scheduled_hooks(): void
    {
        wp_clear_scheduled_hook('perfmanager_cleanup');
    }
}
