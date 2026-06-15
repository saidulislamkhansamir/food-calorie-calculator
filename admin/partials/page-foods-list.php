<?php
/**
 * Admin: Foods list.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$action  = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
$food_id = isset( $_GET['food_id'] ) ? absint( $_GET['food_id'] ) : 0;

if ( in_array( $action, [ 'add', 'edit' ], true ) ) {
	$food       = ( 'edit' === $action && $food_id > 0 ) ? FCC\Database::get_food( $food_id ) : null;
	$categories = FCC\Database::get_all_categories();
	include FCC_PLUGIN_DIR . 'admin/partials/page-foods-edit.php';
	return;
}

$search     = isset( $_GET['s'] )           ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$cat_filter = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] )                  : 0;
$orderby    = isset( $_GET['orderby'] )     ? sanitize_key( $_GET['orderby'] )                : 'name';
$order      = isset( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ? 'DESC'     : 'ASC';
$paged      = isset( $_GET['paged'] )       ? max( 1, absint( $_GET['paged'] ) )              : 1;
$per_page   = 20;

$result = FCC\Database::get_foods( [
	'search'      => $search,
	'category_id' => $cat_filter,
	'orderby'     => $orderby,
	'order'       => $order,
	'per_page'    => $per_page,
	'page'        => $paged,
] );

$total       = $result['total'];
$foods       = $result['rows'];
$total_pages = (int) ceil( $total / $per_page );

$categories = FCC\Database::get_all_categories();
$cat_map    = [];
foreach ( $categories as $cat ) {
	$cat_map[ (int) $cat['id'] ] = esc_html( $cat['name'] );
}

global $wpdb;
$total_all = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}fcc_foods" );

$list_url = admin_url( 'admin.php?page=fcc-foods' );

if ( ! function_exists( 'fcc_sort_url' ) ) {
	function fcc_sort_url( string $col, string $current_col, string $current_order ): string {
		$new_order = ( $col === $current_col && 'ASC' === $current_order ) ? 'desc' : 'asc';
		return add_query_arg( [ 'orderby' => $col, 'order' => $new_order ], admin_url( 'admin.php?page=fcc-foods' ) );
	}
}
?>
<div class="wrap fcc-admin-wrap fcc-foods-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></h1>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-foods-hero">
		<div class="fcc-foods-hero__inner">

			<div class="fcc-foods-hero__content">
				<div class="fcc-foods-hero__icon" aria-hidden="true">🥗</div>
				<div>
					<div class="fcc-foods-hero__title"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-foods-hero__sub">
						<?php esc_html_e( 'Manage your food database. Search, filter, and edit individual nutrition records.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>

			<div class="fcc-foods-hero__stats">
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo $total_all; ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Total Foods', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo count( $categories ); ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat fcc-foods-hero-stat--cta">
					<a href="<?php echo esc_url( add_query_arg( 'action', 'add', $list_url ) ); ?>"
						class="fcc-foods-hero-cta">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						<?php esc_html_e( 'Add New Food', 'food-calorie-calculator' ); ?>
					</a>
				</div>
			</div>

		</div>
	</div><!-- .fcc-foods-hero -->

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ======================================================================
	     Toolbar: search + filter + bulk actions
	     ====================================================================== -->
	<div class="fcc-foods-toolbar">

		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>"
			class="fcc-foods-toolbar__search" id="fcc-search-form">
			<input type="hidden" name="page" value="fcc-foods">

			<div class="fcc-foods-searchbox">
				<svg class="fcc-foods-searchbox__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>"
					placeholder="<?php esc_attr_e( 'Search foods…', 'food-calorie-calculator' ); ?>"
					class="fcc-foods-searchbox__input">
			</div>

			<select name="category_id" class="fcc-foods-catselect">
				<option value="0"><?php esc_html_e( 'All Categories', 'food-calorie-calculator' ); ?></option>
				<?php foreach ( $categories as $cat ) : ?>
					<option value="<?php echo absint( $cat['id'] ); ?>"
						<?php selected( $cat_filter, $cat['id'] ); ?>>
						<?php echo esc_html( $cat['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<button type="submit" class="fcc-foods-filter-btn">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
				<?php esc_html_e( 'Filter', 'food-calorie-calculator' ); ?>
			</button>

		</form>

		<div class="fcc-foods-toolbar__bulk">
			<select name="bulk_action" form="fcc-bulk-form" id="fcc-bulk-action" class="fcc-foods-bulk-select">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'food-calorie-calculator' ); ?></option>
				<option value="delete"><?php esc_html_e( 'Delete', 'food-calorie-calculator' ); ?></option>
			</select>
			<button type="submit" form="fcc-bulk-form" class="fcc-foods-bulk-apply">
				<?php esc_html_e( 'Apply', 'food-calorie-calculator' ); ?>
			</button>
		</div>

	</div><!-- .fcc-foods-toolbar -->

	<!-- Result count + pagination (top) -->
	<?php if ( $total > 0 ) : ?>
	<div class="fcc-foods-tablenav fcc-foods-tablenav--top">
		<span class="fcc-foods-count">
			<?php printf(
				/* translators: %1$d = from, %2$d = to, %3$d = total */
				esc_html__( 'Showing %1$d–%2$d of %3$d foods', 'food-calorie-calculator' ),
				( ( $paged - 1 ) * $per_page ) + 1,
				min( $paged * $per_page, $total ),
				$total
			); ?>
		</span>
		<?php if ( $total_pages > 1 ) : ?>
		<div class="fcc-foods-pagination">
			<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $list_url ) ); ?>"
					class="fcc-foods-page-btn <?php echo $p === $paged ? 'fcc-foods-page-btn--active' : ''; ?>">
					<?php echo esc_html( $p ); ?>
				</a>
			<?php endfor; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- ======================================================================
	     Bulk form wrapping the table
	     ====================================================================== -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		id="fcc-bulk-form">
		<input type="hidden" name="action" value="fcc_bulk_foods">
		<?php wp_nonce_field( 'fcc_bulk_foods' ); ?>

		<div class="fcc-foods-table-wrap">
			<table class="fcc-foods-table">
				<thead>
					<tr>
						<th class="fcc-foods-th fcc-foods-th--check">
							<input type="checkbox" id="fcc-select-all"
								aria-label="<?php esc_attr_e( 'Select all', 'food-calorie-calculator' ); ?>">
						</th>
						<th class="fcc-foods-th fcc-foods-th--name">
							<a href="<?php echo esc_url( fcc_sort_url( 'name', $orderby, $order ) ); ?>"
								class="fcc-foods-sort <?php echo 'name' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
								<?php esc_html_e( 'Name', 'food-calorie-calculator' ); ?>
								<span class="fcc-foods-sort__arrow" aria-hidden="true">
									<?php echo 'name' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
								</span>
							</a>
						</th>
						<th class="fcc-foods-th"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-foods-th fcc-foods-th--num">
							<a href="<?php echo esc_url( fcc_sort_url( 'energy_kcal', $orderby, $order ) ); ?>"
								class="fcc-foods-sort <?php echo 'energy_kcal' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
								<?php esc_html_e( 'kcal', 'food-calorie-calculator' ); ?>
								<span class="fcc-foods-sort__arrow" aria-hidden="true">
									<?php echo 'energy_kcal' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
								</span>
							</a>
						</th>
						<th class="fcc-foods-th fcc-foods-th--num"><?php esc_html_e( 'Protein', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-foods-th fcc-foods-th--num"><?php esc_html_e( 'Carbs', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-foods-th fcc-foods-th--num"><?php esc_html_e( 'Fat', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-foods-th fcc-foods-th--badge">Ω-3</th>
						<th class="fcc-foods-th fcc-foods-th--badge"><?php esc_html_e( 'Caff.', 'food-calorie-calculator' ); ?></th>
						<th class="fcc-foods-th fcc-foods-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $foods ) ) : ?>
						<tr>
							<td colspan="10" class="fcc-foods-empty-row">
								<div class="fcc-foods-empty">
									<span class="fcc-foods-empty__icon" aria-hidden="true">🔍</span>
									<p><?php esc_html_e( 'No foods found. Try a different search or filter.', 'food-calorie-calculator' ); ?></p>
								</div>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $foods as $food ) :
							$edit_url   = esc_url( add_query_arg( [ 'action' => 'edit', 'food_id' => $food['id'] ], $list_url ) );
							$delete_url = esc_url( wp_nonce_url(
								add_query_arg( [ 'action' => 'fcc_delete_food', 'food_id' => $food['id'] ], admin_url( 'admin-post.php' ) ),
								'fcc_delete_food_' . $food['id']
							) );
						?>
							<tr class="fcc-foods-row">
								<td class="fcc-foods-td fcc-foods-td--check">
									<input type="checkbox" name="food_ids[]" value="<?php echo absint( $food['id'] ); ?>"
										aria-label="<?php echo esc_attr( $food['name'] ); ?>">
								</td>
								<td class="fcc-foods-td fcc-foods-td--name">
									<a href="<?php echo $edit_url; ?>" class="fcc-foods-name-link">
										<?php echo esc_html( $food['name'] ); ?>
									</a>
								</td>
								<td class="fcc-foods-td">
									<?php if ( isset( $cat_map[ (int) $food['category_id'] ] ) ) : ?>
										<span class="fcc-foods-cat-badge"><?php echo $cat_map[ (int) $food['category_id'] ]; ?></span>
									<?php else : ?>
										<span class="fcc-foods-null">—</span>
									<?php endif; ?>
								</td>
								<td class="fcc-foods-td fcc-foods-td--num">
									<span class="fcc-foods-kcal"><?php echo esc_html( number_format( (float) $food['energy_kcal'], 0 ) ); ?></span>
								</td>
								<td class="fcc-foods-td fcc-foods-td--num fcc-foods-td--protein">
									<?php echo esc_html( number_format( (float) $food['protein_g'], 1 ) ); ?>g
								</td>
								<td class="fcc-foods-td fcc-foods-td--num fcc-foods-td--carbs">
									<?php echo esc_html( number_format( (float) $food['carbohydrate_g'], 1 ) ); ?>g
								</td>
								<td class="fcc-foods-td fcc-foods-td--num fcc-foods-td--fat">
									<?php echo esc_html( number_format( (float) $food['fat_g'], 1 ) ); ?>g
								</td>
								<td class="fcc-foods-td fcc-foods-td--badge">
									<?php if ( null !== $food['omega3_total_mg'] ) : ?>
										<span class="fcc-foods-dot fcc-foods-dot--green"
											title="<?php echo esc_attr( number_format( (float) $food['omega3_total_mg'], 0 ) . ' mg' ); ?>">✓</span>
									<?php else : ?>
										<span class="fcc-foods-null">—</span>
									<?php endif; ?>
								</td>
								<td class="fcc-foods-td fcc-foods-td--badge">
									<?php if ( null !== $food['caffeine_mg'] ) : ?>
										<span class="fcc-foods-dot fcc-foods-dot--amber"
											title="<?php echo esc_attr( number_format( (float) $food['caffeine_mg'], 0 ) . ' mg' ); ?>">✓</span>
									<?php else : ?>
										<span class="fcc-foods-null">—</span>
									<?php endif; ?>
								</td>
								<td class="fcc-foods-td fcc-foods-td--actions">
									<div class="fcc-foods-action-group">
										<a href="<?php echo $edit_url; ?>" class="fcc-foods-action-btn fcc-foods-action-btn--edit">
											<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
											<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
										</a>
										<a href="<?php echo $delete_url; ?>"
											class="fcc-foods-action-btn fcc-foods-action-btn--delete fcc-confirm-delete"
											data-confirm="<?php esc_attr_e( 'Delete this food permanently?', 'food-calorie-calculator' ); ?>">
											<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
											<?php esc_html_e( 'Delete', 'food-calorie-calculator' ); ?>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div><!-- .fcc-foods-table-wrap -->

	</form><!-- #fcc-bulk-form -->

	<!-- Bottom pagination -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="fcc-foods-tablenav fcc-foods-tablenav--bottom">
		<span class="fcc-foods-count">
			<?php printf(
				/* translators: %d = total */
				esc_html__( '%d foods total', 'food-calorie-calculator' ),
				$total
			); ?>
		</span>
		<div class="fcc-foods-pagination">
			<?php for ( $p = 1; $p <= $total_pages; $p++ ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $list_url ) ); ?>"
					class="fcc-foods-page-btn <?php echo $p === $paged ? 'fcc-foods-page-btn--active' : ''; ?>">
					<?php echo esc_html( $p ); ?>
				</a>
			<?php endfor; ?>
		</div>
	</div>
	<?php endif; ?>

</div><!-- .wrap -->

<script>
( function () {
	'use strict';

	const selectAll = document.getElementById( 'fcc-select-all' );
	if ( selectAll ) {
		selectAll.addEventListener( 'change', function () {
			document.querySelectorAll( 'input[name="food_ids[]"]' ).forEach( function ( cb ) {
				cb.checked = selectAll.checked;
			} );
		} );
	}
} )();
</script>
