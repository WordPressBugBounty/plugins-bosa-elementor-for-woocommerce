<?php
/**
 * AI provider type definitions for the "AI Integration" settings tab.
 *
 * Kept as a plain, filterable data list (same convention as the widget/module
 * arrays in page-settings.php) so a 6th provider can be added later without
 * touching the tab markup, the AJAX handler, or the JS.
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the list of selectable AI provider types, keyed by a stable slug.
 *
 * `help_html` is shown under the API Key field for whichever provider is
 * selected; it intentionally allows a plain `<a>` tag (rendered via
 * wp_kses_post()), so keep it to a single short sentence with one link.
 *
 * @return array<string, array{label: string, help_html: string}>
 */
function bew_get_ai_provider_types() {
	$types = [
		// Order is deliberate: providers with a genuine no-card free tier first
		// (Google, OpenRouter, Mistral), then paid-only providers, with Groq
		// last since its free tier -- while real -- isn't obvious from its own
		// pricing page and gets easily missed at a glance.
		'google' => [
			'label'     => __( 'Google', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to Google AI Studio */
				__( 'Free to use, no card required (Gemini Flash models, rate-limited). You can get your API Keys in your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Google AI Studio ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'openrouter' => [
			'label'     => __( 'OpenRouter', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the OpenRouter API keys page */
				__( 'Includes free models with no card required. For paid models, you will use the same OpenRouter balance you already top up -- BEW AI never adds an extra charge on top. Get your API key from your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://openrouter.ai/settings/keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'OpenRouter Account ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'mistral' => [
			'label'     => __( 'Mistral', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the Mistral console API keys page */
				__( 'Includes a rate-limited free tier for evaluation. Beyond that, you will use the same balance you already have with Mistral -- BEW AI never adds an extra charge on top. Get your API key from your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://console.mistral.ai/api-keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Mistral Console ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'openai' => [
			'label'     => __( 'OpenAI', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the OpenAI API keys page */
				__( 'No free API tier -- a ChatGPT Plus subscription does not cover this. But you will use the same API balance you already pay for -- BEW AI never adds an extra charge on top. You can get your API Keys in your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'OpenAI Account ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'anthropic' => [
			'label'     => __( 'Anthropic', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the Anthropic API keys page */
				__( 'No free API tier -- a Claude Pro subscription does not cover this. But you will use the same API balance you already pay for -- BEW AI never adds an extra charge on top. You can get your API Keys in your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Anthropic Account ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'replicate' => [
			'label'     => __( 'Replicate', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the Replicate API tokens page */
				__( 'No free tier -- pay-per-run. But you will use the same balance you already pay for -- BEW AI never adds an extra charge on top. Get your API token from your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://replicate.com/account/api-tokens" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Replicate Account ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'xai' => [
			'label'     => __( 'xAI (Grok)', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the xAI console */
				__( 'xAI (Grok) requires a paid account, but you will use the same balance you already pay for -- BEW AI never adds an extra charge on top. You can get your API Keys in your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://console.x.ai/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'xAI Console ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'perplexity' => [
			'label'     => __( 'Perplexity', 'bosa-elementor-for-woocommerce' ),
			// referral_code intentionally left blank -- filled in later once a referrer account exists.
			'help_html' => sprintf(
				/* translators: %s: link to the Perplexity account/signup page */
				__( 'Perplexity.ai is a paid service ($10 free credit on signup) -- beyond that, you will use the same balance you already pay for and BEW AI never adds an extra charge on top. You can get your API Keys in your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://www.perplexity.ai/pro?referral_code=" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Perplexity Account ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
		'groq' => [
			'label'     => __( 'Groq', 'bosa-elementor-for-woocommerce' ),
			'help_html' => sprintf(
				/* translators: %s: link to the Groq console API keys page */
				__( 'Free to use, no card required, across their full model catalog (rate-limited). Get your API key from your %s.', 'bosa-elementor-for-woocommerce' ),
				'<a href="https://console.groq.com/keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GroqCloud Console ↗', 'bosa-elementor-for-woocommerce' ) . '</a>'
			),
		],
	];

	/**
	 * Filter the list of selectable AI provider types.
	 *
	 * @param array<string, array{label: string, help_html: string}> $types
	 */
	return apply_filters( 'bew_ai_provider_types', $types );
}

/**
 * Pull the provider's own error detail out of its response body, when
 * present, so the user (and whoever's debugging) sees the real reason
 * instead of a bare status code. Every provider used here (OpenAI, xAI,
 * Perplexity, and -- as far as could be confirmed -- Anthropic and Google)
 * nests it the same way: {"error": {"message": "..."}}. Returned in full --
 * the UI collapses long text behind a "Show more" toggle rather than this
 * function cutting it off, so nothing is ever lost.
 *
 * @param array|null $body Decoded JSON response body, if any.
 * @return string Empty string if no detail could be found.
 */
function bew_ai_provider_error_detail( $body ) {
	if ( ! is_array( $body ) ) {
		return '';
	}

	if ( ! empty( $body['error']['message'] ) && is_string( $body['error']['message'] ) ) {
		return trim( $body['error']['message'] );
	}

	if ( ! empty( $body['message'] ) && is_string( $body['message'] ) ) {
		return trim( $body['message'] );
	}

	return '';
}

/**
 * A specific, accurate message for a non-200 provider response, shared by
 * every fetch/generate call across all providers. 429 in particular is NOT
 * a key problem -- it means the key is valid but rate-limited/out of quota,
 * so telling the user to "check your API key" for that code is misleading.
 *
 * @param int        $code HTTP status code.
 * @param array|null $body Decoded JSON response body, if available -- its
 *                         own error detail (when present) is appended so the
 *                         real cause is visible, not just the status code.
 * @return string
 */
function bew_ai_http_error_message( $code, $body = null ) {
	$code   = (int) $code;
	$detail = bew_ai_provider_error_detail( $body );

	if ( 429 === $code ) {
		$base = __( 'Rate limit or quota reached for this API key. Wait a moment and try again, or check your usage on the provider\'s dashboard.', 'bosa-elementor-for-woocommerce' );
	} elseif ( in_array( $code, [ 401, 403 ], true ) ) {
		$base = __( 'This API key was rejected. Check that it\'s correct and still active on the provider\'s dashboard.', 'bosa-elementor-for-woocommerce' );
	} elseif ( $code >= 500 ) {
		$base = __( 'The provider is having issues right now. Please try again shortly.', 'bosa-elementor-for-woocommerce' );
	} else {
		$base = sprintf(
			/* translators: %d: HTTP status code */
			__( 'Provider returned HTTP %d.', 'bosa-elementor-for-woocommerce' ),
			$code
		);
	}

	return $detail ? $base . ' ' . $detail : $base;
}

/**
 * Whether the BEW AI feature is currently in beta. Single source of truth so
 * every surface (Settings tab, in-editor button, changelog copy) shows the
 * same state, and graduating out of beta later is a one-line change.
 *
 * @return bool
 */
function bew_ai_is_beta() {
	return (bool) apply_filters( 'bew_ai_is_beta', true );
}

/**
 * @return bool Whether at least one AI provider is connected.
 */
function bew_has_connected_ai_provider() {
	return ! empty( BEW_Plugin_Settings::get( 'ai_providers', [] ) );
}

/**
 * @param array $providers
 * @return bool Whether any entry in the list is already marked default.
 */
function bew_ai_providers_has_default( $providers ) {
	foreach ( (array) $providers as $p ) {
		if ( ! empty( $p['is_default'] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * The provider used for generation when a request doesn't specify one:
 * whichever entry is marked default, falling back to the first connected
 * entry if none is marked (e.g. the marked one was deleted).
 *
 * @return array|null
 */
function bew_get_default_ai_provider() {
	$providers = BEW_Plugin_Settings::get( 'ai_providers', [] );

	foreach ( $providers as $p ) {
		if ( ! empty( $p['is_default'] ) ) {
			return $p;
		}
	}

	return $providers ? $providers[0] : null;
}
