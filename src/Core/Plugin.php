<?php
/**
 * Classe principal do plugin PerfManager.
 *
 * @package PerfManager\Core
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager\Core;

use PerfManager\Admin\REST\AssetsController;
use PerfManager\Frontend\AssetManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe singleton que gerencia o ciclo de vida do plugin.
 */
final class Plugin
{
    private static ?self $instance = null;
    
    private AssetsController $assets_controller;
    private AssetManager $asset_manager;

    /**
     * Construtor privado para padrão singleton.
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->assets_controller = new AssetsController();
        $this->asset_manager = new AssetManager();
    }

    /**
     * Previne clonagem da instância.
     */
    private function __clone() {}

    /**
     * Previne desserialização da instância.
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Obtém a instância singleton do plugin.
     *
     * @return self Instância do plugin.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Inicializa todos os hooks do plugin.
     */
    private function init_hooks(): void
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('rest_api_init', [$this->assets_controller, 'register_routes']);
        add_action('wp_enqueue_scripts', [$this->asset_manager, 'dequeue_disabled_assets'], 100);
    }

    /**
     * Carrega o text domain para internacionalização.
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'perfmanager',
            false,
            dirname(plugin_basename(PERFMANAGER_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Registra o menu administrativo.
     */
    public function register_admin_menu(): void
    {
        add_menu_page(
            __('Gerenciador de Scripts e Estilos', 'perfmanager'),
            __('Assets Manager', 'perfmanager'),
            'edit_pages',
            'perfmanager',
            [$this, 'render_admin_page'],
            'dashicons-performance',
            100
        );
    }

    /**
     * Renderiza a página administrativa.
     */
    public function render_admin_page(): void
    {
        include PERFMANAGER_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    /**
     * Enfileira assets administrativos.
     *
     * @param string $hook Hook da página atual.
     */
    public function enqueue_admin_assets(string $hook): void
    {
        if ($hook !== 'toplevel_page_perfmanager') {
            return;
        }

        $asset_version = filemtime(PERFMANAGER_PLUGIN_DIR . 'assets/js/admin.js');
        
        wp_enqueue_script(
            'perfmanager-admin',
            plugins_url('assets/js/admin.js', PERFMANAGER_PLUGIN_FILE),
            ['wp-element', 'wp-api-fetch', 'wp-i18n'],
            $asset_version,
            true
        );

        wp_localize_script('perfmanager-admin', 'perfmanagerSettings', [
            'apiUrl' => rest_url('perfmanager/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'i18n' => [
                'loading' => __('Carregando...', 'perfmanager'),
                'error' => __('Erro ao carregar dados.', 'perfmanager'),
                'noAssets' => __('Nenhum script ou estilo detectado nesta página.', 'perfmanager'),
                'saveSuccess' => __('Asset atualizado com sucesso.', 'perfmanager'),
                'saveError' => __('Erro ao atualizar asset.', 'perfmanager'),
            ],
        ]);

        wp_enqueue_style(
            'perfmanager-admin',
            plugins_url('assets/css/admin.css', PERFMANAGER_PLUGIN_FILE),
            [],
            filemtime(PERFMANAGER_PLUGIN_DIR . 'assets/css/admin.css')
        );
    }

    /**
     * Obtém o controller de assets.
     *
     * @return AssetsController
     */
    public function getAssetsController(): AssetsController
    {
        return $this->assets_controller;
    }

    /**
     * Obtém o gerenciador de assets frontend.
     *
     * @return AssetManager
     */
    public function getAssetManager(): AssetManager
    {
        return $this->asset_manager;
    }
}
