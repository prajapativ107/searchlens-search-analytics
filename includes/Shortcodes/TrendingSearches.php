<?php
/**
 * Trending searches shortcode.
 *
 * @package VPLens
 */

namespace VPLens\Shortcodes;

use VPLens\Analytics\Service\AnalyticsService;
use VPLens\Core\Constants;

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
				'title' => __( 'Trending Searches', 'search-analytics-insights' ),
			),
			$attributes,
			'searsean_trending'
		);

		$limit    = max( 1, min( 50, absint( $attributes['limit'] ) ) );
		$title    = sanitize_text_field( (string) $attributes['title'] );
		$filters  = array(
			'date_from' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
		);
		$searches = $this->analytics_service->get_top_search_terms( $filters, $limit );

		ob_start();
		?>
		<div class="vplens-shortcode vplens-trending-searches">
			<?php if ( '' !== $title ) : ?>
				<h2 class="vplens-shortcode-title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

			<?php if ( empty( $searches ) ) : ?>
				<p><?php esc_html_e( 'No trending searches found for the last 7 days.', 'search-analytics-insights' ); ?></p>
			<?php else : ?>
				<ul class="vplens-trending-searches-list">
					<?php foreach ( $searches as $row ) : ?>
						<?php
						$term   = isset( $row['search_term'] ) ? (string) $row['search_term'] : '';
						$search = add_query_arg( 's', rawurlencode( $term ), home_url( '/' ) );
						?>
						<li class="vplens-trending-searches-item">
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