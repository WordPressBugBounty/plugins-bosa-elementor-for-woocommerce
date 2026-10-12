<?php
/**
 * BEW Settings > System Status tab.
 *
 * Included inside the #bew-tab-system-status panel of page-settings.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/class-bew-system-status.php';
?>
<h3><?php esc_html_e( 'System Status', 'bosa-elementor-for-woocommerce' ); ?></h3>
<p><?php esc_html_e( 'These checks show whether your server is ready to run BEW and import its templates. Nothing here changes your settings.', 'bosa-elementor-for-woocommerce' ); ?></p>

<h4 class="bew-subsection-title"><?php esc_html_e( 'Server', 'bosa-elementor-for-woocommerce' ); ?></h4>
<table class="widefat striped bew-status">
	<thead>
		<tr>
			<th class="bew-status-col-icon"></th>
			<th><?php esc_html_e( 'Check', 'bosa-elementor-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Current', 'bosa-elementor-for-woocommerce' ); ?></th>
			<th><?php esc_html_e( 'Recommended', 'bosa-elementor-for-woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( BEW_System_Status::server_checks() as $bew_status_row ) : ?>
			<tr>
				<td class="bew-status-col-icon"><?php BEW_System_Status::render_icon( $bew_status_row[3] ); ?></td>
				<td><?php echo esc_html( $bew_status_row[0] ); ?></td>
				<td>
					<?php echo esc_html( $bew_status_row[1] ); ?>
					<?php if ( false === $bew_status_row[3] ) : ?>
						<br><em class="bew-status-hint"><?php esc_html_e( 'Ask your hosting provider to update this setting.', 'bosa-elementor-for-woocommerce' ); ?></em>
					<?php endif; ?>
				</td>
				<td><?php echo esc_html( $bew_status_row[2] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h4 class="bew-subsection-title"><?php esc_html_e( 'Theme and plugins', 'bosa-elementor-for-woocommerce' ); ?></h4>
<table class="widefat striped bew-status">
	<tbody>
		<?php foreach ( BEW_System_Status::plugin_checks() as $bew_status_row ) : ?>
			<tr>
				<td class="bew-status-col-icon"><?php BEW_System_Status::render_icon( $bew_status_row[2] ); ?></td>
				<td><?php echo esc_html( $bew_status_row[0] ); ?></td>
				<td>
					<?php echo esc_html( $bew_status_row[1] ); ?>
					<?php if ( $bew_status_row[3] ) : ?>
						<br><em class="bew-status-hint"><?php echo esc_html( $bew_status_row[3] ); ?></em>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<h4 class="bew-subsection-title"><?php esc_html_e( 'System info', 'bosa-elementor-for-woocommerce' ); ?></h4>
<p><?php esc_html_e( 'Paste this into your support request so we can help faster.', 'bosa-elementor-for-woocommerce' ); ?></p>
<textarea id="bew-system-info" class="large-text code" rows="16" readonly><?php echo esc_textarea( BEW_System_Status::system_report() ); ?></textarea>
<p><button type="button" class="bew-box-button bew-button-primary" id="bew-copy-system-info"><?php esc_html_e( 'Copy system info', 'bosa-elementor-for-woocommerce' ); ?></button></p>
