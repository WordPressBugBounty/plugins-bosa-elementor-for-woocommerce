<?php
/**
 * BEW AI — saved instruction presets.
 *
 * A small, named, reusable instruction string a user saves once and loads
 * into any card's instruction box afterward. Unlike Generation History, a
 * preset is NOT scoped to one field or owner -- one global list for the
 * whole site, since the point is reusing one voice across many pages,
 * widgets, and records. Mandatory across every generator, same free/Pro
 * line as History: drawn at the generator level, not inside a shared
 * control.
 *
 * One option row holds the whole list, JSON-encoded:
 *   [ { id, label, instruction, created_at }, ... ]
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_AI_Presets {

	const OPTION_KEY   = 'bew_ai_presets';
	const MAX_PRESETS  = 100;

	public function __construct() {
		add_action( 'wp_ajax_bew_ai_get_presets', [ $this, 'ajax_get_presets' ] );
		add_action( 'wp_ajax_bew_ai_save_preset', [ $this, 'ajax_save_preset' ] );
		add_action( 'wp_ajax_bew_ai_delete_preset', [ $this, 'ajax_delete_preset' ] );
	}

	// ---- Storage-level API.

	/**
	 * @return array Every saved preset, oldest first.
	 */
	public static function get_all() {
		$raw = get_option( self::OPTION_KEY, '' );
		if ( ! $raw ) {
			return [];
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Upsert: an empty/unmatched $id appends a new preset; an $id matching an
	 * existing one updates it in place, keeping its original position (and
	 * its own created_at) rather than moving it to the end of the list.
	 *
	 * @return string The preset's id (generated fresh for a new preset).
	 */
	public static function save( $id, $label, $instruction ) {
		$presets = self::get_all();
		$label       = sanitize_text_field( $label );
		$instruction = sanitize_textarea_field( $instruction );

		$found = false;
		foreach ( $presets as &$preset ) {
			if ( $id && isset( $preset['id'] ) && $preset['id'] === $id ) {
				$preset['label']       = $label;
				$preset['instruction'] = $instruction;
				$found = true;
				break;
			}
		}
		unset( $preset );

		if ( ! $found ) {
			$id        = wp_generate_uuid4();
			$presets[] = [
				'id'          => $id,
				'label'       => $label,
				'instruction' => $instruction,
				'created_at'  => time(),
			];
			// Sanity cap, not a curation limit like History's FIFO -- these
			// are user-named and user-managed, not auto-logged, so dropping
			// the OLDEST rather than silently refusing the save is just a
			// backstop against unbounded option growth, not an expected path.
			if ( count( $presets ) > self::MAX_PRESETS ) {
				array_shift( $presets );
			}
		}

		self::save_all( $presets );
		return $id;
	}

	public static function delete( $id ) {
		$presets = self::get_all();
		$presets = array_values( array_filter( $presets, function ( $preset ) use ( $id ) {
			return ! isset( $preset['id'] ) || $preset['id'] !== $id;
		} ) );
		self::save_all( $presets );
	}

	private static function save_all( array $presets ) {
		// Do NOT add the wp_slash() fix BEW_AI_History::save_all() needs --
		// that's for update_metadata(), which unconditionally wp_unslash()es.
		// update_option() has no wp_unslash() in its path; adding wp_slash()
		// here was confirmed live to over-escape the stored JSON, breaking
		// json_decode() on read.
		update_option( self::OPTION_KEY, wp_json_encode( $presets ), false );
	}

	// ---- AJAX handlers. Same nonce/capability convention as every other AI
	// endpoint in this plugin (class-bew-ai-history.php, class-bew-ajax.php).

	private function require_access() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}
	}

	public function ajax_get_presets() {
		$this->require_access();
		wp_send_json_success( [ 'presets' => self::get_all() ] );
	}

	public function ajax_save_preset() {
		$this->require_access();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- require_access() above already verified the request nonce.
		$id          = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		$label       = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
		$instruction = isset( $_POST['instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instruction'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === trim( $label ) || '' === trim( $instruction ) ) {
			wp_send_json_error( __( 'A preset needs both a name and an instruction.', 'bosa-elementor-for-woocommerce' ) );
		}

		$saved_id = self::save( $id, $label, $instruction );
		wp_send_json_success( [ 'id' => $saved_id ] );
	}

	public function ajax_delete_preset() {
		$this->require_access();

		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- require_access() above already verified the request nonce.
		if ( '' === $id ) {
			wp_send_json_error( __( 'Missing preset id', 'bosa-elementor-for-woocommerce' ) );
		}

		self::delete( $id );
		wp_send_json_success();
	}
}
