<?php

if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template variables set by Display_Engine via extract().
/**
 * Template for displaying a floating bar campaign.
 *
 * The following variables are available in this template:
 * @var string $headline
 * @var string $content
 * @var string $button
 * @var string $email_placeholder
 * @var string $bg_color
 * @var string $headline_color
 * @var string $button_bg_color
 * @var string $button_text_color
 * @var string $position
 */

$position = isset( $position ) && 'bottom' === $position ? 'bottom' : 'top';
$position_class = 'top' === $position ? 'wpr-top-0' : 'wpr-bottom-0';

// Build close button styles (consistent with popup/slide-in).
$close_btn_styles = [];
$close_btn_styles[] = 'color: ' . esc_attr( $close_btn_color ?: '#6b7280' );
$close_btn_styles[] = 'background-color: ' . esc_attr( $close_btn_bg_color ?: '#ffffff' );

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
$close_btn_styles[] = 'cursor: pointer';
$close_btn_styles[] = 'flex-shrink: 0';
$close_btn_style_attr = implode( '; ', $close_btn_styles );
?>
<div id="wpr-engage-<?php echo esc_attr( $campaign_id ); ?>" class="wpr-engage-campaign">
<div id="wpr-engage-bar-container" class="wpr-hidden wpr-fixed wpr-left-0 wpr-right-0 <?php echo esc_attr( $position_class ); ?>" data-campaign-id="<?php echo esc_attr( get_the_ID() ); ?>" style="z-index: 99999; <?php echo $bg_color ? 'background-color: ' . esc_attr( $bg_color ) . ';' : ''; ?>">
	<div class="wpr-max-w-7xl wpr-mx-auto wpr-px-4 wpr-py-3 sm:wpr-px-6 lg:wpr-px-8">
		<div class="wpr-flex wpr-items-center wpr-justify-between wpr-flex-wrap">
			<div class="wpr-flex wpr-items-center wpr-flex-1">
				<div id="wpr-engage-view-main" class="wpr-flex wpr-items-center wpr-justify-between wpr-w-full">
					<div class="wpr-flex wpr-items-center wpr-gap-4 wpr-flex-1">
						<div class="wpr-flex-1">
							<p class="wpr-text-lg wpr-font-bold wpr-m-0" style="<?php echo $headline_color ? 'color: ' . esc_attr( $headline_color ) . ';' : ''; ?>">
								<?php echo esc_html( $headline ); ?>
							</p>
							<?php if ( $content ) : ?>
								<p class="wpr-text-sm wpr-m-0 wpr-mt-1" style="<?php echo $content_color ? 'color: ' . esc_attr( $content_color ) . ';' : ''; ?>">
									<?php echo esc_html( $content ); ?>
								</p>
							<?php endif; ?>
						</div>
						
							<!-- Native form -->
							<form id="wpr-engage-form" class="wpr-flex wpr-items-center wpr-gap-2">
								<?php
								// Render form fields
								if ( ! empty( $form_fields ) && is_array( $form_fields ) ) :
									foreach ( $form_fields as $index => $field ) :
										$field_type = $field['type'] ?? 'text';
										$html_type = ( 'phone' === $field_type ) ? 'tel' : $field_type;
										$field_placeholder = $field['placeholder'] ?? '';
										$field_required = ! empty( $field['required'] );

										if ( 'checkbox' === $field_type ) :
											?>
											<label class="wpr-flex wpr-items-center wpr-text-sm">
												<input type="checkbox"
													   name="wpr_field_<?php echo esc_attr( $index ); ?>"
													   class="wpr-form-field wpr-mr-2"
													   data-field-type="<?php echo esc_attr( $field_type ); ?>"
													   <?php echo $field_required ? 'required' : ''; ?>>
												<?php echo esc_html( $field_placeholder ?: $field['label'] ?? '' ); ?>
											</label>
										<?php else : ?>
											<input type="<?php echo esc_attr( $html_type ); ?>" 
												   name="wpr_field_<?php echo esc_attr( $index ); ?>" 
												   placeholder="<?php echo esc_attr( $field_placeholder ); ?>" 
												   class="wpr-form-field wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded wpr-min-w-[200px]"
												   data-field-type="<?php echo esc_attr( $field_type ); ?>"
												   <?php echo $field_required ? 'required' : ''; ?>>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php else : ?>
									<!-- Fallback to single email field for backward compatibility -->
									<input id="wpr-engage-email" type="email" required placeholder="<?php echo esc_attr( $email_placeholder ?: 'Enter your email' ); ?>" class="wpr-p-2 wpr-border wpr-border-gray-300 wpr-rounded wpr-min-w-[250px]">
								<?php endif; ?>
								<button type="submit" class="wpr-bg-blue-500 wpr-text-white wpr-font-bold wpr-py-2 wpr-px-6 wpr-rounded wpr-whitespace-nowrap" style="<?php echo $button_bg_color ? 'background-color: ' . esc_attr( $button_bg_color ) . ';' : ''; ?><?php echo $button_text_color ? 'color: ' . esc_attr( $button_text_color ) . ';' : ''; ?>">
									<?php echo esc_html( $button ); ?>
								</button>
							</form>
					</div>
					<button id="wpr-engage-bar-close" class="wpr-ml-4" style="<?php echo esc_attr( $close_btn_style_attr ); ?>" data-hover-color="<?php echo esc_attr( $close_btn_hover_color ?: '#1f2937' ); ?>">&times;</button>
				</div>

				<div id="wpr-engage-view-success" class="wpr-hidden wpr-w-full wpr-flex wpr-items-center wpr-justify-between">
					<div class="wpr-flex-1">
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
						<span class="wpr-text-2xl wpr-mr-2" style="color: <?php echo esc_attr( $success_icon_color ); ?>;"><?php echo esc_html( $icon_char ); ?></span>
						<?php endif; ?>
						<div class="wpr-inline">
							<div class="wpr-text-lg wpr-font-bold wpr-m-0 wpr-inline" style="color: <?php echo esc_attr( $success_title_color ); ?>; font-size: <?php echo esc_attr( $success_title_font_size ); ?>px; font-weight: <?php echo esc_attr( $success_title_font_weight ); ?>;">
								<?php echo wp_kses_post( $success_message_headline ); ?>
							</div>
							<div class="wpr-text-sm wpr-m-0 wpr-mt-1 wpr-inline wpr-ml-2" style="color: <?php echo esc_attr( $success_content_color ); ?>; font-size: <?php echo esc_attr( $success_content_font_size ); ?>px; font-weight: <?php echo esc_attr( $success_content_font_weight ); ?>;">
								<?php echo wp_kses_post( $success_message_content ); ?>
							</div>
							<div id="wpr-auto-close-countdown" class="wpr-text-xs wpr-mt-1 wpr-hidden" style="<?php echo $content_color ? 'color: ' . esc_attr( $content_color ) . ';' : ''; ?>"></div>
						</div>
					</div>
					<button id="wpr-engage-bar-close-success" class="wpr-ml-4" style="<?php echo esc_attr( $close_btn_style_attr ); ?>" data-hover-color="<?php echo esc_attr( $close_btn_hover_color ?: '#1f2937' ); ?>">&times;</button>
				</div>
			</div>
			<p id="wpr-engage-error" class="wpr-text-red-500 wpr-text-sm wpr-mt-2 wpr-hidden wpr-w-full wpr-text-center"></p>
		</div>
	</div>
</div>
</div>

