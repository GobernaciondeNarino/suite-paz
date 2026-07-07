# suite-paz — Plugin WordPress de datos de Paz (Gobernación de Nariño) — Diseño

**Fecha:** 2026-07-06
**Repo destino:** https://github.com/GobernaciondeNarino/suite-paz (vacío, público, rama `main`)
**Base:** recreación fiel del plugin `tic-suite` (https://github.com/GobernaciondeNarino/tic-suite) adaptado al proyecto de PAZ.
**Fuente de datos:** `6Julio_Presentación PAZ_2026.pdf` (37 diapositivas) + `narino_municipios.topojson` (64 municipios).

---

## 1. Objetivo

Recrear el plugin de WordPress `tic-suite` como **`suite-paz`**, cambiando el contenido al proyecto de Paz de Nariño, de modo que la Gobernación pueda:
1. Insertar **gráficos, mapas coropléticos y módulos** de datos de paz en cualquier página/entrada de WordPress mediante **shortcodes**.
2. **Maquetar manualmente** las páginas combinando módulos y secciones.
3. **Editar y actualizar los datos manualmente** desde el admin (capacidad nueva respecto a tic-suite, que es solo lectura).

El plugin `tic-suite` produjo el sitio https://gobiernoabierto.narino.gov.co/datos-abiertos/secretaria-tic-innovacion-y-gobierno-abierto/; `suite-paz` debe habilitar un sitio equivalente para Paz.

## 2. Decisiones aprobadas

| Decisión | Elección |
|---|---|
| Stack | **WordPress plugin PHP + d3plus** (fiel a tic-suite) |
| Modelado de slides no tabulares | **Módulos PAZ nativos** (KPI, comparativa, timeline, logro) + gráficos d3plus |
| Persistencia del editor de datos | **Base de datos de WordPress** (JSON como semilla) |
| Entrega | **Push directo al repo `suite-paz`**, rama `actualizar-paz`, PR "actualizar-paz", README + CHANGELOG versionado |

## 3. Arquitectura (fiel a tic-suite, prefijo `spz`)

```
suite-paz/
  suite-paz.php                  → Headers del plugin + bootstrap (SemVer)
  uninstall.php                  → Limpieza de opciones/tabla sin tocar contenido del usuario
  includes/
    class-spz-plugin.php         → Singleton; whitelist de SECCIONES; cablea todo
    class-spz-security.php       → sanitización, nonces, capabilities, anti path-traversal
    class-spz-chart-types.php    → registro de tipos d3plus + compatibilidad por categoría
    class-spz-modules.php        → registro de módulos PAZ nativos (kpi, compare, timeline, logro)
    class-spz-data-provider.php  → lee override de BD si existe, si no el JSON semilla; caché por sección
    class-spz-data-store.php     → CRUD de overrides en BD (tabla wp_spz_views); seed/export/reset
    class-spz-shortcode.php      → shortcodes [spz_grafico], [spz_kpi], [spz_compare], [spz_timeline], [spz_logro], [spz_seccion]
    class-spz-rest-api.php       → /wp-json/suite-paz/v1 (render, data, save) con nonces + whitelist
    class-spz-admin.php          → menús y pantallas (Constructor, Shortcodes, Editar datos, Ajustes)
  templates/admin/
    builder.php                  → constructor por sección (Vista/Módulo → Tipo → preview + shortcode)
    shortcodes.php               → galería de shortcodes por sección
    data-editor.php              → EDITOR de datos (formulario/tabla editable) — NUEVO
    settings.php                 → ajustes (tema, reset)
  assets/
    js/renderer.js               → d3plus v3.1.4: mapea vista JSON → gráfico/mapa
    js/modules.js                → render de módulos PAZ nativos (KPI count-up, compare, timeline, logro)
    js/frontend.js               → carga shortcodes en el front, pide datos por REST con nonce
    js/admin.js                  → lógica del constructor + editor de datos
    css/frontend.css, css/admin.css → paleta de marca (violeta #5B3B8C / teal #3FCF97)
  data/
    topo/narino_municipios.topojson, .lookup.json  → 64 municipios (de tic-suite)
    views/<seccion>/*.json       → vistas y módulos semilla por sección
  scripts/
    build-views.py               → genera/regenera los JSON semilla desde el catálogo del PDF
  README.md, CHANGELOG.md
```

**Principio de aislamiento:** cada clase tiene una responsabilidad única; `data-provider` (lectura+caché) se separa de `data-store` (escritura en BD); `chart-types` (gráficos d3plus) se separa de `modules` (módulos PAZ). El renderer d3plus (`renderer.js`) no conoce los módulos y viceversa (`modules.js`).

## 4. Secciones (multi-proyecto, whitelist `SECCIONES`)

Las 6 agrupaciones temáticas del PDF, modeladas como los "proyectos" de tic-suite:

| slug | Nombre | Slides |
|---|---|---|
| `dni` | Diálogo, Negociación e Implementación | 4–18 (desplazamiento, confinamiento, NNA, minas, firmantes, desaparecidos, timelines) |
| `seguridad` | Seguridad Territorial | 20–26 (estructuras armadas, homicidios, ranking, terrorismo, fuerza pública) |
| `convivencia` | Convivencia Ciudadana | 27–29 (intrafamiliar, lesiones, feminicidio, hurtos, hallazgos) |
| `estrategia` | La Estrategia 2026 | 31–32 (Subsecretaría, Nariño 360) |
| `transformaciones` | Transformaciones Territoriales | 34–36 (IPM, indicadores sociales, desocupación, PIB) |

Las portadas (1,2,3,19,30,33,37) no son secciones de datos. Cada sección: carpeta `data/views/<slug>/`, caché aislado (prefijo `<slug>:`), path aislado (protegido contra traversal).

## 5. Datos: 37 slides → vistas y módulos (item 1)

Se revisa cada slide y se genera un JSON por unidad de contenido. Dos esquemas:

**(a) Vistas de gráfico/mapa** (formato de publicación tic-suite, renderer intacto):
```json
{ "vista": "homicidios-municipio-2025", "titulo": "...", "descripcion": "...",
  "tipo_grafico_sugerido": "geomap", "total_municipios": 64,
  "municipios": [ { "municipio": "SAMANIEGO", "tasa_2025": 47.6, "casos_2025": 14, "pdet": true }, ... ] }
```
Categorías: `categorical`, `temporal`, `geographic`, `hierarchical`, `network`, `statistical`.

**Mapas CON DATOS reales** (corrige el defecto de "mapas sin datos"):
- `desminado-municipios` (Samaniego, Mallama, La Llanada, Cumbal, San Pablo, Santacruz).
- `homicidios-municipio` (Barbacoas 17.1, El Charco 13.4, Pasto 9.7, Tumaco 14.6, Samaniego 47.6 — tasa 2025; series 2023–25).
- `desaparecidos-cuerpos` (Cumbal 13, Santacruz 12, Samaniego 12, La Llanada 2).
- `estructuras-armadas` (zonas de influencia por grupo).

**Gráficos/temporales**: desplazamiento (25.344→17.441→4.227), confinamiento, histórico homicidios por gobierno, IPM 2024–25, PIB por sector, terrorismo nacional vs Nariño, etc.

**(b) Módulos PAZ** (esquema propio):
```json
{ "modulo": "kpi", "id": "firmantes-100", "titulo": "Homicidios de firmantes de paz",
  "valor": 100, "unidad": "%", "leyenda": "Reducción", "serie": [{"y":"2023","v":2},{"y":"2024","v":0}] }
```
Tipos de módulo: `kpi`, `compare` (antes→después+delta), `timeline` (hitos), `logro` (cita/tarjeta).

**Todas las cifras** provienen del catálogo del PDF (documentado en el spec del proyecto anterior; verbatim, sin alterar). Un `scripts/build-views.py` genera los JSON semilla de forma reproducible.

## 6. Módulos PAZ nativos (item 3)

Renderizados por `modules.js` (no d3plus), cada uno con shortcode:
- `[spz_kpi id="firmantes-100" project="dni"]` — cifra grande + count-up + delta con color (bajar = positivo).
- `[spz_compare id="desplazamiento-anual" project="dni"]` — antes → después + % de reducción.
- `[spz_timeline id="acuerdos-fcs" project="dni"]` — hitos de acuerdos.
- `[spz_logro id="san-pablo-libre-minas" project="dni"]` — tarjeta de logro/cita.
Los gráficos y mapas van por `[spz_grafico view="..." type="..." project="..."]` (15 tipos d3plus).
Shortcode de sección opcional: `[spz_seccion id="seguridad"]` arma un bloque temático con sus módulos/vistas en orden.

## 7. Editor de datos manual (item 4 — nuevo)

Pantalla admin **"Editar datos"** (`templates/admin/data-editor.php` + `admin.js` + `class-spz-data-store.php`):
- Selector **sección → vista/módulo** → formulario/tabla editable de sus filas y campos.
- **Guardar** escribe un *override* en la BD (`wp_spz_views`, columnas: `seccion`, `slug`, `payload` JSON, `updated_at`).
- **Data-provider** resuelve: si hay override en BD → lo usa; si no → lee el JSON semilla del disco. Así las ediciones sobreviven a actualizaciones/reinstalación del plugin.
- Botones: **Guardar**, **Exportar JSON** (descarga el estado actual), **Restablecer al original** (borra el override → vuelve al semilla).
- **Seguridad**: capability `manage_options`, nonce por operación, sanitización profunda de cada campo (claves con `sanitize_key`, textos con `sanitize_text_field`, números con validación de tipo), whitelist de sección/slug. La escritura valida el esquema antes de persistir.

## 8. Renderer y compatibilidad

- `renderer.js` reutiliza la lógica d3plus v3.1.4 de tic-suite: carga `@d3plus/core@3.1.4` (bundle `full`) desde CDN, infiere dimensiones/medidas de la primera fila, normaliza `municipio` → `_municipio_id` para el join con el topojson, tooltips en es-CO, leyenda al bottom, geomap sin basemap/ocean transparente, filtrado de municipios sin datos (excepto mapa/grafos).
- `chart-types.js` registra los 15 tipos y su compatibilidad por categoría (igual que tic-suite).
- `modules.js` es independiente: recibe el payload del módulo por REST y pinta el KPI/compare/timeline/logro con la paleta de marca y animación (count-up), respetando `prefers-reduced-motion`.

## 9. Seguridad (fiel a tic-suite)

Capability `manage_options` para todo el admin; nonces `X-WP-Nonce` en cada REST; `sanitize_key`/`sanitize_text_field`/`absint`/`sanitize_html_class`; whitelist estricta de `project`(sección)/`view`/`type`/`modulo`; `realpath` dentro de `data/views/`; el shortcode no vuelca datos en el HTML (se piden por REST con nonce); `uninstall.php` limpia opciones y la tabla sin tocar contenido del usuario.

## 10. Entrega, versionado y PR (item del mensaje del usuario)

- **Todo el trabajo en el repo `GobernaciondeNarino/suite-paz`.** Autenticación por PAT (push+admin verificados); el token se guarda **fuera del repo** (nunca se comitea).
- **Rama `main`**: commit inicial de arranque (README + LICENSE + .gitignore + docs). **Rama `actualizar-paz`**: todo el desarrollo del plugin.
- **Versiones por cada modificación**: SemVer en el header de `suite-paz.php` + entradas en `CHANGELOG.md` por cambio; commits atómicos.
- **Pull Request "actualizar-paz"** (`actualizar-paz` → `main`) creado por API, actualizado a medida que avanza el desarrollo.
- **README.md**: instalación, uso, tabla de shortcodes, arquitectura, secciones, editor de datos, seguridad; se mantiene al día con cada cambio.

## 11. Validación (restricciones honestas)

No hay WordPress ni PHP en el equipo de desarrollo. Estrategia:
- **JSON**: validación de sintaxis y esquema de todas las vistas/módulos (script Python).
- **PHP**: `php -l` si se instala php-cli; si no, revisión estricta de sintaxis y patrones WP por subagente. (Instalar php-cli es un paso opcional del plan.)
- **Renderer/módulos**: un **harness HTML** local carga `renderer.js` + d3plus + `modules.js` con las vistas/módulos reales y se verifica con **Playwright** que mapas, gráficos y módulos pintan **con datos**.
- **Instalación real en WordPress**: se documenta el procedimiento (subir .zip / clonar en `wp-content/plugins`, activar) para que el usuario lo pruebe; no se puede automatizar aquí.

## 12. Fuera de alcance (YAGNI)

- Bloques de Gutenberg (los shortcodes cubren la maquetación manual; se puede añadir después).
- Multi-idioma, control de versiones de datos con historial, importación desde Excel/CSV.
- Rediseño del sitio destino (el plugin solo provee módulos; la maquetación la hace el usuario).

## 13. Riesgos y mitigaciones

- **Sin entorno WP local**: se mitiga con harness de render + validación de JSON/esquema + documentación de prueba de instalación. El usuario valida la activación en su WordPress.
- **d3plus desde CDN**: requiere internet en el front (igual que tic-suite). Si se necesita offline, se puede empaquetar el bundle (fuera de alcance inicial).
- **Datos heterogéneos del PDF**: los módulos PAZ absorben lo no-tabular; lo tabular/geográfico usa el esquema de publicación probado.
- **Token en texto plano en el chat**: el usuario debe **rotar el PAT** al finalizar la entrega.
