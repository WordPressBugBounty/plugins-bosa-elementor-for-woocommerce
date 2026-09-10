<?php
/**
 * HTTP client for the BEW Template Kits Demo Manager REST API.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all outbound HTTP requests to the remote template service.
 */
class Bosa_Ewc_Remote_Client {

	/** Seconds before an outbound request times out. */
	const TIMEOUT = 30;

	/** @var string|null Last request URL (for diagnostics). */
	private $last_request_url = null;

	/** @var string|null Last transport or parse error message. */
	private $last_error = null;

	/** @var string Base URL without trailing slash. */
	private $base_url;

	/** @var string Optional API key sent as a custom header. */
	private $api_key;

	/**
	 * @param string $base_url Remote site URL.
	 * @param string $api_key  Optional API key for license-gated content.
	 */
	public function __construct( $base_url, $api_key = '' ) {
		$this->base_url = untrailingslashit( esc_url_raw( $base_url ) );
		$this->api_key  = sanitize_text_field( $api_key );
	}

	/**
	 * Fetch the combined template list (pages + sections).
	 *
	 * @param array $args Optional query args: per_page, page, category, s, is_free.
	 * @return array|WP_Error
	 */
	public function fetch_templates( $args = [] ) {
		return $this->get( '/templates', $args );
	}

	/**
	 * Fetch templates for a specific type.
	 *
	 * The hub only exposes a single combined /templates listing route (no
	 * per-type endpoints), so a specific type is fetched the same way as
	 * "all" and then filtered locally by the item's `type` field. Note this
	 * filters after the hub's own per_page limit is applied, so a small
	 * per_page value mixed with a type filter can return fewer items than
	 * actually exist remotely for that type.
	 *
	 * @param string $type One of: kit, page, section. Empty uses /templates.
	 * @param array  $args Optional query args.
	 * @return array|WP_Error
	 */
	public function fetch_by_type( $type, $args = [] ) {
		$type = sanitize_key( $type );

		if ( '' === $type ) {
			return $this->fetch_templates( $args );
		}

		$result = $this->fetch_templates( $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array_values( array_filter( $result, function ( $item ) use ( $type ) {
			return isset( $item['type'] ) && $type === $item['type'];
		} ) );
	}

	/**
	 * Fetch the kits list.
	 *
	 * @param array $args Optional query args.
	 * @return array|WP_Error
	 */
	public function fetch_kits( $args = [] ) {
		return $this->fetch_by_type( 'kit', $args );
	}

	/**
	 * Fetch available categories from the remote service.
	 *
	 * @return array|WP_Error
	 */
	public function fetch_categories() {
		return $this->get( '/categories' );
	}

	/**
	 * Fetch a kit's own page/section items directly (older kits whose
	 * catalog row has no included_pages/included_sections to resolve
	 * locally -- see class-api.php's get_remote_kit_items_response()).
	 *
	 * @param int $kit_id Remote kit post ID.
	 * @return array|WP_Error
	 */
	public function fetch_kit_items( $kit_id ) {
		return $this->get( '/kits/' . absint( $kit_id ) . '/items' );
	}

	/**
	 * Retrieve decoded Elementor JSON for a single template.
	 *
	 * @param int $template_id Remote post ID.
	 * @return array|WP_Error
	 */
	public function import_template( $template_id ) {
		$url = $this->endpoint( '/import' );
		$this->last_request_url = $url;
		$response = wp_remote_post( $url, array_merge(
			$this->http_args( 'POST' ),
			[ 'body' => [ 'template_id' => absint( $template_id ) ] ]
		) );

		return $this->parse_response( $response );
	}

	/**
	 * Verify connectivity and that the combined templates API returns data.
	 *
	 * @return array{count:int}|WP_Error
	 */
	public function test_connection() {
		$result = $this->get( '/templates', [ 'per_page' => 1 ] );

		if ( is_wp_error( $result ) ) {
			$root = wp_remote_get( $this->base_url . '/wp-json', $this->http_args( 'GET' ) );
			if ( is_wp_error( $root ) ) {
				return $root;
			}

			$code = (int) wp_remote_retrieve_response_code( $root );
			if ( 200 !== $code ) {
				return new WP_Error(
					'bew_tl_connection_failed',
					/* translators: %d: HTTP status code */
					sprintf( __( 'Remote service returned unexpected status code: %d', 'bosa-elementor-for-woocommerce' ), $code )
				);
			}

			$detail = $this->last_error ?: $result->get_error_message();

			return new WP_Error(
				'bew_tl_templates_unreachable',
				sprintf(
					/* translators: 1: templates endpoint URL, 2: error detail */
					__( 'WordPress REST is reachable, but the template list could not be read from %1$s: %2$s', 'bosa-elementor-for-woocommerce' ),
					$this->last_request_url ?: $this->endpoint( '/templates' ),
					$detail
				)
			);
		}

		return [ 'count' => is_array( $result ) ? count( $result ) : 0 ];
	}

	/**
	 * @return string
	 */
	public function get_last_request_url() {
		return $this->last_request_url ? (string) $this->last_request_url : '';
	}

	/**
	 * @return string
	 */
	public function get_last_error() {
		return $this->last_error ? (string) $this->last_error : '';
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * @param string $path Path starting with a forward slash.
	 * @return string
	 */
	private function endpoint( $path ) {
		return $this->base_url . '/wp-json/content-hub/v1' . $path;
	}

	/**
	 * @return array
	 */
	private function request_headers() {
		$headers = [ 'Accept' => 'application/json' ];
		if ( $this->api_key ) {
			$headers['X-Bosa-TK-Key'] = $this->api_key;
		}
		return $headers;
	}

	/**
	 * @param string $path Path starting with a forward slash.
	 * @param array  $args Query string parameters.
	 * @return array|WP_Error
	 */
	private function get( $path, $args = [] ) {
		$url = add_query_arg( array_filter( $args ), $this->endpoint( $path ) );
		return $this->request_get( $url );
	}

	/**
	 * @param string $url Full URL.
	 * @return array|WP_Error
	 */
	private function request_get( $url ) {
		$this->last_request_url = $url;
		$this->last_error       = null;

		$response = wp_remote_get( $url, $this->http_args( 'GET' ) );
		$parsed   = $this->parse_response( $response );

		if ( is_wp_error( $parsed ) ) {
			$this->last_error = $parsed->get_error_message();
		}

		return $parsed;
	}

	/**
	 * @param string $method HTTP method.
	 * @return array
	 */
	private function http_args( $method = 'GET' ) {
		$args = [
			'headers'     => $this->request_headers(),
			'timeout'     => self::TIMEOUT,
			'redirection' => 5,
			'user-agent'  => 'BosaElementorWooCommerce/' . ( defined( 'BEW_VERSION' ) ? BEW_VERSION : '1.0.0' ) . '; ' . home_url(),
		];

		return apply_filters( 'bosa_ewc_remote_http_args', $args, $method );
	}

	/**
	 * @param array|WP_Error $response
	 * @return array|WP_Error
	 */
	private function parse_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error(
				'bew_tl_remote_http_error',
				/* translators: %d: HTTP status code */
				sprintf( __( 'Remote service returned status %d.', 'bosa-elementor-for-woocommerce' ), $code )
			);
		}

		$raw  = $this->normalize_response_body( wp_remote_retrieve_body( $response ) );
		$body = json_decode( $raw, true );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $body ) ) {
			return new WP_Error(
				'bew_tl_invalid_response',
				__( 'Invalid JSON from the remote template service.', 'bosa-elementor-for-woocommerce' )
			);
		}

		if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
			return $body['data'];
		}

		if ( array_values( $body ) === $body ) {
			return $body;
		}

		return new WP_Error(
			'bew_tl_invalid_response',
			__( 'Invalid response from the remote template service.', 'bosa-elementor-for-woocommerce' )
		);
	}

	/**
	 * Strip BOM / whitespace so json_decode succeeds on hub responses.
	 *
	 * @param string $body Raw HTTP body.
	 * @return string
	 */
	private function normalize_response_body( $body ) {
		$body = is_string( $body ) ? $body : '';

		// Loop rather than a single check -- a misconfigured upstream file
		// can concatenate more than one BOM (with or without whitespace
		// between them), and a single pass only strips whatever is
		// immediately at the very start.
		while ( true ) {
			$body = ltrim( $body );

			if ( 0 === strpos( $body, "\xEF\xBB\xBF" ) ) {
				$body = substr( $body, 3 );
				continue;
			}

			if ( 0 === strpos( $body, "\xFE\xFF" ) || 0 === strpos( $body, "\xFF\xFE" ) ) {
				$body = substr( $body, 2 );
				continue;
			}

			break;
		}

		return $body;
	}
}
