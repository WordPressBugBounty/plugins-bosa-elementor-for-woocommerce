<?php
/**
 * BEW Info Page
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/partials/components/component-banner.php';
require_once BEW_PATH . 'admin/partials/components/component-version-eyebrow.php';
require_once BEW_PATH . 'admin/partials/components/component-box.php';

// BEW Pro license state (see BEW::pro_license()): 'active', 'expired', 'none', or '' without BEW Pro.
$bew_pro_status    = BEW::pro_status();
$bew_pro_installed = '' !== $bew_pro_status;
$bew_is_pro_active = 'active' === $bew_pro_status;
$bew_pro_cta       = BEW::pro_cta();
?>
<div class="wrap bew-admin-page bew-admin-page-info">

	<?php bew_render_version_eyebrow(); ?>

	<h1>
		<?php
		if ( $bew_pro_installed ) {
			esc_html_e( 'Welcome to BEW Pro - Premium Elementor Addons, Templates & AI Builder', 'bosa-elementor-for-woocommerce' );
		} else {
			esc_html_e( 'Welcome to BEW - Elementor Addons, Templates & AI Builder', 'bosa-elementor-for-woocommerce' );
		}
		?>
	</h1>

	<?php
	if ( $bew_pro_installed ) {
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
		} elseif ( 'none' === $bew_pro_status ) {
			esc_html_e( 'BEW Pro is installed. Activate your license to unlock Pro widgets, modules, templates and AI content generation.', 'bosa-elementor-for-woocommerce' );
		} elseif ( 'expired' === $bew_pro_status ) {
			esc_html_e( 'Your BEW Pro license has expired. Pages you already built keep working; renew to add Pro widgets, templates and AI content again and to keep getting updates.', 'bosa-elementor-for-woocommerce' );
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
		if ( ! $bew_pro_installed ) {
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

		if ( 'none' === $bew_pro_status || 'expired' === $bew_pro_status ) {
			// License section: BEW Pro is installed but its license is missing or expired.
			bew_render_box( [
				'title'        => 'none' === $bew_pro_status
					? __( 'Activate Your License', 'bosa-elementor-for-woocommerce' )
					: __( 'Renew Your License', 'bosa-elementor-for-woocommerce' ),
				'description'  => 'none' === $bew_pro_status
					? __( 'Enter your BEW Pro license key to unlock Pro widgets, modules, 3,000+ templates & AI-powered Content Generation.', 'bosa-elementor-for-woocommerce' )
					: __( 'Renew to add Pro widgets, modules, templates & AI content in the editor again, and to keep getting updates and priority support.', 'bosa-elementor-for-woocommerce' ),
				'button_text'  => $bew_pro_cta['text'],
				'button_link'  => $bew_pro_cta['url'],
				'button_class' => 'primary',
				'new_tab'      => $bew_pro_cta['new_tab'],
			] );
		} elseif ( ! $bew_pro_installed ) {
			bew_render_box( [
				'title'        => __( 'Need More Features?', 'bosa-elementor-for-woocommerce' ),
				'description'  => __( 'Upgrade to Pro & unlock premium widgets, modules, 3,000+ templates, & AI-powered Content Generation.', 'bosa-elementor-for-woocommerce' ),
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

		if ( ! $bew_pro_installed ) {
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
