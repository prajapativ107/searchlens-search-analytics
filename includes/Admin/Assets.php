<?php
/**
 * Admin assets loader.
 *
 * @package VPLens
 */

namespace VPLens\Admin;

use VPLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues admin-side assets for the plugin screens only.
 */
final class Assets {
	private const SCREEN_ID = 'toplevel_page_vplens';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue CSS and JS for the plugin screen.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || false === strpos( $screen->id, 'vplens' ) ) {
			return;
		}

		wp_enqueue_style(
			'vplens-admin',
			VPLENS_URL . 'assets/css/admin.css',
			array(),
			Constants::VERSION
		);

		wp_enqueue_script(
			'vplens-admin',
			VPLENS_URL . 'assets/js/admin.js',
			array( 'wp-element' ),
			Constants::VERSION,
			true
		);
	}
}
