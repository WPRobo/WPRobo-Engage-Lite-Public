<?php
namespace WPRobo_Engage_Lite\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Analytics_Handler
 *
 * Handles analytics tracking for impressions and conversions.
 *
 * @package WPRobo_Engage_Lite\Includes
 */
class Analytics_Handler {

	/**
	 * Track an impression event via AJAX.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function track_impression(): void {
		// Verify nonce — the nonce is embedded in the page via wp_localize_script.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpr_engage_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wprobo-engage-lite' ) ) );
			return;
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( empty( $campaign_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'wprobo-engage-lite' ) ) );
			return;
		}

		// Rate-limit impression tracking: 1 per IP per campaign per 10 seconds
		// to prevent analytics inflation from bots or rapid reloads.
		$ip       = $this->get_client_ip();
		$rate_key = 'wpr_imp_' . md5( $ip . '_' . $campaign_id );
		if ( get_transient( $rate_key ) ) {
			// Silently succeed — don't expose rate-limit details to client.
			wp_send_json_success( array( 'message' => 'ok' ) );
			return;
		}
		set_transient( $rate_key, 1, 10 );

		$result = self::track_event( $campaign_id, 'impression' );

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not track impression.', 'wprobo-engage-lite' ) ) );
			return;
		}

		wp_send_json_success( array( 'message' => __( 'Impression tracked.', 'wprobo-engage-lite' ) ) );
	}

	/**
	 * Track an event in the analytics table.
	 *
	 * @since 1.0.0
	 * @param int    $campaign_id The campaign ID.
	 * @param string $event_type  The event type ('impression' or 'conversion').
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public static function track_event( int $campaign_id, string $event_type ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wprobo_engage_analytics';

		// Detect device type.
		$device_type = self::detect_device_type();

		// Get user agent.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: '';

		// Get current page URL from Referer header, falling back to POST body.
		$page_url = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified by calling method (track_impression).
		if ( empty( $page_url ) && isset( $_POST['page_url'] ) ) {
			$page_url = esc_url_raw( wp_unslash( $_POST['page_url'] ) );
		}
		// phpcs:enable
		$page_url = substr( $page_url, 0, 500 );

		// Get IP address.
		$ip_address = self::get_client_ip();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, no WP API equivalent.
		$result = $wpdb->insert(
			$table_name,
			array(
				'campaign_id' => $campaign_id,
				'event_type'  => $event_type,
				'event_date'  => current_time( 'mysql' ),
				'device_type' => $device_type,
				'user_agent'  => $user_agent,
				'page_url'    => $page_url,
				'ip_address'  => $ip_address,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false !== $result ) {
			// Invalidate the cached dashboard stats so the next page load reflects
			// the newly recorded event without waiting for the transient to expire.
			delete_transient( 'wpr_engage_dashboard_stats' );

			/**
			 * Fires after an analytics event has been recorded.
			 *
			 * @since 1.0.0
			 * @param int    $campaign_id The campaign ID.
			 * @param string $event_type  The event type ('impression' or 'conversion').
			 */
			do_action( 'wprobo_engage_analytics_event_tracked', $campaign_id, $event_type );
		}

		return $result;
	}

	/**
	 * Create the custom database table for analytics.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'wprobo_engage_analytics';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) UNSIGNED NOT NULL,
			event_type varchar(20) NOT NULL,
			event_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			device_type varchar(20) DEFAULT '' NOT NULL,
			user_agent varchar(255) DEFAULT '' NOT NULL,
			page_url varchar(500) DEFAULT '' NOT NULL,
			ip_address varchar(45) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id),
			KEY event_type (event_type),
			KEY event_date (event_date),
			KEY device_type (device_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Detect device type from user agent.
	 *
	 * @since 1.0.0
	 * @return string Device type (desktop, mobile, tablet, or unknown).
	 */
	private static function detect_device_type(): string {
		if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return 'unknown';
		}

		$user_agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );

		// Check for tablet first.
		if ( preg_match( '/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent ) ) {
			return 'tablet';
		}

		// Check for mobile.
		if ( preg_match( '/Mobile|iP(hone|od)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/i', $user_agent ) ) {
			return 'mobile';
		}

		return 'desktop';
	}

	/**
	 * Returns the most-trustworthy client IP address available.
	 *
	 * @since 1.0.0
	 * @return string IP address string, or empty string if unavailable.
	 */
	private static function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$header_value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				$ips          = explode( ',', $header_value );
				$ip           = trim( $ips[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}
}
