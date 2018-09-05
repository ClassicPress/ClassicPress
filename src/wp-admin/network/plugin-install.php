<?php
/**
 * Install plugin network administration panel.
 *
 * @package ClassicPress
 * @subpackage Multisite
 * @since WP-3.1.0
 */

if ( isset( $_GET['tab'] ) && ( 'plugin-information' == $_GET['tab'] ) )
	define( 'IFRAME_REQUEST', true );

/** Load ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

require( ABSPATH . 'wp-admin/plugin-install.php' );
