<?php
/**
 * Cliente de conexión y procesamiento de Google Sheets.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 *
 * @package Prolijo_Sheets_Viewer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Prolijo_CAP_Sheets_Client {

    /**
     * Construye la URL adecuada para descargar el CSV según el formato del enlace de Google Sheets.
     *
     * @param string $base_url URL base ingresada por el usuario.
     * @param string $gid      Identificador de la hoja (gid).
     * @return string URL completa para exportar CSV.
     */
    public static function build_csv_url($base_url, $gid = '0') {
        $base_url = trim($base_url);
        if (empty($base_url)) {
            return '';
        }

        $gid = trim((string) $gid);
        if ($gid === '') {
            $gid = '0';
        }

        // Caso 1: Enlace publicado en la web (/pub)
        if (strpos($base_url, '/pub') !== false) {
            $clean_base = strtok($base_url, '?');
            return add_query_arg(
                array(
                    'gid'    => $gid,
                    'single' => 'true',
                    'output' => 'csv',
                ),
                $clean_base
            );
        }

        // Caso 2: Enlace estándar de edición o visualización de Google Sheets (/spreadsheets/d/{ID}/...)
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $base_url, $matches)) {
            $sheet_id = $matches[1];
            return 'https://docs.google.com/spreadsheets/d/' . $sheet_id . '/export?format=csv&gid=' . urlencode($gid);
        }

        // Caso 3: Fallback general manteniendo la URL base
        $clean_base = strtok($base_url, '?');
        return add_query_arg(
            array(
                'gid'    => $gid,
                'single' => 'true',
                'output' => 'csv',
            ),
            $clean_base
        );
    }

    /**
     * Obtiene las filas de datos para una pestaña u hoja específica.
     * Utiliza transients para cachear los datos según el tiempo configurado.
     *
     * @param string $tab_id        Identificador de la pestaña/hoja (ej. 'faq', 'productos', 'invid').
     * @param bool   $force_refresh Si es true, ignora el caché y descarga nuevamente.
     * @return array|WP_Error Array de filas o WP_Error en caso de fallo.
     */
    public static function get_rows($tab_id, $force_refresh = false) {
        $settings = Prolijo_CAP_Settings::get_settings();
        $tabs     = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : (isset($settings['providers']) ? $settings['providers'] : array());

        // Buscar la pestaña solicitada
        $target_tab = null;
        foreach ($tabs as $t) {
            if (isset($t['id']) && $t['id'] === $tab_id) {
                $target_tab = $t;
                break;
            }
        }

        if (!$target_tab) {
            return new WP_Error(
                'prolijo_tab_invalido',
                sprintf(__('La pestaña u hoja "%s" no existe o fue deshabilitada en la configuración.', 'prolijo-sheets-viewer'), esc_html($tab_id))
            );
        }

        // Determinar URL base y GID
        $gid      = isset($target_tab['gid']) ? (string) $target_tab['gid'] : '0';
        $base_url = !empty($target_tab['url']) ? $target_tab['url'] : (isset($settings['sheets_url']) ? $settings['sheets_url'] : '');

        if (empty($base_url)) {
            return new WP_Error(
                'prolijo_sin_url',
                __('No se ha configurado la URL de Google Sheets en el panel de administración.', 'prolijo-sheets-viewer')
            );
        }

        $csv_url    = self::build_csv_url($base_url, $gid);
        $cache_time = isset($settings['cache_time']) ? (int) $settings['cache_time'] : 5;
        $cache_key  = 'prolijo_sheets_' . md5($tab_id . '_' . $gid . '_' . $base_url);

        // Si no se fuerza refresco y el caché está activo, consultar transient
        if (!$force_refresh && $cache_time > 0) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && !empty($cached)) {
                return $cached;
            }
        }

        // Petición remota con timeout y headers adecuados
        $response = wp_remote_get($csv_url, array(
            'timeout'     => 25,
            'redirection' => 5,
            'headers'     => array(
                'Accept' => 'text/csv, text/plain, */*',
            ),
            'user-agent'  => 'ProlijoSheetsViewer/2.1; ' . home_url('/'),
        ));

        if (is_wp_error($response)) {
            return new WP_Error(
                'prolijo_conexion_error',
                sprintf(__('Error de conexión con Google Sheets: %s', 'prolijo-sheets-viewer'), $response->get_error_message())
            );
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            return new WP_Error(
                'prolijo_http_error',
                sprintf(__('Google Sheets respondió con código de error HTTP %d.', 'prolijo-sheets-viewer'), $code)
            );
        }

        // Quitar UTF-8 BOM si existe
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $body_lower = strtolower(ltrim($body));

        // Detectar si Google devolvió HTML (error de permisos o URL errónea)
        if ($body === '' || strpos($body_lower, '<!doctype html') === 0 || strpos($body_lower, '<html') === 0) {
            return new WP_Error(
                'prolijo_csv_invalido',
                __('Google Sheets no devolvió un CSV válido. Verificá que la hoja esté publicada en la web como CSV o que el enlace tenga permisos de visualización públicos.', 'prolijo-sheets-viewer')
            );
        }

        // Parsear CSV usando un stream temporal
        $stream = fopen('php://temp', 'r+');
        if (!$stream) {
            return new WP_Error('prolijo_memoria', __('No se pudo procesar el archivo CSV en el servidor.', 'prolijo-sheets-viewer'));
        }

        fwrite($stream, $body);
        rewind($stream);

        $rows = array();
        while (($row = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if (count($row) === 1 && ($row[0] === null || trim((string) $row[0]) === '')) {
                continue;
            }
            $clean_row = array();
            foreach ($row as $cell) {
                $clean_row[] = $cell === null ? '' : (string) $cell;
            }
            $rows[] = $clean_row;
        }
        fclose($stream);

        if (count($rows) < 2) {
            return new WP_Error(
                'prolijo_vacio',
                __('La hoja de Google Sheets está vacía o no contiene filas de datos suficientes.', 'prolijo-sheets-viewer')
            );
        }

        // Guardar en caché si el tiempo es mayor a cero
        if ($cache_time > 0) {
            set_transient($cache_key, $rows, $cache_time * MINUTE_IN_SECONDS);
        }

        return $rows;
    }

    /**
     * Vacía el caché de transients de todas las hojas configuradas.
     *
     * @return int Cantidad de elementos de caché limpiados.
     */
    public static function clear_all_cache() {
        global $wpdb;
        $deleted = 0;

        // Borrar transients que coincidan con prolijo_sheets_ o prolijo_cap_
        $prefixes = array('_transient_prolijo_sheets_%', '_transient_prolijo_cap_%');
        foreach ($prefixes as $like) {
            $results = $wpdb->get_col($wpdb->prepare(
                "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
                $like
            ));

            if (!empty($results)) {
                foreach ($results as $option) {
                    $transient = str_replace('_transient_', '', $option);
                    delete_transient($transient);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Prueba la conexión en vivo con Google Sheets para la pestaña especificada (o la primera activa).
     *
     * @param string|null $tab_id
     * @return array Resultado detallado de la prueba.
     */
    public static function test_connection($tab_id = null) {
        $settings = Prolijo_CAP_Settings::get_settings();
        $tabs     = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : (isset($settings['providers']) ? $settings['providers'] : array());

        if (empty($tabs)) {
            return array(
                'success' => false,
                'message' => __('No hay pestañas configuradas.', 'prolijo-sheets-viewer'),
            );
        }

        $target = null;
        if ($tab_id) {
            foreach ($tabs as $t) {
                if (isset($t['id']) && $t['id'] === $tab_id) {
                    $target = $t;
                    break;
                }
            }
        }

        if (!$target) {
            // Tomar la primera pestaña activa
            foreach ($tabs as $t) {
                if (!empty($t['active'])) {
                    $target = $t;
                    break;
                }
            }
            if (!$target) {
                $target = $tabs[0];
            }
        }

        $start_time = microtime(true);
        $rows = self::get_rows($target['id'], true); // Forzar consulta fresca
        $elapsed = round(microtime(true) - $start_time, 2);

        if (is_wp_error($rows)) {
            return array(
                'success'  => false,
                'tab_id'   => $target['id'],
                'tab_name' => isset($target['nombre']) ? $target['nombre'] : $target['id'],
                'message'  => $rows->get_error_message(),
                'elapsed'  => $elapsed,
            );
        }

        $headers = isset($rows[0]) ? $rows[0] : array();
        $total_rows = count($rows) - 1; // Descontando cabecera
        $preview = array_slice($rows, 1, 3);

        return array(
            'success'    => true,
            'tab_id'     => $target['id'],
            'tab_name'   => isset($target['nombre']) ? $target['nombre'] : $target['id'],
            'message'    => sprintf(__('Conexión exitosa con Google Sheets. Se obtuvieron %d filas de datos en %s segundos.', 'prolijo-sheets-viewer'), $total_rows, $elapsed),
            'total_rows' => $total_rows,
            'headers'    => $headers,
            'preview'    => $preview,
            'elapsed'    => $elapsed,
        );
    }
}
