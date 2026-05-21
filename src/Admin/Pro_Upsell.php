<?php

namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pro_Upsell
 *
 * Renders informational cards and badges for Pro-only features.
 * No feature code is gated — this is purely informational UI
 * that describes features available in the separate Pro plugin.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Pro_Upsell {

	/**
	 * Returns a small inline PRO badge.
	 *
	 * @return string HTML badge markup.
	 */
	public static function render_badge(): string {
		return '<span class="wpr-pro-badge">PRO</span>';
	}

	/**
	 * Returns an informational overlay card for a Pro feature.
	 *
	 * @param string $title       Feature name.
	 * @param string $description One-line description.
	 * @return string HTML markup.
	 */
	public static function render_overlay( string $title, string $description ): string {
		$upgrade_url = WPROBO_ENGAGE_LITE_UPGRADE_URL;

		$html  = '<div class="wpr-pro-overlay">';
		$html .= '<div class="wpr-pro-overlay-content">';
		$html .= '<span class="dashicons dashicons-lock wpr-pro-overlay-icon" aria-hidden="true"></span>';
		$html .= '<h3 class="wpr-pro-overlay-title">' . esc_html( $title ) . '</h3>';
		$html .= '<p class="wpr-pro-overlay-desc">' . esc_html( $description ) . '</p>';
		$html .= '<a href="' . esc_url( $upgrade_url ) . '" class="wpr-btn-upgrade" target="_blank" rel="noopener noreferrer">';
		$html .= esc_html__( 'Upgrade to Pro', 'wprobo-engage-lite' );
		$html .= '</a>';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

}
