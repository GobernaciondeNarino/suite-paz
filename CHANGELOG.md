# Changelog
Todas las versiones del plugin Suite PAZ.

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
