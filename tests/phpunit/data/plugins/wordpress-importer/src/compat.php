<?php
/**
 * Implementation for WordPress functions missing in older WordPress versions.
 *
 * @package WordPress
 * @subpackage Importer
 */

if ( ! function_exists( 'wp_slash_strings_only' ) ) {
	/**
	 * Adds slashes to only string values in an array of values.
	 *
	 * Compat for WordPress < 5.3.0.
	 *
	 * @since 0.7.0
	 *
	 * @param mixed $value Scalar or array of scalars.
	 * @return mixed Slashes $value
	 */
	function wp_slash_strings_only( $value ) {
		return map_deep( $value, 'addslashes_strings_only' );
	}
}

if ( ! function_exists( 'addslashes_strings_only' ) ) {
	/**
	 * Adds slashes only if the provided value is a string.
	 *
	 * Compat for WordPress < 5.3.0.
	 *
	 * @since 0.7.0
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	function addslashes_strings_only( $value ) {
		return is_string( $value ) ? addslashes( $value ) : $value;
	}
}
