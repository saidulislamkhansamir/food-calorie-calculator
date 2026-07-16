<?php
/**
 * PWA install-funnel tracking — logs "Install App" button clicks and
 * completed installs (public AJAX, visitors fire this). Uninstalls are not
 * tracked: no browser exposes an event for that to web pages.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Pwa_Tracker {

	public function register( Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_pwa_track',        $this, 'ajax_track' );
		$loader->add_action( 'wp_ajax_nopriv_fcc_pwa_track', $this, 'ajax_track' );
	}

	/** Track a PWA install-funnel event (public AJAX — visitors fire this). */
	public function ajax_track(): void {
		check_ajax_referer( 'fcc_pwa_track', 'nonce' );

		$event  = sanitize_key( wp_unslash( $_POST['event'] ?? '' ) );
		$device = sanitize_key( wp_unslash( $_POST['device'] ?? '' ) );

		if ( ! in_array( $event, [ 'install_click', 'installed' ], true ) ) {
			wp_send_json_error();
		}

		Database::log_pwa_event( $event, $device );
		wp_send_json_success();
	}
}
