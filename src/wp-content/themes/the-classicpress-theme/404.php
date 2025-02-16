<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#404-not-found
 *
 * @package Susty
 */

get_header();
?>

	<div id="primary">
		<main id="main" class="page-main" role="main">

			<section>
				<div class="page-content">
					<p><?php esc_html_e( 'It looks like nothing was found at this location. Maybe try one of the links below or a search?', 'the-classicpress-theme' ); ?></p>

					<?php
					$widget_title_args = array(
						'before_title' => '<h2 class="widget-title">',
						'after_title' => '</h2>',
					);

					the_widget( 'WP_Widget_Search', array(), $widget_title_args );

					the_widget( 'WP_Widget_Recent_Posts', array(), $widget_title_args );
					?>

					<div class="widget widget_categories">
						<h2 class="widget-title"><?php esc_html_e( 'Most Used Categories', 'the-classicpress-theme' ); ?></h2>
						<ul>
							<?php
							wp_list_categories(
								array(
									'orderby'    => 'count',
									'order'      => 'DESC',
									'show_count' => 1,
									'title_li'   => '',
									'number'     => 10,
								)
							);
							?>
						</ul>
					</div><!-- .widget -->

					<?php
					the_widget( 'WP_Widget_Archives', array(), $widget_title_args );

					the_widget( 'WP_Widget_Tag_Cloud', array(), $widget_title_args );
					?>
				</div>
			</section>

		</main><!-- #main -->

		<?php get_sidebar(); ?>		

	</div><!-- #primary -->

<?php
get_footer();
