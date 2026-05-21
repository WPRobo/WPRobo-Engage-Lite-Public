<?php

namespace WPRobo_Engage_Lite\Public;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Display_Engine
 *
 * Handles the logic for displaying campaigns on the frontend of the website.
 *
 * @package WPRobo_Engage_Lite\Public
 */
class Display_Engine {

	/**
	 * The rule engine instance.
	 *
	 * @var Rule_Engine
	 */
	private $rule_engine;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->rule_engine = new Rule_Engine();
	}

	/**
	 * Main entry point, hooked into 'wp_footer'.
	 * Finds and renders active campaigns that match display rules.
	 * Supports displaying multiple campaigns of different types simultaneously.
	 *
	 * @return void
	 */
	public function run(): void {
		// Save the current page's post object BEFORE the campaign query overrides it.
		global $post;
		$original_post = $post;

		$active_campaigns = new \WP_Query(
			array(
				'post_type'      => 'wpr_campaign',
				'posts_per_page' => -1,
				'meta_key'       => '_wpr_engage_campaign_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => 'active', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! $active_campaigns->have_posts() ) {
			return; // No active campaigns found.
		}

		// Track which campaign types have been rendered to show only one per type
		$rendered_types = array();

		// Loop through all active campaigns and render one of each type that matches display rules
		while ( $active_campaigns->have_posts() ) {
			$active_campaigns->the_post();
			$campaign_id   = get_the_ID();
			$campaign_type = get_post_meta( $campaign_id, '_wpr_engage_campaign_type', true );

			// Skip if we've already rendered a campaign of this type
			if ( isset( $rendered_types[ $campaign_type ] ) ) {
				continue;
			}

			// Restore the original page post so display rules evaluate against
			// the actual page being viewed, not the campaign CPT.
			$post = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			// Check if this campaign's display rules match the current page
			if ( $this->should_display_campaign( $campaign_id ) ) {
				$this->render_campaign( $campaign_id );
				$rendered_types[ $campaign_type ] = true;
			}
		}

		// Fully restore original post data.
		$post = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wp_reset_postdata();
	}

	/**
	 * Determines if a campaign should be displayed based on its display rules or rule groups.
	 *
	 * @param int $campaign_id The ID of the campaign to check.
	 * @return bool True if the campaign should be displayed, false otherwise.
	 */
	private function should_display_campaign( int $campaign_id ): bool {
		// Check if advanced rule groups are being used
		$rule_groups = get_post_meta( $campaign_id, '_wpr_engage_rule_groups', true );

		// If rule groups exist, use group evaluation logic
		if ( ! empty( $rule_groups ) && is_array( $rule_groups ) ) {
			return $this->evaluate_rule_groups( $rule_groups );
		}

		// Otherwise, use simple display rules
		$display_rules = get_post_meta( $campaign_id, '_wpr_engage_display_rules', true );

		// If no rules are set, show the campaign everywhere (default behavior)
		if ( empty( $display_rules ) || ! is_array( $display_rules ) ) {
			return true;
		}

		$show_rules = array();
		$hide_rules = array();

		// Separate rules into show and hide groups
		foreach ( $display_rules as $rule ) {
			if ( ! isset( $rule['type'] ) ) {
				continue;
			}

			$action = isset( $rule['action'] ) ? $rule['action'] : 'show';

			if ( 'show' === $action ) {
				$show_rules[] = $rule;
			} else {
				$hide_rules[] = $rule;
			}
		}

		// First check hide rules - if any match, don't show the campaign
		foreach ( $hide_rules as $rule ) {
			if ( $this->evaluate_rule( $rule ) ) {
				return false;
			}
		}

		// If there are show rules, at least one must match
		if ( ! empty( $show_rules ) ) {
			foreach ( $show_rules as $rule ) {
				if ( $this->evaluate_rule( $rule ) ) {
					return true;
				}
			}
			// No show rules matched
			return false;
		}

		// No show rules and no hide rules matched, so display it
		return true;
	}

	/**
	 * Evaluates rule groups (advanced mode with AND/OR logic).
	 *
	 * @param array $groups Array of rule groups.
	 * @return bool True if campaign should be displayed.
	 */
	private function evaluate_rule_groups( array $groups ): bool {
		$show_groups = array();
		$hide_groups = array();

		// Separate groups into show and hide
		foreach ( $groups as $group ) {
			$action = isset( $group['action'] ) ? $group['action'] : 'show';
			if ( 'show' === $action ) {
				$show_groups[] = $group;
			} else {
				$hide_groups[] = $group;
			}
		}

		// First check hide groups - if any group matches, don't show
		foreach ( $hide_groups as $group ) {
			if ( $this->evaluate_single_group( $group ) ) {
				return false;
			}
		}

		// If there are show groups, at least one must match (OR between groups)
		if ( ! empty( $show_groups ) ) {
			foreach ( $show_groups as $group ) {
				if ( $this->evaluate_single_group( $group ) ) {
					return true;
				}
			}
			// No show groups matched
			return false;
		}

		// No show groups and no hide groups matched
		return true;
	}

	/**
	 * Evaluates a single rule group with AND/OR logic.
	 *
	 * @param array $group Rule group with logic and rules.
	 * @return bool True if the group matches.
	 */
	private function evaluate_single_group( array $group ): bool {
		if ( ! isset( $group['rules'] ) || ! is_array( $group['rules'] ) || empty( $group['rules'] ) ) {
			return true; // Empty group means no restriction
		}

		$logic = isset( $group['logic'] ) ? $group['logic'] : 'and';
		$rules = $group['rules'];

		if ( 'and' === $logic ) {
			// ALL rules must match (AND logic)
			foreach ( $rules as $rule ) {
				if ( ! $this->evaluate_rule( $rule ) ) {
					return false; // One rule failed, group doesn't match
				}
			}
			return true; // All rules passed
		} else {
			// ANY rule must match (OR logic)
			foreach ( $rules as $rule ) {
				if ( $this->evaluate_rule( $rule ) ) {
					return true; // One rule passed, group matches
				}
			}
			return false; // No rules passed
		}
	}

	/**
	 * Evaluates a single display rule against the current page context.
	 *
	 * @param array $rule The rule to evaluate.
	 * @return bool True if the rule matches the current context.
	 */
	private function evaluate_rule( array $rule ): bool {
		return $this->rule_engine->evaluate_rule( $rule );
	}

	/**
	 * Renders the HTML for a specific campaign using a template file.
	 *
	 * @param int $campaign_id The ID of the campaign to render.
	 * @return void
	 */
	private function render_campaign( int $campaign_id ): void {
		// Get campaign type to determine which template to load
		$campaign_type = get_post_meta( $campaign_id, '_wpr_engage_campaign_type', true );

		// Fetch all the data the template will need
		$data = array(
			'campaign_id'                 => $campaign_id,
			'headline'                    => get_post_meta( $campaign_id, '_wpr_engage_design_headline', true ),
			'content'                     => get_post_meta( $campaign_id, '_wpr_engage_design_content', true ),
			'button'                      => get_post_meta( $campaign_id, '_wpr_engage_design_button', true ),
			'email_placeholder'           => get_post_meta( $campaign_id, '_wpr_engage_design_email_placeholder', true ),
			'bg_color'                    => get_post_meta( $campaign_id, '_wpr_engage_style_bg_color', true ),
			'headline_color'              => get_post_meta( $campaign_id, '_wpr_engage_style_headline_color', true ),
			'content_color'               => get_post_meta( $campaign_id, '_wpr_engage_style_content_color', true ),
			'button_bg_color'             => get_post_meta( $campaign_id, '_wpr_engage_style_button_bg_color', true ),
			'button_text_color'           => get_post_meta( $campaign_id, '_wpr_engage_style_button_text_color', true ),
			'border_radius'               => get_post_meta( $campaign_id, '_wpr_engage_style_border_radius', true ),
			'border_width'                => get_post_meta( $campaign_id, '_wpr_engage_style_border_width', true ),
			'border_color'                => get_post_meta( $campaign_id, '_wpr_engage_style_border_color', true ),
			'box_shadow_enabled'          => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_enabled', true ),
			'box_shadow_color'            => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_color', true ),
			'box_shadow_x'                => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_x', true ),
			'box_shadow_y'                => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_y', true ),
			'box_shadow_blur'             => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_blur', true ),
			'box_shadow_spread'           => get_post_meta( $campaign_id, '_wpr_engage_style_box_shadow_spread', true ),
			'bg_image_url'                => get_post_meta( $campaign_id, '_wpr_engage_style_bg_image_url', true ),
			'bg_image_repeat'             => get_post_meta( $campaign_id, '_wpr_engage_style_bg_image_repeat', true ),
			'bg_image_position'           => get_post_meta( $campaign_id, '_wpr_engage_style_bg_image_position', true ),
			'bg_image_size'               => get_post_meta( $campaign_id, '_wpr_engage_style_bg_image_size', true ),
			'bg_media_type'               => get_post_meta( $campaign_id, '_wpr_engage_style_bg_media_type', true ),
			'close_btn_color'             => get_post_meta( $campaign_id, '_wpr_engage_style_close_btn_color', true ),
			'close_btn_hover_color'       => get_post_meta( $campaign_id, '_wpr_engage_style_close_btn_hover_color', true ),
			'close_btn_bg_color'          => get_post_meta( $campaign_id, '_wpr_engage_style_close_btn_bg_color', true ),
			'close_btn_shape'             => get_post_meta( $campaign_id, '_wpr_engage_style_close_btn_shape', true ),
			'esc_to_close'                => get_post_meta( $campaign_id, '_wpr_engage_esc_to_close', true ),
			'show_close_icon'             => get_post_meta( $campaign_id, '_wpr_engage_show_close_icon', true ),
			'form_type'                   => get_post_meta( $campaign_id, '_wpr_engage_form_type', true ) ?: 'native',
			'form_fields'                 => get_post_meta( $campaign_id, '_wpr_engage_form_fields', true ),
			'success_message_headline'    => get_post_meta( $campaign_id, '_wpr_engage_success_message_headline', true ) ?: __( 'Thank you!', 'wprobo-engage-lite' ),
			'success_message_content'     => get_post_meta( $campaign_id, '_wpr_engage_success_message_content', true ) ?: __( 'Your subscription has been confirmed.', 'wprobo-engage-lite' ),
			'success_show_icon'           => get_post_meta( $campaign_id, '_wpr_engage_success_show_icon', true ) !== '' ? get_post_meta( $campaign_id, '_wpr_engage_success_show_icon', true ) : '1',
			'success_icon_type'           => get_post_meta( $campaign_id, '_wpr_engage_success_icon_type', true ) ?: 'checkmark',
			'success_icon_color'          => get_post_meta( $campaign_id, '_wpr_engage_success_icon_color', true ) ?: '#059669',
			'success_title_color'         => get_post_meta( $campaign_id, '_wpr_engage_success_title_color', true ) ?: '#059669',
			'success_content_color'       => get_post_meta( $campaign_id, '_wpr_engage_success_content_color', true ) ?: '#4B5563',
			'success_title_font_size'     => get_post_meta( $campaign_id, '_wpr_engage_success_title_font_size', true ) ?: '24',
			'success_content_font_size'   => get_post_meta( $campaign_id, '_wpr_engage_success_content_font_size', true ) ?: '16',
			'success_title_font_weight'   => get_post_meta( $campaign_id, '_wpr_engage_success_title_font_weight', true ) ?: 'bold',
			'success_content_font_weight' => get_post_meta( $campaign_id, '_wpr_engage_success_content_font_weight', true ) ?: 'normal',
		);

		$data['ab_test_enabled'] = false;

		// For floating bar, add position setting
		if ( 'floating-bar' === $campaign_type ) {
			$data['position'] = get_post_meta( $campaign_id, '_wpr_engage_bar_position', true );
		}

		// For slide-in, add position setting
		if ( 'slide-in' === $campaign_type ) {
			$data['position'] = get_post_meta( $campaign_id, '_wpr_engage_slide_position', true );
		}

		// Determine which template file to load based on campaign type
		$template_file = 'campaign-popup.php'; // Default to popup
		if ( 'floating-bar' === $campaign_type ) {
			$template_file = 'campaign-floating-bar.php';
		} elseif ( 'slide-in' === $campaign_type ) {
			$template_file = 'campaign-slide-in.php';
		}

		$template_path = WPROBO_ENGAGE_LITE_PATH . 'templates/' . $template_file;

		if ( file_exists( $template_path ) ) {
			// This makes the $data array available inside the template file as individual variables
			extract( $data );
			include $template_path;
		}
	}

}
