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
			<h2>Answers to some commonly asked questions</h2>
			<p>We hope to be able to answer many of your questions here in this FAQ but we understand you will very likely have other questions too. If you can’t find what you’re looking for here or on the rest of our website, we’d love it if you popped into our <a href="http://forums.classicpress.net/" target="_blank" rel="noopener">forums</a> and ask whatever question(s) you need answering.</p>
			<br>

		<?php
		if ( have_posts() ) :

				/* Start the Loop */
				while ( have_posts() ) :
					the_post();
					?>

					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<details>
							<summary>
								<h3><?php the_title(); ?></h3>
							</summary>
							<div class="faq-content"><?php the_content(); ?></div>
						</details>
					</article><!-- #post-<?php the_ID(); ?> -->

				<?php
				endwhile;

			the_posts_navigation();

		else:

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>

		</main><!-- #main -->

		<?php get_sidebar(); ?>

	</div><!-- #primary -->

<?php
get_footer();
