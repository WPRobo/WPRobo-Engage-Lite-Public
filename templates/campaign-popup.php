<?php

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables set by Display_Engine via extract().
/**
 * Template for displaying a popup campaign.
 *
 * The following variables are available in this template:
 * @var string $headline
 * @var string $content
 * @var string $button
 * @var string $email_placeholder
 * @var string $bg_color
 * @var string $headline_color
 * @var string $content_color
 * @var string $button_bg_color
 * @var string $button_text_color
 * @var int    $campaign_id
 * @var string $border_radius
 * @var string $border_width
 * @var string $border_color
 * @var string $box_shadow_enabled
 * @var string $box_shadow_color
 * @var string $box_shadow_x
 * @var string $box_shadow_y
 * @var string $box_shadow_blur
 * @var string $box_shadow_spread
 * @var string $bg_image_url
 * @var string $bg_image_repeat
 * @var string $bg_image_position
 * @var string $bg_image_size
 * @var string $close_btn_color
 * @var string $close_btn_hover_color
 * @var string $close_btn_bg_color
 * @var string $close_btn_shape
 * @var string $esc_to_close
 * @var string $show_close_icon
 */

// Build inline styles for the popup wrapper
$popup_styles = [];

if ( $bg_color ) {
	$popup_styles[] = 'background-color: ' . esc_attr( $bg_color );
}

// Handle background media based on type
$bg_media_type = $bg_media_type ?: 'image'; // Default to image for backward compatibility
if ( $bg_media_type === 'image' && $bg_image_url ) {
	$popup_styles[] = 'background-image: url(' . esc_url( $bg_image_url ) . ')';
	$popup_styles[] = 'background-repeat: ' . esc_attr( $bg_image_repeat ?: 'no-repeat' );
	$popup_styles[] = 'background-position: ' . esc_attr( $bg_image_position ?: 'center' );
	$popup_styles[] = 'background-size: ' . esc_attr( $bg_image_size ?: 'cover' );
}

$border_radius_value = $border_radius ?: '8';
$popup_styles[] = 'border-radius: ' . esc_attr( $border_radius_value ) . 'px';

$border_width_value = $border_width ?: '0';
if ( $border_width_value > 0 ) {
	$popup_styles[] = 'border: ' . esc_attr( $border_width_value ) . 'px solid ' . esc_attr( $border_color ?: '#d1d5db' );
}

if ( $box_shadow_enabled === '1' ) {
	$shadow_x = $box_shadow_x ?: '0';
	$shadow_y = $box_shadow_y ?: '10';
	$shadow_blur = $box_shadow_blur ?: '15';
	$shadow_spread = $box_shadow_spread ?: '-3';
	$shadow_color = $box_shadow_color ?: '#000000';

	// Convert hex to rgba for shadow
	list( $r, $g, $b ) = sscanf( $shadow_color, '#%02x%02x%02x' );
	$rgba = "rgba($r, $g, $b, 0.1)";

	$popup_styles[] = 'box-shadow: ' . esc_attr( $shadow_x ) . 'px ' . esc_attr( $shadow_y ) . 'px ' . esc_attr( $shadow_blur ) . 'px ' . esc_attr( $shadow_spread ) . 'px ' . $rgba;
}

$popup_style_attr = implode( '; ', $popup_styles );

// Add word-break to popup wrapper
$popup_styles[] = 'word-break: break-word';
$popup_styles[] = 'overflow-wrap: break-word';

$popup_style_attr = implode( '; ', $popup_styles );

// Build close button styles
$close_btn_styles = [];
$close_btn_styles[] = 'color: ' . esc_attr( $close_btn_color ?: '#6b7280' );
$close_btn_styles[] = 'background-color: ' . esc_attr( $close_btn_bg_color ?: '#ffffff' );

// Determine border-radius from shape setting
$close_shape = $close_btn_shape ?: 'rounded';
if ( 'square' === $close_shape ) {
	$close_btn_styles[] = 'border-radius: 0';
} elseif ( 'circle' === $close_shape ) {
	$close_btn_styles[] = 'border-radius: 50%';
} else {
	$close_btn_styles[] = 'border-radius: 4px';
}

$close_btn_styles[] = 'font-size: 20px';
$close_btn_styles[] = 'line-height: 1';
$close_btn_styles[] = 'width: 28px';
$close_btn_styles[] = 'height: 28px';
$close_btn_styles[] = 'padding: 0';
$close_btn_styles[] = 'border: 1px solid #e2e8f0';
$close_btn_styles[] = 'display: flex';
$close_btn_styles[] = 'align-items: center';
$close_btn_styles[] = 'justify-content: center';
$close_btn_styles[] = 'box-shadow: 0 1px 3px rgba(0,0,0,0.1)';
$close_btn_style_attr = implode( '; ', $close_btn_styles );

// Determine if close button should be shown (default: yes)
$show_close = ( $show_close_icon === '' || $show_close_icon === '1' );

// Determine if ESC key should close (default: yes)
$esc_close = ( $esc_to_close === '' || $esc_to_close === '1' ) ? '1' : '0';
?>
<div id="wpr-engage-<?php echo esc_attr( $campaign_id ); ?>" class="wpr-engage-campaign">
<div id="wpr-engage-popup-overlay" class="wpr-hidden wpr-fixed wpr-inset-0 wpr-bg-black wpr-bg-opacity-50 wpr-flex wpr-items-center wpr-justify-center wpr-p-4" style="z-index: 99999;" data-esc-close="<?php echo esc_attr( $esc_close ); ?>">
	<div id="wpr-engage-popup-wrapper" data-campaign-id="<?php echo esc_attr( get_the_ID() ); ?>" class="wpr-bg-white wpr-shadow-lg wpr-p-8 wpr-w-full wpr-max-w-lg wpr-text-center wpr-relative" style="<?php echo esc_attr( $popup_style_attr ); ?>">
		<?php if ( $show_close ) : ?>
			<button id="wpr-engage-popup-close" class="wpr-absolute wpr-text-gray-500 hover:wpr-text-gray-800" style="<?php echo esc_attr( $close_btn_style_attr . '; position: absolute; top: 8px; right: 8px; z-index: 2; cursor: pointer;' ); ?>" data-hover-color="<?php echo esc_attr( $close_btn_hover_color ?: '#1f2937' ); ?>">&times;</button>
		<?php endif; ?>

		<!-- Content wrapper with z-index to appear above video -->
		<div style="position: relative; z-index: 1;">

		<div id="wpr-engage-view-main">
			<h2 class="wpr-text-2xl wpr-font-bold wpr-text-gray-900" style="<?php echo $headline_color ? 'color: ' . esc_attr( $headline_color ) . ';' : ''; ?> word-break: break-word;"><?php echo esc_html( $headline ); ?></h2>
			<p class="wpr-mt-4 wpr-text-gray-600" style="<?php echo $content_color ? 'color: ' . esc_attr( $content_color ) . ';' : ''; ?> word-break: break-word; white-space: pre-wrap;"><?php echo esc_html( $content ); ?></p>

				<!-- Native form -->
				<form id="wpr-engage-form" class="wpr-mt-6 wpr-flex wpr-flex-col wpr-items-center">
					<?php
					// Render form fields
					if ( ! empty( $form_fields ) && is_array( $form_fields ) ) :
						foreach ( $form_fields as $index => $field ) :
							$field_type = $field['type'] ?? 'text';
							$html_type = ( 'phone' === $field_type ) ? 'tel' : $field_type;
							$field_label = $field['label'] ?? '';
							$field_placeholder = $field['placeholder'] ?? '';
							$field_required = ! empty( $field['required'] );

							if ( 'checkbox' === $field_type ) :
								?>
								<label class="wpr-flex wpr-items-center wpr-w-full wpr-max-w-xs wpr-text-sm wpr-mb-3">
									<input type="checkbox"
										   name="wpr_field_<?php echo esc_attr( $index ); ?>"
										   class="wpr-form-field wpr-mr-2"
										   data-field-type="<?php echo esc_attr( $field_type ); ?>"
										   <?php echo $field_required ? 'required' : ''; ?>>
									<?php echo esc_html( $field_label ?: $field_placeholder ); ?>
								</label>
							<?php else : ?>
								<input type="<?php echo esc_attr( $html_type ); ?>"
									   name="wpr_field_<?php echo esc_attr( $index ); ?>"
									   placeholder="<?php echo esc_attr( $field_placeholder ); ?>"
									   class="wpr-form-field wpr-w-full wpr-max-w-xs wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded wpr-mb-3"
									   data-field-type="<?php echo esc_attr( $field_type ); ?>"
									   <?php echo $field_required ? 'required' : ''; ?>>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php else : ?>
						<!-- Fallback to single email field for backward compatibility -->
						<input id="wpr-engage-email" type="email" required placeholder="<?php echo esc_attr( $email_placeholder ?: 'Enter your email' ); ?>" class="wpr-w-full wpr-max-w-xs wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded wpr-mb-3">
					<?php endif; ?>

					<p id="wpr-engage-error" class="wpr-text-red-500 wpr-text-sm wpr-mt-1 wpr-hidden"></p>
					<button type="submit" class="wpr-mt-4 wpr-w-full wpr-max-w-xs wpr-bg-blue-500 wpr-text-white wpr-font-bold wpr-py-2 wpr-px-6 wpr-rounded" style="<?php echo $button_bg_color ? 'background-color: ' . esc_attr( $button_bg_color ) . ';' : ''; ?><?php echo $button_text_color ? 'color: ' . esc_attr( $button_text_color ) . ';' : ''; ?>">
						<?php echo esc_html( $button ); ?>
					</button>
				</form>
		</div>

		<div id="wpr-engage-view-success" class="wpr-hidden" style="text-align: center;">
			<?php if ( '1' === $success_show_icon ) : 
				// Map icon types to their display characters
				$icon_chars = [
					'checkmark' => '✓',
					'star' => '★',
					'heart' => '♥',
					'thumbs-up' => '👍',
					'celebration' => '🎉',
					'check-circle' => '✅',
				];
				$icon_char = $icon_chars[ $success_icon_type ] ?? '✓';
			?>
			<div class="wpr-success-icon wpr-text-6xl wpr-mb-4" style="color: <?php echo esc_attr( $success_icon_color ); ?>;"><?php echo esc_html( $icon_char ); ?></div>
			<?php endif; ?>
			<h2 class="wpr-text-2xl wpr-font-bold" style="color: <?php echo esc_attr( $success_title_color ); ?>; font-size: <?php echo esc_attr( $success_title_font_size ); ?>px; font-weight: <?php echo esc_attr( $success_title_font_weight ); ?>;">
				<?php echo wp_kses_post( $success_message_headline ); ?>
			</h2>
			<div class="wpr-mt-4" style="color: <?php echo esc_attr( $success_content_color ); ?>; font-size: <?php echo esc_attr( $success_content_font_size ); ?>px; font-weight: <?php echo esc_attr( $success_content_font_weight ); ?>;"><?php echo wp_kses_post( $success_message_content ); ?></div>
			<div id="wpr-auto-close-countdown" class="wpr-mt-4 wpr-text-sm wpr-text-gray-500 wpr-hidden"></div>
		</div>
		</div><!-- End content wrapper -->
	</div>
</div>
</div>

