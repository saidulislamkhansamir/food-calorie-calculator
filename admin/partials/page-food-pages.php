<?php
/**
 * Admin: Food Pages — hub, category, and food page content manager.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$saved    = isset( $_GET['saved'] ) ? sanitize_key( $_GET['saved'] ) : '';
$saved_cat = isset( $_GET['cat_id'] ) ? absint( $_GET['cat_id'] ) : 0;

$hub_intro  = FCC\Settings::get( 'content.hub_intro', '' );
$categories = FCC\Database::get_all_categories();
$hub_url    = home_url( '/calories/' );

// Foods list (paginated + searchable).
$search   = isset( $_GET['fps'] ) ? sanitize_text_field( wp_unslash( $_GET['fps'] ) ) : '';
$paged    = max( 1, absint( $_GET['fpp'] ?? 1 ) );
$per_page = 50;

$result     = FCC\Database::get_foods( [
	'search'   => $search,
	'per_page' => $per_page,
	'page'     => $paged,
	'orderby'  => 'name',
	'order'    => 'ASC',
] );
$foods       = $result['rows'];
$total_foods = $result['total'];
$total_pages = (int) ceil( $total_foods / $per_page );

// Build category maps.
$cat_map      = [];
$cat_slug_map = [];
foreach ( $categories as $cat ) {
	$cat_map[ (int) $cat['id'] ]      = esc_html( $cat['name'] );
	$cat_slug_map[ (int) $cat['id'] ] = $cat['slug'];
}

$page_url = admin_url( 'admin.php?page=fcc-food-pages' );
?>
<div class="wrap fcc-admin-wrap fcc-food-pages-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></h1>

	<!-- Hero -->
	<div class="fcc-foods-hero">
		<div class="fcc-foods-hero__inner">
			<div class="fcc-foods-hero__content">
				<div class="fcc-foods-hero__icon" aria-hidden="true">🌐</div>
				<div>
					<div class="fcc-foods-hero__title"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-foods-hero__sub">
						<?php esc_html_e( 'Manage content for your hub page, category pages, and individual food pages.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>
			<div class="fcc-foods-hero__stats">
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo count( $categories ); ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Category Pages', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo number_format( $total_foods ); ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<?php if ( 'hub' === $saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Hub page content saved.', 'food-calorie-calculator' ); ?></p></div>
	<?php elseif ( 'cat' === $saved && $saved_cat ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Category description saved.', 'food-calorie-calculator' ); ?></p></div>
	<?php endif; ?>

	<!-- ======================================================================
	     1. HUB PAGE
	     ====================================================================== -->
	<div class="fcc-fp-section postbox" style="padding:20px 24px;margin-bottom:20px;">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
			<h2 style="margin:0;font-size:1.1rem;"><?php esc_html_e( 'Hub Page', 'food-calorie-calculator' ); ?>
				<span style="font-weight:400;font-size:0.85rem;color:#718096;margin-left:8px;"><?php echo esc_html( $hub_url ); ?></span>
			</h2>
			<a href="<?php echo esc_url( $hub_url ); ?>" target="_blank" class="button button-secondary">
				<?php esc_html_e( 'View Page', 'food-calorie-calculator' ); ?> ↗
			</a>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="fcc_save_hub_content">
			<?php wp_nonce_field( 'fcc_save_hub_content' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="fcc-hub-intro"><?php esc_html_e( 'Intro Paragraph', 'food-calorie-calculator' ); ?></label>
					</th>
					<td>
						<textarea id="fcc-hub-intro" name="hub_intro" rows="4" class="large-text"><?php echo esc_textarea( $hub_intro ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Shown below the title on /calories/. Leave blank to use the built-in default text.', 'food-calorie-calculator' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Hub Content', 'food-calorie-calculator' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<!-- ======================================================================
	     2. CATEGORY PAGES
	     ====================================================================== -->
	<div class="fcc-fp-section postbox" style="padding:20px 24px;margin-bottom:20px;">
		<h2 style="margin:0 0 14px;font-size:1.1rem;"><?php esc_html_e( 'Category Pages', 'food-calorie-calculator' ); ?></h2>

		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th style="width:22%"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
					<th style="width:30%"><?php esc_html_e( 'URL', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Description', 'food-calorie-calculator' ); ?></th>
					<th style="width:12%"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $categories as $cat ) :
				$cat_url    = home_url( '/calories/' . $cat['slug'] . '/' );
				$has_custom = ! empty( $cat['description'] );
				$row_id     = 'fcc-cat-edit-' . (int) $cat['id'];
			?>
				<tr id="fcc-cat-row-<?php echo (int) $cat['id']; ?>">
					<td><strong><?php echo esc_html( $cat['name'] ); ?></strong></td>
					<td>
						<a href="<?php echo esc_url( $cat_url ); ?>" target="_blank" style="font-size:0.82rem;">
							/calories/<?php echo esc_html( $cat['slug'] ); ?>/ ↗
						</a>
					</td>
					<td>
						<?php if ( $has_custom ) : ?>
							<span style="color:#2d6a4f;">&#10003; <?php esc_html_e( 'Custom', 'food-calorie-calculator' ); ?></span>
							<span style="color:#718096;font-size:0.82rem;margin-left:6px;"><?php echo esc_html( mb_strimwidth( $cat['description'], 0, 80, '…' ) ); ?></span>
						<?php else : ?>
							<span style="color:#a0aec0;"><?php esc_html_e( 'Built-in default', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<button type="button" class="button button-small fcc-fp-cat-toggle" data-target="<?php echo esc_attr( $row_id ); ?>">
							<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
						</button>
					</td>
				</tr>
				<tr id="<?php echo esc_attr( $row_id ); ?>" class="fcc-fp-cat-edit-row" style="display:none;">
					<td colspan="4" style="padding:16px 20px;background:#f8fafc;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="fcc_save_cat_content">
							<input type="hidden" name="cat_id" value="<?php echo (int) $cat['id']; ?>">
							<?php wp_nonce_field( 'fcc_save_cat_content' ); ?>
							<label style="display:block;font-weight:600;margin-bottom:6px;">
								<?php printf( esc_html__( 'Description for %s', 'food-calorie-calculator' ), esc_html( $cat['name'] ) ); ?>
							</label>
							<textarea name="description" rows="4" class="large-text" style="margin-bottom:8px;"><?php echo esc_textarea( $cat['description'] ?? '' ); ?></textarea>
							<p class="description" style="margin-bottom:10px;">
								<?php esc_html_e( 'Leave blank to use the built-in default description for this category.', 'food-calorie-calculator' ); ?>
							</p>
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Description', 'food-calorie-calculator' ); ?></button>
							<button type="button" class="button fcc-fp-cat-cancel" data-target="<?php echo esc_attr( $row_id ); ?>" style="margin-left:6px;">
								<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- ======================================================================
	     3. FOOD PAGES
	     ====================================================================== -->
	<div class="fcc-fp-section postbox" style="padding:20px 24px;">
		<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;">
			<h2 style="margin:0;font-size:1.1rem;"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></h2>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display:flex;gap:6px;">
				<input type="hidden" name="page" value="fcc-food-pages">
				<input type="search" name="fps" value="<?php echo esc_attr( $search ); ?>"
					placeholder="<?php esc_attr_e( 'Search food pages…', 'food-calorie-calculator' ); ?>"
					class="regular-text">
				<button type="submit" class="button"><?php esc_html_e( 'Search', 'food-calorie-calculator' ); ?></button>
				<?php if ( $search ) : ?>
					<a href="<?php echo esc_url( $page_url ); ?>" class="button"><?php esc_html_e( 'Clear', 'food-calorie-calculator' ); ?></a>
				<?php endif; ?>
			</form>
		</div>

		<?php if ( $foods ) : ?>
		<table class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th style="width:28%"><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
					<th style="width:18%"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Page URL', 'food-calorie-calculator' ); ?></th>
					<th style="width:10%"><?php esc_html_e( 'Content', 'food-calorie-calculator' ); ?></th>
					<th style="width:14%"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $foods as $food ) :
				$cid      = (int) $food['category_id'];
				$cat_slug = $cat_slug_map[ $cid ] ?? 'uncategorised';
				$cat_name = $cat_map[ $cid ] ?? '—';
				$food_url = home_url( '/calories/' . $cat_slug . '/' . $food['slug'] . '/' );
				$has_content = ! empty( $food['page_content'] );
				$edit_url = admin_url( 'admin.php?page=fcc-foods&action=edit&food_id=' . (int) $food['id'] );
			?>
				<tr>
					<td><strong><?php echo esc_html( $food['name'] ); ?></strong></td>
					<td><?php echo esc_html( $cat_name ); ?></td>
					<td style="font-size:0.82rem;">
						<a href="<?php echo esc_url( $food_url ); ?>" target="_blank">
							/calories/<?php echo esc_html( $cat_slug . '/' . $food['slug'] ); ?>/ ↗
						</a>
					</td>
					<td>
						<?php if ( $has_content ) : ?>
							<span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:10px;font-size:0.78rem;font-weight:600;">
								<?php esc_html_e( 'Custom', 'food-calorie-calculator' ); ?>
							</span>
						<?php else : ?>
							<span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:10px;font-size:0.78rem;">
								<?php esc_html_e( 'Auto', 'food-calorie-calculator' ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td style="white-space:nowrap;">
						<a href="<?php echo esc_url( $food_url ); ?>" target="_blank" class="button button-small">
							<?php esc_html_e( 'View', 'food-calorie-calculator' ); ?>
						</a>
						<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary button-small" style="margin-left:4px;">
							<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
		<div style="margin-top:14px;display:flex;align-items:center;gap:6px;">
			<?php if ( $paged > 1 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'fpp' => $paged - 1, 'fps' => $search ], $page_url ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Prev', 'food-calorie-calculator' ); ?></a>
			<?php endif; ?>
			<span style="color:#718096;font-size:0.9rem;">
				<?php printf( esc_html__( 'Page %1$d of %2$d (%3$s foods)', 'food-calorie-calculator' ), $paged, $total_pages, number_format( $total_foods ) ); ?>
			</span>
			<?php if ( $paged < $total_pages ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'fpp' => $paged + 1, 'fps' => $search ], $page_url ) ); ?>" class="button"><?php esc_html_e( 'Next', 'food-calorie-calculator' ); ?> &raquo;</a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No food pages found.', 'food-calorie-calculator' ); ?></p>
		<?php endif; ?>
	</div>

</div><!-- .wrap -->

<script>
( function () {
	document.querySelectorAll( '.fcc-fp-cat-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var row = document.getElementById( btn.dataset.target );
			if ( row ) {
				var isOpen = row.style.display !== 'none';
				row.style.display = isOpen ? 'none' : '';
				btn.textContent   = isOpen ? '<?php echo esc_js( __( 'Edit', 'food-calorie-calculator' ) ); ?>' : '<?php echo esc_js( __( 'Close', 'food-calorie-calculator' ) ); ?>';
			}
		} );
	} );
	document.querySelectorAll( '.fcc-fp-cat-cancel' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var row = document.getElementById( btn.dataset.target );
			if ( row ) {
				row.style.display = 'none';
				var toggle = document.querySelector( '[data-target="' + btn.dataset.target + '"].fcc-fp-cat-toggle' );
				if ( toggle ) toggle.textContent = '<?php echo esc_js( __( 'Edit', 'food-calorie-calculator' ) ); ?>';
			}
		} );
	} );
} )();
</script>
