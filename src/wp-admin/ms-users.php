<?php
/**
 * Multisite users administration panel.
 *
 * @package ClassicPress
 * @subpackage Multisite
 * @since WP-3.0.0
 */

require_once( dirname( __FILE__ ) . '/admin.php' );

wp_redirect( network_admin_url('users.php') );
exit;
