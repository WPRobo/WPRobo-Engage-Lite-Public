<?php
/**
 * Plugin Name:       WPRobo Engage Lite
 * Plugin URI:        https://wprobo.com/plugins/wprobo-engage
 * Description:       Grow your email list with beautiful popups, floating bars, and slide-ins. Create unlimited campaigns with time delay, scroll depth, and exit-intent triggers.
 * Version:           1.0.0
 * Author:            wprobo
 * Author URI:        https://wprobo.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wprobo-engage-lite
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Abort if the Pro version is already active.
 * We check the active_plugins option directly because Pro may load after Lite
 * alphabetically, so its constants may not be defined yet at this point.
 */
$wprobo_engage_lite_active_plugins = (array) get_option( 'active_plugins', array() );
if ( in_array( 'wprobo-engage-pro/wprobo-engage.php', $wprobo_engage_lite_active_plugins, true ) ) {
	/**
	 * Show an admin notice telling the user Lite was not loaded because Pro is active.
	 *
	 * @return void
	 */
	function wprobo_engage_lite_pro_active_notice() {
		echo '<div class="notice notice-warning is-dismissible"><p>';
		echo '<strong>' . esc_html__( 'WPRobo Engage (Free)', 'wprobo-engage-lite' ) . '</strong> — ';
		esc_html_e( 'The Pro version is already active. The free version has been deactivated.', 'wprobo-engage-lite' );
		echo '</p></div>';
	}
	add_action( 'admin_notices', 'wprobo_engage_lite_pro_active_notice' );

	// Deactivate ourselves so only Pro remains active.
	add_action(
		'admin_init',
		function () {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			// Remove the "Plugin activated" notice since we immediately deactivated.
			if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				unset( $_GET['activate'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
		}
	);

	return; // Stop loading the rest of this file.
}

/**
 * Define constants.
 */
define( 'WPROBO_ENGAGE_LITE_VERSION', '1.0.0' );
define( 'WPROBO_ENGAGE_LITE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPROBO_ENGAGE_LITE_URL', plugin_dir_url( __FILE__ ) );
define( 'WPROBO_ENGAGE_LITE_UPGRADE_URL', 'https://wprobo.com/plugins/wprobo-engage/?utm_source=lite&utm_medium=plugin&utm_campaign=upgrade' );

// Include the autoloader.
if ( file_exists( WPROBO_ENGAGE_LITE_PATH . 'vendor/autoload.php' ) ) {
	require_once WPROBO_ENGAGE_LITE_PATH . 'vendor/autoload.php';
}

/**
 * The main function to begin execution of the plugin.
 *
 * @since 1.0.0
 * @return \WPRobo_Engage_Lite\Core\Init
 */
function wprobo_engage_lite_init() {
	return WPRobo_Engage_Lite\Core\Init::instance();
}

// Let's get this party started.
wprobo_engage_lite_init();

// Register activation hook.
register_activation_hook( __FILE__, 'wprobo_engage_lite_activate' );

/**
 * Activation callback. Blocks activation if Pro is active, otherwise runs Lite setup.
 *
 * @return void
 */
function wprobo_engage_lite_activate() {
	// Ensure is_plugin_active() is available (not always loaded during activation).
	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// If Pro is active, block Lite activation completely.
	$pro_plugin = 'wprobo-engage-pro/wprobo-engage.php';
	if ( is_plugin_active( $pro_plugin ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'WPRobo Engage Pro is already active. Please deactivate Pro first if you want to use the free version.', 'wprobo-engage-lite' ),
			esc_html__( 'Plugin Activation Error', 'wprobo-engage-lite' ),
			array(
				'back_link' => true,
				'response'  => 200,
			)
		);
	}

	WPRobo_Engage_Lite\Includes\Activator::activate();
}
