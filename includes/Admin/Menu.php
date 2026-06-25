<?php
/**
 * Admin menu registration.
 *
 * @package SearchAnalyticsInsights
 */

namespace SearchAnalyticsInsights\Admin;

use SearchAnalyticsInsights\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin admin menu.
 */
final class Menu {
	private Dashboard $dashboard;

	/**
	 * Constructor.
	 *
	 * @param Dashboard $dashboard Dashboard renderer.
	 */
	public function __construct( Dashboard $dashboard ) {
		$this->dashboard = $dashboard;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_tools_actions' ) );
	}

	/**
	 * Register the top-level menu and submenu pages.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Parent top-level menu (mapped to dashboard page render)
		add_menu_page(
			esc_html__( 'Search Analytics', 'search-analytics-insights' ),
			esc_html__( 'Search Analytics', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-insights',
			array( $this->dashboard, 'render_dashboard_page' ),
			'dashicons-search',
			56
		);

		// Submenu: Dashboard
		add_submenu_page(
			'search-analytics-insights',
			esc_html__( 'Dashboard', 'search-analytics-insights' ),
			esc_html__( 'Dashboard', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-insights',
			''
		);

		// Submenu: Analytics & Insights
		add_submenu_page(
			'search-analytics-insights',
			esc_html__( 'Analytics & Insights', 'search-analytics-insights' ),
			esc_html__( 'Analytics & Insights', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-analytics',
			array( $this->dashboard, 'render_analytics_page' )
		);

		// Submenu: Search Settings
		add_submenu_page(
			'search-analytics-insights',
			esc_html__( 'Search Settings', 'search-analytics-insights' ),
			esc_html__( 'Search Settings', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-settings',
			array( $this->dashboard, 'render_settings_page' )
		);

		// Submenu: Tools
		add_submenu_page(
			'search-analytics-insights',
			esc_html__( 'Tools', 'search-analytics-insights' ),
			esc_html__( 'Tools', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-tools',
			array( $this->dashboard, 'render_tools_page' )
		);

		// Submenu: Help & Documentation
		add_submenu_page(
			'search-analytics-insights',
			esc_html__( 'Help & Documentation', 'search-analytics-insights' ),
			esc_html__( 'Help & Docs', 'search-analytics-insights' ),
			Constants::CAPABILITY,
			'search-analytics-help',
			array( $this->dashboard, 'render_help_page' )
		);
	}

	/**
	 * Intercept and handle tools action submissions on admin_init.
	 *
	 * @return void
	 */
	public function handle_tools_actions(): void {
		if ( ! is_admin() || ! current_user_can( Constants::CAPABILITY ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( 'search-analytics-tools' !== $page ) {
			return;
		}

		$action = isset( $_POST['sai_action'] ) ? sanitize_key( $_POST['sai_action'] ) : '';
		if ( empty( $action ) ) {
			return;
		}

		// Verify Nonce
		$nonce = isset( $_POST['sai_tools_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sai_tools_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sai_tools_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'search-analytics-insights' ) );
		}

		if ( 'export' === $action ) {
			$this->export_to_csv();
		} elseif ( 'clear' === $action ) {
			$this->clear_analytics_data();
		} elseif ( 'reset' === $action ) {
			$this->reset_plugin_settings();
		}
	}

	/**
	 * Stream all search records to CSV download.
	 *
	 * @return void
	 */
	private function export_to_csv(): void {
		global $wpdb;

		// Set headers for download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=search-analytics-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( ! $output ) {
			wp_die( esc_html__( 'Failed to generate export file.', 'search-analytics-insights' ) );
		}

		// CSV Column headers
		fputcsv(
			$output,
			array(
				__( 'ID', 'search-analytics-insights' ),
				__( 'Search Term', 'search-analytics-insights' ),
				__( 'Searched At', 'search-analytics-insights' ),
				__( 'Source', 'search-analytics-insights' ),
				__( 'Matched Post Types', 'search-analytics-insights' ),
				__( 'Result Count', 'search-analytics-insights' ),
				__( 'User ID', 'search-analytics-insights' ),
				__( 'Session ID', 'search-analytics-insights' ),
			)
		);

		// Batch fetch search logs
		$table  = Constants::table_name();
		$limit  = 1000;
		$offset = 0;

		while ( true ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = "SELECT id, search_term, searched_at, source, matched_post_types, result_count, user_id, session_id FROM {$table} ORDER BY id ASC LIMIT %d OFFSET %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows  = $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ), ARRAY_A );

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				fputcsv(
					$output,
					array(
						$row['id'],
						$row['search_term'],
						$row['searched_at'],
						$row['source'],
						$row['matched_post_types'],
						$row['result_count'],
						$row['user_id'] ? $row['user_id'] : '',
						$row['session_id'],
					)
				);
			}

			$offset += $limit;
			$wpdb->flush();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Clear all search logs from the database table.
	 *
	 * @return void
	 */
	private function clear_analytics_data(): void {
		$repository = new \SearchAnalyticsInsights\Database\Repository\SearchRepository();
		$repository->clear_all();

		wp_safe_redirect( add_query_arg( 'sai_message', 'clear_success', admin_url( 'admin.php?page=search-analytics-tools' ) ) );
		exit;
	}

	/**
	 * Reset plugin settings back to default options.
	 *
	 * @return void
	 */
	private function reset_plugin_settings(): void {
		delete_option( Constants::OPTION_SETTINGS );

		wp_safe_redirect( add_query_arg( 'sai_message', 'reset_success', admin_url( 'admin.php?page=search-analytics-tools' ) ) );
		exit;
	}
}
