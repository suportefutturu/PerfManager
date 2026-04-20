<?php
/**
 * Classe de ativação do plugin.
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
 * Gerencia a ativação do plugin.
 */
final class Activation
{
    /**
     * Método executado na ativação do plugin.
     */
    public static function activate(): void
    {
        global $wpdb;

        self::migrate_legacy_data();
        self::schedule_cleanup();
        
        flush_rewrite_rules();
    }

    /**
     * Migra dados legados da versão anterior.
     */
    private static function migrate_legacy_data(): void
    {
        // A migração é transparente - as chaves de meta são as mesmas
        // _disabled_scripts e _disabled_styles permanecem compatíveis
    }

    /**
     * Agenda tarefa de limpeza.
     */
    private static function schedule_cleanup(): void
    {
        if (!wp_next_scheduled('perfmanager_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'perfmanager_cleanup');
        }
    }
}
