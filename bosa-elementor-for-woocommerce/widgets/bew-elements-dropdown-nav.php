<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Dropdown_Nav extends BEW_Settings {

	public function get_name() {
		return 'bew-elements-dropdown-nav';
	}

	public function get_title() {
		return esc_html__( 'Dropdown Nav', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon() {
		return 'bew-widget bew-icon-dropdown';
	}

	public function get_keywords() {
		return [ 'bew', 'dropdown nav', 'bew dropdown nav', 'bew nav', 'woo', 'woocommerce', 'nav' ];
	}

	public function get_menus() {
        $list  = [ '' => esc_html__( '— Select Menu —', 'bosa-elementor-for-woocommerce' ) ];
        $menus = wp_get_nav_menus();
        foreach ( $menus as $menu ) {
            $list[ $menu->slug ] = $menu->name;
        }
        return $list;
    }

	protected function register_controls() {

		$this->start_controls_section(
			'bew_dropdown_nav',
			[
				'label' => esc_html__( 'Content', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'dropdown_nav_menu',
			[
				'label' 		=> esc_html__( 'Select Menu', 'bosa-elementor-for-woocommerce' ),
				'type' 			=> Controls_Manager::SELECT,
				'default' 		=> '',
				'options' 		=> $this->get_menus(),
			]
		);

		$this->get_item_visibility( 'label_switcher', esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Show', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Hide', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->add_control(
			'label',
			[
				'label' 		=> esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
				'type' 			=> Controls_Manager::TEXT,
				'default' 		=> esc_html__( 'All Categories', 'bosa-elementor-for-woocommerce' ),
				'condition' => [
					'label_switcher' => 'yes',
				],
			]
		);

		$this->add_control(
			'icon_switcher',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'Hide', 'bosa-elementor-for-woocommerce' ),
				'selectors_dictionary' => [
					'yes' => 'block',
					'' => 'none',
				],
				'default' => 'yes',
				'selectors' => [
		          '{{WRAPPER}} .bew-category-nav-container .bew-category-parent-label .bew-icon' => 'display: {{VALUE}};',
		        ],
			]
		);

		$this->add_control(
			'drop_icon_switcher',
			[
				'label' => esc_html__( 'Drop Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'Hide', 'bosa-elementor-for-woocommerce' ),
				'selectors_dictionary' => [
					'yes' => 'block',
					'' => 'none',
				],
				'default' => 'yes',
				'selectors' => [
		          '{{WRAPPER}} .bew-category-nav-container .bew-category-parent-label:after' => 'display: {{VALUE}};',
		        ],
			]
		);

		$this->add_control(
			'dropdown_show_on_click_switcher',
			[
				'label' => esc_html__( 'Show on Click', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'Hide', 'bosa-elementor-for-woocommerce' ),
				'default' => 'no',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_item',
			[
				'label' 		=> esc_html__( 'Item', 'bosa-elementor-for-woocommerce' ),
				'tab' 			=> Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'title_typography', '.bew-category-nav-container .bew-category-menu-label' );

		$this->start_controls_tabs(
			'item_tabs'
		);

		$this->start_controls_tab(
			'item_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'title_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav-container .bew-category-menu-label', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'item_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-category-nav-container .bew-category-parent-label',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'item_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'title_hov_color',
			[
				'label' => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-category-nav-container:hover .bew-category-parent-label .bew-category-menu-label'  => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'item_hov_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-category-nav-container:hover .bew-category-parent-label',
			]
		);

		$this->get_normal_color( 'item_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav-container:hover .bew-category-parent-label', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr1',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'item_border', '.bew-category-nav-container .bew-category-parent-label' );

		$this->get_border_radius( 'item_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav-container .bew-category-parent-label', 'border-radius' );

		$this->get_margin( 'item_margin', '.bew-category-nav-container .bew-category-parent-label' );

		$this->get_padding( 'item_padding', '.bew-category-nav-container .bew-category-parent-label' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_dropdown_nav_style',
			[
				'label' => esc_html__( 'Icon Group', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'size' => 22,
				],
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
					'{{WRAPPER}} .bew-category-nav-container .bew-icon, {{WRAPPER}} .bew-category-nav-container .bew-category-parent-label:after' => 'font-size: {{SIZE}}{{UNIT}};',
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
					'{{WRAPPER}} .bew-category-nav-container .bew-icon path, {{WRAPPER}} .bew-category-nav-container .bew-category-parent-label:after' => 'fill: {{VALUE}}; color: {{VALUE}};'
				],
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
			'icon_hov_color',
			[
				'label' => esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bew-category-nav-container:hover .bew-category-parent-label path, {{WRAPPER}} .bew-category-nav-container:hover .bew-category-parent-label:after' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr2',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_margin( 'icon_margin', '.bew-category-nav-container .bew-icon' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_dropdown',
			[
				'label' => esc_html__( 'Dropdown', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
	      'menu_width',
	      [
	        'label' => esc_html__( 'Menu Width', 'bosa-elementor-for-woocommerce' ),
	        'type' => \Elementor\Controls_Manager::SLIDER,
	        'size_units' => [ 'px', '%' ],
	        'default' => [
	          'unit' => 'px',
	          'size' => '',
	        ],
	        'range' => [
	          'px' => [
	            'min' => 0,
	            'max' => 1170,
	            'step' => 10,
	          ],
	          '%' => [
	            'min' => 0,
	            'max' => 100,
	          ],
	        ],
	        'selectors' => [
	          '{{WRAPPER}} .bew-category-nav' => 'width: {{SIZE}}{{UNIT}};',
	        ],
	      ]
	    );

		$this->get_title_typography( 'dropdown_title_typography', '.bew-category-nav ul li a' );

	    $this->add_control(
			'dropdown_divider_switcher',
			[
				'label' => esc_html__( 'Divider', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Show', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'Hide', 'bosa-elementor-for-woocommerce' ),
				'default' => 'yes',
				'selectors_dictionary' => [
					'yes' => '',
					'' => 'none',
				],
				'selectors' => [
					'{{WRAPPER}} .bew-category-nav ul li a' => 'border: {{VALUE}};'
		        ],
			]
		);

	    $this->add_control(
			'divider_color',
			[
				'label' => esc_html__('Divider Color','bosa-elementor-for-woocommerce'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}}  .bew-category-nav ul li a'=> 'border-color: {{VALUE}}',
				],
				'condition' => [
					'dropdown_divider_switcher' => 'yes',
				],
			]
		);

		$this->start_controls_tabs(
			'dropdown_tabs'
		);

		$this->start_controls_tab(
			'dropdown_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'dorpdown_text_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav ul li a', 'color' );

		$this->get_normal_color( 'dorpdown_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav', 'background-color' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'dropdown_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'dorpdown_text__hover_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav ul li a:hover', 'color' );

		$this->get_normal_color( 'dorpdown_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-category-nav:hover', 'border-color' );
		
		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr3',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'dropdown_border', '.bew-category-nav' );

		$this->get_margin( 'dropdown_margin', '.bew-category-nav-container .bew-category-nav ul li a' );

		$this->get_padding( 'dropdown_padding', '.bew-category-nav-container .bew-category-nav ul li a' );

		$this->end_controls_section();

	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$class = "";
		if ( isset( $settings['dropdown_show_on_click_switcher'] ) && 'yes' === $settings['dropdown_show_on_click_switcher'] ) {
		 	$class = "bew-clickable";
		}

		$nav_menu_slug  = isset( $settings['dropdown_nav_menu'] ) ? sanitize_text_field( $settings['dropdown_nav_menu'] ) : '';
		$nav_menu_items = ( '' !== $nav_menu_slug ) ? wp_get_nav_menu_items( $nav_menu_slug ) : false;

		if ( $nav_menu_items !== false && count( $nav_menu_items ) > 0 ) { ?>
			<div class="bew-category-nav-container <?php echo esc_attr( $class ); ?>">
				<button type="button" aria-haspopup="true" aria-expanded="false" class="bew-category-parent-label">
	                <span class="bew-icon">
	                    <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	                     width="32px" height="20px" viewBox="0 0 32 20" enable-background="new 0 0 32 20" xml:space="preserve">
	                        <path fill="#fff" d="M29.958,4.141H2.042C1.466,4.141,1,3.47,1,2.641s0.466-1.5,1.042-1.5h27.917c0.575,0,1.042,0.672,1.042,1.5
	                            S30.533,4.141,29.958,4.141z"/>
	                        <path fill="#fff" d="M29.958,11.503H2.042c-0.575,0-1.042-0.672-1.042-1.5c0-0.828,0.466-1.5,1.042-1.5h27.917
	                            c0.575,0,1.042,0.672,1.042,1.5C31,10.831,30.533,11.503,29.958,11.503z"/>
	                        <path fill="#fff" d="M29.958,18.864H2.042c-0.575,0-1.042-0.672-1.042-1.5s0.466-1.5,1.042-1.5h27.917
	                            c0.575,0,1.042,0.672,1.042,1.5S30.533,18.864,29.958,18.864z"/>
	                    </svg>
	                </span>
	            	<?php if ( isset( $settings['label_switcher'] ) && 'yes' === $settings['label_switcher'] ) {?>
		                <span class="bew-category-menu-label">
		                    <?php echo isset( $settings['label'] ) ? esc_html( $settings['label'] ) : ''; ?>
		                </span>
	         	 	<?php } ?>
                </button>
				<nav class="bew-category-nav">
	                <?php
	                $walker = class_exists( '\ElementsKit_Lite\ElementsKit_Menu_Walker' ) ? new \ElementsKit_Lite\ElementsKit_Menu_Walker() : null;
	                wp_nav_menu( array(
	                    'container'     => 'div',
	                    'container_id'  => 'bew-megamenu-' . esc_attr( $nav_menu_slug ),
	                    'menu'          => $nav_menu_slug,
	                    'menu_class'    => 'bew-category-dropmenu',
	                    'walker'        => $walker,
	                ) );
                	?>
			    </nav>
			</div>
			<?php 
		}
	}
}