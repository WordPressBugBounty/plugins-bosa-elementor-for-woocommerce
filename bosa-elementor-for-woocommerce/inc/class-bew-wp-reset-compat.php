<?php
/**
 * BEW WP Reset compatibility
 *
 * WP Reset's "Reset Site" can reactivate the plugins that were active before the
 * reset. It saves that list in the `wpr_active_plugins` transient, in the order
 * WordPress stores it (alphabetical by plugin path), then replays it with
 * activate_plugin() and ignores any failure.
 *
 * BEW declares "Requires Plugins: elementor", and WordPress refuses to activate
 * a plugin whose required plugins aren't active yet. "bosa-elementor-for-
 * woocommerce/" sorts before "elementor/", so on replay BEW is tried first,
 * refused, and never retried: it stays deactivated.
 *
 * So when WP Reset saves the list, this puts every plugin's required plugins
 * ahead of it. The sort is stable: a list that is already in a valid order comes
 * back unchanged. This is the only point BEW can act -- WP Reset saves the list
 * in an AJAX request just before the reset, while BEW is still active; once the
 * reset starts, BEW is no longer loaded. BEW Pro (requires BEW and Elementor) is
 * covered by the same sort.
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BEW_WP_Reset_Compat' ) ) {

	class BEW_WP_Reset_Compat {

		const TRANSIENT = 'wpr_active_plugins';

		public static function init() {
			add_filter( 'pre_set_transient_' . self::TRANSIENT, array( __CLASS__, 'order_by_dependency' ) );
		}

		/**
		 * @param mixed $plugins Plugin files, in the order WP Reset will reactivate them.
		 * @return mixed The same plugins, each one placed after the plugins it requires.
		 */
		public static function order_by_dependency( $plugins ) {
			if ( ! is_array( $plugins ) || count( $plugins ) < 2 ) {
				return $plugins;
			}

			foreach ( $plugins as $file ) {
				if ( ! is_string( $file ) ) {
					return $plugins;
				}
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$installed = get_plugins();
			$by_slug   = array();
			foreach ( $plugins as $file ) {
				$by_slug[ self::slug( $file ) ] = $file;
			}

			$ordered = array();
			$seen    = array();
			$visit   = function ( $file ) use ( &$visit, &$ordered, &$seen, $installed, $by_slug ) {
				if ( isset( $seen[ $file ] ) ) {
					return;
				}
				// Marked before descending, so a circular requirement can't loop.
				$seen[ $file ] = true;

				$requires = isset( $installed[ $file ]['RequiresPlugins'] ) ? $installed[ $file ]['RequiresPlugins'] : '';
				foreach ( array_filter( array_map( 'trim', explode( ',', $requires ) ) ) as $slug ) {
					if ( isset( $by_slug[ $slug ] ) ) {
						$visit( $by_slug[ $slug ] );
					}
				}

				$ordered[] = $file;
			};

			foreach ( $plugins as $file ) {
				$visit( $file );
			}

			return $ordered;
		}

		/**
		 * The slug a "Requires Plugins" header uses for a plugin file: its folder
		 * name, or the file name for a single-file plugin.
		 */
		private static function slug( $file ) {
			$dir = dirname( $file );

			return '.' === $dir ? basename( $file, '.php' ) : $dir;
		}
	}

	BEW_WP_Reset_Compat::init();
}
