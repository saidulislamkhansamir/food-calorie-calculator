<?php
/**
 * Admin page: Food Requests.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$allowed_statuses = [ '', 'pending', 'done', 'dismissed' ];
$status_filter    = sanitize_key( $_GET['status'] ?? '' );
if ( ! in_array( $status_filter, $allowed_statuses, true ) ) {
	$status_filter = '';
}
$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
$paged    = max( 1, absint( $_GET['paged'] ?? 1 ) );
$per_page = 20;

// Hero stats.
$total_all     = \FCC\Database::count_food_requests();
$total_pending = \FCC\Database::count_food_requests( [ 'status' => 'pending' ] );
$total_done    = \FCC\Database::count_food_requests( [ 'status' => 'done' ] );
$total_optin   = \FCC\Database::count_opted_in_requests();

// Initial table data.
$total       = \FCC\Database::count_food_requests( [ 'status' => $status_filter, 'search' => $search ] );
$total_pages = (int) ceil( $total / $per_page );
$requests    = \FCC\Database::get_food_requests( [
	'status'   => $status_filter,
	'search'   => $search,
	'per_page' => $per_page,
	'page'     => $paged,
] );

$nonce = wp_create_nonce( 'fcc_ajax_reqs' );
?>
<div class="wrap fcc-admin-wrap fcc-reqs-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Requests', 'food-calorie-calculator' ); ?></h1>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-foods-hero">
		<div class="fcc-foods-hero__inner">

			<div class="fcc-foods-hero__content">
				<div class="fcc-foods-hero__icon" aria-hidden="true">📥</div>
				<div>
					<div class="fcc-foods-hero__title"><?php esc_html_e( 'Food Requests', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-foods-hero__sub">
						<?php esc_html_e( 'Foods users have requested to be added to the database. Review and action each request.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>

			<div class="fcc-foods-hero__stats">
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo (int) $total_all; ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Total', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value fcc-reqs-stat--pending"><?php echo (int) $total_pending; ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Pending', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value fcc-reqs-stat--optin"><?php echo (int) $total_optin; ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Newsletter', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo (int) $total_done; ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Done', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

		</div>
	</div><!-- .fcc-foods-hero -->

	<!-- ======================================================================
	     Toolbar: search + filter tabs
	     ====================================================================== -->
	<div class="fcc-reqs-toolbar">

		<div class="fcc-foods-searchbox fcc-reqs-searchbox">
			<svg class="fcc-foods-searchbox__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
			</svg>
			<input type="search" id="fcc-reqs-search"
				value="<?php echo esc_attr( $search ); ?>"
				placeholder="<?php esc_attr_e( 'Search by food name or email…', 'food-calorie-calculator' ); ?>"
				class="fcc-foods-searchbox__input">
		</div>

		<div class="fcc-reqs-tabs">
			<?php
			$tabs = [
				''          => __( 'All', 'food-calorie-calculator' ),
				'pending'   => __( 'Pending', 'food-calorie-calculator' ),
				'done'      => __( 'Done', 'food-calorie-calculator' ),
				'dismissed' => __( 'Dismissed', 'food-calorie-calculator' ),
			];
			foreach ( $tabs as $key => $label ) :
				$active = $status_filter === $key ? ' fcc-reqs-tab--active' : '';
			?>
				<button type="button"
					class="fcc-reqs-tab fcc-reqs-tab-btn<?php echo esc_attr( $active ); ?>"
					data-status="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

	</div><!-- .fcc-reqs-toolbar -->

	<!-- ======================================================================
	     AJAX region — table + pagination
	     ====================================================================== -->
	<div id="fcc-reqs-list"
		data-nonce="<?php echo esc_attr( $nonce ); ?>"
		data-status="<?php echo esc_attr( $status_filter ); ?>"
		data-search="<?php echo esc_attr( $search ); ?>"
		data-paged="<?php echo (int) $paged; ?>">
		<?php include FCC_PLUGIN_DIR . 'admin/partials/page-food-requests-table.php'; ?>
	</div><!-- #fcc-reqs-list -->

</div><!-- .wrap -->
