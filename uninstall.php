<?php
/**
 * Uninstall entry point.
 *
 * @package SearchLens
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Core/Constants.php';
require_once __DIR__ . '/includes/Core/Uninstaller.php';

SearchLens\Core\Uninstaller::uninstall();
