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
	$copyright  = '<a href="' . esc_url( __( 'https://www.classicpress.net/', 'twentyseventeen' ) ) . '" class="imprint">';
	/* translators: %s: ClassicPress */
	$copyright .= sprintf( __( 'Proudly powered by %s', 'twentyseventeen' ), 'ClassicPress' ) . '</a>';
	/**
	 * Filters Twenty Seventeen footer copyright.
	 *
	 * @since Twenty Seventeen 99.4
	 *
	 * @param string $copyright Copyright text.
	 */
	echo apply_filters( 'twentyseventeen_copyright', $copyright );
	?>
</div><!-- .site-info -->
