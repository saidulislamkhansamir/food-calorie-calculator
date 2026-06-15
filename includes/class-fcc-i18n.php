<?php
/**
 * Defines internationalisation functionality.
 *
 * @package FCC
 */

namespace FCC;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class I18n {

	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			FCC_TEXT_DOMAIN,
			false,
			dirname( FCC_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
