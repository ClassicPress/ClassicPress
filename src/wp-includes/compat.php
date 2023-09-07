<?php
/**
 * ClassicPress implementation for PHP functions either missing from older PHP versions or not included by default.
 *
 * @package PHP
 * @access private
 */

// If gettext isn't available.
if ( ! function_exists( '_' ) ) {
	function _( $message ) {
		return $message;
	}
}

/**
 * Returns whether PCRE/u (PCRE_UTF8 modifier) is available for use.
 *
 * @ignore
 * @since 4.2.2
 * @access private
 *
 * @param bool $set - Used for testing only
 *             null   : default - get PCRE/u capability
 *             false  : Used for testing - return false for future calls to this function
 *             'reset': Used for testing - restore default behavior of this function
 */
function _wp_can_use_pcre_u( $set = null ) {
	static $utf8_pcre = 'reset';

	if ( null !== $set ) {
		$utf8_pcre = $set;
	}

	if ( 'reset' === $utf8_pcre ) {
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- intentional error generated to detect PCRE/u support.
		$utf8_pcre = @preg_match( '/^./u', 'a' );
	}

	return $utf8_pcre;
}

if ( ! function_exists( 'mb_substr' ) ) :
	/**
	 * Compat function to mimic mb_substr().
	 *
	 * @ignore
	 * @since 3.2.0
	 *
	 * @see _mb_substr()
	 *
	 * @param string      $string   The string to extract the substring from.
	 * @param int         $start    Position to being extraction from in `$string`.
	 * @param int|null    $length   Optional. Maximum number of characters to extract from `$string`.
	 *                              Default null.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return string Extracted substring.
	 */
	function mb_substr( $string, $start, $length = null, $encoding = null ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
		return _mb_substr( $string, $start, $length, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_substr().
 *
 * Only understands UTF-8 and 8bit. All other character sets will be treated as 8bit.
 * For `$encoding === UTF-8`, the `$str` input is expected to be a valid UTF-8 byte
 * sequence. The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since 3.2.0
 *
 * @param string      $str      The string to extract the substring from.
 * @param int         $start    Position to being extraction from in `$str`.
 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
 *                              Default null.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return string Extracted substring.
 */
function _mb_substr( $str, $start, $length = null, $encoding = null ) {
	if ( null === $str ) {
		return '';
	}

	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset' );
	}

	/*
	 * The solution below works only for UTF-8, so in case of a different
	 * charset just use built-in substr().
	 */
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ), true ) ) {
		return is_null( $length ) ? substr( $str, $start ) : substr( $str, $start, $length );
	}

	if ( _wp_can_use_pcre_u() ) {
		// Use the regex unicode support to separate the UTF-8 characters into an array.
		preg_match_all( '/./us', $str, $match );
		$chars = is_null( $length ) ? array_slice( $match[0], $start ) : array_slice( $match[0], $start, $length );
		return implode( '', $chars );
	}

	$regex = '/(
		[\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

	// Start with 1 element instead of 0 since the first thing we do is pop.
	$chars = array( '' );

	do {
		// We had some string left over from the last round, but we counted it in that last round.
		array_pop( $chars );

		/*
		 * Split by UTF-8 character, limit to 1000 characters (last array element will contain
		 * the rest of the string).
		 */
		$pieces = preg_split( $regex, $str, 1000, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

		$chars = array_merge( $chars, $pieces );

		// If there's anything left over, repeat the loop.
	} while ( count( $pieces ) > 1 && $str = array_pop( $pieces ) );

	return implode( '', array_slice( $chars, $start, $length ) );
}

if ( ! function_exists( 'mb_strlen' ) ) :
	/**
	 * Compat function to mimic mb_strlen().
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @see _mb_strlen()
	 *
	 * @param string      $string   The string to retrieve the character length from.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return int String length of `$string`.
	 */
	function mb_strlen( $string, $encoding = null ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.stringFound
		return _mb_strlen( $string, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_strlen().
 *
 * Only understands UTF-8 and 8bit. All other character sets will be treated as 8bit.
 * For `$encoding === UTF-8`, the `$str` input is expected to be a valid UTF-8 byte
 * sequence. The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since 4.2.0
 *
 * @param string      $str      The string to retrieve the character length from.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return int String length of `$str`.
 */
function _mb_strlen( $str, $encoding = null ) {
	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset' );
	}

	/*
	 * The solution below works only for UTF-8, so in case of a different charset
	 * just use built-in strlen().
	 */
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ), true ) ) {
		return strlen( $str );
	}

	if ( _wp_can_use_pcre_u() ) {
		// Use the regex unicode support to separate the UTF-8 characters into an array.
		preg_match_all( '/./us', $str, $match );
		return count( $match[0] );
	}

	$regex = '/(?:
		[\x00-\x7F]                  # single-byte sequences   0xxxxxxx
		| [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
		| \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		| [\xE1-\xEC][\x80-\xBF]{2}
		| \xED[\x80-\x9F][\x80-\xBF]
		| [\xEE-\xEF][\x80-\xBF]{2}
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
		| [\xF1-\xF3][\x80-\xBF]{3}
		| \xF4[\x80-\x8F][\x80-\xBF]{2}
	)/x';

	// Start at 1 instead of 0 since the first thing we do is decrement.
	$count = 1;

	do {
		// We had some string left over from the last round, but we counted it in that last round.
		$count--;

		/*
		 * Split by UTF-8 character, limit to 1000 characters (last array element will contain
		 * the rest of the string).
		 */
		$pieces = preg_split( $regex, $str, 1000 );

		// Increment.
		$count += count( $pieces );

		// If there's anything left over, repeat the loop.
	} while ( $str = array_pop( $pieces ) );

	// Fencepost: preg_split() always returns one extra item in the array.
	return --$count;
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Polyfill for `str_contains()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if needle is
	 * contained in haystack.
	 *
	 * @since 5.9.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if `$needle` is in `$haystack`, otherwise false.
	 */
	function str_contains( $haystack, $needle ) {
		return ( '' === $needle || false !== strpos( $haystack, $needle ) );
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Polyfill for `str_starts_with()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if
	 * the haystack begins with needle.
	 *
	 * @since 5.9.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 * @return bool True if `$haystack` starts with `$needle`, otherwise false.
	 */
	function str_starts_with( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( $haystack, $needle );
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * Polyfill for `str_ends_with()` function added in PHP 8.0.
	 *
	 * Performs a case-sensitive check indicating if
	 * the haystack ends with needle.
	 *
	 * @since 5.9.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 * @return bool True if `$haystack` ends with `$needle`, otherwise false.
	 */
	function str_ends_with( $haystack, $needle ) {
		if ( '' === $haystack && '' !== $needle ) {
			return false;
		}

		$len = strlen( $needle );

		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}
}

// IMAGETYPE_WEBP constant is only defined in PHP 7.1 or later.
if ( ! defined( 'IMAGETYPE_WEBP' ) ) {
	define( 'IMAGETYPE_WEBP', 18 );
}

// IMG_WEBP constant is only defined in PHP 7.0.10 or later.
if ( ! defined( 'IMG_WEBP' ) ) {
	define( 'IMG_WEBP', IMAGETYPE_WEBP );
}
