<?php
/**
 * Your Rights administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Used in the HTML title tag.
$title = __( 'Freedoms' );

require_once ABSPATH . 'wp-admin/admin-header.php';

?>
<div class="wrap about-wrap full-width-layout">

<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

<p class="about-text">
	<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
	<?php classicpress_dev_version_info(); ?>
</p>
<p class="about-text">
	<?php
	printf(
		/* translators: link to "business-focused CMS" article */
		__( 'Thank you for using ClassicPress, the <a href="%s">CMS for Creators</a>.' ),
		'https://link.classicpress.net/the-cms-for-creators'
	);
	?>
	<br>
	<?php _e( 'Stable. Lightweight. Instantly Familiar.' ); ?>
</p>

<div class="wp-badge"></div>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a href="about.php" class="nav-tab"><?php _e( 'About' ); ?></a>
	<a href="credits.php" class="nav-tab"><?php _e( 'Credits' ); ?></a>
	<a href="freedoms.php" class="nav-tab nav-tab-active"><?php _e( 'Freedoms' ); ?></a>
	<a href="privacy.php" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
</h2>

<div class="about-wrap-content">
	<p class="about-description"><?php printf( __( 'ClassicPress is Free and open source software, built by a distributed community of volunteer developers from around the world. ClassicPress comes with some awesome, worldview-changing rights courtesy of its <a href="%s">license</a>, the GPL.' ), 'https://opensource.org/licenses/gpl-license' ); ?></p>

	<hr>
	<h3><?php _e( 'The Four Freedoms' ); ?></h3>
	<ul class="about-freedoms">
		<li><h4><?php _e( 'The 1st Freedom' ); ?></h4><?php _e( 'To run the program for any purpose.' ); ?></li>
		<li><h4><?php _e( 'The 2nd Freedom' ); ?></h4><?php _e( 'To study how the program works and change it to make it do what you wish.' ); ?></li>
		<li><h4><?php _e( 'The 3rd Freedom' ); ?></h4><?php _e( 'To redistribute.' ); ?></li>
		<li><h4><?php _e( 'The 4th Freedom' ); ?></h4><?php _e( 'To distribute copies of your modified versions to others.' ); ?></li>
	</ul>
	<hr>

	<p>
	<?php
	$cp_directory = 'https://directory.classicpress.net/';
	$license_url = 'https://opensource.org/licenses/gpl-license';
	printf(
		/* translators: 1: link to CP Directory, 2: link to GPL */
		__( 'Every plugin and theme in the <a href="%1$s">ClassicPress Directory</a> falls under the <a href="%2$s">GPL</a> or a similarly free and compatible license.' ),
		$cp_directory,
		$license_url
	);
	?>
	</p>

	<p><?php _e( 'If you get a plugin or theme from another source, make sure it has the right license. If it does not respect the ClassicPress license, we do not recommend it.' ); ?></p>

	<p><?php _e( 'Don&#8217;t you wish all software came with these freedoms? So do we! For more information, check out the <a href="https://www.fsf.org/">Free Software Foundation</a>.' ); ?></p>
</div>

</div>
<?php

require_once ABSPATH . 'wp-admin/admin-footer.php';
