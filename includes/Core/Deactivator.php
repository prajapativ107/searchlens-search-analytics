<?php
/**
 * Plugin deactivation handler.
 *
 * @package VPLens
 */

namespace VPLens\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation tasks.
 */
final class Deactivator {
	/**
	 * Run deactivation logic.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'vplens_cleanup' );
		wp_clear_scheduled_hook( 'search_analytics_insights_cleanup' );
	}
}
