<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Yith_Compare extends BEW_Settings {

	public function get_name(){
		return 'bew-elements-yith-compare';
	}

	public function get_title(){
		return esc_html__( 'Woo YITH Compare', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon(){
		return 'bew-widget bew-icon-compare';
	}

	public function get_keywords(){
		return [ 'bew', 'compare', 'bew compare', 'woo', 'woocommerce', 'yith', 'yith compare', 'woo yith compare' ];
	}

	protected function register_controls(){
		
		$this->start_controls_section(
			'bew_yith_compare',
			[
				'label' => esc_html__( 'Content', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
                'label',
                [
                    'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
                    'type' => Controls_Manager::TEXT,
                    'default' => esc_html__( 'Compare', 'bosa-elementor-for-woocommerce' ),
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
					'value' => 'fas fa-sync',
					'library' => 'fa-solid',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_yith_compare_style',
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
					'{{WRAPPER}} .bew-compare-inner .bew-icon' => 'font-size: {{SIZE}}{{UNIT}};',
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
					// --bew-compare-icon-stroke feeds the hand-built outline
					// sync icon's mask-image in bew-global.css (see
					// .bew-icon-outline-sync) -- fill/color alone have no
					// effect on it, it's a masked ::before, not the glyph.
					'{{WRAPPER}} .bew-compare-inner .bew-icon' => 'fill: {{VALUE}}; color: {{VALUE}}; --bew-compare-icon-stroke: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-compare-inner .bew-icon',
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
					'{{WRAPPER}} .bew-compare-inner .bew-icon:hover' => 'fill: {{VALUE}}; color: {{VALUE}}; --bew-compare-icon-stroke: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-compare-inner .bew-icon:hover',
			]
		);

		$this->get_normal_color( 'icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-compare-inner .bew-icon:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr1',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'icon_border', '.bew-compare-inner .bew-icon' );

		$this->get_border_radius( 'icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-compare-inner .bew-icon', 'border-radius' );

		$this->get_margin( 'icon_margin', '.bew-compare-inner .bew-icon' );

		$this->get_padding( 'icon_padding', '.bew-compare-inner .bew-icon' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_yith_compare_label',
			[
				'label' => esc_html__( 'Label', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'label_typography', '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip' );

		$this->start_controls_tabs(
			'label_tabs'
		);

		$this->start_controls_tab(
			'label_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'label_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip', 'color' );

		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			[
				'name' => 'label_bg_color',
				'label' => esc_html__( 'Background', 'bosa-elementor-for-woocommerce' ),
				'types' => [ 'classic', 'gradient' ],
				'exclude' => [ 'image' ],
				'selector' => '{{WRAPPER}} .bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip',
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
					'{{WRAPPER}} .bew-compare-inner a:hover h4'  => 'color: {{VALUE}}',
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
				'selector' => '{{WRAPPER}} .bew-compare-inner a:hover h4',
			]
		);

		$this->get_normal_color( 'label_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-compare-inner a:hover h4', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr2',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'label_border', '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip' );

		$this->get_border_radius( 'label_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip', 'border-radius' );

		$this->get_margin( 'label_margin', '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip' );

		$this->get_padding( 'label_padding', '.bew-compare-inner h4, {{WRAPPER}} .bew-compare-inner .bew-tooltip' );

		$this->end_controls_section();

	}

	protected function render(){
		$settings = $this->get_settings_for_display();
		$label_tool_tip = isset( $settings['label_tool_tip'] ) ? sanitize_key( $settings['label_tool_tip'] ) : 'no';
		$label          = isset( $settings['label'] ) ? sanitize_text_field( $settings['label'] ) : '';

		// This widget's default icon (fas fa-sync) is a solid Font Awesome
		// glyph -- Font Awesome 5 Free has no regular/outline "sync" at all
		// (confirmed: far fa-sync renders no glyph), so it can't just switch
		// to the "far" style like Wishlist/My Account did to get an outline
		// look consistent with theirs. CSS instead draws a hand-built
		// outline sync icon over it -- but only when this is still the
		// unmodified default value, so a user who picks a different icon
		// via the control keeps seeing exactly that icon. Detecting "still
		// the default" in PHP (once, here) is far more reliable than trying
		// to detect it in CSS against Elementor's icon markup, which
		// renders as either an <i> font glyph or an inline <svg> depending
		// on site config/Elementor version.
		$icon_is_unmodified_default = isset( $settings['icon']['value'] ) && 'fas fa-sync' === $settings['icon']['value'];

		if ( function_exists( 'yith_woocompare_constructor' ) && class_exists( 'WooCommerce' ) ) {

			// \YITH_WooCompare_Frontend::instance() is a plain lazy singleton, so
			// it's always safe to call directly — unlike the legacy
			// `global $yith_woocompare; $yith_woocompare->obj` pattern, which is
			// only populated by YITH's own frontend-only bootstrap hooks and can
			// still be unset in contexts those hooks don't run in (e.g. Elementor
			// editor preview requests, which are flagged is_admin() even though
			// they render real widget output), leaving the compare link with an
			// empty href in that case.
			$compare_url = class_exists( 'YITH_WooCompare_Frontend' ) ? \YITH_WooCompare_Frontend::instance()->view_table_url() : '';

			// YITH's own JS only intercepts clicks on "a.compare-widget" inside
			// a ".yith-woocompare-widget" ancestor to open its popup — both
			// classes are required, matching YITH's own widget template
			// (yith-compare-widget.php). Without them the link just navigates
			// to $compare_url instead of opening the popup.
			//
			// The script is only ever registered (not enqueued) by YITH's own
			// enqueue_scripts(), which runs on the frontend-only `wp_enqueue_scripts`
			// hook — a hook that, for the same is_admin()-in-preview reason above,
			// may never have fired for this request, leaving the script not just
			// unenqueued but never registered at all. Calling enqueue_scripts()
			// directly guarantees the registration exists before we enqueue it;
			// doing so again if it already ran elsewhere is harmless.
			if ( class_exists( 'YITH_WooCompare_Frontend' ) ) {
				\YITH_WooCompare_Frontend::instance()->enqueue_scripts();
			}
			wp_enqueue_script( 'yith-woocompare-main' );
			?>
	        <div class="bew-compare-inner bew-widget-inner yith-woocompare-widget">
        		<a href="<?php echo esc_url( $compare_url ); ?>" class="compare-widget">
		        		<span class="bew-icon<?php echo $icon_is_unmodified_default ? ' bew-icon-outline-sync' : ''; ?>">
		        			<?php Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] );?>
		        		</span>
	        		<?php if ( 'yes' !== $label_tool_tip ) { ?>
			   				<h4> <?php echo esc_html( $label ); ?></h4>			
			      	<?php } ?>
		  		</a>
		  		<?php if ( 'yes' === $label_tool_tip ) { ?>
						<span class="bew-tooltip">
			                <?php echo esc_html( $label ); ?>
			            </span> 
	       		<?php } ?>
	        </div>
 			<?php
 		} else {
 			$error = esc_html__( 'YITH Compare plugin not installed. Please verify plugin is active.', 'bosa-elementor-for-woocommerce' );
 			if ( ! class_exists( 'WooCommerce' ) ) {
 				$error = esc_html__( 'YITH Compare and WooCommerce plugin not installed. Please verify plugins are active.', 'bosa-elementor-for-woocommerce' );
 			}
 			?>
			<div class="bew-editor-error">
		   		<?php echo esc_html( $error ); ?>
		    </div>
	  		<?php
		}	
	}
}