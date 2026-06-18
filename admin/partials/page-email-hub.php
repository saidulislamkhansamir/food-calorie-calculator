<?php
/**
 * Admin partial: Email Marketing Hub — subscriber management.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$per_page    = 20;
$paged       = max( 1, absint( $_GET['paged'] ?? 1 ) );
$food_filter = sanitize_text_field( $_GET['food_filter'] ?? '' );
$search      = sanitize_text_field( $_GET['search'] ?? '' );
$status_filter = sanitize_key( $_GET['eh_status'] ?? '' );

$args = [
	'food_name' => $food_filter,
	'search'    => $search,
	'status'    => $status_filter,
	'per_page'  => $per_page,
	'page'      => $paged,
];

$subscribers = \FCC\Database::get_email_subscribers( $args );
$total       = \FCC\Database::count_email_subscribers( $args );
$total_pages = (int) ceil( $total / $per_page );

// Stats.
$total_all     = \FCC\Database::count_email_subscribers( [] );
$total_pending = \FCC\Database::count_email_subscribers( [ 'status' => 'pending' ] );
$total_done    = \FCC\Database::count_email_subscribers( [ 'status' => 'done' ] );
$recent        = \FCC\Database::get_recent_optins( 1 );
$latest_date   = ! empty( $recent[0]['created_at'] ) ? date_i18n( 'M j, Y', strtotime( $recent[0]['created_at'] ) ) : '—';

$hub_nonce = wp_create_nonce( 'fcc_ajax_email_hub' );
?>
<div class="wrap fcc-admin-wrap fcc-eh-page">

	<!-- ═══ Hero ═══ -->
	<div class="fcc-eh-hero">
		<div class="fcc-eh-hero__main">
			<div class="fcc-eh-hero__icon" aria-hidden="true">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
			</div>
			<div>
				<h1 class="fcc-eh-hero__title"><?php esc_html_e( 'Email Marketing Hub', 'food-calorie-calculator' ); ?></h1>
				<p class="fcc-eh-hero__desc"><?php esc_html_e( 'Manage opted-in subscribers collected through food requests.', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>
		<div class="fcc-eh-hero__stats">
			<div class="fcc-eh-hero-stat">
				<span class="fcc-eh-hero-stat__value"><?php echo $total_all; ?></span>
				<span class="fcc-eh-hero-stat__label"><?php esc_html_e( 'Total', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-eh-hero-stat">
				<span class="fcc-eh-hero-stat__value"><?php echo $total_pending; ?></span>
				<span class="fcc-eh-hero-stat__label"><?php esc_html_e( 'Pending', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-eh-hero-stat">
				<span class="fcc-eh-hero-stat__value"><?php echo $total_done; ?></span>
				<span class="fcc-eh-hero-stat__label"><?php esc_html_e( 'Added', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-eh-hero-stat">
				<span class="fcc-eh-hero-stat__value"><?php echo esc_html( $latest_date ); ?></span>
				<span class="fcc-eh-hero-stat__label"><?php esc_html_e( 'Latest', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>
	</div>

	<!-- ═══ Toolbar ═══ -->
	<div class="fcc-eh-toolbar-v2">
		<div class="fcc-eh-toolbar__search">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<input type="text" id="fcc-eh-search" class="fcc-eh-search-input"
				placeholder="<?php esc_attr_e( 'Search email or food name…', 'food-calorie-calculator' ); ?>"
				value="<?php echo esc_attr( $search ); ?>">
		</div>

		<div class="fcc-eh-status-pills" id="fcc-eh-status-pills">
			<?php
			$statuses = [
				''          => __( 'All', 'food-calorie-calculator' ),
				'pending'   => __( 'Pending', 'food-calorie-calculator' ),
				'done'      => __( 'Added', 'food-calorie-calculator' ),
				'dismissed' => __( 'Dismissed', 'food-calorie-calculator' ),
			];
			foreach ( $statuses as $val => $lbl ) : ?>
				<button type="button" class="fcc-eh-pill<?php echo $status_filter === $val ? ' fcc-eh-pill--active' : ''; ?>"
					data-status="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></button>
			<?php endforeach; ?>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fcc-eh-export-form">
			<input type="hidden" name="action" value="fcc_export_subscribers">
			<?php wp_nonce_field( 'fcc_export_subscribers' ); ?>
			<button type="submit" class="fcc-eh-export-btn">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
				<?php esc_html_e( 'Export CSV', 'food-calorie-calculator' ); ?>
			</button>
		</form>
	</div>

	<!-- ═══ Table ═══ -->
	<div id="fcc-eh-table-wrap"
		data-nonce="<?php echo esc_attr( $hub_nonce ); ?>"
		data-search="<?php echo esc_attr( $search ); ?>"
		data-status="<?php echo esc_attr( $status_filter ); ?>"
		data-orderby="created_at"
		data-order="DESC">
		<?php include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub-table.php'; ?>
	</div>

</div>

<script>
(function () {
	var wrap      = document.getElementById( 'fcc-eh-table-wrap' );
	var searchEl  = document.getElementById( 'fcc-eh-search' );
	var pillsWrap = document.getElementById( 'fcc-eh-status-pills' );
	if ( ! wrap ) return;

	var nonce   = wrap.dataset.nonce;
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var curStatus  = wrap.dataset.status || '';
	var curOrderby = wrap.dataset.orderby || 'created_at';
	var curOrder   = wrap.dataset.order || 'DESC';

	function loadPage( paged ) {
		wrap.style.opacity = '.5';
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', ajaxUrl );
		xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
		xhr.onload = function () {
			wrap.style.opacity = '1';
			if ( xhr.status !== 200 ) return;
			try {
				var res = JSON.parse( xhr.responseText );
				if ( res.success ) { wrap.innerHTML = res.data.html; bindAll(); }
			} catch ( e ) {}
		};
		xhr.send(
			'action=fcc_email_hub_page' +
			'&_ajax_nonce=' + encodeURIComponent( nonce ) +
			'&search=' + encodeURIComponent( searchEl ? searchEl.value : '' ) +
			'&status=' + encodeURIComponent( curStatus ) +
			'&orderby=' + encodeURIComponent( curOrderby ) +
			'&order=' + encodeURIComponent( curOrder ) +
			'&paged=' + paged
		);
	}

	function bindAll() {
		wrap.querySelectorAll( '.fcc-eh-page-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () { loadPage( this.dataset.page ); } );
		} );
		wrap.querySelectorAll( '.fcc-eh-sort' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var col = this.dataset.col;
				if ( curOrderby === col ) {
					curOrder = curOrder === 'ASC' ? 'DESC' : 'ASC';
				} else {
					curOrderby = col;
					curOrder = col === 'created_at' ? 'DESC' : 'ASC';
				}
				loadPage( 1 );
			} );
		} );
		wrap.querySelectorAll( '.fcc-eh-delete-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				if ( ! confirm( '<?php echo esc_js( __( 'Remove this subscriber?', 'food-calorie-calculator' ) ); ?>' ) ) return;
				var id = this.dataset.id;
				var row = this.closest( 'tr' );
				if ( row ) row.style.opacity = '.3';
				var xhr2 = new XMLHttpRequest();
				xhr2.open( 'POST', ajaxUrl );
				xhr2.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
				xhr2.onload = function () { loadPage( 1 ); };
				xhr2.send( 'action=fcc_email_hub_delete&_ajax_nonce=' + encodeURIComponent( nonce ) + '&subscriber_id=' + id );
			} );
		} );
		wrap.querySelectorAll( '.fcc-eh-copy-btn' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var email = this.dataset.email;
				if ( navigator.clipboard ) {
					navigator.clipboard.writeText( email );
					this.textContent = '✓';
					var self = this;
					setTimeout( function () { self.textContent = '📋'; }, 1500 );
				}
			} );
		} );
	}

	// Search on Enter.
	if ( searchEl ) {
		searchEl.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) loadPage( 1 );
		} );
		var debounce = null;
		searchEl.addEventListener( 'input', function () {
			clearTimeout( debounce );
			debounce = setTimeout( function () { loadPage( 1 ); }, 400 );
		} );
	}

	// Status pills.
	if ( pillsWrap ) {
		pillsWrap.querySelectorAll( '.fcc-eh-pill' ).forEach( function ( pill ) {
			pill.addEventListener( 'click', function () {
				pillsWrap.querySelectorAll( '.fcc-eh-pill' ).forEach( function ( p ) { p.classList.remove( 'fcc-eh-pill--active' ); } );
				pill.classList.add( 'fcc-eh-pill--active' );
				curStatus = pill.dataset.status;
				loadPage( 1 );
			} );
		} );
	}

	bindAll();
}() );
</script>
