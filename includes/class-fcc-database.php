<?php
/**
 * Database schema, table helpers, and CRUD for foods and categories.
 *
 * All queries use $wpdb->prepare() — no raw interpolation of user data.
 * The schema uses dbDelta-compatible SQL (precise spacing, PRIMARY KEY on its
 * own line, two spaces before KEY definitions).
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Database {

	const SCHEMA_VERSION = '1.1';

	// -------------------------------------------------------------------------
	// Table name helpers.
	// -------------------------------------------------------------------------

	public static function foods_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_foods';
	}

	public static function categories_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_categories';
	}

	public static function requests_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_food_requests';
	}

	public static function missed_searches_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_missed_searches';
	}

	public static function search_log_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_search_log';
	}

	// -------------------------------------------------------------------------
	// Schema creation / upgrade.
	// -------------------------------------------------------------------------

	/**
	 * Create or upgrade tables via dbDelta.
	 *
	 * omega3_* and caffeine_mg are nullable: NULL = no verified data exists.
	 * They must never be zero-filled; import/seed code enforces this.
	 * is_fruit_veg and portion_grams are forward-compat placeholders for a
	 * future NHS 5-a-day feature; no v1 logic uses them.
	 */
	public static function create_tables(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$foods           = self::foods_table();
		$cats            = self::categories_table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta requires raw string; no user input.
		$sql_cats = "CREATE TABLE {$cats} (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  slug varchar(100) NOT NULL,
  description text,
  display_order int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug)
) {$charset_collate};";

		$sql_foods = "CREATE TABLE {$foods} (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  name varchar(200) NOT NULL,
  slug varchar(200) NOT NULL,
  category_id mediumint(9) NOT NULL DEFAULT 0,
  serving_sizes longtext,
  energy_kcal decimal(8,2) NOT NULL DEFAULT 0,
  energy_kj decimal(8,2) NOT NULL DEFAULT 0,
  protein_g decimal(8,3) NOT NULL DEFAULT 0,
  carbohydrate_g decimal(8,3) NOT NULL DEFAULT 0,
  of_which_sugars_g decimal(8,3) DEFAULT NULL,
  fat_g decimal(8,3) NOT NULL DEFAULT 0,
  of_which_saturates_g decimal(8,3) DEFAULT NULL,
  fibre_g decimal(8,3) DEFAULT NULL,
  salt_g decimal(8,3) DEFAULT NULL,
  omega3_total_mg decimal(10,3) DEFAULT NULL,
  omega3_ala_mg decimal(10,3) DEFAULT NULL,
  omega3_epa_mg decimal(10,3) DEFAULT NULL,
  omega3_dha_mg decimal(10,3) DEFAULT NULL,
  caffeine_mg decimal(8,2) DEFAULT NULL,
  is_fruit_veg tinyint(1) DEFAULT NULL,
  portion_grams decimal(8,2) DEFAULT NULL,
  source_notes text,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug),
  KEY category_id (category_id),
  KEY name (name(50))
) {$charset_collate};";
		// phpcs:enable

		$requests = self::requests_table();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql_requests = "CREATE TABLE {$requests} (
  id int(11) NOT NULL AUTO_INCREMENT,
  food_name varchar(200) NOT NULL,
  note text,
  requester_email varchar(200),
  status varchar(20) NOT NULL DEFAULT 'pending',
  ip_address varchar(45),
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY status (status)
) {$charset_collate};";
		// phpcs:enable

		$missed   = self::missed_searches_table();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql_missed = "CREATE TABLE {$missed} (
  id int(11) NOT NULL AUTO_INCREMENT,
  query varchar(255) NOT NULL,
  search_count int(11) NOT NULL DEFAULT 1,
  last_searched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY fcc_ms_query (query(191)),
  KEY status (status)
) {$charset_collate};";
		// phpcs:enable

		$search_log = self::search_log_table();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql_search_log = "CREATE TABLE {$search_log} (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  query varchar(200) NOT NULL,
  has_results tinyint(1) NOT NULL DEFAULT 0,
  log_date date NOT NULL,
  count int(11) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY  (id),
  UNIQUE KEY fcc_sl_uniq (query(100), log_date, has_results),
  KEY log_date (log_date)
) {$charset_collate};";
		// phpcs:enable

		dbDelta( $sql_cats );
		dbDelta( $sql_foods );
		dbDelta( $sql_requests );
		dbDelta( $sql_missed );
		dbDelta( $sql_search_log );

		update_option( 'fcc_db_version', self::SCHEMA_VERSION );
	}

	/**
	 * Create the missed searches table — called by migration seed_v15.
	 */
	public static function create_missed_searches_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$missed          = self::missed_searches_table();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE {$missed} (
  id int(11) NOT NULL AUTO_INCREMENT,
  query varchar(255) NOT NULL,
  search_count int(11) NOT NULL DEFAULT 1,
  last_searched_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY fcc_ms_query (query(191)),
  KEY status (status)
) {$charset_collate};";
		// phpcs:enable
		dbDelta( $sql );
	}

	/**
	 * Create the food requests table — called by migration for existing installs.
	 */
	public static function create_requests_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$requests        = self::requests_table();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE {$requests} (
  id int(11) NOT NULL AUTO_INCREMENT,
  food_name varchar(200) NOT NULL,
  note text,
  requester_email varchar(200),
  status varchar(20) NOT NULL DEFAULT 'pending',
  ip_address varchar(45),
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY status (status)
) {$charset_collate};";
		// phpcs:enable
		dbDelta( $sql );
	}

	// -------------------------------------------------------------------------
	// Food CRUD.
	// -------------------------------------------------------------------------

	/**
	 * Fetch a single food by ID.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function get_food( int $id ): ?array {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ? self::decode_food( $row ) : null;
	}

	/**
	 * Fetch a single food by slug.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function get_food_by_slug( string $slug ): ?array {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", $slug ), ARRAY_A );
		return $row ? self::decode_food( $row ) : null;
	}

	/**
	 * Search foods by name fragment, optionally filtered by category.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function search_foods( string $query, int $category_id = 0, int $limit = 20 ): array {
		global $wpdb;
		$table = self::foods_table();
		$like  = '%' . $wpdb->esc_like( $query ) . '%';

		if ( $category_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE name LIKE %s AND category_id = %d ORDER BY name ASC LIMIT %d",
					$like,
					$category_id,
					$limit
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE name LIKE %s ORDER BY name ASC LIMIT %d",
					$like,
					$limit
				),
				ARRAY_A
			);
		}

		return array_map( [ self::class, 'decode_food' ], $rows ?? [] );
	}

	/**
	 * Get foods ordered by search_count DESC (most popular first).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_popular_foods( int $limit = 8 ): array {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE search_count > 0 ORDER BY search_count DESC LIMIT %d", $limit ),
			ARRAY_A
		);
		return array_map( [ self::class, 'decode_food' ], $rows ?? [] );
	}

	/**
	 * Increment search_count for a food by 1.
	 */
	public static function increment_food_hit( int $id ): void {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET search_count = search_count + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Get all foods, paginated, with optional sorting and category filter.
	 *
	 * @return array{total:int,rows:array<int,array<string,mixed>>}
	 */
	public static function get_foods( array $args = [] ): array {
		global $wpdb;
		$table = self::foods_table();

		$defaults = [
			'per_page'    => 20,
			'page'        => 1,
			'orderby'     => 'name',
			'order'       => 'ASC',
			'category_id' => 0,
			'search'      => '',
		];
		$args = wp_parse_args( $args, $defaults );

		$allowed_orderby = [
			'id', 'name', 'category_id', 'category_name',
			'energy_kcal', 'protein_g', 'carbohydrate_g', 'fat_g',
			'omega3_total_mg', 'caffeine_mg', 'created_at',
		];
		$order    = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$use_join = 'category_name' === $args['orderby'];

		if ( ! in_array( $args['orderby'], $allowed_orderby, true ) ) {
			$args['orderby'] = 'name';
			$use_join        = false;
		}

		$col_prefix = $use_join ? 'f.' : '';
		$orderby    = $use_join ? "COALESCE(c.name,'')" : $args['orderby'];

		$where_parts = [];
		$where_vals  = [];

		if ( $args['category_id'] > 0 ) {
			$where_parts[] = "{$col_prefix}category_id = %d";
			$where_vals[]  = absint( $args['category_id'] );
		}
		if ( '' !== $args['search'] ) {
			$where_parts[] = "{$col_prefix}name LIKE %s";
			$where_vals[]  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';
		$offset    = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		if ( $use_join ) {
			$cat_table = self::categories_table();
			$join_sql  = "LEFT JOIN {$cat_table} c ON f.category_id = c.id";
			$count_sql = "SELECT COUNT(*) FROM {$table} f {$join_sql} {$where_sql}";
			$data_sql  = "SELECT f.* FROM {$table} f {$join_sql} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		} else {
			$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
			$data_sql  = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		}

		$total = (int) ( $where_vals
			? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$where_vals ) )
			: $wpdb->get_var( $count_sql )
		);

		$all_vals = array_merge( $where_vals, [ absint( $args['per_page'] ), $offset ] );
		$rows     = $wpdb->get_results( $wpdb->prepare( $data_sql, ...$all_vals ), ARRAY_A );
		// phpcs:enable

		return [
			'total' => $total,
			'rows'  => array_map( [ self::class, 'decode_food' ], $rows ?? [] ),
		];
	}

	/**
	 * Get all foods (no pagination) — used for export.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_all_foods(): array {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY name ASC", ARRAY_A );
		return array_map( [ self::class, 'decode_food' ], $rows ?? [] );
	}

	/**
	 * Insert a new food row.
	 *
	 * @param array<string,mixed> $data
	 * @return int|false New row ID or false on failure.
	 */
	public static function insert_food( array $data ): int|false {
		global $wpdb;

		$row    = self::prepare_food_row( $data );
		$result = $wpdb->insert( self::foods_table(), $row, self::food_formats( $row ) );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update an existing food row.
	 *
	 * @param int                 $id   Food ID.
	 * @param array<string,mixed> $data Field values to update.
	 */
	public static function update_food( int $id, array $data ): bool {
		global $wpdb;
		$row    = self::prepare_food_row( $data );
		$result = $wpdb->update( self::foods_table(), $row, [ 'id' => $id ], self::food_formats( $row ), [ '%d' ] );
		return false !== $result;
	}

	/**
	 * Delete a food row by ID.
	 */
	public static function delete_food( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::foods_table(), [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Bulk-delete food rows.
	 *
	 * @param int[] $ids
	 */
	public static function bulk_delete_foods( array $ids ): int {
		global $wpdb;
		$table       = self::foods_table();
		$ids         = array_map( 'absint', $ids );
		$ids         = array_filter( $ids );
		if ( ! $ids ) {
			return 0;
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );
	}

	/**
	 * Check if a food slug already exists (optionally excluding a specific ID).
	 */
	public static function slug_exists( string $slug, int $exclude_id = 0 ): bool {
		global $wpdb;
		$table = self::foods_table();
		if ( $exclude_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s AND id != %d LIMIT 1", $slug, $exclude_id ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s LIMIT 1", $slug ) );
	}

	// -------------------------------------------------------------------------
	// Category CRUD.
	// -------------------------------------------------------------------------

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_all_categories(): array {
		global $wpdb;
		$table = self::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY display_order ASC, name ASC", ARRAY_A ) ?? [];
	}

	/**
	 * @return array<string,mixed>|null
	 */
	public static function get_category( int $id ): ?array {
		global $wpdb;
		$table = self::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ) ?: null;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return int|false
	 */
	public static function insert_category( array $data ): int|false {
		global $wpdb;
		$row = [
			'name'          => sanitize_text_field( $data['name'] ?? '' ),
			'slug'          => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'description'   => sanitize_textarea_field( $data['description'] ?? '' ),
			'display_order' => absint( $data['display_order'] ?? 0 ),
		];
		$result = $wpdb->insert( self::categories_table(), $row, [ '%s', '%s', '%s', '%d' ] );
		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * @param array<string,mixed> $data
	 */
	public static function update_category( int $id, array $data ): bool {
		global $wpdb;
		$row = [
			'name'          => sanitize_text_field( $data['name'] ?? '' ),
			'slug'          => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'description'   => sanitize_textarea_field( $data['description'] ?? '' ),
			'display_order' => absint( $data['display_order'] ?? 0 ),
		];
		return false !== $wpdb->update( self::categories_table(), $row, [ 'id' => $id ], [ '%s', '%s', '%s', '%d' ], [ '%d' ] );
	}

	public static function delete_category( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::categories_table(), [ 'id' => $id ], [ '%d' ] );
	}

	// -------------------------------------------------------------------------
	// Seeder helpers.
	// -------------------------------------------------------------------------

	/**
	 * Returns true if the foods table already has data (prevents re-seeding).
	 */
	public static function is_seeded(): bool {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) > 0;
	}

	// -------------------------------------------------------------------------
	// Internal helpers.
	// -------------------------------------------------------------------------

	/**
	 * Sanitise and prepare a food data array for DB insertion/update.
	 *
	 * Nullable fields remain NULL when the input value is null/empty string.
	 * Numeric fields are cast to float; booleans to int.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	private static function prepare_food_row( array $data ): array {
		$nullable_floats = [
			'of_which_sugars_g', 'of_which_saturates_g', 'fibre_g', 'salt_g',
			'omega3_total_mg', 'omega3_ala_mg', 'omega3_epa_mg', 'omega3_dha_mg',
			'caffeine_mg', 'portion_grams',
		];

		$row = [
			'name'          => sanitize_text_field( $data['name'] ?? '' ),
			'slug'          => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'category_id'   => absint( $data['category_id'] ?? 0 ),
			'serving_sizes' => isset( $data['serving_sizes'] ) ? wp_json_encode( $data['serving_sizes'] ) : null,
			'energy_kcal'   => (float) ( $data['energy_kcal'] ?? 0 ),
			'energy_kj'     => (float) ( $data['energy_kj']   ?? 0 ),
			'protein_g'     => (float) ( $data['protein_g']   ?? 0 ),
			'carbohydrate_g'=> (float) ( $data['carbohydrate_g'] ?? 0 ),
			'fat_g'         => (float) ( $data['fat_g']       ?? 0 ),
			'is_fruit_veg'  => isset( $data['is_fruit_veg'] ) ? (int) (bool) $data['is_fruit_veg'] : null,
			'source_notes'  => isset( $data['source_notes'] ) ? sanitize_textarea_field( $data['source_notes'] ) : null,
		];

		foreach ( $nullable_floats as $col ) {
			if ( isset( $data[ $col ] ) && '' !== $data[ $col ] && null !== $data[ $col ] ) {
				$row[ $col ] = (float) $data[ $col ];
			} else {
				$row[ $col ] = null;
			}
		}

		return $row;
	}

	/**
	 * Return printf-style format strings for each key in a food row.
	 *
	 * @param array<string,mixed> $row
	 * @return array<int,string>
	 */
	private static function food_formats( array $row ): array {
		$int_cols    = [ 'category_id', 'is_fruit_veg' ];
		$string_cols = [ 'name', 'slug', 'serving_sizes', 'source_notes' ];

		$formats = [];
		foreach ( array_keys( $row ) as $col ) {
			if ( in_array( $col, $int_cols, true ) ) {
				$formats[] = '%d';
			} elseif ( in_array( $col, $string_cols, true ) ) {
				$formats[] = '%s';
			} else {
				$formats[] = '%f'; // All numeric nutrient columns.
			}
		}
		return $formats;
	}

	/**
	 * Decode a raw food DB row: parse the serving_sizes JSON, cast numerics.
	 *
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	public static function decode_food( array $row ): array {
		if ( isset( $row['serving_sizes'] ) && is_string( $row['serving_sizes'] ) ) {
			$decoded = json_decode( $row['serving_sizes'], true );
			$row['serving_sizes'] = is_array( $decoded ) ? $decoded : [];
		} else {
			$row['serving_sizes'] = [];
		}

		// Cast nullable float columns — leave null if null.
		$nullable_floats = [
			'of_which_sugars_g', 'of_which_saturates_g', 'fibre_g', 'salt_g',
			'omega3_total_mg', 'omega3_ala_mg', 'omega3_epa_mg', 'omega3_dha_mg',
			'caffeine_mg', 'portion_grams',
		];
		foreach ( $nullable_floats as $col ) {
			$row[ $col ] = isset( $row[ $col ] ) && null !== $row[ $col ] ? (float) $row[ $col ] : null;
		}

		$float_cols = [ 'energy_kcal', 'energy_kj', 'protein_g', 'carbohydrate_g', 'fat_g' ];
		foreach ( $float_cols as $col ) {
			if ( isset( $row[ $col ] ) ) {
				$row[ $col ] = (float) $row[ $col ];
			}
		}

		$row['id']          = (int) $row['id'];
		$row['category_id'] = (int) $row['category_id'];

		return $row;
	}

	// -------------------------------------------------------------------------
	// Food Requests CRUD.
	// -------------------------------------------------------------------------

	public static function insert_food_request( array $data ): int|false {
		global $wpdb;
		$result = $wpdb->insert(
			self::requests_table(),
			[
				'food_name'        => sanitize_text_field( $data['food_name'] ?? '' ),
				'note'             => sanitize_textarea_field( $data['note'] ?? '' ),
				'requester_email'  => sanitize_email( $data['email'] ?? '' ),
				'marketing_optin'  => isset( $data['marketing_optin'] ) ? (int) (bool) $data['marketing_optin'] : 1,
				'status'           => 'pending',
				'ip_address'       => sanitize_text_field( $data['ip'] ?? '' ),
			],
			[ '%s', '%s', '%s', '%d', '%s', '%s' ]
		);
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get food requests with optional filtering, search and pagination.
	 *
	 * @param array{status?:string,search?:string,per_page?:int,page?:int} $args
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_food_requests( array $args = [] ): array {
		global $wpdb;
		$table = self::requests_table();

		$args = wp_parse_args( $args, [
			'status'   => '',
			'search'   => '',
			'per_page' => 20,
			'page'     => 1,
		] );

		[ $where, $vals ] = self::reqs_where( $args );
		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];

		$vals[] = (int) $args['per_page'];
		$vals[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $vals ),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Count food requests with the same filtering as get_food_requests().
	 *
	 * @param array{status?:string,search?:string} $args
	 */
	public static function count_food_requests( array $args = [] ): int {
		global $wpdb;
		$table = self::requests_table();
		[ $where, $vals ] = self::reqs_where( $args );
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where}", $vals ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/** @return array{0:string,1:array<int,mixed>} */
	private static function reqs_where( array $args ): array {
		$parts = [];
		$vals  = [];
		if ( ! empty( $args['status'] ) ) {
			$parts[] = 'status = %s';
			$vals[]  = $args['status'];
		}
		if ( ! empty( $args['search'] ) ) {
			global $wpdb;
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$parts[] = '(food_name LIKE %s OR requester_email LIKE %s)';
			$vals[]  = $like;
			$vals[]  = $like;
		}
		$where = $parts ? ' WHERE ' . implode( ' AND ', $parts ) : '';
		return [ $where, $vals ];
	}

	/**
	 * Fetch all matching requests for file export (no pagination).
	 *
	 * @param array{optin_only?:bool,days?:int,status?:string} $args
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_requests_for_export( array $args = [] ): array {
		global $wpdb;
		$table = self::requests_table();

		$defaults = [
			'optin_only' => true,
			'days'       => 0,
			'status'     => '',
			'date_from'  => '',
			'date_to'    => '',
		];
		$args = wp_parse_args( $args, $defaults );

		$parts = [];
		$vals  = [];

		if ( $args['optin_only'] ) {
			$parts[] = "marketing_optin = 1 AND requester_email != ''";
		}
		if ( ! empty( $args['date_from'] ) ) {
			$parts[] = 'created_at >= %s';
			$vals[]  = $args['date_from'] . ' 00:00:00';
		} elseif ( (int) $args['days'] > 0 ) {
			$parts[] = 'created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$vals[]  = (int) $args['days'];
		}
		if ( ! empty( $args['date_to'] ) ) {
			$parts[] = 'created_at <= %s';
			$vals[]  = $args['date_to'] . ' 23:59:59';
		}
		if ( ! empty( $args['status'] ) ) {
			$parts[] = 'status = %s';
			$vals[]  = $args['status'];
		}

		$where = $parts ? ' WHERE ' . implode( ' AND ', $parts ) : '';

		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare( "SELECT id, food_name, note, requester_email, marketing_optin, status, created_at FROM {$table}{$where} ORDER BY created_at DESC", $vals );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = "SELECT id, food_name, note, requester_email, marketing_optin, status, created_at FROM {$table} ORDER BY created_at DESC";
		}

		return $wpdb->get_results( $sql, ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function count_opted_in_requests(): int {
		global $wpdb;
		$table = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE marketing_optin = 1 AND requester_email != ''" );
	}

	public static function update_request_status( int $id, string $status ): void {
		global $wpdb;
		$wpdb->update( self::requests_table(), [ 'status' => $status ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
	}

	public static function delete_food_request( int $id ): void {
		global $wpdb;
		$wpdb->delete( self::requests_table(), [ 'id' => $id ], [ '%d' ] );
	}

	public static function count_pending_requests(): int {
		global $wpdb;
		$table = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'pending' ) );
	}

	// -------------------------------------------------------------------------
	// Food Requests — Grouped view (deduplicated by food_name).
	// -------------------------------------------------------------------------

	/**
	 * Get food requests grouped by food_name, with sort, period, and status filter.
	 *
	 * @param array{status?:string,sort?:string,days?:int,date_from?:string,date_to?:string,per_page?:int,page?:int} $args
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_food_requests_grouped( array $args = [] ): array {
		global $wpdb;
		$table = self::requests_table();

		$args = wp_parse_args( $args, [
			'status'    => '',
			'sort'      => 'most_requested',
			'days'      => 0,
			'date_from' => '',
			'date_to'   => '',
			'per_page'  => 20,
			'page'      => 1,
		] );

		[ $where, $having, $vals ] = self::grouped_reqs_clauses( $args );
		$having_sql = $having ? ' HAVING ' . $having : '';

		$order = match ( $args['sort'] ) {
			'latest' => 'last_requested DESC',
			'oldest' => 'first_requested ASC',
			default  => 'request_count DESC, last_requested DESC',
		};

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$sql = "SELECT
			food_name,
			COUNT(*) AS request_count,
			MAX(created_at) AS last_requested,
			MIN(created_at) AS first_requested,
			MAX(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) AS has_pending,
			MAX(CASE WHEN status = 'done'      THEN 1 ELSE 0 END) AS has_done,
			(SELECT note FROM {$table} r2
			 WHERE LOWER(r2.food_name) = LOWER(r.food_name)
			   AND r2.note != ''
			 ORDER BY r2.created_at DESC LIMIT 1) AS latest_note
		FROM {$table} r{$where}
		GROUP BY LOWER(food_name){$having_sql}
		ORDER BY {$order}
		LIMIT %d OFFSET %d";
		// phpcs:enable

		$qvals   = array_merge( $vals, [ (int) $args['per_page'], $offset ] );
		$rows    = $qvals
			? $wpdb->get_results( $wpdb->prepare( $sql, $qvals ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( $rows as &$row ) {
			$row['group_status'] = $row['has_pending'] ? 'pending'
				: ( $row['has_done'] ? 'done' : 'dismissed' );
		}

		return $rows ?: [];
	}

	/**
	 * Count distinct food_name groups (same filters as get_food_requests_grouped).
	 *
	 * @param array{status?:string,days?:int,date_from?:string,date_to?:string} $args
	 */
	public static function count_food_requests_grouped( array $args = [] ): int {
		global $wpdb;
		$table = self::requests_table();

		[ $where, $having, $vals ] = self::grouped_reqs_clauses( $args );
		$having_sql = $having ? ' HAVING ' . $having : '';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$inner = "SELECT 1 FROM {$table} r{$where} GROUP BY LOWER(food_name){$having_sql}";
		$sql   = "SELECT COUNT(*) FROM ({$inner}) AS grp";
		// phpcs:enable

		return $vals
			? (int) $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array{0:string,1:string,2:array<int,mixed>} [where_sql, having_sql, vals] */
	private static function grouped_reqs_clauses( array $args ): array {
		$where_parts  = [];
		$having_parts = [];
		$vals         = [];

		// Period filter (WHERE on created_at).
		$days      = (int) ( $args['days'] ?? 0 );
		$date_from = $args['date_from'] ?? '';
		$date_to   = $args['date_to'] ?? '';

		if ( $date_from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$where_parts[] = 'created_at >= %s';
			$vals[]        = $date_from . ' 00:00:00';
		} elseif ( $days > 0 ) {
			$where_parts[] = 'created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$vals[]        = $days;
		}
		if ( $date_to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$where_parts[] = 'created_at <= %s';
			$vals[]        = $date_to . ' 23:59:59';
		}

		// Status filter (HAVING on aggregated status).
		$status = $args['status'] ?? '';
		if ( 'pending' === $status ) {
			$having_parts[] = "MAX(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) = 1";
		} elseif ( 'done' === $status ) {
			$having_parts[] = "MAX(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) = 0";
			$having_parts[] = "MAX(CASE WHEN status = 'done' THEN 1 ELSE 0 END) = 1";
		} elseif ( 'dismissed' === $status ) {
			$having_parts[] = "MAX(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) = 0";
			$having_parts[] = "MAX(CASE WHEN status = 'done' THEN 1 ELSE 0 END) = 0";
		}

		$where  = $where_parts ? ' WHERE ' . implode( ' AND ', $where_parts ) : '';
		$having = $having_parts ? implode( ' AND ', $having_parts ) : '';

		return [ $where, $having, $vals ];
	}

	/** Update all requests with a given food_name to a new status. */
	public static function update_requests_status_by_food( string $food_name, string $status ): void {
		global $wpdb;
		$table = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s WHERE LOWER(food_name) = LOWER(%s)",
				$status,
				$food_name
			)
		);
	}

	// -------------------------------------------------------------------------
	// Missed Searches CRUD.
	// -------------------------------------------------------------------------

	/**
	 * Log a missed search query — insert new row or increment count.
	 * Normalises to lowercase. Silently ignores queries shorter than 2 chars.
	 */
	public static function log_missed_search( string $query ): void {
		global $wpdb;
		$query = mb_strtolower( trim( $query ), 'UTF-8' );
		if ( strlen( $query ) < 2 ) {
			return;
		}
		$table = self::missed_searches_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (query, search_count, last_searched_at)
				 VALUES (%s, 1, NOW())
				 ON DUPLICATE KEY UPDATE search_count = search_count + 1, last_searched_at = NOW()",
				$query
			)
		);
	}

	/**
	 * Get missed searches with optional filtering and pagination.
	 *
	 * @param array{status?:string,sort?:string,days?:int,date_from?:string,date_to?:string,per_page?:int,page?:int} $args
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_missed_searches( array $args = [] ): array {
		global $wpdb;
		$table = self::missed_searches_table();

		$args = wp_parse_args( $args, [
			'status'    => '',
			'sort'      => 'most_searched',
			'days'      => 0,
			'date_from' => '',
			'date_to'   => '',
			'per_page'  => 20,
			'page'      => 1,
		] );

		[ $where, $vals ] = self::ms_where( $args );
		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];

		$order = match ( $args['sort'] ) {
			'latest' => 'last_searched_at DESC',
			'oldest' => 'created_at ASC',
			default  => 'search_count DESC, last_searched_at DESC',
		};

		$vals[] = (int) $args['per_page'];
		$vals[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table}{$where} ORDER BY {$order} LIMIT %d OFFSET %d", $vals ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		) ?: [];
	}

	/**
	 * Count missed searches with the same filtering as get_missed_searches().
	 *
	 * @param array{status?:string,days?:int,date_from?:string,date_to?:string} $args
	 */
	public static function count_missed_searches( array $args = [] ): int {
		global $wpdb;
		$table = self::missed_searches_table();
		[ $where, $vals ] = self::ms_where( $args );
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where}", $vals ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	public static function count_active_missed_searches(): int {
		global $wpdb;
		$table = self::missed_searches_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'active' ) );
	}

	public static function count_high_priority_missed_searches( int $threshold = 5 ): int {
		global $wpdb;
		$table = self::missed_searches_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = 'active' AND search_count >= %d", $threshold ) );
	}

	public static function update_missed_search_status( int $id, string $status ): void {
		global $wpdb;
		$wpdb->update( self::missed_searches_table(), [ 'status' => $status ], [ 'id' => $id ], [ '%s' ], [ '%d' ] );
	}

	public static function delete_missed_search( int $id ): void {
		global $wpdb;
		$wpdb->delete( self::missed_searches_table(), [ 'id' => $id ], [ '%d' ] );
	}

	// -------------------------------------------------------------------------
	// Search Log — time-series analytics table.
	// -------------------------------------------------------------------------

	public static function create_search_log_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::search_log_table();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE {$table} (
  id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  query varchar(200) NOT NULL,
  has_results tinyint(1) NOT NULL DEFAULT 0,
  log_date date NOT NULL,
  count int(11) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY  (id),
  UNIQUE KEY fcc_sl_uniq (query(100), log_date, has_results),
  KEY log_date (log_date)
) {$charset_collate};";
		// phpcs:enable
		dbDelta( $sql );
	}

	/** Log any search query (successful or not) — upserts a daily aggregate row. */
	public static function log_search( string $query, bool $has_results ): void {
		global $wpdb;
		$query = mb_strtolower( trim( $query ), 'UTF-8' );
		if ( strlen( $query ) < 2 ) {
			return;
		}
		$table = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (query, has_results, log_date, count)
				 VALUES (%s, %d, CURDATE(), 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				$query,
				(int) $has_results
			)
		);
	}

	/** Total search count for a period (0 = all time). */
	public static function count_total_searches( int $days = 0 ): int {
		global $wpdb;
		$table = self::search_log_table();
		if ( $days > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT SUM(count) FROM {$table} WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)", $days )
			);
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT SUM(count) FROM {$table}" );
	}

	/** Search success rate 0–100 for a period (0 = all time). */
	public static function get_search_success_rate( int $days = 0 ): float {
		global $wpdb;
		$table = self::search_log_table();
		$where = $days > 0
			? $wpdb->prepare( 'WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)', $days )
			: '';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		$row = $wpdb->get_row(
			"SELECT SUM(count) AS total, SUM(CASE WHEN has_results=1 THEN count ELSE 0 END) AS hits FROM {$table} {$where}",
			ARRAY_A
		);
		if ( ! $row || (int) $row['total'] === 0 ) {
			return 0.0;
		}
		return round( ( (int) $row['hits'] / (int) $row['total'] ) * 100, 1 );
	}

	/**
	 * Daily search volume for charts.
	 *
	 * @return array<int,array{log_date:string,count:int}>
	 */
	public static function get_search_volume_by_day( int $days = 30 ): array {
		global $wpdb;
		$table = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT log_date, SUM(count) AS count FROM {$table}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				 GROUP BY log_date ORDER BY log_date ASC",
				$days
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Top N foods by hit count.
	 *
	 * @return array<int,array{name:string,search_count:int}>
	 */
	public static function get_top_foods_by_hits( int $limit = 10 ): array {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT name, search_count FROM {$table} WHERE search_count > 0 ORDER BY search_count DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Top N active missed searches (content gaps).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_top_content_gaps( int $limit = 15 ): array {
		global $wpdb;
		$table = self::missed_searches_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, query, search_count, last_searched_at, status FROM {$table}
				 WHERE status = 'active' ORDER BY search_count DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Top N requested foods (grouped by food_name).
	 *
	 * @return array<int,array{food_name:string,request_count:int,last_requested:string}>
	 */
	public static function get_top_requested_foods( int $limit = 10 ): array {
		global $wpdb;
		$table = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT food_name, COUNT(*) AS request_count, MAX(created_at) AS last_requested
				 FROM {$table} GROUP BY LOWER(food_name)
				 ORDER BY request_count DESC, last_requested DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Recent marketing opt-ins.
	 *
	 * @return array<int,array{requester_email:string,food_name:string,created_at:string}>
	 */
	public static function get_recent_optins( int $limit = 5 ): array {
		global $wpdb;
		$table = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT requester_email, food_name, created_at FROM {$table}
				 WHERE marketing_optin = 1 AND requester_email != ''
				 ORDER BY created_at DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Paginated opted-in subscribers for the Email Hub.
	 *
	 * @param array{food_name?:string,per_page?:int,page?:int} $args
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_email_subscribers( array $args = [] ): array {
		global $wpdb;
		$table = self::requests_table();
		$args  = wp_parse_args( $args, [ 'food_name' => '', 'per_page' => 20, 'page' => 1 ] );

		$parts = [ "marketing_optin = 1 AND requester_email != ''" ];
		$vals  = [];
		if ( ! empty( $args['food_name'] ) ) {
			$parts[] = 'LOWER(food_name) = LOWER(%s)';
			$vals[]  = $args['food_name'];
		}
		$where  = ' WHERE ' . implode( ' AND ', $parts );
		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$vals[] = (int) $args['per_page'];
		$vals[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, food_name, note, requester_email, status, created_at FROM {$table}{$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$vals
			),
			ARRAY_A
		) ?: [];
	}

	/** Count opted-in subscribers (with optional food_name filter). */
	public static function count_email_subscribers( array $args = [] ): int {
		global $wpdb;
		$table  = self::requests_table();
		$parts  = [ "marketing_optin = 1 AND requester_email != ''" ];
		$vals   = [];
		if ( ! empty( $args['food_name'] ) ) {
			$parts[] = 'LOWER(food_name) = LOWER(%s)';
			$vals[]  = $args['food_name'];
		}
		$where = ' WHERE ' . implode( ' AND ', $parts );
		if ( $vals ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table}{$where}", $vals ) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}{$where}" );
	}

	/**
	 * Content planner: combine missed searches + food requests into a priority list.
	 * priority_score = (missed_search_count × 2) + request_count
	 *
	 * @return array<int,array{item:string,missed_count:int,request_count:int,priority_score:int}>
	 */
	public static function get_content_planner_items( int $limit = 50 ): array {
		global $wpdb;
		$ms_table  = self::missed_searches_table();
		$req_table = self::requests_table();

		// MySQL doesn't support FULL OUTER JOIN — use LEFT JOIN UNION RIGHT JOIN.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "
			SELECT
				COALESCE(ms.query, fr.food_name) AS item,
				COALESCE(ms.search_count, 0)     AS missed_count,
				COALESCE(fr.req_count, 0)        AS request_count,
				(COALESCE(ms.search_count, 0) * 2 + COALESCE(fr.req_count, 0)) AS priority_score,
				ms.id AS ms_id
			FROM (
				SELECT query, search_count, id FROM {$ms_table} WHERE status = 'active'
			) ms
			LEFT JOIN (
				SELECT LOWER(food_name) AS food_name, COUNT(*) AS req_count FROM {$req_table} GROUP BY LOWER(food_name)
			) fr ON LOWER(ms.query) = fr.food_name

			UNION

			SELECT
				COALESCE(ms.query, fr.food_name) AS item,
				COALESCE(ms.search_count, 0)     AS missed_count,
				COALESCE(fr.req_count, 0)        AS request_count,
				(COALESCE(ms.search_count, 0) * 2 + COALESCE(fr.req_count, 0)) AS priority_score,
				ms.id AS ms_id
			FROM (
				SELECT LOWER(food_name) AS food_name, COUNT(*) AS req_count FROM {$req_table} GROUP BY LOWER(food_name)
			) fr
			LEFT JOIN (
				SELECT query, search_count, id FROM {$ms_table} WHERE status = 'active'
			) ms ON LOWER(ms.query) = fr.food_name
			WHERE ms.query IS NULL

			ORDER BY priority_score DESC, item ASC
			LIMIT %d";
		// phpcs:enable

		return $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array{0:string,1:array<int,mixed>} */
	private static function ms_where( array $args ): array {
		$parts = [];
		$vals  = [];

		if ( ! empty( $args['status'] ) ) {
			$parts[] = 'status = %s';
			$vals[]  = $args['status'];
		}

		$days      = (int) ( $args['days'] ?? 0 );
		$date_from = $args['date_from'] ?? '';
		$date_to   = $args['date_to'] ?? '';

		if ( $date_from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$parts[] = 'created_at >= %s';
			$vals[]  = $date_from . ' 00:00:00';
		} elseif ( $days > 0 ) {
			$parts[] = 'created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)';
			$vals[]  = $days;
		}
		if ( $date_to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$parts[] = 'created_at <= %s';
			$vals[]  = $date_to . ' 23:59:59';
		}

		$where = $parts ? ' WHERE ' . implode( ' AND ', $parts ) : '';
		return [ $where, $vals ];
	}
}
