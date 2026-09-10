<?php
/**
 * BEW Preloader — Elementor Site Settings tab.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Tab_Base;

class BEW_Preloader_Tab extends Tab_Base {

	public function get_id() {
		return 'bew-preloader';
	}

	public function get_title() {
		return esc_html__( 'BEW Preloader', 'bosa-elementor-for-woocommerce' );
	}

	public function get_group() {
		return 'settings';
	}

	public function get_icon() {
		return 'eicon-loading';
	}

	protected function register_tab_controls() {

		// ── Content ────────────────────────────────────────────────────────
		$this->start_controls_section(
			'bew_preloader_content_section',
			[
				'label' => esc_html__( 'Preloader', 'bosa-elementor-for-woocommerce' ),
				'tab'   => $this->get_id(),
			]
		);

		$this->add_control(
			'bew_preloader_source',
			[
				'label'   => esc_html__( 'Loader Source', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'builtin' => [
						'title' => esc_html__( 'Built-in Preloader', 'bosa-elementor-for-woocommerce' ),
						'icon'  => 'eicon-animation',
					],
					'custom'  => [
						'title' => esc_html__( 'Custom Image', 'bosa-elementor-for-woocommerce' ),
						'icon'  => 'eicon-image',
					],
				],
				'default'   => 'builtin',
				'toggle'    => false,
			]
		);

		$preloader_options = [];
		for ( $i = 1; $i <= 10; $i++ ) {
			$preloader_options[ 'preloader' . $i ] = sprintf(
				/* translators: %d: preloader number */
				esc_html__( 'Preloader %d', 'bosa-elementor-for-woocommerce' ),
				$i
			);
		}

		$this->add_control(
			'bew_preloader_builtin',
			[
				'label'   => esc_html__( 'Built-in Preloader', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'preloader1',
				'options' => $preloader_options,
				'condition' => [
					'bew_preloader_source' => 'builtin',
				],
			]
		);

		$this->add_control(
			'bew_preloader_builtin_preview',
			[
				'type'      => Controls_Manager::RAW_HTML,
				'raw'       => '<div id="bew-preloader-preview" style="text-align:center;padding:10px 0;"><img src="" style="max-width:80px;max-height:80px;" /></div>'
					. '<script>'
					. 'jQuery(function($){'
					. 'function bewPreloaderPreview(){'
					. 'var v=$("[data-setting=bew_preloader_builtin]").val()||"preloader1";'
					. '$("#bew-preloader-preview img").attr("src","' . esc_url( BEW_URL ) . 'modules/preloader/assets/images/"+v+".gif");'
					. '}'
					. 'bewPreloaderPreview();'
					. '$(document).on("change","[data-setting=bew_preloader_builtin]",bewPreloaderPreview);'
					. '});'
					. '</script>',
				'condition' => [
					'bew_preloader_source' => 'builtin',
				],
			]
		);

		$this->add_control(
			'bew_preloader_custom_image',
			[
				'label'   => esc_html__( 'Custom Image', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
				'condition' => [
					'bew_preloader_source' => 'custom',
				],
			]
		);

		$this->add_responsive_control(
			'bew_preloader_custom_size',
			[
				'label'   => esc_html__( 'Custom Image Size', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => [ 'px' => [ 'min' => 20, 'max' => 400 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-preloader-image' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
				],
				'condition' => [
					'bew_preloader_source' => 'custom',
				],
			]
		);

		$this->end_controls_section();

		// ── Style ──────────────────────────────────────────────────────────
		$this->start_controls_section(
			'bew_preloader_style_section',
			[
				'label'     => esc_html__( 'Style', 'bosa-elementor-for-woocommerce' ),
				'tab'       => $this->get_id(),
			]
		);

		$this->add_control(
			'bew_preloader_bg_color',
			[
				'label'     => esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .bew-preloader-overlay' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_preloader_image_size',
			[
				'label' => esc_html__( 'Loader Size', 'bosa-elementor-for-woocommerce' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [ 'size' => 42, 'unit' => 'px' ],
				'range' => [ 'px' => [ 'min' => 20, 'max' => 400 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-preloader-image' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
				],
				'condition' => [
					'bew_preloader_source' => 'builtin',
				],
			]
		);

		$this->add_control(
			'bew_preloader_z_index',
			[
				'label'     => esc_html__( 'Z-index', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 99999,
				'min'       => 0,
				'selectors' => [
					'{{WRAPPER}} .bew-preloader-overlay' => 'z-index: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'bew_preloader_fade_duration',
			[
				'label'       => esc_html__( 'Fade Out Duration (ms)', 'bosa-elementor-for-woocommerce' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 400,
				'min'         => 0,
				'max'         => 3000,
				'step'        => 50,
			]
		);

		$this->end_controls_section();
	}
}
