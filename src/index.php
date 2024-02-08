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
<<<<<<< HEAD
define( 'WP_USE_THEMES', true );
=======
if ( file_exists( ABSPATH . 'wp-includes/js/dist/edit-post.js' ) ) {
	require_once ABSPATH . '_index.php';
	return;
}

define( 'WPINC', 'wp-includes' );
require_once ABSPATH . WPINC . '/version.php';
require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/load.php';

// Check for the required PHP version and for the MySQL extension or a database drop-in.
wp_check_php_mysql_versions();

// Standardize $_SERVER variables across setups.
wp_fix_server_vars();

define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
require_once ABSPATH . WPINC . '/functions.php';

wp_load_translations_early();

// Die with an error message.
$die = sprintf(
	'<p>%s</p>',
	__( 'You are running WordPress without JavaScript and CSS files. These need to be built.' )
);

$die .= '<p>' . sprintf(
	/* translators: %s: npm install */
	__( 'Before running any build tasks you need to make sure the dependencies are installed. You can install these by running %s.' ),
	'<code style="color: green;">npm install</code>'
) . '</p>';

$die .= '<ul>';
$die .= '<li>' . __( 'To build WordPress while developing, run:' ) . '<br /><br />';
$die .= '<code style="color: green;">npm run dev</code></li>';
$die .= '<li>' . __( 'To build files automatically when changing the source files, run:' ) . '<br /><br />';
$die .= '<code style="color: green;">npm run watch</code></li>';
$die .= '<li>' . __( 'To create a production build of WordPress, run:' ) . '<br /><br />';
$die .= '<code style="color: green;">npm run build</code></li>';
$die .= '</ul>';

$die .= '<p>' . sprintf(
	/* translators: 1: npm URL, 2: Handbook URL. */
	__( 'This requires <a href="%1$s">npm</a>. <a href="%2$s">Learn more about setting up your local development environment</a>.' ),
	'https://www.npmjs.com/get-npm',
	__( 'https://make.wordpress.org/core/handbook/tutorials/installing-wordpress-locally/' )
) . '</p>';
>>>>>>> 3349f1a527 (Bootstrap/Load: Require `wp-includes/compat.php` in `src/index.php`.)

/** Loads the ClassicPress Environment and Template */
require __DIR__ . '/wp-blog-header.php';
