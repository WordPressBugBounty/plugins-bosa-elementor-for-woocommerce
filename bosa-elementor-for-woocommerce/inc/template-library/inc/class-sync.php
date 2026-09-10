<?php
/**
 * Shared template-library sync callable used by both WP-Cron and the manual
 * sync REST endpoint.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flushes the remote-template transient cache and records the outcome.
 */
class Bosa_Ewc_Sync {

	/** Transient key for the exclusive run lock. */
	const LOCK_KEY = 'bew_tl_sync_lock';

	/** Lock TTL in seconds — long enough to cover the flush query. */
	const LOCK_TTL = 300;

	/**
	 * Flush the template cache. Acquires a transient lock so concurrent
	 * cron/manual triggers cannot step on each other.
	 *
	 * @return void
	 */
	public static function run() {
		if ( get_transient( self::LOCK_KEY ) ) {
			return;
		}

		set_transient( self::LOCK_KEY, 1, self::LOCK_TTL );

		$error = '';

		try {
			global $wpdb;
			( new Bosa_Ewc_Cache() )->flush_all();
			if ( $wpdb->last_error ) {
				$error = $wpdb->last_error;
			}
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
		} finally {
			delete_transient( self::LOCK_KEY );
		}

		update_option( 'bew_tl_last_sync_time', time(), false );

		if ( $error ) {
			update_option( 'bew_tl_last_sync_status', 'error', false );
			update_option( 'bew_tl_last_sync_error', $error, false );
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( 'BEW daily template sync error: ' . $error );
		} else {
			update_option( 'bew_tl_last_sync_status', 'success', false );
			update_option( 'bew_tl_last_sync_error', '', false );
		}
	}
}
