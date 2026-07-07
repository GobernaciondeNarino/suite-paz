<?php
/**
 * Data provider for Suite PAZ views.
 *
 * Loads view definitions from JSON files under data/views/<seccion>/*.json
 * and normalises them into a single canonical shape, regardless of whether
 * the file is a regular view or a native module (kpi, compare, timeline …).
 *
 * Adapted from class-tsg-data-provider.php (tic-suite).
 * Changes: TSG_ → SPZ_, project → seccion, path, module detection,
 * respect explicit `categoria` field, add get_raw(), in-memory cache.
 *
 * Shapes accepted:
 *
 *   A) Native plugin format
 *      { "id":"slug", "name":"…", "data":[{…},…] }
 *
 *   B) PAZ publication format (auto-detected)
 *      { "vista":"slug", "titulo":"…", "municipios"|"datos"|…:[{…},…] }
 *      Dimensions / measures / category are inferred from the first row:
 *        - string / bool fields  → dimensions
 *        - int / float fields    → measures     (uses is_int/is_float, NOT
 *          is_numeric, so DIVIPOLA strings like "52001" stay as dimensions)
 *        - explicit `categoria` key in the JSON wins over inference
 *        - if no explicit categoria: "municipio"/"departamento" dim → geographic,
 *          date-ish dim name → temporal, else → categorical
 *
 *   M) Module files
 *      { "modulo":"kpi"|"compare"|…, … }
 *      get_view() returns the raw payload with `is_module => true`.
 *      No dims/measures inference is performed.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Data_Provider
 *
 * One instance per sección (dni, seguridad, …). Each instance scans its own
 * data/views/{seccion}/ directory so secciones stay isolated — view slugs can
 * even collide across secciones.
 */
class SPZ_Data_Provider {

	private const VIEWS_DIR = 'views';

	/**
	 * Data keys we'll probe (in order) looking for a row array in the PAZ
	 * publication format.
	 */
	private const DATA_KEY_CANDIDATES = [ 'data', 'datos', 'municipios', 'items', 'rows' ];

	private SPZ_Security $security;
	private string $seccion;

	/**
	 * In-memory cache keyed by "<seccion>:<suffix>".
	 *
	 * @var array<string, mixed>
	 */
	private array $cache = [];

	/**
	 * Constructor.
	 *
	 * @param SPZ_Security $security Security helper.
	 * @param string       $seccion  Sección slug (sanitised internally).
	 */
	public function __construct( SPZ_Security $security, string $seccion ) {
		$this->security = $security;
		$this->seccion  = sanitize_key( $seccion );
	}

	/**
	 * Return this provider's sección slug.
	 */
	public function seccion(): string {
		return $this->seccion;
	}

	/**
	 * Return the absolute path to this sección's views directory (trailing /).
	 */
	public function views_path(): string {
		return SPZ_DATA_DIR . self::VIEWS_DIR . '/' . $this->seccion . '/';
	}

	/**
	 * Return the absolute path to the shared topo directory.
	 */
	public function topo_path(): string {
		return SPZ_DATA_DIR . 'topo/';
	}

	// -------------------------------------------------------------------------
	// Public API.
	// -------------------------------------------------------------------------

	/**
	 * Return a summary list of every view and module in this sección.
	 *
	 * For regular views the summary includes: id, name, description, category,
	 * dimensions, measures, rows.
	 * For modules the summary includes: id, name, description, is_module, modulo.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function list_views(): array {
		$cache_key = $this->cache_key( 'all_summaries' );
		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$out = [];
		$dir = $this->views_path();
		if ( ! is_dir( $dir ) ) {
			return $out;
		}

		$files = glob( $dir . '*.json' );
		if ( ! is_array( $files ) ) {
			return $out;
		}

		foreach ( $files as $file ) {
			$raw = $this->read_file( $file );
			if ( empty( $raw ) ) {
				continue;
			}

			// Module files — return a minimal summary without dim/measure inference.
			if ( isset( $raw['modulo'] ) ) {
				$out[] = [
					'id'          => sanitize_key( (string) ( $raw['id'] ?? basename( $file, '.json' ) ) ),
					'name'        => sanitize_text_field( (string) ( $raw['titulo'] ?? '' ) ),
					'description' => sanitize_text_field( (string) ( $raw['descripcion'] ?? '' ) ),
					'is_module'   => true,
					'modulo'      => sanitize_key( (string) $raw['modulo'] ),
				];
				continue;
			}

			// Regular view — adapt and normalise.
			$view = $this->adapt_and_normalize( $raw, basename( $file, '.json' ) );
			if ( empty( $view ) ) {
				continue;
			}
			$out[] = [
				'id'          => (string) $view['id'],
				'name'        => (string) $view['name'],
				'description' => (string) $view['description'],
				'category'    => (string) $view['category'],
				'dimensions'  => $view['dimensions'],
				'measures'    => $view['measures'],
				'rows'        => count( $view['data'] ),
			];
		}

		usort( $out, static fn( $a, $b ) => strcasecmp( (string) $a['name'], (string) $b['name'] ) );

		$this->cache[ $cache_key ] = $out;
		return $out;
	}

	/**
	 * Return a full, normalised view or module by slug (file basename without
	 * the .json extension). Returns null if the slug is invalid or not found.
	 *
	 * Modules (files that have a top-level `modulo` key) are returned as-is
	 * with `is_module => true`; no dims/measures inference is performed.
	 *
	 * @param string $slug View/module slug.
	 * @return array<string, mixed>|null
	 */
	public function get_view( string $slug ): ?array {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return null;
		}

		$cache_key = $this->cache_key( 'view_' . $slug );
		if ( isset( $this->cache[ $cache_key ] ) ) {
			return $this->cache[ $cache_key ];
		}

		$path = $this->security->safe_view_path( $this->seccion, $slug );
		if ( null === $path ) {
			return null;
		}

		$raw = $this->read_file( $path );
		if ( empty( $raw ) ) {
			return null;
		}

		// Module — return raw payload decorated with is_module flag.
		if ( isset( $raw['modulo'] ) ) {
			$view = array_merge( $raw, [ 'is_module' => true ] );
			$this->cache[ $cache_key ] = $view;
			return $view;
		}

		// Regular view — adapt and normalise.
		$view = $this->adapt_and_normalize( $raw, $slug );
		if ( empty( $view ) ) {
			return null;
		}

		$this->cache[ $cache_key ] = $view;
		return $view;
	}

	/**
	 * Return the raw decoded JSON for a slug without any normalisation or
	 * module detection. Returns null if the slug is invalid or not found.
	 *
	 * Useful for writing back overrides or reading metadata without the
	 * normalisation pass altering values.
	 *
	 * @param string $slug View/module slug.
	 * @return array<string, mixed>|null
	 */
	public function get_raw( string $slug ): ?array {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return null;
		}

		$path = $this->security->safe_view_path( $this->seccion, $slug );
		if ( null === $path ) {
			return null;
		}

		$raw = $this->read_file( $path );
		return empty( $raw ) ? null : $raw;
	}

	// -------------------------------------------------------------------------
	// Internal helpers.
	// -------------------------------------------------------------------------

	/**
	 * Build an in-memory cache key for this sección.
	 */
	private function cache_key( string $suffix ): string {
		return $this->seccion . ':' . $suffix;
	}

	/**
	 * Read and JSON-decode a file defensively, guarding against path traversal.
	 *
	 * @param string $file Absolute path.
	 * @return array<string, mixed>
	 */
	private function read_file( string $file ): array {
		$real_base = realpath( $this->views_path() );
		$real_file = realpath( $file );

		// Path-traversal guard: resolved path must start with the views directory
		// (with separator appended so a sibling dir like ".../viewsX/" is rejected).
		if ( ! $real_base || ! $real_file || strpos( $real_file, $real_base . DIRECTORY_SEPARATOR ) !== 0 ) {
			return [];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw = file_get_contents( $real_file );
		if ( false === $raw ) {
			return [];
		}

		return $this->security->decode_json( $raw );
	}

	/**
	 * Detect the JSON shape, adapt to canonical form, then normalise.
	 *
	 * @param array  $raw      Decoded JSON.
	 * @param string $fallback Fallback id (filename without extension).
	 * @return array<string, mixed>
	 */
	private function adapt_and_normalize( array $raw, string $fallback ): array {
		$adapted = $this->adapt( $raw, $fallback );
		if ( empty( $adapted ) ) {
			return [];
		}
		return $this->normalize_view( $adapted );
	}

	/**
	 * Detect the file shape and convert it to the canonical plugin shape.
	 *
	 * @param array  $raw      Decoded JSON (must not have `modulo` key — caller checked).
	 * @param string $fallback Fallback id.
	 * @return array<string, mixed>
	 */
	private function adapt( array $raw, string $fallback ): array {
		// A) Native plugin format — has id + name + data[].
		if ( isset( $raw['id'], $raw['name'], $raw['data'] ) && is_array( $raw['data'] ) ) {
			return $raw;
		}

		// B) PAZ publication format — has vista + titulo.
		if ( isset( $raw['vista'], $raw['titulo'] ) ) {
			$data = $this->extract_rows( $raw );
			if ( empty( $data ) ) {
				return [];
			}

			$fields = $this->infer_fields( $data[0] );

			// Respect explicit `categoria` field (PAZ JSONs provide it); fall back
			// to inferring from dimension names when absent.
			if ( ! empty( $raw['categoria'] ) ) {
				$category = sanitize_key( (string) $raw['categoria'] );
			} else {
				$category = $this->infer_category( $fields['dimensions'] );
			}

			$hint = isset( $raw['tipo_grafico_sugerido'] ) ? (string) $raw['tipo_grafico_sugerido'] : '';

			return [
				'id'                    => (string) $raw['vista'],
				'name'                  => (string) $raw['titulo'],
				'description'           => (string) ( $raw['descripcion'] ?? '' ),
				'category'              => $category,
				'dimensions'            => $fields['dimensions'],
				'measures'              => $fields['measures'],
				'data'                  => $data,
				'tipo_grafico_sugerido' => $hint,
			];
		}

		// C) Anonymous dataset — no vista/titulo/id but has a row array.
		// Skip files whose rows have no numeric measures (e.g. nested meta files).
		$data = $this->extract_rows( $raw );
		if ( ! empty( $data ) ) {
			$fields = $this->infer_fields( $data[0] );
			if ( empty( $fields['measures'] ) ) {
				return [];
			}
			$category = $this->infer_category( $fields['dimensions'] );
			return [
				'id'          => $fallback,
				'name'        => $this->humanize_id( $fallback ),
				'description' => (string) ( $raw['fuente'] ?? $raw['descripcion'] ?? '' ),
				'category'    => $category,
				'dimensions'  => $fields['dimensions'],
				'measures'    => $fields['measures'],
				'data'        => $data,
			];
		}

		return [];
	}

	/**
	 * Humanise a slug: strip leading "vista-" prefix and replace separators.
	 *
	 * @param string $id Slug.
	 */
	private function humanize_id( string $id ): string {
		$name = preg_replace( '/^vista[-_]/', '', $id );
		$name = str_replace( [ '-', '_' ], ' ', (string) $name );
		return ucfirst( trim( (string) $name ) );
	}

	/**
	 * Pull the row array out of a publication-format file.
	 *
	 * Prefers `datos` over `municipios` when the latter is a list of strings
	 * (as in files that store both a municipio name-list and a row dataset).
	 *
	 * @param array $raw Decoded JSON.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_rows( array $raw ): array {
		// Prefer `datos` when it is a row list (even if `municipios` also exists).
		if ( isset( $raw['datos'] ) && is_array( $raw['datos'] ) && $this->is_row_list( $raw['datos'] ) ) {
			return array_values( $raw['datos'] );
		}
		foreach ( self::DATA_KEY_CANDIDATES as $key ) {
			if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) && $this->is_row_list( $raw[ $key ] ) ) {
				return array_values( $raw[ $key ] );
			}
		}
		return [];
	}

	/**
	 * True if $arr is a non-empty list whose first element is an associative
	 * array (i.e. a row object, not a list of scalars).
	 *
	 * @param array $arr Candidate array.
	 */
	private function is_row_list( array $arr ): bool {
		if ( empty( $arr ) ) {
			return false;
		}
		$first = reset( $arr );
		return is_array( $first )
			&& ! empty( $first )
			&& count( array_filter( array_keys( $first ), 'is_string' ) ) > 0;
	}

	/**
	 * Infer dimensions + measures from the first row of a dataset.
	 *
	 * Key rule (same as tic-suite): uses is_int / is_float, NOT is_numeric, so
	 * DIVIPOLA codes like "52001" (numeric strings) remain as dimensions and are
	 * never promoted to measures.
	 *
	 * @param array $row Representative first row.
	 * @return array{dimensions: array<int,string>, measures: array<int,string>}
	 */
	private function infer_fields( array $row ): array {
		$dimensions = [];
		$measures   = [];

		foreach ( $row as $field => $value ) {
			if ( ! is_string( $field ) ) {
				continue;
			}
			if ( is_int( $value ) || is_float( $value ) ) {
				$measures[] = $field;
			} elseif ( is_string( $value ) || is_bool( $value ) ) {
				$dimensions[] = $field;
			}
			// Nested arrays/objects are ignored (not auto-flattened).
		}

		return [ 'dimensions' => $dimensions, 'measures' => $measures ];
	}

	/**
	 * Infer the high-level category from dimension names.
	 *
	 * @param array $dimensions Dimension field names.
	 */
	private function infer_category( array $dimensions ): string {
		$lower = array_map( 'strtolower', $dimensions );

		foreach ( $lower as $d ) {
			if ( in_array( $d, [ 'municipio', 'municipios', 'departamento', 'departamentos', 'municipio_id' ], true ) ) {
				return 'geographic';
			}
		}

		$date_hints = [ 'fecha', 'mes', 'anio', 'año', 'year', 'date', 'periodo', 'vigencia' ];
		foreach ( $lower as $d ) {
			foreach ( $date_hints as $hint ) {
				if ( false !== strpos( $d, $hint ) ) {
					return 'temporal';
				}
			}
		}

		return 'categorical';
	}

	/**
	 * Final normalisation + sanitisation pass on a canonical view array.
	 *
	 * @param array $view Adapted view.
	 * @return array<string, mixed>
	 */
	private function normalize_view( array $view ): array {
		$view['id']             = sanitize_key( (string) ( $view['id'] ?? '' ) );
		$view['name']           = sanitize_text_field( (string) ( $view['name'] ?? '' ) );
		$view['description']    = sanitize_text_field( (string) ( $view['description'] ?? '' ) );
		$view['category']       = sanitize_key( (string) ( $view['category'] ?? '' ) );
		$view['dimensions']     = array_values(
			array_map( 'sanitize_text_field', (array) ( $view['dimensions'] ?? [] ) )
		);
		$view['measures']       = array_values(
			array_map( 'sanitize_text_field', (array) ( $view['measures'] ?? [] ) )
		);
		$view['temporal_range'] = ! empty( $view['temporal_range'] );
		$view['edges']          = ! empty( $view['edges'] ) ? array_values( (array) $view['edges'] ) : [];
		$view['data']           = is_array( $view['data'] ?? null ) ? array_values( $view['data'] ) : [];

		if ( isset( $view['tipo_grafico_sugerido'] ) ) {
			$view['tipo_grafico_sugerido'] = sanitize_text_field( (string) $view['tipo_grafico_sugerido'] );
		}

		return $view;
	}
}
