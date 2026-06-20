<?php
/**
 * Admin AJAX handlers for Food Requests and Missed Searches management.
 *
 * @package FCC
 */

namespace FCC\Admin;

use FCC\Database;
use FCC\Import_Export;

defined( 'ABSPATH' ) || exit;

class Food_Requests {

	public function register( \FCC\Loader $loader ): void {
		// Food Requests (grouped).
		$loader->add_action( 'wp_ajax_fcc_reqs_page',               $this, 'ajax_reqs_page' );
		$loader->add_action( 'wp_ajax_fcc_ajax_mark_group_added',   $this, 'ajax_mark_group_added' );
		$loader->add_action( 'wp_ajax_fcc_ajax_dismiss_group',      $this, 'ajax_dismiss_group' );

		// Legacy individual-row handlers (kept for backwards-compat / direct use).
		$loader->add_action( 'wp_ajax_fcc_ajax_mark_request_done',  $this, 'ajax_mark_done' );
		$loader->add_action( 'wp_ajax_fcc_ajax_delete_request',     $this, 'ajax_delete' );
		$loader->add_action( 'wp_ajax_fcc_ajax_dismiss_request',    $this, 'ajax_dismiss' );

		// Missed Searches.
		$loader->add_action( 'wp_ajax_fcc_ms_page',                 $this, 'ajax_ms_page' );
		$loader->add_action( 'wp_ajax_fcc_ajax_mark_ms_added',      $this, 'ajax_mark_ms_added' );
		$loader->add_action( 'wp_ajax_fcc_ajax_dismiss_ms',         $this, 'ajax_dismiss_ms' );
		$loader->add_action( 'wp_ajax_fcc_ajax_delete_ms',          $this, 'ajax_delete_ms' );
		$loader->add_action( 'wp_ajax_fcc_ajax_bulk_delete_dismissed_ms',   $this, 'ajax_bulk_delete_dismissed_ms' );
		$loader->add_action( 'wp_ajax_fcc_ajax_bulk_delete_dismissed_reqs', $this, 'ajax_bulk_delete_dismissed_reqs' );

		// Export.
		$loader->add_action( 'admin_post_fcc_export_requests',      $this, 'handle_export_requests' );
	}

	// -------------------------------------------------------------------------
	// Food Requests — grouped AJAX handlers.
	// -------------------------------------------------------------------------

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

		$sort    = $this->parse_sort( $_POST['sort'] ?? '', [ 'most_requested', 'latest', 'oldest' ], 'most_requested' );
		$paged   = max( 1, absint( $_POST['paged'] ?? 1 ) );
		$per_page = 20;

		[ $days, $date_from, $date_to ] = $this->parse_period( $_POST );

		$args = compact( 'status', 'sort', 'days', 'date_from', 'date_to', 'per_page' );
		$args['page'] = $paged;

		$total       = Database::count_food_requests_grouped( $args );
		$total_pages = (int) ceil( $total / $per_page );
		$requests    = Database::get_food_requests_grouped( $args );

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-food-requests-table.php';
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	public function ajax_mark_group_added(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$food_name = sanitize_text_field( wp_unslash( $_POST['food_name'] ?? '' ) );
		if ( ! $food_name ) {
			wp_send_json_error( [ 'message' => 'Invalid food name' ] );
		}
		Database::update_requests_status_by_food( $food_name, 'done' );
		wp_send_json_success( [ 'message' => __( 'Marked as added.', 'food-calorie-calculator' ) ] );
	}

	public function ajax_dismiss_group(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$food_name = sanitize_text_field( wp_unslash( $_POST['food_name'] ?? '' ) );
		if ( ! $food_name ) {
			wp_send_json_error( [ 'message' => 'Invalid food name' ] );
		}
		Database::update_requests_status_by_food( $food_name, 'dismissed' );
		wp_send_json_success( [ 'message' => __( 'Dismissed.', 'food-calorie-calculator' ) ] );
	}

	// -------------------------------------------------------------------------
	// Food Requests — legacy individual handlers.
	// -------------------------------------------------------------------------

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
		wp_send_json_success( [ 'message' => __( 'Marked as added.', 'food-calorie-calculator' ) ] );
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

	// -------------------------------------------------------------------------
	// Missed Searches AJAX handlers.
	// -------------------------------------------------------------------------

	public function ajax_ms_page(): void {
		check_ajax_referer( 'fcc_ajax_ms' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$allowed_statuses = [ '', 'active', 'done', 'dismissed' ];
		$status           = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = 'active';
		}

		$sort     = $this->parse_sort( $_POST['sort'] ?? '', [ 'most_searched', 'latest', 'oldest' ], 'most_searched' );
		$paged    = max( 1, absint( $_POST['paged'] ?? 1 ) );
		$per_page = 20;

		[ $days, $date_from, $date_to ] = $this->parse_period( $_POST );

		$args = compact( 'status', 'sort', 'days', 'date_from', 'date_to', 'per_page' );
		$args['page'] = $paged;

		$total       = Database::count_missed_searches( $args );
		$total_pages = (int) ceil( $total / $per_page );
		$searches    = Database::get_missed_searches( $args );

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-missed-searches-table.php';
		$html = ob_get_clean();

		wp_send_json_success( [ 'html' => $html ] );
	}

	public function ajax_mark_ms_added(): void {
		check_ajax_referer( 'fcc_ajax_ms' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['ms_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::update_missed_search_status( $id, 'done' );
		wp_send_json_success( [ 'message' => __( 'Marked as added.', 'food-calorie-calculator' ) ] );
	}

	public function ajax_dismiss_ms(): void {
		check_ajax_referer( 'fcc_ajax_ms' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['ms_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::update_missed_search_status( $id, 'dismissed' );
		wp_send_json_success( [ 'message' => __( 'Dismissed.', 'food-calorie-calculator' ) ] );
	}

	public function ajax_delete_ms(): void {
		check_ajax_referer( 'fcc_ajax_ms' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$id = absint( $_POST['ms_id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}
		Database::delete_missed_search( $id );
		wp_send_json_success( [ 'message' => __( 'Deleted.', 'food-calorie-calculator' ) ] );
	}

	// -------------------------------------------------------------------------
	// Bulk delete dismissed.
	// -------------------------------------------------------------------------

	public function ajax_bulk_delete_dismissed_ms(): void {
		check_ajax_referer( 'fcc_ajax_ms' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$deleted = Database::delete_missed_searches_by_status( 'dismissed' );
		wp_send_json_success( [ 'message' => sprintf( __( '%d dismissed searches deleted.', 'food-calorie-calculator' ), $deleted ), 'deleted' => $deleted ] );
	}

	public function ajax_bulk_delete_dismissed_reqs(): void {
		check_ajax_referer( 'fcc_ajax_reqs' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}
		$deleted = Database::delete_requests_by_status( 'dismissed' );
		wp_send_json_success( [ 'message' => sprintf( __( '%d dismissed requests deleted.', 'food-calorie-calculator' ), $deleted ), 'deleted' => $deleted ] );
	}

	// -------------------------------------------------------------------------
	// Export handler.
	// -------------------------------------------------------------------------

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

	// -------------------------------------------------------------------------
	// Helpers.
	// -------------------------------------------------------------------------

	private function parse_sort( $raw, array $allowed, string $default ): string {
		$val = sanitize_key( wp_unslash( $raw ) );
		return in_array( $val, $allowed, true ) ? $val : $default;
	}

	/** @return array{0:int,1:string,2:string} [days, date_from, date_to] */
	private function parse_period( array $post ): array {
		$days_raw  = (int) ( $post['period'] ?? 0 );
		$date_from = '';
		$date_to   = '';
		$days      = 0;

		if ( -1 === $days_raw ) {
			$df = sanitize_text_field( wp_unslash( $post['date_from'] ?? '' ) );
			$dt = sanitize_text_field( wp_unslash( $post['date_to'] ?? '' ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $df ) ) $date_from = $df;
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dt ) ) $date_to   = $dt;
		} elseif ( $days_raw > 0 ) {
			$days = $days_raw;
		}

		return [ $days, $date_from, $date_to ];
	}

	/** @return array{optin_only:bool,days:int,date_from:string,date_to:string,status:string} */
	private function parse_export_args(): array {
		$optin_only = (bool) absint( $_POST['optin_only'] ?? 1 );

		$days_raw  = (int) ( $_POST['days'] ?? 0 );
		$date_from = '';
		$date_to   = '';
		$days      = 0;

		if ( -1 === $days_raw ) {
			$df = sanitize_text_field( wp_unslash( $_POST['date_from'] ?? '' ) );
			$dt = sanitize_text_field( wp_unslash( $_POST['date_to'] ?? '' ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $df ) ) $date_from = $df;
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dt ) ) $date_to   = $dt;
		} else {
			$days = max( 0, $days_raw );
		}

		$allowed_statuses = [ '', 'pending', 'done', 'dismissed' ];
		$status           = sanitize_key( $_POST['req_status'] ?? '' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			$status = '';
		}

		return compact( 'optin_only', 'days', 'date_from', 'date_to', 'status' );
	}
}
