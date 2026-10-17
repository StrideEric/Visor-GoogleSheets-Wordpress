/**
 * Scripts de interactividad para el panel de administración del Visor de Google Sheets.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 */

jQuery(document).ready(function ($) {
    const feedbackBox = $('#prolijo-ajax-feedback');

    function showFeedback(message, type) {
        feedbackBox.removeClass('is-success is-error is-loading');
        feedbackBox.addClass('is-' + type).html(message).slideDown(200);
        if (type === 'success') {
            setTimeout(function () {
                feedbackBox.slideUp(300);
            }, 6000);
        }
    }

    // Navegación de pestañas interactiva sin pérdida de datos
    $('#prolijo-admin-tabs .nav-tab').on('click', function (e) {
        e.preventDefault();
        const target = $(this).data('target');
        if (!target) return;

        // Actualizar botón activo
        $('#prolijo-admin-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        // Mostrar pane correspondiente
        $('.prolijo-tab-pane').hide().removeClass('is-active');
        $('#prolijo-pane-' + target).show().addClass('is-active');

        // Guardar estado en localStorage
        try {
            localStorage.setItem('prolijo_sheets_active_tab', target);
        } catch (err) {}
    });

    // Restaurar pestaña activa tras guardar o recargar
    try {
        let savedTab = localStorage.getItem('prolijo_sheets_active_tab');
        if (window.location.hash) {
            const hashTab = window.location.hash.replace('#', '');
            if ($('#prolijo-pane-' + hashTab).length) {
                savedTab = hashTab;
            }
        }
        if (savedTab && $('#prolijo-pane-' + savedTab).length) {
            $('#prolijo-admin-tabs .nav-tab[data-target="' + savedTab + '"]').trigger('click');
        }
    } catch (err) {}

    // Copiar shortcode al portapapeles
    $('.prolijo-copy-code').on('click', function () {
        const btn = $(this);
        const code = btn.data('code') || '[prolijo_sheets]';
        navigator.clipboard.writeText(code).then(function () {
            const originalText = btn.text();
            btn.text('¡Copiado!');
            setTimeout(function () {
                btn.text(originalText);
            }, 2000);
        });
    });

    // Vaciar Caché vía AJAX
    $('#prolijo-btn-clear-cache').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true);
        showFeedback('⏳ ' + (prolijoCapAdmin.texts.clearing_cache || 'Vaciando caché...'), 'loading');

        $.ajax({
            url: prolijoCapAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'prolijo_cap_clear_cache',
                nonce: prolijoCapAdmin.nonce
            },
            success: function (res) {
                if (res && res.success) {
                    showFeedback('✓ ' + res.data.message, 'success');
                } else {
                    const msg = (res && res.data && res.data.message) ? res.data.message : 'Error al vaciar caché.';
                    showFeedback('✕ ' + msg, 'error');
                }
            },
            error: function (xhr) {
                showFeedback('✕ Error de comunicación con el servidor (HTTP ' + xhr.status + ').', 'error');
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    // Probar Conexión vía AJAX
    $('#prolijo-btn-test-connection').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true);
        showFeedback('⏳ ' + (prolijoCapAdmin.texts.testing || 'Probando conexión con Google Sheets...'), 'loading');

        $.ajax({
            url: prolijoCapAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'prolijo_cap_test_connection',
                nonce: prolijoCapAdmin.nonce
            },
            success: function (res) {
                if (res && res.success) {
                    let html = '<strong>✓ ' + res.data.message + '</strong>';
                    if (res.data.headers && res.data.headers.length > 0) {
                        html += '<div style="margin-top:6px; font-size:12px;"><strong>Encabezados detectados:</strong> <code>' + res.data.headers.join(' | ') + '</code></div>';
                    }
                    showFeedback(html, 'success');
                } else {
                    const msg = (res && res.data && res.data.message) ? res.data.message : 'No se pudo conectar.';
                    showFeedback('✕ ' + msg, 'error');
                }
            },
            error: function (xhr) {
                showFeedback('✕ Error al probar conexión (HTTP ' + xhr.status + ').', 'error');
            },
            complete: function () {
                btn.prop('disabled', false);
            }
        });
    });

    // Eliminar fila en cualquier tabla repetidora
    $(document).on('click', '.prolijo-remove-row', function () {
        const row = $(this).closest('tr');
        const table = row.closest('table');
        if (table.find('tbody tr').length <= 1) {
            alert('Debe quedar al menos una fila en la configuración.');
            return;
        }
        if (confirm(prolijoCapAdmin.texts.confirm_delete || '¿Eliminar este elemento?')) {
            row.fadeOut(200, function () {
                $(this).remove();
            });
        }
    });

    // Agregar nueva Pestaña / Hoja
    $('#prolijo-add-provider, #prolijo-add-tab').on('click', function () {
        const tbody = $('#prolijo-table-providers tbody');
        const newIndex = Date.now();
        const rowHtml = `
            <tr>
                <td>
                    <input type="text" name="prolijo_sheets_settings[tabs][${newIndex}][id]" value="" class="regular-text code" style="width:100%;" placeholder="ej: principal" required>
                </td>
                <td>
                    <input type="text" name="prolijo_sheets_settings[tabs][${newIndex}][nombre]" value="" class="regular-text" style="width:100%; font-weight:600;" placeholder="Ej: Pestaña Principal" required>
                </td>
                <td>
                    <input type="text" name="prolijo_sheets_settings[tabs][${newIndex}][gid]" value="0" class="small-text code" style="width:100%;" placeholder="0">
                </td>
                <td>
                    <input type="url" name="prolijo_sheets_settings[tabs][${newIndex}][url]" value="" class="regular-text" style="width:100%;" placeholder="Dejar vacío si usa la URL general">
                </td>
                <td style="text-align:center;">
                    <input type="checkbox" name="prolijo_sheets_settings[tabs][${newIndex}][active]" value="1" checked>
                </td>
                <td style="text-align:center;">
                    <button type="button" class="button button-link-delete prolijo-remove-row">&times; Borrar</button>
                </td>
            </tr>
        `;
        tbody.append(rowHtml);
        tbody.find('tr:last input[type="text"]').first().focus();
    });

    // Agregar nueva Columna
    $('#prolijo-add-column').on('click', function () {
        const tbody = $('#prolijo-table-columns tbody');
        const nextIndex = tbody.find('tr').length;
        const uniqueKey = Date.now();
        const rowHtml = `
            <tr>
                <td>
                    <input type="number" name="prolijo_sheets_settings[columns][${uniqueKey}][index]" value="${nextIndex}" min="0" max="99" class="small-text" style="width:100%; text-align:center;" required>
                </td>
                <td>
                    <input type="text" name="prolijo_sheets_settings[columns][${uniqueKey}][key]" value="col_${nextIndex + 1}" class="regular-text code" style="width:100%;" required>
                </td>
                <td>
                    <input type="text" name="prolijo_sheets_settings[columns][${uniqueKey}][title]" value="Nueva Columna" class="regular-text" style="width:100%; font-weight:600;" required>
                </td>
                <td>
                    <select name="prolijo_sheets_settings[columns][${uniqueKey}][type]" style="width:100%;">
                        <option value="text" selected>Texto estándar</option>
                        <option value="code">Código / Destacado (Negrita)</option>
                        <option value="category">Categoría (Filtro desplegable)</option>
                        <option value="stock">Estado / Badge con color</option>
                        <option value="price">Precio / Valor monetario</option>
                    </select>
                </td>
                <td style="text-align:center;">
                    <input type="checkbox" name="prolijo_sheets_settings[columns][${uniqueKey}][visible]" value="1" checked>
                </td>
                <td style="text-align:center;">
                    <input type="checkbox" name="prolijo_sheets_settings[columns][${uniqueKey}][searchable]" value="1">
                </td>
                <td style="text-align:center;">
                    <button type="button" class="button button-link-delete prolijo-remove-row">&times; Borrar</button>
                </td>
            </tr>
        `;
        tbody.append(rowHtml);
        tbody.find('tr:last input[type="text"]').eq(1).focus().select();
    });
});
