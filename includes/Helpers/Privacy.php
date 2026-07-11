<?php
/**
 * Privacy helper.
 *
 * @package SearchLens
 */

namespace SearchLens\Helpers;

use SearchLens\Core\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Registers privacy policy guidance for the plugin.
 */
final class Privacy {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
	}

	/**
	 * Add the plugin privacy policy content.
	 *
	 * @return void
	 */
	public function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			/* translators: %s: plugin name */
			__( '%s collects search terms entered into the site search, the date and time of the search, the number of search results returned, the logged-in user ID when available, and a random anonymous session identifier. It does not collect raw IP addresses or user agent strings.', 'search-analytics-insights' ),
			esc_html__( 'SearchLens – Search Analytics & Insights', 'search-analytics-insights' )
		);

		wp_add_privacy_policy_content( 'search-analytics-insights', wp_kses_post( wpautop( $content ) ) );
	}
}
