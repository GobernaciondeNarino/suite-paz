<?php
/**
 * Data store: persists user-edited view/module overrides in the WordPress DB.
 *
 * Table `wp_spz_views` maps (seccion, slug) → JSON payload. When a row
 * exists its payload wins over the seed JSON; when absent the seed JSON
 * is used (see SPZ_Data_Provider::get_view).
 *
 * All queries use $wpdb->prepare() to prevent SQL injection.
 * $wpdb->replace() / $wpdb->delete() use format arrays ('%s') for
 * the same reason.
 *
 * Adapted from class-tsg-data-store.php (tic-suite).
 * Changes: TSG_ → SPZ_, table name tsg_views → spz_views.
 *
 * @package SuitePaz
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SPZ_Data_Store
 *
 * Thin wpdb wrapper for the spz_views override table.
 * One global instance is created in SPZ_Plugin and shared by every
 * SPZ_Data_Provider so there is only one DB connection per request.
 */
final class SPZ_Data_Store {

	// -------------------------------------------------------------------------
	// Schema.
	// -------------------------------------------------------------------------

	/**
	 * Return the full table name, including the WordPress table prefix.
	 *
	 * @return string
	 */
	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'spz_views';
	}

	/**
	 * Create (or upgrade) the table via dbDelta.
	 * Safe to call multiple times — dbDelta is idempotent.
	 * Called from SPZ_Plugin::activate().
	 */
	public function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$t       = $this->table();
		$charset = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		dbDelta(
			"CREATE TABLE $t (
			  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			  seccion    VARCHAR(40)     NOT NULL,
			  slug       VARCHAR(120)    NOT NULL,
			  payload    LONGTEXT        NOT NULL,
			  updated_at DATETIME        NOT NULL,
			  PRIMARY KEY  (id),
			  UNIQUE KEY sec_slug (seccion, slug)
			) $charset;"
		);
	}

	// -------------------------------------------------------------------------
	// CRUD.
	// -------------------------------------------------------------------------

	/**
	 * Return the decoded override payload for (seccion, slug), or null when
	 * no override row exists for that pair.
	 *
	 * @param string $sec  Sección slug (already sanitised by caller).
	 * @param string $slug View/module slug (already sanitised by caller).
	 * @return array<string, mixed>|null
	 */
	public function get_override( string $sec, string $slug ): ?array {
		global $wpdb;
		$t = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT payload FROM $t WHERE seccion = %s AND slug = %s",
				$sec,
				$slug
			)
		);

		if ( null === $row ) {
			return null;
		}

		$decoded = json_decode( (string) $row, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	/**
	 * Upsert an override for (seccion, slug).
	 *
	 * Uses $wpdb->replace so it inserts-or-updates atomically without a
	 * separate SELECT. The payload array is JSON-encoded for storage.
	 *
	 * @param string               $sec     Sección slug (already sanitised).
	 * @param string               $slug    View/module slug (already sanitised).
	 * @param array<string, mixed> $payload Validated payload to persist.
	 * @return bool True on success, false on DB error.
	 */
	public function save_override( string $sec, string $slug, array $payload ): bool {
		global $wpdb;

		return false !== $wpdb->replace(
			$this->table(),
			[
				'seccion'    => $sec,
				'slug'       => $slug,
				'payload'    => wp_json_encode( $payload ),
				'updated_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Delete the override row for (seccion, slug), restoring the seed JSON
	 * as the authoritative source for that view.
	 *
	 * @param string $sec  Sección slug (already sanitised).
	 * @param string $slug View/module slug (already sanitised).
	 * @return bool True on success (including when row did not exist), false on error.
	 */
	public function delete_override( string $sec, string $slug ): bool {
		global $wpdb;

		return false !== $wpdb->delete(
			$this->table(),
			[ 'seccion' => $sec, 'slug' => $slug ],
			[ '%s', '%s' ]
		);
	}

	/**
	 * Return every override row as a list of associative arrays.
	 *
	 * Each element has keys: seccion, slug, payload (decoded array), updated_at.
	 * Used by the export-all endpoint and admin UI (Task 8).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all_overrides(): array {
		global $wpdb;
		$t = $this->table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT seccion, slug, payload, updated_at FROM $t ORDER BY seccion, slug",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $row ) {
			$decoded = json_decode( (string) $row['payload'], true );
			$out[]   = [
				'seccion'    => (string) $row['seccion'],
				'slug'       => (string) $row['slug'],
				'payload'    => is_array( $decoded ) ? $decoded : [],
				'updated_at' => (string) $row['updated_at'],
			];
		}
		return $out;
	}
}
