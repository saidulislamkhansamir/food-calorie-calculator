<?php
/**
 * Admin: Add / Edit food form.
 *
 * $food       = array|null  (null when adding)
 * $categories = array       (from page-foods-list.php)
 *
 * @package FCC
 */

defined( 'ABSPATH' ) || exit;

$is_edit  = ! empty( $food );
$food_id  = $is_edit ? (int) $food['id'] : 0;
$page_title = $is_edit
	? __( 'Edit Food', 'food-calorie-calculator' )
	: __( 'Add New Food', 'food-calorie-calculator' );

/**
 * Helper: output a number field, showing empty when value is NULL.
 */
function fcc_num_field( string $id, string $name, $value, string $label, bool $required = false, string $step = '0.01', string $hint = '' ): void {
	$display = ( null === $value || '' === $value ) ? '' : number_format( (float) $value, 3, '.', '' );
	printf(
		'<div class="fcc-field%s"><label for="%s">%s%s</label><input type="number" id="%s" name="%s" value="%s" step="%s" min="0"%s class="regular-text">%s</div>',
		$required ? ' fcc-field--required' : '',
		esc_attr( $id ),
		esc_html( $label ),
		$required ? ' <span class="required">*</span>' : '',
		esc_attr( $id ),
		esc_attr( $name ),
		esc_attr( $display ),
		esc_attr( $step ),
		$required ? ' required' : '',
		$hint ? '<p class="description">' . esc_html( $hint ) . '</p>' : ''
	);
}
?>
<div class="wrap fcc-admin-wrap">
	<h1><?php echo esc_html( $page_title ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="page-title-action">
		← <?php esc_html_e( 'Back to Foods', 'food-calorie-calculator' ); ?>
	</a>

	<?php FCC\Admin\Foods::maybe_render_notice(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fcc-form fcc-food-form">
		<input type="hidden" name="action" value="fcc_save_food">
		<input type="hidden" name="food_id" value="<?php echo esc_attr( $food_id ); ?>">
		<?php wp_nonce_field( 'fcc_save_food' ); ?>

		<div class="fcc-form-grid">

			<!-- Left column: core fields -->
			<div class="fcc-form-col">
				<div class="fcc-card">
					<h2 class="fcc-card__title"><?php esc_html_e( 'Basic Info', 'food-calorie-calculator' ); ?></h2>
					<div class="fcc-field fcc-field--required">
						<label for="food_name"><?php esc_html_e( 'Name', 'food-calorie-calculator' ); ?> <span class="required">*</span></label>
						<input type="text" id="food_name" name="name" required
							value="<?php echo $is_edit ? esc_attr( $food['name'] ) : esc_attr( sanitize_text_field( $_GET['food_name'] ?? '' ) ); ?>"
							class="regular-text">
					</div>
					<div class="fcc-field">
						<label for="food_category"><?php esc_html_e( 'Category', 'food-calorie-calculator' ); ?></label>
						<select id="food_category" name="category_id" class="regular-text">
							<option value="0"><?php esc_html_e( '— None —', 'food-calorie-calculator' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo absint( $cat['id'] ); ?>"
									<?php selected( $is_edit ? $food['category_id'] : 0, $cat['id'] ); ?>>
									<?php echo esc_html( $cat['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="fcc-field">
						<label>
							<input type="checkbox" name="is_fruit_veg" value="1"
								<?php checked( $is_edit && ! empty( $food['is_fruit_veg'] ) ); ?>>
							<?php esc_html_e( 'Is fruit or vegetable (for future NHS 5-a-day feature)', 'food-calorie-calculator' ); ?>
						</label>
					</div>
				</div>

				<!-- Core nutrients (per 100g) -->
				<div class="fcc-card">
					<h2 class="fcc-card__title"><?php esc_html_e( 'Nutrients per 100g', 'food-calorie-calculator' ); ?></h2>
					<div class="fcc-nutrient-grid">
						<?php
						fcc_num_field( 'energy_kcal', 'energy_kcal', $is_edit ? $food['energy_kcal'] : null, __( 'Energy (kcal)', 'food-calorie-calculator' ), true, '0.01' );
						fcc_num_field( 'energy_kj', 'energy_kj', $is_edit ? $food['energy_kj'] : null, __( 'Energy (kJ)', 'food-calorie-calculator' ), true, '0.01' );
						fcc_num_field( 'protein_g', 'protein_g', $is_edit ? $food['protein_g'] : null, __( 'Protein (g)', 'food-calorie-calculator' ), true );
						fcc_num_field( 'carbohydrate_g', 'carbohydrate_g', $is_edit ? $food['carbohydrate_g'] : null, __( 'Carbohydrate (g)', 'food-calorie-calculator' ), true );
						fcc_num_field( 'of_which_sugars_g', 'of_which_sugars_g', $is_edit ? $food['of_which_sugars_g'] : null, __( 'of which Sugars (g)', 'food-calorie-calculator' ), false, '0.001', __( 'Leave empty if not available', 'food-calorie-calculator' ) );
						fcc_num_field( 'fat_g', 'fat_g', $is_edit ? $food['fat_g'] : null, __( 'Fat (g)', 'food-calorie-calculator' ), true );
						fcc_num_field( 'of_which_saturates_g', 'of_which_saturates_g', $is_edit ? $food['of_which_saturates_g'] : null, __( 'of which Saturates (g)', 'food-calorie-calculator' ), false, '0.001', __( 'Leave empty if not available', 'food-calorie-calculator' ) );
						fcc_num_field( 'fibre_g', 'fibre_g', $is_edit ? $food['fibre_g'] : null, __( 'Fibre (g)', 'food-calorie-calculator' ), false, '0.001', __( 'Leave empty if not available', 'food-calorie-calculator' ) );
						fcc_num_field( 'salt_g', 'salt_g', $is_edit ? $food['salt_g'] : null, __( 'Salt (g) — UK uses salt, not sodium', 'food-calorie-calculator' ), false, '0.001', __( 'Leave empty if not available', 'food-calorie-calculator' ) );
						?>
					</div>
				</div>

				<!-- Serving sizes -->
				<div class="fcc-card">
					<h2 class="fcc-card__title"><?php esc_html_e( 'Serving Sizes', 'food-calorie-calculator' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Add serving sizes so users can pick "1 slice", "1 cup" etc instead of entering grams manually.', 'food-calorie-calculator' ); ?></p>
					<div id="fcc-serving-sizes">
						<?php
						$servings = $is_edit && ! empty( $food['serving_sizes'] ) ? $food['serving_sizes'] : [];
						if ( ! $servings ) {
							$servings[] = [ 'label' => '', 'grams' => '' ];
						}
						foreach ( $servings as $i => $srv ) :
						?>
						<div class="fcc-serving-row">
							<input type="text" name="serving_label[]"
								value="<?php echo esc_attr( $srv['label'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'e.g. 1 slice', 'food-calorie-calculator' ); ?>"
								class="fcc-serving-label">
							<input type="number" name="serving_grams[]"
								value="<?php echo esc_attr( $srv['grams'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'grams', 'food-calorie-calculator' ); ?>"
								step="0.1" min="0.1" class="fcc-serving-grams">
							<button type="button" class="button fcc-remove-serving">×</button>
						</div>
						<?php endforeach; ?>
					</div>
					<button type="button" class="button fcc-add-serving">
						+ <?php esc_html_e( 'Add Serving Size', 'food-calorie-calculator' ); ?>
					</button>
				</div>
			</div>

			<!-- Right column: optional nutrients + notes -->
			<div class="fcc-form-col">

				<!-- Omega-3 (nullable) -->
				<div class="fcc-card fcc-card--optional">
					<h2 class="fcc-card__title">
						<?php esc_html_e( 'Omega-3 Fatty Acids', 'food-calorie-calculator' ); ?>
						<span class="fcc-badge fcc-badge--optional"><?php esc_html_e( 'Optional — leave empty if no verified data', 'food-calorie-calculator' ); ?></span>
					</h2>
					<p class="description">
						<?php esc_html_e( 'Only populate from a verified published source (e.g. USDA FoodData Central). Leave ALL fields empty if data is unavailable — the calculator hides the section rather than showing zero.', 'food-calorie-calculator' ); ?>
					</p>
					<div class="fcc-nutrient-grid">
						<?php
						fcc_num_field( 'omega3_total_mg', 'omega3_total_mg', $is_edit ? $food['omega3_total_mg'] : null, __( 'Total Omega-3 (mg)', 'food-calorie-calculator' ), false, '0.001', __( 'Leave empty if data unavailable', 'food-calorie-calculator' ) );
						fcc_num_field( 'omega3_ala_mg', 'omega3_ala_mg', $is_edit ? $food['omega3_ala_mg'] : null, __( 'ALA (mg)', 'food-calorie-calculator' ), false, '0.001', __( 'Alpha-linolenic acid — plant-based omega-3', 'food-calorie-calculator' ) );
						fcc_num_field( 'omega3_epa_mg', 'omega3_epa_mg', $is_edit ? $food['omega3_epa_mg'] : null, __( 'EPA (mg)', 'food-calorie-calculator' ), false, '0.001', __( 'Eicosapentaenoic acid — mainly from fish/seafood', 'food-calorie-calculator' ) );
						fcc_num_field( 'omega3_dha_mg', 'omega3_dha_mg', $is_edit ? $food['omega3_dha_mg'] : null, __( 'DHA (mg)', 'food-calorie-calculator' ), false, '0.001', __( 'Docosahexaenoic acid — mainly from fish/seafood', 'food-calorie-calculator' ) );
						?>
					</div>
				</div>

				<!-- Caffeine (nullable) -->
				<div class="fcc-card fcc-card--optional">
					<h2 class="fcc-card__title">
						<?php esc_html_e( 'Caffeine', 'food-calorie-calculator' ); ?>
						<span class="fcc-badge fcc-badge--optional"><?php esc_html_e( 'Optional — leave empty if no data', 'food-calorie-calculator' ); ?></span>
					</h2>
					<p class="description">
						<?php esc_html_e( 'mg per 100g (solid) or per 100ml (liquid). Only populate for foods/drinks with published caffeine content. Leave empty for all others.', 'food-calorie-calculator' ); ?>
					</p>
					<?php fcc_num_field( 'caffeine_mg', 'caffeine_mg', $is_edit ? $food['caffeine_mg'] : null, __( 'Caffeine (mg/100g or 100ml)', 'food-calorie-calculator' ), false, '0.01', __( 'Leave empty if not applicable', 'food-calorie-calculator' ) ); ?>
				</div>

				<!-- Source notes -->
				<div class="fcc-card">
					<h2 class="fcc-card__title"><?php esc_html_e( 'Source / Notes', 'food-calorie-calculator' ); ?></h2>
					<div class="fcc-field">
						<label for="source_notes"><?php esc_html_e( 'Data source & notes', 'food-calorie-calculator' ); ?></label>
						<textarea id="source_notes" name="source_notes" rows="4" class="large-text"><?php
							echo $is_edit ? esc_textarea( $food['source_notes'] ?? '' ) : '';
						?></textarea>
						<p class="description"><?php esc_html_e( 'Cite the data source for each value (e.g. "M&W 8th ed.; Omega-3: USDA FDC #175167"). Used in the readme and for data integrity.', 'food-calorie-calculator' ); ?></p>
					</div>
				</div>

				<!-- Allergens & Dietary Tags -->
				<div class="fcc-card">
					<h2 class="fcc-card__title"><?php esc_html_e( 'Allergens & Dietary Tags', 'food-calorie-calculator' ); ?></h2>
					<div class="fcc-field">
						<label><?php esc_html_e( 'Contains Allergens', 'food-calorie-calculator' ); ?></label>
						<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:6px">
							<?php
							$allergens = [
								'allergen_fish'      => __( '🐟 Fish', 'food-calorie-calculator' ),
								'allergen_shellfish' => __( '🦐 Shellfish', 'food-calorie-calculator' ),
								'allergen_dairy'     => __( '🥛 Dairy', 'food-calorie-calculator' ),
								'allergen_eggs'      => __( '🥚 Eggs', 'food-calorie-calculator' ),
								'allergen_nuts'      => __( '🥜 Tree Nuts', 'food-calorie-calculator' ),
								'allergen_gluten'    => __( '🌾 Gluten', 'food-calorie-calculator' ),
								'allergen_soy'       => __( '🫘 Soy', 'food-calorie-calculator' ),
								'allergen_celery'    => __( '🥬 Celery', 'food-calorie-calculator' ),
							];
							foreach ( $allergens as $key => $label ) : ?>
								<label style="display:flex;align-items:center;gap:4px;font-size:13px">
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
										<?php checked( $is_edit && ! empty( $food[ $key ] ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="fcc-field" style="margin-top:16px">
						<label><?php esc_html_e( 'Dietary Tags', 'food-calorie-calculator' ); ?></label>
						<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:6px">
							<?php
							$diets = [
								'diet_keto'       => __( '🥑 Keto', 'food-calorie-calculator' ),
								'diet_paleo'      => __( '🍖 Paleo', 'food-calorie-calculator' ),
								'diet_halal'      => __( '☪️ Halal', 'food-calorie-calculator' ),
								'diet_kosher'     => __( '✡️ Kosher', 'food-calorie-calculator' ),
								'diet_vegan'      => __( '🌱 Vegan', 'food-calorie-calculator' ),
								'diet_vegetarian' => __( '🥬 Vegetarian', 'food-calorie-calculator' ),
							];
							foreach ( $diets as $key => $label ) : ?>
								<label style="display:flex;align-items:center;gap:4px;font-size:13px">
									<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1"
										<?php checked( $is_edit && ! empty( $food[ $key ] ) ); ?>>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Sponsorship -->
				<div class="fcc-card fcc-card--sponsorship">
					<h2 class="fcc-card__title">
						<?php esc_html_e( 'Sponsorship', 'food-calorie-calculator' ); ?>
						<span class="fcc-badge fcc-badge--optional"><?php esc_html_e( 'Monetisation', 'food-calorie-calculator' ); ?></span>
					</h2>
					<p class="description" style="margin-bottom:1rem">
						<?php esc_html_e( 'Mark this food as a paid sponsored listing. Sponsored foods appear first in search results with a "Sponsored" badge.', 'food-calorie-calculator' ); ?>
					</p>

					<div class="fcc-field">
						<label>
							<input type="checkbox" name="is_sponsored" id="fcc-is-sponsored" value="1"
								<?php checked( $is_edit && ! empty( $food['is_sponsored'] ) ); ?>>
							<?php esc_html_e( 'Is Sponsored', 'food-calorie-calculator' ); ?>
						</label>
					</div>

					<div id="fcc-sponsor-fields" style="<?php echo ( $is_edit && ! empty( $food['is_sponsored'] ) ) ? '' : 'display:none'; ?>">

						<div class="fcc-field" style="margin-top:.75rem">
							<label>
								<input type="checkbox" name="sponsor_active" value="1"
									<?php checked( $is_edit && ! empty( $food['sponsor_active'] ) ); ?>>
								<?php esc_html_e( 'Active (show on frontend)', 'food-calorie-calculator' ); ?>
							</label>
						</div>

						<div class="fcc-field">
							<label for="sponsor_name"><?php esc_html_e( 'Brand Name', 'food-calorie-calculator' ); ?></label>
							<input type="text" id="sponsor_name" name="sponsor_name" class="regular-text"
								placeholder="<?php esc_attr_e( 'e.g. Tesco Finest', 'food-calorie-calculator' ); ?>"
								value="<?php echo esc_attr( $is_edit ? ( $food['sponsor_name'] ?? '' ) : '' ); ?>">
							<p class="description"><?php esc_html_e( 'Shown as "Sponsored by [Brand Name]" on the frontend.', 'food-calorie-calculator' ); ?></p>
						</div>

						<div class="fcc-field">
							<label for="sponsor_url"><?php esc_html_e( 'Brand URL', 'food-calorie-calculator' ); ?></label>
							<input type="url" id="sponsor_url" name="sponsor_url" class="regular-text"
								placeholder="https://"
								value="<?php echo esc_attr( $is_edit ? ( $food['sponsor_url'] ?? '' ) : '' ); ?>">
							<p class="description"><?php esc_html_e( 'Link opens in a new tab (rel="sponsored").', 'food-calorie-calculator' ); ?></p>
						</div>

						<div class="fcc-field">
							<label><?php esc_html_e( 'Brand Logo', 'food-calorie-calculator' ); ?></label>
							<?php
							$logo_id  = $is_edit ? (int) ( $food['sponsor_logo_id'] ?? 0 ) : 0;
							$logo_url = $logo_id ? wp_get_attachment_url( $logo_id ) : '';
							?>
							<input type="hidden" id="sponsor_logo_id" name="sponsor_logo_id" value="<?php echo esc_attr( $logo_id ?: '' ); ?>">
							<div id="fcc-sponsor-logo-preview" style="<?php echo $logo_url ? '' : 'display:none'; ?>;margin-bottom:.5rem">
								<img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-height:60px;border:1px solid #ddd;border-radius:4px;padding:4px">
							</div>
							<button type="button" id="fcc-sponsor-logo-select" class="button">
								<?php esc_html_e( 'Select Logo', 'food-calorie-calculator' ); ?>
							</button>
							<button type="button" id="fcc-sponsor-logo-remove" class="button"
								style="<?php echo $logo_url ? '' : 'display:none'; ?>">
								<?php esc_html_e( 'Remove', 'food-calorie-calculator' ); ?>
							</button>
							<p class="description"><?php esc_html_e( 'Recommended: transparent PNG, max 200×80px.', 'food-calorie-calculator' ); ?></p>
						</div>

						<div class="fcc-field">
							<label for="sponsor_expires_at"><?php esc_html_e( 'Expires', 'food-calorie-calculator' ); ?></label>
							<input type="date" id="sponsor_expires_at" name="sponsor_expires_at" class="regular-text"
								value="<?php
									$exp = $is_edit ? ( $food['sponsor_expires_at'] ?? '' ) : '';
									echo esc_attr( $exp ? date( 'Y-m-d', strtotime( $exp ) ) : '' );
								?>">
							<p class="description"><?php esc_html_e( 'Leave empty for no expiry. After this date the sponsorship is automatically paused.', 'food-calorie-calculator' ); ?></p>
						</div>

					</div><!-- #fcc-sponsor-fields -->
				</div>

				<!-- Submit -->
				<div class="fcc-card">
					<?php submit_button( $is_edit ? __( 'Update Food', 'food-calorie-calculator' ) : __( 'Add Food', 'food-calorie-calculator' ), 'primary large', 'submit', false ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=fcc-foods' ) ); ?>" class="button button-large">
						<?php esc_html_e( 'Cancel', 'food-calorie-calculator' ); ?>
					</a>
				</div>

			</div><!-- .fcc-form-col -->
		</div><!-- .fcc-form-grid -->

<script>
(function () {
	var cb    = document.getElementById( 'fcc-is-sponsored' );
	var panel = document.getElementById( 'fcc-sponsor-fields' );
	if ( cb && panel ) {
		cb.addEventListener( 'change', function () {
			panel.style.display = this.checked ? '' : 'none';
		} );
	}

	// WP Media Library logo picker.
	var selectBtn  = document.getElementById( 'fcc-sponsor-logo-select' );
	var removeBtn  = document.getElementById( 'fcc-sponsor-logo-remove' );
	var logoInput  = document.getElementById( 'sponsor_logo_id' );
	var logoPreview = document.getElementById( 'fcc-sponsor-logo-preview' );

	if ( selectBtn ) {
		selectBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			var frame = wp.media( {
				title: 'Select Brand Logo',
				button: { text: 'Use this logo' },
				multiple: false,
				library: { type: 'image' },
			} );
			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				logoInput.value = attachment.id;
				logoPreview.querySelector( 'img' ).src = attachment.url;
				logoPreview.style.display = '';
				if ( removeBtn ) removeBtn.style.display = '';
			} );
			frame.open();
		} );
	}

	if ( removeBtn ) {
		removeBtn.addEventListener( 'click', function () {
			logoInput.value = '';
			logoPreview.style.display = 'none';
			removeBtn.style.display = 'none';
		} );
	}
}() );
</script>
	</form>
</div><!-- .wrap -->
