<?php

namespace WPRobo_Engage_Lite\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Deactivator
 *
 * Handles plugin deactivation tasks.
 *
 * @package WPRobo_Engage_Lite\Includes
 */
class Deactivator {

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules on deactivation.
		flush_rewrite_rules();
	}
}
