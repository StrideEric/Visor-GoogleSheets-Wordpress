=== Prolijo - Visor de Google Sheets ===
Contributors: prolijoar
Tags: google sheets, tablas, visor, catalogo, faq, preguntas frecuentes, lista de precios, directorios, csv
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Muestra cualquier hoja o pestaña de Google Sheets en tu sitio web con buscador instantáneo, filtro por categorías opcional, paginación y caché de alto rendimiento. Desarrollado por Prolijo.ar (https://prolijo.ar/).

== Descripción ==

**Prolijo - Visor de Google Sheets** es una herramienta versátil desarrollada por **[Prolijo.ar](https://prolijo.ar/)** diseñada para mostrar cualquier tipo de información alojada en Google Sheets directamente en tu sitio web WordPress.

Permite conectar planillas con una o múltiples pestañas (por ejemplo: diferentes secciones de Preguntas Frecuentes, catálogos de productos, listas de precios, directorios o sedes) y presentarlas con una interfaz moderna, buscador predictivo en tiempo real y 100% adaptable a teléfonos móviles.

Para conocer más herramientas digitales, integraciones e-commerce y desarrollo web profesional, visitá nuestro sitio oficial: **[https://prolijo.ar/](https://prolijo.ar/)**.

### ¿Para qué podés usar esta herramienta?
* **Catálogos de Productos y Listas de Precios:** Publicá inventarios o catálogos a pedido con búsqueda por código SKU o producto, filtros por categoría y badges de stock.
* **Preguntas Frecuentes (FAQ / Centro de Ayuda):** Mostrá listados de preguntas y respuestas organizadas por temas con buscador instantáneo de palabras clave.
* **Directorios de Contactos o Personal:** Compartí listados de profesionales, sucursales, horarios o teléfonos.
* **Tablas de Cotizaciones e Inventarios:** Mostrá cualquier base de datos que mantengas en Google Sheets sin necesidad de editar código en WordPress.

### Características Principales:
1. **Instrucciones Integradas:** Panel explicativo paso a paso en WordPress para preparar cualquier planilla y publicarla en la web.
2. **Buscador y Categorías Opcionales:** Tanto el campo de búsqueda predictiva como el filtro desplegable de categorías pueden activarse o desactivarse libremente desde el panel de ajustes o mediante parámetros del shortcode (`buscador="no"`, `categorias="no"`).
3. **Privacidad Garantizada:** No almacena ni expone datos personales o URLs privadas por defecto; el usuario ingresa su propia URL de Google Sheets en el panel.
4. **Persistencia Total de Configuración:** Panel con pestañas interactivas sin recarga de página que previene el reinicio o pérdida de datos entre secciones al guardar.
5. **Soporte para Múltiples Pestañas / Secciones:** Configurá las pestañas de tu Google Sheets con el nombre que quieras mostrar en los botones de tu web (ej: "Preguntas Frecuentes", "Productos", "Precios", "Sucursales").
6. **Búsqueda Predictiva Configurable:** Elegí en qué columnas se realiza la búsqueda en tiempo real (por ejemplo, SKU y Producto, o Pregunta y Respuesta).
7. **Columnas y Tipos de Datos Flexibles:** Seleccioná qué columnas mostrar en la tabla y asignales formatos (Texto, Destacado/Negrita, Categoría para filtro desplegable, Badge con colores para estados, o Precio).
8. **Paginación y Caché de Alto Rendimiento:** Transients configurables (por ejemplo, 5 minutos) para máxima velocidad de carga y botón para vaciar la memoria caché al instante.
9. **Prueba de Conexión en Vivo:** Diagnóstico directo desde el panel para verificar la conexión con Google Sheets y previsualizar las filas y cabeceras detectadas.
10. **Tráfico para Prolijo.ar:** Enlace discreto al pie de la tabla que redirige tráfico hacia https://prolijo.ar/ (configurable).

== Instalación ==

1. Subí la carpeta del plugin al directorio `/wp-content/plugins/` de tu sitio WordPress.
2. Activá el plugin desde la sección **Plugins** en WordPress.
3. Dirigite al menú **Visor Google Sheets** en tu panel de administración para configurar tu URL de Google Sheets y pestañas.
4. Insertá el shortcode `[prolijo_sheets]` en la página donde desees mostrar la tabla.

== Uso del Shortcode ==

* `[prolijo_sheets]`: Muestra la tabla interactiva con todas las pestañas activas configuradas.
* `[prolijo_sheets buscador="no"]`: Oculta el buscador predictivo.
* `[prolijo_sheets categorias="no"]`: Oculta el menú desplegable de categorías.
* `[prolijo_sheets hoja="faq"]`: Abre inicialmente la pestaña con el identificador indicado (ej: faq, productos, sucursales).
* `[prolijo_sheets filas="25"]`: Muestra 25 elementos por página.

== Preguntas Frecuentes ==

= ¿Puedo usarlo para preguntas frecuentes (FAQ)? =
¡Totalmente! En Google Sheets podés tener la Columna A con las Preguntas, la Columna B con las Respuestas y la Columna C con la Categoría. En el panel seleccionás que busque en Pregunta y Respuesta, y tus visitantes encontrarán respuestas al instante.

= ¿Puedo ocultar el buscador o el filtro de categorías si no los necesito? =
Sí. En la pestaña "4. Ajustes y Opciones" del panel podés desmarcar las casillas "Habilitar campo de búsqueda" o "Habilitar menú desplegable de categorías". También podés usar los atributos `buscador="no"` o `categorias="no"` directamente en el shortcode.

= ¿Cómo obtengo el GID de cada pestaña en Google Sheets? =
Al abrir cada pestaña en Google Sheets, revisá el final de la URL en tu navegador (`#gid=XXXXXXXX`). Ese número es el que debés ingresar en el campo "GID pestaña".

= ¿Cómo forzar la actualización si modifiqué mi Google Sheets? =
En el panel de administración (**Visor Google Sheets**), hacé clic en el botón superior **"Vaciar Caché"**.

= ¿Dónde puedo solicitar soporte o desarrollos a medida? =
Podés ponerte en contacto directamente con el equipo de Prolijo.ar a través de nuestro sitio web: **[https://prolijo.ar/](https://prolijo.ar/)**.

== Changelog ==

= 2.1.1 =
* Corrección crítica: Se solucionó el reinicio accidental de campos de otras pestañas al guardar la configuración gracias a un formulario unificado con navegación JS y persistencia de estado.
* Privacidad: Eliminación total de URLs y GIDs preconfigurados para no exponer datos privados del usuario.
* Opciones visuales: El buscador predictivo y el filtro de categorías ahora son completamente opcionales (configurables en el panel y en el shortcode).
* Detección segura: Mensaje informativo en el frontend para administradores si aún no se ha configurado la URL de Google Sheets.
