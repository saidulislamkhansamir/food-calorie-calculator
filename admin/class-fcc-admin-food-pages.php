<?php
/**
 * Admin: Food Pages — manage content for hub, category, and food pages.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Food_Pages_Admin {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_save_hub_content',  $this, 'save_hub_content' );
		$loader->add_action( 'admin_post_fcc_save_hub_seo',      $this, 'save_hub_seo' );
		$loader->add_action( 'admin_post_fcc_save_cat_content',  $this, 'save_cat_content' );
		$loader->add_action( 'admin_post_fcc_save_cat_seo',      $this, 'save_cat_seo' );
		$loader->add_action( 'admin_post_fcc_save_food_seo',     $this, 'save_food_seo' );
		$loader->add_action( 'wp_ajax_fcc_food_pages_list',      $this, 'ajax_food_pages_list' );
	}

	// -------------------------------------------------------------------------
	// AJAX: paginated food list.
	// -------------------------------------------------------------------------

	public function ajax_food_pages_list(): void {
		check_ajax_referer( 'fcc_food_pages_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.' );
		}

		$search   = isset( $_POST['search'] )   ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$paged    = max( 1, absint( $_POST['paged'] ?? 1 ) );
		$per_page = in_array( (int) ( $_POST['per_page'] ?? 50 ), [ 50, 100, 250, 500 ], true )
			? (int) $_POST['per_page'] : 50;

		$result = \FCC\Database::get_foods( [
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $paged,
			'orderby'  => 'name',
			'order'    => 'ASC',
		] );

		$foods       = $result['rows'];
		$total       = $result['total'];
		$total_pages = (int) ceil( $total / $per_page );

		$categories   = \FCC\Database::get_all_categories();
		$cat_map      = [];
		$cat_slug_map = [];
		foreach ( $categories as $cat ) {
			$cat_map[ (int) $cat['id'] ]      = $cat['name'];
			$cat_slug_map[ (int) $cat['id'] ] = $cat['slug'];
		}

		wp_send_json_success( [
			'rows'            => self::render_food_rows( $foods, $cat_map, $cat_slug_map ),
			'pagination'      => self::render_pagination_html( $paged, $total_pages, $total, $per_page, $search ),
			'pagination_top'  => self::render_pagination_html( $paged, $total_pages, $total, $per_page, $search, 'top' ),
			'total'           => $total,
		] );
	}

	// -------------------------------------------------------------------------
	// Static renderers — called from both initial page load and AJAX.
	// -------------------------------------------------------------------------

	public static function render_food_rows( array $foods, array $cat_map, array $cat_slug_map ): string {
		if ( empty( $foods ) ) {
			return '<tr><td colspan="6" style="text-align:center;padding:48px 24px;color:#94a3b8;">'
				. esc_html__( 'No food pages found.', 'food-calorie-calculator' )
				. '</td></tr>';
		}
		$seo_nonce = wp_create_nonce( 'fcc_save_food_seo' );
		ob_start();
		foreach ( $foods as $food ) :
			$cid         = (int) $food['category_id'];
			$cat_slug    = $cat_slug_map[ $cid ] ?? 'uncategorised';
			$cat_name    = $cat_map[ $cid ] ?? '—';
			$food_url    = home_url( '/calories/' . $cat_slug . '/' . $food['slug'] . '/' );
			$has_content = ! empty( $food['page_content'] );
			$has_seo     = ! empty( $food['seo_title'] ) || ! empty( $food['seo_description'] );
			$edit_url    = admin_url( 'admin.php?page=fcc-foods&action=edit&food_id=' . (int) $food['id'] );
			$seo_row_id  = 'fcc-seo-edit-' . (int) $food['id'];
			$auto_title  = \FCC\Food_Pages::generate_food_title( $food );
			$auto_desc   = \FCC\Food_Pages::generate_food_meta_desc( $food );
		?>
		<tr id="fcc-food-row-<?php echo (int) $food['id']; ?>">
			<td class="fcc-fp-food-name"><?php echo esc_html( $food['name'] ); ?></td>
			<td class="fcc-fp-cat-label"><?php echo esc_html( $cat_name ); ?></td>
			<td>
				<a href="<?php echo esc_url( $food_url ); ?>" target="_blank" class="fcc-fp-table-url">
					/calories/<?php echo esc_html( $cat_slug . '/' . $food['slug'] ); ?>/
					<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
				</a>
			</td>
			<td><?php if ( $has_content ) : ?>
				<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; Custom</span>
			<?php else : ?>
				<span class="fcc-fp-badge fcc-fp-badge--auto">Auto</span>
			<?php endif; ?></td>
			<td><?php if ( $has_seo ) : ?>
				<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; Custom</span>
			<?php else : ?>
				<span class="fcc-fp-badge fcc-fp-badge--auto">Auto</span>
			<?php endif; ?></td>
			<td>
				<div class="fcc-fp-actions">
					<a href="<?php echo esc_url( $food_url ); ?>" target="_blank" class="fcc-fp-btn-view">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
						View
					</a>
					<a href="<?php echo esc_url( $edit_url ); ?>" class="fcc-fp-btn-edit">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
						Edit
					</a>
					<button type="button" class="fcc-fp-btn-toggle fcc-fp-seo-toggle" data-target="<?php echo esc_attr( $seo_row_id ); ?>">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						SEO
					</button>
				</div>
			</td>
		</tr>
		<tr id="<?php echo esc_attr( $seo_row_id ); ?>" class="fcc-fp-edit-row" style="display:none;">
			<td colspan="6">
				<div class="fcc-fp-edit-inner">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="fcc_save_food_seo">
						<input type="hidden" name="food_id" value="<?php echo (int) $food['id']; ?>">
						<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $seo_nonce ); ?>">
						<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
							<div>
								<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
									<span>SEO Title Override</span>
									<span class="fcc-seo-chr" data-max="60" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
								</label>
								<input type="text" name="seo_title" class="fcc-seo-title-input"
									value="<?php echo esc_attr( $food['seo_title'] ?? '' ); ?>"
									maxlength="60" placeholder="<?php echo esc_attr( $auto_title ); ?>"
									style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;">
								<p class="description" style="margin-top:6px;">
									Auto: <em style="color:#475569;"><?php echo esc_html( $auto_title ); ?></em>
									<span style="color:#94a3b8;">(<?php echo mb_strlen( $auto_title ); ?> chars)</span>
								</p>
							</div>
							<div>
								<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
									<span>SEO Description Override</span>
									<span class="fcc-seo-chr" data-max="160" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
								</label>
								<textarea name="seo_description" class="fcc-seo-desc-input" rows="3" maxlength="160"
									placeholder="<?php echo esc_attr( $auto_desc ); ?>"
									style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;resize:vertical;"><?php echo esc_textarea( $food['seo_description'] ?? '' ); ?></textarea>
								<p class="description" style="margin-top:6px;">
									Auto: <em style="color:#475569;"><?php echo esc_html( mb_strimwidth( $auto_desc, 0, 80, '…' ) ); ?></em>
									<span style="color:#94a3b8;">(<?php echo mb_strlen( $auto_desc ); ?> chars)</span>
								</p>
							</div>
						</div>
						<p class="description" style="margin-bottom:12px;color:#64748b;">Leave both blank to use auto-generated values.</p>
						<div class="fcc-fp-edit-actions">
							<button type="submit" class="fcc-fp-btn-save">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
								Save SEO
							</button>
							<button type="button" class="fcc-fp-btn-cancel fcc-fp-seo-cancel" data-target="<?php echo esc_attr( $seo_row_id ); ?>">Cancel</button>
							<?php if ( $has_seo ) : ?>
								<button type="submit" name="seo_title" value="" class="fcc-fp-btn-cancel" style="color:#dc2626;border-color:#fca5a5;"
									onclick="this.form.querySelector('[name=seo_description]').value='';">
									&#10005; Clear overrides
								</button>
							<?php endif; ?>
						</div>
					</form>
				</div>
			</td>
		</tr>
		<?php endforeach;
		return ob_get_clean();
	}

	public static function render_pagination_html( int $paged, int $total_pages, int $total, int $per_page, string $search, string $position = 'bottom' ): string {
		if ( $total_pages < 1 ) { return ''; }

		// Build smart window: first 3, last 3, current ±2.
		$show = [];
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			if ( $i <= 3 || $i > $total_pages - 3 || abs( $i - $paged ) <= 2 ) {
				$show[] = $i;
			}
		}
		$show = array_unique( $show );
		sort( $show );

		$extra_class = 'top' === $position ? ' fcc-fp-pag--top' : '';

		ob_start();
		?>
		<div class="fcc-fp-pag<?php echo $extra_class; ?>">
			<div class="fcc-fp-pag-info">
				Page <strong><?php echo $paged; ?></strong> of <strong><?php echo $total_pages; ?></strong>
				&nbsp;·&nbsp; <strong><?php echo number_format( $total ); ?></strong> foods total
			</div>
			<div class="fcc-fp-pag-pages">
				<button class="fcc-fp-pag-btn fcc-fp-pag-prev" data-page="<?php echo max( 1, $paged - 1 ); ?>"
					<?php echo $paged <= 1 ? 'disabled' : ''; ?>>&#8592; Prev</button>
				<?php
				$prev_shown = 0;
				foreach ( $show as $p ) {
					if ( $prev_shown && $p - $prev_shown > 1 ) {
						echo '<span class="fcc-fp-pag-ellipsis">&hellip;</span>';
					}
					$cls = ( $p === $paged ) ? ' fcc-fp-pag-btn--active' : '';
					echo '<button class="fcc-fp-pag-btn' . $cls . '" data-page="' . $p . '">' . $p . '</button>';
					$prev_shown = $p;
				}
				?>
				<button class="fcc-fp-pag-btn fcc-fp-pag-next" data-page="<?php echo min( $total_pages, $paged + 1 ); ?>"
					<?php echo $paged >= $total_pages ? 'disabled' : ''; ?>>Next &#8594;</button>
			</div>
			<div class="fcc-fp-pag-controls">
				<div class="fcc-fp-pag-jump">
					<input type="number" class="fcc-fp-pag-jump-input" min="1" max="<?php echo $total_pages; ?>" placeholder="Page #">
					<button type="button" class="fcc-fp-pag-go">Go</button>
				</div>
				<select class="fcc-fp-per-page-sel">
					<?php foreach ( [ 50, 100, 250, 500 ] as $n ) : ?>
						<option value="<?php echo $n; ?>"<?php selected( $n, $per_page ); ?>><?php echo $n; ?> / page</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function page_food_pages(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-food-pages.php';
	}

	// -------------------------------------------------------------------------
	// Save handlers.
	// -------------------------------------------------------------------------

	public function save_hub_content(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_hub_content' );

		$hub_intro     = isset( $_POST['hub_intro'] )     ? wp_kses_post( wp_unslash( $_POST['hub_intro'] ) )     : '';
		$hub_editorial = isset( $_POST['hub_editorial'] ) ? wp_kses_post( wp_unslash( $_POST['hub_editorial'] ) ) : '';

		$all = \FCC\Settings::get_all();
		$all['content']['hub_intro']     = $hub_intro;
		$all['content']['hub_editorial'] = $hub_editorial;
		\FCC\Settings::save( $all );

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'hub' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function save_hub_seo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_hub_seo' );

		$seo_title       = isset( $_POST['hub_seo_title'] )       ? sanitize_text_field( wp_unslash( $_POST['hub_seo_title'] ) )       : '';
		$seo_description = isset( $_POST['hub_seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['hub_seo_description'] ) ) : '';

		$all = \FCC\Settings::get_all();
		$all['content']['hub_seo_title']       = $seo_title;
		$all['content']['hub_seo_description'] = $seo_description;
		\FCC\Settings::save( $all );

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'hub_seo' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function save_cat_seo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_cat_seo' );

		$cat_id          = absint( $_POST['cat_id'] ?? 0 );
		$seo_title       = isset( $_POST['seo_title'] )       ? sanitize_text_field( wp_unslash( $_POST['seo_title'] ) )       : '';
		$seo_description = isset( $_POST['seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_description'] ) ) : '';

		if ( $cat_id > 0 ) {
			\FCC\Database::update_category_seo( $cat_id, $seo_title, $seo_description );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'cat_seo', 'cat_id' => $cat_id ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function save_food_seo(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_food_seo' );

		$food_id         = absint( $_POST['food_id'] ?? 0 );
		$seo_title       = isset( $_POST['seo_title'] )       ? sanitize_text_field( wp_unslash( $_POST['seo_title'] ) )       : '';
		$seo_description = isset( $_POST['seo_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['seo_description'] ) ) : '';

		if ( $food_id > 0 ) {
			\FCC\Database::update_food_seo( $food_id, $seo_title, $seo_description );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'seo', 'food_id' => $food_id ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function save_cat_content(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_cat_content' );

		$cat_id = absint( $_POST['cat_id'] ?? 0 );
		$desc   = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( $cat_id > 0 ) {
			\FCC\Database::update_category_description( $cat_id, $desc );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'cat', 'cat_id' => $cat_id ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
