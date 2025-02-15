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

</article><!-- #post-<?php the_ID(); ?> -->
