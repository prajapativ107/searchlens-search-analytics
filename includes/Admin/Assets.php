<?php
/**
 * Admin assets loader.
 *
 * @package SearchLens
 */

namespace SearchLens\Admin;

use SearchLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues admin-side assets for the plugin screens only.
 */
final class Assets {
	private const SCREEN_ID = 'toplevel_page_searchlens';

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

		if ( ! $screen || false === strpos( $screen->id, 'searchlens' ) ) {
			return;
		}

		wp_enqueue_style(
			'searchlens-admin',
			SEARCHLENS_URL . 'assets/css/admin.css',
			array(),
			Constants::VERSION
		);

		wp_enqueue_script(
			'searchlens-admin',
			SEARCHLENS_URL . 'assets/js/admin.js',
			array( 'wp-element' ),
			Constants::VERSION,
			true
		);
	}
}
