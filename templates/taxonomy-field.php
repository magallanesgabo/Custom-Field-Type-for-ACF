<?php
/**
 * Template to echo the taxonomy field.
 *
 * @package WordPress.
 */

?>
<div <?php echo esc_attr( $attributes ); ?>>
	<?php acf_render_field( $field ); ?>
</div>
<div id="produ-sub-sections" style="display: flex;flex-direction: column;">
	<input type="hidden" name="produ-sub-categories" value='<?php echo esc_attr( $sub_categories ); ?>'>
	<?php wp_nonce_field( 'handle-sub-taxonomies-' . $post_id, 'produ-sub-categories-nonce' ); ?>
</div>
