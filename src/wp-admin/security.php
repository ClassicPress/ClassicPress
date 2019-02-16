<?php
/**
 * Security Administration Screen.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __('Security');

require_once( ABSPATH . 'wp-admin/admin-header.php' );

?>
<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>
<hr class="wp-header-end">
<h2 class="nav-tab-wrapper wp-clearfix"><a class="nav-tab nav-tab-active" href="#">General</a></h2>
<div class="card">
<p>Some uplifting blurb about security goes here.</p>
</div>
<?php

/**
 * Fires at the end of the Tools Administration screen.
 *
 * @since WP-2.8.0
 */
do_action( 'tool_box' );
?>
</div>
<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );

