<?php
/**
 * Auto-Publisher: drip-publishes food pages on a daily WP Cron schedule.
 *
 * Separates page visibility (page_published) from calculator availability
 * (is_active), so foods always appear in the calculator even before their
 * individual pages go live.
 *
 * @package FCC
 */

namespace FCC;

defined( 'ABSPATH' ) || exit;

class Auto_Publisher {

	const CRON_HOOK  = 'fcc_auto_publish_batch';
	const LOG_OPTION = 'fcc_auto_publisher_log';

	public function register(): void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_batch' ] );
		add_action( 'wp_ajax_fcc_ap_run_now',     [ __CLASS__, 'ajax_run_now' ] );
		add_action( 'wp_ajax_fcc_ap_reset_all',   [ __CLASS__, 'ajax_reset_all' ] );
		add_action( 'wp_ajax_fcc_ap_publish_all', [ __CLASS__, 'ajax_publish_all' ] );
	}

	// -------------------------------------------------------------------------
	// Scheduling
	// -------------------------------------------------------------------------

	public static function maybe_reschedule(): void {
		$sm      = Settings::get_section( 'auto_publisher' );
		$enabled = ! empty( $sm['enabled'] );

		if ( ! $enabled ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
			return;
		}

		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return; // Already scheduled.
		}

		$hour = max( 0, min( 23, (int) ( $sm['run_hour'] ?? 8 ) ) );
		$tz   = wp_timezone();
		$now  = new \DateTime( 'now', $tz );
		$run  = new \DateTime( 'today ' . sprintf( '%02d:00:00', $hour ), $tz );
		if ( $run <= $now ) {
			$run->modify( '+1 day' );
		}
		wp_schedule_event( $run->getTimestamp(), 'daily', self::CRON_HOOK );
	}

	// -------------------------------------------------------------------------
	// Batch runner
	// -------------------------------------------------------------------------

	public static function run_batch(): void {
		$sm = Settings::get_section( 'auto_publisher' );
		if ( empty( $sm['enabled'] ) ) {
			return;
		}

		$min   = max( 1, (int) ( $sm['min_per_day'] ?? 7 ) );
		$max   = max( $min, (int) ( $sm['max_per_day'] ?? 21 ) );
		$count = rand( $min, $max );
		$order = $sm['publish_order'] ?? 'random';

		$published = Database::publish_food_batch( $count, $order );

		// Auto-disable when all foods are published.
		if ( 0 === $published ) {
			$all                           = Settings::get_all();
			$all['auto_publisher']['enabled'] = false;
			Settings::save( $all );
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}

		// Append to rolling 30-day log.
		$log = get_option( self::LOG_OPTION, [] );
		array_unshift( $log, [ 'date' => gmdate( 'Y-m-d' ), 'count' => $published ] );
		update_option( self::LOG_OPTION, array_slice( $log, 0, 30 ), false );
	}

	// -------------------------------------------------------------------------
	// Stats
	// -------------------------------------------------------------------------

	public static function get_stats(): array {
		$counts    = Database::get_published_food_counts();
		$sm        = Settings::get_section( 'auto_publisher' );
		$min       = (int) ( $sm['min_per_day'] ?? 7 );
		$max_day   = (int) ( $sm['max_per_day'] ?? 21 );
		$avg       = (int) round( ( $min + $max_day ) / 2 );
		$remaining = $counts['unpublished'];
		$est_days  = ( $avg > 0 && $remaining > 0 ) ? (int) ceil( $remaining / $avg ) : 0;
		$next      = wp_next_scheduled( self::CRON_HOOK );

		return [
			'published'   => $counts['published'],
			'unpublished' => $counts['unpublished'],
			'total'       => $counts['total'],
			'next_run'    => $next ? gmdate( 'Y-m-d H:i', $next ) . ' UTC' : 'Not scheduled',
			'est_days'    => $est_days,
		];
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	public static function ajax_run_now(): void {
		check_ajax_referer( 'fcc_save_settings', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.', 403 );
		}
		self::run_batch();
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_reset_all(): void {
		check_ajax_referer( 'fcc_save_settings', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.', 403 );
		}
		Database::bulk_set_page_published( 0 );
		delete_option( self::LOG_OPTION );
		wp_send_json_success( self::get_stats() );
	}

	public static function ajax_publish_all(): void {
		check_ajax_referer( 'fcc_save_settings', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied.', 403 );
		}
		Database::bulk_set_page_published( 1 );
		wp_send_json_success( self::get_stats() );
	}
}
