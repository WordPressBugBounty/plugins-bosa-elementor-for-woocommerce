<?php
/**
 * Template import logic.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles importing a BEW template by post ID.
 */
class Bosa_Ewc_Importer {

	/** @var string[] */
	private $allowed_types = [];

	public function __construct() {
		$this->allowed_types = bosa_ewc_get_all_cpt_slugs();
	}

	/**
	 * @param int  $template_id
	 * @param bool $use_placeholders
	 * @return array|WP_Error
	 */
	public function import( $template_id, $use_placeholders = false ) {
		$template_id = intval( $template_id );

		if ( ! $template_id ) {
			return new WP_Error( 'bew_tl_invalid_id', __( 'Invalid template ID.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 400 ] );
		}

		$post = get_post( $template_id );

		if ( ! $post || ! in_array( $post->post_status, bosa_ewc_get_library_post_statuses(), true ) ) {
			return new WP_Error( 'bew_tl_not_found', __( 'Template not found.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 404 ] );
		}

		if ( ! in_array( $post->post_type, $this->allowed_types, true ) ) {
			return new WP_Error( 'bew_tl_invalid_type', __( 'This post is not a BEW Template Library template.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 400 ] );
		}

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );

		if ( empty( $elementor_data ) ) {
			return new WP_Error( 'bew_tl_no_data', __( 'This template has no Elementor data.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 404 ] );
		}

		$decoded = json_decode( $elementor_data, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'bew_tl_json_error', __( 'Template data is corrupted.', 'bosa-elementor-for-woocommerce' ), [ 'status' => 500 ] );
		}

		$decoded = apply_filters( 'bosa_ewc_import_data', $decoded, $template_id, $post );

		$scan_result = class_exists( 'Bosa_Ewc_Dependency_Scanner' )
			? ( new Bosa_Ewc_Dependency_Scanner() )->scan( $decoded )
			: [ 'all' => [], 'missing' => [] ];

		$missing_plugins = $scan_result['missing'];

		if ( ! empty( $missing_plugins ) && class_exists( 'Bosa_Ewc_Editor_Prompt' ) ) {
			Bosa_Ewc_Editor_Prompt::store_missing_plugins( $missing_plugins );
		}

		$original_decoded    = $decoded;
		$registered_types    = self::get_registered_widget_types();
		$placeholder_content = $this->replace_missing_widgets(
			self::deep_copy_elements( $decoded ),
			$scan_result['all'],
			$registered_types
		);

		$import_content = ( $use_placeholders && ! empty( $missing_plugins ) )
			? $placeholder_content
			: $original_decoded;

		do_action( 'bosa_ewc_after_import', $template_id, $import_content );

		$result = [
			'id'              => $post->ID,
			'title'           => get_the_title( $post ),
			'type'            => $this->get_type_label( $post->post_type ),
			'content'         => $import_content,
			'missing_plugins' => $missing_plugins,
		];

		if ( ! empty( $missing_plugins ) ) {
			$result['original_content']    = $original_decoded;
			$result['placeholder_content'] = $placeholder_content;
		}

		return $result;
	}

	/**
	 * @param string $post_type
	 * @return string
	 */
	private function get_type_label( $post_type ) {
		$map = bosa_ewc_get_type_map();
		return $map[ $post_type ] ?? $post_type;
	}

	/**
	 * Walk the element tree and replace unregistered widget types with HTML placeholders.
	 *
	 * @param array    $elements
	 * @param array    $detected_widgets
	 * @param string[] $registered_types
	 * @return array
	 */
	public function replace_missing_widgets( array $elements, array $detected_widgets, array $registered_types = [] ) {
		foreach ( $elements as &$element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['widgetType'] ) ) {
				$type        = $element['widgetType'];
				$plugin_name = null;
				$do_replace  = false;

				if ( isset( $detected_widgets[ $type ] ) && ! $detected_widgets[ $type ]['is_active'] ) {
					$plugin_name = $detected_widgets[ $type ]['plugin_name'];
					$do_replace  = true;
				} elseif ( ! empty( $registered_types ) && ! in_array( $type, $registered_types, true ) ) {
					$do_replace = true;
				}

				if ( $do_replace ) {
					$element = $this->make_placeholder_widget( $element, $plugin_name );
					continue;
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$element['elements'] = $this->replace_missing_widgets( $element['elements'], $detected_widgets, $registered_types );
			}
		}
		unset( $element );

		return $elements;
	}

	/**
	 * @param array $elements
	 * @return array
	 */
	public static function deep_copy_elements( array $elements ) {
		$copied = json_decode( wp_json_encode( $elements ), true );
		return is_array( $copied ) ? $copied : $elements;
	}

	/**
	 * @return string[]
	 */
	public static function get_registered_widget_types() {
		if ( class_exists( '\Elementor\Plugin' )
			&& isset( \Elementor\Plugin::instance()->widgets_manager )
			&& method_exists( \Elementor\Plugin::instance()->widgets_manager, 'get_widget_types' ) ) {
			$types = \Elementor\Plugin::instance()->widgets_manager->get_widget_types();
			if ( ! empty( $types ) ) {
				return array_keys( $types );
			}
		}
		return [];
	}

	/**
	 * @param array  $original
	 * @param string $plugin_name
	 * @return array
	 */
	public function make_placeholder_widget( array $original, $plugin_name = null ) {
		$line1 = $plugin_name
			? sprintf(
				/* translators: %s: required plugin name */
				esc_html__( 'This widget requires %s.', 'bosa-elementor-for-woocommerce' ),
				'<strong>' . esc_html( $plugin_name ) . '</strong>'
			)
			: esc_html__( 'This widget requires a plugin that is not installed or active.', 'bosa-elementor-for-woocommerce' );

		$html = '<div style="padding:20px;background:#f9f9f9;border:2px dashed #d5d5d5;border-radius:4px;text-align:center;">'
			. '<p style="margin:0 0 6px;font-size:13px;color:#555;">' . $line1 . '</p>'
			. '<p style="margin:0;font-size:12px;color:#999;">'
			. esc_html__( 'Install the plugin to display the full widget.', 'bosa-elementor-for-woocommerce' )
			. '</p>'
			. '</div>';

		return [
			'id'         => $original['id'] ?? '',
			'elType'     => 'widget',
			'widgetType' => 'html',
			'settings'   => [ 'html_code' => $html ],
			'elements'   => [],
		];
	}
}
