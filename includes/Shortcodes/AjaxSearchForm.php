<?php
/**
 * AJAX search shortcode.
 *
 * @package SearchAnalyticsInsights
 */

namespace SearchAnalyticsInsights\Shortcodes;

use SearchAnalyticsInsights\Admin\Settings;
use SearchAnalyticsInsights\Core\Constants;
use SearchAnalyticsInsights\Widgets\SearchWidget;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the live AJAX search form.
 */
final class AjaxSearchForm {
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Plugin settings instance.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
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
				'placeholder'          => $this->settings->get_placeholder(),
				'button_text'          => $this->settings->get_button_text(),
				'limit'                => $this->settings->get_maximum_results(),
				'show_featured_images' => null,
				'form_style'           => '',
			),
			$attributes,
			'search_insights_ajax_form'
		);

		$placeholder     = sanitize_text_field( (string) $attributes['placeholder'] );
		$button_text     = sanitize_text_field( (string) $attributes['button_text'] );
		$limit           = max( 1, absint( $attributes['limit'] ) );
		$settings        = $this->settings->get_ajax_search_settings();
		$show_images     = null === $attributes['show_featured_images']
			? $this->settings->get_show_featured_images()
			: (bool) $attributes['show_featured_images'];
		$is_ajax_enabled = $this->settings->is_ajax_search_enabled();
		$form_style      = sanitize_key( (string) ( '' !== (string) $attributes['form_style'] ? $attributes['form_style'] : $this->settings->get_form_style() ) );
		$form_style      = in_array( $form_style, array( 'rounded', 'rectangle', 'underlined' ), true ) ? $form_style : $this->settings->get_form_style();

		wp_enqueue_style(
			'search-analytics-insights-ajax-search',
			SEARCH_ANALYTICS_INSIGHTS_URL . 'assets/css/ajax-search.css',
			array(),
			Constants::VERSION
		);

		wp_enqueue_script(
			'search-analytics-insights-ajax-search',
			SEARCH_ANALYTICS_INSIGHTS_URL . 'assets/js/ajax-search.js',
			array(),
			Constants::VERSION,
			true
		);

		ob_start();
		?>
		<div
			class="search-analytics-insights-ajax-search search-analytics-insights-ajax-search--<?php echo esc_attr( $form_style ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( Constants::NONCE_ACTION ) ); ?>"
			data-action="<?php echo esc_attr( Constants::AJAX_ACTION_SEARCH ); ?>"
			data-min-chars="<?php echo esc_attr( (string) $settings['minimum_characters'] ); ?>"
			data-debounce="<?php echo esc_attr( (string) $this->settings->get_debounce_time() ); ?>"
			data-limit="<?php echo esc_attr( (string) $limit ); ?>"
			data-show-featured-images="<?php echo esc_attr( $show_images ? '1' : '0' ); ?>"
			data-show-post-type-label="<?php echo esc_attr( $this->settings->get_show_post_type_label() ? '1' : '0' ); ?>"
			data-loading-text="<?php echo esc_attr__( 'Searching...', 'search-analytics-insights' ); ?>"
			data-empty-text="<?php echo esc_attr( $this->settings->get_no_results_message() ); ?>"
			data-min-chars-text="<?php echo esc_attr( sprintf( /* translators: %d: minimum characters count */ __( 'Type at least %d characters to search.', 'search-analytics-insights' ), (int) $settings['minimum_characters'] ) ); ?>"
			data-error-text="<?php echo esc_attr__( 'Unable to search right now.', 'search-analytics-insights' ); ?>"
		>
			<form class="search-analytics-insights-ajax-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" novalidate>
				<label class="screen-reader-text" for="search-analytics-insights-ajax-search-input">
					<?php esc_html_e( 'Search for:', 'search-analytics-insights' ); ?>
				</label>
				<div class="search-analytics-insights-ajax-search-fields<?php echo $is_ajax_enabled ? ' search-analytics-insights-ajax-search-fields--no-button search-analytics-insights-ajax-search-fields--has-icon' : ''; ?>">
					<input
						id="search-analytics-insights-ajax-search-input"
						type="search"
						class="search-analytics-insights-ajax-search-input"
						name="s"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						autocomplete="off"
						aria-autocomplete="list"
						aria-expanded="false"
						aria-controls="search-analytics-insights-ajax-search-results"
					/>
					<?php if ( ! $is_ajax_enabled ) : ?>
						<button type="submit" class="search-analytics-insights-ajax-search-button">
							<?php echo esc_html( $button_text ); ?>
						</button>
					<?php else : ?>
						<span class="search-analytics-insights-input-icon">
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
				<span class="search-analytics-insights-ajax-search-status" style="display:none;" aria-live="polite" aria-atomic="true"></span>
			</form>
			<div class="search-analytics-insights-ajax-search-results-wrap">
				<ul id="search-analytics-insights-ajax-search-results" class="search-analytics-insights-ajax-search-results" role="listbox"></ul>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}