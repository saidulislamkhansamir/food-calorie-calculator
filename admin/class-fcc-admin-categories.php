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
		$loader->add_action( 'admin_post_fcc_save_category',   $this, 'handle_save_category' );
		$loader->add_action( 'admin_post_fcc_delete_category', $this, 'handle_delete_category' );
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
	// Helpers.
	// -------------------------------------------------------------------------

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
