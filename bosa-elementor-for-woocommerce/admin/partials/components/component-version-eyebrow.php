<?php
/**
 * Version Eyebrow UI Component
 *
 * Small "Version x.y.z" label shown above the title on every BEW admin page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bew_render_version_eyebrow() {
	?>
	<div class="bew-version-eyebrow">
		<?php
		/* translators: %s: BEW version number, e.g. "2.1.3". */
		printf( esc_html__( 'Version %s', 'bosa-elementor-for-woocommerce' ), esc_html( BEW_VERSION ) );
		?>
	</div>
	<?php
}
