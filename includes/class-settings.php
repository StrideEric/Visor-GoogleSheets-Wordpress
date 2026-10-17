<?php
/**
 * Panel de administración y gestión de opciones del visor de Google Sheets.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 *
 * @package Prolijo_Sheets_Viewer
 */

if (!defined('ABSPATH')) {
    exit;
}

class Prolijo_CAP_Settings {

    const OPTION_KEY = 'prolijo_sheets_settings';
    const LEGACY_OPTION_KEY = 'prolijo_cap_settings';

    /**
     * Inicializa los hooks de administración.
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_filter('plugin_action_links_' . PROLIJO_CAP_BASENAME, array(__CLASS__, 'add_plugin_action_links'));
    }

    /**
     * Devuelve la configuración por defecto del plugin (sin datos privados).
     *
     * @return array
     */
    public static function get_default_settings() {
        return array(
            'sheets_url'        => '', // Vacío por defecto para no exponer datos privados del usuario
            'cache_time'        => 5,
            'page_size'         => 50,
            'default_tab'       => 'hoja_1',
            'enable_search'     => 1, // Buscador opcional (1 = activo, 0 = inactivo)
            'enable_categories' => 1, // Filtro de categorías opcional (1 = activo, 0 = inactivo)
            'show_credits'      => 1,
            'tabs'              => array(
                array(
                    'id'     => 'hoja_1',
                    'nombre' => 'Pestaña 1',
                    'gid'    => '0',
                    'url'    => '',
                    'active' => 1,
                ),
            ),
            'columns'           => array(
                array(
                    'key'        => 'col_1',
                    'index'      => 0,
                    'title'      => 'Identificador / SKU / Título',
                    'searchable' => 1,
                    'visible'    => 1,
                    'type'       => 'code',
                ),
                array(
                    'key'        => 'col_2',
                    'index'      => 1,
                    'title'      => 'Descripción / Detalle',
                    'searchable' => 1,
                    'visible'    => 1,
                    'type'       => 'text',
                ),
                array(
                    'key'        => 'col_3',
                    'index'      => 2,
                    'title'      => 'Categoría / Sección',
                    'searchable' => 0,
                    'visible'    => 1,
                    'type'       => 'category',
                ),
                array(
                    'key'        => 'col_4',
                    'index'      => 3,
                    'title'      => 'Estado / Disponibilidad',
                    'searchable' => 0,
                    'visible'    => 1,
                    'type'       => 'stock',
                ),
                array(
                    'key'        => 'col_5',
                    'index'      => 4,
                    'title'      => 'Precio / Valor',
                    'searchable' => 0,
                    'visible'    => 1,
                    'type'       => 'price',
                ),
            ),
            'texts'             => array(
                'search_placeholder' => 'Escribí para buscar en la tabla...',
                'all_categories'     => 'Todas las categorías',
                'no_results'         => 'No se encontraron registros con ese criterio de búsqueda.',
                'loading'            => 'Cargando datos desde Google Sheets...',
                'item_name'          => 'registros',
            ),
            'stock_rules'       => array(
                'ok_words'  => 'disponible, stock, en stock, si, activo, ok, publicado',
                'low_words' => 'bajo, menos, ultimas, pocas, consultar, pendiente, revision',
            ),
        );
    }

    /**
     * Obtiene los ajustes combinados con los valores predeterminados.
     *
     * @return array
     */
    public static function get_settings() {
        $saved = get_option(self::OPTION_KEY, null);
        if ($saved === null) {
            $saved = get_option(self::LEGACY_OPTION_KEY, array());
        }

        $defaults = self::get_default_settings();

        if (!is_array($saved) || empty($saved)) {
            return $defaults;
        }

        // Si venía configurado con 'providers', normalizar a 'tabs'
        if (isset($saved['providers']) && !isset($saved['tabs'])) {
            $saved['tabs'] = $saved['providers'];
        }
        if (isset($saved['default_provider']) && !isset($saved['default_tab'])) {
            $saved['default_tab'] = $saved['default_provider'];
        }

        return wp_parse_args($saved, $defaults);
    }

    /**
     * Agrega el enlace en el menú lateral de WordPress.
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Visor Google Sheets', 'prolijo-sheets-viewer'),
            __('Visor Google Sheets', 'prolijo-sheets-viewer'),
            'manage_options',
            'prolijo-sheets-viewer',
            array(__CLASS__, 'render_admin_page'),
            'dashicons-media-spreadsheet',
            56
        );
    }

    /**
     * Enlaces de acción rápida en la lista de plugins.
     */
    public static function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=prolijo-sheets-viewer')) . '">' . __('Configuración', 'prolijo-sheets-viewer') . '</a>';
        $author_link = '<a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" style="font-weight:700; color:#2563eb;">Prolijo.ar</a>';
        array_unshift($links, $author_link);
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Registra las opciones en la API de configuración de WordPress.
     */
    public static function register_settings() {
        register_setting(
            'prolijo_sheets_settings_group',
            self::OPTION_KEY,
            array(__CLASS__, 'sanitize_settings')
        );
    }

    /**
     * Encola estilos y scripts para el panel de administración.
     */
    public static function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_prolijo-sheets-viewer' && $hook !== 'toplevel_page_prolijo-catalogo-pedido') {
            return;
        }

        wp_enqueue_style(
            'prolijo-cap-admin-css',
            PROLIJO_CAP_URL . 'assets/css/admin.css',
            array(),
            PROLIJO_CAP_VERSION
        );

        wp_enqueue_script(
            'prolijo-cap-admin-js',
            PROLIJO_CAP_URL . 'assets/js/admin.js',
            array('jquery'),
            PROLIJO_CAP_VERSION,
            true
        );

        wp_localize_script('prolijo-cap-admin-js', 'prolijoCapAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('prolijo_cap_admin_nonce'),
            'texts'    => array(
                'clearing_cache' => __('Vaciando caché...', 'prolijo-sheets-viewer'),
                'testing'        => __('Probando conexión...', 'prolijo-sheets-viewer'),
                'confirm_delete' => __('¿Estás seguro de eliminar este elemento?', 'prolijo-sheets-viewer'),
            ),
        ));
    }

    /**
     * Sanitiza los campos guardados SIN reiniciar las configuraciones de otras pestañas.
     * Mantiene los valores actuales de los campos no incluidos en el formulario enviado.
     */
    public static function sanitize_settings($input) {
        $current = self::get_settings();
        $output  = $current; // PRESERVA las configuraciones previas para no reiniciar nada

        // 1. URL de Google Sheets
        if (isset($input['sheets_url'])) {
            $output['sheets_url'] = esc_url_raw(trim($input['sheets_url']));
        }

        // 2. Caché y paginación
        if (isset($input['cache_time'])) {
            $output['cache_time'] = max(0, (int) $input['cache_time']);
        }
        if (isset($input['page_size'])) {
            $output['page_size'] = max(5, min(500, (int) $input['page_size']));
        }
        if (isset($input['default_tab'])) {
            $output['default_tab'] = sanitize_key($input['default_tab']);
        }

        // 3. Opciones de buscador y filtro de categorías (Opcionales)
        if (isset($input['settings_submitted'])) {
            $output['enable_search']     = !empty($input['enable_search']) ? 1 : 0;
            $output['enable_categories'] = !empty($input['enable_categories']) ? 1 : 0;
            $output['show_credits']      = !empty($input['show_credits']) ? 1 : 0;
        }

        // 4. Pestañas / Hojas
        if (isset($input['tabs']) && is_array($input['tabs'])) {
            $clean_tabs = array();
            foreach ($input['tabs'] as $tab) {
                $id = !empty($tab['id']) ? sanitize_key($tab['id']) : '';
                $nombre = !empty($tab['nombre']) ? sanitize_text_field($tab['nombre']) : '';
                $gid = isset($tab['gid']) ? sanitize_text_field(trim($tab['gid'])) : '0';
                $url = !empty($tab['url']) ? esc_url_raw(trim($tab['url'])) : '';
                $active = !empty($tab['active']) ? 1 : 0;

                if (!empty($id) && !empty($nombre)) {
                    $clean_tabs[] = array(
                        'id'     => $id,
                        'nombre' => $nombre,
                        'gid'    => $gid,
                        'url'    => $url,
                        'active' => $active,
                    );
                }
            }
            if (!empty($clean_tabs)) {
                $output['tabs'] = $clean_tabs;
                $output['providers'] = $clean_tabs;
            }
        }

        // 5. Columnas
        if (isset($input['columns']) && is_array($input['columns'])) {
            $clean_cols = array();
            foreach ($input['columns'] as $col) {
                $key = !empty($col['key']) ? sanitize_key($col['key']) : '';
                $title = !empty($col['title']) ? sanitize_text_field($col['title']) : '';
                $index = isset($col['index']) ? max(0, (int) $col['index']) : 0;
                $searchable = !empty($col['searchable']) ? 1 : 0;
                $visible = !empty($col['visible']) ? 1 : 0;
                $type = !empty($col['type']) && in_array($col['type'], array('text', 'code', 'category', 'stock', 'price'), true) ? $col['type'] : 'text';

                if (!empty($key) && !empty($title)) {
                    $clean_cols[] = array(
                        'key'        => $key,
                        'index'      => $index,
                        'title'      => $title,
                        'searchable' => $searchable,
                        'visible'    => $visible,
                        'type'       => $type,
                    );
                }
            }
            if (!empty($clean_cols)) {
                $output['columns'] = $clean_cols;
            }
        }

        // 6. Textos de interfaz
        if (isset($input['texts']) && is_array($input['texts'])) {
            if (!isset($output['texts'])) {
                $output['texts'] = array();
            }
            foreach ($input['texts'] as $tk => $tv) {
                $output['texts'][$tk] = sanitize_text_field($tv);
            }
        }

        // 7. Reglas de badges de estado
        if (isset($input['stock_rules']) && is_array($input['stock_rules'])) {
            if (!isset($output['stock_rules'])) {
                $output['stock_rules'] = array();
            }
            foreach ($input['stock_rules'] as $rk => $rv) {
                $output['stock_rules'][$rk] = sanitize_text_field($rv);
            }
        }

        // Al guardar cambios, limpiar transients para que tomen efecto de inmediato
        Prolijo_CAP_Sheets_Client::clear_all_cache();

        return $output;
    }

    /**
     * Renderiza la página completa del panel de administración.
     * Todas las pestañas están dentro del mismo formulario interactivo para evitar pérdida de datos al guardar.
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        ?>
        <div class="wrap prolijo-admin-wrap">
            <header class="prolijo-admin-header">
                <div class="prolijo-header-brand">
                    <a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
                        <span class="prolijo-badge-brand">Prolijo.ar</span>
                    </a>
                    <h1><?php esc_html_e('Visor de Google Sheets', 'prolijo-sheets-viewer'); ?></h1>
                    <span class="prolijo-badge-version">v<?php echo esc_html(PROLIJO_CAP_VERSION); ?></span>
                </div>
                <div class="prolijo-header-actions">
                    <button type="button" class="button button-secondary" id="prolijo-btn-clear-cache">
                        <span class="dashicons dashicons-update"></span> <?php esc_html_e('Vaciar Caché', 'prolijo-sheets-viewer'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="prolijo-btn-test-connection">
                        <span class="dashicons dashicons-cloud"></span> <?php esc_html_e('Probar Conexión', 'prolijo-sheets-viewer'); ?>
                    </button>
                    <a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" class="prolijo-header-author">
                        <?php esc_html_e('Desarrollado por Prolijo.ar', 'prolijo-sheets-viewer'); ?> &rarr;
                    </a>
                </div>
            </header>

            <div id="prolijo-ajax-feedback" class="prolijo-alert-box" style="display:none;"></div>

            <!-- Navegación de pestañas interactiva (sin recarga lenta de página) -->
            <nav class="nav-tab-wrapper prolijo-tab-nav" id="prolijo-admin-tabs">
                <button type="button" class="nav-tab nav-tab-active" data-target="instrucciones">
                    <span class="dashicons dashicons-book"></span> <?php esc_html_e('1. Cómo Funciona', 'prolijo-sheets-viewer'); ?>
                </button>
                <button type="button" class="nav-tab" data-target="pestanas">
                    <span class="dashicons dashicons-networking"></span> <?php esc_html_e('2. Google Sheets y Pestañas', 'prolijo-sheets-viewer'); ?>
                </button>
                <button type="button" class="nav-tab" data-target="columnas">
                    <span class="dashicons dashicons-search"></span> <?php esc_html_e('3. Columnas y Búsqueda', 'prolijo-sheets-viewer'); ?>
                </button>
                <button type="button" class="nav-tab" data-target="ajustes">
                    <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('4. Ajustes y Opciones', 'prolijo-sheets-viewer'); ?>
                </button>
            </nav>

            <div class="prolijo-tab-content">
                <!-- PANE 1: Instrucciones -->
                <div class="prolijo-tab-pane is-active" id="prolijo-pane-instrucciones">
                    <?php self::render_tab_instrucciones(); ?>
                </div>

                <!-- FORMULARIO GLOBAL: Contiene Pestañas, Columnas y Ajustes para que NUNCA se pierdan datos al guardar -->
                <form method="post" action="options.php" id="prolijo-settings-form">
                    <?php settings_fields('prolijo_sheets_settings_group'); ?>
                    <input type="hidden" name="prolijo_sheets_settings[settings_submitted]" value="1">

                    <!-- PANE 2: Pestañas de Google Sheets -->
                    <div class="prolijo-tab-pane" id="prolijo-pane-pestanas" style="display:none;">
                        <?php self::render_tab_pestanas($settings); ?>
                        <div class="prolijo-form-footer">
                            <?php submit_button(__('Guardar Configuración', 'prolijo-sheets-viewer'), 'primary button-hero', 'submit_pestanas', false); ?>
                        </div>
                    </div>

                    <!-- PANE 3: Columnas y Búsqueda -->
                    <div class="prolijo-tab-pane" id="prolijo-pane-columnas" style="display:none;">
                        <?php self::render_tab_columnas($settings); ?>
                        <div class="prolijo-form-footer">
                            <?php submit_button(__('Guardar Configuración', 'prolijo-sheets-viewer'), 'primary button-hero', 'submit_columnas', false); ?>
                        </div>
                    </div>

                    <!-- PANE 4: Ajustes y Opciones -->
                    <div class="prolijo-tab-pane" id="prolijo-pane-ajustes" style="display:none;">
                        <?php self::render_tab_ajustes($settings); ?>
                        <div class="prolijo-form-footer">
                            <?php submit_button(__('Guardar Configuración', 'prolijo-sheets-viewer'), 'primary button-hero', 'submit_ajustes', false); ?>
                        </div>
                    </div>
                </form>
            </div>

            <footer style="margin-top:25px; padding:15px 0; text-align:center; color:#64748b; font-size:13px; border-top:1px solid #e2e8f0;">
                <?php esc_html_e('Visor de Google Sheets es una herramienta desarrollada por', 'prolijo-sheets-viewer'); ?>
                <a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" style="color:#2563eb; font-weight:700; text-decoration:none;">Prolijo.ar</a> —
                <?php esc_html_e('Soluciones web profesionales, integraciones a medida y automatizaciones en WordPress.', 'prolijo-sheets-viewer'); ?>
            </footer>
        </div>
        <?php
    }

    /**
     * Tab 1: Explicación universal del funcionamiento e instrucciones paso a paso.
     */
    private static function render_tab_instrucciones() {
        ?>
        <div class="prolijo-instructions-container">
            <div class="prolijo-intro-banner">
                <h2><?php esc_html_e('¡Bienvenido al Visor Universal de Google Sheets de Prolijo.ar!', 'prolijo-sheets-viewer'); ?></h2>
                <p>
                    <?php esc_html_e('Esta herramienta te permite conectar cualquier planilla de Google Sheets a tu sitio WordPress para mostrar datos interactivos con buscador predictivo en tiempo real, filtro por categorías, paginación rápida y diseño 100% responsivo para móviles.', 'prolijo-sheets-viewer'); ?>
                </p>
            </div>

            <div class="prolijo-card prolijo-mt-20" style="background:#f8fafc; border-left:4px solid #2563eb; margin-bottom:20px;">
                <h3 style="margin-top:0; color:#0f172a;"><?php esc_html_e('💡 Ejemplos de uso:', 'prolijo-sheets-viewer'); ?></h3>
                <ul style="margin:0 0 0 20px; font-size:14px; color:#334155; line-height:1.7;">
                    <li><strong><?php esc_html_e('Catálogos y Listas de Precios:', 'prolijo-sheets-viewer'); ?></strong> <?php esc_html_e('Mostrá productos a pedido, stock de proveedores, precios actualizados y códigos SKU.', 'prolijo-sheets-viewer'); ?></li>
                    <li><strong><?php esc_html_e('Preguntas Frecuentes (FAQ):', 'prolijo-sheets-viewer'); ?></strong> <?php esc_html_e('Publicá una base de conocimiento donde los usuarios pueden buscar preguntas y respuestas al instante.', 'prolijo-sheets-viewer'); ?></li>
                    <li><strong><?php esc_html_e('Directorios y Contactos:', 'prolijo-sheets-viewer'); ?></strong> <?php esc_html_e('Listados de profesionales, sedes, sucursales, horarios o contactos organizados por sección.', 'prolijo-sheets-viewer'); ?></li>
                    <li><strong><?php esc_html_e('Tablas de Cotizaciones e Inventarios:', 'prolijo-sheets-viewer'); ?></strong> <?php esc_html_e('Cualquier tabla de datos que mantengas en Google Sheets sin necesidad de tocar código en WordPress.', 'prolijo-sheets-viewer'); ?></li>
                </ul>
            </div>

            <div class="prolijo-steps-grid">
                <div class="prolijo-step-card">
                    <div class="prolijo-step-number">1</div>
                    <h3><?php esc_html_e('Preparar la planilla', 'prolijo-sheets-viewer'); ?></h3>
                    <p><?php esc_html_e('Organiza los datos en tu Google Sheets en filas. La fila 1 contendrá los encabezados de columna.', 'prolijo-sheets-viewer'); ?></p>
                    <small><?php esc_html_e('En la pestaña "3. Columnas y Búsqueda" podés configurar qué columnas buscar y cuáles mostrar.', 'prolijo-sheets-viewer'); ?></small>
                </div>

                <div class="prolijo-step-card">
                    <div class="prolijo-step-number">2</div>
                    <h3><?php esc_html_e('Publicar en la Web en Google Sheets', 'prolijo-sheets-viewer'); ?></h3>
                    <p><?php esc_html_e('En tu documento de Google Sheets:', 'prolijo-sheets-viewer'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Andá a: Archivo &rarr; Compartir &rarr; Publicar en la web.', 'prolijo-sheets-viewer'); ?></li>
                        <li><?php esc_html_e('Enlace: seleccioná "Todo el documento" o la pestaña deseada.', 'prolijo-sheets-viewer'); ?></li>
                        <li><?php esc_html_e('Formato: elegí "Valores separados por comas (.csv)".', 'prolijo-sheets-viewer'); ?></li>
                        <li><?php esc_html_e('Hacé clic en Publicar y copiá el enlace generado.', 'prolijo-sheets-viewer'); ?></li>
                    </ol>
                </div>

                <div class="prolijo-step-card">
                    <div class="prolijo-step-number">3</div>
                    <h3><?php esc_html_e('Identificar el GID de cada pestaña', 'prolijo-sheets-viewer'); ?></h3>
                    <p><?php esc_html_e('Si tu documento tiene varias pestañas, cada una tiene un "gid" al final de la URL en el navegador:', 'prolijo-sheets-viewer'); ?></p>
                    <code>.../edit#gid=<strong>0</strong></code>
                    <p><?php esc_html_e('Ese número es el que debés ingresar en la configuración para cada pestaña.', 'prolijo-sheets-viewer'); ?></p>
                </div>

                <div class="prolijo-step-card">
                    <div class="prolijo-step-number">4</div>
                    <h3><?php esc_html_e('Insertar en tu sitio web', 'prolijo-sheets-viewer'); ?></h3>
                    <p><?php esc_html_e('Pegá el siguiente shortcode en cualquier página o entrada:', 'prolijo-sheets-viewer'); ?></p>
                    <div class="prolijo-shortcode-box">
                        <code>[prolijo_sheets]</code>
                        <button type="button" class="button button-small prolijo-copy-code" data-code="[prolijo_sheets]"><?php esc_html_e('Copiar', 'prolijo-sheets-viewer'); ?></button>
                    </div>
                </div>
            </div>

            <div class="prolijo-card prolijo-mt-20">
                <h3><?php esc_html_e('Opciones del Shortcode', 'prolijo-sheets-viewer'); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Shortcode con parámetro', 'prolijo-sheets-viewer'); ?></th>
                            <th><?php esc_html_e('Descripción', 'prolijo-sheets-viewer'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[prolijo_sheets]</code></td>
                            <td><?php esc_html_e('Muestra la tabla con la configuración predeterminada.', 'prolijo-sheets-viewer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[prolijo_sheets buscador="no"]</code></td>
                            <td><?php esc_html_e('Oculta el buscador predictivo para esa tabla.', 'prolijo-sheets-viewer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[prolijo_sheets categorias="no"]</code></td>
                            <td><?php esc_html_e('Oculta el selector desplegable de categorías para esa tabla.', 'prolijo-sheets-viewer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[prolijo_sheets hoja="faq"]</code></td>
                            <td><?php esc_html_e('Inicia directamente con la pestaña especificada seleccionada.', 'prolijo-sheets-viewer'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[prolijo_sheets filas="25"]</code></td>
                            <td><?php esc_html_e('Sobrescribe la cantidad de filas por página.', 'prolijo-sheets-viewer'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Caja de redirección de tráfico a Prolijo.ar -->
            <div class="prolijo-card prolijo-mt-20" style="background:#f8fafc; border-left:4px solid #2563eb;">
                <h3 style="margin-top:0; color:#0f172a;"><?php esc_html_e('¿Necesitás desarrollos a medida o integraciones avanzadas?', 'prolijo-sheets-viewer'); ?></h3>
                <p style="font-size:14px; color:#475569; line-height:1.6;">
                    <?php esc_html_e('En Prolijo.ar creamos herramientas personalizadas, integraciones de Google Sheets con WooCommerce o CRMs y desarrollo web profesional.', 'prolijo-sheets-viewer'); ?>
                </p>
                <a href="https://prolijo.ar/" target="_blank" rel="noopener noreferrer" class="button button-primary" style="font-weight:700;">
                    <?php esc_html_e('Conocé más en Prolijo.ar', 'prolijo-sheets-viewer'); ?> &rarr;
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Tab 2: Configuración de URL de Google Sheets y Pestañas / Secciones.
     */
    private static function render_tab_pestanas($settings) {
        $sheets_url = isset($settings['sheets_url']) ? $settings['sheets_url'] : '';
        $tabs       = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : array();
        ?>
        <div class="prolijo-section-box">
            <h2><?php esc_html_e('2. Configuración de Google Sheets y Pestañas / Secciones', 'prolijo-sheets-viewer'); ?></h2>
            <p class="description">
                <?php esc_html_e('Ingresá el enlace de publicación de tu Google Sheets y añadí las pestañas con los nombres exactos que querés que se muestren en los botones de tu página web (ej: "Productos", "Preguntas Frecuentes", "Precios", proveedores, etc.).', 'prolijo-sheets-viewer'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="prolijo_sheets_url"><?php esc_html_e('URL de Google Sheets (Publicada en la Web)', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="url" name="prolijo_sheets_settings[sheets_url]" id="prolijo_sheets_url" value="<?php echo esc_attr($sheets_url); ?>" class="large-text" placeholder="https://docs.google.com/spreadsheets/d/e/.../pub">
                        <p class="description">
                            <?php esc_html_e('Copiá el enlace generado en Archivo > Compartir > Publicar en la web (como CSV) en tu Google Sheets.', 'prolijo-sheets-viewer'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Pestañas / Hojas a Mostrar en la Web', 'prolijo-sheets-viewer'); ?></h3>
            <p class="description">
                <?php esc_html_e('Para cada pestaña podés definir su clave identificadora, el nombre que los usuarios verán en la página web, y el número GID de la hoja en Google Sheets.', 'prolijo-sheets-viewer'); ?>
            </p>

            <table class="widefat striped prolijo-table-repeater" id="prolijo-table-providers">
                <thead>
                    <tr>
                        <th style="width: 140px;"><?php esc_html_e('ID / Slug', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 240px;"><?php esc_html_e('Nombre a mostrar en la web', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 140px;"><?php esc_html_e('GID pestaña', 'prolijo-sheets-viewer'); ?></th>
                        <th><?php esc_html_e('URL personalizada (opcional)', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php esc_html_e('Activo', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php esc_html_e('Acción', 'prolijo-sheets-viewer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tabs)) : ?>
                        <?php foreach ($tabs as $idx => $t) : ?>
                            <tr>
                                <td>
                                    <input type="text" name="prolijo_sheets_settings[tabs][<?php echo esc_attr($idx); ?>][id]" value="<?php echo esc_attr(isset($t['id']) ? $t['id'] : ''); ?>" class="regular-text code" style="width:100%;" placeholder="ej: principal" required>
                                </td>
                                <td>
                                    <input type="text" name="prolijo_sheets_settings[tabs][<?php echo esc_attr($idx); ?>][nombre]" value="<?php echo esc_attr(isset($t['nombre']) ? $t['nombre'] : ''); ?>" class="regular-text" style="width:100%; font-weight:600;" placeholder="Ej: Pestaña Principal" required>
                                </td>
                                <td>
                                    <input type="text" name="prolijo_sheets_settings[tabs][<?php echo esc_attr($idx); ?>][gid]" value="<?php echo esc_attr(isset($t['gid']) ? $t['gid'] : '0'); ?>" class="small-text code" style="width:100%;" placeholder="0">
                                </td>
                                <td>
                                    <input type="url" name="prolijo_sheets_settings[tabs][<?php echo esc_attr($idx); ?>][url]" value="<?php echo esc_attr(isset($t['url']) ? $t['url'] : ''); ?>" class="regular-text" style="width:100%;" placeholder="Dejar vacío si usa la URL general">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="prolijo_sheets_settings[tabs][<?php echo esc_attr($idx); ?>][active]" value="1" <?php checked(!empty($t['active'])); ?>>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="button button-link-delete prolijo-remove-row">&times; <?php esc_html_e('Borrar', 'prolijo-sheets-viewer'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 12px;">
                <button type="button" class="button button-secondary" id="prolijo-add-provider">
                    <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Agregar Pestaña / Hoja', 'prolijo-sheets-viewer'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Tab 3: Configuración de columnas a mostrar y columnas de búsqueda.
     */
    private static function render_tab_columnas($settings) {
        $columns = isset($settings['columns']) && is_array($settings['columns']) ? $settings['columns'] : array();
        ?>
        <div class="prolijo-section-box">
            <h2><?php esc_html_e('3. Configuración de Columnas y Búsqueda', 'prolijo-sheets-viewer'); ?></h2>
            <p class="description">
                <?php esc_html_e('Definí qué columnas del CSV se deben mostrar en la tabla y cuáles deben ser utilizadas por el buscador predictivo en tiempo real (por ejemplo, SKU y Producto, o Pregunta y Respuesta).', 'prolijo-sheets-viewer'); ?>
            </p>

            <table class="widefat striped prolijo-table-repeater" id="prolijo-table-columns">
                <thead>
                    <tr>
                        <th style="width: 90px;"><?php esc_html_e('Índice CSV', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 140px;"><?php esc_html_e('Identificador', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 220px;"><?php esc_html_e('Título a mostrar en la tabla', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 160px;"><?php esc_html_e('Tipo de dato / Formato', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 100px; text-align: center;"><?php esc_html_e('¿Mostrar?', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 130px; text-align: center;"><?php esc_html_e('¿Buscar en esta?', 'prolijo-sheets-viewer'); ?></th>
                        <th style="width: 80px; text-align: center;"><?php esc_html_e('Acción', 'prolijo-sheets-viewer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($columns)) : ?>
                        <?php foreach ($columns as $idx => $col) : ?>
                            <tr>
                                <td>
                                    <input type="number" name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][index]" value="<?php echo esc_attr(isset($col['index']) ? $col['index'] : $idx); ?>" min="0" max="99" class="small-text" style="width:100%; text-align:center;" required>
                                </td>
                                <td>
                                    <input type="text" name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][key]" value="<?php echo esc_attr(isset($col['key']) ? $col['key'] : ''); ?>" class="regular-text code" style="width:100%;" required>
                                </td>
                                <td>
                                    <input type="text" name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][title]" value="<?php echo esc_attr(isset($col['title']) ? $col['title'] : ''); ?>" class="regular-text" style="width:100%; font-weight:600;" required>
                                </td>
                                <td>
                                    <select name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][type]" style="width:100%;">
                                        <option value="text" <?php selected($col['type'], 'text'); ?>><?php esc_html_e('Texto estándar', 'prolijo-sheets-viewer'); ?></option>
                                        <option value="code" <?php selected($col['type'], 'code'); ?>><?php esc_html_e('Código / Destacado (Negrita)', 'prolijo-sheets-viewer'); ?></option>
                                        <option value="category" <?php selected($col['type'], 'category'); ?>><?php esc_html_e('Categoría (Filtro desplegable)', 'prolijo-sheets-viewer'); ?></option>
                                        <option value="stock" <?php selected($col['type'], 'stock'); ?>><?php esc_html_e('Estado / Badge con color', 'prolijo-sheets-viewer'); ?></option>
                                        <option value="price" <?php selected($col['type'], 'price'); ?>><?php esc_html_e('Precio / Valor monetario', 'prolijo-sheets-viewer'); ?></option>
                                    </select>
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][visible]" value="1" <?php checked(!empty($col['visible'])); ?>>
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="prolijo_sheets_settings[columns][<?php echo esc_attr($idx); ?>][searchable]" value="1" <?php checked(!empty($col['searchable'])); ?>>
                                </td>
                                <td style="text-align:center;">
                                    <button type="button" class="button button-link-delete prolijo-remove-row">&times; <?php esc_html_e('Borrar', 'prolijo-sheets-viewer'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 12px;">
                <button type="button" class="button button-secondary" id="prolijo-add-column">
                    <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Agregar Columna', 'prolijo-sheets-viewer'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Tab 4: Ajustes Generales, Opciones visuales y Caché.
     */
    private static function render_tab_ajustes($settings) {
        $cache_time        = isset($settings['cache_time']) ? (int) $settings['cache_time'] : 5;
        $page_size         = isset($settings['page_size']) ? (int) $settings['page_size'] : 50;
        $def_tab           = isset($settings['default_tab']) ? $settings['default_tab'] : '';
        $enable_search     = !empty($settings['enable_search']);
        $enable_categories = !empty($settings['enable_categories']);
        $show_credits      = !empty($settings['show_credits']);
        $tabs              = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : array();
        $texts             = isset($settings['texts']) ? $settings['texts'] : array();
        $stock_rules       = isset($settings['stock_rules']) ? $settings['stock_rules'] : array();
        ?>
        <div class="prolijo-section-box">
            <h2><?php esc_html_e('4. Ajustes Generales y Opciones Visuales', 'prolijo-sheets-viewer'); ?></h2>

            <h3><?php esc_html_e('Visualización y Filtros (Opcionales)', 'prolijo-sheets-viewer'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Buscador en tiempo real', 'prolijo-sheets-viewer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="prolijo_sheets_settings[enable_search]" value="1" <?php checked($enable_search); ?>>
                            <strong><?php esc_html_e('Habilitar campo de búsqueda', 'prolijo-sheets-viewer'); ?></strong>
                        </label>
                        <p class="description"><?php esc_html_e('Si está desmarcado, el buscador no se mostrará en la tabla.', 'prolijo-sheets-viewer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Filtro de categorías', 'prolijo-sheets-viewer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="prolijo_sheets_settings[enable_categories]" value="1" <?php checked($enable_categories); ?>>
                            <strong><?php esc_html_e('Habilitar menú desplegable de categorías', 'prolijo-sheets-viewer'); ?></strong>
                        </label>
                        <p class="description"><?php esc_html_e('Si está desmarcado, el menú de categorías no se mostrará en la tabla.', 'prolijo-sheets-viewer'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Enlace a Prolijo.ar al pie', 'prolijo-sheets-viewer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="prolijo_sheets_settings[show_credits]" value="1" <?php checked($show_credits); ?>>
                            <?php esc_html_e('Mostrar crédito discreto "Desarrollado por Prolijo.ar" al pie de la tabla (redirige tráfico a https://prolijo.ar/).', 'prolijo-sheets-viewer'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Paginación y Rendimiento', 'prolijo-sheets-viewer'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="prolijo_page_size"><?php esc_html_e('Filas a mostrar por página', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <select name="prolijo_sheets_settings[page_size]" id="prolijo_page_size">
                            <option value="10" <?php selected($page_size, 10); ?>>10</option>
                            <option value="25" <?php selected($page_size, 25); ?>>25</option>
                            <option value="50" <?php selected($page_size, 50); ?>>50 (Recomendado)</option>
                            <option value="100" <?php selected($page_size, 100); ?>>100</option>
                            <option value="200" <?php selected($page_size, 200); ?>>200</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_cache_time"><?php esc_html_e('Tiempo de refresco en caché (minutos)', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="prolijo_sheets_settings[cache_time]" id="prolijo_cache_time" value="<?php echo esc_attr($cache_time); ?>" min="0" max="1440" class="small-text">
                        <p class="description">
                            <?php esc_html_e('Minutos durante los cuales WordPress almacena temporalmente los datos para cargar rápido sin saturar Google Sheets. Usá 0 solo para pruebas.', 'prolijo-sheets-viewer'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_default_tab"><?php esc_html_e('Pestaña activa inicial por defecto', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <select name="prolijo_sheets_settings[default_tab]" id="prolijo_default_tab">
                            <?php foreach ($tabs as $t) : ?>
                                <option value="<?php echo esc_attr($t['id']); ?>" <?php selected($def_tab, $t['id']); ?>>
                                    <?php echo esc_html($t['nombre']); ?> (<?php echo esc_html($t['id']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Textos de la Interfaz', 'prolijo-sheets-viewer'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="prolijo_item_name"><?php esc_html_e('Nombre de los elementos en la paginación', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[texts][item_name]" id="prolijo_item_name" value="<?php echo esc_attr(isset($texts['item_name']) ? $texts['item_name'] : 'registros'); ?>" class="regular-text" placeholder="ej: productos, preguntas, registros">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_search_placeholder"><?php esc_html_e('Texto del buscador', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[texts][search_placeholder]" id="prolijo_search_placeholder" value="<?php echo esc_attr(isset($texts['search_placeholder']) ? $texts['search_placeholder'] : ''); ?>" class="large-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_all_categories"><?php esc_html_e('Texto selector de categorías', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[texts][all_categories]" id="prolijo_all_categories" value="<?php echo esc_attr(isset($texts['all_categories']) ? $texts['all_categories'] : ''); ?>" class="regular-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_no_results"><?php esc_html_e('Mensaje cuando no hay resultados', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[texts][no_results]" id="prolijo_no_results" value="<?php echo esc_attr(isset($texts['no_results']) ? $texts['no_results'] : ''); ?>" class="large-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_stock_ok"><?php esc_html_e('Palabras para Estado Positivo (Badge Verde)', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[stock_rules][ok_words]" id="prolijo_stock_ok" value="<?php echo esc_attr(isset($stock_rules['ok_words']) ? $stock_rules['ok_words'] : ''); ?>" class="large-text">
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="prolijo_stock_low"><?php esc_html_e('Palabras para Estado Alerta (Badge Amarillo)', 'prolijo-sheets-viewer'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="prolijo_sheets_settings[stock_rules][low_words]" id="prolijo_stock_low" value="<?php echo esc_attr(isset($stock_rules['low_words']) ? $stock_rules['low_words'] : ''); ?>" class="large-text">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}
