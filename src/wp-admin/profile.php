<?php
/**
 * User Profile Administration Screen.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/**
 * This is a profile page.
 *
 * @since WP-2.5.0
 * @var bool
 */
define('IS_PROFILE_PAGE', true);

/** Load User Editing Page */
require_once( dirname( __FILE__ ) . '/user-edit.php' );
