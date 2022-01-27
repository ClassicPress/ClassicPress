<?php
/**
 * Upgrade API: Theme_Upgrader class
 *
 * @package ClassicPress
 * @subpackage Upgrader
 * @since WP-4.6.0
 */

/**
 * Core class used for upgrading/installing themes.
 *
 * It is designed to upgrade/install themes from a local zip, remote zip URL,
 * or uploaded zip file.
 *
 * @since WP-2.8.0
 * @since WP-4.6.0 Moved to its own file from wp-admin/includes/class-wp-upgrader.php.
 *
 * @see WP_Upgrader
 */
class Theme_Upgrader extends WP_Upgrader {

	/**
	 * Result of the theme upgrade offer.
	 *
	 * @since WP-2.8.0
	 * @var array|WP_Error $result
	 * @see WP_Upgrader::$result
	 */
	public $result;

	/**
	 * Whether multiple themes are being upgraded/installed in bulk.
	 *
	 * @since WP-2.9.0
	 * @var bool $bulk
	 */
	public $bulk = false;

	/**
	 * New theme info.
	 *
	 * @since WP-5.5.0
	 * @var array $new_theme_data
	 *
	 * @see check_package()
	 */
	public $new_theme_data = array();

	/**
	 * Initialize the upgrade strings.
	 *
	 * @since WP-2.8.0
	 */
	public function upgrade_strings() {
		$this->strings['up_to_date'] = __('The theme is at the latest version.');
		$this->strings['no_package'] = __('Update package not available.');
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading update from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;');
		$this->strings['remove_old'] = __('Removing the old version of the theme&#8230;');
		$this->strings['remove_old_failed'] = __('Could not remove the old theme.');
		$this->strings['process_failed'] = __('Theme update failed.');
		$this->strings['process_success'] = __('Theme updated successfully.');
	}

	/**
	 * Initialize the installation strings.
	 *
	 * @since WP-2.8.0
	 */
	public function install_strings() {
		$this->strings['no_package'] = __('Installation package not available.');
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading installation package from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package'] = __('Unpacking the package&#8230;');
		$this->strings['installing_package'] = __('Installing the theme&#8230;');
		$this->strings['remove_old'] = __( 'Removing the old version of the theme&#8230;' );
		$this->strings['remove_old_failed'] = __( 'Could not remove the old theme.' );
		$this->strings['no_files'] = __('The theme contains no files.');
		$this->strings['process_failed'] = __('Theme installation failed.');
		$this->strings['process_success'] = __('Theme installed successfully.');
		/* translators: 1: theme name, 2: version */
		$this->strings['process_success_specific'] = __('Successfully installed the theme <strong>%1$s %2$s</strong>.');
		$this->strings['parent_theme_search'] = __('This theme requires a parent theme. Checking if it is installed&#8230;');
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_prepare_install'] = __('Preparing to install <strong>%1$s %2$s</strong>&#8230;');
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_currently_installed'] = __('The parent theme, <strong>%1$s %2$s</strong>, is currently installed.');
		/* translators: 1: theme name, 2: version */
		$this->strings['parent_theme_install_success'] = __('Successfully installed the parent theme, <strong>%1$s %2$s</strong>.');
		/* translators: %s: theme name */
		$this->strings['parent_theme_not_found'] = sprintf( __( '<strong>The parent theme could not be found.</strong> You will need to install the parent theme, %s, before you can use this child theme.' ), '<strong>%s</strong>' );
		/* translators: %s: Theme error. */
		$this->strings['current_theme_has_errors'] = __( 'The current theme has the following error: "%s".' );

		if ( 'update-theme' === $this->skin->overwrite ) {
			$this->strings['installing_package'] = __( 'Updating the theme&#8230;' );
			$this->strings['process_failed']     = __( 'Theme update failed.' );
			$this->strings['process_success']    = __( 'Theme updated successfully.' );
		}

		if ( 'downgrade-theme' === $this->skin->overwrite ) {
			$this->strings['installing_package'] = __( 'Downgrading the theme&#8230;' );
			$this->strings['process_failed']     = __( 'Theme downgrade failed.' );
			$this->strings['process_success']    = __( 'Theme downgraded successfully.' );
		}
	}

	/**
	 * Check if a child theme is being installed and we need to install its parent.
	 *
	 * Hooked to the {@see 'upgrader_post_install'} filter by Theme_Upgrader::install().
	 *
	 * @since WP-3.4.0
	 *
	 * @param bool  $install_result
	 * @param array $hook_extra
	 * @param array $child_result
	 * @return type
	 */
	public function check_parent_theme_filter( $install_result, $hook_extra, $child_result ) {
		// Check to see if we need to install a parent theme
		$theme_info = $this->theme_info();

		if ( ! $theme_info->parent() )
			return $install_result;

		$this->skin->feedback( 'parent_theme_search' );

		if ( ! $theme_info->parent()->errors() ) {
			$this->skin->feedback( 'parent_theme_currently_installed', $theme_info->parent()->display('Name'), $theme_info->parent()->display('Version') );
			// We already have the theme, fall through.
			return $install_result;
		}

		// We don't have the parent theme, let's install it.
		$api = themes_api('theme_information', array('slug' => $theme_info->get('Template'), 'fields' => array('sections' => false, 'tags' => false) ) ); //Save on a bit of bandwidth.

		if ( ! $api || is_wp_error($api) ) {
			$this->skin->feedback( 'parent_theme_not_found', $theme_info->get('Template') );
			// Don't show activate or preview actions after installation
			add_filter('install_theme_complete_actions', array($this, 'hide_activate_preview_actions') );
			return $install_result;
		}

		// Backup required data we're going to override:
		$child_api = $this->skin->api;
		$child_success_message = $this->strings['process_success'];

		// Override them
		$this->skin->api = $api;
		$this->strings['process_success_specific'] = $this->strings['parent_theme_install_success'];//, $api->name, $api->version);

		$this->skin->feedback('parent_theme_prepare_install', $api->name, $api->version);

		add_filter('install_theme_complete_actions', '__return_false', 999); // Don't show any actions after installing the theme.

		// Install the parent theme
		$parent_result = $this->run( array(
			'package' => $api->download_link,
			'destination' => get_theme_root(),
			'clear_destination' => false, //Do not overwrite files.
			'clear_working' => true
		) );

		if ( is_wp_error($parent_result) )
			add_filter('install_theme_complete_actions', array($this, 'hide_activate_preview_actions') );

		// Start cleaning up after the parents installation
		remove_filter('install_theme_complete_actions', '__return_false', 999);

		// Reset child's result and data
		$this->result = $child_result;
		$this->skin->api = $child_api;
		$this->strings['process_success'] = $child_success_message;

		return $install_result;
	}

	/**
	 * Don't display the activate and preview actions to the user.
	 *
	 * Hooked to the {@see 'install_theme_complete_actions'} filter by
	 * Theme_Upgrader::check_parent_theme_filter() when installing
	 * a child theme and installing the parent theme fails.
	 *
	 * @since WP-3.4.0
	 *
	 * @param array $actions Preview actions.
	 * @return array
	 */
	public function hide_activate_preview_actions( $actions ) {
		unset($actions['activate'], $actions['preview']);
		return $actions;
	}

	/**
	 * Install a theme package.
	 *
	 * @since WP-2.8.0
	 * @since WP-3.7.0 The `$args` parameter was added, making clearing the update cache optional.
	 *
	 * @param string $package The full local path or URI of the package.
	 * @param array  $args {
	 *     Optional. Other arguments for installing a theme package. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the updates cache if successful.
	 *                                    Default true.
	 * }
	 *
	 * @return bool|WP_Error True if the installation was successful, false or a WP_Error object otherwise.
	 */
	public function install( $package, $args = array() ) {
		$defaults = array(
			'clear_update_cache' => true,
			'overwrite_package'  => false, // Do not overwrite files.
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->install_strings();

		add_filter('upgrader_source_selection', array($this, 'check_package') );
		add_filter('upgrader_post_install', array($this, 'check_parent_theme_filter'), 10, 3);

		if ( $parsed_args['clear_update_cache'] ) {
			// Clear cache so wp_update_themes() knows about the new theme.
			add_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9, 0 );
		}

		$this->run( array(
			'package' => $package,
			'destination' => get_theme_root(),
			'clear_destination' => $parsed_args['overwrite_package'],
			'clear_working' => true,
			'hook_extra' => array(
				'type' => 'theme',
				'action' => 'install',
			),
		) );

		remove_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9 );
		remove_filter('upgrader_source_selection', array($this, 'check_package') );
		remove_filter('upgrader_post_install', array($this, 'check_parent_theme_filter'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Refresh the Theme Update information
		wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

		if ( $parsed_args['overwrite_package'] ) {
			/**
			 * Fires when the upgrader has successfully overwritten a currently installed
			 * plugin or theme with an uploaded zip package.
			 *
			 * @since WP-5.5.0
			 *
			 * @param string  $package        The package file.
			 * @param array   $new_theme_data The new theme data.
			 * @param string  $package_type   The package type (plugin or theme).
			 */
			do_action( 'upgrader_overwrote_package', $package, $this->new_theme_data, 'theme' );
		}

		return true;
	}

	/**
	 * Upgrade a theme.
	 *
	 * @since WP-2.8.0
	 * @since WP-3.7.0 The `$args` parameter was added, making clearing the update cache optional.
	 *
	 * @param string $theme The theme slug.
	 * @param array  $args {
	 *     Optional. Other arguments for upgrading a theme. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the update cache if successful.
	 *                                    Default true.
	 * }
	 * @return bool|WP_Error True if the upgrade was successful, false or a WP_Error object otherwise.
	 */
	public function upgrade( $theme, $args = array() ) {
		$defaults = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		// Is an update available?
		$current = get_site_transient( 'update_themes' );
		if ( !isset( $current->response[ $theme ] ) ) {
			$this->skin->before();
			$this->skin->set_result(false);
			$this->skin->error( 'up_to_date' );
			$this->skin->after();
			return false;
		}

		$r = $current->response[ $theme ];

		add_filter('upgrader_pre_install', array($this, 'current_before'), 10, 2);
		add_filter('upgrader_post_install', array($this, 'current_after'), 10, 2);
		add_filter('upgrader_clear_destination', array($this, 'delete_old_theme'), 10, 4);
		if ( $parsed_args['clear_update_cache'] ) {
			// Clear cache so wp_update_themes() knows about the new theme.
			add_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9, 0 );
		}

		$this->run( array(
			'package' => $r['package'],
			'destination' => get_theme_root( $theme ),
			'clear_destination' => true,
			'clear_working' => true,
			'hook_extra' => array(
				'theme' => $theme,
				'type' => 'theme',
				'action' => 'update',
			),
		) );

		remove_action( 'upgrader_process_complete', 'wp_clean_themes_cache', 9 );
		remove_filter('upgrader_pre_install', array($this, 'current_before'));
		remove_filter('upgrader_post_install', array($this, 'current_after'));
		remove_filter('upgrader_clear_destination', array($this, 'delete_old_theme'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

		return true;
	}

	/**
	 * Upgrade several themes at once.
	 *
	 * @since WP-3.0.0
	 * @since WP-3.7.0 The `$args` parameter was added, making clearing the update cache optional.
	 *
	 * @param array $themes The theme slugs.
	 * @param array $args {
	 *     Optional. Other arguments for upgrading several themes at once. Default empty array.
	 *
	 *     @type bool $clear_update_cache Whether to clear the update cache if successful.
	 *                                    Default true.
	 * }
	 * @return array[]|false An array of results, or false if unable to connect to the filesystem.
	 */
	public function bulk_upgrade( $themes, $args = array() ) {
		$defaults = array(
			'clear_update_cache' => true,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->bulk = true;
		$this->upgrade_strings();

		$current = get_site_transient( 'update_themes' );

		add_filter('upgrader_pre_install', array($this, 'current_before'), 10, 2);
		add_filter('upgrader_post_install', array($this, 'current_after'), 10, 2);
		add_filter('upgrader_clear_destination', array($this, 'delete_old_theme'), 10, 4);

		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if:
		// - running Multisite and there are one or more themes specified, OR
		// - a theme with an update available is currently in use.
		// @TODO: For multisite, maintenance mode should only kick in for individual sites if at all possible.
		$maintenance = ( is_multisite() && ! empty( $themes ) );
		foreach ( $themes as $theme )
			$maintenance = $maintenance || $theme == get_stylesheet() || $theme == get_template();
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();

		$this->update_count = count($themes);
		$this->update_current = 0;
		foreach ( $themes as $theme ) {
			$this->update_current++;

			$this->skin->theme_info = $this->theme_info($theme);

			if ( !isset( $current->response[ $theme ] ) ) {
				$this->skin->set_result(true);
				$this->skin->before();
				$this->skin->feedback( 'up_to_date' );
				$this->skin->after();
				$results[$theme] = true;
				continue;
			}

			// Get the URL to the zip file
			$r = $current->response[ $theme ];

			$result = $this->run( array(
				'package' => $r['package'],
				'destination' => get_theme_root( $theme ),
				'clear_destination' => true,
				'clear_working' => true,
				'is_multi' => true,
				'hook_extra' => array(
					'theme' => $theme
				),
			) );

			$results[$theme] = $this->result;

			// Prevent credentials auth screen from displaying multiple times
			if ( false === $result )
				break;
		} //end foreach $plugins

		$this->maintenance_mode(false);

		// Refresh the Theme Update information
		wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

		/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
		do_action( 'upgrader_process_complete', $this, array(
			'action' => 'update',
			'type' => 'theme',
			'bulk' => true,
			'themes' => $themes,
		) );

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter('upgrader_pre_install', array($this, 'current_before'));
		remove_filter('upgrader_post_install', array($this, 'current_after'));
		remove_filter('upgrader_clear_destination', array($this, 'delete_old_theme'));

		return $results;
	}

	/**
	 * Check that the package source contains a valid theme.
	 *
	 * Hooked to the {@see 'upgrader_source_selection'} filter by Theme_Upgrader::install().
	 * It will return an error if the theme doesn't have style.css or index.php
	 * files.
	 *
	 * @since WP-3.3.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 *
	 * @param string $source The full path to the package source.
	 * @return string|WP_Error The source or a WP_Error.
	 */
	public function check_package( $source ) {
		global $wp_filesystem;

		$this->new_theme_data = array();

		if ( is_wp_error($source) )
			return $source;

		// Check the folder contains a valid theme
		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit(WP_CONTENT_DIR), $source);
		if ( ! is_dir($working_directory) ) // Sanity check, if the above fails, let's not prevent installation.
			return $source;

		// A proper archive should have a style.css file in the single subdirectory
		if ( ! file_exists( $working_directory . 'style.css' ) ) {
			return new WP_Error( 'incompatible_archive_theme_no_style', $this->strings['incompatible_archive'],
				/* translators: %s: style.css */
				sprintf( __( 'The theme is missing the %s stylesheet.' ),
					'<code>style.css</code>'
				)
			);
		}

		// All these headers are needed on Theme_Installer_Skin::do_overwrite().
		$info = get_file_data(
			$working_directory . 'style.css',
			array(
				'Name'        => 'Theme Name',
				'Version'     => 'Version',
				'Author'      => 'Author',
				'Template'    => 'Template',
				'RequiresWP'  => 'Requires at least',
				'RequiresPHP' => 'Requires PHP',
			)
		);

		if ( empty( $info['Name'] ) ) {
			return new WP_Error( 'incompatible_archive_theme_no_name', $this->strings['incompatible_archive'],
				/* translators: %s: style.css */
				sprintf( __( 'The %s stylesheet doesn&#8217;t contain a valid theme header.' ),
					'<code>style.css</code>'
				)
			);
		}

		// If it's not a child theme, it must have at least an index.php to be legit.
		if ( empty( $info['Template'] ) && ! file_exists( $working_directory . 'index.php' ) ) {
			return new WP_Error( 'incompatible_archive_theme_no_index', $this->strings['incompatible_archive'],
				/* translators: %s: index.php */
				sprintf( __( 'The theme is missing the %s file.' ),
					'<code>index.php</code>'
				)
			);
		}

		$requires_php = isset( $info['RequiresPHP'] ) ? $info['RequiresPHP'] : null;
		$requires_wp  = isset( $info['RequiresWP'] ) ? $info['RequiresWP'] : null;

		if ( ! is_php_version_compatible( $requires_php ) ) {
			$error = sprintf(
				/* translators: 1: Current PHP version, 2: Version required by the uploaded theme. */
				__( 'The PHP version on your server is %1$s, however the uploaded theme requires %2$s.' ),
				phpversion(),
				$requires_php
			);

			return new WP_Error( 'incompatible_php_required_version', $this->strings['incompatible_archive'], $error );
		}
		if ( ! is_wp_version_compatible( $requires_wp ) ) {
			$error = sprintf(
				/* translators: 1: Current WordPress version, 2: Version required by the uploaded theme. */
				__( 'Your WordPress version is %1$s, however the uploaded theme requires %2$s.' ),
				$GLOBALS['wp_version'],
				$requires_wp
			);

			return new WP_Error( 'incompatible_wp_required_version', $this->strings['incompatible_archive'], $error );
		}

		$this->new_theme_data = $info;

		return $source;
	}

	/**
	 * Turn on maintenance mode before attempting to upgrade the current theme.
	 *
	 * Hooked to the {@see 'upgrader_pre_install'} filter by Theme_Upgrader::upgrade() and
	 * Theme_Upgrader::bulk_upgrade().
	 *
	 * @since WP-2.8.0
	 *
	 * @param bool|WP_Error  $return
	 * @param array          $theme
	 * @return bool|WP_Error
	 */
	public function current_before($return, $theme) {
		if ( is_wp_error($return) )
			return $return;

		$theme = isset($theme['theme']) ? $theme['theme'] : '';

		if ( $theme != get_stylesheet() ) //If not current
			return $return;
		//Change to maintenance mode now.
		if ( ! $this->bulk )
			$this->maintenance_mode(true);

		return $return;
	}

	/**
	 * Turn off maintenance mode after upgrading the current theme.
	 *
	 * Hooked to the {@see 'upgrader_post_install'} filter by Theme_Upgrader::upgrade()
	 * and Theme_Upgrader::bulk_upgrade().
	 *
	 * @since WP-2.8.0
	 *
	 * @param bool|WP_Error  $return
	 * @param array          $theme
	 * @return bool|WP_Error
	 */
	public function current_after($return, $theme) {
		if ( is_wp_error($return) )
			return $return;

		$theme = isset($theme['theme']) ? $theme['theme'] : '';

		if ( $theme != get_stylesheet() ) // If not current
			return $return;

		// Ensure stylesheet name hasn't changed after the upgrade:
		if ( $theme == get_stylesheet() && $theme != $this->result['destination_name'] ) {
			wp_clean_themes_cache();
			$stylesheet = $this->result['destination_name'];
			switch_theme( $stylesheet );
		}

		//Time to remove maintenance mode
		if ( ! $this->bulk )
			$this->maintenance_mode(false);
		return $return;
	}

	/**
	 * Delete the old theme during an upgrade.
	 *
	 * Hooked to the {@see 'upgrader_clear_destination'} filter by Theme_Upgrader::upgrade()
	 * and Theme_Upgrader::bulk_upgrade().
	 *
	 * @since WP-2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 *
	 * @param bool   $removed
	 * @param string $local_destination
	 * @param string $remote_destination
	 * @param array  $theme
	 * @return bool
	 */
	public function delete_old_theme( $removed, $local_destination, $remote_destination, $theme ) {
		global $wp_filesystem;

		if ( is_wp_error( $removed ) )
			return $removed; // Pass errors through.

		if ( ! isset( $theme['theme'] ) )
			return $removed;

		$theme = $theme['theme'];
		$themes_dir = trailingslashit( $wp_filesystem->wp_themes_dir( $theme ) );
		if ( $wp_filesystem->exists( $themes_dir . $theme ) ) {
			if ( ! $wp_filesystem->delete( $themes_dir . $theme, true ) )
				return false;
		}

		return true;
	}

	/**
	 * Get the WP_Theme object for a theme.
	 *
	 * @since WP-2.8.0
	 * @since WP-3.0.0 The `$theme` argument was added.
	 *
	 * @param string $theme The directory name of the theme. This is optional, and if not supplied,
	 *                      the directory name from the last result will be used.
	 * @return WP_Theme|false The theme's info object, or false `$theme` is not supplied
	 *                        and the last result isn't set.
	 */
	public function theme_info( $theme = null ) {
		if ( empty( $theme ) ) {
			if ( ! empty( $this->result['destination_name'] ) )
				$theme = $this->result['destination_name'];
			else
				return false;
		}

		$theme = wp_get_theme( $theme );
		$theme->cache_delete();

		return $theme;
	}

}
