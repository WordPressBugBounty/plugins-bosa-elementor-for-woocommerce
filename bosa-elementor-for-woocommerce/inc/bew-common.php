<?php

namespace Elementor;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Widget_Base;


/**
 * Elementor common class to use same field on multiple times
 * 
 * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
 */
abstract class BEW_Settings extends Widget_Base {
	
	/**
     * Elementor Category
     * 
     * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
     */
	public function get_categories() {
		return [ 'bosa-elementor-for-woocommerce' ];
	}

	public function get_items_no_res( $id = null, $label = null, $max = 4, $desktop_default = 3, $tablet_default = 3, $mobile_default = 1  ) {
		$this->add_responsive_control(
			$id,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 1,
						'max' => $max,
					],
				],
				'devices' => [ 'desktop', 'tablet', 'mobile' ],
				'desktop_default' => [
					'size' => $desktop_default,
				],
				'tablet_default' => [
					'size' => $tablet_default,
				],
				'mobile_default' => [
					'size' => $mobile_default
				],
			]
		);
	}

	public function get_post_categories(){
		$this->add_control(
			'posts_categories',
			[
				'label' => __( 'Select Categories', 'bosa-elementor-for-woocommerce' ),
                'label_block' => true,
				'type' => \Elementor\Controls_Manager::SELECT2,
				'multiple' => true,
				'default' => [ '1' ],
				'options' => $this->_posts_categories(),
			]
		);
	}

	public function get_items_no( $id="items_no", $label = 'Number of Posts', $max = 100, $default = 10 ){
		$this->add_control(
			$id,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::NUMBER,
				'min' => 1,
				'max' => $max,
				'step' => 1,
				'default' => $default,
				
			]
		);
	}

	public function get_item_visibility( $id = null, $label = null, $label_on = 'Show', $label_off = 'Hide', $default='no' ) {
		$this->add_control(
			$id,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => $label_on,
				'label_off' => $label_off,
				'return_value' => 'yes',
				'default' => $default
			]
		);
	}

	public function get_title_typography($name = null, $selector = null) {
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => $name,
				'label' => __( 'Typography', 'bosa-elementor-for-woocommerce' ),
				'selector' => '{{WRAPPER}} ' . $selector,
				'fields_options' => [
					'typography' => ['default' => 'yes'],
				],
			]
		);
	}

	public function get_normal_color($name = null, $label = null, $selector = null, $property = null) {
		$this->add_control(
			$name,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $selector => $property . ': {{VALUE}}',
				],
			]
		);
	}

	public function get_title_hover_color($selector = null) {
		$this->add_control(
			'hov_title_color',
			[
				'label' => esc_html__( 'Hover Color', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'color: {{VALUE}}',
				],
			]
		);
	}

	public function get_border_attr($name = null, $selector = null) {
		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => $name,
				'label' => esc_html__( 'Border', 'bosa-elementor-for-woocommerce' ),
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);
	}

	public function get_border_radius($name = null, $label = null, $selector = null, $property = 'border-radius') {
		$this->add_responsive_control(
			$name,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} ' . $selector => $property . ': {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	public function get_item_spacing($name = null, $selector = null) {
		$this->add_responsive_control(
			$name,
			[
				'label' => esc_html__( 'Item Spacing', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	public function get_item_margin($name = null, $selector = null, $label = null ) {
		$this->add_responsive_control(
			$name,
			[
				'label' => $label,
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	public function get_margin($name = null, $selector = null) {
		$this->add_responsive_control(
			$name,
			[
				'label' => esc_html__( 'Margin', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

	public function get_padding($name = null, $selector = null) {
		$this->add_responsive_control(
			$name,
			[
				'label' => esc_html__( 'Padding', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
	}

    public function get_column_attr($settings, $desktop_default = 3, $tablet_default = 3, $mobile_default = 1 ){ 

        if(isset($settings['column_no']['size']) && !empty($settings['column_no']['size'])) {
			$desktop_col_no			= $settings['column_no']['size'];
		} else {
			$desktop_col_no			= $desktop_default;
		}
		if(isset($settings['column_no_tablet']['size']) && !empty($settings['column_no_tablet']['size'])) {
			$tablet_col_no          = $settings['column_no_tablet']['size'];
		} else {
			$tablet_col_no          = $tablet_default;
		}
		if(isset($settings['column_no_mobile']['size']) && !empty($settings['column_no_mobile']['size'])) {
			$mobile_col_no          = $settings['column_no_mobile']['size'];
		} else {
			$mobile_col_no          = $mobile_default;
		}

        return 'desktop-col="'.esc_attr( $desktop_col_no ).'" tablet-col="'.esc_attr( $tablet_col_no ).'" mobile-col="'. esc_attr( $mobile_col_no ).'"';

    }

	public function _woocommerce_category( $only_top_level = false ){

		$taxonomy     = 'product_cat';
		$orderby      = 'name';  
		$show_count   = 0;      // 1 for yes, 0 for no
		$pad_counts   = 0;      // 1 for yes, 0 for no
		$hierarchical = 1;      // 1 for yes, 0 for no  
		$title        = '';  
		$empty        = false;
		$args = array(
			'taxonomy'     => $taxonomy,
			'orderby'      => $orderby,
			'show_count'   => $show_count,
			
			'title_li'     => $title,
			'hide_empty'   => $empty
		);

		$woocommerce_categories = array();
		if( $only_top_level ) $woocommerce_categories[0] = __( 'Only Top Level', 'bosa-elementor-for-woocommerce' );
		$woocommerce_categories_obj = get_categories( $args );
		foreach( $woocommerce_categories_obj as $category ) {
			$woocommerce_categories[$category->term_id] = $category->name;
		}

		return $woocommerce_categories;
	}

	public function _posts_categories() {
		$blog_categories    			= [];
        $blog_categories_list 			= get_categories();
        foreach( $blog_categories_list as $blog_category ) {
            $blog_categories[$blog_category->cat_ID] = $blog_category->name;
        }
		return $blog_categories;
	}

	public function _contact_form_list() {
		$args = array(
				'numberposts' => 10,
				'post_type'   => 'wpcf7_contact_form'
			);
		$contact_forms = get_posts( $args );
		$contact_form_list = [];
		$contact_form_list[] = __( '- Select Contact Form -', 'bosa-elementor-for-woocommerce' );

		foreach( $contact_forms as $contact_form ) {
			$contact_form_list[$contact_form->ID] = $contact_form->post_title;
		}
		return $contact_form_list;
	}

	public function get_woocommerce_uncategorized_id() {
		$uncategorized_term_id = [];
		$uncategorized_term_id[0] = get_option( 'default_product_cat' );
		return $uncategorized_term_id;
	}	

	public function get_woocommerce_tags() {
		$terms = get_terms( 'product_tag' );
		$term_array = [];
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			foreach ( $terms as $term ) {
				$term_array[$term->term_id] = $term->name;
			}
		}
		return $term_array;
	}

	public function get_woocommerce_products() {
		$args = array( 'post_type' => 'product', 'posts_per_page' => -1 );
		$products = new \WP_Query( $args );
		$products_list = [];
		while ( $products->have_posts() ) : $products->the_post();
			$products_list[get_the_ID()] = get_the_title();
		endwhile;
		wp_reset_postdata();
		return $products_list;
	}


	/**
	 * Numbered Pagination
	 *
	 * @since	1.0.0
	 * @link	https://codex.wordpress.org/Function_Reference/paginate_links
	 */
	function bew_pagination($query = '', $echo = true) {

		// Arrows with RTL support
		$prev_arrow = is_rtl() ? 'fa fa-angle-right' : 'fa fa-angle-left';
		$next_arrow = is_rtl() ? 'fa fa-angle-left' : 'fa fa-angle-right';

		if (!$query) {
			global $wp_query;
			$query = $wp_query;
		}

		$total = $query->max_num_pages;
		$big = 999999999;

		if ($total > 1) {

			if ($current_page = get_query_var('paged')) {
				$current_page = $current_page;
			} elseif ($current_page = get_query_var('page')) {
				$current_page = $current_page;
			} else {
				$current_page = 1;
			}

			if (get_option('permalink_structure')) {
				if (is_page()) {
					$format = 'page/%#%/';
				} else {
					$format = '/%#%/';
				}
			} else {
				$format = '&paged=%#%';
			}

			$args = apply_filters('bew_pagination_args', array(
				'base' => str_replace($big, '%#%', html_entity_decode(get_pagenum_link($big))),
				'format' => $format,
				'current' => max(1, $current_page),
				'total' => $total,
				'mid_size' => 3,
				'type' => 'list',
				'prev_text' => '<i class="' . $prev_arrow . '"></i>',
				'next_text' => '<i class="' . $next_arrow . '"></i>',
			));

			if ($echo) {
				echo '<div class="bew-pagination clr">' . wp_kses_post(paginate_links($args)) . '</div>';
			} else {
				return '<div class="bew-pagination clr">' . wp_kses_post(paginate_links($args)) . '</div>';
			}
		}
	}

	public function get_img_sizes() {
		global $_wp_additional_image_sizes;

		$sizes = array();
	    $get_intermediate_image_sizes = get_intermediate_image_sizes();

	    foreach($get_intermediate_image_sizes as $_size) {
	        if(in_array($_size, array('thumbnail', 'medium', 'medium_large', 'large'))) {
	            $sizes[ $_size ]['width'] 	= get_option($_size . '_size_w');
	            $sizes[ $_size ]['height'] 	= get_option($_size . '_size_h');
	            $sizes[ $_size ]['crop'] 	= (bool) get_option($_size . '_crop');
	        } elseif(isset($_wp_additional_image_sizes[ $_size ])) {
	            $sizes[ $_size ] = array(
	                'width' 	=> $_wp_additional_image_sizes[ $_size ]['width'],
	                'height' 	=> $_wp_additional_image_sizes[ $_size ]['height'],
	                'crop' 		=> $_wp_additional_image_sizes[ $_size ]['crop'],
	           );
	        }
	    }

	    $image_sizes = array();

		foreach($sizes as $size_key => $size_attributes) {
			$image_sizes[ $size_key ] = ucwords(str_replace('_', ' ', $size_key)) . sprintf(' - %d x %d', $size_attributes['width'], $size_attributes['height']);
		}

		$image_sizes['full'] 	= _x('Full', 'Image Size Control', 'bosa-elementor-for-woocommerce');

	    return $image_sizes;
	}

	

	public function register_button_content_controls( $args = [] ) {
		$default_args = [
			'section_condition' => [],
			'button_default_text' => esc_html__( 'Click here', 'bosa-elementor-for-woocommerce' ),
			'text_control_label' => esc_html__( 'Text', 'bosa-elementor-for-woocommerce' ),
			'alignment_default' => '',
			'icon_exclude_inline_options' => [],
			'dynamic_link' => false
		];

		$args = wp_parse_args( $args, $default_args );

		$this->add_control(
			'button_type',
			[
				'label' => esc_html__( 'Type', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					'' => esc_html__( 'Default', 'bosa-elementor-for-woocommerce' ),
					'info' => esc_html__( 'Info', 'bosa-elementor-for-woocommerce' ),
					'success' => esc_html__( 'Success', 'bosa-elementor-for-woocommerce' ),
					'warning' => esc_html__( 'Warning', 'bosa-elementor-for-woocommerce' ),
					'danger' => esc_html__( 'Danger', 'bosa-elementor-for-woocommerce' ),
				],
				'prefix_class' => 'elementor-button-',
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'read_more_text',
			[
				'label' => $args['text_control_label'],
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => $args['button_default_text'],
				'placeholder' => $args['button_default_text'],
				'condition' => $args['section_condition'],
			]
		);

		if( $args['dynamic_link']){
			$this->add_control(
				'link',
				[
					'label' => esc_html__( 'Link', 'bosa-elementor-for-woocommerce' ),
					'type' => Controls_Manager::URL,
					'dynamic' => [
						'active' => true,
					],
					'placeholder' => esc_html__( 'https://your-link.com', 'bosa-elementor-for-woocommerce' ),
					'default' => [
						'url' => '#',
					],
					'condition' => $args['section_condition'],
				]
			);
		}

		$this->add_responsive_control(
			'align',
			[
				'label' => esc_html__( 'Alignment', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
						'title' => esc_html__( 'Left', 'bosa-elementor-for-woocommerce' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'bosa-elementor-for-woocommerce' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'bosa-elementor-for-woocommerce' ),
						'icon' => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => esc_html__( 'Justified', 'bosa-elementor-for-woocommerce' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'default' => $args['alignment_default'], 
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'size',
			[
				'label' => esc_html__( 'Size', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'sm',
				'options' => self::get_button_sizes(),
				'style_transfer' => true,
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'selected_icon',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::ICONS,
				'fa4compatibility' => 'icon',
				'skin' => 'inline',
				'label_block' => false,
				'condition' => $args['section_condition'],
				'icon_exclude_inline_options' => $args['icon_exclude_inline_options'],
			]
		);

		$this->add_control(
			'icon_align',
			[
				'label' => esc_html__( 'Icon Position', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => esc_html__( 'Before', 'bosa-elementor-for-woocommerce' ),
					'right' => esc_html__( 'After', 'bosa-elementor-for-woocommerce' ),
				],
				'condition' => array_merge( $args['section_condition'], [ 'selected_icon[value]!' => '' ] ),
			]
		);

		$this->add_control(
			'icon_indent',
			[
				'label' => esc_html__( 'Icon Spacing', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-button .elementor-align-icon-right' => 'margin-left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .elementor-button .elementor-align-icon-left' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'view',
			[
				'label' => esc_html__( 'View', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::HIDDEN,
				'default' => 'traditional',
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'button_css_id',
			[
				'label' => esc_html__( 'Button ID', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'title' => esc_html__( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'bosa-elementor-for-woocommerce' ),
				'description' => sprintf(
					/* translators: %1$s: opening <code> tag, %2$s: closing </code> tag */
					esc_html__( 'Please make sure the ID is unique and not used elsewhere on the page this form is displayed. This field allows %1$sA-z 0-9%2$s & underscore chars without spaces.', 'bosa-elementor-for-woocommerce' ),
					'<code>',
					'</code>'
				),
				'separator' => 'before',
				'condition' => $args['section_condition'],
			]
		);
	}

	public function register_button_style_controls( $args = [] ) {
		$default_args = [
			'section_condition' => [],
		];

		$args = wp_parse_args( $args, $default_args );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'button_typography',
				'selector' => '{{WRAPPER}} .read-more-blog',
				'fields_options' => [
					'typography' => ['default' => 'yes'],
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'blog_btn_text_shadow',
				'selector' => '{{WRAPPER}} .read-more-blog',
				'condition' => $args['section_condition'],
			]
		);

		$this->start_controls_tabs( 'button_tabs', [
			'condition' => $args['section_condition'],
		] );

		$this->start_controls_tab(
			'button_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'btn_color',
			[
				'label' => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .read-more-blog' => 'fill: {{VALUE}}; color: {{VALUE}};',
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->get_normal_color('btn_bg_color', esc_html__('Background Color', 'bosa-elementor-for-woocommerce'), '.read-more-blog', 'background-color');

		$this->get_normal_color('btn_border_color', esc_html__('Border Color', 'bosa-elementor-for-woocommerce'), '.read-more-blog', 'border-color');

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
				'condition' => $args['section_condition'],
			]
		);

		$this->add_control(
			'btn_hov_color',
			[
				'label' => esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .read-more-blog:hover, {{WRAPPER}} .read-more-blog:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .read-more-blog:hover svg, {{WRAPPER}} .read-more-blog:focus svg' => 'fill: {{VALUE}};',
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->get_normal_color('btn_hov_bg_color', esc_html__('Background Color', 'bosa-elementor-for-woocommerce'), '.read-more-blog:hover, {{WRAPPER}} .read-more-blog:focus', 'background-color');

		$this->add_control(
			'btn_hov_border_color',
			[
				'label' => esc_html__( 'Border Color', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .read-more-blog:hover, {{WRAPPER}} .read-more-blog:focus' => 'border-color: {{VALUE}};',
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'blog_btn_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .read-more-blog' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => $args['section_condition'],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'blog_button_box_shadow',
				'selector' => '{{WRAPPER}} .read-more-blog',
				'condition' => $args['section_condition'],
			]
		);

		$this->add_responsive_control(
			'btn_margin',
			[
				'label' => esc_html__( 'Margin', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .read-more-blog' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
				'condition' => $args['section_condition'],
			]
		);

		$this->add_responsive_control(
			'btn_padding',
			[
				'label' => esc_html__( 'Padding', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .read-more-blog' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => $args['section_condition'],
			]
		);
	}

	public static function get_button_sizes() {
		return [
			'xs' => esc_html__( 'Extra Small', 'bosa-elementor-for-woocommerce' ),
			'sm' => esc_html__( 'Small', 'bosa-elementor-for-woocommerce' ),
			'md' => esc_html__( 'Medium', 'bosa-elementor-for-woocommerce' ),
			'lg' => esc_html__( 'Large', 'bosa-elementor-for-woocommerce' ),
			'xl' => esc_html__( 'Extra Large', 'bosa-elementor-for-woocommerce' ),
		];
	}

	public function get_product_image_position( $id, $label = "Image Position", $default = 'left' ) {
		// $label is intentionally not used for translation -- __()/esc_html__()
		// require a literal string argument for WordPress's translation
		// extraction tooling to find it, and every current caller (both this
		// plugin and Pro) always passes the same "Image Position" text anyway.
		$this->add_control(
			$id,
			[
				'label' => esc_html__( 'Image Position', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::SELECT,
				'default' => $default,
				'options' => [
					'left' => esc_html__( 'Left', 'bosa-elementor-for-woocommerce' ),
					'right' => esc_html__( 'Right', 'bosa-elementor-for-woocommerce' ),
				],
			]
		);
	}

	/**
	 * Renders BEW's own Compare trigger for the current global $product.
	 * Self-contained: builds the add-to-compare link straight from YITH's
	 * own frontend API rather than relying on any theme hook/markup, so
	 * it renders identically on every theme -- including ones with no
	 * YITH integration of their own, e.g. Hello Elementor.
	 */
	public function render_bew_compare_icon() {
		if ( ! class_exists( '\YITH_WooCompare_Frontend' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$product_id = $product->get_id();

		if ( apply_filters( 'yith_woocompare_remove_compare_link_by_cat', false, $product_id ) ) {
			return;
		}

		$url = \YITH_WooCompare_Frontend::instance()->add_product_url( $product_id );
		?>
		<div class="bew-product-compare">
			<a href="<?php echo esc_url( $url ); ?>" class="bew-compare-link" rel="nofollow" data-product_id="<?php echo absint( $product_id ); ?>">
				<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<polyline points="17 1 21 5 17 9"></polyline>
					<path d="M3 11V9a4 4 0 0 1 4-4h14"></path>
					<polyline points="7 23 3 19 7 15"></polyline>
					<path d="M21 13v2a4 4 0 0 1-4 4H3"></path>
				</svg>
				<span class="bew-compare-tooltip"><?php esc_html_e( 'Compare', 'bosa-elementor-for-woocommerce' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Hooks render_bew_compare_icon() onto the core shop-loop-item hook
	 * for the duration of one widget's own product loop only. Scoped
	 * add/remove (rather than touching the theme's or YITH's own hooks)
	 * so BEW's icon never leaks into unrelated WooCommerce output --
	 * e.g. related products -- rendered later on the same page, and nothing
	 * outside BEW's own widgets is ever affected. Call immediately before
	 * the loop; pair with end_bew_compare_icon_scope() immediately after.
	 */
	public function start_bew_compare_icon_scope() {
		add_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_bew_compare_icon' ], 25 );
	}

	public function end_bew_compare_icon_scope() {
		remove_action( 'woocommerce_after_shop_loop_item', [ $this, 'render_bew_compare_icon' ], 25 );
	}

	/**
	 * Opt-in control shared by BEW's independent product-listing widgets
	 * (Products List/Grid, Grid Carousel, Product Slider, Product Tabs,
	 * Product Accordion, Hot Deals) -- their own custom loops never fire
	 * woocommerce_after_shop_loop_item, so neither the theme's nor YITH's
	 * compare/wishlist buttons ever appear there on their own. Default is
	 * OFF: these widgets ship to thousands of existing installs, and
	 * turning a new button on by default would silently change their
	 * layout. Call inside register_controls(); pair with
	 * render_bew_default_yith_buttons() in render(), gated on this same
	 * setting.
	 */
	public function register_bew_yith_buttons_control() {
		$this->start_controls_section(
			'bew_yith_buttons_section',
			[
				'label' => esc_html__( 'Compare & Wishlist', 'bosa-elementor-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'bew_show_yith_buttons',
			[
				'label' => esc_html__( 'Show Compare & Wishlist Buttons', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'No', 'bosa-elementor-for-woocommerce' ),
				'return_value' => 'yes',
				'default' => '',
				'description' => esc_html__( 'Requires YITH WooCommerce Compare and/or YITH WooCommerce Wishlist to be active.', 'bosa-elementor-for-woocommerce' ),
			]
		);

		// prefix_class, not selectors_dictionary+selectors: confirmed live
		// (via a from-scratch diagnostic reading Elementor's own
		// controls-stack.php/base-data.php) that giving this specific
		// switcher a selectors_dictionary breaks its OWN value resolution
		// entirely -- get_settings() returns it as genuinely absent, not
		// merely "no CSS generated" -- which then fails Conditions::check()
		// for every other control below that references it, even though
		// each of those has its own correctly-resolving default. Rules
		// out: the condition chain itself (still broken with no condition
		// at all on this control), an incomplete dictionary (still broken
		// with both 'yes' and '' keys present), and the control's name
		// (still broken renamed to something else entirely) -- isolated to
		// selectors_dictionary+selectors specifically, on this control.
		// prefix_class doesn't touch this path at all (proven by Compare/
		// Wishlist Label Tooltip below, unaffected before this control's
		// own bug was found) so it's what every control here uses now.
		$this->add_control(
			'bew_yith_buttons_show_label',
			[
				'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'No', 'bosa-elementor-for-woocommerce' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
				],
				'prefix_class' => 'bew-yith-show-label-',
			]
		);

		$this->add_control(
			'bew_yith_buttons_compare_label',
			[
				'label' => esc_html__( 'Compare Label', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Compare', 'bosa-elementor-for-woocommerce' ),
				'label_block' => true,
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
					'bew_yith_buttons_show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'bew_yith_buttons_compare_tooltip',
			[
				'label' => esc_html__( 'Compare Label Tooltip', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'No', 'bosa-elementor-for-woocommerce' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => esc_html__( 'On: shows on hover only, as a tooltip. Off: shows next to the icon at all times.', 'bosa-elementor-for-woocommerce' ),
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
					'bew_yith_buttons_show_label' => 'yes',
				],
				'prefix_class' => 'bew-yith-compare-tooltip-',
			]
		);

		$this->add_control(
			'bew_yith_buttons_wishlist_label',
			[
				'label' => esc_html__( 'Wishlist Label', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'default' => esc_html__( 'Add to wishlist', 'bosa-elementor-for-woocommerce' ),
				'label_block' => true,
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
					'bew_yith_buttons_show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'bew_yith_buttons_wishlist_tooltip',
			[
				'label' => esc_html__( 'Wishlist Label Tooltip', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ),
				'label_off' => esc_html__( 'No', 'bosa-elementor-for-woocommerce' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'description' => esc_html__( 'On: shows on hover only, as a tooltip. Off: shows next to the icon at all times.', 'bosa-elementor-for-woocommerce' ),
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
					'bew_yith_buttons_show_label' => 'yes',
				],
				'prefix_class' => 'bew-yith-wishlist-tooltip-',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_yith_buttons_icon_style_section',
			[
				'label' => esc_html__( 'Compare & Wishlist Icon', 'bosa-elementor-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'bew_yith_buttons_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 12,
						'max' => 60,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 18,
				],
				'selectors' => [
					'{{WRAPPER}} .bew-yith-buttons-group' => '--bew-yith-icon-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'bew_yith_buttons_gap',
			[
				'label' => esc_html__( 'Gap Between Icons', 'bosa-elementor-for-woocommerce' ),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 60,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 12,
				],
				'selectors' => [
					'{{WRAPPER}} .bew-yith-buttons-group' => '--bew-yith-icon-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'bew_yith_buttons_icon_tabs' );

		$this->start_controls_tab(
			'bew_yith_buttons_icon_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'bew_yith_buttons_icon_color', esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group', '--bew-yith-icon-color' );

		// A plain Background group control can't reliably win here: the
		// theme's own .woocommerce a.button rule (background-color, no
		// !important) ties in specificity with this feature's own
		// .bew-yith-buttons-group a.compare reset, which therefore has to
		// carry !important to guarantee it wins regardless of stylesheet
		// order -- and a non-important group-control rule can never beat an
		// !important one. Routed through a custom property instead (same
		// proven mechanism as Icon Color/Size above) so the override is
		// guaranteed rather than a source-order coin flip.
		$this->get_normal_color( 'bew_yith_buttons_icon_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group', '--bew-yith-icon-bg' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'bew_yith_buttons_icon_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'bew_yith_buttons_icon_hover_color', esc_html__( 'Icon Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group', '--bew-yith-icon-hover-color' );

		$this->get_normal_color( 'bew_yith_buttons_icon_hov_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group', '--bew-yith-icon-hov-bg' );

		$this->get_normal_color( 'bew_yith_buttons_icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group a.compare:hover, {{WRAPPER}} .bew-yith-buttons-group .yith-wcwl-add-to-wishlist-button:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'bew_yith_buttons_icon_hr',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'bew_yith_buttons_icon_border', '.bew-yith-buttons-group a.compare, {{WRAPPER}} .bew-yith-buttons-group .yith-wcwl-add-to-wishlist-button' );

		$this->get_border_radius( 'bew_yith_buttons_icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group a.compare, {{WRAPPER}} .bew-yith-buttons-group .yith-wcwl-add-to-wishlist-button' );

		$this->get_margin( 'bew_yith_buttons_icon_margin', '.bew-yith-buttons-group a.compare, {{WRAPPER}} .bew-yith-buttons-group .yith-wcwl-add-to-wishlist-button' );

		$this->get_padding( 'bew_yith_buttons_icon_padding', '.bew-yith-buttons-group a.compare, {{WRAPPER}} .bew-yith-buttons-group .yith-wcwl-add-to-wishlist-button' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_yith_buttons_label_style_section',
			[
				'label' => esc_html__( 'Compare & Wishlist Label', 'bosa-elementor-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'bew_show_yith_buttons' => 'yes',
				],
			]
		);

		// .bew-yith-tooltip-label is the one element used for both display
		// modes (plain inline text when its own Tooltip switch is off, a
		// hover bubble when on -- see bew-global.css) so styling it directly
		// covers both at once, same as the dedicated Woo YITH Compare
		// widget's own label controls target its inline h4 and its .bew-tooltip
		// together.
		$this->get_title_typography( 'bew_yith_buttons_label_typography', '.bew-yith-buttons-group .bew-yith-tooltip-label' );

		$this->start_controls_tabs( 'bew_yith_buttons_label_tabs' );

		$this->start_controls_tab(
			'bew_yith_buttons_label_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'bew_yith_buttons_label_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group .bew-yith-tooltip-label', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'bew_yith_buttons_label_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-yith-buttons-group .bew-yith-tooltip-label',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'bew_yith_buttons_label_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'bew_yith_buttons_label_hov_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group .bew-yith-button-wrap:hover .bew-yith-tooltip-label', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'bew_yith_buttons_label_hov_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-yith-buttons-group .bew-yith-button-wrap:hover .bew-yith-tooltip-label',
			]
		);

		$this->get_normal_color( 'bew_yith_buttons_label_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group .bew-yith-button-wrap:hover .bew-yith-tooltip-label', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'bew_yith_buttons_label_hr',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'bew_yith_buttons_label_border', '.bew-yith-buttons-group .bew-yith-tooltip-label' );

		$this->get_border_radius( 'bew_yith_buttons_label_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-yith-buttons-group .bew-yith-tooltip-label' );

		$this->get_margin( 'bew_yith_buttons_label_margin', '.bew-yith-buttons-group .bew-yith-tooltip-label' );

		$this->get_padding( 'bew_yith_buttons_label_padding', '.bew-yith-buttons-group .bew-yith-tooltip-label' );

		$this->end_controls_section();
	}

	/**
	 * Renders YITH's own default Compare button (native template/styling,
	 * respecting YITH's own visibility rules) and YITH's own default
	 * Wishlist button (its official shortcode) for the current global
	 * $product. Pair with register_bew_yith_buttons_control() -- only call
	 * this when that control's value is 'yes'.
	 *
	 * @param string $compare_label  Tooltip text for the Compare icon --
	 *                                pass $settings['bew_yith_buttons_compare_label'].
	 * @param string $wishlist_label Tooltip text for the Wishlist icon --
	 *                                pass $settings['bew_yith_buttons_wishlist_label'].
	 *
	 * The label text is BEW's own, not YITH's: YITH's Compare button's own
	 * label could be read from its output and swapped, but Wishlist's is
	 * baked into YITH's own JS bundle (a globally-localized string, rendered
	 * client-side after the block-based button hydrates -- see
	 * add-to-wishlist/index.js's initAddToWishlistButton()), not something a
	 * single widget instance can override without changing YITH's wishlist
	 * text sitewide. Rendering BEW's own tooltip span for both icons keeps
	 * the two consistent and gives each one an independently editable,
	 * AI-generatable label (register_bew_yith_buttons_control()'s Compare
	 * Label / Wishlist Label controls) regardless of what YITH itself does
	 * with its own markup. YITH's own label elements are still rendered
	 * (can't suppress Wishlist's without breaking its JS init) but hidden
	 * via CSS.
	 */
	public function render_bew_default_yith_buttons( $compare_label = '', $wishlist_label = '' ) {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$has_compare  = class_exists( '\YITH_WooCompare_Frontend' );
		$has_wishlist = function_exists( 'YITH_WCWL' );
		if ( ! $has_compare && ! $has_wishlist ) {
			return;
		}

		echo '<div class="bew-yith-buttons-group">';

		if ( $has_compare ) {
			echo '<span class="bew-yith-button-wrap bew-yith-button-wrap--compare">';
			\YITH_WooCompare_Frontend::instance()->output_button( $product->get_id() );
			if ( '' !== $compare_label ) {
				echo '<span class="bew-yith-tooltip-label">' . esc_html( $compare_label ) . '</span>';
			}
			echo '</span>';
		}

		if ( $has_wishlist ) {
			echo '<span class="bew-yith-button-wrap bew-yith-button-wrap--wishlist">';
			echo do_shortcode( '[yith_wcwl_add_to_wishlist]' );
			if ( '' !== $wishlist_label ) {
				echo '<span class="bew-yith-tooltip-label">' . esc_html( $wishlist_label ) . '</span>';
			}
			echo '</span>';
		}

		echo '</div>';
	}

	public function insert_bew_pro_message(){
        if(!class_exists('BEW_PRO')){
            $this->start_controls_section(
                'bew_section_pro',
                [
                    'label' => esc_html__('Go Premium for More Features', 'bosa-elementor-for-woocommerce'),

                ]
            );

            $this->add_control(
                'bew_control_get_pro',
                [
                    'label' => esc_html__('Unlock More Possibilities', 'bosa-elementor-for-woocommerce'),
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        '1' => [
                            'title' => '',
                            'icon' => 'eicon-lock',
                        ],
                    ],
                    'default' => '1',
                    'toggle'    => false,
                    'description' => '<span class="bew-widget-pro-feature">' . __(' Get the ', 'bosa-elementor-for-woocommerce') .'<a href="https://bew.bosathemes.com/pricing" target="_blank">' . __(' Pro version ', 'bosa-elementor-for-woocommerce').'</a>' . __(' for more awesome widgets, modules and template library.', 'bosa-elementor-for-woocommerce') .'</span>',
                ]
            );

            $this->end_controls_section();
        }
    }
}