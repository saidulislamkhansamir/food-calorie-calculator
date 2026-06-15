<?php
/**
 * Fired during plugin activation.
 *
 * Creates / upgrades tables via dbDelta, installs default settings,
 * and seeds the food database on first install.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Activator {

	public static function activate(): void {
		// Ensure all required classes are available during activation.
		require_once FCC_PLUGIN_DIR . 'includes/class-fcc-database.php';
		require_once FCC_PLUGIN_DIR . 'includes/class-fcc-settings.php';
		require_once FCC_PLUGIN_DIR . 'includes/class-fcc-seed-data.php';

		Database::create_tables();
		Settings::install_defaults();
		Seed_Data::seed();

		// Flush rewrite rules so the REST API namespace is available immediately.
		flush_rewrite_rules();
	}
}
