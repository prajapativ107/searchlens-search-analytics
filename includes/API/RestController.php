<?php
/**
 * REST controller.
 *
 * @package VPLens
 */

namespace VPLens\API;

use VPLens\Analytics\Service\AnalyticsService;
use VPLens\Core\Constants;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Registers REST endpoints for the plugin.
 */
final class RestController {
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
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register plugin routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'vplens/v1',
			'/analytics',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'permissions_check' ),
				'callback'            => array( $this, 'get_analytics' ),
				'args'                => $this->get_filter_args(),
			)
		);

		register_rest_route(
			'vplens/v1',
			'/searches',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'permissions_check' ),
				'callback'            => array( $this, 'get_searches' ),
				'args'                => $this->get_search_args(),
			)
		);
	}

	/**
	 * Permission check for admin-only access.
	 *
	 * @return bool
	 */
	public function permissions_check(): bool {
		return current_user_can( Constants::CAPABILITY );
	}

	/**
	 * Return analytics data.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_analytics( WP_REST_Request $request ): WP_REST_Response {
		$filters = $this->prepare_filters( $request );

		return rest_ensure_response(
			array(
				'summary'      => $this->analytics_service->get_dashboard_summary( $filters ),
				'top_terms'    => $this->analytics_service->get_top_search_terms( $filters, 10 ),
				'daily_counts' => $this->analytics_service->get_searches_per_day( $filters ),
			)
		);
	}

	/**
	 * Return paginated search records.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_REST_Response
	 */
	public function get_searches( WP_REST_Request $request ): WP_REST_Response {
		$filters  = $this->prepare_filters( $request );
		$page     = isset( $request['page'] ) ? absint( $request['page'] ) : 1;
		$per_page = isset( $request['per_page'] ) ? absint( $request['per_page'] ) : 20;

		return rest_ensure_response(
			$this->analytics_service->get_search_records( $filters, $page, $per_page )
		);
	}

	/**
	 * Filter argument schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_filter_args(): array {
		return array(
			'date_from'   => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'date_to'     => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search_term' => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'no_results'  => array(
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Search list argument schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_search_args(): array {
		return array_merge(
			$this->get_filter_args(),
			array(
				'page'     => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
				'per_page' => array(
					'type'              => 'integer',
					'required'          => false,
					'default'           => 20,
					'sanitize_callback' => 'absint',
				),
			)
		);
	}

	/**
	 * Convert request params into a sanitized filter array.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return array<string, mixed>
	 */
	private function prepare_filters( WP_REST_Request $request ): array {
		return array(
			'date_from'   => sanitize_text_field( (string) $request->get_param( 'date_from' ) ),
			'date_to'     => sanitize_text_field( (string) $request->get_param( 'date_to' ) ),
			'search_term' => sanitize_text_field( (string) $request->get_param( 'search_term' ) ),
			'no_results'  => absint( $request->get_param( 'no_results' ) ),
		);
	}
}
