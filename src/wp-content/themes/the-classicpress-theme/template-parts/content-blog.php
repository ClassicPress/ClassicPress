<?php
/**
 * Template part for displaying posts at blog page
 *
 * This theme will use it for displaying posts at archive page as well
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

		<?php if ( get_edit_post_link() ) : ?>
			<?php
			edit_post_link(
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Edit <span class="screen-reader-text">%s</span>', 'the-classicpress-theme' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					get_the_title()
				),
				' <div class="edit-link">',
				'</div>'
			);
			?>
		<?php endif; ?>

		<?php
		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'the-classicpress-theme' ),
				'after'  => '</div>',
			)
		);
		?>

	</div>	

</article><!-- #post-<?php the_ID(); ?> -->
