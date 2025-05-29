<?php
/**
 * Themes administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to edit theme options on this site.' ) . '</p>',
		403
	);
}

if ( current_user_can( 'switch_themes' ) && isset( $_GET['action'] ) ) {
	if ( 'activate' === $_GET['action'] ) {
		check_admin_referer( 'switch-theme_' . $_GET['stylesheet'] );
		$theme = wp_get_theme( $_GET['stylesheet'] );

		if ( ! $theme->exists() || ! $theme->is_allowed() ) {
			wp_die(
				'<h1>' . __( 'Something went wrong.' ) . '</h1>' .
				'<p>' . __( 'The requested theme does not exist.' ) . '</p>',
				403
			);
		}

		switch_theme( $theme->get_stylesheet() );
		wp_redirect( admin_url( 'themes.php?activated=true' ) );
		exit;
	} elseif ( 'resume' === $_GET['action'] ) {
		check_admin_referer( 'resume-theme_' . $_GET['stylesheet'] );
		$theme = wp_get_theme( $_GET['stylesheet'] );

		if ( ! current_user_can( 'resume_theme', $_GET['stylesheet'] ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to resume this theme.' ) . '</p>',
				403
			);
		}

		$result = resume_theme( $theme->get_stylesheet(), self_admin_url( 'themes.php?error=resuming' ) );

		if ( is_wp_error( $result ) ) {
			wp_die( $result );
		}

		wp_redirect( admin_url( 'themes.php?resumed=true' ) );
		exit;
	} elseif ( 'delete' === $_GET['action'] ) {
		check_admin_referer( 'delete-theme_' . $_GET['stylesheet'] );
		$theme = wp_get_theme( $_GET['stylesheet'] );

		if ( ! current_user_can( 'delete_themes' ) ) {
			wp_die(
				'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
				'<p>' . __( 'Sorry, you are not allowed to delete this item.' ) . '</p>',
				403
			);
		}

		if ( ! $theme->exists() ) {
			wp_die(
				'<h1>' . __( 'Something went wrong.' ) . '</h1>' .
				'<p>' . __( 'The requested theme does not exist.' ) . '</p>',
				403
			);
		}

		$active = wp_get_theme();
		if ( $active->get( 'Template' ) === $_GET['stylesheet'] ) {
			wp_redirect( admin_url( 'themes.php?delete-active-child=true' ) );
		} else {
			delete_theme( $_GET['stylesheet'] );
			wp_redirect( admin_url( 'themes.php?deleted=true' ) );
		}
		exit;
	} elseif ( 'enable-auto-update' === $_GET['action'] ) {
		if ( ! ( current_user_can( 'update_themes' ) && wp_is_auto_update_enabled_for_type( 'theme' ) ) ) {
			wp_die( __( 'Sorry, you are not allowed to enable themes automatic updates.' ) );
		}

		check_admin_referer( 'updates' );

		$all_items    = wp_get_themes();
		$auto_updates = (array) get_site_option( 'auto_update_themes', array() );

		$auto_updates[] = $_GET['stylesheet'];
		$auto_updates   = array_unique( $auto_updates );
		// Remove themes that have been deleted since the site option was last updated.
		$auto_updates = array_intersect( $auto_updates, array_keys( $all_items ) );

		update_site_option( 'auto_update_themes', $auto_updates );

		wp_redirect( admin_url( 'themes.php?enabled-auto-update=true' ) );

		exit;
	} elseif ( 'disable-auto-update' === $_GET['action'] ) {
		if ( ! ( current_user_can( 'update_themes' ) && wp_is_auto_update_enabled_for_type( 'theme' ) ) ) {
			wp_die( __( 'Sorry, you are not allowed to disable themes automatic updates.' ) );
		}

		check_admin_referer( 'updates' );

		$all_items    = wp_get_themes();
		$auto_updates = (array) get_site_option( 'auto_update_themes', array() );

		$auto_updates = array_diff( $auto_updates, array( $_GET['stylesheet'] ) );
		// Remove themes that have been deleted since the site option was last updated.
		$auto_updates = array_intersect( $auto_updates, array_keys( $all_items ) );

		update_site_option( 'auto_update_themes', $auto_updates );

		wp_redirect( admin_url( 'themes.php?disabled-auto-update=true' ) );

		exit;
	}
}

// Used in the HTML title tag.
$title       = __( 'Themes' );
$parent_file = 'themes.php';

// Help tab: Overview.
if ( current_user_can( 'switch_themes' ) ) {
	$help_overview = '<p>' . __( 'This screen is used for managing your installed themes. Aside from the default theme(s) included with your ClassicPress installation, themes are designed and developed by third parties.' ) . '</p>' .
		'<p>' . __( 'From this screen you can:' ) . '</p>' .
		'<ul><li>' . __( 'Hover or tap to see Activate and Live Preview buttons' ) . '</li>' .
		'<li>' . __( 'Click on the theme to see the theme name, version, author, description, tags, and the Delete link' ) . '</li>' .
		'<li>' . __( 'Click Customize for the active theme or Live Preview for any other theme to see a live preview' ) . '</li></ul>' .
		'<p>' . __( 'The active theme is displayed highlighted as the first theme.' ) . '</p>' .
		'<p>' . __( 'The search for installed themes will search for terms in their name, description, author, or tag.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>';

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' => $help_overview,
		)
	);
} // End if 'switch_themes'.

// Help tab: Adding Themes.
if ( current_user_can( 'install_themes' ) ) {
	if ( is_multisite() ) {
		$help_install = '<p>' . __( 'Installing themes on Multisite can only be done from the Network Admin section.' ) . '</p>';
	} else {
		$help_install = '<p>' . sprintf(
			/* translators: %s: https://wordpress.org/themes/ */
			__( 'If you would like to see more themes to choose from, click on the &#8220;Add New&#8221; button and you will be able to browse or search for additional themes from the <a href="%s">WordPress Theme Directory</a>. Themes in the WordPress Theme Directory are designed and developed by third parties, and are compatible with the license WordPress uses. Oh, and they&#8217;re free!' ),
			__( 'https://wordpress.org/themes/' )
		) . '</p>';
	}

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'adding-themes',
			'title'   => __( 'Adding Themes' ),
			'content' => $help_install,
		)
	);
} // End if 'install_themes'.

// Help tab: Previewing and Customizing.
if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
	$help_customize =
		'<p>' . __( 'Tap or hover on any theme then click the Live Preview button to see a live preview of that theme and change theme options in a separate, full-screen view. You can also find a Live Preview button at the bottom of the theme details screen. Any installed theme can be previewed and customized in this way.' ) . '</p>' .
		'<p>' . __( 'The theme being previewed is fully interactive &mdash; navigate to different pages to see how the theme handles posts, archives, and other page templates. The settings may differ depending on what theme features the theme being previewed supports. To accept the new settings and activate the theme all in one step, click the Activate &amp; Publish button above the menu.' ) . '</p>' .
		'<p>' . __( 'When previewing on smaller monitors, you can use the collapse icon at the bottom of the left-hand pane. This will hide the pane, giving you more room to preview your site in the new theme. To bring the pane back, click on the collapse icon again.' ) . '</p>';

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'customize-preview-themes',
			'title'   => __( 'Previewing and Customizing' ),
			'content' => $help_customize,
		)
	);
} // End if 'edit_theme_options' && 'customize'.

$help_sidebar_autoupdates = '';

// Help tab: Auto-updates.
if ( current_user_can( 'update_themes' ) && wp_is_auto_update_enabled_for_type( 'theme' ) ) {
	$help_tab_autoupdates =
		'<p>' . __( 'Auto-updates can be enabled or disabled for each individual theme. Themes with auto-updates enabled will display the estimated date of the next auto-update. Auto-updates depends on the WP-Cron task scheduling system.' ) . '</p>' .
		'<p>' . __( 'Please note: Third-party themes and plugins, or custom code, may override ClassicPress scheduling.' ) . '</p>';

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'plugins-themes-auto-updates',
			'title'   => __( 'Auto-updates' ),
			'content' => $help_tab_autoupdates,
		)
	);

	$help_sidebar_autoupdates = '<p>' . __( '<a href="https://wordpress.org/documentation/article/plugins-themes-auto-updates/">Documentation on Auto-updates</a>' ) . '</p>';
} // End if 'update_themes' && 'wp_is_auto_update_enabled_for_type'.

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/work-with-themes/">Documentation on Using Themes</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/appearance-themes-screen/">Documentation on Managing Themes</a>' ) . '</p>' .
	$help_sidebar_autoupdates .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

if ( current_user_can( 'switch_themes' ) ) {
	$themes = wp_prepare_themes_for_js();
} else {
	$themes = wp_prepare_themes_for_js( array( wp_get_theme() ) );
}
wp_reset_vars( array( 'theme', 'search' ) );

wp_localize_script(
	'theme',
	'_wpThemeSettings',
	array(
		'themes'   => $themes,
		'settings' => array(
			'canInstall'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ),
			'installURI'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ) ? admin_url( 'theme-install.php' ) : null,
			'confirmDelete' => __( "Are you sure you want to delete this theme?\n\nClick 'Cancel' to go back, 'OK' to confirm the delete." ),
			'adminUrl'      => parse_url( admin_url(), PHP_URL_PATH ),
		),
		'l10n'     => array(
			'addNew'            => __( 'Add New Theme' ),
			'search'            => __( 'Search Installed Themes' ),
			'searchPlaceholder' => __( 'Search installed themes...' ), // Placeholder (no ellipsis).
			/* translators: %d: Number of themes. */
			'themesFound'       => __( 'Number of Themes found: %d' ),
			'noThemesFound'     => __( 'No themes found. Try a different search.' ),
			'version'           => __( 'Version' ),
			'activate'          => __( 'Activate' ),
			'cannot_activate'   => __( 'Cannot Activate' ),
			'delete'            => __( 'Delete' ),
			'updated'           => __( 'Updated!' ),
		),
	)
);

wp_enqueue_script( 'theme' );
wp_enqueue_script( 'updates' );

require_once ABSPATH . 'wp-admin/admin-header.php';
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Themes' ); ?>
		<span class="title-count theme-count"><?php echo ! empty( $_GET['search'] ) ? __( '&hellip;' ) : count( $themes ); ?></span>
	</h1>

	<?php if ( ! is_multisite() && current_user_can( 'install_themes' ) ) : ?>
		<a href="<?php echo esc_url( admin_url( 'theme-install.php' ) ); ?>" class="hide-if-no-js page-title-action"><?php echo esc_html_x( 'Add New', 'theme' ); ?></a>
		<form class="search-form">
			<label class="screen-reader-text" for="wp-filter-search-input"><?php esc_html_e( 'Search Installed Themes' ); ?></label>
			<input placeholder="<?php esc_html_e( 'Search installed themes...' ); ?>" type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search">
		</form>
	<?php endif; ?>

	<form class="search-form"></form>

	<hr class="wp-header-end">
<?php
if ( ! validate_current_theme() || isset( $_GET['broken'] ) ) {
	wp_admin_notice(
		__( 'The active theme is broken. Reverting to the default theme.' ),
		array(
			'id'                 => 'message1',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
} elseif ( isset( $_GET['activated'] ) ) {
	if ( isset( $_GET['previewed'] ) ) {
		wp_admin_notice(
			__( 'Settings saved and theme activated.' ) . '<a href="' . esc_url( home_url( '/' ) ) . '">' . __( 'Visit site' ) . '</a>',
			array(
				'id'                 => 'message2',
				'additional_classes' => array( 'updated' ),
				'dismissible'        => true,
			)
		);
	} else {
		wp_admin_notice(
			__( 'New theme activated.' ) . '<a href="' . esc_url( home_url( '/' ) ) . '">' . __( 'Visit site' ) . '</a>',
			array(
				'id'                 => 'message2',
				'additional_classes' => array( 'updated' ),
				'dismissible'        => true,
			)
		);
	}
} elseif ( isset( $_GET['deleted'] ) ) {
	wp_admin_notice(
		__( 'Theme deleted.' ),
		array(
			'id'                 => 'message3',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
} elseif ( isset( $_GET['delete-active-child'] ) ) {
	wp_admin_notice(
		__( 'You cannot delete a theme while it has an active child theme.' ),
		array(
			'id'                 => 'message4',
			'additional_classes' => array( 'error' ),
		)
	);
} elseif ( isset( $_GET['resumed'] ) ) {
	wp_admin_notice(
		__( 'Theme resumed.' ),
		array(
			'id'                 => 'message5',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
} elseif ( isset( $_GET['error'] ) && 'resuming' === $_GET['error'] ) {
	wp_admin_notice(
		__( 'Theme could not be resumed because it triggered a <strong>fatal error</strong>.' ),
		array(
			'id'                 => 'message6',
			'additional_classes' => array( 'error' ),
		)
	);
} elseif ( isset( $_GET['enabled-auto-update'] ) ) {
	wp_admin_notice(
		__( 'Theme will be auto-updated.' ),
		array(
			'id'                 => 'message7',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
} elseif ( isset( $_GET['disabled-auto-update'] ) ) {
	wp_admin_notice(
		__( 'Theme will no longer be auto-updated.' ),
		array(
			'id'                 => 'message8',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
}

$current_theme = wp_get_theme();

if ( $current_theme->errors() && ( ! is_multisite() || current_user_can( 'manage_network_themes' ) ) ) {
	echo '<div class="error"><p>' . __( 'Error:' ) . ' ' . $current_theme->errors()->get_error_message() . '</p></div>';
}

$current_theme_actions = array();

if ( is_array( $submenu ) && isset( $submenu['themes.php'] ) ) {
	$forbidden_paths = array(
		'themes.php',
		'theme-editor.php',
		'site-editor.php',
		'edit.php?post_type=wp_navigation',
	);

	foreach ( (array) $submenu['themes.php'] as $item ) {
		$class = '';

		if ( in_array( $item[2], $forbidden_paths, true ) || str_starts_with( $item[2], 'customize.php' ) ) {
			continue;
		}

		// 0 = name, 1 = capability, 2 = file.
		if ( 0 === strcmp( $self, $item[2] ) && empty( $parent_file )
			|| $parent_file && $item[2] === $parent_file
		) {
			$class = ' current';
		}

		if ( ! empty( $submenu[ $item[2] ] ) ) {
			$submenu[ $item[2] ] = array_values( $submenu[ $item[2] ] ); // Re-index.
			$menu_hook           = get_plugin_page_hook( $submenu[ $item[2] ][0][2], $item[2] );

			if ( file_exists( WP_PLUGIN_DIR . "/{$submenu[$item[2]][0][2]}" ) || ! empty( $menu_hook ) ) {
				$current_theme_actions[] = "<a class='button$class' href='admin.php?page={$submenu[$item[2]][0][2]}'>{$item[0]}</a>";
			} else {
				$current_theme_actions[] = "<a class='button$class' href='{$submenu[$item[2]][0][2]}'>{$item[0]}</a>";
			}
		} elseif ( ! empty( $item[2] ) && current_user_can( $item[1] ) ) {
			$menu_file = $item[2];

			if ( current_user_can( 'customize' ) ) {
				if ( 'custom-header' === $menu_file ) {
					$current_theme_actions[] = "<a class='button hide-if-no-customize$class' href='customize.php?autofocus[control]=header_image'>{$item[0]}</a>";
				} elseif ( 'custom-background' === $menu_file ) {
					$current_theme_actions[] = "<a class='button hide-if-no-customize$class' href='customize.php?autofocus[control]=background_image'>{$item[0]}</a>";
				}
			}

			$pos = strpos( $menu_file, '?' );
			if ( false !== $pos ) {
				$menu_file = substr( $menu_file, 0, $pos );
			}

			if ( file_exists( ABSPATH . "wp-admin/$menu_file" ) ) {
				$current_theme_actions[] = "<a class='button$class' href='{$item[2]}'>{$item[0]}</a>";
			} else {
				$current_theme_actions[] = "<a class='button$class' href='themes.php?page={$item[2]}'>{$item[0]}</a>";
			}
		}
	}
}

$class_name = 'theme-browser';
if ( ! empty( $_GET['search'] ) ) {
	$class_name .= ' search-loading';
}
?>
<div class="<?php echo esc_attr( $class_name ); ?>">
	<ul class="themes wp-clearfix">

<?php
/*
 * This PHP is synchronized with the tmpl-theme template below!
 */
$cp_has_update = classicpress_has_update();

foreach ( $themes as $theme ) :
	$aria_action = $theme['id'] . '-action';
	$aria_name   = $theme['id'] . '-name';

	$active_class = '';
	if ( $theme['active'] ) {
		$active_class = ' active';
	}
	$activate_nonce = '';
	if ( isset( $theme['actions']['activate'] ) ) {
		$activate_nonce = esc_url( $theme['actions']['activate'] );
	}
	$delete_nonce = '';
	if ( isset( $theme['actions']['delete'] ) ) {
		$delete_nonce = esc_url( $theme['actions']['delete'] );
	}
	$customize_action = '';
	if ( isset( $theme['actions']['customize'] ) ) {
		$customize_action = esc_url( $theme['actions']['customize'] );
	}
	$autoupdate_action = '';
	if ( isset( $theme['actions']['autoupdate'] ) ) {
		$autoupdate_action = esc_url( $theme['actions']['autoupdate'] );
	}
	?>
<li id="<?php esc_html_e( $theme['id'] ); ?>" class="theme<?php echo $active_class; ?>" tabindex="0" data-active="<?php esc_attr_e( $theme['active'] ); ?>" data-activate-nonce="<?php echo $activate_nonce; ?>" data-customize="<?php echo $customize_action; ?>" data-autoupdate="<?php echo $autoupdate_action; ?>" data-autoupdate-enabled="<?php esc_attr_e( $theme['autoupdate']['enabled'] ?? '' ); ?>" data-autoupdate-supported="<?php esc_attr_e( $theme['autoupdate']['supported'] ?? '' ); ?>" data-autoupdate-forced="<?php esc_attr_e( $theme['autoupdate']['forced'] ?? '' ); ?>" data-description="<?php esc_attr_e( $theme['description'] ); ?>" data-tags="<?php esc_attr_e( $theme['tags'] ); ?>" data-parent="<?php esc_attr_e( $theme['parent'] ); ?>" data-version="<?php esc_attr_e( $theme['version'] ); ?>" data-delete-nonce="<?php echo $delete_nonce; ?>" data-compatible-wp="<?php esc_attr_e( $theme['compatibleWP'] ); ?>" data-compatible-php="<?php esc_attr_e( $theme['compatiblePHP'] ); ?>" data-has-update="<?php esc_attr_e( $theme['hasUpdate'] ); ?>" data-update="<?php esc_attr_e( $theme['update'] ); ?>" data-update-response="<?php esc_attr_e( $theme['updateResponse']['compatibleWP'] . '-' . $theme['updateResponse']['compatiblePHP'] ); ?>">
	<?php if ( ! empty( $theme['screenshot'][0] ) ) { ?>
		<div class="theme-screenshot">
			<img src="<?php echo esc_url( $theme['screenshot'][0] . '?ver=' . $theme['version'] ); ?>" alt="">
		</div>
	<?php } else { ?>
		<div class="theme-screenshot blank"></div>
	<?php } ?>

	<?php if ( $theme['hasUpdate'] ) : ?>
		<?php if ( $theme['updateResponse']['compatibleWP'] && $theme['updateResponse']['compatiblePHP'] ) : ?>
			<div class="update-message notice inline notice-warning notice-alt"><p>
				<?php if ( $theme['hasPackage'] ) : ?>
					<?php esc_html_e( 'New version available.' ); ?> <button class="button-link update-button-link" type="button"><?php esc_html_e( 'Update now' ); ?></button>
				<?php else : ?>
					<?php esc_html_e( 'New version available.' ); ?>
				<?php endif; ?>
			</p></div>
		<?php else : ?>
			<div class="update-message notice inline notice-error notice-alt"><p>
				<?php
				if ( ! $theme['updateResponse']['compatibleWP'] && ! $theme['updateResponse']['compatiblePHP'] ) {
					printf(
						/* translators: %s: Theme name. */
						__( 'There is a new version of %s available, but it does not work with your versions of ClassicPress and PHP.' ),
						$theme['name']
					);
					if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
						if ( $cp_has_update ) {
							printf(
								/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
								' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
								self_admin_url( 'update-core.php' ),
								esc_url( wp_get_update_php_url() )
							);
						} else {
							printf(
							/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
						}
						wp_update_php_annotation( '</p><p><em>', '</em>' );
					} elseif ( current_user_can( 'update_core' ) && $cp_has_update ) {
						printf(
							/* translators: %s: URL to WordPress Updates screen. */
							' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
							self_admin_url( 'update-core.php' )
						);
					} elseif ( current_user_can( 'update_php' ) ) {
						printf(
							/* translators: %s: URL to Update PHP page. */
							' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
							esc_url( wp_get_update_php_url() )
						);
						wp_update_php_annotation( '</p><p><em>', '</em>' );
					}
				} elseif ( ! $theme['updateResponse']['compatibleWP'] ) {
					printf(
						/* translators: %s: Theme name. */
						__( 'There is a new version of %s available, but it does not work with your version of ClassicPress.' ),
						$theme['name']
					);
					if ( current_user_can( 'update_core' ) && $cp_has_update ) {
						printf(
							/* translators: %s: URL to WordPress Updates screen. */
							' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
							self_admin_url( 'update-core.php' )
						);
					}
				} elseif ( ! $theme['updateResponse']['compatiblePHP'] ) {
					printf(
						/* translators: %s: Theme name. */
						__( 'There is a new version of %s available, but it does not work with your version of PHP.' ),
						$theme['name']
					);
					if ( current_user_can( 'update_php' ) ) {
						printf(
							/* translators: %s: URL to Update PHP page. */
							' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
							esc_url( wp_get_update_php_url() )
						);
						wp_update_php_annotation( '</p><p><em>', '</em>' );
					}
				}
				?>
			</p></div>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	if ( ! $theme['compatibleWP'] || ! $theme['compatiblePHP'] ) {
		$message = '';
		if ( ! $theme['compatibleWP'] && ! $theme['compatiblePHP'] ) {
			$message = __( 'This theme does not work with your versions of ClassicPress and PHP.' );
			if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
				if ( $cp_has_update ) {
					$message .= sprintf(
						/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
						' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
						self_admin_url( 'update-core.php' ),
						esc_url( wp_get_update_php_url() )
					);
				} else {
					$message .= sprintf(
						/* translators: %s: URL to Update PHP page. */
						' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
						esc_url( wp_get_update_php_url() )
					);
				}
				$message .= wp_update_php_annotation( '</p><p><em>', '</em>', false );
			} elseif ( current_user_can( 'update_core' ) && $cp_has_update ) {
				$message .= sprintf(
					/* translators: %s: URL to WordPress Updates screen. */
					' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
					self_admin_url( 'update-core.php' )
				);
			} elseif ( current_user_can( 'update_php' ) ) {
				$message .= sprintf(
					/* translators: %s: URL to Update PHP page. */
					' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
					esc_url( wp_get_update_php_url() )
				);
				$message .= wp_update_php_annotation( '</p><p><em>', '</em>', false );
			}
		} elseif ( ! $theme['compatibleWP'] ) {
			$message .= __( 'This theme does not work with your version of ClassicPress.' );
			if ( current_user_can( 'update_core' ) ) {
				$message .= sprintf(
					/* translators: %s: URL to WordPress Updates screen. */
					' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
					self_admin_url( 'update-core.php' )
				);
			}
		} elseif ( ! $theme['compatiblePHP'] ) {
			$message .= __( 'This theme does not work with your version of PHP.' );
			if ( current_user_can( 'update_php' ) ) {
				$message .= sprintf(
					/* translators: %s: URL to Update PHP page. */
					' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
					esc_url( wp_get_update_php_url() )
				);
				$message .= wp_update_php_annotation( '</p><p><em>', '</em>', false );
			}
		}

		wp_admin_notice(
			$message,
			array(
				'type'               => 'error',
				'additional_classes' => array( 'inline', 'notice-alt' ),
			)
		);
	}
	?>

	<?php
	/* translators: %s: Theme name. */
	$details_aria_label = sprintf( _x( 'View Theme Details for %s', 'theme' ), $theme['name'] );
	?>
	<button type="button" aria-label="<?php echo esc_attr( $details_aria_label ); ?>" class="more-details" id="<?php echo esc_attr( $aria_action ); ?>"><?php esc_html_e( 'Theme Details' ); ?></button>
	<div class="theme-author">
		<?php
		/* translators: %s: Theme author name. */
		printf( __( 'By %s' ), $theme['authorAndUri'] ? $theme['authorAndUri'] : $theme['author'] );
		?>
	</div>

	<div class="theme-id-container">
		<?php if ( $theme['active'] ) { ?>
			<h2 class="theme-name" id="<?php echo esc_attr( $aria_name ); ?>">
				<span><?php _ex( 'Active:', 'theme' ); ?></span> <?php echo $theme['name']; ?>
			</h2>
		<?php } else { ?>
			<h2 class="theme-name" id="<?php echo esc_attr( $aria_name ); ?>"><?php echo $theme['name']; ?></h2>
		<?php } ?>

		<div class="theme-actions">
		<?php if ( $theme['active'] ) { ?>
			<?php
			if ( $theme['actions']['customize'] && current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
				/* translators: %s: Theme name. */
				$customize_aria_label = sprintf( _x( 'Customize %s', 'theme' ), $theme['name'] );
				?>
				<a aria-label="<?php echo esc_attr( $customize_aria_label ); ?>" class="button button-primary customize load-customize hide-if-no-customize" href="<?php echo $theme['actions']['customize']; ?>"><?php esc_html_e( 'Customize' ); ?></a>
			<?php } ?>
		<?php } elseif ( $theme['compatibleWP'] && $theme['compatiblePHP'] ) { ?>
			<?php
			/* translators: %s: Theme name. */
			$aria_label = sprintf( _x( 'Activate %s', 'theme' ), '{{ data.name }}' );
			?>
			<a class="button activate" href="<?php echo $theme['actions']['activate']; ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php esc_html_e( 'Activate' ); ?></a>
			<?php
			if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
				/* translators: %s: Theme name. */
				$live_preview_aria_label = sprintf( _x( 'Live Preview %s', 'theme' ), '{{ data.name }}' );
				?>
				<a aria-label="<?php echo esc_attr( $live_preview_aria_label ); ?>" class="button button-primary load-customize hide-if-no-customize" href="<?php echo $theme['actions']['customize']; ?>"><?php esc_html_e( 'Live Preview' ); ?></a>
			<?php } ?>
		<?php } else { ?>
			<?php
			/* translators: %s: Theme name. */
			$aria_label = sprintf( _x( 'Cannot Activate %s', 'theme' ), '{{ data.name }}' );
			?>
			<a class="button disabled" aria-label="<?php echo esc_attr( $aria_label ); ?>"><?php _ex( 'Cannot Activate', 'theme' ); ?></a>
			<?php if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) { ?>
				<a class="button button-primary hide-if-no-customize disabled"><?php esc_html_e( 'Live Preview' ); ?></a>
			<?php } ?>
		<?php } ?>

		</div>
	</div>
</li>
<?php endforeach; ?>
<li class="theme add-new-theme">
	<a href="<?php echo esc_url( admin_url( 'theme-install.php' ) ); ?>">
		<div class="theme-screenshot">
			<span></span>
		</div>
		<h2 class="theme-name"><?php esc_html_e( 'Add New Theme' ); ?></h2>
	</a>
</li>
	</ul>
</div>

<p class="no-themes"><?php esc_html_e( 'No themes found. Try a different search.' ); ?></p>

<?php
// List broken themes, if any.
$broken_themes = wp_get_themes( array( 'errors' => true ) );
if ( ! is_multisite() && $broken_themes ) {
	?>

<div class="broken-themes">
<h3><?php esc_html_e( 'Broken Themes' ); ?></h3>
<p><?php esc_html_e( 'The following themes are installed but incomplete.' ); ?></p>

	<?php
	$can_resume  = current_user_can( 'resume_themes' );
	$can_delete  = current_user_can( 'delete_themes' );
	$can_install = current_user_can( 'install_themes' );
	?>
<table>
	<tr>
		<th><?php _ex( 'Name', 'theme name' ); ?></th>
		<th><?php esc_html_e( 'Description' ); ?></th>
		<?php if ( $can_resume ) { ?>
			<td></td>
		<?php } ?>
		<?php if ( $can_delete ) { ?>
			<td></td>
		<?php } ?>
		<?php if ( $can_install ) { ?>
			<td></td>
		<?php } ?>
	</tr>
	<?php foreach ( $broken_themes as $broken_theme ) : ?>
		<tr>
			<td><?php echo $broken_theme->get( 'Name' ) ? $broken_theme->display( 'Name' ) : esc_html( $broken_theme->get_stylesheet() ); ?></td>
			<td><?php echo $broken_theme->errors()->get_error_message(); ?></td>
			<?php
			if ( $can_resume ) {
				if ( 'theme_paused' === $broken_theme->errors()->get_error_code() ) {
					$stylesheet = $broken_theme->get_stylesheet();
					$resume_url = add_query_arg(
						array(
							'action'     => 'resume',
							'stylesheet' => urlencode( $stylesheet ),
						),
						admin_url( 'themes.php' )
					);
					$resume_url = wp_nonce_url( $resume_url, 'resume-theme_' . $stylesheet );
					?>
					<td><a href="<?php echo esc_url( $resume_url ); ?>" class="button resume-theme"><?php esc_html_e( 'Resume' ); ?></a></td>
					<?php
				} else {
					?>
					<td></td>
					<?php
				}
			}

			if ( $can_delete ) {
				$stylesheet = $broken_theme->get_stylesheet();
				$delete_url = add_query_arg(
					array(
						'action'     => 'delete',
						'stylesheet' => urlencode( $stylesheet ),
					),
					admin_url( 'themes.php' )
				);
				$delete_url = wp_nonce_url( $delete_url, 'delete-theme_' . $stylesheet );
				?>
				<td><a href="<?php echo esc_url( $delete_url ); ?>" class="button delete-theme"><?php esc_html_e( 'Delete' ); ?></a></td>
				<?php
			}

			if ( $can_install && 'theme_no_parent' === $broken_theme->errors()->get_error_code() ) {
				$parent_theme_name = $broken_theme->get( 'Template' );
				$parent_theme      = themes_api( 'theme_information', array( 'slug' => urlencode( $parent_theme_name ) ) );

				if ( ! is_wp_error( $parent_theme ) ) {
					$install_url = add_query_arg(
						array(
							'action' => 'install-theme',
							'theme'  => urlencode( $parent_theme_name ),
						),
						admin_url( 'update.php' )
					);
					$install_url = wp_nonce_url( $install_url, 'install-theme_' . $parent_theme_name );
					?>
					<td><a href="<?php echo esc_url( $install_url ); ?>" class="button install-theme"><?php esc_html_e( 'Install Parent Theme' ); ?></a></td>
					<?php
				}
			}
			?>
		</tr>
	<?php endforeach; ?>
</table>
</div>

	<?php
}
?>
</div><!-- .wrap -->

<dialog id="theme-modal" class="theme-overlay" aria-label="<?php esc_html_e( 'Theme Details' ); ?>">
	<div class="theme-overlay">
		<div class="theme-wrap wp-clearfix">
			<div class="theme-header">
				<button class="left dashicons dashicons-no">
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( 'Show previous theme' );
						?>
					</span>
				</button>
				<button class="right dashicons dashicons-no">
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( 'Show next theme' );
						?>
					</span>
				</button>
				<button class="close dashicons dashicons-no" autofocus>
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						_e( 'Close details dialog' );
						?>
					</span>
				</button>
			</div>
			<?php /* Template below is inserted here when modal opens. */ ?>
		</div>
	</div>
</dialog>

<?php
/*
 * The contents of this template are inserted into the modal when the modal is opened.
 *
 * This makes it easier to reset the dialog to its default state when closed,
 * simply by deleting the contents of this template.
 */
?>
<template id="theme-modal-insert">
	<div id="theme-modal-insert-container">
		<div class="theme-about wp-clearfix">
			<div class="theme-screenshots">
				<div class="screenshot"></div>
			</div>

			<div class="theme-info">
				<span class="current-label" hidden><?php esc_html_e( 'Active Theme' ); ?></span>
				<h2>
					<span class="theme-name"></span>
					<span class="theme-version"></span>
				</h2>
				<p class="theme-author"></p>

				<div class="notice inline notice-error notice-alt notice-large no-wp-php" hidden>
					<p>
						<?php
						_e( 'This theme does not work with your versions of ClassicPress and PHP.' );
						if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
							if ( $cp_needs_update ) {
								printf(
									/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
									' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
									self_admin_url( 'update-core.php' ),
									esc_url( wp_get_update_php_url() )
								);
							} else {
								printf(
									/* translators: %s: URL to Update PHP page. */
									' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
									esc_url( wp_get_update_php_url() )
								);
							}
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						} elseif ( current_user_can( 'update_core' ) && $cp_needs_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						} elseif ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
						?>
					</p>
				</div>	

				<div class="notice inline notice-error notice-alt notice-large no-wp" hidden>
					<p>
						<?php
						_e( 'This theme does not work with your version of ClassicPress.' );
						if ( current_user_can( 'update_core' ) && $cp_needs_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						}
						?>
					</p>
				</div>

				<div class="notice inline notice-error notice-alt notice-large no-php" hidden>
					<p>
						<?php
						_e( 'This theme does not work with your version of PHP.' );
						if ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
						?>
					</p>
				</div>

				<div class="notice inline notice-warning notice-alt notice-large has-update" hidden>
					<h3 class="notice-title"><?php esc_html_e( 'Update Available' ); ?></h3>
					<span></span>
				</div>

				<div class="notice inline notice-error notice-alt notice-large incompat-update" hidden>
					<h3 class="notice-title"><?php esc_html_e( 'Update Incompatible' ); ?></h3>
					<p class="no-wp-php" hidden>
						<?php
						esc_html_e( 'There is a new version of this theme available, but it does not work with your versions of ClassicPress and PHP.' );
						if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
							if ( $cp_has_update ) {
								printf(
									/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
									' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
									self_admin_url( 'update-core.php' ),
									esc_url( wp_get_update_php_url() )
								);
							} else {
								printf(
									/* translators: %s: URL to Update PHP page. */
									' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
									esc_url( wp_get_update_php_url() )
								);
							}
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						} elseif ( current_user_can( 'update_core' ) && $cp_has_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						} elseif ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
						?>
					</p>

					<p class="no-wp" hidden>
						<?php
						esc_html_e( 'There is a new version of this theme available, but it does not work with your version of ClassicPress.' );
						if ( current_user_can( 'update_core' ) && $cp_has_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						}
						?>
					</p>

					<p class="no-php" hidden>
						<?php
						esc_html_e( 'There is a new version of this theme available, but it does not work with your version of PHP.' );
						if ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
						?>
					</p>
				</div>

				<div class="theme-autoupdate" style="margin-top:1em;" hidden>
					<span class="forced" hidden><?php esc_html_e( 'Auto-updates enabled' ); ?></span>
					<span class="not-forced" hidden><?php esc_html_e( 'Auto-updates disabled' ); ?></span>

					<?php
					if ( current_user_can( 'edit_theme_options' ) ) {
						?>

						<button type="button" class="toggle-auto-update button-link enabled" data-slug="" data-wp-action="disable" hidden>
							<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span><span class="label"><?php esc_html_e( 'Disable auto-updates' ); ?></span>
						</button>
						<button type="button" class="toggle-auto-update button-link no-auto" data-slug="" data-wp-action="enable" hidden>
							<span class="dashicons dashicons-update spin hidden" aria-hidden="true"></span><span class="label"><?php esc_html_e( 'Enable auto-updates' ); ?></span>
						</button>

						<?php
					}
					?>

					<span class="auto-update-time" hidden>					
						<br><?php wp_get_auto_update_message(); ?>
					</span>

					<?php
					wp_get_admin_notice(
						'',
						array(
							'type'               => 'error',
							'additional_classes' => array( 'notice-alt', 'inline', 'hidden' ),
						)
					);
					?>

				</div>

				<p class="theme-description"></p>

				<p class="parent-theme" hidden><span><?php esc_html_e( 'This is a child theme of:' ); ?></span> <strong></strong></p>

				<p class="theme-tags" hidden>
					<span><?php esc_html_e( 'Tags:' ); ?></span>
					<span class="theme-tags-span" style="font-weight:normal;"></span>
				</p>
			</div>
		</div>

		<div class="theme-actions">
			<div class="active-theme" hidden>

				<?php
				if ( current_user_can( 'edit_theme_options' ) ) {
					if ( current_user_can( 'customize' ) ) {
						?>
				
						<a href="" class="button button-primary customize load-customize hide-if-no-customize"><?php esc_html_e( 'Customize' ); ?></a>

						<?php
					}
					if ( current_user_can( 'install_themes' ) ) {
						echo implode( ' ', $current_theme_actions );
					}
				}
				?>

			</div>
			<div class="inactive-theme" hidden>
				<a href="" class="button" aria-label=""><?php esc_html_e( 'Activate' ); ?></a>

				<?php
				if ( current_user_can( 'edit_theme_options' ) && current_user_can( 'customize' ) ) {
					?>

					<a href="" class="button button-primary load-customize hide-if-no-customize"><?php esc_html_e( 'Live Preview' ); ?></a>

					<?php
				}
				?>

			</div>

			<?php
			if ( current_user_can( 'delete_themes' ) ) {
				?>

				<a href="" class="button delete-theme" aria-label="" hidden><?php esc_html_e( 'Delete' ); ?></a>

				<?php
			}
			?>

		</div>
	</div>
</template>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
wp_print_update_row_templates();

wp_localize_script(
	'updates',
	'_wpUpdatesItemCounts',
	array(
		'totals' => wp_get_update_data(),
	)
);

require_once ABSPATH . 'wp-admin/admin-footer.php';
