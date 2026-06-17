<?php
/**
 * Analytics page — tabbed business intelligence dashboard.
 *
 * Tabs: Overview | Search | Monetization | Content | Audience
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

// ── Range selector ──────────────────────────────────────────────────────
$allowed_ranges = [ 7, 30, 90, 0 ];
$range          = absint( $_GET['range'] ?? 30 );
if ( ! in_array( $range, $allowed_ranges, true ) ) { $range = 30; }
$range_labels = [ 7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days', 0 => 'All Time' ];
$active_tab   = sanitize_key( $_GET['tab'] ?? 'overview' );

// ── Overview KPIs ───────────────────────────────────────────────────────
$search_cmp   = \FCC\Database::count_searches_with_comparison( $range );
$rate_cmp     = \FCC\Database::get_success_rate_with_comparison( $range );
$kpi_gaps     = \FCC\Database::count_active_missed_searches();
$kpi_requests = \FCC\Database::count_food_requests_grouped( [ 'status' => 'pending' ] );
$kpi_subs     = \FCC\Database::count_opted_in_requests();
$kpi_sponsors = \FCC\Database::count_active_sponsors();

// Supplement aggregate stats.
$supp_catalog = \FCC\Admin\Supplements::get_catalog_with_stats();
$supp_config  = \FCC\Admin\Supplements::get_config();
$supp_totals  = [ 'clicks' => 0, 'impressions' => 0 ];
foreach ( $supp_catalog as $s ) {
	$supp_totals['clicks']      += (int) ( $s['clicks'] ?? 0 );
	$supp_totals['impressions'] += (int) ( $s['impressions'] ?? 0 );
}
$supp_ctr     = $supp_totals['impressions'] > 0
	? round( $supp_totals['clicks'] / $supp_totals['impressions'] * 100, 1 )
	: 0;
$avg_comm     = (float) ( $supp_config['avg_commission'] ?? 0 );
$supp_revenue = round( $supp_totals['clicks'] * $avg_comm, 2 );

// White Label stats.
$wl_stats = \FCC\Database::get_wl_stats();

// Sponsor clicks for overview.
$sponsor_cmp = \FCC\Database::get_total_sponsor_clicks( $range > 0 ? $range : 30 );

// Affiliates config.
$aff_config  = \FCC\Admin\Affiliates::get_config();
$aff_enabled = 0;
$aff_names   = [];
if ( ! empty( $aff_config['retailers'] ) ) {
	foreach ( $aff_config['retailers'] as $r ) {
		if ( ! empty( $r['enabled'] ) ) { $aff_enabled++; $aff_names[] = $r['custom_label'] ?? $r['name'] ?? ''; }
	}
}

// ── Content tab data ────────────────────────────────────────────────────
$scored_gaps   = \FCC\Database::get_scored_content_gaps( 20 );
$pipeline      = \FCC\Database::get_request_pipeline_counts();
$cat_coverage  = \FCC\Database::get_category_coverage();

// ── Monetization tab data ───────────────────────────────────────────────
$sponsor_by_food = \FCC\Database::get_sponsor_clicks_by_food( $range > 0 ? $range : 0, 5 );

// Sort supplements by clicks for top 5.
$supp_top5 = $supp_catalog;
usort( $supp_top5, function ( $a, $b ) { return ( $b['clicks'] ?? 0 ) - ( $a['clicks'] ?? 0 ); } );
$supp_top5 = array_slice( $supp_top5, 0, 5 );

// ── Audience tab data ───────────────────────────────────────────────────
$recent_optins = \FCC\Database::get_recent_optins( 10 );

// ── Nonces ──────────────────────────────────────────────────────────────
$analytics_nonce = wp_create_nonce( 'fcc_analytics_nonce' );
$export_nonce    = wp_create_nonce( 'fcc_export_subscribers' );
$csv_nonce       = wp_create_nonce( 'fcc_export_analytics_csv' );

// ── Delta helper ────────────────────────────────────────────────────────
function fcc_delta_badge( float $current, float $previous, string $suffix = '' ): string {
	if ( $previous <= 0 ) { return ''; }
	$pct = round( ( $current - $previous ) / $previous * 100, 1 );
	if ( abs( $pct ) < 0.1 ) {
		$cls = 'fcc-an-delta--neutral';
		$arrow = '—';
	} elseif ( $pct > 0 ) {
		$cls = 'fcc-an-delta--up';
		$arrow = '↑';
	} else {
		$cls = 'fcc-an-delta--down';
		$arrow = '↓';
	}
	return '<span class="fcc-an-delta ' . $cls . '">' . $arrow . ' ' . abs( $pct ) . '%' . esc_html( $suffix ) . '</span>';
}
?>
<div class="wrap fcc-admin-wrap fcc-an-page"
	data-nonce="<?php echo esc_attr( $analytics_nonce ); ?>"
	data-range="<?php echo esc_attr( $range ); ?>"
	data-tab="<?php echo esc_attr( $active_tab ); ?>"
	data-csv-nonce="<?php echo esc_attr( $csv_nonce ); ?>">

	<!-- ═══ Hero ═══ -->
	<div class="fcc-an-hero">
		<div class="fcc-an-hero__main">
			<div class="fcc-an-hero__icon">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
			</div>
			<div>
				<h1 class="fcc-an-hero__title"><?php esc_html_e( 'Analytics', 'food-calorie-calculator' ); ?></h1>
				<p class="fcc-an-hero__desc"><?php esc_html_e( 'Business intelligence — search, monetization, content, and audience.', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>
		<div class="fcc-an-range-pills">
			<?php foreach ( $range_labels as $val => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( [ 'range' => $val, 'tab' => $active_tab ] ) ); ?>"
					class="fcc-reqs-pill<?php echo $range === $val ? ' fcc-reqs-pill--active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- ═══ Tab Navigation ═══ -->
	<div class="fcc-an-tabs">
		<?php
		$tabs = [
			'overview'      => [ 'Overview',      'M3 3v18h18' ],
			'search'        => [ 'Search',        'M11 11a8 8 0 100-16 8 8 0 000 16zM21 21l-4.35-4.35' ],
			'monetization'  => [ 'Monetization',   'M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6' ],
			'content'       => [ 'Content',        'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z' ],
			'audience'      => [ 'Audience',       'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75' ],
		];
		foreach ( $tabs as $key => $t ) : ?>
			<button type="button" class="fcc-an-tab<?php echo $active_tab === $key ? ' fcc-an-tab--active' : ''; ?>" data-tab="<?php echo esc_attr( $key ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo $t[1]; ?>"/></svg>
				<?php echo esc_html( $t[0] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<!-- ═══════════════════════════════════════════════════════════════════
	     TAB 1 — OVERVIEW
	     ═══════════════════════════════════════════════════════════════════ -->
	<div id="fcc-an-panel-overview" class="fcc-an-tab-panel<?php echo $active_tab === 'overview' ? ' fcc-an-tab-panel--active' : ''; ?>">

		<!-- KPI Cards (8) -->
		<div class="fcc-an-kpi-row fcc-an-kpi-row--8">

			<div class="fcc-an-kpi-card fcc-an-kpi-card--green">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $search_cmp['current'] ); ?></span>
					<?php echo fcc_delta_badge( $search_cmp['current'], $search_cmp['previous'] ); ?>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Total Searches', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php echo esc_html( $range_labels[ $range ] ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--blue">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo esc_html( $rate_cmp['current'] ); ?>%</span>
					<?php echo fcc_delta_badge( $rate_cmp['current'], $rate_cmp['previous'] ); ?>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Success Rate', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Searches finding a food', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--amber">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_gaps ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Content Gaps', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Active missed searches', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--green">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_requests ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Pending Requests', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Food add requests', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--blue">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_subs ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Email Subscribers', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Newsletter opt-ins', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--gold">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_sponsors ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Active Sponsors', 'food-calorie-calculator' ); ?></span>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-sponsored' ) ); ?>" class="fcc-an-kpi-card__sub" style="color:inherit;text-decoration:underline"><?php esc_html_e( 'Manage →', 'food-calorie-calculator' ); ?></a>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--purple">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 1v3M10 1v3M14 1v3"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo esc_html( $supp_ctr ); ?>%</span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Supplement CTR', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php echo esc_html( number_format( $supp_totals['clicks'] ) . ' clicks' ); ?></span>
				</div>
			</div>

			<div class="fcc-an-kpi-card fcc-an-kpi-card--teal">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value">£<?php echo number_format( $wl_stats['mrr'], 0 ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'White Label MRR', 'food-calorie-calculator' ); ?></span>
					<span class="fcc-an-kpi-card__sub"><?php echo esc_html( $wl_stats['active'] . ' active licenses' ); ?></span>
				</div>
			</div>

		</div>

		<!-- Charts Row -->
		<div class="fcc-an-charts-row">
			<div class="fcc-card fcc-an-chart-card">
				<div class="fcc-an-chart-card__header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
					<strong><?php esc_html_e( 'Search Volume', 'food-calorie-calculator' ); ?></strong>
					<span class="fcc-an-chart-card__sub"><?php echo esc_html( $range_labels[ $range ] ); ?></span>
				</div>
				<div class="fcc-an-chart-wrap">
					<canvas id="fcc-an-volume-chart"></canvas>
					<div class="fcc-an-spinner" id="fcc-an-volume-spinner"><span class="spinner is-active"></span></div>
				</div>
			</div>
			<div class="fcc-card fcc-an-chart-card">
				<div class="fcc-an-chart-card__header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/></svg>
					<strong><?php esc_html_e( 'Top 10 Foods', 'food-calorie-calculator' ); ?></strong>
					<span class="fcc-an-chart-card__sub"><?php esc_html_e( 'By search hits (all time)', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-an-chart-wrap">
					<canvas id="fcc-an-foods-chart"></canvas>
					<div class="fcc-an-spinner" id="fcc-an-foods-spinner"><span class="spinner is-active"></span></div>
				</div>
			</div>
		</div>

		<!-- Quick Revenue Summary -->
		<div class="fcc-an-revenue-row">
			<div class="fcc-an-rev-card fcc-an-rev-card--purple">
				<span class="fcc-an-rev-card__icon">💊</span>
				<div class="fcc-an-rev-card__data">
					<span class="fcc-an-rev-card__val"><?php echo number_format( $supp_totals['clicks'] ); ?></span>
					<span class="fcc-an-rev-card__label"><?php esc_html_e( 'Supplement Clicks', 'food-calorie-calculator' ); ?></span>
				</div>
				<span class="fcc-an-rev-card__extra">≈ £<?php echo number_format( $supp_revenue, 2 ); ?></span>
			</div>
			<div class="fcc-an-rev-card fcc-an-rev-card--gold">
				<span class="fcc-an-rev-card__icon">⭐</span>
				<div class="fcc-an-rev-card__data">
					<span class="fcc-an-rev-card__val"><?php echo number_format( $sponsor_cmp['current'] ); ?></span>
					<span class="fcc-an-rev-card__label"><?php esc_html_e( 'Sponsor Clicks', 'food-calorie-calculator' ); ?></span>
				</div>
				<?php echo fcc_delta_badge( $sponsor_cmp['current'], $sponsor_cmp['previous'] ); ?>
			</div>
			<div class="fcc-an-rev-card fcc-an-rev-card--teal">
				<span class="fcc-an-rev-card__icon">🏷️</span>
				<div class="fcc-an-rev-card__data">
					<span class="fcc-an-rev-card__val">£<?php echo number_format( $wl_stats['arr'], 0 ); ?></span>
					<span class="fcc-an-rev-card__label"><?php esc_html_e( 'White Label ARR', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
			<div class="fcc-an-rev-card fcc-an-rev-card--green">
				<span class="fcc-an-rev-card__icon">🔗</span>
				<div class="fcc-an-rev-card__data">
					<span class="fcc-an-rev-card__val"><?php echo $aff_enabled; ?></span>
					<span class="fcc-an-rev-card__label"><?php esc_html_e( 'Affiliate Retailers', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>

	</div><!-- #fcc-an-panel-overview -->

	<!-- ═══════════════════════════════════════════════════════════════════
	     TAB 2 — SEARCH INTELLIGENCE
	     ═══════════════════════════════════════════════════════════════════ -->
	<div id="fcc-an-panel-search" class="fcc-an-tab-panel<?php echo $active_tab === 'search' ? ' fcc-an-tab-panel--active' : ''; ?>">

		<!-- Trending Searches Table -->
		<div class="fcc-card fcc-an-section">
			<div class="fcc-an-section__header">
				<div class="fcc-an-section__title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
					<strong><?php esc_html_e( 'Trending Searches', 'food-calorie-calculator' ); ?></strong>
				</div>
				<span class="fcc-an-section__desc"><?php esc_html_e( 'Queries with biggest growth vs previous period.', 'food-calorie-calculator' ); ?></span>
				<button type="button" class="fcc-an-export-btn" data-section="trending_searches" title="<?php esc_attr_e( 'Export CSV', 'food-calorie-calculator' ); ?>">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
				</button>
			</div>
			<div id="fcc-an-trending-table">
				<p class="fcc-an-empty"><span class="spinner is-active" style="float:none"></span> <?php esc_html_e( 'Loading…', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>

		<!-- Search Charts (2x2 grid) -->
		<div class="fcc-an-charts-row fcc-an-charts-row--search">
			<div class="fcc-card fcc-an-chart-card">
				<div class="fcc-an-chart-card__header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3498db" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
					<strong><?php esc_html_e( 'Success Rate Trend', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-chart-wrap">
					<canvas id="fcc-an-success-chart"></canvas>
					<div class="fcc-an-spinner" id="fcc-an-success-spinner"><span class="spinner is-active"></span></div>
				</div>
			</div>
			<div class="fcc-card fcc-an-chart-card">
				<div class="fcc-an-chart-card__header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9b59b6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
					<strong><?php esc_html_e( 'Category Breakdown', 'food-calorie-calculator' ); ?></strong>
					<span class="fcc-an-chart-card__sub"><?php esc_html_e( 'All time', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-an-chart-wrap">
					<canvas id="fcc-an-category-chart"></canvas>
					<div class="fcc-an-spinner" id="fcc-an-category-spinner"><span class="spinner is-active"></span></div>
				</div>
			</div>
			<div class="fcc-card fcc-an-chart-card">
				<div class="fcc-an-chart-card__header">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e67e22" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="8" y1="12" x2="8" y2="21"/><line x1="12" y1="8" x2="12" y2="21"/><line x1="16" y1="15" x2="16" y2="21"/></svg>
					<strong><?php esc_html_e( 'Peak Search Days', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-chart-wrap">
					<canvas id="fcc-an-peak-chart"></canvas>
					<div class="fcc-an-spinner" id="fcc-an-peak-spinner"><span class="spinner is-active"></span></div>
				</div>
			</div>
		</div>

	</div><!-- #fcc-an-panel-search -->

	<!-- ═══════════════════════════════════════════════════════════════════
	     TAB 3 — MONETIZATION
	     ═══════════════════════════════════════════════════════════════════ -->
	<div id="fcc-an-panel-monetization" class="fcc-an-tab-panel<?php echo $active_tab === 'monetization' ? ' fcc-an-tab-panel--active' : ''; ?>">

		<div class="fcc-an-monetization-grid">

			<!-- Supplements Card -->
			<div class="fcc-card fcc-an-rev-detail fcc-an-rev-detail--purple">
				<div class="fcc-an-rev-detail__header">
					<span>💊</span>
					<strong><?php esc_html_e( 'Supplements', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-rev-detail__kpis">
					<div><span class="fcc-an-rev-detail__num"><?php echo number_format( $supp_totals['clicks'] ); ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></span></div>
					<div><span class="fcc-an-rev-detail__num"><?php echo number_format( $supp_totals['impressions'] ); ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Impressions', 'food-calorie-calculator' ); ?></span></div>
					<div><span class="fcc-an-rev-detail__num"><?php echo esc_html( $supp_ctr ); ?>%</span><span class="fcc-an-rev-detail__lbl">CTR</span></div>
					<div><span class="fcc-an-rev-detail__num">£<?php echo number_format( $supp_revenue, 2 ); ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Est. Revenue', 'food-calorie-calculator' ); ?></span></div>
				</div>
				<?php if ( $supp_top5 ) : ?>
					<table class="fcc-an-table fcc-an-table--compact">
						<thead><tr><th><?php esc_html_e( 'Supplement', 'food-calorie-calculator' ); ?></th><th><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></th><th>CTR</th></tr></thead>
						<tbody>
						<?php foreach ( $supp_top5 as $s ) :
							$ctr = ( $s['impressions'] ?? 0 ) > 0 ? round( ( $s['clicks'] ?? 0 ) / $s['impressions'] * 100, 1 ) : 0; ?>
							<tr>
								<td><?php echo esc_html( $s['name'] ); ?></td>
								<td class="fcc-an-td--center"><?php echo (int) ( $s['clicks'] ?? 0 ); ?></td>
								<td class="fcc-an-td--center"><?php echo esc_html( $ctr ); ?>%</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-supplements' ) ); ?>" class="fcc-an-view-all"><?php esc_html_e( 'Manage Supplements →', 'food-calorie-calculator' ); ?></a>
			</div>

			<!-- Sponsors Card -->
			<div class="fcc-card fcc-an-rev-detail fcc-an-rev-detail--gold">
				<div class="fcc-an-rev-detail__header">
					<span>⭐</span>
					<strong><?php esc_html_e( 'Sponsors', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-rev-detail__kpis">
					<div>
						<span class="fcc-an-rev-detail__num"><?php echo number_format( $sponsor_cmp['current'] ); ?></span>
						<?php echo fcc_delta_badge( $sponsor_cmp['current'], $sponsor_cmp['previous'] ); ?>
						<span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></span>
					</div>
					<div><span class="fcc-an-rev-detail__num"><?php echo $kpi_sponsors; ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Active', 'food-calorie-calculator' ); ?></span></div>
				</div>
				<div class="fcc-an-chart-wrap fcc-an-chart-wrap--mini">
					<canvas id="fcc-an-sponsor-chart"></canvas>
				</div>
				<?php if ( $sponsor_by_food ) : ?>
					<table class="fcc-an-table fcc-an-table--compact">
						<thead><tr><th><?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?></th><th><?php esc_html_e( 'Sponsor', 'food-calorie-calculator' ); ?></th><th><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $sponsor_by_food as $sf ) : ?>
							<tr>
								<td><?php echo esc_html( $sf['food_name'] ); ?></td>
								<td><?php echo esc_html( $sf['sponsor_name'] ); ?></td>
								<td class="fcc-an-td--center"><?php echo (int) $sf['clicks']; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-sponsored' ) ); ?>" class="fcc-an-view-all"><?php esc_html_e( 'Manage Sponsors →', 'food-calorie-calculator' ); ?></a>
			</div>

			<!-- White Label Card -->
			<div class="fcc-card fcc-an-rev-detail fcc-an-rev-detail--teal">
				<div class="fcc-an-rev-detail__header">
					<span>🏷️</span>
					<strong><?php esc_html_e( 'White Label', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-rev-detail__kpis">
					<div><span class="fcc-an-rev-detail__num"><?php echo $wl_stats['active']; ?>/<?php echo $wl_stats['total']; ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Active / Total', 'food-calorie-calculator' ); ?></span></div>
					<div><span class="fcc-an-rev-detail__num">£<?php echo number_format( $wl_stats['mrr'], 0 ); ?></span><span class="fcc-an-rev-detail__lbl">MRR</span></div>
					<div><span class="fcc-an-rev-detail__num">£<?php echo number_format( $wl_stats['arr'], 0 ); ?></span><span class="fcc-an-rev-detail__lbl">ARR</span></div>
				</div>
				<?php if ( $wl_stats['expiring_30d'] > 0 ) : ?>
					<p class="fcc-an-rev-detail__warn">⚠ <?php printf( esc_html__( '%d license(s) expiring within 30 days', 'food-calorie-calculator' ), $wl_stats['expiring_30d'] ); ?></p>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-white-label' ) ); ?>" class="fcc-an-view-all"><?php esc_html_e( 'Manage Licenses →', 'food-calorie-calculator' ); ?></a>
			</div>

			<!-- Affiliates Card -->
			<div class="fcc-card fcc-an-rev-detail fcc-an-rev-detail--green">
				<div class="fcc-an-rev-detail__header">
					<span>🔗</span>
					<strong><?php esc_html_e( 'Affiliates', 'food-calorie-calculator' ); ?></strong>
				</div>
				<div class="fcc-an-rev-detail__kpis">
					<div><span class="fcc-an-rev-detail__num"><?php echo $aff_enabled; ?></span><span class="fcc-an-rev-detail__lbl"><?php esc_html_e( 'Enabled Retailers', 'food-calorie-calculator' ); ?></span></div>
				</div>
				<?php if ( $aff_names ) : ?>
					<div class="fcc-an-rev-detail__tags">
						<?php foreach ( array_slice( $aff_names, 0, 8 ) as $n ) : ?>
							<span class="fcc-an-tag"><?php echo esc_html( $n ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<p class="fcc-an-rev-detail__note"><?php esc_html_e( 'Click tracking coming soon.', 'food-calorie-calculator' ); ?></p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-affiliates' ) ); ?>" class="fcc-an-view-all"><?php esc_html_e( 'Manage Affiliates →', 'food-calorie-calculator' ); ?></a>
			</div>

		</div>

	</div><!-- #fcc-an-panel-monetization -->

	<!-- ═══════════════════════════════════════════════════════════════════
	     TAB 4 — CONTENT INTELLIGENCE
	     ═══════════════════════════════════════════════════════════════════ -->
	<div id="fcc-an-panel-content" class="fcc-an-tab-panel<?php echo $active_tab === 'content' ? ' fcc-an-tab-panel--active' : ''; ?>">

		<!-- Request Pipeline -->
		<div class="fcc-an-pipeline-row">
			<div class="fcc-an-pipeline-card fcc-an-pipeline-card--pending">
				<span class="fcc-an-pipeline-card__num"><?php echo $pipeline['pending']; ?></span>
				<span class="fcc-an-pipeline-card__lbl"><?php esc_html_e( 'Pending', 'food-calorie-calculator' ); ?></span>
			</div>
			<span class="fcc-an-pipeline-arrow">→</span>
			<div class="fcc-an-pipeline-card fcc-an-pipeline-card--done">
				<span class="fcc-an-pipeline-card__num"><?php echo $pipeline['done']; ?></span>
				<span class="fcc-an-pipeline-card__lbl"><?php esc_html_e( 'Done', 'food-calorie-calculator' ); ?></span>
			</div>
			<span class="fcc-an-pipeline-arrow">→</span>
			<div class="fcc-an-pipeline-card fcc-an-pipeline-card--dismissed">
				<span class="fcc-an-pipeline-card__num"><?php echo $pipeline['dismissed']; ?></span>
				<span class="fcc-an-pipeline-card__lbl"><?php esc_html_e( 'Dismissed', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-an-pipeline-card fcc-an-pipeline-card--total">
				<span class="fcc-an-pipeline-card__num"><?php echo $pipeline['total']; ?></span>
				<span class="fcc-an-pipeline-card__lbl"><?php esc_html_e( 'Total', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-an-tables-row">

			<!-- Scored Content Gaps -->
			<div class="fcc-card fcc-an-section">
				<div class="fcc-an-section__header">
					<div class="fcc-an-section__title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<strong><?php esc_html_e( 'Prioritised Content Gaps', 'food-calorie-calculator' ); ?></strong>
					</div>
					<span class="fcc-an-section__desc"><?php esc_html_e( 'Scored by search count × recency. Add high-priority foods first.', 'food-calorie-calculator' ); ?></span>
					<button type="button" class="fcc-an-export-btn" data-section="content_gaps" title="<?php esc_attr_e( 'Export CSV', 'food-calorie-calculator' ); ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					</button>
				</div>
				<?php if ( empty( $scored_gaps ) ) : ?>
					<p class="fcc-an-empty"><?php esc_html_e( 'No content gaps found.', 'food-calorie-calculator' ); ?></p>
				<?php else : ?>
					<table class="fcc-an-table">
						<thead>
							<tr>
								<th>#</th>
								<th><?php esc_html_e( 'Query', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Searches', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Last Searched', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Priority', 'food-calorie-calculator' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $scored_gaps as $i => $gap ) :
							$score = (float) $gap['priority_score'];
							$badge_cls = $score >= 15 ? 'fcc-an-score--critical' : ( $score >= 8 ? 'fcc-an-score--high' : 'fcc-an-score--med' );
						?>
							<tr>
								<td class="fcc-an-td--num"><?php echo $i + 1; ?></td>
								<td><?php echo esc_html( $gap['query'] ); ?></td>
								<td class="fcc-an-td--center"><span class="fcc-reqs-count-badge"><?php echo (int) $gap['search_count']; ?>×</span></td>
								<td class="fcc-an-td--date"><?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $gap['last_searched_at'] ) ) ); ?></td>
								<td class="fcc-an-td--center"><span class="fcc-an-score <?php echo esc_attr( $badge_cls ); ?>"><?php echo (int) $score; ?></span></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&food_name=' . urlencode( $gap['query'] ) ) ); ?>" class="fcc-an-quick-action" title="<?php esc_attr_e( 'Add this food', 'food-calorie-calculator' ); ?>">+ Add</a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<!-- Category Coverage -->
			<div class="fcc-card fcc-an-section">
				<div class="fcc-an-section__header">
					<div class="fcc-an-section__title">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
						<strong><?php esc_html_e( 'Category Coverage', 'food-calorie-calculator' ); ?></strong>
					</div>
					<span class="fcc-an-section__desc"><?php esc_html_e( 'Foods per category — identify gaps in your database.', 'food-calorie-calculator' ); ?></span>
					<button type="button" class="fcc-an-export-btn" data-section="category_coverage" title="<?php esc_attr_e( 'Export CSV', 'food-calorie-calculator' ); ?>">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					</button>
				</div>
				<?php if ( empty( $cat_coverage ) ) : ?>
					<p class="fcc-an-empty"><?php esc_html_e( 'No categories yet.', 'food-calorie-calculator' ); ?></p>
				<?php else : ?>
					<?php $max_foods = max( array_column( $cat_coverage, 'food_count' ) ) ?: 1; ?>
					<table class="fcc-an-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Coverage', 'food-calorie-calculator' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $cat_coverage as $cc ) :
							$cnt = (int) $cc['food_count'];
							$pct = round( $cnt / $max_foods * 100 );
							$bar_cls = $cnt === 0 ? 'fcc-an-bar--empty' : ( $pct < 25 ? 'fcc-an-bar--low' : '' );
						?>
							<tr>
								<td><?php echo esc_html( $cc['category_name'] ); ?></td>
								<td class="fcc-an-td--center"><?php echo $cnt; ?></td>
								<td>
									<div class="fcc-an-bar-wrap">
										<div class="fcc-an-bar <?php echo esc_attr( $bar_cls ); ?>" style="width:<?php echo max( $pct, 2 ); ?>%"></div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

		</div>

	</div><!-- #fcc-an-panel-content -->

	<!-- ═══════════════════════════════════════════════════════════════════
	     TAB 5 — AUDIENCE
	     ═══════════════════════════════════════════════════════════════════ -->
	<div id="fcc-an-panel-audience" class="fcc-an-tab-panel<?php echo $active_tab === 'audience' ? ' fcc-an-tab-panel--active' : ''; ?>">

		<!-- Audience KPIs -->
		<div class="fcc-an-kpi-row" style="grid-template-columns:repeat(3,1fr);">
			<div class="fcc-an-kpi-card fcc-an-kpi-card--blue">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_subs ); ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Total Subscribers', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
			<div class="fcc-an-kpi-card fcc-an-kpi-card--green">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value" id="fcc-an-new-subs-month">—</span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'New This Month', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
			<div class="fcc-an-kpi-card fcc-an-kpi-card--amber">
				<div class="fcc-an-kpi-card__icon">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
				</div>
				<div>
					<span class="fcc-an-kpi-card__value"><?php echo $pipeline['pending']; ?></span>
					<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Pending Requests', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Subscriber Growth Chart -->
		<div class="fcc-card fcc-an-chart-card" style="max-width:800px">
			<div class="fcc-an-chart-card__header">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
				<strong><?php esc_html_e( 'Subscriber Growth', 'food-calorie-calculator' ); ?></strong>
				<span class="fcc-an-chart-card__sub"><?php esc_html_e( 'Last 12 months', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-an-chart-wrap">
				<canvas id="fcc-an-growth-chart"></canvas>
				<div class="fcc-an-spinner" id="fcc-an-growth-spinner"><span class="spinner is-active"></span></div>
			</div>
		</div>

		<!-- Recent Opt-ins + Export -->
		<div class="fcc-card fcc-an-section fcc-an-email-section">
			<div class="fcc-an-section__header">
				<div class="fcc-an-section__title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
					<strong><?php esc_html_e( 'Recent Opt-ins', 'food-calorie-calculator' ); ?></strong>
				</div>
				<span class="fcc-an-section__desc">
					<?php printf( esc_html__( '%d opted-in subscribers ready for your newsletter.', 'food-calorie-calculator' ), $kpi_subs ); ?>
				</span>
			</div>
			<div class="fcc-an-email-body">
				<div class="fcc-an-email-list">
					<?php if ( empty( $recent_optins ) ) : ?>
						<p class="fcc-an-empty"><?php esc_html_e( 'No opt-ins yet.', 'food-calorie-calculator' ); ?></p>
					<?php else : ?>
						<table class="fcc-an-table">
							<thead><tr><th><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?></th><th><?php esc_html_e( 'Requested Food', 'food-calorie-calculator' ); ?></th><th><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( $recent_optins as $sub ) : ?>
								<tr>
									<td><?php echo esc_html( $sub['requester_email'] ); ?></td>
									<td><?php echo esc_html( $sub['food_name'] ); ?></td>
									<td class="fcc-an-td--date"><?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $sub['created_at'] ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
						<div class="fcc-an-section__footer">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-email-hub' ) ); ?>" class="fcc-an-view-all"><?php esc_html_e( 'View all subscribers →', 'food-calorie-calculator' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
				<div class="fcc-an-email-export">
					<p class="fcc-an-email-export__desc"><?php esc_html_e( 'Export all opted-in subscribers to use in Mailchimp, ConvertKit, or any email platform.', 'food-calorie-calculator' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="fcc_export_subscribers">
						<?php wp_nonce_field( 'fcc_export_subscribers' ); ?>
						<button type="submit" class="fcc-reqs-btn fcc-reqs-btn--mark-added">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
							<?php esc_html_e( 'Export CSV', 'food-calorie-calculator' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>

	</div><!-- #fcc-an-panel-audience -->

</div><!-- .fcc-an-page -->
