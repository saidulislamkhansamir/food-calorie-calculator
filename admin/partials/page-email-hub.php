<?php
/**
 * Admin partial: Email Marketing Hub.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$per_page    = 20;
$paged       = max( 1, absint( $_GET['paged'] ?? 1 ) );
$food_filter = sanitize_text_field( $_GET['food_filter'] ?? '' );

$args = [
	'food_name' => $food_filter,
	'per_page'  => $per_page,
	'page'      => $paged,
];

$subscribers = \FCC\Database::get_email_subscribers( $args );
$total       = \FCC\Database::count_email_subscribers( $args );
$total_pages = (int) ceil( $total / $per_page );
$total_all   = \FCC\Database::count_email_subscribers( [] );
$hub_nonce   = wp_create_nonce( 'fcc_ajax_email_hub' );
?>
<div class="wrap fcc-admin-wrap">

	<div class="fcc-eh-hero">
		<div>
			<h1><?php esc_html_e( 'Email Marketing Hub', 'food-calorie-calculator' ); ?></h1>
			<p><?php printf( esc_html__( '%d opted-in subscribers', 'food-calorie-calculator' ), $total_all ); ?></p>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0">
			<input type="hidden" name="action" value="fcc_export_subscribers">
			<?php wp_nonce_field( 'fcc_export_subscribers' ); ?>
			<button type="submit" class="button" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.5)">
				<?php esc_html_e( 'Export CSV', 'food-calorie-calculator' ); ?>
			</button>
		</form>
	</div>

	<div class="fcc-an-section">
		<div class="fcc-eh-toolbar">
			<input type="text" id="fcc-eh-food-filter" class="fcc-eh-filter-input"
				placeholder="<?php esc_attr_e( 'Filter by food name…', 'food-calorie-calculator' ); ?>"
				value="<?php echo esc_attr( $food_filter ); ?>">
			<button id="fcc-eh-filter-btn" class="button button-primary">
				<?php esc_html_e( 'Filter', 'food-calorie-calculator' ); ?>
			</button>
			<?php if ( $food_filter ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-email-hub' ) ); ?>" class="button">
					<?php esc_html_e( 'Clear', 'food-calorie-calculator' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div id="fcc-eh-table-wrap" data-nonce="<?php echo esc_attr( $hub_nonce ); ?>">
			<?php include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub-table.php'; ?>
		</div>
	</div>

</div>

<script>
(function () {
	var filterInput = document.getElementById( 'fcc-eh-food-filter' );
	var filterBtn   = document.getElementById( 'fcc-eh-filter-btn' );
	var tableWrap   = document.getElementById( 'fcc-eh-table-wrap' );
	var nonce       = tableWrap ? tableWrap.dataset.nonce : '';
	var ajaxUrl     = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

	function loadPage( foodFilter, paged ) {
		tableWrap.style.opacity = '.5';
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxUrl );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onload = function () {
			tableWrap.style.opacity = '1';
			if ( xhr.status !== 200 ) { return; }
			try {
				var res = JSON.parse( xhr.responseText );
				if ( res.success ) {
					tableWrap.innerHTML = res.data.html;
					bindPager();
				}
			} catch ( e ) {}
		};
		xhr.send(
			'action=fcc_email_hub_page' +
			'&_ajax_nonce=' + encodeURIComponent( nonce ) +
			'&food_filter=' + encodeURIComponent( foodFilter ) +
			'&paged=' + paged
		);
	}

	function bindPager() {
		tableWrap.querySelectorAll( '.fcc-eh-page-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				loadPage( filterInput.value, this.dataset.page );
			} );
		} );
	}

	if ( filterBtn ) {
		filterBtn.addEventListener( 'click', function () { loadPage( filterInput.value, 1 ); } );
	}
	if ( filterInput ) {
		filterInput.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) { loadPage( filterInput.value, 1 ); }
		} );
	}

	bindPager();
}() );
</script>
