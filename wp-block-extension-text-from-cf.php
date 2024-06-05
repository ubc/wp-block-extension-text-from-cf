<?php
/**
 *
 * Plugin Name:       WP Block Extension - Text from CF
 * Description:       Allow blocks text to pull value exists in custom field.
 * Version:           1.0
 * Author:            Kelvin Xu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-text-from-cf
 *
 * @package wp_text_from_cf
 */

namespace UBC\CTLT\BLOCKS\EXTENSION\TEXT_FROM_CF;

define( 'TEXT_FROM_CF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TEXT_FROM_CF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once TEXT_FROM_CF_PLUGIN_DIR . 'src/core-button/core-button-extend.php';

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\enqueue_assets' );

/**
 * Enqueue block assets.
 *
 * @return void
 */
function enqueue_assets() {

	wp_enqueue_script(
		'wp-block-extension-text-from-cf-js',
		TEXT_FROM_CF_PLUGIN_URL . 'build/script.js',
		array( 'jquery' ),
		filemtime( TEXT_FROM_CF_PLUGIN_DIR . 'build/script.js' ),
		true
	);

	wp_localize_script(
		'wp-block-extension-text-from-cf-js',
		'wp_text_from_cf',
		array(
			'nonce' => wp_create_nonce( 'wp_text_from_cf' ),
		)
	);
}//end enqueue_assets()


add_action( 'wp_ajax_text_to_cf_get_custom_field_value', __NAMESPACE__ . '\\text_to_cf_get_custom_field_value' );

/**
 * Ajax request handler to return the post meta value based on post id and meta key.
 *
 * @return void
 */
function text_to_cf_get_custom_field_value() {

	// phpcs:ignore
	wp_verify_nonce( $_POST['nonce'], 'custom_field_block_ajax' );

	if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['meta_key'] ) ) {
		wp_send_json_error( 'Missing required informations for the request.' );
	}

	$post_id  = intval( $_POST['post_id'] );
	$meta_key = sanitize_text_field( wp_unslash( $_POST['meta_key'] ) );

	$metadata = get_metadata( 'post', $post_id, $meta_key, true );

	if ( false === $metadata ) {
		wp_send_json_error();
	}

	if ( '' === trim( $meta_key ) ) {
		wp_send_json_error();
	}

	wp_send_json_success( $metadata );
}

add_action( 'wp_ajax_wp_text_from_cf_get_meta_keys', __NAMESPACE__ . '\\get_meta_keys' );

/**
 * Ajax request handler to return the list of meta keys from the post meta table.
 *
 * @return void
 */
function get_meta_keys() {
	global $wpdb;

	// phpcs:ignore
	wp_verify_nonce( $_POST['nonce'], 'wp_text_from_cf' );

	$keys = get_transient( 'wp_text_from_cf' );
	if ( false !== $keys ) {
		wp_send_json_success( $keys );
	}

	$keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_key
			FROM $wpdb->postmeta
			WHERE meta_key NOT BETWEEN '_' AND '_z'
			HAVING meta_key NOT LIKE %s
			ORDER BY meta_key",
			$wpdb->esc_like( '_' ) . '%'
		)
	);

	set_transient( 'wp_text_from_cf', $keys, HOUR_IN_SECONDS );

	wp_send_json_success( $keys );
}//end get_meta_keys()

add_action( 'updated_post_meta', __NAMESPACE__ . '\\reset_metakeys_transient' );

/**
 * Delete `wp_metadata_filter_get_keys` transient when any of the post metas is updated.
 */
function reset_metakeys_transient() {
	if ( false !== get_transient( 'wp_text_from_cf' ) ) {
		delete_transient( 'wp_text_from_cf' );
	}
}//end reset_metakeys_transient()
