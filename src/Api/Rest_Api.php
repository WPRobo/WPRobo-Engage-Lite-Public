<?php

namespace WPRobo_Engage_Lite\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Rest_Api
 *
 * Manages the custom REST API endpoints for the plugin.
 *
 * @package WPRobo_Engage_Lite\Api
 */
class Rest_Api {

	/**
	 * The namespace for the REST API.
	 */
	const NAMESPACE = 'wprobo-engage/v1';

	/**
	 * Registers the REST API routes.
	 * Hooked into 'rest_api_init'.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/campaigns/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE, // Corresponds to POST, PUT, PATCH
				'callback'            => array( $this, 'update_campaign_settings' ),
				'permission_callback' => array( $this, 'update_settings_permission_check' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/analytics',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_analytics_data' ),
				'permission_callback' => array( $this, 'analytics_permission_check' ),
			)
		);

		// Display rules data endpoints
		register_rest_route(
			self::NAMESPACE,
			'/display-rules/pages',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_pages_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/display-rules/posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/display-rules/post-types',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_post_types_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/display-rules/posts-by-type/(?P<post_type>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_posts_by_type_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
				'args'                => array(
					'post_type' => array(
						'validate_callback' => function ( $param ) {
							return post_type_exists( $param );
						},
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/display-rules/categories',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_categories_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/display-rules/tags',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tags_for_rules' ),
				'permission_callback' => array( $this, 'display_rules_permission_check' ),
			)
		);
	}

	/**
	 * Checks if the current user has permission to update campaign settings.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function update_settings_permission_check( \WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', $request['id'] );
	}

	/**
	 * The callback function to update campaign settings.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function update_campaign_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$campaign_id = $request['id'];
		$params      = $request->get_json_params();

		// --- Define the fields we expect to save ---
		$savable_fields = array(
			'headline'         => 'sanitize_text_field',
			'content'          => 'wp_kses_post',
			'button'           => 'sanitize_text_field',
			'emailPlaceholder' => 'sanitize_text_field',
		);

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $savable_fields ) ) {
				$sanitization_function = $savable_fields[ $key ];
				// Convert camelCase to snake_case for meta key
				$snake_key = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
				$meta_key  = '_wpr_engage_design_' . $snake_key;
				update_post_meta( $campaign_id, $meta_key, call_user_func( $sanitization_function, $value ) );
			}
		}

		// --- Handle Style/Color Fields ---
		$style_fields = array(
			'bgColor'            => 'sanitize_hex_color',
			'headlineColor'      => 'sanitize_hex_color',
			'contentColor'       => 'sanitize_hex_color',
			'buttonBgColor'      => 'sanitize_hex_color',
			'buttonTextColor'    => 'sanitize_hex_color',
			'borderColor'        => 'sanitize_hex_color',
			'boxShadowColor'     => 'sanitize_hex_color',
			'closeBtnColor'      => 'sanitize_hex_color',
			'closeBtnHoverColor' => 'sanitize_hex_color',
			'closeBtnBgColor'    => 'sanitize_text_field', // Can be 'transparent'
			'closeBtnShape'      => 'sanitize_text_field', // square, rounded, circle
		);

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $style_fields ) ) {
				$sanitization_function = $style_fields[ $key ];
				// Convert camelCase to snake_case for meta key
				$snake_key = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
				$meta_key  = '_wpr_engage_style_' . $snake_key;
				update_post_meta( $campaign_id, $meta_key, call_user_func( $sanitization_function, $value ) );
			}
		}

		// --- Handle Numeric Fields ---
		$numeric_fields = array(
			'borderRadius'    => 'absint',
			'borderWidth'     => 'absint',
			'boxShadowX'      => 'intval',
			'boxShadowY'      => 'intval',
			'boxShadowBlur'   => 'absint',
			'boxShadowSpread' => 'intval',
		);

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $numeric_fields ) ) {
				$sanitization_function = $numeric_fields[ $key ];
				// Convert camelCase to snake_case for meta key
				$snake_key = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
				$meta_key  = '_wpr_engage_style_' . $snake_key;
				update_post_meta( $campaign_id, $meta_key, call_user_func( $sanitization_function, $value ) );
			}
		}

		// --- Handle Text/URL Fields ---
		$text_fields = array(
			'bgImageUrl'      => 'esc_url_raw',
			'bgImageRepeat'   => 'sanitize_text_field',
			'bgImagePosition' => 'sanitize_text_field',
			'bgImageSize'     => 'sanitize_text_field',
			'bgMediaType'     => 'sanitize_text_field',
		);

		foreach ( $params as $key => $value ) {
			if ( array_key_exists( $key, $text_fields ) ) {
				$sanitization_function = $text_fields[ $key ];
				// Convert camelCase to snake_case for meta key
				$snake_key = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
				$meta_key  = '_wpr_engage_style_' . $snake_key;
				update_post_meta( $campaign_id, $meta_key, call_user_func( $sanitization_function, $value ) );
			}
		}

		// --- Handle Boolean/Checkbox Fields ---
		if ( isset( $params['boxShadowEnabled'] ) ) {
			update_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_enabled', $params['boxShadowEnabled'] === '1' ? '1' : '0' );
		}

		if ( isset( $params['escToClose'] ) ) {
			update_post_meta( $campaign_id, '_wpr_engage_esc_to_close', $params['escToClose'] === '1' ? '1' : '0' );
		}

		if ( isset( $params['showCloseIcon'] ) ) {
			update_post_meta( $campaign_id, '_wpr_engage_show_close_icon', $params['showCloseIcon'] === '1' ? '1' : '0' );
		}

		// --- Handle Display Rules ---
		if ( isset( $params['displayRules'] ) ) {
			$display_rules = $this->sanitize_display_rules( $params['displayRules'] );
			update_post_meta( $campaign_id, '_wpr_engage_display_rules', $display_rules );
		}

		// --- Handle Rule Groups (Advanced Mode) ---
		if ( isset( $params['ruleGroups'] ) ) {
			$rule_groups = $this->sanitize_rule_groups( $params['ruleGroups'] );
			update_post_meta( $campaign_id, '_wpr_engage_rule_groups', $rule_groups );
		}

		// --- Handle Trigger Settings ---
		if ( isset( $params['triggerType'] ) ) {
			$trigger_type = sanitize_text_field( $params['triggerType'] );
			update_post_meta( $campaign_id, '_wpr_engage_trigger_type', $trigger_type );
		}

		if ( isset( $params['triggerValue'] ) ) {
			$trigger_value = sanitize_text_field( $params['triggerValue'] );
			update_post_meta( $campaign_id, '_wpr_engage_trigger_value', $trigger_value );
		}

		// --- Handle Success Action Settings ---
		if ( isset( $params['successAction'] ) ) {
			$success_action = sanitize_text_field( $params['successAction'] );
			// Only allow 'message' or 'redirect'
			if ( in_array( $success_action, array( 'message', 'redirect' ), true ) ) {
				update_post_meta( $campaign_id, '_wpr_engage_success_action', $success_action );
			}
		}

		if ( isset( $params['successRedirectUrl'] ) ) {
			$success_redirect_url = esc_url_raw( $params['successRedirectUrl'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_redirect_url', $success_redirect_url );
		}

		// --- Handle Success Message Settings ---
		if ( isset( $params['successMessageHeadline'] ) ) {
			$success_message_headline = sanitize_text_field( $params['successMessageHeadline'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_message_headline', $success_message_headline );
		}

		if ( isset( $params['successMessageContent'] ) ) {
			$success_message_content = wp_kses_post( $params['successMessageContent'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_message_content', $success_message_content );
		}

		if ( isset( $params['successAutoClose'] ) ) {
			$success_auto_close = sanitize_text_field( $params['successAutoClose'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_auto_close', $success_auto_close );
		}

		if ( isset( $params['successAutoCloseDelay'] ) ) {
			$success_auto_close_delay = absint( $params['successAutoCloseDelay'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_auto_close_delay', $success_auto_close_delay );
		}

		// --- Handle Redirect Settings ---
		if ( isset( $params['successRedirectDelay'] ) ) {
			$success_redirect_delay = absint( $params['successRedirectDelay'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_redirect_delay', $success_redirect_delay );
		}

		if ( isset( $params['successRedirectNewTab'] ) ) {
			$success_redirect_new_tab = sanitize_text_field( $params['successRedirectNewTab'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_redirect_new_tab', $success_redirect_new_tab );
		}

		// --- Handle Success Icon Settings ---
		if ( isset( $params['successShowIcon'] ) ) {
			update_post_meta( $campaign_id, '_wpr_engage_success_show_icon', $params['successShowIcon'] === '1' ? '1' : '0' );
		} else {
			// If not set, save as '0' to explicitly uncheck
			update_post_meta( $campaign_id, '_wpr_engage_success_show_icon', '0' );
		}

		if ( isset( $params['successIconType'] ) ) {
			$success_icon_type = sanitize_text_field( $params['successIconType'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_icon_type', $success_icon_type );
		}

		if ( isset( $params['successIconColor'] ) ) {
			$success_icon_color = sanitize_hex_color( $params['successIconColor'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_icon_color', $success_icon_color );
		}

		// --- Handle Success Message Styling ---
		if ( isset( $params['successTitleColor'] ) ) {
			$success_title_color = sanitize_hex_color( $params['successTitleColor'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_title_color', $success_title_color );
		}

		if ( isset( $params['successContentColor'] ) ) {
			$success_content_color = sanitize_hex_color( $params['successContentColor'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_content_color', $success_content_color );
		}

		if ( isset( $params['successTitleFontSize'] ) ) {
			$success_title_font_size = absint( $params['successTitleFontSize'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_title_font_size', $success_title_font_size );
		}

		if ( isset( $params['successContentFontSize'] ) ) {
			$success_content_font_size = absint( $params['successContentFontSize'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_content_font_size', $success_content_font_size );
		}

		if ( isset( $params['successTitleFontWeight'] ) ) {
			$success_title_font_weight = sanitize_text_field( $params['successTitleFontWeight'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_title_font_weight', $success_title_font_weight );
		}

		if ( isset( $params['successContentFontWeight'] ) ) {
			$success_content_font_weight = sanitize_text_field( $params['successContentFontWeight'] );
			update_post_meta( $campaign_id, '_wpr_engage_success_content_font_weight', $success_content_font_weight );
		}

		/**
		 * Fires after all campaign settings have been saved via the REST API.
		 *
		 * Use this hook to run custom logic after a campaign is updated, such
		 * as clearing plugin-level caches or triggering notifications.
		 *
		 * @since 1.0.0
		 * @param int   $campaign_id The post ID of the saved campaign.
		 * @param array $params      The sanitized parameters that were saved.
		 */
		do_action( 'wprobo_engage_campaign_settings_saved', $campaign_id, $params );

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Sanitizes display rules data.
	 *
	 * @param array $rules The raw display rules data.
	 * @return array The sanitized display rules.
	 */
	private function sanitize_display_rules( $rules ): array {
		if ( ! is_array( $rules ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) || ! isset( $rule['type'] ) ) {
				continue;
			}

			$sanitized_rule = array(
				'type'   => sanitize_text_field( $rule['type'] ),
				'action' => isset( $rule['action'] ) ? sanitize_text_field( $rule['action'] ) : 'show',
			);

			// Sanitize the value based on rule type
			if ( isset( $rule['value'] ) ) {
				$type = $rule['type'];

				// Integer values
				if ( in_array( $type, array( 'specific_page', 'specific_post' ), true ) ) {
					$sanitized_rule['value'] = absint( $rule['value'] );
				}
				// Array values (for multiple selections)
				elseif ( in_array( $type, array( 'user_role', 'user_not_role', 'device_browser', 'device_os', 'referral_domain' ), true ) ) {
					if ( is_array( $rule['value'] ) ) {
						$sanitized_rule['value'] = array_map( 'sanitize_text_field', $rule['value'] );
					} else {
						$sanitized_rule['value'] = sanitize_text_field( $rule['value'] );
					}
				}
				// UTM parameters (associative array)
				elseif ( $type === 'referral_utm' ) {
					if ( is_array( $rule['value'] ) ) {
						$sanitized_rule['value'] = array(
							'source'   => isset( $rule['value']['source'] ) ? sanitize_text_field( $rule['value']['source'] ) : '',
							'medium'   => isset( $rule['value']['medium'] ) ? sanitize_text_field( $rule['value']['medium'] ) : '',
							'campaign' => isset( $rule['value']['campaign'] ) ? sanitize_text_field( $rule['value']['campaign'] ) : '',
							'match'    => isset( $rule['value']['match'] ) ? sanitize_text_field( $rule['value']['match'] ) : 'all',
						);
					}
				}
				// Text values (default)
				else {
					$sanitized_rule['value'] = sanitize_text_field( $rule['value'] );
				}
			}

			$sanitized[] = $sanitized_rule;
		}

		return $sanitized;
	}

	/**
	 * Sanitizes rule groups data.
	 *
	 * @param array $groups The raw rule groups data.
	 * @return array The sanitized rule groups.
	 */
	private function sanitize_rule_groups( $groups ): array {
		if ( ! is_array( $groups ) ) {
			return array();
		}

		$sanitized = array();

		foreach ( $groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$sanitized_group = array(
				'action' => isset( $group['action'] ) ? sanitize_text_field( $group['action'] ) : 'show',
				'logic'  => isset( $group['logic'] ) ? sanitize_text_field( $group['logic'] ) : 'and',
				'rules'  => array(),
			);

			// Sanitize rules within the group
			if ( isset( $group['rules'] ) && is_array( $group['rules'] ) ) {
				foreach ( $group['rules'] as $rule ) {
					if ( ! is_array( $rule ) || ! isset( $rule['type'] ) ) {
						continue;
					}

					$sanitized_rule = array(
						'type' => sanitize_text_field( $rule['type'] ),
					);

					// Sanitize the value based on rule type (same logic as display_rules)
					if ( isset( $rule['value'] ) ) {
						$type = $rule['type'];

						// Integer values
						if ( in_array( $type, array( 'specific_page', 'specific_post', 'time_on_site', 'scroll_depth', 'page_views_session', 'page_views_lifetime' ), true ) ) {
							$sanitized_rule['value'] = absint( $rule['value'] );
						}
						// Array values (for multiple selections)
						elseif ( in_array( $type, array( 'user_role', 'user_not_role', 'device_browser', 'device_os', 'referral_domain' ), true ) ) {
							if ( is_array( $rule['value'] ) ) {
								$sanitized_rule['value'] = array_map( 'sanitize_text_field', $rule['value'] );
							} else {
								$sanitized_rule['value'] = sanitize_text_field( $rule['value'] );
							}
						}
						// Text values (default)
						else {
							$sanitized_rule['value'] = sanitize_text_field( $rule['value'] );
						}
					}

					$sanitized_group['rules'][] = $sanitized_rule;
				}
			}

			$sanitized[] = $sanitized_group;
		}

		return $sanitized;
	}

	/**
	 * Checks if the current user has permission to view analytics data.
	 *
	 * @return bool
	 */
	public function analytics_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get analytics data for the dashboard chart.
	 * Returns impressions and conversions per day for the last 30 days.
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_analytics_data( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$days = 30;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no WP API equivalent.
		// Get data for the last 30 days.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					DATE(event_date) as date,
					event_type,
					COUNT(*) as count
				FROM {$wpdb->prefix}wprobo_engage_analytics
				WHERE event_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
				GROUP BY DATE(event_date), event_type
				ORDER BY date ASC",
				$days
			)
		);
		// phpcs:enable

		// Format data for Chart.js
		$dates       = array();
		$impressions = array();
		$conversions = array();

		// Get the last 30 days
		for ( $i = 29; $i >= 0; $i-- ) {
			$date                 = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$dates[]              = $date;
			$impressions[ $date ] = 0;
			$conversions[ $date ] = 0;
		}

		// Fill in the actual data
		foreach ( $results as $row ) {
			if ( 'impression' === $row->event_type ) {
				$impressions[ $row->date ] = (int) $row->count;
			} elseif ( 'conversion' === $row->event_type ) {
				$conversions[ $row->date ] = (int) $row->count;
			}
		}

		// Convert associative arrays to indexed arrays for Chart.js
		$impression_values = array_values( $impressions );
		$conversion_values = array_values( $conversions );

		return new \WP_REST_Response(
			array(
				'labels'      => $dates,
				'impressions' => $impression_values,
				'conversions' => $conversion_values,
			),
			200
		);
	}

	/**
	 * Permission check for display rules data endpoints.
	 *
	 * @return bool
	 */
	public function display_rules_permission_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get all published pages for display rules dropdown.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_pages_for_rules(): \WP_REST_Response {
		$pages = get_pages(
			array(
				'post_status' => 'publish',
				'number'      => 1000, // Limit to prevent performance issues
			)
		);

		$formatted_pages = array();
		foreach ( $pages as $page ) {
			$formatted_pages[] = array(
				'id'   => $page->ID,
				'text' => $page->post_title,
			);
		}

		return new \WP_REST_Response( $formatted_pages, 200 );
	}

	/**
	 * Get all published posts for display rules dropdown.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_posts_for_rules(): \WP_REST_Response {
		$posts = get_posts(
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$formatted_posts = array();
		foreach ( $posts as $post ) {
			$formatted_posts[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title,
			);
		}

		return new \WP_REST_Response( $formatted_posts, 200 );
	}

	/**
	 * Get all public custom post types for display rules dropdown.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_post_types_for_rules(): \WP_REST_Response {
		$post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);

		$formatted_types = array();
		foreach ( $post_types as $post_type ) {
			// Skip our own campaign post type
			if ( 'wpr_campaign' === $post_type->name ) {
				continue;
			}

			$formatted_types[] = array(
				'slug'  => $post_type->name,
				'label' => $post_type->label,
			);
		}

		return new \WP_REST_Response( $formatted_types, 200 );
	}

	/**
	 * Get posts by post type for display rules dropdown.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_posts_by_type_for_rules( \WP_REST_Request $request ): \WP_REST_Response {
		$post_type = $request['post_type'];

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$formatted_posts = array();
		foreach ( $posts as $post ) {
			$formatted_posts[] = array(
				'id'   => $post->ID,
				'text' => $post->post_title,
			);
		}

		return new \WP_REST_Response( $formatted_posts, 200 );
	}

	/**
	 * Get all categories for display rules dropdown.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_categories_for_rules(): \WP_REST_Response {
		$categories = get_categories(
			array(
				'hide_empty' => false,
				'number'     => 1000,
			)
		);

		$formatted_categories = array();
		foreach ( $categories as $category ) {
			$formatted_categories[] = array(
				'id'   => $category->slug,
				'text' => $category->name,
			);
		}

		return new \WP_REST_Response( $formatted_categories, 200 );
	}

	/**
	 * Get all tags for display rules dropdown.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_tags_for_rules(): \WP_REST_Response {
		$tags = get_tags(
			array(
				'hide_empty' => false,
				'number'     => 1000,
			)
		);

		$formatted_tags = array();
		foreach ( $tags as $tag ) {
			$formatted_tags[] = array(
				'id'   => $tag->slug,
				'text' => $tag->name,
			);
		}

		return new \WP_REST_Response( $formatted_tags, 200 );
	}
}
