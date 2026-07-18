<?php
/**
 * Plugin Name: SearchLens – Search Analytics & Insights
 * Plugin URI: https://wordpress.org/plugins/
 * Description: Track WordPress site searches and provide analytics for administrators.
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 8.0
 * Author: Vivek Prajapati
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: search-analytics-insights
 * Domain Path: /languages
 *
 * @package VPLens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VPLENS_FILE', __FILE__ );
define( 'VPLENS_PATH', plugin_dir_path( __FILE__ ) );
define( 'VPLENS_URL', plugin_dir_url( __FILE__ ) );

require_once VPLENS_PATH . 'includes/Core/Constants.php';
require_once VPLENS_PATH . 'includes/Core/Autoloader.php';
require_once VPLENS_PATH . 'includes/Database/Schema.php';
require_once VPLENS_PATH . 'includes/Core/Activator.php';
require_once VPLENS_PATH . 'includes/Core/Deactivator.php';
require_once VPLENS_PATH . 'includes/Core/Uninstaller.php';
require_once VPLENS_PATH . 'includes/Core/Plugin.php';

register_activation_hook( __FILE__, array( 'VPLens\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VPLens\Core\Deactivator', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'VPLens\Core\Uninstaller', 'uninstall' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		VPLens\Core\Autoloader::register();
		VPLens\Core\Plugin::instance();
	}
);
