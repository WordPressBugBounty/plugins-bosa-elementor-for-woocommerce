<?php
/**
 * REST API endpoints for the Template Library module.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles Template Library REST routes.
 */
class Bosa_Ewc_API {

	const NAMESPACE = 'bew/v1';

	/**
	 * @return void
	 */
	public function run() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'pre_get_posts', [ $this, 'enforce_library_publish_only' ], PHP_INT_MAX );
		add_filter( 'posts_where',   [ $this, 'enforce_publish_where' ],        PHP_INT_MAX, 2 );
	}

	/**
	 * @param WP_Query $query
	 */
	public function enforce_library_publish_only( WP_Query $query ) {
		if ( $query->get( 'bosa_tk_library_only' ) ) {
			$query->set( 'post_status', 'publish' );
		}
	}

	/**
	 * @param string   $where
	 * @param WP_Query $query
	 * @return string
	 */
	public function enforce_publish_where( $where, $query ) {
		global $wpdb;
		if ( $query->get( 'bosa_tk_library_only' ) ) {
			$where .= " AND {$wpdb->posts}.post_status = 'publish'";
		}
		return $where;
	}

	/**
	 * @return void
	 */
	public function register_routes() {
		$auth       = [ $this, 'check_permission' ];
		$public     = '__return_true';
		$collection = $this->get_collection_args();
		$type_map   = bosa_ewc_get_type_map();

		$type_routes = [
			'/templates' => null,
			'/pages'     => [ 'bosa_tk_page', 'bosa_template_kits_page' ],
			'/sections'  => [ 'bosa_tk_section', 'bosa_template_kits_section' ],
			'/kits'      => [ 'bosa_tk_kit', 'bosa_template_kits_kit' ],
			'/headers'   => [ 'bosa_tk_header', 'bosa_template_kits_header' ],
			'/footers'   => [ 'bosa_tk_footer', 'bosa_template_kits_footer' ],
		];

		foreach ( $type_routes as $route => $candidate_types ) {
			register_rest_route( self::NAMESPACE, $route, [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => function ( WP_REST_Request $request ) use ( $candidate_types, $type_map ) {
					if ( null === $candidate_types ) {
						$post_types = $this->resolve_type_filter( $request );
					} else {
						$post_types = array_values( array_intersect( $candidate_types, array_keys( $type_map ) ) );
					}
					return $this->get_by_type( $post_types ?: array_keys( $type_map ), $request );
				},
				'permission_callback' => $public,
				'args'                => $collection,
			] );
		}

		register_rest_route( self::NAMESPACE, '/kits/(?P<id>\d+)/items', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_kit_items' ],
			'permission_callback' => $public,
			'args'                => [
				'id' => [
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/categories', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'get_categories' ],
			'permission_callback' => $public,
			'args'                => [
				'type' => [
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'default'           => '',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/clear-cache', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'clear_cache' ],
			'permission_callback' => $auth,
		] );

		register_rest_route( self::NAMESPACE, '/install-plugin', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'install_plugin' ],
			'permission_callback' => function () {
				return current_user_can( 'install_plugins' );
			},
			'args'                => [
				'plugin_slug' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/import', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $this, 'import_template' ],
			'permission_callback' => $auth,
			'args'                => [
				'template_id' => [
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => function ( $value ) {
						return is_numeric( $value ) && intval( $value ) > 0;
					},
				],
				'use_placeholders' => [
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'default'           => false,
				],
			],
		] );
	}

	/**
	 * @return bool|WP_Error
	 */
	public function check_permission( ?WP_REST_Request $request = null ) {
		if ( $request && WP_REST_Server::READABLE === $request->get_method() ) {
			return true;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'bew_tl_forbidden',
				__( 'You do not have permission to access this resource.', 'bosa-elementor-for-woocommerce' ),
				[ 'status' => 403 ]
			);
		}
		return true;
	}

	/**
	 * @return WP_REST_Response
	 */
	public function clear_cache() {
		Bosa_Ewc_Sync::run();
		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_kit_items( WP_REST_Request $request ) {
		return $this->get_kit_items_for_blog( $request );
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	private function get_kit_items_for_blog( WP_REST_Request $request ) {
		$kit_id = absint( $request->get_param( 'id' ) );

		if ( ! $kit_id ) {
			return bosa_ewc_rest_error( 'invalid_id', __( 'Invalid kit ID.', 'bosa-elementor-for-woocommerce' ), 400 );
		}

		$kit_post = $this->resolve_kit_post( $kit_id );

		if ( $kit_post ) {
			return $this->build_local_kit_items_response( $kit_post->ID );
		}

		$remote = $this->get_remote_kit_items_response( $kit_id );
		if ( $remote ) {
			return $remote;
		}

		return $this->empty_kit_items_response();
	}

	/**
	 * @param int $kit_id
	 * @return WP_Post|null
	 */
	private function resolve_kit_post( $kit_id ) {
		$kit_id    = absint( $kit_id );
		$type_map  = bosa_ewc_get_type_map();
		$kit_types = array_keys( array_filter( $type_map, function ( $label ) {
			return 'kit' === $label;
		} ) );

		$kit_post = get_post( $kit_id );
		if ( $kit_post && in_array( $kit_post->post_type, $kit_types, true ) && 'publish' === $kit_post->post_status ) {
			return $kit_post;
		}

		$lookup = new WP_Query( [
			'post_type'              => $kit_types,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				[
					'key'     => '_bosa_template_kits_elementor_id',
					'value'   => (string) $kit_id,
					'compare' => '=',
				],
				[
					'key'     => '_elementor_template_id',
					'value'   => (string) $kit_id,
					'compare' => '=',
				],
			],
		] );

		if ( ! empty( $lookup->posts ) ) {
			$resolved = get_post( (int) $lookup->posts[0] );
			wp_reset_postdata();
			if ( $resolved instanceof WP_Post ) {
				return $resolved;
			}
		}

		wp_reset_postdata();
		return null;
	}

	/**
	 * @param int $kit_id
	 * @return WP_REST_Response
	 */
	private function build_local_kit_items_response( $kit_id ) {
		$type_map    = bosa_ewc_get_type_map();
		$included    = bosa_ewc_get_kit_included_ids( $kit_id );
		$post_ids    = array_values( array_unique( array_merge( $included['pages'], $included['sections'] ) ) );
		$child_types = array_keys( array_filter( $type_map, function ( $label ) {
			return in_array( $label, [ 'page', 'section' ], true );
		} ) );

		if ( empty( $post_ids ) ) {
			return $this->empty_kit_items_response();
		}

		$query = new WP_Query( [
			'post_type'            => $child_types,
			'post__in'             => $post_ids,
			'posts_per_page'       => -1,
			'orderby'              => 'post__in',
			'post_status'          => 'publish',
			'bosa_tk_library_only' => true,
			'no_found_rows'        => true,
		] );

		$posts    = $this->format_template_posts( $query->posts );
		$pages    = [];
		$sections = [];

		foreach ( $posts as $item ) {
			if ( empty( $item['type'] ) ) {
				continue;
			}
			if ( 'page' === $item['type'] ) {
				$pages[] = $item;
			} elseif ( 'section' === $item['type'] ) {
				$sections[] = $item;
			}
		}

		wp_reset_postdata();

		return $this->kit_items_response( $pages, $sections );
	}

	/**
	 * @param int $kit_id
	 * @return WP_REST_Response|null
	 */
	private function get_remote_kit_items_response( $kit_id ) {
		$kit_id = absint( $kit_id );
		// find_remote_kit() only scans get_remote_for_api()'s default
		// 100-item catalog window -- older kits sorted past that window on
		// the remote hub are never matched here, even though they still
		// exist remotely. Rather than giving up on that alone, always fall
		// through to fetch_remote_kit_items_directly() below, which looks
		// the kit up by id directly and doesn't depend on it being within
		// that window.
		$kit = $this->find_remote_kit( $kit_id );

		if ( $kit ) {
			$page_ids    = array_values( array_filter( array_map( 'absint', (array) ( $kit['included_pages'] ?? [] ) ) ) );
			$section_ids = array_values( array_filter( array_map( 'absint', (array) ( $kit['included_sections'] ?? [] ) ) ) );

			if ( ! empty( $page_ids ) || ! empty( $section_ids ) ) {
				$pages    = $this->get_remote_templates_by_ids( 'page', $page_ids );
				$sections = $this->get_remote_templates_by_ids( 'section', $section_ids );
				$pages    = $this->attach_kit_title_to_pages( $pages, $kit['title'] ?? '' );
				return $this->kit_items_response( $pages, $sections );
			}
		}

		$remote = $this->fetch_remote_kit_items_directly( $kit_id, $kit['title'] ?? '' );
		if ( $remote ) {
			return $remote;
		}

		return $this->empty_kit_items_response();
	}

	/**
	 * @param int    $kit_id
	 * @param string $kit_title Already-cleaned kit title, for the page-title site suffix.
	 * @return WP_REST_Response|null
	 */
	private function fetch_remote_kit_items_directly( $kit_id, $kit_title = '' ) {
		// Was a bare wp_remote_get() with no auth header and WordPress's
		// default user-agent -- indistinguishable from generic bot traffic,
		// so the remote host's own bot protection could challenge/block it
		// even though the exact same host answers get_remote_for_api()'s
		// requests below just fine. Bosa_Ewc_Remote_Client sends the same
		// identifying user-agent and X-Bosa-TK-Key header that path already
		// relies on, so this fallback is authenticated exactly the same way.
		$client = new Bosa_Ewc_Remote_Client( BEW_TL_REMOTE_URL, BEW_TL_API_KEY );
		$items  = $client->fetch_kit_items( $kit_id );

		if ( is_wp_error( $items ) || empty( $items ) ) {
			return null;
		}

		$pages    = [];
		$sections = [];
		foreach ( $items as $item ) {
			$row = $this->format_remote_template( $item );
			if ( empty( $row ) ) {
				continue;
			}
			if ( 'section' === ( $row['type'] ?? '' ) ) {
				$sections[] = $row;
			} else {
				$pages[] = $row;
			}
		}

		if ( empty( $pages ) && empty( $sections ) ) {
			return null;
		}

		$pages = $this->attach_kit_title_to_pages( $pages, $kit_title );

		return $this->kit_items_response( $pages, $sections );
	}

	/**
	 * @param int $kit_id
	 * @return array|null
	 */
	private function find_remote_kit( $kit_id ) {
		// get_remote_for_api()'s default $needed_total (100) only scans the
		// remote hub's first page of kits -- older kits (lower remote IDs)
		// sort past that page and were never found here, even though their
		// own catalog row already has valid included_pages/included_sections
		// to resolve locally. 2000 covers the full kit catalog with room to
		// grow; each 100-item page is cached independently (see
		// get_remote_for_api()), so this only costs extra remote pages once.
		$kits = $this->get_remote_for_api( [ 'type' => 'kit' ], 2000 );

		foreach ( $kits as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row_id        = absint( $row['id'] ?? 0 );
			$row_elementor = absint( $row['elementor_id'] ?? 0 );
			if ( $row_id === $kit_id || ( $row_elementor && $row_elementor === $kit_id ) ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * @param string $type
	 * @param int[]  $ids
	 * @return array
	 */
	private function get_remote_templates_by_ids( $type, array $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
		if ( empty( $ids ) ) {
			return [];
		}

		$wanted  = array_flip( $ids );
		$remote  = $this->get_remote_catalog_rows( sanitize_key( $type ) );
		$matched = [];

		foreach ( $remote as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$row_id = absint( $row['id'] ?? 0 );
			if ( ! $row_id || ! isset( $wanted[ $row_id ] ) ) {
				continue;
			}
			$matched[] = $row;
			unset( $wanted[ $row_id ] );
			if ( empty( $wanted ) ) {
				break;
			}
		}

		return $matched;
	}

	/**
	 * @param array $pages
	 * @param array $sections
	 * @return WP_REST_Response
	 */
	private function kit_items_response( array $pages, array $sections ) {
		return new WP_REST_Response(
			[
				'success'  => true,
				'data'     => array_merge( $pages, $sections ),
				'pages'    => $pages,
				'sections' => $sections,
			],
			200
		);
	}

	/**
	 * @return WP_REST_Response
	 */
	private function empty_kit_items_response() {
		return new WP_REST_Response(
			[ 'success' => true, 'data' => [], 'pages' => [], 'sections' => [] ],
			200
		);
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_categories( WP_REST_Request $request ) {
		$type     = (string) $request->get_param( 'type' );
		$resolved = bosa_ewc_resolve_category_type( $type );

		if ( $resolved ) {
			$labels = bosa_ewc_get_categories_for_type( $resolved );
			$data   = [];
			foreach ( $labels as $label ) {
				$data[] = [ 'slug' => sanitize_title( $label ), 'name' => $label, 'label' => $label ];
			}
			return new WP_REST_Response( [ 'success' => true, 'type' => $resolved, 'data' => $data ], 200 );
		}

		$categories = bosa_ewc_get_all_categories();
		$data       = [];
		foreach ( $categories as $slug => $name ) {
			$data[] = [ 'slug' => $slug, 'name' => $name, 'label' => $name ];
		}
		return new WP_REST_Response( [ 'success' => true, 'data' => $data ], 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_template( WP_REST_Request $request ) {
		$template_id      = absint( $request->get_param( 'template_id' ) );
		$use_placeholders = (bool) $request->get_param( 'use_placeholders' );

		if ( ! bosa_ewc_can_import_template( $template_id ) ) {
			return new WP_Error(
				'bew_tl_pro_required',
				__( 'This template requires BEW Pro.', 'bosa-elementor-for-woocommerce' ),
				[ 'status' => 403 ]
			);
		}

		require_once BEW_TL_DIR . 'admin/class-editor-prompt.php';
		require_once BEW_TL_DIR . 'frontend/class-widget-plugin-map.php';
		require_once BEW_TL_DIR . 'frontend/class-dependency-scanner.php';
		require_once BEW_TL_DIR . 'frontend/class-importer.php';

		$importer = new Bosa_Ewc_Importer();
		$result   = $importer->import( $template_id, $use_placeholders );

		if ( is_wp_error( $result ) ) {
			$client   = new Bosa_Ewc_Remote_Client( BEW_TL_REMOTE_URL, BEW_TL_API_KEY );
			$response = $client->import_template( $template_id );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$content = isset( $response['content'] ) && is_array( $response['content'] ) ? $response['content'] : null;
			if ( ! $content ) {
				return new WP_Error( 'bew_tl_no_data', __( 'This template has no Elementor data.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 404 ] );
			}

			$scan_result     = class_exists( 'Bosa_Ewc_Dependency_Scanner' )
				? ( new Bosa_Ewc_Dependency_Scanner() )->scan( $content )
				: [ 'all' => [], 'missing' => [] ];
			$missing_plugins = $scan_result['missing'];

			if ( ! empty( $missing_plugins ) && class_exists( 'Bosa_Ewc_Editor_Prompt' ) ) {
				Bosa_Ewc_Editor_Prompt::store_missing_plugins( $missing_plugins );
			}

			$original_content    = $content;
			$registered_types    = Bosa_Ewc_Importer::get_registered_widget_types();
			$placeholder_content = $importer->replace_missing_widgets(
				Bosa_Ewc_Importer::deep_copy_elements( $content ),
				$scan_result['all'],
				$registered_types
			);
			$import_content      = ( $use_placeholders && ! empty( $missing_plugins ) )
				? $placeholder_content
				: $original_content;

			$result = [
				'id'              => $template_id,
				'title'           => sanitize_text_field( isset( $response['title'] ) ? $response['title'] : '' ),
				'type'            => sanitize_key( isset( $response['type'] ) ? $response['type'] : 'section' ),
				'content'         => $import_content,
				'page_settings'   => [],
				'missing_plugins' => $missing_plugins,
			];

			if ( ! empty( $missing_plugins ) ) {
				$result['original_content']    = $original_content;
				$result['placeholder_content'] = $placeholder_content;
			}
		}

		if ( ! empty( $result['missing_plugins'] ) && is_array( $result['missing_plugins'] ) ) {
			foreach ( $result['missing_plugins'] as $key => &$plugin ) {
				if ( ! is_array( $plugin ) ) {
					continue;
				}
				$plugin_slug = ! empty( $plugin['slug'] ) ? sanitize_key( $plugin['slug'] ) : sanitize_key( (string) $key );
				if ( 0 === strpos( $plugin_slug, 'unknown_' ) ) {
					continue;
				}
				$plugin['slug']  = $plugin_slug;
				$plugin['nonce'] = wp_create_nonce( 'bosa_install_' . $plugin_slug );
			}
			unset( $plugin );
		}

		foreach ( [ 'content', 'original_content', 'placeholder_content' ] as $content_key ) {
			if ( ! empty( $result[ $content_key ] ) ) {
				$result[ $content_key ] = bosa_ewc_fix_copyright_symbol( $result[ $content_key ] );
			}
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function install_plugin( WP_REST_Request $request ) {
		require_once BEW_TL_DIR . 'admin/class-editor-prompt.php';
		require_once BEW_TL_DIR . 'frontend/class-widget-plugin-map.php';
		require_once BEW_TL_DIR . 'frontend/class-plugin-installer.php';

		$slug      = sanitize_key( $request->get_param( 'plugin_slug' ) );
		$installer = new Bosa_Ewc_Plugin_Installer();
		$result    = $installer->install_plugin_by_slug( $slug );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $result ], 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 * @return string[]
	 */
	private function resolve_type_filter( WP_REST_Request $request ) {
		$type_map = bosa_ewc_get_type_map();
		$type     = sanitize_key( (string) $request->get_param( 'type' ) );
		$matched  = array_keys( $type_map, $type, true );
		return ! empty( $matched ) ? $matched : array_keys( $type_map );
	}

	/**
	 * @param string[]        $post_types
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	private function get_by_type( array $post_types, WP_REST_Request $request ) {
		$search   = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$category = sanitize_text_field( (string) $request->get_param( 'category' ) );
		$is_free  = $request->get_param( 'is_free' );
		$per_page = absint( $request->get_param( 'per_page' ) );
		$page     = absint( $request->get_param( 'page' ) );

		$per_page = ( $per_page > 0 && $per_page <= 100 ) ? $per_page : 60;
		$page     = $page > 0 ? $page : 1;

		$query_args = [
			'post_type'            => $post_types,
			'post_status'          => 'publish',
			'bosa_tk_library_only' => true,
			'orderby'              => 'date',
			'order'                => 'DESC',
		];

		if ( $search ) {
			$query_args['s'] = $search;
		}

		$meta_query = [];

		if ( null !== $is_free ) {
			$meta_query[] = [
				'key'     => '_bosa_template_kits_is_free',
				'value'   => $is_free ? '1' : '0',
				'compare' => '=',
			];
		}

		if ( $category ) {
			$query_args['tax_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'bosa_template_kit_cat',
					'field'    => 'slug',
					'terms'    => sanitize_title( $category ),
				],
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		}

		$remote_type = sanitize_key( (string) $request->get_param( 'type' ) );
		if ( ! $remote_type ) {
			$remote_type = $this->resolve_type_label( $post_types );
		}

		// Only fetch as much remote data as this page needs; get_remote_for_api() pages incrementally.
		$needed_total = $page * $per_page;
		$remote_data  = $this->get_remote_for_api( [
			'type'     => $remote_type,
			'category' => $category,
			'search'   => $search,
		], $needed_total );

		if ( ! empty( $remote_data ) ) {
			$local_query = new WP_Query( array_merge( $query_args, [
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			] ) );
			$local_data  = $this->format_template_posts( $local_query->posts );
			wp_reset_postdata();

			$merged      = array_merge( $remote_data, $local_data );
			$total       = count( $merged );
			$offset      = ( $page - 1 ) * $per_page;
			$data        = array_slice( $merged, $offset, $per_page );
			$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		} else {
			$list_query  = new WP_Query( array_merge( $query_args, [
				'posts_per_page' => $per_page,
				'offset'         => ( $page - 1 ) * $per_page,
			] ) );
			$total       = (int) $list_query->found_posts;
			$data        = $this->format_template_posts( $list_query->posts );
			$total_pages = max( 1, (int) ceil( $total / $per_page ) );
			wp_reset_postdata();
		}

		// Category dropdown must list every category, so re-scan unfiltered and
		// uncapped (PHP_INT_MAX) — otherwise a category whose only items sort past
		// the first remote page would never surface. Still stops on a short page.
		if ( $category || $search ) {
			$category_scan_remote = $this->get_remote_for_api( [
				'type' => $remote_type,
			], PHP_INT_MAX );
			$category_scan_local_query = new WP_Query( array_merge(
				array_diff_key( $query_args, [ 'tax_query' => true, 's' => true ] ),
				[ 'posts_per_page' => -1, 'no_found_rows' => true ]
			) );
			$all_items = array_merge(
				$category_scan_remote,
				$this->format_template_posts( $category_scan_local_query->posts )
			);
			wp_reset_postdata();
		} else {
			$all_items = isset( $merged ) ? $merged : $data;
		}

		// Keyed by sanitize_title() to dedupe regardless of case/punctuation. The real
		// $item['category'] label is collected first so it always wins over a
		// merely-derived $item['categories'] slug for the same category.
		$category_options = bosa_ewc_get_categories_for_type( $remote_type );
		$seen_cats = [];
		foreach ( $category_options as $existing_label ) {
			$seen_cats[ sanitize_title( $existing_label ) ] = $existing_label;
		}

		foreach ( $all_items as $item ) {
			if ( ! empty( $item['category'] ) ) {
				$key = sanitize_title( $item['category'] );
				if ( ! isset( $seen_cats[ $key ] ) ) {
					$seen_cats[ $key ] = $item['category'];
				}
			}
		}

		foreach ( $all_items as $item ) {
			if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
				foreach ( $item['categories'] as $slug ) {
					$key = sanitize_title( $slug );
					if ( $key && ! isset( $seen_cats[ $key ] ) ) {
						$seen_cats[ $key ] = ucwords( str_replace( '-', ' ', $key ) );
					}
				}
			}
		}

		$category_options = array_values( $seen_cats );
		sort( $category_options );

		// A full page back means there's likely more; the client requests until it gets a short page.
		$has_more = count( $data ) >= $per_page;

		return new WP_REST_Response(
			[
				'success'          => true,
				'data'             => $data,
				'total'            => $total,
				'total_pages'      => $total_pages,
				'has_more'         => $has_more,
				'category_options' => array_values( $category_options ),
			],
			200
		);
	}

	/**
	 * A failed remote fetch was previously discarded into a bare empty
	 * array with no record of why, so a genuinely empty catalog and a
	 * broken connection to the hub were indistinguishable from the admin
	 * side -- both just showed "No templates found" after a sync. Log the
	 * same way Bosa_Ewc_Sync::run() already does for the daily cron sync,
	 * so a remote failure is visible in the PHP error log instead of
	 * silently swallowed.
	 *
	 * @param string   $type     Requested template type ('' for all).
	 * @param array    $query    Query args sent to the remote API.
	 * @param WP_Error $error    The error returned by the remote client.
	 * @return void
	 */
	private function log_remote_fetch_error( $type, array $query, WP_Error $error ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf(
			'BEW template library: remote fetch failed for type "%s" (%s): %s',
			$type ?: 'all',
			wp_json_encode( $query ),
			$error->get_error_message()
		) );
	}

	/**
	 * Fetch remote catalog items for a type/category/search filter, paging
	 * through the remote API in 100-item batches as needed. Each batch is
	 * cached independently.
	 *
	 * @param array $params       {type, category, search}.
	 * @param int   $needed_total Minimum number of items to cover.
	 * @param bool  $enrich       Whether to run type-specific enrichment
	 *                            (enrich_remote_kits() / attach_remote_page_site_titles()).
	 *                            Must be false when called from those two methods'
	 *                            own catalog lookups (via get_remote_catalog_rows()) —
	 *                            otherwise 'kit' enrichment calls back into 'page'/
	 *                            'section' lookups, which for 'page' calls back into
	 *                            'kit' enrichment again, recursing indefinitely.
	 *                            Included in the cache key so an enriched and a
	 *                            raw fetch of the same filter never overwrite
	 *                            each other's cached batches.
	 * @return array
	 */
	private function get_remote_for_api( array $params, $needed_total = 100, $enrich = true ) {
		$type       = isset( $params['type'] ) ? sanitize_key( $params['type'] ) : '';
		$category   = isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : '';
		$search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$cache      = new Bosa_Ewc_Cache();
		$client     = new Bosa_Ewc_Remote_Client( BEW_TL_REMOTE_URL, BEW_TL_API_KEY );
		$filter_key = md5( wp_json_encode( [ 'type' => $type, 'category' => $category, 'search' => $search, 'enrich' => $enrich ] ) );

		$remote_per_page = 100;
		$pages_needed     = max( 1, (int) ceil( absint( $needed_total ) / $remote_per_page ) );
		$formatted        = [];

		for ( $remote_page = 1; $remote_page <= $pages_needed; $remote_page++ ) {
			$page_cache_key = 'v12_remote_' . $filter_key . '_p' . $remote_page;
			$page_rows      = $cache->get( $page_cache_key );

			if ( false === $page_rows || ! is_array( $page_rows ) || empty( $page_rows ) ) {
				$query = array_filter( [
					'per_page' => $remote_per_page,
					'page'     => $remote_page,
					'category' => $category,
					'search'   => $search,
				] );

				$response = $client->fetch_by_type( $type, $query );

				if ( is_wp_error( $response ) ) {
					$this->log_remote_fetch_error( $type, $query, $response );
				}

				if ( is_wp_error( $response ) || ! is_array( $response ) ) {
					$response = [];
				}

				// Same fallback as before: if the typed endpoint returns nothing
				// on the first page, try the combined /templates endpoint.
				if ( empty( $response ) && $type && 1 === $remote_page ) {
					$combined = $client->fetch_templates( $query );
					if ( is_wp_error( $combined ) ) {
						$this->log_remote_fetch_error( $type, $query, $combined );
					}
					if ( is_array( $combined ) ) {
						$response = array_values( array_filter( $combined, function ( $item ) use ( $type ) {
							return isset( $item['type'] ) && $type === sanitize_key( $item['type'] );
						} ) );
					}
				}

				$page_rows = [];
				foreach ( $response as $item ) {
					$row = $this->format_remote_template( $item );
					if ( ! empty( $row ) ) {
						$page_rows[] = $row;
					}
				}

				if ( $enrich && 'kit' === $type && ! empty( $page_rows ) ) {
					$page_rows = $this->enrich_remote_kits( $page_rows );
				}

				if ( $enrich && 'page' === $type && ! empty( $page_rows ) ) {
					$page_rows = $this->attach_remote_page_site_titles( $page_rows );
				}

				if ( ! empty( $page_rows ) ) {
					$cache->set( $page_cache_key, $page_rows, BEW_TL_REMOTE_TTL );
				}
			}

			if ( empty( $page_rows ) ) {
				break;
			}

			$formatted = array_merge( $formatted, $page_rows );

			if ( count( $page_rows ) < $remote_per_page ) {
				break; // Last remote page reached — nothing more to fetch.
			}
		}

		return $formatted;
	}

	/**
	 * @param array $kits
	 * @return array
	 */
	private function enrich_remote_kits( array $kits ) {
		$needs_enrichment = false;
		foreach ( $kits as $kit ) {
			if ( empty( $kit['included_pages'] ) || empty( $kit['included_sections'] ) ) {
				$needs_enrichment = true;
				break;
			}
		}

		if ( ! $needs_enrichment ) {
			return $kits;
		}

		$pages            = $this->get_remote_catalog_rows( 'page' );
		$sections         = $this->get_remote_catalog_rows( 'section' );
		$claimed_pages    = [];
		$claimed_sections = [];
		$sorted_indices   = array_keys( $kits );

		usort( $sorted_indices, function ( $left, $right ) use ( $kits ) {
			return strlen( (string) ( $kits[ $right ]['title'] ?? '' ) ) - strlen( (string) ( $kits[ $left ]['title'] ?? '' ) );
		} );

		foreach ( $sorted_indices as $index ) {
			$kit = $kits[ $index ];

			if ( ! empty( $kit['included_pages'] ) && ! empty( $kit['included_sections'] ) ) {
				foreach ( (array) $kit['included_pages'] as $id ) { $claimed_pages[ absint( $id ) ] = true; }
				foreach ( (array) $kit['included_sections'] as $id ) { $claimed_sections[ absint( $id ) ] = true; }
				continue;
			}

			$resolved    = $this->resolve_remote_kit_includes( $kit, $pages, $sections, $claimed_pages, $claimed_sections );
			$page_ids    = ! empty( $kit['included_pages'] )
				? array_values( array_filter( array_map( 'absint', (array) $kit['included_pages'] ) ) )
				: $resolved['pages'];
			$section_ids = ! empty( $kit['included_sections'] )
				? array_values( array_filter( array_map( 'absint', (array) $kit['included_sections'] ) ) )
				: $resolved['sections'];

			if ( empty( $page_ids ) && empty( $section_ids ) ) {
				continue;
			}

			foreach ( $page_ids as $id ) { $claimed_pages[ $id ] = true; }
			foreach ( $section_ids as $id ) { $claimed_sections[ $id ] = true; }

			$kits[ $index ]['pages']             = $page_ids;
			$kits[ $index ]['sections']          = $section_ids;
			$kits[ $index ]['included_pages']    = $page_ids;
			$kits[ $index ]['included_sections'] = $section_ids;
			$kits[ $index ]['item_count']        = count( $page_ids ) + count( $section_ids );
		}

		return $kits;
	}

	/**
	 * @param array $kit
	 * @param array $pages
	 * @param array $sections
	 * @param array $claimed_pages
	 * @param array $claimed_sections
	 * @return array{pages: int[], sections: int[]}
	 */
	private function resolve_remote_kit_includes( array $kit, array $pages, array $sections, array $claimed_pages = [], array $claimed_sections = [] ) {
		$thumb_key   = $this->get_remote_thumbnail_key( $kit['thumbnail'] ?? '' );
		$page_ids    = [];
		$section_ids = [];

		foreach ( $pages as $page ) {
			$page_id = absint( $page['id'] ?? 0 );
			if ( ! $page_id || isset( $claimed_pages[ $page_id ] ) ) { continue; }
			if ( $this->remote_item_belongs_to_kit( $page, $kit, $thumb_key ) ) {
				$page_ids[] = $page_id;
			}
		}

		foreach ( $sections as $section ) {
			$section_id = absint( $section['id'] ?? 0 );
			if ( ! $section_id || isset( $claimed_sections[ $section_id ] ) ) { continue; }
			if ( $this->remote_item_belongs_to_kit( $section, $kit, $thumb_key ) ) {
				$section_ids[] = $section_id;
			}
		}

		return [
			'pages'    => array_values( array_filter( $page_ids ) ),
			'sections' => array_values( array_filter( $section_ids ) ),
		];
	}

	/**
	 * @param array  $item
	 * @param array  $kit
	 * @param string $thumb_key Unused.
	 * @return bool
	 */
	private function remote_item_belongs_to_kit( array $item, array $kit, $thumb_key ) {
		$kit_preview  = rtrim( (string) ( $kit['preview_url'] ?? '' ), '/' );
		$item_preview = (string) ( $item['preview_url'] ?? '' );

		if ( $kit_preview && $item_preview && 0 === stripos( $item_preview, $kit_preview . '/' ) ) {
			return true;
		}

		if ( $thumb_key ) {
			$item_thumb = $this->get_remote_thumbnail_key( $item['thumbnail'] ?? '' );
			if ( $item_thumb && $thumb_key === $item_thumb ) {
				return true;
			}
		}

		$kit_slug = $this->get_remote_thumbnail_base( $kit['thumbnail'] ?? '' );
		if ( $kit_slug && strlen( $kit_slug ) > 5 ) {
			$item_slug = $this->get_remote_thumbnail_base( $item['thumbnail'] ?? '' );
			if ( $item_slug && 0 === strpos( $item_slug, $kit_slug ) ) {
				return true;
			}
		}

		$kit_title  = trim( (string) ( $kit['title'] ?? '' ) );
		$item_title = trim( (string) ( $item['title'] ?? '' ) );

		if ( ! $kit_title || ! $item_title ) { return false; }
		if ( 0 !== stripos( $item_title, $kit_title ) ) { return false; }

		$tail = substr( $item_title, strlen( $kit_title ) );
		return '' === $tail || ' ' === $tail[0];
	}

	/**
	 * @param string $url Thumbnail URL.
	 * @return string Base slug without dimensions or extension.
	 */
	private function get_remote_thumbnail_base( $url ) {
		$key = $this->get_remote_thumbnail_key( $url );
		if ( ! $key ) {
			return '';
		}
		$slug = preg_replace( '/\.[^.]+$/', '', $key );
		return preg_replace( '/-\d+[-x]\d+$/', '', $slug );
	}

	/**
	 * @param string $url
	 * @return string
	 */
	private function get_remote_thumbnail_key( $url ) {
		$path = wp_parse_url( (string) $url, PHP_URL_PATH );
		return $path ? strtolower( basename( $path ) ) : '';
	}

	/**
	 * @param string $type
	 * @return array
	 */
	private function get_remote_catalog_rows( $type ) {
		// Delegates to get_remote_for_api(), which already pages and caches these
		// batches, so this only pays the network cost once per cache window.
		//
		// enrich=false is required, not optional: this is called from inside
		// enrich_remote_kits() and attach_remote_page_site_titles(), which
		// get_remote_for_api() would otherwise invoke again while producing these
		// same rows, recursing indefinitely.
		return $this->get_remote_for_api( [ 'type' => sanitize_key( $type ) ], PHP_INT_MAX, false );
	}

	/**
	 * Append " - {Kit Title}" to each page's title for display, for the "viewing one
	 * kit's items" context where the parent kit is already known — no matching needed.
	 *
	 * @param array  $pages     Formatted page rows.
	 * @param string $kit_title Already-cleaned kit title (see format_remote_template()).
	 * @return array
	 */
	private function attach_kit_title_to_pages( array $pages, $kit_title ) {
		$kit_title = trim( (string) $kit_title );
		if ( '' === $kit_title ) {
			return $pages;
		}

		foreach ( $pages as &$page ) {
			$page = $this->append_kit_title_to_page( $page, $kit_title );
		}
		unset( $page );

		return $pages;
	}

	/**
	 * For the general "browse all pages" listing (no single kit context), match each
	 * page back to its parent kit using the same heuristics enrich_remote_kits() uses
	 * (preview URL / thumbnail / title-prefix), then append that kit's title.
	 *
	 * @param array $pages Formatted page rows.
	 * @return array
	 */
	private function attach_remote_page_site_titles( array $pages ) {
		$kits = $this->get_remote_catalog_rows( 'kit' );
		if ( empty( $kits ) ) {
			return $pages;
		}

		// Fast path: kits carry real included_pages IDs from the hub, so most
		// pages resolve with an O(1) lookup instead of scanning every kit.
		// The fuzzy scan below stays as a fallback for pages no kit lists.
		$kit_by_page_id = [];
		foreach ( $kits as $kit ) {
			foreach ( (array) ( $kit['included_pages'] ?? [] ) as $pid ) {
				$kit_by_page_id[ absint( $pid ) ] = $kit;
			}
		}

		foreach ( $pages as &$page ) {
			$matched_kit = $kit_by_page_id[ absint( $page['id'] ?? 0 ) ] ?? null;

			if ( ! $matched_kit ) {
				foreach ( $kits as $kit ) {
					$thumb_key = $this->get_remote_thumbnail_key( $kit['thumbnail'] ?? '' );
					if ( $this->remote_item_belongs_to_kit( $page, $kit, $thumb_key ) ) {
						$matched_kit = $kit;
						break;
					}
				}
			}

			if ( $matched_kit ) {
				$page = $this->append_kit_title_to_page( $page, $matched_kit['title'] ?? '' );
			}
		}
		unset( $page );

		return $pages;
	}

	/**
	 * @param array  $page_row  Formatted page row.
	 * @param string $kit_title Already-cleaned kit title.
	 * @return array
	 */
	private function append_kit_title_to_page( array $page_row, $kit_title ) {
		$kit_title = trim( (string) $kit_title );
		if ( '' === $kit_title || empty( $page_row['title'] ) ) {
			return $page_row;
		}

		// Defensive: don't double-append if a cached/re-processed row already has it.
		if ( false !== stripos( $page_row['title'], ' - ' . $kit_title ) ) {
			return $page_row;
		}

		$page_row['title'] .= ' - ' . $kit_title;

		return $page_row;
	}

	/**
	 * @param array    $item Raw remote template data.
	 * @param string[] $keys Key names to check, in priority order.
	 * @return int[]
	 */
	private function extract_kit_child_ids( array $item, array $keys ) {
		foreach ( $keys as $key ) {
			if ( empty( $item[ $key ] ) || ! is_array( $item[ $key ] ) ) {
				continue;
			}
			$ids = array_values( array_filter( array_map( function ( $v ) {
				return is_array( $v ) ? absint( $v['id'] ?? 0 ) : absint( $v );
			}, $item[ $key ] ) ) );
			if ( $ids ) {
				return $ids;
			}
		}
		return [];
	}

	/**
	 * @param mixed $item
	 * @return array
	 */
	private function format_remote_template( $item ) {
		if ( ! is_array( $item ) ) { return []; }
		$id = absint( isset( $item['id'] ) ? $item['id'] : 0 );
		if ( ! $id ) { return []; }
		if ( isset( $item['status'] ) && 'publish' !== $item['status'] ) { return []; }

		$category_label = isset( $item['category'] ) ? sanitize_text_field( $item['category'] ) : '';
		$category_slug  = $category_label ? sanitize_title( $category_label ) : sanitize_key( $item['category'] ?? '' );
		$elementor_id   = isset( $item['elementor_id'] ) ? sanitize_text_field( $item['elementor_id'] ) : (string) $id;
		$preview_url    = ! empty( $item['preview_url'] ) ? esc_url_raw( $item['preview_url'] ) : '';

		if ( ! $preview_url ) {
			$preview_url = bosa_ewc_get_preview_url( 0, $elementor_id );
		}

		$categories = [];
		if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) {
			$categories = array_values( array_filter( array_map( 'sanitize_title', $item['categories'] ) ) );
		}
		if ( ! $categories && $category_slug ) {
			$categories = [ $category_slug ];
		}

		$title = sanitize_text_field( isset( $item['title'] ) ? $item['title'] : '' );
		$type  = sanitize_key( isset( $item['type'] ) ? $item['type'] : 'section' );

		if ( 'kit' === $type || 'page' === $type ) {
			// Display only — hub-side child blognames are suffixed "Pro" internally
			// (e.g. "Tech Company Pro", "Home Pro"); that suffix is noise once
			// surfaced in this site's Template Library modal.
			$title = bosa_ewc_strip_pro_suffix( $title );
		}

		$row = [
			'id'           => $id,
			'title'        => $title,
			'type'         => $type,
			'category'     => $category_label,
			'categories'   => $categories,
			'is_free'      => isset( $item['is_free'] ) ? (bool) $item['is_free'] : true,
			'is_locked'    => false,
			'thumbnail'    => bosa_ewc_get_remote_thumbnail_url( isset( $item['thumbnail'] ) ? $item['thumbnail'] : '' ),
			'thumbnail_full' => esc_url_raw( isset( $item['thumbnail_full'] ) ? $item['thumbnail_full'] : ( isset( $item['thumbnail'] ) ? $item['thumbnail'] : '' ) ),
			'preview_url'  => $preview_url,
			'elementor_id' => $elementor_id,
		];

		if ( 'kit' === $row['type'] ) {
			$pages    = $this->extract_kit_child_ids( $item, [ 'included_pages', 'pages', 'page_ids' ] );
			$sections = $this->extract_kit_child_ids( $item, [ 'included_sections', 'sections', 'section_ids' ] );
			$row['pages']             = $pages;
			$row['sections']          = $sections;
			$row['included_pages']    = $pages;
			$row['included_sections'] = $sections;
			$row['item_count']        = count( $pages ) + count( $sections );
		}

		return $row;
	}

	/**
	 * @param string[] $post_types
	 * @return string
	 */
	private function resolve_type_label( array $post_types ) {
		$type_map = bosa_ewc_get_type_map();
		$labels   = array_unique( array_values( array_intersect_key( $type_map, array_flip( $post_types ) ) ) );
		return ( 1 === count( $labels ) ) ? reset( $labels ) : '';
	}

	/**
	 * @param WP_Post[] $posts
	 * @return array
	 */
	private function format_template_posts( array $posts ) {
		return array_values( array_filter(
			array_map( [ $this, 'format_template' ], $posts ),
			function ( $item ) { return ! empty( $item ); }
		) );
	}

	/**
	 * @param WP_Post $post
	 * @return array
	 */
	private function format_template( WP_Post $post ) {
		if ( 'publish' !== $post->post_status ) { return []; }

		$type_map       = bosa_ewc_get_type_map();
		$type           = $type_map[ $post->post_type ] ?? $post->post_type;
		$thumbnail      = bosa_ewc_get_thumbnail_url( $post->ID, 'large' );
		$thumbnail_full = bosa_ewc_get_thumbnail_url( $post->ID, 'full' );
		$is_free        = bosa_ewc_is_free_template( $post->ID );
		$categories     = bosa_ewc_get_template_categories( $post->ID );
		$category_label = '';

		if ( ! empty( $categories ) ) {
			$all_cats       = bosa_ewc_get_all_categories();
			$category_label = $all_cats[ $categories[0] ] ?? $categories[0];
		}

		$elementor_id_raw = get_post_meta( $post->ID, '_bosa_template_kits_elementor_id', true )
			?: get_post_meta( $post->ID, '_elementor_template_id', true );
		$elementor_id     = $elementor_id_raw ? sanitize_text_field( $elementor_id_raw ) : (string) $post->ID;

		$item = [
			'id'           => $post->ID,
			'title'        => get_the_title( $post ),
			'type'         => $type,
			'category'     => sanitize_text_field( $category_label ),
			'categories'   => $categories,
			'is_free'      => $is_free,
			'is_locked'    => false,
			'thumbnail'    => $thumbnail,
			'thumbnail_full' => $thumbnail_full,
			'preview_url'  => bosa_ewc_get_preview_url( $post->ID, $elementor_id ),
			'elementor_id' => $elementor_id,
		];

		if ( 'kit' === $type ) {
			$included                    = bosa_ewc_get_kit_included_ids( $post->ID );
			$item['pages']               = $included['pages'];
			$item['sections']            = $included['sections'];
			$item['included_pages']      = $included['pages'];
			$item['included_sections']   = $included['sections'];
			$item['item_count']          = count( $included['pages'] ) + count( $included['sections'] );

			// Display only — the stored Kit post_title is left untouched.
			$item['title'] = bosa_ewc_strip_pro_suffix( $item['title'] );
		} elseif ( 'page' === $type ) {
			$site_title = bosa_ewc_get_source_site_title( $post->ID );

			if ( '' !== $site_title ) {
				// Display only — the stored Page post_title is left untouched.
				$item['title'] = $item['title'] . ' - ' . $site_title;
			}
		}

		return apply_filters( 'bosa_ewc_api_template_data', $item, $post, $type );
	}

	/**
	 * @return array
	 */
	private function get_collection_args() {
		$type_map = bosa_ewc_get_type_map();
		return [
			'search'   => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'category' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
			'type'     => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key', 'enum' => array_merge( array_values( $type_map ), [ '' ] ), 'default' => '' ],
			'is_free'  => [ 'type' => 'boolean', 'sanitize_callback' => 'rest_sanitize_boolean', 'default' => null ],
			'per_page' => [ 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 60 ],
			'page'     => [ 'type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 1 ],
		];
	}
}
