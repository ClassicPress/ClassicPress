<?php
/**
 * ClassicPress Generic Request (POST/GET) Handler
 *
 * Intended for form submission handling in themes and plugins.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** We are located in ClassicPress Administration Screens */
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

<<<<<<< HEAD
if ( defined('ABSPATH') )
	require_once(ABSPATH . 'wp-load.php');
else
	require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );
=======
if ( defined( 'ABSPATH' ) ) {
	require_once ABSPATH . 'wp-load.php';
} else {
	require_once dirname( __DIR__ ) . '/wp-load.php';
}
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.

/** Allow for cross-domain requests (from the front end). */
send_origin_headers();

<<<<<<< HEAD
require_once(ABSPATH . 'wp-admin/includes/admin.php');
=======
require_once ABSPATH . 'wp-admin/includes/admin.php';
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.

nocache_headers();

/** This action is documented in wp-admin/admin.php */
do_action( 'admin_init' );

$action = empty( $_REQUEST['action'] ) ? '' : $_REQUEST['action'];

if ( ! wp_validate_auth_cookie() ) {
	if ( empty( $action ) ) {
		/**
		 * Fires on a non-authenticated admin post request where no action was supplied.
		 *
		 * @since WP-2.6.0
		 */
		do_action( 'admin_post_nopriv' );
	} else {
		/**
		 * Fires on a non-authenticated admin post request for the given action.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the given
		 * request action.
		 *
		 * @since WP-2.6.0
		 */
		do_action( "admin_post_nopriv_{$action}" );
	}
} else {
	if ( empty( $action ) ) {
		/**
		 * Fires on an authenticated admin post request where no action was supplied.
		 *
		 * @since WP-2.6.0
		 */
		do_action( 'admin_post' );
	} else {
		/**
		 * Fires on an authenticated admin post request for the given action.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the given
		 * request action.
		 *
		 * @since WP-2.6.0
		 */
		do_action( "admin_post_{$action}" );
	}
}
