<?php
/**
 * Admin AJAX handlers for Food Requests management.
 *
 * @package FCC
 */

namespace FCC\Admin;

use FCC\Database;
use FCC\Import_Export;

defined( 'ABSPATH' ) || exit;

class Food_Requests {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_ajax_mark_request_done',   $this, 'ajax_mark_done' );
		$loader->add_action( 'wp_ajax_fcc_ajax_delete_request',      $this, 'ajax_delete' );
		$loader->add_action( 'wp_ajax_fcc_ajax_dismiss_request',     $this, 'ajax_dismiss' );
		$loader->add_action( 'wp_ajax_fcc_reqs_page',                $this, 'ajax_reqs_page' );
		$loader->add_action( 'admin_post_fcc_export_requests',       $this, 'handle_export_requests' );
	}

	public function handle_export_requests(): void {
		check_admin_referer( 'fcc_export_requests' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'food-calorie-calculator' ) );
		}

		$args   = $this->parse_export_args();
		$format = sanitize_key( $_POST['format'] ?? 'csv' );

		if ( 'xlsx' === $format ) {
			Import_Export::export_requests_xlsx( $args );
		} else {
			Import_Export::export_requests_csv( $args );
		}
	}

	/** @return array{optin_only:bool,days:int,status:string} */
	private function parse_export_args(): array {
		$optin_only = (bool) absint( $_POST['optin_only'] ?? 1 );

		$days_raw    = (int) ( $_POST['days'] ?? 0 );
		$days_custom = absint( $_POST['days_custom'] ?? 0 );
		$days        = ( -1 === $days_raw ) ? $days_custom : max( 0, $days_raw );

		$allowed_statuses = [ '', 'pending', 'done', 'dismissed' ];
		$status           = sanitize_key( $_POST['req_status'] ?? '' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = '';
		}

		return compact( 'optin_only', 'days', 'status' );
	}

	public function ajax_reqs_page(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$allowed_statuses = [ '', 'pending', 'done', 'dismissed' ];
		$status           = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = '';
		}
		$search   = sanitize_text_field( wp_unslash( $_POST['s'] ?? '' ) );
		$paged    = max( 1, absint( $_POST['paged'] ?? 1 ) );
		$per_page = 20;

		$total       = Database::count_food_requests( [ 'status' => $status, 'search' => $search ] );
		$total_pages = (int) ceil( $total / $per_page );
		$requests    = Database::get_food_requests( [
			'status'   => $status,
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $paged,
		] );

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-food-requests-table.php';
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	public function ajax_mark_done(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['request_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::update_request_status( $id, 'done' );
		wp_send_json_success( [ 'message' => __( 'Marked as done.', 'food-calorie-calculator' ) ] );
	}

	public function ajax_dismiss(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['request_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::update_request_status( $id, 'dismissed' );
		wp_send_json_success( [ 'message' => __( 'Dismissed.', 'food-calorie-calculator' ) ] );
	}

	public function ajax_delete(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['request_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::delete_food_request( $id );
		wp_send_json_success( [ 'message' => __( 'Deleted.', 'food-calorie-calculator' ) ] );
	}
}
