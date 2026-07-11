<?php
/**
 * AJAX search service.
 *
 * @package VPLens
 */

namespace VPLens\Ajax;

use VPLens\Admin\Settings;
use VPLens\Database\Repository\SearchRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides live search results for published posts and pages.
 */
final class SearchService {
	private SearchRepository $repository;
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param SearchRepository $repository Search repository instance.
	 * @param Settings         $settings   Post type settings instance.
	 */
	public function __construct( SearchRepository $repository, Settings $settings ) {
		$this->repository = $repository;
		$this->settings   = $settings;
	}

	/**
	 * Search published content.
	 *
	 * @param string $search_term Search term.
	 * @param int    $limit Result limit.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int, matched_post_types: array<int, string>}
	 */
	public function search( string $search_term, int $limit = 10 ): array {
		$search_term = sanitize_text_field( $search_term );
		$limit       = max( 1, min( $this->settings->get_max_results(), absint( $limit ) ) );

		$term_length = function_exists( 'mb_strlen' ) ? mb_strlen( $search_term ) : strlen( $search_term );

		if ( $term_length < $this->settings->get_minimum_characters() ) {
			return array(
				'items'              => array(),
				'total'              => 0,
				'matched_post_types' => array(),
			);
		}

		$post_types         = $this->settings->get_searchable_post_types();
		$results            = $this->repository->search_content( $search_term, $post_types, $limit, $this->settings->get_show_featured_images() );
		$matched_post_types = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( array $item ): string {
							return isset( $item['post_type'] ) ? sanitize_key( (string) $item['post_type'] ) : '';
						},
						isset( $results['items'] ) && is_array( $results['items'] ) ? $results['items'] : array()
					),
					static function ( string $post_type ): bool {
						return '' !== $post_type;
					}
				)
			)
		);

		$items = array();

		foreach ( isset( $results['items'] ) && is_array( $results['items'] ) ? $results['items'] : array() as $item ) {
			$items[] = array(
				'id'              => isset( $item['id'] ) ? absint( $item['id'] ) : 0,
				'title'           => isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '',
				'url'             => isset( $item['url'] ) ? esc_url_raw( (string) $item['url'] ) : '',
				'featured_image'  => isset( $item['featured_image'] ) ? esc_url_raw( (string) $item['featured_image'] ) : '',
				'post_type'       => isset( $item['post_type'] ) ? sanitize_key( (string) $item['post_type'] ) : '',
				'post_type_label' => isset( $item['post_type'] ) ? $this->get_post_type_label( (string) $item['post_type'] ) : '',
			);
		}

		return array(
			'items'              => $items,
			'total'              => isset( $results['total'] ) ? absint( $results['total'] ) : 0,
			'matched_post_types' => $matched_post_types,
		);
	}

	/**
	 * Get the minimum search length.
	 *
	 * @return int
	 */
	public function get_minimum_characters(): int {
		return $this->settings->get_minimum_characters();
	}

	/**
	 * Get the configured maximum results.
	 *
	 * @return int
	 */
	public function get_max_results(): int {
		return $this->settings->get_max_results();
	}

	/**
	 * Get a readable post type label.
	 *
	 * @param string $post_type Post type slug.
	 *
	 * @return string
	 */
	private function get_post_type_label( string $post_type ): string {
		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object instanceof \WP_Post_Type && ! empty( $post_type_object->labels->singular_name ) ) {
			return sanitize_text_field( (string) $post_type_object->labels->singular_name );
		}

		return sanitize_text_field( $post_type );
	}

	/**
	 * Determine whether AJAX search is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->settings->is_ajax_search_enabled();
	}
}
