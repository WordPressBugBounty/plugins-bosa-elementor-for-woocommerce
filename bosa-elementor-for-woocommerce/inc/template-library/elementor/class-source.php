<?php
/**
 * Elementor template library source.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ) {
	return;
}

/**
 * BEW Templates source for Elementor's template library.
 */
class Bosa_Ewc_Source extends \Elementor\TemplateLibrary\Source_Base {

	/**
	 * @return string
	 */
	public function get_id() {
		return 'bew';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'BEW Templates', 'bosa-elementor-for-woocommerce' );
	}

	/**
	 * @return void
	 */
	public function register_data() {}

	/**
	 * @return array<string,string>
	 */
	public function get_categories() {
		return bosa_ewc_get_all_categories();
	}

	/**
	 * @return array<string,string>
	 */
	public function get_tags() {
		return bosa_ewc_get_all_categories();
	}

	/**
	 * Returns an empty array — all browsing happens in the Bosa custom modal.
	 *
	 * @param array $args Unused.
	 * @return array
	 */
	public function get_items( $args = [] ) {
		return [];
	}

	/**
	 * @param int $template_id Post ID.
	 * @return array|WP_Error
	 */
	public function get_item( $template_id ) {
		$post = get_post( absint( $template_id ) );

		if (
			! $post
			|| ! array_key_exists( $post->post_type, bosa_ewc_get_type_map() )
			|| ! in_array( $post->post_status, bosa_ewc_get_library_post_statuses(), true )
		) {
			return new WP_Error(
				'bew_tl_not_found',
				__( 'Template not found.', 'bosa-elementor-for-woocommerce' ),
				[ 'status' => 404 ]
			);
		}

		return $this->format_item( $post );
	}

	/**
	 * Return decoded Elementor JSON for editor insertion.
	 *
	 * @param array $args Must include 'template_id'.
	 * @return array|WP_Error
	 */
	public function get_data( array $args ) {
		$template_id = absint( $args['template_id'] ?? 0 );
		$type_map    = bosa_ewc_get_type_map();

		if ( ! $template_id ) {
			return new WP_Error( 'bew_tl_invalid_id', __( 'Invalid template ID.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 400 ] );
		}

		$post = get_post( $template_id );

		if (
			! $post
			|| ! in_array( $post->post_status, bosa_ewc_get_library_post_statuses(), true )
			|| ! array_key_exists( $post->post_type, $type_map )
		) {
			return $this->get_remote_data( $template_id );
		}

		$raw = get_post_meta( $post->ID, '_elementor_data', true );

		if ( empty( $raw ) ) {
			return new WP_Error( 'bew_tl_no_data', __( 'This template has no Elementor data.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 404 ] );
		}

		$content = json_decode( $raw, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'bew_tl_json_error', __( 'Template data is corrupted.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 500 ] );
		}

		$content = apply_filters( 'bosa_ewc_source_content', $content, $template_id, $post );

		return [
			'template_id'   => $template_id,
			'content'       => $content,
			'page_settings' => [],
		];
	}

	/**
	 * @param array $template_data Unused.
	 * @return WP_Error
	 */
	public function save_item( $template_data ) {
		return new WP_Error( 'bew_tl_read_only', __( 'BEW templates are read-only.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 403 ] );
	}

	/**
	 * @param array $new_data Unused.
	 * @return WP_Error
	 */
	public function update_item( $new_data ) {
		return new WP_Error( 'bew_tl_read_only', __( 'BEW templates are read-only.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 403 ] );
	}

	/**
	 * @param array $template_data Unused.
	 * @return WP_Error
	 */
	public function save_template( array $template_data ) {
		return new WP_Error( 'bew_tl_read_only', __( 'BEW templates are read-only.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 403 ] );
	}

	/**
	 * @param array $new_data Unused.
	 * @return WP_Error
	 */
	public function update_template( array $new_data ) {
		return new WP_Error( 'bew_tl_read_only', __( 'BEW templates are read-only.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 403 ] );
	}

	/**
	 * @param int $template_id Unused.
	 * @return WP_Error
	 */
	public function delete_template( $template_id ) {
		return new WP_Error( 'bew_tl_read_only', __( 'BEW templates are read-only.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 403 ] );
	}

	/**
	 * @param int $template_id Unused.
	 * @return WP_Error
	 */
	public function export_template( $template_id ) {
		return new WP_Error( 'bew_tl_not_supported', __( 'Export is not supported for BEW templates.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 400 ] );
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * @param int $template_id Remote post ID.
	 * @return array|WP_Error
	 */
	private function get_remote_data( $template_id ) {
		$client   = new Bosa_Ewc_Remote_Client( BEW_TL_REMOTE_URL, BEW_TL_API_KEY );
		$response = $client->import_template( $template_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$content = isset( $response['content'] ) ? $response['content'] : null;

		if ( ! is_array( $content ) ) {
			return new WP_Error( 'bew_tl_no_data', __( 'This template has no Elementor data.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 404 ] );
		}

		return [
			'template_id'   => $template_id,
			'content'       => $content,
			'page_settings' => [],
		];
	}

	/**
	 * @param WP_Post $post CPT post object.
	 * @return array
	 */
	private function format_item( WP_Post $post ) {
		$type_map  = bosa_ewc_get_type_map();
		$type      = $type_map[ $post->post_type ] ?? 'page';
		$thumbnail = bosa_ewc_get_thumbnail_url( $post->ID, 'large' );
		$author    = get_userdata( $post->post_author );
		$tags      = bosa_ewc_get_template_categories( $post->ID );
		$is_free   = bosa_ewc_is_free_template( $post->ID );

		return [
			'template_id'     => (string) $post->ID,
			'source'          => $this->get_id(),
			'type'            => $type,
			'subtype'         => $type,
			'title'           => get_the_title( $post ),
			'thumbnail'       => $thumbnail,
			'date'            => (int) get_post_timestamp( $post ),
			'author'          => $author ? $author->display_name : '',
			'tags'            => $tags,
			'isPro'           => ! $is_free,
			'is_locked'       => false,
			'accessLevel'     => 0,
			'url'             => esc_url( home_url() ),
			'export_link'     => '',
			'hasPageSettings' => false,
		];
	}
}
