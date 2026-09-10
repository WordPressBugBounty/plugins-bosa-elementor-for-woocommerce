<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Search extends BEW_Settings {

	public function get_name(){
		return 'bew-elements-search';
	}

	public function get_title(){
		return esc_html__( 'Woo Search', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon(){
		return 'bew-widget bew-icon-search';
	}

	public function get_keywords(){
		return [ 'search', 'products search', 'bew search', 'woo', 'woocommerce', 'woo search' ];
	}

	protected function register_controls(){
		
		$this->start_controls_section(
			'section_search',
			[
				'label' 		=> esc_html__( 'Search', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'placeholder',
			[
				'label' 		=> esc_html__( 'Placeholder', 'bosa-elementor-for-woocommerce' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> esc_html__( 'Search', 'bosa-elementor-for-woocommerce' ),
				'placeholder' 	=> esc_html__( 'Search', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'categories_placeholder',
			[
				'label' 		=> esc_html__( 'All Categories Placeholder', 'bosa-elementor-for-woocommerce' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> esc_html__( 'All Categories', 'bosa-elementor-for-woocommerce' ),
				'placeholder' 	=> esc_html__( 'All Categories', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_input',
			[
				'label' 		=> esc_html__( 'Input', 'bosa-elementor-for-woocommerce' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'title_typography', '.bew-product-search-input input' );

		$this->start_controls_tabs('tabs_input_style');

		$this->start_controls_tab(
			'tab_input_normal',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'input_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-input input', 'color' );

		$this->get_normal_color( '_input_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-input input', 'background-color' );

		$this->add_control(
			'placeholder_color',
			[
				'label' => esc_html__( 'Placeholder Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-product-search-input input::-webkit-input-placeholder' => 'color: {{VALUE}}',
					'{{WRAPPER}} .bew-product-search-input input::-moz-placeholder'          => 'color: {{VALUE}}',
					'{{WRAPPER}} .bew-product-search-input input:-ms-input-placeholder'       => 'color: {{VALUE}}',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_input_focus',
			[
				'label' => esc_html__( 'Focus', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'input_focus_input_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-input input:focus', 'color' );

		$this->get_normal_color( 'input_focus_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-input input:focus', 'background-color' );

		$this->get_normal_color( 'input_focus_border_color', esc_html__( 'Border Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-form .bew-product-search-input input:focus', 'border-color' );

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' 			=> 'input_focus_box_shadow',
				'selector' 		=> '{{WRAPPER}} .bew-product-search-input input:focus',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr1',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'input_border', '.bew-product-search-form .bew-product-search-input input' );

		$this->get_border_radius( 'input_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-input input', 'border-radius' );

		$this->add_responsive_control(
			'input_width',
			[
				'label' => esc_html__( 'Width', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bew-product-search-input' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->get_padding( 'input_padding', '.bew-product-search-input input' );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_categories',
			[
				'label' 		=> esc_html__( 'All Categories', 'bosa-elementor-for-woocommerce' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'categories_title_typography', '.bew-product-search-select .bew-select-styled' );


		$this->get_normal_color( 'categories_input_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-styled', 'color' );

		$this->get_normal_color( 'categories_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-styled', 'background-color' );

		$this->add_control(
			'hr2',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'categories_border', '.bew-product-search-select .bew-select-styled' );

		$this->get_border_radius( 'categories_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-styled', 'border-radius' );

		$this->add_responsive_control(
			'categories_width',
			[
				'label' => esc_html__( 'Width', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bew-product-search-select' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->get_padding( 'categories_padding', '.bew-product-search-select .bew-select-styled' );

		$this->add_control(
			'dropdown_heading',
			[
				'label' => esc_html__( 'Categories Dropdown', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'after',
			]
		);

		$this->get_title_typography( 'dropdown_categories_title_typography', '.bew-product-search-select .bew-select-options' );

		$this->start_controls_tabs('tabs_dropdown_categories_style');

		$this->start_controls_tab(
			'tab_dropdown_categories_normal',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'dropdown_categories_input_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options li', 'color' );

		$this->get_normal_color( 'dropdown_categories_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options', 'background-color' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_dropdown_categories_hover',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'dropdown_categories_hover_input_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options li:hover', 'color' );

		$this->get_normal_color( 'dropdown_categories_hover_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options:hover', 'background-color' );

		$this->get_normal_color( 'dropdown_categories_hover_border_color', esc_html__( 'Border Color', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->get_border_attr( 'dropdown_categories_border', '.bew-product-search-select .bew-select-options' );
		
		$this->get_border_radius( 'dropdown_categories_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-product-search-select .bew-select-options', 'border-radius' );

		$this->end_controls_section();

		$this->start_controls_section(
			'section_icon',
			[
				'label' 		=> esc_html__( 'Icon Button', 'bosa-elementor-for-woocommerce' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' 		=> esc_html__( 'Icon Size', 'bosa-elementor-for-woocommerce' ),
				'type' 			=> Controls_Manager::SLIDER,
				'size_units'	=> ['px'],
				'default' => [
					'size' => 12,
				],
				'selectors' => [
					'{{WRAPPER}} .bew-search-submit button' => 'font-size: {{SIZE}}{{UNIT}};',
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

		$this->get_normal_color( 'icon_color', esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ), '.bew-search-submit button', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'icon_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-search-submit button',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'icon_hov_txt_color', esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ), '.bew-search-submit button:hover', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'icon_hov_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-search-submit button:hover',
			]
		);

		$this->get_normal_color( 'icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-search-submit button:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr3',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'icon_border', '.bew-search-submit button' );

		$this->get_border_radius( 'icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-search-submit button', 'border-radius' );

		$this->add_responsive_control(
			'icon_width',
			[
				'label' => esc_html__( 'Width', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
						'step' => 1,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bew-search-submit button' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->get_padding( 'icon_padding', '.bew-search-submit button' );

		$this->end_controls_section();
	}

	protected function render(){
		$settings    = $this->get_settings_for_display();
		$current_cat = isset( $_GET['product_cat'] ) ? sanitize_text_field( wp_unslash( $_GET['product_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- search/filter forms use GET and do not require a nonce
		$placeholder            = isset( $settings['placeholder'] ) ? $settings['placeholder'] : esc_html__( 'Search', 'bosa-elementor-for-woocommerce' );
		$categories_placeholder  = isset( $settings['categories_placeholder'] ) ? $settings['categories_placeholder'] : esc_html__( 'All Categories', 'bosa-elementor-for-woocommerce' );
		?>

		<form class="d-flex align-items-center bew-product-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="product" />
			<div class="bew-product-search-input">
				<input name="s" type="search" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
			</div>
			<div class="bew-product-search-select">
				<select name="product_cat">
	                <option value=""><?php echo esc_html( $categories_placeholder ); ?></option>
	                <?php
	                $product_cats = get_terms( array(
	                    'taxonomy'   => 'product_cat',
	                    'hide_empty' => false,
	                ) );
	                if ( ! empty( $product_cats ) && ! is_wp_error( $product_cats ) ) {
	                    foreach ( $product_cats as $cat ) {
	                        printf(
	                            '<option value="%s"%s>%s (%d)</option>',
	                            esc_attr( $cat->slug ),
	                            selected( $current_cat, $cat->slug, false ),
	                            esc_html( $cat->name ),
	                            absint( $cat->count )
	                        );
	                    }
	                }
	                ?>
            	</select>
			</div>
			<div class="bew-search-submit">
				<button type="submit" aria-label="<?php echo esc_attr__( 'Search', 'bosa-elementor-for-woocommerce' ); ?>">
					<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
						<circle cx="8.5" cy="8.5" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
						<line x1="13" y1="13" x2="18" y2="18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</button>
			</div>
		</form>
		<?php
	}
}