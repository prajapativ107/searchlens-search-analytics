<?php
/**
 * Plugin constants.
 *
 * @package SearchAnalyticsInsights
 */

namespace SearchAnalyticsInsights\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central plugin constants and shared keys.
 */
final class Constants {
	public const VERSION                      = '1.0.0';
	public const TEXT_DOMAIN                  = 'search-analytics-insights';
	public const OPTION_SCHEMA_VERSION        = 'search_analytics_insights_schema_version';
	public const OPTION_PLUGIN_VERSION        = 'search_analytics_insights_plugin_version';
	public const OPTION_SETTINGS              = 'search_analytics_insights_settings';
	public const OPTION_SEARCHABLE_POST_TYPES = 'search_analytics_insights_post_types';
	public const OPTION_AJAX_SEARCH_SETTINGS  = 'search_analytics_insights_ajax_search_settings';
	public const NONCE_ACTION                 = 'search_analytics_insights_action';
	public const NONCE_NAME                   = 'search_analytics_insights_nonce';
	public const AJAX_ACTION_SEARCH           = 'search_analytics_insights_search';
	public const SOURCE_AJAX_SEARCH           = 'ajax_search';
	public const SOURCE_WORDPRESS_SEARCH      = 'wordpress_search';
	public const CAPABILITY                   = 'manage_options';
	public const TABLE_SUFFIX                 = 'search_analytics_insights_searches';

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
		return plugin_basename( SEARCH_ANALYTICS_INSIGHTS_FILE );
	}
}
