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

	<!-- ======================================================================
	     AJAX-refreshable table region
	     ====================================================================== -->
	<div id="fcc-foods-list"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'fcc_foods_page' ) ); ?>"
		data-search="<?php echo esc_attr( $search ); ?>"
		data-cat="<?php echo esc_attr( $cat_filter ); ?>"
		data-orderby="<?php echo esc_attr( $orderby ); ?>"
		data-order="<?php echo esc_attr( strtolower( $order ) ); ?>">

		<?php include FCC_PLUGIN_DIR . 'admin/partials/page-foods-table.php'; ?>

	</div><!-- #fcc-foods-list -->

</div><!-- .wrap -->
