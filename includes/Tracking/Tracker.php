<?php
/**
 * Search tracker.
 *
 * @package VPLens
 */

namespace VPLens\Tracking;

use VPLens\Core\Constants;
use VPLens\Database\Repository\SearchRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Captures WordPress search requests and stores analytics events.
 */
final class Tracker {

	private const COOKIE_NAME     = 'vplens_session';
	private const COOKIE_LIFETIME = MONTH_IN_SECONDS;

	private SearchRepository $repository;

	/**
	 * Constructor.
	 *
	 * @param SearchRepository $repository Search repository instance.
	 */
	public function __construct( SearchRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'maybe_issue_session_cookie' ) );
		add_action( 'wp', array( $this, 'track_search' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Enqueue frontend script to capture search context page data.
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts(): void {
		wp_enqueue_script(
			'vplens-frontend',
			VPLENS_URL . 'assets/js/frontend.js',
			array(),
			Constants::VERSION,
			true
		);

		wp_localize_script(
			'vplens-frontend',
			'vplens_data',
			array(
				'page_title' => \VPLens\Helpers\PageHelper::get_current_page_title(),
				'page_url'   => \VPLens\Helpers\PageHelper::get_current_page_url(),
				'page_type'  => \VPLens\Helpers\PageHelper::get_current_page_type(),
			)
		);
	}

	/**
	 * Issue an anonymous session cookie if one does not exist.
	 *
	 * @return void
	 */
	public function maybe_issue_session_cookie(): void {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return;
		}

		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) || ! empty( $_COOKIE['search_analytics_insights_session'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return;
		}

		$session_id = $this->generate_session_id();

		if ( headers_sent() ) {
			return;
		}

		setcookie(
			self::COOKIE_NAME,
			$session_id,
			array(
				'expires'  => time() + self::COOKIE_LIFETIME,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);

		$_COOKIE[ self::COOKIE_NAME ] = $session_id; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Track the current WordPress search request.
	 *
	 * @return void
	 */
	public function track_search(): void {
		if ( is_admin() || ! is_search() || is_feed() ) {
			return;
		}

		$search_term = get_search_query( false );

		if ( '' === trim( $search_term ) ) {
			return;
		}

		$raw_title  = isset( $_GET['vplens_page_title'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['vplens_page_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_url   = isset( $_GET['vplens_page_url'] ) ? esc_url_raw( wp_unslash( (string) $_GET['vplens_page_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_title = \VPLens\Helpers\PageHelper::resolve_page_title( $page_url, $raw_title );

		$this->repository->insert(
			array(
				'search_term'  => sanitize_text_field( $search_term ),
				'source'       => Constants::SOURCE_WORDPRESS_SEARCH,
				'searched_at'  => current_time( 'mysql', true ),
				'result_count' => isset( $GLOBALS['wp_query']->found_posts ) ? absint( $GLOBALS['wp_query']->found_posts ) : 0,
				'user_id'      => get_current_user_id() ? absint( get_current_user_id() ) : null,
				'session_id'   => $this->get_session_id(),
				'blog_id'      => get_current_blog_id(),
				'page_title'   => sanitize_text_field( $page_title ),
				'page_url'     => $page_url,
				'referrer'     => isset( $_GET['vplens_referrer'] ) ? esc_url_raw( wp_unslash( (string) $_GET['vplens_referrer'] ) ) : ( isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( (string) $_SERVER['HTTP_REFERER'] ) ) : '' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'page_type'    => isset( $_GET['vplens_page_type'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['vplens_page_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);
	}

	/**
	 * Get the anonymous session id.
	 *
	 * @return string|null
	 */
	private function get_session_id(): ?string {
		if ( ! empty( $_COOKIE[ self::COOKIE_NAME ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( (string) $_COOKIE[ self::COOKIE_NAME ] ) );
		}

		if ( ! empty( $_COOKIE['search_analytics_insights_session'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( (string) $_COOKIE['search_analytics_insights_session'] ) );
		}

		return null;
	}

	/**
	 * Generate a new anonymous session id.
	 *
	 * @return string
	 */
	private function generate_session_id(): string {
		return hash( 'sha256', wp_generate_uuid4() . wp_rand() . microtime( true ) );
	}
}
