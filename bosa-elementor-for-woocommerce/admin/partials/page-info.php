<?php
/**
 * BEW Info Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/partials/components/component-banner.php';
require_once BEW_PATH . 'admin/partials/components/component-box.php';

$bew_is_pro_active = class_exists( 'BEW_Pro' );
?>
<div class="wrap bew-admin-page bew-admin-page-info">

	<h1>
		<?php
		if ( $bew_is_pro_active ) {
			esc_html_e( 'Welcome to BEW Pro - Premium Elementor Addons, Templates & AI Builder', 'bosa-elementor-for-woocommerce' );
		} else {
			esc_html_e( 'Welcome to BEW - Elementor Addons, Templates & AI Builder', 'bosa-elementor-for-woocommerce' );
		}
		?>
	</h1>

	<?php
	if ( $bew_is_pro_active ) {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-pro-banner.jpg' );
	} else {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-banner.jpg', '', 'https://bew.bosathemes.com/pricing' );
	}
	?>

	<p class="bew-welcome-text">
		<?php
		if ( $bew_is_pro_active ) {
			echo wp_kses(
				__( 'Thanks for going Pro! <strong>BEW Pro is now installed and ready to use.</strong> We hope the following information will help and you enjoy using it!', 'bosa-elementor-for-woocommerce' ),
				[ 'strong' => [] ]
			);
		} else {
			esc_html_e( 'BEW is now installed and ready to use. We hope the following information will help and you enjoy using it!', 'bosa-elementor-for-woocommerce' );
		}
		?>
	</p>

	<div class="bew-buttons-container">
		<a href="https://bew.bosathemes.com/" class="bew-box-button bew-button-secondary" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'View Details', 'bosa-elementor-for-woocommerce' ); ?>
		</a>
	</div>

	<div class="bew-info-boxes">
		<?php
		if ( ! $bew_is_pro_active ) {
			bew_render_box( [
				'title'        => __( 'BEW Free VS Pro', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Explore the difference between Free and Pro versions and choose what suits you best.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Learn More', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bew.bosathemes.com/#free-vs-pro/',
				'button_class' => 'secondary',
			] );
		}

		bew_render_box( [
			'title'        => __( 'Documentation', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'Read our comprehensive documentation to learn how to use all features of the plugin.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Read Docs', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bew.bosathemes.com/docs/',
			'button_class' => 'secondary',
		] );

		if ( ! $bew_is_pro_active ) {
			bew_render_box( [
				'title'        => __( 'Need More Features?', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Upgrade to Pro & unlock premium widgets, modules, 2,500+ templates, & AI-powered Content Generation.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'View Pricing', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://bew.bosathemes.com/pricing',
				'button_class' => 'primary',
			] );
		}

		bew_render_box( [
			'title'        => __( 'Customization Request', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'Need custom modifications? Get in touch with our team for professional customization services.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Get Service', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bew.bosathemes.com/hire/',
			'button_class' => 'secondary',
		] );

		bew_render_box( [
			'title'        => __( 'Get Priority Support Access', 'bosa-elementor-for-woocommerce' ),
			'description'  => __( 'Get community help, find solutions, & connect with the BEW community. BEW Pro users can submit a support ticket directly to our core support team.', 'bosa-elementor-for-woocommerce' ),
			'button_text'  => __( 'Visit Support Center', 'bosa-elementor-for-woocommerce' ),
			'button_link'  => 'https://bew.bosathemes.com/support/',
			'button_class' => 'secondary',
		] );

		if ( ! $bew_is_pro_active ) {
			bew_render_box( [
				'title'        => __( 'Rate This Plugin', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'If you love BEW, please leave a 5-star rating to help other users discover it.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => __( 'Leave Rating', 'bosa-elementor-for-woocommerce' ),
				'button_link'  => 'https://wordpress.org/support/plugin/bosa-elementor-for-woocommerce/reviews/',
				'button_class' => 'secondary',
			] );
		}
		?>
	</div>
</div>
