<?php
namespace WPRobo_Engage_Lite\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Lead_Handler
 *
 * Handles lead submissions and database interactions.
 */
class Lead_Handler {

	public function handle_submission(): void {
		/**
		 * For logged-in users submit with a nonce; for public (nopriv)
		 * submissions we cannot require a WordPress nonce because non-logged-in
		 * visitors on the site itself do not have access to wp_create_nonce().
		 * Instead, we apply IP-based rate-limiting (one submission per campaign
		 * per 30 s).
		 */
		if ( is_user_logged_in() ) {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpr_engage_nonce' ) ) {
				wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wprobo-engage-lite' ) ) );
				return;
			}
		} else {
			// Rate-limit public submissions: 1 per IP per campaign per 30 seconds.
			$campaign_id_raw = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
			$ip              = $this->get_client_ip();
			$rate_key        = 'wpr_rl_' . md5( $ip . '_' . $campaign_id_raw );

			if ( get_transient( $rate_key ) ) {
				wp_send_json_error( array( 'message' => __( 'Please wait before submitting again.', 'wprobo-engage-lite' ) ) );
				return;
			}
			// Transient expires in 30 seconds; set after validation below.
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;

		if ( empty( $campaign_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid campaign ID.', 'wprobo-engage-lite' ) ) );
			return;
		}

		// Collect form data.
		$form_data = array();
		$email     = '';

		// Check if this is a legacy single-email submission or a new multi-field submission.
		if ( isset( $_POST['email'] ) ) {
			// Legacy single email field.
			$email              = sanitize_email( wp_unslash( $_POST['email'] ) );
			$form_data['email'] = $email;
		} elseif ( isset( $_POST['form_data'] ) && is_array( $_POST['form_data'] ) ) {
			// New multi-field submission — unslash the entire array first.
			$raw_form_data = wp_unslash( $_POST['form_data'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-field below

			$form_fields = get_post_meta( $campaign_id, '_wpr_engage_form_fields', true );

			foreach ( $raw_form_data as $key => $value ) {
				$sanitized_key = sanitize_text_field( $key );

				// Determine field type from key pattern (wpr_field_0, wpr_field_1, etc.).
				if ( preg_match( '/^wpr_field_(\d+)$/', $key, $matches ) ) {
					$field_index = $matches[1];

					if ( ! empty( $form_fields[ $field_index ] ) ) {
						$field_type = $form_fields[ $field_index ]['type'] ?? 'text';

						if ( 'email' === $field_type ) {
							$form_data[ $sanitized_key ] = sanitize_email( $value );
							$email                       = $form_data[ $sanitized_key ];
						} elseif ( 'checkbox' === $field_type ) {
							$form_data[ $sanitized_key ] = ! empty( $value ) ? 'yes' : 'no';
						} else {
							$form_data[ $sanitized_key ] = sanitize_text_field( $value );
						}
					} else {
						// Field meta not found — sanitize as text, detect email by format.
						if ( is_email( $value ) ) {
							$form_data[ $sanitized_key ] = sanitize_email( $value );
							if ( empty( $email ) ) {
								$email = $form_data[ $sanitized_key ];
							}
						} else {
							$form_data[ $sanitized_key ] = sanitize_text_field( $value );
						}
					}
				}
			}

			// Fallback: if no email was found via field types, check the 'email' key.
			if ( empty( $email ) && ! empty( $raw_form_data['email'] ) ) {
				$email = sanitize_email( $raw_form_data['email'] );
			}
		}

		/**
		 * Filters the collected form data before it is validated and saved.
		 *
		 * @since 1.0.0
		 * @param array $form_data   The sanitized form field data.
		 * @param int   $campaign_id The campaign ID.
		 */
		$form_data = apply_filters( 'wprobo_engage_lead_form_data', $form_data, $campaign_id );

		/**
		 * Filters the email address extracted from the submission.
		 *
		 * @since 1.0.0
		 * @param string $email       The email address.
		 * @param int    $campaign_id The campaign ID.
		 */
		$email = apply_filters( 'wprobo_engage_lead_email', $email, $campaign_id );

		// Validate email (either from legacy or new format).
		$email = trim( $email );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address.', 'wprobo-engage-lite' ) ) );
			return;
		}

		// Set rate-limit transient now that input is validated (nopriv only).
		if ( ! is_user_logged_in() ) {
			$ip       = $this->get_client_ip();
			$rate_key = 'wpr_rl_' . md5( $ip . '_' . $campaign_id );
			set_transient( $rate_key, 1, 30 );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wprobo_engage_leads';

		/**
		 * Use a stored schema-version flag (set at activation) instead of running
		 * a SHOW COLUMNS query on every form submission.
		 */
		$schema_version = get_option( 'wpr_engage_schema_version', '1.0' );
		$has_form_data  = version_compare( $schema_version, '1.1', '>=' );

		if ( $has_form_data ) {
			// Schema 1.1+ — form_data column exists.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table, no WP API equivalent.
			$result = $wpdb->insert(
				$table_name,
				array(
					'campaign_id'  => $campaign_id,
					'email'        => $email,
					'form_data'    => wp_json_encode( $form_data ),
					'submitted_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s' )
			);
		} else {
			// Legacy schema — no form_data column.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table, no WP API equivalent.
			$result = $wpdb->insert(
				$table_name,
				array(
					'campaign_id'  => $campaign_id,
					'email'        => $email,
					'submitted_at' => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s' )
			);
		}

		if ( false === $result ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WPRobo Engage - Database Error: ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- intentional debug logging
			}

			/**
			 * Fires when a lead could not be saved to the database.
			 *
			 * @since 1.0.0
			 * @param int    $campaign_id The campaign ID.
			 * @param string $email       The submitted email.
			 * @param array  $form_data   The form field data.
			 */
			do_action( 'wprobo_engage_lead_save_failed', $campaign_id, $email, $form_data );

			wp_send_json_error( array( 'message' => __( 'Could not save subscription.', 'wprobo-engage-lite' ) ) );
			return;
		}

		$lead_id = (int) $wpdb->insert_id;

		/**
		 * Fires after a lead has been saved successfully.
		 *
		 * @since 1.0.0
		 * @param int    $lead_id     The newly inserted lead row ID.
		 * @param int    $campaign_id The campaign ID.
		 * @param string $email       The submitted email.
		 * @param array  $form_data   The saved form field data.
		 */
		do_action( 'wprobo_engage_lead_saved', $lead_id, $campaign_id, $email, $form_data );

		// Track conversion in analytics.
		Analytics_Handler::track_event( $campaign_id, 'conversion' );

		wp_send_json_success( array( 'message' => __( 'Subscription successful.', 'wprobo-engage-lite' ) ) );
	}


	/**
	 * Create the custom database table for leads.
	 *
	 * @return void
	 */
	public static function create_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'wprobo_engage_leads';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) UNSIGNED NOT NULL,
			email varchar(100) NOT NULL,
			form_data TEXT,
			submitted_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Returns the most-trustworthy client IP address available.
	 *
	 * @return string IP address string, or empty string if unavailable.
	 */
	private function get_client_ip(): string {
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
				// X-Forwarded-For can be a comma-separated list; use the first.
				$ips = explode( ',', $header_value );
				$ip  = trim( $ips[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}

		// Fall back to REMOTE_ADDR (may be private/local).
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	}

}
