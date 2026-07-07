=== Suite PAZ ===
Contributors: gobernaciondenarino
Tags: charts, maps, d3plus, data-visualization, paz, narino
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publica los datos del proyecto de Paz de Nariño como gráficos, mapas coropléticos y módulos mediante shortcodes, con editor de datos en BD.

== Description ==

Suite PAZ es un plugin de WordPress para la Gobernación de Nariño que expone los datos del proyecto "Así llegó la paz a Nariño" como visualizaciones interactivas insertables en cualquier página mediante shortcodes.

**Características principales:**

* 34 vistas de datos organizadas en 5 secciones temáticas (DNI/Paz, Seguridad, Convivencia, Estrategia, Transformaciones).
* 15 tipos de gráfico d3plus v3: bar, stacked_bar, line, area, stacked_area, pie, donut, treemap, geomap (mapa coroplético con los 64 municipios de Nariño), network, tree, sankey, rings, box_whisker, priestley.
* 4 tipos de módulo nativo: KPI (count-up animado + delta), Compare (antes/después + %), Timeline (hitos), Logro (tarjeta destacada).
* Editor de datos en el panel de administración: editar, guardar, exportar y restablecer cualquier vista directamente en la BD de WordPress.
* Shortcode `[spz_seccion id]` para insertar toda una sección con un solo tag.
* Seguridad WordPress-native: capability manage_options, nonces en REST, sanitización, whitelist de parámetros, anti-traversal, escapado de salida.
* Sin datos inline en el HTML del shortcode: los datos se cargan por REST (cache-safe).
* Requiere conexión a Internet en el frontend (d3plus se carga desde CDN de jsDelivr).

**Shortcodes disponibles:**

`[spz_grafico view="slug" type="geomap" seccion="seguridad" height="450"]`
`[spz_kpi id="slug" seccion="dni"]`
`[spz_compare id="slug" seccion="dni"]`
`[spz_timeline id="slug" seccion="dni"]`
`[spz_logro id="slug" seccion="dni"]`
`[spz_seccion id="seguridad"]`

**Limitación conocida:** Las vistas `subsecretaria` y `narino-360` de la sección Estrategia usan tipos de visualización no estándar ("radial"/"strategy") y se maquetar manualmente. Sus datos JSON están disponibles y correctos.

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

== Changelog ==

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

= 1.0.0 =
Primera versión estable. Instalar desde cero o actualizar desde cualquier versión 0.x.
