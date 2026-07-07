<?php
/**
 * verify-compat.php — Verifica que tipo_grafico_sugerido ∈ compatible_for(view)
 * para cada vista de gráfico (non-module) de suite-paz.
 *
 * Stubs mínimos de WP para poder cargar SPZ_Chart_Types sin WordPress.
 * Replica la inferencia de dims/measures de SPZ_Data_Provider::infer_fields
 * (is_int / is_float, NO is_numeric).
 *
 * Ejecutar desde la raíz del plugin:
 *   php scripts/verify-compat.php
 *
 * Salida esperada (v1.1.1+):
 *   COMPAT OK: N vistas de gráfico  (incluyendo las 5 vistas 'tabla')
 */

declare(strict_types=1);

// ── WP stubs mínimos ─────────────────────────────────────────────────────────
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (!function_exists('__')) {
    function __(string $text, string $domain = ''): string { return $text; }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key(string $str): string {
        return strtolower((string)preg_replace('/[^a-z0-9_\-]/', '', strtolower($str)));
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string {
        return trim(strip_tags($str));
    }
}

// ── Cargar clase ─────────────────────────────────────────────────────────────
$class_file = dirname(__DIR__) . '/includes/class-spz-chart-types.php';
if (!file_exists($class_file)) {
    fwrite(STDERR, "ERROR: no se encontró {$class_file}\n");
    exit(1);
}
require $class_file;

$chart_types = new SPZ_Chart_Types();

// ── Assertions inline: 'tabla' debe ser válido y universal ───────────────────
if (!$chart_types->is_valid_type('tabla')) {
    fwrite(STDERR, "ASSERT FAIL: is_valid_type('tabla') debe ser true — registrar 'tabla' en SPZ_Chart_Types\n");
    exit(1);
}

// Verificar que compatible_for ofrece 'tabla' para una vista categorical típica.
$_test_view = [
    'category'              => 'categorical',
    'dimensions'            => ['region'],
    'measures'              => [42],
    'tipo_grafico_sugerido' => 'tabla',
    'edges'                 => [],
    'temporal_range'        => false,
    'is_module'             => false,
];
$_test_compat = array_column($chart_types->compatible_for($_test_view), 'key');
if (!in_array('tabla', $_test_compat, true)) {
    fwrite(STDERR, "ASSERT FAIL: compatible_for(categorical tabla view) no incluye 'tabla'\n");
    exit(1);
}
unset($_test_view, $_test_compat);

// ── Helpers (replica de SPZ_Data_Provider) ───────────────────────────────────

/**
 * Replica SPZ_Data_Provider::infer_fields.
 * Usa is_int / is_float (NO is_numeric) para no promover strings numéricos a medidas.
 *
 * @param array $row Primera fila representativa del dataset.
 * @return array{dimensions: list<string>, measures: list<string>}
 */
function spzv_infer_fields(array $row): array
{
    $dimensions = [];
    $measures   = [];
    foreach ($row as $field => $value) {
        if (!is_string($field)) {
            continue;
        }
        if (is_int($value) || is_float($value)) {
            $measures[] = $field;
        } elseif (is_string($value) || is_bool($value)) {
            $dimensions[] = $field;
        }
        // null y arrays se ignoran (igual que en el data-provider)
    }
    return ['dimensions' => $dimensions, 'measures' => $measures];
}

/**
 * Extrae la primera fila de datos de un archivo de vista PAZ.
 * Orden de preferencia: datos > data > municipios > items > rows.
 *
 * @param array $raw JSON decodificado.
 * @return array|null Primera fila, o null si no hay filas válidas.
 */
function spzv_first_row(array $raw): ?array
{
    $candidates = ['datos', 'data', 'municipios', 'items', 'rows'];
    foreach ($candidates as $key) {
        if (!isset($raw[$key]) || !is_array($raw[$key]) || empty($raw[$key])) {
            continue;
        }
        $first = reset($raw[$key]);
        if (is_array($first) && !empty($first)) {
            return $first;
        }
    }
    return null;
}

// ── Recorrer directorios de vistas ───────────────────────────────────────────
$views_root = dirname(__DIR__) . '/data/views';
$secciones  = ['dni', 'seguridad', 'convivencia', 'estrategia', 'transformaciones'];

$failures  = [];
$ok_count  = 0;

foreach ($secciones as $seccion) {
    $dir = "{$views_root}/{$seccion}";
    if (!is_dir($dir)) {
        continue;
    }

    $files = glob("{$dir}/*.json");
    if (!is_array($files)) {
        continue;
    }

    foreach ($files as $file) {
        $slug = basename($file, '.json');

        $json = file_get_contents($file);
        if (false === $json) {
            $failures[] = "{$seccion}/{$slug}: no se pudo leer el archivo";
            continue;
        }

        $raw = json_decode($json, true);
        if (!is_array($raw)) {
            $failures[] = "{$seccion}/{$slug}: JSON inválido";
            continue;
        }

        // Omitir módulos (kpi, compare, timeline, logro, diagrama, estrategia, …)
        if (isset($raw['modulo'])) {
            continue;
        }

        // Sólo procesar vistas PAZ (tienen clave 'vista')
        if (!isset($raw['vista'])) {
            continue;
        }

        $tipo = (string)($raw['tipo_grafico_sugerido'] ?? '');

        // ── Vistas de gráfico (d3plus o nativas): verificar compatibilidad ────
        $first = spzv_first_row($raw);
        if (null === $first) {
            $failures[] = "{$seccion}/{$slug}: sin filas de datos (no se puede inferir dims/measures)";
            continue;
        }

        $fields    = spzv_infer_fields($first);
        $categoria = sanitize_key((string)($raw['categoria'] ?? ''));

        // Construir vista normalizada equivalente a lo que produce adapt_and_normalize
        $view_norm = [
            'category'              => $categoria,
            'dimensions'            => $fields['dimensions'],
            'measures'              => $fields['measures'],
            'tipo_grafico_sugerido' => $tipo,
            'edges'                 => [],
            'temporal_range'        => false,
            'is_module'             => false,
        ];

        $compat_list = $chart_types->compatible_for($view_norm);
        $compat_keys = array_column($compat_list, 'key');

        if (!in_array($tipo, $compat_keys, true)) {
            $failures[] = sprintf(
                "%s/%s: tipo='%s' NO está en compatible_for"
                . " (categoria='%s', dims=%d [%s], measures=%d [%s]). Compatible: [%s]",
                $seccion,
                $slug,
                $tipo,
                $categoria,
                count($fields['dimensions']),
                implode(', ', $fields['dimensions']),
                count($fields['measures']),
                implode(', ', $fields['measures']),
                implode(', ', $compat_keys) ?: '—ninguno—'
            );
        } else {
            $ok_count++;
        }
    }
}

// ── Informe ───────────────────────────────────────────────────────────────────
if (!empty($failures)) {
    echo 'COMPAT FAILURES: ' . count($failures) . " vistas\n";
    foreach ($failures as $f) {
        echo "  FAIL: {$f}\n";
    }
    exit(1);
}

echo "COMPAT OK: {$ok_count} vistas de gráfico\n";
