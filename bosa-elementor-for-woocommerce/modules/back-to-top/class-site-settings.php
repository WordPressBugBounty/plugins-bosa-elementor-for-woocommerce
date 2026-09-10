<?php
/**
 * BEW Back to Top — Elementor Site Settings tab.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Tab_Base;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class BEW_Back_To_Top_Tab extends Tab_Base {

	public function get_id() {
		return 'bew-back-to-top';
	}

	public function get_title() {
		return esc_html__( 'BEW Back to Top', 'bosa-elementor-for-woocommerce' );
	}

	public function get_group() {
		return 'settings';
	}

	public function get_icon() {
		return 'eicon-arrow-up';
	}

	protected function register_tab_controls() {

		// ── Content ────────────────────────────────────────────────────────
		$this->start_controls_section(
			'bew_btt_content_section',
			[
				'label' => esc_html__( 'Back to Top', 'bosa-elementor-for-woocommerce' ),
				'tab'   => $this->get_id(),
			]
		);

		$this->add_control(
			'bew_btt_icon',
			[
				'label'   => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-arrow-up',
					'library' => 'fa-solid',
				],
			]
		);

		$this->add_control(
			'bew_btt_text',
			[
				'label'       => esc_html__( 'Button Text', 'bosa-elementor-for-woocommerce' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Back to Top', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'bew_btt_tooltip',
			[
				'label'       => esc_html__( 'Tooltip', 'bosa-elementor-for-woocommerce' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => esc_html__( 'Scroll to top', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'bew_btt_scroll_offset',
			[
				'label'       => esc_html__( 'Scroll Offset (px)', 'bosa-elementor-for-woocommerce' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 300,
				'min'         => 0,
				'step'        => 10,
				'description' => esc_html__( 'Button appears after scrolling this distance.', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'bew_btt_position',
			[
				'label'   => esc_html__( 'Position', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'  => [
						'title' => esc_html__( 'Bottom Left', 'bosa-elementor-for-woocommerce' ),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => esc_html__( 'Bottom Right', 'bosa-elementor-for-woocommerce' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'default'   => 'right',
				'toggle'    => false,
			]
		);

		$this->add_responsive_control(
			'bew_btt_offset_x',
			[
				'label'   => esc_html__( 'Horizontal Offset', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 20, 'unit' => 'px' ],
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 200 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button.bew-btt-right' => 'right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bew-btt-button.bew-btt-left'  => 'left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_btt_offset_y',
			[
				'label'   => esc_html__( 'Vertical Offset', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [ 'size' => 20, 'unit' => 'px' ],
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 200 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Style ──────────────────────────────────────────────────────────
		$this->start_controls_section(
			'bew_btt_style_section',
			[
				'label'     => esc_html__( 'Style', 'bosa-elementor-for-woocommerce' ),
				'tab'       => $this->get_id(),
			]
		);

		$this->add_responsive_control(
			'bew_btt_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'bosa-elementor-for-woocommerce' ),
				'type'  => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 8, 'max' => 80 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button i'   => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bew-btt-button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_btt_button_width',
			[
				'label' => esc_html__( 'Button Width', 'bosa-elementor-for-woocommerce' ),
				'type'  => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 20, 'max' => 200 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_btt_button_height',
			[
				'label' => esc_html__( 'Button Height', 'bosa-elementor-for-woocommerce' ),
				'type'  => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 20, 'max' => 200 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_btt_padding',
			[
				'label'      => esc_html__( 'Padding', 'bosa-elementor-for-woocommerce' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bew-btt-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_btt_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bew-btt-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'bew_btt_typography',
				'selector'  => '{{WRAPPER}} .bew-btt-button .bew-btt-text',
				'condition' => [ 'bew_btt_text!' => '' ],
			]
		);

		$this->start_controls_tabs( 'bew_btt_color_tabs' );

		$this->start_controls_tab(
			'bew_btt_normal_tab',
			[ 'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ) ]
		);

		$this->add_control(
			'bew_btt_color',
			[
				'label'     => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'bew_btt_background',
			[
				'label'     => esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'bew_btt_hover_tab',
			[ 'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ) ]
		);

		$this->add_control(
			'bew_btt_hover_color',
			[
				'label'     => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button:hover' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'bew_btt_hover_background',
			[
				'label'     => esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'bew_btt_border',
				'selector'  => '{{WRAPPER}} .bew-btt-button',
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'bew_btt_box_shadow',
				'selector' => '{{WRAPPER}} .bew-btt-button',
			]
		);

		$this->add_control(
			'bew_btt_opacity',
			[
				'label' => esc_html__( 'Opacity', 'bosa-elementor-for-woocommerce' ),
				'type'  => Controls_Manager::SLIDER,
				'range' => [ 'px' => [ 'min' => 0, 'max' => 1, 'step' => 0.05 ] ],
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button.bew-btt-visible' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_control(
			'bew_btt_entrance_animation',
			[
				'label'   => esc_html__( 'Entrance Animation', 'bosa-elementor-for-woocommerce' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-in',
				'options' => [
					''         => esc_html__( 'None', 'bosa-elementor-for-woocommerce' ),
					'fade-in'  => esc_html__( 'Fade In', 'bosa-elementor-for-woocommerce' ),
					'slide-up' => esc_html__( 'Slide Up', 'bosa-elementor-for-woocommerce' ),
					'float'    => esc_html__( 'Float', 'bosa-elementor-for-woocommerce' ),
				],
			]
		);

		$this->add_control(
			'bew_btt_z_index',
			[
				'label'     => esc_html__( 'Z-index', 'bosa-elementor-for-woocommerce' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 999,
				'min'       => 0,
				'selectors' => [
					'{{WRAPPER}} .bew-btt-button' => 'z-index: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
