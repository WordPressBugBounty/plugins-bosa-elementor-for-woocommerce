<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Yith_Wishlist extends BEW_Settings {

	public function get_name(){
		return 'bew-elements-yith-wishlist';
	}

	public function get_title(){
		return esc_html__( 'Woo YITH Wishlist', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon(){
		return 'bew-widget bew-icon-wishlist';
	}

	public function get_keywords(){
		return [ 'bew', 'wishlist', 'bew wishlist', 'woo', 'woocommerce', 'yith', 'yith wishlist', 'woo yith wishlist' ];
	}

	protected function register_controls(){
		
		$this->start_controls_section(
			'bew_yith_wishlist',
			[
				'label' => esc_html__( 'Content', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
                'label',
                [
                    'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Wishlist', 'bosa-elementor-for-woocommerce' ),
                    'label_block' => true,
                ]
        );

        $this->get_item_visibility( 'label_tool_tip', esc_html__( 'Label Tool Tip', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->add_control(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'far fa-heart',
					'library' => 'fa-regular',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_yith_wishlist_style',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'selectors' => [
					'{{WRAPPER}} .bew-wishlist-inner .bew-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs(
			'icon_tabs'
		);

		$this->start_controls_tab(
			'icon_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label' => esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-wishlist-inner .bew-icon' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'icon_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-wishlist-inner .bew-icon',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'icon_hov_txt_color',
			[
				'label' => esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-wishlist-inner .bew-icon:hover' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'icon_hov_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-wishlist-inner .bew-icon:hover',
			]
		);

		$this->get_normal_color( 'icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-wishlist-inner .bew-icon:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr1',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'icon_border', '.bew-wishlist-inner .bew-icon' );

		$this->get_border_radius( 'icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-wishlist-inner .bew-icon', 'border-radius' );

		$this->get_margin( 'icon_margin', '.bew-wishlist-inner .bew-icon' );

		$this->get_padding( 'icon_padding', '.bew-wishlist-inner .bew-icon' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_products_label',
			[
				'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'label_typography', '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip' );

		$this->start_controls_tabs(
			'label_tabs'
		);

		$this->start_controls_tab(
			'label_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'label_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'label_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'label_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'label_hov_color',
			[
				'label' => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-wishlist-inner a:hover h4'  => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'label_hov_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-wishlist-inner a:hover h4',
			]
		);

		$this->get_normal_color( 'label_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-wishlist-inner a:hover h4', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr2',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'label_border', '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip' );

		$this->get_border_radius( 'label_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip', 'border-radius' );

		$this->get_margin( 'label_margin', '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip' );

		$this->get_padding( 'label_padding', '.bew-wishlist-inner h4, {{WRAPPER}} .bew-wishlist-inner .bew-tooltip' );

		$this->end_controls_section();

	}
	protected function render(){
		$settings = $this->get_settings_for_display();
		$label_tool_tip = isset( $settings['label_tool_tip'] ) ? sanitize_key( $settings['label_tool_tip'] ) : 'no';
		$label          = isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '';
		$wishlist_url = '';

		if ( function_exists( 'YITH_WCWL' ) && class_exists( 'WooCommerce' ) ) { 
			$wishlist_url = YITH_WCWL()->get_wishlist_url(); ?>
		    <div class="bew-wishlist-inner bew-widget-inner">
		    	<a href="<?php echo esc_url( $wishlist_url ); ?>">
		    		<span class="bew-icon">
		    			<?php Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] ); ?>
		    		</span>
		    		<?php if ( 'yes' !== $label_tool_tip ) { ?>
			   			<h4><?php echo esc_html( $label ); ?></h4>			
			      	<?php } ?>
		  		</a>
		  		<?php if ( 'yes' === $label_tool_tip ) { ?>
					<span class="bew-tooltip">
		                <?php echo esc_html( $label ); ?>
		            </span> 
		        <?php } ?> 
		    </div> <?php 
		} else {
		 	$error = esc_html__( 'YITH Wishlist plugin not installed. Please verify plugin is active.', 'bosa-elementor-for-woocommerce' );
		 	if ( ! class_exists( 'WooCommerce' ) ) {
	 				$error = esc_html__( 'YITH Wishlist and WooCommerce plugin not installed. Please verify plugins are active.', 'bosa-elementor-for-woocommerce' );
	 			}
	 			?>
			<div class="bew-editor-error">
	   			<?php echo esc_html( $error ); ?>
	    	</div>
  		<?php
  		}
	}
}