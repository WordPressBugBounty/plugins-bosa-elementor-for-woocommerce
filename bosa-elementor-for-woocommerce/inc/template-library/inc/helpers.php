<?php
/**
 * Shared helper functions for the Template Library module.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------
// CPT / type helpers
// -------------------------------------------------------------------------

/**
 * Post statuses included in the Elementor library and REST listings.
 *
 * @return string[]
 */
function bosa_ewc_get_library_post_statuses() {
	return [ 'publish' ];
}

/**
 * Return the CPT-slug → type-label map.
 *
 * @return array<string,string>
 */
function bosa_ewc_get_type_map() {
	return [
		'bosa_tk_page'    => 'page',
		'bosa_tk_section' => 'section',
		'bosa_tk_kit'     => 'kit',
		'bosa_tk_header'  => 'header',
		'bosa_tk_footer'  => 'footer',
	];
}

/**
 * @return string[]
 */
function bosa_ewc_get_all_cpt_slugs() {
	return array_keys( bosa_ewc_get_type_map() );
}

/**
 * Build a label array for register_post_type().
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function bosa_ewc_cpt_labels( $singular, $plural ) {
	return [
		'name'               => $plural,
		'singular_name'      => $singular,
		/* translators: %s: plural label */
		'all_items'          => sprintf( __( 'All %s', 'bosa-elementor-for-woocommerce' ), $plural ),
		/* translators: %s: singular label */
		'add_new_item'       => sprintf( __( 'Add New %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: singular label */
		'edit_item'          => sprintf( __( 'Edit %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: singular label */
		'new_item'           => sprintf( __( 'New %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: singular label */
		'view_item'          => sprintf( __( 'View %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: plural label */
		'search_items'       => sprintf( __( 'Search %s', 'bosa-elementor-for-woocommerce' ), $plural ),
		/* translators: %s: plural label */
		'not_found'          => sprintf( __( 'No %s found.', 'bosa-elementor-for-woocommerce' ), $plural ),
		/* translators: %s: plural label */
		'not_found_in_trash' => sprintf( __( 'No %s found in trash.', 'bosa-elementor-for-woocommerce' ), $plural ),
	];
}

/**
 * Build a label array for register_taxonomy().
 *
 * @param string $singular Singular label.
 * @param string $plural   Plural label.
 * @return array
 */
function bosa_ewc_tax_labels( $singular, $plural ) {
	return [
		'name'          => $plural,
		'singular_name' => $singular,
		/* translators: %s: plural label */
		'all_items'     => sprintf( __( 'All %s', 'bosa-elementor-for-woocommerce' ), $plural ),
		/* translators: %s: singular label */
		'edit_item'     => sprintf( __( 'Edit %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: singular label */
		'update_item'   => sprintf( __( 'Update %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: singular label */
		'add_new_item'  => sprintf( __( 'Add New %s', 'bosa-elementor-for-woocommerce' ), $singular ),
		/* translators: %s: plural label */
		'search_items'  => sprintf( __( 'Search %s', 'bosa-elementor-for-woocommerce' ), $plural ),
		/* translators: %s: plural label */
		'not_found'     => sprintf( __( 'No %s found.', 'bosa-elementor-for-woocommerce' ), $plural ),
	];
}

// -------------------------------------------------------------------------
// Category helpers
// -------------------------------------------------------------------------

/**
 * Normalize a tab or API type slug to kit|page|section.
 *
 * @param string $type Tab slug (kits, pages, sections) or type (kit, page, section).
 * @return string kit|page|section, or empty string when unknown.
 */
function bosa_ewc_resolve_category_type( $type ) {
	$map = [
		'kit'      => 'kit',
		'kits'     => 'kit',
		'page'     => 'page',
		'pages'    => 'page',
		'section'  => 'section',
		'sections' => 'section',
	];

	$type = sanitize_key( (string) $type );

	return $map[ $type ] ?? '';
}

/**
 * Return category labels for a single template type.
 *
 * @param string $type Tab or type slug.
 * @return string[]
 */
function bosa_ewc_get_categories_for_type( $type ) {
	$resolved = bosa_ewc_resolve_category_type( $type );

	if ( ! $resolved ) {
		return [];
	}

	return array_values( bosa_ewc_get_all_categories() );
}

/**
 * Return all available template categories as slug => label pairs.
 *
 * @return array<string,string>
 */
function bosa_ewc_get_all_categories() {
	$terms = get_terms( [
		'taxonomy'   => 'bosa_template_kit_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	] );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	$categories = [];
	foreach ( $terms as $term ) {
		$categories[ $term->slug ] = $term->name;
	}
	return $categories;
}

/**
 * Return category slugs for a single template post.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function bosa_ewc_get_template_categories( $post_id ) {
	$post_id = absint( $post_id );
	$terms   = get_the_terms( $post_id, 'bosa_template_kit_cat' );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	return wp_list_pluck( $terms, 'slug' );
}

// -------------------------------------------------------------------------
// Template meta
// -------------------------------------------------------------------------

/**
 * Return true if a template is marked as free (default when meta is absent).
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function bosa_ewc_is_free_template( $post_id ) {
	$meta = get_post_meta( absint( $post_id ), '_bosa_template_kits_is_free', true );
	return '' === $meta ? true : ( '1' === $meta || true === (bool) $meta );
}

/**
 * Whether Bosa Elementor for WooCommerce Pro is active.
 *
 * @return bool
 */
function bosa_ewc_has_pro_plugin() {
	return class_exists( 'BEW_Pro' );
}

/**
 * Whether a remote catalog template is marked free.
 *
 * @param int $template_id Remote template ID.
 * @return bool
 */
function bosa_ewc_remote_template_is_free( $template_id ) {
	$template_id = absint( $template_id );
	if ( ! $template_id ) {
		return true;
	}

	static $free_map = null;

	if ( null === $free_map ) {
		$free_map = [];
		$client   = new Bosa_Ewc_Remote_Client( BEW_TL_REMOTE_URL, BEW_TL_API_KEY );

		foreach ( [ 'page', 'section' ] as $type ) {
			$page = 1;

			do {
				$batch = $client->fetch_by_type( $type, [ 'per_page' => 100, 'page' => $page ] );

				if ( is_wp_error( $batch ) || ! is_array( $batch ) || empty( $batch ) ) {
					break;
				}

				foreach ( $batch as $item ) {
					$id = absint( isset( $item['id'] ) ? $item['id'] : 0 );
					if ( $id ) {
						$free_map[ $id ] = ! isset( $item['is_free'] ) || (bool) $item['is_free'];
					}
				}

				$page++;
			} while ( count( $batch ) >= 100 && $page <= 20 );
		}
	}

	return isset( $free_map[ $template_id ] ) ? (bool) $free_map[ $template_id ] : true;
}

/**
 * Whether a template requires the Pro plugin before import.
 *
 * @param int $template_id Local or remote template ID.
 * @return bool
 */
function bosa_ewc_template_requires_pro( $template_id ) {
	$template_id = absint( $template_id );
	if ( ! $template_id ) {
		return false;
	}

	$type_map = bosa_ewc_get_type_map();
	$post     = get_post( $template_id );

	if ( $post && isset( $type_map[ $post->post_type ] ) ) {
		return ! bosa_ewc_is_free_template( $post->ID );
	}

	return ! bosa_ewc_remote_template_is_free( $template_id );
}

/**
 * Whether the current site may import a given template.
 *
 * @param int $template_id Local or remote template ID.
 * @return bool
 */
function bosa_ewc_can_import_template( $template_id ) {
	if ( bosa_ewc_has_pro_plugin() ) {
		return true;
	}

	return ! bosa_ewc_template_requires_pro( $template_id );
}

// -------------------------------------------------------------------------
// Thumbnails and Elementor data
// -------------------------------------------------------------------------

/**
 * Return the template featured image URL with a placeholder fallback.
 *
 * Use 'bosa_tk_card_thumb' for tall screenshots to avoid blurry narrow images
 * caused by WordPress's proportional 'large' size.
 *
 * @param int    $post_id Post ID.
 * @param string $size    Image size. Default 'large'.
 * @return string
 */
function bosa_ewc_get_thumbnail_url( $post_id, $size = 'large' ) {
	$post_id      = absint( $post_id );
	$thumbnail_id = get_post_thumbnail_id( $post_id );

	if ( $thumbnail_id && 'large' === $size ) {
		$card_url = bosa_ewc_get_or_generate_sized_image_url( $thumbnail_id, 'bosa_tk_card_thumb', 253, 0 );
		if ( $card_url ) {
			return esc_url( $card_url );
		}
	}

	$url = get_the_post_thumbnail_url( $post_id, $size );
	return $url ? esc_url( $url ) : esc_url( BEW_TL_URL . 'assets/images/placeholder.png' );
}

/**
 * Return the URL for a registered image size, generating it on demand if
 * missing (so older attachments don't require thumbnail regeneration).
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size_name     Registered size name, used as the metadata key.
 * @param int    $width         Target width in pixels.
 * @param int    $height        Target height in pixels, or 0 for proportional/uncapped.
 * @param bool   $crop          Hard-crop to exact dimensions. Default false.
 * @return string|false Image URL, or false if it couldn't be found or generated.
 */
function bosa_ewc_get_or_generate_sized_image_url( $attachment_id, $size_name, $width, $height = 0, $crop = false ) {
	$attachment_id  = absint( $attachment_id );
	$attachment_url = wp_get_attachment_url( $attachment_id );

	if ( ! $attachment_url ) {
		return false;
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );

	if ( ! empty( $metadata['sizes'][ $size_name ]['file'] ) ) {
		return trailingslashit( dirname( $attachment_url ) ) . $metadata['sizes'][ $size_name ]['file'];
	}

	if ( ! function_exists( 'image_make_intermediate_size' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$original_file = get_attached_file( $attachment_id );
	if ( ! $original_file || ! file_exists( $original_file ) ) {
		return false;
	}

	$generated = image_make_intermediate_size( $original_file, $width, $height, $crop );
	if ( ! $generated ) {
		return false;
	}

	if ( ! is_array( $metadata ) ) {
		$metadata = [];
	}
	$metadata['sizes'][ $size_name ] = $generated;
	wp_update_attachment_metadata( $attachment_id, $metadata );

	return trailingslashit( dirname( $attachment_url ) ) . $generated['file'];
}

/**
 * Ensure remote thumbnails meet the minimum width by generating and caching
 * a local resized copy when the source image is too narrow.
 *
 * @param string $remote_url Thumbnail URL as returned by the remote API.
 * @param int    $min_width  Minimum acceptable width in pixels. Default 253.
 * @return string
 */
function bosa_ewc_get_remote_thumbnail_url( $remote_url, $min_width = 253 ) {
	$remote_url = esc_url_raw( (string) $remote_url );

	if ( '' === $remote_url || ! preg_match( '/-(\d+)x(\d+)(\.[a-z0-9]+)$/i', $remote_url, $matches ) ) {
		return $remote_url;
	}

	if ( (int) $matches[1] >= $min_width ) {
		return $remote_url;
	}

	$cache_key = 'bew_tl_rthumb_' . md5( $remote_url . '|' . $min_width );
	$cached    = get_transient( $cache_key );

	if ( is_string( $cached ) && '' !== $cached ) {
		return $cached;
	}

	$local_url = bosa_ewc_sideload_min_width_thumbnail( $remote_url, $matches[0], $matches[3], $min_width );
	$result    = $local_url ? $local_url : $remote_url;

	// Cache success or failure for the same TTL as the remote catalog itself,
	// so a failed download doesn't retry on every single request.
	set_transient( $cache_key, $result, BEW_TL_REMOTE_TTL );

	return $result;
}

/**
 * Download a remote screenshot original, create a width-limited local copy,
 * and cache it under uploads/bew-tl-thumbs/.
 *
 * @param string $remote_url Narrow intermediate URL the API returned.
 * @param string $suffix     The matched "-{w}x{h}.ext" suffix.
 * @param string $ext        The matched file extension, including the dot.
 * @param int    $min_width  Target width in pixels.
 * @return string|false Local URL, or false on failure.
 */
function bosa_ewc_sideload_min_width_thumbnail( $remote_url, $suffix, $ext, $min_width ) {
	if ( ! function_exists( 'download_url' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'wp_get_image_editor' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$original_url = str_replace( $suffix, $ext, $remote_url );

	$tmp_file = download_url( $original_url );
	if ( is_wp_error( $tmp_file ) ) {
		// The guessed unsuffixed original doesn't exist — fall back to
		// downloading the URL we were actually given.
		$tmp_file = download_url( $remote_url );
		if ( is_wp_error( $tmp_file ) ) {
			return false;
		}
	}

	$editor = wp_get_image_editor( $tmp_file );
	if ( is_wp_error( $editor ) ) {
		wp_delete_file( $tmp_file );
		return false;
	}

	$editor->resize( $min_width, 0, false );

	$upload_dir = wp_upload_dir();
	$cache_dir  = trailingslashit( $upload_dir['basedir'] ) . 'bew-tl-thumbs';
	wp_mkdir_p( $cache_dir );

	$filename = md5( $remote_url ) . '-' . $min_width . 'w' . $ext;
	$saved    = $editor->save( trailingslashit( $cache_dir ) . $filename );

	wp_delete_file( $tmp_file );

	if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
		return false;
	}

	return trailingslashit( $upload_dir['baseurl'] ) . 'bew-tl-thumbs/' . wp_basename( $saved['path'] );
}

/**
 * Return the live preview URL for a template.
 *
 * @param int    $post_id      Local post ID (0 for remote-only items).
 * @param string $elementor_id Remote Elementor template post ID.
 * @return string
 */
function bosa_ewc_get_preview_url( $post_id = 0, $elementor_id = '' ) {
	$post_id = absint( $post_id );

	if ( $post_id ) {
		$meta_url = get_post_meta( $post_id, '_bosa_template_kits_preview_url', true );
		if ( is_string( $meta_url ) && '' !== $meta_url ) {
			return esc_url( $meta_url );
		}

		$permalink = get_permalink( $post_id );
		if ( $permalink ) {
			return esc_url( $permalink );
		}
	}

	$elementor_id = absint( $elementor_id );
	if ( $elementor_id ) {
		return esc_url( untrailingslashit( BEW_TL_REMOTE_URL ) . '/?p=' . $elementor_id );
	}

	return '';
}

/**
 * Return the decoded _elementor_data for a post, or null on failure.
 *
 * @param int $post_id Post ID.
 * @return array|null
 */
function bosa_ewc_get_elementor_data( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id ) {
		return null;
	}

	$raw = get_post_meta( $post_id, '_elementor_data', true );
	if ( empty( $raw ) ) {
		return null;
	}

	$decoded = json_decode( $raw, true );
	return JSON_ERROR_NONE === json_last_error() ? $decoded : null;
}

/**
 * Fix a known demo-content issue where a stripped JSON escape left "u00a9"
 * instead of "©". Only this exact copyright symbol case is corrected.
 *
 * @param mixed $data Elementor element tree (array), or a scalar leaf value.
 * @return mixed
 */
function bosa_ewc_fix_copyright_symbol( $data ) {
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			$data[ $key ] = bosa_ewc_fix_copyright_symbol( $value );
		}
		return $data;
	}

	if ( is_string( $data ) ) {
		return str_replace( 'u00a9', '©', $data );
	}

	return $data;
}

// -------------------------------------------------------------------------
// REST response helpers
// -------------------------------------------------------------------------

/**
 * Build a standardised REST success response.
 *
 * @param mixed $data   Response payload.
 * @param int   $status HTTP status code. Default 200.
 * @return WP_REST_Response
 */
function bosa_ewc_rest_success( $data, $status = 200 ) {
	return new WP_REST_Response( [ 'success' => true, 'data' => $data ], absint( $status ) );
}

/**
 * Build a standardised REST error response.
 *
 * @param string $code    Machine-readable error code.
 * @param string $message Human-readable message.
 * @param int    $status  HTTP status code. Default 400.
 * @return WP_Error
 */
function bosa_ewc_rest_error( $code, $message, $status = 400 ) {
	return new WP_Error( sanitize_key( $code ), $message, [ 'status' => absint( $status ) ] );
}

/**
 * Remove the trailing " Pro" suffix from display titles in the Template
 * Library modal. This only affects the shown label, not the CPT title.
 *
 * @param string $title Raw title.
 * @return string
 */
function bosa_ewc_strip_pro_suffix( $title ) {
	return trim( (string) preg_replace( '/\s+pro$/i', '', trim( (string) $title ) ) );
}

/**
 * Resolve a synced Page/Section source site title for display, matching Kit
 * title formatting by removing the "Bosa " prefix and trailing " Pro" suffix.
 *
 * @param int $post_id CPT post ID.
 * @return string Site title, or an empty string when unresolvable (e.g. a
 *                locally-synced Saved Template with no source site).
 */
function bosa_ewc_get_source_site_title( $post_id ) {
	$blog_id = (int) get_post_meta( absint( $post_id ), '_bosa_template_kits_source_blog_id', true );

	if ( $blog_id <= 0 ) {
		return '';
	}

	$details = get_blog_details( $blog_id );

	if ( ! $details ) {
		return '';
	}

	$title = preg_replace( '/^bosa[\s\-_]+/i', '', trim( (string) $details->blogname ) );

	return bosa_ewc_strip_pro_suffix( $title );
}

/**
 * Return page and section post IDs linked to a template kit.
 *
 * @param int $kit_id Kit post ID.
 * @return array{pages: int[], sections: int[]}
 */
function bosa_ewc_get_kit_included_ids( $kit_id ) {
	$kit_id = absint( $kit_id );

	$pages = get_post_meta( $kit_id, '_bosa_template_kits_included_pages', true );
	if ( ! is_array( $pages ) || empty( $pages ) ) {
		$pages = get_post_meta( $kit_id, '_bosa_kit_page_ids', true );
	}

	$sections = get_post_meta( $kit_id, '_bosa_template_kits_included_sections', true );
	if ( ! is_array( $sections ) || empty( $sections ) ) {
		$sections = get_post_meta( $kit_id, '_bosa_kit_section_ids', true );
	}

	return [
		'pages'    => array_values( array_filter( array_map( 'absint', (array) $pages ) ) ),
		'sections' => array_values( array_filter( array_map( 'absint', (array) $sections ) ) ),
	];
}
