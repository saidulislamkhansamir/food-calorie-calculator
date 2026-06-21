<?php
/**
 * Admin: Settings page (tabbed).
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$settings   = FCC\Settings::get_all();
$general    = $settings['general'];
$features   = $settings['features'];
$appearance = $settings['appearance'];
$labels     = $settings['labels'];
$advanced   = $settings['advanced'];

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

$tabs = [
	'general'    => [ 'label' => __( 'General',    'food-calorie-calculator' ), 'icon' => '⚙️' ],
	'features'   => [ 'label' => __( 'Features',   'food-calorie-calculator' ), 'icon' => '⚡' ],
	'appearance' => [ 'label' => __( 'Appearance', 'food-calorie-calculator' ), 'icon' => '🎨' ],
	'labels'     => [ 'label' => __( 'Labels',     'food-calorie-calculator' ), 'icon' => '🏷️' ],
	'pinned'     => [ 'label' => __( 'Pinned',     'food-calorie-calculator' ), 'icon' => '📌' ],
	'advanced'   => [ 'label' => __( 'Advanced',   'food-calorie-calculator' ), 'icon' => '🔧' ],
];

$categories = FCC\Database::get_all_categories();

$all_nutrients = [
	'energy_kcal'          => __( 'Energy (kcal)',      'food-calorie-calculator' ),
	'energy_kj'            => __( 'Energy (kJ)',         'food-calorie-calculator' ),
	'protein_g'            => __( 'Protein',            'food-calorie-calculator' ),
	'carbohydrate_g'       => __( 'Carbohydrate',       'food-calorie-calculator' ),
	'of_which_sugars_g'    => __( 'of which Sugars',    'food-calorie-calculator' ),
	'fat_g'                => __( 'Fat',                'food-calorie-calculator' ),
	'of_which_saturates_g' => __( 'of which Saturates', 'food-calorie-calculator' ),
	'fibre_g'              => __( 'Fibre',              'food-calorie-calculator' ),
	'salt_g'               => __( 'Salt',               'food-calorie-calculator' ),
	'omega3_total_mg'      => __( 'Omega-3 Total',      'food-calorie-calculator' ),
	'caffeine_mg'          => __( 'Caffeine',           'food-calorie-calculator' ),
];

$features_on = count( array_filter( $features ) );
$active_label = $tabs[ $active_tab ]['label'] ?? '';
?>
<div class="wrap fcc-admin-wrap fcc-settings-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Settings', 'food-calorie-calculator' ); ?></h1>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-stg-hero">
		<div class="fcc-stg-hero__inner">
			<div class="fcc-stg-hero__content">
				<div class="fcc-stg-hero__icon" aria-hidden="true">⚙️</div>
				<div>
					<div class="fcc-stg-hero__title"><?php esc_html_e( 'Settings', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-stg-hero__sub">
						<?php esc_html_e( 'Configure every aspect of the calculator — units, features, colours, labels, and more.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>
			<div class="fcc-stg-hero__stats">
				<div class="fcc-stg-hero-stat">
					<span class="fcc-stg-hero-stat__value"><?php echo $features_on; ?></span>
					<span class="fcc-stg-hero-stat__label"><?php esc_html_e( 'Features On', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-stg-hero-stat">
					<span class="fcc-stg-hero-stat__value"><?php echo count( $tabs ); ?></span>
					<span class="fcc-stg-hero-stat__label"><?php esc_html_e( 'Sections', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-stg-hero-stat fcc-stg-hero-stat--active">
					<span class="fcc-stg-hero-stat__pill">
						<?php echo esc_html( $tabs[ $active_tab ]['icon'] . ' ' . $active_label ); ?>
					</span>
					<span class="fcc-stg-hero-stat__label"><?php esc_html_e( 'Active Tab', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div><!-- .fcc-stg-hero -->

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ======================================================================
	     Tab navigation
	     ====================================================================== -->
	<nav class="fcc-stg-tabs" aria-label="<?php esc_attr_e( 'Settings tabs', 'food-calorie-calculator' ); ?>">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'fcc-settings', 'tab' => $key ], admin_url( 'admin.php' ) ) ); ?>"
				class="fcc-stg-tab <?php echo $key === $active_tab ? 'fcc-stg-tab--active' : ''; ?>"
				aria-current="<?php echo $key === $active_tab ? 'page' : 'false'; ?>">
				<span class="fcc-stg-tab__icon" aria-hidden="true"><?php echo $tab['icon']; // phpcs:ignore -- emoji from hardcoded array ?></span>
				<span class="fcc-stg-tab__label"><?php echo esc_html( $tab['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>

	<!-- ======================================================================
	     Settings form
	     ====================================================================== -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		class="fcc-stg-form" id="fcc-stg-form">
		<input type="hidden" name="action"  value="fcc_save_settings">
		<input type="hidden" name="fcc_tab" value="<?php echo esc_attr( $active_tab ); ?>">
		<?php wp_nonce_field( 'fcc_save_settings' ); ?>

		<div class="fcc-stg-body">

		<?php if ( 'general' === $active_tab ) : ?>
		<!-- ============================================================
		     GENERAL TAB
		     ============================================================ -->

			<!-- Display settings -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Display Settings', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Controls how the calculator presents data to visitors.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">

					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="default_unit"><?php esc_html_e( 'Default Unit System', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<select id="default_unit" name="default_unit" class="fcc-stg-select">
								<option value="metric"   <?php selected( $general['default_unit'], 'metric' ); ?>><?php esc_html_e( 'Metric (grams)', 'food-calorie-calculator' ); ?></option>
								<option value="imperial" <?php selected( $general['default_unit'], 'imperial' ); ?>><?php esc_html_e( 'Imperial (oz)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>

					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="decimal_places"><?php esc_html_e( 'Decimal Places', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Applied to nutrient values (0–3)', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="decimal_places" name="decimal_places" min="0" max="3"
								value="<?php echo absint( $general['decimal_places'] ); ?>" class="fcc-stg-number">
						</div>
					</div>

					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="default_category"><?php esc_html_e( 'Default Category Filter', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Pre-selects a category in the search dropdown.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<select id="default_category" name="default_category" class="fcc-stg-select">
								<option value="0"><?php esc_html_e( 'All Categories', 'food-calorie-calculator' ); ?></option>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo absint( $cat['id'] ); ?>"
										<?php selected( $general['default_category'], $cat['id'] ); ?>>
										<?php echo esc_html( $cat['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

				</div>
			</div>

			<!-- Search & Quantity -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Search & Quantity', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Control search behaviour and quantity input constraints.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="default_quantity"><?php esc_html_e( 'Default Quantity', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Pre-filled when a food is selected.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="default_quantity" name="default_quantity" min="1" max="9999"
								value="<?php echo absint( $general['default_quantity'] ?? 100 ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="max_quantity"><?php esc_html_e( 'Max Quantity', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Maximum value a visitor can enter.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="max_quantity" name="max_quantity" min="100" max="99999"
								value="<?php echo absint( $general['max_quantity'] ?? 9999 ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="search_result_limit"><?php esc_html_e( 'Search Result Limit', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Max autocomplete results (5–20).', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="search_result_limit" name="search_result_limit" min="5" max="20"
								value="<?php echo absint( $general['search_result_limit'] ?? 10 ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="popular_foods_count"><?php esc_html_e( 'Popular Foods Count', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Shown below search. Set 0 to hide entirely.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="popular_foods_count" name="popular_foods_count" min="0" max="12"
								value="<?php echo absint( $general['popular_foods_count'] ?? 8 ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="search_debounce"><?php esc_html_e( 'Search Debounce (ms)', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Delay before search fires (100–500). Lower = faster.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="search_debounce" name="search_debounce" min="100" max="500" step="10"
								value="<?php echo absint( $general['search_debounce'] ?? 280 ); ?>" class="fcc-stg-number">
						</div>
					</div>

				</div>
			</div>

			<!-- Show nutrients -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Show Nutrients', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Choose which nutrients appear in the results table. Unticked rows are hidden from visitors.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-nutrient-grid">
					<?php foreach ( $all_nutrients as $key => $label ) : ?>
						<label class="fcc-stg-nutrient <?php echo in_array( $key, $general['show_nutrients'] ?? [], true ) ? 'fcc-stg-nutrient--checked' : ''; ?>">
							<input type="checkbox" name="show_nutrients[]" value="<?php echo esc_attr( $key ); ?>"
								<?php checked( in_array( $key, $general['show_nutrients'] ?? [], true ) ); ?>>
							<span class="fcc-stg-nutrient__box" aria-hidden="true">
								<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
							</span>
							<span class="fcc-stg-nutrient__label"><?php echo esc_html( $label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- UK Reference Intakes -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'UK Reference Intakes (per day)', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Official UK values: 2000 kcal / 8400 kJ. Used for the %RI column in results.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-numgrid">
					<?php
					$ri_fields = [
						'ri_energy_kcal'    => __( 'Energy (kcal)', 'food-calorie-calculator' ),
						'ri_energy_kj'      => __( 'Energy (kJ)',   'food-calorie-calculator' ),
						'ri_fat_g'          => __( 'Fat (g)',       'food-calorie-calculator' ),
						'ri_saturates_g'    => __( 'Saturates (g)', 'food-calorie-calculator' ),
						'ri_carbohydrate_g' => __( 'Carbohydrate (g)', 'food-calorie-calculator' ),
						'ri_sugars_g'       => __( 'Sugars (g)',   'food-calorie-calculator' ),
						'ri_protein_g'      => __( 'Protein (g)',  'food-calorie-calculator' ),
						'ri_fibre_g'        => __( 'Fibre (g)',    'food-calorie-calculator' ),
						'ri_salt_g'         => __( 'Salt (g)',     'food-calorie-calculator' ),
					];
					foreach ( $ri_fields as $field => $label ) : ?>
						<div class="fcc-stg-numfield">
							<label class="fcc-stg-numfield__label" for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
							<input type="number" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>"
								step="0.1" min="0" value="<?php echo esc_attr( $general[ $field ] ?? '' ); ?>"
								class="fcc-stg-numfield__input">
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- FSA Traffic Light Thresholds -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'FSA Traffic-Light Thresholds (per 100g)', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Below Low = green  •  Above High = red  •  Between = amber', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-fsa-table">
					<div class="fcc-stg-fsa-hd">
						<span></span>
						<span class="fcc-stg-fsa-hd__col fcc-stg-fsa-hd__col--low">🟢 <?php esc_html_e( 'Low (green ≤)', 'food-calorie-calculator' ); ?></span>
						<span class="fcc-stg-fsa-hd__col fcc-stg-fsa-hd__col--high">🔴 <?php esc_html_e( 'High (red >)', 'food-calorie-calculator' ); ?></span>
					</div>
					<?php
					$fsa_groups = [
						'fat'        => __( 'Fat (g)',        'food-calorie-calculator' ),
						'saturates'  => __( 'Saturates (g)',  'food-calorie-calculator' ),
						'sugars'     => __( 'Sugars (g)',     'food-calorie-calculator' ),
						'salt'       => __( 'Salt (g)',       'food-calorie-calculator' ),
					];
					foreach ( $fsa_groups as $key => $label ) : ?>
						<div class="fcc-stg-fsa-row">
							<span class="fcc-stg-fsa-row__name"><?php echo esc_html( $label ); ?></span>
							<input type="number" name="fsa_<?php echo esc_attr( $key ); ?>_low"
								id="fsa_<?php echo esc_attr( $key ); ?>_low"
								step="0.1" min="0"
								value="<?php echo esc_attr( $general[ 'fsa_' . $key . '_low' ] ?? '' ); ?>"
								class="fcc-stg-fsa-input fcc-stg-fsa-input--low"
								aria-label="<?php echo esc_attr( $label . ' low' ); ?>">
							<input type="number" name="fsa_<?php echo esc_attr( $key ); ?>_high"
								id="fsa_<?php echo esc_attr( $key ); ?>_high"
								step="0.1" min="0"
								value="<?php echo esc_attr( $general[ 'fsa_' . $key . '_high' ] ?? '' ); ?>"
								class="fcc-stg-fsa-input fcc-stg-fsa-input--high"
								aria-label="<?php echo esc_attr( $label . ' high' ); ?>">
						</div>
					<?php endforeach; ?>
				</div>
			</div>

		<?php elseif ( 'features' === $active_tab ) : ?>
		<!-- ============================================================
		     FEATURES TAB
		     ============================================================ -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Plugin Features', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Enable or disable individual features. Disabled features are hidden from visitors entirely.', 'food-calorie-calculator' ); ?></p>
				</div>

				<?php
				$feature_groups = [
					[
						'title' => __( 'Food Lookup', 'food-calorie-calculator' ),
						'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
						'color' => '#075B5E',
						'items' => [
							'fsa_traffic_lights' => [ __( 'FSA Traffic-Light Labels', 'food-calorie-calculator' ), __( 'Green / Amber / Red per 100g', 'food-calorie-calculator' ) ],
							'ri_display'         => [ __( '% Reference Intake Column', 'food-calorie-calculator' ), __( '%RI per serving', 'food-calorie-calculator' ) ],
							'macro_chart'        => [ __( 'Macro Breakdown Chart', 'food-calorie-calculator' ), __( 'Donut chart visualization', 'food-calorie-calculator' ) ],
							'health_highlights'  => [ __( 'Health Highlights', 'food-calorie-calculator' ), __( 'Green/amber nutrient chips', 'food-calorie-calculator' ) ],
							'omega3_display'     => [ __( 'Omega-3 Display', 'food-calorie-calculator' ), __( 'ALA / EPA / DHA breakdown', 'food-calorie-calculator' ) ],
							'caffeine_display'   => [ __( 'Caffeine Display', 'food-calorie-calculator' ), __( 'mg per serving', 'food-calorie-calculator' ) ],
							'popular_foods'      => [ __( 'Popular Foods', 'food-calorie-calculator' ), __( 'Trending searches below input', 'food-calorie-calculator' ) ],
							'voice_search'       => [ __( 'Voice Search', 'food-calorie-calculator' ), __( 'Microphone button for speech-to-search', 'food-calorie-calculator' ) ],
							'food_request_form'  => [ __( 'Food Request Form', 'food-calorie-calculator' ), __( '"Can\'t find your food?" form', 'food-calorie-calculator' ) ],
						],
					],
					[
						'title' => __( 'Your Meal', 'food-calorie-calculator' ),
						'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
						'color' => '#16a34a',
						'items' => [
							'meal_builder'       => [ __( 'Meal Builder', 'food-calorie-calculator' ), __( 'Add multiple foods, see combined totals', 'food-calorie-calculator' ) ],
							'meal_categories'    => [ __( 'Meal Categories', 'food-calorie-calculator' ), __( 'Breakfast / Lunch / Dinner / Snack grouping', 'food-calorie-calculator' ) ],
							'meal_servings'      => [ __( 'Servings Divider', 'food-calorie-calculator' ), __( 'Divide totals by servings (recipe mode)', 'food-calorie-calculator' ) ],
							'meal_daily_goal'    => [ __( 'Daily Goal Bar', 'food-calorie-calculator' ), __( 'Progress bar showing meal kcal vs TDEE', 'food-calorie-calculator' ) ],
							'meal_edit_quantity' => [ __( 'Edit Quantity', 'food-calorie-calculator' ), __( 'Inline quantity editing for meal items', 'food-calorie-calculator' ) ],
							'meal_print'         => [ __( 'Print Meal', 'food-calorie-calculator' ), __( 'Print meal plan with items and totals', 'food-calorie-calculator' ) ],
							'meal_share'         => [ __( 'Share Meal Link', 'food-calorie-calculator' ), __( 'Shareable URL that auto-loads a meal', 'food-calorie-calculator' ) ],
							'meal_copy'          => [ __( 'Copy Meal Totals', 'food-calorie-calculator' ), __( 'Copy meal nutrition to clipboard', 'food-calorie-calculator' ) ],
							'meal_micronutrients' => [ __( 'Meal Micronutrients', 'food-calorie-calculator' ), __( 'Iron, Calcium, Vitamin C in totals', 'food-calorie-calculator' ) ],
						],
					],
					[
						'title' => __( 'Compare', 'food-calorie-calculator' ),
						'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
						'color' => '#7c3aed',
						'items' => [
							'compare_foods' => [ __( 'Compare Foods', 'food-calorie-calculator' ), __( 'Side-by-side food comparison tab', 'food-calorie-calculator' ) ],
						],
					],
					[
						'title' => __( 'Daily Calorie Need', 'food-calorie-calculator' ),
						'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
						'color' => '#d97706',
						'items' => [
							'bmr_tdee'               => [ __( 'BMR / TDEE Calculator', 'food-calorie-calculator' ), __( 'Mifflin-St Jeor formula', 'food-calorie-calculator' ) ],
							'daily_needs_comparison' => [ __( 'Daily Needs Comparison', 'food-calorie-calculator' ), __( 'Portion % of daily TDEE target', 'food-calorie-calculator' ) ],
						],
					],
					[
						'title' => __( 'General', 'food-calorie-calculator' ),
						'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>',
						'color' => '#64748b',
						'items' => [
							'print_pdf'          => [ __( 'Print / PDF Download', 'food-calorie-calculator' ), __( 'Browser print for food results', 'food-calorie-calculator' ) ],
							'share_link'         => [ __( 'Shareable Link', 'food-calorie-calculator' ), __( 'URL with food + quantity', 'food-calorie-calculator' ) ],
							'json_ld_schema'     => [ __( 'JSON-LD Schema (SEO)', 'food-calorie-calculator' ), __( 'Structured data output', 'food-calorie-calculator' ) ],
							'powered_by_footer'  => [ __( 'Powered-by Footer', 'food-calorie-calculator' ), __( 'Attribution footer link', 'food-calorie-calculator' ) ],
							'add_custom_food'    => [ __( 'Add Custom Food (Frontend)', 'food-calorie-calculator' ), __( 'Visitor-submitted foods', 'food-calorie-calculator' ) ],
						],
					],
				];

				foreach ( $feature_groups as $group ) : ?>
					<div class="fcc-card fcc-stg-feature-group" style="border-left: 4px solid <?php echo esc_attr( $group['color'] ); ?>">
						<h3 class="fcc-stg-feature-group__title" style="color: <?php echo esc_attr( $group['color'] ); ?>">
							<?php echo $group['icon']; // phpcs:ignore ?>
							<?php echo esc_html( $group['title'] ); ?>
							<span class="fcc-stg-feature-group__count"><?php echo count( $group['items'] ); ?></span>
						</h3>
						<div class="fcc-stg-features-grid">
							<?php foreach ( $group['items'] as $key => $data ) : ?>
								<div class="fcc-stg-feature <?php echo ! empty( $features[ $key ] ) ? 'fcc-stg-feature--on' : ''; ?>">
									<div class="fcc-stg-feature__info">
										<strong class="fcc-stg-feature__name"><?php echo esc_html( $data[0] ); ?></strong>
										<span class="fcc-stg-feature__hint"><?php echo esc_html( $data[1] ); ?></span>
									</div>
									<label class="fcc-stg-toggle" aria-label="<?php echo esc_attr( $data[0] ); ?>">
										<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
											<?php checked( ! empty( $features[ $key ] ) ); ?>>
										<span class="fcc-stg-toggle__track"></span>
									</label>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

		<?php elseif ( 'appearance' === $active_tab ) : ?>
		<!-- ============================================================
		     APPEARANCE TAB
		     ============================================================ -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Colours', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'These set the CSS custom properties on the frontend calculator widget.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="primary_colour"><?php esc_html_e( 'Primary Colour', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( '--fcc-primary', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="text" id="primary_colour" name="primary_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['primary_colour'] ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="accent_colour"><?php esc_html_e( 'Accent Colour', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( '--fcc-accent', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="text" id="accent_colour" name="accent_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['accent_colour'] ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="background_colour"><?php esc_html_e( 'Background Colour', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( '--fcc-bg', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="text" id="background_colour" name="background_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['background_colour'] ); ?>">
						</div>
					</div>
				</div>
			</div>

			<!-- Chart Colours -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Chart Colours', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Customise the macro breakdown donut chart segments.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label"><label for="chart_protein_colour"><?php esc_html_e( 'Protein', 'food-calorie-calculator' ); ?></label></div>
						<div class="fcc-stg-row__control">
							<input type="text" id="chart_protein_colour" name="chart_protein_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['chart_protein_colour'] ?? '#3b82f6' ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label"><label for="chart_carbs_colour"><?php esc_html_e( 'Carbohydrates', 'food-calorie-calculator' ); ?></label></div>
						<div class="fcc-stg-row__control">
							<input type="text" id="chart_carbs_colour" name="chart_carbs_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['chart_carbs_colour'] ?? '#f59e0b' ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label"><label for="chart_fat_colour"><?php esc_html_e( 'Fat', 'food-calorie-calculator' ); ?></label></div>
						<div class="fcc-stg-row__control">
							<input type="text" id="chart_fat_colour" name="chart_fat_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['chart_fat_colour'] ?? '#ef4444' ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label"><label for="chart_other_colour"><?php esc_html_e( 'Other', 'food-calorie-calculator' ); ?></label></div>
						<div class="fcc-stg-row__control">
							<input type="text" id="chart_other_colour" name="chart_other_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['chart_other_colour'] ?? '#94a3b8' ); ?>">
						</div>
					</div>
				</div>
			</div>

			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Layout & Typography', 'food-calorie-calculator' ); ?></h2>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="button_radius"><?php esc_html_e( 'Button / Card Radius (px)', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( '--fcc-radius', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="button_radius" name="button_radius" min="0" max="50"
								value="<?php echo absint( $appearance['button_radius'] ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="font_family"><?php esc_html_e( 'Font', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<select id="font_family" name="font_family" class="fcc-stg-select">
								<option value="system"    <?php selected( $appearance['font_family'], 'system' ); ?>><?php esc_html_e( 'System Default', 'food-calorie-calculator' ); ?></option>
								<option value="sans-serif" <?php selected( $appearance['font_family'], 'sans-serif' ); ?>><?php esc_html_e( 'Sans-Serif', 'food-calorie-calculator' ); ?></option>
								<option value="serif"     <?php selected( $appearance['font_family'], 'serif' ); ?>><?php esc_html_e( 'Serif', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label><?php esc_html_e( 'Dark Mode', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Auto-detects prefers-color-scheme', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<label class="fcc-stg-toggle">
								<input type="checkbox" name="dark_mode" value="1"
									<?php checked( ! empty( $appearance['dark_mode'] ) ); ?>>
								<span class="fcc-stg-toggle__track"></span>
							</label>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="layout"><?php esc_html_e( 'Layout', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Controls calculator max-width.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<select id="layout" name="layout" class="fcc-stg-select">
								<option value="standard" <?php selected( $appearance['layout'] ?? '', 'standard' ); ?>><?php esc_html_e( 'Standard (720px)', 'food-calorie-calculator' ); ?></option>
								<option value="compact"  <?php selected( $appearance['layout'] ?? '', 'compact' ); ?>><?php esc_html_e( 'Compact (480px)', 'food-calorie-calculator' ); ?></option>
								<option value="wide"     <?php selected( $appearance['layout'] ?? '', 'wide' ); ?>><?php esc_html_e( 'Wide (960px)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="card_style"><?php esc_html_e( 'Card Style', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Visual treatment for content sections.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<select id="card_style" name="card_style" class="fcc-stg-select">
								<option value="elevated" <?php selected( $appearance['card_style'] ?? '', 'elevated' ); ?>><?php esc_html_e( 'Elevated (shadow)', 'food-calorie-calculator' ); ?></option>
								<option value="flat"     <?php selected( $appearance['card_style'] ?? '', 'flat' ); ?>><?php esc_html_e( 'Flat (no border)', 'food-calorie-calculator' ); ?></option>
								<option value="outlined" <?php selected( $appearance['card_style'] ?? '', 'outlined' ); ?>><?php esc_html_e( 'Outlined (border)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label><?php esc_html_e( 'Results Animation', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Fade-in effect when results appear.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<label class="fcc-stg-toggle">
								<input type="checkbox" name="results_animation" value="1"
									<?php checked( ! empty( $appearance['results_animation'] ?? true ) ); ?>>
								<span class="fcc-stg-toggle__track"></span>
							</label>
						</div>
					</div>
				</div>
			</div>

			<!-- Voice Search Customisation -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Voice Search Button', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Customise the microphone button appearance. Enable/disable in the Features tab.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="voice_icon"><?php esc_html_e( 'Icon Style', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<select id="voice_icon" name="voice_icon" class="fcc-stg-select">
								<option value="svg"   <?php selected( $appearance['voice_icon'] ?? '', 'svg' ); ?>>SVG (🎙️)</option>
								<option value="text"  <?php selected( $appearance['voice_icon'] ?? '', 'text' ); ?>><?php esc_html_e( 'Text (Mic)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="voice_colour"><?php esc_html_e( 'Button Colour', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<input type="text" id="voice_colour" name="voice_colour" class="fcc-color-picker"
								value="<?php echo esc_attr( $appearance['voice_colour'] ?? '#075B5E' ); ?>">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="voice_size"><?php esc_html_e( 'Button Size', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<select id="voice_size" name="voice_size" class="fcc-stg-select">
								<option value="small"  <?php selected( $appearance['voice_size'] ?? '', 'small' ); ?>><?php esc_html_e( 'Small (30px)', 'food-calorie-calculator' ); ?></option>
								<option value="medium" <?php selected( $appearance['voice_size'] ?? '', 'medium' ); ?>><?php esc_html_e( 'Medium (38px)', 'food-calorie-calculator' ); ?></option>
								<option value="large"  <?php selected( $appearance['voice_size'] ?? '', 'large' ); ?>><?php esc_html_e( 'Large (46px)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Custom CSS', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Applied to the frontend calculator only. Use .fcc-* selectors.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows fcc-stg-rows--flush">
					<textarea id="custom_css" name="custom_css" rows="10"
						class="fcc-stg-css-editor large-text code"><?php echo esc_textarea( $appearance['custom_css'] ?? '' ); ?></textarea>
				</div>
			</div>

		<?php elseif ( 'labels' === $active_tab ) : ?>
		<!-- ============================================================
		     LABELS TAB
		     ============================================================ -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Frontend Text Labels', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Override any text shown in the frontend calculator. Leave blank to use the plugin default.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-labels-grid">
					<?php
					$label_fields = [
						'calculator_title'    => __( 'Calculator Title',          'food-calorie-calculator' ),
						'search_placeholder'  => __( 'Search Placeholder',        'food-calorie-calculator' ),
						'quantity_label'      => __( 'Quantity Label',            'food-calorie-calculator' ),
						'unit_label'          => __( 'Unit Label',                'food-calorie-calculator' ),
						'results_title'       => __( 'Results Section Title',     'food-calorie-calculator' ),
						'per_label'           => __( '"Per" Label',               'food-calorie-calculator' ),
						'ri_label'            => __( '%RI Column Header',         'food-calorie-calculator' ),
						'ri_footnote'         => __( '%RI Footnote',              'food-calorie-calculator' ),
						'meal_title'          => __( 'Meal Builder Title',        'food-calorie-calculator' ),
						'add_to_meal_label'   => __( 'Add to Meal Button',        'food-calorie-calculator' ),
						'bmr_title'           => __( 'BMR/TDEE Section Title',   'food-calorie-calculator' ),
						'omega3_title'        => __( 'Omega-3 Section Title',    'food-calorie-calculator' ),
						'caffeine_title'      => __( 'Caffeine Section Title',   'food-calorie-calculator' ),
						'no_data_label'       => __( '"Data Not Available" Text', 'food-calorie-calculator' ),
						'traffic_light_label' => __( 'Traffic Lights Label',     'food-calorie-calculator' ),
					];
					foreach ( $label_fields as $field => $desc ) : ?>
						<div class="fcc-stg-label-field">
							<label class="fcc-stg-label-field__label" for="label_<?php echo esc_attr( $field ); ?>">
								<?php echo esc_html( $desc ); ?>
							</label>
							<input type="text" id="label_<?php echo esc_attr( $field ); ?>"
								name="<?php echo esc_attr( $field ); ?>"
								value="<?php echo esc_attr( $labels[ $field ] ?? '' ); ?>"
								class="fcc-stg-label-field__input"
								placeholder="<?php esc_attr_e( 'Plugin default', 'food-calorie-calculator' ); ?>">
						</div>
					<?php endforeach; ?>
				</div>
			</div>

		<?php elseif ( 'pinned' === $active_tab ) : ?>
		<!-- ============================================================
		     PINNED TAB — Promotion Control Center
		     ============================================================ -->
			<?php $pinned_settings = \FCC\Settings::get_section( 'pinned' );
			$badge_options = [ '' => '— None —', 'Best Seller' => 'Best Seller', 'Sponsored' => 'Sponsored', 'New' => 'New', 'Editor Pick' => 'Editor Pick', 'Popular' => 'Popular', 'Featured' => 'Featured', 'Premium' => 'Premium', 'Sale' => 'Sale' ];
			?>

			<!-- ── Section 1: Pinned Search Results ── -->
			<?php $pin_count = count( $pinned_settings['pinned_foods'] ?? [] ); ?>
			<div class="fcc-promo-section fcc-promo-section--pin">
				<div class="fcc-promo-section__header">
					<div class="fcc-promo-section__icon fcc-promo-section__icon--pin">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 11V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v7"/><path d="M5 11h14l-1.5 6H6.5z"/></svg>
					</div>
					<div>
						<h2 class="fcc-promo-section__title"><?php esc_html_e( 'Pinned Search Results', 'food-calorie-calculator' ); ?> <span class="fcc-promo-section__count"><?php echo $pin_count; ?></span></h2>
						<p class="fcc-promo-section__desc"><?php esc_html_e( 'Force specific foods to appear at positions 1st, 2nd, or 3rd in the search dropdown when a keyword matches. Optionally add a promotional badge.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>
				<?php if ( ! $pin_count ) : ?>
					<div class="fcc-promo-empty">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M12 17v5"/><path d="M9 11V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v7"/><path d="M5 11h14l-1.5 6H6.5z"/></svg>
						<p><?php esc_html_e( 'No pin rules yet. Click "Add Pin Rule" to promote foods for specific search keywords.', 'food-calorie-calculator' ); ?></p>
					</div>
				<?php endif; ?>

				<table class="fcc-pin-table" id="fcc-pin-table"<?php echo ! $pin_count ? ' style="display:none"' : ''; ?>>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Keyword', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Pos', 'food-calorie-calculator' ); ?></th>
							<th><?php esc_html_e( 'Badge', 'food-calorie-calculator' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="fcc-pin-tbody">
						<?php
						$pinned_foods = $pinned_settings['pinned_foods'] ?? [];
						foreach ( $pinned_foods as $idx => $rule ) : ?>
							<tr class="fcc-pin-row">
								<td>
									<input type="text" name="pinned_foods[<?php echo $idx; ?>][keyword]"
										value="<?php echo esc_attr( $rule['keyword'] ); ?>"
										placeholder="e.g. caviar" class="fcc-pin-input">
								</td>
								<td style="position:relative">
									<input type="text" class="fcc-pin-input fcc-pin-food-search"
										value="<?php echo esc_attr( $rule['food_name'] ); ?>"
										placeholder="<?php esc_attr_e( 'Search food…', 'food-calorie-calculator' ); ?>"
										autocomplete="off">
									<input type="hidden" name="pinned_foods[<?php echo $idx; ?>][food_id]"
										value="<?php echo absint( $rule['food_id'] ); ?>" class="fcc-pin-food-id">
									<input type="hidden" name="pinned_foods[<?php echo $idx; ?>][food_name]"
										value="<?php echo esc_attr( $rule['food_name'] ); ?>" class="fcc-pin-food-name">
									<ul class="fcc-pin-dropdown" hidden></ul>
								</td>
								<td>
									<select name="pinned_foods[<?php echo $idx; ?>][position]" class="fcc-pin-select">
										<option value="1"<?php selected( $rule['position'] ?? 1, 1 ); ?>>1st</option>
										<option value="2"<?php selected( $rule['position'] ?? 1, 2 ); ?>>2nd</option>
										<option value="3"<?php selected( $rule['position'] ?? 1, 3 ); ?>>3rd</option>
									</select>
								</td>
								<td>
									<select name="pinned_foods[<?php echo $idx; ?>][badge]" class="fcc-pin-select">
										<?php foreach ( $badge_options as $val => $lbl ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $rule['badge'] ?? '', $val ); ?>><?php echo esc_html( $lbl ); ?></option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<button type="button" class="fcc-pin-remove" title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="fcc-promo-section__footer">
					<button type="button" class="fcc-promo-add-btn" id="fcc-pin-add">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						<?php esc_html_e( 'Add Pin Rule', 'food-calorie-calculator' ); ?>
					</button>
					<span class="fcc-promo-section__limit"><?php esc_html_e( 'Max 20 rules', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<!-- ── Section 2: Curated Trending (Dropdown) ── -->
			<?php $trend_count = count( $pinned_settings['trending_foods'] ?? [] ); ?>
			<div class="fcc-promo-section fcc-promo-section--trend">
				<div class="fcc-promo-section__header">
					<div class="fcc-promo-section__icon fcc-promo-section__icon--trend">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
					</div>
					<div>
						<h2 class="fcc-promo-section__title"><?php esc_html_e( 'Curated Trending (Dropdown)', 'food-calorie-calculator' ); ?> <span class="fcc-promo-section__count"><?php echo $trend_count; ?></span></h2>
						<p class="fcc-promo-section__desc"><?php esc_html_e( 'When a visitor focuses the search box (before typing), these foods appear as "Trending Now" inside the dropdown. The auto-generated trending chips below the search bar remain unchanged.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>
				<?php if ( ! $trend_count ) : ?>
					<div class="fcc-promo-empty">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
						<p><?php esc_html_e( 'No curated trending foods yet. Add foods to promote them when visitors first click the search box.', 'food-calorie-calculator' ); ?></p>
					</div>
				<?php endif; ?>

				<table class="fcc-pin-table" id="fcc-trending-table"<?php echo ! $trend_count ? ' style="display:none"' : ''; ?>>
					<thead>
						<tr>
							<th>#</th>
							<th><?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody id="fcc-trending-tbody">
						<?php
						$trending_foods = $pinned_settings['trending_foods'] ?? [];
						foreach ( $trending_foods as $idx => $item ) : ?>
							<tr class="fcc-pin-row">
								<td class="fcc-an-td--num"><?php echo $idx + 1; ?></td>
								<td style="position:relative">
									<input type="text" class="fcc-pin-input fcc-pin-food-search"
										value="<?php echo esc_attr( $item['food_name'] ); ?>"
										placeholder="<?php esc_attr_e( 'Search food…', 'food-calorie-calculator' ); ?>"
										autocomplete="off">
									<input type="hidden" name="trending_foods[<?php echo $idx; ?>][food_id]"
										value="<?php echo absint( $item['food_id'] ); ?>" class="fcc-pin-food-id">
									<input type="hidden" name="trending_foods[<?php echo $idx; ?>][food_name]"
										value="<?php echo esc_attr( $item['food_name'] ); ?>" class="fcc-pin-food-name">
									<ul class="fcc-pin-dropdown" hidden></ul>
								</td>
								<td>
									<button type="button" class="fcc-pin-remove" title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="fcc-promo-section__footer">
					<button type="button" class="fcc-promo-add-btn" id="fcc-trending-add">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						<?php esc_html_e( 'Add Trending Food', 'food-calorie-calculator' ); ?>
					</button>
					<span class="fcc-promo-section__limit"><?php esc_html_e( 'Max 10 foods', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<!-- ── Section 3: Promotional Banners ── -->
			<?php $banner_count = count( $pinned_settings['promo_banners'] ?? [] ); ?>
			<div class="fcc-promo-section fcc-promo-section--banner">
				<div class="fcc-promo-section__header">
					<div class="fcc-promo-section__icon fcc-promo-section__icon--banner">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
					</div>
					<div>
						<h2 class="fcc-promo-section__title"><?php esc_html_e( 'Promotional Banners', 'food-calorie-calculator' ); ?> <span class="fcc-promo-section__count"><?php echo $banner_count; ?></span></h2>
						<p class="fcc-promo-section__desc"><?php esc_html_e( 'Show a custom promotional message with an optional CTA button when a specific food is selected. Perfect for affiliate offers and sponsor callouts.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>
				<?php if ( ! $banner_count ) : ?>
					<div class="fcc-promo-empty">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
						<p><?php esc_html_e( 'No promo banners yet. Add a banner to show a promotional message when visitors select a specific food.', 'food-calorie-calculator' ); ?></p>
					</div>
				<?php endif; ?>

				<div id="fcc-promo-list">
					<?php
					$promo_banners = $pinned_settings['promo_banners'] ?? [];
					foreach ( $promo_banners as $idx => $promo ) : ?>
						<div class="fcc-promo-card">
							<div class="fcc-promo-card__row">
								<div class="fcc-promo-card__field">
									<label><?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?></label>
									<div style="position:relative">
										<input type="text" class="fcc-pin-input fcc-pin-food-search"
											value="<?php echo esc_attr( $promo['food_name'] ); ?>"
											placeholder="<?php esc_attr_e( 'Search food…', 'food-calorie-calculator' ); ?>"
											autocomplete="off">
										<input type="hidden" name="promo_banners[<?php echo $idx; ?>][food_id]"
											value="<?php echo absint( $promo['food_id'] ); ?>" class="fcc-pin-food-id">
										<input type="hidden" name="promo_banners[<?php echo $idx; ?>][food_name]"
											value="<?php echo esc_attr( $promo['food_name'] ); ?>" class="fcc-pin-food-name">
										<ul class="fcc-pin-dropdown" hidden></ul>
									</div>
								</div>
								<div class="fcc-promo-card__field fcc-promo-card__field--wide">
									<label><?php esc_html_e( 'Message', 'food-calorie-calculator' ); ?></label>
									<input type="text" name="promo_banners[<?php echo $idx; ?>][message]"
										value="<?php echo esc_attr( $promo['message'] ?? '' ); ?>"
										placeholder="e.g. Buy fresh Beluga Caviar at SalmonCaviar.co.uk!" class="fcc-pin-input">
								</div>
							</div>
							<div class="fcc-promo-card__row">
								<div class="fcc-promo-card__field">
									<label><?php esc_html_e( 'Button Text', 'food-calorie-calculator' ); ?></label>
									<input type="text" name="promo_banners[<?php echo $idx; ?>][link_text]"
										value="<?php echo esc_attr( $promo['link_text'] ?? '' ); ?>"
										placeholder="e.g. Shop Now →" class="fcc-pin-input">
								</div>
								<div class="fcc-promo-card__field fcc-promo-card__field--wide">
									<label><?php esc_html_e( 'Button URL', 'food-calorie-calculator' ); ?></label>
									<input type="url" name="promo_banners[<?php echo $idx; ?>][link_url]"
										value="<?php echo esc_attr( $promo['link_url'] ?? '' ); ?>"
										placeholder="https://salmoncaviar.co.uk/beluga-caviar" class="fcc-pin-input">
								</div>
								<div class="fcc-promo-card__field" style="flex:0 0 auto;align-self:flex-end">
									<button type="button" class="fcc-pin-remove fcc-promo-remove" title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									</button>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="fcc-promo-section__footer">
					<button type="button" class="fcc-promo-add-btn" id="fcc-promo-add">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						<?php esc_html_e( 'Add Promo Banner', 'food-calorie-calculator' ); ?>
					</button>
					<span class="fcc-promo-section__limit"><?php esc_html_e( 'Max 10 banners', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<!-- How it works -->
			<div class="fcc-pin-help">
				<h3><?php esc_html_e( 'How the Promotion Suite works', 'food-calorie-calculator' ); ?></h3>
				<ul>
					<li><strong><?php esc_html_e( 'Pinned Results:', 'food-calorie-calculator' ); ?></strong> <?php esc_html_e( 'Force foods into position 1st/2nd/3rd for specific keywords. Badges like "Best Seller" show as coloured pills.', 'food-calorie-calculator' ); ?></li>
					<li><strong><?php esc_html_e( 'Curated Trending:', 'food-calorie-calculator' ); ?></strong> <?php esc_html_e( 'Your hand-picked foods appear in the dropdown when a visitor focuses the search box (before typing). Auto-generated trending chips below the search bar stay as-is.', 'food-calorie-calculator' ); ?></li>
					<li><strong><?php esc_html_e( 'Promo Banners:', 'food-calorie-calculator' ); ?></strong> <?php esc_html_e( 'Custom message + CTA button shown when a specific food is selected. Use for affiliate links, sponsor callouts, or cross-selling.', 'food-calorie-calculator' ); ?></li>
				</ul>
			</div>

		<?php elseif ( 'advanced' === $active_tab ) : ?>
		<!-- ============================================================
		     ADVANCED TAB
		     ============================================================ -->
			<!-- Health Highlights Thresholds -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Health Highlight Thresholds (per 100g)', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Configure what triggers positive (green) and warning (amber) badges on food results.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-numgrid">
					<?php
					$hl_fields = [
						'hl_high_protein'        => [ '🟢', __( 'High Protein (g ≥)',      'food-calorie-calculator' ) ],
						'hl_low_fat'             => [ '🟢', __( 'Low Fat (g ≤)',            'food-calorie-calculator' ) ],
						'hl_low_calorie'         => [ '🟢', __( 'Low Calorie (kcal ≤)',     'food-calorie-calculator' ) ],
						'hl_low_sugar'           => [ '🟢', __( 'Low Sugar (g ≤)',          'food-calorie-calculator' ) ],
						'hl_high_fibre'          => [ '🟢', __( 'High Fibre (g ≥)',         'food-calorie-calculator' ) ],
						'hl_low_salt'            => [ '🟢', __( 'Low Salt (g ≤)',           'food-calorie-calculator' ) ],
						'hl_omega3_rich'         => [ '🟢', __( 'Rich in Omega-3 (mg ≥)',   'food-calorie-calculator' ) ],
						'hl_warn_high_salt'      => [ '🟠', __( 'High Salt Warning (g ≥)', 'food-calorie-calculator' ) ],
						'hl_warn_high_saturates' => [ '🟠', __( 'High Saturates Warning (g ≥)', 'food-calorie-calculator' ) ],
						'hl_warn_high_sugar'     => [ '🟠', __( 'High Sugar Warning (g ≥)',     'food-calorie-calculator' ) ],
					];
					foreach ( $hl_fields as $field => $meta ) : ?>
						<div class="fcc-stg-numfield">
							<label class="fcc-stg-numfield__label" for="<?php echo esc_attr( $field ); ?>"><?php echo $meta[0] . ' ' . esc_html( $meta[1] ); ?></label>
							<input type="number" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( $field ); ?>"
								step="0.1" min="0" value="<?php echo esc_attr( $advanced[ $field ] ?? '' ); ?>"
								class="fcc-stg-numfield__input">
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- BMR Configuration -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'BMR / TDEE Configuration', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Controls the daily calorie calculator formula and weight goal adjustments.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="bmr_formula"><?php esc_html_e( 'BMR Formula', 'food-calorie-calculator' ); ?></label>
						</div>
						<div class="fcc-stg-row__control">
							<select id="bmr_formula" name="bmr_formula" class="fcc-stg-select">
								<option value="mifflin"          <?php selected( $advanced['bmr_formula'] ?? '', 'mifflin' ); ?>><?php esc_html_e( 'Mifflin-St Jeor (recommended)', 'food-calorie-calculator' ); ?></option>
								<option value="harris_benedict"  <?php selected( $advanced['bmr_formula'] ?? '', 'harris_benedict' ); ?>><?php esc_html_e( 'Harris-Benedict (revised)', 'food-calorie-calculator' ); ?></option>
								<option value="katch_mcardle"    <?php selected( $advanced['bmr_formula'] ?? '', 'katch_mcardle' ); ?>><?php esc_html_e( 'Katch-McArdle (est. body fat)', 'food-calorie-calculator' ); ?></option>
							</select>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="calorie_goal_adjustment"><?php esc_html_e( 'Goal Calorie Adjustment (kcal)', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Added/subtracted for gain/lose weight goals.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="calorie_goal_adjustment" name="calorie_goal_adjustment" min="100" max="2000" step="50"
								value="<?php echo absint( $advanced['calorie_goal_adjustment'] ?? 500 ); ?>" class="fcc-stg-number">
						</div>
					</div>
				</div>
			</div>

			<!-- Meal Categories -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Meal Categories', 'food-calorie-calculator' ); ?></h2>
					<p class="fcc-stg-section__sub"><?php esc_html_e( 'Customise category names, emojis, and time windows for auto-selection. Enable/disable in Features tab.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-rows">
					<?php
					$meal_cats = [
						'breakfast' => __( 'Breakfast', 'food-calorie-calculator' ),
						'lunch'     => __( 'Lunch', 'food-calorie-calculator' ),
						'dinner'    => __( 'Dinner', 'food-calorie-calculator' ),
						'snack'     => __( 'Snack', 'food-calorie-calculator' ),
					];
					foreach ( $meal_cats as $cat_key => $cat_default ) :
						$lbl   = $advanced[ 'meal_cat_' . $cat_key . '_label' ] ?? $cat_default;
						$emoji = $advanced[ 'meal_cat_' . $cat_key . '_emoji' ] ?? '';
						$start = $advanced[ 'meal_cat_' . $cat_key . '_start' ] ?? 0;
						$end   = $advanced[ 'meal_cat_' . $cat_key . '_end' ] ?? 0;
					?>
						<div class="fcc-stg-row">
							<div class="fcc-stg-row__label">
								<label><?php echo esc_html( $cat_default ); ?></label>
							</div>
							<div class="fcc-stg-row__control" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
								<input type="text" name="meal_cat_<?php echo esc_attr( $cat_key ); ?>_emoji"
									value="<?php echo esc_attr( $emoji ); ?>" class="fcc-stg-number" style="width:50px;text-align:center"
									placeholder="🍽️" title="<?php esc_attr_e( 'Emoji', 'food-calorie-calculator' ); ?>">
								<input type="text" name="meal_cat_<?php echo esc_attr( $cat_key ); ?>_label"
									value="<?php echo esc_attr( $lbl ); ?>" class="fcc-stg-number" style="width:100px"
									placeholder="<?php echo esc_attr( $cat_default ); ?>">
								<?php if ( $cat_key !== 'snack' ) : ?>
									<span style="font-size:0.72rem;color:#888"><?php esc_html_e( 'Hours:', 'food-calorie-calculator' ); ?></span>
									<input type="number" name="meal_cat_<?php echo esc_attr( $cat_key ); ?>_start"
										value="<?php echo absint( $start ); ?>" min="0" max="23" class="fcc-stg-number" style="width:55px">
									<span style="font-size:0.75rem;color:#888">–</span>
									<input type="number" name="meal_cat_<?php echo esc_attr( $cat_key ); ?>_end"
										value="<?php echo absint( $end ); ?>" min="0" max="23" class="fcc-stg-number" style="width:55px">
								<?php else : ?>
									<span style="font-size:0.72rem;color:#888;font-style:italic"><?php esc_html_e( 'Anytime (fills gaps)', 'food-calorie-calculator' ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="meal_max_templates"><?php esc_html_e( 'Max Templates', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Maximum saved meal templates per user (1–20).', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="meal_max_templates" name="meal_max_templates" min="1" max="20"
								value="<?php echo absint( $advanced['meal_max_templates'] ?? 10 ); ?>" class="fcc-stg-number">
						</div>
					</div>
				</div>
			</div>

			<!-- Performance -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Performance', 'food-calorie-calculator' ); ?></h2>
				</div>
				<div class="fcc-stg-rows">
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label><?php esc_html_e( 'REST API Cache', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Transient cache for food search results.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<label class="fcc-stg-toggle">
								<input type="checkbox" name="cache_enabled" value="1"
									<?php checked( ! empty( $advanced['cache_enabled'] ) ); ?>>
								<span class="fcc-stg-toggle__track"></span>
							</label>
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="cache_duration"><?php esc_html_e( 'Cache Duration (seconds)', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( '3600 = 1 hour. Range: 60–86400.', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="cache_duration" name="cache_duration" min="60" max="86400"
								value="<?php echo absint( $advanced['cache_duration'] ?? 3600 ); ?>" class="fcc-stg-number">
						</div>
					</div>
					<div class="fcc-stg-row">
						<div class="fcc-stg-row__label">
							<label for="search_min_chars"><?php esc_html_e( 'Search Min Characters', 'food-calorie-calculator' ); ?></label>
							<p class="fcc-stg-row__hint"><?php esc_html_e( 'Minimum characters before search fires (1–5).', 'food-calorie-calculator' ); ?></p>
						</div>
						<div class="fcc-stg-row__control">
							<input type="number" id="search_min_chars" name="search_min_chars" min="1" max="5"
								value="<?php echo absint( $advanced['search_min_chars'] ?? 2 ); ?>" class="fcc-stg-number">
						</div>
					</div>
				</div>
			</div>

			<div class="fcc-stg-section fcc-stg-section--danger">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title fcc-stg-section__title--danger">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
						<?php esc_html_e( 'Danger Zone', 'food-calorie-calculator' ); ?>
					</h2>
					<p class="fcc-stg-section__sub fcc-stg-section__sub--danger"><?php esc_html_e( 'Irreversible actions. Read carefully before enabling.', 'food-calorie-calculator' ); ?></p>
				</div>
				<div class="fcc-stg-features-grid">
					<div class="fcc-stg-feature fcc-stg-feature--danger <?php echo ! empty( $advanced['delete_data_on_uninstall'] ) ? 'fcc-stg-feature--on' : ''; ?>">
						<div class="fcc-stg-feature__info">
							<strong class="fcc-stg-feature__name"><?php esc_html_e( 'Delete All Data on Uninstall', 'food-calorie-calculator' ); ?></strong>
							<span class="fcc-stg-feature__hint"><?php esc_html_e( 'Drops custom DB tables + all settings. Cannot be undone.', 'food-calorie-calculator' ); ?></span>
						</div>
						<label class="fcc-stg-toggle fcc-stg-toggle--danger">
							<input type="checkbox" name="delete_data_on_uninstall" value="1"
								<?php checked( ! empty( $advanced['delete_data_on_uninstall'] ) ); ?>>
							<span class="fcc-stg-toggle__track"></span>
						</label>
					</div>
				</div>
			</div>

		<?php endif; ?>

		</div><!-- .fcc-stg-body -->

		<div class="fcc-stg-footer">
			<button type="submit" name="submit_settings" class="fcc-stg-save">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
				<?php esc_html_e( 'Save Settings', 'food-calorie-calculator' ); ?>
			</button>
			<span class="fcc-stg-footer__tab">
				<?php echo esc_html( $tabs[ $active_tab ]['icon'] . ' ' . $tabs[ $active_tab ]['label'] ); ?>
			</span>
		</div>

	</form>

</div><!-- .wrap -->

<script>
( function () {
	'use strict';
	// Keep nutrient card appearance in sync with checkbox state.
	document.querySelectorAll( '.fcc-stg-nutrient input[type="checkbox"]' ).forEach( function ( cb ) {
		cb.addEventListener( 'change', function () {
			cb.closest( '.fcc-stg-nutrient' ).classList.toggle( 'fcc-stg-nutrient--checked', cb.checked );
		} );
	} );
	// Keep feature card appearance in sync with toggle state.
	document.querySelectorAll( '.fcc-stg-feature .fcc-stg-toggle input' ).forEach( function ( cb ) {
		cb.addEventListener( 'change', function () {
			cb.closest( '.fcc-stg-feature' ).classList.toggle( 'fcc-stg-feature--on', cb.checked );
		} );
	} );
} )();

// ── Promotion suite JS (Pinned, Trending, Promo Banners) ────────────
( function () {
	var restUrl = '<?php echo esc_url( rest_url( 'fcc/v1/foods/search' ) ); ?>';
	var nonce   = '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>';
	var badgeOpts = '<?php
		$opts = '';
		foreach ( $badge_options as $v => $l ) {
			$sel = '';
			$opts .= '<option value="' . esc_attr( $v ) . '">' . esc_html( $l ) . '</option>';
		}
		echo $opts;
	?>';
	var rmSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

	function foodCell( prefix, idx ) {
		return '<div style="position:relative">' +
			'<input type="text" class="fcc-pin-input fcc-pin-food-search" placeholder="Search food…" autocomplete="off">' +
			'<input type="hidden" name="' + prefix + '[' + idx + '][food_id]" class="fcc-pin-food-id">' +
			'<input type="hidden" name="' + prefix + '[' + idx + '][food_name]" class="fcc-pin-food-name">' +
			'<ul class="fcc-pin-dropdown" hidden></ul></div>';
	}

	// ── 1. Pinned Search Results ──
	var pinBody  = document.getElementById( 'fcc-pin-tbody' );
	var pinAdd   = document.getElementById( 'fcc-pin-add' );
	var pinCount = pinBody ? pinBody.querySelectorAll( '.fcc-pin-row' ).length : 0;

	if ( pinAdd ) pinAdd.addEventListener( 'click', function () {
		if ( pinCount >= 20 ) { alert( 'Maximum 20 pin rules.' ); return; }
		var tr = document.createElement( 'tr' );
		tr.className = 'fcc-pin-row';
		tr.innerHTML =
			'<td><input type="text" name="pinned_foods[' + pinCount + '][keyword]" placeholder="e.g. caviar" class="fcc-pin-input"></td>' +
			'<td style="position:relative">' + foodCell( 'pinned_foods', pinCount ).replace( '<div style="position:relative">', '' ).replace( '</div>', '' ) + '</td>' +
			'<td><select name="pinned_foods[' + pinCount + '][position]" class="fcc-pin-select">' +
				'<option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option></select></td>' +
			'<td><select name="pinned_foods[' + pinCount + '][badge]" class="fcc-pin-select">' + badgeOpts + '</select></td>' +
			'<td><button type="button" class="fcc-pin-remove" title="Remove">' + rmSvg + '</button></td>';
		pinBody.appendChild( tr );
		pinCount++;
	} );

	// ── 2. Curated Trending ──
	var trendBody  = document.getElementById( 'fcc-trending-tbody' );
	var trendAdd   = document.getElementById( 'fcc-trending-add' );
	var trendCount = trendBody ? trendBody.querySelectorAll( '.fcc-pin-row' ).length : 0;

	if ( trendAdd ) trendAdd.addEventListener( 'click', function () {
		if ( trendCount >= 10 ) { alert( 'Maximum 10 trending foods.' ); return; }
		var tr = document.createElement( 'tr' );
		tr.className = 'fcc-pin-row';
		tr.innerHTML =
			'<td class="fcc-an-td--num">' + ( trendCount + 1 ) + '</td>' +
			'<td style="position:relative">' +
				'<input type="text" class="fcc-pin-input fcc-pin-food-search" placeholder="Search food…" autocomplete="off">' +
				'<input type="hidden" name="trending_foods[' + trendCount + '][food_id]" class="fcc-pin-food-id">' +
				'<input type="hidden" name="trending_foods[' + trendCount + '][food_name]" class="fcc-pin-food-name">' +
				'<ul class="fcc-pin-dropdown" hidden></ul>' +
			'</td>' +
			'<td><button type="button" class="fcc-pin-remove" title="Remove">' + rmSvg + '</button></td>';
		trendBody.appendChild( tr );
		trendCount++;
	} );

	// ── 3. Promotional Banners ──
	var promoList = document.getElementById( 'fcc-promo-list' );
	var promoAdd  = document.getElementById( 'fcc-promo-add' );
	var promoCount = promoList ? promoList.querySelectorAll( '.fcc-promo-card' ).length : 0;

	if ( promoAdd ) promoAdd.addEventListener( 'click', function () {
		if ( promoCount >= 10 ) { alert( 'Maximum 10 promo banners.' ); return; }
		var card = document.createElement( 'div' );
		card.className = 'fcc-promo-card';
		card.innerHTML =
			'<div class="fcc-promo-card__row">' +
				'<div class="fcc-promo-card__field"><label>Food</label>' + foodCell( 'promo_banners', promoCount ) + '</div>' +
				'<div class="fcc-promo-card__field fcc-promo-card__field--wide"><label>Message</label>' +
					'<input type="text" name="promo_banners[' + promoCount + '][message]" placeholder="e.g. Buy fresh Beluga Caviar!" class="fcc-pin-input"></div>' +
			'</div>' +
			'<div class="fcc-promo-card__row">' +
				'<div class="fcc-promo-card__field"><label>Button Text</label>' +
					'<input type="text" name="promo_banners[' + promoCount + '][link_text]" placeholder="e.g. Shop Now →" class="fcc-pin-input"></div>' +
				'<div class="fcc-promo-card__field fcc-promo-card__field--wide"><label>Button URL</label>' +
					'<input type="url" name="promo_banners[' + promoCount + '][link_url]" placeholder="https://..." class="fcc-pin-input"></div>' +
				'<div class="fcc-promo-card__field" style="flex:0 0 auto;align-self:flex-end">' +
					'<button type="button" class="fcc-pin-remove fcc-promo-remove" title="Remove">' + rmSvg + '</button></div>' +
			'</div>';
		promoList.appendChild( card );
		promoCount++;
	} );

	// ── Global: remove buttons ──
	document.addEventListener( 'click', function ( e ) {
		var rm = e.target.closest( '.fcc-pin-remove' );
		if ( ! rm ) return;
		var row = rm.closest( '.fcc-pin-row' ) || rm.closest( '.fcc-promo-card' );
		if ( row ) row.remove();
	} );

	// ── Global: food autocomplete ──
	var debounceTimer;
	document.addEventListener( 'input', function ( e ) {
		var inp = e.target;
		if ( ! inp.classList.contains( 'fcc-pin-food-search' ) ) return;
		var q = inp.value.trim();
		var parent = inp.closest( 'td' ) || inp.closest( 'div' );
		var dd = parent.querySelector( '.fcc-pin-dropdown' );
		if ( ! dd ) return;
		if ( q.length < 2 ) { dd.hidden = true; dd.innerHTML = ''; return; }

		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( function () {
			fetch( restUrl + '?q=' + encodeURIComponent( q ) + '&limit=6', {
				headers: { 'X-WP-Nonce': nonce }
			} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( foods ) {
				dd.innerHTML = '';
				if ( ! foods.length ) { dd.hidden = true; return; }
				foods.forEach( function ( f ) {
					var li = document.createElement( 'li' );
					li.className = 'fcc-pin-dropdown__item';
					li.textContent = f.name + ' (' + f.energy_kcal + ' kcal)';
					li.dataset.id   = f.id;
					li.dataset.name = f.name;
					dd.appendChild( li );
				} );
				dd.hidden = false;
			} )
			.catch( function () { dd.hidden = true; } );
		}, 250 );
	} );

	document.addEventListener( 'click', function ( e ) {
		var item = e.target.closest( '.fcc-pin-dropdown__item' );
		if ( item ) {
			var parent = item.closest( 'td' ) || item.closest( 'div' );
			parent.querySelector( '.fcc-pin-food-search' ).value = item.dataset.name;
			parent.querySelector( '.fcc-pin-food-id' ).value     = item.dataset.id;
			parent.querySelector( '.fcc-pin-food-name' ).value   = item.dataset.name;
			item.closest( '.fcc-pin-dropdown' ).hidden = true;
			return;
		}
		if ( ! e.target.closest( '.fcc-pin-food-search' ) ) {
			document.querySelectorAll( '.fcc-pin-dropdown' ).forEach( function ( dd ) { dd.hidden = true; } );
		}
	} );
} )();
</script>
