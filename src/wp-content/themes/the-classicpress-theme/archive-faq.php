<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Susty
 */

get_header();
?>
	<div id="primary">
		<main id="main">

		<?php
		if ( have_posts() ) :

				/* Start the Loop */
			while ( have_posts() ) :
				the_post();
				?>

					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<details>
							<summary>
								<h2><?php the_title(); ?></h2>
							</summary>
							<div class="faq-content"><?php the_content(); ?></div>
						</details>
					</article><!-- #post-<?php the_ID(); ?> -->

				<?php
				endwhile;

			the_posts_navigation();

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>

		</main><!-- #main -->

		<?php get_sidebar(); ?>

	</div><!-- #primary -->

<?php
get_footer();
