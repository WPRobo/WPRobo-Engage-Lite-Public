<?php
namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Menu
 *
 * Handles the creation of the admin menu and pages for the plugin.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Menu {

	/**
	 * Gets the minimum required capability to access the plugin.
	 * Checks user roles against the allowed roles setting.
	 *
	 * @return string The capability required to access the plugin.
	 */
	private function get_required_capability(): string {
		$allowed_roles = get_option( 'wpr_allowed_roles', array( 'administrator' ) );
		if ( ! is_array( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator' );
		}

		// Get current user
		$user = wp_get_current_user();

		// Check if user has any of the allowed roles
		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				// Return a capability that this role definitely has
				// For simplicity, we use 'read' which all roles have, but we'll do manual check
				return 'read';
			}
		}

		// If user doesn't have allowed role, require manage_options (admin only)
		return 'manage_options';
	}

	/**
	 * Checks if current user can access the plugin based on role settings.
	 *
	 * @return bool True if user can access, false otherwise.
	 */
	public function current_user_can_access(): bool {
		$allowed_roles = get_option( 'wpr_allowed_roles', array( 'administrator' ) );
		if ( ! is_array( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator' );
		}

		$user = wp_get_current_user();

		foreach ( $allowed_roles as $role ) {
			if ( in_array( $role, $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Registers the admin menu and submenus.
	 * This method is hooked into the 'admin_menu' action.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		// Check if current user can access based on role settings
		if ( ! $this->current_user_can_access() ) {
			return;
		}

		$capability = 'read'; // Use 'read' since we do manual role checking

		// Add the main top-level menu page
		add_menu_page(
			esc_html__( 'WPRobo Engage', 'wprobo-engage-lite' ),
			esc_html__( 'WPRobo Engage', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage',
			array( $this, 'render_dashboard_page' ),
			'dashicons-megaphone',
			null
		);

		// Add the Dashboard submenu page
		add_submenu_page(
			'wprobo-engage',
			esc_html__( 'Dashboard', 'wprobo-engage-lite' ),
			esc_html__( 'Dashboard', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage', // This makes it the default page for the parent menu
			array( $this, 'render_dashboard_page' )
		);

		// Add the Leads submenu page
		add_submenu_page(
			'wprobo-engage',
			esc_html__( 'Leads', 'wprobo-engage-lite' ),
			esc_html__( 'Leads', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage-leads',
			array( $this, 'render_leads_page' )
		);

		// Add the Settings submenu page
		add_submenu_page(
			'wprobo-engage',
			esc_html__( 'Settings', 'wprobo-engage-lite' ),
			esc_html__( 'Settings', 'wprobo-engage-lite' ),
			'manage_options', // Settings always require admin
			'wprobo-engage-settings',
			array( $this, 'render_settings_page' )
		);

		// Add the Tools submenu page
		add_submenu_page(
			'wprobo-engage',
			esc_html__( 'Tools', 'wprobo-engage-lite' ),
			esc_html__( 'Tools', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage-tools',
			array( $this, 'render_tools_page' )
		);

		// Add the hidden Template Selection page. We set parent_slug to null to hide it.
		add_submenu_page(
			null, // No parent, so it's hidden from menus.
			esc_html__( 'Choose a Template', 'wprobo-engage-lite' ),
			esc_html__( 'Choose a Template', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage-templates',
			array( $this, 'render_template_page' )
		);

		// Add the hidden Builder page. We set parent_slug to null to hide it.
		add_submenu_page(
			null, // No parent, so it's hidden from menus.
			esc_html__( 'Campaign Builder', 'wprobo-engage-lite' ),
			esc_html__( 'Campaign Builder', 'wprobo-engage-lite' ),
			$capability,
			'wprobo-engage-builder',
			array( $this, 'render_builder_page' )
		);

		add_filter( 'parent_file', array( $this, 'highlight_parent_menu' ) );
		add_filter( 'submenu_file', array( $this, 'highlight_submenu_file' ) );
	}

	/**
	 * Highlights the parent menu for hidden submenu pages.
	 *
	 * @param string $parent_file The current parent file.
	 * @return string The modified parent file.
	 */
	public function highlight_parent_menu( $parent_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page context check, no state change.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( in_array( $page, array( 'wprobo-engage-templates', 'wprobo-engage-builder' ), true ) ) {
			return 'wprobo-engage';
		}
		return $parent_file;
	}

	/**
	 * Highlights the submenu item for hidden submenu pages.
	 *
	 * @param string $submenu_file The current submenu file.
	 * @return string The modified submenu file.
	 */
	public function highlight_submenu_file( $submenu_file ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page context check, no state change.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( in_array( $page, array( 'wprobo-engage-templates', 'wprobo-engage-builder' ), true ) ) {
			// Pointing specifically to the dashboard or the main slug
			return 'wprobo-engage';
		}
		return $submenu_file;
	}

	/**
	 * Renders the content for the Dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page(): void {
		// Capability gate — current_user_can_access() already checks role settings.
		if ( ! $this->current_user_can_access() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wprobo-engage-lite' ) );
		}

		global $wpdb;

		/**
		 * Cache the dashboard aggregated stats for 30 minutes.
		 * The transient is invalidated by Analytics_Handler::track_event() when
		 * a new impression or conversion is recorded, so data stays fresh.
		 *
		 * @var array|false $cached_stats
		 */
		$cached_stats = get_transient( 'wpr_engage_dashboard_stats' );

		if ( false !== $cached_stats && is_array( $cached_stats ) ) {
			$total_impressions    = $cached_stats['total_impressions'];
			$total_conversions    = $cached_stats['total_conversions'];
			$current_impressions  = $cached_stats['current_impressions'];
			$previous_impressions = $cached_stats['previous_impressions'];
			$current_conversions  = $cached_stats['current_conversions'];
			$previous_conversions = $cached_stats['previous_conversions'];
			$top_campaigns        = $cached_stats['top_campaigns'];
		} else {
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table, no WP API equivalent.
			// Query total impressions and conversions
			$total_impressions = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s",
					'impression'
				)
			);

			$total_conversions = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s",
					'conversion'
				)
			);

			// Period-over-period comparison (last 30 days vs the 30 days before that)
			$window_current  = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
			$window_previous = gmdate( 'Y-m-d H:i:s', strtotime( '-60 days' ) );

			$current_impressions  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s AND event_date >= %s",
					'impression',
					$window_current
				)
			);
			$previous_impressions = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s AND event_date >= %s AND event_date < %s",
					'impression',
					$window_previous,
					$window_current
				)
			);
			$current_conversions  = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s AND event_date >= %s",
					'conversion',
					$window_current
				)
			);
			$previous_conversions = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE event_type = %s AND event_date >= %s AND event_date < %s",
					'conversion',
					$window_previous,
					$window_current
				)
			);
			// phpcs:enable

			// Query campaigns with their stats
			$campaigns = get_posts(
				array(
					'post_type'      => 'wpr_campaign',
					'posts_per_page' => -1,
					'post_status'    => 'any',
				)
			);

			// Get stats for each campaign
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table.
			$campaign_stats = array();
			foreach ( $campaigns as $campaign ) {
				$impressions = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE campaign_id = %d AND event_type = %s",
						$campaign->ID,
						'impression'
					)
				);

				$conversions = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_analytics WHERE campaign_id = %d AND event_type = %s",
						$campaign->ID,
						'conversion'
					)
				);

				// phpcs:enable
				$conversion_rate = $impressions > 0 ? ( $conversions / $impressions ) * 100 : 0;

				$campaign_stats[] = array(
					'id'              => $campaign->ID,
					'title'           => $campaign->post_title,
					'status'          => $campaign->post_status,
					'impressions'     => $impressions,
					'conversions'     => $conversions,
					'conversion_rate' => $conversion_rate,
				);
			}

			// Sort by impressions (descending) and limit to top 5
			usort(
				$campaign_stats,
				function ( $a, $b ) {
					return $b['impressions'] - $a['impressions'];
				}
			);
			$top_campaigns = array_slice( $campaign_stats, 0, 5 );

			// Persist all computed values in the transient.
			set_transient(
				'wpr_engage_dashboard_stats',
				array(
					'total_impressions'    => $total_impressions,
					'total_conversions'    => $total_conversions,
					'current_impressions'  => $current_impressions,
					'previous_impressions' => $previous_impressions,
					'current_conversions'  => $current_conversions,
					'previous_conversions' => $previous_conversions,
					'top_campaigns'        => $top_campaigns,
				),
				30 * MINUTE_IN_SECONDS
			);
		} // end transient cache block

		// Derived values — computed from the (possibly-cached) raw stats.
		$overall_conversion_rate = $total_impressions > 0 ? ( $total_conversions / $total_impressions ) * 100 : 0;
		$current_rate            = $current_impressions > 0 ? ( $current_conversions / $current_impressions ) * 100 : 0;
		$previous_rate           = $previous_impressions > 0 ? ( $previous_conversions / $previous_impressions ) * 100 : 0;

		/**
		 * Builds the trend indicator HTML for a KPI card.
		 *
		 * Returns a "No previous data" note when the previous period is zero,
		 * otherwise an arrow + percentage delta. All output is pre-escaped.
		 *
		 * @param float|int $current  Current-period value.
		 * @param float|int $previous Previous-period value.
		 * @return string HTML fragment (already escaped).
		 */
		$render_trend = function ( $current, $previous ) {
			if ( $previous <= 0 ) {
				return '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:#9ca3af;margin-top:8px;">'
					. esc_html__( 'No previous data', 'wprobo-engage-lite' ) . '</span>';
			}

			$delta     = $current - $previous;
			$pct       = round( ( $delta / $previous ) * 100, 1 );
			$is_up     = $pct > 0;
			$is_flat   = 0.0 === $pct;
			$color     = $is_flat ? '#6b7280' : ( $is_up ? '#059669' : '#dc2626' );
			$arrow     = $is_flat ? '─' : ( $is_up ? '▲' : '▼' );
			$sign      = $is_up ? '+' : '';
			$sr_prefix = $is_flat ? esc_html__( 'No change', 'wprobo-engage-lite' ) : ( $is_up ? esc_html__( 'Up', 'wprobo-engage-lite' ) : esc_html__( 'Down', 'wprobo-engage-lite' ) );

			return '<span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;color:' . esc_attr( $color ) . ';margin-top:8px;font-weight:600;">'
				. '<span aria-hidden="true">' . esc_html( $arrow ) . '</span>'
				. '<span class="screen-reader-text">' . $sr_prefix . '</span>'
				. esc_html( $sign . $pct ) . '% '
				. '<span style="color:#9ca3af;font-weight:400;">' . esc_html__( 'vs previous 30 days', 'wprobo-engage-lite' ) . '</span>'
				. '</span>';
		};

		?>
		<div class="wpr-wrap wpr-p-6">
			<h1 class="wpr-text-2xl wpr-font-semibold wpr-text-gray-800 wpr-mb-6">
				<?php esc_html_e( 'WPRobo Engage Dashboard', 'wprobo-engage-lite' ); ?>
			</h1>

			<!-- Top Stats Boxes -->
			<div class="wpr-grid wpr-grid-cols-1 md:wpr-grid-cols-3 wpr-gap-6 wpr-mb-8">
				<!-- Total Impressions Box -->
				<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6">
					<div class="wpr-flex wpr-items-center wpr-justify-between">
						<div>
							<p class="wpr-text-sm wpr-font-medium wpr-text-gray-600 wpr-uppercase">
								<?php esc_html_e( 'Total Impressions', 'wprobo-engage-lite' ); ?>
							</p>
							<p class="wpr-text-3xl wpr-font-bold wpr-text-gray-900 wpr-mt-2">
								<?php echo esc_html( number_format_i18n( $total_impressions ) ); ?>
							</p>
							<div><?php echo wp_kses_post( $render_trend( $current_impressions, $previous_impressions ) ); ?></div>
						</div>
						<div class="wpr-bg-blue-100 wpr-rounded-full wpr-p-3">
							<svg class="wpr-w-8 wpr-h-8 wpr-text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
							</svg>
						</div>
					</div>
				</div>

				<!-- Total Conversions Box -->
				<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6">
					<div class="wpr-flex wpr-items-center wpr-justify-between">
						<div>
							<p class="wpr-text-sm wpr-font-medium wpr-text-gray-600 wpr-uppercase">
								<?php esc_html_e( 'Total Conversions', 'wprobo-engage-lite' ); ?>
							</p>
							<p class="wpr-text-3xl wpr-font-bold wpr-text-gray-900 wpr-mt-2">
								<?php echo esc_html( number_format_i18n( $total_conversions ) ); ?>
							</p>
							<div><?php echo wp_kses_post( $render_trend( $current_conversions, $previous_conversions ) ); ?></div>
						</div>
						<div class="wpr-bg-green-100 wpr-rounded-full wpr-p-3">
							<svg class="wpr-w-8 wpr-h-8 wpr-text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
							</svg>
						</div>
					</div>
				</div>

				<!-- Overall Conversion Rate Box -->
				<?php
				// Below this threshold, a conversion rate is statistically
				// meaningless. Showing "0.00%" prominently makes new users
				// think the plugin is broken when campaigns are just warming up.
				$has_enough_data = ( $total_impressions >= 50 && $total_conversions >= 1 );
				?>
				<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6">
					<div class="wpr-flex wpr-items-center wpr-justify-between">
						<div>
							<p class="wpr-text-sm wpr-font-medium wpr-text-gray-600 wpr-uppercase">
								<?php esc_html_e( 'Conversion Rate', 'wprobo-engage-lite' ); ?>
							</p>
							<?php if ( $has_enough_data ) : ?>
								<p class="wpr-text-3xl wpr-font-bold wpr-text-gray-900 wpr-mt-2">
									<?php echo esc_html( number_format_i18n( $overall_conversion_rate, 2 ) ); ?>%
								</p>
							<?php else : ?>
								<p class="wpr-text-xl wpr-font-semibold wpr-text-gray-500 wpr-mt-2" style="font-size: 18px;">
									<?php esc_html_e( 'Collecting data…', 'wprobo-engage-lite' ); ?>
								</p>
								<p class="wpr-text-xs wpr-text-gray-400 wpr-mt-1" style="font-size: 11px; line-height: 1.4;">
									<?php
									printf(
										/* translators: %d: remaining impressions needed */
										esc_html__( 'Need %d more impressions for a reliable rate.', 'wprobo-engage-lite' ),
										(int) max( 0, 50 - (int) $total_impressions )
									);
									?>
								</p>
							<?php endif; ?>
							<div><?php echo wp_kses_post( $render_trend( $current_rate, $previous_rate ) ); ?></div>
						</div>
						<div class="wpr-bg-purple-100 wpr-rounded-full wpr-p-3">
							<svg class="wpr-w-8 wpr-h-8 wpr-text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
							</svg>
						</div>
					</div>
				</div>
			</div>

			<!-- Analytics Chart -->
			<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6 wpr-mb-8">
				<h2 class="wpr-text-lg wpr-font-semibold wpr-text-gray-800 wpr-mb-4">
					<?php esc_html_e( 'Performance Over Time (Last 30 Days)', 'wprobo-engage-lite' ); ?>
				</h2>
				<div class="wpr-relative" style="height: 300px;">
					<canvas id="wpr-analytics-chart"></canvas>
				</div>
			</div>

			<!-- Campaign Statistics Table -->
			<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-overflow-hidden">
				<div class="wpr-px-6 wpr-py-4 wpr-border-b wpr-border-gray-200">
					<h2 class="wpr-text-lg wpr-font-semibold wpr-text-gray-800">
						<?php esc_html_e( 'Top 5 Performing Campaigns', 'wprobo-engage-lite' ); ?>
					</h2>
				</div>
				<div class="wpr-overflow-x-auto">
					<table class="wpr-min-w-full wpr-divide-y wpr-divide-gray-200">
						<thead class="wpr-bg-gray-50">
							<tr>
								<th scope="col" class="wpr-px-6 wpr-py-3 wpr-text-left wpr-text-xs wpr-font-medium wpr-text-gray-500 wpr-uppercase wpr-tracking-wider">
									<?php esc_html_e( 'Campaign', 'wprobo-engage-lite' ); ?>
								</th>
								<th scope="col" class="wpr-px-6 wpr-py-3 wpr-text-left wpr-text-xs wpr-font-medium wpr-text-gray-500 wpr-uppercase wpr-tracking-wider">
									<?php esc_html_e( 'Status', 'wprobo-engage-lite' ); ?>
								</th>
								<th scope="col" class="wpr-px-6 wpr-py-3 wpr-text-left wpr-text-xs wpr-font-medium wpr-text-gray-500 wpr-uppercase wpr-tracking-wider">
									<?php esc_html_e( 'Impressions', 'wprobo-engage-lite' ); ?>
								</th>
								<th scope="col" class="wpr-px-6 wpr-py-3 wpr-text-left wpr-text-xs wpr-font-medium wpr-text-gray-500 wpr-uppercase wpr-tracking-wider">
									<?php esc_html_e( 'Conversions', 'wprobo-engage-lite' ); ?>
								</th>
								<th scope="col" class="wpr-px-6 wpr-py-3 wpr-text-left wpr-text-xs wpr-font-medium wpr-text-gray-500 wpr-uppercase wpr-tracking-wider">
									<?php esc_html_e( 'Conversion Rate', 'wprobo-engage-lite' ); ?>
								</th>
							</tr>
						</thead>
						<tbody class="wpr-bg-white wpr-divide-y wpr-divide-gray-200">
							<?php if ( ! empty( $top_campaigns ) ) : ?>
								<?php foreach ( $top_campaigns as $stat ) : ?>
									<?php
									$is_default_title = ( 'New Campaign' === $stat['title'] || '' === trim( $stat['title'] ) );
									$edit_url         = get_edit_post_link( $stat['id'] );
									?>
									<tr>
										<td class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap">
											<div class="wpr-text-sm wpr-font-medium wpr-text-gray-900">
												<?php echo esc_html( $stat['title'] ?: __( 'Untitled Campaign', 'wprobo-engage-lite' ) ); ?>
											</div>
											<?php if ( $is_default_title && $edit_url ) : ?>
												<div style="margin-top: 4px; font-size: 11px; color: #d97706; display: flex; align-items: center; gap: 4px;">
													<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
													<a href="<?php echo esc_url( $edit_url ); ?>" style="color: #d97706; text-decoration: none; font-weight: 500;"><?php esc_html_e( 'Rename this campaign', 'wprobo-engage-lite' ); ?></a>
												</div>
											<?php endif; ?>
										</td>
										<td class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap">
											<?php
											$status_class = 'publish' === $stat['status'] ? 'wpr-bg-green-100 wpr-text-green-800' : 'wpr-bg-gray-100 wpr-text-gray-800';
											$status_text  = 'publish' === $stat['status'] ? __( 'Active', 'wprobo-engage-lite' ) : ucfirst( $stat['status'] );
											?>
											<span class="wpr-px-2 wpr-inline-flex wpr-text-xs wpr-leading-5 wpr-font-semibold wpr-rounded-full <?php echo esc_attr( $status_class ); ?>">
												<?php echo esc_html( $status_text ); ?>
											</span>
										</td>
										<td class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap wpr-text-sm wpr-text-gray-900">
											<?php echo esc_html( number_format_i18n( $stat['impressions'] ) ); ?>
										</td>
										<td class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap wpr-text-sm wpr-text-gray-900">
											<?php echo esc_html( number_format_i18n( $stat['conversions'] ) ); ?>
										</td>
										<td class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap wpr-text-sm wpr-text-gray-900">
											<?php echo esc_html( number_format_i18n( $stat['conversion_rate'], 2 ) ); ?>%
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="5" class="wpr-px-6 wpr-py-4 wpr-whitespace-nowrap wpr-text-sm wpr-text-gray-500 wpr-text-center">
										<?php esc_html_e( 'No campaigns found. Create your first campaign to start tracking analytics.', 'wprobo-engage-lite' ); ?>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>

			<!-- Pro Analytics Features -->
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 32px;">
				<?php
				echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Device Breakdown', 'wprobo-engage-lite' ), __( 'See how campaigns perform across desktop, mobile, and tablet.', 'wprobo-engage-lite' ) ) );
				echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Export Reports', 'wprobo-engage-lite' ), __( 'Export your analytics data as CSV for external analysis.', 'wprobo-engage-lite' ) ) );
				?>
			</div>

		</div>
		<?php
	}

	/**
	 * Renders the UI shell for the Campaign Builder.
	 */
	public function render_builder_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display parameter, no state change.
		$campaign_id = isset( $_GET['campaign_id'] ) ? absint( $_GET['campaign_id'] ) : 0;
		// Fetch saved values
		$headline                 = get_post_meta( $campaign_id, '_wpr_engage_design_headline', true );
		$content                  = get_post_meta( $campaign_id, '_wpr_engage_design_content', true );
		$button                   = get_post_meta( $campaign_id, '_wpr_engage_design_button', true );
		$display_rules            = get_post_meta( $campaign_id, '_wpr_engage_display_rules', true );
		$success_action           = get_post_meta( $campaign_id, '_wpr_engage_success_action', true );
		$success_redirect_url     = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_url', true );
		$success_message_headline = get_post_meta( $campaign_id, '_wpr_engage_success_message_headline', true );
		$success_message_content  = get_post_meta( $campaign_id, '_wpr_engage_success_message_content', true );
		$success_auto_close       = get_post_meta( $campaign_id, '_wpr_engage_success_auto_close', true );
		$success_auto_close_delay = get_post_meta( $campaign_id, '_wpr_engage_success_auto_close_delay', true );
		$success_redirect_delay   = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_delay', true );
		$success_redirect_new_tab = get_post_meta( $campaign_id, '_wpr_engage_success_redirect_new_tab', true );

		// Ensure display_rules is an array
		if ( ! is_array( $display_rules ) ) {
			$display_rules = array();
		}

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
		?>
		<div class="wpr-builder-wrap wpr-bg-gray-100 wpr-h-screen">
			<header class="wpr-bg-white wpr-p-4 wpr-shadow-md wpr-flex wpr-justify-between wpr-items-center">
				<div>
					<h1 class="wpr-text-xl wpr-font-bold wpr-text-gray-800"><?php echo esc_html( get_the_title( $campaign_id ) ); ?></h1>
				</div>
				<div>
					<button id="wpr-save-campaign" class="wpr-bg-blue-500 wpr-text-white wpr-font-bold wpr-py-2 wpr-px-4 wpr-rounded hover:wpr-bg-blue-700">
						<?php esc_html_e( 'Save Campaign', 'wprobo-engage-lite' ); ?>
					</button>
				</div>
			</header>

			<div class="wpr-flex wpr-h-[calc(100vh-72px)]">
				<aside class="wpr-w-1/4 wpr-bg-white wpr-border-r wpr-border-gray-200 wpr-overflow-y-auto">
					<!-- Tabs Navigation -->
					<div class="wpr-flex wpr-border-b wpr-border-gray-200">
						<button class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-border-b-2 wpr-border-blue-500 wpr-bg-white" data-tab="design">
							<?php esc_html_e( 'Design', 'wprobo-engage-lite' ); ?>
						</button>
						<button class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium wpr-text-gray-500 wpr-border-b-2 wpr-border-transparent hover:wpr-text-gray-700 hover:wpr-border-gray-300" data-tab="display-rules">
							<?php esc_html_e( 'Display Rules', 'wprobo-engage-lite' ); ?>
						</button>
						<button class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium wpr-text-gray-500 wpr-border-b-2 wpr-border-transparent hover:wpr-text-gray-700 hover:wpr-border-gray-300" data-tab="success">
							<?php esc_html_e( 'Success', 'wprobo-engage-lite' ); ?>
						</button>
						<?php $pro_badge = ' ' . Pro_Upsell::render_badge(); ?>
						<button class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium wpr-text-gray-500 wpr-border-b-2 wpr-border-transparent hover:wpr-text-gray-700 hover:wpr-border-gray-300" data-tab="embed">
							<?php echo wp_kses( __( 'Embed', 'wprobo-engage-lite' ) . $pro_badge, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ); ?>
						</button>
						<button class="wpr-tab-button wpr-flex-1 wpr-py-3 wpr-px-4 wpr-text-sm wpr-font-medium wpr-text-gray-500 wpr-border-b-2 wpr-border-transparent hover:wpr-text-gray-700 hover:wpr-border-gray-300" data-tab="history">
							<?php echo wp_kses( __( 'History', 'wprobo-engage-lite' ) . $pro_badge, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ); ?>
						</button>
					</div>

					<!-- Design Tab -->
					<div id="wpr-tab-design" class="wpr-tab-content wpr-p-6">
						<div class="wpr-space-y-6">
							<div>
								<label for="wpr-headline" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Headline', 'wprobo-engage-lite' ); ?></label>
								<input type="text" id="wpr-headline" value="<?php echo esc_attr( $headline ); ?>" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2">
							</div>
							<div>
								<label for="wpr-content" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Content', 'wprobo-engage-lite' ); ?></label>
								<textarea id="wpr-content" rows="4" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2"><?php echo esc_textarea( $content ); ?></textarea>
							</div>
							<div>
								<label for="wpr-button" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Button Text', 'wprobo-engage-lite' ); ?></label>
								<input type="text" id="wpr-button" value="<?php echo esc_attr( $button ); ?>" class="wpr-mt-1 wpr-block wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2">
							</div>
						</div>
					</div>

					<!-- Display Rules Tab -->
					<div id="wpr-tab-display-rules" class="wpr-tab-content wpr-p-6 wpr-hidden">
						<div class="wpr-space-y-4">
							<div>
								<h3 class="wpr-text-sm wpr-font-semibold wpr-text-gray-700 wpr-mb-3"><?php esc_html_e( 'Display Rules', 'wprobo-engage-lite' ); ?></h3>
								<p class="wpr-text-xs wpr-text-gray-500 wpr-mb-4">
									<?php esc_html_e( 'Control where this campaign appears. Add rules to show or hide the campaign on specific pages.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<div id="wpr-display-rules-container">
								<!-- Rules will be dynamically added here -->
							</div>

							<div class="wpr-space-y-2">
								<button type="button" id="wpr-add-show-rule" class="wpr-w-full wpr-py-2 wpr-px-4 wpr-border wpr-border-green-500 wpr-text-green-600 wpr-rounded-md wpr-text-sm wpr-font-medium hover:wpr-bg-green-50">
									<?php esc_html_e( '+ Add Show Rule', 'wprobo-engage-lite' ); ?>
								</button>
								<button type="button" id="wpr-add-hide-rule" class="wpr-w-full wpr-py-2 wpr-px-4 wpr-border wpr-border-red-500 wpr-text-red-600 wpr-rounded-md wpr-text-sm wpr-font-medium hover:wpr-bg-red-50">
									<?php esc_html_e( '+ Add Hide Rule', 'wprobo-engage-lite' ); ?>
								</button>
							</div>
						</div>
					</div>

					<!-- Success Tab -->
					<div id="wpr-tab-success" class="wpr-tab-content wpr-p-6 wpr-hidden">
						<div class="wpr-space-y-4">
							<div>
								<h3 class="wpr-text-sm wpr-font-semibold wpr-text-gray-700 wpr-mb-3"><?php esc_html_e( 'Post-Submission Action', 'wprobo-engage-lite' ); ?></h3>
								<p class="wpr-text-xs wpr-text-gray-500 wpr-mb-4">
									<?php esc_html_e( 'Choose what happens after a visitor successfully subscribes.', 'wprobo-engage-lite' ); ?>
								</p>
							</div>

							<div class="wpr-space-y-3">
								<label class="wpr-flex wpr-items-start wpr-p-3 wpr-border wpr-border-gray-300 wpr-rounded-md wpr-cursor-pointer hover:wpr-bg-gray-50">
									<input type="radio" name="wpr-success-action" id="wpr-success-action-message" value="message" class="wpr-mt-1" <?php checked( $success_action, 'message' ); ?>>
									<div class="wpr-ml-3">
										<span class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Show a message', 'wprobo-engage-lite' ); ?></span>
										<span class="wpr-block wpr-text-xs wpr-text-gray-500"><?php esc_html_e( 'Display a customizable success message', 'wprobo-engage-lite' ); ?></span>
									</div>
								</label>

								<!-- Success Message Configuration -->
								<div id="wpr-success-message-config" class="wpr-pl-3 wpr-space-y-4 <?php echo ( 'message' !== $success_action ) ? 'wpr-hidden' : ''; ?>">
									<div>
										<label for="wpr-success-message-headline" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
											<?php esc_html_e( 'Success Headline', 'wprobo-engage-lite' ); ?>
										</label>
										<input type="text" id="wpr-success-message-headline" value="<?php echo esc_attr( $success_message_headline ); ?>" placeholder="Thank you!" class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
									</div>

									<div>
										<label for="wpr-success-message-content" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
											<?php esc_html_e( 'Success Message', 'wprobo-engage-lite' ); ?>
										</label>
										<textarea id="wpr-success-message-content" rows="3" placeholder="Your subscription has been confirmed." class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm"><?php echo esc_textarea( $success_message_content ); ?></textarea>
										<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1">
											<?php esc_html_e( 'You can use {first_name} and {email} as placeholders', 'wprobo-engage-lite' ); ?>
										</p>
									</div>

									<div>
										<label class="wpr-flex wpr-items-center wpr-cursor-pointer">
											<input type="checkbox" id="wpr-success-auto-close" value="1" <?php checked( $success_auto_close, '1' ); ?> class="wpr-mr-2">
											<span class="wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Auto-close after success', 'wprobo-engage-lite' ); ?></span>
										</label>
									</div>

									<div id="wpr-auto-close-delay-container" class="wpr-pl-6 <?php echo ( '1' !== $success_auto_close ) ? 'wpr-hidden' : ''; ?>">
										<label for="wpr-success-auto-close-delay" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
											<?php esc_html_e( 'Auto-close delay (seconds)', 'wprobo-engage-lite' ); ?>
										</label>
										<input type="number" id="wpr-success-auto-close-delay" value="<?php echo esc_attr( $success_auto_close_delay ); ?>" min="1" max="60" class="wpr-w-32 wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
										<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1">
											<?php esc_html_e( 'Campaign will close automatically after this many seconds', 'wprobo-engage-lite' ); ?>
										</p>
									</div>
								</div>

								<label class="wpr-flex wpr-items-start wpr-p-3 wpr-border wpr-border-gray-300 wpr-rounded-md wpr-cursor-pointer hover:wpr-bg-gray-50">
									<input type="radio" name="wpr-success-action" id="wpr-success-action-redirect" value="redirect" class="wpr-mt-1" <?php checked( $success_action, 'redirect' ); ?>>
									<div class="wpr-ml-3">
										<span class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Redirect to a URL', 'wprobo-engage-lite' ); ?></span>
										<span class="wpr-block wpr-text-xs wpr-text-gray-500"><?php esc_html_e( 'Redirect the visitor to a specific page', 'wprobo-engage-lite' ); ?></span>
									</div>
								</label>

								<!-- Redirect Configuration -->
								<div id="wpr-redirect-url-container" class="wpr-pl-3 wpr-space-y-4 <?php echo ( 'redirect' !== $success_action ) ? 'wpr-hidden' : ''; ?>">
									<div>
										<label for="wpr-success-redirect-url" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
											<?php esc_html_e( 'Redirect URL', 'wprobo-engage-lite' ); ?>
										</label>
										<input type="url" id="wpr-success-redirect-url" value="<?php echo esc_attr( $success_redirect_url ); ?>" placeholder="https://example.com/thank-you" class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
									</div>

									<div>
										<label for="wpr-success-redirect-delay" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
											<?php esc_html_e( 'Redirect delay (seconds)', 'wprobo-engage-lite' ); ?>
										</label>
										<input type="number" id="wpr-success-redirect-delay" value="<?php echo esc_attr( $success_redirect_delay ); ?>" min="0" max="30" class="wpr-w-32 wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
										<p class="wpr-text-xs wpr-text-gray-500 wpr-mt-1">
											<?php esc_html_e( 'Wait this many seconds before redirecting (0 for immediate)', 'wprobo-engage-lite' ); ?>
										</p>
									</div>

									<div>
										<label class="wpr-flex wpr-items-center wpr-cursor-pointer">
											<input type="checkbox" id="wpr-success-redirect-new-tab" value="1" <?php checked( $success_redirect_new_tab, '1' ); ?> class="wpr-mr-2">
											<span class="wpr-text-sm wpr-font-medium wpr-text-gray-700"><?php esc_html_e( 'Open in new tab', 'wprobo-engage-lite' ); ?></span>
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Embed Tab (Pro) -->
					<div id="wpr-tab-embed" class="wpr-tab-content wpr-p-6 wpr-hidden">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Remote Embedding', 'wprobo-engage-lite' ), __( 'Embed campaigns on external websites with a simple script tag.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

					<!-- Version History Tab (Pro) -->
					<div id="wpr-tab-history" class="wpr-tab-content wpr-p-6 wpr-hidden">
						<?php echo wp_kses_post( Pro_Upsell::render_overlay( __( 'Version History', 'wprobo-engage-lite' ), __( 'Track changes and restore previous versions of your campaign.', 'wprobo-engage-lite' ) ) ); ?>
					</div>

				</aside>

				<main class="wpr-w-3/4 wpr-p-8 wpr-overflow-y-auto wpr-flex wpr-items-center wpr-justify-center">
					<div id="wpr-preview-wrapper" class="wpr-bg-white wpr-shadow-lg wpr-rounded-lg wpr-p-8 wpr-w-full wpr-max-w-lg wpr-text-center">
						<h2 id="wpr-preview-headline" class="wpr-text-2xl wpr-font-bold wpr-text-gray-900">
							<?php echo esc_html( $headline ?: 'This is the Headline' ); ?>
						</h2>
						<p id="wpr-preview-content" class="wpr-mt-4 wpr-text-gray-600">
							<?php echo esc_html( $content ?: 'This is the main content area. Describe your offer here.' ); ?>
						</p>
						<div class="wpr-mt-6">
							<button id="wpr-preview-button" class="wpr-bg-blue-500 wpr-text-white wpr-font-bold wpr-py-2 wpr-px-6 wpr-rounded">
								<?php echo esc_html( $button ?: 'Subscribe' ); ?>
							</button>
						</div>
					</div>
				</main>
			</div>
		</div>

		<?php
		wp_add_inline_script( 'wprobo-engage-admin', 'var wprDisplayRules = ' . wp_json_encode( $display_rules ) . ';', 'before' );
	}

	/**
	 * Modifies the action links on the 'All Campaigns' list table.
	 *
	 * @param array    $actions The existing action links.
	 * @param \WP_Post $post    The post object.
	 * @return array The modified action links.
	 */
	public function modify_campaign_row_actions( array $actions, \WP_Post $post ): array {
		// We only want to modify this for our CPT
		if ( 'wpr_campaign' === $post->post_type ) {

			// Build the URL for export
			$export_url = add_query_arg(
				array(
					'action'      => 'export_campaign',
					'campaign_id' => $post->ID,
					'_wpnonce'    => wp_create_nonce( 'export_campaign_' . $post->ID ),
				),
				admin_url( 'admin.php' )
			);

			// Clone (Pro feature).
			$new_actions['clone'] = '<span style="color:#94a3b8;">' . esc_html__( 'Clone', 'wprobo-engage-lite' ) . ' ' . Pro_Upsell::render_badge() . '</span>';

			// Add "Export" link
			$new_actions['export'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $export_url ),
				esc_html__( 'Export', 'wprobo-engage-lite' )
			);

			// Merge our new link to the front of the array
			return array_merge( $actions, $new_actions );
		}

		return $actions;
	}

	/**
	 * Renders the template selection page.
	 *
	 * @return void
	 */
	public function render_template_page(): void {
		$template_library = new Template_Library();
		$template_library->render_template_selection_page();
	}

	/**
	 * Handle template actions early before any output.
	 *
	 * @return void
	 */
	public function handle_template_actions(): void {
		// Check if we're on the template page with an action
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified inside handle_template_action().
		if ( isset( $_GET['page'] ) && 'wprobo-engage-templates' === $_GET['page'] && isset( $_GET['action'] ) && 'create' === $_GET['action'] ) {
			$template_library = new Template_Library();
			$template_library->handle_template_action();
		}
	}

	/**
	 * Redirect "Add New" campaign link to template selection page.
	 *
	 * @return void
	 */
	public function redirect_add_new_to_templates(): void {
		global $pagenow;

		// Check if we're on the post-new.php page for our CPT
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page redirect, no state change.
		if ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) && 'wpr_campaign' === $_GET['post_type'] ) {
			wp_safe_redirect( admin_url( 'admin.php?page=wprobo-engage-templates' ) );
			exit;
		}
	}

	/**
	 * Renders the Leads page.
	 *
	 * @return void
	 */
	public function render_leads_page(): void {
		$leads_table = new Leads_List_Table();

		// Process bulk actions before loading items so a delete doesn't leave
		// stale rows visible. Delete redirects to a clean URL with a status
		// param; export streams CSV and exits.
		$leads_table->process_bulk_action();

		$leads_table->prepare_items();

		// True "no leads ever" state — no results AND no filters active.
		// If a filter is set (search, campaign filter), keep the table visible
		// so the user can see how to clear the filter.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter parameters, no state change.
		$has_search       = ! empty( $_GET['s'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter parameter, no state change.
		$has_campaign_flt = ! empty( $_GET['campaign_id'] );
		$is_truly_empty   = empty( $leads_table->items ) && ! $has_search && ! $has_campaign_flt;
		?>
		<div class="wpr-wrap wpr-p-6">
			<div class="wpr-flex wpr-justify-between wpr-items-center wpr-mb-6">
				<h1 class="wpr-text-2xl wpr-font-semibold wpr-text-gray-800">
					<?php esc_html_e( 'Leads', 'wprobo-engage-lite' ); ?>
				</h1>
				<?php if ( ! $is_truly_empty ) : ?>
					<a href="<?php echo esc_url( WPROBO_ENGAGE_LITE_UPGRADE_URL ); ?>" target="_blank" rel="noopener" class="wpr-btn-upgrade">
						<?php esc_html_e( 'Export as CSV', 'wprobo-engage-lite' ); ?>
						<?php echo wp_kses_post( Pro_Upsell::render_badge() ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php
			// Deleted-count notice is driven by the URL param set by the
			// redirect-after-delete flow in Leads_List_Table::process_bulk_action().
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display parameter, no state change.
			$deleted_count = isset( $_GET['wpr_deleted'] ) ? absint( $_GET['wpr_deleted'] ) : 0;
			if ( $deleted_count > 0 ) :
				?>
				<div class="notice notice-success is-dismissible" style="margin-bottom: 16px;">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: number of leads deleted */
								_n( '%d lead deleted.', '%d leads deleted.', $deleted_count, 'wprobo-engage-lite' ),
								$deleted_count
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $is_truly_empty ) : ?>
				<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md" style="text-align: center; padding: 64px 32px;">
					<div style="display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; background: #eff6ff; color: #3b82f6; border-radius: 999px; margin-bottom: 20px;" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
					</div>
					<h3 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 8px;">
						<?php esc_html_e( 'No leads captured yet', 'wprobo-engage-lite' ); ?>
					</h3>
					<p style="color: #6b7280; max-width: 460px; margin: 0 auto 24px; line-height: 1.6; font-size: 14px;">
						<?php esc_html_e( 'Leads are collected whenever a visitor submits a form in one of your active campaigns. Launch a campaign to start capturing leads.', 'wprobo-engage-lite' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-engage-templates' ) ); ?>" class="button button-primary button-large" style="padding: 8px 20px; font-weight: 600;">
						<?php esc_html_e( 'Create Your First Campaign', 'wprobo-engage-lite' ); ?>
					</a>
					<p style="color: #9ca3af; margin-top: 20px; font-size: 12px;">
						<?php
						printf(
							/* translators: %s: link to Campaigns screen */
							esc_html__( 'Already have campaigns? %s to make sure they are active.', 'wprobo-engage-lite' ),
							'<a href="' . esc_url( admin_url( 'edit.php?post_type=wpr_campaign' ) ) . '" style="color: #2563eb; font-weight: 500;">' . esc_html__( 'Review them here', 'wprobo-engage-lite' ) . '</a>'
						);
						?>
					</p>
				</div>
			<?php else : ?>
				<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-4">
					<form method="get">
						<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page parameter for form action. ?>
						<input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' ); ?>" />
						<?php
						$leads_table->search_box( __( 'Search by Email', 'wprobo-engage-lite' ), 'email' );
						$leads_table->display();
						?>
					</form>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the Settings page.
	 * This method delegates to the Settings class.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$settings = new Settings();
		$settings->render_settings_page();
	}

	/**
	 * Renders the Tools page.
	 * This method delegates to the Tools class.
	 *
	 * @return void
	 */
	public function render_tools_page(): void {
		$tools = new Tools();
		$tools->render_tools_page();
	}
}
