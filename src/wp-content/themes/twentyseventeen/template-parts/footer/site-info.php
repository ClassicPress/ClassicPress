<?php
/**
 * Displays footer site info
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since WP-1.0
 * @version 1.0
 */

?>
<div class="site-info">
	<?php
	if ( function_exists( 'the_privacy_policy_link' ) ) {
		the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
	}
	?>
<<<<<<< HEAD
	<a href="<?php echo esc_url( __( 'https://www.classicpress.net/', 'twentyseventeen' ) ); ?>" class="imprint">
		<?php printf( __( 'Proudly powered by %s', 'twentyseventeen' ), 'ClassicPress' ); ?>
=======
	<a href="<?php echo esc_url( __( 'https://wordpress.org/', 'twentyseventeen' ) ); ?>" class="imprint">
		<?php
			/* translators: %s: WordPress */
		printf( __( 'Proudly powered by %s', 'twentyseventeen' ), 'WordPress' );
		?>
>>>>>>> 272e5bb4e5 (Coding Standards: Add missing translator comments.)
	</a>
</div><!-- .site-info -->
