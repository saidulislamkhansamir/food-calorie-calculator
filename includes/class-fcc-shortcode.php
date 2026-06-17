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
		$loader->add_action( 'wp_footer', $this, 'maybe_enqueue_assets' );
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

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/frontend-calculator.php';
		$html = (string) ob_get_clean();

		// Powered-by footer — hidden via CSS when hidePoweredBy is active.
		$html .= '<div class="fcc-powered-by"><span>Powered by <a href="https://foodcaloriecalculator.co.uk" target="_blank" rel="noopener">Food Calorie Calculator</a></span></div>';

		return $html;
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

		$ver = FCC_VERSION;

		wp_enqueue_style(
			'fcc-public',
			FCC_PLUGIN_URL . 'assets/css/fcc-public.css',
			[],
			$ver
		);

		// Inline CSS custom properties from appearance settings.
		$custom_props = sprintf(
			':root{--fcc-primary:%s;--fcc-accent:%s;--fcc-bg:%s;--fcc-radius:%dpx;}',
			esc_attr( $appearance['primary_colour']    ?? '#005EB8' ),
			esc_attr( $appearance['accent_colour']     ?? '#41B883' ),
			esc_attr( $appearance['background_colour'] ?? '#F0F4F8' ),
			absint(   $appearance['button_radius']     ?? 8 )
		);
		if ( ! empty( $appearance['custom_css'] ) ) {
			$custom_props .= wp_strip_all_tags( $appearance['custom_css'] );
		}
		wp_add_inline_style( 'fcc-public', $custom_props );

		wp_enqueue_script(
			'fcc-chart',
			FCC_PLUGIN_URL . 'assets/js/fcc-chart.js',
			[],
			$ver,
			true
		);

		wp_enqueue_script(
			'fcc-calculator',
			FCC_PLUGIN_URL . 'assets/js/fcc-calculator.js',
			[ 'fcc-chart' ],
			$ver,
			true
		);

		// Pass settings + REST nonce to JS via wp_localize_script.
		wp_localize_script(
			'fcc-calculator',
			'fccData',
			[
				'restUrl'    => esc_url_raw( rest_url( 'fcc/v1' ) ),
				'restNonce'  => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'features'   => $features,
				'general'    => [
					'defaultUnit'    => $general['default_unit']    ?? 'metric',
					'decimalPlaces'  => absint( $general['decimal_places'] ?? 1 ),
					'showNutrients'  => $general['show_nutrients']  ?? [],
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
				'wl' => $wl_active ? $wl_active['wl_data'] : null,
			]
		);

		// White-label: inject enterprise custom CSS (Enterprise tier only).
		$wl_active = self::$wl_active;
		if ( $wl_active && ! empty( $wl_active['custom_css'] ) ) {
			wp_add_inline_style( 'fcc-public', $wl_active['custom_css'] );
		}

		// JSON-LD schema if enabled.
		if ( ! empty( $features['json_ld_schema'] ) ) {
			self::output_json_ld();
		}
	}

	/**
	 * Output NutritionInformation + FAQ JSON-LD schema.
	 */
	private static function output_json_ld(): void {
		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => 'FAQPage',
			'mainEntity' => [
				[
					'@type' => 'Question',
					'name'  => 'How do I use the food calorie calculator?',
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => 'Search for a food, enter your quantity in grams, ounces or a serving size, and instantly see calories, macros, Reference Intakes, and FSA traffic-light ratings.',
					],
				],
				[
					'@type' => 'Question',
					'name'  => 'What are UK Reference Intakes?',
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => 'UK Reference Intakes (RI) are guideline daily amounts for an average adult: 2000 kcal energy, 70g fat, 20g saturates, 90g sugars, 6g salt, and 50g protein.',
					],
				],
				[
					'@type' => 'Question',
					'name'  => 'What are FSA traffic-light labels?',
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => 'The UK Food Standards Agency traffic-light scheme colour-codes food for fat, saturated fat, sugars and salt as green (low), amber (medium) or red (high) per 100g, making it easy to compare healthiness at a glance.',
					],
				],
			],
		];

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
