<?php
/**
 * White Label admin controller.
 *
 * Handles the White Label CRM page and all AJAX endpoints for license
 * management (save, delete, toggle status).
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class White_Label {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_wl_save_license',   $this, 'ajax_save_license' );
		$loader->add_action( 'wp_ajax_fcc_wl_delete_license', $this, 'ajax_delete_license' );
		$loader->add_action( 'wp_ajax_fcc_wl_toggle_license', $this, 'ajax_toggle_license' );
		$loader->add_action( 'wp_ajax_fcc_wl_renew_license',  $this, 'ajax_renew_license' );
	}

	public function page_white_label(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-white-label.php';
	}

	// -------------------------------------------------------------------------
	// AJAX handlers.
	// -------------------------------------------------------------------------

	public function ajax_save_license(): void {
		check_ajax_referer( 'fcc_wl_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$id   = absint( $_POST['id'] ?? 0 );
		$tier = sanitize_key( $_POST['tier'] ?? 'starter' );

		$tier_prices = [
			'starter'      => 99.00,
			'growth'       => 149.00,
			'professional' => 199.00,
			'enterprise'   => 299.00,
		];

		$raw_domains = sanitize_textarea_field( $_POST['allowed_domains'] ?? '' );
		$domains     = array_filter( array_map( 'trim', explode( "\n", $raw_domains ) ) );

		$data = [
			'client_name'     => sanitize_text_field( $_POST['client_name'] ?? '' ),
			'client_email'    => sanitize_email( $_POST['client_email'] ?? '' ),
			'client_url'      => esc_url_raw( $_POST['client_url'] ?? '' ),
			'business_type'   => sanitize_key( $_POST['business_type'] ?? 'other' ),
			'tier'            => $tier,
			'price_gbp'       => $tier_prices[ $tier ] ?? 99.00,
			'status'          => sanitize_key( $_POST['status'] ?? 'trial' ),
			'allowed_domains' => $domains,
			'brand_name'      => sanitize_text_field( $_POST['brand_name'] ?? '' ),
			'primary_colour'  => sanitize_hex_color( $_POST['primary_colour'] ?? '' ),
			'accent_colour'   => sanitize_hex_color( $_POST['accent_colour'] ?? '' ),
			'logo_url'        => esc_url_raw( $_POST['logo_url'] ?? '' ),
			'hide_powered_by' => isset( $_POST['hide_powered_by'] ) ? 1 : 0,
			'custom_css'      => wp_strip_all_tags( $_POST['custom_css'] ?? '' ),
			'notes'           => sanitize_textarea_field( $_POST['notes'] ?? '' ),
			'expires_at'      => sanitize_text_field( $_POST['expires_at'] ?? '' ),
		];

		if ( empty( $data['client_name'] ) || empty( $data['client_email'] ) ) {
			wp_send_json_error( [ 'message' => 'Client name and email are required.' ] );
		}

		if ( $id > 0 ) {
			$ok = \FCC\Database::update_wl_license( $id, $data );
			wp_send_json_success( [ 'updated' => $ok, 'id' => $id ] );
		} else {
			$new_id = \FCC\Database::insert_wl_license( $data );
			if ( ! $new_id ) {
				wp_send_json_error( [ 'message' => 'Could not save license. License key may already exist.' ] );
			}
			$license = \FCC\Database::get_wl_license( $new_id );
			wp_send_json_success( [ 'id' => $new_id, 'license_key' => $license['license_key'] ?? '' ] );
		}
	}

	public function ajax_delete_license(): void {
		check_ajax_referer( 'fcc_wl_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}

		$ok = \FCC\Database::delete_wl_license( $id );
		wp_send_json_success( [ 'deleted' => $ok ] );
	}

	public function ajax_toggle_license(): void {
		check_ajax_referer( 'fcc_wl_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$id     = absint( $_POST['id'] ?? 0 );
		$status = sanitize_key( $_POST['status'] ?? 'active' );

		if ( ! $id || ! in_array( $status, [ 'active', 'suspended', 'trial' ], true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid parameters' ] );
		}

		$ok = \FCC\Database::update_wl_license( $id, [ 'status' => $status ] );
		wp_send_json_success( [ 'updated' => $ok, 'status' => $status ] );
	}

	public function ajax_renew_license(): void {
		check_ajax_referer( 'fcc_wl_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$id         = absint( $_POST['id'] ?? 0 );
		$expires_at = sanitize_text_field( $_POST['expires_at'] ?? '' );

		if ( ! $id ) {
			wp_send_json_error( [ 'message' => 'Invalid ID' ] );
		}

		$ok = \FCC\Database::update_wl_license( $id, [
			'expires_at' => $expires_at,
			'renewed_at' => current_time( 'mysql' ),
			'status'     => 'active',
		] );

		wp_send_json_success( [ 'updated' => $ok ] );
	}
}
