<?php
/**
 * Extend core button block.
 *
 * @package wp_link_from_cf
 */

namespace UBC\CTLT\BLOCKS\EXTENSION\TEXT_FROM_CF;

add_filter( 'render_block', __NAMESPACE__ . '\\render_core_button_block_content', 10, 3 );


/**
 * Render the content of the core button block.
 *
 * This function is responsible for rendering the content of the core button block. It checks if the block is a core button block and if the CFToText_enable attribute is set to true. If so, it retrieves the meta value associated with the CFToText_key attribute and replaces the text within the anchor tag in the content.
 *
 * @param mixed $content The rendered block content.
 * @param mixed $block The block attributes.
 * @param mixed $instance The block instance.
 * @return string The modified block content.
 */
function render_core_button_block_content( $content, $block, $instance ) {

	if ( 'core/button' !== $block['blockName'] ) {
		return $content;
	}

	$post_id           = $instance->context['postId'];
	$cf_to_text_enable = isset( $block['attrs']['CFToText_enable'] ) ? boolval( $block['attrs']['CFToText_enable'] ) : false;
	$cf_to_text_key    = isset( $block['attrs']['CFToText_key'] ) ? sanitize_key( $block['attrs']['CFToText_key'] ) : false;
	$text              = '';

	if ( ! $cf_to_text_enable ) {
		return $content;
	}

	$meta = get_post_meta( $post_id, $cf_to_text_key, true );

	return preg_replace(
		'/>([^<]*)<\/a>/',
		'>' . $meta . '</a>',
		$content,
		1
	);
}
