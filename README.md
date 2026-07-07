# Suite PAZ

**Plugin de WordPress** para la **Gobernación de Nariño** que publica los datos del proyecto de Paz "Así llegó la paz a Nariño" como **gráficos interactivos, mapas coropléticos y módulos de datos** insertables en cualquier página mediante **shortcodes**, con un **editor de datos** integrado en el panel de administración.

Recreación del plugin [`tic-suite`](https://github.com/GobernaciondeNarino/tic-suite) adaptada al proyecto de Paz.

---

## Descripción

Suite PAZ expone **34 vistas de datos** (gráficos y módulos) organizadas en 5 secciones temáticas. Los datos semilla viven en archivos JSON en `data/views/<seccion>/`; cualquier cambio realizado desde el panel de administración se persiste en la base de datos de WordPress (`wp_spz_views`) y tiene prioridad sobre el JSON de origen. El renderizado de gráficos corre 100 % en el cliente a través de [@d3plus/core v3.1.4](https://github.com/d3plus/d3plus) (CDN) — **se requiere conexión a Internet en el frontend**.

**Versión 1.1.x:** corrección de la causa raíz de vistas rotas (categorías), tipo nativo `tabla`, módulos `diagrama`/`estrategia`, shortcode `[spz_analisis]` y botón "Ver datos" en cada vista.

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

**Tipos de gráfico disponibles** (d3plus v3.1.4 + nativos):

| Slug           | Clase / Motor   | Descripción |
|----------------|-----------------|-------------|
| `bar`          | BarChart (d3plus) | Barras verticales |
| `stacked_bar`  | BarChart (d3plus) | Barras apiladas |
| `line`         | LinePlot (d3plus) | Líneas temporales |
| `area`         | AreaPlot (d3plus) | Áreas |
| `stacked_area` | StackedArea (d3plus) | Áreas apiladas |
| `pie`          | Pie (d3plus)    | Pastel |
| `donut`        | Donut (d3plus)  | Dona |
| `treemap`      | Treemap (d3plus) | Treemap |
| `geomap`       | Geomap (d3plus) | Mapa coroplético (64 municipios de Nariño) |
| `network`      | Network (d3plus) | Red de nodos |
| `tree`         | Tree (d3plus)   | Árbol jerárquico |
| `sankey`       | Sankey (d3plus) | Diagrama Sankey |
| `rings`        | Rings (d3plus)  | Anillos de red |
| `box_whisker`  | BoxWhisker (d3plus) | Caja y bigotes |
| `priestley`    | Priestley (d3plus) | Timeline de Priestley |
| **`tabla`**    | **Nativo (sin d3plus)** | **Tabla HTML responsiva con encabezado violeta y filas zebra. Compatible con todas las categorías. No requiere conexión a CDN.** |

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

### `[spz_analisis]` — Análisis ciudadano

Muestra el texto de análisis ciudadano (~594 caracteres) asociado a una vista o módulo. El texto se renderiza en servidor como un párrafo `.spz-analisis` con borde violeta izquierdo y fondo lavanda; no requiere JavaScript ni d3plus.

| Atributo | Tipo   | Por defecto | Descripción |
|----------|--------|-------------|-------------|
| `id`     | string | —           | Slug de la vista o módulo (requerido) |
| `seccion`| string | `dni`       | Sección temática |

**Ejemplo:**
```
[spz_analisis id="cneb-confinamiento" seccion="dni"]
```

**Notas:**
- Si la vista no tiene campo `analisis` o está en blanco, el shortcode no emite HTML (tolerante a datos sin análisis).
- En el **Constructor de shortcodes** (`Suite PAZ → Constructor`), al seleccionar una vista se genera automáticamente un bloque copiable de `[spz_analisis …]` junto al `[spz_grafico …]` correspondiente.
- En la **Galería de shortcodes** (`Suite PAZ → Shortcodes`), cada tarjeta de vista o módulo incluye su `[spz_analisis …]` copiable.
- Los 34 elementos del plugin tienen análisis ciudadano redactados a ~594 caracteres cada uno en español de Colombia.

---

## Las 5 Secciones

| ID               | Etiqueta              | Vistas | Descripción |
|------------------|-----------------------|--------|-------------|
| `dni`            | Diálogo, Negociación e Implementación | 19     | Desplazamiento, confinamiento, minas antipersonal, niñez (NNA), firmantes de paz, búsqueda de personas desaparecidas (UBPD), acuerdos de paz. |
| `seguridad`      | Seguridad             | 6      | Homicidios por municipio (mapa), histórico departamental (tabla), estructuras armadas (tabla), fuerza pública, terrorismo nacional vs Nariño |
| `convivencia`    | Convivencia           | 3      | Convivencia ciudadana, hurtos (tabla), hallazgos clave |
| `estrategia`     | Estrategia            | 2      | Subsecretaría de Paz (módulo `diagrama`), Nariño 360 (módulo `estrategia`) |
| `transformaciones` | Transformaciones    | 4      | IPM, desocupación, indicadores sociales (tabla), PIB |

### Módulos de la sección Estrategia

Las dos vistas de la sección **Estrategia** son módulos nativos (no gráficos d3plus), insertados con sus propios shortcodes:

| Módulo       | Vista slug      | Shortcode                                    | Descripción |
|--------------|-----------------|----------------------------------------------|-------------|
| `diagrama`   | `subsecretaria` | `[spz_grafico view="subsecretaria" seccion="estrategia"]` o módulo `spz_diagrama` | Nodo central "Subsecretaría" con ramas que muestran nombre, KPI y sub-descripción de cada área. Renderizado con HTML/CSS nativo sin d3plus. |
| `estrategia` | `narino-360`    | `[spz_grafico view="narino-360" seccion="estrategia"]` o módulo `spz_estrategia` | Descripción estratégica, lista numerada de líneas de acción y chips de canales de comunicación. Renderizado con HTML/CSS nativo sin d3plus. |

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

## Botón "Ver datos"

Cada vista renderizada (gráfico d3plus, tabla nativa, módulo diagrama/estrategia y módulos KPI/compare/timeline/logro) incluye automáticamente un botón **"Ver datos"** (`<button class="spz-verdatos">`) con un panel colapsable `<div class="spz-datapanel">`.

- El botón abre/cierra el panel con gestión de foco y tecla Esc para accesibilidad.
- El panel muestra las filas de datos que el renderer consumió (en tabla o lista clave/valor) y la fuente de los datos en cursiva con borde teal.
- No requiere configuración adicional: se adjunta automáticamente al final de cada elemento renderizado.
- El CSS del panel respeta la paleta de marca (fondo lavanda `#F4F1FA`, borde teal `#3FCF97`, botón cerrar ×).

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
│   ├── class-spz-chart-types.php   # Registro de 15 tipos d3plus + 1 nativo (tabla); compatible_for(view)
│   ├── class-spz-modules.php       # Registro de tipos de módulo (kpi/compare/timeline/logro/diagrama/estrategia)
│   ├── class-spz-shortcode.php     # Handlers de los 7 shortcodes (incluye spz_analisis)
│   ├── class-spz-rest-api.php      # Rutas REST (suite-paz/v1)
│   └── class-spz-admin.php         # Menú de administración (4 subpáginas)
│
├── assets/
│   ├── js/
│   │   ├── renderer.js         # SPZ.renderer — wrapper d3plus (15 tipos + tabla nativa) + SPZ.util.attachVerDatos
│   │   ├── frontend.js         # Hidratador WP: escanea .spz-chart/.spz-module, fetch REST, render
│   │   ├── modules.js          # SPZ.modules.render — kpi/compare/timeline/logro/diagrama/estrategia
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
│   ├── build-views.py          # Generador de vistas JSON semilla (categorías estándar + análisis)
│   ├── validate-views.py       # Validador de esquema JSON (34 vistas, incluye diagrama/estrategia)
│   ├── verify-compat.php       # Verifica tipo_grafico_sugerido ∈ compatible_for para las 17 vistas de gráfico
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
8. Para probar la **tabla nativa**, insertar:
   ```
   [spz_grafico view="homicidios-departamental" type="tabla" seccion="seguridad"]
   ```
   y verificar que aparece una tabla HTML con encabezado violeta y filas zebra (no requiere d3plus ni CDN).
9. Para probar el **análisis ciudadano**, insertar:
   ```
   [spz_analisis id="cneb-confinamiento" seccion="dni"]
   ```
   y verificar que aparece el párrafo con borde violeta izquierdo sobre fondo lavanda.
10. Verificar que cada vista tiene un botón **"Ver datos"** (pill violeta debajo del gráfico); hacer clic abre un panel con las filas de datos y la fuente.
11. Para probar módulos de Estrategia:
    ```
    [spz_grafico view="subsecretaria" seccion="estrategia"]
    [spz_grafico view="narino-360" seccion="estrategia"]
    ```
12. Para probar el **editor de datos**: ir a **Suite PAZ → Editar datos**, seleccionar `seguridad` → `homicidios-municipio`, modificar un valor, guardar, y recargar el mapa para confirmar que el cambio se refleja.
13. Usar **Restablecer** para volver a los datos originales.
14. Verificar que el menú principal y los 4 submenús son visibles únicamente para usuarios con rol **Administrador**.

> **Nota:** El frontend requiere conexión a Internet para cargar la librería d3plus desde CDN (`cdn.jsdelivr.net`). En entornos sin acceso a Internet, los gráficos no se renderizarán.

---

## Solución de problemas

### Una vista muestra solo el contenedor vacío (sin gráfico ni tabla)

La causa más frecuente es tener una versión anterior al **v1.1.x** del plugin. Antes de la versión 1.1.0, la semilla de datos usaba categorías temáticas no estándar (`humanitarian`, `security`, `economic`, `coexistence`) que `compatible_for()` no reconocía; como resultado, el endpoint REST `/render` devolvía **409 Conflict** para todas esas vistas.

**Verificar:** ir a **Plugins → Plugins instalados** y confirmar que Suite PAZ muestra la versión **1.1.7** o posterior.

**Otras causas:**
- La librería d3plus no cargó desde CDN (revisar conexión a `cdn.jsdelivr.net`). Las vistas `tabla`, `diagrama` y `estrategia` no requieren d3plus y siempre renderizan.
- El tipo seleccionado no es compatible con la vista (la categoría de la vista no incluye ese tipo). Usar la **Galería de shortcodes** (`Suite PAZ → Shortcodes`) para obtener los tipos compatibles por vista.

### Los módulos `diagrama` y `estrategia` no aparecen

Confirmar que la sección es `estrategia` (slug exacto). Estas dos vistas son módulos nativos y no usan d3plus; renderizan siempre que el plugin esté activo, sin necesidad de conexión a Internet.

### El análisis ciudadano `[spz_analisis]` no muestra texto

Confirmar que el atributo `id` coincide exactamente con el slug de la vista y que `seccion` es correcto. El shortcode devuelve vacío si el campo `analisis` no existe en el JSON de la vista (comportamiento esperado en instalaciones con datos personalizados que no incluyan ese campo).

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
