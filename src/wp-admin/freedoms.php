<?php
/**
 * Your Rights administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// This file was used to also display the Privacy tab on the About screen from 4.9.6 until 5.3.0.
if ( isset( $_GET['privacy-notice'] ) ) {
	wp_redirect( admin_url( 'privacy.php' ), 301 );
	exit;
}

// Used in the HTML title tag.
$title = __( 'Freedoms' );

require_once ABSPATH . 'wp-admin/admin-header.php';

$is_privacy_notice = isset( $_GET['privacy-notice'] );

if ( $is_privacy_notice ) {
	$freedoms_class = '';
	$privacy_class  = ' nav-tab-active';
} else {
	$freedoms_class = ' nav-tab-active';
	$privacy_class  = '';
}

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
	<a href="freedoms.php" class="nav-tab<?php echo $freedoms_class; ?>"><?php _e( 'Freedoms' ); ?></a>
	<a href="freedoms.php?privacy-notice" class="nav-tab<?php echo $privacy_class; ?>"><?php _e( 'Privacy' ); ?></a>
</h2>

<?php if ( $is_privacy_notice ) : ?>

<div class="about-wrap-content">
	<p class="about-description"><?php _e( 'From time to time, your ClassicPress site may send anonymous data to ClassicPress.net. Some examples of the kinds of data that may be sent are the version of ClassicPress your site is running and a list of installed plugins and themes.' ); ?></p>

	<p><?php printf( __( 'We take privacy and transparency very seriously. To learn more about what data we collect, how we use it, and what precautions we take to ensure site owners&#8217; privacy, please see the <a href="%s">ClassicPress Privacy Policy</a>.' ), 'https://link.classicpress.net/core-privacy-policy/' ); ?></p>
</div>

<?php else : ?>
<div class="about-wrap-content">
	<p class="about-description"><?php printf( __( 'ClassicPress is Free and open source software, built by a distributed community of volunteer developers from around the world. ClassicPress comes with some awesome, worldview-changing rights courtesy of its <a href="%s">license</a>, the GPL.' ), 'https://opensource.org/licenses/gpl-license' ); ?></p>

	<ul class="about-freedoms">
		<li><?php _e( 'You have the freedom to run the program, for any purpose. (freedom 0)' ); ?></li>
		<li><?php _e( 'You have access to the source code, the freedom to study how the program works, and the freedom to change it to make it do what you wish. (freedom 1)' ); ?></li>
		<li><?php _e( 'You have the freedom to redistribute copies of the original program so you can help your neighbor. (freedom 2)' ); ?></li>
		<li><?php _e( 'You have the freedom to distribute copies of your modified versions to others. By doing this you can give the whole community a chance to benefit from your changes. (freedom 3)' ); ?></li>
	</ul>

	<p>
	<?php

	$plugins_url = current_user_can( 'activate_plugins' ) ? admin_url( 'plugins.php' ) : __( 'https://wordpress.org/plugins/' );
	$themes_url  = current_user_can( 'switch_themes' ) ? admin_url( 'themes.php' ) : __( 'https://wordpress.org/themes/' );
	$license_url = 'https://www.gnu.org/licenses/old-licenses/gpl-2.0.html';

	printf( __( 'Every plugin and theme in ClassicPress.net&#8217;s directory is 100%% GPL or a similarly free and compatible license, so you can feel safe finding <a href="%1$s">plugins</a> and <a href="%2$s">themes</a> there. If you get a plugin or theme from another source, make sure to ask them if it&#8217;s <a href="%3$s">GPL</a> first. If they don&#8217;t respect the ClassicPress license, we don&#8217;t recommend them.' ), $plugins_url, $themes_url, $license_url );
	?>
	</p>

	<p><?php _e( 'Don&#8217;t you wish all software came with these freedoms? So do we! For more information, check out the <a href="https://www.fsf.org/">Free Software Foundation</a>.' ); ?></p>
</div>

<?php endif; ?>
</div>
<?php require ABSPATH . 'wp-admin/admin-footer.php'; ?>
