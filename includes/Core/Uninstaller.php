<?php
/**
 * Plugin uninstall handler.
 *
 * @package SearchAnalyticsInsights
 */

namespace SearchAnalyticsInsights\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Handles full plugin data cleanup on uninstall.
 */
final class Uninstaller {
	/**
	 * Remove plugin data.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		self::delete_options();
		self::delete_tables();
	}

	/**
	 * Delete plugin options.
	 *
	 * @return void
	 */
	private static function delete_options(): void {
		delete_option( Constants::OPTION_SCHEMA_VERSION );
		delete_option( Constants::OPTION_PLUGIN_VERSION );
		delete_option( Constants::OPTION_SETTINGS );
		delete_option( Constants::OPTION_SEARCHABLE_POST_TYPES );
		delete_option( Constants::OPTION_AJAX_SEARCH_SETTINGS );
	}

	/**
	 * Drop plugin tables.
	 *
	 * @return void
	 */
	private static function delete_tables(): void {
		global $wpdb;

		$table = esc_sql( Constants::table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}
}
