<?php

namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Meta_Box
 *
 * Handles the creation and data handling for the campaign settings meta box.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Meta_Box {

	/**
	 * Adds the meta box to the 'wpr_campaign' post type.
	 * Hooked into 'add_meta_boxes'.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'wprobo_engage_settings',
			esc_html__( 'Campaign Settings', 'wprobo-engage-lite' ),
			array( $this, 'render_meta_box_html' ),
			'wpr_campaign',
			'normal',
			'high'
		);
	}

	/**
	 * Renders the HTML content for the meta box.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return void
	 */
	public function render_meta_box_html( \WP_Post $post ): void {
		// Add a nonce field for security.
		wp_nonce_field( 'wprobo_engage_save_meta_box_data', 'wprobo_engage_meta_box_nonce' );

		// Batch-fetch ALL meta for this post in ONE database query.
		// Individual get_post_meta( $id, $key, true ) calls hit cache after this.
		$all_meta = get_post_meta( $post->ID );

		// Helper: extract a single meta value with an optional default.
		// Handles maybe_unserialize for serialized arrays.
		$get = function ( string $key, $default = '' ) use ( $all_meta ) {
			if ( ! isset( $all_meta[ $key ][0] ) ) {
				return $default;
			}
			return maybe_unserialize( $all_meta[ $key ][0] );
		};

		// Get existing values.
		$campaign_type     = $get( '_wpr_engage_campaign_type' );
		$campaign_status   = $get( '_wpr_engage_campaign_status' );
		$headline          = $get( '_wpr_engage_design_headline' );
		$content           = $get( '_wpr_engage_design_content' );
		$button            = $get( '_wpr_engage_design_button' );
		$email_placeholder = $get( '_wpr_engage_design_email_placeholder' );
		$display_rules     = $get( '_wpr_engage_display_rules', array() );
		$rule_groups       = $get( '_wpr_engage_rule_groups', array() );

		// Get form configuration values.
		$form_type      = $get( '_wpr_engage_form_type' );
		$form_fields    = $get( '_wpr_engage_form_fields', array() );
		$embed_code     = $get( '_wpr_engage_embed_code' );
		$embed_provider = $get( '_wpr_engage_embed_provider' );

		// Set defaults.
		if ( empty( $form_type ) ) {
			$form_type = 'native';
		}
		if ( ! is_array( $form_fields ) || empty( $form_fields ) ) {
			$form_fields = array(
				array(
					'type'        => 'email',
					'label'       => 'Email',
					'placeholder' => 'Enter your email',
					'required'    => true,
				),
			);
		}
		if ( empty( $embed_provider ) ) {
			$embed_provider = 'generic';
		}

		// Get style values.
		$bg_color              = $get( '_wpr_engage_style_bg_color' );
		$headline_color        = $get( '_wpr_engage_style_headline_color' );
		$content_color         = $get( '_wpr_engage_style_content_color' );
		$button_bg_color       = $get( '_wpr_engage_style_button_bg_color' );
		$button_text_color     = $get( '_wpr_engage_style_button_text_color' );
		$border_radius         = $get( '_wpr_engage_style_border_radius' );
		$border_width          = $get( '_wpr_engage_style_border_width' );
		$border_color          = $get( '_wpr_engage_style_border_color' );
		$box_shadow_enabled    = $get( '_wpr_engage_style_box_shadow_enabled' );
		$box_shadow_color      = $get( '_wpr_engage_style_box_shadow_color' );
		$box_shadow_x          = $get( '_wpr_engage_style_box_shadow_x' );
		$box_shadow_y          = $get( '_wpr_engage_style_box_shadow_y' );
		$box_shadow_blur       = $get( '_wpr_engage_style_box_shadow_blur' );
		$box_shadow_spread     = $get( '_wpr_engage_style_box_shadow_spread' );
		$bg_image_url          = $get( '_wpr_engage_style_bg_image_url' );
		$bg_image_repeat       = $get( '_wpr_engage_style_bg_image_repeat' );
		$bg_image_position     = $get( '_wpr_engage_style_bg_image_position' );
		$bg_image_size         = $get( '_wpr_engage_style_bg_image_size' );
		$bg_media_type         = $get( '_wpr_engage_style_bg_media_type' );
		$bg_video_url          = $get( '_wpr_engage_style_bg_video_url' );
		$bg_video_autoplay     = $get( '_wpr_engage_style_bg_video_autoplay' );
		$bg_video_loop         = $get( '_wpr_engage_style_bg_video_loop' );
		$bg_video_muted        = $get( '_wpr_engage_style_bg_video_muted' );
		$bg_youtube_url        = $get( '_wpr_engage_style_bg_youtube_url' );
		$bg_vimeo_url          = $get( '_wpr_engage_style_bg_vimeo_url' );
		$close_btn_color       = $get( '_wpr_engage_style_close_btn_color' );
		$close_btn_hover_color = $get( '_wpr_engage_style_close_btn_hover_color' );
		$close_btn_bg_color    = $get( '_wpr_engage_style_close_btn_bg_color' );
		$close_btn_shape       = $get( '_wpr_engage_style_close_btn_shape' );
		$esc_to_close          = $get( '_wpr_engage_esc_to_close' );
		$show_close_icon       = $get( '_wpr_engage_show_close_icon' );
		$preview_button_bg   = $button_bg_color ? $button_bg_color : '#3b82f6';
		$preview_button_text = $button_text_color ? $button_text_color : '#ffffff';

		$success_action           = $get( '_wpr_engage_success_action' );
		$success_redirect_url     = $get( '_wpr_engage_success_redirect_url' );
		$success_message_headline = $get( '_wpr_engage_success_message_headline' );
		$success_message_content  = $get( '_wpr_engage_success_message_content' );
		$success_auto_close       = $get( '_wpr_engage_success_auto_close' );
		$success_auto_close_delay = $get( '_wpr_engage_success_auto_close_delay' );
		$success_redirect_delay   = $get( '_wpr_engage_success_redirect_delay' );
		$success_redirect_new_tab = $get( '_wpr_engage_success_redirect_new_tab' );

		// Get success icon settings.
		$success_show_icon  = $get( '_wpr_engage_success_show_icon' );
		$success_icon_type  = $get( '_wpr_engage_success_icon_type' );
		$success_icon_color = $get( '_wpr_engage_success_icon_color' );

		// Get success message styling.
		$success_title_color         = $get( '_wpr_engage_success_title_color' );
		$success_content_color       = $get( '_wpr_engage_success_content_color' );
		$success_title_font_size     = $get( '_wpr_engage_success_title_font_size' );
		$success_content_font_size   = $get( '_wpr_engage_success_content_font_size' );
		$success_title_font_weight   = $get( '_wpr_engage_success_title_font_weight' );
		$success_content_font_weight = $get( '_wpr_engage_success_content_font_weight' );

		// Get discount code.

		// Set default success action if not set
		if ( empty( $success_action ) ) {
			$success_action = 'message';
		}
		if ( empty( $success_message_headline ) ) {
			$success_message_headline = __( 'Thank you!', 'wprobo-engage-lite' );
		}
		if ( empty( $success_message_content ) ) {
			$success_message_content = __( 'Your subscription has been confirmed.', 'wprobo-engage-lite' );
		}
		if ( '' === $success_auto_close_delay ) {
			$success_auto_close_delay = '5';
		}
		if ( '' === $success_redirect_delay ) {
			$success_redirect_delay = '3';
		}

		// Set success icon defaults.
		// Use === '' to check if truly empty, not '0'
		if ( '' === $success_show_icon ) {
			$success_show_icon = '1'; // Show icon by default for new campaigns
		}
		if ( empty( $success_icon_type ) ) {
			$success_icon_type = 'checkmark';
		}
		if ( empty( $success_icon_color ) ) {
			$success_icon_color = '#059669'; // Green
		}

		// Set success styling defaults.
		if ( empty( $success_title_color ) ) {
			$success_title_color = '#059669'; // Green
		}
		if ( empty( $success_content_color ) ) {
			$success_content_color = '#4B5563'; // Gray
		}
		if ( empty( $success_title_font_size ) ) {
			$success_title_font_size = '24';
		}
		if ( empty( $success_content_font_size ) ) {
			$success_content_font_size = '16';
		}
		if ( empty( $success_title_font_weight ) ) {
			$success_title_font_weight = 'bold';
		}
		if ( empty( $success_content_font_weight ) ) {
			$success_content_font_weight = 'normal';
		}
		// Get trigger values.
		$trigger_type  = $get( '_wpr_engage_trigger_type' );
		$trigger_value = $get( '_wpr_engage_trigger_value' );

		// Get schedule settings.
		$schedule_enabled            = $get( '_wpr_engage_schedule_enabled' );
		$schedule_start_date         = $get( '_wpr_engage_schedule_start_date' );
		$schedule_start_time         = $get( '_wpr_engage_schedule_start_time' );
		$schedule_end_date           = $get( '_wpr_engage_schedule_end_date' );
		$schedule_end_time           = $get( '_wpr_engage_schedule_end_time' );
		$schedule_days_of_week       = $get( '_wpr_engage_schedule_days_of_week', array() );
		$schedule_time_range_enabled = $get( '_wpr_engage_schedule_time_range_enabled' );
		$schedule_time_start         = $get( '_wpr_engage_schedule_time_start' );
		$schedule_time_end           = $get( '_wpr_engage_schedule_time_end' );

		// Set schedule defaults.
		if ( ! is_array( $schedule_days_of_week ) ) {
			$schedule_days_of_week = array();
		}

		// Get floating bar specific settings.
		$bar_position = get_post_meta( $post->ID, '_wpr_engage_bar_position', true );

		// Get slide-in specific settings.
		$slide_position = get_post_meta( $post->ID, '_wpr_engage_slide_position', true );

		// Ensure display_rules is an array
		if ( ! is_array( $display_rules ) ) {
			$display_rules = array();
		}

		$form_elem_relative_css = '';
		if ( $bg_media_type === 'video' || $bg_media_type === 'youtube' || $bg_media_type === 'vimeo' ) {
			$form_elem_relative_css = 'position: relative; z-index: 2;';
		}

		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th>
					<label for="wpr-engage-type"><?php esc_html_e( 'Campaign Type', 'wprobo-engage-lite' ); ?></label>
				</th>
				<td>
					<select name="wpr_engage_campaign_type" id="wpr-engage-type" class="postbox">
						<option value="popup" <?php selected( $campaign_type, 'popup' ); ?>><?php esc_html_e( 'Lightbox Popup', 'wprobo-engage-lite' ); ?></option>
						<option value="floating-bar" <?php selected( $campaign_type, 'floating-bar' ); ?>><?php esc_html_e( 'Floating Bar', 'wprobo-engage-lite' ); ?></option>
						<option value="slide-in" <?php selected( $campaign_type, 'slide-in' ); ?>><?php esc_html_e( 'Slide-in', 'wprobo-engage-lite' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label for="wpr-engage-status"><?php esc_html_e( 'Status', 'wprobo-engage-lite' ); ?></label>
				</th>
				<td>
					<select name="wpr_engage_campaign_status" id="wpr-engage-status" class="postbox">
						<option value="paused" <?php selected( $campaign_status, 'paused' ); ?>><?php esc_html_e( 'Paused', 'wprobo-engage-lite' ); ?></option>
						<option value="active" <?php selected( $campaign_status, 'active' ); ?>><?php esc_html_e( 'Active', 'wprobo-engage-lite' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Set to "Active" to make this campaign live on your site, provided its display rules are met.', 'wprobo-engage-lite' ); ?>
					</p>
				</td>
			</tr>
			<tr id="wpr-bar-position-row" style="<?php echo 'floating-bar' === $campaign_type ? '' : 'display: none;'; ?>">
				<th>
					<label for="wpr-engage-bar-position"><?php esc_html_e( 'Bar Position', 'wprobo-engage-lite' ); ?></label>
				</th>
				<td>
					<select name="wpr_engage_bar_position" id="wpr-engage-bar-position" class="postbox">
						<option value="top" <?php selected( $bar_position, 'top' ); ?>><?php esc_html_e( 'Top', 'wprobo-engage-lite' ); ?></option>
						<option value="bottom" <?php selected( $bar_position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'wprobo-engage-lite' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose whether the bar appears at the top or bottom of the screen.', 'wprobo-engage-lite' ); ?>
					</p>
				</td>
			</tr>
			<tr id="wpr-slide-position-row" style="<?php echo 'slide-in' === $campaign_type ? '' : 'display: none;'; ?>">
				<th>
					<label for="wpr-engage-slide-position"><?php esc_html_e( 'Slide-in Position', 'wprobo-engage-lite' ); ?></label>
				</th>
				<td>
					<select name="wpr_engage_slide_position" id="wpr-engage-slide-position" class="postbox">
						<option value="bottom-right" <?php selected( $slide_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'wprobo-engage-lite' ); ?></option>
						<option value="bottom-left" <?php selected( $slide_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'wprobo-engage-lite' ); ?></option>
						<option value="top-right" <?php selected( $slide_position, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'wprobo-engage-lite' ); ?></option>
						<option value="top-left" <?php selected( $slide_position, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'wprobo-engage-lite' ); ?></option>
					</select>
					<p class="description">
						<?php esc_html_e( 'Choose which corner of the screen the slide-in appears from.', 'wprobo-engage-lite' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<!-- Campaign Builder UI -->
		<div class="wpr-builder-content" style="margin-top: 20px;">
			
			<!-- Tabs Navigation -->
			<?php
			$pro_badge    = ' ' . Pro_Upsell::render_badge();
			$editor_tabs  = array(
				'design'        => __( 'Design', 'wprobo-engage-lite' ),
				'triggers'      => __( 'Triggers', 'wprobo-engage-lite' ),
				'urgency'       => __( 'Urgency', 'wprobo-engage-lite' ) . $pro_badge,
				'display-rules' => __( 'Display Rules', 'wprobo-engage-lite' ),
				'form'          => __( 'Form', 'wprobo-engage-lite' ),
				'after-success' => __( 'Success', 'wprobo-engage-lite' ),
				'ab-testing'    => __( 'A/B Testing', 'wprobo-engage-lite' ) . $pro_badge,
				'integrations'  => __( 'Integrations', 'wprobo-engage-lite' ) . $pro_badge,
				'schedule'      => __( 'Schedule', 'wprobo-engage-lite' ) . $pro_badge,
			);
			$first_tab   = true;
			?>
			<div role="tablist" aria-label="<?php esc_attr_e( 'Campaign Settings', 'wprobo-engage-lite' ); ?>" class="wpr-flex wpr-border-b wpr-border-gray-200" style="display: flex; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; overflow-x: auto;">
				<?php foreach ( $editor_tabs as $tab_key => $tab_label ) : ?>
					<button type="button"
						role="tab"
						id="wpr-tab-btn-<?php echo esc_attr( $tab_key ); ?>"
						aria-controls="wpr-tab-<?php echo esc_attr( $tab_key ); ?>"
						aria-selected="<?php echo $first_tab ? 'true' : 'false'; ?>"
						<?php echo $first_tab ? '' : 'tabindex="-1"'; ?>
						class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium <?php echo $first_tab ? 'wpr-text-gray-700 wpr-border-b-2 wpr-border-blue-500' : 'wpr-text-gray-500 wpr-border-b-2 wpr-border-transparent'; ?> wpr-bg-white"
						data-tab="<?php echo esc_attr( $tab_key ); ?>"
						style="flex: 0 0 auto; padding: 12px 16px; background: white; cursor: pointer; white-space: nowrap;">
						<?php echo wp_kses( $tab_label, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ); ?>
					</button>
					<?php $first_tab = false; ?>
				<?php endforeach; ?>
			</div>

			<!-- Toolbar -->
			<div style="display: flex; justify-content: flex-end; margin-bottom: 12px;">
				<button type="button" id="wpr-revert-changes" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 13px; font-weight: 500; color: #6b7280; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; transition: all 0.2s;" title="<?php esc_attr_e( 'Revert all unsaved changes', 'wprobo-engage-lite' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
					<?php esc_html_e( 'Revert Changes', 'wprobo-engage-lite' ); ?>
				</button>
			</div>

			<!-- Split Layout -->
			<div class="wpr-flex" style="display: flex; gap: 20px;">
				<div class="wpr-builder-sidebar" style="flex: 0 0 350px; max-width: 40%;">
					<!-- Design Tab -->
					<div id="wpr-tab-design" role="tabpanel" aria-labelledby="wpr-tab-btn-design" class="wpr-tab-content">
						<div class="wpr-design-panel">

							<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'AI Copywriting', 'wprobo-engage-lite' ), __( 'Generate high-converting headlines, content, and CTAs with AI.', 'wprobo-engage-lite' ) ) ); ?>

							<!-- ── SECTION: Content ─────────────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header open" data-accordion="wpr-acc-content">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-content">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" /></svg>
										</span>
										<?php esc_html_e( 'Content', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body open" id="wpr-acc-content">
									<div>
										<label for="wpr-headline" class="wpr-field-label"><?php esc_html_e( 'Headline', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-headline" value="<?php echo esc_attr( $headline ); ?>" class="wpr-field-input">
									</div>
									<div>
										<label for="wpr-content" class="wpr-field-label"><?php esc_html_e( 'Content', 'wprobo-engage-lite' ); ?></label>
										<textarea id="wpr-content" rows="3" class="wpr-field-input" style="resize:vertical;"><?php echo esc_textarea( $content ); ?></textarea>
									</div>
									<div>
										<label for="wpr-button" class="wpr-field-label"><?php esc_html_e( 'Button Text', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-button" value="<?php echo esc_attr( $button ); ?>" class="wpr-field-input">
									</div>
									<div>
										<label for="wpr-email-placeholder" class="wpr-field-label"><?php esc_html_e( 'Email Placeholder', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-email-placeholder" value="<?php echo esc_attr( $email_placeholder ?: 'Enter your email' ); ?>" class="wpr-field-input">
									</div>
								</div>
							</div>

							<!-- ── SECTION: Colors ──────────────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header open" data-accordion="wpr-acc-colors">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-colors">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z" clip-rule="evenodd" /></svg>
										</span>
										<?php esc_html_e( 'Colors', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body open" id="wpr-acc-colors">
									<div>
										<label for="wpr-bg-color" class="wpr-field-label"><?php esc_html_e( 'Background', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-bg-color" value="<?php echo esc_attr( $bg_color ?: '#ffffff' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-headline-color" class="wpr-field-label"><?php esc_html_e( 'Headline Text', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-headline-color" value="<?php echo esc_attr( $headline_color ?: '#111827' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-content-color" class="wpr-field-label"><?php esc_html_e( 'Content Text', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-content-color" value="<?php echo esc_attr( $content_color ?: '#4b5563' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-button-bg-color" class="wpr-field-label"><?php esc_html_e( 'Button Background', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-button-bg-color" value="<?php echo esc_attr( $button_bg_color ?: '#3b82f6' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-button-text-color" class="wpr-field-label"><?php esc_html_e( 'Button Text', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-button-text-color" value="<?php echo esc_attr( $button_text_color ?: '#ffffff' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
								</div>
							</div>

							<!-- ── SECTION: Border & Shape ──────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header" data-accordion="wpr-acc-border">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-border">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 010 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 010 2H4a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
										</span>
										<?php esc_html_e( 'Border & Shape', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body" id="wpr-acc-border">
									<div class="wpr-2col-grid">
										<div>
											<label for="wpr-border-radius" class="wpr-field-label-sm"><?php esc_html_e( 'Radius (px)', 'wprobo-engage-lite' ); ?></label>
											<input type="number" id="wpr-border-radius" value="<?php echo esc_attr( $border_radius ?: '8' ); ?>" min="0" max="100" class="wpr-field-input-sm">
										</div>
										<div>
											<label for="wpr-border-width" class="wpr-field-label-sm"><?php esc_html_e( 'Width (px)', 'wprobo-engage-lite' ); ?></label>
											<input type="number" id="wpr-border-width" value="<?php echo esc_attr( $border_width ?: '0' ); ?>" min="0" max="20" class="wpr-field-input-sm">
										</div>
									</div>
									<div>
										<label for="wpr-border-color" class="wpr-field-label"><?php esc_html_e( 'Border Color', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-border-color" value="<?php echo esc_attr( $border_color ?: '#d1d5db' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
								</div>
							</div>

							<!-- ── SECTION: Shadow ──────────────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header" data-accordion="wpr-acc-shadow">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-shadow">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" /><path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" /></svg>
										</span>
										<?php esc_html_e( 'Shadow', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body" id="wpr-acc-shadow">
									<label class="wpr-check-row">
										<input type="checkbox" id="wpr-box-shadow-enabled" value="1" <?php checked( $box_shadow_enabled, '1' ); ?>>
										<span><?php esc_html_e( 'Enable Box Shadow', 'wprobo-engage-lite' ); ?></span>
									</label>
									<div id="wpr-box-shadow-controls" style="<?php echo ( $box_shadow_enabled !== '1' ) ? 'display:none;' : 'display:flex;flex-direction:column;gap:12px;'; ?>">
										<div>
											<label for="wpr-box-shadow-color" class="wpr-field-label"><?php esc_html_e( 'Shadow Color', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-box-shadow-color" value="<?php echo esc_attr( $box_shadow_color ?: '#000000' ); ?>" class="wpr-color-picker" style="width:100%;">
										</div>
										<div class="wpr-2col-grid">
											<div>
												<label for="wpr-box-shadow-x" class="wpr-field-label-sm"><?php esc_html_e( 'X Offset', 'wprobo-engage-lite' ); ?></label>
												<input type="number" id="wpr-box-shadow-x" value="<?php echo esc_attr( $box_shadow_x ?: '0' ); ?>" min="-50" max="50" class="wpr-field-input-sm">
											</div>
											<div>
												<label for="wpr-box-shadow-y" class="wpr-field-label-sm"><?php esc_html_e( 'Y Offset', 'wprobo-engage-lite' ); ?></label>
												<input type="number" id="wpr-box-shadow-y" value="<?php echo esc_attr( $box_shadow_y ?: '10' ); ?>" min="-50" max="50" class="wpr-field-input-sm">
											</div>
											<div>
												<label for="wpr-box-shadow-blur" class="wpr-field-label-sm"><?php esc_html_e( 'Blur', 'wprobo-engage-lite' ); ?></label>
												<input type="number" id="wpr-box-shadow-blur" value="<?php echo esc_attr( $box_shadow_blur ?: '15' ); ?>" min="0" max="100" class="wpr-field-input-sm">
											</div>
											<div>
												<label for="wpr-box-shadow-spread" class="wpr-field-label-sm"><?php esc_html_e( 'Spread', 'wprobo-engage-lite' ); ?></label>
												<input type="number" id="wpr-box-shadow-spread" value="<?php echo esc_attr( $box_shadow_spread ?: '-3' ); ?>" min="-50" max="50" class="wpr-field-input-sm">
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- ── SECTION: Background Media ────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header" data-accordion="wpr-acc-background">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-background">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" /></svg>
										</span>
										<?php esc_html_e( 'Background Media', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body" id="wpr-acc-background">
									<div>
										<label for="wpr-bg-media-type" class="wpr-field-label"><?php esc_html_e( 'Media Type', 'wprobo-engage-lite' ); ?></label>
										<select id="wpr-bg-media-type" class="wpr-field-input">
											<option value="none" <?php selected( $bg_media_type, 'none' ); ?>><?php esc_html_e( 'None (Solid Color)', 'wprobo-engage-lite' ); ?></option>
											<option value="image" 
											<?php
											selected( $bg_media_type, 'image' );
											selected( $bg_media_type, '' );
											?>
											><?php esc_html_e( 'Image / GIF', 'wprobo-engage-lite' ); ?></option>
										</select>
									</div>

									<!-- Image Background Settings -->
									<div id="wpr-bg-image-settings" style="<?php echo ( ! $bg_media_type || $bg_media_type === 'image' ) ? 'display:flex;flex-direction:column;gap:10px;' : 'display:none;'; ?>">
										<div>
											<label for="wpr-bg-image-url" class="wpr-field-label"><?php esc_html_e( 'Image URL', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-bg-image-url" value="<?php echo esc_attr( $bg_image_url ); ?>" placeholder="https://example.com/image.jpg" class="wpr-field-input">
										</div>
										<div class="wpr-2col-grid">
											<div>
												<label for="wpr-bg-image-repeat" class="wpr-field-label-sm"><?php esc_html_e( 'Repeat', 'wprobo-engage-lite' ); ?></label>
												<select id="wpr-bg-image-repeat" class="wpr-field-input-sm">
													<option value="no-repeat" <?php selected( $bg_image_repeat, 'no-repeat' ); ?>><?php esc_html_e( 'No Repeat', 'wprobo-engage-lite' ); ?></option>
													<option value="repeat" <?php selected( $bg_image_repeat, 'repeat' ); ?>><?php esc_html_e( 'Repeat', 'wprobo-engage-lite' ); ?></option>
													<option value="repeat-x" <?php selected( $bg_image_repeat, 'repeat-x' ); ?>><?php esc_html_e( 'Repeat X', 'wprobo-engage-lite' ); ?></option>
													<option value="repeat-y" <?php selected( $bg_image_repeat, 'repeat-y' ); ?>><?php esc_html_e( 'Repeat Y', 'wprobo-engage-lite' ); ?></option>
												</select>
											</div>
											<div>
												<label for="wpr-bg-image-position" class="wpr-field-label-sm"><?php esc_html_e( 'Position', 'wprobo-engage-lite' ); ?></label>
												<select id="wpr-bg-image-position" class="wpr-field-input-sm">
													<option value="center" <?php selected( $bg_image_position, 'center' ); ?>><?php esc_html_e( 'Center', 'wprobo-engage-lite' ); ?></option>
													<option value="top" <?php selected( $bg_image_position, 'top' ); ?>><?php esc_html_e( 'Top', 'wprobo-engage-lite' ); ?></option>
													<option value="bottom" <?php selected( $bg_image_position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'wprobo-engage-lite' ); ?></option>
													<option value="left" <?php selected( $bg_image_position, 'left' ); ?>><?php esc_html_e( 'Left', 'wprobo-engage-lite' ); ?></option>
													<option value="right" <?php selected( $bg_image_position, 'right' ); ?>><?php esc_html_e( 'Right', 'wprobo-engage-lite' ); ?></option>
												</select>
											</div>
											<div>
												<label for="wpr-bg-image-size" class="wpr-field-label-sm"><?php esc_html_e( 'Size', 'wprobo-engage-lite' ); ?></label>
												<select id="wpr-bg-image-size" class="wpr-field-input-sm">
													<option value="cover" <?php selected( $bg_image_size, 'cover' ); ?>><?php esc_html_e( 'Cover', 'wprobo-engage-lite' ); ?></option>
													<option value="contain" <?php selected( $bg_image_size, 'contain' ); ?>><?php esc_html_e( 'Contain', 'wprobo-engage-lite' ); ?></option>
													<option value="auto" <?php selected( $bg_image_size, 'auto' ); ?>><?php esc_html_e( 'Auto', 'wprobo-engage-lite' ); ?></option>
												</select>
											</div>
										</div>
									</div>

									<!-- Video Background Settings -->
									<div id="wpr-bg-video-settings" style="<?php echo ( $bg_media_type === 'video' ) ? 'display:flex;flex-direction:column;gap:10px;' : 'display:none;'; ?>">
										<div>
											<label for="wpr-bg-video-url" class="wpr-field-label"><?php esc_html_e( 'Video URL (MP4/WebM)', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-bg-video-url" value="<?php echo esc_attr( $bg_video_url ); ?>" placeholder="https://example.com/video.mp4" class="wpr-field-input">
										</div>
										<div style="display:flex;flex-direction:column;gap:6px;">
											<label class="wpr-check-row"><input type="checkbox" id="wpr-bg-video-autoplay" value="1" <?php checked( $bg_video_autoplay === '' || $bg_video_autoplay === '1', true ); ?>><?php esc_html_e( 'Autoplay', 'wprobo-engage-lite' ); ?></label>
											<label class="wpr-check-row"><input type="checkbox" id="wpr-bg-video-loop" value="1" <?php checked( $bg_video_loop === '' || $bg_video_loop === '1', true ); ?>><?php esc_html_e( 'Loop', 'wprobo-engage-lite' ); ?></label>
											<label class="wpr-check-row"><input type="checkbox" id="wpr-bg-video-muted" value="1" <?php checked( $bg_video_muted === '' || $bg_video_muted === '1', true ); ?>><?php esc_html_e( 'Muted (required for autoplay)', 'wprobo-engage-lite' ); ?></label>
										</div>
									</div>

									<!-- YouTube Background Settings -->
									<div id="wpr-bg-youtube-settings" style="<?php echo ( $bg_media_type === 'youtube' ) ? '' : 'display:none;'; ?>">
										<label for="wpr-bg-youtube-url" class="wpr-field-label"><?php esc_html_e( 'YouTube URL', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-bg-youtube-url" value="<?php echo esc_attr( $bg_youtube_url ); ?>" placeholder="https://www.youtube.com/watch?v=..." class="wpr-field-input">
									</div>

									<!-- Vimeo Background Settings -->
									<div id="wpr-bg-vimeo-settings" style="<?php echo ( $bg_media_type === 'vimeo' ) ? '' : 'display:none;'; ?>">
										<label for="wpr-bg-vimeo-url" class="wpr-field-label"><?php esc_html_e( 'Vimeo URL', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-bg-vimeo-url" value="<?php echo esc_attr( $bg_vimeo_url ); ?>" placeholder="https://vimeo.com/123456789" class="wpr-field-input">
									</div>
								</div>
							</div>

							<!-- ── SECTION: Close Button ─────────────────── -->
							<div class="wpr-accordion">
								<button type="button" class="wpr-accordion-header" data-accordion="wpr-acc-close">
									<span class="wpr-accordion-title">
										<span class="wpr-accordion-icon wpr-icon-close">
											<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
										</span>
										<?php esc_html_e( 'Close Button', 'wprobo-engage-lite' ); ?>
									</span>
									<svg class="wpr-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
								</button>
								<div class="wpr-accordion-body" id="wpr-acc-close">
									<div>
										<label for="wpr-close-btn-color" class="wpr-field-label"><?php esc_html_e( 'Icon Color', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-close-btn-color" value="<?php echo esc_attr( $close_btn_color ?: '#6b7280' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-close-btn-hover-color" class="wpr-field-label"><?php esc_html_e( 'Icon Hover Color', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-close-btn-hover-color" value="<?php echo esc_attr( $close_btn_hover_color ?: '#1f2937' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-close-btn-bg-color" class="wpr-field-label"><?php esc_html_e( 'Background Color', 'wprobo-engage-lite' ); ?></label>
										<input type="text" id="wpr-close-btn-bg-color" value="<?php echo esc_attr( $close_btn_bg_color ?: 'transparent' ); ?>" class="wpr-color-picker" style="width:100%;">
									</div>
									<div>
										<label for="wpr-close-btn-shape" class="wpr-field-label"><?php esc_html_e( 'Button Shape', 'wprobo-engage-lite' ); ?></label>
										<select id="wpr-close-btn-shape" class="wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-md wpr-p-2 wpr-text-sm" style="border: 1px solid #e2e8f0; height: 38px; width: 100%;">
											<option value="square" <?php selected( $close_btn_shape, 'square' ); ?>><?php esc_html_e( 'Square', 'wprobo-engage-lite' ); ?></option>
											<option value="rounded" <?php selected( $close_btn_shape === '' || $close_btn_shape === 'rounded', true ); ?>><?php esc_html_e( 'Rounded', 'wprobo-engage-lite' ); ?></option>
											<option value="circle" <?php selected( $close_btn_shape, 'circle' ); ?>><?php esc_html_e( 'Circle', 'wprobo-engage-lite' ); ?></option>
										</select>
									</div>
									<hr class="wpr-section-divider">
									<label class="wpr-check-row">
										<input type="checkbox" id="wpr-esc-to-close" value="1" <?php checked( $esc_to_close === '' || $esc_to_close === '1', true ); ?>>
										<?php esc_html_e( 'Allow ESC key to close', 'wprobo-engage-lite' ); ?>
									</label>
									<label class="wpr-check-row">
										<input type="checkbox" id="wpr-show-close-icon" value="1" <?php checked( $show_close_icon === '' || $show_close_icon === '1', true ); ?>>
										<?php esc_html_e( 'Show close (X) icon', 'wprobo-engage-lite' ); ?>
									</label>
								</div>
							</div>

							<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Custom CSS', 'wprobo-engage-lite' ), __( 'Add custom CSS to fully control your campaign styling.', 'wprobo-engage-lite' ) ) ); ?>

						</div>
					</div>

					<!-- Form Tab -->
					<div id="wpr-tab-form" role="tabpanel" aria-labelledby="wpr-tab-btn-form" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 24px;">

							<!-- Card 1: Form Header -->
							<div style="background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); padding: 24px; border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
									<svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
									</svg>
									<h3 class="wpr-text-lg wpr-font-bold" style="margin: 0; color: #ffffff;"><?php esc_html_e( 'Form Configuration', 'wprobo-engage-lite' ); ?></h3>
								</div>
								<p class="wpr-text-sm wpr-opacity-90" style="margin: 0; color: #ffffff; line-height: 1.5;">
									<?php esc_html_e( 'Choose how you want to collect user information - use our built-in form builder or embed a third-party form.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<!-- Card 2: Form Type Selection -->
							<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<label class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-4" style="display: block; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Selection Strategy', 'wprobo-engage-lite' ); ?></label>
								<div class="wpr-grid wpr-gap-4" style="display: grid; grid-template-columns: 1fr; gap: 16px;">
									<!-- Native Radio Card -->
									<label style="position: relative; cursor: pointer; display: block; outline: none; margin-bottom: 0;">
										<input type="radio" name="wpr_engage_form_type" value="native" <?php checked( $form_type, 'native' ); ?> style="position: absolute; opacity: 0; width: 0; height: 0;" class="wpr-peer">
										<div style="padding: 24px 20px; border-radius: 12px; height: 100%; border: 2px solid #e2e8f0; background: #ffffff; transition: all 0.2s;" class="wpr-radio-card">
											<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
												<div style="display: flex; align-items: flex-start; gap: 14px; flex: 1;">
													<div class="wpr-icon-wrapper" style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; transition: all 0.2s; flex-shrink: 0;">
														<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
													</div>
													<div style="flex: 1;">
														<span class="wpr-card-title" style="display: block; font-size: 15px; font-weight: 700; color: #1e293b; transition: color 0.1s;"><?php esc_html_e( 'Built-in Builder', 'wprobo-engage-lite' ); ?></span>
														<span style="display: block; font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5;"><?php esc_html_e( 'Create custom forms with multiple fields natively.', 'wprobo-engage-lite' ); ?></span>
													</div>
												</div>
												<div class="wpr-radio-indicator" style="margin-top: 2px;"></div>
											</div>
										</div>
									</label>
									<!-- Embed Radio Card -->
									<label style="position: relative; cursor: pointer; display: block; outline: none; margin-bottom: 0;">
										<input type="radio" name="wpr_engage_form_type" value="embed" <?php checked( $form_type, 'embed' ); ?> style="position: absolute; opacity: 0; width: 0; height: 0;" class="wpr-peer">
										<div style="padding: 24px 20px; border-radius: 12px; height: 100%; border: 2px solid #e2e8f0; background: #ffffff; transition: all 0.2s;" class="wpr-radio-card">
											<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 14px;">
												<div style="display: flex; align-items: flex-start; gap: 14px; flex: 1;">
													<div class="wpr-icon-wrapper" style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #8b5cf6; transition: all 0.2s; flex-shrink: 0;">
														<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
													</div>
													<div style="flex: 1;">
														<span class="wpr-card-title" style="display: block; font-size: 15px; font-weight: 700; color: #1e293b; transition: color 0.1s;"><?php esc_html_e( 'Third-Party Embed', 'wprobo-engage-lite' ); ?></span>
														<span style="display: block; font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5;"><?php esc_html_e( 'Embed code from Mailchimp or other providers.', 'wprobo-engage-lite' ); ?></span>
													</div>
												</div>
												<div class="wpr-radio-indicator" style="margin-top: 2px;"></div>
											</div>
										</div>
									</label>
								</div>
							</div>

							<!-- Card 3: Dynamic Form Settings -->
							<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								
								<!-- Built-in Form Builder (Native) -->
								<div id="wpr-native-form-builder" style="<?php echo 'native' === $form_type ? '' : 'display: none;'; ?>">
									<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
										<h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Form Fields', 'wprobo-engage-lite' ); ?></h4>
										<button type="button" id="wpr-add-form-field" class="button button-primary wpr-btn-gradient" style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); border: none; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.15);">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
											<?php esc_html_e( 'Add Field', 'wprobo-engage-lite' ); ?>
										</button>
									</div>

									<div id="wpr-form-fields-container" class="wpr-space-y-4" style="display: flex; flex-direction: column; gap: 16px;">
										<?php foreach ( $form_fields as $index => $field ) : ?>
											<div class="wpr-form-field-item" draggable="true" style="padding: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; transition: border-color 0.2s, opacity 0.2s;" data-index="<?php echo esc_attr( $index ); ?>">
												<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px;">
													<div style="display: flex; align-items: center; gap: 10px;">
														<span class="wpr-drag-handle" style="cursor: grab; color: #cbd5e1; font-size: 16px; line-height: 1; user-select: none;" title="<?php esc_attr_e( 'Drag to reorder', 'wprobo-engage-lite' ); ?>">⠿</span>
														<span class="wpr-field-number" style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em;"><?php echo esc_html__( 'Field #', 'wprobo-engage-lite' ) . esc_html( $index + 1 ); ?></span>
														<select name="wpr_engage_form_fields[<?php echo esc_attr( $index ); ?>][type]" class="wpr-field-type" style="height: 34px; font-size: 13px; border-radius: 6px; padding: 0 10px; border: 1px solid #cbd5e1; outline: none; background: white; color: #334155;">
															<option value="email" <?php selected( $field['type'], 'email' ); ?>><?php esc_html_e( 'Email', 'wprobo-engage-lite' ); ?></option>
															<option value="text" <?php selected( $field['type'], 'text' ); ?>><?php esc_html_e( 'Text', 'wprobo-engage-lite' ); ?></option>
															<option value="phone" <?php selected( $field['type'], 'phone' ); ?>><?php esc_html_e( 'Phone', 'wprobo-engage-lite' ); ?></option>
															<option value="checkbox" <?php selected( $field['type'], 'checkbox' ); ?>><?php esc_html_e( 'Checkbox', 'wprobo-engage-lite' ); ?></option>
														</select>
													</div>
													<button type="button" class="wpr-remove-field" style="color: #ef4444; background: none; border: none; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; text-transform: uppercase; letter-spacing: 0.025em;">
														<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
														<?php esc_html_e( 'Remove', 'wprobo-engage-lite' ); ?>
													</button>
												</div>
												<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
													<div>
														<label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.025em;"><?php esc_html_e( 'Label', 'wprobo-engage-lite' ); ?></label>
														<input type="text" name="wpr_engage_form_fields[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $field['label'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Full Name', 'wprobo-engage-lite' ); ?>" class="wpr-field-label-input" style="width: 100%; height: 40px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: white; color: #334155;">
													</div>
													<div>
														<label style="display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.025em;"><?php esc_html_e( 'Placeholder', 'wprobo-engage-lite' ); ?></label>
														<input type="text" name="wpr_engage_form_fields[<?php echo esc_attr( $index ); ?>][placeholder]" value="<?php echo esc_attr( $field['placeholder'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Enter your name', 'wprobo-engage-lite' ); ?>" class="wpr-field-placeholder-input" style="width: 100%; height: 40px; font-size: 13px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; background: white; color: #334155;">
													</div>
												</div>
												<label style="display: flex; align-items: center; gap: 10px; font-size: 13px; cursor: pointer; color: #475569; font-weight: 500;">
													<input type="checkbox" name="wpr_engage_form_fields[<?php echo esc_attr( $index ); ?>][required]" value="1" <?php checked( ! empty( $field['required'] ) ); ?> style="width: 18px; height: 18px; border-radius: 4px; border-color: #cbd5e1;">
													<?php esc_html_e( 'Required Field', 'wprobo-engage-lite' ); ?>
												</label>
											</div>
										<?php endforeach; ?>
									</div>
								</div>

								<!-- Embed Third-Party Form -->
								<div id="wpr-embed-form-section" style="<?php echo 'embed' === $form_type ? '' : 'display: none;'; ?>">
									<div class="wpr-mb-6" style="margin-bottom: 24px;">
										<label for="wpr-embed-provider" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="text-transform: uppercase;"><?php esc_html_e( 'Email Service Provider', 'wprobo-engage-lite' ); ?></label>
										<select name="wpr_engage_embed_provider" id="wpr-embed-provider" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; height: 44px; outline: none; border-color: #cbd5e1; background: white;">
											<option value="generic" <?php selected( $embed_provider, 'generic' ); ?>><?php esc_html_e( 'Generic HTML/JavaScript', 'wprobo-engage-lite' ); ?></option>
											<option value="mailchimp" <?php selected( $embed_provider, 'mailchimp' ); ?>><?php esc_html_e( 'Mailchimp', 'wprobo-engage-lite' ); ?></option>
											<option value="convertkit" <?php selected( $embed_provider, 'convertkit' ); ?>><?php esc_html_e( 'ConvertKit', 'wprobo-engage-lite' ); ?></option>
											<option value="aweber" <?php selected( $embed_provider, 'aweber' ); ?>><?php esc_html_e( 'AWeber', 'wprobo-engage-lite' ); ?></option>
											<option value="activecampaign" <?php selected( $embed_provider, 'activecampaign' ); ?>><?php esc_html_e( 'ActiveCampaign', 'wprobo-engage-lite' ); ?></option>
										</select>
									</div>

									<div class="wpr-mt-6" style="margin-top: 24px;">
										<label for="wpr-embed-code" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="text-transform: uppercase;"><?php esc_html_e( 'Embed Code (HTML)', 'wprobo-engage-lite' ); ?></label>
										<textarea name="wpr_engage_embed_code" id="wpr-embed-code" rows="10" style="width: 100%; padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; color: #334155; background: #f8fafc; line-height: 1.6;" placeholder="<?php esc_attr_e( 'Paste your form embed code here...', 'wprobo-engage-lite' ); ?>"><?php echo esc_textarea( $embed_code ); ?></textarea>
										<p style="margin-top: 10px; font-size: 12px; color: #64748b; line-height: 1.5;">
											<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
											<?php esc_html_e( 'Paste the complete HTML and JavaScript code from your email service provider.', 'wprobo-engage-lite' ); ?>
										</p>
									</div>

									<div style="margin-top: 24px; background: #fffbeb; border: 1px solid #fef3c7; padding: 16px; border-radius: 12px; display: flex; gap: 12px;">
										<div style="color: #f59e0b; flex-shrink: 0; margin-top: 2px;">
											<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
										</div>
										<div>
											<strong style="display: block; font-size: 13px; color: #92400e; margin-bottom: 2px;"><?php esc_html_e( 'Security Notice', 'wprobo-engage-lite' ); ?></strong>
											<p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
												<?php esc_html_e( 'Embedded code will be sanitized for security. Only safe HTML and JavaScript will be allowed.', 'wprobo-engage-lite' ); ?>
											</p>
										</div>
									</div>
								</div>

							</div>

						</div>
					</div>

					<!-- Triggers Tab -->
					<div id="wpr-tab-triggers" role="tabpanel" aria-labelledby="wpr-tab-btn-triggers" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 24px;">
							<!-- Triggers Header Card (Standalone) -->
							<div style="background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); padding: 24px; border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
									<svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
									<h3 class="wpr-text-lg wpr-font-bold" style="margin: 0; color: #ffffff;"><?php esc_html_e( 'Trigger Settings', 'wprobo-engage-lite' ); ?></h3>
								</div>
								<p class="wpr-text-sm wpr-opacity-90" style="margin: 0; color: #ffffff; line-height: 1.5;">
									<?php esc_html_e( 'Choose when to display this campaign based on user behavior.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<!-- Trigger Settings Card (Standalone) -->
							<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 24px;">
									
									<!-- Trigger Type Selection -->
									<div>
										<label for="wpr-trigger-type" class="wpr-block wpr-text-sm wpr-font-bold wpr-text-slate-700 wpr-mb-2"><?php esc_html_e( 'Trigger Type', 'wprobo-engage-lite' ); ?></label>
										<select id="wpr-trigger-type" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; height: 44px; outline: none; border-color: #cbd5e1; font-size: 14px; color: #1e293b;">
											<option value="" <?php selected( $trigger_type, '' ); ?>><?php esc_html_e( 'No Trigger (Manual)', 'wprobo-engage-lite' ); ?></option>
											<option value="timed_delay" <?php selected( $trigger_type, 'timed_delay' ); ?>><?php esc_html_e( 'Timed Delay', 'wprobo-engage-lite' ); ?></option>
											<option value="scroll_depth" <?php selected( $trigger_type, 'scroll_depth' ); ?>><?php esc_html_e( 'Scroll Depth', 'wprobo-engage-lite' ); ?></option>
											<option value="exit_intent" <?php selected( $trigger_type, 'exit_intent' ); ?>><?php esc_html_e( 'Exit-Intent', 'wprobo-engage-lite' ); ?></option>
										</select>
									</div>

									<!-- Conditional Value Container -->
									<div id="wpr-trigger-value-container" style="<?php echo ( empty( $trigger_type ) || 'exit_intent' === $trigger_type ) ? 'display: none;' : ''; ?>">
										
										<!-- Timed Delay Field -->
										<div id="wpr-timed-delay-field" style="<?php echo 'timed_delay' === $trigger_type ? '' : 'display: none;'; ?>">
											<label for="wpr-trigger-value-delay" class="wpr-block wpr-text-sm wpr-font-bold wpr-text-slate-700 wpr-mb-2"><?php esc_html_e( 'Delay (seconds)', 'wprobo-engage-lite' ); ?></label>
											<input type="number" id="wpr-trigger-value-delay" min="0" step="1" value="<?php echo 'timed_delay' === $trigger_type ? esc_attr( $trigger_value ) : '5'; ?>" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; height: 44px; outline: none; border-color: #cbd5e1;">
											<p class="wpr-text-xs wpr-text-slate-500 wpr-mt-2" style="line-height: 1.5;">
												<?php esc_html_e( 'Number of seconds to wait before showing the popup.', 'wprobo-engage-lite' ); ?>
											</p>
										</div>

										<!-- Scroll Depth Field -->
										<div id="wpr-scroll-depth-field" style="<?php echo 'scroll_depth' === $trigger_type ? '' : 'display: none;'; ?>">
											<label for="wpr-trigger-value-scroll" class="wpr-block wpr-text-sm wpr-font-bold wpr-text-slate-700 wpr-mb-2"><?php esc_html_e( 'Scroll Percentage (%)', 'wprobo-engage-lite' ); ?></label>
											<input type="number" id="wpr-trigger-value-scroll" min="0" max="100" step="1" value="<?php echo 'scroll_depth' === $trigger_type ? esc_attr( $trigger_value ) : '50'; ?>" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; height: 44px; outline: none; border-color: #cbd5e1;">
											<p class="wpr-text-xs wpr-text-slate-500 wpr-mt-2" style="line-height: 1.5;">
												<?php esc_html_e( 'Percentage of page scrolled before showing the popup.', 'wprobo-engage-lite' ); ?>
											</p>
										</div>
									</div>

									<!-- Trigger Info Box — content populated by JS based on selected trigger type -->
									<div id="wpr-trigger-info-box">
										<div style="background-color: #f0f9ff; border: 1px solid #e0f2fe; padding: 16px; border-radius: 10px; display: flex; align-items: flex-start; gap: 12px;">
											<div id="wpr-trigger-info-icon" style="color: #3b82f6; flex-shrink: 0; margin-top: 2px; font-size: 20px; line-height: 1;" aria-hidden="true"></div>
											<div style="flex: 1;">
												<div id="wpr-trigger-info-title" style="font-size: 13px; font-weight: 700; color: #0369a1; margin-bottom: 4px;"></div>
												<p id="wpr-trigger-info-desc" class="wpr-text-sm" style="margin: 0 0 6px; color: #0369a1; line-height: 1.6;"></p>
												<p id="wpr-trigger-info-tips" class="wpr-text-xs" style="margin: 0; color: #0284c7; line-height: 1.5; font-style: italic;"></p>
											</div>
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>

					<!-- Urgency Tab (Pro) -->
					<div id="wpr-tab-urgency" role="tabpanel" aria-labelledby="wpr-tab-btn-urgency" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Countdown Timers', 'wprobo-engage-lite' ), __( 'Add urgency with fixed, evergreen, and daily recurring countdown timers.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

					<!-- Display Rules Tab -->
					<div id="wpr-tab-display-rules" role="tabpanel" aria-labelledby="wpr-tab-btn-display-rules" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<div class="wpr-space-y-6">
							<!-- Display Rules Header -->
							<div style="background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); padding: 24px; border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
								<h3 class="wpr-text-lg wpr-font-bold wpr-mb-2" style="display: flex; align-items: center; gap: 8px; color: #ffffff;">
									<svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
									</svg>
									<?php esc_html_e( 'Display Rules', 'wprobo-engage-lite' ); ?>
								</h3>
								<p class="wpr-text-sm wpr-opacity-90" style="color: #ffffff;">
									<?php esc_html_e( 'Precision target where and when your campaign appears across your site.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<!-- Advanced Mode Toggle -->
							<div style="background: #ffffff; padding: 24px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<label style="display: flex; align-items: flex-start; gap: 14px; cursor: pointer;">
									<input type="checkbox" id="wpr-use-rule-groups" value="1" style="width: 20px; height: 20px; margin-top: 2px;" <?php checked( get_post_meta( $post->ID, '_wpr_engage_use_rule_groups', true ), '1' ); ?> />
									<div>
										<span class="wpr-text-sm wpr-font-bold wpr-text-slate-800">
											<?php esc_html_e( 'Advanced Logic (AND/OR)', 'wprobo-engage-lite' ); ?>
										</span>
										<p class="wpr-text-xs wpr-text-slate-500 wpr-mt-1" style="line-height: 1.5;">
											<?php esc_html_e( 'Create complex rule groups with custom logical relationships between multiple conditions.', 'wprobo-engage-lite' ); ?>
										</p>
									</div>
								</label>
							</div>

							<!-- Rule Templates -->
							<div class="wpr-rule-templates wpr-simple-mode-only" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<details>
									<summary style="padding: 16px 24px; background: #f8fafc; cursor: pointer; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0;">
										<div style="display: flex; align-items: center; gap: 10px;">
											<span style="font-size: 1.1rem;">📋</span>
											<span class="wpr-text-sm wpr-font-bold wpr-text-slate-700"><?php esc_html_e( 'Quick Start: Rule Templates', 'wprobo-engage-lite' ); ?></span>
										</div>
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px; color: #64748b;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
										</svg>
									</summary>
									<div style="padding: 24px;">
										<p class="wpr-text-xs wpr-text-slate-500 wpr-mb-4">
											<?php esc_html_e( 'Apply battle-tested targeting rules for the most common conversion scenarios.', 'wprobo-engage-lite' ); ?>
										</p>
										<div id="wpr-rule-templates-list" class="wpr-grid wpr-grid-cols-2 wpr-gap-3" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
											<!-- Templates will be dynamically loaded here -->
										</div>
									</div>
								</details>
							</div>

							<!-- Simple Mode Container -->
							<div id="wpr-simple-rules-container" class="wpr-simple-mode-only" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<div id="wpr-display-rules-container" class="wpr-mb-6">
									<!-- Rules will be dynamically added here -->
								</div>

								<div style="display: flex; flex-direction: column; gap: 12px;">
									<button type="button" id="wpr-add-show-rule" class="wpr-w-full wpr-py-3 wpr-px-4 wpr-bg-green-600 wpr-text-white wpr-text-xs wpr-font-bold wpr-rounded-lg hover:wpr-bg-green-700 wpr-transition-colors" style="border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
										</svg>
										<?php esc_html_e( 'ADD SHOW RULE', 'wprobo-engage-lite' ); ?>
									</button>
									<button type="button" id="wpr-add-hide-rule" class="wpr-w-full wpr-py-3 wpr-px-4 wpr-bg-red-600 wpr-text-white wpr-text-xs wpr-font-bold wpr-rounded-lg hover:wpr-bg-red-700 wpr-transition-colors" style="border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
										</svg>
										<?php esc_html_e( 'ADD HIDE RULE', 'wprobo-engage-lite' ); ?>
									</button>
								</div>
							</div>

							<!-- Advanced Mode Container (Rule Groups) -->
							<div id="wpr-rule-groups-container" class="wpr-advanced-mode-only" style="display: none; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<div id="wpr-rule-groups-list" class="wpr-mb-6">
									<!-- Rule groups will be dynamically added here -->
								</div>

								<div style="display: flex; flex-direction: column; gap: 12px;">
									<button type="button" id="wpr-add-show-group" class="wpr-w-full wpr-py-3 wpr-px-4 wpr-bg-green-600 wpr-text-white wpr-text-xs wpr-font-bold wpr-rounded-lg hover:wpr-bg-green-700 wpr-transition-colors" style="border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
										</svg>
										<?php esc_html_e( 'ADD SHOW GROUP', 'wprobo-engage-lite' ); ?>
									</button>
									<button type="button" id="wpr-add-hide-group" class="wpr-w-full wpr-py-3 wpr-px-4 wpr-bg-red-600 wpr-text-white wpr-text-xs wpr-font-bold wpr-rounded-lg hover:wpr-bg-red-700 wpr-transition-colors" style="border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
										</svg>
										<?php esc_html_e( 'ADD HIDE GROUP', 'wprobo-engage-lite' ); ?>
									</button>
								</div>
							</div>

							<!-- Rule Preview -->
							<div class="wpr-rule-preview" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
								<h4 class="wpr-text-sm wpr-font-bold wpr-text-slate-800 wpr-mb-4" style="display: flex; align-items: center; gap: 10px;">
									<div style="background: #f1f5f9; padding: 8px; border-radius: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; color: #3b82f6;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
										</svg>
									</div>
									<?php esc_html_e( 'Live Targeting Summary', 'wprobo-engage-lite' ); ?>
								</h4>
								<div id="wpr-rule-preview-content" class="wpr-text-xs wpr-text-slate-600" style="line-height: 1.6;">
									<p class="wpr-italic" style="color: #94a3b8;"><?php esc_html_e( 'No specific rules configured. This campaign is currently set to display site-wide.', 'wprobo-engage-lite' ); ?></p>
								</div>
							</div>
						</div>
					</div>

					<!-- Success Tab -->
					<div id="wpr-tab-after-success" role="tabpanel" aria-labelledby="wpr-tab-btn-after-success" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 24px;">
							
							<!-- Success Header Card -->
							<div style="background: linear-gradient(135deg, #0f172a 0%, #1e40af 100%); padding: 24px; border-radius: 12px; color: white; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
								<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
									<svg xmlns="http://www.w3.org/2000/svg" style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
									</svg>
									<h3 class="wpr-text-lg wpr-font-bold" style="margin: 0; color: #ffffff;"><?php esc_html_e( 'Success Strategy', 'wprobo-engage-lite' ); ?></h3>
								</div>
								<p class="wpr-text-sm wpr-opacity-90" style="margin: 0; color: #ffffff; line-height: 1.5;">
									<?php esc_html_e( 'Choose what happens after a visitor successfully subscribes to your campaign.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<!-- Action Selection Cards -->
							<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<label class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-4" style="display: block; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e( 'Display Mode', 'wprobo-engage-lite' ); ?></label>
								<div class="wpr-grid wpr-gap-4" style="display: grid; grid-template-columns: 1fr; gap: 16px;">
									<!-- Action: Message -->
									<label style="position: relative; cursor: pointer; display: block;">
										<input type="radio" name="wpr-success-action" value="message" <?php checked( $success_action, 'message' ); ?> style="position: absolute; opacity: 0; width: 0; height: 0;" class="wpr-success-peer">
										<div style="padding: 24px 20px; border-radius: 12px; height: 100%; border: 2px solid #e2e8f0; background: #ffffff; transition: all 0.2s;" class="wpr-success-radio-card">
											<div style="display: flex; align-items: flex-start; gap: 14px;">
												<div class="wpr-icon-wrapper" style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #3b82f6; transition: all 0.2s; flex-shrink: 0;">
													<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
												</div>
												<div style="flex: 1;">
													<span class="wpr-card-title" style="display: block; font-size: 15px; font-weight: 700; color: #1e293b; transition: color 0.1s;"><?php esc_html_e( 'Show Message', 'wprobo-engage-lite' ); ?></span>
													<span style="display: block; font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5;"><?php esc_html_e( 'Display a custom success message or discount code.', 'wprobo-engage-lite' ); ?></span>
												</div>
											</div>
										</div>
									</label>
									<!-- Action: Redirect -->
									<label style="position: relative; cursor: pointer; display: block;">
										<input type="radio" name="wpr-success-action" value="redirect" <?php checked( $success_action, 'redirect' ); ?> style="position: absolute; opacity: 0; width: 0; height: 0;" class="wpr-success-peer">
										<div style="padding: 24px 20px; border-radius: 12px; height: 100%; border: 2px solid #e2e8f0; background: #ffffff; transition: all 0.2s;" class="wpr-success-radio-card">
											<div style="display: flex; align-items: flex-start; gap: 14px;">
												<div class="wpr-icon-wrapper" style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #8b5cf6; transition: all 0.2s; flex-shrink: 0;">
													<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
												</div>
												<div style="flex: 1;">
													<span class="wpr-card-title" style="display: block; font-size: 15px; font-weight: 700; color: #1e293b; transition: color 0.1s;"><?php esc_html_e( 'Redirect to URL', 'wprobo-engage-lite' ); ?></span>
													<span style="display: block; font-size: 12px; color: #64748b; margin-top: 6px; line-height: 1.5;"><?php esc_html_e( 'Send visitors to a thank you page or special offer.', 'wprobo-engage-lite' ); ?></span>
												</div>
											</div>
										</div>
									</label>
								</div>
							</div>

							<!-- Settings for "Show Message" -->
							<div id="wpr-success-message-config" class="wpr-space-y-6" style="<?php echo ( 'message' !== $success_action ) ? 'display: none;' : ''; ?>">
								
								<!-- Card: Message Content & Icons -->
								<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
									<h4 style="margin: 0 0 24px 0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
										<?php esc_html_e( 'Success Message Content', 'wprobo-engage-lite' ); ?>
									</h4>

									<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 20px;">
										<div>
											<label for="wpr-success-message-headline" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase; letter-spacing: 0.025em;"><?php esc_html_e( 'Success Headline', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-success-message-headline" value="<?php echo esc_attr( $success_message_headline ); ?>" placeholder="Thank you!" class="wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; height: 44px; outline: none; border: 1px solid #cbd5e1; font-size: 14px; color: #1e293b;">
										</div>

										<div>
											<label for="wpr-success-message-content" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase; letter-spacing: 0.025em;"><?php esc_html_e( 'Success Message Text', 'wprobo-engage-lite' ); ?></label>
											<textarea id="wpr-success-message-content" rows="3" placeholder="Your subscription has been confirmed." class="wpr-w-full wpr-border wpr-border-slate-200 wpr-rounded-lg wpr-p-3" style="width: 100%; outline: none; border: 1px solid #cbd5e1; font-size: 14px; color: #1e293b; padding: 12px;"><?php echo esc_textarea( $success_message_content ); ?></textarea>
											<p style="margin-top: 8px; font-size: 11px; color: #64748b;">
												<?php esc_html_e( 'Supports placeholders like {first_name} and {email}', 'wprobo-engage-lite' ); ?>
											</p>
										</div>

										<div style="border-top: 1px dashed #e2e8f0; padding-top: 20px;">
											<label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 16px;">
												<input type="checkbox" id="wpr-success-show-icon" value="1" <?php checked( $success_show_icon, '1' ); ?> style="width: 18px; height: 18px; border-radius: 4px; border-color: #cbd5e1;">
												<span style="font-size: 13px; font-weight: 600; color: #475569;"><?php esc_html_e( 'Show visual success indicator (Icon)', 'wprobo-engage-lite' ); ?></span>
											</label>

											<div id="wpr-success-icon-options" class="wpr-grid wpr-grid-cols-2 wpr-gap-4" style="display: <?php echo ( '1' !== $success_show_icon ) ? 'none' : 'grid'; ?>; grid-template-columns: 1fr 1fr; gap: 16px; margin-left: 28px;">
												<div>
													<label for="wpr-success-icon-type" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block;"><?php esc_html_e( 'Icon Style', 'wprobo-engage-lite' ); ?></label>
													<select id="wpr-success-icon-type" style="height: 38px; border-radius: 6px; border: 1px solid #cbd5e1; width: 100%; color: #334155;">
														<option value="checkmark" <?php selected( $success_icon_type, 'checkmark' ); ?>><?php esc_html_e( '✓ Checkmark', 'wprobo-engage-lite' ); ?></option>
														<option value="star" <?php selected( $success_icon_type, 'star' ); ?>><?php esc_html_e( '★ Star', 'wprobo-engage-lite' ); ?></option>
														<option value="heart" <?php selected( $success_icon_type, 'heart' ); ?>><?php esc_html_e( '♥ Heart', 'wprobo-engage-lite' ); ?></option>
														<option value="thumbs-up" <?php selected( $success_icon_type, 'thumbs-up' ); ?>><?php esc_html_e( '👍 Thumbs Up', 'wprobo-engage-lite' ); ?></option>
														<option value="celebration" <?php selected( $success_icon_type, 'celebration' ); ?>><?php esc_html_e( '🎉 Celebration', 'wprobo-engage-lite' ); ?></option>
													</select>
												</div>
												<div>
													<label for="wpr-success-icon-color" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block;"><?php esc_html_e( 'Icon Color', 'wprobo-engage-lite' ); ?></label>
													<input type="text" id="wpr-success-icon-color" value="<?php echo esc_attr( $success_icon_color ); ?>" class="wpr-color-picker">
												</div>
											</div>
										</div>
									</div>
								</div>

								<!-- Card: Appearance & Styling -->
								<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
									<h4 style="margin: 0 0 24px 0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2v20"/><path d="M2 12h20"/></svg>
										<?php esc_html_e( 'Appearance & Styling', 'wprobo-engage-lite' ); ?>
									</h4>

									<div style="display: flex; flex-direction: column; gap: 20px;">
										<!-- Headline row: color, size, weight -->
										<div>
											<label class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase; font-size: 10px; margin-bottom: 8px;"><?php esc_html_e( 'Headline', 'wprobo-engage-lite' ); ?></label>
											<div style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
												<div>
													<input type="text" id="wpr-success-title-color" value="<?php echo esc_attr( $success_title_color ); ?>" class="wpr-color-picker">
												</div>
												<div style="width: 60px;">
													<input type="number" id="wpr-success-title-font-size" value="<?php echo esc_attr( $success_title_font_size ); ?>" min="12" max="72" style="width: 100%; height: 34px; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0 6px; font-size: 13px;" title="<?php esc_attr_e( 'Font size (px)', 'wprobo-engage-lite' ); ?>">
												</div>
												<div style="min-width: 0; flex: 1;">
													<select id="wpr-success-title-font-weight" style="width: 100%; height: 34px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
														<option value="normal" <?php selected( $success_title_font_weight, 'normal' ); ?>><?php esc_html_e( 'Normal', 'wprobo-engage-lite' ); ?></option>
														<option value="bold" <?php selected( $success_title_font_weight, 'bold' ); ?>><?php esc_html_e( 'Bold', 'wprobo-engage-lite' ); ?></option>
														<option value="600" <?php selected( $success_title_font_weight, '600' ); ?>><?php esc_html_e( 'Semi-Bold', 'wprobo-engage-lite' ); ?></option>
													</select>
												</div>
											</div>
										</div>
										<!-- Content row: color, size, weight -->
										<div>
											<label class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase; font-size: 10px; margin-bottom: 8px;"><?php esc_html_e( 'Content Text', 'wprobo-engage-lite' ); ?></label>
											<div style="display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;">
												<div>
													<input type="text" id="wpr-success-content-color" value="<?php echo esc_attr( $success_content_color ); ?>" class="wpr-color-picker">
												</div>
												<div style="width: 60px;">
													<input type="number" id="wpr-success-content-font-size" value="<?php echo esc_attr( $success_content_font_size ); ?>" min="10" max="48" style="width: 100%; height: 34px; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0 6px; font-size: 13px;" title="<?php esc_attr_e( 'Font size (px)', 'wprobo-engage-lite' ); ?>">
												</div>
												<div style="min-width: 0; flex: 1;">
													<select id="wpr-success-content-font-weight" style="width: 100%; height: 34px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 13px;">
														<option value="normal" <?php selected( $success_content_font_weight, 'normal' ); ?>><?php esc_html_e( 'Normal', 'wprobo-engage-lite' ); ?></option>
														<option value="bold" <?php selected( $success_content_font_weight, 'bold' ); ?>><?php esc_html_e( 'Bold', 'wprobo-engage-lite' ); ?></option>
														<option value="600" <?php selected( $success_content_font_weight, '600' ); ?>><?php esc_html_e( 'Semi-Bold', 'wprobo-engage-lite' ); ?></option>
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>

								<!-- Card: Rewards & Incentives -->
								<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
									<h4 style="margin: 0 0 24px 0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
										<?php esc_html_e( 'Rewards & Incentives', 'wprobo-engage-lite' ); ?>
									</h4>

									<?php if ( class_exists( 'WooCommerce' ) ) : ?>
										<div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 10px; padding: 16px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px;">
											<div style="color: #f59e0b; padding-top: 2px;">
												<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
											</div>
											<p style="margin: 0; font-size: 12px; color: #92400e; line-height: 1.5;">
												<?php esc_html_e( 'Provide a discount code to visitors immediately after they subscribe. Pair this with a matching WooCommerce coupon so the code works at checkout.', 'wprobo-engage-lite' ); ?>
											</p>
										</div>
									<?php else : ?>
										<div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 12px;">
											<div style="color: #2563eb; padding-top: 2px;">
												<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
											</div>
											<div style="font-size: 12px; color: #1e40af; line-height: 1.5;">
												<strong style="display: block; margin-bottom: 4px;"><?php esc_html_e( 'WooCommerce is not active', 'wprobo-engage-lite' ); ?></strong>
												<?php
												printf(
													/* translators: %s: WooCommerce plugin install link */
													esc_html__( 'You can still show any coupon code text here, but automatic redemption at checkout requires WooCommerce. %s to enable full rewards.', 'wprobo-engage-lite' ),
													'<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '" style="color: #1d4ed8; font-weight: 600;">' . esc_html__( 'Install WooCommerce', 'wprobo-engage-lite' ) . '</a>'
												);
												?>
											</div>
										</div>
									<?php endif; ?>

									<div class="wpr-grid wpr-grid-cols-2 wpr-gap-6" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
										<div>
											<label for="wpr-discount-code" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase;"><?php esc_html_e( 'Coupon Code', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-discount-code" value="<?php echo esc_attr( $discount_code ); ?>" placeholder="SAVE20" style="width: 100%; height: 44px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 700; color: #1e293b; background: #f8fafc; text-transform: uppercase; letter-spacing: 1px;">
										</div>
										<div>
											<label for="wpr-discount-code-label" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase;"><?php esc_html_e( 'Display Label', 'wprobo-engage-lite' ); ?></label>
											<input type="text" id="wpr-discount-code-label" value="<?php echo esc_attr( $discount_code_label ); ?>" placeholder="Use discount code:" style="width: 100%; height: 44px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
										</div>
									</div>
								</div>

							</div>

							<!-- Settings for "Redirect to URL" -->
							<div id="wpr-redirect-url-container" class="wpr-space-y-6" style="<?php echo ( 'redirect' !== $success_action ) ? 'display: none;' : ''; ?>">
								
								<!-- Card: Redirect Rules -->
								<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
									<h4 style="margin: 0 0 24px 0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px;">
										<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
										<?php esc_html_e( 'Redirect Configuration', 'wprobo-engage-lite' ); ?>
									</h4>

									<div class="wpr-space-y-6" style="display: flex; flex-direction: column; gap: 20px;">
										<div>
											<label for="wpr-success-redirect-url" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase;"><?php esc_html_e( 'Destination URL', 'wprobo-engage-lite' ); ?></label>
											<div style="position: relative;">
												<input type="url" id="wpr-success-redirect-url" value="<?php echo esc_attr( $success_redirect_url ); ?>" placeholder="https://example.com/thank-you" style="width: 100%; height: 44px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px 0 40px; color: #1e293b;">
												<span style="position: absolute; left: 12px; top: 13px; color: #94a3b8;">
													<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
												</span>
											</div>
										</div>

										<div class="wpr-grid wpr-grid-cols-2 wpr-gap-6" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; padding-top: 10px;">
											<div>
												<label for="wpr-success-redirect-delay" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase;"><?php esc_html_e( 'Redirect Delay', 'wprobo-engage-lite' ); ?></label>
												<div style="display: flex; align-items: center; gap: 10px;">
													<input type="number" id="wpr-success-redirect-delay" value="<?php echo esc_attr( $success_redirect_delay ); ?>" min="0" max="30" style="width: 80px; height: 38px; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0 10px;">
													<span style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Seconds (0 for instant)', 'wprobo-engage-lite' ); ?></span>
												</div>
											</div>
											<div style="display: flex; align-items: flex-end;">
												<label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding-bottom: 8px;">
													<input type="checkbox" id="wpr-success-redirect-new-tab" value="1" <?php checked( $success_redirect_new_tab, '1' ); ?> style="width: 18px; height: 18px; border-radius: 4px; border-color: #cbd5e1;">
													<span style="font-size: 13px; font-weight: 600; color: #475569;"><?php esc_html_e( 'New Window', 'wprobo-engage-lite' ); ?></span>
												</label>
											</div>
										</div>
									</div>
								</div>

							</div>

							<!-- Card: Post-Success Automation (Shared) -->
							<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);">
								<h4 style="margin: 0 0 24px 0; font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 8px;">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="M4.93 4.93l2.83 2.83"/><path d="M16.24 16.24l2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="M4.93 19.07l2.83-2.83"/><path d="M16.24 7.76l2.83-2.83"/></svg>
									<?php esc_html_e( 'Post-Success Automation', 'wprobo-engage-lite' ); ?>
								</h4>

								<div class="wpr-space-y-4" style="display: flex; flex-direction: column; gap: 16px;">
									<label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
										<input type="checkbox" id="wpr-success-auto-close" value="1" <?php checked( $success_auto_close, '1' ); ?> style="width: 20px; height: 20px; border-radius: 4px; border-color: #cbd5e1;">
										<div style="flex: 1;">
											<span style="display: block; font-size: 14px; font-weight: 700; color: #1e293b;"><?php esc_html_e( 'Auto-close campaign after success', 'wprobo-engage-lite' ); ?></span>
											<span style="display: block; font-size: 11px; color: #64748b; margin-top: 2px;"><?php esc_html_e( 'Automatically hide the popup after the subscriber sees the success message.', 'wprobo-engage-lite' ); ?></span>
										</div>
									</label>

									<div id="wpr-auto-close-delay-container" style="display: <?php echo ( '1' !== $success_auto_close ) ? 'none' : 'block'; ?>; margin-left: 30px; padding-left: 16px; border-left: 2px solid #e2e8f0;">
										<label for="wpr-success-auto-close-delay" class="wpr-block wpr-text-xs wpr-font-bold wpr-text-slate-500 wpr-mb-2" style="display: block; text-transform: uppercase;"><?php esc_html_e( 'Auto-Close Delay', 'wprobo-engage-lite' ); ?></label>
										<div style="display: flex; align-items: center; gap: 12px;">
											<input type="number" id="wpr-success-auto-close-delay" value="<?php echo esc_attr( $success_auto_close_delay ); ?>" min="1" max="60" style="width: 80px; height: 38px; border-radius: 6px; border: 1px solid #cbd5e1; padding: 0 10px;">
											<span style="font-size: 11px; color: #64748b;"><?php esc_html_e( 'Seconds before closing', 'wprobo-engage-lite' ); ?></span>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>

					<!-- A/B Testing Tab (Pro) -->
					<div id="wpr-tab-ab-testing" role="tabpanel" aria-labelledby="wpr-tab-btn-ab-testing" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'A/B Testing', 'wprobo-engage-lite' ), __( 'Split test campaign variations to find the highest-converting design.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

					<!-- Integrations Tab (Pro) -->
					<div id="wpr-tab-integrations" role="tabpanel" aria-labelledby="wpr-tab-btn-integrations" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Direct Integrations', 'wprobo-engage-lite' ), __( 'Connect directly to Mailchimp, ConvertKit, and other email services via API.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

					<!-- Schedule Tab (Pro) -->
					<div id="wpr-tab-schedule" role="tabpanel" aria-labelledby="wpr-tab-btn-schedule" class="wpr-tab-content wpr-hidden" style="padding: 24px; background: white;">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Campaign Scheduling', 'wprobo-engage-lite' ), __( 'Schedule campaigns with start/end dates, days of week, and time ranges.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

				</div>

				<!-- Preview Panel: neutral checkered canvas so popup boundaries are
					visible regardless of the popup's own background color. -->
				<div class="wpr-preview-panel" style="flex: 1; background-color: #e5e7eb; background-image: linear-gradient(45deg, #d1d5db 25%, transparent 25%), linear-gradient(-45deg, #d1d5db 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #d1d5db 75%), linear-gradient(-45deg, transparent 75%, #d1d5db 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0px; padding: 32px; display: flex; flex-direction: column; gap: 12px; align-items: center; justify-content: center; position: sticky; top: 32px; align-self: flex-start; max-height: calc(100vh - 64px); overflow-y: auto;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<div style="font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 700; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 999px; padding: 4px 10px;">
							<?php esc_html_e( 'Live Preview', 'wprobo-engage-lite' ); ?>
						</div>
						<div id="wpr-preview-state-toggle" style="display: flex; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 999px; overflow: hidden; font-size: 10px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase;">
							<button type="button" data-preview-state="default" style="padding: 4px 12px; border: none; cursor: pointer; background: #3b82f6; color: #ffffff; transition: all 0.2s;"><?php esc_html_e( 'Default', 'wprobo-engage-lite' ); ?></button>
							<button type="button" data-preview-state="success" style="padding: 4px 12px; border: none; cursor: pointer; background: transparent; color: #64748b; transition: all 0.2s;"><?php esc_html_e( 'Success', 'wprobo-engage-lite' ); ?></button>
						</div>
					</div>
					<div id="wpr-preview-wrapper" class="wpr-bg-white wpr-shadow-lg wpr-rounded-lg wpr-p-8 wpr-text-center" style="background: white; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 8px; padding: 32px; text-align: center; max-width: 500px; position: relative; border: 0px solid #d1d5db; word-break: break-word; overflow-wrap: break-word;">
						<?php
						$close_shape_radius = '4px';
						if ( $close_btn_shape === 'square' ) {
							$close_shape_radius = '0';
						} elseif ( $close_btn_shape === 'circle' ) {
							$close_shape_radius = '50%';
						}
						?>
					<button id="wpr-preview-close" class="wpr-absolute wpr-text-gray-500" style="position: absolute; top: 8px; right: 8px; z-index: 5; font-size: 20px; line-height: 1; background: <?php echo esc_attr( $close_btn_bg_color ?: '#ffffff' ); ?>; border: 1px solid #e2e8f0; cursor: default; color: <?php echo esc_attr( $close_btn_color ?: '#6b7280' ); ?>; padding: 0; border-radius: <?php echo esc_attr( $close_shape_radius ); ?>; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">&times;</button>
						<!-- Timer Container - Position: above-headline -->
						<div id="wpr-preview-timer-above-headline" class="wpr-timer-preview-container" style="display: none; margin-bottom: 20px;"></div>
						<h2 id="wpr-preview-headline" class="wpr-text-2xl wpr-font-bold wpr-text-gray-900" style="font-size: 24px; font-weight: 700; margin-bottom: 16px; word-break: break-word; <?php echo esc_attr( $form_elem_relative_css ); ?>">
							<?php echo esc_html( $headline ?: 'This is the Headline' ); ?>
						</h2>
						<!-- Timer Container - Position: below-headline -->
						<div id="wpr-preview-timer-below-headline" class="wpr-timer-preview-container" style="display: none; margin-bottom: 16px;"></div>
						<p id="wpr-preview-content" class="wpr-mt-4 wpr-text-gray-600" style="margin-top: 16px; color: #4b5563; word-break: break-word; white-space: pre-wrap; <?php echo esc_attr( $form_elem_relative_css ); ?>">
							<?php echo esc_html( $content ?: 'This is the main content area. Describe your offer here.' ); ?>
						</p>
						<div class="wpr-mt-6" style="margin-top: 24px; <?php echo esc_attr( $form_elem_relative_css ); ?>">
							<!-- Timer Container - Position: above-form -->
							<div id="wpr-preview-timer-above-form" class="wpr-timer-preview-container" style="display: none; margin-bottom: 16px;"></div>
							<input id="wpr-preview-email" type="email" placeholder="<?php echo esc_attr( $email_placeholder ?: 'Enter your email' ); ?>" class="wpr-w-full wpr-max-w-xs wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded" style="width: 100%; max-width: 20rem; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; margin-bottom: 1rem; display: block; margin-left: auto; margin-right: auto;">
							<button id="wpr-preview-button" type="button" style="padding: 10px 24px; border-radius: 6px; border: 1px solid <?php echo esc_attr( $preview_button_bg ); ?>; background: <?php echo esc_attr( $preview_button_bg ); ?>; color: <?php echo esc_attr( $preview_button_text ); ?>; font-size: 14px; font-weight: 600; line-height: 1.2; cursor: default; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.15);">
								<?php echo esc_html( $button ?: 'Subscribe' ); ?>
							</button>
							<!-- Timer Container - Position: below-button -->
							<div id="wpr-preview-timer-below-button" class="wpr-timer-preview-container" style="display: none; margin-top: 16px;"></div>
						</div>

						<!-- Success State Preview (hidden by default, toggled via state toggle) -->
						<div id="wpr-preview-success-state" style="display: none; text-align: center; max-width: 100%; word-break: break-word;">
							<div id="wpr-preview-success-icon" style="font-size: 3rem; margin-bottom: 16px; color: <?php echo esc_attr( $success_icon_color ?: '#059669' ); ?>;">✓</div>
							<h2 id="wpr-preview-success-headline" style="font-size: <?php echo esc_attr( $success_title_font_size ?: '24' ); ?>px; font-weight: <?php echo esc_attr( $success_title_font_weight ?: 'bold' ); ?>; color: <?php echo esc_attr( $success_title_color ?: '#059669' ); ?>; margin-bottom: 12px;">
								<?php echo esc_html( $success_message_headline ?: 'Thank you!' ); ?>
							</h2>
							<p id="wpr-preview-success-content" style="font-size: <?php echo esc_attr( $success_content_font_size ?: '16' ); ?>px; font-weight: <?php echo esc_attr( $success_content_font_weight ?: 'normal' ); ?>; color: <?php echo esc_attr( $success_content_color ?: '#4B5563' ); ?>;"><?php echo esc_html( $success_message_content ?: 'Your subscription has been confirmed.' ); ?></p>
							<div id="wpr-preview-discount-wrapper" style="margin-top: 20px; padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; <?php echo empty( $discount_code ) ? 'display: none;' : ''; ?>">
								<div id="wpr-preview-discount-label" style="color: #ffffff; font-size: 12px; margin-bottom: 8px;"><?php echo esc_html( $discount_code_label ?: 'Use discount code:' ); ?></div>
								<div id="wpr-preview-discount-code" style="background: #ffffff; border-radius: 6px; padding: 10px; font-size: 1.25rem; font-weight: 700; color: #7c3aed;"><?php echo esc_html( $discount_code ); ?></div>
							</div>
						</div>
					</div>
					<p style="margin: 0; font-size: 12px; color: #64748b; text-align: center; max-width: 480px;">
						<?php esc_html_e( 'Changes apply instantly in this preview. Final behavior may vary slightly based on trigger and display rules.', 'wprobo-engage-lite' ); ?>
					</p>
				</div>
			</div>
		</div>

		<!-- AI Modal -->
		<div id="wpr-ai-modal" class="wpr-hidden wpr-fixed wpr-inset-0 wpr-bg-black wpr-bg-opacity-50 wpr-z-50 wpr-flex wpr-items-center wpr-justify-center">
			<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-xl wpr-w-full wpr-max-w-2xl wpr-mx-4">
				<div class="wpr-p-6 wpr-border-b wpr-border-gray-200">
					<div class="wpr-flex wpr-items-center wpr-justify-between">
						<h3 class="wpr-text-lg wpr-font-semibold wpr-text-gray-900 wpr-flex wpr-items-center wpr-gap-2">
							<svg xmlns="http://www.w3.org/2000/svg" class="wpr-h-5 wpr-w-5 wpr-text-purple-600" viewBox="0 0 20 20" fill="currentColor">
								<path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
							</svg>
							<?php esc_html_e( 'AI Copywriting Assistant', 'wprobo-engage-lite' ); ?>
						</h3>
						<button type="button" id="wpr-ai-modal-close" class="wpr-text-gray-400 hover:wpr-text-gray-600">
							<svg xmlns="http://www.w3.org/2000/svg" class="wpr-h-6 wpr-w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
				</div>

				<div class="wpr-p-6">
					<div class="wpr-mb-4">
						<label for="wpr-ai-prompt" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
							<?php esc_html_e( 'Describe what you want to create:', 'wprobo-engage-lite' ); ?>
						</label>
						<textarea id="wpr-ai-prompt" rows="3" class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-3 focus:wpr-ring-2 focus:wpr-ring-purple-500 focus:wpr-border-transparent" placeholder="<?php esc_attr_e( 'e.g., A compelling headline for a 20% discount offer on our new product line', 'wprobo-engage-lite' ); ?>"></textarea>
					</div>

					<div class="wpr-flex wpr-gap-3 wpr-mb-4">
						<button type="button" id="wpr-ai-generate" class="wpr-flex-1 wpr-bg-purple-600 wpr-text-white wpr-py-2 wpr-px-4 wpr-rounded-md wpr-font-medium hover:wpr-bg-purple-700 wpr-flex wpr-items-center wpr-justify-center wpr-gap-2">
							<svg xmlns="http://www.w3.org/2000/svg" class="wpr-h-5 wpr-w-5" viewBox="0 0 20 20" fill="currentColor">
								<path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
							</svg>
							<span><?php esc_html_e( 'Generate', 'wprobo-engage-lite' ); ?></span>
						</button>
					</div>

					<div id="wpr-ai-loading" class="wpr-hidden wpr-text-center wpr-py-8">
						<div class="wpr-inline-block wpr-animate-spin wpr-rounded-full wpr-h-8 wpr-w-8 wpr-border-b-2 wpr-border-purple-600"></div>
						<p class="wpr-mt-2 wpr-text-sm wpr-text-gray-600"><?php esc_html_e( 'Generating suggestions...', 'wprobo-engage-lite' ); ?></p>
					</div>

					<div id="wpr-ai-error" class="wpr-hidden wpr-bg-red-50 wpr-border wpr-border-red-200 wpr-text-red-700 wpr-px-4 wpr-py-3 wpr-rounded-md wpr-text-sm">
					</div>

					<div id="wpr-ai-results" class="wpr-hidden wpr-space-y-2">
						<p class="wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-3"><?php esc_html_e( 'AI Suggestions (click to use):', 'wprobo-engage-lite' ); ?></p>
						<div id="wpr-ai-suggestions" class="wpr-space-y-2">
							<!-- Suggestions will be inserted here -->
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php
		// Pass display rules data to JS via wp_add_inline_script (no inline <script> tags).
		$inline_data = 'var wprDisplayRules = ' . wp_json_encode( $display_rules ) . ';'
			. 'var wprRuleGroups = ' . wp_json_encode( $rule_groups ) . ';'
			. 'var wprRuleTemplates = ' . wp_json_encode( \WPRobo_Engage_Lite\Admin\Rule_Templates::get_templates() ) . ';';
		wp_add_inline_script( 'wprobo-engage-admin', $inline_data, 'before' );
	}

	/**
	 * Saves the meta box data when the post is saved.
	 * Hooked into 'save_post'.
	 *
	 * @param int $post_id The ID of the post being saved.
	 * @return void
	 */
	public function save_meta_data( int $post_id ): void {
		// 1. Check if our nonce is set and valid.
		if ( ! isset( $_POST['wprobo_engage_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wprobo_engage_meta_box_nonce'] ) ), 'wprobo_engage_save_meta_box_data' ) ) {
			return;
		}

		// 2. If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// 3. Check the user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// 4. Make sure we are saving for our 'wpr_campaign' post type.
		if ( ! isset( $_POST['post_type'] ) || 'wpr_campaign' !== $_POST['post_type'] ) {
			return;
		}

		// 5. Sanitize and save the data.
		$fields = array(
			'wpr_engage_campaign_type'  => '_wpr_engage_campaign_type',
			'wpr_engage_bar_position'   => '_wpr_engage_bar_position',
			'wpr_engage_slide_position' => '_wpr_engage_slide_position',
			'wpr_engage_form_type'      => '_wpr_engage_form_type',
			'wpr_engage_embed_provider' => '_wpr_engage_embed_provider',
		);

		foreach ( $fields as $key => $meta_key ) {
			if ( array_key_exists( $key, $_POST ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Save campaign status.
		if ( array_key_exists( 'wpr_engage_campaign_status', $_POST ) ) {
			$requested_status = sanitize_text_field( wp_unslash( $_POST['wpr_engage_campaign_status'] ) );
			update_post_meta( $post_id, '_wpr_engage_campaign_status', $requested_status );
		}

		// Save form fields array — sanitize each sub-field individually so the
		// sniffer can verify coverage without a broad phpcs:ignore suppression.
		if ( isset( $_POST['wpr_engage_form_fields'] ) && is_array( $_POST['wpr_engage_form_fields'] ) ) {
			$raw_fields  = (array) wp_unslash( $_POST['wpr_engage_form_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each sub-field is sanitized individually in the loop below.
			$form_fields = array();
			foreach ( $raw_fields as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$form_fields[] = array(
					'type'        => sanitize_text_field( $field['type'] ?? 'text' ),
					'label'       => sanitize_text_field( $field['label'] ?? '' ),
					'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
					'required'    => ! empty( $field['required'] ),
				);
			}
			update_post_meta( $post_id, '_wpr_engage_form_fields', $form_fields );
		}

		// Save embed code with wp_kses to allow safe HTML/JS.
		if ( isset( $_POST['wpr_engage_embed_code'] ) ) {
			$allowed_html = array(
				'form'     => array(
					'action' => true,
					'method' => true,
					'class'  => true,
					'id'     => true,
					'target' => true,
				),
				'input'    => array(
					'type'        => true,
					'name'        => true,
					'value'       => true,
					'placeholder' => true,
					'class'       => true,
					'id'          => true,
					'required'    => true,
				),
				'textarea' => array(
					'name'        => true,
					'placeholder' => true,
					'class'       => true,
					'id'          => true,
					'rows'        => true,
					'required'    => true,
				),
				'button'   => array(
					'type'  => true,
					'class' => true,
					'id'    => true,
				),
				'label'    => array(
					'for'   => true,
					'class' => true,
				),
				'div'      => array(
					'class' => true,
					'id'    => true,
					'style' => true,
				),
				'p'        => array(
					'class' => true,
				),
				'span'     => array(
					'class' => true,
				),
				'noscript' => array(),
			);
			$embed_code   = wp_kses( wp_unslash( $_POST['wpr_engage_embed_code'] ), $allowed_html );
			update_post_meta( $post_id, '_wpr_engage_embed_code', $embed_code );
		}

	}

}
