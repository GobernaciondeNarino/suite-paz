=== Suite PAZ ===
Contributors: gobernaciondenarino
Tags: charts, maps, d3plus, data-visualization, paz, narino
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publica los datos del proyecto de Paz de Nariño como gráficos, mapas coropléticos y módulos mediante shortcodes, con editor de datos en BD.

== Description ==

Suite PAZ es un plugin de WordPress para la Gobernación de Nariño que expone los datos del proyecto "Así llegó la paz a Nariño" como visualizaciones interactivas insertables en cualquier página mediante shortcodes.

**Características principales:**

* 34 vistas de datos organizadas en 5 secciones temáticas (Diálogo, Negociación e Implementación, Seguridad, Convivencia, Estrategia, Transformaciones).
* 15 tipos de gráfico d3plus v3 + 1 tipo nativo `tabla` (HTML puro, sin CDN): bar, stacked_bar, line, area, stacked_area, pie, donut, treemap, geomap, network, tree, sankey, rings, box_whisker, priestley, tabla.
* 6 tipos de módulo nativo: KPI (count-up animado + delta), Compare (antes/después + %), Timeline (hitos), Logro (tarjeta destacada), Diagrama (nodo central + ramas para Subsecretaría), Estrategia (líneas de acción + chips para Nariño 360).
* Botón "Ver datos" en cada vista: panel colapsable con las filas de datos consumidas y la fuente.
* Shortcode `[spz_analisis id seccion]` para el análisis ciudadano (~594 caracteres) de cada vista, renderizado en servidor.
* Editor de datos en el panel de administración: editar, guardar, exportar y restablecer cualquier vista directamente en la BD de WordPress.
* Shortcode `[spz_seccion id]` para insertar toda una sección con un solo tag.
* Seguridad WordPress-native: capability manage_options, nonces en REST, sanitización, whitelist de parámetros, anti-traversal, escapado de salida.
* Sin datos inline en el HTML del shortcode: los datos se cargan por REST (cache-safe).
* Las vistas `tabla`, `diagrama` y `estrategia` no requieren conexión a Internet (sin d3plus). Los 15 tipos d3plus requieren CDN de jsDelivr.

**Shortcodes disponibles:**

`[spz_grafico view="slug" type="geomap" seccion="seguridad" height="450"]`
`[spz_grafico view="slug" type="tabla" seccion="seguridad"]`
`[spz_kpi id="slug" seccion="dni"]`
`[spz_compare id="slug" seccion="dni"]`
`[spz_timeline id="slug" seccion="dni"]`
`[spz_logro id="slug" seccion="dni"]`
`[spz_seccion id="seguridad"]`
`[spz_analisis id="slug" seccion="dni"]`

== Installation ==

1. Descargar el ZIP del repositorio GitHub o clonar en `wp-content/plugins/suite-paz/`.
2. En el panel de WordPress, ir a Plugins → Añadir nuevo → Subir plugin.
3. Seleccionar el ZIP e instalar.
4. Activar el plugin. Al activar, se crea la tabla `wp_spz_views` en la base de datos.
5. Insertar shortcodes en páginas o entradas. El menú "Suite PAZ" aparece en el panel lateral.

== Frequently Asked Questions ==

= ¿Requiere Internet en el frontend? =

Sí. La librería d3plus se carga desde `cdn.jsdelivr.net`. En entornos sin Internet los gráficos no se renderizan.

= ¿Dónde se guardan los cambios del editor de datos? =

En la tabla `wp_spz_views` de la base de datos de WordPress. Un override en BD siempre tiene prioridad sobre el JSON semilla del plugin.

= ¿Cómo vuelvo a los datos originales? =

Desde el editor de datos (Suite PAZ → Editar datos), selecciona la vista y haz clic en "Restablecer".

= ¿Qué capacidad de WordPress necesito para usar el editor de datos? =

`manage_options` (Administrador).

== Screenshots ==

1. Constructor de shortcodes — selección de vista, tipo y preview.
2. Mapa coroplético de homicidios por municipio (64 municipios de Nariño).
3. Módulo KPI con count-up animado y delta interanual.
4. Editor de datos — tabla editable con botones Guardar/Exportar/Restablecer.

= ¿Por qué una vista muestra solo el contenedor vacío? =

Verificar que el plugin está en la versión 1.1.7 o posterior. Antes de v1.1.0 el endpoint REST devolvía 409 para la mayoría de vistas por un problema de categorías. Las vistas `tabla`, `diagrama` y `estrategia` no requieren d3plus y siempre renderizan.

== Changelog ==

= 1.1.7 =
* Corrección: fuga de listener keydown en panel "Ver datos". aria-label sin doble-escape. Campo fuente incluido en metadata del panel.

= 1.1.6 =
* Botón "Ver datos" con panel colapsable en cada vista (gráficos, tablas, módulos). Accesibilidad: aria-expanded, Esc cierra y devuelve foco.

= 1.1.5 =
* Corrección: builder revela [spz_analisis] simétricamente al mostrar/ocultar el shortcode principal.

= 1.1.4 =
* Shortcode [spz_analisis id seccion]: análisis ciudadano (~594c) server-side. 34 análisis redactados. Integrado en Constructor y Galería.

= 1.1.3 =
* Escapado XSS en fallbacks de renderer.js y modules.js. Guards en diagrama.

= 1.1.2 =
* Módulos diagrama y estrategia para la sección Estrategia (subsecretaria y narino-360). Tipo nativo tabla con HTML/CSS puro.

= 1.1.1 =
* Tipo nativo tabla registrado en SPZ_Chart_Types; compatible con todas las categorías estándar; no requiere d3plus.

= 1.1.0 =
* FIX CRÍTICO: categorías de la semilla corregidas a estándar (categorical/geographic/social). Fin de los 409 en /render para las vistas DNI/seguridad/convivencia/transformaciones. subsecretaria → módulo diagrama; narino-360 → módulo estrategia; tipos tabla/list nativos.

= 1.0.0 =
* Versión estable inicial. Plugin feature-complete: 34 vistas de datos con datos reales de los 64 municipios, 15 tipos de gráfico d3plus, 4 tipos de módulo nativo (kpi/compare/timeline/logro), shortcodes, REST API, editor de datos en BD, panel de administración completo.

= 0.8.1 =
* Corrección: ruta REST GET /views/{slug} para el Constructor. Guard isNaN en editor.

= 0.8.0 =
* Panel de administración completo: Constructor, Shortcodes, Editor de datos, Ajustes.

= 0.7.0 =
* Data store en BD (wp_spz_views): save, reset, export con validación de payload.

= 0.6.0 =
* Módulos nativos: kpi, compare, timeline, logro. Shortcode [spz_seccion].

= 0.5.0 =
* Shortcode [spz_grafico]. REST API: /render, /views.

= 0.4.0 =
* Renderer d3plus v3 (15 tipos). Harness de validación Playwright.

= 0.3.0 =
* Data provider con anti-traversal y override BD.

= 0.2.0 =
* 34 vistas JSON con datos reales de los 64 municipios de Nariño.

= 0.1.0 =
* Scaffold inicial del plugin.

== Upgrade Notice ==

= 1.1.7 =
Actualización recomendada. Corrige vistas rotas (409 en /render), añade tabla nativa, módulos diagrama/estrategia, análisis ciudadano y botón "Ver datos".

= 1.0.0 =
Primera versión estable. Instalar desde cero o actualizar desde cualquier versión 0.x.
