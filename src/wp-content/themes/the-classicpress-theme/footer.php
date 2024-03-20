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
			<a id="footer-logo" href="<?php echo esc_url( home_url() ); ?>"><img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/images/classicpress-logo-feather-white.svg' ); ?>" alt="<?php esc_attr_e( 'ClassicPress feather logo', 'the-classicpress-theme' ); ?>" width="90"></a>
			<div class="registration">
				<p><?php esc_html_e( 'The ClassicPress project is under the direction of The ClassicPress Initiative, a nonprofit organization registered under section 501(c)(3) of the United States IRS code.', 'the-classicpress-theme' ); ?></p>
				<ul class="social-menu">
					<li><a href="<?php echo esc_url( home_url( '/forums/' ) ); ?>" target="_blank" title="<?php esc_attr_e( 'Forums', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-discourse"></i><span class="screen-reader-text"><?php esc_html_e( 'Support forums', 'the-classicpress-theme' ); ?></span></a></li>
					<li><a href="https://classicpress.zulipchat.com/register/" target="_blank" title="<?php esc_attr_e( 'Zulip', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-zulip"></i><span class="screen-reader-text"><?php esc_html_e( 'Join on Zulip Chat', 'the-classicpress-theme' ); ?></span></a></li>
					<li><a href="https://github.com/ClassicPress" target="_blank" title="<?php esc_attr_e( 'GitHub', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-github"></i><span class="screen-reader-text"><?php esc_html_e( 'Visit GitHub', 'the-classicpress-theme' ); ?></span></a></li>
					<li><a href="https://fosstodon.org/@classicpress" target="_blank" title="<?php esc_attr_e( 'Mastodon', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-mastodon"></i><span class="screen-reader-text"><?php esc_html_e( 'Follow on Mastodon', 'the-classicpress-theme' ); ?></span></a></li>
					<li><a href="https://twitter.com/GetClassicPress" target="_blank" title="<?php esc_attr_e( 'Twitter', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-twitter"></i><span class="screen-reader-text"><?php esc_html_e( 'Follow on Twitter', 'the-classicpress-theme' ); ?></span></a></li>
					<li><a href="https://www.facebook.com/GetClassicPress" target="_blank" title="<?php esc_attr_e( 'Facebook', 'the-classicpress-theme' ); ?>" rel="noreferrer noopener"><i class="cpicon-facebook-f"></i><span class="screen-reader-text"><?php esc_html_e( 'Like on Facebook', 'the-classicpress-theme' ); ?></span></a></li>
				</ul>
			</div>
		</div>
		<div class="footerright">
			<?php
			$footmenu = wp_nav_menu(
				array(
					'theme_location' => 'footer-menu',
					'depth'          => 1,
					'menu_id'        => 'footmenu',
					'menu_class'     => 'nav',
				)
			);
			if ( $footmenu ) {
				echo $footmenu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</footer>
<footer id="legal">
	<div class="cplegal">
		<div class="cpcopyright">
			<p>© 2018-<?php echo esc_attr( gmdate( 'Y' ) ); ?> ClassicPress. All Rights Reserved.</p>
		</div>
		<div class="cppolicy">
			<p><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">Privacy Policy</a></p>
		</div>
	</div>
</footer>

</div>

<?php wp_footer(); ?>

</body>

</html>
