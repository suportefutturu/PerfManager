<?php
/**
 * Arquivo de uninstall do PerfManager.
 *
 * @package PerfManager
 * @since 2.0.0
 */

declare(strict_types=1);

// Se uninstall não for chamado pelo WordPress, sair.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Incluir classe de uninstall para reutilização de lógica.
require_once __DIR__ . '/src/Core/Uninstall.php';

use PerfManager\Core\Uninstall;

Uninstall::uninstall();
