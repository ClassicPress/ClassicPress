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
		</p>
		<p class="about-text">
			<?php _e( 'Thank you for trying ClassicPress! We are under heavy development while we prepare for an initial release.' ); ?>
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

		<hr />

		<div class="return-to-dashboard">
			<?php if ( current_user_can( 'update_core' ) && isset( $_GET['updated'] ) ) : ?>
				<a href="<?php echo esc_url( self_admin_url( 'update-core.php' ) ); ?>">
					<?php is_multisite() ? _e( 'Return to Updates' ) : _e( 'Return to Dashboard &rarr; Updates' ); ?>
				</a> |
			<?php endif; ?>
			<a href="<?php echo esc_url( self_admin_url() ); ?>"><?php is_blog_admin() ? _e( 'Go to Dashboard &rarr; Home' ) : _e( 'Go to Dashboard' ); ?></a>
		</div>
	</div>

	<script>
		(function( $ ) {
			$( function() {
				var $window = $( window );
				var $adminbar = $( '#wpadminbar' );
				var $sections = $( '.floating-header-section' );
				var offset = 0;

				// Account for Admin bar.
				if ( $adminbar.length ) {
					offset += $adminbar.height();
				}

				function setup() {
					$sections.each( function( i, section ) {
						var $section = $( section );
						// If the title is long, switch the layout
						var $title = $section.find( 'h2' );
						if ( $title.innerWidth() > 300 ) {
							$section.addClass( 'has-long-title' );
						}
					} );
				}

				var adjustScrollPosition = _.throttle( function adjustScrollPosition() {
					$sections.each( function( i, section ) {
						var $section = $( section );
						var $header = $section.find( 'h2' );
						var width = $header.innerWidth();
						var height = $header.innerHeight();

						if ( $section.hasClass( 'has-long-title' ) ) {
							return;
						}

						var sectionStart = $section.offset().top - offset;
						var sectionEnd = sectionStart + $section.innerHeight();
						var scrollPos = $window.scrollTop();

						// If we're scrolled into a section, stick the header
						if ( scrollPos >= sectionStart && scrollPos < sectionEnd - height ) {
							$header.css( {
								position: 'fixed',
								top: offset + 'px',
								bottom: 'auto',
								width: width + 'px'
							} );
						// If we're at the end of the section, stick the header to the bottom
						} else if ( scrollPos >= sectionEnd - height && scrollPos < sectionEnd ) {
							$header.css( {
								position: 'absolute',
								top: 'auto',
								bottom: 0,
								width: width + 'px'
							} );
						// Unstick the header
						} else {
							$header.css( {
								position: 'static',
								top: 'auto',
								bottom: 'auto',
								width: 'auto'
							} );
						}
					} );
				}, 100 );

				function enableFixedHeaders() {
					if ( $window.width() > 782 ) {
						setup();
						adjustScrollPosition();
						$window.on( 'scroll', adjustScrollPosition );
					} else {
						$window.off( 'scroll', adjustScrollPosition );
						$sections.find( '.section-header' )
							.css( {
								width: 'auto'
							} );
						$sections.find( 'h2' )
							.css( {
								position: 'static',
								top: 'auto',
								bottom: 'auto',
								width: 'auto'
							} );
					}
				}
				$( window ).resize( enableFixedHeaders );
				enableFixedHeaders();
			} );
		})( jQuery );
	</script>

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
