<?php
/**
 * Options API wrapper.
 *
 * All plugin settings are stored in a single serialised option `fcc_settings`.
 * Use Settings::get('section.key') with dot notation, or Settings::get_section('section').
 *
 * @package FCC
 */

namespace FCC;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Settings {

	const OPTION_KEY = 'fcc_settings';

	// ---------------------------------------------------------------------------
	// Defaults
	// ---------------------------------------------------------------------------

	public static function defaults(): array {
		return [
			'general' => [
				'default_unit'         => 'metric',    // metric | imperial
				'default_category'     => 0,
				'decimal_places'       => 1,
				'default_quantity'     => 100,
				'max_quantity'         => 9999,
				'search_result_limit'  => 10,
				'popular_foods_count'  => 8,
				'search_debounce'      => 280,
				'show_nutrients'   => [
					'energy_kcal', 'energy_kj', 'protein_g', 'carbohydrate_g',
					'of_which_sugars_g', 'fat_g', 'of_which_saturates_g',
					'fibre_g', 'salt_g',
				],
				// UK Reference Intakes — admin-editable.
				'ri_energy_kcal'        => 2000,
				'ri_energy_kj'          => 8400,
				'ri_fat_g'              => 70,
				'ri_saturates_g'        => 20,
				'ri_carbohydrate_g'     => 260,
				'ri_sugars_g'           => 90,
				'ri_protein_g'          => 50,
				'ri_fibre_g'            => 30,
				'ri_salt_g'             => 6,
				// FSA traffic-light thresholds per 100g (solids) — admin-editable.
				'fsa_fat_low'           => 3,
				'fsa_fat_high'          => 17.5,
				'fsa_saturates_low'     => 1.5,
				'fsa_saturates_high'    => 5,
				'fsa_sugars_low'        => 5,
				'fsa_sugars_high'       => 22.5,
				'fsa_salt_low'          => 0.3,
				'fsa_salt_high'         => 1.5,
			],
			'features' => [
				'bmr_tdee'               => true,
				'daily_needs_comparison' => true,
				'fsa_traffic_lights'     => true,
				'ri_display'             => true,
				'macro_chart'            => true,
				'omega3_display'         => true,
				'caffeine_display'       => true,
				'meal_builder'           => true,
				'print_pdf'              => true,
				'share_link'             => true,
				'add_custom_food'        => false,
				'json_ld_schema'         => true,
				'compare_foods'          => true,
				'health_highlights'      => true,
				'popular_foods'          => true,
				'food_request_form'      => true,
				'powered_by_footer'      => true,
				'voice_search'           => true,
				'meal_categories'        => true,
				'meal_daily_goal'        => true,
				'meal_edit_quantity'      => true,
				'meal_print'             => true,
				'meal_share'             => true,
				'meal_copy'              => true,
				'meal_micronutrients'    => true,
				'meal_servings'          => true,
				'pwa_install'            => true,
			],
			'appearance' => [
				'primary_colour'    => '#075B5E',
				'accent_colour'     => '#FF3F33',
				'background_colour' => '#FFE6E1',
				'voice_icon'        => 'svg',
				'voice_colour'      => '#075B5E',
				'voice_size'        => 'medium',
				'dark_mode'         => false,
				'button_radius'     => 8,
				'font_family'       => 'system',
				'chart_protein_colour' => '#3b82f6',
				'chart_carbs_colour'   => '#f59e0b',
				'chart_fat_colour'     => '#ef4444',
				'chart_other_colour'   => '#94a3b8',
				'layout'               => 'standard',
				'results_animation'    => true,
				'card_style'           => 'elevated',
				'custom_css'        => '',
			],
			'labels' => [
				'calculator_title'      => __( 'Food Calorie Calculator', 'food-calorie-calculator' ),
				'search_placeholder'    => __( 'Search for a food…', 'food-calorie-calculator' ),
				'quantity_label'        => __( 'Quantity', 'food-calorie-calculator' ),
				'unit_label'            => __( 'Unit', 'food-calorie-calculator' ),
				'results_title'         => __( 'Nutrition Information', 'food-calorie-calculator' ),
				'per_label'             => __( 'Per', 'food-calorie-calculator' ),
				'ri_label'              => __( '% RI*', 'food-calorie-calculator' ),
				'ri_footnote'           => __( '*Reference Intake of an average adult (8400kJ / 2000kcal)', 'food-calorie-calculator' ),
				'meal_title'            => __( 'Your Meal', 'food-calorie-calculator' ),
				'add_to_meal_label'     => __( 'Add to Meal', 'food-calorie-calculator' ),
				'bmr_title'             => __( 'Daily Calorie Need', 'food-calorie-calculator' ),
				'omega3_title'          => __( 'Omega-3 Fatty Acids', 'food-calorie-calculator' ),
				'caffeine_title'        => __( 'Caffeine', 'food-calorie-calculator' ),
				'no_data_label'         => __( 'Data not available', 'food-calorie-calculator' ),
				'traffic_light_label'   => __( 'FSA Traffic Lights', 'food-calorie-calculator' ),
			],
			'advanced' => [
				'delete_data_on_uninstall' => false,
				'cache_enabled'            => true,
				'cache_duration'           => 3600,
				'search_min_chars'         => 2,
				'hl_high_protein'          => 15,
				'hl_low_fat'               => 3,
				'hl_low_calorie'           => 100,
				'hl_low_sugar'             => 5,
				'hl_high_fibre'            => 6,
				'hl_low_salt'              => 0.3,
				'hl_omega3_rich'           => 500,
				'hl_warn_high_salt'        => 1.5,
				'hl_warn_high_saturates'   => 5,
				'hl_warn_high_sugar'       => 22.5,
				'bmr_formula'              => 'mifflin',
				'calorie_goal_adjustment'  => 500,
				'meal_cat_breakfast_label'  => 'Breakfast',
				'meal_cat_breakfast_emoji'  => '🌅',
				'meal_cat_breakfast_start'  => 5,
				'meal_cat_breakfast_end'    => 11,
				'meal_cat_lunch_label'      => 'Lunch',
				'meal_cat_lunch_emoji'      => '🍽️',
				'meal_cat_lunch_start'      => 11,
				'meal_cat_lunch_end'        => 15,
				'meal_cat_dinner_label'     => 'Dinner',
				'meal_cat_dinner_emoji'     => '🌙',
				'meal_cat_dinner_start'     => 17,
				'meal_cat_dinner_end'       => 22,
				'meal_cat_snack_label'      => 'Snack',
				'meal_cat_snack_emoji'      => '🍎',
				'meal_max_templates'        => 10,
			],
			'pinned' => [
				'pinned_foods'   => [],
				'trending_foods' => [],
				'promo_banners'  => [],
			],
			'content' => [
				'hub_intro' => '',
			],
		];
	}

	// ---------------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------------

	/**
	 * Get a setting value by dot-notation key, e.g. "features.bmr_tdee".
	 * Returns the default if the key doesn't exist.
	 *
	 * @param string $key     Dot-notation path (e.g. 'general.decimal_places').
	 * @param mixed  $default Fallback value.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all    = self::get_all();
		$parts  = explode( '.', $key );
		$cursor = $all;

		foreach ( $parts as $part ) {
			if ( ! is_array( $cursor ) || ! array_key_exists( $part, $cursor ) ) {
				return $default;
			}
			$cursor = $cursor[ $part ];
		}

		return $cursor;
	}

	/**
	 * Get an entire section, merged with defaults.
	 */
	public static function get_section( string $section ): array {
		$defaults = self::defaults();
		$all      = self::get_all();

		return array_merge(
			$defaults[ $section ] ?? [],
			$all[ $section ]      ?? []
		);
	}

	/**
	 * Return the full settings array merged with defaults.
	 */
	public static function get_all(): array {
		$saved    = get_option( self::OPTION_KEY, [] );
		$defaults = self::defaults();

		if ( ! is_array( $saved ) ) {
			return $defaults;
		}

		// Deep merge: preserve saved values, fill missing with defaults.
		return self::deep_merge( $defaults, $saved );
	}

	// ---------------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------------

	/**
	 * Save the full settings array.
	 */
	public static function save( array $data ): bool {
		return update_option( self::OPTION_KEY, $data );
	}

	/**
	 * Update a single section's settings.
	 */
	public static function save_section( string $section, array $data ): bool {
		$all             = self::get_all();
		$all[ $section ] = $data;
		return self::save( $all );
	}

	/**
	 * Install defaults on first activation (does not overwrite existing values).
	 */
	public static function install_defaults(): void {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, self::defaults() );
		}
	}

	public static function migrate_pinned_to_section(): void {
		$all = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $all ) ) return;
		if ( ! empty( $all['general']['pinned_foods'] ) && empty( $all['pinned']['pinned_foods'] ) ) {
			$all['pinned'] = [ 'pinned_foods' => $all['general']['pinned_foods'] ];
			unset( $all['general']['pinned_foods'] );
			update_option( self::OPTION_KEY, $all );
		}
	}

	// ---------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------

	/**
	 * Recursively merge $defaults with $override.
	 * $override values win; keys missing from $override keep the $defaults value.
	 */
	private static function deep_merge( array $defaults, array $override ): array {
		$merged = $defaults;

		foreach ( $override as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::deep_merge( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}
}
