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
		$loader->add_action( 'admin_post_fcc_save_food',        $this, 'handle_save_food' );
		$loader->add_action( 'admin_post_fcc_delete_food',      $this, 'handle_delete_food' );
		$loader->add_action( 'admin_post_fcc_bulk_foods',       $this, 'handle_bulk_foods' );
		$loader->add_action( 'admin_post_fcc_duplicate_food',   $this, 'handle_duplicate_food' );
		$loader->add_action( 'admin_post_fcc_export_foods_view', $this, 'handle_export_view' );
		$loader->add_action( 'wp_ajax_fcc_foods_page',          $this, 'ajax_foods_page' );
		$loader->add_action( 'wp_ajax_fcc_quick_update_food',   $this, 'ajax_quick_update' );
		$loader->add_action( 'wp_ajax_fcc_toggle_active',      $this, 'ajax_toggle_active' );
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
				sprintf( __( '%d food(s) deleted.', 'food-calorie-calculator' ), $deleted )
			);
			return;
		}

		if ( 'change_category' === $action && $ids ) {
			$cat_id  = absint( $_POST['bulk_category_id'] ?? 0 );
			$updated = \FCC\Database::bulk_update_category( $ids, $cat_id );
			$this->redirect_with_notice(
				'fcc-foods',
				'success',
				sprintf( __( '%d food(s) updated.', 'food-calorie-calculator' ), $updated )
			);
			return;
		}

		if ( ( 'hide' === $action || 'show' === $action ) && $ids ) {
			$val     = 'show' === $action ? 1 : 0;
			$updated = \FCC\Database::bulk_set_active( $ids, $val );
			$this->redirect_with_notice(
				'fcc-foods',
				'success',
				sprintf( __( '%d food(s) %s.', 'food-calorie-calculator' ), $updated, 'show' === $action ? 'shown' : 'hidden' )
			);
			return;
		}

		$this->redirect_with_notice( 'fcc-foods', 'info', __( 'No action taken.', 'food-calorie-calculator' ) );
	}

	// -------------------------------------------------------------------------
	// AJAX: paginate foods table without a full page reload.
	// -------------------------------------------------------------------------

	public function ajax_foods_page(): void {
		check_ajax_referer( 'fcc_foods_page' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied', 403 );
		}

		$search     = isset( $_POST['s'] )           ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '';
		$cat_filter = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] )                  : 0;
		$orderby    = isset( $_POST['orderby'] )     ? sanitize_key( $_POST['orderby'] )                : 'name';
		$order      = isset( $_POST['order'] ) && 'desc' === strtolower( sanitize_key( $_POST['order'] ) ) ? 'DESC' : 'ASC';
		$paged      = isset( $_POST['paged'] )       ? max( 1, absint( $_POST['paged'] ) )              : 1;
		$per_page   = isset( $_POST['per_page'] )    ? max( 10, absint( $_POST['per_page'] ) )          : 20;
		$status     = isset( $_POST['status'] )       ? sanitize_key( $_POST['status'] )                 : '';
		if ( $per_page > 500 ) { $per_page = 500; }

		$result = \FCC\Database::get_foods( [
			'search'      => $search,
			'category_id' => $cat_filter,
			'orderby'     => $orderby,
			'order'       => $order,
			'per_page'    => $per_page,
			'page'        => $paged,
			'status'      => $status,
		] );

		$total       = $result['total'];
		$foods       = $result['rows'];
		$total_pages = (int) ceil( $total / $per_page );
		$list_url    = admin_url( 'admin.php?page=fcc-foods' );

		$categories = \FCC\Database::get_all_categories();
		$cat_map    = [];
		foreach ( $categories as $cat ) {
			$cat_map[ (int) $cat['id'] ] = esc_html( $cat['name'] );
		}

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-foods-table.php';
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html, 'paged' => $paged ] );
	}

	/** Duplicate a food and redirect to edit the copy. */
	public function handle_duplicate_food(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Permission denied.' ); }
		$id = absint( $_GET['food_id'] ?? 0 );
		check_admin_referer( 'fcc_duplicate_food_' . $id );
		$food = \FCC\Database::get_food( $id );
		if ( ! $food ) { $this->redirect_with_notice( 'fcc-foods', 'error', __( 'Food not found.', 'food-calorie-calculator' ) ); return; }
		unset( $food['id'], $food['created_at'] );
		$food['name'] .= ' (Copy)';
		$food['slug']  = sanitize_title( $food['name'] ) . '-' . time();
		$food['search_count'] = 0;
		$food['is_sponsored'] = 0;
		$food['sponsor_active'] = 0;
		$new_id = \FCC\Database::insert_food( $food );
		if ( $new_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=fcc-foods&action=edit&food_id=' . $new_id . '&fcc_notice=' . rawurlencode( __( 'Food duplicated.', 'food-calorie-calculator' ) ) . '&fcc_ntype=success' ) );
			exit;
		}
		$this->redirect_with_notice( 'fcc-foods', 'error', __( 'Duplication failed.', 'food-calorie-calculator' ) );
	}

	/** Export the currently filtered view as CSV. */
	public function handle_export_view(): void {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Permission denied.' ); }
		check_admin_referer( 'fcc_export_foods_view' );
		$args = [
			'search'      => sanitize_text_field( $_GET['s'] ?? '' ),
			'category_id' => absint( $_GET['category_id'] ?? 0 ),
			'status'      => sanitize_key( $_GET['status'] ?? '' ),
			'orderby'     => sanitize_key( $_GET['orderby'] ?? 'name' ),
			'order'       => ( isset( $_GET['order'] ) && 'desc' === strtolower( $_GET['order'] ) ) ? 'DESC' : 'ASC',
			'per_page'    => 99999,
			'page'        => 1,
		];
		$result  = \FCC\Database::get_foods( $args );
		$headers = [ 'Name','Category ID','kcal','kJ','Protein','Carbs','Sugars','Fat','Saturates','Fibre','Salt','Omega-3 Total','Caffeine','Iron (mg)','Calcium (mg)','Vitamin C (mg)','Active' ];
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="fcc-foods-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $headers );
		foreach ( $result['rows'] as $f ) {
			fputcsv( $out, [
				$f['name'], $f['category_id'], $f['energy_kcal'], $f['energy_kj'],
				$f['protein_g'], $f['carbohydrate_g'], $f['of_which_sugars_g'],
				$f['fat_g'], $f['of_which_saturates_g'], $f['fibre_g'], $f['salt_g'],
				$f['omega3_total_mg'], $f['caffeine_mg'],
				$f['iron_mg'], $f['calcium_mg'], $f['vitamin_c_mg'],
				$f['is_active'] ? 1 : 0,
			] );
		}
		fclose( $out );
		exit;
	}

	/** AJAX quick-update: inline edit of name, category, and macros. */
	public function ajax_quick_update(): void {
		check_ajax_referer( 'fcc_foods_page' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied', 403 ); }
		$id = absint( $_POST['food_id'] ?? 0 );
		if ( ! $id ) { wp_send_json_error( 'Missing food ID.' ); }
		$data = [
			'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
			'category_id'    => absint( $_POST['category_id'] ?? 0 ),
			'energy_kcal'    => (float) ( $_POST['energy_kcal'] ?? 0 ),
			'protein_g'      => (float) ( $_POST['protein_g'] ?? 0 ),
			'carbohydrate_g' => (float) ( $_POST['carbohydrate_g'] ?? 0 ),
			'fat_g'          => (float) ( $_POST['fat_g'] ?? 0 ),
		];
		if ( empty( $data['name'] ) ) { wp_send_json_error( 'Name is required.' ); }
		\FCC\Database::update_food( $id, $data );
		wp_send_json_success( [ 'message' => __( 'Updated.', 'food-calorie-calculator' ) ] );
	}

	/** AJAX: toggle is_active on/off for a food. */
	public function ajax_toggle_active(): void {
		check_ajax_referer( 'fcc_foods_page' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied', 403 ); }
		$id = absint( $_POST['food_id'] ?? 0 );
		if ( ! $id ) { wp_send_json_error( 'Missing food ID.' ); }
		$food = \FCC\Database::get_food( $id );
		if ( ! $food ) { wp_send_json_error( 'Food not found.' ); }
		$new_val = $food['is_active'] ? 0 : 1;
		\FCC\Database::set_food_field( $id, 'is_active', $new_val );
		wp_send_json_success( [ 'is_active' => $new_val ] );
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
			'caffeine_mg', 'iron_mg', 'calcium_mg', 'vitamin_c_mg', 'portion_grams',
		];

		$data = [
			'name'               => sanitize_text_field( $post['name']          ?? '' ),
			'category_id'        => absint(              $post['category_id']   ?? 0  ),
			'energy_kcal'        => (float)             ($post['energy_kcal']   ?? 0  ),
			'energy_kj'          => (float)             ($post['energy_kj']     ?? 0  ),
			'protein_g'          => (float)             ($post['protein_g']     ?? 0  ),
			'carbohydrate_g'     => (float)             ($post['carbohydrate_g']?? 0  ),
			'fat_g'              => (float)             ($post['fat_g']         ?? 0  ),
			'is_fruit_veg'       => isset( $post['is_fruit_veg'] ) ? 1 : null,
			'source_notes'       => sanitize_textarea_field( $post['source_notes'] ?? '' ),
			'is_sponsored'       => isset( $post['is_sponsored'] ) ? 1 : 0,
			'sponsor_active'     => isset( $post['sponsor_active'] ) ? 1 : 0,
			'sponsor_name'       => sanitize_text_field( $post['sponsor_name'] ?? '' ),
			'sponsor_logo_id'    => absint( $post['sponsor_logo_id'] ?? 0 ) ?: null,
			'sponsor_url'        => esc_url_raw( $post['sponsor_url'] ?? '' ),
			'sponsor_expires_at' => sanitize_text_field( $post['sponsor_expires_at'] ?? '' ),
			'allergen_fish'      => isset( $post['allergen_fish'] ) ? 1 : null,
			'allergen_shellfish' => isset( $post['allergen_shellfish'] ) ? 1 : null,
			'allergen_dairy'     => isset( $post['allergen_dairy'] ) ? 1 : null,
			'allergen_eggs'      => isset( $post['allergen_eggs'] ) ? 1 : null,
			'allergen_nuts'      => isset( $post['allergen_nuts'] ) ? 1 : null,
			'allergen_gluten'    => isset( $post['allergen_gluten'] ) ? 1 : null,
			'allergen_soy'       => isset( $post['allergen_soy'] ) ? 1 : null,
			'allergen_celery'    => isset( $post['allergen_celery'] ) ? 1 : null,
			'diet_keto'          => isset( $post['diet_keto'] ) ? 1 : null,
			'diet_paleo'         => isset( $post['diet_paleo'] ) ? 1 : null,
			'diet_halal'         => isset( $post['diet_halal'] ) ? 1 : null,
			'diet_kosher'        => isset( $post['diet_kosher'] ) ? 1 : null,
			'diet_vegan'         => isset( $post['diet_vegan'] ) ? 1 : null,
			'diet_vegetarian'    => isset( $post['diet_vegetarian'] ) ? 1 : null,
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
		$per_page    = isset( $_GET['per_page'] ) ? max( 10, min( 500, absint( $_GET['per_page'] ) ) ) : 20;
		$status      = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';

		$result = \FCC\Database::get_foods( [
			'search'      => $search,
			'category_id' => $category_id,
			'orderby'     => $orderby,
			'order'       => $order,
			'per_page'    => $per_page,
			'page'        => $paged,
			'status'      => $status,
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
