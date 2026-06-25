<?php
/**
 * Admin: Foods table inner content — used by the list page and the AJAX paginator.
 *
 * Required variables (set by caller):
 *   int    $paged, $total, $total_pages, $per_page
 *   array  $foods, $cat_map
 *   string $orderby, $order, $search, $list_url
 *   int    $cat_filter
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'fcc_highlight_search' ) ) :
	function fcc_highlight_search( string $text, string $search ): string {
		if ( '' === $search ) { return esc_html( $text ); }
		return preg_replace( '/(' . preg_quote( $search, '/' ) . ')/i', '<mark>$1</mark>', esc_html( $text ) );
	}
endif;

if ( ! function_exists( 'fcc_completeness' ) ) :
	function fcc_completeness( array $f ): string {
		$core = [ 'energy_kcal', 'protein_g', 'carbohydrate_g', 'fat_g', 'fibre_g', 'salt_g' ];
		$missing = [];
		foreach ( $core as $k ) { if ( null === $f[ $k ] || '' === $f[ $k ] ) { $missing[] = $k; } }
		if ( ! $missing ) {
			return '<span class="fcc-foods-comp fcc-foods-comp--full" title="' . esc_attr__( 'All core nutrients filled', 'food-calorie-calculator' ) . '"></span>';
		}
		$lbl = implode( ', ', array_map( function( $k ) { return str_replace( '_', ' ', str_replace( '_g', '', $k ) ); }, $missing ) );
		return '<span class="fcc-foods-comp fcc-foods-comp--incomplete" title="' . esc_attr( 'Missing: ' . $lbl ) . '"></span>';
	}
endif;

if ( ! function_exists( 'fcc_sort_url' ) ) :
	function fcc_sort_url( string $col, string $current_col, string $current_order ): string {
		$new_order = ( $col === $current_col && 'ASC' === $current_order ) ? 'desc' : 'asc';
		return add_query_arg( [ 'orderby' => $col, 'order' => $new_order ], admin_url( 'admin.php?page=fcc-foods' ) );
	}
endif;

if ( ! function_exists( 'fcc_build_pagination' ) ) :
	function fcc_build_pagination( int $paged, int $total_pages, string $base_url ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		// Collect page numbers to display (gaps become null = ellipsis).
		$show = [];
		for ( $i = 1; $i <= $total_pages; $i++ ) {
			if ( 1 === $i || $i === $total_pages || abs( $i - $paged ) <= 2 ) {
				$show[] = $i;
			}
		}
		$pages = [];
		$prev  = null;
		foreach ( $show as $p ) {
			if ( null !== $prev && $p - $prev > 1 ) {
				$pages[] = null;
			}
			$pages[] = $p;
			$prev    = $p;
		}

		$out = '<div class="fcc-foods-pagination">';

		// Prev arrow.
		if ( $paged > 1 ) {
			$out .= sprintf(
				'<a href="%s" class="fcc-foods-page-btn fcc-foods-page-btn--nav" data-page="%d" aria-label="Previous">&#8249;</a>',
				esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) ),
				$paged - 1
			);
		} else {
			$out .= '<span class="fcc-foods-page-btn fcc-foods-page-btn--nav fcc-foods-page-btn--disabled" aria-disabled="true">&#8249;</span>';
		}

		foreach ( $pages as $p ) {
			if ( null === $p ) {
				$out .= '<span class="fcc-foods-page-btn fcc-foods-page-btn--ellipsis" aria-hidden="true">&#8230;</span>';
			} else {
				$out .= sprintf(
					'<a href="%s" class="fcc-foods-page-btn%s" data-page="%d">%d</a>',
					esc_url( add_query_arg( 'paged', $p, $base_url ) ),
					$p === $paged ? ' fcc-foods-page-btn--active' : '',
					$p,
					$p
				);
			}
		}

		// Next arrow.
		if ( $paged < $total_pages ) {
			$out .= sprintf(
				'<a href="%s" class="fcc-foods-page-btn fcc-foods-page-btn--nav" data-page="%d" aria-label="Next">&#8250;</a>',
				esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) ),
				$paged + 1
			);
		} else {
			$out .= '<span class="fcc-foods-page-btn fcc-foods-page-btn--nav fcc-foods-page-btn--disabled" aria-disabled="true">&#8250;</span>';
		}

		$out .= '</div>';
		return $out;
	}
endif;
?>

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
	<?php echo fcc_build_pagination( $paged, $total_pages, $list_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
					<th class="fcc-foods-th fcc-foods-th--serial">#</th>
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
					<th class="fcc-foods-th fcc-foods-th--active"><?php esc_html_e( 'Visible', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-foods-th">
						<a href="<?php echo esc_url( fcc_sort_url( 'category_name', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'category_name' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'category_name' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--num">
						<a href="<?php echo esc_url( fcc_sort_url( 'energy_kcal', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'energy_kcal' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'kcal', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'energy_kcal' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--num">
						<a href="<?php echo esc_url( fcc_sort_url( 'protein_g', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'protein_g' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'Protein', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'protein_g' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--num">
						<a href="<?php echo esc_url( fcc_sort_url( 'carbohydrate_g', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'carbohydrate_g' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'Carbs', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'carbohydrate_g' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--num">
						<a href="<?php echo esc_url( fcc_sort_url( 'fat_g', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'fat_g' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'Fat', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'fat_g' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--badge">
						<a href="<?php echo esc_url( fcc_sort_url( 'omega3_total_mg', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'omega3_total_mg' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							&#937;-3
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'omega3_total_mg' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--badge">
						<a href="<?php echo esc_url( fcc_sort_url( 'caffeine_mg', $orderby, $order ) ); ?>"
							class="fcc-foods-sort <?php echo 'caffeine_mg' === $orderby ? 'fcc-foods-sort--active' : ''; ?>">
							<?php esc_html_e( 'Caff.', 'food-calorie-calculator' ); ?>
							<span class="fcc-foods-sort__arrow" aria-hidden="true">
								<?php echo 'caffeine_mg' === $orderby ? ( 'ASC' === $order ? '↑' : '↓' ) : '↕'; ?>
							</span>
						</a>
					</th>
					<th class="fcc-foods-th fcc-foods-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $foods ) ) : ?>
					<tr>
						<td colspan="12" class="fcc-foods-empty-row">
							<div class="fcc-foods-empty">
								<span class="fcc-foods-empty__icon" aria-hidden="true">&#128269;</span>
								<p><?php esc_html_e( 'No foods found. Try a different search or filter.', 'food-calorie-calculator' ); ?></p>
							</div>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $foods as $_idx => $food ) :
						$edit_url   = esc_url( add_query_arg( [ 'action' => 'edit', 'food_id' => $food['id'] ], $list_url ) );
						$delete_url = esc_url( wp_nonce_url(
							add_query_arg( [ 'action' => 'fcc_delete_food', 'food_id' => $food['id'] ], admin_url( 'admin-post.php' ) ),
							'fcc_delete_food_' . $food['id']
						) );
						$dup_url = esc_url( wp_nonce_url(
							add_query_arg( [ 'action' => 'fcc_duplicate_food', 'food_id' => $food['id'] ], admin_url( 'admin-post.php' ) ),
							'fcc_duplicate_food_' . $food['id']
						) );
					?>
						<tr class="fcc-foods-row"
							data-id="<?php echo absint( $food['id'] ); ?>"
							data-name="<?php echo esc_attr( $food['name'] ); ?>"
							data-cat="<?php echo absint( $food['category_id'] ); ?>"
							data-kcal="<?php echo esc_attr( $food['energy_kcal'] ); ?>"
							data-protein="<?php echo esc_attr( $food['protein_g'] ); ?>"
							data-carbs="<?php echo esc_attr( $food['carbohydrate_g'] ); ?>"
							data-fat="<?php echo esc_attr( $food['fat_g'] ); ?>">
							<td class="fcc-foods-td fcc-foods-td--serial" title="ID: <?php echo absint( $food['id'] ); ?>">
								<?php echo ( ( $paged - 1 ) * $per_page ) + $_idx + 1; ?>
							</td>
							<td class="fcc-foods-td fcc-foods-td--check">
								<input type="checkbox" name="food_ids[]" value="<?php echo absint( $food['id'] ); ?>"
									aria-label="<?php echo esc_attr( $food['name'] ); ?>">
							</td>
							<td class="fcc-foods-td fcc-foods-td--name">
								<?php echo fcc_completeness( $food ); ?>
								<a href="<?php echo $edit_url; ?>" class="fcc-foods-name-link">
									<?php echo fcc_highlight_search( $food['name'], $search ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</a>
								<?php if ( ! empty( $food['is_sponsored'] ) ) : ?>
									<span class="fcc-foods-sponsored-pill"><?php esc_html_e( 'Sponsored', 'food-calorie-calculator' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="fcc-foods-td fcc-foods-td--active">
								<button type="button"
									class="fcc-foods-toggle-active <?php echo ! empty( $food['is_active'] ) ? 'fcc-foods-toggle-active--on' : 'fcc-foods-toggle-active--off'; ?>"
									data-id="<?php echo absint( $food['id'] ); ?>"
									title="<?php echo ! empty( $food['is_active'] ) ? esc_attr__( 'Visible — click to hide', 'food-calorie-calculator' ) : esc_attr__( 'Hidden — click to show', 'food-calorie-calculator' ); ?>">
									<span class="fcc-foods-toggle-active__track">
										<span class="fcc-foods-toggle-active__thumb"></span>
									</span>
								</button>
							</td>
							<td class="fcc-foods-td">
								<?php if ( isset( $cat_map[ (int) $food['category_id'] ] ) ) : ?>
									<span class="fcc-foods-cat-badge"><?php echo esc_html( $cat_map[ (int) $food['category_id'] ] ); ?></span>
								<?php else : ?>
									<span class="fcc-foods-null">&#8212;</span>
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
										title="<?php echo esc_attr( number_format( (float) $food['omega3_total_mg'], 0 ) . ' mg' ); ?>">&#10003;</span>
								<?php else : ?>
									<span class="fcc-foods-null">&#8212;</span>
								<?php endif; ?>
							</td>
							<td class="fcc-foods-td fcc-foods-td--badge">
								<?php if ( null !== $food['caffeine_mg'] ) : ?>
									<span class="fcc-foods-dot fcc-foods-dot--amber"
										title="<?php echo esc_attr( number_format( (float) $food['caffeine_mg'], 0 ) . ' mg' ); ?>">&#10003;</span>
								<?php else : ?>
									<span class="fcc-foods-null">&#8212;</span>
								<?php endif; ?>
							</td>
							<td class="fcc-foods-td fcc-foods-td--actions">
								<div class="fcc-foods-action-group">
									<button type="button" class="fcc-foods-action-btn fcc-foods-action-btn--qe fcc-foods-qe-trigger"
										title="<?php esc_attr_e( 'Quick Edit', 'food-calorie-calculator' ); ?>">
										<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
										<?php esc_html_e( 'Quick', 'food-calorie-calculator' ); ?>
									</button>
									<a href="<?php echo $edit_url; ?>" class="fcc-foods-action-btn fcc-foods-action-btn--edit">
										<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
										<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
									</a>
									<a href="<?php echo $dup_url; ?>" class="fcc-foods-action-btn fcc-foods-action-btn--dup"
										title="<?php esc_attr_e( 'Duplicate', 'food-calorie-calculator' ); ?>">
										<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
									</a>
									<a href="<?php echo $delete_url; ?>"
										class="fcc-foods-action-btn fcc-foods-action-btn--delete fcc-confirm-delete"
										data-confirm="<?php esc_attr_e( 'Delete this food permanently?', 'food-calorie-calculator' ); ?>">
										<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
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

<?php if ( $total_pages > 1 ) : ?>
<div class="fcc-foods-tablenav fcc-foods-tablenav--bottom">
	<span class="fcc-foods-count">
		<?php printf(
			/* translators: %d = total */
			esc_html__( '%d foods total', 'food-calorie-calculator' ),
			$total
		); ?>
	</span>
	<?php echo fcc_build_pagination( $paged, $total_pages, $list_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
<?php endif; ?>
