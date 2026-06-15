<?php
/**
 * Admin: Categories manager.
 * Layout: Hero header → horizontal quick-add/edit form → category cards grid.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$categories = FCC\Database::get_all_categories();
$edit_id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$edit_cat   = $edit_id > 0 ? FCC\Database::get_category( $edit_id ) : null;

global $wpdb;

// Food counts per category (one query).
$counts_raw  = $wpdb->get_results(
	"SELECT category_id, COUNT(*) AS cnt FROM {$wpdb->prefix}fcc_foods GROUP BY category_id",
	ARRAY_A
);
$food_counts = [];
foreach ( (array) $counts_raw as $row ) {
	$food_counts[ (int) $row['category_id'] ] = (int) $row['cnt'];
}

// Total food count for hero stat.
$total_foods = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}fcc_foods" );

// Colour palette assigned by position.
$palette = [
	'#3b82f6', '#22c55e', '#f97316', '#8b5cf6',
	'#14b8a6', '#ef4444', '#eab308', '#ec4899',
	'#06b6d4', '#84cc16', '#f59e0b', '#6366f1',
];

// Icon map — matched against lowercase category name.
$icon_map = [
	'fruit'      => '🥦', 'veg'        => '🥦',
	'meat'       => '🥩', 'poultry'    => '🥩',
	'fish'       => '🐟', 'seafood'    => '🐟',
	'dairy'      => '🥛', 'egg'        => '🥚',
	'bread'      => '🍞', 'cereal'     => '🌾', 'grain' => '🌾',
	'nut'        => '🥜', 'seed'       => '🌰',
	'fat'        => '🫙', 'oil'        => '🫙',
	'drink'      => '🥤', 'beverage'   => '🥤',
	'snack'      => '🍫', 'confection' => '🍭',
	'takeaway'   => '🥡', 'ready'      => '🥡',
	'legume'     => '🫘', 'pulse'      => '🫘', 'bean' => '🫘',
	'condiment'  => '🧂', 'sauce'      => '🧂',
];

$get_icon = static function ( string $name ) use ( $icon_map ): string {
	$lower = strtolower( $name );
	foreach ( $icon_map as $key => $icon ) {
		if ( str_contains( $lower, $key ) ) {
			return $icon;
		}
	}
	return '🍽️';
};
?>
<div class="wrap fcc-admin-wrap fcc-categories-page">

	<!-- Screen-reader h1 for accessibility; visual title lives in the hero card -->
	<h1 class="screen-reader-text"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></h1>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-cats-hero <?php echo $edit_cat ? 'fcc-cats-hero--editing' : ''; ?>">
		<div class="fcc-cats-hero__inner">

			<div class="fcc-cats-hero__content">
				<div class="fcc-cats-hero__icon" aria-hidden="true">🏷️</div>
				<div>
					<div class="fcc-cats-hero__title">
						<?php echo $edit_cat
							? esc_html__( 'Categories', 'food-calorie-calculator' )
							: esc_html__( 'Categories', 'food-calorie-calculator' ); ?>
					</div>
					<p class="fcc-cats-hero__sub">
						<?php echo $edit_cat
							/* translators: %s: category name */
							? sprintf( esc_html__( 'Currently editing "%s" — update the form below.', 'food-calorie-calculator' ), esc_html( $edit_cat['name'] ) )
							: esc_html__( 'Organise your foods into categories for better navigation and filtering.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>

			<div class="fcc-cats-hero__stats">
				<div class="fcc-cats-hero-stat">
					<span class="fcc-cats-hero-stat__value"><?php echo count( $categories ); ?></span>
					<span class="fcc-cats-hero-stat__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-cats-hero-stat">
					<span class="fcc-cats-hero-stat__value"><?php echo $total_foods; ?></span>
					<span class="fcc-cats-hero-stat__label"><?php esc_html_e( 'Total Foods', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

		</div>
	</div><!-- .fcc-cats-hero -->

	<!-- ======================================================================
	     Horizontal quick-add / edit form bar
	     ====================================================================== -->
	<div class="fcc-cats-formbar <?php echo $edit_cat ? 'fcc-cats-formbar--edit' : ''; ?>">

		<div class="fcc-cats-formbar__label">
			<?php if ( $edit_cat ) : ?>
				<span class="fcc-cats-formbar__mode fcc-cats-formbar__mode--edit">✏️ <?php esc_html_e( 'Editing', 'food-calorie-calculator' ); ?></span>
				<strong><?php echo esc_html( $edit_cat['name'] ); ?></strong>
			<?php else : ?>
				<span class="fcc-cats-formbar__mode">➕ <?php esc_html_e( 'Add New Category', 'food-calorie-calculator' ); ?></span>
			<?php endif; ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			class="fcc-cats-formbar__row" id="fcc-cat-form">
			<input type="hidden" name="action"      value="fcc_save_category">
			<input type="hidden" name="category_id" value="<?php echo esc_attr( $edit_id ); ?>">
			<?php wp_nonce_field( 'fcc_save_category' ); ?>

			<!-- Name -->
			<div class="fcc-cats-qfield fcc-cats-qfield--name">
				<label class="fcc-cats-qlabel" for="cat_name">
					<?php esc_html_e( 'Name', 'food-calorie-calculator' ); ?>
					<span class="fcc-cats-req" aria-hidden="true">*</span>
				</label>
				<input type="text" id="cat_name" name="name" required
					value="<?php echo $edit_cat ? esc_attr( $edit_cat['name'] ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'e.g. Fruit & Vegetables', 'food-calorie-calculator' ); ?>"
					class="fcc-cats-qinput" autocomplete="off">
			</div>

			<!-- Slug -->
			<div class="fcc-cats-qfield fcc-cats-qfield--slug">
				<label class="fcc-cats-qlabel" for="cat_slug">
					<?php esc_html_e( 'Slug', 'food-calorie-calculator' ); ?>
					<span class="fcc-cats-qlabel-hint"><?php esc_html_e( 'auto', 'food-calorie-calculator' ); ?></span>
				</label>
				<input type="text" id="cat_slug" name="slug"
					value="<?php echo $edit_cat ? esc_attr( $edit_cat['slug'] ) : ''; ?>"
					placeholder="fruit-veg"
					class="fcc-cats-qinput fcc-cats-qinput--slug" autocomplete="off">
			</div>

			<!-- Description (single-line for horizontal layout) -->
			<div class="fcc-cats-qfield fcc-cats-qfield--desc">
				<label class="fcc-cats-qlabel" for="cat_desc">
					<?php esc_html_e( 'Description', 'food-calorie-calculator' ); ?>
					<span class="fcc-cats-qlabel-hint"><?php esc_html_e( 'optional', 'food-calorie-calculator' ); ?></span>
				</label>
				<input type="text" id="cat_desc" name="description"
					value="<?php echo $edit_cat ? esc_attr( $edit_cat['description'] ?? '' ) : ''; ?>"
					placeholder="<?php esc_attr_e( 'A brief description…', 'food-calorie-calculator' ); ?>"
					class="fcc-cats-qinput">
			</div>

			<!-- Display order -->
			<div class="fcc-cats-qfield fcc-cats-qfield--order">
				<label class="fcc-cats-qlabel" for="cat_order">
					<?php esc_html_e( 'Order', 'food-calorie-calculator' ); ?>
				</label>
				<input type="number" id="cat_order" name="display_order" min="0" step="1"
					value="<?php echo $edit_cat ? absint( $edit_cat['display_order'] ) : '0'; ?>"
					class="fcc-cats-qinput fcc-cats-qinput--order">
			</div>

			<!-- Submit + Cancel -->
			<div class="fcc-cats-qfield fcc-cats-qfield--actions">
				<span class="fcc-cats-qlabel fcc-cats-qlabel--ghost" aria-hidden="true">&nbsp;</span>
				<div class="fcc-cats-qactions">
					<button type="submit" class="fcc-cats-qsubmit <?php echo $edit_cat ? 'fcc-cats-qsubmit--update' : ''; ?>">
						<?php echo $edit_cat
							? esc_html__( 'Update', 'food-calorie-calculator' )
							: esc_html__( 'Add Category', 'food-calorie-calculator' ); ?>
					</button>
					<?php if ( $edit_cat ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-categories' ) ); ?>" class="fcc-cats-qcancel">
							<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		</form>
	</div><!-- .fcc-cats-formbar -->

	<!-- ======================================================================
	     All categories grid
	     ====================================================================== -->
	<div class="fcc-cats-section">

		<div class="fcc-cats-section-hd">
			<h2 class="fcc-cats-section-title"><?php esc_html_e( 'All Categories', 'food-calorie-calculator' ); ?></h2>
			<?php if ( ! empty( $categories ) ) : ?>
				<span class="fcc-cats-list-badge"><?php echo count( $categories ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( empty( $categories ) ) : ?>

			<div class="fcc-cats-empty">
				<div class="fcc-cats-empty__icon">🏷️</div>
				<p class="fcc-cats-empty__text">
					<?php esc_html_e( 'No categories yet. Use the form above to add your first one!', 'food-calorie-calculator' ); ?>
				</p>
			</div>

		<?php else : ?>

			<div class="fcc-cats-grid">
				<?php foreach ( $categories as $i => $cat ) :
					$color      = $palette[ $i % count( $palette ) ];
					$icon       = $get_icon( $cat['name'] );
					$food_count = $food_counts[ $cat['id'] ] ?? 0;
					$is_editing = $edit_id === (int) $cat['id'];
					$edit_url   = esc_url( add_query_arg(
						[ 'page' => 'fcc-categories', 'edit' => $cat['id'] ],
						admin_url( 'admin.php' )
					) );
					$delete_url = esc_url( wp_nonce_url(
						add_query_arg(
							[ 'action' => 'fcc_delete_category', 'category_id' => $cat['id'] ],
							admin_url( 'admin-post.php' )
						),
						'fcc_delete_category_' . $cat['id']
					) );
				?>
					<div class="fcc-cat-card <?php echo $is_editing ? 'fcc-cat-card--active' : ''; ?>"
						style="--cat-color:<?php echo esc_attr( $color ); ?>">
						<div class="fcc-cat-card__accent"></div>
						<div class="fcc-cat-card__body">
							<div class="fcc-cat-card__top">
								<span class="fcc-cat-card__icon" role="img"
									aria-label="<?php echo esc_attr( $cat['name'] ); ?>"><?php
									echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- emoji from hardcoded PHP array
								?></span>
								<span class="fcc-cat-card__count-badge">
									<?php
									echo $food_count . ' ' . ( $food_count === 1
										? esc_html__( 'food', 'food-calorie-calculator' )
										: esc_html__( 'foods', 'food-calorie-calculator' ) );
									?>
								</span>
							</div>
							<h3 class="fcc-cat-card__name"><?php echo esc_html( $cat['name'] ); ?></h3>
							<span class="fcc-cat-card__slug">#<?php echo esc_html( $cat['slug'] ); ?></span>
							<?php if ( ! empty( $cat['description'] ) ) : ?>
								<p class="fcc-cat-card__desc"><?php echo esc_html( $cat['description'] ); ?></p>
							<?php endif; ?>
						</div>
						<div class="fcc-cat-card__footer">
							<a href="<?php echo $edit_url; ?>" class="fcc-cat-card__btn fcc-cat-card__btn--edit">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
								<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
							</a>
							<a href="<?php echo $delete_url; ?>"
								class="fcc-cat-card__btn fcc-cat-card__btn--delete fcc-confirm-delete"
								data-confirm="<?php esc_attr_e( 'Delete this category? Foods in this category will become uncategorised.', 'food-calorie-calculator' ); ?>">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
								<?php esc_html_e( 'Delete', 'food-calorie-calculator' ); ?>
							</a>
						</div>
					</div><!-- .fcc-cat-card -->
				<?php endforeach; ?>
			</div><!-- .fcc-cats-grid -->

		<?php endif; ?>

	</div><!-- .fcc-cats-section -->

</div><!-- .wrap -->

<script>
( function () {
	'use strict';

	const nameInput = document.getElementById( 'cat_name' );
	const slugInput = document.getElementById( 'cat_slug' );
	if ( ! nameInput || ! slugInput ) return;

	// Stop auto-generating slug once the user manually edits it.
	let slugManual = slugInput.value.trim() !== '';
	slugInput.addEventListener( 'input', function () { slugManual = true; } );

	nameInput.addEventListener( 'input', function () {
		if ( slugManual ) return;
		slugInput.value = this.value
			.toLowerCase()
			.replace( /[^a-z0-9\s-]/g, '' )
			.replace( /\s+/g, '-' )
			.replace( /-{2,}/g, '-' )
			.replace( /^-|-$/g, '' );
	} );
} )();
</script>
