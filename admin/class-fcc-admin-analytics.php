<?php
/**
 * Admin: Analytics page — search trends, food popularity, content gaps, email subscribers.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Analytics {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_analytics_charts',    $this, 'ajax_charts' );
		$loader->add_action( 'admin_post_fcc_export_subscribers', $this, 'handle_export_subscribers' );
	}

	public function page_analytics(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-analytics.php';
	}

	/**
	 * AJAX: return both chart datasets (volume + top foods) as JSON.
	 * Accepts POST param 'range' (7|30|90|0 for all-time).
	 */
	public function ajax_charts(): void {
		check_ajax_referer( 'fcc_analytics_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$allowed = [ 7, 30, 90, 0 ];
		$range   = absint( $_POST['range'] ?? 30 );
		if ( ! in_array( $range, $allowed, true ) ) {
			$range = 30;
		}

		$chart_days  = $range > 0 ? $range : 365;
		$volume_rows = \FCC\Database::get_search_volume_by_day( $chart_days );
		$top_foods   = \FCC\Database::get_top_foods_by_hits( 10 );

		wp_send_json_success( [
			'volume' => [
				'labels' => array_column( $volume_rows, 'log_date' ),
				'data'   => array_map( 'intval', array_column( $volume_rows, 'count' ) ),
			],
			'foods' => [
				'labels' => array_column( $top_foods, 'name' ),
				'data'   => array_map( 'intval', array_column( $top_foods, 'search_count' ) ),
			],
		] );
	}

	/** Export opted-in subscribers as CSV. */
	public function handle_export_subscribers(): void {
		check_admin_referer( 'fcc_export_subscribers' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		\FCC\Import_Export::export_requests_csv( [ 'optin_only' => true ] );
	}
}
