<?php

namespace WPRobo_Engage_Lite\Public;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

use WPRobo_Engage_Lite\Api\Rest_Api;

class Enqueue {

	public function enqueue_admin_assets(): void {
		$current_screen = get_current_screen();

		$asset_ver = function ( string $relative_path ): string {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$full_path = WPROBO_ENGAGE_LITE_PATH . $relative_path;
				clearstatcache( true, $full_path );
				return file_exists( $full_path ) ? (string) filemtime( $full_path ) : WPROBO_ENGAGE_LITE_VERSION;
			}
			return WPROBO_ENGAGE_LITE_VERSION;
		};

		wp_enqueue_style(
			'wprobo-engage-admin',
			WPROBO_ENGAGE_LITE_URL . 'assets/css/admin.css',
			array(),
			$asset_ver( 'assets/css/admin.css' )
		);

		wp_enqueue_style(
			'wprobo-engage-pro-upsell',
			WPROBO_ENGAGE_LITE_URL . 'assets/css/admin-pro-upsell.css',
			array(),
			$asset_ver( 'assets/css/admin-pro-upsell.css' )
		);

		// Load Chart.js and dashboard assets on the dashboard page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wprobo-engage' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_script(
				'chartjs',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/vendor/chart.umd.min.js',
				array(),
				'4.4.1',
				true
			);

			wp_enqueue_script(
				'wprobo-engage-dashboard',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/dashboard.js',
				array( 'jquery', 'chartjs' ),
				$asset_ver( 'assets/js/dashboard.js' ),
				true
			);

			wp_localize_script(
				'wprobo-engage-dashboard',
				'WPRoboDashboard',
				array(
					'apiUrl'           => rest_url( Rest_Api::NAMESPACE . '/analytics' ),
					'nonce'            => wp_create_nonce( 'wp_rest' ),
					'impressionsLabel' => esc_html__( 'Impressions', 'wprobo-engage-lite' ),
					'conversionsLabel' => esc_html__( 'Conversions', 'wprobo-engage-lite' ),
					'errorMessage'     => esc_html__( 'Failed to load analytics data. Please refresh the page.', 'wprobo-engage-lite' ),
				)
			);
		}

		// Load import/export tools assets on the Tools admin page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wprobo-engage-tools' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style(
				'wprobo-engage-admin-tools',
				WPROBO_ENGAGE_LITE_URL . 'assets/css/admin-tools.css',
				array(),
				$asset_ver( 'assets/css/admin-tools.css' )
			);

			wp_enqueue_script(
				'wprobo-engage-admin-tools',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/admin-tools.js',
				array(),
				$asset_ver( 'assets/js/admin-tools.js' ),
				true
			);

			wp_localize_script(
				'wprobo-engage-admin-tools',
				'WPRoboTools',
				array(
					'i18n' => array(
						'notJsonFile'      => esc_html__( 'Please select a .json file.', 'wprobo-engage-lite' ),
						'notExportFile'    => esc_html__( 'This file does not look like a WPRobo Engage campaign export.', 'wprobo-engage-lite' ),
						'campaign'         => esc_html__( 'campaign', 'wprobo-engage-lite' ),
						'campaigns'        => esc_html__( 'campaigns', 'wprobo-engage-lite' ),
						'readyToImport'    => esc_html__( 'Ready to import: ', 'wprobo-engage-lite' ),
						'untitledCampaign' => esc_html__( 'Untitled Campaign', 'wprobo-engage-lite' ),
						/* translators: %s: file name being imported */
						'confirmImport'    => esc_html__( 'Import %s?\n\nThis will create new campaigns as drafts. Existing campaigns will not be overwritten.', 'wprobo-engage-lite' ),
						'invalidJson'      => esc_html__( 'Invalid JSON — the file could not be parsed.', 'wprobo-engage-lite' ),
					),
				)
			);
		}

		// Load template library assets on the template selection page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wprobo-engage-templates' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			wp_enqueue_style(
				'wprobo-engage-admin-templates',
				WPROBO_ENGAGE_LITE_URL . 'assets/css/admin-templates.css',
				array(),
				$asset_ver( 'assets/css/admin-templates.css' )
			);

			wp_enqueue_script(
				'wprobo-engage-admin-templates',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/admin-templates.js',
				array(),
				$asset_ver( 'assets/js/admin-templates.js' ),
				true
			);

			wp_localize_script(
				'wprobo-engage-admin-templates',
				'WPRoboTemplates',
				array(
					'i18n' => array(
						'emailPlaceholder' => esc_html__( 'you@example.com', 'wprobo-engage-lite' ),
					),
				)
			);
		}

		// Load builder assets on the campaign edit screen.
		if ( isset( $current_screen->id ) && 'wpr_campaign' === $current_screen->id ) {
			wp_enqueue_style( 'wp-color-picker' );

			wp_enqueue_style(
				'wprobo-engage-public',
				WPROBO_ENGAGE_LITE_URL . 'assets/css/public.css',
				array(),
				$asset_ver( 'assets/css/public.css' )
			);

			wp_enqueue_style(
				'select2',
				WPROBO_ENGAGE_LITE_URL . 'assets/css/vendor/select2.min.css',
				array(),
				'4.0.13'
			);

			wp_enqueue_script(
				'select2',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/vendor/select2.min.js',
				array( 'jquery' ),
				'4.0.13',
				true
			);

			wp_enqueue_script(
				'wprobo-engage-admin',
				WPROBO_ENGAGE_LITE_URL . 'assets/js/admin.js',
				array( 'jquery', 'wp-api', 'wp-color-picker', 'select2' ),
				$asset_ver( 'assets/js/admin.js' ),
				true
			);

			global $post;
			$campaign_id = isset( $post->ID ) ? $post->ID : 0;

			wp_localize_script(
				'wprobo-engage-admin',
				'WPRoboEngage',
				array(
					'apiUrl'                     => rest_url( Rest_Api::NAMESPACE . '/campaigns/' ),
					'displayRulesPagesUrl'       => rest_url( Rest_Api::NAMESPACE . '/display-rules/pages' ),
					'displayRulesPostsUrl'       => rest_url( Rest_Api::NAMESPACE . '/display-rules/posts' ),
					'displayRulesPostTypesUrl'   => rest_url( Rest_Api::NAMESPACE . '/display-rules/post-types' ),
					'displayRulesPostsByTypeUrl' => rest_url( Rest_Api::NAMESPACE . '/display-rules/posts-by-type/' ),
					'displayRulesCategoriesUrl'  => rest_url( Rest_Api::NAMESPACE . '/display-rules/categories' ),
					'displayRulesTagsUrl'        => rest_url( Rest_Api::NAMESPACE . '/display-rules/tags' ),
					'nonce'                      => wp_create_nonce( 'wp_rest' ),
					'campaignId'                 => $campaign_id,
					'i18n'                       => array(
						'revertConfirm'     => esc_html__( 'Revert all unsaved changes to last saved state?', 'wprobo-engage-lite' ),
						'changesReverted'   => esc_html__( 'Changes reverted to last saved state', 'wprobo-engage-lite' ),
						'noRuleGroups'      => esc_html__( 'No rules added yet.', 'wprobo-engage-lite' ),
						'addTargetingRule'  => esc_html__( 'Add Rule', 'wprobo-engage-lite' ),
						'noRulesConfigured' => esc_html__( 'No rules configured. Campaign will show everywhere.', 'wprobo-engage-lite' ),
					),
				)
			);
		}
	}

	/**
	 * Enqueues scripts and styles for the public-facing website.
	 */
	public function enqueue_public_assets(): void {
		$asset_ver = function ( string $relative_path ): string {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$full_path = WPROBO_ENGAGE_LITE_PATH . $relative_path;
				clearstatcache( true, $full_path );
				return file_exists( $full_path ) ? (string) filemtime( $full_path ) : WPROBO_ENGAGE_LITE_VERSION;
			}
			return WPROBO_ENGAGE_LITE_VERSION;
		};

		wp_enqueue_style(
			'wprobo-engage-public',
			WPROBO_ENGAGE_LITE_URL . 'assets/css/public.css',
			array(),
			$asset_ver( 'assets/css/public.css' )
		);

		wp_enqueue_script(
			'wprobo-engage-public',
			WPROBO_ENGAGE_LITE_URL . 'assets/js/public.js',
			array( 'jquery' ),
			$asset_ver( 'assets/js/public.js' ),
			true
		);

		// Build per-campaign settings for the frontend.
		$campaigns_data = array();

		$active_campaigns = new \WP_Query(
			array(
				'post_type'      => 'wpr_campaign',
				'posts_per_page' => -1,
				'meta_key'       => '_wpr_engage_campaign_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- standard WP_Query parameter for filtering active campaigns.
				'meta_value'     => 'active', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- standard WP_Query parameter for filtering active campaigns.
			)
		);

		while ( $active_campaigns->have_posts() ) {
			$active_campaigns->the_post();
			$campaign_id = get_the_ID();

			$trigger_type             = get_post_meta( $campaign_id, '_wpr_engage_trigger_type', true );
			$trigger_value            = get_post_meta( $campaign_id, '_wpr_engage_trigger_value', true );
			$success_action           = get_post_meta( $campaign_id, '_wpr_engage_success_action', true ) ?: 'message';
			$success_redirect_url     = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_url', true );
			$success_message_headline = get_post_meta( $campaign_id, '_wpr_engage_success_message_headline', true ) ?: __( 'Thank you!', 'wprobo-engage-lite' );
			$success_message_content  = get_post_meta( $campaign_id, '_wpr_engage_success_message_content', true ) ?: __( 'Your subscription has been confirmed.', 'wprobo-engage-lite' );
			$success_auto_close       = get_post_meta( $campaign_id, '_wpr_engage_success_auto_close', true );
			$success_auto_close_delay_raw = get_post_meta( $campaign_id, '_wpr_engage_success_auto_close_delay', true );
			$success_auto_close_delay = ( '' !== $success_auto_close_delay_raw ) ? $success_auto_close_delay_raw : '5';
			$success_redirect_delay_raw = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_delay', true );
			$success_redirect_delay   = ( '' !== $success_redirect_delay_raw ) ? $success_redirect_delay_raw : '3';
			$success_redirect_new_tab = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_new_tab', true );

			$campaigns_data[ $campaign_id ] = array(
				'trigger_type'             => $trigger_type,
				'trigger_value'            => $trigger_value,
				'success_action'           => $success_action,
				'success_redirect_url'     => $success_redirect_url,
				'success_message_headline' => $success_message_headline,
				'success_message_content'  => $success_message_content,
				'success_auto_close'       => $success_auto_close,
				'success_auto_close_delay' => $success_auto_close_delay,
				'success_redirect_delay'   => $success_redirect_delay,
				'success_redirect_new_tab' => $success_redirect_new_tab,
				'ab_test_enabled'          => false,
				'timer_settings'           => array(),
			);
		}

		wp_reset_postdata();

		$first = ! empty( $campaigns_data ) ? reset( $campaigns_data ) : array();

		wp_localize_script(
			'wprobo-engage-public',
			'WPREngagePublic',
			array(
				'ajax_url'                 => admin_url( 'admin-ajax.php' ),
				'nonce'                    => wp_create_nonce( 'wpr_engage_nonce' ),
				'campaigns'                => $campaigns_data,
				'trigger_type'             => isset( $first['trigger_type'] ) ? $first['trigger_type'] : '',
				'trigger_value'            => isset( $first['trigger_value'] ) ? $first['trigger_value'] : '',
				'success_action'           => isset( $first['success_action'] ) ? $first['success_action'] : 'message',
				'success_redirect_url'     => isset( $first['success_redirect_url'] ) ? $first['success_redirect_url'] : '',
				'success_message_headline' => isset( $first['success_message_headline'] ) ? $first['success_message_headline'] : __( 'Thank you!', 'wprobo-engage-lite' ),
				'success_message_content'  => isset( $first['success_message_content'] ) ? $first['success_message_content'] : __( 'Your subscription has been confirmed.', 'wprobo-engage-lite' ),
				'success_auto_close'       => isset( $first['success_auto_close'] ) ? $first['success_auto_close'] : '',
				'success_auto_close_delay' => isset( $first['success_auto_close_delay'] ) ? $first['success_auto_close_delay'] : '5',
				'success_redirect_delay'   => isset( $first['success_redirect_delay'] ) ? $first['success_redirect_delay'] : '3',
				'success_redirect_new_tab' => isset( $first['success_redirect_new_tab'] ) ? $first['success_redirect_new_tab'] : '',
				'display_rules'            => array(),
				'timer_settings'           => array(),
				'ab_test_enabled'          => false,
				'i18n'                     => array(
					'submitting'    => esc_html__( 'Submitting...', 'wprobo-engage-lite' ),
					'anError'       => esc_html__( 'An error occurred.', 'wprobo-engage-lite' ),
					'networkError'  => esc_html__( 'A network error occurred. Please try again.', 'wprobo-engage-lite' ),
					'redirectingIn' => esc_html__( 'Redirecting in', 'wprobo-engage-lite' ),
					'closingIn'     => esc_html__( 'Closing in', 'wprobo-engage-lite' ),
					'second'        => esc_html__( 'second', 'wprobo-engage-lite' ),
					'seconds'       => esc_html__( 'seconds', 'wprobo-engage-lite' ),
				),
			)
		);
	}
}
