<?php
/**
 * Admin: Food Pages — manage content for hub, category, and food pages.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Food_Pages_Admin {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_post_fcc_save_hub_content', $this, 'save_hub_content' );
		$loader->add_action( 'admin_post_fcc_save_cat_content', $this, 'save_cat_content' );
	}

	public function page_food_pages(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-food-pages.php';
	}

	// -------------------------------------------------------------------------
	// Save handlers.
	// -------------------------------------------------------------------------

	public function save_hub_content(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_hub_content' );

		$hub_intro = isset( $_POST['hub_intro'] ) ? wp_kses_post( wp_unslash( $_POST['hub_intro'] ) ) : '';

		$all = \FCC\Settings::get_all();
		$all['content']['hub_intro'] = $hub_intro;
		\FCC\Settings::save( $all );

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'hub' ], admin_url( 'admin.php' ) ) );
		exit;
	}

	public function save_cat_content(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		check_admin_referer( 'fcc_save_cat_content' );

		$cat_id = absint( $_POST['cat_id'] ?? 0 );
		$desc   = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( $cat_id > 0 ) {
			\FCC\Database::update_category_description( $cat_id, $desc );
		}

		wp_safe_redirect( add_query_arg( [ 'page' => 'fcc-food-pages', 'saved' => 'cat', 'cat_id' => $cat_id ], admin_url( 'admin.php' ) ) );
		exit;
	}
}
