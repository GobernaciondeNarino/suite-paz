# suite-paz v1.1.0 — Fix de vistas + análisis ciudadano + botón "Ver datos" — Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use `- [ ]`.

**Goal:** Que TODAS las vistas rendericen en WordPress (no solo los geomaps), añadir un shortcode de análisis ciudadano (~594 caracteres) por elemento, y un botón "Ver datos" en cada vista.

**Root cause (confirmado):** REST `/render` devuelve 409 salvo que el `type` esté en `compatible_for($view)`, que depende de `categoria`. La semilla usó categorías temáticas (`humanitarian`,`security`,`economic`,`coexistence`,`strategy`) que la tabla de compatibilidad NO reconoce (solo `categorical,temporal,geographic,hierarchical,network,statistical,social`). Además `table/list/radial/strategy` no son tipos d3plus. El harness lo ocultó (alimentaba JSON crudo, sin el filtro REST).

**Tech Stack:** PHP (WP), d3plus, JS vanilla, Python (regenerar semilla), Playwright + php portable para validar.

## Global Constraints
- Rama `actualizar-paz`; commits versionados (SemVer + CHANGELOG). Push con `scripts/gitpush.sh`. NUNCA commitear el token (ya eliminado; askpass en `<scratchpad>`).
- PHP lint: `PHP="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad/php/php.exe"`.
- **Criterio de aceptación "todo funciona":** para cada vista de gráfico, su `tipo_grafico_sugerido` ∈ `compatible_for(view)` (verificado por script PHP). Para módulos y tipos nativos (`tabla`,`diagrama`,`estrategia`): renderizan en el harness.
- Cifras verbatim (no cambian). Solo cambian `categoria`, `tipo_grafico_sugerido` y se añade `analisis` (+ opcional `tema`).
- Paleta de marca: violeta `#5B3B8C`, teal `#3FCF97`, coral `#E63946`, texto `#1E2233`.
- Mapeo de categorías (semilla → estándar): humanitarian→`categorical` (o `temporal` si es serie de año en línea), security→`categorical`, economic→`categorical`, coexistence→`categorical`, geographic→`geographic` (sin cambio), social→`social` (sin cambio). Guardar el tema original en un campo `tema` (opcional, para agrupar).
- Tipos no-d3plus: `table`→`tabla` (nuevo, nativo); `list` (estructuras-armadas)→`tabla`; `radial` (subsecretaria)→módulo `diagrama`; `strategy` (narino-360)→módulo `estrategia`.

---

### Task 1: Corregir categorías y tipos en la semilla (causa raíz)

**Files:** Modify `scripts/build-views.py`, `scripts/paz_catalog.py` (si aplica); regenerate `data/views/**`. Create `scripts/verify-compat.php`.

**Interfaces:** Produces: cada vista de gráfico con `categoria` estándar y `tipo_grafico_sugerido` válido; tablas con `tipo_grafico_sugerido:"tabla"`; subsecretaria→`{modulo:"diagrama",...}`, narino-360→`{modulo:"estrategia",...}`.

- [ ] **Step 1: Ajustar `build-views.py`** — para cada vista poner la `categoria` estándar según el mapeo (Global Constraints). Series por año que se grafican como línea (`nna-desvinculacion`, `hist-homicidios-gob` si aplica) → `temporal`; barras → `categorical`. Añadir `tema` = categoría temática original. Cambiar los `tipo_grafico_sugerido` no-d3plus: las 4 tablas (`homicidios-departamental`, `hurtos`, `convivencia`, `indicadores-sociales`) → `"tabla"`; `estructuras-armadas` (list) → `"tabla"`. Convertir `subsecretaria.json` a `{"modulo":"diagrama","id":"subsecretaria","titulo":...,"centro":"Subsecretaría de Seguridad Ciudadana","ramas":[{"nombre":...,"kpi":...,"sub":[...]}, ...]}` y `narino-360.json` a `{"modulo":"estrategia","id":"narino-360","titulo":...,"descripcion":...,"lineas":[...],"comunicaciones":[...]}` (usar el contenido de s31/s32 del catálogo del PDF; ver `C:/Users/Usuario/.claude/plugins/paz/src/data.js` s31/s32).

- [ ] **Step 2: Regenerar y validar esquema** — `python scripts/build-views.py && python scripts/validate-views.py` → VIEWS OK. (validate-views.py debe aceptar `modulo:diagrama|estrategia` y `tipo_grafico_sugerido:tabla`; si no, ampliar sus listas `MOD_TYPES`/tipos.)

- [ ] **Step 3: Script de verificación de compatibilidad** — crear `scripts/verify-compat.php` (con stubs WP mínimos) que cargue `SPZ_Chart_Types` + `SPZ_Data_Provider` (o replique la inferencia), recorra TODAS las vistas de gráfico y afirme que `tipo_grafico_sugerido ∈ compatible_for(view)`; para `tabla`/módulos, que el tipo/módulo esté registrado. Imprime `COMPAT OK: N vistas` o lista las que fallan.
Run: `"$PHP" scripts/verify-compat.php` → `COMPAT OK` para todas las de gráfico (bar/line/geomap/tabla).

- [ ] **Step 4: Commit (v1.1.0-alpha.1)** — bump a `1.1.0`, CHANGELOG `## [1.1.0]` (fix categorías + tipos). `git add scripts data/views suite-paz.php CHANGELOG.md && git commit -m "fix: categorías estándar + tipos renderizables en la semilla (causa raíz de vistas rotas)"`.

---

### Task 2: Registrar `tabla` + manejar categorías nuevas en chart-types

**Files:** Modify `includes/class-spz-chart-types.php`, `includes/class-spz-rest-api.php` (si `tabla` necesita mapping).

**Interfaces:** Produces: `tabla` como tipo válido (`is_valid_type('tabla')===true`), compatible con TODAS las categorías (universal), `d3plus_class` vacío/`'Table'` marcado como nativo. `build_mapping` para `tabla` devuelve columnas = dimensions+measures.

- [ ] **Step 1: Registrar el tipo `tabla`** en `SPZ_Chart_Types::TYPES`/registry: `categories` = todas las estándar (`categorical,temporal,geographic,hierarchical,network,statistical,social`), `requires` = ninguno, `d3plus_class` = `''` con flag `native=>true`, label "Tabla de datos". Así `compatible_for` lo ofrece para cualquier vista y `is_valid_type('tabla')` es true.

- [ ] **Step 2: Mapping para `tabla`** en `class-spz-rest-api.php build_mapping()`: caso `tabla` → `['columns'=>array_merge($view['dimensions'],$view['measures'])]`.

- [ ] **Step 3: Validar** — `"$PHP" -l` de ambos; `"$PHP" scripts/verify-compat.php` sigue OK y ahora las tablas resuelven `tabla` compatible.

- [ ] **Step 4: Commit** — `git add includes/class-spz-chart-types.php includes/class-spz-rest-api.php CHANGELOG.md && git commit -m "feat: tipo nativo 'tabla' compatible con todas las categorías"`.

---

### Task 3: Renderer — tabla nativa + módulos diagrama/estrategia

**Files:** Modify `assets/js/renderer.js` (rama `tabla`), `assets/js/modules.js` (diagrama, estrategia), `assets/css/frontend.css`.

**Interfaces:** Consumes payload REST. Produces: `SPZ.renderer.render` detecta `type==='tabla'` (o `chart.class===''/native`) y pinta una tabla HTML de marca desde `data` + `mapping.columns` (formato es-CO, sin d3plus). `SPZ.modules.render` maneja `diagrama` (centro + ramas) y `estrategia` (líneas + comunicaciones).

- [ ] **Step 1: Rama `tabla` en renderer.js** — antes de instanciar d3plus, si el tipo es `tabla` (o `chart.class` vacío/native), construir `<table class="spz-tabla">` con encabezados = columnas y filas = `data`, números con `toLocaleString('es-CO')`, escapando texto. No llamar a d3plus.

- [ ] **Step 2: `diagrama` y `estrategia` en modules.js** — `diagrama(el,d)`: nodo central `d.centro` + lista de `d.ramas` (nombre, kpi, sub). `estrategia(el,d)`: `d.descripcion` + `d.lineas` (numeradas) + `d.comunicaciones`. Escapar todo el texto (helper `esc` ya existe). Añadir a la tabla de dispatch de `SPZ.modules`.

- [ ] **Step 3: CSS** — `.spz-tabla` (tabla limpia de marca, zebra, encabezado violeta), `.spz-diagrama`, `.spz-estrategia`.

- [ ] **Step 4: Validar en harness** — añadir a `tests/harness.html` una tabla (cargando `../data/views/seguridad/homicidios-departamental.json` como payload nativo) y un diagrama/estrategia. Serve + Playwright: `.spz-tabla tr` > 1; `.spz-diagrama`/`.spz-estrategia` presentes; consola limpia. Screenshot.

- [ ] **Step 5: Commit** — `git add assets tests CHANGELOG.md && git commit -m "feat: renderer de tabla nativa + módulos diagrama/estrategia"`.

---

### Task 4: Análisis ciudadano — campo `analisis` + shortcode `[spz_analisis]`

**Files:** Modify `includes/class-spz-shortcode.php` (nuevo shortcode), `templates/admin/shortcodes.php` (mostrar el shortcode de análisis bajo cada elemento), `templates/admin/builder.php` (mostrar `[spz_analisis]` bajo el shortcode del gráfico), `assets/css/frontend.css`. Data: se añade en Task 5.

**Interfaces:** Produces: `[spz_analisis id="<slug>" seccion="<sec>"]` → renderiza (server-side, escapado) el campo `analisis` de esa vista/módulo dentro de `<div class="spz-analisis">`. Si no hay `analisis`, no imprime nada (o un aviso discreto en admin).

- [ ] **Step 1: Shortcode `render_analisis($atts)`** — sanitiza `id`/`seccion`; obtiene la vista vía `data_provider($seccion)->get_view($id)` (o `get_raw`); imprime `esc_html($view['analisis'])` en un bloque de marca. Registrar `add_shortcode('spz_analisis', ...)`.

- [ ] **Step 2: Builder + galería** — en `builder.php` y `shortcodes.php`, debajo del shortcode del elemento mostrar también el bloque copiable `[spz_analisis id="…" seccion="…"]` (con botón copiar, como los demás).

- [ ] **Step 3: CSS** `.spz-analisis` (bloque de párrafo legible, borde/acento de marca, buena tipografía para ciudadanía).

- [ ] **Step 4: Validar** — `"$PHP" -l` shortcode; revisión de escapado. (El render real se prueba en WP; verificar por código que lee `analisis` y escapa.)

- [ ] **Step 5: Commit** — `git add includes/class-spz-shortcode.php templates assets/css/frontend.css CHANGELOG.md && git commit -m "feat: shortcode [spz_analisis] de análisis ciudadano por elemento"`.

---

### Task 5: Redactar los análisis ciudadanos (~594 caracteres) para los 34 elementos

**Files:** Modify `scripts/build-views.py` (o un `scripts/analisis.py` con el texto por slug) para inyectar `analisis` en cada JSON; regenerate.

**Interfaces:** Produces: cada `data/views/<sec>/<slug>.json` con un campo `"analisis"` = párrafo ~594 caracteres, enfocado a la ciudadanía, derivado de las cifras reales de ese elemento (qué mide, cómo cambió, qué significa para la gente). Tono claro, no técnico, sin inventar cifras.

- [ ] **Step 1: Escribir `scripts/analisis.py`** — un dict `ANALISIS = { '<slug>': "<párrafo ~594 chars>" }` para los 34 slugs, usando las cifras de cada vista (leer los JSON/paz_catalog). Cada texto: 560–620 caracteres, español ciudadano, explica el dato y su impacto humano. No inventar; si un valor es null/0, redactar con honestidad.

- [ ] **Step 2: Inyectar en build-views.py** — al escribir cada vista/módulo, añadir `obj['analisis'] = ANALISIS.get(slug, '')`. Regenerar: `python scripts/build-views.py && python scripts/validate-views.py`.

- [ ] **Step 3: Verificar longitudes** — script/`python` que imprime, por slug, `len(analisis)`; confirmar que los 34 están entre ~560 y ~630 y ninguno vacío.

- [ ] **Step 4: Commit** — `git add scripts data/views && git commit -m "content: análisis ciudadano (~594c) para los 34 elementos"`.

---

### Task 6: Botón "Ver datos" en cada vista

**Files:** Modify `assets/js/frontend.js` (chrome del contenedor), `assets/js/modules.js` (para módulos), `assets/css/frontend.css`.

**Interfaces:** Produces: cada `.spz-chart` y `.spz-module` renderizado obtiene un botón "Ver datos" que abre un panel/modal con la data que consume (tabla de `data`/filas del payload + fuente/`descripcion`). Cierra con Esc/botón. Accesible (foco, aria).

- [ ] **Step 1: Botón + panel en frontend.js** — tras renderizar una vista, insertar un botón "Ver datos"; al pulsarlo, construir una tabla HTML con el `payload.data` (o `municipios`/`datos`) y mostrarla en un panel plegable/modal bajo el gráfico. Reutilizar el generador de tabla de Task 3 si es práctico (extraer a `SPZ.util.dataTable(rows)`).

- [ ] **Step 2: Igual para módulos** en modules.js — el panel muestra los campos del módulo (serie, from/to/delta, eventos…).

- [ ] **Step 3: CSS** `.spz-verdatos`, `.spz-datapanel` (marca, legible, responsive).

- [ ] **Step 4: Validar en harness** — pulsar el botón (Playwright `browser_click`) y confirmar que aparece la tabla de datos; consola limpia; screenshot.

- [ ] **Step 5: Commit** — `git add assets CHANGELOG.md && git commit -m "feat: botón 'Ver datos' con panel de la data consumida por cada vista"`.

---

### Task 7: Validación integral + README + push

**Files:** Modify `README.md`, `CHANGELOG.md`, `suite-paz.php`.

- [ ] **Step 1: Verificación total** — `python scripts/validate-views.py`; `"$PHP" scripts/verify-compat.php` (todas las de gráfico compatibles); `"$PHP" -l` de todos los .php; harness Playwright reconfirmando: un bar (cneb-confinamiento) renderiza CON el payload REST-shaped (no crudo), una tabla, un módulo diagrama/estrategia, el botón "Ver datos", y `[spz_analisis]` (texto). Registrar resultados.
- [ ] **Step 2: Simular el path WP offline** — script PHP que produce el payload REST real de `cneb-confinamiento` (type=bar) y confirmar que ya NO es 409 y trae `chart/data/mapping`. Alimentar ese payload al renderer en el harness → barras con datos.
- [ ] **Step 3: README** — documentar `[spz_analisis]`, el botón "Ver datos", el tipo `tabla`, los módulos diagrama/estrategia; actualizar la limitación de estrategia (ya resuelta). Bump a `1.1.0` final.
- [ ] **Step 4: Commit + push** — `git add -A && git commit -m "docs: README + validación integral (v1.1.0)"`; `bash scripts/gitpush.sh actualizar-paz`.

---

## Notas
- Causa raíz = categorías temáticas vs. familias de forma. El fix corrige la data (fuente única) y añade `tabla` nativo.
- Cifras verbatim; solo cambian metadatos de render (`categoria`,`tipo_grafico_sugerido`) y se añade `analisis`.
- El botón "Ver datos" y la tabla nativa comparten el generador `SPZ.util.dataTable`.
