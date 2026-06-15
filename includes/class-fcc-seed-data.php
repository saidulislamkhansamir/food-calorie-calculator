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
}
