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
		// Legacy single-hook (backward compat for any existing scheduled event).
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_batch' ] );
		// Per-slot hooks for multi-batch mode.
		for ( $i = 0; $i < 4; $i++ ) {
			add_action( self::batch_hook( $i ), static function () use ( $i ): void {
				self::run_batch( $i );
			} );
		}
		add_action( 'wp_ajax_fcc_ap_run_now',     [ __CLASS__, 'ajax_run_now' ] );
		add_action( 'wp_ajax_fcc_ap_reset_all',   [ __CLASS__, 'ajax_reset_all' ] );
		add_action( 'wp_ajax_fcc_ap_publish_all', [ __CLASS__, 'ajax_publish_all' ] );
	}

	// -------------------------------------------------------------------------
	// Scheduling
	// -------------------------------------------------------------------------

	private static function batch_hook( int $slot ): string {
		return self::CRON_HOOK . '_' . $slot;
	}

	private static function clear_all_hooks(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		for ( $i = 0; $i < 4; $i++ ) {
			wp_clear_scheduled_hook( self::batch_hook( $i ) );
		}
	}

	public static function maybe_reschedule(): void {
		$sm      = Settings::get_section( 'auto_publisher' );
		$enabled = ! empty( $sm['enabled'] );

		if ( ! $enabled ) {
			self::clear_all_hooks();
			return;
		}

		$n    = max( 1, min( 4, (int) ( $sm['batches_per_day'] ?? 1 ) ) );
		$hour = max( 0, min( 23, (int) ( $sm['run_hour'] ?? 8 ) ) );

		// Ensure all N required hooks are scheduled and no extra slots exist.
		$needs_reschedule = false;
		for ( $i = 0; $i < $n; $i++ ) {
			if ( ! wp_next_scheduled( self::batch_hook( $i ) ) ) {
				$needs_reschedule = true;
				break;
			}
		}
		if ( ! $needs_reschedule ) {
			for ( $i = $n; $i < 4; $i++ ) {
				if ( wp_next_scheduled( self::batch_hook( $i ) ) ) {
					$needs_reschedule = true;
					break;
				}
			}
		}

		if ( ! $needs_reschedule ) {
			return;
		}

		self::clear_all_hooks();

		$tz       = wp_timezone();
		$now      = new \DateTime( 'now', $tz );
		$interval = (int) floor( 24 / $n );

		for ( $i = 0; $i < $n; $i++ ) {
			$batch_hour = ( $hour + $i * $interval ) % 24;
			$run        = new \DateTime( 'today ' . sprintf( '%02d:00:00', $batch_hour ), $tz );
			if ( $run <= $now ) {
				$run->modify( '+1 day' );
			}
			wp_schedule_event( $run->getTimestamp(), 'daily', self::batch_hook( $i ) );
		}
	}

	// -------------------------------------------------------------------------
	// Batch runner
	// -------------------------------------------------------------------------

	public static function run_batch( int $slot = 0 ): void {
		$sm = Settings::get_section( 'auto_publisher' );
		if ( empty( $sm['enabled'] ) ) {
			return;
		}

		$n     = max( 1, min( 4, (int) ( $sm['batches_per_day'] ?? 1 ) ) );
		$min   = max( 1, (int) ( $sm['min_per_day'] ?? 7 ) );
		$max   = max( $min, (int) ( $sm['max_per_day'] ?? 21 ) );
		$order = $sm['publish_order'] ?? 'random';

		// Distribute daily quota evenly across batches.
		$batch_min = max( 1, (int) floor( $min / $n ) );
		$batch_max = max( $batch_min, (int) floor( $max / $n ) );
		$count     = rand( $batch_min, $batch_max );

		$published = Database::publish_food_batch( $count, $order );

		// Auto-disable when all foods are published.
		if ( 0 === $published ) {
			$all                              = Settings::get_all();
			$all['auto_publisher']['enabled'] = false;
			Settings::save( $all );
			self::clear_all_hooks();
		}

		// Merge same-day batch totals into a single log entry.
		$log   = get_option( self::LOG_OPTION, [] );
		$today = gmdate( 'Y-m-d' );
		$found = false;
		foreach ( $log as &$entry ) {
			if ( $entry['date'] === $today ) {
				$entry['count'] += $published;
				$found = true;
				break;
			}
		}
		unset( $entry );
		if ( ! $found ) {
			array_unshift( $log, [ 'date' => $today, 'count' => $published ] );
		}
		update_option( self::LOG_OPTION, array_slice( $log, 0, 30 ), false );
	}

	// -------------------------------------------------------------------------
	// Stats
	// -------------------------------------------------------------------------

	public static function get_stats(): array {
		$counts    = Database::get_published_food_counts();
		$sm        = Settings::get_section( 'auto_publisher' );
		$n         = max( 1, min( 4, (int) ( $sm['batches_per_day'] ?? 1 ) ) );
		$min       = (int) ( $sm['min_per_day'] ?? 7 );
		$max_day   = (int) ( $sm['max_per_day'] ?? 21 );
		$avg       = (int) round( ( $min + $max_day ) / 2 );
		$remaining = $counts['unpublished'];
		$est_days  = ( $avg > 0 && $remaining > 0 ) ? (int) ceil( $remaining / $avg ) : 0;

		// Next run: earliest of all scheduled batch hooks.
		$next = null;
		for ( $i = 0; $i < $n; $i++ ) {
			$t = wp_next_scheduled( self::batch_hook( $i ) );
			if ( $t && ( null === $next || $t < $next ) ) {
				$next = $t;
			}
		}
		if ( null === $next ) {
			$next = wp_next_scheduled( self::CRON_HOOK ); // Legacy fallback.
		}

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
