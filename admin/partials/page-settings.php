<?php
/**
 * Admin: Settings page (tabbed).
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$settings    = FCC\Settings::get_all();
$general     = $settings['general'];
$features    = $settings['features'];
$appearance  = $settings['appearance'];
$labels      = $settings['labels'];
$advanced    = $settings['advanced'];
$xml_sitemap   = FCC\Settings::get_section( 'xml_sitemap' );
$auto_pub_cfg  = FCC\Settings::get_section( 'auto_publisher' );
$auto_pub_stats = FCC\Auto_Publisher::get_stats();
$auto_pub_log   = get_option( FCC\Auto_Publisher::LOG_OPTION, [] );

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

$tabs = [
	'general'      => [ 'label' => __( 'General',        'food-calorie-calculator' ), 'icon' => '⚙️' ],
	'features'     => [ 'label' => __( 'Features',       'food-calorie-calculator' ), 'icon' => '⚡' ],
	'appearance'   => [ 'label' => __( 'Appearance',     'food-calorie-calculator' ), 'icon' => '🎨' ],
	'labels'       => [ 'label' => __( 'Labels',         'food-calorie-calculator' ), 'icon' => '🏷️' ],
	'pinned'       => [ 'label' => __( 'Pinned',         'food-calorie-calculator' ), 'icon' => '📌' ],
	'advanced'     => [ 'label' => __( 'Advanced',       'food-calorie-calculator' ), 'icon' => '🔧' ],
	'xml_sitemap'  => [ 'label' => __( 'XML Sitemap',    'food-calorie-calculator' ), 'icon' => '🗺️' ],
	'auto_publisher' => [ 'label' => __( 'Auto Publisher', 'food-calorie-calculator' ), 'icon' => '📅' ],
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
				<div class="fcc-stg-hero__icon" aria-hidden="true"><img src="<?php echo esc_url( FCC_PLUGIN_URL . 'logo/Food Calorie Calculator Favicon - White (1).png' ); ?>" width="40" height="40" alt="" decoding="async" style="display:block;width:40px;height:40px;object-fit:contain;"></div>
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
							'pwa_install'        => [ __( 'Install as App (PWA)', 'food-calorie-calculator' ), __( 'Add to Home Screen button for mobile visitors', 'food-calorie-calculator' ) ],
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

				<div id="fcc-pin-list">
					<?php
					$pinned_foods = $pinned_settings['pinned_foods'] ?? [];
					$pos_labels = [ 1 => '1st', 2 => '2nd', 3 => '3rd' ];
					foreach ( $pinned_foods as $idx => $rule ) :
						$is_enabled = ( $rule['enabled'] ?? 1 ) ? true : false;
					?>
						<div class="fcc-pincard<?php echo ! $is_enabled ? ' fcc-pincard--disabled' : ''; ?>">
							<div class="fcc-pincard__topbar">
								<div class="fcc-pincard__topbar-icon">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 11V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v7"/><path d="M5 11h14l-1.5 6H6.5z"/></svg>
								</div>
								<span class="fcc-pincard__topbar-label"><?php echo esc_html( $rule['food_name'] ?: 'New Rule' ); ?></span>
								<span class="fcc-pincard__topbar-pos"><?php echo esc_html( $pos_labels[ $rule['position'] ?? 1 ] ?? '1st' ); ?></span>
								<?php if ( ! empty( $rule['badge'] ) ) : ?>
									<span class="fcc-pincard__topbar-badge"><?php echo esc_html( $rule['badge'] ); ?></span>
								<?php endif; ?>
								<div class="fcc-pincard__actions">
									<input type="hidden" name="pinned_foods[<?php echo $idx; ?>][enabled]" value="<?php echo $is_enabled ? '1' : '0'; ?>" class="fcc-pincard__enabled-val">
									<button type="button" class="fcc-pincard__action-btn fcc-pincard__toggle" title="<?php echo $is_enabled ? esc_attr__( 'Disable', 'food-calorie-calculator' ) : esc_attr__( 'Enable', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
									</button>
									<button type="button" class="fcc-pincard__action-btn fcc-pincard__duplicate" title="<?php esc_attr_e( 'Duplicate', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
									</button>
									<button type="button" class="fcc-pincard__action-btn fcc-pincard__collapse" title="<?php esc_attr_e( 'Collapse', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
									</button>
									<button type="button" class="fcc-promo-card__close fcc-pin-remove" title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
										<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
									</button>
								</div>
							</div>
							<div class="fcc-pincard__body">
								<div class="fcc-pincard__grid">
									<div class="fcc-pincard__cell fcc-pincard__cell--keyword">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
											<?php esc_html_e( 'Keyword', 'food-calorie-calculator' ); ?>
										</label>
										<input type="text" name="pinned_foods[<?php echo $idx; ?>][keyword]"
											value="<?php echo esc_attr( $rule['keyword'] ); ?>"
											placeholder="e.g. caviar" class="fcc-pin-input">
									</div>
									<div class="fcc-pincard__cell fcc-pincard__cell--food" style="position:relative">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/></svg>
											<?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?>
										</label>
										<input type="text" class="fcc-pin-input fcc-pin-food-search"
											value="<?php echo esc_attr( $rule['food_name'] ); ?>"
											placeholder="<?php esc_attr_e( 'Search food…', 'food-calorie-calculator' ); ?>"
											autocomplete="off">
										<input type="hidden" name="pinned_foods[<?php echo $idx; ?>][food_id]"
											value="<?php echo absint( $rule['food_id'] ); ?>" class="fcc-pin-food-id">
										<input type="hidden" name="pinned_foods[<?php echo $idx; ?>][food_name]"
											value="<?php echo esc_attr( $rule['food_name'] ); ?>" class="fcc-pin-food-name">
										<ul class="fcc-pin-dropdown" hidden></ul>
									</div>
									<div class="fcc-pincard__cell fcc-pincard__cell--pos">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
											<?php esc_html_e( 'Position', 'food-calorie-calculator' ); ?>
										</label>
										<select name="pinned_foods[<?php echo $idx; ?>][position]" class="fcc-pin-select">
											<option value="1"<?php selected( $rule['position'] ?? 1, 1 ); ?>>1st</option>
											<option value="2"<?php selected( $rule['position'] ?? 1, 2 ); ?>>2nd</option>
											<option value="3"<?php selected( $rule['position'] ?? 1, 3 ); ?>>3rd</option>
										</select>
									</div>
									<div class="fcc-pincard__cell fcc-pincard__cell--badge">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
											<?php esc_html_e( 'Badge', 'food-calorie-calculator' ); ?>
										</label>
										<select name="pinned_foods[<?php echo $idx; ?>][badge]" class="fcc-pin-select">
											<?php foreach ( $badge_options as $val => $lbl ) : ?>
												<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $rule['badge'] ?? '', $val ); ?>><?php echo esc_html( $lbl ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
							<div class="fcc-pincard__preview">
								<div class="fcc-pincard__preview-dot"></div>
								<span>Search "<strong><?php echo esc_html( $rule['keyword'] ); ?></strong>" → <strong><?php echo esc_html( $rule['food_name'] ); ?></strong> at <?php echo esc_html( $pos_labels[ $rule['position'] ?? 1 ] ?? '1st' ); ?></span>
								<?php if ( ! empty( $rule['badge'] ) ) : ?>
									<span class="fcc-pincard__preview-badge"><?php echo esc_html( $rule['badge'] ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
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
							<div class="fcc-promo-card__topbar">
								<div class="fcc-promo-card__topbar-icon">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/><path d="M12 8v4"/></svg>
								</div>
								<span class="fcc-promo-card__topbar-label"><?php echo esc_html( $promo['food_name'] ?: 'New Banner' ); ?></span>
								<span class="fcc-promo-card__topbar-num">#<?php echo $idx + 1; ?></span>
								<button type="button" class="fcc-promo-card__close fcc-pin-remove" title="<?php esc_attr_e( 'Remove', 'food-calorie-calculator' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
								</button>
							</div>
							<div class="fcc-promo-card__body">
								<div class="fcc-promo-card__row fcc-promo-card__row--wide">
									<div class="fcc-promo-card__field">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
											<?php esc_html_e( 'Food', 'food-calorie-calculator' ); ?>
										</label>
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
									<div class="fcc-promo-card__field">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
											<?php esc_html_e( 'Promotional Message', 'food-calorie-calculator' ); ?>
										</label>
										<input type="text" name="promo_banners[<?php echo $idx; ?>][message]"
											value="<?php echo esc_attr( $promo['message'] ?? '' ); ?>"
											placeholder="e.g. Buy fresh Beluga Caviar at SalmonCaviar.co.uk!" class="fcc-pin-input">
									</div>
								</div>
								<div class="fcc-promo-card__row fcc-promo-card__row--wide">
									<div class="fcc-promo-card__field">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8"/></svg>
											<?php esc_html_e( 'Button Text', 'food-calorie-calculator' ); ?>
										</label>
										<input type="text" name="promo_banners[<?php echo $idx; ?>][link_text]"
											value="<?php echo esc_attr( $promo['link_text'] ?? '' ); ?>"
											placeholder="e.g. Shop Now →" class="fcc-pin-input">
									</div>
									<div class="fcc-promo-card__field">
										<label>
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
											<?php esc_html_e( 'Button URL', 'food-calorie-calculator' ); ?>
										</label>
										<input type="url" name="promo_banners[<?php echo $idx; ?>][link_url]"
											value="<?php echo esc_attr( $promo['link_url'] ?? '' ); ?>"
											placeholder="https://salmoncaviar.co.uk/beluga-caviar" class="fcc-pin-input">
									</div>
								</div>
							</div>
							<?php if ( ! empty( $promo['message'] ) ) : ?>
								<div class="fcc-promo-card__preview">
									<div class="fcc-promo-card__preview-dot"></div>
									<?php esc_html_e( 'Preview:', 'food-calorie-calculator' ); ?>
									<span><?php echo esc_html( mb_strimwidth( $promo['message'], 0, 60, '…' ) ); ?></span>
									<?php if ( ! empty( $promo['link_text'] ) ) : ?>
										<span style="color:#9b59b6;font-weight:700">[<?php echo esc_html( $promo['link_text'] ); ?>]</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
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

		<?php elseif ( 'xml_sitemap' === $active_tab ) :
		// ============================================================
		// XML SITEMAP TAB
		// ============================================================

		// Pre-compute food sub-sitemap count for quick links.
		global $wpdb;
		$_sm_per   = max( 50, (int) ( $xml_sitemap['foods_per_page'] ?? 500 ) );
		$_sm_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}fcc_foods WHERE is_active = 1" ); // phpcs:ignore
		$_sm_pages = max( 1, (int) ceil( $_sm_total / $_sm_per ) );
		$_all_wp_pages = get_posts( [ 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1 ] );
		$_excluded_ids = array_map( 'absint', (array) ( $xml_sitemap['excluded_pages'] ?? [] ) );
		$_freqs = [ 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never' ];
		?>

		<!-- Quick Links -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Quick Links', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Click any link to preview that sitemap in a new tab.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Master Index', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control">
						<a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary">
							<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?> ↗
						</a>
					</div>
				</div>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Pages Sitemap', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control">
						<a href="<?php echo esc_url( home_url( '/page-sitemap.xml' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary">
							<?php echo esc_url( home_url( '/page-sitemap.xml' ) ); ?> ↗
						</a>
					</div>
				</div>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Food Category Sitemap', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control">
						<a href="<?php echo esc_url( home_url( '/food-category-sitemap.xml' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary">
							<?php echo esc_url( home_url( '/food-category-sitemap.xml' ) ); ?> ↗
						</a>
					</div>
				</div>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php echo esc_html( sprintf( __( 'Food Sitemaps (%d files)', 'food-calorie-calculator' ), $_sm_pages ) ); ?></div>
					<div class="fcc-stg-row__control" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
						<?php for ( $i = 1; $i <= min( $_sm_pages, 20 ); $i++ ) : ?>
						<a href="<?php echo esc_url( home_url( '/food-sitemap-' . $i . '.xml' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary" style="font-size:0.8rem;padding:2px 8px;">
							food-sitemap-<?php echo esc_html( $i ); ?>.xml ↗
						</a>
						<?php endfor; ?>
						<?php if ( $_sm_pages > 20 ) : ?>
						<span style="color:#666;font-size:0.85rem;align-self:center;">+ <?php echo esc_html( $_sm_pages - 20 ); ?> more</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Ping Google -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Ping Google', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Notify Google that your sitemap has been updated. Do this after major content changes.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Notify Google', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control">
						<button type="button" id="fcc-ping-google" class="button button-primary">
							<?php esc_html_e( 'Ping Google Now', 'food-calorie-calculator' ); ?>
						</button>
						<span id="fcc-ping-result" style="margin-left:1rem;font-size:0.875rem;"></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Pagination -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Pagination', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Control how many food pages appear per sub-sitemap file.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="foods_per_page"><?php esc_html_e( 'Foods per sub-sitemap', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<select name="foods_per_page" id="foods_per_page" class="fcc-stg-select">
							<?php foreach ( [ 100 => '100', 200 => '200', 500 => '500 (recommended)', 1000 => '1000' ] as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php selected( (int) ( $xml_sitemap['foods_per_page'] ?? 500 ), $val ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php echo esc_html( sprintf( __( 'Currently: %d foods split across %d sub-sitemap files.', 'food-calorie-calculator' ), $_sm_total, $_sm_pages ) ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Section Toggles -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Include in Sitemap', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Toggle which sections appear in the sitemap index.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<?php
				$_sm_toggles = [
					'include_wp_pages'   => __( 'WordPress Pages (Homepage, About, Contact, etc.)', 'food-calorie-calculator' ),
					'include_hub'        => __( 'Food Hub (/calories/)', 'food-calorie-calculator' ),
					'include_categories' => __( 'Food Category Pages', 'food-calorie-calculator' ),
					'include_foods'      => __( 'Individual Food Pages (4,900+)', 'food-calorie-calculator' ),
				];
				foreach ( $_sm_toggles as $_sm_key => $_sm_label ) : ?>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="<?php echo esc_attr( $_sm_key ); ?>"><?php echo esc_html( $_sm_label ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<label class="fcc-toggle">
							<input type="checkbox" name="<?php echo esc_attr( $_sm_key ); ?>" id="<?php echo esc_attr( $_sm_key ); ?>" value="1"
								<?php checked( ! empty( $xml_sitemap[ $_sm_key ] ) ); ?>>
							<span class="fcc-toggle__slider"></span>
						</label>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Priorities -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Priority', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Values from 0.0 (lowest) to 1.0 (highest). Indicates relative importance to search engines.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-numgrid">
				<?php
				$_sm_priorities = [
					'priority_homepage'   => __( 'Homepage', 'food-calorie-calculator' ),
					'priority_hub'        => __( 'Food Hub', 'food-calorie-calculator' ),
					'priority_categories' => __( 'Category Pages', 'food-calorie-calculator' ),
					'priority_foods'      => __( 'Food Pages', 'food-calorie-calculator' ),
					'priority_wp_pages'   => __( 'WP Pages (About, Contact, etc.)', 'food-calorie-calculator' ),
				];
				foreach ( $_sm_priorities as $_sm_key => $_sm_label ) : ?>
				<div class="fcc-stg-numfield">
					<label class="fcc-stg-numfield__label" for="<?php echo esc_attr( $_sm_key ); ?>"><?php echo esc_html( $_sm_label ); ?></label>
					<input type="number" id="<?php echo esc_attr( $_sm_key ); ?>" name="<?php echo esc_attr( $_sm_key ); ?>"
						step="0.1" min="0" max="1" value="<?php echo esc_attr( $xml_sitemap[ $_sm_key ] ?? '0.5' ); ?>"
						class="fcc-stg-numfield__input">
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Change Frequency -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Change Frequency', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'How often each section is expected to change. Used as a hint by search engines.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<?php
				$_sm_freqs_map = [
					'changefreq_homepage'   => __( 'Homepage', 'food-calorie-calculator' ),
					'changefreq_hub'        => __( 'Food Hub', 'food-calorie-calculator' ),
					'changefreq_categories' => __( 'Category Pages', 'food-calorie-calculator' ),
					'changefreq_foods'      => __( 'Food Pages', 'food-calorie-calculator' ),
					'changefreq_wp_pages'   => __( 'WP Pages (About, Contact, etc.)', 'food-calorie-calculator' ),
				];
				foreach ( $_sm_freqs_map as $_sm_key => $_sm_label ) : ?>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="<?php echo esc_attr( $_sm_key ); ?>"><?php echo esc_html( $_sm_label ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<select name="<?php echo esc_attr( $_sm_key ); ?>" id="<?php echo esc_attr( $_sm_key ); ?>" class="fcc-stg-select">
							<?php foreach ( $_freqs as $_f ) : ?>
							<option value="<?php echo esc_attr( $_f ); ?>" <?php selected( $xml_sitemap[ $_sm_key ] ?? '', $_f ); ?>>
								<?php echo esc_html( ucfirst( $_f ) ); ?>
							</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Exclude Pages -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Exclude WordPress Pages', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Pages checked here will not appear in the sitemap. Useful for login pages, account pages, and thin-content pages.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<?php if ( empty( $_all_wp_pages ) ) : ?>
				<div class="fcc-stg-row"><div class="fcc-stg-row__control"><p class="description"><?php esc_html_e( 'No published pages found.', 'food-calorie-calculator' ); ?></p></div></div>
				<?php else : ?>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Exclude pages', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control">
						<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:0.4rem 1.5rem;">
						<?php foreach ( $_all_wp_pages as $_pg ) : ?>
							<label style="display:flex;align-items:center;gap:0.4rem;font-size:0.875rem;cursor:pointer;">
								<input type="checkbox" name="excluded_pages[]" value="<?php echo esc_attr( $_pg->ID ); ?>"
									<?php checked( in_array( (int) $_pg->ID, $_excluded_ids, true ) ); ?>>
								<?php echo esc_html( get_the_title( $_pg->ID ) ); ?>
								<span style="color:#999;font-size:0.78rem;"><?php echo esc_html( str_replace( home_url(), '', get_permalink( $_pg->ID ) ) ); ?></span>
							</label>
						<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		( function () {
			var btn    = document.getElementById( 'fcc-ping-google' );
			var result = document.getElementById( 'fcc-ping-result' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				btn.disabled = true;
				btn.textContent = '<?php echo esc_js( __( 'Pinging…', 'food-calorie-calculator' ) ); ?>';
				result.textContent = '';
				result.style.color = '';
				fetch( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams( {
						action:   'fcc_ping_google_sitemap',
						_wpnonce: '<?php echo esc_js( wp_create_nonce( 'fcc_save_settings' ) ); ?>',
					} ),
				} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					btn.disabled    = false;
					btn.textContent = '<?php echo esc_js( __( 'Ping Google Now', 'food-calorie-calculator' ) ); ?>';
					result.textContent = data.data && data.data.message ? data.data.message : ( data.success ? 'Done.' : 'Failed.' );
					result.style.color = data.success ? '#1a7a3f' : '#c0392b';
				} )
				.catch( function () {
					btn.disabled    = false;
					btn.textContent = '<?php echo esc_js( __( 'Ping Google Now', 'food-calorie-calculator' ) ); ?>';
					result.textContent = 'Network error.';
					result.style.color = '#c0392b';
				} );
			} );
		}() );
		</script>

		<?php elseif ( 'auto_publisher' === $active_tab ) :
		// ============================================================
		// AUTO PUBLISHER TAB
		// ============================================================
		$ap_nonce     = wp_create_nonce( 'fcc_save_settings' );
		$ap_published = (int) $auto_pub_stats['published'];
		$ap_total     = (int) $auto_pub_stats['total'];
		$ap_pct       = $ap_total > 0 ? round( $ap_published / $ap_total * 100 ) : 0;
		?>

		<!-- Publication Status -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Publication Status', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Live count of published vs. remaining food pages.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">

				<!-- Stat row -->
				<div class="fcc-stg-row" style="align-items:flex-start;flex-wrap:wrap;gap:1.5rem;">
					<div style="display:flex;gap:2.5rem;flex-wrap:wrap;">
						<div class="fcc-stg-stat">
							<span class="fcc-stg-stat__val" id="fcc-ap-published"><?php echo esc_html( number_format( $ap_published ) ); ?></span>
							<span class="fcc-stg-stat__lbl">Published</span>
						</div>
						<div class="fcc-stg-stat">
							<span class="fcc-stg-stat__val" style="color:#6b7280;" id="fcc-ap-unpublished"><?php echo esc_html( number_format( (int) $auto_pub_stats['unpublished'] ) ); ?></span>
							<span class="fcc-stg-stat__lbl">Remaining</span>
						</div>
						<div class="fcc-stg-stat">
							<span class="fcc-stg-stat__val" style="color:#374151;" id="fcc-ap-total"><?php echo esc_html( number_format( $ap_total ) ); ?></span>
							<span class="fcc-stg-stat__lbl">Total Foods</span>
						</div>
						<div class="fcc-stg-stat">
							<span class="fcc-stg-stat__val" id="fcc-ap-pct"><?php echo esc_html( $ap_pct ); ?>%</span>
							<span class="fcc-stg-stat__lbl">Complete</span>
						</div>
						<div class="fcc-stg-stat">
							<span class="fcc-stg-stat__val" style="font-size:1.2rem;" id="fcc-ap-est"><?php echo $auto_pub_stats['est_days'] > 0 ? esc_html( $auto_pub_stats['est_days'] ) . ' days' : '&mdash;'; ?></span>
							<span class="fcc-stg-stat__lbl">Est. to finish</span>
						</div>
					</div>
				</div>

				<!-- Progress bar row -->
				<div class="fcc-stg-row" style="flex-direction:column;align-items:stretch;gap:.5rem;">
					<div style="background:#e5e7eb;border-radius:999px;height:12px;overflow:hidden;">
						<div id="fcc-ap-bar" style="height:100%;border-radius:999px;background:linear-gradient(90deg,#1a7a3f,#28a356);transition:width .5s ease;width:<?php echo esc_attr( $ap_pct ); ?>%;"></div>
					</div>
					<p style="margin:0;font-size:0.82rem;color:#6b7280;">Next scheduled run: <strong id="fcc-ap-next"><?php echo esc_html( $auto_pub_stats['next_run'] ); ?></strong></p>
				</div>

				<!-- Action buttons row -->
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php esc_html_e( 'Quick Actions', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-stg-row__control" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center;">
						<button type="button" id="fcc-ap-run-now" class="button button-primary" style="background:#1a7a3f;border-color:#1a7a3f;"><?php esc_html_e( 'Run Batch Now', 'food-calorie-calculator' ); ?></button>
						<button type="button" id="fcc-ap-publish-all" class="button button-secondary"><?php esc_html_e( 'Publish All Pages', 'food-calorie-calculator' ); ?></button>
						<button type="button" id="fcc-ap-reset-all" class="button" style="color:#b91c1c;border-color:#fca5a5;"><?php esc_html_e( 'Reset All to Unpublished', 'food-calorie-calculator' ); ?></button>
						<span id="fcc-ap-msg" style="font-size:.85rem;display:none;"></span>
					</div>
				</div>

			</div>
		</div>

		<!-- Publisher Settings -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Publisher Settings', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Configure the daily schedule. Save Settings after making changes.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-enabled"><?php esc_html_e( 'Enable Auto-Publisher', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<label class="fcc-stg-toggle">
							<input type="checkbox" name="enabled" id="fcc-ap-enabled" value="1" <?php checked( ! empty( $auto_pub_cfg['enabled'] ) ); ?>>
							<span class="fcc-stg-toggle__track"></span>
						</label>
						<p class="description"><?php esc_html_e( 'When enabled, a daily batch of food pages will go live automatically.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-min"><?php esc_html_e( 'Min foods per day', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<input type="number" name="min_per_day" id="fcc-ap-min" value="<?php echo esc_attr( $auto_pub_cfg['min_per_day'] ); ?>" min="1" max="100" class="small-text">
					</div>
				</div>

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-max"><?php esc_html_e( 'Max foods per day', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<input type="number" name="max_per_day" id="fcc-ap-max" value="<?php echo esc_attr( $auto_pub_cfg['max_per_day'] ); ?>" min="1" max="500" class="small-text">
					</div>
				</div>

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-hour"><?php esc_html_e( 'Daily run hour (0–23)', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<input type="number" name="run_hour" id="fcc-ap-hour" value="<?php echo esc_attr( $auto_pub_cfg['run_hour'] ); ?>" min="0" max="23" class="small-text">
						<p class="description"><?php esc_html_e( 'Server UTC time. e.g. 8 = 08:00 UTC.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-order"><?php esc_html_e( 'Publish order', 'food-calorie-calculator' ); ?></label>
					</div>
					<div class="fcc-stg-row__control">
						<select name="publish_order" id="fcc-ap-order" class="fcc-stg-select">
							<?php foreach ( [ 'random' => 'Random', 'alphabetical' => 'Alphabetical (A–Z)', 'by_category' => 'By Category' ] as $val => $lbl ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $auto_pub_cfg['publish_order'], $val ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label">
						<label for="fcc-ap-batches"><?php esc_html_e( 'Batches per day', 'food-calorie-calculator' ); ?></label>
						<p class="fcc-stg-row__hint"><?php esc_html_e( 'Split the daily quota into mini-batches spread evenly across the day.', 'food-calorie-calculator' ); ?></p>
					</div>
					<div class="fcc-stg-row__control">
						<select name="batches_per_day" id="fcc-ap-batches" class="fcc-stg-select">
							<?php foreach ( [ 1 => '1 — once a day', 2 => '2 — every 12 hours', 3 => '3 — every 8 hours', 4 => '4 — every 6 hours' ] as $val => $lbl ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( (int) ( $auto_pub_cfg['batches_per_day'] ?? 1 ), $val ); ?>><?php echo esc_html( $lbl ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

			</div>
		</div>

		<?php if ( ! empty( $auto_pub_log ) ) : ?>
		<!-- Activity Log -->
		<div class="fcc-stg-section">
			<div class="fcc-stg-section__hd">
				<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Activity Log', 'food-calorie-calculator' ); ?></h2>
				<p class="fcc-stg-section__sub"><?php esc_html_e( 'Last 30 daily publishing runs.', 'food-calorie-calculator' ); ?></p>
			</div>
			<div class="fcc-stg-rows">
				<?php foreach ( $auto_pub_log as $entry ) : ?>
				<div class="fcc-stg-row">
					<div class="fcc-stg-row__label"><?php echo esc_html( $entry['date'] ); ?></div>
					<div class="fcc-stg-row__control">
						<span style="display:inline-block;background:#ecfdf5;color:#1a7a3f;border:1px solid #a7f3d0;border-radius:6px;padding:.2rem .6rem;font-size:.8rem;font-weight:600;">
							+<?php echo esc_html( number_format( (int) $entry['count'] ) ); ?> pages published
						</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<style>
		.fcc-stg-stat { display:flex; flex-direction:column; }
		.fcc-stg-stat__val { font-size:1.75rem; font-weight:700; color:#1a7a3f; line-height:1; }
		.fcc-stg-stat__lbl { font-size:.7rem; color:#6b7280; text-transform:uppercase; letter-spacing:.06em; margin-top:.2rem; }
		</style>

		<script>
		( function () {
			var nonce   = <?php echo wp_json_encode( $ap_nonce ); ?>;
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

			function updateStats( data ) {
				document.getElementById('fcc-ap-published').textContent   = data.published.toLocaleString();
				document.getElementById('fcc-ap-unpublished').textContent = data.unpublished.toLocaleString();
				document.getElementById('fcc-ap-total').textContent       = data.total.toLocaleString();
				var pct = data.total > 0 ? Math.round( data.published / data.total * 100 ) : 0;
				document.getElementById('fcc-ap-pct').textContent         = pct + '%';
				document.getElementById('fcc-ap-bar').style.width         = pct + '%';
				document.getElementById('fcc-ap-next').textContent        = data.next_run;
				document.getElementById('fcc-ap-est').textContent         = data.est_days > 0 ? data.est_days + ' days' : '—';
			}

			function apCall( action, confirmMsg ) {
				if ( confirmMsg && ! confirm( confirmMsg ) ) { return; }
				var btn = document.getElementById( 'fcc-ap-' + action.replace( 'fcc_ap_', '' ).replace( '_', '-' ) );
				var msg = document.getElementById( 'fcc-ap-msg' );
				if ( btn ) { btn.disabled = true; }
				msg.style.display = 'none';
				fetch( ajaxUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'action=' + action + '&nonce=' + encodeURIComponent( nonce )
				} )
				.then( function(r){ return r.json(); } )
				.then( function(res) {
					if ( res.success ) {
						updateStats( res.data );
						msg.textContent  = 'Done.';
						msg.style.color  = '#1a7a3f';
					} else {
						msg.textContent  = 'Error: ' + ( res.data || 'unknown' );
						msg.style.color  = '#b91c1c';
					}
					msg.style.display = 'inline';
					if ( btn ) { btn.disabled = false; }
				} );
			}

			document.getElementById('fcc-ap-run-now').addEventListener( 'click', function(){ apCall('fcc_ap_run_now', null); } );
			document.getElementById('fcc-ap-publish-all').addEventListener( 'click', function(){ apCall('fcc_ap_publish_all', 'Publish ALL food pages now? They will all become visible immediately.'); } );
			document.getElementById('fcc-ap-reset-all').addEventListener( 'click', function(){ apCall('fcc_ap_reset_all', 'Reset ALL food pages to unpublished? The calculator will still show all foods.'); } );
		} )();
		</script>

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
	var pinList  = document.getElementById( 'fcc-pin-list' );
	var pinAdd   = document.getElementById( 'fcc-pin-add' );
	var pinCount = pinList ? pinList.querySelectorAll( '.fcc-pincard' ).length : 0;
	var pinIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 11V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v7"/><path d="M5 11h14l-1.5 6H6.5z"/></svg>';
	var kwIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
	var fdIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 010 8h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/></svg>';
	var posIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>';
	var starIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

	if ( pinAdd ) pinAdd.addEventListener( 'click', function () {
		if ( pinCount >= 20 ) { alert( 'Maximum 20 pin rules.' ); return; }
		var empty = pinAdd.closest( '.fcc-promo-section' ).querySelector( '.fcc-promo-empty' );
		if ( empty ) empty.style.display = 'none';
		var card = document.createElement( 'div' );
		card.className = 'fcc-pincard';
		card.innerHTML = buildPinCardHTML( pinCount, '', '', 0, '', '1', 'New Rule' );
		pinList.appendChild( card );
		pinCount++;
	} );

	var toggleSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.36 6.64a9 9 0 11-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>';
	var dupSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>';
	var collSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';

	function buildPinCardHTML( idx, keyword, foodName, foodId, badge, position, label ) {
		return '<div class="fcc-pincard__topbar">' +
			'<div class="fcc-pincard__topbar-icon">' + pinIcon + '</div>' +
			'<span class="fcc-pincard__topbar-label">' + ( label || 'New Rule' ) + '</span>' +
			'<span class="fcc-pincard__topbar-pos">' + ( {1:'1st',2:'2nd',3:'3rd'}[position] || '1st' ) + '</span>' +
			( badge ? '<span class="fcc-pincard__topbar-badge">' + badge + '</span>' : '' ) +
			'<div class="fcc-pincard__actions">' +
				'<input type="hidden" name="pinned_foods[' + idx + '][enabled]" value="1" class="fcc-pincard__enabled-val">' +
				'<button type="button" class="fcc-pincard__action-btn fcc-pincard__toggle" title="Toggle">' + toggleSvg + '</button>' +
				'<button type="button" class="fcc-pincard__action-btn fcc-pincard__duplicate" title="Duplicate">' + dupSvg + '</button>' +
				'<button type="button" class="fcc-pincard__action-btn fcc-pincard__collapse" title="Collapse">' + collSvg + '</button>' +
				'<button type="button" class="fcc-promo-card__close fcc-pin-remove" title="Remove">' + rmSvg + '</button>' +
			'</div>' +
		'</div>' +
		'<div class="fcc-pincard__body">' +
			'<div class="fcc-pincard__grid">' +
				'<div class="fcc-pincard__cell fcc-pincard__cell--keyword"><label>' + kwIcon + ' Keyword</label>' +
					'<input type="text" name="pinned_foods[' + idx + '][keyword]" value="' + keyword + '" placeholder="e.g. caviar" class="fcc-pin-input"></div>' +
				'<div class="fcc-pincard__cell fcc-pincard__cell--food" style="position:relative"><label>' + fdIcon + ' Food</label>' +
					'<div style="position:relative"><input type="text" class="fcc-pin-input fcc-pin-food-search" value="' + foodName + '" placeholder="Search food…" autocomplete="off">' +
					'<input type="hidden" name="pinned_foods[' + idx + '][food_id]" value="' + foodId + '" class="fcc-pin-food-id">' +
					'<input type="hidden" name="pinned_foods[' + idx + '][food_name]" value="' + foodName + '" class="fcc-pin-food-name">' +
					'<ul class="fcc-pin-dropdown" hidden></ul></div></div>' +
				'<div class="fcc-pincard__cell fcc-pincard__cell--pos"><label>' + posIcon + ' Position</label>' +
					'<select name="pinned_foods[' + idx + '][position]" class="fcc-pin-select">' +
					'<option value="1"' + ( position == '1' ? ' selected' : '' ) + '>1st</option>' +
					'<option value="2"' + ( position == '2' ? ' selected' : '' ) + '>2nd</option>' +
					'<option value="3"' + ( position == '3' ? ' selected' : '' ) + '>3rd</option></select></div>' +
				'<div class="fcc-pincard__cell fcc-pincard__cell--badge"><label>' + starIcon + ' Badge</label>' +
					'<select name="pinned_foods[' + idx + '][badge]" class="fcc-pin-select">' + badgeOpts.replace( 'value="' + badge + '"', 'value="' + badge + '" selected' ) + '</select></div>' +
			'</div>' +
		'</div>';
	}

	// ── 2. Curated Trending ──
	var trendBody  = document.getElementById( 'fcc-trending-tbody' );
	var trendAdd   = document.getElementById( 'fcc-trending-add' );
	var trendCount = trendBody ? trendBody.querySelectorAll( '.fcc-pin-row' ).length : 0;

	if ( trendAdd ) trendAdd.addEventListener( 'click', function () {
		if ( trendCount >= 10 ) { alert( 'Maximum 10 trending foods.' ); return; }
		var table = document.getElementById( 'fcc-trending-table' );
		if ( table ) table.style.display = '';
		var empty = trendAdd.closest( '.fcc-promo-section' ).querySelector( '.fcc-promo-empty' );
		if ( empty ) empty.style.display = 'none';
		var tr = document.createElement( 'tr' );
		tr.className = 'fcc-pin-row';
		tr.innerHTML =
			'<td class="fcc-pin-num">' + ( trendCount + 1 ) + '</td>' +
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
		var empty = promoAdd.closest( '.fcc-promo-section' ).querySelector( '.fcc-promo-empty' );
		if ( empty ) empty.style.display = 'none';
		var card = document.createElement( 'div' );
		card.className = 'fcc-promo-card';
		var bannerIcon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z"/><path d="M12 8v4"/></svg>';
		var searchIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
		var msgIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>';
		var btnIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 12h8"/></svg>';
		var linkIcon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
		card.innerHTML =
			'<div class="fcc-promo-card__topbar">' +
				'<div class="fcc-promo-card__topbar-icon">' + bannerIcon + '</div>' +
				'<span class="fcc-promo-card__topbar-label">New Banner</span>' +
				'<span class="fcc-promo-card__topbar-num">#' + ( promoCount + 1 ) + '</span>' +
				'<button type="button" class="fcc-promo-card__close fcc-pin-remove" title="Remove">' + rmSvg + '</button>' +
			'</div>' +
			'<div class="fcc-promo-card__body">' +
				'<div class="fcc-promo-card__row fcc-promo-card__row--wide">' +
					'<div class="fcc-promo-card__field"><label>' + searchIcon + ' Food</label>' + foodCell( 'promo_banners', promoCount ) + '</div>' +
					'<div class="fcc-promo-card__field"><label>' + msgIcon + ' Promotional Message</label>' +
						'<input type="text" name="promo_banners[' + promoCount + '][message]" placeholder="e.g. Buy fresh Beluga Caviar!" class="fcc-pin-input"></div>' +
				'</div>' +
				'<div class="fcc-promo-card__row fcc-promo-card__row--wide">' +
					'<div class="fcc-promo-card__field"><label>' + btnIcon + ' Button Text</label>' +
						'<input type="text" name="promo_banners[' + promoCount + '][link_text]" placeholder="e.g. Shop Now →" class="fcc-pin-input"></div>' +
					'<div class="fcc-promo-card__field"><label>' + linkIcon + ' Button URL</label>' +
						'<input type="url" name="promo_banners[' + promoCount + '][link_url]" placeholder="https://..." class="fcc-pin-input"></div>' +
				'</div>' +
			'</div>';
		promoList.appendChild( card );
		promoCount++;
	} );

	// ── Global: remove buttons ──
	document.addEventListener( 'click', function ( e ) {
		var rm = e.target.closest( '.fcc-pin-remove' );
		if ( ! rm ) return;
		var row = rm.closest( '.fcc-pincard' ) || rm.closest( '.fcc-pin-row' ) || rm.closest( '.fcc-promo-card' );
		if ( row ) row.remove();
	} );

	// ── Toggle ON/OFF ──
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.fcc-pincard__toggle' );
		if ( ! btn ) return;
		var card = btn.closest( '.fcc-pincard' );
		var inp  = card.querySelector( '.fcc-pincard__enabled-val' );
		var isOn = inp.value === '1';
		inp.value = isOn ? '0' : '1';
		card.classList.toggle( 'fcc-pincard--disabled', isOn );
	} );

	// ── Collapse/Expand ──
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.fcc-pincard__collapse' );
		if ( ! btn ) return;
		btn.closest( '.fcc-pincard' ).classList.toggle( 'fcc-pincard--collapsed' );
	} );

	// ── Duplicate ──
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.fcc-pincard__duplicate' );
		if ( ! btn ) return;
		if ( ! pinList ) return;
		if ( pinCount >= 20 ) { alert( 'Maximum 20 pin rules.' ); return; }
		var src = btn.closest( '.fcc-pincard' );
		var kw   = ( src.querySelector( 'input[name*="[keyword]"]' ) || {} ).value || '';
		var fName = ( src.querySelector( '.fcc-pin-food-name' ) || {} ).value || '';
		var fId   = ( src.querySelector( '.fcc-pin-food-id' ) || {} ).value || '0';
		var pos   = ( src.querySelector( 'select[name*="[position]"]' ) || {} ).value || '1';
		var badge = ( src.querySelector( 'select[name*="[badge]"]' ) || {} ).value || '';
		var card = document.createElement( 'div' );
		card.className = 'fcc-pincard';
		card.innerHTML = buildPinCardHTML( pinCount, '', fName, fId, badge, pos, fName || 'New Rule' );
		src.after( card );
		pinCount++;
		card.querySelector( 'input[name*="[keyword]"]' ).focus();
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
				var parentCard = dd.closest( '.fcc-pincard' ) || dd.closest( '.fcc-promo-card' );
				if ( parentCard ) parentCard.classList.add( parentCard.classList.contains( 'fcc-pincard' ) ? 'fcc-pincard--dropdown-open' : 'fcc-promo-card--dropdown-open' );
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
			var card = item.closest( '.fcc-pincard' ) || item.closest( '.fcc-promo-card' );
			if ( card ) card.classList.remove( 'fcc-pincard--dropdown-open', 'fcc-promo-card--dropdown-open' );
			return;
		}
		if ( ! e.target.closest( '.fcc-pin-food-search' ) ) {
			document.querySelectorAll( '.fcc-pin-dropdown' ).forEach( function ( dd ) { dd.hidden = true; } );
			document.querySelectorAll( '.fcc-pincard--dropdown-open, .fcc-promo-card--dropdown-open' ).forEach( function ( c ) {
				c.classList.remove( 'fcc-pincard--dropdown-open', 'fcc-promo-card--dropdown-open' );
			} );
		}
	} );
} )();
</script>
