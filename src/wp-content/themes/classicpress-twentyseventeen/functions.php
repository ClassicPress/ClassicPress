<?php
/**
 * ClassicPress TwentySeventeen functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package ClassicPress
 * @subpackage ClassicPress_TwentySeventeen
 * @since 1.0.0
 */

function cp2017_setup() {
	load_theme_textdomain( 'classicpress-twentyseventeen' );
}
add_action( 'after_setup_theme', 'cp2017_setup' );

// Enqueue parent/child themes styles with cachebusting for child theme styles built in
add_action( 'wp_enqueue_scripts', 'cp2017_enqueue_styles' );

function cp2017_enqueue_styles() {
	$parent_style = 'twentyseventeen-style';
	wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );

	wp_enqueue_style(
		'classicpress-twentyseventeen',
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		classicpress_asset_version( 'style', 'classicpress-twentyseventeen' )
	);
}
