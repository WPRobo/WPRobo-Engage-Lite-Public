<?php

namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Leads_List_Table
 *
 * Displays leads in a professional, sortable, and paginated table.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Leads_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'lead',
				'plural'   => 'leads',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get columns for the table.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return array(
			'cb'           => '<input type="checkbox" />',
			'id'           => __( 'ID', 'wprobo-engage-lite' ),
			'email'        => __( 'Email', 'wprobo-engage-lite' ),
			'form_data'    => __( 'Form Data', 'wprobo-engage-lite' ),
			'campaign'     => __( 'Campaign', 'wprobo-engage-lite' ),
			'submitted_at' => __( 'Date Submitted', 'wprobo-engage-lite' ),
		);
	}

	/**
	 * Bulk actions dropdown entries. Nonce is automatically added by
	 * display_tablenav() under the 'bulk-leads' action name.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'wprobo-engage-lite' ),
		);
	}

	/**
	 * Checkbox column renderer.
	 *
	 * @param array $item Lead row data.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<label class="screen-reader-text" for="lead_%1$d">%2$s</label>'
			. '<input type="checkbox" name="lead[]" id="lead_%1$d" value="%1$d" />',
			absint( $item['id'] ),
			esc_html(
				sprintf(
					/* translators: %d: lead ID */
					__( 'Select lead #%d', 'wprobo-engage-lite' ),
					absint( $item['id'] )
				)
			)
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return array(
			'id'           => array( 'id', false ),
			'email'        => array( 'email', false ),
			'submitted_at' => array( 'submitted_at', true ),
		);
	}

	/**
	 * Process bulk actions (delete) before the items are loaded so
	 * a successful delete doesn't leave stale rows visible.
	 *
	 * @return array{action: string, count: int}|null Info about the
	 *         completed action (for rendering an admin notice), or null if
	 *         no bulk action was processed.
	 */
	public function process_bulk_action() {
		$action = $this->current_action();
		if ( 'delete' !== $action ) {
			return null;
		}

		// current_action() returns the value of action or action2 — either
		// dropdown position. WP_List_Table adds a hidden nonce field named
		// _wpnonce under the 'bulk-leads' action when display_tablenav() is
		// called. See WP_List_Table::bulk_actions().
		check_admin_referer( 'bulk-leads' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage leads.', 'wprobo-engage-lite' ) );
		}

		$ids = isset( $_REQUEST['lead'] ) && is_array( $_REQUEST['lead'] )
			? array_filter( array_map( 'absint', $_REQUEST['lead'] ) )
			: array();

		if ( empty( $ids ) ) {
			return null;
		}

		$count = $this->delete_leads( $ids );
		// Redirect to a clean URL so a browser refresh doesn't re-trigger
		// the nonce-protected request (which would wp_die on the second
		// submission). Carry the count as a status param for the notice.
		$redirect = add_query_arg(
			array(
				'page'        => 'wprobo-engage-leads',
				'wpr_deleted' => $count,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Delete leads by id. Safe against empty arrays.
	 *
	 * @param int[] $ids Positive integer IDs.
	 * @return int Number of rows deleted.
	 */
	private function delete_leads( array $ids ): int {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholder = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $placeholder is a whitelist of %d tokens built from count(); custom table, no WP API equivalent.
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wprobo_engage_leads WHERE id IN ({$placeholder})", $ids ) );
		// phpcs:enable
	}

	/**
	 * Prepare the items for the table.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		global $wpdb;

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get sorting parameters
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort parameter, no state change.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'id';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sort parameter, no state change.
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		// Validate orderby
		$allowed_orderby = array( 'id', 'email', 'submitted_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'id';
		}

		// Validate order
		$order = strtoupper( $order );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		// Build WHERE clause for filters
		$where_clauses = array();
		$where_values  = array();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter/search params for list table display, no state change.
		// Search filter (email)
		if ( ! empty( $_GET['s'] ) ) {
			$search          = sanitize_text_field( wp_unslash( $_GET['s'] ) );
			$where_clauses[] = 'email LIKE %s';
			$where_values[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Campaign filter
		if ( ! empty( $_GET['campaign_id'] ) && 'all' !== $_GET['campaign_id'] ) {
			$campaign_id     = absint( $_GET['campaign_id'] );
			$where_clauses[] = 'campaign_id = %d';
			$where_values[]  = $campaign_id;
		}
		// phpcs:enable

		// Build WHERE SQL
		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// Get total count with filters.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- custom table, no WP API equivalent.
		if ( ! empty( $where_values ) ) {
			$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_leads" . $where_sql;
			$total_items = $wpdb->get_var( $wpdb->prepare( $count_query, $where_values ) );
		} else {
			$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wprobo_engage_leads" );
		}

		// Get items for current page with filters.
		$query        = "SELECT * FROM {$wpdb->prefix}wprobo_engage_leads" . $where_sql . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$query_values = array_merge( $where_values, array( $per_page, $offset ) );

		$this->items = $wpdb->get_results(
			$wpdb->prepare( $query, $query_values ),
			ARRAY_A
		);
		// phpcs:enable

		// Set pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Display extra tablenav (filters).
	 *
	 * @param string $which Top or bottom.
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter parameter, no state change.
		$selected_campaign = isset( $_GET['campaign_id'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_id'] ) ) : 'all';

		// Get all campaigns for filter dropdown
		$campaigns = get_posts(
			array(
				'post_type'      => 'wpr_campaign',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="alignleft actions">
			<label for="filter-by-campaign" class="screen-reader-text">
				<?php esc_html_e( 'Filter by campaign', 'wprobo-engage-lite' ); ?>
			</label>
			<select name="campaign_id" id="filter-by-campaign">
				<option value="all" <?php selected( $selected_campaign, 'all' ); ?>>
					<?php esc_html_e( 'All Campaigns', 'wprobo-engage-lite' ); ?>
				</option>
				<?php foreach ( $campaigns as $campaign ) : ?>
					<option value="<?php echo esc_attr( $campaign->ID ); ?>" <?php selected( $selected_campaign, $campaign->ID ); ?>>
						<?php echo esc_html( $campaign->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Filter', 'wprobo-engage-lite' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Default column renderer.
	 *
	 * @param array  $item        The item data.
	 * @param string $column_name The column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return esc_html( $item['id'] );
			case 'email':
				return esc_html( $item['email'] );
			case 'form_data':
				return $this->render_form_data( $item );
			case 'campaign':
				$campaign_id = absint( $item['campaign_id'] );
				$campaign    = get_post( $campaign_id );
				return $campaign ? esc_html( $campaign->post_title ) : __( 'Unknown Campaign', 'wprobo-engage-lite' );
			case 'submitted_at':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['submitted_at'] ) );
			default:
				return '';
		}
	}

	/**
	 * Render form data column.
	 *
	 * @param array $item The lead item.
	 * @return string
	 */
	private function render_form_data( $item ): string {
		// Check if form_data exists
		if ( empty( $item['form_data'] ) ) {
			return '<em>' . esc_html__( 'No additional data', 'wprobo-engage-lite' ) . '</em>';
		}

		// Decode JSON form data
		$form_data = json_decode( $item['form_data'], true );
		if ( ! is_array( $form_data ) || empty( $form_data ) ) {
			return '<em>' . esc_html__( 'No additional data', 'wprobo-engage-lite' ) . '</em>';
		}

		// Get campaign form fields to map labels
		$campaign_id = absint( $item['campaign_id'] );
		$form_fields = get_post_meta( $campaign_id, '_wpr_engage_form_fields', true );

		$pills    = array();
		$overflow = 0;
		$max_show = 3;
		$shown    = 0;

		foreach ( $form_data as $key => $value ) {
			// Skip the email field — it already has its own column.
			if ( 'email' === $key || ( is_string( $value ) && is_email( $value ) ) ) {
				continue;
			}

			// Resolve a human-readable label from the campaign's form config.
			$label = $key;
			if ( preg_match( '/^wpr_field_(\d+)$/', $key, $matches ) ) {
				$field_index = absint( $matches[1] );
				if ( ! empty( $form_fields[ $field_index ]['label'] ) ) {
					$label = $form_fields[ $field_index ]['label'];
				}
			}

			// Format booleans.
			$display_value = $value;
			if ( 'yes' === $value || '1' === $value ) {
				$display_value = __( 'Yes', 'wprobo-engage-lite' );
			} elseif ( 'no' === $value || '0' === $value ) {
				$display_value = __( 'No', 'wprobo-engage-lite' );
			}

			// Trim very long values in the summary to keep the pill compact.
			$display_string = (string) $display_value;
			if ( strlen( $display_string ) > 40 ) {
				$display_string = rtrim( mb_substr( $display_string, 0, 40 ) ) . '…';
			}

			if ( $shown < $max_show ) {
				$pills[] = sprintf(
					'<span class="wpr-lead-pill" title="%1$s: %2$s" style="display:inline-flex;align-items:center;gap:4px;padding:2px 8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:999px;font-size:12px;color:#334155;margin-right:4px;margin-bottom:4px;"><strong style="color:#475569;font-weight:600;">%3$s:</strong> %4$s</span>',
					esc_attr( $label ),
					esc_attr( (string) $display_value ),
					esc_html( ucfirst( $label ) ),
					esc_html( $display_string )
				);
				++$shown;
			} else {
				++$overflow;
			}
		}

		if ( empty( $pills ) ) {
			return '<em>' . esc_html__( 'No additional data', 'wprobo-engage-lite' ) . '</em>';
		}

		$output = '<div class="wpr-form-data-display">' . implode( '', $pills );
		if ( $overflow > 0 ) {
			$output .= sprintf(
				'<span style="display:inline-block;padding:2px 8px;font-size:11px;color:#64748b;">+%d %s</span>',
				$overflow,
				esc_html( _n( 'more', 'more', $overflow, 'wprobo-engage-lite' ) )
			);
		}
		$output .= '</div>';

		return $output;
	}
}
