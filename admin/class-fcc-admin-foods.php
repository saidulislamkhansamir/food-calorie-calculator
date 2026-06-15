<?php
/**
 * Admin: Foods manager.
 *
 * Handles add/edit/delete/bulk-delete for the food database.
 * All actions: nonce-verified + capability-checked.
 * All output: escaped.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Foods {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_save_food',   $this, 'handle_save_food' );
		$loader->add_action( 'admin_post_fcc_delete_food', $this, 'handle_delete_food' );
		$loader->add_action( 'admin_post_fcc_bulk_foods',  $this, 'handle_bulk_foods' );
	}

	// -------------------------------------------------------------------------
	// AJAX / POST handlers.
	// -------------------------------------------------------------------------

	/**
	 * Handle add or update of a food (action=fcc_save_food).
	 */
	public function handle_save_food(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		check_admin_referer( 'fcc_save_food' );

		$id   = isset( $_POST['food_id'] ) ? absint( $_POST['food_id'] ) : 0;
		$data = $this->sanitise_food_post( $_POST );

		if ( empty( $data['name'] ) ) {
			$this->redirect_with_notice( 'fcc-foods', 'error', __( 'Food name is required.', 'food-calorie-calculator' ), $id );
			return;
		}

		// Slug collision check.
		$slug = sanitize_title( $data['name'] );
		if ( \FCC\Database::slug_exists( $slug, $id ) ) {
			$slug .= '-' . time();
		}
		$data['slug'] = $slug;

		if ( $id > 0 ) {
			\FCC\Database::update_food( $id, $data );
			$this->redirect_with_notice( 'fcc-foods', 'success', __( 'Food updated.', 'food-calorie-calculator' ) );
		} else {
			$new_id = \FCC\Database::insert_food( $data );
			if ( $new_id ) {
				$this->redirect_with_notice( 'fcc-foods', 'success', __( 'Food added.', 'food-calorie-calculator' ) );
			} else {
				$this->redirect_with_notice( 'fcc-foods', 'error', __( 'Failed to add food.', 'food-calorie-calculator' ) );
			}
		}
	}

	/**
	 * Handle single-food delete (action=fcc_delete_food).
	 */
	public function handle_delete_food(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		$id = isset( $_GET['food_id'] ) ? absint( $_GET['food_id'] ) : 0;
		check_admin_referer( 'fcc_delete_food_' . $id );

		if ( $id > 0 ) {
			\FCC\Database::delete_food( $id );
		}

		$this->redirect_with_notice( 'fcc-foods', 'success', __( 'Food deleted.', 'food-calorie-calculator' ) );
	}

	/**
	 * Handle bulk actions submitted from the foods list table.
	 */
	public function handle_bulk_foods(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		check_admin_referer( 'fcc_bulk_foods' );

		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( $_POST['bulk_action'] ) : '';
		$ids    = isset( $_POST['food_ids'] ) && is_array( $_POST['food_ids'] )
			? array_map( 'absint', (array) $_POST['food_ids'] )
			: [];

		if ( 'delete' === $action && $ids ) {
			$deleted = \FCC\Database::bulk_delete_foods( $ids );
			$this->redirect_with_notice(
				'fcc-foods',
				'success',
				// translators: %d = number of foods deleted.
				sprintf( __( '%d food(s) deleted.', 'food-calorie-calculator' ), $deleted )
			);
			return;
		}

		$this->redirect_with_notice( 'fcc-foods', 'info', __( 'No action taken.', 'food-calorie-calculator' ) );
	}

	// -------------------------------------------------------------------------
	// Helpers.
	// -------------------------------------------------------------------------

	/**
	 * Sanitise all POST fields for a food row.
	 * Nullable fields remain null when submitted as empty string.
	 *
	 * @param array<string,mixed> $post
	 * @return array<string,mixed>
	 */
	private function sanitise_food_post( array $post ): array {
		$nullable_float_fields = [
			'of_which_sugars_g', 'of_which_saturates_g', 'fibre_g', 'salt_g',
			'omega3_total_mg', 'omega3_ala_mg', 'omega3_epa_mg', 'omega3_dha_mg',
			'caffeine_mg', 'portion_grams',
		];

		$data = [
			'name'          => sanitize_text_field( $post['name']          ?? '' ),
			'category_id'   => absint(              $post['category_id']   ?? 0  ),
			'energy_kcal'   => (float)             ($post['energy_kcal']   ?? 0  ),
			'energy_kj'     => (float)             ($post['energy_kj']     ?? 0  ),
			'protein_g'     => (float)             ($post['protein_g']     ?? 0  ),
			'carbohydrate_g'=> (float)             ($post['carbohydrate_g']?? 0  ),
			'fat_g'         => (float)             ($post['fat_g']         ?? 0  ),
			'is_fruit_veg'  => isset( $post['is_fruit_veg'] ) ? 1 : null,
			'source_notes'  => sanitize_textarea_field( $post['source_notes'] ?? '' ),
		];

		foreach ( $nullable_float_fields as $field ) {
			$raw = trim( (string) ( $post[ $field ] ?? '' ) );
			$data[ $field ] = ( '' === $raw ) ? null : (float) $raw;
		}

		// Serving sizes: submitted as JSON string or built from paired arrays.
		if ( ! empty( $post['serving_sizes_json'] ) ) {
			if ( strlen( $post['serving_sizes_json'] ) > 50000 ) {
				$data['serving_sizes'] = [];
			} else {
				$decoded = json_decode( wp_unslash( $post['serving_sizes_json'] ), true );
				$data['serving_sizes'] = is_array( $decoded ) ? $decoded : [];
			}
		} elseif ( isset( $post['serving_label'] ) && is_array( $post['serving_label'] ) ) {
			$labels = (array) $post['serving_label'];
			$grams  = (array) ( $post['serving_grams'] ?? [] );
			$sizes  = [];
			foreach ( $labels as $i => $label ) {
				$label = sanitize_text_field( $label );
				$g     = isset( $grams[ $i ] ) ? (float) $grams[ $i ] : 0;
				if ( $label && $g > 0 ) {
					$sizes[] = [ 'label' => $label, 'grams' => $g ];
				}
			}
			$data['serving_sizes'] = $sizes;
		} else {
			$data['serving_sizes'] = [];
		}

		return $data;
	}

	/**
	 * Redirect back to an admin page and pass a notice.
	 *
	 * @param string $page   Admin page slug.
	 * @param string $type   'success' | 'error' | 'info'.
	 * @param string $msg    Human-readable message.
	 * @param int    $edit_id  Food ID to return to edit form (0 = list).
	 */
	private function redirect_with_notice( string $page, string $type, string $msg, int $edit_id = 0 ): void {
		$url = admin_url( 'admin.php?page=' . rawurlencode( $page ) );
		if ( $edit_id > 0 ) {
			$url = add_query_arg( 'food_id', $edit_id, $url );
		}
		$url = add_query_arg(
			[
				'fcc_notice' => rawurlencode( $msg ),
				'fcc_ntype'  => $type,
			],
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}

	// -------------------------------------------------------------------------
	// Static helpers used by partials.
	// -------------------------------------------------------------------------

	/**
	 * Render an admin notice from GET params (set by redirect_with_notice).
	 */
	public static function maybe_render_notice(): void {
		if ( empty( $_GET['fcc_notice'] ) ) {
			return;
		}
		$msg  = sanitize_text_field( wp_unslash( $_GET['fcc_notice'] ) );
		$type = in_array( $_GET['fcc_ntype'] ?? '', [ 'success', 'error', 'info', 'warning' ], true )
			? sanitize_key( $_GET['fcc_ntype'] )
			: 'info';
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $msg )
		);
	}

	/**
	 * Render a paginated foods list table (HTML fragment).
	 */
	public static function render_foods_table(): void {
		$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$category_id = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		$orderby     = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'name';
		$order       = isset( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ? 'DESC' : 'ASC';
		$paged       = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page    = 20;

		$result = \FCC\Database::get_foods( [
			'search'      => $search,
			'category_id' => $category_id,
			'orderby'     => $orderby,
			'order'       => $order,
			'per_page'    => $per_page,
			'page'        => $paged,
		] );

		$total      = $result['total'];
		$foods      = $result['rows'];
		$categories = \FCC\Database::get_all_categories();
		$cat_map    = [];
		foreach ( $categories as $cat ) {
			$cat_map[ (int) $cat['id'] ] = $cat['name'];
		}

		$total_pages  = (int) ceil( $total / $per_page );
		$current_page = admin_url( 'admin.php?page=fcc-foods' );

		include FCC_PLUGIN_DIR . 'admin/partials/page-foods-list.php';
	}
}
