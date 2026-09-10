<?php
/**
 * BEW Plugin Settings Manager
 *
 * Named BEW_Plugin_Settings (not BEW_Settings) to avoid collision with
 * \Elementor\BEW_Settings — the abstract widget base class in inc/bew-common.php.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Plugin_Settings {

	const SETTINGS_KEY = 'bew_plugin_settings';

	public static function get_all() {
		return get_option( self::SETTINGS_KEY, self::get_defaults() );
	}

	public static function get( $key, $default = null ) {
		$settings = self::get_all();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	public static function save( $key, $value ) {
		$settings         = self::get_all();
		$settings[ $key ] = $value;
		return update_option( self::SETTINGS_KEY, $settings );
	}

	public static function toggle_widget( $widget_id, $enabled = true ) {
		$widgets               = self::get( 'widget_toggles', [] );
		$widgets[ $widget_id ] = (bool) $enabled;
		self::save( 'widget_toggles', $widgets );
	}

	public static function is_widget_enabled( $widget_id ) {
		$widgets = self::get( 'widget_toggles', [] );
		return isset( $widgets[ $widget_id ] ) ? (bool) $widgets[ $widget_id ] : true;
	}

	public static function toggle_ai_feature( $feature_id, $enabled = true ) {
		$features                = self::get( 'ai_feature_toggles', [] );
		$features[ $feature_id ] = (bool) $enabled;
		self::save( 'ai_feature_toggles', $features );
	}

	public static function is_ai_feature_enabled( $feature_id ) {
		$features = self::get( 'ai_feature_toggles', [] );
		return isset( $features[ $feature_id ] ) ? (bool) $features[ $feature_id ] : true;
	}

	public static function toggle_module( $module_id, $enabled = true ) {
		$module_id               = preg_replace( '/^module_/', '', $module_id );
		$modules                 = self::get( 'module_toggles', [] );
		$modules[ $module_id ]   = (bool) $enabled;
		self::save( 'module_toggles', $modules );
	}

	public static function is_module_enabled( $module_id ) {
		$modules = self::get( 'module_toggles', [] );
		if ( isset( $modules[ $module_id ] ) ) {
			return (bool) $modules[ $module_id ];
		}
		$legacy = self::get( 'extension_toggles', [] );
		if ( isset( $legacy[ $module_id ] ) ) {
			return (bool) $legacy[ $module_id ];
		}
		// Modules are opt-in; stay off until explicitly enabled.
		return false;
	}

	const ROLLBACK_MIN_VERSION = '2.0.0';

	public static function get_rollback_version() {
		$stored = self::get( 'previous_version', '' );
		if ( $stored && version_compare( $stored, self::ROLLBACK_MIN_VERSION, '>=' ) ) {
			return $stored;
		}

		$cached = get_transient( 'bew_rollback_version' );
		if ( false !== $cached ) {
			return $cached;
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api( 'plugin_information', [
			'slug'   => 'bosa-elementor-for-woocommerce',
			'fields' => [ 'versions' => true ],
		] );

		if ( is_wp_error( $api ) || empty( $api->versions ) ) {
			set_transient( 'bew_rollback_version', '', DAY_IN_SECONDS );
			return '';
		}

		$versions = array_keys( $api->versions );
		$versions = array_filter( $versions, function ( $v ) {
			return 'trunk' !== $v && version_compare( $v, BEW_Plugin_Settings::ROLLBACK_MIN_VERSION, '>=' );
		} );
		usort( $versions, 'version_compare' );
		$versions = array_values( $versions );

		$prev          = '';
		$current_index = array_search( BEW_VERSION, $versions, true );

		if ( false !== $current_index && $current_index > 0 ) {
			$prev = $versions[ $current_index - 1 ];
		} elseif ( count( $versions ) >= 2 ) {
			$prev = $versions[ count( $versions ) - 2 ];
		}

		set_transient( 'bew_rollback_version', $prev ?: '', DAY_IN_SECONDS );
		return $prev;
	}

	private static function get_defaults() {
		return [
			'widget_toggles'     => [],
			'module_toggles'     => [],
			'ai_feature_toggles' => [],
			'extension_toggles'  => [],
			'template_library'   => true,
			'previous_version'   => '',
			'current_version'    => '',
		];
	}
}
