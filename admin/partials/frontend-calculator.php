<?php
/**
 * Frontend calculator HTML shell.
 *
 * All interactivity is driven by fcc-calculator.js; this file provides
 * the accessible markup skeleton and CSS anchor points.
 *
 * Variables available from FCC\Shortcode::render():
 *   $settings   = full settings array
 *   $features   = settings['features']
 *   $appearance = settings['appearance']
 *   $labels     = settings['labels']
 *   $general    = settings['general']
 *   $atts       = shortcode attributes
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$title          = ! empty( $atts['title'] ) ? $atts['title'] : ( $labels['calculator_title'] ?? __( 'Food Calorie Calculator', 'food-calorie-calculator' ) );
$dark_mode_attr = ! empty( $appearance['dark_mode'] ) ? 'data-fcc-dark="auto"' : '';
$font_style     = '';
$font           = $appearance['font_family'] ?? 'system';
if ( 'system' !== $font ) {
	$font_style = ' style="font-family:' . esc_attr( $font ) . ';"';
}
$has_bmr     = ! empty( $features['bmr_tdee'] );
$has_compare = true; // Compare is always available.
$has_tabs    = $has_bmr || ! empty( $features['meal_builder'] ) || $has_compare;
?>
<div class="fcc-calculator" id="fcc-calculator" <?php echo $dark_mode_attr; // phpcs:ignore ?><?php echo $font_style; // phpcs:ignore ?> role="main" aria-label="<?php echo esc_attr( $title ); ?>">

	<!-- ======================================================================
	     Hero header
	     ====================================================================== -->
	<header class="fcc-hero">
		<div class="fcc-hero__inner">
			<div class="fcc-hero__icon" aria-hidden="true">
				<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8 2 4 5.5 4 10c0 3.9 2.5 7.1 6 8.5V21h4v-2.5c3.5-1.4 6-4.6 6-8.5C20 5.5 16 2 12 2z"/><line x1="12" y1="6" x2="12" y2="10"/><line x1="10" y1="8" x2="14" y2="8"/></svg>
			</div>
			<div class="fcc-hero__text">
				<h1 class="fcc-title"><?php echo esc_html( $title ); ?></h1>
				<p class="fcc-hero__sub"><?php esc_html_e( 'UK nutrition data · FSA traffic lights · Reference Intakes', 'food-calorie-calculator' ); ?></p>
			</div>
		</div>
	</header>

	<!-- ======================================================================
	     Tab navigation (only rendered when BMR is enabled)
	     ====================================================================== -->
	<?php if ( $has_tabs ) : ?>
	<nav class="fcc-tabs-nav" aria-label="<?php esc_attr_e( 'Calculator sections', 'food-calorie-calculator' ); ?>">
		<button type="button" class="fcc-tab-btn fcc-tab-btn--active" data-tab="calculator" aria-selected="true">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<?php esc_html_e( 'Food Lookup', 'food-calorie-calculator' ); ?>
		</button>
		<?php if ( ! empty( $features['meal_builder'] ) ) : ?>
		<button type="button" class="fcc-tab-btn" data-tab="meal" aria-selected="false">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
			<?php echo esc_html( $labels['meal_title'] ?? __( 'My Meal', 'food-calorie-calculator' ) ); ?>
			<span class="fcc-tab-badge" hidden aria-label="<?php esc_attr_e( 'items in meal', 'food-calorie-calculator' ); ?>">0</span>
		</button>
		<?php endif; ?>
		<?php if ( $has_compare ) : ?>
		<button type="button" class="fcc-tab-btn" data-tab="compare" aria-selected="false">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
			<?php esc_html_e( 'Compare', 'food-calorie-calculator' ); ?>
		</button>
		<?php endif; ?>
		<?php if ( $has_bmr ) : ?>
		<button type="button" class="fcc-tab-btn" data-tab="bmr" aria-selected="false">
			<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
			<?php echo esc_html( $labels['bmr_title'] ?? __( 'Daily Calorie Need', 'food-calorie-calculator' ) ); ?>
		</button>
		<?php endif; ?>
	</nav>
	<?php endif; ?>

	<div class="fcc-tabs-body">

		<!-- ==================================================================
		     TAB PANEL 1: Calculator
		     ================================================================== -->
		<div class="fcc-tab-panel" data-panel="calculator">

			<?php if ( ! empty( $ad_slot_above_search ) ) : ?>
			<div class="fcc-ad-slot fcc-ad-slot--above-search"><?php echo $ad_slot_above_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

			<!-- ============================================================
			     FOOD SEARCH
			     ============================================================ -->
			<section class="fcc-section fcc-search-section" aria-label="<?php esc_attr_e( 'Food search', 'food-calorie-calculator' ); ?>">
				<div class="fcc-search-wrapper">
					<label for="fcc-food-search" class="fcc-label fcc-label--search">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<?php echo esc_html( $labels['search_placeholder'] ?? __( 'Search for a food', 'food-calorie-calculator' ) ); ?>
					</label>
					<div class="fcc-autocomplete" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="fcc-search-results">
						<input
							type="search"
							id="fcc-food-search"
							class="fcc-search-input"
							placeholder="<?php echo esc_attr( $labels['search_placeholder'] ?? __( 'e.g. salmon, banana, cheddar…', 'food-calorie-calculator' ) ); ?>"
							autocomplete="off"
							aria-autocomplete="list"
							aria-controls="fcc-search-results"
							aria-label="<?php esc_attr_e( 'Search for a food', 'food-calorie-calculator' ); ?>"
						>
						<span class="fcc-search-icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						</span>
						<span class="fcc-search-spinner" aria-hidden="true"></span>
						<button type="button" class="fcc-search-clear" hidden aria-label="<?php esc_attr_e( 'Clear search', 'food-calorie-calculator' ); ?>">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
						</button>
						<ul id="fcc-search-results" class="fcc-results-dropdown" role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'food-calorie-calculator' ); ?>"></ul>
					</div>
					<p class="fcc-search-hint"><?php esc_html_e( 'Type at least 2 characters to search from our UK foods database', 'food-calorie-calculator' ); ?></p>
					<div id="fcc-popular-section" class="fcc-popular-section" hidden>
						<p class="fcc-popular-label"><?php esc_html_e( 'Popular searches', 'food-calorie-calculator' ); ?></p>
						<div id="fcc-popular-chips" class="fcc-popular-chips" aria-label="<?php esc_attr_e( 'Popular foods', 'food-calorie-calculator' ); ?>"></div>
					</div>

					<!-- Food request panel (shown when search returns no results) -->
					<div id="fcc-request-panel" class="fcc-request-panel" hidden>
						<div class="fcc-request-panel__head">
							<p class="fcc-request-panel__title">
								<?php esc_html_e( 'Request', 'food-calorie-calculator' ); ?>
								<strong class="fcc-req-food-name"></strong>
								<?php esc_html_e( 'to be added', 'food-calorie-calculator' ); ?>
							</p>
							<button type="button" class="fcc-request-close" aria-label="<?php esc_attr_e( 'Close', 'food-calorie-calculator' ); ?>">&times;</button>
						</div>
						<form id="fcc-request-form" class="fcc-request-form" novalidate>
							<input type="hidden" id="fcc-req-food-input" name="food_name" value="">
							<div class="fcc-req-row">
								<label for="fcc-req-email" class="fcc-req-label">
									<?php esc_html_e( 'Your email', 'food-calorie-calculator' ); ?>
									<span class="fcc-req-required" aria-hidden="true">*</span>
								</label>
								<input type="email" id="fcc-req-email" name="email" class="fcc-req-input" placeholder="you@example.com" required>
								<p class="fcc-req-hint"><?php esc_html_e( "We'll email you when this food is added.", 'food-calorie-calculator' ); ?></p>
								<p class="fcc-req-error" id="fcc-req-email-error" hidden><?php esc_html_e( 'Please enter a valid email address.', 'food-calorie-calculator' ); ?></p>
							</div>
							<div class="fcc-req-row fcc-req-row--optin">
								<label class="fcc-req-optin-label" for="fcc-req-optin">
									<input type="checkbox" id="fcc-req-optin" name="marketing_optin" checked>
									<?php esc_html_e( 'Also send me occasional nutrition tips & updates', 'food-calorie-calculator' ); ?>
								</label>
							</div>
							<div class="fcc-req-row">
								<label for="fcc-req-note" class="fcc-req-label">
									<?php esc_html_e( 'Extra info', 'food-calorie-calculator' ); ?>
									<span class="fcc-req-opt"><?php esc_html_e( '(optional)', 'food-calorie-calculator' ); ?></span>
								</label>
								<textarea id="fcc-req-note" name="note" class="fcc-req-textarea" rows="2" placeholder="<?php esc_attr_e( 'Brand, where you found it, any other details…', 'food-calorie-calculator' ); ?>"></textarea>
							</div>
							<div class="fcc-req-footer">
								<button type="submit" class="fcc-req-submit"><?php esc_html_e( 'Send Request', 'food-calorie-calculator' ); ?></button>
							</div>
						</form>
						<div id="fcc-request-success" class="fcc-request-success" hidden>
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
							<?php esc_html_e( "Request sent! We'll review and add it soon.", 'food-calorie-calculator' ); ?>
						</div>
					</div>
				</div>
			</section>

			<!-- ============================================================
			     FOOD HEADER + QUANTITY CONTROLS
			     ============================================================ -->
			<section class="fcc-section fcc-quantity-section" aria-label="<?php esc_attr_e( 'Quantity', 'food-calorie-calculator' ); ?>" hidden>

				<!-- Food name + calorie highlight -->
				<div class="fcc-food-header">
					<div class="fcc-food-header__name">
						<div class="fcc-food-header__label"><?php esc_html_e( 'Selected food', 'food-calorie-calculator' ); ?></div>
						<div class="fcc-selected-food-name" aria-live="polite"></div>
					</div>
					<div class="fcc-food-header__kcal" aria-label="<?php esc_attr_e( 'Calories per serving', 'food-calorie-calculator' ); ?>">
						<span class="fcc-food-kcal-num">0</span>
						<span class="fcc-food-kcal-unit">kcal</span>
					</div>
				</div>

				<!-- Quantity + unit row -->
				<div class="fcc-quantity-row">
					<div class="fcc-quantity-controls">
						<div class="fcc-qty-group">
							<label for="fcc-quantity" class="fcc-label">
								<?php echo esc_html( $labels['quantity_label'] ?? __( 'Quantity', 'food-calorie-calculator' ) ); ?>
							</label>
							<input
								type="number"
								id="fcc-quantity"
								class="fcc-quantity-input"
								value="100"
								min="1"
								max="9999"
								step="1"
								aria-label="<?php esc_attr_e( 'Quantity', 'food-calorie-calculator' ); ?>"
							>
						</div>
						<div class="fcc-qty-group">
							<label class="fcc-label" id="fcc-unit-label">
								<?php echo esc_html( $labels['unit_label'] ?? __( 'Unit', 'food-calorie-calculator' ) ); ?>
							</label>
							<!-- Native select hidden; kept as the JS data source -->
							<select id="fcc-unit" class="fcc-unit-select" aria-hidden="true" tabindex="-1"
								style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;overflow:hidden;">
								<option value="g"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></option>
								<?php if ( ! empty( $general['default_unit'] ) && 'imperial' === $general['default_unit'] ) : ?>
								<option value="oz"><?php esc_html_e( 'ounces (oz)', 'food-calorie-calculator' ); ?></option>
								<?php endif; ?>
								<!-- Serving sizes added dynamically by JS -->
							</select>
							<!-- Custom visual dropdown -->
							<div class="fcc-unit-custom" role="combobox" aria-labelledby="fcc-unit-label" aria-expanded="false" aria-haspopup="listbox">
								<button type="button" class="fcc-unit-trigger" aria-haspopup="listbox">
									<span class="fcc-unit-trigger__text"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></span>
									<svg class="fcc-unit-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
								</button>
								<ul class="fcc-unit-options" role="listbox" aria-labelledby="fcc-unit-label"></ul>
							</div>
						</div>
					</div>

					<?php if ( ! empty( $features['meal_builder'] ) ) : ?>
					<button type="button" class="fcc-btn fcc-btn--secondary fcc-add-to-meal" hidden>
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						<?php echo esc_html( $labels['add_to_meal_label'] ?? __( 'Add to Meal', 'food-calorie-calculator' ) ); ?>
					</button>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( ! empty( $ad_slot_before_results ) ) : ?>
			<div class="fcc-ad-slot fcc-ad-slot--before-results"><?php echo $ad_slot_before_results; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

			<!-- ============================================================
			     RESULTS PANEL
			     ============================================================ -->
			<section class="fcc-section fcc-results-section" aria-label="<?php esc_attr_e( 'Nutrition results', 'food-calorie-calculator' ); ?>" hidden aria-live="polite">
				<h3 class="fcc-section-title"><?php echo esc_html( $labels['results_title'] ?? __( 'Nutrition Information', 'food-calorie-calculator' ) ); ?></h3>

				<!-- Health Highlights -->
				<div class="fcc-health-highlights" hidden aria-label="<?php esc_attr_e( 'Health highlights', 'food-calorie-calculator' ); ?>"></div>

				<!-- FSA Traffic Lights -->
				<?php if ( ! empty( $features['fsa_traffic_lights'] ) ) : ?>
				<div class="fcc-traffic-lights" hidden aria-label="<?php esc_attr_e( 'FSA Traffic Light Labels', 'food-calorie-calculator' ); ?>">
					<p class="fcc-tl-label"><?php echo esc_html( $labels['traffic_light_label'] ?? __( 'FSA Traffic Lights', 'food-calorie-calculator' ) ); ?></p>
					<div class="fcc-tl-row" role="list">
						<div class="fcc-tl-item" role="listitem" data-nutrient="fat">
							<div class="fcc-tl-dot" aria-hidden="true"></div>
							<div class="fcc-tl-info">
								<span class="fcc-tl-nutrient"><?php esc_html_e( 'Fat', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-tl-value"></span>
							</div>
							<span class="fcc-tl-rating"></span>
						</div>
						<div class="fcc-tl-item" role="listitem" data-nutrient="saturates">
							<div class="fcc-tl-dot" aria-hidden="true"></div>
							<div class="fcc-tl-info">
								<span class="fcc-tl-nutrient"><?php esc_html_e( 'Saturates', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-tl-value"></span>
							</div>
							<span class="fcc-tl-rating"></span>
						</div>
						<div class="fcc-tl-item" role="listitem" data-nutrient="sugars">
							<div class="fcc-tl-dot" aria-hidden="true"></div>
							<div class="fcc-tl-info">
								<span class="fcc-tl-nutrient"><?php esc_html_e( 'Sugars', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-tl-value"></span>
							</div>
							<span class="fcc-tl-rating"></span>
						</div>
						<div class="fcc-tl-item" role="listitem" data-nutrient="salt">
							<div class="fcc-tl-dot" aria-hidden="true"></div>
							<div class="fcc-tl-info">
								<span class="fcc-tl-nutrient"><?php esc_html_e( 'Salt', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-tl-value"></span>
							</div>
							<span class="fcc-tl-rating"></span>
						</div>
					</div>
					<p class="fcc-tl-footnote"><?php esc_html_e( 'Based on FSA traffic-light thresholds per 100g', 'food-calorie-calculator' ); ?></p>
				</div>
				<?php endif; ?>

				<!-- Main nutrition table -->
				<div class="fcc-nutrition-table-wrapper">
					<table class="fcc-nutrition-table" aria-label="<?php esc_attr_e( 'Nutrition values', 'food-calorie-calculator' ); ?>">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Nutrient', 'food-calorie-calculator' ); ?></th>
								<th scope="col" class="fcc-col-per"><?php echo esc_html( $labels['per_label'] ?? __( 'Per', 'food-calorie-calculator' ) ); ?> <span class="fcc-serving-desc"></span></th>
								<th scope="col" class="fcc-col-per100"><?php esc_html_e( 'Per 100g', 'food-calorie-calculator' ); ?></th>
								<?php if ( ! empty( $features['ri_display'] ) ) : ?>
								<th scope="col" class="fcc-col-ri"><?php echo esc_html( $labels['ri_label'] ?? __( '% RI*', 'food-calorie-calculator' ) ); ?></th>
								<?php endif; ?>
							</tr>
						</thead>
						<tbody class="fcc-nutrients-body">
							<!-- Populated by JS -->
						</tbody>
					</table>
					<?php if ( ! empty( $features['ri_display'] ) ) : ?>
					<p class="fcc-ri-footnote"><?php echo esc_html( $labels['ri_footnote'] ?? __( '*Reference Intake of an average adult (8400kJ / 2000kcal)', 'food-calorie-calculator' ) ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Macro chart -->
				<?php if ( ! empty( $features['macro_chart'] ) ) : ?>
				<div class="fcc-macro-chart-wrapper" hidden>
					<canvas id="fcc-macro-chart" class="fcc-macro-chart" width="200" height="200"
						aria-label="<?php esc_attr_e( 'Macro breakdown chart', 'food-calorie-calculator' ); ?>" role="img"></canvas>
					<div class="fcc-macro-legend" aria-label="<?php esc_attr_e( 'Macro legend', 'food-calorie-calculator' ); ?>"></div>
				</div>
				<?php endif; ?>

				<!-- Omega-3 -->
				<?php if ( ! empty( $features['omega3_display'] ) ) : ?>
				<div class="fcc-omega3-section" hidden aria-live="polite">
					<h4 class="fcc-subsection-title"><?php echo esc_html( $labels['omega3_title'] ?? __( 'Omega-3 Fatty Acids', 'food-calorie-calculator' ) ); ?></h4>
					<div class="fcc-omega3-cards">
						<div class="fcc-omega3-card fcc-omega3-card--total" data-omega3="total">
							<span class="fcc-omega3-card__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="5"/></svg></span>
							<span class="fcc-omega3-val"></span>
							<span class="fcc-omega3-card__label"><?php esc_html_e( 'Total', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-omega3-card__sub"><?php esc_html_e( 'Omega-3', 'food-calorie-calculator' ); ?></span>
						</div>
						<div class="fcc-omega3-card fcc-omega3-card--ala" data-omega3="ala">
							<span class="fcc-omega3-card__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg></span>
							<span class="fcc-omega3-val"></span>
							<span class="fcc-omega3-card__label"><?php esc_html_e( 'ALA', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-omega3-card__sub"><?php esc_html_e( 'Plant source', 'food-calorie-calculator' ); ?></span>
						</div>
						<div class="fcc-omega3-card fcc-omega3-card--epa" data-omega3="epa">
							<span class="fcc-omega3-card__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M2 12c1.5-3 3-3 4.5 0s3 3 4.5 0 3-3 4.5 0 3-3 4.5 0"/></svg></span>
							<span class="fcc-omega3-val"></span>
							<span class="fcc-omega3-card__label"><?php esc_html_e( 'EPA', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-omega3-card__sub"><?php esc_html_e( 'Anti-inflammatory', 'food-calorie-calculator' ); ?></span>
						</div>
						<div class="fcc-omega3-card fcc-omega3-card--dha" data-omega3="dha">
							<span class="fcc-omega3-card__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
							<span class="fcc-omega3-val"></span>
							<span class="fcc-omega3-card__label"><?php esc_html_e( 'DHA', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-omega3-card__sub"><?php esc_html_e( 'Brain &amp; Heart', 'food-calorie-calculator' ); ?></span>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Caffeine -->
				<?php if ( ! empty( $features['caffeine_display'] ) ) : ?>
				<div class="fcc-caffeine-section" hidden aria-live="polite">
					<h4 class="fcc-subsection-title"><?php echo esc_html( $labels['caffeine_title'] ?? __( 'Caffeine', 'food-calorie-calculator' ) ); ?></h4>
					<p class="fcc-caffeine-value"></p>
				</div>
				<?php endif; ?>

				<!-- Affiliate buy buttons (populated by JS from cfg.affiliates) -->
				<div class="fcc-affiliate-links" hidden aria-label="<?php esc_attr_e( 'Buy this food online', 'food-calorie-calculator' ); ?>">
					<p class="fcc-affiliate-links__label"><?php esc_html_e( 'Buy online:', 'food-calorie-calculator' ); ?></p>
					<div class="fcc-affiliate-links__chips"></div>
				</div>

				<!-- Action buttons -->
				<div class="fcc-result-actions">
					<?php if ( ! empty( $features['print_pdf'] ) ) : ?>
					<button type="button" class="fcc-btn fcc-btn--ghost fcc-print-btn" aria-label="<?php esc_attr_e( 'Print results', 'food-calorie-calculator' ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
						<?php esc_html_e( 'Print', 'food-calorie-calculator' ); ?>
					</button>
					<?php endif; ?>
					<?php if ( ! empty( $features['share_link'] ) ) : ?>
					<button type="button" class="fcc-btn fcc-btn--ghost fcc-share-btn" aria-label="<?php esc_attr_e( 'Copy shareable link', 'food-calorie-calculator' ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
						<?php esc_html_e( 'Share', 'food-calorie-calculator' ); ?>
					</button>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( ! empty( $ad_slot_after_results ) ) : ?>
			<div class="fcc-ad-slot fcc-ad-slot--after-results"><?php echo $ad_slot_after_results; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>

		</div><!-- .fcc-tab-panel[calculator] -->

		<!-- ==================================================================
		     TAB PANEL 2: My Meal
		     ================================================================== -->
		<?php if ( ! empty( $features['meal_builder'] ) ) : ?>
		<div class="fcc-tab-panel" data-panel="meal" hidden>

			<!-- Empty state (shown when no items) -->
			<div class="fcc-meal-empty">
				<svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
				<p><?php esc_html_e( 'Your meal is empty', 'food-calorie-calculator' ); ?></p>
				<p class="fcc-meal-empty__hint"><?php esc_html_e( 'Go to "Food Lookup", select a food and click "+ Add to Meal" to start building.', 'food-calorie-calculator' ); ?></p>
			</div>

			<!-- Meal items (shown when ≥1 item) -->
			<section class="fcc-section fcc-meal-section" aria-label="<?php esc_attr_e( 'Meal builder', 'food-calorie-calculator' ); ?>" hidden>

				<!-- Rich header: title + live total kcal -->
				<div class="fcc-meal-header">
					<div class="fcc-meal-header__left">
						<div class="fcc-meal-header__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
						</div>
						<h3 class="fcc-meal-header__title"><?php echo esc_html( $labels['meal_title'] ?? __( 'Your Meal', 'food-calorie-calculator' ) ); ?></h3>
					</div>
					<div class="fcc-meal-header__kcal" aria-live="polite" aria-label="<?php esc_attr_e( 'Total calories', 'food-calorie-calculator' ); ?>">
						<span class="fcc-meal-total-kcal">0</span>
						<span class="fcc-meal-header__kcal-unit"><?php esc_html_e( 'kcal', 'food-calorie-calculator' ); ?></span>
					</div>
				</div>

				<!-- Macro summary pills (Protein / Carbs / Fat) -->
				<div class="fcc-meal-macros" hidden aria-label="<?php esc_attr_e( 'Macro summary', 'food-calorie-calculator' ); ?>">
					<div class="fcc-meal-macro fcc-meal-macro--protein">
						<span class="fcc-meal-macro__val fcc-meal-macro__val--protein">0g</span>
						<span class="fcc-meal-macro__label"><?php esc_html_e( 'Protein', 'food-calorie-calculator' ); ?></span>
					</div>
					<div class="fcc-meal-macro fcc-meal-macro--carbs">
						<span class="fcc-meal-macro__val fcc-meal-macro__val--carbs">0g</span>
						<span class="fcc-meal-macro__label"><?php esc_html_e( 'Carbs', 'food-calorie-calculator' ); ?></span>
					</div>
					<div class="fcc-meal-macro fcc-meal-macro--fat">
						<span class="fcc-meal-macro__val fcc-meal-macro__val--fat">0g</span>
						<span class="fcc-meal-macro__label"><?php esc_html_e( 'Fat', 'food-calorie-calculator' ); ?></span>
					</div>
					<div class="fcc-meal-macro fcc-meal-macro--fibre">
						<span class="fcc-meal-macro__val fcc-meal-macro__val--fibre">0g</span>
						<span class="fcc-meal-macro__label"><?php esc_html_e( 'Fibre', 'food-calorie-calculator' ); ?></span>
					</div>
				</div>

				<!-- Food items list -->
				<div class="fcc-meal-items" role="list" aria-label="<?php esc_attr_e( 'Meal items', 'food-calorie-calculator' ); ?>">
					<!-- Populated by JS -->
				</div>

				<!-- Full breakdown table -->
				<div class="fcc-meal-totals" hidden>
					<div class="fcc-meal-totals__hd">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
						<?php esc_html_e( 'Full Breakdown', 'food-calorie-calculator' ); ?>
					</div>
					<table class="fcc-nutrition-table fcc-meal-totals-table">
						<tbody class="fcc-meal-totals-body"></tbody>
					</table>
				</div>

			</section>

		</div><!-- .fcc-tab-panel[meal] -->
		<?php endif; ?>

		<!-- ==================================================================
		     TAB PANEL 3: Compare
		     ================================================================== -->
		<?php if ( $has_compare ) : ?>
		<div class="fcc-tab-panel" data-panel="compare" hidden>

			<!-- Intro callout -->
			<div class="fcc-compare-intro">
				<div class="fcc-compare-intro__icon" aria-hidden="true">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
				</div>
				<div class="fcc-compare-intro__text">
					<strong><?php esc_html_e( 'Compare Two Foods', 'food-calorie-calculator' ); ?></strong>
					<span><?php esc_html_e( 'Search both slots, then see nutrition side-by-side with winner highlights.', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

			<!-- Two food selector columns -->
			<div class="fcc-compare-grid">

				<!-- ── Slot A ─────────────────────────────────────── -->
				<div class="fcc-compare-col" data-slot="a">
					<div class="fcc-compare-col__hd">
						<span class="fcc-compare-badge fcc-compare-badge--a">A</span>
						<?php esc_html_e( 'First Food', 'food-calorie-calculator' ); ?>
					</div>
					<div class="fcc-compare-search-wrap">
						<svg class="fcc-compare-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<input type="search" class="fcc-compare-search" data-slot="a"
							placeholder="<?php esc_attr_e( 'Search a food…', 'food-calorie-calculator' ); ?>"
							autocomplete="off"
							aria-label="<?php esc_attr_e( 'Search first food', 'food-calorie-calculator' ); ?>" />
						<ul class="fcc-compare-dropdown" data-slot="a" role="listbox" hidden></ul>
					</div>
					<div class="fcc-compare-selected" data-slot="a" hidden>
						<div class="fcc-compare-food-pill fcc-compare-food-pill--a">
							<span class="fcc-compare-badge fcc-compare-badge--a fcc-compare-badge--xs">A</span>
							<span class="fcc-compare-food-name"></span>
						</div>
						<div class="fcc-quantity-controls fcc-compare-qty-controls">
							<div class="fcc-qty-group">
								<label class="fcc-label"><?php esc_html_e( 'Quantity', 'food-calorie-calculator' ); ?></label>
								<input type="number" class="fcc-quantity-input fcc-compare-qty" data-slot="a"
									value="100" min="1" max="9999" step="1"
									aria-label="<?php esc_attr_e( 'Quantity for food A', 'food-calorie-calculator' ); ?>" />
							</div>
							<div class="fcc-qty-group">
								<label class="fcc-label" id="fcc-cu-label-a"><?php esc_html_e( 'Unit', 'food-calorie-calculator' ); ?></label>
								<select class="fcc-compare-unit-select" data-slot="a"
									aria-hidden="true" tabindex="-1"
									style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;overflow:hidden;">
									<option value="g"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></option>
								</select>
								<div class="fcc-unit-custom fcc-compare-unit-custom" data-slot="a"
									role="combobox" aria-labelledby="fcc-cu-label-a" aria-expanded="false" aria-haspopup="listbox">
									<button type="button" class="fcc-unit-trigger" aria-haspopup="listbox">
										<span class="fcc-unit-trigger__text"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></span>
										<svg class="fcc-unit-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
									</button>
									<ul class="fcc-unit-options" role="listbox" aria-labelledby="fcc-cu-label-a"></ul>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- VS divider -->
				<div class="fcc-compare-vs" aria-hidden="true"><span>vs</span></div>

				<!-- ── Slot B ─────────────────────────────────────── -->
				<div class="fcc-compare-col" data-slot="b">
					<div class="fcc-compare-col__hd">
						<span class="fcc-compare-badge fcc-compare-badge--b">B</span>
						<?php esc_html_e( 'Second Food', 'food-calorie-calculator' ); ?>
					</div>
					<div class="fcc-compare-search-wrap">
						<svg class="fcc-compare-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<input type="search" class="fcc-compare-search" data-slot="b"
							placeholder="<?php esc_attr_e( 'Search a food…', 'food-calorie-calculator' ); ?>"
							autocomplete="off"
							aria-label="<?php esc_attr_e( 'Search second food', 'food-calorie-calculator' ); ?>" />
						<ul class="fcc-compare-dropdown" data-slot="b" role="listbox" hidden></ul>
					</div>
					<div class="fcc-compare-selected" data-slot="b" hidden>
						<div class="fcc-compare-food-pill fcc-compare-food-pill--b">
							<span class="fcc-compare-badge fcc-compare-badge--b fcc-compare-badge--xs">B</span>
							<span class="fcc-compare-food-name"></span>
						</div>
						<div class="fcc-quantity-controls fcc-compare-qty-controls">
							<div class="fcc-qty-group">
								<label class="fcc-label"><?php esc_html_e( 'Quantity', 'food-calorie-calculator' ); ?></label>
								<input type="number" class="fcc-quantity-input fcc-compare-qty" data-slot="b"
									value="100" min="1" max="9999" step="1"
									aria-label="<?php esc_attr_e( 'Quantity for food B', 'food-calorie-calculator' ); ?>" />
							</div>
							<div class="fcc-qty-group">
								<label class="fcc-label" id="fcc-cu-label-b"><?php esc_html_e( 'Unit', 'food-calorie-calculator' ); ?></label>
								<select class="fcc-compare-unit-select" data-slot="b"
									aria-hidden="true" tabindex="-1"
									style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;overflow:hidden;">
									<option value="g"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></option>
								</select>
								<div class="fcc-unit-custom fcc-compare-unit-custom" data-slot="b"
									role="combobox" aria-labelledby="fcc-cu-label-b" aria-expanded="false" aria-haspopup="listbox">
									<button type="button" class="fcc-unit-trigger" aria-haspopup="listbox">
										<span class="fcc-unit-trigger__text"><?php esc_html_e( 'grams (g)', 'food-calorie-calculator' ); ?></span>
										<svg class="fcc-unit-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
									</button>
									<ul class="fcc-unit-options" role="listbox" aria-labelledby="fcc-cu-label-b"></ul>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div><!-- /.fcc-compare-grid -->

			<!-- Results (hidden until both foods are selected) -->
			<div class="fcc-compare-results" hidden>

				<!-- Header: Food A vs Food B -->
				<div class="fcc-compare-results-hd">
					<div class="fcc-compare-results-hd__col fcc-compare-results-hd__col--a">
						<span class="fcc-compare-badge fcc-compare-badge--a">A</span>
						<span class="fcc-compare-results-hd__name fcc-compare-results-hd__name--a"></span>
					</div>
					<div class="fcc-compare-results-hd__vs">vs</div>
					<div class="fcc-compare-results-hd__col fcc-compare-results-hd__col--b">
						<span class="fcc-compare-badge fcc-compare-badge--b">B</span>
						<span class="fcc-compare-results-hd__name fcc-compare-results-hd__name--b"></span>
					</div>
				</div>

				<!-- FSA Traffic Lights side-by-side -->
				<?php if ( ! empty( $features['fsa_traffic_lights'] ) ) : ?>
				<div class="fcc-compare-fsa-row">
					<div class="fcc-compare-fsa-col" data-slot="a"></div>
					<div class="fcc-compare-fsa-col" data-slot="b"></div>
				</div>
				<?php endif; ?>

				<!-- Nutrient comparison table -->
				<div class="fcc-compare-table-wrap">
					<table class="fcc-compare-table" aria-label="<?php esc_attr_e( 'Nutrition comparison', 'food-calorie-calculator' ); ?>">
						<thead>
							<tr>
								<th class="fcc-compare-th-nutrient"><?php esc_html_e( 'Nutrient', 'food-calorie-calculator' ); ?></th>
								<th class="fcc-compare-th fcc-compare-th--a">
									<span class="fcc-compare-badge fcc-compare-badge--a fcc-compare-badge--sm">A</span>
									<span class="fcc-compare-th__name fcc-compare-th__name--a"></span>
								</th>
								<th class="fcc-compare-th fcc-compare-th--b">
									<span class="fcc-compare-badge fcc-compare-badge--b fcc-compare-badge--sm">B</span>
									<span class="fcc-compare-th__name fcc-compare-th__name--b"></span>
								</th>
							</tr>
						</thead>
						<tbody class="fcc-compare-tbody"></tbody>
					</table>
				</div>

				<!-- Win/loss summary bar -->
				<div class="fcc-compare-summary"></div>

				<!-- Actions -->
				<div class="fcc-compare-actions">
					<button type="button" class="fcc-btn fcc-btn--ghost fcc-compare-reset-btn"
						aria-label="<?php esc_attr_e( 'Reset comparison', 'food-calorie-calculator' ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.45"/></svg>
						<?php esc_html_e( 'Reset', 'food-calorie-calculator' ); ?>
					</button>
					<?php if ( ! empty( $features['share_link'] ) ) : ?>
					<button type="button" class="fcc-btn fcc-btn--ghost fcc-compare-share-btn"
						aria-label="<?php esc_attr_e( 'Copy shareable comparison link', 'food-calorie-calculator' ); ?>">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
						<?php esc_html_e( 'Share Comparison', 'food-calorie-calculator' ); ?>
					</button>
					<?php endif; ?>
				</div>

			</div><!-- /.fcc-compare-results -->

		</div><!-- .fcc-tab-panel[compare] -->
		<?php endif; ?>

		<!-- ==================================================================
		     TAB PANEL 2: Daily Calorie Need (BMR/TDEE)
		     ================================================================== -->
		<?php if ( $has_bmr ) : ?>
		<div class="fcc-tab-panel" data-panel="bmr" hidden>
			<section class="fcc-section fcc-bmr-section" aria-label="<?php esc_attr_e( 'Daily calorie need calculator', 'food-calorie-calculator' ); ?>">

				<!-- Gradient header -->
				<div class="fcc-bmr-header">
					<div class="fcc-bmr-header__left">
						<div class="fcc-bmr-header__icon" aria-hidden="true">
							<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2c0 0-5 7-5 11a5 5 0 0 0 10 0c0-4-5-11-5-11z"/><path d="M12 17a2 2 0 0 1-2-2c0-1.5 2-4 2-4s2 2.5 2 4a2 2 0 0 1-2 2z" fill="currentColor" stroke="none"/></svg>
						</div>
						<div>
							<h3 class="fcc-bmr-header__title"><?php echo esc_html( $labels['bmr_title'] ?? __( 'Daily Calorie Need', 'food-calorie-calculator' ) ); ?></h3>
							<p class="fcc-bmr-header__sub"><?php esc_html_e( 'Mifflin-St Jeor formula · TDEE', 'food-calorie-calculator' ); ?></p>
						</div>
					</div>
				</div>

				<div class="fcc-bmr-form">

					<!-- 4 core inputs: 2-column grid -->
					<div class="fcc-bmr-grid">
						<div class="fcc-bmr-row">
							<label for="fcc-bmr-sex" class="fcc-bmr-field-label">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
								<?php esc_html_e( 'Biological Sex', 'food-calorie-calculator' ); ?>
							</label>
							<select id="fcc-bmr-sex" class="fcc-bmr-sex" hidden aria-hidden="true">
								<option value="male"><?php esc_html_e( 'Male', 'food-calorie-calculator' ); ?></option>
								<option value="female"><?php esc_html_e( 'Female', 'food-calorie-calculator' ); ?></option>
							</select>
							<div class="fcc-bmr-sex-toggle" role="group" aria-label="<?php esc_attr_e( 'Biological Sex', 'food-calorie-calculator' ); ?>">
								<button type="button" class="fcc-bmr-sex-btn fcc-bmr-sex-btn--active" data-sex="male">
									<span class="fcc-bmr-sex-btn__icon" aria-hidden="true">
										<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="10" cy="10" r="6"/><line x1="19" y1="5" x2="14.65" y2="9.35"/><polyline points="15 5 19 5 19 9"/></svg>
									</span>
									<?php esc_html_e( 'Male', 'food-calorie-calculator' ); ?>
								</button>
								<button type="button" class="fcc-bmr-sex-btn" data-sex="female">
									<span class="fcc-bmr-sex-btn__icon" aria-hidden="true">
										<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><line x1="12" y1="15" x2="12" y2="22"/><line x1="9" y1="19" x2="15" y2="19"/></svg>
									</span>
									<?php esc_html_e( 'Female', 'food-calorie-calculator' ); ?>
								</button>
							</div>
						</div>
						<div class="fcc-bmr-row">
							<label for="fcc-bmr-age" class="fcc-bmr-field-label">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
								<?php esc_html_e( 'Age', 'food-calorie-calculator' ); ?>
							</label>
							<div class="fcc-bmr-input-box">
								<input type="number" id="fcc-bmr-age" class="fcc-bmr-age fcc-bmr-num-input" min="15" max="99" value="30" placeholder="30">
								<span class="fcc-bmr-input-box__unit"><?php esc_html_e( 'yrs', 'food-calorie-calculator' ); ?></span>
							</div>
						</div>
						<div class="fcc-bmr-row">
							<div class="fcc-bmr-field-label-wrap">
								<label for="fcc-bmr-height" class="fcc-bmr-field-label">
									<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 3H3v18h18V3z"/><path d="M9 7h2M9 11h4M9 15h2"/></svg>
									<?php esc_html_e( 'Height', 'food-calorie-calculator' ); ?>
								</label>
								<span class="fcc-bmr-unit-toggle" role="group" aria-label="<?php esc_attr_e( 'Height unit', 'food-calorie-calculator' ); ?>">
									<button type="button" class="fcc-bmr-height-unit-btn fcc-bmr-height-unit-btn--active" data-unit="cm">cm</button>
									<button type="button" class="fcc-bmr-height-unit-btn" data-unit="in">in</button>
								</span>
							</div>
							<div class="fcc-bmr-input-box">
								<input type="number" id="fcc-bmr-height" class="fcc-bmr-height fcc-bmr-num-input" min="100" max="250" value="170" placeholder="170" step="0.1">
								<span class="fcc-bmr-input-box__unit fcc-bmr-height-unit-display">cm</span>
							</div>
						</div>
						<div class="fcc-bmr-row">
							<label for="fcc-bmr-weight" class="fcc-bmr-field-label">
								<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12l1 7H5L6 3z"/><path d="M5 10v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9"/><path d="M12 10v5m-2-3h4"/></svg>
								<?php esc_html_e( 'Weight', 'food-calorie-calculator' ); ?>
							</label>
							<div class="fcc-bmr-input-box">
								<input type="number" id="fcc-bmr-weight" class="fcc-bmr-weight fcc-bmr-num-input" min="30" max="300" value="70" placeholder="70">
								<span class="fcc-bmr-input-box__unit"><?php esc_html_e( 'kg', 'food-calorie-calculator' ); ?></span>
							</div>
						</div>
					</div>

					<!-- Activity level visual cards (synced to hidden select) -->
					<div class="fcc-bmr-field--full">
						<span class="fcc-bmr-field-label fcc-bmr-field-label--block">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
							<?php esc_html_e( 'Activity Level', 'food-calorie-calculator' ); ?>
						</span>
						<select id="fcc-bmr-activity" class="fcc-bmr-activity" hidden aria-hidden="true">
							<option value="1.2"><?php esc_html_e( 'Sedentary',  'food-calorie-calculator' ); ?></option>
							<option value="1.375"><?php esc_html_e( 'Light',     'food-calorie-calculator' ); ?></option>
							<option value="1.55" selected><?php esc_html_e( 'Moderate', 'food-calorie-calculator' ); ?></option>
							<option value="1.725"><?php esc_html_e( 'Active',    'food-calorie-calculator' ); ?></option>
							<option value="1.9"><?php esc_html_e( 'Very Active','food-calorie-calculator' ); ?></option>
						</select>
						<div class="fcc-bmr-act-cards" role="group" aria-label="<?php esc_attr_e( 'Activity Level', 'food-calorie-calculator' ); ?>">
							<button type="button" class="fcc-bmr-act-card" data-activity="1.2">
								<span class="fcc-bmr-act-card__emoji" aria-hidden="true">🛋️</span>
								<span class="fcc-bmr-act-card__name"><?php esc_html_e( 'Sedentary', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-act-card__desc"><?php esc_html_e( 'Little/no exercise', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-act-card" data-activity="1.375">
								<span class="fcc-bmr-act-card__emoji" aria-hidden="true">🚶</span>
								<span class="fcc-bmr-act-card__name"><?php esc_html_e( 'Light', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-act-card__desc"><?php esc_html_e( '1–3 days/week', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-act-card fcc-bmr-act-card--active" data-activity="1.55">
								<span class="fcc-bmr-act-card__emoji" aria-hidden="true">🏃</span>
								<span class="fcc-bmr-act-card__name"><?php esc_html_e( 'Moderate', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-act-card__desc"><?php esc_html_e( '3–5 days/week', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-act-card" data-activity="1.725">
								<span class="fcc-bmr-act-card__emoji" aria-hidden="true">💪</span>
								<span class="fcc-bmr-act-card__name"><?php esc_html_e( 'Active', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-act-card__desc"><?php esc_html_e( '6–7 days/week', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-act-card" data-activity="1.9">
								<span class="fcc-bmr-act-card__emoji" aria-hidden="true">🏋️</span>
								<span class="fcc-bmr-act-card__name"><?php esc_html_e( 'Very Active', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-act-card__desc"><?php esc_html_e( 'Twice daily', 'food-calorie-calculator' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Goal pill selector (synced to hidden select) -->
					<div class="fcc-bmr-field--full">
						<span class="fcc-bmr-field-label fcc-bmr-field-label--block">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
							<?php esc_html_e( 'Goal', 'food-calorie-calculator' ); ?>
						</span>
						<select id="fcc-bmr-goal" class="fcc-bmr-goal" hidden aria-hidden="true">
							<option value="lose"><?php esc_html_e( 'Lose weight', 'food-calorie-calculator' ); ?></option>
							<option value="maintain" selected><?php esc_html_e( 'Maintain weight', 'food-calorie-calculator' ); ?></option>
							<option value="gain"><?php esc_html_e( 'Gain weight', 'food-calorie-calculator' ); ?></option>
						</select>
						<div class="fcc-bmr-goal-pills" role="group" aria-label="<?php esc_attr_e( 'Goal', 'food-calorie-calculator' ); ?>">
							<button type="button" class="fcc-bmr-goal-pill fcc-bmr-goal-pill--lose" data-goal="lose">
								<span class="fcc-bmr-goal-pill__arrow" aria-hidden="true">↓</span>
								<span class="fcc-bmr-goal-pill__name"><?php esc_html_e( 'Lose Weight', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-goal-pill__sub"><?php esc_html_e( '−500 kcal/day', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-goal-pill fcc-bmr-goal-pill--maintain fcc-bmr-goal-pill--active" data-goal="maintain">
								<span class="fcc-bmr-goal-pill__arrow" aria-hidden="true">→</span>
								<span class="fcc-bmr-goal-pill__name"><?php esc_html_e( 'Maintain', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-goal-pill__sub"><?php esc_html_e( 'Stay the same', 'food-calorie-calculator' ); ?></span>
							</button>
							<button type="button" class="fcc-bmr-goal-pill fcc-bmr-goal-pill--gain" data-goal="gain">
								<span class="fcc-bmr-goal-pill__arrow" aria-hidden="true">↑</span>
								<span class="fcc-bmr-goal-pill__name"><?php esc_html_e( 'Gain Weight', 'food-calorie-calculator' ); ?></span>
								<span class="fcc-bmr-goal-pill__sub"><?php esc_html_e( '+500 kcal/day', 'food-calorie-calculator' ); ?></span>
							</button>
						</div>
					</div>

					<!-- Full-width calculate button -->
					<button type="button" class="fcc-btn fcc-btn--primary fcc-btn--block fcc-bmr-calculate">
						<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
						<?php esc_html_e( 'Calculate my TDEE', 'food-calorie-calculator' ); ?>
					</button>

					<!-- Rich result card -->
					<div class="fcc-bmr-result" aria-live="polite" hidden>
						<div class="fcc-bmr-result-card">
							<div class="fcc-bmr-result-top">
								<div class="fcc-bmr-result-top__left">
									<span class="fcc-bmr-result-top__label"><?php esc_html_e( 'Daily Target', 'food-calorie-calculator' ); ?></span>
									<div class="fcc-bmr-result-top__kcal">
										<span class="fcc-bmr-tdee-display">0</span>
										<span class="fcc-bmr-result-top__unit"><?php esc_html_e( 'kcal/day', 'food-calorie-calculator' ); ?></span>
									</div>
								</div>
								<div class="fcc-bmr-result-top__right">
									<span class="fcc-bmr-result-top__bmr-label"><?php esc_html_e( 'Base BMR', 'food-calorie-calculator' ); ?></span>
									<strong class="fcc-bmr-bmr-val">—</strong>
									<span class="fcc-bmr-result-top__bmr-unit"><?php esc_html_e( 'kcal', 'food-calorie-calculator' ); ?></span>
								</div>
							</div>
							<div class="fcc-bmr-stats-row">
								<div class="fcc-bmr-stat fcc-bmr-stat--lose">
									<span class="fcc-bmr-stat__arrow" aria-hidden="true">↓</span>
									<span class="fcc-bmr-stat__label"><?php esc_html_e( 'Lose', 'food-calorie-calculator' ); ?></span>
									<strong class="fcc-bmr-stat__val fcc-bmr-kcal-lose">—</strong>
								</div>
								<div class="fcc-bmr-stat fcc-bmr-stat--maintain">
									<span class="fcc-bmr-stat__arrow" aria-hidden="true">→</span>
									<span class="fcc-bmr-stat__label"><?php esc_html_e( 'Maintain', 'food-calorie-calculator' ); ?></span>
									<strong class="fcc-bmr-stat__val fcc-bmr-kcal-maintain">—</strong>
								</div>
								<div class="fcc-bmr-stat fcc-bmr-stat--gain">
									<span class="fcc-bmr-stat__arrow" aria-hidden="true">↑</span>
									<span class="fcc-bmr-stat__label"><?php esc_html_e( 'Gain', 'food-calorie-calculator' ); ?></span>
									<strong class="fcc-bmr-stat__val fcc-bmr-kcal-gain">—</strong>
								</div>
							</div>
							<?php if ( ! empty( $features['daily_needs_comparison'] ) ) : ?>
							<p class="fcc-bmr-comparison"></p>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</section>
		</div><!-- .fcc-tab-panel[bmr] -->
		<?php endif; ?>

	</div><!-- .fcc-tabs-body -->

</div><!-- .fcc-calculator -->
