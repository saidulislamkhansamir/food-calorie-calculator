<?php
/**
 * Core admin class — registers menus, enqueues admin assets, adds plugin
 * action links and handles the dashboard overview page.
 *
 * All admin pages gate access with current_user_can('manage_options').
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'admin_menu',            $this, 'register_menus' );
		$loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_assets', 10, 1 );
		$loader->add_filter(
			'plugin_action_links_' . FCC_PLUGIN_BASENAME,
			$this,
			'plugin_action_links',
			10,
			1
		);
	}

	// -------------------------------------------------------------------------
	// Menu registration.
	// -------------------------------------------------------------------------

	public function register_menus(): void {
		$capability = 'manage_options';
		$icon       = 'dashicons-carrot';

		add_menu_page(
			__( 'Food Calorie Calculator', 'food-calorie-calculator' ),
			__( 'Food Calculator', 'food-calorie-calculator' ),
			$capability,
			'fcc-dashboard',
			[ $this, 'page_dashboard' ],
			$icon,
			56
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Dashboard', 'food-calorie-calculator' ),
			__( 'Dashboard', 'food-calorie-calculator' ),
			$capability,
			'fcc-dashboard',
			[ $this, 'page_dashboard' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Foods', 'food-calorie-calculator' ),
			__( 'Foods', 'food-calorie-calculator' ),
			$capability,
			'fcc-foods',
			[ $this, 'page_foods' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Categories', 'food-calorie-calculator' ),
			__( 'Categories', 'food-calorie-calculator' ),
			$capability,
			'fcc-categories',
			[ $this, 'page_categories' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Import / Export', 'food-calorie-calculator' ),
			__( 'Import / Export', 'food-calorie-calculator' ),
			$capability,
			'fcc-import-export',
			[ $this, 'page_import_export' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Settings', 'food-calorie-calculator' ),
			__( 'Settings', 'food-calorie-calculator' ),
			$capability,
			'fcc-settings',
			[ $this, 'page_settings' ]
		);

		$pending = \FCC\Database::count_pending_requests();
		$req_label = __( 'Food Requests', 'food-calorie-calculator' );
		if ( $pending > 0 ) {
			$req_label .= ' <span class="awaiting-mod count-' . $pending . '"><span class="pending-count">' . $pending . '</span></span>';
		}
		add_submenu_page(
			'fcc-dashboard',
			__( 'Food Requests', 'food-calorie-calculator' ),
			$req_label,
			$capability,
			'fcc-food-requests',
			[ $this, 'page_food_requests' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Sponsored', 'food-calorie-calculator' ),
			__( 'Sponsored', 'food-calorie-calculator' ),
			$capability,
			'fcc-sponsored',
			[ $this, 'page_sponsored' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Analytics', 'food-calorie-calculator' ),
			__( 'Analytics', 'food-calorie-calculator' ),
			$capability,
			'fcc-analytics',
			[ $this, 'page_analytics' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Email Hub', 'food-calorie-calculator' ),
			__( 'Email Hub', 'food-calorie-calculator' ),
			$capability,
			'fcc-email-hub',
			[ $this, 'page_email_hub' ]
		);

		add_submenu_page(
			'fcc-dashboard',
			__( 'Content Planner', 'food-calorie-calculator' ),
			__( 'Content Planner', 'food-calorie-calculator' ),
			$capability,
			'fcc-content-planner',
			[ $this, 'page_content_planner' ]
		);
	}

	// -------------------------------------------------------------------------
	// Page callbacks — delegate to partials.
	// -------------------------------------------------------------------------

	public function page_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-dashboard.php';
	}

	public function page_foods(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-foods-list.php';
	}

	public function page_categories(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-categories.php';
	}

	public function page_import_export(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-import-export.php';
	}

	public function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-settings.php';
	}

	public function page_food_requests(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-food-requests.php';
	}

	public function page_sponsored(): void {
		( new Sponsored() )->page_sponsored();
	}

	public function page_analytics(): void {
		( new Analytics() )->page_analytics();
	}

	public function page_email_hub(): void {
		( new Email_Hub() )->page_email_hub();
	}

	public function page_content_planner(): void {
		( new Content_Planner() )->page_content_planner();
	}

	// -------------------------------------------------------------------------
	// Assets.
	// -------------------------------------------------------------------------

	/**
	 * Enqueue admin CSS/JS only on our own pages.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ): void {
		// All our pages contain 'fcc-' in the hook.
		if ( false === strpos( $hook, 'fcc-' ) && false === strpos( $hook, 'food-calculator' ) ) {
			return;
		}

		$ver = FCC_VERSION;

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style(
			'fcc-admin',
			FCC_PLUGIN_URL . 'assets/css/fcc-admin.css',
			[ 'wp-color-picker' ],
			$ver
		);

		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'fcc-admin',
			FCC_PLUGIN_URL . 'assets/js/fcc-admin.js',
			[ 'jquery', 'wp-color-picker' ],
			$ver,
			true
		);

		// Enqueue WP media library on the food edit/add page for the logo picker.
		if ( false !== strpos( $hook, 'fcc-foods' ) ) {
			wp_enqueue_media();
		}

		if ( false !== strpos( $hook, 'fcc-analytics' ) ) {
			wp_register_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js', [], '4.4.4', true );
			wp_enqueue_script( 'chartjs' );
			wp_enqueue_script( 'fcc-analytics', FCC_PLUGIN_URL . 'assets/js/fcc-analytics.js', [ 'chartjs' ], $ver, true );
			wp_localize_script( 'fcc-analytics', 'fccAnalytics', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'fcc_analytics_nonce' ),
				'range'   => absint( $_GET['range'] ?? 30 ),
			] );
		}

		wp_localize_script(
			'fcc-admin',
			'fccAdmin',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'fcc_admin_nonce' ),
				'foodsNonce' => wp_create_nonce( 'fcc_foods_page' ),
				'catsNonce'  => wp_create_nonce( 'fcc_ajax_cats' ),
				'reqsNonce'  => wp_create_nonce( 'fcc_ajax_reqs' ),
				'msNonce'    => wp_create_nonce( 'fcc_ajax_ms' ),
				'addFoodUrl' => admin_url( 'admin.php?page=fcc-foods' ),
				'i18n'       => [
					'confirmDelete'     => __( 'Are you sure you want to delete this food?', 'food-calorie-calculator' ),
					'confirmDeleteReq'  => __( 'Are you sure you want to delete this request?', 'food-calorie-calculator' ),
					'confirmBulkDelete' => __( 'Are you sure you want to delete the selected foods?', 'food-calorie-calculator' ),
					'selectItems'       => __( 'Please select at least one item.', 'food-calorie-calculator' ),
					'saved'             => __( 'Saved!', 'food-calorie-calculator' ),
					'error'             => __( 'An error occurred.', 'food-calorie-calculator' ),
					'editing'           => __( 'Editing', 'food-calorie-calculator' ),
					'update'            => __( 'Update', 'food-calorie-calculator' ),
					'addCategory'       => __( 'Add New Category', 'food-calorie-calculator' ),
					'addCategoryBtn'    => __( 'Add Category', 'food-calorie-calculator' ),
					'importing'         => __( 'Importing…', 'food-calorie-calculator' ),
				],
			]
		);
	}

	// -------------------------------------------------------------------------
	// Plugin action links.
	// -------------------------------------------------------------------------

	/**
	 * Add "Settings" and "Foods" links on the Plugins list page.
	 *
	 * @param array<int,string> $links
	 * @return array<int,string>
	 */
	public function plugin_action_links( array $links ): array {
		$extra = [
			'<a href="' . esc_url( admin_url( 'admin.php?page=fcc-settings' ) ) . '">' . esc_html__( 'Settings', 'food-calorie-calculator' ) . '</a>',
			'<a href="' . esc_url( admin_url( 'admin.php?page=fcc-foods' ) ) . '">' . esc_html__( 'Foods', 'food-calorie-calculator' ) . '</a>',
		];
		return array_merge( $extra, $links );
	}
}
