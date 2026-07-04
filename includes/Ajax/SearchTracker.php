<?php
/**
 * AJAX search tracker.
 *
 * @package SearchLens
 */

namespace SearchLens\Ajax;

use SearchLens\Core\Constants;
use SearchLens\Database\Repository\SearchRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Records AJAX search events in the analytics table.
 */
final class SearchTracker {

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
	 * Store an AJAX search event.
	 *
	 * @param string                $search_term        Search term.
	 * @param int                   $result_count       Result count.
	 * @param array<int, string>    $matched_post_types Matched post type slugs.
	 * @param array<string, string> $page_data       Page tracking data parameters.
	 *
	 * @return int|false
	 */
	public function record_search( string $search_term, int $result_count, array $matched_post_types = array(), array $page_data = array() ) {
		$search_term        = sanitize_text_field( $search_term );
		$matched_post_types = array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', $matched_post_types ),
					static function ( string $post_type ): bool {
						return '' !== $post_type;
					}
				)
			)
		);

		if ( '' === trim( $search_term ) ) {
			return false;
		}

		$raw_title  = $page_data['page_title'] ?? '';
		$page_url   = $page_data['page_url'] ?? '';
		$page_title = \SearchLens\Helpers\PageHelper::resolve_page_title( $page_url, $raw_title );

		return $this->repository->insert(
			array(
				'search_term'        => $search_term,
				'searched_at'        => current_time( 'mysql', true ),
				'source'             => Constants::SOURCE_AJAX_SEARCH,
				'matched_post_types' => $matched_post_types ? implode( ',', $matched_post_types ) : '',
				'result_count'       => absint( $result_count ),
				'user_id'            => get_current_user_id() ? absint( get_current_user_id() ) : null,
				'session_id'         => $this->get_session_id(),
				'blog_id'            => get_current_blog_id(),
				'page_title'         => sanitize_text_field( $page_title ),
				'page_url'           => esc_url_raw( $page_url ),
				'referrer'           => esc_url_raw( $page_data['referrer'] ?? '' ),
				'page_type'          => sanitize_text_field( $page_data['page_type'] ?? '' ),
			)
		);
	}

	/**
	 * Get the anonymous session id.
	 *
	 * @return string|null
	 */
	private function get_session_id(): ?string {
		if ( ! empty( $_COOKIE['searchlens_session'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( (string) $_COOKIE['searchlens_session'] ) );
		}

		if ( ! empty( $_COOKIE['search_analytics_insights_session'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return sanitize_text_field( wp_unslash( (string) $_COOKIE['search_analytics_insights_session'] ) );
		}

		return null;
	}
}
