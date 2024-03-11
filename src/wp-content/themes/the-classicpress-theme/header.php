<?php
/**
 * The header for our theme
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Susty
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/fonts/source-sans-pro-v12-latin-600.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/fonts/source-sans-pro-v12-latin-regular.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/fonts/source-sans-pro-v12-latin-italic.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<link rel="preload" href="<?php echo esc_url( get_template_directory_uri() . '/images/logo-white.svg' ); ?>" as="image" type="image/svg+xml">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div id="page">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'susty' ); ?></a>

	<?php if ( is_front_page() ) {
		echo '<section class="home-hero-container">';
	} ?>
	<header id="masthead">
		<div id="inner-header">
			<span class="logo" role="banner">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/images/logo-white.svg' ); ?>" width="250" alt="ClassicPress logo">
					<span class="screen-reader-text"><?php esc_html_e( 'Home', 'susty' ); ?></span>
				</a>
			</span>

			<nav id="site-navigation" class="main-navigation nav--toggle-sub nav--toggle-small" aria-label="<?php esc_attr_e( 'Main menu', 'susty' ); ?>">

				<?php
				$navcheck = '' ;
				$navcheck = wp_nav_menu( array(
					'theme_location' => 'main-menu',
					'depth' => 2,
					'menu_id' => 'primary-menu', /*keeping original id so nav css and js still works*/
					'fallback_cb' => '',
					'echo' => false
				) );
				if ( $navcheck === '' ) {
					echo '<ul>';
					wp_list_pages( 'title_li=&sort_column=menu_order' );
					echo '</ul>';
				} else {
					echo $navcheck;
				}
				?>

			</nav><!-- #site-navigation -->

		</div>

		<button id="menu-toggle" class="menu-toggle" type="button" aria-haspopup="true" aria-controls="site-navigation" aria-expanded="false" tabindex="0">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/images/baseline-menu-24px.svg' ); ?>" alt="Menu" width="32" height="32">
			<span id="menu-toggle-text" class="screen-reader-text"><?php esc_html_e( 'Menu', 'susty' ); ?></span>
		</button>
			
		<button id="menu-toggle-close" class="menu-toggle close" type="button" aria-haspopup="true" aria-controls="site-navigation" aria-expanded="true" tabindex="0">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/images/baseline-close-24px.svg' ); ?>" alt="Close menu" width="32" height="32">
			<span id="menu-toggle-close-text" class="menu-toggle-text screen-reader-text"><?php esc_html_e( 'Close menu', 'susty' ); ?></span>
		</button>
	</header>
	<?php if ( is_front_page() ) {
		echo '</section><!-- .home-hero-container -->';
	} ?>

	<?php if ( ! is_front_page() && ! is_single() ) {
			$category = single_cat_title( '', false );
			echo '<header id="page-title">';
			if ( is_post_type_archive() ) {
				echo '<h1>';
				post_type_archive_title();
				echo '</h1>';				
			} elseif ( is_blog() ) {
				echo '<h1>';
				esc_html_e( 'ClassicPress News', 'susty' );
				if ( ! empty( $category ) ) {
					esc_html_e( ': ', 'susty' );
					echo esc_html( ucwords( $category ) );
				}
				echo '</h1>';
			} elseif ( is_search() ) {
				echo '<h1>';
				esc_html_e( 'Search Results', 'susty' );
				echo '</h1>';
			} elseif ( is_404() ) {
				echo '<h1>';
				esc_html_e( 'Sorry! That page cannot be found.', 'susty' );
				echo '</h1>';
			}
			else {
				the_title( '<h1>', '</h1>' );
			}
			echo '</header><!-- .entry-header -->';
		}
	?>
	<div id="content" role="main">
