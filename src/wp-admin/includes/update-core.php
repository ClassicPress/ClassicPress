<?php
/**
 * ClassicPress core upgrade functionality.
 *
 * @package ClassicPress
 * @subpackage Administration
 * @since 2.7.0
 */

/**
 * Stores files to be deleted.
 *
 * Bundled theme files should not be included in this list.
 *
 * @since 2.7.0
 *
 * @global array $_old_files
 * @var array
 * @name $_old_files
 */
global $_old_files;

$_old_files = array(
	// Added in 5.0.0, not included in ClassicPress
	'wp-admin/edit-form-blocks.php',
	'wp-admin/site-editor.php',
	'wp-admin/widget-form-blocks.php',
	'wp-includes/block-editor.php',
	'wp-includes/block-i18n.json',
	'wp-includes/block-patterns.php',
	'wp-includes/block-template-utils.php',
	'wp-includes/block-template.php',
	'wp-includes/blocks.php',
	'wp-includes/class-wp-block-editor-context.php',
	'wp-includes/class-wp-block-list.php',
	'wp-includes/class-wp-block-parser.php',
	'wp-includes/class-wp-block-pattern-categories-registry.php',
	'wp-includes/class-wp-block-patterns-registry.php',
	'wp-includes/class-wp-block-styles-registry.php',
	'wp-includes/class-wp-block-block-supports.php',
	'wp-includes/class-wp-block-template.php',
	'wp-includes/class-wp-block-type-registry.php',
	'wp-includes/class-wp-block-type.php',
	'wp-includes/class-wp-block.php',
	'wp-includes/class-wp-block-type-registry.php',
	'wp-includes/class-wp-theme-json-data.php',
	'wp-includes/class-wp-theme-json-resolver.php',
	'wp-includes/class-wp-theme-json-schema.php',
	'wp-includes/class-wp-theme-json.php',
	'wp-includes/global-styles-and-settings.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-block-directory-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-block-pattern-categories-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-block-patterns-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-block-renderer-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-block-types-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-blocks-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-edit-site-export-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-global-styles-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-pattern-directory-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-templates-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-url-details-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-widget-types-controller.php',
	'wp-includes/rest-api/endpoints/class-wp-rest-widgets-controller.php',
	'wp-includes/style-engine.php',
	'wp-includes/template-canvas.php',
	'wp-includes/theme-i18n.json',
	'wp-includes/theme-templates.php',
	'wp-includes/theme.json',
	'wp-includes/widgets/class-wp-widget-block.php',
	// Removed in 1.0.0-rc1
	'wp-admin/includes/class-wp-community-events.php',
	// 5.1
	'wp-includes/js/tinymce/wp-tinymce.js.gz',
	// Added in 5.2.3 or older, not included in ClassicPress
	'wp-includes/sodium_compat',

	// Directories.
	'wp-includes/css/dist',
	'wp-includes/block-patterns',
	'wp-includes/block-supports',
	'wp-includes/blocks',
	'wp-includes/html-api',
	'wp-includes/style-engine',
);

/**
 * Stores Requests files to be preloaded and deleted.
 *
 * For classes/interfaces, use the class/interface name
 * as the array key.
 *
 * All other files/directories should not have a key.
 *
 * @since 6.2.0
 *
 * @global array $_old_requests_files
 * @var array
 * @name $_old_requests_files
 */
global $_old_requests_files;

$_old_requests_files = array(
	// Interfaces.
	'Requests_Auth'                              => 'wp-includes/Requests/Auth.php',
	'Requests_Hooker'                            => 'wp-includes/Requests/Hooker.php',
	'Requests_Proxy'                             => 'wp-includes/Requests/Proxy.php',
	'Requests_Transport'                         => 'wp-includes/Requests/Transport.php',

	// Classes.
	'Requests_Auth_Basic'                        => 'wp-includes/Requests/Auth/Basic.php',
	'Requests_Cookie_Jar'                        => 'wp-includes/Requests/Cookie/Jar.php',
	'Requests_Exception_HTTP'                    => 'wp-includes/Requests/Exception/HTTP.php',
	'Requests_Exception_Transport'               => 'wp-includes/Requests/Exception/Transport.php',
	'Requests_Exception_HTTP_304'                => 'wp-includes/Requests/Exception/HTTP/304.php',
	'Requests_Exception_HTTP_305'                => 'wp-includes/Requests/Exception/HTTP/305.php',
	'Requests_Exception_HTTP_306'                => 'wp-includes/Requests/Exception/HTTP/306.php',
	'Requests_Exception_HTTP_400'                => 'wp-includes/Requests/Exception/HTTP/400.php',
	'Requests_Exception_HTTP_401'                => 'wp-includes/Requests/Exception/HTTP/401.php',
	'Requests_Exception_HTTP_402'                => 'wp-includes/Requests/Exception/HTTP/402.php',
	'Requests_Exception_HTTP_403'                => 'wp-includes/Requests/Exception/HTTP/403.php',
	'Requests_Exception_HTTP_404'                => 'wp-includes/Requests/Exception/HTTP/404.php',
	'Requests_Exception_HTTP_405'                => 'wp-includes/Requests/Exception/HTTP/405.php',
	'Requests_Exception_HTTP_406'                => 'wp-includes/Requests/Exception/HTTP/406.php',
	'Requests_Exception_HTTP_407'                => 'wp-includes/Requests/Exception/HTTP/407.php',
	'Requests_Exception_HTTP_408'                => 'wp-includes/Requests/Exception/HTTP/408.php',
	'Requests_Exception_HTTP_409'                => 'wp-includes/Requests/Exception/HTTP/409.php',
	'Requests_Exception_HTTP_410'                => 'wp-includes/Requests/Exception/HTTP/410.php',
	'Requests_Exception_HTTP_411'                => 'wp-includes/Requests/Exception/HTTP/411.php',
	'Requests_Exception_HTTP_412'                => 'wp-includes/Requests/Exception/HTTP/412.php',
	'Requests_Exception_HTTP_413'                => 'wp-includes/Requests/Exception/HTTP/413.php',
	'Requests_Exception_HTTP_414'                => 'wp-includes/Requests/Exception/HTTP/414.php',
	'Requests_Exception_HTTP_415'                => 'wp-includes/Requests/Exception/HTTP/415.php',
	'Requests_Exception_HTTP_416'                => 'wp-includes/Requests/Exception/HTTP/416.php',
	'Requests_Exception_HTTP_417'                => 'wp-includes/Requests/Exception/HTTP/417.php',
	'Requests_Exception_HTTP_418'                => 'wp-includes/Requests/Exception/HTTP/418.php',
	'Requests_Exception_HTTP_428'                => 'wp-includes/Requests/Exception/HTTP/428.php',
	'Requests_Exception_HTTP_429'                => 'wp-includes/Requests/Exception/HTTP/429.php',
	'Requests_Exception_HTTP_431'                => 'wp-includes/Requests/Exception/HTTP/431.php',
	'Requests_Exception_HTTP_500'                => 'wp-includes/Requests/Exception/HTTP/500.php',
	'Requests_Exception_HTTP_501'                => 'wp-includes/Requests/Exception/HTTP/501.php',
	'Requests_Exception_HTTP_502'                => 'wp-includes/Requests/Exception/HTTP/502.php',
	'Requests_Exception_HTTP_503'                => 'wp-includes/Requests/Exception/HTTP/503.php',
	'Requests_Exception_HTTP_504'                => 'wp-includes/Requests/Exception/HTTP/504.php',
	'Requests_Exception_HTTP_505'                => 'wp-includes/Requests/Exception/HTTP/505.php',
	'Requests_Exception_HTTP_511'                => 'wp-includes/Requests/Exception/HTTP/511.php',
	'Requests_Exception_HTTP_Unknown'            => 'wp-includes/Requests/Exception/HTTP/Unknown.php',
	'Requests_Exception_Transport_cURL'          => 'wp-includes/Requests/Exception/Transport/cURL.php',
	'Requests_Proxy_HTTP'                        => 'wp-includes/Requests/Proxy/HTTP.php',
	'Requests_Response_Headers'                  => 'wp-includes/Requests/Response/Headers.php',
	'Requests_Transport_cURL'                    => 'wp-includes/Requests/Transport/cURL.php',
	'Requests_Transport_fsockopen'               => 'wp-includes/Requests/Transport/fsockopen.php',
	'Requests_Utility_CaseInsensitiveDictionary' => 'wp-includes/Requests/Utility/CaseInsensitiveDictionary.php',
	'Requests_Utility_FilteredIterator'          => 'wp-includes/Requests/Utility/FilteredIterator.php',
	'Requests_Cookie'                            => 'wp-includes/Requests/Cookie.php',
	'Requests_Exception'                         => 'wp-includes/Requests/Exception.php',
	'Requests_Hooks'                             => 'wp-includes/Requests/Hooks.php',
	'Requests_IDNAEncoder'                       => 'wp-includes/Requests/IDNAEncoder.php',
	'Requests_IPv6'                              => 'wp-includes/Requests/IPv6.php',
	'Requests_IRI'                               => 'wp-includes/Requests/IRI.php',
	'Requests_Response'                          => 'wp-includes/Requests/Response.php',
	'Requests_SSL'                               => 'wp-includes/Requests/SSL.php',
	'Requests_Session'                           => 'wp-includes/Requests/Session.php',

	// Directories.
	'wp-includes/Requests/Auth/',
	'wp-includes/Requests/Cookie/',
	'wp-includes/Requests/Exception/HTTP/',
	'wp-includes/Requests/Exception/Transport/',
	'wp-includes/Requests/Exception/',
	'wp-includes/Requests/Proxy/',
	'wp-includes/Requests/Response/',
	'wp-includes/Requests/Transport/',
	'wp-includes/Requests/Utility/',
);

/**
 * Stores new files in wp-content to copy
 *
 * The contents of this array indicate any new bundled plugins/themes which
 * should be installed with the ClassicPress Upgrade. These items will not be
 * re-installed in future upgrades, this behaviour is controlled by the
 * introduced version present here being older than the current installed version.
 *
 * The content of this array should follow the following format:
 * Filename (relative to wp-content) => Introduced version
 * Directories should be noted by suffixing it with a trailing slash (/)
 *
 * @since 3.2.0
 * @since 4.7.0 New themes were not automatically installed for 4.4-4.6 on
 *              upgrade. New themes are now installed again. To disable new
 *              themes from being installed on upgrade, explicitly define
 *              CORE_UPGRADE_SKIP_NEW_BUNDLED as true.
 * @global array $_new_bundled_files
 * @var array
 * @name $_new_bundled_files
 */
global $_new_bundled_files;

$_new_bundled_files = array(
	'themes/twentyseventeen/' => '4.7',
);

/**
 * Upgrades the core of ClassicPress.
 *
 * This will create a .maintenance file at the base of the ClassicPress directory
 * to ensure that people can not access the web site, when the files are being
 * copied to their locations.
 *
 * The files in the `$_old_files` list will be removed and the new files
 * copied from the zip file after the database is upgraded.
 *
 * The files in the `$_new_bundled_files` list will be added to the installation
 * if the version is greater than or equal to the old version being upgraded.
 *
 * The steps for the upgrader for after the new release is downloaded and
 * unzipped is:
 *   1. Test unzipped location for select files to ensure that unzipped worked.
 *   2. Create the .maintenance file in current ClassicPress base.
 *   3. Copy new ClassicPress directory over old ClassicPress files.
 *   4. Upgrade ClassicPress to new version.
 *     4.1. Copy all files/folders other than wp-content
 *     4.2. Copy any language files to WP_LANG_DIR (which may differ from WP_CONTENT_DIR
 *     4.3. Copy any new bundled themes/plugins to their respective locations
 *   5. Delete new ClassicPress directory path.
 *   6. Delete .maintenance file.
 *   7. Remove old files.
 *   8. Delete 'update_core' option.
 *
 * There are several areas of failure. For instance if PHP times out before step
 * 6, then you will not be able to access any portion of your site. Also, since
 * the upgrade will not continue where it left off, you will not be able to
 * automatically remove old files and remove the 'update_core' option. This
 * isn't that bad.
 *
 * If the copy of the new ClassicPress over the old fails, then the worse is that
 * the new ClassicPress directory will remain.
 *
 * If it is assumed that every file will be copied over, including plugins and
 * themes, then if you edit the default theme, you should rename it, so that
 * your changes remain.
 *
 * @since 2.7.0
 *
 * @global WP_Filesystem_Base $wp_filesystem          WordPress filesystem subclass.
 * @global array              $_old_files
 * @global array              $_old_requests_files
 * @global array              $_new_bundled_files
 * @global wpdb               $wpdb                   WordPress database abstraction object.
 * @global string             $wp_version
 * @global string             $required_php_version
 * @global string             $required_mysql_version
 *
 * @param string $from New release unzipped path.
 * @param string $to   Path to old WordPress installation.
 * @return string|WP_Error New WordPress version on success, WP_Error on failure.
 */
function update_core( $from, $to ) {
	global $wp_filesystem, $_old_files, $_old_requests_files, $_new_bundled_files, $wpdb;

	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 );
	}

	/*
	 * Merge the old Requests files and directories into the `$_old_files`.
	 * Then preload these Requests files first, before the files are deleted
	 * and replaced to ensure the code is in memory if needed.
	 */
	$_old_files = array_merge( $_old_files, array_values( $_old_requests_files ) );
	_preload_old_requests_classes_and_interfaces( $to );

	/**
	 * Filters feedback messages displayed during the core update process.
	 *
	 * The filter is first evaluated after the zip file for the latest version
	 * has been downloaded and unzipped. It is evaluated five more times during
	 * the process:
	 *
	 * 1. Before WordPress begins the core upgrade process.
	 * 2. Before Maintenance Mode is enabled.
	 * 3. Before WordPress begins copying over the necessary files.
	 * 4. Before Maintenance Mode is disabled.
	 * 5. Before the database is upgraded.
	 *
	 * @since 2.5.0
	 *
	 * @param string $feedback The core update feedback messages.
	 */
	apply_filters( 'update_feedback', __( 'Verifying the unpacked files&#8230;' ) );

	// Sanity check the unzipped distribution.
	$distro = cp_get_update_directory_root( $from );
	if ( ! $distro ) {
		$wp_filesystem->delete( $from, true );

		return new WP_Error( 'insane_distro', __( 'The update could not be unpacked' ) );
	}

	// Import $cp_version, $wp_version, $required_php_version, and
	// $required_mysql_version from the new version.
	//
	// NOTE: These variables are NOT modified in the global scope, and this
	// function is using all variables imported from `version-current.php` in
	// the local scope!  Do not declare any of these variables as global.
	$versions_file = trailingslashit( $wp_filesystem->wp_content_dir() ) . 'upgrade/version-current.php';

	if ( ! $wp_filesystem->copy( $from . $distro . 'wp-includes/version.php', $versions_file ) ) {
		$wp_filesystem->delete( $from, true );

		return new WP_Error(
			'copy_failed_for_version_file',
			__( 'The update cannot be installed because some files could not be copied. This is usually due to inconsistent file permissions.' ),
			'wp-includes/version.php'
		);
	}

	$wp_filesystem->chmod( $versions_file, FS_CHMOD_FILE );

	/*
	 * `wp_opcache_invalidate()` only exists in WordPress 5.5 or later,
	 * so don't run it when upgrading from older versions.
	 */
	if ( function_exists( 'wp_opcache_invalidate' ) ) {
		wp_opcache_invalidate( $versions_file );
	}

	require WP_CONTENT_DIR . '/upgrade/version-current.php';
	$wp_filesystem->delete( $versions_file );

	$php_version       = PHP_VERSION;
	$mysql_version     = $wpdb->db_version();
	$old_wp_version    = $GLOBALS['wp_version']; // The version of WordPress we're updating from.
	$development_build = ( false !== strpos( $old_wp_version . $wp_version, '-' ) ); // A dash in the version indicates a development release.
	$php_compat        = version_compare( $php_version, $required_php_version, '>=' );

	if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && empty( $wpdb->is_mysql ) ) {
		$mysql_compat = true;
	} else {
		$mysql_compat = version_compare( $mysql_version, $required_mysql_version, '>=' );
	}

	if ( ! $mysql_compat || ! $php_compat ) {
		$wp_filesystem->delete( $from, true );
	}

	$php_update_message = '';

	if ( function_exists( 'wp_get_update_php_url' ) ) {
		$php_update_message = '</p><p>' . sprintf(
			/* translators: %s: URL to Update PHP page. */
			__( '<a href="%s">Learn more about updating PHP</a>.' ),
			esc_url( wp_get_update_php_url() )
		);

		if ( function_exists( 'wp_get_update_php_annotation' ) ) {
			$annotation = wp_get_update_php_annotation();

			if ( $annotation ) {
				$php_update_message .= '</p><p><em>' . $annotation . '</em>';
			}
		}
	}

	if ( ! $mysql_compat && ! $php_compat ) {
		return new WP_Error(
			'php_mysql_not_compatible',
			sprintf(
				/* translators: 1: WordPress version number, 2: Minimum required PHP version number, 3: Minimum required MySQL version number, 4: Current PHP version number, 5: Current MySQL version number. */
				__( 'The update cannot be installed because ClassicPress %1$s requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.' ),
				$wp_version,
				$required_php_version,
				$required_mysql_version,
				$php_version,
				$mysql_version
			) . $php_update_message
		);
	} elseif ( ! $php_compat ) {
		return new WP_Error(
			'php_not_compatible',
			sprintf(
				/* translators: 1: WordPress version number, 2: Minimum required PHP version number, 3: Current PHP version number. */
				__( 'The update cannot be installed because ClassicPress %1$s requires PHP version %2$s or higher. You are running version %3$s.' ),
				$wp_version,
				$required_php_version,
				$php_version
			) . $php_update_message
		);
	} elseif ( ! $mysql_compat ) {
		return new WP_Error(
			'mysql_not_compatible',
			sprintf(
				/* translators: 1: WordPress version number, 2: Minimum required MySQL version number, 3: Current MySQL version number. */
				__( 'The update cannot be installed because ClassicPress %1$s requires MySQL version %2$s or higher. You are running version %3$s.' ),
				$wp_version,
				$required_mysql_version,
				$mysql_version
			)
		);
	}

	// Add a warning when the JSON PHP extension is missing.
	if ( ! extension_loaded( 'json' ) ) {
		return new WP_Error(
			'php_not_compatible_json',
			sprintf(
				/* translators: 1: WordPress version number, 2: The PHP extension name needed. */
				__( 'The update cannot be installed because ClassicPress %1$s requires the %2$s PHP extension.' ),
				$wp_version,
				'JSON'
			)
		);
	}

	/** This filter is documented in wp-admin/includes/update-core.php */
	apply_filters( 'update_feedback', __( 'Preparing to install the latest version&#8230;' ) );

	// Don't copy wp-content, we'll deal with that below.
	// We also copy version.php last so failed updates report their old version.
	$skip              = array( 'wp-content', 'wp-includes/version.php' );
	$check_is_writable = array();

	// Check to see which files don't really need updating.  The
	// function_exists check is not necessary, but we'll keep it to preserve
	// the code structure.
	if ( function_exists( 'cp_get_core_checksums' ) ) {
		// Find the local version of the working directory.
		$working_dir_local = WP_CONTENT_DIR . '/upgrade/' . basename( $from ) . $distro;

		$checksums = cp_get_core_checksums( $cp_version );

		if ( is_array( $checksums ) && isset( $checksums[ $wp_version ] ) ) {
			$checksums = $checksums[ $wp_version ]; // Compat code for 3.7-beta2.
		}

		if ( is_array( $checksums ) ) {
			foreach ( $checksums as $file => $checksum ) {
				if ( 'wp-content' === substr( $file, 0, 10 ) ) {
					continue;
				}

				if ( ! file_exists( ABSPATH . $file ) ) {
					continue;
				}

				if ( ! file_exists( $working_dir_local . $file ) ) {
					continue;
				}

				if ( '.' === dirname( $file )
					&& in_array( pathinfo( $file, PATHINFO_EXTENSION ), array( 'html', 'txt' ), true )
				) {
					continue;
				}

				if ( md5_file( ABSPATH . $file ) === $checksum ) {
					$skip[] = $file;
				} else {
					$check_is_writable[ $file ] = ABSPATH . $file;
				}
			}
		}
	}

	// If we're using the direct method, we can predict write failures that are due to permissions.
	if ( $check_is_writable && 'direct' === $wp_filesystem->method ) {
		$files_writable = array_filter( $check_is_writable, array( $wp_filesystem, 'is_writable' ) );

		if ( $files_writable !== $check_is_writable ) {
			$files_not_writable = array_diff_key( $check_is_writable, $files_writable );

			foreach ( $files_not_writable as $relative_file_not_writable => $file_not_writable ) {
				// If the writable check failed, chmod file to 0644 and try again, same as copy_dir().
				$wp_filesystem->chmod( $file_not_writable, FS_CHMOD_FILE );

				if ( $wp_filesystem->is_writable( $file_not_writable ) ) {
					unset( $files_not_writable[ $relative_file_not_writable ] );
				}
			}

			// Store package-relative paths (the key) of non-writable files in the WP_Error object.
			$error_data = version_compare( $old_wp_version, '3.7-beta2', '>' ) ? array_keys( $files_not_writable ) : '';

			if ( $files_not_writable ) {
				return new WP_Error(
					'files_not_writable',
					__( 'The update cannot be installed because your site is unable to copy some files. This is usually due to inconsistent file permissions.' ),
					implode( ', ', $error_data )
				);
			}
		}
	}

	/** This filter is documented in wp-admin/includes/update-core.php */
	apply_filters( 'update_feedback', __( 'Enabling Maintenance mode&#8230;' ) );

	// Create maintenance file to signal that we are upgrading.
	$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
	$maintenance_file   = $to . '.maintenance';
	$wp_filesystem->delete( $maintenance_file );
	$wp_filesystem->put_contents( $maintenance_file, $maintenance_string, FS_CHMOD_FILE );

	/** This filter is documented in wp-admin/includes/update-core.php */
	apply_filters( 'update_feedback', __( 'Copying the required files&#8230;' ) );

	// Copy new versions of WP files into place.
	$result = copy_dir( $from . $distro, $to, $skip );

	if ( is_wp_error( $result ) ) {
		$result = new WP_Error(
			$result->get_error_code(),
			$result->get_error_message(),
			substr( $result->get_error_data(), strlen( $to ) )
		);
	}

	// Since we know the core files have copied over, we can now copy the version file.
	if ( ! is_wp_error( $result ) ) {
		if ( ! $wp_filesystem->copy( $from . $distro . 'wp-includes/version.php', $to . 'wp-includes/version.php', true /* overwrite */ ) ) {
			$wp_filesystem->delete( $from, true );
			$result = new WP_Error(
				'copy_failed_for_version_file',
				__( 'The update cannot be installed because your site is unable to copy some files. This is usually due to inconsistent file permissions.' ),
				'wp-includes/version.php'
			);
		}

		$wp_filesystem->chmod( $to . 'wp-includes/version.php', FS_CHMOD_FILE );

		/*
		 * `wp_opcache_invalidate()` only exists in WordPress 5.5 or later,
		 * so don't run it when upgrading from older versions.
		 */
		if ( function_exists( 'wp_opcache_invalidate' ) ) {
			wp_opcache_invalidate( $to . 'wp-includes/version.php' );
		}
	}

	// Check to make sure everything copied correctly, ignoring the contents of wp-content.
	$skip   = array( 'wp-content' );
	$failed = array();

	if ( isset( $checksums ) && is_array( $checksums ) ) {
		foreach ( $checksums as $file => $checksum ) {
			if ( 'wp-content' === substr( $file, 0, 10 ) ) {
				continue;
			}

			if ( ! file_exists( $working_dir_local . $file ) ) {
				continue;
			}

			if ( '.' === dirname( $file )
				&& in_array( pathinfo( $file, PATHINFO_EXTENSION ), array( 'html', 'txt' ), true )
			) {
				$skip[] = $file;
				continue;
			}

			if ( file_exists( ABSPATH . $file ) && md5_file( ABSPATH . $file ) === $checksum ) {
				$skip[] = $file;
			} else {
				$failed[] = $file;
			}
		}
	}

	// Some files didn't copy properly.
	if ( ! empty( $failed ) ) {
		$total_size = 0;

		foreach ( $failed as $file ) {
			if ( file_exists( $working_dir_local . $file ) ) {
				$total_size += filesize( $working_dir_local . $file );
			}
		}

		// If we don't have enough free space, it isn't worth trying again.
		// Unlikely to be hit due to the check in unzip_file().
		$available_space = function_exists( 'disk_free_space' ) ? @disk_free_space( ABSPATH ) : false;

		if ( $available_space && $total_size >= $available_space ) {
			$result = new WP_Error( 'disk_full', __( 'There is not enough free disk space to complete the update.' ) );
		} else {
			$result = copy_dir( $from . $distro, $to, $skip );

			if ( is_wp_error( $result ) ) {
				$result = new WP_Error(
					$result->get_error_code() . '_retry',
					$result->get_error_message(),
					substr( $result->get_error_data(), strlen( $to ) )
				);
			}
		}
	}

	// Custom content directory needs updating now.
	// Copy languages.
	if ( ! is_wp_error( $result ) && $wp_filesystem->is_dir( $from . $distro . 'wp-content/languages' ) ) {
		if ( WP_LANG_DIR !== ABSPATH . WPINC . '/languages' || @is_dir( WP_LANG_DIR ) ) {
			$lang_dir = WP_LANG_DIR;
		} else {
			$lang_dir = WP_CONTENT_DIR . '/languages';
		}

		// Check if the language directory exists first.
		if ( ! @is_dir( $lang_dir ) && 0 === strpos( $lang_dir, ABSPATH ) ) {
			// If it's within the ABSPATH we can handle it here, otherwise they're out of luck.
			$wp_filesystem->mkdir( $to . str_replace( ABSPATH, '', $lang_dir ), FS_CHMOD_DIR );
			clearstatcache(); // For FTP, need to clear the stat cache.
		}

		if ( @is_dir( $lang_dir ) ) {
			$wp_lang_dir = $wp_filesystem->find_folder( $lang_dir );

			if ( $wp_lang_dir ) {
				$result = copy_dir( $from . $distro . 'wp-content/languages/', $wp_lang_dir );

				if ( is_wp_error( $result ) ) {
					$result = new WP_Error(
						$result->get_error_code() . '_languages',
						$result->get_error_message(),
						substr( $result->get_error_data(), strlen( $wp_lang_dir ) )
					);
				}
			}
		}
	}

	/** This filter is documented in wp-admin/includes/update-core.php */
	apply_filters( 'update_feedback', __( 'Disabling Maintenance mode&#8230;' ) );

	// Remove maintenance file, we're done with potential site-breaking changes.
	$wp_filesystem->delete( $maintenance_file );

	// 3.5 -> 3.5+ - an empty twentytwelve directory was created upon upgrade to 3.5 for some users,
	// preventing installation of Twenty Twelve.
	if ( '3.5' === $old_wp_version ) {
		if ( is_dir( WP_CONTENT_DIR . '/themes/twentytwelve' )
			&& ! file_exists( WP_CONTENT_DIR . '/themes/twentytwelve/style.css' )
		) {
			$wp_filesystem->delete( $wp_filesystem->wp_themes_dir() . 'twentytwelve/' );
		}
	}

	/*
	 * Copy new bundled plugins & themes.
	 * This gives us the ability to install new plugins & themes bundled with
	 * future versions of WordPress whilst avoiding the re-install upon upgrade issue.
	 * $development_build controls us overwriting bundled themes and plugins when a non-stable release is being updated.
	 */
	if ( ! is_wp_error( $result )
		&& ( ! defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) || ! CORE_UPGRADE_SKIP_NEW_BUNDLED )
	) {
		foreach ( (array) $_new_bundled_files as $file => $introduced_version ) {
			// If a $development_build or if $introduced version is greater than what the site was previously running.
			if ( $development_build || version_compare( $introduced_version, $old_wp_version, '>' ) ) {
				$directory = ( '/' === $file[ strlen( $file ) - 1 ] );

				list( $type, $filename ) = explode( '/', $file, 2 );

				// Check to see if the bundled items exist before attempting to copy them.
				if ( ! $wp_filesystem->exists( $from . $distro . 'wp-content/' . $file ) ) {
					continue;
				}

				if ( 'plugins' === $type ) {
					$dest = $wp_filesystem->wp_plugins_dir();
				} elseif ( 'themes' === $type ) {
					// Back-compat, ::wp_themes_dir() did not return trailingslash'd pre-3.2.
					$dest = trailingslashit( $wp_filesystem->wp_themes_dir() );
				} else {
					continue;
				}

				if ( ! $directory ) {
					if ( ! $development_build && $wp_filesystem->exists( $dest . $filename ) ) {
						continue;
					}

					if ( ! $wp_filesystem->copy( $from . $distro . 'wp-content/' . $file, $dest . $filename, FS_CHMOD_FILE ) ) {
						$result = new WP_Error( "copy_failed_for_new_bundled_$type", __( 'Could not copy file.' ), $dest . $filename );
					}
				} else {
					if ( ! $development_build && $wp_filesystem->is_dir( $dest . $filename ) ) {
						continue;
					}

					$wp_filesystem->mkdir( $dest . $filename, FS_CHMOD_DIR );
					$_result = copy_dir( $from . $distro . 'wp-content/' . $file, $dest . $filename );

					// If a error occurs partway through this final step, keep the error flowing through, but keep process going.
					if ( is_wp_error( $_result ) ) {
						if ( ! is_wp_error( $result ) ) {
							$result = new WP_Error();
						}

						$result->add(
							$_result->get_error_code() . "_$type",
							$_result->get_error_message(),
							substr( $_result->get_error_data(), strlen( $dest ) )
						);
					}
				}
			}
		} // End foreach.
	}

	// Handle $result error from the above blocks.
	if ( is_wp_error( $result ) ) {
		$wp_filesystem->delete( $from, true );

		return $result;
	}

	// Remove old files.
	foreach ( $_old_files as $old_file ) {
		$old_file = $to . $old_file;

		if ( ! $wp_filesystem->exists( $old_file ) ) {
			continue;
		}

		// If the file isn't deleted, try writing an empty string to the file instead.
		if ( ! $wp_filesystem->delete( $old_file, true ) && $wp_filesystem->is_file( $old_file ) ) {
			$wp_filesystem->put_contents( $old_file, '' );
		}
	}

	// Remove any Genericons example.html's from the filesystem.
	_upgrade_422_remove_genericons();

	// Deactivate the REST API plugin if its version is 2.0 Beta 4 or lower.
	_upgrade_440_force_deactivate_incompatible_plugins();

	// Upgrade DB with separate request.
	/** This filter is documented in wp-admin/includes/update-core.php */
	apply_filters( 'update_feedback', __( 'Upgrading database&#8230;' ) );

	$db_upgrade_url = admin_url( 'upgrade.php?step=upgrade_db' );
	wp_remote_post( $db_upgrade_url, array( 'timeout' => 60 ) );

	// Clear the cache to prevent an update_option() from saving a stale db_version to the cache.
	wp_cache_flush();
	// Not all cache back ends listen to 'flush'.
	wp_cache_delete( 'alloptions', 'options' );

	// Remove working directory.
	$wp_filesystem->delete( $from, true );

	// Force refresh of update information.
	if ( function_exists( 'delete_site_transient' ) ) {
		delete_site_transient( 'update_core' );
	} else {
		delete_option( 'update_core' );
	}

	/**
	 * Fires after WordPress core has been successfully updated.
	 *
	 * @since 3.3.0
	 *
	 * @param string $wp_version The current WordPress version.
	 */
	do_action( '_core_updated_successfully', $wp_version );

	// Clear the option that blocks auto-updates after failures, now that we've been successful.
	if ( function_exists( 'delete_site_option' ) ) {
		delete_site_option( 'auto_core_update_failed' );
	}

	return $cp_version;
}

/**
 * Preloads old Requests classes and interfaces.
 *
 * This function preloads the old Requests code into memory before the
 * upgrade process deletes the files. Why? Requests code is loaded into
 * memory via an autoloader, meaning when a class or interface is needed
 * If a request is in process, Requests could attempt to access code. If
 * the file is not there, a fatal error could occur. If the file was
 * replaced, the new code is not compatible with the old, resulting in
 * a fatal error. Preloading ensures the code is in memory before the
 * code is updated.
 *
 * @since 6.2.0
 *
 * @global array              $_old_requests_files Requests files to be preloaded.
 * @global WP_Filesystem_Base $wp_filesystem       WordPress filesystem subclass.
 * @global string             $wp_version          The WordPress version string.
 *
 * @param string $to Path to old WordPress installation.
 */
function _preload_old_requests_classes_and_interfaces( $to ) {
	global $_old_requests_files, $wp_filesystem, $wp_version;

	/*
	 * Requests was introduced in WordPress 4.6.
	 *
	 * Skip preloading if the website was previously using
	 * an earlier version of WordPress.
	 */
	if ( version_compare( $wp_version, '4.6', '<' ) ) {
		return;
	}

	if ( ! defined( 'REQUESTS_SILENCE_PSR0_DEPRECATIONS' ) ) {
		define( 'REQUESTS_SILENCE_PSR0_DEPRECATIONS', true );
	}

	foreach ( $_old_requests_files as $name => $file ) {
		// Skip files that aren't interfaces or classes.
		if ( is_int( $name ) ) {
			continue;
		}

		// Skip if it's already loaded.
		if ( class_exists( $name ) || interface_exists( $name ) ) {
			continue;
		}

		// Skip if the file is missing.
		if ( ! $wp_filesystem->is_file( $to . $file ) ) {
			continue;
		}

		require_once $to . $file;
	}
}

/**
 * Redirect to the About ClassicPress page after a successful upgrade.
 *
 * This function is only needed when the existing installation is older than 3.4.0.
 *
 * @since 3.3.0
 *
 * @global string $wp_version The WordPress version string.
 * @global string $pagenow    The filename of the current screen.
 * @global string $action
 *
 * @param string $new_version
 */
function _redirect_to_about_wordpress( $new_version ) {
	global $wp_version, $pagenow, $action;

	if ( version_compare( $wp_version, '3.4-RC1', '>=' ) ) {
		return;
	}

	// Ensure we only run this on the update-core.php page. The Core_Upgrader may be used in other contexts.
	if ( 'update-core.php' !== $pagenow ) {
		return;
	}

	if ( 'do-core-upgrade' !== $action && 'do-core-reinstall' !== $action ) {
		return;
	}

	// Load the updated default text localization domain for new strings.
	load_default_textdomain();

	// See do_core_upgrade()
	show_message( __( 'ClassicPress updated successfully' ) );

	// self_admin_url() won't exist when upgrading from <= 3.0, so relative URLs are intentional.
	show_message(
		'<span class="hide-if-no-js">' . sprintf(
			/* translators: 1: ClassicPress version, 2: URL to About screen. */
			__( 'Welcome to ClassicPress %1$s. You will be redirected to the About ClassicPress screen. If not, click <a href="%2$s">here</a>.' ),
			$new_version,
			'about.php?updated'
		) . '</span>'
	);
	show_message(
		'<span class="hide-if-js">' . sprintf(
			/* translators: 1: ClassicPress version, 2: URL to About screen. */
			__( 'Welcome to ClassicPress %1$s. <a href="%2$s">Learn more</a>.' ),
			$new_version,
			'about.php?updated'
		) . '</span>'
	);
	echo '</div>';
	?>
<script>
window.location = 'about.php?updated';
</script>
	<?php

	// Include admin-footer.php and exit.
	require_once ABSPATH . 'wp-admin/admin-footer.php';
	exit;
}

/**
 * Cleans up Genericons example files.
 *
 * @since 4.2.2
 *
 * @global array              $wp_theme_directories
 * @global WP_Filesystem_Base $wp_filesystem
 */
function _upgrade_422_remove_genericons() {
	global $wp_theme_directories, $wp_filesystem;

	// A list of the affected files using the filesystem absolute paths.
	$affected_files = array();

	// Themes.
	foreach ( $wp_theme_directories as $directory ) {
		$affected_theme_files = _upgrade_422_find_genericons_files_in_folder( $directory );
		$affected_files       = array_merge( $affected_files, $affected_theme_files );
	}

	// Plugins.
	$affected_plugin_files = _upgrade_422_find_genericons_files_in_folder( WP_PLUGIN_DIR );
	$affected_files        = array_merge( $affected_files, $affected_plugin_files );

	foreach ( $affected_files as $file ) {
		$gen_dir = $wp_filesystem->find_folder( trailingslashit( dirname( $file ) ) );

		if ( empty( $gen_dir ) ) {
			continue;
		}

		// The path when the file is accessed via WP_Filesystem may differ in the case of FTP.
		$remote_file = $gen_dir . basename( $file );

		if ( ! $wp_filesystem->exists( $remote_file ) ) {
			continue;
		}

		if ( ! $wp_filesystem->delete( $remote_file, false, 'f' ) ) {
			$wp_filesystem->put_contents( $remote_file, '' );
		}
	}
}

/**
 * Recursively find Genericons example files in a given folder.
 *
 * @ignore
 * @since 4.2.2
 *
 * @param string $directory Directory path. Expects trailingslashed.
 * @return array
 */
function _upgrade_422_find_genericons_files_in_folder( $directory ) {
	$directory = trailingslashit( $directory );
	$files     = array();

	if ( file_exists( "{$directory}example.html" )
		&& false !== strpos( file_get_contents( "{$directory}example.html" ), '<title>Genericons</title>' )
	) {
		$files[] = "{$directory}example.html";
	}

	$dirs = glob( $directory . '*', GLOB_ONLYDIR );
	$dirs = array_filter(
		$dirs,
		static function ( $dir ) {
			// Skip any node_modules directories.
			return false === strpos( $dir, 'node_modules' );
		}
	);

	if ( $dirs ) {
		foreach ( $dirs as $dir ) {
			$files = array_merge( $files, _upgrade_422_find_genericons_files_in_folder( $dir ) );
		}
	}

	return $files;
}

/**
 * @ignore
 * @since 4.4.0
 */
function _upgrade_440_force_deactivate_incompatible_plugins() {
	if ( defined( 'REST_API_VERSION' ) && version_compare( REST_API_VERSION, '2.0-beta4', '<=' ) ) {
		deactivate_plugins( array( 'rest-api/plugin.php' ), true );
	}
}

/**
 * Gets the checksums for the given version of ClassicPress.
 *
 * This function is a duplicate copy of `get_core_checksums()` to ensure the
 * new code is loaded and used when updating from a pre-1.3.0 version.
 *
 * @since CP-1.3.0
 *
 * @param string $version Version string to query.
 * @return bool|array False on failure. An array of checksums on success.
 */
function cp_get_core_checksums( $version ) {
	$url = 'https://api-v1.classicpress.net/checksums/md5/' . $version . '.json';

	$options = array(
		'timeout' => wp_doing_cron() ? 30 : 3,
	);

	$response = wp_remote_get( $url, $options );

	if ( is_wp_error( $response ) ) {
		trigger_error(
			sprintf(
				/* translators: %s: support forums URL */
				__( 'An unexpected error occurred. Something may be wrong with ClassicPress.net or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
				__( 'https://forums.classicpress.net/c/support' )
			) . ' ' . __( '(ClassicPress could not establish a secure connection to ClassicPress.net. Please contact your server administrator.)' ),
			headers_sent() || WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
		);

		// Retry request
		$response = wp_remote_get( $url, $options );
	}

	if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
		return false;
	}

	$body = trim( wp_remote_retrieve_body( $response ) );
	$body = json_decode( $body, true );

	if (
		! is_array( $body ) ||
		! isset( $body['checksums'] ) ||
		! is_array( $body['checksums'] )
	) {
		return false;
	}

	return $body['checksums'];
}

/**
 * Verifies and returns the root directory entry of a ClassicPress update.
 *
 * For WordPress, this was always '/wordpress/'.  For ClassicPress, since
 * GitHub builds our zip packages for us, the zip file (and therefore the
 * directory where it was unpacked) will contain a single directory entry whose
 * name starts with 'ClassicPress-'.
 *
 * We also need to allow the root directory to be called 'wordpress', since
 * this is used when migrating from WordPress to ClassicPress.  If the
 * directory is named otherwise, the WordPress updater will reject the update
 * package for the migration.
 *
 * NOTE: This function is duplicated in class-core-upgrader.php.  This
 * duplication is intentional, as the load order during an upgrade is quite
 * complicated and this is the simplest way to make sure that this code is
 * always available.
 *
 * @since CP-1.0.0
 *
 * @param string $working_dir The directory where a ClassicPress update package
 *                            has been extracted.
 *
 * @return string|null The root directory entry that contains the new files, or
 *                     `null` if this does not look like a valid update.
 */
function cp_get_update_directory_root( $working_dir ) {
	global $wp_filesystem;

	$distro  = null;
	$entries = array_values( $wp_filesystem->dirlist( $working_dir ) );

	if (
		count( $entries ) === 1 &&
		(
			substr( $entries[0]['name'], 0, 13 ) === 'ClassicPress-' ||
			$entries[0]['name'] === 'wordpress' // migration build
		) &&
		$entries[0]['type'] === 'd'
	) {
		$distro = '/' . $entries[0]['name'] . '/';
		$root   = $working_dir . $distro;
		if (
			! $wp_filesystem->exists( $root . 'readme.html' ) ||
			! $wp_filesystem->exists( $root . 'wp-includes/version.php' )
		) {
			$distro = null;
		}
	}

	return $distro;
}
