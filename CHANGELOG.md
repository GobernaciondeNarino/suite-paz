# Changelog
Todas las versiones del plugin Suite PAZ.

## [0.6.0] — 2026-07-06
### Added
- `includes/class-spz-modules.php`: `SPZ_Modules` con `types():array` → `['kpi','compare','timeline','logro']` e `is_valid(string):bool`.
- `assets/js/modules.js`: `SPZ.modules.render(el, payload)` — dispatcher para kpi (count-up + delta, bajar=verde), compare (antes→después + %), timeline (hitos con `<ol>`), logro (tarjeta). Respeta `prefers-reduced-motion`. Verbatim del brief.
- `includes/class-spz-shortcode.php`: shortcodes `[spz_kpi id seccion]`, `[spz_compare id seccion]`, `[spz_timeline id seccion]`, `[spz_logro id seccion]` — emiten `.spz-module[data-modulo][data-id][data-seccion]`; verifican que la vista exista y sea del tipo correcto via data-provider. `[spz_seccion id]` lista todos los módulos/vistas de la sección en orden (módulos como `.spz-module`, vistas como `.spz-chart` con el primer tipo compatible). `maybe_enqueue_assets()` ahora encola también `spz-modules`.
- `assets/js/frontend.js`: DOMContentLoaded escanea también `.spz-module[data-modulo][data-id][data-seccion]`; `initModule(el)` fetches via `data-spz-src` (harness) o REST `/render?seccion&view` (WP) y llama `SPZ.modules.render(el, payload)`.
- `assets/css/frontend.css`: estilos de módulos — `.spz-kpi__v{font-size:clamp(2rem,5vw,3.4rem);font-weight:800;color:#5B3B8C}`, `.spz-delta.good{color:#2FA87A}`, `.spz-delta.bad{color:#E63946}`, `.spz-compare`, `.spz-timeline`, `.spz-logro` con paleta de marca. `.spz-seccion` como columna de módulos.
- `includes/class-spz-plugin.php`: `$this->modules = new SPZ_Modules()` en `__construct()`; `spz-modules` registrado en `enqueue_public_assets()`; pasado a `SPZ_Shortcode`.
- `includes/class-spz-rest-api.php`: `type` ahora es opcional en `/render`; cuando la vista tiene `is_module=true` retorna el JSON raw del módulo directamente (sin validación de tipo de gráfico).
- `tests/harness.html`: Bloques 3–6 para kpi/compare/timeline/logro cargando los JSONs reales de `dni/`; assertions Playwright: `.spz-kpi__v` contiene número (count-up corrió), `.spz-timeline li` count > 0, `.spz-delta.good` visible.

## [0.5.1] — 2026-07-06
### Fixed
- `includes/class-spz-rest-api.php`: nodos network/rings ahora preservan todos los atributos del row (label, group, value, etc.); garantiza clave `id` sin descartar el resto.
- `includes/class-spz-plugin.php`: `topojsonUrl` y `pluginUrl` envueltos con `esc_url_raw()` para consistencia con `restUrl`.
- `includes/class-spz-rest-api.php`: argumento `type` del endpoint `/render` incluye `validate_callback` que rechaza tipos inválidos con 400 en la capa de enrutamiento.
- `includes/class-spz-plugin.php`: docblock de `enqueue_public_assets()` corregido de "Tarea 6" a "Task 5".

## [0.5.0] — 2026-07-06
### Added
- `includes/class-spz-shortcode.php`: `[spz_grafico view type seccion height title theme]` — emite `<div class="spz-chart" data-view data-type data-seccion data-height style="min-height:...px">` sin datos inline; encola `spz-frontend` + `spz-frontend` CSS de forma lazy en `wp_footer`. Atributos sanitizados con `sanitize_slug`/`sanitize_key`/`absint`/`sanitize_text_field`/`sanitize_html_class`; `type` validado contra `SPZ_Chart_Types::is_valid_type`.
- `includes/class-spz-rest-api.php`: `GET /suite-paz/v1/render?seccion&view&type` → `{chart, view, data, mapping, compatible, seccion}` (público con whitelist interna); `GET /suite-paz/v1/views?seccion` → lista (admin). Usa `compatible_for()` (no `compatible_with_view`). Geomap mapping usa `SPZ_PLUGIN_URL` para topojson.
- `includes/class-spz-plugin.php`: instancia `SPZ_Shortcode` y `SPZ_Rest_Api` en `__construct()`; `run()` registra shortcode + `rest_api_init`; `enqueue_public_assets()` registra `spz-d3plus` (handle correcto), `spz-renderer`, `spz-frontend`, `spz-frontend` CSS y `wp_localize_script('spz-frontend','SPZ_FRONTEND',{restUrl, nonce, topojsonUrl, pluginUrl, i18n})`.
### Changed
- `suite-paz.php`: versión `0.4.1` → `0.5.0`.

## [0.4.1] — 2026-07-07
### Fixed
- `assets/js/renderer.js`: regex normalización robusta — reemplaza combinadores literales invisibles por escapes Unicode explícitos (`/[̀-ͯ]/g`), funcionalmente idéntico pero seguro ante re-encodings.
- `assets/js/frontend.js`: selector incluye `[data-seccion]` para evitar hidratar elementos de terceros que coincidan con `.spz-chart[data-view][data-type]`.
- `assets/css/frontend.css`: añade `min-height: 400px` a `.spz-chart` para que el div de shortcode no colapse (~24px) antes del render.
- `assets/js/renderer.js` (stacked_area): verificado via Playwright — `window.d3plus.StackedArea` es función en v3.1.4; no se requiere cambio (`StackedArea` permanece).

## [0.4.0] — 2026-07-06
### Added
- `assets/js/renderer.js`: `SPZ.renderer.render(el, {view, type, options})` — renderer d3plus v3 adaptado de tic-suite. Acepta payloads REST pre-construidos o el JSON semilla PAZ crudo (normalización interna: extrae filas de `municipios|datos|data`, infiere dims/measures, construye mapping). CLASS_MAP con los 15 tipos verificados contra el bundle v3.1.4 (`BarChart`, `LinePlot`, `AreaPlot`, `StackedArea`, `Pie`, `Donut`, `Treemap`, `Geomap`, `Network`, `Tree`, `Sankey`, `Rings`, `BoxWhisker`, `Priestley`). Geomap: `viz.tiles(false)` + `viz.ocean('transparent')` (sin basemap), join `_municipio_id` por NFD-normalize, `topojsonId('id')` (key `properties.id` del topojson). Topojson URL leída de `SPZ.config.topojsonUrl` (default relativo para harness; la Task 5 inyecta la URL del plugin WP). Paleta de marca SPZ.
- `assets/js/frontend.js`: Hidratador WP — localiza `.spz-chart[data-view][data-type][data-seccion]`, llama REST `suite-paz/v1/render` con nonce, renderiza via `SPZ.renderer`. Soporte `data-spz-src` para modo standalone (harness).
- `assets/css/frontend.css`: Estilos `.spz-*` con paleta de marca (violeta `#5B3B8C`, teal `#3FCF97`, coral `#E63946`, ámbar `#F4A93C`). Adaptado de tic-suite (`tsg-` → `spz-`).
- `tests/harness.html`: Harness de validación Playwright — renderiza geomap (homicidios-municipio, 64 municipios, Samaniego 47.6 tasa más intensa) + bar chart (fuerza-publica). Servir desde la raíz del plugin: `python -m http.server 8770`, abrir `http://localhost:8770/tests/harness.html`.
### Changed
- `suite-paz.php`: versión `0.3.1` → `0.4.0`.

## [0.3.1] — 2026-07-06
### Fixed
- `class-spz-chart-types.php`: sankey ahora requiere `edges => true`; sin aristas no se ofrece para datos categóricos planos.
- `class-spz-data-provider.php` + `class-spz-security.php`: endurecer guard anti-traversal — se exige `DIRECTORY_SEPARATOR` tras la base para rechazar directorios hermanos (e.g. `.../viewsX/`).
- `class-spz-chart-types.php`: priestley incluye categoría `temporal`; stacked_bar documenta que el renderer debe llamar `.stacked(true)`.

## [0.3.0] — 2026-07-06
### Added
- `includes/class-spz-data-provider.php`: `SPZ_Data_Provider(SPZ_Security, string $seccion)` con `list_views()`, `get_view(string):?array`, `get_raw(string):?array`. Caché en memoria `<seccion>:<key>`. Detecta módulos (`modulo` key) y los devuelve con `is_module=true` sin inferir dims/measures. Para vistas PAZ respeta el campo `categoria` explícito; inferencia de dims/measures usa `is_int`/`is_float` (no `is_numeric`) para no promover DIVIPOLA strings a medidas.
- `includes/class-spz-chart-types.php`: `SPZ_Chart_Types` con 15 tipos d3plus (`bar`, `stacked_bar`, `line`, `area`, `stacked_area`, `pie`, `donut`, `treemap`, `geomap`, `network`, `tree`, `sankey`, `rings`, `box_whisker`, `priestley`). `compatible_for(array $view)` retorna `[]` para módulos y para `tipo_grafico_sugerido` no estándar (e.g. "strategy", "radial"). `is_valid_type(string):bool` y `all_for_js():array`.
- `includes/class-spz-plugin.php`: `$this->chart_types` (`SPZ_Chart_Types`) + `$this->data_providers[]` (un `SPZ_Data_Provider` por sección). Método público `data_provider(?string $seccion=null):SPZ_Data_Provider`.

## [0.2.1] — 2026-07-06
### Added
- `data/views/dni/minas-interanual.json`: compare 2024→2025 personas afectadas por minas antipersonal (26→6, −76.9%).
- `data/views/dni/minas-narino-parcial.json`: compare 2025→2026 personas afectadas por minas (6→6, 0%).
- `data/views/dni/coordinadora-desplazamiento.json`: compare desplazamiento CNEB Ene-Jun 2025→2026 (1036→329, −68.3%).
- `data/views/dni/coordinadora-confinamiento.json`: compare confinamiento CGSB Ene-Jun 2025→2026 (142→0, −100%).
- `data/views/dni/rutas-nna.json`: kpi rutas de prevención NNA (7 casos Ene–Jun 2026; serie 2023–2025: 25/17/7).

## [0.2.0] — 2026-07-06
### Added
- `scripts/paz_catalog.py`: catálogo completo de cifras verbatim de las 37 slides (desplazamiento, confinamiento, CNEB, reclutamiento NNA, minas, firmantes, UBPD, timelines, estructuras armadas, homicidios municipio/histórico, terrorismo, fuerza pública, convivencia, hurtos, hallazgos, subsecretaría, Nariño 360, IPM, indicadores sociales, PIB).
- `scripts/build-views.py`: generador de 29 archivos JSON (14 DNI, 6 seguridad, 3 convivencia, 2 estrategia, 4 transformaciones); geomaps expandidos a 64 municipios con datos reales.
- `scripts/validate-views.py`: validador de esquema (vistas y módulos); reporta `VIEWS OK: 29 archivos válidos`.
- `data/views/<seccion>/*.json`: semilla de datos de las 5 secciones (29 archivos).

## [0.1.0] — 2026-07-06
### Added
- Scaffold del plugin: main file, singleton `SPZ_Plugin` con 5 secciones, `SPZ_Security`, uninstall, topojson de los 64 municipios.
