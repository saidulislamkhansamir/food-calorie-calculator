<?php
/**
 * Admin: Email Marketing Hub — paginated opted-in subscriber list with food filter.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Email_Hub {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_email_hub_page', $this, 'ajax_page' );
	}

	public function page_email_hub(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub.php';
	}

	/** AJAX: reload subscriber table. */
	public function ajax_page(): void {
		check_ajax_referer( 'fcc_ajax_email_hub' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}

		$food_name = sanitize_text_field( $_POST['food_filter'] ?? '' );
		$paged     = max( 1, absint( $_POST['paged'] ?? 1 ) );
		$per_page  = 20;

		$args = [
			'food_name' => $food_name,
			'per_page'  => $per_page,
			'page'      => $paged,
		];

		$subscribers = \FCC\Database::get_email_subscribers( $args );
		$total       = \FCC\Database::count_email_subscribers( $args );
		$total_pages = (int) ceil( $total / $per_page );

		ob_start();
		include FCC_PLUGIN_DIR . 'admin/partials/page-email-hub-table.php';
		wp_send_json_success( [ 'html' => ob_get_clean() ] );
	}
}
