<?php
/**
 * Categories grid inner content — used by the main categories page and AJAX handlers.
 *
 * Required variables (set by caller):
 *   array    $categories
 *   array    $food_counts    keyed by category_id
 *   array    $cat_stats      keyed by category_id (food_count, total_searches, complete, incomplete)
 *   array    $top_foods      keyed by category_id (name, search_count)
 *   array    $palette        colour strings
 *   callable $get_icon       fn( string $name ): string
 *   int      $edit_id        currently-being-edited ID (0 = none)
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="fcc-cats-section-hd">
	<h2 class="fcc-cats-section-title"><?php esc_html_e( 'All Categories', 'food-calorie-calculator' ); ?></h2>
	<?php if ( ! empty( $categories ) ) : ?>
		<span class="fcc-cats-list-badge"><?php echo count( $categories ); ?></span>
	<?php endif; ?>

	<?php if ( ! empty( $categories ) ) : ?>
		<!-- Search filter -->
		<div class="fcc-cats-filter">
			<input type="text" id="fcc-cat-filter" class="fcc-cats-filter__input"
				placeholder="<?php esc_attr_e( 'Filter categories…', 'food-calorie-calculator' ); ?>">
		</div>

		<!-- Sort toggle -->
		<div class="fcc-cats-sort-btns">
			<span class="fcc-cats-sort-label"><?php esc_html_e( 'Sort:', 'food-calorie-calculator' ); ?></span>
			<button type="button" class="fcc-cats-sort-btn fcc-cats-sort-btn--active" data-sort="order"><?php esc_html_e( 'Order', 'food-calorie-calculator' ); ?></button>
			<button type="button" class="fcc-cats-sort-btn" data-sort="name"><?php esc_html_e( 'Name', 'food-calorie-calculator' ); ?></button>
			<button type="button" class="fcc-cats-sort-btn" data-sort="foods"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></button>
			<button type="button" class="fcc-cats-sort-btn" data-sort="searches"><?php esc_html_e( 'Searches', 'food-calorie-calculator' ); ?></button>
		</div>
	<?php endif; ?>
</div>

<?php if ( empty( $categories ) ) : ?>
	<div class="fcc-cats-empty">
		<div class="fcc-cats-empty__icon">&#127991;</div>
		<p class="fcc-cats-empty__text">
			<?php esc_html_e( 'No categories yet. Use the form above to add your first one!', 'food-calorie-calculator' ); ?>
		</p>
	</div>
<?php else : ?>
	<div class="fcc-cats-grid" id="fcc-cats-grid">
		<?php foreach ( $categories as $i => $cat ) :
			$cid        = (int) $cat['id'];
			$color      = $palette[ $i % count( $palette ) ];
			$icon       = $get_icon( $cat['name'] );
			$food_count = $food_counts[ $cid ] ?? 0;
			$stats      = $cat_stats[ $cid ] ?? [ 'food_count' => 0, 'total_searches' => 0, 'complete' => 0, 'incomplete' => 0 ];
			$top        = $top_foods[ $cid ] ?? null;
			$pct        = $food_count > 0 ? round( $stats['complete'] / $food_count * 100 ) : 0;
			$is_editing = ( $edit_id > 0 && $edit_id === $cid );
		?>
			<div class="fcc-cat-card <?php echo $is_editing ? 'fcc-cat-card--active' : ''; ?>"
				style="--cat-color:<?php echo esc_attr( $color ); ?>"
				data-cat-id="<?php echo $cid; ?>"
				data-cat-name="<?php echo esc_attr( $cat['name'] ); ?>"
				data-cat-slug="<?php echo esc_attr( $cat['slug'] ); ?>"
				data-cat-desc="<?php echo esc_attr( $cat['description'] ?? '' ); ?>"
				data-cat-order="<?php echo absint( $cat['display_order'] ); ?>"
				data-foods="<?php echo $food_count; ?>"
				data-searches="<?php echo $stats['total_searches']; ?>">
				<div class="fcc-cat-card__accent"></div>
				<div class="fcc-cat-card__body">
					<div class="fcc-cat-card__top">
						<span class="fcc-cat-card__icon" role="img"
							aria-label="<?php echo esc_attr( $cat['name'] ); ?>"><?php
							echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?></span>
						<span class="fcc-cat-card__count-badge">
							<?php echo esc_html( $food_count . ' ' . ( 1 === $food_count
								? __( 'food', 'food-calorie-calculator' )
								: __( 'foods', 'food-calorie-calculator' ) ) ); ?>
						</span>
					</div>
					<h3 class="fcc-cat-card__name"><?php echo esc_html( $cat['name'] ); ?></h3>
					<span class="fcc-cat-card__slug">#<?php echo esc_html( $cat['slug'] ); ?></span>
					<?php if ( ! empty( $cat['description'] ) ) : ?>
						<p class="fcc-cat-card__desc"><?php echo esc_html( $cat['description'] ); ?></p>
					<?php endif; ?>

					<!-- Stats row -->
					<?php if ( $food_count > 0 ) : ?>
						<div class="fcc-cat-card__stats">
							<span class="fcc-cat-card__stat" title="<?php esc_attr_e( 'Total searches', 'food-calorie-calculator' ); ?>">
								<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
								<?php echo number_format_i18n( $stats['total_searches'] ); ?>
							</span>
							<span class="fcc-cat-card__stat fcc-cat-card__stat--ok" title="<?php esc_attr_e( 'Complete foods', 'food-calorie-calculator' ); ?>">
								✓ <?php echo $stats['complete']; ?>
							</span>
							<?php if ( $stats['incomplete'] > 0 ) : ?>
								<span class="fcc-cat-card__stat fcc-cat-card__stat--warn" title="<?php esc_attr_e( 'Incomplete foods', 'food-calorie-calculator' ); ?>">
									✗ <?php echo $stats['incomplete']; ?>
								</span>
							<?php endif; ?>
						</div>

						<!-- Completeness bar -->
						<div class="fcc-cat-card__bar" title="<?php echo esc_attr( $pct . '% complete' ); ?>">
							<div class="fcc-cat-card__bar-fill" style="width:<?php echo $pct; ?>%"></div>
						</div>

						<?php if ( $top ) : ?>
							<div class="fcc-cat-card__top-food" title="<?php esc_attr_e( 'Most searched food', 'food-calorie-calculator' ); ?>">
								🔥 <?php echo esc_html( $top['name'] ); ?>
								<span class="fcc-cat-card__top-food-count">(<?php echo number_format_i18n( $top['search_count'] ); ?>)</span>
							</div>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<div class="fcc-cat-card__footer">
					<button type="button" class="fcc-cat-card__btn fcc-cat-card__btn--edit fcc-cat-edit-btn">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
						<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
					</button>
					<button type="button" class="fcc-cat-card__btn fcc-cat-card__btn--merge fcc-cat-merge-btn"
						data-cat-id="<?php echo $cid; ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6L21 6"/><path d="M8 12L21 12"/><path d="M8 18L21 18"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
						<?php esc_html_e( 'Merge', 'food-calorie-calculator' ); ?>
					</button>
					<button type="button"
						class="fcc-cat-card__btn fcc-cat-card__btn--delete fcc-cat-delete-btn"
						data-cat-id="<?php echo $cid; ?>"
						data-confirm="<?php esc_attr_e( 'Delete this category? Foods in this category will become uncategorised.', 'food-calorie-calculator' ); ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
