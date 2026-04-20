<?php
/**
 * Template da página administrativa do PerfManager.
 *
 * @package PerfManager
 * @since 2.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Gerenciador de Scripts e Estilos', 'perfmanager'); ?>
    </h1>
    
    <p class="description" style="max-width: 800px; margin-top: 10px;">
        <?php esc_html_e('Gerencie os scripts e estilos carregados em cada página do seu site WordPress. Desative assets desnecessários para melhorar a performance.', 'perfmanager'); ?>
    </p>

    <hr class="wp-header-end">

    <div id="perfmanager-app" style="margin-top: 20px;">
        <div class="notice notice-info is-dismissible">
            <p>
                <?php esc_html_e('Carregando interface...', 'perfmanager'); ?>
            </p>
        </div>
    </div>
</div>
