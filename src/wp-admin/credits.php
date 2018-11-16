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

include( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap about-wrap full-width-layout">

<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

<p class="about-text">
	<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
	<?php classicpress_dev_version_info(); ?>
</p>
<p class="about-text">
	<?php _e( 'Thank you for using ClassicPress, the business focused CMS.' ); ?><br>
	<?php _e( 'Powerful. Versatile. Predictable.' ); ?>
</p>

<div class="wp-badge"></div>

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
	'https://www.classicpress.net/contributors/'
) . '</p>';

echo '<p class="about-description">' . sprintf(
	/* translators: %s: https://www.classicpress.net/get-involved/ */
	__( 'Interested in helping out with development? <a href="%s">Get involved in ClassicPress</a>.' ),
	'https://www.classicpress.net/get-involved/'
) . '</p>';

?>
</div>
</div>
<?php

include( ABSPATH . 'wp-admin/admin-footer.php' );

return;
