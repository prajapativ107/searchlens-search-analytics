<?php
/**
 * Admin dashboard renderer.
 *
 * @package SearchAnalyticsInsights
 */

namespace SearchAnalyticsInsights\Admin;

use SearchAnalyticsInsights\Analytics\Service\AnalyticsService;
use SearchAnalyticsInsights\Core\Constants;
defined( 'ABSPATH' ) || exit;

/**
 * Renders the analytics dashboard in wp-admin.
 */
final class Dashboard {
	private AnalyticsService $analytics_service;

	/**
	 * Constructor.
	 *
	 * @param AnalyticsService $analytics_service Analytics service instance.
	 */
	public function __construct( AnalyticsService $analytics_service ) {
		$this->analytics_service = $analytics_service;
	}

	/**
	 * Render the legacy top-level page callback (delegates to dashboard).
	 *
	 * @return void
	 */
	public function render(): void {
		$this->render_dashboard_page();
	}

	/**
	 * Render the main dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page(): void {
		$filters         = array();
		$summary         = $this->analytics_service->get_dashboard_summary( $filters );
		$top_terms       = $this->analytics_service->get_top_search_terms( $filters, 5 );
		$recent_activity = $this->analytics_service->get_recent_search_activity( $filters, 5 );

		$this->render_tabbed_page(
			'dashboard',
			function () use ( $summary, $top_terms, $recent_activity ) {
				?>
			<div class="sai-dashboard-grid">
				<div class="sai-card-column">
				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Overview Summary', 'search-analytics-insights' ); ?></h3>
					<div class="search-analytics-insights-summary">
						<?php $this->render_summary_card( __( 'Total Searches', 'search-analytics-insights' ), (int) $summary['total_searches'] ); ?>
						<?php $this->render_summary_card( __( 'Unique Searches', 'search-analytics-insights' ), (int) $summary['unique_searches'] ); ?>
						<?php $this->render_summary_card( __( 'No Results', 'search-analytics-insights' ), (int) $summary['no_result_searches'] ); ?>
					</div>
				</div>
				
				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Quick Actions', 'search-analytics-insights' ); ?></h3>
					<div class="sai-quick-actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-analytics' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Analytics', 'search-analytics-insights' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-settings' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Manage Settings', 'search-analytics-insights' ); ?></a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-help' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Read Help Docs', 'search-analytics-insights' ); ?></a>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-tools' ) ); ?>">
							<?php wp_nonce_field( 'sai_tools_action', 'sai_tools_nonce' ); ?>
							<input type="hidden" name="sai_action" value="export" />
							<button type="submit" class="button button-secondary"><?php esc_html_e( 'Export to CSV', 'search-analytics-insights' ); ?></button>
						</form>
					</div>
				</div>
			</div>

				<div class="sai-card-column">
					<div class="search-analytics-insights-panel sai-panel">
						<h3><?php esc_html_e( 'Top Searches (Overall)', 'search-analytics-insights' ); ?></h3>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Term', 'search-analytics-insights' ); ?></th>
									<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $top_terms ) ) : ?>
									<tr><td colspan="2"><?php esc_html_e( 'No search terms recorded yet.', 'search-analytics-insights' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $top_terms as $row ) : ?>
										<tr>
											<td><strong><?php echo esc_html( (string) $row['search_term'] ); ?></strong></td>
											<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>

					<div class="search-analytics-insights-panel sai-panel">
						<h3><?php esc_html_e( 'Recent Search Activity', 'search-analytics-insights' ); ?></h3>
						<table class="widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Term', 'search-analytics-insights' ); ?></th>
									<th><?php esc_html_e( 'Date', 'search-analytics-insights' ); ?></th>
									<th><?php esc_html_e( 'Results', 'search-analytics-insights' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $recent_activity ) ) : ?>
									<tr><td colspan="3"><?php esc_html_e( 'No search activity found.', 'search-analytics-insights' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $recent_activity as $record ) : ?>
										<tr>
											<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
											<td><span class="description"><?php echo esc_html( (string) $record['searched_at'] ); ?></span></td>
											<td><span class="badge <?php echo $record['result_count'] > 0 ? 'badge-success' : 'badge-danger'; ?>"><?php echo esc_html( (string) $record['result_count'] ); ?></span></td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
				<?php
			}
		);
	}

	/**
	 * Render the detailed analytics reports page.
	 *
	 * @return void
	 */
	public function render_analytics_page(): void {
		$filters                         = $this->get_filters();
		$summary                         = $this->analytics_service->get_dashboard_summary( $filters );
		$aggregated_activity             = $this->analytics_service->get_aggregated_search_activity(
			$filters,
			$filters['page'],
			$filters['per_page']
		);
		$recent_activity                 = $this->analytics_service->get_recent_search_activity( $filters, $filters['per_page'] );
		$top_terms                       = $this->analytics_service->get_top_search_terms( $filters, 10 );
		$no_result_filters               = $filters;
		$no_result_filters['no_results'] = 1;
		$no_result_terms                 = $this->analytics_service->get_top_search_terms( $no_result_filters, $filters['per_page'] );
		$daily_counts                    = $this->analytics_service->get_searches_per_day( $filters );

		$total_pages = max( 1, (int) ceil( $aggregated_activity['total'] / $filters['per_page'] ) );
		$page        = min( $filters['page'], $total_pages );

		$this->render_tabbed_page(
			'analytics',
			function () use ( $filters, $summary, $aggregated_activity, $recent_activity, $top_terms, $no_result_terms, $daily_counts, $total_pages, $page ) {
				?>
			<form method="get" class="search-analytics-insights-filters">
				<input type="hidden" name="page" value="search-analytics-analytics" />
				<div class="search-analytics-insights-filter-row">
					<label for="search-analytics-insights-date-from"><?php esc_html_e( 'From', 'search-analytics-insights' ); ?></label>
					<input id="search-analytics-insights-date-from" type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />

					<label for="search-analytics-insights-date-to"><?php esc_html_e( 'To', 'search-analytics-insights' ); ?></label>
					<input id="search-analytics-insights-date-to" type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />

					<label for="search-analytics-insights-term"><?php esc_html_e( 'Search term', 'search-analytics-insights' ); ?></label>
					<input id="search-analytics-insights-term" type="search" name="search_term" value="<?php echo esc_attr( $filters['search_term'] ); ?>" />

					<label for="search-analytics-insights-no-results">
						<input id="search-analytics-insights-no-results" type="checkbox" name="no_results" value="1" <?php checked( 1, (int) $filters['no_results'] ); ?> />
						<?php esc_html_e( 'No results only', 'search-analytics-insights' ); ?>
					</label>

					<label for="search-analytics-insights-per-page"><?php esc_html_e( 'Per page', 'search-analytics-insights' ); ?></label>
					<select id="search-analytics-insights-per-page" name="per_page">
						<?php foreach ( array( 10, 20, 50, 100 ) as $option ) : ?>
							<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( $filters['per_page'], $option ); ?>><?php echo esc_html( (string) $option ); ?></option>
						<?php endforeach; ?>
					</select>

					<?php submit_button( __( 'Filter', 'search-analytics-insights' ), 'primary', '', false ); ?>
				</div>
			</form>

			<div class="search-analytics-insights-summary">
				<?php $this->render_summary_card( __( 'Total Searches', 'search-analytics-insights' ), (int) $summary['total_searches'] ); ?>
				<?php $this->render_summary_card( __( 'Unique Searches', 'search-analytics-insights' ), (int) $summary['unique_searches'] ); ?>
				<?php $this->render_summary_card( __( 'No Result Searches', 'search-analytics-insights' ), (int) $summary['no_result_searches'] ); ?>
			</div>

			<div class="search-analytics-insights-grid">
				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'Searches per day', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Date', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $daily_counts ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No searches found for this range.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $daily_counts as $row ) : ?>
									<tr>
										<td><?php echo esc_html( (string) $row['search_date'] ); ?></td>
										<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'Top Search Terms', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Search Term', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $top_terms ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No top search terms yet.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $top_terms as $row ) : ?>
									<tr>
										<td><?php echo esc_html( (string) $row['search_term'] ); ?></td>
										<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'No Result Searches', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Search Term', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $no_result_terms ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No searches with zero results have been recorded yet.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $no_result_terms as $row ) : ?>
									<tr>
										<td><?php echo esc_html( (string) $row['search_term'] ); ?></td>
										<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="search-analytics-insights-panel search-analytics-insights-table-panel">
				<h2><?php esc_html_e( 'Aggregated Search Activity', 'search-analytics-insights' ); ?></h2>
				<p><?php esc_html_e( 'Repeated searches are grouped by search term and user.', 'search-analytics-insights' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Search Term', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Search Count', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Last Searched', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Results', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'User', 'search-analytics-insights' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $aggregated_activity['items'] ) ) : ?>
							<tr><td colspan="5"><?php esc_html_e( 'No search records found.', 'search-analytics-insights' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $aggregated_activity['items'] as $record ) : ?>
								<tr>
									<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
									<td><?php echo esc_html( (string) $record['search_count'] ); ?></td>
									<td><?php echo esc_html( (string) $record['last_searched'] ); ?></td>
									<td><?php echo esc_html( (string) $record['result_count'] ); ?></td>
									<td><?php echo esc_html( $this->get_user_label( $record ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<?php
							$pagination_base = add_query_arg(
								array_filter(
									array(
										'page'        => 'search-analytics-analytics',
										'date_from'   => $filters['date_from'],
										'date_to'     => $filters['date_to'],
										'search_term' => $filters['search_term'],
										'no_results'  => $filters['no_results'],
										'per_page'    => $filters['per_page'],
										'paged'       => '%#%',
									),
									static function ( $value ): bool {
										return '' !== $value && null !== $value;
									}
								),
								admin_url( 'admin.php' )
							);
							?>
							<?php
							echo wp_kses_post(
								paginate_links(
									array(
										'base'      => $pagination_base,
										'format'    => '',
										'current'   => $page,
										'total'     => $total_pages,
										'prev_text' => '&laquo;',
										'next_text' => '&raquo;',
									)
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div class="search-analytics-insights-panel search-analytics-insights-table-panel">
				<h2><?php esc_html_e( 'Recent Search Activity', 'search-analytics-insights' ); ?></h2>
				<p><?php esc_html_e( 'The latest 20 raw search records.', 'search-analytics-insights' ); ?></p>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Search Term', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Date', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Results', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'User', 'search-analytics-insights' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_activity ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No recent search activity found.', 'search-analytics-insights' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $recent_activity as $record ) : ?>
								<tr>
									<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
									<td><?php echo esc_html( (string) $record['searched_at'] ); ?></td>
									<td><?php echo esc_html( (string) $record['result_count'] ); ?></td>
									<td><?php echo esc_html( $this->get_user_label( $record ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
				<?php
			}
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$this->render_tabbed_page(
			'settings',
			function () {
				?>
			<div class="search-analytics-insights-panel sai-panel">
				<h3><?php esc_html_e( 'Search Settings', 'search-analytics-insights' ); ?></h3>
				<p class="search-analytics-insights-settings-description"><?php esc_html_e( 'Configure the search form, live results, sources, and analytics in one place.', 'search-analytics-insights' ); ?></p>
				<form method="post" action="options.php">
					<?php settings_fields( 'search_analytics_insights_settings' ); ?>
					<?php do_settings_sections( 'search-analytics-insights' ); ?>
					<?php submit_button( __( 'Save Settings', 'search-analytics-insights' ) ); ?>
				</form>
			</div>
				<?php
			}
		);
	}

	/**
	 * Render the tools and data utility page.
	 *
	 * @return void
	 */
	public function render_tools_page(): void {
		$this->render_tabbed_page(
			'tools',
			function () {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$message = isset( $_GET['sai_message'] ) ? sanitize_key( $_GET['sai_message'] ) : '';
				?>
			<div class="sai-tools-wrapper">
				<?php if ( 'clear_success' === $message ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All search analytics data has been successfully cleared.', 'search-analytics-insights' ); ?></p></div>
				<?php elseif ( 'reset_success' === $message ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'All plugin settings have been successfully reset to defaults.', 'search-analytics-insights' ); ?></p></div>
				<?php endif; ?>

				<!-- Export Card -->
				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Export Analytics Data', 'search-analytics-insights' ); ?></h3>
					<p><?php esc_html_e( 'Download the complete search logs database in CSV format for offline reporting or backups.', 'search-analytics-insights' ); ?></p>
					<form method="post" action="">
						<?php wp_nonce_field( 'sai_tools_action', 'sai_tools_nonce' ); ?>
						<input type="hidden" name="sai_action" value="export" />
						<?php submit_button( __( 'Download CSV Export', 'search-analytics-insights' ), 'primary', 'submit_export', false ); ?>
					</form>
				</div>

				<!-- Clear Database Card -->
				<div class="search-analytics-insights-panel sai-panel card-destructive">
					<h3 class="destructive"><?php esc_html_e( 'Clear Search Logs', 'search-analytics-insights' ); ?></h3>
					<p><?php esc_html_e( 'Warning: This action is permanent and will completely empty the search tracking database table. This cannot be undone.', 'search-analytics-insights' ); ?></p>
					<form method="post" action="" onsubmit="return confirm('<?php echo esc_attr__( 'Are you absolutely sure you want to permanently delete all search analytics logs?', 'search-analytics-insights' ); ?>');">
						<?php wp_nonce_field( 'sai_tools_action', 'sai_tools_nonce' ); ?>
						<input type="hidden" name="sai_action" value="clear" />
						<?php submit_button( __( 'Permanently Delete Logs', 'search-analytics-insights' ), 'destructive', 'submit_clear', false ); ?>
					</form>
				</div>

				<!-- Reset Settings Card -->
				<div class="search-analytics-insights-panel sai-panel card-destructive">
					<h3 class="destructive"><?php esc_html_e( 'Reset Plugin Settings', 'search-analytics-insights' ); ?></h3>
					<p><?php esc_html_e( 'Restore all settings on this plugin back to their factory defaults. This does not delete search logs.', 'search-analytics-insights' ); ?></p>
					<form method="post" action="" onsubmit="return confirm('<?php echo esc_attr__( 'Are you sure you want to reset all plugin settings back to defaults?', 'search-analytics-insights' ); ?>');">
						<?php wp_nonce_field( 'sai_tools_action', 'sai_tools_nonce' ); ?>
						<input type="hidden" name="sai_action" value="reset" />
						<?php submit_button( __( 'Reset Settings to Defaults', 'search-analytics-insights' ), 'destructive', 'submit_reset', false ); ?>
					</form>
				</div>
			</div>
				<?php
			}
		);
	}

	/**
	 * Render the help, shortcodes, and documentation page.
	 *
	 * @return void
	 */
	public function render_help_page(): void {
		$this->render_tabbed_page(
			'help',
			function () {
				?>
			<div class="sai-help-wrapper">
				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Available Shortcodes', 'search-analytics-insights' ); ?></h3>
					<p><?php esc_html_e( 'Use the following shortcodes to add search form and search insights blocks to your pages or posts.', 'search-analytics-insights' ); ?></p>
					<table class="widefat striped search-analytics-insights-shortcodes-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Shortcode', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Description', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Example Usage', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Action', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $this->get_shortcode_docs() as $shortcode ) : ?>
								<tr>
									<td><code><?php echo esc_html( $shortcode['tag'] ); ?></code></td>
									<td><?php echo esc_html( $shortcode['description'] ); ?></td>
									<td><code><?php echo esc_html( $shortcode['example'] ); ?></code></td>
									<td>
										<button
											type="button"
											class="button button-small search-analytics-insights-copy-shortcode"
											data-copy-shortcode="<?php echo esc_attr( $shortcode['example'] ); ?>"
										>
											<?php esc_html_e( 'Copy', 'search-analytics-insights' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Widget & Block Usage', 'search-analytics-insights' ); ?></h3>
					<div class="sai-usage-grid">
						<div>
							<h4><?php esc_html_e( 'Gutenberg Block', 'search-analytics-insights' ); ?></h4>
							<p><?php esc_html_e( 'Open any post or page editor. Search for the "Search Analytics Search" block in the block library and insert it. You can adjust Open Mode (Dropdown/Modal/Slide Down) and Show Label under the block sidebar panel.', 'search-analytics-insights' ); ?></p>
						</div>
						<div>
							<h4><?php esc_html_e( 'WordPress Widget', 'search-analytics-insights' ); ?></h4>
							<p><?php esc_html_e( 'Navigate to Appearance > Widgets. Add the "Search Analytics Search Widget" into any sidebar or widget area. You can customize the Widget Title, icon size, toggle Open Mode, and choose whether to display text labels.', 'search-analytics-insights' ); ?></p>
						</div>
					</div>
				</div>

				<div class="search-analytics-insights-panel sai-panel">
					<h3><?php esc_html_e( 'Frequently Asked Questions', 'search-analytics-insights' ); ?></h3>
					<div class="sai-faq-list">
						<div>
							<h4><?php esc_html_e( 'Does this track personal user data?', 'search-analytics-insights' ); ?></h4>
							<p><?php esc_html_e( 'The plugin is designed to be privacy-friendly. It does not collect raw IP addresses, relying on optional logged-in user IDs and anonymous session IDs to compile search counts.', 'search-analytics-insights' ); ?></p>
						</div>
						<div>
							<h4><?php esc_html_e( 'How are search sources managed?', 'search-analytics-insights' ); ?></h4>
							<p><?php esc_html_e( 'By default, search indexes pages and posts. You can select specific public post types (e.g. products, events) under the Search Settings tab.', 'search-analytics-insights' ); ?></p>
						</div>
					</div>
				</div>
			</div>
				<?php
			}
		);
	}

	/**
	 * Common tab layout wrapper for premium multi-page UI.
	 *
	 * @param string   $active_tab        Slug of the active tab.
	 * @param callable $content_renderer Callback rendering page content.
	 *
	 * @return void
	 */
	private function render_tabbed_page( string $active_tab, callable $content_renderer ): void {
		if ( ! current_user_can( Constants::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'search-analytics-insights' ) );
		}
		?>
		<div class="wrap search-analytics-insights-wrap search-analytics-insights-admin-wrap" data-copied-label="<?php echo esc_attr__( 'Copied', 'search-analytics-insights' ); ?>">
			<div class="sai-admin-header">
				<h1><?php esc_html_e( 'Search Analytics & Insights', 'search-analytics-insights' ); ?></h1>
				<p class="sai-admin-header-subtitle"><?php esc_html_e( 'Privacy-first search activity tracking and reporting.', 'search-analytics-insights' ); ?></p>
			</div>
			
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-insights' ) ); ?>" class="nav-tab <?php echo 'dashboard' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-dashboard"></span> <?php esc_html_e( 'Dashboard', 'search-analytics-insights' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-analytics' ) ); ?>" class="nav-tab <?php echo 'analytics' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e( 'Analytics & Insights', 'search-analytics-insights' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Search Settings', 'search-analytics-insights' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-tools' ) ); ?>" class="nav-tab <?php echo 'tools' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e( 'Tools', 'search-analytics-insights' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=search-analytics-help' ) ); ?>" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Help & Docs', 'search-analytics-insights' ); ?>
				</a>
			</h2>
			
			<div class="sai-admin-content-area">
				<?php call_user_func( $content_renderer ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Normalize incoming filter values.
	 *
	 * @return array<string, mixed>
	 */
	private function get_filters(): array {
		$page     = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 20; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return array(
			'date_from'   => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'     => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search_term' => isset( $_GET['search_term'] ) ? sanitize_text_field( wp_unslash( $_GET['search_term'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'no_results'  => isset( $_GET['no_results'] ) ? absint( wp_unslash( $_GET['no_results'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'page'        => max( 1, $page ),
			'per_page'    => max( 1, min( 100, $per_page ) ),
		);
	}

	/**
	 * Get the display label for the user column.
	 *
	 * @param array<string, mixed> $record Search record.
	 *
	 * @return string
	 */
	private function get_user_label( array $record ): string {
		$user_id = isset( $record['user_id'] ) ? absint( $record['user_id'] ) : 0;

		if ( 0 === $user_id ) {
			return __( 'Visitor', 'search-analytics-insights' );
		}

		$user = get_userdata( $user_id );

		if ( ! $user || empty( $user->user_login ) ) {
			return __( 'Visitor', 'search-analytics-insights' );
		}

		return (string) $user->user_login;
	}

	/**
	 * Get shortcode documentation rows.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function get_shortcode_docs(): array {
		return array(
			array(
				'tag'         => '[search_insights_form]',
				'description' => __( 'Displays a search form that automatically uses live AJAX search or native search depending on plugin settings.', 'search-analytics-insights' ),
				'example'     => '[search_insights_form placeholder="Search..." button_text="Search"]',
			),
			array(
				'tag'         => '[search_insights_popular]',
				'description' => __( 'Displays the most searched terms from the analytics database.', 'search-analytics-insights' ),
				'example'     => '[search_insights_popular limit="5" show_count="true"]',
			),
			array(
				'tag'         => '[search_insights_trending]',
				'description' => __( 'Displays trending searches from the last 7 days.', 'search-analytics-insights' ),
				'example'     => '[search_insights_trending limit="5" title="Trending Searches"]',
			),
		);
	}

	/**
	 * Render a summary card.
	 *
	 * @param string $label Label text.
	 * @param int    $value Value text.
	 *
	 * @return void
	 */
	private function render_summary_card( string $label, int $value ): void {
		?>
		<div class="search-analytics-insights-card">
			<span class="search-analytics-insights-card-label"><?php echo esc_html( $label ); ?></span>
			<span class="search-analytics-insights-card-value"><?php echo esc_html( (string) $value ); ?></span>
		</div>
		<?php
	}
}