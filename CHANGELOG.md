# Changelog
Todas las versiones del plugin Suite PAZ.

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
