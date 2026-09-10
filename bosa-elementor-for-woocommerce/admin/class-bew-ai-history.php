<?php
/**
 * BEW AI — Generation History.
 *
 * Shared, free-plugin-owned storage/retrieval for "browse and restore a
 * previous AI attempt for a field" -- every card (free and Pro alike) reads
 * and writes through this one class. Never gated behind a card's own toggle
 * -- History is part of whichever generator a user already has access to.
 *
 * One entry is logged per successful Apply, not per Generate/Preview -- "how
 * many values has this field actually had." Restoring an entry writes
 * through the SAME path Apply already uses, then logs a new entry itself: a
 * linear, append-only log, never branching.
 *
 * Storage lives on whichever entity actually owns the field being generated,
 * not on whichever widget happens to be showing it right now:
 *   - Elementor-settings family (Text/Image/Bulk/Repeater) -> the edited
 *     post's own meta, since the field is page-scoped Elementor data.
 *   - Content-adapter family (Products/Blog) -> the live record's own meta
 *     (product/post via post_meta, category via term_meta), since the field
 *     is that record's own data and should stay discoverable regardless of
 *     which page/widget currently happens to be displaying it.
 *
 * One meta key holds every field's history for that owner, JSON-encoded:
 *   { field_key: [ {id, value, provider_label, model_label, instruction,
 *   created_at}, ... up to MAX_ENTRIES_PER_FIELD, oldest dropped first ] }
 *
 * field_key is an opaque string each card builds itself -- for the
 * Elementor-settings family it must include the element id, not just the
 * setting key (`"{element_id}:{settings_key}"`), since one post's meta blob
 * covers every widget on that page and two different widgets can share a
 * setting name.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_AI_History {

	const META_KEY              = '_bew_ai_generation_history';
	const MAX_ENTRIES_PER_FIELD = 10;

	public function __construct() {
		add_action( 'wp_ajax_bew_ai_get_history', [ $this, 'ajax_get_history' ] );
		add_action( 'wp_ajax_bew_ai_log_history', [ $this, 'ajax_log_history' ] );
		add_action( 'wp_ajax_bew_ai_delete_history_item', [ $this, 'ajax_delete_history_item' ] );
		add_action( 'wp_ajax_bew_ai_clear_history', [ $this, 'ajax_clear_history' ] );
	}

	// ---- Storage-level API. Also called directly (no AJAX round trip) by a
	// content-adapter card's own server-side apply_record_fields() handler,
	// since that path is already a server request applying the field itself.

	/**
	 * @param string $owner_type 'post' or 'term'.
	 * @param int    $owner_id
	 * @param string $field_key  Opaque bucket key -- see class docblock.
	 * @param array  $entry      { value, provider_label, model_label, instruction }
	 * @return array|null The entry actually stored, with its generated id/created_at.
	 */
	public static function log( $owner_type, $owner_id, $field_key, array $entry ) {
		if ( ! self::is_valid_owner_type( $owner_type ) || ! $owner_id || '' === $field_key ) {
			return null;
		}

		$data = self::get_all( $owner_type, $owner_id );

		$stored = [
			'id'             => wp_generate_uuid4(),
			'value'          => isset( $entry['value'] ) ? $entry['value'] : '',
			'provider_label' => isset( $entry['provider_label'] ) ? (string) $entry['provider_label'] : '',
			'model_label'    => isset( $entry['model_label'] ) ? (string) $entry['model_label'] : '',
			'instruction'    => isset( $entry['instruction'] ) ? (string) $entry['instruction'] : '',
			'created_at'     => time(),
		];

		if ( ! isset( $data[ $field_key ] ) || ! is_array( $data[ $field_key ] ) ) {
			$data[ $field_key ] = [];
		}

		$data[ $field_key ][] = $stored;

		// FIFO prune, bounded per field key (not per owner) -- an entity with
		// many AI-generated fields still keeps a full 10-deep history on each.
		if ( count( $data[ $field_key ] ) > self::MAX_ENTRIES_PER_FIELD ) {
			$data[ $field_key ] = array_slice( $data[ $field_key ], -self::MAX_ENTRIES_PER_FIELD );
		}

		self::save_all( $owner_type, $owner_id, $data );

		return $stored;
	}

	/**
	 * @return array Entries for this field, newest first.
	 */
	public static function get( $owner_type, $owner_id, $field_key ) {
		$data    = self::get_all( $owner_type, $owner_id );
		$entries = ( isset( $data[ $field_key ] ) && is_array( $data[ $field_key ] ) ) ? $data[ $field_key ] : [];
		return array_reverse( $entries );
	}

	public static function delete_item( $owner_type, $owner_id, $field_key, $entry_id ) {
		$data = self::get_all( $owner_type, $owner_id );
		if ( empty( $data[ $field_key ] ) || ! is_array( $data[ $field_key ] ) ) {
			return;
		}

		$data[ $field_key ] = array_values( array_filter( $data[ $field_key ], function ( $e ) use ( $entry_id ) {
			return ! isset( $e['id'] ) || $e['id'] !== $entry_id;
		} ) );

		self::save_all( $owner_type, $owner_id, $data );
	}

	/**
	 * @param string|null $field_key Clear just this field, or every field on
	 *                                this owner when omitted (the "clear all
	 *                                history for this page/record" action).
	 */
	public static function clear( $owner_type, $owner_id, $field_key = null ) {
		if ( null === $field_key ) {
			self::save_all( $owner_type, $owner_id, [] );
			return;
		}

		$data = self::get_all( $owner_type, $owner_id );
		unset( $data[ $field_key ] );
		self::save_all( $owner_type, $owner_id, $data );
	}

	private static function get_all( $owner_type, $owner_id ) {
		$raw = 'term' === $owner_type
			? get_term_meta( $owner_id, self::META_KEY, true )
			: get_post_meta( $owner_id, self::META_KEY, true );

		if ( ! $raw ) {
			return [];
		}

		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : [];
	}

	private static function save_all( $owner_type, $owner_id, array $data ) {
		// update_post_meta()/update_term_meta() -> update_metadata() always
		// calls wp_unslash() on the value before storing it (WordPress's own
		// "undo legacy magic-quotes escaping on any write" convention) --
		// harmless for plain text, but wp_json_encode()'s own \uXXXX escapes
		// (e.g. an em dash in a provider label) are indistinguishable from
		// that escaping to wp_unslash(), so its backslash gets silently
		// eaten, corrupting a real "–" into a literal "u2013". wp_slash()
		// here adds exactly the one extra backslash wp_unslash() is about to
		// remove, so the JSON actually stored in the DB comes out correct.
		$encoded = wp_slash( wp_json_encode( $data ) );
		if ( 'term' === $owner_type ) {
			update_term_meta( $owner_id, self::META_KEY, $encoded );
		} else {
			update_post_meta( $owner_id, self::META_KEY, $encoded );
		}
	}

	private static function is_valid_owner_type( $owner_type ) {
		return in_array( $owner_type, [ 'post', 'term' ], true );
	}

	// ---- AJAX handlers. Same nonce/capability convention as every other AI
	// endpoint in this plugin (class-bew-ajax.php's ai_generate_text()).

	private function require_access() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}
	}

	/**
	 * @return array [ owner_type, owner_id, field_key ] -- field_key may be
	 *                empty string for endpoints that don't need one (clear-all).
	 */
	private function request_owner() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- every
		// caller of this method is an AJAX handler that already ran
		// require_access()'s check_ajax_referer() first; the sniff can't see
		// across that method call.
		$owner_type = isset( $_POST['owner_type'] ) ? sanitize_text_field( wp_unslash( $_POST['owner_type'] ) ) : '';
		$owner_id   = isset( $_POST['owner_id'] ) ? absint( $_POST['owner_id'] ) : 0;
		$field_key  = isset( $_POST['field_key'] ) ? sanitize_text_field( wp_unslash( $_POST['field_key'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! self::is_valid_owner_type( $owner_type ) || ! $owner_id ) {
			wp_send_json_error( __( 'Invalid history owner', 'bosa-elementor-for-woocommerce' ) );
		}

		return [ $owner_type, $owner_id, $field_key ];
	}

	public function ajax_get_history() {
		$this->require_access();
		list( $owner_type, $owner_id, $field_key ) = $this->request_owner();

		if ( '' === $field_key ) {
			wp_send_json_error( __( 'Missing field key', 'bosa-elementor-for-woocommerce' ) );
		}

		wp_send_json_success( [ 'entries' => self::get( $owner_type, $owner_id, $field_key ) ] );
	}

	public function ajax_log_history() {
		$this->require_access();
		list( $owner_type, $owner_id, $field_key ) = $this->request_owner();

		if ( '' === $field_key ) {
			wp_send_json_error( __( 'Missing field key', 'bosa-elementor-for-woocommerce' ) );
		}

		// The client always sends value JSON-encoded (a quoted string decodes
		// to a string, an object/array decodes to one) -- decoding once here
		// removes any ambiguity about which shape was sent, rather than the
		// server having to guess from the raw bytes.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- require_access() above already verified the request nonce.
		$value_raw = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : 'null'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value     = self::sanitize_value( json_decode( $value_raw, true ) );

		$entry = self::log( $owner_type, $owner_id, $field_key, [
			'value'          => $value,
			'provider_label' => isset( $_POST['provider_label'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_label'] ) ) : '',
			'model_label'    => isset( $_POST['model_label'] ) ? sanitize_text_field( wp_unslash( $_POST['model_label'] ) ) : '',
			'instruction'    => isset( $_POST['instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instruction'] ) ) : '',
		] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		wp_send_json_success( [ 'entry' => $entry ] );
	}

	public function ajax_delete_history_item() {
		$this->require_access();
		list( $owner_type, $owner_id, $field_key ) = $this->request_owner();
		$entry_id = isset( $_POST['entry_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- require_access() above already verified the request nonce.

		if ( '' === $field_key || '' === $entry_id ) {
			wp_send_json_error( __( 'Missing field key or entry id', 'bosa-elementor-for-woocommerce' ) );
		}

		self::delete_item( $owner_type, $owner_id, $field_key, $entry_id );
		wp_send_json_success();
	}

	// field_key is optional here, unlike get/log/delete -- omitted clears
	// every field for this owner; present clears just that one field, so a
	// shared owner (e.g. a repeater's rows sharing the page) doesn't wipe
	// every other item's history too.
	public function ajax_clear_history() {
		$this->require_access();
		list( $owner_type, $owner_id, $field_key ) = $this->request_owner();

		self::clear( $owner_type, $owner_id, '' === $field_key ? null : $field_key );
		wp_send_json_success();
	}

	/**
	 * A history value is either a plain string (simple text field) or a
	 * small object -- {id,url} for an image field, or a repeater's array of
	 * row objects -- never complex enough to need more than
	 * textarea-level sanitization per scalar it contains.
	 */
	private static function sanitize_value( $decoded ) {
		if ( is_array( $decoded ) ) {
			return array_map( [ __CLASS__, 'sanitize_value' ], $decoded );
		}

		return is_scalar( $decoded ) ? sanitize_textarea_field( (string) $decoded ) : '';
	}
}
