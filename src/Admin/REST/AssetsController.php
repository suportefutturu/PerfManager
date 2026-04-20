<?php
/**
 * Controller REST API para gerenciamento de assets.
 *
 * @package PerfManager\Admin\REST
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Admin\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use PerfManager\{sanitize_asset_handle, validate_asset_type, get_asset_meta_key, current_user_can_manage_assets};

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gerencia endpoints REST API para assets.
 */
final class AssetsController
{
    private string $namespace = 'perfmanager/v1';

    /**
     * Registra as rotas da API REST.
     */
    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/pages', [
            'methods' => 'GET',
            'callback' => [$this, 'get_pages'],
            'permission_callback' => fn() => current_user_can_manage_assets(),
        ]);

        register_rest_route($this->namespace, '/assets', [
            'methods' => 'GET',
            'callback' => [$this, 'get_assets'],
            'permission_callback' => fn() => current_user_can_manage_assets(),
            'args' => $this->get_assets_args(),
        ]);

        register_rest_route($this->namespace, '/toggle-asset', [
            'methods' => 'POST',
            'callback' => [$this, 'toggle_asset'],
            'permission_callback' => fn() => current_user_can_manage_assets(),
            'args' => $this->get_toggle_args(),
        ]);
    }

    /**
     * Obtém lista de páginas publicadas.
     *
     * @return WP_REST_RESPONSE|WP_Error
     */
    public function get_pages(): WP_REST_Response|WP_Error
    {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'page' AND post_status = 'publish' 
             ORDER BY post_title ASC",
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        if ($results === null) {
            return new WP_Error(
                'db_error',
                __('Erro ao buscar páginas.', 'perfmanager'),
                ['status' => 500]
            );
        }

        $pages = array_map(fn($post) => [
            'id' => (int) $post['ID'],
            'title' => $post['post_title'] ?: __('Sem título', 'perfmanager'),
        ], $results);

        return new WP_REST_Response($pages, 200);
    }

    /**
     * Obtém assets registrados para uma página.
     *
     * @param WP_REST_Request $request Request atual.
     * @return WP_REST_RESPONSE|WP_Error
     */
    public function get_assets(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $page_id = (int) $request->get_param('page_id');
        $page_num = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) $request->get_param('per_page')));
        $search = sanitize_text_field($request->get_param('search') ?? '');

        $post = get_post($page_id);
        if (!$post || $post->post_type !== 'page') {
            return new WP_Error(
                'invalid_page',
                __('Página inválida.', 'perfmanager'),
                ['status' => 404]
            );
        }

        $this->setup_post_context($post);

        wp_enqueue_scripts();

        global $wp_scripts, $wp_styles;

        $scripts = $this->collect_assets($wp_scripts->queue, $wp_scripts->registered, 'script');
        $styles = $this->collect_assets($wp_styles->queue, $wp_styles->registered, 'style');

        $disabled_scripts = $this->get_disabled_assets($page_id, 'script');
        $disabled_styles = $this->get_disabled_assets($page_id, 'style');

        $scripts = $this->add_enabled_status($scripts, $disabled_scripts);
        $styles = $this->add_enabled_status($styles, $disabled_styles);

        $all_assets = array_merge($scripts, $styles);

        if ($search !== '') {
            $all_assets = $this->filter_assets($all_assets, $search);
        }

        $total_items = count($all_assets);
        $total_pages = (int) ceil($total_items / $per_page);

        $offset = ($page_num - 1) * $per_page;
        $paginated_assets = array_slice($all_assets, $offset, $per_page);

        wp_reset_postdata();

        return new WP_REST_Response([
            'data' => $paginated_assets,
            'meta' => [
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'current_page' => $page_num,
                'per_page' => $per_page,
            ],
        ], 200);
    }

    /**
     * Alterna status de um asset.
     *
     * @param WP_REST_Request $request Request atual.
     * @return WP_REST_RESPONSE|WP_Error
     */
    public function toggle_asset(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error(
                'invalid_nonce',
                __('Falha na verificação de segurança.', 'perfmanager'),
                ['status' => 403]
            );
        }

        $page_id = (int) $request->get_param('page_id');
        $asset_type = validate_asset_type((string) $request->get_param('asset_type'));
        $handle = sanitize_asset_handle((string) $request->get_param('handle'));
        $enabled = (bool) $request->get_param('enabled');

        if (!$page_id || !$asset_type || $handle === '') {
            return new WP_Error(
                'invalid_data',
                __('Dados inválidos.', 'perfmanager'),
                ['status' => 400]
            );
        }

        if (!current_user_can('edit_pages')) {
            return new WP_Error(
                'forbidden',
                __('Permissão negada.', 'perfmanager'),
                ['status' => 403]
            );
        }

        $meta_key = get_asset_meta_key($asset_type);
        $disabled_assets = $this->get_disabled_assets($page_id, $asset_type);

        if ($enabled) {
            $disabled_assets = array_values(array_diff($disabled_assets, [$handle]));
        } else {
            if (!in_array($handle, $disabled_assets, true)) {
                $disabled_assets[] = $handle;
            }
        }

        update_post_meta($page_id, $meta_key, $disabled_assets);

        return new WP_REST_Response([
            'success' => true,
            'updated_assets' => $disabled_assets,
        ], 200);
    }

    /**
     * Argumentos para endpoint de assets.
     *
     * @return array
     */
    private function get_assets_args(): array
    {
        return [
            'page_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($value) => is_int($value) && $value > 0,
            ],
            'page' => [
                'default' => 1,
                'type' => 'integer',
                'minimum' => 1,
            ],
            'per_page' => [
                'default' => 10,
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 100,
            ],
            'search' => [
                'default' => '',
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }

    /**
     * Argumentos para endpoint de toggle.
     *
     * @return array
     */
    private function get_toggle_args(): array
    {
        return [
            'page_id' => [
                'required' => true,
                'type' => 'integer',
                'validate_callback' => fn($value) => is_int($value) && $value > 0,
            ],
            'asset_type' => [
                'required' => true,
                'type' => 'string',
                'enum' => ['script', 'style'],
            ],
            'handle' => [
                'required' => true,
                'type' => 'string',
                'minLength' => 1,
                'sanitize_callback' => 'sanitize_asset_handle',
            ],
            'enabled' => [
                'required' => true,
                'type' => 'boolean',
            ],
        ];
    }

    /**
     * Configura contexto do post para simular carregamento.
     *
     * @param \WP_Post $post Post atual.
     */
    private function setup_post_context(\WP_Post $post): void
    {
        $GLOBALS['post'] = $post;
        setup_postdata($post);
    }

    /**
     * Coleta assets de um registry.
     *
     * @param array $queue Fila de assets.
     * @param array $registered Registry de assets.
     * @param string $type Tipo do asset.
     * @return array
     */
    private function collect_assets(array $queue, array $registered, string $type): array
    {
        return array_map(fn($handle) => [
            'handle' => $handle,
            'src' => $registered[$handle]->src ?? '',
            'type' => $type,
        ], $queue);
    }

    /**
     * Obtém assets desabilitados.
     *
     * @param int $page_id ID da página.
     * @param string $type Tipo do asset.
     * @return array
     */
    private function get_disabled_assets(int $page_id, string $type): array
    {
        $meta_key = get_asset_meta_key($type);
        $assets = get_post_meta($page_id, $meta_key, true);
        return is_array($assets) ? $assets : [];
    }

    /**
     * Adiciona status de enabled aos assets.
     *
     * @param array $assets Lista de assets.
     * @param array $disabled Assets desabilitados.
     * @return array
     */
    private function add_enabled_status(array $assets, array $disabled): array
    {
        return array_map(fn($asset) => [
            ...$asset,
            'enabled' => !in_array($asset['handle'], $disabled, true),
        ], $assets);
    }

    /**
     * Filtra assets por termo de busca.
     *
     * @param array $assets Lista de assets.
     * @param string $search Termo de busca.
     * @return array
     */
    private function filter_assets(array $assets, string $search): array
    {
        $search_lower = strtolower($search);
        
        return array_filter($assets, fn($asset) => (
            str_contains(strtolower($asset['handle']), $search_lower) ||
            str_contains(strtolower($asset['type']), $search_lower) ||
            str_contains(strtolower($asset['src']), $search_lower)
        ));
    }
}
