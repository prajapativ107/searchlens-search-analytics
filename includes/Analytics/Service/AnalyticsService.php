<?php
/**
 * Analytics service.
 *
 * @package VPLens
 */

namespace VPLens\Analytics\Service;

use VPLens\Database\Repository\SearchRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Provides analytics data for admin and API consumers.
 */
final class AnalyticsService {
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
	 * Get the dashboard summary.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return array<string, int>
	 */
	public function get_dashboard_summary( array $filters = array() ): array {
		return $this->repository->get_summary( $filters );
	}

	/**
	 * Get aggregated search activity grouped by term and user.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $page    Page number.
	 * @param int                  $per_page Items per page.
	 *
	 * @return array<string, mixed>
	 */
	public function get_aggregated_search_activity( array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		return $this->repository->get_aggregated_search_activity( $filters, $page, $per_page );
	}

	/**
	 * Get recent raw search activity.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent_search_activity( array $filters = array(), int $limit = 20 ): array {
		return $this->repository->get_recent_search_activity( $filters, $limit );
	}

	/**
	 * Get the top search terms.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit   Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_search_terms( array $filters = array(), int $limit = 10 ): array {
		return $this->repository->get_top_terms( $filters, $limit );
	}

	/**
	 * Get searches per day.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_searches_per_day( array $filters = array() ): array {
		return $this->repository->get_daily_counts( $filters );
	}

	/**
	 * Get paginated search records.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $page    Page number.
	 * @param int                  $per_page Items per page.
	 *
	 * @return array<string, mixed>
	 */
	public function get_search_records( array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		return $this->repository->get_searches( $filters, $page, $per_page );
	}

	/**
	 * Get the top search pages by title.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit   Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_pages_by_title( array $filters = array(), int $limit = 10 ): array {
		return $this->repository->get_top_pages_by_title( $filters, $limit );
	}

	/**
	 * Get the top search pages by URL.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit   Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_pages_by_url( array $filters = array(), int $limit = 10 ): array {
		return $this->repository->get_top_pages_by_url( $filters, $limit );
	}

	/**
	 * Get search count grouped by page type.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit   Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_searches_by_page_type( array $filters = array(), int $limit = 10 ): array {
		return $this->repository->get_searches_by_page_type( $filters, $limit );
	}

	/**
	 * Delete all logged search records.
	 *
	 * @return bool
	 */
	public function clear_all_data(): bool {
		return $this->repository->clear_all();
	}
}
