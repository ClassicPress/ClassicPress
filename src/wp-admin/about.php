<?php
/**
 * About This Version administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

wp_enqueue_script( 'underscore' );

/* translators: Page title of the About ClassicPress page in the admin. */
$title = _x( 'About', 'page title' );

require ABSPATH . 'wp-admin/admin-header.php';
?>
	<div class="wrap about-wrap full-width-layout">
		<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

		<p class="about-text">
			<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
			<?php classicpress_dev_version_info(); ?>
		</p>
		<p class="about-text">
			<?php
			printf(
				/* translators: link to "business-focused CMS" article */
				__( 'Thank you for using ClassicPress, the <a href="%s">CMS for Creators</a>.' ),
				'https://link.classicpress.net/the-cms-for-creators'
			);
			?>
			<br>
			<?php _e( 'Stable. Lightweight. Instantly Familiar.' ); ?>
		</p>
		<div class="wp-badge"></div>

		<h2 class="nav-tab-wrapper wp-clearfix">
			<a href="about.php" class="nav-tab nav-tab-active"><?php _e( 'About' ); ?></a>
			<a href="credits.php" class="nav-tab"><?php _e( 'Credits' ); ?></a>
			<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
			<a href="freedoms.php?privacy-notice" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
		</h2>

		<div class="changelog point-releases about-wrap-content">

			<?php if ( get_locale() !== 'en_US' ) { ?>
				<p class="about-inline-notice notice-warning">
					<?php
					printf(
						/* translators: link to learn more about translating ClassicPress */
						__( 'Help us translate ClassicPress into your language! <a href="%s">Learn more</a>.' ),
						'https://www.classicpress.net/translating-classicpress/'
					);
					?>
				</p>
			<?php } ?>

			<h3><?php _e( 'About ClassicPress' ); ?></h3>

			<p>
				<?php
				global $wp_version;
				printf(
					/* translators: link to ClassicPress site */
					__( '<a href="%s"><strong>ClassicPress</strong></a> is a fork of the WordPress %s branch, including the battle-tested and proven classic editor interface using TinyMCE.' ),
					'https://www.classicpress.net',
					$wp_version
				);
				?>
			</p>
			<p>
				<?php
				_e(
					'This has been a solid foundation for millions of sites for many years, and we believe it will also be an excellent foundation for the future.'
				);
				?>
			</p>
			<h3><?php _e( 'Join our growing community' ); ?></h3>
			<p>
				<?php
				printf(
					/* translators: 1: link with instructions to join ClassicPress Zulip, 2: link to community forums */
					__( 'For general discussion about ClassicPress, <a href="%1$s"><strong>join our Zulip group</strong></a> or our <a href="%2$s"><strong>community forum</strong></a>.' ),
					'https://classicpress.zulipchat.com/register/',
					'https://forums.classicpress.net/c/support'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: link to ClassicPress FAQs page, 2: link to ClassicPress support forum */
					__( 'If you need help with something else, please see our <a href="%1$s"><strong>FAQs page</strong></a>. If your question is not answered there, you can make a new post on our <a href="%2$s"><strong>support forum</strong></a>.' ),
					'https://www.classicpress.net/faq/',
					'https://forums.classicpress.net/c/support/'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: link to ClassicPress GitHub repository, 2: link to GitHub issues list */
					__( 'ClassicPress is developed <a href="%1$s"><strong>on GitHub</strong></a>. For specific bug reports or technical suggestions, see the <a href="%2$s"><strong>issues list</strong></a> and add your report if it is not already present.' ),
					'https://github.com/ClassicPress/ClassicPress',
					'https://github.com/ClassicPress/ClassicPress/issues'
				);
				?>
			</p>
			<h3><?php _e( 'ClassicPress changelogs' ); ?></h3>
			<h4><?php _e( 'ClassicPress 2.0.0' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: link to ClassicPress 1.0.0 changelog */
					__( 'For a list of new features and other changes from WordPress %1$s, see the <a href="%2$s"><strong>ClassicPress 2.0.0 (Bella) release notes</strong></a>.' ),
					'6.2.x',
					'https://forums.classicpress.net/t/classicpress-2-0-0-bella-release-notes/5099'
				);
				?>
			</p>
			<h4>
			<?php
			printf(
				/* translators: current ClassicPress version */
				__( 'ClassicPress 1.0.1 - %s' ),
				classicpress_version()
			);
			?>
			</h4>
			<p>
				<?php
				printf(
					/* translators: link to ClassicPress release announcements subforum */
					__( 'The changes and new features included in recent versions of ClassicPress can be found in our <a href="%s"><strong>Release Announcements subforum</strong></a>.' ),
					'https://forums.classicpress.net/c/announcements/release-notes'
				);
				?>
			</p>
			<h4><?php _e( 'ClassicPress 1.0.0' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: link to ClassicPress 1.0.0 changelog */
					__( 'For a list of new features and other changes in WordPress %1$s, see the <a href="%2$s"><strong>ClassicPress 1.0.0 (Aurora) release notes</strong></a>.' ),
					'4.9.x',
					'https://forums.classicpress.net/t/classicpress-1-0-0-aurora-release-notes/910'
				);
				?>
			</p>
			<h3><?php _e( 'ClassicPress Maintenance and Security Releases' ); ?></h3>
			<p>
				<?php
				_e(
					'This version of ClassicPress includes all changes from the following versions of WordPress:'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: %s: WordPress version. */
					__( '<strong>Version %s</strong> addressed some security issues.' ),
					'6.2.3'
				);
				?>
				<?php
				printf(
					/* translators: %s: HelpHub URL. */
					__( 'For more information, see <a href="%s">the release notes</a>.' ),
					sprintf(
						/* translators: %s: WordPress version. */
						esc_url( __( 'https://wordpress.org/support/wordpress-version/version-%s/' ) ),
						sanitize_title( '6.2.3' )
					)
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
						1
					),
					'6.2.2',
					number_format_i18n( 1 )
				);
				?>
				<?php
				printf(
					/* translators: %s: HelpHub URL */
					__( 'For more information, see <a href="%s">the release notes</a>.' ),
					sprintf(
						/* translators: %s: WordPress version */
						esc_url( __( 'https://wordpress.org/support/wordpress-version/version-%s/' ) ),
						sanitize_title( '6.2.2' )
					)
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
						20
					),
					'6.2.1',
					number_format_i18n( 20 )
				);
				?>
				<?php
				printf(
					/* translators: %s: HelpHub URL */
					__( 'For more information, see <a href="%s">the release notes</a>.' ),
					sprintf(
						/* translators: %s: WordPress version */
						esc_url( __( 'https://wordpress.org/support/wordpress-version/version-%s/' ) ),
						sanitize_title( '6.2.1' )
					)
				);
				?>
			</p>
		</div>
	</div>
<?php

require ABSPATH . 'wp-admin/admin-footer.php';

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
_n_noop(
	'<strong>Version %1$s</strong> addressed %2$s bug.',
	'<strong>Version %1$s</strong> addressed %2$s bugs.'
);

/* translators: 1: ClassicPress version number, 2: plural number of bugs. Singular security issue. */
_n_noop(
	'<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
	'<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.'
);

/* translators: 1: ClassicPress version number, 2: plural number of bugs. More than one security issue. */
_n_noop(
	'<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
	'<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.'
);

/* translators: %s: Codex URL */
__( 'For more information, see <a href="%s">the release notes</a>.' );
