<?php
/**
 * Displays footer site info
 *
 * @package ClassicPress
 * @subpackage ClassicPress_TwentySeventeen
 * @since 1.0.0
 * @version 1.0.0
 */

?>
<div class="site-info">
	<?php
	if ( function_exists( 'the_privacy_policy_link' ) ) {
		the_privacy_policy_link( '', '<span role="separator" aria-hidden="true"></span>' );
	}
	?>
	<a href="<?php echo esc_url( __( 'https://www.classicpress.net/', 'classicpress-twentyseventeen' ) ); ?>" class="imprint">
		<?php printf( __( 'Proudly powered by %s', 'classicpress-twentyseventeen' ), 'ClassicPress' ); ?>
	</a>
</div><!-- .site-info -->
