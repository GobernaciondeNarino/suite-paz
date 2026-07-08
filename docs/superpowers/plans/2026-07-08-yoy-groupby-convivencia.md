# Year-Over-Year GroupBy + Convivencia Graphs — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add year-over-year chart emphasis (auto-detect year dimension, configurable groupBy/measure), convert Convivencia views from tabla to bar, wire UI controls in builder.

**Architecture:** Renderer detects a "year dimension" (named año/anio/year/vigencia/periodo or holding 4-digit year values) and uses it as groupBy for bar/line/area, placing the category on x and coloring by year. Shortcode attributes `group_by` and `measure` override this. Builder admin UI exposes these as selects. Python seed script changes tipo_grafico_sugerido for convivencia/hurtos from "tabla" to "bar".

**Tech Stack:** JavaScript (ES5+ IIFE), PHP 7.4+, Python 3, d3plus v3.1.4, Playwright

## Global Constraints

- PHP lint must pass: `"$PHP" -l <file>`
- `python scripts/validate-views.py` must report VIEWS OK
- `"$PHP" scripts/verify-compat.php` must report COMPAT OK, zero failures
- Version: suite-paz.php + SPZ_VERSION constant → 1.4.0
- Branch: actualizar-paz
- No new dependencies; no figures changed
- Wide-format `_YYYY` measure path must still work (regression)

---

### Task 1: renderer.js — detectYearDim + chooseMeasure helpers

**Files:**
- Modify: `assets/js/renderer.js` — add two helpers to the Renderer object

**Interfaces:**
- Produces: `Renderer.detectYearDim(dims: string[], data: object[]): string|null`
- Produces: `Renderer.chooseMeasure(measures: string[], override: string): string`

- [ ] **Step 1: Add `detectYearDim` after `detectYears` method**

In `renderer.js`, after the `detectYears( measures )` method (~line 887), insert:

```javascript
detectYearDim( dims, data ) {
    const yearNames = [ 'año', 'anio', 'year', 'vigencia', 'periodo' ];
    for ( let i = 0; i < dims.length; i++ ) {
        const normalized = String( dims[ i ] )
            .toLowerCase()
            .normalize( 'NFD' )
            .replace( /[̀-ͯ]/g, '' );
        if ( yearNames.indexOf( normalized ) !== -1 ) {
            return dims[ i ];
        }
    }
    // Fallback: check if first column values are 4-digit years.
    if ( Array.isArray( data ) && data.length && dims.length ) {
        for ( let i = 0; i < dims.length; i++ ) {
            const sample = data.slice( 0, Math.min( 5, data.length ) )
                .map( function ( r ) { return String( r[ dims[ i ] ] || '' ); } );
            if ( sample.length && sample.every( function ( v ) { return /^20\d{2}$/.test( v ); } ) ) {
                return dims[ i ];
            }
        }
    }
    return null;
},

chooseMeasure( measures, override ) {
    if ( override && measures.indexOf( override ) !== -1 ) {
        return override;
    }
    // Prefer tasa_narino, then any *_narino measure, then first.
    for ( let i = 0; i < measures.length; i++ ) {
        if ( /tasa_narino$/i.test( measures[ i ] ) ) {
            return measures[ i ];
        }
    }
    for ( let i = 0; i < measures.length; i++ ) {
        if ( /narino$/i.test( measures[ i ] ) ) {
            return measures[ i ];
        }
    }
    return measures[ 0 ] || '';
},
```

- [ ] **Step 2: Verify helpers are syntactically valid by running the harness (no changes yet to configure)**

```bash
# Serve plugin root and open harness; existing tests should still pass
# (Playwright will run after all code changes in Task 4)
```

---

### Task 2: renderer.js — thread `el` into configure + year-aware bar/line/area

**Files:**
- Modify: `assets/js/renderer.js` — update `configure` signature and bar/line/area cases

**Interfaces:**
- Consumes: `Renderer.detectYearDim`, `Renderer.chooseMeasure` from Task 1
- Produces: `el.dataset.spzGroupBy` set to resolved groupBy field (testable)
- Produces: `payload._resolvedGroupBy`, `payload._resolvedMeasure` (used in applyAxes update)

- [ ] **Step 1: Update `configure` signature to accept `el` as third parameter**

Change line ~563:
```javascript
configure( viz, payload ) {
```
to:
```javascript
configure( viz, payload, el ) {
```

- [ ] **Step 2: Update the call site in `render()` (~line 345)**

Change:
```javascript
this.configure( viz, payload, opts );
```
to:
```javascript
this.configure( viz, payload, el );
```

- [ ] **Step 3: Replace the `bar` case in `configure` (lines ~573-577)**

Replace:
```javascript
case 'bar':
    viz
        .data( data )
        .groupBy( dims[ 0 ] )
        .x( dims[ 0 ] )
        .y( measures[ 0 ] );
    break;
```

With:
```javascript
case 'bar': {
    const yearDimB   = this.detectYearDim( dims, data );
    const gbOverrideB = ( el && el.getAttribute( 'data-group-by' ) ) || '';
    const mOverrideB  = ( el && el.getAttribute( 'data-measure' )  ) || '';
    let groupByFieldB, xFieldB;
    if ( yearDimB && dims.length >= 2 ) {
        const wantedGB = ( gbOverrideB && dims.indexOf( gbOverrideB ) !== -1 ) ? gbOverrideB : yearDimB;
        groupByFieldB = wantedGB;
        xFieldB = dims.find( function ( d ) { return d !== groupByFieldB; } ) || dims[ 0 ];
    } else {
        groupByFieldB = ( gbOverrideB && dims.indexOf( gbOverrideB ) !== -1 ) ? gbOverrideB : dims[ 0 ];
        xFieldB = groupByFieldB;
    }
    const yFieldB = this.chooseMeasure( measures, mOverrideB );
    if ( el ) { el.dataset.spzGroupBy = groupByFieldB; }
    payload._resolvedGroupBy = groupByFieldB;
    payload._resolvedMeasure = yFieldB;
    viz
        .data( data )
        .groupBy( groupByFieldB )
        .x( xFieldB )
        .y( yFieldB );
    break;
}
```

- [ ] **Step 4: Update the `line`/`area` else-branch (when yearCols.length < 2, ~line 615-619)**

The else branch currently is:
```javascript
} else {
    viz
        .data( data )
        .groupBy( dims[ 1 ] || dims[ 0 ] )
        .x( dims[ 0 ] )
        .y( measures[ 0 ] );
}
```

Replace with:
```javascript
} else {
    const yearDimLA   = this.detectYearDim( dims, data );
    const gbOverrideLA = ( el && el.getAttribute( 'data-group-by' ) ) || '';
    const mOverrideLA  = ( el && el.getAttribute( 'data-measure' )  ) || '';
    if ( yearDimLA && dims.length >= 2 ) {
        const wantedGBLA = ( gbOverrideLA && dims.indexOf( gbOverrideLA ) !== -1 ) ? gbOverrideLA : yearDimLA;
        const xFieldLA   = dims.find( function ( d ) { return d !== wantedGBLA; } ) || dims[ 0 ];
        const yFieldLA   = this.chooseMeasure( measures, mOverrideLA );
        if ( el ) { el.dataset.spzGroupBy = wantedGBLA; }
        payload._resolvedGroupBy = wantedGBLA;
        payload._resolvedMeasure = yFieldLA;
        viz
            .data( data )
            .groupBy( wantedGBLA )
            .x( xFieldLA )
            .y( yFieldLA );
    } else {
        viz
            .data( data )
            .groupBy( dims[ 1 ] || dims[ 0 ] )
            .x( dims[ 0 ] )
            .y( measures[ 0 ] );
    }
}
```

---

### Task 3: class-spz-shortcode.php — add group_by + measure attributes

**Files:**
- Modify: `includes/class-spz-shortcode.php` — `render_grafico` method

- [ ] **Step 1: Add defaults in shortcode_atts**

In `render_grafico`, in `shortcode_atts([ ... ])`, add after `'timeline' => 'auto'`:
```php
'group_by'     => '',
'measure'      => '',
```

- [ ] **Step 2: Sanitize the new attributes**

After the `$timeline = ...` line, add:
```php
$group_by     = sanitize_key( (string) $atts['group_by'] );
$measure_attr = sanitize_key( (string) $atts['measure'] );
```

- [ ] **Step 3: Emit data-group-by and data-measure in the chart div**

In the `sprintf( '<div class="spz-chart"' ... )` call, add after `data-timeline`:
```php
. ' data-group-by="%s" data-measure="%s"'
```
And add the corresponding args after `esc_attr( $timeline )`:
```php
esc_attr( $group_by ),
esc_attr( $measure_attr ),
```

- [ ] **Step 4: PHP lint**

```bash
"$PHP" -l includes/class-spz-shortcode.php
```
Expected: No syntax errors

---

### Task 4: build-views.py — change convivencia/hurtos to bar + hint fields

**Files:**
- Modify: `scripts/build-views.py` — `conv_convivencia()` and `conv_hurtos()`

- [ ] **Step 1: Change `tipo_grafico_sugerido` in `conv_convivencia()`**

In `conv_convivencia()`, change:
```python
"tipo_grafico_sugerido": "tabla",
```
to:
```python
"tipo_grafico_sugerido": "bar",
```

And add hint fields after `"categoria": "categorical"`:
```python
"group_by_default": "año",
"measure_default": "tasa_narino",
```

- [ ] **Step 2: Change `tipo_grafico_sugerido` in `conv_hurtos()`**

In `conv_hurtos()`, change:
```python
"tipo_grafico_sugerido": "tabla",
```
to:
```python
"tipo_grafico_sugerido": "bar",
```

And add hint fields after `"categoria": "categorical"`:
```python
"group_by_default": "año",
"measure_default": "casos_narino",
```

- [ ] **Step 3: Regenerate views**

```bash
cd C:/Users/Usuario/.claude/plugins/suite-paz && python scripts/build-views.py
```
Expected: writes convivencia/convivencia.json and convivencia/hurtos.json (among others)

- [ ] **Step 4: Validate views**

```bash
python scripts/validate-views.py
```
Expected: `VIEWS OK: 34 archivos válidos` (or more)

- [ ] **Step 5: Verify compat**

```bash
"$PHP" scripts/verify-compat.php
```
Expected: `COMPAT OK: N vistas de gráfico` with zero failures (convivencia and hurtos now offer bar/line/tabla)

---

### Task 5: builder.php — add Agrupar por + Medida selects

**Files:**
- Modify: `templates/admin/builder.php` — add two option rows to the fieldset

- [ ] **Step 1: Add "Agrupar por" row after the timeline row**

After the `</div>` closing the timeline `spz-options__row`, add:

```php
<div class="spz-options__row">
    <label class="spz-select-inline">
        <span><?php esc_html_e( 'Agrupar por:', 'suite-paz' ); ?></span>
        <select data-spz-opt="group_by">
            <option value=""><?php esc_html_e( 'Auto (año/vigencia)', 'suite-paz' ); ?></option>
        </select>
    </label>
</div>

<div class="spz-options__row">
    <label class="spz-select-inline">
        <span><?php esc_html_e( 'Medida:', 'suite-paz' ); ?></span>
        <select data-spz-opt="measure">
            <option value=""><?php esc_html_e( 'Auto (tasa_narino / primera)', 'suite-paz' ); ?></option>
        </select>
    </label>
</div>
```

- [ ] **Step 2: PHP lint**

```bash
"$PHP" -l templates/admin/builder.php
```
Expected: No syntax errors

---

### Task 6: admin.js — wire group_by + measure options + populate selects + emit shortcode

**Files:**
- Modify: `assets/js/admin.js` — builder state, wireOptionsUI, populate selects on view select, renderShortcode

- [ ] **Step 1: Add group_by and measure to initial state.options**

In the builder `state` object:
```javascript
options: {
    legend:       true,
    legend_style: 'text',
    toolbar:      true,
    actions:      DEFAULT_ACTIONS.slice(),
    x_title:      '',
    y_title:      '',
    timeline:     'auto',
    group_by:     '',
    measure:      '',
},
```

- [ ] **Step 2: Populate group_by and measure selects when a view is selected**

In `onSelectView`, after `state.view = payload.view; state.compatible = payload.compatible || [];`, add a call:
```javascript
populateGroupByMeasure( payload.view );
```

Add function `populateGroupByMeasure( view )`:
```javascript
function populateGroupByMeasure( view ) {
    const dims     = ( view && view.dimensions ) || [];
    const measures = ( view && view.measures )   || [];

    const gbSel = $( '[data-spz-opt="group_by"]', els.options );
    const mSel  = $( '[data-spz-opt="measure"]',  els.options );

    if ( gbSel ) {
        gbSel.innerHTML = '<option value="">' + escapeHtml( SPZ_ADMIN.i18n.autoGroupBy || 'Auto (año/vigencia)' ) + '</option>';
        dims.forEach( function ( d ) {
            const opt = document.createElement( 'option' );
            opt.value       = d;
            opt.textContent = d;
            gbSel.appendChild( opt );
        } );
        gbSel.value = '';
        state.options.group_by = '';
    }

    if ( mSel ) {
        mSel.innerHTML = '<option value="">' + escapeHtml( SPZ_ADMIN.i18n.autoMeasure || 'Auto (tasa_narino / primera)' ) + '</option>';
        measures.forEach( function ( m ) {
            const opt = document.createElement( 'option' );
            opt.value       = m;
            opt.textContent = m;
            mSel.appendChild( opt );
        } );
        mSel.value = '';
        state.options.measure = '';
    }
}
```

- [ ] **Step 3: Emit group_by and measure in renderShortcode when set**

In `renderShortcode`, after the `timeline` line:
```javascript
if ( opts.group_by ) { parts.push( `group_by="${ opts.group_by }"` ); }
if ( opts.measure )  { parts.push( `measure="${ opts.measure }"` ); }
```

---

### Task 7: harness.html — Tests 13 + 14 (convivencia bar + line)

**Files:**
- Modify: `tests/harness.html` — add two test cards before closing `</body>`

- [ ] **Step 1: Add Test 13 card (bar, auto groupBy resolves to año)**

After the last test card (12b) and before `<!-- Renderer -->`:

```html
<!-- ============================================================
     TEST 13 — Convivencia bar con year-over-year groupBy
     data/views/convivencia/convivencia.json · tipo: bar
     groupBy auto → debe resolverse a 'año' (el.dataset.spzGroupBy)
     x-axis: 3 indicadores; legend: 3 años (2023/2024/2025)
     ============================================================ -->
<div class="card">
  <h2>Test 13 — Barras Convivencia año-a-año (groupBy=año auto)</h2>
  <p class="desc">Vista: convivencia/convivencia · tipo: bar · data-group-by="" (auto)<br>
     Se espera <code>el.dataset.spzGroupBy === 'año'</code> y SVG con barras.</p>
  <div class="spz-chart" id="c13" style="height:420px;min-height:420px;"></div>
  <div class="status" id="s13">Cargando…</div>
</div>

<!-- ============================================================
     TEST 14 — Convivencia line chart
     data/views/convivencia/convivencia.json · tipo: line
     Debe renderizar sin error; SVG con elementos.
     ============================================================ -->
<div class="card">
  <h2>Test 14 — Líneas Convivencia año-a-año</h2>
  <p class="desc">Vista: convivencia/convivencia · tipo: line<br>
     Se espera SVG con elementos (sin error de consola).</p>
  <div class="spz-chart" id="c14" style="height:420px;min-height:420px;"></div>
  <div class="status" id="s14">Cargando…</div>
</div>
```

- [ ] **Step 2: Add JS for Tests 13 and 14 in the main script block**

In the main `(function () { ... })()` block, after the Test 12b block:

```javascript
// ----------------------------------------------------------------
// Test 13 — Convivencia bar: groupBy resolves to año
// ----------------------------------------------------------------
fetch('../data/views/convivencia/convivencia.json')
  .then(function(r) { return r.json(); })
  .then(function(view) {
    var c13 = document.getElementById('c13');
    if (!c13) { setStatus('s13', false, '#c13 no encontrado'); return; }
    c13.scrollIntoView({ behavior: 'instant', block: 'center' });
    SPZ.renderer.render(c13, { view: view, type: 'bar', options: { legend: true } });
    var attempts = 0;
    var timer = setInterval(function() {
      attempts++;
      var groupBy = c13.dataset.spzGroupBy;
      var svgEls  = c13.querySelectorAll('svg *').length;
      if (groupBy) {
        clearInterval(timer);
        var ok = (groupBy === 'año');
        setStatus('s13', ok,
          ok
            ? 'spzGroupBy="año" ✓ — ' + svgEls + ' elementos SVG'
            : 'spzGroupBy="' + groupBy + '" (esperaba "año")');
      } else if (attempts > 80) {
        clearInterval(timer);
        setStatus('s13', false, 'Timeout — spzGroupBy no seteado, svgEls=' + svgEls);
      }
    }, 100);
  })
  .catch(function(err) {
    console.error('[SPZ harness test 13]', err);
    setStatus('s13', false, 'Error: ' + err.message);
  });

// ----------------------------------------------------------------
// Test 14 — Convivencia line chart renders without error
// ----------------------------------------------------------------
fetch('../data/views/convivencia/convivencia.json')
  .then(function(r) { return r.json(); })
  .then(function(view) {
    var c14 = document.getElementById('c14');
    if (!c14) { setStatus('s14', false, '#c14 no encontrado'); return; }
    c14.scrollIntoView({ behavior: 'instant', block: 'center' });
    SPZ.renderer.render(c14, { view: view, type: 'line', options: { legend: true } });
    var attempts = 0;
    var timer = setInterval(function() {
      attempts++;
      var svgEls = c14.querySelectorAll('svg *').length;
      if (svgEls > 5) {
        clearInterval(timer);
        setStatus('s14', true, 'Line chart pintado — ' + svgEls + ' elementos SVG');
      } else if (attempts > 80) {
        clearInterval(timer);
        setStatus('s14', svgEls > 0, 'SVG elementos=' + svgEls + (svgEls > 0 ? ' (mínimo)' : ' — sin render'));
      }
    }, 100);
  })
  .catch(function(err) {
    console.error('[SPZ harness test 14]', err);
    setStatus('s14', false, 'Error: ' + err.message);
  });
```

---

### Task 8: Version bump + CHANGELOG

**Files:**
- Modify: `suite-paz.php` — version constants
- Modify: `CHANGELOG.md` — prepend new entry

- [ ] **Step 1: Bump version in suite-paz.php**

Change:
```php
 * Version:           1.3.2
```
to:
```php
 * Version:           1.4.0
```

Change:
```php
define( 'SPZ_VERSION', '1.3.2' );
```
to:
```php
define( 'SPZ_VERSION', '1.4.0' );
```

- [ ] **Step 2: Add CHANGELOG entry**

Prepend after `# Changelog\nTodas las versiones del plugin Suite PAZ.\n`:

```markdown
## [1.4.0] — 2026-07-08
### Added
- feat: énfasis año-a-año — groupBy por vigencia + atributos group_by/measure; Convivencia como gráficos. `renderer.js`: nuevos helpers `detectYearDim` (normaliza tildes, detecta nombres año/anio/year/vigencia/periodo o valores 4-dígitos) y `chooseMeasure` (prefiere tasa_narino/*_narino). `configure(viz, payload, el)` recibe el contenedor; para `bar`/`line`/`area` con formato largo y dimensión año: `groupBy=año`, `x=indicador`, `y=tasa_narino`; soporta overrides `data-group-by`/`data-measure`; expone `el.dataset.spzGroupBy`. Shortcode `[spz_grafico]`: atributos `group_by` (sanitize_key, emitido como `data-group-by`) y `measure` (idem). Constructor: selects "Agrupar por" y "Medida" poblados desde dims/measures de la vista; generan `group_by="…"`/`measure="…"` en el shortcode cuando no son auto. `build-views.py`: convivencia y hurtos cambian de `tabla` a `bar` + hint fields `group_by_default:"año"` y `measure_default`. Harness: Tests 13 (bar groupBy→año) y 14 (line renderiza).
```

- [ ] **Step 3: PHP lint on suite-paz.php**

```bash
"$PHP" -l suite-paz.php
```

---

### Task 9: Validation + Playwright + Commit

- [ ] **Step 1: Run full validation suite**

```bash
python scripts/build-views.py
python scripts/validate-views.py
"$PHP" scripts/verify-compat.php
```

Expected:
- build-views: all views written
- validate-views: VIEWS OK
- verify-compat: COMPAT OK, 0 failures (convivencia/hurtos now bar → compatible)

- [ ] **Step 2: PHP lint all modified files**

```bash
"$PHP" -l includes/class-spz-shortcode.php
"$PHP" -l templates/admin/builder.php
"$PHP" -l suite-paz.php
```

- [ ] **Step 3: Playwright harness run**

Serve plugin root on port 8794, navigate to harness, wait for all tests. Assert:
- s13 passes: `spzGroupBy === 'año'`
- s14 passes: SVG renders
- s1–s12b still pass (regression)
- Console clean

- [ ] **Step 4: Screenshot**

Take screenshot of c13 (grouped bar with 3 years as legend colors).

- [ ] **Step 5: Git commit**

```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
git add includes/class-spz-shortcode.php assets/js/renderer.js templates/admin/builder.php assets/js/admin.js scripts/build-views.py data/views suite-paz.php CHANGELOG.md tests/harness.html
git commit -m "feat: énfasis año-a-año (groupBy por vigencia) + group_by/measure; Convivencia como gráficos (v1.4.0)"
```

- [ ] **Step 6: Push**

```bash
bash scripts/gitpush.sh actualizar-paz
```

- [ ] **Step 7: Write report**

Write full report to `C:/Users/Usuario/.claude/plugins/suite-paz/briefs/conv-1-report.md`
