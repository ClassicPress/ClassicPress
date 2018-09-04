<?php
/**
 * Front to the ClassicPress application. This file doesn't do anything, but loads
 * wp-blog-header.php which does and tells ClassicPress to load the theme.
 *
 * @package ClassicPress
 */

/**
 * Tells ClassicPress to load the ClassicPress theme and output it.
 *
 * @var bool
 */
define('WP_USE_THEMES', true);

/** Loads the ClassicPress Environment and Template */
require( dirname( __FILE__ ) . '/wp-blog-header.php' );
