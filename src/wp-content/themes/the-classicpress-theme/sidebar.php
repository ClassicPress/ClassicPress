<?php
/* Sidebar template */
?>

<aside id="sidebar" class="sidebar" role="complementary" aria-labelledby="sidebar-header">

	<h2 id="sidebar-header" class="screen-reader-text">
		<?php echo esc_attr_x( 'Sidebar', 'Sidebar aria label', 'the-classicpress-theme' ); ?>
	</h2>

	<?php
	if ( is_page() && is_active_sidebar( 'main-sidebar' ) ) {
		dynamic_sidebar( 'main-sidebar' );
	} elseif ( is_active_sidebar( 'blog-sidebar' ) ) {
		dynamic_sidebar( 'blog-sidebar' );
	}
	?>

</aside>
