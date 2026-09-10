<?php
/**
 * Elementor editor integration — assets and settings injection.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages editor asset enqueueing and Elementor settings injection.
 */
class Bosa_Ewc_Modal {

	/**
	 * @return void
	 */
	public function run() {
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueue_preview_assets' ] );
		add_filter( 'elementor/editor/localize_settings', [ $this, 'inject_editor_settings' ] );
	}

	/**
	 * Enqueue the toolbar button script and stylesheet inside the Elementor editor.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		$editor_css = 'assets/css/editor.css';
		$modal_css  = 'assets/css/modal.css';

		wp_enqueue_style(
			'bew-template-library',
			BEW_TL_URL . $editor_css,
			[],
			$this->asset_version( $editor_css )
		);

		wp_enqueue_style(
			'bew-template-library-modal',
			BEW_TL_URL . $modal_css,
			[ 'elementor-editor' ],
			$this->asset_version( $modal_css )
		);

		wp_enqueue_script( 'masonry' );

		$modal_deps = [ 'jquery', 'backbone', 'elementor-editor', 'masonry' ];
		$modal_dir  = 'assets/js/modal/';

		$modal_files = [
			'bew-tl-modal-collection'     => $modal_dir . 'collections/templates-collection.js',
			'bew-tl-dependency-modal'     => $modal_dir . 'dependency-modal.js',
			'bew-tl-modal-insert'         => $modal_dir . 'template-insert.js',
			'bew-tl-modal-card'           => $modal_dir . 'views/template-card-view.js',
			'bew-tl-modal-preview-body'   => $modal_dir . 'views/template-preview-view.js',
			'bew-tl-modal-preview-header' => $modal_dir . 'views/preview-header-view.js',
			'bew-tl-modal-templates-view' => $modal_dir . 'views/templates-view.js',
			'bew-tl-modal-kit-details'    => $modal_dir . 'views/kit-details-view.js',
			'bew-tl-modal-toolbar-view'   => $modal_dir . 'views/body-toolbar-view.js',
			'bew-tl-modal-content-view'   => $modal_dir . 'views/content-view.js',
			'bew-tl-modal-header-view'    => $modal_dir . 'views/header-view.js',
			'bew-tl-modal-layout'         => $modal_dir . 'views/modal-layout.js',
			'bew-tl-modal-component'      => $modal_dir . 'component.js',
		];

		$category_lists = $this->get_category_lists_for_modal();
		$pro_active     = bosa_ewc_has_pro_plugin();
		$go_pro_banner  = $pro_active
			? 'admin/assets/images/bew-cta-sm-pro-banner.jpg'
			: 'admin/assets/images/bew-cta-sm-banner.jpg';

		$prev = $modal_deps;
		foreach ( $modal_files as $handle => $path ) {
			wp_enqueue_script( $handle, BEW_TL_URL . $path, $prev, $this->asset_version( $path ), true );

			if ( 'bew-tl-modal-toolbar-view' === $handle ) {
				wp_add_inline_script(
					$handle,
					'window.BEWTLCategoryLists=' . wp_json_encode( $category_lists ) . ';',
					'before'
				);
			}

			$prev = [ $handle ];
		}

		$modal_js = 'assets/js/modal/modal.js';
		wp_enqueue_script(
			'bew-template-library-modal',
			BEW_TL_URL . $modal_js,
			$prev,
			$this->asset_version( $modal_js ),
			true
		);

		$editor_js = 'assets/js/editor.js';
		wp_enqueue_script(
			'bew-template-library',
			BEW_TL_URL . $editor_js,
			[ 'jquery', 'elementor-editor', 'bew-template-library-modal' ],
			$this->asset_version( $editor_js ),
			true
		);

		wp_localize_script(
			'bew-template-library',
			'BEWTemplateLibrary',
			[
				'i18n' => [
					'button_label' => __( 'BEW Templates', 'bosa-elementor-for-woocommerce' ),
				],
			]
		);

		wp_localize_script(
			'bew-template-library-modal',
			'BEWTemplateLibraryModal',
			[
				'restUrl'         => esc_url_raw( rest_url( 'bew/v1/' ) ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'build'           => $this->asset_version( 'assets/js/modal/views/template-card-view.js' ),
				'categoryLists'   => $category_lists,
				'proPluginActive' => $pro_active,
				'proUpgradeUrl'   => esc_url_raw( 'https://bew.bosathemes.com/pricing' ),
				'goProBannerUrl'  => esc_url_raw( BEW_URL . $go_pro_banner ),
				'endOfListIconUrl' => esc_url_raw( BEW_TL_URL . 'assets/images/template-info.svg' ),
				'i18n'            => [
					'title'          => __( 'BEW Templates', 'bosa-elementor-for-woocommerce' ),
					'search'         => __( 'Search templates…', 'bosa-elementor-for-woocommerce' ),
					'all_categories' => __( 'All Categories', 'bosa-elementor-for-woocommerce' ),
					'category_label' => __( 'Category', 'bosa-elementor-for-woocommerce' ),
					'search_tabs'    => [
						'kits'     => __( 'Search Kits', 'bosa-elementor-for-woocommerce' ),
						'pages'    => __( 'Search Pages', 'bosa-elementor-for-woocommerce' ),
						'sections' => __( 'Search Sections', 'bosa-elementor-for-woocommerce' ),
					],
					'insert'         => __( 'Insert', 'bosa-elementor-for-woocommerce' ),
					'go_pro'         => __( 'Go Pro', 'bosa-elementor-for-woocommerce' ),
					'preview'        => __( 'Preview', 'bosa-elementor-for-woocommerce' ),
					'live_preview'   => __( 'Live Preview', 'bosa-elementor-for-woocommerce' ),
					'close'          => __( 'Close', 'bosa-elementor-for-woocommerce' ),
					'preview_tagline' => __( 'Start using this style on your WordPress sites and make your site more flexible.', 'bosa-elementor-for-woocommerce' ),
					'back_to'        => [
						'kits'     => __( 'Back To Templates', 'bosa-elementor-for-woocommerce' ),
						'pages'    => __( 'Back To Pages', 'bosa-elementor-for-woocommerce' ),
						'sections' => __( 'Back To Sections', 'bosa-elementor-for-woocommerce' ),
					],
					'back_to_kits'   => __( 'Back To Templates', 'bosa-elementor-for-woocommerce' ),
					'back_to_kit'    => __( 'Back To Kit', 'bosa-elementor-for-woocommerce' ),
					'free'           => __( 'Free', 'bosa-elementor-for-woocommerce' ),
					'pro'            => __( 'Pro', 'bosa-elementor-for-woocommerce' ),
					'loading'        => __( 'Loading…', 'bosa-elementor-for-woocommerce' ),
					'no_results'     => __( 'No templates found.', 'bosa-elementor-for-woocommerce' ),
					'refresh'        => __( 'Sync templates', 'bosa-elementor-for-woocommerce' ),
					'end_of_list'    => __( 'Stay tuned! More awesome templates coming soon.', 'bosa-elementor-for-woocommerce' ),
					'dep_title'          => __( 'Required Plugins', 'bosa-elementor-for-woocommerce' ),
					'dep_desc'           => __( 'This template uses widgets from third-party plugins. Install them for the full design, or skip to import with placeholders.', 'bosa-elementor-for-woocommerce' ),
					'dep_install_all'    => __( 'Install & Activate', 'bosa-elementor-for-woocommerce' ),
					'dep_installing'     => __( 'Installing…', 'bosa-elementor-for-woocommerce' ),
					'dep_installed'      => __( 'Installed ✓', 'bosa-elementor-for-woocommerce' ),
					'dep_install_failed' => __( 'Failed', 'bosa-elementor-for-woocommerce' ),
					'dep_skip'           => __( 'Skip & Import Anyway', 'bosa-elementor-for-woocommerce' ),
					'tabs'           => [
						'kits'     => __( 'Kits', 'bosa-elementor-for-woocommerce' ),
						'pages'    => __( 'Pages', 'bosa-elementor-for-woocommerce' ),
						'sections' => __( 'Sections', 'bosa-elementor-for-woocommerce' ),
					],
				],
			]
		);
	}

	/**
	 * Enqueue the canvas button script and style inside Elementor's preview iframe.
	 *
	 * @return void
	 */
	public function enqueue_preview_assets() {
		wp_enqueue_style(
			'bew-template-library-canvas',
			BEW_TL_URL . 'assets/css/canvas.css',
			[],
			BEW_VERSION
		);

		wp_enqueue_script(
			'bew-template-library-canvas',
			BEW_TL_URL . 'assets/js/canvas.js',
			[],
			BEW_VERSION,
			true
		);

		wp_localize_script(
			'bew-template-library-canvas',
			'BEWTemplateLibraryCanvas',
			[
				'source' => 'bew',
			]
		);
	}

	/**
	 * Inject Bosa source config into Elementor's editor settings object.
	 *
	 * @param array $settings Elementor editor settings array.
	 * @return array
	 */
	public function inject_editor_settings( array $settings ) {
		$settings['bew_template_library'] = [
			'source'   => 'bew',
			'rest_url' => esc_url_raw( rest_url( 'bew/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		];
		return $settings;
	}

	/**
	 * @return array<string, string[]>
	 */
	private function get_category_lists_for_modal() {
		$lists = apply_filters( 'bosa_ewc_category_lists', [] );
		return is_array( $lists ) ? $lists : [];
	}

	/**
	 * @param string $relative_path Path relative to the module root.
	 * @return string
	 */
	private function asset_version( $relative_path ) {
		static $cache = [];

		if ( isset( $cache[ $relative_path ] ) ) {
			return $cache[ $relative_path ];
		}

		$file    = BEW_TL_DIR . ltrim( $relative_path, '/' );
		$version = file_exists( $file ) ? (string) filemtime( $file ) : BEW_VERSION;

		$cache[ $relative_path ] = $version;

		return $version;
	}
}
