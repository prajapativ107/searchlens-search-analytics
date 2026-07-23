<?php
/**
 * AJAX search shortcode.
 *
 * @package VPLens
 */

namespace VPLens\Shortcodes;

use VPLens\Admin\Settings;
use VPLens\Core\Constants;
use VPLens\Widgets\SearchWidget;

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
			'searchlens_ajax_form'
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
			'vplens-ajax-search',
			VPLENS_URL . 'assets/css/ajax-search.css',
			array(),
			Constants::VERSION
		);

		wp_enqueue_script(
			'vplens-ajax-search',
			VPLENS_URL . 'assets/js/ajax-search.js',
			array( 'vplens-frontend' ),
			Constants::VERSION,
			true
		);

		wp_localize_script(
			'vplens-ajax-search',
			'vplensAjaxSearchI18n',
			array(
				'loading' => __( 'Searching...', 'search-analytics-insights' ),
				'empty'   => __( 'No results found.', 'search-analytics-insights' ),
				'minimum' => __( 'Type at least 2 characters to search.', 'search-analytics-insights' ),
				'error'   => __( 'Unable to search right now.', 'search-analytics-insights' ),
			)
		);

		ob_start();
		?>
		<div
			class="vplens-ajax-search vplens-ajax-search--<?php echo esc_attr( $form_style ); ?>"
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
			<form class="vplens-ajax-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" novalidate>
				<label class="screen-reader-text" for="vplens-ajax-search-input">
					<?php esc_html_e( 'Search for:', 'search-analytics-insights' ); ?>
				</label>
				<div class="vplens-ajax-search-fields<?php echo $is_ajax_enabled ? ' vplens-ajax-search-fields--no-button vplens-ajax-search-fields--has-icon' : ''; ?>">
					<input
						id="vplens-ajax-search-input"
						type="search"
						class="vplens-ajax-search-input"
						name="s"
						placeholder="<?php echo esc_attr( $placeholder ); ?>"
						autocomplete="off"
						aria-autocomplete="list"
						aria-expanded="false"
						aria-controls="vplens-ajax-search-results"
					/>
					<?php if ( ! $is_ajax_enabled ) : ?>
						<button type="submit" class="vplens-ajax-search-button">
							<?php echo esc_html( $button_text ); ?>
						</button>
					<?php else : ?>
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
				<span class="vplens-ajax-search-status" style="display:none;" aria-live="polite" aria-atomic="true"></span>
			</form>
			<div class="vplens-ajax-search-results-wrap">
				<ul id="vplens-ajax-search-results" class="vplens-ajax-search-results" role="listbox"></ul>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}