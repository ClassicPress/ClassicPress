<?php
/**
 * About This Version administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

wp_enqueue_script( 'underscore' );

/* translators: Page title of the About ClassicPress page in the admin. */
$title = _x( 'About', 'page title' );

include( ABSPATH . 'wp-admin/admin-header.php' );
?>
	<div class="wrap about-wrap full-width-layout">
		<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

		<p class="about-text">
			<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
			<?php classicpress_dev_version_info(); ?>
		</p>
		<p class="about-text">
			<?php _e( 'Thank you for using ClassicPress, the business focused CMS.' ); ?><br>
			<?php _e( 'Powerful. Versatile. Predictable.' ); ?>
		</p>
		<div class="wp-badge"></div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="about.php" class="nav-tab nav-tab-active"><?php _e( 'What&#8217;s New' ); ?></a>
			<a href="credits.php" class="nav-tab"><?php _e( 'Credits' ); ?></a>
			<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
			<a href="freedoms.php?privacy-notice" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
		</h2>

		<div class="changelog point-releases">
			<h3><?php _e( 'ClassicPress' ); ?></h3>

			<?php
			echo "<p>\n";
			_e( '<strong>We began a fork of WordPress</strong> named ClassicPress.' );
			print ' ';
			_e( 'The initial version will be based on the WordPress 4.9 branch.' );
			echo "</p><p>\n";
			/* translators: 1: ClassicPress GitHub URL, 2: ClassicPress website URL */
			printf(
				__( 'To see how you can help, take a look at <a href="%s">our GitHub repository</a> and <a href="%s">the official ClassicPress website</a>!' ),
				'https://github.com/ClassicPress/ClassicPress',
				'https://www.classicpress.net'
			);
			echo "</p>\n";
			?>

			<h3><?php _e( 'WordPress Maintenance and Security Releases' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed %2$s bugs.',
						46
					),
					'4.9.8',
					number_format_i18n( 46 )
				);
				?>
				<?php
				printf(
					/* translators: %s: Codex URL */
					__( 'For more information, see <a href="%s">the release notes</a>.' ),
					'https://codex.wordpress.org/Version_4.9.8'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
						17
					),
					'4.9.7',
					number_format_i18n( 17 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.7' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed %2$s bugs.',
						18
					),
					'4.9.6',
					number_format_i18n( 18 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.6' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
						28
					),
					'4.9.5',
					number_format_i18n( 28 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.5' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed %2$s bugs.',
						1
					),
					'4.9.4',
					number_format_i18n( 1 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.4' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed %2$s bugs.',
						34
					),
					'4.9.3',
					number_format_i18n( 34 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.3' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
						22
					),
					'4.9.2',
					number_format_i18n( 22 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.2' );
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: WordPress version number, 2: plural number of bugs. */
					_n(
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bug.',
						'<strong>WordPress version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
						11
					),
					'4.9.1',
					number_format_i18n( 11 )
				);
				?>
				<?php
				/* translators: %s: Codex URL */
				printf( __( 'For more information, see <a href="%s">the release notes</a>.' ), 'https://codex.wordpress.org/Version_4.9.1' );
				?>
			</p>
		</div>
	</div>
<?php

include( ABSPATH . 'wp-admin/admin-footer.php' );

// These are strings we may use to describe maintenance/security releases, where we aim for no new strings.
return;

__( 'Maintenance Release' );
__( 'Maintenance Releases' );

__( 'Security Release' );
__( 'Security Releases' );

__( 'Maintenance and Security Release' );
__( 'Maintenance and Security Releases' );

/* translators: %s: ClassicPress version number */
__( '<strong>Version %s</strong> addressed one security issue.' );
/* translators: %s: ClassicPress version number */
__( '<strong>Version %s</strong> addressed some security issues.' );

/* translators: 1: ClassicPress version number, 2: plural number of bugs. */
_n_noop( '<strong>Version %1$s</strong> addressed %2$s bug.',
         '<strong>Version %1$s</strong> addressed %2$s bugs.' );

/* translators: 1: ClassicPress version number, 2: plural number of bugs. Singular security issue. */
_n_noop( '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
         '<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.' );

/* translators: 1: ClassicPress version number, 2: plural number of bugs. More than one security issue. */
_n_noop( '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
         '<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.' );

/* translators: %s: Codex URL */
__( 'For more information, see <a href="%s">the release notes</a>.' );
