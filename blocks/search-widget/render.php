<?php
/**
 * Block render template.
 *
 * @package VPLens
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

use VPLens\Admin\Settings;
use VPLens\Core\Constants;
use VPLens\Shortcodes\AjaxSearchForm;
use VPLens\Shortcodes\SearchForm;
use VPLens\Widgets\SearchWidget;

defined( 'ABSPATH' ) || exit;

( function ( $attributes ) {
	$settings                     = new Settings();
	$default_open_mode            = 'dropdown';
	$default_show_label           = true;
	$default_enable_ajax_search   = $settings->is_ajax_search_enabled();
	$default_show_featured_images = $settings->get_show_featured_images();

	$open_mode = isset( $attributes['openMode'] ) && '' !== $attributes['openMode'] ? sanitize_key( (string) $attributes['openMode'] ) : $default_open_mode;
	if ( ! in_array( $open_mode, array( 'dropdown', 'modal', 'slide-down' ), true ) ) {
		$open_mode = $default_open_mode;
	}

	$show_label           = array_key_exists( 'showLabel', $attributes ) ? (bool) $attributes['showLabel'] : $default_show_label;
	$enable_ajax_search   = $default_enable_ajax_search;
	$show_featured_images = $default_show_featured_images;

	$search_form = new SearchForm();
	$ajax_form   = new AjaxSearchForm( $settings );
	$widget_id   = wp_unique_id( 'vplens-block-search-widget-' );
	$popup_id    = $widget_id . '-popup';

	wp_enqueue_style(
		'vplens-block-search-widget',
		VPLENS_URL . 'assets/css/block-search-widget.css',
		array(),
		Constants::VERSION
	);

	wp_enqueue_style(
		'vplens-search-widget',
		VPLENS_URL . 'assets/css/search-widget.css',
		array(),
		Constants::VERSION
	);

	wp_enqueue_script(
		'vplens-block-search-widget',
		VPLENS_URL . 'assets/js/search-widget.js',
		array( 'vplens-frontend' ),
		Constants::VERSION,
		true
	);

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

	$rendered_form = $enable_ajax_search ? $ajax_form->render(
		array(
			'show_featured_images' => $show_featured_images,
			'form_style'           => $settings->get_form_style(),
		)
	) : $search_form->render(
		array(
			'show_button' => $settings->get_show_button(),
			'form_style'  => $settings->get_form_style(),
		)
	);
	?>
	<div
		class="vplens-search-widget vplens-search-widget--<?php echo esc_attr( $open_mode ); ?> vplens-search-widget--block"
		data-open-mode="<?php echo esc_attr( $open_mode ); ?>"
		data-ajax-enabled="<?php echo esc_attr( $enable_ajax_search ? '1' : '0' ); ?>"
		data-ajax-url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
		data-ajax-nonce="<?php echo esc_attr( wp_create_nonce( Constants::NONCE_ACTION ) ); ?>"
		data-ajax-action="<?php echo esc_attr( Constants::AJAX_ACTION_SEARCH ); ?>"
		data-min-chars="<?php echo esc_attr( (string) $settings->get_minimum_characters() ); ?>"
		data-limit="<?php echo esc_attr( (string) $settings->get_max_results() ); ?>"
		data-show-featured-images="<?php echo esc_attr( $show_featured_images ? '1' : '0' ); ?>"
		data-show-post-type-label="<?php echo esc_attr( $settings->get_show_post_type_label() ? '1' : '0' ); ?>"
		data-loading-text="<?php echo esc_attr__( 'Searching...', 'search-analytics-insights' ); ?>"
		data-empty-text="<?php echo esc_attr__( 'No results found.', 'search-analytics-insights' ); ?>"
		data-error-text="<?php echo esc_attr__( 'Unable to search right now.', 'search-analytics-insights' ); ?>"
	>
		<button
			type="button"
			class="vplens-search-toggle"
			aria-label="<?php echo esc_attr( $show_label ? __( 'Open search', 'search-analytics-insights' ) : __( 'Search', 'search-analytics-insights' ) ); ?>"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $popup_id ); ?>"
		>
			<span class="vplens-search-toggle-icon" aria-hidden="true">
				<?php echo wp_kses( SearchWidget::get_icon_markup(), $allowed_tags ); ?>
			</span>
			<?php if ( $show_label ) : ?>
				<span class="vplens-search-toggle-label"><?php esc_html_e( 'Search', 'search-analytics-insights' ); ?></span>
			<?php endif; ?>
		</button>

		<div id="<?php echo esc_attr( $popup_id ); ?>" class="vplens-search-popup" hidden aria-hidden="true" inert>
			<div class="vplens-search-panel">
				<?php if ( 'modal' === $open_mode ) : ?>
					<button type="button" class="vplens-search-close" aria-label="<?php echo esc_attr__( 'Close search', 'search-analytics-insights' ); ?>">
						<span aria-hidden="true">&times;</span>
					</button>
				<?php endif; ?>
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $rendered_form;
				?>
			</div>
		</div>
	</div>
	<?php
} )( isset( $attributes ) && is_array( $attributes ) ? $attributes : array() );