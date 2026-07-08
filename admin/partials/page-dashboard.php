<?php
/**
 * Admin dashboard — data-driven command centre.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

// ── Data assembly ───────────────────────────────────────────────────────
global $wpdb;
$food_count   = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'fcc_foods' );
$cat_count    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'fcc_categories' );
$searches_30d = \FCC\Database::count_total_searches( 30 );
$success_rate = \FCC\Database::get_search_success_rate( 30 );
$subscribers  = \FCC\Database::count_opted_in_requests();
$sponsors     = \FCC\Database::count_active_sponsors();

// KPI cards with comparison.
$search_cmp = \FCC\Database::count_searches_with_comparison( 30 );
$rate_cmp   = \FCC\Database::get_success_rate_with_comparison( 30 );
$gaps_count = \FCC\Database::count_active_missed_searches();
$gaps_high  = \FCC\Database::count_high_priority_missed_searches( 5 );
$pending    = \FCC\Database::count_pending_requests();
$total_reqs = \FCC\Database::count_food_requests();

// Sparkline data.
$volume_30d = \FCC\Database::get_search_volume_by_day( 30 );

// Widget data.
$top_foods     = \FCC\Database::get_top_foods_by_hits( 8 );
$scored_gaps   = \FCC\Database::get_scored_content_gaps( 5 );
$top_requested = \FCC\Database::get_top_requested_foods( 3 );
$recent_optins = \FCC\Database::get_recent_optins( 3 );

// Revenue (conditional).
$wl_stats       = \FCC\Database::get_wl_stats();
$sponsor_clicks = \FCC\Database::get_total_sponsor_clicks( 30 );
$supp_catalog   = \FCC\Admin\Supplements::get_catalog_with_stats();
$supp_totals    = [ 'clicks' => 0, 'impressions' => 0 ];
foreach ( $supp_catalog as $s ) {
	$supp_totals['clicks']      += (int) ( $s['clicks'] ?? 0 );
	$supp_totals['impressions'] += (int) ( $s['impressions'] ?? 0 );
}
$supp_ctr  = $supp_totals['impressions'] > 0 ? round( $supp_totals['clicks'] / $supp_totals['impressions'] * 100, 1 ) : 0;
$aff_config  = \FCC\Admin\Affiliates::get_config();
$aff_enabled = 0;
if ( ! empty( $aff_config['retailers'] ) ) {
	foreach ( $aff_config['retailers'] as $r ) {
		if ( ! empty( $r['enabled'] ) ) { $aff_enabled++; }
	}
}
$has_revenue = $sponsors > 0 || $wl_stats['total'] > 0 || $supp_totals['clicks'] > 0 || $aff_enabled > 0;

// Helpers.
function fcc_dash_delta( float $current, float $previous ): string {
	if ( $previous <= 0 ) { return ''; }
	$pct = round( ( $current - $previous ) / $previous * 100, 1 );
	if ( abs( $pct ) < 0.1 ) { return '<span class="fcc-dash-delta fcc-dash-delta--flat">—</span>'; }
	$cls   = $pct > 0 ? 'fcc-dash-delta--up' : 'fcc-dash-delta--down';
	$arrow = $pct > 0 ? '↑' : '↓';
	return '<span class="fcc-dash-delta ' . $cls . '">' . $arrow . ' ' . abs( $pct ) . '%</span>';
}

function fcc_dash_sparkline( array $rows, string $color = '#2D7A4F' ): string {
	$values = array_map( 'intval', array_column( $rows, 'count' ) );
	if ( count( $values ) < 2 ) { return ''; }
	$max  = max( $values ) ?: 1;
	$w    = 80;
	$h    = 24;
	$step = $w / ( count( $values ) - 1 );
	$pts  = [];
	foreach ( $values as $i => $v ) {
		$x    = round( $i * $step, 1 );
		$y    = round( $h - ( $v / $max ) * ( $h - 2 ) - 1, 1 );
		$pts[] = "$x,$y";
	}
	return '<svg width="' . $w . '" height="' . $h . '" class="fcc-dash-sparkline" aria-hidden="true">'
		. '<polyline points="' . implode( ' ', $pts ) . '" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
		. '</svg>';
}

$shortcode = '[food_calorie_calculator]';
?>
<div class="wrap fcc-admin-wrap fcc-dashboard-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Calorie Calculator Dashboard', 'food-calorie-calculator' ); ?></h1>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ═══ HERO ═══ -->
	<div class="fcc-dash-hero">
		<div class="fcc-dash-hero__inner">
			<div class="fcc-dash-hero__content">
				<div class="fcc-dash-hero__icon" aria-hidden="true"><img src="<?php echo esc_url( FCC_PLUGIN_URL . 'logo/Food Calorie Calculator Favicon - White (1).png' ); ?>" width="48" height="48" alt="" decoding="async" style="display:block;width:48px;height:48px;object-fit:contain;"></div>
				<div>
					<div class="fcc-dash-hero__title">
						<?php esc_html_e( 'Food Calorie Calculator', 'food-calorie-calculator' ); ?>
						<span class="fcc-dash-version">v<?php echo esc_html( FCC_VERSION ); ?></span>
					</div>
					<p class="fcc-dash-hero__sub"><?php esc_html_e( 'UK nutrition calculator with FSA traffic lights, meal builder, macros, BMR/TDEE, and full CSV/Excel import-export.', 'food-calorie-calculator' ); ?></p>
				</div>
			</div>
			<div class="fcc-dash-hero__stats">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo number_format_i18n( $food_count ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-categories' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo number_format_i18n( $cat_count ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-analytics' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo number_format_i18n( $searches_30d ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Searches (30d)', 'food-calorie-calculator' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-analytics&tab=search' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo esc_html( $success_rate ); ?>%</span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Success Rate', 'food-calorie-calculator' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-email-hub' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo number_format_i18n( $subscribers ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Subscribers', 'food-calorie-calculator' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-sponsored' ) ); ?>" class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo number_format_i18n( $sponsors ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Sponsors', 'food-calorie-calculator' ); ?></span>
				</a>
			</div>
		</div>
	</div>

	<!-- ═══ KPI CARDS ═══ -->
	<div class="fcc-dash-kpi-row">

		<div class="fcc-dash-kpi fcc-dash-kpi--green">
			<div class="fcc-dash-kpi__top">
				<span class="fcc-dash-kpi__val"><?php echo number_format_i18n( $search_cmp['current'] ); ?></span>
				<?php echo fcc_dash_delta( $search_cmp['current'], $search_cmp['previous'] ); ?>
			</div>
			<span class="fcc-dash-kpi__label"><?php esc_html_e( 'Searches (30 days)', 'food-calorie-calculator' ); ?></span>
			<?php echo fcc_dash_sparkline( $volume_30d ); ?>
		</div>

		<?php
		$rate_cls = $rate_cmp['current'] >= 80 ? 'fcc-dash-kpi--green' : ( $rate_cmp['current'] >= 50 ? 'fcc-dash-kpi--amber' : 'fcc-dash-kpi--red' );
		?>
		<div class="fcc-dash-kpi <?php echo esc_attr( $rate_cls ); ?>">
			<div class="fcc-dash-kpi__top">
				<span class="fcc-dash-kpi__val"><?php echo esc_html( $rate_cmp['current'] ); ?>%</span>
				<?php echo fcc_dash_delta( $rate_cmp['current'], $rate_cmp['previous'] ); ?>
			</div>
			<span class="fcc-dash-kpi__label"><?php esc_html_e( 'Search Success Rate', 'food-calorie-calculator' ); ?></span>
		</div>

		<div class="fcc-dash-kpi fcc-dash-kpi--amber">
			<div class="fcc-dash-kpi__top">
				<span class="fcc-dash-kpi__val"><?php echo number_format_i18n( $gaps_count ); ?></span>
				<?php if ( $gaps_high > 0 ) : ?>
					<span class="fcc-dash-kpi__badge"><?php echo $gaps_high; ?> high priority</span>
				<?php endif; ?>
			</div>
			<span class="fcc-dash-kpi__label"><?php esc_html_e( 'Content Gaps', 'food-calorie-calculator' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-analytics&tab=content' ) ); ?>" class="fcc-dash-kpi__link"><?php esc_html_e( 'View gaps →', 'food-calorie-calculator' ); ?></a>
		</div>

		<div class="fcc-dash-kpi fcc-dash-kpi--blue">
			<div class="fcc-dash-kpi__top">
				<span class="fcc-dash-kpi__val"><?php echo number_format_i18n( $pending ); ?></span>
				<span class="fcc-dash-kpi__sub"><?php printf( esc_html__( 'of %s total', 'food-calorie-calculator' ), number_format_i18n( $total_reqs ) ); ?></span>
			</div>
			<span class="fcc-dash-kpi__label"><?php esc_html_e( 'Pending Requests', 'food-calorie-calculator' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-food-requests' ) ); ?>" class="fcc-dash-kpi__link"><?php esc_html_e( 'Review →', 'food-calorie-calculator' ); ?></a>
		</div>

	</div>

	<!-- ═══ MAIN WIDGETS (3-col) ═══ -->
	<div class="fcc-dash-widgets">

		<!-- Top Foods -->
		<div class="fcc-dash-widget">
			<div class="fcc-dash-widget__hd">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
				<strong><?php esc_html_e( 'Top Foods', 'food-calorie-calculator' ); ?></strong>
				<span class="fcc-dash-widget__sub"><?php esc_html_e( 'All time', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-widget__body">
				<?php if ( empty( $top_foods ) ) : ?>
					<p class="fcc-dash-empty"><?php esc_html_e( 'No search data yet.', 'food-calorie-calculator' ); ?></p>
				<?php else : ?>
					<table class="fcc-dash-table">
						<tbody>
						<?php foreach ( $top_foods as $i => $f ) : ?>
							<tr>
								<td class="fcc-dash-table__rank"><span class="fcc-dash-rank"><?php echo $i + 1; ?></span></td>
								<td class="fcc-dash-table__name"><?php echo esc_html( $f['name'] ); ?></td>
								<td class="fcc-dash-table__count"><span class="fcc-dash-count-badge"><?php echo number_format_i18n( $f['search_count'] ); ?></span></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<div class="fcc-dash-widget__footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-analytics' ) ); ?>"><?php esc_html_e( 'View Analytics →', 'food-calorie-calculator' ); ?></a>
			</div>
		</div>

		<!-- Content Gaps & Requests -->
		<div class="fcc-dash-widget">
			<div class="fcc-dash-widget__hd">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e67e22" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				<strong><?php esc_html_e( 'Content Gaps', 'food-calorie-calculator' ); ?></strong>
			</div>
			<div class="fcc-dash-widget__body">
				<?php if ( empty( $scored_gaps ) ) : ?>
					<p class="fcc-dash-empty"><?php esc_html_e( 'No missed searches yet.', 'food-calorie-calculator' ); ?></p>
				<?php else : ?>
					<table class="fcc-dash-table">
						<tbody>
						<?php foreach ( $scored_gaps as $g ) :
							$score = (float) $g['priority_score'];
							$s_cls = $score >= 15 ? 'fcc-dash-score--crit' : ( $score >= 8 ? 'fcc-dash-score--high' : 'fcc-dash-score--med' );
						?>
							<tr>
								<td class="fcc-dash-table__name"><?php echo esc_html( $g['query'] ); ?></td>
								<td class="fcc-dash-table__count"><span class="fcc-dash-count-badge"><?php echo (int) $g['search_count']; ?>×</span></td>
								<td class="fcc-dash-table__score"><span class="fcc-dash-score <?php echo esc_attr( $s_cls ); ?>"><?php echo (int) $score; ?></span></td>
								<td class="fcc-dash-table__act">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&food_name=' . urlencode( $g['query'] ) ) ); ?>" class="fcc-dash-quick-add" title="<?php esc_attr_e( 'Add this food', 'food-calorie-calculator' ); ?>">+ Add</a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( ! empty( $top_requested ) ) : ?>
					<div class="fcc-dash-widget__divider"></div>
					<p class="fcc-dash-widget__sublabel"><?php esc_html_e( 'Most Requested', 'food-calorie-calculator' ); ?></p>
					<?php foreach ( $top_requested as $r ) : ?>
						<div class="fcc-dash-req-row">
							<span><?php echo esc_html( $r['food_name'] ); ?></span>
							<span class="fcc-dash-count-badge"><?php echo (int) $r['request_count']; ?>×</span>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<div class="fcc-dash-widget__footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-content-planner' ) ); ?>"><?php esc_html_e( 'Content Planner →', 'food-calorie-calculator' ); ?></a>
			</div>
		</div>

		<!-- Recent Activity -->
		<div class="fcc-dash-widget">
			<div class="fcc-dash-widget__hd">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3498db" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<strong><?php esc_html_e( 'Recent Activity', 'food-calorie-calculator' ); ?></strong>
			</div>
			<div class="fcc-dash-widget__body">
				<?php if ( ! empty( $recent_optins ) ) : ?>
					<p class="fcc-dash-widget__sublabel"><?php esc_html_e( 'Email Opt-ins', 'food-calorie-calculator' ); ?></p>
					<div class="fcc-dash-timeline">
						<?php foreach ( $recent_optins as $o ) : ?>
							<div class="fcc-dash-timeline__item">
								<span class="fcc-dash-timeline__dot"></span>
								<div class="fcc-dash-timeline__content">
									<span class="fcc-dash-timeline__email"><?php echo esc_html( $o['requester_email'] ); ?></span>
									<span class="fcc-dash-timeline__food"><?php echo esc_html( $o['food_name'] ); ?></span>
									<span class="fcc-dash-timeline__date"><?php echo esc_html( date_i18n( 'M j', strtotime( $o['created_at'] ) ) ); ?></span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $wl_stats['expiring_30d'] > 0 ) : ?>
					<div class="fcc-dash-alert fcc-dash-alert--warn">
						⚠ <?php printf( esc_html__( '%d WL license(s) expiring within 30 days', 'food-calorie-calculator' ), $wl_stats['expiring_30d'] ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-white-label' ) ); ?>"><?php esc_html_e( 'View →', 'food-calorie-calculator' ); ?></a>
					</div>
				<?php endif; ?>

				<?php if ( $pending > 0 ) : ?>
					<div class="fcc-dash-alert fcc-dash-alert--info">
						📩 <?php printf( esc_html__( '%d food request(s) awaiting review', 'food-calorie-calculator' ), $pending ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-food-requests' ) ); ?>"><?php esc_html_e( 'Review →', 'food-calorie-calculator' ); ?></a>
					</div>
				<?php endif; ?>

				<?php if ( empty( $recent_optins ) && $wl_stats['expiring_30d'] <= 0 && $pending <= 0 ) : ?>
					<p class="fcc-dash-empty"><?php esc_html_e( 'No recent activity.', 'food-calorie-calculator' ); ?></p>
				<?php endif; ?>
			</div>
			<div class="fcc-dash-widget__footer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-email-hub' ) ); ?>"><?php esc_html_e( 'Email Hub →', 'food-calorie-calculator' ); ?></a>
			</div>
		</div>

	</div>

	<?php if ( $has_revenue ) : ?>
	<!-- ═══ REVENUE ROW ═══ -->
	<div class="fcc-dash-revenue-row">

		<div class="fcc-dash-rev-card fcc-dash-rev-card--gold">
			<span class="fcc-dash-rev-card__icon">⭐</span>
			<div>
				<span class="fcc-dash-rev-card__val"><?php echo $sponsors; ?> <?php esc_html_e( 'active', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__label"><?php esc_html_e( 'Sponsors', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__sub"><?php echo number_format_i18n( $sponsor_clicks['current'] ); ?> <?php esc_html_e( 'clicks (30d)', 'food-calorie-calculator' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-sponsored' ) ); ?>" class="fcc-dash-rev-card__link">→</a>
		</div>

		<div class="fcc-dash-rev-card fcc-dash-rev-card--teal">
			<span class="fcc-dash-rev-card__icon">🏷️</span>
			<div>
				<span class="fcc-dash-rev-card__val"><?php echo $wl_stats['active']; ?> <?php esc_html_e( 'licenses', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__label"><?php esc_html_e( 'White Label', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__sub">£<?php echo number_format( $wl_stats['mrr'], 0 ); ?> MRR</span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-white-label' ) ); ?>" class="fcc-dash-rev-card__link">→</a>
		</div>

		<div class="fcc-dash-rev-card fcc-dash-rev-card--purple">
			<span class="fcc-dash-rev-card__icon">💊</span>
			<div>
				<span class="fcc-dash-rev-card__val"><?php echo number_format_i18n( $supp_totals['clicks'] ); ?> <?php esc_html_e( 'clicks', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__label"><?php esc_html_e( 'Supplements', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__sub"><?php echo esc_html( $supp_ctr ); ?>% CTR</span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-supplements' ) ); ?>" class="fcc-dash-rev-card__link">→</a>
		</div>

		<div class="fcc-dash-rev-card fcc-dash-rev-card--green">
			<span class="fcc-dash-rev-card__icon">🔗</span>
			<div>
				<span class="fcc-dash-rev-card__val"><?php echo $aff_enabled; ?> <?php esc_html_e( 'enabled', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-dash-rev-card__label"><?php esc_html_e( 'Affiliates', 'food-calorie-calculator' ); ?></span>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-affiliates' ) ); ?>" class="fcc-dash-rev-card__link">→</a>
		</div>

	</div>
	<?php endif; ?>

	<!-- ═══ BOTTOM ROW: Quick Actions + Embed ═══ -->
	<div class="fcc-dash-bottom-row">

		<!-- Quick Actions -->
		<div class="fcc-dash-card">
			<div class="fcc-dash-card__hd fcc-dash-card__hd--green">
				<span class="fcc-dash-card__hicon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
				</span>
				<span class="fcc-dash-card__htitle"><?php esc_html_e( 'Quick Actions', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-card__body">
				<div class="fcc-dash-actions-grid fcc-dash-actions-grid--6">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&action=add' ) ); ?>" class="fcc-dash-action fcc-dash-action--green">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Add Food', 'food-calorie-calculator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-import-export' ) ); ?>" class="fcc-dash-action fcc-dash-action--blue">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Import', 'food-calorie-calculator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-categories' ) ); ?>" class="fcc-dash-action fcc-dash-action--purple">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-settings' ) ); ?>" class="fcc-dash-action fcc-dash-action--slate">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Settings', 'food-calorie-calculator' ); ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-food-requests' ) ); ?>" class="fcc-dash-action fcc-dash-action--amber">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Requests', 'food-calorie-calculator' ); ?><?php if ( $pending > 0 ) : ?> <span class="fcc-dash-action__badge"><?php echo $pending; ?></span><?php endif; ?></span>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-analytics' ) ); ?>" class="fcc-dash-action fcc-dash-action--blue">
						<span class="fcc-dash-action__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Analytics', 'food-calorie-calculator' ); ?></span>
					</a>
				</div>
			</div>
		</div>

		<!-- Embed + System Info -->
		<div class="fcc-dash-card">
			<div class="fcc-dash-card__hd fcc-dash-card__hd--blue">
				<span class="fcc-dash-card__hicon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
				</span>
				<span class="fcc-dash-card__htitle"><?php esc_html_e( 'Embed & Info', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-card__body">
				<p class="fcc-dash-card__desc"><?php esc_html_e( 'Paste this shortcode into any page or post, or use the Gutenberg block.', 'food-calorie-calculator' ); ?></p>
				<div class="fcc-dash-code-box">
					<code class="fcc-dash-code-box__code" id="fcc-shortcode-text"><?php echo esc_html( $shortcode ); ?></code>
					<button type="button" class="fcc-dash-code-box__copy" id="fcc-copy-btn"
						data-clipboard="<?php echo esc_attr( $shortcode ); ?>"
						aria-label="<?php esc_attr_e( 'Copy shortcode', 'food-calorie-calculator' ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
						<span id="fcc-copy-label"><?php esc_html_e( 'Copy', 'food-calorie-calculator' ); ?></span>
					</button>
				</div>

				<div class="fcc-dash-sysinfo">
					<span>WP <?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
					<span>PHP <?php echo esc_html( PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ); ?></span>
					<span>DB <?php echo esc_html( FCC_DB_VERSION ); ?></span>
					<span>Plugin <?php echo esc_html( FCC_VERSION ); ?></span>
				</div>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="fcc-dash-mini-link"><?php esc_html_e( 'View Foods →', 'food-calorie-calculator' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-settings' ) ); ?>" class="fcc-dash-mini-link"><?php esc_html_e( 'Plugin Settings →', 'food-calorie-calculator' ); ?></a>
			</div>
		</div>

	</div>

</div><!-- .wrap -->

<script>
( function () {
	'use strict';
	var btn   = document.getElementById( 'fcc-copy-btn' );
	var label = document.getElementById( 'fcc-copy-label' );
	if ( ! btn || ! label ) return;

	btn.addEventListener( 'click', function () {
		var text = btn.dataset.clipboard || '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( flash );
		} else {
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.style.cssText = 'position:fixed;opacity:0';
			document.body.appendChild( ta );
			ta.select();
			document.execCommand( 'copy' );
			document.body.removeChild( ta );
			flash();
		}
	} );

	function flash() {
		btn.classList.add( 'fcc-dash-code-box__copy--copied' );
		label.textContent = '<?php echo esc_js( __( 'Copied!', 'food-calorie-calculator' ) ); ?>';
		setTimeout( function () {
			btn.classList.remove( 'fcc-dash-code-box__copy--copied' );
			label.textContent = '<?php echo esc_js( __( 'Copy', 'food-calorie-calculator' ) ); ?>';
		}, 2000 );
	}
} )();
</script>
