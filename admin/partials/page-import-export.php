<?php
/**
 * Admin: Import / Export page — redesigned.
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$import_errors = get_transient( 'fcc_import_errors' );
if ( $import_errors ) {
	delete_transient( 'fcc_import_errors' );
}

$columns = FCC\Import_Export::columns();

global $wpdb;
$food_count     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}fcc_foods" );
$cat_count      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}fcc_categories" );
$categories     = FCC\Database::get_all_categories();
$import_history = get_option( 'fcc_import_history', [] );
if ( ! is_array( $import_history ) ) { $import_history = []; }
?>
<div class="wrap fcc-admin-wrap fcc-ie-page">

	<h1 class="screen-reader-text"><?php esc_html_e( 'Import / Export', 'food-calorie-calculator' ); ?></h1>

	<!-- ======================================================================
	     Hero header
	     ====================================================================== -->
	<div class="fcc-ie-hero">
		<div class="fcc-ie-hero__inner">
			<div class="fcc-ie-hero__content">
				<div class="fcc-ie-hero__icon" aria-hidden="true"><img src="<?php echo esc_url( FCC_PLUGIN_URL . 'logo/Food Calorie Calculator Favicon - White (1).png' ); ?>" width="48" height="48" alt="" decoding="async" style="display:block;width:48px;height:48px;object-fit:contain;"></div>
				<div>
					<div class="fcc-ie-hero__title"><?php esc_html_e( 'Import / Export', 'food-calorie-calculator' ); ?></div>
					<p class="fcc-ie-hero__sub">
						<?php esc_html_e( 'Bulk-manage your food database. Import from CSV or Excel to add or update foods, or export a full backup at any time.', 'food-calorie-calculator' ); ?>
					</p>
				</div>
			</div>
			<div class="fcc-ie-hero__stats">
				<div class="fcc-ie-hero-stat">
					<span class="fcc-ie-hero-stat__value"><?php echo $food_count; ?></span>
					<span class="fcc-ie-hero-stat__label"><?php esc_html_e( 'Foods', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-ie-hero-stat">
					<span class="fcc-ie-hero-stat__value"><?php echo $cat_count; ?></span>
					<span class="fcc-ie-hero-stat__label"><?php esc_html_e( 'Categories', 'food-calorie-calculator' ); ?></span>
				</div>
				<div class="fcc-ie-hero-stat">
					<span class="fcc-ie-hero-stat__value"><?php echo count( $columns ); ?></span>
					<span class="fcc-ie-hero-stat__label"><?php esc_html_e( 'Columns', 'food-calorie-calculator' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<!-- Import row-skip errors -->
	<?php if ( $import_errors ) : ?>
		<div class="fcc-ie-errors">
			<span class="fcc-ie-errors__icon" aria-hidden="true">⚠️</span>
			<div class="fcc-ie-errors__body">
				<strong><?php esc_html_e( 'Some rows were skipped during import:', 'food-calorie-calculator' ); ?></strong>
				<ul class="fcc-ie-errors__list">
					<?php foreach ( $import_errors as $err ) : ?>
						<li><?php echo esc_html( $err ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<!-- ======================================================================
	     Import + Export cards
	     ====================================================================== -->
	<div class="fcc-ie-actions">

		<!-- ---------------------------------------------------------------- -->
		<!-- Import card                                                       -->
		<!-- ---------------------------------------------------------------- -->
		<div class="fcc-ie-card">
			<div class="fcc-ie-card__header fcc-ie-card__header--import">
				<span class="fcc-ie-card__hicon" aria-hidden="true">📥</span>
				<div>
					<div class="fcc-ie-card__htitle"><?php esc_html_e( 'Import Foods', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-ie-card__hsub"><?php esc_html_e( 'Upload CSV or Excel to add or update foods', 'food-calorie-calculator' ); ?></div>
				</div>
			</div>

			<div class="fcc-ie-card__body">
				<form method="post" enctype="multipart/form-data"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					id="fcc-import-form">
					<input type="hidden" name="action" value="fcc_import">
					<?php wp_nonce_field( 'fcc_import' ); ?>

					<!-- Drop zone -->
					<label class="fcc-ie-dropzone" id="fcc-dropzone">
						<span class="fcc-ie-dropzone__cloud" aria-hidden="true">
							<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
						</span>
						<span class="fcc-ie-dropzone__primary"><?php esc_html_e( 'Drop your file here, or click to browse', 'food-calorie-calculator' ); ?></span>
						<span class="fcc-ie-dropzone__secondary" id="fcc-dz-hint">
							<?php esc_html_e( 'Supports CSV and XLSX — max 10 MB', 'food-calorie-calculator' ); ?>
						</span>
						<div class="fcc-ie-dropzone__badges">
							<span class="fcc-ie-fbadge fcc-ie-fbadge--csv">CSV</span>
							<span class="fcc-ie-fbadge fcc-ie-fbadge--xlsx">XLSX</span>
						</div>
						<input type="file" id="fcc_import_file" name="fcc_import_file"
							accept=".csv,.xlsx" required class="fcc-ie-file-input">
					</label>

					<!-- Selected file preview -->
					<div class="fcc-ie-file-preview" id="fcc-file-preview" hidden>
						<span class="fcc-ie-file-preview__icon" id="fcc-file-icon" aria-hidden="true">📄</span>
						<span class="fcc-ie-file-preview__name" id="fcc-file-name"></span>
						<button type="button" class="fcc-ie-file-preview__clear" id="fcc-file-clear"
							aria-label="<?php esc_attr_e( 'Remove selected file', 'food-calorie-calculator' ); ?>">×</button>
					</div>

					<!-- Guidance notes -->
					<ul class="fcc-ie-import-notes">
						<li>
							<span class="fcc-ie-import-notes__icon" aria-hidden="true">🔄</span>
							<?php esc_html_e( 'Existing foods (matched by slug) are updated; new slugs are inserted.', 'food-calorie-calculator' ); ?>
						</li>
						<li>
							<span class="fcc-ie-import-notes__icon" aria-hidden="true">🚫</span>
							<?php esc_html_e( 'Leave Omega-3 and caffeine cells blank to store NULL — never use 0 as a substitute.', 'food-calorie-calculator' ); ?>
						</li>
						<li>
							<span class="fcc-ie-import-notes__icon" aria-hidden="true">🏷️</span>
							<?php esc_html_e( 'Unknown category names are created automatically on import.', 'food-calorie-calculator' ); ?>
						</li>
					</ul>

					<div class="fcc-ie-template-row">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=fcc_download_template' ), 'fcc_download_template' ) ); ?>"
							class="fcc-ie-template-btn">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
							<?php esc_html_e( 'Download Template CSV', 'food-calorie-calculator' ); ?>
						</a>
						<span class="fcc-ie-template-hint"><?php esc_html_e( 'Headers + 1 example row — fill in and import', 'food-calorie-calculator' ); ?></span>
					</div>

					<button type="submit" class="fcc-ie-import-btn" id="fcc-import-btn" disabled>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
						<?php esc_html_e( 'Import Foods', 'food-calorie-calculator' ); ?>
					</button>

				</form>

				<div id="fcc-import-result" class="fcc-import-result" hidden></div>

			</div>
		</div><!-- .fcc-ie-card (import) -->

		<!-- ---------------------------------------------------------------- -->
		<!-- Export card                                                       -->
		<!-- ---------------------------------------------------------------- -->
		<div class="fcc-ie-card">
			<div class="fcc-ie-card__header fcc-ie-card__header--export">
				<span class="fcc-ie-card__hicon" aria-hidden="true">📤</span>
				<div>
					<div class="fcc-ie-card__htitle"><?php esc_html_e( 'Export Foods', 'food-calorie-calculator' ); ?></div>
					<div class="fcc-ie-card__hsub"><?php esc_html_e( 'Download your full food database as a file', 'food-calorie-calculator' ); ?></div>
				</div>
			</div>

			<div class="fcc-ie-card__body">

				<p class="fcc-ie-export-summary">
					<?php printf(
						esc_html__( 'Your export will include all %d foods. NULL values export as empty cells so they round-trip cleanly on re-import.', 'food-calorie-calculator' ),
						$food_count
					); ?>
				</p>

				<!-- Category filter for export -->
				<div class="fcc-ie-export-filter">
					<label for="fcc-export-cat" class="fcc-ie-export-filter__label"><?php esc_html_e( 'Filter by category:', 'food-calorie-calculator' ); ?></label>
					<select id="fcc-export-cat" class="fcc-ie-export-filter__select">
						<option value="0"><?php esc_html_e( 'All Categories', 'food-calorie-calculator' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo absint( $cat['id'] ); ?>"><?php echo esc_html( $cat['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- CSV option -->
				<div class="fcc-ie-export-row">
					<div class="fcc-ie-export-row__icon fcc-ie-export-row__icon--csv" aria-hidden="true">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
					</div>
					<div class="fcc-ie-export-row__info">
						<strong class="fcc-ie-export-row__title">CSV</strong>
						<span class="fcc-ie-export-row__desc"><?php esc_html_e( 'Universal — opens in any spreadsheet or text editor', 'food-calorie-calculator' ); ?></span>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="fcc_export_csv">
						<?php wp_nonce_field( 'fcc_export_csv' ); ?>
						<button type="submit" class="fcc-ie-dl-btn fcc-ie-dl-btn--csv">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
							<?php esc_html_e( 'Download CSV', 'food-calorie-calculator' ); ?>
						</button>
					</form>
				</div>

				<div class="fcc-ie-export-divider"></div>

				<!-- XLSX option -->
				<div class="fcc-ie-export-row">
					<div class="fcc-ie-export-row__icon fcc-ie-export-row__icon--xlsx" aria-hidden="true">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>
					</div>
					<div class="fcc-ie-export-row__info">
						<strong class="fcc-ie-export-row__title">Excel (.xlsx)</strong>
						<span class="fcc-ie-export-row__desc"><?php esc_html_e( 'Native Excel format — best for Microsoft Office &amp; Google Sheets', 'food-calorie-calculator' ); ?></span>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="fcc_export_xlsx">
						<?php wp_nonce_field( 'fcc_export_xlsx' ); ?>
						<button type="submit" class="fcc-ie-dl-btn fcc-ie-dl-btn--xlsx">
							<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
							<?php esc_html_e( 'Download XLSX', 'food-calorie-calculator' ); ?>
						</button>
					</form>
				</div>

				<!-- Export tip -->
				<div class="fcc-ie-export-tip">
					<span aria-hidden="true">💡</span>
					<?php esc_html_e( 'Export → edit in Excel → re-import to bulk-update your food data.', 'food-calorie-calculator' ); ?>
				</div>

			</div>
		</div><!-- .fcc-ie-card (export) -->

	</div><!-- .fcc-ie-actions -->

	<!-- ======================================================================
	     Column reference
	     ====================================================================== -->
	<div class="fcc-ie-colref">

		<div class="fcc-ie-colref__hd">
			<h2 class="fcc-ie-colref__title"><?php esc_html_e( 'Column Reference', 'food-calorie-calculator' ); ?></h2>
			<p class="fcc-ie-colref__sub">
				<?php esc_html_e( 'The first row of your file must contain these exact column headers (case-sensitive).', 'food-calorie-calculator' ); ?>
			</p>
		</div>

		<div class="fcc-ie-colref__grid">
			<?php foreach ( $columns as $key => $def ) :
				$is_required = (bool) $def['required'];
				$is_nullable = (bool) ( $def['nullable'] ?? false );
				$is_json     = 'json' === ( $def['type'] ?? '' );
			?>
				<div class="fcc-ie-col-card <?php echo $is_nullable ? 'fcc-ie-col-card--nullable' : ''; ?>">
					<div class="fcc-ie-col-card__top">
						<code class="fcc-ie-col-card__key"><?php echo esc_html( $key ); ?></code>
						<?php if ( $is_required ) : ?>
							<span class="fcc-ie-col-badge fcc-ie-col-badge--req"><?php esc_html_e( 'Required', 'food-calorie-calculator' ); ?></span>
						<?php else : ?>
							<span class="fcc-ie-col-badge fcc-ie-col-badge--opt"><?php esc_html_e( 'Optional', 'food-calorie-calculator' ); ?></span>
						<?php endif; ?>
					</div>
					<div class="fcc-ie-col-card__label"><?php echo esc_html( $def['label'] ); ?></div>
					<?php if ( $is_nullable ) : ?>
						<div class="fcc-ie-col-card__note fcc-ie-col-card__note--null">
							<span aria-hidden="true">∅</span> <?php esc_html_e( 'Blank = NULL (not zero)', 'food-calorie-calculator' ); ?>
						</div>
					<?php elseif ( $is_json ) : ?>
						<div class="fcc-ie-col-card__note">
							<code class="fcc-ie-col-card__eg">[{"label":"1 slice","grams":33}]</code>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

	</div><!-- .fcc-ie-colref -->

	<?php if ( ! empty( $import_history ) ) : ?>
	<!-- ======================================================================
	     Import History
	     ====================================================================== -->
	<div class="fcc-ie-history">
		<div class="fcc-ie-history__hd">
			<h2 class="fcc-ie-history__title">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2D7A4F" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
				<?php esc_html_e( 'Import History', 'food-calorie-calculator' ); ?>
			</h2>
		</div>
		<table class="fcc-ie-history__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'File', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Imported', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'Skipped', 'food-calorie-calculator' ); ?></th>
					<th><?php esc_html_e( 'User', 'food-calorie-calculator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $import_history as $h ) : ?>
					<tr>
						<td class="fcc-ie-history__date"><?php echo esc_html( date_i18n( 'M j, Y H:i', strtotime( $h['date'] ) ) ); ?></td>
						<td><code class="fcc-ie-history__file"><?php echo esc_html( $h['file'] ); ?></code></td>
						<td class="fcc-ie-history__num fcc-ie-history__num--ok"><?php echo (int) $h['imported']; ?></td>
						<td class="fcc-ie-history__num <?php echo $h['skipped'] > 0 ? 'fcc-ie-history__num--warn' : ''; ?>"><?php echo (int) $h['skipped']; ?></td>
						<td><?php echo esc_html( $h['user'] ?? '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

</div><!-- .wrap -->

<script>
( function () {
	'use strict';

	const input     = document.getElementById( 'fcc_import_file' );
	const dropzone  = document.getElementById( 'fcc-dropzone' );
	const preview   = document.getElementById( 'fcc-file-preview' );
	const nameEl    = document.getElementById( 'fcc-file-name' );
	const iconEl    = document.getElementById( 'fcc-file-icon' );
	const clearBtn  = document.getElementById( 'fcc-file-clear' );
	const submitBtn = document.getElementById( 'fcc-import-btn' );
	if ( ! input || ! dropzone ) return;

	function showFile( file ) {
		if ( ! file ) return;
		const ext    = file.name.split( '.' ).pop().toLowerCase();
		iconEl.textContent = ext === 'xlsx' ? '📊' : '📄';
		nameEl.textContent = file.name + ' (' + ( file.size > 1048576
			? ( file.size / 1048576 ).toFixed( 1 ) + ' MB'
			: Math.round( file.size / 1024 ) + ' KB' ) + ')';
		preview.hidden = false;
		dropzone.classList.add( 'fcc-ie-dropzone--file' );
		submitBtn.disabled = false;
	}

	function clearFile() {
		input.value        = '';
		preview.hidden     = true;
		nameEl.textContent = '';
		dropzone.classList.remove( 'fcc-ie-dropzone--file', 'fcc-ie-dropzone--over' );
		submitBtn.disabled = true;
	}

	input.addEventListener( 'change', function () { showFile( this.files[0] ); } );

	clearBtn.addEventListener( 'click', function ( e ) {
		e.preventDefault();
		clearFile();
	} );

	// Drag and drop.
	dropzone.addEventListener( 'dragover', function ( e ) {
		e.preventDefault();
		this.classList.add( 'fcc-ie-dropzone--over' );
	} );
	dropzone.addEventListener( 'dragleave', function ( e ) {
		if ( ! this.contains( e.relatedTarget ) ) {
			this.classList.remove( 'fcc-ie-dropzone--over' );
		}
	} );
	dropzone.addEventListener( 'drop', function ( e ) {
		e.preventDefault();
		this.classList.remove( 'fcc-ie-dropzone--over' );
		const file = e.dataTransfer.files[0];
		if ( ! file ) return;
		try {
			const dt = new DataTransfer();
			dt.items.add( file );
			input.files = dt.files;
		} catch ( _ ) {}
		showFile( file );
	} );
} )();
</script>
