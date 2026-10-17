<?php
/**
 * Plugin Name: Prolijo - Visor de Google Sheets
 * Plugin URI: https://prolijo.ar/
 * Description: Muestra cualquier planilla o pestaña de Google Sheets en tu sitio web con buscador instantáneo, filtro por categorías, paginación rápida y diseño responsivo. Ideal para catálogos de productos, listas de precios, preguntas frecuentes (FAQ), directorios, inventarios y más. Desarrollado por Prolijo.ar.
 * Version: 2.1.1
 * Author: Prolijo.ar
 * Author URI: https://prolijo.ar/
 * Text Domain: prolijo-sheets-viewer
 * Domain Path: /languages
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definición de constantes del plugin
define('PROLIJO_CAP_VERSION', '2.1.1');
define('PROLIJO_CAP_FILE', __FILE__);
define('PROLIJO_CAP_DIR', plugin_dir_path(__FILE__));
define('PROLIJO_CAP_URL', plugin_dir_url(__FILE__));
define('PROLIJO_CAP_BASENAME', plugin_basename(__FILE__));

// Carga de clases del plugin
require_once PROLIJO_CAP_DIR . 'includes/class-sheets-client.php';
require_once PROLIJO_CAP_DIR . 'includes/class-ajax-handler.php';
require_once PROLIJO_CAP_DIR . 'includes/class-settings.php';
require_once PROLIJO_CAP_DIR . 'includes/class-shortcode.php';

/**
 * Inicialización de todos los módulos del plugin.
 */
function prolijo_cap_init() {
    Prolijo_CAP_Settings::init();
    Prolijo_CAP_Ajax_Handler::init();
    Prolijo_CAP_Shortcode::init();
}
add_action('plugins_loaded', 'prolijo_cap_init');

/**
 * Gancho de activación: inicializa las opciones predeterminadas si aún no existen.
 */
function prolijo_cap_activate() {
    $current = get_option(Prolijo_CAP_Settings::OPTION_KEY);
    if ($current === false || empty($current)) {
        update_option(Prolijo_CAP_Settings::OPTION_KEY, Prolijo_CAP_Settings::get_default_settings());
    }
}
register_activation_hook(__FILE__, 'prolijo_cap_activate');

/**
 * Gancho de desactivación: limpia la memoria de transients.
 */
function prolijo_cap_deactivate() {
    Prolijo_CAP_Sheets_Client::clear_all_cache();
}
register_deactivation_hook(__FILE__, 'prolijo_cap_deactivate');
