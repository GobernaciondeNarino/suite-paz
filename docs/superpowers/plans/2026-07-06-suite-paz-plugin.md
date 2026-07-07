# suite-paz — Plugin WordPress de Paz — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Recrear el plugin WordPress `tic-suite` como `suite-paz`: publica los datos de Paz de Nariño como gráficos/mapas d3plus + módulos PAZ nativos mediante shortcodes, con un editor de datos en la BD de WordPress, y se entrega en el repo `GobernaciondeNarino/suite-paz` (rama `actualizar-paz` + PR).

**Architecture:** Plugin PHP (prefijo `SPZ_`/`spz`) que reutiliza la arquitectura de tic-suite (singleton que cablea security, data-provider, chart-types, shortcode, rest-api, admin), añadiendo `class-spz-modules` (módulos PAZ nativos) y `class-spz-data-store` (overrides editables en BD). El renderer d3plus y los módulos JS piden datos por REST con nonce. Los datos semilla viven en `data/views/<seccion>/*.json`.

**Tech Stack:** PHP 7.4+ (WordPress 6.0+), @d3plus/core v3.1.4 (CDN, bundle `full`), JavaScript vanilla, Python 3 (generación de semillas + validación), Playwright MCP (harness de render), git + GitHub API (entrega).

## Global Constraints

- Prefijo de clases `SPZ_`, constantes `SPZ_*`, funciones/handles `spz`. Text domain `suite-paz`.
- Plugin instalable en `wp-content/plugins/suite-paz/`; archivo principal `suite-paz.php` con header WordPress y **SemVer** (empieza en `0.1.0`).
- Renderer d3plus: `@d3plus/core@3.1.4` bundle **`/umd/d3plus-core.full.js`** (el `/umd/d3plus-core.js` falla — requiere 30+ peer deps).
- 5 secciones (whitelist `SECCIONES`): `dni`, `seguridad`, `convivencia`, `estrategia`, `transformaciones`. Etiqueta legible cada una. `dni` es la default.
- Paleta de marca: violeta `#5B3B8C`, teal `#3FCF97`, coral `#E63946`, ámbar `#F4A93C`, texto `#1E2233`, fondos `#FFFFFF`/`#F4F1FA`.
- Seguridad obligatoria: capability `manage_options` para admin/escritura; nonce `X-WP-Nonce` en cada REST; `sanitize_key`/`sanitize_text_field`/`absint`; whitelist de `project`(sección)/`view`/`type`/`modulo`; `realpath` dentro de `data/views/`; ningún dato volcado en el HTML del shortcode (se pide por REST).
- Cifras = **verbatim** del catálogo del PDF (ver `docs/.../specs` y el catálogo del proyecto previo). No inventar ni redondear. Mapas SIEMPRE con datos reales de municipios.
- Datos editables persisten en **BD de WordPress** (tabla `wp_spz_views`); los JSON son semilla. Override en BD gana sobre el JSON.
- Namespace REST: `suite-paz/v1`. Topojson: `data/topo/narino_municipios.topojson` (64 municipios), copiado de tic-suite.
- **Referencia de código:** el repo tic-suite está clonado en `C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad/tic-suite`. Para cada archivo derivado, LEER el equivalente `tsg` y adaptarlo (renombrar `TSG_`→`SPZ_`, `tsg`→`spz`, `PROJECTS`→`SECCIONES`, `tic-suite/v1`→`suite-paz/v1`, `Py Nación/Py Ondas`→las 5 secciones). Mantener la disciplina de seguridad idéntica.
- **Repo/entrega:** trabajar en `C:/Users/Usuario/.claude/plugins/suite-paz` (repo git ya inicializado, remote `origin` = GobernaciondeNarino/suite-paz, rama `actualizar-paz`). Commits atómicos con SemVer + entrada en `CHANGELOG.md`. Push con `GIT_ASKPASS` seguro (ver Task 0). NO commitear el token.
- **Sin entorno WP/PHP local garantizado.** Validación: `php -l` si php está disponible; validación de JSON/esquema con Python; harness HTML + Playwright para renderer/módulos. La activación real en WordPress la prueba el usuario.

---

### Task 0: Prerrequisitos de entorno (git seguro + PHP opcional)

**Files:**
- Create: `scripts/gitpush.sh` (helper de push que usa el askpass seguro)

**Interfaces:**
- Produces: convención de push — `bash scripts/gitpush.sh <rama>` empuja usando el token del scratchpad sin exponerlo. Variable de entorno `SPZ_TOKEN_FILE` apunta al archivo del token.

- [ ] **Step 1: Confirmar estado del repo y credenciales**

Run:
```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
git status --short && git branch && git remote -v
ls "C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad/.gh_token" && echo "TOKEN OK"
```
Expected: rama `actualizar-paz` activa; remote `origin` → suite-paz; `TOKEN OK`.

- [ ] **Step 2: Crear helper de push seguro**

Create `scripts/gitpush.sh`:
```bash
#!/bin/sh
# Uso: scripts/gitpush.sh <rama>
# Empuja usando el token del scratchpad vía GIT_ASKPASS (nunca lo imprime ni lo guarda en config).
SP="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad"
BRANCH="${1:-actualizar-paz}"
ASK="$SP/askpass.sh"
if [ ! -f "$ASK" ]; then printf '#!/bin/sh\ncat "%s/.gh_token"\n' "$SP" > "$ASK"; chmod +x "$ASK"; fi
git remote set-url origin "https://x-access-token@github.com/GobernaciondeNarino/suite-paz.git"
GIT_TERMINAL_PROMPT=0 GIT_ASKPASS="$ASK" git push -u origin "$BRANCH"
```

- [ ] **Step 3: Detectar PHP (opcional, para lint)**

Run: `which php && php --version | head -1 || echo "PHP no disponible — se usará revisión estricta + validación JSON/harness"`
Registrar el resultado. Si PHP no está, los pasos `php -l` de tareas siguientes se sustituyen por "revisión de sintaxis por el implementador" y no bloquean.

- [ ] **Step 4: Commit del helper**

```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
git add scripts/gitpush.sh
git commit -m "chore: helper de push seguro (askpass)"
```

---

### Task 1: Scaffold del plugin (main file, plugin singleton, seguridad, uninstall, topo, LICENSE)

**Files:**
- Create: `suite-paz.php`
- Create: `includes/class-spz-plugin.php`
- Create: `includes/class-spz-security.php`
- Create: `includes/index.php`, `data/index.php`, `data/views/index.php` (silencios "Silence is golden")
- Create: `uninstall.php`
- Create: `LICENSE` (GPL-2.0-or-later, texto completo)
- Create: `CHANGELOG.md`
- Copy: `data/topo/narino_municipios.topojson`, `data/topo/narino_municipios.lookup.json` (desde el repo tic-suite clonado)

**Interfaces:**
- Produces:
  - Constantes: `SPZ_VERSION`, `SPZ_D3PLUS_VERSION='3.1.4'`, `SPZ_D3PLUS_URL` (bundle full), `SPZ_PLUGIN_DIR`, `SPZ_PLUGIN_URL`, `SPZ_PLUGIN_FILE`, `SPZ_PLUGIN_BASENAME`, `SPZ_MIN_CAPABILITY='manage_options'`, `SPZ_REST_NAMESPACE='suite-paz/v1'`, `SPZ_NONCE_ACTION='spz_nonce_action'`, `SPZ_DATA_DIR`.
  - Autoloader que mapea `SPZ_Foo_Bar` → `includes/class-spz-foo-bar.php`.
  - `SPZ_Plugin::SECCIONES` (array slug→label), `SPZ_Plugin::DEFAULT_SECCION='dni'`, `SPZ_Plugin::instance()`, `->normalize_seccion(?string)`, `->secciones()`, `->run()`, `::activate()`, `::deactivate()`. (Los sub-módulos se cablean en tareas siguientes; en esta tarea el constructor solo crea `security` y deja TODOs mínimos comentados que las tareas 3/5/6/8 completan — pero SIN romper el lint.)
  - `SPZ_Security` con: `verify_nonce()`, `current_user_can_manage()`, `sanitize_slug(string):string`, `sanitize_seccion(string):string`, `safe_view_path(string $seccion, string $slug):?string` (realpath dentro de `data/views/<seccion>/`).

- [ ] **Step 1: Leer las referencias de tic-suite**

Run (leer, no ejecutar):
```bash
TS="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad/tic-suite"
cat "$TS/tic-suite-graficos.php"
cat "$TS/includes/class-tsg-plugin.php"
cat "$TS/includes/class-tsg-security.php"
cat "$TS/uninstall.php"
```
Estos son la plantilla. Adaptar prefijos y `PROJECTS`→`SECCIONES`.

- [ ] **Step 2: Escribir `suite-paz.php`**

Header WordPress + constantes + autoloader + bootstrap. Basarse en el main de tic-suite. Valores clave:
```php
<?php
/**
 * Plugin Name:       Suite PAZ
 * Plugin URI:        https://github.com/GobernaciondeNarino/suite-paz
 * Description:       Publica los datos del proyecto de Paz de Nariño como gráficos, mapas y módulos mediante shortcodes, con editor de datos en el panel.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gobernación de Nariño
 * License:           GPL-2.0-or-later
 * Text Domain:       suite-paz
 * @package SuitePaz
 */
declare( strict_types=1 );
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'SPZ_VERSION', '0.1.0' );
define( 'SPZ_D3PLUS_VERSION', '3.1.4' );
define( 'SPZ_D3PLUS_URL', 'https://cdn.jsdelivr.net/npm/@d3plus/core@' . SPZ_D3PLUS_VERSION . '/umd/d3plus-core.full.js' );
define( 'SPZ_PLUGIN_FILE', __FILE__ );
define( 'SPZ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SPZ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SPZ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SPZ_MIN_CAPABILITY', 'manage_options' );
define( 'SPZ_REST_NAMESPACE', 'suite-paz/v1' );
define( 'SPZ_NONCE_ACTION', 'spz_nonce_action' );
define( 'SPZ_DATA_DIR', SPZ_PLUGIN_DIR . 'data/' );
spl_autoload_register( static function ( string $class ): void {
    if ( strpos( $class, 'SPZ_' ) !== 0 ) { return; }
    $file = SPZ_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
    if ( is_readable( $file ) ) { require_once $file; }
} );
require_once SPZ_PLUGIN_DIR . 'includes/class-spz-plugin.php';
register_activation_hook( __FILE__, [ 'SPZ_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SPZ_Plugin', 'deactivate' ] );
add_action( 'plugins_loaded', static function () { SPZ_Plugin::instance()->run(); } );
```

- [ ] **Step 3: Escribir `includes/class-spz-plugin.php`**

Singleton adaptado del de tic-suite, con `SECCIONES`:
```php
public const SECCIONES = [
    'dni'              => 'Diálogo, Negociación e Implementación',
    'seguridad'        => 'Seguridad Territorial',
    'convivencia'      => 'Convivencia Ciudadana',
    'estrategia'       => 'La Estrategia 2026',
    'transformaciones' => 'Transformaciones Territoriales',
];
public const DEFAULT_SECCION = 'dni';
```
El constructor crea `new SPZ_Security()`. `normalize_seccion()` valida contra `SECCIONES` (como `normalize_project` en tic-suite). `run()` por ahora solo llama `load_plugin_textdomain('suite-paz', ...)` y registra `enqueue_public_assets`/`enqueue_admin_assets` como métodos vacíos-seguros (las tareas 3–8 añaden el cableado de shortcode/rest/admin/data-provider). `activate()` guarda `add_option('spz_settings', [...])` con `default_theme=>'suite-paz'`; `deactivate()` no-op. **Los servicios que aún no existen (data-provider, shortcode, etc.) NO se instancian todavía** para no romper el lint; añadir un comentario `// Tarea N cablea X`.

- [ ] **Step 4: Escribir `includes/class-spz-security.php`**

Adaptar `class-tsg-security.php`. Debe incluir:
```php
public function sanitize_slug( string $s ): string { return sanitize_key( $s ); }
public function sanitize_seccion( string $s ): string {
    $s = sanitize_key( $s );
    return isset( SPZ_Plugin::SECCIONES[ $s ] ) ? $s : SPZ_Plugin::DEFAULT_SECCION;
}
public function safe_view_path( string $seccion, string $slug ): ?string {
    $seccion = $this->sanitize_seccion( $seccion );
    $slug    = $this->sanitize_slug( $slug );
    $base    = realpath( SPZ_DATA_DIR . 'views/' . $seccion );
    if ( false === $base ) { return null; }
    $path = realpath( $base . '/' . $slug . '.json' );
    if ( false === $path || strpos( $path, $base ) !== 0 ) { return null; } // anti traversal
    return $path;
}
public function verify_nonce(): bool { /* copiar de tic-suite: check_ajax_referer / rest nonce */ }
public function current_user_can_manage(): bool { return current_user_can( SPZ_MIN_CAPABILITY ); }
```

- [ ] **Step 5: `uninstall.php`, índices, LICENSE, CHANGELOG, topo**

- `uninstall.php`: adaptar el de tic-suite — `if (!defined('WP_UNINSTALL_PLUGIN')) exit;` + `delete_option('spz_settings')` + `DROP TABLE {$wpdb->prefix}spz_views` (la tabla se crea en Task 7; el uninstall la borra si existe).
- `includes/index.php`, `data/index.php`, `data/views/index.php`: cada uno `<?php // Silence is golden.`
- `LICENSE`: texto completo GPL-2.0-or-later (copiar de https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt o del repo tic-suite si lo trae).
- `CHANGELOG.md`: 
```markdown
# Changelog
Todas las versiones del plugin Suite PAZ.

## [0.1.0] — 2026-07-06
### Added
- Scaffold del plugin: main file, singleton `SPZ_Plugin` con 5 secciones, `SPZ_Security`, uninstall, topojson de los 64 municipios.
```
- Copiar topo:
```bash
TS="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad/tic-suite"
mkdir -p "C:/Users/Usuario/.claude/plugins/suite-paz/data/topo"
cp "$TS/data/topo/narino_municipios.topojson" "$TS/data/topo/narino_municipios.lookup.json" "C:/Users/Usuario/.claude/plugins/suite-paz/data/topo/"
```

- [ ] **Step 6: Validar (lint / sintaxis)**

Si php disponible: `for f in suite-paz.php includes/*.php uninstall.php; do php -l "$f"; done` → todos "No syntax errors detected". Si no: el implementador revisa manualmente el balance de llaves/paréntesis y que no haya símbolos indefinidos, y valida que el autoloader mapea bien los nombres (`SPZ_Security`→`class-spz-security.php`).

- [ ] **Step 7: Commit**

```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
git add suite-paz.php includes uninstall.php LICENSE CHANGELOG.md data/topo data/index.php data/views/index.php
git commit -m "feat: scaffold del plugin suite-paz (v0.1.0) — singleton, seguridad, topo"
```

---

### Task 2: Generación de datos semilla — 37 slides → vistas + módulos (item 1)

**Files:**
- Create: `scripts/build-views.py`
- Create: `scripts/paz_catalog.py` (el catálogo de cifras del PDF como estructura Python — fuente única)
- Create: `data/views/<seccion>/*.json` (generados)
- Create: `scripts/validate-views.py` (validador de esquema)
- Create: `data/views/dni/index.php`, `.../seguridad/index.php`, etc. (silencios)

**Interfaces:**
- Produces:
  - Esquema **vista gráfico/mapa** (formato publicación tic-suite): `{ vista, titulo, descripcion, tipo_grafico_sugerido, total_municipios?, municipios|datos:[{...}], categoria }`.
  - Esquema **módulo**: `{ modulo:'kpi'|'compare'|'timeline'|'logro', id, titulo, ... }` (campos por tipo, definidos abajo).
  - Cada archivo se llama `<slug>.json` dentro de `data/views/<seccion>/`.
  - `validate-views.py` recorre todos los JSON y falla si algún archivo no cumple su esquema.

- [ ] **Step 1: Escribir `scripts/paz_catalog.py` con TODAS las cifras del PDF**

Estructura Python con las cifras verbatim del catálogo (las del spec §9 del proyecto previo). Ejemplo de forma (completar las 37 slides):
```python
CATALOGO = {
  # DNI
  "desplazamiento_narino": {"2023":25344,"2024":17441,"2025":4227,"delta_2324":-31.1,"delta_2425":-75.8},
  "desplazamiento_fcs": {"2023":15465,"2024":911,"2025":349,"delta_2324":-94,"delta_2425":-61.7},
  "confinamiento_narino": {"2023":6772,"2024":3999,"2025":1821,"delta_2324":-40.9,"delta_2425":-54.5},
  "cneb_desplazamiento": {"2023":15697,"2024":12611,"2025":2973,"2026":441},
  "cneb_confinamiento": {"2023":2787,"2024":286,"2025":142,"2026":0},
  "minas_narino": {"2023":42,"2024":26,"2025":6,"2026":6,"delta_2324":-38.1,"delta_2425":-76.9},
  "desminado_municipios": ["SAMANIEGO","MALLAMA","LA LLANADA","CUMBAL","SAN PABLO","SANTACRUZ"],
  "firmantes": {"2023":2,"2024":0,"2025":0,"2026":0,"reduccion":100},
  "desaparecidos": {"total":39,"identificados":14,"entregas":6,"lugares":26,
      "por_municipio":{"CUMBAL":13,"SANTACRUZ":12,"SAMANIEGO":12,"LA LLANADA":2}},
  "nna_desvinculacion": {"2023":38,"2024":35,"2025":27,"total":428,"parcial_2026":9},
  # SEGURIDAD
  "homicidios_municipio": {  # tasa por 100k por año + casos
     "BARBACOAS":{"2023":36.7,"2024":28.3,"2025":17.1,"casos_2025":11},
     "EL CHARCO":{"2023":40.3,"2024":26.8,"2025":13.4,"casos_2025":3},
     "PASTO":{"2023":10.0,"2024":7.9,"2025":9.7,"casos_2025":39},
     "SAN ANDRES DE TUMACO":{"2023":33.4,"2024":21.3,"2025":14.6,"casos_2025":40},
     "SAMANIEGO":{"2023":98.6,"2024":40.8,"2025":47.6,"casos_2025":14}},
  "terrorismo": {"Nacional":[839,1126,1398],"Chocó":[53,81,87],"Valle del Cauca":[57,100,193],
     "Cauca":[158,385,None],"Nariño":[83,39,31]},
  "hist_homicidios_gob": {"govs":["Gaviria","Samper","Pastrana","Uribe I","Uribe II","Santos I","Santos II","Duque","Petro"],
     "rate":[73,80,84,82,72,71,45,36,26.1]},
  # TRANSFORMACIONES
  "ipm": {"total":{"narino":[18.1,13.2],"nacional":[11.5,9.9]},
          "urbano":{"narino":[10.0,8.2],"nacional":[7.8,6.3]},
          "rural":{"narino":[24.5,17.2],"nacional":[24.3,22.4]}},
  "pib": {"Agricultura, ganadería, caza, silvicultura y pesca":[26130,28593],
          "Explotación de minas y canteras":[5185,5852],
          "Industrias manufactureras":[217,243],
          "Comercio y reparación de vehículos":[525,569]},
  "desocupacion": {"narino":[6.5,6.0],"nacional":[9,8]},
  # ... (completar TODAS: convivencia s27/s28, estrategia s31/s32, timelines s17/s18, reclutamiento s10, rutas s12, estructuras armadas s20)
}
MUNICIPIOS_64 = None  # se leen del lookup.json de data/topo
```
El implementador DEBE completar el catálogo con TODAS las cifras del spec §9 del proyecto previo (archivo `C:/Users/Usuario/.claude/plugins/paz/docs/superpowers/specs/2026-07-06-paz-narino-dashboard-design.md` §9 y el catálogo detallado). No inventar valores; usar `None` donde el PDF es ilegible.

- [ ] **Step 2: Escribir `scripts/build-views.py`**

Genera los JSON por sección. Para mapas, expande a los 64 municipios (leyendo `data/topo/narino_municipios.lookup.json`), poniendo el valor del catálogo o `0`/`null` para municipios sin dato. Ejemplo de una vista geográfica:
```python
import json, pathlib, unicodedata
ROOT = pathlib.Path(__file__).resolve().parent.parent
from paz_catalog import CATALOGO
lookup = json.load(open(ROOT/'data/topo/narino_municipios.lookup.json', encoding='utf-8'))
NOMBRES = [m['nombre'] for m in lookup]  # 64 nombres oficiales (con acentos)
def norm(s): return ''.join(c for c in unicodedata.normalize('NFD', s.upper()) if unicodedata.category(c)!='Mn').strip()

def write(seccion, slug, obj):
    d = ROOT/'data/views'/seccion; d.mkdir(parents=True, exist_ok=True)
    json.dump(obj, open(d/f'{slug}.json','w',encoding='utf-8'), ensure_ascii=False, indent=2)
    print('wrote', seccion, slug)

# Mapa: homicidios tasa 2025 por municipio (64 filas, join por nombre)
def homicidios_municipio():
    src = CATALOGO['homicidios_municipio']; srcn = {norm(k):v for k,v in src.items()}
    filas = [{'municipio': nom, 'tasa_homicidio_2025': srcn.get(norm(nom),{}).get('2025', 0),
              'pdet': False} for nom in NOMBRES]
    write('seguridad','homicidios-municipio', {
        'vista':'homicidios-municipio','titulo':'Tasa de homicidio por municipio (2025)',
        'descripcion':'Tasa por 100.000 hab. en municipios priorizados; 0 = sin dato reportado.',
        'tipo_grafico_sugerido':'geomap','categoria':'geographic','total_municipios':64,'municipios':filas})
homicidios_municipio()
# ... una función por vista/módulo (desminado, desaparecidos, estructuras, desplazamiento, ipm, pib, etc.)
```
Módulos (escribir con esquema propio), ejemplos:
```python
# KPI
write('dni','firmantes-100', {'modulo':'kpi','id':'firmantes-100',
    'titulo':'Homicidios de firmantes de paz','valor':100,'unidad':'%','leyenda':'Reducción',
    'serie':[{'y':'2023','v':2},{'y':'2024','v':0},{'y':'2025','v':0},{'y':'2026','v':0}]})
# COMPARE
write('dni','desplazamiento-anual', {'modulo':'compare','id':'desplazamiento-anual',
    'titulo':'Desplazamiento forzado (Nariño)','unidad':'personas',
    'from':{'y':'2023','v':25344},'to':{'y':'2024','v':17441},'delta':-31.1,
    'fuente':'Comité de Justicia Transicional Ley 1448 de 2011'})
# TIMELINE
write('dni','acuerdos-fcs', {'modulo':'timeline','id':'acuerdos-fcs',
    'titulo':'Acuerdos con el Frente Comuneros del Sur','total':12,
    'eventos':[{'fecha':'Sep 2024','texto':'Instalación / primeros acuerdos'}, ...]})
# LOGRO
write('dni','san-pablo-libre-minas', {'modulo':'logro','id':'san-pablo-libre-minas',
    'titulo':'San Pablo libre de minas','texto':'San Pablo entregado como municipio libre de minas antipersonal.'})
```
Generar como MÍNIMO estas vistas/módulos (cobertura de las 37 slides), agrupadas por sección:
- **dni:** compare desplazamiento-anual/interanual, confinamiento; kpi firmantes-100, confinamiento-fcs-100; bars cneb-desplazamiento/confinamiento; geomap desminado-municipios; geomap desaparecidos-cuerpos; line nna-desvinculacion; timeline acuerdos-fcs, acuerdos-cneb; logro san-pablo-libre-minas; compare minas.
- **seguridad:** geomap homicidios-municipio; bars hist-homicidios-gob; table/bars terrorismo-nacional-narino; bars fuerza-publica; ranking homicidios-departamental; module/list estructuras-armadas.
- **convivencia:** table convivencia (intrafamiliar/lesiones/feminicidio); table hurtos; logro/list hallazgos-clave.
- **estrategia:** module subsecretaria; module narino-360.
- **transformaciones:** bars ipm; table indicadores-sociales; kpi/compare desocupacion; bars pib.

- [ ] **Step 3: Escribir `scripts/validate-views.py`**

```python
import json, pathlib, sys
ROOT = pathlib.Path(__file__).resolve().parent.parent
VIEW_REQ = {'vista','titulo','descripcion','tipo_grafico_sugerido','categoria'}
MOD_TYPES = {'kpi','compare','timeline','logro'}
errs=[]; n=0
for p in (ROOT/'data/views').rglob('*.json'):
    n+=1; d=json.load(open(p,encoding='utf-8'))
    if 'modulo' in d:
        if d['modulo'] not in MOD_TYPES: errs.append(f'{p}: modulo inválido {d["modulo"]}')
        if 'id' not in d or 'titulo' not in d: errs.append(f'{p}: modulo sin id/titulo')
    else:
        miss = VIEW_REQ - set(d)
        if miss: errs.append(f'{p}: faltan campos {miss}')
        if 'municipios' not in d and 'datos' not in d: errs.append(f'{p}: sin municipios/datos')
if errs: print('\n'.join(errs)); sys.exit(1)
print(f'VIEWS OK: {n} archivos válidos')
```

- [ ] **Step 4: Generar y validar**

Run:
```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
python scripts/build-views.py
python scripts/validate-views.py
```
Expected: `build-views` imprime cada archivo; `validate-views` → `VIEWS OK: N archivos válidos` (N ≥ 25). Verificar manualmente que `data/views/seguridad/homicidios-municipio.json` tiene 64 municipios y valores reales (Samaniego 47.6, Barbacoas 17.1).

- [ ] **Step 5: Commit (bump a 0.2.0)**

Actualizar `SPZ_VERSION`/header a `0.2.0` y añadir entrada CHANGELOG `## [0.2.0]` (datos semilla generados). Luego:
```bash
git add scripts data/views suite-paz.php CHANGELOG.md
git commit -m "feat: datos semilla de las 37 slides (vistas + módulos) con datos reales de municipios (v0.2.0)"
```

---

### Task 3: Data provider + chart types (lectura de vistas + compatibilidad d3plus)

**Files:**
- Create: `includes/class-spz-data-provider.php`
- Create: `includes/class-spz-chart-types.php`
- Modify: `includes/class-spz-plugin.php` (instanciar un data-provider por sección; guardar `chart_types`)

**Interfaces:**
- Consumes: `SPZ_Security` (Task 1), JSON de `data/views/<seccion>/` (Task 2).
- Produces:
  - `SPZ_Data_Provider(SPZ_Security $sec, string $seccion)` con: `list_views():array` (metadatos de cada vista/módulo de la sección), `get_view(string $slug):?array` (payload normalizado con `dimensions`/`measures` inferidas), `get_raw(string $slug):?array`. Caché en memoria con prefijo `<seccion>:`.
  - `SPZ_Chart_Types` con: `all():array` (15 tipos d3plus con metadatos), `compatible_for(array $view):array` (tipos compatibles según categoría/dims/measures), `is_valid_type(string):bool`.
  - `SPZ_Plugin->data_provider(?string $seccion):SPZ_Data_Provider`, `->chart_types`.

- [ ] **Step 1: Leer referencias**

`cat` de `class-tsg-data-provider.php` y `class-tsg-chart-types.php` (en el tic-suite clonado). Reutilizar la lógica de inferencia de dims/measures (usa `is_int`/`is_float`, no `is_numeric`, para no promover DIVIPOLA string a medida) y la de compatibilidad por categoría. Adaptar prefijos y el path a `data/views/<seccion>/`.

- [ ] **Step 2: Escribir `class-spz-data-provider.php`**

Adaptación del de tic-suite: el constructor recibe `$seccion` (antes `$project`); las lecturas usan `$sec->safe_view_path($this->seccion, $slug)`. Debe DETECTAR módulos: si el JSON tiene `modulo`, `get_view` devuelve el payload tal cual con `is_module=>true` (los módulos no infieren dims/measures). Para vistas, inferir `dimensions`/`measures`/`category` de la primera fila como en tic-suite.

- [ ] **Step 3: Escribir `class-spz-chart-types.php`**

Copia adaptada del de tic-suite (15 tipos: bar, stacked_bar, line, area, stacked_area, pie, donut, treemap, geomap, network, tree, sankey, rings, box_whisker, priestley) con su tabla de compatibilidad por categoría. `compatible_for` devuelve `[]` para módulos.

- [ ] **Step 4: Cablear en el singleton**

En `class-spz-plugin.php` (constructor), tras crear `security`:
```php
$this->chart_types = new SPZ_Chart_Types();
foreach ( array_keys( self::SECCIONES ) as $sec ) {
    $this->data_providers[ $sec ] = new SPZ_Data_Provider( $this->security, $sec );
}
```
Añadir propiedad `public SPZ_Chart_Types $chart_types;`, `private array $data_providers=[];`, método `data_provider(?string $seccion=null):SPZ_Data_Provider`.

- [ ] **Step 5: Validar**

`php -l` de los dos archivos nuevos (si php disponible). Además, prueba de lectura sin WP con un stub mínimo:
Create `scripts/smoke-provider.php` (temporal, no se commitea):
```php
<?php
// Stubs mínimos de WP para probar la lógica pura de inferencia fuera de WordPress.
function sanitize_key($s){return preg_replace('/[^a-z0-9_\-]/','',strtolower($s));}
define('ABSPATH', __DIR__); define('SPZ_DATA_DIR', __DIR__.'/data/');
// cargar clases y ejercitar SPZ_Data_Provider->get_view('homicidios-municipio') sobre la sección 'seguridad'
```
Run: `php scripts/smoke-provider.php` → imprime dims=['municipio'], measures incluye 'tasa_homicidio_2025', 64 filas. Si php no está: el implementador razona la corrección leyendo el código y confía en la validación de render (Task 4). Borrar el smoke script tras la prueba.

- [ ] **Step 6: Commit (v0.3.0)**

```bash
git add includes/class-spz-data-provider.php includes/class-spz-chart-types.php includes/class-spz-plugin.php suite-paz.php CHANGELOG.md
git commit -m "feat: data provider por sección + registro de 15 tipos d3plus (v0.3.0)"
```

---

### Task 4: Renderer d3plus + frontend + harness de validación

**Files:**
- Create: `assets/js/renderer.js`
- Create: `assets/js/frontend.js`
- Create: `assets/css/frontend.css`
- Create: `tests/harness.html` (no se instala; sirve para Playwright)
- Create: `tests/serve-views.py` (sirve views como JSON para el harness)

**Interfaces:**
- Consumes: los JSON de vistas (Task 2), d3plus CDN, el topojson.
- Produces:
  - `SPZ.renderer.render(el, {view, type, options})` global — pinta un gráfico/mapa d3plus dentro de `el` a partir de un payload de vista. Reutiliza la lógica de tic-suite: normaliza `municipio`→`_municipio_id`, geomap sin basemap/ocean transparente, tooltips es-CO, leyenda al bottom, filtrado de municipios sin datos.
  - `SPZ.frontend` que localiza contenedores `.spz-chart[data-view][data-type][data-seccion]`, pide el payload por REST (con nonce) y llama al renderer.

- [ ] **Step 1: Leer y adaptar `renderer.js` de tic-suite**

`cat "$TS/assets/js/renderer.js"`. Es la pieza clave del render d3plus (28 KB). Adaptar: namespace `TSG`→`SPZ`, mismos parámetros. Mantener `waitForD3plus()`, la construcción por tipo, el join geomap por `_municipio_id`, tooltips, leyenda. El topojson se carga desde `SPZ_PLUGIN_URL + 'data/topo/narino_municipios.topojson'` (en el harness, ruta relativa).

- [ ] **Step 2: Escribir `frontend.js` y `frontend.css`**

`frontend.js`: al `DOMContentLoaded`, por cada `.spz-chart`, `fetch` a `/wp-json/suite-paz/v1/render?seccion=..&view=..&type=..` con header `X-WP-Nonce` (en el harness se sirve el JSON directo) y llama `SPZ.renderer.render`. `frontend.css`: contenedores `.spz-chart`/`.spz-module` con altura, paleta de marca, SVG responsive (adaptar de tic-suite `frontend.css`).

- [ ] **Step 3: Harness de prueba (sin WordPress)**

Create `tests/harness.html`:
```html
<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<script src="https://cdn.jsdelivr.net/npm/@d3plus/core@3.1.4/umd/d3plus-core.full.js"></script>
<link rel="stylesheet" href="../assets/css/frontend.css">
<style>.spz-chart{height:460px}</style></head><body>
<h3>Geomap homicidios</h3>
<div class="spz-chart" id="c1"></div>
<script src="../assets/js/renderer.js"></script>
<script>
fetch('../data/views/seguridad/homicidios-municipio.json').then(r=>r.json()).then(view=>{
  SPZ.renderer.render(document.getElementById('c1'), {view, type:'geomap', options:{}});
});
</script></body></html>
```
Servir: `cd tests && python -m http.server 8765` (background) — pero como `harness.html` usa rutas `../data`, servir desde la RAÍZ del plugin: `cd "C:/Users/Usuario/.claude/plugins/suite-paz" && python -m http.server 8765`, y abrir `http://localhost:8765/tests/harness.html`.

- [ ] **Step 4: Validar render CON datos (Playwright)**

Playwright MCP (no puede file://): navegar `http://localhost:8765/tests/harness.html`. Esperar ~4s (d3plus + topojson). Validar:
- `() => !!document.querySelector('#c1 svg')` → `true` (el geomap pintó un SVG).
- `() => document.querySelectorAll('#c1 svg path').length > 50` → `true` (64 municipios dibujados).
- Screenshot de `#c1`: debe verse el mapa de Nariño con municipios coloreados según la tasa (Samaniego más intenso). **Este es el criterio que corrige "mapas sin datos".**
- Consola sin errores de d3plus ("Tipo de gráfico no soportado" = fallo).
Repetir con un `type='bar'` sobre una vista categórica para confirmar barras con datos.

- [ ] **Step 5: Commit (v0.4.0)**

```bash
git add assets/js/renderer.js assets/js/frontend.js assets/css/frontend.css tests suite-paz.php CHANGELOG.md
git commit -m "feat: renderer d3plus + frontend + harness; mapas y gráficos pintan con datos (v0.4.0)"
```

---

### Task 5: Shortcode de gráficos + REST API

**Files:**
- Create: `includes/class-spz-shortcode.php`
- Create: `includes/class-spz-rest-api.php`
- Modify: `includes/class-spz-plugin.php` (cablear shortcode + rest + enqueue de assets)

**Interfaces:**
- Consumes: data-provider, chart-types, security.
- Produces:
  - Shortcode `[spz_grafico view="" type="" seccion="" height="" title="" theme=""]` → emite un `<div class="spz-chart" data-...>` (SIN datos inline) + encola assets.
  - REST: `GET /suite-paz/v1/render?seccion&view&type` → `{view, data, compatible}` con permiso público de lectura pero validando whitelist; `GET /suite-paz/v1/views?seccion` → lista. Nonce para escritura (Task 7).
  - `SPZ_Plugin->run()` registra el shortcode, las rutas REST y encola `renderer.js`/`frontend.js`/`frontend.css` + `wp_localize_script` con `{restUrl, nonce, pluginUrl}`.

- [ ] **Step 1: Adaptar de tic-suite**

`cat "$TS/includes/class-tsg-shortcode.php"` y `class-tsg-rest-api.php`. Adaptar prefijos, `project`→`seccion`, namespace `suite-paz/v1`. Mantener sanitización de atributos y la whitelist de `type` contra `chart_types`.

- [ ] **Step 2: Escribir shortcode + REST**

Shortcode `render_grafico($atts)`: sanitiza atributos (`sanitize_key` view/type/seccion, `absint` height, `sanitize_text_field` title), valida `seccion` y `type`; devuelve el HTML del contenedor. REST `register_routes()`: ruta `render` con `permission_callback` que valida whitelist; `callback` usa `data_provider($seccion)->get_view($view)` + `chart_types->compatible_for()`.

- [ ] **Step 3: Cablear en el singleton**

En `class-spz-plugin.php`: instanciar `$this->shortcode = new SPZ_Shortcode($this, $this->chart_types, $this->security);` y `$this->rest_api = new SPZ_Rest_Api($this, $this->chart_types, $this->security);`. En `run()`: `$this->shortcode->register();`, `add_action('rest_api_init', [$this->rest_api,'register_routes']);`, y `enqueue_public_assets()` que hace `wp_enqueue_script` de d3plus (SPZ_D3PLUS_URL), renderer, frontend, y `wp_localize_script('spz-frontend','SPZ_CFG',[...])`.

- [ ] **Step 4: Validar**

`php -l` de los archivos nuevos. Validación de integración real se hará en la prueba de instalación en WP (documentada). Verificar por revisión: la ruta REST arma el mismo payload que el harness consumió en Task 4 (mismos campos `view`/`data`).

- [ ] **Step 5: Commit (v0.5.0)**

```bash
git add includes/class-spz-shortcode.php includes/class-spz-rest-api.php includes/class-spz-plugin.php suite-paz.php CHANGELOG.md
git commit -m "feat: shortcode [spz_grafico] + REST /render con seguridad (v0.5.0)"
```

---

### Task 6: Módulos PAZ nativos (kpi/compare/timeline/logro) + shortcodes

**Files:**
- Create: `includes/class-spz-modules.php`
- Create: `assets/js/modules.js`
- Modify: `includes/class-spz-shortcode.php` (añadir shortcodes de módulos + `[spz_seccion]`)
- Modify: `assets/css/frontend.css` (estilos de módulos)
- Modify: `includes/class-spz-plugin.php` (encolar modules.js)

**Interfaces:**
- Consumes: data-provider (lee JSON de módulo), REST render.
- Produces:
  - `SPZ_Modules` con `types():array` (`kpi,compare,timeline,logro`), `is_valid(string):bool`.
  - Shortcodes `[spz_kpi id seccion]`, `[spz_compare id seccion]`, `[spz_timeline id seccion]`, `[spz_logro id seccion]`, `[spz_seccion id]` → contenedores `.spz-module[data-modulo][data-id][data-seccion]`.
  - `SPZ.modules.render(el, payload)` global (en `modules.js`): pinta kpi (count-up + delta), compare (antes→después + %), timeline (hitos), logro (tarjeta). Respeta `prefers-reduced-motion`. La lógica de count-up/delta reutiliza los patrones del dashboard previo (bajar = positivo → verde).

- [ ] **Step 1: Escribir `class-spz-modules.php`**

Registro de los 4 tipos con validación. El data-provider ya devuelve `is_module=>true`; los shortcodes de módulo consultan `data_provider($seccion)->get_view($id)` y verifican `modulo`.

- [ ] **Step 2: Escribir `assets/js/modules.js`**

```javascript
window.SPZ = window.SPZ || {};
SPZ.modules = (function(){
  const fmt = n => (n==null?'—':Number(n).toLocaleString('es-CO'));
  const reduced = () => matchMedia('(prefers-reduced-motion:reduce)').matches;
  function countUp(el,to){ if(reduced()){el.textContent=fmt(to);return;} const t0=performance.now();
    (function step(t){const p=Math.min(1,(t-t0)/1000);el.textContent=fmt(Math.round(to*(1-Math.pow(1-p,3))));
      if(p<1)requestAnimationFrame(step);})(performance.now()); }
  function deltaTag(d){const good=d<=0;const a=d<0?'▼':(d>0?'▲':'—');
    return `<span class="spz-delta ${good?'good':'bad'}">${a} ${Math.abs(d).toLocaleString('es-CO')}%</span>`;}
  function kpi(el,d){el.innerHTML=`<div class="spz-kpi"><span class="spz-kpi__k">${d.titulo}</span>
    <b class="spz-kpi__v" data-cu="${d.valor}">0</b><span class="spz-kpi__u">${d.unidad||''}</span>
    <small>${d.leyenda||''}</small></div>`; countUp(el.querySelector('[data-cu]'),d.valor);}
  function compare(el,d){el.innerHTML=`<div class="spz-compare"><h4>${d.titulo}</h4>
    <div class="spz-compare__row"><div><small>${d.from.y}</small><b>${fmt(d.from.v)}</b></div><span>→</span>
    <div><small>${d.to.y}</small><b>${fmt(d.to.v)}</b></div></div>${deltaTag(d.delta)}
    <div class="spz-compare__u">${d.unidad||''}</div>${d.fuente?`<p class="spz-src">Fuente: ${d.fuente}</p>`:''}</div>`;}
  function timeline(el,d){el.innerHTML=`<div class="spz-timeline"><h4>${d.titulo}</h4>
    ${d.total?`<span class="spz-timeline__k">${d.total} acuerdos</span>`:''}
    <ol>${d.eventos.map(e=>`<li><time>${e.fecha}</time><p>${e.texto}</p></li>`).join('')}</ol></div>`;}
  function logro(el,d){el.innerHTML=`<div class="spz-logro"><h4>${d.titulo}</h4><p>${d.texto}</p></div>`;}
  const R={kpi,compare,timeline,logro};
  return { render(el,payload){ const fn=R[payload.modulo]; if(fn) fn(el,payload);
    else el.innerHTML='<em>Módulo no soportado</em>'; } };
})();
```

- [ ] **Step 3: Shortcodes de módulo + `[spz_seccion]`**

En `class-spz-shortcode.php` añadir `render_kpi/compare/timeline/logro($atts)` (todos emiten `.spz-module[data-modulo][data-id][data-seccion]`) y `render_seccion($atts)` que lista los módulos/vistas de la sección en orden. `frontend.js` (Task 4) debe extenderse para también localizar `.spz-module`, pedir el payload por REST y llamar `SPZ.modules.render`.

- [ ] **Step 4: CSS de módulos**

Añadir a `frontend.css`: `.spz-kpi__v{font-size:clamp(2rem,5vw,3.4rem);font-weight:800;color:#5B3B8C}`, `.spz-delta.good{color:#2FA87A}`, `.spz-delta.bad{color:#E63946}`, `.spz-compare`, `.spz-timeline`, `.spz-logro` con la paleta de marca.

- [ ] **Step 5: Validar en el harness**

Añadir a `tests/harness.html` un bloque por módulo cargando `../data/views/dni/firmantes-100.json` etc. y llamando `SPZ.modules.render`. Playwright:
- `() => document.querySelector('.spz-kpi__v').textContent` tras 1.2s → contiene el número (count-up corrió).
- `() => document.querySelectorAll('.spz-timeline li').length > 0` → true.
- Screenshot mostrando KPI + compare + timeline con la paleta de marca.

- [ ] **Step 6: Commit (v0.6.0)**

```bash
git add includes/class-spz-modules.php includes/class-spz-shortcode.php assets/js/modules.js assets/js/frontend.js assets/css/frontend.css includes/class-spz-plugin.php tests suite-paz.php CHANGELOG.md
git commit -m "feat: módulos PAZ nativos (kpi/compare/timeline/logro) + shortcodes (v0.6.0)"
```

---

### Task 7: Data store (BD) + editor por REST (item 4)

**Files:**
- Create: `includes/class-spz-data-store.php`
- Modify: `includes/class-spz-data-provider.php` (resolver override de BD antes que el JSON)
- Modify: `includes/class-spz-rest-api.php` (rutas `save`/`reset`/`export`)
- Modify: `includes/class-spz-plugin.php` (crear tabla en `activate()`)

**Interfaces:**
- Consumes: security, `$wpdb`.
- Produces:
  - `SPZ_Data_Store` con: `table():string` (`{$wpdb->prefix}spz_views`), `create_table():void` (dbDelta), `get_override(string $sec,string $slug):?array`, `save_override(string $sec,string $slug,array $payload):bool`, `delete_override(string $sec,string $slug):bool`, `all_overrides():array`.
  - `SPZ_Data_Provider->get_view()` ahora consulta `SPZ_Data_Store->get_override()` primero; si existe, lo usa; si no, el JSON.
  - REST: `POST /suite-paz/v1/save` (nonce + capability) body `{seccion, slug, payload}` → valida esquema, guarda override; `POST /reset` borra override; `GET /export?seccion&slug` devuelve el estado actual (override o semilla).

- [ ] **Step 1: Escribir `class-spz-data-store.php`**

```php
final class SPZ_Data_Store {
  public function table(): string { global $wpdb; return $wpdb->prefix . 'spz_views'; }
  public function create_table(): void {
    global $wpdb; require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $t = $this->table(); $charset = $wpdb->get_charset_collate();
    dbDelta( "CREATE TABLE $t (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      seccion VARCHAR(40) NOT NULL, slug VARCHAR(120) NOT NULL,
      payload LONGTEXT NOT NULL, updated_at DATETIME NOT NULL,
      PRIMARY KEY (id), UNIQUE KEY sec_slug (seccion, slug)
    ) $charset;" );
  }
  public function get_override( string $sec, string $slug ): ?array {
    global $wpdb; $t=$this->table();
    $row=$wpdb->get_var($wpdb->prepare("SELECT payload FROM $t WHERE seccion=%s AND slug=%s",$sec,$slug));
    if(null===$row){return null;} $d=json_decode($row,true); return is_array($d)?$d:null;
  }
  public function save_override( string $sec, string $slug, array $payload ): bool {
    global $wpdb; $t=$this->table();
    return false!==$wpdb->replace($t,[ 'seccion'=>$sec,'slug'=>$slug,
      'payload'=>wp_json_encode($payload),'updated_at'=>current_time('mysql') ],['%s','%s','%s','%s']);
  }
  public function delete_override( string $sec, string $slug ): bool {
    global $wpdb; return false!==$wpdb->delete($this->table(),['seccion'=>$sec,'slug'=>$slug],['%s','%s']);
  }
}
```

- [ ] **Step 2: Resolver override en el data-provider**

En `get_view()`, al inicio: `$ov = $this->store->get_override($this->seccion,$slug); if($ov){ return $this->normalize($ov); }` (inyectar `SPZ_Data_Store` por constructor o vía singleton). Mantener el fallback al JSON.

- [ ] **Step 3: Rutas REST de escritura**

`POST /save`: `permission_callback` = `current_user_can_manage()` + nonce; sanitiza `seccion` (whitelist), `slug` (`sanitize_key`), y **valida el payload** con la misma lógica que `validate-views.py` reimplementada en PHP (`SPZ_Security->validate_payload(array):bool`): claves permitidas, tipos correctos, sin scripts. Guarda vía `save_override`. `POST /reset` → `delete_override`. `GET /export` → override o semilla.

- [ ] **Step 4: Crear tabla en activación**

En `SPZ_Plugin::activate()`: `(new SPZ_Data_Store())->create_table();`. En `uninstall.php`: `DROP TABLE`.

- [ ] **Step 5: Validar**

`php -l`. Prueba de lógica pura del validador de payload con un stub (como Task 3 smoke): payload válido → true; payload con clave no permitida o `<script>` → false. Documentar que la prueba BD real se hace en WP.

- [ ] **Step 6: Commit (v0.7.0)**

```bash
git add includes/class-spz-data-store.php includes/class-spz-data-provider.php includes/class-spz-rest-api.php includes/class-spz-plugin.php includes/class-spz-security.php suite-paz.php CHANGELOG.md
git commit -m "feat: data store en BD + overrides editables + REST save/reset/export (v0.7.0)"
```

---

### Task 8: Admin — menús, constructor, galería de shortcodes, EDITOR de datos, ajustes

**Files:**
- Create: `includes/class-spz-admin.php`
- Create: `templates/admin/builder.php`, `templates/admin/shortcodes.php`, `templates/admin/data-editor.php`, `templates/admin/settings.php`
- Create: `assets/js/admin.js`, `assets/css/admin.css`
- Modify: `includes/class-spz-plugin.php` (cablear admin + enqueue admin assets)

**Interfaces:**
- Consumes: plugin (secciones), data-provider, chart-types, modules, rest-api.
- Produces:
  - Menú superior **"Suite PAZ"** (dashicon) → submenús: **Constructor** (por sección, con selector), **Shortcodes**, **Editar datos**, **Ajustes**.
  - `templates/admin/data-editor.php`: selector sección→vista/módulo; renderiza un formulario/tabla editable de las filas/campos; `admin.js` envía `POST /save` (nonce), muestra "Guardado", y botones **Exportar**/**Restablecer**.
  - `admin.js`: lógica del constructor (preview + shortcode generado) y del editor (cargar/guardar/reset/export por REST).

- [ ] **Step 1: Adaptar de tic-suite**

`cat "$TS/includes/class-tsg-admin.php"` y `templates/admin/{builder,shortcodes,data}.php`, `assets/js/admin.js`, `assets/css/admin.css`. El **Constructor** y **Shortcodes** se adaptan casi directo (project→seccion, prefijos). El **"Datos de vista"** de tic-suite es solo lectura: aquí se reemplaza por **`data-editor.php`** editable.

- [ ] **Step 2: Menús admin**

`class-spz-admin.php` `register()`: `add_menu_page('Suite PAZ', ...)` + `add_submenu_page` para Constructor/Shortcodes/Editar datos/Ajustes, todos con capability `manage_options`. Cada callback incluye su template.

- [ ] **Step 3: Editor de datos**

`data-editor.php`: un `<select>` de sección + un `<select>` de vista/módulo (poblado por REST `views`). Al elegir, `admin.js` hace `GET /export?seccion&slug`, construye un formulario: para vistas geográficas/tabulares una **tabla editable** (una fila por registro, inputs por medida); para módulos, inputs por campo (`valor`, `from.v`, `to.v`, `delta`, eventos, texto). Botón **Guardar** → `POST /save` con nonce. Botones **Exportar JSON** (descarga) y **Restablecer** (`POST /reset`). Feedback visible.

- [ ] **Step 4: admin.js + admin.css**

`admin.js`: usa `SPZ_ADMIN_CFG` (localizado: restUrl, nonce). Constructor: al elegir vista, muestra tipos compatibles y genera el shortcode `[spz_grafico ...]` copiable. Editor: cargar/editar/guardar/reset/export. `admin.css`: paleta de marca, layout de 3 paneles (constructor) y tabla-formulario (editor).

- [ ] **Step 5: Cablear admin en el singleton**

`class-spz-plugin.php`: `$this->admin = new SPZ_Admin($this, $this->chart_types, $this->security);` y en `run()` `if(is_admin()){ $this->admin->register(); }` + `admin_enqueue_scripts` que encola `admin.js`/`admin.css` + `wp_localize_script`.

- [ ] **Step 6: Validar**

`php -l` de admin + templates. Como el admin requiere WP, la validación funcional se documenta como prueba de instalación. Verificar por revisión: nonces presentes en cada acción, capability en cada callback, escapado (`esc_html`/`esc_attr`) en los templates.

- [ ] **Step 7: Commit (v0.8.0)**

```bash
git add includes/class-spz-admin.php templates assets/js/admin.js assets/css/admin.css includes/class-spz-plugin.php suite-paz.php CHANGELOG.md
git commit -m "feat: admin — constructor, shortcodes, EDITOR de datos, ajustes (v0.8.0)"
```

---

### Task 9: README, empaquetado, validación final, push y PR "actualizar-paz"

**Files:**
- Modify: `README.md` (completo)
- Create: `readme.txt` (formato WordPress.org, opcional pero recomendado)
- Modify: `CHANGELOG.md`, `suite-paz.php` (bump a `1.0.0`)

**Interfaces:**
- Produces: repo listo, PR abierto.

- [ ] **Step 1: README completo**

Reescribir `README.md`: descripción, instalación (clonar/zip en `wp-content/plugins/suite-paz`, activar), **tabla de shortcodes** (`[spz_grafico]`, `[spz_kpi]`, `[spz_compare]`, `[spz_timeline]`, `[spz_logro]`, `[spz_seccion]` con sus atributos), **secciones** (las 5), **editor de datos** (cómo editar/guardar/restablecer), **arquitectura** (árbol de archivos), **seguridad**, y **cómo prueba el usuario la instalación en WordPress**. Incluir nota de que d3plus se carga por CDN (requiere internet en el front).

- [ ] **Step 2: Validación final consolidada**

- `python scripts/validate-views.py` → OK.
- `php -l` de todos los `.php` (si php disponible) → sin errores.
- Harness Playwright: reconfirmar geomap CON datos + un módulo KPI. Screenshots finales.
- Revisión de seguridad: grep de que cada ruta REST de escritura tiene `permission_callback` y nonce; que ningún template hace echo sin escapar; que no hay `eval`.
Registrar resultados.

- [ ] **Step 3: Empaquetar zip (para instalación)**

```bash
cd "C:/Users/Usuario/.claude/plugins"
zip -r suite-paz/dist/suite-paz.zip suite-paz -x 'suite-paz/.git/*' 'suite-paz/docs/*' 'suite-paz/tests/*' 'suite-paz/dist/*' 'suite-paz/scripts/smoke*'
```
(El zip es artefacto; `dist/` está gitignored.)

- [ ] **Step 4: Bump a 1.0.0 y commit**

Actualizar header/`SPZ_VERSION` a `1.0.0` y CHANGELOG `## [1.0.0]` (resumen de features). Commit:
```bash
git add -A
git commit -m "docs: README completo + readme.txt + validación final (v1.0.0)"
```

- [ ] **Step 5: Push de la rama**

```bash
cd "C:/Users/Usuario/.claude/plugins/suite-paz"
bash scripts/gitpush.sh actualizar-paz
```
Expected: push OK a `origin/actualizar-paz`.

- [ ] **Step 6: Abrir el PR "actualizar-paz" por API**

```bash
SP="C:/Users/Usuario/AppData/Local/Temp/claude/C--Users-Usuario--claude-plugins-paz/2a51b90b-9b79-4187-8d16-3c803d0a5aeb/scratchpad"
TOK=$(cat "$SP/.gh_token")
curl -s -X POST -H "Authorization: Bearer $TOK" -H "Accept: application/vnd.github+json" \
  https://api.github.com/repos/GobernaciondeNarino/suite-paz/pulls \
  -d '{"title":"actualizar-paz","head":"actualizar-paz","base":"main","body":"Plugin Suite PAZ v1.0.0: recreación de tic-suite para el proyecto de Paz. Vistas y módulos con datos reales de los 64 municipios, mapas coropléticos, editor de datos en BD, shortcodes para maquetación manual. Ver CHANGELOG.md."}' \
  | python -c "import sys,json;d=json.load(sys.stdin);print('PR:',d.get('html_url') or d)"
unset TOK
```
Expected: imprime la URL del PR. Si ya existe, GitHub devuelve error de PR existente (aceptable — significa que ya está abierto).

- [ ] **Step 7: Verificar entrega**

Run: `git log --oneline -5` y confirmar que `origin/actualizar-paz` está al día (`git status`). Reportar la URL del PR al usuario.

---

## Notas de implementación

- **Recreación fiel:** para cada archivo derivado de tic-suite, LEER el original en el repo clonado y adaptar (prefijos, `project`→`seccion`, namespace). No reinventar la seguridad ni el renderer — adaptarlos.
- **Cifras verbatim:** el catálogo (`scripts/paz_catalog.py`) es la fuente única; sale del spec §9 del proyecto previo. Los mapas SIEMPRE con datos reales de municipios (corrige la queja original).
- **Versionado:** cada tarea sube el SemVer del header + una entrada en CHANGELOG. Commits atómicos en `actualizar-paz`.
- **Seguridad:** capability + nonce + whitelist + escapado + anti-traversal en TODA entrada. El validador de payload (PHP) refleja `validate-views.py`.
- **Sin WP local:** la validación offline (JSON/esquema/harness/lint) no sustituye la prueba de activación en WordPress, que documenta el README para el usuario.
- **Token:** nunca se comitea; el push usa `scripts/gitpush.sh` (askpass). Recordar al usuario rotar el PAT al finalizar.
```
