<?php
/**
 * AJAX search controller.
 *
 * @package VPLens
 */

namespace VPLens\Ajax;

use VPLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX search requests.
 */
final class SearchController {
	private SearchService $search_service;
	private SearchTracker $search_tracker;

	/**
	 * Constructor.
	 *
	 * @param SearchService $search_service Search service instance.
	 * @param SearchTracker $search_tracker Search tracker instance.
	 */
	public function __construct( SearchService $search_service, SearchTracker $search_tracker ) {
		$this->search_service = $search_service;
		$this->search_tracker = $search_tracker;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_' . Constants::AJAX_ACTION_SEARCH, array( $this, 'handle_search' ) );
		add_action( 'wp_ajax_nopriv_' . Constants::AJAX_ACTION_SEARCH, array( $this, 'handle_search' ) );
	}

	/**
	 * Handle the AJAX search request.
	 *
	 * @return void
	 */
	public function handle_search(): void {
		check_ajax_referer( Constants::NONCE_ACTION, 'nonce' );

		if ( ! $this->search_service->is_enabled() ) {
			wp_send_json_error(
				array(
					'message' => __( 'AJAX search is disabled.', 'search-analytics-insights' ),
				),
				403
			);
		}

		$search_term        = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$term_length        = function_exists( 'mb_strlen' ) ? mb_strlen( $search_term ) : strlen( $search_term );
		$minimum_characters = $this->search_service->get_minimum_characters();

		if ( $term_length < $minimum_characters ) {
			wp_send_json_error(
				array(
					'message' => sprintf(
						/* translators: %d: minimum character count */
						__( 'Please enter at least %d characters.', 'search-analytics-insights' ),
						$minimum_characters
					),
				),
				400
			);
		}

		$page_title = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['page_title'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$page_url   = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( (string) $_POST['page_url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$referrer   = isset( $_POST['referrer'] ) ? esc_url_raw( wp_unslash( (string) $_POST['referrer'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$page_type  = isset( $_POST['page_type'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['page_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$results = $this->search_service->search( $search_term, $this->search_service->get_max_results() );
		$this->search_tracker->record_search(
			$search_term,
			(int) $results['total'],
			isset( $results['matched_post_types'] ) && is_array( $results['matched_post_types'] ) ? $results['matched_post_types'] : array(),
			array(
				'page_title' => $page_title,
				'page_url'   => $page_url,
				'referrer'   => $referrer,
				'page_type'  => $page_type,
			)
		);

		wp_send_json_success(
			array(
				'items' => $results['items'],
			)
		);
	}
}
