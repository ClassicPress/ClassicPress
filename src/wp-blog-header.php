<?php
/**
 * Loads the ClassicPress environment and template.
 *
 * @package ClassicPress
 */

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

<<<<<<< HEAD
	// Load the ClassicPress library.
	require_once( dirname(__FILE__) . '/wp-load.php' );
=======
	// Load the WordPress library.
	require_once __DIR__ . '/wp-load.php';
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.

	// Set up the ClassicPress query.
	wp();

	// Load the theme template.
	require_once ABSPATH . WPINC . '/template-loader.php';

}
