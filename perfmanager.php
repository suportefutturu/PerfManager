<?php
/**
 * Plugin Name: PerfManager
 * Plugin URI: https://github.com/example/perfmanager
 * Description: Gerencie scripts e estilos do WordPress por página para otimizar a performance do site.
 * Version: 2.0.0
 * Requires at least: 5.8
 * Requires PHP: 8.2
 * Author: Abdelhalim Khouas
 * Author URI: https://example.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: perfmanager
 * Domain Path: /languages
 */

declare(strict_types=1);

namespace PerfManager;

if (!defined('ABSPATH')) {
    exit;
}

define('PERFMANAGER_VERSION', '2.0.0');
define('PERFMANAGER_PLUGIN_FILE', __FILE__);
define('PERFMANAGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PERFMANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERFMANAGER_TEXT_DOMAIN', 'perfmanager');

spl_autoload_register(function (string $class): void {
    $prefix = 'PerfManager\\';
    $base_dir = PERFMANAGER_PLUGIN_DIR . 'src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once PERFMANAGER_PLUGIN_DIR . 'includes/functions.php';

register_activation_hook(__FILE__, [Core\Activation::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Deactivation::class, 'deactivate']);
register_uninstall_hook(__FILE__, [Core\Uninstall::class, 'uninstall']);

add_action('plugins_loaded', [Core\Plugin::class, 'getInstance']);
