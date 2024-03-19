<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Susty
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="blog-post">

		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<p class="entry-meta">
			<?php susty_wp_posted_on(); ?>
			<?php susty_wp_posted_by(); ?>
			<?php esc_html_e( ' | Category: ', 'the-classicpress-theme' ); ?>
			<?php the_category( ', ' ); ?>
		</p>

		<div class="excerpt"><p><?php the_excerpt(); ?>
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
					' <span class="edit-link">',
					'</span>'
				);
				?>
			<?php endif; ?>
		</p>
		</div>

		<!--a href="<?php //the_permalink(); ?>" class="button button-purple"><?php //esc_html_e( 'Continue Reading', 'the-classicpress-theme' ); ?></a--> 
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
