<?php

/**
 * Defines the custom field type class.
 *
 * @package WordPress
 */

if (!defined('ABSPATH')) {
	exit;
}

use Produ\Master\Template;


class Produ_ACF_Field_Taxonomies extends \acf_field
{

	public $show_in_rest = true;
	private $env;

	public function __construct()
	{
		$this->name = 'produCustomTaxonomyField';
		$this->label = __('Checkbox taxonomy tree', 'produ');
		$this->category = 'Produ';
		$this->defaults      = array(
			'taxonomy'             => 'category',
			'field_type'           => 'select',
			'multiple'             => 1,
			'allow_null'           => 0,
		);
		$this->env = array(
			'url'     => site_url(str_replace(ABSPATH, '', __DIR__)), 
			'version' => '1.0',
		);

		parent::__construct();
	}

	/**
	 * Returns the Term's title displayed in the field UI.
	 *
	 * @date    1/11/2013
	 * @since   5.0.0
	 *
	 * @param   WP_Term $term    The term object.
	 * @param   array   $field   The field settings.
	 * @param   mixed   $post_id The post_id being edited.
	 * @return  string
	 */
	function get_term_title($term, $field, $post_id = 0)
	{
		$title = acf_get_term_title($term);

		// Default $post_id to current post being edited.
		$post_id = $post_id ? $post_id : acf_get_form_data('post_id');

		/**
		 * Filters the term title.
		 *
		 * @date    1/11/2013
		 * @since   5.0.0
		 *
		 * @param   string $title The term title.
		 * @param   WP_Term $term The term object.
		 * @param   array $field The field settings.
		 * @param   (int|string) $post_id The post_id being edited.
		 */
		return apply_filters('acf/fields/taxonomy/result', $title, $term, $field, $post_id);
	}

	/**
	 * This function will return an array of terms for a given field value
	 *
	 * @type    function
	 * @date    13/06/2014
	 * @since   5.0.0
	 *
	 * @param   $value (array)
	 * @return  $value
	 */
	function get_terms($value, $taxonomy = 'category')
	{

		// load terms in 1 query to save multiple DB calls from following code
		if (count($value) > 1) {
			$terms = acf_get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'include'    => $value,
					'hide_empty' => false,
				)
			);
		}

		// update value to include $post
		foreach (array_keys($value) as $i) {
			$value[$i] = get_term($value[$i], $taxonomy);
		}

		// filter out null values
		$value = array_filter($value);

		// return
		return $value;
	}

	/**
	 * Settings to display when users configure a field of this type.
	 *
	 * These settings appear on the ACF “Edit Field Group” admin page when
	 * setting up the field.
	 *
	 * @param array $field The field to render.
	 * @return void
	 */
	public function render_field_settings($field)
	{

		// default_value.
		acf_render_field_setting(
			$field,
			array(
				'label'        => __('Taxonomy', 'acf'),
				'instructions' => __('Select the taxonomy to be displayed', 'acf'),
				'type'         => 'select',
				'name'         => 'taxonomy',
				'choices'      => acf_get_taxonomy_labels(),
			)
		);
	}

	/**
	 * HTML content to show when a publisher edits the field on the edit screen.
	 *
	 * @param array $field The field settings and values.
	 * @return void
	 */
	public function render_field( $field ) {

		global $post;

		$field['type'] = 'select';
		$field['multiple'] = 1;
		$field['ui']       = 1;
		$field['ajax']     = 1;
		$field['choices']  = array();

		if ( 1 === $field['required'] ) {
			$field['allow_null'] = 1;
		} else {
			$field['allow_null'] = 0;
		}

		$div = array(
			'class'           => 'acf-taxonomy-field',
			'data-save'       => 1,
			'data-ftype'      => 'multi-select',
			'data-taxonomy'   => 'category',
			'data-allow_null' => 0,
		);

		$params = array(
			'attributes'     => acf_esc_attrs( $div ),
			'field'          => $field,
			'post_id'        => $post->ID,
			'sub_categories' => '',
		);

		if ( ! empty( $field['value'] ) ) {
			$terms = $this->get_terms( $field['value'], $field['taxonomy'] );

			if ( ! empty( $terms ) ) {
				foreach ( array_keys( $terms ) as $i ) {
					$term = acf_extract_var( $terms, $i );
					$field['choices'][ $term->term_id ] = $this->get_term_title( $term, $field );
				}

				$params['sub_categories'] = get_post_meta( $post->ID, 'produ-sub-categories', true );
				$params['field'] = $field;
			}
		}

		$file     = Produ\ACF\PATH . 'templates/taxonomy-field.php';
		$template = new Template();
		$html     = $template->load( $file, $params );

		echo $html;
	}

	/**
	 * Enqueues CSS and JavaScript needed by HTML in the render_field() method.
	 *
	 * Callback for admin_enqueue_script.
	 *
	 * @return void
	 */
	public function input_admin_enqueue_scripts()
	{
		$url     = trailingslashit($this->env['url']);
		$version = $this->env['version'];

		wp_enqueue_script(
			'produCustomTaxonomyField_general',
			Produ\ACF\URL . 'assets/taxonomy.js',
			array('acf-input'),
			$version,
			true
		);

		wp_enqueue_script(
			'produCustomTaxonomyField_jsTree_script',
			Produ\ACF\URL . 'assets/jsTree/jstree.min.js',
			array('jquery'),
			$version,
			true
		);

		wp_enqueue_style(
			'produCustomTaxonomyField_style',
			Produ\ACF\URL . 'assets/taxonomy-styles.css',
			array(),
			$version
		);

		wp_enqueue_style(
			'produCustomTaxonomyField_jsTree_style',
			Produ\ACF\URL . 'assets/jsTree/themes/default/style.min.css',
			array(),
			$version
		);

		wp_add_inline_script(
			'produCustomTaxonomyField_general',
			'const PRODU_DATA = ' .
				wp_json_encode(
					array('tax_endpoint' => get_rest_url(null, '/produ/v1/taxonomy/'))
				),
			'before'
		);
	}
}
