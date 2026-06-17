<?php
/**
 * Admin: Sponsored Foods — manage paid listings, view click stats, toggle active.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Sponsored {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_sponsor_toggle', $this, 'ajax_toggle' );
	}

	public function page_sponsored(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-sponsored.php';
	}

	/** AJAX: toggle sponsor_active on/off. */
	public function ajax_toggle(): void {
		check_ajax_referer( 'fcc_ajax_sponsor' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
		$food_id = absint( $_POST['food_id'] ?? 0 );
		$active  = (bool) ( $_POST['active'] ?? 0 );
		if ( $food_id > 0 ) {
			\FCC\Database::toggle_sponsor_active( $food_id, $active );
		}
		wp_send_json_success( [ 'active' => $active ] );
	}
}
