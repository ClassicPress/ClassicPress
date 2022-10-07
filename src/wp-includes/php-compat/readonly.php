<?php
/**
 * Conditionally declares a `readonly()` function, which was renamed
 * to `wp_readonly()` in WordPress WP-5.9.0.
 *
 * In order to avoid PHP parser errors, this function was extracted
 * to this separate file and is only included conditionally on PHP 8.1.
 *
 * Including this file on PHP >= 8.1 results in a fatal error.
 *
 * @package WordPress
 * @since WP-5.9.0
 */

/**
 * Outputs the HTML readonly attribute.
 *
 * Compares the first two arguments and if identical marks as readonly
 *
 * This function is deprecated, and cannot be used on PHP >= 8.1.
 *
 * @since WP-4.9.0
 * @deprecated WP-5.9.0 Use `wp_readonly` introduced in WP-5.9.0.
 *
 * @see wp_readonly()
 *
 * @param mixed $readonly One of the values to compare
 * @param mixed $current  (true) The other value to compare if not just true
 * @param bool  $echo     Whether to echo or just return the string
 * @return string HTML attribute or empty string
 */
function readonly( $readonly, $current = true, $echo = true ) {
	_deprecated_function( __FUNCTION__, 'WP-5.9.0', 'wp_readonly()' );
	return wp_readonly( $readonly, $current, $echo );
}
