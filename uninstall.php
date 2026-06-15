<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Drops custom tables ONLY when the admin has toggled
 * "Delete data on uninstall" in Settings → Advanced.
 *
 * @package FCC
 */

// Only run if WordPress called this directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

global $wpdb;

$settings = get_option( 'fcc_settings', [] );
$delete   = ! empty( $settings['advanced']['delete_data_on_uninstall'] );

if ( $delete ) {
	$foods_table      = $wpdb->prefix . 'fcc_foods';
	$categories_table = $wpdb->prefix . 'fcc_categories';

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are safe prefix+literal strings.
	$wpdb->query( "DROP TABLE IF EXISTS `{$foods_table}`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$categories_table}`" );
	// phpcs:enable

	delete_option( 'fcc_settings' );
	delete_option( 'fcc_db_version' );
}
