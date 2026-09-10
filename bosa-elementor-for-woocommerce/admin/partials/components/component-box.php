<?php
/**
 * Box UI Component
 *
 * Content box with title, description, and optional button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bew_render_box( $args = [] ) {
	$defaults = [
		'title'        => '',
		'description'  => '',
		'button_text'  => '',
		'button_link'  => '#',
		'button_class' => 'primary',
		'icon'         => '',
		'class'        => '',
		'tag'          => '',
	];

	$args = wp_parse_args( $args, $defaults );
	?>
	<div class="bew-box-ui <?php echo esc_attr( $args['class'] ); ?>">
		<?php if ( $args['icon'] ) : ?>
			<div class="bew-box-icon"><?php echo wp_kses_post( $args['icon'] ); ?></div>
		<?php endif; ?>
		<?php if ( $args['tag'] ) : ?>
			<span class="bew-box-tag"><?php echo esc_html( $args['tag'] ); ?></span>
		<?php endif; ?>
		<h3 class="bew-box-title"><?php echo esc_html( $args['title'] ); ?></h3>
		<p class="bew-box-description"><?php echo wp_kses_post( $args['description'] ); ?></p>
		<?php if ( $args['button_text'] && $args['button_link'] ) : ?>
			<a href="<?php echo esc_url( $args['button_link'] ); ?>" class="bew-box-button bew-button-<?php echo esc_attr( $args['button_class'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( $args['button_text'] ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}
