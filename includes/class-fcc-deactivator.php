<?php
/**
 * Fired during plugin deactivation.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
