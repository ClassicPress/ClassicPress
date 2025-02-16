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

<?php if ( is_active_sidebar( 'footer' ) ) { ?>
<footer id="colophon" role="complementary">
	<div class="inner-colophon classic">
		<?php
		dynamic_sidebar( 'footer' );
		?>
	</div>
</footer>
<?php } ?>

<footer id="legal" role="contentinfo">
	<div class="inner-legal cplegal">
		<div class="copyright cpcopyright">
			<p>
			<?php _e( 'Copyright', 'the-classicpress-theme' ); ?> <?php echo date( 'Y' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php bloginfo( 'name' ); ?>"><?php bloginfo( 'name' ); ?></a>
			</p>
		</div>
		<div class="policy cppolicy">
			<?php
			$policy_page_id = get_option( 'wp_page_for_privacy_policy' );
			if ( !empty ( get_privacy_policy_url() ) ) {
			?>
				<p>
				<a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php _e('Privacy Policy', 'the-classicpress-theme'); ?></a>
				</p>
			<?php
			}
			?>
		</div>
	</div>
</footer>

</div>

<?php wp_footer(); ?>

</body>

</html>
