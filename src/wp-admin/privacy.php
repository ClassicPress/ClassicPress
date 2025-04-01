<?php
/**
 * Privacy administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Used in the HTML title tag.
$title = __( 'Privacy' );

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
		<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
		<a href="privacy.php" class="nav-tab nav-tab-active"><?php _e( 'Privacy' ); ?></a>
	</h2>

	<div class="about-wrap-content">
		<p class="about-description"><?php _e( 'From time to time, your ClassicPress site may send anonymous data to ClassicPress.net. Some examples of the kinds of data that may be sent are the version of ClassicPress your site is running and a list of installed plugins and themes.' ); ?></p>

		<p><?php printf( __( 'We take privacy and transparency very seriously. To learn more about what data we collect, how we use it, and what precautions we take to ensure site owners&#8217; privacy, please see the <a href="%s">ClassicPress Privacy Policy</a>.' ), 'https://www.classicpress.net/privacy-policy/' ); ?></p>
	</div>

</div>
<?php

require_once ABSPATH . 'wp-admin/admin-footer.php';
