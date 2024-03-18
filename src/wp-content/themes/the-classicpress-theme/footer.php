<?php

/**
 * The template for displaying the footer
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Susty
 */
?>

</div>

<footer id="colophon">
	<div class="classic">
		<div class="footerleft">
			<a id="footer-logo" href="<?php echo esc_url( home_url() ); ?>"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/classicpress-logo-feather-white.svg' ); ?>" alt="<?php esc_attr_e( 'ClassicPress feather logo', 'classicpress' ); ?>" width="90"></a>
			<div class="registration">
				<p><?php _e( 'The ClassicPress project is under the direction of The ClassicPress Initiative, a nonprofit organization registered under section 501(c)(3) of the United States IRS code.', 'classicpress' ); ?></p>
				<ul class="social-menu">
					<li><a href="<?php echo esc_url( home_url( '/forums/' ) ); ?>" target="_blank" title="<?php esc_attr_e( 'Forums', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-discourse"></i><span class="screen-reader-text"><?php _e( 'Support forums', 'classicpress' ); ?></span></a></li>
					<li><a href="https://classicpress.zulipchat.com/register/" target="_blank" title="<?php esc_attr_e( 'Zulip', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-zulip"></i><span class="screen-reader-text"><?php _e( 'Join on Zulip Chat', 'classicpress' ); ?></span></a></li>
					<li><a href="https://github.com/ClassicPress" target="_blank" title="<?php esc_attr_e( 'GitHub', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-github"></i><span class="screen-reader-text"><?php _e( 'Visit GitHub', 'classicpress' ); ?></span></a></li>
					<li><a href="https://fosstodon.org/@classicpress" target="_blank" title="<?php esc_attr_e( 'Mastodon', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-mastodon"></i><span class="screen-reader-text"><?php _e( 'Follow on Mastodon', 'classicpress' ); ?></span></a></li>
					<li><a href="https://twitter.com/GetClassicPress" target="_blank" title="<?php esc_attr_e( 'Twitter', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-twitter"></i><span class="screen-reader-text"><?php _e( 'Follow on Twitter', 'classicpress' ); ?></span></a></li>
					<li><a href="https://www.facebook.com/GetClassicPress" target="_blank" title="<?php esc_attr_e( 'Facebook', 'classicpress' ); ?>" rel="noreferrer noopener"><i class="cpicon-facebook-f"></i><span class="screen-reader-text"><?php _e( 'Like on Facebook', 'classicpress' ); ?></span></a></li>
				</ul>
			</div>
		</div>
		<div class="footerright">
			<?php
			$footmenu = wp_nav_menu( array(
				'theme_location' => 'footer-menu',
				'depth' => 1,
				'menu_id' => 'footmenu',
				'menu_class' => 'nav'
			) );
			if ( $footmenu ) {
				echo $footmenu;
			}
			?>
		</div>
	</div>
</footer>
<footer id="legal">
	<div class="cplegal">
		<div class="cpcopyright">
			<p>© 2018-<?php echo date( 'Y' ); ?> ClassicPress. All Rights Reserved.</p>
		</div>
		<div class="cppolicy">
			<p><a href="<?php echo esc_url( home_url() ); ?>">Privacy Policy</a></p>
		</div>
	</div>
</footer>

</div>

<?php wp_footer(); ?>

</body>

</html>
