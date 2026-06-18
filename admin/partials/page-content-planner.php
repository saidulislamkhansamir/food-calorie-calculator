<?php
/**
 * Admin partial: Content Planner — priority-scored content gap management.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$items         = \FCC\Database::get_content_planner_items( 100 );
$planner_nonce = wp_create_nonce( 'fcc_ajax_planner' );

// Stats.
$total_items   = count( $items );
$high_priority = 0;
$total_searches = 0;
$total_requests = 0;
$max_score     = 0;
foreach ( $items as $it ) {
	$sc = (int) $it['priority_score'];
	if ( $sc >= 10 ) { $high_priority++; }
	if ( $sc > $max_score ) { $max_score = $sc; }
	$total_searches += (int) $it['missed_count'];
	$total_requests += (int) $it['request_count'];
}
?>
<div class="wrap fcc-admin-wrap fcc-cp-page">

	<!-- ═══ Hero ═══ -->
	<div class="fcc-cp-hero fcc-cp-hero--v2">
		<div class="fcc-cp-hero__main">
			<div class="fcc-cp-hero__icon" aria-hidden="true">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
			</div>
			<div>
				<h1 class="fcc-cp-hero__title"><?php esc_html_e( 'Content Planner', 'food-calorie-calculator' ); ?></h1>
				<p class="fcc-cp-hero__desc"><?php esc_html_e( 'Prioritised content gaps — missed searches + user requests scored and ranked.', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>
		<div class="fcc-cp-hero__stats">
			<div class="fcc-cp-hero-stat">
				<span class="fcc-cp-hero-stat__value"><?php echo $total_items; ?></span>
				<span class="fcc-cp-hero-stat__label"><?php esc_html_e( 'Items', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-cp-hero-stat fcc-cp-hero-stat--hot">
				<span class="fcc-cp-hero-stat__value"><?php echo $high_priority; ?></span>
				<span class="fcc-cp-hero-stat__label"><?php esc_html_e( 'Hot (10+)', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-cp-hero-stat">
				<span class="fcc-cp-hero-stat__value"><?php echo number_format_i18n( $total_searches ); ?></span>
				<span class="fcc-cp-hero-stat__label"><?php esc_html_e( 'Searches', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-cp-hero-stat">
				<span class="fcc-cp-hero-stat__value"><?php echo number_format_i18n( $total_requests ); ?></span>
				<span class="fcc-cp-hero-stat__label"><?php esc_html_e( 'Requests', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>
	</div>

	<!-- ═══ Toolbar ═══ -->
	<div class="fcc-cp-toolbar">
		<div class="fcc-cp-search">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<input type="text" id="fcc-cp-search" class="fcc-cp-search__input"
				placeholder="<?php esc_attr_e( 'Filter items…', 'food-calorie-calculator' ); ?>">
		</div>
		<div class="fcc-cp-filter-pills">
			<button type="button" class="fcc-cp-pill fcc-cp-pill--active" data-filter="all"><?php esc_html_e( 'All', 'food-calorie-calculator' ); ?> <span class="fcc-cp-pill__count"><?php echo $total_items; ?></span></button>
			<button type="button" class="fcc-cp-pill" data-filter="hot"><?php esc_html_e( 'Hot 10+', 'food-calorie-calculator' ); ?> <span class="fcc-cp-pill__count"><?php echo $high_priority; ?></span></button>
			<button type="button" class="fcc-cp-pill" data-filter="searches"><?php esc_html_e( 'Searches Only', 'food-calorie-calculator' ); ?></button>
			<button type="button" class="fcc-cp-pill" data-filter="requests"><?php esc_html_e( 'Requests Only', 'food-calorie-calculator' ); ?></button>
		</div>
		<div class="fcc-cp-scoring-info" title="<?php esc_attr_e( 'Priority = (missed searches × 2) + food requests', 'food-calorie-calculator' ); ?>">
			<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
			<?php esc_html_e( 'Score = searches×2 + requests', 'food-calorie-calculator' ); ?>
		</div>
	</div>

	<!-- ═══ Table ═══ -->
	<?php if ( empty( $items ) ) : ?>
		<div class="fcc-cp-empty">
			<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
			<p><?php esc_html_e( 'No content gaps found — your food database is comprehensive!', 'food-calorie-calculator' ); ?></p>
		</div>
	<?php else : ?>

	<div class="fcc-cp-table-wrap" id="fcc-cp-table-wrap">
		<table class="fcc-cp-table" id="fcc-cp-table">
			<thead>
				<tr>
					<th class="fcc-cp-th--num">#</th>
					<th class="fcc-cp-th--score"><?php esc_html_e( 'Score', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-cp-th--bar"></th>
					<th class="fcc-cp-th--item"><?php esc_html_e( 'Item', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-cp-th--num"><?php esc_html_e( 'Searches', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-cp-th--num"><?php esc_html_e( 'Requests', 'food-calorie-calculator' ); ?></th>
					<th class="fcc-cp-th--actions"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $items as $idx => $item ) :
				$score       = (int) $item['priority_score'];
				$score_class = $score >= 20 ? 'fcc-cp-sc--high' : ( $score >= 10 ? 'fcc-cp-sc--med' : 'fcc-cp-sc--low' );
				$ms_id       = (int) ( $item['ms_id'] ?? 0 );
				$name        = $item['item'] ?? $item['item_name'] ?? '';
				$missed      = (int) $item['missed_count'];
				$reqs        = (int) $item['request_count'];
				$bar_pct     = $max_score > 0 ? round( $score / $max_score * 100 ) : 0;
			?>
				<tr class="fcc-cp-row" id="fcc-cp-row-<?php echo $ms_id ?: esc_attr( $name ); ?>"
					data-score="<?php echo $score; ?>"
					data-missed="<?php echo $missed; ?>"
					data-reqs="<?php echo $reqs; ?>"
					data-name="<?php echo esc_attr( strtolower( $name ) ); ?>">
					<td class="fcc-cp-td--num"><?php echo $idx + 1; ?></td>
					<td class="fcc-cp-td--score">
						<span class="fcc-cp-sc <?php echo esc_attr( $score_class ); ?>"><?php echo $score; ?></span>
					</td>
					<td class="fcc-cp-td--bar">
						<div class="fcc-cp-bar"><div class="fcc-cp-bar__fill <?php echo esc_attr( $score_class ); ?>" style="width:<?php echo max( $bar_pct, 3 ); ?>%"></div></div>
					</td>
					<td class="fcc-cp-td--item">
						<strong><?php echo esc_html( $name ); ?></strong>
						<?php if ( $score >= 10 ) : ?>
							<span class="fcc-cp-hot">🔥</span>
						<?php endif; ?>
					</td>
					<td class="fcc-cp-td--num">
						<?php if ( $missed > 0 ) : ?>
							<span class="fcc-cp-count fcc-cp-count--search"><?php echo $missed; ?></span>
						<?php else : ?>
							<span class="fcc-cp-zero">—</span>
						<?php endif; ?>
					</td>
					<td class="fcc-cp-td--num">
						<?php if ( $reqs > 0 ) : ?>
							<span class="fcc-cp-count fcc-cp-count--req"><?php echo $reqs; ?></span>
						<?php else : ?>
							<span class="fcc-cp-zero">—</span>
						<?php endif; ?>
					</td>
					<td class="fcc-cp-td--actions">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&action=add&food_name=' . urlencode( $name ) ) ); ?>"
							class="fcc-cp-btn fcc-cp-btn--add" target="_blank">
							<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
							<?php esc_html_e( 'Add', 'food-calorie-calculator' ); ?>
						</a>
						<?php if ( $ms_id > 0 ) : ?>
						<button type="button" class="fcc-cp-btn fcc-cp-btn--dismiss fcc-cp-dismiss-btn"
							data-ms-id="<?php echo $ms_id; ?>" data-nonce="<?php echo esc_attr( $planner_nonce ); ?>">
							<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
						</button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div>

<script>
(function () {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

	// ── Dismiss ──
	document.querySelectorAll( '.fcc-cp-dismiss-btn' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var msId  = this.dataset.msId;
			var nonce = this.dataset.nonce;
			var row   = document.getElementById( 'fcc-cp-row-' + msId );
			btn.disabled = true;
			var xhr = new XMLHttpRequest();
			xhr.open( 'POST', ajaxUrl );
			xhr.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
			xhr.onload = function () {
				try {
					var res = JSON.parse( xhr.responseText );
					if ( res.success && row ) {
						row.style.transition = 'opacity .3s';
						row.style.opacity = '0';
						setTimeout( function () { row.remove(); }, 310 );
					} else { btn.disabled = false; }
				} catch ( e ) { btn.disabled = false; }
			};
			xhr.send( 'action=fcc_planner_dismiss&_ajax_nonce=' + encodeURIComponent( nonce ) + '&ms_id=' + encodeURIComponent( msId ) );
		} );
	} );

	// ── Search filter (client-side) ──
	var searchEl = document.getElementById( 'fcc-cp-search' );
	if ( searchEl ) {
		searchEl.addEventListener( 'input', function () {
			var q = this.value.toLowerCase();
			document.querySelectorAll( '.fcc-cp-row' ).forEach( function ( row ) {
				row.style.display = row.dataset.name.indexOf( q ) !== -1 ? '' : 'none';
			} );
		} );
	}

	// ── Filter pills (client-side) ──
	document.querySelectorAll( '.fcc-cp-pill' ).forEach( function ( pill ) {
		pill.addEventListener( 'click', function () {
			document.querySelectorAll( '.fcc-cp-pill' ).forEach( function ( p ) { p.classList.remove( 'fcc-cp-pill--active' ); } );
			pill.classList.add( 'fcc-cp-pill--active' );
			var filter = pill.dataset.filter;
			document.querySelectorAll( '.fcc-cp-row' ).forEach( function ( row ) {
				var show = true;
				if ( filter === 'hot' ) show = parseInt( row.dataset.score, 10 ) >= 10;
				else if ( filter === 'searches' ) show = parseInt( row.dataset.missed, 10 ) > 0 && parseInt( row.dataset.reqs, 10 ) === 0;
				else if ( filter === 'requests' ) show = parseInt( row.dataset.reqs, 10 ) > 0 && parseInt( row.dataset.missed, 10 ) === 0;
				row.style.display = show ? '' : 'none';
			} );
		} );
	} );
}() );
</script>
