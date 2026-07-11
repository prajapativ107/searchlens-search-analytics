<?php
/**
 * Plugin activation handler.
 *
 * @package VPLens
 */

namespace VPLens\Core;

use VPLens\Database\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation tasks.
 */
final class Activator {
	private const DEFAULT_SETTINGS = array(
		'search_form'    => array(
			'placeholder' => 'Search posts and pages...',
			'button_text' => 'Search',
			'show_button' => true,
			'form_style'  => 'rounded',
		),
		'ajax_search'    => array(
			'enabled'            => true,
			'minimum_characters' => 2,
			'maximum_results'    => 10,
			'debounce_time'      => 300,
		),
		'search_results' => array(
			'show_featured_images' => true,
			'show_post_type_label' => true,
			'no_results_message'   => 'No results found.',
		),
		'search_sources' => array(
			'load_all_public_post_types' => false,
			'searchable_post_types'      => array( 'post', 'page' ),
		),
		'widget_block'   => array(
			'open_mode'  => 'dropdown',
			'show_label' => true,
		),
		'analytics'      => array(
			'track_logged_in_users'   => false,
			'track_guests'            => true,
			'search_retention_period' => 30,
		),
	);

	/**
	 * Run activation logic.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Schema::create_table();

		if ( false === get_option( Constants::OPTION_SETTINGS, false ) ) {
			add_option( Constants::OPTION_SETTINGS, self::DEFAULT_SETTINGS );
		}

		update_option( Constants::OPTION_SCHEMA_VERSION, Constants::VERSION, true );
		update_option( Constants::OPTION_PLUGIN_VERSION, Constants::VERSION, true );

		if ( ! wp_next_scheduled( 'vplens_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'vplens_cleanup' );
		}
	}
}
