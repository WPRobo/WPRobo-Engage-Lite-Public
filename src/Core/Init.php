<?php

namespace WPRobo_Engage_Lite\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Init
 *
 * The main plugin class responsible for initializing the plugin,
 * setting up hooks, and loading all necessary components.
 *
 * @package WPRobo_Engage_Lite\Core
 */
final class Init {

	// Use the Singleton trait.
	use Singleton;

	/**
	 * Protected constructor to initialize the plugin.
	 * It sets up the initial hooks and loads the necessary classes.
	 */
	protected function __construct() {
		$this->register_services();
	}

	/**
	 * Instantiates the Services class and registers all hooks.
	 *
	 * @return void
	 */
	private function register_services(): void {
		( new Services() )->register();
	}
}
