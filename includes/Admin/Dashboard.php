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
									<th><?php esc_html_e( 'From', 'search-analytics-insights' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'search-analytics-insights' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( empty( $recent_activity ) ) : ?>
									<tr><td colspan="5"><?php esc_html_e( 'No search activity found.', 'search-analytics-insights' ); ?></td></tr>
								<?php else : ?>
									<?php foreach ( $recent_activity as $record ) : ?>
										<tr>
											<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
											<td><span class="description"><?php echo esc_html( (string) $record['searched_at'] ); ?></span></td>
											<td><span class="badge <?php echo $record['result_count'] > 0 ? 'badge-success' : 'badge-danger'; ?>"><?php echo esc_html( (string) $record['result_count'] ); ?></span></td>
											<td><?php $this->render_page_column( $record ); ?></td>
											<td>
												<?php
												$record_id = ! empty( $record['id'] ) ? (string) $record['id'] : md5( (string) $record['search_term'] . '-' . (string) $record['searched_at'] );
												?>
												<button type="button" class="button button-small sai-toggle-details" data-target="sai-details-<?php echo esc_attr( $record_id ); ?>" data-show-label="<?php echo esc_attr__( 'Details', 'search-analytics-insights' ); ?>" data-hide-label="<?php echo esc_attr__( 'Hide', 'search-analytics-insights' ); ?>">
													<?php esc_html_e( 'Details', 'search-analytics-insights' ); ?>
												</button>
											</td>
										</tr>
										<?php $this->render_details_row( $record, 5 ); ?>
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
		$top_pages_by_title              = $this->analytics_service->get_top_pages_by_title( $filters, 10 );
		$top_pages_by_url                = $this->analytics_service->get_top_pages_by_url( $filters, 10 );
		$searches_by_page_type           = $this->analytics_service->get_searches_by_page_type( $filters, 10 );

		$total_pages = max( 1, (int) ceil( $aggregated_activity['total'] / $filters['per_page'] ) );
		$page        = min( $filters['page'], $total_pages );

		$this->render_tabbed_page(
			'analytics',
			function () use ( $filters, $summary, $aggregated_activity, $recent_activity, $top_terms, $no_result_terms, $daily_counts, $total_pages, $page, $top_pages_by_title, $top_pages_by_url, $searches_by_page_type ) {
				?>
			<div class="sai-filters-card" aria-labelledby="sai-filters-card-title">
				<div class="sai-filters-header">
					<div class="sai-filters-header-title">
						<span class="dashicons dashicons-filter sai-filter-icon" aria-hidden="true"></span>
						<div>
							<h3 id="sai-filters-card-title"><?php esc_html_e( 'Search Filters', 'search-analytics-insights' ); ?></h3>
							<p class="description"><?php esc_html_e( 'Refine your search analytics results', 'search-analytics-insights' ); ?></p>
						</div>
					</div>
					<button type="button" class="sai-filters-toggle-btn" aria-expanded="true" aria-controls="sai-filters-form" data-show-text="<?php echo esc_attr__( 'Show Filters', 'search-analytics-insights' ); ?>" data-hide-text="<?php echo esc_attr__( 'Hide Filters', 'search-analytics-insights' ); ?>">
						<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
						<span class="sai-toggle-text"><?php esc_html_e( 'Hide Filters', 'search-analytics-insights' ); ?></span>
					</button>
				</div>

				<form id="sai-filters-form" method="get" class="sai-filters-form">
					<input type="hidden" name="page" value="search-analytics-analytics" />
					<div class="sai-filters-form-inner">
						<div class="sai-filters-grid">
							<!-- From Date -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-date-from"><?php esc_html_e( 'From', 'search-analytics-insights' ); ?></label>
								<div class="sai-input-icon-wrapper">
									<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
									<input id="search-analytics-insights-date-from" type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" aria-label="<?php esc_attr_e( 'From Date', 'search-analytics-insights' ); ?>" />
								</div>
							</div>

							<!-- To Date -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-date-to"><?php esc_html_e( 'To', 'search-analytics-insights' ); ?></label>
								<div class="sai-input-icon-wrapper">
									<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
									<input id="search-analytics-insights-date-to" type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" aria-label="<?php esc_attr_e( 'To Date', 'search-analytics-insights' ); ?>" />
								</div>
							</div>

							<!-- Search Term -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-term"><?php esc_html_e( 'Search Term', 'search-analytics-insights' ); ?></label>
								<div class="sai-input-icon-wrapper">
									<span class="dashicons dashicons-search" aria-hidden="true"></span>
									<input id="search-analytics-insights-term" type="search" name="search_term" value="<?php echo esc_attr( $filters['search_term'] ); ?>" placeholder="<?php esc_attr_e( 'Search term...', 'search-analytics-insights' ); ?>" />
								</div>
							</div>

							<!-- Post Type -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-page-type"><?php esc_html_e( 'Post Type', 'search-analytics-insights' ); ?></label>
								<select id="search-analytics-insights-page-type" name="page_type">
									<option value=""><?php esc_html_e( 'All Post Types', 'search-analytics-insights' ); ?></option>
									<?php
									$post_types = get_post_types( array( 'public' => true ), 'objects' );
									$exclude    = array(
										'attachment',
										'revision',
										'nav_menu_item',
										'wp_navigation',
										'wp_template',
										'wp_template_part',
										'customize_changeset',
										'oembed_cache',
										'user_request',
										'wp_font_family',
										'wp_font_face',
									);
									foreach ( $post_types as $post_type ) :
										if ( in_array( $post_type->name, $exclude, true ) ) {
											continue;
										}
										$label = ! empty( $post_type->labels->singular_name ) ? $post_type->labels->singular_name : $post_type->name;
										?>
										<option value="<?php echo esc_attr( $post_type->name ); ?>" <?php selected( $filters['page_type'], $post_type->name ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- Page Title -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-page-title"><?php esc_html_e( 'Page Title', 'search-analytics-insights' ); ?></label>
								<input id="search-analytics-insights-page-title" type="text" name="page_title" value="<?php echo esc_attr( $filters['page_title'] ); ?>" placeholder="<?php esc_attr_e( 'Search page title...', 'search-analytics-insights' ); ?>" />
							</div>

							<!-- Page URL -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-page-url"><?php esc_html_e( 'Page URL', 'search-analytics-insights' ); ?></label>
								<div class="sai-input-icon-wrapper">
									<span class="dashicons dashicons-admin-links" aria-hidden="true"></span>
									<input id="search-analytics-insights-page-url" type="text" name="page_url" value="<?php echo esc_attr( $filters['page_url'] ); ?>" placeholder="<?php esc_attr_e( 'Search page URL...', 'search-analytics-insights' ); ?>" />
								</div>
							</div>

							<!-- Username -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-username"><?php esc_html_e( 'Username', 'search-analytics-insights' ); ?></label>
								<div class="sai-input-icon-wrapper">
									<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
									<input id="search-analytics-insights-username" type="text" name="username" value="<?php echo esc_attr( $filters['username'] ); ?>" placeholder="<?php esc_attr_e( 'Search username...', 'search-analytics-insights' ); ?>" />
								</div>
							</div>

							<!-- No Results Only Checkbox -->
							<div class="sai-filter-field sai-filter-field-checkbox">
								<label for="search-analytics-insights-no-results">
									<input id="search-analytics-insights-no-results" type="checkbox" name="no_results" value="1" <?php checked( 1, (int) $filters['no_results'] ); ?> />
									<span><?php esc_html_e( 'No Results Only', 'search-analytics-insights' ); ?></span>
								</label>
								<p class="description"><?php esc_html_e( 'Show only searches that returned zero results.', 'search-analytics-insights' ); ?></p>
							</div>

							<!-- Per Page Select -->
							<div class="sai-filter-field">
								<label for="search-analytics-insights-per-page"><?php esc_html_e( 'Per Page', 'search-analytics-insights' ); ?></label>
								<select id="search-analytics-insights-per-page" name="per_page">
									<?php foreach ( array( 10, 20, 50, 100 ) as $option ) : ?>
										<option value="<?php echo esc_attr( (string) $option ); ?>" <?php selected( $filters['per_page'], $option ); ?>><?php echo esc_html( (string) $option ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<!-- Actions Buttons -->
						<div class="sai-filters-actions">
							<div class="sai-filters-actions-left">
								<button type="submit" class="button button-primary sai-filter-submit-btn">
									<span class="dashicons dashicons-filter" aria-hidden="true"></span>
									<?php esc_html_e( 'Filter', 'search-analytics-insights' ); ?>
								</button>
							</div>
							<div class="sai-filters-actions-right">
								<?php
								$reset_url = admin_url( 'admin.php?page=search-analytics-analytics' );
								?>
								<a href="<?php echo esc_url( $reset_url ); ?>" class="button button-secondary sai-filter-reset-btn">
									<span class="dashicons dashicons-undo" aria-hidden="true"></span>
									<?php esc_html_e( 'Reset Filters', 'search-analytics-insights' ); ?>
								</a>
							</div>
						</div>

						<!-- Tip / Information Box -->
						<div class="sai-filters-tip-box">
							<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Tip: You can filter results by date range, page information, and search terms to find exactly what you need.', 'search-analytics-insights' ); ?></p>
						</div>
					</div>
				</form>

				<!-- Summary Bar -->
				<div class="sai-filters-summary-bar">
					<div class="sai-filters-summary-left">
						<strong><?php esc_html_e( 'Active Filters:', 'search-analytics-insights' ); ?></strong>
						<span class="sai-active-filters-list">
							<?php
							$active_filters_output = array();
							if ( ! empty( $filters['date_from'] ) ) {
								/* translators: %s: From date */
								$active_filters_output[] = sprintf( __( 'From: %s', 'search-analytics-insights' ), $filters['date_from'] );
							}
							if ( ! empty( $filters['date_to'] ) ) {
								/* translators: %s: To date */
								$active_filters_output[] = sprintf( __( 'To: %s', 'search-analytics-insights' ), $filters['date_to'] );
							}
							if ( ! empty( $filters['search_term'] ) ) {
								/* translators: %s: Search term */
								$active_filters_output[] = sprintf( __( 'Term: "%s"', 'search-analytics-insights' ), $filters['search_term'] );
							}
							if ( ! empty( $filters['page_type'] ) ) {
								/* translators: %s: Page type */
								$active_filters_output[] = sprintf( __( 'Type: %s', 'search-analytics-insights' ), $filters['page_type'] );
							}
							if ( ! empty( $filters['page_title'] ) ) {
								/* translators: %s: Page title */
								$active_filters_output[] = sprintf( __( 'Title: "%s"', 'search-analytics-insights' ), $filters['page_title'] );
							}
							if ( ! empty( $filters['page_url'] ) ) {
								/* translators: %s: Page URL */
								$active_filters_output[] = sprintf( __( 'URL: "%s"', 'search-analytics-insights' ), $filters['page_url'] );
							}
							if ( ! empty( $filters['username'] ) ) {
								/* translators: %s: Username */
								$active_filters_output[] = sprintf( __( 'Username: "%s"', 'search-analytics-insights' ), $filters['username'] );
							}
							if ( ! empty( $filters['no_results'] ) ) {
								$active_filters_output[] = __( 'No Results Only', 'search-analytics-insights' );
							}

							if ( empty( $active_filters_output ) ) {
								esc_html_e( 'Showing all searches', 'search-analytics-insights' );
							} else {
								echo esc_html( implode( ' | ', $active_filters_output ) );
							}
							?>
						</span>
					</div>
					<div class="sai-filters-summary-right">
						<span class="sai-summary-total-label"><?php esc_html_e( 'Total Searches:', 'search-analytics-insights' ); ?></span>
						<strong class="sai-summary-total-value"><?php echo esc_html( number_format_i18n( (int) $aggregated_activity['total'] ) ); ?></strong>
					</div>
				</div>
			</div>

			<div class="search-analytics-insights-summary">
				<?php $this->render_summary_card( __( 'Total Searches', 'search-analytics-insights' ), (int) $summary['total_searches'] ); ?>
				<?php $this->render_summary_card( __( 'Unique Searches', 'search-analytics-insights' ), (int) $summary['unique_searches'] ); ?>
				<?php $this->render_summary_card( __( 'No Result Searches', 'search-analytics-insights' ), (int) $summary['no_result_searches'] ); ?>
			</div>

			<div class="search-analytics-insights-summary">
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

			<div class="search-analytics-insights-summary">
				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'Top Pages Where Users Search', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Page Title', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $top_pages_by_title ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No searches recorded by page title yet.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $top_pages_by_title as $row ) : ?>
									<tr>
										<td>
											<strong><?php echo esc_html( ! empty( $row['page_title'] ) ? $row['page_title'] : __( 'Untitled Page', 'search-analytics-insights' ) ); ?></strong>
											<?php if ( ! empty( $row['page_url'] ) ) : ?>
												<br />
												<a href="<?php echo esc_url( $row['page_url'] ); ?>" target="_blank" class="description" style="font-size: 0.85em; word-break: break-all; color: var(--sai-muted);">
													<?php echo esc_html( wp_parse_url( $row['page_url'], PHP_URL_PATH ) ); ?>
												</a>
											<?php endif; ?>
										</td>
										<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'Most Active Search Pages', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Page URL', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $top_pages_by_url ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No searches recorded by URL yet.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $top_pages_by_url as $row ) : ?>
									<tr>
										<td>
											<a href="<?php echo esc_url( $row['page_url'] ); ?>" target="_blank" style="word-break: break-all;">
												<?php echo esc_html( $row['page_url'] ); ?>
											</a>
										</td>
										<td><?php echo esc_html( (string) $row['search_count'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>

				<div class="search-analytics-insights-panel">
					<h2><?php esc_html_e( 'Searches by Post Type', 'search-analytics-insights' ); ?></h2>
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Post Type', 'search-analytics-insights' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'search-analytics-insights' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $searches_by_page_type ) ) : ?>
								<tr><td colspan="2"><?php esc_html_e( 'No searches by Post type recorded yet.', 'search-analytics-insights' ); ?></td></tr>
							<?php else : ?>
								<?php foreach ( $searches_by_page_type as $row ) : ?>
									<tr>
										<td>
											<span class="sai-page-type-badge" style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; background-color: var(--sai-accent-soft); color: var(--sai-accent);">
												<?php echo esc_html( ! empty( $row['page_type'] ) ? $row['page_type'] : __( 'Other / Unknown', 'search-analytics-insights' ) ); ?>
											</span>
										</td>
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
							<th><?php esc_html_e( 'From', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Username', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'search-analytics-insights' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $aggregated_activity['items'] ) ) : ?>
							<tr><td colspan="7"><?php esc_html_e( 'No search records found.', 'search-analytics-insights' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $aggregated_activity['items'] as $record ) : ?>
								<tr>
									<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
									<td><?php echo esc_html( (string) $record['search_count'] ); ?></td>
									<td><?php echo esc_html( (string) $record['last_searched'] ); ?></td>
									<td><?php echo esc_html( (string) $record['result_count'] ); ?></td>
									<td><?php $this->render_page_column( $record ); ?></td>
									<td><?php echo esc_html( $this->get_user_label( $record ) ); ?></td>
									<td>
										<?php
										$record_id = md5( (string) $record['search_term'] . '-' . $this->get_user_label( $record ) );
										?>
										<button type="button" class="button button-small sai-toggle-details" data-target="sai-details-<?php echo esc_attr( $record_id ); ?>" data-show-label="<?php echo esc_attr__( 'Details', 'search-analytics-insights' ); ?>" data-hide-label="<?php echo esc_attr__( 'Hide', 'search-analytics-insights' ); ?>">
											<?php esc_html_e( 'Details', 'search-analytics-insights' ); ?>
										</button>
									</td>
								</tr>
								<?php $this->render_details_row( $record, 7 ); ?>
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
										'page_type'   => $filters['page_type'],
										'page_title'  => $filters['page_title'],
										'page_url'    => $filters['page_url'],
										'username'    => $filters['username'],
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
							<th><?php esc_html_e( 'From', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Username', 'search-analytics-insights' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'search-analytics-insights' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $recent_activity ) ) : ?>
							<tr><td colspan="6"><?php esc_html_e( 'No recent search activity found.', 'search-analytics-insights' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $recent_activity as $record ) : ?>
								<tr>
									<td><?php echo esc_html( (string) $record['search_term'] ); ?></td>
									<td><?php echo esc_html( (string) $record['searched_at'] ); ?></td>
									<td><?php echo esc_html( (string) $record['result_count'] ); ?></td>
									<td><?php $this->render_page_column( $record ); ?></td>
									<td><?php echo esc_html( $this->get_user_label( $record ) ); ?></td>
									<td>
										<?php
										$record_id = ! empty( $record['id'] ) ? (string) $record['id'] : md5( (string) $record['search_term'] . '-' . (string) $record['searched_at'] );
										?>
										<button type="button" class="button button-small sai-toggle-details" data-target="sai-details-<?php echo esc_attr( $record_id ); ?>" data-show-label="<?php echo esc_attr__( 'Details', 'search-analytics-insights' ); ?>" data-hide-label="<?php echo esc_attr__( 'Hide', 'search-analytics-insights' ); ?>">
											<?php esc_html_e( 'Details', 'search-analytics-insights' ); ?>
										</button>
									</td>
								</tr>
								<?php $this->render_details_row( $record, 6 ); ?>
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
		$per_page = isset( $_GET['per_page'] ) ? absint( wp_unslash( $_GET['per_page'] ) ) : 10; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return array(
			'date_from'   => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'date_to'     => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'search_term' => isset( $_GET['search_term'] ) ? sanitize_text_field( wp_unslash( $_GET['search_term'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'no_results'  => isset( $_GET['no_results'] ) ? absint( wp_unslash( $_GET['no_results'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'page'        => max( 1, $page ),
			'per_page'    => max( 1, min( 100, $per_page ) ),
			'page_type'   => isset( $_GET['page_type'] ) ? sanitize_text_field( wp_unslash( $_GET['page_type'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'page_title'  => isset( $_GET['page_title'] ) ? sanitize_text_field( wp_unslash( $_GET['page_title'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'page_url'    => isset( $_GET['page_url'] ) ? sanitize_text_field( wp_unslash( $_GET['page_url'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'username'    => isset( $_GET['username'] ) ? sanitize_text_field( wp_unslash( $_GET['username'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		$display_name = ! empty( $record['display_name'] ) ? (string) $record['display_name'] : '';
		if ( '' !== $display_name ) {
			return $display_name;
		}

		return ! empty( $record['user_login'] ) ? (string) $record['user_login'] : __( 'Guest', 'search-analytics-insights' );
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

	/**
	 * Render the page context column cell.
	 *
	 * @param array<string, mixed> $record Search record.
	 *
	 * @return void
	 */
	private function render_page_column( array $record ): void {
		$page_title = ! empty( $record['page_title'] ) ? $record['page_title'] : '';
		$page_url   = ! empty( $record['page_url'] ) ? $record['page_url'] : '';
		$referrer   = ! empty( $record['referrer'] ) ? $record['referrer'] : '';
		$page_type  = ! empty( $record['page_type'] ) ? $record['page_type'] : '';

		if ( empty( $page_title ) && empty( $page_url ) ) {
			echo '<span class="description">' . esc_html__( 'Direct/Unknown', 'search-analytics-insights' ) . '</span>';
			return;
		}

		$display_title = ! empty( $page_title ) ? $page_title : __( 'Untitled Page', 'search-analytics-insights' );
		$display_path  = $page_url;
		if ( ! empty( $page_url ) ) {
			$parsed_url   = wp_parse_url( $page_url );
			$display_path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : $page_url;
			if ( isset( $parsed_url['query'] ) ) {
				$display_path .= '?' . $parsed_url['query'];
			}
		}

		?>
		<div class="sai-page-column-cell" style="display: flex; flex-direction: column; gap: 2px;">
			<div class="sai-page-title-row" style="display: flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-admin-page" style="font-size: 16px; width: 16px; height: 16px; color: var(--sai-muted);" title="<?php echo esc_attr( $page_type ); ?>"></span>
				<strong class="sai-page-title" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr( $display_title ); ?>">
					<?php echo esc_html( $display_title ); ?>
				</strong>
			</div>
			<?php if ( ! empty( $page_url ) ) : ?>
				<a class="sai-page-url-link" href="<?php echo esc_url( $page_url ); ?>" target="_blank" style="font-size: 0.85em; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--sai-muted);" title="<?php echo esc_attr( $page_url ); ?>">
					<?php echo esc_html( $display_path ); ?>
				</a>
			<?php endif; ?>
			<?php if ( ! empty( $referrer ) ) : ?>
				<div class="sai-referrer-row" style="display: flex; align-items: center; gap: 4px; font-size: 0.8em; color: var(--sai-muted);">
					<span class="dashicons dashicons-share-alt2" style="font-size: 14px; width: 14px; height: 14px;" title="<?php echo esc_attr__( 'Referrer: ', 'search-analytics-insights' ) . esc_attr( $referrer ); ?>"></span>
					<span style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr( $referrer ); ?>">
						<?php echo esc_html( $referrer ); ?>
					</span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render the expandable details row.
	 *
	 * @param array<string, mixed> $record  Search record.
	 * @param int                  $colspan Number of columns to span.
	 *
	 * @return void
	 */
	private function render_details_row( array $record, int $colspan ): void {
		$page_title = ! empty( $record['page_title'] ) ? $record['page_title'] : __( 'N/A', 'search-analytics-insights' );
		$page_url   = ! empty( $record['page_url'] ) ? $record['page_url'] : '';
		$referrer   = ! empty( $record['referrer'] ) ? $record['referrer'] : __( 'Direct / None', 'search-analytics-insights' );
		$page_type  = ! empty( $record['page_type'] ) ? $record['page_type'] : __( 'Other / Unknown', 'search-analytics-insights' );

		$record_id = '';
		if ( ! empty( $record['id'] ) ) {
			$record_id = (string) $record['id'];
		} elseif ( ! empty( $record['latest_id'] ) ) {
			$record_id = (string) $record['latest_id'];
		} else {
			$record_id = md5( (string) $record['search_term'] . '-' . (string) ( isset( $record['searched_at'] ) ? $record['searched_at'] : $this->get_user_label( $record ) ) );
		}
		?>
		<tr id="sai-details-<?php echo esc_attr( $record_id ); ?>" class="sai-details-row" style="display: none;">
			<td colspan="<?php echo esc_attr( (string) $colspan ); ?>">
				<div class="sai-details-content" style="padding: 12px 16px; background-color: var(--sai-surface-alt); border-top: 1px solid var(--sai-border); border-bottom: 1px solid var(--sai-border);">
					<div class="sai-details-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
						<div>
							<strong><?php esc_html_e( 'Page Title:', 'search-analytics-insights' ); ?></strong>
							<div><?php echo esc_html( $page_title ); ?></div>
						</div>
						<div>
							<strong><?php esc_html_e( 'Page URL:', 'search-analytics-insights' ); ?></strong>
							<div>
								<?php if ( ! empty( $page_url ) ) : ?>
									<a href="<?php echo esc_url( $page_url ); ?>" target="_blank"><?php echo esc_html( $page_url ); ?></a>
								<?php else : ?>
									<?php echo esc_html__( 'N/A', 'search-analytics-insights' ); ?>
								<?php endif; ?>
							</div>
						</div>
						<div>
							<strong><?php esc_html_e( 'Post Type:', 'search-analytics-insights' ); ?></strong>
							<div>
								<span class="sai-page-type-badge" style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; background-color: var(--sai-accent-soft); color: var(--sai-accent);">
									<?php echo esc_html( $page_type ); ?>
								</span>
							</div>
						</div>
						<div>
							<strong><?php esc_html_e( 'Referrer:', 'search-analytics-insights' ); ?></strong>
							<div style="word-break: break-all;">
								<?php if ( filter_var( $referrer, FILTER_VALIDATE_URL ) ) : ?>
									<a href="<?php echo esc_url( $referrer ); ?>" target="_blank"><?php echo esc_html( $referrer ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $referrer ); ?>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<?php
	}
}