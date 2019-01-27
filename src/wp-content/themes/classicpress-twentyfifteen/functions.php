<?php
/**
 * ClassicPress TwentyFifteen functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package ClassicPress
 * @subpackage ClassicPress_TwentyFifteen
 * @since 1.0.0
 */

function cp2015_setup() {
	load_theme_textdomain( 'classicpress-twentyfifteen' );
}
add_action( 'after_setup_theme', 'cp2015_setup' );

// Enqueue parent/child themes styles with cachebusting for child theme styles built in
add_action( 'wp_enqueue_scripts', 'cp2015_enqueue_styles' );

function cp2015_enqueue_styles() {
	$parent_style = 'twentyfifteen-style';
	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );

	wp_enqueue_style(
		'classicpress-twentyfifteen',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		classicpress_asset_version( 'style', 'classicpress-twentyfifteen' )
	);
}
