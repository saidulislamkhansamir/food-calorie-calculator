<?php
/**
 * Ad Networks monetization admin page.
 *
 * @package FCC\Admin
 */

defined( 'ABSPATH' ) || exit;

use FCC\Admin\Ads;

$config   = Ads::get_config();
$general  = $config['general'];
$networks = $config['networks'];
$defs     = Ads::get_network_definitions();

// Stats.
$total_networks  = count( $defs );
$active_count    = 0;
$positions_used  = [];
foreach ( $networks as $n ) {
	if ( $n['enabled'] ) {
		$active_count++;
		$positions_used[] = $n['position'];
	}
}
$positions_used = array_unique( $positions_used );

// Group networks by category.
$categories = [];
foreach ( $defs as $key => $def ) {
	$categories[ $def['category'] ][] = $key;
}

$position_labels = [
	'above_search'    => 'Above Search',
	'before_results'  => 'Before Results',
	'after_results'   => 'After Results',
	'below_calculator' => 'Below Calculator',
];

$category_icons = [
	'Premium Display'      => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
	'Programmatic'         => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
	'Direct / Marketplace' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>',
	'Performance'          => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>',
	'Native'               => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>',
	'Specialist'           => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M4.93 4.93l1.41 1.41M18.66 18.66l1.41 1.41M2 12h2M20 12h2M12 2v2M12 20v2"/></svg>',
];

wp_nonce_field( 'fcc_ads_nonce', 'fcc_ads_nonce_field' );
?>

<div class="wrap fcc-ads-page">

	<!-- ──────────────────────────────────────────────────────────────── HERO -->
	<div class="fcc-ads-hero">
		<div class="fcc-ads-hero__inner">
			<div class="fcc-ads-hero__left">
				<div class="fcc-ads-hero__icon">
					<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/></svg>
				</div>
				<div>
					<h1 class="fcc-ads-hero__title"><?php esc_html_e( 'Ad Monetization', 'food-calorie-calculator' ); ?></h1>
					<p class="fcc-ads-hero__sub"><?php esc_html_e( 'Connect ad networks and earn from your calculator traffic', 'food-calorie-calculator' ); ?></p>
				</div>
			</div>
			<div class="fcc-ads-hero__stats">
				<div class="fcc-ads-hero__stat">
					<span class="fcc-ads-hero__stat-val"><?php echo esc_html( $active_count ); ?></span>
					<span class="fcc-ads-hero__stat-lbl"><?php esc_html_e( 'Active Networks', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-ads-hero__stat">
					<span class="fcc-ads-hero__stat-val"><?php echo esc_html( $total_networks ); ?></span>
					<span class="fcc-ads-hero__stat-lbl"><?php esc_html_e( 'Available', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-ads-hero__stat">
					<span class="fcc-ads-hero__stat-val"><?php echo esc_html( count( $positions_used ) ); ?></span>
					<span class="fcc-ads-hero__stat-lbl"><?php esc_html_e( 'Positions Used', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-ads-hero__stat fcc-ads-hero__stat--cpm">
					<span class="fcc-ads-hero__stat-val">£3–30</span>
					<span class="fcc-ads-hero__stat-lbl"><?php esc_html_e( 'CPM Range (UK)', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- ──────────────────────────────────────── GENERAL SETTINGS CARD -->
	<div class="fcc-ads-section fcc-card">
		<div class="fcc-card__header">
			<h2 class="fcc-card__title">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M4.93 4.93l1.41 1.41M18.66 18.66l1.41 1.41M2 12h2M20 12h2M12 2v2M12 20v2"/></svg>
				<?php esc_html_e( 'General Settings', 'food-calorie-calculator' ); ?>
			</h2>
		</div>
		<div class="fcc-card__body fcc-ads-general">

			<div class="fcc-ads-general__row">
				<div class="fcc-ads-general__field">
					<label class="fcc-ads-toggle-label">
						<span class="fcc-aff-toggle">
							<input type="checkbox" id="fcc-ads-global-enabled" name="general[enabled]" value="1" <?php checked( $general['enabled'] ); ?>>
							<span class="fcc-aff-toggle__track"></span>
						</span>
						<span class="fcc-ads-toggle-label__text">
							<strong><?php esc_html_e( 'Enable Ad Monetization', 'food-calorie-calculator' ); ?></strong>
							<span class="fcc-ads-toggle-label__hint"><?php esc_html_e( 'Master switch — disabling this hides all ad slots globally.', 'food-calorie-calculator' ); ?></span>
						</span>
					</label>
				</div>

				<div class="fcc-ads-general__field">
					<label class="fcc-ads-toggle-label">
						<span class="fcc-aff-toggle">
							<input type="checkbox" id="fcc-ads-show-label" name="general[show_label]" value="1" <?php checked( $general['show_label'] ); ?>>
							<span class="fcc-aff-toggle__track"></span>
						</span>
						<span class="fcc-ads-toggle-label__text">
							<strong><?php esc_html_e( 'Show Disclosure Label', 'food-calorie-calculator' ); ?></strong>
							<span class="fcc-ads-toggle-label__hint"><?php esc_html_e( 'Required by Google AdSense, FTC, and ASA guidelines.', 'food-calorie-calculator' ); ?></span>
						</span>
					</label>
				</div>

				<div class="fcc-ads-general__field fcc-ads-general__field--inline">
					<label for="fcc-ads-label-text" class="fcc-form-label"><?php esc_html_e( 'Disclosure Label Text', 'food-calorie-calculator' ); ?></label>
					<input type="text" id="fcc-ads-label-text" name="general[label_text]" class="regular-text"
						value="<?php echo esc_attr( $general['label_text'] ); ?>"
						placeholder="Advertisement">
					<p class="description"><?php esc_html_e( 'Shown above each ad slot. "Advertisement" or "Sponsored" are standard.', 'food-calorie-calculator' ); ?></p>
				</div>
			</div>

		</div>
	</div>

	<!-- ──────────────────────────────────────── AD POSITIONS OVERVIEW -->
	<div class="fcc-ads-section fcc-card">
		<div class="fcc-card__header">
			<h2 class="fcc-card__title">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
				<?php esc_html_e( 'Ad Positions', 'food-calorie-calculator' ); ?>
			</h2>
			<p class="fcc-card__desc"><?php esc_html_e( 'Choose where each network\'s ad unit appears inside the calculator. Select per network below.', 'food-calorie-calculator' ); ?></p>
		</div>
		<div class="fcc-card__body">
			<div class="fcc-ads-positions-grid">
				<?php
				$pos_meta = [
					'above_search'     => [ 'icon' => '⬆', 'label' => 'Above Search',     'hint' => 'Top of the calculator, before the search box.' ],
					'before_results'   => [ 'icon' => '📊', 'label' => 'Before Results',   'hint' => 'Between quantity controls and the nutrition panel.' ],
					'after_results'    => [ 'icon' => '⬇', 'label' => 'After Results',    'hint' => 'Below the nutrition results section.' ],
					'below_calculator' => [ 'icon' => '📋', 'label' => 'Below Calculator', 'hint' => 'Outside the widget, after the entire calculator.' ],
				];
				foreach ( $pos_meta as $pk => $pm ) :
					$nets_here = [];
					foreach ( $networks as $nk => $n ) {
						if ( $n['enabled'] && $n['position'] === $pk ) $nets_here[] = $n['name'];
					}
				?>
				<div class="fcc-ads-pos-card <?php echo ! empty( $nets_here ) ? 'fcc-ads-pos-card--active' : ''; ?>">
					<div class="fcc-ads-pos-card__icon"><?php echo esc_html( $pm['icon'] ); ?></div>
					<div class="fcc-ads-pos-card__info">
						<strong><?php echo esc_html( $pm['label'] ); ?></strong>
						<span class="fcc-ads-pos-card__hint"><?php echo esc_html( $pm['hint'] ); ?></span>
						<?php if ( ! empty( $nets_here ) ) : ?>
						<div class="fcc-ads-pos-card__nets">
							<?php foreach ( $nets_here as $nm ) : ?>
							<span class="fcc-ads-pos-card__chip"><?php echo esc_html( $nm ); ?></span>
							<?php endforeach; ?>
						</div>
						<?php else : ?>
						<span class="fcc-ads-pos-card__empty"><?php esc_html_e( 'No networks assigned', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- ──────────────────────────────────────── NETWORK SECTIONS -->
	<?php foreach ( $categories as $cat_name => $cat_keys ) : ?>
	<div class="fcc-ads-section">
		<div class="fcc-ads-category-header">
			<span class="fcc-ads-category-icon" aria-hidden="true"><?php echo wp_kses_post( $category_icons[ $cat_name ] ?? '' ); ?></span>
			<h2 class="fcc-ads-category-title"><?php echo esc_html( $cat_name ); ?></h2>
			<span class="fcc-ads-category-count">
				<?php
				$cat_active = count( array_filter( $cat_keys, fn( $k ) => ! empty( $networks[ $k ]['enabled'] ) ) );
				echo esc_html( $cat_active ) . '/' . esc_html( count( $cat_keys ) ) . ' active';
				?>
			</span>
		</div>

		<div class="fcc-ads-network-grid">
			<?php foreach ( $cat_keys as $nkey ) :
				$n   = $networks[ $nkey ];
				$def = $defs[ $nkey ];
				$is_enabled = ! empty( $n['enabled'] );
			?>
			<div class="fcc-ads-network-card <?php echo $is_enabled ? 'fcc-ads-network-card--active' : ''; ?>"
				data-network="<?php echo esc_attr( $nkey ); ?>"
				style="--net-colour:<?php echo esc_attr( $def['colour'] ); ?>">

				<!-- Card header -->
				<div class="fcc-ads-network-card__header">
					<div class="fcc-ads-network-card__colour-bar"></div>
					<div class="fcc-ads-network-card__meta">
						<div class="fcc-ads-network-card__name">
							<?php echo esc_html( $def['name'] ); ?>
							<?php if ( ! empty( $def['badge'] ) ) : ?>
							<span class="fcc-ads-network-badge"><?php echo esc_html( $def['badge'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="fcc-ads-network-card__cpm">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
							<?php echo esc_html( $def['cpm_range'] ); ?> CPM
						</div>
					</div>
					<label class="fcc-aff-toggle fcc-ads-network-card__toggle" aria-label="<?php echo esc_attr( sprintf( __( 'Enable %s', 'food-calorie-calculator' ), $def['name'] ) ); ?>">
						<input type="checkbox" name="networks[<?php echo esc_attr( $nkey ); ?>][enabled]" value="1"
							class="fcc-ads-net-toggle" data-network="<?php echo esc_attr( $nkey ); ?>"
							<?php checked( $is_enabled ); ?>>
						<span class="fcc-aff-toggle__track"></span>
					</label>
				</div>

				<!-- Description -->
				<p class="fcc-ads-network-card__desc"><?php echo esc_html( $def['description'] ); ?></p>

				<!-- Expandable config -->
				<div class="fcc-ads-network-card__body <?php echo $is_enabled ? 'fcc-ads-network-card__body--open' : ''; ?>">

					<!-- Position selector -->
					<div class="fcc-ads-network-card__field">
						<label class="fcc-form-label"><?php esc_html_e( 'Ad Position', 'food-calorie-calculator' ); ?></label>
						<div class="fcc-ads-pos-radios">
							<?php foreach ( $position_labels as $pval => $plabel ) : ?>
							<label class="fcc-ads-pos-radio <?php echo ( $n['position'] === $pval ) ? 'fcc-ads-pos-radio--active' : ''; ?>">
								<input type="radio" name="networks[<?php echo esc_attr( $nkey ); ?>][position]"
									value="<?php echo esc_attr( $pval ); ?>"
									<?php checked( $n['position'], $pval ); ?>>
								<?php echo esc_html( $plabel ); ?>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Head script -->
					<div class="fcc-ads-network-card__field">
						<label class="fcc-form-label" for="fcc-ads-head-<?php echo esc_attr( $nkey ); ?>">
							<?php esc_html_e( 'Header Script', 'food-calorie-calculator' ); ?>
							<span class="fcc-ads-code-hint"><?php esc_html_e( 'Injected in footer (works for all networks)', 'food-calorie-calculator' ); ?></span>
						</label>
						<textarea id="fcc-ads-head-<?php echo esc_attr( $nkey ); ?>"
							name="networks[<?php echo esc_attr( $nkey ); ?>][head_script]"
							class="fcc-ads-code-textarea" rows="3"
							placeholder="<?php echo esc_attr( $def['head_example'] ); ?>"><?php echo esc_textarea( $n['head_script'] ); ?></textarea>
						<?php if ( empty( $def['unit_example'] ) ) : ?>
						<p class="fcc-ads-code-note">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 110 20A10 10 0 0112 2zm0 9a1 1 0 00-1 1v4a1 1 0 002 0v-4a1 1 0 00-1-1zm0-4a1.25 1.25 0 110 2.5A1.25 1.25 0 0112 7z"/></svg>
							<?php esc_html_e( 'This network auto-places ads from the header script — no ad unit code needed.', 'food-calorie-calculator' ); ?>
						</p>
						<?php endif; ?>
					</div>

					<!-- Ad unit code -->
					<div class="fcc-ads-network-card__field">
						<label class="fcc-form-label" for="fcc-ads-unit-<?php echo esc_attr( $nkey ); ?>">
							<?php esc_html_e( 'Ad Unit Code', 'food-calorie-calculator' ); ?>
							<span class="fcc-ads-code-hint"><?php esc_html_e( 'Placed inline at the selected position', 'food-calorie-calculator' ); ?></span>
						</label>
						<textarea id="fcc-ads-unit-<?php echo esc_attr( $nkey ); ?>"
							name="networks[<?php echo esc_attr( $nkey ); ?>][ad_code]"
							class="fcc-ads-code-textarea" rows="<?php echo empty( $def['unit_example'] ) ? 2 : 5; ?>"
							placeholder="<?php echo esc_attr( $def['unit_example'] ); ?>"><?php echo esc_textarea( $n['ad_code'] ); ?></textarea>
					</div>

				</div><!-- /.fcc-ads-network-card__body -->

				<!-- Expand toggle -->
				<button type="button" class="fcc-ads-expand-btn" data-network="<?php echo esc_attr( $nkey ); ?>"
					aria-expanded="<?php echo $is_enabled ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( __( 'Configure %s', 'food-calorie-calculator' ), $def['name'] ) ); ?>">
					<span class="fcc-ads-expand-btn__txt"><?php esc_html_e( 'Configure', 'food-calorie-calculator' ); ?></span>
					<svg class="fcc-ads-expand-btn__caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
				</button>

			</div><!-- /.fcc-ads-network-card -->
			<?php endforeach; ?>
		</div><!-- /.fcc-ads-network-grid -->

	</div><!-- /.fcc-ads-section (category) -->
	<?php endforeach; ?>

	<!-- ──────────────────────────────────────── SAVE BAR -->
	<div class="fcc-aff-save-bar" id="fcc-ads-save-bar">
		<div class="fcc-aff-save-bar__inner">
			<span class="fcc-aff-save-bar__status" id="fcc-ads-save-status" aria-live="polite"></span>
			<button type="button" id="fcc-ads-save-btn" class="button button-primary fcc-aff-save-bar__btn">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
				<?php esc_html_e( 'Save All Networks', 'food-calorie-calculator' ); ?>
			</button>
		</div>
	</div>

</div><!-- /.wrap.fcc-ads-page -->

<script>
(function() {

	var ajaxUrl  = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
	var nonce    = <?php echo wp_json_encode( wp_create_nonce( 'fcc_ads_nonce' ) ); ?>;
	var saveBtn  = document.getElementById( 'fcc-ads-save-btn' );
	var statusEl = document.getElementById( 'fcc-ads-save-status' );

	// ── Toggle expand/collapse on card ──────────────────────────────────
	document.querySelectorAll( '.fcc-ads-expand-btn' ).forEach( function( btn ) {
		btn.addEventListener( 'click', function() {
			var key  = btn.dataset.network;
			var card = document.querySelector( '.fcc-ads-network-card[data-network="' + key + '"]' );
			var body = card ? card.querySelector( '.fcc-ads-network-card__body' ) : null;
			if ( ! body ) return;
			var open = body.classList.toggle( 'fcc-ads-network-card__body--open' );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	} );

	// ── Auto-expand when network is toggled on ───────────────────────────
	document.querySelectorAll( '.fcc-ads-net-toggle' ).forEach( function( toggle ) {
		toggle.addEventListener( 'change', function() {
			var key  = toggle.dataset.network;
			var card = document.querySelector( '.fcc-ads-network-card[data-network="' + key + '"]' );
			if ( ! card ) return;
			var body   = card.querySelector( '.fcc-ads-network-card__body' );
			var expBtn = card.querySelector( '.fcc-ads-expand-btn' );
			card.classList.toggle( 'fcc-ads-network-card--active', toggle.checked );
			if ( toggle.checked && body ) {
				body.classList.add( 'fcc-ads-network-card__body--open' );
				if ( expBtn ) expBtn.setAttribute( 'aria-expanded', 'true' );
			}
		} );
	} );

	// ── Position radio label styling ─────────────────────────────────────
	document.querySelectorAll( '.fcc-ads-pos-radios' ).forEach( function( row ) {
		row.querySelectorAll( 'input[type=radio]' ).forEach( function( r ) {
			r.addEventListener( 'change', function() {
				row.querySelectorAll( '.fcc-ads-pos-radio' ).forEach( function( lbl ) {
					lbl.classList.remove( 'fcc-ads-pos-radio--active' );
				} );
				if ( r.checked ) r.closest( '.fcc-ads-pos-radio' ).classList.add( 'fcc-ads-pos-radio--active' );
			} );
		} );
	} );

	// ── Collect form data ────────────────────────────────────────────────
	function collectFormData() {
		var data = { action: 'fcc_save_ads', nonce: nonce };

		// General
		data['general[enabled]']    = document.getElementById( 'fcc-ads-global-enabled' ).checked ? '1' : '';
		data['general[show_label]'] = document.getElementById( 'fcc-ads-show-label' ).checked ? '1' : '';
		data['general[label_text]'] = document.getElementById( 'fcc-ads-label-text' ).value;

		// Per-network
		document.querySelectorAll( '.fcc-ads-network-card' ).forEach( function( card ) {
			var key    = card.dataset.network;
			var toggle = card.querySelector( '.fcc-ads-net-toggle' );
			var posEl  = card.querySelector( 'input[name="networks[' + key + '][position]"]:checked' );
			var headEl = card.querySelector( 'textarea[name="networks[' + key + '][head_script]"]' );
			var unitEl = card.querySelector( 'textarea[name="networks[' + key + '][ad_code]"]' );

			data[ 'networks[' + key + '][enabled]' ]     = ( toggle && toggle.checked ) ? '1' : '';
			data[ 'networks[' + key + '][position]' ]    = posEl  ? posEl.value  : 'after_results';
			data[ 'networks[' + key + '][head_script]' ] = headEl ? headEl.value : '';
			data[ 'networks[' + key + '][ad_code]' ]     = unitEl ? unitEl.value : '';
		} );

		return data;
	}

	// ── Save ─────────────────────────────────────────────────────────────
	saveBtn.addEventListener( 'click', function() {
		saveBtn.disabled = true;
		saveBtn.textContent = 'Saving…';
		statusEl.textContent = '';

		var params = new URLSearchParams( collectFormData() );

		fetch( ajaxUrl, {
			method  : 'POST',
			headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
			body    : params.toString(),
		} )
		.then( function( r ) { return r.json(); } )
		.then( function( res ) {
			if ( res.success ) {
				statusEl.textContent  = '✓ Saved successfully';
				statusEl.style.color  = '#2D7A4F';
			} else {
				statusEl.textContent  = '✗ Save failed. Try again.';
				statusEl.style.color  = '#c62828';
			}
		} )
		.catch( function() {
			statusEl.textContent = '✗ Network error. Try again.';
			statusEl.style.color = '#c62828';
		} )
		.finally( function() {
			saveBtn.disabled    = false;
			saveBtn.innerHTML   = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save All Networks';
			setTimeout( function() { statusEl.textContent = ''; }, 4000 );
		} );
	} );

}());
</script>
