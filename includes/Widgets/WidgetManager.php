<?php
/**
 * Widget manager.
 *
 * @package SearchLens
 */

namespace SearchLens\Widgets;

use SearchLens\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin widgets.
 */
final class WidgetManager {
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings helper.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the widget classes.
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		SearchWidget::set_settings( $this->settings );
		register_widget( SearchWidget::class );
	}
}
