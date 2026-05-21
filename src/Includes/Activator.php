<?php

namespace WPRobo_Engage_Lite\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Activator
 *
 * Handles plugin activation tasks.
 */
class Activator {
	/**
	 * Fired when the plugin is activated.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Create custom table for leads.
		Lead_Handler::create_table();

		// Create custom table for analytics.
		Analytics_Handler::create_table();

		/**
		 * Store the current schema version so that Lead_Handler can determine
		 * whether the form_data column exists without running a SHOW COLUMNS
		 * query on every form submission.
		 *
		 * Bump this value whenever a DB schema change is made so that existing
		 * installs (upgraded via dbDelta) are also correctly identified.
		 */
		update_option( 'wpr_engage_schema_version', '1.1', false );

		// Flush rewrite rules for the CPT.
		flush_rewrite_rules();
	}
}
