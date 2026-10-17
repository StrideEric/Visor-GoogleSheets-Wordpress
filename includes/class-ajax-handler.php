<?php
/**
 * Manejador de endpoints AJAX para el visor de Google Sheets y panel de administración.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 *
 * @package Prolijo_Sheets_Viewer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Prolijo_CAP_Ajax_Handler {

    /**
     * Inicializa los hooks de AJAX.
     */
    public static function init() {
        // Endpoint público universal para obtener los datos de la hoja
        add_action('wp_ajax_prolijo_sheets_get_data', array(__CLASS__, 'ajax_get_data'));
        add_action('wp_ajax_nopriv_prolijo_sheets_get_data', array(__CLASS__, 'ajax_get_data'));

        // Alias para compatibilidad con implementaciones previas
        add_action('wp_ajax_prolijo_cap_get_catalog', array(__CLASS__, 'ajax_get_data'));
        add_action('wp_ajax_nopriv_prolijo_cap_get_catalog', array(__CLASS__, 'ajax_get_data'));

        // Endpoints administrativos protegidos con permisos y nonce
        add_action('wp_ajax_prolijo_cap_clear_cache', array(__CLASS__, 'ajax_clear_cache'));
        add_action('wp_ajax_prolijo_cap_test_connection', array(__CLASS__, 'ajax_test_connection'));
    }

    /**
     * Devuelve las filas de datos en formato JSON para el frontend.
     */
    public static function ajax_get_data() {
        // Obtener identificador de pestaña u hoja de forma flexible
        $tab = '';
        if (isset($_GET['hoja'])) {
            $tab = sanitize_key(wp_unslash($_GET['hoja']));
        } elseif (isset($_GET['pestana'])) {
            $tab = sanitize_key(wp_unslash($_GET['pestana']));
        } elseif (isset($_GET['tab'])) {
            $tab = sanitize_key(wp_unslash($_GET['tab']));
        } elseif (isset($_GET['proveedor'])) {
            $tab = sanitize_key(wp_unslash($_GET['proveedor']));
        } elseif (isset($_GET['provider'])) {
            $tab = sanitize_key(wp_unslash($_GET['provider']));
        }

        if (empty($tab)) {
            $settings = Prolijo_CAP_Settings::get_settings();
            $tab = isset($settings['default_tab']) ? $settings['default_tab'] : (isset($settings['default_provider']) ? $settings['default_provider'] : '');
            if (empty($tab) && !empty($settings['tabs'][0]['id'])) {
                $tab = $settings['tabs'][0]['id'];
            }
        }

        $rows = Prolijo_CAP_Sheets_Client::get_rows($tab);

        if (is_wp_error($rows)) {
            wp_send_json_error(array(
                'message' => $rows->get_error_message(),
                'code'    => $rows->get_error_code(),
            ), 502);
        }

        wp_send_json_success(array(
            'tab'   => $tab,
            'rows'  => $rows,
            'count' => max(0, count($rows) - 1),
        ));
    }

    /**
     * Endpoint administrativo: Vacía el caché de Google Sheets.
     */
    public static function ajax_clear_cache() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tenés permisos para realizar esta acción.', 'prolijo-sheets-viewer')), 403);
        }

        check_ajax_referer('prolijo_cap_admin_nonce', 'nonce');

        $deleted = Prolijo_CAP_Sheets_Client::clear_all_cache();

        wp_send_json_success(array(
            'message' => sprintf(__('¡Caché vaciado con éxito! Se eliminaron %d registros temporales.', 'prolijo-sheets-viewer'), $deleted),
            'deleted' => $deleted,
        ));
    }

    /**
     * Endpoint administrativo: Prueba la conexión en vivo con Google Sheets.
     */
    public static function ajax_test_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tenés permisos para realizar esta acción.', 'prolijo-sheets-viewer')), 403);
        }

        check_ajax_referer('prolijo_cap_admin_nonce', 'nonce');

        $tab_id = null;
        if (isset($_POST['tab_id'])) {
            $tab_id = sanitize_key(wp_unslash($_POST['tab_id']));
        } elseif (isset($_POST['provider_id'])) {
            $tab_id = sanitize_key(wp_unslash($_POST['provider_id']));
        }

        $result = Prolijo_CAP_Sheets_Client::test_connection($tab_id);

        if (!$result['success']) {
            wp_send_json_error($result, 400);
        }

        wp_send_json_success($result);
    }
}
