<?php
/**
 * Multisite themes administration panel.
 *
 * @package ClassicPress
 * @subpackage Multisite
 * @since WP-3.0.0
 */

require_once __DIR__ . '/admin.php';

wp_redirect( network_admin_url('themes.php') );
exit;
