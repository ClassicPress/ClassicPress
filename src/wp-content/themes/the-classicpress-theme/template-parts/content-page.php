<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Susty
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'the-classicpress-theme' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>

	<?php
	if ( get_edit_post_link() ) :
		edit_post_link(
			sprintf(
				wp_kses_post(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Edit', 'the-classicpress-theme' ) . ' <span class="screen-reader-text">%s</span>'
				),
				get_the_title()
			),
			' <span class="edit-link">',
			'</span>'
		);
	endif;
	?>

</article><!-- #post-<?php the_ID(); ?> -->
