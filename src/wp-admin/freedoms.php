<?php
/**
 * Your Rights administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __( 'Freedoms' );

include( ABSPATH . 'wp-admin/admin-header.php' );

$is_privacy_notice = isset( $_GET['privacy-notice'] );

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
	<a href="credits.php" class="nav-tab"><?php _e( 'Credits' ); ?></a>
	<a href="freedoms.php" class="nav-tab<?php if ( ! $is_privacy_notice ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Freedoms' ); ?></a>
	<a href="freedoms.php?privacy-notice" class="nav-tab<?php if ( $is_privacy_notice ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Privacy' ); ?></a>
</h2>

<?php if ( $is_privacy_notice ) : ?>

<div class="about-wrap-content">
	<p class="about-description"><?php _e( 'From time to time, your ClassicPress site may send data to ClassicPress.net &#8212; including, but not limited to &#8212; the version of ClassicPress you are using, and a list of installed plugins and themes.' ); ?></p>

	<p><?php printf( __( 'We take privacy and transparency very seriously. To learn more about what data we collect, and how we use it, please visit <a href="%s">ClassicPress Privacy Policy</a>.' ), 'https://www.iubenda.com/privacy-policy/41030260' ); ?></p>
</div>

<?php else : ?>
<div class="about-wrap-content">
	<p class="about-description"><?php printf( __( 'ClassicPress is Free and open source software, built by a distributed community of mostly volunteer developers from around the world. ClassicPress comes with some awesome, worldview-changing rights courtesy of its <a href="%s">license</a>, the GPL.' ), 'https://opensource.org/licenses/gpl-license' ); ?></p>

	<ol start="0">
		<li><p><?php _e( 'You have the freedom to run the program, for any purpose.' ); ?></p></li>
		<li><p><?php _e( 'You have access to the source code, the freedom to study how the program works, and the freedom to change it to make it do what you wish.' ); ?></p></li>
		<li><p><?php _e( 'You have the freedom to redistribute copies of the original program so you can help your neighbor.' ); ?></p></li>
		<li><p><?php _e( 'You have the freedom to distribute copies of your modified versions to others. By doing this you can give the whole community a chance to benefit from your changes.' ); ?></p></li>
	</ol>

	<p><?php

	$plugins_url = current_user_can( 'activate_plugins' ) ? admin_url( 'plugins.php' ) : __( 'https://wordpress.org/plugins/' );
	$themes_url = current_user_can( 'switch_themes' ) ? admin_url( 'themes.php' ) : __( 'https://wordpress.org/themes/' );

	printf( __( 'Every plugin and theme in ClassicPress.net&#8217;s directory is 100%% GPL or a similarly free and compatible license, so you can feel safe finding <a href="%1$s">plugins</a> and <a href="%2$s">themes</a> there. If you get a plugin or theme from another source, make sure to ask them if it&#8217;s GPL first. If they don&#8217;t respect the ClassicPress license, we don&#8217;t recommend them.' ), $plugins_url, $themes_url ); ?></p>

	<p><?php _e( 'Don&#8217;t you wish all software came with these freedoms? So do we! For more information, check out the <a href="https://www.fsf.org/">Free Software Foundation</a>.' ); ?></p>
</div>

<?php endif; ?>
</div>
<?php include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
