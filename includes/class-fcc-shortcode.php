<?php
/**
 * [food_calorie_calculator] shortcode.
 *
 * Renders the calculator shell; all interactivity is handled by
 * fcc-calculator.js. Enqueues assets only on pages where the shortcode
 * or block is present (conditional enqueue pattern).
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	/** Set to true when the shortcode is found on the current page. */
	private bool $enqueue_assets = false;

	/** White-label data for the active license (if any), shared with enqueue_public_assets(). */
	private static ?array $wl_active = null;

	public function register( Loader $loader ): void {
		$loader->add_action( 'init', $this, 'register_shortcode' );
		$loader->add_action( 'wp_enqueue_scripts', $this, 'maybe_enqueue_style_early' );
		$loader->add_action( 'wp_head', $this, 'maybe_output_pwa_manifest', 1 );
		$loader->add_action( 'wp_footer', $this, 'maybe_enqueue_assets' );
		add_action( 'wp_head', [ $this, 'output_theme_compat_css' ], 9999 );
	}

	/**
	 * Output theme-compatibility CSS at the very end of <head> so it loads
	 * after WoodMart and other aggressive theme stylesheets.
	 */
	public function output_theme_compat_css(): void {
		echo '<style id="fcc-theme-compat">
/* Search — #fcc-calculator ID (specificity 1,0,0) beats any WoodMart class rule */
#fcc-calculator .fcc-autocomplete{position:relative!important;display:block!important;width:100%!important;overflow:visible!important;}
#fcc-calculator .fcc-results-dropdown{position:absolute!important;top:calc(100% + 6px)!important;left:0!important;right:0!important;z-index:9999!important;}
#fcc-calculator .fcc-search-icon{position:absolute!important;left:0.9rem!important;right:auto!important;top:50%!important;bottom:auto!important;transform:translateY(-50%)!important;pointer-events:none!important;display:flex!important;align-items:center!important;z-index:2!important;color:#075B5E!important;}
#fcc-calculator .fcc-search-icon svg{width:18px!important;height:18px!important;display:block!important;flex-shrink:0!important;}
/* Voice button: sits left of clear; clear is on the far right */
#fcc-calculator .fcc-voice-btn{position:absolute!important;right:3rem!important;left:auto!important;top:50%!important;bottom:auto!important;transform:translateY(-50%)!important;z-index:10!important;margin:0!important;width:36px!important;height:36px!important;border-radius:50%!important;display:flex!important;align-items:center!important;justify-content:center!important;background:#075B5E!important;color:#fff!important;border:none!important;box-shadow:0 2px 6px rgba(7,91,94,.3)!important;}
#fcc-calculator .fcc-voice-btn[hidden]{display:none!important;}
/* Clear button: pink circle on the far right, 0.6rem from border */
#fcc-calculator .fcc-search-clear{position:absolute!important;right:0.6rem!important;left:auto!important;top:50%!important;bottom:auto!important;transform:translateY(-50%)!important;z-index:10!important;margin:0!important;padding:0!important;width:28px!important;height:28px!important;border-radius:50%!important;background:#fee2e2!important;border:none!important;color:#dc2626!important;cursor:pointer!important;transition:background 0.15s!important;}
#fcc-calculator .fcc-search-clear[hidden]{display:none!important;}
#fcc-calculator .fcc-search-clear:not([hidden]){display:flex!important;align-items:center!important;justify-content:center!important;}
#fcc-calculator .fcc-search-clear:hover{background:#fecaca!important;}
#fcc-calculator .fcc-search-clear svg{display:block!important;width:13px!important;height:13px!important;flex-shrink:0!important;stroke:#dc2626!important;}
#fcc-calculator .fcc-search-input{width:100%!important;max-width:100%!important;box-sizing:border-box!important;padding-left:3rem!important;padding-right:5.5rem!important;}
/* Tabs */
.fcc-calculator .fcc-tab-btn{text-transform:none!important;letter-spacing:normal!important;}
/* Popular / trending chips */
.fcc-calculator .fcc-popular-chips .fcc-popular-chip,
.fcc-calculator .fcc-popular-chip.fcc-popular-chip{
	display:inline-flex!important;align-items:center!important;
	padding:0.25rem 0.65rem!important;font-size:0.78rem!important;
	font-weight:400!important;line-height:1.4!important;
	min-height:0!important;height:auto!important;width:auto!important;
	border-radius:999px!important;text-transform:none!important;
	letter-spacing:normal!important;box-shadow:none!important;
	margin:0!important;white-space:nowrap!important;
}
/* PWA install button */
.fcc-calculator .fcc-pwa-install-btn{
	display:flex!important;align-items:center!important;justify-content:center!important;
	min-height:0!important;height:auto!important;
	padding:0.4rem 1.1rem!important;font-size:0.82rem!important;
	font-weight:600!important;line-height:1.4!important;
	text-transform:none!important;letter-spacing:normal!important;
	width:fit-content!important;border-radius:8px!important;
}
</style>' . "\n";
	}

	public function register_shortcode(): void {
		add_shortcode( 'food_calorie_calculator', [ $this, 'render' ] );
	}

	/**
	 * Render the calculator HTML.
	 *
	 * @param array<string,string>|string $atts
	 */
	public function render( $atts ): string {
		$this->enqueue_assets = true;

		$atts = shortcode_atts(
			[
				'title'    => '',
				'category' => '',
				'license'  => '',
			],
			$atts,
			'food_calorie_calculator'
		);

		$settings   = Settings::get_all();
		$features   = $settings['features'] ?? [];
		$appearance = $settings['appearance'] ?? [];
		$labels     = $settings['labels'] ?? [];
		$general    = $settings['general'] ?? [];

		// Resolve white-label license key: shortcode attr takes precedence over URL param.
		$wl_key     = ! empty( $atts['license'] ) ? sanitize_text_field( $atts['license'] ) : sanitize_text_field( $_GET['wl'] ?? '' );
		$wl_license = null;
		$wl_data    = null;

		if ( $wl_key ) {
			$lic = Database::get_wl_license_by_key( $wl_key );
			if ( $lic && $lic['status'] === 'active' && ( empty( $lic['expires_at'] ) || strtotime( $lic['expires_at'] ) > time() ) ) {
				// Domain restriction check (skip for enterprise tier).
				$allowed = (array) $lic['allowed_domains'];
				if ( $lic['tier'] !== 'enterprise' && ! empty( $allowed ) ) {
					$host    = strtolower( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
					$referer = strtolower( (string) parse_url( wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ), PHP_URL_HOST ) );
					$valid   = false;
					foreach ( $allowed as $d ) {
						$d = strtolower( trim( $d ) );
						if ( $d && ( $host === $d || $referer === $d || str_ends_with( $host, '.' . $d ) || str_ends_with( $referer, '.' . $d ) ) ) {
							$valid = true;
							break;
						}
					}
					if ( ! $valid ) {
						$lic = null;
					}
				}
			} else {
				$lic = null;
			}

			if ( $lic ) {
				$wl_license = $lic;
				$wl_data    = [
					'brandName'     => ! empty( $lic['brand_name'] ) ? esc_html( $lic['brand_name'] ) : null,
					'logoUrl'       => ! empty( $lic['logo_url'] ) ? esc_url( $lic['logo_url'] ) : null,
					'hidePoweredBy' => (bool) $lic['hide_powered_by'],
					'tier'          => $lic['tier'],
				];
				// Override appearance CSS with license branding (Pro+ tiers).
				if ( in_array( $lic['tier'], [ 'professional', 'enterprise' ], true ) ) {
					if ( ! empty( $lic['primary_colour'] ) ) $appearance['primary_colour'] = $lic['primary_colour'];
					if ( ! empty( $lic['accent_colour'] ) )  $appearance['accent_colour']  = $lic['accent_colour'];
				}
				// Share with enqueue_public_assets() (custom CSS + wl object in fccData).
				self::$wl_active = [
					'wl_data'    => $wl_data,
					'custom_css' => $lic['tier'] === 'enterprise' ? ( $lic['custom_css'] ?? '' ) : '',
				];
				// Fire-and-forget embed load counter.
				Database::increment_wl_embed_load( $wl_key );
			}
		}

		// Ad slot HTML — injected at specific positions inside/around the template.
		$ad_slot_above_search     = \FCC\Admin\Ads::get_slot_html( 'above_search' );
		$ad_slot_before_results   = \FCC\Admin\Ads::get_slot_html( 'before_results' );
		$ad_slot_after_results    = \FCC\Admin\Ads::get_slot_html( 'after_results' );
		$ad_slot_below_calculator = \FCC\Admin\Ads::get_slot_html( 'below_calculator' );

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/frontend-calculator.php';
		$html = (string) ob_get_clean();

		if ( ! isset( $features['powered_by_footer'] ) || ! empty( $features['powered_by_footer'] ) ) {
			$html .= '<div class="fcc-powered-by"><span>Powered by <a href="https://foodcaloriecalculator.co.uk" target="_blank" rel="noopener">Food Calorie Calculator</a></span></div>';
		}

		// Below-calculator ad slot (outside the widget wrapper).
		if ( ! empty( $ad_slot_below_calculator ) ) {
			$html .= '<div class="fcc-ad-slot fcc-ad-slot--below-calculator">' . $ad_slot_below_calculator . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		return $html;
	}

	/**
	 * Output PWA manifest link in <head> if pwa_install feature is enabled.
	 */
	public function maybe_output_pwa_manifest(): void {
		if ( ! is_singular() ) return;
		$features = Settings::get_section( 'features' );
		if ( empty( $features['pwa_install'] ) ) return;
		global $post;
		if ( ! $post ) return;
		if ( ! has_shortcode( $post->post_content, 'food_calorie_calculator' )
			&& ! ( function_exists( 'has_block' ) && has_block( 'fcc/calculator', $post ) ) ) return;

		$manifest_url = FCC_PLUGIN_URL . 'assets/pwa/manifest.json';
		echo '<link rel="manifest" href="' . esc_url( $manifest_url ) . '">' . "\n";
		echo '<meta name="theme-color" content="#075B5E">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( FCC_PLUGIN_URL . 'assets/pwa/icon.svg' ) . '">' . "\n";
	}

	/**
	 * Pre-enqueue CSS in <head> to prevent FOUC.
	 * Checks if the current post/page contains our shortcode or block.
	 */
	public function maybe_enqueue_style_early(): void {
		if ( ! is_singular() ) return;
		global $post;
		if ( ! $post ) return;
		$has_shortcode = has_shortcode( $post->post_content, 'food_calorie_calculator' );
		$has_block     = function_exists( 'has_block' ) && has_block( 'fcc/calculator', $post );
		if ( ! $has_shortcode && ! $has_block ) return;

		wp_enqueue_style(
			'fcc-public',
			FCC_PLUGIN_URL . 'assets/css/fcc-public.css',
			[],
			FCC_VERSION
		);

		$settings   = Settings::get_all();
		$appearance = $settings['appearance'] ?? [];
		$custom_props = ':root{--fcc-primary:' . esc_attr( $appearance['primary_colour'] ?? '#075B5E' )
			. ';--fcc-accent:' . esc_attr( $appearance['accent_colour'] ?? '#FF3F33' )
			. ';--fcc-bg-colour:' . esc_attr( $appearance['background_colour'] ?? '#FFE6E1' ) . ';}';
		if ( ! empty( $appearance['custom_css'] ) ) {
			$custom_props .= wp_strip_all_tags( $appearance['custom_css'] );
		}
		wp_add_inline_style( 'fcc-public', $custom_props );
	}

	/**
	 * Enqueue public assets — called from wp_footer so we know if the
	 * shortcode was actually used on this page load.
	 */
	public function maybe_enqueue_assets(): void {
		if ( ! $this->enqueue_assets ) {
			return;
		}
		$this->enqueue_public_assets();

		// Output ad network scripts in footer — all major ad networks support footer injection.
		$ad_scripts = \FCC\Admin\Ads::get_head_scripts();
		if ( $ad_scripts ) {
			echo $ad_scripts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Enqueue all frontend CSS and JS plus the localised data object.
	 * Called both by the shortcode and by FCC_Block when the block is present.
	 */
	public static function enqueue_public_assets(): void {
		$settings   = Settings::get_all();
		$features   = $settings['features'] ?? [];
		$appearance = $settings['appearance'] ?? [];
		$general    = $settings['general'] ?? [];
		$labels     = $settings['labels'] ?? [];
		$advanced   = $settings['advanced'] ?? [];

		$ver = FCC_VERSION;

		wp_enqueue_style(
			'fcc-public',
			FCC_PLUGIN_URL . 'assets/css/fcc-public.css',
			[],
			$ver
		);

		// Inline CSS custom properties — skip if already added by early hook.
		static $inline_added = false;
		if ( ! $inline_added ) {
			$custom_props = sprintf(
				':root{--fcc-primary:%s;--fcc-accent:%s;--fcc-bg:%s;--fcc-radius:%dpx;}',
				esc_attr( $appearance['primary_colour']    ?? '#075B5E' ),
				esc_attr( $appearance['accent_colour']     ?? '#FF3F33' ),
				esc_attr( $appearance['background_colour'] ?? '#FFE6E1' ),
				absint(   $appearance['button_radius']     ?? 8 )
			);
			if ( ! empty( $appearance['custom_css'] ) ) {
				$custom_props .= wp_strip_all_tags( $appearance['custom_css'] );
			}
			wp_add_inline_style( 'fcc-public', $custom_props );
			$inline_added = true;
		}

		wp_enqueue_script(
			'fcc-calculator',
			FCC_PLUGIN_URL . 'assets/js/fcc-calculator.js',
			[],
			$ver,
			true
		);

		// Pass settings + REST nonce to JS via wp_localize_script.
		wp_localize_script(
			'fcc-calculator',
			'fccData',
			[
				'restUrl'    => esc_url_raw( rest_url( 'fcc/v1' ) ),
				'pluginUrl'  => esc_url_raw( FCC_PLUGIN_URL ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'features'   => $features,
				'general'    => [
					'defaultUnit'       => $general['default_unit']    ?? 'metric',
					'decimalPlaces'     => absint( $general['decimal_places'] ?? 1 ),
					'showNutrients'     => $general['show_nutrients']  ?? [],
					'defaultQuantity'   => absint( $general['default_quantity']    ?? 100 ),
					'maxQuantity'       => absint( $general['max_quantity']        ?? 9999 ),
					'searchResultLimit' => absint( $general['search_result_limit'] ?? 10 ),
					'popularFoodsCount' => absint( $general['popular_foods_count'] ?? 8 ),
					'searchDebounce'    => absint( $general['search_debounce']     ?? 280 ),
					// UK Reference Intakes.
					'ri' => [
						'energy_kcal'    => (float) ( $general['ri_energy_kcal']    ?? 2000 ),
						'energy_kj'      => (float) ( $general['ri_energy_kj']      ?? 8400 ),
						'fat_g'          => (float) ( $general['ri_fat_g']          ?? 70   ),
						'saturates_g'    => (float) ( $general['ri_saturates_g']    ?? 20   ),
						'carbohydrate_g' => (float) ( $general['ri_carbohydrate_g'] ?? 260  ),
						'sugars_g'       => (float) ( $general['ri_sugars_g']       ?? 90   ),
						'protein_g'      => (float) ( $general['ri_protein_g']      ?? 50   ),
						'fibre_g'        => (float) ( $general['ri_fibre_g']        ?? 30   ),
						'salt_g'         => (float) ( $general['ri_salt_g']         ?? 6    ),
					],
					// FSA traffic-light thresholds per 100g.
					'fsa' => [
						'fat_low'           => (float) ( $general['fsa_fat_low']          ?? 3    ),
						'fat_high'          => (float) ( $general['fsa_fat_high']         ?? 17.5 ),
						'saturates_low'     => (float) ( $general['fsa_saturates_low']    ?? 1.5  ),
						'saturates_high'    => (float) ( $general['fsa_saturates_high']   ?? 5    ),
						'sugars_low'        => (float) ( $general['fsa_sugars_low']       ?? 5    ),
						'sugars_high'       => (float) ( $general['fsa_sugars_high']      ?? 22.5 ),
						'salt_low'          => (float) ( $general['fsa_salt_low']         ?? 0.3  ),
						'salt_high'         => (float) ( $general['fsa_salt_high']        ?? 1.5  ),
					],
				],
				'labels'     => array_map( 'esc_html', $labels ),
				'i18n'       => [
					'searchPlaceholder' => esc_html__( 'Search for a food…', 'food-calorie-calculator' ),
					'noResults'         => esc_html__( 'No foods found', 'food-calorie-calculator' ),
					'requestFood'       => esc_html__( 'Request this food', 'food-calorie-calculator' ),
					'loading'           => esc_html__( 'Searching…', 'food-calorie-calculator' ),
					'perLabel'          => esc_html__( 'per', 'food-calorie-calculator' ),
					'grams'             => esc_html__( 'g', 'food-calorie-calculator' ),
					'ounces'            => esc_html__( 'oz', 'food-calorie-calculator' ),
					'calories'          => esc_html__( 'kcal', 'food-calorie-calculator' ),
					'dataNotAvailable'  => esc_html__( 'Data not available', 'food-calorie-calculator' ),
					'addToMeal'         => esc_html__( 'Add to Meal', 'food-calorie-calculator' ),
					'remove'            => esc_html__( 'Remove', 'food-calorie-calculator' ),
					'mealTotal'         => esc_html__( 'Meal Total', 'food-calorie-calculator' ),
					'copyDone'          => esc_html__( 'Link copied!', 'food-calorie-calculator' ),
					'printTitle'        => esc_html__( 'Food Calorie Calculator — Results', 'food-calorie-calculator' ),
					'male'              => esc_html__( 'Male', 'food-calorie-calculator' ),
					'female'            => esc_html__( 'Female', 'food-calorie-calculator' ),
					'sedentary'         => esc_html__( 'Sedentary (little/no exercise)', 'food-calorie-calculator' ),
					'light'             => esc_html__( 'Light (1-3 days/wk)', 'food-calorie-calculator' ),
					'moderate'          => esc_html__( 'Moderate (3-5 days/wk)', 'food-calorie-calculator' ),
					'active'            => esc_html__( 'Active (6-7 days/wk)', 'food-calorie-calculator' ),
					'veryActive'        => esc_html__( 'Very Active (2x/day)', 'food-calorie-calculator' ),
					'lose'              => esc_html__( 'Lose weight', 'food-calorie-calculator' ),
					'maintain'          => esc_html__( 'Maintain weight', 'food-calorie-calculator' ),
					'gain'              => esc_html__( 'Gain weight', 'food-calorie-calculator' ),
				],
				'appearance'  => [
					'chartProteinColour' => esc_attr( $appearance['chart_protein_colour'] ?? '#3b82f6' ),
					'chartCarbsColour'   => esc_attr( $appearance['chart_carbs_colour']   ?? '#f59e0b' ),
					'chartFatColour'     => esc_attr( $appearance['chart_fat_colour']     ?? '#ef4444' ),
					'chartOtherColour'   => esc_attr( $appearance['chart_other_colour']   ?? '#94a3b8' ),
					'layout'             => sanitize_key( $appearance['layout']           ?? 'standard' ),
					'resultsAnimation'   => ! empty( $appearance['results_animation'] ?? true ),
					'cardStyle'          => sanitize_key( $appearance['card_style']       ?? 'elevated' ),
					'voiceIcon'          => sanitize_key( $appearance['voice_icon']       ?? 'emoji' ),
					'voiceColour'        => esc_attr( $appearance['voice_colour']         ?? '#075B5E' ),
					'voiceSize'          => sanitize_key( $appearance['voice_size']        ?? 'medium' ),
				],
				'advanced'    => [
					'healthThresholds' => [
						'highProtein'       => (float) ( $advanced['hl_high_protein']        ?? 15 ),
						'lowFat'            => (float) ( $advanced['hl_low_fat']             ?? 3 ),
						'lowCalorie'        => (float) ( $advanced['hl_low_calorie']         ?? 100 ),
						'lowSugar'          => (float) ( $advanced['hl_low_sugar']           ?? 5 ),
						'highFibre'         => (float) ( $advanced['hl_high_fibre']          ?? 6 ),
						'lowSalt'           => (float) ( $advanced['hl_low_salt']            ?? 0.3 ),
						'omega3Rich'        => (float) ( $advanced['hl_omega3_rich']         ?? 500 ),
						'warnHighSalt'      => (float) ( $advanced['hl_warn_high_salt']      ?? 1.5 ),
						'warnHighSaturates' => (float) ( $advanced['hl_warn_high_saturates'] ?? 5 ),
						'warnHighSugar'     => (float) ( $advanced['hl_warn_high_sugar']     ?? 22.5 ),
					],
					'bmrFormula'            => sanitize_key( $advanced['bmr_formula'] ?? 'mifflin' ),
					'calorieGoalAdjustment' => absint( $advanced['calorie_goal_adjustment'] ?? 500 ),
					'searchMinChars'        => absint( $advanced['search_min_chars'] ?? 2 ),
					'mealCategories'        => [
						'breakfast' => [
							'label' => sanitize_text_field( $advanced['meal_cat_breakfast_label'] ?? 'Breakfast' ),
							'emoji' => sanitize_text_field( $advanced['meal_cat_breakfast_emoji'] ?? '🌅' ),
							'start' => absint( $advanced['meal_cat_breakfast_start'] ?? 5 ),
							'end'   => absint( $advanced['meal_cat_breakfast_end'] ?? 11 ),
						],
						'lunch' => [
							'label' => sanitize_text_field( $advanced['meal_cat_lunch_label'] ?? 'Lunch' ),
							'emoji' => sanitize_text_field( $advanced['meal_cat_lunch_emoji'] ?? '🍽️' ),
							'start' => absint( $advanced['meal_cat_lunch_start'] ?? 11 ),
							'end'   => absint( $advanced['meal_cat_lunch_end'] ?? 15 ),
						],
						'dinner' => [
							'label' => sanitize_text_field( $advanced['meal_cat_dinner_label'] ?? 'Dinner' ),
							'emoji' => sanitize_text_field( $advanced['meal_cat_dinner_emoji'] ?? '🌙' ),
							'start' => absint( $advanced['meal_cat_dinner_start'] ?? 17 ),
							'end'   => absint( $advanced['meal_cat_dinner_end'] ?? 22 ),
						],
						'snack' => [
							'label' => sanitize_text_field( $advanced['meal_cat_snack_label'] ?? 'Snack' ),
							'emoji' => sanitize_text_field( $advanced['meal_cat_snack_emoji'] ?? '🍎' ),
						],
					],
					'mealMaxTemplates' => absint( $advanced['meal_max_templates'] ?? 10 ),
				],
				'wl'          => $wl_active ? $wl_active['wl_data'] : null,
				'affiliates'  => \FCC\Admin\Affiliates::get_enabled_for_frontend(),
				'supplements' => \FCC\Admin\Supplements::get_frontend_data(),
				'promotions'  => self::get_promotions_for_frontend(),
				'preloadFood' => apply_filters( 'fcc_preload_food', null ),
			]
		);

		// White-label: inject enterprise custom CSS (Enterprise tier only).
		$wl_active = self::$wl_active;
		if ( $wl_active && ! empty( $wl_active['custom_css'] ) ) {
			wp_add_inline_style( 'fcc-public', $wl_active['custom_css'] );
		}

	}

	private static function get_promotions_for_frontend(): array {
		$pinned = Settings::get_section( 'pinned' );

		$trending = [];
		foreach ( ( $pinned['trending_foods'] ?? [] ) as $item ) {
			$food = Database::get_food( (int) ( $item['food_id'] ?? 0 ) );
			if ( $food ) {
				$trending[] = [
					'id'          => (int) $food['id'],
					'name'        => $food['name'],
					'energy_kcal' => (float) $food['energy_kcal'],
				];
			}
		}

		$badges = [];
		foreach ( ( $pinned['pinned_foods'] ?? [] ) as $rule ) {
			if ( ! empty( $rule['badge'] ) && ! empty( $rule['food_id'] ) ) {
				$badges[ (int) $rule['food_id'] ] = $rule['badge'];
			}
		}

		$banners = [];
		foreach ( ( $pinned['promo_banners'] ?? [] ) as $p ) {
			if ( ! empty( $p['food_id'] ) && ! empty( $p['message'] ) ) {
				$banners[ (int) $p['food_id'] ] = [
					'message'   => $p['message'],
					'link_text' => $p['link_text'] ?? '',
					'link_url'  => $p['link_url'] ?? '',
				];
			}
		}

		return [
			'trendingFoods' => $trending,
			'badges'        => (object) $badges,
			'banners'       => (object) $banners,
		];
	}
}
