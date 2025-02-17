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
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'the-classicpress-theme' ); ?></a>

	<header id="masthead">
		<div class="inner-masthead">
			<div class="logo" role="banner">

				<?php
				// Custom logo or site title and site description
				if ( is_front_page() ) {
					$before_title = '<h1 class="site-title">';
					$after_title = '</h1>';
				} else {
					$before_title = '<div class="site-title">';
					$after_title = '</div>';
				}
				if ( get_theme_mod( 'custom_logo' ) ) {
					$custom_logo_id = get_theme_mod( 'custom_logo' );
					$custom_logo_url = wp_get_attachment_image_url( $custom_logo_id, 'full' );
					$custom_logo_alt = get_bloginfo( 'name' );
					echo '<a href="' . esc_url( home_url( '/' ) ) . '"><img src="' . esc_url( $custom_logo_url ) . '" alt="' . $custom_logo_alt . '"></a>';	
				} else {
					echo $before_title; ?><a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php bloginfo( 'name' ); ?>"><?php bloginfo( 'name' ); ?></a><?php echo $after_title;
					if ( get_bloginfo( 'description' ) ) {
					?>
						<div class="site-description"><?php bloginfo( 'description' ); ?></div>
					<?php
					}
				}
				?>

			</div>

			<?php
			// Menu
			?>
			<nav id="site-navigation" class="main-navigation nav--toggle-sub nav--toggle-small" role="navigation" aria-label="<?php esc_attr_e( 'Main menu', 'the-classicpress-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'main-menu',
						'depth'          => 2,
						'menu_id'        => 'primary-menu', /*keeping original id so nav css and js still works*/
					)
				);
				?>
			</nav>
		</div>

		<?php
		// Mobile menu toggle
		?>
		<button id="menu-toggle" class="menu-toggle" type="button" aria-haspopup="true" aria-controls="site-navigation" aria-expanded="false" tabindex="0">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/images/baseline-menu-24px.svg' ); ?>" alt="Menu" width="32" height="32">
			<span id="menu-toggle-text" class="screen-reader-text"><?php esc_html_e( 'Menu', 'the-classicpress-theme' ); ?></span>
		</button>

		<button id="menu-toggle-close" class="menu-toggle close" type="button" aria-haspopup="true" aria-controls="site-navigation" aria-expanded="true" tabindex="0">
			<img src="<?php echo esc_url( get_template_directory_uri() . '/images/baseline-close-24px.svg' ); ?>" alt="Close menu" width="32" height="32">
			<span id="menu-toggle-close-text" class="menu-toggle-text screen-reader-text"><?php esc_html_e( 'Close menu', 'the-classicpress-theme' ); ?></span>
		</button>
	</header>

	<?php
	// Homepage hero
	if ( is_front_page() ) {
		if ( get_header_image() || is_active_sidebar( 'hero' ) ) {
		?>
		<div class="home-hero">
			<div class="inner-home-hero">
				<?php
				if ( is_active_sidebar( 'hero' ) ) {
				?>
				<div class="home-hero-text" role="complementary">
					<?php
					dynamic_sidebar( 'hero' );
					?>
				</div>
				<?php
				}
				?>
				<?php
				if ( get_header_image() ) {
				?>
				<div class="home-hero-image">
					<img src="<?php echo get_header_image(); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" />
				</div>
				<?php
				}
				?>
			</div>
		</div>
		<?php
		}
	}
	?>

	<?php
	// Page title
	if ( ! is_front_page() ) {
		if ( is_singular( 'page' ) ) {
			echo '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">' . get_the_title() . '</h1></div></header>';
		} elseif ( is_home() ) {
			echo '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">' . esc_html( 'News', 'the-classicpress-theme' ) . '</h1></div></header>';
		} elseif ( is_post_type_archive() ) {
			echo '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">';
			post_type_archive_title();
			echo '</h1></div></header>';
		} elseif ( is_archive() ) {
			echo the_archive_title( '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">', '</h1></div></header>' );
		} elseif ( is_search() ) {
			echo '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">' . esc_html( 'Search Results', 'the-classicpress-theme' ) . '</h1></div></header>';
		} elseif ( is_404() ) {
			echo '<header id="page-header"><div class="inner-page-header"><h1 class="page-title">' . esc_html( 'Sorry! That page cannot be found.', 'the-classicpress-theme' ) . '</h1></div></header>';
		}
	}
	?>
	<div id="content">
