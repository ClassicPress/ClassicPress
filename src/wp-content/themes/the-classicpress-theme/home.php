<?php
/**
 * The main template file
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
			?>
		
			<div class="blog-list"> 
			<?php

				/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				get_template_part( 'template-parts/content-blog', get_post_type() );

				endwhile;
			?>

			</div> 
			<?php

			the_posts_navigation();

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>

		</main>

		<?php get_sidebar(); ?>

	</div>

<?php
get_footer();
