<?php
/**
 * Plugin Name: ACF Checkbox Taxonomy Tree 
 * Plugin URI: https://produ.com/
 * Description: Plugin to add custom field type, to show taxonomy tree in checkbox format.
 * Version: 1.5.0
 * Author: PRODU & Gabriel Magallanes
 *
 * @package WordPress
 */

namespace Produ\ACF;

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! defined( __NAMESPACE__ . '\PATH' ) ) {
	define( __NAMESPACE__ . '\PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( __NAMESPACE__ . '\URL' ) ) {
	define( __NAMESPACE__ . '\URL', plugin_dir_url( __FILE__ ) );
}

/**
 * This is the main class of the plugin.
 */
class Fields {

	/**
	 * Has the class been instantiated?
	 *
	 * @var bool
	 */
	private static $instance = false;

	/**
	 * Add hooks here
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'include_custom_field_types' ) );
		add_action( 'rest_api_init', array( $this, 'add_endpoints' ) );
		add_filter( 'acf/fields/taxonomy/query', array( $this, 'customize_args_post_query' ), 10, 3 );
		add_action( 'acf/save_post', array( $this, 'handle_subtaxonomies' ) );
	}

	/**
	 * Instantiate the class
	 */
	public static function get_instance(): self {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Add Produ custom field types.
	 */
	public function include_custom_field_types() {
		if ( ! function_exists( 'acf_register_field_type' ) ) {
			return;
		}

		require_once namespace\PATH . 'includes/class-produ-acf-field-taxonomies.php';

		acf_register_field_type( 'produ_acf_field_taxonomies' );
	}

	/**
	 * Customize the taxonomy query so it just returns
	 * parent sections.
	 *
	 * @param array      $args The query args. See WP_Term_Query for available args.
	 * @param array      $field The field array containing all settings.
	 * @param int|string $post_id The current post ID being edited.
	 */
	public function customize_args_post_query( $args, $field, $post_id ) {
		if ( 'produCustomTaxonomyField' !== $field['type'] ) {
			return $args;
		}

		$args['parent'] = 0;
		return $args;
	}

	/**
	 * Add custom endpoints to dispatch
	 * ajax queries.
	 */
	public function add_endpoints() {
		// TODO: Add sanitize and permission callbacks.
		// See: https://github.com/Victor3790/portefy/blob/master/public/classes/class_portefy_endpoints.php.
		register_rest_route(
			'produ/v1',
			'/taxonomy/(?P<id>\d+)',
			array(
				'methods'  => 'GET',
				'callback' => array( $this, 'get_taxonomies' ),
				'permission_callback' => '__return_true',
			),
		);
	}

	/**
	 * Returns the sub taxonomies of the
	 * parent taxnomoy identified by id
	 * as a json string.
	 *
	 * @param WP_REST_Request $request The rest rout request.
	 */
	public function get_taxonomies( $request ) {
		$id = $request->get_param( 'id' );
		$id = filter_var( $id, FILTER_VALIDATE_INT );

		// TODO: These queries should be able to get other taxonomies.
		$parent_category = get_categories(
			array(
				'include'    => $id,
				'fields'     => 'id=>name',
				'hide_empty' => false,
			)
		);

		$child_categories = get_categories(
			array(
				'parent'     => $id,
				'fields'     => 'id=>name',
				'hide_empty' => false,
			)
		);

		$result = array(
			'id'       => key( $parent_category ),
			'text'     => current( $parent_category ),
			'children' => array(),
		);

		foreach ( $child_categories as $id => $name ) {

			$child = array(
				'id'   => $id,
				'text' => $name,
			);

			array_push( $result['children'], $child );

		}

		$json_result = wp_json_encode( $result );

		rest_send_cors_headers( '200' );
		echo wp_kses( $json_result, 'post' );
	}

	/**
	 * Handle sub taxonomies.
	 *
	 * @param int|string $post_id The ID of the post being edited.
	 */
	public function handle_subtaxonomies( $post_id ) {

		if ( ! isset( $_POST['produ-sub-categories'] ) || ! isset( $_POST['produ-sub-categories-nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['produ-sub-categories-nonce'] ) );

		wp_verify_nonce( $nonce, 'handle-sub-taxonomies-' . $post_id );

		$sub_categories = sanitize_text_field( wp_unslash( $_POST['produ-sub-categories'] ) );
		update_post_meta( $post_id, 'produ-sub-categories', $sub_categories );

		$array_sub_categories = json_decode( stripslashes( $sub_categories ), true );

		$valid_categories = array();

		if(is_null( $array_sub_categories)){ $array_sub_categories=array();}

		foreach ( $array_sub_categories as $key => $subtaxonomies ) {
			$array_key = explode( '_', $key );

			$valid_categories[] = (int) $array_key[1];

			foreach ( $subtaxonomies as $subtaxonomy ) {
				$valid_categories[] = (int) $subtaxonomy;
			}
		}

		wp_set_post_categories( $post_id, $valid_categories );
	}
}

$produ_acf_fields = Fields::get_instance();
