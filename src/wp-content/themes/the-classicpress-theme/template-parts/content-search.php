<?php
/**
 * Template part for displaying results in search pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Susty
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header>
		<?php the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>

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

	<div class="excerpt">
		<?php the_excerpt(); ?>
	</div>

</article><!-- #post-<?php the_ID(); ?> -->
