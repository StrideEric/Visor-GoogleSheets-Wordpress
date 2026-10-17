<?php
/**
 * Renderizador de shortcodes y carga de assets del visor de Google Sheets.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 *
 * @package Prolijo_Sheets_Viewer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Prolijo_CAP_Shortcode {

    /**
     * Inicializa los shortcodes.
     */
    public static function init() {
        // Shortcode principal universal
        add_shortcode('prolijo_sheets', array(__CLASS__, 'render'));
        add_shortcode('visor_google_sheets', array(__CLASS__, 'render'));

        // Alias para compatibilidad
        add_shortcode('catalogo_productos_pedido', array(__CLASS__, 'render'));
        add_shortcode('prolijo_catalogo', array(__CLASS__, 'render'));
        add_shortcode('productos_a_pedido', array(__CLASS__, 'render'));
    }

    /**
     * Renderiza la tabla de datos de Google Sheets en el frontend.
     *
     * @param array $atts Atributos del shortcode.
     * @return string Marcado HTML de la tabla interactiva.
     */
    public static function render($atts = array()) {
        $settings = Prolijo_CAP_Settings::get_settings();

        $atts = shortcode_atts(array(
            'hoja'       => '',
            'pestana'    => '',
            'tab'        => '',
            'proveedor'  => '',
            'provider'   => '',
            'filas'      => '',
            'page_size'  => '',
            'buscador'   => '',
            'search'     => '',
            'categorias' => '',
            'categories' => '',
        ), $atts, 'prolijo_sheets');

        // Validar si la URL de Google Sheets ha sido configurada
        $sheets_url = isset($settings['sheets_url']) ? trim($settings['sheets_url']) : '';
        if (empty($sheets_url)) {
            if (current_user_can('manage_options')) {
                return '<div class="prolijo-notice prolijo-notice-warning" style="padding:16px 20px; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; color:#92400e; font-size:14px; margin:20px 0;">' .
                    '<strong>' . esc_html__('Configuración requerida:', 'prolijo-sheets-viewer') . '</strong> ' .
                    esc_html__('Aún no has configurado la URL de publicación de tu Google Sheets. Podés ingresarla desde el panel de WordPress en ', 'prolijo-sheets-viewer') .
                    '<a href="' . esc_url(admin_url('admin.php?page=prolijo-sheets-viewer')) . '" style="color:#2563eb; font-weight:700;">' . esc_html__('Visor Google Sheets &rarr; 2. Google Sheets y Pestañas', 'prolijo-sheets-viewer') . '</a>.' .
                    '</div>';
            } else {
                return '<div class="prolijo-notice prolijo-notice-info" style="padding:16px 20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; color:#64748b; font-size:14px; text-align:center; margin:20px 0;">' .
                    esc_html__('La información se encuentra en preparación.', 'prolijo-sheets-viewer') .
                    '</div>';
            }
        }

        // Pestañas / Hojas activas
        $all_tabs = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : (isset($settings['providers']) ? $settings['providers'] : array());
        $active_tabs = array();
        foreach ($all_tabs as $t) {
            if (!empty($t['active'])) {
                $active_tabs[] = $t;
            }
        }

        if (empty($active_tabs)) {
            return '<div class="prolijo-notice prolijo-notice-warning" style="padding:14px; background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; color:#991b1b;"><p>' .
                esc_html__('No hay pestañas activas configuradas en el visor de Google Sheets.', 'prolijo-sheets-viewer') .
                '</p></div>';
        }

        // Pestaña inicial solicitada
        $chosen_tab = '';
        foreach (array('hoja', 'pestana', 'tab', 'proveedor', 'provider') as $attr_key) {
            if (!empty($atts[$attr_key])) {
                $chosen_tab = sanitize_key($atts[$attr_key]);
                break;
            }
        }

        $default_tab = isset($settings['default_tab']) ? $settings['default_tab'] : (isset($settings['default_provider']) ? $settings['default_provider'] : $active_tabs[0]['id']);

        $initial_tab = $default_tab;
        if (!empty($chosen_tab)) {
            foreach ($active_tabs as $t) {
                if ($t['id'] === $chosen_tab) {
                    $initial_tab = $chosen_tab;
                    break;
                }
            }
        }

        // Cantidad de filas por página
        $page_size = isset($settings['page_size']) ? (int) $settings['page_size'] : 50;
        if (!empty($atts['filas'])) {
            $page_size = max(5, min(500, (int) $atts['filas']));
        } elseif (!empty($atts['page_size'])) {
            $page_size = max(5, min(500, (int) $atts['page_size']));
        }

        // Control de Buscador Opcional (por ajuste o por parámetro de shortcode)
        $enable_search = !empty($settings['enable_search']);
        $search_override = !empty($atts['buscador']) ? $atts['buscador'] : (!empty($atts['search']) ? $atts['search'] : '');
        if ($search_override === 'no' || $search_override === '0' || $search_override === 'false') {
            $enable_search = false;
        } elseif ($search_override === 'si' || $search_override === 'yes' || $search_override === '1' || $search_override === 'true') {
            $enable_search = true;
        }

        // Control de Filtro de Categorías Opcional (por ajuste o por parámetro de shortcode)
        $enable_categories = !empty($settings['enable_categories']);
        $cat_override = !empty($atts['categorias']) ? $atts['categorias'] : (!empty($atts['categories']) ? $atts['categories'] : '');
        if ($cat_override === 'no' || $cat_override === '0' || $cat_override === 'false') {
            $enable_categories = false;
        } elseif ($cat_override === 'si' || $cat_override === 'yes' || $cat_override === '1' || $cat_override === 'true') {
            $enable_categories = true;
        }

        // Columnas configuradas
        $columns = isset($settings['columns']) && is_array($settings['columns']) ? $settings['columns'] : array();
        $visible_columns = array();
        foreach ($columns as $c) {
            if (!empty($c['visible'])) {
                $visible_columns[] = $c;
            }
        }

        // Textos configurables
        $texts = isset($settings['texts']) ? $settings['texts'] : array();
        $search_placeholder = !empty($texts['search_placeholder']) ? $texts['search_placeholder'] : __('Escribí para buscar en la tabla...', 'prolijo-sheets-viewer');
        $all_categories_txt = !empty($texts['all_categories']) ? $texts['all_categories'] : __('Todas las categorías', 'prolijo-sheets-viewer');
        $no_results_txt     = !empty($texts['no_results']) ? $texts['no_results'] : __('No se encontraron registros con ese criterio de búsqueda.', 'prolijo-sheets-viewer');
        $loading_txt        = !empty($texts['loading']) ? $texts['loading'] : __('Cargando datos...', 'prolijo-sheets-viewer');
        $item_name_txt      = !empty($texts['item_name']) ? $texts['item_name'] : __('registros', 'prolijo-sheets-viewer');

        // Reglas de badges de estado
        $stock_rules = isset($settings['stock_rules']) ? $settings['stock_rules'] : array();
        $ok_words = array_filter(array_map('trim', explode(',', isset($stock_rules['ok_words']) ? $stock_rules['ok_words'] : 'disponible, stock, en stock, si, activo, ok, publicado')));
        $low_words = array_filter(array_map('trim', explode(',', isset($stock_rules['low_words']) ? $stock_rules['low_words'] : 'bajo, menos, ultimas, pocas, consultar, pendiente, revision')));
        $show_credits = !empty($settings['show_credits']);

        // Encolar assets de frontend
        wp_enqueue_style(
            'prolijo-cap-frontend-css',
            PROLIJO_CAP_URL . 'assets/css/frontend.css',
            array(),
            PROLIJO_CAP_VERSION
        );

        wp_enqueue_script(
            'prolijo-cap-frontend-js',
            PROLIJO_CAP_URL . 'assets/js/frontend.js',
            array(),
            PROLIJO_CAP_VERSION,
            true
        );

        $instance_id = wp_unique_id('prolijo-sheets-');

        // Datos de configuración inyectados para JS
        $sheets_config = array(
            'ajax_url'          => admin_url('admin-ajax.php'),
            'initial_tab'       => $initial_tab,
            'page_size'         => $page_size,
            'enable_search'     => $enable_search,
            'enable_categories' => $enable_categories,
            'tabs'              => $active_tabs,
            'columns'           => $columns,
            'visible_columns'   => $visible_columns,
            'texts'             => array(
                'search_placeholder' => $search_placeholder,
                'all_categories'     => $all_categories_txt,
                'no_results'         => $no_results_txt,
                'loading'            => $loading_txt,
                'item_name'          => $item_name_txt,
                'showing'            => __('Mostrando', 'prolijo-sheets-viewer'),
                'of'                 => __('de', 'prolijo-sheets-viewer'),
                'page'               => __('Página', 'prolijo-sheets-viewer'),
                'prev'               => __('← Anteriores', 'prolijo-sheets-viewer'),
                'next'               => __('Siguientes →', 'prolijo-sheets-viewer'),
                'retry'              => __('Reintentar', 'prolijo-sheets-viewer'),
                'error_timeout'      => __('La consulta tardó más tiempo de lo esperado y se canceló. Por favor, reintentá.', 'prolijo-sheets-viewer'),
            ),
            'stock_rules'       => array(
                'ok_words'  => array_values($ok_words),
                'low_words' => array_values($low_words),
            ),
        );

        // Si solo hay una pestaña activa, no es necesario dibujar la barra de pestañas
        $show_tabs_nav = count($active_tabs) > 1;

        ob_start();
        ?>
        <div id="<?php echo esc_attr($instance_id); ?>" class="prolijo-catalogo" data-config="<?php echo esc_attr(wp_json_encode($sheets_config)); ?>">
            <?php if ($show_tabs_nav) : ?>
                <!-- Pestañas u Hojas -->
                <div class="prolijo-catalogo-tabs" role="tablist" aria-label="<?php esc_attr_e('Pestañas', 'prolijo-sheets-viewer'); ?>">
                    <?php foreach ($active_tabs as $t) : ?>
                        <?php $is_active = ($t['id'] === $initial_tab); ?>
                        <button type="button"
                                class="prolijo-tab-btn <?php echo $is_active ? 'is-active' : ''; ?>"
                                data-tab="<?php echo esc_attr($t['id']); ?>"
                                data-provider="<?php echo esc_attr($t['id']); ?>"
                                aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                            <?php echo esc_html($t['nombre']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Loader de estado -->
            <div class="prolijo-catalogo-loader" aria-live="polite">
                <?php echo esc_html($loading_txt); ?>
            </div>

            <!-- Contenedor principal con filtros y tabla -->
            <div class="prolijo-catalogo-container" hidden>
                <?php if ($enable_search || $enable_categories) : ?>
                    <!-- Barra de Filtros (Opcionales) -->
                    <div class="prolijo-filtros-bar">
                        <?php if ($enable_search) : ?>
                            <div class="prolijo-filtro-grupo">
                                <label>
                                    <span class="prolijo-filtro-icon">🔍</span> <?php esc_html_e('Buscar:', 'prolijo-sheets-viewer'); ?>
                                    <input type="search" class="prolijo-input-buscar" placeholder="<?php echo esc_attr($search_placeholder); ?>" autocomplete="off">
                                </label>
                            </div>
                        <?php endif; ?>

                        <?php if ($enable_categories) : ?>
                            <div class="prolijo-filtro-grupo">
                                <label>
                                    <span class="prolijo-filtro-icon">📁</span> <?php esc_html_e('Categoría:', 'prolijo-sheets-viewer'); ?>
                                    <select class="prolijo-select-cat">
                                        <option value=""><?php echo esc_html($all_categories_txt); ?></option>
                                    </select>
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Datos con Scroll Horizontal -->
                <div class="prolijo-tabla-scroll">
                    <table class="prolijo-tabla-productos">
                        <thead>
                            <tr>
                                <?php foreach ($visible_columns as $col) : ?>
                                    <th class="col-<?php echo esc_attr($col['key']); ?>">
                                        <?php echo esc_html($col['title']); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Barra de Paginación -->
                <div class="prolijo-paginacion-bar">
                    <div class="prolijo-pag-info" aria-live="polite"></div>
                    <div class="prolijo-pag-botones">
                        <button type="button" class="prolijo-btn-pag prolijo-btn-prev" hidden>
                            <?php echo esc_html(sprintf(__('← Anteriores %d', 'prolijo-sheets-viewer'), $page_size)); ?>
                        </button>
                        <button type="button" class="prolijo-btn-pag prolijo-btn-next" hidden>
                            <?php echo esc_html(sprintf(__('Siguientes %d →', 'prolijo-sheets-viewer'), $page_size)); ?>
                        </button>
                    </div>
                </div>

                <?php if ($show_credits) : ?>
                    <!-- Enlace a Prolijo.ar para redirigir tráfico -->
                    <div class="prolijo-catalogo-footer">
                        <a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" class="prolijo-credit-link">
                            <?php esc_html_e('Desarrollado por Prolijo.ar', 'prolijo-sheets-viewer'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
