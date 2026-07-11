<?php
/**
 * Search form shortcode.
 *
 * @package VPLens
 */

namespace VPLens\Shortcodes;

use VPLens\Admin\Settings;
use VPLens\Core\Constants;
use VPLens\Widgets\SearchWidget;

defined( 'ABSPATH' ) || exit;

/**
 * Renders an accessible search form shortcode.
 */
final class SearchForm {
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Plugin settings helper.
	 */
	public function __construct( ?Settings $settings = null ) {
		$this->settings = $settings ? $settings : new Settings();
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( array $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'placeholder' => $this->settings->get_placeholder(),
				'button_text' => $this->settings->get_button_text(),
				'show_button' => null,
				'form_style'  => '',
			),
			$attributes,
			'vplens_form'
		);

		$placeholder     = sanitize_text_field( (string) $attributes['placeholder'] );
		$button_text     = sanitize_text_field( (string) $attributes['button_text'] );
		$is_ajax_enabled = $this->settings->is_ajax_search_enabled();
		$show_button     = null === $attributes['show_button'] ? $this->settings->get_show_button() : (bool) $attributes['show_button'];
		if ( $is_ajax_enabled ) {
			$show_button = false;
		}
		$form_style   = sanitize_key( (string) ( '' !== (string) $attributes['form_style'] ? $attributes['form_style'] : $this->settings->get_form_style() ) );
		$form_style   = in_array( $form_style, array( 'rounded', 'rectangle', 'underlined' ), true ) ? $form_style : $this->settings->get_form_style();
		$field_id     = wp_unique_id( 'vplens-search-field-' );
		$action_url   = home_url( '/' );
		$current_term = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		?>
		<form role="search" method="get" class="search-form vplens-form vplens-form--<?php echo esc_attr( $form_style ); ?>" action="<?php echo esc_url( $action_url ); ?>">
			<label for="<?php echo esc_attr( $field_id ); ?>" class="screen-reader-text">
				<?php esc_html_e( 'Search for:', 'search-analytics-insights' ); ?>
			</label>
			<div class="vplens-form-fields vplens-form-fields--<?php echo esc_attr( $show_button ? 'with-button' : 'no-button' ); ?><?php echo $is_ajax_enabled ? ' vplens-form-fields--has-icon' : ''; ?>">
				<input
					id="<?php echo esc_attr( $field_id ); ?>"
					type="search"
					class="search-field"
					name="s"
					value="<?php echo esc_attr( $current_term ); ?>"
					placeholder="<?php echo esc_attr( $placeholder ); ?>"
					autocomplete="search"
				/>
				<?php if ( $show_button ) : ?>
					<button type="submit" class="search-submit">
						<?php echo esc_html( $button_text ); ?>
					</button>
				<?php elseif ( $is_ajax_enabled ) : ?>
					<span class="vplens-input-icon">
						<?php
						$allowed_tags = array(
							'svg'  => array(
								'class'       => true,
								'aria-hidden' => true,
								'focusable'   => true,
								'viewbox'     => true,
								'xmlns'       => true,
							),
							'path' => array(
								'd'    => true,
								'fill' => true,
							),
						);
						echo wp_kses( SearchWidget::get_icon_markup(), $allowed_tags );
						?>
					</span>
				<?php endif; ?>
			</div>
		</form>
		<?php

		return (string) ob_get_clean();
	}
}