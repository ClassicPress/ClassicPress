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

if ( ! function_exists( 'map_deep' ) ) {
	/**
	 * Maps a function to all non-iterable elements of an array or an object.
	 *
	 * Compat for WordPress < 4.4.0.
	 *
	 * @since 0.7.0
	 *
	 * @param mixed    $value    The array, object, or scalar.
	 * @param callable $callback The function to map onto $value.
	 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
	 */
	function map_deep( $value, $callback ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = map_deep( $item, $callback );
			}
		} elseif ( is_object( $value ) ) {
			$object_vars = get_object_vars( $value );
			foreach ( $object_vars as $property_name => $property_value ) {
				$value->$property_name = map_deep( $property_value, $callback );
			}
		} else {
			$value = call_user_func( $callback, $value );
		}

		return $value;
	}
}
