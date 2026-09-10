<?php
/**
 * BEW AJAX Handler
 *
 * Handles AJAX requests from the BEW admin dashboard.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_AJAX {

	public function __construct() {
		add_action( 'wp_ajax_bew_toggle_widget',    [ $this, 'toggle_widget' ] );
		add_action( 'wp_ajax_bew_toggle_module',    [ $this, 'toggle_module' ] );
		add_action( 'wp_ajax_bew_save_settings',    [ $this, 'save_settings' ] );
		add_action( 'wp_ajax_bew_rollback',         [ $this, 'rollback' ] );
		add_action( 'wp_ajax_bew_save_widgets',     [ $this, 'save_widgets' ] );
		add_action( 'wp_ajax_bew_save_modules',     [ $this, 'save_modules' ] );
		add_action( 'wp_ajax_bew_save_ai_features', [ $this, 'save_ai_features' ] );
		add_action( 'wp_ajax_bew_save_ai_provider', [ $this, 'save_ai_provider' ] );
		add_action( 'wp_ajax_bew_delete_ai_provider', [ $this, 'delete_ai_provider' ] );
		add_action( 'wp_ajax_bew_fetch_ai_models', [ $this, 'fetch_ai_models' ] );
		add_action( 'wp_ajax_bew_set_default_ai_provider', [ $this, 'set_default_ai_provider' ] );
		add_action( 'wp_ajax_bew_ai_check_connected', [ $this, 'ai_check_connected' ] );
		add_action( 'wp_ajax_bew_ai_generate_text', [ $this, 'ai_generate_text' ] );
	}

	public function toggle_widget() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';
		$enabled   = isset( $_POST['enabled'] ) ? rest_sanitize_boolean( $_POST['enabled'] ) : false;

		if ( ! $widget_id ) {
			wp_send_json_error( __( 'Invalid widget ID', 'bosa-elementor-for-woocommerce' ) );
		}

		BEW_Plugin_Settings::toggle_widget( $widget_id, $enabled );
		wp_send_json_success( [ 'widget_id' => $widget_id, 'enabled' => $enabled ] );
	}

	public function toggle_module() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$module_id = isset( $_POST['module_id'] ) ? sanitize_text_field( wp_unslash( $_POST['module_id'] ) ) : '';
		$enabled   = isset( $_POST['enabled'] ) ? rest_sanitize_boolean( $_POST['enabled'] ) : false;

		if ( ! $module_id ) {
			wp_send_json_error( __( 'Invalid module ID', 'bosa-elementor-for-woocommerce' ) );
		}

		BEW_Plugin_Settings::toggle_module( $module_id, $enabled );
		wp_send_json_success( [ 'module_id' => $module_id, 'enabled' => $enabled ] );
	}

	/**
	 * Boolean keys use rest_sanitize_boolean(); all others use sanitize_text_field().
	 */
	public function save_settings() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : [];
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		$raw_settings = is_array( $raw ) ? $raw : [];

		$boolean_keys = [ 'template_library' ];

		foreach ( $raw_settings as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! $key ) {
				continue;
			}
			if ( in_array( $key, $boolean_keys, true ) ) {
				BEW_Plugin_Settings::save( $key, rest_sanitize_boolean( $value ) );
			} else {
				BEW_Plugin_Settings::save( $key, sanitize_text_field( wp_unslash( (string) $value ) ) );
			}
		}

		wp_send_json_success( [ 'message' => __( 'Settings saved successfully', 'bosa-elementor-for-woocommerce' ) ] );
	}

	public function save_widgets() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$raw = isset( $_POST['widgets'] ) ? wp_unslash( $_POST['widgets'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			wp_send_json_error( __( 'Invalid data', 'bosa-elementor-for-woocommerce' ) );
		}

		$toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );

		foreach ( $raw as $widget_id => $enabled ) {
			$widget_id = sanitize_key( $widget_id );
			if ( $widget_id ) {
				$toggles[ $widget_id ] = (bool) rest_sanitize_boolean( $enabled );
			}
		}

		BEW_Plugin_Settings::save( 'widget_toggles', $toggles );

		wp_send_json_success( [ 'message' => __( 'Widget settings saved', 'bosa-elementor-for-woocommerce' ) ] );
	}

	public function save_ai_features() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$raw = isset( $_POST['features'] ) ? wp_unslash( $_POST['features'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			wp_send_json_error( __( 'Invalid data', 'bosa-elementor-for-woocommerce' ) );
		}

		$toggles = BEW_Plugin_Settings::get( 'ai_feature_toggles', [] );

		foreach ( $raw as $feature_id => $enabled ) {
			$feature_id = sanitize_key( $feature_id );
			if ( $feature_id ) {
				$toggles[ $feature_id ] = (bool) rest_sanitize_boolean( $enabled );
			}
		}

		BEW_Plugin_Settings::save( 'ai_feature_toggles', $toggles );

		wp_send_json_success( [ 'message' => __( 'AI feature settings saved', 'bosa-elementor-for-woocommerce' ) ] );
	}

	public function save_modules() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$raw = isset( $_POST['modules'] ) ? wp_unslash( $_POST['modules'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}
		if ( ! is_array( $raw ) ) {
			wp_send_json_error( __( 'Invalid data', 'bosa-elementor-for-woocommerce' ) );
		}

		$toggles = BEW_Plugin_Settings::get( 'module_toggles', [] );

		foreach ( $raw as $mod_id => $enabled ) {
			$mod_id = sanitize_key( preg_replace( '/^module_/', '', $mod_id ) );
			if ( $mod_id ) {
				$toggles[ $mod_id ] = (bool) rest_sanitize_boolean( $enabled );
			}
		}

		BEW_Plugin_Settings::save( 'module_toggles', $toggles );

		wp_send_json_success( [ 'message' => __( 'Module settings saved', 'bosa-elementor-for-woocommerce' ) ] );
	}

	/**
	 * Add a new AI provider credential. Only the AES-256-CBC encrypted key
	 * and a 4-char preview are persisted; the raw value is never stored.
	 */
	public function save_ai_provider() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$type        = isset( $_POST['provider_type'] ) ? sanitize_key( wp_unslash( $_POST['provider_type'] ) ) : '';
		$label       = isset( $_POST['provider_label'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_label'] ) ) : '';
		$api_key     = isset( $_POST['api_key'] ) ? preg_replace( '/[\r\n\0]/', '', trim( (string) wp_unslash( $_POST['api_key'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$model       = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		$model_label = isset( $_POST['model_label'] ) ? sanitize_text_field( wp_unslash( $_POST['model_label'] ) ) : '';

		$types = function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : [];

		if ( ! $type || ! isset( $types[ $type ] ) ) {
			wp_send_json_error( __( 'Invalid provider type', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( '' === $api_key ) {
			wp_send_json_error( __( 'API key is required', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( '' === $model ) {
			wp_send_json_error( __( 'Please load and select a model before adding this provider', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( '' === $label ) {
			$label = $types[ $type ]['label'];
		}

		if ( '' === $model_label ) {
			$model_label = $model;
		}

		$api_key_enc = BEW_Crypto::encrypt( $api_key );

		if ( false === $api_key_enc ) {
			wp_send_json_error( __( 'Could not securely store this API key -- your server is missing the PHP OpenSSL extension (or it failed unexpectedly). Please contact your host.', 'bosa-elementor-for-woocommerce' ) );
		}

		$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );

		// OpenAI becomes the default automatically the first time it's connected and nothing else is already marked default.
		$is_default = ( 'openai' === $type ) && ! bew_ai_providers_has_default( $providers );

		$entry = [
			'id'          => wp_generate_uuid4(),
			'type'        => $type,
			'label'       => $label,
			'api_key_enc' => $api_key_enc,
			'key_preview' => substr( $api_key, -4 ),
			'model'       => $model,
			'model_label' => $model_label,
			'is_default'  => $is_default,
			'created_at'  => current_time( 'mysql' ),
		];

		$providers[] = $entry;
		BEW_Plugin_Settings::save( 'ai_providers', $providers );

		$this->warm_image_model_cache( $type, $api_key );

		wp_send_json_success( [
			'message'  => __( 'AI provider added', 'bosa-elementor-for-woocommerce' ),
			'provider' => [
				'id'          => $entry['id'],
				'label'       => $entry['label'],
				'type_label'  => $types[ $type ]['label'],
				'key_preview' => $entry['key_preview'],
				'model_label' => $entry['model_label'],
				'is_default'  => $entry['is_default'],
				'created_at'  => $entry['created_at'],
			],
		] );
	}

	/**
	 * Mark one connected provider as the default used for generation when a
	 * request doesn't specify one.
	 */
	public function set_default_ai_provider() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$id = isset( $_POST['provider_id'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_id'] ) ) : '';
		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid provider ID', 'bosa-elementor-for-woocommerce' ) );
		}

		$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );
		$found     = false;

		foreach ( $providers as &$p ) {
			$is_match        = isset( $p['id'] ) && $p['id'] === $id;
			$p['is_default'] = $is_match;
			$found           = $found || $is_match;
		}
		unset( $p );

		if ( ! $found ) {
			wp_send_json_error( __( 'Provider not found', 'bosa-elementor-for-woocommerce' ) );
		}

		BEW_Plugin_Settings::save( 'ai_providers', $providers );

		wp_send_json_success( [ 'message' => __( 'Default provider updated', 'bosa-elementor-for-woocommerce' ) ] );
	}

	/**
	 * Lightweight check used by the in-editor "connect an AI provider" prompt
	 * to re-verify connection status without a full editor reload.
	 */
	public function ai_check_connected() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		wp_send_json_success( [ 'connected' => bew_has_connected_ai_provider() ] );
	}

	/**
	 * Generate replacement text for one widget's AI-eligible fields.
	 * The requested field list is re-validated against the field-role map
	 * server-side -- the client's selection is never trusted directly.
	 */
	public function ai_generate_text() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$widget_type = isset( $_POST['widget_type'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_type'] ) ) : '';

		$fields_raw = isset( $_POST['fields'] ) ? wp_unslash( $_POST['fields'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$fields_raw = is_string( $fields_raw ) ? json_decode( $fields_raw, true ) : $fields_raw;
		$fields     = bew_validate_ai_requested_fields( $widget_type, $fields_raw );

		if ( empty( $fields['simple'] ) && empty( $fields['repeater'] ) ) {
			wp_send_json_error( __( 'No eligible fields selected', 'bosa-elementor-for-woocommerce' ) );
		}

		$current_raw    = isset( $_POST['current_values'] ) ? wp_unslash( $_POST['current_values'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$current_raw    = is_string( $current_raw ) ? json_decode( $current_raw, true ) : $current_raw;
		$current_values = is_array( $current_raw ) ? bew_sanitize_ai_current_values( $current_raw ) : [];

		$generated_raw     = isset( $_POST['already_generated'] ) ? wp_unslash( $_POST['already_generated'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$generated_raw     = is_string( $generated_raw ) ? json_decode( $generated_raw, true ) : $generated_raw;
		$already_generated = is_array( $generated_raw ) ? $generated_raw : [];

		// Same shape as current_values (string per simple field, array of row
		// objects per repeater) -- whatever the preview card showed but the
		// user hasn't Applied yet, sent back so a regenerate doesn't just come
		// back with a near-identical rewrite of the same rejected text.
		$previous_raw     = isset( $_POST['previous_attempt'] ) ? wp_unslash( $_POST['previous_attempt'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$previous_raw     = is_string( $previous_raw ) ? json_decode( $previous_raw, true ) : $previous_raw;
		$previous_attempt = is_array( $previous_raw ) ? bew_sanitize_ai_current_values( $previous_raw ) : [];

		$instruction = isset( $_POST['instruction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instruction'] ) ) : '';
		$provider_id = isset( $_POST['provider_id'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_id'] ) ) : '';

		$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );
		$provider  = null;

		if ( $provider_id ) {
			foreach ( $providers as $p ) {
				if ( isset( $p['id'] ) && $p['id'] === $provider_id ) {
					$provider = $p;
					break;
				}
			}
		}

		if ( ! $provider ) {
			$provider = bew_get_default_ai_provider();
		}

		if ( ! $provider || empty( $provider['model'] ) ) {
			wp_send_json_error( __( 'Connect an AI provider first', 'bosa-elementor-for-woocommerce' ) );
		}

		$api_key = BEW_Crypto::decrypt( $provider['api_key_enc'] );

		// Optional per-generation override of the connection's saved model --
		// re-validated against the cache here rather than trusted directly,
		// same as every other client-supplied value on this endpoint.
		// Invalid/empty falls back to the connection's saved default.
		$model = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '';
		if ( $model && function_exists( 'bew_get_cached_ai_provider_models_readonly' ) ) {
			$valid_ids = wp_list_pluck( bew_get_cached_ai_provider_models_readonly( $provider['type'], $api_key, 'text' ), 'id' );
			if ( ! in_array( $model, $valid_ids, true ) ) {
				$model = '';
			}
		} else {
			$model = '';
		}
		if ( ! $model ) {
			$model = $provider['model'];
		}

		$prompt = bew_build_ai_generation_prompt( $widget_type, $fields['simple'], $fields['repeater'], $current_values, $already_generated, $previous_attempt, $instruction );

		$result = bew_generate_ai_text( $provider['type'], $api_key, $model, $prompt );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$map        = function_exists( 'bosa_ewc_get_ai_field_map' ) ? bosa_ewc_get_ai_field_map() : [];
		$widget_map = isset( $map[ $widget_type ] ) ? $map[ $widget_type ] : [];

		$safe_result = [];

		foreach ( $fields['simple'] as $field_key ) {
			if ( isset( $result[ $field_key ] ) ) {
				$safe_result[ $field_key ] = sanitize_textarea_field( (string) $result[ $field_key ] );
			}
		}

		foreach ( $fields['repeater'] as $field_key ) {
			if ( empty( $result[ $field_key ] ) || ! is_array( $result[ $field_key ] ) || ! isset( $widget_map[ $field_key ] ) ) {
				continue;
			}

			$sub_map = $widget_map[ $field_key ];
			$rows    = [];

			foreach ( $result[ $field_key ] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$clean_row = [];
				foreach ( $sub_map as $sub_key => $sub_role ) {
					if ( isset( $row[ $sub_key ] ) ) {
						$clean_row[ $sub_key ] = sanitize_textarea_field( (string) $row[ $sub_key ] );
					}
				}
				if ( $clean_row ) {
					$rows[] = $clean_row;
				}
			}

			if ( $rows ) {
				$safe_result[ $field_key ] = $rows;
			}
		}

		if ( empty( $safe_result ) ) {
			wp_send_json_error( __( 'The AI did not return usable text. Please try again.', 'bosa-elementor-for-woocommerce' ) );
		}

		$types = function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : [];

		// $model may be an override, not the connection's saved default --
		// look up its real label rather than assuming the saved one still
		// matches what was actually used.
		$model_label = $model;
		if ( $model === $provider['model'] && ! empty( $provider['model_label'] ) ) {
			$model_label = $provider['model_label'];
		} elseif ( function_exists( 'bew_get_cached_ai_provider_models_readonly' ) ) {
			foreach ( bew_get_cached_ai_provider_models_readonly( $provider['type'], $api_key, 'text' ) as $m ) {
				if ( $m['id'] === $model ) {
					$model_label = $m['label'];
					break;
				}
			}
		}

		wp_send_json_success( [
			'fields'         => $safe_result,
			'provider_label' => isset( $provider['label'] ) ? $provider['label'] : '',
			'type_label'     => isset( $types[ $provider['type'] ]['label'] ) ? $types[ $provider['type'] ]['label'] : $provider['type'],
			'model_label'    => $model_label,
		] );
	}

	/**
	 * Fetch a provider's live model list for the Add-Provider form (key not
	 * yet saved). Returns status 'ok' with models, 'manual' when the provider
	 * has no live detection, or 'error' when a live fetch was attempted but
	 * failed -- the client falls back to manual model-ID entry either way.
	 */
	public function fetch_ai_models() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$type    = isset( $_POST['provider_type'] ) ? sanitize_key( wp_unslash( $_POST['provider_type'] ) ) : '';
		$api_key = isset( $_POST['api_key'] ) ? preg_replace( '/[\r\n\0]/', '', trim( (string) wp_unslash( $_POST['api_key'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$types = function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : [];

		if ( ! $type || ! isset( $types[ $type ] ) ) {
			wp_send_json_error( __( 'Invalid provider type', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( '' === $api_key ) {
			wp_send_json_error( __( 'API key is required', 'bosa-elementor-for-woocommerce' ) );
		}

		wp_send_json_success( $this->build_model_response( $type, $api_key ) );
	}

	/**
	 * Also warms the image-capability model cache for this connection, when
	 * Pro's Image Generation is active and this type actually supports it --
	 * so connecting a provider primes every AI feature that reads from it,
	 * not just Text Generation (the weekly auto-refresh cron keeps both
	 * caches current after that). A no-op in a free-only install.
	 *
	 * @param string $type
	 * @param string $api_key Raw (unencrypted) API key.
	 */
	private function warm_image_model_cache( $type, $api_key ) {
		if ( ! function_exists( 'bew_ai_image_capable_provider_types' ) || ! function_exists( 'bew_get_cached_ai_provider_models' ) ) {
			return;
		}
		if ( ! in_array( $type, bew_ai_image_capable_provider_types(), true ) ) {
			return;
		}
		bew_get_cached_ai_provider_models( $type, $api_key, true, 'image' );
	}

	/**
	 * Look up a stored provider entry by ID.
	 *
	 * @param string $id Provider entry ID.
	 * @return array|null
	 */
	private function find_provider( $id ) {
		$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );
		foreach ( $providers as $p ) {
			if ( isset( $p['id'] ) && $p['id'] === $id ) {
				return $p;
			}
		}
		return null;
	}

	/**
	 * Build the {status, message, models} payload used by fetch_ai_models().
	 *
	 * @param string $type          Provider type key.
	 * @param string $api_key       Raw (unencrypted) API key.
	 * @param bool   $force_refresh Bypass the cache.
	 * @return array{status: string, message: string, models: array}
	 */
	private function build_model_response( $type, $api_key, $force_refresh = false ) {
		$models = bew_get_cached_ai_provider_models( $type, $api_key, $force_refresh );

		if ( is_wp_error( $models ) ) {
			$is_unsupported = 'bew_ai_no_live_fetch' === $models->get_error_code();
			return [
				'status'  => $is_unsupported ? 'manual' : 'error',
				'message' => $models->get_error_message(),
				'models'  => [],
			];
		}

		return [
			'status'  => 'ok',
			'message' => '',
			'models'  => $models,
		];
	}

	/**
	 * Remove an AI provider credential by ID.
	 */
	public function delete_ai_provider() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$id = isset( $_POST['provider_id'] ) ? sanitize_text_field( wp_unslash( $_POST['provider_id'] ) ) : '';

		if ( ! $id ) {
			wp_send_json_error( __( 'Invalid provider ID', 'bosa-elementor-for-woocommerce' ) );
		}

		$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );
		$filtered  = array_values( array_filter( $providers, function ( $p ) use ( $id ) {
			return ! isset( $p['id'] ) || $p['id'] !== $id;
		} ) );

		if ( count( $filtered ) === count( $providers ) ) {
			wp_send_json_error( __( 'Provider not found', 'bosa-elementor-for-woocommerce' ) );
		}

		BEW_Plugin_Settings::save( 'ai_providers', $filtered );

		wp_send_json_success( [ 'message' => __( 'AI provider removed', 'bosa-elementor-for-woocommerce' ) ] );
	}

	public function rollback() {
		check_ajax_referer( 'bew-settings-nonce', 'nonce' );

		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_send_json_error( __( 'Unauthorized access', 'bosa-elementor-for-woocommerce' ) );
		}

		$version = isset( $_POST['version'] ) ? sanitize_text_field( wp_unslash( $_POST['version'] ) ) : '';
		if ( ! $version || ! preg_match( '/^\d+\.\d+(\.\d+)*$/', $version ) ) {
			wp_send_json_error( __( 'Invalid version specified', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( version_compare( $version, BEW_Plugin_Settings::ROLLBACK_MIN_VERSION, '<' ) ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: minimum version */
					__( 'Rollback is only supported for version %s and above', 'bosa-elementor-for-woocommerce' ),
					BEW_Plugin_Settings::ROLLBACK_MIN_VERSION
				)
			);
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$api = plugins_api( 'plugin_information', [
			'slug'   => 'bosa-elementor-for-woocommerce',
			'fields' => [ 'versions' => true ],
		] );

		if ( is_wp_error( $api ) || empty( $api->versions[ $version ] ) ) {
			wp_send_json_error( __( 'Version not found on WordPress.org', 'bosa-elementor-for-woocommerce' ) );
		}

		$download_url = $api->versions[ $version ];
		$plugin_file  = 'bosa-elementor-for-woocommerce/bosa-elementor-for-woocommerce.php';

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( ! is_object( $update_plugins ) ) {
			$update_plugins           = new stdClass();
			$update_plugins->response = [];
		}

		$update_info              = new stdClass();
		$update_info->slug        = 'bosa-elementor-for-woocommerce';
		$update_info->plugin      = $plugin_file;
		$update_info->new_version = $version;
		$update_info->package     = $download_url;

		$update_plugins->response[ $plugin_file ] = $update_info;
		set_site_transient( 'update_plugins', $update_plugins );

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->upgrade( $plugin_file );

		$update_plugins = get_site_transient( 'update_plugins' );
		if ( is_object( $update_plugins ) && isset( $update_plugins->response[ $plugin_file ] ) ) {
			unset( $update_plugins->response[ $plugin_file ] );
			set_site_transient( 'update_plugins', $update_plugins );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		if ( false === $result ) {
			$errors = $skin->get_errors();
			$msg    = ( is_wp_error( $errors ) && $errors->has_errors() )
				? $errors->get_error_message()
				: __( 'Rollback failed. Please try again.', 'bosa-elementor-for-woocommerce' );
			wp_send_json_error( $msg );
		}

		delete_transient( 'bew_rollback_version' );

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: version number */
				__( 'Successfully rolled back to version %s', 'bosa-elementor-for-woocommerce' ),
				$version
			),
		] );
	}
}
