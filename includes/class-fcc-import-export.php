<?php
/**
 * CSV and Excel (.xlsx) import/export for the food database.
 *
 * Import: validates column headers, sanitises values, preserves NULL vs 0 for
 * nullable fields (omega3_*, caffeine_mg), reports per-row errors without
 * stopping the whole import.
 *
 * Export: streams the full food database (joined with category names) as either
 * CSV or XLSX.  NULL values are exported as empty strings so they round-trip
 * correctly back through import.
 *
 * Vendor libraries:
 *   vendor/SimpleXLSX.php    — read .xlsx (Sergey Shuchkin, MIT licence)
 *   vendor/SimpleXLSXGen.php — write .xlsx (Sergey Shuchkin, MIT licence)
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Import_Export {

	// -------------------------------------------------------------------------
	// Column definitions.
	// -------------------------------------------------------------------------

	/**
	 * Ordered list of import/export columns.
	 * required = must not be empty.
	 * nullable = NULL when blank, never 0-filled.
	 *
	 * @return array<string,array{label:string,required:bool,nullable:bool,type:string}>
	 */
	public static function columns(): array {
		return [
			'name'                 => [ 'label' => 'Name',                       'required' => true,  'nullable' => false, 'type' => 'string' ],
			'category'             => [ 'label' => 'Category',                   'required' => false, 'nullable' => true,  'type' => 'string' ],
			'energy_kcal'          => [ 'label' => 'Energy (kcal)',              'required' => true,  'nullable' => false, 'type' => 'float'  ],
			'energy_kj'            => [ 'label' => 'Energy (kJ)',                'required' => true,  'nullable' => false, 'type' => 'float'  ],
			'protein_g'            => [ 'label' => 'Protein (g)',                'required' => true,  'nullable' => false, 'type' => 'float'  ],
			'carbohydrate_g'       => [ 'label' => 'Carbohydrate (g)',           'required' => true,  'nullable' => false, 'type' => 'float'  ],
			'of_which_sugars_g'    => [ 'label' => 'of which Sugars (g)',        'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'fat_g'                => [ 'label' => 'Fat (g)',                    'required' => true,  'nullable' => false, 'type' => 'float'  ],
			'of_which_saturates_g' => [ 'label' => 'of which Saturates (g)',     'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'fibre_g'              => [ 'label' => 'Fibre (g)',                  'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'salt_g'               => [ 'label' => 'Salt (g)',                   'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'omega3_total_mg'      => [ 'label' => 'Omega-3 Total (mg)',         'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'omega3_ala_mg'        => [ 'label' => 'Omega-3 ALA (mg)',           'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'omega3_epa_mg'        => [ 'label' => 'Omega-3 EPA (mg)',           'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'omega3_dha_mg'        => [ 'label' => 'Omega-3 DHA (mg)',           'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'caffeine_mg'          => [ 'label' => 'Caffeine (mg)',              'required' => false, 'nullable' => true,  'type' => 'float'  ],
			'serving_sizes'        => [ 'label' => 'Serving Sizes (JSON)',       'required' => false, 'nullable' => true,  'type' => 'json'   ],
			'source_notes'         => [ 'label' => 'Source / Notes',            'required' => false, 'nullable' => true,  'type' => 'string' ],
		];
	}

	// -------------------------------------------------------------------------
	// Export.
	// -------------------------------------------------------------------------

	/**
	 * Stream a CSV export to the browser.
	 * Caller must have already done capability + nonce checks.
	 */
	public static function export_csv(): void {
		$rows    = self::get_export_rows();
		$headers = self::column_headers();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="food-calorie-calculator-export-' . gmdate( 'Y-m-d' ) . '.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'wb' );
		if ( false === $out ) {
			wp_die( esc_html__( 'Could not open output stream.', 'food-calorie-calculator' ) );
		}

		// BOM so Excel opens UTF-8 without mangling special chars.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv( $out, $headers );
		foreach ( $rows as $row ) {
			fputcsv( $out, $row );
		}

		fclose( $out );
		exit;
	}

	/**
	 * Stream an XLSX export to the browser.
	 * Caller must have already done capability + nonce checks.
	 */
	public static function export_xlsx(): void {
		require_once FCC_PLUGIN_DIR . 'vendor/SimpleXLSXGen.php';

		$rows    = self::get_export_rows();
		$headers = self::column_headers();

		$data = array_merge( [ $headers ], $rows );

		$xlsx = \Shuchkin\SimpleXLSXGen::fromArray( $data );
		$xlsx->downloadAs( 'food-calorie-calculator-export-' . gmdate( 'Y-m-d' ) . '.xlsx' );
		exit;
	}

	// -------------------------------------------------------------------------
	// Import.
	// -------------------------------------------------------------------------

	/**
	 * Import a CSV file.
	 *
	 * @param string $file_path Absolute server path to the uploaded file.
	 * @return array{imported:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_csv( string $file_path ): array {
		$handle = fopen( $file_path, 'rb' );
		if ( false === $handle ) {
			return [ 'imported' => 0, 'skipped' => 0, 'errors' => [ __( 'Could not open uploaded file.', 'food-calorie-calculator' ) ] ];
		}

		// Strip BOM if present.
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$raw_headers = fgetcsv( $handle );
		if ( ! $raw_headers ) {
			fclose( $handle );
			return [ 'imported' => 0, 'skipped' => 0, 'errors' => [ __( 'File is empty or header row is missing.', 'food-calorie-calculator' ) ] ];
		}

		$col_map = self::map_headers( $raw_headers );
		$errors  = $col_map['errors'];

		$imported = 0;
		$skipped  = 0;
		$row_num  = 1;

		// Cache category name → id for performance.
		$cat_cache = self::build_category_cache();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;
			if ( self::row_is_empty( $row ) ) {
				continue;
			}

			$result = self::process_row( $row, $col_map['map'], $cat_cache, $row_num );
			if ( is_string( $result ) ) {
				$errors[] = $result;
				$skipped++;
			} else {
				$imported++;
			}
		}

		fclose( $handle );
		return [ 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors ];
	}

	/**
	 * Import an XLSX file.
	 *
	 * @param string $file_path Absolute server path to the uploaded file.
	 * @return array{imported:int,skipped:int,errors:array<int,string>}
	 */
	public static function import_xlsx( string $file_path ): array {
		require_once FCC_PLUGIN_DIR . 'vendor/SimpleXLSX.php';

		$xlsx = \Shuchkin\SimpleXLSX::parse( $file_path );
		if ( ! $xlsx ) {
			return [
				'imported' => 0,
				'skipped'  => 0,
				// translators: %s = library error message.
				'errors'   => [ sprintf( __( 'Could not parse XLSX file: %s', 'food-calorie-calculator' ), \Shuchkin\SimpleXLSX::parseError() ) ],
			];
		}

		$rows    = $xlsx->rows();
		$errors  = [];
		$imported = 0;
		$skipped  = 0;

		if ( empty( $rows ) ) {
			return [ 'imported' => 0, 'skipped' => 0, 'errors' => [ __( 'File is empty.', 'food-calorie-calculator' ) ] ];
		}

		$raw_headers = array_shift( $rows );
		$col_map     = self::map_headers( $raw_headers );
		$errors      = $col_map['errors'];

		$cat_cache = self::build_category_cache();
		$row_num   = 1;

		foreach ( $rows as $row ) {
			$row_num++;
			if ( self::row_is_empty( $row ) ) {
				continue;
			}

			$result = self::process_row( $row, $col_map['map'], $cat_cache, $row_num );
			if ( is_string( $result ) ) {
				$errors[] = $result;
				$skipped++;
			} else {
				$imported++;
			}
		}

		return [ 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors ];
	}

	// -------------------------------------------------------------------------
	// Internal helpers.
	// -------------------------------------------------------------------------

	/**
	 * Get ordered rows for export.
	 * NULL values are exported as empty string ''.
	 *
	 * @return array<int,array<int,string>>
	 */
	private static function get_export_rows(): array {
		$foods = Database::get_all_foods();
		$cats  = [];
		foreach ( Database::get_all_categories() as $cat ) {
			$cats[ (int) $cat['id'] ] = $cat['name'];
		}

		$cols = array_keys( self::columns() );
		$out  = [];

		foreach ( $foods as $food ) {
			$row = [];
			foreach ( $cols as $col ) {
				if ( 'category' === $col ) {
					$row[] = $cats[ $food['category_id'] ] ?? '';
				} elseif ( 'serving_sizes' === $col ) {
					$row[] = empty( $food['serving_sizes'] ) ? '' : wp_json_encode( $food['serving_sizes'] );
				} else {
					// NULL → '' so it round-trips correctly.
					$row[] = null === $food[ $col ] ? '' : (string) $food[ $col ];
				}
			}
			$out[] = $row;
		}

		return $out;
	}

	/** @return array<int,string> */
	private static function column_headers(): array {
		return array_column( self::columns(), 'label' );
	}

	/**
	 * Map raw header strings to column keys.
	 *
	 * @param array<int,string> $raw
	 * @return array{map:array<string,int>,errors:array<int,string>}
	 */
	private static function map_headers( array $raw ): array {
		$cols    = self::columns();
		$label_to_key = [];
		foreach ( $cols as $key => $def ) {
			$label_to_key[ strtolower( trim( $def['label'] ) ) ] = $key;
		}

		$map    = [];
		$errors = [];

		foreach ( $raw as $i => $cell ) {
			$normalised = strtolower( trim( (string) $cell ) );
			if ( isset( $label_to_key[ $normalised ] ) ) {
				$map[ $label_to_key[ $normalised ] ] = $i;
			}
		}

		// Check required columns.
		foreach ( $cols as $key => $def ) {
			if ( $def['required'] && ! isset( $map[ $key ] ) ) {
				// translators: %s = column label.
				$errors[] = sprintf( __( 'Required column "%s" not found in header row.', 'food-calorie-calculator' ), $def['label'] );
			}
		}

		return [ 'map' => $map, 'errors' => $errors ];
	}

	/**
	 * Process a single data row.
	 *
	 * @param array<int,string>  $row
	 * @param array<string,int>  $map       Column key → array index.
	 * @param array<string,int>  $cat_cache Category name (lower) → id.
	 * @param int                $row_num   For error messages.
	 * @return true|string  true on success, error string on failure.
	 */
	private static function process_row( array $row, array $map, array $cat_cache, int $row_num ): true|string {
		$cols = self::columns();

		// Extract values.
		$values = [];
		foreach ( $map as $key => $idx ) {
			$values[ $key ] = isset( $row[ $idx ] ) ? trim( (string) $row[ $idx ] ) : '';
		}

		// Required field check.
		foreach ( $cols as $key => $def ) {
			if ( $def['required'] && isset( $values[ $key ] ) && '' === $values[ $key ] ) {
				return sprintf(
					// translators: 1: row number, 2: column label.
					__( 'Row %1$d: required field "%2$s" is empty — row skipped.', 'food-calorie-calculator' ),
					$row_num,
					$def['label']
				);
			}
		}

		// Build data array.
		$data = [];
		foreach ( $values as $key => $raw_val ) {
			$def = $cols[ $key ];

			if ( 'category' === $key ) {
				$data['category_id'] = self::resolve_category( $raw_val, $cat_cache );
				continue;
			}

			if ( 'float' === $def['type'] ) {
				if ( $def['nullable'] && '' === $raw_val ) {
					$data[ $key ] = null;
				} else {
					$parsed = filter_var( $raw_val, FILTER_VALIDATE_FLOAT );
					if ( false === $parsed ) {
						return sprintf(
							// translators: 1: row number, 2: column label.
							__( 'Row %1$d: column "%2$s" must be a number — row skipped.', 'food-calorie-calculator' ),
							$row_num,
							$def['label']
						);
					}
					$data[ $key ] = $parsed;
				}
				continue;
			}

			if ( 'json' === $def['type'] ) {
				if ( '' === $raw_val || null === $raw_val ) {
					$data[ $key ] = null;
				} else {
					$decoded = json_decode( $raw_val, true );
					// For serving_sizes, validate each entry has the expected shape.
					if ( is_array( $decoded ) && 'serving_sizes' === $key ) {
						foreach ( $decoded as $entry ) {
							if ( ! is_array( $entry ) || ! isset( $entry['label'], $entry['grams'] ) || ! is_string( $entry['label'] ) || ! is_numeric( $entry['grams'] ) ) {
								$decoded = null;
								break;
							}
						}
					}
					$data[ $key ] = is_array( $decoded ) ? $decoded : null;
				}
				continue;
			}

			// String.
			if ( $def['nullable'] && '' === $raw_val ) {
				$data[ $key ] = null;
			} else {
				$data[ $key ] = sanitize_text_field( $raw_val );
			}
		}

		if ( empty( $data['name'] ) ) {
			return sprintf(
				// translators: %d = row number.
				__( 'Row %d: Name is empty — row skipped.', 'food-calorie-calculator' ),
				$row_num
			);
		}

		// Upsert: update if slug already exists, insert otherwise.
		$slug = sanitize_title( $data['name'] );
		$existing = Database::get_food_by_slug( $slug );

		if ( $existing ) {
			Database::update_food( (int) $existing['id'], $data );
		} else {
			$data['slug'] = $slug;
			$result = Database::insert_food( $data );
			if ( false === $result ) {
				return sprintf(
					// translators: %d = row number.
					__( 'Row %d: Database insert failed.', 'food-calorie-calculator' ),
					$row_num
				);
			}
		}

		return true;
	}

	/**
	 * Resolve a category name string to a DB id.
	 * Creates the category if it doesn't exist.
	 *
	 * @param string            $name
	 * @param array<string,int> &$cache
	 */
	private static function resolve_category( string $name, array &$cache ): int {
		if ( '' === trim( $name ) ) {
			return 0;
		}
		$key = strtolower( trim( $name ) );
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		// Create on-the-fly.
		$id = Database::insert_category( [ 'name' => $name ] );
		if ( $id ) {
			$cache[ $key ] = $id;
			return $id;
		}
		return 0;
	}

	/**
	 * Build a lowercase-name → id map for all existing categories.
	 *
	 * @return array<string,int>
	 */
	private static function build_category_cache(): array {
		$cache = [];
		foreach ( Database::get_all_categories() as $cat ) {
			$cache[ strtolower( trim( $cat['name'] ) ) ] = (int) $cat['id'];
		}
		return $cache;
	}

	/**
	 * Return true when every cell in a row is empty string / null.
	 *
	 * @param array<int,string> $row
	 */
	private static function row_is_empty( array $row ): bool {
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}
}
