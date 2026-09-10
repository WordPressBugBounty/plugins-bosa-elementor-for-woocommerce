<?php
/**
 * AI text generation for the in-editor "BEW AI" feature.
 *
 * Mirrors the fetcher pattern in ai-provider-models.php: one function per
 * provider, a filterable dispatcher, shared response validation. Every call
 * asks the model to return a single JSON object keyed by the widget's own
 * setting keys, so one request can cover several fields on one widget at once.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate an HTTP response common to every provider: network error, status
 * code, outer JSON envelope. Provider-specific functions extract the actual
 * completion text from the validated body themselves (each provider nests it
 * differently).
 *
 * @param array|WP_Error $response
 * @return array|WP_Error
 */
function bew_validate_ai_http_response( $response ) {
	if ( is_wp_error( $response ) ) {
		// Connection-level failure (timeout, DNS, refused), distinct from the
		// provider itself responding with a rejection (handled below). Lead
		// with a plain explanation and append the raw WP_Error detail, same
		// base+detail pattern as bew_ai_http_error_message() uses.
		$detail = trim( $response->get_error_message() );
		$base   = __( 'Could not reach the AI provider (a connection issue, e.g. a timeout) -- this is not related to your API key or usage limits. Please try again.', 'bosa-elementor-for-woocommerce' );

		return new WP_Error( 'bew_ai_connection_failed', $detail ? $base . ' ' . $detail : $base );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}

	if ( ! is_array( $body ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return $body;
}

/**
 * Pull a JSON object out of a model's raw text reply, tolerating a
 * ```json ... ``` fence some models add despite being told not to.
 *
 * @param string $text
 * @return array|WP_Error
 */
function bew_extract_json_from_ai_text( $text ) {
	$text = trim( (string) $text );

	if ( preg_match( '/^```(?:json)?\s*(.*?)\s*```$/s', $text, $matches ) ) {
		$text = $matches[1];
	}

	$decoded = json_decode( $text, true );
	if ( ! is_array( $decoded ) ) {
		return new WP_Error( 'bew_ai_invalid_json', __( 'The AI response could not be understood. Please try again.', 'bosa-elementor-for-woocommerce' ) );
	}

	return $decoded;
}

/**
 * @return string
 */
function bew_ai_system_instruction() {
	return 'You are a professional website copywriter. Always respond with ONLY a valid JSON object, no markdown formatting, no explanation.';
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_openai_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'           => $model,
			'messages'        => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
			'response_format' => [ 'type' => 'json_object' ],
			'temperature'     => 0.8,
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_anthropic_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
		'headers' => [
			'x-api-key'         => $api_key,
			'anthropic-version' => '2023-06-01',
			'Content-Type'      => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'      => $model,
			// Anthropic is the only provider here requiring an explicit
			// output cap. 4096 avoids silently truncating long-form output
			// (e.g. Blog Generator's "content" field) while staying within
			// every current Claude 3.x model's own max_tokens ceiling.
			'max_tokens' => 4096,
			'system'     => bew_ai_system_instruction(),
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['content'][0]['text'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['content'][0]['text'] );
}

/**
 * @param string $api_key
 * @param string $model Full "models/gemini-..." path, as returned by bew_fetch_google_models().
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_google_text( $api_key, $model, $prompt ) {
	$model = ( 0 === strpos( $model, 'models/' ) ) ? $model : 'models/' . $model;
	$url   = 'https://generativelanguage.googleapis.com/v1beta/' . $model . ':generateContent?key=' . rawurlencode( $api_key );

	$response = wp_remote_post( $url, [
		'headers' => [ 'Content-Type' => 'application/json' ],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'contents'         => [
				[ 'parts' => [ [ 'text' => bew_ai_system_instruction() . "\n\n" . $prompt ] ] ],
			],
			'generationConfig' => [
				'temperature'      => 0.8,
				'responseMimeType' => 'application/json',
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['candidates'][0]['content']['parts'][0]['text'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_perplexity_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.perplexity.ai/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'    => $model,
			'messages' => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_xai_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.x.ai/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'    => $model,
			'messages' => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_openrouter_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://openrouter.ai/api/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'    => $model,
			'messages' => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_mistral_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.mistral.ai/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'    => $model,
			'messages' => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * @param string $api_key
 * @param string $model
 * @param string $prompt
 * @return array|WP_Error
 */
function bew_generate_groq_text( $api_key, $model, $prompt ) {
	$response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', [
		'headers' => [
			'Authorization' => 'Bearer ' . $api_key,
			'Content-Type'  => 'application/json',
		],
		'timeout' => 60,
		'body'    => wp_json_encode( [
			'model'    => $model,
			'messages' => [
				[ 'role' => 'system', 'content' => bew_ai_system_instruction() ],
				[ 'role' => 'user', 'content' => $prompt ],
			],
		] ),
	] );

	$body = bew_validate_ai_http_response( $response );
	if ( is_wp_error( $body ) ) {
		return $body;
	}

	if ( empty( $body['choices'][0]['message']['content'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	return bew_extract_json_from_ai_text( $body['choices'][0]['message']['content'] );
}

/**
 * Dispatch to the right provider's text generator.
 *
 * @param string $type    Provider type key.
 * @param string $api_key Raw (unencrypted) API key.
 * @param string $model   Model ID.
 * @param string $prompt  Fully-built prompt text.
 * @return array|WP_Error
 */
function bew_generate_ai_text( $type, $api_key, $model, $prompt ) {
	/**
	 * Filter the map of provider type => callable that generates text for it.
	 *
	 * @param array<string, callable> $generators
	 */
	$generators = apply_filters( 'bew_ai_provider_text_generators', [
		'openai'     => 'bew_generate_openai_text',
		'anthropic'  => 'bew_generate_anthropic_text',
		'google'     => 'bew_generate_google_text',
		'perplexity' => 'bew_generate_perplexity_text',
		'xai'        => 'bew_generate_xai_text',
		'openrouter' => 'bew_generate_openrouter_text',
		'mistral'    => 'bew_generate_mistral_text',
		'groq'       => 'bew_generate_groq_text',
	] );

	if ( empty( $generators[ $type ] ) || ! is_callable( $generators[ $type ] ) ) {
		return new WP_Error( 'bew_ai_unsupported_provider', __( 'This provider does not support text generation.', 'bosa-elementor-for-woocommerce' ) );
	}

	return call_user_func( $generators[ $type ], $api_key, $model, $prompt );
}

/**
 * Split the requested field keys into simple (single text value) and
 * repeater (array-of-rows) fields, keeping only keys that are actually
 * mapped for this widget type -- the server stays the source of truth for
 * what's eligible, never the client's own claim about a field's shape.
 *
 * @param string $widget_type
 * @param mixed  $requested_fields
 * @return array{simple: string[], repeater: string[]}
 */
function bew_validate_ai_requested_fields( $widget_type, $requested_fields ) {
	$map = function_exists( 'bosa_ewc_get_ai_field_map' ) ? bosa_ewc_get_ai_field_map() : [];
	if ( ! isset( $map[ $widget_type ] ) ) {
		return [
			'simple'   => [],
			'repeater' => [],
		];
	}

	$widget_map = $map[ $widget_type ];
	$simple     = [];
	$repeater   = [];

	foreach ( (array) $requested_fields as $field_key ) {
		$field_key = sanitize_key( $field_key );
		if ( ! isset( $widget_map[ $field_key ] ) ) {
			continue;
		}
		if ( is_string( $widget_map[ $field_key ] ) ) {
			$simple[] = $field_key;
		} elseif ( is_array( $widget_map[ $field_key ] ) ) {
			$repeater[] = $field_key;
		}
	}

	return [
		'simple'   => $simple,
		'repeater' => $repeater,
	];
}

/**
 * Recursively sanitize a `current_values` payload as sent by the client:
 * simple fields are plain strings, repeater fields are arrays of row objects
 * (already trimmed client-side to just the mapped sub-fields).
 *
 * @param array $raw
 * @return array
 */
function bew_sanitize_ai_current_values( $raw ) {
	$out = [];

	foreach ( (array) $raw as $key => $value ) {
		$key = sanitize_key( $key );

		if ( is_array( $value ) ) {
			$rows = [];
			foreach ( $value as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$clean_row = [];
				foreach ( $row as $sub_key => $sub_val ) {
					$clean_row[ sanitize_key( $sub_key ) ] = sanitize_textarea_field( (string) $sub_val );
				}
				$rows[] = $clean_row;
			}
			$out[ $key ] = $rows;
		} else {
			$out[ $key ] = sanitize_textarea_field( (string) $value );
		}
	}

	return $out;
}

/**
 * Render a repeater's rows as a compact "item N: key="val", key="val"; ..."
 * string for use inside a generation prompt. Shared between the "current
 * values" and "you already suggested this" context blocks below.
 *
 * @param array $rows
 * @param array $sub_map sub_key => role, defines which sub-keys to include.
 * @return string Empty string if there's nothing to describe.
 */
function bew_ai_describe_repeater_rows( $rows, $sub_map ) {
	$examples = [];

	foreach ( (array) $rows as $i => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$parts = [];
		foreach ( $sub_map as $sub_key => $sub_role ) {
			if ( ! empty( $row[ $sub_key ] ) ) {
				$parts[] = $sub_key . '="' . $row[ $sub_key ] . '"';
			}
		}
		if ( $parts ) {
			$examples[] = 'item ' . ( $i + 1 ) . ': ' . implode( ', ', $parts );
		}
	}

	return implode( '; ', $examples );
}

/**
 * Build the prompt sent to the provider for one generation request, covering
 * every requested field (simple and repeater alike) on a single widget in
 * one shot.
 *
 * Context rule: a field's first-ever regeneration uses its current value as
 * topic/tone grounding; once already regenerated this session, re-mixing in
 * the old value a second time would fight the direction the user steered it.
 *
 * $previous_attempt is whatever was shown in the preview card a moment ago
 * but never Applied. Without it, a repeated regenerate-without-apply request
 * carries the identical prompt every time and models tend to converge on
 * near-identical phrasing -- flagging the rejected attempt explicitly asks
 * for a genuinely different one instead.
 *
 * @param string   $widget_type
 * @param string[] $simple_fields     Validated simple field keys.
 * @param string[] $repeater_fields   Validated repeater field keys.
 * @param array    $current_values    field_key => string, or array of row objects for repeaters.
 * @param array    $already_generated field_key => true if BEW AI already generated this field once this session.
 * @param array    $previous_attempt  field_key => string or array of row objects -- last preview shown but not yet applied, if any.
 * @param string   $instruction       Optional free-text instruction from the user.
 * @return string
 */
function bew_build_ai_generation_prompt( $widget_type, $simple_fields, $repeater_fields, $current_values, $already_generated, $previous_attempt, $instruction ) {
	$map        = function_exists( 'bosa_ewc_get_ai_field_map' ) ? bosa_ewc_get_ai_field_map() : [];
	$widget_map = isset( $map[ $widget_type ] ) ? $map[ $widget_type ] : [];

	$lines   = [];
	$lines[] = 'You are writing website copy. Respond with a single JSON object whose keys are exactly the field keys listed below.';
	$lines[] = 'For a simple field, the value is a new text string. For a field marked REPEATER, the value must be a JSON array of objects, one per item, using exactly the sub-keys listed for it.';
	$lines[] = 'Write in this language: ' . get_locale() . '.';

	if ( $instruction ) {
		$lines[] = 'Instruction from the site owner: ' . $instruction;
	}

	$lines[] = 'Fields to write:';

	foreach ( $simple_fields as $field_key ) {
		if ( ! isset( $widget_map[ $field_key ] ) || ! is_string( $widget_map[ $field_key ] ) ) {
			continue;
		}

		$role    = $widget_map[ $field_key ];
		$current = isset( $current_values[ $field_key ] ) ? (string) $current_values[ $field_key ] : '';
		$touched = ! empty( $already_generated[ $field_key ] );
		$prior   = isset( $previous_attempt[ $field_key ] ) ? (string) $previous_attempt[ $field_key ] : '';

		$line = '- "' . $field_key . '" (role: ' . $role . ')';

		if ( '' !== $current ) {
			$length = function_exists( 'mb_strlen' ) ? mb_strlen( $current ) : strlen( $current );
			$line  .= '. Keep it roughly the same length as the current text (~' . $length . ' characters).';
			$line  .= $touched
				? ' Use its current tone and topic as the main guide, current text: "' . $current . '"'
				: ' Current text (useful as topic/tone context): "' . $current . '"';
		}

		if ( '' !== $prior ) {
			$line .= ' You already suggested "' . $prior . '" for this a moment ago and it was rejected -- write a genuinely different variation this time (different wording and structure, not just a synonym swap).';
		}

		$lines[] = $line;
	}

	foreach ( $repeater_fields as $field_key ) {
		if ( ! isset( $widget_map[ $field_key ] ) || ! is_array( $widget_map[ $field_key ] ) ) {
			continue;
		}

		$sub_map = $widget_map[ $field_key ];
		$rows    = ( isset( $current_values[ $field_key ] ) && is_array( $current_values[ $field_key ] ) ) ? $current_values[ $field_key ] : [];
		$count   = max( 1, count( $rows ) );

		$sub_desc = [];
		foreach ( $sub_map as $sub_key => $sub_role ) {
			$sub_desc[] = '"' . $sub_key . '" (role: ' . $sub_role . ')';
		}

		$line = '- "' . $field_key . '" is a REPEATER. Return exactly ' . $count . ' item(s), each an object with these keys: ' . implode( ', ', $sub_desc ) . '.';

		$examples = bew_ai_describe_repeater_rows( $rows, $sub_map );
		if ( $examples ) {
			$line .= ' Current values (useful as topic/tone/length context): ' . $examples . '.';
		}

		$prior_rows = ( isset( $previous_attempt[ $field_key ] ) && is_array( $previous_attempt[ $field_key ] ) ) ? $previous_attempt[ $field_key ] : [];
		$prior_desc = bew_ai_describe_repeater_rows( $prior_rows, $sub_map );
		if ( $prior_desc ) {
			$line .= ' You already suggested (' . $prior_desc . ') a moment ago and it was rejected -- write genuinely different variations this time.';
		}

		$lines[] = $line;
	}

	return implode( "\n", $lines );
}
