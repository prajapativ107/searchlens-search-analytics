<?php
/**
 * Plugin constants.
 *
 * @package SearchLens
 */

namespace SearchLens\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central plugin constants and shared keys.
 */
final class Constants {
	public const VERSION                      = '1.0.0';
	public const TEXT_DOMAIN                  = 'searchlens-search-analytics';
	public const OPTION_SCHEMA_VERSION        = 'searchlens_schema_version';
	public const OPTION_PLUGIN_VERSION        = 'searchlens_plugin_version';
	public const OPTION_SETTINGS              = 'searchlens_settings';
	public const OPTION_SEARCHABLE_POST_TYPES = 'searchlens_post_types';
	public const OPTION_AJAX_SEARCH_SETTINGS  = 'searchlens_ajax_search_settings';
	public const NONCE_ACTION                 = 'searchlens_action';
	public const NONCE_NAME                   = 'searchlens_nonce';
	public const AJAX_ACTION_SEARCH           = 'searchlens_search';
	public const SOURCE_AJAX_SEARCH           = 'ajax_search';
	public const SOURCE_WORDPRESS_SEARCH      = 'wordpress_search';
	public const CAPABILITY                   = 'manage_options';
	public const TABLE_SUFFIX                 = 'searchlens_searches';

	/**
	 * Get the full database table name.
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Get the plugin basename.
	 *
	 * @return string
	 */
	public static function plugin_basename(): string {
		return plugin_basename( SEARCHLENS_FILE );
	}
}
