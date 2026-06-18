<?php
/**
 * Admin: Categories manager (CRUD).
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Categories {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_save_category',       $this, 'handle_save_category' );
		$loader->add_action( 'admin_post_fcc_delete_category',     $this, 'handle_delete_category' );
		$loader->add_action( 'wp_ajax_fcc_ajax_save_category',     $this, 'ajax_save_category' );
		$loader->add_action( 'wp_ajax_fcc_ajax_delete_category',   $this, 'ajax_delete_category' );
		$loader->add_action( 'wp_ajax_fcc_ajax_merge_category',    $this, 'ajax_merge_category' );
	}

	// -------------------------------------------------------------------------
	// Handlers.
	// -------------------------------------------------------------------------

	public function handle_save_category(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		check_admin_referer( 'fcc_save_category' );

		$id   = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$data = [
			'name'          => sanitize_text_field( $_POST['name']          ?? '' ),
			'slug'          => sanitize_title(      $_POST['slug']          ?? '' ),
			'description'   => sanitize_textarea_field( $_POST['description'] ?? '' ),
			'display_order' => absint( $_POST['display_order'] ?? 0 ),
		];

		if ( empty( $data['name'] ) ) {
			$this->redirect( 'error', __( 'Category name is required.', 'food-calorie-calculator' ) );
			return;
		}

		if ( empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_title( $data['name'] );
		}

		if ( $id > 0 ) {
			\FCC\Database::update_category( $id, $data );
			$this->redirect( 'success', __( 'Category updated.', 'food-calorie-calculator' ) );
		} else {
			$new_id = \FCC\Database::insert_category( $data );
			if ( $new_id ) {
				$this->redirect( 'success', __( 'Category added.', 'food-calorie-calculator' ) );
			} else {
				$this->redirect( 'error', __( 'Failed to add category.', 'food-calorie-calculator' ) );
			}
		}
	}

	public function handle_delete_category(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		$id = isset( $_GET['category_id'] ) ? absint( $_GET['category_id'] ) : 0;
		check_admin_referer( 'fcc_delete_category_' . $id );

		if ( $id > 0 ) {
			\FCC\Database::delete_category( $id );
		}

		$this->redirect( 'success', __( 'Category deleted.', 'food-calorie-calculator' ) );
	}

	// -------------------------------------------------------------------------
	// AJAX handlers.
	// -------------------------------------------------------------------------

	public function ajax_save_category(): void {
		check_ajax_referer( 'fcc_save_category' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.', 403 );
		}

		$id   = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		$data = [
			'name'          => sanitize_text_field( $_POST['name']        ?? '' ),
			'slug'          => sanitize_title(      $_POST['slug']        ?? '' ),
			'description'   => sanitize_textarea_field( $_POST['description'] ?? '' ),
			'display_order' => absint( $_POST['display_order'] ?? 0 ),
		];

		if ( empty( $data['name'] ) ) {
			wp_send_json_error( __( 'Category name is required.', 'food-calorie-calculator' ) );
		}

		if ( empty( $data['slug'] ) ) {
			$data['slug'] = sanitize_title( $data['name'] );
		}

		if ( $id > 0 ) {
			\FCC\Database::update_category( $id, $data );
			$message = __( 'Category updated.', 'food-calorie-calculator' );
		} else {
			$new_id = \FCC\Database::insert_category( $data );
			if ( ! $new_id ) {
				wp_send_json_error( __( 'Failed to add category.', 'food-calorie-calculator' ) );
			}
			$message = __( 'Category added.', 'food-calorie-calculator' );
		}

		wp_send_json_success( [
			'html'    => $this->render_grid_html(),
			'message' => $message,
		] );
	}

	public function ajax_delete_category(): void {
		check_ajax_referer( 'fcc_ajax_cats' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.', 403 );
		}

		$id = isset( $_POST['category_id'] ) ? absint( $_POST['category_id'] ) : 0;
		if ( $id > 0 ) {
			\FCC\Database::delete_category( $id );
		}

		wp_send_json_success( [
			'html'    => $this->render_grid_html(),
			'message' => __( 'Category deleted.', 'food-calorie-calculator' ),
		] );
	}

	public function ajax_merge_category(): void {
		check_ajax_referer( 'fcc_ajax_cats' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Permission denied.', 403 ); }

		$source = absint( $_POST['source_id'] ?? 0 );
		$target = absint( $_POST['target_id'] ?? 0 );
		if ( ! $source || ! $target || $source === $target ) { wp_send_json_error( 'Invalid category IDs.' ); }

		$moved = \FCC\Database::merge_categories( $source, $target );

		wp_send_json_success( [
			'html'    => $this->render_grid_html(),
			'message' => sprintf( __( 'Merged — %d food(s) moved.', 'food-calorie-calculator' ), $moved ),
		] );
	}

	// -------------------------------------------------------------------------
	// Helpers.
	// -------------------------------------------------------------------------

	private function render_grid_html(): string {
		$categories  = \FCC\Database::get_all_categories();
		$cat_stats   = \FCC\Database::get_category_stats();
		$top_foods   = \FCC\Database::get_top_food_per_category();
		$food_counts = [];
		foreach ( $cat_stats as $cid => $st ) {
			$food_counts[ $cid ] = $st['food_count'];
		}

		$palette = [
			'#3b82f6', '#22c55e', '#f97316', '#8b5cf6',
			'#14b8a6', '#ef4444', '#eab308', '#ec4899',
			'#06b6d4', '#84cc16', '#f59e0b', '#6366f1',
		];

		$icon_map = [
			'fruit'      => '🥦', 'veg'        => '🥦',
			'meat'       => '🥩', 'poultry'    => '🥩',
			'fish'       => '🐟', 'seafood'    => '🐟',
			'dairy'      => '🥛', 'egg'        => '🥚',
			'bread'      => '🍞', 'cereal'     => '🌾', 'grain' => '🌾',
			'nut'        => '🥜', 'seed'       => '🌰',
			'fat'        => '🫙', 'oil'        => '🫙',
			'drink'      => '🥤', 'beverage'   => '🥤',
			'snack'      => '🍫', 'confection' => '🍭',
			'takeaway'   => '🥡', 'ready'      => '🥡',
			'legume'     => '🫘', 'pulse'      => '🫘', 'bean' => '🫘',
			'condiment'  => '🧂', 'sauce'      => '🧂',
		];

		$get_icon = static function ( string $name ) use ( $icon_map ): string {
			$lower = strtolower( $name );
			foreach ( $icon_map as $key => $icon ) {
				if ( str_contains( $lower, $key ) ) {
					return $icon;
				}
			}
			return '🍽️';
		};

		$edit_id = 0;

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-categories-grid.php';
		return (string) ob_get_clean();
	}

	private function redirect( string $type, string $msg ): void {
		wp_safe_redirect( add_query_arg(
			[
				'page'       => 'fcc-categories',
				'fcc_notice' => rawurlencode( $msg ),
				'fcc_ntype'  => $type,
			],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
