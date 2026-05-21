<?php
namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Settings
 *
 * Handles the global Settings page with multi-tabbed interface.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Settings {

	/**
	 * Renders the Settings page content with tabs.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		// Handle form submission for all tabs.
		$this->handle_form_submission();

		// Determine which tab to show: POST-submitted tab takes priority,
		// then URL param, then default to 'general'.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- read-only tab selection; nonce verified in handle_form_submission() for actual saves.
		$current_tab = 'general';
		if ( isset( $_POST['wpr_active_tab'] ) ) {
			$current_tab = sanitize_key( wp_unslash( $_POST['wpr_active_tab'] ) );
		} elseif ( isset( $_GET['tab'] ) ) {
			$current_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
		}
		// phpcs:enable
		if ( 'ai' === $current_tab ) {
			$current_tab = 'advanced'; // Legacy URL.
		}

		$pro_badge = ' ' . Pro_Upsell::render_badge();
		$tabs      = array(
			'general'      => __( 'General', 'wprobo-engage-lite' ),
			'integrations' => __( 'Integrations', 'wprobo-engage-lite' ) . $pro_badge,
			'advanced'     => __( 'Advanced', 'wprobo-engage-lite' ),
		);
		?>
		<div class="wpr-wrap wpr-p-6">
			<h1 class="wpr-text-2xl wpr-font-semibold wpr-text-gray-800 wpr-mb-6"><?php esc_html_e( 'Settings', 'wprobo-engage-lite' ); ?></h1>

			<?php
			settings_errors( 'wpr_general_settings' );
			settings_errors( 'wpr_advanced_settings' );
			?>

			<!-- Tab Navigation (client-side switching, no page reload) -->
			<div class="wpr-flex wpr-border-b wpr-border-gray-200 wpr-mb-6" style="display: flex; border-bottom: 1px solid #e5e7eb; gap: 0;">
				<?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
					<button type="button"
						class="wpr-settings-tab"
						data-tab="<?php echo esc_attr( $tab_key ); ?>"
						style="padding: 10px 20px; font-size: 14px; font-weight: 500; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -1px; cursor: pointer; color: #6b7280; transition: color 0.2s, border-color 0.2s;">
						<?php echo wp_kses( $tab_label, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<!-- All tab panels rendered, visibility toggled by JS -->
			<div id="wpr-settings-tab-general" class="wpr-settings-panel">
				<?php $this->render_general_tab(); ?>
			</div>
			<div id="wpr-settings-tab-integrations" class="wpr-settings-panel" style="display: none;">
				<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Third-Party Integrations', 'wprobo-engage-lite' ), __( 'Connect directly to Mailchimp, ConvertKit, and other email marketing services via API.', 'wprobo-engage-lite' ) ) ); ?>
			</div>
			<div id="wpr-settings-tab-advanced" class="wpr-settings-panel" style="display: none;">
				<?php $this->render_advanced_tab(); ?>
			</div>
		</div>

		<?php
		$settings_js = "(function() {
			var currentTab = '" . esc_js( $current_tab ) . "';
			var modified = false;
			var tabs = document.querySelectorAll('.wpr-settings-tab');
			var panels = document.querySelectorAll('.wpr-settings-panel');
			function switchTab(tabKey) {
				panels.forEach(function(p) { p.style.display = 'none'; });
				tabs.forEach(function(t) { t.style.color = '#6b7280'; t.style.borderBottomColor = 'transparent'; });
				var panel = document.getElementById('wpr-settings-tab-' + tabKey);
				if (panel) { panel.style.display = ''; }
				tabs.forEach(function(t) { if (t.getAttribute('data-tab') === tabKey) { t.style.color = '#3b82f6'; t.style.borderBottomColor = '#3b82f6'; } });
				currentTab = tabKey;
			}
			switchTab(currentTab);
			tabs.forEach(function(tab) { tab.addEventListener('click', function() { var target = this.getAttribute('data-tab'); if (target !== currentTab) { switchTab(target); } }); });
			document.querySelectorAll('.wpr-settings-panel input, .wpr-settings-panel select, .wpr-settings-panel textarea').forEach(function(el) { el.addEventListener('change', function() { modified = true; }); el.addEventListener('input', function() { modified = true; }); });
			document.querySelectorAll('.wpr-settings-panel form').forEach(function(form) { form.addEventListener('submit', function() { modified = false; var input = document.createElement('input'); input.type = 'hidden'; input.name = 'wpr_active_tab'; input.value = currentTab; form.appendChild(input); }); });
			window.addEventListener('beforeunload', function(e) { if (modified) { e.preventDefault(); e.returnValue = ''; } });
		})();";
		wp_print_inline_script_tag( $settings_js );
	}

	/**
	 * Handles form submission for all tabs.
	 *
	 * @return void
	 */
	private function handle_form_submission(): void {
		// General tab submission
		if ( isset( $_POST['wpr_general_settings_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpr_general_settings_nonce'] ) ), 'wpr_save_general_settings' ) ) {
			$this->save_general_settings();
		}

		// Advanced tab submission
		if ( isset( $_POST['wpr_advanced_settings_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpr_advanced_settings_nonce'] ) ), 'wpr_save_advanced_settings' ) ) {
			$this->save_advanced_settings();
		}
	}

	/**
	 * Renders the General tab content.
	 *
	 * @return void
	 */
	private function render_general_tab(): void {
		$allowed_roles = get_option( 'wpr_allowed_roles', array( 'administrator' ) );
		if ( ! is_array( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator' );
		}

		$wp_roles = wp_roles();
		$roles    = $wp_roles->get_names();
		?>
		<div class="wpr-p-6 wpr-bg-white wpr-rounded-lg wpr-shadow-md" style="max-width: 800px;">
			<h2 class="wpr-text-xl wpr-font-semibold wpr-mb-4"><?php esc_html_e( 'General Settings', 'wprobo-engage-lite' ); ?></h2>
			
			<form method="post" action="">
				<?php wp_nonce_field( 'wpr_save_general_settings', 'wpr_general_settings_nonce' ); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'User Role Management', 'wprobo-engage-lite' ); ?></label>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'User Role Management', 'wprobo-engage-lite' ); ?></span>
								</legend>
								<?php foreach ( $roles as $role_slug => $role_name ) : ?>
									<label style="display: block; margin-bottom: 8px;">
										<input type="checkbox" 
												name="wpr_allowed_roles[]" 
												value="<?php echo esc_attr( $role_slug ); ?>"
												<?php checked( in_array( $role_slug, $allowed_roles, true ) ); ?>>
										<?php echo esc_html( $role_name ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description">
									<?php esc_html_e( 'Select which user roles can access and manage WPRobo Engage campaigns.', 'wprobo-engage-lite' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>

					</table>

				<?php submit_button( esc_html__( 'Save General Settings', 'wprobo-engage-lite' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the Advanced tab content. Hosts two independent forms:
	 * Data Cleanup on Uninstall, and AI Configuration. Previously the AI
	 * form lived on its own tab but it was only one field, so it was
	 * folded in here — see issue #182.
	 *
	 * Each form keeps its own nonce + save handler so they post
	 * independently.
	 *
	 * @return void
	 */
	private function render_advanced_tab(): void {
		$delete_on_uninstall = get_option( 'wpr_delete_on_uninstall', '0' );
		?>
		<div class="wpr-p-6 wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-mb-6" style="max-width: 800px;">
			<h2 class="wpr-text-xl wpr-font-semibold wpr-mb-4"><?php esc_html_e( 'Data Cleanup', 'wprobo-engage-lite' ); ?></h2>

			<form method="post" action="">
				<?php wp_nonce_field( 'wpr_save_advanced_settings', 'wpr_advanced_settings_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="wpr-delete-on-uninstall"><?php esc_html_e( 'Uninstall Behavior', 'wprobo-engage-lite' ); ?></label>
						</th>
						<td>
							<fieldset>
								<label for="wpr-delete-on-uninstall">
									<input type="checkbox"
											id="wpr-delete-on-uninstall"
											name="wpr_delete_on_uninstall"
											value="1"
											<?php checked( $delete_on_uninstall, '1' ); ?>>
									<?php esc_html_e( 'Delete all data on plugin uninstall', 'wprobo-engage-lite' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Warning: When enabled, all campaigns, leads, settings, and analytics data will be permanently deleted when the plugin is uninstalled. This action cannot be undone.', 'wprobo-engage-lite' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php submit_button( esc_html__( 'Save Data Cleanup Settings', 'wprobo-engage-lite' ) ); ?>
			</form>
		</div>

		<!-- AI Configuration (Pro) -->
		<div style="margin-top: 24px; max-width: 800px;">
			<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'AI Copywriting', 'wprobo-engage-lite' ), __( 'AI-powered headline and content generation with multiple providers.', 'wprobo-engage-lite' ) ) ); ?>
		</div>
		<?php
	}

	/**
	 * Saves the General settings.
	 *
	 * @return void
	 */
	private function save_general_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission() before calling this method.
		$allowed_roles = isset( $_POST['wpr_allowed_roles'] ) && is_array( $_POST['wpr_allowed_roles'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['wpr_allowed_roles'] ) )
			: array();
		// phpcs:enable

		// Always ensure administrator is included
		if ( ! in_array( 'administrator', $allowed_roles, true ) ) {
			$allowed_roles[] = 'administrator';
		}

		update_option( 'wpr_allowed_roles', $allowed_roles );

		add_settings_error(
			'wpr_general_settings',
			'settings_saved',
			esc_html__( 'General settings saved successfully!', 'wprobo-engage-lite' ),
			'updated'
		);
	}

	/**
	 * Saves the Advanced settings.
	 *
	 * @return void
	 */
	private function save_advanced_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission() before calling this method.
		$delete_on_uninstall = isset( $_POST['wpr_delete_on_uninstall'] ) ? '1' : '0';

		update_option( 'wpr_delete_on_uninstall', $delete_on_uninstall );

		add_settings_error(
			'wpr_advanced_settings',
			'settings_saved',
			esc_html__( 'Advanced settings saved successfully!', 'wprobo-engage-lite' ),
			'updated'
		);
	}

}
