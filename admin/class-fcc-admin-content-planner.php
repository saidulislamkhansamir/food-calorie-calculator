<?php
/**
 * Admin: Content Planner — priority table combining missed searches + food requests.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Content_Planner {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_planner_dismiss', $this, 'ajax_dismiss' );
	}

	public function page_content_planner(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-content-planner.php';
	}

	/** AJAX: dismiss a missed-search entry from the planner. */
	public function ajax_dismiss(): void {
		check_ajax_referer( 'fcc_ajax_planner' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
		$ms_id = absint( $_POST['ms_id'] ?? 0 );
		if ( $ms_id > 0 ) {
			\FCC\Database::update_missed_search_status( $ms_id, 'dismissed' );
		}
		wp_send_json_success();
	}
}
