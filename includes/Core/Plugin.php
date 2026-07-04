<?php
/**
 * Main plugin orchestrator.
 *
 * @package SearchLens
 */

namespace SearchLens\Core;

use SearchLens\API\RestController;
use SearchLens\Ajax\SearchController;
use SearchLens\Ajax\SearchService as AjaxSearchService;
use SearchLens\Ajax\SearchTracker as AjaxSearchTracker;
use SearchLens\Admin\Dashboard;
use SearchLens\Admin\Assets;
use SearchLens\Admin\Menu;
use SearchLens\Admin\Settings;
use SearchLens\Analytics\Service\AnalyticsService;
use SearchLens\Database\Repository\SearchRepository;
use SearchLens\Database\Schema;
use SearchLens\Helpers\Privacy;
use SearchLens\Tracking\Tracker;
use SearchLens\Shortcodes\Registrar;
use SearchLens\Shortcodes\AjaxSearchForm;
use SearchLens\Shortcodes\SearchForm;
use SearchLens\Shortcodes\PopularSearches;
use SearchLens\Shortcodes\TrendingSearches;
use SearchLens\Widgets\WidgetManager;
use SearchLens\Blocks\BlockManager;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates plugin bootstrapping and runtime hook registration.
 */
final class Plugin {
	private static ?self $instance = null;
	private Container $container;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->container = new Container();
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->register_hooks();
		}

		return self::$instance;
	}

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'boot_modules' ), 5 );
		add_action( 'searchlens_cleanup', array( $this, 'run_cleanup' ) );
	}



	/**
	 * Get the service container.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Boot feature modules that are available.
	 *
	 * @return void
	 */
	public function boot_modules(): void {
		$this->maybe_upgrade_schema();

		$this->container->set(
			SearchRepository::class,
			static function (): SearchRepository {
				return new SearchRepository();
			}
		);

		$this->container->set(
			Tracker::class,
			function ( Container $container ): Tracker {
				return new Tracker( $container->get( SearchRepository::class ) );
			}
		);

		$this->container->set(
			AnalyticsService::class,
			function ( Container $container ): AnalyticsService {
				return new AnalyticsService( $container->get( SearchRepository::class ) );
			}
		);

		$this->container->set(
			Settings::class,
			static function (): Settings {
				return new Settings();
			}
		);

		$this->container->set(
			AjaxSearchService::class,
			function ( Container $container ): AjaxSearchService {
				return new AjaxSearchService(
					$container->get( SearchRepository::class ),
					$container->get( Settings::class )
				);
			}
		);

		$this->container->set(
			AjaxSearchTracker::class,
			function ( Container $container ): AjaxSearchTracker {
				return new AjaxSearchTracker( $container->get( SearchRepository::class ) );
			}
		);

		$this->container->set(
			Dashboard::class,
			function ( Container $container ): Dashboard {
				return new Dashboard( $container->get( AnalyticsService::class ) );
			}
		);

		$this->container->set(
			Menu::class,
			function ( Container $container ): Menu {
				return new Menu( $container->get( Dashboard::class ) );
			}
		);

		$this->container->set(
			Assets::class,
			static function (): Assets {
				return new Assets();
			}
		);

		$this->container->set(
			RestController::class,
			function ( Container $container ): RestController {
				return new RestController( $container->get( AnalyticsService::class ) );
			}
		);

		$this->container->set(
			SearchController::class,
			function ( Container $container ): SearchController {
				return new SearchController(
					$container->get( AjaxSearchService::class ),
					$container->get( AjaxSearchTracker::class )
				);
			}
		);

		$this->container->set(
			Privacy::class,
			static function (): Privacy {
				return new Privacy();
			}
		);

		$this->container->set(
			AjaxSearchForm::class,
			function ( Container $container ): AjaxSearchForm {
				return new AjaxSearchForm( $container->get( Settings::class ) );
			}
		);

		$this->container->set(
			SearchForm::class,
			function ( Container $container ): SearchForm {
				return new SearchForm( $container->get( Settings::class ) );
			}
		);

		$this->container->set(
			PopularSearches::class,
			function ( Container $container ): PopularSearches {
				return new PopularSearches(
					$container->get( AnalyticsService::class )
				);
			}
		);

		$this->container->set(
			TrendingSearches::class,
			function ( Container $container ): TrendingSearches {
				return new TrendingSearches(
					$container->get( AnalyticsService::class )
				);
			}
		);

		$this->container->set(
			Registrar::class,
			function ( Container $container ): Registrar {
				return new Registrar(
					$container->get( AjaxSearchForm::class ),
					$container->get( SearchForm::class ),
					$container->get( PopularSearches::class ),
					$container->get( TrendingSearches::class ),
					$container->get( Settings::class )
				);
			}
		);

		$this->container->set(
			WidgetManager::class,
			function ( Container $container ): WidgetManager {
				return new WidgetManager( $container->get( Settings::class ) );
			}
		);

		$this->container->set(
			BlockManager::class,
			static function (): BlockManager {
				return new BlockManager();
			}
		);

		$this->container->get( Tracker::class )->register_hooks();
		$this->container->get( RestController::class )->register_hooks();
		$this->container->get( SearchController::class )->register_hooks();
		$this->container->get( Privacy::class )->register_hooks();
		$this->container->get( Registrar::class )->register_hooks();
		$this->container->get( WidgetManager::class )->register_hooks();
		$this->container->get( BlockManager::class )->register_hooks();
		$this->container->get( Settings::class )->register_hooks();

		if ( is_admin() ) {
			$this->container->get( Menu::class )->register_hooks();
			$this->container->get( Assets::class )->register_hooks();
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'searchlens_container_ready', $this->container );
		do_action( 'search_analytics_insights_container_ready', $this->container );
	}

	/**
	 * Upgrade the schema when the plugin version changes.
	 *
	 * @return void
	 */
	private function maybe_upgrade_schema(): void {
		$current_version = (string) get_option( Constants::OPTION_PLUGIN_VERSION, '' );

		if ( Constants::VERSION === $current_version ) {
			return;
		}

		Schema::create_table();
		update_option( Constants::OPTION_SCHEMA_VERSION, Constants::VERSION, true );
		update_option( Constants::OPTION_PLUGIN_VERSION, Constants::VERSION, true );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'searchlens-search-analytics',
			false,
			dirname( Constants::plugin_basename() ) . '/languages'
		);
	}

	/**
	 * Run the retention cleanup scheduled cron job.
	 *
	 * @return void
	 */
	public function run_cleanup(): void {
		$settings           = $this->container->has( Settings::class ) ? $this->container->get( Settings::class ) : new Settings();
		$analytics_settings = $settings->get_analytics_settings();
		$retention_days     = isset( $analytics_settings['search_retention_period'] ) ? absint( $analytics_settings['search_retention_period'] ) : 30;

		if ( $retention_days <= 0 ) {
			return;
		}

		$repository = $this->container->has( SearchRepository::class ) ? $this->container->get( SearchRepository::class ) : new SearchRepository();
		$repository->delete_older_than( $retention_days );
	}
}
