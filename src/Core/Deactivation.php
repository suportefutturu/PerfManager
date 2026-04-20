<?php
/**
 * Classe de desativação do plugin.
 *
 * @package PerfManager\Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gerencia a desativação do plugin.
 */
final class Deactivation
{
    /**
     * Método executado na desativação do plugin.
     */
    public static function deactivate(): void
    {
        self::unschedule_cleanup();
        flush_rewrite_rules();
    }

    /**
     * Remove tarefas agendadas.
     */
    private static function unschedule_cleanup(): void
    {
        $timestamp = wp_next_scheduled('perfmanager_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'perfmanager_cleanup');
        }
    }
}
