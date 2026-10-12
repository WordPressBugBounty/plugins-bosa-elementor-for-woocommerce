<?php
/**
 * BEW System Status
 *
 * Read-only server and plugin checks for BEW > Settings > System Status, plus
 * a plain-text report to paste into a support request. Nothing here changes
 * any setting.
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_System_Status {

	const PRO_PLUGIN = 'bosa-elementor-for-woocommerce-pro/bosa-elementor-for-woocommerce-pro.php';

	/**
	 * Server checks. Each row: label, value, recommended, ok (bool, or null for info only).
	 *
	 * @return array[]
	 */
	public static function server_checks() {
		$mem_wp  = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		$mem_ini = wp_convert_hr_to_bytes( (string) ini_get( 'memory_limit' ) );
		$mem     = ( $mem_ini < 0 || $mem_wp < 0 ) ? -1 : max( $mem_wp, $mem_ini );
		$time    = (int) ini_get( 'max_execution_time' );
		$vars    = (int) ini_get( 'max_input_vars' );
		$fopen   = filter_var( ini_get( 'allow_url_fopen' ), FILTER_VALIDATE_BOOLEAN );
		$upload  = wp_max_upload_size();
		$curl    = function_exists( 'curl_version' );
		$zip     = class_exists( 'ZipArchive' );
		$wp      = get_bloginfo( 'version' );

		$on        = __( 'On', 'bosa-elementor-for-woocommerce' );
		$off       = __( 'Off', 'bosa-elementor-for-woocommerce' );
		$available = __( 'Available', 'bosa-elementor-for-woocommerce' );
		$missing   = __( 'Missing', 'bosa-elementor-for-woocommerce' );
		$unlimited = __( 'Unlimited', 'bosa-elementor-for-woocommerce' );

		return array(
			array( __( 'PHP version', 'bosa-elementor-for-woocommerce' ), PHP_VERSION, __( '7.4 or higher', 'bosa-elementor-for-woocommerce' ), version_compare( PHP_VERSION, '7.4', '>=' ) ),
			array( __( 'WordPress version', 'bosa-elementor-for-woocommerce' ), $wp, __( '6.9 or higher', 'bosa-elementor-for-woocommerce' ), version_compare( $wp, '6.9', '>=' ) ),
			array( __( 'Memory limit', 'bosa-elementor-for-woocommerce' ), $mem < 0 ? $unlimited : size_format( $mem ), '256 MB', $mem < 0 || $mem >= 256 * MB_IN_BYTES ),
			array( __( 'Max execution time', 'bosa-elementor-for-woocommerce' ), 0 === $time ? $unlimited : $time . 's', '300s', 0 === $time || $time >= 300 ),
			array( __( 'Max upload size', 'bosa-elementor-for-woocommerce' ), size_format( $upload ), '32 MB', $upload >= 32 * MB_IN_BYTES ),
			array( __( 'Max input vars', 'bosa-elementor-for-woocommerce' ), (string) $vars, '3000', $vars >= 3000 ),
			array( 'allow_url_fopen', $fopen ? $on : $off, $on, $fopen ),
			array( 'cURL', $curl ? $available : $missing, $available, $curl ),
			array( 'ZipArchive', $zip ? $available : $missing, $available, $zip ),
		);
	}

	/**
	 * Theme and plugin checks. Each row: label, value, ok (bool, or null for info only), note.
	 *
	 * @return array[]
	 */
	public static function plugin_checks() {
		$theme     = wp_get_theme();
		$elementor = defined( 'ELEMENTOR_VERSION' );
		$woo       = class_exists( 'WooCommerce' );

		$not_active = __( 'Not active', 'bosa-elementor-for-woocommerce' );

		return array(
			array( __( 'Active theme', 'bosa-elementor-for-woocommerce' ), trim( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ), null, '' ),
			array(
				'Elementor',
				$elementor ? self::active_label( ELEMENTOR_VERSION ) : $not_active,
				$elementor,
				$elementor ? '' : __( 'BEW adds its widgets and templates inside Elementor.', 'bosa-elementor-for-woocommerce' ),
			),
			array(
				'WooCommerce',
				$woo ? self::active_label( defined( 'WC_VERSION' ) ? WC_VERSION : '' ) : $not_active,
				null,
				__( 'Needed only for the WooCommerce widgets.', 'bosa-elementor-for-woocommerce' ),
			),
			array( 'BEW Pro', self::pro_label(), null, '' ),
		);
	}

	/**
	 * Plain-text report for support requests.
	 *
	 * @return string
	 */
	public static function system_report() {
		$lines   = array();
		$lines[] = 'BEW: ' . BEW_VERSION;
		$lines[] = 'Site URL: ' . site_url();
		$lines[] = 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' );
		$lines[] = '';
		foreach ( self::server_checks() as $row ) {
			$lines[] = $row[0] . ': ' . $row[1] . ' (recommended: ' . $row[2] . ')' . ( false === $row[3] ? ' [WARNING]' : '' );
		}
		$lines[] = '';
		foreach ( self::plugin_checks() as $row ) {
			$lines[] = $row[0] . ': ' . $row[1] . ( false === $row[2] ? ' [WARNING]' : '' );
		}
		return implode( "\n", $lines );
	}

	/**
	 * Status icon: info for null, tick for true, warning for false.
	 *
	 * @param bool|null $ok Check result.
	 */
	public static function render_icon( $ok ) {
		if ( null === $ok ) {
			echo '<span class="dashicons dashicons-info-outline bew-status-info" aria-hidden="true"></span>';
			return;
		}

		if ( $ok ) {
			echo '<span class="dashicons dashicons-yes-alt bew-status-ok" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'OK', 'bosa-elementor-for-woocommerce' ) . '</span>';
			return;
		}

		echo '<span class="dashicons dashicons-warning bew-status-warn" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html__( 'Warning', 'bosa-elementor-for-woocommerce' ) . '</span>';
	}

	/**
	 * "Active (4.3.3)", or just "Active" when the version is unknown.
	 *
	 * @param string $version Version string.
	 * @return string
	 */
	private static function active_label( $version ) {
		if ( '' === (string) $version ) {
			return __( 'Active', 'bosa-elementor-for-woocommerce' );
		}

		/* translators: %s: plugin version, e.g. "4.3.3". */
		return sprintf( __( 'Active (%s)', 'bosa-elementor-for-woocommerce' ), $version );
	}

	/**
	 * BEW Pro: active (with version), installed but inactive, or not installed.
	 *
	 * @return string
	 */
	private static function pro_label() {
		if ( class_exists( 'BEW_Pro' ) ) {
			return self::active_label( defined( 'BEW_PRO_VERSION' ) ? BEW_PRO_VERSION : '' );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return isset( get_plugins()[ self::PRO_PLUGIN ] )
			? __( 'Installed but inactive', 'bosa-elementor-for-woocommerce' )
			: __( 'Not installed', 'bosa-elementor-for-woocommerce' );
	}
}
