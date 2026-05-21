<?php

namespace WPRobo_Engage_Lite\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Trait Singleton
 * Ensures that a class has only one instance and provides a global point of access to it.
 *
 * @package WPRobo_Engage_Lite\Core
 */
trait Singleton {

	/**
	 * The single instance of the class.
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance of the class.
	 *
	 * @return self
	 */
	final public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * class via the `new` operator from outside of this class.
	 */
	protected function __construct() {
	}

	/**
	 * Private clone method to prevent cloning of the instance of the class.
	 */
	private function __clone() {
	}

	/**
	 * Private unserialize method to prevent unserializing of the instance of the class.
	 */
	public function __wakeup() {
	}
}
