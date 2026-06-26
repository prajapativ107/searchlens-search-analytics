<?php
/**
 * Plugin Name: Search Analytics & Insights
 * Plugin URI: https://github.com/prajapativ107/search-analytics-insights
 * Description: Track WordPress site searches and provide analytics for administrators.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Vivek Prajapati
 * Author URI: https://github.com/prajapativ107
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: search-analytics-insights
 * Domain Path: /languages
 *
 * @package SearchAnalyticsInsights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SEARCH_ANALYTICS_INSIGHTS_FILE', __FILE__ );
define( 'SEARCH_ANALYTICS_INSIGHTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEARCH_ANALYTICS_INSIGHTS_URL', plugin_dir_url( __FILE__ ) );

require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Constants.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Autoloader.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Database/Schema.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Activator.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Deactivator.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Uninstaller.php';
require_once SEARCH_ANALYTICS_INSIGHTS_PATH . 'includes/Core/Plugin.php';

register_activation_hook( __FILE__, array( 'SearchAnalyticsInsights\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SearchAnalyticsInsights\Core\Deactivator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'SearchAnalyticsInsights\Core\Uninstaller', 'uninstall' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		SearchAnalyticsInsights\Core\Autoloader::register();
		SearchAnalyticsInsights\Core\Plugin::instance();
	}
);
