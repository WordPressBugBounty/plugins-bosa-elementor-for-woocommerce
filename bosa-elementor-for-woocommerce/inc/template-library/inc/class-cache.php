<?php
/**
 * Transient-based template cache.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around WordPress transients for caching remote API responses.
 */
class Bosa_Ewc_Cache {

	/** Prefix applied to every transient key. */
	const PREFIX = 'bosa_tk_';

	/**
	 * @param string $key   Arbitrary cache key.
	 * @param mixed  $value Value to store.
	 * @param int    $ttl   Expiry in seconds.
	 * @return void
	 */
	public function set( $key, $value, $ttl = 3600 ) {
		set_transient( self::PREFIX . md5( $key ), $value, absint( $ttl ) );
	}

	/**
	 * @param string $key Cache key.
	 * @return mixed|false False when the key does not exist or has expired.
	 */
	public function get( $key ) {
		return get_transient( self::PREFIX . md5( $key ) );
	}

	/**
	 * @param string $key Cache key.
	 * @return void
	 */
	public function delete( $key ) {
		delete_transient( self::PREFIX . md5( $key ) );
	}

	/**
	 * Delete every transient whose name starts with our prefix.
	 *
	 * @return void
	 */
	public function flush_all() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::PREFIX ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%'
			)
		);
	}
}
