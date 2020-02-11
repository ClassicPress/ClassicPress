<?php
/*
Plugin Name: Disable theme update notification
Plugin URI: http://www.classicpress.net
Description: Disables update notifications for WP parent themes shipped with ClassicPress
Author: ClassicPress
Version: 1.0
Author URI: http://www.classicpress.net
*/


function cp_disable_theme_update_notification( $value ) {
	if ( ! empty( $value ) && is_object( $value ) && is_array( $value->response ) ) {
		unset( $value->response['twentyfifteen'] );
		unset( $value->response['twentysixteen'] );
		unset( $value->response['twentyseventeen'] );
	}
	return $value;
}
add_filter( 'site_transient_update_themes', 'cp_disable_theme_update_notification' );
