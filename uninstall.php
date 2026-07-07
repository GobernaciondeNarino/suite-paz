<?php
/**
 * Uninstall handler.
 *
 * Removes options and database tables the plugin created. Does NOT delete
 * user-provided JSON seed files under /data/views — those are treated as
 * content and may have been customised.
 *
 * @package SuitePaz
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove plugin options.
delete_option( 'spz_settings' );

// Drop the data-store table created in Task 7 (safe even if it doesn't exist yet).
$table = $wpdb->prefix . 'spz_views';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );

wp_cache_flush();
