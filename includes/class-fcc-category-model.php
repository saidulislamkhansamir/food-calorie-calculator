<?php
/**
 * CRUD model for the fcc_categories table.
 *
 * @package FCC
 */

namespace FCC;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Category_Model {

	// ---------------------------------------------------------------------------
	// Read
	// ---------------------------------------------------------------------------

	/**
	 * Get all categories ordered by display_order then name.
	 *
	 * @return array<int,object>
	 */
	public static function get_all(): array {
		global $wpdb;
		$table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY display_order ASC, name ASC" ) ?: [];
	}

	/**
	 * Get a single category by ID.
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ) );
	}

	/**
	 * Get a single category by slug.
	 */
	public static function get_by_slug( string $slug ): ?object {
		global $wpdb;
		$table = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE slug = %s", $slug ) );
	}

	/**
	 * Return a key-value map id → name.
	 *
	 * @return array<int,string>
	 */
	public static function get_id_name_map(): array {
		$map = [];
		foreach ( self::get_all() as $cat ) {
			$map[ (int) $cat->id ] = $cat->name;
		}
		return $map;
	}

	/**
	 * Count foods in each category.
	 *
	 * @return array<int,int>  category_id → count
	 */
	public static function get_food_counts(): array {
		global $wpdb;
		$foods_table = Database::foods_table();
		$cats_table  = Database::categories_table();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT category_id, COUNT(*) AS cnt FROM `{$foods_table}` GROUP BY category_id"
		);
		$map  = [];
		foreach ( $rows as $row ) {
			$map[ (int) $row->category_id ] = (int) $row->cnt;
		}
		return $map;
	}

	// ---------------------------------------------------------------------------
	// Write
	// ---------------------------------------------------------------------------

	/**
	 * Insert a new category.
	 *
	 * @param array{name:string,slug:string,description:string,display_order:int} $data
	 * @return int|false  New ID or false on error.
	 */
	public static function create( array $data ): int|false {
		global $wpdb;
		$table = Database::categories_table();

		$result = $wpdb->insert(
			$table,
			[
				'name'          => sanitize_text_field( $data['name'] ),
				'slug'          => sanitize_title( $data['slug'] ?: $data['name'] ),
				'description'   => sanitize_textarea_field( $data['description'] ?? '' ),
				'display_order' => absint( $data['display_order'] ?? 0 ),
			],
			[ '%s', '%s', '%s', '%d' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an existing category.
	 *
	 * @param int   $id
	 * @param array $data
	 * @return int|false  Rows updated or false.
	 */
	public static function update( int $id, array $data ): int|false {
		global $wpdb;
		$table = Database::categories_table();

		return $wpdb->update(
			$table,
			[
				'name'          => sanitize_text_field( $data['name'] ),
				'slug'          => sanitize_title( $data['slug'] ?: $data['name'] ),
				'description'   => sanitize_textarea_field( $data['description'] ?? '' ),
				'display_order' => absint( $data['display_order'] ?? 0 ),
			],
			[ 'id' => $id ],
			[ '%s', '%s', '%s', '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Delete a category. Reassigns its foods to category 0 (uncategorised).
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		// Orphan foods rather than cascade-delete — data is precious.
		$foods_table = Database::foods_table();
		$wpdb->update( $foods_table, [ 'category_id' => 0 ], [ 'category_id' => $id ], [ '%d' ], [ '%d' ] );

		$table = Database::categories_table();
		return (bool) $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );
	}

	/**
	 * Ensure a slug is unique; append -2, -3 … if not.
	 */
	public static function unique_slug( string $base_slug, int $exclude_id = 0 ): string {
		global $wpdb;
		$table = Database::categories_table();
		$slug  = $base_slug;
		$i     = 1;

		while ( true ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM `{$table}` WHERE slug = %s AND id != %d",
					$slug,
					$exclude_id
				)
			);
			if ( ! $exists ) {
				break;
			}
			++$i;
			$slug = $base_slug . '-' . $i;
		}

		return $slug;
	}
}
