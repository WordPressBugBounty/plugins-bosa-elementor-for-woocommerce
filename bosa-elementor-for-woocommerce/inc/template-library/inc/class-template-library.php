<?php
/**
 * Core module class.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps all Template Library components, registers CPTs and the template category taxonomy.
 */
class Bosa_Ewc_Template_Library {

	/**
	 * @return void
	 */
	public function run() {
		if ( ! $this->is_elementor_active() ) {
			add_action( 'admin_notices', [ $this, 'elementor_missing_notice' ] );
			return;
		}

		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_taxonomies' ] );
		add_action( 'init', [ $this, 'register_image_sizes' ] );

		add_action( 'elementor/init', [ $this, 'init_components' ] );
	}

	/**
	 * Boot sub-systems once Elementor is ready.
	 *
	 * @return void
	 */
	public function init_components() {
		$this->register_elementor_source();

		( new Bosa_Ewc_Modal() )->run();
		( new Bosa_Ewc_API() )->run();

		require_once BEW_TL_DIR . 'admin/class-editor-prompt.php';
		( new Bosa_Ewc_Editor_Prompt() )->run();
	}

	/**
	 * @return void
	 */
	public function register_post_types() {
		$shared = [
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => [ 'title', 'thumbnail', 'custom-fields' ],
		];

		$types = [
			'bosa_tk_page'    => [ __( 'Page',    'bosa-elementor-for-woocommerce' ), __( 'Pages',    'bosa-elementor-for-woocommerce' ) ],
			'bosa_tk_section' => [ __( 'Section', 'bosa-elementor-for-woocommerce' ), __( 'Sections', 'bosa-elementor-for-woocommerce' ) ],
			'bosa_tk_kit'     => [ __( 'Kit',     'bosa-elementor-for-woocommerce' ), __( 'Kits',     'bosa-elementor-for-woocommerce' ) ],
			'bosa_tk_header'  => [ __( 'Header',  'bosa-elementor-for-woocommerce' ), __( 'Headers',  'bosa-elementor-for-woocommerce' ) ],
			'bosa_tk_footer'  => [ __( 'Footer',  'bosa-elementor-for-woocommerce' ), __( 'Footers',  'bosa-elementor-for-woocommerce' ) ],
		];

		foreach ( $types as $cpt => [ $singular, $plural ] ) {
			register_post_type(
				$cpt,
				array_merge( $shared, [
					'label'  => $plural,
					'labels' => bosa_ewc_cpt_labels( $singular, $plural ),
				] )
			);
		}
	}

	/**
	 * Register the shared template category taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		register_taxonomy(
			'bosa_template_kit_cat',
			bosa_ewc_get_all_cpt_slugs(),
			[
				'labels'       => bosa_ewc_tax_labels(
					__( 'Category', 'bosa-elementor-for-woocommerce' ),
					__( 'Categories', 'bosa-elementor-for-woocommerce' )
				),
				'public'       => false,
				'show_ui'      => false,
				'show_in_rest' => false,
				'hierarchical' => true,
				'rewrite'      => false,
				'query_var'    => false,
			]
		);
	}

	/**
	 * Register the card thumbnail size: fixed width, uncapped height.
	 *
	 * @return void
	 */
	public function register_image_sizes() {
		add_image_size( 'bosa_tk_card_thumb', 253, 0, false );
	}

	/**
	 * @return void
	 */
	public function elementor_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'BEW Template Library requires Elementor to be installed and activated.', 'bosa-elementor-for-woocommerce' );
		echo '</p></div>';
	}

	/**
	 * Register Bosa_Ewc_Source with Elementor's template library manager.
	 *
	 * @return void
	 */
	private function register_elementor_source() {
		if (
			! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ||
			! isset( \Elementor\Plugin::$instance->templates_manager ) ||
			! method_exists( \Elementor\Plugin::$instance->templates_manager, 'register_source' )
		) {
			return;
		}

		require_once BEW_TL_DIR . 'elementor/class-source.php';

		if ( ! class_exists( 'Bosa_Ewc_Source' ) ) {
			return;
		}

		try {
			\Elementor\Plugin::$instance->templates_manager->register_source( 'Bosa_Ewc_Source' );
		} catch ( \Exception $e ) {
			// Source registration failed; toolbar button remains functional.
		}
	}

	/**
	 * @return bool
	 */
	private function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}
}
