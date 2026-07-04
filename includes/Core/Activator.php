<?php
/**
 * Plugin activation handler.
 *
 * @package SearchLens
 */

namespace SearchLens\Core;

use SearchLens\Database\Schema;

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
		// Migrate old settings to new keys if needed
		if ( false === get_option( Constants::OPTION_SETTINGS, false ) ) {
			$old_settings = get_option( 'search_analytics_insights_settings', false );
			if ( false !== $old_settings ) {
				add_option( Constants::OPTION_SETTINGS, $old_settings );
			}
		}

		if ( false === get_option( Constants::OPTION_SCHEMA_VERSION, false ) ) {
			$old_schema = get_option( 'search_analytics_insights_schema_version', false );
			if ( false !== $old_schema ) {
				add_option( Constants::OPTION_SCHEMA_VERSION, $old_schema );
			}
		}

		if ( false === get_option( Constants::OPTION_PLUGIN_VERSION, false ) ) {
			$old_version = get_option( 'search_analytics_insights_plugin_version', false );
			if ( false !== $old_version ) {
				add_option( Constants::OPTION_PLUGIN_VERSION, $old_version );
			}
		}

		if ( false === get_option( Constants::OPTION_SEARCHABLE_POST_TYPES, false ) ) {
			$old_post_types = get_option( 'search_analytics_insights_post_types', false );
			if ( false !== $old_post_types ) {
				add_option( Constants::OPTION_SEARCHABLE_POST_TYPES, $old_post_types );
			}
		}

		if ( false === get_option( Constants::OPTION_AJAX_SEARCH_SETTINGS, false ) ) {
			$old_ajax = get_option( 'search_analytics_insights_ajax_search_settings', false );
			if ( false !== $old_ajax ) {
				add_option( Constants::OPTION_AJAX_SEARCH_SETTINGS, $old_ajax );
			}
		}

		Schema::create_table();

		if ( false === get_option( Constants::OPTION_SETTINGS, false ) ) {
			$settings             = self::DEFAULT_SETTINGS;
			$legacy_post_types    = get_option( Constants::OPTION_SEARCHABLE_POST_TYPES, false );
			$legacy_ajax_settings = get_option( Constants::OPTION_AJAX_SEARCH_SETTINGS, false );

			if ( is_array( $legacy_post_types ) && ! empty( $legacy_post_types ) ) {
				$settings['search_sources']['searchable_post_types'] = array_values(
					array_filter(
						array_map(
							static function ( $post_type ): string {
								return sanitize_key( (string) $post_type );
							},
							$legacy_post_types
						)
					)
				);
			}

			if ( is_array( $legacy_ajax_settings ) ) {
				if ( isset( $legacy_ajax_settings['max_results'] ) ) {
					$settings['ajax_search']['maximum_results'] = absint( $legacy_ajax_settings['max_results'] );
				}

				if ( isset( $legacy_ajax_settings['minimum_characters'] ) ) {
					$settings['ajax_search']['minimum_characters'] = absint( $legacy_ajax_settings['minimum_characters'] );
				}

				$settings['search_results']['show_featured_images'] = ! empty( $legacy_ajax_settings['show_featured_images'] );
			}

			add_option( Constants::OPTION_SETTINGS, $settings );
		}

		update_option( Constants::OPTION_SCHEMA_VERSION, Constants::VERSION, true );
		update_option( Constants::OPTION_PLUGIN_VERSION, Constants::VERSION, true );

		if ( ! wp_next_scheduled( 'searchlens_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'searchlens_cleanup' );
		}
	}
}
