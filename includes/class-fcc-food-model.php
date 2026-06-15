<?php
/**
 * CRUD model for the fcc_foods table.
 *
 * Null discipline for Omega-3 and caffeine fields:
 *   - DB NULL  → no verified data exists; JS must hide the row.
 *   - DB 0.0   → verified zero (rare — only when published source says zero).
 *   - All reads return null (PHP) for DB NULL; all writes preserve this.
 *
 * @package FCC
 */

namespace FCC;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Food_Model {

	// ---------------------------------------------------------------------------
	// Nutrient columns in DB order — used by import/export.
	// ---------------------------------------------------------------------------

	/** Core non-nullable nutrient columns. */
	const CORE_FIELDS = [
		'energy_kcal', 'energy_kj', 'protein_g', 'carbohydrate_g',
		'fat_g',
	];

	/** Nullable core nutrients. */
	const NULLABLE_CORE = [
		'of_which_sugars_g', 'of_which_saturates_g', 'fibre_g', 'salt_g',
	];

	/** Optional nullable fields — Omega-3 and caffeine. */
	const OPTIONAL_FIELDS = [
		'omega3_total_mg', 'omega3_ala_mg', 'omega3_epa_mg', 'omega3_dha_mg',
		'caffeine_mg',
	];

	/** Forward-compat fields for v2 (schema only). */
	const COMPAT_FIELDS = [ 'is_fruit_veg', 'portion_grams' ];

	// ---------------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------------

	/**
	 * Get a single food by ID.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Database::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ) );
	}

	/**
	 * Paginated, searchable, filterable list of foods for admin.
	 *
	 * @param array{
	 *   search?:string,
	 *   category_id?:int,
	 *   orderby?:string,
	 *   order?:string,
	 *   per_page?:int,
	 *   offset?:int,
	 * } $args
	 * @return array{items:object[],total:int}
	 */
	public static function get_list( array $args = [] ): array {
		global $wpdb;
		$table = Database::foods_table();

		$search      = sanitize_text_field( $args['search']      ?? '' );
		$category_id = absint( $args['category_id']  ?? 0 );
		$per_page    = max( 1, absint( $args['per_page']    ?? 20 ) );
		$offset      = max( 0, absint( $args['offset']      ?? 0 ) );

		$allowed_cols  = [ 'id', 'name', 'category_id', 'energy_kcal', 'protein_g', 'fat_g', 'created_at' ];
		$allowed_order = [ 'ASC', 'DESC' ];
		$orderby       = in_array( $args['orderby'] ?? '', $allowed_cols, true ) ? $args['orderby'] : 'name';
		$order         = in_array( strtoupper( $args['order'] ?? '' ), $allowed_order, true ) ? strtoupper( $args['order'] ) : 'ASC';

		$where  = [];
		$params = [];

		if ( $search !== '' ) {
			$where[]  = 'name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		if ( $category_id > 0 ) {
			$where[]  = 'category_id = %d';
			$params[] = $category_id;
		}

		$where_clause = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_sql = "SELECT COUNT(*) FROM `{$table}` {$where_clause}";
		$total     = (int) ( $params
			? $wpdb->get_var( $wpdb->prepare( $total_sql, ...$params ) )
			: $wpdb->get_var( $total_sql ) );

		$items_sql = "SELECT * FROM `{$table}` {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$items     = $wpdb->get_results(
			$params
				? $wpdb->prepare( $items_sql, ...$params, $per_page, $offset )
				: $wpdb->prepare( $items_sql, $per_page, $offset )
		) ?: [];
		// phpcs:enable

		return compact( 'items', 'total' );
	}

	/**
	 * Search foods for the REST autocomplete endpoint.
	 * Returns a lightweight array (id, name, category_id, energy_kcal) for fast autocomplete,
	 * plus all nutrient fields for a full detail lookup.
	 *
	 * @return object[]
	 */
	public static function search( string $term, int $category_id = 0, int $limit = 20 ): array {
		global $wpdb;
		$table  = Database::foods_table();
		$params = [];

		$where = 'WHERE name LIKE %s';
		$params[] = '%' . $wpdb->esc_like( $term ) . '%';

		if ( $category_id > 0 ) {
			$where   .= ' AND category_id = %d';
			$params[] = $category_id;
		}

		$params[] = $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM `{$table}` {$where} ORDER BY name ASC LIMIT %d", ...$params )
		) ?: [];
	}

	/**
	 * Fetch all foods (used for export — no pagination).
	 *
	 * @return object[]
	 */
	public static function get_all_for_export(): array {
		global $wpdb;
		$table = Database::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY id ASC" ) ?: [];
	}

	// ---------------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------------

	/**
	 * Insert a new food row.
	 *
	 * @param array $data  Associative array of field → value.
	 * @return int|false   New ID or false on error.
	 */
	public static function create( array $data ): int|false {
		global $wpdb;
		$table   = Database::foods_table();
		$row     = self::sanitize_row( $data );
		$formats = self::format_array( $row );

		$result = $wpdb->insert( $table, $row, $formats );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing food row.
	 *
	 * @return int|false  Rows updated or false.
	 */
	public static function update( int $id, array $data ): int|false {
		global $wpdb;
		$table   = Database::foods_table();
		$row     = self::sanitize_row( $data );
		$formats = self::format_array( $row );

		return $wpdb->update( $table, $row, [ 'id' => $id ], $formats, [ '%d' ] );
	}

	/**
	 * Delete a single food by ID.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;
		$table = Database::foods_table();
		return (bool) $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Bulk-delete an array of IDs.
	 *
	 * @param int[] $ids
	 */
	public static function bulk_delete( array $ids ): int {
		global $wpdb;
		if ( empty( $ids ) ) {
			return 0;
		}
		$table       = Database::foods_table();
		$ids         = array_map( 'absint', $ids );
		$ids         = array_filter( $ids );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query(
			$wpdb->prepare( "DELETE FROM `{$table}` WHERE id IN ({$placeholders})", ...$ids )
		);
	}

	/**
	 * Count total foods (optionally filtered by category).
	 */
	public static function count( int $category_id = 0 ): int {
		global $wpdb;
		$table = Database::foods_table();
		if ( $category_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE category_id = %d", $category_id ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" );
	}

	/**
	 * Check if a slug already exists (used by seeder / import).
	 */
	public static function slug_exists( string $slug ): bool {
		global $wpdb;
		$table = Database::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `{$table}` WHERE slug = %s", $slug ) );
	}

	// ---------------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------------

	/**
	 * Sanitise and type-cast an incoming data array.
	 * Null discipline: empty string or absence of optional keys → PHP null → DB NULL.
	 */
	public static function sanitize_row( array $data ): array {
		$row = [
			'name'                 => sanitize_text_field( $data['name']                ?? '' ),
			'slug'                 => sanitize_title( $data['slug']   ?: ( $data['name'] ?? '' ) ),
			'category_id'          => absint( $data['category_id']    ?? 0 ),
			'serving_sizes'        => isset( $data['serving_sizes'] ) && is_array( $data['serving_sizes'] )
									  ? wp_json_encode( $data['serving_sizes'] )
									  : ( is_string( $data['serving_sizes'] ?? null ) && $data['serving_sizes'] !== ''
										? sanitize_text_field( $data['serving_sizes'] )
										: null ),
			'energy_kcal'          => floatval( $data['energy_kcal']  ?? 0 ),
			'energy_kj'            => floatval( $data['energy_kj']    ?? 0 ),
			'protein_g'            => floatval( $data['protein_g']    ?? 0 ),
			'carbohydrate_g'       => floatval( $data['carbohydrate_g'] ?? 0 ),
			'of_which_sugars_g'    => self::nullable_float( $data['of_which_sugars_g']    ?? null ),
			'fat_g'                => floatval( $data['fat_g']         ?? 0 ),
			'of_which_saturates_g' => self::nullable_float( $data['of_which_saturates_g'] ?? null ),
			'fibre_g'              => self::nullable_float( $data['fibre_g']               ?? null ),
			'salt_g'               => self::nullable_float( $data['salt_g']                ?? null ),
			// Omega-3: strict nullable — null unless a real value is explicitly given.
			'omega3_total_mg'      => self::nullable_float( $data['omega3_total_mg']  ?? null ),
			'omega3_ala_mg'        => self::nullable_float( $data['omega3_ala_mg']    ?? null ),
			'omega3_epa_mg'        => self::nullable_float( $data['omega3_epa_mg']    ?? null ),
			'omega3_dha_mg'        => self::nullable_float( $data['omega3_dha_mg']    ?? null ),
			// Caffeine: same discipline.
			'caffeine_mg'          => self::nullable_float( $data['caffeine_mg']      ?? null ),
			// Forward-compat.
			'is_fruit_veg'         => isset( $data['is_fruit_veg'] ) ? (int) (bool) $data['is_fruit_veg'] : 0,
			'portion_grams'        => self::nullable_float( $data['portion_grams']    ?? null ),
			'source_notes'         => sanitize_textarea_field( $data['source_notes']  ?? '' ),
		];

		// Remove null serving_sizes so DB stores NULL properly.
		if ( $row['serving_sizes'] === null ) {
			unset( $row['serving_sizes'] );
		}

		return $row;
	}

	/**
	 * Convert a value to float or null.
	 * Empty string, 'null', null → null.
	 * Any numeric string or number → floatval.
	 */
	public static function nullable_float( mixed $value ): ?float {
		if ( $value === null || $value === '' || strtolower( (string) $value ) === 'null' ) {
			return null;
		}
		return (float) $value;
	}

	/**
	 * Build the $wpdb->insert format array matching the row keys.
	 *
	 * @param array $row
	 * @return string[]
	 */
	private static function format_array( array $row ): array {
		$float_cols = array_merge( self::CORE_FIELDS, self::NULLABLE_CORE, self::OPTIONAL_FIELDS, [ 'portion_grams' ] );
		$int_cols   = [ 'category_id', 'is_fruit_veg' ];

		$formats = [];
		foreach ( $row as $key => $val ) {
			if ( $val === null ) {
				$formats[] = '%s'; // wpdb will treat null %s as SQL NULL.
			} elseif ( in_array( $key, $int_cols, true ) ) {
				$formats[] = '%d';
			} elseif ( in_array( $key, $float_cols, true ) ) {
				$formats[] = '%f';
			} else {
				$formats[] = '%s';
			}
		}
		return $formats;
	}

	/**
	 * Decode serving_sizes JSON back to array (safe: handles invalid JSON).
	 *
	 * @param object $food  A DB row.
	 * @return array<array{label:string,grams:float}>
	 */
	public static function serving_sizes( object $food ): array {
		if ( empty( $food->serving_sizes ) ) {
			return [];
		}
		$decoded = json_decode( $food->serving_sizes, true );
		return is_array( $decoded ) ? $decoded : [];
	}
}
