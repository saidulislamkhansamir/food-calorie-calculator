<?php
/**
 * Admin AJAX handlers for Food Requests management.
 *
 * @package FCC
 */

namespace FCC\Admin;

use FCC\Database;

defined( 'ABSPATH' ) || exit;

class Food_Requests {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_ajax_mark_request_done',   $this, 'ajax_mark_done' );
		$loader->add_action( 'wp_ajax_fcc_ajax_delete_request',      $this, 'ajax_delete' );
		$loader->add_action( 'wp_ajax_fcc_ajax_dismiss_request',     $this, 'ajax_dismiss' );
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
