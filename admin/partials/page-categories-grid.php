<?php
/**
 * Categories grid inner content — used by the main categories page and AJAX handlers.
 *
 * Required variables (set by caller):
 *   array    $categories
 *   array    $food_counts    keyed by category_id
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
</div>

<?php if ( empty( $categories ) ) : ?>
	<div class="fcc-cats-empty">
		<div class="fcc-cats-empty__icon">&#127991;</div>
		<p class="fcc-cats-empty__text">
			<?php esc_html_e( 'No categories yet. Use the form above to add your first one!', 'food-calorie-calculator' ); ?>
		</p>
	</div>
<?php else : ?>
	<div class="fcc-cats-grid">
		<?php foreach ( $categories as $i => $cat ) :
			$color      = $palette[ $i % count( $palette ) ];
			$icon       = $get_icon( $cat['name'] );
			$food_count = $food_counts[ (int) $cat['id'] ] ?? 0;
			$is_editing = ( $edit_id > 0 && $edit_id === (int) $cat['id'] );
		?>
			<div class="fcc-cat-card <?php echo $is_editing ? 'fcc-cat-card--active' : ''; ?>"
				style="--cat-color:<?php echo esc_attr( $color ); ?>"
				data-cat-id="<?php echo absint( $cat['id'] ); ?>"
				data-cat-name="<?php echo esc_attr( $cat['name'] ); ?>"
				data-cat-slug="<?php echo esc_attr( $cat['slug'] ); ?>"
				data-cat-desc="<?php echo esc_attr( $cat['description'] ?? '' ); ?>"
				data-cat-order="<?php echo absint( $cat['display_order'] ); ?>">
				<div class="fcc-cat-card__accent"></div>
				<div class="fcc-cat-card__body">
					<div class="fcc-cat-card__top">
						<span class="fcc-cat-card__icon" role="img"
							aria-label="<?php echo esc_attr( $cat['name'] ); ?>"><?php
							echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- emoji from hardcoded array
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
				</div>
				<div class="fcc-cat-card__footer">
					<button type="button" class="fcc-cat-card__btn fcc-cat-card__btn--edit fcc-cat-edit-btn">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
						<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
					</button>
					<button type="button"
						class="fcc-cat-card__btn fcc-cat-card__btn--delete fcc-cat-delete-btn"
						data-cat-id="<?php echo absint( $cat['id'] ); ?>"
						data-confirm="<?php esc_attr_e( 'Delete this category? Foods in this category will become uncategorised.', 'food-calorie-calculator' ); ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
						<?php esc_html_e( 'Delete', 'food-calorie-calculator' ); ?>
					</button>
				</div>
			</div><!-- .fcc-cat-card -->
		<?php endforeach; ?>
	</div><!-- .fcc-cats-grid -->
<?php endif; ?>
