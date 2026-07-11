<?php
/**
 * Service container.
 *
 * @package VPLens
 */

namespace VPLens\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal dependency container for plugin services.
 */
final class Container {
	/**
	 * Shared service instances.
	 *
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * Service factories.
	 *
	 * @var array<string, callable(self):object>
	 */
	private array $factories = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $id      Service identifier.
	 * @param callable $factory Factory that returns the service instance.
	 *
	 * @return void
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	/**
	 * Check whether a service is registered.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] ) || isset( $this->factories[ $id ] );
	}

	/**
	 * Resolve a service instance.
	 *
	 * @param string $id Service identifier.
	 *
	 * @return object
	 *
	 * @throws \RuntimeException When the service is not registered.
	 */
	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new \RuntimeException( sprintf( 'Service "%s" is not registered.', esc_html( $id ) ) );
		}

		$this->instances[ $id ] = call_user_func( $this->factories[ $id ], $this );

		return $this->instances[ $id ];
	}

	/**
	 * Register a service instance directly.
	 *
	 * @param string $id       Service identifier.
	 * @param object $instance Service instance.
	 *
	 * @return void
	 */
	public function set_instance( string $id, object $instance ): void {
		$this->instances[ $id ] = $instance;
	}
}
