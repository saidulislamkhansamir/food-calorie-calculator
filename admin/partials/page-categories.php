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

// Per-category stats (one query).
$cat_stats   = FCC\Database::get_category_stats();
$top_foods   = FCC\Database::get_top_food_per_category();
$food_counts = [];
foreach ( $cat_stats as $cid => $st ) {
	$food_counts[ $cid ] = $st['food_count'];
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

$edit_id = (int) $edit_id;
?>
<div class="wrap fcc-admin-wrap fcc-categories-page">

	<!-- Screen-reader h1 for accessibility; visual title lives in the hero card -->
	<h1 class="screen-reader-text"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></h1>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-cats-hero <?php echo $edit_cat ? 'fcc-cats-hero--editing' : ''; ?>" id="fcc-cats-hero">
		<div class="fcc-cats-hero__inner">

			<div class="fcc-cats-hero__content">
				<div class="fcc-cats-hero__icon" aria-hidden="true"><img src="<?php echo esc_url( FCC_PLUGIN_URL . 'logo/Food Calorie Calculator Favicon - White (1).png' ); ?>" width="48" height="48" alt="" decoding="async" style="display:block;width:48px;height:48px;object-fit:contain;"></div>
				<div>
					<div class="fcc-cats-hero__title">
						<?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?>
					</div>
					<p class="fcc-cats-hero__sub" id="fcc-cats-hero-sub">
						<?php echo $edit_cat
							/* translators: %s: category name */
							? sprintf( esc_html__( 'Currently editing &#8220;%s&#8221; &mdash; update the form below.', 'food-calorie-calculator' ), esc_html( $edit_cat['name'] ) )
							: esc_html__( 'Organise your foods into categories for better navigation and filtering.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>

			<div class="fcc-cats-hero__stats">
				<div class="fcc-cats-hero-stat">
					<span class="fcc-cats-hero-stat__value" id="fcc-cats-stat-count"><?php echo count( $categories ); ?></span>
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
	<div class="fcc-cats-formbar <?php echo $edit_cat ? 'fcc-cats-formbar--edit' : ''; ?>" id="fcc-cats-formbar">

		<div class="fcc-cats-formbar__label" id="fcc-cats-formbar-label">
			<?php if ( $edit_cat ) : ?>
				<span class="fcc-cats-formbar__mode fcc-cats-formbar__mode--edit" id="fcc-cats-formbar-mode">
					&#9999;&#65039; <?php esc_html_e( 'Editing', 'food-calorie-calculator' ); ?>
				</span>
				<strong id="fcc-cats-formbar-name"><?php echo esc_html( $edit_cat['name'] ); ?></strong>
			<?php else : ?>
				<span class="fcc-cats-formbar__mode" id="fcc-cats-formbar-mode">
					&#10133; <?php esc_html_e( 'Add New Category', 'food-calorie-calculator' ); ?>
				</span>
				<strong id="fcc-cats-formbar-name" hidden></strong>
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
					placeholder="<?php esc_attr_e( 'A brief description&hellip;', 'food-calorie-calculator' ); ?>"
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
					<button type="submit" id="fcc-cat-submit-btn"
						class="fcc-cats-qsubmit <?php echo $edit_cat ? 'fcc-cats-qsubmit--update' : ''; ?>">
						<?php echo $edit_cat
							? esc_html__( 'Update', 'food-calorie-calculator' )
							: esc_html__( 'Add Category', 'food-calorie-calculator' ); ?>
					</button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-categories' ) ); ?>"
						class="fcc-cats-qcancel"
						id="fcc-cat-cancel"
						<?php echo ! $edit_cat ? 'hidden' : ''; ?>>
						<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
					</a>
				</div>
			</div>

		</form>
	</div><!-- .fcc-cats-formbar -->

	<!-- ======================================================================
	     All categories grid (AJAX-refreshed region)
	     ====================================================================== -->
	<div class="fcc-cats-section" id="fcc-cats-region"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'fcc_ajax_cats' ) ); ?>">
		<?php include FCC_PLUGIN_DIR . 'admin/partials/page-categories-grid.php'; ?>
	</div><!-- #fcc-cats-region -->

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
