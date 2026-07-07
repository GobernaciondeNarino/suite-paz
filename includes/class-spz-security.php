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

		// Anti path-traversal: resolved path must start with the base directory.
		if ( strpos( $path, $base ) !== 0 ) {
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
}
