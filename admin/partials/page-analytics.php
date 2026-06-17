<?php
/**
 * Analytics page — marketing intelligence dashboard.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

// Range selector.
$allowed_ranges = [ 7, 30, 90, 0 ];
$range          = absint( $_GET['range'] ?? 30 );
if ( ! in_array( $range, $allowed_ranges, true ) ) { $range = 30; }

$range_labels = [ 7 => 'Last 7 Days', 30 => 'Last 30 Days', 90 => 'Last 90 Days', 0 => 'All Time' ];

// KPI cards.
$kpi_searches    = \FCC\Database::count_total_searches( $range );
$kpi_rate        = \FCC\Database::get_search_success_rate( $range );
$kpi_gaps        = \FCC\Database::count_active_missed_searches();
$kpi_requests    = \FCC\Database::count_food_requests_grouped( [ 'status' => 'pending' ] );
$kpi_subscribers = \FCC\Database::count_opted_in_requests();
$kpi_sponsors    = \FCC\Database::count_active_sponsors();

// Tables.
$content_gaps  = \FCC\Database::get_top_content_gaps( 15 );
$top_requested = \FCC\Database::get_top_requested_foods( 10 );
$recent_optins = \FCC\Database::get_recent_optins( 5 );

$export_nonce    = wp_create_nonce( 'fcc_export_subscribers' );
$analytics_nonce = wp_create_nonce( 'fcc_analytics_nonce' );
?>
<div class="wrap fcc-admin-wrap fcc-an-page"
	data-nonce="<?php echo esc_attr( $analytics_nonce ); ?>"
	data-range="<?php echo esc_attr( $range ); ?>">

	<!-- Hero -->
	<div class="fcc-an-hero">
		<div class="fcc-an-hero__main">
			<div class="fcc-an-hero__icon">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
			</div>
			<div>
				<h1 class="fcc-an-hero__title"><?php esc_html_e( 'Analytics', 'food-calorie-calculator' ); ?></h1>
				<p class="fcc-an-hero__desc"><?php esc_html_e( 'Search trends, food popularity, content gaps, and email subscribers.', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>
		<div class="fcc-an-range-pills">
			<?php foreach ( $range_labels as $val => $label ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'range', $val ) ); ?>"
					class="fcc-reqs-pill<?php echo $range === $val ? ' fcc-reqs-pill--active' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- KPI Cards -->
	<div class="fcc-an-kpi-row">

		<div class="fcc-an-kpi-card fcc-an-kpi-card--green">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_searches ); ?></span>
				<span class="fcc-an-kpi-card__label"><?php echo esc_html( 'Total Searches' ); ?></span>
				<span class="fcc-an-kpi-card__sub"><?php echo esc_html( $range_labels[ $range ] ); ?></span>
			</div>
		</div>

		<div class="fcc-an-kpi-card fcc-an-kpi-card--blue">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo esc_html( $kpi_rate ); ?>%</span>
				<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Search Success Rate', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Searches finding a food', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-an-kpi-card fcc-an-kpi-card--amber">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_gaps ); ?></span>
				<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Content Gaps', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Active missed searches', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-an-kpi-card fcc-an-kpi-card--green">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_requests ); ?></span>
				<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Pending Requests', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Food add requests', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-an-kpi-card fcc-an-kpi-card--blue">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_subscribers ); ?></span>
				<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Email Subscribers', 'food-calorie-calculator' ); ?></span>
				<span class="fcc-an-kpi-card__sub"><?php esc_html_e( 'Newsletter opt-ins', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-an-kpi-card fcc-an-kpi-card--gold">
			<div class="fcc-an-kpi-card__icon">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
			</div>
			<div>
				<span class="fcc-an-kpi-card__value"><?php echo number_format( $kpi_sponsors ); ?></span>
				<span class="fcc-an-kpi-card__label"><?php esc_html_e( 'Active Sponsors', 'food-calorie-calculator' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-sponsored' ) ); ?>" class="fcc-an-kpi-card__sub" style="color:inherit;text-decoration:underline">
					<?php esc_html_e( 'Manage →', 'food-calorie-calculator' ); ?>
				</a>
			</div>
		</div>

	</div><!-- .fcc-an-kpi-row -->

	<!-- Charts Row -->
	<div class="fcc-an-charts-row">

		<div class="fcc-card fcc-an-chart-card">
			<div class="fcc-an-chart-card__header">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
				<strong><?php esc_html_e( 'Search Volume', 'food-calorie-calculator' ); ?></strong>
				<span class="fcc-an-chart-card__sub"><?php echo esc_html( $range_labels[ $range ] ); ?></span>
			</div>
			<div class="fcc-an-chart-wrap">
				<canvas id="fcc-an-volume-chart" aria-label="<?php esc_attr_e( 'Search volume chart', 'food-calorie-calculator' ); ?>"></canvas>
				<div class="fcc-an-spinner" id="fcc-an-volume-spinner">
					<span class="spinner is-active"></span>
				</div>
			</div>
		</div>

		<div class="fcc-card fcc-an-chart-card">
			<div class="fcc-an-chart-card__header">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
				<strong><?php esc_html_e( 'Top 10 Foods', 'food-calorie-calculator' ); ?></strong>
				<span class="fcc-an-chart-card__sub"><?php esc_html_e( 'By search hits (all time)', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-an-chart-wrap">
				<canvas id="fcc-an-foods-chart" aria-label="<?php esc_attr_e( 'Top foods chart', 'food-calorie-calculator' ); ?>"></canvas>
				<div class="fcc-an-spinner" id="fcc-an-foods-spinner">
					<span class="spinner is-active"></span>
				</div>
			</div>
		</div>

	</div><!-- .fcc-an-charts-row -->

	<!-- Two-column tables row -->
	<div class="fcc-an-tables-row">

		<!-- Content Gaps -->
		<div class="fcc-card fcc-an-section">
			<div class="fcc-an-section__header">
				<div class="fcc-an-section__title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
					<strong><?php esc_html_e( 'Content Gaps', 'food-calorie-calculator' ); ?></strong>
				</div>
				<span class="fcc-an-section__desc"><?php esc_html_e( 'Top 15 searches with no results — add these foods first.', 'food-calorie-calculator' ); ?></span>
			</div>
			<?php if ( empty( $content_gaps ) ) : ?>
				<p class="fcc-an-empty"><?php esc_html_e( 'No missed searches yet. Data appears once users search for foods not in the database.', 'food-calorie-calculator' ); ?></p>
			<?php else : ?>
				<table class="fcc-an-table">
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Query', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Searches', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Last Searched', 'food-calorie-calculator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $content_gaps as $i => $gap ) :
							$is_hot = (int) $gap['search_count'] >= 5; ?>
							<tr>
								<td class="fcc-an-td--num"><?php echo $i + 1; ?></td>
								<td>
									<?php echo esc_html( $gap['query'] ); ?>
									<?php if ( $is_hot ) : ?>
										<span class="fcc-reqs-hot">&#x1F525; <?php esc_html_e( 'Hot', 'food-calorie-calculator' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="fcc-an-td--center">
									<span class="fcc-reqs-count-badge"><?php echo (int) $gap['search_count']; ?>&times;</span>
								</td>
								<td class="fcc-an-td--date"><?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $gap['last_searched_at'] ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="fcc-an-section__footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-food-requests#missed' ) ); ?>" class="fcc-an-view-all">
						<?php esc_html_e( 'View all missed searches →', 'food-calorie-calculator' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

		<!-- Top Requested Foods -->
		<div class="fcc-card fcc-an-section">
			<div class="fcc-an-section__header">
				<div class="fcc-an-section__title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
					<strong><?php esc_html_e( 'Top Requested Foods', 'food-calorie-calculator' ); ?></strong>
				</div>
				<span class="fcc-an-section__desc"><?php esc_html_e( 'Most requested foods by users — add these to grow your database.', 'food-calorie-calculator' ); ?></span>
			</div>
			<?php if ( empty( $top_requested ) ) : ?>
				<p class="fcc-an-empty"><?php esc_html_e( 'No food requests yet.', 'food-calorie-calculator' ); ?></p>
			<?php else : ?>
				<table class="fcc-an-table">
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Requests', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Last Requested', 'food-calorie-calculator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $top_requested as $i => $food ) : ?>
							<tr>
								<td class="fcc-an-td--num"><?php echo $i + 1; ?></td>
								<td><?php echo esc_html( $food['food_name'] ); ?></td>
								<td class="fcc-an-td--center">
									<span class="fcc-reqs-count-badge"><?php echo (int) $food['request_count']; ?>&times;</span>
								</td>
								<td class="fcc-an-td--date"><?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $food['last_requested'] ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="fcc-an-section__footer">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-food-requests' ) ); ?>" class="fcc-an-view-all">
						<?php esc_html_e( 'View all requests →', 'food-calorie-calculator' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>

	</div><!-- .fcc-an-tables-row -->

	<!-- Email List Summary -->
	<div class="fcc-card fcc-an-section fcc-an-email-section">
		<div class="fcc-an-section__header">
			<div class="fcc-an-section__title">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
				<strong><?php esc_html_e( 'Email Subscribers', 'food-calorie-calculator' ); ?></strong>
			</div>
			<span class="fcc-an-section__desc">
				<?php printf(
					/* translators: %d number of subscribers */
					esc_html__( '%d opted-in subscribers ready for your newsletter or product launch.', 'food-calorie-calculator' ),
					$kpi_subscribers
				); ?>
			</span>
		</div>
		<div class="fcc-an-email-body">
			<div class="fcc-an-email-list">
				<?php if ( empty( $recent_optins ) ) : ?>
					<p class="fcc-an-empty"><?php esc_html_e( 'No opt-ins yet.', 'food-calorie-calculator' ); ?></p>
				<?php else : ?>
					<table class="fcc-an-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Email', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Requested Food', 'food-calorie-calculator' ); ?></th>
								<th><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th>
							</tr>
						</thead>
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
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-email-hub' ) ); ?>" class="fcc-an-view-all">
							<?php esc_html_e( 'View all subscribers →', 'food-calorie-calculator' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
			<div class="fcc-an-email-export">
				<p class="fcc-an-email-export__desc"><?php esc_html_e( 'Export all opted-in subscribers to use in Mailchimp, ConvertKit, or any email platform.', 'food-calorie-calculator' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="fcc_export_subscribers">
					<?php wp_nonce_field( 'fcc_export_subscribers' ); ?>
					<button type="submit" class="fcc-reqs-btn fcc-reqs-btn--mark-added">
						<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
						<?php esc_html_e( 'Export CSV', 'food-calorie-calculator' ); ?>
					</button>
				</form>
			</div>
		</div>
	</div>

</div><!-- .fcc-an-page -->
