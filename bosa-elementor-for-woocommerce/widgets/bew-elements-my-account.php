<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_My_Account extends BEW_Settings {

	public function get_name(){
		return 'bew-elements-my-account';
	}

	public function get_title(){
		return esc_html__( 'Woo My Account', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon(){
		return 'bew-widget bew-icon-account';
	}

	public function get_keywords(){
		return [ 'bew', 'account', 'bew my account', 'my account', 'woo', 'woocommerce', 'woo my account' ];
	}

	protected function register_controls(){
		
		$this->start_controls_section(
			'bew_my_account',
			[
				'label' => esc_html__( 'Content', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
                'label',
                [
                    'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'My Account', 'bosa-elementor-for-woocommerce' ),
                    'label_block' => true,
                ]
        );

        $this->get_item_visibility( 'label_tool_tip', esc_html__( 'Label Tool Tip', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

        $this->get_item_visibility( 'my_account_popup', esc_html__( 'Popup Login', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->add_control(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'far fa-user',
					'library' => 'fa-regular',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_my_account_style',
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
					'{{WRAPPER}} .bew-account-inner .bew-icon' => 'font-size: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .bew-account-inner .bew-icon' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-account-inner .bew-icon',
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
					'{{WRAPPER}} .bew-account-inner .bew-icon:hover' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-account-inner .bew-icon:hover',
			]
		);

		$this->get_normal_color( 'icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-account-inner .bew-icon:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr1',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'icon_border', '.bew-account-inner .bew-icon' );

		$this->get_border_radius( 'icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-account-inner .bew-icon', 'border-radius' );

		$this->get_margin( 'icon_margin', '.bew-account-inner .bew-icon' );

		$this->get_padding( 'icon_padding', '.bew-account-inner .bew-icon' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_products_label',
			[
				'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs(
			'label_tabs'
		);

		$this->start_controls_tab(
			'label_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'label_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'label_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip',
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
					'{{WRAPPER}} .bew-account-inner a:hover h4'  => 'color: {{VALUE}}',
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
				'selector' => '{{WRAPPER}} .bew-account-inner a:hover h4',
			]
		);

		$this->get_normal_color( 'label_hov_border_color', esc_html__( 'Border Color', 'bosa-elementor-for-woocommerce' ), '.bew-account-inner a:hover h4', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

	    $this->get_border_attr( 'label_border', '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip' );

		$this->get_border_radius( 'label_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip', 'border-radius' );

		$this->get_title_typography( 'label_typography', '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip' );

		$this->get_margin( 'label_margin', '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip' );

		$this->get_padding( 'label_padding', '.bew-account-inner h4, {{WRAPPER}} .bew-account-inner .bew-tooltip' );

		$this->end_controls_section();

	}

	protected function render(){
		$settings = $this->get_settings_for_display();
		$show_popup = isset( $settings['my_account_popup'] ) && 'yes' === $settings['my_account_popup'];
		$label_tool_tip = isset( $settings['label_tool_tip'] ) ? sanitize_key( $settings['label_tool_tip'] ) : 'no';
		$label = isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '';

		// A logged-in visitor already has an account, so the popup is
		// real-visitor-facing only while logged out -- but that also makes
		// it invisible while editing in Elementor, since the editor is
		// always used logged in, which reads as "enabled but not working".
		// is_edit_mode() is true only inside Elementor's own live-preview
		// request, never on a real front-end view, so allowing it there
		// too makes the popup previewable without changing real-visitor
		// behavior at all.
		$show_popup_ui = $show_popup && ( ! is_user_logged_in() || Plugin::$instance->editor->is_edit_mode() );

		if ( $show_popup_ui ) {
	        $login_id = "bew_popuplogin";
	        $login_link = '#';
		}else {
	        $login_id = "defaultlogin";
	        $login_link = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
		}
		if ( class_exists( 'WooCommerce' ) ) { ?>
	        <div class="bew-account-inner bew-widget-inner">
	        	<a id="<?php echo esc_attr($login_id); ?>" href="<?php echo esc_url($login_link); ?>">
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
	        </div> 
	        <?php
	        if ( $show_popup_ui ) { ?>
		        <div class="bew-account-login-wrapper">
		            <div class="bew-account-login-inner">
		            	<a href="#" class="bew-toggle-icon"><i class="fa fa-close"></i></a>
			            <div id="bew-account-popup">
			                <?php echo do_shortcode( '[woocommerce_my_account]' ); ?>
			            </div>
		            </div>
		    	</div>
    			<?php
    		}
    	} else {
 			$error = esc_html__( 'WooCommerce plugin not installed. Please verify plugins are active.', 'bosa-elementor-for-woocommerce' ); ?>
 			<div class="bew-editor-error">
		   		<?php echo esc_html( $error ); ?>
		    </div>
	 	<?php
	 	}	
    }
}