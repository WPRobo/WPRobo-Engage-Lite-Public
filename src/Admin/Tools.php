<?php
namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Tools
 *
 * Handles campaign import/export functionality.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Tools {

	public function allow_json_uploads( $mimes ) {
		$mimes['json'] = 'application/json';
		return $mimes;
	}

	/**
	 * Render the Tools page.
	 *
	 * @return void
	 */
	public function render_tools_page(): void {
		// Handle import messages from redirect
		$import_message = '';
		$import_status  = '';
		
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only GET params; values originate from wp_safe_redirect() calls in nonce-verified handlers and are used only for rendering feedback messages, never for state changes.
		if ( isset( $_GET['import_status'] ) ) {
			$import_status = sanitize_text_field( wp_unslash( $_GET['import_status'] ) );
			$import_code   = isset( $_GET['import_code'] ) ? sanitize_text_field( wp_unslash( $_GET['import_code'] ) ) : '';

			if ( 'success' === $import_status ) {
				$count          = isset( $_GET['count'] ) ? absint( $_GET['count'] ) : 0;
				$import_message = sprintf(
					/* translators: %d: number of campaigns imported */
					_n( 'Successfully imported %d campaign!', 'Successfully imported %d campaigns!', $count, 'wprobo-engage-lite' ),
					$count
				) . ' <a href="' . admin_url( 'edit.php?post_type=wpr_campaign' ) . '" class="wpr-underline wpr-font-semibold">' . esc_html__( 'View all campaigns', 'wprobo-engage-lite' ) . '</a>';
			} else {
				switch ( $import_code ) {
					case 'upload_failed':
						$import_message = esc_html__( 'Error: No file was uploaded or there was an upload error.', 'wprobo-engage-lite' );
						break;
					case 'invalid_file':
						$import_message = esc_html__( 'Error: Please upload a valid JSON file.', 'wprobo-engage-lite' );
						break;
					case 'invalid_json':
						$import_message = esc_html__( 'Error: Invalid JSON file format.', 'wprobo-engage-lite' );
						break;
					case 'invalid_format':
						$import_message = esc_html__( 'Error: This file does not look like a WPRobo Engage campaign export.', 'wprobo-engage-lite' );
						break;
					default:
						$import_message = esc_html__( 'Error: Campaign import failed.', 'wprobo-engage-lite' );
						break;
				}
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		?>
		<div class="wpr-wrap wpr-p-6">
			<div class="wpr-mb-8">
				<h1 class="wpr-text-3xl wpr-font-bold wpr-text-gray-800 wpr-mb-2">
					<?php esc_html_e( 'Import/Export Tools', 'wprobo-engage-lite' ); ?>
				</h1>
				<p class="wpr-text-gray-600">
					<?php esc_html_e( 'Export campaigns to transfer them between sites, or import campaigns from JSON files.', 'wprobo-engage-lite' ); ?>
				</p>
			</div>

			<?php if ( $import_message ) : ?>
				<div class="wpr-mb-6 wpr-p-4 wpr-rounded-lg <?php echo $import_status === 'success' ? 'wpr-bg-green-50 wpr-border wpr-border-green-200' : 'wpr-bg-red-50 wpr-border wpr-border-red-200'; ?>">
					<p class="<?php echo $import_status === 'success' ? 'wpr-text-green-700' : 'wpr-text-red-700'; ?>">
						<?php echo wp_kses_post( $import_message ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display parameter.
			if ( isset( $_GET['wpr_export_error'] ) && 'no_campaigns' === $_GET['wpr_export_error'] ) : ?>
				<div class="wpr-mb-6 wpr-p-4 wpr-rounded-lg wpr-bg-yellow-50 wpr-border wpr-border-yellow-200">
					<p class="wpr-text-yellow-700">
						<?php esc_html_e( 'No campaigns found matching the selected filters. Try changing the status or type filters, or create some campaigns first.', 'wprobo-engage-lite' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<div class="wpr-bg-blue-50 wpr-border wpr-border-blue-200 wpr-rounded-lg wpr-p-4 wpr-mb-6">
				<h3 class="wpr-text-sm wpr-font-semibold wpr-text-blue-800 wpr-mb-2">
					<?php esc_html_e( 'How to Export a Single Campaign', 'wprobo-engage-lite' ); ?>
				</h3>
				<p class="wpr-text-sm wpr-text-blue-700">
					<?php esc_html_e( 'To export a single campaign, go to the All Campaigns page and click the "Export" link next to the campaign you want to export. A JSON file will be downloaded to your computer.', 'wprobo-engage-lite' ); ?>
				</p>
			</div>

			<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6 wpr-mb-6">
				<h2 class="wpr-text-xl wpr-font-bold wpr-text-gray-800 wpr-mb-4">
					<?php esc_html_e( 'Import Campaign', 'wprobo-engage-lite' ); ?>
				</h2>
				<p class="wpr-text-gray-600 wpr-mb-4">
					<?php esc_html_e( 'Upload a campaign JSON file to import it into your site.', 'wprobo-engage-lite' ); ?>
				</p>

				<form method="post" enctype="multipart/form-data" class="wpr-space-y-4" id="wpr-import-form">
					<?php wp_nonce_field( 'wprobo_engage_import_campaign', 'wprobo_engage_import_nonce' ); ?>

					<div id="wpr-drop-zone" class="wpr-drop-zone" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Drop a campaign JSON file here, or click to browse', 'wprobo-engage-lite' ); ?>">
						<div class="wpr-drop-zone-inner">
							<div class="wpr-drop-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
							</div>
							<p class="wpr-drop-primary">
								<?php esc_html_e( 'Drop your campaign JSON file here', 'wprobo-engage-lite' ); ?>
							</p>
							<p class="wpr-drop-secondary">
								<?php esc_html_e( 'or', 'wprobo-engage-lite' ); ?>
							</p>
							<label for="campaign_file" class="button">
								<?php esc_html_e( 'Browse Files', 'wprobo-engage-lite' ); ?>
							</label>
							<input
								type="file"
								name="campaign_file"
								id="campaign_file"
								accept=".json,application/json"
								required
								class="wpr-visually-hidden"
							/>
						</div>
					</div>

					<div id="wpr-import-status" class="wpr-import-status" hidden></div>

					<div>
						<button
							type="submit"
							name="wprobo_engage_import_campaign"
							id="wpr-import-submit"
							class="wpr-bg-blue-500 wpr-text-white wpr-font-semibold wpr-py-2 wpr-px-6 wpr-rounded hover:wpr-bg-blue-700 wpr-transition-colors wpr-disabled:wpr-opacity-50"
							disabled
							aria-describedby="wpr-import-status"
						>
							<?php esc_html_e( 'Import Campaign', 'wprobo-engage-lite' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div class="wpr-bg-white wpr-rounded-lg wpr-shadow-md wpr-p-6 wpr-mb-6">
				<h2 class="wpr-text-xl wpr-font-bold wpr-text-gray-800 wpr-mb-4">
					<?php esc_html_e( 'Export All Campaigns', 'wprobo-engage-lite' ); ?>
				</h2>
				<p class="wpr-text-gray-600 wpr-mb-4">
					<?php esc_html_e( 'Export multiple campaigns at once based on status and type.', 'wprobo-engage-lite' ); ?>
				</p>

				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="wpr-space-y-4">
					<input type="hidden" name="action" value="export_all_campaigns" />
					<?php wp_nonce_field( 'export_all_campaigns', '_wpnonce' ); ?>

					<div class="wpr-grid wpr-grid-cols-1 md:wpr-grid-cols-2 wpr-gap-4">
						<div>
							<label for="export_status" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
								<?php esc_html_e( 'Campaign Status', 'wprobo-engage-lite' ); ?>
							</label>
							<select name="export_status" id="export_status" class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
								<option value="all"><?php esc_html_e( 'All Statuses', 'wprobo-engage-lite' ); ?></option>
								<option value="active"><?php esc_html_e( 'Active', 'wprobo-engage-lite' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft', 'wprobo-engage-lite' ); ?></option>
								<option value="paused"><?php esc_html_e( 'Paused', 'wprobo-engage-lite' ); ?></option>
							</select>
						</div>

						<div>
							<label for="export_type" class="wpr-block wpr-text-sm wpr-font-medium wpr-text-gray-700 wpr-mb-2">
								<?php esc_html_e( 'Campaign Type', 'wprobo-engage-lite' ); ?>
							</label>
							<select name="export_type" id="export_type" class="wpr-w-full wpr-border wpr-border-gray-300 wpr-rounded-md wpr-shadow-sm wpr-p-2 wpr-text-sm">
								<option value="all"><?php esc_html_e( 'All Types', 'wprobo-engage-lite' ); ?></option>
								<option value="popup"><?php esc_html_e( 'Popup', 'wprobo-engage-lite' ); ?></option>
								<option value="floating-bar"><?php esc_html_e( 'Floating Bar', 'wprobo-engage-lite' ); ?></option>
							</select>
						</div>
					</div>

					<div>
						<button
							type="submit"
							class="wpr-bg-green-500 wpr-text-white wpr-font-semibold wpr-py-2 wpr-px-6 wpr-rounded hover:wpr-bg-green-700 wpr-transition-colors"
						>
							<?php esc_html_e( 'Export All Campaigns', 'wprobo-engage-lite' ); ?>
						</button>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle campaign import action.
	 * Hooked to admin_init.
	 *
	 * @return void
	 */
	public function handle_import_action(): void {
		if ( ! isset( $_POST['wprobo_engage_import_nonce'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wprobo_engage_import_nonce'] ) ), 'wprobo_engage_import_campaign' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wprobo-engage-lite' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import campaigns.', 'wprobo-engage-lite' ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['campaign_file'] ) || ! isset( $_FILES['campaign_file']['error'] ) || $_FILES['campaign_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->redirect_with_message( 'error', 'upload_failed' );
		}

		// Use WordPress upload handler securely
		$file = wp_unslash( $_FILES['campaign_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		// Secure file type check
		$file_info = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'json' => 'application/json' ) );
		if ( ! $file_info['ext'] || ! $file_info['type'] || $file_info['ext'] !== 'json' ) {
			$this->redirect_with_message( 'error', 'invalid_file' );
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		$upload_overrides = array(
			'test_form' => false,
			'mimes'     => array( 'json' => 'application/json' ),
		);
		
		$movefile = wp_handle_upload( $file, $upload_overrides );

		if ( $movefile && ! isset( $movefile['error'] ) ) {
			// Read the file securely from the new path
			$json_content = file_get_contents( $movefile['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			
			// Delete the file after reading, we don't need to keep it
			wp_delete_file( $movefile['file'] );
			
			$campaign_data = json_decode( $json_content, true );

			if ( json_last_error() !== JSON_ERROR_NONE || ! $campaign_data ) {
				$this->redirect_with_message( 'error', 'invalid_json' );
			}

			// Validate export structure
			$is_bulk   = isset( $campaign_data['campaigns'] ) && is_array( $campaign_data['campaigns'] );
			$is_single = isset( $campaign_data['name'] ) && isset( $campaign_data['settings'] ) && is_array( $campaign_data['settings'] );

			if ( ! $is_bulk && ! $is_single ) {
				$this->redirect_with_message( 'error', 'invalid_format' );
			}

			$imported_count = 0;
			if ( $is_bulk ) {
				foreach ( $campaign_data['campaigns'] as $single_campaign ) {
					if ( $this->create_campaign_from_data( $single_campaign ) ) {
						++$imported_count;
					}
				}
			} else {
				if ( $this->create_campaign_from_data( $campaign_data ) ) {
					++$imported_count;
				}
			}

			if ( $imported_count > 0 ) {
				wp_safe_redirect( add_query_arg( array( 'page' => 'wprobo-engage-tools', 'import_status' => 'success', 'count' => $imported_count ), admin_url( 'admin.php' ) ) );
				exit;
			} else {
				$this->redirect_with_message( 'error', 'import_failed' );
			}
		} else {
			$this->redirect_with_message( 'error', 'upload_failed' );
		}
	}

	/**
	 * Create a campaign from imported data.
	 *
	 * @param array $data Campaign data from JSON.
	 * @return int|false Campaign post ID or false on failure.
	 */
	private function create_campaign_from_data( array $data ) {
		// Get campaign title
		$campaign_title = isset( $data['name'] ) ? $data['name'] : __( 'Imported Campaign', 'wprobo-engage-lite' );

		// Create the campaign post
		$post_id = wp_insert_post(
			array(
				'post_title'  => $campaign_title,
				'post_type'   => 'wpr_campaign',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return false;
		}

		// Import settings if they exist
		if ( isset( $data['settings'] ) && is_array( $data['settings'] ) ) {
			foreach ( $data['settings'] as $key => $value ) {
				$meta_key = '_wpr_engage_' . $key;

				// Handle display_rules specially
				if ( 'display_rules' === $key && is_array( $value ) ) {
					$sanitized_rules = $this->sanitize_display_rules( $value );
					update_post_meta( $post_id, $meta_key, $sanitized_rules );
				}
				// Handle other arrays
				elseif ( is_array( $value ) ) {
					update_post_meta( $post_id, $meta_key, $value );
				}
				// Handle scalar values
				else {
					update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
				}
			}
		}

		return $post_id;
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
				if ( in_array( $rule['type'], array( 'specific_page', 'specific_post' ), true ) ) {
					$sanitized_rule['value'] = absint( $rule['value'] );
				} else {
					$sanitized_rule['value'] = sanitize_text_field( $rule['value'] );
				}
			}

			$sanitized[] = $sanitized_rule;
		}

		return $sanitized;
	}

	/**
	 * Export a campaign as JSON.
	 *
	 * @param int $campaign_id Campaign post ID.
	 * @return void
	 */
	public function export_campaign( int $campaign_id ): void {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'export_campaign_' . $campaign_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wprobo-engage-lite' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export campaigns.', 'wprobo-engage-lite' ) );
		}

		// Get campaign post
		$campaign = get_post( $campaign_id );

		if ( ! $campaign || $campaign->post_type !== 'wpr_campaign' ) {
			wp_die( esc_html__( 'Invalid campaign.', 'wprobo-engage-lite' ) );
		}

		// Get all campaign meta data
		$all_meta = get_post_meta( $campaign_id );
		$settings = array();

		// Extract only WPRobo Engage meta keys
		foreach ( $all_meta as $meta_key => $meta_value ) {
			if ( strpos( $meta_key, '_wpr_engage_' ) === 0 ) {
				// Remove the '_wpr_engage_' prefix for cleaner JSON
				$clean_key = str_replace( '_wpr_engage_', '', $meta_key );
				// Get the first value (meta values are arrays)
				$value = isset( $meta_value[0] ) ? $meta_value[0] : '';

				// Try to unserialize if it's a serialized value
				$unserialized           = maybe_unserialize( $value );
				$settings[ $clean_key ] = $unserialized;
			}
		}

		// Build export data structure
		$export_data = array(
			'name'        => $campaign->post_title,
			'description' => sprintf(
				/* translators: %s: campaign title */
				__( 'Exported campaign: %s', 'wprobo-engage-lite' ),
				$campaign->post_title
			),
			'settings'    => $settings,
			'exported_at' => current_time( 'mysql' ),
			'version'     => WPROBO_ENGAGE_LITE_VERSION ?? '1.0.0',
		);

		// Create filename
		$filename = sanitize_file_name( $campaign->post_title ) . '-' . gmdate( 'Y-m-d-His' ) . '.json';

		// Set headers for download
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );

		// Output JSON
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Handle export action.
	 *
	 * @return void
	 */
	public function handle_export_action(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce is verified inside export_campaign() and export_all_campaigns().
		// Check if this is a single export request
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_campaign' && isset( $_GET['campaign_id'] ) ) {
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
			$campaign_id = absint( $_GET['campaign_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified inside export_campaign().
			$this->export_campaign( $campaign_id );
		}

		// Check if this is a bulk export request
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce is verified inside export_all_campaigns().
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'export_all_campaigns' ) {
			$this->export_all_campaigns();
		}
	}

	/**
	 * Export all campaigns as a single JSON file.
	 *
	 * @return void
	 */
	public function export_all_campaigns(): void {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'export_all_campaigns' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'wprobo-engage-lite' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export campaigns.', 'wprobo-engage-lite' ) );
		}

		// Get filter parameters
		$status_filter = isset( $_GET['export_status'] ) ? sanitize_text_field( wp_unslash( $_GET['export_status'] ) ) : 'all';
		$type_filter   = isset( $_GET['export_type'] ) ? sanitize_text_field( wp_unslash( $_GET['export_type'] ) ) : 'all';

		// Build query arguments
		$args = array(
			'post_type'      => 'wpr_campaign',
			'posts_per_page' => -1,
			'post_status'    => 'any',
		);

		// Add meta query for filters
		$meta_query = array();

		if ( $status_filter !== 'all' ) {
			$meta_query[] = array(
				'key'   => '_wpr_engage_campaign_status',
				'value' => $status_filter,
			);
		}

		if ( $type_filter !== 'all' ) {
			$meta_query[] = array(
				'key'   => '_wpr_engage_campaign_type',
				'value' => $type_filter,
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- standard WP_Query meta filtering.
		}

		// Get campaigns
		$campaigns_query = new \WP_Query( $args );
		$campaigns_data  = array();

		if ( $campaigns_query->have_posts() ) {
			while ( $campaigns_query->have_posts() ) {
				$campaigns_query->the_post();
				$campaign_id = get_the_ID();
				$campaign    = get_post( $campaign_id );

				// Get all campaign meta data
				$all_meta = get_post_meta( $campaign_id );
				$settings = array();

				// Extract only WPRobo Engage meta keys
				foreach ( $all_meta as $meta_key => $meta_value ) {
					if ( strpos( $meta_key, '_wpr_engage_' ) === 0 ) {
						// Remove the '_wpr_engage_' prefix for cleaner JSON
						$clean_key = str_replace( '_wpr_engage_', '', $meta_key );
						// Get the first value (meta values are arrays)
						$value = isset( $meta_value[0] ) ? $meta_value[0] : '';

						// Try to unserialize if it's a serialized value
						$unserialized           = maybe_unserialize( $value );
						$settings[ $clean_key ] = $unserialized;
					}
				}

				$campaigns_data[] = array(
					'name'        => $campaign->post_title,
					'description' => sprintf(
						/* translators: %s: campaign title */
						__( 'Exported campaign: %s', 'wprobo-engage-lite' ),
						$campaign->post_title
					),
					'settings'    => $settings,
				);
			}
			wp_reset_postdata();
		}

		if ( empty( $campaigns_data ) ) {
			wp_safe_redirect( add_query_arg( 'wpr_export_error', 'no_campaigns', admin_url( 'admin.php?page=wprobo-engage-tools' ) ) );
			exit;
		}

		// Build export data structure
		$export_data = array(
			'campaigns'   => $campaigns_data,
			'exported_at' => current_time( 'mysql' ),
			'version'     => WPROBO_ENGAGE_LITE_VERSION ?? '1.0.0',
			'count'       => count( $campaigns_data ),
			'filters'     => array(
				'status' => $status_filter,
				'type'   => $type_filter,
			),
		);

		// Create filename
		$filename = 'campaigns-bulk-export-' . gmdate( 'Y-m-d-His' ) . '.json';

		// Set headers for download
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );

		// Output JSON
		echo wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		exit;
	}
}
