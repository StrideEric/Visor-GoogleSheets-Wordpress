/**
 * Motor interactivo del visor de Google Sheets para el Frontend.
 * Desarrollado por Prolijo.ar - https://prolijo.ar/
 */

(function () {
    'use strict';

    function initCatalogo(root) {
        if (!root || root.dataset.prolijoInitialized === '1') {
            return;
        }
        root.dataset.prolijoInitialized = '1';

        // Parsear configuración inyectada
        let config = {};
        try {
            config = JSON.parse(root.dataset.config || '{}');
        } catch (e) {
            console.error('Error al parsear la configuración del visor:', e);
            return;
        }

        const ajaxUrl          = config.ajax_url || '/wp-admin/admin-ajax.php';
        const pageSize         = parseInt(config.page_size, 10) || 50;
        const columns          = Array.isArray(config.columns) ? config.columns : [];
        const visibleCols      = Array.isArray(config.visible_columns) ? config.visible_columns : columns.filter(function (c) { return c.visible; });
        const texts            = config.texts || {};
        const itemName         = texts.item_name || 'registros';
        const enableSearch     = config.enable_search !== false;
        const enableCategories = config.enable_categories !== false;
        const stockOkWords     = (config.stock_rules && Array.isArray(config.stock_rules.ok_words)) ? config.stock_rules.ok_words : ['disponible', 'stock', 'en stock', 'si', 'activo', 'ok', 'publicado'];
        const stockLowWords    = (config.stock_rules && Array.isArray(config.stock_rules.low_words)) ? config.stock_rules.low_words : ['bajo', 'menos', 'ultimas', 'pocas', 'consultar', 'pendiente', 'revision'];

        // Elementos DOM
        const loader          = root.querySelector('.prolijo-catalogo-loader');
        const container       = root.querySelector('.prolijo-catalogo-container');
        const searchInput     = root.querySelector('.prolijo-input-buscar');
        const categorySelect  = root.querySelector('.prolijo-select-cat');
        const tbody           = root.querySelector('.prolijo-tabla-productos tbody');
        const info            = root.querySelector('.prolijo-pag-info');
        const prevButton      = root.querySelector('.prolijo-btn-prev');
        const nextButton      = root.querySelector('.prolijo-btn-next');
        const tabButtons      = Array.from(root.querySelectorAll('[data-tab], [data-provider]'));

        // Estado local
        let allItems          = [];
        let filteredItems     = [];
        let currentPage       = 1;
        let activeTab         = config.initial_tab || config.initial_provider || (tabButtons[0] ? (tabButtons[0].dataset.tab || tabButtons[0].dataset.provider) : 'hoja_1');
        let requestNumber     = 0;
        const memoryCache     = {}; // Caché en memoria durante la sesión en el navegador

        function normalizar(val) {
            return String(val == null ? '' : val)
                .toLocaleLowerCase('es')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        function limpiarTexto(val) {
            return String(val == null ? '' : val).trim();
        }

        function mostrarError(message) {
            loader.replaceChildren();
            loader.classList.add('is-error');
            const p = document.createElement('p');
            p.textContent = message || 'No se pudieron cargar los datos de la hoja.';
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'prolijo-retry';
            retry.textContent = texts.retry || 'Reintentar';
            retry.addEventListener('click', function () {
                cargarPestana(activeTab, true);
            });
            loader.append(p, retry);
            loader.hidden = false;
            container.hidden = true;
        }

        function procesarFilas(rows) {
            allItems = [];
            const categories = new Set();

            // Identificar columna de categoría si existe
            const catCol = columns.find(function (c) { return c.type === 'category'; });
            const catIndex = catCol ? parseInt(catCol.index, 10) : -1;

            // Identificar qué columnas son buscables
            const searchableCols = columns.filter(function (c) { return c.searchable; });

            rows.forEach(function (row, index) {
                if (!Array.isArray(row) || row.length === 0) {
                    return;
                }

                // Omitir cabecera si la primera fila coincide con encabezados típicos
                if (index === 0) {
                    const firstVal = normalizar(row[0]);
                    const secondVal = normalizar(row[1]);
                    if (firstVal === 'sku' || firstVal === 'codigo' || firstVal === 'pregunta' || secondVal.includes('producto') || secondVal.includes('respuesta') || secondVal.includes('descripcion')) {
                        return;
                    }
                }

                // Extraer categoría para el filtro
                let categoryRaw = (catIndex >= 0 && catIndex < row.length) ? limpiarTexto(row[catIndex]) : '';
                let category = categoryRaw.replace(/<[^>]*>/g, '').trim();
                let isFeatured = /strong/i.test(categoryRaw) || normalizar(category).includes('destacado') || normalizar(category).includes('oferta');

                if (category) {
                    categories.add(category);
                }

                // Generar texto combinado para búsqueda según las columnas configuradas
                let searchWords = [];
                if (searchableCols.length > 0) {
                    searchableCols.forEach(function (sc) {
                        const idx = parseInt(sc.index, 10);
                        if (idx < row.length) {
                            searchWords.push(normalizar(row[idx]));
                        }
                    });
                } else {
                    // Fallback a columnas 0 y 1 si ninguna está configurada como buscable
                    if (row[0]) searchWords.push(normalizar(row[0]));
                    if (row[1]) searchWords.push(normalizar(row[1]));
                }
                const searchableText = searchWords.join(' ');

                allItems.push({
                    raw: row,
                    category: category,
                    categoryLower: normalizar(category),
                    isFeatured: isFeatured,
                    searchableText: searchableText
                });
            });

            // Llenar selector de categorías solo si está habilitado y el elemento existe
            if (categorySelect && enableCategories) {
                categorySelect.replaceChildren();
                const allOption = document.createElement('option');
                allOption.value = '';
                allOption.textContent = (texts.all_categories || 'Todas las categorías') + ' (' + allItems.length + ')';
                categorySelect.appendChild(allOption);

                Array.from(categories).sort(function (a, b) {
                    return a.localeCompare(b, 'es', { sensitivity: 'base' });
                }).forEach(function (cat) {
                    const opt = document.createElement('option');
                    opt.value = cat;
                    opt.textContent = cat;
                    categorySelect.appendChild(opt);
                });

                // Ocultar selector si no hay categorías
                const parentGroup = categorySelect.closest('.prolijo-filtro-grupo');
                if (parentGroup) {
                    parentGroup.style.display = categories.size === 0 ? 'none' : '';
                }
                categorySelect.value = '';
            }

            if (searchInput) {
                searchInput.value = '';
            }

            currentPage = 1;
            aplicarFiltros();

            loader.hidden = true;
            loader.classList.remove('is-error');
            container.hidden = false;
        }

        function dibujarPagina() {
            tbody.replaceChildren();
            const total = filteredItems.length;

            if (total === 0) {
                const tr = document.createElement('tr');
                const td = document.createElement('td');
                td.colSpan = Math.max(1, visibleCols.length);
                td.style.textAlign = 'center';
                td.style.padding = '30px 15px';
                td.style.color = '#64748b';
                td.textContent = texts.no_results || 'No se encontraron registros con ese criterio de búsqueda.';
                tr.appendChild(td);
                tbody.appendChild(tr);

                info.textContent = (texts.showing || 'Mostrando') + ' 0 ' + itemName;
                prevButton.hidden = true;
                nextButton.hidden = true;
                return;
            }

            const totalPages = Math.ceil(total / pageSize);
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = Math.min(start + pageSize, total);
            const fragment = document.createDocumentFragment();

            filteredItems.slice(start, end).forEach(function (item) {
                const tr = document.createElement('tr');
                const rowData = item.raw;

                visibleCols.forEach(function (col) {
                    const cell = document.createElement('td');
                    const idx = parseInt(col.index, 10);
                    const val = idx < rowData.length ? limpiarTexto(rowData[idx]) : '';

                    switch (col.type) {
                        case 'code':
                            const strong = document.createElement('strong');
                            strong.textContent = val;
                            cell.appendChild(strong);
                            break;

                        case 'category':
                            if (item.isFeatured) {
                                const strongCat = document.createElement('strong');
                                strongCat.textContent = item.category || val;
                                cell.appendChild(strongCat);
                            } else {
                                cell.textContent = item.category || val;
                            }
                            break;

                        case 'stock':
                            const valNorm = normalizar(val);
                            const isOk = stockOkWords.some(function (w) { return valNorm.includes(normalizar(w)); });
                            const isLow = stockLowWords.some(function (w) { return valNorm.includes(normalizar(w)); });

                            if (isOk) {
                                const badge = document.createElement('span');
                                badge.className = 'prolijo-badge-stock prolijo-stock-ok';
                                badge.textContent = val;
                                cell.appendChild(badge);
                            } else if (isLow) {
                                const badge = document.createElement('span');
                                badge.className = 'prolijo-badge-stock prolijo-stock-bajo';
                                badge.textContent = val;
                                cell.appendChild(badge);
                            } else {
                                cell.textContent = val;
                            }
                            break;

                        case 'price':
                            cell.className = 'prolijo-precio-col';
                            cell.textContent = val;
                            break;

                        default:
                            cell.textContent = val;
                            break;
                    }

                    tr.appendChild(cell);
                });

                fragment.appendChild(tr);
            });

            tbody.appendChild(fragment);

            const showingTxt = texts.showing || 'Mostrando';
            const ofTxt = texts.of || 'de';
            const pageTxt = texts.page || 'Página';

            info.textContent = `${showingTxt} ${start + 1}–${end} ${ofTxt} ${total} ${itemName} (${pageTxt} ${currentPage} ${ofTxt} ${totalPages})`;
            prevButton.hidden = currentPage <= 1;
            nextButton.hidden = currentPage >= totalPages;
        }

        function aplicarFiltros() {
            const query = (searchInput && enableSearch) ? normalizar(searchInput.value) : '';
            const words = query.split(/\s+/).filter(Boolean);
            const chosenCategory = (categorySelect && enableCategories) ? categorySelect.value : '';

            filteredItems = allItems.filter(function (item) {
                // Filtro por categoría (si está activo)
                if (enableCategories && chosenCategory && item.category !== chosenCategory) {
                    return false;
                }

                // Filtro predictivo por palabras en las columnas buscables (si está activo)
                if (enableSearch && words.length > 0) {
                    return words.every(function (word) {
                        return item.searchableText.includes(word);
                    });
                }

                return true;
            });

            dibujarPagina();
        }

        async function cargarPestana(tabId, forceRefresh) {
            activeTab = tabId;
            const requestId = ++requestNumber;

            // Actualizar estilo visual de los botones de pestañas
            tabButtons.forEach(function (button) {
                const btnTab = button.dataset.tab || button.dataset.provider;
                const isActive = (btnTab === tabId);
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            // Encontrar nombre para mostrar de la pestaña activa
            const activeBtn = tabButtons.find(function (b) {
                return (b.dataset.tab || b.dataset.provider) === tabId;
            });
            const tabName = activeBtn ? activeBtn.textContent.trim() : tabId.toUpperCase();

            // Si está en la memoria del navegador y no se fuerza refresco
            if (!forceRefresh && memoryCache[tabId]) {
                procesarFilas(memoryCache[tabId]);
                return;
            }

            loader.classList.remove('is-error');
            loader.replaceChildren(document.createTextNode('⏳ ' + (texts.loading || 'Cargando datos...') + ' (' + tabName + ')'));
            loader.hidden = false;
            container.hidden = true;

            const controller = new AbortController();
            const timer = window.setTimeout(function () {
                controller.abort();
            }, 25000);

            try {
                const url = new URL(ajaxUrl, window.location.href);
                url.searchParams.set('action', 'prolijo_sheets_get_data');
                url.searchParams.set('hoja', tabId);

                const response = await fetch(url.toString(), {
                    credentials: 'same-origin',
                    signal: controller.signal
                });

                const payload = await response.json();

                if (requestId !== requestNumber) {
                    return; // Otra solicitud más reciente fue disparada
                }

                if (!response.ok || !payload || payload.success !== true || !payload.data || !Array.isArray(payload.data.rows)) {
                    const msg = (payload && payload.data && payload.data.message) ? payload.data.message : 'No se pudieron obtener los datos.';
                    throw new Error(msg);
                }

                // Guardar en memoria y procesar
                memoryCache[tabId] = payload.data.rows;
                procesarFilas(payload.data.rows);

            } catch (error) {
                if (requestId !== requestNumber) {
                    return;
                }
                if (error && error.name === 'AbortError') {
                    mostrarError(texts.error_timeout || 'La consulta tardó más de 25 segundos y se canceló. Por favor, reintentá.');
                } else {
                    mostrarError(error && error.message ? error.message : 'Error al cargar los datos.');
                }
            } finally {
                window.clearTimeout(timer);
            }
        }

        // Listeners de eventos protegidos
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentPage = 1;
                aplicarFiltros();
            });
        }

        if (categorySelect) {
            categorySelect.addEventListener('change', function () {
                currentPage = 1;
                aplicarFiltros();
            });
        }

        prevButton.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                dibujarPagina();
                root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        nextButton.addEventListener('click', function () {
            const totalPages = Math.ceil(filteredItems.length / pageSize);
            if (currentPage < totalPages) {
                currentPage++;
                dibujarPagina();
                root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const tab = button.dataset.tab || button.dataset.provider;
                if (tab) {
                    cargarPestana(tab, false);
                }
            });
        });

        // Cargar pestaña inicial
        cargarPestana(activeTab, false);
    }

    // Inicializar todos los visores en el DOM
    function initAllSheets() {
        const roots = document.querySelectorAll('.prolijo-catalogo');
        roots.forEach(initCatalogo);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllSheets);
    } else {
        initAllSheets();
    }
})();
