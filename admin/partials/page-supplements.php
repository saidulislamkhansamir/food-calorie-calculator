<?php
/**
 * Lead Gen for Supplements — admin page.
 *
 * @package FCC\Admin
 */

defined( 'ABSPATH' ) || exit;

use FCC\Admin\Supplements;

$config      = Supplements::get_config();
$catalog     = Supplements::get_catalog_with_stats();
$rules       = Supplements::get_rules();
$stats       = Supplements::get_stats();
$cat_defs    = Supplements::get_category_definitions();
$net_defs    = Supplements::get_network_definitions();
$nutr_labels = Supplements::get_nutrient_field_labels();

// Aggregate stats.
$total_supp     = count( $catalog );
$active_count   = count( array_filter( $catalog, fn( $s ) => $s['status'] === 'active' ) );
$total_clicks   = array_sum( array_column( array_values( $stats ), 'clicks' ) );
$total_impr     = array_sum( array_column( array_values( $stats ), 'impressions' ) );
$avg_ctr        = $total_impr > 0 ? round( $total_clicks / $total_impr * 100, 1 ) : 0;
$est_revenue    = round( $total_clicks * (float) ( $config['avg_commission'] ?? 0.50 ), 2 );

// Top performers.
$with_stats = $catalog;
usort( $with_stats, fn( $a, $b ) => ( $b['clicks'] ?? 0 ) <=> ( $a['clicks'] ?? 0 ) );
$top_performers = array_slice( $with_stats, 0, 5 );

$supp_nonce_field = wp_create_nonce( 'fcc_supplements_nonce' );
?>

<div class="wrap fcc-supps-page">

	<!-- ─────────────────────────────────────────────────────────────── HERO -->
	<div class="fcc-supps-hero">
		<div class="fcc-supps-hero__inner">
			<div class="fcc-supps-hero__left">
				<div class="fcc-supps-hero__icon">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
				</div>
				<div>
					<h1 class="fcc-supps-hero__title"><?php esc_html_e( 'Lead Gen for Supplements', 'food-calorie-calculator' ); ?></h1>
					<p class="fcc-supps-hero__sub"><?php esc_html_e( 'Show contextual supplement suggestions when users search high-protein, vitamin-rich, or sports foods', 'food-calorie-calculator' ); ?></p>
				</div>
			</div>
			<div class="fcc-supps-hero__stats">
				<div class="fcc-supps-hero__stat">
					<span class="fcc-supps-hero__stat-val"><?php echo esc_html( $active_count ); ?></span>
					<span class="fcc-supps-hero__stat-lbl"><?php esc_html_e( 'Active Supplements', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-hero__stat">
					<span class="fcc-supps-hero__stat-val"><?php echo esc_html( count( array_filter( $rules, fn( $r ) => ! empty( $r['enabled'] ) ) ) ); ?></span>
					<span class="fcc-supps-hero__stat-lbl"><?php esc_html_e( 'Active Rules', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-hero__stat">
					<span class="fcc-supps-hero__stat-val"><?php echo esc_html( number_format( $total_clicks ) ); ?></span>
					<span class="fcc-supps-hero__stat-lbl"><?php esc_html_e( 'Total Clicks', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-hero__stat">
					<span class="fcc-supps-hero__stat-val"><?php echo esc_html( $avg_ctr ); ?>%</span>
					<span class="fcc-supps-hero__stat-lbl"><?php esc_html_e( 'Avg CTR', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-hero__stat fcc-supps-hero__stat--revenue">
					<span class="fcc-supps-hero__stat-val">£<?php echo esc_html( number_format( $est_revenue, 2 ) ); ?></span>
					<span class="fcc-supps-hero__stat-lbl"><?php esc_html_e( 'Est. Revenue', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- ──────────────────────────────────────────── PERFORMANCE PANEL -->
	<?php if ( $total_clicks > 0 || $total_impr > 0 ) : ?>
	<div class="fcc-supps-section fcc-card">
		<div class="fcc-card__header">
			<h2 class="fcc-card__title">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
				<?php esc_html_e( 'Performance', 'food-calorie-calculator' ); ?>
			</h2>
			<p class="fcc-card__desc"><?php esc_html_e( 'Top supplement click performance across all triggers.', 'food-calorie-calculator' ); ?></p>
		</div>
		<div class="fcc-card__body fcc-supps-perf">
			<div class="fcc-supps-perf__overview">
				<div class="fcc-supps-perf__kpi">
					<span class="fcc-supps-perf__kpi-val"><?php echo esc_html( number_format( $total_impr ) ); ?></span>
					<span class="fcc-supps-perf__kpi-lbl"><?php esc_html_e( 'Impressions', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-perf__kpi">
					<span class="fcc-supps-perf__kpi-val"><?php echo esc_html( number_format( $total_clicks ) ); ?></span>
					<span class="fcc-supps-perf__kpi-lbl"><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-perf__kpi">
					<span class="fcc-supps-perf__kpi-val"><?php echo esc_html( $avg_ctr ); ?>%</span>
					<span class="fcc-supps-perf__kpi-lbl"><?php esc_html_e( 'Avg CTR', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-supps-perf__kpi fcc-supps-perf__kpi--rev">
					<span class="fcc-supps-perf__kpi-val">£<?php echo esc_html( number_format( $est_revenue, 2 ) ); ?></span>
					<span class="fcc-supps-perf__kpi-lbl"><?php esc_html_e( 'Est. Revenue', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
			<table class="fcc-supps-perf__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Supplement', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'Impressions', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'Clicks', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'CTR', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'Est. Revenue', 'food-calorie-calculator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $top_performers as $tp ) :
						$cat_label = $cat_defs[ $tp['category'] ]['label'] ?? $tp['category'];
						$cat_col   = $cat_defs[ $tp['category'] ]['colour'] ?? '#607D8B';
						$tp_rev    = round( ( $tp['clicks'] ?? 0 ) * (float) ( $config['avg_commission'] ?? 0.50 ), 2 );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $tp['name'] ); ?></strong>
							<span class="fcc-supps-perf__brand"><?php echo esc_html( $tp['brand'] ); ?></span>
						</td>
						<td><span class="fcc-supps-cat-badge" style="background:<?php echo esc_attr( $cat_col ); ?>;"><?php echo esc_html( $cat_label ); ?></span></td>
						<td><?php echo esc_html( number_format( $tp['impressions'] ?? 0 ) ); ?></td>
						<td><strong><?php echo esc_html( number_format( $tp['clicks'] ?? 0 ) ); ?></strong></td>
						<td><?php echo esc_html( $tp['ctr'] ?? 0 ); ?>%</td>
						<td>£<?php echo esc_html( number_format( $tp_rev, 2 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<!-- ──────────────────────────────────────────── GLOBAL CONFIG -->
	<div class="fcc-supps-section fcc-card" id="fcc-supps-config-card">
		<div class="fcc-card__header">
			<h2 class="fcc-card__title">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M4.93 4.93l1.41 1.41M18.66 18.66l1.41 1.41M2 12h2M20 12h2M12 2v2M12 20v2"/></svg>
				<?php esc_html_e( 'Display Settings', 'food-calorie-calculator' ); ?>
			</h2>
		</div>
		<div class="fcc-card__body">
			<div class="fcc-supps-config-grid">

				<label class="fcc-supps-toggle-row" for="cfg-enabled">
					<span class="fcc-aff-toggle">
						<input type="checkbox" id="cfg-enabled" data-cfg="enabled" value="1" <?php checked( $config['enabled'] ); ?>>
						<span class="fcc-aff-toggle__track"></span>
					</span>
					<span>
						<strong><?php esc_html_e( 'Enable Supplement Lead Gen', 'food-calorie-calculator' ); ?></strong>
						<span class="fcc-supps-hint"><?php esc_html_e( 'Show supplement suggestions when trigger rules match.', 'food-calorie-calculator' ); ?></span>
					</span>
				</label>

				<div class="fcc-supps-config-row">
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-position"><?php esc_html_e( 'Display Position', 'food-calorie-calculator' ); ?></label>
						<select id="cfg-position" data-cfg="position" class="fcc-supps-select">
							<option value="after_results"    <?php selected( $config['position'], 'after_results' );    ?>><?php esc_html_e( 'After Results Panel', 'food-calorie-calculator' ); ?></option>
							<option value="below_calculator" <?php selected( $config['position'], 'below_calculator' ); ?>><?php esc_html_e( 'Below Calculator Widget', 'food-calorie-calculator' ); ?></option>
						</select>
					</div>
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-max-sugg"><?php esc_html_e( 'Max Suggestions Shown', 'food-calorie-calculator' ); ?></label>
						<select id="cfg-max-sugg" data-cfg="max_sugg" class="fcc-supps-select">
							<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $config['max_sugg'], $i ); ?>><?php echo esc_html( $i ); ?></option>
							<?php endfor; ?>
						</select>
					</div>
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-display-style"><?php esc_html_e( 'Card Style', 'food-calorie-calculator' ); ?></label>
						<select id="cfg-display-style" data-cfg="display_style" class="fcc-supps-select">
							<option value="card"    <?php selected( $config['display_style'], 'card' );    ?>><?php esc_html_e( 'Full Card', 'food-calorie-calculator' ); ?></option>
							<option value="compact" <?php selected( $config['display_style'], 'compact' ); ?>><?php esc_html_e( 'Compact Row', 'food-calorie-calculator' ); ?></option>
						</select>
					</div>
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-cta"><?php esc_html_e( 'CTA Button Text', 'food-calorie-calculator' ); ?></label>
						<input type="text" id="cfg-cta" data-cfg="cta_text" class="regular-text" value="<?php echo esc_attr( $config['cta_text'] ); ?>">
					</div>
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-heading"><?php esc_html_e( 'Section Heading', 'food-calorie-calculator' ); ?></label>
						<input type="text" id="cfg-heading" data-cfg="heading" class="regular-text" value="<?php echo esc_attr( $config['heading'] ); ?>">
					</div>
					<div class="fcc-supps-config-field">
						<label class="fcc-form-label" for="cfg-avg-comm"><?php esc_html_e( 'Avg Commission per Click (£)', 'food-calorie-calculator' ); ?></label>
						<input type="number" id="cfg-avg-comm" data-cfg="avg_commission" class="small-text" step="0.01" min="0" value="<?php echo esc_attr( $config['avg_commission'] ); ?>">
						<p class="description"><?php esc_html_e( 'Used to estimate revenue in stats.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>

				<div class="fcc-supps-config-row fcc-supps-config-row--toggles">
					<label class="fcc-supps-toggle-row" for="cfg-show-price">
						<span class="fcc-aff-toggle">
							<input type="checkbox" id="cfg-show-price" data-cfg="show_price" value="1" <?php checked( $config['show_price'] ); ?>>
							<span class="fcc-aff-toggle__track"></span>
						</span>
						<span><?php esc_html_e( 'Show Price on Card', 'food-calorie-calculator' ); ?></span>
					</label>
					<label class="fcc-supps-toggle-row" for="cfg-show-network">
						<span class="fcc-aff-toggle">
							<input type="checkbox" id="cfg-show-network" data-cfg="show_network" value="1" <?php checked( $config['show_network'] ); ?>>
							<span class="fcc-aff-toggle__track"></span>
						</span>
						<span><?php esc_html_e( 'Show Retailer Badge', 'food-calorie-calculator' ); ?></span>
					</label>
				</div>

				<div class="fcc-supps-config-field fcc-supps-config-field--full">
					<label class="fcc-form-label" for="cfg-disclosure"><?php esc_html_e( 'Affiliate Disclosure Text', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="cfg-disclosure" data-cfg="disclosure" class="large-text" value="<?php echo esc_attr( $config['disclosure'] ); ?>">
				</div>

			</div>
		</div>
	</div>

	<!-- ──────────────────────────────────────────── TRIGGER RULES -->
	<div class="fcc-supps-section fcc-card" id="fcc-rules-card">
		<div class="fcc-card__header">
			<h2 class="fcc-card__title">
				<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
				<?php esc_html_e( 'Smart Trigger Rules', 'food-calorie-calculator' ); ?>
			</h2>
			<p class="fcc-card__desc"><?php esc_html_e( 'Define when supplement suggestions appear — based on nutrient thresholds, food keywords, or category matches.', 'food-calorie-calculator' ); ?></p>
		</div>
		<div class="fcc-card__body">

			<div id="fcc-rules-list" class="fcc-supps-rules-list">
				<!-- Rendered by JS -->
			</div>

			<button type="button" id="fcc-add-rule-btn" class="fcc-supps-add-btn">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
				<?php esc_html_e( 'Add Trigger Rule', 'food-calorie-calculator' ); ?>
			</button>

		</div>
	</div>

	<!-- ──────────────────────────────────────────── SUPPLEMENT CATALOG -->
	<div class="fcc-supps-section fcc-card" id="fcc-catalog-card">
		<div class="fcc-card__header fcc-catalog-header">
			<div>
				<h2 class="fcc-card__title">
					<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
					<?php esc_html_e( 'Supplement Catalog', 'food-calorie-calculator' ); ?>
					<span class="fcc-supps-count-badge" id="fcc-catalog-count"><?php echo esc_html( $total_supp ); ?></span>
				</h2>
				<p class="fcc-card__desc"><?php esc_html_e( 'Manage the supplements shown to users. Add your affiliate URLs to each supplement.', 'food-calorie-calculator' ); ?></p>
			</div>
			<button type="button" id="fcc-add-supp-btn" class="button button-primary">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
				<?php esc_html_e( 'Add Supplement', 'food-calorie-calculator' ); ?>
			</button>
		</div>

		<!-- Add supplement form (hidden by default) -->
		<div id="fcc-supp-form-panel" class="fcc-supps-form-panel" hidden>
			<h3 class="fcc-supps-form-panel__title" id="fcc-supp-form-title"><?php esc_html_e( 'Add New Supplement', 'food-calorie-calculator' ); ?></h3>
			<input type="hidden" id="fcc-supp-edit-idx" value="">
			<div class="fcc-supps-form-grid">
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Supplement Name *', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-sf-name" class="regular-text" placeholder="e.g. Whey Protein Isolate">
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Brand', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-sf-brand" class="regular-text" placeholder="e.g. Optimum Nutrition">
				</div>
				<div class="fcc-supps-form-field fcc-supps-form-field--wide">
					<label class="fcc-form-label"><?php esc_html_e( 'Tagline / Short Description', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-sf-tagline" class="large-text" placeholder="e.g. 24g protein per scoop. Fast-absorbing whey.">
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></label>
					<select id="fcc-sf-category" class="fcc-supps-select">
						<?php foreach ( $cat_defs as $slug => $def ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $def['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Retailer / Network', 'food-calorie-calculator' ); ?></label>
					<select id="fcc-sf-network" class="fcc-supps-select">
						<?php foreach ( $net_defs as $slug => $def ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $def['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="fcc-supps-form-field fcc-supps-form-field--wide">
					<label class="fcc-form-label"><?php esc_html_e( 'Affiliate URL *', 'food-calorie-calculator' ); ?></label>
					<input type="url" id="fcc-sf-url" class="large-text" placeholder="https://amzn.to/xxxxx or full Amazon affiliate URL">
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Price (display only)', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-sf-price" class="small-text" placeholder="£24.99">
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Badge Label (optional)', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-sf-badge" class="regular-text" placeholder="Best Seller">
				</div>
				<div class="fcc-supps-form-field fcc-supps-form-field--wide">
					<label class="fcc-form-label"><?php esc_html_e( 'Product Image URL (optional)', 'food-calorie-calculator' ); ?></label>
					<input type="url" id="fcc-sf-image" class="large-text" placeholder="https://...">
				</div>
				<div class="fcc-supps-form-field">
					<label class="fcc-form-label"><?php esc_html_e( 'Status', 'food-calorie-calculator' ); ?></label>
					<select id="fcc-sf-status" class="fcc-supps-select">
						<option value="active"><?php esc_html_e( 'Active', 'food-calorie-calculator' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'Inactive', 'food-calorie-calculator' ); ?></option>
					</select>
				</div>
			</div>
			<div class="fcc-supps-form-actions">
				<button type="button" id="fcc-supp-form-save" class="button button-primary"><?php esc_html_e( 'Save Supplement', 'food-calorie-calculator' ); ?></button>
				<button type="button" id="fcc-supp-form-cancel" class="button"><?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?></button>
			</div>
		</div>

		<div class="fcc-card__body">
			<div id="fcc-catalog-grid" class="fcc-supps-catalog-grid">
				<!-- Rendered by JS -->
			</div>
		</div>
	</div>

	<!-- ──────────────────────────────────────────── SAVE BAR -->
	<div class="fcc-aff-save-bar" id="fcc-supps-save-bar">
		<div class="fcc-aff-save-bar__inner">
			<span class="fcc-aff-save-bar__status" id="fcc-supps-save-status" aria-live="polite"></span>
			<button type="button" id="fcc-supps-save-btn" class="button button-primary fcc-aff-save-bar__btn">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
				<?php esc_html_e( 'Save All Changes', 'food-calorie-calculator' ); ?>
			</button>
		</div>
	</div>

</div><!-- /.wrap -->

<script>
(function() {
'use strict';

var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
var nonce   = <?php echo wp_json_encode( $supp_nonce_field ); ?>;

// Initial data from PHP
var catDefs  = <?php echo wp_json_encode( $cat_defs ); ?>;
var netDefs  = <?php echo wp_json_encode( $net_defs ); ?>;
var nutrFlds = <?php echo wp_json_encode( $nutr_labels ); ?>;

// Mutable state
var catalog = <?php echo wp_json_encode( array_values( $catalog ) ); ?>;
var rules   = <?php echo wp_json_encode( array_values( $rules ) ); ?>;

// ── Utility ──────────────────────────────────────────────────────────────
function esc(str) {
	var d = document.createElement('div');
	d.appendChild(document.createTextNode(str || ''));
	return d.innerHTML;
}
function uid() {
	return 'x' + Math.random().toString(36).substr(2, 8);
}
function catLabel(slug) {
	return catDefs[slug] ? catDefs[slug].label : slug;
}
function catColour(slug) {
	return catDefs[slug] ? catDefs[slug].colour : '#607D8B';
}
function netLabel(slug) {
	return netDefs[slug] ? netDefs[slug].label : slug;
}
function catIcon(slug) {
	return catDefs[slug] ? catDefs[slug].icon : '💊';
}

// ── Render rules list ──────────────────────────────────────────────────
function renderRules() {
	var container = document.getElementById('fcc-rules-list');
	if (!container) return;
	if (!rules.length) {
		container.innerHTML = '<p class="fcc-supps-empty"><?php echo esc_js( __( 'No trigger rules yet. Click "Add Trigger Rule" to create one.', 'food-calorie-calculator' ) ); ?></p>';
		return;
	}
	var html = '<table class="fcc-supps-rules-table"><thead><tr>'
		+ '<th><?php echo esc_js( __( 'Rule Name', 'food-calorie-calculator' ) ); ?></th>'
		+ '<th><?php echo esc_js( __( 'Condition', 'food-calorie-calculator' ) ); ?></th>'
		+ '<th><?php echo esc_js( __( 'Triggers Categories', 'food-calorie-calculator' ) ); ?></th>'
		+ '<th><?php echo esc_js( __( 'Status', 'food-calorie-calculator' ) ); ?></th>'
		+ '<th><?php echo esc_js( __( 'Actions', 'food-calorie-calculator' ) ); ?></th>'
		+ '</tr></thead><tbody>';

	rules.forEach(function(r, idx) {
		var condText = '';
		if (r.type === 'nutrient') {
			var opMap = {gte: '≥', lte: '≤', eq: '='};
			condText = (nutrFlds[r.field] || r.field) + ' ' + (opMap[r.operator] || r.operator) + ' ' + r.value;
		} else if (r.type === 'keyword') {
			condText = 'Food name contains: ' + r.value.split(',').slice(0,3).join(', ') + (r.value.split(',').length > 3 ? '…' : '');
		} else if (r.type === 'category') {
			condText = 'Food category contains: ' + r.value;
		}
		var catChips = (r.cats || []).map(function(c) {
			return '<span class="fcc-supps-cat-badge" style="background:' + esc(catColour(c)) + ';">' + esc(catLabel(c)) + '</span>';
		}).join(' ');
		var typeBadge = '<span class="fcc-supps-type-badge fcc-supps-type-badge--' + esc(r.type) + '">' + esc(r.type) + '</span>';
		html += '<tr data-rule-idx="' + idx + '">'
			+ '<td><strong>' + esc(r.label) + '</strong></td>'
			+ '<td>' + typeBadge + ' <span class="fcc-supps-cond-text">' + esc(condText) + '</span></td>'
			+ '<td>' + catChips + '</td>'
			+ '<td><label class="fcc-aff-toggle"><input type="checkbox" class="fcc-rule-toggle" data-idx="' + idx + '" ' + (r.enabled ? 'checked' : '') + '><span class="fcc-aff-toggle__track"></span></label></td>'
			+ '<td class="fcc-supps-row-actions">'
			+ '<button type="button" class="fcc-supps-btn-edit" data-idx="' + idx + '"><?php echo esc_js( __( 'Edit', 'food-calorie-calculator' ) ); ?></button>'
			+ '<button type="button" class="fcc-supps-btn-delete fcc-supps-btn-delete--rule" data-idx="' + idx + '"><?php echo esc_js( __( 'Delete', 'food-calorie-calculator' ) ); ?></button>'
			+ '</td></tr>';
	});

	html += '</tbody></table>';
	container.innerHTML = html;
	bindRuleEvents();
}

function bindRuleEvents() {
	document.querySelectorAll('.fcc-rule-toggle').forEach(function(t) {
		t.addEventListener('change', function() {
			rules[parseInt(t.dataset.idx)].enabled = t.checked;
		});
	});
	document.querySelectorAll('.fcc-supps-btn-edit[data-idx]').forEach(function(btn) {
		if (btn.closest('.fcc-supps-rules-table')) {
			btn.addEventListener('click', function() { openRuleForm(parseInt(btn.dataset.idx)); });
		}
	});
	document.querySelectorAll('.fcc-supps-btn-delete--rule').forEach(function(btn) {
		btn.addEventListener('click', function() {
			if (confirm('<?php echo esc_js( __( 'Delete this rule?', 'food-calorie-calculator' ) ); ?>')) {
				rules.splice(parseInt(btn.dataset.idx), 1);
				renderRules();
			}
		});
	});
}

// ── Rule form (modal-style overlay inside card) ──────────────────────
var ruleFormEl = null;
var editingRuleIdx = -1;

function buildRuleForm() {
	var el = document.createElement('div');
	el.className = 'fcc-supps-rule-form';
	el.id = 'fcc-rule-form';
	el.innerHTML = '<h3 class="fcc-supps-form-panel__title" id="fcc-rule-form-title"><?php echo esc_js( __( 'Add Trigger Rule', 'food-calorie-calculator' ) ); ?></h3>'
		+ '<div class="fcc-supps-form-grid">'

		+ '<div class="fcc-supps-form-field fcc-supps-form-field--wide">'
		+ '<label class="fcc-form-label"><?php echo esc_js( __( 'Rule Name', 'food-calorie-calculator' ) ); ?></label>'
		+ '<input type="text" id="rf-label" class="regular-text" placeholder="e.g. High Protein Food">'
		+ '</div>'

		+ '<div class="fcc-supps-form-field">'
		+ '<label class="fcc-form-label"><?php echo esc_js( __( 'Rule Type', 'food-calorie-calculator' ) ); ?></label>'
		+ '<select id="rf-type" class="fcc-supps-select"><option value="nutrient"><?php echo esc_js( __( 'Nutrient Threshold', 'food-calorie-calculator' ) ); ?></option><option value="keyword"><?php echo esc_js( __( 'Food Keyword', 'food-calorie-calculator' ) ); ?></option><option value="category"><?php echo esc_js( __( 'Food Category', 'food-calorie-calculator' ) ); ?></option></select>'
		+ '</div>'

		+ '<div class="fcc-supps-form-field fcc-rf-nutrient-fields">'
		+ '<label class="fcc-form-label"><?php echo esc_js( __( 'Nutrient', 'food-calorie-calculator' ) ); ?></label>'
		+ '<select id="rf-field" class="fcc-supps-select">'
		+ Object.entries(nutrFlds).map(function(e) { return '<option value="' + esc(e[0]) + '">' + esc(e[1]) + '</option>'; }).join('')
		+ '</select>'
		+ '</div>'

		+ '<div class="fcc-supps-form-field fcc-rf-nutrient-fields">'
		+ '<label class="fcc-form-label"><?php echo esc_js( __( 'Operator', 'food-calorie-calculator' ) ); ?></label>'
		+ '<select id="rf-operator" class="fcc-supps-select"><option value="gte">≥ Greater or equal</option><option value="lte">≤ Less or equal</option><option value="eq">= Equals</option></select>'
		+ '</div>'

		+ '<div class="fcc-supps-form-field">'
		+ '<label class="fcc-form-label" id="rf-value-label"><?php echo esc_js( __( 'Threshold Value', 'food-calorie-calculator' ) ); ?></label>'
		+ '<input type="text" id="rf-value" class="regular-text" placeholder="e.g. 20">'
		+ '</div>'

		+ '</div>'

		+ '<div class="fcc-supps-form-field fcc-supps-form-field--wide" style="margin-top:0.75rem;">'
		+ '<label class="fcc-form-label"><?php echo esc_js( __( 'Show Supplement Categories', 'food-calorie-calculator' ) ); ?></label>'
		+ '<div class="fcc-supps-cat-checkboxes" id="rf-cats">'
		+ Object.entries(catDefs).map(function(e) {
			return '<label class="fcc-supps-cat-check"><input type="checkbox" value="' + esc(e[0]) + '">'
				+ '<span style="background:' + esc(e[1].colour) + ';" class="fcc-supps-cat-badge">' + esc(e[1].label) + '</span>'
				+ '</label>';
		}).join('')
		+ '</div>'
		+ '</div>'

		+ '<div class="fcc-supps-form-actions" style="margin-top:1rem;">'
		+ '<button type="button" id="rf-save" class="button button-primary"><?php echo esc_js( __( 'Save Rule', 'food-calorie-calculator' ) ); ?></button>'
		+ '<button type="button" id="rf-cancel" class="button"><?php echo esc_js( __( 'Cancel', 'food-calorie-calculator' ) ); ?></button>'
		+ '</div>';

	// Bind type change
	el.querySelector('#rf-type').addEventListener('change', function() {
		var t = this.value;
		el.querySelectorAll('.fcc-rf-nutrient-fields').forEach(function(f) {
			f.style.display = t === 'nutrient' ? '' : 'none';
		});
		var lbl = el.querySelector('#rf-value-label');
		if (t === 'keyword') lbl.textContent = '<?php echo esc_js( __( 'Keywords (comma-separated)', 'food-calorie-calculator' ) ); ?>';
		else if (t === 'category') lbl.textContent = '<?php echo esc_js( __( 'Category Name (partial match)', 'food-calorie-calculator' ) ); ?>';
		else lbl.textContent = '<?php echo esc_js( __( 'Threshold Value', 'food-calorie-calculator' ) ); ?>';
	});

	el.querySelector('#rf-save').addEventListener('click', saveRule);
	el.querySelector('#rf-cancel').addEventListener('click', closeRuleForm);

	return el;
}

function openRuleForm(idx) {
	if (ruleFormEl) closeRuleForm();
	editingRuleIdx = (typeof idx === 'number') ? idx : -1;
	ruleFormEl = buildRuleForm();

	if (editingRuleIdx >= 0) {
		var r = rules[editingRuleIdx];
		ruleFormEl.querySelector('#fcc-rule-form-title').textContent = '<?php echo esc_js( __( 'Edit Trigger Rule', 'food-calorie-calculator' ) ); ?>';
		ruleFormEl.querySelector('#rf-label').value    = r.label   || '';
		ruleFormEl.querySelector('#rf-type').value     = r.type    || 'nutrient';
		ruleFormEl.querySelector('#rf-field').value    = r.field   || 'protein_g';
		ruleFormEl.querySelector('#rf-operator').value = r.operator || 'gte';
		ruleFormEl.querySelector('#rf-value').value    = r.value   || '';
		(r.cats || []).forEach(function(c) {
			var cb = ruleFormEl.querySelector('#rf-cats input[value="' + c + '"]');
			if (cb) cb.checked = true;
		});
		// Trigger type change to show/hide nutrient fields
		ruleFormEl.querySelector('#rf-type').dispatchEvent(new Event('change'));
	}

	var addBtn = document.getElementById('fcc-add-rule-btn');
	addBtn.parentNode.insertBefore(ruleFormEl, addBtn);
	addBtn.style.display = 'none';
}

function closeRuleForm() {
	if (ruleFormEl) { ruleFormEl.remove(); ruleFormEl = null; }
	document.getElementById('fcc-add-rule-btn').style.display = '';
	editingRuleIdx = -1;
}

function saveRule() {
	var label = document.getElementById('rf-label').value.trim();
	if (!label) { alert('<?php echo esc_js( __( 'Please enter a rule name.', 'food-calorie-calculator' ) ); ?>'); return; }
	var type    = document.getElementById('rf-type').value;
	var value   = document.getElementById('rf-value').value.trim();
	var cats    = [];
	ruleFormEl.querySelectorAll('#rf-cats input:checked').forEach(function(cb) { cats.push(cb.value); });
	if (!cats.length) { alert('<?php echo esc_js( __( 'Select at least one supplement category.', 'food-calorie-calculator' ) ); ?>'); return; }

	var rule = {
		id:       editingRuleIdx >= 0 ? rules[editingRuleIdx].id : ('rule_' + uid()),
		label:    label,
		type:     type,
		field:    type === 'nutrient' ? document.getElementById('rf-field').value : '',
		operator: type === 'nutrient' ? document.getElementById('rf-operator').value : 'contains',
		value:    value,
		cats:     cats,
		enabled:  editingRuleIdx >= 0 ? (rules[editingRuleIdx].enabled !== false) : true,
		priority: 10,
	};

	if (editingRuleIdx >= 0) rules[editingRuleIdx] = rule;
	else rules.push(rule);

	closeRuleForm();
	renderRules();
}

document.getElementById('fcc-add-rule-btn').addEventListener('click', function() { openRuleForm(); });

// ── Render catalog grid ────────────────────────────────────────────────
function renderCatalog() {
	var grid = document.getElementById('fcc-catalog-grid');
	var countEl = document.getElementById('fcc-catalog-count');
	if (countEl) countEl.textContent = catalog.length;
	if (!grid) return;
	if (!catalog.length) {
		grid.innerHTML = '<p class="fcc-supps-empty"><?php echo esc_js( __( 'No supplements yet. Click "Add Supplement" to get started.', 'food-calorie-calculator' ) ); ?></p>';
		return;
	}
	var html = '';
	catalog.forEach(function(s, idx) {
		var col      = catColour(s.category);
		var lbl      = catLabel(s.category);
		var icon     = catIcon(s.category);
		var net      = netLabel(s.network);
		var clk      = s.clicks || 0;
		var impr     = s.impressions || 0;
		var ctr      = impr > 0 ? (clk / impr * 100).toFixed(1) : '0';
		var isActive = s.status !== 'inactive';

		var trashIcon = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>';
		var editIcon  = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
		var linkIcon  = s.affiliate_url
			? '<span class="fcc-sc__link-dot fcc-sc__link-dot--ok" title="Affiliate URL set">●</span>'
			: '<span class="fcc-sc__link-dot fcc-sc__link-dot--missing" title="No affiliate URL">●</span>';

		html += '<div class="fcc-sc ' + (isActive ? 'fcc-sc--active' : 'fcc-sc--off') + '"'
			+ ' data-idx="' + idx + '" style="--sc:' + esc(col) + ';">'

			// ── Colored header band ──────────────────────────────────────
			+ '<div class="fcc-sc__hdr">'
			+ '<span class="fcc-sc__hdr-icon" aria-hidden="true">' + esc(icon) + '</span>'
			+ '<span class="fcc-sc__hdr-cat">' + esc(lbl) + '</span>'
			+ (s.badge ? '<span class="fcc-sc__hdr-badge">' + esc(s.badge) + '</span>' : '')
			+ '<span class="fcc-sc__hdr-status ' + (isActive ? 'fcc-sc__hdr-status--on' : 'fcc-sc__hdr-status--off') + '">'
			+ (isActive ? '<?php echo esc_js( __( 'Active', 'food-calorie-calculator' ) ); ?>' : '<?php echo esc_js( __( 'Off', 'food-calorie-calculator' ) ); ?>')
			+ '</span>'
			+ '</div>'

			// ── Body: brand / name / tagline ─────────────────────────────
			+ '<div class="fcc-sc__body">'
			+ '<div class="fcc-sc__brand">' + esc(s.brand || '—') + '</div>'
			+ '<div class="fcc-sc__name">' + esc(s.name) + '</div>'
			+ (s.tagline ? '<div class="fcc-sc__tagline">' + esc(s.tagline) + '</div>' : '')
			+ '</div>'

			// ── Price + retailer row ──────────────────────────────────────
			+ '<div class="fcc-sc__meta">'
			+ (s.price ? '<span class="fcc-sc__price">' + esc(s.price) + '</span>' : '<span></span>')
			+ '<span class="fcc-sc__net-pill">' + esc(net) + '</span>'
			+ linkIcon
			+ '</div>'

			// ── Stats strip ───────────────────────────────────────────────
			+ '<div class="fcc-sc__stats">'
			+ '<div class="fcc-sc__stat"><b>' + clk + '</b><span>Clicks</span></div>'
			+ '<div class="fcc-sc__stat"><b>' + impr + '</b><span>Impr.</span></div>'
			+ '<div class="fcc-sc__stat fcc-sc__stat--ctr"><b>' + ctr + '%</b><span>CTR</span></div>'
			+ '</div>'

			// ── Actions ───────────────────────────────────────────────────
			+ '<div class="fcc-sc__actions">'
			+ '<button type="button" class="fcc-supps-btn-edit fcc-sc__edit-btn" data-cat-idx="' + idx + '">'
			+ editIcon + ' <?php echo esc_js( __( 'Edit', 'food-calorie-calculator' ) ); ?>'
			+ '</button>'
			+ '<button type="button" class="fcc-supps-btn-delete fcc-sc__del-btn" data-cat-idx="' + idx + '" title="<?php echo esc_js( __( 'Delete supplement', 'food-calorie-calculator' ) ); ?>">'
			+ trashIcon
			+ '</button>'
			+ '</div>'

			+ '</div>'; // /.fcc-sc
	});
	grid.innerHTML = html;
	bindCatalogEvents();
}

function bindCatalogEvents() {
	document.querySelectorAll('.fcc-supps-btn-edit[data-cat-idx]').forEach(function(btn) {
		btn.addEventListener('click', function() { openSuppForm(parseInt(btn.dataset.catIdx)); });
	});
	document.querySelectorAll('.fcc-supps-btn-delete[data-cat-idx]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			if (confirm('<?php echo esc_js( __( 'Delete this supplement?', 'food-calorie-calculator' ) ); ?>')) {
				catalog.splice(parseInt(btn.dataset.catIdx), 1);
				renderCatalog();
			}
		});
	});
}

// ── Supplement form ────────────────────────────────────────────────────
var editingSuppIdx = -1;

function openSuppForm(idx) {
	editingSuppIdx = (typeof idx === 'number') ? idx : -1;
	var panel = document.getElementById('fcc-supp-form-panel');
	var title = document.getElementById('fcc-supp-form-title');

	// Clear form
	['fcc-sf-name','fcc-sf-brand','fcc-sf-tagline','fcc-sf-url','fcc-sf-price','fcc-sf-badge','fcc-sf-image'].forEach(function(id) {
		var el = document.getElementById(id);
		if (el) el.value = '';
	});
	document.getElementById('fcc-sf-category').value = 'protein';
	document.getElementById('fcc-sf-network').value  = 'amazon_uk';
	document.getElementById('fcc-sf-status').value   = 'active';

	if (editingSuppIdx >= 0) {
		var s = catalog[editingSuppIdx];
		title.textContent = '<?php echo esc_js( __( 'Edit Supplement', 'food-calorie-calculator' ) ); ?>';
		document.getElementById('fcc-sf-name').value     = s.name     || '';
		document.getElementById('fcc-sf-brand').value    = s.brand    || '';
		document.getElementById('fcc-sf-tagline').value  = s.tagline  || '';
		document.getElementById('fcc-sf-url').value      = s.affiliate_url || '';
		document.getElementById('fcc-sf-price').value    = s.price    || '';
		document.getElementById('fcc-sf-badge').value    = s.badge    || '';
		document.getElementById('fcc-sf-image').value    = s.image_url || '';
		document.getElementById('fcc-sf-category').value = s.category || 'protein';
		document.getElementById('fcc-sf-network').value  = s.network  || 'amazon_uk';
		document.getElementById('fcc-sf-status').value   = s.status   || 'active';
	} else {
		title.textContent = '<?php echo esc_js( __( 'Add New Supplement', 'food-calorie-calculator' ) ); ?>';
	}

	panel.hidden = false;
	panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeSuppForm() {
	document.getElementById('fcc-supp-form-panel').hidden = true;
	editingSuppIdx = -1;
}

function saveSuppForm() {
	var name = document.getElementById('fcc-sf-name').value.trim();
	if (!name) { alert('<?php echo esc_js( __( 'Supplement name is required.', 'food-calorie-calculator' ) ); ?>'); return; }
	var obj = {
		id:            editingSuppIdx >= 0 ? catalog[editingSuppIdx].id : ('supp_' + uid()),
		name:          name,
		brand:         document.getElementById('fcc-sf-brand').value.trim(),
		tagline:       document.getElementById('fcc-sf-tagline').value.trim(),
		category:      document.getElementById('fcc-sf-category').value,
		affiliate_url: document.getElementById('fcc-sf-url').value.trim(),
		network:       document.getElementById('fcc-sf-network').value,
		price:         document.getElementById('fcc-sf-price').value.trim(),
		badge:         document.getElementById('fcc-sf-badge').value.trim(),
		image_url:     document.getElementById('fcc-sf-image').value.trim(),
		status:        document.getElementById('fcc-sf-status').value,
		// Preserve stats if editing
		clicks:        editingSuppIdx >= 0 ? (catalog[editingSuppIdx].clicks || 0) : 0,
		impressions:   editingSuppIdx >= 0 ? (catalog[editingSuppIdx].impressions || 0) : 0,
		ctr:           editingSuppIdx >= 0 ? (catalog[editingSuppIdx].ctr || 0) : 0,
	};
	if (editingSuppIdx >= 0) catalog[editingSuppIdx] = obj;
	else catalog.push(obj);
	closeSuppForm();
	renderCatalog();
}

document.getElementById('fcc-add-supp-btn').addEventListener('click', function() { openSuppForm(); });
document.getElementById('fcc-supp-form-save').addEventListener('click', saveSuppForm);
document.getElementById('fcc-supp-form-cancel').addEventListener('click', closeSuppForm);

// ── Save all ────────────────────────────────────────────────────────────
function collectConfig() {
	var cfg = {};
	document.querySelectorAll('[data-cfg]').forEach(function(el) {
		var key = el.dataset.cfg;
		if (el.type === 'checkbox') cfg[key] = el.checked ? '1' : '';
		else cfg[key] = el.value;
	});
	return cfg;
}

document.getElementById('fcc-supps-save-btn').addEventListener('click', function() {
	var btn    = document.getElementById('fcc-supps-save-btn');
	var status = document.getElementById('fcc-supps-save-status');
	btn.disabled = true;
	btn.textContent = '<?php echo esc_js( __( 'Saving…', 'food-calorie-calculator' ) ); ?>';
	status.textContent = '';

	var fd = new FormData();
	fd.append('action', 'fcc_save_supplements');
	fd.append('nonce', nonce);

	// Config
	var cfg = collectConfig();
	Object.keys(cfg).forEach(function(k) { fd.append('config[' + k + ']', cfg[k]); });

	// Catalog (strip stats for submission)
	catalog.forEach(function(s, i) {
		['id','name','brand','tagline','category','image_url','affiliate_url','network','price','badge','status'].forEach(function(k) {
			fd.append('catalog[' + i + '][' + k + ']', s[k] || '');
		});
	});

	// Rules
	rules.forEach(function(r, i) {
		['id','label','type','field','operator','value','enabled','priority'].forEach(function(k) {
			fd.append('rules[' + i + '][' + k + ']', r[k] !== undefined ? String(r[k]) : '');
		});
		(r.cats || []).forEach(function(c) { fd.append('rules[' + i + '][cats][]', c); });
	});

	fetch(ajaxUrl, { method: 'POST', body: fd })
		.then(function(r) { return r.json(); })
		.then(function(res) {
			if (res.success) {
				status.textContent = '✓ <?php echo esc_js( __( 'Saved successfully', 'food-calorie-calculator' ) ); ?>';
				status.style.color = '#2D7A4F';
			} else {
				status.textContent = '✗ <?php echo esc_js( __( 'Save failed', 'food-calorie-calculator' ) ); ?>';
				status.style.color = '#c62828';
			}
		}).catch(function() {
			status.textContent = '✗ <?php echo esc_js( __( 'Network error', 'food-calorie-calculator' ) ); ?>';
			status.style.color = '#c62828';
		}).finally(function() {
			btn.disabled = false;
			btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> <?php echo esc_js( __( 'Save All Changes', 'food-calorie-calculator' ) ); ?>';
			setTimeout(function() { status.textContent = ''; }, 4000);
		});
});

// ── Init ────────────────────────────────────────────────────────────────
renderRules();
renderCatalog();

}());
</script>
