<?php
/**
 * Plugin settings.
 *
 * @package SearchLens
 */

namespace SearchLens\Admin;

use SearchLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders plugin settings.
 */
final class Settings {
	private const SETTINGS_GROUP                  = 'searchlens_settings';
	private const PAGE_SLUG                       = 'searchlens';
	private const SECTION_SEARCH_FORM             = 'searchlens_search_form';
	private const SECTION_AJAX_SEARCH             = 'searchlens_ajax_search';
	private const SECTION_SEARCH_RESULTS          = 'searchlens_search_results';
	private const SECTION_SEARCH_SOURCES          = 'searchlens_search_sources';
	private const SECTION_ANALYTICS               = 'searchlens_analytics';
	private const DEFAULT_PLACEHOLDER             = 'Search posts and pages...';
	private const DEFAULT_BUTTON_TEXT             = 'Search';
	private const DEFAULT_NO_RESULTS_MESSAGE      = 'No results found.';
	private const DEFAULT_FORM_STYLE              = 'rounded';
	private const DEFAULT_MAX_RESULTS             = 10;
	private const DEFAULT_MINIMUM_CHARACTERS      = 2;
	private const DEFAULT_DEBOUNCE_TIME           = 300;
	private const DEFAULT_SEARCH_RETENTION_PERIOD = 30;
	private const DISALLOWED_TYPES                = array(
		'revision',
		'nav_menu_item',
		'attachment',
		'custom_css',
		'customize_changeset',
		'oembed_cache',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_navigation',
		'wp_global_styles',
		'wp_font_family',
		'wp_font_face',
		'wp_pattern',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'maybe_migrate_legacy_settings' ), 5 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			Constants::OPTION_SETTINGS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		add_settings_section(
			self::SECTION_SEARCH_FORM,
			__( 'Search Form', 'search-analytics-insights' ),
			array( $this, 'render_search_form_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'placeholder',
			__( 'Placeholder text', 'search-analytics-insights' ),
			array( $this, 'render_placeholder_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_FORM
		);

		add_settings_field(
			'button_text',
			__( 'Button text', 'search-analytics-insights' ),
			array( $this, 'render_button_text_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_FORM
		);

		add_settings_field(
			'show_button',
			__( 'Show button', 'search-analytics-insights' ),
			array( $this, 'render_show_button_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_FORM
		);

		add_settings_field(
			'form_style',
			__( 'Form style', 'search-analytics-insights' ),
			array( $this, 'render_form_style_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_FORM
		);

		add_settings_section(
			self::SECTION_AJAX_SEARCH,
			__( 'AJAX Search', 'search-analytics-insights' ),
			array( $this, 'render_ajax_search_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'enabled',
			__( 'Enable AJAX search', 'search-analytics-insights' ),
			array( $this, 'render_ajax_enabled_field' ),
			self::PAGE_SLUG,
			self::SECTION_AJAX_SEARCH
		);

		add_settings_field(
			'minimum_characters',
			__( 'Minimum characters', 'search-analytics-insights' ),
			array( $this, 'render_minimum_characters_field' ),
			self::PAGE_SLUG,
			self::SECTION_AJAX_SEARCH
		);

		add_settings_field(
			'maximum_results',
			__( 'Maximum results', 'search-analytics-insights' ),
			array( $this, 'render_maximum_results_field' ),
			self::PAGE_SLUG,
			self::SECTION_AJAX_SEARCH
		);

		add_settings_field(
			'debounce_time',
			__( 'Debounce time', 'search-analytics-insights' ),
			array( $this, 'render_debounce_time_field' ),
			self::PAGE_SLUG,
			self::SECTION_AJAX_SEARCH
		);

		add_settings_section(
			self::SECTION_SEARCH_RESULTS,
			__( 'Search Results', 'search-analytics-insights' ),
			array( $this, 'render_search_results_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'show_featured_images',
			__( 'Show featured images', 'search-analytics-insights' ),
			array( $this, 'render_show_featured_images_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_RESULTS
		);

		add_settings_field(
			'show_post_type_label',
			__( 'Show post type label', 'search-analytics-insights' ),
			array( $this, 'render_show_post_type_label_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_RESULTS
		);

		add_settings_field(
			'no_results_message',
			__( 'No results message', 'search-analytics-insights' ),
			array( $this, 'render_no_results_message_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_RESULTS
		);

		add_settings_section(
			self::SECTION_SEARCH_SOURCES,
			__( 'Search Sources', 'search-analytics-insights' ),
			array( $this, 'render_search_sources_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'load_all_public_post_types',
			__( 'Automatically load all public post types', 'search-analytics-insights' ),
			array( $this, 'render_load_all_public_post_types_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_SOURCES
		);

		add_settings_field(
			'searchable_post_types',
			__( 'Allow selecting searchable post types', 'search-analytics-insights' ),
			array( $this, 'render_searchable_post_types_field' ),
			self::PAGE_SLUG,
			self::SECTION_SEARCH_SOURCES
		);

		add_settings_section(
			self::SECTION_ANALYTICS,
			__( 'Analytics', 'search-analytics-insights' ),
			array( $this, 'render_analytics_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'track_logged_in_users',
			__( 'Track logged-in users', 'search-analytics-insights' ),
			array( $this, 'render_track_logged_in_users_field' ),
			self::PAGE_SLUG,
			self::SECTION_ANALYTICS
		);

		add_settings_field(
			'track_guests',
			__( 'Track guests', 'search-analytics-insights' ),
			array( $this, 'render_track_guests_field' ),
			self::PAGE_SLUG,
			self::SECTION_ANALYTICS
		);

		add_settings_field(
			'search_retention_period',
			__( 'Search retention period', 'search-analytics-insights' ),
			array( $this, 'render_search_retention_period_field' ),
			self::PAGE_SLUG,
			self::SECTION_ANALYTICS
		);
	}

	/**
	 * Render the Search Form section description.
	 *
	 * @return void
	 */
	public function render_search_form_section_description(): void {
		echo '<p>' . esc_html__( 'Set the default search form copy and visual style.', 'search-analytics-insights' ) . '</p>';
	}

	/**
	 * Render the AJAX Search section description.
	 *
	 * @return void
	 */
	public function render_ajax_search_section_description(): void {
		echo '<p>' . esc_html__( 'Control how quickly live search appears and how many results it returns.', 'search-analytics-insights' ) . '</p>';
	}

	/**
	 * Render the Search Results section description.
	 *
	 * @return void
	 */
	public function render_search_results_section_description(): void {
		echo '<p>' . esc_html__( 'Adjust what visitors see in live search result items.', 'search-analytics-insights' ) . '</p>';
	}

	/**
	 * Render the Search Sources section description.
	 *
	 * @return void
	 */
	public function render_search_sources_section_description(): void {
		echo '<p>' . esc_html__( 'Choose which public content types should be searchable.', 'search-analytics-insights' ) . '</p>';
	}

	/**
	 * Render the Analytics section description.
	 *
	 * @return void
	 */
	public function render_analytics_section_description(): void {
		echo '<p>' . esc_html__( 'Define which searches should be tracked and how long they are retained.', 'search-analytics-insights' ) . '</p>';
	}

	/**
	 * Render the placeholder field.
	 *
	 * @return void
	 */
	public function render_placeholder_field(): void {
		$settings = $this->get_search_form_settings();
		$this->render_text_field(
			$this->field_id( 'search_form', 'placeholder' ),
			$this->field_name( 'search_form', 'placeholder' ),
			(string) $settings['placeholder'],
			__( 'Text displayed inside the search input before typing.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the button text field.
	 *
	 * @return void
	 */
	public function render_button_text_field(): void {
		$settings = $this->get_search_form_settings();
		$this->render_text_field(
			$this->field_id( 'search_form', 'button_text' ),
			$this->field_name( 'search_form', 'button_text' ),
			(string) $settings['button_text'],
			__( 'Label shown on the search submit button.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the show button toggle.
	 *
	 * @return void
	 */
	public function render_show_button_field(): void {
		$settings = $this->get_search_form_settings();
		$this->render_checkbox_field(
			$this->field_id( 'search_form', 'show_button' ),
			$this->field_name( 'search_form', 'show_button' ),
			! empty( $settings['show_button'] ),
			__( 'Show the submit button next to the input.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the form style selector.
	 *
	 * @return void
	 */
	public function render_form_style_field(): void {
		$settings = $this->get_search_form_settings();
		$this->render_select_field(
			$this->field_id( 'search_form', 'form_style' ),
			$this->field_name( 'search_form', 'form_style' ),
			(string) $settings['form_style'],
			array(
				'rounded'    => __( 'Rounded', 'search-analytics-insights' ),
				'rectangle'  => __( 'Rectangle', 'search-analytics-insights' ),
				'underlined' => __( 'Underlined', 'search-analytics-insights' ),
			),
			__( 'Choose the default visual treatment for the search form.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the AJAX enabled toggle.
	 *
	 * @return void
	 */
	public function render_ajax_enabled_field(): void {
		$settings = $this->get_ajax_search_settings();
		$this->render_checkbox_field(
			$this->field_id( 'ajax_search', 'enabled' ),
			$this->field_name( 'ajax_search', 'enabled' ),
			! empty( $settings['enabled'] ),
			__( 'Enable the live AJAX search experience on the front end.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the minimum characters field.
	 *
	 * @return void
	 */
	public function render_minimum_characters_field(): void {
		$settings = $this->get_ajax_search_settings();
		$this->render_number_field(
			$this->field_id( 'ajax_search', 'minimum_characters' ),
			$this->field_name( 'ajax_search', 'minimum_characters' ),
			(int) $settings['minimum_characters'],
			1,
			10,
			__( 'Search only starts after this many characters are entered.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the maximum results field.
	 *
	 * @return void
	 */
	public function render_maximum_results_field(): void {
		$settings = $this->get_ajax_search_settings();
		$this->render_number_field(
			$this->field_id( 'ajax_search', 'maximum_results' ),
			$this->field_name( 'ajax_search', 'maximum_results' ),
			(int) $settings['max_results'],
			1,
			20,
			__( 'Controls how many results the AJAX search returns.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the debounce time field.
	 *
	 * @return void
	 */
	public function render_debounce_time_field(): void {
		$settings = $this->get_ajax_search_settings();
		$this->render_number_field(
			$this->field_id( 'ajax_search', 'debounce_time' ),
			$this->field_name( 'ajax_search', 'debounce_time' ),
			(int) $settings['debounce_time'],
			50,
			2000,
			__( 'Delay in milliseconds before the AJAX request is sent.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the featured images toggle.
	 *
	 * @return void
	 */
	public function render_show_featured_images_field(): void {
		$settings = $this->get_search_results_settings();
		$this->render_checkbox_field(
			$this->field_id( 'search_results', 'show_featured_images' ),
			$this->field_name( 'search_results', 'show_featured_images' ),
			! empty( $settings['show_featured_images'] ),
			__( 'Display featured images in live search results.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the post type label toggle.
	 *
	 * @return void
	 */
	public function render_show_post_type_label_field(): void {
		$settings = $this->get_search_results_settings();
		$this->render_checkbox_field(
			$this->field_id( 'search_results', 'show_post_type_label' ),
			$this->field_name( 'search_results', 'show_post_type_label' ),
			! empty( $settings['show_post_type_label'] ),
			__( 'Show the post type label under each result title.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the no results message field.
	 *
	 * @return void
	 */
	public function render_no_results_message_field(): void {
		$settings = $this->get_search_results_settings();
		$this->render_textarea_field(
			$this->field_id( 'search_results', 'no_results_message' ),
			$this->field_name( 'search_results', 'no_results_message' ),
			(string) $settings['no_results_message'],
			__( 'Message shown when a search returns no matches.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the automatic source loading toggle.
	 *
	 * @return void
	 */
	public function render_load_all_public_post_types_field(): void {
		$settings = $this->get_search_sources_settings();
		$this->render_checkbox_field(
			$this->field_id( 'search_sources', 'load_all_public_post_types' ),
			$this->field_name( 'search_sources', 'load_all_public_post_types' ),
			! empty( $settings['load_all_public_post_types'] ),
			__( 'Use every public post type as a searchable source.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the searchable post type checklist.
	 *
	 * @return void
	 */
	public function render_searchable_post_types_field(): void {
		$enabled_post_types = $this->get_enabled_post_types();
		$post_types         = $this->get_public_post_types();

		if ( empty( $post_types ) ) {
			echo '<p>' . esc_html__( 'No public post types are available.', 'search-analytics-insights' ) . '</p>';
			return;
		}

		echo '<fieldset class="searchlens-post-types-fieldset">';

		foreach ( $post_types as $post_type ) {
			$checked = in_array( $post_type->name, $enabled_post_types, true );
			?>
			<label class="searchlens-post-type-option" for="searchlens-post-type-<?php echo esc_attr( $post_type->name ); ?>">
				<input
					id="searchlens-post-type-<?php echo esc_attr( $post_type->name ); ?>"
					type="checkbox"
					name="<?php echo esc_attr( Constants::OPTION_SETTINGS ); ?>[search_sources][searchable_post_types][]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( $checked ); ?>
				/>
				<span><?php echo esc_html( $post_type->labels->name ); ?></span>
				<code><?php echo esc_html( $post_type->name ); ?></code>
			</label>
			<?php
		}

		echo '</fieldset>';
	}

	/**
	 * Render the logged-in tracking toggle.
	 *
	 * @return void
	 */
	public function render_track_logged_in_users_field(): void {
		$settings = $this->get_analytics_settings();
		$this->render_checkbox_field(
			$this->field_id( 'analytics', 'track_logged_in_users' ),
			$this->field_name( 'analytics', 'track_logged_in_users' ),
			! empty( $settings['track_logged_in_users'] ),
			__( 'Count search events from signed-in users.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the guest tracking toggle.
	 *
	 * @return void
	 */
	public function render_track_guests_field(): void {
		$settings = $this->get_analytics_settings();
		$this->render_checkbox_field(
			$this->field_id( 'analytics', 'track_guests' ),
			$this->field_name( 'analytics', 'track_guests' ),
			! empty( $settings['track_guests'] ),
			__( 'Count search events from anonymous visitors.', 'search-analytics-insights' )
		);
	}

	/**
	 * Render the retention period field.
	 *
	 * @return void
	 */
	public function render_search_retention_period_field(): void {
		$settings = $this->get_analytics_settings();
		$this->render_number_field(
			$this->field_id( 'analytics', 'search_retention_period' ),
			$this->field_name( 'analytics', 'search_retention_period' ),
			(int) $settings['search_retention_period'],
			1,
			3650,
			__( 'Number of days to keep search records before cleanup.', 'search-analytics-insights' )
		);
	}

	/**
	 * Get the consolidated settings array.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_settings(): array {
		$stored = get_option( Constants::OPTION_SETTINGS, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return $this->sanitize_settings( array_replace_recursive( $this->get_default_settings(), $this->get_legacy_settings(), $stored ) );
	}

	/**
	 * Migrate legacy options into the consolidated settings option.
	 *
	 * @return void
	 */
	public function maybe_migrate_legacy_settings(): void {
		if ( false !== get_option( Constants::OPTION_SETTINGS, false ) ) {
			return;
		}

		add_option( Constants::OPTION_SETTINGS, $this->sanitize_settings( array_replace_recursive( $this->get_default_settings(), $this->get_legacy_settings() ) ) );
	}

	/**
	 * Get the search form settings.
	 *
	 * @return array{placeholder: string, button_text: string, show_button: bool, form_style: string}
	 */
	public function get_search_form_settings(): array {
		$settings = $this->get_settings();

		return $settings['search_form'];
	}

	/**
	 * Get the saved placeholder text.
	 *
	 * @return string
	 */
	public function get_placeholder(): string {
		$settings = $this->get_search_form_settings();

		return (string) $settings['placeholder'];
	}

	/**
	 * Get the saved button text.
	 *
	 * @return string
	 */
	public function get_button_text(): string {
		$settings = $this->get_search_form_settings();

		return (string) $settings['button_text'];
	}

	/**
	 * Get the saved form style.
	 *
	 * @return string
	 */
	public function get_form_style(): string {
		$settings = $this->get_search_form_settings();

		return (string) $settings['form_style'];
	}

	/**
	 * Get the saved show button.
	 *
	 * @return bool
	 */
	public function get_show_button(): bool {
		$settings = $this->get_search_form_settings();

		return ! empty( $settings['show_button'] );
	}

	/**
	 * Get the AJAX search settings in the format existing callers expect.
	 *
	 * @return array{enabled: bool, max_results: int, minimum_characters: int, debounce_time: int, show_featured_images: bool}
	 */
	public function get_ajax_search_settings(): array {
		$settings = $this->get_settings();

		return array(
			'enabled'              => ! empty( $settings['ajax_search']['enabled'] ),
			'max_results'          => (int) $settings['ajax_search']['maximum_results'],
			'minimum_characters'   => (int) $settings['ajax_search']['minimum_characters'],
			'debounce_time'        => (int) $settings['ajax_search']['debounce_time'],
			'show_featured_images' => ! empty( $settings['search_results']['show_featured_images'] ),
		);
	}

	/**
	 * Determine whether AJAX search is enabled.
	 *
	 * @return bool
	 */
	public function is_ajax_search_enabled(): bool {
		$settings = $this->get_ajax_search_settings();

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Get the maximum results setting.
	 *
	 * @return int
	 */
	public function get_maximum_results(): int {
		return $this->get_max_results();
	}

	/**
	 * Get the debounce time setting.
	 *
	 * @return int
	 */
	public function get_debounce_time(): int {
		$settings = $this->get_ajax_search_settings();

		return isset( $settings['debounce_time'] ) ? absint( $settings['debounce_time'] ) : self::DEFAULT_DEBOUNCE_TIME;
	}

	/**
	 * Get the saved enabled post types.
	 *
	 * @return array<int, string>
	 */
	public function get_enabled_post_types(): array {
		$settings = $this->get_search_sources_settings();

		if ( ! empty( $settings['load_all_public_post_types'] ) ) {
			return wp_list_pluck( $this->get_public_post_types(), 'name' );
		}

		return $this->sanitize_enabled_post_types( $settings['searchable_post_types'] );
	}

	/**
	 * Get the configured maximum results.
	 *
	 * @return int
	 */
	public function get_max_results(): int {
		$settings = $this->get_ajax_search_settings();

		return isset( $settings['max_results'] ) ? absint( $settings['max_results'] ) : self::DEFAULT_MAX_RESULTS;
	}

	/**
	 * Get the minimum search length.
	 *
	 * @return int
	 */
	public function get_minimum_characters(): int {
		$settings = $this->get_ajax_search_settings();

		return isset( $settings['minimum_characters'] ) ? absint( $settings['minimum_characters'] ) : self::DEFAULT_MINIMUM_CHARACTERS;
	}

	/**
	 * Get the search results settings.
	 *
	 * @return array{show_featured_images: bool, show_post_type_label: bool, no_results_message: string}
	 */
	public function get_search_results_settings(): array {
		$settings = $this->get_settings();

		return $settings['search_results'];
	}

	/**
	 * Get whether featured images should be shown.
	 *
	 * @return bool
	 */
	public function get_show_featured_images(): bool {
		$settings = $this->get_search_results_settings();

		return ! empty( $settings['show_featured_images'] );
	}

	/**
	 * Get whether post type label should be shown.
	 *
	 * @return bool
	 */
	public function get_show_post_type_label(): bool {
		$settings = $this->get_search_results_settings();

		return ! empty( $settings['show_post_type_label'] );
	}

	/**
	 * Get the no results message.
	 *
	 * @return string
	 */
	public function get_no_results_message(): string {
		$settings = $this->get_search_results_settings();

		return (string) $settings['no_results_message'];
	}

	/**
	 * Get the search sources settings.
	 *
	 * @return array{load_all_public_post_types: bool, searchable_post_types: array<int, string>}
	 */
	public function get_search_sources_settings(): array {
		$settings = $this->get_settings();

		return $settings['search_sources'];
	}

	/**
	 * Get searchable post types.
	 *
	 * @return array<int, string>
	 */
	public function get_searchable_post_types(): array {
		return $this->get_enabled_post_types();
	}

	/**
	 * Get the analytics settings.
	 *
	 * @return array{track_logged_in_users: bool, track_guests: bool, search_retention_period: int}
	 */
	public function get_analytics_settings(): array {
		$settings = $this->get_settings();

		return $settings['analytics'];
	}

	/**
	 * Determine whether featured images should be shown.
	 *
	 * @return bool
	 */
	public function show_featured_images(): bool {
		$settings = $this->get_search_results_settings();

		return ! empty( $settings['show_featured_images'] );
	}

	/**
	 * Sanitize the consolidated settings array.
	 *
	 * @param mixed $value Raw option value.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function sanitize_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'search_form'    => $this->sanitize_search_form_settings( $value['search_form'] ?? array() ),
			'ajax_search'    => $this->sanitize_ajax_search_settings_section( $value['ajax_search'] ?? array() ),
			'search_results' => $this->sanitize_search_results_settings( $value['search_results'] ?? array() ),
			'search_sources' => $this->sanitize_search_sources_settings( $value['search_sources'] ?? array() ),
			'analytics'      => $this->sanitize_analytics_settings( $value['analytics'] ?? array() ),
		);
	}

	/**
	 * Sanitize legacy enabled post types.
	 *
	 * @param mixed $value Raw option value.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_enabled_post_types( $value ): array {
		$settings = $this->sanitize_search_sources_settings(
			array(
				'load_all_public_post_types' => false,
				'searchable_post_types'      => $value,
			)
		);

		return $settings['searchable_post_types'];
	}

	/**
	 * Sanitize legacy AJAX search settings.
	 *
	 * @param mixed $value Raw option value.
	 *
	 * @return array{max_results: int, minimum_characters: int, show_featured_images: bool}
	 */
	public function sanitize_ajax_search_settings( $value ): array {
		$settings = $this->sanitize_ajax_search_settings_section( is_array( $value ) ? $value : array() );

		return array(
			'max_results'          => $settings['maximum_results'],
			'minimum_characters'   => $settings['minimum_characters'],
			'show_featured_images' => ! empty( $value['show_featured_images'] ),
		);
	}

	/**
	 * Get public post type objects, excluding internal types.
	 *
	 * @return array<int, \WP_Post_Type>
	 */
	public function get_public_post_types(): array {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$post_types = array_filter( $post_types, array( $this, 'is_allowed_post_type' ) );

		uasort(
			$post_types,
			static function ( \WP_Post_Type $left, \WP_Post_Type $right ): int {
				return strcasecmp( $left->labels->name, $right->labels->name );
			}
		);

		return array_values( $post_types );
	}

	/**
	 * Check whether a post type should be shown.
	 *
	 * @param \WP_Post_Type $post_type Post type object.
	 *
	 * @return bool
	 */
	private function is_allowed_post_type( \WP_Post_Type $post_type ): bool {
		return ! in_array( $post_type->name, self::DISALLOWED_TYPES, true );
	}

	/**
	 * Default enabled post types.
	 *
	 * @return array<int, string>
	 */
	private function get_default_enabled_post_types(): array {
		$defaults = array( 'post', 'page' );
		$allowed  = wp_list_pluck( $this->get_public_post_types(), 'name' );

		return array_values( array_intersect( $defaults, $allowed ) );
	}

	/**
	 * Get legacy values from the old option keys.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_legacy_settings(): array {
		$legacy               = array();
		$legacy_post_types    = get_option( 'search_analytics_insights_post_types', null );
		$legacy_ajax_settings = get_option( 'search_analytics_insights_ajax_search_settings', null );

		if ( is_array( $legacy_post_types ) && ! empty( $legacy_post_types ) ) {
			$legacy['search_sources'] = array(
				'load_all_public_post_types' => false,
				'searchable_post_types'      => $this->sanitize_enabled_post_types( $legacy_post_types ),
			);
		}

		if ( is_array( $legacy_ajax_settings ) ) {
			$legacy['ajax_search'] = array(
				'enabled'            => true,
				'minimum_characters' => isset( $legacy_ajax_settings['minimum_characters'] ) ? absint( $legacy_ajax_settings['minimum_characters'] ) : self::DEFAULT_MINIMUM_CHARACTERS,
				'maximum_results'    => isset( $legacy_ajax_settings['max_results'] ) ? absint( $legacy_ajax_settings['max_results'] ) : self::DEFAULT_MAX_RESULTS,
				'debounce_time'      => self::DEFAULT_DEBOUNCE_TIME,
			);

			$legacy['search_results'] = array(
				'show_featured_images' => ! empty( $legacy_ajax_settings['show_featured_images'] ),
			);
		}

		return $legacy;
	}

	/**
	 * Get the default consolidated settings.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_default_settings(): array {
		return array(
			'search_form'    => array(
				'placeholder' => self::DEFAULT_PLACEHOLDER,
				'button_text' => self::DEFAULT_BUTTON_TEXT,
				'show_button' => true,
				'form_style'  => self::DEFAULT_FORM_STYLE,
			),
			'ajax_search'    => array(
				'enabled'            => true,
				'minimum_characters' => self::DEFAULT_MINIMUM_CHARACTERS,
				'maximum_results'    => self::DEFAULT_MAX_RESULTS,
				'debounce_time'      => self::DEFAULT_DEBOUNCE_TIME,
			),
			'search_results' => array(
				'show_featured_images' => true,
				'show_post_type_label' => true,
				'no_results_message'   => self::DEFAULT_NO_RESULTS_MESSAGE,
			),
			'search_sources' => array(
				'load_all_public_post_types' => false,
				'searchable_post_types'      => $this->get_default_enabled_post_types(),
			),
			'analytics'      => array(
				'track_logged_in_users'   => false,
				'track_guests'            => true,
				'search_retention_period' => self::DEFAULT_SEARCH_RETENTION_PERIOD,
			),
		);
	}

	/**
	 * Sanitize the Search Form settings.
	 *
	 * @param mixed $value Raw section value.
	 *
	 * @return array{placeholder: string, button_text: string, show_button: bool, form_style: string}
	 */
	private function sanitize_search_form_settings( $value ): array {
		$value      = is_array( $value ) ? $value : array();
		$form_style = isset( $value['form_style'] ) ? sanitize_key( (string) $value['form_style'] ) : self::DEFAULT_FORM_STYLE;

		if ( ! in_array( $form_style, array( 'rounded', 'rectangle', 'underlined' ), true ) ) {
			$form_style = self::DEFAULT_FORM_STYLE;
		}

		return array(
			'placeholder' => $this->sanitize_text_setting( $value['placeholder'] ?? self::DEFAULT_PLACEHOLDER ),
			'button_text' => $this->sanitize_text_setting( $value['button_text'] ?? self::DEFAULT_BUTTON_TEXT ),
			'show_button' => ! empty( $value['show_button'] ),
			'form_style'  => $form_style,
		);
	}

	/**
	 * Sanitize the AJAX Search settings.
	 *
	 * @param mixed $value Raw section value.
	 *
	 * @return array{enabled: bool, minimum_characters: int, maximum_results: int, debounce_time: int}
	 */
	private function sanitize_ajax_search_settings_section( $value ): array {
		$value              = is_array( $value ) ? $value : array();
		$maximum_results    = isset( $value['maximum_results'] ) ? absint( $value['maximum_results'] ) : self::DEFAULT_MAX_RESULTS;
		$minimum_characters = isset( $value['minimum_characters'] ) ? absint( $value['minimum_characters'] ) : self::DEFAULT_MINIMUM_CHARACTERS;
		$debounce_time      = isset( $value['debounce_time'] ) ? absint( $value['debounce_time'] ) : self::DEFAULT_DEBOUNCE_TIME;

		$maximum_results    = max( 1, min( 20, $maximum_results ) );
		$minimum_characters = max( 1, min( 10, $minimum_characters ) );
		$debounce_time      = max( 50, min( 2000, $debounce_time ) );

		if ( $minimum_characters > $maximum_results ) {
			$minimum_characters = $maximum_results;
		}

		return array(
			'enabled'            => ! empty( $value['enabled'] ),
			'minimum_characters' => $minimum_characters,
			'maximum_results'    => $maximum_results,
			'debounce_time'      => $debounce_time,
		);
	}

	/**
	 * Sanitize the Search Results settings.
	 *
	 * @param mixed $value Raw section value.
	 *
	 * @return array{show_featured_images: bool, show_post_type_label: bool, no_results_message: string}
	 */
	private function sanitize_search_results_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'show_featured_images' => ! empty( $value['show_featured_images'] ),
			'show_post_type_label' => ! empty( $value['show_post_type_label'] ),
			'no_results_message'   => $this->sanitize_textarea_setting( $value['no_results_message'] ?? self::DEFAULT_NO_RESULTS_MESSAGE ),
		);
	}

	/**
	 * Sanitize the Search Sources settings.
	 *
	 * @param mixed $value Raw section value.
	 *
	 * @return array{load_all_public_post_types: bool, searchable_post_types: array<int, string>}
	 */
	private function sanitize_search_sources_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		if ( ! empty( $value['load_all_public_post_types'] ) ) {
			return array(
				'load_all_public_post_types' => true,
				'searchable_post_types'      => wp_list_pluck( $this->get_public_post_types(), 'name' ),
			);
		}

		$allowed_post_types  = wp_list_pluck( $this->get_public_post_types(), 'name' );
		$selected_post_types = array();

		if ( isset( $value['searchable_post_types'] ) && is_array( $value['searchable_post_types'] ) ) {
			foreach ( $value['searchable_post_types'] as $post_type ) {
				$post_type = sanitize_key( (string) $post_type );

				if ( '' === $post_type || ! in_array( $post_type, $allowed_post_types, true ) ) {
					continue;
				}

				$selected_post_types[] = $post_type;
			}
		}

		$selected_post_types = array_values( array_unique( $selected_post_types ) );

		if ( empty( $selected_post_types ) ) {
			$selected_post_types = $this->get_default_enabled_post_types();
		}

		return array(
			'load_all_public_post_types' => ! empty( $value['load_all_public_post_types'] ),
			'searchable_post_types'      => $selected_post_types,
		);
	}

	/**
	 * Sanitize the Analytics settings.
	 *
	 * @param mixed $value Raw section value.
	 *
	 * @return array{track_logged_in_users: bool, track_guests: bool, search_retention_period: int}
	 */
	private function sanitize_analytics_settings( $value ): array {
		$value                   = is_array( $value ) ? $value : array();
		$search_retention_period = isset( $value['search_retention_period'] ) ? absint( $value['search_retention_period'] ) : self::DEFAULT_SEARCH_RETENTION_PERIOD;

		return array(
			'track_logged_in_users'   => ! empty( $value['track_logged_in_users'] ),
			'track_guests'            => ! empty( $value['track_guests'] ),
			'search_retention_period' => max( 1, min( 3650, $search_retention_period ) ),
		);
	}

	/**
	 * Render a text field.
	 *
	 * @param string $id Field id.
	 * @param string $name Field name.
	 * @param string $value Current value.
	 * @param string $description Field description.
	 *
	 * @return void
	 */
	private function render_text_field( string $id, string $name, string $value, string $description ): void {
		?>
		<input id="<?php echo esc_attr( $id ); ?>" class="regular-text" type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a textarea field.
	 *
	 * @param string $id Field id.
	 * @param string $name Field name.
	 * @param string $value Current value.
	 * @param string $description Field description.
	 *
	 * @return void
	 */
	private function render_textarea_field( string $id, string $name, string $value, string $description ): void {
		?>
		<textarea id="<?php echo esc_attr( $id ); ?>" class="large-text" name="<?php echo esc_attr( $name ); ?>" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a number field.
	 *
	 * @param string $id Field id.
	 * @param string $name Field name.
	 * @param int    $value Current value.
	 * @param int    $min Minimum value.
	 * @param int    $max Maximum value.
	 * @param string $description Field description.
	 *
	 * @return void
	 */
	private function render_number_field( string $id, string $name, int $value, int $min, int $max, string $description ): void {
		?>
		<input id="<?php echo esc_attr( $id ); ?>" class="small-text" type="number" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param string $id Field id.
	 * @param string $name Field name.
	 * @param bool   $checked Whether the field is checked.
	 * @param string $description Field description.
	 *
	 * @return void
	 */
	private function render_checkbox_field( string $id, string $name, bool $checked, string $description ): void {
		?>
		<label class="searchlens-toggle" for="<?php echo esc_attr( $id ); ?>">
			<input id="<?php echo esc_attr( $id ); ?>" type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( $checked ); ?> />
			<span><?php echo esc_html( $description ); ?></span>
		</label>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param string                $id Field id.
	 * @param string                $name Field name.
	 * @param string                $value Current value.
	 * @param array<string, string> $choices Allowed choices.
	 * @param string                $description Field description.
	 *
	 * @return void
	 */
	private function render_select_field( string $id, string $name, string $value, array $choices, string $description ): void {
		?>
		<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $choices as $choice_value => $choice_label ) : ?>
				<option value="<?php echo esc_attr( $choice_value ); ?>" <?php selected( $value, $choice_value ); ?>><?php echo esc_html( $choice_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Build a nested field name.
	 *
	 * @param string $section Section key.
	 * @param string $key Setting key.
	 *
	 * @return string
	 */
	private function field_name( string $section, string $key ): string {
		return Constants::OPTION_SETTINGS . '[' . $section . '][' . $key . ']';
	}

	/**
	 * Build a field id.
	 *
	 * @param string $section Section key.
	 * @param string $key Setting key.
	 *
	 * @return string
	 */
	private function field_id( string $section, string $key ): string {
		return 'searchlens-' . $section . '-' . $key;
	}

	/**
	 * Sanitize a plain text setting.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return string
	 */
	private function sanitize_text_setting( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize a textarea setting.
	 *
	 * @param mixed $value Value to sanitize.
	 *
	 * @return string
	 */
	private function sanitize_textarea_setting( $value ): string {
		$value = sanitize_textarea_field( (string) $value );

		return '' === $value ? self::DEFAULT_NO_RESULTS_MESSAGE : $value;
	}
}
