<?php
/**
 * Admin page: Food Requests + Missed Searches.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$per_page = 20;

// ---- Hero stats ----
$stat_pending  = \FCC\Database::count_food_requests( [ 'status' => 'pending' ] );
$stat_active_ms = \FCC\Database::count_active_missed_searches();
$stat_hot      = \FCC\Database::count_high_priority_missed_searches( 5 );

// ---- User Requests: initial load ----
$reqs_status   = '';
$reqs_sort     = 'most_requested';
$reqs_period   = 0;
$reqs_paged    = 1;
$reqs_args     = [ 'status' => $reqs_status, 'sort' => $reqs_sort, 'days' => $reqs_period, 'per_page' => $per_page, 'page' => $reqs_paged ];
$reqs_total    = \FCC\Database::count_food_requests_grouped( $reqs_args );
$reqs_pages    = (int) ceil( $reqs_total / $per_page );
$requests      = \FCC\Database::get_food_requests_grouped( $reqs_args );

// Tab counts for requests.
$reqs_count_all       = \FCC\Database::count_food_requests_grouped( [] );
$reqs_count_pending   = \FCC\Database::count_food_requests_grouped( [ 'status' => 'pending' ] );
$reqs_count_done      = \FCC\Database::count_food_requests_grouped( [ 'status' => 'done' ] );
$reqs_count_dismissed = \FCC\Database::count_food_requests_grouped( [ 'status' => 'dismissed' ] );

// ---- Missed Searches: initial load ----
$ms_status   = 'active';
$ms_sort     = 'most_searched';
$ms_period   = 0;
$ms_paged    = 1;
$ms_args     = [ 'status' => $ms_status, 'sort' => $ms_sort, 'days' => $ms_period, 'per_page' => $per_page, 'page' => $ms_paged ];
$ms_total    = \FCC\Database::count_missed_searches( $ms_args );
$ms_pages    = (int) ceil( $ms_total / $per_page );
$searches    = \FCC\Database::get_missed_searches( $ms_args );

// Tab counts for missed searches.
$ms_count_all       = \FCC\Database::count_missed_searches( [] );
$ms_count_active    = \FCC\Database::count_missed_searches( [ 'status' => 'active' ] );
$ms_count_done      = \FCC\Database::count_missed_searches( [ 'status' => 'done' ] );
$ms_count_dismissed = \FCC\Database::count_missed_searches( [ 'status' => 'dismissed' ] );

$reqs_nonce = wp_create_nonce( 'fcc_ajax_reqs' );
$ms_nonce   = wp_create_nonce( 'fcc_ajax_ms' );
?>
<div class="wrap fcc-admin-wrap fcc-reqs-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Requests', 'food-calorie-calculator' ); ?></h1>

	<!-- =====================================================================
	     Dark hero
	     ===================================================================== -->
	<div class="fcc-reqs-hero">
		<div class="fcc-reqs-hero__main">
			<div class="fcc-reqs-hero__icon" aria-hidden="true">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
			</div>
			<div>
				<h2 class="fcc-reqs-hero__title"><?php esc_html_e( 'Food Requests', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-reqs-hero__desc">
					<?php esc_html_e( 'User-submitted requests & auto-logged missed searches.', 'food-calorie-calculator' ); ?><br>
					<?php esc_html_e( 'Use these insights to decide what food to add next.', 'food-calorie-calculator' ); ?>
				</p>
			</div>
		</div>
		<div class="fcc-reqs-hero__tip">
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			<span>
				<?php esc_html_e( 'Tip:', 'food-calorie-calculator' ); ?>
				<?php esc_html_e( 'Items searched 5+ times are flagged', 'food-calorie-calculator' ); ?>
				<span class="fcc-reqs-hot">&#x1F525; <?php esc_html_e( 'Hot', 'food-calorie-calculator' ); ?></span>
				— <?php esc_html_e( 'these are your highest-priority additions.', 'food-calorie-calculator' ); ?>
			</span>
		</div>
	</div><!-- .fcc-reqs-hero -->

	<!-- =====================================================================
	     3 stat cards
	     ===================================================================== -->
	<div class="fcc-reqs-stat-grid">

		<div class="fcc-reqs-stat-card">
			<div class="fcc-reqs-stat-card__icon fcc-reqs-stat-card__icon--orange" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			</div>
			<div>
				<strong class="fcc-reqs-stat-card__value"><?php echo (int) $stat_pending; ?></strong>
				<span class="fcc-reqs-stat-card__label"><?php esc_html_e( 'Pending User Requests', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-reqs-stat-card">
			<div class="fcc-reqs-stat-card__icon fcc-reqs-stat-card__icon--blue" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			</div>
			<div>
				<strong class="fcc-reqs-stat-card__value"><?php echo (int) $stat_active_ms; ?></strong>
				<span class="fcc-reqs-stat-card__label"><?php esc_html_e( 'Active Missed Searches', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

		<div class="fcc-reqs-stat-card<?php echo $stat_hot > 0 ? ' fcc-reqs-stat-card--alert' : ''; ?>">
			<div class="fcc-reqs-stat-card__icon fcc-reqs-stat-card__icon--red" aria-hidden="true">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
			</div>
			<div>
				<strong class="fcc-reqs-stat-card__value"><?php echo (int) $stat_hot; ?></strong>
				<span class="fcc-reqs-stat-card__label"><?php esc_html_e( 'High Priority (5+ searches)', 'food-calorie-calculator' ); ?></span>
			</div>
		</div>

	</div><!-- .fcc-reqs-stat-grid -->

	<!-- =====================================================================
	     User Requests — unified card
	     ===================================================================== -->
	<div class="fcc-card fcc-reqs-unified fcc-reqs-section" id="fcc-reqs-section">

		<div class="fcc-reqs-unified__header">
			<div class="fcc-reqs-section-title">
				<svg class="fcc-reqs-section-title__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				<div>
					<strong><?php esc_html_e( 'User Requests', 'food-calorie-calculator' ); ?></strong>
					<small><?php esc_html_e( 'Submitted by visitors when they can\'t find a food in the calculator', 'food-calorie-calculator' ); ?></small>
				</div>
			</div>
			<div class="fcc-reqs-unified__tabs" data-region="reqs">
				<?php
				$req_tabs = [
					''          => [ 'label' => __( 'All', 'food-calorie-calculator' ),       'count' => $reqs_count_all ],
					'pending'   => [ 'label' => __( 'Pending', 'food-calorie-calculator' ),   'count' => $reqs_count_pending ],
					'done'      => [ 'label' => __( 'Added', 'food-calorie-calculator' ),     'count' => $reqs_count_done ],
					'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ), 'count' => $reqs_count_dismissed ],
				];
				foreach ( $req_tabs as $key => $tab ) :
					$active = $reqs_status === $key ? ' fcc-reqs-tab--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-tab fcc-reqs-tab-btn<?php echo esc_attr( $active ); ?>"
						data-status="<?php echo esc_attr( $key ); ?>"
						data-region="reqs">
						<?php echo esc_html( $tab['label'] ); ?>
						<?php if ( $tab['count'] > 0 ) : ?>
							<span class="fcc-reqs-tab__count"><?php echo (int) $tab['count']; ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div><!-- .fcc-reqs-unified__header -->

		<!-- Sort + Period bar -->
		<div class="fcc-reqs-sortbar" data-region="reqs">
			<div class="fcc-reqs-sortbar__group">
				<span class="fcc-reqs-sortbar__label"><?php esc_html_e( 'SORT:', 'food-calorie-calculator' ); ?></span>
				<?php
				$req_sorts = [
					'most_requested' => __( 'Most Requested', 'food-calorie-calculator' ),
					'latest'         => __( 'Latest', 'food-calorie-calculator' ),
					'oldest'         => __( 'Oldest', 'food-calorie-calculator' ),
				];
				foreach ( $req_sorts as $key => $label ) :
					$active = $reqs_sort === $key ? ' fcc-reqs-pill--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-pill fcc-reqs-sort-btn<?php echo esc_attr( $active ); ?>"
						data-sort="<?php echo esc_attr( $key ); ?>"
						data-region="reqs">
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="fcc-reqs-sortbar__group">
				<span class="fcc-reqs-sortbar__label"><?php esc_html_e( 'PERIOD:', 'food-calorie-calculator' ); ?></span>
				<?php
				$req_periods = [
					0  => __( 'All Time', 'food-calorie-calculator' ),
					7  => __( 'Last 7 Days', 'food-calorie-calculator' ),
					30 => __( 'Last 30 Days', 'food-calorie-calculator' ),
					-1 => __( 'Custom', 'food-calorie-calculator' ),
				];
				foreach ( $req_periods as $val => $label ) :
					$active = $reqs_period === $val ? ' fcc-reqs-pill--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-pill fcc-reqs-period-btn<?php echo esc_attr( $active ); ?>"
						data-period="<?php echo esc_attr( $val ); ?>"
						data-region="reqs">
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div><!-- .fcc-reqs-sortbar -->

		<!-- Custom date range for requests (hidden by default) -->
		<div class="fcc-reqs-daterange" id="fcc-reqs-daterange" hidden>
			<label class="fcc-reqs-daterange__label">
				<?php esc_html_e( 'From', 'food-calorie-calculator' ); ?>
				<input type="date" class="fcc-reqs-date-input" data-field="date_from" data-region="reqs" value="">
			</label>
			<span class="fcc-reqs-daterange__sep" aria-hidden="true">→</span>
			<label class="fcc-reqs-daterange__label">
				<?php esc_html_e( 'To', 'food-calorie-calculator' ); ?>
				<input type="date" class="fcc-reqs-date-input" data-field="date_to" data-region="reqs" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
			</label>
		</div>

		<!-- AJAX region -->
		<div id="fcc-reqs-list"
			data-nonce="<?php echo esc_attr( $reqs_nonce ); ?>"
			data-status="<?php echo esc_attr( $reqs_status ); ?>"
			data-sort="<?php echo esc_attr( $reqs_sort ); ?>"
			data-period="<?php echo (int) $reqs_period; ?>"
			data-date-from=""
			data-date-to=""
			data-paged="<?php echo (int) $reqs_paged; ?>">
			<?php
			$paged       = $reqs_paged;
			$total       = $reqs_total;
			$total_pages = $reqs_pages;
			include FCC_PLUGIN_DIR . 'admin/partials/page-food-requests-table.php';
			?>
		</div><!-- #fcc-reqs-list -->

	</div><!-- .fcc-reqs-unified -->

	<!-- =====================================================================
	     Missed Searches — unified card
	     ===================================================================== -->
	<div class="fcc-card fcc-reqs-unified fcc-ms-section" id="fcc-ms-section">

		<div class="fcc-reqs-unified__header">
			<div class="fcc-reqs-section-title">
				<svg class="fcc-reqs-section-title__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				<div>
					<strong><?php esc_html_e( 'Missed Searches', 'food-calorie-calculator' ); ?></strong>
					<span class="fcc-reqs-badge fcc-reqs-badge--auto"><?php esc_html_e( 'Auto-logged', 'food-calorie-calculator' ); ?></span>
					<small><?php esc_html_e( 'Search queries that returned 0 results — sorted by frequency to highlight your highest-demand gaps', 'food-calorie-calculator' ); ?></small>
				</div>
			</div>
			<div class="fcc-reqs-unified__tabs" data-region="ms">
				<?php
				$ms_tabs = [
					''          => [ 'label' => __( 'All', 'food-calorie-calculator' ),        'count' => $ms_count_all ],
					'active'    => [ 'label' => __( 'Active', 'food-calorie-calculator' ),     'count' => $ms_count_active ],
					'done'      => [ 'label' => __( 'Added', 'food-calorie-calculator' ),      'count' => $ms_count_done ],
					'dismissed' => [ 'label' => __( 'Dismissed', 'food-calorie-calculator' ),  'count' => $ms_count_dismissed ],
				];
				foreach ( $ms_tabs as $key => $tab ) :
					$active_cls = $ms_status === $key ? ' fcc-reqs-tab--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-tab fcc-reqs-tab-btn<?php echo esc_attr( $active_cls ); ?>"
						data-status="<?php echo esc_attr( $key ); ?>"
						data-region="ms">
						<?php echo esc_html( $tab['label'] ); ?>
						<?php if ( $tab['count'] > 0 ) : ?>
							<span class="fcc-reqs-tab__count"><?php echo (int) $tab['count']; ?></span>
						<?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div><!-- .fcc-reqs-unified__header -->

		<!-- Sort + Period bar -->
		<div class="fcc-reqs-sortbar" data-region="ms">
			<div class="fcc-reqs-sortbar__group">
				<span class="fcc-reqs-sortbar__label"><?php esc_html_e( 'SORT:', 'food-calorie-calculator' ); ?></span>
				<?php
				$ms_sorts = [
					'most_searched' => __( 'Most Searched', 'food-calorie-calculator' ),
					'latest'        => __( 'Latest', 'food-calorie-calculator' ),
					'oldest'        => __( 'Oldest', 'food-calorie-calculator' ),
				];
				foreach ( $ms_sorts as $key => $label ) :
					$active = $ms_sort === $key ? ' fcc-reqs-pill--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-pill fcc-reqs-sort-btn<?php echo esc_attr( $active ); ?>"
						data-sort="<?php echo esc_attr( $key ); ?>"
						data-region="ms">
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</div>

			<div class="fcc-reqs-sortbar__group">
				<span class="fcc-reqs-sortbar__label"><?php esc_html_e( 'PERIOD:', 'food-calorie-calculator' ); ?></span>
				<?php
				$ms_periods = [
					0  => __( 'All Time', 'food-calorie-calculator' ),
					7  => __( 'Last 7 Days', 'food-calorie-calculator' ),
					30 => __( 'Last 30 Days', 'food-calorie-calculator' ),
					-1 => __( 'Custom', 'food-calorie-calculator' ),
				];
				foreach ( $ms_periods as $val => $label ) :
					$active = $ms_period === $val ? ' fcc-reqs-pill--active' : '';
				?>
					<button type="button"
						class="fcc-reqs-pill fcc-reqs-period-btn<?php echo esc_attr( $active ); ?>"
						data-period="<?php echo esc_attr( $val ); ?>"
						data-region="ms">
						<?php echo esc_html( $label ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div><!-- .fcc-reqs-sortbar -->

		<!-- Custom date range for missed searches (hidden by default) -->
		<div class="fcc-reqs-daterange" id="fcc-ms-daterange" hidden>
			<label class="fcc-reqs-daterange__label">
				<?php esc_html_e( 'From', 'food-calorie-calculator' ); ?>
				<input type="date" class="fcc-reqs-date-input" data-field="date_from" data-region="ms" value="">
			</label>
			<span class="fcc-reqs-daterange__sep" aria-hidden="true">→</span>
			<label class="fcc-reqs-daterange__label">
				<?php esc_html_e( 'To', 'food-calorie-calculator' ); ?>
				<input type="date" class="fcc-reqs-date-input" data-field="date_to" data-region="ms" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
			</label>
		</div>

		<!-- AJAX region -->
		<div id="fcc-ms-list"
			data-nonce="<?php echo esc_attr( $ms_nonce ); ?>"
			data-status="<?php echo esc_attr( $ms_status ); ?>"
			data-sort="<?php echo esc_attr( $ms_sort ); ?>"
			data-period="<?php echo (int) $ms_period; ?>"
			data-date-from=""
			data-date-to=""
			data-paged="<?php echo (int) $ms_paged; ?>">
			<?php
			$paged       = $ms_paged;
			$total       = $ms_total;
			$total_pages = $ms_pages;
			include FCC_PLUGIN_DIR . 'admin/partials/page-missed-searches-table.php';
			?>
		</div><!-- #fcc-ms-list -->

	</div><!-- .fcc-ms-section -->

	<!-- =====================================================================
	     Export panel
	     ===================================================================== -->
	<div class="fcc-card fcc-reqs-export">

		<div class="fcc-reqs-export__header">
			<div class="fcc-reqs-export__header-icon" aria-hidden="true">📤</div>
			<div>
				<div class="fcc-reqs-export__title"><?php esc_html_e( 'Export Emails', 'food-calorie-calculator' ); ?></div>
				<p class="fcc-reqs-export__desc"><?php esc_html_e( 'Download contacts collected through food requests for your email campaigns.', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fcc-reqs-export__form">
			<input type="hidden" name="action" value="fcc_export_requests">
			<?php wp_nonce_field( 'fcc_export_requests' ); ?>

			<div class="fcc-reqs-export__filter-bar">
				<div class="fcc-reqs-export__filter-group">
					<span class="fcc-reqs-export__filter-label"><?php esc_html_e( 'Contacts', 'food-calorie-calculator' ); ?></span>
					<label class="fcc-reqs-export__optin-label">
						<input type="checkbox" name="optin_only" value="1" checked>
						<span><?php esc_html_e( 'Newsletter opt-in only', 'food-calorie-calculator' ); ?></span>
					</label>
				</div>
				<div class="fcc-reqs-export__filter-group">
					<label class="fcc-reqs-export__filter-label" for="fcc-reqs-export-status"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></label>
					<select name="req_status" id="fcc-reqs-export-status" class="fcc-reqs-export__select">
						<option value=""><?php esc_html_e( 'All statuses', 'food-calorie-calculator' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending', 'food-calorie-calculator' ); ?></option>
						<option value="done"><?php esc_html_e( 'Added', 'food-calorie-calculator' ); ?></option>
						<option value="dismissed"><?php esc_html_e( 'Dismissed', 'food-calorie-calculator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="fcc-reqs-export__period-row">
				<span class="fcc-reqs-export__filter-label"><?php esc_html_e( 'Period', 'food-calorie-calculator' ); ?></span>
				<div class="fcc-reqs-export__presets" role="radiogroup">
					<?php
					$presets = [
						0  => __( 'All time', 'food-calorie-calculator' ),
						7  => __( 'Last 7 days', 'food-calorie-calculator' ),
						14 => __( 'Last 14 days', 'food-calorie-calculator' ),
						30 => __( 'Last 30 days', 'food-calorie-calculator' ),
						-1 => __( 'Custom range', 'food-calorie-calculator' ),
					];
					foreach ( $presets as $val => $label ) :
					?>
						<label class="fcc-reqs-export__preset">
							<input type="radio" name="days" value="<?php echo esc_attr( $val ); ?>"<?php checked( $val, 0 ); ?>>
							<span><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="fcc-reqs-export__daterange" id="fcc-reqs-daterange-export" hidden>
				<label class="fcc-reqs-export__date-label" for="fcc-exp-date-from">
					<?php esc_html_e( 'From', 'food-calorie-calculator' ); ?>
					<input type="date" name="date_from" id="fcc-exp-date-from" class="fcc-reqs-export__date-input" value="">
				</label>
				<span class="fcc-reqs-export__date-sep" aria-hidden="true">→</span>
				<label class="fcc-reqs-export__date-label" for="fcc-exp-date-to">
					<?php esc_html_e( 'To', 'food-calorie-calculator' ); ?>
					<input type="date" name="date_to" id="fcc-exp-date-to" class="fcc-reqs-export__date-input" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>">
				</label>
			</div>

			<div class="fcc-reqs-export__btns">
				<button type="submit" name="format" value="csv" class="fcc-reqs-export-btn">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					<?php esc_html_e( 'Download CSV', 'food-calorie-calculator' ); ?>
				</button>
				<button type="submit" name="format" value="xlsx" class="fcc-reqs-export-btn fcc-reqs-export-btn--xlsx">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
					<?php esc_html_e( 'Download Excel', 'food-calorie-calculator' ); ?>
				</button>
			</div>
		</form>
	</div><!-- .fcc-reqs-export -->

	<script>
	( function () {
		var radios   = document.querySelectorAll( '#fcc-reqs-daterange-export input[name="days"]' );
		var dateWrap = document.getElementById( 'fcc-reqs-daterange-export' );
		function toggle() {
			var checked = document.querySelector( '#fcc-reqs-daterange-export input[name="days"]:checked' );
			if ( dateWrap ) dateWrap.hidden = ! checked || checked.value !== '-1';
		}
		radios.forEach( function ( r ) { r.addEventListener( 'change', toggle ); } );
		toggle();
	}() );
	</script>

</div><!-- .wrap -->
