<?php
namespace WPRobo_Engage_Lite\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/**
 * Class Template_Library
 *
 * Handles the template library functionality including listing templates,
 * creating campaigns from templates, and displaying the template selection page.
 *
 * @package WPRobo_Engage_Lite\Admin
 */
class Template_Library {

	/**
	 * Get all available templates from the library directory.
	 *
	 * @param string $category Optional category to filter by.
	 * @param string $search Optional search term.
	 * @return array Array of template data.
	 */
	public function get_templates( string $category = '', string $search = '' ): array {
		$templates_dir = WPROBO_ENGAGE_LITE_PATH . 'templates/library/';
		$templates     = array();

		if ( ! is_dir( $templates_dir ) ) {
			return $templates;
		}

		$files = glob( $templates_dir . '*.json' );

		foreach ( $files as $file ) {
			$template_data = json_decode( file_get_contents( $file ), true );

			if ( $template_data && is_array( $template_data ) ) {
				$template_data['id'] = basename( $file, '.json' );

				// Apply category filter
				if ( $category && ! empty( $template_data['category'] ) && $template_data['category'] !== $category ) {
					continue;
				}

				// Apply search filter
				if ( $search ) {
					$search_lower = strtolower( $search );
					$name_match   = isset( $template_data['name'] ) && stripos( $template_data['name'], $search ) !== false;
					$desc_match   = isset( $template_data['description'] ) && stripos( $template_data['description'], $search ) !== false;
					$tags_match   = false;

					if ( isset( $template_data['tags'] ) && is_array( $template_data['tags'] ) ) {
						foreach ( $template_data['tags'] as $tag ) {
							if ( stripos( $tag, $search ) !== false ) {
								$tags_match = true;
								break;
							}
						}
					}

					if ( ! $name_match && ! $desc_match && ! $tags_match ) {
						continue;
					}
				}

				$templates[] = $template_data;
			}
		}

		return $templates;
	}

	/**
	 * Get all unique categories from templates.
	 *
	 * @return array Array of categories, slug => display name.
	 */
	public function get_categories(): array {
		$templates_dir = WPROBO_ENGAGE_LITE_PATH . 'templates/library/';
		$categories    = array();

		if ( ! is_dir( $templates_dir ) ) {
			return $categories;
		}

		$files = glob( $templates_dir . '*.json' );

		foreach ( $files as $file ) {
			$template_data = json_decode( file_get_contents( $file ), true );

			if ( $template_data && is_array( $template_data ) && ! empty( $template_data['category'] ) ) {
				$categories[ $template_data['category'] ] = ucwords( str_replace( '-', ' ', $template_data['category'] ) );
			}
		}

		return $categories;
	}

	/**
	 * Get the number of templates in each category.
	 *
	 * @return array Array of category slug => count.
	 */
	public function get_category_counts(): array {
		$counts = array();

		foreach ( $this->get_templates() as $template ) {
			if ( empty( $template['category'] ) ) {
				continue;
			}
			$slug            = $template['category'];
			$counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * Create a new campaign from a template.
	 *
	 * @param string $template_id The template ID to use.
	 * @return int|false The new campaign post ID or false on failure.
	 */
	public function create_campaign_from_template( string $template_id ) {
		// Verify nonce
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'create_from_template' ) ) {
			return false;
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$template_file = WPROBO_ENGAGE_LITE_PATH . 'templates/library/' . sanitize_file_name( $template_id ) . '.json';

		if ( ! file_exists( $template_file ) ) {
			return false;
		}

		$template_data = json_decode( file_get_contents( $template_file ), true );

		if ( ! $template_data || ! isset( $template_data['settings'] ) ) {
			return false;
		}

		// Create the campaign post
		$campaign_title = isset( $template_data['name'] ) ? $template_data['name'] : __( 'New Campaign', 'wprobo-engage-lite' );

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

		// Apply template settings to the campaign
		$settings = $template_data['settings'];

		foreach ( $settings as $key => $value ) {
			$meta_key = '_wpr_engage_' . $key;
			update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
		}

		return $post_id;
	}

	/**
	 * Render the template selection page.
	 *
	 * @return void
	 */
	public function render_template_selection_page(): void {
		// Get filter parameters
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter parameter, no state change.
		$category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search parameter, no state change.
		$search   = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		$templates               = $this->get_templates( $category, $search );
		$categories              = $this->get_categories();
		$category_counts         = $this->get_category_counts();
		$total_templates_count   = count( $this->get_templates() );
		$filtered_template_count = count( $templates );
		$has_active_filters      = (bool) ( $category || $search );
		?>
		<div class="wpr-template-stage wpr-p-8 wpr-max-w-none wpr-mx-0">
			<div class="wpr-mb-8 wpr-flex wpr-flex-wrap wpr-justify-between wpr-items-center wpr-gap-3">
				<div class="wpr-flex wpr-items-center wpr-gap-2 wpr-text-xs wpr-font-semibold wpr-uppercase wpr-tracking-wider wpr-text-slate-500">
					<span class="wpr-kpi-chip wpr-px-3 wpr-py-1.5 wpr-rounded-full">
						<?php
						/* translators: %d: number of templates */
						echo esc_html( sprintf( _n( '%d template available', '%d templates available', $total_templates_count, 'wprobo-engage-lite' ), $total_templates_count ) );
						?>
					</span>
					<span class="wpr-px-3 wpr-py-1.5 wpr-rounded-full wpr-border wpr-border-slate-200 wpr-bg-white">
						<?php esc_html_e( 'Built for conversion campaigns', 'wprobo-engage-lite' ); ?>
					</span>
				</div>

				<?php if ( $has_active_filters ) : ?>
					<div class="wpr-text-xs wpr-font-semibold wpr-text-slate-500 wpr-bg-white wpr-border wpr-border-slate-200 wpr-rounded-full wpr-px-3 wpr-py-1.5">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: visible template count, 2: total template count */
								_n( 'Showing %1$d of %2$d template', 'Showing %1$d of %2$d templates', $total_templates_count, 'wprobo-engage-lite' ),
								$filtered_template_count,
								$total_templates_count
							)
						);
						?>
					</div>
				<?php endif; ?>
			</div>

			<div class="wpr-mb-10 wpr-flex wpr-justify-between wpr-items-end wpr-gap-4">
				<div>
					<h1 class="wpr-text-4xl wpr-font-black wpr-text-slate-950 wpr-tracking-tight wpr-mb-3">
						<?php esc_html_e( 'Choose a Template', 'wprobo-engage-lite' ); ?>
					</h1>
					<p class="wpr-text-lg wpr-text-slate-600 wpr-max-w-3xl">
						<?php esc_html_e( 'Launch a high-converting campaign in minutes. Start with a proven template, customize copy and colors, and ship with confidence.', 'wprobo-engage-lite' ); ?>
					</p>
				</div>
			</div>

			<div class="wpr-template-toolbar wpr-mb-8 wpr-p-6 wpr-rounded-2xl wpr-shadow-sm wpr-border wpr-border-slate-200">
				<form method="get" action="" class="wpr-template-filter-row wpr-flex wpr-gap-5 wpr-flex-wrap wpr-items-center wpr-w-full">
					<input type="hidden" name="page" value="wprobo-engage-templates">
					
					<input type="hidden" name="category" value="<?php echo esc_attr( $category ); ?>">


					<div class="wpr-flex wpr-items-center wpr-gap-3 wpr-flex-1 wpr-mobile-full">
						<div class="wpr-flex-1">
							<label for="search-filter" class="screen-reader-text">
								<?php esc_html_e( 'Search templates', 'wprobo-engage-lite' ); ?>
							</label>
							<div class="wpr-search-shell">
								<svg class="wpr-h-5 wpr-w-5 wpr-text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
								</svg>
								<input type="text" name="search" id="search-filter" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by objective, niche, or keyword...', 'wprobo-engage-lite' ); ?>" class="wpr-filter-input">
							</div>
						</div>
						<button type="submit" class="wpr-focus-ring wpr-btn-premium wpr-text-white wpr-rounded-xl wpr-px-6 wpr-py-2.5 wpr-text-sm wpr-font-semibold wpr-shadow-sm" style="flex-shrink: 0;">
							<?php esc_html_e( 'Find Templates', 'wprobo-engage-lite' ); ?>
						</button>
					</div>

					<?php if ( $has_active_filters ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-engage-templates' ) ); ?>" class="wpr-focus-ring wpr-text-slate-500 wpr-text-sm wpr-font-semibold hover:wpr-text-slate-900 wpr-transition-colors wpr-rounded-lg wpr-px-2 wpr-py-1">
							<?php esc_html_e( 'Reset Filters', 'wprobo-engage-lite' ); ?>
						</a>
					<?php endif; ?>
				</form>
			</div>

			<?php if ( ! empty( $categories ) ) : ?>
				<div class="wpr-mb-8 wpr-flex wpr-flex-wrap wpr-gap-2">
					<a class="wpr-focus-ring wpr-rounded-full wpr-px-3 wpr-py-1.5 wpr-text-xs wpr-font-semibold wpr-transition-colors <?php echo empty( $category ) ? 'wpr-bg-slate-900 wpr-text-white' : 'wpr-bg-white wpr-text-slate-600 wpr-border wpr-border-slate-200 hover:wpr-border-slate-300'; ?>" aria-current="<?php echo empty( $category ) ? 'page' : 'false'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-engage-templates' ) ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d is total template count */
								__( 'All (%d)', 'wprobo-engage-lite' ),
								$total_templates_count
							)
						);
						?>
					</a>
					<?php foreach ( $categories as $cat_slug => $cat_name ) : ?>
						<?php
						$chip_url   = add_query_arg(
							array(
								'page'     => 'wprobo-engage-templates',
								'category' => $cat_slug,
								'search'   => $search,
							),
							admin_url( 'admin.php' )
						);
						$cat_count  = $category_counts[ $cat_slug ] ?? 0;
						$chip_label = sprintf(
							/* translators: 1: category name, 2: template count */
							__( '%1$s (%2$d)', 'wprobo-engage-lite' ),
							$cat_name,
							$cat_count
						);
						?>
						<a class="wpr-focus-ring wpr-rounded-full wpr-px-3 wpr-py-1.5 wpr-text-xs wpr-font-semibold wpr-transition-colors <?php echo $category === $cat_slug ? 'wpr-bg-slate-900 wpr-text-white' : 'wpr-bg-white wpr-text-slate-600 wpr-border wpr-border-slate-200 hover:wpr-border-slate-300'; ?>" aria-current="<?php echo $category === $cat_slug ? 'page' : 'false'; ?>" href="<?php echo esc_url( $chip_url ); ?>">
							<?php echo esc_html( $chip_label ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $has_active_filters ) : ?>
				<div class="wpr-mb-6 wpr-text-sm wpr-text-slate-600">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d is number of templates after filter */
							_n( 'Showing %d matching template.', 'Showing %d matching templates.', $filtered_template_count, 'wprobo-engage-lite' ),
							$filtered_template_count
						)
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( empty( $templates ) ) : ?>
				<div class="wpr-py-20 wpr-text-center wpr-bg-white wpr-rounded-3xl wpr-border wpr-border-dashed wpr-border-slate-300">
					<p class="wpr-text-slate-500 wpr-text-lg wpr-font-medium">
						<?php esc_html_e( 'No templates match your current filters.', 'wprobo-engage-lite' ); ?>
					</p>
					<p class="wpr-mt-2 wpr-text-slate-500">
						<?php esc_html_e( 'Try a broader keyword or switch category to discover more options.', 'wprobo-engage-lite' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wprobo-engage-templates' ) ); ?>" class="wpr-focus-ring wpr-mt-6 wpr-inline-block wpr-text-slate-900 wpr-font-bold wpr-rounded-lg wpr-px-2 wpr-py-1">
						<?php esc_html_e( 'Show All Templates', 'wprobo-engage-lite' ); ?> &rarr;
					</a>
				</div>
			<?php else : ?>
				<div class="wpr-grid wpr-grid-cols-1 md:wpr-grid-cols-2 lg:wpr-grid-cols-3 wpr-gap-8">
					<?php
					$blank_url = add_query_arg(
						array(
							'page'        => 'wprobo-engage-templates',
							'action'      => 'create',
							'template_id' => 'blank',
							'_wpnonce'    => wp_create_nonce( 'create_from_template' ),
						),
						admin_url( 'admin.php' )
					);
					?>
					<div class="wpr-bg-white wpr-rounded-2xl wpr-premium-card wpr-border wpr-border-slate-200 wpr-flex wpr-flex-col">
						<div class="wpr-bg-slate-50 wpr-h-56 wpr-flex wpr-flex-col wpr-items-center wpr-justify-center wpr-transition-colors">
							<div class="wpr-w-14 wpr-h-14 wpr-bg-white wpr-rounded-2xl wpr-flex wpr-items-center wpr-justify-center wpr-shadow-sm wpr-mb-4 wpr-border wpr-border-slate-200">
								<svg class="wpr-w-7 wpr-h-7 wpr-text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
								</svg>
							</div>
							<h3 class="wpr-text-xl wpr-font-bold wpr-text-slate-900 wpr-tracking-tight">
								<?php esc_html_e( 'Blank Canvas', 'wprobo-engage-lite' ); ?>
							</h3>
							<span class="wpr-mt-2 wpr-px-2.5 wpr-py-1 wpr-rounded-full wpr-text-[10px] wpr-font-bold wpr-uppercase wpr-tracking-wide wpr-text-slate-500 wpr-bg-white wpr-border wpr-border-slate-200">
								<?php esc_html_e( 'Advanced', 'wprobo-engage-lite' ); ?>
							</span>
						</div>
						<div class="wpr-p-6 wpr-flex wpr-flex-col wpr-flex-1">
							<p class="wpr-text-slate-600 wpr-mb-6 wpr-text-sm wpr-leading-relaxed">
								<?php esc_html_e( 'The professional choice. Build your custom campaign from the ground up with total design freedom.', 'wprobo-engage-lite' ); ?>
							</p>
							<div class="wpr-mt-auto">
								<a href="<?php echo esc_url( $blank_url ); ?>" class="wpr-focus-ring wpr-flex wpr-items-center wpr-justify-center wpr-w-full wpr-bg-white wpr-text-slate-800 wpr-font-bold wpr-py-3 wpr-rounded-xl wpr-border wpr-border-slate-300 hover:wpr-bg-slate-50 wpr-transition-all wpr-text-sm">
									<?php esc_html_e( 'Start from Scratch', 'wprobo-engage-lite' ); ?>
								</a>
							</div>
						</div>
					</div>

					<?php foreach ( $templates as $template ) : ?>
						<?php
						$create_url          = add_query_arg(
							array(
								'page'        => 'wprobo-engage-templates',
								'action'      => 'create',
								'template_id' => $template['id'],
								'_wpnonce'    => wp_create_nonce( 'create_from_template' ),
							),
							admin_url( 'admin.php' )
						);
						$category_label      = isset( $template['category'] ) ? str_replace( '-', ' ', $template['category'] ) : __( 'General', 'wprobo-engage-lite' );
						$campaign_type       = isset( $template['settings']['campaign_type'] ) ? $template['settings']['campaign_type'] : __( 'popup', 'wprobo-engage-lite' );
						$preview_bg          = isset( $template['settings']['style_bg_color'] ) ? sanitize_hex_color( $template['settings']['style_bg_color'] ) : '#f8fafc';
						$preview_text_color  = isset( $template['settings']['style_text_color'] ) ? sanitize_hex_color( $template['settings']['style_text_color'] ) : '#0f172a';
						$preview_btn_bg      = isset( $template['settings']['style_button_bg_color'] ) ? sanitize_hex_color( $template['settings']['style_button_bg_color'] ) : '#1d4ed8';
						$preview_btn_text    = isset( $template['settings']['style_button_text_color'] ) ? sanitize_hex_color( $template['settings']['style_button_text_color'] ) : '#ffffff';
						$preview_headline    = isset( $template['settings']['design_headline'] ) ? wp_strip_all_tags( $template['settings']['design_headline'] ) : $template['name'];
						$preview_button_text = isset( $template['settings']['design_button'] ) ? wp_strip_all_tags( $template['settings']['design_button'] ) : __( 'Learn More', 'wprobo-engage-lite' );
						$preview_style       = sprintf( 'background:%1$s;color:%2$s;', esc_attr( $preview_bg ? $preview_bg : '#f8fafc' ), esc_attr( $preview_text_color ? $preview_text_color : '#0f172a' ) );
						$preview_button_css  = sprintf( 'background:%1$s;color:%2$s;', esc_attr( $preview_btn_bg ? $preview_btn_bg : '#1d4ed8' ), esc_attr( $preview_btn_text ? $preview_btn_text : '#ffffff' ) );
						?>
						<div class="wpr-bg-white wpr-rounded-2xl wpr-premium-card wpr-border wpr-border-slate-200 wpr-flex wpr-flex-col">
							<div class="wpr-template-thumb wpr-bg-slate-50 wpr-h-56 wpr-flex wpr-items-center wpr-justify-center wpr-relative wpr-p-4">
								<span class="wpr-absolute wpr-top-4 wpr-left-4 wpr-px-2.5 wpr-py-1 wpr-bg-white wpr-rounded-full wpr-text-[10px] wpr-font-bold wpr-uppercase wpr-tracking-wide wpr-text-slate-500 wpr-border wpr-border-slate-200">
									<?php echo esc_html( ucfirst( str_replace( '-', ' ', $campaign_type ) ) ); ?>
								</span>
								<span class="wpr-absolute wpr-top-4 wpr-right-4 wpr-px-2.5 wpr-py-1 wpr-bg-white wpr-rounded-full wpr-text-[10px] wpr-font-bold wpr-uppercase wpr-tracking-wide wpr-text-slate-500 wpr-border wpr-border-slate-200">
									<?php echo esc_html( $category_label ); ?>
								</span>

								<div class="wpr-template-preview wpr-rounded-2xl wpr-p-4 wpr-w-full" style="<?php echo esc_attr( $preview_style ); ?>">
									<div class="wpr-text-xs wpr-font-semibold wpr-opacity-70 wpr-mb-2"><?php esc_html_e( 'Template Preview', 'wprobo-engage-lite' ); ?></div>
									<div class="wpr-text-lg wpr-font-bold wpr-leading-tight wpr-mb-3"><?php echo esc_html( wp_trim_words( $preview_headline, 9, '...' ) ); ?></div>
									<div class="wpr-inline-flex wpr-items-center wpr-justify-center wpr-rounded-lg wpr-px-3 wpr-py-1.5 wpr-text-xs wpr-font-semibold wpr-template-preview-button" style="<?php echo esc_attr( $preview_button_css ); ?>">
										<?php echo esc_html( wp_trim_words( $preview_button_text, 4, '' ) ); ?>
									</div>
								</div>

								<!-- Hover overlay: larger preview button. Visible only on card hover via CSS below. -->
								<button type="button"
									class="wpr-template-preview-btn"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: template name */ __( 'Preview %s template', 'wprobo-engage-lite' ), $template['name'] ) ); ?>"
									data-name="<?php echo esc_attr( $template['name'] ); ?>"
									data-description="<?php echo esc_attr( $template['description'] ?? '' ); ?>"
									data-campaign-type="<?php echo esc_attr( ucfirst( str_replace( '-', ' ', $campaign_type ) ) ); ?>"
									data-category="<?php echo esc_attr( $category_label ); ?>"
									data-preview-bg="<?php echo esc_attr( $preview_bg ? $preview_bg : '#f8fafc' ); ?>"
									data-preview-text="<?php echo esc_attr( $preview_text_color ? $preview_text_color : '#0f172a' ); ?>"
									data-preview-btn-bg="<?php echo esc_attr( $preview_btn_bg ? $preview_btn_bg : '#1d4ed8' ); ?>"
									data-preview-btn-text="<?php echo esc_attr( $preview_btn_text ? $preview_btn_text : '#ffffff' ); ?>"
									data-headline="<?php echo esc_attr( $preview_headline ); ?>"
									data-button-text="<?php echo esc_attr( $preview_button_text ); ?>"
									data-use-url="<?php echo esc_url( $create_url ); ?>">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<?php esc_html_e( 'Preview', 'wprobo-engage-lite' ); ?>
								</button>
							</div>
							
							<div class="wpr-p-6 wpr-flex wpr-flex-col wpr-flex-1">
								<h3 class="wpr-text-xl wpr-font-bold wpr-text-slate-900 wpr-tracking-tight wpr-mb-2">
									<?php echo esc_html( $template['name'] ); ?>
								</h3>
								<p class="wpr-text-slate-600 wpr-mb-6 wpr-text-sm wpr-leading-relaxed">
									<?php echo esc_html( $template['description'] ); ?>
								</p>
								
								<?php if ( isset( $template['tags'] ) && is_array( $template['tags'] ) ) : ?>
									<div class="wpr-flex wpr-flex-wrap wpr-gap-2 wpr-mb-6">
										<?php foreach ( array_slice( $template['tags'], 0, 3 ) as $tag ) : ?>
											<span class="wpr-px-2.5 wpr-py-1 wpr-bg-slate-100 wpr-text-slate-600 wpr-rounded-md wpr-text-[11px] wpr-font-semibold">
												#<?php echo esc_html( $tag ); ?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>

								<div class="wpr-mt-auto">
									<a href="<?php echo esc_url( $create_url ); ?>" class="wpr-focus-ring wpr-flex wpr-items-center wpr-justify-center wpr-w-full wpr-btn-premium wpr-text-white wpr-font-bold wpr-py-3 wpr-rounded-lg wpr-shadow-sm wpr-text-sm">
										<?php esc_html_e( 'Use This Template', 'wprobo-engage-lite' ); ?>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

				</div>
			<?php endif; ?>
		</div>

		<!-- Template Preview Modal -->
		<div id="wpr-template-preview-modal" class="wpr-tpm-hidden" role="dialog" aria-modal="true" aria-labelledby="wpr-tpm-title" aria-describedby="wpr-tpm-desc">
			<div class="wpr-tpm-backdrop" data-tpm-close></div>
			<div class="wpr-tpm-content" role="document">
				<button type="button" class="wpr-tpm-close" aria-label="<?php esc_attr_e( 'Close preview', 'wprobo-engage-lite' ); ?>" data-tpm-close>
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>
				<div class="wpr-tpm-header">
					<h2 id="wpr-tpm-title" class="wpr-tpm-title"></h2>
					<div class="wpr-tpm-meta">
						<span id="wpr-tpm-type" class="wpr-tpm-chip"></span>
						<span id="wpr-tpm-category" class="wpr-tpm-chip"></span>
					</div>
					<p id="wpr-tpm-desc" class="wpr-tpm-desc"></p>
				</div>
				<div class="wpr-tpm-frame">
					<div id="wpr-tpm-preview" class="wpr-tpm-preview-card">
						<div id="wpr-tpm-headline" class="wpr-tpm-preview-headline"></div>
						<div id="wpr-tpm-email" class="wpr-tpm-preview-email"></div>
						<button type="button" id="wpr-tpm-btn" class="wpr-tpm-preview-button" tabindex="-1"></button>
					</div>
				</div>
				<div class="wpr-tpm-footer">
					<a id="wpr-tpm-use" href="#" class="wpr-focus-ring wpr-btn-premium wpr-text-white wpr-font-bold wpr-py-3 wpr-px-6 wpr-rounded-xl wpr-shadow-sm wpr-text-sm">
						<?php esc_html_e( 'Use This Template', 'wprobo-engage-lite' ); ?>
					</a>
				</div>
			</div>
		</div>

		<?php
	}


	/**
	 * Handle template selection and campaign creation.
	 *
	 * @return void
	 */
	public function handle_template_action(): void {
		if ( ! isset( $_GET['action'] ) || 'create' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['template_id'] ) ) {
			wp_die( esc_html__( 'No template selected.', 'wprobo-engage-lite' ) );
		}

		$template_id = sanitize_text_field( wp_unslash( $_GET['template_id'] ) );

		// Handle blank template
		if ( 'blank' === $template_id ) {
			// Verify nonce
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'create_from_template' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wprobo-engage-lite' ) );
			}

			// Check user permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to create campaigns.', 'wprobo-engage-lite' ) );
			}

			// Create blank campaign
			$post_id = wp_insert_post(
				array(
					'post_title'  => __( 'New Campaign', 'wprobo-engage-lite' ),
					'post_type'   => 'wpr_campaign',
					'post_status' => 'draft',
				)
			);

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				wp_die( esc_html__( 'Failed to create campaign.', 'wprobo-engage-lite' ) );
			}

			// Set default campaign type
			update_post_meta( $post_id, '_wpr_engage_campaign_type', 'popup' );
			update_post_meta( $post_id, '_wpr_engage_campaign_status', 'draft' );
		} else {
			// Create from template
			$post_id = $this->create_campaign_from_template( $template_id );

			if ( ! $post_id ) {
				wp_die( esc_html__( 'Failed to create campaign from template.', 'wprobo-engage-lite' ) );
			}
		}

		// Redirect to the post editor
		$redirect_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
