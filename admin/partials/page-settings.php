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
				<div class="fcc-stg-features-grid">
					<?php
					$feature_labels = [
						'bmr_tdee'               => [ 'label' => __( 'BMR / TDEE Calculator',            'food-calorie-calculator' ), 'hint' => __( 'Mifflin-St Jeor formula', 'food-calorie-calculator' ) ],
						'daily_needs_comparison' => [ 'label' => __( 'Daily Needs Comparison',           'food-calorie-calculator' ), 'hint' => __( 'Meal % of TDEE',          'food-calorie-calculator' ) ],
						'fsa_traffic_lights'     => [ 'label' => __( 'FSA Traffic-Light Labels',         'food-calorie-calculator' ), 'hint' => __( 'Green / Amber / Red',      'food-calorie-calculator' ) ],
						'ri_display'             => [ 'label' => __( '% Reference Intake Column',        'food-calorie-calculator' ), 'hint' => __( '%RI per serving',          'food-calorie-calculator' ) ],
						'macro_chart'            => [ 'label' => __( 'Macro Breakdown Chart',            'food-calorie-calculator' ), 'hint' => __( 'Donut chart',              'food-calorie-calculator' ) ],
						'omega3_display'         => [ 'label' => __( 'Omega-3 Display',                  'food-calorie-calculator' ), 'hint' => __( 'ALA / EPA / DHA',          'food-calorie-calculator' ) ],
						'caffeine_display'       => [ 'label' => __( 'Caffeine Display',                 'food-calorie-calculator' ), 'hint' => __( 'mg per serving',           'food-calorie-calculator' ) ],
						'meal_builder'           => [ 'label' => __( 'Meal Builder',                     'food-calorie-calculator' ), 'hint' => __( 'Add multiple foods',       'food-calorie-calculator' ) ],
						'print_pdf'              => [ 'label' => __( 'Print / PDF Download',             'food-calorie-calculator' ), 'hint' => __( 'Browser print',            'food-calorie-calculator' ) ],
						'share_link'             => [ 'label' => __( 'Shareable Link',                   'food-calorie-calculator' ), 'hint' => __( 'URL with food + qty',      'food-calorie-calculator' ) ],
						'add_custom_food'        => [ 'label' => __( 'Add Custom Food (Frontend)',       'food-calorie-calculator' ), 'hint' => __( 'Visitor-submitted foods',  'food-calorie-calculator' ) ],
						'json_ld_schema'         => [ 'label' => __( 'JSON-LD Schema (SEO)',             'food-calorie-calculator' ), 'hint' => __( 'Structured data output',   'food-calorie-calculator' ) ],
					];
					foreach ( $feature_labels as $key => $data ) : ?>
						<div class="fcc-stg-feature <?php echo ! empty( $features[ $key ] ) ? 'fcc-stg-feature--on' : ''; ?>">
							<div class="fcc-stg-feature__info">
								<strong class="fcc-stg-feature__name"><?php echo esc_html( $data['label'] ); ?></strong>
								<span class="fcc-stg-feature__hint"><?php echo esc_html( $data['hint'] ); ?></span>
							</div>
							<label class="fcc-stg-toggle" aria-label="<?php echo esc_attr( $data['label'] ); ?>">
								<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
									<?php checked( ! empty( $features[ $key ] ) ); ?>>
								<span class="fcc-stg-toggle__track"></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
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

		<?php elseif ( 'advanced' === $active_tab ) : ?>
		<!-- ============================================================
		     ADVANCED TAB
		     ============================================================ -->
			<div class="fcc-stg-section">
				<div class="fcc-stg-section__hd">
					<h2 class="fcc-stg-section__title"><?php esc_html_e( 'Performance', 'food-calorie-calculator' ); ?></h2>
				</div>
				<div class="fcc-stg-features-grid">
					<div class="fcc-stg-feature <?php echo ! empty( $advanced['cache_enabled'] ) ? 'fcc-stg-feature--on' : ''; ?>">
						<div class="fcc-stg-feature__info">
							<strong class="fcc-stg-feature__name"><?php esc_html_e( 'REST API Cache', 'food-calorie-calculator' ); ?></strong>
							<span class="fcc-stg-feature__hint"><?php esc_html_e( 'Transient cache for food search results', 'food-calorie-calculator' ); ?></span>
						</div>
						<label class="fcc-stg-toggle">
							<input type="checkbox" name="cache_enabled" value="1"
								<?php checked( ! empty( $advanced['cache_enabled'] ) ); ?>>
							<span class="fcc-stg-toggle__track"></span>
						</label>
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
</script>
