<?php
/**
 * Trending searches shortcode.
 *
 * @package SearchLens
 */

namespace SearchLens\Shortcodes;

use SearchLens\Analytics\Service\AnalyticsService;
use SearchLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Renders trending searches from the last 7 days.
 */
final class TrendingSearches {
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
	 * Render the shortcode output.
	 *
	 * @param array<string, mixed> $attributes Shortcode attributes.
	 *
	 * @return string
	 */
	public function render( array $attributes = array() ): string {
		$attributes = shortcode_atts(
			array(
				'limit' => 5,
				'title' => __( 'Trending Searches', 'searchlens-search-analytics' ),
			),
			$attributes,
			'searchlens_trending'
		);

		$limit    = max( 1, min( 50, absint( $attributes['limit'] ) ) );
		$title    = sanitize_text_field( (string) $attributes['title'] );
		$filters  = array(
			'date_from' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
		);
		$searches = $this->analytics_service->get_top_search_terms( $filters, $limit );

		ob_start();
		?>
		<div class="searchlens-shortcode searchlens-trending-searches">
			<?php if ( '' !== $title ) : ?>
				<h2 class="searchlens-shortcode-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( empty( $searches ) ) : ?>
				<p><?php esc_html_e( 'No trending searches found for the last 7 days.', 'searchlens-search-analytics' ); ?></p>
			<?php else : ?>
				<ul class="searchlens-trending-searches-list">
					<?php foreach ( $searches as $row ) : ?>
						<?php
						$term   = isset( $row['search_term'] ) ? (string) $row['search_term'] : '';
						$search = add_query_arg( 's', rawurlencode( $term ), home_url( '/' ) );
						?>
						<li class="searchlens-trending-searches-item">
							<a href="<?php echo esc_url( $search ); ?>"><?php echo esc_html( $term ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}