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

<?php if ( is_singular() ) : ?>
	<header>
		<?php
		the_title(
			'<h1>',
			'</h1>'
		);
		?>

		<?php if ( 'post' === get_post_type() ) : ?>
		<p class="entry-meta">
			<?php
			susty_wp_posted_on();
			susty_wp_posted_by();
			esc_html_e( ' | Category: ', 'the-classicpress-theme' );
			the_category( ', ' );
			?>
		</p><!-- .entry-meta -->
		<?php endif; ?>
	</header>

<?php else : ?>
	<header class="blog">
		<?php
		the_title(
			'<h2><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">',
			'</a></h2>'
		);
		?>
	</header>
<?php endif; ?>

	<?php
	if ( is_singular() ) {
		susty_wp_post_thumbnail();
	}
	?>

	<div id="post-content">
		<?php
		the_content(
			sprintf(
				wp_kses(
					/* translators: %s: Name of current post. Only visible to screen readers */
					__( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'the-classicpress-theme' ),
					array(
						'span' => array(
							'class' => array(),
						),
					)
				),
				get_the_title()
			)
		);

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'the-classicpress-theme' ),
				'after'  => '</div>',
			)
		);
		?>
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

</article><!-- #post-<?php the_ID(); ?> -->
