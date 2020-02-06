<?php
/**
 * Administration Functions
 *
 * This file is deprecated, use 'wp-admin/includes/admin.php' instead.
 *
 * @deprecated WP-2.5.0
 * @package ClassicPress
 * @subpackage Administration
 */

_deprecated_file( basename(__FILE__), 'WP-2.5.0', 'wp-admin/includes/admin.php' );

<<<<<<< HEAD
/** ClassicPress Administration API: Includes all Administration functions. */
require_once(ABSPATH . 'wp-admin/includes/admin.php');
=======
/** WordPress Administration API: Includes all Administration functions. */
require_once ABSPATH . 'wp-admin/includes/admin.php';
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.
