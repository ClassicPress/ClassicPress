<?php

if (! isset($wp_did_header)):
if ( !file_exists( dirname(__FILE__) . '/wp-config.php') ) {
	if (strpos($_SERVER['PHP_SELF'], 'wp-admin') !== false) $path = '';
	else $path = 'wp-admin/';

	require_once( dirname(__FILE__) . '/wp-includes/classes.php');
	require_once( dirname(__FILE__) . '/wp-includes/functions.php');
	require_once( dirname(__FILE__) . '/wp-includes/plugin.php');
	wp_die( sprintf(/*WP_I18N_CONFIG*/" There doesn't seem to be a <code>wp-config.php</code> file. I need this before we can get started. Need more help? <a href='https://codex.wordpress.org/Editing_wp-config.php'>We got it</a>. You can create a <code>wp-config.php</code> file through a web interface, but this doesn't work for all server setups. The safest way is to manually create the file.</p><p><a href='%s' class='button'>Create a Configuration File</a>" /*/WP_I18N_CONFIG*/, $path.'setup-config.php'), /*WP_I18N_ERROR*/ "ClassicPress &rsaquo; Error" /*/WP_I18N_ERROR*/);
}

$wp_did_header = true;

require_once( dirname(__FILE__) . '/wp-config.php');

wp();

require_once(ABSPATH . WPINC . '/template-loader.php');

endif;

?>
