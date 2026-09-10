<?php
/**
 * Banner UI Component
 *
 * Renders a full-width banner. Falls back to a CSS gradient when no image is provided.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bew_render_banner( $image_url = '', $title = '', $link_url = '' ) {
	$has_image = ! empty( $image_url );
	$has_link  = ! empty( $link_url );
	$class     = $has_image ? 'bew-banner-has-image' : 'bew-banner-no-image';
	?>
	<div class="bew-banner-ui <?php echo esc_attr( $class ); ?>">
		<?php if ( $has_image && $has_link ) : ?>
			<a href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="bew-banner-image">
			</a>
		<?php elseif ( $has_image ) : ?>
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="bew-banner-image">
		<?php endif; ?>
		<?php if ( $title ) : ?>
			<h2 class="bew-banner-title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>
	</div>
	<?php
}
