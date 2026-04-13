<?php
/**
 * Template part for displaying posts on blog page
 *
 * This theme will use it for displaying posts on archive page as well
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="blog-post">

		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<p class="entry-meta">
			<?php
			susty_wp_posted_on();
			susty_wp_posted_by();
			esc_html_e( ' | Category: ', 'the-classicpress-theme' );
			the_category( ', ' );
			?>
		</p><!-- .entry-meta -->

		<div class="excerpt">
			<?php the_excerpt(); ?>
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

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'the-classicpress-theme' ),
				'after'  => '</div>',
			)
		);
		?>

	</div>	

</article><!-- #post-<?php the_ID(); ?> -->
