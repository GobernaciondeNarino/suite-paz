<?php
/**
 * Security helper: centralized sanitization, escaping, and permission checks.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Security
 *
 * All sanitization, nonce verification, path validation, and capability
 * checks are funnelled through this class so security logic stays in one
 * auditable place.
 */
class SPZ_Security {

	// -------------------------------------------------------------------------
	// Capability checks.
	// -------------------------------------------------------------------------

	/**
	 * Whether the current user holds the minimum plugin capability.
	 *
	 * @return bool
	 */
	public function current_user_can_manage(): bool {
		return current_user_can( SPZ_MIN_CAPABILITY );
	}

	/**
	 * Permission callback for admin-only REST routes.
	 *
	 * @return bool
	 */
	public function rest_admin_permission(): bool {
		return $this->current_user_can_manage();
	}

	/**
	 * Permission callback for public render route (still guarded by nonce +
	 * sanitized, whitelisted inputs — no open write access).
	 *
	 * @return bool
	 */
	public function rest_public_permission(): bool {
		return true;
	}

	// -------------------------------------------------------------------------
	// Nonce verification.
	// -------------------------------------------------------------------------

	/**
	 * Verify the WordPress REST nonce sent in the X-WP-Nonce header or the
	 * _wpnonce query parameter. Returns true when the nonce is valid.
	 *
	 * @return bool
	 */
	public function verify_nonce(): bool {
		// WP REST API sends nonces via the X-WP-Nonce header.
		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
			: '';

		if ( '' === $nonce ) {
			// Fall back to query-string / body param (used in some AJAX paths).
			$nonce = isset( $_REQUEST['_wpnonce'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) )
				: '';
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Verify an admin-area nonce for form submissions.
	 *
	 * @param string $nonce  Nonce value to verify.
	 * @param string $action Action string (defaults to SPZ_NONCE_ACTION).
	 * @return bool
	 */
	public function verify_admin_nonce( string $nonce, string $action = SPZ_NONCE_ACTION ): bool {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	// -------------------------------------------------------------------------
	// Slug / sección sanitization.
	// -------------------------------------------------------------------------

	/**
	 * Sanitize a generic slug (view id, key, handle).
	 *
	 * @param string $s Raw value.
	 * @return string Sanitized slug containing only a-z, 0-9, _ and -.
	 */
	public function sanitize_slug( string $s ): string {
		return sanitize_key( $s );
	}

	/**
	 * Sanitize and whitelist a sección slug.
	 * Returns SPZ_Plugin::DEFAULT_SECCION when the input is not in the list.
	 *
	 * @param string $s Raw sección slug.
	 * @return string Whitelisted sección slug.
	 */
	public function sanitize_seccion( string $s ): string {
		$s = sanitize_key( $s );
		return isset( SPZ_Plugin::SECCIONES[ $s ] ) ? $s : SPZ_Plugin::DEFAULT_SECCION;
	}

	// -------------------------------------------------------------------------
	// Path safety.
	// -------------------------------------------------------------------------

	/**
	 * Return the absolute path to a view JSON file, or null if the resolved
	 * path escapes the expected base directory (anti path-traversal).
	 *
	 * @param string $seccion Sección slug (will be sanitized).
	 * @param string $slug    View slug / filename without extension (will be sanitized).
	 * @return string|null Absolute path on success, null on any validation failure.
	 */
	public function safe_view_path( string $seccion, string $slug ): ?string {
		$seccion = $this->sanitize_seccion( $seccion );
		$slug    = $this->sanitize_slug( $slug );

		if ( '' === $slug ) {
			return null;
		}

		$base = realpath( SPZ_DATA_DIR . 'views/' . $seccion );
		if ( false === $base ) {
			return null;
		}

		$path = realpath( $base . DIRECTORY_SEPARATOR . $slug . '.json' );
		if ( false === $path ) {
			return null;
		}

		// Anti path-traversal: resolved path must start with the base directory
		// (with separator appended so a sibling dir like ".../viewsX/" is rejected).
		if ( strpos( $path, $base . DIRECTORY_SEPARATOR ) !== 0 ) {
			return null;
		}

		return $path;
	}

	// -------------------------------------------------------------------------
	// General sanitization helpers.
	// -------------------------------------------------------------------------

	/**
	 * Deeply sanitize a free-form value used in shortcode / REST params.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed Sanitized value.
	 */
	public function sanitize_deep( $value ) {
		if ( is_array( $value ) ) {
			return array_map( [ $this, 'sanitize_deep' ], $value );
		}
		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}
		if ( is_numeric( $value ) || is_bool( $value ) || null === $value ) {
			return $value;
		}
		return '';
	}

	/**
	 * Escape a label for safe HTML output.
	 *
	 * @param string $label Raw label.
	 * @return string Escaped label.
	 */
	public function esc_label( string $label ): string {
		return esc_html( wp_strip_all_tags( $label ) );
	}

	/**
	 * Safely decode JSON, returning an empty array on any error.
	 *
	 * @param string $raw Raw JSON string.
	 * @return array<mixed>
	 */
	public function decode_json( string $raw ): array {
		$decoded = json_decode( $raw, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return [];
		}
		return $decoded;
	}

	// -------------------------------------------------------------------------
	// Payload validation (mirrors scripts/validate-views.py logic in PHP).
	// -------------------------------------------------------------------------

	/**
	 * Validate a view or module payload before persisting it in the DB.
	 *
	 * Rules (mirrors validate-views.py):
	 *  1. Must be non-empty.
	 *  2. No string value (anywhere, recursively) may contain HTML-like content
	 *     (`<letter` or `</`) — blocks <script>, <img onerror=…>, etc.
	 *  3. The payload must identify as a known shape:
	 *       • Module  — has `modulo` ∈ {kpi, compare, timeline, logro}
	 *       • PAZ view — has `vista` (string) + at least one data array key
	 *       • Native view — has `id` + `name` + `data` (array)
	 *  4. No unexpected top-level keys beyond the allowed set for that shape.
	 *  5. Fields that must be scalar (strings/numbers) are rejected when they
	 *     are arrays, and vice-versa for fields that must be arrays.
	 *
	 * @param array<string, mixed> $payload Decoded payload to validate.
	 * @return bool True when the payload is safe and well-formed, false otherwise.
	 */
	public function validate_payload( array $payload ): bool {
		if ( empty( $payload ) ) {
			return false;
		}

		if ( strlen( wp_json_encode( $payload ) ) > 512000 ) {
			return false;
		}

		// Rule 2 — recursive script/HTML injection check.
		if ( $this->payload_has_script( $payload ) ) {
			return false;
		}

		// Branch by shape.
		if ( isset( $payload['modulo'] ) ) {
			return $this->validate_module_shape( $payload );
		}

		return $this->validate_view_shape( $payload );
	}

	/**
	 * Recursively scan a value for HTML-tag–like strings (potential XSS).
	 * Matches `<letter` or `</` which covers all opening and closing tags.
	 *
	 * @param mixed $value Any decoded JSON value.
	 * @return bool True when script-like content is found.
	 */
	private function payload_has_script( $value ): bool {
		if ( is_string( $value ) ) {
			return (bool) preg_match( '/<[a-zA-Z\/]/', $value );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $v ) {
				if ( $this->payload_has_script( $v ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Validate a module payload (has `modulo` key).
	 *
	 * @param array<string, mixed> $p Decoded payload.
	 * @return bool
	 */
	private function validate_module_shape( array $p ): bool {
		$modulo = (string) ( $p['modulo'] ?? '' );
		if ( ! in_array( $modulo, [ 'kpi', 'compare', 'timeline', 'logro' ], true ) ) {
			return false;
		}

		// Required in every module.
		if ( ! isset( $p['id'], $p['titulo'] ) ) {
			return false;
		}
		if ( ! is_string( $p['id'] ) || ! is_string( $p['titulo'] ) ) {
			return false;
		}

		// Build allowed-key list by module type.
		$base = [ 'modulo', 'id', 'titulo', 'fuente' ];

		switch ( $modulo ) {
			case 'kpi':
				$allowed = array_merge( $base, [ 'valor', 'unidad', 'leyenda', 'serie', 'bajar_es_bueno' ] );
				// valor must be numeric when present.
				if ( isset( $p['valor'] ) && ! is_int( $p['valor'] ) && ! is_float( $p['valor'] ) ) {
					return false;
				}
				// serie must be an array when present.
				if ( isset( $p['serie'] ) && ! is_array( $p['serie'] ) ) {
					return false;
				}
				break;

			case 'compare':
				$allowed = array_merge( $base, [ 'unidad', 'from', 'to', 'delta', 'bajar_es_bueno' ] );
				// delta must be numeric when present.
				if ( isset( $p['delta'] ) && ! is_int( $p['delta'] ) && ! is_float( $p['delta'] ) ) {
					return false;
				}
				// from / to must be arrays when present.
				if ( isset( $p['from'] ) && ! is_array( $p['from'] ) ) {
					return false;
				}
				if ( isset( $p['to'] ) && ! is_array( $p['to'] ) ) {
					return false;
				}
				break;

			case 'timeline':
				$allowed = array_merge( $base, [ 'total', 'eventos' ] );
				// eventos must be an array when present.
				if ( isset( $p['eventos'] ) && ! is_array( $p['eventos'] ) ) {
					return false;
				}
				break;

			case 'logro':
				$allowed = array_merge( $base, [ 'texto' ] );
				// texto must be a string when present.
				if ( isset( $p['texto'] ) && ! is_string( $p['texto'] ) ) {
					return false;
				}
				break;

			default:
				return false;
		}

		// Rule 4 — reject unexpected top-level keys.
		foreach ( array_keys( $p ) as $key ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				return false;
			}
		}

		// fuente must be a string when present.
		if ( isset( $p['fuente'] ) && ! is_string( $p['fuente'] ) ) {
			return false;
		}

		// bajar_es_bueno must be a boolean when present.
		if ( isset( $p['bajar_es_bueno'] ) && ! is_bool( $p['bajar_es_bueno'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate a view payload (no `modulo` key).
	 * Accepts both PAZ publication format (vista + titulo) and native plugin
	 * format (id + name + data[]).
	 *
	 * @param array<string, mixed> $p Decoded payload.
	 * @return bool
	 */
	private function validate_view_shape( array $p ): bool {
		$is_paz    = isset( $p['vista'] ) && is_string( $p['vista'] )
		             && ( isset( $p['municipios'] ) || isset( $p['datos'] ) || isset( $p['data'] ) );
		$is_native = isset( $p['id'], $p['name'], $p['data'] )
		             && is_string( $p['id'] )
		             && is_string( $p['name'] )
		             && is_array( $p['data'] );

		if ( ! $is_paz && ! $is_native ) {
			return false;
		}

		// Union of all allowed top-level keys for both view formats.
		$allowed = [
			// PAZ publication format.
			'vista', 'titulo', 'descripcion', 'tipo_grafico_sugerido', 'categoria',
			'municipios', 'datos', 'data', 'items', 'rows', 'fuente',
			'total_municipios', 'total_valores',
			// Native plugin format.
			'id', 'name', 'description', 'category', 'dimensions', 'measures',
			'temporal_range', 'edges',
		];

		// Rule 4 — reject unexpected top-level keys.
		foreach ( array_keys( $p ) as $key ) {
			if ( ! in_array( $key, $allowed, true ) ) {
				return false;
			}
		}

		// Rule 5 — fields that must be scalar when present.
		$scalar_fields = [
			'vista', 'titulo', 'descripcion', 'tipo_grafico_sugerido', 'categoria',
			'id', 'name', 'description', 'category', 'fuente',
		];
		foreach ( $scalar_fields as $field ) {
			if ( isset( $p[ $field ] ) && ! is_scalar( $p[ $field ] ) ) {
				return false;
			}
		}

		// Fields that must be arrays when present.
		$array_fields = [ 'municipios', 'datos', 'data', 'items', 'rows', 'dimensions', 'measures', 'edges', 'temporal_range' ];
		foreach ( $array_fields as $field ) {
			if ( isset( $p[ $field ] ) && ! is_array( $p[ $field ] ) ) {
				return false;
			}
		}

		// fuente must be a string when present.
		if ( isset( $p['fuente'] ) && ! is_string( $p['fuente'] ) ) {
			return false;
		}

		// total_municipios / total_valores must be integers when present.
		foreach ( [ 'total_municipios', 'total_valores' ] as $int_field ) {
			if ( isset( $p[ $int_field ] ) && ! is_int( $p[ $int_field ] ) ) {
				return false;
			}
		}

		return true;
	}
}
