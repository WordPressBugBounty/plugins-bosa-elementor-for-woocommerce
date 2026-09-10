<?php
/**
 * BEW Our Products Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/partials/components/component-banner.php';
require_once BEW_PATH . 'admin/partials/components/component-box.php';

$bew_is_pro_active = class_exists( 'BEW_Pro' );
?>
<div class="wrap bew-admin-page bew-admin-page-products">

	<h1><?php esc_html_e( 'Our Products', 'bosa-elementor-for-woocommerce' ); ?></h1>

	<?php
	if ( $bew_is_pro_active ) {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-sm-pro-banner.jpg' );
	} else {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-sm-banner.jpg', '', 'https://bew.bosathemes.com/pricing' );
	}
	?>

	<p class="bew-products-intro">
		<?php esc_html_e( 'Explore our complete suite of premium WordPress products and solutions.', 'bosa-elementor-for-woocommerce' ); ?>
	</p>

	<div class="bew-products-grid">
		<?php
		bew_render_box( [
			'title'        => __( 'Bosa Pro', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'All-in-one Premium Elementor Theme with 200+ Demos, One-click Installer & Advanced Features.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bosathemes.com/bosa-pro/',
			'button_class' => 'secondary',
			'tag'          => __( 'Theme', 'bosa-elementor-for-woocommerce' ),
		] );

		bew_render_box( [
			'title'        => __( 'Bosa Shop Extension Pro', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'Packed with advanced WooCommerce features and flexible options, specially crafted to enhance the functionality of the Bosa Free & Pro theme.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bosathemes.com/bosa-shop-extension/',
			'button_class' => 'secondary',
			'tag'          => __( 'Plugin', 'bosa-elementor-for-woocommerce' ),
		] );

		if ( ! class_exists( 'BEW_Pro' ) ) {
			bew_render_box( [
				'title'        => __( 'BEW Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Upgrade to Pro & unlock premium widgets, modules, 2,500+ templates, & AI-powered Content Generation.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bew.bosathemes.com/pricing',
				'button_class' => 'primary',
				'tag'          => __( 'Plugin', 'bosa-elementor-for-woocommerce' ),
			] );
		}

		bew_render_box( [
			'title'        => __( 'Bosa Pro Extension', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'Designed for your store with an Elementor theme, WooCommerce features, widgets, and templates.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bosathemes.com/bosa-pro-extension/',
			'button_class' => 'secondary',
			'tag'          => __( 'All-in-one Bundle', 'bosa-elementor-for-woocommerce' ),
		] );
		?>
	</div>
</div>
