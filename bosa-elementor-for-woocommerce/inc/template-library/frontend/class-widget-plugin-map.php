<?php
/**
 * Dynamic widget-to-plugin detection.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects third-party Elementor widgets and maps them to their originating plugins.
 */
class Bosa_Ewc_Widget_Plugin_Map {

	/**
	 * Prefix-hint table used exclusively as a last resort for inactive plugins.
	 *
	 * @var array<string, array{slug:string, name:string}>
	 */
	private static $inactive_hints = [
		'elementskit-'   => [ 'slug' => 'elementskit-lite',                           'name' => 'ElementsKit Lite' ],
		'ekit-'          => [ 'slug' => 'elementskit-lite',                           'name' => 'ElementsKit Lite' ],
		'wpcf7-'         => [ 'slug' => 'contact-form-7',                             'name' => 'Contact Form 7' ],
		'contact-form-7' => [ 'slug' => 'contact-form-7',                             'name' => 'Contact Form 7' ],
		'wc-'            => [ 'slug' => 'woocommerce',                                'name' => 'WooCommerce' ],
		'bosa-woo-'      => [ 'slug' => 'bosa-elementor-for-woocommerce',             'name' => 'BEW' ],
		'bew-'           => [ 'slug' => 'bosa-elementor-for-woocommerce',             'name' => 'BEW' ],
		'prime-slider-'  => [ 'slug' => 'bdthemes-prime-slider-lite',                'name' => 'Prime Slider' ],
		'prime_slider_'  => [ 'slug' => 'bdthemes-prime-slider-lite',                'name' => 'Prime Slider' ],
		'bdt-'           => [ 'slug' => 'bdthemes-prime-slider-lite',                'name' => 'Prime Slider' ],
		'music-player'   => [ 'slug' => 'mp3-music-player-by-sonaar',                'name' => 'MP3 Audio Player by Sonaar' ],
		'srmp3_'         => [ 'slug' => 'mp3-music-player-by-sonaar',                'name' => 'MP3 Audio Player by Sonaar' ],
		'srmp3-'         => [ 'slug' => 'mp3-music-player-by-sonaar',                'name' => 'MP3 Audio Player by Sonaar' ],
		'sonaar_'        => [ 'slug' => 'mp3-music-player-by-sonaar',                'name' => 'MP3 Audio Player by Sonaar' ],
		'sonaar-'        => [ 'slug' => 'mp3-music-player-by-sonaar',                'name' => 'MP3 Audio Player by Sonaar' ],
		'sina_ext_'      => [ 'slug' => 'sina-extension-for-elementor',              'name' => 'Sina Extension for Elementor' ],
		'sina_'          => [ 'slug' => 'sina-extension-for-elementor',              'name' => 'Sina Extension for Elementor' ],
		'sina-'          => [ 'slug' => 'sina-extension-for-elementor',              'name' => 'Sina Extension for Elementor' ],
		'woolentor-'     => [ 'slug' => 'woolentor-addons',                           'name' => 'ShopLentor' ],
		'woolentor_'     => [ 'slug' => 'woolentor-addons',                           'name' => 'ShopLentor' ],
		'wl-'            => [ 'slug' => 'woolentor-addons',                           'name' => 'ShopLentor' ],
		'wl_'            => [ 'slug' => 'woolentor-addons',                           'name' => 'ShopLentor' ],
		'premium-'       => [ 'slug' => 'premium-addons-for-elementor',              'name' => 'Premium Addons for Elementor' ],
		'pafe-'          => [ 'slug' => 'premium-addons-for-elementor',              'name' => 'Premium Addons for Elementor' ],
		'pafe_'          => [ 'slug' => 'premium-addons-for-elementor',              'name' => 'Premium Addons for Elementor' ],
	];

	/** @var array<string,array{widget_label:string,plugin_slug:string,plugin_name:string}>|null */
	private $active_map = null;

	/** @var string[]|null */
	private $all_registered = null;

	/** @var array<string,array{name:string,file:string}>|null */
	private $installed_by_dir = null;

	/**
	 * Detect third-party widgets from a list of Elementor widget type strings.
	 *
	 * @param string[] $widget_types Widget type strings collected from the template JSON.
	 * @return array<string, array{
	 *     widget_type: string,
	 *     widget_label: string,
	 *     plugin_slug: string|null,
	 *     plugin_name: string|null,
	 *     is_active: bool
	 * }>
	 */
	public function detect( array $widget_types ) {
		$active_map      = $this->get_active_map();
		$all_reg         = $this->get_all_registered();
		$registry_loaded = ! empty( $all_reg );

		$result = [];

		foreach ( array_unique( $widget_types ) as $type ) {
			// BEW's own Contact Form 7 wrapper widget stays registered in
			// Elementor whenever BEW itself is active, independent of whether
			// CF7 is -- unlike every other entry in $inactive_hints,
			// "registered" here does not imply its actual functional
			// dependency (CF7, checked only at render time by the widget's
			// own class_exists('WPCF7') fallback) is met. Check that
			// explicitly, ahead of the registration-based branches below, or
			// a deactivated CF7 is invisible to this scanner: the widget
			// imports with no missing-plugin prompt and no working form.
			if ( 'bew-elements-contact-form-7' === $type && ! $this->is_plugin_slug_active( 'contact-form-7' ) ) {
				$hint = self::$inactive_hints['contact-form-7'];
				$result[ $type ] = [
					'widget_type'  => $type,
					'widget_label' => $active_map[ $type ]['widget_label'] ?? $type,
					'plugin_slug'  => $hint['slug'],
					'plugin_name'  => $hint['name'],
					'is_active'    => false,
				];
				continue;
			}

			if ( $registry_loaded ) {
				if ( isset( $active_map[ $type ] ) ) {
					$result[ $type ] = [
						'widget_type'  => $type,
						'widget_label' => $active_map[ $type ]['widget_label'],
						'plugin_slug'  => $active_map[ $type ]['plugin_slug'],
						'plugin_name'  => $active_map[ $type ]['plugin_name'],
						'is_active'    => true,
					];
				} elseif ( ! in_array( $type, $all_reg, true ) ) {
					$hint = $this->get_inactive_hint( $type );

					if ( $hint && $this->is_plugin_slug_active( $hint['slug'] ) ) {
						continue;
					}

					$result[ $type ] = [
						'widget_type'  => $type,
						'widget_label' => $type,
						'plugin_slug'  => $hint ? $hint['slug'] : null,
						'plugin_name'  => $hint ? $hint['name'] : null,
						'is_active'    => false,
					];
				}
				// Widget is registered by Elementor core; skip.
			} else {
				$hint = $this->get_inactive_hint( $type );
				if ( $hint && ! $this->is_plugin_slug_active( $hint['slug'] ) ) {
					$result[ $type ] = [
						'widget_type'  => $type,
						'widget_label' => $type,
						'plugin_slug'  => $hint['slug'],
						'plugin_name'  => $hint['name'],
						'is_active'    => false,
					];
				}
			}
		}

		return $result;
	}

	/**
	 * @deprecated Use detect() instead.
	 * @return array Always empty.
	 */
	public function get_widget_map() {
		return [];
	}

	/**
	 * Return every WordPress.org slug listed in the inactive prefix-hint table.
	 *
	 * @return string[]
	 */
	public static function get_known_plugin_slugs() {
		$slugs = [];
		foreach ( self::$inactive_hints as $hint ) {
			if ( ! empty( $hint['slug'] ) ) {
				$slugs[ $hint['slug'] ] = true;
			}
		}
		return array_keys( $slugs );
	}

	/**
	 * @param string $widget_type
	 * @return array{slug:string, name:string}|null
	 */
	public function lookup_inactive_plugin( $widget_type ) {
		return $this->get_inactive_hint( $widget_type );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * @return array<string, array{widget_label:string, plugin_slug:string, plugin_name:string}>
	 */
	private function get_active_map() {
		if ( null !== $this->active_map ) {
			return $this->active_map;
		}

		$this->active_map = [];

		if ( ! class_exists( '\Elementor\Plugin' )
			|| ! isset( \Elementor\Plugin::instance()->widgets_manager )
			|| ! method_exists( \Elementor\Plugin::instance()->widgets_manager, 'get_widget_types' ) ) {
			return $this->active_map;
		}

		$plugins_dir = wp_normalize_path( WP_PLUGIN_DIR );
		$installed   = $this->get_installed_by_dir();
		$core_dirs   = [ 'elementor', 'elementor-pro' ];

		foreach ( \Elementor\Plugin::instance()->widgets_manager->get_widget_types() as $type => $widget ) {
			try {
				$file = wp_normalize_path( ( new ReflectionClass( $widget ) )->getFileName() );
			} catch ( \ReflectionException $e ) {
				continue;
			}

			if ( 0 !== strpos( $file, $plugins_dir . '/' ) ) {
				continue;
			}

			$relative    = substr( $file, strlen( $plugins_dir ) + 1 );
			$plugin_slug = strtok( $relative, '/' );

			if ( in_array( $plugin_slug, $core_dirs, true ) ) {
				continue;
			}

			$plugin_name = isset( $installed[ $plugin_slug ] )
				? $installed[ $plugin_slug ]['name']
				: $plugin_slug;

			$this->active_map[ $type ] = [
				'widget_label' => $widget->get_title(),
				'plugin_slug'  => $plugin_slug,
				'plugin_name'  => $plugin_name,
			];
		}

		return $this->active_map;
	}

	/**
	 * @return string[]
	 */
	private function get_all_registered() {
		if ( null !== $this->all_registered ) {
			return $this->all_registered;
		}

		$this->all_registered = [];

		if ( class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::instance()->widgets_manager )
			&& method_exists( \Elementor\Plugin::instance()->widgets_manager, 'get_widget_types' ) ) {
			$this->all_registered = array_keys(
				\Elementor\Plugin::instance()->widgets_manager->get_widget_types()
			);
		}

		return $this->all_registered;
	}

	/**
	 * @return array<string, array{name:string, file:string}>
	 */
	private function get_installed_by_dir() {
		if ( null !== $this->installed_by_dir ) {
			return $this->installed_by_dir;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->installed_by_dir = [];

		foreach ( get_plugins() as $file => $data ) {
			$dir = dirname( $file );
			if ( '.' === $dir ) {
				$dir = basename( $file, '.php' );
			}
			$this->installed_by_dir[ $dir ] = [ 'name' => $data['Name'], 'file' => $file ];
		}

		return $this->installed_by_dir;
	}

	/**
	 * @param string $slug
	 * @return bool
	 */
	private function is_plugin_slug_active( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug ) {
			return false;
		}

		$installed = $this->get_installed_by_dir();
		if ( ! isset( $installed[ $slug ] ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $installed[ $slug ]['file'] );
	}

	/**
	 * @param string $widget_type
	 * @return array{slug:string, name:string}|null
	 */
	private function get_inactive_hint( $widget_type ) {
		$widget_type = (string) $widget_type;

		if ( isset( self::$inactive_hints[ $widget_type ] ) ) {
			return self::$inactive_hints[ $widget_type ];
		}

		$prefixes = array_keys( self::$inactive_hints );
		usort( $prefixes, static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		} );

		foreach ( $prefixes as $prefix ) {
			if ( 0 === strpos( $widget_type, $prefix ) ) {
				return self::$inactive_hints[ $prefix ];
			}
		}

		$seen = [];
		foreach ( self::$inactive_hints as $hint ) {
			$slug = $hint['slug'] ?? '';
			if ( ! $slug || isset( $seen[ $slug ] ) ) {
				continue;
			}
			$seen[ $slug ] = true;

			$stem = preg_replace( '/-lite$/', '', $slug );
			if ( $stem && strlen( $stem ) > 3 && false !== strpos( $widget_type, $stem ) ) {
				return $hint;
			}

			foreach ( explode( '-', $stem ) as $slug_token ) {
				if ( strlen( $slug_token ) <= 2 ) {
					continue;
				}
				if ( 0 === strpos( $widget_type, $slug_token . '_' )
					|| 0 === strpos( $widget_type, $slug_token . '-' ) ) {
					return $hint;
				}
			}
		}

		return null;
	}
}
