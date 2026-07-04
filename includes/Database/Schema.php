<?php
/**
 * Database schema.
 *
 * @package SearchLens
 */

namespace SearchLens\Database;

use SearchLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Defines and creates the plugin's custom tables.
 */
final class Schema {
	/**
	 * Create or update the custom table.
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = Constants::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			search_term varchar(255) NOT NULL,
			search_term_hash char(64) NOT NULL,
			source varchar(50) NOT NULL DEFAULT 'wordpress_search',
			matched_post_types varchar(255) NOT NULL DEFAULT '',
			searched_at datetime NOT NULL,
			result_count int(11) unsigned NOT NULL DEFAULT 0,
			user_id bigint(20) unsigned NULL DEFAULT NULL,
			session_id char(64) NULL DEFAULT NULL,
			blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
			page_title varchar(255) NULL DEFAULT NULL,
			page_url text NULL DEFAULT NULL,
			referrer text NULL DEFAULT NULL,
			page_type varchar(50) NULL DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY search_term (search_term),
			KEY search_term_hash (search_term_hash),
			KEY source (source),
			KEY matched_post_types (matched_post_types),
			KEY searched_at (searched_at),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY blog_id (blog_id),
			KEY page_type (page_type)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
