<?php
/**
 * BEW Our Products Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/partials/components/component-banner.php';
require_once BEW_PATH . 'admin/partials/components/component-version-eyebrow.php';
require_once BEW_PATH . 'admin/partials/components/component-box.php';

$bew_pro_status                 = BEW::pro_status();
$bew_is_pro_active              = 'active' === $bew_pro_status;
$bew_pro_cta                    = BEW::pro_cta();
$bosa_pro_theme_active          = BEW::is_bosa_pro_theme_active();
$bosa_shop_extension_pro_active = BEW::is_bosa_shop_extension_pro_active();
?>
<div class="wrap bew-admin-page bew-admin-page-products">

	<?php bew_render_version_eyebrow(); ?>

	<h1><?php esc_html_e( 'Our Products', 'bosa-elementor-for-woocommerce' ); ?></h1>

	<?php
	if ( '' !== $bew_pro_status ) {
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
		if ( ! $bosa_pro_theme_active ) {
			bew_render_box( [
				'title'        => __( 'Bosa Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Premium Elementor & WooCommerce theme with 200+ pre-built websites — launch any site or online store faster, no coding required.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bosathemes.com/bosa-pro/',
				'button_class' => 'secondary',
				'tag'          => __( 'Theme', 'bosa-elementor-for-woocommerce' ),
			] );
		}

		if ( ! $bosa_shop_extension_pro_active ) {
			bew_render_box( [
				'title'        => __( 'Bosa Shop Extension Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Add variation swatches, Ajax add to cart, Ajax search, sale countdowns & more — powerful shop features that boost conversions.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bosathemes.com/bosa-shop-extension/',
				'button_class' => 'secondary',
				'tag'          => __( 'Plugin', 'bosa-elementor-for-woocommerce' ),
			] );
		}

		if ( 'none' === $bew_pro_status || 'expired' === $bew_pro_status ) {
			// BEW Pro is installed: its card becomes the license card.
			bew_render_box( [
				'title'        => __( 'BEW Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => 'none' === $bew_pro_status
					? __( 'BEW Pro is installed. Activate your license to unlock Pro widgets, modules, 3,000+ templates & AI-powered content generation.', 'bosa-elementor-for-woocommerce' )
					: __( 'Your BEW Pro license has expired. Renew to add Pro widgets, templates & AI content again and to keep getting updates.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => $bew_pro_cta['text'],
				'button_link'  => $bew_pro_cta['url'],
				'button_class' => 'primary',
				'new_tab'      => $bew_pro_cta['new_tab'],
				'tag'          => __( 'Plugin', 'bosa-elementor-for-woocommerce' ),
			] );
		} elseif ( ! $bew_is_pro_active ) {
			bew_render_box( [
				'title'        => __( 'BEW Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Launch and grow your online store — or any website — faster with Elementor widgets, 3,000+ templates & AI-powered content generation, right inside the editor.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bew.bosathemes.com/pricing',
				'button_class' => 'primary',
				'tag'          => __( 'Plugin', 'bosa-elementor-for-woocommerce' ),
			] );
		}

		// The bundle already includes all three, so once every one of them is
		// active there is nothing left for the bundle to sell on this site.
		if ( ! ( $bosa_pro_theme_active && $bosa_shop_extension_pro_active && $bew_is_pro_active ) ) {
			bew_render_box( [
				'title'        => __( 'Bosa Pro Extension', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Everything to launch your online store — or any website: Bosa Pro with 200+ pre-built websites, BEW with 3,000+ templates & AI content generation, and Bosa Shop Extension with conversion-boosting WooCommerce features.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bosathemes.com/bosa-pro-extension/',
				'button_class' => 'secondary',
				'tag'          => __( 'All-in-one Bundle', 'bosa-elementor-for-woocommerce' ),
				'note'         => __( 'Save up to 55% vs buying separately.', 'bosa-elementor-for-woocommerce' ),
			] );
		}
		?>
	</div>
</div>
