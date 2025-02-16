<?php
/**
 * Template part for displaying single post content
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Susty
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header>
		<?php
		the_title( '<h1 class="post-title">', '</h1>' );
		?>

		<?php
		if ( 'post' === get_post_type() ) :
		?>
		<div class="entry-meta">
			<?php
			susty_wp_posted_on();
			susty_wp_posted_by();
			wp_categories_tags();
			?>
		</div>
		<?php
		endif;
		?>
	</header>

	<?php
	susty_wp_post_thumbnail();
	?>

	<div class="post-content">
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
			'<div class="edit-link">',
			'</div>'
		);
	endif;
	?>

</article><!-- #post-<?php the_ID(); ?> -->
