<?php
/**
 * ClassicPress implementation for PHP functions either missing from older PHP versions or not included by default.
 *
 * @package PHP
 * @access private
 */

// If gettext isn't available
if ( !function_exists('_') ) {
	function _($string) {
		return $string;
	}
}

/**
 * Returns whether PCRE/u (PCRE_UTF8 modifier) is available for use.
 *
 * @ignore
 * @since WP-4.2.2
 * @access private
 *
 * @staticvar string $utf8_pcre
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
		$utf8_pcre = @preg_match( '/^./u', 'a' );
	}

	return $utf8_pcre;
}

if ( ! function_exists( 'mb_substr' ) ) :
	/**
	 * Compat function to mimic mb_substr().
	 *
	 * @ignore
	 * @since WP-3.2.0
	 *
	 * @see _mb_substr()
	 *
	 * @param string      $str      The string to extract the substring from.
	 * @param int         $start    Position to being extraction from in `$str`.
	 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
	 *                              Default null.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return string Extracted substring.
	 */
	function mb_substr( $str, $start, $length = null, $encoding = null ) {
		return _mb_substr( $str, $start, $length, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_substr().
 *
 * Only understands UTF-8 and 8bit.  All other character sets will be treated as 8bit.
 * For $encoding === UTF-8, the $str input is expected to be a valid UTF-8 byte sequence.
 * The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since WP-3.2.0
 *
 * @param string      $str      The string to extract the substring from.
 * @param int         $start    Position to being extraction from in `$str`.
 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
 *                              Default null.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return string Extracted substring.
 */
function _mb_substr( $str, $start, $length = null, $encoding = null ) {
	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset' );
	}

	/*
	 * The solution below works only for UTF-8, so in case of a different
	 * charset just use built-in substr().
	 */
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) ) {
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

	return join( '', array_slice( $chars, $start, $length ) );
}

if ( ! function_exists( 'mb_strlen' ) ) :
	/**
	 * Compat function to mimic mb_strlen().
	 *
	 * @ignore
	 * @since WP-4.2.0
	 *
	 * @see _mb_strlen()
	 *
	 * @param string      $str      The string to retrieve the character length from.
	 * @param string|null $encoding Optional. Character encoding to use. Default null.
	 * @return int String length of `$str`.
	 */
	function mb_strlen( $str, $encoding = null ) {
		return _mb_strlen( $str, $encoding );
	}
endif;

/**
 * Internal compat function to mimic mb_strlen().
 *
 * Only understands UTF-8 and 8bit.  All other character sets will be treated as 8bit.
 * For $encoding === UTF-8, the `$str` input is expected to be a valid UTF-8 byte
 * sequence. The behavior of this function for invalid inputs is undefined.
 *
 * @ignore
 * @since WP-4.2.0
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
	if ( ! in_array( $encoding, array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' ) ) ) {
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

if ( ! function_exists( 'hash_hmac' ) ) :
	/**
	 * Compat function to mimic hash_hmac().
	 *
	 * The Hash extension is bundled with PHP by default since PHP 5.1.2.
	 * However, the extension may be explicitly disabled on select servers.
	 * As of PHP 7.4.0, the Hash extension is a core PHP extension and can no
	 * longer be disabled.
	 * I.e. when PHP 7.4.0 becomes the minimum requirement, this polyfill
	 * and the associated `_hash_hmac()` function can be safely removed.
	 *
	 * @ignore
	 * @since WP-3.2.0
	 *
	 * @see _hash_hmac()
	 *
	 * @param string $algo       Hash algorithm. Accepts 'md5' or 'sha1'.
	 * @param string $data       Data to be hashed.
	 * @param string $key        Secret key to use for generating the hash.
	 * @param bool   $raw_output Optional. Whether to output raw binary data (true),
	 *                           or lowercase hexits (false). Default false.
	 * @return string|false The hash in output determined by `$raw_output`. False if `$algo`
	 *                      is unknown or invalid.
	 */
	function hash_hmac( $algo, $data, $key, $raw_output = false ) {
		return _hash_hmac( $algo, $data, $key, $raw_output );
	}
endif;

/**
 * Internal compat function to mimic hash_hmac().
 *
 * @ignore
 * @since WP-3.2.0
 *
 * @param string $algo       Hash algorithm. Accepts 'md5' or 'sha1'.
 * @param string $data       Data to be hashed.
 * @param string $key        Secret key to use for generating the hash.
 * @param bool   $raw_output Optional. Whether to output raw binary data (true),
 *                           or lowercase hexits (false). Default false.
 * @return string|false The hash in output determined by `$raw_output`. False if `$algo`
 *                      is unknown or invalid.
 */
function _hash_hmac($algo, $data, $key, $raw_output = false) {
	$packs = array('md5' => 'H32', 'sha1' => 'H40');

	if ( !isset($packs[$algo]) )
		return false;

	$pack = $packs[$algo];

	if (strlen($key) > 64)
		$key = pack($pack, $algo($key));

	$key = str_pad($key, 64, chr(0));

	$ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
	$opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));

	$hmac = $algo($opad . pack($pack, $algo($ipad . $data)));

	if ( $raw_output )
		return pack( $pack, $hmac );
	return $hmac;
}

if ( !function_exists('json_encode') ) {
	function json_encode( $string ) {
		global $wp_json;

		if ( ! ( $wp_json instanceof Services_JSON ) ) {
			require_once( ABSPATH . WPINC . '/class-json.php' );
			$wp_json = new Services_JSON();
		}

		return $wp_json->encodeUnsafe( $string );
	}
}

if ( !function_exists('json_decode') ) {
	/**
	 * @global Services_JSON $wp_json
	 * @param string $string
	 * @param bool   $assoc_array
	 * @return object|array
	 */
	function json_decode( $string, $assoc_array = false ) {
		global $wp_json;

		if ( ! ($wp_json instanceof Services_JSON ) ) {
			require_once( ABSPATH . WPINC . '/class-json.php' );
			$wp_json = new Services_JSON();
		}

		$res = $wp_json->decode( $string );
		if ( $assoc_array )
			$res = _json_decode_object_helper( $res );
		return $res;
	}

	/**
	 * @param object $data
	 * @return array
	 */
	function _json_decode_object_helper($data) {
		if ( is_object($data) )
			$data = get_object_vars($data);
		return is_array($data) ? array_map(__FUNCTION__, $data) : $data;
	}
}

if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Timing attack safe string comparison
	 *
	 * Compares two strings using the same time whether they're equal or not.
	 *
	 * Note: It can leak the length of a string when arguments of differing length are supplied.
	 *
	 * This function was added in PHP 5.6.
	 * However, the Hash extension may be explicitly disabled on select servers.
	 * As of PHP 7.4.0, the Hash extension is a core PHP extension and can no
	 * longer be disabled.
	 * I.e. when PHP 7.4.0 becomes the minimum requirement, this polyfill
	 * can be safely removed.
	 *
	 * @since WP-3.9.2
	 *
	 * @param string $a Expected string.
	 * @param string $b Actual, user supplied, string.
	 * @return bool Whether strings are equal.
	 */
	function hash_equals( $a, $b ) {
		$a_length = strlen( $a );
		if ( $a_length !== strlen( $b ) ) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return $result === 0;
	}
endif;

// JSON_PRETTY_PRINT was introduced in PHP 5.4
// Defined here to prevent a notice when using it with wp_json_encode()
if ( ! defined( 'JSON_PRETTY_PRINT' ) ) {
	define( 'JSON_PRETTY_PRINT', 128 );
}

if ( ! function_exists( 'json_last_error_msg' ) ) :
	/**
	 * Retrieves the error string of the last json_encode() or json_decode() call.
	 *
	 * @since WP-4.4.0
	 *
	 * @internal This is a compatibility function for PHP <5.5
	 *
	 * @return bool|string Returns the error message on success, "No Error" if no error has occurred,
	 *                     or false on failure.
	 */
	function json_last_error_msg() {
		// See https://core.trac.wordpress.org/ticket/27799.
		if ( ! function_exists( 'json_last_error' ) ) {
			return false;
		}

		$last_error_code = json_last_error();

		// Just in case JSON_ERROR_NONE is not defined.
		$error_code_none = defined( 'JSON_ERROR_NONE' ) ? JSON_ERROR_NONE : 0;

		switch ( true ) {
			case $last_error_code === $error_code_none:
				return 'No error';

			case defined( 'JSON_ERROR_DEPTH' ) && JSON_ERROR_DEPTH === $last_error_code:
				return 'Maximum stack depth exceeded';

			case defined( 'JSON_ERROR_STATE_MISMATCH' ) && JSON_ERROR_STATE_MISMATCH === $last_error_code:
				return 'State mismatch (invalid or malformed JSON)';

			case defined( 'JSON_ERROR_CTRL_CHAR' ) && JSON_ERROR_CTRL_CHAR === $last_error_code:
				return 'Control character error, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_SYNTAX' ) && JSON_ERROR_SYNTAX === $last_error_code:
				return 'Syntax error';

			case defined( 'JSON_ERROR_UTF8' ) && JSON_ERROR_UTF8 === $last_error_code:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';

			case defined( 'JSON_ERROR_RECURSION' ) && JSON_ERROR_RECURSION === $last_error_code:
				return 'Recursion detected';

			case defined( 'JSON_ERROR_INF_OR_NAN' ) && JSON_ERROR_INF_OR_NAN === $last_error_code:
				return 'Inf and NaN cannot be JSON encoded';

			case defined( 'JSON_ERROR_UNSUPPORTED_TYPE' ) && JSON_ERROR_UNSUPPORTED_TYPE === $last_error_code:
				return 'Type is not supported';

			default:
				return 'An unknown error occurred';
		}
	}
endif;

if ( ! interface_exists( 'JsonSerializable' ) ) {
	define( 'WP_JSON_SERIALIZE_COMPATIBLE', true );
	/**
	 * JsonSerializable interface.
	 *
	 * Compatibility shim for PHP <5.4
	 *
	 * @link https://secure.php.net/jsonserializable
	 *
	 * @since WP-4.4.0
	 */
	interface JsonSerializable {
		public function jsonSerialize();
	}
}

// random_int was introduced in PHP 7.0
if ( ! function_exists( 'random_int' ) ) {
	require ABSPATH . WPINC . '/random_compat/random.php';
}

if ( ! function_exists( 'is_countable' ) ) {
	/**
	 * Polyfill for is_countable() function added in PHP 7.3.
	 *
	 * Verify that the content of a variable is an array or an object
	 * implementing the Countable interface.
	 *
	 * @since WP-4.9.6
	 *
	 * @param mixed $var The value to check.
	 *
	 * @return bool True if `$var` is countable, false otherwise.
	 */
	function is_countable( $var ) {
		return ( is_array( $var )
			|| $var instanceof Countable
			|| $var instanceof SimpleXMLElement
			|| $var instanceof ResourceBundle
		);
	}
}

if ( ! function_exists( 'is_iterable' ) ) {
	/**
	 * Polyfill for is_iterable() function added in PHP 7.1.
	 *
	 * Verify that the content of a variable is an array or an object
	 * implementing the Traversable interface.
	 *
	 * @since WP-4.9.6
	 *
	 * @param mixed $var The value to check.
	 *
	 * @return bool True if `$var` is iterable, false otherwise.
	 */
	function is_iterable( $var ) {
		return ( is_array( $var ) || $var instanceof Traversable );
	}
}
