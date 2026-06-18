<?php
/**
 * Admin: Email Marketing Hub — subscriber management with filters, search, sorting.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Email_Hub {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_email_hub_page',   $this, 'ajax_page' );
		$loader->add_action( 'wp_ajax_fcc_email_hub_delete', $this, 'ajax_delete' );
	}

	public function page_email_hub(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub.php';
	}

	public function ajax_page(): void {
		check_ajax_referer( 'fcc_ajax_email_hub' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Unauthorized', 403 ); }

		$per_page = 20;
		$args = [
			'food_name' => sanitize_text_field( $_POST['food_filter'] ?? '' ),
			'search'    => sanitize_text_field( $_POST['search'] ?? '' ),
			'status'    => sanitize_key( $_POST['status'] ?? '' ),
			'orderby'   => sanitize_key( $_POST['orderby'] ?? 'created_at' ),
			'order'     => sanitize_key( $_POST['order'] ?? 'DESC' ),
			'per_page'  => $per_page,
			'page'      => max( 1, absint( $_POST['paged'] ?? 1 ) ),
		];

		$food_filter  = $args['food_name'];
		$subscribers  = \FCC\Database::get_email_subscribers( $args );
		$total        = \FCC\Database::count_email_subscribers( $args );
		$total_pages  = (int) ceil( $total / $per_page );
		$paged        = $args['page'];

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub-table.php';
		wp_send_json_success( [ 'html' => ob_get_clean() ] );
	}

	public function ajax_delete(): void {
		check_ajax_referer( 'fcc_ajax_email_hub' );
		if ( ! current_user_can( 'manage_options' ) ) { wp_send_json_error( 'Unauthorized', 403 ); }

		$id = absint( $_POST['subscriber_id'] ?? 0 );
		if ( $id ) {
			\FCC\Database::delete_food_request( $id );
		}
		wp_send_json_success();
	}
}
