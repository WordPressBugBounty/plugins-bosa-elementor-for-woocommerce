<?php
/**
 * Live AI model detection for connected providers.
 *
 * Each provider's own "list models" API is used so the dropdown always
 * reflects what the key can actually access, instead of a hardcoded list
 * that would drift out of date. Providers without a public list-models
 * endpoint (e.g. Perplexity) are left out of the fetcher map on purpose --
 * the caller falls back to manual model-ID entry for those.
 *
 * A provider's raw list-models response usually mixes every capability it
 * offers -- chat, image, audio, embeddings, moderation -- in one response.
 * Fetchers that can tell these apart accept an optional $capability
 * ('text' by default, matching every existing caller) and filter to that
 * capability's models only; this is how the *same* connected key can power
 * a Model choice in Text Generation, Image Generation, and -- the same way,
 * no new plumbing -- whatever a future video/voice feature filters for.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch selectable models from OpenAI, filtered to one capability.
 *
 * @param string $api_key    Raw (unencrypted) API key.
 * @param string $capability 'text' (default) or 'image'.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_openai_models( $api_key, $capability = 'text' ) {
	$response = wp_remote_get( 'https://api.openai.com/v1/models', [
		'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	// The endpoint lists every family it hosts -- chat, image, embeddings,
	// whisper, moderation, realtime -- in one flat response; each capability
	// keeps only the id pattern that actually matches it.
	$id_matches_capability = 'image' === $capability
		? function ( $id ) { return (bool) preg_match( '/^(dall-e|gpt-image)/', $id ); }
		: function ( $id ) { return preg_match( '/^(gpt-|o1|o3|o4|chatgpt-)/', $id ) && ! preg_match( '/(audio|image|realtime|transcribe|tts|search)/', $id ); };

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( empty( $model['id'] ) ) {
			continue;
		}

		$id = $model['id'];

		if ( ! $id_matches_capability( $id ) ) {
			continue;
		}

		$models[] = [ 'id' => $id, 'label' => $id ];
	}

	usort( $models, function ( $a, $b ) {
		return strcmp( $a['id'], $b['id'] );
	} );

	return $models;
}

/**
 * Fetch selectable models from Anthropic.
 *
 * @param string $api_key Raw (unencrypted) API key.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_anthropic_models( $api_key ) {
	$response = wp_remote_get( 'https://api.anthropic.com/v1/models', [
		'headers' => [
			'x-api-key'         => $api_key,
			'anthropic-version' => '2023-06-01',
		],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( ! empty( $model['id'] ) ) {
			$models[] = [
				'id'    => $model['id'],
				'label' => ! empty( $model['display_name'] ) ? $model['display_name'] : $model['id'],
			];
		}
	}

	return $models;
}

/**
 * Fetch selectable models from Google (Gemini), filtered to one capability.
 *
 * Text and image-output Gemini models both answer through the exact same
 * generateContent method -- supportedGenerationMethods can't tell them apart
 * -- so unlike OpenAI's id-prefix split, capability here is read off Google's
 * own naming patterns for image-output models: a "-image" suffix
 * (gemini-2.5-flash-image, gemini-3.1-flash-image) or the "nano-banana"
 * codename Google itself uses for that same model family.
 *
 * @param string $api_key    Raw (unencrypted) API key.
 * @param string $capability 'text' (default) or 'image'.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_google_models( $api_key, $capability = 'text' ) {
	$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode( $api_key );

	$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['models'] ) || ! is_array( $body['models'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['models'] as $model ) {
		if ( empty( $model['name'] ) ) {
			continue;
		}

		// Only keep models usable for content generation, when the field is present to check.
		if ( isset( $model['supportedGenerationMethods'] ) && is_array( $model['supportedGenerationMethods'] )
			&& ! in_array( 'generateContent', $model['supportedGenerationMethods'], true ) ) {
			continue;
		}

		$is_image_model = (bool) preg_match( '/(-image|nano-banana)/', $model['name'] );

		if ( 'image' === $capability ) {
			if ( ! $is_image_model ) {
				continue;
			}
		} else {
			// generateContent also answers for several specialized, non-chat
			// product lines (text-to-speech, Lyria music generation, robotics
			// control, computer-use/deep-research agents) -- keep those out
			// of the "text" list the same way image models are kept out.
			$is_specialized = (bool) preg_match( '/(-tts|lyria|robotics|computer-use|deep-research|antigravity)/', $model['name'] );
			if ( $is_image_model || $is_specialized ) {
				continue;
			}
		}

		$models[] = [
			'id'    => $model['name'], // Full "models/gemini-..." path -- required as-is by the generateContent endpoint.
			'label' => ! empty( $model['displayName'] ) ? $model['displayName'] : $model['name'],
		];
	}

	return $models;
}

/**
 * Fetch selectable models from xAI (Grok). API is OpenAI-compatible in shape.
 *
 * @param string $api_key Raw (unencrypted) API key.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_xai_models( $api_key ) {
	$response = wp_remote_get( 'https://api.x.ai/v1/models', [
		'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( ! empty( $model['id'] ) ) {
			$models[] = [ 'id' => $model['id'], 'label' => $model['id'] ];
		}
	}

	return $models;
}

/**
 * Fetch selectable models from OpenRouter. API is OpenAI-compatible in shape,
 * aggregating many providers' models behind one key. Each model's own pricing
 * is included in the response, so free (":free"-suffixed, $0/$0 priced)
 * models can be flagged and sorted first -- a key with no credits purchased
 * can only use those.
 *
 * @param string $api_key Raw (unencrypted) API key.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_openrouter_models( $api_key ) {
	$response = wp_remote_get( 'https://openrouter.ai/api/v1/models', [
		'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( empty( $model['id'] ) ) {
			continue;
		}

		$is_free = isset( $model['pricing']['prompt'], $model['pricing']['completion'] )
			&& '0' === (string) $model['pricing']['prompt']
			&& '0' === (string) $model['pricing']['completion'];

		$label = ! empty( $model['name'] ) ? $model['name'] : $model['id'];
		if ( $is_free ) {
			$label .= ' ' . __( '(Free)', 'bosa-elementor-for-woocommerce' );
		}

		$models[] = [ 'id' => $model['id'], 'label' => $label, 'free' => $is_free ];
	}

	// Free models first (a key with no credits purchased can only use those), alphabetical within each group.
	usort( $models, function ( $a, $b ) {
		if ( $a['free'] !== $b['free'] ) {
			return $a['free'] ? -1 : 1;
		}
		return strcasecmp( $a['label'], $b['label'] );
	} );

	return array_map( function ( $m ) {
		return [ 'id' => $m['id'], 'label' => $m['label'] ];
	}, $models );
}

/**
 * Fetch selectable models from Mistral. API is OpenAI-compatible in shape.
 *
 * @param string $api_key Raw (unencrypted) API key.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_mistral_models( $api_key ) {
	$response = wp_remote_get( 'https://api.mistral.ai/v1/models', [
		'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( ! empty( $model['id'] ) ) {
			$models[] = [ 'id' => $model['id'], 'label' => $model['id'] ];
		}
	}

	usort( $models, function ( $a, $b ) {
		return strcmp( $a['id'], $b['id'] );
	} );

	return $models;
}

/**
 * Fetch selectable models from Groq. API is OpenAI-compatible in shape,
 * served under Groq's own "/openai/v1" namespace.
 *
 * @param string $api_key Raw (unencrypted) API key.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_groq_models( $api_key ) {
	$response = wp_remote_get( 'https://api.groq.com/openai/v1/models', [
		'headers' => [ 'Authorization' => 'Bearer ' . $api_key ],
		'timeout' => 15,
	] );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 200 !== $code ) {
		return new WP_Error( 'bew_ai_http_' . $code, bew_ai_http_error_message( $code, is_array( $body ) ? $body : null ) );
	}
	if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'bew_ai_bad_response', __( 'Unexpected response from provider.', 'bosa-elementor-for-woocommerce' ) );
	}

	$models = [];
	foreach ( $body['data'] as $model ) {
		if ( ! empty( $model['id'] ) ) {
			$models[] = [ 'id' => $model['id'], 'label' => $model['id'] ];
		}
	}

	usort( $models, function ( $a, $b ) {
		return strcmp( $a['id'], $b['id'] );
	} );

	return $models;
}

/**
 * Dispatch to the right provider's live model fetcher.
 *
 * Providers with no entry (e.g. Perplexity, which has no public list-models
 * endpoint) return a `bew_ai_no_live_fetch` WP_Error so the caller can fall
 * back to manual model-ID entry instead of treating it as a hard failure.
 *
 * @param string $type       Provider type key (see bew_get_ai_provider_types()).
 * @param string $api_key    Raw (unencrypted) API key.
 * @param string $capability 'text' (default), 'image', or whatever a future
 *                           fetcher supports -- ignored by fetchers that
 *                           don't declare the parameter.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_fetch_ai_provider_models( $type, $api_key, $capability = 'text' ) {
	/**
	 * Filter the map of provider type => callable that fetches its live model list.
	 *
	 * @param array<string, callable> $fetchers
	 */
	$fetchers = apply_filters( 'bew_ai_provider_model_fetchers', [
		'openai'     => 'bew_fetch_openai_models',
		'anthropic'  => 'bew_fetch_anthropic_models',
		'google'     => 'bew_fetch_google_models',
		'xai'        => 'bew_fetch_xai_models',
		'openrouter' => 'bew_fetch_openrouter_models',
		'mistral'    => 'bew_fetch_mistral_models',
		'groq'       => 'bew_fetch_groq_models',
	] );

	if ( empty( $fetchers[ $type ] ) || ! is_callable( $fetchers[ $type ] ) ) {
		return new WP_Error(
			'bew_ai_no_live_fetch',
			__( 'Automatic model detection is not available for this provider. Enter the model ID manually.', 'bosa-elementor-for-woocommerce' )
		);
	}

	return call_user_func( $fetchers[ $type ], $api_key, $capability );
}

/**
 * Cached wrapper around bew_fetch_ai_provider_models() -- avoids re-hitting
 * the provider's API on every page load. Failures are never cached, so the
 * next attempt (e.g. after fixing the key) always retries live.
 *
 * @param string $type          Provider type key.
 * @param string $api_key       Raw (unencrypted) API key.
 * @param bool   $force_refresh Bypass the cache and fetch live.
 * @param string $capability    'text' (default) or 'image' -- cached separately per capability.
 * @return array<int, array{id: string, label: string}>|WP_Error
 */
function bew_get_cached_ai_provider_models( $type, $api_key, $force_refresh = false, $capability = 'text' ) {
	$cache_key = 'bew_aim_' . $type . '_' . $capability . '_' . md5( $api_key );

	if ( ! $force_refresh ) {
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
	}

	$models = bew_fetch_ai_provider_models( $type, $api_key, $capability );

	if ( is_wp_error( $models ) ) {
		return $models;
	}

	set_transient( $cache_key, $models, DAY_IN_SECONDS );

	return $models;
}

/**
 * Cache-only read -- never calls the provider's API, unlike
 * bew_get_cached_ai_provider_models() which falls through to a live fetch.
 * Every in-editor card's Model selector should call this: the only things
 * that ever hit a provider's API are Settings' own Add Provider and Refresh
 * Models actions, never opening an editor card.
 *
 * @param string $type
 * @param string $api_key    Raw (unencrypted) API key.
 * @param string $capability 'text' (default) or 'image'.
 * @return array<int, array{id: string, label: string}> Empty when nothing is cached yet.
 */
function bew_get_cached_ai_provider_models_readonly( $type, $api_key, $capability = 'text' ) {
	$cache_key = 'bew_aim_' . $type . '_' . $capability . '_' . md5( $api_key );
	$cached    = get_transient( $cache_key );

	return is_array( $cached ) ? $cached : [];
}

/**
 * WP-Cron callback (registered in the main plugin file) -- force-refreshes
 * every connected provider's model cache (text always, image when the type
 * supports it), the same fetch+cache "Refresh Models" triggers manually,
 * just running weekly instead of waiting for a click.
 */
function bew_ai_weekly_model_refresh() {
	if ( ! class_exists( 'BEW_Crypto' ) ) {
		return;
	}

	$providers            = BEW_Plugin_Settings::get( 'ai_providers', [] );
	$image_capable_types  = function_exists( 'bew_ai_image_capable_provider_types' ) ? bew_ai_image_capable_provider_types() : [];

	foreach ( $providers as $p ) {
		if ( empty( $p['type'] ) || empty( $p['api_key_enc'] ) ) {
			continue;
		}

		$api_key = BEW_Crypto::decrypt( $p['api_key_enc'] );
		if ( ! $api_key ) {
			continue;
		}

		bew_get_cached_ai_provider_models( $p['type'], $api_key, true, 'text' );

		if ( in_array( $p['type'], $image_capable_types, true ) ) {
			bew_get_cached_ai_provider_models( $p['type'], $api_key, true, 'image' );
		}
	}
}
add_action( 'bew_ai_weekly_model_refresh', 'bew_ai_weekly_model_refresh' );
