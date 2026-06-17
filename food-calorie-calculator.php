<?php
/**
 * Plugin Name:       Food Calorie Calculator
 * Plugin URI:        https://foodcaloriecalculator.co.uk
 * Description:       A comprehensive UK food calorie calculator. Ships with 110+ foods, FSA traffic lights, Omega-3/caffeine tracking, meal builder, BMR/TDEE, CSV/Excel import-export, and a fully-featured admin control panel — no coding required.
 * Version:           1.13.1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            The Khan Digital
 * Author URI:        https://thekhandigital.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       food-calorie-calculator
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Constants.
// ---------------------------------------------------------------------------
define( 'FCC_VERSION',         '1.13.1' );
define( 'FCC_DB_VERSION',      '1.3' );
define( 'FCC_PLUGIN_FILE',     __FILE__ );
define( 'FCC_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'FCC_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'FCC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FCC_TEXT_DOMAIN',     'food-calorie-calculator' );

// ---------------------------------------------------------------------------
// Require all files (explicit includes — avoids autoloader complexity).
// ---------------------------------------------------------------------------
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-loader.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-database.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-settings.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-seed-data.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-import-export.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-rest-api.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-shortcode.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-block.php';

// Affiliates, Ads, and Supplements classes loaded unconditionally — their static helpers are called by the shortcode on the frontend.
require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-affiliates.php';
require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-ads.php';
require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-supplements.php';

// Admin files loaded only inside the admin context.
if ( is_admin() ) {
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-foods.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-categories.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-settings.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-import-export.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-food-requests.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-sponsored.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-analytics.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-email-hub.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-content-planner.php';
	require_once FCC_PLUGIN_DIR . 'admin/class-fcc-admin-white-label.php';
}

// ---------------------------------------------------------------------------
// Activation / deactivation hooks — loaded unconditionally.
// ---------------------------------------------------------------------------
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-activator.php';
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-deactivator.php';

register_activation_hook( FCC_PLUGIN_FILE, [ 'FCC\\Activator', 'activate' ] );
register_deactivation_hook( FCC_PLUGIN_FILE, [ 'FCC\\Deactivator', 'deactivate' ] );

// ---------------------------------------------------------------------------
// Bootstrap on plugins_loaded.
// ---------------------------------------------------------------------------
add_action( 'plugins_loaded', function (): void {
	load_plugin_textdomain(
		FCC_TEXT_DOMAIN,
		false,
		dirname( plugin_basename( FCC_PLUGIN_FILE ) ) . '/languages/'
	);

	$loader = new FCC\Loader();

	// REST API — registered on both admin and frontend (used by autocomplete).
	$rest = new FCC\Rest_Api();
	$rest->register( $loader );

	// Frontend shortcode + Gutenberg block.
	$shortcode = new FCC\Shortcode();
	$shortcode->register( $loader );

	$block = new FCC\FCC_Block();
	$block->register( $loader );

	// Affiliates, Ads, and Supplements AJAX handlers registered for admin + frontend.
	( new FCC\Admin\Affiliates() )->register( $loader );
	( new FCC\Admin\Ads() )->register( $loader );
	( new FCC\Admin\Supplements() )->register( $loader );

	// Admin.
	if ( is_admin() ) {
		( new FCC\Admin\Admin() )->register( $loader );
		( new FCC\Admin\Foods() )->register( $loader );
		( new FCC\Admin\Categories() )->register( $loader );
		( new FCC\Admin\Settings_Page() )->register( $loader );
		( new FCC\Admin\Import_Export() )->register( $loader );
		( new FCC\Admin\Food_Requests() )->register( $loader );
		( new FCC\Admin\Sponsored() )->register( $loader );
		( new FCC\Admin\Analytics() )->register( $loader );
		( new FCC\Admin\Email_Hub() )->register( $loader );
		( new FCC\Admin\Content_Planner() )->register( $loader );
		( new FCC\Admin\White_Label() )->register( $loader );
	}

	$loader->run();

	// Run data migrations for existing installations.
	FCC\Seed_Data::seed_v2();
	FCC\Seed_Data::seed_v3();
	FCC\Seed_Data::seed_v4();
	FCC\Seed_Data::seed_v5();
	FCC\Seed_Data::seed_v6();
	FCC\Seed_Data::seed_v7();
	FCC\Seed_Data::seed_v8();
	FCC\Seed_Data::seed_v9();
	FCC\Seed_Data::seed_v10();
	FCC\Seed_Data::seed_v11();
	FCC\Seed_Data::seed_v12();
	FCC\Seed_Data::seed_v13();
	FCC\Seed_Data::seed_v14();
	FCC\Seed_Data::seed_v15();
	FCC\Seed_Data::seed_v16();
	FCC\Seed_Data::seed_v17();
	FCC\Seed_Data::seed_v18();
} );
