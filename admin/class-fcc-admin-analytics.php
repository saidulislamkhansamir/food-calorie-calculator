<?php
/**
 * Admin: Analytics page — tabbed BI dashboard covering search, monetization,
 * content intelligence, and audience metrics.
 *
 * @package FCC
 */

namespace FCC\Admin;

defined( 'ABSPATH' ) || exit;

class Analytics {

	public function register( \FCC\Loader $loader ): void {
		$loader->add_action( 'wp_ajax_fcc_analytics_charts',        $this, 'ajax_charts' );
		$loader->add_action( 'admin_post_fcc_export_subscribers',   $this, 'handle_export_subscribers' );
		$loader->add_action( 'admin_post_fcc_export_analytics_csv', $this, 'handle_export_csv' );
	}

	public function page_analytics(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'food-calorie-calculator' ) );
		}
		include FCC_PLUGIN_DIR . 'admin/partials/page-analytics.php';
	}

	/**
	 * AJAX: return chart + table datasets per tab.
	 *
	 * POST params: tab (overview|search|monetization|audience), range (7|30|90|0).
	 */
	public function ajax_charts(): void {
		check_ajax_referer( 'fcc_analytics_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
		}

		$allowed = [ 7, 30, 90, 0 ];
		$range   = absint( $_POST['range'] ?? 30 );
		if ( ! in_array( $range, $allowed, true ) ) {
			$range = 30;
		}

		$tab = sanitize_key( $_POST['tab'] ?? 'overview' );

		switch ( $tab ) {
			case 'search':
				$data = $this->get_search_tab_data( $range );
				break;
			case 'monetization':
				$data = $this->get_monetization_tab_data( $range );
				break;
			case 'content':
				$data = $this->get_content_tab_data();
				break;
			case 'audience':
				$data = $this->get_audience_tab_data();
				break;
			default:
				$data = $this->get_overview_tab_data( $range );
				break;
		}

		wp_send_json_success( $data );
	}

	// ─────────────────────────────────────────────────────────────────────
	// Tab data assemblers.
	// ─────────────────────────────────────────────────────────────────────

	private function get_overview_tab_data( int $range ): array {
		$chart_days  = $range > 0 ? $range : 365;
		$volume_rows = \FCC\Database::get_search_volume_by_day( $chart_days );
		$top_foods   = \FCC\Database::get_top_foods_by_hits( 10 );

		return [
			'volume' => [
				'labels' => array_column( $volume_rows, 'log_date' ),
				'data'   => array_map( 'intval', array_column( $volume_rows, 'count' ) ),
			],
			'foods' => [
				'labels' => array_column( $top_foods, 'name' ),
				'data'   => array_map( 'intval', array_column( $top_foods, 'search_count' ) ),
			],
		];
	}

	private function get_search_tab_data( int $range ): array {
		$chart_days = $range > 0 ? $range : 90;

		$success_trend = \FCC\Database::get_success_rate_by_day( $chart_days );
		$cat_breakdown = \FCC\Database::get_searches_by_category();
		$peak_days     = \FCC\Database::get_searches_by_day_of_week( $chart_days );
		$trending      = \FCC\Database::get_trending_searches( $chart_days, 15 );
		$by_hour       = \FCC\Database::get_searches_by_hour( $chart_days );

		$hour_labels = [];
		$hour_data   = [];
		$hour_map    = array_column( $by_hour, 'total', 'hour' );
		for ( $h = 0; $h < 24; $h++ ) {
			$hour_labels[] = sprintf( '%02d:00', $h );
			$hour_data[]   = (int) ( $hour_map[ $h ] ?? 0 );
		}

		return [
			'success_trend' => [
				'labels' => array_column( $success_trend, 'log_date' ),
				'data'   => array_map( 'floatval', array_column( $success_trend, 'success_rate' ) ),
			],
			'category_breakdown' => [
				'labels' => array_column( $cat_breakdown, 'category_name' ),
				'data'   => array_map( 'intval', array_column( $cat_breakdown, 'search_count' ) ),
			],
			'peak_days' => [
				'labels' => array_column( $peak_days, 'day_name' ),
				'data'   => array_map( 'intval', array_column( $peak_days, 'total' ) ),
			],
			'hourly' => [
				'labels' => $hour_labels,
				'data'   => $hour_data,
			],
			'trending' => $trending,
		];
	}

	private function get_monetization_tab_data( int $range ): array {
		$days          = $range > 0 ? $range : 30;
		$sponsor_trend = \FCC\Database::get_sponsor_clicks_by_day( $days );

		return [
			'sponsor_trend' => [
				'labels' => array_column( $sponsor_trend, 'click_date' ),
				'data'   => array_map( 'intval', array_column( $sponsor_trend, 'clicks' ) ),
			],
		];
	}

	private function get_content_tab_data(): array {
		$cat_coverage = \FCC\Database::get_category_coverage();
		return [
			'category_coverage' => [
				'labels' => array_column( $cat_coverage, 'category_name' ),
				'data'   => array_map( 'intval', array_column( $cat_coverage, 'food_count' ) ),
			],
		];
	}

	private function get_audience_tab_data(): array {
		$growth = \FCC\Database::get_subscriber_growth( 12 );

		return [
			'subscriber_growth' => [
				'labels'     => array_column( $growth, 'month' ),
				'data'       => array_map( 'intval', array_column( $growth, 'count' ) ),
				'cumulative' => array_map( 'intval', array_column( $growth, 'cumulative' ) ),
			],
		];
	}

	// ─────────────────────────────────────────────────────────────────────
	// CSV exports.
	// ─────────────────────────────────────────────────────────────────────

	public function handle_export_subscribers(): void {
		check_admin_referer( 'fcc_export_subscribers' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}
		\FCC\Import_Export::export_requests_csv( [ 'optin_only' => true ] );
	}

	public function handle_export_csv(): void {
		check_admin_referer( 'fcc_export_analytics_csv' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'food-calorie-calculator' ) );
		}

		$section = sanitize_key( $_GET['section'] ?? '' );
		$rows    = [];
		$headers = [];
		$fname   = 'fcc-analytics';

		switch ( $section ) {
			case 'content_gaps':
				$headers = [ 'Query', 'Searches', 'Last Searched', 'Priority Score' ];
				$rows    = \FCC\Database::get_scored_content_gaps( 100 );
				$fname   = 'fcc-content-gaps';
				break;

			case 'trending_searches':
				$headers = [ 'Query', 'Current Count', 'Previous Count', 'Growth %' ];
				$rows    = \FCC\Database::get_trending_searches( 30, 50 );
				$fname   = 'fcc-trending-searches';
				break;

			case 'sponsor_clicks':
				$headers = [ 'Food', 'Sponsor', 'Clicks' ];
				$rows    = \FCC\Database::get_sponsor_clicks_by_food( 0, 100 );
				$fname   = 'fcc-sponsor-clicks';
				break;

			case 'category_coverage':
				$headers = [ 'Category', 'Food Count' ];
				$rows    = \FCC\Database::get_category_coverage();
				$fname   = 'fcc-category-coverage';
				break;

			default:
				wp_die( esc_html__( 'Invalid export section.', 'food-calorie-calculator' ) );
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $fname . '-' . gmdate( 'Y-m-d' ) . '.csv"' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, $headers );

		foreach ( $rows as $r ) {
			fputcsv( $out, array_values( $r ) );
		}

		fclose( $out );
		exit;
	}
}
