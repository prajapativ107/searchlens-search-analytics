<?php
/**
 * Block manager.
 *
 * @package SearchLens
 */

namespace SearchLens\Blocks;

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
			SEARCHLENS_PATH . 'blocks/search-widget'
		);
	}
}
