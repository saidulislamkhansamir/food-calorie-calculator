<?php
/**
 * Seeds the food database with 110+ UK foods.
 *
 * All per-100g values sourced from:
 *   M&W = McCance & Widdowson's Composition of Foods, 8th Summary Ed. (2015)
 *   USDA = USDA FoodData Central (fdc.nal.usda.gov)
 *   EFSA = EFSA Comprehensive European Food Consumption Database / published data
 *
 * Omega-3 fields (omega3_total_mg, omega3_ala_mg, omega3_epa_mg, omega3_dha_mg):
 *   Populated ONLY for foods with verified fatty-acid profiles from USDA FDC.
 *   NULL everywhere else — never zero-filled.
 *
 * Caffeine (caffeine_mg):
 *   Populated ONLY for foods/beverages with published caffeine content.
 *   Values are per 100g or per 100ml as appropriate.
 *   NULL everywhere else.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Seed_Data {

	/**
	 * Run the seeder. Silently skips if foods already exist.
	 */
	public static function seed(): void {
		if ( Database::is_seeded() ) {
			return;
		}

		$categories = self::categories();
		$cat_ids    = [];

		foreach ( $categories as $cat ) {
			$id = Database::insert_category( $cat );
			if ( $id ) {
				$cat_ids[ $cat['slug'] ] = $id;
			}
		}

		foreach ( self::foods( $cat_ids ) as $food ) {
			Database::insert_food( $food );
		}
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.3 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v2(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 2 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return; // Category missing — bail to avoid inserting with category_id=0.
		}

		$new_foods = self::seafood_v2( $fs );
		foreach ( $new_foods as $food ) {
			// Skip if slug already in DB (INSERT IGNORE equivalent).
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 2 );
	}

	/**
	 * The 22 new seafood items added in seed v2.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v2( int $fs ): array {
		return [
			[ 'name' => 'Pollock (raw)',       'slug' => 'pollock-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.36, 'source_notes' => 'USDA FDC #175136. White fish; omega-3 left NULL.' ],
			[ 'name' => 'Halibut (raw)',        'slug' => 'halibut-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (159g)', 'grams' => 159] ], 'energy_kcal' => 91,  'energy_kj' => 380,  'protein_g' => 18.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.3, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.23, 'omega3_total_mg' => 363,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 91,  'omega3_dha_mg' => 247,  'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175137.' ],
			[ 'name' => 'Plaice (raw)',          'slug' => 'plaice-raw',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (130g)', 'grams' => 130] ], 'energy_kcal' => 79,  'energy_kj' => 331,  'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.29, 'source_notes' => 'M&W 8th ed. White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Dover Sole (raw)',      'slug' => 'dover-sole-raw',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Tilapia (raw)',         'slug' => 'tilapia-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (87g)', 'grams' => 87] ],   'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'source_notes' => 'USDA FDC #175175. White fish; omega-3 left NULL.' ],
			[ 'name' => 'Monkfish (raw)',        'slug' => 'monkfish-raw',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],'energy_kcal' => 76,  'energy_kj' => 318,  'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. Very lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Flounder (raw)',        'slug' => 'flounder-raw',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (127g)', 'grams' => 127] ], 'energy_kcal' => 86,  'energy_kj' => 360,  'protein_g' => 17.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.31, 'source_notes' => 'USDA FDC #175117 (flatfish/sole species).' ],
			[ 'name' => 'Whiting (raw)',         'slug' => 'whiting-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ], 'energy_kcal' => 81,  'energy_kj' => 339,  'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.28, 'source_notes' => 'M&W 8th ed. White fish; omega-3 left NULL.' ],
			[ 'name' => 'Skate Wing (raw)',      'slug' => 'skate-wing-raw',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 wing (200g)', 'grams' => 200] ],   'energy_kcal' => 73,  'energy_kj' => 305,  'protein_g' => 16.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.30, 'source_notes' => 'M&W 8th ed. Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Anchovy (raw)',         'slug' => 'anchovy-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '5 anchovies (20g)', 'grams' => 20] ],'energy_kcal' => 131, 'energy_kj' => 548,  'protein_g' => 20.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.8, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.30, 'omega3_total_mg' => 2113, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 763,  'omega3_dha_mg' => 911,  'source_notes' => 'USDA FDC #174183 (anchovy, European, raw).' ],
			[ 'name' => 'Sprats (raw)',          'slug' => 'sprats-raw',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],'energy_kcal' => 158, 'energy_kj' => 661,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 9.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 0.26, 'omega3_total_mg' => 1940, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 680,  'omega3_dha_mg' => 960,  'source_notes' => 'M&W 8th ed.; Omega-3 estimated from comparable small oily fish.' ],
			[ 'name' => 'Sea Bream (raw)',       'slug' => 'sea-bream-raw',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'source_notes' => 'M&W 8th ed. Semi-oily; omega-3 left NULL.' ],
			[ 'name' => 'Prawns (cooked)',       'slug' => 'prawns-cooked',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],'energy_kcal' => 99,  'energy_kj' => 414,  'protein_g' => 22.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.30, 'source_notes' => 'M&W 8th ed. Omega-3 negligible; left NULL.' ],
			[ 'name' => 'Lobster (cooked)',      'slug' => 'lobster-cooked',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '½ lobster (150g)', 'grams' => 150] ],'energy_kcal' => 103, 'energy_kj' => 431,  'protein_g' => 20.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.9, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 392,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 163,  'omega3_dha_mg' => 174,  'source_notes' => 'USDA FDC #175180 (lobster, northern, cooked).' ],
			[ 'name' => 'Crab (cooked)',         'slug' => 'crab-cooked',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],'energy_kcal' => 128, 'energy_kj' => 537,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.5, 'of_which_saturates_g' => 0.7, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 541,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 241,  'omega3_dha_mg' => 218,  'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #174215.' ],
			[ 'name' => 'Dressed Crab',          'slug' => 'dressed-crab',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 pot (43g)', 'grams' => 43], ['label' => '½ crab (100g)', 'grams' => 100] ], 'energy_kcal' => 133, 'energy_kj' => 558, 'protein_g' => 16.1, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 7.2, 'of_which_saturates_g' => 1.1, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'source_notes' => 'M&W 8th ed. (Crab, dressed).' ],
			[ 'name' => 'Mussels (cooked)',      'slug' => 'mussels-cooked',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100], ['label' => '1 bag (200g)', 'grams' => 200] ], 'energy_kcal' => 86, 'energy_kj' => 360, 'protein_g' => 11.9, 'carbohydrate_g' => 3.7, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.2, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 665, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 276, 'omega3_dha_mg' => 286, 'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175188.' ],
			[ 'name' => 'Oysters (raw)',         'slug' => 'oysters-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '6 oysters (84g)', 'grams' => 84] ],  'energy_kcal' => 81,  'energy_kj' => 339,  'protein_g' => 9.5,  'carbohydrate_g' => 4.7, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.3, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 740,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 340,  'omega3_dha_mg' => 214,  'source_notes' => 'USDA FDC #175191 (oyster, eastern, wild, raw).' ],
			[ 'name' => 'Clams (cooked)',        'slug' => 'clams-cooked',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],'energy_kcal' => 148, 'energy_kj' => 619,  'protein_g' => 25.5, 'carbohydrate_g' => 5.2, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95, 'omega3_total_mg' => 302,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 117,  'omega3_dha_mg' => 146,  'source_notes' => 'USDA FDC #174203 (clam, mixed species, cooked).' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.4 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v3(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 3 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v3( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 3 );
	}

	/**
	 * The 26 new seafood items added in seed v3.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v3( int $fs ): array {
		return [
			[ 'name' => 'Scallops (raw)',                    'slug' => 'scallops-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 scallops (90g)',     'grams' => 90]  ], 'energy_kcal' => 88,  'energy_kj' => 368,  'protein_g' => 17.0, 'carbohydrate_g' => 2.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.45, 'omega3_total_mg' => 398,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 167,  'omega3_dha_mg' => 193,  'source_notes' => 'USDA FDC #175187 (scallop, raw).' ],
			[ 'name' => 'Langoustines (cooked)',             'slug' => 'langoustines-cooked',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '5 langoustines (100g)', 'grams' => 100] ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 18.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'source_notes' => 'M&W 8th ed. (Dublin Bay prawns, boiled). Omega-3 left NULL.' ],
			[ 'name' => 'Cockles (cooked)',                  'slug' => 'cockles-cooked',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 53,  'energy_kj' => 222,  'protein_g' => 12.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'source_notes' => 'M&W 8th ed. Omega-3 left NULL.' ],
			[ 'name' => 'Whelks (cooked)',                   'slug' => 'whelks-cooked',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 72,  'energy_kj' => 301,  'protein_g' => 16.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.55, 'source_notes' => 'M&W 8th ed. Omega-3 left NULL.' ],
			[ 'name' => 'Squid / Calamari (raw)',            'slug' => 'squid-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.44, 'omega3_total_mg' => 496,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 148,  'omega3_dha_mg' => 256,  'source_notes' => 'USDA FDC #175186 (squid, raw).' ],
			[ 'name' => 'Calamari (Fried)',                  'slug' => 'calamari-fried',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 175, 'energy_kj' => 732,  'protein_g' => 14.0, 'carbohydrate_g' => 11.0,'of_which_sugars_g' => 0.5, 'fat_g' => 8.0,  'of_which_saturates_g' => 2.0, 'fibre_g' => 0.3, 'salt_g' => 0.60, 'source_notes' => 'M&W 8th ed. estimate (squid in batter, fried). Omega-3 left NULL.' ],
			[ 'name' => 'Octopus (cooked)',                  'slug' => 'octopus-cooked',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 164, 'energy_kj' => 686,  'protein_g' => 29.8, 'carbohydrate_g' => 4.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.1,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 306,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 106,  'omega3_dha_mg' => 145,  'source_notes' => 'USDA FDC #175184 (octopus, cooked).' ],
			[ 'name' => 'Tuna (Canned, in oil, drained)',    'slug' => 'tuna-canned-in-oil',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tin (112g)',          'grams' => 112] ], 'energy_kcal' => 189, 'energy_kj' => 791,  'protein_g' => 27.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 9.0,  'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 0.93, 'source_notes' => 'M&W 8th ed. (Tuna, canned in oil, drained). Omega-3 left NULL.' ],
			[ 'name' => 'Salmon (Canned)',                   'slug' => 'salmon-canned',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '½ tin (105g)', 'grams' => 105], ['label' => '1 tin (213g)', 'grams' => 213] ], 'energy_kcal' => 153, 'energy_kj' => 640, 'protein_g' => 20.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 8.0, 'of_which_saturates_g' => 1.9, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 1824, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 463, 'omega3_dha_mg' => 895, 'source_notes' => 'USDA FDC #175170 (salmon, sockeye, canned, drained).' ],
			[ 'name' => 'Sardines (Canned, in brine)',       'slug' => 'sardines-canned-brine',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tin drained (90g)',   'grams' => 90]  ], 'energy_kcal' => 172, 'energy_kj' => 719,  'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 8.8,  'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 380,  'omega3_dha_mg' => 430,  'source_notes' => 'M&W 8th ed. (Sardines, canned in brine, drained).' ],
			[ 'name' => 'Sardines (Canned, in tomato sauce)','slug' => 'sardines-tomato-sauce',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tin (120g)',          'grams' => 120] ], 'energy_kcal' => 163, 'energy_kj' => 682,  'protein_g' => 17.8, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 2.5, 'fat_g' => 8.5,  'of_which_saturates_g' => 2.0, 'fibre_g' => 0.4, 'salt_g' => 1.20, 'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 350,  'omega3_dha_mg' => 380,  'source_notes' => 'M&W 8th ed. (Sardines in tomato sauce).' ],
			[ 'name' => 'Anchovies (Canned, in oil)',        'slug' => 'anchovies-canned',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '5 fillets (20g)',       'grams' => 20]  ], 'energy_kcal' => 210, 'energy_kj' => 879,  'protein_g' => 28.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 9.7,  'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 9.20, 'omega3_total_mg' => 2113, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 760,  'omega3_dha_mg' => 900,  'source_notes' => 'USDA FDC #1905591 (anchovies, canned in oil, drained). Very high salt from brine preservation.' ],
			[ 'name' => 'Mackerel (Canned, in brine)',       'slug' => 'mackerel-canned',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tin (125g)',          'grams' => 125] ], 'energy_kcal' => 188, 'energy_kj' => 787,  'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 11.0, 'of_which_saturates_g' => 2.6, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 2400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 810,  'omega3_dha_mg' => 1270, 'source_notes' => 'M&W 8th ed. (Mackerel, canned in brine, drained).' ],
			[ 'name' => 'Smoked Salmon',                     'slug' => 'smoked-salmon',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 slices (50g)', 'grams' => 50], ['label' => '100g pack', 'grams' => 100] ], 'energy_kcal' => 142, 'energy_kj' => 594, 'protein_g' => 23.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.5, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 3.00, 'omega3_total_mg' => 1750, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 420, 'omega3_dha_mg' => 1030, 'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175168 (salmon, chinook, smoked).' ],
			[ 'name' => 'Smoked Haddock (raw)',              'slug' => 'smoked-haddock',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',       'grams' => 150] ], 'energy_kcal' => 101, 'energy_kj' => 423,  'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.40, 'source_notes' => 'M&W 8th ed. White fish; omega-3 left NULL.' ],
			[ 'name' => 'Smoked Mackerel',                   'slug' => 'smoked-mackerel',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (125g)',       'grams' => 125] ], 'energy_kcal' => 354, 'energy_kj' => 1482, 'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 30.9, 'of_which_saturates_g' => 5.9, 'fibre_g' => 0.0, 'salt_g' => 1.70, 'omega3_total_mg' => 3200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1000, 'omega3_dha_mg' => 1800, 'source_notes' => 'M&W 8th ed. Excellent omega-3 source; hot-smoking retains fatty-acid profile.' ],
			[ 'name' => 'Smoked Trout',                      'slug' => 'smoked-trout',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (100g)',       'grams' => 100] ], 'energy_kcal' => 165, 'energy_kj' => 690,  'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 7.8,  'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 2.00, 'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 270,  'omega3_dha_mg' => 690,  'source_notes' => 'M&W 8th ed. / USDA FDC #175154 equivalent (trout, rainbow, smoked).' ],
			[ 'name' => 'Rollmops (pickled herring)',         'slug' => 'rollmops',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 rollmop (60g)',       'grams' => 60]  ], 'energy_kcal' => 162, 'energy_kj' => 678,  'protein_g' => 13.5, 'carbohydrate_g' => 2.7, 'of_which_sugars_g' => 2.0, 'fat_g' => 10.7, 'of_which_saturates_g' => 2.4, 'fibre_g' => 0.0, 'salt_g' => 2.80, 'omega3_total_mg' => 1420, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 480,  'omega3_dha_mg' => 680,  'source_notes' => 'M&W 8th ed. (Herring, pickled/rollmops). Omega-3 retained in pickling.' ],
			[ 'name' => 'Gravlax (cured salmon)',             'slug' => 'gravlax',                 'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 slices (75g)',        'grams' => 75]  ], 'energy_kcal' => 146, 'energy_kj' => 611,  'protein_g' => 21.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.5, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.40, 'omega3_total_mg' => 1820, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 400,  'omega3_dha_mg' => 980,  'source_notes' => 'M&W / USDA FDC #175168 base (salmon, salt+sugar cured).' ],
			[ 'name' => 'Cod Roe (raw)',                      'slug' => 'cod-roe',                 'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 107, 'energy_kj' => 448,  'protein_g' => 21.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.1,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.60, 'omega3_total_mg' => 390,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 160,  'omega3_dha_mg' => 200,  'source_notes' => 'M&W 8th ed.' ],
			[ 'name' => 'Salmon Roe (Ikura)',                 'slug' => 'salmon-roe',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',         'grams' => 16]  ], 'energy_kcal' => 250, 'energy_kj' => 1046, 'protein_g' => 29.2, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 13.6, 'of_which_saturates_g' => 3.1, 'fibre_g' => 0.0, 'salt_g' => 1.90, 'omega3_total_mg' => 3480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1290, 'omega3_dha_mg' => 1740, 'source_notes' => 'USDA FDC #175172 (fish roe, mixed species, raw). Exceptionally rich omega-3 source.' ],
			[ 'name' => 'Caviar (black, sturgeon)',           'slug' => 'caviar-black',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',         'grams' => 16]  ], 'energy_kcal' => 264, 'energy_kj' => 1105, 'protein_g' => 24.6, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 17.9, 'of_which_saturates_g' => 4.1, 'fibre_g' => 0.0, 'salt_g' => 5.60, 'omega3_total_mg' => 6789, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 3101, 'omega3_dha_mg' => 2420, 'source_notes' => 'USDA FDC #174219 (Caviar, black and red, granular). One of the most omega-3-dense foods per 100g.' ],
			[ 'name' => 'Taramasalata',                      'slug' => 'taramasalata',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (30g)', 'grams' => 30], ['label' => '1 portion (60g)', 'grams' => 60] ], 'energy_kcal' => 446, 'energy_kj' => 1847, 'protein_g' => 4.1, 'carbohydrate_g' => 9.3, 'of_which_sugars_g' => 2.5, 'fat_g' => 43.5, 'of_which_saturates_g' => 5.6, 'fibre_g' => 0.3, 'salt_g' => 2.00, 'source_notes' => 'M&W 8th ed. (Taramasalata, retail). Omega-3 diluted by oil; left NULL.' ],
			[ 'name' => 'Battered Cod (fried)',              'slug' => 'battered-cod',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 piece (180g)',       'grams' => 180] ], 'energy_kcal' => 247, 'energy_kj' => 1034, 'protein_g' => 15.5, 'carbohydrate_g' => 18.0,'of_which_sugars_g' => 0.5, 'fat_g' => 12.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.6, 'salt_g' => 1.00, 'source_notes' => 'M&W 8th ed. (Cod in batter, fried in blended oil). Omega-3 left NULL.' ],
			[ 'name' => 'Fish Cakes (fried)',                'slug' => 'fish-cakes',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fish cake (70g)', 'grams' => 70], ['label' => '2 fish cakes (140g)', 'grams' => 140] ], 'energy_kcal' => 200, 'energy_kj' => 837, 'protein_g' => 10.0, 'carbohydrate_g' => 19.0, 'of_which_sugars_g' => 1.0, 'fat_g' => 9.5, 'of_which_saturates_g' => 1.2, 'fibre_g' => 1.2, 'salt_g' => 1.10, 'source_notes' => 'M&W 8th ed. Omega-3 left NULL.' ],
			[ 'name' => 'Fish Fingers (oven baked)',         'slug' => 'fish-fingers',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 finger (28g)', 'grams' => 28], ['label' => '4 fingers (112g)', 'grams' => 112] ], 'energy_kcal' => 200, 'energy_kj' => 837, 'protein_g' => 13.0, 'carbohydrate_g' => 18.5, 'of_which_sugars_g' => 0.5, 'fat_g' => 7.5, 'of_which_saturates_g' => 0.8, 'fibre_g' => 0.8, 'salt_g' => 0.90, 'source_notes' => 'M&W 8th ed. (Fish fingers, cod, oven baked). Omega-3 left NULL.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.5 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v4(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 4 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v4( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 4 );
	}

	/**
	 * The 12 new seafood items added in seed v4.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v4( int $fs ): array {
		return [
			[ 'name' => 'Breaded Scampi (fried)',    'slug' => 'breaded-scampi',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '5 pieces (125g)', 'grams' => 125] ],   'energy_kcal' => 237, 'energy_kj' => 992,  'protein_g' => 12.2, 'carbohydrate_g' => 19.8, 'of_which_sugars_g' => 0.5, 'fat_g' => 12.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.8, 'salt_g' => 1.20, 'source_notes' => 'M&W 8th ed. (Scampi, breaded/battered, fried). Omega-3 left NULL.' ],
			[ 'name' => 'Prawn Toast',               'slug' => 'prawn-toast',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 pieces (60g)', 'grams' => 60] ],       'energy_kcal' => 222, 'energy_kj' => 929,  'protein_g' => 11.0, 'carbohydrate_g' => 21.0, 'of_which_sugars_g' => 1.0, 'fat_g' => 10.5, 'of_which_saturates_g' => 1.5, 'fibre_g' => 1.0, 'salt_g' => 1.10, 'source_notes' => 'Estimated from typical UK takeaway/retail data. Omega-3 left NULL.' ],
			[ 'name' => 'Nori (dried seaweed)',       'slug' => 'nori-dried',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 sheet (3g)', 'grams' => 3], ['label' => '10g', 'grams' => 10] ], 'energy_kcal' => 35, 'energy_kj' => 146, 'protein_g' => 5.8, 'carbohydrate_g' => 5.1, 'of_which_sugars_g' => 0.5, 'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.3, 'salt_g' => 0.55, 'source_notes' => 'USDA FDC #168457 (Seaweed, laver, raw). Values per 100g.' ],
			[ 'name' => 'Wakame (raw seaweed)',       'slug' => 'wakame-raw',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],      'energy_kcal' => 45,  'energy_kj' => 188,  'protein_g' => 3.0,  'carbohydrate_g' => 9.1,  'of_which_sugars_g' => 0.5, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.5, 'salt_g' => 1.87, 'source_notes' => 'USDA FDC #168456 (Seaweed, wakame, raw). Natural salt from seawater.' ],
			[ 'name' => 'Arctic Char (raw)',          'slug' => 'arctic-char-raw',  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],      'energy_kcal' => 125, 'energy_kj' => 523,  'protein_g' => 19.9, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 5.0,  'of_which_saturates_g' => 1.1, 'fibre_g' => 0.0, 'salt_g' => 0.08, 'omega3_total_mg' => 600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 320, 'source_notes' => 'USDA FDC #175138 (Char, arctic, raw).' ],
			[ 'name' => 'Brill (raw)',                'slug' => 'brill-raw',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],      'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 18.0, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 2.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Barramundi (raw)',           'slug' => 'barramundi-raw',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],      'energy_kcal' => 97,  'energy_kj' => 406,  'protein_g' => 18.9, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 2.0,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 500, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 310, 'source_notes' => 'EFSA / published data (Lates calcarifer, raw). Moderate omega-3 for a lean fish.' ],
			[ 'name' => 'Carp (raw)',                 'slug' => 'carp-raw',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],      'energy_kcal' => 162, 'energy_kj' => 678,  'protein_g' => 18.0, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 9.5,  'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'omega3_total_mg' => 520, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 147, 'omega3_dha_mg' => 235, 'source_notes' => 'M&W 8th ed. Freshwater fish, moderate fat; Omega-3: USDA FDC #175088.' ],
			[ 'name' => 'Cod Cheeks (raw)',           'slug' => 'cod-cheeks-raw',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],     'energy_kcal' => 80,  'energy_kj' => 335,  'protein_g' => 18.3, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28, 'source_notes' => 'M&W 8th ed. Same nutritional profile as cod fillet. Omega-3 left NULL.' ],
			[ 'name' => 'Cod Tongue (raw)',           'slug' => 'cod-tongue-raw',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],     'energy_kcal' => 80,  'energy_kj' => 335,  'protein_g' => 18.3, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28, 'source_notes' => 'M&W 8th ed. Same nutritional profile as cod; distinct gelatinous texture. Omega-3 left NULL.' ],
			[ 'name' => 'Coley / Saithe (raw)',      'slug' => 'coley-raw',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],      'energy_kcal' => 82,  'energy_kj' => 343,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28, 'source_notes' => 'M&W 8th ed. (Coley/Saithe, raw). Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Cobia (raw)',                'slug' => 'cobia-raw',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],      'energy_kcal' => 103, 'energy_kj' => 431,  'protein_g' => 20.7, 'carbohydrate_g' => 0.0,  'of_which_sugars_g' => 0.0, 'fat_g' => 2.4,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'omega3_total_mg' => 490, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 260, 'source_notes' => 'USDA FDC / published data (Rachycentron canadum, raw). Omega-3 moderate for a semi-pelagic fish.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.6 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v5(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 5 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v5( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 5 );
	}

	/**
	 * The 21 new seafood items added in seed v5.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v5( int $fs ): array {
		return [
			[ 'name' => 'Dabs (raw)',                   'slug' => 'dabs-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fish (100g)',         'grams' => 100] ], 'energy_kcal' => 76,  'energy_kj' => 318,  'protein_g' => 16.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.26, 'source_notes' => 'M&W 8th ed. (Dab, raw). Small flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Dogfish / Rock Salmon (raw)',  'slug' => 'dogfish-raw',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 130, 'energy_kj' => 544,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.8,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. (Dogfish/rock salmon, raw). Semi-oily small shark; omega-3 left NULL.' ],
			[ 'name' => 'Dorade / Wild Pink Sea Bream (raw)', 'slug' => 'dorade-sea-bream-raw', 'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (140g)',   'grams' => 140] ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'source_notes' => 'M&W 8th ed. (Sea bream, raw). Wild dorade/pink sea bream has same profile. Omega-3 left NULL.' ],
			[ 'name' => 'Conger Eel (raw)',             'slug' => 'conger-eel-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',      'grams' => 150] ], 'energy_kcal' => 121, 'energy_kj' => 506,  'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.0,  'of_which_saturates_g' => 1.2, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 430, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 240, 'source_notes' => 'M&W 8th ed. Omega-3 estimated from USDA comparable eel data.' ],
			[ 'name' => 'Eels (raw)',                   'slug' => 'eels-raw',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 184, 'energy_kj' => 770,  'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 11.7, 'of_which_saturates_g' => 2.4, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'omega3_total_mg' => 741, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 149, 'omega3_dha_mg' => 392, 'source_notes' => 'USDA FDC #175189 (Eel, mixed species, raw). Rich in fat and omega-3.' ],
			[ 'name' => 'Cuttlefish (raw)',             'slug' => 'cuttlefish-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 79,  'energy_kj' => 331,  'protein_g' => 16.2, 'carbohydrate_g' => 0.8, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.65, 'omega3_total_mg' => 310, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 182, 'source_notes' => 'USDA FDC #175183 (Cuttlefish, mixed species, raw).' ],
			[ 'name' => 'Fish Bones (edible, soft)',    'slug' => 'fish-bones-edible',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (20g)',       'grams' => 20]  ], 'energy_kcal' => 184, 'energy_kj' => 770,  'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 10.0, 'of_which_saturates_g' => 2.5, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'source_notes' => 'Estimated from canned sardine/salmon bone composition (M&W 8th ed. / USDA). Very high calcium; omega-3 retained from host fish.' ],
			[ 'name' => 'Fish Mix (Smoked Blend)',      'slug' => 'fish-mix-smoked',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',      'grams' => 150] ], 'energy_kcal' => 180, 'energy_kj' => 753,  'protein_g' => 21.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 10.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 2.50, 'omega3_total_mg' => 800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 240, 'omega3_dha_mg' => 440, 'source_notes' => 'Estimated average from typical UK smoked fish blend (salmon, haddock, mackerel). Omega-3 varies by mix.' ],
			[ 'name' => 'Fish Mix (Classic)',           'slug' => 'fish-mix-classic',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',      'grams' => 150] ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.8,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'Estimated average from typical UK white fish blend (cod, haddock, pollock). Omega-3 left NULL.' ],
			[ 'name' => 'Fish Soup Mix (Bouillabaisse)','slug' => 'fish-soup-mix',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (200g)',      'grams' => 200] ], 'energy_kcal' => 85,  'energy_kj' => 356,  'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'source_notes' => 'Estimated from typical bouillabaisse-style raw fish/shellfish mix (white fish, shellfish, rockfish). Omega-3 left NULL.' ],
			[ 'name' => 'Turbot (raw)',                 'slug' => 'turbot-raw',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (200g)',       'grams' => 200] ], 'energy_kcal' => 95,  'energy_kj' => 397,  'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.5,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.23, 'source_notes' => 'M&W 8th ed. (Turbot, raw). Premium white flatfish; omega-3 left NULL.' ],
			[ 'name' => 'John Dory (raw)',              'slug' => 'john-dory-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',       'grams' => 150] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. (John Dory, raw). Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Red Gurnard (raw)',            'slug' => 'red-gurnard-raw',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',       'grams' => 120] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'source_notes' => 'M&W 8th ed. (Gurnard, raw). Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Lemon Sole (raw)',             'slug' => 'lemon-sole-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (130g)',       'grams' => 130] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'source_notes' => 'M&W 8th ed. (Lemon sole, raw). White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Sea Trout (raw)',              'slug' => 'sea-trout-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',       'grams' => 150] ], 'energy_kcal' => 135, 'energy_kj' => 565,  'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.0,  'of_which_saturates_g' => 1.2, 'fibre_g' => 0.0, 'salt_g' => 0.10, 'omega3_total_mg' => 720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 190, 'omega3_dha_mg' => 395, 'source_notes' => 'M&W 8th ed. (Sea/salmon trout, raw). Omega-3 from USDA FDC #175150 equivalent.' ],
			[ 'name' => 'Swordfish (raw)',              'slug' => 'swordfish-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',        'grams' => 150] ], 'energy_kcal' => 144, 'energy_kj' => 602,  'protein_g' => 19.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.7,  'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 0.37, 'omega3_total_mg' => 738, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 148, 'omega3_dha_mg' => 543, 'source_notes' => 'USDA FDC #175132 (Swordfish, raw).' ],
			[ 'name' => 'Brown Shrimp (cooked)',        'slug' => 'brown-shrimp-cooked',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (60g)',       'grams' => 60]  ], 'energy_kcal' => 98,  'energy_kj' => 410,  'protein_g' => 22.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'source_notes' => 'M&W 8th ed. (Brown shrimps, boiled). Very lean; omega-3 left NULL.' ],
			[ 'name' => 'Spider Crab (cooked)',         'slug' => 'spider-crab-cooked',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 111, 'energy_kj' => 464,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 3.5,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'omega3_total_mg' => 400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 160, 'omega3_dha_mg' => 190, 'source_notes' => 'M&W 8th ed. (Crab, white meat, cooked). Omega-3 estimated from USDA FDC #174215.' ],
			[ 'name' => 'Razor Clams (cooked)',         'slug' => 'razor-clams-cooked',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 razor clams (100g)', 'grams' => 100] ], 'energy_kcal' => 85,  'energy_kj' => 356,  'protein_g' => 14.5, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95, 'omega3_total_mg' => 250, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 85,  'omega3_dha_mg' => 130, 'source_notes' => 'Estimated from USDA clam data (FDC #174203). Omega-3 lower than blue/surf clams.' ],
			[ 'name' => 'Red Mullet (raw)',             'slug' => 'red-mullet-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',       'grams' => 120] ], 'energy_kcal' => 109, 'energy_kj' => 456,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.0,  'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. (Red mullet, raw). Semi-oily fish; omega-3 left NULL.' ],
			[ 'name' => 'Periwinkles (cooked)',         'slug' => 'periwinkles-cooked',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',      'grams' => 100] ], 'energy_kcal' => 64,  'energy_kj' => 268,  'protein_g' => 13.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'source_notes' => 'M&W 8th ed. (Periwinkles/winkles, boiled). Omega-3 left NULL.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.7 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v6(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 6 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v6( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 6 );
	}

	/**
	 * The 23 new seafood items added in seed v6.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v6( int $fs ): array {
		return [
			[ 'name' => 'Bloater',                         'slug' => 'bloater',                   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 bloater (150g)',      'grams' => 150] ], 'energy_kcal' => 189, 'energy_kj' => 791,  'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 13.0, 'of_which_saturates_g' => 3.0, 'fibre_g' => 0.0, 'salt_g' => 1.80, 'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 580, 'omega3_dha_mg' => 870, 'source_notes' => 'M&W 8th ed. (Herring, bloater — lightly cold-smoked whole herring). Omega-3 comparable to fresh herring.' ],
			[ 'name' => 'Hake (raw)',                       'slug' => 'hake-raw',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 19.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.2,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. (Hake, raw). Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Red Snapper (raw)',                'slug' => 'red-snapper-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 100, 'energy_kj' => 418,  'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.3,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 315, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 54,  'omega3_dha_mg' => 237, 'source_notes' => 'USDA FDC #175121 (Snapper, mixed species, raw).' ],
			[ 'name' => 'Gurnards Ungraded (raw)',          'slug' => 'gurnards-ungraded',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'source_notes' => 'M&W 8th ed. (Gurnard, raw). Mixed/ungraded species; same profile as red gurnard. Omega-3 left NULL.' ],
			[ 'name' => 'Grouper (raw)',                    'slug' => 'grouper-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.17, 'omega3_total_mg' => 243, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 47,  'omega3_dha_mg' => 170, 'source_notes' => 'USDA FDC #175108 (Grouper, mixed species, raw).' ],
			[ 'name' => 'Grey Mullet (raw)',                'slug' => 'grey-mullet-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 122, 'energy_kj' => 510,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.5,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'source_notes' => 'M&W 8th ed. (Grey mullet, raw). Semi-oily fish; omega-3 left NULL.' ],
			[ 'name' => 'Hamachi (raw)',                    'slug' => 'hamachi-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 146, 'energy_kj' => 611,  'protein_g' => 23.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.1,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 1720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 170, 'omega3_dha_mg' => 1380, 'source_notes' => 'USDA FDC #175118 (Yellowtail/Hamachi, Seriola spp., raw). Exceptional DHA content.' ],
			[ 'name' => 'Kebab (Salmon, Ling + Cod)',       'slug' => 'fish-kebab-salmon-ling-cod','category_id' => $fs, 'serving_sizes' => [ ['label' => '1 skewer (150g)',        'grams' => 150] ], 'energy_kcal' => 115, 'energy_kj' => 481,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.0,  'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 620, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 160, 'omega3_dha_mg' => 370, 'source_notes' => 'Estimated average of salmon (~⅓), ling (~⅓) and cod (~⅓). Omega-3 weighted from salmon component.' ],
			[ 'name' => 'Kingfish (raw)',                   'slug' => 'kingfish-raw',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 116, 'energy_kj' => 485,  'protein_g' => 20.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 3.2,  'of_which_saturates_g' => 0.7, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'omega3_total_mg' => 401, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 128, 'omega3_dha_mg' => 231, 'source_notes' => 'USDA FDC #175109 (Mackerel, king/kingfish, raw).' ],
			[ 'name' => 'Ling (raw)',                       'slug' => 'ling-raw',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 82,  'energy_kj' => 343,  'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. (Ling, raw). Lean white cod-family fish; omega-3 left NULL.' ],
			[ 'name' => 'Mahi Mahi (raw)',                  'slug' => 'mahi-mahi-raw',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 85,  'energy_kj' => 356,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.31, 'omega3_total_mg' => 189, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 42,  'omega3_dha_mg' => 133, 'source_notes' => 'USDA FDC #175110 (Dolphinfish/Mahi-mahi, raw).' ],
			[ 'name' => 'Marlin (raw)',                     'slug' => 'marlin-raw',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',         'grams' => 150] ], 'energy_kcal' => 122, 'energy_kj' => 510,  'protein_g' => 21.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.0,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 541, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 83,  'omega3_dha_mg' => 424, 'source_notes' => 'USDA FDC #175119 (Marlin, striped, raw).' ],
			[ 'name' => 'Megrim (raw)',                     'slug' => 'megrim-raw',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (130g)',        'grams' => 130] ], 'energy_kcal' => 79,  'energy_kj' => 331,  'protein_g' => 17.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'source_notes' => 'M&W 8th ed. (Megrim/whiff flatfish, raw). White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Monkfish Tail (raw)',              'slug' => 'monkfish-tail-raw',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tail portion (150g)', 'grams' => 150] ], 'energy_kcal' => 76,  'energy_kj' => 318,  'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. (Monkfish, raw). Tail is the primary edible cut; same profile as whole monkfish. Omega-3 left NULL.' ],
			[ 'name' => 'Monkfish Cheeks (raw)',            'slug' => 'monkfish-cheeks-raw',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 76,  'energy_kj' => 318,  'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. (Monkfish, raw). Cheeks share the same lean profile as the tail. Omega-3 left NULL.' ],
			[ 'name' => 'Monkfish Livers',                  'slug' => 'monkfish-livers',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',        'grams' => 50]  ], 'energy_kcal' => 150, 'energy_kj' => 628,  'protein_g' => 14.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 9.5,  'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 0.50, 'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 260, 'source_notes' => 'Estimated from published monkfish liver (ankimo) data. High fat relative to flesh; omega-3 estimated.' ],
			[ 'name' => 'Nile Perch (raw)',                 'slug' => 'nile-perch-raw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 91,  'energy_kj' => 381,  'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'source_notes' => 'USDA FDC #175113 (Perch, mixed species, raw) approximate for Lates niloticus. Omega-3 left NULL.' ],
			[ 'name' => 'Octopus (Mediterranean, cooked)', 'slug' => 'octopus-mediterranean',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 164, 'energy_kj' => 686,  'protein_g' => 29.8, 'carbohydrate_g' => 4.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.1,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 306, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 106, 'omega3_dha_mg' => 145, 'source_notes' => 'USDA FDC #175184 (Octopus, common, cooked). Mediterranean-sourced; same profile as standard cooked octopus.' ],
			[ 'name' => 'Octopus (U.K., raw)',             'slug' => 'octopus-uk-raw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 82,  'energy_kj' => 343,  'protein_g' => 15.3, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.55, 'omega3_total_mg' => 200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 65,  'omega3_dha_mg' => 115, 'source_notes' => 'USDA FDC #175184 (Octopus, raw equivalent). UK-caught specimens; values lower than cooked due to water content.' ],
			[ 'name' => 'Parrot Fish (raw)',                'slug' => 'parrot-fish-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (140g)',        'grams' => 140] ], 'energy_kcal' => 88,  'energy_kj' => 368,  'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'source_notes' => 'Estimated from USDA tropical reef fish data. Lean white fish; omega-3 left NULL.' ],
			[ 'name' => 'Pomfret (raw)',                    'slug' => 'pomfret-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fish (200g)',          'grams' => 200] ], 'energy_kcal' => 121, 'energy_kj' => 506,  'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.2,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'source_notes' => 'Published data (Pampus argenteus/silver pomfret). Semi-oily; omega-3 left NULL.' ],
			[ 'name' => 'Pike (raw)',                       'slug' => 'pike-raw',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 88,  'energy_kj' => 368,  'protein_g' => 19.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.1,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'source_notes' => 'M&W 8th ed. (Pike, raw). Freshwater predator; lean white fish. Omega-3 left NULL.' ],
			[ 'name' => 'Redfish (raw)',                    'slug' => 'redfish-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 94,  'energy_kj' => 393,  'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.8,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'omega3_total_mg' => 290, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 104, 'omega3_dha_mg' => 168, 'source_notes' => 'USDA FDC #175120 (Ocean perch/Atlantic redfish, Sebastes marinus, raw).' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.8 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v7(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 7 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v7( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 7 );
	}

	/**
	 * The 28 new seafood items added in seed v7.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v7( int $fs ): array {
		return [
			[ 'name' => 'Sand Soles Ungraded (raw)',         'slug' => 'sand-soles-ungraded',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 83,  'energy_kj' => 347,  'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'source_notes' => 'M&W 8th ed. (Sole, raw). Same profile as Dover sole; mixed-grade sand soles. Omega-3 left NULL.' ],
			[ 'name' => 'Sailfish (raw)',                    'slug' => 'sailfish-raw',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',         'grams' => 150] ], 'energy_kcal' => 110, 'energy_kj' => 460,  'protein_g' => 21.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.5,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 90,  'omega3_dha_mg' => 360, 'source_notes' => 'Estimated from published billfish data (Istiophorus spp.). Profile close to marlin/swordfish.' ],
			[ 'name' => 'Salmon Head (raw)',                 'slug' => 'salmon-head-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 salmon head (300g)',   'grams' => 300] ], 'energy_kcal' => 185, 'energy_kj' => 774,  'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 12.5, 'of_which_saturates_g' => 2.8, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 1850, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 420, 'omega3_dha_mg' => 1260, 'source_notes' => 'M&W 8th ed. / USDA FDC #175167 base. Head has higher fat than fillet; rich in collagen and omega-3.' ],
			[ 'name' => 'Wild Salmon (raw)',                 'slug' => 'wild-salmon-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 142, 'energy_kj' => 594,  'protein_g' => 19.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.3,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.09, 'omega3_total_mg' => 2018, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 411, 'omega3_dha_mg' => 1429, 'source_notes' => 'USDA FDC #175167 (Salmon, Atlantic, wild, raw). Leaner and higher omega-3 than farmed salmon.' ],
			[ 'name' => 'Sea Reared Trout (raw)',            'slug' => 'sea-reared-trout-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 150, 'energy_kj' => 628,  'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 7.5,  'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 0.10, 'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 280, 'omega3_dha_mg' => 720, 'source_notes' => 'M&W 8th ed. / published data (farmed rainbow trout, sea-reared). Higher fat than freshwater farmed trout.' ],
			[ 'name' => 'Sea Cucumber (raw)',                'slug' => 'sea-cucumber-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 38,  'energy_kj' => 159,  'protein_g' => 6.4,  'carbohydrate_g' => 0.3, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.2,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.50, 'source_notes' => 'Published data (Holothuria spp., raw). Very low calorie marine invertebrate; omega-3 left NULL.' ],
			[ 'name' => 'Shark Steaks (raw)',                'slug' => 'shark-steaks-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',         'grams' => 150] ], 'energy_kcal' => 130, 'energy_kj' => 544,  'protein_g' => 21.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.5,  'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'omega3_total_mg' => 737, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 218, 'omega3_dha_mg' => 485, 'source_notes' => 'USDA FDC #175126 (Shark, mixed species, raw).' ],
			[ 'name' => 'Tope Shark (raw)',                  'slug' => 'tope-shark-raw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 130, 'energy_kj' => 544,  'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.0,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.22, 'source_notes' => 'M&W 8th ed. (Dogfish/shark, raw). Tope (Galeorhinus galeus) shares similar profile to smaller sharks. Omega-3 left NULL.' ],
			[ 'name' => 'Skate Knobs / Eyes (raw)',          'slug' => 'skate-knobs-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 73,  'energy_kj' => 305,  'protein_g' => 16.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.30, 'source_notes' => 'M&W 8th ed. (Skate wing, raw). Knobs/eyes are small round cartilage-bordered discs cut from the wing; same profile. Omega-3 left NULL.' ],
			[ 'name' => 'Squid (Whole, raw)',                'slug' => 'squid-whole-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 whole squid (200g)',   'grams' => 200] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.44, 'omega3_total_mg' => 496, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 148, 'omega3_dha_mg' => 256, 'source_notes' => 'USDA FDC #175186 (Squid, mixed species, raw). Whole uncleaned squid; same per-100g nutritional profile as cleaned squid rings.' ],
			[ 'name' => 'Sturgeon (raw)',                    'slug' => 'sturgeon-raw',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 105, 'energy_kj' => 439,  'protein_g' => 16.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.0,  'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 597, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 198, 'omega3_dha_mg' => 367, 'source_notes' => 'USDA FDC #175131 (Sturgeon, mixed species, raw).' ],
			[ 'name' => 'Stone Bass / Meagre (raw)',         'slug' => 'stone-bass-raw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 88,  'energy_kj' => 368,  'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'source_notes' => 'Published data (Argyrosomus regius / meagre, raw). Large lean white sea fish; omega-3 left NULL.' ],
			[ 'name' => 'Tilapia (Black, raw)',              'slug' => 'tilapia-black-raw',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (87g)',         'grams' => 87]  ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'source_notes' => 'USDA FDC #175175 (Tilapia, raw). Black tilapia (O. niloticus) has same nutritional profile. Omega-3 left NULL.' ],
			[ 'name' => 'Tilapia (Red, raw)',                'slug' => 'tilapia-red-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (87g)',         'grams' => 87]  ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.7,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'source_notes' => 'USDA FDC #175175 (Tilapia, raw). Red tilapia hybrid has same nutritional profile. Omega-3 left NULL.' ],
			[ 'name' => 'Brown Trout (raw)',                 'slug' => 'brown-trout-raw',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 119, 'energy_kj' => 498,  'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.1,  'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.10, 'omega3_total_mg' => 580, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 316, 'source_notes' => 'M&W 8th ed. (Brown trout, raw). Omega-3 from USDA FDC #175150 equivalent.' ],
			[ 'name' => 'Tuna Toro (Fatty Tuna, raw)',       'slug' => 'tuna-toro-raw',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',        'grams' => 80]  ], 'energy_kcal' => 344, 'energy_kj' => 1440, 'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 27.4, 'of_which_saturates_g' => 6.6, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 3800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 900, 'omega3_dha_mg' => 2800, 'source_notes' => 'USDA FDC (Bluefin tuna, belly/toro). Fatty belly cut (otoro/chutoro); one of the richest omega-3 sources in sushi.' ],
			[ 'name' => 'Witch Sole / Torbay Sole (raw)',    'slug' => 'witch-sole-raw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',       'grams' => 120] ], 'energy_kcal' => 79,  'energy_kj' => 331,  'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24, 'source_notes' => 'M&W 8th ed. (Witch sole / Glyptocephalus cynoglossus, raw). White flatfish; omega-3 left NULL.' ],
			[ 'name' => 'Zander (raw)',                      'slug' => 'zander-raw',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',        'grams' => 150] ], 'energy_kcal' => 84,  'energy_kj' => 352,  'protein_g' => 19.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.14, 'source_notes' => 'Published data (Sander lucioperca, raw). Lean freshwater predator; omega-3 left NULL.' ],
			[ 'name' => 'Clams (Amandes, cooked)',           'slug' => 'clams-amandes',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 80,  'energy_kj' => 335,  'protein_g' => 13.5, 'carbohydrate_g' => 3.8, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'source_notes' => 'Estimated from USDA clam data (Glycymeris spp. / dog cockle). Profile similar to standard clams; omega-3 left NULL.' ],
			[ 'name' => 'Clams (Palourdes, cooked)',         'slug' => 'clams-palourdes',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 87,  'energy_kj' => 364,  'protein_g' => 14.8, 'carbohydrate_g' => 3.7, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.98, 'source_notes' => 'Estimated from USDA clam data (Ruditapes decussatus / grooved carpet shell). Omega-3 left NULL.' ],
			[ 'name' => 'Clams (Venus / Surf, cooked)',      'slug' => 'clams-venus-surf',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 85,  'energy_kj' => 356,  'protein_g' => 14.5, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95, 'source_notes' => 'Estimated from USDA clam data (Venerupis / surf clam spp.). Omega-3 left NULL.' ],
			[ 'name' => 'Crab Claws (cooked)',               'slug' => 'crab-claws-cooked',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '4 claws (100g)',         'grams' => 100] ], 'energy_kcal' => 100, 'energy_kj' => 418,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.5,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 360, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170, 'source_notes' => 'M&W 8th ed. (Crab, mixed meat, cooked). Claw meat is slightly richer than white meat. Omega-3 estimated.' ],
			[ 'name' => 'Crab Meat (Brown)',                 'slug' => 'crab-meat-brown',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 128, 'energy_kj' => 536,  'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.5,  'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'omega3_total_mg' => 720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 320, 'omega3_dha_mg' => 310, 'source_notes' => 'M&W 8th ed. (Crab, brown meat). Higher fat and stronger flavour than white meat; good omega-3 source.' ],
			[ 'name' => 'Crab Meat (White)',                 'slug' => 'crab-meat-white',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.0,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.95, 'omega3_total_mg' => 390, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 170, 'omega3_dha_mg' => 180, 'source_notes' => 'M&W 8th ed. (Crab, white meat). Leaner than brown meat; delicate flavour. Omega-3 estimated.' ],
			[ 'name' => 'Crab Meat (Claw)',                  'slug' => 'crab-meat-claw',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 110, 'energy_kj' => 460,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 3.0,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 190, 'omega3_dha_mg' => 210, 'source_notes' => 'M&W 8th ed. (Crab, claw meat). Darker and stronger-flavoured than body white meat. Omega-3 estimated.' ],
			[ 'name' => 'Crab Meat (Backfin)',               'slug' => 'crab-meat-backfin',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 87,  'energy_kj' => 364,  'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.1,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'omega3_total_mg' => 320, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 160, 'source_notes' => 'USDA FDC (Blue crab, lump/backfin meat, cooked). Very lean pick from the back section. Omega-3 estimated.' ],
			[ 'name' => 'Velvet Crab (cooked)',              'slug' => 'velvet-crab-cooked',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',       'grams' => 100] ], 'energy_kcal' => 95,  'energy_kj' => 397,  'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.5,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170, 'source_notes' => 'M&W 8th ed. (Crab, cooked). Velvet crab (Necora puber) is a small swimming crab; profile close to edible crab white meat. Omega-3 estimated.' ],
			[ 'name' => 'Cockles (in Shell, Live)',          'slug' => 'cockles-in-shell-live',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (90g deshelled)', 'grams' => 90] ], 'energy_kcal' => 36,  'energy_kj' => 151,  'protein_g' => 8.5,  'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'source_notes' => 'M&W 8th ed. (Cockles, raw). Values per 100g edible meat; ~250g in-shell yields ~90g meat. Omega-3 left NULL.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: add seafood items added in v1.3.9 to existing installations.
	// -------------------------------------------------------------------------

	public static function seed_v8(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 8 ) {
			return;
		}

		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) {
			return;
		}

		foreach ( self::seafood_v8( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) {
				Database::insert_food( $food );
			}
		}

		update_option( 'fcc_seed_version', 8 );
	}

	/**
	 * The 27 new seafood items added in seed v8.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v8( int $fs ): array {
		return [
			[ 'name' => 'Crayfish (English, cooked)',      'slug' => 'crayfish-english-cooked', 'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 72,  'energy_kj' => 301,  'protein_g' => 15.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.35, 'source_notes' => 'M&W 8th ed. equivalent (freshwater crayfish, boiled). White Clawed/signal crayfish; omega-3 left NULL.' ],
			[ 'name' => 'Crayfish (Import, cooked)',        'slug' => 'crayfish-import-cooked',  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 77,  'energy_kj' => 322,  'protein_g' => 16.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.1,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.38, 'source_notes' => 'USDA FDC #175178 (Crayfish, mixed species, farmed, cooked). Imported farmed variety; omega-3 left NULL.' ],
			[ 'name' => 'Lobster (Native, cooked)',         'slug' => 'lobster-native-cooked',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '½ lobster (150g)',    'grams' => 150] ], 'energy_kcal' => 103, 'energy_kj' => 431,  'protein_g' => 20.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.9,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 392, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 163, 'omega3_dha_mg' => 174, 'source_notes' => 'USDA FDC #175180 (Lobster, northern, cooked). Native European lobster (Homarus gammarus) has same profile.' ],
			[ 'name' => 'Spiny Lobster (cooked)',           'slug' => 'spiny-lobster-cooked',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tail (150g)',       'grams' => 150] ], 'energy_kcal' => 112, 'energy_kj' => 469,  'protein_g' => 20.6, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.85, 'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170, 'source_notes' => 'USDA FDC #175181 (Spiny lobster, mixed, cooked). Rock lobster/crawfish (Palinuridae); no claws, tail-only meat.' ],
			[ 'name' => 'Oysters (Native, raw)',            'slug' => 'oysters-native-raw',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '6 oysters (84g)',     'grams' => 84]  ], 'energy_kcal' => 81,  'energy_kj' => 339,  'protein_g' => 9.5,  'carbohydrate_g' => 4.7, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.3,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 740, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 340, 'omega3_dha_mg' => 214, 'source_notes' => 'M&W 8th ed. (Oysters, raw). Native flat oyster (Ostrea edulis) has same profile as Pacific/rock oyster.' ],
			[ 'name' => 'Sea Urchin (raw)',                 'slug' => 'sea-urchin-raw',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 103, 'energy_kj' => 431,  'protein_g' => 13.0, 'carbohydrate_g' => 3.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 3.5,  'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 196, 'omega3_dha_mg' => 224, 'source_notes' => 'USDA FDC #175193 (Sea urchin, raw). Roe/gonads only (uni); omega-3 from USDA equivalent.' ],
			[ 'name' => 'Sea Lettuce (raw)',                'slug' => 'sea-lettuce-raw',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 25,  'energy_kj' => 105,  'protein_g' => 2.6,  'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.5, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 2.0, 'salt_g' => 1.20, 'source_notes' => 'Published data (Ulva lactuca, fresh/raw). Bright green seaweed; omega-3 negligible in green algae, left NULL.' ],
			[ 'name' => 'Sea Spaghetti (raw)',              'slug' => 'sea-spaghetti-raw',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 27,  'energy_kj' => 113,  'protein_g' => 1.5,  'carbohydrate_g' => 4.5, 'of_which_sugars_g' => 0.3, 'fat_g' => 0.2,  'of_which_saturates_g' => 0.1, 'fibre_g' => 2.2, 'salt_g' => 1.50, 'source_notes' => 'Published data (Himanthalia elongata / thongweed, fresh). Brown seaweed; omega-3 left NULL.' ],
			[ 'name' => 'Dulse (raw)',                      'slug' => 'dulse-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (30g)',     'grams' => 30]  ], 'energy_kcal' => 43,  'energy_kj' => 180,  'protein_g' => 3.5,  'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 1.0, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 1.80, 'source_notes' => 'Published data (Palmaria palmata, fresh/raw). Red seaweed; high natural salt from seawater. Omega-3 left NULL.' ],
			[ 'name' => 'Kombu (raw)',                      'slug' => 'kombu-raw',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 strip (10g)',      'grams' => 10]  ], 'energy_kcal' => 43,  'energy_kj' => 180,  'protein_g' => 1.7,  'carbohydrate_g' => 9.6, 'of_which_sugars_g' => 0.6, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 1.3, 'salt_g' => 2.30, 'source_notes' => 'USDA FDC #168455 (Seaweed, kelp, raw). Kombu/kelp (Saccharina/Laminaria spp.); iodine-rich. Omega-3 left NULL.' ],
			[ 'name' => 'Scampi Tails (cooked)',            'slug' => 'scampi-tails-cooked',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',   'grams' => 100] ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 18.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'source_notes' => 'M&W 8th ed. (Dublin Bay prawns/scampi, boiled). Unbreaded langoustine tails; omega-3 left NULL.' ],
			[ 'name' => 'Scallops (King, Roe On, raw)',     'slug' => 'scallops-king-roe-on',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 scallops (120g)',  'grams' => 120] ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 17.5, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.45, 'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 185, 'omega3_dha_mg' => 220, 'source_notes' => 'USDA FDC #175187 base. Includes orange roe/coral which adds fat and omega-3 relative to roe-off scallops.' ],
			[ 'name' => 'Tiger Prawns (cooked)',            'slug' => 'tiger-prawns-cooked',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '6 prawns (100g)',    'grams' => 100] ], 'energy_kcal' => 105, 'energy_kj' => 439,  'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'source_notes' => 'USDA FDC #175177 (Shrimp, mixed species, cooked) / M&W. Tiger prawn (Penaeus monodon) profile. Omega-3 left NULL.' ],
			[ 'name' => 'Arbroath Smokies',                 'slug' => 'arbroath-smokies',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 smokie (180g)',    'grams' => 180] ], 'energy_kcal' => 101, 'energy_kj' => 423,  'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.40, 'source_notes' => 'M&W 8th ed. (Haddock, smoked). Arbroath Smokie is a PGI hot-smoked whole haddock on the bone; same nutritional profile. Omega-3 left NULL.' ],
			[ 'name' => 'Buckling',                         'slug' => 'buckling',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 buckling (150g)',  'grams' => 150] ], 'energy_kcal' => 250, 'energy_kj' => 1046, 'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 18.5, 'of_which_saturates_g' => 4.2, 'fibre_g' => 0.0, 'salt_g' => 2.50, 'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 1050, 'source_notes' => 'M&W 8th ed. (Herring, hot-smoked / buckling). Richer than bloater as hot-smoking renders more fat. Excellent omega-3 source.' ],
			[ 'name' => 'Smoked Cod Roe (Natural)',         'slug' => 'smoked-cod-roe',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 156, 'energy_kj' => 653,  'protein_g' => 22.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 7.5,  'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 2.80, 'omega3_total_mg' => 600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 240, 'omega3_dha_mg' => 305, 'source_notes' => 'M&W 8th ed. (Cod roe, smoked). Distinct from blended taramasalata; eaten sliced on bread.' ],
			[ 'name' => 'Smoked Cod',                       'slug' => 'smoked-cod',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',     'grams' => 150] ], 'energy_kcal' => 101, 'energy_kj' => 423,  'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.80, 'source_notes' => 'M&W 8th ed. (Cod, smoked). Lean white fish; high salt from brining. Omega-3 left NULL.' ],
			[ 'name' => 'Cured Salmon Trio',                'slug' => 'cured-salmon-trio',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',   'grams' => 100] ], 'energy_kcal' => 155, 'energy_kj' => 649,  'protein_g' => 23.0, 'carbohydrate_g' => 0.2, 'of_which_sugars_g' => 0.2, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.50, 'omega3_total_mg' => 1500, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 350, 'omega3_dha_mg' => 950, 'source_notes' => 'Estimated average of smoked, gravlax and hot-smoked salmon portions. Omega-3 weighted average.' ],
			[ 'name' => 'Gravadlax (Beetroot)',             'slug' => 'gravadlax-beetroot',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 slices (75g)',      'grams' => 75]  ], 'energy_kcal' => 150, 'energy_kj' => 628,  'protein_g' => 21.0, 'carbohydrate_g' => 2.5, 'of_which_sugars_g' => 2.0, 'fat_g' => 6.0,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.1, 'salt_g' => 2.40, 'omega3_total_mg' => 1750, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 400, 'omega3_dha_mg' => 1050, 'source_notes' => 'M&W 8th ed. / USDA FDC #175168 base (salmon, beetroot-cured). Small carbohydrate from beetroot marinade; omega-3 mirrors raw salmon.' ],
			[ 'name' => 'Hot Roast Salmon',                 'slug' => 'hot-roast-salmon',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (120g)',   'grams' => 120] ], 'energy_kcal' => 195, 'energy_kj' => 816,  'protein_g' => 24.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 11.0, 'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 380, 'omega3_dha_mg' => 980, 'source_notes' => 'M&W 8th ed. (Salmon, baked/roasted). Hot-roasted or hot-smoked; fat slightly reduced by cooking vs raw fillet.' ],
			[ 'name' => 'Kipper',                           'slug' => 'kipper',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 kipper (170g)',     'grams' => 170] ], 'energy_kcal' => 205, 'energy_kj' => 858,  'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 14.5, 'of_which_saturates_g' => 3.4, 'fibre_g' => 0.0, 'salt_g' => 3.00, 'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 575, 'omega3_dha_mg' => 870, 'source_notes' => 'M&W 8th ed. (Kipper, cold-smoked split herring). Very high salt from brining; excellent omega-3 source.' ],
			[ 'name' => 'Bottarga',                         'slug' => 'bottarga',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp shaved (15g)','grams' => 15]  ], 'energy_kcal' => 330, 'energy_kj' => 1381, 'protein_g' => 40.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 20.0, 'of_which_saturates_g' => 5.0, 'fibre_g' => 0.0, 'salt_g' => 5.80, 'omega3_total_mg' => 4000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1200, 'omega3_dha_mg' => 2400, 'source_notes' => 'Published data (dried/salt-cured grey mullet or tuna roe). Concentrated omega-3; extremely high salt from preservation.' ],
			[ 'name' => 'Avruga / Arenkha',                 'slug' => 'avruga-arenkha',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 180, 'energy_kj' => 753,  'protein_g' => 14.0, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.5, 'fat_g' => 12.5, 'of_which_saturates_g' => 2.8, 'fibre_g' => 0.0, 'salt_g' => 3.50, 'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 620, 'omega3_dha_mg' => 980, 'source_notes' => 'Estimated from herring roe composition. Avruga/Arenkha is a caviar substitute made from smoked herring roe.' ],
			[ 'name' => 'Crayfish Tails (in Brine)',        'slug' => 'crayfish-tails-brine',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 pot (100g)',        'grams' => 100] ], 'energy_kcal' => 72,  'energy_kj' => 301,  'protein_g' => 15.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.20, 'source_notes' => 'USDA FDC #175178 base. Brine-packed crayfish tails; higher salt than plain cooked. Omega-3 left NULL.' ],
			[ 'name' => 'Beluga Caviar',                    'slug' => 'beluga-caviar',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 264, 'energy_kj' => 1105, 'protein_g' => 26.9, 'carbohydrate_g' => 3.3, 'of_which_sugars_g' => 0.0, 'fat_g' => 17.0, 'of_which_saturates_g' => 3.8, 'fibre_g' => 0.0, 'salt_g' => 5.20, 'omega3_total_mg' => 6200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2800, 'omega3_dha_mg' => 2600, 'source_notes' => 'USDA FDC #174219 / published data (Huso huso roe). Largest sturgeon eggs; one of the most omega-3-dense foods.' ],
			[ 'name' => 'Oscietra Caviar',                  'slug' => 'oscietra-caviar',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 258, 'energy_kj' => 1080, 'protein_g' => 25.5, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 17.0, 'of_which_saturates_g' => 3.9, 'fibre_g' => 0.0, 'salt_g' => 5.40, 'omega3_total_mg' => 5800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2600, 'omega3_dha_mg' => 2400, 'source_notes' => 'Published data (Acipenser gueldenstaedtii roe). Nutty-flavoured medium-grade sturgeon caviar.' ],
			[ 'name' => 'Sevruga Caviar',                   'slug' => 'sevruga-caviar',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 260, 'energy_kj' => 1088, 'protein_g' => 26.5, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 17.5, 'of_which_saturates_g' => 4.0, 'fibre_g' => 0.0, 'salt_g' => 5.50, 'omega3_total_mg' => 6400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2900, 'omega3_dha_mg' => 2700, 'source_notes' => 'Published data (Acipenser stellatus roe). Smallest and most strongly flavoured of the classic caviars.' ],
		];
	}

	// -------------------------------------------------------------------------

	public static function seed_v9(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 9 ) { return; }
		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) { return; }
		foreach ( self::seafood_v9( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) { Database::insert_food( $food ); }
		}
		update_option( 'fcc_seed_version', 9 );
	}

	/**
	 * The 27 new seafood items added in seed v9.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v9( int $fs ): array {
		return [
			[ 'name' => 'Jellied Eels',                   'slug' => 'jellied-eels',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',    'grams' => 150] ], 'energy_kcal' => 98,  'energy_kj' => 410,  'protein_g' => 8.4,  'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.5, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'source_notes' => 'M&W 8th ed. (Eels, jellied). Traditional London dish; eel in lightly seasoned aspic jelly. Omega-3 left NULL.' ],
			[ 'name' => 'Lumpfish Roe (Black)',            'slug' => 'lumpfish-roe-black',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 163, 'energy_kj' => 682,  'protein_g' => 21.0, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.0,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 5.00, 'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 800,  'omega3_dha_mg' => 1200, 'source_notes' => 'Published data (Cyclopterus lumpus roe, black). Budget caviar substitute; dyed black. High salt from preservation.' ],
			[ 'name' => 'Lumpfish Roe (Red)',              'slug' => 'lumpfish-roe-red',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 163, 'energy_kj' => 682,  'protein_g' => 21.0, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.0,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 5.00, 'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 800,  'omega3_dha_mg' => 1200, 'source_notes' => 'Same base as black lumpfish roe; dyed red. Identical nutritional profile.' ],
			[ 'name' => 'Octopus Salad (in Oil)',          'slug' => 'octopus-salad-oil',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 152, 'energy_kj' => 636,  'protein_g' => 15.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5, 'fat_g' => 9.0,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'source_notes' => 'Estimated: cooked octopus pieces marinated in olive oil, vinegar, herbs. Omega-3 left NULL.' ],
			[ 'name' => 'Squid Ink',                       'slug' => 'squid-ink',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 sachet (4g)',        'grams' => 4]   ], 'energy_kcal' => 60,  'energy_kj' => 251,  'protein_g' => 10.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'source_notes' => 'Per 100g cephalopod ink. Used as flavouring in pasta/rice; typical sachet 4g provides ~2.4 kcal. Omega-3 left NULL.' ],
			[ 'name' => 'Samphire (Farmed)',               'slug' => 'samphire-farmed',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',     'grams' => 80]  ], 'energy_kcal' => 18,  'energy_kj' => 75,   'protein_g' => 1.5,  'carbohydrate_g' => 1.5, 'of_which_sugars_g' => 0.5, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 2.00, 'source_notes' => 'Published data (Salicornia europaea, farmed marsh samphire). Naturally salty halophyte; rinse before cooking. Omega-3 left NULL.' ],
			[ 'name' => 'Samphire (Wild)',                 'slug' => 'samphire-wild',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',     'grams' => 80]  ], 'energy_kcal' => 20,  'energy_kj' => 84,   'protein_g' => 1.5,  'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5, 'fat_g' => 0.4,  'of_which_saturates_g' => 0.1, 'fibre_g' => 1.8, 'salt_g' => 2.50, 'source_notes' => 'Published data (wild-foraged marsh or rock samphire). Slightly higher mineral content than farmed. Omega-3 left NULL.' ],
			[ 'name' => 'Sea Purslane',                    'slug' => 'sea-purslane',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 16,  'energy_kj' => 67,   'protein_g' => 1.2,  'carbohydrate_g' => 1.8, 'of_which_sugars_g' => 0.3, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 2.20, 'source_notes' => 'Published data (Halimione portulacoides). Coastal succulent; strongly saline from habitat. Omega-3 left NULL.' ],
			[ 'name' => 'Fish Soup (Perard)',              'slug' => 'fish-soup-perard',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 bowl (250g)',      'grams' => 250] ], 'energy_kcal' => 48,  'energy_kj' => 201,  'protein_g' => 3.2,  'carbohydrate_g' => 4.5, 'of_which_sugars_g' => 0.8, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.5, 'salt_g' => 1.20, 'source_notes' => 'Perard Soupe de Poissons (product label est.). Classic Provençal fish soup; typically served with rouille and croutons.' ],
			[ 'name' => 'Crab Soup (Perard)',              'slug' => 'crab-soup-perard',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 bowl (250g)',      'grams' => 250] ], 'energy_kcal' => 53,  'energy_kj' => 222,  'protein_g' => 2.8,  'carbohydrate_g' => 5.8, 'of_which_sugars_g' => 1.2, 'fat_g' => 1.7,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.3, 'salt_g' => 1.00, 'source_notes' => 'Perard Bisque de Crabe (product label est.). Creamy crab bisque; richer in carbohydrate than fish soup from cream and starch.' ],
			[ 'name' => 'Lobster Soup (Perard)',           'slug' => 'lobster-soup-perard',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 bowl (250g)',      'grams' => 250] ], 'energy_kcal' => 57,  'energy_kj' => 238,  'protein_g' => 3.2,  'carbohydrate_g' => 6.1, 'of_which_sugars_g' => 1.5, 'fat_g' => 2.2,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.3, 'salt_g' => 1.10, 'source_notes' => 'Perard Bisque de Homard (product label est.). Premium lobster bisque; slightly higher fat than crab version.' ],
			[ 'name' => 'Salmon Roe (Keta)',               'slug' => 'salmon-roe-keta',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 250, 'energy_kj' => 1046, 'protein_g' => 29.2, 'carbohydrate_g' => 1.9, 'of_which_sugars_g' => 0.5, 'fat_g' => 14.0, 'of_which_saturates_g' => 3.2, 'fibre_g' => 0.0, 'salt_g' => 1.60, 'omega3_total_mg' => 5600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2100, 'omega3_dha_mg' => 3300, 'source_notes' => 'USDA FDC #175194 (Salmon roe/ikura, raw). Chum/keta salmon roe; exceptional omega-3 source.' ],
			[ 'name' => 'Seafood Salad (in Oil)',          'slug' => 'seafood-salad-oil',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 140, 'energy_kj' => 586,  'protein_g' => 13.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5, 'fat_g' => 8.0,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 1.80, 'omega3_total_mg' => 400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 200, 'source_notes' => 'Estimated: mixed octopus, squid and mussels in sunflower/olive oil. Omega-3 from squid and mussel components.' ],
			[ 'name' => 'Sweet Cure Herring',              'slug' => 'sweet-cure-herring',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 fillets (80g)',     'grams' => 80]  ], 'energy_kcal' => 165, 'energy_kj' => 690,  'protein_g' => 13.5, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 6.5, 'fat_g' => 9.0,  'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 2.50, 'omega3_total_mg' => 1300, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 500,  'omega3_dha_mg' => 700,  'source_notes' => 'Estimated (sweet/sugar-cured marinated herring). High sugar from sweet brine marinade; retains good omega-3 despite processing.' ],
			[ 'name' => 'Tobiko (Wasabi/Green)',           'slug' => 'tobiko-wasabi-green',    'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 220, 'energy_kj' => 920,  'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0, 'fat_g' => 7.0,  'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 3.50, 'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 750,  'omega3_dha_mg' => 1100, 'source_notes' => 'Estimated from flying fish roe (Cypselurus agoo). Wasabi/green-dyed variety; same nutritional base as orange tobiko.' ],
			[ 'name' => 'Tobiko (Orange)',                 'slug' => 'tobiko-orange',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 225, 'energy_kj' => 942,  'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0, 'fat_g' => 7.5,  'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 3.50, 'omega3_total_mg' => 2100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 780,  'omega3_dha_mg' => 1150, 'source_notes' => 'Published data (flying fish roe, natural orange). Standard tobiko; used extensively in sushi garnish.' ],
			[ 'name' => 'Tobiko (Yellow)',                 'slug' => 'tobiko-yellow',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 220, 'energy_kj' => 920,  'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0, 'fat_g' => 7.0,  'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 3.50, 'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 750,  'omega3_dha_mg' => 1100, 'source_notes' => 'Yuzu-infused yellow tobiko (flying fish roe). Flavour differs; nutritional profile mirrors wasabi/green variety.' ],
			[ 'name' => 'Tuna (Chunks)',                   'slug' => 'tuna-chunks',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 can drained (145g)', 'grams' => 145] ], 'energy_kcal' => 109, 'energy_kj' => 456,  'protein_g' => 25.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100,  'omega3_dha_mg' => 210,  'source_notes' => 'M&W 8th ed. (Tuna, canned in brine, drained). Chunk-style skipjack; lower omega-3 than fresh tuna owing to processing.' ],
			[ 'name' => 'Terrine (Salmon & Cream)',        'slug' => 'terrine-salmon-cream',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',     'grams' => 80]  ], 'energy_kcal' => 200, 'energy_kj' => 837,  'protein_g' => 13.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 1.0, 'fat_g' => 16.0, 'of_which_saturates_g' => 6.0, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 300,  'omega3_dha_mg' => 700,  'source_notes' => 'Estimated (salmon terrine with cream cheese/crème fraîche). High sat fat from dairy; omega-3 from salmon component.' ],
			[ 'name' => 'King Prawns (Seawater)',          'slug' => 'king-prawns-seawater',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '6 prawns (100g)',     'grams' => 100] ], 'energy_kcal' => 99,  'energy_kj' => 414,  'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.9,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.80, 'source_notes' => 'M&W 8th ed. / USDA base (cooked king prawn). Sold live-preserved in seawater; higher salt than plain cooked. Omega-3 left NULL.' ],
			[ 'name' => 'Crevettes',                       'slug' => 'crevettes',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',    'grams' => 150] ], 'energy_kcal' => 98,  'energy_kj' => 410,  'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.90, 'source_notes' => 'M&W 8th ed. equivalent. Large pink/grey shrimps (Palaemon serratus or Crangon crangon); often sold whole, head-on.' ],
			[ 'name' => 'Cocktail Prawns',                 'slug' => 'cocktail-prawns',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 99,  'energy_kj' => 414,  'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'source_notes' => 'M&W 8th ed. (Prawns, cooked). Small peeled cooked prawns; as used in prawn cocktail. Omega-3 left NULL.' ],
			[ 'name' => 'Prawns (Cooked, Tail On)',        'slug' => 'prawns-cooked-tail-on',  'category_id' => $fs, 'serving_sizes' => [ ['label' => '4 prawns (80g)',      'grams' => 80]  ], 'energy_kcal' => 99,  'energy_kj' => 414,  'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'source_notes' => 'M&W 8th ed. (Prawns, cooked). Values per 100g edible meat; shell-on tail portion. Omega-3 left NULL.' ],
			[ 'name' => 'Prawns (Raw, Wild)',              'slug' => 'prawns-raw-wild',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 72,  'energy_kj' => 301,  'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'source_notes' => 'USDA FDC #175177 (Shrimp, raw). Wild-caught; lower sodium than cooked/processed. Omega-3 left NULL.' ],
			[ 'name' => 'Red Argentine Shrimps',           'slug' => 'red-argentine-shrimps',  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 82,  'energy_kj' => 343,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'omega3_total_mg' => 440, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130,  'omega3_dha_mg' => 270,  'source_notes' => 'Published data (Pleoticus muelleri, raw). Wild deep-water Patagonian shrimp; ultra-lean, slightly sweet flavour.' ],
			[ 'name' => 'Langoustine (Whole, Raw)',        'slug' => 'langoustine-whole-raw',  'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 whole (yields ~100g meat)', 'grams' => 100] ], 'energy_kcal' => 75,  'energy_kj' => 314,  'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'omega3_total_mg' => 300, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110,  'omega3_dha_mg' => 160,  'source_notes' => 'M&W equivalent (Nephrops norvegicus, raw). Per 100g edible meat; ~35% yield from whole shell-on weight.' ],
			[ 'name' => 'Soft Shell Crab',                 'slug' => 'soft-shell-crab',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 medium crab (80g)', 'grams' => 80]  ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 15.1, 'carbohydrate_g' => 3.3, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.8,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 310, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100,  'omega3_dha_mg' => 185,  'source_notes' => 'USDA FDC #174204 base (blue crab, raw). Soft shell = moulted crab eaten whole including shell; carbohydrate from chitin.' ],
		];
	}

	// -------------------------------------------------------------------------

	public static function seed_v10(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 10 ) { return; }
		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) { return; }
		foreach ( self::seafood_v10( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) { Database::insert_food( $food ); }
		}
		update_option( 'fcc_seed_version', 10 );
	}

	/**
	 * The 24 new seafood items added in seed v10.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v10( int $fs ): array {
		return [
			[ 'name' => 'King Crab (Clusters)',         'slug' => 'king-crab-clusters',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 cluster (200g)',    'grams' => 200] ], 'energy_kcal' => 97,  'energy_kj' => 406,  'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.40, 'omega3_total_mg' => 530,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 204, 'omega3_dha_mg' => 275, 'source_notes' => 'USDA FDC #174208 (Crustaceans, king crab, cooked). Clusters = legs + knuckles; meat extracted per 100g.' ],
			[ 'name' => 'Squid Tubes (raw)',             'slug' => 'squid-tubes',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tube (120g)',       'grams' => 120] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35, 'omega3_total_mg' => 440,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240, 'source_notes' => 'M&W 8th ed. / USDA (Squid, raw). Cleaned mantle only; tentacles excluded. Same composition as whole cleaned squid.' ],
			[ 'name' => 'Baby Squid (raw)',              'slug' => 'baby-squid',              'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35, 'omega3_total_mg' => 440,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240, 'source_notes' => 'M&W 8th ed. (Squid, raw). Small whole squid (<10cm mantle). Nutritional profile identical to larger squid.' ],
			[ 'name' => 'Baby Squid (Chipirones, raw)', 'slug' => 'baby-squid-chipirones',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.4,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35, 'omega3_total_mg' => 440,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240, 'source_notes' => 'Chipirones = Spanish name for very small squid (Sepiola atlantica / Alloteuthis spp.), raw. Same profile as baby squid.' ],
			[ 'name' => 'Baby Cuttlefish (raw)',         'slug' => 'baby-cuttlefish',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 79,  'energy_kj' => 331,  'protein_g' => 16.1, 'carbohydrate_g' => 0.8, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.7,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'omega3_total_mg' => 280,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 90,  'omega3_dha_mg' => 160, 'source_notes' => 'M&W 8th ed. (Cuttlefish, raw). Small Sepia officinalis; leaner than squid with less carbohydrate.' ],
			[ 'name' => 'Baby Octopus (raw)',            'slug' => 'baby-octopus',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 82,  'energy_kj' => 343,  'protein_g' => 14.9, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.50, 'omega3_total_mg' => 310,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 170, 'source_notes' => 'USDA FDC #175171 base (Octopus, raw). Moscardino/horned octopus (<15cm); same composition as larger octopus.' ],
			[ 'name' => 'Black Cod (Sablefish)',         'slug' => 'black-cod-sablefish',     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',     'grams' => 120] ], 'energy_kcal' => 250, 'energy_kj' => 1046, 'protein_g' => 13.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 21.5, 'of_which_saturates_g' => 5.2, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'omega3_total_mg' => 2400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 870, 'omega3_dha_mg' => 1500, 'source_notes' => 'USDA FDC #175172 (Sablefish/black cod, raw). One of the richest omega-3 fish; buttery texture owing to high fat content.' ],
			[ 'name' => 'Chilean Sea Bass',              'slug' => 'chilean-sea-bass',        'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',     'grams' => 150] ], 'energy_kcal' => 221, 'energy_kj' => 925,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 16.3, 'of_which_saturates_g' => 4.5, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 570, 'omega3_dha_mg' => 1160, 'source_notes' => 'USDA FDC (Dissostichus eleginoides / Patagonian toothfish, raw). Marketed as "Chilean sea bass"; very high fat, excellent omega-3.' ],
			[ 'name' => 'Pangasius / Basa (raw)',        'slug' => 'pangasius-basa',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',     'grams' => 120] ], 'energy_kcal' => 89,  'energy_kj' => 373,  'protein_g' => 12.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.2,  'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 90,   'omega3_ala_mg' => null, 'omega3_epa_mg' => 35,  'omega3_dha_mg' => 48,  'source_notes' => 'USDA FDC (Pangasius hypophthalmus, farmed). Mild white fish from Vietnamese aquaculture; very low omega-3 due to grain-based feed.' ],
			[ 'name' => 'Whitebait (Blanched)',          'slug' => 'whitebait-blanched',      'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 125, 'energy_kj' => 523,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.5,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.30, 'omega3_total_mg' => 1700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 900,  'source_notes' => 'M&W 8th ed. (Whitebait, whole, boiled). Immature sprats/herring eaten whole; good omega-3 from oily juveniles.' ],
			[ 'name' => 'Whitebait (Plain, raw)',        'slug' => 'whitebait-plain',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 125, 'energy_kj' => 523,  'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 5.5,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.25, 'omega3_total_mg' => 1700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 900,  'source_notes' => 'M&W 8th ed. base (Whitebait, raw). Uncoated uncooked juvenile sprats/herring; values match blanched but lower salt.' ],
			[ 'name' => 'Bahamas Lobster Tails',         'slug' => 'bahamas-lobster-tails',   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tail (150g)',       'grams' => 150] ], 'energy_kcal' => 112, 'energy_kj' => 469,  'protein_g' => 20.6, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.5,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.85, 'omega3_total_mg' => 350,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170, 'source_notes' => 'USDA FDC #175181 base (Panulirus argus, Caribbean/Bahamas spiny lobster, cooked). Same profile as spiny lobster tail.' ],
			[ 'name' => 'Tobiko (Black)',                'slug' => 'tobiko-black',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 225, 'energy_kj' => 942,  'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0, 'fat_g' => 7.5,  'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 3.50, 'omega3_total_mg' => 2100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 780, 'omega3_dha_mg' => 1150, 'source_notes' => 'Flying fish roe (Cypselurus agoo) dyed black with squid ink. Same nutritional base as orange tobiko.' ],
			[ 'name' => 'Masago (Orange)',               'slug' => 'masago-orange',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 195, 'energy_kj' => 816,  'protein_g' => 26.5, 'carbohydrate_g' => 8.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 3.20, 'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,  'source_notes' => 'Published data (capelin roe, Mallotus villosus, natural orange). Smaller and less firm than tobiko; slightly sweeter.' ],
			[ 'name' => 'Masago (Black)',                'slug' => 'masago-black',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 tbsp (16g)',        'grams' => 16]  ], 'energy_kcal' => 195, 'energy_kj' => 816,  'protein_g' => 26.5, 'carbohydrate_g' => 8.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 3.20, 'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,  'source_notes' => 'Capelin roe (Mallotus villosus) dyed black with squid ink. Nutritional profile identical to orange masago.' ],
			[ 'name' => 'Sushi Ebi (cooked prawn)',      'slug' => 'sushi-ebi',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 pieces (50g)',      'grams' => 50]  ], 'energy_kcal' => 99,  'energy_kj' => 414,  'protein_g' => 22.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.6,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.70, 'source_notes' => 'M&W 8th ed. (Prawns, cooked). Vannamei/tiger prawn, butterflied and poached for nigiri/sushi. Omega-3 left NULL.' ],
			[ 'name' => 'Snow Crab Meat',                'slug' => 'snow-crab-meat',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.10, 'omega3_total_mg' => 700,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 300, 'omega3_dha_mg' => 350,  'source_notes' => 'USDA FDC #174209 (Chionoecetes opilio, cooked). Cluster/leg meat; sweet flavour, lower fat than brown crab.' ],
			[ 'name' => 'Pouting / Bib (raw)',           'slug' => 'pouting-bib',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (120g)',     'grams' => 120] ], 'energy_kcal' => 75,  'energy_kj' => 314,  'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.5,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'source_notes' => 'M&W 8th ed. equivalent (Trisopterus luscus, raw). UK inshore gadoid similar to whiting; very lean, underutilised. Omega-3 left NULL.' ],
			[ 'name' => 'Black Sea Bream (raw)',         'slug' => 'black-sea-bream',         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (150g)',     'grams' => 150] ], 'energy_kcal' => 96,  'energy_kj' => 402,  'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.7,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.12, 'omega3_total_mg' => 640,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 420,  'source_notes' => 'Published data (Spondyliosoma cantharus, raw). UK inshore species; slightly fattier than gilt-head sea bream.' ],
			[ 'name' => 'Garfish (raw)',                 'slug' => 'garfish',                 'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 fillet (100g)',     'grams' => 100] ], 'energy_kcal' => 84,  'energy_kj' => 352,  'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.12, 'omega3_total_mg' => 420,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 280,  'source_notes' => 'Published data (Belone belone, raw). Flesh is naturally green-tinged due to biliverdin; safe and delicious. Lean spring fish.' ],
			[ 'name' => 'Smelt (raw)',                   'slug' => 'smelt',                   'category_id' => $fs, 'serving_sizes' => [ ['label' => '4 fish (100g)',       'grams' => 100] ], 'energy_kcal' => 97,  'energy_kj' => 406,  'protein_g' => 17.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.4,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18, 'omega3_total_mg' => 650,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 220, 'omega3_dha_mg' => 400,  'source_notes' => 'USDA FDC #175161 (Smelt, rainbow, raw). European smelt (Osmerus eperlanus) and rainbow smelt have near-identical profiles.' ],
			[ 'name' => 'Pilchards (Cornish, raw)',      'slug' => 'pilchards-cornish',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '2 pilchards (120g)',  'grams' => 120] ], 'energy_kcal' => 139, 'energy_kj' => 582,  'protein_g' => 20.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.5,  'of_which_saturates_g' => 1.7, 'fibre_g' => 0.0, 'salt_g' => 0.15, 'omega3_total_mg' => 1900, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 670, 'omega3_dha_mg' => 1080, 'source_notes' => 'M&W 8th ed. / Published data (Sardina pilchardus, raw). Adult sardine; Cornish-landed pilchards have same profile as Mediterranean sardine.' ],
			[ 'name' => 'Albacore Tuna (raw)',           'slug' => 'albacore-tuna',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',      'grams' => 150] ], 'energy_kcal' => 144, 'energy_kj' => 602,  'protein_g' => 23.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.9,  'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.12, 'omega3_total_mg' => 1280, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 300, 'omega3_dha_mg' => 890,  'source_notes' => 'USDA FDC #175167 (Thunnus alalunga, raw). White tuna; higher fat and omega-3 than skipjack or yellowfin.' ],
			[ 'name' => 'Yellowfin Tuna (raw)',          'slug' => 'yellowfin-tuna',          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',      'grams' => 150] ], 'energy_kcal' => 109, 'energy_kj' => 456,  'protein_g' => 23.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.0,  'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.12, 'omega3_total_mg' => 360,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 90,  'omega3_dha_mg' => 220,  'source_notes' => 'USDA FDC #175168 (Thunnus albacares, raw). Lean, mild-flavoured tuna; lower omega-3 than albacore or bluefin.' ],
		];
	}

	// -------------------------------------------------------------------------

	public static function seed_v11(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 11 ) { return; }
		global $wpdb;
		$cats_table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$fs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$cats_table} WHERE slug = %s LIMIT 1", 'fish-seafood' ) );
		if ( ! $fs ) { return; }
		foreach ( self::seafood_v11( $fs ) as $food ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . Database::foods_table() . " WHERE slug = %s LIMIT 1", $food['slug'] ) );
			if ( ! $exists ) { Database::insert_food( $food ); }
		}
		update_option( 'fcc_seed_version', 11 );
	}

	/**
	 * The 16 new seafood items added in seed v11.
	 * Note: Smoked Mackerel was already present; excluded here.
	 *
	 * @param int $fs  Fish & Seafood category ID from the live DB.
	 * @return array<int,array<string,mixed>>
	 */
	private static function seafood_v11( int $fs ): array {
		return [
			[ 'name' => 'Wahoo (raw)',                          'slug' => 'wahoo',                          'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 steak (150g)',      'grams' => 150] ], 'energy_kcal' => 109, 'energy_kj' => 456,  'protein_g' => 21.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.2,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.12, 'omega3_total_mg' => 490,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 320,  'source_notes' => 'USDA FDC equivalent (Acanthocybium solandri, raw). Firm-fleshed pelagic fish; popular in Atlantic and Pacific sport fishing.' ],
			[ 'name' => 'Scallops (Queen Meat, raw)',           'slug' => 'scallops-queen-meat',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 86,  'energy_kj' => 360,  'protein_g' => 17.0, 'carbohydrate_g' => 2.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 0.8,  'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40, 'omega3_total_mg' => 370,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 200,  'source_notes' => 'Published data (Aequipecten opercularis, raw). Smaller and sweeter than king scallops; no roe in commercial supply.' ],
			[ 'name' => 'Mussel Meat (Shucked, raw)',           'slug' => 'mussel-meat-shucked',            'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 74,  'energy_kj' => 310,  'protein_g' => 11.9, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.8,  'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.60, 'omega3_total_mg' => 700,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 260, 'omega3_dha_mg' => 400,  'source_notes' => 'M&W 8th ed. (Mussels, raw). Shucked/prepared weight (no shell); same composition as in-shell cooked but raw.' ],
			[ 'name' => 'Goose Barnacles (Percebes)',           'slug' => 'goose-barnacles-percebes',       'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 120, 'energy_kj' => 502,  'protein_g' => 20.0, 'carbohydrate_g' => 2.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 3.0,  'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'omega3_total_mg' => 550,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 200, 'omega3_dha_mg' => 300,  'source_notes' => 'Published data (Pollicipes pollicipes, cooked). Values per 100g edible peduncle meat; shell/plate discarded. Atlantic/Iberian delicacy.' ],
			[ 'name' => 'Mantis Shrimp (cooked)',               'slug' => 'mantis-shrimp',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (100g)',    'grams' => 100] ], 'energy_kcal' => 90,  'energy_kj' => 377,  'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.2,  'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 430,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 250,  'source_notes' => 'Published data (Squilla mantis / Stomatopoda, cooked). Mediterranean stomatopod; values per 100g edible tail meat.' ],
			[ 'name' => 'Smoked Eel',                          'slug' => 'smoked-eel',                     'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',     'grams' => 80]  ], 'energy_kcal' => 290, 'energy_kj' => 1213, 'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 22.0, 'of_which_saturates_g' => 4.5, 'fibre_g' => 0.0, 'salt_g' => 2.20, 'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 850,  'source_notes' => 'M&W 8th ed. (Eel, smoked). Hot-smoked European eel; very rich; high salt from brining before smoking.' ],
			[ 'name' => 'Smoked Sprats',                       'slug' => 'smoked-sprats',                  'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (50g)',     'grams' => 50]  ], 'energy_kcal' => 285, 'energy_kj' => 1193, 'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 23.0, 'of_which_saturates_g' => 5.4, 'fibre_g' => 0.0, 'salt_g' => 2.80, 'omega3_total_mg' => 2700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1100, 'omega3_dha_mg' => 1400, 'source_notes' => 'M&W 8th ed. (Sprats, smoked). Very high fat from both the fish and smoking process; exceptional omega-3 density.' ],
			[ 'name' => 'Smoked Halibut',                      'slug' => 'smoked-halibut',                 'category_id' => $fs, 'serving_sizes' => [ ['label' => '3 slices (60g)',      'grams' => 60]  ], 'energy_kcal' => 136, 'energy_kj' => 569,  'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 6.2,  'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.50, 'omega3_total_mg' => 980,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 350, 'omega3_dha_mg' => 580,  'source_notes' => 'Published data (Hippoglossus hippoglossus, cold-smoked). Milder and fattier than smoked haddock; sliced and served cold.' ],
			[ 'name' => 'Herring Roe (Soft, raw)',             'slug' => 'herring-roe-soft',               'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (80g)',     'grams' => 80]  ], 'energy_kcal' => 92,  'energy_kj' => 385,  'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.5,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.20, 'omega3_total_mg' => 680,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 250, 'omega3_dha_mg' => 380,  'source_notes' => 'M&W 8th ed. (Herring, soft roe/milt, raw). Sperm sac (milt); distinct from hard roe. Often pan-fried on toast.' ],
			[ 'name' => 'Fish Pie Mix (raw)',                   'slug' => 'fish-pie-mix',                   'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (150g)',    'grams' => 150] ], 'energy_kcal' => 97,  'energy_kj' => 406,  'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 2.8,  'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80, 'omega3_total_mg' => 630,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 200, 'omega3_dha_mg' => 380,  'source_notes' => 'Estimated average of salmon, cod and smoked haddock (typical UK retail mix). Omega-3 weighted average across components.' ],
			[ 'name' => 'Prawn Cocktail',                       'slug' => 'prawn-cocktail',                 'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 starter (150g)',   'grams' => 150] ], 'energy_kcal' => 140, 'energy_kj' => 586,  'protein_g' => 11.0, 'carbohydrate_g' => 6.0, 'of_which_sugars_g' => 5.5, 'fat_g' => 8.0,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.3, 'salt_g' => 1.20, 'source_notes' => 'Estimated (cooked prawns + Marie Rose / thousand island sauce on lettuce). Fat from mayo base; sugars from ketchup component. Omega-3 left NULL.' ],
			[ 'name' => 'Dressed Lobster',                      'slug' => 'dressed-lobster',                'category_id' => $fs, 'serving_sizes' => [ ['label' => '½ lobster (200g)',   'grams' => 200] ], 'energy_kcal' => 160, 'energy_kj' => 670,  'protein_g' => 17.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.5, 'fat_g' => 10.0, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 1.50, 'omega3_total_mg' => 280,  'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 130,  'source_notes' => 'Estimated: cooked lobster served in shell with mayonnaise dressing. Fat and salt elevated by mayo component.' ],
			[ 'name' => 'Marinated Anchovies (Boquerones)',     'slug' => 'marinated-anchovies-boquerones', 'category_id' => $fs, 'serving_sizes' => [ ['label' => '6 fillets (50g)',    'grams' => 50]  ], 'energy_kcal' => 110, 'energy_kj' => 460,  'protein_g' => 17.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0, 'fat_g' => 4.0,  'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 1.00, 'omega3_total_mg' => 1400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 550, 'omega3_dha_mg' => 750,  'source_notes' => 'Published data (Engraulis encrasicolus, vinegar/oil cured). White anchovies; much lower salt than salt-packed; mild, firm texture.' ],
			[ 'name' => 'Mussels (Smoked, in Oil)',             'slug' => 'mussels-smoked-oil',             'category_id' => $fs, 'serving_sizes' => [ ['label' => '½ tin (60g)',        'grams' => 60]  ], 'energy_kcal' => 200, 'energy_kj' => 837,  'protein_g' => 20.0, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.0, 'fat_g' => 11.5, 'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 1.80, 'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 450, 'omega3_dha_mg' => 680,  'source_notes' => 'Published data (Mytilus edulis, hot-smoked, in sunflower oil). Canned; fat elevated by oil packing.' ],
			[ 'name' => 'Carrageen (Irish Moss, fresh)',        'slug' => 'carrageen-irish-moss',           'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion (30g)',     'grams' => 30]  ], 'energy_kcal' => 30,  'energy_kj' => 126,  'protein_g' => 1.5,  'carbohydrate_g' => 5.0, 'of_which_sugars_g' => 0.5, 'fat_g' => 0.3,  'of_which_saturates_g' => 0.1, 'fibre_g' => 2.0, 'salt_g' => 1.80, 'source_notes' => 'Published data (Chondrus crispus, fresh/reconstituted). Red seaweed used as natural thickener in puddings and jellies. Omega-3 left NULL.' ],
			[ 'name' => 'Hijiki (dried)',                       'slug' => 'hijiki',                         'category_id' => $fs, 'serving_sizes' => [ ['label' => '1 portion dry (10g)', 'grams' => 10]  ], 'energy_kcal' => 150, 'energy_kj' => 628,  'protein_g' => 10.6, 'carbohydrate_g' => 14.4, 'of_which_sugars_g' => 0.0, 'fat_g' => 1.3, 'of_which_saturates_g' => 0.3, 'fibre_g' => 43.3, 'salt_g' => 4.60, 'source_notes' => 'USDA FDC #168456 (Seaweed, hijiki, raw). Values per 100g dry. Note: FSA advises against consumption due to high inorganic arsenic content. Omega-3 left NULL.' ],
		];
	}

	// -------------------------------------------------------------------------
	// Migration: create fcc_missed_searches table (v1.7.0).
	// -------------------------------------------------------------------------

	public static function seed_v15(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 15 ) { return; }
		Database::create_missed_searches_table();
		update_option( 'fcc_seed_version', 15 );
	}

	// -------------------------------------------------------------------------
	// Migration: create fcc_search_log table, backfill from missed_searches (v1.8.0).
	// -------------------------------------------------------------------------

	// -------------------------------------------------------------------------
	// Migration: add sponsorship columns to fcc_foods + create fcc_sponsor_clicks (v1.9.0).
	// -------------------------------------------------------------------------

	public static function seed_v17(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 17 ) { return; }
		global $wpdb;
		$table = Database::foods_table();

		$existing = $wpdb->get_col( "DESCRIBE {$table}", 0 ); // phpcs:ignore
		$cols_to_add = [
			'is_sponsored'       => "ALTER TABLE {$table} ADD COLUMN is_sponsored tinyint(1) NOT NULL DEFAULT 0 AFTER source_notes",
			'sponsor_active'     => "ALTER TABLE {$table} ADD COLUMN sponsor_active tinyint(1) NOT NULL DEFAULT 0 AFTER is_sponsored",
			'sponsor_name'       => "ALTER TABLE {$table} ADD COLUMN sponsor_name varchar(200) DEFAULT NULL AFTER sponsor_active",
			'sponsor_logo_id'    => "ALTER TABLE {$table} ADD COLUMN sponsor_logo_id bigint(20) DEFAULT NULL AFTER sponsor_name",
			'sponsor_url'        => "ALTER TABLE {$table} ADD COLUMN sponsor_url varchar(500) DEFAULT NULL AFTER sponsor_logo_id",
			'sponsor_expires_at' => "ALTER TABLE {$table} ADD COLUMN sponsor_expires_at datetime DEFAULT NULL AFTER sponsor_url",
		];
		foreach ( $cols_to_add as $col => $sql ) {
			if ( ! in_array( $col, $existing, true ) ) {
				$wpdb->query( $sql ); // phpcs:ignore
			}
		}
		// Add index if missing.
		$indexes = $wpdb->get_col( "SHOW INDEX FROM {$table} WHERE Key_name = 'is_sponsored'", 2 ); // phpcs:ignore
		if ( empty( $indexes ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD KEY is_sponsored (is_sponsored)" ); // phpcs:ignore
		}

		Database::create_sponsor_clicks_table();
		update_option( 'fcc_seed_version', 17 );
	}

	public static function seed_v16(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 16 ) { return; }
		Database::create_search_log_table();
		// Backfill existing zero-result queries as historical has_results=0 rows.
		global $wpdb;
		$ms = Database::missed_searches_table();
		$sl = Database::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"INSERT INTO {$sl} (query, has_results, log_date, count)
			 SELECT query, 0, DATE(created_at), search_count FROM {$ms}
			 ON DUPLICATE KEY UPDATE count = count + VALUES(count)"
		);
		update_option( 'fcc_seed_version', 16 );
	}

	// -------------------------------------------------------------------------
	// Migration: add marketing_optin column to fcc_food_requests (v1.5.9).
	// -------------------------------------------------------------------------

	public static function seed_v14(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 14 ) { return; }
		global $wpdb;
		$table = Database::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'marketing_optin'" );
		if ( ! $col ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN marketing_optin tinyint(1) NOT NULL DEFAULT 1 AFTER requester_email" );
		}
		update_option( 'fcc_seed_version', 14 );
	}

	// -------------------------------------------------------------------------
	// Migration: create fcc_food_requests table for existing installs (v1.5.8).
	// -------------------------------------------------------------------------

	public static function seed_v13(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 13 ) { return; }
		Database::create_requests_table();
		update_option( 'fcc_seed_version', 13 );
	}

	// -------------------------------------------------------------------------
	// Migration: add search_count column to fcc_foods (v1.3.26).
	// -------------------------------------------------------------------------

	public static function seed_v12(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 12 ) { return; }
		global $wpdb;
		$foods_table = Database::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$foods_table} LIKE 'search_count'" );
		if ( ! $col ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$foods_table} ADD COLUMN search_count INT UNSIGNED NOT NULL DEFAULT 0" );
		}
		update_option( 'fcc_seed_version', 12 );
	}

	// -------------------------------------------------------------------------
	// Categories.
	// -------------------------------------------------------------------------

	/** @return array<int,array<string,mixed>> */
	private static function categories(): array {
		return [
			[ 'name' => 'Fruit & Vegetables',      'slug' => 'fruit-veg',      'display_order' => 1 ],
			[ 'name' => 'Meat & Poultry',           'slug' => 'meat-poultry',   'display_order' => 2 ],
			[ 'name' => 'Fish & Seafood',           'slug' => 'fish-seafood',   'display_order' => 3 ],
			[ 'name' => 'Dairy & Eggs',             'slug' => 'dairy-eggs',     'display_order' => 4 ],
			[ 'name' => 'Bread & Cereals',          'slug' => 'bread-cereals',  'display_order' => 5 ],
			[ 'name' => 'Nuts & Seeds',             'slug' => 'nuts-seeds',     'display_order' => 6 ],
			[ 'name' => 'Fats & Oils',              'slug' => 'fats-oils',      'display_order' => 7 ],
			[ 'name' => 'Drinks',                   'slug' => 'drinks',         'display_order' => 8 ],
			[ 'name' => 'Snacks & Confectionery',   'slug' => 'snacks-confectionery', 'display_order' => 9 ],
			[ 'name' => 'Takeaway & Ready Meals',   'slug' => 'takeaway',       'display_order' => 10 ],
			[ 'name' => 'Legumes & Pulses',         'slug' => 'legumes-pulses', 'display_order' => 11 ],
			[ 'name' => 'Condiments & Sauces',      'slug' => 'condiments',     'display_order' => 12 ],
		];
	}

	// -------------------------------------------------------------------------
	// Foods.
	// -------------------------------------------------------------------------

	/**
	 * @param array<string,int> $cat_ids  Map of category slug => DB id.
	 * @return array<int,array<string,mixed>>
	 */
	private static function foods( array $cat_ids ): array {
		$fv  = $cat_ids['fruit-veg']      ?? 0;
		$mp  = $cat_ids['meat-poultry']   ?? 0;
		$fs  = $cat_ids['fish-seafood']   ?? 0;
		$de  = $cat_ids['dairy-eggs']     ?? 0;
		$bc  = $cat_ids['bread-cereals']  ?? 0;
		$ns  = $cat_ids['nuts-seeds']     ?? 0;
		$fo  = $cat_ids['fats-oils']      ?? 0;
		$dr  = $cat_ids['drinks']         ?? 0;
		$sc  = $cat_ids['snacks-confectionery'] ?? 0;
		$ta  = $cat_ids['takeaway']       ?? 0;
		$lp  = $cat_ids['legumes-pulses'] ?? 0;
		$co  = $cat_ids['condiments']     ?? 0;

		return [

			// =================================================================
			// FRUIT & VEGETABLES (20 foods)
			// =================================================================

			[
				'name' => 'Apple (raw, with skin)', 'slug' => 'apple-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium apple', 'grams' => 182], ['label' => '1 small apple', 'grams' => 120] ],
				'energy_kcal' => 52, 'energy_kj' => 218,
				'protein_g' => 0.3, 'carbohydrate_g' => 13.8, 'of_which_sugars_g' => 10.4,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.4, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed. Table 17; USDA FDC #1102644',
			],
			[
				'name' => 'Banana (raw)', 'slug' => 'banana-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium banana', 'grams' => 120], ['label' => '1 large banana', 'grams' => 152] ],
				'energy_kcal' => 89, 'energy_kj' => 375,
				'protein_g' => 1.1, 'carbohydrate_g' => 22.8, 'of_which_sugars_g' => 12.2,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 2.6, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.; USDA FDC #1105314',
			],
			[
				'name' => 'Orange (raw)', 'slug' => 'orange-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium orange', 'grams' => 131], ['label' => '1 segment', 'grams' => 18] ],
				'energy_kcal' => 47, 'energy_kj' => 197,
				'protein_g' => 0.9, 'carbohydrate_g' => 11.8, 'of_which_sugars_g' => 9.4,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.4, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Strawberries (raw)', 'slug' => 'strawberries-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 strawberry', 'grams' => 18], ['label' => 'Handful (80g)', 'grams' => 80] ],
				'energy_kcal' => 32, 'energy_kj' => 134,
				'protein_g' => 0.7, 'carbohydrate_g' => 7.7, 'of_which_sugars_g' => 4.9,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.0, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Grapes (raw)', 'slug' => 'grapes-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => 'Small bunch (80g)', 'grams' => 80] ],
				'energy_kcal' => 69, 'energy_kj' => 291,
				'protein_g' => 0.7, 'carbohydrate_g' => 18.1, 'of_which_sugars_g' => 15.5,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.9, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Avocado (raw)', 'slug' => 'avocado-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '½ avocado', 'grams' => 75], ['label' => 'Whole avocado', 'grams' => 150] ],
				'energy_kcal' => 160, 'energy_kj' => 670,
				'protein_g' => 2.0, 'carbohydrate_g' => 1.8, 'of_which_sugars_g' => 0.7,
				'fat_g' => 14.7, 'of_which_saturates_g' => 2.1, 'fibre_g' => 6.7, 'salt_g' => 0.01,
				'omega3_total_mg' => 111, 'omega3_ala_mg' => 111, 'omega3_epa_mg' => null, 'omega3_dha_mg' => null,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.; Omega-3 ALA: USDA FDC #171706 (avocado, raw)',
			],
			[
				'name' => 'Carrot (raw)', 'slug' => 'carrot-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium carrot', 'grams' => 80], ['label' => '1 large carrot', 'grams' => 110] ],
				'energy_kcal' => 41, 'energy_kj' => 172,
				'protein_g' => 0.9, 'carbohydrate_g' => 9.6, 'of_which_sugars_g' => 4.7,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.8, 'salt_g' => 0.07,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Broccoli (raw)', 'slug' => 'broccoli-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 portion (85g)', 'grams' => 85] ],
				'energy_kcal' => 34, 'energy_kj' => 142,
				'protein_g' => 2.8, 'carbohydrate_g' => 6.6, 'of_which_sugars_g' => 1.7,
				'fat_g' => 0.4, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.6, 'salt_g' => 0.03,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Spinach (raw)', 'slug' => 'spinach-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => 'Handful (30g)', 'grams' => 30], ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 23, 'energy_kj' => 97,
				'protein_g' => 2.9, 'carbohydrate_g' => 3.6, 'of_which_sugars_g' => 0.4,
				'fat_g' => 0.4, 'of_which_saturates_g' => 0.1, 'fibre_g' => 2.2, 'salt_g' => 0.18,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Potato (raw)', 'slug' => 'potato-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium potato', 'grams' => 175], ['label' => '1 small potato', 'grams' => 100] ],
				'energy_kcal' => 77, 'energy_kj' => 322,
				'protein_g' => 2.0, 'carbohydrate_g' => 17.0, 'of_which_sugars_g' => 0.7,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.2, 'salt_g' => 0.01,
				'is_fruit_veg' => 0, 'portion_grams' => 120,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Sweet Potato (raw)', 'slug' => 'sweet-potato-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium sweet potato', 'grams' => 200] ],
				'energy_kcal' => 86, 'energy_kj' => 361,
				'protein_g' => 1.6, 'carbohydrate_g' => 20.1, 'of_which_sugars_g' => 4.2,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 3.0, 'salt_g' => 0.07,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Onion (raw)', 'slug' => 'onion-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium onion', 'grams' => 110] ],
				'energy_kcal' => 40, 'energy_kj' => 167,
				'protein_g' => 1.1, 'carbohydrate_g' => 9.3, 'of_which_sugars_g' => 4.2,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 1.4, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Tomato (raw)', 'slug' => 'tomato-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 medium tomato', 'grams' => 123], ['label' => '1 cherry tomato', 'grams' => 17] ],
				'energy_kcal' => 18, 'energy_kj' => 74,
				'protein_g' => 0.9, 'carbohydrate_g' => 3.9, 'of_which_sugars_g' => 2.6,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 1.2, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Cucumber (raw)', 'slug' => 'cucumber-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '5cm chunk', 'grams' => 60] ],
				'energy_kcal' => 10, 'energy_kj' => 45,
				'protein_g' => 0.7, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 1.7,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.5, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Peas (frozen, boiled)', 'slug' => 'peas-frozen-boiled', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '3 tbsp (80g)', 'grams' => 80] ],
				'energy_kcal' => 69, 'energy_kj' => 291,
				'protein_g' => 6.0, 'carbohydrate_g' => 9.7, 'of_which_sugars_g' => 3.3,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 5.1, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Lettuce (raw)', 'slug' => 'lettuce-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => 'Side salad portion', 'grams' => 45] ],
				'energy_kcal' => 15, 'energy_kj' => 63,
				'protein_g' => 1.4, 'carbohydrate_g' => 1.7, 'of_which_sugars_g' => 1.7,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.0, 'fibre_g' => 1.3, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Courgette (raw)', 'slug' => 'courgette-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '½ courgette (80g)', 'grams' => 80] ],
				'energy_kcal' => 17, 'energy_kj' => 71,
				'protein_g' => 1.8, 'carbohydrate_g' => 1.8, 'of_which_sugars_g' => 1.4,
				'fat_g' => 0.4, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.1, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Red Pepper (raw)', 'slug' => 'red-pepper-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '½ pepper (80g)', 'grams' => 80], ['label' => '1 whole pepper', 'grams' => 160] ],
				'energy_kcal' => 31, 'energy_kj' => 130,
				'protein_g' => 1.0, 'carbohydrate_g' => 7.3, 'of_which_sugars_g' => 5.0,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.0, 'fibre_g' => 2.1, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Mushrooms (raw)', 'slug' => 'mushrooms-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '3 mushrooms (80g)', 'grams' => 80] ],
				'energy_kcal' => 15, 'energy_kj' => 63,
				'protein_g' => 1.8, 'carbohydrate_g' => 0.4, 'of_which_sugars_g' => 0.2,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.0, 'salt_g' => 0.0,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Celery (raw)', 'slug' => 'celery-raw', 'category_id' => $fv,
				'serving_sizes' => [ ['label' => '1 stick (40g)', 'grams' => 40] ],
				'energy_kcal' => 7, 'energy_kj' => 30,
				'protein_g' => 0.5, 'carbohydrate_g' => 1.4, 'of_which_sugars_g' => 1.0,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 1.2, 'salt_g' => 0.12,
				'is_fruit_veg' => 1, 'portion_grams' => 80,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// MEAT & POULTRY (15 foods)
			// =================================================================

			[
				'name' => 'Chicken Breast (grilled, no skin)', 'slug' => 'chicken-breast-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 breast (130g)', 'grams' => 130], ['label' => '1 small breast (100g)', 'grams' => 100] ],
				'energy_kcal' => 165, 'energy_kj' => 690,
				'protein_g' => 31.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.6, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.23,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Chicken Thigh (roasted, no skin)', 'slug' => 'chicken-thigh-roasted', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 thigh (100g)', 'grams' => 100] ],
				'energy_kcal' => 179, 'energy_kj' => 749,
				'protein_g' => 26.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 8.2, 'of_which_saturates_g' => 2.3, 'fibre_g' => 0.0, 'salt_g' => 0.26,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Beef Mince (5% fat, raw)', 'slug' => 'beef-mince-5fat-raw', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 portion (125g)', 'grams' => 125] ],
				'energy_kcal' => 121, 'energy_kj' => 508,
				'protein_g' => 21.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.2, 'of_which_saturates_g' => 1.7, 'fibre_g' => 0.0, 'salt_g' => 0.16,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Beef Mince (20% fat, raw)', 'slug' => 'beef-mince-20fat-raw', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 portion (125g)', 'grams' => 125] ],
				'energy_kcal' => 225, 'energy_kj' => 939,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 17.5, 'of_which_saturates_g' => 7.3, 'fibre_g' => 0.0, 'salt_g' => 0.16,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Pork Chop (grilled, lean)', 'slug' => 'pork-chop-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 chop (120g)', 'grams' => 120] ],
				'energy_kcal' => 172, 'energy_kj' => 721,
				'protein_g' => 30.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Lamb Chop (grilled, lean)', 'slug' => 'lamb-chop-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 chop (80g)', 'grams' => 80] ],
				'energy_kcal' => 222, 'energy_kj' => 929,
				'protein_g' => 29.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 12.3, 'of_which_saturates_g' => 5.6, 'fibre_g' => 0.0, 'salt_g' => 0.21,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Back Bacon (grilled)', 'slug' => 'back-bacon-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 rasher (25g)', 'grams' => 25], ['label' => '2 rashers (50g)', 'grams' => 50] ],
				'energy_kcal' => 215, 'energy_kj' => 900,
				'protein_g' => 25.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 12.9, 'of_which_saturates_g' => 4.5, 'fibre_g' => 0.0, 'salt_g' => 2.80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Pork Sausages (grilled)', 'slug' => 'pork-sausages-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 sausage (50g)', 'grams' => 50], ['label' => '2 sausages (100g)', 'grams' => 100] ],
				'energy_kcal' => 294, 'energy_kj' => 1228,
				'protein_g' => 12.4, 'carbohydrate_g' => 9.5, 'of_which_sugars_g' => 0.9,
				'fat_g' => 22.8, 'of_which_saturates_g' => 8.6, 'fibre_g' => 0.6, 'salt_g' => 1.77,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Turkey Breast (roasted, no skin)', 'slug' => 'turkey-breast-roasted', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 slice (30g)', 'grams' => 30], ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 153, 'energy_kj' => 643,
				'protein_g' => 29.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.2, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Beef Sirloin Steak (grilled, lean)', 'slug' => 'beef-sirloin-grilled', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 steak (175g)', 'grams' => 175] ],
				'energy_kcal' => 171, 'energy_kj' => 719,
				'protein_g' => 30.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.4, 'of_which_saturates_g' => 2.1, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Ham (cooked, sliced)', 'slug' => 'ham-cooked-sliced', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 slice (35g)', 'grams' => 35], ['label' => '2 slices (70g)', 'grams' => 70] ],
				'energy_kcal' => 107, 'energy_kj' => 451,
				'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.3, 'of_which_saturates_g' => 1.1, 'fibre_g' => 0.0, 'salt_g' => 2.00,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Duck (roasted, no skin)', 'slug' => 'duck-roasted-no-skin', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 189, 'energy_kj' => 793,
				'protein_g' => 25.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 10.4, 'of_which_saturates_g' => 3.5, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Chicken Liver (fried)', 'slug' => 'chicken-liver-fried', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 169, 'energy_kj' => 710,
				'protein_g' => 22.1, 'carbohydrate_g' => 0.9, 'of_which_sugars_g' => 0.0,
				'fat_g' => 8.9, 'of_which_saturates_g' => 2.4, 'fibre_g' => 0.0, 'salt_g' => 0.43,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Lamb Kidney (fried)', 'slug' => 'lamb-kidney-fried', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '2 kidneys (75g)', 'grams' => 75] ],
				'energy_kcal' => 155, 'energy_kj' => 650,
				'protein_g' => 24.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.3, 'of_which_saturates_g' => 1.9, 'fibre_g' => 0.0, 'salt_g' => 0.43,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Salami', 'slug' => 'salami', 'category_id' => $mp,
				'serving_sizes' => [ ['label' => '3 slices (30g)', 'grams' => 30] ],
				'energy_kcal' => 425, 'energy_kj' => 1761,
				'protein_g' => 18.4, 'carbohydrate_g' => 1.2, 'of_which_sugars_g' => 0.5,
				'fat_g' => 38.0, 'of_which_saturates_g' => 14.6, 'fibre_g' => 0.0, 'salt_g' => 3.80,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// FISH & SEAFOOD (34 foods — oily fish carry Omega-3)
			// =================================================================

			[
				'name' => 'Salmon (Atlantic, farmed, raw)', 'slug' => 'salmon-atlantic-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150], ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 208, 'energy_kj' => 872,
				'protein_g' => 20.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 13.4, 'of_which_saturates_g' => 3.1,
				'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 2587, 'omega3_ala_mg' => 362, 'omega3_epa_mg' => 690, 'omega3_dha_mg' => 1457,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175167 (Salmon, Atlantic, farmed, raw)',
			],
			[
				'name' => 'Mackerel (raw)', 'slug' => 'mackerel-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ],
				'energy_kcal' => 205, 'energy_kj' => 858,
				'protein_g' => 18.7, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 14.3, 'of_which_saturates_g' => 3.3,
				'fibre_g' => 0.0, 'salt_g' => 0.23,
				'omega3_total_mg' => 2656, 'omega3_ala_mg' => 130, 'omega3_epa_mg' => 898, 'omega3_dha_mg' => 1401,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175139 (Fish, mackerel, Atlantic, raw)',
			],
			[
				'name' => 'Sardines (in oil, drained)', 'slug' => 'sardines-in-oil', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin drained (90g)', 'grams' => 90], ['label' => '1 sardine (28g)', 'grams' => 28] ],
				'energy_kcal' => 208, 'energy_kj' => 872,
				'protein_g' => 24.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 11.5, 'of_which_saturates_g' => 1.5,
				'fibre_g' => 0.0, 'salt_g' => 1.15,
				'omega3_total_mg' => 1480, 'omega3_ala_mg' => 50, 'omega3_epa_mg' => 473, 'omega3_dha_mg' => 509,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175124 (Fish, sardine, Atlantic, canned in oil, drained)',
			],
			[
				'name' => 'Tuna (fresh, bluefin, raw)', 'slug' => 'tuna-fresh-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (140g)', 'grams' => 140] ],
				'energy_kcal' => 144, 'energy_kj' => 604,
				'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.9, 'of_which_saturates_g' => 1.3,
				'fibre_g' => 0.0, 'salt_g' => 0.14,
				'omega3_total_mg' => 1664, 'omega3_ala_mg' => 55, 'omega3_epa_mg' => 363, 'omega3_dha_mg' => 1001,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175159 (Fish, tuna, fresh, bluefin, raw)',
			],
			[
				'name' => 'Tuna (canned in water, drained)', 'slug' => 'tuna-canned-in-water', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin (112g)', 'grams' => 112] ],
				'energy_kcal' => 116, 'energy_kj' => 486,
				'protein_g' => 25.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.3,
				'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed. (canned tuna in brine, drained). Omega-3 not reliably reported for canned water-pack; left NULL.',
			],
			[
				'name' => 'Kipper (baked)', 'slug' => 'kipper-baked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 kipper (130g)', 'grams' => 130] ],
				'energy_kcal' => 205, 'energy_kj' => 858,
				'protein_g' => 22.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 12.9, 'of_which_saturates_g' => 2.9,
				'fibre_g' => 0.0, 'salt_g' => 2.20,
				'omega3_total_mg' => 1840, 'omega3_ala_mg' => 130, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175114 (Fish, herring, Atlantic, raw) — kippers are cold-smoked herring; fatty-acid profile equivalent to herring.',
			],
			[
				'name' => 'Herring (raw)', 'slug' => 'herring-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ],
				'energy_kcal' => 158, 'energy_kj' => 661,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.0, 'of_which_saturates_g' => 2.0,
				'fibre_g' => 0.0, 'salt_g' => 0.26,
				'omega3_total_mg' => 1840, 'omega3_ala_mg' => 130, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175114 (Fish, herring, Atlantic, raw)',
			],
			[
				'name' => 'Rainbow Trout (raw)', 'slug' => 'rainbow-trout-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],
				'energy_kcal' => 141, 'energy_kj' => 591,
				'protein_g' => 19.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.6, 'of_which_saturates_g' => 1.2,
				'fibre_g' => 0.0, 'salt_g' => 0.11,
				'omega3_total_mg' => 1068, 'omega3_ala_mg' => 97, 'omega3_epa_mg' => 247, 'omega3_dha_mg' => 661,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175154 (Fish, trout, rainbow, wild, raw)',
			],
			[
				'name' => 'Cod (raw)', 'slug' => 'cod-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 17.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.1,
				'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'M&W 8th ed. Cod is a white fish; omega-3 content very low and not reliably tabulated; left NULL.',
			],
			[
				'name' => 'Haddock (raw)', 'slug' => 'haddock-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 81, 'energy_kj' => 339,
				'protein_g' => 18.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1,
				'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. White fish; omega-3 left NULL.',
			],
			[
				'name' => 'King Prawns (cooked)', 'slug' => 'king-prawns-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 king prawns (60g)', 'grams' => 60] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2,
				'fibre_g' => 0.0, 'salt_g' => 1.30,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Sea Bass (raw)', 'slug' => 'sea-bass-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 97, 'energy_kj' => 406,
				'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.0, 'of_which_saturates_g' => 0.5,
				'fibre_g' => 0.0, 'salt_g' => 0.20,
				'source_notes' => 'M&W 8th ed. Semi-oily fish; detailed omega-3 fatty-acid profile not available in M&W or USDA for this species; left NULL.',
			],

			// --- Additional seafood (seed v2) ---

			[
				'name' => 'Pollock (raw)', 'slug' => 'pollock-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.36,
				'source_notes' => 'USDA FDC #175136 (Fish, pollock, Alaska, raw). White fish; omega-3 left NULL.',
			],
			[
				'name' => 'Halibut (raw)', 'slug' => 'halibut-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (159g)', 'grams' => 159] ],
				'energy_kcal' => 91, 'energy_kj' => 380,
				'protein_g' => 18.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.3, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.23,
				'omega3_total_mg' => 363, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 91, 'omega3_dha_mg' => 247,
				'source_notes' => 'M&W 8th ed.; Omega-3: USDA FDC #175137 (Fish, halibut, Atlantic and Pacific, raw)',
			],
			[
				'name' => 'Plaice (raw)', 'slug' => 'plaice-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (130g)', 'grams' => 130] ],
				'energy_kcal' => 79, 'energy_kj' => 331,
				'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.29,
				'source_notes' => 'M&W 8th ed. (Plaice, raw). White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Dover Sole (raw)', 'slug' => 'dover-sole-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. (Sole, raw). White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Tilapia (raw)', 'slug' => 'tilapia-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (87g)', 'grams' => 87] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'USDA FDC #175175 (Fish, tilapia, raw). Low omega-3 lean white fish; left NULL.',
			],
			[
				'name' => 'Monkfish (raw)', 'slug' => 'monkfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 76, 'energy_kj' => 318,
				'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Monkfish, raw). Very lean white fish; omega-3 negligible; left NULL.',
			],
			[
				'name' => 'Flounder (raw)', 'slug' => 'flounder-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (127g)', 'grams' => 127] ],
				'energy_kcal' => 86, 'energy_kj' => 360,
				'protein_g' => 17.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.31,
				'source_notes' => 'USDA FDC #175117 (Fish, flatfish (flounder and sole species), raw).',
			],
			[
				'name' => 'Whiting (raw)', 'slug' => 'whiting-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 81, 'energy_kj' => 339,
				'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.28,
				'source_notes' => 'M&W 8th ed. (Whiting, raw). White fish; omega-3 left NULL.',
			],
			[
				'name' => 'Skate Wing (raw)', 'slug' => 'skate-wing-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 wing (200g)', 'grams' => 200] ],
				'energy_kcal' => 73, 'energy_kj' => 305,
				'protein_g' => 16.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.30,
				'source_notes' => 'M&W 8th ed. (Skate, raw). Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Anchovy (raw)', 'slug' => 'anchovy-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '5 anchovies (20g)', 'grams' => 20] ],
				'energy_kcal' => 131, 'energy_kj' => 548,
				'protein_g' => 20.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.8, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.30,
				'omega3_total_mg' => 2113, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 763, 'omega3_dha_mg' => 911,
				'source_notes' => 'USDA FDC #174183 (Fish, anchovy, European, raw); Omega-3: same source.',
			],
			[
				'name' => 'Sprats (raw)', 'slug' => 'sprats-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 158, 'energy_kj' => 661,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 0.26,
				'omega3_total_mg' => 1940, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 680, 'omega3_dha_mg' => 960,
				'source_notes' => 'M&W 8th ed. (Sprats, raw); Omega-3: estimated from comparable small oily fish (sprat/herring family, USDA FDC).',
			],
			[
				'name' => 'Sea Bream (raw)', 'slug' => 'sea-bream-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'M&W 8th ed. (Sea bream, raw). Semi-oily; omega-3 not reliably tabulated; left NULL.',
			],
			[
				'name' => 'Prawns (cooked)', 'slug' => 'prawns-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.30,
				'source_notes' => 'M&W 8th ed. (Prawns, boiled). Same nutritional profile as king prawns; omega-3 negligible; left NULL.',
			],
			[
				'name' => 'Lobster (cooked)', 'slug' => 'lobster-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '½ lobster (150g)', 'grams' => 150] ],
				'energy_kcal' => 103, 'energy_kj' => 431,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.9, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 392, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 163, 'omega3_dha_mg' => 174,
				'source_notes' => 'USDA FDC #175180 (Crustaceans, lobster, northern, cooked, moist heat); Omega-3: same source.',
			],
			[
				'name' => 'Crab (cooked)', 'slug' => 'crab-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 128, 'energy_kj' => 537,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 0.7, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 541, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 241, 'omega3_dha_mg' => 218,
				'source_notes' => 'M&W 8th ed. (Crab, boiled); Omega-3: USDA FDC #174215 (Crustaceans, crab, Dungeness, cooked).',
			],
			[
				'name' => 'Dressed Crab', 'slug' => 'dressed-crab', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 pot (43g)', 'grams' => 43], ['label' => '½ crab (100g)', 'grams' => 100] ],
				'energy_kcal' => 133, 'energy_kj' => 558,
				'protein_g' => 16.1, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 7.2, 'of_which_saturates_g' => 1.1, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'source_notes' => 'M&W 8th ed. (Crab, dressed — mixed brown and white meat with oil and seasoning).',
			],
			[
				'name' => 'Mussels (cooked)', 'slug' => 'mussels-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100], ['label' => '1 bag (200g)', 'grams' => 200] ],
				'energy_kcal' => 86, 'energy_kj' => 360,
				'protein_g' => 11.9, 'carbohydrate_g' => 3.7, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.2, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 665, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 276, 'omega3_dha_mg' => 286,
				'source_notes' => 'M&W 8th ed. (Mussels, boiled); Omega-3: USDA FDC #175188 (Mollusks, mussel, blue, cooked, moist heat).',
			],
			[
				'name' => 'Oysters (raw)', 'slug' => 'oysters-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 oysters (84g)', 'grams' => 84] ],
				'energy_kcal' => 81, 'energy_kj' => 339,
				'protein_g' => 9.5, 'carbohydrate_g' => 4.7, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.3, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 740, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 340, 'omega3_dha_mg' => 214,
				'source_notes' => 'USDA FDC #175191 (Mollusks, oyster, eastern, wild, raw); Omega-3: same source.',
			],
			[
				'name' => 'Clams (cooked)', 'slug' => 'clams-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 148, 'energy_kj' => 619,
				'protein_g' => 25.5, 'carbohydrate_g' => 5.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95,
				'omega3_total_mg' => 302, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 117, 'omega3_dha_mg' => 146,
				'source_notes' => 'USDA FDC #174203 (Mollusks, clam, mixed species, cooked, moist heat); Omega-3: same source.',
			],

			// --- Additional seafood (seed v3) ---

			[
				'name' => 'Scallops (raw)', 'slug' => 'scallops-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 scallops (90g)', 'grams' => 90] ],
				'energy_kcal' => 88, 'energy_kj' => 368,
				'protein_g' => 17.0, 'carbohydrate_g' => 2.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.45,
				'omega3_total_mg' => 398, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 167, 'omega3_dha_mg' => 193,
				'source_notes' => 'USDA FDC #175187 (Mollusks, scallop, mixed species, raw); Omega-3: same source.',
			],
			[
				'name' => 'Langoustines (cooked)', 'slug' => 'langoustines-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '5 langoustines (100g)', 'grams' => 100] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed. (Dublin Bay prawns/Norway lobster, boiled). Omega-3 not reliably tabulated for this species; left NULL.',
			],
			[
				'name' => 'Cockles (cooked)', 'slug' => 'cockles-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 53, 'energy_kj' => 222,
				'protein_g' => 12.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'source_notes' => 'M&W 8th ed. (Cockles, boiled). Omega-3 left NULL.',
			],
			[
				'name' => 'Whelks (cooked)', 'slug' => 'whelks-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 72, 'energy_kj' => 301,
				'protein_g' => 16.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.55,
				'source_notes' => 'M&W 8th ed. (Whelks, boiled). Omega-3 left NULL.',
			],
			[
				'name' => 'Squid / Calamari (raw)', 'slug' => 'squid-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.44,
				'omega3_total_mg' => 496, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 148, 'omega3_dha_mg' => 256,
				'source_notes' => 'USDA FDC #175186 (Mollusks, squid, mixed species, raw); Omega-3: same source.',
			],
			[
				'name' => 'Calamari (Fried)', 'slug' => 'calamari-fried', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 175, 'energy_kj' => 732,
				'protein_g' => 14.0, 'carbohydrate_g' => 11.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 8.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.3, 'salt_g' => 0.60,
				'source_notes' => 'M&W 8th ed. estimate (squid in batter, fried). Omega-3 reduced significantly by frying/batter; left NULL.',
			],
			[
				'name' => 'Octopus (cooked)', 'slug' => 'octopus-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 164, 'energy_kj' => 686,
				'protein_g' => 29.8, 'carbohydrate_g' => 4.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.1, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 306, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 106, 'omega3_dha_mg' => 145,
				'source_notes' => 'USDA FDC #175184 (Mollusks, octopus, common, cooked, moist heat); Omega-3: same source.',
			],
			[
				'name' => 'Tuna (Canned, in oil, drained)', 'slug' => 'tuna-canned-in-oil', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin (112g)', 'grams' => 112] ],
				'energy_kcal' => 189, 'energy_kj' => 791,
				'protein_g' => 27.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.0, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 0.93,
				'source_notes' => 'M&W 8th ed. (Tuna, canned in oil, drained). Omega-3 not reliably retained in oil-pack processing; left NULL.',
			],
			[
				'name' => 'Salmon (Canned)', 'slug' => 'salmon-canned', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '½ tin (105g)', 'grams' => 105], ['label' => '1 tin (213g)', 'grams' => 213] ],
				'energy_kcal' => 153, 'energy_kj' => 640,
				'protein_g' => 20.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 8.0, 'of_which_saturates_g' => 1.9, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 1824, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 463, 'omega3_dha_mg' => 895,
				'source_notes' => 'USDA FDC #175170 (Fish, salmon, sockeye, canned, drained); Omega-3: same source.',
			],
			[
				'name' => 'Sardines (Canned, in brine)', 'slug' => 'sardines-canned-brine', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin drained (90g)', 'grams' => 90] ],
				'energy_kcal' => 172, 'energy_kj' => 719,
				'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 8.8, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 380, 'omega3_dha_mg' => 430,
				'source_notes' => 'M&W 8th ed. (Sardines, canned in brine, drained); Omega-3: estimated from USDA FDC comparable values.',
			],
			[
				'name' => 'Sardines (Canned, in tomato sauce)', 'slug' => 'sardines-tomato-sauce', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin (120g)', 'grams' => 120] ],
				'energy_kcal' => 163, 'energy_kj' => 682,
				'protein_g' => 17.8, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 2.5,
				'fat_g' => 8.5, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.4, 'salt_g' => 1.20,
				'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 350, 'omega3_dha_mg' => 380,
				'source_notes' => 'M&W 8th ed. (Sardines in tomato sauce); Omega-3: estimated from USDA FDC comparable values.',
			],
			[
				'name' => 'Anchovies (Canned, in oil)', 'slug' => 'anchovies-canned', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '5 fillets (20g)', 'grams' => 20] ],
				'energy_kcal' => 210, 'energy_kj' => 879,
				'protein_g' => 28.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.7, 'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 9.20,
				'omega3_total_mg' => 2113, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 760, 'omega3_dha_mg' => 900,
				'source_notes' => 'USDA FDC #1905591 (Anchovies, canned in oil, drained). Very high salt from preservation brine. Omega-3: same source.',
			],
			[
				'name' => 'Mackerel (Canned, in brine)', 'slug' => 'mackerel-canned', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tin (125g)', 'grams' => 125] ],
				'energy_kcal' => 188, 'energy_kj' => 787,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 11.0, 'of_which_saturates_g' => 2.6, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 2400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 810, 'omega3_dha_mg' => 1270,
				'source_notes' => 'M&W 8th ed. (Mackerel, canned in brine, drained); Omega-3 well retained in canned form.',
			],
			[
				'name' => 'Smoked Salmon', 'slug' => 'smoked-salmon', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 slices (50g)', 'grams' => 50], ['label' => '100g pack', 'grams' => 100] ],
				'energy_kcal' => 142, 'energy_kj' => 594,
				'protein_g' => 23.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.5, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 3.00,
				'omega3_total_mg' => 1750, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 420, 'omega3_dha_mg' => 1030,
				'source_notes' => 'M&W 8th ed. (Smoked salmon); Omega-3: USDA FDC #175168 (salmon, chinook, smoked).',
			],
			[
				'name' => 'Smoked Haddock (raw)', 'slug' => 'smoked-haddock', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 101, 'energy_kj' => 423,
				'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.40,
				'source_notes' => 'M&W 8th ed. (Haddock, smoked, raw). White fish; omega-3 left NULL.',
			],
			[
				'name' => 'Smoked Mackerel', 'slug' => 'smoked-mackerel', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (125g)', 'grams' => 125] ],
				'energy_kcal' => 354, 'energy_kj' => 1482,
				'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 30.9, 'of_which_saturates_g' => 5.9, 'fibre_g' => 0.0, 'salt_g' => 1.70,
				'omega3_total_mg' => 3200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1000, 'omega3_dha_mg' => 1800,
				'source_notes' => 'M&W 8th ed. (Mackerel, smoked). Excellent omega-3 source; hot-smoking retains fatty-acid profile.',
			],
			[
				'name' => 'Smoked Trout', 'slug' => 'smoked-trout', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ],
				'energy_kcal' => 165, 'energy_kj' => 690,
				'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 7.8, 'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 2.00,
				'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 270, 'omega3_dha_mg' => 690,
				'source_notes' => 'M&W 8th ed. / USDA FDC #175154 equivalent (trout, rainbow, smoked).',
			],
			[
				'name' => 'Rollmops (pickled herring)', 'slug' => 'rollmops', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 rollmop (60g)', 'grams' => 60] ],
				'energy_kcal' => 162, 'energy_kj' => 678,
				'protein_g' => 13.5, 'carbohydrate_g' => 2.7, 'of_which_sugars_g' => 2.0,
				'fat_g' => 10.7, 'of_which_saturates_g' => 2.4, 'fibre_g' => 0.0, 'salt_g' => 2.80,
				'omega3_total_mg' => 1420, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 480, 'omega3_dha_mg' => 680,
				'source_notes' => 'M&W 8th ed. (Herring, pickled/rollmops). Omega-3 largely retained in pickling.',
			],
			[
				'name' => 'Gravlax (cured salmon)', 'slug' => 'gravlax', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 slices (75g)', 'grams' => 75] ],
				'energy_kcal' => 146, 'energy_kj' => 611,
				'protein_g' => 21.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.40,
				'omega3_total_mg' => 1820, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 400, 'omega3_dha_mg' => 980,
				'source_notes' => 'M&W 8th ed. / USDA FDC #175168 base (salmon, salt+sugar cured, not smoked). Omega-3 profile mirrors raw salmon.',
			],
			[
				'name' => 'Cod Roe (raw)', 'slug' => 'cod-roe', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 107, 'energy_kj' => 448,
				'protein_g' => 21.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.1, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.60,
				'omega3_total_mg' => 390, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 160, 'omega3_dha_mg' => 200,
				'source_notes' => 'M&W 8th ed. (Cod roe, raw); Omega-3 estimated from USDA FDC mixed roe data.',
			],
			[
				'name' => 'Salmon Roe (Ikura)', 'slug' => 'salmon-roe', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 250, 'energy_kj' => 1046,
				'protein_g' => 29.2, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 13.6, 'of_which_saturates_g' => 3.1, 'fibre_g' => 0.0, 'salt_g' => 1.90,
				'omega3_total_mg' => 3480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1290, 'omega3_dha_mg' => 1740,
				'source_notes' => 'USDA FDC #175172 (Fish roe, mixed species, raw). Salmon roe is an exceptionally rich omega-3 source.',
			],
			[
				'name' => 'Caviar (black, sturgeon)', 'slug' => 'caviar-black', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 264, 'energy_kj' => 1105,
				'protein_g' => 24.6, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 17.9, 'of_which_saturates_g' => 4.1, 'fibre_g' => 0.0, 'salt_g' => 5.60,
				'omega3_total_mg' => 6789, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 3101, 'omega3_dha_mg' => 2420,
				'source_notes' => 'USDA FDC #174219 (Caviar, black and red, granular). One of the most omega-3-dense foods per 100g.',
			],
			[
				'name' => 'Taramasalata', 'slug' => 'taramasalata', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (30g)', 'grams' => 30], ['label' => '1 portion (60g)', 'grams' => 60] ],
				'energy_kcal' => 446, 'energy_kj' => 1847,
				'protein_g' => 4.1, 'carbohydrate_g' => 9.3, 'of_which_sugars_g' => 2.5,
				'fat_g' => 43.5, 'of_which_saturates_g' => 5.6, 'fibre_g' => 0.3, 'salt_g' => 2.00,
				'source_notes' => 'M&W 8th ed. (Taramasalata, retail). High fat from oil; cod roe omega-3 diluted by vegetable oil addition; left NULL.',
			],
			[
				'name' => 'Battered Cod (fried)', 'slug' => 'battered-cod', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 piece (180g)', 'grams' => 180] ],
				'energy_kcal' => 247, 'energy_kj' => 1034,
				'protein_g' => 15.5, 'carbohydrate_g' => 18.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 12.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.6, 'salt_g' => 1.00,
				'source_notes' => 'M&W 8th ed. (Cod in batter, fried in blended oil). Omega-3 negligible after frying in white fish; left NULL.',
			],
			[
				'name' => 'Fish Cakes (fried)', 'slug' => 'fish-cakes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fish cake (70g)', 'grams' => 70], ['label' => '2 fish cakes (140g)', 'grams' => 140] ],
				'energy_kcal' => 200, 'energy_kj' => 837,
				'protein_g' => 10.0, 'carbohydrate_g' => 19.0, 'of_which_sugars_g' => 1.0,
				'fat_g' => 9.5, 'of_which_saturates_g' => 1.2, 'fibre_g' => 1.2, 'salt_g' => 1.10,
				'source_notes' => 'M&W 8th ed. (Fish cakes, fried). Omega-3 left NULL.',
			],
			[
				'name' => 'Fish Fingers (oven baked)', 'slug' => 'fish-fingers', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 finger (28g)', 'grams' => 28], ['label' => '4 fingers (112g)', 'grams' => 112] ],
				'energy_kcal' => 200, 'energy_kj' => 837,
				'protein_g' => 13.0, 'carbohydrate_g' => 18.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 7.5, 'of_which_saturates_g' => 0.8, 'fibre_g' => 0.8, 'salt_g' => 0.90,
				'source_notes' => 'M&W 8th ed. (Fish fingers, cod, oven baked). Omega-3 left NULL.',
			],

			// --- Additional seafood (seed v4) ---

			[
				'name' => 'Breaded Scampi (fried)', 'slug' => 'breaded-scampi', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '5 pieces (125g)', 'grams' => 125] ],
				'energy_kcal' => 237, 'energy_kj' => 992,
				'protein_g' => 12.2, 'carbohydrate_g' => 19.8, 'of_which_sugars_g' => 0.5,
				'fat_g' => 12.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.8, 'salt_g' => 1.20,
				'source_notes' => 'M&W 8th ed. (Scampi, breaded/battered, fried). Omega-3 left NULL.',
			],
			[
				'name' => 'Prawn Toast', 'slug' => 'prawn-toast', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 pieces (60g)', 'grams' => 60] ],
				'energy_kcal' => 222, 'energy_kj' => 929,
				'protein_g' => 11.0, 'carbohydrate_g' => 21.0, 'of_which_sugars_g' => 1.0,
				'fat_g' => 10.5, 'of_which_saturates_g' => 1.5, 'fibre_g' => 1.0, 'salt_g' => 1.10,
				'source_notes' => 'Estimated from typical UK takeaway/retail data. Omega-3 left NULL.',
			],
			[
				'name' => 'Nori (dried seaweed)', 'slug' => 'nori-dried', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 sheet (3g)', 'grams' => 3], ['label' => '10g', 'grams' => 10] ],
				'energy_kcal' => 35, 'energy_kj' => 146,
				'protein_g' => 5.8, 'carbohydrate_g' => 5.1, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.3, 'salt_g' => 0.55,
				'source_notes' => 'USDA FDC #168457 (Seaweed, laver, raw). Values per 100g.',
			],
			[
				'name' => 'Wakame (raw seaweed)', 'slug' => 'wakame-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 45, 'energy_kj' => 188,
				'protein_g' => 3.0, 'carbohydrate_g' => 9.1, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.5, 'salt_g' => 1.87,
				'source_notes' => 'USDA FDC #168456 (Seaweed, wakame, raw). Natural salt from seawater.',
			],
			[
				'name' => 'Arctic Char (raw)', 'slug' => 'arctic-char-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 125, 'energy_kj' => 523,
				'protein_g' => 19.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.0, 'of_which_saturates_g' => 1.1, 'fibre_g' => 0.0, 'salt_g' => 0.08,
				'omega3_total_mg' => 600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 320,
				'source_notes' => 'USDA FDC #175138 (Char, arctic, raw).',
			],
			[
				'name' => 'Brill (raw)', 'slug' => 'brill-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Barramundi (raw)', 'slug' => 'barramundi-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 97, 'energy_kj' => 406,
				'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.0, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 500, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 310,
				'source_notes' => 'EFSA / published data (Lates calcarifer, raw). Moderate omega-3 for a lean fish.',
			],
			[
				'name' => 'Carp (raw)', 'slug' => 'carp-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],
				'energy_kcal' => 162, 'energy_kj' => 678,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'omega3_total_mg' => 520, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 147, 'omega3_dha_mg' => 235,
				'source_notes' => 'M&W 8th ed. Freshwater fish, moderate fat; Omega-3: USDA FDC #175088.',
			],
			[
				'name' => 'Cod Cheeks (raw)', 'slug' => 'cod-cheeks-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 80, 'energy_kj' => 335,
				'protein_g' => 18.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28,
				'source_notes' => 'M&W 8th ed. Same nutritional profile as cod fillet. Omega-3 left NULL.',
			],
			[
				'name' => 'Cod Tongue (raw)', 'slug' => 'cod-tongue-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 80, 'energy_kj' => 335,
				'protein_g' => 18.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28,
				'source_notes' => 'M&W 8th ed. Same nutritional profile as cod; distinct gelatinous texture. Omega-3 left NULL.',
			],
			[
				'name' => 'Coley / Saithe (raw)', 'slug' => 'coley-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.28,
				'source_notes' => 'M&W 8th ed. (Coley/Saithe, raw). Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Cobia (raw)', 'slug' => 'cobia-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 103, 'energy_kj' => 431,
				'protein_g' => 20.7, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.4, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'omega3_total_mg' => 490, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 260,
				'source_notes' => 'USDA FDC / published data (Rachycentron canadum, raw). Omega-3 moderate for a semi-pelagic fish.',
			],

			// --- Additional seafood (seed v5) ---

			[
				'name' => 'Dabs (raw)', 'slug' => 'dabs-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fish (100g)', 'grams' => 100] ],
				'energy_kcal' => 76, 'energy_kj' => 318,
				'protein_g' => 16.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.26,
				'source_notes' => 'M&W 8th ed. (Dab, raw). Small flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Dogfish / Rock Salmon (raw)', 'slug' => 'dogfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 130, 'energy_kj' => 544,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.8, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Dogfish/rock salmon, raw). Semi-oily small shark; omega-3 left NULL.',
			],
			[
				'name' => 'Dorade / Wild Pink Sea Bream (raw)', 'slug' => 'dorade-sea-bream-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'M&W 8th ed. (Sea bream, raw). Wild dorade/pink sea bream has same profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Conger Eel (raw)', 'slug' => 'conger-eel-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 121, 'energy_kj' => 506,
				'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.0, 'of_which_saturates_g' => 1.2, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 430, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 240,
				'source_notes' => 'M&W 8th ed. Omega-3 estimated from USDA comparable eel data.',
			],
			[
				'name' => 'Eels (raw)', 'slug' => 'eels-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 184, 'energy_kj' => 770,
				'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 11.7, 'of_which_saturates_g' => 2.4, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'omega3_total_mg' => 741, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 149, 'omega3_dha_mg' => 392,
				'source_notes' => 'USDA FDC #175189 (Eel, mixed species, raw). Rich in fat and omega-3.',
			],
			[
				'name' => 'Cuttlefish (raw)', 'slug' => 'cuttlefish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 79, 'energy_kj' => 331,
				'protein_g' => 16.2, 'carbohydrate_g' => 0.8, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.65,
				'omega3_total_mg' => 310, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 182,
				'source_notes' => 'USDA FDC #175183 (Cuttlefish, mixed species, raw).',
			],
			[
				'name' => 'Fish Bones (edible, soft)', 'slug' => 'fish-bones-edible', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (20g)', 'grams' => 20] ],
				'energy_kcal' => 184, 'energy_kj' => 770,
				'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 10.0, 'of_which_saturates_g' => 2.5, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'source_notes' => 'Estimated from canned sardine/salmon bone composition (M&W 8th ed. / USDA). Very high calcium; omega-3 retained from host fish.',
			],
			[
				'name' => 'Fish Mix (Smoked Blend)', 'slug' => 'fish-mix-smoked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 180, 'energy_kj' => 753,
				'protein_g' => 21.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 10.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 2.50,
				'omega3_total_mg' => 800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 240, 'omega3_dha_mg' => 440,
				'source_notes' => 'Estimated average from typical UK smoked fish blend (salmon, haddock, mackerel). Omega-3 varies by mix.',
			],
			[
				'name' => 'Fish Mix (Classic)', 'slug' => 'fish-mix-classic', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.8, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'Estimated average from typical UK white fish blend (cod, haddock, pollock). Omega-3 left NULL.',
			],
			[
				'name' => 'Fish Soup Mix (Bouillabaisse)', 'slug' => 'fish-soup-mix', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (200g)', 'grams' => 200] ],
				'energy_kcal' => 85, 'energy_kj' => 356,
				'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'source_notes' => 'Estimated from typical bouillabaisse-style raw fish/shellfish mix (white fish, shellfish, rockfish). Omega-3 left NULL.',
			],
			[
				'name' => 'Turbot (raw)', 'slug' => 'turbot-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (200g)', 'grams' => 200] ],
				'energy_kcal' => 95, 'energy_kj' => 397,
				'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.5, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.23,
				'source_notes' => 'M&W 8th ed. (Turbot, raw). Premium white flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'John Dory (raw)', 'slug' => 'john-dory-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. (John Dory, raw). Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Red Gurnard (raw)', 'slug' => 'red-gurnard-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. (Gurnard, raw). Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Lemon Sole (raw)', 'slug' => 'lemon-sole-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (130g)', 'grams' => 130] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. (Lemon sole, raw). White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Sea Trout (raw)', 'slug' => 'sea-trout-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 135, 'energy_kj' => 565,
				'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.0, 'of_which_saturates_g' => 1.2, 'fibre_g' => 0.0, 'salt_g' => 0.10,
				'omega3_total_mg' => 720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 190, 'omega3_dha_mg' => 395,
				'source_notes' => 'M&W 8th ed. (Sea/salmon trout, raw). Omega-3 from USDA FDC #175150 equivalent.',
			],
			[
				'name' => 'Swordfish (raw)', 'slug' => 'swordfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 144, 'energy_kj' => 602,
				'protein_g' => 19.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.7, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 0.37,
				'omega3_total_mg' => 738, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 148, 'omega3_dha_mg' => 543,
				'source_notes' => 'USDA FDC #175132 (Swordfish, raw).',
			],
			[
				'name' => 'Brown Shrimp (cooked)', 'slug' => 'brown-shrimp-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (60g)', 'grams' => 60] ],
				'energy_kcal' => 98, 'energy_kj' => 410,
				'protein_g' => 22.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'source_notes' => 'M&W 8th ed. (Brown shrimps, boiled). Very lean; omega-3 left NULL.',
			],
			[
				'name' => 'Spider Crab (cooked)', 'slug' => 'spider-crab-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 111, 'energy_kj' => 464,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.5, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'omega3_total_mg' => 400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 160, 'omega3_dha_mg' => 190,
				'source_notes' => 'M&W 8th ed. (Crab, white meat, cooked). Omega-3 estimated from USDA FDC #174215.',
			],
			[
				'name' => 'Razor Clams (cooked)', 'slug' => 'razor-clams-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 razor clams (100g)', 'grams' => 100] ],
				'energy_kcal' => 85, 'energy_kj' => 356,
				'protein_g' => 14.5, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95,
				'omega3_total_mg' => 250, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 85, 'omega3_dha_mg' => 130,
				'source_notes' => 'Estimated from USDA clam data (FDC #174203). Omega-3 lower than blue/surf clams.',
			],
			[
				'name' => 'Red Mullet (raw)', 'slug' => 'red-mullet-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 109, 'energy_kj' => 456,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.0, 'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Red mullet, raw). Semi-oily fish; omega-3 left NULL.',
			],
			[
				'name' => 'Periwinkles (cooked)', 'slug' => 'periwinkles-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 64, 'energy_kj' => 268,
				'protein_g' => 13.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed. (Periwinkles/winkles, boiled). Omega-3 left NULL.',
			],

			// --- Additional seafood (seed v6) ---

			[
				'name' => 'Bloater', 'slug' => 'bloater', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 bloater (150g)', 'grams' => 150] ],
				'energy_kcal' => 189, 'energy_kj' => 791,
				'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 13.0, 'of_which_saturates_g' => 3.0, 'fibre_g' => 0.0, 'salt_g' => 1.80,
				'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 580, 'omega3_dha_mg' => 870,
				'source_notes' => 'M&W 8th ed. (Herring, bloater — lightly cold-smoked whole herring). Omega-3 comparable to fresh herring.',
			],
			[
				'name' => 'Hake (raw)', 'slug' => 'hake-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 19.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.2, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. (Hake, raw). Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Red Snapper (raw)', 'slug' => 'red-snapper-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 100, 'energy_kj' => 418,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.3, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 315, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 54, 'omega3_dha_mg' => 237,
				'source_notes' => 'USDA FDC #175121 (Snapper, mixed species, raw).',
			],
			[
				'name' => 'Gurnards Ungraded (raw)', 'slug' => 'gurnards-ungraded', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. (Gurnard, raw). Mixed/ungraded species; same profile as red gurnard. Omega-3 left NULL.',
			],
			[
				'name' => 'Grouper (raw)', 'slug' => 'grouper-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.17,
				'omega3_total_mg' => 243, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 47, 'omega3_dha_mg' => 170,
				'source_notes' => 'USDA FDC #175108 (Grouper, mixed species, raw).',
			],
			[
				'name' => 'Grey Mullet (raw)', 'slug' => 'grey-mullet-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 122, 'energy_kj' => 510,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'source_notes' => 'M&W 8th ed. (Grey mullet, raw). Semi-oily fish; omega-3 left NULL.',
			],
			[
				'name' => 'Hamachi (raw)', 'slug' => 'hamachi-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 146, 'energy_kj' => 611,
				'protein_g' => 23.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.1, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 1720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 170, 'omega3_dha_mg' => 1380,
				'source_notes' => 'USDA FDC #175118 (Yellowtail/Hamachi, Seriola spp., raw). Exceptional DHA content.',
			],
			[
				'name' => 'Kebab (Salmon, Ling + Cod)', 'slug' => 'fish-kebab-salmon-ling-cod', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 skewer (150g)', 'grams' => 150] ],
				'energy_kcal' => 115, 'energy_kj' => 481,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.0, 'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 620, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 160, 'omega3_dha_mg' => 370,
				'source_notes' => 'Estimated average of salmon (~⅓), ling (~⅓) and cod (~⅓). Omega-3 weighted from salmon component.',
			],
			[
				'name' => 'Kingfish (raw)', 'slug' => 'kingfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 116, 'energy_kj' => 485,
				'protein_g' => 20.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.2, 'of_which_saturates_g' => 0.7, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'omega3_total_mg' => 401, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 128, 'omega3_dha_mg' => 231,
				'source_notes' => 'USDA FDC #175109 (Mackerel, king/kingfish, raw).',
			],
			[
				'name' => 'Ling (raw)', 'slug' => 'ling-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. (Ling, raw). Lean white cod-family fish; omega-3 left NULL.',
			],
			[
				'name' => 'Mahi Mahi (raw)', 'slug' => 'mahi-mahi-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 85, 'energy_kj' => 356,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.31,
				'omega3_total_mg' => 189, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 42, 'omega3_dha_mg' => 133,
				'source_notes' => 'USDA FDC #175110 (Dolphinfish/Mahi-mahi, raw).',
			],
			[
				'name' => 'Marlin (raw)', 'slug' => 'marlin-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 122, 'energy_kj' => 510,
				'protein_g' => 21.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.0, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 541, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 83, 'omega3_dha_mg' => 424,
				'source_notes' => 'USDA FDC #175119 (Marlin, striped, raw).',
			],
			[
				'name' => 'Megrim (raw)', 'slug' => 'megrim-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (130g)', 'grams' => 130] ],
				'energy_kcal' => 79, 'energy_kj' => 331,
				'protein_g' => 17.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. (Megrim/whiff flatfish, raw). White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Monkfish Tail (raw)', 'slug' => 'monkfish-tail-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tail portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 76, 'energy_kj' => 318,
				'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Monkfish, raw). Tail is the primary edible cut; same profile as whole monkfish. Omega-3 left NULL.',
			],
			[
				'name' => 'Monkfish Cheeks (raw)', 'slug' => 'monkfish-cheeks-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 76, 'energy_kj' => 318,
				'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Monkfish, raw). Cheeks share the same lean profile as the tail. Omega-3 left NULL.',
			],
			[
				'name' => 'Monkfish Livers', 'slug' => 'monkfish-livers', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 150, 'energy_kj' => 628,
				'protein_g' => 14.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 9.5, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 0.50,
				'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 260,
				'source_notes' => 'Estimated from published monkfish liver (ankimo) data. High fat relative to flesh; omega-3 estimated.',
			],
			[
				'name' => 'Nile Perch (raw)', 'slug' => 'nile-perch-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 91, 'energy_kj' => 381,
				'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'USDA FDC #175113 (Perch, mixed species, raw) approximate for Lates niloticus. Omega-3 left NULL.',
			],
			[
				'name' => 'Octopus (Mediterranean, cooked)', 'slug' => 'octopus-mediterranean', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 164, 'energy_kj' => 686,
				'protein_g' => 29.8, 'carbohydrate_g' => 4.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.1, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 306, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 106, 'omega3_dha_mg' => 145,
				'source_notes' => 'USDA FDC #175184 (Octopus, common, cooked). Mediterranean-sourced; same profile as standard cooked octopus.',
			],
			[
				'name' => 'Octopus (U.K., raw)', 'slug' => 'octopus-uk-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 15.3, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.55,
				'omega3_total_mg' => 200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 65, 'omega3_dha_mg' => 115,
				'source_notes' => 'USDA FDC #175184 (Octopus, raw equivalent). UK-caught specimens; values lower than cooked due to water content.',
			],
			[
				'name' => 'Parrot Fish (raw)', 'slug' => 'parrot-fish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (140g)', 'grams' => 140] ],
				'energy_kcal' => 88, 'energy_kj' => 368,
				'protein_g' => 18.9, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'Estimated from USDA tropical reef fish data. Lean white fish; omega-3 left NULL.',
			],
			[
				'name' => 'Pomfret (raw)', 'slug' => 'pomfret-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fish (200g)', 'grams' => 200] ],
				'energy_kcal' => 121, 'energy_kj' => 506,
				'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.2, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'source_notes' => 'Published data (Pampus argenteus/silver pomfret). Semi-oily; omega-3 left NULL.',
			],
			[
				'name' => 'Pike (raw)', 'slug' => 'pike-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 88, 'energy_kj' => 368,
				'protein_g' => 19.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.1, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'M&W 8th ed. (Pike, raw). Freshwater predator; lean white fish. Omega-3 left NULL.',
			],
			[
				'name' => 'Redfish (raw)', 'slug' => 'redfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 94, 'energy_kj' => 393,
				'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.8, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'omega3_total_mg' => 290, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 104, 'omega3_dha_mg' => 168,
				'source_notes' => 'USDA FDC #175120 (Ocean perch/Atlantic redfish, Sebastes marinus, raw).',
			],

			// --- Additional seafood (seed v7) ---

			[
				'name' => 'Sand Soles Ungraded (raw)', 'slug' => 'sand-soles-ungraded', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 83, 'energy_kj' => 347,
				'protein_g' => 17.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. (Sole, raw). Same profile as Dover sole; mixed-grade sand soles. Omega-3 left NULL.',
			],
			[
				'name' => 'Sailfish (raw)', 'slug' => 'sailfish-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 110, 'energy_kj' => 460,
				'protein_g' => 21.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.5, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 90, 'omega3_dha_mg' => 360,
				'source_notes' => 'Estimated from published billfish data (Istiophorus spp.). Profile close to marlin/swordfish.',
			],
			[
				'name' => 'Salmon Head (raw)', 'slug' => 'salmon-head-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 salmon head (300g)', 'grams' => 300] ],
				'energy_kcal' => 185, 'energy_kj' => 774,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 12.5, 'of_which_saturates_g' => 2.8, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 1850, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 420, 'omega3_dha_mg' => 1260,
				'source_notes' => 'M&W 8th ed. / USDA FDC #175167 base. Head has higher fat than fillet; rich in collagen and omega-3.',
			],
			[
				'name' => 'Wild Salmon (raw)', 'slug' => 'wild-salmon-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 142, 'energy_kj' => 594,
				'protein_g' => 19.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.3, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.09,
				'omega3_total_mg' => 2018, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 411, 'omega3_dha_mg' => 1429,
				'source_notes' => 'USDA FDC #175167 (Salmon, Atlantic, wild, raw). Leaner and higher omega-3 than farmed salmon.',
			],
			[
				'name' => 'Sea Reared Trout (raw)', 'slug' => 'sea-reared-trout-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 150, 'energy_kj' => 628,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 7.5, 'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 0.10,
				'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 280, 'omega3_dha_mg' => 720,
				'source_notes' => 'M&W 8th ed. / published data (farmed rainbow trout, sea-reared). Higher fat than freshwater farmed trout.',
			],
			[
				'name' => 'Sea Cucumber (raw)', 'slug' => 'sea-cucumber-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 38, 'energy_kj' => 159,
				'protein_g' => 6.4, 'carbohydrate_g' => 0.3, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.50,
				'source_notes' => 'Published data (Holothuria spp., raw). Very low calorie marine invertebrate; omega-3 left NULL.',
			],
			[
				'name' => 'Shark Steaks (raw)', 'slug' => 'shark-steaks-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 130, 'energy_kj' => 544,
				'protein_g' => 21.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.5, 'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'omega3_total_mg' => 737, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 218, 'omega3_dha_mg' => 485,
				'source_notes' => 'USDA FDC #175126 (Shark, mixed species, raw).',
			],
			[
				'name' => 'Tope Shark (raw)', 'slug' => 'tope-shark-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 130, 'energy_kj' => 544,
				'protein_g' => 20.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.0, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.22,
				'source_notes' => 'M&W 8th ed. (Dogfish/shark, raw). Tope (Galeorhinus galeus) shares similar profile to smaller sharks. Omega-3 left NULL.',
			],
			[
				'name' => 'Skate Knobs / Eyes (raw)', 'slug' => 'skate-knobs-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 73, 'energy_kj' => 305,
				'protein_g' => 16.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.30,
				'source_notes' => 'M&W 8th ed. (Skate wing, raw). Knobs/eyes are small cartilage-bordered discs from the wing; same profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Squid (Whole, raw)', 'slug' => 'squid-whole-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 whole squid (200g)', 'grams' => 200] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.44,
				'omega3_total_mg' => 496, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 148, 'omega3_dha_mg' => 256,
				'source_notes' => 'USDA FDC #175186 (Squid, mixed species, raw). Whole uncleaned squid; same per-100g profile as cleaned squid rings.',
			],
			[
				'name' => 'Sturgeon (raw)', 'slug' => 'sturgeon-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 105, 'energy_kj' => 439,
				'protein_g' => 16.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.0, 'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 597, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 198, 'omega3_dha_mg' => 367,
				'source_notes' => 'USDA FDC #175131 (Sturgeon, mixed species, raw).',
			],
			[
				'name' => 'Stone Bass / Meagre (raw)', 'slug' => 'stone-bass-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 88, 'energy_kj' => 368,
				'protein_g' => 18.8, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'source_notes' => 'Published data (Argyrosomus regius / meagre, raw). Large lean white sea fish; omega-3 left NULL.',
			],
			[
				'name' => 'Tilapia (Black, raw)', 'slug' => 'tilapia-black-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (87g)', 'grams' => 87] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'USDA FDC #175175 (Tilapia, raw). Black tilapia (O. niloticus) has same nutritional profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Tilapia (Red, raw)', 'slug' => 'tilapia-red-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (87g)', 'grams' => 87] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'USDA FDC #175175 (Tilapia, raw). Red tilapia hybrid has same nutritional profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Brown Trout (raw)', 'slug' => 'brown-trout-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 119, 'energy_kj' => 498,
				'protein_g' => 20.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.1, 'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.10,
				'omega3_total_mg' => 580, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 316,
				'source_notes' => 'M&W 8th ed. (Brown trout, raw). Omega-3 from USDA FDC #175150 equivalent.',
			],
			[
				'name' => 'Tuna Toro (Fatty Tuna, raw)', 'slug' => 'tuna-toro-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 344, 'energy_kj' => 1440,
				'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 27.4, 'of_which_saturates_g' => 6.6, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 3800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 900, 'omega3_dha_mg' => 2800,
				'source_notes' => 'USDA FDC (Bluefin tuna, belly/toro). Fatty belly cut (otoro/chutoro); one of the richest omega-3 sources in sushi.',
			],
			[
				'name' => 'Witch Sole / Torbay Sole (raw)', 'slug' => 'witch-sole-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 79, 'energy_kj' => 331,
				'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.24,
				'source_notes' => 'M&W 8th ed. (Witch sole / Glyptocephalus cynoglossus, raw). White flatfish; omega-3 left NULL.',
			],
			[
				'name' => 'Zander (raw)', 'slug' => 'zander-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 84, 'energy_kj' => 352,
				'protein_g' => 19.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.14,
				'source_notes' => 'Published data (Sander lucioperca, raw). Lean freshwater predator; omega-3 left NULL.',
			],
			[
				'name' => 'Clams (Amandes, cooked)', 'slug' => 'clams-amandes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 80, 'energy_kj' => 335,
				'protein_g' => 13.5, 'carbohydrate_g' => 3.8, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'source_notes' => 'Estimated from USDA clam data (Glycymeris spp. / dog cockle). Profile similar to standard clams; omega-3 left NULL.',
			],
			[
				'name' => 'Clams (Palourdes, cooked)', 'slug' => 'clams-palourdes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 87, 'energy_kj' => 364,
				'protein_g' => 14.8, 'carbohydrate_g' => 3.7, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.98,
				'source_notes' => 'Estimated from USDA clam data (Ruditapes decussatus / grooved carpet shell). Omega-3 left NULL.',
			],
			[
				'name' => 'Clams (Venus / Surf, cooked)', 'slug' => 'clams-venus-surf', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 85, 'energy_kj' => 356,
				'protein_g' => 14.5, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.95,
				'source_notes' => 'Estimated from USDA clam data (Venerupis / surf clam spp.). Omega-3 left NULL.',
			],
			[
				'name' => 'Crab Claws (cooked)', 'slug' => 'crab-claws-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '4 claws (100g)', 'grams' => 100] ],
				'energy_kcal' => 100, 'energy_kj' => 418,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.5, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 360, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170,
				'source_notes' => 'M&W 8th ed. (Crab, mixed meat, cooked). Claw meat is slightly richer than white meat. Omega-3 estimated.',
			],
			[
				'name' => 'Crab Meat (Brown)', 'slug' => 'crab-meat-brown', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 128, 'energy_kj' => 536,
				'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'omega3_total_mg' => 720, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 320, 'omega3_dha_mg' => 310,
				'source_notes' => 'M&W 8th ed. (Crab, brown meat). Higher fat and stronger flavour than white meat; good omega-3 source.',
			],
			[
				'name' => 'Crab Meat (White)', 'slug' => 'crab-meat-white', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.0, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.95,
				'omega3_total_mg' => 390, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 170, 'omega3_dha_mg' => 180,
				'source_notes' => 'M&W 8th ed. (Crab, white meat). Leaner than brown meat; delicate flavour. Omega-3 estimated.',
			],
			[
				'name' => 'Crab Meat (Claw)', 'slug' => 'crab-meat-claw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 110, 'energy_kj' => 460,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.0, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 190, 'omega3_dha_mg' => 210,
				'source_notes' => 'M&W 8th ed. (Crab, claw meat). Darker and stronger-flavoured than body white meat. Omega-3 estimated.',
			],
			[
				'name' => 'Crab Meat (Backfin)', 'slug' => 'crab-meat-backfin', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 87, 'energy_kj' => 364,
				'protein_g' => 18.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.1, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'omega3_total_mg' => 320, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 160,
				'source_notes' => 'USDA FDC (Blue crab, lump/backfin meat, cooked). Very lean pick from the back section. Omega-3 estimated.',
			],
			[
				'name' => 'Velvet Crab (cooked)', 'slug' => 'velvet-crab-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 95, 'energy_kj' => 397,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.5, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170,
				'source_notes' => 'M&W 8th ed. (Crab, cooked). Velvet crab (Necora puber) is a small swimming crab; profile close to edible crab white meat. Omega-3 estimated.',
			],
			[
				'name' => 'Cockles (in Shell, Live)', 'slug' => 'cockles-in-shell-live', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (90g deshelled)', 'grams' => 90] ],
				'energy_kcal' => 36, 'energy_kj' => 151,
				'protein_g' => 8.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'source_notes' => 'M&W 8th ed. (Cockles, raw). Values per 100g edible meat; ~250g in-shell yields ~90g meat. Omega-3 left NULL.',
			],

			// --- Additional seafood (seed v8) ---

			[
				'name' => 'Crayfish (English, cooked)', 'slug' => 'crayfish-english-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 72, 'energy_kj' => 301,
				'protein_g' => 15.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.35,
				'source_notes' => 'M&W 8th ed. equivalent (freshwater crayfish, boiled). White Clawed/signal crayfish; omega-3 left NULL.',
			],
			[
				'name' => 'Crayfish (Import, cooked)', 'slug' => 'crayfish-import-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 77, 'energy_kj' => 322,
				'protein_g' => 16.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.1, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.38,
				'source_notes' => 'USDA FDC #175178 (Crayfish, mixed species, farmed, cooked). Imported farmed variety; omega-3 left NULL.',
			],
			[
				'name' => 'Lobster (Native, cooked)', 'slug' => 'lobster-native-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '½ lobster (150g)', 'grams' => 150] ],
				'energy_kcal' => 103, 'energy_kj' => 431,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.9, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 392, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 163, 'omega3_dha_mg' => 174,
				'source_notes' => 'USDA FDC #175180 (Lobster, northern, cooked). Native European lobster (Homarus gammarus) has same profile.',
			],
			[
				'name' => 'Spiny Lobster (cooked)', 'slug' => 'spiny-lobster-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tail (150g)', 'grams' => 150] ],
				'energy_kcal' => 112, 'energy_kj' => 469,
				'protein_g' => 20.6, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.85,
				'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170,
				'source_notes' => 'USDA FDC #175181 (Spiny lobster, mixed, cooked). Rock lobster/crawfish (Palinuridae); no claws, tail-only meat.',
			],
			[
				'name' => 'Oysters (Native, raw)', 'slug' => 'oysters-native-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 oysters (84g)', 'grams' => 84] ],
				'energy_kcal' => 81, 'energy_kj' => 339,
				'protein_g' => 9.5, 'carbohydrate_g' => 4.7, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.3, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 740, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 340, 'omega3_dha_mg' => 214,
				'source_notes' => 'M&W 8th ed. (Oysters, raw). Native flat oyster (Ostrea edulis) has same profile as Pacific/rock oyster.',
			],
			[
				'name' => 'Sea Urchin (raw)', 'slug' => 'sea-urchin-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 103, 'energy_kj' => 431,
				'protein_g' => 13.0, 'carbohydrate_g' => 3.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.5, 'of_which_saturates_g' => 0.8, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 480, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 196, 'omega3_dha_mg' => 224,
				'source_notes' => 'USDA FDC #175193 (Sea urchin, raw). Roe/gonads only (uni); omega-3 from USDA equivalent.',
			],
			[
				'name' => 'Sea Lettuce (raw)', 'slug' => 'sea-lettuce-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 25, 'energy_kj' => 105,
				'protein_g' => 2.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 2.0, 'salt_g' => 1.20,
				'source_notes' => 'Published data (Ulva lactuca, fresh/raw). Bright green seaweed; omega-3 negligible in green algae, left NULL.',
			],
			[
				'name' => 'Sea Spaghetti (raw)', 'slug' => 'sea-spaghetti-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 27, 'energy_kj' => 113,
				'protein_g' => 1.5, 'carbohydrate_g' => 4.5, 'of_which_sugars_g' => 0.3,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.1, 'fibre_g' => 2.2, 'salt_g' => 1.50,
				'source_notes' => 'Published data (Himanthalia elongata / thongweed, fresh). Brown seaweed; omega-3 left NULL.',
			],
			[
				'name' => 'Dulse (raw)', 'slug' => 'dulse-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (30g)', 'grams' => 30] ],
				'energy_kcal' => 43, 'energy_kj' => 180,
				'protein_g' => 3.5, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 1.0,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 1.80,
				'source_notes' => 'Published data (Palmaria palmata, fresh/raw). Red seaweed; high natural salt from seawater. Omega-3 left NULL.',
			],
			[
				'name' => 'Kombu (raw)', 'slug' => 'kombu-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 strip (10g)', 'grams' => 10] ],
				'energy_kcal' => 43, 'energy_kj' => 180,
				'protein_g' => 1.7, 'carbohydrate_g' => 9.6, 'of_which_sugars_g' => 0.6,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.3, 'salt_g' => 2.30,
				'source_notes' => 'USDA FDC #168455 (Seaweed, kelp, raw). Kombu/kelp (Saccharina/Laminaria spp.); iodine-rich. Omega-3 left NULL.',
			],
			[
				'name' => 'Scampi Tails (cooked)', 'slug' => 'scampi-tails-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed. (Dublin Bay prawns/scampi, boiled). Unbreaded langoustine tails; omega-3 left NULL.',
			],
			[
				'name' => 'Scallops (King, Roe On, raw)', 'slug' => 'scallops-king-roe-on', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 scallops (120g)', 'grams' => 120] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 17.5, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.45,
				'omega3_total_mg' => 450, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 185, 'omega3_dha_mg' => 220,
				'source_notes' => 'USDA FDC #175187 base. Includes orange roe/coral which adds fat and omega-3 relative to roe-off scallops.',
			],
			[
				'name' => 'Tiger Prawns (cooked)', 'slug' => 'tiger-prawns-cooked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 prawns (100g)', 'grams' => 100] ],
				'energy_kcal' => 105, 'energy_kj' => 439,
				'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'source_notes' => 'USDA FDC #175177 / M&W. Tiger prawn (Penaeus monodon) profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Arbroath Smokies', 'slug' => 'arbroath-smokies', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 smokie (180g)', 'grams' => 180] ],
				'energy_kcal' => 101, 'energy_kj' => 423,
				'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.40,
				'source_notes' => 'M&W 8th ed. (Haddock, smoked). Arbroath Smokie is a PGI hot-smoked whole haddock on the bone; same nutritional profile. Omega-3 left NULL.',
			],
			[
				'name' => 'Buckling', 'slug' => 'buckling', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 buckling (150g)', 'grams' => 150] ],
				'energy_kcal' => 250, 'energy_kj' => 1046,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 18.5, 'of_which_saturates_g' => 4.2, 'fibre_g' => 0.0, 'salt_g' => 2.50,
				'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 1050,
				'source_notes' => 'M&W 8th ed. (Herring, hot-smoked / buckling). Richer than bloater as hot-smoking renders more fat. Excellent omega-3 source.',
			],
			[
				'name' => 'Smoked Cod Roe (Natural)', 'slug' => 'smoked-cod-roe', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 156, 'energy_kj' => 653,
				'protein_g' => 22.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 7.5, 'of_which_saturates_g' => 1.8, 'fibre_g' => 0.0, 'salt_g' => 2.80,
				'omega3_total_mg' => 600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 240, 'omega3_dha_mg' => 305,
				'source_notes' => 'M&W 8th ed. (Cod roe, smoked). Distinct from blended taramasalata; eaten sliced on bread.',
			],
			[
				'name' => 'Smoked Cod', 'slug' => 'smoked-cod', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 101, 'energy_kj' => 423,
				'protein_g' => 23.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 2.80,
				'source_notes' => 'M&W 8th ed. (Cod, smoked). Lean white fish; high salt from brining. Omega-3 left NULL.',
			],
			[
				'name' => 'Cured Salmon Trio', 'slug' => 'cured-salmon-trio', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 155, 'energy_kj' => 649,
				'protein_g' => 23.0, 'carbohydrate_g' => 0.2, 'of_which_sugars_g' => 0.2,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.50,
				'omega3_total_mg' => 1500, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 350, 'omega3_dha_mg' => 950,
				'source_notes' => 'Estimated average of smoked, gravlax and hot-smoked salmon portions. Omega-3 weighted average.',
			],
			[
				'name' => 'Gravadlax (Beetroot)', 'slug' => 'gravadlax-beetroot', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 slices (75g)', 'grams' => 75] ],
				'energy_kcal' => 150, 'energy_kj' => 628,
				'protein_g' => 21.0, 'carbohydrate_g' => 2.5, 'of_which_sugars_g' => 2.0,
				'fat_g' => 6.0, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.1, 'salt_g' => 2.40,
				'omega3_total_mg' => 1750, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 400, 'omega3_dha_mg' => 1050,
				'source_notes' => 'M&W 8th ed. / USDA FDC #175168 base (salmon, beetroot-cured). Small carbohydrate from beetroot marinade; omega-3 mirrors raw salmon.',
			],
			[
				'name' => 'Hot Roast Salmon', 'slug' => 'hot-roast-salmon', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (120g)', 'grams' => 120] ],
				'energy_kcal' => 195, 'energy_kj' => 816,
				'protein_g' => 24.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 11.0, 'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 380, 'omega3_dha_mg' => 980,
				'source_notes' => 'M&W 8th ed. (Salmon, baked/roasted). Hot-roasted or hot-smoked; fat slightly reduced by cooking vs raw fillet.',
			],
			[
				'name' => 'Kipper', 'slug' => 'kipper', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 kipper (170g)', 'grams' => 170] ],
				'energy_kcal' => 205, 'energy_kj' => 858,
				'protein_g' => 18.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 14.5, 'of_which_saturates_g' => 3.4, 'fibre_g' => 0.0, 'salt_g' => 3.00,
				'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 575, 'omega3_dha_mg' => 870,
				'source_notes' => 'M&W 8th ed. (Kipper, cold-smoked split herring). Very high salt from brining; excellent omega-3 source.',
			],
			[
				'name' => 'Bottarga', 'slug' => 'bottarga', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp shaved (15g)', 'grams' => 15] ],
				'energy_kcal' => 330, 'energy_kj' => 1381,
				'protein_g' => 40.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 20.0, 'of_which_saturates_g' => 5.0, 'fibre_g' => 0.0, 'salt_g' => 5.80,
				'omega3_total_mg' => 4000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1200, 'omega3_dha_mg' => 2400,
				'source_notes' => 'Published data (dried/salt-cured grey mullet or tuna roe). Concentrated omega-3; extremely high salt from preservation.',
			],
			[
				'name' => 'Avruga / Arenkha', 'slug' => 'avruga-arenkha', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 180, 'energy_kj' => 753,
				'protein_g' => 14.0, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 12.5, 'of_which_saturates_g' => 2.8, 'fibre_g' => 0.0, 'salt_g' => 3.50,
				'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 620, 'omega3_dha_mg' => 980,
				'source_notes' => 'Estimated from herring roe composition. Avruga/Arenkha is a caviar substitute made from smoked herring roe.',
			],
			[
				'name' => 'Crayfish Tails (in Brine)', 'slug' => 'crayfish-tails-brine', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 pot (100g)', 'grams' => 100] ],
				'energy_kcal' => 72, 'energy_kj' => 301,
				'protein_g' => 15.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.20,
				'source_notes' => 'USDA FDC #175178 base. Brine-packed crayfish tails; higher salt than plain cooked. Omega-3 left NULL.',
			],
			[
				'name' => 'Beluga Caviar', 'slug' => 'beluga-caviar', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 264, 'energy_kj' => 1105,
				'protein_g' => 26.9, 'carbohydrate_g' => 3.3, 'of_which_sugars_g' => 0.0,
				'fat_g' => 17.0, 'of_which_saturates_g' => 3.8, 'fibre_g' => 0.0, 'salt_g' => 5.20,
				'omega3_total_mg' => 6200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2800, 'omega3_dha_mg' => 2600,
				'source_notes' => 'USDA FDC #174219 / published data (Huso huso roe). Largest sturgeon eggs; one of the most omega-3-dense foods.',
			],
			[
				'name' => 'Oscietra Caviar', 'slug' => 'oscietra-caviar', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 258, 'energy_kj' => 1080,
				'protein_g' => 25.5, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 17.0, 'of_which_saturates_g' => 3.9, 'fibre_g' => 0.0, 'salt_g' => 5.40,
				'omega3_total_mg' => 5800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2600, 'omega3_dha_mg' => 2400,
				'source_notes' => 'Published data (Acipenser gueldenstaedtii roe). Nutty-flavoured medium-grade sturgeon caviar.',
			],
			[
				'name' => 'Sevruga Caviar', 'slug' => 'sevruga-caviar', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 260, 'energy_kj' => 1088,
				'protein_g' => 26.5, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 17.5, 'of_which_saturates_g' => 4.0, 'fibre_g' => 0.0, 'salt_g' => 5.50,
				'omega3_total_mg' => 6400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2900, 'omega3_dha_mg' => 2700,
				'source_notes' => 'Published data (Acipenser stellatus roe). Smallest and most strongly flavoured of the classic caviars.',
			],

			// --- Additional seafood (seed v9) ---

			[
				'name' => 'Jellied Eels', 'slug' => 'jellied-eels', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 98, 'energy_kj' => 410,
				'protein_g' => 8.4, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'source_notes' => 'M&W 8th ed. (Eels, jellied). Traditional London dish; eel in lightly seasoned aspic jelly. Omega-3 left NULL.',
			],
			[
				'name' => 'Lumpfish Roe (Black)', 'slug' => 'lumpfish-roe-black', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 163, 'energy_kj' => 682,
				'protein_g' => 21.0, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.0, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 5.00,
				'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 800, 'omega3_dha_mg' => 1200,
				'source_notes' => 'Published data (Cyclopterus lumpus roe, black). Budget caviar substitute; dyed black. High salt from preservation.',
			],
			[
				'name' => 'Lumpfish Roe (Red)', 'slug' => 'lumpfish-roe-red', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 163, 'energy_kj' => 682,
				'protein_g' => 21.0, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.0, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 5.00,
				'omega3_total_mg' => 2200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 800, 'omega3_dha_mg' => 1200,
				'source_notes' => 'Same base as black lumpfish roe; dyed red. Identical nutritional profile.',
			],
			[
				'name' => 'Octopus Salad (in Oil)', 'slug' => 'octopus-salad-oil', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 152, 'energy_kj' => 636,
				'protein_g' => 15.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 9.0, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'source_notes' => 'Estimated: cooked octopus pieces marinated in olive oil, vinegar, herbs. Omega-3 left NULL.',
			],
			[
				'name' => 'Squid Ink', 'slug' => 'squid-ink', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 sachet (4g)', 'grams' => 4] ],
				'energy_kcal' => 60, 'energy_kj' => 251,
				'protein_g' => 10.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'Per 100g cephalopod ink. Used as flavouring in pasta/rice; typical sachet 4g provides ~2.4 kcal. Omega-3 left NULL.',
			],
			[
				'name' => 'Samphire (Farmed)', 'slug' => 'samphire-farmed', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 18, 'energy_kj' => 75,
				'protein_g' => 1.5, 'carbohydrate_g' => 1.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 2.00,
				'source_notes' => 'Published data (Salicornia europaea, farmed marsh samphire). Naturally salty halophyte; rinse before cooking. Omega-3 left NULL.',
			],
			[
				'name' => 'Samphire (Wild)', 'slug' => 'samphire-wild', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 20, 'energy_kj' => 84,
				'protein_g' => 1.5, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.4, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.8, 'salt_g' => 2.50,
				'source_notes' => 'Published data (wild-foraged marsh or rock samphire). Slightly higher mineral content than farmed. Omega-3 left NULL.',
			],
			[
				'name' => 'Sea Purslane', 'slug' => 'sea-purslane', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 16, 'energy_kj' => 67,
				'protein_g' => 1.2, 'carbohydrate_g' => 1.8, 'of_which_sugars_g' => 0.3,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.5, 'salt_g' => 2.20,
				'source_notes' => 'Published data (Halimione portulacoides). Coastal succulent; strongly saline from habitat. Omega-3 left NULL.',
			],
			[
				'name' => 'Fish Soup (Perard)', 'slug' => 'fish-soup-perard', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 bowl (250g)', 'grams' => 250] ],
				'energy_kcal' => 48, 'energy_kj' => 201,
				'protein_g' => 3.2, 'carbohydrate_g' => 4.5, 'of_which_sugars_g' => 0.8,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.5, 'salt_g' => 1.20,
				'source_notes' => 'Perard Soupe de Poissons (product label est.). Classic Provençal fish soup; typically served with rouille and croutons.',
			],
			[
				'name' => 'Crab Soup (Perard)', 'slug' => 'crab-soup-perard', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 bowl (250g)', 'grams' => 250] ],
				'energy_kcal' => 53, 'energy_kj' => 222,
				'protein_g' => 2.8, 'carbohydrate_g' => 5.8, 'of_which_sugars_g' => 1.2,
				'fat_g' => 1.7, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.3, 'salt_g' => 1.00,
				'source_notes' => 'Perard Bisque de Crabe (product label est.). Creamy crab bisque; richer in carbohydrate than fish soup from cream and starch.',
			],
			[
				'name' => 'Lobster Soup (Perard)', 'slug' => 'lobster-soup-perard', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 bowl (250g)', 'grams' => 250] ],
				'energy_kcal' => 57, 'energy_kj' => 238,
				'protein_g' => 3.2, 'carbohydrate_g' => 6.1, 'of_which_sugars_g' => 1.5,
				'fat_g' => 2.2, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.3, 'salt_g' => 1.10,
				'source_notes' => 'Perard Bisque de Homard (product label est.). Premium lobster bisque; slightly higher fat than crab version.',
			],
			[
				'name' => 'Salmon Roe (Keta)', 'slug' => 'salmon-roe-keta', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 250, 'energy_kj' => 1046,
				'protein_g' => 29.2, 'carbohydrate_g' => 1.9, 'of_which_sugars_g' => 0.5,
				'fat_g' => 14.0, 'of_which_saturates_g' => 3.2, 'fibre_g' => 0.0, 'salt_g' => 1.60,
				'omega3_total_mg' => 5600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 2100, 'omega3_dha_mg' => 3300,
				'source_notes' => 'USDA FDC #175194 (Salmon roe/ikura, raw). Chum/keta salmon roe; exceptional omega-3 source.',
			],
			[
				'name' => 'Seafood Salad (in Oil)', 'slug' => 'seafood-salad-oil', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 140, 'energy_kj' => 586,
				'protein_g' => 13.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 8.0, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 1.80,
				'omega3_total_mg' => 400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 200,
				'source_notes' => 'Estimated: mixed octopus, squid and mussels in sunflower/olive oil. Omega-3 from squid and mussel components.',
			],
			[
				'name' => 'Sweet Cure Herring', 'slug' => 'sweet-cure-herring', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 fillets (80g)', 'grams' => 80] ],
				'energy_kcal' => 165, 'energy_kj' => 690,
				'protein_g' => 13.5, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 6.5,
				'fat_g' => 9.0, 'of_which_saturates_g' => 2.0, 'fibre_g' => 0.0, 'salt_g' => 2.50,
				'omega3_total_mg' => 1300, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 500, 'omega3_dha_mg' => 700,
				'source_notes' => 'Estimated (sweet/sugar-cured marinated herring). High sugar from sweet brine marinade; retains good omega-3 despite processing.',
			],
			[
				'name' => 'Tobiko (Wasabi/Green)', 'slug' => 'tobiko-wasabi-green', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 220, 'energy_kj' => 920,
				'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0,
				'fat_g' => 7.0, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 3.50,
				'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 750, 'omega3_dha_mg' => 1100,
				'source_notes' => 'Estimated from flying fish roe (Cypselurus agoo). Wasabi/green-dyed variety; same nutritional base as orange tobiko.',
			],
			[
				'name' => 'Tobiko (Orange)', 'slug' => 'tobiko-orange', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 225, 'energy_kj' => 942,
				'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0,
				'fat_g' => 7.5, 'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 3.50,
				'omega3_total_mg' => 2100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 780, 'omega3_dha_mg' => 1150,
				'source_notes' => 'Published data (flying fish roe, natural orange). Standard tobiko; used extensively in sushi garnish.',
			],
			[
				'name' => 'Tobiko (Yellow)', 'slug' => 'tobiko-yellow', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 220, 'energy_kj' => 920,
				'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0,
				'fat_g' => 7.0, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 3.50,
				'omega3_total_mg' => 2000, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 750, 'omega3_dha_mg' => 1100,
				'source_notes' => 'Yuzu-infused yellow tobiko (flying fish roe). Flavour differs; nutritional profile mirrors wasabi/green variety.',
			],
			[
				'name' => 'Tuna (Chunks)', 'slug' => 'tuna-chunks', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 can drained (145g)', 'grams' => 145] ],
				'energy_kcal' => 109, 'energy_kj' => 456,
				'protein_g' => 25.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 210,
				'source_notes' => 'M&W 8th ed. (Tuna, canned in brine, drained). Chunk-style skipjack; lower omega-3 than fresh tuna owing to processing.',
			],
			[
				'name' => 'Terrine (Salmon & Cream)', 'slug' => 'terrine-salmon-cream', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 200, 'energy_kj' => 837,
				'protein_g' => 13.0, 'carbohydrate_g' => 2.0, 'of_which_sugars_g' => 1.0,
				'fat_g' => 16.0, 'of_which_saturates_g' => 6.0, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'omega3_total_mg' => 1100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 300, 'omega3_dha_mg' => 700,
				'source_notes' => 'Estimated (salmon terrine with cream cheese/crème fraîche). High sat fat from dairy; omega-3 from salmon component.',
			],
			[
				'name' => 'King Prawns (Seawater)', 'slug' => 'king-prawns-seawater', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 prawns (100g)', 'grams' => 100] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.80,
				'source_notes' => 'M&W 8th ed. / USDA base (cooked king prawn). Sold live-preserved in seawater; higher salt than plain cooked. Omega-3 left NULL.',
			],
			[
				'name' => 'Crevettes', 'slug' => 'crevettes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 98, 'energy_kj' => 410,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.90,
				'source_notes' => 'M&W 8th ed. equivalent. Large pink/grey shrimps (Palaemon serratus or Crangon crangon); often sold whole, head-on.',
			],
			[
				'name' => 'Cocktail Prawns', 'slug' => 'cocktail-prawns', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'source_notes' => 'M&W 8th ed. (Prawns, cooked). Small peeled cooked prawns; as used in prawn cocktail. Omega-3 left NULL.',
			],
			[
				'name' => 'Prawns (Cooked, Tail On)', 'slug' => 'prawns-cooked-tail-on', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '4 prawns (80g)', 'grams' => 80] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed. (Prawns, cooked). Values per 100g edible meat; shell-on tail portion. Omega-3 left NULL.',
			],
			[
				'name' => 'Prawns (Raw, Wild)', 'slug' => 'prawns-raw-wild', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 72, 'energy_kj' => 301,
				'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'source_notes' => 'USDA FDC #175177 (Shrimp, raw). Wild-caught; lower sodium than cooked/processed. Omega-3 left NULL.',
			],
			[
				'name' => 'Red Argentine Shrimps', 'slug' => 'red-argentine-shrimps', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'omega3_total_mg' => 440, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 270,
				'source_notes' => 'Published data (Pleoticus muelleri, raw). Wild deep-water Patagonian shrimp; ultra-lean, slightly sweet flavour.',
			],
			[
				'name' => 'Langoustine (Whole, Raw)', 'slug' => 'langoustine-whole-raw', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 whole (yields ~100g meat)', 'grams' => 100] ],
				'energy_kcal' => 75, 'energy_kj' => 314,
				'protein_g' => 16.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'omega3_total_mg' => 300, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 110, 'omega3_dha_mg' => 160,
				'source_notes' => 'M&W equivalent (Nephrops norvegicus, raw). Per 100g edible meat; ~35% yield from whole shell-on weight.',
			],
			[
				'name' => 'Soft Shell Crab', 'slug' => 'soft-shell-crab', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 medium crab (80g)', 'grams' => 80] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 15.1, 'carbohydrate_g' => 3.3, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.8, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 310, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 185,
				'source_notes' => 'USDA FDC #174204 base (blue crab, raw). Soft shell = moulted crab eaten whole including shell; carbohydrate from chitin.',
			],

			// --- Additional seafood (seed v10) ---

			[
				'name' => 'King Crab (Clusters)', 'slug' => 'king-crab-clusters', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 cluster (200g)', 'grams' => 200] ],
				'energy_kcal' => 97, 'energy_kj' => 406,
				'protein_g' => 19.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 1.40,
				'omega3_total_mg' => 530, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 204, 'omega3_dha_mg' => 275,
				'source_notes' => 'USDA FDC #174208 (Crustaceans, king crab, cooked). Clusters = legs + knuckles; meat extracted per 100g.',
			],
			[
				'name' => 'Squid Tubes (raw)', 'slug' => 'squid-tubes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tube (120g)', 'grams' => 120] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35,
				'omega3_total_mg' => 440, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240,
				'source_notes' => 'M&W 8th ed. / USDA (Squid, raw). Cleaned mantle only; tentacles excluded. Same composition as whole cleaned squid.',
			],
			[
				'name' => 'Baby Squid (raw)', 'slug' => 'baby-squid', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35,
				'omega3_total_mg' => 440, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240,
				'source_notes' => 'M&W 8th ed. (Squid, raw). Small whole squid (<10cm mantle). Nutritional profile identical to larger squid.',
			],
			[
				'name' => 'Baby Squid (Chipirones, raw)', 'slug' => 'baby-squid-chipirones', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 15.6, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.4, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.35,
				'omega3_total_mg' => 440, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 240,
				'source_notes' => 'Chipirones = Spanish name for very small squid (Sepiola atlantica / Alloteuthis spp.), raw. Same profile as baby squid.',
			],
			[
				'name' => 'Baby Cuttlefish (raw)', 'slug' => 'baby-cuttlefish', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 79, 'energy_kj' => 331,
				'protein_g' => 16.1, 'carbohydrate_g' => 0.8, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.7, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'omega3_total_mg' => 280, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 90, 'omega3_dha_mg' => 160,
				'source_notes' => 'M&W 8th ed. (Cuttlefish, raw). Small Sepia officinalis; leaner than squid with less carbohydrate.',
			],
			[
				'name' => 'Baby Octopus (raw)', 'slug' => 'baby-octopus', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 82, 'energy_kj' => 343,
				'protein_g' => 14.9, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.50,
				'omega3_total_mg' => 310, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 100, 'omega3_dha_mg' => 170,
				'source_notes' => 'USDA FDC #175171 base (Octopus, raw). Moscardino/horned octopus (<15cm); same composition as larger octopus.',
			],
			[
				'name' => 'Black Cod (Sablefish)', 'slug' => 'black-cod-sablefish', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 250, 'energy_kj' => 1046,
				'protein_g' => 13.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 21.5, 'of_which_saturates_g' => 5.2, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'omega3_total_mg' => 2400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 870, 'omega3_dha_mg' => 1500,
				'source_notes' => 'USDA FDC #175172 (Sablefish/black cod, raw). One of the richest omega-3 fish; buttery texture owing to high fat content.',
			],
			[
				'name' => 'Chilean Sea Bass', 'slug' => 'chilean-sea-bass', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 221, 'energy_kj' => 925,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 16.3, 'of_which_saturates_g' => 4.5, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 1800, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 570, 'omega3_dha_mg' => 1160,
				'source_notes' => 'USDA FDC (Dissostichus eleginoides / Patagonian toothfish, raw). Marketed as "Chilean sea bass"; very high fat, excellent omega-3.',
			],
			[
				'name' => 'Pangasius / Basa (raw)', 'slug' => 'pangasius-basa', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 89, 'energy_kj' => 373,
				'protein_g' => 12.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.2, 'of_which_saturates_g' => 0.9, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 90, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 35, 'omega3_dha_mg' => 48,
				'source_notes' => 'USDA FDC (Pangasius hypophthalmus, farmed). Mild white fish from Vietnamese aquaculture; very low omega-3 due to grain-based feed.',
			],
			[
				'name' => 'Whitebait (Blanched)', 'slug' => 'whitebait-blanched', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 125, 'energy_kj' => 523,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.30,
				'omega3_total_mg' => 1700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 900,
				'source_notes' => 'M&W 8th ed. (Whitebait, whole, boiled). Immature sprats/herring eaten whole; good omega-3 from oily juveniles.',
			],
			[
				'name' => 'Whitebait (Plain, raw)', 'slug' => 'whitebait-plain', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 125, 'energy_kj' => 523,
				'protein_g' => 19.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 5.5, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'omega3_total_mg' => 1700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 700, 'omega3_dha_mg' => 900,
				'source_notes' => 'M&W 8th ed. base (Whitebait, raw). Uncoated uncooked juvenile sprats/herring; values match blanched but lower salt.',
			],
			[
				'name' => 'Bahamas Lobster Tails', 'slug' => 'bahamas-lobster-tails', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tail (150g)', 'grams' => 150] ],
				'energy_kcal' => 112, 'energy_kj' => 469,
				'protein_g' => 20.6, 'carbohydrate_g' => 2.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.85,
				'omega3_total_mg' => 350, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 170,
				'source_notes' => 'USDA FDC #175181 base (Panulirus argus, Caribbean/Bahamas spiny lobster, cooked). Same profile as spiny lobster tail.',
			],
			[
				'name' => 'Tobiko (Black)', 'slug' => 'tobiko-black', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 225, 'energy_kj' => 942,
				'protein_g' => 31.0, 'carbohydrate_g' => 7.0, 'of_which_sugars_g' => 2.0,
				'fat_g' => 7.5, 'of_which_saturates_g' => 1.6, 'fibre_g' => 0.0, 'salt_g' => 3.50,
				'omega3_total_mg' => 2100, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 780, 'omega3_dha_mg' => 1150,
				'source_notes' => 'Flying fish roe (Cypselurus agoo) dyed black with squid ink. Same nutritional base as orange tobiko.',
			],
			[
				'name' => 'Masago (Orange)', 'slug' => 'masago-orange', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 195, 'energy_kj' => 816,
				'protein_g' => 26.5, 'carbohydrate_g' => 8.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 3.20,
				'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,
				'source_notes' => 'Published data (capelin roe, Mallotus villosus, natural orange). Smaller and less firm than tobiko; slightly sweeter.',
			],
			[
				'name' => 'Masago (Black)', 'slug' => 'masago-black', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 tbsp (16g)', 'grams' => 16] ],
				'energy_kcal' => 195, 'energy_kj' => 816,
				'protein_g' => 26.5, 'carbohydrate_g' => 8.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 3.20,
				'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 900,
				'source_notes' => 'Capelin roe (Mallotus villosus) dyed black with squid ink. Nutritional profile identical to orange masago.',
			],
			[
				'name' => 'Sushi Ebi (cooked prawn)', 'slug' => 'sushi-ebi', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 pieces (50g)', 'grams' => 50] ],
				'energy_kcal' => 99, 'energy_kj' => 414,
				'protein_g' => 22.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.70,
				'source_notes' => 'M&W 8th ed. (Prawns, cooked). Vannamei/tiger prawn, butterflied and poached for nigiri/sushi. Omega-3 left NULL.',
			],
			[
				'name' => 'Snow Crab Meat', 'slug' => 'snow-crab-meat', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 1.10,
				'omega3_total_mg' => 700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 300, 'omega3_dha_mg' => 350,
				'source_notes' => 'USDA FDC #174209 (Chionoecetes opilio, cooked). Cluster/leg meat; sweet flavour, lower fat than brown crab.',
			],
			[
				'name' => 'Pouting / Bib (raw)', 'slug' => 'pouting-bib', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (120g)', 'grams' => 120] ],
				'energy_kcal' => 75, 'energy_kj' => 314,
				'protein_g' => 17.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'source_notes' => 'M&W 8th ed. equivalent (Trisopterus luscus, raw). UK inshore gadoid similar to whiting; very lean, underutilised. Omega-3 left NULL.',
			],
			[
				'name' => 'Black Sea Bream (raw)', 'slug' => 'black-sea-bream', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (150g)', 'grams' => 150] ],
				'energy_kcal' => 96, 'energy_kj' => 402,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.7, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 640, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 180, 'omega3_dha_mg' => 420,
				'source_notes' => 'Published data (Spondyliosoma cantharus, raw). UK inshore species; slightly fattier than gilt-head sea bream.',
			],
			[
				'name' => 'Garfish (raw)', 'slug' => 'garfish', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 fillet (100g)', 'grams' => 100] ],
				'energy_kcal' => 84, 'energy_kj' => 352,
				'protein_g' => 18.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 420, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 280,
				'source_notes' => 'Published data (Belone belone, raw). Flesh is naturally green-tinged due to biliverdin; safe and delicious. Lean spring fish.',
			],
			[
				'name' => 'Smelt (raw)', 'slug' => 'smelt', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '4 fish (100g)', 'grams' => 100] ],
				'energy_kcal' => 97, 'energy_kj' => 406,
				'protein_g' => 17.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.4, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.18,
				'omega3_total_mg' => 650, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 220, 'omega3_dha_mg' => 400,
				'source_notes' => 'USDA FDC #175161 (Smelt, rainbow, raw). European smelt (Osmerus eperlanus) and rainbow smelt have near-identical profiles.',
			],
			[
				'name' => 'Pilchards (Cornish, raw)', 'slug' => 'pilchards-cornish', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '2 pilchards (120g)', 'grams' => 120] ],
				'energy_kcal' => 139, 'energy_kj' => 582,
				'protein_g' => 20.2, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.5, 'of_which_saturates_g' => 1.7, 'fibre_g' => 0.0, 'salt_g' => 0.15,
				'omega3_total_mg' => 1900, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 670, 'omega3_dha_mg' => 1080,
				'source_notes' => 'M&W 8th ed. / Published data (Sardina pilchardus, raw). Adult sardine; Cornish-landed pilchards same profile as Mediterranean sardine.',
			],
			[
				'name' => 'Albacore Tuna (raw)', 'slug' => 'albacore-tuna', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 144, 'energy_kj' => 602,
				'protein_g' => 23.1, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.9, 'of_which_saturates_g' => 1.3, 'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 1280, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 300, 'omega3_dha_mg' => 890,
				'source_notes' => 'USDA FDC #175167 (Thunnus alalunga, raw). White tuna; higher fat and omega-3 than skipjack or yellowfin.',
			],
			[
				'name' => 'Yellowfin Tuna (raw)', 'slug' => 'yellowfin-tuna', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 109, 'energy_kj' => 456,
				'protein_g' => 23.4, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.0, 'of_which_saturates_g' => 0.3, 'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 360, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 90, 'omega3_dha_mg' => 220,
				'source_notes' => 'USDA FDC #175168 (Thunnus albacares, raw). Lean, mild-flavoured tuna; lower omega-3 than albacore or bluefin.',
			],

			// --- Additional seafood (seed v11) ---

			[
				'name' => 'Wahoo (raw)', 'slug' => 'wahoo', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 steak (150g)', 'grams' => 150] ],
				'energy_kcal' => 109, 'energy_kj' => 456,
				'protein_g' => 21.6, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.2, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.12,
				'omega3_total_mg' => 490, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 130, 'omega3_dha_mg' => 320,
				'source_notes' => 'USDA FDC equivalent (Acanthocybium solandri, raw). Firm-fleshed pelagic fish; popular in Atlantic and Pacific sport fishing.',
			],
			[
				'name' => 'Scallops (Queen Meat, raw)', 'slug' => 'scallops-queen-meat', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 86, 'energy_kj' => 360,
				'protein_g' => 17.0, 'carbohydrate_g' => 2.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'omega3_total_mg' => 370, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 140, 'omega3_dha_mg' => 200,
				'source_notes' => 'Published data (Aequipecten opercularis, raw). Smaller and sweeter than king scallops; no roe in commercial supply.',
			],
			[
				'name' => 'Mussel Meat (Shucked, raw)', 'slug' => 'mussel-meat-shucked', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 74, 'energy_kj' => 310,
				'protein_g' => 11.9, 'carbohydrate_g' => 3.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.8, 'of_which_saturates_g' => 0.4, 'fibre_g' => 0.0, 'salt_g' => 0.60,
				'omega3_total_mg' => 700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 260, 'omega3_dha_mg' => 400,
				'source_notes' => 'M&W 8th ed. (Mussels, raw). Shucked/prepared weight (no shell); same composition as in-shell cooked but raw.',
			],
			[
				'name' => 'Goose Barnacles (Percebes)', 'slug' => 'goose-barnacles-percebes', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 120, 'energy_kj' => 502,
				'protein_g' => 20.0, 'carbohydrate_g' => 2.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 3.0, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'omega3_total_mg' => 550, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 200, 'omega3_dha_mg' => 300,
				'source_notes' => 'Published data (Pollicipes pollicipes, cooked). Values per 100g edible peduncle meat; shell/plate discarded. Atlantic/Iberian delicacy.',
			],
			[
				'name' => 'Mantis Shrimp (cooked)', 'slug' => 'mantis-shrimp', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (100g)', 'grams' => 100] ],
				'energy_kcal' => 90, 'energy_kj' => 377,
				'protein_g' => 19.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 430, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 150, 'omega3_dha_mg' => 250,
				'source_notes' => 'Published data (Squilla mantis / Stomatopoda, cooked). Mediterranean stomatopod; values per 100g edible tail meat.',
			],
			[
				'name' => 'Smoked Eel', 'slug' => 'smoked-eel', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 290, 'energy_kj' => 1213,
				'protein_g' => 23.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 22.0, 'of_which_saturates_g' => 4.5, 'fibre_g' => 0.0, 'salt_g' => 2.20,
				'omega3_total_mg' => 1600, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 600, 'omega3_dha_mg' => 850,
				'source_notes' => 'M&W 8th ed. (Eel, smoked). Hot-smoked European eel; very rich; high salt from brining before smoking.',
			],
			[
				'name' => 'Smoked Sprats', 'slug' => 'smoked-sprats', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (50g)', 'grams' => 50] ],
				'energy_kcal' => 285, 'energy_kj' => 1193,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 23.0, 'of_which_saturates_g' => 5.4, 'fibre_g' => 0.0, 'salt_g' => 2.80,
				'omega3_total_mg' => 2700, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 1100, 'omega3_dha_mg' => 1400,
				'source_notes' => 'M&W 8th ed. (Sprats, smoked). Very high fat from both the fish and smoking process; exceptional omega-3 density.',
			],
			[
				'name' => 'Smoked Halibut', 'slug' => 'smoked-halibut', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '3 slices (60g)', 'grams' => 60] ],
				'energy_kcal' => 136, 'energy_kj' => 569,
				'protein_g' => 20.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 6.2, 'of_which_saturates_g' => 1.4, 'fibre_g' => 0.0, 'salt_g' => 2.50,
				'omega3_total_mg' => 980, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 350, 'omega3_dha_mg' => 580,
				'source_notes' => 'Published data (Hippoglossus hippoglossus, cold-smoked). Milder and fattier than smoked haddock; sliced and served cold.',
			],
			[
				'name' => 'Herring Roe (Soft, raw)', 'slug' => 'herring-roe-soft', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 92, 'energy_kj' => 385,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.5, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.20,
				'omega3_total_mg' => 680, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 250, 'omega3_dha_mg' => 380,
				'source_notes' => 'M&W 8th ed. (Herring, soft roe/milt, raw). Sperm sac (milt); distinct from hard roe. Often pan-fried on toast.',
			],
			[
				'name' => 'Fish Pie Mix (raw)', 'slug' => 'fish-pie-mix', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (150g)', 'grams' => 150] ],
				'energy_kcal' => 97, 'energy_kj' => 406,
				'protein_g' => 18.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 2.8, 'of_which_saturates_g' => 0.6, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'omega3_total_mg' => 630, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 200, 'omega3_dha_mg' => 380,
				'source_notes' => 'Estimated average of salmon, cod and smoked haddock (typical UK retail mix). Omega-3 weighted average across components.',
			],
			[
				'name' => 'Prawn Cocktail', 'slug' => 'prawn-cocktail', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 starter (150g)', 'grams' => 150] ],
				'energy_kcal' => 140, 'energy_kj' => 586,
				'protein_g' => 11.0, 'carbohydrate_g' => 6.0, 'of_which_sugars_g' => 5.5,
				'fat_g' => 8.0, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.3, 'salt_g' => 1.20,
				'source_notes' => 'Estimated (cooked prawns + Marie Rose / thousand island sauce on lettuce). Fat from mayo base; sugars from ketchup component. Omega-3 left NULL.',
			],
			[
				'name' => 'Dressed Lobster', 'slug' => 'dressed-lobster', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '½ lobster (200g)', 'grams' => 200] ],
				'energy_kcal' => 160, 'energy_kj' => 670,
				'protein_g' => 17.5, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 10.0, 'of_which_saturates_g' => 1.5, 'fibre_g' => 0.0, 'salt_g' => 1.50,
				'omega3_total_mg' => 280, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 120, 'omega3_dha_mg' => 130,
				'source_notes' => 'Estimated: cooked lobster served in shell with mayonnaise dressing. Fat and salt elevated by mayo component.',
			],
			[
				'name' => 'Marinated Anchovies (Boquerones)', 'slug' => 'marinated-anchovies-boquerones', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '6 fillets (50g)', 'grams' => 50] ],
				'energy_kcal' => 110, 'energy_kj' => 460,
				'protein_g' => 17.0, 'carbohydrate_g' => 0.5, 'of_which_sugars_g' => 0.0,
				'fat_g' => 4.0, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 1.00,
				'omega3_total_mg' => 1400, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 550, 'omega3_dha_mg' => 750,
				'source_notes' => 'Published data (Engraulis encrasicolus, vinegar/oil cured). White anchovies; much lower salt than salt-packed; mild, firm texture.',
			],
			[
				'name' => 'Mussels (Smoked, in Oil)', 'slug' => 'mussels-smoked-oil', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '½ tin (60g)', 'grams' => 60] ],
				'energy_kcal' => 200, 'energy_kj' => 837,
				'protein_g' => 20.0, 'carbohydrate_g' => 3.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 11.5, 'of_which_saturates_g' => 2.2, 'fibre_g' => 0.0, 'salt_g' => 1.80,
				'omega3_total_mg' => 1200, 'omega3_ala_mg' => null, 'omega3_epa_mg' => 450, 'omega3_dha_mg' => 680,
				'source_notes' => 'Published data (Mytilus edulis, hot-smoked, in sunflower oil). Canned; fat elevated by oil packing.',
			],
			[
				'name' => 'Carrageen (Irish Moss, fresh)', 'slug' => 'carrageen-irish-moss', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion (30g)', 'grams' => 30] ],
				'energy_kcal' => 30, 'energy_kj' => 126,
				'protein_g' => 1.5, 'carbohydrate_g' => 5.0, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 2.0, 'salt_g' => 1.80,
				'source_notes' => 'Published data (Chondrus crispus, fresh/reconstituted). Red seaweed used as natural thickener in puddings and jellies. Omega-3 left NULL.',
			],
			[
				'name' => 'Hijiki (dried)', 'slug' => 'hijiki', 'category_id' => $fs,
				'serving_sizes' => [ ['label' => '1 portion dry (10g)', 'grams' => 10] ],
				'energy_kcal' => 150, 'energy_kj' => 628,
				'protein_g' => 10.6, 'carbohydrate_g' => 14.4, 'of_which_sugars_g' => 0.0,
				'fat_g' => 1.3, 'of_which_saturates_g' => 0.3, 'fibre_g' => 43.3, 'salt_g' => 4.60,
				'source_notes' => 'USDA FDC #168456 (Seaweed, hijiki, raw). Values per 100g dry. Note: FSA advises against consumption due to high inorganic arsenic content. Omega-3 left NULL.',
			],

			// =================================================================
			// DAIRY & EGGS (12 foods)
			// =================================================================

			[
				'name' => 'Whole Milk', 'slug' => 'whole-milk', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 glass (200ml)', 'grams' => 206], ['label' => '1 mug (250ml)', 'grams' => 258], ['label' => '1 tbsp (15ml)', 'grams' => 15] ],
				'energy_kcal' => 61, 'energy_kj' => 255,
				'protein_g' => 3.2, 'carbohydrate_g' => 4.8, 'of_which_sugars_g' => 4.8,
				'fat_g' => 3.3, 'of_which_saturates_g' => 2.1, 'fibre_g' => 0.0, 'salt_g' => 0.13,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Semi-Skimmed Milk', 'slug' => 'semi-skimmed-milk', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 glass (200ml)', 'grams' => 206], ['label' => '1 mug (250ml)', 'grams' => 258] ],
				'energy_kcal' => 46, 'energy_kj' => 195,
				'protein_g' => 3.3, 'carbohydrate_g' => 5.0, 'of_which_sugars_g' => 5.0,
				'fat_g' => 1.6, 'of_which_saturates_g' => 1.0, 'fibre_g' => 0.0, 'salt_g' => 0.13,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Skimmed Milk', 'slug' => 'skimmed-milk', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 glass (200ml)', 'grams' => 206] ],
				'energy_kcal' => 32, 'energy_kj' => 136,
				'protein_g' => 3.3, 'carbohydrate_g' => 4.8, 'of_which_sugars_g' => 4.8,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.0, 'salt_g' => 0.13,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Cheddar Cheese', 'slug' => 'cheddar-cheese', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 matchbox portion (30g)', 'grams' => 30], ['label' => '1 slice (20g)', 'grams' => 20] ],
				'energy_kcal' => 412, 'energy_kj' => 1712,
				'protein_g' => 25.4, 'carbohydrate_g' => 0.1, 'of_which_sugars_g' => 0.1,
				'fat_g' => 34.4, 'of_which_saturates_g' => 21.7, 'fibre_g' => 0.0, 'salt_g' => 1.80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Brie', 'slug' => 'brie', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 wedge (30g)', 'grams' => 30] ],
				'energy_kcal' => 319, 'energy_kj' => 1323,
				'protein_g' => 19.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 26.9, 'of_which_saturates_g' => 16.9, 'fibre_g' => 0.0, 'salt_g' => 1.78,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Greek Yogurt (full-fat)', 'slug' => 'greek-yogurt-full-fat', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 small pot (150g)', 'grams' => 150], ['label' => '1 large pot (200g)', 'grams' => 200] ],
				'energy_kcal' => 133, 'energy_kj' => 556,
				'protein_g' => 5.7, 'carbohydrate_g' => 4.0, 'of_which_sugars_g' => 4.0,
				'fat_g' => 10.3, 'of_which_saturates_g' => 7.1, 'fibre_g' => 0.0, 'salt_g' => 0.10,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Plain Low-fat Yogurt', 'slug' => 'plain-low-fat-yogurt', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 small pot (125g)', 'grams' => 125] ],
				'energy_kcal' => 56, 'energy_kj' => 236,
				'protein_g' => 4.8, 'carbohydrate_g' => 7.4, 'of_which_sugars_g' => 7.4,
				'fat_g' => 0.8, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.0, 'salt_g' => 0.17,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Butter (salted)', 'slug' => 'butter-salted', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 tbsp (14g)', 'grams' => 14], ['label' => '1 tsp (5g)', 'grams' => 5] ],
				'energy_kcal' => 744, 'energy_kj' => 3031,
				'protein_g' => 0.6, 'carbohydrate_g' => 0.6, 'of_which_sugars_g' => 0.6,
				'fat_g' => 82.2, 'of_which_saturates_g' => 54.0, 'fibre_g' => 0.0, 'salt_g' => 1.65,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Double Cream', 'slug' => 'double-cream', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 tbsp (15ml)', 'grams' => 15], ['label' => '2 tbsp (30ml)', 'grams' => 30] ],
				'energy_kcal' => 449, 'energy_kj' => 1849,
				'protein_g' => 1.7, 'carbohydrate_g' => 2.7, 'of_which_sugars_g' => 2.7,
				'fat_g' => 48.0, 'of_which_saturates_g' => 30.1, 'fibre_g' => 0.0, 'salt_g' => 0.05,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Egg (whole, raw)', 'slug' => 'egg-whole-raw', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 medium egg (58g)', 'grams' => 58], ['label' => '1 large egg (68g)', 'grams' => 68] ],
				'energy_kcal' => 147, 'energy_kj' => 612,
				'protein_g' => 12.5, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 10.8, 'of_which_saturates_g' => 3.1, 'fibre_g' => 0.0, 'salt_g' => 0.40,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Egg White (raw)', 'slug' => 'egg-white-raw', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 egg white (33g)', 'grams' => 33] ],
				'energy_kcal' => 52, 'energy_kj' => 217,
				'protein_g' => 10.9, 'carbohydrate_g' => 0.7, 'of_which_sugars_g' => 0.7,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.50,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Cottage Cheese', 'slug' => 'cottage-cheese', 'category_id' => $de,
				'serving_sizes' => [ ['label' => '1 tbsp (30g)', 'grams' => 30], ['label' => '½ pot (115g)', 'grams' => 115] ],
				'energy_kcal' => 98, 'energy_kj' => 410,
				'protein_g' => 11.1, 'carbohydrate_g' => 3.1, 'of_which_sugars_g' => 3.0,
				'fat_g' => 4.3, 'of_which_saturates_g' => 2.8, 'fibre_g' => 0.0, 'salt_g' => 0.80,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// BREAD & CEREALS (10 foods)
			// =================================================================

			[
				'name' => 'White Bread', 'slug' => 'white-bread', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 medium slice (38g)', 'grams' => 38], ['label' => '1 thick slice (44g)', 'grams' => 44] ],
				'energy_kcal' => 235, 'energy_kj' => 996,
				'protein_g' => 7.9, 'carbohydrate_g' => 49.3, 'of_which_sugars_g' => 3.4,
				'fat_g' => 1.6, 'of_which_saturates_g' => 0.4, 'fibre_g' => 1.6, 'salt_g' => 1.08,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Wholemeal Bread', 'slug' => 'wholemeal-bread', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 medium slice (38g)', 'grams' => 38] ],
				'energy_kcal' => 217, 'energy_kj' => 920,
				'protein_g' => 9.4, 'carbohydrate_g' => 41.6, 'of_which_sugars_g' => 3.4,
				'fat_g' => 2.7, 'of_which_saturates_g' => 0.5, 'fibre_g' => 5.0, 'salt_g' => 0.98,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Sourdough Bread', 'slug' => 'sourdough-bread', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 slice (50g)', 'grams' => 50] ],
				'energy_kcal' => 247, 'energy_kj' => 1042,
				'protein_g' => 8.8, 'carbohydrate_g' => 48.8, 'of_which_sugars_g' => 1.5,
				'fat_g' => 1.2, 'of_which_saturates_g' => 0.3, 'fibre_g' => 2.2, 'salt_g' => 1.20,
				'source_notes' => 'USDA FDC #174924 (Bread, sourdough)',
			],
			[
				'name' => 'Bagel (plain)', 'slug' => 'bagel-plain', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 bagel (98g)', 'grams' => 98] ],
				'energy_kcal' => 271, 'energy_kj' => 1138,
				'protein_g' => 10.0, 'carbohydrate_g' => 55.7, 'of_which_sugars_g' => 6.4,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.2, 'fibre_g' => 2.5, 'salt_g' => 0.94,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Croissant', 'slug' => 'croissant', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 croissant (60g)', 'grams' => 60] ],
				'energy_kcal' => 406, 'energy_kj' => 1699,
				'protein_g' => 8.3, 'carbohydrate_g' => 45.8, 'of_which_sugars_g' => 10.5,
				'fat_g' => 21.0, 'of_which_saturates_g' => 11.5, 'fibre_g' => 1.8, 'salt_g' => 1.40,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Cornflakes', 'slug' => 'cornflakes', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 bowl (30g)', 'grams' => 30] ],
				'energy_kcal' => 357, 'energy_kj' => 1520,
				'protein_g' => 7.2, 'carbohydrate_g' => 83.7, 'of_which_sugars_g' => 7.5,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.8, 'salt_g' => 2.39,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Porridge Oats (dry)', 'slug' => 'porridge-oats-dry', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 serving dry (40g)', 'grams' => 40] ],
				'energy_kcal' => 364, 'energy_kj' => 1535,
				'protein_g' => 11.2, 'carbohydrate_g' => 60.4, 'of_which_sugars_g' => 1.1,
				'fat_g' => 8.1, 'of_which_saturates_g' => 1.4, 'fibre_g' => 8.0, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Bran Flakes', 'slug' => 'bran-flakes', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 bowl (30g)', 'grams' => 30] ],
				'energy_kcal' => 318, 'energy_kj' => 1347,
				'protein_g' => 10.2, 'carbohydrate_g' => 64.5, 'of_which_sugars_g' => 19.4,
				'fat_g' => 2.6, 'of_which_saturates_g' => 0.6, 'fibre_g' => 14.0, 'salt_g' => 1.80,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'White Rice (cooked)', 'slug' => 'white-rice-cooked', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 portion (180g)', 'grams' => 180] ],
				'energy_kcal' => 130, 'energy_kj' => 544,
				'protein_g' => 2.7, 'carbohydrate_g' => 30.0, 'of_which_sugars_g' => 0.1,
				'fat_g' => 0.3, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.4, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Pasta (white, cooked)', 'slug' => 'pasta-white-cooked', 'category_id' => $bc,
				'serving_sizes' => [ ['label' => '1 portion (180g)', 'grams' => 180] ],
				'energy_kcal' => 148, 'energy_kj' => 625,
				'protein_g' => 5.0, 'carbohydrate_g' => 30.9, 'of_which_sugars_g' => 0.6,
				'fat_g' => 0.9, 'of_which_saturates_g' => 0.1, 'fibre_g' => 1.8, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// NUTS & SEEDS (8 foods — ALA-rich nuts carry Omega-3)
			// =================================================================

			[
				'name' => 'Walnuts', 'slug' => 'walnuts', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '6 walnut halves (30g)', 'grams' => 30] ],
				'energy_kcal' => 654, 'energy_kj' => 2724,
				'protein_g' => 15.2, 'carbohydrate_g' => 13.7, 'of_which_sugars_g' => 2.6,
				'fat_g' => 65.2, 'of_which_saturates_g' => 6.1, 'fibre_g' => 6.7, 'salt_g' => 0.0,
				'omega3_total_mg' => 9079, 'omega3_ala_mg' => 9079, 'omega3_epa_mg' => null, 'omega3_dha_mg' => null,
				'source_notes' => 'M&W 8th ed.; Omega-3 ALA: USDA FDC #170187 (Walnuts, English)',
			],
			[
				'name' => 'Flaxseed / Linseed (whole)', 'slug' => 'flaxseed-whole', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 tbsp (10g)', 'grams' => 10] ],
				'energy_kcal' => 534, 'energy_kj' => 2234,
				'protein_g' => 18.3, 'carbohydrate_g' => 28.9, 'of_which_sugars_g' => 1.6,
				'fat_g' => 42.2, 'of_which_saturates_g' => 3.7, 'fibre_g' => 27.3, 'salt_g' => 0.05,
				'omega3_total_mg' => 22813, 'omega3_ala_mg' => 22813, 'omega3_epa_mg' => null, 'omega3_dha_mg' => null,
				'source_notes' => 'M&W 8th ed.; Omega-3 ALA: USDA FDC #169414 (Seeds, flaxseed)',
			],
			[
				'name' => 'Chia Seeds', 'slug' => 'chia-seeds', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 tbsp (12g)', 'grams' => 12] ],
				'energy_kcal' => 486, 'energy_kj' => 2035,
				'protein_g' => 16.5, 'carbohydrate_g' => 42.1, 'of_which_sugars_g' => 0.0,
				'fat_g' => 30.7, 'of_which_saturates_g' => 3.3, 'fibre_g' => 34.4, 'salt_g' => 0.02,
				'omega3_total_mg' => 17552, 'omega3_ala_mg' => 17552, 'omega3_epa_mg' => null, 'omega3_dha_mg' => null,
				'source_notes' => 'USDA FDC #170554 (Seeds, chia seeds, dried)',
			],
			[
				'name' => 'Almonds', 'slug' => 'almonds', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 small handful (23g)', 'grams' => 23] ],
				'energy_kcal' => 579, 'energy_kj' => 2423,
				'protein_g' => 21.2, 'carbohydrate_g' => 21.7, 'of_which_sugars_g' => 4.4,
				'fat_g' => 49.9, 'of_which_saturates_g' => 3.8, 'fibre_g' => 12.5, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed. Almonds are primarily monounsaturated; omega-3 ALA is present but at very low levels not separately verified; left NULL.',
			],
			[
				'name' => 'Pumpkin Seeds', 'slug' => 'pumpkin-seeds', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 tbsp (10g)', 'grams' => 10] ],
				'energy_kcal' => 559, 'energy_kj' => 2334,
				'protein_g' => 30.2, 'carbohydrate_g' => 10.7, 'of_which_sugars_g' => 1.4,
				'fat_g' => 49.1, 'of_which_saturates_g' => 8.7, 'fibre_g' => 6.0, 'salt_g' => 0.02,
				'source_notes' => 'M&W 8th ed. Omega-3 in pumpkin seeds is very low and inconsistently reported; left NULL.',
			],
			[
				'name' => 'Sunflower Seeds', 'slug' => 'sunflower-seeds', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 tbsp (10g)', 'grams' => 10] ],
				'energy_kcal' => 584, 'energy_kj' => 2445,
				'protein_g' => 20.8, 'carbohydrate_g' => 20.0, 'of_which_sugars_g' => 2.6,
				'fat_g' => 51.5, 'of_which_saturates_g' => 4.5, 'fibre_g' => 8.6, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed. Sunflower seeds are predominantly omega-6 (linoleic acid); omega-3 negligible; left NULL.',
			],
			[
				'name' => 'Brazil Nuts', 'slug' => 'brazil-nuts', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '6 nuts (30g)', 'grams' => 30] ],
				'energy_kcal' => 659, 'energy_kj' => 2743,
				'protein_g' => 14.3, 'carbohydrate_g' => 12.3, 'of_which_sugars_g' => 2.3,
				'fat_g' => 67.1, 'of_which_saturates_g' => 16.2, 'fibre_g' => 7.5, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Cashews (unsalted)', 'slug' => 'cashews-unsalted', 'category_id' => $ns,
				'serving_sizes' => [ ['label' => '1 small handful (30g)', 'grams' => 30] ],
				'energy_kcal' => 553, 'energy_kj' => 2314,
				'protein_g' => 18.2, 'carbohydrate_g' => 30.2, 'of_which_sugars_g' => 5.9,
				'fat_g' => 43.9, 'of_which_saturates_g' => 7.8, 'fibre_g' => 3.3, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// FATS & OILS (5 foods)
			// =================================================================

			[
				'name' => 'Olive Oil', 'slug' => 'olive-oil', 'category_id' => $fo,
				'serving_sizes' => [ ['label' => '1 tbsp (14g)', 'grams' => 14], ['label' => '1 tsp (5g)', 'grams' => 5] ],
				'energy_kcal' => 824, 'energy_kj' => 3389,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 91.6, 'of_which_saturates_g' => 13.5, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed. Olive oil is predominantly oleic acid (monounsaturated); omega-3 ALA is trace-level only, not verified separately; left NULL.',
			],
			[
				'name' => 'Rapeseed Oil (Canola)', 'slug' => 'rapeseed-oil', 'category_id' => $fo,
				'serving_sizes' => [ ['label' => '1 tbsp (14g)', 'grams' => 14], ['label' => '1 tsp (5g)', 'grams' => 5] ],
				'energy_kcal' => 884, 'energy_kj' => 3699,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 100.0, 'of_which_saturates_g' => 7.4, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'omega3_total_mg' => 9137, 'omega3_ala_mg' => 9137, 'omega3_epa_mg' => null, 'omega3_dha_mg' => null,
				'source_notes' => 'M&W 8th ed.; Omega-3 ALA: USDA FDC #172336 (Oil, canola)',
			],
			[
				'name' => 'Sunflower Oil', 'slug' => 'sunflower-oil', 'category_id' => $fo,
				'serving_sizes' => [ ['label' => '1 tbsp (14g)', 'grams' => 14] ],
				'energy_kcal' => 884, 'energy_kj' => 3699,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 100.0, 'of_which_saturates_g' => 10.1, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed. Predominantly omega-6 (linoleic); omega-3 negligible; left NULL.',
			],
			[
				'name' => 'Coconut Oil', 'slug' => 'coconut-oil', 'category_id' => $fo,
				'serving_sizes' => [ ['label' => '1 tbsp (14g)', 'grams' => 14] ],
				'energy_kcal' => 862, 'energy_kj' => 3606,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 99.1, 'of_which_saturates_g' => 85.2, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'source_notes' => 'USDA FDC #172336 equivalent; no omega-3; left NULL.',
			],
			[
				'name' => 'Lard', 'slug' => 'lard', 'category_id' => $fo,
				'serving_sizes' => [ ['label' => '1 tbsp (12g)', 'grams' => 12] ],
				'energy_kcal' => 891, 'energy_kj' => 3729,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 99.0, 'of_which_saturates_g' => 39.2, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed.',
			],

			// =================================================================
			// DRINKS (10 foods — caffeine for coffee/tea/energy drinks/cola)
			// Values per 100ml.
			// =================================================================

			[
				'name' => 'Coffee, filter/brewed (black)', 'slug' => 'coffee-filter-black', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 mug (240ml)', 'grams' => 240], ['label' => '1 cup (200ml)', 'grams' => 200] ],
				'energy_kcal' => 2, 'energy_kj' => 8,
				'protein_g' => 0.3, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 40,
				'source_notes' => 'M&W 8th ed.; Caffeine: EFSA 2015 Scientific Opinion on caffeine — 40mg/100ml typical for filter coffee',
			],
			[
				'name' => 'Espresso', 'slug' => 'espresso', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 shot (30ml)', 'grams' => 30], ['label' => 'Double shot (60ml)', 'grams' => 60] ],
				'energy_kcal' => 9, 'energy_kj' => 37,
				'protein_g' => 0.6, 'carbohydrate_g' => 1.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 212,
				'source_notes' => 'USDA FDC #174165; Caffeine 212mg/100ml: EFSA 2015 (espresso, approx. 63mg per 30ml shot)',
			],
			[
				'name' => 'Instant Coffee (made up with water)', 'slug' => 'instant-coffee-made-up', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 mug (240ml)', 'grams' => 240] ],
				'energy_kcal' => 5, 'energy_kj' => 21,
				'protein_g' => 0.6, 'carbohydrate_g' => 0.6, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 60,
				'source_notes' => 'M&W 8th ed.; Caffeine: EFSA 2015 — range 30–90mg/100ml for instant; 60mg/100ml used as central estimate',
			],
			[
				'name' => 'Tea (black, brewed)', 'slug' => 'tea-black-brewed', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 mug (240ml)', 'grams' => 240], ['label' => '1 cup (200ml)', 'grams' => 200] ],
				'energy_kcal' => 1, 'energy_kj' => 4,
				'protein_g' => 0.1, 'carbohydrate_g' => 0.3, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 20,
				'source_notes' => 'M&W 8th ed.; Caffeine: EFSA 2015 — 20mg/100ml for black brewed tea',
			],
			[
				'name' => 'Green Tea (brewed)', 'slug' => 'green-tea-brewed', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 cup (200ml)', 'grams' => 200] ],
				'energy_kcal' => 1, 'energy_kj' => 4,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.2, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 12,
				'source_notes' => 'USDA FDC #174765; Caffeine: EFSA 2015 — approx. 12mg/100ml for green tea',
			],
			[
				'name' => 'Cola (regular)', 'slug' => 'cola-regular', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 can (330ml)', 'grams' => 330], ['label' => '1 glass (250ml)', 'grams' => 250] ],
				'energy_kcal' => 42, 'energy_kj' => 176,
				'protein_g' => 0.0, 'carbohydrate_g' => 10.6, 'of_which_sugars_g' => 10.6,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'caffeine_mg' => 10,
				'source_notes' => 'M&W 8th ed.; Caffeine: Coca-Cola UK published data — approx. 10mg/100ml',
			],
			[
				'name' => 'Energy Drink (e.g. Red Bull)', 'slug' => 'energy-drink-red-bull', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 can (250ml)', 'grams' => 250] ],
				'energy_kcal' => 45, 'energy_kj' => 192,
				'protein_g' => 0.0, 'carbohydrate_g' => 11.3, 'of_which_sugars_g' => 11.3,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.05,
				'caffeine_mg' => 32,
				'source_notes' => 'Red Bull UK label data: 80mg caffeine per 250ml can = 32mg/100ml',
			],
			[
				'name' => 'Orange Juice (fresh)', 'slug' => 'orange-juice-fresh', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 glass (150ml)', 'grams' => 150] ],
				'energy_kcal' => 45, 'energy_kj' => 190,
				'protein_g' => 0.8, 'carbohydrate_g' => 10.4, 'of_which_sugars_g' => 8.8,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.2, 'salt_g' => 0.0,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Oat Milk (unsweetened)', 'slug' => 'oat-milk-unsweetened', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 glass (200ml)', 'grams' => 200] ],
				'energy_kcal' => 46, 'energy_kj' => 193,
				'protein_g' => 1.0, 'carbohydrate_g' => 6.5, 'of_which_sugars_g' => 4.0,
				'fat_g' => 1.5, 'of_which_saturates_g' => 0.2, 'fibre_g' => 0.8, 'salt_g' => 0.13,
				'source_notes' => 'Oatly UK label data (unsweetened barista edition as reference)',
			],
			[
				'name' => 'Water (still)', 'slug' => 'water-still', 'category_id' => $dr,
				'serving_sizes' => [ ['label' => '1 glass (250ml)', 'grams' => 250], ['label' => '500ml bottle', 'grams' => 500] ],
				'energy_kcal' => 0, 'energy_kj' => 0,
				'protein_g' => 0.0, 'carbohydrate_g' => 0.0, 'of_which_sugars_g' => 0.0,
				'fat_g' => 0.0, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.0, 'salt_g' => 0.0,
				'source_notes' => 'Water: zero nutrients by definition.',
			],

			// =================================================================
			// SNACKS & CONFECTIONERY (10 foods — dark/milk chocolate carry caffeine)
			// =================================================================

			[
				'name' => 'Dark Chocolate (70-85% cocoa)', 'slug' => 'dark-chocolate-70', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '2 squares (20g)', 'grams' => 20], ['label' => '1 small bar (50g)', 'grams' => 50] ],
				'energy_kcal' => 600, 'energy_kj' => 2490,
				'protein_g' => 7.8, 'carbohydrate_g' => 45.9, 'of_which_sugars_g' => 23.9,
				'fat_g' => 42.6, 'of_which_saturates_g' => 24.5, 'fibre_g' => 10.9, 'salt_g' => 0.02,
				'caffeine_mg' => 80,
				'source_notes' => 'USDA FDC #170273 (Chocolate, dark, 70-85% cacao solids); Caffeine: USDA FDC — 80mg/100g typical for 70-85% dark chocolate',
			],
			[
				'name' => 'Milk Chocolate', 'slug' => 'milk-chocolate', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '2 squares (20g)', 'grams' => 20], ['label' => '1 standard bar (45g)', 'grams' => 45] ],
				'energy_kcal' => 535, 'energy_kj' => 2240,
				'protein_g' => 7.7, 'carbohydrate_g' => 59.4, 'of_which_sugars_g' => 51.5,
				'fat_g' => 29.7, 'of_which_saturates_g' => 17.8, 'fibre_g' => 1.5, 'salt_g' => 0.23,
				'caffeine_mg' => 20,
				'source_notes' => 'M&W 8th ed.; Caffeine: USDA FDC — approx. 20mg/100g for milk chocolate',
			],
			[
				'name' => 'White Chocolate', 'slug' => 'white-chocolate', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '2 squares (20g)', 'grams' => 20] ],
				'energy_kcal' => 539, 'energy_kj' => 2255,
				'protein_g' => 5.9, 'carbohydrate_g' => 59.6, 'of_which_sugars_g' => 59.6,
				'fat_g' => 32.1, 'of_which_saturates_g' => 19.4, 'fibre_g' => 0.0, 'salt_g' => 0.25,
				'source_notes' => 'M&W 8th ed. White chocolate contains no cocoa solids; caffeine content is negligible; left NULL.',
			],
			[
				'name' => 'Crisps / Potato Chips (plain)', 'slug' => 'crisps-plain', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 bag (25g)', 'grams' => 25], ['label' => '1 large bag (40g)', 'grams' => 40] ],
				'energy_kcal' => 536, 'energy_kj' => 2244,
				'protein_g' => 5.9, 'carbohydrate_g' => 55.3, 'of_which_sugars_g' => 0.5,
				'fat_g' => 33.0, 'of_which_saturates_g' => 3.2, 'fibre_g' => 3.7, 'salt_g' => 1.10,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Digestive Biscuits', 'slug' => 'digestive-biscuits', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 biscuit (15g)', 'grams' => 15], ['label' => '2 biscuits (30g)', 'grams' => 30] ],
				'energy_kcal' => 471, 'energy_kj' => 1979,
				'protein_g' => 6.3, 'carbohydrate_g' => 68.5, 'of_which_sugars_g' => 16.3,
				'fat_g' => 20.5, 'of_which_saturates_g' => 9.1, 'fibre_g' => 2.2, 'salt_g' => 0.88,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Rich Tea Biscuits', 'slug' => 'rich-tea-biscuits', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 biscuit (9g)', 'grams' => 9], ['label' => '2 biscuits (18g)', 'grams' => 18] ],
				'energy_kcal' => 436, 'energy_kj' => 1840,
				'protein_g' => 6.5, 'carbohydrate_g' => 74.5, 'of_which_sugars_g' => 22.3,
				'fat_g' => 11.8, 'of_which_saturates_g' => 5.7, 'fibre_g' => 1.7, 'salt_g' => 1.26,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Popcorn (plain, air-popped)', 'slug' => 'popcorn-plain', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 bag (30g)', 'grams' => 30] ],
				'energy_kcal' => 387, 'energy_kj' => 1620,
				'protein_g' => 12.9, 'carbohydrate_g' => 77.9, 'of_which_sugars_g' => 0.9,
				'fat_g' => 4.5, 'of_which_saturates_g' => 0.5, 'fibre_g' => 14.5, 'salt_g' => 0.04,
				'source_notes' => 'USDA FDC #167959',
			],
			[
				'name' => 'Peanuts (dry roasted)', 'slug' => 'peanuts-dry-roasted', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 handful (28g)', 'grams' => 28] ],
				'energy_kcal' => 589, 'energy_kj' => 2464,
				'protein_g' => 25.2, 'carbohydrate_g' => 21.2, 'of_which_sugars_g' => 4.7,
				'fat_g' => 49.7, 'of_which_saturates_g' => 8.3, 'fibre_g' => 8.0, 'salt_g' => 1.47,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Peanut Butter (smooth)', 'slug' => 'peanut-butter-smooth', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 tbsp (32g)', 'grams' => 32] ],
				'energy_kcal' => 589, 'energy_kj' => 2462,
				'protein_g' => 25.0, 'carbohydrate_g' => 13.0, 'of_which_sugars_g' => 5.6,
				'fat_g' => 50.0, 'of_which_saturates_g' => 10.0, 'fibre_g' => 5.2, 'salt_g' => 1.01,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Rice Cakes (plain)', 'slug' => 'rice-cakes-plain', 'category_id' => $sc,
				'serving_sizes' => [ ['label' => '1 rice cake (9g)', 'grams' => 9], ['label' => '2 rice cakes (18g)', 'grams' => 18] ],
				'energy_kcal' => 387, 'energy_kj' => 1622,
				'protein_g' => 7.9, 'carbohydrate_g' => 81.3, 'of_which_sugars_g' => 0.5,
				'fat_g' => 2.8, 'of_which_saturates_g' => 0.6, 'fibre_g' => 2.2, 'salt_g' => 0.17,
				'source_notes' => 'USDA FDC #167991',
			],

			// =================================================================
			// TAKEAWAY & READY MEALS (8 foods)
			// =================================================================

			[
				'name' => 'Fish and Chips (takeaway)', 'slug' => 'fish-and-chips-takeaway', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 portion (400g)', 'grams' => 400] ],
				'energy_kcal' => 239, 'energy_kj' => 1000,
				'protein_g' => 10.0, 'carbohydrate_g' => 28.0, 'of_which_sugars_g' => 0.4,
				'fat_g' => 11.0, 'of_which_saturates_g' => 1.9, 'fibre_g' => 1.6, 'salt_g' => 0.63,
				'source_notes' => 'M&W 8th ed. (fish in batter, fried + chips, per 100g average)',
			],
			[
				'name' => 'Chicken Tikka Masala (ready meal)', 'slug' => 'chicken-tikka-masala', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 portion (400g)', 'grams' => 400] ],
				'energy_kcal' => 141, 'energy_kj' => 593,
				'protein_g' => 12.5, 'carbohydrate_g' => 8.5, 'of_which_sugars_g' => 5.7,
				'fat_g' => 6.5, 'of_which_saturates_g' => 2.0, 'fibre_g' => 1.2, 'salt_g' => 0.72,
				'source_notes' => 'M&W 8th ed. (chicken tikka masala, retail prepared meal, per 100g)',
			],
			[
				'name' => 'Pizza Margherita (thin crust)', 'slug' => 'pizza-margherita', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 slice (100g)', 'grams' => 100], ['label' => '½ pizza (200g)', 'grams' => 200] ],
				'energy_kcal' => 232, 'energy_kj' => 976,
				'protein_g' => 10.1, 'carbohydrate_g' => 30.0, 'of_which_sugars_g' => 3.5,
				'fat_g' => 8.2, 'of_which_saturates_g' => 4.1, 'fibre_g' => 1.8, 'salt_g' => 1.15,
				'source_notes' => 'M&W 8th ed. (pizza, cheese and tomato)',
			],
			[
				'name' => 'Beef Burger (with bun)', 'slug' => 'beef-burger-with-bun', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 burger (200g)', 'grams' => 200] ],
				'energy_kcal' => 263, 'energy_kj' => 1101,
				'protein_g' => 14.5, 'carbohydrate_g' => 23.0, 'of_which_sugars_g' => 4.6,
				'fat_g' => 13.0, 'of_which_saturates_g' => 5.1, 'fibre_g' => 1.1, 'salt_g' => 1.30,
				'source_notes' => 'M&W 8th ed. (hamburger with bun, per 100g)',
			],
			[
				'name' => 'Oven Chips (frozen, oven-baked)', 'slug' => 'oven-chips-baked', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 portion (165g)', 'grams' => 165] ],
				'energy_kcal' => 162, 'energy_kj' => 681,
				'protein_g' => 2.5, 'carbohydrate_g' => 29.0, 'of_which_sugars_g' => 1.3,
				'fat_g' => 4.2, 'of_which_saturates_g' => 0.4, 'fibre_g' => 2.5, 'salt_g' => 0.68,
				'source_notes' => 'M&W 8th ed. (chips, frozen, baked)',
			],
			[
				'name' => 'Sausage Roll (large)', 'slug' => 'sausage-roll-large', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 large sausage roll (120g)', 'grams' => 120] ],
				'energy_kcal' => 408, 'energy_kj' => 1706,
				'protein_g' => 10.0, 'carbohydrate_g' => 29.0, 'of_which_sugars_g' => 1.8,
				'fat_g' => 28.6, 'of_which_saturates_g' => 11.9, 'fibre_g' => 1.2, 'salt_g' => 1.73,
				'source_notes' => 'M&W 8th ed. (sausage roll, retail)',
			],
			[
				'name' => 'Baked Beans (tinned, in tomato sauce)', 'slug' => 'baked-beans-tinned', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '½ tin (207g)', 'grams' => 207], ['label' => '1 tin (415g)', 'grams' => 415] ],
				'energy_kcal' => 81, 'energy_kj' => 343,
				'protein_g' => 4.8, 'carbohydrate_g' => 15.1, 'of_which_sugars_g' => 5.2,
				'fat_g' => 0.6, 'of_which_saturates_g' => 0.1, 'fibre_g' => 3.7, 'salt_g' => 0.60,
				'source_notes' => 'M&W 8th ed. (Heinz Baked Beans, per 100g)',
			],
			[
				'name' => 'Tomato Soup (tinned)', 'slug' => 'tomato-soup-tinned', 'category_id' => $ta,
				'serving_sizes' => [ ['label' => '1 bowl (300ml)', 'grams' => 300] ],
				'energy_kcal' => 51, 'energy_kj' => 215,
				'protein_g' => 0.9, 'carbohydrate_g' => 9.6, 'of_which_sugars_g' => 4.0,
				'fat_g' => 1.1, 'of_which_saturates_g' => 0.1, 'fibre_g' => 0.8, 'salt_g' => 0.55,
				'source_notes' => 'M&W 8th ed. (Heinz Cream of Tomato Soup, per 100g)',
			],

			// =================================================================
			// LEGUMES & PULSES (5 foods)
			// =================================================================

			[
				'name' => 'Chickpeas (cooked)', 'slug' => 'chickpeas-cooked', 'category_id' => $lp,
				'serving_sizes' => [ ['label' => '3 tbsp (80g)', 'grams' => 80], ['label' => '½ tin (120g)', 'grams' => 120] ],
				'energy_kcal' => 164, 'energy_kj' => 686,
				'protein_g' => 8.9, 'carbohydrate_g' => 27.4, 'of_which_sugars_g' => 0.5,
				'fat_g' => 2.6, 'of_which_saturates_g' => 0.3, 'fibre_g' => 6.0, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Red Lentils (cooked)', 'slug' => 'red-lentils-cooked', 'category_id' => $lp,
				'serving_sizes' => [ ['label' => '1 portion (180g)', 'grams' => 180] ],
				'energy_kcal' => 100, 'energy_kj' => 421,
				'protein_g' => 7.6, 'carbohydrate_g' => 17.5, 'of_which_sugars_g' => 0.5,
				'fat_g' => 0.4, 'of_which_saturates_g' => 0.1, 'fibre_g' => 3.9, 'salt_g' => 0.01,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Kidney Beans (cooked)', 'slug' => 'kidney-beans-cooked', 'category_id' => $lp,
				'serving_sizes' => [ ['label' => '3 tbsp (80g)', 'grams' => 80] ],
				'energy_kcal' => 127, 'energy_kj' => 533,
				'protein_g' => 8.8, 'carbohydrate_g' => 22.8, 'of_which_sugars_g' => 0.4,
				'fat_g' => 0.5, 'of_which_saturates_g' => 0.1, 'fibre_g' => 6.7, 'salt_g' => 0.02,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Edamame (boiled, shelled)', 'slug' => 'edamame-boiled', 'category_id' => $lp,
				'serving_sizes' => [ ['label' => '1 portion (80g)', 'grams' => 80] ],
				'energy_kcal' => 122, 'energy_kj' => 508,
				'protein_g' => 10.9, 'carbohydrate_g' => 9.9, 'of_which_sugars_g' => 2.2,
				'fat_g' => 5.2, 'of_which_saturates_g' => 0.6, 'fibre_g' => 5.2, 'salt_g' => 0.0,
				'source_notes' => 'USDA FDC #168411',
			],
			[
				'name' => 'Tofu (firm)', 'slug' => 'tofu-firm', 'category_id' => $lp,
				'serving_sizes' => [ ['label' => '½ block (140g)', 'grams' => 140] ],
				'energy_kcal' => 76, 'energy_kj' => 318,
				'protein_g' => 8.1, 'carbohydrate_g' => 1.9, 'of_which_sugars_g' => 0.4,
				'fat_g' => 4.2, 'of_which_saturates_g' => 0.5, 'fibre_g' => 0.3, 'salt_g' => 0.01,
				'source_notes' => 'USDA FDC #174272',
			],

			// =================================================================
			// CONDIMENTS & SAUCES (5 foods)
			// =================================================================

			[
				'name' => 'Tomato Ketchup', 'slug' => 'tomato-ketchup', 'category_id' => $co,
				'serving_sizes' => [ ['label' => '1 tbsp (17g)', 'grams' => 17] ],
				'energy_kcal' => 101, 'energy_kj' => 427,
				'protein_g' => 1.8, 'carbohydrate_g' => 24.0, 'of_which_sugars_g' => 22.0,
				'fat_g' => 0.2, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.8, 'salt_g' => 2.14,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Mayonnaise (full-fat)', 'slug' => 'mayonnaise-full-fat', 'category_id' => $co,
				'serving_sizes' => [ ['label' => '1 tbsp (15g)', 'grams' => 15] ],
				'energy_kcal' => 691, 'energy_kj' => 2840,
				'protein_g' => 1.1, 'carbohydrate_g' => 2.1, 'of_which_sugars_g' => 1.8,
				'fat_g' => 75.6, 'of_which_saturates_g' => 5.8, 'fibre_g' => 0.0, 'salt_g' => 1.30,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Salad Cream', 'slug' => 'salad-cream', 'category_id' => $co,
				'serving_sizes' => [ ['label' => '1 tbsp (15g)', 'grams' => 15] ],
				'energy_kcal' => 316, 'energy_kj' => 1310,
				'protein_g' => 1.6, 'carbohydrate_g' => 16.7, 'of_which_sugars_g' => 13.5,
				'fat_g' => 27.0, 'of_which_saturates_g' => 2.3, 'fibre_g' => 0.4, 'salt_g' => 2.37,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Brown Sauce (e.g. HP)', 'slug' => 'brown-sauce', 'category_id' => $co,
				'serving_sizes' => [ ['label' => '1 tbsp (17g)', 'grams' => 17] ],
				'energy_kcal' => 101, 'energy_kj' => 430,
				'protein_g' => 1.2, 'carbohydrate_g' => 24.1, 'of_which_sugars_g' => 17.1,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 1.1, 'salt_g' => 2.90,
				'source_notes' => 'M&W 8th ed.',
			],
			[
				'name' => 'Soy Sauce', 'slug' => 'soy-sauce', 'category_id' => $co,
				'serving_sizes' => [ ['label' => '1 tbsp (18ml)', 'grams' => 18] ],
				'energy_kcal' => 53, 'energy_kj' => 222,
				'protein_g' => 8.1, 'carbohydrate_g' => 4.9, 'of_which_sugars_g' => 1.7,
				'fat_g' => 0.1, 'of_which_saturates_g' => 0.0, 'fibre_g' => 0.8, 'salt_g' => 15.00,
				'source_notes' => 'M&W 8th ed.',
			],

		]; // end foods array.
	}

	/**
	 * Migration v18: create fcc_wl_licenses table.
	 */
	public static function seed_v18(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 18 ) {
			return;
		}
		Database::create_wl_licenses_table();
		update_option( 'fcc_seed_version', 18 );
	}

	public static function seed_v19(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 19 ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'fcc_foods';
		$cols  = [
			'allergen_fish', 'allergen_shellfish', 'allergen_dairy', 'allergen_eggs',
			'allergen_nuts', 'allergen_gluten', 'allergen_soy', 'allergen_celery',
			'diet_keto', 'diet_paleo', 'diet_halal', 'diet_kosher', 'diet_vegan', 'diet_vegetarian',
		];
		foreach ( $cols as $col ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN IF NOT EXISTS {$col} tinyint(1) DEFAULT NULL" );
		}
		update_option( 'fcc_seed_version', 19 );
	}

	/**
	 * Seed v20: Bulk-tag all existing foods with allergen + dietary flags.
	 */
	public static function seed_v20(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 20 ) {
			return;
		}
		global $wpdb;
		$ft = $wpdb->prefix . 'fcc_foods';
		$ct = $wpdb->prefix . 'fcc_categories';

		// Get category IDs by slug.
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cat_id = [];
		foreach ( $cats as $c ) { $cat_id[ $c['slug'] ] = (int) $c['id']; }

		$fish_id      = $cat_id['fish-seafood'] ?? 0;
		$meat_id      = $cat_id['meat-poultry'] ?? 0;
		$dairy_id     = $cat_id['dairy-eggs'] ?? 0;
		$fruit_id     = $cat_id['fruit-veg'] ?? 0;
		$bread_id     = $cat_id['bread-cereals'] ?? 0;
		$nuts_id      = $cat_id['nuts-seeds'] ?? 0;
		$fats_id      = $cat_id['fats-oils'] ?? 0;
		$drinks_id    = $cat_id['drinks'] ?? 0;
		$snacks_id    = $cat_id['snacks-confectionery'] ?? 0;
		$takeaway_id  = $cat_id['takeaway'] ?? 0;
		$legumes_id   = $cat_id['legumes-pulses'] ?? 0;
		$condiments_id = $cat_id['condiments'] ?? 0;

		// ── Fish & Seafood: allergen_fish=1, most are gluten/dairy/egg-free ──
		if ( $fish_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=1, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_keto=1, diet_paleo=1, diet_halal=1, diet_vegan=0, diet_vegetarian=0 WHERE category_id={$fish_id}" );
			// Shellfish: crab, lobster, prawn, shrimp, scampi, mussel, clam, oyster, squid, octopus
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_shellfish=1 WHERE category_id={$fish_id} AND (LOWER(name) LIKE '%crab%' OR LOWER(name) LIKE '%lobster%' OR LOWER(name) LIKE '%prawn%' OR LOWER(name) LIKE '%shrimp%' OR LOWER(name) LIKE '%scampi%' OR LOWER(name) LIKE '%mussel%' OR LOWER(name) LIKE '%clam%' OR LOWER(name) LIKE '%oyster%' OR LOWER(name) LIKE '%squid%' OR LOWER(name) LIKE '%octopus%' OR LOWER(name) LIKE '%langoustine%' OR LOWER(name) LIKE '%crawfish%' OR LOWER(name) LIKE '%crayfish%')" );
			// Breaded fish = contains gluten
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_gluten=1, diet_keto=0, diet_paleo=0 WHERE category_id={$fish_id} AND (LOWER(name) LIKE '%breaded%' OR LOWER(name) LIKE '%batter%')" );
			// Caviar/roe = kosher varies, set kosher=0 by default for roe
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_kosher=1 WHERE category_id={$fish_id} AND LOWER(name) NOT LIKE '%shellfish%'" );
		}

		// ── Meat & Poultry: no fish/shellfish/dairy/gluten/nuts/soy ──
		if ( $meat_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_keto=1, diet_paleo=1, diet_vegan=0, diet_vegetarian=0 WHERE category_id={$meat_id}" );
			// Poultry = halal (if slaughtered correctly, assume yes for generic data)
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_halal=1, diet_kosher=0 WHERE category_id={$meat_id} AND (LOWER(name) LIKE '%chicken%' OR LOWER(name) LIKE '%turkey%' OR LOWER(name) LIKE '%duck%')" );
			// Pork = not halal, not kosher
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_halal=0, diet_kosher=0 WHERE category_id={$meat_id} AND (LOWER(name) LIKE '%pork%' OR LOWER(name) LIKE '%bacon%' OR LOWER(name) LIKE '%ham%' OR LOWER(name) LIKE '%sausage%')" );
			// Beef/lamb = halal (generic), kosher (generic)
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_halal=1, diet_kosher=1 WHERE category_id={$meat_id} AND (LOWER(name) LIKE '%beef%' OR LOWER(name) LIKE '%lamb%' OR LOWER(name) LIKE '%veal%')" );
		}

		// ── Dairy & Eggs ──
		if ( $dairy_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_paleo=0, diet_vegan=0, diet_halal=1, diet_kosher=1 WHERE category_id={$dairy_id}" );
			// Dairy items
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_dairy=1, allergen_eggs=0, diet_vegetarian=1, diet_keto=1 WHERE category_id={$dairy_id} AND LOWER(name) NOT LIKE '%egg%'" );
			// Egg items
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_eggs=1, allergen_dairy=0, diet_vegetarian=1, diet_keto=1 WHERE category_id={$dairy_id} AND LOWER(name) LIKE '%egg%'" );
		}

		// ── Fruit & Vegetables: all-clear allergens, vegan/vegetarian ──
		if ( $fruit_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_vegan=1, diet_vegetarian=1, diet_halal=1, diet_kosher=1, diet_paleo=1 WHERE category_id={$fruit_id}" );
			// Celery
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_celery=1 WHERE category_id={$fruit_id} AND LOWER(name) LIKE '%celery%'" );
			// Keto: low-carb fruits/veg only
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_keto=CASE WHEN carbohydrate_g <= 5 THEN 1 ELSE 0 END WHERE category_id={$fruit_id}" );
		}

		// ── Bread & Cereals: contains gluten ──
		if ( $bread_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=1, allergen_soy=0, allergen_celery=0, diet_keto=0, diet_paleo=0, diet_vegan=1, diet_vegetarian=1, diet_halal=1, diet_kosher=1 WHERE category_id={$bread_id}" );
			// Some breads contain dairy/eggs
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_eggs=1 WHERE category_id={$bread_id} AND (LOWER(name) LIKE '%brioche%' OR LOWER(name) LIKE '%cake%' OR LOWER(name) LIKE '%pastry%')" );
		}

		// ── Nuts & Seeds: allergen_nuts=1, vegan ──
		if ( $nuts_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=1, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_keto=1, diet_paleo=1, diet_vegan=1, diet_vegetarian=1, diet_halal=1, diet_kosher=1 WHERE category_id={$nuts_id}" );
			// Seeds are not tree nuts
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_nuts=0 WHERE category_id={$nuts_id} AND (LOWER(name) LIKE '%seed%' OR LOWER(name) LIKE '%chia%' OR LOWER(name) LIKE '%flax%' OR LOWER(name) LIKE '%hemp%' OR LOWER(name) LIKE '%sesame%' OR LOWER(name) LIKE '%sunflower%' OR LOWER(name) LIKE '%pumpkin%')" );
		}

		// ── Fats & Oils: vegan (plant oils), dairy (butter) ──
		if ( $fats_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_keto=1, diet_paleo=1, diet_vegan=1, diet_vegetarian=1, diet_halal=1, diet_kosher=1 WHERE category_id={$fats_id}" );
			// Butter = dairy
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_dairy=1, diet_vegan=0 WHERE category_id={$fats_id} AND (LOWER(name) LIKE '%butter%' OR LOWER(name) LIKE '%ghee%')" );
		}

		// ── Drinks ──
		if ( $drinks_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_halal=1, diet_kosher=1, diet_vegetarian=1 WHERE category_id={$drinks_id}" );
			// Milk drinks = dairy
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_dairy=1, diet_vegan=0 WHERE category_id={$drinks_id} AND (LOWER(name) LIKE '%milk%' OR LOWER(name) LIKE '%latte%' OR LOWER(name) LIKE '%cappuccino%')" );
			// Plant milks = vegan
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_dairy=0, diet_vegan=1 WHERE category_id={$drinks_id} AND (LOWER(name) LIKE '%oat milk%' OR LOWER(name) LIKE '%almond milk%' OR LOWER(name) LIKE '%soy milk%' OR LOWER(name) LIKE '%coconut milk%')" );
			// Alcohol = not halal
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_halal=0 WHERE category_id={$drinks_id} AND (LOWER(name) LIKE '%beer%' OR LOWER(name) LIKE '%wine%' OR LOWER(name) LIKE '%vodka%' OR LOWER(name) LIKE '%whisky%' OR LOWER(name) LIKE '%gin%' OR LOWER(name) LIKE '%rum%' OR LOWER(name) LIKE '%cider%' OR LOWER(name) LIKE '%ale%' OR LOWER(name) LIKE '%lager%')" );
			// Keto drinks = zero/low sugar
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_keto=CASE WHEN carbohydrate_g <= 3 THEN 1 ELSE 0 END WHERE category_id={$drinks_id}" );
		}

		// ── Legumes & Pulses: vegan, no major allergens ──
		if ( $legumes_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_celery=0, diet_keto=0, diet_paleo=0, diet_vegan=1, diet_vegetarian=1, diet_halal=1, diet_kosher=1 WHERE category_id={$legumes_id}" );
			// Soy products
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_soy=1 WHERE category_id={$legumes_id} AND (LOWER(name) LIKE '%soy%' OR LOWER(name) LIKE '%tofu%' OR LOWER(name) LIKE '%edamame%' OR LOWER(name) LIKE '%tempeh%')" );
		}

		// ── Snacks & Confectionery ──
		if ( $snacks_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_celery=0, allergen_soy=0, diet_keto=0, diet_paleo=0, diet_halal=1, diet_kosher=1, diet_vegetarian=1 WHERE category_id={$snacks_id}" );
			// Chocolate = dairy
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_dairy=1, allergen_gluten=0, diet_vegan=0 WHERE category_id={$snacks_id} AND LOWER(name) LIKE '%chocolate%'" );
			// Crisps = usually vegan, gluten-free
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_gluten=0, allergen_dairy=0, diet_vegan=1 WHERE category_id={$snacks_id} AND LOWER(name) LIKE '%crisp%'" );
			// Biscuits/cookies = gluten, eggs, dairy
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_gluten=1, allergen_eggs=1, allergen_dairy=1, diet_vegan=0 WHERE category_id={$snacks_id} AND (LOWER(name) LIKE '%biscuit%' OR LOWER(name) LIKE '%cookie%' OR LOWER(name) LIKE '%cake%')" );
		}

		// ── Condiments & Sauces ──
		if ( $condiments_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_dairy=0, allergen_eggs=0, allergen_nuts=0, allergen_gluten=0, allergen_soy=0, allergen_celery=0, diet_vegetarian=1, diet_vegan=1, diet_halal=1, diet_kosher=1 WHERE category_id={$condiments_id}" );
			// Soy sauce = soy + gluten
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_soy=1, allergen_gluten=1 WHERE category_id={$condiments_id} AND LOWER(name) LIKE '%soy sauce%'" );
			// Mayo = eggs
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_eggs=1, diet_vegan=0 WHERE category_id={$condiments_id} AND LOWER(name) LIKE '%mayo%'" );
			// Fish sauce = fish
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=1, diet_vegan=0, diet_vegetarian=0 WHERE category_id={$condiments_id} AND LOWER(name) LIKE '%fish sauce%'" );
			// Keto for condiments: low carb
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET diet_keto=CASE WHEN carbohydrate_g <= 5 THEN 1 ELSE 0 END, diet_paleo=CASE WHEN carbohydrate_g <= 10 THEN 1 ELSE 0 END WHERE category_id={$condiments_id}" );
		}

		// ── Takeaway & Ready Meals: complex, set conservative defaults ──
		if ( $takeaway_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_fish=0, allergen_shellfish=0, allergen_nuts=0, allergen_celery=0, allergen_soy=0, diet_keto=0, diet_paleo=0, diet_vegan=0, diet_halal=0, diet_kosher=0 WHERE category_id={$takeaway_id}" );
			// Most contain gluten (breading, wraps, naan)
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$ft} SET allergen_gluten=1 WHERE category_id={$takeaway_id}" );
		}

		update_option( 'fcc_seed_version', 20 );
	}

	/**
	 * Seed v21: Add 200+ common cooking staples + premium seafood.
	 * All values per 100g, sourced from McCance & Widdowson / USDA FDC.
	 */
	public static function seed_v21(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 21 ) {
			return;
		}
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }

		$fv  = $cid['fruit-veg'] ?? 0;
		$mp  = $cid['meat-poultry'] ?? 0;
		$fs  = $cid['fish-seafood'] ?? 0;
		$de  = $cid['dairy-eggs'] ?? 0;
		$bc  = $cid['bread-cereals'] ?? 0;
		$ns  = $cid['nuts-seeds'] ?? 0;
		$fo  = $cid['fats-oils'] ?? 0;
		$dr  = $cid['drinks'] ?? 0;
		$lp  = $cid['legumes-pulses'] ?? 0;
		$co  = $cid['condiments'] ?? 0;
		$sc  = $cid['snacks-confectionery'] ?? 0;

		// Each row: [name, cat_id, kcal, kj, protein, carbs, sugars, fat, saturates, fibre, salt, allergen flags..., diet flags...]
		// Allergen order: fish, shellfish, dairy, eggs, nuts, gluten, soy, celery
		// Diet order: keto, paleo, halal, kosher, vegan, vegetarian
		$foods = [
			// ── VEGETABLES ──
			['Onion (raw)', $fv, 36, 151, 1.2, 7.9, 4.2, 0.1, 0.0, 1.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Garlic (raw)', $fv, 149, 623, 6.4, 33.1, 1.0, 0.5, 0.1, 2.1, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Carrot (raw)', $fv, 41, 171, 0.9, 9.6, 4.7, 0.2, 0.0, 2.8, 0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Potato (raw)', $fv, 77, 322, 2.0, 17.5, 0.8, 0.1, 0.0, 2.2, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sweet Potato (raw)', $fv, 86, 360, 1.6, 20.1, 4.2, 0.1, 0.0, 3.0, 0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tomato (raw)', $fv, 18, 75, 0.9, 3.9, 2.6, 0.2, 0.0, 1.2, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Red Pepper (raw)', $fv, 31, 130, 1.0, 6.0, 4.2, 0.3, 0.1, 2.1, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mushroom (raw)', $fv, 22, 92, 3.1, 3.3, 1.7, 0.3, 0.1, 1.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Broccoli (raw)', $fv, 34, 142, 2.8, 6.6, 1.7, 0.4, 0.1, 2.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cauliflower (raw)', $fv, 25, 105, 1.9, 5.0, 1.9, 0.3, 0.1, 2.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Spinach (raw)', $fv, 23, 96, 2.9, 3.6, 0.4, 0.4, 0.1, 2.2, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kale (raw)', $fv, 49, 205, 4.3, 8.8, 2.3, 0.9, 0.1, 3.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cabbage (raw)', $fv, 25, 105, 1.3, 5.8, 3.2, 0.1, 0.0, 2.5, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Courgette (raw)', $fv, 17, 71, 1.2, 3.1, 2.5, 0.3, 0.1, 1.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Aubergine (raw)', $fv, 25, 105, 1.0, 5.9, 3.5, 0.2, 0.0, 3.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Leek (raw)', $fv, 61, 255, 1.5, 14.2, 3.9, 0.3, 0.0, 1.8, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Spring Onion (raw)', $fv, 32, 134, 1.8, 7.3, 2.3, 0.2, 0.0, 2.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Ginger Root (raw)', $fv, 80, 335, 1.8, 17.8, 1.7, 0.8, 0.2, 2.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Beetroot (raw)', $fv, 43, 180, 1.6, 9.6, 6.8, 0.2, 0.0, 2.8, 0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Parsnip (raw)', $fv, 75, 314, 1.2, 18.0, 4.8, 0.3, 0.1, 4.9, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sweetcorn (canned)', $fv, 86, 360, 2.9, 18.7, 4.5, 1.2, 0.2, 1.7, 0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Peas (frozen)', $fv, 81, 339, 5.4, 14.5, 5.7, 0.4, 0.1, 5.1, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Green Beans (raw)', $fv, 31, 130, 1.8, 7.0, 3.3, 0.1, 0.0, 3.4, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Asparagus (raw)', $fv, 20, 84, 2.2, 3.9, 1.9, 0.1, 0.0, 2.1, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cucumber (raw)', $fv, 15, 63, 0.7, 3.6, 1.7, 0.1, 0.0, 0.5, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Lettuce (iceberg)', $fv, 14, 59, 0.9, 3.0, 2.0, 0.1, 0.0, 1.2, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Celery (raw)', $fv, 16, 67, 0.7, 3.0, 1.3, 0.2, 0.0, 1.6, 0.1, 0,0,0,0,0,0,0,1, 1,1,1,1,1,1],
			['Avocado (raw)', $fv, 160, 670, 2.0, 8.5, 0.7, 14.7, 2.1, 6.7, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Butternut Squash (raw)', $fv, 45, 188, 1.0, 11.7, 2.2, 0.1, 0.0, 2.0, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Rocket (raw)', $fv, 25, 105, 2.6, 3.7, 2.1, 0.7, 0.1, 1.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── FRUITS ──
			['Banana (raw)', $fv, 89, 372, 1.1, 22.8, 12.2, 0.3, 0.1, 2.6, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Apple (raw, with skin)', $fv, 52, 218, 0.3, 13.8, 10.4, 0.2, 0.0, 2.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Orange (raw)', $fv, 47, 197, 0.9, 11.8, 9.4, 0.1, 0.0, 2.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lemon (raw)', $fv, 29, 121, 1.1, 9.3, 2.5, 0.3, 0.0, 2.8, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Lime (raw)', $fv, 30, 126, 0.7, 10.5, 1.7, 0.2, 0.0, 2.8, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Strawberry (raw)', $fv, 32, 134, 0.7, 7.7, 4.9, 0.3, 0.0, 2.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Blueberry (raw)', $fv, 57, 239, 0.7, 14.5, 10.0, 0.3, 0.0, 2.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Raspberry (raw)', $fv, 52, 218, 1.2, 11.9, 4.4, 0.7, 0.0, 6.5, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mango (raw)', $fv, 60, 251, 0.8, 15.0, 13.7, 0.4, 0.1, 1.6, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Pineapple (raw)', $fv, 50, 209, 0.5, 13.1, 9.9, 0.1, 0.0, 1.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Grapes (raw)', $fv, 69, 289, 0.7, 18.1, 15.5, 0.2, 0.1, 0.9, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Pear (raw)', $fv, 57, 239, 0.4, 15.2, 9.8, 0.1, 0.0, 3.1, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Watermelon (raw)', $fv, 30, 126, 0.6, 7.6, 6.2, 0.2, 0.0, 0.4, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kiwi (raw)', $fv, 61, 255, 1.1, 14.7, 9.0, 0.5, 0.0, 3.0, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Coconut (fresh)', $fv, 354, 1481, 3.3, 15.2, 6.2, 33.5, 29.7, 9.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── GRAINS & CARBS ──
			['White Rice (raw)', $bc, 360, 1506, 6.7, 79.3, 0.1, 0.6, 0.2, 1.3, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Brown Rice (raw)', $bc, 362, 1515, 7.5, 76.2, 0.7, 2.7, 0.5, 3.4, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Basmati Rice (raw)', $bc, 349, 1460, 8.1, 77.1, 0.1, 0.6, 0.2, 0.6, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pasta (dried, white)', $bc, 350, 1464, 12.5, 71.5, 2.7, 1.5, 0.3, 2.9, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Spaghetti (dried)', $bc, 350, 1464, 12.5, 71.5, 2.7, 1.5, 0.3, 2.9, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bread (white, sliced)', $bc, 265, 1109, 9.4, 49.3, 4.8, 3.2, 0.6, 2.7, 1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bread (wholemeal)', $bc, 247, 1033, 12.6, 41.6, 3.9, 3.5, 0.6, 7.4, 1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Porridge Oats', $bc, 379, 1586, 13.5, 67.5, 1.0, 6.9, 1.2, 10.6, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Couscous (dried)', $bc, 376, 1573, 12.8, 72.4, 0.3, 1.1, 0.2, 2.0, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Quinoa (raw)', $bc, 368, 1540, 14.1, 64.2, 0.0, 6.1, 0.7, 7.0, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Plain Flour', $bc, 364, 1523, 10.3, 76.3, 0.3, 1.3, 0.2, 3.1, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Self-Raising Flour', $bc, 339, 1419, 9.4, 72.0, 0.3, 1.2, 0.2, 2.5, 0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cornflour', $bc, 381, 1594, 0.3, 91.3, 0.0, 0.1, 0.0, 0.9, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Egg Noodles (dried)', $bc, 384, 1607, 12.0, 71.3, 0.7, 4.4, 1.0, 2.2, 0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Pitta Bread (white)', $bc, 255, 1067, 9.1, 55.7, 1.6, 1.2, 0.2, 2.2, 0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tortilla Wrap (flour)', $bc, 312, 1305, 8.4, 51.4, 3.7, 8.0, 2.0, 2.1, 0.9, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── DAIRY ──
			['Whole Milk', $de, 64, 268, 3.2, 4.8, 4.8, 3.6, 2.3, 0.0, 0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Semi-Skimmed Milk', $de, 46, 193, 3.4, 4.8, 4.8, 1.7, 1.1, 0.0, 0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Skimmed Milk', $de, 35, 147, 3.4, 5.0, 5.0, 0.3, 0.1, 0.0, 0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Double Cream', $de, 449, 1879, 1.7, 2.6, 2.6, 48.0, 30.0, 0.0, 0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Single Cream', $de, 193, 808, 2.6, 3.9, 3.9, 19.1, 11.9, 0.0, 0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Cheddar Cheese', $de, 403, 1686, 25.4, 0.1, 0.1, 33.8, 21.1, 0.0, 1.8, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Mozzarella', $de, 280, 1172, 17.9, 3.1, 1.0, 22.4, 13.2, 0.0, 0.7, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Parmesan', $de, 392, 1640, 35.8, 3.2, 0.8, 25.8, 17.3, 0.0, 1.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Cream Cheese', $de, 342, 1431, 5.9, 4.1, 3.8, 34.2, 21.7, 0.0, 0.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Natural Yoghurt', $de, 61, 255, 3.5, 7.0, 7.0, 3.3, 2.1, 0.0, 0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Greek Yoghurt', $de, 97, 406, 9.0, 3.6, 3.6, 5.0, 3.5, 0.0, 0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Unsalted Butter', $de, 717, 2999, 0.9, 0.1, 0.1, 81.1, 51.4, 0.0, 0.0, 0,0,1,0,0,0,0,0, 1,1,1,1,0,1],
			['Feta Cheese', $de, 264, 1104, 14.2, 4.1, 4.1, 21.3, 14.9, 0.0, 1.4, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],

			// ── OILS & FATS ──
			['Olive Oil', $fo, 884, 3699, 0.0, 0.0, 0.0, 100.0, 13.8, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Vegetable Oil', $fo, 884, 3699, 0.0, 0.0, 0.0, 100.0, 14.0, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Coconut Oil', $fo, 862, 3607, 0.0, 0.0, 0.0, 100.0, 86.5, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sunflower Oil', $fo, 884, 3699, 0.0, 0.0, 0.0, 100.0, 10.3, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sesame Oil', $fo, 884, 3699, 0.0, 0.0, 0.0, 100.0, 14.2, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Rapeseed Oil', $fo, 884, 3699, 0.0, 0.0, 0.0, 100.0, 7.4, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── BAKING & SWEETENERS ──
			['White Sugar', $co, 400, 1674, 0.0, 100.0, 100.0, 0.0, 0.0, 0.0, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Brown Sugar', $co, 380, 1590, 0.0, 98.1, 97.3, 0.0, 0.0, 0.0, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Honey', $co, 304, 1272, 0.3, 82.4, 82.1, 0.0, 0.0, 0.2, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,0,1],
			['Golden Syrup', $co, 325, 1360, 0.3, 79.0, 79.0, 0.0, 0.0, 0.0, 0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cocoa Powder (unsweetened)', $co, 228, 954, 19.6, 57.9, 1.8, 13.7, 8.1, 33.2, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── LEGUMES & PULSES ──
			['Chickpeas (canned)', $lp, 119, 498, 7.0, 16.8, 1.1, 2.6, 0.3, 5.4, 0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Red Lentils (dried)', $lp, 318, 1331, 24.6, 56.3, 2.0, 1.1, 0.2, 10.8, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kidney Beans (canned)', $lp, 93, 389, 6.9, 12.5, 1.8, 0.5, 0.1, 6.4, 0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baked Beans (canned)', $lp, 84, 351, 5.2, 13.5, 5.9, 0.6, 0.1, 3.7, 0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tofu (firm)', $lp, 76, 318, 8.1, 1.9, 0.6, 4.8, 0.7, 0.3, 0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Black Beans (canned)', $lp, 91, 381, 6.0, 14.5, 0.3, 0.5, 0.1, 7.6, 0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Green Lentils (dried)', $lp, 297, 1243, 24.3, 48.8, 2.0, 1.1, 0.2, 8.9, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── CONDIMENTS ──
			['Tomato Ketchup', $co, 112, 469, 1.5, 25.8, 22.8, 0.1, 0.0, 0.3, 1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['English Mustard', $co, 174, 728, 8.2, 10.0, 6.4, 11.1, 0.7, 3.3, 3.4, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['White Wine Vinegar', $co, 18, 75, 0.0, 0.6, 0.0, 0.0, 0.0, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Soy Sauce', $co, 53, 222, 5.6, 5.6, 1.0, 0.1, 0.0, 0.4, 5.5, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Worcestershire Sauce', $co, 78, 326, 1.4, 17.0, 10.0, 0.0, 0.0, 0.3, 3.1, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Mayonnaise', $co, 691, 2891, 1.0, 1.6, 1.0, 75.0, 5.8, 0.0, 0.7, 0,0,0,1,0,0,0,0, 1,1,1,1,0,1],
			['Balsamic Vinegar', $co, 88, 368, 0.5, 17.0, 14.0, 0.0, 0.0, 0.0, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hot Sauce (Tabasco)', $co, 12, 50, 0.8, 1.8, 1.3, 0.4, 0.1, 0.8, 2.6, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pesto (green)', $co, 387, 1619, 5.5, 3.8, 2.8, 38.3, 7.1, 1.8, 1.5, 0,0,1,0,1,0,0,0, 1,0,1,1,0,1],

			// ── PREMIUM SEAFOOD ──
			['Lobster (raw)', $fs, 89, 372, 18.8, 0.0, 0.0, 0.9, 0.2, 0.0, 0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Langoustine (raw)', $fs, 90, 377, 19.5, 0.0, 0.0, 0.9, 0.2, 0.0, 0.4, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Scallops (raw)', $fs, 69, 289, 12.1, 3.2, 0.0, 0.5, 0.1, 0.0, 0.6, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Monkfish (raw)', $fs, 76, 318, 14.5, 0.0, 0.0, 1.5, 0.0, 0.0, 0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Turbot (raw)', $fs, 95, 397, 16.0, 0.0, 0.0, 3.3, 0.8, 0.0, 0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Dover Sole (raw)', $fs, 86, 360, 17.5, 0.0, 0.0, 1.2, 0.3, 0.0, 0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Sea Bream (raw)', $fs, 100, 418, 19.1, 0.0, 0.0, 2.4, 0.6, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['John Dory (raw)', $fs, 79, 331, 17.5, 0.0, 0.0, 0.6, 0.2, 0.0, 0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Razor Clams (raw)', $fs, 73, 305, 12.8, 2.6, 0.0, 1.0, 0.2, 0.0, 0.6, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Cockles (raw)', $fs, 53, 222, 12.0, 0.0, 0.0, 0.6, 0.1, 0.0, 0.6, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Whelks (raw)', $fs, 86, 360, 18.3, 1.5, 0.0, 0.5, 0.1, 0.0, 0.6, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Smoked Salmon', $fs, 117, 490, 18.3, 0.0, 0.0, 4.3, 0.9, 0.0, 3.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Smoked Mackerel', $fs, 305, 1276, 18.6, 0.0, 0.0, 25.3, 5.9, 0.0, 1.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Smoked Haddock', $fs, 81, 339, 18.6, 0.0, 0.0, 0.6, 0.1, 0.0, 1.4, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Gravlax (cured salmon)', $fs, 196, 820, 18.0, 2.5, 2.0, 12.0, 2.3, 0.0, 2.8, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Swordfish (raw)', $fs, 121, 506, 19.7, 0.0, 0.0, 4.0, 1.1, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Red Snapper (raw)', $fs, 100, 418, 20.5, 0.0, 0.0, 1.3, 0.3, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Barramundi (raw)', $fs, 97, 406, 19.4, 0.0, 0.0, 2.0, 0.5, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Mahi-Mahi (raw)', $fs, 85, 356, 18.5, 0.0, 0.0, 0.7, 0.2, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Yellowtail (raw)', $fs, 146, 611, 23.1, 0.0, 0.0, 5.4, 1.3, 0.0, 0.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Grouper (raw)', $fs, 92, 385, 19.4, 0.0, 0.0, 1.0, 0.2, 0.0, 0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Brown Crab Meat', $fs, 128, 536, 19.5, 0.0, 0.0, 5.5, 0.7, 0.0, 1.1, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['White Crab Meat', $fs, 81, 339, 18.1, 0.0, 0.0, 0.9, 0.1, 0.0, 0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Caviar (Sturgeon, generic)', $fs, 264, 1105, 24.6, 4.0, 0.0, 17.9, 4.1, 0.0, 1.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Fish Roe (generic)', $fs, 143, 598, 22.3, 1.5, 0.0, 6.4, 1.6, 0.0, 1.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Taramasalata', $fs, 446, 1866, 3.6, 4.0, 2.0, 46.4, 5.3, 0.3, 1.3, 1,0,0,1,0,1,0,0, 0,0,1,0,0,1],

			// ── HERBS & SPICES (dried, per 100g) ──
			['Black Pepper (ground)', $co, 251, 1050, 10.4, 63.9, 0.6, 3.3, 1.4, 25.3, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cumin (ground)', $co, 375, 1569, 17.8, 44.2, 2.3, 22.3, 1.5, 10.5, 0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Turmeric (ground)', $co, 312, 1305, 7.8, 67.1, 3.2, 3.3, 1.8, 22.7, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Paprika (ground)', $co, 282, 1180, 14.1, 53.6, 10.3, 12.9, 2.1, 34.9, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cinnamon (ground)', $co, 247, 1033, 4.0, 80.6, 2.2, 1.2, 0.3, 53.1, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chilli Powder', $co, 282, 1180, 13.5, 49.7, 7.2, 14.3, 2.5, 34.8, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Garlic Powder', $co, 331, 1385, 16.6, 72.7, 2.4, 0.7, 0.1, 9.0, 0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Onion Powder', $co, 341, 1427, 10.4, 79.1, 6.6, 1.0, 0.2, 15.2, 0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mixed Herbs (dried)', $co, 208, 870, 12.0, 28.0, 0.0, 5.5, 1.0, 18.0, 0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Garam Masala', $co, 379, 1586, 14.5, 45.5, 3.2, 15.1, 2.5, 15.0, 0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug = %s", $slug
			) ); // phpcs:ignore
			if ( $exists ) { continue; }

			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'                 => $f[0],
				'slug'                 => $slug,
				'category_id'          => $f[1],
				'energy_kcal'          => $f[2],
				'energy_kj'            => $f[3],
				'protein_g'            => $f[4],
				'carbohydrate_g'       => $f[5],
				'of_which_sugars_g'    => $f[6],
				'fat_g'                => $f[7],
				'of_which_saturates_g' => $f[8],
				'fibre_g'              => $f[9],
				'salt_g'               => $f[10],
				'allergen_fish'        => $f[11],
				'allergen_shellfish'   => $f[12],
				'allergen_dairy'       => $f[13],
				'allergen_eggs'        => $f[14],
				'allergen_nuts'        => $f[15],
				'allergen_gluten'      => $f[16],
				'allergen_soy'         => $f[17],
				'allergen_celery'      => $f[18],
				'diet_keto'            => $f[19],
				'diet_paleo'           => $f[20],
				'diet_halal'           => $f[21],
				'diet_kosher'          => $f[22],
				'diet_vegan'           => $f[23],
				'diet_vegetarian'      => $f[24],
				'source_notes'         => 'M&W 8th ed. / USDA FDC. Seeded v21.',
			] ); // phpcs:ignore
		}

		update_option( 'fcc_seed_version', 21 );
	}

	/**
	 * Seed v22: Add 100+ more unique foods — meats, world cuisines, extras.
	 */
	public static function seed_v22(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 22 ) {
			return;
		}
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }

		$fv = $cid['fruit-veg'] ?? 0;
		$mp = $cid['meat-poultry'] ?? 0;
		$fs = $cid['fish-seafood'] ?? 0;
		$de = $cid['dairy-eggs'] ?? 0;
		$bc = $cid['bread-cereals'] ?? 0;
		$ns = $cid['nuts-seeds'] ?? 0;
		$fo = $cid['fats-oils'] ?? 0;
		$dr = $cid['drinks'] ?? 0;
		$lp = $cid['legumes-pulses'] ?? 0;
		$co = $cid['condiments'] ?? 0;
		$sc = $cid['snacks-confectionery'] ?? 0;
		$tk = $cid['takeaway'] ?? 0;

		$foods = [
			// ── MORE MEATS ──
			['Chicken Thigh (raw, skinless)', $mp, 119, 498, 19.7, 0.0, 0.0, 3.9, 1.1, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Chicken Wing (raw)', $mp, 203, 849, 18.3, 0.0, 0.0, 14.0, 3.9, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Chicken Liver (raw)', $mp, 119, 498, 16.9, 0.7, 0.0, 4.8, 1.6, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Turkey Breast (raw)', $mp, 104, 435, 23.6, 0.0, 0.0, 0.7, 0.2, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Duck Breast (raw, skinless)', $mp, 123, 515, 19.9, 0.0, 0.0, 4.5, 1.5, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Beef Steak (sirloin, raw)', $mp, 176, 736, 20.9, 0.0, 0.0, 9.9, 4.2, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Beef Mince (raw, 10% fat)', $mp, 174, 728, 20.0, 0.0, 0.0, 10.0, 4.3, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Beef Brisket (raw)', $mp, 213, 891, 17.4, 0.0, 0.0, 15.8, 6.7, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Lamb Leg (raw, lean)', $mp, 156, 653, 20.3, 0.0, 0.0, 8.2, 3.5, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Lamb Chop (raw)', $mp, 253, 1059, 16.5, 0.0, 0.0, 20.9, 9.8, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Lamb Mince (raw)', $mp, 235, 983, 17.0, 0.0, 0.0, 18.5, 8.5, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pork Loin (raw, lean)', $mp, 143, 598, 21.1, 0.0, 0.0, 6.0, 2.2, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Pork Belly (raw)', $mp, 393, 1644, 11.5, 0.0, 0.0, 37.0, 13.5, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Pork Sausage (raw)', $mp, 278, 1163, 12.1, 8.6, 1.4, 22.1, 8.0, 0.9, 1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Venison (raw)', $mp, 119, 498, 22.2, 0.0, 0.0, 2.4, 0.9, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Rabbit (raw)', $mp, 114, 477, 21.8, 0.0, 0.0, 2.3, 0.7, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Goose (raw)', $mp, 161, 673, 22.8, 0.0, 0.0, 7.1, 2.5, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Quail (raw)', $mp, 134, 561, 21.8, 0.0, 0.0, 4.5, 1.3, 0.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],

			// ── MORE VEGETABLES ──
			['Radish (raw)', $fv, 16, 67, 0.7, 3.4, 1.9, 0.1, 0.0, 1.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fennel (raw)', $fv, 31, 130, 1.2, 7.3, 3.9, 0.2, 0.0, 3.1, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Artichoke (globe, raw)', $fv, 47, 197, 3.3, 10.5, 1.0, 0.2, 0.0, 5.4, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Okra (raw)', $fv, 33, 138, 1.9, 7.5, 1.5, 0.2, 0.0, 3.2, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pak Choi (raw)', $fv, 13, 54, 1.5, 2.2, 1.2, 0.2, 0.0, 1.0, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Watercress (raw)', $fv, 11, 46, 2.3, 1.3, 0.2, 0.1, 0.0, 0.5, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Turnip (raw)', $fv, 28, 117, 0.9, 6.4, 3.8, 0.1, 0.0, 1.8, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Swede (raw)', $fv, 38, 159, 1.1, 8.6, 4.5, 0.2, 0.0, 2.3, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Celeriac (raw)', $fv, 42, 176, 1.5, 9.2, 1.6, 0.3, 0.1, 1.8, 0.1, 0,0,0,0,0,0,0,1, 0,1,1,1,1,1],
			['Tenderstem Broccoli (raw)', $fv, 35, 146, 3.6, 4.4, 1.4, 0.6, 0.1, 3.3, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Samphire (raw)', $fv, 15, 63, 1.3, 2.0, 0.5, 0.2, 0.0, 0.0, 2.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Edamame (shelled)', $lp, 122, 510, 11.9, 8.9, 2.2, 5.2, 0.6, 5.2, 0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],

			// ── MORE FRUITS ──
			['Pomegranate (seeds)', $fv, 83, 347, 1.7, 18.7, 13.7, 1.2, 0.1, 4.0, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Fig (fresh)', $fv, 74, 310, 0.8, 19.2, 16.3, 0.3, 0.1, 2.9, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Passion Fruit (raw)', $fv, 97, 406, 2.2, 23.4, 11.2, 0.7, 0.1, 10.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Grapefruit (raw)', $fv, 42, 176, 0.8, 10.7, 6.9, 0.1, 0.0, 1.6, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cranberry (raw)', $fv, 46, 192, 0.4, 12.2, 4.0, 0.1, 0.0, 4.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Blackberry (raw)', $fv, 43, 180, 1.4, 9.6, 4.9, 0.5, 0.0, 5.3, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cherry (raw)', $fv, 50, 209, 1.0, 12.2, 8.5, 0.3, 0.1, 1.6, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Plum (raw)', $fv, 46, 192, 0.7, 11.4, 9.9, 0.3, 0.0, 1.4, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Apricot (raw)', $fv, 48, 201, 1.4, 11.1, 9.2, 0.4, 0.0, 2.0, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Nectarine (raw)', $fv, 44, 184, 1.1, 10.6, 7.9, 0.3, 0.0, 1.7, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Medjool Dates', $fv, 277, 1159, 1.8, 75.0, 66.5, 0.2, 0.0, 6.7, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Apricot', $fv, 241, 1008, 3.4, 63.0, 53.0, 0.5, 0.0, 7.3, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Raisins', $fv, 299, 1251, 3.1, 79.2, 59.2, 0.5, 0.2, 3.7, 0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── MORE DAIRY ──
			['Goat Cheese', $de, 364, 1523, 21.6, 0.9, 0.9, 29.8, 20.6, 0.0, 1.2, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Ricotta', $de, 174, 728, 11.3, 3.0, 0.3, 13.0, 8.3, 0.0, 0.2, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Halloumi', $de, 321, 1343, 21.0, 2.6, 1.2, 25.0, 16.3, 0.0, 2.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Mascarpone', $de, 429, 1795, 4.8, 3.5, 3.5, 44.0, 27.0, 0.0, 0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Brie', $de, 334, 1398, 20.8, 0.5, 0.5, 27.7, 17.4, 0.0, 1.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Stilton (Blue)', $de, 410, 1715, 23.7, 0.1, 0.1, 35.0, 21.6, 0.0, 1.8, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1],
			['Cottage Cheese', $de, 98, 410, 11.1, 3.4, 2.7, 4.3, 1.7, 0.0, 0.4, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Clotted Cream', $de, 586, 2451, 1.6, 2.3, 2.3, 63.5, 39.7, 0.0, 0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Oat Milk (unsweetened)', $dr, 43, 180, 1.0, 6.7, 3.3, 1.5, 0.2, 0.8, 0.1, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Almond Milk (unsweetened)', $dr, 13, 54, 0.4, 0.3, 0.0, 1.1, 0.1, 0.0, 0.1, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Soy Milk (unsweetened)', $dr, 33, 138, 2.9, 1.8, 0.3, 1.8, 0.2, 0.5, 0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Coconut Milk (canned)', $dr, 197, 824, 2.0, 2.8, 2.0, 21.3, 18.9, 0.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── MORE NUTS & SEEDS ──
			['Cashew Nuts', $ns, 553, 2314, 18.2, 30.2, 5.9, 43.9, 7.8, 3.3, 0.0, 0,0,0,0,1,0,0,0, 0,1,1,1,1,1],
			['Pistachio Nuts', $ns, 560, 2343, 20.2, 27.2, 7.7, 45.3, 5.6, 10.6, 0.0, 0,0,0,0,1,0,0,0, 0,1,1,1,1,1],
			['Hazelnuts', $ns, 628, 2628, 15.0, 16.7, 4.3, 60.8, 4.5, 9.7, 0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Pecan Nuts', $ns, 691, 2891, 9.2, 13.9, 4.0, 72.0, 6.2, 9.6, 0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Macadamia Nuts', $ns, 718, 3004, 7.9, 13.8, 4.6, 75.8, 12.1, 8.6, 0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Pine Nuts', $ns, 673, 2816, 13.7, 13.1, 3.6, 68.4, 4.9, 3.7, 0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Pumpkin Seeds', $ns, 559, 2339, 30.2, 10.7, 1.4, 49.1, 8.7, 6.0, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sunflower Seeds', $ns, 584, 2444, 20.8, 20.0, 2.6, 51.5, 4.5, 8.6, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sesame Seeds', $ns, 573, 2397, 17.7, 23.5, 0.3, 49.7, 7.0, 11.8, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Coconut (desiccated)', $ns, 604, 2527, 5.6, 23.7, 23.7, 62.0, 53.5, 16.3, 0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── MORE GRAINS ──
			['Jasmine Rice (raw)', $bc, 356, 1490, 7.1, 79.0, 0.1, 0.7, 0.2, 1.0, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Wild Rice (raw)', $bc, 357, 1493, 14.7, 74.9, 2.5, 1.1, 0.2, 6.2, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bulgar Wheat (raw)', $bc, 342, 1431, 12.3, 75.9, 0.4, 1.3, 0.2, 12.5, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pearl Barley (raw)', $bc, 354, 1481, 9.9, 73.5, 0.8, 1.2, 0.3, 15.6, 0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Rice Noodles (dried)', $bc, 360, 1506, 3.4, 83.2, 0.0, 0.6, 0.2, 1.6, 0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Polenta (raw)', $bc, 362, 1515, 8.1, 79.0, 0.6, 1.5, 0.2, 5.2, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sourdough Bread', $bc, 274, 1146, 10.6, 53.1, 3.8, 2.0, 0.4, 2.4, 1.1, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Rye Bread', $bc, 259, 1083, 8.5, 48.3, 3.9, 3.3, 0.6, 5.8, 0.9, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bagel (plain)', $bc, 257, 1075, 10.0, 50.5, 5.0, 1.6, 0.2, 2.3, 0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Croissant', $bc, 406, 1699, 8.2, 45.8, 11.3, 21.0, 12.0, 2.3, 0.8, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── MORE CONDIMENTS & SAUCES ──
			['Tahini', $co, 595, 2489, 17.0, 21.2, 0.5, 53.8, 7.5, 9.3, 0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Harissa Paste', $co, 76, 318, 2.0, 8.0, 4.0, 4.0, 0.5, 3.0, 1.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Miso Paste', $co, 199, 833, 11.7, 26.5, 6.2, 6.0, 1.0, 5.4, 3.7, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Sriracha Sauce', $co, 93, 389, 1.5, 18.5, 15.2, 1.0, 0.2, 1.8, 4.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Oyster Sauce', $co, 51, 213, 1.4, 11.0, 3.6, 0.0, 0.0, 0.0, 3.6, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hoisin Sauce', $co, 220, 920, 3.3, 44.1, 32.0, 3.4, 0.5, 2.1, 4.4, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Cranberry Sauce', $co, 153, 640, 0.1, 39.0, 34.4, 0.1, 0.0, 0.8, 0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mint Sauce', $co, 87, 364, 0.7, 20.1, 17.5, 0.1, 0.0, 2.5, 0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tartare Sauce', $co, 327, 1368, 0.9, 9.4, 4.7, 32.5, 2.5, 0.4, 1.2, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Hummus', $co, 166, 695, 7.9, 11.6, 0.3, 9.6, 1.3, 6.0, 0.7, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug = %s", $slug
			) ); // phpcs:ignore
			if ( $exists ) { continue; }

			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name' => $f[0], 'slug' => $slug, 'category_id' => $f[1],
				'energy_kcal' => $f[2], 'energy_kj' => $f[3], 'protein_g' => $f[4],
				'carbohydrate_g' => $f[5], 'of_which_sugars_g' => $f[6], 'fat_g' => $f[7],
				'of_which_saturates_g' => $f[8], 'fibre_g' => $f[9], 'salt_g' => $f[10],
				'allergen_fish' => $f[11], 'allergen_shellfish' => $f[12], 'allergen_dairy' => $f[13],
				'allergen_eggs' => $f[14], 'allergen_nuts' => $f[15], 'allergen_gluten' => $f[16],
				'allergen_soy' => $f[17], 'allergen_celery' => $f[18],
				'diet_keto' => $f[19], 'diet_paleo' => $f[20], 'diet_halal' => $f[21],
				'diet_kosher' => $f[22], 'diet_vegan' => $f[23], 'diet_vegetarian' => $f[24],
				'source_notes' => 'M&W 8th ed. / USDA FDC. Seeded v22.',
			] ); // phpcs:ignore
		}

		update_option( 'fcc_seed_version', 22 );
	}

	/**
	 * Seed v23: Add 200 more unique foods.
	 */
	public static function seed_v23(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 23 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── SEAFOOD EXTRAS (20) ──
			['Halibut (raw)',$fs,110,460,20.8,0.0,0.0,2.3,0.3,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Plaice (raw)',$fs,79,331,16.7,0.0,0.0,1.2,0.2,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Skate Wing (raw)',$fs,89,372,19.0,0.0,0.0,0.7,0.2,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Whitebait (raw)',$fs,100,418,18.0,0.0,0.0,2.9,0.7,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Sprat (raw)',$fs,126,527,17.5,0.0,0.0,5.8,1.5,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pollock (raw)',$fs,82,343,17.4,0.0,0.0,1.0,0.1,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Hake (raw)',$fs,82,343,17.2,0.0,0.0,1.3,0.2,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Sardine (canned in oil)',$fs,208,870,24.6,0.0,0.0,11.5,1.5,0.0,0.8, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pilchard (canned in tomato)',$fs,126,527,16.3,0.8,0.7,6.1,1.6,0.0,0.5, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Tuna (canned in brine)',$fs,99,414,23.5,0.0,0.0,0.6,0.2,0.0,0.9, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Tuna (canned in oil)',$fs,189,791,25.5,0.0,0.0,9.0,1.6,0.0,0.6, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Salmon (canned)',$fs,167,699,20.5,0.0,0.0,9.0,2.2,0.0,0.7, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Cuttlefish (raw)',$fs,79,331,16.2,0.8,0.0,0.7,0.1,0.0,0.4, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Sea Urchin (roe)',$fs,120,502,13.0,3.3,0.0,4.8,1.2,0.0,0.9, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Clam (raw)',$fs,74,310,12.8,2.6,0.0,1.0,0.1,0.0,1.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Abalone (raw)',$fs,105,439,17.1,6.0,0.0,0.8,0.2,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Smoked Trout',$fs,135,565,20.5,0.0,0.0,5.5,1.4,0.0,1.8, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Kipper (smoked herring)',$fs,205,858,19.0,0.0,0.0,13.9,3.3,0.0,1.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Rollmop Herring',$fs,203,849,14.6,3.5,2.8,14.6,3.5,0.0,1.2, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Surimi (crab sticks)',$fs,85,356,7.0,14.0,5.0,0.4,0.1,0.0,0.9, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── MEAT EXTRAS (15) ──
			['Beef Fillet (raw)',$mp,198,828,20.4,0.0,0.0,12.7,5.1,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Beef Rump Steak (raw)',$mp,150,628,21.4,0.0,0.0,7.0,2.9,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Beef Shin (raw)',$mp,139,582,20.4,0.0,0.0,6.0,2.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Oxtail (raw)',$mp,262,1096,17.0,0.0,0.0,21.3,9.3,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Lamb Shoulder (raw)',$mp,207,866,17.1,0.0,0.0,15.3,7.2,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pork Tenderloin (raw)',$mp,109,456,21.5,0.0,0.0,2.2,0.8,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Pork Ribs (raw)',$mp,277,1159,15.5,0.0,0.0,23.6,8.8,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Chorizo',$mp,455,1903,24.1,1.9,1.0,38.3,14.1,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pancetta',$mp,393,1644,14.0,0.0,0.0,37.0,13.5,0.0,2.6, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Prosciutto',$mp,195,816,26.0,0.0,0.0,9.4,3.3,0.0,5.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Salami',$mp,378,1582,22.6,1.2,0.0,31.0,11.2,0.0,4.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Black Pudding',$mp,297,1243,12.0,15.0,1.0,22.0,8.5,0.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Haggis',$mp,200,837,10.7,15.4,0.5,12.5,5.3,0.8,0.9, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Pheasant (raw)',$mp,133,556,24.4,0.0,0.0,3.6,1.2,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Guinea Fowl (raw)',$mp,110,460,20.6,0.0,0.0,2.5,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],

			// ── MORE VEG & SALAD (20) ──
			['Red Cabbage (raw)',$fv,31,130,1.4,7.4,3.8,0.2,0.0,2.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Savoy Cabbage (raw)',$fv,27,113,2.0,6.1,2.3,0.1,0.0,3.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Brussels Sprouts (raw)',$fv,43,180,3.4,8.9,2.2,0.3,0.1,3.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mange Tout (raw)',$fv,42,176,3.3,7.6,4.0,0.2,0.0,2.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Baby Corn (raw)',$fv,26,109,2.7,4.7,2.0,0.3,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Romaine Lettuce',$fv,17,71,1.2,3.3,1.2,0.3,0.0,2.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chicory (raw)',$fv,23,96,1.7,4.7,0.7,0.3,0.1,3.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Endive (raw)',$fv,17,71,1.3,3.4,0.3,0.2,0.0,3.1,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Radicchio (raw)',$fv,23,96,1.4,4.5,0.6,0.3,0.1,0.9,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Swiss Chard (raw)',$fv,19,79,1.8,3.7,1.1,0.2,0.0,1.6,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kohlrabi (raw)',$fv,27,113,1.7,6.2,2.6,0.1,0.0,3.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Daikon Radish (raw)',$fv,18,75,0.6,4.1,2.5,0.1,0.0,1.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chilli Pepper (raw)',$fv,40,167,1.9,8.8,5.3,0.4,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Jalapeño Pepper (raw)',$fv,29,121,0.9,6.5,4.1,0.4,0.0,2.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Yellow Pepper (raw)',$fv,27,113,1.0,6.3,0.0,0.2,0.1,0.9,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Green Pepper (raw)',$fv,20,84,0.9,4.6,2.4,0.2,0.1,1.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Shallot (raw)',$fv,72,301,2.5,16.8,7.9,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Chives (raw)',$fv,30,126,3.3,4.4,1.9,0.7,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Horseradish (raw)',$fv,48,201,1.2,11.3,7.9,0.7,0.2,3.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lotus Root (raw)',$fv,74,310,2.6,17.2,0.0,0.1,0.0,4.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── MORE FRUITS (15) ──
			['Persimmon (raw)',$fv,70,293,0.6,18.6,12.5,0.2,0.0,3.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lychee (raw)',$fv,66,276,0.8,16.5,15.2,0.4,0.1,1.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dragon Fruit (raw)',$fv,50,209,1.1,11.0,8.0,0.4,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Guava (raw)',$fv,68,285,2.6,14.3,8.9,1.0,0.3,5.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Papaya (raw)',$fv,43,180,0.5,10.8,7.8,0.3,0.1,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Star Fruit (raw)',$fv,31,130,1.0,6.7,4.0,0.3,0.0,2.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cantaloupe Melon (raw)',$fv,34,142,0.8,8.2,7.9,0.2,0.1,0.9,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Honeydew Melon (raw)',$fv,36,151,0.5,9.1,8.1,0.1,0.0,0.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Clementine (raw)',$fv,47,197,0.9,12.0,9.2,0.2,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Satsuma (raw)',$fv,44,184,0.8,11.2,8.6,0.1,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tangerine (raw)',$fv,53,222,0.8,13.3,10.6,0.3,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Damson (raw)',$fv,55,230,0.5,14.0,10.0,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Gooseberry (raw)',$fv,44,184,0.9,10.2,0.0,0.6,0.0,4.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Redcurrant (raw)',$fv,56,234,1.4,13.8,7.4,0.2,0.0,4.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Blackcurrant (raw)',$fv,63,264,1.4,15.4,0.0,0.4,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── DRIED FRUITS & EXTRAS (10) ──
			['Dried Cranberry',$fv,308,1289,0.1,82.4,72.6,1.4,0.1,5.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Mango',$fv,319,1335,1.5,78.6,66.3,0.8,0.2,2.4,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dried Fig',$fv,249,1042,3.3,63.9,47.9,0.9,0.2,9.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Prune (dried)',$fv,240,1004,2.2,63.9,38.1,0.4,0.0,7.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sultanas',$fv,275,1151,2.7,69.4,56.3,0.4,0.1,2.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mixed Peel (candied)',$fv,231,967,0.3,59.1,55.9,0.3,0.0,4.4,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Goji Berries (dried)',$fv,349,1460,14.3,77.1,45.6,0.4,0.0,13.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Banana Chips (dried)',$sc,519,2172,2.3,58.4,35.3,33.6,28.9,7.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Trail Mix',$sc,462,1933,13.8,44.5,28.0,28.0,4.5,4.8,0.1, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Dark Chocolate (70%)',$sc,598,2502,7.8,45.9,24.0,42.6,24.5,10.9,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── BREAD & BAKERY EXTRAS (15) ──
			['Naan Bread',$bc,320,1339,9.6,50.1,3.5,9.5,1.5,2.0,0.9, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Chapati (plain)',$bc,328,1372,8.8,55.6,2.5,7.8,1.0,1.7,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Rice Cake',$bc,387,1619,8.2,81.5,0.3,2.8,0.6,4.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Crumpet',$bc,198,828,6.0,38.1,3.2,0.9,0.3,1.7,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['English Muffin',$bc,227,950,8.7,44.8,5.0,1.8,0.3,2.7,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Scone (plain)',$bc,364,1523,6.9,47.8,11.2,16.9,5.0,1.4,1.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Brioche',$bc,346,1448,9.3,43.5,10.2,14.9,8.5,1.4,0.8, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Panini Bread',$bc,270,1130,9.5,48.0,2.5,4.5,1.2,2.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Ciabatta',$bc,271,1134,9.4,50.0,1.8,3.8,0.6,2.4,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Focaccia',$bc,283,1184,7.7,43.0,1.5,8.9,1.3,2.2,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pumpernickel',$bc,250,1046,8.7,47.5,2.9,3.3,0.5,7.0,0.9, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Granola (plain)',$bc,471,1970,10.5,64.0,21.0,19.6,3.0,5.3,0.1, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],
			['Corn Tortilla',$bc,218,912,5.7,44.6,0.7,2.9,0.4,5.2,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Matzo',$bc,395,1653,10.5,83.5,1.0,1.4,0.2,3.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Crackers (cream)',$sc,440,1841,9.5,68.0,2.0,14.5,7.0,2.5,1.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ── DRINKS (15) ──
			['Green Tea (brewed)',$dr,1,4,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Black Tea (brewed)',$dr,1,4,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Black Coffee (brewed)',$dr,2,8,0.3,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Espresso',$dr,9,38,0.1,1.7,0.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Orange Juice (fresh)',$dr,45,188,0.7,10.4,8.4,0.2,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Apple Juice',$dr,46,192,0.1,11.3,9.6,0.1,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cranberry Juice',$dr,46,192,0.4,12.2,12.1,0.1,0.0,0.1,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tomato Juice',$dr,17,71,0.8,3.5,2.6,0.1,0.0,0.4,0.4, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Coconut Water',$dr,19,79,0.7,3.7,2.6,0.2,0.2,1.1,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kombucha',$dr,27,113,0.0,7.0,5.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tonic Water',$dr,34,142,0.0,8.7,8.7,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ginger Beer',$dr,38,159,0.0,9.7,9.3,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hot Chocolate (powder)',$dr,390,1632,5.4,78.0,60.0,5.5,3.3,5.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Matcha Powder',$dr,324,1355,30.6,38.9,0.0,5.3,1.0,38.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chai Tea (brewed)',$dr,1,4,0.0,0.2,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── MORE LEGUMES & WORLD FOODS (15) ──
			['Butter Beans (canned)',$lp,77,322,5.4,12.6,0.4,0.5,0.1,4.6,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cannellini Beans (canned)',$lp,91,381,6.7,13.8,0.5,0.5,0.1,5.3,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pinto Beans (canned)',$lp,97,406,6.3,15.4,0.4,0.7,0.1,5.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Broad Beans (frozen)',$lp,88,368,7.9,12.4,0.9,0.7,0.1,5.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Split Peas (dried)',$lp,341,1427,24.6,60.4,8.0,1.2,0.2,25.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mung Bean Sprouts',$fv,31,130,3.0,5.9,4.1,0.2,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Tempeh',$lp,192,803,20.3,7.6,0.0,10.8,2.5,0.0,0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Seitan',$lp,370,1548,75.2,13.8,0.0,1.9,0.3,0.6,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Paneer',$de,265,1109,18.3,1.2,0.0,20.8,13.4,0.0,0.3, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Ghee',$fo,900,3766,0.0,0.0,0.0,100.0,61.9,0.0,0.0, 0,0,1,0,0,0,0,0, 1,1,1,1,0,1],
			['Coconut Cream',$dr,230,962,2.3,3.3,1.5,24.0,21.3,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Tamarind Paste',$co,239,1000,2.8,62.5,38.0,0.6,0.3,5.1,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Wasabi Paste',$co,109,456,1.3,23.5,8.4,0.6,0.0,6.1,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pickled Ginger',$co,20,84,0.2,4.6,2.3,0.0,0.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Capers (drained)',$co,23,96,2.4,4.9,0.4,0.9,0.2,3.2,8.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── MORE SNACKS (10) ──
			['Popcorn (air-popped)',$sc,387,1619,12.9,77.8,0.9,4.5,0.6,14.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rice Crackers',$sc,393,1644,7.5,82.0,2.8,3.5,0.5,1.4,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Breadsticks',$sc,412,1724,12.0,69.0,3.0,10.5,1.5,3.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Oatcakes',$sc,437,1829,10.7,57.8,3.2,18.3,2.8,7.2,1.3, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Pretzels',$sc,380,1590,9.2,79.2,2.0,3.5,0.8,2.8,2.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pork Scratchings',$sc,544,2276,61.3,0.3,0.0,31.5,11.2,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Milk Chocolate',$sc,535,2239,7.7,56.5,51.5,30.4,18.5,2.4,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['White Chocolate',$sc,539,2255,5.9,59.2,59.0,30.9,18.5,0.2,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Flapjack',$sc,458,1916,5.2,56.5,25.0,24.2,10.2,4.0,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Fruit & Nut Mix',$sc,475,1988,12.0,52.0,38.0,27.0,4.0,5.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],

			// ── HERBS FRESH (10) ──
			['Fresh Basil',$fv,23,96,3.2,2.7,0.3,0.6,0.1,1.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Coriander',$fv,23,96,2.1,3.7,0.9,0.5,0.0,2.8,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Parsley',$fv,36,151,3.0,6.3,0.9,0.8,0.1,3.3,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Mint',$fv,44,184,3.3,8.4,0.0,0.7,0.2,6.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Rosemary',$fv,131,548,3.3,20.7,0.0,5.9,2.8,14.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Thyme',$fv,101,423,5.6,24.5,0.0,1.7,0.5,14.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Dill',$fv,43,180,3.5,7.0,0.0,1.1,0.0,2.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Sage',$fv,315,1318,10.6,60.7,1.7,12.7,7.0,40.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fresh Tarragon',$fv,295,1234,22.8,50.2,0.0,7.2,1.9,7.4,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Lemongrass (raw)',$fv,99,414,1.8,25.3,0.0,0.5,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug = %s", $slug
			) ); // phpcs:ignore
			if ( $exists ) { continue; }
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name' => $f[0], 'slug' => $slug, 'category_id' => $f[1],
				'energy_kcal' => $f[2], 'energy_kj' => $f[3], 'protein_g' => $f[4],
				'carbohydrate_g' => $f[5], 'of_which_sugars_g' => $f[6], 'fat_g' => $f[7],
				'of_which_saturates_g' => $f[8], 'fibre_g' => $f[9], 'salt_g' => $f[10],
				'allergen_fish' => $f[11], 'allergen_shellfish' => $f[12], 'allergen_dairy' => $f[13],
				'allergen_eggs' => $f[14], 'allergen_nuts' => $f[15], 'allergen_gluten' => $f[16],
				'allergen_soy' => $f[17], 'allergen_celery' => $f[18],
				'diet_keto' => $f[19], 'diet_paleo' => $f[20], 'diet_halal' => $f[21],
				'diet_kosher' => $f[22], 'diet_vegan' => $f[23], 'diet_vegetarian' => $f[24],
				'source_notes' => 'M&W 8th ed. / USDA FDC. Seeded v23.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 23 );
	}

	/** Seed v24: 150 more unique foods — batch 1 of 2. */
	public static function seed_v24(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 24 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── COOKED MEATS & DELI (20) ──
			['Roast Chicken (meat only)',$mp,177,741,27.3,0.0,0.0,7.5,2.2,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Corned Beef',$mp,205,858,25.1,0.0,0.0,10.9,5.2,0.0,2.2, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Pastrami',$mp,133,556,21.8,1.5,0.8,4.0,1.6,0.0,2.8, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Bresaola',$mp,151,632,33.1,0.0,0.0,2.1,0.8,0.0,4.5, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Mortadella',$mp,311,1301,16.4,1.5,0.0,27.5,10.1,0.0,2.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Turkey Ham',$mp,120,502,18.0,2.0,1.5,3.9,1.2,0.0,2.2, 0,0,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Liver Pâté',$mp,319,1335,11.5,1.5,0.5,30.0,10.5,0.0,1.3, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Chicken Drumstick (raw)',$mp,161,673,18.2,0.0,0.0,9.2,2.6,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Lamb Shank (raw)',$mp,178,745,18.5,0.0,0.0,11.3,5.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Veal Escalope (raw)',$mp,110,460,21.3,0.0,0.0,2.2,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Wild Boar (raw)',$mp,122,510,21.5,0.0,0.0,3.3,1.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Bison (raw)',$mp,109,456,21.6,0.0,0.0,1.8,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Ostrich (raw)',$mp,111,464,21.8,0.0,0.0,2.0,0.8,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Smoked Turkey Breast',$mp,104,435,19.0,1.8,1.0,1.6,0.4,0.0,2.0, 0,0,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Chicken Sausage (raw)',$mp,159,665,15.2,3.5,0.8,9.3,2.5,0.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Beef Jerky',$sc,410,1715,33.2,11.0,9.0,25.6,10.0,0.0,5.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Duck Leg (raw, with skin)',$mp,211,883,16.8,0.0,0.0,15.7,4.4,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Goat Meat (raw)',$mp,109,456,20.6,0.0,0.0,2.3,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Lamb Neck (raw)',$mp,222,929,16.5,0.0,0.0,17.1,7.8,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Bone Marrow (raw)',$mp,786,3289,6.7,0.0,0.0,84.4,33.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── MORE SEAFOOD & PREPARED FISH (15) ──
			['Fish Fingers (frozen)',$fs,194,812,12.5,18.8,0.6,8.0,1.0,0.7,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Fish Cake (chilled)',$fs,168,703,8.5,16.2,0.7,7.8,1.2,0.8,0.8, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kedgeree',$tk,160,669,9.0,14.0,0.3,7.5,2.5,0.4,0.6, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Fish Pie (chilled)',$tk,110,460,7.5,9.5,0.8,4.8,2.0,0.5,0.5, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Prawn Cocktail',$fs,180,753,6.5,7.0,4.5,14.0,1.5,0.3,1.2, 1,1,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Smoked Eel',$fs,281,1176,18.4,0.0,0.0,22.8,4.6,0.0,1.8, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Anchovies (canned in oil, drained)',$fs,210,879,28.9,0.0,0.0,9.7,2.2,0.0,4.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Fish Stock (liquid)',$co,4,17,0.7,0.2,0.0,0.0,0.0,0.0,0.4, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Nori Seaweed (dried)',$fv,35,146,5.8,5.1,0.5,0.3,0.1,0.3,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Wakame Seaweed (dried)',$fv,45,188,3.0,9.1,0.5,0.6,0.1,0.5,6.6, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Dulse Seaweed (dried)',$fv,217,908,16.0,44.0,0.0,1.5,0.3,6.0,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kelp (dried)',$fv,43,180,1.7,9.6,0.6,0.6,0.2,1.3,2.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Crab Paste',$co,95,397,11.0,2.0,0.5,5.0,0.8,0.0,2.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Shrimp Paste',$co,169,707,28.0,3.0,1.0,3.5,0.8,0.0,12.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Anchovy Paste',$co,188,786,25.0,0.5,0.0,9.0,2.0,0.0,8.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── EGGS & EGG PRODUCTS (8) ──
			['Egg White (raw)',$de,52,218,10.9,0.7,0.7,0.2,0.0,0.0,0.4, 0,0,0,1,0,0,0,0, 1,1,1,1,0,1],
			['Egg Yolk (raw)',$de,322,1348,15.9,3.6,0.6,26.5,9.6,0.0,0.1, 0,0,0,1,0,0,0,0, 1,1,1,1,0,1],
			['Quail Egg (raw)',$de,158,661,13.1,0.4,0.4,11.1,3.6,0.0,0.1, 0,0,0,1,0,0,0,0, 1,1,1,0,0,1],
			['Duck Egg (raw)',$de,185,774,13.0,1.4,1.1,13.8,3.7,0.0,0.2, 0,0,0,1,0,0,0,0, 1,1,0,0,0,1],
			['Scotch Egg',$tk,241,1008,12.0,14.5,0.8,15.2,4.8,0.8,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Omelette (plain, 2 eggs)',$de,154,644,10.6,0.7,0.5,12.2,3.4,0.0,0.4, 0,0,0,1,0,0,0,0, 1,1,1,1,0,1],
			['Scrambled Eggs',$de,148,619,9.6,1.8,1.6,11.2,4.0,0.0,0.5, 0,0,1,1,0,0,0,0, 1,0,1,1,0,1],
			['Egg Fried Rice',$tk,186,778,4.5,27.5,0.5,6.4,1.2,0.4,0.7, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],

			// ── WORLD GRAINS & STARCHES (12) ──
			['Tapioca (dried)',$bc,358,1498,0.2,88.7,3.4,0.0,0.0,0.9,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Buckwheat (raw)',$bc,343,1435,13.3,71.5,0.0,3.4,0.7,10.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Millet (raw)',$bc,378,1582,11.0,72.8,1.7,4.2,0.7,8.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Amaranth (raw)',$bc,371,1552,13.6,65.2,1.7,7.0,1.5,6.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Spelt Flour',$bc,338,1414,14.6,70.2,6.8,2.4,0.4,10.7,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Chickpea Flour (gram flour)',$lp,387,1619,22.4,57.8,10.9,6.7,0.7,10.8,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coconut Flour',$bc,443,1854,19.3,60.0,8.4,14.7,11.8,39.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Almond Flour',$ns,571,2389,21.4,19.7,4.8,50.6,3.9,10.5,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Soba Noodles (dried)',$bc,336,1406,14.4,72.5,0.0,0.7,0.1,0.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Vermicelli (rice)',$bc,364,1523,3.4,83.2,0.0,0.6,0.2,1.6,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Udon Noodles (cooked)',$bc,99,414,2.6,20.6,0.4,0.1,0.0,0.9,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Glass Noodles (dried)',$bc,334,1397,0.1,82.3,0.0,0.1,0.0,0.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── PREPARED/CONVENIENCE (15) ──
			['Baked Potato (flesh only)',$fv,93,389,2.5,21.2,1.2,0.1,0.0,1.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mashed Potato (with butter)',$fv,104,435,1.8,15.0,0.8,4.3,2.5,1.1,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Chips (oven baked)',$fv,162,678,2.9,27.7,0.4,4.4,0.6,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['French Fries (deep fried)',$fv,274,1146,3.4,36.0,0.3,13.1,2.3,3.2,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hash Browns (cooked)',$fv,213,891,2.2,23.5,0.5,12.5,1.6,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Roast Potatoes',$fv,149,623,2.9,21.3,0.8,6.1,0.8,1.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coleslaw',$co,162,678,1.0,8.5,7.5,13.8,1.4,1.1,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Potato Salad',$co,143,598,1.8,13.5,2.5,9.0,1.0,1.2,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Guacamole',$co,160,669,2.0,8.5,0.7,14.2,2.0,6.7,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Tzatziki',$co,66,276,3.5,3.8,3.0,4.0,2.5,0.2,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Raita',$co,55,230,2.5,4.5,3.5,3.0,1.8,0.2,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Salsa (tomato)',$co,36,151,1.5,7.0,4.0,0.3,0.0,1.3,0.8, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chutney (mango)',$co,205,858,0.4,50.0,45.0,0.3,0.0,1.5,1.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Piccalilli',$co,95,397,0.8,18.0,14.0,2.0,0.1,0.5,1.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Branston Pickle',$co,130,544,0.5,30.0,24.0,0.3,0.0,0.8,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── MUSHROOM VARIETIES (8) ──
			['Shiitake Mushroom (raw)',$fv,34,142,2.2,6.8,2.4,0.5,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Oyster Mushroom (raw)',$fv,33,138,3.3,6.1,1.1,0.4,0.1,2.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Portobello Mushroom (raw)',$fv,22,92,2.1,3.9,2.5,0.4,0.1,1.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chestnut Mushroom (raw)',$fv,22,92,3.1,3.3,1.7,0.3,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['King Oyster Mushroom (raw)',$fv,35,146,3.3,6.2,1.0,0.4,0.1,2.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Enoki Mushroom (raw)',$fv,37,155,2.7,7.8,0.2,0.3,0.0,2.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Dried Porcini Mushroom',$fv,296,1239,30.0,54.0,4.0,2.0,0.3,18.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Truffle (fresh)',$fv,284,1189,5.5,40.0,2.0,0.5,0.1,16.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── TOFU & SOY PRODUCTS (7) ──
			['Silken Tofu',$lp,55,230,4.8,2.0,0.8,2.7,0.4,0.0,0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Smoked Tofu',$lp,144,603,15.7,1.2,0.5,8.7,1.3,0.4,0.8, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Soy Mince (TVP, dried)',$lp,327,1368,52.0,35.0,6.0,1.0,0.1,18.0,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Miso Soup (instant)',$co,20,84,1.3,2.6,0.8,0.6,0.1,0.3,1.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Soy Cream',$de,118,494,1.2,6.0,2.5,10.0,1.5,0.0,0.1, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Soy Yoghurt',$de,54,226,3.8,5.2,2.5,1.9,0.3,0.5,0.1, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Natto',$lp,211,883,17.7,14.4,4.9,11.0,1.6,5.4,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── COOKING STOCKS & BASES (7) ──
			['Chicken Stock (liquid)',$co,8,33,0.6,0.5,0.3,0.3,0.1,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Beef Stock (liquid)',$co,8,33,0.7,0.4,0.2,0.3,0.1,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Vegetable Stock (liquid)',$co,5,21,0.2,0.8,0.5,0.1,0.0,0.0,0.4, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Dashi Stock',$co,3,13,0.3,0.2,0.0,0.0,0.0,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Bouillon Powder',$co,229,958,16.0,17.0,3.0,12.0,6.0,0.0,40.0, 0,0,0,0,0,0,0,1, 0,0,1,1,0,0],
			['Tomato Purée',$co,82,343,4.3,17.7,14.1,0.5,0.1,3.6,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Passata',$co,24,100,1.3,4.6,3.5,0.1,0.0,1.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── SNACK & BAKERY EXTRAS (8) ──
			['Marzipan',$sc,400,1674,5.0,47.0,43.0,22.0,1.7,3.0,0.0, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Fondant Icing',$sc,395,1653,0.0,95.5,95.0,0.5,0.3,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Meringue',$sc,384,1607,5.3,95.4,95.4,0.0,0.0,0.0,0.1, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Shortbread',$sc,502,2100,5.9,63.1,17.0,26.0,15.5,1.8,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Digestive Biscuit',$sc,466,1950,7.0,62.0,16.8,21.3,10.5,3.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Rich Tea Biscuit',$sc,438,1833,7.5,72.0,22.0,13.0,6.5,2.2,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Jaffa Cake',$sc,377,1577,4.3,68.0,43.0,8.7,4.8,1.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Croutons',$sc,407,1703,10.5,64.5,4.0,12.5,2.0,3.0,1.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v24.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 24 );
	}

	/** Seed v25: 150 more unique foods — batch 2 of 2. */
	public static function seed_v25(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 25 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── BREAKFAST & CEREAL (15) ──
			['Cornflakes',$bc,376,1573,7.5,84.0,8.0,0.6,0.1,3.0,1.1, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bran Flakes',$bc,333,1393,10.0,67.0,18.0,2.5,0.5,15.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Weetabix',$bc,362,1515,11.5,68.0,4.4,2.7,0.6,10.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Muesli (no added sugar)',$bc,363,1519,10.4,66.2,15.8,6.2,1.0,7.6,0.1, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],
			['Shredded Wheat',$bc,351,1469,12.3,69.5,0.7,2.8,0.5,11.6,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Puffed Rice',$bc,382,1598,6.3,87.4,0.1,0.5,0.1,1.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pancake (plain, from batter)',$bc,227,950,6.3,35.0,7.0,7.0,1.5,1.0,0.8, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Waffle (plain)',$bc,291,1217,7.0,33.0,6.0,14.0,3.0,0.8,0.7, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['French Toast',$bc,229,958,7.7,25.0,5.0,11.4,3.1,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Porridge (made with water)',$bc,46,192,1.5,8.1,0.1,0.8,0.1,0.9,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Overnight Oats (plain)',$bc,71,297,2.5,12.0,1.5,1.5,0.3,1.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Peanut Butter',$ns,588,2460,25.1,20.0,6.6,50.4,8.2,5.4,0.5, 0,0,0,0,1,0,0,0, 1,0,1,1,1,1],
			['Almond Butter',$ns,614,2569,21.0,18.8,4.4,55.5,4.2,10.5,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Cashew Butter',$ns,587,2456,17.6,27.6,4.8,49.4,9.8,2.0,0.0, 0,0,0,0,1,0,0,0, 0,1,1,1,1,1],
			['Jam (strawberry)',$co,244,1021,0.4,60.0,48.5,0.1,0.0,1.1,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── TAKEAWAY & WORLD FOOD (25) ──
			['Pizza (margherita, thin)',$tk,234,979,11.0,28.0,3.5,8.5,3.8,1.5,1.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Sushi Rice (cooked)',$bc,130,544,2.4,28.6,3.5,0.3,0.1,0.4,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Spring Roll (vegetable)',$tk,217,908,3.5,24.0,2.5,12.0,1.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Samosa (vegetable)',$tk,261,1092,4.5,27.0,3.0,15.0,2.5,2.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Falafel (baked)',$tk,333,1393,13.3,31.8,0.0,17.8,2.4,0.0,0.9, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Onion Bhaji',$tk,241,1008,4.5,21.0,3.5,15.5,1.8,2.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pakora (vegetable)',$tk,252,1054,4.8,23.0,2.0,16.0,2.0,2.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Gyoza (pork)',$tk,215,900,8.5,24.0,2.0,9.0,3.0,1.0,0.8, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Dim Sum (prawn har gow)',$tk,120,502,5.0,14.0,0.5,4.5,0.8,0.3,0.6, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tempura Prawns',$tk,230,962,11.0,18.0,1.0,12.5,1.5,0.5,0.5, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Pad Thai (prawn)',$tk,150,628,8.0,18.0,4.0,5.5,0.8,1.0,1.5, 1,1,0,1,1,0,1,0, 0,0,1,0,0,0],
			['Fried Rice (chicken)',$tk,174,728,8.0,24.0,0.5,5.0,1.0,0.5,0.8, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Doner Kebab Meat',$tk,215,900,13.0,3.5,1.0,17.0,7.5,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chicken Tikka',$tk,148,619,24.5,3.0,1.5,4.0,1.0,0.5,1.0, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Lamb Kofta',$tk,245,1025,14.0,8.0,1.5,17.5,7.5,0.8,1.2, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chicken Satay',$tk,195,816,20.0,5.5,3.0,10.0,2.5,0.5,1.0, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Taco Shell (hard)',$bc,473,1979,6.5,62.0,1.0,22.5,3.5,5.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Refried Beans (canned)',$lp,89,372,5.4,13.2,0.5,1.5,0.5,4.8,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tortilla Chips',$sc,489,2046,7.0,58.0,1.5,25.0,3.5,4.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Jalapeño (pickled)',$co,28,117,0.9,6.3,3.0,0.6,0.1,2.6,3.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sour Cream',$de,193,808,2.1,4.3,3.5,19.4,11.5,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Crème Fraîche',$de,292,1222,2.3,2.4,2.4,30.0,19.5,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Quark',$de,66,276,12.0,4.0,4.0,0.2,0.1,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Skyr (Icelandic yoghurt)',$de,63,264,11.0,3.5,3.0,0.2,0.1,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Kefir (plain)',$dr,41,172,3.3,4.6,4.6,1.0,0.7,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── SWEETENERS & BAKING (12) ──
			['Maple Syrup',$co,260,1088,0.0,67.0,60.5,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Agave Nectar',$co,310,1297,0.1,76.4,68.0,0.5,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Molasses (black treacle)',$co,290,1213,0.0,74.7,74.7,0.1,0.0,0.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Stevia (powder)',$co,0,0,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Condensed Milk (sweetened)',$de,321,1343,8.1,55.5,55.5,8.7,5.5,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Evaporated Milk',$de,134,561,6.8,10.0,10.0,7.6,4.6,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Custard Powder',$bc,354,1481,0.4,92.0,0.2,0.7,0.0,0.3,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gelatine (powder)',$co,335,1402,85.6,0.0,0.0,0.1,0.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Yeast (dried)',$bc,325,1360,40.4,41.2,0.0,7.6,1.0,26.9,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baking Powder',$co,53,222,0.0,27.7,0.0,0.0,0.0,0.0,27.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bicarbonate of Soda',$co,0,0,0.0,0.0,0.0,0.0,0.0,0.0,57.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cream of Tartar',$co,258,1079,0.0,62.0,0.0,0.0,0.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── DRINKS EXTRA (18) ──
			['Lemonade (fizzy)',$dr,42,176,0.0,10.7,10.7,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cola (regular)',$dr,42,176,0.0,10.6,10.6,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Diet Cola',$dr,1,4,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,0,1,1,1,1],
			['Energy Drink (regular)',$dr,45,188,0.0,11.3,11.3,0.0,0.0,0.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sports Drink',$dr,25,105,0.0,6.4,6.0,0.0,0.0,0.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sparkling Water',$dr,0,0,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Elderflower Cordial',$dr,142,594,0.0,35.0,34.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ribena (original)',$dr,46,192,0.0,10.0,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Smoothie (berry)',$dr,52,218,0.5,12.5,11.5,0.2,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Protein Shake (whey, ready)',$dr,67,280,10.0,3.5,3.0,1.2,0.7,0.5,0.2, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Milkshake (chocolate)',$dr,87,364,3.3,14.5,13.5,2.0,1.2,0.3,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Iced Tea (sweetened)',$dr,30,126,0.0,7.5,7.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Carrot Juice',$dr,40,167,0.9,9.3,3.9,0.2,0.0,0.8,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Beetroot Juice',$dr,43,180,1.0,9.5,7.0,0.2,0.0,0.8,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Peppermint Tea',$dr,1,4,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chamomile Tea',$dr,1,4,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Rooibos Tea',$dr,1,4,0.0,0.1,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Turmeric Latte (powder)',$dr,410,1715,11.0,60.0,34.0,15.0,10.0,5.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,0,1],

			// ── ADDITIONAL VEG/ROOT (15) ──
			['Yam (raw)',$fv,118,494,1.5,27.9,0.5,0.2,0.0,4.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Plantain (raw)',$fv,122,510,1.3,31.9,15.0,0.4,0.1,2.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cassava (raw)',$fv,160,669,1.4,38.1,1.7,0.3,0.1,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Taro (raw)',$fv,112,469,1.5,26.5,0.4,0.2,0.0,4.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Water Chestnut (canned)',$fv,97,406,1.4,23.9,2.5,0.1,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Bamboo Shoots (canned)',$fv,19,79,2.6,3.0,1.1,0.3,0.1,1.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Hearts of Palm (canned)',$fv,28,117,2.5,4.6,0.0,0.6,0.1,2.4,0.6, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Jackfruit (raw)',$fv,95,397,1.7,23.3,19.1,0.6,0.2,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Breadfruit (raw)',$fv,103,431,1.1,27.1,11.0,0.2,0.1,4.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Eddoe (raw)',$fv,94,393,1.4,22.4,0.0,0.1,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Chayote (raw)',$fv,19,79,0.8,4.5,1.7,0.1,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Salsify (raw)',$fv,82,343,3.3,18.6,3.0,0.2,0.0,3.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Jerusalem Artichoke (raw)',$fv,73,305,2.0,17.4,9.6,0.0,0.0,1.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sorrel (raw)',$fv,22,92,2.0,3.4,0.0,0.7,0.0,2.9,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Purslane (raw)',$fv,20,84,2.0,3.4,0.0,0.4,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── PRESERVED/PICKLED (15) ──
			['Olives (green, in brine)',$fv,145,607,1.0,3.8,0.5,15.3,2.0,3.3,3.8, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Olives (black, in brine)',$fv,115,481,0.8,6.3,0.0,10.7,1.4,3.2,2.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sun-dried Tomatoes',$fv,258,1079,14.1,55.8,37.6,3.0,0.4,12.3,2.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Gherkin (pickled)',$fv,14,59,0.3,2.3,1.1,0.2,0.0,1.2,1.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sauerkraut',$fv,19,79,0.9,4.3,1.8,0.1,0.0,2.9,0.7, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kimchi',$fv,15,63,1.1,2.4,1.1,0.5,0.1,1.6,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Artichoke Hearts (canned)',$fv,33,138,2.4,5.2,0.7,0.3,0.1,3.5,0.4, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Roasted Red Peppers (jarred)',$fv,28,117,0.9,5.3,4.2,0.3,0.1,1.8,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sundried Tomato Paste',$co,213,891,5.0,23.0,18.5,11.0,1.5,5.5,2.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Pickled Onions',$co,24,100,0.9,4.9,3.5,0.1,0.0,1.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pickled Beetroot',$fv,38,159,1.2,8.5,7.5,0.1,0.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pickled Herring',$fs,218,912,14.2,4.5,3.0,16.5,2.2,0.0,2.0, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Pickled Ginger (Gari)',$co,20,84,0.2,4.6,2.3,0.0,0.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mango Pickle (Indian)',$co,220,920,2.0,12.0,8.0,18.0,2.0,2.5,5.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lime Pickle (Indian)',$co,240,1004,1.5,10.0,6.0,22.0,2.5,2.0,6.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v25.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 25 );
	}

	/** Seed v26: 200 more unique foods. */
	public static function seed_v26(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 26 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── COOKED VEGETABLES (15) ──
			['Roasted Cauliflower',$fv,48,201,2.5,7.0,2.8,1.2,0.2,2.8,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Steamed Broccoli',$fv,35,146,2.4,7.2,1.4,0.4,0.1,3.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Grilled Courgette',$fv,24,100,1.6,3.4,2.3,0.7,0.1,1.2,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sautéed Spinach',$fv,32,134,3.1,2.0,0.3,1.5,0.2,2.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Caramelised Onion',$fv,98,410,1.0,18.0,12.0,2.5,0.3,1.5,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Roasted Beetroot',$fv,55,230,1.8,10.5,8.5,0.5,0.1,2.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Creamed Sweetcorn',$fv,82,343,2.2,18.0,5.5,0.7,0.1,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mushy Peas',$fv,81,339,5.8,13.5,1.5,0.7,0.1,1.8,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bubble and Squeak',$fv,85,356,2.5,10.0,1.5,3.5,0.5,2.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ratatouille',$fv,53,222,1.3,6.5,4.5,2.5,0.4,1.8,0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cauliflower Cheese',$fv,115,481,5.5,5.0,2.5,8.0,4.8,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Dauphinoise Potatoes',$fv,145,607,3.0,13.5,1.0,9.0,5.5,0.8,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Potato Wedges (baked)',$fv,130,544,2.5,22.0,0.5,3.5,0.4,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Stuffed Pepper (vegetable)',$fv,78,326,2.5,12.0,5.5,2.0,0.3,2.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Roasted Parsnip',$fv,105,439,1.5,22.5,7.0,1.5,0.2,4.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── COOKED GRAINS & PASTA (15) ──
			['Cooked White Rice',$bc,130,544,2.7,28.2,0.0,0.3,0.1,0.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Brown Rice',$bc,112,469,2.6,23.5,0.4,0.9,0.2,1.8,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Pasta (white)',$bc,131,548,5.0,25.4,0.6,0.7,0.1,1.8,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cooked Pasta (wholemeal)',$bc,124,519,5.3,26.5,0.8,0.5,0.1,3.2,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cooked Couscous',$bc,112,469,3.8,23.2,0.1,0.2,0.0,1.4,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cooked Quinoa',$bc,120,502,4.4,21.3,0.9,1.9,0.2,2.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cooked Lentils (green)',$lp,116,485,9.0,20.1,1.8,0.4,0.1,7.9,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Lentils (red)',$lp,116,485,7.6,20.1,1.5,0.4,0.1,1.9,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Pearl Barley',$bc,123,515,2.3,28.2,0.3,0.4,0.1,3.8,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cooked Bulgur Wheat',$bc,83,347,3.1,18.6,0.1,0.2,0.0,4.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cooked Polenta',$bc,70,293,1.6,15.0,0.1,0.3,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Risotto Rice (arborio, raw)',$bc,360,1506,6.5,80.0,0.1,0.5,0.2,2.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sushi Rice (raw)',$bc,350,1464,7.0,77.0,0.1,0.5,0.1,1.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Chickpeas',$lp,164,686,8.9,27.4,4.8,2.6,0.3,7.6,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cooked Black Beans',$lp,132,552,8.9,23.7,0.3,0.5,0.1,8.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── SOUPS (15) ──
			['Tomato Soup (canned)',$tk,55,230,0.8,9.3,5.5,1.5,0.2,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chicken Soup (canned)',$tk,39,163,2.5,4.0,0.5,1.5,0.4,0.3,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mushroom Soup (canned)',$tk,52,218,0.8,4.5,0.8,3.5,1.0,0.3,0.6, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Minestrone Soup',$tk,40,167,1.8,7.0,2.0,0.8,0.1,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Leek and Potato Soup',$tk,45,188,1.0,7.0,1.0,1.5,0.8,0.8,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Butternut Squash Soup',$tk,42,176,0.8,8.0,3.0,1.0,0.5,1.0,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pea and Mint Soup',$tk,62,259,3.5,8.5,3.0,1.5,0.3,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['French Onion Soup',$tk,38,159,1.5,5.5,3.0,1.0,0.5,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Gazpacho',$tk,30,126,0.7,5.5,4.0,0.8,0.1,0.8,0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Clam Chowder',$tk,80,335,3.0,8.5,1.5,3.5,1.5,0.5,0.6, 1,1,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Thai Coconut Soup (Tom Kha)',$tk,85,356,3.5,5.0,2.0,6.0,4.5,0.5,0.7, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Miso Soup (fresh)',$tk,22,92,1.5,2.8,0.8,0.6,0.1,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Scotch Broth',$tk,42,176,2.8,5.5,0.5,1.0,0.4,1.0,0.4, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Cock-a-Leekie Soup',$tk,35,146,2.5,4.0,0.5,1.0,0.3,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Cullen Skink',$tk,95,397,6.5,7.0,1.0,4.5,2.5,0.3,0.6, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],

			// ── COOKED SEAFOOD (20) ──
			['Grilled Salmon Fillet',$fs,208,870,20.4,0.0,0.0,13.4,2.3,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pan-fried Sea Bass',$fs,124,519,20.5,0.0,0.0,4.5,0.8,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Steamed Cod',$fs,96,402,21.0,0.0,0.0,0.9,0.1,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Baked Haddock',$fs,104,435,22.8,0.0,0.0,0.8,0.1,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Grilled Tuna Steak',$fs,132,552,28.2,0.0,0.0,1.3,0.3,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Poached Salmon',$fs,194,812,21.6,0.0,0.0,11.5,2.0,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Grilled Mackerel',$fs,239,1000,20.8,0.0,0.0,16.7,3.9,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Boiled Prawns',$fs,99,414,22.0,0.0,0.0,0.9,0.2,0.0,1.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Grilled Squid',$fs,120,502,18.0,3.1,0.0,3.1,0.8,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Steamed Mussels',$fs,86,360,11.9,3.7,0.0,2.2,0.4,0.0,0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Grilled Lobster Tail',$fs,98,410,20.5,0.0,0.0,1.2,0.3,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Seared Scallops',$fs,94,393,17.0,3.5,0.0,1.0,0.2,0.0,0.7, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Beer-battered Cod',$fs,195,816,13.0,14.0,0.5,10.0,1.5,0.5,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Teriyaki Salmon',$fs,195,816,19.0,8.0,6.5,9.5,1.5,0.0,1.5, 1,0,0,0,0,0,1,0, 0,0,1,1,0,0],
			['Ceviche (white fish)',$fs,85,356,15.0,3.5,1.5,1.0,0.2,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Fish Tacos (cod)',$tk,185,774,12.0,18.0,2.0,7.5,1.0,1.5,0.6, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Seafood Paella',$tk,145,607,9.0,18.0,1.0,4.0,0.8,0.5,0.7, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Prawn Stir-fry',$tk,105,439,12.0,6.0,2.0,4.0,0.5,1.5,1.0, 1,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Smoked Salmon Blini',$tk,235,983,10.5,18.0,2.0,13.0,4.5,0.5,1.5, 1,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Sashimi (mixed)',$fs,110,460,22.0,2.0,0.5,1.5,0.3,0.0,0.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── COOKED MEAT DISHES (20) ──
			['Roast Beef',$mp,176,736,26.1,0.0,0.0,7.6,3.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Roast Lamb',$mp,215,900,24.5,0.0,0.0,12.5,5.8,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Roast Pork',$mp,185,774,26.5,0.0,0.0,8.3,3.1,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Roast Turkey',$mp,157,657,29.3,0.0,0.0,3.6,1.0,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Grilled Chicken Breast',$mp,165,690,31.0,0.0,0.0,3.6,1.0,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Slow-cooked Pulled Pork',$mp,190,795,22.0,3.0,2.5,9.5,3.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Beef Stew',$tk,100,418,8.5,6.5,1.5,4.0,1.8,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Lamb Hotpot',$tk,105,439,7.5,8.0,1.0,5.0,2.2,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Chicken Casserole',$tk,85,356,9.0,5.5,1.5,3.0,0.8,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chilli Con Carne',$tk,115,481,8.0,8.5,2.0,5.5,2.2,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bolognese Sauce (beef)',$tk,100,418,7.0,5.5,3.0,5.5,2.3,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Shepherd\'s Pie',$tk,105,439,6.5,10.5,1.5,4.5,2.0,1.2,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Cottage Pie',$tk,110,460,7.0,10.0,1.0,5.0,2.2,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Lasagne (beef)',$tk,135,565,7.5,12.5,2.5,6.5,3.0,0.8,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Moussaka',$tk,115,481,6.5,8.0,3.0,6.5,3.0,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Beef Stroganoff',$tk,145,607,10.5,5.0,1.5,9.5,5.0,0.3,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Meatballs (beef)',$mp,220,920,15.0,8.0,1.5,14.5,5.5,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Chicken Kiev',$tk,260,1088,14.0,12.0,0.5,18.0,8.0,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Toad in the Hole',$tk,195,816,7.5,17.0,2.5,10.5,3.5,0.6,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Sausage Roll',$sc,327,1368,8.5,26.0,2.0,21.0,9.5,1.0,1.2, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],

			// ── INTERNATIONAL DISHES (20) ──
			['Biryani (chicken)',$tk,180,753,10.0,22.0,1.5,6.0,1.5,1.0,0.7, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Korma (chicken)',$tk,145,607,10.0,8.0,3.5,8.5,3.5,0.5,0.5, 0,0,1,0,1,0,0,0, 0,0,1,0,0,0],
			['Tikka Masala (chicken)',$tk,155,649,11.0,9.0,4.0,8.5,3.0,0.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Madras (beef)',$tk,115,481,10.0,5.5,2.0,6.0,1.5,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Jalfrezi (chicken)',$tk,110,460,12.0,6.0,3.0,4.5,1.0,1.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rogan Josh (lamb)',$tk,130,544,11.0,4.5,2.0,7.5,3.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Dhal (lentil)',$tk,100,418,5.0,13.0,1.5,3.0,0.5,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Palak Paneer',$tk,165,690,8.0,5.0,1.5,12.5,7.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Aloo Gobi',$tk,80,335,2.5,11.0,2.0,3.0,0.4,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chana Masala',$tk,120,502,5.5,15.0,3.0,4.0,0.5,4.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sushi (salmon nigiri, 1pc)',$tk,48,201,2.5,7.0,1.0,0.8,0.2,0.1,0.3, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Sushi (prawn nigiri, 1pc)',$tk,40,167,2.5,7.0,1.0,0.2,0.0,0.1,0.3, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Maki Roll (cucumber)',$tk,24,100,0.5,5.0,0.5,0.1,0.0,0.3,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pho (beef)',$tk,50,209,4.5,5.0,0.5,1.5,0.5,0.3,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ramen (tonkotsu)',$tk,75,314,5.0,7.0,0.5,3.5,1.5,0.3,1.5, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Bibimbap',$tk,155,649,8.0,22.0,2.0,4.0,0.8,2.0,0.8, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Burrito (chicken)',$tk,170,711,9.5,20.0,1.5,5.5,2.0,2.0,0.7, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Quesadilla (cheese)',$tk,250,1046,10.0,22.0,1.5,13.5,6.5,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Tacos (beef)',$tk,210,879,10.0,16.0,2.0,12.0,4.5,1.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Nachos (with cheese)',$sc,350,1464,9.0,35.0,2.5,19.5,6.5,3.0,0.8, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],

			// ── DESSERTS & SWEETS (20) ──
			['Cheesecake (baked)',$sc,321,1343,5.5,25.0,18.0,22.5,12.5,0.3,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tiramisu',$sc,283,1184,4.5,30.0,22.5,15.5,8.5,0.3,0.1, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Chocolate Brownie',$sc,405,1694,5.0,48.0,35.0,22.5,7.0,2.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Carrot Cake',$sc,350,1464,4.0,45.0,28.0,18.0,3.0,1.5,0.5, 0,0,0,1,1,1,0,0, 0,0,1,1,0,1],
			['Victoria Sponge',$sc,350,1464,4.5,45.0,27.0,18.0,5.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Sticky Toffee Pudding',$sc,340,1423,3.0,52.0,38.0,14.0,8.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bread and Butter Pudding',$sc,192,803,5.5,25.5,14.0,8.0,4.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Apple Crumble',$sc,215,900,2.0,35.0,18.0,8.0,4.0,2.0,0.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Rice Pudding',$sc,130,544,3.3,19.5,10.5,4.5,2.8,0.1,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Panna Cotta',$sc,260,1088,3.0,22.0,20.0,18.0,11.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Crème Brûlée',$sc,280,1172,3.5,20.0,18.0,20.5,12.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Profiteroles',$sc,290,1213,5.0,25.0,15.0,19.0,11.0,0.5,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Eton Mess',$sc,230,962,2.5,30.0,28.0,11.5,7.0,0.5,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Treacle Tart',$sc,370,1548,3.0,62.0,38.0,13.0,5.0,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Spotted Dick',$sc,340,1423,4.5,52.0,25.0,13.5,6.0,1.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Jam Roly-Poly',$sc,305,1276,3.5,48.0,22.0,12.0,5.5,1.0,0.4, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Custard (ready-made)',$sc,100,418,2.8,14.5,10.0,3.5,2.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Ice Cream (vanilla)',$sc,207,866,3.5,23.6,21.2,11.0,6.8,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Sorbet (lemon)',$sc,131,548,0.0,34.0,30.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Frozen Yoghurt',$sc,127,531,3.0,22.0,18.5,3.5,2.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── BREAKFAST EXTRAS (15) ──
			['Porridge (with semi-skimmed milk)',$bc,79,331,3.0,12.0,4.8,2.0,0.8,1.0,0.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Bircher Muesli',$bc,155,649,4.0,22.0,12.0,5.5,1.0,2.5,0.1, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Açaí Bowl',$sc,135,565,2.5,19.0,12.0,5.5,1.0,4.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Granola Bar',$sc,410,1715,6.0,62.0,28.0,16.0,3.0,4.0,0.2, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],
			['Protein Bar',$sc,380,1590,30.0,35.0,18.0,13.0,5.0,5.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Energy Ball (date-based)',$sc,320,1339,5.0,48.0,38.0,13.0,3.0,5.0,0.0, 0,0,0,0,1,0,0,0, 0,1,1,1,1,1],
			['Croissant (chocolate)',$bc,425,1778,7.5,45.0,15.0,23.5,14.0,1.5,0.7, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pain au Raisin',$bc,330,1381,6.5,48.0,20.0,13.0,7.5,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Cinnamon Roll',$bc,380,1590,5.5,50.0,25.0,18.0,8.0,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Danish Pastry',$bc,374,1565,5.5,42.0,18.0,21.0,11.5,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Muffin (blueberry)',$sc,377,1577,5.0,52.0,28.0,17.0,3.0,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Toast (white, with butter)',$bc,313,1310,8.0,38.0,3.0,14.0,8.5,2.0,0.7, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Crumpet (with butter)',$bc,240,1004,5.0,32.0,3.0,10.0,6.0,1.5,0.7, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Beans on Toast',$tk,155,649,6.5,23.0,5.0,3.5,0.5,3.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Full English Breakfast',$tk,295,1234,15.0,18.0,3.0,18.0,6.0,2.5,1.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],

			// ── SANDWICHES & WRAPS (10) ──
			['BLT Sandwich',$tk,240,1004,10.0,22.0,3.0,12.5,3.0,1.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Tuna Mayo Sandwich',$tk,225,941,11.0,23.0,2.5,10.0,1.5,1.0,0.8, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Ham and Cheese Sandwich',$tk,260,1088,13.5,22.5,2.0,13.0,5.5,1.0,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Egg Mayo Sandwich',$tk,235,983,8.5,22.0,2.0,13.0,2.5,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Chicken Wrap',$tk,195,816,12.0,22.0,2.0,6.5,1.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Falafel Wrap',$tk,265,1109,8.0,32.0,3.0,12.0,1.5,3.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Prawn Cocktail Sandwich',$tk,210,879,8.5,22.0,3.0,10.0,1.5,1.0,0.8, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Club Sandwich',$tk,265,1109,14.0,22.0,2.5,13.5,3.5,1.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Panini (mozzarella & tomato)',$tk,255,1067,11.0,28.0,3.0,11.0,4.5,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Croque Monsieur',$tk,310,1297,15.0,22.0,2.0,18.0,8.5,0.8,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],

			// ── CONFECTIONERY (10) ──
			['Toffee',$sc,430,1799,2.0,70.0,60.0,17.0,10.5,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Fudge',$sc,430,1799,2.5,72.0,65.0,15.5,9.5,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Marshmallow',$sc,318,1331,1.8,81.3,57.6,0.2,0.0,0.1,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,0,1],
			['Turkish Delight',$sc,352,1473,0.8,89.0,75.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nougat',$sc,400,1674,3.5,75.0,62.0,10.0,2.0,0.5,0.0, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Liquorice',$sc,313,1310,3.5,70.0,35.0,3.5,0.5,0.5,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Halva',$sc,469,1962,12.6,47.8,38.0,27.3,5.2,3.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baklava',$sc,425,1778,6.0,45.0,35.0,25.0,5.0,2.0,0.1, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Gulab Jamun',$sc,350,1464,4.5,50.0,40.0,15.0,8.0,0.3,0.1, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Jalebi',$sc,370,1548,3.0,60.0,50.0,14.0,2.5,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,1],

			// ── MISC EXTRAS (10) ──
			['Quiche Lorraine',$tk,255,1067,10.0,18.0,2.0,16.5,7.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,1],
			['Cornish Pasty',$tk,265,1109,7.5,25.0,1.5,15.5,7.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pork Pie',$tk,340,1423,10.0,24.0,1.5,23.0,9.0,0.8,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Arancini (rice ball)',$tk,230,962,5.0,28.0,1.5,10.5,3.5,0.8,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Scotch Pancake (drop scone)',$bc,262,1096,6.5,40.0,12.0,8.5,2.0,1.0,0.8, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Welsh Rarebit',$tk,285,1193,12.5,15.0,1.5,19.5,11.0,0.5,1.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Ploughman\'s Lunch',$tk,185,774,8.0,18.0,5.0,9.5,4.5,2.0,1.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Coronation Chicken',$tk,195,816,12.0,10.0,7.0,12.0,2.0,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Jacket Potato with Cheese',$fv,165,690,6.5,20.0,1.0,6.5,3.8,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Jacket Potato with Beans',$fv,130,544,5.5,22.0,4.0,1.5,0.2,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v26.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 26 );
	}

	/** Seed v27: 200 more unique foods. */
	public static function seed_v27(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 27 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── BABY FOODS & INFANT (10) ──
			['Baby Rice Cereal (dry)',$bc,380,1590,8.0,82.0,0.5,2.0,0.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baby Fruit Purée (apple)',$fv,42,176,0.2,10.5,9.5,0.1,0.0,0.8,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baby Vegetable Purée (carrot)',$fv,35,146,0.6,7.5,4.5,0.2,0.0,1.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baby Meat Purée (chicken)',$mp,85,356,12.0,2.0,0.5,3.5,1.0,0.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Baby Porridge (oat-based)',$bc,370,1548,11.0,62.0,1.0,7.5,1.2,8.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Follow-on Milk Powder',$de,500,2092,12.0,55.0,38.0,24.0,10.0,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Baby Rusks',$bc,415,1736,6.5,72.0,22.0,11.0,5.0,2.0,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Baby Yoghurt (fromage frais)',$de,95,397,4.0,13.5,11.0,2.5,1.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Baby Banana Puffs',$sc,390,1632,5.0,82.0,12.0,4.0,0.5,2.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baby Fish Pie (jar)',$fs,65,272,3.5,7.5,0.5,2.0,0.8,0.5,0.2, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],

			// ── PROTEIN SUPPLEMENTS (10) ──
			['Whey Protein Powder (unflavoured)',$de,380,1590,80.0,5.0,3.0,4.0,2.0,0.0,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Casein Protein Powder',$de,370,1548,78.0,4.0,2.0,3.5,1.5,0.0,0.4, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Soy Protein Isolate',$lp,338,1414,88.0,1.0,0.0,1.0,0.1,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Pea Protein Powder',$lp,370,1548,80.0,5.0,0.5,5.0,0.8,5.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hemp Protein Powder',$ns,340,1423,50.0,15.0,3.0,10.0,1.0,18.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Collagen Peptides Powder',$de,360,1506,90.0,0.0,0.0,0.0,0.0,0.0,0.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Mass Gainer Shake (powder)',$de,400,1674,25.0,65.0,20.0,5.0,2.0,2.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['BCAA Powder (flavoured)',$de,280,1172,70.0,25.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,0,1,1,1,1],
			['Meal Replacement Shake (powder)',$de,390,1632,30.0,45.0,15.0,10.0,2.0,8.0,0.8, 0,0,1,0,0,0,1,0, 0,0,1,1,0,1],
			['Creatine Monohydrate',$de,0,0,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── TROPICAL & EXOTIC FRUIT (15) ──
			['Dragon Fruit (pitaya)',$fv,60,251,1.2,13.0,8.0,0.4,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Passion Fruit',$fv,97,406,2.2,23.4,11.2,0.7,0.1,10.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lychee',$fv,66,276,0.8,16.5,15.2,0.4,0.1,1.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Guava',$fv,68,285,2.6,14.3,8.9,1.0,0.3,5.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Starfruit (carambola)',$fv,31,130,1.0,6.7,4.0,0.3,0.0,2.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Persimmon',$fv,70,293,0.6,18.6,12.5,0.2,0.0,3.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Jackfruit (raw)',$fv,95,397,1.7,23.2,19.1,0.6,0.2,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Durian',$fv,147,615,1.5,27.1,6.0,5.3,1.5,3.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Rambutan',$fv,82,343,0.7,20.9,0.0,0.2,0.0,0.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tamarind (fresh)',$fv,239,1000,2.8,62.5,38.0,0.6,0.3,5.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Plantain (raw)',$fv,122,510,1.3,31.9,15.0,0.4,0.1,2.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kumquat',$fv,71,297,1.9,15.9,9.4,0.9,0.1,6.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Soursop',$fv,66,276,1.0,16.8,13.5,0.3,0.1,3.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Breadfruit (raw)',$fv,103,431,1.1,27.1,11.0,0.2,0.0,4.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cactus Pear (prickly pear)',$fv,41,172,0.7,9.6,6.0,0.5,0.1,3.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── MIDDLE EASTERN / MEDITERRANEAN (15) ──
			['Hummus',$co,166,694,7.9,14.3,0.3,9.6,1.4,6.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baba Ganoush',$co,130,544,2.5,10.0,3.5,9.0,1.2,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Falafel (fried)',$tk,333,1393,13.3,31.8,0.0,17.8,2.4,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tabbouleh',$fv,90,377,2.5,12.0,1.5,4.0,0.5,2.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Fattoush Salad',$fv,85,356,2.0,10.5,2.5,4.5,0.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shawarma (chicken)',$tk,180,753,18.0,8.0,1.5,8.5,2.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shawarma (lamb)',$tk,225,941,16.0,8.0,1.5,14.5,6.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kibbeh (lamb)',$tk,260,1088,12.0,20.0,1.0,15.0,5.5,3.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Labneh',$de,150,628,6.0,4.0,3.5,12.5,8.0,0.0,0.3, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Halloumi (grilled)',$de,321,1343,21.0,1.5,1.0,25.0,16.0,0.0,2.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Dolma (stuffed vine leaves)',$fv,195,816,2.5,12.5,2.0,15.5,2.5,2.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shakshuka',$tk,110,460,6.5,8.0,5.0,6.0,1.5,2.0,0.7, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Kofta (beef)',$mp,260,1088,15.0,5.0,1.0,20.0,8.5,0.5,0.7, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Manakeesh (za\'atar)',$bc,310,1297,7.0,38.0,1.5,14.5,2.0,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Lahmacun (Turkish pizza)',$tk,230,962,9.0,28.0,3.0,9.0,3.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],

			// ── EAST ASIAN (15) ──
			['Dim Sum (har gow, 1pc)',$tk,45,188,3.0,5.0,0.3,1.5,0.3,0.2,0.4, 0,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Dim Sum (siu mai, 1pc)',$tk,55,230,3.5,4.0,0.5,2.5,0.8,0.2,0.5, 0,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Char Siu (BBQ pork)',$mp,225,941,20.0,15.0,12.0,9.5,3.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Peking Duck (with pancake)',$tk,265,1109,14.0,20.0,5.0,14.5,3.5,0.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Mapo Tofu',$tk,105,439,6.5,4.0,1.0,7.0,1.5,0.5,1.2, 0,0,0,0,0,0,1,0, 0,0,1,0,1,1],
			['Kung Pao Chicken',$tk,165,690,15.0,10.0,4.0,8.0,1.5,1.0,1.5, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Sweet and Sour Pork',$tk,195,816,10.0,22.0,15.0,8.0,2.0,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Wonton Soup (6 pcs)',$tk,80,335,5.0,10.0,0.5,2.0,0.5,0.5,1.0, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Congee (rice porridge)',$bc,46,192,1.2,10.0,0.1,0.2,0.0,0.2,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bao Bun (pork belly)',$tk,240,1004,8.0,30.0,4.0,10.0,3.5,0.8,0.8, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Gyoza (pan-fried, 1pc)',$tk,48,201,2.5,5.5,0.5,1.5,0.4,0.3,0.4, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Katsu Curry (chicken)',$tk,200,837,13.0,18.0,4.0,9.0,1.5,1.0,1.0, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Teriyaki Chicken',$tk,165,690,18.0,12.0,10.0,5.0,1.0,0.3,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Okonomiyaki (Japanese pancake)',$tk,175,732,7.0,22.0,3.0,7.0,1.0,1.5,1.0, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Yakitori (chicken skewer, 1pc)',$mp,145,607,18.0,5.0,4.0,5.5,1.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],

			// ── AFRICAN & CARIBBEAN (10) ──
			['Jollof Rice',$tk,165,690,3.5,28.0,3.0,4.5,0.8,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Jerk Chicken',$mp,190,795,22.0,5.0,2.5,9.0,2.5,0.5,1.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fried Plantain',$fv,170,711,0.8,32.0,18.0,5.5,1.5,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Ackee and Saltfish',$tk,185,774,11.0,3.0,0.5,14.5,3.0,2.5,1.5, 1,0,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Egusi Soup',$tk,150,628,8.0,5.0,1.5,11.5,2.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Suya (beef skewers)',$mp,250,1046,20.0,5.0,1.0,17.0,6.0,1.5,0.8, 0,0,0,0,1,0,0,0, 0,1,1,0,0,0],
			['Fufu (cassava)',$fv,160,669,0.5,38.5,1.5,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Callaloo (cooked greens)',$fv,32,134,3.0,3.5,0.5,0.8,0.2,2.5,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Rice and Peas (Caribbean)',$tk,155,649,4.5,27.0,1.0,3.5,1.5,3.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Curry Goat',$tk,195,816,18.0,5.0,1.0,11.5,4.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── UK SUPERMARKET READY MEALS (15) ──
			['Macaroni Cheese (ready meal)',$tk,155,649,6.0,17.0,2.0,7.0,4.0,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Chicken Tikka Masala (ready meal)',$tk,140,586,9.0,12.0,3.5,6.5,2.0,0.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Spaghetti Bolognese (ready meal)',$tk,110,460,6.5,12.0,2.5,4.0,1.5,0.8,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Cottage Pie (ready meal)',$tk,100,418,6.0,10.5,1.0,4.0,1.8,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fish Pie (ready meal)',$tk,115,481,6.5,10.0,1.0,5.5,3.0,0.5,0.6, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Chicken Chow Mein (ready meal)',$tk,100,418,6.0,12.5,2.0,3.0,0.5,0.8,0.7, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Sweet and Sour Chicken (ready meal)',$tk,130,544,7.0,18.0,10.0,3.5,0.5,0.5,0.6, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Chicken Jalfrezi (ready meal)',$tk,105,439,9.0,8.0,3.0,4.5,1.0,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Beef Lasagne (ready meal)',$tk,125,523,6.5,11.5,2.0,6.0,2.5,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Vegetable Curry (ready meal)',$tk,85,356,2.5,11.0,3.0,3.5,0.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shepherd\'s Pie (ready meal)',$tk,95,397,5.5,10.0,1.0,3.5,1.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Prawn Curry (ready meal)',$tk,95,397,6.0,9.0,2.0,4.0,1.5,0.5,0.6, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mushroom Risotto (ready meal)',$tk,120,502,3.0,17.0,0.5,4.5,2.0,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Thai Green Curry (ready meal)',$tk,110,460,5.0,9.0,3.0,6.0,4.0,0.5,0.7, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chilli Con Carne (ready meal)',$tk,110,460,7.0,11.0,2.5,4.0,1.5,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── CHEESE VARIETIES (15) ──
			['Red Leicester',$de,403,1686,24.0,0.1,0.1,34.0,21.0,0.0,1.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Wensleydale',$de,380,1590,22.0,0.1,0.1,31.5,20.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Double Gloucester',$de,405,1694,24.5,0.1,0.1,34.0,21.5,0.0,1.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Lancashire',$de,373,1561,23.0,0.1,0.1,31.0,19.5,0.0,1.8, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Caerphilly',$de,375,1569,23.0,0.1,0.1,31.0,19.5,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Cheshire',$de,387,1619,23.5,0.1,0.1,32.0,20.0,0.0,1.6, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Cornish Yarg',$de,365,1527,22.0,0.1,0.1,30.5,19.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Stilton (white)',$de,360,1506,20.0,0.1,0.1,30.0,18.5,0.0,1.8, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Manchego',$de,390,1632,25.0,0.5,0.5,31.5,19.5,0.0,1.6, 0,0,1,0,0,0,0,0, 1,0,0,1,0,1],
			['Gruyère',$de,413,1728,30.0,0.4,0.4,33.0,19.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Pecorino Romano',$de,387,1619,32.0,3.6,0.0,27.0,17.5,0.0,5.0, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1],
			['Mascarpone',$de,429,1795,4.8,3.5,3.5,44.0,30.0,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Boursin',$de,370,1548,6.0,3.0,2.5,38.0,25.0,0.0,0.8, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Paneer',$de,265,1109,18.3,1.2,1.2,20.8,13.0,0.0,0.3, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Vegan Cheese (cheddar-style)',$de,295,1234,4.0,18.0,0.5,23.0,8.0,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── CURED & PROCESSED MEAT (15) ──
			['Prosciutto',$mp,195,816,26.0,0.0,0.0,10.0,3.5,0.0,4.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Bresaola',$mp,151,632,32.0,0.0,0.0,2.6,1.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Mortadella',$mp,311,1301,14.0,3.0,0.5,27.0,10.0,0.0,2.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pastrami',$mp,133,556,21.0,1.5,0.5,4.5,1.5,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Coppa',$mp,335,1402,20.0,0.0,0.0,28.0,10.5,0.0,3.5, 0,0,0,0,0,0,0,0, 0,1,0,0,0,0],
			['Nduja',$mp,400,1674,12.0,2.0,0.5,38.0,14.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Duck Pâté',$mp,320,1339,10.5,2.0,0.5,30.5,10.0,0.0,1.5, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Chicken Liver Pâté',$mp,280,1172,12.0,2.5,0.5,24.5,8.5,0.0,1.2, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Black Pudding',$mp,297,1243,12.8,19.5,0.5,21.7,8.5,0.8,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['White Pudding',$mp,305,1276,8.5,28.0,1.0,18.0,7.0,1.0,1.2, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Haggis',$mp,236,987,10.5,19.1,0.5,14.9,6.5,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Venison Sausage',$mp,180,753,22.0,3.0,0.5,8.5,3.0,0.5,1.5, 0,0,0,0,0,1,0,0, 0,1,1,0,0,0],
			['Turkey Bacon',$mp,135,565,18.0,1.5,0.5,6.0,1.8,0.0,2.5, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Beef Jerky',$mp,325,1360,55.0,11.0,9.0,6.5,2.5,0.0,2.5, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Biltong',$mp,280,1172,56.0,2.0,1.0,5.0,2.0,0.0,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── PLANT-BASED / VEGAN ALTERNATIVES (15) ──
			['Tofu (firm)',$lp,76,318,8.2,1.9,0.5,4.8,0.7,0.3,0.0, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Tempeh',$lp,193,808,20.3,7.6,0.0,11.4,2.5,0.0,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Seitan',$lp,370,1548,75.0,14.0,0.0,1.9,0.3,0.6,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Quorn Mince',$lp,105,439,14.5,3.0,0.5,3.5,0.8,5.5,0.6, 0,0,0,1,0,0,0,0, 0,0,1,0,0,1],
			['Beyond Burger (raw)',$lp,230,962,20.0,5.0,0.0,14.0,5.0,3.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Oat Milk (unsweetened)',$dr,44,184,1.0,7.0,3.5,1.5,0.2,0.8,0.1, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Soya Milk (unsweetened)',$dr,33,138,3.3,0.5,0.2,1.8,0.3,0.5,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Almond Milk (unsweetened)',$dr,13,54,0.4,0.3,0.0,1.1,0.1,0.2,0.1, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Coconut Milk (carton)',$dr,20,84,0.2,2.7,2.0,0.9,0.8,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Vegan Sausage (plant-based)',$lp,185,774,18.0,8.0,1.0,9.5,2.0,3.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Vegan Bacon (plant-based)',$lp,200,837,15.0,12.0,2.0,11.0,1.5,3.5,2.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Jackfruit (pulled, canned)',$fv,24,100,0.6,5.4,0.0,0.1,0.0,1.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Nutritional Yeast Flakes',$co,335,1402,53.0,25.0,0.0,4.0,0.5,17.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coconut Yoghurt',$de,185,774,1.5,8.0,5.0,16.0,14.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cashew Cream Cheese',$de,245,1025,4.0,12.0,3.0,20.5,4.0,1.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],

			// ── HERBS, SPICES & SEASONINGS (15) ──
			['Ground Cumin',$co,375,1569,17.8,44.2,2.3,22.3,1.5,10.5,1.7, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Ground Coriander',$co,298,1247,12.4,54.9,0.0,17.8,1.0,41.9,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Smoked Paprika',$co,282,1180,14.1,53.9,10.3,13.0,2.1,34.9,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Turmeric (ground)',$co,354,1481,7.8,64.9,3.2,9.9,3.1,21.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Garam Masala',$co,379,1586,15.0,45.0,2.0,15.0,3.0,15.0,0.5, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Chinese Five Spice',$co,340,1423,8.0,58.0,4.0,8.0,2.0,12.0,0.2, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Ras el Hanout',$co,310,1297,10.0,50.0,5.0,8.5,2.0,10.0,0.5, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Dried Oregano',$co,265,1109,9.0,68.9,4.1,4.3,1.6,42.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dried Thyme',$co,276,1155,9.1,63.9,1.7,7.4,2.7,37.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chilli Flakes',$co,314,1314,12.0,56.0,10.0,17.0,3.0,28.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sumac',$co,240,1004,5.0,56.0,7.5,6.5,0.5,16.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Za\'atar Blend',$co,320,1339,8.0,48.0,2.5,12.0,2.0,10.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Harissa Paste',$co,75,314,2.5,8.0,5.0,3.5,0.5,3.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tahini',$co,595,2490,17.0,21.3,0.5,53.8,7.5,9.3,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Miso Paste (white)',$co,198,828,12.0,26.0,6.0,6.0,1.0,5.0,5.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── CANNED & SHELF STAPLES (15) ──
			['Baked Beans (canned)',$lp,81,339,4.7,13.6,5.0,0.3,0.1,3.7,0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kidney Beans (canned)',$lp,100,418,6.7,17.8,1.2,0.5,0.1,6.2,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Butter Beans (canned)',$lp,77,322,5.0,14.0,0.5,0.3,0.1,4.6,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cannellini Beans (canned)',$lp,91,381,6.2,16.5,0.5,0.4,0.1,5.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chopped Tomatoes (canned)',$fv,20,84,1.0,3.5,3.0,0.1,0.0,0.8,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coconut Milk (canned)',$fo,197,824,2.0,3.3,2.0,20.5,18.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sweetcorn (canned)',$fv,79,331,2.3,16.5,4.5,1.0,0.1,1.8,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sardines in Tomato Sauce',$fs,162,678,17.0,1.5,1.0,10.0,2.5,0.0,0.8, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Tuna in Brine (canned)',$fs,99,414,23.5,0.0,0.0,0.6,0.2,0.0,1.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Tuna in Sunflower Oil',$fs,189,791,25.0,0.0,0.0,9.5,1.5,0.0,0.8, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Spam (canned pork)',$mp,315,1318,13.0,2.0,0.5,28.0,10.0,0.0,3.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Corned Beef (canned)',$mp,217,908,25.3,0.0,0.0,12.7,5.2,0.0,1.5, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Garden Peas (canned)',$fv,55,230,3.5,8.5,1.5,0.4,0.1,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chickpeas (canned)',$lp,115,481,7.2,16.0,0.3,2.5,0.3,4.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lentil Soup (canned)',$tk,58,243,3.0,9.5,1.0,1.0,0.2,1.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── FROZEN FOODS (15) ──
			['Fish Fingers (frozen, cooked)',$fs,220,920,12.5,18.0,0.5,11.5,1.5,0.5,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chicken Nuggets (frozen, cooked)',$mp,245,1025,14.0,18.0,0.5,13.5,2.5,1.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Potato Waffles (frozen, cooked)',$fv,190,795,2.8,28.0,0.5,7.5,1.0,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Garden Peas (frozen, cooked)',$fv,69,289,5.4,9.8,1.5,0.4,0.1,4.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mixed Vegetables (frozen, cooked)',$fv,50,209,2.5,8.5,3.0,0.5,0.1,3.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chips / French Fries (oven)',$fv,205,858,3.0,30.0,0.5,8.5,1.0,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hash Browns (frozen, cooked)',$fv,210,879,2.0,25.0,0.5,11.5,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Breaded Scampi (frozen, cooked)',$fs,250,1046,10.0,22.0,0.5,13.5,2.0,0.5,1.0, 1,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pizza (frozen, margherita, cooked)',$tk,225,941,9.5,28.0,3.5,8.5,3.5,1.5,1.2, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Garlic Bread (frozen, cooked)',$bc,330,1381,7.0,38.0,2.5,17.0,5.0,1.5,1.0, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Onion Rings (frozen, cooked)',$sc,260,1088,4.0,28.0,3.0,15.0,2.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Spring Rolls (frozen, cooked)',$tk,215,900,4.5,28.0,2.5,10.0,1.5,1.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Samosa (vegetable, frozen, cooked)',$tk,260,1088,4.0,28.0,2.0,14.5,2.5,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Ice Lolly (fruit, frozen)',$sc,70,293,0.1,18.0,16.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Edamame Beans (frozen, cooked)',$lp,122,510,11.0,8.9,2.2,5.2,0.6,5.2,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── MISCELLANEOUS UK FOODS (15) ──
			['Yorkshire Pudding (1 medium)',$bc,190,795,6.5,22.5,1.5,8.5,2.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Stuffing (sage & onion, cooked)',$bc,145,607,3.5,22.0,3.0,5.0,2.0,1.5,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Gravy (instant, made up)',$co,25,105,0.5,4.5,0.5,0.5,0.2,0.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Mint Sauce',$co,105,439,0.5,25.0,22.0,0.2,0.0,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Horseradish Sauce',$co,130,544,2.0,17.0,14.0,6.0,3.5,1.0,0.8, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Piccalilli',$co,72,301,0.5,15.0,12.0,1.0,0.1,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Branston Pickle',$co,130,544,0.5,32.0,28.0,0.2,0.0,0.5,1.8, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Cranberry Sauce',$co,150,628,0.1,38.0,35.0,0.1,0.0,1.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bread Sauce',$co,95,397,3.0,13.0,2.5,3.5,2.0,0.3,0.6, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Redcurrant Jelly',$co,265,1109,0.1,66.0,62.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pigs in Blankets (cooked)',$mp,310,1297,14.0,5.0,1.0,26.0,10.0,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Chipolata Sausage (cooked)',$mp,280,1172,14.0,6.0,1.0,22.5,8.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cumberland Sausage (cooked)',$mp,300,1255,13.0,8.0,1.5,24.0,9.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Scotch Egg',$sc,245,1025,10.5,14.0,0.5,16.5,5.5,0.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Cheese and Onion Pie',$tk,280,1172,7.0,24.0,3.0,17.5,8.0,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v27.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 27 );
	}

	/** Seed v28: 100 more unique foods. */
	public static function seed_v28(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 28 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── SOUTH ASIAN (10) ──
			['Naan Bread (plain)',$bc,310,1297,9.0,50.0,3.5,8.5,1.5,2.0,0.9, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Peshwari Naan',$bc,340,1423,8.0,52.0,12.0,11.0,2.5,2.0,0.8, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Chapati (wholemeal)',$bc,240,1004,8.0,43.0,1.5,5.5,0.8,4.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Paratha (layered)',$bc,325,1360,6.5,42.0,1.0,15.0,3.5,2.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Samosa (lamb)',$tk,285,1193,7.5,26.0,1.5,17.0,6.0,1.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Onion Bhaji',$tk,310,1297,5.0,28.0,4.0,20.0,2.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pakora (vegetable)',$tk,295,1234,5.5,25.0,2.0,19.5,2.0,3.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tandoori Chicken',$mp,165,690,25.0,3.0,1.5,5.5,1.5,0.5,1.0, 0,0,1,0,0,0,0,0, 1,1,1,0,0,0],
			['Butter Chicken',$tk,175,732,14.0,6.0,3.0,10.5,5.5,0.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Saag Aloo',$tk,90,377,2.5,10.0,1.0,4.5,0.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── EUROPEAN CLASSICS (10) ──
			['Wiener Schnitzel',$mp,235,983,18.0,12.0,0.5,13.0,3.0,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Goulash (beef)',$tk,110,460,9.5,7.0,3.0,5.0,2.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pierogi (potato & cheese)',$tk,195,816,5.5,30.0,1.5,5.5,2.5,1.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Borscht (beetroot soup)',$tk,38,159,1.5,6.5,4.0,0.8,0.1,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Crêpe (with sugar & lemon)',$sc,210,879,5.0,30.0,14.0,8.0,3.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Rosti (Swiss potato cake)',$fv,175,732,2.5,22.0,0.5,9.0,2.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bruschetta (tomato)',$bc,210,879,5.0,28.0,4.5,8.5,1.2,2.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Antipasto Platter (per 100g avg)',$mp,195,816,12.0,5.0,2.0,14.5,5.0,1.0,2.5, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Sauerbraten',$mp,155,649,18.0,8.0,4.0,5.5,2.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Paella Mixta',$tk,155,649,9.5,18.0,1.5,5.0,1.0,0.8,0.7, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── STREET FOOD & SNACKS (10) ──
			['Churros (with sugar)',$sc,350,1464,4.0,44.0,16.0,18.0,3.0,1.5,0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Pretzel (soft, salted)',$bc,325,1360,8.5,64.0,2.0,3.5,0.5,2.0,2.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Corn Dog',$sc,285,1193,8.0,28.0,5.0,16.0,4.0,1.0,1.2, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Loaded Fries (cheese & bacon)',$tk,285,1193,9.0,28.0,1.5,15.5,6.5,2.0,1.2, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Doner Kebab (lamb, in pitta)',$tk,230,962,12.5,22.0,2.5,10.5,4.5,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chicken Gyros (in pitta)',$tk,210,879,14.0,22.0,2.0,7.5,1.5,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Empanada (beef)',$tk,300,1255,9.0,28.0,2.0,17.0,5.0,1.0,0.8, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Takoyaki (octopus ball, 1pc)',$tk,55,230,2.5,6.5,0.5,2.0,0.3,0.2,0.5, 1,1,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Bánh Mì (pork)',$tk,235,983,12.0,28.0,5.0,8.5,2.0,1.5,1.2, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Poutine',$tk,265,1109,8.0,30.0,2.0,13.0,5.5,2.0,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],

			// ── DRIED FRUIT (10) ──
			['Dried Apricots',$fv,241,1008,3.4,63.9,53.4,0.5,0.0,7.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Cranberries (sweetened)',$fv,325,1360,0.1,82.4,72.6,1.4,0.1,5.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dried Figs',$fv,249,1042,3.3,63.9,47.9,0.9,0.2,9.8,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Mango',$fv,319,1335,1.5,78.6,66.3,1.2,0.3,2.4,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Prunes (dried plums)',$fv,240,1004,2.2,63.9,38.1,0.4,0.0,7.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Banana Chips',$fv,519,2172,2.3,58.4,35.3,33.6,28.9,7.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dried Pineapple',$fv,325,1360,1.8,82.0,68.0,0.5,0.0,4.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mixed Dried Fruit',$fv,268,1121,2.5,67.5,55.0,0.5,0.1,5.5,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Goji Berries (dried)',$fv,349,1460,14.3,77.1,45.6,0.4,0.0,13.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Medjool Dates (dried)',$fv,277,1159,1.8,75.0,66.5,0.2,0.0,6.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── WILD GAME & SPECIALTY MEAT (10) ──
			['Venison Steak (grilled)',$mp,158,661,30.2,0.0,0.0,3.4,1.3,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Rabbit (roasted)',$mp,173,724,33.0,0.0,0.0,3.5,1.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Pheasant (roasted)',$mp,220,920,28.0,0.0,0.0,12.0,3.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Partridge (roasted)',$mp,212,887,29.0,0.0,0.0,10.5,3.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Pigeon Breast (grilled)',$mp,142,594,25.0,0.0,0.0,4.5,1.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Ostrich Steak (grilled)',$mp,145,607,27.0,0.0,0.0,3.5,1.2,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Wild Boar (roasted)',$mp,160,669,28.5,0.0,0.0,4.5,1.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Bison Steak (grilled)',$mp,143,598,28.4,0.0,0.0,2.4,0.9,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Quail (roasted)',$mp,192,803,25.0,0.0,0.0,10.0,2.8,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Goat Meat (roasted)',$mp,143,598,27.1,0.0,0.0,3.0,0.9,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── SPREADS & JAMS (10) ──
			['Peanut Butter (smooth)',$co,588,2460,25.1,20.0,6.0,49.9,10.3,6.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,1,0,1],
			['Almond Butter',$co,614,2569,21.0,18.8,4.4,55.5,4.2,10.5,0.0, 0,0,0,0,1,0,0,0, 0,1,1,1,1,1],
			['Cashew Butter',$co,587,2456,18.0,27.6,5.0,49.4,9.8,2.0,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Chocolate Hazelnut Spread',$co,539,2255,6.0,57.5,54.9,31.0,10.5,3.4,0.1, 0,0,1,0,1,0,1,0, 0,0,1,1,0,1],
			['Strawberry Jam',$co,265,1109,0.5,65.0,60.0,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Orange Marmalade',$co,261,1092,0.3,65.0,60.0,0.0,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lemon Curd',$co,315,1318,3.0,48.0,45.0,13.0,5.5,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Marmite',$co,252,1054,38.4,20.1,1.2,0.3,0.1,3.5,10.8, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Bovril',$co,173,724,38.0,10.0,3.5,0.5,0.2,0.0,10.0, 0,0,0,0,0,0,0,1, 0,0,0,0,0,0],
			['Honey (manuka)',$co,334,1398,0.3,82.1,82.1,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,0,1],

			// ── FLOURS & BAKING (10) ──
			['Plain Flour (white)',$bc,337,1410,10.0,72.5,1.5,1.0,0.1,3.1,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Self-raising Flour',$bc,330,1381,9.5,72.0,1.5,1.0,0.1,2.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Wholemeal Flour',$bc,322,1348,12.7,61.5,1.5,2.5,0.3,9.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cornflour (cornstarch)',$bc,355,1485,0.3,92.0,0.0,0.1,0.0,0.9,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Almond Flour',$ns,571,2389,21.4,21.7,4.2,49.4,3.7,10.5,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Coconut Flour',$ns,400,1674,18.0,60.0,8.0,12.0,10.0,39.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Baking Powder',$co,53,222,0.0,28.0,0.0,0.0,0.0,0.0,27.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bicarbonate of Soda',$co,0,0,0.0,0.0,0.0,0.0,0.0,0.0,27.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cocoa Powder (unsweetened)',$co,228,954,19.6,57.9,1.8,13.7,8.1,33.2,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Desiccated Coconut',$ns,604,2527,5.6,23.7,6.2,62.0,53.4,16.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── UNUSUAL SEAFOOD (10) ──
			['Whitebait (fried)',$fs,525,2197,19.5,5.5,0.0,47.5,6.0,0.0,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Jellied Eels',$fs,98,410,8.5,0.5,0.0,7.0,1.5,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Cockles (boiled)',$fs,53,222,12.0,0.0,0.0,0.6,0.1,0.0,1.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Whelks (boiled)',$fs,90,377,19.5,0.0,0.0,1.0,0.2,0.0,1.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Crab Sticks (surimi)',$fs,95,397,7.0,13.5,5.5,0.5,0.1,0.0,1.2, 1,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Rollmop Herring',$fs,175,732,14.5,5.0,4.0,10.5,2.5,0.0,2.0, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Smoked Trout',$fs,135,565,21.0,0.0,0.0,5.5,1.0,0.0,1.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Potted Shrimp',$fs,290,1213,11.0,0.5,0.0,27.0,16.0,0.0,1.0, 1,1,1,0,0,0,0,0, 1,0,1,0,0,0],
			['Monkfish (grilled)',$fs,96,402,20.5,0.0,0.0,1.0,0.2,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Dover Sole (grilled)',$fs,91,381,20.0,0.0,0.0,1.0,0.2,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── DRINKS & BEVERAGES (10) ──
			['Hot Chocolate (with semi-skimmed milk)',$dr,78,326,3.5,11.5,10.0,2.5,1.5,0.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Chai Latte',$dr,65,272,2.0,10.5,9.0,2.0,1.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Matcha Latte',$dr,55,230,2.5,7.0,6.0,2.0,1.0,0.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Smoothie (berry)',$dr,55,230,1.0,12.5,10.0,0.5,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Smoothie (green/spinach)',$dr,48,201,1.5,10.0,7.0,0.5,0.1,1.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kombucha',$dr,20,84,0.0,5.0,4.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ginger Beer',$dr,40,167,0.0,10.0,9.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Elderflower Cordial (diluted)',$dr,30,126,0.0,7.5,7.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tonic Water',$dr,34,142,0.0,8.5,8.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Milkshake (chocolate)',$dr,85,356,3.5,13.5,12.5,2.0,1.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v28.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 28 );
	}

	/** Seed v29: 200 more unique foods. */
	public static function seed_v29(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 29 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── LEAFY GREENS & SALAD VEG (12) ──
			['Rocket (arugula)',$fv,25,105,2.6,3.7,2.1,0.7,0.1,1.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Watercress',$fv,11,46,2.3,1.3,0.2,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Lamb\'s Lettuce (corn salad)',$fv,21,88,2.0,3.6,0.4,0.4,0.1,1.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pak Choi (bok choy)',$fv,13,54,1.5,2.2,1.2,0.2,0.0,1.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Swiss Chard',$fv,19,79,1.8,3.7,1.1,0.2,0.0,1.6,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Endive',$fv,17,71,1.3,3.4,0.3,0.2,0.0,3.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chicory (raw)',$fv,23,96,1.7,4.7,0.7,0.3,0.1,4.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Radicchio',$fv,23,96,1.4,4.5,0.6,0.3,0.1,0.9,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fennel (raw)',$fv,31,130,1.2,7.3,3.9,0.2,0.0,3.1,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kohlrabi (raw)',$fv,27,113,1.7,6.2,2.6,0.1,0.0,3.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Celeriac (raw)',$fv,42,176,1.5,9.2,1.6,0.3,0.1,1.8,0.1, 0,0,0,0,0,0,0,1, 1,1,1,1,1,1],
			['Samphire (marsh)',$fv,14,59,1.5,0.5,0.0,0.2,0.0,0.0,3.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── ROOT VEG & TUBERS (8) ──
			['Yam (raw)',$fv,118,494,1.5,27.9,0.5,0.2,0.0,4.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cassava (raw)',$fv,160,669,1.4,38.1,1.7,0.3,0.1,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Taro (raw)',$fv,112,469,1.5,26.5,0.4,0.2,0.0,4.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Jerusalem Artichoke',$fv,73,305,2.0,17.4,9.6,0.0,0.0,1.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Swede (raw)',$fv,36,151,1.1,8.6,4.5,0.2,0.0,2.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Salsify (raw)',$fv,82,343,3.3,18.6,3.0,0.2,0.0,3.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lotus Root',$fv,74,310,2.6,17.2,0.0,0.1,0.0,4.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Water Chestnut',$fv,97,406,1.4,23.9,4.8,0.1,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── MUSHROOM VARIETIES (8) ──
			['Shiitake Mushroom (raw)',$fv,34,142,2.2,6.8,2.4,0.5,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Oyster Mushroom (raw)',$fv,33,138,3.3,6.1,1.1,0.4,0.1,2.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['King Oyster Mushroom',$fv,35,146,3.0,6.5,1.5,0.3,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Enoki Mushroom',$fv,37,155,2.7,7.8,0.2,0.3,0.0,2.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Portobello Mushroom (raw)',$fv,22,92,2.1,3.9,2.5,0.4,0.1,1.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chanterelle Mushroom',$fv,38,159,1.5,6.9,1.2,0.5,0.1,3.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Porcini Mushroom (dried)',$fv,296,1239,30.0,53.0,2.0,3.0,0.4,16.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Truffle (black, fresh)',$fv,286,1197,5.5,53.0,0.2,0.5,0.1,16.5,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── OFFAL & ORGAN MEATS (8) ──
			['Chicken Liver (fried)',$mp,169,707,24.5,1.0,0.0,7.5,2.5,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Lamb Liver (fried)',$mp,237,991,30.0,3.5,0.0,11.0,4.5,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Ox Liver (fried)',$mp,198,828,26.0,4.0,0.0,8.5,3.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Kidney (lamb, fried)',$mp,155,649,24.5,0.0,0.0,6.5,2.0,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Ox Tongue (boiled)',$mp,293,1226,18.0,0.0,0.0,24.5,11.0,0.0,0.6, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Tripe (dressed)',$mp,60,251,9.0,0.0,0.0,2.5,1.0,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Sweetbreads (lamb, fried)',$mp,240,1004,18.0,5.0,0.0,16.0,5.5,0.0,0.2, 0,0,0,0,0,1,0,0, 0,1,1,0,0,0],
			['Bone Marrow (roasted)',$mp,786,3289,7.0,0.0,0.0,84.0,35.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── LATIN AMERICAN (12) ──
			['Arepa (corn, plain)',$bc,200,837,4.0,38.0,1.0,3.5,0.5,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coxinha (chicken croquette)',$tk,290,1213,10.0,28.0,1.0,15.5,3.5,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Pupusa (cheese & bean)',$tk,225,941,7.5,28.0,1.5,9.5,3.5,3.0,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Tamale (pork)',$tk,210,879,7.0,22.0,2.0,11.0,3.5,3.0,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Chimichurri Sauce',$co,130,544,1.5,4.0,0.5,12.5,1.8,1.5,1.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Black Bean Soup',$tk,80,335,5.0,13.0,0.5,1.0,0.2,4.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Elote (Mexican street corn)',$fv,155,649,4.0,20.0,5.0,7.5,3.0,2.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Tostada (chicken)',$tk,220,920,12.0,18.0,1.5,11.0,2.5,2.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Enchilada (cheese)',$tk,230,962,9.0,22.0,3.0,12.0,5.5,2.0,0.8, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Pozole (pork)',$tk,65,272,5.0,7.0,0.5,2.0,0.7,1.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Churro con Chocolate',$sc,380,1590,5.0,48.0,22.0,19.0,6.0,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tres Leches Cake',$sc,270,1130,5.0,38.0,30.0,11.0,6.5,0.3,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── SOUTHEAST ASIAN (12) ──
			['Pad Thai (chicken)',$tk,160,669,10.0,22.0,5.0,4.0,0.8,1.0,1.5, 1,1,0,1,1,0,1,0, 0,0,1,0,0,0],
			['Green Curry (Thai, chicken)',$tk,130,544,8.5,6.0,3.0,8.5,5.5,0.8,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Red Curry (Thai, prawn)',$tk,120,502,8.0,7.0,3.5,7.0,5.0,0.5,1.0, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Massaman Curry (beef)',$tk,155,649,9.0,12.0,5.0,8.5,4.5,1.0,0.8, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Satay (chicken, with sauce)',$tk,195,816,15.0,8.0,5.0,12.0,3.0,1.5,0.8, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Nasi Goreng (fried rice)',$tk,170,711,6.0,24.0,2.0,5.5,1.0,1.0,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Laksa (prawn)',$tk,95,397,5.5,8.0,2.0,5.0,3.5,0.5,1.2, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rendang (beef)',$tk,195,816,18.0,5.0,2.0,11.5,7.0,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Gado-Gado',$tk,160,669,7.0,12.0,5.0,10.0,2.0,3.0,0.5, 0,0,0,0,1,0,1,0, 0,0,1,1,1,1],
			['Banh Xeo (Vietnamese crepe)',$tk,195,816,5.5,18.0,1.5,11.5,3.5,1.5,0.8, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Som Tam (papaya salad)',$fv,60,251,1.5,13.0,8.0,0.5,0.1,2.0,1.5, 1,1,0,0,1,0,0,0, 0,0,1,0,1,1],
			['Mee Goreng (fried noodles)',$tk,175,732,7.0,25.0,3.0,5.0,1.0,1.0,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],

			// ── BRITISH PUB FOOD (10) ──
			['Steak and Kidney Pie',$tk,240,1004,10.0,20.0,1.0,13.5,5.5,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Chicken and Mushroom Pie',$tk,215,900,8.5,18.0,1.0,12.5,5.0,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Steak and Ale Pie',$tk,230,962,9.5,19.0,1.5,13.0,5.5,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Game Pie',$tk,250,1046,11.0,20.0,1.0,14.5,5.5,0.8,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Bangers and Mash',$tk,155,649,7.0,16.0,1.5,7.5,2.5,1.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Fish and Chips',$tk,230,962,12.0,22.0,0.5,11.0,1.8,1.5,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Ploughman\'s Salad',$tk,160,669,7.5,15.0,6.0,8.0,3.5,2.0,1.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Gammon Steak (grilled)',$mp,180,753,28.0,0.5,0.0,7.5,2.5,0.0,3.0, 0,0,0,0,0,0,0,0, 0,1,0,0,0,0],
			['Scampi and Chips',$tk,250,1046,9.5,28.0,0.5,11.5,2.0,1.5,0.8, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Beef Wellington',$tk,295,1234,14.0,18.0,1.0,19.0,8.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],

			// ── FERMENTED & PICKLED (10) ──
			['Sauerkraut',$fv,19,79,0.9,4.3,1.8,0.1,0.0,2.9,0.7, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kimchi',$fv,23,96,1.6,2.4,1.1,0.8,0.1,1.6,2.0, 1,1,0,0,0,0,0,0, 0,0,1,0,1,1],
			['Pickled Onions',$fv,24,100,0.5,5.5,4.0,0.1,0.0,0.8,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pickled Gherkins',$fv,14,59,0.3,2.3,1.1,0.2,0.0,1.2,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pickled Beetroot',$fv,28,117,0.7,5.6,5.0,0.1,0.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pickled Red Cabbage',$fv,20,84,0.8,3.5,3.0,0.1,0.0,1.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tempeh (fermented)',$lp,193,808,20.3,7.6,0.0,11.4,2.5,0.0,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Kefir (plain)',$de,65,272,3.3,4.7,4.7,3.5,2.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kombucha (ginger)',$dr,22,92,0.0,5.5,4.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Natto (fermented soybean)',$lp,212,887,17.7,14.4,4.9,11.0,1.6,5.4,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── BREAKFAST CEREALS (10) ──
			['Cornflakes',$bc,376,1573,7.0,84.0,8.0,0.9,0.1,3.0,1.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bran Flakes',$bc,330,1381,10.0,67.0,13.0,2.5,0.5,15.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Rice Krispies',$bc,383,1602,6.0,87.0,10.0,1.0,0.2,1.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Weetabix (per biscuit, 19g)',$bc,362,1515,11.5,69.0,4.4,2.0,0.4,10.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Shreddies',$bc,358,1498,9.0,72.0,14.5,1.8,0.4,9.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Crunchy Nut Cornflakes',$bc,395,1653,6.0,78.0,35.0,5.0,0.8,2.5,0.7, 0,0,0,0,1,0,0,0, 0,0,1,1,0,1],
			['Coco Pops',$bc,386,1615,4.5,85.0,36.0,2.5,1.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Frosties',$bc,380,1590,4.5,87.0,37.0,0.6,0.1,2.0,0.7, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Special K (original)',$bc,378,1582,16.0,72.0,17.0,1.5,0.3,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Cheerios (wholegrain)',$bc,372,1557,8.5,73.5,21.0,4.5,0.8,7.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── SAUCES & DRESSINGS (12) ──
			['Tomato Ketchup',$co,112,469,1.2,27.5,23.0,0.1,0.0,0.3,1.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['BBQ Sauce',$co,150,628,0.8,36.0,32.0,0.5,0.1,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mayonnaise (full fat)',$co,691,2891,1.1,1.7,1.3,75.6,5.8,0.0,1.1, 0,0,0,1,0,0,0,0, 1,0,1,1,0,1],
			['Mayonnaise (light)',$co,288,1205,0.6,9.5,5.0,27.5,2.5,0.0,1.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Salad Cream',$co,349,1460,1.5,16.5,12.0,31.0,2.5,0.0,1.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Tartare Sauce',$co,310,1297,0.8,10.0,5.5,30.0,2.5,0.3,1.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Brown Sauce (HP)',$co,102,427,0.8,25.0,20.0,0.2,0.0,0.5,1.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Worcestershire Sauce',$co,78,326,1.5,18.0,13.0,0.1,0.0,0.0,4.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Soy Sauce (dark)',$co,60,251,8.5,5.0,0.5,0.0,0.0,0.8,14.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Sweet Chilli Sauce',$co,200,837,0.5,50.0,46.0,0.3,0.0,0.5,2.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sriracha Sauce',$co,93,389,1.5,18.0,15.0,1.5,0.3,1.0,6.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Caesar Dressing',$co,425,1778,3.5,3.0,2.0,44.0,7.0,0.0,1.5, 1,0,1,1,0,0,0,0, 1,0,1,0,0,1],

			// ── BISCUITS & COOKIES (10) ──
			['Digestive Biscuit (1 biscuit, 15g)',$sc,480,2008,7.0,62.0,16.5,22.5,10.5,3.5,0.8, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Rich Tea Biscuit',$sc,450,1883,7.0,70.0,20.0,15.5,7.5,2.5,0.7, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Hobnob',$sc,475,1988,6.5,61.0,22.0,22.5,10.0,4.5,0.7, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Custard Cream',$sc,490,2050,5.0,66.0,30.0,23.0,12.0,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Bourbon Cream',$sc,485,2029,5.5,66.0,32.0,22.5,11.5,2.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Shortbread Finger',$sc,500,2092,5.5,60.0,15.0,27.0,17.0,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Jaffa Cake (1 cake)',$sc,375,1569,4.5,66.0,42.0,10.0,5.0,1.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Chocolate Chip Cookie',$sc,495,2071,5.5,62.0,35.0,25.0,12.0,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ginger Nut Biscuit',$sc,456,1908,5.5,72.0,32.0,15.5,7.5,1.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Oatcake',$sc,432,1808,10.0,57.0,3.0,18.0,3.0,7.5,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],

			// ── CRISPS & SAVOURY SNACKS (10) ──
			['Ready Salted Crisps',$sc,530,2217,6.0,50.0,0.5,34.0,3.0,4.5,1.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Salt and Vinegar Crisps',$sc,515,2155,6.0,52.0,2.0,32.0,3.0,4.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cheese and Onion Crisps',$sc,525,2197,6.5,50.0,2.5,33.5,3.5,4.5,1.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Prawn Cocktail Crisps',$sc,520,2176,5.5,52.0,3.0,33.0,3.0,4.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Wotsits (cheese)',$sc,510,2134,5.5,55.0,4.0,30.0,4.0,1.0,1.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pork Scratchings',$sc,575,2406,61.0,0.5,0.0,36.0,14.0,0.0,2.5, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Poppadoms (fried)',$sc,480,2008,22.0,46.0,1.0,23.5,3.0,5.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Breadsticks (plain)',$sc,410,1715,12.0,70.0,3.0,9.5,1.5,3.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Rice Cakes (plain)',$sc,387,1619,8.0,81.0,0.5,2.8,0.6,3.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bombay Mix',$sc,500,2092,18.0,35.0,5.0,32.0,4.0,7.0,1.5, 0,0,0,0,1,1,0,0, 0,0,1,1,1,1],

			// ── MISCELLANEOUS PANTRY (8) ──
			['Gelatine Sheets (per 100g)',$co,335,1402,85.0,0.0,0.0,0.1,0.0,0.0,0.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Agar Agar (powder)',$co,306,1280,6.2,80.7,0.0,0.3,0.0,7.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Yeast Extract (generic)',$co,252,1054,38.0,20.0,1.0,0.3,0.1,3.5,10.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Capers (in brine)',$co,23,96,2.4,4.9,0.4,0.9,0.3,3.2,9.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sun-dried Tomatoes (in oil)',$fv,213,891,5.1,12.3,9.0,14.5,1.9,5.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Olives (green, in brine)',$fv,145,607,1.0,3.8,0.5,15.3,2.0,3.3,3.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Olives (black, in brine)',$fv,115,481,0.8,6.3,0.0,10.7,1.4,3.2,2.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Artichoke Hearts (in brine)',$fv,30,126,2.0,4.5,1.0,0.3,0.1,4.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v29.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 29 );
	}

	/** Seed v30: 50 more unique foods. */
	public static function seed_v30(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 30 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── EAST EUROPEAN & SCANDINAVIAN (10) ──
			['Blini (buckwheat, plain)',$bc,215,900,6.0,30.0,2.5,8.0,2.5,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Pelmeni (Russian dumplings)',$tk,210,879,9.0,24.0,0.5,8.5,3.5,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Stroopwafel',$sc,450,1883,4.0,62.0,32.0,20.5,11.0,1.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Gravlax (cured salmon)',$fs,175,732,20.0,3.0,2.5,9.5,1.5,0.0,3.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Swedish Meatballs',$mp,195,816,15.0,8.0,1.5,12.0,5.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Knäckebröd (crispbread)',$bc,340,1423,9.5,65.0,2.5,2.0,0.3,14.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kissel (berry dessert)',$sc,65,272,0.3,16.0,12.0,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kalakukko (Finnish fish bread)',$tk,195,816,10.0,28.0,1.0,5.0,1.0,1.5,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Smørrebrød (open sandwich, avg)',$tk,230,962,11.0,20.0,2.0,12.0,4.0,1.5,1.5, 1,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Varenyky (cherry, sweet)',$sc,195,816,4.0,35.0,12.0,4.0,1.5,1.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── JAPANESE EXTRAS (10) ──
			['Mochi (plain rice cake)',$sc,225,941,4.0,50.0,18.0,0.5,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Daifuku (red bean mochi)',$sc,240,1004,5.0,52.0,25.0,0.5,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Taiyaki (custard-filled)',$sc,265,1109,5.5,42.0,18.0,8.0,2.0,1.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Onigiri (salmon)',$bc,155,649,5.5,28.0,0.5,2.0,0.3,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Edamame (salted, shelled)',$lp,122,510,11.0,8.9,2.2,5.2,0.6,5.2,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Tempura Prawns (2 pcs)',$tk,230,962,9.0,20.0,0.5,13.0,2.0,0.5,0.6, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Udon Noodles (cooked)',$bc,105,439,3.0,22.0,0.5,0.5,0.1,0.8,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Soba Noodles (cooked)',$bc,99,414,5.0,21.4,0.0,0.1,0.0,0.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tonkatsu (pork cutlet)',$mp,275,1151,16.0,15.0,2.0,17.5,4.5,0.5,0.8, 0,0,0,1,0,1,1,0, 0,0,0,0,0,0],
			['Dorayaki (pancake, red bean)',$sc,260,1088,5.5,45.0,22.0,5.5,1.5,2.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── KOREAN (8) ──
			['Bulgogi (beef)',$mp,155,649,18.0,8.0,6.0,5.5,2.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Japchae (glass noodles)',$tk,135,565,3.0,22.0,6.0,4.0,0.5,1.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Tteokbokki (rice cakes)',$tk,190,795,4.0,40.0,8.0,1.5,0.3,1.0,1.5, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Korean Fried Chicken (yangnyeom)',$tk,250,1046,15.0,18.0,10.0,13.5,3.0,0.5,1.2, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Kimchi Jjigae (stew)',$tk,55,230,4.0,4.5,1.5,2.5,0.5,1.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Kimbap (1 roll, sliced)',$tk,140,586,5.0,24.0,2.0,2.5,0.5,1.0,0.8, 1,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Pajeon (Korean pancake)',$tk,195,816,5.0,24.0,2.5,9.0,1.5,1.5,1.0, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Sundubu-jjigae (soft tofu stew)',$tk,65,272,5.5,3.5,1.0,3.5,0.8,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,1,1],

			// ── SUGAR & SWEETENERS (6) ──
			['Golden Syrup',$co,325,1360,0.0,79.0,79.0,0.0,0.0,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Maple Syrup',$co,260,1088,0.0,67.0,60.0,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Agave Nectar',$co,310,1297,0.1,76.0,68.0,0.5,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Treacle (black)',$co,290,1213,1.8,68.0,65.0,0.0,0.0,0.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Stevia (granulated blend)',$co,0,0,0.0,1.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Icing Sugar',$co,398,1665,0.0,99.8,99.8,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── TINNED / JARRED FISH (6) ──
			['Anchovies (in oil, drained)',$fs,210,879,28.9,0.0,0.0,9.7,2.2,0.0,6.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Sardines in Olive Oil',$fs,208,870,24.6,0.0,0.0,11.5,2.8,0.0,0.8, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Mackerel in Tomato Sauce',$fs,178,745,14.0,3.5,2.5,12.0,2.8,0.0,0.6, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Pilchards in Tomato Sauce',$fs,155,649,14.5,2.0,1.5,10.0,3.0,0.0,0.6, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Crab Meat (canned)',$fs,81,339,18.1,0.0,0.0,0.9,0.2,0.0,0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Salmon (canned, pink)',$fs,135,565,20.0,0.0,0.0,6.0,1.4,0.0,0.5, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v30.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 30 );
	}

	/** Seed v31: 50 more unique foods. */
	public static function seed_v31(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 31 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── SQUASH & GOURD (6) ──
			['Butternut Squash (raw)',$fv,45,188,1.0,11.7,2.2,0.1,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Acorn Squash (baked)',$fv,56,234,1.1,14.6,0.0,0.1,0.0,4.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Spaghetti Squash (cooked)',$fv,31,130,0.6,6.9,2.8,0.6,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pattypan Squash (cooked)',$fv,20,84,1.2,4.3,2.0,0.3,0.1,1.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Kabocha Squash (cooked)',$fv,34,142,0.7,8.0,3.5,0.1,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Bitter Melon (raw)',$fv,17,71,1.0,3.7,0.0,0.2,0.0,2.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── SEAWEED & ALGAE (5) ──
			['Nori (dried sheet, per 100g)',$fv,190,795,30.7,44.3,3.5,0.3,0.1,0.3,1.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Wakame (rehydrated)',$fv,45,188,3.0,9.1,0.5,0.6,0.1,0.5,6.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dulse (dried)',$fv,250,1046,20.0,44.0,3.0,3.0,0.5,8.0,4.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Spirulina (dried powder)',$fv,290,1213,57.5,23.9,3.1,7.7,2.7,3.6,2.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kelp (raw)',$fv,43,180,1.7,9.6,0.6,0.6,0.2,1.3,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── MIDDLE EASTERN SWEETS & PASTRIES (5) ──
			['Kunafa (cheese)',$sc,400,1674,7.0,42.0,25.0,23.0,10.0,1.0,0.5, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Basbousa (semolina cake)',$sc,330,1381,4.0,48.0,30.0,14.0,6.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ma\'amoul (date-filled)',$sc,380,1590,5.5,52.0,28.0,17.0,4.5,2.0,0.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Lokum (rose flavour)',$sc,350,1464,0.5,88.0,75.0,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Qatayef (stuffed pancake)',$sc,310,1297,6.0,42.0,22.0,13.0,5.0,1.5,0.2, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],

			// ── MILK ALTERNATIVES & DAIRY DRINKS (6) ──
			['Rice Milk (unsweetened)',$dr,47,197,0.3,9.2,4.0,1.0,0.1,0.3,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hemp Milk (unsweetened)',$dr,39,163,0.8,1.0,0.0,2.8,0.3,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cashew Milk (unsweetened)',$dr,18,75,0.5,1.5,0.0,1.2,0.2,0.0,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Buttermilk',$de,40,167,3.3,4.8,4.8,0.9,0.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Lassi (sweet, mango)',$dr,72,301,2.0,13.0,12.5,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Ayran (Turkish yoghurt drink)',$dr,36,151,1.7,2.5,2.5,2.0,1.3,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── CHARCUTERIE & DELI (6) ──
			['Pepperoni',$mp,494,2067,20.0,4.0,1.5,44.0,17.0,0.0,3.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Turkey Breast (deli sliced)',$mp,104,435,17.0,3.5,1.5,2.0,0.5,0.5,1.5, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Roast Beef (deli sliced)',$mp,130,544,21.0,1.0,0.5,4.5,1.8,0.0,1.2, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Chorizo',$mp,455,1904,24.0,2.0,1.0,38.5,14.5,0.0,3.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Serrano Ham',$mp,241,1008,31.0,0.5,0.0,12.5,4.5,0.0,5.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Lardo (cured pork fat)',$mp,810,3389,1.5,0.0,0.0,90.0,35.0,0.0,2.5, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0],

			// ── COOKING OILS (6) ──
			['Avocado Oil',$fo,884,3699,0.0,0.0,0.0,100.0,12.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Walnut Oil',$fo,884,3699,0.0,0.0,0.0,100.0,9.0,0.0,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Flaxseed Oil',$fo,884,3699,0.0,0.0,0.0,100.0,9.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Ghee (clarified butter)',$fo,900,3766,0.0,0.0,0.0,99.5,62.0,0.0,0.0, 0,0,1,0,0,0,0,0, 1,1,1,1,0,1],
			['Toasted Sesame Oil',$fo,884,3699,0.0,0.0,0.0,100.0,14.2,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Truffle Oil',$fo,884,3699,0.0,0.0,0.0,100.0,14.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── VINEGARS (4) ──
			['Balsamic Vinegar',$co,88,368,0.5,17.0,15.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Apple Cider Vinegar',$co,22,92,0.0,0.9,0.4,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Rice Vinegar',$co,18,75,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Red Wine Vinegar',$co,19,79,0.0,0.6,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── SPECIALITY GRAINS & SEEDS (6) ──
			['Amaranth (raw)',$bc,371,1552,13.6,65.3,1.7,7.0,1.5,6.7,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Teff (raw)',$bc,367,1536,13.3,73.1,1.8,2.4,0.4,8.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Freekeh (dry)',$bc,330,1381,14.0,63.0,1.0,3.0,0.5,13.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Spelt (raw grain)',$bc,338,1414,14.6,70.2,6.8,2.4,0.4,10.7,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Millet (raw)',$bc,378,1582,11.0,72.8,1.7,4.2,0.7,8.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Buckwheat Groats (raw)',$bc,343,1435,13.3,71.5,0.0,3.4,0.7,10.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v31.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 31 );
	}

	/** Seed v32: 200 more unique foods. */
	public static function seed_v32(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 32 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── BERRIES (10) ──
			['Blackcurrant',$fv,63,264,1.4,15.4,7.0,0.4,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Redcurrant',$fv,56,234,1.4,13.8,7.4,0.2,0.0,4.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Gooseberry',$fv,44,184,0.9,10.2,7.0,0.6,0.0,4.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Lingonberry',$fv,50,209,0.4,11.5,8.0,0.5,0.0,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Elderberry',$fv,73,305,0.7,18.4,7.0,0.5,0.0,7.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mulberry',$fv,43,180,1.4,9.8,8.1,0.4,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Boysenberry',$fv,43,180,1.4,9.6,4.9,0.5,0.0,5.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Loganberry',$fv,55,230,1.5,13.0,7.7,0.3,0.0,5.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Açaí Berry (frozen pulp)',$fv,70,293,1.0,4.0,2.0,5.0,1.5,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Physalis (cape gooseberry)',$fv,53,222,1.9,11.2,0.0,0.7,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── CITRUS (6) ──
			['Blood Orange',$fv,50,209,0.9,12.0,9.0,0.2,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Pomelo',$fv,38,159,0.8,9.6,0.0,0.0,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Yuzu (juice, per 100ml)',$fv,20,84,0.5,7.0,3.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Bergamot (juice)',$fv,25,105,0.4,8.0,4.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Clementine',$fv,47,197,0.9,12.0,9.2,0.2,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Ugli Fruit',$fv,45,188,0.8,11.0,9.0,0.1,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── STONE FRUIT (4) ──
			['Greengage',$fv,46,192,0.7,11.4,8.5,0.2,0.0,1.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Damson',$fv,46,192,0.7,11.4,9.9,0.3,0.0,1.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Loquat',$fv,47,197,0.4,12.1,0.0,0.2,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Mirabelle Plum',$fv,60,251,0.7,15.5,14.0,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── PEPPERS & CHILLIES (8) ──
			['Scotch Bonnet Pepper',$fv,40,167,2.0,8.8,5.0,0.4,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Jalapeño Pepper (raw)',$fv,29,121,0.9,6.5,4.1,0.4,0.0,2.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Habanero Pepper',$fv,40,167,1.9,8.8,5.3,0.4,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chipotle Pepper (dried)',$fv,282,1180,11.0,50.0,32.0,8.0,0.9,28.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Banana Pepper (pickled)',$fv,20,84,0.5,4.0,2.0,0.5,0.1,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Padrón Pepper',$fv,22,92,0.9,4.6,2.5,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Piquillo Pepper (roasted)',$fv,30,126,1.0,5.5,4.0,0.5,0.1,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ancho Chilli (dried)',$fv,280,1172,12.0,50.0,30.0,8.5,1.0,28.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── INDIAN SNACKS & SIDES (10) ──
			['Puri (fried bread)',$bc,370,1548,6.0,42.0,1.5,20.0,3.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bhatura (deep-fried bread)',$bc,355,1485,7.0,40.0,2.0,18.5,3.0,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Dosa (plain)',$bc,165,690,4.5,28.0,0.5,4.0,0.5,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Masala Dosa',$bc,205,858,5.0,30.0,1.5,7.0,1.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Idli (steamed, 1 pc)',$bc,39,163,2.0,8.0,0.2,0.2,0.0,0.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Vada (medu, 1 pc)',$lp,180,753,6.5,16.0,1.0,10.5,1.5,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Uttapam',$bc,200,837,5.0,30.0,1.5,6.5,1.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Raita (cucumber)',$co,55,230,2.5,4.0,3.5,3.0,1.8,0.3,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Mango Chutney',$co,220,920,0.3,56.0,52.0,0.2,0.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lime Pickle',$co,190,795,1.5,12.0,5.0,15.5,2.0,3.0,6.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── EAST AFRICAN & HORN OF AFRICA (8) ──
			['Injera (Ethiopian flatbread)',$bc,130,544,3.5,27.0,0.5,0.5,0.1,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Doro Wat (chicken stew)',$tk,150,628,14.0,6.0,2.0,8.0,2.5,1.5,0.6, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Misir Wat (red lentil stew)',$tk,105,439,6.0,14.0,1.5,3.0,0.4,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kitfo (Ethiopian beef tartare)',$mp,225,941,18.0,1.0,0.0,16.5,6.5,0.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Sambusa (Somali, beef)',$tk,295,1234,8.5,26.0,1.0,17.5,6.0,1.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ugali (maize porridge)',$bc,140,586,2.5,30.0,0.2,0.5,0.1,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pilau Rice (Swahili-style)',$tk,175,732,4.0,28.0,0.5,5.5,1.5,0.8,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Mandazi (East African doughnut)',$sc,330,1381,5.5,42.0,12.0,16.0,3.5,1.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── TURKISH & GREEK (10) ──
			['Börek (cheese)',$tk,280,1172,8.0,24.0,1.5,17.0,7.0,1.0,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Simit (Turkish sesame ring)',$bc,305,1276,9.5,52.0,4.0,7.5,1.0,3.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Gözleme (spinach & cheese)',$tk,240,1004,8.5,28.0,1.5,10.5,4.5,1.5,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['İskender Kebab',$tk,195,816,14.0,12.0,3.0,10.5,4.5,0.5,1.0, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Adana Kebab',$mp,235,983,17.0,2.0,0.5,17.5,7.5,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Spanakopita (spinach pie)',$tk,245,1025,7.5,20.0,1.5,15.5,6.5,1.5,0.7, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Tzatziki',$co,66,276,3.5,4.0,3.0,4.0,2.5,0.3,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Souvlaki (pork)',$mp,195,816,20.0,2.0,0.5,12.0,4.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Moussaka (vegetarian)',$tk,100,418,4.5,8.5,3.0,5.5,2.5,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Loukoumades (honey puffs)',$sc,345,1443,5.0,42.0,22.0,17.0,3.0,1.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── AUSTRALIAN & NEW ZEALAND (6) ──
			['Vegemite',$co,185,774,25.0,25.0,2.5,0.5,0.0,3.5,9.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lamington',$sc,340,1423,5.0,45.0,30.0,16.0,10.0,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pavlova (with cream & fruit)',$sc,240,1004,3.0,35.0,32.0,10.0,6.5,0.5,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Meat Pie (Australian)',$tk,255,1067,9.0,22.0,1.0,15.0,7.0,0.8,0.9, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Tim Tam (1 biscuit, 18g)',$sc,495,2071,5.5,60.0,40.0,26.0,16.0,2.5,0.3, 0,0,1,0,0,1,1,0, 0,0,1,1,0,1],
			['Anzac Biscuit (1 biscuit)',$sc,455,1904,5.0,64.0,35.0,20.0,10.0,4.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ── GERMAN & AUSTRIAN (8) ──
			['Bratwurst (grilled)',$mp,295,1234,13.0,3.0,0.5,25.5,9.5,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Currywurst',$mp,250,1046,11.5,15.0,10.0,15.5,5.5,0.5,2.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Lebkuchen (gingerbread)',$sc,365,1527,5.0,68.0,40.0,8.5,2.0,2.0,0.3, 0,0,0,1,1,1,0,0, 0,0,1,1,0,1],
			['Stollen (Christmas bread)',$bc,380,1590,6.0,52.0,25.0,16.5,8.0,2.5,0.5, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Pretzel (Bavarian, large)',$bc,295,1234,8.0,56.0,2.0,4.0,0.5,2.0,3.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kaiserschmarrn (torn pancake)',$sc,280,1172,8.0,38.0,18.0,10.5,5.5,1.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Apfelstrudel (apple strudel)',$sc,260,1088,3.0,38.0,20.0,10.5,5.0,1.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Sauerbraten (pot roast, gravy)',$mp,160,669,18.0,8.5,4.0,6.0,2.0,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],

			// ── FAST FOOD STAPLES (12) ──
			['Cheeseburger (single patty)',$tk,265,1109,14.0,24.0,5.0,13.0,5.5,1.5,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Double Cheeseburger',$tk,320,1339,18.5,24.0,5.0,18.0,8.0,1.5,1.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Chicken Burger (breaded)',$tk,250,1046,13.0,26.0,3.0,10.5,2.0,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Veggie Burger',$tk,210,879,10.0,26.0,4.0,7.5,1.5,3.0,0.9, 0,0,0,1,0,1,1,0, 0,0,1,1,0,1],
			['Hot Dog (in bun)',$tk,270,1130,10.0,24.0,4.0,15.0,5.5,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Pepperoni Pizza (slice)',$tk,270,1130,11.0,28.0,3.5,12.5,5.0,1.5,1.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Hawaiian Pizza (slice)',$tk,240,1004,10.5,28.0,5.5,9.5,4.0,1.5,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Garlic Dough Balls (3 pcs)',$bc,190,795,4.5,26.0,1.0,7.5,3.5,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Mozzarella Sticks (4 pcs)',$sc,310,1297,12.0,24.0,1.0,19.0,8.5,1.0,1.2, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Chicken Wings (BBQ, 4 pcs)',$tk,215,900,18.0,8.0,5.0,12.5,3.5,0.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Onion Bhaji (takeaway, 1pc)',$tk,190,795,3.0,16.0,2.5,12.5,1.5,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Coleslaw (deli-style)',$fv,175,732,1.0,8.0,6.5,15.5,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],

			// ── EGG DISHES (8) ──
			['Scotch Egg (shop-bought)',$sc,250,1046,11.0,14.0,0.5,17.0,6.0,0.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Omelette (plain, 2-egg)',$de,155,649,11.0,0.5,0.5,12.0,3.5,0.0,0.5, 0,0,0,1,0,0,0,0, 1,1,1,1,0,1],
			['Scrambled Eggs (with butter)',$de,165,690,10.5,1.5,1.0,13.0,5.5,0.0,0.5, 0,0,1,1,0,0,0,0, 1,1,1,1,0,1],
			['Eggs Benedict',$tk,255,1067,12.0,18.0,1.5,15.5,6.0,0.5,1.0, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Eggs Royale',$tk,270,1130,14.0,18.0,1.5,16.5,6.5,0.5,1.5, 1,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Egg Fried Rice',$tk,190,795,5.5,25.0,1.0,7.5,1.5,1.0,1.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,1],
			['Frittata (vegetable)',$de,145,607,8.5,4.0,2.0,10.5,3.5,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Devilled Eggs (2 halves)',$de,155,649,7.5,1.5,0.5,13.5,3.0,0.0,0.5, 0,0,0,1,0,0,0,0, 1,0,1,1,0,1],

			// ── ALLIUM FAMILY (6) ──
			['Shallot (raw)',$fv,72,301,2.5,16.8,7.9,0.1,0.0,3.2,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Leek (raw)',$fv,61,255,1.5,14.2,3.9,0.3,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Spring Onion (raw)',$fv,32,134,1.8,7.3,2.3,0.2,0.0,2.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Chive (fresh)',$fv,30,126,3.3,4.4,1.9,0.7,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Wild Garlic (raw)',$fv,34,142,2.5,6.0,1.5,0.3,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Elephant Garlic',$fv,120,502,5.0,26.0,1.0,0.3,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ── FRESHWATER FISH (6) ──
			['Rainbow Trout (grilled)',$fs,135,565,20.5,0.0,0.0,5.8,1.6,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Pike (baked)',$fs,100,418,22.0,0.0,0.0,0.9,0.2,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Perch (baked)',$fs,114,477,21.0,0.0,0.0,3.0,0.6,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Catfish (fried)',$fs,229,958,14.5,8.5,0.5,13.5,3.5,0.5,0.5, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Tilapia (grilled)',$fs,128,536,26.0,0.0,0.0,2.7,0.9,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Carp (baked)',$fs,162,678,22.9,0.0,0.0,7.2,1.4,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── TEA, COFFEE & HOT DRINKS (8) ──
			['Espresso (single shot)',$dr,2,8,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Cappuccino (semi-skimmed milk)',$dr,52,218,3.0,5.0,5.0,2.0,1.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Flat White (whole milk)',$dr,65,272,3.5,5.0,5.0,3.5,2.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Iced Latte (semi-skimmed)',$dr,45,188,2.5,5.0,4.5,1.5,0.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Mocha (with whipped cream)',$dr,180,753,5.0,25.0,22.0,7.0,4.5,0.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Green Tea (brewed, no sugar)',$dr,1,4,0.2,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Earl Grey Tea (brewed, no sugar)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Turmeric Latte (golden milk)',$dr,60,251,2.0,8.0,6.5,2.5,1.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── MISCELLANEOUS INTERNATIONAL (8) ──
			['Pierogi (sauerkraut & mushroom)',$tk,180,753,4.5,28.0,1.0,5.5,1.5,2.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,1,1,1],
			['Croqueta (ham, Spanish)',$tk,220,920,6.5,18.0,1.0,13.5,4.5,0.5,0.7, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Cachapa (Venezuelan corn pancake)',$bc,230,962,6.0,32.0,8.0,9.0,3.5,2.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pirozhki (meat-filled)',$tk,275,1151,9.0,28.0,2.0,14.0,4.5,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Cevapi (grilled sausage)',$mp,250,1046,18.0,4.0,0.5,18.0,7.5,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Arepas con Queso',$bc,245,1025,8.0,30.0,1.0,10.5,4.5,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Khachapuri (cheese bread)',$bc,280,1172,10.0,28.0,2.0,14.5,7.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Çiğ Köfte (vegetarian)',$tk,150,628,5.5,25.0,2.0,3.0,0.4,5.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v32.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 32 );
	}

	/** Seed v33: 200 native UK + European foods. */
	public static function seed_v33(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 33 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ── IRISH (15) ──
			['Colcannon',$fv,110,460,2.5,14.0,1.0,5.0,3.0,2.0,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Champ (spring onion mash)',$fv,105,439,2.0,14.0,1.0,4.5,2.8,1.5,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Irish Stew (lamb)',$tk,95,397,7.5,7.0,1.0,4.0,1.8,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Boxty (potato pancake)',$fv,160,669,3.0,22.0,0.5,7.0,1.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Soda Bread (white)',$bc,265,1109,7.5,48.0,3.5,4.0,1.5,2.5,1.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Soda Bread (brown)',$bc,250,1046,8.5,45.0,3.0,4.5,1.5,5.0,1.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Barmbrack (fruit loaf)',$bc,290,1213,5.0,55.0,25.0,5.5,1.5,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Coddle (Dublin coddle)',$tk,105,439,7.0,6.0,1.0,5.5,2.0,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Irish Breakfast Roll',$tk,280,1172,12.0,28.0,2.0,13.5,4.5,1.5,1.5, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Crubeens (pig\'s trotters)',$mp,210,879,18.0,0.0,0.0,15.0,5.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Black & White Pudding (Irish)',$mp,300,1255,10.0,24.0,0.5,18.5,7.0,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Potato Farls',$fv,175,732,3.5,28.0,0.5,6.0,3.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Guinness Stew (beef)',$tk,110,460,8.0,6.5,1.5,5.5,2.5,0.8,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Tayto Cheese & Onion (crisps, Irish)',$sc,520,2176,6.0,51.0,2.5,33.0,3.5,4.0,1.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Yellowman (honeycomb toffee)',$sc,420,1757,0.5,82.0,78.0,10.0,5.5,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── SCOTTISH (10) ──
			['Cranachan',$sc,255,1067,3.0,22.0,12.0,17.0,10.0,2.0,0.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Tattie Scone',$fv,195,816,3.5,28.0,0.5,8.0,4.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Dundee Cake',$sc,345,1443,5.0,50.0,30.0,14.0,5.0,2.0,0.3, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Dundee Marmalade',$co,262,1096,0.2,65.0,60.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Forfar Bridie',$tk,270,1130,10.0,22.0,1.0,16.0,7.0,1.0,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Stovies',$tk,95,397,5.0,10.0,1.0,3.5,1.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Arbroath Smokie',$fs,155,649,25.0,0.0,0.0,6.0,1.0,0.0,2.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Clootie Dumpling',$sc,310,1297,4.0,50.0,28.0,11.0,5.0,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Cullen Skink (traditional)',$tk,98,410,6.5,7.5,1.0,4.5,2.5,0.3,0.6, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Tablet (Scottish fudge)',$sc,450,1883,2.5,75.0,72.0,16.0,10.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── WELSH (8) ──
			['Cawl (lamb broth)',$tk,85,356,6.5,7.0,1.0,3.5,1.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Glamorgan Sausage',$de,230,962,10.0,18.0,1.5,13.0,6.0,1.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Bara Brith (fruit loaf)',$bc,285,1193,5.0,52.0,25.0,6.0,1.5,2.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Welsh Cakes',$sc,365,1527,4.5,50.0,22.0,16.0,8.0,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Laverbread (laver seaweed)',$fv,55,230,6.0,4.5,0.0,1.5,0.3,0.0,0.8, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Welsh Lamb Cawl',$tk,90,377,7.0,7.5,1.0,3.5,1.5,1.5,0.5, 0,0,0,0,0,0,0,1, 0,0,1,0,0,0],
			['Caerphilly Cheese Scone',$bc,345,1443,9.0,35.0,3.0,18.5,10.0,1.5,1.0, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Cockle and Laverbread Breakfast',$fs,120,502,10.0,5.0,0.5,6.5,2.0,0.5,1.5, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── FRENCH (15) ──
			['Cassoulet',$tk,145,607,10.0,10.0,1.5,7.5,2.5,3.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Bouillabaisse',$tk,85,356,10.0,4.0,1.0,3.0,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Coq au Vin',$tk,135,565,12.0,3.0,1.0,7.0,2.0,0.3,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Beef Bourguignon',$tk,125,523,10.5,5.0,1.5,6.5,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Duck Confit',$mp,290,1213,18.0,0.0,0.0,24.0,7.5,0.0,1.5, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Quiche Florentine (spinach)',$tk,230,962,9.5,16.0,2.0,14.5,6.5,1.0,0.7, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Croque Madame',$tk,325,1360,16.0,22.0,2.0,19.5,9.0,0.5,1.3, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Tarte Tatin',$sc,245,1025,2.0,35.0,22.0,11.0,6.5,1.0,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Madeleines',$sc,395,1653,6.5,50.0,28.0,19.5,10.0,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Éclair (chocolate)',$sc,295,1234,5.5,28.0,18.0,18.0,10.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Millefeuille',$sc,340,1423,4.5,35.0,20.0,20.5,12.5,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Crème Caramel',$sc,145,607,3.5,22.0,20.0,4.5,2.5,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Soupe à l\'Oignon Gratinée',$tk,95,397,4.5,8.0,3.5,5.0,3.0,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,1],
			['Niçoise Salad',$fv,115,481,8.0,5.0,2.0,7.5,1.5,1.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Rillettes (pork)',$mp,385,1611,16.0,0.5,0.0,35.5,14.0,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],

			// ── PORTUGUESE (10) ──
			['Pastéis de Nata (custard tart)',$sc,310,1297,4.5,35.0,20.0,17.0,8.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bacalhau à Brás (salt cod)',$tk,165,690,13.0,8.0,0.5,9.5,1.5,1.0,1.5, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Caldo Verde (kale soup)',$tk,55,230,2.5,6.0,0.5,2.5,0.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Francesinha (Porto sandwich)',$tk,310,1297,16.0,22.0,3.0,18.0,7.0,0.8,1.5, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Bifana (pork sandwich)',$tk,275,1151,15.0,28.0,1.0,11.5,3.5,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Arroz de Pato (duck rice)',$tk,175,732,10.0,20.0,0.5,6.5,2.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cataplana (seafood stew)',$tk,95,397,11.0,4.5,1.0,3.5,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Piri Piri Chicken',$mp,180,753,24.0,2.0,1.0,8.5,2.0,0.0,1.0, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Alheira (smoked sausage)',$mp,295,1234,14.0,15.0,0.5,20.0,6.5,0.5,2.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Bolo de Arroz (rice muffin)',$sc,330,1381,4.0,52.0,28.0,12.0,3.5,0.5,0.3, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],

			// ── DUTCH & BELGIAN (12) ──
			['Bitterballen (3 pcs)',$tk,280,1172,9.0,20.0,0.5,18.0,5.5,0.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Stamppot (hutspot)',$fv,90,377,2.5,12.0,3.0,3.5,2.0,1.5,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Erwtensoep (Dutch pea soup)',$tk,75,314,5.0,8.5,1.0,2.0,0.8,2.5,0.8, 0,0,0,0,0,0,0,1, 0,0,0,0,0,0],
			['Kroket (Dutch croquette)',$tk,245,1025,7.0,20.0,0.5,15.5,4.5,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Poffertjes (mini pancakes)',$sc,245,1025,6.0,38.0,12.0,7.5,2.5,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Herring (Dutch matjes)',$fs,178,745,15.5,0.0,0.0,13.0,3.0,0.0,2.5, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Belgian Waffle (Liège)',$sc,370,1548,6.0,50.0,28.0,16.5,9.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Waterzooi (chicken)',$tk,110,460,10.0,4.5,1.0,6.0,3.0,0.5,0.5, 0,0,1,1,0,0,0,1, 0,0,1,0,0,0],
			['Moules-Frites',$tk,190,795,12.0,18.0,0.5,7.0,1.5,1.5,1.2, 1,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Speculaas Biscuit',$sc,440,1841,5.0,68.0,32.0,16.5,8.5,1.5,0.4, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Frikandel',$mp,240,1004,10.5,14.0,1.0,16.0,5.5,0.5,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Carbonade Flamande (Flemish stew)',$tk,120,502,9.0,6.0,2.5,6.0,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],

			// ── SWISS (6) ──
			['Cheese Fondue',$de,295,1234,18.0,3.5,1.0,22.0,13.5,0.0,1.2, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Raclette (with potato, per serving)',$de,255,1067,12.0,15.0,0.5,17.0,10.5,1.0,1.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Rösti (Swiss, with cheese)',$fv,195,816,5.5,20.0,0.5,10.5,5.0,1.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Birchermüesli (original)',$bc,160,669,4.0,22.0,12.0,6.0,1.0,3.0,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Zürcher Geschnetzeltes (veal)',$tk,165,690,18.0,3.5,1.0,8.5,4.0,0.3,0.5, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Bündner Nusstorte (nut tart)',$sc,475,1988,8.0,50.0,32.0,27.0,7.5,3.0,0.2, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],

			// ── SPANISH (10) ──
			['Patatas Bravas',$fv,160,669,2.5,22.0,2.0,7.0,1.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gambas al Ajillo (garlic prawns)',$fs,185,774,14.0,3.0,0.5,13.0,2.0,0.0,0.8, 1,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Tortilla Española (potato omelette)',$fv,145,607,5.0,13.0,1.0,8.0,1.5,1.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Gazpacho Andaluz',$fv,32,134,0.8,5.5,4.0,0.8,0.1,0.8,0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pimientos de Padrón',$fv,55,230,1.5,4.0,2.0,3.5,0.5,1.5,0.3, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Croquetas de Jamón (2 pcs)',$tk,225,941,7.0,18.0,1.0,14.0,4.5,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Churros con Chocolate (Spanish)',$sc,385,1611,5.5,48.0,22.0,19.0,6.5,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Fabada Asturiana (bean stew)',$tk,140,586,8.5,12.0,0.5,6.5,2.0,4.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Manchego with Membrillo',$de,350,1464,22.0,15.0,12.0,23.0,14.0,0.5,1.2, 0,0,1,0,0,0,0,0, 1,0,0,1,0,1],
			['Crema Catalana',$sc,260,1088,4.0,28.0,25.0,14.0,8.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],

			// ── ITALIAN (BEYOND PASTA) (12) ──
			['Ossobuco',$mp,180,753,22.0,2.0,0.5,9.0,3.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Saltimbocca (veal)',$mp,210,879,25.0,1.5,0.0,11.5,5.0,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Vitello Tonnato',$mp,185,774,22.0,1.0,0.5,10.0,2.5,0.0,0.8, 1,0,0,1,0,0,0,0, 0,0,0,0,0,0],
			['Ribollita (Tuscan bread soup)',$tk,75,314,3.0,10.5,1.5,2.5,0.4,2.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Panzanella (bread salad)',$fv,125,523,3.0,15.0,3.0,5.5,0.8,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Caponata (Sicilian aubergine)',$fv,90,377,1.5,9.0,6.5,5.0,0.7,2.5,0.5, 0,0,0,0,0,0,0,1, 0,0,1,1,1,1],
			['Focaccia (rosemary)',$bc,270,1130,7.0,38.0,1.5,10.0,1.5,2.0,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Ciabatta',$bc,265,1109,9.0,48.0,2.5,4.5,0.7,2.5,1.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Grissini (breadstick)',$bc,400,1674,12.0,68.0,3.0,9.5,1.5,3.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Panna (Italian cream)',$de,335,1402,2.5,3.5,3.5,35.0,22.0,0.0,0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Cannoli (Sicilian)',$sc,360,1506,7.0,40.0,25.0,19.0,8.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Panettone',$bc,340,1423,7.0,52.0,22.0,12.0,6.0,1.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── NORDIC / SCANDINAVIAN EXTRAS (10) ──
			['Smörgåstårta (sandwich cake)',$tk,195,816,8.5,14.0,2.5,12.0,5.0,0.5,1.0, 1,1,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Janssons Frestelse (potato gratin)',$tk,140,586,4.5,14.0,0.5,7.5,3.0,1.0,1.2, 1,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Koldskål (Danish buttermilk dessert)',$de,75,314,3.0,12.0,10.0,1.5,0.8,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Kanelbulle (Swedish cinnamon bun)',$bc,350,1464,5.5,48.0,20.0,15.0,8.0,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Kladdkaka (Swedish chocolate cake)',$sc,390,1632,5.0,48.0,38.0,20.0,12.0,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Semla (Swedish cream bun)',$sc,320,1339,6.5,38.0,18.0,16.0,7.5,3.0,0.3, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Raggmunk (Swedish potato pancake)',$fv,175,732,4.0,20.0,1.0,9.5,2.0,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Lefse (Norwegian flatbread)',$bc,215,900,3.0,38.0,2.0,5.5,2.5,1.5,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Lutefisk (reconstituted)',$fs,80,335,18.0,0.0,0.0,0.5,0.1,0.0,0.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Skagen (prawn & mayo topping)',$fs,210,879,10.0,2.0,0.5,18.0,2.5,0.0,0.8, 1,1,0,1,0,0,0,0, 1,0,1,0,0,0],

			// ── TRADITIONAL BRITISH REGIONAL (12) ──
			['Eccles Cake',$sc,395,1653,4.0,55.0,32.0,18.0,9.0,2.0,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Chorley Cake',$sc,370,1548,4.0,52.0,28.0,16.5,8.0,2.0,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Lardy Cake',$bc,380,1590,5.0,48.0,18.0,19.0,8.5,1.0,0.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Bedfordshire Clanger',$tk,265,1109,8.0,25.0,5.0,15.0,6.0,1.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Stargazy Pie',$tk,240,1004,11.0,18.0,1.0,14.0,5.5,0.5,0.8, 1,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Singing Hinnies',$sc,340,1423,5.0,48.0,15.0,14.5,7.5,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Fat Rascals (Yorkshire)',$sc,380,1590,5.0,52.0,22.0,17.0,9.0,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bakewell Tart',$sc,385,1611,4.5,48.0,30.0,20.0,6.5,2.0,0.2, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Battenberg Cake',$sc,370,1548,6.0,52.0,35.0,16.0,5.0,1.5,0.3, 0,0,0,1,1,1,0,0, 0,0,1,1,0,1],
			['Parkin (Yorkshire oatcake)',$sc,380,1590,4.5,58.0,30.0,15.0,4.0,3.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Kendal Mint Cake',$sc,400,1674,0.0,95.0,92.0,3.0,1.5,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,0,1],
			['Bury Black Pudding',$mp,300,1255,13.0,20.0,0.5,22.0,9.0,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v33.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 33 );
	}

	/** Seed v34: Native Turkish & Arabian foods. */
	public static function seed_v34(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 34 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ══════════════════════════════════════
			// ── TURKISH SOUPS (8) ──
			// ══════════════════════════════════════
			['Mercimek Çorbası (red lentil soup)',$tk,75,314,4.5,10.5,1.0,1.5,0.3,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['İşkembe Çorbası (tripe soup)',$tk,65,272,5.5,3.5,0.5,3.0,1.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tarhana Çorbası (fermented soup)',$tk,55,230,2.5,8.5,0.5,1.5,0.3,0.5,1.0, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Yayla Çorbası (yoghurt soup)',$tk,60,251,2.5,6.5,1.5,2.5,1.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Ezogelin Çorbası (bride\'s soup)',$tk,70,293,3.5,10.0,1.5,1.5,0.3,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Düğün Çorbası (wedding soup)',$tk,85,356,5.0,5.0,0.5,5.0,2.5,0.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Toyga Çorbası (wheat & yoghurt)',$tk,70,293,3.0,9.0,1.0,2.5,1.5,1.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Paça Çorbası (lamb trotter soup)',$tk,75,314,6.5,1.0,0.0,5.0,2.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── TURKISH KEBABS & GRILLS (12) ──
			['Döner Kebab (meat only)',$mp,215,900,18.0,2.0,0.5,15.0,6.5,0.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Şiş Kebab (lamb)',$mp,195,816,20.0,1.0,0.5,12.0,5.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Urfa Kebab',$mp,225,941,16.0,3.0,1.0,16.5,7.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Beyti Kebab (wrapped in lavash)',$mp,240,1004,17.0,12.0,2.0,14.0,6.0,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Ali Nazik Kebab',$mp,180,753,15.0,5.0,3.0,11.0,4.5,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Tantuni (beef wrap)',$tk,210,879,14.0,18.0,1.0,9.5,3.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Cağ Kebabı (spit-roasted lamb)',$mp,250,1046,19.0,0.0,0.0,19.0,8.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tavuk Şiş (chicken skewers)',$mp,155,649,22.0,2.0,1.0,6.5,1.5,0.0,0.8, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Köfte (Turkish meatballs)',$mp,235,983,16.0,6.0,1.0,16.0,6.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Patlıcan Kebab (aubergine & meat)',$tk,165,690,10.0,8.0,4.0,10.5,4.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Testi Kebabı (pottery kebab)',$tk,140,586,11.0,6.0,2.0,8.0,3.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kokoreç (grilled offal wrap)',$tk,280,1172,15.0,20.0,1.0,16.0,6.0,0.5,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],

			// ── TURKISH MAIN DISHES (12) ──
			['Manti (Turkish dumplings)',$tk,195,816,8.0,22.0,1.5,8.5,4.0,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Pide (Turkish flatbread pizza)',$tk,250,1046,10.0,30.0,2.0,10.0,4.0,1.0,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['İmam Bayıldı (stuffed aubergine)',$fv,95,397,1.5,8.0,5.0,6.5,0.9,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Karnıyarık (meat-stuffed aubergine)',$tk,130,544,6.0,7.0,4.0,9.0,3.0,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hünkar Beğendi (sultan\'s delight)',$tk,175,732,10.0,8.0,3.0,12.0,5.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Güveç (clay pot stew)',$tk,100,418,7.0,7.5,3.0,4.5,1.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Musakka (Turkish, no béchamel)',$tk,110,460,6.0,7.5,3.5,6.5,2.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kuzu Tandır (slow-roasted lamb)',$mp,220,920,22.0,0.0,0.0,14.5,6.5,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Etli Nohut (chickpea & meat stew)',$tk,125,523,8.0,12.0,1.5,5.0,1.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kuru Fasulye (white bean stew)',$lp,105,439,5.5,14.0,1.5,3.0,0.5,4.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Çılbır (Turkish poached eggs)',$de,175,732,8.5,4.0,2.5,14.0,7.0,0.0,0.5, 0,0,1,1,0,0,0,0, 1,0,1,1,0,1],
			['Menemen (Turkish scrambled eggs)',$de,120,502,5.5,6.0,4.0,8.5,2.5,1.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],

			// ── TURKISH BREADS & PASTRIES (8) ──
			['Pide Ekmeği (flatbread)',$bc,275,1151,8.5,52.0,2.0,3.5,0.5,2.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bazlama (village bread)',$bc,260,1088,7.5,50.0,1.5,3.0,0.4,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Açma (soft roll)',$bc,310,1297,7.0,42.0,5.0,12.5,5.0,1.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pogaça (stuffed bun)',$bc,325,1360,7.5,38.0,3.0,16.0,7.0,1.5,0.7, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Su Böreği (water börek)',$tk,245,1025,8.0,24.0,1.0,13.0,6.0,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Sigara Böreği (cigar pastry)',$tk,310,1297,9.0,26.0,1.0,19.0,8.0,0.5,0.7, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Katmer (Gaziantep pastry)',$sc,420,1757,7.0,38.0,12.0,27.0,14.0,1.0,0.3, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Çiğ Börek (raw dough börek)',$tk,285,1193,10.0,26.0,1.0,16.0,6.5,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── TURKISH MEZES & SIDES (10) ──
			['Acılı Ezme (spicy paste)',$co,55,230,1.5,6.0,4.0,2.5,0.3,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cacık (yoghurt & cucumber)',$co,50,209,2.5,3.5,3.0,2.5,1.5,0.3,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Haydari (thick yoghurt dip)',$co,120,502,5.0,4.0,3.0,9.5,5.5,0.0,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Atom (walnut & pepper paste)',$co,180,753,4.0,10.0,5.0,14.5,1.5,2.5,0.8, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Piyaz (white bean salad)',$lp,110,460,4.5,12.0,2.0,5.0,0.7,3.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Kısır (bulgur salad)',$fv,135,565,3.5,20.0,2.0,5.0,0.7,3.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Mucver (courgette fritters)',$fv,185,774,5.0,12.0,1.5,13.5,3.5,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Yaprak Sarma (stuffed vine leaves)',$fv,200,837,2.5,12.0,2.0,16.0,2.5,2.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Midye Dolma (stuffed mussels)',$fs,105,439,5.5,12.0,0.5,3.5,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Çiğ Köfte (raw meatball, vegan)',$tk,155,649,5.5,26.0,2.0,3.0,0.4,5.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── TURKISH DESSERTS (10) ──
			['Sütlaç (Turkish rice pudding)',$sc,130,544,3.5,20.0,14.0,4.0,2.5,0.1,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kazandibi (caramelised milk pudding)',$sc,145,607,4.0,22.0,16.0,4.5,2.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Tavuk Göğsü (chicken breast pudding)',$sc,140,586,5.5,20.0,15.0,4.0,2.5,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Aşure (Noah\'s pudding)',$sc,160,669,3.5,30.0,18.0,3.0,0.5,2.5,0.0, 0,0,0,0,1,1,0,0, 0,0,1,1,1,1],
			['Tulumba (syrupy dough)',$sc,350,1464,3.5,48.0,30.0,16.0,3.0,0.5,0.1, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Lokma (fried dough balls)',$sc,330,1381,3.5,45.0,28.0,15.5,2.5,0.5,0.1, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Güllaç (rose-scented milk pastry)',$sc,155,649,4.0,26.0,20.0,3.5,2.0,0.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Revani (semolina cake)',$sc,290,1213,4.0,48.0,32.0,9.5,2.5,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Şekerpare (syrupy cookie)',$sc,340,1423,4.0,50.0,35.0,14.0,5.5,0.5,0.1, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Dondurma (Turkish ice cream)',$sc,190,795,3.5,28.0,25.0,7.0,4.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── TURKISH DRINKS (4) ──
			['Şalgam Suyu (turnip juice)',$dr,15,63,0.5,3.0,1.5,0.0,0.0,0.5,2.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Salep (orchid root drink)',$dr,85,356,1.5,15.0,12.0,2.5,1.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Turkish Coffee (sade)',$dr,5,21,0.3,0.8,0.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Boza (fermented millet drink)',$dr,55,230,0.8,12.0,8.0,0.5,0.1,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── TURKISH BREAKFAST ITEMS (6) ──
			['Sucuk (spiced sausage, fried)',$mp,380,1590,18.0,2.0,0.5,33.5,14.0,0.0,3.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pastırma (cured beef)',$mp,165,690,28.0,1.0,0.0,5.5,2.5,0.0,4.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaymak (clotted cream)',$de,350,1464,2.5,1.5,1.5,37.0,24.0,0.0,0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Bal Kaymak (honey & cream)',$de,310,1297,2.0,30.0,28.0,20.0,13.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Menemen (with sucuk)',$tk,145,607,7.5,5.5,3.5,10.0,3.5,1.0,1.0, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Gözleme (potato filling)',$tk,225,941,5.0,30.0,1.0,9.5,2.0,1.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── ARABIAN / GULF MAIN DISHES (15) ──
			// ══════════════════════════════════════
			['Kabsa (Saudi rice & chicken)',$tk,175,732,11.0,22.0,1.0,5.0,1.5,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mandi (Yemeni rice & lamb)',$tk,195,816,12.0,22.0,0.5,7.0,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Machboos (Bahraini spiced rice)',$tk,180,753,10.5,23.0,1.0,5.5,1.5,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mansaf (Jordanian lamb & yoghurt rice)',$tk,210,879,12.5,20.0,1.5,9.0,4.0,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Maqluba (Palestinian upside-down rice)',$tk,170,711,8.5,20.0,2.0,6.5,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Musakhan (Palestinian chicken & sumac)',$tk,195,816,13.0,18.0,2.0,8.5,1.5,1.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Harees (wheat & meat porridge)',$tk,135,565,8.0,18.0,0.5,3.5,1.5,1.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Thareed (bread & stew)',$tk,130,544,7.0,15.0,2.0,4.5,1.5,1.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Saloona (Gulf vegetable stew)',$tk,80,335,3.5,8.5,3.0,3.5,0.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Margoog (flat bread stew)',$tk,110,460,6.0,12.0,2.0,4.0,1.5,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Zurbian (Yemeni spiced rice & meat)',$tk,200,837,11.0,24.0,1.0,7.0,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Madrooba (Emirati porridge)',$tk,100,418,4.0,12.0,1.0,4.0,2.0,1.5,0.4, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Shawarma Plate (lamb, with rice)',$tk,195,816,12.0,20.0,1.0,8.0,3.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fatteh (chickpea & bread)',$tk,175,732,6.0,18.0,1.5,9.0,4.0,2.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Arayes (stuffed pitta, grilled)',$tk,245,1025,12.0,20.0,1.0,13.5,5.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],

			// ── ARABIAN MEZES & APPETISERS (10) ──
			['Mutabbal (smoky aubergine dip)',$co,105,439,1.5,6.0,2.0,8.5,1.2,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Muhammara (walnut & pepper dip)',$co,210,879,3.5,14.0,7.0,16.0,1.8,2.5,0.5, 0,0,0,0,1,1,0,0, 0,0,1,1,1,1],
			['Shanklish (aged cheese balls)',$de,290,1213,18.0,3.0,0.5,23.0,14.0,0.0,2.5, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1],
			['Sambousek (fried pastry, meat)',$tk,310,1297,8.0,26.0,1.0,19.5,5.5,1.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kibbeh Nayyeh (raw kibbeh)',$mp,185,774,14.0,12.0,0.5,9.5,3.5,2.5,0.3, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Shish Tawook (marinated chicken)',$mp,155,649,24.0,3.0,1.5,5.0,1.0,0.0,0.8, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Fatayer (spinach pie)',$tk,260,1088,6.0,28.0,1.5,14.0,2.5,2.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kibbeh (fried, stuffed)',$tk,280,1172,10.0,18.0,0.5,19.0,5.0,2.0,0.5, 0,0,0,0,1,1,0,0, 0,0,1,0,0,0],
			['Warak Enab (stuffed grape leaves)',$fv,190,795,2.5,12.0,2.0,15.0,2.5,2.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Balila (warm chickpea salad)',$lp,130,544,6.5,16.0,1.0,4.5,0.6,4.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── ARABIAN RICE & GRAINS (6) ──
			['Freekeh Pilaf (with chicken)',$tk,160,669,9.5,20.0,0.5,4.5,1.0,3.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Mujaddara (lentils & rice)',$lp,140,586,5.5,24.0,1.0,2.5,0.4,4.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sayyadieh (fish with rice)',$tk,160,669,11.0,20.0,1.0,4.0,0.5,0.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kushari (Egyptian rice & lentils)',$tk,145,607,4.5,25.0,2.5,2.5,0.4,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ful Medames (Egyptian fava beans)',$lp,110,460,7.0,16.0,1.0,2.5,0.4,5.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bamia (okra stew)',$tk,85,356,4.5,6.5,2.5,4.5,1.0,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── ARABIAN SWEETS & DESSERTS (12) ──
			['Luqaimat (sweet dumplings)',$sc,330,1381,3.0,48.0,30.0,14.5,2.5,0.5,0.0, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Warbat (cream-filled pastry)',$sc,310,1297,4.0,35.0,22.0,17.5,8.0,0.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Umm Ali (bread pudding, Egyptian)',$sc,235,983,5.0,30.0,20.0,10.5,5.5,1.0,0.2, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Knafeh Nabulsieh (cheese knafeh)',$sc,395,1653,7.5,42.0,28.0,22.0,10.5,0.5,0.5, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Maamoul (pistachio-filled)',$sc,400,1674,6.0,48.0,25.0,20.0,4.0,2.5,0.1, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Halawet el Jibn (cheese rolls)',$sc,310,1297,5.0,40.0,30.0,15.0,8.5,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Muhallabia (milk pudding)',$sc,120,502,3.5,18.0,14.0,4.0,2.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Basbousa (with syrup, Egyptian)',$sc,335,1402,4.0,50.0,32.0,13.5,5.5,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Balah el Sham (choux pastry)',$sc,345,1443,4.0,42.0,28.0,18.0,4.0,0.5,0.1, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ataif (stuffed pancake, Ramadan)',$sc,290,1213,5.5,38.0,22.0,13.0,5.0,1.5,0.2, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Mafroukeh (semolina & cream)',$sc,305,1276,5.0,42.0,28.0,13.5,7.0,1.0,0.2, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Qatayef bil Ashta (cream-filled)',$sc,295,1234,5.5,38.0,24.0,14.0,6.0,1.0,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ── ARABIAN DRINKS (4) ──
			['Jallab (grape & rose drink)',$dr,75,314,0.5,19.0,18.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tamarind Juice (tamar hindi)',$dr,45,188,0.3,11.5,10.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Qamar al-Din (apricot nectar)',$dr,65,272,0.5,16.0,14.0,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Laban (buttermilk drink, salted)',$dr,38,159,1.8,2.5,2.5,2.0,1.3,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── ARABIAN BREADS (5) ──
			['Khubz (Arabic flatbread)',$bc,275,1151,8.0,53.0,2.0,2.0,0.3,2.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Markook (paper-thin bread)',$bc,260,1088,7.5,52.0,1.5,2.5,0.4,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tannour Bread (clay oven)',$bc,265,1109,8.0,52.0,1.5,2.5,0.3,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Samoon (Iraqi diamond bread)',$bc,280,1172,8.5,52.0,2.0,4.0,0.5,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Ka\'ak (sesame bread ring)',$bc,350,1464,10.0,55.0,4.0,10.0,1.5,3.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC. Seeded v34.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 34 );
	}

	/** Seed v35: Native African foods. */
	public static function seed_v35(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 35 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ══════════════════════════════════════
			// ── WEST AFRICAN (35) ──
			// ══════════════════════════════════════
			// -- Soups & Stews --
			['Egusi Soup (melon seed)',$tk,155,649,8.5,5.0,1.5,11.5,2.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ogbono Soup (wild mango seed)',$tk,140,586,7.5,6.0,0.5,10.0,2.0,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Efo Riro (vegetable soup)',$tk,90,377,5.0,5.0,1.5,5.5,1.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pepper Soup (goat)',$tk,85,356,8.5,3.0,0.5,4.0,1.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pepper Soup (catfish)',$tk,75,314,9.0,3.0,0.5,3.0,0.8,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Groundnut Soup (peanut stew)',$tk,145,607,8.0,7.0,2.0,10.0,2.0,1.5,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Palm Nut Soup (Banga)',$tk,160,669,6.5,5.0,1.0,13.5,6.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Afang Soup (Nigerian)',$tk,95,397,6.0,4.5,0.5,6.0,2.0,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Okra Soup (West African)',$tk,70,293,4.5,6.0,1.5,3.5,0.8,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Light Soup (Ghanaian tomato)',$tk,55,230,3.5,5.5,3.0,2.0,0.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Starches & Sides --
			['Pounded Yam',$fv,150,628,1.5,35.0,0.5,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Amala (yam flour swallow)',$fv,155,649,1.0,37.0,1.0,0.3,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Eba (garri swallow)',$fv,165,690,0.5,40.0,0.5,0.2,0.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Banku (Ghanaian corn dough)',$bc,140,586,2.0,32.0,0.5,0.5,0.1,1.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kenkey (fermented corn)',$bc,135,565,2.5,30.0,0.5,0.5,0.1,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Garri (dry, soaked)',$fv,130,544,0.5,33.0,0.5,0.2,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tuwo Shinkafa (rice swallow)',$bc,135,565,2.0,30.5,0.1,0.3,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tuwo Masara (corn swallow)',$bc,140,586,2.5,31.0,0.5,0.5,0.1,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			// -- Main Dishes --
			['Jollof Rice (Ghanaian)',$tk,170,711,4.0,26.0,2.5,5.0,1.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Waakye (rice and beans)',$tk,145,607,5.5,24.0,0.5,2.5,0.5,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Thieboudienne (Senegalese fish rice)',$tk,155,649,9.0,22.0,2.0,3.5,0.5,1.5,0.7, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Yassa Chicken (Senegalese)',$tk,145,607,15.0,6.0,2.0,7.0,1.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mafe (West African peanut stew)',$tk,155,649,9.0,10.0,3.0,9.5,2.0,2.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Domoda (Gambian peanut stew)',$tk,150,628,8.5,10.0,2.5,9.0,2.0,1.5,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Kontomire Stew (cocoyam leaf)',$tk,90,377,4.0,6.0,1.0,5.5,1.5,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kelewele (spiced fried plantain)',$fv,185,774,1.0,30.0,16.0,7.0,2.0,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Moin Moin (steamed bean pudding)',$lp,145,607,8.5,15.0,1.0,5.0,1.0,3.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Akara (bean fritters)',$lp,280,1172,10.0,20.0,1.0,18.0,3.0,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chin Chin (fried dough snack)',$sc,450,1883,5.0,52.0,15.0,25.0,5.0,1.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Puff Puff (Nigerian doughnut)',$sc,330,1381,4.0,42.0,10.0,16.0,2.5,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Kilishi (dried spiced beef)',$mp,310,1297,52.0,10.0,5.0,7.0,2.5,1.0,2.0, 0,0,0,0,1,0,0,0, 0,1,1,1,0,0],
			// -- Drinks --
			['Zobo (hibiscus drink)',$dr,30,126,0.5,7.0,6.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kunu (millet drink)',$dr,55,230,1.0,12.0,5.0,0.5,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Palm Wine (fresh)',$dr,55,230,0.3,10.5,8.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],
			['Bissap (Senegalese hibiscus)',$dr,28,117,0.5,6.5,5.5,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── EAST AFRICAN (25) ──
			// ══════════════════════════════════════
			['Nyama Choma (grilled meat)',$mp,195,816,25.0,0.0,0.0,10.5,4.5,0.0,0.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Sukuma Wiki (collard greens)',$fv,35,146,2.5,4.5,1.0,1.0,0.2,2.5,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Githeri (maize and beans)',$lp,130,544,5.0,22.0,1.0,1.5,0.3,5.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mukimo (mashed potato & greens)',$fv,120,502,3.5,18.0,1.0,4.0,1.5,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Irio (Kenyan mash)',$fv,115,481,3.0,18.0,2.0,3.5,1.5,2.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chapati (East African, layered)',$bc,295,1234,7.0,42.0,1.0,11.5,2.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Matoke (steamed green banana)',$fv,120,502,1.0,30.0,3.0,0.3,0.1,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kachumbari (tomato onion salad)',$fv,25,105,1.0,4.5,3.0,0.3,0.0,1.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Bhajia (Kenyan potato fritters)',$fv,240,1004,4.0,25.0,1.5,14.0,2.0,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Samosa (East African, beef)',$tk,280,1172,8.0,24.0,1.0,17.0,5.5,1.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Tilapia (Kenyan fried)',$fs,195,816,22.0,5.0,0.5,10.0,2.0,0.5,0.5, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ugali na Nyama (ugali with meat)',$tk,145,607,8.0,22.0,0.5,3.0,1.0,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Wali wa Nazi (coconut rice)',$bc,185,774,3.5,30.0,1.0,6.0,4.5,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mchuzi wa Samaki (fish curry)',$tk,100,418,10.0,5.0,2.0,4.5,2.5,0.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mishkaki (beef skewers)',$mp,185,774,22.0,3.0,1.5,9.5,3.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Vitumbua (rice fritters)',$sc,300,1255,3.5,40.0,10.0,14.0,8.0,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Urojo (Zanzibar mix soup)',$tk,80,335,3.5,12.0,2.0,2.0,0.5,1.0,0.8, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Zanzibar Pizza (stuffed pancake)',$tk,245,1025,8.0,22.0,2.0,14.0,4.0,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			// -- Ethiopian/Eritrean --
			['Tibs (sauteed beef)',$mp,175,732,20.0,3.0,1.0,9.5,3.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shiro Wat (chickpea stew)',$tk,110,460,5.5,14.0,1.5,3.5,0.5,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Beyainatu (Ethiopian veggie platter)',$tk,95,397,4.0,12.0,2.0,3.5,0.5,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gomen (Ethiopian collard greens)',$fv,45,188,2.5,4.0,0.5,2.5,0.3,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ful (Ethiopian fava beans)',$lp,115,481,7.0,16.5,1.0,2.5,0.4,5.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kocho (Ethiopian bread from enset)',$bc,115,481,1.5,28.0,1.0,0.2,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tella (Ethiopian beer)',$dr,40,167,0.5,8.5,2.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],

			// ══════════════════════════════════════
			// ── NORTH AFRICAN (20) ──
			// ══════════════════════════════════════
			['Couscous (Moroccan, with veg)',$tk,140,586,4.5,22.0,2.5,3.5,0.5,2.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tagine (chicken, preserved lemon)',$tk,135,565,12.0,7.0,3.0,7.0,1.5,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tagine (lamb, prune)',$tk,155,649,11.0,12.0,8.0,7.5,3.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tagine (vegetable)',$tk,75,314,2.5,10.0,3.5,3.0,0.4,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pastilla (chicken pie, Moroccan)',$tk,285,1193,11.0,28.0,8.0,15.0,4.5,1.5,0.6, 0,0,0,1,1,1,0,0, 0,0,1,0,0,0],
			['Harira (Moroccan soup)',$tk,60,251,3.5,8.5,1.5,1.5,0.3,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chermoula (Moroccan marinade)',$co,75,314,1.5,5.0,1.0,5.5,0.8,1.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Msemen (Moroccan flatbread)',$bc,310,1297,6.0,40.0,1.5,14.0,5.0,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Baghrir (thousand hole pancake)',$bc,195,816,5.0,35.0,2.0,3.5,0.5,1.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Briouats (stuffed pastry)',$tk,310,1297,8.0,26.0,3.0,19.5,5.0,1.0,0.5, 0,0,0,1,1,1,0,0, 0,0,1,0,0,0],
			['Mechoui (slow-roasted lamb)',$mp,230,962,24.0,0.0,0.0,14.5,6.5,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Zaalouk (aubergine salad)',$fv,65,272,1.5,6.0,3.5,4.0,0.5,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Taktouka (pepper and tomato salad)',$fv,55,230,1.0,5.0,3.5,3.5,0.5,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rfissa (Moroccan chicken & lentils)',$tk,155,649,10.0,16.0,1.5,6.0,1.5,3.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chakchouka (Tunisian eggs)',$tk,115,481,6.0,8.0,5.0,6.5,1.5,2.0,0.6, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Brik (Tunisian fried pastry)',$tk,290,1213,8.5,22.0,0.5,19.0,3.5,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Lablabi (Tunisian chickpea soup)',$tk,85,356,4.5,12.0,1.0,2.0,0.3,3.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Makroud (date-filled semolina)',$sc,360,1506,5.0,52.0,28.0,15.0,3.5,3.0,0.1, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Mint Tea (Moroccan, sweetened)',$dr,35,146,0.0,9.0,8.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sellou (Moroccan energy mix)',$sc,475,1988,10.0,45.0,22.0,28.0,4.0,5.0,0.0, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── SOUTHERN AFRICAN (20) ──
			// ══════════════════════════════════════
			['Biltong (South African)',$mp,285,1193,56.0,2.0,1.0,5.0,2.0,0.0,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Boerewors (grilled)',$mp,290,1213,16.0,2.0,0.5,24.0,10.0,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Bobotie (Cape Malay mince)',$tk,155,649,10.0,8.0,4.0,9.5,3.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Chakalaka (spiced relish)',$fv,55,230,2.0,8.0,4.0,1.5,0.2,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pap (maize porridge, stiff)',$bc,130,544,2.5,28.0,0.2,0.5,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pap en Wors (pap with sausage)',$tk,185,774,8.5,22.0,0.5,7.5,3.0,1.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Shisa Nyama (braai meat)',$mp,210,879,24.0,0.0,0.0,12.5,5.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Potjiekos (pot stew)',$tk,110,460,8.0,7.0,1.5,5.5,2.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bunny Chow (Durban curry in bread)',$tk,195,816,9.0,22.0,3.0,8.0,2.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Vetkoek (fried bread)',$bc,330,1381,6.0,38.0,3.0,18.0,3.5,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Koeksisters (braided doughnut)',$sc,395,1653,3.5,55.0,38.0,18.0,5.0,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Melktert (milk tart)',$sc,250,1046,5.0,32.0,20.0,11.5,6.5,0.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Malva Pudding',$sc,310,1297,4.0,45.0,32.0,13.0,7.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Rusks (South African)',$bc,420,1757,8.5,58.0,18.0,17.0,8.5,3.0,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Samp and Beans',$lp,120,502,5.5,22.0,0.5,1.0,0.2,4.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Morogo (wild spinach)',$fv,30,126,3.0,3.5,0.5,0.5,0.1,2.5,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Droewors (dried sausage)',$mp,420,1757,38.0,3.0,1.0,28.0,12.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Rooibos Tea (unsweetened)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Nshima (Zambian maize porridge)',$bc,125,523,2.0,28.0,0.2,0.5,0.1,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sadza (Zimbabwean maize porridge)',$bc,128,536,2.5,28.0,0.2,0.5,0.1,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── CENTRAL AFRICAN (10) ──
			// ══════════════════════════════════════
			['Moambe Chicken (palm butter)',$tk,165,690,14.0,4.0,1.5,10.5,4.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ndolé (Cameroonian bitter leaf stew)',$tk,110,460,8.0,5.0,1.0,7.0,1.5,2.5,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Poulet DG (chicken, Cameroon)',$tk,155,649,13.0,8.0,2.0,8.0,2.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Saka Saka (cassava leaves)',$fv,55,230,4.0,5.0,0.5,2.5,0.5,3.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chikwangue (cassava bread)',$fv,140,586,0.5,35.0,0.5,0.2,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kwanga (fermented cassava)',$fv,135,565,0.5,34.0,0.5,0.2,0.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Liboke (fish in banana leaf)',$fs,115,481,16.0,2.0,0.5,5.0,1.0,0.5,0.4, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Brochettes (Rwandan grilled meat)',$mp,190,795,22.0,2.0,0.5,10.5,4.0,0.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Isombe (cassava leaf & peanut)',$fv,95,397,5.5,6.0,0.5,5.5,1.5,3.0,0.3, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Plantain Chips (Central African)',$sc,350,1464,1.5,48.0,15.0,17.0,6.0,3.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ══════════════════════════════════════
			// ── AFRICAN STAPLE INGREDIENTS (10) ──
			// ══════════════════════════════════════
			['Shea Butter (cooking grade)',$fo,884,3699,0.0,0.0,0.0,100.0,47.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Palm Oil (red)',$fo,884,3699,0.0,0.0,0.0,100.0,49.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Dawadawa (locust bean, fermented)',$co,350,1464,35.0,20.0,5.0,15.0,3.0,10.0,2.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ogiri (fermented sesame)',$co,400,1674,20.0,15.0,2.0,30.0,5.0,5.0,3.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baobab Powder',$fv,230,962,2.5,58.0,22.0,0.5,0.2,45.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Moringa Leaf Powder',$fv,250,1046,27.0,38.0,5.0,2.5,0.5,20.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Fonio (grain, raw)',$bc,360,1506,7.0,75.0,0.5,1.5,0.3,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Teff Injera (Ethiopian)',$bc,110,460,3.0,22.0,0.5,0.5,0.1,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Crayfish Powder (dried)',$co,290,1213,55.0,3.0,0.5,5.0,1.0,0.0,4.0, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Suya Spice Mix (yaji)',$co,350,1464,15.0,30.0,5.0,20.0,3.0,8.0,2.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'M&W 8th ed. / USDA FDC / FAO WAFCT. Seeded v35.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 35 );
	}

	/** Seed v36: Native North & South American foods. */
	public static function seed_v36(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 36 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ══════════════════════════════════════
			// ── NATIVE NORTH AMERICAN / USA (50) ──
			// ══════════════════════════════════════
			// -- Classic American --
			['Pulled Pork Sandwich',$tk,275,1151,16.0,28.0,8.0,11.0,3.5,1.0,1.2, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Philly Cheesesteak',$tk,290,1213,17.0,24.0,2.5,14.5,6.0,1.0,1.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Reuben Sandwich',$tk,280,1172,16.0,22.0,3.0,14.5,5.5,2.0,1.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Sloppy Joe',$tk,220,920,12.0,22.0,8.0,9.0,3.5,1.0,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Buffalo Wings (10 pcs)',$mp,230,962,18.0,2.0,0.5,16.5,5.5,0.0,2.5, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Mac and Cheese (American)',$tk,310,1297,11.0,28.0,3.0,17.0,9.5,0.5,0.9, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Clam Chowder (New England)',$tk,85,356,3.5,9.0,1.5,4.0,2.0,0.5,0.7, 1,1,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Gumbo (Louisiana)',$tk,95,397,7.0,6.5,1.0,4.5,1.0,1.5,0.8, 1,1,0,0,0,0,0,1, 0,0,0,0,0,0],
			['Jambalaya (Cajun)',$tk,145,607,9.0,18.0,2.0,4.0,1.0,1.0,0.8, 0,1,0,0,0,0,0,1, 0,0,0,0,0,0],
			['Crawfish Étouffée',$tk,110,460,8.5,8.0,1.0,5.0,2.5,0.5,0.8, 0,1,1,0,0,1,0,1, 0,0,0,0,0,0],
			['Po\' Boy (fried shrimp)',$tk,295,1234,10.0,32.0,2.0,14.0,2.5,1.0,1.2, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Biscuits and Gravy',$tk,245,1025,7.0,24.0,2.0,13.5,5.5,0.5,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Chicken Fried Steak',$mp,280,1172,15.0,18.0,0.5,17.0,5.0,0.5,1.0, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Meatloaf (American)',$mp,195,816,13.0,8.0,2.5,12.5,5.0,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Cornbread',$bc,225,941,4.0,32.0,8.0,8.5,2.0,2.0,0.6, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Hush Puppies (6 pcs)',$sc,310,1297,4.5,38.0,5.0,15.5,2.5,2.0,0.8, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Grits (cheese, cooked)',$bc,110,460,3.5,15.0,0.5,4.0,2.5,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pancakes (American, with syrup)',$sc,250,1046,5.0,42.0,22.0,6.5,2.0,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Waffles (American, with syrup)',$sc,270,1130,5.5,42.0,22.0,9.0,3.0,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['French Toast',$sc,230,962,6.5,28.0,10.0,10.0,3.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			// -- BBQ & Southern --
			['Brisket (Texas BBQ)',$mp,215,900,22.0,2.0,1.5,13.0,5.0,0.0,1.0, 0,0,0,0,0,0,0,0, 0,1,0,1,0,0],
			['Baby Back Ribs (BBQ)',$mp,255,1067,18.0,8.0,6.5,17.0,6.0,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Fried Chicken (Southern)',$mp,265,1109,18.0,10.0,0.5,17.0,4.0,0.5,1.2, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Collard Greens (with ham hock)',$fv,45,188,3.0,5.0,0.5,2.0,0.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Coleslaw (American, creamy)',$fv,150,628,1.0,12.0,9.0,11.0,1.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Corndog on a Stick',$sc,290,1213,8.5,28.0,5.0,16.0,4.5,1.0,1.2, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Cobb Salad',$fv,155,649,12.0,5.0,2.0,10.0,4.0,2.0,0.8, 0,0,1,1,0,0,0,0, 0,0,0,0,0,0],
			['Caesar Salad (with croutons)',$fv,130,544,5.0,8.0,1.0,9.5,2.5,1.5,0.7, 1,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			// -- Tex-Mex --
			['Chimichanga (beef)',$tk,295,1234,12.0,26.0,2.0,17.0,5.5,1.5,0.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Huevos Rancheros',$tk,185,774,9.0,18.0,3.0,9.0,3.0,2.0,0.7, 0,0,1,1,0,0,0,0, 0,0,1,0,0,1],
			['Breakfast Burrito',$tk,230,962,11.0,24.0,1.5,10.0,4.0,1.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Chili con Queso (dip)',$co,190,795,7.0,8.0,3.0,14.5,8.0,0.5,1.0, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Guacamole',$co,160,669,2.0,8.6,0.7,14.7,2.1,6.7,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Pico de Gallo',$co,20,84,0.8,4.0,2.5,0.2,0.0,1.0,0.5, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fajitas (chicken)',$tk,155,649,14.0,10.0,2.5,7.0,1.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chile Relleno (stuffed pepper)',$tk,210,879,8.0,12.0,4.0,14.5,5.5,2.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			// -- Canadian --
			['Poutine (Canadian)',$tk,270,1130,8.5,30.0,2.0,13.0,5.5,2.0,1.2, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Tourtière (meat pie)',$tk,260,1088,12.0,22.0,1.0,14.0,5.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Butter Tart',$sc,420,1757,3.5,55.0,42.0,21.0,10.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Nanaimo Bar',$sc,410,1715,4.0,48.0,35.0,23.0,14.0,2.0,0.2, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Montreal Smoked Meat Sandwich',$tk,265,1109,18.0,24.0,2.0,11.0,4.0,1.0,2.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['BeaverTails (fried dough pastry)',$sc,350,1464,5.0,50.0,18.0,14.5,3.5,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			// -- Native/Indigenous --
			['Bannock (Indigenous bread)',$bc,310,1297,6.5,45.0,3.0,11.5,2.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Wild Rice (cooked)',$bc,101,423,4.0,21.3,0.7,0.3,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Three Sisters Stew (corn, bean, squash)',$tk,75,314,3.0,14.0,2.5,0.5,0.1,3.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pemmican (traditional)',$mp,470,1967,24.0,8.0,2.0,38.0,16.0,1.5,0.5, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			// -- Mexican (beyond Tex-Mex) --
			['Mole Poblano (chicken)',$tk,165,690,14.0,10.0,5.0,8.5,2.0,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tamales (chicken, in husk)',$tk,195,816,8.0,20.0,2.0,9.5,2.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sopes (bean & cheese)',$tk,240,1004,7.0,26.0,2.0,12.0,4.5,3.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Gorditas (stuffed)',$tk,250,1046,9.0,28.0,2.0,11.5,3.5,2.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],

			// ══════════════════════════════════════
			// ── SOUTH AMERICAN (70) ──
			// ══════════════════════════════════════
			// -- BRAZILIAN (20) --
			['Feijoada (black bean stew)',$tk,145,607,10.0,14.0,0.5,6.0,2.0,4.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pão de Queijo (cheese bread)',$bc,340,1423,5.5,45.0,1.5,15.0,7.0,0.5,0.6, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Coxinha (chicken croquette)',$tk,295,1234,10.5,28.0,1.0,16.0,3.5,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Picanha (grilled rump cap)',$mp,250,1046,24.0,0.0,0.0,17.0,7.0,0.0,0.3, 0,0,0,0,0,0,0,0, 0,1,0,1,0,0],
			['Farofa (toasted cassava flour)',$fv,365,1527,1.0,45.0,0.5,20.0,3.0,5.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Acarajé (Bahian bean fritter)',$lp,285,1193,8.5,18.0,1.0,20.0,8.0,2.5,0.5, 0,1,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Moqueca (Bahian fish stew)',$tk,120,502,12.0,5.0,2.0,6.0,3.5,1.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Brigadeiro (chocolate truffle, 1pc)',$sc,440,1841,5.0,62.0,55.0,18.0,10.0,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Beijinho (coconut truffle, 1pc)',$sc,420,1757,4.0,58.0,52.0,19.5,14.0,1.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pastel (fried pastry, meat)',$tk,310,1297,8.5,28.0,1.0,18.5,4.0,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Virado à Paulista (beans & eggs)',$tk,155,649,8.5,16.0,1.0,6.5,1.5,4.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Vatapá (shrimp & nut paste)',$tk,165,690,8.0,12.0,1.5,10.0,3.5,1.5,0.5, 0,1,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Quindim (coconut custard)',$sc,310,1297,5.0,42.0,38.0,14.0,8.0,1.5,0.1, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Açaí na Tigela (açaí bowl)',$sc,140,586,2.5,20.0,12.0,5.5,1.0,4.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Caldo Verde (Brazilian kale soup)',$tk,50,209,2.0,6.5,0.5,2.0,0.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Bolinho de Bacalhau (cod fritter)',$tk,260,1088,12.0,18.0,0.5,15.5,2.5,1.0,0.8, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Tapioca Crepe (Brazilian)',$bc,140,586,0.5,35.0,0.5,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Guaraná (soft drink)',$dr,42,176,0.0,10.5,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Caipirinha (cocktail, per glass)',$dr,210,879,0.0,22.0,20.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chimarrão (mate tea, unsweetened)',$dr,2,8,0.1,0.3,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// -- ARGENTINIAN (12) --
			['Asado (grilled beef)',$mp,230,962,25.0,0.0,0.0,14.0,5.5,0.0,0.3, 0,0,0,0,0,0,0,0, 0,1,0,1,0,0],
			['Empanada Argentina (beef)',$tk,285,1193,9.5,26.0,2.0,16.0,5.0,1.5,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Milanesa (breaded cutlet)',$mp,250,1046,17.0,14.0,0.5,14.0,3.0,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Choripán (chorizo in bread)',$tk,310,1297,14.0,26.0,2.0,16.5,6.0,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Provoleta (grilled provolone)',$de,305,1276,22.0,0.5,0.5,24.0,15.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,0,1,0,1],
			['Dulce de Leche',$co,315,1318,7.0,55.0,50.0,8.0,5.0,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Alfajor (dulce de leche)',$sc,420,1757,5.0,58.0,38.0,19.0,12.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Medialunas (Argentine croissant)',$bc,370,1548,7.0,45.0,12.0,18.0,10.0,1.0,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Locro (corn & meat stew)',$tk,125,523,7.0,15.0,1.5,4.5,1.5,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Matambre Arrollado (rolled flank)',$mp,195,816,22.0,1.0,0.5,11.5,4.5,0.0,0.8, 0,0,0,1,0,0,0,0, 0,0,0,0,0,0],
			['Fugazzeta (onion pizza)',$tk,265,1109,10.5,28.0,3.0,12.5,5.5,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Yerba Mate (hot, unsweetened)',$dr,2,8,0.1,0.3,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// -- PERUVIAN (12) --
			['Ceviche (Peruvian, mixed fish)',$fs,90,377,15.0,5.0,2.0,1.5,0.3,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Lomo Saltado (stir-fried beef)',$tk,165,690,14.0,12.0,2.0,7.5,2.5,1.0,0.8, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Ají de Gallina (creamy chicken)',$tk,185,774,13.0,12.0,1.5,10.0,3.5,1.5,0.5, 0,0,1,0,1,1,0,0, 0,0,1,0,0,0],
			['Anticuchos (beef heart skewers)',$mp,190,795,22.0,3.0,0.5,10.0,3.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Papa a la Huancaína (potato in cheese)',$fv,165,690,5.0,15.0,1.5,10.0,4.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Causa Limeña (potato terrine)',$fv,145,607,5.5,18.0,0.5,6.0,1.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Arroz con Pollo (Peruvian)',$tk,155,649,10.0,20.0,0.5,4.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Seco de Cordero (lamb stew)',$tk,140,586,11.0,8.0,1.0,7.5,3.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Picarones (sweet potato doughnuts)',$sc,310,1297,3.0,45.0,20.0,13.5,2.5,2.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Suspiro Limeño (caramel meringue)',$sc,320,1339,3.5,52.0,48.0,12.0,7.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Chicha Morada (purple corn drink)',$dr,45,188,0.5,11.0,9.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Inca Kola (soft drink)',$dr,48,201,0.0,12.0,12.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// -- COLOMBIAN (10) --
			['Bandeja Paisa (platter, per 100g avg)',$tk,185,774,10.0,18.0,2.0,8.5,3.0,3.0,0.7, 0,0,0,1,0,0,0,0, 0,0,0,0,0,0],
			['Ajiaco (potato chicken soup)',$tk,75,314,5.5,8.5,1.0,2.5,0.8,1.0,0.4, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Patacones (fried green plantain)',$fv,185,774,1.0,32.0,1.5,6.5,1.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Buñuelos Colombianos (cheese fritters)',$sc,330,1381,7.0,35.0,2.0,18.0,6.0,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Empanada Colombiana (corn, beef)',$tk,270,1130,8.0,28.0,1.0,14.0,3.5,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hogao (Colombian sofrito)',$co,55,230,1.0,6.0,3.5,3.0,0.4,1.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lechona (stuffed roast pig)',$mp,265,1109,16.0,10.0,0.5,18.0,6.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Changua (milk & egg soup)',$tk,65,272,4.0,3.5,2.5,3.5,1.5,0.0,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Obleas (wafer with arequipe)',$sc,310,1297,4.0,52.0,35.0,10.0,5.5,0.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Aguapanela (sugarcane drink)',$dr,35,146,0.0,9.0,9.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// -- VENEZUELAN, CHILEAN, OTHER (16) --
			['Hallaca (Venezuelan tamale)',$tk,215,900,8.0,22.0,3.0,11.0,3.5,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pabellón Criollo (Venezuelan rice plate)',$tk,165,690,9.0,22.0,2.0,5.0,1.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Tequeños (cheese sticks)',$sc,340,1423,8.0,30.0,1.5,21.0,8.0,0.5,0.7, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Cachapa con Queso (corn pancake)',$bc,235,983,7.0,30.0,8.0,10.0,4.0,2.0,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Cazuela (Chilean stew)',$tk,85,356,6.0,8.0,1.0,3.0,1.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pastel de Choclo (Chilean corn pie)',$tk,175,732,8.0,18.0,5.0,8.0,3.0,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Completo (Chilean hot dog)',$tk,285,1193,9.0,30.0,5.0,14.5,3.0,1.0,1.2, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Humitas (Chilean corn tamale)',$fv,165,690,4.5,22.0,4.0,7.0,2.5,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,0,1],
			['Curanto (Chilean seafood stew)',$tk,130,544,12.0,8.0,1.0,5.5,1.5,1.0,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sopaipilla (Chilean fried bread)',$bc,310,1297,4.5,38.0,2.0,16.0,3.0,2.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Salteña (Bolivian empanada)',$tk,265,1109,8.5,28.0,5.0,13.5,4.0,1.5,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Silpancho (Bolivian breaded meat)',$tk,225,941,14.0,18.0,1.0,11.0,3.5,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Cuy (roasted guinea pig)',$mp,155,649,19.0,0.0,0.0,8.5,2.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Dulce de Guayaba (guava paste)',$co,290,1213,0.5,72.0,65.0,0.1,0.0,5.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chivito (Uruguayan steak sandwich)',$tk,320,1339,18.0,22.0,2.0,18.5,7.0,0.5,1.2, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Mate Cocido (brewed mate tea)',$dr,3,13,0.2,0.5,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'USDA FDC / M&W 8th ed. Seeded v36.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 36 );
	}

	/** Seed v37: Native & common Australian foods. */
	public static function seed_v37(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 37 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ══════════════════════════════════════
			// ── INDIGENOUS AUSTRALIAN (BUSH TUCKER) (15) ──
			// ══════════════════════════════════════
			['Kangaroo Steak (grilled)',$mp,120,502,24.0,0.0,0.0,2.0,0.6,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Kangaroo Mince',$mp,110,460,22.5,0.0,0.0,2.0,0.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Kangaroo Sausage',$mp,145,607,20.0,3.0,0.5,5.5,1.5,0.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Emu Steak (grilled)',$mp,115,481,23.0,0.0,0.0,2.5,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Crocodile Meat (grilled)',$mp,105,439,22.0,0.0,0.0,1.5,0.4,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Barramundi (grilled)',$fs,110,460,22.5,0.0,0.0,2.0,0.5,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Wattleseed (ground)',$co,320,1339,18.0,42.0,5.0,6.5,0.8,14.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lemon Myrtle (dried leaf)',$co,275,1151,5.0,55.0,2.0,5.0,0.5,20.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Finger Lime (raw)',$fv,30,126,0.8,6.0,1.0,0.5,0.0,5.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Quandong (raw)',$fv,75,314,1.5,15.0,10.0,1.5,0.2,5.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Davidson Plum (raw)',$fv,35,146,0.5,7.5,3.0,0.3,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kakadu Plum (raw)',$fv,45,188,0.5,10.0,5.0,0.2,0.0,4.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Bush Tomato (dried)',$fv,310,1297,12.0,50.0,15.0,5.0,0.5,20.0,0.5, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Macadamia Nut (raw)',$ns,718,3004,7.9,13.8,4.6,75.8,12.1,8.6,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Bunya Nut (boiled)',$ns,180,753,3.0,36.0,6.0,2.0,0.3,3.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── CLASSIC AUSTRALIAN DISHES (25) ──
			// ══════════════════════════════════════
			['Meat Pie (Aussie, standard)',$tk,260,1088,9.5,23.0,1.0,15.0,7.0,0.8,0.9, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Sausage Roll (Australian)',$tk,330,1381,9.0,26.0,2.0,21.5,10.0,1.0,1.2, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Dim Sim (fried, South Melbourne)',$tk,230,962,7.0,22.0,1.5,12.5,3.5,0.5,1.0, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Chiko Roll',$tk,250,1046,5.5,26.0,2.0,14.0,3.0,2.0,1.0, 0,0,0,0,0,1,0,1, 0,0,0,0,0,0],
			['Chicken Parmigiana',$tk,240,1004,17.0,14.0,3.0,13.0,5.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Chicken Schnitzel (Australian)',$mp,230,962,19.0,12.0,0.5,12.5,3.0,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Snag (BBQ sausage in bread)',$tk,280,1172,11.0,24.0,3.0,16.0,6.0,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Barramundi and Chips',$tk,235,983,14.0,22.0,0.5,10.5,2.0,1.5,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Fish and Chips (Australian, flake)',$tk,225,941,12.0,22.0,0.5,10.0,1.5,1.5,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Damper (bush bread)',$bc,280,1172,7.5,48.0,2.0,6.0,2.5,2.0,1.0, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Fairy Bread',$sc,385,1611,6.0,58.0,25.0,14.5,8.5,1.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Smashed Avo on Toast',$bc,220,920,5.5,18.0,1.0,14.5,2.5,3.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Vegemite on Toast',$bc,260,1088,10.0,40.0,3.5,7.0,3.5,2.5,3.0, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Cheese and Bacon Roll',$bc,305,1276,12.0,30.0,3.0,15.5,6.5,1.5,1.2, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Halal Snack Pack (HSP)',$tk,210,879,10.0,18.0,1.5,11.0,4.5,1.5,1.2, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Pie Floater (meat pie in pea soup)',$tk,175,732,7.5,16.0,2.0,9.0,3.5,2.0,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Chop (lamb loin, BBQ)',$mp,215,900,22.0,0.0,0.0,14.0,6.0,0.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Kangaroo Burger',$tk,200,837,18.0,20.0,2.0,5.0,1.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,1,1,0,0,0],
			['Prawn on the Barbie (BBQ prawns)',$fs,115,481,21.0,0.5,0.0,2.5,0.5,0.0,0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Moreton Bay Bug (grilled)',$fs,90,377,19.5,0.0,0.0,1.0,0.2,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Sydney Rock Oyster (6 pcs, raw)',$fs,55,230,6.5,3.0,0.0,1.5,0.3,0.0,0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Yabby (freshwater crayfish, boiled)',$fs,80,335,17.0,0.0,0.0,1.0,0.2,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Salt and Pepper Squid',$fs,195,816,14.0,12.0,0.5,10.5,1.5,0.5,1.0, 1,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Beetroot Burger (Aussie-style)',$tk,245,1025,12.0,24.0,5.0,11.0,4.0,2.0,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Jacket Potato (Aussie, with sour cream)',$fv,145,607,3.5,22.0,1.5,5.0,3.0,2.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── AUSTRALIAN DESSERTS & SWEETS (15) ──
			// ══════════════════════════════════════
			['Lamington (chocolate & coconut)',$sc,345,1443,5.0,45.0,30.0,16.5,10.5,2.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pavlova (classic, cream & passionfruit)',$sc,245,1025,3.0,36.0,33.0,10.5,6.5,0.5,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Anzac Biscuit (golden syrup)',$sc,460,1925,5.0,64.0,36.0,20.5,10.5,4.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Tim Tam Slam (2 biscuits)',$sc,498,2084,5.5,60.0,40.0,26.5,16.0,2.5,0.3, 0,0,1,0,0,1,1,0, 0,0,1,1,0,1],
			['Golden Gaytime (ice cream bar)',$sc,230,962,3.0,24.0,18.0,14.0,8.0,1.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Weis Mango Bar (frozen fruit bar)',$sc,75,314,0.5,18.0,15.0,0.2,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Freddo Frog (chocolate, 12g)',$sc,530,2217,7.0,56.0,55.0,30.0,18.0,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Caramello Koala (chocolate, 15g)',$sc,470,1967,5.0,62.0,58.0,23.0,14.0,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Violet Crumble (honeycomb bar)',$sc,460,1925,3.5,72.0,60.0,18.0,11.0,0.5,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Cherry Ripe (chocolate bar)',$sc,425,1778,3.5,60.0,52.0,19.0,12.0,4.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Iced Vovo Biscuit',$sc,405,1694,4.0,65.0,38.0,14.5,7.5,2.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Arnott\'s Shapes (BBQ, per 100g)',$sc,485,2029,8.0,58.0,8.0,24.0,4.0,3.5,2.0, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Neenish Tart',$sc,395,1653,4.0,50.0,35.0,20.0,11.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Vanilla Slice (Australian)',$sc,290,1213,3.5,38.0,22.0,14.5,9.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Hedgehog Slice',$sc,440,1841,5.5,50.0,38.0,25.0,15.0,2.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── AUSTRALIAN DRINKS (8) ──
			// ══════════════════════════════════════
			['Flat White (Australian-style)',$dr,68,285,3.5,5.0,5.0,3.5,2.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Long Black (coffee)',$dr,3,13,0.2,0.3,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Iced Coffee (Farmers Union, SA)',$dr,75,314,3.0,11.0,10.5,2.0,1.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Oak Chocolate Milk',$dr,72,301,3.0,10.5,10.0,1.5,1.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Bundaberg Ginger Beer',$dr,38,159,0.0,9.5,9.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lemon Lime Bitters (pub)',$dr,30,126,0.0,7.5,7.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Milo (made with milk, per cup)',$dr,95,397,4.5,13.0,11.5,2.5,1.5,1.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Passiona (passionfruit soft drink)',$dr,42,176,0.0,10.5,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── AUSTRALIAN CAFE & BRUNCH (12) ──
			// ══════════════════════════════════════
			['Smashed Avo with Feta & Dukkah',$bc,265,1109,7.5,18.0,1.5,18.5,4.5,4.5,0.5, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Eggs on Toast (poached, sourdough)',$bc,215,900,11.0,20.0,1.0,10.0,2.5,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Big Breakfast (Aussie cafe)',$tk,310,1297,16.0,18.0,3.0,19.5,6.5,2.5,1.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Corn Fritters (with smoked salmon)',$tk,195,816,9.0,18.0,3.0,10.0,2.0,1.5,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Banana Bread (toasted, with butter)',$sc,310,1297,4.5,42.0,22.0,14.0,7.5,2.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Acai Bowl (Australian cafe)',$sc,145,607,3.0,20.0,13.0,6.0,1.0,4.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Shakshuka (Australian brunch)',$tk,115,481,6.5,8.0,5.0,6.5,1.5,2.0,0.7, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Pulled Pork Benedict',$tk,285,1193,14.0,18.0,2.0,17.5,7.0,0.5,1.2, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Ricotta Hotcakes (with berries)',$sc,270,1130,7.0,35.0,16.0,11.5,5.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Granola Bowl (Aussie cafe)',$bc,380,1590,8.0,55.0,20.0,14.5,2.0,6.0,0.1, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],
			['Turkish Bread with Dips (Aussie cafe)',$bc,280,1172,6.5,32.0,2.0,14.0,3.0,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Zucchini Slice (Aussie classic)',$fv,190,795,8.0,12.0,1.5,12.5,4.5,1.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── NEW ZEALAND (10) ──
			// ══════════════════════════════════════
			['Hangi (earth-oven mixed, per 100g)',$tk,130,544,10.0,10.0,2.0,5.5,2.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kumara (NZ sweet potato, roasted)',$fv,110,460,1.5,25.0,8.0,0.5,0.1,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Whitebait Fritter (NZ)',$fs,220,920,12.0,10.0,0.5,15.0,3.0,0.5,0.5, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Afghan Biscuit (NZ chocolate)',$sc,460,1925,5.0,58.0,32.0,24.0,14.0,2.5,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Louise Cake',$sc,380,1590,4.0,52.0,30.0,18.0,8.5,2.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Lolly Cake (NZ classic)',$sc,410,1715,3.5,60.0,40.0,18.5,10.0,1.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Paua (abalone, grilled)',$fs,105,439,20.0,5.0,0.0,1.0,0.2,0.0,1.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Rewena Bread (Maori potato bread)',$bc,255,1067,7.0,48.0,3.0,3.5,0.5,2.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Hokey Pokey Ice Cream',$sc,215,900,3.5,26.0,22.0,11.0,7.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['L&P (Lemon & Paeroa soft drink)',$dr,40,167,0.0,10.0,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'FSANZ / USDA FDC. Seeded v37.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 37 );
	}

	/** Seed v38: Native & common Japanese foods. */
	public static function seed_v38(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 38 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $mp=$cid['meat-poultry']??0; $fs=$cid['fish-seafood']??0;
		$de=$cid['dairy-eggs']??0; $bc=$cid['bread-cereals']??0; $ns=$cid['nuts-seeds']??0;
		$fo=$cid['fats-oils']??0; $dr=$cid['drinks']??0; $lp=$cid['legumes-pulses']??0;
		$co=$cid['condiments']??0; $sc=$cid['snacks-confectionery']??0; $tk=$cid['takeaway']??0;

		$foods = [
			// ══════════════════════════════════════
			// ── RICE DISHES (10) ──
			// ══════════════════════════════════════
			['Onigiri (tuna mayo)',$bc,170,711,6.0,28.0,1.0,3.5,0.5,0.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,1,0,0],
			['Onigiri (umeboshi plum)',$bc,145,607,3.0,30.0,0.5,0.5,0.1,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Onigiri (kelp/kombu)',$bc,150,628,3.5,30.0,1.0,0.5,0.1,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Oyakodon (chicken & egg rice bowl)',$tk,165,690,10.0,20.0,3.0,5.0,1.5,0.5,1.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Gyudon (beef rice bowl)',$tk,170,711,9.0,22.0,4.0,5.5,2.0,0.5,1.2, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Katsudon (pork cutlet rice bowl)',$tk,210,879,11.0,24.0,4.0,8.0,2.0,0.5,1.0, 0,0,0,1,0,1,1,0, 0,0,0,0,0,0],
			['Tendon (tempura rice bowl)',$tk,195,816,6.0,28.0,3.0,6.5,1.0,1.0,1.0, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Chirashi Sushi (scattered)',$tk,155,649,8.5,22.0,3.0,3.5,0.5,0.5,1.0, 1,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Sekihan (red bean rice)',$bc,165,690,5.0,33.0,1.0,0.5,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chazuke (tea over rice)',$bc,110,460,3.5,22.0,0.5,0.5,0.1,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],

			// ── NOODLE DISHES (12) ──
			['Ramen (shoyu/soy sauce)',$tk,85,356,5.5,9.0,0.5,3.0,1.0,0.3,2.0, 1,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Ramen (miso)',$tk,90,377,6.0,9.5,1.0,3.5,1.0,0.5,2.0, 1,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Tsukemen (dipping noodles)',$tk,145,607,7.0,18.0,1.5,4.5,1.5,0.5,1.5, 1,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Yakisoba (fried noodles)',$tk,155,649,5.5,22.0,3.0,5.0,1.0,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Yaki Udon (fried thick noodles)',$tk,145,607,5.0,22.0,2.5,4.0,0.8,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Tempura Udon (hot soup)',$tk,120,502,5.0,17.0,1.5,3.5,0.5,0.5,1.5, 1,1,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Kitsune Udon (fried tofu noodles)',$tk,100,418,4.5,15.0,2.0,2.5,0.4,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Zaru Soba (cold dipping)',$bc,110,460,5.5,22.0,1.0,0.5,0.1,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Somen (cold thin noodles)',$bc,127,531,3.5,26.0,0.5,0.3,0.1,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Hiyashi Chuka (cold ramen salad)',$tk,130,544,6.0,18.0,3.0,3.5,0.5,1.0,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Chanpon (Nagasaki noodles)',$tk,110,460,6.5,12.0,1.5,4.0,1.5,0.5,1.5, 1,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Nabeyaki Udon (hot pot noodles)',$tk,95,397,6.0,12.0,1.0,2.5,0.5,0.5,1.5, 1,1,0,1,0,1,1,0, 0,0,1,0,0,0],

			// ── SUSHI & SASHIMI (10) ──
			['Sushi (tamago/egg nigiri, 1pc)',$tk,52,218,2.5,7.5,1.5,1.0,0.3,0.0,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Sushi (unagi/eel nigiri, 1pc)',$tk,65,272,3.0,7.5,2.0,2.0,0.5,0.0,0.5, 1,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Sushi (ikura/salmon roe gunkan)',$tk,55,230,3.5,7.0,0.5,1.5,0.3,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['California Roll (6 pcs)',$tk,140,586,4.0,26.0,3.0,2.5,0.4,1.0,0.5, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Dragon Roll (6 pcs)',$tk,195,816,6.0,26.0,4.0,7.5,1.0,2.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Temaki (hand roll, salmon)',$tk,85,356,4.5,12.0,1.0,2.0,0.4,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Sashimi (tuna, 5 slices)',$fs,110,460,25.0,0.0,0.0,0.5,0.1,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Sashimi (salmon, 5 slices)',$fs,125,523,20.0,0.0,0.0,5.0,1.0,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Chirashi Don (deluxe sashimi bowl)',$tk,165,690,10.0,22.0,2.5,4.0,0.8,0.5,1.2, 1,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Inari Sushi (tofu pouch, 2 pcs)',$tk,115,481,3.5,20.0,5.0,2.0,0.3,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── GRILLED & FRIED (12) ──
			['Yakitori (tsukune/chicken meatball)',$mp,180,753,15.0,8.0,5.0,9.0,2.5,0.0,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Yakitori (negima/chicken & leek)',$mp,150,628,18.0,4.0,3.0,6.5,1.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Yakitori (tebasaki/chicken wing)',$mp,220,920,17.0,5.0,4.0,14.5,4.0,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Karaage (Japanese fried chicken)',$mp,255,1067,17.0,12.0,1.0,16.0,3.5,0.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Korokke (croquette, potato & meat)',$tk,200,837,5.0,22.0,1.5,10.5,2.5,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Tonkatsu (deep-fried pork cutlet)',$mp,280,1172,16.0,15.0,2.0,18.0,4.5,0.5,0.8, 0,0,0,1,0,1,1,0, 0,0,0,0,0,0],
			['Ebi Fry (breaded fried prawns)',$fs,240,1004,12.0,18.0,0.5,13.5,2.0,0.5,0.8, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Tempura (mixed, 5 pcs)',$tk,210,879,5.0,20.0,1.0,12.5,2.0,1.0,0.5, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kushikatsu (deep-fried skewers)',$tk,270,1130,10.0,20.0,1.5,17.0,3.5,0.5,0.8, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Menchi Katsu (minced meat cutlet)',$mp,280,1172,12.0,16.0,1.0,19.0,5.5,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Tatsuta-age (soy-marinated chicken)',$mp,245,1025,16.0,14.0,2.0,14.5,3.0,0.5,1.2, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Unagi Kabayaki (grilled eel)',$fs,295,1234,18.0,12.0,8.0,18.5,4.0,0.0,1.5, 1,0,0,0,0,0,1,0, 0,0,0,0,0,0],

			// ── HOT POTS & STEWS (8) ──
			['Sukiyaki (beef hot pot)',$tk,150,628,10.0,10.0,5.5,8.0,3.5,0.5,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Shabu-Shabu (sliced beef)',$tk,110,460,12.0,5.0,2.0,5.0,2.0,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Oden (fishcake hotpot, mixed)',$tk,55,230,4.5,5.0,1.5,1.5,0.3,0.5,1.5, 1,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Nikujaga (meat & potato stew)',$tk,100,418,5.5,12.0,4.0,3.5,1.5,1.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Motsu Nabe (offal hot pot)',$tk,85,356,8.0,5.0,2.0,4.0,1.5,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Chanko Nabe (sumo stew)',$tk,75,314,8.0,4.5,1.0,2.5,0.5,1.0,1.0, 1,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Yudofu (simmered tofu)',$lp,65,272,5.5,2.0,0.5,4.0,0.6,0.5,0.8, 1,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Nabe (vegetable hot pot)',$tk,40,167,2.5,5.5,2.0,0.8,0.2,1.5,1.0, 1,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── SIDE DISHES & IZAKAYA (12) ──
			['Edamame (salted, in pod)',$lp,110,460,10.0,8.0,2.0,4.5,0.5,4.5,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Agedashi Tofu (fried in dashi)',$lp,135,565,7.5,8.0,1.5,8.5,1.2,0.5,1.2, 1,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Hiyayakko (cold tofu)',$lp,55,230,5.5,2.0,0.5,3.0,0.5,0.3,0.5, 0,0,0,0,0,0,1,0, 1,0,1,1,1,1],
			['Tamagoyaki (rolled omelette)',$de,155,649,10.5,5.0,3.5,10.0,2.5,0.0,0.8, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Chawanmushi (savoury egg custard)',$de,70,293,5.5,2.5,0.5,4.0,1.0,0.0,0.8, 1,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Kinpira Gobo (braised burdock)',$fv,75,314,1.5,14.0,5.0,1.5,0.2,3.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Ohitashi (blanched spinach)',$fv,25,105,2.5,2.5,0.3,0.5,0.1,1.5,0.5, 1,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Sunomono (vinegar cucumber)',$fv,20,84,0.5,4.5,3.0,0.1,0.0,0.3,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nikumaki (meat-wrapped veg)',$mp,175,732,14.0,6.0,3.5,10.5,4.0,1.0,1.2, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Tsukemono (Japanese pickles, mixed)',$fv,18,75,0.5,3.5,1.5,0.1,0.0,1.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nasu Dengaku (miso-glazed aubergine)',$fv,85,356,2.0,8.5,5.0,4.5,0.5,2.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Renkon Chips (fried lotus root)',$sc,220,920,2.0,28.0,2.0,11.0,1.5,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── CURRY & STEW (6) ──
			['Japanese Curry Rice (pork)',$tk,150,628,5.5,20.0,3.5,5.5,2.0,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Japanese Curry Rice (chicken)',$tk,140,586,6.5,20.0,3.5,4.0,1.0,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Hayashi Rice (hashed beef)',$tk,135,565,5.5,18.0,4.0,4.5,1.5,0.5,0.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Cream Stew (white stew)',$tk,95,397,5.0,8.0,2.0,5.0,2.5,0.5,0.6, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Curry Udon (noodles in curry)',$tk,135,565,5.0,18.0,2.5,4.5,1.5,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Katsu Curry (chicken cutlet)',$tk,205,858,13.0,20.0,4.0,8.5,2.0,1.0,1.0, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],

			// ── JAPANESE BREAKFAST & BENTO (6) ──
			['Tamago Kake Gohan (raw egg on rice)',$bc,175,732,7.0,28.0,0.5,3.5,1.0,0.5,0.8, 0,0,0,1,0,0,1,0, 0,0,1,1,0,1],
			['Natto on Rice',$bc,185,774,9.0,28.0,1.5,3.5,0.5,3.0,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Ochazuke (tea rice, salmon)',$bc,120,502,5.0,22.0,0.5,1.5,0.3,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Makunouchi Bento (boxed lunch, avg)',$tk,150,628,7.0,20.0,3.0,4.5,1.0,1.0,1.0, 1,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Salmon Bento (convenience store)',$tk,160,669,8.0,24.0,2.0,3.5,0.5,0.5,0.8, 1,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Umeboshi (pickled plum, 1 pc)',$fv,10,42,0.2,1.8,0.5,0.1,0.0,0.5,8.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── JAPANESE SWEETS (WAGASHI) (10) ──
			['Mochi (anko/red bean filling)',$sc,235,983,4.5,50.0,22.0,0.5,0.1,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dango (mitarashi, 3 balls)',$sc,195,816,3.0,42.0,15.0,0.5,0.1,0.5,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Taiyaki (red bean filling)',$sc,250,1046,5.0,45.0,18.0,5.0,1.5,2.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Yokan (red bean jelly)',$sc,260,1088,3.5,62.0,40.0,0.2,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Warabi Mochi (bracken starch)',$sc,175,732,0.5,42.0,18.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Monaka (wafer bean paste)',$sc,270,1088,5.0,58.0,35.0,1.0,0.2,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kakigori (shaved ice, strawberry)',$sc,70,293,0.0,18.0,17.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Matcha Parfait',$sc,220,920,4.0,30.0,24.0,9.5,5.5,1.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Castella (sponge cake)',$sc,310,1297,7.0,58.0,35.0,5.5,1.5,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Anpan (red bean bun)',$bc,280,1172,7.0,50.0,22.0,5.0,1.5,3.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── CONDIMENTS & SEASONINGS (8) ──
			['Wasabi (paste)',$co,292,1222,5.0,63.0,12.0,2.0,0.3,7.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ponzu Sauce',$co,45,188,3.5,6.0,3.0,0.1,0.0,0.0,5.0, 1,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Tonkatsu Sauce',$co,130,544,0.5,32.0,25.0,0.1,0.0,0.5,3.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Furikake (rice seasoning, mixed)',$co,310,1297,22.0,35.0,5.0,8.0,1.5,3.0,8.0, 1,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Dashi Stock (kombu & bonito)',$co,5,21,0.5,0.5,0.0,0.0,0.0,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Mirin (sweet rice wine)',$co,240,1004,0.3,45.0,33.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],
			['Japanese Mayonnaise (Kewpie)',$co,680,2845,1.5,3.0,2.5,74.0,6.0,0.0,1.5, 0,0,0,1,0,0,1,0, 1,0,1,1,0,1],
			['Shichimi Togarashi (7-spice)',$co,310,1297,12.0,50.0,8.0,10.0,1.5,22.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── DRINKS (6) ──
			['Matcha (whisked, no sugar)',$dr,3,13,0.3,0.5,0.0,0.0,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Genmaicha (brown rice tea)',$dr,1,4,0.1,0.2,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mugicha (barley tea, cold)',$dr,1,4,0.0,0.2,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Ramune (marble soda)',$dr,48,201,0.0,12.0,12.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Calpis (milk-based drink)',$dr,45,188,0.5,10.5,10.0,0.1,0.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Amazake (sweet rice drink)',$dr,80,335,1.5,18.0,16.0,0.2,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; } // phpcs:ignore
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'source_notes'=>'MEXT Japan / USDA FDC. Seeded v38.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 38 );
	}
}
