<?php
/**
 * Card UI Component
 *
 * Feature card with enable/disable toggle and optional demo link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bew_render_card( $args = [] ) {
	$defaults = [
		'name'         => '',
		'toggle_id'    => '',
		'toggle_state' => false,
		'toggle_type'  => 'setting',
		'badge'        => '',
		'pro'          => false,
		'demo_link'    => '',
		'demo_text'    => __( 'VIEW DEMO', 'bosa-elementor-for-woocommerce' ),
		'class'        => '',
	];

	$args = wp_parse_args( $args, $defaults );

	$card_classes = 'bew-card-ui';
	if ( $args['class'] ) {
		$card_classes .= ' ' . $args['class'];
	}
	if ( $args['pro'] ) {
		$card_classes .= ' bew-card-pro';
	}
	?>
	<div class="<?php echo esc_attr( $card_classes ); ?>">
		<div class="bew-card-header">
			<h4 class="bew-card-name">
				<?php echo esc_html( $args['name'] ); ?>
				<?php if ( $args['pro'] ) : ?>
					<span class="bew-card-badge bew-badge-pro"><?php esc_html_e( 'PRO', 'bosa-elementor-for-woocommerce' ); ?></span>
				<?php elseif ( $args['badge'] ) : ?>
					<span class="bew-card-badge"><?php echo esc_html( $args['badge'] ); ?></span>
				<?php endif; ?>
			</h4>
			<label class="bew-toggle-switch" for="<?php echo esc_attr( $args['toggle_id'] ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $args['toggle_id'] ); ?>"
					class="bew-widget-toggle"
					<?php echo $args['toggle_state'] ? 'checked' : ''; ?>
					data-widget="<?php echo esc_attr( $args['toggle_id'] ); ?>"
					data-type="<?php echo esc_attr( $args['toggle_type'] ); ?>"
					data-pro="<?php echo $args['pro'] ? '1' : '0'; ?>"
					aria-label="<?php
						/* translators: %s: Widget or setting name shown on the card. */
						echo esc_attr( sprintf( __( 'Toggle %s', 'bosa-elementor-for-woocommerce' ), $args['name'] ) );
					?>"
				>
				<span class="bew-toggle-slider"></span>
			</label>
		</div>
		<?php if ( $args['demo_link'] ) : ?>
			<a href="<?php echo esc_url( $args['demo_link'] ); ?>" target="_blank" rel="noopener noreferrer" class="bew-card-demo-link">
				<?php echo esc_html( $args['demo_text'] ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}
