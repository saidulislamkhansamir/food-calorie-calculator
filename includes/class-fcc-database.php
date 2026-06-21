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

	const SCHEMA_VERSION = '1.3';

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

	public static function sponsor_clicks_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_sponsor_clicks';
	}

	public static function wl_licenses_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'fcc_wl_licenses';
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
  iron_mg decimal(8,3) DEFAULT NULL,
  calcium_mg decimal(10,3) DEFAULT NULL,
  vitamin_c_mg decimal(8,2) DEFAULT NULL,
  is_fruit_veg tinyint(1) DEFAULT NULL,
  portion_grams decimal(8,2) DEFAULT NULL,
  source_notes text,
  allergen_fish tinyint(1) DEFAULT NULL,
  allergen_shellfish tinyint(1) DEFAULT NULL,
  allergen_dairy tinyint(1) DEFAULT NULL,
  allergen_eggs tinyint(1) DEFAULT NULL,
  allergen_nuts tinyint(1) DEFAULT NULL,
  allergen_gluten tinyint(1) DEFAULT NULL,
  allergen_soy tinyint(1) DEFAULT NULL,
  allergen_celery tinyint(1) DEFAULT NULL,
  diet_keto tinyint(1) DEFAULT NULL,
  diet_paleo tinyint(1) DEFAULT NULL,
  diet_halal tinyint(1) DEFAULT NULL,
  diet_kosher tinyint(1) DEFAULT NULL,
  diet_vegan tinyint(1) DEFAULT NULL,
  diet_vegetarian tinyint(1) DEFAULT NULL,
  is_sponsored tinyint(1) NOT NULL DEFAULT 0,
  sponsor_active tinyint(1) NOT NULL DEFAULT 0,
  sponsor_name varchar(200) DEFAULT NULL,
  sponsor_logo_id bigint(20) DEFAULT NULL,
  sponsor_url varchar(500) DEFAULT NULL,
  sponsor_expires_at datetime DEFAULT NULL,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug),
  KEY category_id (category_id),
  KEY name (name(50)),
  KEY is_sponsored (is_sponsored)
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

		$sponsor_clicks = self::sponsor_clicks_table();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql_sponsor_clicks = "CREATE TABLE {$sponsor_clicks} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  food_id mediumint(9) NOT NULL,
  clicked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY food_id (food_id),
  KEY clicked_at (clicked_at)
) {$charset_collate};";
		// phpcs:enable

		$wl_licenses = self::wl_licenses_table();
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql_wl_licenses = "CREATE TABLE {$wl_licenses} (
  id              int(11) NOT NULL AUTO_INCREMENT,
  license_key     varchar(64) NOT NULL,
  client_name     varchar(200) NOT NULL,
  client_email    varchar(200) NOT NULL,
  client_url      varchar(500) DEFAULT NULL,
  business_type   varchar(50) NOT NULL DEFAULT 'other',
  tier            varchar(20) NOT NULL DEFAULT 'starter',
  price_gbp       decimal(8,2) NOT NULL DEFAULT 99.00,
  status          varchar(20) NOT NULL DEFAULT 'trial',
  allowed_domains text DEFAULT NULL,
  brand_name      varchar(200) DEFAULT NULL,
  primary_colour  varchar(7) DEFAULT NULL,
  accent_colour   varchar(7) DEFAULT NULL,
  logo_url        varchar(500) DEFAULT NULL,
  hide_powered_by tinyint(1) NOT NULL DEFAULT 0,
  custom_css      text DEFAULT NULL,
  notes           text DEFAULT NULL,
  embed_loads     int(11) NOT NULL DEFAULT 0,
  search_count    int(11) NOT NULL DEFAULT 0,
  expires_at      datetime DEFAULT NULL,
  renewed_at      datetime DEFAULT NULL,
  created_at      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY license_key (license_key),
  KEY status (status),
  KEY expires_at (expires_at)
) {$charset_collate};";
		// phpcs:enable

		dbDelta( $sql_cats );
		dbDelta( $sql_foods );
		dbDelta( $sql_requests );
		dbDelta( $sql_missed );
		dbDelta( $sql_search_log );
		dbDelta( $sql_sponsor_clicks );
		dbDelta( $sql_wl_licenses );

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
	public static function search_foods( string $query, int $category_id = 0, int $limit = 20, array $pinned_rules = [] ): array {
		global $wpdb;
		$table = self::foods_table();
		$like  = '%' . $wpdb->esc_like( $query ) . '%';

		// Sponsored + active + not-expired foods float to top; then by search_count DESC.
		$sponsor_order = "CASE WHEN is_sponsored=1 AND sponsor_active=1 AND (sponsor_expires_at IS NULL OR sponsor_expires_at > NOW()) THEN 0 ELSE 1 END ASC, search_count DESC";

		if ( $category_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE name LIKE %s AND category_id = %d ORDER BY {$sponsor_order} LIMIT %d",
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
					"SELECT * FROM {$table} WHERE name LIKE %s ORDER BY {$sponsor_order} LIMIT %d",
					$like,
					$limit
				),
				ARRAY_A
			);
		}

		$foods = array_map( [ self::class, 'decode_food' ], $rows ?? [] );

		if ( ! empty( $pinned_rules ) && $query !== '' ) {
			$foods = self::apply_pinned_rules( $foods, $query, $pinned_rules, $limit );
		}

		return $foods;
	}

	private static function apply_pinned_rules( array $foods, string $query, array $rules, int $limit ): array {
		$matched = [];
		foreach ( $rules as $rule ) {
			if ( empty( $rule['keyword'] ) || empty( $rule['food_id'] ) ) continue;
			if ( mb_stripos( $query, $rule['keyword'] ) === false ) continue;
			$matched[] = $rule;
		}

		if ( empty( $matched ) ) return $foods;

		usort( $matched, fn( $a, $b ) => ( $a['position'] ?? 1 ) - ( $b['position'] ?? 1 ) );

		$used_positions = [];
		foreach ( $matched as $rule ) {
			$food = self::get_food( (int) $rule['food_id'] );
			if ( ! $food ) continue;

			$foods = array_values( array_filter( $foods, fn( $f ) => (int) $f['id'] !== (int) $rule['food_id'] ) );

			$pos = ( (int) ( $rule['position'] ?? 1 ) ) - 1;
			while ( isset( $used_positions[ $pos ] ) ) { $pos++; }
			$pos = min( $pos, count( $foods ) );

			array_splice( $foods, $pos, 0, [ $food ] );
			$used_positions[ $pos ] = true;
		}

		return array_slice( $foods, 0, $limit );
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
			'status'      => '',
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
		if ( 'incomplete' === $args['status'] ) {
			$where_parts[] = "({$col_prefix}energy_kcal IS NULL OR {$col_prefix}protein_g IS NULL OR {$col_prefix}carbohydrate_g IS NULL OR {$col_prefix}fat_g IS NULL OR {$col_prefix}fibre_g IS NULL OR {$col_prefix}salt_g IS NULL)";
		} elseif ( 'complete' === $args['status'] ) {
			$where_parts[] = "({$col_prefix}energy_kcal IS NOT NULL AND {$col_prefix}protein_g IS NOT NULL AND {$col_prefix}carbohydrate_g IS NOT NULL AND {$col_prefix}fat_g IS NOT NULL AND {$col_prefix}fibre_g IS NOT NULL AND {$col_prefix}salt_g IS NOT NULL)";
		} elseif ( 'sponsored' === $args['status'] ) {
			$where_parts[] = "{$col_prefix}is_sponsored = 1";
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

	public static function bulk_update_category( array $ids, int $category_id ): int {
		global $wpdb;
		$table = self::foods_table();
		$ids   = array_filter( array_map( 'absint', $ids ) );
		if ( ! $ids ) { return 0; }
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET category_id = %d WHERE id IN ({$placeholders})",
			$category_id, ...$ids
		) );
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

	/**
	 * Per-category stats: food count, search total, complete/incomplete counts.
	 *
	 * @return array<int,array{food_count:int,total_searches:int,complete:int,incomplete:int}>
	 */
	public static function get_category_stats(): array {
		global $wpdb;
		$ft = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT category_id,
			    COUNT(*) AS food_count,
			    COALESCE(SUM(search_count), 0) AS total_searches,
			    SUM(CASE WHEN energy_kcal IS NOT NULL AND protein_g IS NOT NULL AND carbohydrate_g IS NOT NULL
			         AND fat_g IS NOT NULL AND fibre_g IS NOT NULL AND salt_g IS NOT NULL THEN 1 ELSE 0 END) AS complete
			 FROM {$ft} GROUP BY category_id",
			ARRAY_A
		) ?: [];

		$out = [];
		foreach ( $rows as $r ) {
			$c = (int) $r['complete'];
			$t = (int) $r['food_count'];
			$out[ (int) $r['category_id'] ] = [
				'food_count'      => $t,
				'total_searches'  => (int) $r['total_searches'],
				'complete'        => $c,
				'incomplete'      => $t - $c,
			];
		}
		return $out;
	}

	/**
	 * Most-searched food per category.
	 *
	 * @return array<int,array{name:string,search_count:int}>
	 */
	public static function get_top_food_per_category(): array {
		global $wpdb;
		$ft = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT f.category_id, f.name, f.search_count
			 FROM {$ft} f
			 INNER JOIN (
			     SELECT category_id, MAX(search_count) AS max_sc
			     FROM {$ft} WHERE search_count > 0
			     GROUP BY category_id
			 ) m ON f.category_id = m.category_id AND f.search_count = m.max_sc
			 GROUP BY f.category_id",
			ARRAY_A
		) ?: [];

		$out = [];
		foreach ( $rows as $r ) {
			$out[ (int) $r['category_id'] ] = [
				'name'         => $r['name'],
				'search_count' => (int) $r['search_count'],
			];
		}
		return $out;
	}

	/**
	 * Merge source category into target: move all foods, delete source.
	 *
	 * @return int Foods moved count.
	 */
	public static function merge_categories( int $source_id, int $target_id ): int {
		global $wpdb;
		$ft = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$moved = (int) $wpdb->query( $wpdb->prepare(
			"UPDATE {$ft} SET category_id = %d WHERE category_id = %d",
			$target_id, $source_id
		) );
		self::delete_category( $source_id );
		return $moved;
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
			'caffeine_mg', 'iron_mg', 'calcium_mg', 'vitamin_c_mg', 'portion_grams',
		];

		$row = [
			'name'               => sanitize_text_field( $data['name'] ?? '' ),
			'slug'               => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'category_id'        => absint( $data['category_id'] ?? 0 ),
			'serving_sizes'      => isset( $data['serving_sizes'] ) ? wp_json_encode( $data['serving_sizes'] ) : null,
			'energy_kcal'        => (float) ( $data['energy_kcal'] ?? 0 ),
			'energy_kj'          => (float) ( $data['energy_kj']   ?? 0 ),
			'protein_g'          => (float) ( $data['protein_g']   ?? 0 ),
			'carbohydrate_g'     => (float) ( $data['carbohydrate_g'] ?? 0 ),
			'fat_g'              => (float) ( $data['fat_g']       ?? 0 ),
			'is_fruit_veg'       => isset( $data['is_fruit_veg'] ) ? (int) (bool) $data['is_fruit_veg'] : null,
			'source_notes'       => isset( $data['source_notes'] ) ? sanitize_textarea_field( $data['source_notes'] ) : null,
			'is_sponsored'       => (int) (bool) ( $data['is_sponsored'] ?? 0 ),
			'sponsor_active'     => (int) (bool) ( $data['sponsor_active'] ?? 0 ),
			'sponsor_name'       => isset( $data['sponsor_name'] ) && '' !== $data['sponsor_name'] ? sanitize_text_field( $data['sponsor_name'] ) : null,
			'sponsor_logo_id'    => isset( $data['sponsor_logo_id'] ) && $data['sponsor_logo_id'] > 0 ? (int) $data['sponsor_logo_id'] : null,
			'sponsor_url'        => isset( $data['sponsor_url'] ) && '' !== $data['sponsor_url'] ? esc_url_raw( $data['sponsor_url'] ) : null,
			'sponsor_expires_at' => isset( $data['sponsor_expires_at'] ) && '' !== $data['sponsor_expires_at'] ? sanitize_text_field( $data['sponsor_expires_at'] ) : null,
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
		$int_cols    = [ 'category_id', 'is_fruit_veg', 'is_sponsored', 'sponsor_active', 'sponsor_logo_id',
			'allergen_fish', 'allergen_shellfish', 'allergen_dairy', 'allergen_eggs', 'allergen_nuts', 'allergen_gluten', 'allergen_soy', 'allergen_celery',
			'diet_keto', 'diet_paleo', 'diet_halal', 'diet_kosher', 'diet_vegan', 'diet_vegetarian' ];
		$string_cols = [ 'name', 'slug', 'serving_sizes', 'source_notes', 'sponsor_name', 'sponsor_url', 'sponsor_expires_at' ];

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
			'caffeine_mg', 'iron_mg', 'calcium_mg', 'vitamin_c_mg', 'portion_grams',
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

		$row['id']            = (int) $row['id'];
		$row['category_id']   = (int) $row['category_id'];
		$row['is_sponsored']  = (bool) ( $row['is_sponsored'] ?? 0 );
		$row['sponsor_active'] = (bool) ( $row['sponsor_active'] ?? 0 );
		$row['sponsor_logo_id'] = isset( $row['sponsor_logo_id'] ) && null !== $row['sponsor_logo_id'] ? (int) $row['sponsor_logo_id'] : null;

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

	public static function delete_requests_by_status( string $status ): int {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM " . self::requests_table() . " WHERE status = %s", $status ) ); // phpcs:ignore
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
			 ORDER BY r2.created_at DESC LIMIT 1) AS latest_note,
			(SELECT requester_email FROM {$table} r3
			 WHERE LOWER(r3.food_name) = LOWER(r.food_name)
			   AND r3.requester_email != ''
			 ORDER BY r3.created_at DESC LIMIT 1) AS latest_email
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

	public static function delete_missed_searches_by_status( string $status ): int {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM " . self::missed_searches_table() . " WHERE status = %s", $status ) ); // phpcs:ignore
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

	public static function create_sponsor_clicks_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::sponsor_clicks_table();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE {$table} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  food_id mediumint(9) NOT NULL,
  clicked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY food_id (food_id),
  KEY clicked_at (clicked_at)
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
		$args  = wp_parse_args( $args, [
			'food_name' => '', 'search' => '', 'status' => '',
			'per_page' => 20, 'page' => 1, 'orderby' => 'created_at', 'order' => 'DESC',
		] );

		$parts = [ "marketing_optin = 1 AND requester_email != ''" ];
		$vals  = [];
		if ( ! empty( $args['food_name'] ) ) {
			$parts[] = 'LOWER(food_name) = LOWER(%s)';
			$vals[]  = $args['food_name'];
		}
		if ( ! empty( $args['search'] ) ) {
			$parts[] = '(requester_email LIKE %s OR food_name LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$vals[]  = $like;
			$vals[]  = $like;
		}
		if ( ! empty( $args['status'] ) ) {
			$parts[] = 'status = %s';
			$vals[]  = $args['status'];
		}

		$allowed_order = [ 'created_at', 'requester_email', 'food_name', 'status' ];
		$orderby = in_array( $args['orderby'], $allowed_order, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$where  = ' WHERE ' . implode( ' AND ', $parts );
		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * (int) $args['per_page'];
		$vals[] = (int) $args['per_page'];
		$vals[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, food_name, note, requester_email, status, created_at FROM {$table}{$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$vals
			),
			ARRAY_A
		) ?: [];
	}

	/** Count opted-in subscribers (with filters). */
	public static function count_email_subscribers( array $args = [] ): int {
		global $wpdb;
		$table  = self::requests_table();
		$parts  = [ "marketing_optin = 1 AND requester_email != ''" ];
		$vals   = [];
		if ( ! empty( $args['food_name'] ) ) {
			$parts[] = 'LOWER(food_name) = LOWER(%s)';
			$vals[]  = $args['food_name'];
		}
		if ( ! empty( $args['search'] ) ) {
			$parts[] = '(requester_email LIKE %s OR food_name LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$vals[]  = $like;
			$vals[]  = $like;
		}
		if ( ! empty( $args['status'] ) ) {
			$parts[] = 'status = %s';
			$vals[]  = $args['status'];
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

	// -------------------------------------------------------------------------
	// Sponsored Foods.
	// -------------------------------------------------------------------------

	/**
	 * Record a sponsored-food click for engagement analytics.
	 */
	public static function record_sponsor_click( int $food_id ): void {
		global $wpdb;
		$wpdb->insert( self::sponsor_clicks_table(), [ 'food_id' => $food_id ], [ '%d' ] );
	}

	/**
	 * Count sponsor clicks for a food in the last N days (0 = all time).
	 */
	public static function get_sponsor_click_count( int $food_id, int $days = 30 ): int {
		global $wpdb;
		$table = self::sponsor_clicks_table();
		if ( $days > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE food_id = %d AND clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$food_id,
				$days
			) );
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE food_id = %d", $food_id ) );
	}

	/**
	 * Get all sponsored foods (is_sponsored=1) with 30-day click counts.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_sponsored_foods(): array {
		global $wpdb;
		$ft = self::foods_table();
		$ct = self::sponsor_clicks_table();
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT f.id, f.name, f.slug, f.is_sponsored, f.sponsor_active, f.sponsor_name,
			        f.sponsor_logo_id, f.sponsor_url, f.sponsor_expires_at,
			        COUNT(c.id) AS clicks_30d
			   FROM {$ft} f
			   LEFT JOIN {$ct} c ON c.food_id = f.id AND c.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			  WHERE f.is_sponsored = 1
			  GROUP BY f.id
			  ORDER BY f.sponsor_active DESC, f.sponsor_name ASC",
			ARRAY_A
		) ?? [];

		foreach ( $rows as &$r ) {
			$r['is_sponsored']   = (bool) $r['is_sponsored'];
			$r['sponsor_active'] = (bool) $r['sponsor_active'];
			$r['clicks_30d']     = (int) $r['clicks_30d'];
			$r['sponsor_logo_id'] = $r['sponsor_logo_id'] ? (int) $r['sponsor_logo_id'] : null;
			// Compute status: expired / paused / active.
			if ( $r['sponsor_expires_at'] && strtotime( $r['sponsor_expires_at'] ) < strtotime( $now ) ) {
				$r['sponsor_status'] = 'expired';
			} elseif ( ! $r['sponsor_active'] ) {
				$r['sponsor_status'] = 'paused';
			} else {
				$r['sponsor_status'] = 'active';
			}
		}
		unset( $r );

		return $rows;
	}

	/**
	 * Toggle sponsor_active for a food.
	 */
	public static function toggle_sponsor_active( int $id, bool $active ): void {
		global $wpdb;
		$wpdb->update( self::foods_table(), [ 'sponsor_active' => $active ? 1 : 0 ], [ 'id' => $id ], [ '%d' ], [ '%d' ] );
	}

	/**
	 * Count active (non-expired) sponsored foods — used in Analytics KPI card.
	 */
	public static function count_active_sponsors(): int {
		global $wpdb;
		$table = self::foods_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			  WHERE is_sponsored = 1
			    AND sponsor_active = 1
			    AND (sponsor_expires_at IS NULL OR sponsor_expires_at > NOW())"
		);
	}

	// -------------------------------------------------------------------------
	// White Label Licenses.
	// -------------------------------------------------------------------------

	/**
	 * Create (or upgrade) the fcc_wl_licenses table via dbDelta.
	 * Called by seed_v18() on existing installs.
	 */
	public static function create_wl_licenses_table(): void {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::wl_licenses_table();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE {$table} (
  id              int(11) NOT NULL AUTO_INCREMENT,
  license_key     varchar(64) NOT NULL,
  client_name     varchar(200) NOT NULL,
  client_email    varchar(200) NOT NULL,
  client_url      varchar(500) DEFAULT NULL,
  business_type   varchar(50) NOT NULL DEFAULT 'other',
  tier            varchar(20) NOT NULL DEFAULT 'starter',
  price_gbp       decimal(8,2) NOT NULL DEFAULT 99.00,
  status          varchar(20) NOT NULL DEFAULT 'trial',
  allowed_domains text DEFAULT NULL,
  brand_name      varchar(200) DEFAULT NULL,
  primary_colour  varchar(7) DEFAULT NULL,
  accent_colour   varchar(7) DEFAULT NULL,
  logo_url        varchar(500) DEFAULT NULL,
  hide_powered_by tinyint(1) NOT NULL DEFAULT 0,
  custom_css      text DEFAULT NULL,
  notes           text DEFAULT NULL,
  embed_loads     int(11) NOT NULL DEFAULT 0,
  search_count    int(11) NOT NULL DEFAULT 0,
  expires_at      datetime DEFAULT NULL,
  renewed_at      datetime DEFAULT NULL,
  created_at      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY license_key (license_key),
  KEY status (status),
  KEY expires_at (expires_at)
) {$charset_collate};";
		// phpcs:enable
		dbDelta( $sql );
	}

	/**
	 * Generate a cryptographically random 32-char uppercase hex license key.
	 */
	public static function generate_license_key(): string {
		return strtoupper( bin2hex( random_bytes( 16 ) ) );
	}

	/**
	 * Insert a new white-label license.
	 *
	 * @param array<string,mixed> $data
	 * @return int|false  Inserted row ID or false on failure.
	 */
	public static function insert_wl_license( array $data ): int|false {
		global $wpdb;

		if ( empty( $data['license_key'] ) ) {
			$data['license_key'] = self::generate_license_key();
		}

		$result = $wpdb->insert(
			self::wl_licenses_table(),
			[
				'license_key'     => sanitize_text_field( $data['license_key'] ),
				'client_name'     => sanitize_text_field( $data['client_name'] ?? '' ),
				'client_email'    => sanitize_email( $data['client_email'] ?? '' ),
				'client_url'      => esc_url_raw( $data['client_url'] ?? '' ),
				'business_type'   => sanitize_key( $data['business_type'] ?? 'other' ),
				'tier'            => sanitize_key( $data['tier'] ?? 'starter' ),
				'price_gbp'       => (float) ( $data['price_gbp'] ?? 99.00 ),
				'status'          => sanitize_key( $data['status'] ?? 'trial' ),
				'allowed_domains' => isset( $data['allowed_domains'] ) ? wp_json_encode( (array) $data['allowed_domains'] ) : null,
				'brand_name'      => sanitize_text_field( $data['brand_name'] ?? '' ),
				'primary_colour'  => sanitize_hex_color( $data['primary_colour'] ?? '' ) ?: null,
				'accent_colour'   => sanitize_hex_color( $data['accent_colour'] ?? '' ) ?: null,
				'logo_url'        => esc_url_raw( $data['logo_url'] ?? '' ) ?: null,
				'hide_powered_by' => (int) ( $data['hide_powered_by'] ?? 0 ),
				'custom_css'      => wp_strip_all_tags( $data['custom_css'] ?? '' ) ?: null,
				'notes'           => sanitize_textarea_field( $data['notes'] ?? '' ) ?: null,
				'expires_at'      => ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null,
			]
		);

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update an existing white-label license.
	 *
	 * @param array<string,mixed> $data
	 */
	public static function update_wl_license( int $id, array $data ): bool {
		global $wpdb;

		$update = [];

		if ( isset( $data['client_name'] ) )     $update['client_name']     = sanitize_text_field( $data['client_name'] );
		if ( isset( $data['client_email'] ) )     $update['client_email']    = sanitize_email( $data['client_email'] );
		if ( isset( $data['client_url'] ) )       $update['client_url']      = esc_url_raw( $data['client_url'] );
		if ( isset( $data['business_type'] ) )    $update['business_type']   = sanitize_key( $data['business_type'] );
		if ( isset( $data['tier'] ) )             $update['tier']            = sanitize_key( $data['tier'] );
		if ( isset( $data['price_gbp'] ) )        $update['price_gbp']       = (float) $data['price_gbp'];
		if ( isset( $data['status'] ) )           $update['status']          = sanitize_key( $data['status'] );
		if ( isset( $data['allowed_domains'] ) )  $update['allowed_domains'] = wp_json_encode( (array) $data['allowed_domains'] );
		if ( isset( $data['brand_name'] ) )       $update['brand_name']      = sanitize_text_field( $data['brand_name'] );
		if ( isset( $data['primary_colour'] ) )   $update['primary_colour']  = sanitize_hex_color( $data['primary_colour'] ) ?: null;
		if ( isset( $data['accent_colour'] ) )    $update['accent_colour']   = sanitize_hex_color( $data['accent_colour'] ) ?: null;
		if ( isset( $data['logo_url'] ) )         $update['logo_url']        = esc_url_raw( $data['logo_url'] ) ?: null;
		if ( isset( $data['hide_powered_by'] ) )  $update['hide_powered_by'] = (int) $data['hide_powered_by'];
		if ( isset( $data['custom_css'] ) )       $update['custom_css']      = wp_strip_all_tags( $data['custom_css'] ) ?: null;
		if ( isset( $data['notes'] ) )            $update['notes']           = sanitize_textarea_field( $data['notes'] ) ?: null;
		if ( array_key_exists( 'expires_at', $data ) ) {
			$update['expires_at'] = ! empty( $data['expires_at'] ) ? sanitize_text_field( $data['expires_at'] ) : null;
		}
		if ( isset( $data['renewed_at'] ) ) $update['renewed_at'] = sanitize_text_field( $data['renewed_at'] );

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::wl_licenses_table(), $update, [ 'id' => $id ] );
		return false !== $result;
	}

	/**
	 * Delete a white-label license.
	 */
	public static function delete_wl_license( int $id ): bool {
		global $wpdb;
		$result = $wpdb->delete( self::wl_licenses_table(), [ 'id' => $id ], [ '%d' ] );
		return (bool) $result;
	}

	/**
	 * Get a single license by ID.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function get_wl_license( int $id ): ?array {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ? self::decode_wl_license( $row ) : null;
	}

	/**
	 * Get a single license by key string.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function get_wl_license_by_key( string $key ): ?array {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE license_key = %s", $key ), ARRAY_A );
		return $row ? self::decode_wl_license( $row ) : null;
	}

	/**
	 * Paginated list of licenses with optional status filter.
	 *
	 * @param array<string,mixed> $args  Keys: status, per_page, page, orderby, order
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_wl_licenses( array $args = [] ): array {
		global $wpdb;
		$table    = self::wl_licenses_table();
		$per_page = max( 1, (int) ( $args['per_page'] ?? 25 ) );
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;
		$orderby  = in_array( $args['orderby'] ?? '', [ 'client_name', 'tier', 'status', 'expires_at', 'created_at', 'embed_loads' ], true )
			? $args['orderby'] : 'created_at';
		$order    = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

		$where = '1=1';
		$values = [];

		if ( ! empty( $args['status'] ) ) {
			$where   .= ' AND status = %s';
			$values[] = $args['status'];
		}

		$values[] = $per_page;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				...$values
			),
			ARRAY_A
		) ?? [];

		return array_map( [ self::class, 'decode_wl_license' ], $rows );
	}

	/**
	 * Count licenses with optional status filter.
	 *
	 * @param array<string,mixed> $args
	 */
	public static function count_wl_licenses( array $args = [] ): int {
		global $wpdb;
		$table = self::wl_licenses_table();

		if ( ! empty( $args['status'] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $args['status'] )
			);
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/**
	 * Increment embed_loads counter for a license key.
	 */
	public static function increment_wl_embed_load( string $key ): void {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET embed_loads = embed_loads + 1 WHERE license_key = %s", $key ) );
	}

	/**
	 * Increment search_count counter for a license key.
	 */
	public static function increment_wl_search_count( string $key ): void {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET search_count = search_count + 1 WHERE license_key = %s", $key ) );
	}

	/**
	 * Get aggregate stats for the White Label dashboard hero.
	 *
	 * @return array{total:int,active:int,expiring_30d:int,mrr:float,arr:float}
	 */
	public static function get_wl_stats(): array {
		global $wpdb;
		$table = self::wl_licenses_table();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			"SELECT
			    COUNT(*) AS total,
			    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
			    SUM(CASE WHEN status = 'active' AND expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_30d,
			    SUM(CASE WHEN status = 'active' THEN price_gbp ELSE 0 END) / 12 AS mrr,
			    SUM(CASE WHEN status = 'active' THEN price_gbp ELSE 0 END) AS arr
			  FROM {$table}",
			ARRAY_A
		);

		return [
			'total'        => (int)   ( $row['total']        ?? 0 ),
			'active'       => (int)   ( $row['active']       ?? 0 ),
			'expiring_30d' => (int)   ( $row['expiring_30d'] ?? 0 ),
			'mrr'          => (float) ( $row['mrr']          ?? 0 ),
			'arr'          => (float) ( $row['arr']          ?? 0 ),
		];
	}

	/**
	 * Get licenses expiring within a given number of days.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function get_wl_expiring( int $days = 30 ): array {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				  WHERE status = 'active'
				    AND expires_at IS NOT NULL
				    AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL %d DAY)
				  ORDER BY expires_at ASC",
				$days
			),
			ARRAY_A
		) ?? [];

		return array_map( [ self::class, 'decode_wl_license' ], $rows );
	}

	/**
	 * Get per-tier revenue breakdown for the summary cards.
	 *
	 * @return array<string,array{count:int,arr:float}>
	 */
	public static function get_wl_tier_breakdown(): array {
		global $wpdb;
		$table = self::wl_licenses_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT tier,
			        COUNT(*) AS cnt,
			        SUM(CASE WHEN status = 'active' THEN price_gbp ELSE 0 END) AS arr
			   FROM {$table}
			  GROUP BY tier",
			ARRAY_A
		) ?? [];

		$result = [];
		foreach ( $rows as $r ) {
			$result[ $r['tier'] ] = [
				'count' => (int)   $r['cnt'],
				'arr'   => (float) $r['arr'],
			];
		}

		return $result;
	}

	/**
	 * Cast raw DB row to correct PHP types.
	 *
	 * @param array<string,mixed> $row
	 * @return array<string,mixed>
	 */
	private static function decode_wl_license( array $row ): array {
		$row['id']              = (int) $row['id'];
		$row['price_gbp']       = (float) $row['price_gbp'];
		$row['hide_powered_by'] = (bool) $row['hide_powered_by'];
		$row['embed_loads']     = (int) $row['embed_loads'];
		$row['search_count']    = (int) $row['search_count'];

		// Decode allowed_domains JSON → array.
		if ( ! empty( $row['allowed_domains'] ) ) {
			$decoded = json_decode( $row['allowed_domains'], true );
			$row['allowed_domains'] = is_array( $decoded ) ? $decoded : [];
		} else {
			$row['allowed_domains'] = [];
		}

		return $row;
	}

	// ─────────────────────────────────────────────────────────────────────
	// Analytics v2 — comparison, trending, monetization, content, audience.
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Total searches for current period AND previous period of equal length.
	 *
	 * @return array{current:int,previous:int}
	 */
	public static function count_searches_with_comparison( int $days ): array {
		if ( $days <= 0 ) {
			$total = self::count_total_searches( 0 );
			return [ 'current' => $total, 'previous' => 0 ];
		}
		global $wpdb;
		$t = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				    COALESCE(SUM(CASE WHEN log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY) THEN count END), 0) AS current_val,
				    COALESCE(SUM(CASE WHEN log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY) AND log_date < DATE_SUB(CURDATE(), INTERVAL %d DAY) THEN count END), 0) AS previous_val
				 FROM {$t}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)",
				$days, $days * 2, $days, $days * 2
			),
			ARRAY_A
		);
		return [
			'current'  => (int) ( $row['current_val']  ?? 0 ),
			'previous' => (int) ( $row['previous_val'] ?? 0 ),
		];
	}

	/**
	 * Success rate for current AND previous period.
	 *
	 * @return array{current:float,previous:float}
	 */
	public static function get_success_rate_with_comparison( int $days ): array {
		if ( $days <= 0 ) {
			return [ 'current' => self::get_search_success_rate( 0 ), 'previous' => 0.0 ];
		}
		global $wpdb;
		$t = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				    CASE WHEN log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY) THEN 'cur' ELSE 'prev' END AS period,
				    SUM(count) AS total,
				    SUM(CASE WHEN has_results=1 THEN count ELSE 0 END) AS hits
				 FROM {$t}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				 GROUP BY period",
				$days, $days * 2
			),
			ARRAY_A
		) ?: [];

		$out = [ 'current' => 0.0, 'previous' => 0.0 ];
		foreach ( $rows as $r ) {
			$rate = (int) $r['total'] > 0 ? round( (int) $r['hits'] / (int) $r['total'] * 100, 1 ) : 0.0;
			if ( $r['period'] === 'cur' ) {
				$out['current'] = $rate;
			} else {
				$out['previous'] = $rate;
			}
		}
		return $out;
	}

	/**
	 * Trending searches — queries with biggest growth vs previous period.
	 *
	 * @return array<int,array{query:string,current_count:int,previous_count:int,growth_pct:float}>
	 */
	public static function get_trending_searches( int $days = 30, int $limit = 15 ): array {
		global $wpdb;
		$t = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT query,
				    COALESCE(SUM(CASE WHEN log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY) THEN count END), 0) AS cur,
				    COALESCE(SUM(CASE WHEN log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY) AND log_date < DATE_SUB(CURDATE(), INTERVAL %d DAY) THEN count END), 0) AS prev
				 FROM {$t}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				 GROUP BY query
				 HAVING cur > 0
				 ORDER BY (cur - prev) / GREATEST(prev, 1) DESC, cur DESC
				 LIMIT %d",
				$days, $days * 2, $days, $days * 2, $limit
			),
			ARRAY_A
		) ?: [];

		return array_map( function ( $r ) {
			$cur  = (int) $r['cur'];
			$prev = (int) $r['prev'];
			return [
				'query'          => $r['query'],
				'current_count'  => $cur,
				'previous_count' => $prev,
				'growth_pct'     => $prev > 0 ? round( ( $cur - $prev ) / $prev * 100, 1 ) : ( $cur > 0 ? 100.0 : 0.0 ),
			];
		}, $rows );
	}

	/**
	 * Daily success rate for trend chart.
	 *
	 * @return array<int,array{log_date:string,success_rate:float,total:int}>
	 */
	public static function get_success_rate_by_day( int $days = 30 ): array {
		global $wpdb;
		$t = self::search_log_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT log_date, SUM(count) AS total,
				    SUM(CASE WHEN has_results=1 THEN count ELSE 0 END) AS hits
				 FROM {$t}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				 GROUP BY log_date ORDER BY log_date ASC",
				$days
			),
			ARRAY_A
		) ?: [];

		return array_map( function ( $r ) {
			$total = (int) $r['total'];
			return [
				'log_date'     => $r['log_date'],
				'success_rate' => $total > 0 ? round( (int) $r['hits'] / $total * 100, 1 ) : 0.0,
				'total'        => $total,
			];
		}, $rows );
	}

	/**
	 * Search count by food category (all-time, uses foods.search_count).
	 *
	 * @return array<int,array{category_id:int,category_name:string,search_count:int}>
	 */
	public static function get_searches_by_category(): array {
		global $wpdb;
		$ft = self::foods_table();
		$ct = self::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			"SELECT c.id AS category_id, c.name AS category_name, COALESCE(SUM(f.search_count),0) AS search_count
			 FROM {$ct} c
			 LEFT JOIN {$ft} f ON f.category_id = c.id
			 GROUP BY c.id, c.name
			 HAVING search_count > 0
			 ORDER BY search_count DESC",
			ARRAY_A
		) ?: [];
	}

	/**
	 * Search volume by day of week.
	 *
	 * @return array<int,array{day_of_week:int,day_name:string,total:int}>
	 */
	public static function get_searches_by_day_of_week( int $days = 90 ): array {
		global $wpdb;
		$t = self::search_log_table();
		$day_names = [ 1 => 'Sun', 2 => 'Mon', 3 => 'Tue', 4 => 'Wed', 5 => 'Thu', 6 => 'Fri', 7 => 'Sat' ];
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DAYOFWEEK(log_date) AS dow, SUM(count) AS total
				 FROM {$t}
				 WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				 GROUP BY DAYOFWEEK(log_date)
				 ORDER BY DAYOFWEEK(log_date)",
				$days
			),
			ARRAY_A
		) ?: [];

		return array_map( function ( $r ) use ( $day_names ) {
			$dow = (int) $r['dow'];
			return [
				'day_of_week' => $dow,
				'day_name'    => $day_names[ $dow ] ?? '?',
				'total'       => (int) $r['total'],
			];
		}, $rows );
	}

	/**
	 * Total sponsor clicks for a period, with previous-period comparison.
	 *
	 * @return array{current:int,previous:int}
	 */
	public static function get_total_sponsor_clicks( int $days = 30 ): array {
		global $wpdb;
		$t = self::sponsor_clicks_table();
		if ( $days <= 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" );
			return [ 'current' => $total, 'previous' => 0 ];
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				    SUM(CASE WHEN clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY) THEN 1 ELSE 0 END) AS cur,
				    SUM(CASE WHEN clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY) AND clicked_at < DATE_SUB(NOW(), INTERVAL %d DAY) THEN 1 ELSE 0 END) AS prev
				 FROM {$t}
				 WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days, $days * 2, $days, $days * 2
			),
			ARRAY_A
		);
		return [
			'current'  => (int) ( $row['cur']  ?? 0 ),
			'previous' => (int) ( $row['prev'] ?? 0 ),
		];
	}

	/**
	 * Sponsor clicks grouped by food.
	 *
	 * @return array<int,array{food_id:int,food_name:string,sponsor_name:string,clicks:int}>
	 */
	public static function get_sponsor_clicks_by_food( int $days = 30, int $limit = 10 ): array {
		global $wpdb;
		$ct = self::sponsor_clicks_table();
		$ft = self::foods_table();
		$where_date = $days > 0
			? $wpdb->prepare( 'AND sc.clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days )
			: '';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT sc.food_id, f.name AS food_name, f.sponsor_name, COUNT(*) AS clicks
				 FROM {$ct} sc
				 INNER JOIN {$ft} f ON f.id = sc.food_id
				 WHERE 1=1 {$where_date}
				 GROUP BY sc.food_id
				 ORDER BY clicks DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Sponsor clicks by day for trend sparkline.
	 *
	 * @return array<int,array{click_date:string,clicks:int}>
	 */
	public static function get_sponsor_clicks_by_day( int $days = 30 ): array {
		global $wpdb;
		$t = self::sponsor_clicks_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(clicked_at) AS click_date, COUNT(*) AS clicks
				 FROM {$t}
				 WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY DATE(clicked_at) ORDER BY click_date ASC",
				$days
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Content gaps scored by search_count × recency weight.
	 *
	 * @return array<int,array{id:int,query:string,search_count:int,last_searched_at:string,priority_score:float}>
	 */
	public static function get_scored_content_gaps( int $limit = 20 ): array {
		global $wpdb;
		$t = self::missed_searches_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, query, search_count, last_searched_at,
				    search_count * CASE
				        WHEN last_searched_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 3
				        WHEN last_searched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 2
				        ELSE 1
				    END AS priority_score
				 FROM {$t}
				 WHERE status = 'active'
				 ORDER BY priority_score DESC, search_count DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Food request counts by status for pipeline visualisation.
	 *
	 * @return array{pending:int,done:int,dismissed:int,total:int}
	 */
	public static function get_request_pipeline_counts(): array {
		global $wpdb;
		$t = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT status, COUNT(*) AS cnt FROM {$t} GROUP BY status",
			ARRAY_A
		) ?: [];

		$out = [ 'pending' => 0, 'done' => 0, 'dismissed' => 0, 'total' => 0 ];
		foreach ( $rows as $r ) {
			$s = $r['status'];
			$c = (int) $r['cnt'];
			if ( isset( $out[ $s ] ) ) {
				$out[ $s ] = $c;
			}
			$out['total'] += $c;
		}
		return $out;
	}

	/**
	 * Foods per category for coverage analysis.
	 *
	 * @return array<int,array{category_id:int,category_name:string,food_count:int}>
	 */
	public static function get_category_coverage(): array {
		global $wpdb;
		$ft = self::foods_table();
		$ct = self::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			"SELECT c.id AS category_id, c.name AS category_name, COUNT(f.id) AS food_count
			 FROM {$ct} c
			 LEFT JOIN {$ft} f ON f.category_id = c.id
			 GROUP BY c.id, c.name
			 ORDER BY food_count DESC",
			ARRAY_A
		) ?: [];
	}

	/**
	 * Monthly subscriber growth (cumulative opt-ins).
	 *
	 * @return array<int,array{month:string,count:int,cumulative:int}>
	 */
	public static function get_subscriber_growth( int $months = 12 ): array {
		global $wpdb;
		$t = self::requests_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(created_at, '%%Y-%%m') AS month, COUNT(*) AS count
				 FROM {$t}
				 WHERE marketing_optin = 1 AND requester_email != ''
				   AND created_at >= DATE_SUB(NOW(), INTERVAL %d MONTH)
				 GROUP BY month ORDER BY month ASC",
				$months
			),
			ARRAY_A
		) ?: [];

		$cumulative = 0;
		return array_map( function ( $r ) use ( &$cumulative ) {
			$cumulative += (int) $r['count'];
			return [
				'month'      => $r['month'],
				'count'      => (int) $r['count'],
				'cumulative' => $cumulative,
			];
		}, $rows );
	}

	// ─── Analytics helper queries ────────────────────────────────────

	public static function count_all_foods(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::foods_table() );
	}

	public static function count_all_categories(): int {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::categories_table() );
	}

	public static function count_foods_with_serving_sizes(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . self::foods_table() . " WHERE serving_sizes IS NOT NULL AND serving_sizes != '' AND serving_sizes != '[]'"
		);
	}

	public static function count_foods_with_micronutrients(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . self::foods_table() . " WHERE iron_mg IS NOT NULL OR calcium_mg IS NOT NULL OR vitamin_c_mg IS NOT NULL"
		);
	}

	public static function count_foods_with_allergen_tags(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM " . self::foods_table() . " WHERE contains_gluten = 1 OR contains_dairy = 1 OR contains_nuts = 1 OR contains_eggs = 1 OR contains_soy = 1 OR contains_fish = 1 OR contains_shellfish = 1 OR contains_celery = 1 OR is_vegetarian = 1 OR is_vegan = 1 OR is_halal = 1 OR is_kosher = 1 OR is_keto_friendly = 1 OR is_gluten_free = 1"
		);
	}

	public static function get_avg_daily_searches( int $days = 30 ): float {
		global $wpdb;
		$t = self::search_log_table();
		if ( $days > 0 ) {
			$val = $wpdb->get_var( $wpdb->prepare(
				"SELECT COALESCE(COUNT(*) / NULLIF(DATEDIFF(NOW(), MIN(created_at)), 0), 0) FROM {$t} WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			) );
		} else {
			$val = $wpdb->get_var( "SELECT COALESCE(COUNT(*) / NULLIF(DATEDIFF(NOW(), MIN(created_at)), 0), 0) FROM {$t}" );
		}
		return round( (float) $val, 1 );
	}

	public static function get_searches_by_hour( int $days = 30 ): array {
		global $wpdb;
		$t = self::search_log_table();
		$where = $days > 0
			? $wpdb->prepare( "WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days )
			: '';
		return $wpdb->get_results(
			"SELECT HOUR(created_at) AS hour, COUNT(*) AS total FROM {$t} {$where} GROUP BY hour ORDER BY hour ASC",
			ARRAY_A
		) ?: [];
	}

	public static function get_peak_search_hour( int $days = 30 ): ?array {
		$by_hour = self::get_searches_by_hour( $days );
		if ( empty( $by_hour ) ) return null;
		usort( $by_hour, function ( $a, $b ) { return (int) $b['total'] - (int) $a['total']; } );
		return $by_hour[0];
	}

	public static function get_zero_result_queries( int $limit = 10 ): array {
		global $wpdb;
		$t = self::search_log_table();
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT query, COUNT(*) AS search_count, MAX(created_at) AS last_searched
			 FROM {$t} WHERE food_id IS NULL AND query != ''
			 GROUP BY query ORDER BY search_count DESC LIMIT %d",
			$limit
		), ARRAY_A ) ?: [];
	}

	public static function get_repeat_visitor_rate( int $days = 30 ): float {
		global $wpdb;
		$t = self::search_log_table();
		$where = $days > 0
			? $wpdb->prepare( "WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)", $days )
			: '';
		$total_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$t} {$where}" );
		if ( $total_sessions === 0 ) return 0;
		$repeat = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM (SELECT session_id, COUNT(*) AS c FROM {$t} {$where} GROUP BY session_id HAVING c > 1) AS sub"
		);
		return round( $repeat / $total_sessions * 100, 1 );
	}
}
