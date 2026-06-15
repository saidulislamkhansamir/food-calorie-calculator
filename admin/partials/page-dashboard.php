<?php
/**
 * Admin dashboard overview.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$foods_count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'fcc_foods' );
$cats_count  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'fcc_categories' );
$shortcode   = '[food_calorie_calculator]';
?>
<div class="wrap fcc-admin-wrap fcc-dashboard-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Calorie Calculator Dashboard', 'food-calorie-calculator' ); ?></h1>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- ======================================================================
	     Hero header card
	     ====================================================================== -->
	<div class="fcc-dash-hero">
		<div class="fcc-dash-hero__inner">

			<div class="fcc-dash-hero__content">
				<div class="fcc-dash-hero__icon" aria-hidden="true">🥕</div>
				<div>
					<div class="fcc-dash-hero__title">
						<?php esc_html_e( 'Food Calorie Calculator', 'food-calorie-calculator' ); ?>
						<span class="fcc-dash-version">v<?php echo esc_html( FCC_VERSION ); ?></span>
					</div>
					<p class="fcc-dash-hero__sub">
						<?php esc_html_e( 'UK nutrition calculator with FSA traffic lights, meal builder, macros, BMR/TDEE, and full CSV/Excel import-export.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>

			<div class="fcc-dash-hero__stats">
				<div class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo esc_html( number_format_i18n( $foods_count ) ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value"><?php echo esc_html( number_format_i18n( $cats_count ) ); ?></span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-dash-hero-stat">
					<span class="fcc-dash-hero-stat__value">18</span>
					<span class="fcc-dash-hero-stat__label"><?php esc_html_e( 'Nutrients', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>

		</div>
	</div><!-- .fcc-dash-hero -->

	<!-- ======================================================================
	     Body — 3-column card grid
	     ====================================================================== -->
	<div class="fcc-dash-grid">

		<!-- Shortcode card -->
		<div class="fcc-dash-card fcc-dash-card--shortcode">
			<div class="fcc-dash-card__hd fcc-dash-card__hd--blue">
				<span class="fcc-dash-card__hicon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
				</span>
				<span class="fcc-dash-card__htitle"><?php esc_html_e( 'Embed Shortcode', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-card__body">
				<p class="fcc-dash-card__desc">
					<?php esc_html_e( 'Paste this shortcode into any page or post, or add the Gutenberg block from the block inserter.', 'food-calorie-calculator' ); ?>
				</p>
				<div class="fcc-dash-code-box">
					<code class="fcc-dash-code-box__code" id="fcc-shortcode-text"><?php echo esc_html( $shortcode ); ?></code>
					<button type="button" class="fcc-dash-code-box__copy" id="fcc-copy-btn"
						data-clipboard="<?php echo esc_attr( $shortcode ); ?>"
						aria-label="<?php esc_attr_e( 'Copy shortcode', 'food-calorie-calculator' ); ?>">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
						<span id="fcc-copy-label"><?php esc_html_e( 'Copy', 'food-calorie-calculator' ); ?></span>
					</button>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="fcc-dash-mini-link">
					<?php esc_html_e( 'View Foods →', 'food-calorie-calculator' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-settings' ) ); ?>" class="fcc-dash-mini-link">
					<?php esc_html_e( 'Plugin Settings →', 'food-calorie-calculator' ); ?>
				</a>
			</div>
		</div><!-- shortcode card -->

		<!-- Quick Actions card -->
		<div class="fcc-dash-card fcc-dash-card--actions">
			<div class="fcc-dash-card__hd fcc-dash-card__hd--green">
				<span class="fcc-dash-card__hicon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
				</span>
				<span class="fcc-dash-card__htitle"><?php esc_html_e( 'Quick Actions', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-card__body">
				<div class="fcc-dash-actions-grid">

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods&action=add' ) ); ?>"
						class="fcc-dash-action fcc-dash-action--green">
						<span class="fcc-dash-action__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Add New Food', 'food-calorie-calculator' ); ?></span>
					</a>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-import-export' ) ); ?>"
						class="fcc-dash-action fcc-dash-action--blue">
						<span class="fcc-dash-action__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
						</span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Import Foods', 'food-calorie-calculator' ); ?></span>
					</a>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-categories' ) ); ?>"
						class="fcc-dash-action fcc-dash-action--purple">
						<span class="fcc-dash-action__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
						</span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
					</a>

					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-settings' ) ); ?>"
						class="fcc-dash-action fcc-dash-action--slate">
						<span class="fcc-dash-action__icon" aria-hidden="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
						</span>
						<span class="fcc-dash-action__label"><?php esc_html_e( 'Settings', 'food-calorie-calculator' ); ?></span>
					</a>

				</div><!-- .fcc-dash-actions-grid -->
			</div>
		</div><!-- quick actions card -->

		<!-- How to Use card -->
		<div class="fcc-dash-card fcc-dash-card--howto">
			<div class="fcc-dash-card__hd fcc-dash-card__hd--orange">
				<span class="fcc-dash-card__hicon" aria-hidden="true">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
				</span>
				<span class="fcc-dash-card__htitle"><?php esc_html_e( 'How to Use', 'food-calorie-calculator' ); ?></span>
			</div>
			<div class="fcc-dash-card__body">
				<ol class="fcc-dash-steps">
					<li class="fcc-dash-step">
						<span class="fcc-dash-step__num fcc-dash-step__num--1">1</span>
						<span class="fcc-dash-step__text">
							<?php esc_html_e( 'Add the shortcode or Gutenberg block to any page to embed the calculator.', 'food-calorie-calculator' ); ?>
						</span>
					</li>
					<li class="fcc-dash-step">
						<span class="fcc-dash-step__num fcc-dash-step__num--2">2</span>
						<span class="fcc-dash-step__text">
							<?php esc_html_e( 'Visitors search for a food, set a quantity, and see nutrition data with FSA traffic lights.', 'food-calorie-calculator' ); ?>
						</span>
					</li>
					<li class="fcc-dash-step">
						<span class="fcc-dash-step__num fcc-dash-step__num--3">3</span>
						<span class="fcc-dash-step__text">
							<?php esc_html_e( 'Use Import / Export to bulk-load your own foods from CSV or Excel files.', 'food-calorie-calculator' ); ?>
						</span>
					</li>
					<li class="fcc-dash-step">
						<span class="fcc-dash-step__num fcc-dash-step__num--4">4</span>
						<span class="fcc-dash-step__text">
							<?php esc_html_e( 'Use Settings to control features, colours, labels, and Reference Intake values.', 'food-calorie-calculator' ); ?>
						</span>
					</li>
				</ol>
			</div>
		</div><!-- how to use card -->

	</div><!-- .fcc-dash-grid -->

</div><!-- .wrap -->

<script>
( function () {
	'use strict';

	const btn   = document.getElementById( 'fcc-copy-btn' );
	const label = document.getElementById( 'fcc-copy-label' );
	if ( ! btn || ! label ) return;

	btn.addEventListener( 'click', function () {
		const text = btn.dataset.clipboard || '';
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( function () { flash(); } );
		} else {
			const ta = document.createElement( 'textarea' );
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
