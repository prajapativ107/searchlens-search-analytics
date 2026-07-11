<?php
/**
 * Block manager.
 *
 * @package VPLens
 */

namespace VPLens\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin blocks.
 */
final class BlockManager {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register block types.
	 *
	 * @return void
	 */
	public function register_blocks(): void {
		register_block_type(
			VPLENS_PATH . 'blocks/search-widget'
		);
	}
}
