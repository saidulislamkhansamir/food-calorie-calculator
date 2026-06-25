<?php
/**
 * Plugin Name:       Food Calorie Calculator
 * Plugin URI:        https://foodcaloriecalculator.co.uk
 * Description:       A comprehensive UK food calorie calculator. Ships with 5,200+ foods from 190+ countries, FSA traffic lights, SVG macro rings, Omega-3/caffeine/micronutrient tracking, meal builder with templates, BMR/TDEE, promotion suite, analytics dashboard, and a fully-featured admin control panel — no coding required.
 * Version:           14.0.8
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
define( 'FCC_VERSION',         '14.0.8' );
define( 'FCC_DB_VERSION',      '1.5' );
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
require_once FCC_PLUGIN_DIR . 'includes/class-fcc-food-pages.php';
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

	// Individual food pages (/food/{slug}/).
	$food_pages = new FCC\Food_Pages();
	$food_pages->register( $loader );
	add_action( 'init', [ FCC\Food_Pages::class, 'register_sitemap' ], 20 );

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

	// Migrate pinned_foods from general to pinned section (one-time).
	FCC\Settings::migrate_pinned_to_section();

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
	FCC\Seed_Data::seed_v19();
	FCC\Seed_Data::seed_v20();
	FCC\Seed_Data::seed_v21();
	FCC\Seed_Data::seed_v22();
	FCC\Seed_Data::seed_v23();
	FCC\Seed_Data::seed_v24();
	FCC\Seed_Data::seed_v25();
	FCC\Seed_Data::seed_v26();
	FCC\Seed_Data::seed_v27();
	FCC\Seed_Data::seed_v28();
	FCC\Seed_Data::seed_v29();
	FCC\Seed_Data::seed_v30();
	FCC\Seed_Data::seed_v31();
	FCC\Seed_Data::seed_v32();
	FCC\Seed_Data::seed_v33();
	FCC\Seed_Data::seed_v34();
	FCC\Seed_Data::seed_v35();
	FCC\Seed_Data::seed_v36();
	FCC\Seed_Data::seed_v37();
	FCC\Seed_Data::seed_v38();
	FCC\Seed_Data::seed_v39();
	FCC\Seed_Data::seed_v40();
	FCC\Seed_Data::seed_v41();
	FCC\Seed_Data::seed_v42();
	FCC\Seed_Data::seed_v43();
	FCC\Seed_Data::seed_v44();
	FCC\Seed_Data::seed_v45();
	FCC\Seed_Data::seed_v46();
	FCC\Seed_Data::seed_v47();
	FCC\Seed_Data::seed_v48();
	FCC\Seed_Data::seed_v49();
	FCC\Seed_Data::seed_v50();
	FCC\Seed_Data::seed_v51();
	FCC\Seed_Data::seed_v52();
	FCC\Seed_Data::seed_v53();
	FCC\Seed_Data::seed_v54();
	FCC\Seed_Data::seed_v55();
	FCC\Seed_Data::seed_v56();
	FCC\Seed_Data::seed_v57();
	FCC\Seed_Data::seed_v58();
	FCC\Seed_Data::seed_v59();
	FCC\Seed_Data::seed_v60();
	FCC\Seed_Data::seed_v61();
	FCC\Seed_Data::seed_v62();
	FCC\Seed_Data::seed_v63();
	FCC\Seed_Data::seed_v64();
	FCC\Seed_Data::seed_v65();
	FCC\Seed_Data::seed_v66();
	FCC\Seed_Data::seed_v67();
	FCC\Seed_Data::seed_v68();
	FCC\Seed_Data::seed_v69();
	FCC\Seed_Data::seed_v70();
	FCC\Seed_Data::seed_v71();
	FCC\Seed_Data::seed_v72();
	FCC\Seed_Data::seed_v73();
	FCC\Seed_Data::seed_v74();
	FCC\Seed_Data::seed_v75();
	FCC\Seed_Data::seed_v76();
	FCC\Seed_Data::seed_v77();
	FCC\Seed_Data::seed_v78();
	FCC\Seed_Data::seed_v79();
	FCC\Seed_Data::seed_v80();
	FCC\Seed_Data::seed_v81();
	FCC\Seed_Data::seed_v82();
	FCC\Seed_Data::seed_v83();
	FCC\Seed_Data::seed_v84();
	FCC\Seed_Data::seed_v85();
	FCC\Seed_Data::seed_v86();
	FCC\Seed_Data::seed_v87();
	FCC\Seed_Data::seed_v88();
	FCC\Seed_Data::seed_v89();
	FCC\Seed_Data::seed_v90();
	FCC\Seed_Data::seed_v91();
	FCC\Seed_Data::seed_v92();
	FCC\Seed_Data::seed_v93();
	FCC\Seed_Data::seed_v94();
	FCC\Seed_Data::seed_v95();
	FCC\Seed_Data::seed_v96();
	FCC\Seed_Data::seed_v97();
	FCC\Seed_Data::seed_v98();
	FCC\Seed_Data::seed_v99();
	FCC\Seed_Data::seed_v100();
	FCC\Seed_Data::seed_v101();
	FCC\Seed_Data::seed_v102();
	FCC\Seed_Data::seed_v103();
	FCC\Seed_Data::seed_v104();
	FCC\Seed_Data::seed_v105();
	FCC\Seed_Data::seed_v106();
	FCC\Seed_Data::seed_v107();
} );
