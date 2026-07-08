<?php
/**
 * Admin: Food Pages — hub, category, and food page content manager.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$saved      = isset( $_GET['saved'] )   ? sanitize_key( $_GET['saved'] ) : '';
$saved_cat  = isset( $_GET['cat_id'] )  ? absint( $_GET['cat_id'] ) : 0;
$saved_food = isset( $_GET['food_id'] ) ? absint( $_GET['food_id'] ) : 0;

$hub_seo_title       = \FCC\Settings::get( 'content.hub_seo_title', '' );
$hub_seo_description = \FCC\Settings::get( 'content.hub_seo_description', '' );
$hub_auto_title      = \FCC\Food_Pages::generate_hub_title();
$hub_auto_desc       = \FCC\Food_Pages::generate_hub_meta_desc();

$hub_intro     = FCC\Settings::get( 'content.hub_intro', '' );
$hub_editorial = FCC\Settings::get( 'content.hub_editorial', '' );
$categories    = FCC\Database::get_all_categories();
$hub_url    = home_url( '/calories/' );

// Foods list (paginated + searchable).
$search   = isset( $_GET['fps'] ) ? sanitize_text_field( wp_unslash( $_GET['fps'] ) ) : '';
$paged    = max( 1, absint( $_GET['fpp'] ?? 1 ) );
$_fpps    = isset( $_GET['fpps'] ) ? (int) $_GET['fpps'] : 50;
$per_page = in_array( $_fpps, [ 50, 100, 250, 500 ], true ) ? $_fpps : 50;

$result     = FCC\Database::get_foods( [
	'search'   => $search,
	'per_page' => $per_page,
	'page'     => $paged,
	'orderby'  => 'name',
	'order'    => 'ASC',
] );
$foods       = $result['rows'];
$total_foods = $result['total'];
$total_pages = (int) ceil( $total_foods / $per_page );

// Build category maps.
$cat_map      = [];
$cat_slug_map = [];
foreach ( $categories as $cat ) {
	$cat_map[ (int) $cat['id'] ]      = esc_html( $cat['name'] );
	$cat_slug_map[ (int) $cat['id'] ] = $cat['slug'];
}

$page_url = admin_url( 'admin.php?page=fcc-food-pages' );

$custom_cat_count = count( array_filter( $categories, fn( $c ) => ! empty( $c['description'] ) ) );
?>
<style>
/* ── Food Pages page scoped styles ──────────────────────────────────────── */
.fcc-food-pages-page { max-width: 1200px; }

/* Section cards */
.fcc-fp-card {
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	box-shadow: 0 1px 4px rgba(0,0,0,.06);
	margin-bottom: 24px;
	overflow: hidden;
}

/* Section header strip */
.fcc-fp-card__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 14px;
	padding: 18px 24px;
	border-bottom: 1px solid #e2e8f0;
	flex-wrap: wrap;
}
.fcc-fp-card__head--hub      { background: linear-gradient(135deg,#eff6ff 0%,#f8faff 100%); }
.fcc-fp-card__head--cats     { background: linear-gradient(135deg,#f0fdf4 0%,#f8faff 100%); }
.fcc-fp-card__head--foods    { background: linear-gradient(135deg,#faf5ff 0%,#f8faff 100%); }

.fcc-fp-card__head-left { display: flex; align-items: center; gap: 14px; }

.fcc-fp-card__icon {
	width: 44px; height: 44px;
	border-radius: 10px;
	display: flex; align-items: center; justify-content: center;
	font-size: 1.3rem; flex-shrink: 0;
}
.fcc-fp-card__icon--hub   { background: #dbeafe; }
.fcc-fp-card__icon--cats  { background: #dcfce7; }
.fcc-fp-card__icon--foods { background: #ede9fe; }

.fcc-fp-card__title {
	font-size: 1rem;
	font-weight: 700;
	color: #1e293b;
	margin: 0 0 2px;
	line-height: 1.2;
}
.fcc-fp-card__meta {
	display: flex;
	align-items: center;
	gap: 10px;
	flex-wrap: wrap;
}
.fcc-fp-url-pill {
	display: inline-flex; align-items: center; gap: 4px;
	background: rgba(0,0,0,.05);
	border: 1px solid rgba(0,0,0,.08);
	border-radius: 20px;
	padding: 2px 10px;
	font-size: 0.78rem;
	color: #475569;
	text-decoration: none;
	transition: background .15s;
}
.fcc-fp-url-pill:hover { background: rgba(0,0,0,.1); color: #1e293b; }
.fcc-fp-url-pill svg { opacity: .6; }

.fcc-fp-stat-pill {
	display: inline-flex; align-items: center; gap: 5px;
	font-size: 0.78rem; color: #64748b;
}
.fcc-fp-stat-pill strong { color: #1e293b; }

/* Card body */
.fcc-fp-card__body { padding: 24px; }

/* Hub form */
.fcc-fp-hub-field label {
	display: block;
	font-weight: 600;
	font-size: 0.875rem;
	color: #374151;
	margin-bottom: 6px;
}
.fcc-fp-hub-field textarea {
	width: 100%;
	max-width: 760px;
	border: 1px solid #d1d5db;
	border-radius: 8px;
	padding: 10px 12px;
	font-size: 0.9rem;
	line-height: 1.6;
	color: #1e293b;
	resize: vertical;
	transition: border-color .15s, box-shadow .15s;
	box-sizing: border-box;
}
.fcc-fp-hub-field textarea:focus {
	border-color: #3b82f6;
	box-shadow: 0 0 0 3px rgba(59,130,246,.15);
	outline: none;
}
.fcc-fp-hub-field .description {
	margin-top: 6px;
	font-size: 0.8rem;
	color: #94a3b8;
}
.fcc-fp-hub-actions { margin-top: 18px; display: flex; align-items: center; gap: 10px; }

/* Save button */
.fcc-fp-btn-save {
	display: inline-flex; align-items: center; gap: 6px;
	background: #2563eb; color: #fff;
	border: none; border-radius: 8px;
	padding: 8px 18px; font-size: 0.875rem; font-weight: 600;
	cursor: pointer; transition: background .15s, transform .1s;
	line-height: 1;
}
.fcc-fp-btn-save:hover { background: #1d4ed8; }
.fcc-fp-btn-save:active { transform: scale(.97); }

/* Tables */
.fcc-fp-table {
	width: 100%;
	border-collapse: collapse;
	font-size: 0.875rem;
}
.fcc-fp-table th {
	background: #f8fafc;
	border-bottom: 2px solid #e2e8f0;
	padding: 10px 14px;
	text-align: left;
	font-size: 0.75rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .05em;
	color: #64748b;
	white-space: nowrap;
}
.fcc-fp-table td {
	padding: 12px 14px;
	border-bottom: 1px solid #f1f5f9;
	vertical-align: middle;
	color: #374151;
}
.fcc-fp-table tbody tr:last-child td { border-bottom: none; }
.fcc-fp-table tbody tr:hover td { background: #fafbfc; }

.fcc-fp-food-name { font-weight: 600; color: #1e293b; }
.fcc-fp-cat-label { color: #64748b; font-size: 0.82rem; }

/* URL link inside table */
.fcc-fp-table-url {
	color: #3b82f6;
	font-size: 0.8rem;
	text-decoration: none;
	display: inline-flex; align-items: center; gap: 3px;
}
.fcc-fp-table-url:hover { text-decoration: underline; }

/* Badges */
.fcc-fp-badge {
	display: inline-flex; align-items: center; gap: 4px;
	padding: 3px 10px; border-radius: 20px;
	font-size: 0.72rem; font-weight: 700;
	letter-spacing: .03em;
	white-space: nowrap;
}
.fcc-fp-badge--custom { background: #d1fae5; color: #065f46; }
.fcc-fp-badge--auto   { background: #f1f5f9; color: #64748b; }
.fcc-fp-badge--default { background: #fef9c3; color: #854d0e; }

/* Action buttons */
.fcc-fp-actions { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.fcc-fp-btn-view {
	display: inline-flex; align-items: center; gap: 4px;
	background: #fff; color: #374151;
	border: 1px solid #d1d5db; border-radius: 6px;
	padding: 5px 12px; font-size: 0.78rem; font-weight: 500;
	cursor: pointer; text-decoration: none; transition: border-color .15s, background .15s;
	line-height: 1.2;
}
.fcc-fp-btn-view:hover { border-color: #6b7280; background: #f9fafb; color: #111827; }
.fcc-fp-btn-edit {
	display: inline-flex; align-items: center; gap: 4px;
	background: #2563eb; color: #fff;
	border: 1px solid #2563eb; border-radius: 6px;
	padding: 5px 12px; font-size: 0.78rem; font-weight: 600;
	cursor: pointer; text-decoration: none; transition: background .15s;
	line-height: 1.2;
}
.fcc-fp-btn-edit:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
.fcc-fp-btn-toggle {
	display: inline-flex; align-items: center; gap: 4px;
	background: #f1f5f9; color: #374151;
	border: 1px solid #d1d5db; border-radius: 6px;
	padding: 5px 12px; font-size: 0.78rem; font-weight: 500;
	cursor: pointer; transition: background .15s, border-color .15s;
	line-height: 1.2;
}
.fcc-fp-btn-toggle:hover { background: #e2e8f0; border-color: #94a3b8; }
.fcc-fp-btn-toggle.is-open { background: #dbeafe; border-color: #93c5fd; color: #1d4ed8; }
.fcc-fp-btn-cancel {
	background: none; color: #6b7280; border: 1px solid #d1d5db;
	border-radius: 6px; padding: 5px 12px; font-size: 0.78rem;
	cursor: pointer; transition: background .15s; line-height: 1.2;
}
.fcc-fp-btn-cancel:hover { background: #f3f4f6; color: #374151; }

/* Inline edit row */
.fcc-fp-edit-row td {
	padding: 0 !important;
	border-bottom: none !important;
}
.fcc-fp-edit-inner {
	background: #f0f7ff;
	border-left: 3px solid #3b82f6;
	padding: 20px 24px;
	margin: 0;
}
.fcc-fp-edit-inner label {
	display: block; font-weight: 600; font-size: 0.85rem; color: #1e293b; margin-bottom: 8px;
}
.fcc-fp-edit-inner textarea {
	width: 100%; max-width: 700px;
	border: 1px solid #93c5fd; border-radius: 8px;
	padding: 10px 12px; font-size: 0.875rem; line-height: 1.6; color: #1e293b;
	resize: vertical; box-sizing: border-box; background: #fff;
	transition: border-color .15s, box-shadow .15s;
}
.fcc-fp-edit-inner textarea:focus {
	border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); outline: none;
}
.fcc-fp-edit-inner .description { font-size: 0.78rem; color: #94a3b8; margin: 6px 0 14px; }
.fcc-fp-edit-actions { display: flex; align-items: center; gap: 8px; }

/* Search bar (Food Pages) */
.fcc-fp-search-row {
	display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.fcc-fp-search-input {
	flex: 1; min-width: 220px; max-width: 360px;
	border: 1px solid #d1d5db; border-radius: 8px;
	padding: 8px 12px; font-size: 0.875rem; color: #1e293b;
	transition: border-color .15s, box-shadow .15s; box-sizing: border-box;
}
.fcc-fp-search-input:focus {
	border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.12); outline: none;
}
.fcc-fp-btn-search {
	background: #7c3aed; color: #fff;
	border: none; border-radius: 8px;
	padding: 8px 16px; font-size: 0.875rem; font-weight: 600;
	cursor: pointer; transition: background .15s; line-height: 1.2;
}
.fcc-fp-btn-search:hover { background: #6d28d9; }
.fcc-fp-btn-clear {
	background: #fff; color: #6b7280;
	border: 1px solid #d1d5db; border-radius: 8px;
	padding: 8px 14px; font-size: 0.875rem;
	cursor: pointer; text-decoration: none; transition: background .15s; line-height: 1.2;
}
.fcc-fp-btn-clear:hover { background: #f9fafb; color: #374151; }

/* Pagination */
.fcc-fp-pagination {
	display: flex; align-items: center; gap: 8px;
	padding: 16px 24px;
	border-top: 1px solid #f1f5f9;
	flex-wrap: wrap;
}
.fcc-fp-pagination-info {
	font-size: 0.82rem; color: #94a3b8; flex: 1; min-width: 160px;
}
.fcc-fp-pagination-info strong { color: #475569; }
.fcc-fp-page-btn {
	display: inline-flex; align-items: center; gap: 4px;
	background: #fff; color: #374151;
	border: 1px solid #d1d5db; border-radius: 7px;
	padding: 6px 14px; font-size: 0.82rem; font-weight: 500;
	text-decoration: none; transition: background .15s, border-color .15s;
}
.fcc-fp-page-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #1e293b; }

/* Rich pagination */
.fcc-fp-pag { display:flex; align-items:center; gap:12px; padding:14px 24px; border-top:1px solid #f1f5f9; flex-wrap:wrap; }
.fcc-fp-pag-info { font-size:0.82rem; color:#94a3b8; flex:1; min-width:160px; }
.fcc-fp-pag-info strong { color:#475569; }
.fcc-fp-pag-pages { display:flex; align-items:center; gap:3px; flex-wrap:wrap; }
.fcc-fp-pag-btn { display:inline-flex; align-items:center; justify-content:center; min-width:32px; height:32px; padding:0 8px; background:#fff; color:#374151; border:1px solid #e2e8f0; border-radius:7px; font-size:0.8rem; font-weight:500; cursor:pointer; transition:background .12s, border-color .12s, color .12s; }
.fcc-fp-pag-btn:hover:not(:disabled) { background:#f1f5f9; border-color:#94a3b8; color:#1e293b; }
.fcc-fp-pag-btn:disabled { opacity:.4; cursor:default; }
.fcc-fp-pag-btn--active { background:#7c3aed; border-color:#7c3aed; color:#fff; font-weight:600; }
.fcc-fp-pag-btn--active:hover:not(:disabled) { background:#6d28d9; border-color:#6d28d9; color:#fff; }
.fcc-fp-pag-ellipsis { padding:0 4px; color:#94a3b8; font-size:0.85rem; line-height:32px; }
.fcc-fp-pag-controls { display:flex; align-items:center; gap:8px; margin-left:auto; }
.fcc-fp-pag-jump { display:flex; align-items:center; gap:4px; }
.fcc-fp-pag-jump-input { width:78px; height:32px; padding:0 8px; border:1px solid #e2e8f0 !important; border-radius:7px !important; font-size:0.8rem; font-weight:500; color:#374151; text-align:center; background:#fff !important; outline:none; box-shadow:none !important; transition:border-color .12s, box-shadow .12s; -moz-appearance:textfield; }
.fcc-fp-pag-jump-input::-webkit-outer-spin-button,
.fcc-fp-pag-jump-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
.fcc-fp-pag-jump-input:focus { border-color:#7c3aed !important; box-shadow:0 0 0 3px rgba(124,58,237,.12) !important; }
.fcc-fp-pag-go { height:32px; padding:0 12px; background:#7c3aed; color:#fff; border:1px solid #7c3aed; border-radius:7px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:background .12s, border-color .12s; }
.fcc-fp-pag-go:hover { background:#6d28d9; border-color:#6d28d9; }
.fcc-fp-per-page-sel { height:32px; padding:0 28px 0 10px; border:1px solid #e2e8f0 !important; border-radius:7px !important; font-size:0.8rem; font-weight:500; color:#374151; background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 8px center !important; -webkit-appearance:none; appearance:none; cursor:pointer; outline:none; box-shadow:none !important; transition:border-color .12s, box-shadow .12s; }
.fcc-fp-per-page-sel:hover { border-color:#94a3b8 !important; }
.fcc-fp-per-page-sel:focus { border-color:#7c3aed !important; box-shadow:0 0 0 3px rgba(124,58,237,.12) !important; }
#fcc-foods-wrap { transition:opacity .15s; }
#fcc-foods-wrap.fcc-loading { opacity:.5; pointer-events:none; }

/* Notice */
.fcc-fp-notice {
	display: flex; align-items: center; gap: 10px;
	background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
	padding: 12px 18px; margin-bottom: 20px; font-size: 0.875rem; color: #166534;
}

/* Empty state */
.fcc-fp-empty {
	text-align: center; padding: 48px 24px; color: #94a3b8;
}
.fcc-fp-empty svg { margin-bottom: 12px; opacity: .4; }
.fcc-fp-empty p { font-size: 0.9rem; margin: 0; }
</style>

<div class="wrap fcc-admin-wrap fcc-food-pages-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></h1>

	<!-- Hero -->
	<div class="fcc-foods-hero">
		<div class="fcc-foods-hero__inner">
			<div class="fcc-foods-hero__content">
				<div class="fcc-foods-hero__icon" aria-hidden="true"><img src="<?php echo esc_url( FCC_PLUGIN_URL . 'logo/Food Calorie Calculator Favicon - White (1).png' ); ?>" width="40" height="40" alt="" decoding="async" style="display:block;width:40px;height:40px;object-fit:contain;"></div>
				<div>
					<div class="fcc-foods-hero__title"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-foods-hero__sub">
						<?php esc_html_e( 'Manage content for your hub page, category pages, and individual food pages — all in one place.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>
			<div class="fcc-foods-hero__stats">
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value">1</span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Hub Page', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo count( $categories ); ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Category Pages', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-foods-hero-stat">
					<span class="fcc-foods-hero-stat__value"><?php echo number_format( $total_foods ); ?></span>
					<span class="fcc-foods-hero-stat__label"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<?php if ( 'seo' === $saved && $saved_food ) : ?>
		<div class="fcc-fp-notice">
			<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Food SEO title and description saved.', 'food-calorie-calculator' ); ?>
		</div>
	<?php elseif ( 'cat_seo' === $saved && $saved_cat ) : ?>
		<div class="fcc-fp-notice">
			<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Category SEO title and description saved.', 'food-calorie-calculator' ); ?>
		</div>
	<?php elseif ( 'hub_seo' === $saved ) : ?>
		<div class="fcc-fp-notice">
			<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Hub SEO title and description saved.', 'food-calorie-calculator' ); ?>
		</div>
	<?php elseif ( 'hub' === $saved ) : ?>
		<div class="fcc-fp-notice">
			<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Hub page content saved successfully.', 'food-calorie-calculator' ); ?>
		</div>
	<?php elseif ( 'cat' === $saved && $saved_cat ) : ?>
		<div class="fcc-fp-notice">
			<svg width="18" height="18" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="#22c55e"/><path d="M8 12l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Category description saved successfully.', 'food-calorie-calculator' ); ?>
		</div>
	<?php endif; ?>

	<!-- ======================================================================
	     1. HUB PAGE
	     ====================================================================== -->
	<div class="fcc-fp-card">
		<div class="fcc-fp-card__head fcc-fp-card__head--hub">
			<div class="fcc-fp-card__head-left">
				<div class="fcc-fp-card__icon fcc-fp-card__icon--hub">🏠</div>
				<div>
					<p class="fcc-fp-card__title"><?php esc_html_e( 'Hub Page', 'food-calorie-calculator' ); ?></p>
					<div class="fcc-fp-card__meta">
						<a href="<?php echo esc_url( $hub_url ); ?>" target="_blank" class="fcc-fp-url-pill">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
							<?php echo esc_html( str_replace( home_url(), '', $hub_url ) ); ?>
							<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
						</a>
						<?php if ( ! empty( $hub_intro ) ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; <?php esc_html_e( 'Custom intro', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $hub_editorial ) ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; <?php esc_html_e( 'Custom editorial', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
						<?php if ( empty( $hub_intro ) && empty( $hub_editorial ) ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--default"><?php esc_html_e( 'Using default text', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<a href="<?php echo esc_url( $hub_url ); ?>" target="_blank" class="fcc-fp-btn-view">
				<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				<?php esc_html_e( 'View Page', 'food-calorie-calculator' ); ?>
			</a>
		</div>

		<div class="fcc-fp-card__body">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fcc_save_hub_content">
				<?php wp_nonce_field( 'fcc_save_hub_content' ); ?>

				<div class="fcc-fp-hub-field">
					<label for="fcc-hub-intro"><?php esc_html_e( 'Intro Paragraph', 'food-calorie-calculator' ); ?></label>
					<textarea id="fcc-hub-intro" name="hub_intro" rows="4"><?php echo esc_textarea( $hub_intro ); ?></textarea>
					<p class="description">
						<?php esc_html_e( 'Shown below the page title on /calories/. Leave blank to use the built-in default text.', 'food-calorie-calculator' ); ?>
					</p>
				</div>

				<div class="fcc-fp-hub-field">
					<label for="fcc_hub_editorial"><?php esc_html_e( 'Editorial Content (below category grid)', 'food-calorie-calculator' ); ?></label>
					<?php
					wp_editor( $hub_editorial, 'fcc_hub_editorial', [
						'textarea_name' => 'hub_editorial',
						'media_buttons' => false,
						'teeny'         => false,
						'tinymce'       => true,
						'quicktags'     => true,
						'editor_height' => 500,
					] );
					?>
					<p class="description"><?php esc_html_e( 'The editorial H2 sections displayed below the category grid on /calories/. Leave blank to use the built-in default content.', 'food-calorie-calculator' ); ?></p>
				</div>

				<div class="fcc-fp-hub-actions">
					<button type="submit" class="fcc-fp-btn-save">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
						<?php esc_html_e( 'Save Hub Content', 'food-calorie-calculator' ); ?>
					</button>
				</div>
			</form>

			<hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">

			<p style="font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:16px;">
				<?php esc_html_e( 'Hub Page SEO Override', 'food-calorie-calculator' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="fcc_save_hub_seo">
				<?php wp_nonce_field( 'fcc_save_hub_seo' ); ?>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">

					<div>
						<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
							<span style="font-weight:500;"><?php esc_html_e( 'SEO Title Override', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-seo-chr" data-max="60" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
						</label>
						<input type="text" name="hub_seo_title" class="fcc-seo-title-input"
							value="<?php echo esc_attr( $hub_seo_title ); ?>"
							maxlength="60"
							placeholder="<?php echo esc_attr( $hub_auto_title ); ?>"
							style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;">
						<p class="description" style="margin-top:6px;">
							<?php esc_html_e( 'Auto:', 'food-calorie-calculator' ); ?>
							<em style="color:#475569;"><?php echo esc_html( $hub_auto_title ); ?></em>
							&nbsp;<span style="color:#94a3b8;">(<?php echo mb_strlen( $hub_auto_title ); ?> <?php esc_html_e( 'chars', 'food-calorie-calculator' ); ?>)</span>
						</p>
					</div>

					<div>
						<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
							<span style="font-weight:500;"><?php esc_html_e( 'SEO Description Override', 'food-calorie-calculator' ); ?></span>
							<span class="fcc-seo-chr" data-max="160" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
						</label>
						<textarea name="hub_seo_description" class="fcc-seo-desc-input" rows="3" maxlength="160"
							placeholder="<?php echo esc_attr( $hub_auto_desc ); ?>"
							style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;resize:vertical;"><?php echo esc_textarea( $hub_seo_description ); ?></textarea>
						<p class="description" style="margin-top:6px;">
							<?php esc_html_e( 'Auto:', 'food-calorie-calculator' ); ?>
							<em style="color:#475569;"><?php echo esc_html( mb_strimwidth( $hub_auto_desc, 0, 80, '…' ) ); ?></em>
							&nbsp;<span style="color:#94a3b8;">(<?php echo mb_strlen( $hub_auto_desc ); ?> <?php esc_html_e( 'chars', 'food-calorie-calculator' ); ?>)</span>
						</p>
					</div>

				</div>

				<p class="description" style="margin-bottom:12px;color:#64748b;">
					<?php esc_html_e( 'Leave both fields blank to use the auto-generated values.', 'food-calorie-calculator' ); ?>
				</p>

				<div class="fcc-fp-hub-actions">
					<button type="submit" class="fcc-fp-btn-save">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
						<?php esc_html_e( 'Save Hub SEO', 'food-calorie-calculator' ); ?>
					</button>
					<?php if ( ! empty( $hub_seo_title ) || ! empty( $hub_seo_description ) ) : ?>
						<button type="submit" name="hub_seo_title" value=""
							class="fcc-fp-btn-cancel" style="color:#dc2626;border-color:#fca5a5;"
							onclick="this.form.querySelector('[name=hub_seo_description]').value='';"
							title="<?php esc_attr_e( 'Clear overrides and revert to auto-generated', 'food-calorie-calculator' ); ?>">
							&#10005; <?php esc_html_e( 'Clear overrides', 'food-calorie-calculator' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</div>

	<!-- ======================================================================
	     2. CATEGORY PAGES
	     ====================================================================== -->
	<div class="fcc-fp-card">
		<div class="fcc-fp-card__head fcc-fp-card__head--cats">
			<div class="fcc-fp-card__head-left">
				<div class="fcc-fp-card__icon fcc-fp-card__icon--cats">📂</div>
				<div>
					<p class="fcc-fp-card__title"><?php esc_html_e( 'Category Pages', 'food-calorie-calculator' ); ?></p>
					<div class="fcc-fp-card__meta">
						<span class="fcc-fp-stat-pill">
							<strong><?php echo count( $categories ); ?></strong> <?php esc_html_e( 'categories', 'food-calorie-calculator' ); ?>
						</span>
						<?php if ( $custom_cat_count > 0 ) : ?>
							<span class="fcc-fp-stat-pill">
								<strong><?php echo $custom_cat_count; ?></strong> <?php esc_html_e( 'with custom descriptions', 'food-calorie-calculator' ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<table class="fcc-fp-table">
			<thead>
				<tr>
					<th style="width:20%"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
					<th style="width:26%"><?php esc_html_e( 'URL', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Description', 'food-calorie-calculator' ); ?></th>
					<th style="width:8%"><?php esc_html_e( 'Content', 'food-calorie-calculator' ); ?></th>
					<th style="width:8%"><?php esc_html_e( 'SEO', 'food-calorie-calculator' ); ?></th>
					<th style="width:14%"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $categories as $cat ) :
				$cat_url     = home_url( '/calories/' . $cat['slug'] . '/' );
				$has_custom  = ! empty( $cat['description'] );
				$has_cat_seo = ! empty( $cat['seo_title'] ) || ! empty( $cat['seo_description'] );
				$row_id      = 'fcc-cat-edit-' . (int) $cat['id'];
				$seo_row_id  = 'fcc-cat-seo-edit-' . (int) $cat['id'];
				$cat_auto_title = \FCC\Food_Pages::generate_category_title( $cat );
				$cat_auto_desc  = \FCC\Food_Pages::generate_category_meta_desc( $cat );
			?>
				<tr id="fcc-cat-row-<?php echo (int) $cat['id']; ?>">
					<td class="fcc-fp-food-name"><?php echo esc_html( $cat['name'] ); ?></td>
					<td>
						<a href="<?php echo esc_url( $cat_url ); ?>" target="_blank" class="fcc-fp-table-url">
							/calories/<?php echo esc_html( $cat['slug'] ); ?>/
							<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
						</a>
					</td>
					<td style="color:#64748b;font-size:0.82rem;max-width:200px;">
						<?php if ( $has_custom ) : ?>
							<?php echo esc_html( mb_strimwidth( $cat['description'], 0, 90, '…' ) ); ?>
						<?php else : ?>
							<em style="color:#cbd5e1;"><?php esc_html_e( 'No custom description set', 'food-calorie-calculator' ); ?></em>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $has_custom ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; <?php esc_html_e( 'Custom', 'food-calorie-calculator' ); ?></span>
						<?php else : ?>
							<span class="fcc-fp-badge fcc-fp-badge--default"><?php esc_html_e( 'Default', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $has_cat_seo ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--custom">&#10003; <?php esc_html_e( 'Custom', 'food-calorie-calculator' ); ?></span>
						<?php else : ?>
							<span class="fcc-fp-badge fcc-fp-badge--auto"><?php esc_html_e( 'Auto', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<div class="fcc-fp-actions">
							<button type="button"
								class="fcc-fp-btn-toggle fcc-fp-cat-toggle"
								data-target="<?php echo esc_attr( $row_id ); ?>">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
								<?php esc_html_e( 'Edit', 'food-calorie-calculator' ); ?>
							</button>
							<button type="button" class="fcc-fp-btn-toggle fcc-fp-seo-toggle" data-target="<?php echo esc_attr( $seo_row_id ); ?>">
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
								SEO
							</button>
						</div>
					</td>
				</tr>
				<tr id="<?php echo esc_attr( $row_id ); ?>" class="fcc-fp-edit-row" style="display:none;">
					<td colspan="6">
						<div class="fcc-fp-edit-inner">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="fcc_save_cat_content">
								<input type="hidden" name="cat_id" value="<?php echo (int) $cat['id']; ?>">
								<?php wp_nonce_field( 'fcc_save_cat_content' ); ?>
								<label>
									<?php printf(
										/* translators: %s: category name */
										esc_html__( 'Description for %s', 'food-calorie-calculator' ),
										'<strong>' . esc_html( $cat['name'] ) . '</strong>'
									); ?>
								</label>
								<textarea name="description" rows="4"><?php echo esc_textarea( $cat['description'] ?? '' ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Leave blank to use the built-in default description for this category.', 'food-calorie-calculator' ); ?></p>
								<div class="fcc-fp-edit-actions">
									<button type="submit" class="fcc-fp-btn-save">
										<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
										<?php esc_html_e( 'Save Description', 'food-calorie-calculator' ); ?>
									</button>
									<button type="button"
										class="fcc-fp-btn-cancel fcc-fp-cat-cancel"
										data-target="<?php echo esc_attr( $row_id ); ?>">
										<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
									</button>
								</div>
							</form>
						</div>
					</td>
				</tr>
				<tr id="<?php echo esc_attr( $seo_row_id ); ?>" class="fcc-fp-edit-row" style="display:none;">
					<td colspan="6">
						<div class="fcc-fp-edit-inner">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="fcc_save_cat_seo">
								<input type="hidden" name="cat_id" value="<?php echo (int) $cat['id']; ?>">
								<?php wp_nonce_field( 'fcc_save_cat_seo' ); ?>

								<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:16px;">
									<div>
										<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
											<span><?php esc_html_e( 'SEO Title Override', 'food-calorie-calculator' ); ?></span>
											<span class="fcc-seo-chr" data-max="60" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
										</label>
										<input type="text" name="seo_title" class="fcc-seo-title-input"
											value="<?php echo esc_attr( $cat['seo_title'] ?? '' ); ?>"
											maxlength="60"
											placeholder="<?php echo esc_attr( $cat_auto_title ); ?>"
											style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;">
										<p class="description" style="margin-top:6px;">
											<?php esc_html_e( 'Auto:', 'food-calorie-calculator' ); ?>
											<em style="color:#475569;"><?php echo esc_html( $cat_auto_title ); ?></em>
											&nbsp;<span style="color:#94a3b8;">(<?php echo mb_strlen( $cat_auto_title ); ?> <?php esc_html_e( 'chars', 'food-calorie-calculator' ); ?>)</span>
										</p>
									</div>
									<div>
										<label style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
											<span><?php esc_html_e( 'SEO Description Override', 'food-calorie-calculator' ); ?></span>
											<span class="fcc-seo-chr" data-max="160" style="font-size:0.75rem;color:#94a3b8;font-weight:400;"></span>
										</label>
										<textarea name="seo_description" class="fcc-seo-desc-input" rows="3" maxlength="160"
											placeholder="<?php echo esc_attr( $cat_auto_desc ); ?>"
											style="width:100%;border:1px solid #93c5fd;border-radius:8px;padding:9px 12px;font-size:0.875rem;box-sizing:border-box;resize:vertical;"><?php echo esc_textarea( $cat['seo_description'] ?? '' ); ?></textarea>
										<p class="description" style="margin-top:6px;">
											<?php esc_html_e( 'Auto:', 'food-calorie-calculator' ); ?>
											<em style="color:#475569;"><?php echo esc_html( mb_strimwidth( $cat_auto_desc, 0, 80, '…' ) ); ?></em>
											&nbsp;<span style="color:#94a3b8;">(<?php echo mb_strlen( $cat_auto_desc ); ?> <?php esc_html_e( 'chars', 'food-calorie-calculator' ); ?>)</span>
										</p>
									</div>
								</div>

								<p class="description" style="margin-bottom:12px;color:#64748b;">
									<?php esc_html_e( 'Leave both fields blank to use the auto-generated values.', 'food-calorie-calculator' ); ?>
								</p>

								<div class="fcc-fp-edit-actions">
									<button type="submit" class="fcc-fp-btn-save">
										<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
										<?php esc_html_e( 'Save SEO', 'food-calorie-calculator' ); ?>
									</button>
									<button type="button" class="fcc-fp-btn-cancel fcc-fp-seo-cancel" data-target="<?php echo esc_attr( $seo_row_id ); ?>">
										<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
									</button>
									<?php if ( $has_cat_seo ) : ?>
										<button type="submit" name="seo_title" value="" class="fcc-fp-btn-cancel" style="color:#dc2626;border-color:#fca5a5;"
											onclick="this.form.querySelector('[name=seo_description]').value='';"
											title="<?php esc_attr_e( 'Clear overrides', 'food-calorie-calculator' ); ?>">
											&#10005; <?php esc_html_e( 'Clear overrides', 'food-calorie-calculator' ); ?>
										</button>
									<?php endif; ?>
								</div>
							</form>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- ======================================================================
	     3. FOOD PAGES
	     ====================================================================== -->
	<div class="fcc-fp-card">
		<div class="fcc-fp-card__head fcc-fp-card__head--foods">
			<div class="fcc-fp-card__head-left">
				<div class="fcc-fp-card__icon fcc-fp-card__icon--foods">🍽️</div>
				<div>
					<p class="fcc-fp-card__title"><?php esc_html_e( 'Food Pages', 'food-calorie-calculator' ); ?></p>
					<div class="fcc-fp-card__meta">
						<span class="fcc-fp-stat-pill">
							<strong><?php echo number_format( $total_foods ); ?></strong> <?php esc_html_e( 'food pages', 'food-calorie-calculator' ); ?>
						</span>
						<?php if ( $search ) : ?>
							<span class="fcc-fp-badge fcc-fp-badge--auto"><?php printf( esc_html__( 'Filtering: "%s"', 'food-calorie-calculator' ), esc_html( $search ) ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="fcc-food-pages">
				<div class="fcc-fp-search-row">
					<input type="search" name="fps" value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php esc_attr_e( 'Search food pages…', 'food-calorie-calculator' ); ?>"
						class="fcc-fp-search-input">
					<button type="submit" class="fcc-fp-btn-search">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
						<?php esc_html_e( 'Search', 'food-calorie-calculator' ); ?>
					</button>
					<?php if ( $search ) : ?>
						<a href="<?php echo esc_url( $page_url ); ?>" class="fcc-fp-btn-clear"><?php esc_html_e( 'Clear', 'food-calorie-calculator' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>

		<?php if ( $foods ) : ?>
		<div id="fcc-foods-wrap">
			<table class="fcc-fp-table">
				<thead>
					<tr>
						<th style="width:24%"><?php esc_html_e( 'Food Name', 'food-calorie-calculator' ); ?></th>
						<th style="width:15%"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></th>
						<th><?php esc_html_e( 'Page URL', 'food-calorie-calculator' ); ?></th>
						<th style="width:9%"><?php esc_html_e( 'Content', 'food-calorie-calculator' ); ?></th>
						<th style="width:9%"><?php esc_html_e( 'SEO', 'food-calorie-calculator' ); ?></th>
						<th style="width:16%"><?php esc_html_e( 'Actions', 'food-calorie-calculator' ); ?></th>
					</tr>
				</thead>
				<tbody id="fcc-foods-tbody">
					<?php echo \FCC\Admin\Food_Pages_Admin::render_food_rows( $foods, $cat_map, $cat_slug_map ); ?>
				</tbody>
			</table>
			<div id="fcc-foods-pagination">
				<?php echo \FCC\Admin\Food_Pages_Admin::render_pagination_html( $paged, $total_pages, $total_foods, $per_page, $search ); ?>
			</div>
		</div>
		<?php else : ?>
		<div class="fcc-fp-empty">
			<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
			<p><?php echo $search ? esc_html__( 'No food pages match your search.', 'food-calorie-calculator' ) : esc_html__( 'No food pages found.', 'food-calorie-calculator' ); ?></p>
		</div>
		<?php endif; ?>
	</div>

</div><!-- .wrap -->

<script>
( function () {
	var ajaxUrl   = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce     = '<?php echo esc_js( wp_create_nonce( 'fcc_food_pages_ajax' ) ); ?>';
	var curPaged  = <?php echo (int) $paged; ?>;
	var curPP     = <?php echo (int) $per_page; ?>;
	var curSearch = <?php echo wp_json_encode( $search ); ?>;

	// --- Category toggles (rows not AJAX-replaced) ---
	document.querySelectorAll( '.fcc-fp-cat-toggle' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var row    = document.getElementById( btn.dataset.target );
			var isOpen = row && row.style.display !== 'none';
			if ( row ) row.style.display = isOpen ? 'none' : '';
			btn.classList.toggle( 'is-open', ! isOpen );
			var label = btn.lastChild;
			if ( label && label.nodeType === 3 ) {
				label.textContent = isOpen
					? ' <?php echo esc_js( __( 'Edit', 'food-calorie-calculator' ) ); ?>'
					: ' <?php echo esc_js( __( 'Close', 'food-calorie-calculator' ) ); ?>';
			}
		} );
	} );
	document.querySelectorAll( '.fcc-fp-cat-cancel' ).forEach( function ( btn ) {
		btn.addEventListener( 'click', function () {
			var row = document.getElementById( btn.dataset.target );
			if ( row ) row.style.display = 'none';
			var toggle = document.querySelector( '[data-target="' + btn.dataset.target + '"].fcc-fp-cat-toggle' );
			if ( toggle ) toggle.classList.remove( 'is-open' );
		} );
	} );

	// --- Food SEO toggles (event delegation — tbody replaced by AJAX) ---
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.fcc-fp-seo-toggle' );
		if ( btn ) {
			var row    = document.getElementById( btn.dataset.target );
			var isOpen = row && row.style.display !== 'none';
			if ( row ) row.style.display = isOpen ? 'none' : '';
			btn.classList.toggle( 'is-open', ! isOpen );
			return;
		}
		var cancel = e.target.closest( '.fcc-fp-seo-cancel' );
		if ( cancel ) {
			var cRow = document.getElementById( cancel.dataset.target );
			if ( cRow ) cRow.style.display = 'none';
			var toggle = document.querySelector( '[data-target="' + cancel.dataset.target + '"].fcc-fp-seo-toggle' );
			if ( toggle ) toggle.classList.remove( 'is-open' );
		}
	} );

	// --- Char counters (delegated so they work after AJAX) ---
	function fccSeoCounter( input ) {
		var parent = input.closest( 'div' );
		var label  = parent ? parent.querySelector( '.fcc-seo-chr' ) : null;
		if ( ! label ) return;
		var max = parseInt( label.dataset.max, 10 ) || 60;
		var len = input.value.length;
		label.textContent = len + ' / ' + max;
		label.style.color = len > max ? '#dc2626' : ( len >= max * 0.85 ? '#d97706' : '#94a3b8' );
	}
	document.querySelectorAll( '.fcc-seo-title-input, .fcc-seo-desc-input' ).forEach( function ( inp ) {
		fccSeoCounter( inp );
	} );
	document.addEventListener( 'input', function ( e ) {
		if ( e.target.matches( '.fcc-seo-title-input, .fcc-seo-desc-input' ) ) {
			fccSeoCounter( e.target );
		}
	} );

	// --- AJAX food page loader ---
	var wrap         = document.getElementById( 'fcc-foods-wrap' );
	var tbody        = document.getElementById( 'fcc-foods-tbody' );
	var paginationDiv = document.getElementById( 'fcc-foods-pagination' );

	function loadFoodPage( page, pp, search ) {
		if ( wrap ) wrap.classList.add( 'fcc-loading' );
		var fd = new FormData();
		fd.append( 'action',   'fcc_food_pages_list' );
		fd.append( 'nonce',    nonce );
		fd.append( 'paged',    page );
		fd.append( 'per_page', pp );
		fd.append( 'search',   search );
		fetch( ajaxUrl, { method: 'POST', body: fd } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( data ) {
				if ( data.success ) {
					if ( tbody )        tbody.innerHTML         = data.data.rows;
					if ( paginationDiv ) paginationDiv.innerHTML = data.data.pagination;
					curPaged  = page;
					curPP     = pp;
					curSearch = search;
					document.querySelectorAll( '#fcc-foods-tbody .fcc-seo-title-input, #fcc-foods-tbody .fcc-seo-desc-input' ).forEach( function ( inp ) {
						fccSeoCounter( inp );
					} );
					if ( wrap ) wrap.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				}
			} )
			.catch( function () {} )
			.finally( function () {
				if ( wrap ) wrap.classList.remove( 'fcc-loading' );
			} );
	}

	// Pagination button clicks (delegated).
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.fcc-fp-pag-btn' );
		if ( btn && paginationDiv && paginationDiv.contains( btn ) ) {
			var page = parseInt( btn.dataset.page, 10 );
			if ( page && page !== curPaged ) loadFoodPage( page, curPP, curSearch );
			return;
		}
		var go = e.target.closest( '.fcc-fp-pag-go' );
		if ( go && paginationDiv && paginationDiv.contains( go ) ) {
			var jumpInput = paginationDiv.querySelector( '.fcc-fp-pag-jump-input' );
			var page = jumpInput ? parseInt( jumpInput.value, 10 ) : 0;
			if ( page >= 1 ) loadFoodPage( page, curPP, curSearch );
		}
	} );

	// Jump-to-page Enter key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Enter' && e.target.matches( '.fcc-fp-pag-jump-input' ) ) {
			e.preventDefault();
			var page = parseInt( e.target.value, 10 );
			if ( page >= 1 ) loadFoodPage( page, curPP, curSearch );
		}
	} );

	// Per-page selector change.
	document.addEventListener( 'change', function ( e ) {
		if ( e.target.matches( '.fcc-fp-per-page-sel' ) ) {
			var pp = parseInt( e.target.value, 10 );
			if ( pp ) loadFoodPage( 1, pp, curSearch );
		}
	} );

	// Scroll to saved food SEO row and open it.
	<?php if ( 'seo' === $saved && $saved_food ) : ?>
	( function () {
		var row     = document.getElementById( 'fcc-food-row-<?php echo (int) $saved_food; ?>' );
		var target  = 'fcc-seo-edit-<?php echo (int) $saved_food; ?>';
		var editRow = document.getElementById( target );
		var btn     = document.querySelector( '[data-target="' + target + '"].fcc-fp-seo-toggle' );
		if ( row ) row.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		if ( editRow ) editRow.style.display = '';
		if ( btn )     btn.classList.add( 'is-open' );
	} )();
	<?php endif; ?>

	// Scroll to saved category SEO row and open it.
	<?php if ( 'cat_seo' === $saved && $saved_cat ) : ?>
	( function () {
		var row     = document.getElementById( 'fcc-cat-row-<?php echo (int) $saved_cat; ?>' );
		var target  = 'fcc-cat-seo-edit-<?php echo (int) $saved_cat; ?>';
		var editRow = document.getElementById( target );
		var btn     = document.querySelector( '[data-target="' + target + '"].fcc-fp-seo-toggle' );
		if ( row ) row.scrollIntoView( { behavior: 'smooth', block: 'center' } );
		if ( editRow ) editRow.style.display = '';
		if ( btn )     btn.classList.add( 'is-open' );
	} )();
	<?php endif; ?>
} )();
</script>
