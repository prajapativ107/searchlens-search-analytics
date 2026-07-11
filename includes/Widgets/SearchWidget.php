<?php
/**
 * Search widget.
 *
 * @package VPLens
 */

namespace VPLens\Widgets;

use VPLens\Admin\Settings;
use VPLens\Ajax\SearchService;
use VPLens\Core\Constants;
use VPLens\Shortcodes\SearchForm;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a search icon that opens a search form.
 */
final class SearchWidget extends \WP_Widget {
	private const DEFAULT_TITLE     = 'Search';
	private const DEFAULT_ICON_SIZE = 24;
	private const OPEN_MODES        = array( 'dropdown', 'modal', 'slide-down' );

	private static ?Settings $settings = null;

	/**
	 * Inject the shared settings helper.
	 *
	 * @param Settings $settings Settings helper.
	 *
	 * @return void
	 */
	public static function set_settings( Settings $settings ): void {
		self::$settings = $settings;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'vplens_search_widget',
			__( 'SearchLens Search Widget', 'search-analytics-insights' ),
			array(
				'description'                 => __( 'Displays a search icon that opens a search form.', 'search-analytics-insights' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Render the widget output.
	 *
	 * @param array<string, mixed> $args     Widget arguments.
	 * @param array<string, mixed> $instance Widget instance settings.
	 *
	 * @return void
	 */
	public function widget( $args, $instance ): void {
		$instance     = wp_parse_args( (array) $instance, $this->get_default_instance() );
		$settings     = $this->get_settings_helper();
		$search_form  = new SearchForm();
		$widget_id    = ! empty( $args['widget_id'] ) ? sanitize_html_class( (string) $args['widget_id'] ) : wp_unique_id( 'vplens-search-widget-' );
		$popup_id     = $widget_id . '-popup';
		$ajax_enabled = $settings->is_ajax_search_enabled();
		$open_mode    = ! empty( $instance['open_mode'] ) ? sanitize_key( (string) $instance['open_mode'] ) : 'dropdown';
		$show_label   = ! empty( $instance['show_label'] );

		wp_enqueue_style(
			'vplens-search-widget',
			VPLENS_URL . 'assets/css/search-widget.css',
			array(),
			Constants::VERSION
		);

		wp_enqueue_script(
			'vplens-search-widget',
			VPLENS_URL . 'assets/js/search-widget.js',
			array( 'vplens-frontend' ),
			Constants::VERSION,
			true
		);

		echo isset( $args['before_widget'] ) ? wp_kses_post( (string) $args['before_widget'] ) : '';
		?>
		<div
			class="vplens-search-widget vplens-search-widget--<?php echo esc_attr( $open_mode ); ?>"
			data-open-mode="<?php echo esc_attr( $open_mode ); ?>"
			data-ajax-enabled="<?php echo esc_attr( $ajax_enabled ? '1' : '0' ); ?>"
			data-ajax-url="<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>"
			data-ajax-nonce="<?php echo esc_attr( wp_create_nonce( Constants::NONCE_ACTION ) ); ?>"
			data-ajax-action="<?php echo esc_attr( Constants::AJAX_ACTION_SEARCH ); ?>"
			data-min-chars="<?php echo esc_attr( (string) $settings->get_minimum_characters() ); ?>"
			data-limit="<?php echo esc_attr( (string) $settings->get_max_results() ); ?>"
			data-show-featured-images="<?php echo esc_attr( $settings->get_show_featured_images() ? '1' : '0' ); ?>"
			data-show-post-type-label="<?php echo esc_attr( $settings->get_show_post_type_label() ? '1' : '0' ); ?>"
			data-loading-text="<?php echo esc_attr__( 'Searching...', 'search-analytics-insights' ); ?>"
			data-empty-text="<?php echo esc_attr( $settings->get_no_results_message() ); ?>"
			data-error-text="<?php echo esc_attr__( 'Unable to search right now.', 'search-analytics-insights' ); ?>"
		>
			<button
				type="button"
				class="vplens-search-toggle"
				aria-label="<?php echo esc_attr( $this->get_toggle_label( (string) $instance['title'], $show_label ) ); ?>"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $popup_id ); ?>"
				style="--vplens-search-icon-size: <?php echo esc_attr( (string) $instance['icon_size'] ); ?>px;"
			>
				<span class="vplens-search-toggle-icon" aria-hidden="true">
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
					echo wp_kses( $this->get_icon_markup(), $allowed_tags );
					?>
				</span>
				<?php if ( $show_label ) : ?>
					<span class="vplens-search-toggle-label"><?php echo esc_html__( 'Search', 'search-analytics-insights' ); ?></span>
				<?php else : ?>
					<span class="screen-reader-text"><?php echo esc_html__( 'Search', 'search-analytics-insights' ); ?></span>
				<?php endif; ?>
			</button>

			<div id="<?php echo esc_attr( $popup_id ); ?>" class="vplens-search-popup" hidden>
				<div class="vplens-search-panel">
					<?php if ( '' !== trim( (string) $instance['title'] ) ) : ?>
						<h2 class="vplens-search-title"><?php echo esc_html( (string) $instance['title'] ); ?></h2>
					<?php endif; ?>
					<?php
					$rendered_form = $search_form->render(
						array(
							'show_button' => $settings->get_show_button(),
							'form_style'  => $settings->get_form_style(),
						)
					);
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $rendered_form;
					?>
					<?php if ( $ajax_enabled ) : ?>
						<div class="vplens-search-status" aria-live="polite" aria-atomic="true"></div>
						<div class="vplens-search-results-wrap" hidden>
							<ul class="vplens-search-results" role="listbox"></ul>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
		echo isset( $args['after_widget'] ) ? wp_kses_post( (string) $args['after_widget'] ) : '';
	}

	/**
	 * Render widget settings form.
	 *
	 * @param array<string, mixed> $instance Widget instance settings.
	 *
	 * @return void
	 */
	public function form( $instance ): void {
		$instance = wp_parse_args( (array) $instance, $this->get_default_instance() );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget Title', 'search-analytics-insights' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( (string) $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'icon_size' ) ); ?>"><?php esc_html_e( 'Icon Size', 'search-analytics-insights' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'icon_size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'icon_size' ) ); ?>" type="number" min="16" max="72" step="1" value="<?php echo esc_attr( (string) $instance['icon_size'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'open_mode' ) ); ?>"><?php esc_html_e( 'Open Mode', 'search-analytics-insights' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'open_mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'open_mode' ) ); ?>">
				<option value="dropdown" <?php selected( 'dropdown', $instance['open_mode'] ); ?>><?php esc_html_e( 'Dropdown', 'search-analytics-insights' ); ?></option>
				<option value="modal" <?php selected( 'modal', $instance['open_mode'] ); ?>><?php esc_html_e( 'Modal Popup', 'search-analytics-insights' ); ?></option>
				<option value="slide-down" <?php selected( 'slide-down', $instance['open_mode'] ); ?>><?php esc_html_e( 'Slide Down', 'search-analytics-insights' ); ?></option>
			</select>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( ! empty( $instance['show_label'] ) ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_label' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_label' ) ); ?>" value="1" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_label' ) ); ?>"><?php esc_html_e( 'Show Label', 'search-analytics-insights' ); ?></label>
		</p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @param array<string, mixed> $new_instance New instance values.
	 * @param array<string, mixed> $old_instance Existing instance values.
	 *
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance               = $this->get_default_instance();
		$instance['title']      = sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) );
		$instance['icon_size']  = $this->sanitize_icon_size( $new_instance['icon_size'] ?? self::DEFAULT_ICON_SIZE );
		$instance['open_mode']  = $this->sanitize_open_mode( $new_instance['open_mode'] ?? 'dropdown' );
		$instance['show_label'] = ! empty( $new_instance['show_label'] );

		return $instance;
	}

	/**
	 * Determine whether the AJAX search module is available.
	 *
	 * @return bool
	 */
	private function is_ajax_enabled(): bool {
		$settings = $this->get_settings_helper();

		return $settings->is_ajax_search_enabled() && ( has_action( 'wp_ajax_' . Constants::AJAX_ACTION_SEARCH ) || has_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_SEARCH ) );
	}

	/**
	 * Get the widget default values.
	 *
	 * @return array<string, mixed>
	 */
	private function get_default_instance(): array {
		return array(
			'title'      => self::DEFAULT_TITLE,
			'icon_size'  => self::DEFAULT_ICON_SIZE,
			'open_mode'  => 'dropdown',
			'show_label' => true,
		);
	}

	/**
	 * Get the shared settings helper.
	 *
	 * @return Settings
	 */
	private function get_settings_helper(): Settings {
		if ( null === self::$settings ) {
			self::$settings = new Settings();
		}

		return self::$settings;
	}

	/**
	 * Sanitize the icon size.
	 *
	 * @param mixed $value Icon size.
	 *
	 * @return int
	 */
	private function sanitize_icon_size( $value ): int {
		$icon_size = absint( $value );

		if ( $icon_size < 16 ) {
			return self::DEFAULT_ICON_SIZE;
		}

		return min( 72, $icon_size );
	}

	/**
	 * Sanitize the open mode.
	 *
	 * @param mixed $value Open mode.
	 *
	 * @return string
	 */
	private function sanitize_open_mode( $value ): string {
		$value = sanitize_key( (string) $value );

		if ( in_array( $value, self::OPEN_MODES, true ) ) {
			return $value;
		}

		return 'dropdown';
	}

	/**
	 * Build the toggle aria-label.
	 *
	 * @param string $title      Widget title.
	 * @param bool   $show_label Whether to show label.
	 *
	 * @return string
	 */
	private function get_toggle_label( string $title, bool $show_label ): string {
		if ( ! $show_label ) {
			return __( 'Open search', 'search-analytics-insights' );
		}

		if ( '' !== trim( $title ) ) {
			return sprintf(
				/* translators: %s: widget title */
				__( 'Open %s', 'search-analytics-insights' ),
				$title
			);
		}

		return __( 'Open search', 'search-analytics-insights' );
	}

	/**
	 * Get the inline search icon.
	 *
	 * @return string
	 */
	public static function get_icon_markup(): string {
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="vplens-search-icon" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M10.5 4a6.5 6.5 0 1 0 4.02 11.62l4.43 4.43 1.41-1.41-4.43-4.43A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z"/></svg>';
	}
}