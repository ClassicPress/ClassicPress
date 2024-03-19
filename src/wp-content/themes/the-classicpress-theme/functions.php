<?php
/**
 * Susty WP functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Susty
 */

if ( ! function_exists( 'susty_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function susty_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on The ClassicPress Theme, use a find and replace
		 * to change 'the-classicpress-theme' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'the-classicpress-theme', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in two locations.
		register_nav_menus(
			array(
				'main-menu'   => esc_html__( 'MainMenu', 'the-classicpress-theme' ),
				'footer-menu' => esc_html__( 'FooterMenu', 'the-classicpress-theme' ),
			)
		);

		// Set up the WordPress core custom background feature.
		add_theme_support(
			'custom-background',
			apply_filters(
				'susty_custom_background_args',
				array(
					'default-color' => 'fffefc',
					'default-image' => '',
				)
			)
		);

		// Add theme support for selective refresh for widgets.
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 50,
				'width'       => 250,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);
	}
endif;
add_action( 'after_setup_theme', 'susty_setup' );

/**
 * Enqueue scripts and styles.
 */
function susty_scripts() {
	wp_enqueue_style( 'susty-style', get_stylesheet_uri() );

	wp_deregister_script( 'wp-embed' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'susty_scripts' );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

function susty_nav_rewrite_rule() {
	add_rewrite_rule( 'menu', 'index.php?menu=true', 'top' );
}

add_action( 'init', 'susty_nav_rewrite_rule' );

function susty_register_query_var( $vars ) {
	$vars[] = 'menu';

	return $vars;
}
add_filter( 'query_vars', 'susty_register_query_var' );

add_filter(
	'template_include',
	function ( $path ) {
		if ( get_query_var( 'menu' ) ) {
			return get_template_directory() . '/menu.php';
		}
		return $path;
	}
);

// Remove dashicons in frontend for unauthenticated users
function susty_dequeue_dashicons() {
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'susty_dequeue_dashicons' );

/**
 * Stylesheet version (cache buster)
 */
function cp_susty_get_asset_version() {
	return '20240227';
}

/**
 * Enqueue scripts and styles
 */
function cp_susty_enqueue_assets() {
	/* Make menu more accessible */
	wp_enqueue_script(
		'cp-menu-resize',
		get_template_directory_uri() . '/js/menu-resize.js',
		null,
		cp_susty_get_asset_version(),
	);
}
add_action( 'wp_enqueue_scripts', 'cp_susty_enqueue_assets' );

// Add custom stylesheet to TinyMCE editor
function cp_tiny_css( $wp ) {
	$wp .= ',' . get_template_directory_uri() . '/css/editor-style.css';
	return $wp;
}
add_filter( 'mce_css', 'cp_tiny_css' );


/* Add widgets to blog sidebar */
if ( function_exists( 'register_sidebar' ) ) {
	register_sidebar(
		array(
			'id'            => 'blog-sidebar',
			'name'          => 'Blog Sidebar',
			'before_widget' => '<div class="widget-container">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);
	register_sidebar(
		array(
			'id'            => 'main-sidebar',
			'name'          => 'Main Sidebar',
			'before_widget' => '<div class="widget-container">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		)
	);
}

/**
 * Modify Featured Image Text
 */
function filter_featured_image_admin_text( $content, $post_id, $thumbnail_id ) {
	$help_text = '<p><i>' . __( 'Ideal size is 800 x 471 pixels.', 'the-classicpress-theme' ) . '</i></p>';
	return $help_text . $content;
}
add_filter( 'admin_post_thumbnail_html', 'filter_featured_image_admin_text', 10, 3 );

// Remove empty paragraph tags
function cp_remove_empty_p( $content ) {
	$content = force_balance_tags( $content );
	return preg_replace( '#<p>\s*+(<br\s*/*>)?\s*</p>#i', '', $content );
}
add_filter( 'the_content', 'cp_remove_empty_p', 20, 1 );

// Add excerpts to pages
add_post_type_support( 'page', 'excerpt' );


/**
 * Simplify blog detection
 */
function is_blog() {
	return ( is_archive() || is_author() || is_category() || is_home() || is_tag() ) && 'post' == get_post_type();
}


/**
 * Set our own version string for the theme's stylesheet
 */
function cp_susty_override_style_css_version( $version, $type, $handle ) {
	if ( $type !== 'style' || $handle !== 'susty-style' ) {
		return $version;
	}
	return cp_susty_get_asset_version();
}
add_filter( 'classicpress_asset_version', 'cp_susty_override_style_css_version', 10, 3 );


/**
 * Add the page slug as a class to the <body>
 * Gives greater flexibility for styling
 */
function cp_add_page_slug_body_class( $classes ) {
	global $post;
	if ( isset( $post ) ) {
		$classes[] = 'page-' . $post->post_name;
	}
	return $classes;
}
add_filter( 'body_class', 'cp_add_page_slug_body_class' );
