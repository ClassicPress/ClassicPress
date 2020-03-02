<?php
/**
 * Loads the ClassicPress environment and template.
 *
 * @package ClassicPress
 */

if ( !isset($wp_did_header) ) {

	$wp_did_header = true;

	// Load the ClassicPress library.
	require_once( dirname(__FILE__) . '/wp-load.php' );

	// Set up the ClassicPress query.
	wp();

	// Load the theme template.
	require_once( ABSPATH . WPINC . '/template-loader.php' );

}
