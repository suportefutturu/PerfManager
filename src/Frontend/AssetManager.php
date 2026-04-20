<?php
/**
 * Gerenciador de assets no frontend.
 *
 * @package PerfManager\Frontend
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Frontend;

use PerfManager\get_asset_meta_key;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gerencia o dequeue de assets no frontend.
 */
final class AssetManager
{
    /**
     * Remove assets desabilitados para a página atual.
     */
    public function dequeue_disabled_assets(): void
    {
        if (is_admin()) {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $page_id = get_queried_object_id();
        
        if (!$page_id) {
            return;
        }

        $disabled_scripts = $this->get_disabled_assets($page_id, 'script');
        $disabled_styles = $this->get_disabled_assets($page_id, 'style');

        foreach ($disabled_scripts as $handle) {
            wp_dequeue_script($handle);
        }

        foreach ($disabled_styles as $handle) {
            wp_dequeue_style($handle);
        }
    }

    /**
     * Obtém lista de assets desabilitados.
     *
     * @param int $page_id ID da página.
     * @param string $type Tipo do asset.
     * @return array<string>
     */
    private function get_disabled_assets(int $page_id, string $type): array
    {
        $meta_key = get_asset_meta_key($type);
        $assets = get_post_meta($page_id, $meta_key, true);
        
        if (!is_array($assets)) {
            return [];
        }

        return array_filter($assets, fn($handle) => is_string($handle) && $handle !== '');
    }
}
