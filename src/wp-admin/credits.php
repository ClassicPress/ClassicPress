<?php
/**
 * Credits administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __( 'Credits' );

$display_version = classicpress_version();

include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap about-wrap full-width-layout">

<h1><?php printf( __( 'Welcome to ClassicPress %s' ), $display_version ); ?></h1>

<p class="about-text"><?php printf( __( 'Thank you for trying ClassicPress! We are under heavy development while we prepare for an initial release.' ), $display_version ); ?></p>

<div class="wp-badge"><?php printf( __( 'Version %s' ), $display_version ); ?></div>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a href="about.php" class="nav-tab"><?php _e( 'What&#8217;s New' ); ?></a>
	<a href="credits.php" class="nav-tab nav-tab-active"><?php _e( 'Credits' ); ?></a>
	<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
	<a href="freedoms.php?privacy-notice" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
</h2>

<div class="about-wrap-content">
<?php

echo '<p class="about-description">' . sprintf(
	/* translators: %s: https://www.classicpress.net/contributors/ */
	__( 'ClassicPress is created by a <a href="%1$s">worldwide team</a> of passionate individuals.' ),
	__( 'https://www.classicpress.net/contributors/' )
) . '</p>';

echo '<p class="about-description">' . sprintf(
	/* translators: %s: https://www.classicpress.net/get-involved/ */
	__( 'Interested in helping out with development? <a href="%s">Get involved in ClassicPress</a>.' ),
	__( 'https://www.classicpress.net/get-involved/' )
) . '</p>';

?>
</div>
</div>
<?php

include( ABSPATH . 'wp-admin/admin-footer.php' );

return;
