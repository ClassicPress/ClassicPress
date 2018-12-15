<?php
/**
 * CP2017 Child functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package ClassicPress
 * @subpackage CP2017_Child
 * @since 1.0.0
 */


add_action( 'after_setup_theme', 'cp2017_child_setup' );

function cp2017_child_setup() {

	load_theme_textdomain( 'cp2017-child' );

}

// Enqueue parent/child themes styles with cachebusting for child theme styles built in
add_action( 'wp_enqueue_scripts', 'cp2017_child_enqueue_styles' );

function cp2017_child_enqueue_styles() {

    $parent_style = 'twentyseventeen-style';

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'cp2017-child',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );

}
