<?php
/**
 * Shortcode registrar.
 *
 * @package VPLens
 */

namespace VPLens\Shortcodes;

use VPLens\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers all plugin shortcodes in one place.
 */
final class Registrar {
	private AjaxSearchForm $ajax_search_form;
	private SearchForm $search_form;
	private PopularSearches $popular_searches;
	private TrendingSearches $trending_searches;
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param AjaxSearchForm   $ajax_search_form   AJAX search shortcode handler.
	 * @param SearchForm       $search_form        Search form shortcode handler.
	 * @param PopularSearches  $popular_searches   Popular searches shortcode handler.
	 * @param TrendingSearches $trending_searches  Trending searches shortcode handler.
	 * @param Settings         $settings           Settings instance.
	 */
	public function __construct(
		AjaxSearchForm $ajax_search_form,
		SearchForm $search_form,
		PopularSearches $popular_searches,
		TrendingSearches $trending_searches,
		Settings $settings
	) {
		$this->ajax_search_form  = $ajax_search_form;
		$this->search_form       = $search_form;
		$this->popular_searches  = $popular_searches;
		$this->trending_searches = $trending_searches;
		$this->settings          = $settings;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
	}

	/**
	 * Register all plugin shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		// New primary shortcodes
		add_shortcode( 'vplens_form', array( $this, 'render_search_form' ) );
		add_shortcode( 'vplens_ajax_form', array( $this, 'render_ajax_search_form' ) );
		add_shortcode( 'vplens_popular', array( $this->popular_searches, 'render' ) );
		add_shortcode( 'vplens_trending', array( $this->trending_searches, 'render' ) );

		// Legacy backward-compatible shortcodes
		add_shortcode( 'searchlens_form', array( $this, 'render_search_form' ) );
		add_shortcode( 'searchlens_ajax_form', array( $this, 'render_ajax_search_form' ) );
		add_shortcode( 'searchlens_popular', array( $this->popular_searches, 'render' ) );
		add_shortcode( 'searchlens_trending', array( $this->trending_searches, 'render' ) );

		add_shortcode( 'search_insights_form', array( $this, 'render_search_form' ) );
		add_shortcode( 'search_insights_ajax_form', array( $this, 'render_ajax_search_form' ) );
		add_shortcode( 'search_insights_popular', array( $this->popular_searches, 'render' ) );
		add_shortcode( 'search_insights_trending', array( $this->trending_searches, 'render' ) );
	}

	/**
	 * Render the primary search form shortcode.
	 *
	 * Depending on plugin settings, this will render either the live AJAX form
	 * or the standard native WordPress search form.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_search_form( array $attributes = array() ): string {
		if ( $this->settings->is_ajax_search_enabled() ) {
			return $this->ajax_search_form->render( $attributes );
		}

		return $this->search_form->render( $attributes );
	}

	/**
	 * Render the live AJAX search form shortcode directly.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render_ajax_search_form( array $attributes = array() ): string {
		return $this->ajax_search_form->render( $attributes );
	}
}
