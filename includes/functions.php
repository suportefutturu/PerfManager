<?php
/**
 * Funções utilitárias globais do plugin PerfManager.
 *
 * @package PerfManager
 * @since 2.0.0
 */

declare(strict_types=1);

namespace PerfManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtém o text domain do plugin.
 *
 * @return string Text domain do plugin.
 */
function text_domain(): string
{
    return PERFMANAGER_TEXT_DOMAIN;
}

/**
 * Wrapper para __() com o text domain do plugin.
 *
 * @param string $text Texto a ser traduzido.
 * @return string Texto traduzido.
 */
function __(string $text): string
{
    return \__($text, text_domain());
}

/**
 * Wrapper para _e() com o text domain do plugin.
 *
 * @param string $text Texto a ser traduzido e ecoado.
 */
function _e(string $text): void
{
    \_e($text, text_domain());
}

/**
 * Wrapper para sprintf(__()) com o text domain do plugin.
 *
 * @param string $text Texto a ser traduzido.
 * @param mixed  ...$args Argumentos para sprintf.
 * @return string Texto traduzido formatado.
 */
function _x(string $text, ...$args): string
{
    return \sprintf(\__($text, text_domain()), ...$args);
}

/**
 * Registra um hook com prefixo do plugin.
 *
 * @param string   $hook_name Nome do hook.
 * @param callable $callback Callback a ser executado.
 * @param int      $priority Prioridade do hook.
 * @param int      $accepted_args Número de argumentos aceitos.
 * @return bool Retorna true se o hook foi registrado.
 */
function add_hook(
    string $hook_name,
    callable $callback,
    int $priority = 10,
    int $accepted_args = 1
): bool {
    return \add_action($hook_name, $callback, $priority, $accepted_args);
}

/**
 * Verifica se o usuário tem permissão para gerenciar assets.
 *
 * @return bool True se o usuário tiver permissão.
 */
function current_user_can_manage_assets(): bool
{
    return \current_user_can('edit_pages');
}

/**
 * Sanitiza um handle de script/estilo.
 *
 * @param string $handle Handle a ser sanitizado.
 * @return string Handle sanitizado.
 */
function sanitize_asset_handle(string $handle): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $handle) ?? '';
}

/**
 * Valida um tipo de asset.
 *
 * @param string $type Tipo a ser validado.
 * @return string|null Tipo válido ou null se inválido.
 */
function validate_asset_type(string $type): ?string
{
    $valid_types = ['script', 'style'];
    return in_array($type, $valid_types, true) ? $type : null;
}

/**
 * Obtém a chave de meta para um tipo de asset.
 *
 * @param string $type Tipo do asset ('script' ou 'style').
 * @return string Chave de meta.
 */
function get_asset_meta_key(string $type): string
{
    return $type === 'script' ? '_disabled_scripts' : '_disabled_styles';
}

/**
 * Escapa dados para output HTML seguro.
 *
 * @param mixed  $data Dados a serem escapados.
 * @param string $context Contexto de escaping (html, url, js, attr, textarea).
 * @return string Dados escapados.
 */
function escape_output(mixed $data, string $context = 'html'): string
{
    return match ($context) {
        'html'       => esc_html((string) $data),
        'url'        => esc_url((string) $data),
        'js'         => esc_js((string) $data),
        'attr'       => esc_attr((string) $data),
        'textarea'   => esc_textarea((string) $data),
        default      => esc_html((string) $data),
    };
}

/**
 * Gera um nonce para uma ação específica.
 *
 * @param string $action Ação para o nonce.
 * @return string Nonce gerado.
 */
function create_nonce(string $action): string
{
    return \wp_create_nonce($action);
}

/**
 * Verifica um nonce.
 *
 * @param string $nonce Nonce a ser verificado.
 * @param string $action Ação associada ao nonce.
 * @return bool|int True se válido, false se inválido.
 */
function verify_nonce(string $nonce, string $action): bool|int
{
    return \wp_verify_nonce($nonce, $action);
}

/**
 * Prepara uma query SQL com segurança.
 *
 * @param \wpdb $wpdb Instância do wpdb.
 * @param string $query Query SQL com placeholders.
 * @param array  $args Argumentos para a query.
 * @return string|null Query preparada ou null em caso de erro.
 */
function prepare_query(\wpdb $wpdb, string $query, array $args): ?string
{
    try {
        return $wpdb->prepare($query, ...$args);
    } catch (\Throwable $e) {
        error_log('[PerfManager] Query preparation error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Log de debug do plugin (apenas em WP_DEBUG).
 *
 * @param string $message Mensagem de log.
 * @param mixed  $context Contexto adicional.
 */
function debug_log(string $message, mixed $context = null): void
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $prefix = '[PerfManager] ';
        $log_message = $prefix . $message;
        
        if ($context !== null) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }
        
        error_log($log_message);
    }
}

/**
 * Obtém URL base do plugin.
 *
 * @param string $path Caminho relativo para adicionar à URL.
 * @return string URL completa.
 */
function plugin_url(string $path = ''): string
{
    return PERFMANAGER_PLUGIN_URL . ltrim($path, '/');
}

/**
 * Obtém caminho absoluto do plugin.
 *
 * @param string $path Caminho relativo para adicionar.
 * @return string Caminho completo.
 */
function plugin_path(string $path = ''): string
{
    return PERFMANAGER_PLUGIN_DIR . ltrim($path, '/');
}

/**
 * Renderiza um template do plugin.
 *
 * @param string $template_name Nome do template.
 * @param array  $args Argumentos para o template.
 */
function render_template(string $template_name, array $args = []): void
{
    $template_path = plugin_path('templates/' . $template_name . '.php');
    
    if (!file_exists($template_path)) {
        debug_log("Template not found: {$template_name}");
        return;
    }
    
    extract($args, EXTR_SKIP);
    include $template_path;
}
