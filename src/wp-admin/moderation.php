<?php
/**
 * Comment Moderation Administration Screen.
 *
 * Redirects to edit-comments.php?comment_status=moderated.
 *
 * @package ClassicPress
 * @subpackage Administration
 */
<<<<<<< HEAD
require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );
wp_redirect( admin_url('edit-comments.php?comment_status=moderated') );
=======
require_once dirname( __DIR__ ) . '/wp-load.php';
wp_redirect( admin_url( 'edit-comments.php?comment_status=moderated' ) );
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.
exit;
