<?php
/**
 * Admin menu registration.
 *
 * @package SearchLens
 */

namespace SearchLens\Admin;

use SearchLens\Core\Constants;

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
			esc_html__( 'SearchLens', 'searchlens-search-analytics' ),
			esc_html__( 'SearchLens', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens',
			array( $this->dashboard, 'render_dashboard_page' ),
			'dashicons-search',
			56
		);

		// Submenu: Dashboard
		add_submenu_page(
			'searchlens',
			esc_html__( 'Dashboard', 'searchlens-search-analytics' ),
			esc_html__( 'Dashboard', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens',
			''
		);

		// Submenu: Analytics & Insights
		add_submenu_page(
			'searchlens',
			esc_html__( 'Analytics & Insights', 'searchlens-search-analytics' ),
			esc_html__( 'Analytics & Insights', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens-analytics',
			array( $this->dashboard, 'render_analytics_page' )
		);

		// Submenu: Search Settings
		add_submenu_page(
			'searchlens',
			esc_html__( 'Search Settings', 'searchlens-search-analytics' ),
			esc_html__( 'Search Settings', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens-settings',
			array( $this->dashboard, 'render_settings_page' )
		);

		// Submenu: Tools
		add_submenu_page(
			'searchlens',
			esc_html__( 'Tools', 'searchlens-search-analytics' ),
			esc_html__( 'Tools', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens-tools',
			array( $this->dashboard, 'render_tools_page' )
		);

		// Submenu: Help & Documentation
		add_submenu_page(
			'searchlens',
			esc_html__( 'Help & Documentation', 'searchlens-search-analytics' ),
			esc_html__( 'Help & Docs', 'searchlens-search-analytics' ),
			Constants::CAPABILITY,
			'searchlens-help',
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
		if ( 'searchlens-tools' !== $page ) {
			return;
		}

		$action = isset( $_POST['searchlens_action'] ) ? sanitize_key( $_POST['searchlens_action'] ) : '';
		if ( empty( $action ) ) {
			return;
		}

		// Verify Nonce
		$nonce = isset( $_POST['searchlens_tools_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['searchlens_tools_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'searchlens_tools_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'searchlens-search-analytics' ) );
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

		// Set headers for download.
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=searchlens-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( ! $output ) {
			wp_die( esc_html__( 'Failed to generate export file.', 'searchlens-search-analytics' ) );
		}

		// Write the UTF-8 BOM to ensure spreadsheet applications display special characters correctly.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $output, "\xEF\xBB\xBF" );

		// CSV Column headers.
		$headers = array(
			__( 'ID', 'searchlens-search-analytics' ),
			__( 'Search Term', 'searchlens-search-analytics' ),
			__( 'Searched At', 'searchlens-search-analytics' ),
			__( 'Source', 'searchlens-search-analytics' ),
			__( 'Matched Post Types', 'searchlens-search-analytics' ),
			__( 'Result Count', 'searchlens-search-analytics' ),
			__( 'Username', 'searchlens-search-analytics' ),
			__( 'Page Title', 'searchlens-search-analytics' ),
			__( 'Page URL', 'searchlens-search-analytics' ),
			__( 'Referrer', 'searchlens-search-analytics' ),
			__( 'Page Type', 'searchlens-search-analytics' ),
		);

		fputcsv( $output, array_map( array( $this, 'escape_csv_value' ), $headers ) );

		// Batch fetch search logs.
		$table  = Constants::table_name();
		$limit  = 1000;
		$offset = 0;

		while ( true ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = "SELECT {$table}.id, {$table}.search_term, {$table}.searched_at, {$table}.source, {$table}.matched_post_types, {$table}.result_count, {$table}.page_title, {$table}.page_url, {$table}.referrer, {$table}.page_type, u.display_name, u.user_login FROM {$table} LEFT JOIN {$wpdb->users} AS u ON {$table}.user_id = u.ID ORDER BY {$table}.id ASC LIMIT %d OFFSET %d";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $query, $limit, $offset ), ARRAY_A );

			if ( empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				$page_title = \SearchLens\Helpers\PageHelper::resolve_page_title( $row['page_url'], $row['page_title'] );

				$csv_row = array(
					$row['id'],
					$row['search_term'],
					$row['searched_at'],
					$row['source'],
					$row['matched_post_types'],
					$row['result_count'],
					$this->get_user_label( $row ),
					$page_title,
					$row['page_url'],
					$row['referrer'],
					$row['page_type'],
				);

				fputcsv( $output, array_map( array( $this, 'escape_csv_value' ), $csv_row ) );
			}

			$offset += $limit;
			$wpdb->flush();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Escape a CSV value to prevent CSV Injection / Formula Injection.
	 *
	 * @param mixed $value The value to escape.
	 *
	 * @return string The escaped value.
	 */
	private function escape_csv_value( $value ): string {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}

		$dangerous_chars = array( '=', '+', '-', '@', "\t", "\r", "\n" );
		$first_char      = substr( $value, 0, 1 );

		if ( in_array( $first_char, $dangerous_chars, true ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Get the display label for the user column.
	 *
	 * @param array<string, mixed> $record Search record.
	 *
	 * @return string
	 */
	private function get_user_label( array $record ): string {
		$display_name = ! empty( $record['display_name'] ) ? (string) $record['display_name'] : '';
		if ( '' !== $display_name ) {
			return $display_name;
		}

		return ! empty( $record['user_login'] ) ? (string) $record['user_login'] : __( 'Guest', 'searchlens-search-analytics' );
	}

	/**
	 * Clear all search logs from the database table.
	 *
	 * @return void
	 */
	private function clear_analytics_data(): void {
		$repository = new \SearchLens\Database\Repository\SearchRepository();
		$repository->clear_all();

		wp_safe_redirect( add_query_arg( 'searchlens_message', 'clear_success', admin_url( 'admin.php?page=searchlens-tools' ) ) );
		exit;
	}

	/**
	 * Reset plugin settings back to default options.
	 *
	 * @return void
	 */
	private function reset_plugin_settings(): void {
		delete_option( Constants::OPTION_SETTINGS );

		wp_safe_redirect( add_query_arg( 'searchlens_message', 'reset_success', admin_url( 'admin.php?page=searchlens-tools' ) ) );
		exit;
	}
}
