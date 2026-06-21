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

	/** Seed v39: Native & common Chinese foods. */
	public static function seed_v39(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 39 ) { return; }
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
			// ── DIM SUM & DUMPLINGS (15) ──
			// ══════════════════════════════════════
			['Xiaolongbao (soup dumpling, 1pc)',$tk,55,230,3.0,5.5,0.5,2.5,0.8,0.2,0.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Sheng Jian Bao (pan-fried bun, 1pc)',$tk,75,314,3.5,8.0,0.5,3.5,1.0,0.3,0.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Jiaozi (boiled dumpling, 1pc)',$tk,40,167,2.0,5.0,0.3,1.5,0.4,0.2,0.3, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Guotie (potsticker, 1pc)',$tk,50,209,2.5,5.0,0.3,2.0,0.5,0.2,0.4, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Har Gow (shrimp dumpling, 1pc)',$tk,48,201,3.0,5.5,0.3,1.5,0.3,0.2,0.4, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Siu Mai (pork & prawn, 1pc)',$tk,58,243,3.5,4.5,0.5,2.5,0.8,0.2,0.5, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Cheung Fun (rice noodle roll, shrimp)',$tk,100,418,4.0,14.0,0.5,2.5,0.5,0.3,0.8, 0,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Char Siu Bao (BBQ pork bun, 1pc)',$tk,130,544,5.0,18.0,5.0,4.0,1.0,0.5,0.5, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Lo Mai Gai (sticky rice in lotus leaf)',$tk,175,732,6.0,25.0,1.5,5.5,1.5,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Turnip Cake (lo bak go, pan-fried)',$tk,115,481,2.0,15.0,0.5,5.5,1.0,0.5,0.6, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Phoenix Claws (chicken feet, braised)',$mp,215,900,19.0,0.5,0.0,15.0,4.0,0.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Zongzi (sticky rice dumpling)',$bc,180,753,4.5,30.0,2.0,4.5,1.0,1.0,0.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Baozi (steamed meat bun, 1pc)',$tk,105,439,4.5,15.0,1.5,3.0,1.0,0.5,0.4, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Shumai (open-top dumpling, 1pc)',$tk,52,218,3.0,4.5,0.5,2.5,0.8,0.2,0.5, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Wonton (fried, 1pc)',$tk,55,230,2.0,5.5,0.3,2.5,0.5,0.2,0.3, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],

			// ── NOODLE DISHES (12) ──
			['Chow Mein (chicken)',$tk,160,669,9.0,18.0,2.0,6.0,1.0,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Lo Mein (pork)',$tk,170,711,8.0,22.0,2.5,5.5,1.5,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Dan Dan Noodles (Sichuan)',$tk,185,774,8.0,20.0,2.0,8.5,2.0,1.0,1.5, 0,0,0,0,1,1,1,0, 0,0,1,0,0,0],
			['Zhajiangmian (bean paste noodles)',$tk,165,690,7.5,22.0,3.0,5.0,1.5,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Lanzhou Beef Noodle Soup',$tk,85,356,5.5,10.0,0.5,2.5,0.8,0.5,1.2, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Biang Biang Noodles (hand-pulled)',$tk,170,711,5.5,26.0,1.5,5.0,0.8,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Cold Sesame Noodles',$tk,195,816,6.0,24.0,3.0,8.5,1.5,1.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Liangpi (cold skin noodles)',$tk,140,586,3.5,22.0,1.5,4.5,0.5,1.0,1.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Wonton Noodle Soup (Cantonese)',$tk,75,314,5.0,8.5,0.5,2.0,0.5,0.5,1.0, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Crossing-the-Bridge Noodles (Yunnan)',$tk,80,335,5.5,9.0,0.5,2.5,0.5,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hot Dry Noodles (Wuhan re gan mian)',$tk,185,774,7.0,24.0,1.5,7.0,1.0,1.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Chow Fun (flat rice noodles, beef)',$tk,165,690,8.0,20.0,1.0,6.0,2.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],

			// ── RICE DISHES (10) ──
			['Fried Rice (Yangzhou/egg fried)',$tk,175,732,6.5,24.0,1.5,6.0,1.0,1.0,1.2, 0,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Congee (century egg & pork)',$bc,55,230,3.0,7.0,0.2,1.5,0.5,0.2,0.5, 0,0,0,1,0,0,0,0, 0,0,0,0,0,0],
			['Claypot Rice (lap cheong)',$tk,195,816,8.0,28.0,2.0,5.5,1.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Hainanese Chicken Rice',$tk,155,649,10.0,22.0,0.5,3.5,0.8,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Sticky Rice (with toppings)',$bc,170,711,3.5,35.0,1.0,1.5,0.3,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Steamed Rice (jasmine)',$bc,130,544,2.7,28.0,0.0,0.3,0.1,0.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rice Porridge (plain/jook)',$bc,48,201,1.2,10.5,0.1,0.2,0.0,0.2,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lu Rou Fan (braised pork rice, Taiwan)',$tk,195,816,9.0,22.0,3.0,8.0,3.0,0.5,1.2, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Zha Cai Rou Si Fan (pickled veg pork rice)',$tk,165,690,7.5,24.0,1.0,4.5,1.5,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Fried Rice (Hokkien prawn)',$tk,180,753,8.5,22.0,1.0,7.0,1.5,0.5,1.5, 0,1,0,1,0,0,1,0, 0,0,1,0,0,0],

			// ── STIR-FRY & WOK DISHES (15) ──
			['Kung Pao Chicken (Sichuan)',$tk,170,711,15.0,10.0,4.0,8.5,1.5,1.0,1.5, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Sweet and Sour Pork (guobaorou)',$tk,200,837,10.0,22.0,15.0,8.0,2.0,0.5,0.8, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Mapo Tofu (Sichuan)',$tk,108,452,7.0,4.0,1.0,7.5,1.5,0.5,1.2, 0,0,0,0,0,0,1,0, 0,0,1,0,1,1],
			['Twice-Cooked Pork (Hui Guo Rou)',$mp,220,920,12.0,5.0,3.0,17.0,5.5,1.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Yu Xiang Rou Si (fish-fragrant pork)',$mp,175,732,10.0,10.0,5.0,11.0,3.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Dry-fried Green Beans (Sichuan)',$fv,110,460,3.0,8.0,2.5,7.5,1.0,2.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Stir-fried Pak Choi with Garlic',$fv,45,188,2.0,3.5,1.5,2.5,0.3,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chinese Broccoli (kai lan, oyster sauce)',$fv,55,230,3.0,5.0,1.5,2.5,0.4,2.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,0,0],
			['Tomato Egg Stir-fry (Xi Hong Shi Chao Dan)',$fv,95,397,5.5,6.0,4.0,5.5,1.5,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Black Bean Beef (dou chi niu rou)',$mp,165,690,14.0,5.0,2.0,10.0,3.5,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Cashew Chicken (yao guo ji ding)',$tk,175,732,14.0,10.0,3.0,9.0,1.5,1.0,1.2, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Mongolian Beef',$mp,185,774,12.0,14.0,8.0,9.0,3.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Orange Chicken',$tk,195,816,12.0,18.0,12.0,8.0,1.5,0.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Chilli Beef (Hunan-style)',$mp,175,732,14.0,8.0,3.0,10.0,3.0,1.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Stir-fried Morning Glory (water spinach)',$fv,50,209,2.5,4.0,1.0,2.5,0.3,2.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── SOUPS (8) ──
			['Hot and Sour Soup',$tk,50,209,3.5,5.0,0.5,2.0,0.5,0.5,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Egg Drop Soup (dan hua tang)',$tk,35,146,2.5,2.5,0.5,1.5,0.5,0.0,0.8, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Wonton Soup (Cantonese, clear)',$tk,45,188,3.0,5.0,0.3,1.5,0.4,0.3,0.8, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Winter Melon Soup',$tk,20,84,1.0,3.0,1.0,0.5,0.1,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Corn Soup (Chinese-style)',$tk,55,230,2.0,8.5,2.5,1.5,0.5,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Lotus Root and Pork Rib Soup',$tk,60,251,4.5,5.0,1.0,2.5,0.8,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Herbal Chicken Soup (yao shan)',$tk,55,230,5.5,2.0,0.5,2.5,0.8,0.3,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Seaweed and Egg Soup',$tk,25,105,2.0,2.0,0.3,1.0,0.3,0.3,1.0, 0,0,0,1,0,0,1,0, 0,0,1,1,0,1],

			// ── ROASTED & BRAISED (10) ──
			['Cantonese Roast Duck',$mp,265,1109,16.0,3.0,2.5,21.0,6.0,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Siu Yuk (crispy roast pork belly)',$mp,350,1464,18.0,0.5,0.5,30.5,11.0,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Char Siu (Cantonese BBQ pork)',$mp,230,962,20.0,15.0,12.0,10.0,3.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['White Cut Chicken (bai qie ji)',$mp,175,732,20.0,0.0,0.0,10.5,3.0,0.0,0.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Drunken Chicken (zui ji)',$mp,145,607,18.0,2.0,0.5,6.5,1.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Red-braised Pork Belly (hong shao rou)',$mp,310,1297,12.0,8.0,6.0,26.0,10.0,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Dongpo Pork (dong po rou)',$mp,320,1339,10.0,8.0,6.5,28.0,11.0,0.0,1.2, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Soy Sauce Chicken (see yau gai)',$mp,180,753,18.0,5.0,3.5,10.0,2.5,0.0,2.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Tea Egg (cha ye dan, 1 egg)',$de,75,314,6.0,0.5,0.5,5.0,1.5,0.0,0.8, 0,0,0,1,0,0,1,0, 0,0,1,1,0,0],
			['Braised Beef Shank (lu niu rou)',$mp,175,732,24.0,3.0,1.5,7.0,2.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],

			// ── STREET FOOD & SNACKS (10) ──
			['Jianbing (Chinese crepe)',$tk,195,816,6.0,24.0,2.0,8.5,1.5,1.0,0.8, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Rou Jia Mo (Chinese burger)',$tk,250,1046,11.0,28.0,2.0,10.5,3.5,0.5,0.8, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Cong You Bing (scallion pancake)',$bc,280,1172,5.0,32.0,1.0,15.0,2.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Dan Bing (egg crepe, Taiwanese)',$tk,185,774,7.5,20.0,1.0,8.5,2.0,0.5,0.6, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['You Tiao (fried dough stick)',$bc,390,1632,7.5,42.0,1.0,22.0,3.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Mantou (steamed bun, plain)',$bc,235,983,7.0,46.0,2.0,1.5,0.3,1.5,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Stinky Tofu (chou doufu, fried)',$lp,195,816,10.0,12.0,0.5,12.5,2.0,1.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Tanghulu (candied fruit skewer)',$sc,275,1151,0.5,68.0,60.0,0.2,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Egg Waffle (gai dan jai, Hong Kong)',$sc,295,1234,6.0,40.0,18.0,12.5,3.5,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Turnip Cake (steamed, Cantonese)',$fv,105,439,2.0,14.0,0.5,5.0,1.0,0.5,0.6, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── CHINESE DESSERTS & SWEETS (10) ──
			['Tangyuan (glutinous rice balls, 3 pcs)',$sc,185,774,2.5,35.0,18.0,4.0,1.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mooncake (lotus seed, 1/4)',$sc,420,1757,7.0,58.0,38.0,18.5,5.0,2.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Egg Tart (dan tat, 1pc)',$sc,245,1025,4.0,28.0,15.0,13.0,6.5,0.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Mango Pudding (Cantonese)',$sc,115,481,1.5,20.0,16.0,3.5,2.0,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Red Bean Soup (sweet, tong sui)',$sc,100,418,3.0,20.0,12.0,0.3,0.1,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Douhua (soft tofu pudding)',$sc,60,251,3.0,8.0,5.5,1.5,0.2,0.0,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Sesame Balls (jian dui)',$sc,320,1339,4.0,42.0,18.0,15.0,2.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nian Gao (Chinese new year cake)',$sc,230,962,2.0,55.0,22.0,0.5,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Fa Gao (prosperity cake)',$sc,260,1088,3.5,48.0,22.0,6.0,1.0,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Wife Cake (lao po bing)',$sc,365,1527,4.5,50.0,22.0,16.5,5.5,1.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── CONDIMENTS & SAUCES (8) ──
			['Hoisin Sauce',$co,220,920,3.5,42.0,28.0,4.0,0.5,1.5,4.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Oyster Sauce',$co,75,314,1.5,16.0,6.0,0.2,0.0,0.0,5.5, 0,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Black Bean Sauce (dou chi)',$co,110,460,8.0,12.0,2.0,3.0,0.5,2.0,6.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Chilli Oil (la you)',$co,820,3431,1.0,5.0,1.0,88.0,8.0,2.0,1.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Chinkiang Vinegar (black vinegar)',$co,15,63,0.0,1.5,0.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['XO Sauce',$co,350,1464,15.0,12.0,5.0,28.0,5.0,0.5,5.0, 1,1,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Sichuan Peppercorn (ground)',$co,300,1255,10.0,55.0,3.0,5.0,0.5,20.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Doubanjiang (chilli bean paste)',$co,60,251,3.5,7.0,1.5,2.0,0.3,2.0,8.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],

			// ── CHINESE DRINKS (6) ──
			['Chrysanthemum Tea',$dr,15,63,0.0,3.5,3.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Soy Milk (sweetened, Chinese)',$dr,55,230,3.0,7.0,5.0,1.8,0.3,0.5,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Bubble Tea (milk tea, tapioca)',$dr,90,377,1.0,18.0,12.0,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pu-erh Tea (brewed)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Oolong Tea (brewed)',$dr,1,4,0.1,0.2,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Lemon Tea (Hong Kong, iced)',$dr,50,209,0.1,12.5,12.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── REGIONAL SPECIALITIES (6) ──
			['Sichuan Hot Pot (broth per 100ml)',$tk,40,167,2.0,2.0,0.5,2.5,0.8,0.0,2.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Peking Duck (with pancake, 1 serving)',$tk,270,1130,14.0,20.0,5.0,14.5,3.5,0.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Ma La Xiang Guo (spicy stir-fry pot)',$tk,145,607,8.0,5.0,1.5,10.5,2.5,1.5,2.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Beggar\'s Chicken (whole, per 100g)',$mp,185,774,20.0,2.0,0.5,11.0,3.0,0.0,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Xiao Long Xia (crayfish, Sichuan spiced)',$fs,85,356,15.0,2.0,0.5,2.0,0.4,0.0,2.0, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Century Egg (pi dan, 1 egg)',$de,85,356,6.5,0.5,0.0,6.5,2.0,0.0,1.5, 0,0,0,1,0,0,0,0, 0,0,0,0,0,0],
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
				'source_notes'=>'China CDC / USDA FDC. Seeded v39.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 39 );
	}

	/** Seed v40: Native & common Korean foods. */
	public static function seed_v40(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 40 ) { return; }
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
			// ── RICE DISHES (BAP) (10) ──
			// ══════════════════════════════════════
			['Bibimbap (dolsot, stone pot)',$tk,160,669,8.5,22.0,2.5,4.5,1.0,2.0,0.8, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Bokkeumbap (kimchi fried rice)',$tk,175,732,6.0,24.0,2.0,6.0,1.5,1.0,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Gimbap (Korean rice roll, 1 roll)',$tk,145,607,5.5,24.0,2.0,3.0,0.5,1.0,0.8, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Juk (Korean rice porridge, plain)',$bc,50,209,1.0,11.0,0.1,0.2,0.0,0.2,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hobakjuk (pumpkin porridge)',$bc,65,272,1.5,14.0,3.0,0.5,0.1,1.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Patjuk (red bean porridge)',$bc,85,356,3.0,17.0,4.0,0.3,0.1,2.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cupbap (rice bowl with toppings)',$tk,155,649,7.0,22.0,2.0,4.0,1.0,1.5,1.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Omurice (Korean omelette rice)',$tk,180,753,7.5,22.0,3.0,7.0,2.0,0.5,0.8, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Kongnamul Bap (bean sprout rice)',$bc,140,586,5.0,24.0,0.5,2.0,0.3,1.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Dolsot Bap (stone pot mixed rice)',$bc,150,628,4.5,28.0,0.5,2.0,0.3,1.5,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── STEWS & SOUPS (JJIGAE & GUK) (15) ──
			['Doenjang Jjigae (soybean paste stew)',$tk,55,230,4.0,4.5,1.0,2.5,0.5,1.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,1,1],
			['Sundubu Jjigae (soft tofu stew, seafood)',$tk,70,293,6.0,3.5,1.0,3.5,0.8,0.5,1.2, 1,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Budae Jjigae (army stew)',$tk,95,397,6.5,7.0,2.0,5.0,1.5,1.0,2.0, 0,0,1,0,0,1,1,0, 0,0,0,0,0,0],
			['Gamjatang (pork bone soup)',$tk,80,335,6.5,4.5,0.5,4.5,1.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Samgyetang (ginseng chicken soup)',$tk,110,460,10.0,8.0,0.5,4.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Seolleongtang (ox bone soup)',$tk,65,272,5.5,2.0,0.0,4.0,1.8,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Galbitang (short rib soup)',$tk,85,356,7.0,3.0,0.5,5.0,2.0,0.3,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Yukgaejang (spicy beef soup)',$tk,70,293,6.5,3.5,1.0,3.5,1.2,1.0,1.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Miyeok Guk (seaweed birthday soup)',$tk,30,126,2.5,1.5,0.0,1.5,0.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Tteokguk (rice cake soup, New Year)',$tk,90,377,4.5,14.0,0.5,1.5,0.5,0.3,1.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Manduguk (dumpling soup)',$tk,75,314,4.5,8.0,0.5,2.5,0.8,0.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Gopchang Jeongol (tripe hot pot)',$tk,95,397,8.0,4.0,1.0,5.5,2.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Kkotge Tang (blue crab soup)',$tk,45,188,5.5,2.5,0.5,1.5,0.3,0.3,1.2, 0,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Cheonggukjang Jjigae (fast-fermented bean stew)',$tk,60,251,5.0,4.0,1.0,3.0,0.5,1.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Kongnamul Guk (bean sprout soup)',$tk,20,84,1.5,2.0,0.3,0.5,0.1,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── GRILLED & BBQ (GUI) (10) ──
			['Bulgogi (marinated beef, BBQ)',$mp,160,669,18.0,8.0,6.0,6.0,2.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Galbi (beef short ribs, BBQ)',$mp,235,983,17.0,8.0,6.5,15.0,6.0,0.0,1.2, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Samgyeopsal (grilled pork belly)',$mp,330,1381,15.0,0.0,0.0,30.0,11.0,0.0,0.5, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Dwaeji Bulgogi (spicy pork)',$mp,185,774,16.0,8.0,5.5,10.0,3.5,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Dak Galbi (spicy chicken stir-fry)',$tk,145,607,14.0,10.0,5.0,5.5,1.0,1.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Dakgangjeong (crispy sweet chicken)',$mp,250,1046,14.0,22.0,14.0,12.0,2.5,0.5,1.0, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Jeyuk Bokkeum (spicy pork stir-fry)',$mp,180,753,15.0,8.0,5.0,10.0,3.5,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Galbi Jjim (braised short ribs)',$mp,195,816,16.0,10.0,6.0,10.0,4.0,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Bossam (boiled pork belly wraps)',$mp,220,920,18.0,4.0,2.0,15.0,5.5,0.0,1.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Yangnyeom Chicken (sweet & spicy fried)',$mp,255,1067,15.0,18.0,12.0,14.0,3.0,0.5,1.2, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],

			// ── NOODLE DISHES (8) ──
			['Japchae (sweet potato glass noodles)',$tk,140,586,3.5,22.0,6.0,4.5,0.8,1.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Jajangmyeon (black bean noodles)',$tk,165,690,6.0,26.0,4.0,4.5,1.0,1.0,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Naengmyeon (cold buckwheat noodles)',$tk,115,481,5.0,22.0,2.0,1.0,0.2,1.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Bibim Naengmyeon (spicy cold noodles)',$tk,130,544,5.0,22.0,4.0,2.0,0.3,1.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Kalguksu (knife-cut noodle soup)',$tk,85,356,4.0,14.0,0.5,1.5,0.3,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ramyeon (Korean instant noodles)',$tk,145,607,4.0,20.0,2.0,5.5,2.5,0.5,2.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Jjamppong (spicy seafood noodle soup)',$tk,95,397,6.5,10.0,1.5,3.0,0.8,0.5,1.5, 1,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Sujebi (hand-torn noodle soup)',$tk,70,293,3.0,12.0,0.5,1.0,0.2,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],

			// ── PANCAKES & FRIED (JEON) (8) ──
			['Pajeon (scallion pancake)',$tk,200,837,5.0,24.0,2.5,9.5,1.5,1.5,1.0, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Haemul Pajeon (seafood pancake)',$tk,195,816,8.0,20.0,2.0,9.0,1.5,1.0,1.0, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kimchi Jeon (kimchi pancake)',$tk,185,774,4.5,22.0,2.5,9.0,1.5,1.5,1.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Bindaetteok (mung bean pancake)',$lp,210,879,8.0,18.0,1.0,12.0,2.0,2.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gamja Jeon (potato pancake)',$fv,165,690,2.5,22.0,1.0,7.5,1.0,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Twigim (Korean tempura, mixed)',$tk,235,983,5.0,24.0,1.5,13.5,2.0,1.5,0.5, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Hotteok (sweet filled pancake)',$sc,275,1151,4.5,38.0,18.0,12.0,2.5,1.0,0.3, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],
			['Buchimgae (Korean savoury pancake, mixed)',$tk,190,795,5.5,22.0,2.0,9.0,1.5,1.5,0.8, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],

			// ── SIDE DISHES (BANCHAN) (12) ──
			['Kimchi (napa cabbage, tongbaechu)',$fv,25,105,1.5,3.0,1.5,0.5,0.1,1.5,2.5, 1,1,0,0,0,0,0,0, 0,0,1,0,1,1],
			['Kkakdugi (cubed radish kimchi)',$fv,22,92,1.0,3.5,2.0,0.3,0.0,1.5,2.5, 1,1,0,0,0,0,0,0, 0,0,1,0,1,1],
			['Kongnamul Muchim (seasoned bean sprouts)',$fv,35,146,2.5,3.5,0.5,1.5,0.2,1.0,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Sigeumchi Namul (spinach side)',$fv,35,146,2.5,2.5,0.3,2.0,0.3,1.5,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Musaengchae (spicy radish salad)',$fv,30,126,0.5,6.0,3.5,0.5,0.1,1.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Japchae (glass noodle banchan)',$fv,135,565,3.0,20.0,5.5,4.5,0.8,1.0,0.8, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Gyeran Mari (rolled egg omelette)',$de,155,649,10.0,3.0,1.5,11.0,3.0,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Eomuk Bokkeum (stir-fried fish cake)',$fs,115,481,8.0,12.0,4.0,3.5,0.8,0.5,1.5, 1,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Gamja Jorim (braised potatoes)',$fv,95,397,1.5,16.0,4.0,2.5,0.3,1.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Myeolchi Bokkeum (stir-fried anchovies)',$fs,210,879,18.0,10.0,5.0,11.0,2.0,1.0,2.0, 1,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Pickled Radish (danmuji)',$fv,18,75,0.3,4.0,3.5,0.1,0.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Perilla Leaves (pickled, kkaennip)',$fv,40,167,2.0,4.0,0.5,1.5,0.2,2.0,2.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── STREET FOOD & SNACKS (10) ──
			['Tteokbokki (spicy rice cakes)',$tk,195,816,4.0,40.0,8.0,2.0,0.3,1.0,1.5, 1,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Odeng / Eomuk (fish cake on stick)',$fs,90,377,7.0,10.0,2.0,2.0,0.5,0.5,1.5, 1,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Sundae (Korean blood sausage)',$mp,220,920,10.0,22.0,0.5,10.5,3.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Bungeoppang (fish-shaped pastry)',$sc,260,1088,5.0,42.0,18.0,7.5,2.0,2.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Gyeranppang (egg bread)',$bc,245,1025,9.0,30.0,5.0,10.0,3.0,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Corn Dog (Korean, mozzarella)',$sc,310,1297,9.0,32.0,5.0,16.0,6.0,1.0,1.0, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Tornado Potato (spiral on stick)',$sc,280,1172,3.0,35.0,0.5,14.5,2.0,2.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dakgochi (chicken skewer, street)',$mp,180,753,16.0,6.0,4.0,10.0,2.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Mandu (Korean dumpling, fried, 1pc)',$tk,55,230,2.5,5.5,0.3,2.5,0.5,0.3,0.4, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Hoddeok (brown sugar pancake)',$sc,280,1151,4.0,40.0,20.0,12.0,2.5,1.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],

			// ── KOREAN CONDIMENTS & SAUCES (6) ──
			['Gochujang (red chilli paste)',$co,175,732,4.5,35.0,15.0,2.0,0.3,3.0,5.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],
			['Doenjang (soybean paste)',$co,125,523,12.0,13.0,3.0,4.0,0.5,4.0,5.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Ssamjang (wrap sauce)',$co,145,607,6.0,18.0,6.0,5.5,0.8,2.5,4.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Gochugaru (Korean chilli flakes)',$co,310,1297,12.0,55.0,10.0,10.0,1.5,25.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sesame Oil (chamgireum)',$fo,884,3699,0.0,0.0,0.0,100.0,14.2,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Perilla Oil (deulgireum)',$fo,884,3699,0.0,0.0,0.0,100.0,8.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── KOREAN DESSERTS & DRINKS (11) ──
			['Bingsu (patbingsu, red bean shaved ice)',$sc,155,649,3.5,30.0,20.0,2.5,1.5,2.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Songpyeon (rice cake, Chuseok)',$sc,195,816,3.5,40.0,10.0,2.0,0.3,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Yakgwa (honey cookie)',$sc,400,1674,4.0,55.0,30.0,18.0,3.0,1.0,0.1, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Dasik (pressed tea cookie)',$sc,350,1464,5.0,60.0,25.0,10.0,1.5,2.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Yuja Cha (citron tea)',$dr,60,251,0.2,15.0,14.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sikhye (sweet rice drink)',$dr,50,209,0.5,12.0,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sujeonggwa (cinnamon persimmon punch)',$dr,45,188,0.1,11.0,10.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Banana Milk (Korean, Binggrae)',$dr,75,314,1.5,14.0,13.5,1.5,0.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Makgeolli (rice wine)',$dr,55,230,0.5,5.0,3.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Soju (per 100ml)',$dr,125,523,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Omija Tea (five-flavour berry)',$dr,15,63,0.2,3.5,3.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
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
				'source_notes'=>'Rural Dev. Admin. Korea / USDA FDC. Seeded v40.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 40 );
	}

	/** Seed v41: Native & common Russian foods. */
	public static function seed_v41(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 41 ) { return; }
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
			// ── SOUPS (10) ──
			// ══════════════════════════════════════
			['Borscht (Russian, with smetana)',$tk,45,188,2.0,6.5,3.5,1.5,0.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Shchi (cabbage soup)',$tk,35,146,2.0,4.0,1.5,1.0,0.4,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Solyanka (meat & pickle soup)',$tk,55,230,4.5,3.0,1.0,3.0,1.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Ukha (fish soup)',$tk,40,167,4.5,2.5,0.5,1.5,0.3,0.3,0.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Rassolnik (pickle soup)',$tk,40,167,2.5,5.0,1.0,1.0,0.3,1.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Okroshka (cold kvas soup)',$tk,50,209,2.5,6.0,2.0,1.5,0.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Svekolnik (cold beetroot soup)',$tk,35,146,1.5,5.5,3.5,0.5,0.2,1.0,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Lapsha (chicken noodle soup)',$tk,50,209,3.5,6.0,0.5,1.5,0.4,0.3,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kharcho (Georgian-Russian spiced soup)',$tk,65,272,5.0,4.5,1.0,3.0,1.0,0.5,0.6, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Mushroom Soup (Russian, with barley)',$tk,45,188,1.5,7.0,0.5,1.5,0.3,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── DUMPLINGS & PASTRIES (12) ──
			['Pelmeni (Siberian, pork & beef)',$tk,215,900,9.5,24.0,0.5,9.0,3.5,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Vareniki (potato & onion)',$tk,180,753,4.5,28.0,1.0,5.5,2.0,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Vareniki (cherry, sweet)',$sc,200,837,4.0,35.0,12.0,4.5,1.5,1.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Vareniki (tvorog/curd cheese)',$tk,195,816,7.5,26.0,3.0,6.5,3.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pirozhki (cabbage filling)',$tk,245,1025,5.5,28.0,2.0,12.5,3.5,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Pirozhki (meat filling, baked)',$tk,260,1088,9.0,26.0,1.5,13.5,4.5,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Chebureki (deep-fried meat turnover)',$tk,310,1297,9.5,26.0,1.0,19.0,6.0,1.0,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Belyashi (fried meat bun)',$tk,280,1172,9.0,26.0,1.0,16.0,5.0,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kulebyaka (salmon pie)',$tk,240,1004,10.0,22.0,1.5,12.5,5.0,1.0,0.6, 1,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Rasstegai (open fish pie)',$tk,225,941,9.5,22.0,1.0,11.5,4.0,0.5,0.6, 1,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Kurnik (layered chicken pie)',$tk,260,1088,10.0,24.0,1.5,14.0,5.5,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Samsa (Central Asian pastry, lamb)',$tk,295,1234,9.0,24.0,1.0,18.5,7.5,1.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],

			// ── MAIN DISHES (12) ──
			['Beef Stroganoff (classic)',$tk,150,628,11.0,5.0,1.5,10.0,5.0,0.3,0.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Chicken Kiev (kotleta po-kievski)',$mp,265,1109,15.0,12.0,0.5,18.0,8.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Kotlety (Russian meat patties)',$mp,210,879,14.0,8.0,0.5,14.0,5.5,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Golubtsy (stuffed cabbage rolls)',$tk,105,439,6.0,10.0,2.0,4.5,1.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Plov (Russian pilaf, Uzbek-style)',$tk,175,732,7.5,22.0,1.5,6.5,2.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Zrazy (stuffed meat rolls)',$mp,195,816,14.0,8.0,1.0,12.0,4.5,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Bitochki (breaded meat cutlets)',$mp,230,962,13.0,12.0,0.5,15.0,4.5,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Shashlik (marinated grilled meat)',$mp,200,837,20.0,2.0,1.0,12.5,5.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Zharkoye (Russian pot roast)',$tk,110,460,8.5,7.0,1.0,5.5,2.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pelmeni v Bulyione (dumplings in broth)',$tk,95,397,5.5,8.5,0.3,4.5,1.5,0.3,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Zapekanka (tvorog bake)',$sc,190,795,9.0,22.0,10.0,7.5,3.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Draniki (potato pancakes, Belarusian)',$fv,180,753,3.0,22.0,1.0,9.0,1.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],

			// ── SALADS & COLD DISHES (10) ──
			['Olivier Salad (Russian salad)',$fv,130,544,4.5,8.0,2.0,9.0,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Vinegret (beetroot vinaigrette)',$fv,75,314,1.5,12.0,4.0,2.5,0.3,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shuba (herring under fur coat)',$fv,145,607,5.0,10.0,3.0,9.5,1.5,1.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Holodets (meat jelly/aspic)',$mp,85,356,10.0,0.5,0.0,5.0,2.0,0.0,0.8, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Ikra (aubergine caviar spread)',$fv,75,314,1.0,5.5,3.5,5.5,0.7,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Forshmak (herring pâté)',$fs,180,753,10.0,5.0,2.0,13.5,3.5,0.5,2.0, 1,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Mimosa Salad (layered fish salad)',$fv,155,649,6.0,6.0,1.5,12.0,2.0,0.5,0.5, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Selyodka pod Shuboy (herring coat)',$fv,145,607,5.0,10.0,3.0,9.5,1.5,1.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Krasnaya Ikra (red caviar, per 100g)',$fs,245,1025,32.0,0.5,0.0,13.0,2.5,0.0,4.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Salo (cured pork fat)',$mp,770,3222,1.5,0.0,0.0,85.0,33.0,0.0,4.0, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0],

			// ── BREADS & BAKERY (8) ──
			['Borodinsky Bread (rye, dark)',$bc,205,858,6.5,40.0,5.0,1.0,0.2,6.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kalach (white bread ring)',$bc,285,1193,8.0,52.0,3.0,4.5,1.0,2.0,0.8, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Karavai (celebration bread)',$bc,295,1234,7.5,50.0,8.0,6.5,2.5,1.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Lavash (thin flatbread)',$bc,275,1151,8.5,52.0,1.5,3.0,0.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pryanik (Russian gingerbread)',$sc,350,1464,4.5,72.0,35.0,4.5,1.0,1.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Sushki (dried bread rings)',$sc,360,1506,10.0,70.0,8.0,3.5,0.8,3.0,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Bubliki (boiled bread ring)',$bc,310,1297,9.0,58.0,5.0,4.0,1.0,2.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Lepyoshka (Central Asian flatbread)',$bc,290,1213,8.0,52.0,2.0,5.5,1.0,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── PANCAKES & CREPES (6) ──
			['Blini (Russian, buckwheat)',$bc,220,920,6.5,30.0,3.0,8.5,3.0,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Blini with Smetana & Red Caviar',$tk,255,1067,10.0,25.0,2.0,13.0,5.0,0.5,1.5, 1,0,1,1,0,0,0,0, 0,0,0,0,0,0],
			['Oladyi (thick pancakes)',$sc,240,1004,5.0,32.0,6.0,10.0,3.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Syrniki (curd cheese fritters)',$sc,220,920,10.0,22.0,6.0,10.5,4.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Nalesniki (filled crepes, sweet)',$sc,210,879,6.0,28.0,10.0,8.0,3.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Blini with Mushrooms & Smetana',$tk,195,816,5.0,24.0,2.0,9.0,3.5,1.0,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],

			// ── DESSERTS & SWEETS (10) ──
			['Medovik (honey cake)',$sc,360,1506,5.0,50.0,32.0,16.0,8.0,0.5,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Napoleon Cake (mille-feuille)',$sc,350,1464,5.0,38.0,20.0,20.0,12.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ptichye Moloko (bird\'s milk cake)',$sc,320,1339,5.5,42.0,35.0,15.0,9.0,0.0,0.2, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Praga Cake (Prague chocolate cake)',$sc,380,1590,5.0,45.0,30.0,20.5,12.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pastila (fruit marshmallow)',$sc,310,1297,0.5,80.0,70.0,0.2,0.0,1.0,0.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Zefir (Russian marshmallow)',$sc,326,1364,1.0,79.5,72.0,0.1,0.0,1.0,0.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Vatrushka (curd pastry)',$sc,270,1130,8.0,34.0,10.0,11.0,5.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ponchiki (Russian doughnuts)',$sc,340,1423,5.0,42.0,15.0,16.5,4.0,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Halva (sunflower seed, Russian)',$sc,505,2113,12.0,50.0,38.0,29.0,5.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kartoshka (chocolate potato cake)',$sc,380,1590,5.0,48.0,32.0,19.0,10.0,1.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ── DAIRY & FERMENTED (6) ──
			['Smetana (sour cream, 20%)',$de,206,862,2.5,3.5,3.5,20.0,12.5,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Tvorog (Russian curd cheese)',$de,155,649,18.0,3.0,3.0,8.0,5.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kefir (Russian, 3.2%)',$de,59,247,3.0,4.0,4.0,3.2,2.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Ryazhenka (baked fermented milk)',$de,67,280,3.0,4.0,4.0,4.0,2.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Prostokvasha (clotted sour milk)',$de,55,230,3.0,4.5,4.5,2.5,1.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Sgushyonka (condensed milk)',$de,320,1339,7.0,56.0,56.0,8.5,5.5,0.0,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── DRINKS (6) ──
			['Kvas (bread-fermented drink)',$dr,27,113,0.2,6.5,5.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kompot (stewed fruit drink)',$dr,40,167,0.1,10.0,9.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mors (berry drink, cranberry)',$dr,30,126,0.1,7.5,6.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kissel (berry, thick)',$dr,68,285,0.3,17.0,12.0,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sbiten (honey spiced drink)',$dr,55,230,0.2,14.0,13.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Samovar Tea (Russian black tea)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
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
				'source_notes'=>'Russian nutrition tables / USDA FDC. Seeded v41.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 41 );
	}

	/** Seed v42: Native & common Canadian foods. */
	public static function seed_v42(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 42 ) { return; }
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
			// ── QUÉBÉCOIS & FRENCH-CANADIAN (15) ──
			// ══════════════════════════════════════
			['Pâté Chinois (shepherd\'s pie, Québec)',$tk,115,481,6.5,11.0,2.0,5.0,2.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pouding Chômeur (poor man\'s pudding)',$sc,310,1297,3.0,48.0,38.0,12.0,7.0,0.3,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tarte au Sucre (sugar pie)',$sc,380,1590,3.5,55.0,42.0,16.5,9.5,0.3,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Cipaille (layered meat pie)',$tk,210,879,10.0,16.0,1.0,12.0,4.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Cretons (pork spread)',$mp,250,1046,12.0,3.0,0.5,21.0,8.5,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Ploye (Acadian buckwheat pancake)',$bc,165,690,4.5,28.0,1.0,3.5,1.0,2.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Fèves au Lard (Québec baked beans)',$lp,125,523,5.5,18.0,8.0,3.0,1.0,4.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Oreilles de Crisse (fried pork rinds)',$sc,550,2301,50.0,0.0,0.0,38.0,14.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Tire d\'Érable (maple taffy on snow)',$sc,355,1485,0.0,90.0,80.0,0.5,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Maple Butter (beurre d\'érable)',$co,310,1297,0.0,76.0,72.0,0.5,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Galvaude (fries, chicken, peas, gravy)',$tk,195,816,9.0,20.0,1.5,9.0,2.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Poutine Italienne (with meat sauce)',$tk,195,816,9.0,22.0,2.5,8.5,3.5,1.5,1.0, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Montreal Bagel (sesame)',$bc,290,1213,9.5,52.0,5.0,4.0,0.5,2.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Tarte aux Bleuets (blueberry pie)',$sc,280,1172,2.5,42.0,22.0,12.0,5.0,2.0,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Guédilles (lobster roll, Québec)',$tk,230,962,14.0,22.0,2.0,10.0,2.0,0.5,1.0, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── ATLANTIC CANADA (10) ──
			['Rappie Pie (râpure, Acadian)',$tk,135,565,5.0,18.0,0.5,5.0,1.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Donair (Halifax)',$tk,245,1025,12.0,24.0,5.0,11.5,4.5,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Donair Sauce (sweet garlic)',$co,295,1234,2.0,35.0,32.0,17.0,3.0,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Garlic Fingers (with donair sauce)',$tk,285,1193,8.0,30.0,4.0,14.5,5.5,1.0,1.2, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Toutons (fried dough, Newfoundland)',$bc,320,1339,5.5,38.0,2.0,16.5,5.0,1.0,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Jiggs Dinner (Newfoundland boiled dinner)',$tk,95,397,6.5,10.0,2.0,3.0,1.0,2.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Hodge Podge (NS vegetable stew)',$fv,65,272,2.0,8.5,3.0,2.5,1.5,2.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Solomon Gundy (pickled herring, NS)',$fs,170,711,14.0,4.0,3.0,11.0,2.5,0.0,3.0, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Lobster Roll (Atlantic)',$tk,240,1004,15.0,22.0,2.0,10.5,2.5,0.5,1.0, 0,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Fish Cakes (Atlantic, pan-fried)',$fs,195,816,10.0,16.0,0.5,10.0,2.0,1.0,0.8, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── WESTERN & PRAIRIE CANADA (8) ──
			['Calgary Ginger Beef',$tk,210,879,10.0,20.0,12.0,10.0,2.5,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Saskatoon Berry Pie',$sc,270,1130,2.5,40.0,20.0,11.5,5.0,2.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Perogies (Prairie-style, fried)',$tk,215,900,5.0,28.0,1.0,9.5,3.5,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Flapper Pie (Alberta custard & meringue)',$sc,260,1088,4.5,38.0,24.0,10.0,5.0,0.3,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Gopher Hole Doughnuts (style)',$sc,380,1590,4.5,48.0,22.0,19.0,5.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bison Burger (Western Canadian)',$tk,210,879,19.0,20.0,2.0,6.0,2.5,1.5,0.7, 0,0,0,0,0,1,0,0, 0,1,1,0,0,0],
			['Bannock (fried, with jam)',$bc,340,1423,5.5,45.0,10.0,15.5,3.0,1.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Wild Salmon (BC, grilled)',$fs,195,816,22.0,0.0,0.0,11.5,2.0,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ── CANADIAN SNACKS & CONFECTIONERY (10) ──
			['Ketchup Chips (per 100g)',$sc,510,2134,5.5,55.0,5.0,30.0,3.0,4.0,1.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['All-Dressed Chips (per 100g)',$sc,520,2176,6.0,53.0,4.0,32.0,3.5,4.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coffee Crisp (chocolate bar)',$sc,500,2092,5.0,62.0,42.0,25.0,14.0,1.5,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Caramilk (chocolate bar)',$sc,520,2176,5.5,60.0,50.0,29.0,17.0,0.0,0.3, 0,0,1,0,0,0,1,0, 0,0,1,1,0,1],
			['Jos Louis (snack cake)',$sc,395,1653,3.5,55.0,35.0,18.5,10.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['May West (snack cake)',$sc,405,1694,3.5,52.0,32.0,20.5,12.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Eat-More (chocolate bar)',$sc,410,1715,6.0,58.0,38.0,18.0,8.0,3.0,0.3, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Smarties (Canadian, Nestlé)',$sc,480,2008,4.5,65.0,58.0,21.5,13.0,0.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Hickory Sticks (snack)',$sc,510,2134,6.0,56.0,2.0,29.5,4.0,3.5,2.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hawkins Cheezies',$sc,530,2217,8.0,48.0,2.0,34.0,7.0,1.0,2.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── CANADIAN BREAKFAST & BRUNCH (5) ──
			['Peameal Bacon (grilled)',$mp,140,586,22.0,2.0,0.5,5.0,1.5,0.5,2.5, 0,0,0,0,0,0,0,0, 0,1,0,0,0,0],
			['Peameal Bacon Sandwich (Toronto)',$tk,255,1067,16.0,28.0,3.0,8.0,2.5,1.5,2.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Eggs Benedict (Canadian back bacon)',$tk,260,1088,13.0,18.0,1.5,16.0,6.5,0.5,1.2, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Pancakes with Maple Syrup (Canadian)',$sc,265,1109,5.0,45.0,25.0,7.0,2.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Maple Baked Beans',$lp,135,565,5.5,22.0,12.0,1.5,0.3,4.0,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],

			// ── CANADIAN DRINKS (7) ──
			['Caesar (cocktail, Canadian Bloody Mary)',$dr,85,356,0.5,10.0,6.0,0.2,0.0,0.5,2.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tim Hortons Double-Double (coffee)',$dr,55,230,1.5,7.0,7.0,2.5,1.5,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Canadian Club Ginger Ale',$dr,35,146,0.0,8.5,8.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Spruce Beer (traditional)',$dr,35,146,0.0,8.0,7.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Iced Capp (Tim Hortons, medium)',$dr,125,523,2.0,22.0,20.0,3.5,2.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['London Fog (Earl Grey latte)',$dr,50,209,2.0,8.0,7.5,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Labatt Blue (beer, per 100ml)',$dr,43,180,0.3,3.5,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,0,0,1,1],
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
				'source_notes'=>'Health Canada CNF / USDA FDC. Seeded v42.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 42 );
	}

	/** Seed v43: Native & common French + Spanish foods. */
	public static function seed_v43(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 43 ) { return; }
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
			// ── FRENCH — MAINS & CLASSICS (15) ──
			// ══════════════════════════════════════
			['Blanquette de Veau (veal stew)',$tk,120,502,10.0,5.0,1.0,7.0,3.5,0.5,0.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Pot-au-Feu (French boiled dinner)',$tk,80,335,7.0,5.0,1.0,3.5,1.5,1.0,0.5, 0,0,0,0,0,0,0,1, 0,0,0,0,0,0],
			['Steak Frites (with fries)',$tk,250,1046,18.0,20.0,0.5,12.0,4.5,2.0,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Steak Tartare (raw beef)',$mp,175,732,18.0,1.5,0.5,11.0,4.0,0.0,0.8, 0,0,0,1,0,0,0,0, 0,1,0,0,0,0],
			['Confit de Canard (duck leg)',$mp,295,1234,18.0,0.0,0.0,24.5,7.5,0.0,1.5, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Choucroute Garnie (sauerkraut & meats)',$tk,125,523,8.0,5.0,1.5,8.5,3.0,2.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Moules Marinières (French-style)',$fs,80,335,10.0,4.0,0.5,2.5,0.5,0.0,1.0, 1,1,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Brandade de Morue (salt cod purée)',$fs,150,628,10.0,8.0,0.5,9.0,3.5,0.5,1.5, 1,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Poulet Rôti (French roast chicken)',$mp,175,732,22.0,0.0,0.0,9.5,2.8,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Quenelles de Brochet (pike dumplings)',$fs,155,649,9.0,12.0,0.5,8.0,4.0,0.3,0.6, 1,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Gratin Dauphinois (potato gratin)',$fv,150,628,3.5,14.0,1.0,9.5,6.0,0.8,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Vol-au-Vent (chicken & mushroom)',$tk,240,1004,10.0,18.0,1.0,14.5,7.5,0.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Flamiche (leek tart, Picardy)',$tk,245,1025,6.5,20.0,1.5,15.5,8.5,1.0,0.6, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Soupe de Poisson (Provençal fish soup)',$tk,50,209,5.0,3.0,0.5,2.0,0.3,0.3,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Salade Lyonnaise (frisée & lardons)',$fv,155,649,8.0,5.0,1.0,11.5,3.5,1.5,0.8, 0,0,0,1,0,0,0,0, 0,0,0,0,0,0],

			// ── FRENCH — REGIONAL SPECIALITIES (10) ──
			['Galette Bretonne (buckwheat, ham & cheese)',$bc,230,962,11.0,22.0,1.0,11.0,5.5,1.0,1.0, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Tartiflette (Savoyard potato bake)',$tk,195,816,8.0,14.0,1.0,12.5,7.5,1.0,0.8, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Raclette (French, with charcuterie)',$de,260,1088,12.5,15.0,0.5,17.0,11.0,1.0,1.2, 0,0,1,0,0,0,0,0, 1,0,0,0,0,0],
			['Aligot (cheesy mashed potato, Auvergne)',$fv,170,711,6.0,16.0,0.5,9.5,6.0,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Piperade (Basque pepper & egg)',$fv,95,397,5.0,6.0,4.0,5.5,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Socca (chickpea flatbread, Nice)',$bc,175,732,5.5,18.0,1.0,9.0,1.0,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pissaladière (onion tart, Provençal)',$tk,235,983,5.0,26.0,5.0,12.5,3.0,1.5,1.5, 1,0,0,0,0,1,0,0, 0,0,1,1,0,0],
			['Tian Provençal (vegetable gratin)',$fv,75,314,2.0,6.0,3.5,5.0,1.5,2.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Quiche Alsacienne (onion & bacon)',$tk,255,1067,9.0,18.0,2.5,16.5,7.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Salade Niçoise (traditional)',$fv,120,502,8.0,5.5,2.0,8.0,1.5,1.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],

			// ── FRENCH — BREADS & BAKERY (8) ──
			['Baguette (traditional)',$bc,270,1130,9.0,52.0,3.0,1.5,0.3,2.5,1.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Brioche',$bc,355,1485,8.0,45.0,8.0,15.0,8.5,1.5,0.6, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pain de Campagne (country bread)',$bc,255,1067,8.0,50.0,2.0,1.5,0.3,3.5,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Fougasse (Provençal bread)',$bc,280,1172,7.5,42.0,1.5,9.5,1.2,2.0,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Gougères (cheese puffs, Burgundy)',$sc,340,1423,12.0,22.0,1.0,23.0,13.5,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Palmier (elephant ear pastry)',$sc,450,1883,4.5,50.0,20.0,26.0,16.0,1.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Kouign-Amann (Breton butter cake)',$sc,430,1799,5.0,48.0,22.0,25.0,16.0,1.0,0.6, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Pain d\'Épices (French gingerbread)',$sc,330,1381,4.0,68.0,35.0,4.0,1.0,2.0,0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── FRENCH — DESSERTS & PÂTISSERIE (12) ──
			['Soufflé au Chocolat',$sc,250,1046,6.0,28.0,22.0,13.0,7.5,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Mousse au Chocolat',$sc,225,941,4.5,22.0,20.0,14.0,8.5,1.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Île Flottante (floating island)',$sc,155,649,5.0,25.0,22.0,3.5,2.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Clafoutis (cherry)',$sc,195,816,5.0,28.0,18.0,7.0,3.5,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Far Breton (Breton flan)',$sc,210,879,5.5,32.0,18.0,6.5,3.5,1.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Canelé (Bordeaux custard cake)',$sc,310,1297,5.0,48.0,32.0,10.5,5.5,0.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Financier (almond cake)',$sc,395,1653,7.0,45.0,28.0,21.0,8.0,2.0,0.3, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Paris-Brest (choux praline)',$sc,350,1464,7.0,32.0,18.0,22.0,10.0,2.0,0.2, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Tarte aux Fraises (strawberry tart)',$sc,220,920,3.0,30.0,18.0,10.0,5.5,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Gâteau Basque (cherry cream)',$sc,340,1423,5.5,40.0,20.0,18.0,10.0,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Saint-Honoré (choux & caramel)',$sc,320,1339,4.5,38.0,24.0,17.0,10.0,0.5,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tarte au Citron (lemon tart)',$sc,295,1234,4.0,38.0,25.0,15.0,8.0,0.3,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── FRENCH — SAUCES & CONDIMENTS (6) ──
			['Béarnaise Sauce',$co,480,2008,2.5,1.0,0.5,50.0,30.0,0.0,0.5, 0,0,1,1,0,0,0,0, 1,0,1,1,0,1],
			['Béchamel Sauce',$co,115,481,3.5,8.0,3.5,7.5,4.5,0.0,0.4, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Hollandaise Sauce',$co,465,1946,3.0,0.5,0.3,50.0,30.0,0.0,0.5, 0,0,1,1,0,0,0,0, 1,0,1,1,0,1],
			['Rouille (Provençal garlic sauce)',$co,350,1464,2.0,10.0,0.5,34.0,4.0,0.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Aïoli (garlic mayonnaise)',$co,650,2719,1.5,2.0,0.5,70.0,8.0,0.0,0.8, 0,0,0,1,0,0,0,0, 1,0,1,1,0,1],
			['Dijon Mustard',$co,66,276,4.0,4.0,3.0,3.5,0.2,3.5,5.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── SPANISH — TAPAS & APPETISERS (15) ──
			// ══════════════════════════════════════
			['Jamón Ibérico (acorn-fed)',$mp,270,1130,33.0,0.0,0.0,15.0,5.5,0.0,5.0, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0],
			['Pan con Tomate (tomato bread)',$bc,195,816,4.5,28.0,3.5,7.5,1.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pulpo a la Gallega (Galician octopus)',$fs,120,502,17.0,3.0,0.0,4.5,0.8,0.0,0.8, 1,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Boquerones en Vinagre (marinated anchovies)',$fs,130,544,15.0,1.0,0.5,7.5,1.5,0.0,2.0, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Albondigas (Spanish meatballs)',$mp,175,732,12.0,8.0,2.5,10.5,3.5,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Pimientos del Piquillo Rellenos',$fv,110,460,5.0,6.0,3.5,7.0,3.5,1.0,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Ensaladilla Rusa (Spanish potato salad)',$fv,140,586,3.0,10.0,1.5,10.0,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Pintxos Gilda (olive, anchovy, pepper)',$tk,130,544,5.0,3.0,1.0,11.0,2.0,1.0,3.0, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Txistorra (Basque thin chorizo)',$mp,400,1674,20.0,3.0,1.0,34.0,13.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Callos a la Madrileña (Madrid tripe)',$tk,100,418,8.5,4.0,1.0,5.5,2.0,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Banderillas (pickled skewer)',$fv,50,209,1.5,5.0,2.0,2.5,0.4,1.5,4.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Manchego Cheese (curado)',$de,395,1653,26.0,0.5,0.5,32.0,20.0,0.0,1.6, 0,0,1,0,0,0,0,0, 1,0,0,1,0,1],
			['Salmorejo (Córdoba cold soup)',$tk,85,356,2.5,8.0,3.5,5.0,0.7,1.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Escalivada (roasted veg, Catalan)',$fv,55,230,1.0,5.0,4.0,3.5,0.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Espinacas con Garbanzos (spinach & chickpeas)',$lp,105,439,5.5,12.0,1.0,4.0,0.5,4.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── SPANISH — MAIN DISHES (12) ──
			['Paella Valenciana (rabbit, chicken, snails)',$tk,150,628,9.5,18.0,1.0,4.5,1.0,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fideuà (Catalan noodle paella)',$tk,155,649,9.0,18.0,1.0,5.5,0.8,0.8,0.7, 1,1,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Cochinillo Asado (roast suckling pig)',$mp,250,1046,22.0,0.0,0.0,18.0,7.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pisto Manchego (Spanish ratatouille)',$fv,55,230,1.5,6.0,4.0,2.5,0.4,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Migas (fried breadcrumbs with chorizo)',$tk,285,1193,8.0,28.0,1.5,16.0,4.0,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Bacalao a la Vizcaína (Basque salt cod)',$fs,130,544,14.0,5.0,2.5,6.0,1.0,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Suquet de Peix (Catalan fish stew)',$tk,85,356,10.0,4.5,1.0,3.0,0.5,0.5,0.7, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Empanada Gallega (Galician tuna pie)',$tk,255,1067,9.0,26.0,2.0,13.0,2.5,1.0,0.7, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Marmitako (Basque tuna stew)',$tk,100,418,10.0,8.0,1.0,3.5,0.5,1.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Arroz Negro (black squid ink rice)',$tk,155,649,7.5,22.0,0.5,4.0,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lacón con Grelos (ham with turnip greens)',$mp,125,523,12.0,3.0,0.5,7.5,2.5,2.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cordero al Chilindron (lamb with peppers)',$mp,155,649,14.0,4.0,2.5,9.0,3.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── SPANISH — DESSERTS & SWEETS (8) ──
			['Flan (Spanish egg custard)',$sc,155,649,5.0,22.0,20.0,5.0,2.5,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Turrón de Jijona (soft nougat)',$sc,490,2050,10.0,42.0,35.0,32.0,4.0,5.0,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,0,1],
			['Turrón de Alicante (hard nougat)',$sc,485,2029,12.0,40.0,35.0,30.0,3.5,4.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,0,1],
			['Ensaïmada (Mallorcan pastry)',$sc,370,1548,6.0,48.0,15.0,17.0,9.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Arroz con Leche (Spanish rice pudding)',$sc,140,586,3.5,20.0,14.0,5.0,3.0,0.2,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Leche Frita (fried custard)',$sc,250,1046,5.0,32.0,18.0,11.5,5.0,0.3,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tarta de Santiago (almond cake)',$sc,405,1694,10.0,40.0,30.0,23.0,4.5,4.0,0.2, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Polvorones (crumbly shortbread)',$sc,475,1988,6.0,55.0,22.0,26.0,10.0,2.0,0.1, 0,0,0,0,1,1,0,0, 0,0,1,1,0,1],

			// ── SPANISH — DRINKS (5) ──
			['Sangria (per glass, 200ml)',$dr,100,418,0.2,12.0,10.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Horchata de Chufa (tiger nut milk)',$dr,65,272,0.5,12.0,10.0,2.5,0.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tinto de Verano (red wine & lemonade)',$dr,55,230,0.0,8.0,7.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Café con Leche (Spanish)',$dr,40,167,2.0,4.0,4.0,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Agua de Valencia (orange cocktail)',$dr,120,502,0.5,15.0,14.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
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
				'source_notes'=>'ANSES/Ciqual France / AESAN Spain / USDA FDC. Seeded v43.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 43 );
	}

	/** Seed v44: Native & common Portuguese foods. */
	public static function seed_v44(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 44 ) { return; }
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
			// ── SOUPS (8) ──
			// ══════════════════════════════════════
			['Caldo Verde (traditional, with chouriço)',$tk,58,243,2.5,6.5,0.5,2.5,0.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Sopa da Pedra (stone soup, Ribatejo)',$tk,70,293,4.5,8.0,1.0,2.0,0.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Açorda Alentejana (bread & garlic soup)',$tk,95,397,4.0,10.0,0.5,4.0,0.7,1.0,0.8, 0,0,0,1,0,1,0,0, 0,0,1,1,0,0],
			['Canja de Galinha (chicken rice soup)',$tk,45,188,3.5,5.0,0.3,1.0,0.3,0.3,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sopa de Peixe (Portuguese fish soup)',$tk,55,230,5.5,4.0,0.5,2.0,0.3,0.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Gaspacho Alentejano (bread-thickened)',$tk,80,335,2.0,10.0,2.5,3.5,0.5,1.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Sopa de Legumes (vegetable soup)',$fv,35,146,1.5,5.0,1.5,1.0,0.2,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Creme de Marisco (shellfish bisque)',$tk,85,356,5.0,5.0,1.0,5.0,2.5,0.3,0.8, 1,1,1,0,0,0,0,0, 0,0,1,0,0,0],

			// ── BACALHAU (SALT COD) DISHES (8) ──
			['Bacalhau à Brás (shredded cod & egg)',$tk,170,711,13.0,8.5,0.5,10.0,1.5,1.0,1.5, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Bacalhau com Natas (cod with cream)',$tk,185,774,12.0,10.0,1.0,11.5,6.0,0.5,1.0, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Bacalhau à Gomes de Sá (cod & potato bake)',$tk,140,586,11.0,10.0,0.5,6.5,1.0,1.0,1.5, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Bacalhau à Lagareiro (roasted cod)',$fs,195,816,18.0,5.0,0.5,11.5,1.5,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bacalhau com Todos (boiled cod dinner)',$tk,120,502,14.0,6.0,0.5,4.5,0.7,1.0,1.5, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Pastéis de Bacalhau (cod fritters)',$tk,265,1109,12.5,18.0,0.5,16.0,2.5,1.0,1.0, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Pataniscas de Bacalhau (cod battered)',$tk,240,1004,12.0,16.0,0.5,14.5,2.0,0.5,1.0, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Bacalhau Espiritual (cod soufflé)',$tk,155,649,11.0,8.0,2.0,9.0,4.5,0.5,0.8, 1,0,1,1,0,1,0,0, 0,0,1,0,0,0],

			// ── SEAFOOD DISHES (8) ──
			['Cataplana de Marisco (shellfish cataplana)',$tk,100,418,12.0,4.5,1.0,4.0,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Arroz de Marisco (seafood rice)',$tk,135,565,8.5,16.0,0.5,4.0,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Caldeirada (Portuguese fish stew)',$tk,85,356,9.5,4.5,1.0,3.0,0.5,0.5,0.7, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Amêijoas à Bulhão Pato (clams)',$fs,80,335,10.0,2.0,0.3,3.5,0.5,0.0,1.5, 1,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Sardinhas Assadas (grilled sardines)',$fs,195,816,18.5,0.0,0.0,13.0,3.5,0.0,0.8, 1,0,0,0,0,0,0,0, 0,1,1,1,0,0],
			['Polvo à Lagareiro (roasted octopus)',$fs,130,544,18.0,3.0,0.0,5.0,0.8,0.0,0.8, 1,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Arroz de Tamboril (monkfish rice)',$tk,125,523,9.0,14.0,0.5,3.5,0.5,0.5,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Percebes (goose barnacles, boiled)',$fs,70,293,14.0,2.0,0.0,1.0,0.2,0.0,2.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],

			// ── MEAT DISHES (10) ──
			['Cozido à Portuguesa (mixed boil-up)',$tk,115,481,8.0,6.5,0.5,6.5,2.5,1.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Leitão da Bairrada (roast suckling pig)',$mp,260,1088,22.0,2.0,0.5,18.5,7.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Frango no Churrasco (piri piri chicken)',$mp,185,774,24.0,2.0,1.0,9.0,2.0,0.0,1.0, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Chanfana (goat stew, Coimbra)',$mp,155,649,16.0,2.0,0.5,9.5,3.5,0.3,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Alheira de Mirandela (smoked sausage)',$mp,300,1255,14.0,15.0,0.5,20.5,6.5,0.5,2.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Chouriço Assado (flamed chorizo)',$mp,390,1632,22.0,2.0,0.5,33.0,12.0,0.0,3.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Rojões à Minhota (fried pork cubes)',$mp,275,1151,18.0,3.0,0.5,21.5,7.5,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Febras Grelhadas (grilled pork steaks)',$mp,175,732,26.0,0.5,0.0,7.5,2.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Carne de Porco à Alentejana (pork & clams)',$tk,165,690,14.0,5.0,0.5,10.0,2.5,0.5,1.0, 1,1,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Feijoada à Transmontana (bean stew)',$tk,135,565,8.5,12.0,0.5,6.0,2.0,4.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],

			// ── SNACKS & PETISCOS (8) ──
			['Rissóis de Camarão (prawn rissoles)',$tk,270,1130,7.0,24.0,0.5,16.5,3.5,0.5,0.7, 0,1,1,1,0,1,0,0, 0,0,1,0,0,0],
			['Croquetes de Carne (meat croquettes)',$tk,255,1067,7.5,22.0,0.5,15.5,4.5,0.5,0.7, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Chamuças (Portuguese samosas)',$tk,275,1151,7.0,24.0,1.0,17.0,5.0,1.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Prego no Pão (steak sandwich)',$tk,285,1193,16.0,28.0,1.5,12.0,4.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Tosta Mista (toasted ham & cheese)',$tk,280,1172,14.0,24.0,2.0,14.0,6.0,0.5,1.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Empada de Galinha (chicken pies, 1pc)',$tk,210,879,6.5,18.0,1.0,12.5,4.5,0.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Pica-Pau (sautéed beef strips)',$mp,195,816,18.0,2.0,0.5,12.5,4.0,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Tremoços (pickled lupin beans)',$lp,115,481,16.0,10.0,0.5,2.5,0.3,4.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── RICE & SIDES (6) ──
			['Arroz de Pato (duck rice, traditional)',$tk,180,753,10.0,20.0,0.5,7.0,2.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Arroz de Tomate (tomato rice)',$bc,130,544,2.5,22.0,2.5,3.0,0.4,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Migas Alentejanas (bread migas)',$bc,175,732,4.0,18.0,1.0,9.5,2.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Batatas a Murro (punched potatoes)',$fv,110,460,2.0,18.0,0.5,3.5,0.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Arroz de Feijão (bean rice)',$lp,135,565,4.5,22.0,0.5,2.5,0.4,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Grelos Salteados (sautéed turnip tops)',$fv,50,209,3.0,3.5,0.5,2.5,0.3,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── DESSERTS & PASTRIES (12) ──
			['Pastel de Nata (custard tart, Belém)',$sc,315,1318,4.5,35.0,20.0,17.5,9.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Travesseiro de Sintra (almond pillow)',$sc,400,1674,7.0,42.0,25.0,22.0,8.0,3.0,0.2, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Queijada de Sintra (cheese tart)',$sc,350,1464,6.0,42.0,28.0,17.5,9.0,0.3,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Ovos Moles de Aveiro (egg yolk sweet)',$sc,340,1423,6.0,52.0,42.0,12.0,3.5,0.0,0.1, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Pão de Ló (Portuguese sponge cake)',$sc,310,1297,7.5,50.0,35.0,9.0,2.5,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Arroz Doce (Portuguese rice pudding)',$sc,145,607,3.0,22.0,14.0,4.5,2.8,0.2,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Aletria (vermicelli pudding)',$sc,155,649,3.0,25.0,16.0,4.5,2.5,0.2,0.1, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Toucinho do Céu (almond & egg cake)',$sc,385,1611,7.5,48.0,40.0,18.5,5.0,3.5,0.1, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Bola de Berlim (Portuguese doughnut)',$sc,325,1360,5.0,40.0,18.0,16.0,4.5,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Farturas (fried dough, Portuguese churros)',$sc,350,1464,4.0,44.0,16.0,18.0,3.5,1.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Serradura (Macau sawdust pudding)',$sc,280,1172,3.0,28.0,18.0,17.5,10.5,0.5,0.1, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Rabanadas (Portuguese French toast)',$sc,290,1213,6.5,35.0,18.0,13.5,4.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── BREADS (4) ──
			['Broa de Milho (corn bread)',$bc,215,900,5.0,40.0,2.0,3.5,0.5,3.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pão Alentejano (Alentejo wheat bread)',$bc,260,1088,8.0,50.0,2.0,2.0,0.3,3.0,1.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bolo do Caco (Madeira garlic bread)',$bc,250,1046,5.5,42.0,1.5,6.5,1.0,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Regueifa (sweet ring bread)',$bc,310,1297,6.5,52.0,12.0,8.0,3.0,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── DRINKS (6) ──
			['Ginjinha (sour cherry liqueur, per glass)',$dr,175,732,0.0,20.0,18.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Galão (milky coffee, tall glass)',$dr,50,209,2.0,5.0,5.0,2.0,1.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Bica (Portuguese espresso)',$dr,3,13,0.2,0.5,0.0,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sumo de Laranja Natural (fresh OJ)',$dr,45,188,0.7,10.0,8.5,0.2,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mazagran (iced coffee lemon)',$dr,40,167,0.2,10.0,9.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Licor Beirão (herbal liqueur, per glass)',$dr,165,690,0.0,18.0,16.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
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
				'source_notes'=>'INSA Portugal / USDA FDC. Seeded v44.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 44 );
	}

	/** Seed v45: Iranian, Afghan, Pakistani & Indian foods. */
	public static function seed_v45(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 45 ) { return; }
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
			// ── IRANIAN / PERSIAN (30) ──
			// ══════════════════════════════════════
			// -- Rice & Stews --
			['Chelow (Iranian steamed rice, plain)',$bc,135,565,2.5,28.5,0.0,0.5,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tahdig (crispy rice crust)',$bc,195,816,3.0,30.0,0.5,7.0,1.0,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ghormeh Sabzi (herb stew)',$tk,120,502,9.0,5.0,0.5,7.5,2.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Gheimeh (split pea & meat stew)',$tk,125,523,8.5,8.0,1.0,7.0,2.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fesenjan (pomegranate walnut stew)',$tk,190,795,12.0,12.0,8.0,11.5,1.5,1.5,0.3, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Baghali Polo (dill & fava bean rice)',$bc,165,690,4.5,28.0,0.5,3.5,1.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Zereshk Polo ba Morgh (barberry chicken rice)',$tk,170,711,10.0,24.0,2.0,4.0,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Adas Polo (lentil rice with raisins)',$bc,175,732,5.0,30.0,5.0,3.5,0.5,2.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ash Reshteh (noodle herb soup)',$tk,80,335,3.5,12.0,0.5,2.0,0.3,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Abgoosht (lamb & chickpea broth)',$tk,95,397,7.5,6.0,0.5,4.5,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Kebabs & Grills --
			['Koobideh (minced lamb kebab)',$mp,250,1046,16.0,2.0,0.5,20.0,8.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Joojeh Kebab (saffron chicken)',$mp,160,669,22.0,2.0,1.0,7.0,1.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Barg Kebab (lamb fillet kebab)',$mp,210,879,24.0,0.0,0.0,12.5,5.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chenjeh Kebab (lamb cube kebab)',$mp,195,816,22.0,1.0,0.5,11.5,5.0,0.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Sides & Snacks --
			['Kashk-e Bademjan (aubergine & whey dip)',$co,110,460,4.0,6.0,2.0,8.0,2.0,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Mirza Ghasemi (smoky aubergine & egg)',$fv,95,397,3.5,5.0,3.0,7.0,1.0,1.5,0.3, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Kuku Sabzi (Persian herb frittata)',$de,165,690,8.0,5.0,0.5,12.5,3.0,2.0,0.4, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Dolmeh (stuffed vine leaves, Persian)',$fv,185,774,3.0,12.0,2.0,14.5,2.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Salad Shirazi (cucumber, tomato, onion)',$fv,30,126,1.0,5.0,3.0,0.5,0.1,1.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Sangak Bread (stone-baked flatbread)',$bc,275,1151,9.0,50.0,1.5,3.5,0.5,4.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Barbari Bread (sesame flatbread)',$bc,285,1193,8.5,52.0,2.0,4.5,0.5,2.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Lavash (Persian, thin)',$bc,265,1109,8.0,52.0,1.5,2.5,0.4,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			// -- Sweets & Drinks --
			['Sholeh Zard (saffron rice pudding)',$sc,165,690,2.5,30.0,18.0,3.5,0.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Faloodeh (rose water ice noodles)',$sc,115,481,0.5,28.0,22.0,0.0,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bastani Sonnati (saffron ice cream)',$sc,200,837,3.5,24.0,20.0,10.0,6.0,0.5,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Gaz (Isfahan nougat)',$sc,400,1674,5.0,70.0,60.0,12.0,2.0,1.0,0.0, 0,0,0,1,1,0,0,0, 0,0,1,1,0,1],
			['Sohan (saffron brittle)',$sc,470,1967,6.0,55.0,40.0,26.0,5.0,2.0,0.0, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Doogh (yoghurt soda drink)',$dr,25,105,1.0,2.0,2.0,0.5,0.3,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Persian Tea (chai, with nabat sugar)',$dr,20,84,0.0,5.0,5.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sharbat-e Sekanjabin (mint vinegar drink)',$dr,35,146,0.0,9.0,8.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── AFGHAN (20) ──
			// ══════════════════════════════════════
			['Kabuli Pulao (Afghan national dish)',$tk,185,774,8.0,26.0,5.0,5.5,1.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Qabili Palau (lamb & carrot rice)',$tk,190,795,9.0,26.0,4.0,6.0,1.5,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mantu (Afghan dumplings with yoghurt)',$tk,175,732,8.0,20.0,1.5,7.0,2.5,0.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Ashak (leek dumplings)',$tk,160,669,5.0,22.0,1.0,5.5,2.0,1.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Bolani (stuffed flatbread)',$bc,225,941,5.0,30.0,1.5,9.5,1.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Chapli Kebab (spiced meat patty)',$mp,260,1088,15.0,6.0,1.0,20.0,8.5,0.5,0.8, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Tikka Kebab (Afghan-style, lamb)',$mp,205,858,22.0,2.0,0.5,12.5,5.5,0.0,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shorba (Afghan lamb soup)',$tk,60,251,4.5,5.0,1.0,2.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Qorma-e-Sabzi (green herb stew)',$tk,110,460,8.0,4.0,0.5,7.0,2.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaddo Bourani (pumpkin with yoghurt)',$fv,90,377,3.0,12.0,5.0,3.5,1.5,1.5,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Afghan Naan (tandoor bread)',$bc,280,1172,8.5,52.0,2.0,4.0,0.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Sheer Khurma (vermicelli milk dessert)',$sc,185,774,4.0,25.0,18.0,7.5,3.5,0.5,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Firni (cornflour milk pudding)',$sc,120,502,2.5,18.0,14.0,4.0,2.5,0.0,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Jalebi (Afghan-style, fried)',$sc,375,1569,3.0,58.0,50.0,15.0,2.5,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,1],
			['Sheer Yakh (Afghan ice cream)',$sc,195,816,3.0,28.0,24.0,8.0,5.0,0.0,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Doogh (Afghan salted yoghurt drink)',$dr,28,117,1.2,2.0,2.0,0.8,0.5,0.0,0.8, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kahwa (Afghan green tea, cardamom)',$dr,5,21,0.1,1.0,0.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Afghan Kebab Wrap (with salad)',$tk,235,983,13.0,22.0,2.0,10.5,4.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Dampukht (slow-cooked lamb & rice)',$tk,180,753,10.0,22.0,0.5,6.0,2.0,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lawang (Afghan cardamom pastry)',$sc,390,1632,5.0,52.0,28.0,18.0,8.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── PAKISTANI (25) ──
			// ══════════════════════════════════════
			['Nihari (slow-cooked beef stew)',$tk,135,565,10.0,5.0,0.5,8.5,3.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Haleem (slow-cooked wheat & meat)',$tk,125,523,8.5,12.0,0.5,5.0,1.5,2.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Biryani (Karachi-style, mutton)',$tk,185,774,9.5,22.0,1.0,7.0,2.5,0.5,0.7, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Biryani (Sindhi, chicken)',$tk,175,732,10.0,22.0,1.5,5.5,1.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Karahi Gosht (wok-cooked meat)',$tk,155,649,12.0,4.0,2.0,10.5,4.0,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Seekh Kebab (Pakistani spiced)',$mp,245,1025,16.0,4.0,1.0,18.5,8.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shami Kebab (lentil & meat patty)',$mp,230,962,14.0,10.0,1.0,15.0,5.5,1.5,0.7, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Lahori Fish Fry',$fs,235,983,16.0,12.0,0.5,14.0,2.5,0.5,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Paye (trotters soup)',$tk,85,356,7.0,2.0,0.0,5.5,2.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sajji (whole roasted lamb/chicken)',$mp,195,816,22.0,1.0,0.0,11.5,4.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chana Chaat (chickpea salad)',$lp,125,523,5.5,18.0,3.0,3.5,0.4,4.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dahi Bhalla (yoghurt lentil balls)',$lp,135,565,4.5,15.0,5.0,6.0,1.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Puri Halwa (breakfast, fried bread & semolina)',$bc,380,1590,5.0,48.0,18.0,19.0,4.0,1.5,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Paratha (layered, with butter)',$bc,330,1381,6.5,42.0,1.0,15.5,6.0,2.0,0.6, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Naan Khatai (cardamom shortbread)',$sc,445,1862,5.0,58.0,25.0,22.0,8.0,1.0,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Gulab Jamun (Pakistani-style)',$sc,355,1485,4.5,50.0,40.0,15.5,8.5,0.3,0.1, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Ras Malai (cream cheese in milk)',$sc,195,816,5.0,22.0,20.0,10.0,6.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kheer (Pakistani rice pudding)',$sc,140,586,3.5,20.0,14.0,5.0,3.0,0.2,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Lassi (salty, Pakistani)',$dr,40,167,2.0,3.0,3.0,2.0,1.2,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Rooh Afza (rose syrup drink)',$dr,55,230,0.0,14.0,13.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Doodh Patti (milk tea)',$dr,55,230,2.0,6.5,6.0,2.0,1.2,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kulfi (Pakistani, pistachio)',$sc,210,879,4.0,25.0,22.0,10.5,6.5,0.5,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Chicken Tikka (Pakistani charcoal)',$mp,165,690,25.0,3.0,1.5,5.5,1.5,0.0,1.0, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Bun Kebab (Pakistani street burger)',$tk,260,1088,10.0,28.0,3.0,12.5,4.0,1.5,0.8, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Gol Gappay (pani puri, Pakistani)',$sc,215,900,3.0,32.0,4.0,8.5,1.0,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── INDIAN (40) ──
			// ══════════════════════════════════════
			// -- Curries & Mains --
			['Dal Makhani (black lentil curry)',$tk,130,544,5.5,14.0,1.0,6.0,3.5,3.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Rajma (kidney bean curry)',$tk,115,481,5.5,16.0,1.5,3.0,0.5,4.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chole Bhature (chickpea & fried bread)',$tk,280,1172,7.5,32.0,3.0,14.0,2.5,4.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Paneer Tikka (grilled cheese)',$de,265,1109,16.0,6.0,2.0,20.0,12.0,0.5,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Malai Kofta (cream cheese dumplings)',$tk,185,774,6.0,14.0,4.0,12.0,6.5,1.0,0.5, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Vindaloo (pork, Goan)',$tk,145,607,12.0,5.0,2.0,8.5,2.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Rogan Josh (Kashmiri lamb)',$tk,135,565,12.0,4.0,2.0,8.0,3.5,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Mutter Paneer (peas & cheese)',$tk,145,607,7.0,6.0,2.0,10.0,5.5,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Bhindi Masala (okra curry)',$fv,85,356,2.0,6.0,2.0,6.0,0.8,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baingan Bharta (smoky aubergine)',$fv,75,314,2.0,6.5,3.0,4.5,0.5,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Fish Curry (Kerala, coconut)',$tk,120,502,12.0,5.0,2.0,6.0,4.0,1.0,0.6, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Prawn Malai Curry (Bengali)',$tk,140,586,12.0,5.0,2.0,8.5,5.0,0.5,0.5, 0,1,1,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Breads & Rice --
			['Garlic Naan',$bc,320,1339,9.0,48.0,3.5,10.0,2.0,2.0,0.9, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Keema Naan (minced meat stuffed)',$bc,310,1297,11.0,40.0,2.0,12.0,4.0,1.5,0.9, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Roti (wholemeal, tawa)',$bc,250,1046,8.5,45.0,1.5,4.5,0.7,4.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Jeera Rice (cumin rice)',$bc,155,649,3.0,28.0,0.0,3.0,0.5,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lemon Rice (South Indian)',$bc,165,690,3.0,28.0,0.5,5.0,0.8,0.5,0.4, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Puri (deep-fried bread, Indian)',$bc,370,1548,6.0,42.0,1.5,20.0,3.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			// -- Snacks & Street Food --
			['Samosa (vegetable, Indian)',$tk,260,1088,4.5,26.0,2.0,15.5,2.5,2.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Aloo Tikki (potato patty)',$fv,195,816,3.5,22.0,1.5,10.5,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pav Bhaji (Mumbai street food)',$tk,220,920,5.0,28.0,4.0,10.0,4.5,3.0,0.6, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Vada Pav (Mumbai potato burger)',$tk,270,1130,5.5,32.0,2.0,13.5,2.0,2.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Pani Puri (6 pcs with water)',$sc,175,732,3.0,28.0,4.0,6.0,0.8,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Bhel Puri (puffed rice mix)',$sc,190,795,4.5,28.0,4.0,7.0,1.0,2.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			// -- Desserts & Drinks --
			['Gulab Jamun (Indian-style)',$sc,350,1464,4.5,50.0,40.0,15.0,8.0,0.3,0.1, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Rasgulla (Bengali cheese balls)',$sc,185,774,4.0,32.0,28.0,4.0,2.5,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Barfi (milk fudge)',$sc,350,1464,6.0,52.0,45.0,13.5,8.0,0.0,0.0, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Jalebi (Indian, crispy)',$sc,370,1548,3.0,60.0,50.0,14.0,2.5,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,1],
			['Laddu (besan/chickpea flour)',$sc,420,1757,6.0,52.0,38.0,22.0,5.0,2.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Payasam (South Indian, vermicelli)',$sc,145,607,3.0,22.0,16.0,5.0,3.0,0.5,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Masala Chai (spiced milk tea)',$dr,50,209,1.5,7.0,6.5,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Mango Lassi (sweet)',$dr,75,314,2.0,13.5,12.5,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Nimbu Pani (lemon water)',$dr,25,105,0.1,6.0,5.5,0.0,0.0,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Filter Coffee (South Indian)',$dr,45,188,1.5,5.5,5.0,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
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
				'source_notes'=>'IFCT India / USDA FDC. Seeded v45.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 45 );
	}

	/** Seed v46: Native & common Bangladeshi foods. */
	public static function seed_v46(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 46 ) { return; }
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
			// ── FISH CURRIES (MACHHER JHOL) (12) ──
			// ══════════════════════════════════════
			['Ilish Bhapa (steamed hilsa in mustard)',$fs,225,941,16.0,2.0,0.5,17.0,4.5,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ilish Machher Jhol (hilsa curry)',$fs,185,774,14.0,4.0,1.0,13.0,3.5,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ilish Polao (hilsa with fragrant rice)',$tk,195,816,9.0,24.0,0.5,7.5,2.0,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rui Machher Jhol (rohu fish curry)',$fs,110,460,12.0,3.5,1.0,5.5,1.0,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Machher Kalia (spicy fish curry)',$fs,135,565,12.5,5.0,1.5,7.5,1.5,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shorshe Ilish (hilsa in mustard sauce)',$fs,215,900,15.0,3.0,0.5,16.5,4.5,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chingri Malai Curry (prawn coconut)',$fs,155,649,12.0,5.0,2.0,10.0,6.5,0.5,0.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Machher Jhol (basic fish curry)',$fs,100,418,10.0,4.0,1.0,5.0,1.0,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Doi Maach (fish in yoghurt sauce)',$fs,130,544,12.0,3.5,2.0,7.5,2.5,0.0,0.5, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Shutki Bhuna (dried fish curry)',$fs,175,732,22.0,4.0,1.0,8.0,1.5,0.5,3.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pabda Machher Jhol (pabda catfish curry)',$fs,95,397,10.5,3.5,0.5,4.5,1.0,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Macher Chop (fish cutlet)',$fs,225,941,10.0,16.0,0.5,13.5,2.5,1.0,0.6, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── MEAT CURRIES & DISHES (10) ──
			['Kacchi Biryani (Dhaka-style, mutton)',$tk,195,816,10.0,24.0,1.0,7.0,2.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tehari (beef with rice, Old Dhaka)',$tk,185,774,8.5,24.0,1.0,6.5,2.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rezala (white mutton curry)',$tk,155,649,12.0,4.0,1.5,10.5,4.5,0.5,0.5, 0,0,1,0,1,0,0,0, 0,0,1,0,0,0],
			['Bhuna Khichuri (spiced rice & lentil)',$bc,155,649,5.0,24.0,1.0,4.0,1.0,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kala Bhuna (Chittagong dry beef curry)',$mp,185,774,16.0,4.0,1.5,12.0,4.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kosha Mangsho (slow-cooked mutton)',$mp,175,732,14.0,4.0,1.5,11.5,4.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Murgi Roast (Bangladeshi chicken roast)',$mp,185,774,18.0,5.0,2.0,10.0,3.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Moghlai Paratha (egg-stuffed paratha)',$bc,310,1297,8.0,32.0,1.5,16.5,5.0,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Shami Kebab (Bangladeshi, lentil & beef)',$mp,235,983,14.0,10.0,1.0,15.5,6.0,1.5,0.7, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Nargisi Kofta (Scotch egg curry)',$tk,195,816,10.0,6.0,2.0,14.0,5.0,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],

			// ── DAL & VEGETABLE DISHES (TORKARI) (12) ──
			['Masoor Dal (red lentil, Bangladeshi)',$lp,95,397,5.5,13.0,1.0,2.5,0.4,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mung Dal (yellow, with garlic tarka)',$lp,100,418,6.0,14.0,1.0,2.0,0.3,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cholar Dal (Bengal gram, coconut)',$lp,125,523,5.5,16.0,2.0,4.5,2.0,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Aloo Bharta (mashed potato with mustard oil)',$fv,110,460,2.0,14.0,0.5,5.5,0.7,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Begun Bharta (mashed aubergine)',$fv,75,314,1.5,5.0,2.5,5.5,0.7,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sheem Bhaji (flat bean curry)',$fv,55,230,2.5,5.5,1.0,2.5,0.3,2.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lau Ghonto (bottle gourd curry)',$fv,45,188,1.5,5.0,2.0,2.0,0.3,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Potol Bhaji (pointed gourd fry)',$fv,65,272,1.5,5.0,1.5,4.5,0.5,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kumra Bhaji (pumpkin curry)',$fv,50,209,1.0,7.0,3.0,2.0,0.3,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chorchori (mixed vegetable stir-fry)',$fv,55,230,2.0,5.5,1.5,3.0,0.4,2.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shukto (bitter vegetable medley)',$fv,65,272,2.5,6.0,1.5,3.5,0.5,2.5,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Dhokar Dalna (lentil cake curry)',$lp,130,544,5.5,12.0,1.5,7.0,1.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── RICE DISHES (8) ──
			['Bhaat (plain steamed rice, Bangladeshi)',$bc,130,544,2.5,28.5,0.0,0.3,0.1,0.4,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Polao (Bangladeshi fragrant rice)',$bc,170,711,3.5,28.0,0.5,5.0,1.5,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Khichuri (rice & lentil comfort food)',$bc,140,586,4.5,22.0,0.5,3.5,1.0,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Panta Bhat (fermented rice, Pohela Boishakh)',$bc,115,481,2.0,25.0,0.5,0.3,0.1,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Morog Polao (chicken pulao)',$tk,180,753,9.0,24.0,0.5,5.5,1.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Fried Rice (Bangladeshi-Chinese)',$tk,185,774,6.0,24.0,1.5,7.0,1.5,0.5,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Bhuna Khichuri with Beef',$tk,175,732,8.5,22.0,1.0,6.0,2.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Jorda (sweet saffron rice)',$sc,220,920,2.5,38.0,18.0,6.5,2.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── BREADS & SNACKS (NASHTA) (12) ──
			['Luchi (Bengali fried bread)',$bc,350,1464,5.5,40.0,1.0,19.0,3.5,1.5,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Porota (layered flatbread, Dhaka)',$bc,330,1381,6.5,42.0,1.0,15.0,3.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Paratha (egg, Bangladeshi)',$bc,310,1297,8.0,35.0,1.0,15.5,4.0,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Singara (Bangladeshi samosa)',$tk,255,1067,4.5,26.0,1.5,15.0,2.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Piyaju (onion fritters, lentil)',$lp,280,1172,6.0,22.0,2.0,19.0,3.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Beguni (aubergine fritters)',$fv,250,1046,3.0,20.0,1.5,17.5,2.5,2.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Alur Chop (potato chop/croquette)',$fv,235,983,4.0,24.0,1.0,14.0,2.0,2.0,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Fuchka (Bangladeshi pani puri)',$sc,195,816,3.5,28.0,4.0,7.5,1.0,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Chotpoti (chickpea & potato snack)',$lp,145,607,4.5,20.0,2.0,5.5,0.8,3.0,1.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Jhalmuri (spiced puffed rice mix)',$sc,165,690,4.0,26.0,2.0,5.5,0.8,2.0,1.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Chanachur (Bombay mix, Bangladeshi)',$sc,480,2008,12.0,45.0,5.0,28.0,4.5,5.0,2.5, 0,0,0,0,1,1,0,0, 0,0,1,1,1,1],
			['Dim er Devil (spiced Scotch egg)',$mp,245,1025,11.0,10.0,1.0,18.0,5.5,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── SWEETS (MISHTI) (12) ──
			['Roshogolla (spongy cheese ball)',$sc,180,753,4.0,30.0,26.0,5.0,3.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Mishti Doi (sweetened yoghurt)',$de,120,502,3.5,18.0,16.0,3.5,2.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Sandesh (fresh cheese sweet)',$sc,310,1297,7.0,45.0,40.0,11.0,7.0,0.0,0.0, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Chomchom (oblong cheese sweet)',$sc,285,1193,5.5,42.0,38.0,10.5,6.5,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kalojam (dark gulab jamun)',$sc,360,1506,4.5,52.0,42.0,15.0,8.0,0.3,0.1, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Pantua (fried milk ball in syrup)',$sc,345,1443,4.0,50.0,40.0,14.5,8.0,0.3,0.1, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Misti Paan (sweet betel leaf)',$sc,105,439,1.0,22.0,18.0,2.0,0.5,0.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,0,1],
			['Shemai (vermicelli milk dessert)',$sc,155,649,3.0,22.0,16.0,6.0,3.5,0.3,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Payesh (Bengali rice pudding)',$sc,140,586,3.0,20.0,14.0,5.0,3.0,0.2,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Patishapta (stuffed crepe with kheer)',$sc,200,837,4.5,30.0,16.0,7.0,3.5,0.5,0.1, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bhapa Pitha (steamed rice cake)',$sc,195,816,3.0,38.0,14.0,3.5,1.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Chitoi Pitha (rice pancake with coconut)',$sc,210,879,3.0,35.0,10.0,7.0,4.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── PITHA (TRADITIONAL CAKES) (6) ──
			['Nakshi Pitha (decorated rice cake)',$sc,285,1193,3.5,50.0,18.0,8.0,3.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Puli Pitha (rice dumpling, jaggery)',$sc,245,1025,2.5,45.0,22.0,6.0,3.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tel Pitha (fried rice cake, coconut)',$sc,320,1339,3.0,42.0,18.0,15.5,8.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dudh Pitha (milk-soaked rice cake)',$sc,175,732,3.5,28.0,14.0,5.5,3.0,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Malpua (Bengali sweet pancake)',$sc,330,1381,4.0,42.0,28.0,16.5,4.5,0.5,0.1, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Rosh Bora (lentil balls in syrup)',$sc,290,1213,5.0,42.0,30.0,11.5,2.0,1.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── CONDIMENTS & SIDES (8) ──
			['Kasundi (Bengali mustard relish)',$co,75,314,2.5,8.0,4.0,3.5,0.4,1.5,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Aam Achar (raw mango pickle)',$co,135,565,1.5,15.0,8.0,8.0,1.0,1.5,4.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tok Dal (sour lentil, tamarind)',$lp,85,356,5.0,12.0,2.0,1.5,0.2,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bhorta (generic mash, various veg)',$fv,90,377,2.0,6.0,1.0,6.5,0.8,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shutki Bhorta (dried fish mash)',$fs,165,690,20.0,3.0,0.5,8.5,1.5,0.5,3.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Narkel Naru (coconut balls)',$sc,385,1611,4.0,42.0,35.0,23.0,18.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Til er Naru (sesame seed balls)',$sc,440,1841,10.0,38.0,28.0,28.0,4.5,5.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ghee Bhaat (rice with clarified butter)',$bc,175,732,2.5,28.0,0.0,6.5,4.0,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── DRINKS (6) ──
			['Cha (Bangladeshi tea, with condensed milk)',$dr,55,230,1.0,10.0,9.5,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Borhani (spiced yoghurt drink)',$dr,30,126,1.5,3.0,2.5,1.0,0.5,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Shorbot (lemon sherbet drink)',$dr,30,126,0.0,7.5,7.0,0.0,0.0,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Aam er Shorbot (green mango drink)',$dr,35,146,0.2,8.5,7.5,0.1,0.0,0.3,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dab er Pani (green coconut water)',$dr,19,79,0.7,3.7,2.6,0.2,0.2,1.1,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Faluda (rose milk with vermicelli)',$dr,110,460,2.0,20.0,16.0,2.5,1.5,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── BANGLADESHI-CHINESE FUSION (6) ──
			['Chilli Chicken (Bangladeshi-Chinese)',$tk,195,816,14.0,10.0,4.0,11.0,2.0,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Chow Mein (Bangladeshi-style)',$tk,165,690,6.0,22.0,2.5,6.0,1.0,1.0,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Chicken Lollipop',$mp,240,1004,14.0,12.0,3.0,15.5,3.5,0.5,1.0, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Fried Rice (Bangladeshi-style)',$tk,180,753,5.5,24.0,1.5,7.0,1.5,0.5,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Sweet and Sour Fish (Bangladeshi)',$fs,185,774,11.0,18.0,10.0,7.0,1.0,0.5,0.8, 1,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Mixed Vegetables (Bangladeshi-Chinese)',$fv,75,314,2.5,7.0,2.5,4.0,0.5,2.0,1.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
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
				'source_notes'=>'FCT Bangladesh / USDA FDC. Seeded v46.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 46 );
	}

	/** Seed v47: Nepali, Bhutanese & Myanmar foods. */
	public static function seed_v47(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 47 ) { return; }
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
			// ── NEPALI — DAL BHAT & MAINS (12) ──
			// ══════════════════════════════════════
			['Dal Bhat (Nepali, lentil soup & rice)',$tk,135,565,5.0,24.0,0.5,2.0,0.3,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Dal Bhat Tarkari (full plate, avg)',$tk,145,607,5.5,22.0,1.0,3.5,0.5,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kwati (mixed bean sprouted soup)',$lp,80,335,5.0,11.0,1.0,1.5,0.2,3.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gundruk Ko Jhol (fermented greens soup)',$fv,25,105,2.0,3.0,0.5,0.5,0.1,2.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Thukpa (Tibetan-Nepali noodle soup)',$tk,85,356,5.0,11.0,0.5,2.5,0.8,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Choila (Newari grilled buffalo meat)',$mp,190,795,22.0,2.0,0.5,10.5,3.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sekuwa (Nepali grilled meat skewer)',$mp,200,837,22.0,3.0,1.5,11.0,4.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sukuti (dried meat, Nepali jerky)',$mp,295,1234,50.0,5.0,1.0,8.0,3.0,0.0,3.0, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Gorkhali Lamb Curry',$tk,145,607,12.0,4.0,1.5,9.0,3.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Masura Ko Dal (Nepali red lentil)',$lp,90,377,5.5,13.0,1.0,2.0,0.3,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Alu Tama Bodi (potato, bamboo, beans)',$fv,75,314,3.0,10.0,1.5,2.5,0.4,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Achar (Nepali tomato pickle)',$co,55,230,1.0,6.0,4.0,3.0,0.3,1.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── NEPALI — SNACKS & STREET FOOD (10) ──
			['Momo (steamed, buff/chicken, 8 pcs)',$tk,185,774,10.0,22.0,1.0,6.5,2.0,0.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Momo (fried, 8 pcs)',$tk,265,1109,9.0,24.0,1.0,15.0,3.5,0.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Jhol Momo (soup momo, 8 pcs)',$tk,195,816,10.0,22.0,2.0,7.5,2.5,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kothey Momo (pan-fried, 8 pcs)',$tk,230,962,9.5,22.0,1.0,11.5,3.0,0.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Chatamari (Newari rice crepe)',$bc,195,816,7.0,24.0,1.5,8.0,2.0,1.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Bara (Newari lentil patty)',$lp,220,920,8.0,18.0,0.5,13.5,2.5,2.0,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Yomari (Newari rice dumpling, chaku)',$sc,225,941,3.5,40.0,15.0,5.5,1.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Samosa (Nepali-style, vegetable)',$tk,255,1067,4.5,26.0,1.5,15.0,2.5,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Sel Roti (Nepali rice doughnut)',$sc,310,1297,4.0,48.0,10.0,12.0,2.5,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pani Puri (Nepali, gol gappa)',$sc,185,774,3.0,28.0,4.0,7.0,1.0,1.5,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── NEPALI — SWEETS & DRINKS (8) ──
			['Juju Dhau (Bhaktapur king yoghurt)',$de,110,460,4.5,12.0,10.0,5.0,3.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Lakhamari (Newari sweet bread)',$sc,395,1653,6.0,60.0,25.0,14.5,5.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Sikarni (spiced hung yoghurt)',$de,135,565,4.0,14.0,12.0,7.0,4.0,0.0,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Kheer (Nepali rice pudding)',$sc,140,586,3.0,20.0,14.0,5.0,3.0,0.2,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Chaku (jaggery molasses hard candy)',$sc,380,1590,2.0,88.0,80.0,2.0,0.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Masala Tea (Nepali chiya)',$dr,50,209,1.5,7.0,6.5,1.5,0.8,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Tongba (millet beer, fermented)',$dr,40,167,0.5,7.0,2.0,0.2,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],
			['Chhyang (Nepali rice wine)',$dr,50,209,0.3,6.0,2.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],

			// ══════════════════════════════════════
			// ── BHUTANESE (20) ──
			// ══════════════════════════════════════
			['Ema Datshi (chilli & cheese stew)',$tk,145,607,6.0,5.0,2.0,11.0,7.0,1.5,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Kewa Datshi (potato & cheese)',$tk,130,544,5.0,12.0,1.0,7.5,4.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Shamu Datshi (mushroom & cheese)',$tk,120,502,5.5,4.0,1.0,9.0,5.5,1.5,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Phaksha Paa (pork with chilli)',$mp,195,816,16.0,3.0,1.0,13.5,5.0,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Sikam Paa (dried pork belly stew)',$mp,310,1297,15.0,3.0,0.5,27.0,10.0,0.5,2.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Jasha Maru (spiced chicken stew)',$tk,110,460,12.0,4.0,1.5,5.0,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shakam Ema Datshi (dried beef & chilli cheese)',$tk,175,732,14.0,4.0,1.5,11.5,6.5,0.5,1.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Hoentay (buckwheat dumplings, Haa)',$tk,175,732,5.0,22.0,1.0,7.5,1.5,2.0,0.4, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Momos (Bhutanese, cheese & cabbage)',$tk,180,753,6.5,22.0,1.0,7.0,3.5,1.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Juma (Bhutanese blood sausage)',$mp,250,1046,12.0,5.0,0.5,20.0,8.0,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Red Rice (Bhutanese, cooked)',$bc,115,481,2.5,24.0,0.3,0.8,0.2,1.8,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ezay (Bhutanese chilli sauce)',$co,35,146,1.0,5.0,3.0,1.0,0.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Goep (tripe stew, Bhutanese)',$tk,85,356,8.0,3.0,0.5,4.5,1.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lom (turnip leaf stew)',$fv,40,167,2.0,4.5,1.0,1.5,0.8,2.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Khule (buckwheat pancake)',$bc,185,774,5.5,28.0,1.0,6.0,1.0,3.0,0.3, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Puta (buckwheat noodles, Bhutanese)',$bc,145,607,5.0,28.0,0.5,1.5,0.3,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Zow Shungo (rice & veg mixed dish)',$tk,110,460,3.0,18.0,1.5,3.0,1.5,1.5,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Suja (Bhutanese butter tea)',$dr,55,230,0.5,1.0,0.5,5.5,3.5,0.0,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Ara (Bhutanese rice wine)',$dr,65,272,0.2,5.0,1.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],
			['Doma (betel nut chew, Bhutanese)',$sc,50,209,0.5,8.0,2.0,1.5,0.5,1.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],

			// ══════════════════════════════════════
			// ── MYANMAR / BURMESE (30) ──
			// ══════════════════════════════════════
			// -- Salads (Thoke) --
			['Lahpet Thoke (tea leaf salad)',$fv,120,502,4.5,8.0,1.5,8.5,1.0,3.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Gin Thoke (ginger salad)',$fv,105,439,3.0,10.0,2.0,6.5,0.8,2.0,1.5, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Kyet Thar Thoke (chicken salad)',$mp,145,607,14.0,6.0,2.0,7.5,1.5,1.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Samusa Thoke (samosa salad)',$fv,185,774,4.0,18.0,2.5,11.0,2.0,2.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tohu Thoke (chickpea tofu salad)',$lp,115,481,5.0,10.0,1.0,6.5,0.8,3.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			// -- Noodles (Khaut Swe) --
			['Mohinga (fish noodle soup, national dish)',$tk,95,397,6.0,12.0,0.5,3.0,0.5,1.0,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shan Noodles (Shan khaut swe)',$tk,155,649,7.0,22.0,1.5,4.5,1.0,0.5,1.0, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Nan Gyi Thoke (thick noodle salad)',$tk,160,669,7.5,20.0,1.5,5.5,1.0,1.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khow Suey (coconut chicken noodles)',$tk,145,607,7.5,14.0,1.5,7.0,4.5,0.5,0.8, 0,0,0,0,1,1,0,0, 0,0,1,0,0,0],
			['Meeshay (Shan rice noodles with pork)',$tk,135,565,6.5,18.0,1.0,4.5,1.0,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			// -- Curries & Mains (Hin) --
			['Wet Thar Hin (pork curry, Burmese)',$mp,185,774,14.0,4.0,1.5,13.0,4.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Kyet Thar Hin (chicken curry, Burmese)',$tk,140,586,13.0,4.0,1.5,8.0,2.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ametha Hin (beef curry, Burmese)',$mp,155,649,14.0,4.0,1.0,9.5,3.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Nga Thar Hin (fish curry, Burmese)',$fs,115,481,12.0,3.5,1.0,6.0,1.0,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Danpauk (Burmese biryani)',$tk,180,753,8.5,24.0,1.0,6.0,1.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Htamin Jin (Shan rice balls)',$bc,140,586,3.0,26.0,0.5,2.5,0.4,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			// -- Snacks & Sides --
			['Samusa (Burmese samosa, 1 pc)',$tk,85,356,2.0,8.5,0.5,5.0,1.0,0.5,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Mont Lin Maya (stuffed rice pancake pairs)',$sc,195,816,4.5,25.0,1.5,9.0,1.5,0.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['E Kya Kway (Burmese fried dough stick)',$bc,385,1611,7.0,42.0,1.0,22.0,3.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tohu (Shan chickpea tofu)',$lp,100,418,6.0,12.0,0.5,3.5,0.5,3.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Balachaung (dried shrimp chilli relish)',$co,280,1172,18.0,12.0,3.0,18.0,2.5,2.0,4.0, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ngapi Ye (fermented fish paste sauce)',$co,50,209,6.0,3.0,0.5,1.5,0.3,0.0,8.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Sweets & Drinks --
			['Mont Lone Yay Paw (glutinous rice balls)',$sc,195,816,2.5,38.0,15.0,4.0,2.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Shwe Yin Aye (golden heart cooler)',$dr,85,356,1.0,18.0,14.0,1.5,1.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Faluda (Burmese, rose milk)',$dr,115,481,2.0,20.0,16.0,3.0,1.5,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Htan Yay (toddy palm juice)',$dr,35,146,0.3,8.5,7.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Laphet Yay (Burmese green tea)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Mont Sein Baung (semolina cake)',$sc,310,1297,4.0,42.0,22.0,14.5,7.0,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
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
				'source_notes'=>'FAO SMILING / USDA FDC. Seeded v47.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 47 );
	}

	/** Seed v48: Lao, Thai, Cambodian & Vietnamese foods. */
	public static function seed_v48(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 48 ) { return; }
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
			// ── LAOS (20) ──
			// ══════════════════════════════════════
			['Khao Niaw (Lao sticky rice)',$bc,170,711,3.5,37.0,0.0,0.3,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Laab (Lao minced meat salad)',$mp,130,544,14.0,3.0,1.0,7.0,2.5,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Laab Pla (fish laab)',$fs,110,460,14.0,3.0,1.0,5.0,1.0,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tam Mak Hoong (Lao papaya salad)',$fv,65,272,2.0,12.0,7.0,1.0,0.2,2.5,2.0, 1,1,0,0,1,0,0,0, 0,0,1,0,1,1],
			['Or Lam (Luang Prabang stew)',$tk,95,397,7.0,5.0,1.0,5.5,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ping Kai (Lao grilled chicken)',$mp,175,732,22.0,3.0,2.0,8.5,2.0,0.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khao Piak Sen (Lao noodle soup)',$tk,75,314,4.5,10.0,0.5,2.0,0.5,0.3,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khao Poon (Lao rice vermicelli soup)',$tk,80,335,4.0,10.0,1.0,2.5,1.5,0.5,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mok Pa (steamed fish in banana leaf)',$fs,120,502,14.0,3.0,0.5,6.0,1.5,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sai Oua (Lao herb sausage)',$mp,275,1151,14.0,5.0,1.5,22.0,7.5,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Jaew Bong (roasted chilli dip)',$co,55,230,1.5,8.0,3.0,2.0,0.3,1.5,2.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaeng Nor Mai (bamboo shoot soup)',$tk,40,167,2.5,4.0,0.5,1.5,0.5,2.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khao Jee (Lao grilled sticky rice)',$bc,185,774,3.0,35.0,0.5,4.0,1.0,0.5,0.2, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Naem Khao (crispy rice salad)',$tk,190,795,6.0,22.0,2.0,9.0,2.0,1.0,1.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Phan Phak (Lao spring rolls, fresh)',$fv,85,356,3.5,14.0,1.5,1.5,0.3,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ping Sin (Lao grilled beef)',$mp,195,816,24.0,3.0,2.0,9.5,3.5,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khai Paen (fried river weed snack)',$sc,230,962,8.0,30.0,1.0,10.0,1.5,3.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Khao Lam (bamboo sticky rice)',$sc,200,837,3.0,38.0,8.0,4.0,2.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lao Beer (per 100ml)',$dr,42,176,0.3,3.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,1,0,0, 0,0,0,0,1,1],
			['Lao Lao (rice whisky, per 100ml)',$dr,230,962,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],

			// ══════════════════════════════════════
			// ── THAILAND (30) ──
			// ══════════════════════════════════════
			// -- Curries --
			['Panang Curry (beef)',$tk,165,690,10.0,6.0,3.0,11.5,7.5,0.5,1.0, 1,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Yellow Curry (chicken)',$tk,120,502,8.0,8.0,3.0,6.5,4.5,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Jungle Curry (pork, no coconut)',$tk,85,356,9.0,4.0,1.5,3.5,0.8,1.5,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaeng Khiao Wan (green curry, authentic)',$tk,135,565,8.5,6.5,3.0,8.5,5.5,0.8,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Khao Soi (Chiang Mai curry noodle)',$tk,175,732,9.0,18.0,2.5,8.0,5.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Gaeng Hung Lay (Northern pork curry)',$mp,165,690,12.0,8.0,5.0,10.0,3.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			// -- Stir-fry & Mains --
			['Pad Krapao (holy basil stir-fry)',$tk,155,649,12.0,12.0,2.0,7.5,1.5,0.5,2.0, 1,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Pad See Ew (wide noodle stir-fry)',$tk,165,690,7.5,22.0,3.0,5.5,1.0,0.5,2.0, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Pad Pak Ruam (mixed vegetable stir-fry)',$fv,60,251,2.5,5.0,2.0,3.5,0.5,2.0,1.0, 1,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Kai Med Ma Muang (cashew chicken, Thai)',$tk,180,753,14.0,10.0,3.5,9.5,1.5,1.0,1.5, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Pla Pao (salt-crusted grilled fish)',$fs,110,460,20.0,0.0,0.0,3.0,0.5,0.0,2.0, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Mu Ping (Thai grilled pork skewer)',$mp,195,816,18.0,8.0,6.0,10.0,3.5,0.0,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Khao Man Gai (Thai chicken rice)',$tk,160,669,10.5,22.0,0.5,3.5,0.8,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Kai Yang (Thai grilled chicken)',$mp,170,711,22.0,3.0,2.0,7.5,2.0,0.0,1.2, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Soups --
			['Tom Yum Goong (hot & sour prawn soup)',$tk,55,230,5.5,4.0,1.5,2.0,0.3,0.5,1.5, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tom Kha Gai (coconut chicken soup)',$tk,90,377,5.5,5.0,2.0,6.0,4.5,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaeng Jued (Thai clear soup)',$tk,30,126,2.5,2.5,0.5,1.0,0.3,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			// -- Salads --
			['Som Tam Thai (papaya salad, peanut)',$fv,65,272,2.5,11.0,8.0,1.5,0.2,2.0,2.0, 1,1,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Yam Nua (Thai beef salad)',$mp,115,481,14.0,5.0,3.0,4.5,1.5,1.0,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Yam Woon Sen (glass noodle salad)',$tk,95,397,5.0,12.0,3.0,3.0,0.5,0.5,1.5, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Laab Gai (Thai chicken laab)',$mp,125,523,14.0,4.0,1.5,6.0,1.5,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Snacks & Street --
			['Moo Satay (pork satay, 4 sticks)',$mp,190,795,15.0,8.0,5.0,11.5,3.0,0.5,0.8, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Tod Mun Pla (Thai fish cakes)',$fs,195,816,11.0,12.0,2.0,11.5,2.0,1.0,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Poh Pia Tod (Thai spring rolls, fried)',$tk,230,962,4.5,24.0,2.0,13.0,2.0,1.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kanom Krok (coconut pancake cups)',$sc,195,816,2.5,22.0,10.0,11.0,8.5,0.5,0.2, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Roti Sai Mai (cotton candy roti)',$sc,335,1402,4.0,48.0,22.0,14.5,5.0,0.5,0.2, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			// -- Sweets & Drinks --
			['Mango Sticky Rice (khao niao mamuang)',$sc,175,732,2.5,32.0,14.0,4.5,3.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Thai Iced Tea (cha yen)',$dr,85,356,1.5,16.0,15.0,2.0,1.2,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Nam Manao (Thai limeade)',$dr,40,167,0.1,10.0,9.0,0.0,0.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bua Loy (glutinous rice balls in coconut)',$sc,165,690,2.0,28.0,14.0,5.5,4.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── CAMBODIA (20) ──
			// ══════════════════════════════════════
			['Fish Amok (Khmer steamed curry)',$fs,125,523,10.0,5.0,2.0,7.5,5.0,0.5,0.8, 1,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Lok Lak (stir-fried beef, pepper-lime)',$mp,155,649,16.0,6.0,2.0,7.5,2.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Bai Sach Chrouk (pork & rice, breakfast)',$tk,175,732,10.0,24.0,1.0,4.5,1.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Kuy Teav (Khmer noodle soup)',$tk,80,335,5.0,10.0,0.5,2.0,0.5,0.5,1.2, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Samlor Korko (Khmer stirring soup)',$tk,50,209,3.0,6.0,1.5,1.5,0.3,2.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Samlor Machu (sour soup)',$tk,40,167,3.5,4.0,1.5,1.0,0.2,1.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Nom Banh Chok (Khmer noodles, green curry)',$tk,95,397,3.5,15.0,1.0,2.5,1.5,1.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Prahok Ktiss (prahok dip with pork)',$co,130,544,10.0,5.0,2.0,8.0,2.5,0.5,3.5, 1,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cha Kroeung Sach Ko (lemongrass beef)',$mp,160,669,16.0,5.0,2.0,8.5,2.5,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pleah Sach Ko (Khmer raw beef salad)',$mp,135,565,16.0,5.0,2.5,6.0,2.0,1.0,1.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ang Dtray-Meuk (grilled squid)',$fs,115,481,16.0,3.0,1.0,4.5,0.8,0.0,1.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Cha Traop Dot (stuffed aubergine)',$fv,85,356,4.5,6.0,2.0,5.0,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Num Pang (Khmer baguette sandwich)',$tk,250,1046,10.0,30.0,3.0,10.0,2.0,1.5,1.2, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Kampot Pepper Crab',$fs,125,523,12.0,5.0,1.0,6.5,1.0,0.5,1.5, 0,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Trey Aing (grilled fish, Cambodian)',$fs,110,460,20.0,1.0,0.5,3.0,0.5,0.0,1.0, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Nom Krok (Khmer coconut cake)',$sc,185,774,2.5,20.0,8.0,10.5,8.0,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Num Ansorm (Khmer sticky rice cake)',$sc,195,816,3.0,36.0,5.0,4.5,2.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cha Houy Teuk (Khmer jelly dessert)',$sc,85,356,0.5,20.0,16.0,0.5,0.3,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tuk-a-Loc (Cambodian fruit shake)',$dr,95,397,2.0,16.0,14.0,2.5,1.5,0.5,0.0, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Kampot Pepper (whole, dried)',$co,255,1067,10.0,64.0,0.6,3.3,1.0,26.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── VIETNAM (30) ──
			// ══════════════════════════════════════
			// -- Soups & Noodles --
			['Pho Bo (beef pho)',$tk,55,230,4.5,6.0,0.5,1.5,0.5,0.3,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pho Ga (chicken pho)',$tk,50,209,4.0,6.0,0.5,1.0,0.3,0.3,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bun Bo Hue (spicy beef noodle soup)',$tk,70,293,5.5,7.0,0.5,2.5,0.8,0.5,1.0, 1,1,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Bun Rieu (crab & tomato noodle soup)',$tk,60,251,4.5,6.0,1.5,2.0,0.4,0.5,0.8, 0,1,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Bun Cha (grilled pork & noodles, Hanoi)',$tk,140,586,10.0,16.0,3.0,4.5,1.5,0.5,1.5, 1,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cao Lau (Hoi An noodles)',$tk,155,649,8.0,20.0,1.0,5.0,1.5,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Mi Quang (turmeric noodles, Da Nang)',$tk,145,607,7.5,18.0,1.0,5.0,1.0,1.0,1.0, 0,1,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Hu Tieu (Southern pork noodle soup)',$tk,65,272,4.0,8.5,0.5,1.5,0.5,0.3,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			// -- Main Dishes --
			['Banh Mi (classic pork)',$tk,240,1004,12.0,28.0,5.0,9.0,2.0,1.5,1.2, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Com Tam (broken rice with pork chop)',$tk,175,732,10.0,24.0,1.0,5.0,1.5,0.5,1.5, 1,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Bo Luc Lac (shaking beef)',$mp,185,774,18.0,5.0,2.0,10.5,3.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Ca Kho To (caramelised fish in clay pot)',$fs,155,649,16.0,8.0,6.0,6.5,1.5,0.0,2.0, 1,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Thit Kho (caramelised pork belly)',$mp,250,1046,14.0,8.0,6.5,18.0,6.5,0.0,1.5, 0,0,0,1,0,0,1,0, 0,0,0,0,0,0],
			['Bo Kho (Vietnamese beef stew)',$tk,100,418,8.0,6.0,2.0,5.0,2.0,1.0,0.6, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Ga Nuong (Vietnamese roast chicken)',$mp,175,732,22.0,3.0,2.0,8.0,2.0,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Canh Chua (sour fish soup, southern)',$tk,50,209,5.0,5.0,2.0,1.5,0.3,1.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Rolls & Wraps --
			['Goi Cuon (fresh spring rolls, 2 pcs)',$tk,85,356,5.0,13.0,1.0,1.0,0.2,1.0,0.8, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Cha Gio (fried spring rolls, 2 pcs)',$tk,210,879,5.5,18.0,1.0,13.5,2.5,0.5,0.6, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Banh Cuon (steamed rice crepes)',$tk,115,481,5.0,16.0,0.5,3.5,0.8,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bo La Lot (grilled beef in betel leaf)',$mp,195,816,15.0,3.0,1.0,14.0,5.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			// -- Snacks --
			['Banh Xeo (Vietnamese sizzling crepe)',$tk,200,837,6.0,18.0,1.5,12.0,4.0,1.5,0.8, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Banh Khot (mini crispy pancakes)',$tk,185,774,4.5,18.0,1.0,10.5,6.0,0.5,0.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Xoi (sticky rice with toppings)',$bc,185,774,5.0,32.0,2.0,4.0,1.0,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Condiments --
			['Nuoc Cham (fish dipping sauce)',$co,40,167,2.5,7.5,6.0,0.0,0.0,0.0,6.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Nuoc Mam (fish sauce)',$co,35,146,5.0,3.5,0.0,0.0,0.0,0.0,23.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			// -- Sweets & Drinks --
			['Che Ba Mau (three-colour dessert)',$sc,120,502,2.5,22.0,14.0,2.5,1.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Banh Flan (Vietnamese crème caramel)',$sc,145,607,4.0,20.0,18.0,5.5,2.5,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Ca Phe Sua Da (Vietnamese iced coffee)',$dr,75,314,1.5,14.0,13.0,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Sinh To Bo (avocado smoothie)',$dr,115,481,1.5,12.0,10.0,7.0,2.0,1.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Tra Da (Vietnamese iced tea)',$dr,1,4,0.0,0.2,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
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
				'source_notes'=>'FAO SMILING / USDA FDC. Seeded v48.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 48 );
	}

	/** Seed v49: Malaysian, Singaporean, Filipino, Indonesian & Bruneian foods. */
	public static function seed_v49(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 49 ) { return; }
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
			// ── MALAYSIA (25) ──
			// ══════════════════════════════════════
			['Nasi Lemak (coconut rice, full set)',$tk,195,816,7.5,24.0,2.0,8.0,3.5,1.5,1.0, 1,1,0,1,1,0,0,0, 0,0,1,0,0,0],
			['Char Kway Teow (Penang fried noodles)',$tk,175,732,7.0,20.0,2.0,8.0,2.0,0.5,2.0, 0,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Laksa Lemak (Nyonya curry laksa)',$tk,100,418,5.5,8.5,1.5,5.5,3.5,0.5,1.2, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Assam Laksa (Penang sour laksa)',$tk,85,356,5.0,10.0,2.0,3.0,0.5,0.5,1.5, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rendang Daging (Malaysian dry beef curry)',$mp,200,837,18.0,5.0,2.0,12.0,7.5,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Satay (Malaysian, chicken, 10 sticks)',$mp,185,774,16.0,8.0,5.0,10.5,3.0,0.5,0.8, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Roti Canai (Malaysian flatbread)',$bc,300,1255,7.0,38.0,2.0,13.5,5.0,1.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Roti Telur (egg roti)',$bc,330,1381,9.0,36.0,2.0,16.5,6.0,1.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Mee Goreng Mamak (Indian-Malay fried noodles)',$tk,180,753,7.0,22.0,3.0,7.5,1.5,1.0,2.0, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Nasi Kandar (rice with mixed curries)',$tk,210,879,10.0,24.0,2.0,8.5,3.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hokkien Mee (KL-style dark noodles)',$tk,165,690,8.5,18.0,2.0,7.0,2.0,0.5,2.5, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Bak Kut Teh (pork rib herbal soup)',$tk,85,356,7.5,2.0,0.5,5.5,2.0,0.3,0.8, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Curry Mee (curry noodle soup)',$tk,115,481,6.0,10.0,1.5,6.5,4.0,0.5,1.5, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Ayam Percik (flame-grilled chicken)',$mp,175,732,20.0,5.0,3.0,8.5,4.0,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ikan Bakar (charcoal grilled fish)',$fs,130,544,18.0,3.0,2.0,5.5,1.0,0.0,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rojak (fruit & veg salad, shrimp paste)',$fv,115,481,3.0,14.0,10.0,5.5,1.0,2.0,1.5, 0,1,0,0,1,0,0,0, 0,0,1,0,1,1],
			['Cendol (shaved ice dessert)',$sc,130,544,1.0,28.0,20.0,3.0,2.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kuih Lapis (layered steamed cake)',$sc,270,1130,2.0,40.0,25.0,11.5,8.0,0.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Apam Balik (peanut pancake turnover)',$sc,295,1234,6.0,40.0,18.0,13.0,3.5,2.0,0.3, 0,0,0,1,1,1,0,0, 0,0,1,1,0,1],
			['Ondeh-Ondeh (palm sugar glutinous balls)',$sc,210,879,2.0,38.0,18.0,5.5,4.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Teh Tarik (pulled milk tea)',$dr,65,272,1.5,12.0,11.5,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kopi-O (Malaysian black coffee, sweet)',$dr,30,126,0.2,7.5,7.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sirap Bandung (rose syrup milk)',$dr,75,314,1.5,16.0,15.5,1.0,0.6,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Air Mata Kucing (longan drink)',$dr,45,188,0.2,11.0,10.0,0.0,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nasi Goreng Kampung (village fried rice)',$tk,180,753,6.5,24.0,1.5,6.5,1.5,1.0,1.5, 1,0,0,1,0,0,1,0, 0,0,1,0,0,0],

			// ══════════════════════════════════════
			// ── SINGAPORE (15) ──
			// ══════════════════════════════════════
			['Chilli Crab (Singapore)',$fs,145,607,11.0,10.0,6.0,7.0,1.0,0.5,2.0, 0,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Black Pepper Crab',$fs,140,586,12.0,6.0,2.0,7.5,1.5,0.5,1.5, 0,1,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Hainanese Chicken Rice (Singapore)',$tk,160,669,10.5,22.0,0.5,3.5,0.8,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Laksa (Singapore curry)',$tk,105,439,6.0,9.0,1.5,5.5,3.5,0.5,1.2, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaya Toast (with soft-boiled eggs)',$bc,250,1046,7.0,32.0,12.0,10.5,5.5,0.5,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Carrot Cake (chai tow kway, fried)',$tk,165,690,4.0,18.0,1.5,9.0,1.5,0.5,1.5, 0,0,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Bak Chor Mee (minced pork noodles)',$tk,155,649,8.0,18.0,1.5,6.0,1.5,0.5,2.0, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Wanton Mee (Singapore, dry)',$tk,160,669,7.5,20.0,2.0,5.5,1.5,0.5,2.0, 0,1,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Nasi Biryani (Singapore Indian)',$tk,185,774,10.0,22.0,1.0,6.5,2.0,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mee Siam (spicy vermicelli)',$tk,145,607,5.0,20.0,3.0,5.0,1.0,0.5,1.5, 0,1,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Fish Head Curry (Singapore)',$tk,105,439,8.5,5.0,2.0,6.0,3.0,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Oyster Omelette (or luak)',$tk,185,774,8.0,14.0,0.5,11.0,2.5,0.3,1.5, 1,1,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Popiah (fresh spring roll, Singaporean)',$tk,95,397,3.5,14.0,2.0,2.5,0.4,1.5,0.8, 0,1,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Ice Kachang (ABC, shaved ice)',$sc,120,502,2.0,26.0,20.0,1.5,1.0,1.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kopi (Singapore coffee, sweet)',$dr,55,230,1.5,8.0,7.5,2.0,1.2,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── PHILIPPINES (25) ──
			// ══════════════════════════════════════
			['Adobo (chicken, Filipino national dish)',$mp,175,732,16.0,3.0,1.0,11.0,3.0,0.0,2.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Adobo (pork)',$mp,210,879,14.0,3.0,1.0,16.0,5.5,0.0,2.0, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
			['Sinigang na Baboy (sour pork soup)',$tk,55,230,4.0,4.5,2.0,2.5,0.8,1.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Sinigang na Hipon (sour prawn soup)',$tk,45,188,4.5,4.0,2.0,1.5,0.3,1.0,0.8, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kare-Kare (oxtail peanut stew)',$tk,135,565,8.0,8.0,2.0,8.5,2.5,1.5,1.0, 0,1,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Lechon (whole roast pig, per 100g)',$mp,270,1130,22.0,0.5,0.0,20.0,7.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Sisig (sizzling pork)',$mp,235,983,15.0,5.0,1.0,18.0,6.5,0.0,1.5, 0,0,0,1,0,0,1,0, 0,0,0,0,0,0],
			['Lumpia Shanghai (fried spring rolls, 5 pcs)',$tk,270,1130,8.0,22.0,1.0,17.0,3.5,0.5,0.8, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Lumpia Sariwa (fresh spring roll)',$tk,95,397,3.5,14.0,3.0,2.5,0.4,1.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pancit Canton (stir-fried noodles)',$tk,160,669,6.5,22.0,2.0,5.5,1.0,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Pancit Bihon (rice noodles stir-fried)',$tk,145,607,5.0,22.0,1.5,4.0,0.8,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Tinola (ginger chicken soup)',$tk,60,251,6.0,3.5,0.5,2.5,0.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Caldereta (beef stew, Filipino)',$tk,125,523,9.0,6.0,2.0,7.5,2.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Bistek Tagalog (Filipino beef steak)',$mp,170,711,16.0,5.0,2.0,9.5,3.5,0.5,2.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Mechado (tomato beef stew)',$tk,115,481,9.0,5.0,2.5,6.5,2.5,0.5,0.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Laing (taro leaves in coconut)',$fv,105,439,4.0,5.0,1.5,8.0,5.5,3.0,0.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pinakbet (mixed vegetable stew)',$fv,60,251,3.0,6.0,2.0,2.5,0.5,2.5,1.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bibingka (rice cake)',$sc,265,1109,4.5,40.0,15.0,10.0,5.5,0.5,0.3, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Puto (steamed rice cake)',$sc,205,858,3.5,38.0,12.0,4.0,2.0,0.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Leche Flan (Filipino caramel custard)',$sc,255,1067,5.5,32.0,30.0,12.0,6.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Halo-Halo (mixed shaved ice dessert)',$sc,130,544,2.5,26.0,20.0,3.0,1.5,1.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Ube Halaya (purple yam jam)',$sc,210,879,1.5,42.0,28.0,4.5,2.5,1.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Ensaymada (Filipino brioche)',$bc,345,1443,6.5,42.0,12.0,16.5,8.5,1.0,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Taho (silken tofu with syrup)',$sc,85,356,3.5,15.0,10.0,1.5,0.2,0.3,0.0, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Calamansi Juice (Filipino limeade)',$dr,35,146,0.2,8.5,7.5,0.0,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── INDONESIA (25) ──
			// ══════════════════════════════════════
			['Nasi Goreng (Indonesian fried rice)',$tk,175,732,6.5,24.0,2.0,6.0,1.0,1.0,1.5, 0,1,0,1,0,0,1,0, 0,0,1,0,0,0],
			['Rendang Padang (West Sumatran beef)',$mp,205,858,18.0,5.0,2.0,13.0,8.0,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Satay Ayam (Indonesian chicken, peanut sauce)',$mp,190,795,15.0,8.5,5.0,11.0,2.5,0.5,0.8, 0,0,0,0,1,0,1,0, 0,0,1,0,0,0],
			['Soto Ayam (Indonesian chicken soup)',$tk,60,251,5.0,4.5,0.5,2.5,0.5,0.5,0.8, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Soto Betawi (Jakarta milk-based soup)',$tk,95,397,6.0,4.0,1.0,6.5,3.5,0.3,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Rawon (black nut beef soup)',$tk,90,377,7.5,3.5,0.5,5.5,2.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Gado-Gado (Indonesian peanut salad)',$fv,165,690,7.0,12.0,5.0,10.5,2.0,3.0,0.5, 0,0,0,0,1,0,1,0, 0,0,1,1,1,1],
			['Bakso (Indonesian meatball soup)',$tk,75,314,5.0,8.0,0.5,2.5,0.8,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,1,0,0,0],
			['Mie Goreng (Indonesian fried noodles)',$tk,180,753,6.0,24.0,2.5,7.0,1.5,1.0,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Bakmi Goreng (Chinese-Indonesian noodles)',$tk,175,732,7.0,22.0,2.0,6.5,1.5,0.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Nasi Uduk (coconut steamed rice)',$bc,185,774,3.5,28.0,0.5,6.5,4.5,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nasi Padang (Padang rice plate, avg)',$tk,215,900,10.0,22.0,1.5,10.0,4.0,0.5,0.8, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Ayam Goreng (Indonesian fried chicken)',$mp,245,1025,18.0,8.0,1.0,15.5,4.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pecel (Javanese peanut veg salad)',$fv,145,607,5.5,10.0,3.0,9.5,1.5,3.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Gudeg (Javanese jackfruit stew)',$fv,125,523,3.0,16.0,10.0,5.5,3.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tempeh Goreng (fried tempeh)',$lp,225,941,18.0,10.0,1.0,13.5,2.5,5.0,0.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],
			['Martabak Manis (sweet thick pancake)',$sc,345,1443,6.0,42.0,22.0,17.0,7.5,1.0,0.3, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Martabak Telur (savoury stuffed pancake)',$tk,270,1130,10.0,22.0,2.0,16.0,5.0,1.0,0.8, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Klepon (pandan glutinous balls)',$sc,215,900,2.0,40.0,18.0,5.0,3.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Es Teler (mixed fruit ice drink)',$dr,95,397,1.5,18.0,15.0,2.5,1.5,1.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Es Cendol (Indonesian iced cendol)',$sc,135,565,1.0,28.0,20.0,3.0,2.5,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bandrek (ginger spice drink)',$dr,45,188,0.2,11.0,10.0,0.2,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Teh Botol (Indonesian sweet bottled tea)',$dr,30,126,0.0,7.5,7.5,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sambal Terasi (shrimp paste chilli)',$co,85,356,2.5,8.0,4.0,5.0,0.5,1.5,3.0, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kecap Manis (sweet soy sauce)',$co,250,1046,4.0,55.0,48.0,0.5,0.1,0.5,6.0, 0,0,0,0,0,1,1,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── BRUNEI (10) ──
			// ══════════════════════════════════════
			['Ambuyat (sago starch staple)',$bc,130,544,0.5,32.0,0.5,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nasi Katok (Brunei rice & fried chicken)',$tk,235,983,12.0,28.0,1.0,8.5,2.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kelupis (glutinous rice in palm leaves)',$bc,195,816,3.5,38.0,1.0,3.5,2.0,1.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kuih Cincin (ring-shaped doughnut)',$sc,380,1590,4.0,50.0,15.0,18.5,4.0,0.5,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Soto Brunei (local chicken soup)',$tk,65,272,5.5,4.5,0.5,3.0,0.8,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Rendang Bruneian (drier style)',$mp,210,879,18.5,5.0,2.0,13.5,8.0,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Daging Masak Lada Hitam (black pepper beef)',$mp,165,690,15.0,5.0,1.5,9.5,3.5,0.5,1.0, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Puding Di Raja (Brunei royal pudding)',$sc,280,1172,4.0,38.0,28.0,13.0,8.5,0.5,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Pengat Pisang (banana in coconut milk)',$sc,145,607,1.0,26.0,18.0,4.5,3.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Teh C Special (Brunei layered tea)',$dr,70,293,1.5,12.0,11.5,2.0,1.2,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
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
				'source_notes'=>'MyFCD Malaysia / USDA FDC. Seeded v49.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 49 );
	}

	/** Seed v50: Pacific Island foods — PNG, Solomon Islands, Nauru, Tonga, Tuvalu, Tokelau. */
	public static function seed_v50(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 50 ) { return; }
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
			// ── PAPUA NEW GUINEA (20) ──
			// ══════════════════════════════════════
			['Mumu (PNG earth oven, mixed, per 100g)',$tk,115,481,7.5,12.0,2.0,4.0,1.5,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kaukau (PNG sweet potato, roasted)',$fv,105,439,1.5,24.0,6.5,0.2,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sago (PNG, processed starch)',$bc,355,1485,0.2,88.0,0.0,0.1,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Sago Pancake (PNG)',$bc,175,732,0.5,42.0,0.5,0.2,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tulip (canned meat, PNG staple)',$mp,260,1088,12.0,4.0,1.0,22.0,9.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Buai (betel nut, chewed)',$sc,55,230,1.0,10.0,2.0,1.5,0.5,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,0,0],
			['Kokoda (PNG raw fish in coconut)',$fs,115,481,12.0,4.0,2.0,6.0,4.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Taro (PNG, boiled)',$fv,115,481,0.5,27.0,0.5,0.1,0.0,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Banana (cooking, PNG, boiled)',$fv,115,481,1.0,30.0,3.0,0.2,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Aibika (PNG spinach-like greens)',$fv,25,105,3.0,3.0,0.5,0.3,0.1,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Galip Nut (Canarium, PNG)',$ns,580,2427,8.0,10.0,2.0,57.0,8.0,5.0,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Lamb Flap (PNG imported, grilled)',$mp,315,1318,12.0,0.0,0.0,30.0,14.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Coconut Cream (fresh, PNG)',$fo,195,816,2.0,3.0,2.0,20.0,17.5,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Breadfruit (PNG, roasted)',$fv,110,460,1.5,28.0,5.0,0.3,0.1,4.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Yam (PNG, boiled)',$fv,115,481,1.5,27.5,0.5,0.2,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Tapiok (cassava, boiled, PNG)',$fv,155,649,1.0,38.0,1.5,0.3,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Smoked Fish (PNG river fish)',$fs,165,690,28.0,0.0,0.0,5.5,1.0,0.0,2.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Tinned Fish and Rice (PNG common meal)',$tk,145,607,7.5,20.0,0.5,4.0,0.8,0.5,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kumu (PNG pumpkin tips, cooked)',$fv,20,84,2.5,2.5,0.5,0.3,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Pitpit (wild sugarcane shoot, PNG)',$fv,25,105,1.5,5.0,1.0,0.2,0.0,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],

			// ══════════════════════════════════════
			// ── SOLOMON ISLANDS (10) ──
			// ══════════════════════════════════════
			['Poi (Solomon Islands taro pudding)',$fv,135,565,1.0,32.0,5.0,0.5,0.2,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kumara (SI sweet potato, baked)',$fv,100,418,1.5,23.0,6.0,0.2,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Cassava Pudding (Solomon Islands)',$sc,165,690,1.0,35.0,12.0,3.5,2.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ngali Nut (Canarium, Solomon Islands)',$ns,570,2385,7.5,10.0,2.0,56.0,8.0,5.0,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Fish in Coconut Cream (SI)',$fs,135,565,14.0,3.0,1.5,7.5,5.5,0.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Slippery Cabbage (SI, cooked in coconut)',$fv,55,230,2.5,3.5,0.5,3.5,2.5,1.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Banana Pudding (SI, baked)',$sc,140,586,1.0,28.0,12.0,3.5,2.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tuna Loin (SI, grilled, fresh)',$fs,130,544,28.0,0.0,0.0,1.5,0.3,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Simboro (SI leaf-wrapped rice)',$bc,150,628,3.0,28.0,0.5,3.0,2.0,1.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Coconut Toddy (SI, fresh)',$dr,35,146,0.3,8.0,7.0,0.2,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── NAURU (6) ──
			// ══════════════════════════════════════
			['Coconut Fish (Nauruan, raw in lime)',$fs,105,439,13.0,3.0,1.5,5.0,3.5,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Pandanus Fruit Paste (Nauruan)',$fv,135,565,1.0,32.0,15.0,1.5,0.3,5.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Fried Breadfruit (Nauruan)',$fv,175,732,1.5,25.0,4.0,8.0,2.0,4.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Toddy Syrup (Nauruan, from coconut)',$co,280,1172,0.5,70.0,65.0,0.2,0.1,0.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baked Taro (Nauruan style)',$fv,120,502,1.0,28.0,0.5,0.2,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Grilled Reef Fish (Nauruan)',$fs,100,418,20.0,0.0,0.0,2.0,0.4,0.0,0.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],

			// ══════════════════════════════════════
			// ── TONGA (10) ──
			// ══════════════════════════════════════
			['Lu Pulu (corned beef in taro leaves)',$tk,145,607,8.0,4.0,0.5,11.0,5.5,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Lu Sipi (lamb in taro leaves)',$tk,140,586,9.0,3.5,0.5,10.5,5.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ota Ika (Tongan raw fish salad)',$fs,95,397,12.0,4.0,2.0,4.0,3.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Topai (Tongan dumplings in coconut)',$sc,185,774,2.5,30.0,10.0,6.5,5.0,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Feke (Tongan octopus in coconut)',$fs,110,460,14.0,4.0,1.5,4.5,3.0,0.0,0.5, 1,1,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Umu Feast (Tongan earth oven, avg)',$tk,120,502,8.0,12.0,2.0,4.5,2.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Vai Siaine (banana in coconut cream)',$sc,130,544,1.0,24.0,14.0,4.0,3.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kapisi Pulu (cabbage & corned beef)',$tk,85,356,5.0,4.5,1.5,5.5,2.5,1.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Keke (Tongan deep-fried dough)',$sc,350,1464,5.0,42.0,10.0,18.0,4.0,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Otai (Tongan fruit coconut drink)',$dr,75,314,0.5,14.0,12.0,2.5,2.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── TUVALU (6) ──
			// ══════════════════════════════════════
			['Pulaka (swamp taro, Tuvalu, boiled)',$fv,125,523,1.0,30.0,0.5,0.2,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Fakkai (Tuvalu coconut & taro dessert)',$sc,160,669,1.5,28.0,12.0,5.0,4.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ika Mata (Tuvalu raw fish in coconut)',$fs,100,418,12.0,3.5,1.5,5.0,3.5,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Pani Popo (Tuvalu coconut bread rolls)',$bc,285,1193,6.0,38.0,15.0,12.5,8.5,1.0,0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Fried Flying Fish (Tuvalu)',$fs,185,774,18.0,6.0,0.5,10.0,1.5,0.5,0.5, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Toddy (Tuvalu coconut sap drink)',$dr,40,167,0.3,9.5,8.5,0.2,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── TOKELAU (6) ──
			// ══════════════════════════════════════
			['Coconut Crab (Tokelau, boiled)',$fs,125,523,15.0,1.0,0.0,7.0,1.5,0.0,1.0, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Takihi (Tokelau breadfruit & coconut)',$fv,145,607,1.5,22.0,5.0,6.0,5.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kanava (Tokelau clam, raw)',$fs,75,314,12.0,3.5,0.0,1.0,0.2,0.0,1.0, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Poke (Tokelau, fermented breadfruit)',$fv,95,397,0.8,22.0,3.0,0.5,0.2,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Aku (Tokelau skipjack tuna, grilled)',$fs,130,544,28.0,0.0,0.0,1.5,0.4,0.0,0.3, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Karewe (Tokelau fermented toddy)',$dr,45,188,0.3,10.0,9.0,0.2,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],
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
				'source_notes'=>'FAO Pacific / USDA FDC. Seeded v50.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 50 );
	}

	/** Seed v51: Vanuatu, New Caledonia, Fiji, Wallis & Futuna, Samoa, Niue, Cook Islands, Norfolk Island. */
	public static function seed_v51(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 51 ) { return; }
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
			// ── VANUATU (10) ──
			// ══════════════════════════════════════
			['Laplap (Vanuatu national dish, taro/banana)',$fv,145,607,2.0,28.0,3.0,3.5,2.5,2.0,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Nalot (pounded breadfruit & coconut)',$fv,160,669,1.5,24.0,5.0,7.0,5.5,3.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tuluk (meat-filled laplap roll)',$tk,155,649,6.0,22.0,2.0,5.0,2.5,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Simboro (Vanuatu leaf-wrapped banana)',$fv,130,544,1.0,28.0,8.0,3.0,2.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kava (Vanuatu, traditional drink)',$dr,10,42,0.0,1.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lap Lap Banana (sweet variety)',$sc,155,649,1.0,30.0,10.0,4.0,3.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Island Cabbage (Vanuatu, in coconut)',$fv,50,209,2.5,3.5,0.5,3.0,2.5,1.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Navara (Vanuatu wild yam, baked)',$fv,120,502,1.5,28.0,1.0,0.2,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Nangai Nut (Vanuatu Canarium)',$ns,565,2364,7.5,11.0,2.0,55.0,8.0,5.5,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1],
			['Coconut Crab (Vanuatu, steamed)',$fs,120,502,15.0,1.0,0.0,6.5,1.5,0.0,1.0, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],

			// ══════════════════════════════════════
			// ── NEW CALEDONIA (8) ──
			// ══════════════════════════════════════
			['Bougna (Kanak earth-oven stew)',$tk,120,502,6.5,14.0,2.0,4.5,3.0,2.0,0.3, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bougna Végétarien (taro & yam in coconut)',$fv,130,544,2.0,22.0,2.5,4.5,3.5,2.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Poisson Cru (NC raw fish in lime & coconut)',$fs,110,460,13.0,4.0,2.0,5.5,4.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Cerf Rôti (NC venison roast)',$mp,150,628,28.0,0.0,0.0,4.0,1.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Taro Gratiné (NC taro gratin, French-style)',$fv,140,586,3.5,18.0,1.0,6.5,4.0,1.5,0.4, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Crabe de Cocotier (NC coconut crab)',$fs,125,523,15.0,1.5,0.0,7.0,1.5,0.0,1.0, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Soupe de Poisson Calédonienne',$tk,55,230,5.5,3.5,0.5,2.0,0.4,0.3,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pain Coco (NC coconut bread)',$bc,290,1213,5.0,38.0,10.0,13.5,10.0,1.0,0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── FIJI (12) ──
			// ══════════════════════════════════════
			['Kokoda (Fijian raw fish in coconut)',$fs,115,481,12.0,4.0,2.0,6.5,4.5,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Lovo Feast (Fijian earth oven, avg)',$tk,120,502,8.0,12.0,2.0,4.5,2.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Palusami (taro leaves & coconut cream)',$fv,110,460,3.0,6.0,1.5,8.5,7.0,2.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rourou (Fijian taro leaf curry)',$fv,80,335,3.5,5.0,1.0,5.5,4.0,2.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cassava Cake (Fijian, sweet)',$sc,185,774,1.5,32.0,14.0,6.0,4.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Vakalolo (Fijian coconut cassava dessert)',$sc,175,732,1.0,30.0,14.0,6.0,5.0,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Duruka (Fijian wild sugarcane flower)',$fv,20,84,1.5,3.5,0.5,0.2,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Nama (Fijian sea grapes)',$fv,12,50,1.0,1.5,0.0,0.1,0.0,0.5,2.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],
			['Fiji Butter Chicken',$tk,145,607,12.0,6.0,2.5,8.5,4.5,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Roti (Fiji Indian flatbread)',$bc,255,1067,7.5,44.0,1.5,5.0,0.8,3.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Dhal (Fiji Indian lentil)',$lp,95,397,5.5,13.0,1.0,2.5,0.4,2.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Yaqona / Kava (Fijian ceremony drink)',$dr,10,42,0.0,1.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── WALLIS & FUTUNA (5) ──
			// ══════════════════════════════════════
			['Umu (Wallis earth-oven pork & taro)',$tk,125,523,8.0,12.0,1.5,5.0,2.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Poisson Cru Wallisien (coconut fish)',$fs,110,460,13.0,4.0,2.0,5.5,4.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Lu (Wallisian taro leaf & coconut)',$fv,105,439,3.0,5.5,1.0,8.0,6.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Talo (Wallisian pounded taro)',$fv,130,544,1.0,30.0,0.5,0.3,0.1,2.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Kava (Wallis & Futuna)',$dr,10,42,0.0,1.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── SAMOA (10) ──
			// ══════════════════════════════════════
			['Oka I\'a (Samoan raw fish salad)',$fs,100,418,13.0,4.0,2.0,4.0,3.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Palusami (Samoan, corned beef & coconut)',$tk,155,649,6.0,5.0,1.0,13.0,9.0,1.5,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Sapasui (Samoan chop suey)',$tk,110,460,6.5,14.0,1.5,3.5,1.0,0.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Fa\'alifu Fa\'i (Samoan banana in coconut)',$sc,140,586,1.0,24.0,12.0,5.0,4.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Koko Samoa (Samoan cocoa drink)',$dr,65,272,1.5,8.0,5.0,3.5,2.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Panipopo (Samoan coconut bun)',$bc,280,1172,5.5,38.0,15.0,12.0,8.5,1.0,0.4, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Suafa\'i (Samoan banana soup)',$sc,115,481,1.0,22.0,12.0,3.5,2.5,1.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pisupo (Samoan corned beef, canned)',$mp,210,879,14.0,1.0,0.5,17.0,7.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Umu Feast (Samoan earth oven, avg)',$tk,125,523,8.0,12.0,2.0,5.0,2.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ava (Samoan kava ceremony drink)',$dr,10,42,0.0,1.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── NIUE (5) ──
			// ══════════════════════════════════════
			['Uga (Niuean coconut crab)',$fs,120,502,15.0,1.0,0.0,6.5,1.5,0.0,1.0, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Takihi (Niuean green banana & taro in coconut)',$fv,135,565,1.5,22.0,3.0,5.0,4.0,2.5,0.1, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Luku (Niuean fermented coconut cream)',$fo,245,1025,2.0,5.0,3.0,25.0,21.0,0.0,0.3, 0,0,0,0,0,0,0,0, 1,0,1,1,1,1],
			['Poke (Niuean fermented breadfruit)',$fv,95,397,0.8,22.0,3.0,0.5,0.2,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ika Mata (Niuean raw fish in coconut)',$fs,105,439,13.0,3.5,1.5,5.0,3.5,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],

			// ══════════════════════════════════════
			// ── COOK ISLANDS (8) ──
			// ══════════════════════════════════════
			['Ika Mata (Cook Islands raw fish)',$fs,100,418,13.0,4.0,2.0,4.5,3.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Rukau (Cook Islands taro leaf in coconut)',$fv,95,397,3.0,5.5,1.0,7.0,5.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Umu Kai (Cook Islands earth oven, avg)',$tk,120,502,7.5,12.0,2.0,4.5,2.0,1.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Poke (Cook Islands fermented breadfruit)',$fv,90,377,0.8,21.0,2.5,0.5,0.2,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mitiore (Cook Islands coconut sauce)',$co,195,816,2.0,5.0,3.0,19.0,16.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mamoe Tunu (Cook Islands roast lamb)',$mp,230,962,22.0,0.0,0.0,15.5,7.0,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ariki Banana Cake (Cook Islands)',$sc,285,1193,3.5,42.0,22.0,12.5,7.0,2.0,0.2, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Tumunu (Cook Islands homebrew, orange)',$dr,50,209,0.2,8.0,7.0,0.0,0.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,0,0,1,1],

			// ══════════════════════════════════════
			// ── NORFOLK ISLAND (5) ──
			// ══════════════════════════════════════
			['Pilhi (Norfolk Island banana & sweet potato dish)',$fv,125,523,1.0,26.0,8.0,2.5,1.5,2.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Hilli Duff (Norfolk Island steamed pudding)',$sc,285,1193,4.0,45.0,22.0,10.5,6.0,2.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Mudda (Norfolk fish & kumara dish)',$fs,110,460,10.0,12.0,1.5,3.5,0.8,1.5,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Norfolk Passionfruit Pie',$sc,260,1088,3.5,35.0,20.0,12.5,6.5,1.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Bunya Pine Nut (Norfolk, roasted)',$ns,185,774,3.5,36.0,6.0,2.5,0.3,3.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
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
				'source_notes'=>'FAO Pacific / USDA FDC. Seeded v51.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 51 );
	}

	/** Seed v52: Caribbean foods — Cuba, Jamaica, Haiti, Puerto Rico, Barbados, etc. */
	public static function seed_v52(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 52 ) { return; }
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
			// ── CUBA (12) ──
			// ══════════════════════════════════════
			['Ropa Vieja (Cuban shredded beef)',$mp,155,649,16.0,5.0,2.0,8.0,2.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Moros y Cristianos (black beans & rice)',$lp,145,607,5.0,24.0,0.5,2.5,0.5,4.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lechón Asado (Cuban roast pork)',$mp,245,1025,24.0,2.0,1.0,15.5,5.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cubano Sandwich (pressed)',$tk,310,1297,18.0,26.0,2.0,15.0,5.5,0.5,1.8, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Picadillo (Cuban ground beef hash)',$mp,145,607,12.0,8.0,3.0,7.5,2.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Vaca Frita (crispy fried beef)',$mp,220,920,20.0,4.0,1.0,14.0,5.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tostones (twice-fried green plantain)',$fv,175,732,1.0,30.0,1.0,6.5,1.5,2.0,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Yuca con Mojo (cassava with garlic sauce)',$fv,155,649,1.0,32.0,1.5,3.5,0.5,1.5,0.3, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Croquetas de Jamón (Cuban ham croquettes)',$tk,265,1109,8.0,20.0,1.0,17.0,5.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Flan Cubano (coconut flan)',$sc,195,816,4.0,28.0,24.0,7.5,4.5,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Cafecito (Cuban espresso, sweetened)',$dr,25,105,0.2,6.0,6.0,0.1,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mojito (Cuban cocktail, per glass)',$dr,145,607,0.1,12.0,10.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── JAMAICA (10) ──
			['Jerk Pork',$mp,210,879,20.0,5.0,3.0,12.0,4.5,0.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ackee and Saltfish (national dish)',$tk,190,795,11.0,3.5,0.5,15.0,3.5,2.5,1.5, 1,0,0,0,0,0,0,0, 1,0,1,0,0,0],
			['Curry Goat (Jamaican)',$tk,200,837,18.0,5.0,1.0,12.0,4.5,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Oxtail Stew (Jamaican)',$tk,165,690,14.0,6.0,1.5,10.0,4.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bammy (cassava flatbread)',$bc,175,732,1.0,40.0,1.0,1.0,0.2,2.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Festival (Jamaican fried dumpling)',$sc,310,1297,3.5,42.0,8.0,14.5,3.0,1.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Escovitch Fish (fried, pickled)',$fs,195,816,15.0,10.0,3.0,11.0,2.0,0.5,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Jamaican Patty (beef)',$tk,280,1172,8.0,28.0,2.0,15.0,5.5,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Sorrel Drink (Jamaican hibiscus)',$dr,45,188,0.2,11.0,10.0,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rum Punch (Jamaican, per glass)',$dr,175,732,0.1,18.0,16.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── HAITI (8) ──
			['Griot (Haitian fried pork)',$mp,285,1193,18.0,5.0,1.0,21.5,7.5,0.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Diri Djon Djon (black mushroom rice)',$bc,160,669,3.5,28.0,0.5,3.5,0.5,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Poul ak Nwa (chicken in cashew sauce)',$tk,165,690,14.0,6.0,2.0,9.5,2.0,1.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Soup Joumou (Haitian pumpkin soup, Jan 1st)',$tk,65,272,3.5,8.0,2.0,2.5,0.8,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pikliz (Haitian spicy slaw)',$fv,25,105,0.5,5.0,2.5,0.5,0.1,2.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bannann Peze (Haitian tostones)',$fv,170,711,1.0,30.0,1.0,6.0,1.5,2.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Akra (Haitian malanga fritters)',$fv,245,1025,3.0,22.0,1.0,16.5,3.0,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kremas (Haitian coconut cream drink)',$dr,165,690,2.0,22.0,20.0,5.5,4.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── DOMINICAN REPUBLIC (8) ──
			['La Bandera (rice, beans & meat, national)',$tk,165,690,8.5,22.0,1.0,5.0,1.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mangú (mashed green plantain)',$fv,130,544,1.5,30.0,2.0,2.0,0.5,2.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Sancocho (Dominican hearty stew)',$tk,95,397,6.0,10.0,1.5,3.5,1.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chimichurri Burger (Dominican street)',$tk,290,1213,13.0,26.0,3.0,15.0,5.0,1.0,1.2, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Mofongo (mashed fried plantain)',$fv,220,920,5.0,30.0,2.0,9.5,2.5,2.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Locrio de Pollo (Dominican chicken rice)',$tk,160,669,9.0,22.0,1.0,4.5,1.0,0.5,0.6, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Habichuelas con Dulce (sweet bean cream)',$sc,145,607,3.5,24.0,15.0,4.5,2.5,1.5,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Morir Soñando (milk & orange drink)',$dr,85,356,2.0,15.0,14.0,2.0,1.2,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── PUERTO RICO (8) ──
			['Mofongo (Puerto Rican, with shrimp)',$tk,235,983,8.0,28.0,2.0,11.0,3.0,2.0,1.0, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pernil (PR slow-roasted pork shoulder)',$mp,235,983,24.0,2.0,1.0,14.5,5.0,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Arroz con Gandules (rice & pigeon peas)',$tk,150,628,4.5,24.0,0.5,3.5,0.8,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Alcapurrias (fried plantain & meat fritter)',$tk,270,1130,7.0,26.0,1.0,15.5,3.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pastelón (PR plantain lasagne)',$tk,175,732,8.0,18.0,5.0,8.5,3.5,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Tembleque (coconut pudding)',$sc,145,607,1.0,24.0,16.0,5.5,4.5,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Mallorca (PR sweet bread)',$bc,330,1381,7.0,48.0,15.0,12.5,5.5,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Piña Colada (per glass)',$dr,195,816,0.5,28.0,26.0,7.5,6.0,0.3,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── BAHAMAS (5) ──
			['Conch Salad (Bahamian, raw)',$fs,85,356,14.0,5.0,1.5,1.0,0.2,0.5,0.8, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Conch Fritters (Bahamian)',$fs,235,983,8.0,22.0,1.0,13.5,2.5,0.5,0.8, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Peas and Rice (Bahamian)',$lp,150,628,5.0,24.0,1.0,3.0,0.8,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cracked Conch (fried)',$fs,240,1004,10.0,18.0,0.5,14.0,2.5,0.5,0.8, 1,1,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Guava Duff (Bahamian steamed pudding)',$sc,270,1130,3.5,40.0,22.0,11.0,6.0,2.0,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── BARBADOS (6) ──
			['Cou-Cou and Flying Fish (Bajan national)',$tk,135,565,10.0,18.0,0.5,3.5,0.5,2.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Macaroni Pie (Bajan)',$tk,250,1046,9.0,26.0,3.0,12.5,6.5,0.5,0.7, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Pudding and Souse (Bajan)',$mp,175,732,12.0,18.0,2.0,5.5,1.5,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Fish Cakes (Bajan, saltfish fritters)',$fs,260,1088,10.0,20.0,1.0,16.0,2.5,1.0,1.0, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Bajan Pepper Sauce',$co,45,188,0.5,10.0,8.0,0.5,0.1,1.5,2.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Rum Punch (Bajan)',$dr,180,753,0.1,18.0,16.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── GRENADA (5) ──
			['Oil Down (Grenadian national dish)',$tk,135,565,7.0,14.0,1.5,6.0,4.0,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Callaloo Soup (Grenadian)',$tk,55,230,3.5,5.5,1.0,2.5,1.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lambie Souse (conch, Grenada)',$fs,90,377,12.0,5.0,1.5,2.5,0.5,0.5,1.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Nutmeg Ice Cream (Grenada)',$sc,210,879,3.5,24.0,20.0,11.5,7.0,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Spice Cake (Grenada, nutmeg & cinnamon)',$sc,340,1423,4.5,48.0,28.0,15.0,5.0,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── DOMINICA & ST LUCIA (6) ──
			['Mountain Chicken (Dominica, frog legs)',$mp,75,314,16.5,0.0,0.0,0.5,0.1,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Callaloo Soup (Dominican, dasheen)',$tk,50,209,3.0,5.5,1.0,2.0,0.8,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Bouillon (Dominica, thick veg & meat soup)',$tk,80,335,5.0,8.0,1.0,3.0,1.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Green Fig and Saltfish (St Lucian national)',$tk,130,544,8.0,18.0,1.0,3.5,0.5,2.5,1.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Bouyon (St Lucian hearty soup)',$tk,75,314,4.5,8.5,1.0,2.5,0.8,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Cocoa Tea (St Lucian hot chocolate)',$dr,80,335,2.0,12.0,10.0,3.0,2.0,0.5,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── ARUBA, CAYMAN, TURKS & CAICOS, BVI, MONTSERRAT (10) ──
			['Keshi Yena (Aruban stuffed cheese)',$tk,210,879,12.0,8.0,3.0,14.0,7.0,0.5,1.0, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Pan Bati (Aruban cornmeal pancake)',$bc,195,816,4.0,28.0,3.0,7.5,1.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Sopi di Giambo (Aruban okra soup)',$tk,50,209,3.0,5.5,1.0,2.0,0.5,2.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pastechi (Aruban meat pastry)',$tk,285,1193,8.5,26.0,1.5,16.5,5.0,1.0,0.7, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Turtle Stew (Cayman traditional)',$tk,95,397,12.0,4.0,0.5,3.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Cayman-style Beef (slow-cooked)',$mp,155,649,16.0,5.0,1.5,8.0,3.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Conch Chowder (Turks & Caicos)',$tk,75,314,5.5,8.0,1.0,2.5,0.5,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Peas n\' Hominy (TCI, pigeon peas & corn)',$lp,110,460,4.0,18.0,1.0,2.5,0.5,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Fish and Fungi (BVI, cornmeal & okra)',$tk,130,544,8.0,18.0,1.0,3.0,0.5,2.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Goat Water (Montserrat, national dish)',$tk,110,460,9.0,5.0,1.0,6.0,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
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
				'source_notes'=>'CFNI Caribbean / USDA FDC. Seeded v52.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 52 );
	}

	/** Seed v53: Caucasus & Central Asian foods — Georgia, Azerbaijan, Kazakhstan, Uzbekistan, Turkmenistan, Kyrgyzstan, Tajikistan. */
	public static function seed_v53(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 53 ) { return; }
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
			// ── GEORGIA (15) ──
			// ══════════════════════════════════════
			['Khachapuri Adjaruli (cheese boat bread)',$bc,290,1213,10.5,28.0,2.0,15.0,8.0,0.5,1.0, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Khachapuri Imeruli (cheese flatbread)',$bc,270,1130,10.0,28.0,2.0,13.0,7.0,0.5,0.9, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Khinkali (Georgian soup dumplings, 1pc)',$tk,65,272,3.5,7.5,0.3,2.0,0.8,0.3,0.3, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Lobio (Georgian bean stew)',$lp,105,439,5.5,14.0,1.0,3.0,0.5,4.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Satsivi (walnut chicken sauce)',$tk,165,690,12.0,5.0,1.5,11.5,1.5,1.5,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Mtsvadi (Georgian shashlik/kebab)',$mp,210,879,22.0,1.0,0.5,13.0,5.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pkhali (walnut & vegetable pâté)',$fv,120,502,4.5,6.0,1.5,9.0,1.0,3.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Badrijani Nigvzit (aubergine walnut rolls)',$fv,145,607,3.5,5.0,2.0,12.5,1.5,2.0,0.4, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Chakhokhbili (chicken tomato stew)',$tk,110,460,12.0,4.0,2.5,5.5,1.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ostri (Georgian spicy beef stew)',$tk,120,502,10.0,5.0,2.5,6.5,2.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Churchkhela (walnut grape candy)',$sc,390,1632,6.5,52.0,40.0,17.0,1.5,3.0,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Shotis Puri (Georgian clay oven bread)',$bc,265,1109,8.0,52.0,2.0,1.5,0.3,2.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Tkemali (sour plum sauce)',$co,55,230,0.5,12.0,8.0,0.5,0.1,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Adjika (Georgian chilli paste)',$co,60,251,2.0,8.0,4.0,2.5,0.3,3.0,3.5, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Georgian Wine (Saperavi, per glass 150ml)',$dr,110,460,0.0,3.5,1.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── AZERBAIJAN (12) ──
			// ══════════════════════════════════════
			['Plov (Azerbaijani, lamb & saffron)',$tk,185,774,8.5,24.0,2.0,6.5,2.0,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shah Plov (pastry-wrapped pilaf)',$tk,215,900,8.0,28.0,3.0,8.5,3.0,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Dolma (Azerbaijani, vine leaves & lamb)',$tk,195,816,8.0,12.0,2.0,13.0,5.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Qutab (thin flatbread, meat or herb)',$bc,250,1046,7.0,26.0,1.0,13.0,3.5,1.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Dushbara (tiny lamb dumplings in broth)',$tk,75,314,4.5,7.0,0.3,3.0,1.0,0.3,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Piti (Azerbaijani lamb shank soup)',$tk,95,397,7.0,5.0,0.5,5.5,2.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lyulya Kebab (Azerbaijani minced lamb)',$mp,255,1067,16.0,2.0,0.5,20.5,9.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lavangi (stuffed chicken with walnuts)',$mp,185,774,18.0,5.0,2.0,10.5,2.5,1.0,0.5, 0,0,0,0,1,0,0,0, 0,0,1,0,0,0],
			['Dovga (yoghurt & herb soup)',$tk,35,146,2.0,3.5,2.0,1.5,0.8,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pakhlava (Azerbaijani baklava)',$sc,420,1757,6.5,45.0,32.0,24.0,5.0,2.5,0.1, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Shekerbura (crescent pastry, nut-filled)',$sc,395,1653,6.0,48.0,25.0,20.0,4.0,2.0,0.1, 0,0,1,0,1,1,0,0, 0,0,1,1,0,1],
			['Sherbet (Azerbaijani fruit drink)',$dr,40,167,0.1,10.0,9.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── KAZAKHSTAN (10) ──
			// ══════════════════════════════════════
			['Beshbarmak (national dish, meat & noodles)',$tk,175,732,12.0,16.0,0.5,8.0,3.0,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kazy (horse meat sausage)',$mp,340,1423,16.0,0.0,0.0,30.5,12.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kurt (dried fermented cheese balls)',$de,350,1464,25.0,12.0,5.0,22.0,14.0,0.0,4.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Baursak (fried dough, Kazakh)',$sc,340,1423,6.0,42.0,3.0,17.0,3.5,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Lagman (Kazakh pulled noodle soup)',$tk,90,377,5.0,10.0,1.5,3.0,1.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kuurdak (fried organ meat & potato)',$tk,165,690,12.0,8.0,0.5,10.0,4.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shubat (fermented camel milk)',$dr,55,230,2.5,5.0,4.5,2.5,1.5,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kumiss (fermented mare\'s milk)',$dr,50,209,2.0,5.0,4.5,1.5,1.0,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Nauryz Kozhe (7-ingredient festival soup)',$tk,65,272,3.5,8.0,1.0,2.0,0.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Tandyr Nan (Kazakh clay oven bread)',$bc,275,1151,8.0,52.0,2.0,4.0,0.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── UZBEKISTAN (12) ──
			// ══════════════════════════════════════
			['Plov (Uzbek, national dish, lamb & rice)',$tk,190,795,9.0,22.0,2.0,7.5,2.0,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Shashlik (Uzbek, marinated lamb)',$mp,215,900,20.0,2.0,1.0,14.0,6.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Manti (Uzbek, steamed dumplings, 1pc)',$tk,70,293,3.5,7.5,0.3,2.5,1.0,0.3,0.3, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Samsa (Uzbek, lamb in pastry, 1pc)',$tk,285,1193,9.0,24.0,1.0,17.5,7.0,0.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Lagman (Uzbek, pulled noodle stew)',$tk,95,397,5.5,10.0,1.5,3.5,1.0,1.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Norin (cold horsemeat & noodles)',$tk,155,649,12.0,16.0,0.5,5.0,1.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Dimlama (Uzbek layered vegetable & meat)',$tk,95,397,6.5,7.0,1.5,4.5,1.5,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Naryn (hand-pulled noodles with horse)',$tk,150,628,11.0,16.0,0.5,4.5,1.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Obi Non (Uzbek round bread)',$bc,270,1130,8.5,50.0,2.0,3.5,0.5,2.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Chuchvara (tiny Uzbek dumplings in broth)',$tk,70,293,4.0,7.0,0.3,2.5,0.8,0.3,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Halvaitar (Uzbek flour halva)',$sc,440,1841,5.0,52.0,35.0,24.0,5.0,1.0,0.0, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Green Tea (Uzbek, unsweetened)',$dr,1,4,0.1,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ══════════════════════════════════════
			// ── TURKMENISTAN (8) ──
			// ══════════════════════════════════════
			['Plov (Turkmen, lamb & carrot)',$tk,185,774,8.5,22.0,2.0,7.5,2.0,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Dograma (Turkmen bread & meat soup)',$tk,100,418,7.0,10.0,0.5,3.5,1.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ishlekli (Turkmen meat pie)',$tk,265,1109,10.0,24.0,1.0,14.5,5.5,0.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Gutap (Turkmen stuffed flatbread, herb)',$bc,240,1004,5.0,28.0,1.0,12.5,3.0,2.0,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Shurpa (Turkmen lamb soup)',$tk,60,251,4.5,4.0,1.0,3.0,1.2,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Chorek (Turkmen flatbread)',$bc,275,1151,8.0,52.0,2.0,4.0,0.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Chal (fermented camel milk, Turkmen)',$dr,50,209,2.5,5.0,4.5,2.0,1.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Gara Ash (Turkmen thick herb soup)',$tk,70,293,3.5,9.0,0.5,2.5,0.5,2.0,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── KYRGYZSTAN (10) ──
			// ══════════════════════════════════════
			['Beshbarmak (Kyrgyz, mutton & noodles)',$tk,180,753,13.0,16.0,0.5,8.5,3.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Kuurdak (Kyrgyz fried lamb & offal)',$mp,185,774,14.0,5.0,0.5,12.5,5.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Oromo (Kyrgyz steamed meat roll)',$tk,175,732,8.0,18.0,0.5,8.0,3.0,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ashlyam-Fu (cold Kyrgyz noodle dish)',$tk,85,356,3.5,12.0,1.0,2.5,0.5,1.0,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Samsa (Kyrgyz, baked meat pastry)',$tk,280,1172,9.0,24.0,1.0,16.5,6.5,0.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Shorpo (Kyrgyz lamb broth)',$tk,55,230,4.5,3.5,0.5,2.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kumiss (Kyrgyz fermented mare milk)',$dr,48,201,2.0,5.0,4.5,1.5,0.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Boorsok (Kyrgyz fried dough)',$sc,340,1423,6.0,42.0,3.0,17.0,3.5,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Maksym (Kyrgyz fermented grain drink)',$dr,30,126,0.5,6.5,2.0,0.2,0.0,0.5,0.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Lepyoshka (Kyrgyz round bread)',$bc,280,1172,8.5,50.0,2.0,5.0,0.8,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ══════════════════════════════════════
			// ── TAJIKISTAN (8) ──
			// ══════════════════════════════════════
			['Qurutob (Tajik national dish, bread & yoghurt)',$tk,135,565,5.5,14.0,2.0,6.5,3.5,1.0,0.8, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Oshi Palav (Tajik pilaf, lamb)',$tk,190,795,9.0,24.0,2.0,7.0,2.0,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sambusa (Tajik baked pastry, meat)',$tk,275,1151,8.5,24.0,1.0,16.5,6.0,0.5,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Tuhum Barak (Tajik egg dumplings)',$tk,170,711,6.5,18.0,0.5,8.0,2.5,0.5,0.4, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Shurbo (Tajik mutton & veg soup)',$tk,65,272,5.0,4.5,1.0,3.0,1.0,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mastoba (Tajik rice & meat soup)',$tk,75,314,4.5,8.0,0.5,2.5,0.8,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Non (Tajik round flatbread)',$bc,270,1130,8.5,50.0,2.0,3.5,0.5,2.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Shirchoy (Tajik milk tea with butter)',$dr,55,230,1.5,3.0,2.5,4.0,2.5,0.0,0.3, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
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
				'source_notes'=>'USDA FDC / FAO CENTAL. Seeded v53.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 53 );
	}

	/** Seed v54: Mongolia + Northern/Eastern/Southeastern European foods. */
	public static function seed_v54(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 54 ) { return; }
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
			// ── MONGOLIA (10) ──
			// ══════════════════════════════════════
			['Buuz (Mongolian steamed dumplings, 1pc)',$tk,65,272,3.5,7.0,0.3,2.5,1.0,0.3,0.3, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Khuushuur (Mongolian fried meat pastry)',$tk,280,1172,10.0,24.0,0.5,16.0,5.5,0.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Tsuivan (Mongolian stir-fried noodles & meat)',$tk,155,649,8.0,16.0,1.0,7.0,2.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Khorkhog (Mongolian hot stone BBQ mutton)',$mp,230,962,20.0,2.0,0.5,16.0,7.0,0.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Boodog (marmot/goat cooked with hot stones)',$mp,215,900,22.0,0.0,0.0,14.0,5.5,0.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Guriltai Shul (Mongolian noodle soup)',$tk,70,293,4.5,8.0,0.5,2.0,0.8,0.3,0.5, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Airag (fermented mare\'s milk, Mongolian)',$dr,48,201,2.0,5.0,4.0,1.5,0.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Suutei Tsai (Mongolian salt milk tea)',$dr,40,167,1.5,2.5,2.0,2.5,1.5,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Aaruul (Mongolian dried curd)',$de,350,1464,30.0,20.0,8.0,16.0,10.0,0.0,2.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Boortsog (Mongolian fried biscuit)',$sc,345,1443,6.0,42.0,3.0,17.5,3.5,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── FINLAND (8) ──
			['Karjalanpiirakka (Karelian pie, 1pc)',$bc,220,920,5.0,38.0,1.0,4.5,2.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kalakukko (fish-filled bread)',$tk,200,837,10.0,28.0,1.0,5.5,1.0,1.5,0.8, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Lohikeitto (Finnish salmon soup)',$tk,85,356,5.5,5.0,0.5,5.0,2.5,0.5,0.4, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Poronkäristys (sautéed reindeer)',$mp,130,544,22.0,2.0,0.5,4.0,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Hernekeitto (Finnish pea soup)',$lp,85,356,6.0,12.0,1.0,1.5,0.3,3.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Mustikkapiirakka (blueberry pie)',$sc,270,1130,3.5,38.0,18.0,12.0,5.5,2.5,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Korvapuusti (Finnish cinnamon roll)',$sc,340,1423,5.5,46.0,18.0,15.0,8.0,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Glögi (Finnish mulled wine/juice)',$dr,85,356,0.1,18.0,16.0,0.0,0.0,0.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],

			// ── ESTONIA (5) ──
			['Verivorst (Estonian blood sausage)',$mp,295,1234,12.0,20.0,0.5,19.0,7.5,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Mulgipuder (Estonian potato & barley mash)',$fv,110,460,3.0,18.0,1.0,3.0,1.5,2.5,0.3, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Kama (Estonian mixed grain flour dessert)',$sc,350,1464,12.0,60.0,8.0,6.0,1.0,8.0,0.0, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Kiluvõileib (sprat sandwich, Estonian)',$tk,215,900,8.0,22.0,1.0,10.5,2.0,1.0,1.5, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Kohuke (Estonian curd snack bar)',$de,350,1464,9.0,32.0,25.0,20.0,12.0,0.5,0.2, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── LATVIA (5) ──
			['Rupjmaizes Kārtojums (rye bread trifle)',$sc,240,1004,4.0,32.0,18.0,11.0,6.5,2.0,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Sklandrausis (Latvian carrot & potato pie)',$sc,195,816,3.0,30.0,10.0,7.5,2.5,2.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Pelēkie Zirņi ar Speķi (grey peas & bacon)',$lp,145,607,7.0,18.0,1.0,5.0,1.5,5.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Piragi (Latvian bacon buns, 1pc)',$bc,175,732,5.0,18.0,1.0,9.5,3.5,0.5,0.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Aukstā Zupa (Latvian cold beet soup)',$tk,40,167,2.0,5.0,3.0,1.0,0.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],

			// ── LITHUANIA (5) ──
			['Cepelinai (potato dumplings, national)',$tk,165,690,6.0,24.0,0.5,5.0,2.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Šaltibarščiai (cold pink beet soup)',$tk,40,167,2.0,5.0,3.0,1.0,0.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Kibinai (Karaite meat pastry, 1pc)',$tk,250,1046,8.0,22.0,0.5,14.5,5.5,0.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Šakotis (tree cake, Lithuanian)',$sc,390,1632,7.0,48.0,25.0,19.5,10.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Kepta Duona (fried rye bread with garlic)',$sc,350,1464,8.0,45.0,2.0,16.0,3.0,4.0,1.5, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],

			// ── BELARUS (5) ──
			['Draniki (Belarusian potato pancakes)',$fv,185,774,3.0,22.0,1.0,9.5,1.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Machanka (pork in sour cream sauce)',$tk,175,732,10.0,5.0,1.0,13.5,6.0,0.3,0.6, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Kolduny (Belarusian stuffed dumplings)',$tk,185,774,7.5,20.0,0.5,8.5,3.5,1.0,0.5, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],
			['Babka (Belarusian potato bake)',$fv,130,544,3.5,16.0,0.5,6.0,2.5,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Zhurek (Belarusian sour rye soup)',$tk,40,167,2.5,6.0,0.5,0.8,0.2,0.5,0.8, 0,0,0,1,0,1,0,0, 0,0,0,0,0,0],

			// ── UKRAINE (8) ──
			['Borscht (Ukrainian, with pampushky)',$tk,50,209,2.5,6.5,3.0,1.5,0.5,1.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Varenyky (Ukrainian, potato & cheese)',$tk,185,774,5.0,28.0,1.0,6.0,2.5,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Holubtsi (Ukrainian cabbage rolls)',$tk,100,418,5.5,10.0,2.0,4.0,1.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Salo (Ukrainian cured pork fat, with garlic)',$mp,775,3243,1.5,0.0,0.0,85.0,33.0,0.0,3.5, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0],
			['Deruny (Ukrainian potato fritters)',$fv,180,753,3.0,20.0,1.0,10.0,2.0,1.5,0.4, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Pampushky (garlic bread rolls)',$bc,275,1151,6.5,42.0,2.0,9.0,2.0,1.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,0,1],
			['Chicken Kyiv (Ukrainian original)',$mp,270,1130,16.0,12.0,0.5,18.0,9.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,0,0,0,0],
			['Uzvar (Ukrainian dried fruit compote)',$dr,35,146,0.2,8.5,7.5,0.0,0.0,0.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── CZECHIA (6) ──
			['Svíčková na Smetaně (sirloin in cream)',$tk,145,607,12.0,8.0,2.5,7.5,3.5,0.5,0.5, 0,0,1,0,0,1,0,0, 0,0,0,0,0,0],
			['Vepřo Knedlo Zelo (pork, dumplings, sauerkraut)',$tk,165,690,10.0,18.0,1.0,6.0,2.0,2.0,0.8, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Trdelník (chimney cake, Czech)',$sc,350,1464,5.0,52.0,18.0,14.0,5.0,1.0,0.3, 0,0,0,1,1,1,0,0, 0,0,1,1,0,1],
			['Bramboráky (Czech potato pancakes)',$fv,175,732,3.0,20.0,0.5,9.5,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Smažený Sýr (Czech fried cheese)',$de,330,1381,14.0,16.0,0.5,24.0,12.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Koláče (Czech fruit-filled pastry)',$sc,285,1193,5.0,42.0,18.0,11.0,4.5,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ── SLOVAKIA (5) ──
			['Bryndzové Halušky (sheep cheese dumplings)',$tk,195,816,7.5,22.0,1.0,9.0,4.5,1.0,1.0, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Kapustnica (Slovak sauerkraut soup)',$tk,65,272,4.0,4.5,1.0,3.5,1.2,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Zemiakové Placky (Slovak potato pancakes)',$fv,175,732,3.0,20.0,0.5,9.5,1.5,1.5,0.5, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Parenica (Slovak smoked cheese)',$de,310,1297,25.0,1.0,0.5,23.0,14.5,0.0,2.0, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1],
			['Šúľance (Slovak poppy seed noodles)',$sc,250,1046,5.5,38.0,10.0,8.5,2.5,2.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],

			// ── CROATIA (5) ──
			['Ćevapčići (Croatian grilled sausages)',$mp,250,1046,18.0,3.0,0.5,18.5,7.5,0.0,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Peka (Croatian bell-cooked meat & veg)',$tk,120,502,10.0,6.0,1.5,6.0,2.0,1.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Crni Rižot (Croatian squid ink risotto)',$tk,150,628,7.0,20.0,0.5,4.5,0.8,0.5,0.8, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Štrukli (Croatian cheese pastry)',$tk,225,941,8.0,22.0,2.0,12.0,6.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Fritule (Croatian doughnut balls)',$sc,330,1381,5.0,42.0,15.0,16.0,3.5,1.0,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],

			// ── SERBIA (5) ──
			['Ćevapi (Serbian, 10 pcs with lepinja)',$tk,265,1109,16.0,22.0,1.0,12.5,5.0,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pljeskavica (Serbian spiced meat patty)',$mp,275,1151,17.0,5.0,0.5,21.0,8.5,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sarma (Serbian stuffed cabbage rolls)',$tk,105,439,6.0,8.0,1.5,5.5,2.0,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Gibanica (Serbian cheese phyllo pie)',$tk,265,1109,10.0,20.0,2.0,16.5,9.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Ajvar (Serbian roasted pepper relish)',$co,65,272,1.0,6.5,5.0,4.0,0.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── ROMANIA (6) ──
			['Sarmale (Romanian cabbage rolls)',$tk,110,460,6.5,8.0,1.5,6.0,2.0,1.5,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Mici/Mititei (Romanian grilled sausages)',$mp,255,1067,16.0,3.0,0.5,20.0,8.0,0.5,1.2, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Ciorbă de Burtă (Romanian tripe soup)',$tk,55,230,4.5,3.0,0.5,3.0,1.0,0.3,0.8, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Mămăligă (Romanian polenta)',$bc,75,314,2.0,16.0,0.2,0.5,0.1,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Papanași (Romanian fried cheese doughnuts)',$sc,295,1234,8.0,32.0,12.0,15.0,8.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Cozonac (Romanian sweet bread)',$bc,340,1423,7.5,50.0,20.0,12.0,5.0,1.5,0.4, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],

			// ── MOLDOVA (4) ──
			['Mămăligă cu Brânză (polenta with cheese)',$bc,130,544,5.5,16.0,0.5,5.0,3.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Plăcinte (Moldovan filled flatbread)',$bc,250,1046,6.5,30.0,3.0,12.0,4.0,1.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Zeamă (Moldovan chicken noodle soup)',$tk,45,188,3.5,4.5,0.5,1.5,0.4,0.3,0.5, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Sărățele (Moldovan savoury biscuits)',$sc,420,1757,8.0,50.0,3.0,20.0,8.5,1.5,1.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],

			// ── BULGARIA (6) ──
			['Shopska Salata (Bulgarian national salad)',$fv,70,293,3.5,4.5,3.0,4.5,2.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Banitsa (Bulgarian cheese phyllo pie)',$tk,260,1088,8.0,22.0,1.5,16.0,8.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Kavarma (Bulgarian clay pot stew)',$tk,125,523,10.0,5.0,2.0,7.5,2.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Tarator (Bulgarian cold yoghurt soup)',$tk,40,167,1.5,3.0,2.0,2.5,1.0,0.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Kebapche (Bulgarian grilled meat roll)',$mp,250,1046,16.0,3.0,0.5,19.5,8.0,0.5,1.0, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Lukanka (Bulgarian dried sausage)',$mp,380,1590,25.0,2.0,0.5,30.0,12.0,0.0,3.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
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
				'source_notes'=>'USDA FDC / national sources. Seeded v54.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 54 );
	}

	/** Seed v55: Icelandic & Greenlandic foods. */
	public static function seed_v55(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 55 ) { return; }
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
			// ── ICELAND (20) ──
			// ══════════════════════════════════════
			['Hákarl (fermented shark)',$fs,115,481,22.0,0.0,0.0,3.0,0.5,0.0,3.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Harðfiskur (dried fish jerky)',$fs,340,1423,78.0,0.0,0.0,2.0,0.4,0.0,2.5, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Plokkfiskur (mashed fish & potato)',$tk,105,439,8.0,10.0,0.5,4.0,2.0,1.0,0.6, 1,0,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Hangikjöt (smoked lamb)',$mp,175,732,28.0,0.0,0.0,7.0,3.0,0.0,2.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Svið (singed sheep head, half)',$mp,195,816,22.0,0.0,0.0,12.0,5.0,0.0,0.5, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Kjötsúpa (Icelandic lamb soup)',$tk,55,230,4.5,4.5,1.0,2.0,0.8,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pylsur (Icelandic hot dog)',$tk,260,1088,9.5,24.0,3.0,14.0,5.0,1.0,1.5, 0,0,0,0,0,1,0,0, 0,0,0,0,0,0],
			['Rúgbrauð (Icelandic rye bread, hot spring)',$bc,220,920,5.5,42.0,8.0,1.0,0.2,6.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Flatkökur (Icelandic flatbread)',$bc,280,1172,7.5,50.0,2.0,5.0,1.5,3.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Skyr (traditional Icelandic)',$de,63,264,11.0,4.0,3.5,0.2,0.1,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Skyr Cake (Icelandic cheesecake)',$sc,230,962,8.0,28.0,18.0,10.0,6.0,0.0,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Kleinur (Icelandic twisted doughnut)',$sc,365,1527,6.0,45.0,15.0,18.0,5.0,0.5,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Pönnukökur (Icelandic thin pancakes)',$sc,215,900,6.0,28.0,8.0,9.0,4.0,0.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Brennivín (Icelandic caraway schnapps, 40ml)',$dr,95,397,0.0,0.0,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Harðfiskur with Butter',$fs,385,1611,55.0,0.5,0.0,18.0,10.5,0.0,2.0, 1,0,1,0,0,0,0,0, 1,0,1,0,0,0],
			['Icelandic Lamb Chop (grilled)',$mp,220,920,22.0,0.0,0.0,14.5,6.5,0.0,0.2, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0],
			['Hrútspungar (pickled ram testicles)',$mp,135,565,15.0,0.5,0.0,8.0,3.0,0.0,2.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Slátur (Icelandic blood pudding)',$mp,280,1172,12.0,18.0,1.0,18.0,7.0,1.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Lundi (puffin breast, smoked)',$mp,140,586,23.0,0.0,0.0,5.0,1.5,0.0,1.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Jolakaka (Icelandic Christmas cake)',$sc,330,1381,5.0,50.0,25.0,12.5,5.0,2.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],

			// ══════════════════════════════════════
			// ── GREENLAND (KALAALLIT NUNAAT) (15) ──
			// ══════════════════════════════════════
			['Suaasat (Greenlandic seal/caribou soup)',$tk,70,293,7.0,4.0,0.5,3.0,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Mattak (narwhal/whale skin, raw)',$mp,140,586,8.0,0.0,0.0,12.0,2.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Tuttu (caribou/reindeer, roasted)',$mp,145,607,27.0,0.0,0.0,4.0,1.5,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Ammassat (dried capelin fish)',$fs,310,1297,60.0,0.0,0.0,7.5,1.5,0.0,3.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Puisi Suaasat (seal meat soup)',$tk,75,314,8.0,3.5,0.5,3.5,1.0,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Kiviak (fermented auk, traditional)',$mp,185,774,20.0,0.0,0.0,11.5,3.5,0.0,2.0, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Arctic Char (grilled, Greenlandic)',$fs,145,607,21.0,0.0,0.0,6.5,1.0,0.0,0.2, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Dried Halibut (Greenlandic)',$fs,325,1360,72.0,0.0,0.0,3.0,0.5,0.0,3.0, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0],
			['Musk Ox (roasted)',$mp,135,565,25.0,0.0,0.0,3.5,1.0,0.0,0.2, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Crowberry (paarnaqat, raw)',$fv,40,167,0.5,9.0,5.0,0.5,0.0,3.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1],
			['Greenlandic Shrimp (boiled, cold-water)',$fs,90,377,19.0,0.0,0.0,1.2,0.2,0.0,1.5, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Kaffe Mik (Greenlandic coffee cocktail)',$dr,175,732,0.5,15.0,14.0,5.5,3.5,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Angelica (Greenlandic wild herb, candied)',$sc,280,1172,1.0,68.0,60.0,0.5,0.1,3.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Greenlandic Coffee (with whisky & cream)',$dr,185,774,0.5,14.0,13.0,6.5,4.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Whale Blubber (raw, traditional)',$mp,400,1674,3.0,0.0,0.0,43.0,7.0,0.0,0.5, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0],
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
				'source_notes'=>'Matís Iceland / USDA FDC. Seeded v55.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 55 );
	}

	/** Seed v56: Remaining Europe + Asia — Balkans, Armenia, Malta, Cyprus, Sri Lanka, Lebanon, Israel, Taiwan, etc. */
	public static function seed_v56(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 56 ) { return; }
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
			// ── ALBANIA (5) ──
			['Tavë Kosi (Albanian baked lamb & yoghurt)',$tk,155,649,12.0,5.0,2.0,10.0,4.5,0.3,0.5, 0,0,1,1,0,0,0,0, 0,0,1,0,0,0],
			['Byrek (Albanian phyllo pie, cheese)',$tk,255,1067,8.0,22.0,1.5,15.5,7.0,0.5,0.8, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Fërgesë (Albanian pepper & cheese bake)',$tk,135,565,6.5,5.0,3.0,10.0,5.5,1.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Qofte (Albanian grilled meatballs)',$mp,245,1025,15.0,5.0,0.5,18.5,7.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Trilece (Albanian three-milk cake)',$sc,215,900,4.5,28.0,24.0,10.0,6.0,0.0,0.2, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],

			// ── BOSNIA & HERZEGOVINA (5) ──
			['Ćevapi u Lepinja (Bosnian, with bread)',$tk,270,1130,16.0,22.0,1.0,13.0,5.5,0.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Burek (Bosnian, meat spiral pie)',$tk,270,1130,10.0,24.0,1.0,15.5,6.0,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Bosanski Lonac (Bosnian pot stew)',$tk,85,356,6.5,5.0,1.0,4.5,1.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Begova Čorba (bey\'s soup, chicken cream)',$tk,75,314,4.5,5.0,1.0,4.0,2.0,0.5,0.5, 0,0,1,0,0,1,0,0, 0,0,1,0,0,0],
			['Tufahije (Bosnian stuffed apple dessert)',$sc,195,816,2.5,32.0,25.0,7.5,1.0,1.5,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],

			// ── NORTH MACEDONIA (4) ──
			['Tavče Gravče (Macedonian baked beans)',$lp,110,460,5.5,16.0,1.5,2.5,0.4,5.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Pastrmajlija (Macedonian meat pizza)',$tk,255,1067,12.0,26.0,1.5,12.0,4.5,0.5,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Turli Tava (Macedonian mixed vegetable bake)',$fv,75,314,3.0,8.0,2.5,3.5,1.0,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pindjur (Macedonian roasted pepper spread)',$co,60,251,1.0,6.0,4.5,3.5,0.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── MONTENEGRO & KOSOVO & SLOVENIA (6) ──
			['Njeguški Steak (Montenegro stuffed veal)',$mp,270,1130,18.0,3.0,0.5,21.0,10.0,0.0,1.5, 0,0,1,0,0,0,0,0, 0,0,0,0,0,0],
			['Kačamak (Montenegrin polenta & cheese)',$bc,145,607,5.0,16.0,0.5,7.0,4.0,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Flia (Kosovo layered pancake pie)',$bc,285,1193,7.0,28.0,2.0,16.0,8.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Flija (Kosovo crepe cake with cream)',$sc,295,1234,6.5,28.0,3.0,17.5,9.5,0.5,0.5, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],
			['Potica (Slovenian nut roll)',$sc,380,1590,8.0,48.0,22.0,18.0,4.5,3.0,0.3, 0,0,1,1,1,1,0,0, 0,0,1,1,0,1],
			['Štruklji (Slovenian rolled dumplings)',$tk,195,816,6.5,26.0,4.0,7.5,3.5,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,0,0,1],

			// ── MALTA (5) ──
			['Pastizzi (Maltese ricotta pastry, 1pc)',$sc,280,1172,6.0,22.0,1.0,19.0,8.5,0.3,0.6, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Rabbit Stew (Stuffat tal-Fenek, Maltese)',$tk,130,544,14.0,4.0,1.5,6.5,1.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ftira (Maltese flatbread with toppings)',$bc,245,1025,7.0,32.0,2.5,10.0,2.0,1.5,1.0, 1,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Ħobż biż-Żejt (Maltese bread with oil & tomato)',$bc,225,941,5.5,28.0,3.0,10.0,1.5,1.5,1.0, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Imqaret (Maltese date diamonds)',$sc,350,1464,3.5,48.0,25.0,16.5,3.5,2.5,0.2, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],

			// ── CYPRUS (4) ──
			['Halloumi (Cypriot, grilled, traditional)',$de,325,1360,22.0,1.5,1.0,25.5,16.5,0.0,2.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1],
			['Souvla (Cypriot charcoal spit roast)',$mp,210,879,24.0,0.0,0.0,12.5,5.0,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Koupepia (Cypriot stuffed vine leaves)',$tk,190,795,5.0,12.0,2.0,14.0,3.5,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Commandaria (Cypriot dessert wine, 75ml)',$dr,110,460,0.0,14.0,13.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── ARMENIA (5) ──
			['Khorovats (Armenian grilled meat)',$mp,215,900,22.0,1.0,0.5,13.5,5.5,0.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Harissa (Armenian wheat & chicken porridge)',$tk,115,481,7.5,14.0,0.5,3.0,0.8,2.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Lahmajun (Armenian, thin meat flatbread)',$tk,230,962,9.5,28.0,3.0,9.0,3.5,1.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Gata (Armenian sweet bread)',$sc,360,1506,6.0,48.0,18.0,16.0,8.0,1.0,0.4, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Tahn (Armenian yoghurt drink)',$dr,30,126,1.5,2.5,2.5,1.0,0.6,0.0,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],

			// ── LEBANON (6) ──
			['Kibbeh Nayyeh (Lebanese raw lamb)',$mp,190,795,14.0,12.0,0.5,10.0,3.5,3.0,0.3, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Tabbouleh (Lebanese, traditional)',$fv,95,397,2.5,12.0,2.0,4.5,0.6,3.0,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Manakish Zaatar (Lebanese)',$bc,305,1276,7.5,38.0,1.5,13.5,2.0,3.0,0.8, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Kebbeh (fried, Lebanese)',$tk,285,1193,10.0,18.0,0.5,19.5,5.0,2.0,0.5, 0,0,0,0,1,1,0,0, 0,0,1,0,0,0],
			['Sfiha (Lebanese meat pie)',$tk,260,1088,10.0,26.0,2.0,13.0,4.5,1.0,0.7, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Knafeh (Lebanese, with cheese)',$sc,400,1674,7.5,42.0,28.0,22.5,10.5,0.5,0.5, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],

			// ── SYRIA & IRAQ (6) ──
			['Makdous (Syrian stuffed aubergine pickle)',$fv,185,774,2.5,6.0,2.0,17.0,2.5,2.5,2.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1],
			['Muhammara (Syrian walnut dip)',$co,215,900,3.5,14.0,7.0,16.5,2.0,3.0,0.5, 0,0,0,0,1,1,0,0, 0,0,1,1,1,1],
			['Masgouf (Iraqi grilled carp)',$fs,160,669,20.0,2.0,1.0,8.0,1.5,0.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Dolma (Iraqi, rice-stuffed vegetables)',$fv,130,544,3.0,16.0,2.5,6.0,1.0,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Kleicha (Iraqi date cookie)',$sc,380,1590,5.0,55.0,28.0,16.0,4.5,2.5,0.2, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Tepsi Baytinijan (Iraqi aubergine casserole)',$tk,110,460,6.0,7.0,3.0,7.0,2.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── ISRAEL (6) ──
			['Shakshuka (Israeli, classic)',$tk,118,494,7.0,8.0,5.0,7.0,1.5,2.0,0.7, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['Sabich (Iraqi-Israeli aubergine pitta)',$tk,265,1109,8.0,28.0,3.0,14.0,2.0,3.0,0.6, 0,0,0,1,0,1,0,0, 0,0,1,0,0,1],
			['Malabi (Israeli milk pudding, rose water)',$sc,125,523,3.5,18.0,14.0,4.5,3.0,0.0,0.1, 0,0,1,0,1,0,0,0, 0,0,1,1,0,1],
			['Jachnun (Yemeni-Israeli rolled pastry)',$bc,350,1464,6.5,42.0,3.0,18.0,10.0,1.0,0.4, 0,0,1,0,0,1,0,0, 0,0,1,1,0,1],
			['Rugelach (Israeli crescent pastry)',$sc,375,1569,5.0,45.0,22.0,20.0,10.0,1.5,0.3, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Limonana (Israeli frozen lemonade)',$dr,40,167,0.1,10.0,9.0,0.0,0.0,0.2,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],

			// ── SRI LANKA (6) ──
			['Rice and Curry (Sri Lankan, full plate avg)',$tk,155,649,5.5,22.0,2.0,5.0,2.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kottu Roti (Sri Lankan chopped roti)',$tk,195,816,8.0,22.0,2.0,8.5,2.5,1.0,1.0, 0,0,0,1,0,1,0,0, 0,0,1,0,0,0],
			['Hoppers (Sri Lankan rice bowl pancake)',$bc,155,649,3.0,26.0,1.5,4.5,3.0,0.5,0.3, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],
			['String Hoppers (idiyappam, Sri Lankan)',$bc,135,565,2.5,28.0,0.5,1.0,0.2,1.0,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Lamprais (Sri Lankan Dutch-Burgher rice)',$tk,210,879,9.0,26.0,1.5,8.0,3.0,1.5,0.6, 0,0,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Watalappam (Sri Lankan coconut custard)',$sc,195,816,4.0,28.0,22.0,8.0,5.5,0.5,0.1, 0,0,0,1,0,0,0,0, 0,0,1,1,0,1],

			// ── TAIWAN (6) ──
			['Beef Noodle Soup (Taiwanese)',$tk,95,397,6.5,8.0,1.0,4.5,1.5,0.5,1.5, 0,0,0,0,0,1,1,0, 0,0,0,0,0,0],
			['Gua Bao (Taiwanese pork belly bun)',$tk,245,1025,9.0,28.0,5.0,11.0,3.5,0.5,0.8, 0,0,0,0,1,1,1,0, 0,0,0,0,0,0],
			['Oyster Omelette (Taiwanese)',$tk,190,795,7.5,14.0,0.5,12.0,2.5,0.3,1.5, 1,1,0,1,0,0,0,0, 0,0,1,0,0,0],
			['Bubble Tea (Taiwanese original)',$dr,85,356,1.0,18.0,14.0,1.5,1.0,0.0,0.0, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Pineapple Cake (Taiwanese, 1pc)',$sc,350,1464,4.0,52.0,28.0,14.5,7.0,1.5,0.2, 0,0,1,1,0,1,0,0, 0,0,1,1,0,1],
			['Stinky Tofu (Taiwanese, fried)',$lp,200,837,10.0,12.0,0.5,13.0,2.0,1.5,1.5, 0,0,0,0,0,0,1,0, 0,0,1,1,1,1],

			// ── MALDIVES & TIMOR-LESTE (4) ──
			['Garudhiya (Maldivian tuna broth)',$tk,40,167,7.0,1.0,0.0,0.8,0.2,0.0,1.0, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Mas Huni (Maldivian tuna & coconut breakfast)',$fs,155,649,12.0,10.0,2.0,8.0,5.5,2.0,1.0, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Batar Da\'an (Timorese corn & bean stew)',$lp,95,397,4.0,15.0,1.0,2.0,0.3,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Ikan Sabuko (Timorese grilled fish)',$fs,110,460,20.0,1.0,0.5,3.0,0.5,0.0,0.5, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
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
				'source_notes'=>'USDA FDC / national sources. Seeded v56.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 56 );
	}

	/** Seed v57: Remaining Americas, Africa & Oceania. */
	public static function seed_v57(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 57 ) { return; }
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
			// ── CENTRAL AMERICA (14 — 2 per country) ──
			['Pupusa (Salvadoran, cheese & bean)',$tk,230,962,7.5,28.0,1.5,10.0,3.5,3.0,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],
			['Curtido (Salvadoran pickled cabbage)',$fv,15,63,0.5,3.0,1.5,0.1,0.0,1.5,1.5, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Baleada (Honduran bean & cheese tortilla)',$tk,265,1109,8.0,30.0,1.0,12.5,5.0,4.0,0.6, 0,0,1,0,0,1,0,0, 0,0,1,0,0,1],
			['Sopa de Caracol (Honduran conch soup)',$tk,70,293,5.0,6.0,1.0,3.0,1.5,0.5,0.7, 0,1,1,0,0,0,0,0, 0,0,1,0,0,0],
			['Pepián (Guatemalan spiced meat stew)',$tk,115,481,8.0,8.0,1.5,6.0,1.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kak\'ik (Guatemalan turkey soup)',$tk,65,272,6.0,4.0,1.0,3.0,0.8,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Vigorón (Nicaraguan pork rind & yuca salad)',$tk,195,816,8.0,22.0,2.0,8.5,3.0,2.0,0.8, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Nacatamal (Nicaraguan tamale)',$tk,225,941,7.5,24.0,2.0,11.5,3.5,2.0,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Gallo Pinto (Costa Rican rice & beans)',$lp,145,607,5.0,24.0,0.5,2.5,0.5,4.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Casado (Costa Rican set meal)',$tk,165,690,9.0,22.0,1.5,5.0,1.5,3.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Sancocho Panameño (Panamanian chicken soup)',$tk,65,272,5.0,6.0,1.0,2.5,0.5,1.0,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Hojaldras (Panamanian fried dough)',$sc,340,1423,5.0,40.0,3.0,18.0,4.0,0.5,0.3, 0,0,0,1,0,1,0,0, 0,0,1,1,0,1],
			['Rice and Beans (Belizean, coconut)',$lp,160,669,4.5,26.0,1.0,4.5,2.5,3.5,0.3, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Garnaches (Belizean fried tortilla, bean & cheese)',$tk,230,962,6.5,24.0,1.0,12.5,4.5,2.5,0.6, 0,0,1,0,0,0,0,0, 0,0,1,0,0,1],

			// ── TRINIDAD & TOBAGO, GUYANA, SURINAME (8) ──
			['Doubles (Trini, curried chickpea in bara)',$tk,245,1025,7.0,30.0,2.0,11.0,2.0,3.5,0.5, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Roti (Trini, with curry filling)',$tk,220,920,9.0,26.0,2.0,9.0,2.5,2.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Callaloo (Trinidadian, with crab)',$tk,65,272,4.5,4.0,0.5,3.5,1.0,2.0,0.5, 0,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pelau (Trini rice, meat & pigeon peas)',$tk,155,649,7.5,22.0,1.0,4.5,1.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Pepperpot (Guyanese, cassareep meat stew)',$tk,145,607,12.0,5.0,2.0,9.0,3.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Cook-up Rice (Guyanese one-pot)',$tk,155,649,6.5,22.0,0.5,4.5,2.0,3.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Roti (Surinamese, with curry)',$tk,215,900,8.5,26.0,2.0,8.5,2.5,2.0,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Pom (Surinamese, tayer root casserole)',$tk,140,586,6.0,14.0,2.0,7.0,2.0,1.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],

			// ── ECUADOR & PARAGUAY (6) ──
			['Encebollado (Ecuadorian tuna soup)',$tk,85,356,8.0,8.0,1.0,3.0,0.5,1.0,0.8, 1,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Llapingacho (Ecuadorian potato patty)',$fv,175,732,4.5,20.0,0.5,9.0,3.5,1.5,0.5, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1],
			['Ceviche de Camarón (Ecuadorian shrimp)',$fs,80,335,8.5,8.0,3.0,1.5,0.3,0.5,0.5, 0,1,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Sopa Paraguaya (Paraguayan corn bread)',$bc,250,1046,7.0,28.0,3.0,12.5,5.0,1.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Chipa (Paraguayan cheese bread)',$bc,310,1297,5.5,42.0,2.0,13.0,5.5,0.5,0.5, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1],
			['Tereré (Paraguayan cold yerba mate)',$dr,2,8,0.1,0.3,0.0,0.0,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1],

			// ── REMAINING CARIBBEAN (4) ──
			['Funchi (Curaçao cornmeal, like polenta)',$bc,75,314,2.0,16.0,0.2,0.5,0.1,1.5,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Roti (Antigua & Barbuda, curry chicken)',$tk,215,900,10.0,24.0,2.0,8.5,2.5,1.5,0.6, 0,0,0,0,0,1,0,0, 0,0,1,0,0,0],
			['Breadfruit Cou-Cou (St Vincent)',$fv,115,481,1.5,24.0,3.0,2.0,0.5,3.0,0.2, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Stew Chicken (St Kitts & Nevis)',$tk,125,523,12.0,4.0,1.5,6.5,1.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],

			// ── MADAGASCAR, MAURITIUS, SEYCHELLES, CAPE VERDE (8) ──
			['Romazava (Malagasy beef & greens stew)',$tk,75,314,6.0,4.0,0.5,4.0,1.5,2.0,0.4, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ravitoto (Malagasy pork & cassava leaf)',$tk,115,481,8.0,5.0,0.5,7.5,2.5,2.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Dholl Puri (Mauritian split pea flatbread)',$bc,230,962,6.5,32.0,1.0,8.5,1.5,3.5,0.4, 0,0,0,0,0,1,0,0, 0,0,1,1,1,1],
			['Mine Frite (Mauritian fried noodles)',$tk,170,711,6.5,22.0,2.0,6.5,1.5,0.5,1.5, 0,0,0,1,0,1,1,0, 0,0,1,0,0,0],
			['Octopus Curry (Seychellois, Kari Zourit)',$tk,100,418,12.0,5.0,1.5,4.0,1.5,1.0,0.6, 1,1,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Ladob (Seychellois banana & coconut dessert)',$sc,140,586,1.0,24.0,14.0,5.0,4.0,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Cachupa (Cape Verdean corn & bean stew)',$tk,100,418,5.5,14.0,1.0,2.5,0.5,3.5,0.5, 0,0,0,0,0,0,0,0, 0,0,0,0,0,0],
			['Pastel de Atum (Cape Verdean tuna pasty)',$tk,265,1109,9.0,26.0,1.0,14.0,2.5,1.0,0.7, 1,0,0,1,0,1,0,0, 0,0,1,0,0,0],

			// ── OCEANIA REMAINING (8) ──
			['Poisson Cru (Tahitian, French Polynesia)',$fs,110,460,13.0,4.0,2.0,5.5,4.0,0.5,0.5, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0],
			['Po\'e (Tahitian baked fruit pudding)',$sc,145,607,1.0,30.0,16.0,3.5,2.5,1.5,0.0, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Tinola (Palauan chicken ginger soup)',$tk,55,230,5.5,3.5,0.5,2.0,0.5,0.5,0.5, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Kelaguen (Guam, citrus-cooked chicken)',$mp,130,544,18.0,4.0,2.0,5.0,1.0,0.5,0.8, 0,0,0,0,0,0,0,0, 0,0,1,0,0,0],
			['Red Rice (Guam/Chamorro, achote)',$bc,145,607,3.0,26.0,0.5,3.0,0.5,0.5,0.4, 0,0,0,0,0,0,0,0, 0,0,1,1,1,1],
			['Poke Bowl (Hawaiian, tuna)',$tk,145,607,14.0,16.0,2.0,4.0,0.5,0.5,1.5, 1,0,0,0,0,0,1,0, 0,0,1,0,0,0],
			['Loco Moco (Hawaiian, rice, burger, egg, gravy)',$tk,195,816,10.0,22.0,1.0,7.5,2.5,0.5,0.8, 0,0,0,1,0,0,1,0, 0,0,0,0,0,0],
			['Spam Musubi (Hawaiian, 1pc)',$tk,175,732,6.5,26.0,2.0,4.5,1.5,0.3,1.5, 0,0,0,0,0,0,1,0, 0,0,0,0,0,0],
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
				'source_notes'=>'USDA FDC / FAO regional. Seeded v57.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 57 );
	}

	/** Seed v58: Populate serving_sizes for ~250 common foods. */
	public static function seed_v58(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 58 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── EGGS & DAIRY ──
			'egg-boiled'              => [['label'=>'1 egg','grams'=>60],['label'=>'2 eggs','grams'=>120]],
			'egg-fried'               => [['label'=>'1 egg','grams'=>60],['label'=>'2 eggs','grams'=>120]],
			'egg-poached'             => [['label'=>'1 egg','grams'=>60],['label'=>'2 eggs','grams'=>120]],
			'egg-scrambled'           => [['label'=>'1 egg','grams'=>60],['label'=>'2 eggs','grams'=>120]],
			'scrambled-eggs-with-butter' => [['label'=>'1 portion','grams'=>120],['label'=>'2 eggs','grams'=>120]],
			'omelette-plain-2-egg'    => [['label'=>'1 omelette','grams'=>120]],
			'cheddar-cheese'          => [['label'=>'1 slice','grams'=>20],['label'=>'1 oz','grams'=>28],['label'=>'1 matchbox piece','grams'=>30]],
			'mozzarella'              => [['label'=>'1 ball','grams'=>125],['label'=>'1 slice','grams'=>28]],
			'cream-cheese'            => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 oz','grams'=>28]],
			'butter-salted'           => [['label'=>'1 pat','grams'=>5],['label'=>'1 tablespoon','grams'=>14],['label'=>'1 knob','grams'=>10]],
			'whole-milk'              => [['label'=>'1 glass','grams'=>250],['label'=>'1 cup','grams'=>244],['label'=>'splash','grams'=>15]],
			'semi-skimmed-milk'       => [['label'=>'1 glass','grams'=>250],['label'=>'1 cup','grams'=>244]],
			'skimmed-milk'            => [['label'=>'1 glass','grams'=>250],['label'=>'1 cup','grams'=>244]],
			'greek-yoghurt'           => [['label'=>'1 pot','grams'=>150],['label'=>'1 tablespoon','grams'=>15]],
			'natural-yoghurt'         => [['label'=>'1 pot','grams'=>150],['label'=>'1 tablespoon','grams'=>15]],
			'double-cream'            => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 pouring jug','grams'=>50]],
			'single-cream'            => [['label'=>'1 tablespoon','grams'=>15]],
			'parmesan'                => [['label'=>'1 tablespoon grated','grams'=>10],['label'=>'1 oz','grams'=>28]],
			'brie'                    => [['label'=>'1 wedge','grams'=>30],['label'=>'1 oz','grams'=>28]],
			'stilton-blue'            => [['label'=>'1 portion','grams'=>30],['label'=>'1 oz','grams'=>28]],
			'paneer'                  => [['label'=>'1 portion','grams'=>75],['label'=>'1 block','grams'=>225]],
			'halloumi-grilled'        => [['label'=>'1 slice','grams'=>40],['label'=>'1 portion','grams'=>80]],
			'skyr-traditional-icelandic' => [['label'=>'1 pot','grams'=>150],['label'=>'1 cup','grams'=>170]],

			// ── BREAD & CEREALS ──
			'white-bread'             => [['label'=>'1 slice','grams'=>36],['label'=>'2 slices','grams'=>72]],
			'wholemeal-bread'         => [['label'=>'1 slice','grams'=>36],['label'=>'2 slices','grams'=>72]],
			'toast-white-with-butter' => [['label'=>'1 slice','grams'=>40],['label'=>'2 slices','grams'=>80]],
			'pitta-bread-white'       => [['label'=>'1 pitta','grams'=>60]],
			'naan-bread-plain'        => [['label'=>'1 naan','grams'=>130]],
			'chapati-wholemeal'       => [['label'=>'1 chapati','grams'=>50]],
			'roti-wholemeal-tawa'     => [['label'=>'1 roti','grams'=>40]],
			'tortilla-flour'          => [['label'=>'1 wrap','grams'=>45],['label'=>'1 large wrap','grams'=>65]],
			'baguette-traditional'    => [['label'=>'1 quarter','grams'=>65],['label'=>'1 half','grams'=>130]],
			'croissant'               => [['label'=>'1 croissant','grams'=>57]],
			'crumpet-with-butter'     => [['label'=>'1 crumpet','grams'=>55]],
			'bagel-plain'             => [['label'=>'1 bagel','grams'=>90]],
			'porridge-with-semi-skimmed-milk' => [['label'=>'1 bowl','grams'=>250]],
			'cornflakes'              => [['label'=>'1 bowl (30g)','grams'=>30],['label'=>'1 bowl with milk','grams'=>155]],
			'weetabix-per-biscuit-19g' => [['label'=>'1 biscuit','grams'=>19],['label'=>'2 biscuits','grams'=>38]],
			'granola'                 => [['label'=>'1 serving','grams'=>45],['label'=>'1 cup','grams'=>120]],

			// ── RICE, PASTA & GRAINS ──
			'cooked-white-rice'       => [['label'=>'1 cup','grams'=>185],['label'=>'1 bowl','grams'=>300],['label'=>'1 portion','grams'=>150]],
			'cooked-brown-rice'       => [['label'=>'1 cup','grams'=>185],['label'=>'1 bowl','grams'=>300]],
			'steamed-rice-jasmine'    => [['label'=>'1 cup','grams'=>185],['label'=>'1 bowl','grams'=>300]],
			'cooked-pasta-white'      => [['label'=>'1 cup','grams'=>140],['label'=>'1 portion','grams'=>180]],
			'cooked-pasta-wholemeal'  => [['label'=>'1 cup','grams'=>140],['label'=>'1 portion','grams'=>180]],
			'cooked-couscous'         => [['label'=>'1 cup','grams'=>160],['label'=>'1 portion','grams'=>150]],
			'cooked-quinoa'           => [['label'=>'1 cup','grams'=>185],['label'=>'1 portion','grams'=>150]],

			// ── OILS & FATS ──
			'olive-oil'               => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'coconut-oil'             => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'vegetable-oil'           => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'avocado-oil'             => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'sesame-oil-chamgireum'   => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'ghee-clarified-butter'   => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],

			// ── FRUIT ──
			'apple'                   => [['label'=>'1 medium apple','grams'=>150],['label'=>'1 small apple','grams'=>120]],
			'banana'                  => [['label'=>'1 medium banana','grams'=>120],['label'=>'1 small banana','grams'=>90]],
			'orange'                  => [['label'=>'1 medium orange','grams'=>150]],
			'strawberry'              => [['label'=>'1 cup','grams'=>150],['label'=>'5 berries','grams'=>60]],
			'blueberry'               => [['label'=>'1 cup','grams'=>148],['label'=>'1 handful','grams'=>40]],
			'grapes'                  => [['label'=>'1 cup','grams'=>150],['label'=>'1 small bunch','grams'=>75]],
			'mango'                   => [['label'=>'1 medium mango','grams'=>200],['label'=>'1 cup sliced','grams'=>165]],
			'avocado'                 => [['label'=>'1 whole avocado','grams'=>150],['label'=>'½ avocado','grams'=>75]],
			'watermelon'              => [['label'=>'1 slice','grams'=>280],['label'=>'1 cup diced','grams'=>150]],
			'pineapple'               => [['label'=>'1 cup chunks','grams'=>165],['label'=>'1 slice','grams'=>85]],
			'peach'                   => [['label'=>'1 medium peach','grams'=>150]],
			'pear'                    => [['label'=>'1 medium pear','grams'=>170]],
			'kiwi-fruit'              => [['label'=>'1 kiwi','grams'=>75],['label'=>'2 kiwis','grams'=>150]],
			'lemon'                   => [['label'=>'1 lemon','grams'=>60],['label'=>'juice of 1','grams'=>30]],
			'lime'                    => [['label'=>'1 lime','grams'=>44],['label'=>'juice of 1','grams'=>20]],
			'medjool-dates-dried'     => [['label'=>'1 date','grams'=>24],['label'=>'3 dates','grams'=>72]],

			// ── VEGETABLES ──
			'potato-boiled'           => [['label'=>'1 medium potato','grams'=>175],['label'=>'1 small potato','grams'=>120]],
			'sweet-potato-baked'      => [['label'=>'1 medium','grams'=>180]],
			'onion'                   => [['label'=>'1 medium onion','grams'=>110],['label'=>'1 small onion','grams'=>70]],
			'garlic'                  => [['label'=>'1 clove','grams'=>3],['label'=>'1 bulb','grams'=>40]],
			'tomato'                  => [['label'=>'1 medium tomato','grams'=>125],['label'=>'1 cherry tomato','grams'=>17]],
			'carrot'                  => [['label'=>'1 medium carrot','grams'=>80],['label'=>'1 cup grated','grams'=>110]],
			'broccoli'                => [['label'=>'1 cup florets','grams'=>90],['label'=>'1 portion','grams'=>80]],
			'steamed-broccoli'        => [['label'=>'1 cup','grams'=>90],['label'=>'1 portion','grams'=>80]],
			'spinach-raw'             => [['label'=>'1 cup','grams'=>30],['label'=>'1 handful','grams'=>25]],
			'cucumber'                => [['label'=>'1 cup sliced','grams'=>120],['label'=>'½ cucumber','grams'=>150]],
			'pepper-red'              => [['label'=>'1 medium pepper','grams'=>150],['label'=>'½ pepper','grams'=>75]],
			'mushroom'                => [['label'=>'1 cup sliced','grams'=>70],['label'=>'3 medium','grams'=>54]],
			'corn-on-the-cob'         => [['label'=>'1 ear','grams'=>150]],
			'courgette-zucchini'      => [['label'=>'1 medium','grams'=>200],['label'=>'1 cup sliced','grams'=>115]],
			'aubergine'               => [['label'=>'1 medium','grams'=>300],['label'=>'1 cup diced','grams'=>82]],
			'lettuce-iceberg'         => [['label'=>'1 cup shredded','grams'=>55],['label'=>'1 leaf','grams'=>20]],
			'cabbage'                 => [['label'=>'1 cup shredded','grams'=>90]],
			'cauliflower'             => [['label'=>'1 cup florets','grams'=>100]],
			'peas-frozen'             => [['label'=>'1 cup','grams'=>145],['label'=>'1 tablespoon','grams'=>10]],
			'sweetcorn-canned'        => [['label'=>'1 cup','grams'=>165],['label'=>'1 tablespoon','grams'=>15]],

			// ── MEAT & POULTRY ──
			'chicken-breast-raw'      => [['label'=>'1 breast','grams'=>174],['label'=>'1 portion','grams'=>150]],
			'grilled-chicken-breast'  => [['label'=>'1 breast','grams'=>150],['label'=>'1 portion','grams'=>120]],
			'chicken-thigh-raw'       => [['label'=>'1 thigh','grams'=>110]],
			'chicken-drumstick-raw'   => [['label'=>'1 drumstick','grams'=>75]],
			'chicken-wing-raw'        => [['label'=>'1 wing','grams'=>45],['label'=>'6 wings','grams'=>270]],
			'minced-beef-raw'         => [['label'=>'1 portion','grams'=>125],['label'=>'¼ lb patty','grams'=>113]],
			'beef-steak-sirloin-raw'  => [['label'=>'1 steak','grams'=>225]],
			'lamb-chop-raw'           => [['label'=>'1 chop','grams'=>100],['label'=>'2 chops','grams'=>200]],
			'pork-chop-raw'           => [['label'=>'1 chop','grams'=>150]],
			'bacon-back-grilled'      => [['label'=>'1 rasher','grams'=>25],['label'=>'2 rashers','grams'=>50]],
			'bacon-streaky-fried'     => [['label'=>'1 rasher','grams'=>15],['label'=>'3 rashers','grams'=>45]],
			'sausage-pork-grilled'    => [['label'=>'1 sausage','grams'=>48],['label'=>'2 sausages','grams'=>96]],
			'ham-sliced'              => [['label'=>'1 slice','grams'=>23],['label'=>'2 slices','grams'=>46]],
			'turkey-breast-deli-sliced' => [['label'=>'1 slice','grams'=>28],['label'=>'3 slices','grams'=>84]],
			'roast-beef'              => [['label'=>'1 slice','grams'=>28],['label'=>'3 slices','grams'=>84]],
			'roast-chicken'           => [['label'=>'1 portion','grams'=>120],['label'=>'1 leg quarter','grams'=>180]],
			'roast-lamb'              => [['label'=>'1 portion','grams'=>120],['label'=>'2 slices','grams'=>60]],
			'roast-turkey'            => [['label'=>'1 portion','grams'=>120],['label'=>'3 slices','grams'=>84]],

			// ── FISH & SEAFOOD ──
			'salmon-raw'              => [['label'=>'1 fillet','grams'=>150],['label'=>'1 portion','grams'=>125]],
			'grilled-salmon-fillet'   => [['label'=>'1 fillet','grams'=>140]],
			'tuna-canned-in-brine'    => [['label'=>'1 can (drained)','grams'=>120],['label'=>'½ can','grams'=>60]],
			'cod-raw'                 => [['label'=>'1 fillet','grams'=>180]],
			'steamed-cod'             => [['label'=>'1 fillet','grams'=>150]],
			'prawns-raw'              => [['label'=>'1 cup','grams'=>115],['label'=>'6 large','grams'=>84]],
			'boiled-prawns'           => [['label'=>'1 cup','grams'=>115],['label'=>'6 large','grams'=>84]],
			'sardines-in-tomato-sauce' => [['label'=>'1 can','grams'=>120]],
			'smoked-salmon'           => [['label'=>'1 slice','grams'=>28],['label'=>'1 portion','grams'=>56]],
			'fish-fingers-frozen-cooked' => [['label'=>'3 fingers','grams'=>84],['label'=>'5 fingers','grams'=>140]],

			// ── NUTS & SEEDS ──
			'almonds'                 => [['label'=>'1 handful','grams'=>25],['label'=>'10 almonds','grams'=>14]],
			'walnuts'                 => [['label'=>'1 handful','grams'=>25],['label'=>'7 halves','grams'=>14]],
			'cashew-nuts'             => [['label'=>'1 handful','grams'=>25],['label'=>'1 oz','grams'=>28]],
			'peanuts-roasted'         => [['label'=>'1 handful','grams'=>25],['label'=>'1 packet','grams'=>50]],
			'peanut-butter-smooth'    => [['label'=>'1 tablespoon','grams'=>16],['label'=>'1 teaspoon','grams'=>5]],
			'almond-butter'           => [['label'=>'1 tablespoon','grams'=>16]],
			'chia-seeds'              => [['label'=>'1 tablespoon','grams'=>12],['label'=>'1 teaspoon','grams'=>4]],
			'flaxseeds'               => [['label'=>'1 tablespoon','grams'=>10]],
			'pumpkin-seeds'           => [['label'=>'1 tablespoon','grams'=>8],['label'=>'1 handful','grams'=>25]],
			'sunflower-seeds'         => [['label'=>'1 tablespoon','grams'=>9],['label'=>'1 handful','grams'=>25]],
			'macadamia-nut-raw'       => [['label'=>'1 handful','grams'=>25],['label'=>'10 nuts','grams'=>20]],
			'desiccated-coconut'      => [['label'=>'1 tablespoon','grams'=>7]],

			// ── LEGUMES ──
			'baked-beans-canned'      => [['label'=>'1 cup','grams'=>260],['label'=>'½ can','grams'=>200],['label'=>'1 portion on toast','grams'=>150]],
			'kidney-beans-canned'     => [['label'=>'1 cup','grams'=>180],['label'=>'½ can','grams'=>130]],
			'chickpeas-canned'        => [['label'=>'1 cup','grams'=>160],['label'=>'½ can','grams'=>120]],
			'cooked-lentils-green'    => [['label'=>'1 cup','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'cooked-lentils-red'      => [['label'=>'1 cup','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'tofu-firm'               => [['label'=>'1 block','grams'=>300],['label'=>'½ block','grams'=>150],['label'=>'1 portion','grams'=>100]],

			// ── CONDIMENTS & SAUCES ──
			'tomato-ketchup'          => [['label'=>'1 tablespoon','grams'=>17],['label'=>'1 teaspoon','grams'=>6],['label'=>'1 sachet','grams'=>10]],
			'mayonnaise-full-fat'     => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'soy-sauce-dark'          => [['label'=>'1 tablespoon','grams'=>16],['label'=>'1 teaspoon','grams'=>5]],
			'honey'                   => [['label'=>'1 tablespoon','grams'=>21],['label'=>'1 teaspoon','grams'=>7]],
			'honey-manuka'            => [['label'=>'1 tablespoon','grams'=>21],['label'=>'1 teaspoon','grams'=>7]],
			'maple-syrup'             => [['label'=>'1 tablespoon','grams'=>20],['label'=>'1 teaspoon','grams'=>7]],
			'golden-syrup'            => [['label'=>'1 tablespoon','grams'=>20],['label'=>'1 teaspoon','grams'=>7]],
			'marmite'                 => [['label'=>'1 teaspoon','grams'=>4],['label'=>'thin spread','grams'=>2]],
			'hummus'                  => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 portion','grams'=>50]],
			'mustard-english'         => [['label'=>'1 teaspoon','grams'=>5]],
			'dijon-mustard'           => [['label'=>'1 teaspoon','grams'=>5]],
			'bbq-sauce'               => [['label'=>'1 tablespoon','grams'=>17],['label'=>'1 sachet','grams'=>10]],
			'sweet-chilli-sauce'      => [['label'=>'1 tablespoon','grams'=>17]],
			'sriracha-sauce'          => [['label'=>'1 teaspoon','grams'=>5]],
			'worcestershire-sauce'    => [['label'=>'1 teaspoon','grams'=>5],['label'=>'1 tablespoon','grams'=>15]],

			// ── DRINKS ──
			'orange-juice'            => [['label'=>'1 glass','grams'=>250],['label'=>'1 small glass','grams'=>150]],
			'apple-juice'             => [['label'=>'1 glass','grams'=>250]],
			'coca-cola'               => [['label'=>'1 can (330ml)','grams'=>330],['label'=>'1 glass','grams'=>250]],
			'hot-chocolate-with-semi-skimmed-milk' => [['label'=>'1 mug','grams'=>250]],
			'espresso-single-shot'    => [['label'=>'1 shot','grams'=>30]],
			'cappuccino-semi-skimmed-milk' => [['label'=>'1 cup','grams'=>250],['label'=>'1 small','grams'=>180]],
			'flat-white-whole-milk'   => [['label'=>'1 cup','grams'=>200]],
			'green-tea-brewed-no-sugar' => [['label'=>'1 cup','grams'=>240]],

			// ── SNACKS & SWEETS ──
			'milk-chocolate'          => [['label'=>'1 bar (45g)','grams'=>45],['label'=>'1 square','grams'=>5],['label'=>'1 row','grams'=>15]],
			'dark-chocolate-70'       => [['label'=>'1 bar (45g)','grams'=>45],['label'=>'2 squares','grams'=>10]],
			'digestive-biscuit-1-biscuit-15g' => [['label'=>'1 biscuit','grams'=>15],['label'=>'2 biscuits','grams'=>30]],
			'ready-salted-crisps'     => [['label'=>'1 bag (25g)','grams'=>25],['label'=>'1 sharing bag','grams'=>150]],
			'ice-cream-vanilla'       => [['label'=>'1 scoop','grams'=>66],['label'=>'2 scoops','grams'=>132]],
			'rice-cakes-plain'        => [['label'=>'1 cake','grams'=>9],['label'=>'2 cakes','grams'=>18]],
			'popcorn-air-popped'      => [['label'=>'1 cup','grams'=>8],['label'=>'1 bag (cinema)','grams'=>100]],

			// ── TAKEAWAY & COMMON MEALS ──
			'fish-and-chips'          => [['label'=>'1 portion','grams'=>350]],
			'cheeseburger-single-patty' => [['label'=>'1 burger','grams'=>150]],
			'pizza-frozen-margherita-cooked' => [['label'=>'1 slice','grams'=>107],['label'=>'½ pizza','grams'=>214]],
			'pepperoni-pizza-slice'    => [['label'=>'1 slice','grams'=>107]],
			'chicken-nuggets-frozen-cooked' => [['label'=>'6 nuggets','grams'=>100],['label'=>'10 nuggets','grams'=>166]],
			'hot-dog-in-bun'          => [['label'=>'1 hot dog','grams'=>100]],
			'sausage-roll-australian'  => [['label'=>'1 roll','grams'=>150]],

			// ── BAKING ESSENTIALS ──
			'plain-flour-white'       => [['label'=>'1 cup','grams'=>125],['label'=>'1 tablespoon','grams'=>8]],
			'self-raising-flour'      => [['label'=>'1 cup','grams'=>125],['label'=>'1 tablespoon','grams'=>8]],
			'caster-sugar'            => [['label'=>'1 cup','grams'=>200],['label'=>'1 tablespoon','grams'=>13],['label'=>'1 teaspoon','grams'=>4]],
			'icing-sugar'             => [['label'=>'1 cup','grams'=>120],['label'=>'1 tablespoon','grams'=>8]],
			'cocoa-powder-unsweetened' => [['label'=>'1 tablespoon','grams'=>5],['label'=>'1 teaspoon','grams'=>2]],
			'baking-powder'           => [['label'=>'1 teaspoon','grams'=>4]],
			'bicarbonate-of-soda'     => [['label'=>'1 teaspoon','grams'=>5]],
			'cornflour-cornstarch'    => [['label'=>'1 tablespoon','grams'=>8],['label'=>'1 teaspoon','grams'=>3]],
			'desiccated-coconut'      => [['label'=>'1 tablespoon','grams'=>7],['label'=>'1 cup','grams'=>80]],

			// ── POPULAR INTERNATIONAL DISHES ──
			'dal-bhat-nepali-lentil-soup-rice' => [['label'=>'1 plate','grams'=>350]],
			'momo-steamed-buff-chicken-8-pcs'  => [['label'=>'8 momos','grams'=>200],['label'=>'4 momos','grams'=>100]],
			'biryani-chicken'         => [['label'=>'1 plate','grams'=>350],['label'=>'1 portion','grams'=>250]],
			'butter-chicken'          => [['label'=>'1 portion','grams'=>250],['label'=>'1 bowl','grams'=>200]],
			'dal-makhani-black-lentil-curry' => [['label'=>'1 bowl','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'nasi-lemak-coconut-rice-full-set' => [['label'=>'1 plate','grams'=>300]],
			'pad-thai-chicken'        => [['label'=>'1 plate','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'sushi-salmon-nigiri-1pc' => [['label'=>'1 piece','grams'=>35],['label'=>'2 pieces','grams'=>70]],
			'pho-bo-beef-pho'         => [['label'=>'1 bowl','grams'=>500],['label'=>'1 small bowl','grams'=>350]],
			'ramen-tonkotsu'          => [['label'=>'1 bowl','grams'=>500]],
			'tacos-beef'              => [['label'=>'1 taco','grams'=>85],['label'=>'3 tacos','grams'=>255]],
			'burrito-chicken'         => [['label'=>'1 burrito','grams'=>250]],
			'falafel-wrap'            => [['label'=>'1 wrap','grams'=>250]],
			'kebab-koobideh-minced-lamb-kebab' => [['label'=>'1 skewer','grams'=>100],['label'=>'2 skewers','grams'=>200]],
			'jollof-rice'             => [['label'=>'1 plate','grams'=>300],['label'=>'1 portion','grams'=>200]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 58 );
	}

	/** Seed v59: Serving sizes batch 2 — ~350 more foods. */
	public static function seed_v59(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 59 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── COOKED MEAT DISHES ──
			'roast-pork'              => [['label'=>'1 portion','grams'=>120],['label'=>'2 slices','grams'=>60]],
			'slow-cooked-pulled-pork' => [['label'=>'1 portion','grams'=>150],['label'=>'1 sandwich fill','grams'=>100]],
			'beef-stew'               => [['label'=>'1 bowl','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'lamb-hotpot'             => [['label'=>'1 bowl','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'chicken-casserole'       => [['label'=>'1 bowl','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'chilli-con-carne'        => [['label'=>'1 bowl','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'bolognese-sauce-beef'    => [['label'=>'1 portion','grams'=>200],['label'=>'1 ladle','grams'=>120]],
			'shepherds-pie'           => [['label'=>'1 portion','grams'=>300]],
			'cottage-pie'             => [['label'=>'1 portion','grams'=>300]],
			'lasagne-beef'            => [['label'=>'1 portion','grams'=>300]],
			'meatballs-beef'          => [['label'=>'4 meatballs','grams'=>120],['label'=>'6 meatballs','grams'=>180]],
			'chicken-kiev'            => [['label'=>'1 kiev','grams'=>150]],
			'sausage-roll'            => [['label'=>'1 roll','grams'=>70],['label'=>'1 large roll','grams'=>150]],
			'cornish-pasty'           => [['label'=>'1 pasty','grams'=>250]],
			'pork-pie'                => [['label'=>'1 pie','grams'=>140],['label'=>'1 mini pie','grams'=>70]],
			'scotch-egg'              => [['label'=>'1 scotch egg','grams'=>113]],
			'beef-wellington'         => [['label'=>'1 portion','grams'=>250]],
			'gammon-steak-grilled'    => [['label'=>'1 steak','grams'=>170]],
			'bratwurst-grilled'       => [['label'=>'1 sausage','grams'=>85]],
			'pepperoni'               => [['label'=>'5 slices','grams'=>15],['label'=>'10 slices','grams'=>30]],
			'chorizo'                 => [['label'=>'4 slices','grams'=>20],['label'=>'1 portion','grams'=>50]],
			'prosciutto'              => [['label'=>'2 slices','grams'=>30]],
			'beef-jerky'              => [['label'=>'1 piece','grams'=>28],['label'=>'1 bag','grams'=>50]],
			'biltong'                 => [['label'=>'1 portion','grams'=>30],['label'=>'1 bag','grams'=>50]],
			'venison-steak-grilled'   => [['label'=>'1 steak','grams'=>150]],
			'kangaroo-steak-grilled'  => [['label'=>'1 steak','grams'=>150]],

			// ── COOKED SEAFOOD DISHES ──
			'pan-fried-sea-bass'      => [['label'=>'1 fillet','grams'=>150]],
			'baked-haddock'           => [['label'=>'1 fillet','grams'=>150]],
			'grilled-tuna-steak'      => [['label'=>'1 steak','grams'=>150]],
			'grilled-mackerel'        => [['label'=>'1 fillet','grams'=>120]],
			'beer-battered-cod'       => [['label'=>'1 fillet','grams'=>200]],
			'teriyaki-salmon'         => [['label'=>'1 fillet','grams'=>150]],
			'fish-tacos-cod'          => [['label'=>'1 taco','grams'=>100],['label'=>'2 tacos','grams'=>200]],
			'grilled-lobster-tail'    => [['label'=>'1 tail','grams'=>120]],
			'steamed-mussels'         => [['label'=>'1 bowl','grams'=>250],['label'=>'1 portion','grams'=>150]],
			'salt-and-pepper-squid'   => [['label'=>'1 portion','grams'=>150]],
			'barramundi-grilled'      => [['label'=>'1 fillet','grams'=>170]],

			// ── SOUPS ──
			'tomato-soup-canned'      => [['label'=>'1 bowl','grams'=>250],['label'=>'1 mug','grams'=>200],['label'=>'½ can','grams'=>200]],
			'chicken-soup-canned'     => [['label'=>'1 bowl','grams'=>250],['label'=>'1 mug','grams'=>200]],
			'mushroom-soup-canned'    => [['label'=>'1 bowl','grams'=>250],['label'=>'1 mug','grams'=>200]],
			'minestrone-soup'         => [['label'=>'1 bowl','grams'=>250]],
			'pea-and-mint-soup'       => [['label'=>'1 bowl','grams'=>250]],
			'miso-soup-fresh'         => [['label'=>'1 bowl','grams'=>200]],
			'pho-ga-chicken-pho'      => [['label'=>'1 bowl','grams'=>500]],
			'tom-yum-goong-hot-sour-prawn-soup' => [['label'=>'1 bowl','grams'=>300]],
			'tom-kha-gai-coconut-chicken-soup'  => [['label'=>'1 bowl','grams'=>300]],
			'mercimek-corbasi-red-lentil-soup'  => [['label'=>'1 bowl','grams'=>250]],
			'harira-moroccan-soup'    => [['label'=>'1 bowl','grams'=>250]],

			// ── INTERNATIONAL CURRIES & STEWS ──
			'tikka-masala-chicken'    => [['label'=>'1 portion','grams'=>250],['label'=>'1 bowl','grams'=>300]],
			'korma-chicken'           => [['label'=>'1 portion','grams'=>250]],
			'madras-beef'             => [['label'=>'1 portion','grams'=>250]],
			'jalfrezi-chicken'        => [['label'=>'1 portion','grams'=>250]],
			'rogan-josh-kashmiri-lamb' => [['label'=>'1 portion','grams'=>250]],
			'vindaloo-pork-goan'      => [['label'=>'1 portion','grams'=>250]],
			'palak-paneer'            => [['label'=>'1 portion','grams'=>200]],
			'chole-bhature-chickpea-fried-bread' => [['label'=>'1 plate','grams'=>300]],
			'rajma-kidney-bean-curry' => [['label'=>'1 bowl','grams'=>200]],
			'aloo-gobi'               => [['label'=>'1 portion','grams'=>200]],
			'chana-masala'            => [['label'=>'1 portion','grams'=>200]],
			'green-curry-thai-chicken' => [['label'=>'1 portion','grams'=>250]],
			'red-curry-thai-prawn'    => [['label'=>'1 portion','grams'=>250]],
			'massaman-curry-beef'     => [['label'=>'1 portion','grams'=>250]],
			'panang-curry-beef'       => [['label'=>'1 portion','grams'=>250]],
			'japanese-curry-rice-pork' => [['label'=>'1 plate','grams'=>350]],
			'rendang-daging-malaysian-dry-beef-curry' => [['label'=>'1 portion','grams'=>150]],
			'nihari-slow-cooked-beef-stew' => [['label'=>'1 bowl','grams'=>250]],
			'karahi-gosht-wok-cooked-meat' => [['label'=>'1 portion','grams'=>200]],
			'ghormeh-sabzi-herb-stew' => [['label'=>'1 bowl','grams'=>250],['label'=>'1 portion','grams'=>200]],
			'fesenjan-pomegranate-walnut-stew' => [['label'=>'1 portion','grams'=>200]],

			// ── RICE DISHES (INTERNATIONAL) ──
			'kacchi-biryani-dhaka-style-mutton' => [['label'=>'1 plate','grams'=>350],['label'=>'1 portion','grams'=>250]],
			'kabsa-saudi-rice-chicken' => [['label'=>'1 plate','grams'=>350],['label'=>'1 portion','grams'=>250]],
			'nasi-goreng-indonesian-fried-rice' => [['label'=>'1 plate','grams'=>300]],
			'fried-rice-yangzhou-egg-fried' => [['label'=>'1 plate','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'arroz-con-gandules-rice-pigeon-peas' => [['label'=>'1 plate','grams'=>250]],
			'pilau-rice-swahili-style' => [['label'=>'1 plate','grams'=>250]],
			'plov-uzbek-national-dish-lamb-rice' => [['label'=>'1 plate','grams'=>300]],

			// ── NOODLE DISHES ──
			'chow-mein-chicken'       => [['label'=>'1 plate','grams'=>300],['label'=>'1 portion','grams'=>250]],
			'lo-mein-pork'            => [['label'=>'1 plate','grams'=>300]],
			'dan-dan-noodles-sichuan' => [['label'=>'1 bowl','grams'=>300]],
			'yakisoba-fried-noodles'  => [['label'=>'1 plate','grams'=>250]],
			'pad-see-ew-wide-noodle-stir-fry' => [['label'=>'1 plate','grams'=>300]],
			'pad-krapao-holy-basil-stir-fry' => [['label'=>'1 plate','grams'=>250]],
			'bun-cha-grilled-pork-noodles-hanoi' => [['label'=>'1 set','grams'=>350]],
			'mohinga-fish-noodle-soup-national-dish' => [['label'=>'1 bowl','grams'=>350]],
			'laksa-prawn'             => [['label'=>'1 bowl','grams'=>350]],
			'laksa-lemak-nyonya-curry-laksa' => [['label'=>'1 bowl','grams'=>350]],
			'khao-soi-chiang-mai-curry-noodle' => [['label'=>'1 bowl','grams'=>350]],

			// ── DUMPLINGS & DIM SUM ──
			'xiaolongbao-soup-dumpling-1pc' => [['label'=>'1 piece','grams'=>30],['label'=>'8 pieces','grams'=>240]],
			'jiaozi-boiled-dumpling-1pc' => [['label'=>'1 piece','grams'=>25],['label'=>'10 pieces','grams'=>250]],
			'guotie-potsticker-1pc'   => [['label'=>'1 piece','grams'=>30],['label'=>'6 pieces','grams'=>180]],
			'char-siu-bao-bbq-pork-bun-1pc' => [['label'=>'1 bun','grams'=>80],['label'=>'3 buns','grams'=>240]],
			'baozi-steamed-meat-bun-1pc' => [['label'=>'1 bun','grams'=>75],['label'=>'2 buns','grams'=>150]],
			'gyoza-pan-fried-1pc'     => [['label'=>'1 piece','grams'=>25],['label'=>'6 pieces','grams'=>150]],
			'manti-uzbek-steamed-dumplings-1pc' => [['label'=>'1 piece','grams'=>40],['label'=>'5 pieces','grams'=>200]],
			'khinkali-georgian-soup-dumplings-1pc' => [['label'=>'1 piece','grams'=>60],['label'=>'5 pieces','grams'=>300]],
			'pelmeni-siberian-pork-beef' => [['label'=>'10 pieces','grams'=>200],['label'=>'15 pieces','grams'=>300]],
			'buuz-mongolian-steamed-dumplings-1pc' => [['label'=>'1 piece','grams'=>40],['label'=>'6 pieces','grams'=>240]],

			// ── SANDWICHES & WRAPS ──
			'blt-sandwich'            => [['label'=>'1 sandwich','grams'=>200]],
			'tuna-mayo-sandwich'      => [['label'=>'1 sandwich','grams'=>200]],
			'ham-and-cheese-sandwich' => [['label'=>'1 sandwich','grams'=>200]],
			'egg-mayo-sandwich'       => [['label'=>'1 sandwich','grams'=>200]],
			'chicken-wrap'            => [['label'=>'1 wrap','grams'=>220]],
			'falafel-wrap'            => [['label'=>'1 wrap','grams'=>250]],
			'club-sandwich'           => [['label'=>'1 sandwich','grams'=>300]],
			'cubano-sandwich-pressed' => [['label'=>'1 sandwich','grams'=>280]],
			'banh-mi-classic-pork'    => [['label'=>'1 sandwich','grams'=>250]],
			'prego-no-pao-steak-sandwich' => [['label'=>'1 sandwich','grams'=>200]],
			'shawarma-chicken'        => [['label'=>'1 wrap','grams'=>250]],
			'doner-kebab-lamb-in-pitta' => [['label'=>'1 kebab','grams'=>300]],

			// ── BREADS (INTERNATIONAL) ──
			'garlic-naan'             => [['label'=>'1 naan','grams'=>130]],
			'peshwari-naan'           => [['label'=>'1 naan','grams'=>130]],
			'paratha-layered'         => [['label'=>'1 paratha','grams'=>80]],
			'dosa-plain'              => [['label'=>'1 dosa','grams'=>100]],
			'masala-dosa'             => [['label'=>'1 dosa','grams'=>150]],
			'idli-steamed-1-pc'       => [['label'=>'1 idli','grams'=>40],['label'=>'3 idlis','grams'=>120]],
			'roti-canai-malaysian-flatbread' => [['label'=>'1 roti','grams'=>100]],
			'injera-ethiopian-flatbread' => [['label'=>'1 piece','grams'=>100],['label'=>'1 full injera','grams'=>200]],
			'sangak-bread-stone-baked-flatbread' => [['label'=>'1 piece','grams'=>100]],
			'luchi-bengali-fried-bread' => [['label'=>'1 luchi','grams'=>30],['label'=>'4 luchis','grams'=>120]],
			'bolani-stuffed-flatbread' => [['label'=>'1 bolani','grams'=>120]],
			'khachapuri-adjaruli-cheese-boat-bread' => [['label'=>'1 boat','grams'=>350]],

			// ── SNACKS & STREET FOOD ──
			'samosa-vegetable-indian' => [['label'=>'1 samosa','grams'=>80],['label'=>'2 samosas','grams'=>160]],
			'singara-bangladeshi-samosa' => [['label'=>'1 singara','grams'=>60],['label'=>'3 singaras','grams'=>180]],
			'onion-bhaji'             => [['label'=>'1 bhaji','grams'=>50],['label'=>'3 bhajis','grams'=>150]],
			'pakora-vegetable'        => [['label'=>'1 piece','grams'=>30],['label'=>'5 pieces','grams'=>150]],
			'spring-rolls-frozen-cooked' => [['label'=>'1 roll','grams'=>60],['label'=>'4 rolls','grams'=>240]],
			'empanada-argentina-beef' => [['label'=>'1 empanada','grams'=>120]],
			'pasteis-de-nata-custard-tart-belem' => [['label'=>'1 tart','grams'=>65]],
			'jamaican-patty-beef'     => [['label'=>'1 patty','grams'=>170]],
			'aloo-tikki-potato-patty' => [['label'=>'1 tikki','grams'=>80],['label'=>'2 tikkis','grams'=>160]],
			'pav-bhaji-mumbai-street-food' => [['label'=>'1 portion','grams'=>300]],
			'vada-pav-mumbai-potato-burger' => [['label'=>'1 vada pav','grams'=>150]],
			'fuchka-bangladeshi-pani-puri' => [['label'=>'6 pieces','grams'=>100]],
			'chotpoti-chickpea-potato-snack' => [['label'=>'1 bowl','grams'=>200]],
			'doubles-trini-curried-chickpea-in-bara' => [['label'=>'1 doubles','grams'=>150]],
			'pupusa-salvadoran-cheese-bean' => [['label'=>'1 pupusa','grams'=>120],['label'=>'2 pupusas','grams'=>240]],
			'arepa-corn-plain'        => [['label'=>'1 arepa','grams'=>100]],

			// ── DESSERTS & SWEETS ──
			'cheesecake-baked'        => [['label'=>'1 slice','grams'=>125]],
			'chocolate-brownie'       => [['label'=>'1 brownie','grams'=>60]],
			'victoria-sponge'         => [['label'=>'1 slice','grams'=>100]],
			'carrot-cake'             => [['label'=>'1 slice','grams'=>100]],
			'sticky-toffee-pudding'   => [['label'=>'1 portion','grams'=>120]],
			'apple-crumble'           => [['label'=>'1 portion','grams'=>150]],
			'tiramisu'                => [['label'=>'1 portion','grams'=>120]],
			'creme-brulee'            => [['label'=>'1 ramekin','grams'=>120]],
			'bakewell-tart'           => [['label'=>'1 slice','grams'=>85]],
			'treacle-tart'            => [['label'=>'1 slice','grams'=>100]],
			'mince-pie'               => [['label'=>'1 pie','grams'=>55]],
			'pancakes-american-with-syrup' => [['label'=>'3 pancakes','grams'=>200]],
			'gulab-jamun-indian-style' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'roshogolla-spongy-cheese-ball' => [['label'=>'1 piece','grams'=>40],['label'=>'3 pieces','grams'=>120]],
			'jalebi-indian-crispy'    => [['label'=>'1 piece','grams'=>25],['label'=>'4 pieces','grams'=>100]],
			'baklava'                 => [['label'=>'1 piece','grams'=>40],['label'=>'3 pieces','grams'=>120]],
			'mochi-anko-red-bean-filling' => [['label'=>'1 piece','grams'=>45],['label'=>'3 pieces','grams'=>135]],
			'mooncake-lotus-seed-1-4' => [['label'=>'¼ cake','grams'=>45],['label'=>'½ cake','grams'=>90]],
			'lamington-chocolate-coconut' => [['label'=>'1 lamington','grams'=>70]],
			'pavlova-classic-cream-passionfruit' => [['label'=>'1 slice','grams'=>120]],
			'churros-with-sugar'      => [['label'=>'3 churros','grams'=>75],['label'=>'6 churros','grams'=>150]],

			// ── DRINKS ──
			'smoothie-berry'          => [['label'=>'1 glass','grams'=>250],['label'=>'1 small','grams'=>150]],
			'smoothie-green-spinach'  => [['label'=>'1 glass','grams'=>250]],
			'kombucha'                => [['label'=>'1 bottle','grams'=>330],['label'=>'1 glass','grams'=>200]],
			'chai-latte'              => [['label'=>'1 cup','grams'=>250]],
			'matcha-latte'            => [['label'=>'1 cup','grams'=>250]],
			'milkshake-chocolate'     => [['label'=>'1 glass','grams'=>300],['label'=>'1 small','grams'=>200]],
			'thai-iced-tea-cha-yen'   => [['label'=>'1 glass','grams'=>300]],
			'ca-phe-sua-da-vietnamese-iced-coffee' => [['label'=>'1 glass','grams'=>200]],
			'bubble-tea-milk-tea-tapioca' => [['label'=>'1 cup','grams'=>400],['label'=>'1 small','grams'=>300]],
			'teh-tarik-pulled-milk-tea' => [['label'=>'1 glass','grams'=>200]],
			'masala-chai-spiced-milk-tea' => [['label'=>'1 cup','grams'=>200]],
			'mango-lassi-sweet'       => [['label'=>'1 glass','grams'=>250]],
			'koko-samoa-samoan-cocoa-drink' => [['label'=>'1 cup','grams'=>200]],
			'doogh-yoghurt-soda-drink' => [['label'=>'1 glass','grams'=>250]],

			// ── CANNED & TINNED ──
			'sardines-in-olive-oil'   => [['label'=>'1 can','grams'=>120]],
			'mackerel-in-tomato-sauce' => [['label'=>'1 can','grams'=>125]],
			'salmon-canned-pink'      => [['label'=>'1 can','grams'=>213],['label'=>'½ can','grams'=>106]],
			'spam-canned-pork'        => [['label'=>'1 slice','grams'=>56],['label'=>'¼ can','grams'=>85]],
			'corned-beef-canned'      => [['label'=>'1 slice','grams'=>28],['label'=>'½ can','grams'=>175]],
			'chopped-tomatoes-canned' => [['label'=>'1 can','grams'=>400],['label'=>'½ can','grams'=>200]],
			'coconut-milk-canned'     => [['label'=>'1 can','grams'=>400],['label'=>'½ can','grams'=>200]],
			'butter-beans-canned'     => [['label'=>'1 can','grams'=>400],['label'=>'½ can','grams'=>200]],

			// ── MILK ALTERNATIVES ──
			'oat-milk-unsweetened'    => [['label'=>'1 glass','grams'=>250],['label'=>'splash','grams'=>30]],
			'soya-milk-unsweetened'   => [['label'=>'1 glass','grams'=>250],['label'=>'splash','grams'=>30]],
			'almond-milk-unsweetened' => [['label'=>'1 glass','grams'=>250],['label'=>'splash','grams'=>30]],
			'coconut-milk-carton'     => [['label'=>'1 glass','grams'=>250]],

			// ── DRIED FRUIT ──
			'dried-apricots'          => [['label'=>'3 apricots','grams'=>21],['label'=>'1 handful','grams'=>30]],
			'dried-cranberries-sweetened' => [['label'=>'1 tablespoon','grams'=>10],['label'=>'1 handful','grams'=>25]],
			'prunes-dried-plums'      => [['label'=>'3 prunes','grams'=>25],['label'=>'5 prunes','grams'=>42]],
			'raisins'                 => [['label'=>'1 tablespoon','grams'=>10],['label'=>'1 small box','grams'=>14],['label'=>'1 handful','grams'=>25]],
			'mixed-dried-fruit'       => [['label'=>'1 handful','grams'=>30],['label'=>'1 cup','grams'=>150]],

			// ── FLOURS & BAKING (more) ──
			'almond-flour'            => [['label'=>'1 cup','grams'=>96],['label'=>'1 tablespoon','grams'=>6]],
			'coconut-flour'           => [['label'=>'1 cup','grams'=>112],['label'=>'1 tablespoon','grams'=>7]],
			'wholemeal-flour'         => [['label'=>'1 cup','grams'=>120],['label'=>'1 tablespoon','grams'=>8]],

			// ── PROTEIN SUPPLEMENTS ──
			'whey-protein-powder-unflavoured' => [['label'=>'1 scoop','grams'=>30],['label'=>'2 scoops','grams'=>60]],
			'pea-protein-powder'      => [['label'=>'1 scoop','grams'=>30]],
			'casein-protein-powder'   => [['label'=>'1 scoop','grams'=>30]],
			'creatine-monohydrate'    => [['label'=>'1 scoop','grams'=>5],['label'=>'1 teaspoon','grams'=>5]],

			// ── SPICES & SEASONINGS ──
			'ground-cumin'            => [['label'=>'1 teaspoon','grams'=>3]],
			'turmeric-ground'         => [['label'=>'1 teaspoon','grams'=>3]],
			'smoked-paprika'          => [['label'=>'1 teaspoon','grams'=>2]],
			'garam-masala'            => [['label'=>'1 teaspoon','grams'=>3]],
			'chilli-flakes'           => [['label'=>'1 teaspoon','grams'=>2],['label'=>'1 pinch','grams'=>0.5]],
			'gochujang-red-chilli-paste' => [['label'=>'1 tablespoon','grams'=>17],['label'=>'1 teaspoon','grams'=>6]],
			'tahini'                  => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'miso-paste-white'        => [['label'=>'1 tablespoon','grams'=>17],['label'=>'1 teaspoon','grams'=>6]],
			'harissa-paste'           => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'wasabi-paste'            => [['label'=>'1 teaspoon','grams'=>5],['label'=>'1 pea-sized','grams'=>2]],

			// ── FROZEN FOODS ──
			'potato-waffles-frozen-cooked' => [['label'=>'1 waffle','grams'=>56],['label'=>'2 waffles','grams'=>112]],
			'chips-french-fries-oven' => [['label'=>'1 portion','grams'=>165],['label'=>'½ bag','grams'=>225]],
			'hash-browns-frozen-cooked' => [['label'=>'1 hash brown','grams'=>56],['label'=>'2 hash browns','grams'=>112]],
			'garlic-bread-frozen-cooked' => [['label'=>'1 slice','grams'=>40],['label'=>'½ baguette','grams'=>100]],
			'onion-rings-frozen-cooked' => [['label'=>'5 rings','grams'=>75],['label'=>'8 rings','grams'=>120]],
			'samosa-vegetable-frozen-cooked' => [['label'=>'1 samosa','grams'=>60],['label'=>'3 samosas','grams'=>180]],

			// ── BISCUITS & COOKIES ──
			'rich-tea-biscuit'        => [['label'=>'1 biscuit','grams'=>8],['label'=>'3 biscuits','grams'=>24]],
			'hobnob'                  => [['label'=>'1 biscuit','grams'=>14],['label'=>'2 biscuits','grams'=>28]],
			'custard-cream'           => [['label'=>'1 biscuit','grams'=>11],['label'=>'3 biscuits','grams'=>33]],
			'bourbon-cream'           => [['label'=>'1 biscuit','grams'=>13],['label'=>'2 biscuits','grams'=>26]],
			'shortbread-finger'       => [['label'=>'1 finger','grams'=>20],['label'=>'2 fingers','grams'=>40]],
			'jaffa-cake-1-cake'       => [['label'=>'1 cake','grams'=>12],['label'=>'3 cakes','grams'=>36]],
			'chocolate-chip-cookie'   => [['label'=>'1 cookie','grams'=>30],['label'=>'2 cookies','grams'=>60]],
			'oatcake'                 => [['label'=>'1 oatcake','grams'=>13],['label'=>'3 oatcakes','grams'=>39]],

			// ── CRISPS ──
			'salt-and-vinegar-crisps' => [['label'=>'1 bag (25g)','grams'=>25],['label'=>'1 grab bag','grams'=>50]],
			'cheese-and-onion-crisps' => [['label'=>'1 bag (25g)','grams'=>25]],
			'pork-scratchings'        => [['label'=>'1 bag','grams'=>40]],
			'poppadoms-fried'         => [['label'=>'1 poppadom','grams'=>15],['label'=>'2 poppadums','grams'=>30]],
			'bombay-mix'              => [['label'=>'1 handful','grams'=>30],['label'=>'1 bowl','grams'=>80]],

			// ── VINEGARS & MISC ──
			'balsamic-vinegar'        => [['label'=>'1 tablespoon','grams'=>16],['label'=>'1 teaspoon','grams'=>5]],
			'apple-cider-vinegar'     => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'fish-sauce'              => [['label'=>'1 tablespoon','grams'=>18],['label'=>'1 teaspoon','grams'=>6]],
			'soy-sauce-dark'          => [['label'=>'1 tablespoon','grams'=>16],['label'=>'1 teaspoon','grams'=>5]],
			'kecap-manis-sweet-soy-sauce' => [['label'=>'1 tablespoon','grams'=>18],['label'=>'1 teaspoon','grams'=>6]],
			'nuoc-mam-fish-sauce'     => [['label'=>'1 tablespoon','grams'=>18],['label'=>'1 teaspoon','grams'=>6]],

			// ── POPULAR UK MEALS ──
			'full-english-breakfast'  => [['label'=>'1 plate','grams'=>400]],
			'beans-on-toast'          => [['label'=>'1 portion','grams'=>250]],
			'jacket-potato-with-cheese' => [['label'=>'1 jacket potato','grams'=>300]],
			'jacket-potato-with-beans' => [['label'=>'1 jacket potato','grams'=>300]],
			'bangers-and-mash'        => [['label'=>'1 plate','grams'=>350]],
			'steak-and-kidney-pie'    => [['label'=>'1 pie','grams'=>250]],
			'chicken-and-mushroom-pie' => [['label'=>'1 pie','grams'=>250]],
			'yorkshire-pudding-1-medium' => [['label'=>'1 pudding','grams'=>35],['label'=>'2 puddings','grams'=>70]],
			'quiche-lorraine'         => [['label'=>'1 slice','grams'=>150]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 59 );
	}

	/** Seed v60: Serving sizes batch 3 — ~300 more foods. */
	public static function seed_v60(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 60 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── SOUTH ASIAN DISHES ──
			'biryani-karachi-style-mutton' => [['label'=>'1 plate','grams'=>350],['label'=>'1 portion','grams'=>250]],
			'biryani-sindhi-chicken' => [['label'=>'1 plate','grams'=>350]],
			'haleem-slow-cooked-wheat-meat' => [['label'=>'1 bowl','grams'=>250]],
			'seekh-kebab-pakistani-spiced' => [['label'=>'1 skewer','grams'=>100],['label'=>'2 skewers','grams'=>200]],
			'shami-kebab-lentil-meat-patty' => [['label'=>'1 kebab','grams'=>50],['label'=>'2 kebabs','grams'=>100]],
			'chicken-tikka-pakistani-charcoal' => [['label'=>'1 portion','grams'=>150],['label'=>'3 pieces','grams'=>120]],
			'tandoori-chicken' => [['label'=>'1 leg','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'paratha-layered-with-butter' => [['label'=>'1 paratha','grams'=>80]],
			'puri-deep-fried-bread-indian' => [['label'=>'1 puri','grams'=>25],['label'=>'4 puris','grams'=>100]],
			'samosa-lamb' => [['label'=>'1 samosa','grams'=>80]],
			'dahi-bhalla-yoghurt-lentil-balls' => [['label'=>'3 pieces','grams'=>120]],
			'chana-chaat-chickpea-salad' => [['label'=>'1 bowl','grams'=>150]],
			'pani-puri-6-pcs-with-water' => [['label'=>'6 pieces','grams'=>100]],
			'bhel-puri-puffed-rice-mix' => [['label'=>'1 plate','grams'=>150]],
			'kheer-pakistani-rice-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'gulab-jamun-pakistani-style' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'ras-malai-cream-cheese-in-milk' => [['label'=>'1 piece','grams'=>50],['label'=>'2 pieces','grams'=>100]],
			'kulfi-pakistani-pistachio' => [['label'=>'1 kulfi','grams'=>80]],
			'lassi-salty-pakistani' => [['label'=>'1 glass','grams'=>250]],
			'rooh-afza-rose-syrup-drink' => [['label'=>'1 glass','grams'=>250]],
			'masala-chai-spiced-milk-tea' => [['label'=>'1 cup','grams'=>200]],

			// ── BANGLADESHI ──
			'ilish-bhapa-steamed-hilsa-in-mustard' => [['label'=>'1 piece','grams'=>120]],
			'ilish-machher-jhol-hilsa-curry' => [['label'=>'1 bowl','grams'=>250]],
			'chingri-malai-curry-prawn-coconut' => [['label'=>'1 portion','grams'=>200]],
			'kacchi-biryani-dhaka-style-mutton' => [['label'=>'1 plate','grams'=>350]],
			'bhaat-plain-steamed-rice-bangladeshi' => [['label'=>'1 plate','grams'=>250],['label'=>'1 cup','grams'=>185]],
			'polao-bangladeshi-fragrant-rice' => [['label'=>'1 plate','grams'=>250]],
			'khichuri-rice-lentil-comfort-food' => [['label'=>'1 plate','grams'=>300]],
			'luchi-bengali-fried-bread' => [['label'=>'1 luchi','grams'=>30],['label'=>'4 luchis','grams'=>120]],
			'porota-layered-flatbread-dhaka' => [['label'=>'1 porota','grams'=>80]],
			'singara-bangladeshi-samosa' => [['label'=>'1 singara','grams'=>60],['label'=>'3 singaras','grams'=>180]],
			'piyaju-onion-fritters-lentil' => [['label'=>'1 piece','grams'=>30],['label'=>'5 pieces','grams'=>150]],
			'beguni-aubergine-fritters' => [['label'=>'1 piece','grams'=>25],['label'=>'5 pieces','grams'=>125]],
			'fuchka-bangladeshi-pani-puri' => [['label'=>'6 pieces','grams'=>100]],
			'chotpoti-chickpea-potato-snack' => [['label'=>'1 bowl','grams'=>200]],
			'roshogolla-spongy-cheese-ball' => [['label'=>'1 piece','grams'=>40],['label'=>'3 pieces','grams'=>120]],
			'mishti-doi-sweetened-yoghurt' => [['label'=>'1 cup','grams'=>100]],
			'sandesh-fresh-cheese-sweet' => [['label'=>'1 piece','grams'=>25],['label'=>'4 pieces','grams'=>100]],
			'payesh-bengali-rice-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'cha-bangladeshi-tea-with-condensed-milk' => [['label'=>'1 cup','grams'=>120]],

			// ── PERSIAN / IRANIAN ──
			'chelow-iranian-steamed-rice-plain' => [['label'=>'1 plate','grams'=>250],['label'=>'1 cup','grams'=>185]],
			'tahdig-crispy-rice-crust' => [['label'=>'1 portion','grams'=>80]],
			'gheimeh-split-pea-meat-stew' => [['label'=>'1 bowl','grams'=>250]],
			'koobideh-minced-lamb-kebab' => [['label'=>'1 skewer','grams'=>100],['label'=>'2 skewers','grams'=>200]],
			'joojeh-kebab-saffron-chicken' => [['label'=>'1 skewer','grams'=>120]],
			'ash-reshteh-noodle-herb-soup' => [['label'=>'1 bowl','grams'=>300]],
			'sholeh-zard-saffron-rice-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'faloodeh-rose-water-ice-noodles' => [['label'=>'1 bowl','grams'=>150]],
			'doogh-yoghurt-soda-drink' => [['label'=>'1 glass','grams'=>250]],

			// ── NEPALI ──
			'dal-bhat-nepali-lentil-soup-rice' => [['label'=>'1 plate','grams'=>350]],
			'dal-bhat-tarkari-full-plate-avg' => [['label'=>'1 full plate','grams'=>400]],
			'momo-steamed-buff-chicken-8-pcs' => [['label'=>'8 momos','grams'=>200],['label'=>'4 momos','grams'=>100]],
			'momo-fried-8-pcs' => [['label'=>'8 momos','grams'=>220],['label'=>'4 momos','grams'=>110]],
			'sel-roti-nepali-rice-doughnut' => [['label'=>'1 ring','grams'=>60]],
			'thukpa-tibetan-nepali-noodle-soup' => [['label'=>'1 bowl','grams'=>350]],

			// ── EAST ASIAN ──
			'oyakodon-chicken-egg-rice-bowl' => [['label'=>'1 bowl','grams'=>350]],
			'gyudon-beef-rice-bowl' => [['label'=>'1 bowl','grams'=>350]],
			'katsudon-pork-cutlet-rice-bowl' => [['label'=>'1 bowl','grams'=>350]],
			'karaage-japanese-fried-chicken' => [['label'=>'5 pieces','grams'=>125],['label'=>'8 pieces','grams'=>200]],
			'tonkatsu-deep-fried-pork-cutlet' => [['label'=>'1 cutlet','grams'=>150]],
			'tempura-mixed-5-pcs' => [['label'=>'5 pieces','grams'=>120]],
			'sukiyaki-beef-hot-pot' => [['label'=>'1 bowl','grams'=>300]],
			'onigiri-salmon' => [['label'=>'1 onigiri','grams'=>110]],
			'onigiri-tuna-mayo' => [['label'=>'1 onigiri','grams'=>110]],
			'udon-noodles-cooked' => [['label'=>'1 bowl','grams'=>250],['label'=>'1 portion','grams'=>200]],
			'soba-noodles-cooked' => [['label'=>'1 bowl','grams'=>200]],
			'mango-sticky-rice-khao-niao-mamuang' => [['label'=>'1 portion','grams'=>200]],

			// ── KOREAN ──
			'bibimbap-dolsot-stone-pot' => [['label'=>'1 bowl','grams'=>350]],
			'bulgogi-marinated-beef-bbq' => [['label'=>'1 portion','grams'=>150]],
			'samgyeopsal-grilled-pork-belly' => [['label'=>'1 portion','grams'=>150],['label'=>'3 slices','grams'=>100]],
			'tteokbokki-spicy-rice-cakes' => [['label'=>'1 bowl','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'gimbap-korean-rice-roll-1-roll' => [['label'=>'1 roll','grams'=>230],['label'=>'½ roll','grams'=>115]],
			'jajangmyeon-black-bean-noodles' => [['label'=>'1 bowl','grams'=>350]],
			'bingsu-patbingsu-red-bean-shaved-ice' => [['label'=>'1 bowl','grams'=>250]],
			'yangnyeom-chicken-sweet-spicy-fried' => [['label'=>'1 portion','grams'=>200]],
			'korean-fried-chicken-yangnyeom' => [['label'=>'1 portion','grams'=>200]],

			// ── CHINESE ──
			'kung-pao-chicken-sichuan' => [['label'=>'1 portion','grams'=>200]],
			'mapo-tofu-sichuan' => [['label'=>'1 portion','grams'=>200]],
			'sweet-and-sour-pork-guobaorou' => [['label'=>'1 portion','grams'=>200]],
			'red-braised-pork-belly-hong-shao-rou' => [['label'=>'1 portion','grams'=>150]],
			'cantonese-roast-duck' => [['label'=>'¼ duck','grams'=>200],['label'=>'1 portion','grams'=>120]],
			'char-siu-cantonese-bbq-pork' => [['label'=>'1 portion','grams'=>120]],
			'egg-tart-dan-tat-1pc' => [['label'=>'1 tart','grams'=>65]],
			'tangyuan-glutinous-rice-balls-3-pcs' => [['label'=>'3 balls','grams'=>90],['label'=>'6 balls','grams'=>180]],
			'jianbing-chinese-crepe' => [['label'=>'1 crepe','grams'=>200]],
			'rou-jia-mo-chinese-burger' => [['label'=>'1 burger','grams'=>150]],
			'cong-you-bing-scallion-pancake' => [['label'=>'1 pancake','grams'=>120]],
			'you-tiao-fried-dough-stick' => [['label'=>'1 stick','grams'=>50],['label'=>'2 sticks','grams'=>100]],
			'hot-and-sour-soup' => [['label'=>'1 bowl','grams'=>250]],

			// ── SOUTHEAST ASIAN ──
			'nasi-lemak-coconut-rice-full-set' => [['label'=>'1 plate','grams'=>300]],
			'char-kway-teow-penang-fried-noodles' => [['label'=>'1 plate','grams'=>300]],
			'roti-canai-malaysian-flatbread' => [['label'=>'1 roti','grams'=>100]],
			'satay-malaysian-chicken-10-sticks' => [['label'=>'10 sticks','grams'=>200],['label'=>'5 sticks','grams'=>100]],
			'nasi-goreng-indonesian-fried-rice' => [['label'=>'1 plate','grams'=>300]],
			'rendang-padang-west-sumatran-beef' => [['label'=>'1 portion','grams'=>150]],
			'gado-gado-indonesian-peanut-salad' => [['label'=>'1 plate','grams'=>200]],
			'satay-ayam-indonesian-chicken-peanut-sauce' => [['label'=>'5 sticks','grams'=>100],['label'=>'10 sticks','grams'=>200]],
			'adobo-chicken-filipino-national-dish' => [['label'=>'1 portion','grams'=>200]],
			'sinigang-na-baboy-sour-pork-soup' => [['label'=>'1 bowl','grams'=>350]],
			'lumpia-shanghai-fried-spring-rolls-5-pcs' => [['label'=>'5 pieces','grams'=>125]],
			'halo-halo-mixed-shaved-ice-dessert' => [['label'=>'1 cup','grams'=>250]],
			'fish-amok-khmer-steamed-curry' => [['label'=>'1 portion','grams'=>200]],
			'lahpet-thoke-tea-leaf-salad' => [['label'=>'1 portion','grams'=>120]],

			// ── MIDDLE EASTERN / ARABIAN ──
			'hummus' => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 portion','grams'=>70],['label'=>'1 cup','grams'=>200]],
			'falafel-fried' => [['label'=>'1 piece','grams'=>17],['label'=>'5 pieces','grams'=>85]],
			'shawarma-chicken' => [['label'=>'1 wrap','grams'=>250]],
			'shawarma-lamb' => [['label'=>'1 wrap','grams'=>250]],
			'mansaf-jordanian-lamb-yoghurt-rice' => [['label'=>'1 plate','grams'=>350]],
			'kabsa-saudi-rice-chicken' => [['label'=>'1 plate','grams'=>350]],
			'kushari-egyptian-rice-lentils' => [['label'=>'1 bowl','grams'=>300]],
			'ful-medames-egyptian-fava-beans' => [['label'=>'1 bowl','grams'=>200]],
			'luqaimat-sweet-dumplings' => [['label'=>'5 pieces','grams'=>60],['label'=>'10 pieces','grams'=>120]],
			'knafeh-nabulsieh-cheese-knafeh' => [['label'=>'1 slice','grams'=>100]],

			// ── TURKISH ──
			'doner-kebab-meat-only' => [['label'=>'1 portion','grams'=>120]],
			'lahmacun-turkish-pizza' => [['label'=>'1 lahmacun','grams'=>120]],
			'pide-turkish-flatbread-pizza' => [['label'=>'1 portion','grams'=>200]],
			'manti-turkish-dumplings' => [['label'=>'1 portion','grams'=>200]],
			'simit-turkish-sesame-ring' => [['label'=>'1 simit','grams'=>120]],
			'borek-cheese' => [['label'=>'1 portion','grams'=>150]],
			'gozleme-spinach-cheese' => [['label'=>'1 gozleme','grams'=>200]],
			'sutlac-turkish-rice-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'turkish-coffee-sade' => [['label'=>'1 cup','grams'=>60]],
			'ayran-turkish-yoghurt-drink' => [['label'=>'1 glass','grams'=>200]],

			// ── AFRICAN ──
			'jollof-rice-ghanaian' => [['label'=>'1 plate','grams'=>300]],
			'egusi-soup-melon-seed' => [['label'=>'1 bowl','grams'=>250]],
			'pounded-yam' => [['label'=>'1 portion','grams'=>200]],
			'fufu-cassava' => [['label'=>'1 portion','grams'=>200]],
			'suya-beef-skewers' => [['label'=>'3 sticks','grams'=>100],['label'=>'5 sticks','grams'=>170]],
			'bobotie-cape-malay-mince' => [['label'=>'1 portion','grams'=>250]],
			'tagine-chicken-preserved-lemon' => [['label'=>'1 portion','grams'=>250]],
			'couscous-moroccan-with-veg' => [['label'=>'1 plate','grams'=>300]],
			'injera-ethiopian-flatbread' => [['label'=>'1 piece','grams'=>100],['label'=>'1 full','grams'=>200]],
			'doro-wat-chicken-stew' => [['label'=>'1 portion','grams'=>200]],

			// ── LATIN AMERICAN ──
			'feijoada-black-bean-stew' => [['label'=>'1 bowl','grams'=>300]],
			'pao-de-queijo-cheese-bread' => [['label'=>'1 piece','grams'=>25],['label'=>'4 pieces','grams'=>100]],
			'picanha-grilled-rump-cap' => [['label'=>'1 portion','grams'=>150]],
			'empanada-argentina-beef' => [['label'=>'1 empanada','grams'=>120]],
			'asado-grilled-beef' => [['label'=>'1 portion','grams'=>200]],
			'ceviche-peruvian-mixed-fish' => [['label'=>'1 portion','grams'=>150]],
			'lomo-saltado-stir-fried-beef' => [['label'=>'1 plate','grams'=>300]],
			'arepas-con-queso' => [['label'=>'1 arepa','grams'=>150]],
			'bandeja-paisa-platter-per-100g-avg' => [['label'=>'1 plate','grams'=>450]],
			'gallo-pinto-costa-rican-rice-beans' => [['label'=>'1 plate','grams'=>250]],

			// ── CARIBBEAN ──
			'jerk-pork' => [['label'=>'1 portion','grams'=>150]],
			'curry-goat-jamaican' => [['label'=>'1 bowl','grams'=>250]],
			'oxtail-stew-jamaican' => [['label'=>'1 bowl','grams'=>250]],
			'jamaican-patty-beef' => [['label'=>'1 patty','grams'=>170]],
			'ropa-vieja-cuban-shredded-beef' => [['label'=>'1 portion','grams'=>200]],
			'mofongo-dominican-mashed-fried-plantain' => [['label'=>'1 portion','grams'=>200]],
			'pernil-pr-slow-roasted-pork-shoulder' => [['label'=>'1 portion','grams'=>150]],

			// ── EUROPEAN (REMAINING) ──
			'cevapi-serbian-10-pcs-with-lepinja' => [['label'=>'1 portion','grams'=>250]],
			'mici-mititei-romanian-grilled-sausages' => [['label'=>'3 pieces','grams'=>120],['label'=>'5 pieces','grams'=>200]],
			'cepelinai-potato-dumplings-national' => [['label'=>'1 piece','grams'=>150],['label'=>'2 pieces','grams'=>300]],
			'pierogi-potato-cheese' => [['label'=>'5 pieces','grams'=>200],['label'=>'8 pieces','grams'=>320]],
			'bryndzove-halusky-sheep-cheese-dumplings' => [['label'=>'1 portion','grams'=>250]],
			'svickova-na-smetane-sirloin-in-cream' => [['label'=>'1 plate','grams'=>300]],
			'varenyky-ukrainian-potato-cheese' => [['label'=>'6 pieces','grams'=>200],['label'=>'10 pieces','grams'=>330]],
			'banitsa-bulgarian-cheese-phyllo-pie' => [['label'=>'1 slice','grams'=>150]],
			'shopska-salata-bulgarian-national-salad' => [['label'=>'1 plate','grams'=>200]],
			'pastizzi-maltese-ricotta-pastry-1pc' => [['label'=>'1 pastizzi','grams'=>60],['label'=>'2 pastizzi','grams'=>120]],

			// ── RUSSIAN & CENTRAL ASIAN ──
			'borscht-russian-with-smetana' => [['label'=>'1 bowl','grams'=>250]],
			'pelmeni-siberian-pork-beef' => [['label'=>'10 pieces','grams'=>200],['label'=>'15 pieces','grams'=>300]],
			'blini-russian-buckwheat' => [['label'=>'3 blini','grams'=>100],['label'=>'5 blini','grams'=>170]],
			'syrniki-curd-cheese-fritters' => [['label'=>'3 pieces','grams'=>120]],
			'medovik-honey-cake' => [['label'=>'1 slice','grams'=>120]],
			'beshbarmak-national-dish-meat-noodles' => [['label'=>'1 plate','grams'=>350]],
			'plov-azerbaijani-lamb-saffron' => [['label'=>'1 plate','grams'=>300]],

			// ── AUSTRALIAN & NZ ──
			'meat-pie-aussie-standard' => [['label'=>'1 pie','grams'=>175]],
			'chicken-parmigiana' => [['label'=>'1 serve','grams'=>300]],
			'snag-bbq-sausage-in-bread' => [['label'=>'1 snag','grams'=>150]],
			'smashed-avo-on-toast' => [['label'=>'1 serve','grams'=>180]],
			'fairy-bread' => [['label'=>'1 slice','grams'=>25],['label'=>'3 slices','grams'=>75]],
			'hangi-earth-oven-mixed-per-100g' => [['label'=>'1 plate','grams'=>350]],
			'kumara-nz-sweet-potato-roasted' => [['label'=>'1 medium','grams'=>200]],

			// ── PACIFIC ISLANDS ──
			'laplap-vanuatu-national-dish-taro-banana' => [['label'=>'1 portion','grams'=>200]],
			'kokoda-fijian-raw-fish-in-coconut' => [['label'=>'1 bowl','grams'=>150]],
			'palusami-samoan-corned-beef-coconut' => [['label'=>'1 portion','grams'=>200]],
			'oka-ia-samoan-raw-fish-salad' => [['label'=>'1 bowl','grams'=>150]],
			'lu-pulu-corned-beef-in-taro-leaves' => [['label'=>'1 portion','grams'=>200]],

			// ── MONGOLIAN ──
			'buuz-mongolian-steamed-dumplings-1pc' => [['label'=>'1 buuz','grams'=>40],['label'=>'6 buuz','grams'=>240]],
			'khuushuur-mongolian-fried-meat-pastry' => [['label'=>'1 piece','grams'=>80],['label'=>'3 pieces','grams'=>240]],
			'tsuivan-mongolian-stir-fried-noodles-meat' => [['label'=>'1 plate','grams'=>300]],

			// ── ICELANDIC & GREENLANDIC ──
			'pylsur-icelandic-hot-dog' => [['label'=>'1 hot dog','grams'=>100]],
			'kleinur-icelandic-twisted-doughnut' => [['label'=>'1 piece','grams'=>35],['label'=>'3 pieces','grams'=>105]],
			'suaasat-greenlandic-seal-caribou-soup' => [['label'=>'1 bowl','grams'=>300]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 60 );
	}

	/** Seed v61: Serving sizes batch 4 — remaining foods. */
	public static function seed_v61(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 61 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── RAW VEGETABLES (remaining) ──
			'celery'                  => [['label'=>'1 stalk','grams'=>40],['label'=>'3 stalks','grams'=>120]],
			'spring-onion-raw'        => [['label'=>'1 spring onion','grams'=>15],['label'=>'3 spring onions','grams'=>45]],
			'shallot-raw'             => [['label'=>'1 shallot','grams'=>30]],
			'leek-raw'                => [['label'=>'1 leek','grams'=>90]],
			'beetroot'                => [['label'=>'1 medium','grams'=>80],['label'=>'1 small','grams'=>60]],
			'radish'                  => [['label'=>'1 radish','grams'=>10],['label'=>'5 radishes','grams'=>50]],
			'asparagus'               => [['label'=>'5 spears','grams'=>75],['label'=>'1 bunch','grams'=>150]],
			'green-beans'             => [['label'=>'1 cup','grams'=>100],['label'=>'1 handful','grams'=>60]],
			'pak-choi-bok-choy'       => [['label'=>'1 head','grams'=>170],['label'=>'1 cup chopped','grams'=>70]],
			'fennel-raw'              => [['label'=>'1 bulb','grams'=>235],['label'=>'½ bulb','grams'=>117]],
			'celeriac-raw'            => [['label'=>'½ medium','grams'=>230]],
			'butternut-squash-raw'    => [['label'=>'1 cup cubed','grams'=>140]],
			'courgette-zucchini'      => [['label'=>'1 medium','grams'=>200],['label'=>'1 cup sliced','grams'=>115]],
			'ginger-root'             => [['label'=>'1 inch piece','grams'=>11],['label'=>'1 tablespoon grated','grams'=>6]],
			'chilli-pepper-red'       => [['label'=>'1 chilli','grams'=>15]],
			'jalapeno-pepper-raw'     => [['label'=>'1 pepper','grams'=>14]],
			'rocket-arugula'          => [['label'=>'1 cup','grams'=>20],['label'=>'1 handful','grams'=>10]],
			'watercress'              => [['label'=>'1 cup','grams'=>34],['label'=>'1 bunch','grams'=>85]],
			'kale'                    => [['label'=>'1 cup chopped','grams'=>67],['label'=>'1 leaf','grams'=>20]],

			// ── MORE FRUIT ──
			'fig-fresh'               => [['label'=>'1 fig','grams'=>50],['label'=>'3 figs','grams'=>150]],
			'plum'                    => [['label'=>'1 plum','grams'=>65]],
			'apricot'                 => [['label'=>'1 apricot','grams'=>35],['label'=>'3 apricots','grams'=>105]],
			'cherry'                  => [['label'=>'10 cherries','grams'=>70],['label'=>'1 cup','grams'=>140]],
			'raspberry'               => [['label'=>'1 cup','grams'=>125],['label'=>'1 handful','grams'=>30]],
			'blackberry'              => [['label'=>'1 cup','grams'=>145],['label'=>'1 handful','grams'=>35]],
			'pomegranate'             => [['label'=>'1 pomegranate','grams'=>175],['label'=>'½ cup seeds','grams'=>87]],
			'passion-fruit'           => [['label'=>'1 fruit','grams'=>18]],
			'lychee'                  => [['label'=>'5 lychees','grams'=>50],['label'=>'10 lychees','grams'=>100]],
			'dragon-fruit-pitaya'     => [['label'=>'1 fruit','grams'=>200]],
			'guava'                   => [['label'=>'1 guava','grams'=>55]],
			'papaya'                  => [['label'=>'1 cup cubed','grams'=>145],['label'=>'½ papaya','grams'=>150]],
			'coconut-flesh-raw'       => [['label'=>'1 piece','grams'=>45],['label'=>'1 cup shredded','grams'=>80]],
			'clementine'              => [['label'=>'1 clementine','grams'=>74],['label'=>'2 clementines','grams'=>148]],
			'grapefruit'              => [['label'=>'½ grapefruit','grams'=>125],['label'=>'1 grapefruit','grams'=>250]],
			'plantain-raw'            => [['label'=>'1 plantain','grams'=>180]],

			// ── COOKED VEG DISHES ──
			'roasted-cauliflower'     => [['label'=>'1 cup','grams'=>125]],
			'grilled-courgette'       => [['label'=>'1 medium','grams'=>180]],
			'roasted-beetroot'        => [['label'=>'3 wedges','grams'=>100]],
			'mushy-peas'              => [['label'=>'1 portion','grams'=>100]],
			'ratatouille'             => [['label'=>'1 portion','grams'=>200],['label'=>'1 bowl','grams'=>250]],
			'cauliflower-cheese'      => [['label'=>'1 portion','grams'=>200]],
			'dauphinoise-potatoes'    => [['label'=>'1 portion','grams'=>150]],
			'potato-wedges-baked'     => [['label'=>'1 portion','grams'=>150]],
			'roasted-parsnip'         => [['label'=>'3 pieces','grams'=>100]],
			'colcannon'               => [['label'=>'1 portion','grams'=>200]],
			'bubble-and-squeak'       => [['label'=>'1 portion','grams'=>200]],
			'patatas-bravas'          => [['label'=>'1 portion','grams'=>200]],
			'tostones-twice-fried-green-plantain' => [['label'=>'5 pieces','grams'=>100]],
			'kelewele-spiced-fried-plantain' => [['label'=>'1 portion','grams'=>150]],

			// ── MORE CHEESE ──
			'red-leicester'           => [['label'=>'1 slice','grams'=>20],['label'=>'1 oz','grams'=>28]],
			'wensleydale'             => [['label'=>'1 slice','grams'=>20]],
			'stilton-white'           => [['label'=>'1 portion','grams'=>30]],
			'gruyere'                 => [['label'=>'1 slice','grams'=>20],['label'=>'1 oz','grams'=>28]],
			'manchego-cheese-curado'  => [['label'=>'1 slice','grams'=>20],['label'=>'1 oz','grams'=>28]],
			'mascarpone'              => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'feta-cheese'             => [['label'=>'1 oz','grams'=>28],['label'=>'1 cup crumbled','grams'=>150]],
			'cottage-cheese'          => [['label'=>'½ cup','grams'=>113],['label'=>'1 tablespoon','grams'=>14]],
			'vegan-cheese-cheddar-style' => [['label'=>'1 slice','grams'=>20]],

			// ── SPREADS & JAMS ──
			'strawberry-jam'          => [['label'=>'1 tablespoon','grams'=>20],['label'=>'1 teaspoon','grams'=>7]],
			'orange-marmalade'        => [['label'=>'1 tablespoon','grams'=>20],['label'=>'1 teaspoon','grams'=>7]],
			'lemon-curd'              => [['label'=>'1 tablespoon','grams'=>15]],
			'chocolate-hazelnut-spread' => [['label'=>'1 tablespoon','grams'=>18],['label'=>'1 teaspoon','grams'=>6]],
			'dulce-de-leche'          => [['label'=>'1 tablespoon','grams'=>20]],

			// ── MORE DESSERTS & PASTRIES ──
			'croissant-chocolate'     => [['label'=>'1 croissant','grams'=>75]],
			'pain-au-raisin'          => [['label'=>'1 pastry','grams'=>90]],
			'cinnamon-roll'           => [['label'=>'1 roll','grams'=>100]],
			'danish-pastry'           => [['label'=>'1 pastry','grams'=>70]],
			'muffin-blueberry'        => [['label'=>'1 muffin','grams'=>110]],
			'eclair-chocolate'        => [['label'=>'1 eclair','grams'=>80]],
			'profiteroles'            => [['label'=>'3 profiteroles','grams'=>90],['label'=>'6 profiteroles','grams'=>180]],
			'rice-pudding'            => [['label'=>'1 bowl','grams'=>200],['label'=>'1 pot','grams'=>150]],
			'custard-ready-made'      => [['label'=>'1 portion','grams'=>120],['label'=>'1 jug','grams'=>200]],
			'sorbet-lemon'            => [['label'=>'1 scoop','grams'=>74],['label'=>'2 scoops','grams'=>148]],
			'frozen-yoghurt'          => [['label'=>'1 scoop','grams'=>70],['label'=>'1 cup','grams'=>140]],
			'panettone'               => [['label'=>'1 slice','grams'=>80]],
			'stollen-christmas-bread' => [['label'=>'1 slice','grams'=>70]],
			'cannoli-sicilian'        => [['label'=>'1 cannoli','grams'=>60]],
			'kunafa-cheese'           => [['label'=>'1 slice','grams'=>100]],
			'basbousa-semolina-cake'  => [['label'=>'1 piece','grams'=>60]],
			'tres-leches-cake'        => [['label'=>'1 slice','grams'=>120]],
			'brigadeiro-chocolate-truffle-1pc' => [['label'=>'1 piece','grams'=>15],['label'=>'5 pieces','grams'=>75]],
			'alfajor-dulce-de-leche'  => [['label'=>'1 alfajor','grams'=>50]],

			// ── BREAKFAST ITEMS ──
			'bircher-muesli'          => [['label'=>'1 bowl','grams'=>200]],
			'acai-bowl'               => [['label'=>'1 bowl','grams'=>250]],
			'granola-bar'             => [['label'=>'1 bar','grams'=>30]],
			'protein-bar'             => [['label'=>'1 bar','grams'=>60]],
			'energy-ball-date-based'  => [['label'=>'1 ball','grams'=>20],['label'=>'3 balls','grams'=>60]],
			'french-toast'            => [['label'=>'2 slices','grams'=>120]],
			'eggs-benedict'           => [['label'=>'1 serving','grams'=>200]],
			'big-breakfast-aussie-cafe' => [['label'=>'1 plate','grams'=>400]],

			// ── MORE DRINKS ──
			'lemon-lime-bitters-pub'  => [['label'=>'1 glass','grams'=>250]],
			'milo-made-with-milk-per-cup' => [['label'=>'1 cup','grams'=>250]],
			'ginger-beer'             => [['label'=>'1 bottle','grams'=>330],['label'=>'1 glass','grams'=>250]],
			'tonic-water'             => [['label'=>'1 bottle','grams'=>200],['label'=>'1 can','grams'=>150]],
			'elderflower-cordial-diluted' => [['label'=>'1 glass','grams'=>250]],
			'pina-colada-per-glass'   => [['label'=>'1 glass','grams'=>250]],
			'mojito-cuban-cocktail-per-glass' => [['label'=>'1 glass','grams'=>200]],
			'sangria-per-glass-200ml' => [['label'=>'1 glass','grams'=>200]],
			'irish-coffee'            => [['label'=>'1 glass','grams'=>180]],
			'guarana-soft-drink'      => [['label'=>'1 can','grams'=>350]],
			'ramune-marble-soda'      => [['label'=>'1 bottle','grams'=>200]],
			'calpis-milk-based-drink' => [['label'=>'1 glass','grams'=>200]],
			'amazake-sweet-rice-drink' => [['label'=>'1 cup','grams'=>200]],
			'kvas-bread-fermented-drink' => [['label'=>'1 glass','grams'=>250]],

			// ── GAME & SPECIALTY MEATS ──
			'rabbit-roasted'          => [['label'=>'1 portion','grams'=>150]],
			'pheasant-roasted'        => [['label'=>'1 portion','grams'=>150]],
			'ostrich-steak-grilled'   => [['label'=>'1 steak','grams'=>150]],
			'bison-steak-grilled'     => [['label'=>'1 steak','grams'=>170]],
			'goat-meat-roasted'       => [['label'=>'1 portion','grams'=>120]],
			'emu-steak-grilled'       => [['label'=>'1 steak','grams'=>150]],
			'crocodile-meat-grilled'  => [['label'=>'1 portion','grams'=>120]],

			// ── OFFAL ──
			'chicken-liver-fried'     => [['label'=>'1 portion','grams'=>100]],
			'lamb-liver-fried'        => [['label'=>'1 portion','grams'=>100]],
			'black-pudding'           => [['label'=>'1 slice','grams'=>30],['label'=>'2 slices','grams'=>60]],
			'haggis'                  => [['label'=>'1 portion','grams'=>150]],

			// ── FRESHWATER FISH ──
			'rainbow-trout-grilled'   => [['label'=>'1 fillet','grams'=>140]],
			'tilapia-grilled'         => [['label'=>'1 fillet','grams'=>115]],

			// ── SEAWEED ──
			'nori-dried-sheet-per-100g' => [['label'=>'1 sheet','grams'=>3],['label'=>'5 sheets','grams'=>15]],
			'wakame-rehydrated'       => [['label'=>'1 tablespoon dry','grams'=>3],['label'=>'¼ cup rehydrated','grams'=>30]],
			'spirulina-dried-powder'  => [['label'=>'1 teaspoon','grams'=>3],['label'=>'1 tablespoon','grams'=>7]],

			// ── TEA & COFFEE (remaining) ──
			'earl-grey-tea-brewed-no-sugar' => [['label'=>'1 cup','grams'=>240]],
			'iced-latte-semi-skimmed' => [['label'=>'1 cup','grams'=>350]],
			'mocha-with-whipped-cream' => [['label'=>'1 cup','grams'=>300]],
			'turmeric-latte-golden-milk' => [['label'=>'1 cup','grams'=>250]],
			'london-fog-earl-grey-latte' => [['label'=>'1 cup','grams'=>300]],

			// ── SUGAR & SWEETENERS ──
			'caster-sugar'            => [['label'=>'1 teaspoon','grams'=>4],['label'=>'1 tablespoon','grams'=>13]],
			'demerara-sugar'          => [['label'=>'1 teaspoon','grams'=>5],['label'=>'1 tablespoon','grams'=>15]],
			'stevia-granulated-blend' => [['label'=>'1 teaspoon','grams'=>1]],
			'agave-nectar'            => [['label'=>'1 tablespoon','grams'=>21],['label'=>'1 teaspoon','grams'=>7]],

			// ── FERMENTED FOODS ──
			'sauerkraut'              => [['label'=>'1 tablespoon','grams'=>15],['label'=>'½ cup','grams'=>70]],
			'kimchi-napa-cabbage-tongbaechu' => [['label'=>'1 tablespoon','grams'=>15],['label'=>'½ cup','grams'=>75]],
			'kefir-plain'             => [['label'=>'1 glass','grams'=>250],['label'=>'1 cup','grams'=>240]],
			'natto-fermented-soybean' => [['label'=>'1 pack','grams'=>50]],
			'pickled-gherkins'        => [['label'=>'1 gherkin','grams'=>30],['label'=>'3 gherkins','grams'=>90]],
			'pickled-onions'          => [['label'=>'1 onion','grams'=>15],['label'=>'3 onions','grams'=>45]],
			'olives-green-in-brine'   => [['label'=>'5 olives','grams'=>20],['label'=>'10 olives','grams'=>40]],
			'olives-black-in-brine'   => [['label'=>'5 olives','grams'=>20],['label'=>'10 olives','grams'=>40]],

			// ── PLANT-BASED ──
			'tempeh'                  => [['label'=>'1 portion','grams'=>100],['label'=>'½ block','grams'=>150]],
			'seitan'                  => [['label'=>'1 portion','grams'=>85]],
			'beyond-burger-raw'       => [['label'=>'1 patty','grams'=>113]],
			'vegan-sausage-plant-based' => [['label'=>'1 sausage','grams'=>60],['label'=>'2 sausages','grams'=>120]],
			'nutritional-yeast-flakes' => [['label'=>'1 tablespoon','grams'=>5],['label'=>'2 tablespoons','grams'=>10]],
			'coconut-yoghurt'         => [['label'=>'1 pot','grams'=>150]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 61 );
	}

	/** Seed v62: Serving sizes batch 5 — deep coverage remaining foods. */
	public static function seed_v62(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 62 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── FRENCH DISHES ──
			'blanquette-de-veau-veal-stew' => [['label'=>'1 portion','grams'=>250]],
			'pot-au-feu-french-boiled-dinner' => [['label'=>'1 bowl','grams'=>350]],
			'steak-frites-with-fries' => [['label'=>'1 plate','grams'=>350]],
			'steak-tartare-raw-beef' => [['label'=>'1 portion','grams'=>150]],
			'moules-marinieres-french-style' => [['label'=>'1 bowl','grams'=>300]],
			'galette-bretonne-buckwheat-ham-cheese' => [['label'=>'1 galette','grams'=>200]],
			'tartiflette-savoyard-potato-bake' => [['label'=>'1 portion','grams'=>250]],
			'souffle-au-chocolat' => [['label'=>'1 ramekin','grams'=>120]],
			'mousse-au-chocolat' => [['label'=>'1 pot','grams'=>100]],
			'clafoutis-cherry' => [['label'=>'1 slice','grams'=>120]],
			'canele-bordeaux-custard-cake' => [['label'=>'1 canelé','grams'=>55]],
			'financier-almond-cake' => [['label'=>'1 piece','grams'=>30]],
			'tarte-au-citron-lemon-tart' => [['label'=>'1 slice','grams'=>100]],
			'creme-caramel' => [['label'=>'1 ramekin','grams'=>120]],
			'kouign-amann-breton-butter-cake' => [['label'=>'1 piece','grams'=>80]],

			// ── SPANISH DISHES ──
			'tortilla-espanola-potato-omelette' => [['label'=>'1 slice','grams'=>100],['label'=>'1 wedge','grams'=>150]],
			'gambas-al-ajillo-garlic-prawns' => [['label'=>'1 portion','grams'=>120]],
			'paella-valenciana-rabbit-chicken-snails' => [['label'=>'1 plate','grams'=>350]],
			'fideua-catalan-noodle-paella' => [['label'=>'1 plate','grams'=>300]],
			'cochinillo-asado-roast-suckling-pig' => [['label'=>'1 portion','grams'=>150]],
			'flan-spanish-egg-custard' => [['label'=>'1 flan','grams'=>120]],
			'turron-de-jijona-soft-nougat' => [['label'=>'1 slice','grams'=>30]],
			'ensaimada-mallorcan-pastry' => [['label'=>'1 pastry','grams'=>100]],
			'crema-catalana' => [['label'=>'1 ramekin','grams'=>120]],

			// ── PORTUGUESE DISHES ──
			'pasteis-de-nata-custard-tart-belem' => [['label'=>'1 tart','grams'=>65],['label'=>'2 tarts','grams'=>130]],
			'bacalhau-a-bras-shredded-cod-egg' => [['label'=>'1 plate','grams'=>250]],
			'caldo-verde-traditional-with-chourico' => [['label'=>'1 bowl','grams'=>250]],
			'francesinha-porto-sandwich' => [['label'=>'1 francesinha','grams'=>400]],
			'sardinhas-assadas-grilled-sardines' => [['label'=>'3 sardines','grams'=>120],['label'=>'5 sardines','grams'=>200]],
			'cozido-a-portuguesa-mixed-boil-up' => [['label'=>'1 plate','grams'=>350]],
			'pasteis-de-bacalhau-cod-fritters' => [['label'=>'2 fritters','grams'=>60],['label'=>'4 fritters','grams'=>120]],
			'bola-de-berlim-portuguese-doughnut' => [['label'=>'1 doughnut','grams'=>80]],

			// ── ITALIAN (remaining) ──
			'focaccia-rosemary' => [['label'=>'1 slice','grams'=>70],['label'=>'1 piece','grams'=>100]],
			'ciabatta' => [['label'=>'1 roll','grams'=>90],['label'=>'½ loaf','grams'=>150]],
			'panna-cotta' => [['label'=>'1 pot','grams'=>130]],
			'cannoli-sicilian' => [['label'=>'1 cannoli','grams'=>60]],
			'panettone' => [['label'=>'1 slice','grams'=>80]],
			'ossobuco' => [['label'=>'1 shank','grams'=>250]],
			'bruschetta-tomato' => [['label'=>'1 piece','grams'=>60],['label'=>'3 pieces','grams'=>180]],

			// ── GERMAN/AUSTRIAN ──
			'bratwurst-grilled' => [['label'=>'1 sausage','grams'=>85],['label'=>'2 sausages','grams'=>170]],
			'currywurst' => [['label'=>'1 portion','grams'=>200]],
			'lebkuchen-gingerbread' => [['label'=>'1 piece','grams'=>40]],
			'apfelstrudel-apple-strudel' => [['label'=>'1 slice','grams'=>120]],
			'kaiserschmarrn-torn-pancake' => [['label'=>'1 portion','grams'=>200]],

			// ── SCANDINAVIAN ──
			'kanelbulle-swedish-cinnamon-bun' => [['label'=>'1 bun','grams'=>80]],
			'kladdkaka-swedish-chocolate-cake' => [['label'=>'1 slice','grams'=>80]],
			'semla-swedish-cream-bun' => [['label'=>'1 bun','grams'=>100]],
			'karjalanpiirakka-karelian-pie-1pc' => [['label'=>'1 pie','grams'=>50],['label'=>'3 pies','grams'=>150]],
			'gravlax-cured-salmon' => [['label'=>'3 slices','grams'=>50],['label'=>'1 portion','grams'=>80]],

			// ── IRISH ──
			'irish-stew-lamb' => [['label'=>'1 bowl','grams'=>300]],
			'soda-bread-white' => [['label'=>'1 slice','grams'=>60]],
			'boxty-potato-pancake' => [['label'=>'1 boxty','grams'=>100]],
			'guinness-stew-beef' => [['label'=>'1 bowl','grams'=>300]],

			// ── SCOTTISH/WELSH ──
			'cranachan' => [['label'=>'1 glass','grams'=>150]],
			'tattie-scone' => [['label'=>'1 scone','grams'=>65]],
			'welsh-cakes' => [['label'=>'1 cake','grams'=>25],['label'=>'3 cakes','grams'=>75]],
			'bara-brith-fruit-loaf' => [['label'=>'1 slice','grams'=>50]],

			// ── BRITISH REGIONAL ──
			'eccles-cake' => [['label'=>'1 cake','grams'=>60]],
			'bakewell-tart' => [['label'=>'1 slice','grams'=>85]],
			'battenberg-cake' => [['label'=>'1 slice','grams'=>50]],
			'kendal-mint-cake' => [['label'=>'1 bar','grams'=>85],['label'=>'½ bar','grams'=>42]],
			'parkin-yorkshire-oatcake' => [['label'=>'1 slice','grams'=>60]],
			'cornish-pasty' => [['label'=>'1 pasty','grams'=>250]],
			'scotch-egg' => [['label'=>'1 scotch egg','grams'=>113]],

			// ── DUTCH/BELGIAN ──
			'bitterballen-3-pcs' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'belgian-waffle-liege' => [['label'=>'1 waffle','grams'=>90]],
			'stroopwafel' => [['label'=>'1 waffle','grams'=>30]],
			'speculaas-biscuit' => [['label'=>'1 biscuit','grams'=>10],['label'=>'3 biscuits','grams'=>30]],

			// ── GEORGIAN ──
			'khachapuri-adjaruli-cheese-boat-bread' => [['label'=>'1 boat','grams'=>350]],
			'khinkali-georgian-soup-dumplings-1pc' => [['label'=>'1 piece','grams'=>60],['label'=>'5 pieces','grams'=>300]],
			'churchkhela-walnut-grape-candy' => [['label'=>'1 piece','grams'=>65]],

			// ── AFGHAN ──
			'kabuli-pulao-afghan-national-dish' => [['label'=>'1 plate','grams'=>350]],
			'mantu-afghan-dumplings-with-yoghurt' => [['label'=>'1 portion','grams'=>200]],
			'bolani-stuffed-flatbread' => [['label'=>'1 bolani','grams'=>120]],
			'chapli-kebab-spiced-meat-patty' => [['label'=>'1 kebab','grams'=>100]],

			// ── SRI LANKAN ──
			'rice-and-curry-sri-lankan-full-plate-avg' => [['label'=>'1 plate','grams'=>350]],
			'kottu-roti-sri-lankan-chopped-roti' => [['label'=>'1 plate','grams'=>300]],
			'hoppers-sri-lankan-rice-bowl-pancake' => [['label'=>'1 hopper','grams'=>60],['label'=>'2 hoppers','grams'=>120]],
			'string-hoppers-idiyappam-sri-lankan' => [['label'=>'5 pieces','grams'=>100],['label'=>'10 pieces','grams'=>200]],

			// ── TAIWANESE ──
			'beef-noodle-soup-taiwanese' => [['label'=>'1 bowl','grams'=>500]],
			'gua-bao-taiwanese-pork-belly-bun' => [['label'=>'1 bun','grams'=>120]],
			'pineapple-cake-taiwanese-1pc' => [['label'=>'1 cake','grams'=>40],['label'=>'3 cakes','grams'=>120]],

			// ── LEBANESE/ISRAELI ──
			'tabbouleh-lebanese-traditional' => [['label'=>'1 portion','grams'=>130]],
			'manakish-zaatar-lebanese' => [['label'=>'1 manakish','grams'=>150]],
			'sabich-iraqi-israeli-aubergine-pitta' => [['label'=>'1 sabich','grams'=>250]],
			'shakshuka-israeli-classic' => [['label'=>'1 portion','grams'=>200]],
			'rugelach-israeli-crescent-pastry' => [['label'=>'1 piece','grams'=>30],['label'=>'4 pieces','grams'=>120]],

			// ── CENTRAL AMERICAN ──
			'pupusa-salvadoran-cheese-bean' => [['label'=>'1 pupusa','grams'=>120],['label'=>'2 pupusas','grams'=>240]],
			'baleada-honduran-bean-cheese-tortilla' => [['label'=>'1 baleada','grams'=>180]],
			'gallo-pinto-costa-rican-rice-beans' => [['label'=>'1 plate','grams'=>250]],

			// ── HAWAIIAN ──
			'poke-bowl-hawaiian-tuna' => [['label'=>'1 bowl','grams'=>350]],
			'loco-moco-hawaiian-rice-burger-egg-gravy' => [['label'=>'1 plate','grams'=>400]],
			'spam-musubi-hawaiian-1pc' => [['label'=>'1 piece','grams'=>100],['label'=>'2 pieces','grams'=>200]],

			// ── REMAINING MISC ──
			'baursak-fried-dough-kazakh' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'boorsok-kyrgyz-fried-dough' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'qurutob-tajik-national-dish-bread-yoghurt' => [['label'=>'1 plate','grams'=>300]],
			'dograma-turkmen-bread-meat-soup' => [['label'=>'1 bowl','grams'=>300]],
			'tavce-gravce-macedonian-baked-beans' => [['label'=>'1 bowl','grams'=>200]],
			'byrek-albanian-phyllo-pie-cheese' => [['label'=>'1 slice','grams'=>120]],
			'burek-bosnian-meat-spiral-pie' => [['label'=>'1 slice','grams'=>150]],
			'pastrmajlija-macedonian-meat-pizza' => [['label'=>'1 portion','grams'=>200]],
			'conch-salad-bahamian-raw' => [['label'=>'1 bowl','grams'=>150]],
			'conch-fritters-bahamian' => [['label'=>'5 fritters','grams'=>100]],
			'oil-down-grenadian-national-dish' => [['label'=>'1 bowl','grams'=>300]],
			'griot-haitian-fried-pork' => [['label'=>'1 portion','grams'=>150]],
			'la-bandera-rice-beans-meat-national' => [['label'=>'1 plate','grams'=>350]],
			'doubles-trini-curried-chickpea-in-bara' => [['label'=>'1 doubles','grams'=>150]],
			'cachupa-cape-verdean-corn-bean-stew' => [['label'=>'1 bowl','grams'=>300]],
			'romazava-malagasy-beef-greens-stew' => [['label'=>'1 bowl','grams'=>250]],
			'bougna-kanak-earth-oven-stew' => [['label'=>'1 portion','grams'=>250]],
			'encebollado-ecuadorian-tuna-soup' => [['label'=>'1 bowl','grams'=>300]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 62 );
	}

	/** Seed v63: Serving sizes batch 6 — final sweep. */
	public static function seed_v63(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 63 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── BANGLADESHI (remaining) ──
			'masoor-dal-red-lentil-bangladeshi' => [['label'=>'1 bowl','grams'=>200]],
			'mung-dal-yellow-with-garlic-tarka' => [['label'=>'1 bowl','grams'=>200]],
			'cholar-dal-bengal-gram-coconut' => [['label'=>'1 bowl','grams'=>200]],
			'aloo-bharta-mashed-potato-with-mustard-oil' => [['label'=>'1 portion','grams'=>100]],
			'begun-bharta-mashed-aubergine' => [['label'=>'1 portion','grams'=>100]],
			'murgi-roast-bangladeshi-chicken-roast' => [['label'=>'1 portion','grams'=>200]],
			'kala-bhuna-chittagong-dry-beef-curry' => [['label'=>'1 portion','grams'=>200]],
			'kosha-mangsho-slow-cooked-mutton' => [['label'=>'1 bowl','grams'=>200]],
			'tehari-beef-with-rice-old-dhaka' => [['label'=>'1 plate','grams'=>350]],
			'moghlai-paratha-egg-stuffed-paratha' => [['label'=>'1 paratha','grams'=>120]],
			'jorda-sweet-saffron-rice' => [['label'=>'1 portion','grams'=>100]],
			'shemai-vermicelli-milk-dessert' => [['label'=>'1 bowl','grams'=>150]],
			'chomchom-oblong-cheese-sweet' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'kalojam-dark-gulab-jamun' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'patishapta-stuffed-crepe-with-kheer' => [['label'=>'2 pieces','grams'=>100]],
			'bhapa-pitha-steamed-rice-cake' => [['label'=>'1 piece','grams'=>60],['label'=>'3 pieces','grams'=>180]],
			'chitoi-pitha-rice-pancake-with-coconut' => [['label'=>'1 piece','grams'=>50],['label'=>'3 pieces','grams'=>150]],
			'nakshi-pitha-decorated-rice-cake' => [['label'=>'1 piece','grams'=>40]],
			'narkel-naru-coconut-balls' => [['label'=>'1 ball','grams'=>20],['label'=>'5 balls','grams'=>100]],
			'til-er-naru-sesame-seed-balls' => [['label'=>'1 ball','grams'=>15],['label'=>'5 balls','grams'=>75]],
			'borhani-spiced-yoghurt-drink' => [['label'=>'1 glass','grams'=>200]],
			'faluda-rose-milk-with-vermicelli' => [['label'=>'1 glass','grams'=>250]],
			'jhalmuri-spiced-puffed-rice-mix' => [['label'=>'1 portion','grams'=>100]],
			'chanachur-bombay-mix-bangladeshi' => [['label'=>'1 handful','grams'=>30],['label'=>'1 bowl','grams'=>80]],
			'dim-er-devil-spiced-scotch-egg' => [['label'=>'1 piece','grams'=>80]],
			'chilli-chicken-bangladeshi-chinese' => [['label'=>'1 portion','grams'=>200]],
			'chicken-lollipop' => [['label'=>'3 pieces','grams'=>100],['label'=>'6 pieces','grams'=>200]],
			'alur-chop-potato-chop-croquette' => [['label'=>'1 piece','grams'=>60],['label'=>'3 pieces','grams'=>180]],
			'macher-chop-fish-cutlet' => [['label'=>'1 piece','grams'=>60],['label'=>'2 pieces','grams'=>120]],

			// ── INDIAN (remaining) ──
			'paneer-tikka-grilled-cheese' => [['label'=>'1 portion','grams'=>120],['label'=>'5 cubes','grams'=>100]],
			'malai-kofta-cream-cheese-dumplings' => [['label'=>'1 portion','grams'=>200]],
			'bhindi-masala-okra-curry' => [['label'=>'1 portion','grams'=>150]],
			'baingan-bharta-smoky-aubergine' => [['label'=>'1 portion','grams'=>150]],
			'fish-curry-kerala-coconut' => [['label'=>'1 bowl','grams'=>250]],
			'prawn-malai-curry-bengali' => [['label'=>'1 portion','grams'=>200]],
			'keema-naan-minced-meat-stuffed' => [['label'=>'1 naan','grams'=>150]],
			'jeera-rice-cumin-rice' => [['label'=>'1 plate','grams'=>200],['label'=>'1 cup','grams'=>185]],
			'rasgulla-bengali-cheese-balls' => [['label'=>'1 piece','grams'=>40],['label'=>'3 pieces','grams'=>120]],
			'barfi-milk-fudge' => [['label'=>'1 piece','grams'=>25],['label'=>'4 pieces','grams'=>100]],
			'laddu-besan-chickpea-flour' => [['label'=>'1 laddu','grams'=>30],['label'=>'3 laddus','grams'=>90]],
			'payasam-south-indian-vermicelli' => [['label'=>'1 bowl','grams'=>150]],
			'filter-coffee-south-indian' => [['label'=>'1 cup','grams'=>150]],
			'nimbu-pani-lemon-water' => [['label'=>'1 glass','grams'=>250]],

			// ── PAKISTANI (remaining) ──
			'nihari-slow-cooked-beef-stew' => [['label'=>'1 bowl','grams'=>250]],
			'lahori-fish-fry' => [['label'=>'1 piece','grams'=>150]],
			'sajji-whole-roasted-lamb-chicken' => [['label'=>'1 portion','grams'=>200]],
			'paye-trotters-soup' => [['label'=>'1 bowl','grams'=>250]],
			'bun-kebab-pakistani-street-burger' => [['label'=>'1 bun kebab','grams'=>150]],
			'gol-gappay-pani-puri-pakistani' => [['label'=>'6 pieces','grams'=>100]],
			'puri-halwa-breakfast-fried-bread-semolina' => [['label'=>'1 portion','grams'=>150]],
			'naan-khatai-cardamom-shortbread' => [['label'=>'1 biscuit','grams'=>20],['label'=>'4 biscuits','grams'=>80]],
			'doodh-patti-milk-tea' => [['label'=>'1 cup','grams'=>150]],

			// ── BHUTANESE ──
			'ema-datshi-chilli-cheese-stew' => [['label'=>'1 portion','grams'=>150]],
			'kewa-datshi-potato-cheese' => [['label'=>'1 portion','grams'=>200]],
			'phaksha-paa-pork-with-chilli' => [['label'=>'1 portion','grams'=>150]],
			'red-rice-bhutanese-cooked' => [['label'=>'1 cup','grams'=>185],['label'=>'1 bowl','grams'=>250]],
			'suja-bhutanese-butter-tea' => [['label'=>'1 cup','grams'=>150]],

			// ── MYANMAR ──
			'mohinga-fish-noodle-soup-national-dish' => [['label'=>'1 bowl','grams'=>350]],
			'shan-noodles-shan-khaut-swe' => [['label'=>'1 plate','grams'=>250]],
			'wet-thar-hin-pork-curry-burmese' => [['label'=>'1 portion','grams'=>150]],
			'lahpet-thoke-tea-leaf-salad' => [['label'=>'1 portion','grams'=>120]],
			'mont-lone-yay-paw-glutinous-rice-balls' => [['label'=>'3 balls','grams'=>60],['label'=>'6 balls','grams'=>120]],

			// ── LAOTIAN ──
			'khao-niaw-lao-sticky-rice' => [['label'=>'1 basket','grams'=>150],['label'=>'1 cup','grams'=>185]],
			'laab-lao-minced-meat-salad' => [['label'=>'1 portion','grams'=>120]],
			'ping-kai-lao-grilled-chicken' => [['label'=>'1 portion','grams'=>150]],
			'sai-oua-lao-herb-sausage' => [['label'=>'1 sausage','grams'=>80]],

			// ── CAMBODIAN ──
			'fish-amok-khmer-steamed-curry' => [['label'=>'1 portion','grams'=>200]],
			'lok-lak-stir-fried-beef-pepper-lime' => [['label'=>'1 plate','grams'=>250]],
			'bai-sach-chrouk-pork-rice-breakfast' => [['label'=>'1 plate','grams'=>300]],
			'num-pang-khmer-baguette-sandwich' => [['label'=>'1 sandwich','grams'=>200]],

			// ── VIETNAMESE (remaining) ──
			'banh-mi-classic-pork' => [['label'=>'1 sandwich','grams'=>250]],
			'com-tam-broken-rice-with-pork-chop' => [['label'=>'1 plate','grams'=>350]],
			'bo-luc-lac-shaking-beef' => [['label'=>'1 plate','grams'=>250]],
			'goi-cuon-fresh-spring-rolls-2-pcs' => [['label'=>'2 rolls','grams'=>120],['label'=>'4 rolls','grams'=>240]],
			'cha-gio-fried-spring-rolls-2-pcs' => [['label'=>'2 rolls','grams'=>60],['label'=>'6 rolls','grams'=>180]],
			'banh-xeo-vietnamese-sizzling-crepe' => [['label'=>'1 crepe','grams'=>200]],
			'ca-phe-sua-da-vietnamese-iced-coffee' => [['label'=>'1 glass','grams'=>200]],

			// ── THAI (remaining) ──
			'som-tam-thai-papaya-salad-peanut' => [['label'=>'1 plate','grams'=>150]],
			'kai-yang-thai-grilled-chicken' => [['label'=>'1 portion','grams'=>150]],
			'tod-mun-pla-thai-fish-cakes' => [['label'=>'3 cakes','grams'=>75],['label'=>'5 cakes','grams'=>125]],
			'mu-ping-thai-grilled-pork-skewer' => [['label'=>'3 sticks','grams'=>90],['label'=>'5 sticks','grams'=>150]],
			'khao-man-gai-thai-chicken-rice' => [['label'=>'1 plate','grams'=>350]],

			// ── FILIPINO (remaining) ──
			'adobo-pork' => [['label'=>'1 portion','grams'=>200]],
			'lechon-whole-roast-pig-per-100g' => [['label'=>'1 portion','grams'=>150]],
			'sisig-sizzling-pork' => [['label'=>'1 plate','grams'=>200]],
			'pancit-canton-stir-fried-noodles' => [['label'=>'1 plate','grams'=>250]],
			'bibingka-rice-cake' => [['label'=>'1 piece','grams'=>100]],
			'ube-halaya-purple-yam-jam' => [['label'=>'1 portion','grams'=>80]],
			'ensaymada-filipino-brioche' => [['label'=>'1 piece','grams'=>80]],
			'leche-flan-filipino-caramel-custard' => [['label'=>'1 slice','grams'=>80]],

			// ── INDONESIAN (remaining) ──
			'soto-ayam-indonesian-chicken-soup' => [['label'=>'1 bowl','grams'=>300]],
			'bakso-indonesian-meatball-soup' => [['label'=>'1 bowl','grams'=>350]],
			'mie-goreng-indonesian-fried-noodles' => [['label'=>'1 plate','grams'=>300]],
			'ayam-goreng-indonesian-fried-chicken' => [['label'=>'1 piece','grams'=>120]],
			'tempeh-goreng-fried-tempeh' => [['label'=>'3 slices','grams'=>75],['label'=>'5 slices','grams'=>125]],
			'martabak-manis-sweet-thick-pancake' => [['label'=>'1 slice','grams'=>80],['label'=>'¼ pan','grams'=>120]],
			'klepon-pandan-glutinous-balls' => [['label'=>'3 pieces','grams'=>45],['label'=>'6 pieces','grams'=>90]],

			// ── SINGAPOREAN ──
			'chilli-crab-singapore' => [['label'=>'1 portion','grams'=>250]],
			'kaya-toast-with-soft-boiled-eggs' => [['label'=>'1 set','grams'=>150]],
			'ice-kachang-abc-shaved-ice' => [['label'=>'1 bowl','grams'=>250]],

			// ── BRUNEIAN ──
			'ambuyat-sago-starch-staple' => [['label'=>'1 portion','grams'=>200]],
			'nasi-katok-brunei-rice-fried-chicken' => [['label'=>'1 packet','grams'=>200]],

			// ── CUBAN (remaining) ──
			'cubano-sandwich-pressed' => [['label'=>'1 sandwich','grams'=>280]],
			'picadillo-cuban-ground-beef-hash' => [['label'=>'1 portion','grams'=>200]],
			'yuca-con-mojo-cassava-with-garlic-sauce' => [['label'=>'1 portion','grams'=>200]],

			// ── NORTH AFRICAN (remaining) ──
			'tagine-lamb-prune' => [['label'=>'1 portion','grams'=>250]],
			'pastilla-chicken-pie-moroccan' => [['label'=>'1 slice','grams'=>120]],
			'msemen-moroccan-flatbread' => [['label'=>'1 piece','grams'=>80]],
			'sellou-moroccan-energy-mix' => [['label'=>'2 tablespoons','grams'=>40]],

			// ── SOUTH AFRICAN ──
			'boerewors-grilled' => [['label'=>'1 coil','grams'=>150],['label'=>'½ coil','grams'=>75]],
			'bunny-chow-durban-curry-in-bread' => [['label'=>'¼ loaf','grams'=>300]],
			'koeksisters-braided-doughnut' => [['label'=>'1 piece','grams'=>50],['label'=>'2 pieces','grams'=>100]],
			'malva-pudding' => [['label'=>'1 portion','grams'=>120]],
			'melktert-milk-tart' => [['label'=>'1 slice','grams'=>100]],
			'droewors-dried-sausage' => [['label'=>'1 handful','grams'=>30],['label'=>'1 bag','grams'=>50]],

			// ── WEST AFRICAN (remaining) ──
			'pounded-yam' => [['label'=>'1 portion','grams'=>200]],
			'eba-garri-swallow' => [['label'=>'1 portion','grams'=>200]],
			'akara-bean-fritters' => [['label'=>'3 pieces','grams'=>75],['label'=>'5 pieces','grams'=>125]],
			'moin-moin-steamed-bean-pudding' => [['label'=>'1 portion','grams'=>150]],
			'chin-chin-fried-dough-snack' => [['label'=>'1 handful','grams'=>30],['label'=>'1 bowl','grams'=>80]],
			'puff-puff-nigerian-doughnut' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'kilishi-dried-spiced-beef' => [['label'=>'1 piece','grams'=>20],['label'=>'3 pieces','grams'=>60]],
			'zobo-hibiscus-drink' => [['label'=>'1 glass','grams'=>250]],
			'thieboudienne-senegalese-fish-rice' => [['label'=>'1 plate','grams'=>350]],
			'waakye-rice-and-beans' => [['label'=>'1 plate','grams'=>300]],

			// ── EAST AFRICAN (remaining) ──
			'nyama-choma-grilled-meat' => [['label'=>'1 portion','grams'=>200]],
			'sukuma-wiki-collard-greens' => [['label'=>'1 portion','grams'=>100]],
			'ugali-na-nyama-ugali-with-meat' => [['label'=>'1 plate','grams'=>350]],
			'tibs-sauteed-beef' => [['label'=>'1 portion','grams'=>150]],
			'shiro-wat-chickpea-stew' => [['label'=>'1 portion','grams'=>200]],

			// ── RUSSIAN (remaining) ──
			'olivier-salad-russian-salad' => [['label'=>'1 portion','grams'=>150]],
			'shuba-herring-under-fur-coat' => [['label'=>'1 portion','grams'=>150]],
			'napoleon-cake-mille-feuille' => [['label'=>'1 slice','grams'=>120]],
			'ptichye-moloko-birds-milk-cake' => [['label'=>'1 slice','grams'=>100]],
			'ponchiki-russian-doughnuts' => [['label'=>'1 piece','grams'=>50],['label'=>'3 pieces','grams'=>150]],

			// ── MONGOLIAN (remaining) ──
			'khorkhog-mongolian-hot-stone-bbq-mutton' => [['label'=>'1 portion','grams'=>200]],
			'suutei-tsai-mongolian-salt-milk-tea' => [['label'=>'1 bowl','grams'=>200]],
			'aaruul-mongolian-dried-curd' => [['label'=>'1 piece','grams'=>15],['label'=>'5 pieces','grams'=>75]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 63 );
	}

	/** Seed v64: Serving sizes batch 7 — remaining raw ingredients, confectionery, ready meals, baby foods, misc. */
	public static function seed_v64(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 64 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── RAW MEAT CUTS ──
			'chicken-thigh-raw'       => [['label'=>'1 thigh','grams'=>110]],
			'chicken-drumstick-raw'   => [['label'=>'1 drumstick','grams'=>75]],
			'chicken-wing-raw'        => [['label'=>'1 wing','grams'=>45],['label'=>'6 wings','grams'=>270]],
			'minced-beef-raw'         => [['label'=>'1 portion','grams'=>125],['label'=>'¼ lb patty','grams'=>113]],
			'lamb-chop-raw'           => [['label'=>'1 chop','grams'=>100]],
			'pork-chop-raw'           => [['label'=>'1 chop','grams'=>150]],
			'lamb-mince-raw'          => [['label'=>'1 portion','grams'=>125]],
			'turkey-mince-raw'        => [['label'=>'1 portion','grams'=>125]],
			'duck-breast-raw'         => [['label'=>'1 breast','grams'=>200]],
			'pork-belly-raw'          => [['label'=>'1 portion','grams'=>200]],

			// ── PROCESSED / CURED MEATS ──
			'mortadella'              => [['label'=>'2 slices','grams'=>40]],
			'pastrami'                => [['label'=>'3 slices','grams'=>56]],
			'coppa'                   => [['label'=>'3 slices','grams'=>30]],
			'nduja'                   => [['label'=>'1 tablespoon','grams'=>15]],
			'duck-pate'               => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 portion','grams'=>40]],
			'chicken-liver-pate'      => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 portion','grams'=>40]],
			'serrano-ham'             => [['label'=>'2 slices','grams'=>20]],
			'jamon-iberico-acorn-fed' => [['label'=>'3 slices','grams'=>30]],
			'turkey-bacon'            => [['label'=>'2 rashers','grams'=>30]],
			'chipolata-sausage-cooked' => [['label'=>'1 sausage','grams'=>25],['label'=>'3 sausages','grams'=>75]],
			'cumberland-sausage-cooked' => [['label'=>'1 sausage','grams'=>65]],
			'pigs-in-blankets-cooked' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'kangaroo-mince'          => [['label'=>'1 portion','grams'=>125]],
			'kangaroo-sausage'        => [['label'=>'1 sausage','grams'=>70]],

			// ── CONFECTIONERY ──
			'toffee'                  => [['label'=>'1 piece','grams'=>8],['label'=>'3 pieces','grams'=>24]],
			'fudge'                   => [['label'=>'1 piece','grams'=>15],['label'=>'3 pieces','grams'=>45]],
			'marshmallow'             => [['label'=>'1 marshmallow','grams'=>7],['label'=>'5 marshmallows','grams'=>35]],
			'turkish-delight'         => [['label'=>'1 piece','grams'=>20],['label'=>'3 pieces','grams'=>60]],
			'nougat'                  => [['label'=>'1 piece','grams'=>25]],
			'liquorice'               => [['label'=>'1 piece','grams'=>10],['label'=>'1 bag','grams'=>50]],
			'halva-sunflower-seed-russian' => [['label'=>'1 slice','grams'=>30]],
			'gulab-jamun-pakistani-style' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'jalebi-afghan-style-fried' => [['label'=>'1 piece','grams'=>25],['label'=>'3 pieces','grams'=>75]],
			'lokum-rose-flavour'      => [['label'=>'1 piece','grams'=>10],['label'=>'5 pieces','grams'=>50]],
			'tim-tam-slam-2-biscuits' => [['label'=>'2 biscuits','grams'=>36]],
			'freddo-frog-chocolate-12g' => [['label'=>'1 frog','grams'=>12]],
			'caramello-koala-chocolate-15g' => [['label'=>'1 koala','grams'=>15]],
			'violet-crumble-honeycomb-bar' => [['label'=>'1 bar','grams'=>50]],
			'cherry-ripe-chocolate-bar' => [['label'=>'1 bar','grams'=>52]],
			'coffee-crisp-chocolate-bar' => [['label'=>'1 bar','grams'=>50]],
			'caramilk-chocolate-bar'  => [['label'=>'1 bar','grams'=>50]],
			'eat-more-chocolate-bar'  => [['label'=>'1 bar','grams'=>52]],
			'golden-gaytime-ice-cream-bar' => [['label'=>'1 bar','grams'=>105]],

			// ── BREAKFAST CEREALS ──
			'bran-flakes'             => [['label'=>'1 bowl (30g)','grams'=>30],['label'=>'1 bowl with milk','grams'=>155]],
			'rice-krispies'           => [['label'=>'1 bowl (30g)','grams'=>30]],
			'shreddies'               => [['label'=>'1 bowl (40g)','grams'=>40]],
			'crunchy-nut-cornflakes'  => [['label'=>'1 bowl (30g)','grams'=>30]],
			'coco-pops'               => [['label'=>'1 bowl (30g)','grams'=>30]],
			'frosties'                => [['label'=>'1 bowl (30g)','grams'=>30]],
			'special-k-original'      => [['label'=>'1 bowl (30g)','grams'=>30]],
			'cheerios-wholegrain'     => [['label'=>'1 bowl (30g)','grams'=>30]],

			// ── READY MEALS ──
			'macaroni-cheese-ready-meal' => [['label'=>'1 meal','grams'=>350]],
			'chicken-tikka-masala-ready-meal' => [['label'=>'1 meal','grams'=>400]],
			'spaghetti-bolognese-ready-meal' => [['label'=>'1 meal','grams'=>400]],
			'cottage-pie-ready-meal'  => [['label'=>'1 meal','grams'=>400]],
			'fish-pie-ready-meal'     => [['label'=>'1 meal','grams'=>400]],
			'vegetable-curry-ready-meal' => [['label'=>'1 meal','grams'=>400]],
			'beef-lasagne-ready-meal' => [['label'=>'1 meal','grams'=>400]],
			'shepherds-pie-ready-meal' => [['label'=>'1 meal','grams'=>400]],
			'chilli-con-carne-ready-meal' => [['label'=>'1 meal','grams'=>400]],

			// ── BABY FOODS ──
			'baby-rice-cereal-dry'    => [['label'=>'1 tablespoon','grams'=>5],['label'=>'1 serving','grams'=>15]],
			'baby-fruit-puree-apple'  => [['label'=>'1 pouch','grams'=>100],['label'=>'1 jar','grams'=>125]],
			'baby-vegetable-puree-carrot' => [['label'=>'1 pouch','grams'=>100]],
			'baby-yoghurt-fromage-frais' => [['label'=>'1 pot','grams'=>60]],
			'follow-on-milk-powder'   => [['label'=>'1 scoop','grams'=>5],['label'=>'1 feed (3 scoops)','grams'=>15]],
			'baby-rusks'              => [['label'=>'1 rusk','grams'=>14]],

			// ── UK CONDIMENTS & SAUCES (remaining) ──
			'brown-sauce-hp'          => [['label'=>'1 tablespoon','grams'=>17],['label'=>'1 teaspoon','grams'=>6]],
			'salad-cream'             => [['label'=>'1 tablespoon','grams'=>15]],
			'tartare-sauce'           => [['label'=>'1 tablespoon','grams'=>15]],
			'mint-sauce'              => [['label'=>'1 tablespoon','grams'=>15]],
			'horseradish-sauce'       => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'cranberry-sauce'         => [['label'=>'1 tablespoon','grams'=>15]],
			'piccalilli'              => [['label'=>'1 tablespoon','grams'=>20]],
			'branston-pickle'         => [['label'=>'1 tablespoon','grams'=>20]],
			'gravy-instant-made-up'   => [['label'=>'1 ladle','grams'=>75],['label'=>'2 ladles','grams'=>150]],
			'redcurrant-jelly'        => [['label'=>'1 tablespoon','grams'=>15]],
			'bread-sauce'             => [['label'=>'2 tablespoons','grams'=>40]],
			'stuffing-sage-onion-cooked' => [['label'=>'1 ball','grams'=>30],['label'=>'2 balls','grams'=>60]],
			'hoisin-sauce'            => [['label'=>'1 tablespoon','grams'=>16]],
			'oyster-sauce'            => [['label'=>'1 tablespoon','grams'=>18]],
			'tonkatsu-sauce'          => [['label'=>'1 tablespoon','grams'=>17]],
			'kewpie-mayo-japanese-mayonnaise' => [['label'=>'1 tablespoon','grams'=>15]],
			'sambal-terasi-shrimp-paste-chilli' => [['label'=>'1 teaspoon','grams'=>5]],
			'chilli-oil-la-you'       => [['label'=>'1 teaspoon','grams'=>5],['label'=>'1 tablespoon','grams'=>14]],

			// ── VINEGARS & COOKING LIQUIDS ──
			'rice-vinegar'            => [['label'=>'1 tablespoon','grams'=>15]],
			'red-wine-vinegar'        => [['label'=>'1 tablespoon','grams'=>15]],
			'chinkiang-vinegar-black-vinegar' => [['label'=>'1 tablespoon','grams'=>15]],
			'mirin-sweet-rice-wine'   => [['label'=>'1 tablespoon','grams'=>15]],
			'fish-sauce'              => [['label'=>'1 tablespoon','grams'=>18],['label'=>'1 teaspoon','grams'=>6]],
			'nuoc-cham-fish-dipping-sauce' => [['label'=>'1 tablespoon','grams'=>15]],
			'ponzu-sauce'             => [['label'=>'1 tablespoon','grams'=>15]],

			// ── MORE NUTS ──
			'pistachio'               => [['label'=>'1 handful (shelled)','grams'=>25],['label'=>'1 oz','grams'=>28]],
			'brazil-nuts'             => [['label'=>'3 nuts','grams'=>15],['label'=>'6 nuts','grams'=>30]],
			'pecan-nuts'              => [['label'=>'10 halves','grams'=>14],['label'=>'1 handful','grams'=>25]],
			'hazelnuts'               => [['label'=>'10 nuts','grams'=>13],['label'=>'1 handful','grams'=>25]],
			'pine-nuts'               => [['label'=>'1 tablespoon','grams'=>10]],
			'coconut-cream-fresh-png' => [['label'=>'¼ cup','grams'=>60],['label'=>'½ cup','grams'=>120]],

			// ── MISC UK & INTERNATIONAL ──
			'scotch-pancake-drop-scone' => [['label'=>'1 pancake','grams'=>25],['label'=>'3 pancakes','grams'=>75]],
			'zucchini-slice-aussie-classic' => [['label'=>'1 slice','grams'=>80]],
			'ricotta-hotcakes-with-berries' => [['label'=>'2 hotcakes','grams'=>150]],
			'corn-fritters-with-smoked-salmon' => [['label'=>'2 fritters','grams'=>120]],
			'banana-bread-toasted-with-butter' => [['label'=>'1 slice','grams'=>80]],
			'damper-bush-bread'       => [['label'=>'1 slice','grams'=>60]],
			'vegemite-on-toast'       => [['label'=>'1 slice','grams'=>35]],
			'cheese-and-bacon-roll'   => [['label'=>'1 roll','grams'=>110]],
			'arnotts-shapes-bbq-per-100g' => [['label'=>'1 handful','grams'=>25],['label'=>'1 box','grams'=>175]],
			'neenish-tart'            => [['label'=>'1 tart','grams'=>60]],
			'vanilla-slice-australian' => [['label'=>'1 slice','grams'=>90]],
			'hedgehog-slice'          => [['label'=>'1 slice','grams'=>50]],
			'iced-vovo-biscuit'       => [['label'=>'1 biscuit','grams'=>15],['label'=>'3 biscuits','grams'=>45]],
			'afghan-biscuit-nz-chocolate' => [['label'=>'1 biscuit','grams'=>50]],
			'hokey-pokey-ice-cream'   => [['label'=>'1 scoop','grams'=>66],['label'=>'2 scoops','grams'=>132]],
			'lolly-cake-nz-classic'   => [['label'=>'1 slice','grams'=>40]],
			'whitebait-fritter-nz'    => [['label'=>'1 fritter','grams'=>80]],
			'rewena-bread-maori-potato-bread' => [['label'=>'1 slice','grams'=>50]],

			// ── TEA, COFFEE & DRINKS (final) ──
			'matcha-whisked-no-sugar'  => [['label'=>'1 bowl','grams'=>60]],
			'genmaicha-brown-rice-tea' => [['label'=>'1 cup','grams'=>240]],
			'chrysanthemum-tea'       => [['label'=>'1 cup','grams'=>240]],
			'pu-erh-tea-brewed'       => [['label'=>'1 cup','grams'=>240]],
			'oolong-tea-brewed'       => [['label'=>'1 cup','grams'=>240]],
			'rooibos-tea-unsweetened' => [['label'=>'1 cup','grams'=>240]],
			'yerba-mate-hot-unsweetened' => [['label'=>'1 gourd','grams'=>200]],
			'samovar-tea-russian-black-tea' => [['label'=>'1 cup','grams'=>200]],
			'persian-tea-chai-with-nabat-sugar' => [['label'=>'1 glass','grams'=>150]],
			'turkish-coffee-sade'     => [['label'=>'1 cup','grams'=>60]],
			'bica-portuguese-espresso' => [['label'=>'1 shot','grams'=>30]],
			'galao-milky-coffee-tall-glass' => [['label'=>'1 glass','grams'=>200]],
			'cafecito-cuban-espresso-sweetened' => [['label'=>'1 shot','grams'=>40]],
			'tim-hortons-double-double-coffee' => [['label'=>'1 cup','grams'=>300]],
			'iced-capp-tim-hortons-medium' => [['label'=>'1 medium','grams'=>400]],
			'long-black-coffee'       => [['label'=>'1 cup','grams'=>200]],
			'farmers-union-iced-coffee-sa' => [['label'=>'1 carton','grams'=>600],['label'=>'1 glass','grams'=>250]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 64 );
	}

	/** Seed v65: Serving sizes batch 8 — absolute final coverage. */
	public static function seed_v65(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 65 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── REMAINING COOKED DISHES ──
			'moussaka'                => [['label'=>'1 portion','grams'=>250]],
			'moussaka-vegetarian'     => [['label'=>'1 portion','grams'=>250]],
			'beef-stroganoff-classic' => [['label'=>'1 portion','grams'=>250]],
			'chicken-fried-steak'     => [['label'=>'1 steak','grams'=>200]],
			'meatloaf-american'       => [['label'=>'1 slice','grams'=>150]],
			'goulash-beef'            => [['label'=>'1 bowl','grams'=>300]],
			'chop-lamb-loin-bbq'      => [['label'=>'1 chop','grams'=>100]],
			'toad-in-the-hole'        => [['label'=>'1 portion','grams'=>200]],
			'beef-bourguignon'        => [['label'=>'1 bowl','grams'=>300]],
			'coq-au-vin'              => [['label'=>'1 portion','grams'=>300]],
			'cassoulet'               => [['label'=>'1 bowl','grams'=>300]],
			'choucroute-garnie-sauerkraut-meats' => [['label'=>'1 plate','grams'=>350]],
			'poulet-roti-french-roast-chicken' => [['label'=>'1 portion','grams'=>150]],
			'wiener-schnitzel'        => [['label'=>'1 schnitzel','grams'=>170]],
			'chicken-schnitzel-australian' => [['label'=>'1 schnitzel','grams'=>170]],

			// ── REMAINING SANDWICHES ──
			'pulled-pork-sandwich'    => [['label'=>'1 sandwich','grams'=>250]],
			'philly-cheesesteak'      => [['label'=>'1 sandwich','grams'=>280]],
			'reuben-sandwich'         => [['label'=>'1 sandwich','grams'=>280]],
			'sloppy-joe'              => [['label'=>'1 sandwich','grams'=>200]],
			'montreal-smoked-meat-sandwich' => [['label'=>'1 sandwich','grams'=>280]],
			'peameal-bacon-sandwich-toronto' => [['label'=>'1 sandwich','grams'=>200]],
			'chivito-uruguayan-steak-sandwich' => [['label'=>'1 sandwich','grams'=>300]],
			'croque-monsieur'         => [['label'=>'1 sandwich','grams'=>180]],
			'croque-madame'           => [['label'=>'1 sandwich','grams'=>200]],

			// ── REMAINING BBQ & AMERICAN ──
			'buffalo-wings-10-pcs'    => [['label'=>'5 wings','grams'=>150],['label'=>'10 wings','grams'=>300]],
			'brisket-texas-bbq'       => [['label'=>'1 portion','grams'=>150]],
			'baby-back-ribs-bbq'      => [['label'=>'½ rack','grams'=>250],['label'=>'full rack','grams'=>500]],
			'fried-chicken-southern'  => [['label'=>'1 piece','grams'=>120],['label'=>'2 pieces','grams'=>240]],
			'mac-and-cheese-american' => [['label'=>'1 bowl','grams'=>250]],
			'biscuits-and-gravy'      => [['label'=>'1 portion','grams'=>250]],
			'cornbread'               => [['label'=>'1 piece','grams'=>65]],
			'hush-puppies-6-pcs'      => [['label'=>'6 pieces','grams'=>90]],
			'grits-cheese-cooked'     => [['label'=>'1 bowl','grams'=>200]],
			'cobb-salad'              => [['label'=>'1 plate','grams'=>300]],
			'caesar-salad-with-croutons' => [['label'=>'1 plate','grams'=>250]],

			// ── REMAINING TEX-MEX & MEXICAN ──
			'chimichanga-beef'        => [['label'=>'1 chimichanga','grams'=>200]],
			'huevos-rancheros'        => [['label'=>'1 plate','grams'=>250]],
			'breakfast-burrito'       => [['label'=>'1 burrito','grams'=>250]],
			'fajitas-chicken'         => [['label'=>'1 portion','grams'=>200]],
			'chile-relleno-stuffed-pepper' => [['label'=>'1 pepper','grams'=>120]],
			'guacamole'               => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'pico-de-gallo'           => [['label'=>'2 tablespoons','grams'=>30]],
			'chili-con-queso-dip'     => [['label'=>'2 tablespoons','grams'=>30],['label'=>'¼ cup','grams'=>60]],
			'nachos-with-cheese'      => [['label'=>'1 portion','grams'=>150]],
			'mole-poblano-chicken'    => [['label'=>'1 portion','grams'=>250]],
			'sopes-bean-cheese'       => [['label'=>'1 sope','grams'=>80],['label'=>'3 sopes','grams'=>240]],
			'gorditas-stuffed'        => [['label'=>'1 gordita','grams'=>100]],
			'enchilada-cheese'        => [['label'=>'1 enchilada','grams'=>120],['label'=>'2 enchiladas','grams'=>240]],
			'elote-mexican-street-corn' => [['label'=>'1 cob','grams'=>150]],

			// ── REMAINING CANADIAN ──
			'tourtiere-meat-pie'      => [['label'=>'1 slice','grams'=>200]],
			'butter-tart'             => [['label'=>'1 tart','grams'=>45]],
			'nanaimo-bar'             => [['label'=>'1 bar','grams'=>50]],
			'beavertails-fried-dough-pastry' => [['label'=>'1 BeaverTail','grams'=>150]],
			'bannock-indigenous-bread' => [['label'=>'1 piece','grams'=>80]],
			'ketchup-chips-per-100g'  => [['label'=>'1 bag (25g)','grams'=>25],['label'=>'1 sharing bag','grams'=>150]],
			'all-dressed-chips-per-100g' => [['label'=>'1 bag (25g)','grams'=>25]],
			'jos-louis-snack-cake'    => [['label'=>'1 cake','grams'=>40]],
			'may-west-snack-cake'     => [['label'=>'1 cake','grams'=>40]],
			'calgary-ginger-beef'     => [['label'=>'1 portion','grams'=>200]],
			'saskatoon-berry-pie'     => [['label'=>'1 slice','grams'=>125]],
			'maple-baked-beans'       => [['label'=>'1 portion','grams'=>200]],

			// ── REMAINING SOUTH AMERICAN ──
			'acaraje-bahian-bean-fritter' => [['label'=>'1 piece','grams'=>100]],
			'moqueca-bahian-fish-stew' => [['label'=>'1 bowl','grams'=>300]],
			'pastel-fried-pastry-meat' => [['label'=>'1 pastel','grams'=>80]],
			'acai-na-tigela-acai-bowl' => [['label'=>'1 bowl','grams'=>250]],
			'tapioca-crepe-brazilian'  => [['label'=>'1 crepe','grams'=>100]],
			'choripan-chorizo-in-bread' => [['label'=>'1 choripán','grams'=>200]],
			'provoleta-grilled-provolone' => [['label'=>'1 portion','grams'=>100]],
			'medialunas-argentine-croissant' => [['label'=>'1 medialuna','grams'=>50],['label'=>'3 medialunas','grams'=>150]],
			'hallaca-venezuelan-tamale' => [['label'=>'1 hallaca','grams'=>200]],
			'tequenos-cheese-sticks'  => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'cazuela-chilean-stew'    => [['label'=>'1 bowl','grams'=>350]],
			'completo-chilean-hot-dog' => [['label'=>'1 completo','grams'=>200]],
			'sopaipilla-chilean-fried-bread' => [['label'=>'2 pieces','grams'=>80],['label'=>'4 pieces','grams'=>160]],
			'saltena-bolivian-empanada' => [['label'=>'1 salteña','grams'=>150]],

			// ── REMAINING CARIBBEAN ──
			'griot-haitian-fried-pork' => [['label'=>'1 portion','grams'=>150]],
			'soup-joumou-haitian-pumpkin-soup-jan-1st' => [['label'=>'1 bowl','grams'=>350]],
			'mofongo-puerto-rican-with-shrimp' => [['label'=>'1 portion','grams'=>200]],
			'alcapurrias-fried-plantain-meat-fritter' => [['label'=>'1 piece','grams'=>100],['label'=>'2 pieces','grams'=>200]],
			'pastelon-pr-plantain-lasagne' => [['label'=>'1 portion','grams'=>200]],
			'tembleque-coconut-pudding' => [['label'=>'1 portion','grams'=>120]],
			'keshi-yena-aruban-stuffed-cheese' => [['label'=>'1 portion','grams'=>200]],
			'green-fig-and-saltfish-st-lucian-national' => [['label'=>'1 plate','grams'=>250]],
			'cou-cou-and-flying-fish-bajan-national' => [['label'=>'1 plate','grams'=>300]],
			'oil-down-grenadian-national-dish' => [['label'=>'1 bowl','grams'=>300]],
			'goat-water-montserrat-national-dish' => [['label'=>'1 bowl','grams'=>250]],

			// ── REMAINING PACIFIC ISLANDS ──
			'mumu-png-earth-oven-mixed-per-100g' => [['label'=>'1 plate','grams'=>350]],
			'kaukau-png-sweet-potato-roasted' => [['label'=>'1 medium','grams'=>200]],
			'kokoda-png-raw-fish-in-coconut' => [['label'=>'1 bowl','grams'=>150]],
			'ota-ika-tongan-raw-fish-salad' => [['label'=>'1 bowl','grams'=>150]],
			'otai-tongan-fruit-coconut-drink' => [['label'=>'1 glass','grams'=>250]],
			'cassava-pudding-solomon-islands' => [['label'=>'1 portion','grams'=>150]],
			'panipopo-samoan-coconut-bun' => [['label'=>'1 bun','grams'=>80]],

			// ── REMAINING CENTRAL ASIAN ──
			'samsa-uzbek-lamb-in-pastry-1pc' => [['label'=>'1 samsa','grams'=>100],['label'=>'2 samsas','grams'=>200]],
			'lagman-uzbek-pulled-noodle-stew' => [['label'=>'1 bowl','grams'=>350]],
			'obi-non-uzbek-round-bread' => [['label'=>'¼ bread','grams'=>70],['label'=>'½ bread','grams'=>140]],
			'kazy-horse-meat-sausage' => [['label'=>'3 slices','grams'=>50],['label'=>'1 portion','grams'=>80]],
			'kurt-dried-fermented-cheese-balls' => [['label'=>'1 ball','grams'=>10],['label'=>'5 balls','grams'=>50]],
			'shubat-fermented-camel-milk' => [['label'=>'1 bowl','grams'=>200]],
			'gutap-turkmen-stuffed-flatbread-herb' => [['label'=>'1 gutap','grams'=>100]],
			'ishlekli-turkmen-meat-pie' => [['label'=>'1 slice','grams'=>150]],

			// ── REMAINING EGG DISHES ──
			'eggs-royale'             => [['label'=>'1 serving','grams'=>200]],
			'egg-fried-rice'          => [['label'=>'1 plate','grams'=>300]],
			'frittata-vegetable'      => [['label'=>'1 slice','grams'=>120]],
			'devilled-eggs-2-halves'  => [['label'=>'2 halves','grams'=>60],['label'=>'4 halves','grams'=>120]],
			'tamagoyaki-rolled-omelette' => [['label'=>'1 portion','grams'=>80]],
			'chawanmushi-savoury-egg-custard' => [['label'=>'1 cup','grams'=>150]],
			'shakshuka-australian-brunch' => [['label'=>'1 portion','grams'=>250]],

			// ── REMAINING MISC FOODS ──
			'coleslaw-american-creamy' => [['label'=>'1 portion','grams'=>100]],
			'coleslaw-deli-style'     => [['label'=>'1 portion','grams'=>100]],
			'potato-salad'            => [['label'=>'1 portion','grams'=>150]],
			'sun-dried-tomatoes-in-oil' => [['label'=>'3 pieces','grams'=>15],['label'=>'5 pieces','grams'=>25]],
			'artichoke-hearts-in-brine' => [['label'=>'2 hearts','grams'=>40],['label'=>'4 hearts','grams'=>80]],
			'capers-in-brine'         => [['label'=>'1 teaspoon','grams'=>5],['label'=>'1 tablespoon','grams'=>9]],
			'gelatine-sheets-per-100g' => [['label'=>'1 sheet','grams'=>2]],
			'baobab-powder'           => [['label'=>'1 tablespoon','grams'=>10]],
			'moringa-leaf-powder'     => [['label'=>'1 teaspoon','grams'=>3],['label'=>'1 tablespoon','grams'=>7]],
			'wattleseed-ground'       => [['label'=>'1 teaspoon','grams'=>3]],
			'finger-lime-raw'         => [['label'=>'1 lime','grams'=>10],['label'=>'5 limes','grams'=>50]],
			'quandong-raw'            => [['label'=>'3 fruits','grams'=>30]],
			'crowberry-paarnaqat-raw' => [['label'=>'1 handful','grams'=>30]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 65 );
	}

	/** Seed v66: Serving sizes batch 9 — mop-up of all remaining gaps. */
	public static function seed_v66(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 66 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── FAST FOOD & PIZZA ──
			'double-cheeseburger'     => [['label'=>'1 burger','grams'=>200]],
			'chicken-burger-breaded'  => [['label'=>'1 burger','grams'=>170]],
			'veggie-burger'           => [['label'=>'1 burger','grams'=>150]],
			'hawaiian-pizza-slice'    => [['label'=>'1 slice','grams'=>107],['label'=>'2 slices','grams'=>214]],
			'garlic-dough-balls-3-pcs' => [['label'=>'3 balls','grams'=>45],['label'=>'6 balls','grams'=>90]],
			'mozzarella-sticks-4-pcs' => [['label'=>'4 sticks','grams'=>80]],
			'chicken-wings-bbq-4-pcs' => [['label'=>'4 wings','grams'=>160],['label'=>'8 wings','grams'=>320]],
			'loaded-fries-cheese-bacon' => [['label'=>'1 portion','grams'=>250]],
			'corn-dog'                => [['label'=>'1 corn dog','grams'=>100]],
			'corndog-on-a-stick'      => [['label'=>'1 corn dog','grams'=>100]],
			'po-boy-fried-shrimp'     => [['label'=>'1 po\' boy','grams'=>250]],

			// ── REMAINING PIES & PASTRIES ──
			'steak-and-ale-pie'       => [['label'=>'1 pie','grams'=>250]],
			'game-pie'                => [['label'=>'1 pie','grams'=>250]],
			'pie-floater-meat-pie-in-pea-soup' => [['label'=>'1 serve','grams'=>350]],
			'cheese-and-onion-pie'    => [['label'=>'1 pie','grams'=>200]],
			'bedfordshire-clanger'    => [['label'=>'1 clanger','grams'=>250]],
			'stargazy-pie'            => [['label'=>'1 portion','grams'=>200]],
			'forfar-bridie'           => [['label'=>'1 bridie','grams'=>150]],
			'lardy-cake'              => [['label'=>'1 slice','grams'=>70]],
			'dundee-cake'             => [['label'=>'1 slice','grams'=>80]],
			'clootie-dumpling'        => [['label'=>'1 slice','grams'=>100]],
			'spotted-dick'            => [['label'=>'1 portion','grams'=>120]],
			'jam-roly-poly'           => [['label'=>'1 portion','grams'=>120]],

			// ── REMAINING LEGUMES ──
			'cooked-chickpeas'        => [['label'=>'1 cup','grams'=>160],['label'=>'½ can','grams'=>120]],
			'cooked-black-beans'      => [['label'=>'1 cup','grams'=>170],['label'=>'½ can','grams'=>130]],
			'cannellini-beans-canned' => [['label'=>'1 cup','grams'=>180],['label'=>'½ can','grams'=>130]],
			'garden-peas-canned'      => [['label'=>'½ cup','grams'=>80],['label'=>'1 cup','grams'=>160]],
			'garden-peas-frozen-cooked' => [['label'=>'½ cup','grams'=>80],['label'=>'1 cup','grams'=>145]],
			'mixed-vegetables-frozen-cooked' => [['label'=>'1 cup','grams'=>150],['label'=>'1 portion','grams'=>80]],
			'edamame-beans-frozen-cooked' => [['label'=>'½ cup','grams'=>75],['label'=>'1 cup','grams'=>150]],
			'edamame-salted-in-pod'   => [['label'=>'½ cup','grams'=>75],['label'=>'1 cup','grams'=>150]],
			'lentil-soup-canned'      => [['label'=>'1 bowl','grams'=>250],['label'=>'½ can','grams'=>200]],
			'samp-and-beans'          => [['label'=>'1 bowl','grams'=>250]],

			// ── REMAINING SOUPS ──
			'leek-and-potato-soup'    => [['label'=>'1 bowl','grams'=>250]],
			'butternut-squash-soup'   => [['label'=>'1 bowl','grams'=>250]],
			'french-onion-soup'       => [['label'=>'1 bowl','grams'=>250]],
			'gazpacho'                => [['label'=>'1 bowl','grams'=>250]],
			'clam-chowder'            => [['label'=>'1 bowl','grams'=>250]],
			'scotch-broth'            => [['label'=>'1 bowl','grams'=>250]],
			'cullen-skink-traditional' => [['label'=>'1 bowl','grams'=>250]],
			'egg-drop-soup-dan-hua-tang' => [['label'=>'1 bowl','grams'=>250]],
			'winter-melon-soup'       => [['label'=>'1 bowl','grams'=>250]],
			'corn-soup-chinese-style' => [['label'=>'1 bowl','grams'=>250]],
			'canja-de-galinha-chicken-rice-soup' => [['label'=>'1 bowl','grams'=>250]],
			'ajiaco-potato-chicken-soup' => [['label'=>'1 bowl','grams'=>350]],
			'sancocho-dominican-hearty-stew' => [['label'=>'1 bowl','grams'=>350]],
			'pepper-soup-goat'        => [['label'=>'1 bowl','grams'=>250]],
			'groundnut-soup-peanut-stew' => [['label'=>'1 bowl','grams'=>250]],
			'callaloo-soup-grenadian' => [['label'=>'1 bowl','grams'=>250]],

			// ── REMAINING DIPS & SPREADS ──
			'baba-ganoush'            => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'tzatziki'                => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'raita-cucumber'          => [['label'=>'2 tablespoons','grams'=>40]],
			'mango-chutney'           => [['label'=>'1 tablespoon','grams'=>20]],
			'lime-pickle'             => [['label'=>'1 teaspoon','grams'=>8]],
			'chimichurri-sauce'       => [['label'=>'1 tablespoon','grams'=>15]],
			'hogao-colombian-sofrito' => [['label'=>'1 tablespoon','grams'=>15]],
			'ajvar-serbian-roasted-pepper-relish' => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'tkemali-sour-plum-sauce' => [['label'=>'1 tablespoon','grams'=>15]],
			'adjika-georgian-chilli-paste' => [['label'=>'1 teaspoon','grams'=>5]],
			'chermoula-moroccan-marinade' => [['label'=>'1 tablespoon','grams'=>15]],
			'pikliz-haitian-spicy-slaw' => [['label'=>'2 tablespoons','grams'=>30]],
			'bajan-pepper-sauce'      => [['label'=>'1 teaspoon','grams'=>5]],
			'ezay-bhutanese-chilli-sauce' => [['label'=>'1 tablespoon','grams'=>15]],
			'jaew-bong-roasted-chilli-dip' => [['label'=>'1 tablespoon','grams'=>15]],
			'prahok-ktiss-prahok-dip-with-pork' => [['label'=>'1 tablespoon','grams'=>15]],
			'balachaung-dried-shrimp-chilli-relish' => [['label'=>'1 tablespoon','grams'=>10]],
			'ngapi-ye-fermented-fish-paste-sauce' => [['label'=>'1 teaspoon','grams'=>5]],
			'doenjang-soybean-paste'  => [['label'=>'1 tablespoon','grams'=>18]],
			'ssamjang-wrap-sauce'     => [['label'=>'1 tablespoon','grams'=>18]],
			'gochugaru-korean-chilli-flakes' => [['label'=>'1 tablespoon','grams'=>5]],
			'furikake-rice-seasoning-mixed' => [['label'=>'1 teaspoon','grams'=>3]],
			'dashi-stock-kombu-bonito' => [['label'=>'1 cup','grams'=>240]],
			'xo-sauce'                => [['label'=>'1 tablespoon','grams'=>15]],
			'doubanjiang-chilli-bean-paste' => [['label'=>'1 tablespoon','grams'=>15]],
			'shichimi-togarashi-7-spice' => [['label'=>'1 teaspoon','grams'=>2]],

			// ── REMAINING RAW SEAFOOD ──
			'tuna-raw-sashimi-grade'  => [['label'=>'5 slices','grams'=>80]],
			'sea-bass-raw'            => [['label'=>'1 fillet','grams'=>150]],
			'haddock-raw'             => [['label'=>'1 fillet','grams'=>180]],
			'mackerel-raw'            => [['label'=>'1 fillet','grams'=>100]],
			'sardines-raw'            => [['label'=>'3 sardines','grams'=>75]],
			'squid-raw'               => [['label'=>'1 portion','grams'=>100]],
			'mussels-raw'             => [['label'=>'1 cup meat','grams'=>150]],
			'crab-meat-canned'        => [['label'=>'1 can','grams'=>170],['label'=>'½ can','grams'=>85]],
			'anchovies-in-oil-drained' => [['label'=>'3 fillets','grams'=>9],['label'=>'6 fillets','grams'=>18]],
			'rollmop-herring'         => [['label'=>'1 rollmop','grams'=>60]],
			'smoked-trout'            => [['label'=>'1 fillet','grams'=>100]],
			'whitebait-fried'         => [['label'=>'1 portion','grams'=>80]],
			'cockles-boiled'          => [['label'=>'1 portion','grams'=>50]],
			'whelks-boiled'           => [['label'=>'1 portion','grams'=>50]],
			'dover-sole-grilled'      => [['label'=>'1 fish','grams'=>200]],
			'monkfish-grilled'        => [['label'=>'1 portion','grams'=>150]],

			// ── REMAINING MILK & DAIRY ──
			'buttermilk'              => [['label'=>'1 glass','grams'=>245],['label'=>'1 cup','grams'=>245]],
			'smetana-sour-cream-20'   => [['label'=>'1 tablespoon','grams'=>12],['label'=>'2 tablespoons','grams'=>24]],
			'tvorog-russian-curd-cheese' => [['label'=>'1 portion','grams'=>100],['label'=>'½ cup','grams'=>120]],
			'kaymak-clotted-cream'    => [['label'=>'1 tablespoon','grams'=>15]],
			'labneh'                  => [['label'=>'1 tablespoon','grams'=>15],['label'=>'¼ cup','grams'=>60]],
			'sgushyonka-condensed-milk' => [['label'=>'1 tablespoon','grams'=>20]],

			// ── REMAINING SWEETS & DRINKS ──
			'maamoul-pistachio-filled' => [['label'=>'1 piece','grams'=>30]],
			'muhallabia-milk-pudding' => [['label'=>'1 cup','grams'=>150]],
			'umm-ali-bread-pudding-egyptian' => [['label'=>'1 portion','grams'=>150]],
			'halawet-el-jibn-cheese-rolls' => [['label'=>'2 pieces','grams'=>60]],
			'dondurma-turkish-ice-cream' => [['label'=>'1 scoop','grams'=>70],['label'=>'2 scoops','grams'=>140]],
			'salep-orchid-root-drink' => [['label'=>'1 cup','grams'=>200]],
			'shalgam-suyu-turnip-juice' => [['label'=>'1 glass','grams'=>200]],
			'jallab-grape-rose-drink' => [['label'=>'1 glass','grams'=>200]],
			'qamar-al-din-apricot-nectar' => [['label'=>'1 glass','grams'=>200]],
			'tella-ethiopian-beer'    => [['label'=>'1 glass','grams'=>250]],
			'palm-wine-fresh'         => [['label'=>'1 glass','grams'=>200]],
			'kunu-millet-drink'       => [['label'=>'1 glass','grams'=>250]],
			'bissap-senegalese-hibiscus' => [['label'=>'1 glass','grams'=>250]],
			'sorrel-drink-jamaican-hibiscus' => [['label'=>'1 glass','grams'=>250]],
			'chicha-morada-purple-corn-drink' => [['label'=>'1 glass','grams'=>250]],
			'aguapanela-sugarcane-drink' => [['label'=>'1 glass','grams'=>250]],
			'calamansi-juice-filipino-limeade' => [['label'=>'1 glass','grams'=>250]],
			'bandrek-ginger-spice-drink' => [['label'=>'1 glass','grams'=>200]],
			'es-teler-mixed-fruit-ice-drink' => [['label'=>'1 glass','grams'=>250]],
			'shwe-yin-aye-golden-heart-cooler' => [['label'=>'1 glass','grams'=>250]],
			'htan-yay-toddy-palm-juice' => [['label'=>'1 glass','grams'=>200]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 66 );
	}

	/** Seed v67: Serving sizes batch 10 — absolute final remaining foods. */
	public static function seed_v67(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 67 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── REMAINING STIR-FRIES & WOK ──
			'twice-cooked-pork-hui-guo-rou' => [['label'=>'1 portion','grams'=>200]],
			'yu-xiang-rou-si-fish-fragrant-pork' => [['label'=>'1 portion','grams'=>200]],
			'dry-fried-green-beans-sichuan' => [['label'=>'1 portion','grams'=>150]],
			'stir-fried-pak-choi-with-garlic' => [['label'=>'1 portion','grams'=>120]],
			'chinese-broccoli-kai-lan-oyster-sauce' => [['label'=>'1 portion','grams'=>120]],
			'tomato-egg-stir-fry-xi-hong-shi-chao-dan' => [['label'=>'1 portion','grams'=>200]],
			'black-bean-beef-dou-chi-niu-rou' => [['label'=>'1 portion','grams'=>200]],
			'cashew-chicken-yao-guo-ji-ding' => [['label'=>'1 portion','grams'=>200]],
			'mongolian-beef'          => [['label'=>'1 portion','grams'=>200]],
			'orange-chicken'          => [['label'=>'1 portion','grams'=>200]],
			'chilli-beef-hunan-style' => [['label'=>'1 portion','grams'=>200]],
			'stir-fried-morning-glory-water-spinach' => [['label'=>'1 portion','grams'=>120]],

			// ── REMAINING ROASTS & BRAISED ──
			'siu-yuk-crispy-roast-pork-belly' => [['label'=>'1 portion','grams'=>100]],
			'white-cut-chicken-bai-qie-ji' => [['label'=>'1 portion','grams'=>150]],
			'drunken-chicken-zui-ji'  => [['label'=>'1 portion','grams'=>150]],
			'dongpo-pork-dong-po-rou' => [['label'=>'1 portion','grams'=>120]],
			'soy-sauce-chicken-see-yau-gai' => [['label'=>'1 portion','grams'=>150]],
			'tea-egg-cha-ye-dan-1-egg' => [['label'=>'1 egg','grams'=>50]],
			'braised-beef-shank-lu-niu-rou' => [['label'=>'3 slices','grams'=>60],['label'=>'1 portion','grams'=>100]],
			'century-egg-pi-dan-1-egg' => [['label'=>'1 egg','grams'=>50]],
			'lechon-asado-cuban-roast-pork' => [['label'=>'1 portion','grams'=>150]],
			'leitao-da-bairrada-roast-suckling-pig' => [['label'=>'1 portion','grams'=>150]],
			'mechoui-slow-roasted-lamb' => [['label'=>'1 portion','grams'=>150]],
			'shisa-nyama-braai-meat'  => [['label'=>'1 portion','grams'=>200]],

			// ── REMAINING STREET FOOD ──
			'dan-bing-egg-crepe-taiwanese' => [['label'=>'1 crepe','grams'=>120]],
			'mantou-steamed-bun-plain' => [['label'=>'1 bun','grams'=>50],['label'=>'2 buns','grams'=>100]],
			'tanghulu-candied-fruit-skewer' => [['label'=>'1 skewer','grams'=>60]],
			'egg-waffle-gai-dan-jai-hong-kong' => [['label'=>'1 waffle','grams'=>120]],
			'turnip-cake-steamed-cantonese' => [['label'=>'1 slice','grams'=>50],['label'=>'3 slices','grams'=>150]],
			'takoyaki-octopus-ball-1pc' => [['label'=>'1 ball','grams'=>30],['label'=>'6 balls','grams'=>180]],
			'okonomiyaki-japanese-pancake' => [['label'=>'1 pancake','grams'=>250]],
			'yakitori-negima-chicken-leek' => [['label'=>'2 sticks','grams'=>60],['label'=>'4 sticks','grams'=>120]],
			'yakitori-tebasaki-chicken-wing' => [['label'=>'2 wings','grams'=>80]],
			'yakitori-tsukune-chicken-meatball' => [['label'=>'2 sticks','grams'=>60]],
			'kushikatsu-deep-fried-skewers' => [['label'=>'3 sticks','grams'=>90]],
			'tatsuta-age-soy-marinated-chicken' => [['label'=>'5 pieces','grams'=>100]],
			'ebi-fry-breaded-fried-prawns' => [['label'=>'3 prawns','grams'=>75],['label'=>'5 prawns','grams'=>125]],
			'unagi-kabayaki-grilled-eel' => [['label'=>'1 fillet','grams'=>100]],
			'kelaguen-guam-citrus-cooked-chicken' => [['label'=>'1 portion','grams'=>120]],
			'conch-chowder-turks-caicos' => [['label'=>'1 bowl','grams'=>250]],
			'fish-and-fungi-bvi-cornmeal-okra' => [['label'=>'1 plate','grams'=>300]],

			// ── REMAINING JAPANESE ──
			'sushi-tamago-egg-nigiri-1pc' => [['label'=>'1 piece','grams'=>35],['label'=>'2 pieces','grams'=>70]],
			'sushi-unagi-eel-nigiri-1pc' => [['label'=>'1 piece','grams'=>35]],
			'sushi-ikura-salmon-roe-gunkan' => [['label'=>'1 piece','grams'=>30]],
			'california-roll-6-pcs'   => [['label'=>'6 pieces','grams'=>180]],
			'dragon-roll-6-pcs'       => [['label'=>'6 pieces','grams'=>200]],
			'temaki-hand-roll-salmon' => [['label'=>'1 roll','grams'=>100]],
			'inari-sushi-tofu-pouch-2-pcs' => [['label'=>'2 pieces','grams'=>80]],
			'chirashi-don-deluxe-sashimi-bowl' => [['label'=>'1 bowl','grams'=>350]],
			'nikujaga-meat-potato-stew' => [['label'=>'1 bowl','grams'=>250]],
			'oden-fishcake-hotpot-mixed' => [['label'=>'1 bowl','grams'=>300]],
			'agedashi-tofu-fried-in-dashi' => [['label'=>'1 portion','grams'=>120]],
			'kinpira-gobo-braised-burdock' => [['label'=>'1 portion','grams'=>60]],
			'nasu-dengaku-miso-glazed-aubergine' => [['label'=>'2 halves','grams'=>120]],
			'anpan-red-bean-bun'      => [['label'=>'1 bun','grams'=>80]],
			'dango-mitarashi-3-balls' => [['label'=>'1 skewer (3 balls)','grams'=>75]],
			'yokan-red-bean-jelly'    => [['label'=>'1 slice','grams'=>50]],
			'kakigori-shaved-ice-strawberry' => [['label'=>'1 bowl','grams'=>200]],
			'matcha-parfait'          => [['label'=>'1 parfait','grams'=>200]],
			'castella-sponge-cake'    => [['label'=>'1 slice','grams'=>50]],

			// ── REMAINING KOREAN ──
			'dakgangjeong-crispy-sweet-chicken' => [['label'=>'1 portion','grams'=>150]],
			'jeyuk-bokkeum-spicy-pork-stir-fry' => [['label'=>'1 portion','grams'=>150]],
			'galbi-jjim-braised-short-ribs' => [['label'=>'1 portion','grams'=>200]],
			'bossam-boiled-pork-belly-wraps' => [['label'=>'1 portion','grams'=>150]],
			'dak-galbi-spicy-chicken-stir-fry' => [['label'=>'1 portion','grams'=>200]],
			'naengmyeon-cold-buckwheat-noodles' => [['label'=>'1 bowl','grams'=>400]],
			'kalguksu-knife-cut-noodle-soup' => [['label'=>'1 bowl','grams'=>350]],
			'jjamppong-spicy-seafood-noodle-soup' => [['label'=>'1 bowl','grams'=>400]],
			'sujebi-hand-torn-noodle-soup' => [['label'=>'1 bowl','grams'=>350]],
			'haemul-pajeon-seafood-pancake' => [['label'=>'1 pancake','grams'=>250]],
			'kimchi-jeon-kimchi-pancake' => [['label'=>'1 pancake','grams'=>200]],
			'bindaetteok-mung-bean-pancake' => [['label'=>'1 pancake','grams'=>150]],
			'hotteok-sweet-filled-pancake' => [['label'=>'1 hotteok','grams'=>80]],
			'sundae-korean-blood-sausage' => [['label'=>'1 portion','grams'=>150]],
			'bungeoppang-fish-shaped-pastry' => [['label'=>'1 piece','grams'=>80]],
			'gyeranppang-egg-bread'   => [['label'=>'1 piece','grams'=>100]],
			'korean-corn-dog-mozzarella' => [['label'=>'1 corn dog','grams'=>120]],
			'songpyeon-rice-cake-chuseok' => [['label'=>'3 pieces','grams'=>60],['label'=>'6 pieces','grams'=>120]],
			'yakgwa-honey-cookie'     => [['label'=>'1 piece','grams'=>20],['label'=>'3 pieces','grams'=>60]],
			'yuja-cha-citron-tea'     => [['label'=>'1 cup','grams'=>200]],
			'sikhye-sweet-rice-drink' => [['label'=>'1 glass','grams'=>250]],
			'sujeonggwa-cinnamon-persimmon-punch' => [['label'=>'1 glass','grams'=>200]],
			'banana-milk-korean-binggrae' => [['label'=>'1 bottle','grams'=>240]],
			'makgeolli-rice-wine'     => [['label'=>'1 bowl','grams'=>300],['label'=>'1 glass','grams'=>200]],

			// ── REMAINING RUSSIAN ──
			'solyanka-meat-pickle-soup' => [['label'=>'1 bowl','grams'=>250]],
			'ukha-fish-soup'          => [['label'=>'1 bowl','grams'=>250]],
			'okroshka-cold-kvas-soup' => [['label'=>'1 bowl','grams'=>300]],
			'chebureki-deep-fried-meat-turnover' => [['label'=>'1 piece','grams'=>120]],
			'kulebyaka-salmon-pie'    => [['label'=>'1 slice','grams'=>150]],
			'golubtsy-stuffed-cabbage-rolls' => [['label'=>'2 rolls','grams'=>200]],
			'holodets-meat-jelly-aspic' => [['label'=>'1 portion','grams'=>100]],
			'vatrushka-curd-pastry'   => [['label'=>'1 piece','grams'=>80]],
			'kartoshka-chocolate-potato-cake' => [['label'=>'1 piece','grams'=>60]],
			'pastila-fruit-marshmallow' => [['label'=>'2 pieces','grams'=>30]],
			'zefir-russian-marshmallow' => [['label'=>'1 piece','grams'=>30],['label'=>'2 pieces','grams'=>60]],
			'kompot-stewed-fruit-drink' => [['label'=>'1 glass','grams'=>250]],
			'mors-berry-drink-cranberry' => [['label'=>'1 glass','grams'=>250]],
			'kissel-berry-thick'      => [['label'=>'1 bowl','grams'=>200]],

			// ── REMAINING TURKISH ──
			'kofte-turkish-meatballs' => [['label'=>'3 köfte','grams'=>100],['label'=>'5 köfte','grams'=>170]],
			'imam-bayildi-stuffed-aubergine' => [['label'=>'1 aubergine','grams'=>200]],
			'karniyarik-meat-stuffed-aubergine' => [['label'=>'1 aubergine','grams'=>200]],
			'hunkar-begendi-sultans-delight' => [['label'=>'1 portion','grams'=>250]],
			'menemen-turkish-scrambled-eggs' => [['label'=>'1 portion','grams'=>200]],
			'cilbir-turkish-poached-eggs' => [['label'=>'1 portion','grams'=>200]],
			'sucuk-spiced-sausage-fried' => [['label'=>'4 slices','grams'=>50],['label'=>'8 slices','grams'=>100]],
			'pastirma-cured-beef'     => [['label'=>'3 slices','grams'=>20]],
			'sigara-boregi-cigar-pastry' => [['label'=>'3 pieces','grams'=>60],['label'=>'5 pieces','grams'=>100]],
			'tulumba-syrupy-dough'    => [['label'=>'3 pieces','grams'=>45],['label'=>'6 pieces','grams'=>90]],
			'lokma-fried-dough-balls' => [['label'=>'5 pieces','grams'=>50],['label'=>'10 pieces','grams'=>100]],
			'revani-semolina-cake'    => [['label'=>'1 piece','grams'=>60]],
			'sekerpare-syrupy-cookie' => [['label'=>'1 piece','grams'=>30],['label'=>'3 pieces','grams'=>90]],
			'boza-fermented-millet-drink' => [['label'=>'1 glass','grams'=>200]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 67 );
	}

	/** Seed v68: Serving sizes batch 11 — remaining stews, soups, sides, grains, hot pots. */
	public static function seed_v68(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 68 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── HOT POTS & STEWS (JAPANESE/KOREAN/CHINESE) ──
			'shabu-shabu-sliced-beef' => [['label'=>'1 bowl','grams'=>300]],
			'chanko-nabe-sumo-stew'   => [['label'=>'1 bowl','grams'=>350]],
			'motsu-nabe-offal-hot-pot' => [['label'=>'1 bowl','grams'=>300]],
			'yudofu-simmered-tofu'    => [['label'=>'1 bowl','grams'=>250]],
			'nabe-vegetable-hot-pot'  => [['label'=>'1 bowl','grams'=>350]],
			'sichuan-hot-pot-broth-per-100ml' => [['label'=>'1 bowl broth','grams'=>300]],
			'ma-la-xiang-guo-spicy-stir-fry-pot' => [['label'=>'1 portion','grams'=>250]],
			'doenjang-jjigae-soybean-paste-stew' => [['label'=>'1 bowl','grams'=>250]],
			'budae-jjigae-army-stew'  => [['label'=>'1 bowl','grams'=>350]],
			'gamjatang-pork-bone-soup' => [['label'=>'1 bowl','grams'=>350]],
			'samgyetang-ginseng-chicken-soup' => [['label'=>'1 bowl','grams'=>400]],
			'seolleongtang-ox-bone-soup' => [['label'=>'1 bowl','grams'=>350]],
			'galbitang-short-rib-soup' => [['label'=>'1 bowl','grams'=>350]],
			'yukgaejang-spicy-beef-soup' => [['label'=>'1 bowl','grams'=>300]],
			'miyeok-guk-seaweed-birthday-soup' => [['label'=>'1 bowl','grams'=>300]],
			'tteokguk-rice-cake-soup-new-year' => [['label'=>'1 bowl','grams'=>300]],
			'kimchi-jjigae-stew'      => [['label'=>'1 bowl','grams'=>250]],
			'gopchang-jeongol-tripe-hot-pot' => [['label'=>'1 bowl','grams'=>300]],
			'cheonggukjang-jjigae-fast-fermented-bean-stew' => [['label'=>'1 bowl','grams'=>200]],

			// ── REMAINING SOUPS WORLDWIDE ──
			'sopa-de-peixe-portuguese-fish-soup' => [['label'=>'1 bowl','grams'=>250]],
			'creme-de-marisco-shellfish-bisque' => [['label'=>'1 bowl','grams'=>200]],
			'soupe-a-loignon-gratinee' => [['label'=>'1 bowl','grams'=>250]],
			'wonton-soup-cantonese-clear' => [['label'=>'1 bowl','grams'=>300]],
			'lotus-root-and-pork-rib-soup' => [['label'=>'1 bowl','grams'=>300]],
			'herbal-chicken-soup-yao-shan' => [['label'=>'1 bowl','grams'=>300]],
			'seaweed-and-egg-soup'    => [['label'=>'1 bowl','grams'=>250]],
			'lanzhou-beef-noodle-soup' => [['label'=>'1 bowl','grams'=>500]],
			'crossing-the-bridge-noodles-yunnan' => [['label'=>'1 bowl','grams'=>400]],
			'wonton-noodle-soup-cantonese' => [['label'=>'1 bowl','grams'=>400]],
			'khao-piak-sen-lao-noodle-soup' => [['label'=>'1 bowl','grams'=>350]],
			'khao-poon-lao-rice-vermicelli-soup' => [['label'=>'1 bowl','grams'=>350]],
			'kuy-teav-khmer-noodle-soup' => [['label'=>'1 bowl','grams'=>400]],
			'hu-tieu-southern-pork-noodle-soup' => [['label'=>'1 bowl','grams'=>400]],
			'shorba-afghan-lamb-soup' => [['label'=>'1 bowl','grams'=>250]],
			'shurbo-tajik-mutton-veg-soup' => [['label'=>'1 bowl','grams'=>250]],
			'shorpo-kyrgyz-lamb-broth' => [['label'=>'1 bowl','grams'=>250]],
			'shurpa-turkmen-lamb-soup' => [['label'=>'1 bowl','grams'=>250]],
			'begova-corba-beys-soup-chicken-cream' => [['label'=>'1 bowl','grams'=>250]],
			'kapustnica-slovak-sauerkraut-soup' => [['label'=>'1 bowl','grams'=>250]],
			'ciorba-de-burta-romanian-tripe-soup' => [['label'=>'1 bowl','grams'=>250]],
			'tarator-bulgarian-cold-yoghurt-soup' => [['label'=>'1 bowl','grams'=>250]],
			'dovga-yoghurt-herb-soup' => [['label'=>'1 bowl','grams'=>250]],
			'lablabi-tunisian-chickpea-soup' => [['label'=>'1 bowl','grams'=>250]],
			'samlor-korko-khmer-stirring-soup' => [['label'=>'1 bowl','grams'=>300]],
			'samlor-machu-sour-soup'  => [['label'=>'1 bowl','grams'=>250]],
			'canh-chua-sour-fish-soup-southern' => [['label'=>'1 bowl','grams'=>300]],
			'kaeng-jued-thai-clear-soup' => [['label'=>'1 bowl','grams'=>250]],
			'sopa-da-pedra-stone-soup-ribatejo' => [['label'=>'1 bowl','grams'=>300]],

			// ── REMAINING BANCHAN & SIDES ──
			'kongnamul-muchim-seasoned-bean-sprouts' => [['label'=>'1 portion','grams'=>50]],
			'sigeumchi-namul-spinach-side' => [['label'=>'1 portion','grams'=>50]],
			'musaengchae-spicy-radish-salad' => [['label'=>'1 portion','grams'=>50]],
			'gyeran-mari-rolled-egg-omelette' => [['label'=>'3 slices','grams'=>60]],
			'eomuk-bokkeum-stir-fried-fish-cake' => [['label'=>'1 portion','grams'=>60]],
			'gamja-jorim-braised-potatoes' => [['label'=>'1 portion','grams'=>80]],
			'myeolchi-bokkeum-stir-fried-anchovies' => [['label'=>'1 portion','grams'=>30]],
			'pickled-radish-danmuji'  => [['label'=>'5 slices','grams'=>25]],
			'perilla-leaves-pickled-kkaennip' => [['label'=>'3 leaves','grams'=>15]],
			'kkakdugi-cubed-radish-kimchi' => [['label'=>'2 tablespoons','grams'=>30],['label'=>'¼ cup','grams'=>50]],
			'tsukemono-japanese-pickles-mixed' => [['label'=>'1 portion','grams'=>30]],
			'sunomono-vinegar-cucumber' => [['label'=>'1 portion','grams'=>50]],
			'ohitashi-blanched-spinach' => [['label'=>'1 portion','grams'=>60]],
			'umeboshi-pickled-plum-1-pc' => [['label'=>'1 plum','grams'=>8]],
			'salad-shirazi-cucumber-tomato-onion' => [['label'=>'1 portion','grams'=>100]],
			'kachumbari-tomato-onion-salad' => [['label'=>'1 portion','grams'=>80]],
			'chakalaka-spiced-relish' => [['label'=>'1 portion','grams'=>80]],
			'curtido-salvadoran-pickled-cabbage' => [['label'=>'2 tablespoons','grams'=>30]],
			'ensaladilla-rusa-spanish-potato-salad' => [['label'=>'1 portion','grams'=>100]],

			// ── REMAINING GRAINS & STAPLES ──
			'cooked-pearl-barley'     => [['label'=>'1 cup','grams'=>160],['label'=>'½ cup','grams'=>80]],
			'cooked-bulgur-wheat'     => [['label'=>'1 cup','grams'=>180],['label'=>'½ cup','grams'=>90]],
			'cooked-polenta'          => [['label'=>'1 portion','grams'=>150]],
			'wild-rice-cooked'        => [['label'=>'1 cup','grams'=>165],['label'=>'½ cup','grams'=>82]],
			'amaranth-raw'            => [['label'=>'¼ cup','grams'=>50]],
			'teff-raw'                => [['label'=>'¼ cup','grams'=>50]],
			'fonio-grain-raw'         => [['label'=>'¼ cup','grams'=>45]],
			'buckwheat-groats-raw'    => [['label'=>'¼ cup','grams'=>45]],
			'sago-png-processed-starch' => [['label'=>'¼ cup','grams'=>50]],
			'mamaliaga-romanian-polenta' => [['label'=>'1 portion','grams'=>200]],
			'pap-maize-porridge-stiff' => [['label'=>'1 portion','grams'=>200]],
			'ugali-maize-porridge'    => [['label'=>'1 portion','grams'=>200]],
			'nshima-zambian-maize-porridge' => [['label'=>'1 portion','grams'=>200]],
			'sadza-zimbabwean-maize-porridge' => [['label'=>'1 portion','grams'=>200]],
			'banku-ghanaian-corn-dough' => [['label'=>'1 portion','grams'=>200]],
			'kenkey-fermented-corn'   => [['label'=>'1 portion','grams'=>200]],
			'amala-yam-flour-swallow' => [['label'=>'1 portion','grams'=>200]],
			'garri-dry-soaked'        => [['label'=>'1 portion','grams'=>150]],
			'tuwo-shinkafa-rice-swallow' => [['label'=>'1 portion','grams'=>200]],
			'ambuyat-sago-starch-staple' => [['label'=>'1 portion','grams'=>200]],
			'chikwangue-cassava-bread' => [['label'=>'1 portion','grams'=>150]],
			'pulaka-swamp-taro-tuvalu-boiled' => [['label'=>'1 portion','grams'=>200]],

			// ── REMAINING BREADS ──
			'borodinsky-bread-rye-dark' => [['label'=>'1 slice','grams'=>40]],
			'pumpernickel'            => [['label'=>'1 slice','grams'=>30]],
			'sourdough'               => [['label'=>'1 slice','grams'=>50],['label'=>'2 slices','grams'=>100]],
			'cornbread'               => [['label'=>'1 piece','grams'=>65]],
			'broa-de-milho-corn-bread' => [['label'=>'1 slice','grams'=>60]],
			'pao-alentejano-alentejo-wheat-bread' => [['label'=>'1 slice','grams'=>60]],
			'bolo-do-caco-madeira-garlic-bread' => [['label'=>'1 piece','grams'=>80]],
			'chorek-turkmen-flatbread' => [['label'=>'¼ bread','grams'=>70]],
			'non-tajik-round-flatbread' => [['label'=>'¼ bread','grams'=>70]],
			'tandyr-nan-kazakh-clay-oven-bread' => [['label'=>'¼ bread','grams'=>70]],
			'lepyoshka-kyrgyz-round-bread' => [['label'=>'¼ bread','grams'=>70]],
			'khubz-arabic-flatbread'  => [['label'=>'1 bread','grams'=>80]],
			'markook-paper-thin-bread' => [['label'=>'1 sheet','grams'=>60]],
			'kaak-sesame-bread-ring'  => [['label'=>'1 ring','grams'=>100]],
			'afghan-naan-tandoor-bread' => [['label'=>'1 naan','grams'=>100]],
			'barbari-bread-sesame-flatbread' => [['label'=>'¼ bread','grams'=>80]],
			'shotis-puri-georgian-clay-oven-bread' => [['label'=>'1 piece','grams'=>60]],
			'flatkokur-icelandic-flatbread' => [['label'=>'1 piece','grams'=>40]],
			'rugbraud-icelandic-rye-bread-hot-spring' => [['label'=>'1 slice','grams'=>40]],
			'vetkoek-fried-bread'     => [['label'=>'1 vetkoek','grams'=>80]],
			'toutons-fried-dough-newfoundland' => [['label'=>'1 touton','grams'=>60],['label'=>'3 toutons','grams'=>180]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 68 );
	}

	/** Seed v69: Serving sizes batch 12 — final remaining dishes, noodles, curries, desserts, drinks. */
	public static function seed_v69(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 69 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── REMAINING NOODLE DISHES ──
			'zhajiangmian-bean-paste-noodles' => [['label'=>'1 bowl','grams'=>350]],
			'biang-biang-noodles-hand-pulled' => [['label'=>'1 bowl','grams'=>350]],
			'cold-sesame-noodles'     => [['label'=>'1 bowl','grams'=>250]],
			'liangpi-cold-skin-noodles' => [['label'=>'1 bowl','grams'=>250]],
			'hot-dry-noodles-wuhan-re-gan-mian' => [['label'=>'1 bowl','grams'=>300]],
			'chow-fun-flat-rice-noodles-beef' => [['label'=>'1 plate','grams'=>300]],
			'chanpon-nagasaki-noodles' => [['label'=>'1 bowl','grams'=>400]],
			'nabeyaki-udon-hot-pot-noodles' => [['label'=>'1 bowl','grams'=>400]],
			'hiyashi-chuka-cold-ramen-salad' => [['label'=>'1 plate','grams'=>300]],
			'somen-cold-thin-noodles' => [['label'=>'1 portion','grams'=>200]],
			'zaru-soba-cold-dipping'  => [['label'=>'1 portion','grams'=>200]],
			'kitsune-udon-fried-tofu-noodles' => [['label'=>'1 bowl','grams'=>400]],
			'tempura-udon-hot-soup'   => [['label'=>'1 bowl','grams'=>400]],
			'tsukemen-dipping-noodles' => [['label'=>'1 set','grams'=>350]],
			'ramen-shoyu-soy-sauce'   => [['label'=>'1 bowl','grams'=>500]],
			'ramen-miso'              => [['label'=>'1 bowl','grams'=>500]],
			'ramyeon-korean-instant-noodles' => [['label'=>'1 bowl','grams'=>400]],
			'cao-lau-hoi-an-noodles'  => [['label'=>'1 bowl','grams'=>300]],
			'mi-quang-turmeric-noodles-da-nang' => [['label'=>'1 bowl','grams'=>300]],
			'nan-gyi-thoke-thick-noodle-salad' => [['label'=>'1 plate','grams'=>250]],
			'meeshay-shan-rice-noodles-with-pork' => [['label'=>'1 plate','grams'=>250]],
			'ashlyam-fu-cold-kyrgyz-noodle-dish' => [['label'=>'1 bowl','grams'=>250]],
			'norin-cold-horsemeat-noodles' => [['label'=>'1 plate','grams'=>250]],
			'naryn-hand-pulled-noodles-with-horse' => [['label'=>'1 plate','grams'=>250]],

			// ── REMAINING CURRY & STEW DISHES ──
			'dhal-lentil'             => [['label'=>'1 bowl','grams'=>200],['label'=>'1 portion','grams'=>150]],
			'saag-aloo'               => [['label'=>'1 portion','grams'=>200]],
			'mutter-paneer-peas-cheese' => [['label'=>'1 portion','grams'=>200]],
			'cream-stew-white-stew'   => [['label'=>'1 bowl','grams'=>250]],
			'curry-udon-noodles-in-curry' => [['label'=>'1 bowl','grams'=>400]],
			'hayashi-rice-hashed-beef' => [['label'=>'1 plate','grams'=>350]],
			'dimlama-uzbek-layered-vegetable-meat' => [['label'=>'1 portion','grams'=>250]],
			'potjiekos-pot-stew'      => [['label'=>'1 bowl','grams'=>250]],
			'zharkoye-russian-pot-roast' => [['label'=>'1 portion','grams'=>250]],
			'ostri-georgian-spicy-beef-stew' => [['label'=>'1 bowl','grams'=>250]],
			'chakhokhbili-chicken-tomato-stew' => [['label'=>'1 portion','grams'=>250]],
			'satsivi-walnut-chicken-sauce' => [['label'=>'1 portion','grams'=>200]],
			'piti-azerbaijani-lamb-shank-soup' => [['label'=>'1 bowl','grams'=>300]],
			'abgoosht-lamb-chickpea-broth' => [['label'=>'1 bowl','grams'=>300]],
			'qorma-e-sabzi-green-herb-stew' => [['label'=>'1 bowl','grams'=>250]],
			'or-lam-luang-prabang-stew' => [['label'=>'1 bowl','grams'=>250]],
			'kaeng-nor-mai-bamboo-shoot-soup' => [['label'=>'1 bowl','grams'=>250]],
			'moambe-chicken-palm-butter' => [['label'=>'1 portion','grams'=>200]],
			'ndole-cameroonian-bitter-leaf-stew' => [['label'=>'1 bowl','grams'=>250]],
			'poulet-dg-chicken-cameroon' => [['label'=>'1 portion','grams'=>200]],
			'efo-riro-vegetable-soup' => [['label'=>'1 bowl','grams'=>250]],
			'ogbono-soup-wild-mango-seed' => [['label'=>'1 bowl','grams'=>250]],
			'palm-nut-soup-banga'     => [['label'=>'1 bowl','grams'=>250]],
			'afang-soup-nigerian'     => [['label'=>'1 bowl','grams'=>250]],
			'okra-soup-west-african'  => [['label'=>'1 bowl','grams'=>250]],
			'light-soup-ghanaian-tomato' => [['label'=>'1 bowl','grams'=>250]],

			// ── REMAINING DESSERTS & SWEETS ──
			'warabi-mochi-bracken-starch' => [['label'=>'3 pieces','grams'=>60]],
			'monaka-wafer-bean-paste' => [['label'=>'1 piece','grams'=>30]],
			'dorayaki-pancake-red-bean' => [['label'=>'1 piece','grams'=>65]],
			'daifuku-red-bean-mochi'  => [['label'=>'1 piece','grams'=>50]],
			'taiyaki-red-bean-filling' => [['label'=>'1 piece','grams'=>80]],
			'nian-gao-chinese-new-year-cake' => [['label'=>'1 slice','grams'=>50]],
			'sesame-balls-jian-dui'   => [['label'=>'1 ball','grams'=>40],['label'=>'3 balls','grams'=>120]],
			'mango-pudding-cantonese' => [['label'=>'1 cup','grams'=>120]],
			'douhua-soft-tofu-pudding' => [['label'=>'1 bowl','grams'=>200]],
			'red-bean-soup-sweet-tong-sui' => [['label'=>'1 bowl','grams'=>200]],
			'fa-gao-prosperity-cake'  => [['label'=>'1 piece','grams'=>50]],
			'wife-cake-lao-po-bing'   => [['label'=>'1 cake','grams'=>40]],
			'dasik-pressed-tea-cookie' => [['label'=>'1 piece','grams'=>15],['label'=>'5 pieces','grams'=>75]],
			'gaz-isfahan-nougat'      => [['label'=>'1 piece','grams'=>20],['label'=>'3 pieces','grams'=>60]],
			'sohan-saffron-brittle'   => [['label'=>'1 piece','grams'=>25]],
			'bastani-sonnati-saffron-ice-cream' => [['label'=>'1 scoop','grams'=>70],['label'=>'2 scoops','grams'=>140]],
			'sheer-khurma-vermicelli-milk-dessert' => [['label'=>'1 bowl','grams'=>150]],
			'firni-cornflour-milk-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'sheer-yakh-afghan-ice-cream' => [['label'=>'1 scoop','grams'=>80]],
			'guava-duff-bahamian-steamed-pudding' => [['label'=>'1 portion','grams'=>120]],
			'nutmeg-ice-cream-grenada' => [['label'=>'1 scoop','grams'=>66]],
			'habichuelas-con-dulce-sweet-bean-cream' => [['label'=>'1 bowl','grams'=>200]],
			'che-ba-mau-three-colour-dessert' => [['label'=>'1 glass','grams'=>200]],
			'banh-flan-vietnamese-creme-caramel' => [['label'=>'1 flan','grams'=>100]],
			'bua-loy-glutinous-rice-balls-in-coconut' => [['label'=>'1 bowl','grams'=>200]],
			'kanom-krok-coconut-pancake-cups' => [['label'=>'4 cups','grams'=>60]],
			'nom-krok-khmer-coconut-cake' => [['label'=>'4 pieces','grams'=>60]],
			'num-ansorm-khmer-sticky-rice-cake' => [['label'=>'1 piece','grams'=>100]],
			'fakkai-tuvalu-coconut-taro-dessert' => [['label'=>'1 portion','grams'=>120]],
			'vakalolo-fijian-coconut-cassava-dessert' => [['label'=>'1 portion','grams'=>120]],
			'cassava-cake-fijian-sweet' => [['label'=>'1 slice','grams'=>80]],
			'suafai-samoan-banana-soup' => [['label'=>'1 bowl','grams'=>200]],
			'faaliifu-fai-samoan-banana-in-coconut' => [['label'=>'1 portion','grams'=>150]],
			'vai-siaine-banana-in-coconut-cream' => [['label'=>'1 portion','grams'=>150]],
			'topai-tongan-dumplings-in-coconut' => [['label'=>'3 pieces','grams'=>100]],
			'khao-lam-bamboo-sticky-rice' => [['label'=>'1 piece','grams'=>100]],
			'koeksisters-braided-doughnut' => [['label'=>'1 piece','grams'=>50]],
			'farturas-fried-dough-portuguese-churros' => [['label'=>'1 fartura','grams'=>60],['label'=>'3 farturas','grams'=>180]],
			'rabanadas-portuguese-french-toast' => [['label'=>'2 slices','grams'=>100]],
			'ovos-moles-de-aveiro-egg-yolk-sweet' => [['label'=>'1 piece','grams'=>15],['label'=>'5 pieces','grams'=>75]],
			'serradura-macau-sawdust-pudding' => [['label'=>'1 cup','grams'=>120]],
			'arroz-doce-portuguese-rice-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'aletria-vermicelli-pudding' => [['label'=>'1 bowl','grams'=>150]],
			'pao-de-lo-portuguese-sponge-cake' => [['label'=>'1 slice','grams'=>60]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 69 );
	}

	/** Seed v70: Serving sizes batch 13 — absolute last remaining foods. */
	public static function seed_v70(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 70 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';

		$servings = [
			// ── REMAINING KEBABS & GRILLS ──
			'sis-kebab-lamb'          => [['label'=>'1 skewer','grams'=>100],['label'=>'2 skewers','grams'=>200]],
			'urfa-kebab'              => [['label'=>'1 skewer','grams'=>100]],
			'beyti-kebab-wrapped-in-lavash' => [['label'=>'1 portion','grams'=>200]],
			'ali-nazik-kebab'         => [['label'=>'1 portion','grams'=>200]],
			'tantuni-beef-wrap'       => [['label'=>'1 wrap','grams'=>200]],
			'cag-kebabi-spit-roasted-lamb' => [['label'=>'1 portion','grams'=>150]],
			'tavuk-sis-chicken-skewers' => [['label'=>'2 skewers','grams'=>120]],
			'patlican-kebab-aubergine-meat' => [['label'=>'1 portion','grams'=>200]],
			'testi-kebabi-pottery-kebab' => [['label'=>'1 portion','grams'=>250]],
			'kokorec-grilled-offal-wrap' => [['label'=>'½ portion','grams'=>150],['label'=>'1 portion','grams'=>300]],
			'barg-kebab-lamb-fillet-kebab' => [['label'=>'1 skewer','grams'=>120]],
			'chenjeh-kebab-lamb-cube-kebab' => [['label'=>'1 skewer','grams'=>120]],
			'tikka-kebab-afghan-style-lamb' => [['label'=>'1 skewer','grams'=>120]],
			'lyulya-kebab-azerbaijani-minced-lamb' => [['label'=>'1 skewer','grams'=>100]],
			'khorovats-armenian-grilled-meat' => [['label'=>'1 portion','grams'=>200]],
			'mtsvadi-georgian-shashlik-kebab' => [['label'=>'1 skewer','grams'=>100],['label'=>'2 skewers','grams'=>200]],
			'shashlik-uzbek-marinated-lamb' => [['label'=>'1 skewer','grams'=>100]],
			'mishkaki-beef-skewers'   => [['label'=>'2 skewers','grams'=>80],['label'=>'4 skewers','grams'=>160]],
			'brochettes-rwandan-grilled-meat' => [['label'=>'2 skewers','grams'=>100]],
			'anticuchos-beef-heart-skewers' => [['label'=>'2 skewers','grams'=>100]],
			'dakgochi-chicken-skewer-street' => [['label'=>'1 skewer','grams'=>60],['label'=>'3 skewers','grams'=>180]],

			// ── REMAINING DUMPLINGS & PASTRIES ──
			'cheung-fun-rice-noodle-roll-shrimp' => [['label'=>'1 roll','grams'=>100],['label'=>'3 rolls','grams'=>300]],
			'lo-mai-gai-sticky-rice-in-lotus-leaf' => [['label'=>'1 piece','grams'=>200]],
			'turnip-cake-lo-bak-go-pan-fried' => [['label'=>'2 slices','grams'=>80],['label'=>'4 slices','grams'=>160]],
			'phoenix-claws-chicken-feet-braised' => [['label'=>'3 feet','grams'=>60]],
			'zongzi-sticky-rice-dumpling' => [['label'=>'1 zongzi','grams'=>150]],
			'wonton-fried-1pc'        => [['label'=>'5 wontons','grams'=>75],['label'=>'10 wontons','grams'=>150]],
			'shumai-open-top-dumpling-1pc' => [['label'=>'4 pieces','grams'=>80]],
			'sheng-jian-bao-pan-fried-bun-1pc' => [['label'=>'4 buns','grams'=>200]],
			'ashak-leek-dumplings'    => [['label'=>'6 pieces','grams'=>180]],
			'tuhum-barak-tajik-egg-dumplings' => [['label'=>'6 pieces','grams'=>180]],
			'chuchvara-tiny-uzbek-dumplings-in-broth' => [['label'=>'1 bowl','grams'=>300]],
			'dushbara-tiny-lamb-dumplings-in-broth' => [['label'=>'1 bowl','grams'=>300]],
			'oromo-kyrgyz-steamed-meat-roll' => [['label'=>'1 portion','grams'=>200]],
			'hoentay-buckwheat-dumplings-haa' => [['label'=>'6 pieces','grams'=>200]],
			'coxinha-chicken-croquette' => [['label'=>'1 piece','grams'=>60],['label'=>'3 pieces','grams'=>180]],
			'arancini-rice-ball'      => [['label'=>'1 ball','grams'=>80],['label'=>'2 balls','grams'=>160]],
			'rissois-de-camarao-prawn-rissoles' => [['label'=>'2 pieces','grams'=>60],['label'=>'4 pieces','grams'=>120]],
			'croquetes-de-carne-meat-croquettes' => [['label'=>'2 pieces','grams'=>50],['label'=>'4 pieces','grams'=>100]],
			'chamucas-portuguese-samosas' => [['label'=>'2 pieces','grams'=>60]],
			'empada-de-galinha-chicken-pies-1pc' => [['label'=>'1 pie','grams'=>40],['label'=>'3 pies','grams'=>120]],
			'sambousek-fried-pastry-meat' => [['label'=>'2 pieces','grams'=>60],['label'=>'5 pieces','grams'=>150]],
			'fatayer-spinach-pie'     => [['label'=>'1 piece','grams'=>60],['label'=>'3 pieces','grams'=>180]],
			'kibbeh-fried-stuffed'    => [['label'=>'1 piece','grams'=>50],['label'=>'3 pieces','grams'=>150]],
			'briouats-stuffed-pastry' => [['label'=>'2 pieces','grams'=>50],['label'=>'5 pieces','grams'=>125]],
			'samsa-kyrgyz-baked-meat-pastry' => [['label'=>'1 samsa','grams'=>100]],
			'sambusa-somali-beef'     => [['label'=>'2 pieces','grams'=>80]],
			'brik-tunisian-fried-pastry' => [['label'=>'1 brik','grams'=>80]],

			// ── REMAINING SALADS ──
			'niçoise-salad'           => [['label'=>'1 plate','grams'=>250]],
			'salade-lyonnaise-frisee-lardons' => [['label'=>'1 plate','grams'=>200]],
			'salade-nicoise-traditional' => [['label'=>'1 plate','grams'=>250]],
			'vinegret-beetroot-vinaigrette' => [['label'=>'1 portion','grams'=>150]],
			'olivier-salad-russian-salad' => [['label'=>'1 portion','grams'=>150]],
			'fattoush-salad'          => [['label'=>'1 plate','grams'=>150]],
			'panzanella-bread-salad'  => [['label'=>'1 plate','grams'=>200]],
			'caponata-sicilian-aubergine' => [['label'=>'1 portion','grams'=>120]],
			'zaalouk-aubergine-salad' => [['label'=>'1 portion','grams'=>100]],
			'taktouka-pepper-and-tomato-salad' => [['label'=>'1 portion','grams'=>100]],
			'naem-khao-crispy-rice-salad' => [['label'=>'1 plate','grams'=>150]],
			'yam-nua-thai-beef-salad' => [['label'=>'1 plate','grams'=>150]],
			'laab-gai-thai-chicken-laab' => [['label'=>'1 plate','grams'=>120]],
			'yam-woon-sen-glass-noodle-salad' => [['label'=>'1 plate','grams'=>150]],
			'gin-thoke-ginger-salad'  => [['label'=>'1 portion','grams'=>100]],
			'tohu-thoke-chickpea-tofu-salad' => [['label'=>'1 portion','grams'=>120]],
			'samusa-thoke-samosa-salad' => [['label'=>'1 portion','grams'=>120]],
			'pleah-sach-ko-khmer-raw-beef-salad' => [['label'=>'1 plate','grams'=>120]],
			'kisir-bulgur-salad'      => [['label'=>'1 portion','grams'=>120]],
			'piyaz-white-bean-salad'  => [['label'=>'1 portion','grams'=>120]],
			'tabbouleh'               => [['label'=>'1 portion','grams'=>130]],

			// ── REMAINING RICE & ONE-POT DISHES ──
			'claypot-rice-lap-cheong' => [['label'=>'1 pot','grams'=>350]],
			'hainanese-chicken-rice'  => [['label'=>'1 plate','grams'=>350]],
			'lu-rou-fan-braised-pork-rice-taiwan' => [['label'=>'1 bowl','grams'=>300]],
			'congee-century-egg-pork' => [['label'=>'1 bowl','grams'=>350]],
			'sticky-rice-with-toppings' => [['label'=>'1 portion','grams'=>150]],
			'sekihan-red-bean-rice'   => [['label'=>'1 bowl','grams'=>200]],
			'chazuke-tea-over-rice'   => [['label'=>'1 bowl','grams'=>250]],
			'tamago-kake-gohan-raw-egg-on-rice' => [['label'=>'1 bowl','grams'=>250]],
			'natto-on-rice'           => [['label'=>'1 bowl','grams'=>250]],
			'makunouchi-bento-boxed-lunch-avg' => [['label'=>'1 bento','grams'=>350]],
			'salmon-bento-convenience-store' => [['label'=>'1 bento','grams'=>300]],
			'omurice-korean-omelette-rice' => [['label'=>'1 plate','grams'=>300]],
			'cupbap-rice-bowl-with-toppings' => [['label'=>'1 bowl','grams'=>300]],
			'bokkeumbap-kimchi-fried-rice' => [['label'=>'1 plate','grams'=>300]],
			'dolsot-bap-stone-pot-mixed-rice' => [['label'=>'1 bowl','grams'=>250]],
			'kongnamul-bap-bean-sprout-rice' => [['label'=>'1 bowl','grams'=>250]],
			'hobakjuk-pumpkin-porridge' => [['label'=>'1 bowl','grams'=>250]],
			'patjuk-red-bean-porridge' => [['label'=>'1 bowl','grams'=>250]],
			'juk-korean-rice-porridge-plain' => [['label'=>'1 bowl','grams'=>300]],
			'arroz-de-tomate-tomato-rice' => [['label'=>'1 plate','grams'=>200]],
			'arroz-de-feijao-bean-rice' => [['label'=>'1 plate','grams'=>250]],
			'locrio-de-pollo-dominican-chicken-rice' => [['label'=>'1 plate','grams'=>300]],
			'arroz-con-pollo-peruvian' => [['label'=>'1 plate','grams'=>300]],
			'dampukht-slow-cooked-lamb-rice' => [['label'=>'1 plate','grams'=>300]],
			'adas-polo-lentil-rice-with-raisins' => [['label'=>'1 plate','grams'=>250]],
			'baghali-polo-dill-fava-bean-rice' => [['label'=>'1 plate','grams'=>250]],
			'zereshk-polo-ba-morgh-barberry-chicken-rice' => [['label'=>'1 plate','grams'=>300]],
			'wali-wa-nazi-coconut-rice' => [['label'=>'1 plate','grams'=>250]],
			'nasi-uduk-coconut-steamed-rice' => [['label'=>'1 plate','grams'=>250]],
			'arroz-negro-black-squid-ink-rice' => [['label'=>'1 plate','grams'=>250]],
			'diri-djon-djon-black-mushroom-rice' => [['label'=>'1 plate','grams'=>250]],
			'panta-bhat-fermented-rice-pohela-boishakh' => [['label'=>'1 bowl','grams'=>250]],
			'htamin-jin-shan-rice-balls' => [['label'=>'3 balls','grams'=>100]],
		];

		foreach ( $servings as $slug => $sizes ) {
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$t} SET serving_sizes = %s WHERE slug = %s AND (serving_sizes IS NULL OR serving_sizes = '')", // phpcs:ignore
				$json, $slug
			) );
		}
		update_option( 'fcc_seed_version', 70 );
	}

	/**
	 * Seed v71: Auto-assign serving sizes to ALL remaining foods that still have none.
	 * Uses category-based defaults so no food gets missed by slug mismatch.
	 */
	public static function seed_v71(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 71 ) { return; }
		global $wpdb;
		$ft = $wpdb->prefix . 'fcc_foods';
		$ct = $wpdb->prefix . 'fcc_categories';

		// Build category slug → id map.
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cat_ids = [];
		foreach ( $cats as $c ) { $cat_ids[ $c['slug'] ] = (int) $c['id']; }

		// Default serving sizes per category.
		$defaults = [
			'fruit-veg'           => [['label'=>'1 portion','grams'=>80],['label'=>'1 cup','grams'=>130]],
			'meat-poultry'        => [['label'=>'1 portion','grams'=>120],['label'=>'1 serving','grams'=>150]],
			'fish-seafood'        => [['label'=>'1 portion','grams'=>120],['label'=>'1 fillet','grams'=>140]],
			'dairy-eggs'          => [['label'=>'1 portion','grams'=>30],['label'=>'1 serving','grams'=>50]],
			'bread-cereals'       => [['label'=>'1 portion','grams'=>50],['label'=>'1 serving','grams'=>80]],
			'nuts-seeds'          => [['label'=>'1 handful','grams'=>25],['label'=>'1 tablespoon','grams'=>10]],
			'fats-oils'           => [['label'=>'1 tablespoon','grams'=>14],['label'=>'1 teaspoon','grams'=>5]],
			'drinks'              => [['label'=>'1 glass','grams'=>250],['label'=>'1 cup','grams'=>200]],
			'legumes-pulses'      => [['label'=>'1 cup','grams'=>170],['label'=>'1 portion','grams'=>150]],
			'condiments'          => [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 teaspoon','grams'=>5]],
			'snacks-confectionery' => [['label'=>'1 portion','grams'=>30],['label'=>'1 serving','grams'=>50]],
			'takeaway'            => [['label'=>'1 portion','grams'=>250],['label'=>'1 plate','grams'=>350]],
		];

		foreach ( $defaults as $cat_slug => $sizes ) {
			$cid = $cat_ids[ $cat_slug ] ?? 0;
			if ( ! $cid ) { continue; }
			$json = wp_json_encode( $sizes );
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$ft} SET serving_sizes = %s WHERE category_id = %d AND (serving_sizes IS NULL OR serving_sizes = '' OR serving_sizes = '[]')", // phpcs:ignore
				$json, $cid
			) );
		}
		update_option( 'fcc_seed_version', 71 );
	}

	/** Seed v72: Add iron_mg, calcium_mg, vitamin_c_mg columns. */
	public static function seed_v72(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 72 ) { return; }
		global $wpdb;
		$t = $wpdb->prefix . 'fcc_foods';
		$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$t}" ); // phpcs:ignore
		if ( ! in_array( 'iron_mg', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$t} ADD COLUMN iron_mg decimal(8,3) DEFAULT NULL AFTER caffeine_mg" ); // phpcs:ignore
		}
		if ( ! in_array( 'calcium_mg', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$t} ADD COLUMN calcium_mg decimal(10,3) DEFAULT NULL AFTER iron_mg" ); // phpcs:ignore
		}
		if ( ! in_array( 'vitamin_c_mg', $cols, true ) ) {
			$wpdb->query( "ALTER TABLE {$t} ADD COLUMN vitamin_c_mg decimal(8,2) DEFAULT NULL AFTER calcium_mg" ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 72 );
	}

	/** Seed v73: Missing fruits from dailycalorie.app comparison. */
	public static function seed_v73(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 73 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv = $cid['fruit-veg'] ?? 0;

		$foods = [
			// name, cat, kcal, kJ, protein, carbs, sugars, fat, saturates, fibre, salt,
			// fish,shellfish,dairy,eggs,nuts,gluten,soy,celery, keto,paleo,halal,kosher,vegan,veg,
			// serving_sizes (JSON array)
			['Honeydew Melon',$fv,36,151,0.5,9.1,8.1,0.1,0.0,0.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup diced','grams'=>170],['label'=>'1 wedge','grams'=>125]]],
			['Cantaloupe Melon',$fv,34,142,0.8,8.2,7.9,0.2,0.1,0.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup diced','grams'=>160],['label'=>'1 wedge','grams'=>125]]],
			['Nectarine',$fv,44,184,1.1,10.6,7.9,0.3,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 medium','grams'=>142]]],
			['Rhubarb (raw)',$fv,21,88,0.9,4.5,1.1,0.2,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 cup diced','grams'=>122],['label'=>'1 stalk','grams'=>50]]],
			['Cranberry (fresh, raw)',$fv,46,192,0.5,12.2,4.0,0.1,0.0,4.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>100]]],
			['Acerola (Barbados cherry)',$fv,32,134,0.4,7.7,0.0,0.3,0.1,1.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>98],['label'=>'10 cherries','grams'=>50]]],
			['Cherimoya',$fv,75,314,1.6,17.7,12.9,0.7,0.0,3.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>235],['label'=>'1 cup','grams'=>160]]],
			['Feijoa (pineapple guava)',$fv,55,230,1.0,12.9,8.2,0.6,0.2,6.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>42],['label'=>'1 cup','grams'=>243]]],
			['Mangosteen',$fv,73,305,0.4,18.0,0.0,0.6,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>76],['label'=>'1 cup','grams'=>196]]],
			['Jujube (Chinese date, fresh)',$fv,79,331,1.2,20.2,0.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'3 fruits','grams'=>30],['label'=>'1 oz','grams'=>28]]],
			['Longan (fresh)',$fv,60,251,1.3,15.1,0.0,0.1,0.0,1.1,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'10 fruits','grams'=>32],['label'=>'1 cup','grams'=>150]]],
			['Sapodilla',$fv,83,347,0.4,20.0,0.0,1.1,0.2,5.3,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>170]]],
			['Quince',$fv,57,238,0.4,15.3,0.0,0.1,0.0,1.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>92]]],
			['Nance',$fv,82,343,0.7,17.1,0.0,1.3,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>150]]],
			['Pitanga (Surinam cherry)',$fv,33,138,0.8,7.5,0.0,0.4,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>173]]],
			['Sapote (Mamey)',$fv,124,519,1.5,32.1,0.0,0.5,0.2,5.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>175]]],
			['Carissa (Natal plum)',$fv,62,259,0.5,13.6,0.0,1.3,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>20]]],
			['Golden Kiwi',$fv,63,264,1.0,15.8,12.3,0.3,0.0,1.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>75],['label'=>'2 fruits','grams'=>150]]],
			['Abiyuch',$fv,69,289,1.7,17.6,0.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>150]]],
			['Rose Apple',$fv,25,105,0.6,5.7,0.0,0.3,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>40]]],
			['Yuzu (whole fruit)',$fv,21,88,0.5,7.0,1.0,0.1,0.0,1.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>60]]],
			['Custard Apple (Bullock\'s heart)',$fv,101,423,1.7,25.2,0.0,0.6,0.0,2.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>150]]],
			['Java Plum (Jambolan)',$fv,60,251,0.7,15.6,0.0,0.2,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>135]]],
			['Sugar Apple (Sweetsop)',$fv,94,393,2.1,23.6,0.0,0.3,0.0,4.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>155]]],
			['Crabapple',$fv,76,318,0.4,19.9,0.0,0.3,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup sliced','grams'=>110]]],
			['Wild Blueberry',$fv,57,238,0.7,14.5,10.0,0.3,0.0,2.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>140]]],
			['Yellow Watermelon',$fv,30,126,0.6,7.6,6.2,0.2,0.0,0.4,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup diced','grams'=>150],['label'=>'1 wedge','grams'=>280]]],
			['Roselle (Hibiscus sabdariffa)',$fv,49,205,1.0,11.3,0.0,0.6,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>57]]],
			['Miracle Berry',$fv,46,192,0.7,10.0,5.0,0.4,0.0,2.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'3 berries','grams'=>9]]],
			['Horned Melon (Kiwano)',$fv,44,184,1.8,7.6,0.0,1.3,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 fruit','grams'=>209]]],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; }
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'serving_sizes'=> wp_json_encode( $f[25] ),
				'source_notes'=>'USDA FDC. Seeded v73.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 73 );
	}

	/** Seed v74: Missing vegetables, fish, dairy from dailycalorie.app. */
	public static function seed_v74(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 74 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$fv=$cid['fruit-veg']??0; $fs=$cid['fish-seafood']??0; $de=$cid['dairy-eggs']??0;
		$mp=$cid['meat-poultry']??0; $ns=$cid['nuts-seeds']??0;

		$foods = [
			// ── MISSING VEGETABLES ──
			['Chayote (raw)',$fv,19,79,0.8,4.5,1.7,0.1,0.0,1.7,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 chayote','grams'=>200],['label'=>'1 cup chopped','grams'=>130]]],
			['Jicama (raw)',$fv,38,159,0.7,8.8,1.8,0.1,0.0,4.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup sliced','grams'=>120],['label'=>'1 medium','grams'=>450]]],
			['Tomatillo (raw)',$fv,32,134,1.0,5.8,3.9,1.0,0.1,1.9,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 medium','grams'=>34],['label'=>'1 cup chopped','grams'=>130]]],
			['Dandelion Greens (raw)',$fv,45,188,2.7,9.2,0.7,0.7,0.2,3.5,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup chopped','grams'=>55]]],
			['Hearts of Palm (canned)',$fv,28,117,2.5,4.6,0.0,0.6,0.1,2.4,0.4, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 piece','grams'=>33],['label'=>'1 cup','grams'=>146]]],
			['Nopales (cactus paddle, raw)',$fv,16,67,1.3,3.3,1.1,0.1,0.0,2.2,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 cup sliced','grams'=>86],['label'=>'1 pad','grams'=>100]]],
			['Turnip (raw)',$fv,28,117,0.9,6.4,3.8,0.1,0.0,1.8,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 medium','grams'=>122],['label'=>'1 cup cubed','grams'=>130]]],
			['Beet Greens (raw)',$fv,22,92,2.2,4.3,0.5,0.1,0.0,3.7,0.2, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>38]]],
			['Mustard Greens (raw)',$fv,27,113,2.9,4.7,1.3,0.4,0.0,3.2,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup chopped','grams'=>56]]],
			['Snow Peas (raw)',$fv,42,176,2.8,7.6,4.0,0.2,0.0,2.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>63],['label'=>'10 pods','grams'=>34]]],
			['Sugar Snap Peas (raw)',$fv,42,176,2.8,7.6,4.0,0.2,0.0,2.6,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>63],['label'=>'10 pods','grams'=>30]]],
			['Baby Carrots',$fv,35,146,0.6,8.2,4.8,0.1,0.0,2.9,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'8 carrots','grams'=>80],['label'=>'1 cup','grams'=>128]]],
			['Daikon Radish (raw)',$fv,18,75,0.6,4.1,2.5,0.1,0.0,1.6,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 cup sliced','grams'=>116],['label'=>'1 daikon (7")','grams'=>340]]],
			['Okra (raw)',$fv,33,138,1.9,7.5,1.5,0.2,0.1,3.2,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup sliced','grams'=>100],['label'=>'8 pods','grams'=>85]]],
			['Parsley (fresh)',$fv,36,151,3.0,6.3,0.9,0.8,0.1,3.3,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup chopped','grams'=>60],['label'=>'1 tablespoon','grams'=>4]]],
			['Lemongrass (raw)',$fv,99,414,1.8,25.3,0.0,0.5,0.1,0.0,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 tablespoon chopped','grams'=>5],['label'=>'1 stalk','grams'=>20]]],
			['Pumpkin (raw)',$fv,26,109,1.0,6.5,2.8,0.1,0.1,0.5,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup cubed','grams'=>116]]],
			['Brussels Sprouts (raw)',$fv,43,180,3.4,9.0,2.2,0.3,0.1,3.8,0.0, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 cup','grams'=>88],['label'=>'5 sprouts','grams'=>95]]],
			['Artichoke (globe, raw)',$fv,47,197,3.3,10.5,1.0,0.2,0.0,5.4,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,1,1, [['label'=>'1 medium','grams'=>128]]],
			['Purslane (raw)',$fv,20,84,2.0,3.4,0.0,0.4,0.0,0.0,0.0, 0,0,0,0,0,0,0,0, 1,1,1,1,1,1, [['label'=>'1 cup','grams'=>43]]],

			// ── MISSING FISH & SEAFOOD ──
			['Mahi-Mahi (raw)',$fs,85,356,18.5,0.0,0.0,0.7,0.2,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>170]]],
			['Swordfish (raw)',$fs,121,506,19.8,0.0,0.0,4.0,1.1,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 steak','grams'=>170]]],
			['Grouper (raw)',$fs,92,385,19.4,0.0,0.0,1.0,0.2,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>170]]],
			['Red Snapper (raw)',$fs,100,418,20.5,0.0,0.0,1.3,0.3,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>170]]],
			['Halibut (raw)',$fs,110,460,20.8,0.0,0.0,2.3,0.3,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>200]]],
			['Yellowtail (raw)',$fs,146,611,23.1,0.0,0.0,5.2,1.3,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>150]]],
			['Wahoo (raw)',$fs,108,452,19.3,0.0,0.0,3.1,0.8,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>170]]],
			['Orange Roughy (raw)',$fs,69,289,14.7,0.0,0.0,0.7,0.0,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>140]]],
			['Shark (raw)',$fs,130,544,20.9,0.0,0.0,4.5,0.9,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,0,0,0,0, [['label'=>'1 steak','grams'=>150]]],
			['Sturgeon (raw)',$fs,105,439,16.1,0.0,0.0,4.0,0.9,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>150]]],
			['Sea Urchin (uni, raw)',$fs,120,502,13.0,3.3,0.0,4.3,0.0,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'5 pieces','grams'=>25],['label'=>'1 tray','grams'=>60]]],
			['Langoustine (raw)',$fs,77,322,16.5,0.0,0.0,0.9,0.1,0.0,0.5, 1,1,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'6 tails','grams'=>120]]],
			['Bluefish (raw)',$fs,124,519,20.0,0.0,0.0,4.2,0.9,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>150]]],
			['Mullet (raw)',$fs,117,489,19.4,0.0,0.0,3.8,1.1,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>120]]],
			['Pompano (raw)',$fs,164,686,18.5,0.0,0.0,9.5,3.5,0.0,0.1, 1,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet','grams'=>115]]],

			// ── MISSING DAIRY & CHEESE ──
			['Ricotta Cheese (whole milk)',$de,174,728,11.3,3.0,0.3,13.0,8.3,0.0,0.1, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'½ cup','grams'=>124],['label'=>'¼ cup','grams'=>62]]],
			['Colby Jack Cheese',$de,393,1644,24.0,1.5,0.5,32.0,20.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>21],['label'=>'1 oz','grams'=>28]]],
			['Roquefort Cheese',$de,369,1544,21.5,2.0,0.0,30.6,19.3,0.0,3.5, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1, [['label'=>'1 oz','grams'=>28],['label'=>'1 crumble','grams'=>5]]],
			['String Cheese (mozzarella)',$de,280,1172,28.0,1.0,0.5,17.0,10.0,0.0,2.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 stick','grams'=>28]]],
			['Edam Cheese',$de,357,1494,25.0,1.4,1.4,27.8,17.6,0.0,1.8, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>25],['label'=>'1 oz','grams'=>28]]],
			['Fontina Cheese',$de,389,1628,25.6,1.6,1.6,31.1,19.2,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 oz','grams'=>28],['label'=>'1 slice','grams'=>25]]],
			['Monterey Jack Cheese',$de,373,1561,24.5,0.7,0.5,30.3,19.1,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>21],['label'=>'1 oz','grams'=>28]]],
			['Gouda Cheese',$de,356,1490,25.0,2.2,2.2,27.4,17.6,0.0,2.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>22],['label'=>'1 oz','grams'=>28]]],
			['Neufchatel Cheese',$de,253,1059,9.2,2.9,2.9,22.8,14.4,0.0,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 oz','grams'=>28],['label'=>'1 tablespoon','grams'=>15]]],
			['Provolone Cheese',$de,351,1469,25.6,2.1,0.6,26.6,17.1,0.0,2.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>21],['label'=>'1 oz','grams'=>28]]],
			['Swiss Cheese',$de,380,1590,27.0,5.4,1.5,28.0,18.0,0.0,0.5, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 slice','grams'=>22],['label'=>'1 oz','grams'=>28]]],
			['Goat Cheese (hard)',$de,452,1891,31.0,2.2,2.2,36.0,25.0,0.0,1.5, 0,0,1,0,0,0,0,0, 1,0,1,0,0,1, [['label'=>'1 oz','grams'=>28]]],

			// ── MISSING NUTS & SEEDS ──
			['Almond (whole, raw)',$ns,579,2423,21.2,21.7,4.4,49.9,3.7,12.5,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1, [['label'=>'1 oz (23 almonds)','grams'=>28],['label'=>'1 handful','grams'=>20]]],
			['Cashew (raw)',$ns,553,2314,18.2,30.2,5.9,43.9,7.8,3.3,0.0, 0,0,0,0,1,0,0,0, 0,0,1,1,1,1, [['label'=>'1 oz','grams'=>28],['label'=>'1 handful','grams'=>25]]],
			['Pecan (raw)',$ns,691,2891,9.2,13.9,4.0,72.0,6.2,9.6,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1, [['label'=>'1 oz (19 halves)','grams'=>28]]],
			['Hazelnut / Filbert (raw)',$ns,628,2628,15.0,16.7,4.3,60.8,4.5,9.7,0.0, 0,0,0,0,1,0,0,0, 1,1,1,1,1,1, [['label'=>'1 oz','grams'=>28],['label'=>'10 nuts','grams'=>14]]],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; }
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'serving_sizes'=> wp_json_encode( $f[25] ),
				'source_notes'=>'USDA FDC. Seeded v74.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 74 );
	}

	/** Seed v75: Missing beef, poultry, dairy from dailycalorie.app. */
	public static function seed_v75(): void {
		if ( (int) get_option( 'fcc_seed_version', 0 ) >= 75 ) { return; }
		global $wpdb;
		$ct = $wpdb->prefix . 'fcc_categories';
		$cats = $wpdb->get_results( "SELECT id, slug FROM {$ct}", ARRAY_A ); // phpcs:ignore
		$cid = [];
		foreach ( $cats as $c ) { $cid[ $c['slug'] ] = (int) $c['id']; }
		$mp=$cid['meat-poultry']??0; $de=$cid['dairy-eggs']??0; $fo=$cid['fats-oils']??0;

		$foods = [
			// ── BEEF CUTS (raw) ──
			['Beef Flank Steak (raw)',$mp,155,649,21.2,0.0,0.0,7.3,3.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>200]]],
			['Beef Brisket (raw)',$mp,213,891,17.4,0.0,0.0,15.5,6.2,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 portion','grams'=>200]]],
			['Beef Ribeye Steak (raw)',$mp,250,1046,17.4,0.0,0.0,19.8,8.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>250]]],
			['Beef T-Bone Steak (raw)',$mp,182,762,19.7,0.0,0.0,11.0,4.3,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>300]]],
			['Beef Porterhouse Steak (raw)',$mp,182,762,19.7,0.0,0.0,11.0,4.3,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>350]]],
			['Beef Skirt Steak (raw)',$mp,182,762,20.3,0.0,0.0,10.6,4.4,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>200]]],
			['Beef Chuck Roast (raw)',$mp,165,690,19.0,0.0,0.0,9.6,3.8,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0, [['label'=>'1 portion','grams'=>200]]],
			['Beef Short Ribs (raw)',$mp,295,1234,17.8,0.0,0.0,24.5,10.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'3 ribs','grams'=>200]]],
			['Beef Tri-Tip (raw)',$mp,143,598,20.8,0.0,0.0,6.2,2.4,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 portion','grams'=>200]]],
			['Beef Tenderloin / Fillet (raw)',$mp,157,657,20.2,0.0,0.0,8.1,3.2,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 fillet steak','grams'=>200]]],
			['Beef Rump Steak (raw)',$mp,140,586,20.8,0.0,0.0,6.0,2.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak','grams'=>225]]],
			['Beef Topside (raw)',$mp,130,544,21.5,0.0,0.0,4.8,2.0,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Beef Silverside (raw)',$mp,138,577,20.5,0.0,0.0,5.9,2.5,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Beef Shin (raw)',$mp,140,586,20.2,0.0,0.0,6.5,2.8,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,1,0,0, [['label'=>'1 portion','grams'=>200]]],
			['Wagyu Beef (raw, A5)',$mp,335,1402,14.0,0.0,0.0,30.5,13.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 steak (100g)','grams'=>100]]],
			['Veal Loin (raw)',$mp,110,460,20.0,0.0,0.0,3.0,1.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 cutlet','grams'=>150]]],
			['Veal Escalope (raw)',$mp,105,439,21.0,0.0,0.0,2.0,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,1,0,0, [['label'=>'1 escalope','grams'=>120]]],

			// ── POULTRY & GAME ──
			['Chicken Ground (raw)',$mp,143,598,17.4,0.0,0.0,8.1,2.2,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0, [['label'=>'1 portion','grams'=>125]]],
			['Turkey Ground (raw)',$mp,150,628,17.5,0.0,0.0,8.3,2.3,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0, [['label'=>'1 portion','grams'=>125]]],
			['Duck Breast (raw)',$mp,135,565,18.3,0.0,0.0,6.6,1.8,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 breast','grams'=>200]]],
			['Duck Leg (raw)',$mp,195,816,16.0,0.0,0.0,14.5,4.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 leg','grams'=>200]]],
			['Goose (roasted)',$mp,238,996,29.0,0.0,0.0,13.0,4.6,0.0,0.3, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Guinea Fowl (raw)',$mp,110,460,20.6,0.0,0.0,2.5,0.6,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Squab / Pigeon (raw)',$mp,142,594,17.5,0.0,0.0,7.5,2.0,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 squab','grams'=>180]]],
			['Wild Duck (raw)',$mp,123,515,19.9,0.0,0.0,4.3,1.5,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'½ duck','grams'=>200]]],
			['Elk (raw)',$mp,111,464,22.8,0.0,0.0,1.5,0.6,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'1 steak','grams'=>170]]],
			['Dove Breast (raw)',$mp,130,544,22.0,0.0,0.0,4.5,1.3,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,1,0,0,0, [['label'=>'2 breasts','grams'=>80]]],
			['Salami (Genoa)',$mp,370,1548,22.0,1.0,0.5,30.5,11.5,0.0,4.0, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0, [['label'=>'3 slices','grams'=>30],['label'=>'1 oz','grams'=>28]]],
			['Pancetta',$mp,390,1632,14.0,0.0,0.0,37.0,13.5,0.0,3.5, 0,0,0,0,0,0,0,0, 1,0,0,0,0,0, [['label'=>'2 slices','grams'=>20],['label'=>'1 oz','grams'=>28]]],
			['Pork Tenderloin (raw)',$mp,109,456,21.6,0.0,0.0,2.2,0.7,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Pork Loin (raw)',$mp,143,598,20.7,0.0,0.0,6.3,2.2,0.0,0.1, 0,0,0,0,0,0,0,0, 1,1,0,0,0,0, [['label'=>'1 chop','grams'=>150]]],
			['Lamb Leg (raw)',$mp,175,732,19.0,0.0,0.0,10.8,4.8,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0, [['label'=>'1 portion','grams'=>150]]],
			['Lamb Shoulder (raw)',$mp,205,858,17.5,0.0,0.0,14.8,6.8,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0, [['label'=>'1 portion','grams'=>200]]],
			['Lamb Shank (raw)',$mp,165,690,18.5,0.0,0.0,10.0,4.5,0.0,0.1, 0,0,0,0,0,0,0,0, 0,1,1,0,0,0, [['label'=>'1 shank','grams'=>300]]],

			// ── DAIRY (remaining) ──
			['Eggnog',$de,88,368,3.7,8.4,8.4,4.2,2.6,0.0,0.1, 0,0,1,1,0,0,0,0, 0,0,1,1,0,1, [['label'=>'1 cup','grams'=>250],['label'=>'1 glass','grams'=>200]]],
			['Whipped Butter',$de,717,3000,0.9,0.1,0.1,81.1,51.4,0.0,0.7, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 tablespoon','grams'=>9],['label'=>'1 pat','grams'=>5]]],
			['Half and Half',$de,130,544,3.0,4.3,4.3,11.5,7.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1, [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 cup','grams'=>242]]],
			['Sour Cream (light)',$de,138,577,3.5,4.3,4.3,11.5,7.2,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1, [['label'=>'1 tablespoon','grams'=>12],['label'=>'¼ cup','grams'=>58]]],
			['Whipped Cream (aerosol)',$de,257,1075,3.2,12.5,12.5,22.2,13.8,0.0,0.1, 0,0,1,0,0,0,0,0, 0,0,1,1,0,1, [['label'=>'2 tablespoons','grams'=>8],['label'=>'¼ cup','grams'=>15]]],
			['Clotted Cream (Cornish)',$de,586,2452,1.6,2.3,2.3,63.5,39.0,0.0,0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 pot','grams'=>56]]],
			['Crème Fraîche',$de,292,1222,2.3,2.8,2.8,30.0,20.0,0.0,0.0, 0,0,1,0,0,0,0,0, 1,0,1,1,0,1, [['label'=>'1 tablespoon','grams'=>15],['label'=>'1 pot','grams'=>200]]],
			['Duck Egg (whole, raw)',$de,185,774,13.0,1.5,1.0,14.0,3.7,0.0,0.2, 0,0,0,1,0,0,0,0, 1,1,1,0,0,1, [['label'=>'1 egg','grams'=>70]]],
			['Quail Egg (whole, raw)',$de,158,661,13.1,0.4,0.4,11.1,3.6,0.0,0.1, 0,0,0,1,0,0,0,0, 1,1,1,0,0,1, [['label'=>'1 egg','grams'=>9],['label'=>'5 eggs','grams'=>45]]],
			['Goose Egg (whole, raw)',$de,185,774,13.9,1.4,0.0,13.3,3.6,0.0,0.2, 0,0,0,1,0,0,0,0, 1,1,1,0,0,1, [['label'=>'1 egg','grams'=>144]]],
		];

		foreach ( $foods as $f ) {
			$slug = sanitize_title( $f[0] );
			if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcc_foods WHERE slug=%s", $slug ) ) ) { continue; }
			$wpdb->insert( $wpdb->prefix . 'fcc_foods', [
				'name'=>$f[0],'slug'=>$slug,'category_id'=>$f[1],'energy_kcal'=>$f[2],'energy_kj'=>$f[3],
				'protein_g'=>$f[4],'carbohydrate_g'=>$f[5],'of_which_sugars_g'=>$f[6],'fat_g'=>$f[7],
				'of_which_saturates_g'=>$f[8],'fibre_g'=>$f[9],'salt_g'=>$f[10],
				'allergen_fish'=>$f[11],'allergen_shellfish'=>$f[12],'allergen_dairy'=>$f[13],
				'allergen_eggs'=>$f[14],'allergen_nuts'=>$f[15],'allergen_gluten'=>$f[16],
				'allergen_soy'=>$f[17],'allergen_celery'=>$f[18],
				'diet_keto'=>$f[19],'diet_paleo'=>$f[20],'diet_halal'=>$f[21],
				'diet_kosher'=>$f[22],'diet_vegan'=>$f[23],'diet_vegetarian'=>$f[24],
				'serving_sizes'=> wp_json_encode( $f[25] ),
				'source_notes'=>'USDA FDC / M&W 8th ed. Seeded v75.',
			] ); // phpcs:ignore
		}
		update_option( 'fcc_seed_version', 75 );
	}
}
