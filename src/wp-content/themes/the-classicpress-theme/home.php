<?php
/**
 * The template for displaying latest posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

get_header();
?>

	<div id="primary">
		<main id="main" class="page-main" role="main">
			<div class="post-list">

				<?php
				if ( have_posts() ) :

					/* Start the Loop */
					while ( have_posts() ) :
						the_post();

						get_template_part( 'template-parts/content', 'blog' );

					endwhile;

					the_posts_navigation();

				else:

					get_template_part( 'template-parts/content', 'none' );

				endif;
				?>

			</div>
		</main><!-- #main -->

		<?php get_sidebar(); ?>

	</div><!-- #primary -->

<?php
get_footer();
