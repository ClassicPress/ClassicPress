<?php
/**
 * Feed API
 *
 * @package ClassicPress
 * @subpackage Feed
 */

_deprecated_file( basename( __FILE__ ), 'WP-4.7.0', 'fetch_feed()' );

if ( ! class_exists( 'SimplePie', false ) ) {
	require_once ABSPATH . WPINC . '/class-simplepie.php';
}

<<<<<<< HEAD
require_once( ABSPATH . WPINC . '/class-wp-feed-cache.php' );
require_once( ABSPATH . WPINC . '/class-wp-feed-cache-transient.php' );
require_once( ABSPATH . WPINC . '/class-wp-simplepie-file.php' );
require_once( ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php' );
=======
require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.
