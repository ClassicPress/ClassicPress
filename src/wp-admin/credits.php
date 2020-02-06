<?php
/**
 * Credits administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

<<<<<<< HEAD
/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __( 'Credits' );

include( ABSPATH . 'wp-admin/admin-header.php' );
=======
/** WordPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/includes/credits.php';

$title = __( 'Credits' );

list( $display_version ) = explode( '-', get_bloginfo( 'version' ) );

require_once ABSPATH . 'wp-admin/admin-header.php';

$credits = wp_credits();
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.
?>
<div class="wrap about-wrap full-width-layout">

<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

<p class="about-text">
	<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
	<?php classicpress_dev_version_info(); ?>
</p>
<p class="about-text">
	<?php printf(
		/* translators: link to "business-focused CMS" article */
		__( 'Thank you for using ClassicPress, the <a href="%s">business-focused CMS</a>.' ),
		'https://www.classicpress.net/blog/2018/10/29/classicpress-for-business-professional-organization-websites/'
	); ?>
	<br>
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
<<<<<<< HEAD
=======
if ( ! $credits ) {
	echo '</div>';
	require_once ABSPATH . 'wp-admin/admin-footer.php';
	exit;
}
?>

	<hr />

	<div class="about__section">
		<div class="column has-subtle-background-color">
			<?php wp_credits_section_title( $credits['groups']['core-developers'] ); ?>
			<?php wp_credits_section_list( $credits, 'core-developers' ); ?>
			<?php wp_credits_section_list( $credits, 'contributing-developers' ); ?>
		</div>
	</div>

	<hr />

	<div class="about__section">
		<div class="column">
			<?php wp_credits_section_title( $credits['groups']['props'] ); ?>
			<?php wp_credits_section_list( $credits, 'props' ); ?>
		</div>
	</div>
>>>>>>> e72fff9cef... Code Modernization: Replace `dirname( __FILE__ )` calls with `__DIR__` magic constant.

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

require_once ABSPATH . 'wp-admin/admin-footer.php';

return;
