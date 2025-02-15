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
		/**
		 * Set the content width in pixels, based on the theme's design and stylesheet.
		 *
		 * Priority 0 to make it available to lower priority callbacks.
		 *
		 * @global int $content_width
		 */
		global $content_width;
		if ( ! isset( $content_width ) )
		$content_width = 850;

		/**
		 * Add default posts and comments RSS feed links to head.
		 */	
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

		/**
		 * This theme uses wp_nav_menu() in one location.
		 */
		register_nav_menus(
			array(
				'main-menu'   => esc_html__( 'Main Menu', 'the-classicpress-theme' ),
			)
		);

		/**
		 * Add support for core custom background.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/custom-backgrounds/
		 */
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

		/**
		 * Add support for selective refresh for widgets.
		 */
		add_theme_support( 'customize-selective-refresh-widgets' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/custom-logo/
		 */
		add_theme_support(
			'custom-logo',
			array(
				'height' => 50,
				'width' => 250,
				'flex-width' => true,
				'flex-height' => true,
			)
		);

		/**
		 * Add support for core custom header.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/custom-headers/
		 */
		add_theme_support(
			'custom-header',
			array(
				'height' => 360,
				'width' => 640,
				'flex-width' => true,
				'flex-height' => true,
				'default-image' => get_template_directory_uri() . '/images/classicpress-admin.png',
				'header-text' => false,
				'uploads' => true,
			)
		);
		register_default_headers( array(
			'classicpress-admin' => array(
				'url' => get_template_directory_uri() . '/images/classicpress-admin.png',
				'thumbnail_url' => get_template_directory_uri() . '/images/classicpress-admin.png',
				'description' => __( 'Classicpress Admin', 'the-classicpress-theme' )
			)
		) );

		/**
		 * Add support for core editor style.
		 *
		 * @link https://codex.wordpress.org/Editor_Style
		 */
		add_theme_support( 'editor-styles' );
		add_editor_style( 'css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'susty_setup' );

/**
 * Enqueue scripts and styles.
 */
function susty_scripts() {
	wp_enqueue_style( 'susty-style', get_stylesheet_uri() );

	wp_enqueue_script( 'cp-menu-resize', get_template_directory_uri() . '/js/menu-resize.js', null,	null );	

	wp_deregister_script( 'wp-embed' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'susty_scripts' );

/**
 * Remove emoji styles.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/**
 * Add widgets to hero, sidebar and footer.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/
 */
function cp_susty_register_sidebar() {
	register_sidebar(
		array(
			'id'            => 'main-sidebar',
			'name'          => __( 'Main Sidebar', 'the-classicpress-theme' ),
			'before_widget' => '<div class="widget-container widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
	register_sidebar(
		array(
			'id'            => 'blog-sidebar',
			'name'          => __( 'Blog Sidebar', 'the-classicpress-theme' ),
			'before_widget' => '<div class="widget-container widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
	register_sidebar(
		array(
			'id'            => 'hero',
			'name'          => __( 'Hero', 'the-classicpress-theme' ),
			'before_widget' => '<div class="widget-container widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
	register_sidebar(
		array(
			'id'            => 'footer',
			'name'          => __( 'Footer', 'the-classicpress-theme' ),
			'before_widget' => '<div class="widget-container widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'cp_susty_register_sidebar' );

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into ClassicPress.
 */
require get_template_directory() . '/inc/template-functions.php';
