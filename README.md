# Suite PAZ

**Plugin de WordPress** para la **Gobernación de Nariño** que publica los datos del proyecto de Paz "Así llegó la paz a Nariño" como **gráficos interactivos, mapas coropléticos y módulos de datos** insertables en cualquier página mediante **shortcodes**, con un **editor de datos** integrado en el panel de administración.

Recreación del plugin [`tic-suite`](https://github.com/GobernaciondeNarino/tic-suite) adaptada al proyecto de Paz.

---

## Descripción

Suite PAZ expone **34 vistas de datos** (gráficos y módulos) organizadas en 5 secciones temáticas. Los datos semilla viven en archivos JSON en `data/views/<seccion>/`; cualquier cambio realizado desde el panel de administración se persiste en la base de datos de WordPress (`wp_spz_views`) y tiene prioridad sobre el JSON de origen. El renderizado de gráficos corre 100 % en el cliente a través de [@d3plus/core v3.1.4](https://github.com/d3plus/d3plus) (CDN) — **se requiere conexión a Internet en el frontend**.

---

## Instalación

### Opción A — Clonar desde GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/GobernaciondeNarino/suite-paz.git
```

### Opción B — Instalar el ZIP

1. Descargar el ZIP del repositorio (botón **Code → Download ZIP** en GitHub).
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin**.
3. Seleccionar el ZIP y hacer clic en **Instalar ahora**.

### Activación

1. Ir a **Plugins → Plugins instalados**.
2. Activar **Suite PAZ**.
3. Al activar, el plugin crea automáticamente la tabla `wp_spz_views` en la base de datos.
4. Verificar que aparece el menú **Suite PAZ** en el panel lateral de administración.

---

## Shortcodes

### `[spz_grafico]` — Gráfico o mapa d3plus

Inserta un gráfico interactivo renderizado por d3plus. Los datos se cargan por REST (no se incluyen en el HTML).

| Atributo | Tipo     | Por defecto | Descripción |
|----------|----------|-------------|-------------|
| `view`   | string   | —           | Slug de la vista (requerido). Ej.: `homicidios-municipio` |
| `type`   | string   | —           | Tipo de gráfico (requerido). Ej.: `geomap`, `bar`, `line` |
| `seccion`| string   | `dni`       | Sección temática. Una de: `dni`, `seguridad`, `convivencia`, `estrategia`, `transformaciones` |
| `height` | integer  | `450`       | Altura del contenedor en píxeles |
| `title`  | string   | —           | Título opcional visible sobre el gráfico |
| `theme`  | string   | `default`   | Tema de estilos CSS. Por defecto usa la paleta de marca SPZ |

**Ejemplo:**
```
[spz_grafico view="homicidios-municipio" type="geomap" seccion="seguridad" height="500" title="Homicidios por municipio"]
```

**Tipos de gráfico disponibles** (d3plus v3.1.4):

| Slug           | d3plus Class    | Descripción |
|----------------|-----------------|-------------|
| `bar`          | BarChart        | Barras verticales |
| `stacked_bar`  | BarChart        | Barras apiladas |
| `line`         | LinePlot        | Líneas temporales |
| `area`         | AreaPlot        | Áreas |
| `stacked_area` | StackedArea     | Áreas apiladas |
| `pie`          | Pie             | Pastel |
| `donut`        | Donut           | Dona |
| `treemap`      | Treemap         | Treemap |
| `geomap`       | Geomap          | Mapa coroplético (64 municipios de Nariño) |
| `network`      | Network         | Red de nodos |
| `tree`         | Tree            | Árbol jerárquico |
| `sankey`       | Sankey          | Diagrama Sankey |
| `rings`        | Rings           | Anillos de red |
| `box_whisker`  | BoxWhisker      | Caja y bigotes |
| `priestley`    | Priestley       | Timeline de Priestley |

---

### `[spz_kpi]` — Módulo KPI

Muestra un indicador clave de rendimiento con efecto count-up animado y delta interanual.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | Slug de la vista módulo (requerido) |
| `seccion`| string | `dni`       | Sección temática |

**Ejemplo:**
```
[spz_kpi id="rutas-nna" seccion="dni"]
```

---

### `[spz_compare]` — Módulo Comparativo

Muestra una comparación antes/después con variación porcentual.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | Slug de la vista módulo (requerido) |
| `seccion`| string | `dni`       | Sección temática |

**Ejemplo:**
```
[spz_compare id="minas-interanual" seccion="dni"]
```

---

### `[spz_timeline]` — Módulo Línea de Tiempo

Muestra una línea de tiempo de hitos.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | Slug de la vista módulo (requerido) |
| `seccion`| string | `dni`       | Sección temática |

**Ejemplo:**
```
[spz_timeline id="acuerdos-cneb" seccion="dni"]
```

---

### `[spz_logro]` — Módulo Logro

Muestra una tarjeta de logro o hito destacado.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | Slug de la vista módulo (requerido) |
| `seccion`| string | `dni`       | Sección temática |

**Ejemplo:**
```
[spz_logro id="san-pablo-libre-minas" seccion="dni"]
```

---

### `[spz_seccion]` — Sección completa

Inserta automáticamente todos los módulos y gráficos de una sección en orden.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | ID de la sección (requerido). Una de las 5 secciones |

**Ejemplo:**
```
[spz_seccion id="seguridad"]
```

---

## Las 5 Secciones

| ID               | Etiqueta              | Vistas | Descripción |
|------------------|-----------------------|--------|-------------|
| `dni`            | DNI / Paz             | 19     | Datos del Departamento Nacional de Inteligencia y Paz: desplazamiento, confinamiento, minas antipersonal, NNA, firmantes, UBPD |
| `seguridad`      | Seguridad             | 6      | Homicidios por municipio (mapa), histórico departamental, estructuras armadas, fuerza pública, terrorismo nacional vs Nariño |
| `convivencia`    | Convivencia           | 3      | Convivencia ciudadana, hurtos, hallazgos clave |
| `estrategia`     | Estrategia            | 2      | Subsecretaría de Paz, Nariño 360 |
| `transformaciones` | Transformaciones    | 4      | IPM, desocupación, indicadores sociales, PIB |

---

## Editor de Datos

El panel **Suite PAZ → Editar datos** permite modificar cualquier vista o módulo directamente desde WordPress, sin editar archivos JSON.

### Flujo de trabajo

1. Seleccionar la **sección** en el menú desplegable.
2. Seleccionar la **vista o módulo** a editar.
3. El editor carga los datos actuales (JSON de BD si hay override, o JSON semilla).
4. Editar los valores en la tabla o formulario interactivo.
5. Hacer clic en **Guardar** para persistir en `wp_spz_views`.
6. Para descargar el JSON editado, hacer clic en **Exportar**.
7. Para volver a los datos originales del JSON semilla, hacer clic en **Restablecer**.

### Persistencia

- Los datos guardados se almacenan en la tabla `wp_spz_views` (columnas: `seccion`, `slug`, `payload` JSON, `updated_at`).
- Un override en BD siempre tiene prioridad sobre el JSON de `data/views/<seccion>/<slug>.json`.
- La fuente activa se indica con un badge **override** (BD) o **seed** (JSON) en el editor.

---

## Arquitectura

```
suite-paz/
├── suite-paz.php               # Punto de entrada del plugin (header WP, constantes, autoloader)
├── uninstall.php               # Limpieza al desinstalar (elimina tabla wp_spz_views)
├── CHANGELOG.md
├── readme.txt                  # Formato WordPress.org
│
├── includes/
│   ├── class-spz-plugin.php    # Singleton principal; instancia y cablea todos los componentes
│   ├── class-spz-security.php  # Capability checks, nonce verification, sanitización, anti-traversal
│   ├── class-spz-data-provider.php # Lee vistas/módulos: BD override → JSON semilla
│   ├── class-spz-data-store.php    # CRUD en wp_spz_views ($wpdb->prepare en todas las queries)
│   ├── class-spz-chart-types.php   # Registro de 15 tipos d3plus; compatible_for(view)
│   ├── class-spz-modules.php       # Registro de tipos de módulo (kpi/compare/timeline/logro)
│   ├── class-spz-shortcode.php     # Handlers de los 6 shortcodes
│   ├── class-spz-rest-api.php      # Rutas REST (suite-paz/v1)
│   └── class-spz-admin.php         # Menú de administración (4 subpáginas)
│
├── assets/
│   ├── js/
│   │   ├── renderer.js         # SPZ.renderer — wrapper d3plus (15 tipos, geomap, módulos)
│   │   ├── frontend.js         # Hidratador WP: escanea .spz-chart/.spz-module, fetch REST, render
│   │   ├── modules.js          # SPZ.modules.render — kpi/compare/timeline/logro
│   │   └── admin.js            # Constructor + Editor de datos (panel admin)
│   └── css/
│       ├── frontend.css        # Estilos del frontend (paleta SPZ, módulos, gráficos)
│       └── admin.css           # Estilos del panel admin
│
├── data/
│   ├── topo/
│   │   └── narino_municipios.topojson  # 64 municipios de Nariño (TopoJSON)
│   └── views/
│       ├── dni/                # 19 vistas (gráficos + módulos)
│       ├── seguridad/          # 6 vistas
│       ├── convivencia/        # 3 vistas
│       ├── estrategia/         # 2 vistas
│       └── transformaciones/   # 4 vistas
│
├── templates/
│   └── admin/
│       ├── builder.php         # Constructor de shortcodes
│       ├── shortcodes.php      # Galería de shortcodes disponibles
│       ├── data-editor.php     # Editor de datos en BD
│       └── settings.php        # Ajustes del plugin
│
├── scripts/
│   ├── build-views.py          # Generador de vistas JSON semilla
│   ├── validate-views.py       # Validador de esquema JSON (34 vistas)
│   └── gitpush.sh              # Push seguro con ASKPASS
│
└── tests/
    └── harness.html            # Harness Playwright para validación de renderer
```

### Rutas REST (`/wp-json/suite-paz/v1/`)

| Método | Ruta             | Acceso | Descripción |
|--------|-----------------|--------|-------------|
| GET    | `/render`        | Público | Payload de render: datos + mapping + tipos compatibles |
| GET    | `/views`         | Admin   | Lista de vistas de una sección |
| GET    | `/views/{slug}`  | Admin   | Metadatos de una vista + tipos compatibles |
| POST   | `/save`          | Admin   | Guardar override en BD (nonce requerido) |
| POST   | `/reset`         | Admin   | Eliminar override (restaurar semilla) |
| GET    | `/export`        | Admin   | Exportar JSON de una vista (override o semilla) |

---

## Seguridad

- **Capability**: todas las rutas de escritura y los paneles de administración requieren `manage_options`.
- **Nonce**: cada petición REST de escritura (`/save`, `/reset`) verifica el nonce de WordPress (`X-WP-Nonce`).
- **Sanitización**: todos los parámetros de entrada pasan por `sanitize_key()`, `sanitize_text_field()` o `absint()` según su tipo.
- **Whitelist**: los parámetros `seccion`, `view` y `type` se validan contra listas blancas internas (`SECCIONES`, slugs de vistas, tipos de gráfico) — ningún valor arbitrario llega a la capa de datos.
- **Anti-traversal**: `realpath()` verifica que la ruta del JSON quede dentro de `data/views/<seccion>/`; se rechaza cualquier intento de saltar directorios (`../`).
- **Escapado de salida**: todos los valores de texto en los módulos JS pasan por `esc()` antes de insertarse en el DOM; las plantillas PHP usan `esc_html()` / `esc_attr()` / `esc_url()`.
- **Sin `eval`**: ni el PHP ni el JavaScript del plugin usan `eval`.
- **Queries seguras**: todas las queries a `wp_spz_views` usan `$wpdb->prepare()`.

---

## Cómo probar la instalación en WordPress

1. **Instalar y activar** el plugin siguiendo los pasos de instalación.
2. Ir al **panel de administración → Suite PAZ → Shortcodes** para ver la galería de shortcodes disponibles.
3. Crear o editar una página de WordPress.
4. Insertar un shortcode de prueba, por ejemplo:
   ```
   [spz_grafico view="homicidios-municipio" type="geomap" seccion="seguridad" height="500"]
   ```
5. Publicar la página y abrirla en el frontend.
6. Verificar que aparece el mapa con los 64 municipios de Nariño coloreados por intensidad de homicidios (la paleta va de teal a violeta según la tasa).
7. Para probar módulos, insertar:
   ```
   [spz_kpi id="rutas-nna" seccion="dni"]
   ```
   y verificar que el número se anima con count-up al cargar la página.
8. Para probar el **editor de datos**: ir a **Suite PAZ → Editar datos**, seleccionar `seguridad` → `homicidios-municipio`, modificar un valor, guardar, y recargar el mapa para confirmar que el cambio se refleja.
9. Usar **Restablecer** para volver a los datos originales.
10. Verificar que el menú principal y los 4 submenús son visibles únicamente para usuarios con rol **Administrador**.

> **Nota:** El frontend requiere conexión a Internet para cargar la librería d3plus desde CDN (`cdn.jsdelivr.net`). En entornos sin acceso a Internet, los gráficos no se renderizarán.

---

## Limitación conocida

Las vistas de la sección **Estrategia** (`subsecretaria` y `narino-360`) usan `tipo_grafico_sugerido: "radial"` y `"strategy"` respectivamente. Estos tipos no son tipos d3plus estándar; por tanto, `compatible_for()` devuelve `[]` para estas vistas y el shortcode `[spz_grafico]` no las renderiza automáticamente como gráfico. **Se maquetar manualmente** en la página usando el HTML/CSS apropiado para el diseño radial o de mapa estratégico. Los datos JSON están disponibles y correctos — solo falta el componente de visualización personalizado.

---

## Paleta de marca

| Nombre    | Hex       | Uso |
|-----------|-----------|-----|
| Violeta   | `#5B3B8C` | Color principal, KPI valores |
| Teal      | `#3FCF97` | Acento positivo, mapas (positivo) |
| Coral     | `#E63946` | Acento negativo, alertas |
| Ámbar     | `#F4A93C` | Acento secundario |
| Texto     | `#1E2233` | Color de texto |
| Fondo claro | `#F4F1FA` | Fondo de secciones |

---

## Licencia

GPL-2.0-or-later. Ver [LICENSE](LICENSE).
