<?php
/**
 * Network Plugins administration panel.
 *
 * @package ClassicPress
 * @subpackage Multisite
 * @since WP-3.1.0
 */

<<<<<<< HEAD
/** Load ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );
=======
/** Load WordPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.

require ABSPATH . 'wp-admin/plugins.php';
