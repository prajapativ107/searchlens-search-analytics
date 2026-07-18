<?php
/**
 * Search repository.
 *
 * @package VPLens
 */

namespace VPLens\Database\Repository;

use VPLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Handles all persistence and read queries for search events.
 */
final class SearchRepository {
	/**
	 * Save a search event.
	 *
	 * @param array<string, mixed> $event Search event data.
	 *
	 * @return int|false
	 */
	public function insert( array $event ) {
		global $wpdb;

		$data = array(
			'search_term'        => isset( $event['search_term'] ) ? sanitize_text_field( (string) $event['search_term'] ) : '',
			'search_term_hash'   => isset( $event['search_term_hash'] ) ? sanitize_text_field( (string) $event['search_term_hash'] ) : '',
			'source'             => isset( $event['source'] ) ? sanitize_text_field( (string) $event['source'] ) : Constants::SOURCE_WORDPRESS_SEARCH,
			'matched_post_types' => isset( $event['matched_post_types'] ) ? sanitize_text_field( (string) $event['matched_post_types'] ) : '',
			'searched_at'        => isset( $event['searched_at'] ) ? gmdate( 'Y-m-d H:i:s', strtotime( (string) $event['searched_at'] ) ) : gmdate( 'Y-m-d H:i:s' ),
			'result_count'       => isset( $event['result_count'] ) ? absint( $event['result_count'] ) : 0,
			'user_id'            => isset( $event['user_id'] ) ? absint( $event['user_id'] ) : null,
			'session_id'         => isset( $event['session_id'] ) ? sanitize_text_field( (string) $event['session_id'] ) : null,
			'blog_id'            => isset( $event['blog_id'] ) ? absint( $event['blog_id'] ) : get_current_blog_id(),
			'page_title'         => isset( $event['page_title'] ) ? sanitize_text_field( (string) $event['page_title'] ) : '',
			'page_url'           => isset( $event['page_url'] ) ? esc_url_raw( (string) $event['page_url'] ) : '',
			'referrer'           => isset( $event['referrer'] ) ? esc_url_raw( (string) $event['referrer'] ) : '',
			'page_type'          => isset( $event['page_type'] ) ? sanitize_text_field( (string) $event['page_type'] ) : '',
		);

		if ( '' === $data['search_term_hash'] ) {
			$data['search_term_hash'] = hash( 'sha256', mb_strtolower( $data['search_term'] ) );
		}

		$format = array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert( Constants::table_name(), $data, $format );

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get the aggregate summary for the current filters.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return array<string, int>
	 */
	public function get_summary( array $filters = array() ): array {
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$table      = Constants::table_name();

		$sql    = 'SELECT COUNT(*) AS total_searches, COUNT(DISTINCT v.search_term_hash) AS unique_searches, SUM(CASE WHEN v.result_count = 0 THEN 1 ELSE 0 END) AS no_result_searches FROM %i AS v';
		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return array(
			'total_searches'     => isset( $row['total_searches'] ) ? (int) $row['total_searches'] : 0,
			'unique_searches'    => isset( $row['unique_searches'] ) ? (int) $row['unique_searches'] : 0,
			'no_result_searches' => isset( $row['no_result_searches'] ) ? (int) $row['no_result_searches'] : 0,
		);
	}

	/**
	 * Get aggregated search activity grouped by search term and user.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $page    Page number.
	 * @param int                  $per_page Items per page.
	 *
	 * @return array<string, mixed>
	 */
	public function get_aggregated_search_activity( array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		global $wpdb;

		$page        = max( 1, absint( $page ) );
		$per_page    = max( 1, min( 100, absint( $per_page ) ) );
		$offset      = ( $page - 1 ) * $per_page;
		$where_data  = $this->build_where_conditions( $filters );
		$table       = Constants::table_name();
		$users_table = $wpdb->users;

		$query = 'SELECT grouped.search_term, grouped.search_count, grouped.last_searched, latest.result_count, latest.page_title, latest.page_url, latest.referrer, latest.page_type, u.display_name, u.user_login FROM ( SELECT v.search_term, v.user_id, COUNT(*) AS search_count, MAX(v.searched_at) AS last_searched, MAX(v.id) AS latest_id FROM %i AS v';

		$params = array( $table );
		if ( ! empty( $where_data['conditions'] ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}
		$query .= ' GROUP BY v.search_term, v.user_id ) AS grouped INNER JOIN %i AS latest ON latest.id = grouped.latest_id LEFT JOIN %i AS u ON latest.user_id = u.ID ORDER BY grouped.last_searched DESC, grouped.search_term ASC, u.display_name ASC, u.user_login ASC LIMIT %d OFFSET %d';

		$params[] = $table;
		$params[] = $users_table;
		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $query, ...$params ), ARRAY_A );

		$count_query  = 'SELECT COUNT(*) FROM ( SELECT v.search_term, v.user_id FROM %i AS v';
		$count_params = array( $table );
		if ( ! empty( $where_data['conditions'] ) ) {
			$count_query .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$count_params = array_merge( $count_params, $where_data['params'] );
		}
		$count_query .= ' GROUP BY v.search_term, v.user_id ) AS grouped_count';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_query, ...$count_params ) );

		return array(
			'items' => is_array( $results ) ? $results : array(),
			'total' => $total,
		);
	}

	/**
	 * Get the most recent raw search activity.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent_search_activity( array $filters = array(), int $limit = 20 ): array {
		global $wpdb;

		$limit       = max( 1, min( 100, absint( $limit ) ) );
		$where_data  = $this->build_where_conditions( $filters );
		$table       = Constants::table_name();
		$users_table = $wpdb->users;

		$sql = 'SELECT v.id, v.search_term, v.searched_at, v.source, v.matched_post_types, v.result_count, v.session_id, v.page_title, v.page_url, v.referrer, v.page_type, u.display_name, u.user_login FROM %i AS v LEFT JOIN %i AS u ON v.user_id = u.ID';

		$params = array( $table, $users_table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$allowed_orderby = array(
			'searched_at'  => 'v.searched_at',
			'search_term'  => 'v.search_term',
			'result_count' => 'v.result_count',
			'page_title'   => 'v.page_title',
			'page_type'    => 'v.page_type',
			'id'           => 'v.id',
		);

		$primary_orderby     = isset( $allowed_orderby['searched_at'] ) ? $allowed_orderby['searched_at'] : 'v.searched_at';
		$primary_direction   = 'DESC';
		$secondary_orderby   = isset( $allowed_orderby['id'] ) ? $allowed_orderby['id'] : 'v.id';
		$secondary_direction = 'DESC';

		$sql .= " ORDER BY {$primary_orderby} {$primary_direction}, {$secondary_orderby} {$secondary_direction}";
		$sql .= ' LIMIT %d';

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Get the search counts grouped by day.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_daily_counts( array $filters = array() ): array {
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$table      = Constants::table_name();

		$sql = 'SELECT DATE(v.searched_at) AS search_date, COUNT(*) AS search_count FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$sql .= ' GROUP BY DATE(v.searched_at) ORDER BY search_date ASC';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get the top search terms.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @param int                  $limit   Result limit.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_top_terms( array $filters = array(), int $limit = 10 ): array {
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$limit      = max( 1, absint( $limit ) );
		$table      = Constants::table_name();

		$sql = 'SELECT v.search_term, COUNT(*) AS search_count FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$sql .= ' GROUP BY v.search_term ORDER BY search_count DESC, v.search_term ASC LIMIT %d';

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $results ) ? $results : array();
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
	public function get_searches( array $filters = array(), int $page = 1, int $per_page = 20 ): array {
		global $wpdb;

		$page        = max( 1, absint( $page ) );
		$per_page    = max( 1, min( 100, absint( $per_page ) ) );
		$offset      = ( $page - 1 ) * $per_page;
		$where_data  = $this->build_where_conditions( $filters );
		$table       = Constants::table_name();
		$users_table = $wpdb->users;

		$sql = 'SELECT v.id, v.search_term, v.searched_at, v.source, v.matched_post_types, v.result_count, v.blog_id, v.page_title, v.page_url, v.referrer, v.page_type, u.display_name, u.user_login FROM %i AS v LEFT JOIN %i AS u ON v.user_id = u.ID';

		$params = array( $table, $users_table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$allowed_orderby = array(
			'searched_at'  => 'v.searched_at',
			'search_term'  => 'v.search_term',
			'result_count' => 'v.result_count',
			'page_title'   => 'v.page_title',
			'page_type'    => 'v.page_type',
			'id'           => 'v.id',
		);

		$primary_orderby     = isset( $allowed_orderby['searched_at'] ) ? $allowed_orderby['searched_at'] : 'v.searched_at';
		$primary_direction   = 'DESC';
		$secondary_orderby   = isset( $allowed_orderby['id'] ) ? $allowed_orderby['id'] : 'v.id';
		$secondary_direction = 'DESC';

		$sql .= " ORDER BY {$primary_orderby} {$primary_direction}, {$secondary_orderby} {$secondary_direction}";
		$sql .= ' LIMIT %d OFFSET %d';

		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return array(
			'items' => is_array( $results ) ? $results : array(),
			'total' => $this->count( $filters ),
		);
	}

	/**
	 * Count matching search rows.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return int
	 */
	public function count( array $filters = array() ): int {
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$table      = Constants::table_name();

		$sql = 'SELECT COUNT(*) FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Search published posts and pages.
	 *
	 * @param string             $search_term Search term.
	 * @param array<int, string> $post_types Post type slugs.
	 * @param int                $limit      Result limit.
	 * @param bool               $include_featured_images Whether to resolve thumbnail URLs.
	 *
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public function search_content( string $search_term, array $post_types = array(), int $limit = 10, bool $include_featured_images = true ): array {
		$post_types = array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', $post_types ),
					static function ( string $post_type ): bool {
						return '' !== $post_type;
					}
				)
			)
		);

		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		}

		$query = new \WP_Query(
			array(
				's'                   => $search_term,
				'post_type'           => $post_types,
				'post_status'         => 'publish',
				'posts_per_page'      => max( 1, min( 20, absint( $limit ) ) ),
				'no_found_rows'       => false,
				'ignore_sticky_posts' => true,
			)
		);

		$results = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$featured_image = '';

			if ( $include_featured_images ) {
				$thumbnail_url  = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
				$featured_image = false === $thumbnail_url ? '' : (string) $thumbnail_url;
			}

			$results[] = array(
				'id'             => (int) $post->ID,
				'title'          => get_the_title( $post ),
				'url'            => get_permalink( $post ),
				'featured_image' => $featured_image,
				'post_type'      => $post->post_type,
			);
		}

		wp_reset_postdata();

		return array(
			'items' => $results,
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Delete all logged search records.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_all(): bool {
		global $wpdb;
		$table = Constants::table_name();
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/', $table ) ) {
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table ) );
		if ( false === $result ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $table ) );
		}
		return false !== $result;
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
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$limit      = max( 1, absint( $limit ) );
		$table      = Constants::table_name();

		$sql = 'SELECT v.page_title, v.page_url, COUNT(*) AS search_count FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$sql .= ' GROUP BY v.page_title, v.page_url ORDER BY search_count DESC, v.page_title ASC LIMIT %d';

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $results ) ? $results : array();
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
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$limit      = max( 1, absint( $limit ) );
		$table      = Constants::table_name();

		$sql = 'SELECT v.page_url, COUNT(*) AS search_count FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$sql .= ' GROUP BY v.page_url ORDER BY search_count DESC, v.page_url ASC LIMIT %d';

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $results ) ? $results : array();
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
		global $wpdb;

		$where_data = $this->build_where_conditions( $filters );
		$limit      = max( 1, absint( $limit ) );
		$table      = Constants::table_name();

		$sql = 'SELECT v.page_type, COUNT(*) AS search_count FROM %i AS v';

		$params = array( $table );

		if ( ! empty( $where_data['conditions'] ) ) {
			$sql   .= ' WHERE ' . implode( ' AND ', $where_data['conditions'] );
			$params = array_merge( $params, $where_data['params'] );
		}

		$sql .= ' GROUP BY v.page_type ORDER BY search_count DESC, v.page_type ASC LIMIT %d';

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Build the SQL where conditions and parameters.
	 *
	 * @param array<string, mixed> $filters Query filters.
	 *
	 * @return array{conditions: array<int, string>, params: array<int, mixed>}
	 */
	private function build_where_conditions( array $filters ): array {
		$conditions = array();
		$params     = array();

		if ( ! empty( $filters['search_term'] ) ) {
			$conditions[] = 'v.search_term LIKE %s';
			$params[]     = '%' . $this->normalize_term( (string) $filters['search_term'] ) . '%';
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$conditions[] = 'v.searched_at >= %s';
			$params[]     = $this->normalize_date_start( (string) $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$conditions[] = 'v.searched_at <= %s';
			$params[]     = $this->normalize_date_end( (string) $filters['date_to'] );
		}

		if ( ! empty( $filters['username'] ) ) {
			$username = sanitize_text_field( $filters['username'] );
			if ( 'guest' === strtolower( $username ) ) {
				$conditions[] = '(v.user_id IS NULL OR v.user_id = 0)';
			} else {
				$user_query = new \WP_User_Query(
					array(
						'search'         => '*' . $username . '*',
						'search_columns' => array( 'user_login', 'display_name' ),
						'fields'         => 'ID',
					)
				);
				$user_ids   = $user_query->get_results();
				if ( ! empty( $user_ids ) ) {
					$user_ids_placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
					$conditions[]          = "v.user_id IN ({$user_ids_placeholders})";
					foreach ( $user_ids as $uid ) {
						$params[] = absint( $uid );
					}
				} else {
					$conditions[] = '1 = 0';
				}
			}
		}

		if ( ! empty( $filters['session_id'] ) ) {
			$conditions[] = 'v.session_id = %s';
			$params[]     = sanitize_text_field( (string) $filters['session_id'] );
		}

		if ( ! empty( $filters['no_results'] ) ) {
			$conditions[] = 'v.result_count = 0';
		}

		if ( ! empty( $filters['page_type'] ) ) {
			$conditions[] = 'v.page_type = %s';
			$params[]     = sanitize_text_field( (string) $filters['page_type'] );
		}

		if ( ! empty( $filters['page_title'] ) ) {
			$conditions[] = 'v.page_title LIKE %s';
			$params[]     = '%' . sanitize_text_field( (string) $filters['page_title'] ) . '%';
		}

		if ( ! empty( $filters['page_url'] ) ) {
			$conditions[] = 'v.page_url LIKE %s';
			$params[]     = '%' . sanitize_text_field( (string) $filters['page_url'] ) . '%';
		}

		return array(
			'conditions' => $conditions,
			'params'     => $params,
		);
	}

	/**
	 * Normalize search terms for filtering.
	 *
	 * @param string $term Raw term.
	 *
	 * @return string
	 */
	private function normalize_term( string $term ): string {
		return sanitize_text_field( wp_unslash( $term ) );
	}

	/**
	 * Normalize a date value to the start of day.
	 *
	 * @param string $date Date string.
	 *
	 * @return string
	 */
	private function normalize_date_start( string $date ): string {
		return gmdate( 'Y-m-d 00:00:00', strtotime( $date ) );
	}

	/**
	 * Normalize a date value to the end of day.
	 *
	 * @param string $date Date string.
	 *
	 * @return string
	 */
	private function normalize_date_end( string $date ): string {
		return gmdate( 'Y-m-d 23:59:59', strtotime( $date ) );
	}

	/**
	 * Delete search records older than a specific number of days.
	 *
	 * @param int $days Number of days of retention.
	 *
	 * @return int|false Number of deleted rows or false on failure.
	 */
	public function delete_older_than( int $days ) {
		global $wpdb;

		if ( $days <= 0 ) {
			return false;
		}

		$table = Constants::table_name();
		$date  = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE searched_at < %s', $table, $date ) );
	}
}
