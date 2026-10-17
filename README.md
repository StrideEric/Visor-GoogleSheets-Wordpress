# Prolijo - Visor de Google Sheets para WordPress

[![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](LICENSE)
[![Desarrollado por](https://img.shields.io/badge/Desarrollado%20por-Prolijo.ar-0f172a.svg)](https://prolijo.ar/)

**Prolijo - Visor de Google Sheets** es una herramienta potente, ligera y versátil para WordPress desarrollada por **[Prolijo.ar](https://prolijo.ar/)**. Permite sincronizar y mostrar en tiempo real cualquier hoja o pestaña de **Google Sheets** directamente en tu sitio web, con buscador predictivo instantáneo, filtro por categorías, paginación rápida y diseño completamente responsivo para dispositivos móviles.

---

## 🎯 ¿Para qué sirve?

El plugin está diseñado para ser una solución universal, adaptable a cualquier tipo de información organizada en Google Sheets:

* **📦 Catálogos de Productos y Listas de Precios:** Muestra listas de precios actualizadas, productos a pedido, códigos SKU, disponibilidad de stock con badges de colores y precios de venta.
* **❓ Preguntas Frecuentes (FAQ / Centro de Ayuda):** Publica una base de conocimiento con preguntas y respuestas donde tus usuarios pueden buscar al instante por palabras clave o filtrar por temática.
* **👥 Directorios y Contactos:** Listados de sucursales, horarios de atención, profesionales, sedes o personal clasificados por sección.
* **📊 Tablas de Cotizaciones e Inventarios:** Cualquier base de datos en Google Sheets que desees mantener actualizada en la nube y reflejar automáticamente en WordPress sin editar código.

---

## ✨ Características Principales

1. **Buscador Predictivo Opcional:** Filtra filas al instante mientras el usuario escribe. Podés configurar en qué columnas buscar (ej: SKU y Producto, o Pregunta y Respuesta) y activarlo u ocultarlo a voluntad.
2. **Filtro de Categorías Opcional:** Genera automáticamente un menú desplegable a partir de los valores de la columna que definas como categoría. Puede ocultarse si no lo necesitás.
3. **Soporte para Múltiples Pestañas / Secciones:** Conectá tantas pestañas de tu Google Sheets como desees (por GID) y definí el nombre personalizado que quieras que aparezca en los botones de tu web (ej: *"Productos"*, *"Preguntas Frecuentes"*, *"Precios Mayoristas"*).
4. **Columnas y Tipos de Datos Configurables:**
   - **Texto estándar:** Para respuestas, descripciones o teléfonos.
   - **Código / Destacado (Negrita):** Para SKUs, preguntas o títulos.
   - **Categoría:** Genera el selector desplegable automático.
   - **Estado / Badge con Color:** Aplica etiquetas inteligentes (verde para disponible/activo, amarillo para consultar/alerta) según palabras clave configurables.
   - **Precio / Valor:** Formato resaltado para precios o valores monetarios.
5. **Caché Optimizado con Transients:** Guarda las consultas en la memoria del servidor de WordPress durante el tiempo que indiques (por defecto 5 minutos) para que tu sitio cargue a máxima velocidad sin saturar Google Sheets.
6. **Herramientas de Diagnóstico Integradas:**
   - **Vaciar Caché:** Botón directo en el panel para forzar la actualización inmediata tras modificar precios o datos en Google Sheets.
   - **Probar Conexión:** Validador en vivo para comprobar la respuesta de Google Sheets, tiempo de respuesta y cantidad de filas detectadas.
7. **Privacidad Garantizada:** La URL de Google Sheets viene vacía por defecto. No almacena ni expone datos privados.
8. **Persistencia Total:** Panel interactivo sin recarga de página que evita la pérdida o reinicio de datos entre pestañas al guardar.

---

## 🚀 Guía de Instalación y Uso

### 1. Instalación en WordPress
1. Descargá o cloná este repositorio en la carpeta de plugins de tu sitio:
   ```bash
   wp-content/plugins/prolijo-sheets-viewer/
   ```
2. Accedé a tu panel de WordPress &rarr; **Plugins** y activá **Prolijo - Visor de Google Sheets**.

### 2. Preparar tu Google Sheets
1. Creá tu planilla en Google Sheets con los encabezados en la **Fila 1**.
2. Andá a **Archivo** &rarr; **Compartir** &rarr; **Publicar en la web**.
3. Seleccioná *"Todo el documento"* (o la pestaña deseada) y elegí formato **Valores separados por comas (.csv)**.
4. Hacé clic en **Publicar** y copiá el enlace generado.
5. Si tenés varias pestañas, observá el número al final de la URL en tu navegador (`.../edit#gid=123456789`). Ese número es el **GID** de cada pestaña.

### 3. Configuración en WordPress
1. En el menú de administración, ingresá a **Visor Google Sheets**.
2. En la pestaña **2. Google Sheets y Pestañas**, pegá la URL publicada y agregá las pestañas con su GID y el nombre que quieras mostrar en los botones.
3. En la pestaña **3. Columnas y Búsqueda**, definí los nombres de tus columnas y tildá cuáles querés mostrar y cuáles deben participar de la búsqueda.
4. En la pestaña **4. Ajustes y Opciones**, podés activar/desactivar el buscador, el filtro de categorías y ajustar las filas por página.

---

## 📋 Uso del Shortcode

Insertá el shortcode en cualquier página, entrada o maquetador visual (Gutenberg, Elementor, Divi):

```text
[prolijo_sheets]
```

### Parámetros opcionales

| Parámetro | Ejemplo | Descripción |
| :--- | :--- | :--- |
| `hoja` | `[prolijo_sheets hoja="faq"]` | Abre inicialmente la pestaña con ese identificador. |
| `buscador` | `[prolijo_sheets buscador="no"]` | Oculta la barra de búsqueda para esa tabla específica. |
| `categorias` | `[prolijo_sheets categorias="no"]` | Oculta el menú desplegable de categorías. |
| `filas` | `[prolijo_sheets filas="25"]` | Sobrescribe la cantidad de filas por página (10, 25, 50, 100). |

#### Ejemplo de tabla limpia (sin buscador ni categorías):
```text
[prolijo_sheets buscador="no" categorias="no"]
```

---

## 📁 Estructura del Código

```text
prolijo-sheets-viewer/
├── prolijo-sheets-viewer.php      # Archivo principal de inicialización y constantes
├── readme.txt                     # Documentación para el repositorio de WordPress
├── README.md                      # Documentación completa para GitHub
├── .gitignore                     # Exclusiones de Git
├── assets/
│   ├── css/
│   │   ├── admin.css              # Estilos del panel de control
│   │   └── frontend.css           # Estilos responsivos de la tabla, filtros y badges
│   └── js/
│       ├── admin.js               # Interactividad del panel, AJAX y persistencia
│       └── frontend.js            # Motor del catálogo, filtros predictivos y caché local
└── includes/
    ├── class-settings.php         # Gestión de opciones, pestañas del panel y sanitización
    ├── class-sheets-client.php    # Cliente de conexión remota con Google Sheets y transients
    ├── class-ajax-handler.php     # Endpoints AJAX públicos y administrativos
    └── class-shortcode.php        # Renderizado de shortcodes y parámetros
```

---

## 👨‍💻 Desarrollado por Prolijo.ar

Este plugin ha sido desarrollado con los más altos estándares de calidad y rendimiento por el equipo de **Prolijo.ar**.

* **Sitio Web Oficial:** [https://prolijo.ar/](https://prolijo.ar/)
* **Servicios:** Desarrollo de plugins y temas para WordPress, integración de APIs y Google Sheets con WooCommerce, automatizaciones y tiendas online de alto rendimiento.
* **Contacto y Soporte:** Visitá [prolijo.ar](https://prolijo.ar/) para solicitar integraciones a medida o soporte especializado.

---

*Licencia: GPL-2.0+ &copy; [Prolijo.ar](https://prolijo.ar/)*
