<?php

namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Cart extends BEW_Settings {

	/**
	 * Ensure WC()->cart exists before this widget reads from it.
	 *
	 * Deliberately NOT done in the constructor: Elementor instantiates every
	 * registered widget (via elementor/widgets/register, on `init`) well before
	 * WooCommerce's own `wp_loaded`-hooked cart/session init runs. Forcing
	 * wc_load_cart() that early creates the cart before WC is ready to
	 * hydrate it, and WC's own init then skips re-initializing it (it only
	 * runs when WC()->cart is still null) — leaving a stale, empty cart for
	 * the rest of the request. Calling this from render() instead means it
	 * only ever runs after WC's real init has already had its chance to.
	 *
	 * No `is_admin()` guard here (unlike the old constructor version): when
	 * Elementor's editor does a fresh full reload, its own preview-iframe
	 * request is itself flagged is_admin() === true, which silently skipped
	 * this whole fallback and left the cart permanently null there — icon
	 * count, subtotal and the hover panel all rendered empty until something
	 * else (e.g. changing a setting) triggered a render on a request where
	 * is_admin() happened to be false. render() only runs when Elementor
	 * actually wants this widget's HTML, so it's always correct to load the
	 * cart here regardless of how is_admin() evaluates for that request.
	 */
	private function ensure_cart_loaded() {
		if ( class_exists( 'WooCommerce' ) && is_null( WC()->cart ) ) {
			include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
			include_once WC_ABSPATH . 'includes/class-wc-cart.php';
			wc_load_cart();
		}
	}

	public function get_name(){
		return 'bew-elements-cart';
	}

	public function get_title(){
		return esc_html__( 'Woo Cart', 'bosa-elementor-for-woocommerce' );
	}

	public function get_icon(){
		return 'bew-widget bew-icon-cart';
	}

	public function get_keywords(){
		return [ 'bew', 'cart', 'bew cart', 'woo', 'woocommerce', 'woo cart' ];
	}

	protected function register_controls(){
		
		$this->start_controls_section(
			'bew_elements_cart',
			[
				'label' => esc_html__( 'Content', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_control(
			'icon',
			[
				'label' => esc_html__( 'Icon', 'bosa-elementor-for-woocommerce' ),
				'type' => Controls_Manager::ICONS,
				'default' => [
					'value' => 'fas fa-shopping-cart',
					'library' => 'fa-solid',
				],
			]
		);

		$this->get_item_visibility( 'subtotal', esc_html__( 'Subtotal', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->get_item_visibility( 'open_cart_on_hover', esc_html__( 'Open Cart on Hover', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->get_item_visibility( 'open_cart_on_add_to_cart', esc_html__( 'Open Cart on Add to Cart', 'bosa-elementor-for-woocommerce' ), esc_html__( 'Yes', 'bosa-elementor-for-woocommerce' ), esc_html__( 'No', 'bosa-elementor-for-woocommerce' ), 'yes' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_cart_style',
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
					'{{WRAPPER}} .bew-cart-inner .bew-icon' => 'font-size: {{SIZE}}{{UNIT}};',
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
					// --bew-cart-icon-stroke feeds the hand-built outline cart
					// icon's mask-image in bew-global.css (see
					// .bew-icon-outline-cart) -- fill/color alone have no
					// effect on it, it's a masked ::before, not the glyph.
					'{{WRAPPER}} .bew-cart-inner .bew-icon' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}}; --bew-cart-icon-stroke: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-cart-inner .bew-icon',
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
					'{{WRAPPER}} .bew-cart-inner .bew-icon:hover' => 'fill: {{VALUE}}; color: {{VALUE}}; border-color: {{VALUE}}; --bew-cart-icon-stroke: {{VALUE}};',
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
				'selector' => '{{WRAPPER}} .bew-cart-inner .bew-icon:hover',
			]
		);

		$this->get_normal_color( 'icon_hov_border_color', esc_html__( 'Border Hover Color', 'bosa-elementor-for-woocommerce' ), '.bew-cart-inner .bew-icon:hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->get_border_attr( 'icon_border', '.bew-cart-inner .bew-icon' );

		$this->get_border_radius( 'icon_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-cart-inner .bew-icon', 'border-radius' );

		$this->get_margin( 'icon_margin', '.bew-cart-inner .bew-icon' );

		$this->get_padding( 'icon_padding', '.bew-cart-inner .bew-icon' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_cart_products_subtotal',
			[
				'label' => esc_html__( 'Subtotal', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' =>[
					'subtotal' => 'yes',
				],
			]
		);

		$this->get_title_typography( 'subtotal_typography', '.bew-cart-amount' );

		$this->get_normal_color( 'subtotal_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-cart-amount', 'color' );

		$this->get_normal_color('subtotal_bg_color', esc_html__('Background Color', 'bosa-elementor-for-woocommerce'), '.bew-cart-amount', 'background-color');

		$this->get_border_attr( 'subtotal_border', '.bew-cart-amount' );

		$this->get_border_radius( 'subtotal_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-cart-amount' );

		$this->get_margin( 'subtotal_margin', '.bew-cart-amount' );

		$this->get_padding( 'subtotal_padding', '.bew-cart-amount' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_cart_products_counts',
			[
				'label' => esc_html__( 'Counts', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->get_title_typography( 'counts_typography', '.bew-cart-count' );

		$this->get_normal_color( 'counts_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), '.bew-cart-count', 'color' );

		$this->get_normal_color('counts_bg_color', esc_html__('Background Color', 'bosa-elementor-for-woocommerce'), '.bew-cart-count', 'background-color');

		$this->get_border_attr( 'counts_border', '.bew-cart-count' );

		$this->get_border_radius( 'counts_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), '.bew-cart-count', 'border-radius' );

		$this->get_padding( 'counts_padding', '.bew-cart-count' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_open_cart_on_hover',
			[
				'label' => esc_html__( 'Open Cart on Hover', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' =>[
					'open_cart_on_hover' => 'yes',
				],
			]
		);

		$this->add_control(
			'open_cart_on_hover_custom_panel_notice',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'warning',
				'dismissible' => false,
				'heading' => esc_html__( 'Note', 'bosa-elementor-for-woocommerce' ),
				'content' => esc_html__( 'Open Preview Changes to View.', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'opem_cart_title_typography',
				'label' => esc_html__( 'Title Typography', 'bosa-elementor-for-woocommerce' ),
				'selector' => '{{WRAPPER}} .bew-cart-inner .woocommerce-mini-cart a:not(.remove), {{WRAPPER}} .bew-cart-inner .woocommerce-mini-cart__total strong',
				'fields_options' => [
					'typography' => ['default' => 'yes'],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'open_cart_price_count_typography',
				'label' => esc_html__( 'Price Count Typography', 'bosa-elementor-for-woocommerce' ),
				'selector' => '{{WRAPPER}} .bew-cart-inner .woocommerce-mini-cart .quantity, {{WRAPPER}} .bew-cart-inner .woocommerce-mini-cart__total .amount',
				'fields_options' => [
					'typography' => ['default' => 'yes'],
				],
			]
		);

		$this->start_controls_tabs(
			'open_cart_tabs'
		);

		$this->start_controls_tab(
			'open_cart_normal_tab',
			[
				'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'open_cart_title_color', esc_html__( 'Title Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart .woocommerce a', 'color' );
		
		$this->get_normal_color('open_cart_bg_color', esc_html__('Background Color', 'bosa-elementor-for-woocommerce'), '.bew-shoping-detail-cart', 'background-color');

		$this->get_normal_color( 'open_cart_cross_color', esc_html__( 'Cross Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart', 'color' );

		$this->get_normal_color( 'open_cart_price_count_color', esc_html__( 'Price Count Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart', 'color' );

		$this->get_normal_color( 'divider_color', esc_html__( 'Divider Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart', 'color' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			'open_cart_hover_tab',
			[
				'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->get_normal_color( 'open_cart_title_hover_color', esc_html__( 'Title Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart .woocommerce a', 'color' );

		$this->get_normal_color( 'open_cart_cross_hover_color', esc_html__( 'Cross Color', 'bosa-elementor-for-woocommerce' ), '.bew-shoping-detail-cart', 'color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'hr3',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);

		$this->get_border_attr( 'cart_on_hover_border', '.bew-shoping-detail-cart' );

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' 			=> 'cart_on_box_shadow',
				'selector' 		=> '{{WRAPPER}} .bew-shoping-detail-cart',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_view_cart_button',
			[
				'label' => esc_html__( 'View Cart Button', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' =>[
					'open_cart_on_hover' => 'yes',
				],
			]
		);

		$this->add_control(
			'view_cart_button_custom_panel_notice',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'warning',
				'dismissible' => false,
				'heading' => esc_html__( 'Note', 'bosa-elementor-for-woocommerce' ),
				'content' => esc_html__( 'Open Preview Changes to View.', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->register_cart_action_button_style( 'view_', '.bew-shoping-detail-cart .woocommerce a.button' );

		$this->end_controls_section();

		$this->start_controls_section(
			'bew_cart_checkout_button',
			[
				'label' => esc_html__( 'Checkout Button', 'bosa-elementor-for-woocommerce' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' =>[
					'open_cart_on_hover' => 'yes',
				],
			]
		);

		$this->add_control(
			'cart_checkout_button_custom_panel_notice',
			[
				'type' => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'warning',
				'dismissible' => false,
				'heading' => esc_html__( 'Note', 'bosa-elementor-for-woocommerce' ),
				'content' => esc_html__( 'Open Preview Changes to View.', 'bosa-elementor-for-woocommerce' ),
			]
		);

		$this->register_cart_action_button_style( 'checkout_', '.bew-shoping-detail-cart .woocommerce a.checkout' );

		$this->end_controls_section();

	}

	/**
	 * Independent style controls for the mini-cart's "View Cart"/"Checkout"
	 * links — parameterized by prefix+selector so the two buttons can be
	 * styled separately (BEW_Settings::register_button_style_controls() is
	 * hardcoded to the Blog widget's own button, not reusable here).
	 */
	private function register_cart_action_button_style( $prefix, $selector ) {
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $prefix . 'btn_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->start_controls_tabs( $prefix . 'btn_tabs' );

		$this->start_controls_tab(
			$prefix . 'btn_normal_tab',
			[ 'label' => esc_html__( 'Normal', 'bosa-elementor-for-woocommerce' ) ]
		);

		$this->get_normal_color( $prefix . 'btn_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), $selector, 'color' );
		$this->get_normal_color( $prefix . 'btn_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), $selector, 'background-color' );

		$this->end_controls_tab();

		$this->start_controls_tab(
			$prefix . 'btn_hover_tab',
			[ 'label' => esc_html__( 'Hover', 'bosa-elementor-for-woocommerce' ) ]
		);

		$this->get_normal_color( $prefix . 'btn_hov_color', esc_html__( 'Text Color', 'bosa-elementor-for-woocommerce' ), $selector . ':hover', 'color' );
		$this->get_normal_color( $prefix . 'btn_hov_bg_color', esc_html__( 'Background Color', 'bosa-elementor-for-woocommerce' ), $selector . ':hover', 'background-color' );
		$this->get_normal_color( $prefix . 'btn_hov_border_color', esc_html__( 'Border Color', 'bosa-elementor-for-woocommerce' ), $selector . ':hover', 'border-color' );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->get_border_attr( $prefix . 'btn_border', $selector );
		$this->get_border_radius( $prefix . 'btn_border_radius', esc_html__( 'Border Radius', 'bosa-elementor-for-woocommerce' ), $selector, 'border-radius' );
		$this->get_margin( $prefix . 'btn_margin', $selector );
		$this->get_padding( $prefix . 'btn_padding', $selector );
	}

	protected function render(){
		$this->ensure_cart_loaded();
		$settings = $this->get_settings_for_display();
		$open_auto = '';
		if ( isset( $settings['open_cart_on_add_to_cart'] ) && 'yes' === $settings['open_cart_on_add_to_cart'] ){
			$open_auto = 'bew-open-widget-cart';
		}
		// See Woo YITH Compare's own render() / bew-icon-outline-sync for the
		// same pattern: only while the icon control still holds the
		// unmodified default do we swap in the hand-built outline SVG, so a
		// user who picks a different icon keeps seeing exactly that icon.
		$icon_is_unmodified_default = isset( $settings['icon']['value'] ) && 'fas fa-shopping-cart' === $settings['icon']['value'];
		if ( class_exists( 'WooCommerce' ) ) { ?>
	        <div class="bew-cart-inner bew-widget-inner <?php echo esc_attr( $open_auto ); ?> ">
	        	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>">
	        		<span class="bew-icon<?php echo $icon_is_unmodified_default ? ' bew-icon-outline-cart' : ''; ?>">
	        			<?php Icons_Manager::render_icon( $settings['icon'], [ 'aria-hidden' => 'true' ] );
	        			 	if ( function_exists( 'WC' ) && WC()->cart ) { ?>
	        			 			<span class="bew-cart-count"><?php echo esc_html( (string) WC()->cart->get_cart_contents_count() ); ?></span>
	        		  <?php } ?>
	        		</span>
	        		<?php	
		        	if ( isset( $settings['subtotal'] ) && 'yes' === $settings['subtotal'] ) { 
		        		if ( function_exists( 'WC' ) && WC()->cart ) { ?>
		        			<div class="bew-cart-amount"><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></div>
							<?php  
		      			}
		      		} ?>
	        	</a>
	        	<?php if ( isset( $settings['open_cart_on_hover'] ) && 'yes' === $settings['open_cart_on_hover'] ) {
		        		// WC_Widget_Cart always echoes an EMPTY placeholder div and relies on
		        		// wc-cart-fragments (AJAX) to fill it client-side after page load — that
		        		// AJAX call doesn't reliably complete inside Elementor's preview iframe,
		        		// leaving the panel permanently blank there. So render the real mini-cart
		        		// markup ourselves via WooCommerce's own template function (identical
		        		// output/classes to what the AJAX response would have injected, so the
		        		// existing style controls still apply) and keep the script enqueued too,
		        		// so it can still live-refresh this panel after an AJAX add-to-cart on
		        		// the real frontend.
		        		wp_enqueue_script( 'wc-cart-fragments' );
		        	?>
		        	<div class="bew-shoping-detail-cart">
		        		<div class="widget woocommerce widget_shopping_cart">
		        			<div class="widget_shopping_cart_content">
		        				<?php woocommerce_mini_cart(); ?>
		        			</div>
		        		</div>
		        	</div>
		    	<?php } ?>	
	        </div>
			<?php
		} else {
 			$error = esc_html__( 'WooCommerce plugin not installed. Please verify plugins are active.', 'bosa-elementor-for-woocommerce' ); ?>
 			<div class="bew-editor-error">
		   		<?php echo esc_html( $error ); ?>
		    </div>
	 		<?php
	 	}
	}
}