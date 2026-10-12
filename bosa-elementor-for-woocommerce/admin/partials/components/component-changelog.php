<?php
/**
 * BEW Changelog component
 *
 * One collapsible block per release, newest first; the newest starts open.
 * The data comes from BEW_Changelog (the bundled readme.txt).
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/class-bew-changelog.php';

/**
 * Prints the changelog. Prints nothing when the readme has no changelog.
 */
function bew_render_changelog() {
	$releases = BEW_Changelog::get_releases();

	if ( ! $releases ) {
		return;
	}
	?>
	<div class="bew-changelog">
		<h3><?php esc_html_e( 'Changelog', 'bosa-elementor-for-woocommerce' ); ?></h3>

		<?php foreach ( $releases as $index => $release ) : ?>
			<details class="bew-changelog-release"<?php echo 0 === $index ? ' open' : ''; ?>>
				<summary>
					<span class="bew-changelog-version">v<?php echo esc_html( $release['version'] ); ?></span>
					<?php if ( version_compare( $release['version'], BEW_VERSION, '==' ) ) : ?>
						<span class="bew-changelog-current"><?php esc_html_e( 'Current', 'bosa-elementor-for-woocommerce' ); ?></span>
					<?php endif; ?>
				</summary>

				<div class="bew-changelog-body">
					<?php foreach ( $release['groups'] as $group ) : ?>
						<?php if ( '' !== $group['label'] ) : ?>
							<span class="bew-changelog-group bew-changelog-group-<?php echo esc_attr( sanitize_html_class( strtolower( $group['label'] ) ) ); ?>"><?php echo esc_html( $group['label'] ); ?></span>
						<?php endif; ?>
						<ul>
							<?php foreach ( $group['items'] as $item ) : ?>
								<li><?php echo esc_html( $item ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endforeach; ?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php
}
