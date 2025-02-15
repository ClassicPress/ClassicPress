<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package Susty
 */

get_header();
?>

	<div id="primary">
		<main id="main" class="page-main" role="main">
			<div class="post-list">

				<?php if ( have_posts() ) : ?>

					<header>
						<h3>
							<?php
							/* translators: %s: search query. */
							printf( esc_html__( 'Search term: %s', 'the-classicpress-theme' ), '<span>' . get_search_query() . '</span>' );
							?>
						</h3>
					</header><!-- .page-header -->

					<?php
					/* Start the Loop */
					while ( have_posts() ) :
						the_post();

						/**
						 * Run the loop for the search to output the results.
						 * If you want to overload this in a child theme then include a file
						 * called content-search.php and that will be used instead.
						 */
						get_template_part( 'template-parts/content', 'search' );

					endwhile;

					the_posts_navigation();

				else :

					get_template_part( 'template-parts/content', 'none' );

				endif;
				?>
			</div>
		</main><!-- #main -->

	<?php get_sidebar(); ?>

	</div><!-- #primary -->

<?php
get_footer();
