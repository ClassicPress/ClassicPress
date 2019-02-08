<?php
/**
 * Upgrade API: Core_Upgrader class
 *
 * @package ClassicPress
 * @subpackage Upgrader
 * @since WP-4.6.0
 */

/**
 * Core class used for updating core.
 *
 * It allows for ClassicPress to upgrade itself in combination with
 * the wp-admin/includes/update-core.php file.
 *
 * @since WP-2.8.0
 * @since WP-4.6.0 Moved to its own file from wp-admin/includes/class-wp-upgrader.php.
 *
 * @see WP_Upgrader
 */
class Core_Upgrader extends WP_Upgrader {

	/**
	 * Initialize the upgrade strings.
	 *
	 * @since WP-2.8.0
	 */
	public function upgrade_strings() {
		$this->strings['up_to_date'] = __( 'ClassicPress is at the latest version.' );
		$this->strings['locked'] = __( 'Another update was started but has not completed yet.' );
		$this->strings['no_package'] = __( 'Update package not available.' );
		/* translators: %s: package URL */
		$this->strings['downloading_package'] = sprintf( __( 'Downloading update from %s&#8230;' ), '<span class="code">%s</span>' );
		$this->strings['unpack_package'] = __( 'Unpacking the update&#8230;' );
		$this->strings['copy_failed'] = __( 'Could not copy files.' );
		$this->strings['copy_failed_space'] = __( 'Could not copy files. You may have run out of disk space.'  );
		$this->strings['start_rollback'] = __( 'Attempting to roll back to previous version.' );
		$this->strings['rollback_was_required'] = __( 'Due to an error during updating, ClassicPress has rolled back to your previous version.' );
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
	 * NOTE: This function is duplicated in includes/update-core.php.  This
	 * duplication is intentional, as the load order during an upgrade is quite
	 * complicated and this is the simplest way to make sure that this code is
	 * always available.
	 *
	 * @since 1.0.0-beta1
	 *
	 * @param string $working_dir The directory where a ClassicPress update package
	 *                            has been extracted.
	 *
	 * @return string|null The root directory entry that contains the new files, or
	 *                     `null` if this does not look like a valid update.
	 */
	public static function get_update_directory_root( $working_dir ) {
		global $wp_filesystem;

		$distro = null;
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
			$root = $working_dir . $distro;
			if (
				! $wp_filesystem->exists( $root . 'readme.html' ) ||
				! $wp_filesystem->exists( $root . 'wp-includes/version.php' )
			) {
				$distro = null;
			}
		}

		return $distro;
	}

	/**
	 * Upgrade ClassicPress core.
	 *
	 * @since WP-2.8.0
	 *
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 * @global callable           $_wp_filesystem_direct_method
	 *
	 * @param object $current Response object for whether ClassicPress is current.
	 * @param array  $args {
	 *        Optional. Arguments for upgrading ClassicPress core. Default empty array.
	 *
	 *        @type bool $pre_check_md5    Whether to check the file checksums before
	 *                                     attempting the upgrade. Default true.
	 *        @type bool $attempt_rollback Whether to attempt to rollback the chances if
	 *                                     there is a problem. Default false.
	 *        @type bool $do_rollback      Whether to perform this "upgrade" as a rollback.
	 *                                     Default false.
	 * }
	 * @return null|false|WP_Error False or WP_Error on failure, null on success.
	 */
	public function upgrade( $current, $args = array() ) {
		global $wp_filesystem;

		include( ABSPATH . WPINC . '/version.php' ); // $wp_version;

		$start_time = time();

		$defaults = array(
			'pre_check_md5'    => true,
			'attempt_rollback' => false,
			'do_rollback'      => false,
			'allow_relaxed_file_ownership' => false,
		);
		$parsed_args = wp_parse_args( $args, $defaults );

		$this->init();
		$this->upgrade_strings();

		// Is an update available?
		if ( !isset( $current->response ) || $current->response == 'latest' )
			return new WP_Error('up_to_date', $this->strings['up_to_date']);

		$res = $this->fs_connect( array( ABSPATH, WP_CONTENT_DIR ), $parsed_args['allow_relaxed_file_ownership'] );
		if ( ! $res || is_wp_error( $res ) ) {
			return $res;
		}

		$wp_dir = trailingslashit($wp_filesystem->abspath());

		$partial = true;
		if ( $parsed_args['do_rollback'] )
			$partial = false;
		elseif ( $parsed_args['pre_check_md5'] && ! $this->check_files() )
			$partial = false;

		/*
		 * If partial update is returned from the API, use that, unless we're doing
		 * a reinstallation. If we cross the new_bundled version number, then use
		 * the new_bundled zip. Don't though if the constant is set to skip bundled items.
		 * If the API returns a no_content zip, go with it. Finally, default to the full zip.
		 */
		if ( $parsed_args['do_rollback'] && $current->packages->rollback )
			$to_download = 'rollback';
		elseif ( $current->packages->partial && 'reinstall' != $current->response && $wp_version == $current->partial_version && $partial )
			$to_download = 'partial';
		elseif ( $current->packages->new_bundled && version_compare( $wp_version, $current->new_bundled, '<' )
			&& ( ! defined( 'CORE_UPGRADE_SKIP_NEW_BUNDLED' ) || ! CORE_UPGRADE_SKIP_NEW_BUNDLED ) )
			$to_download = 'new_bundled';
		elseif ( $current->packages->no_content )
			$to_download = 'no_content';
		else
			$to_download = 'full';

		// Lock to prevent multiple Core Updates occurring
		$lock = WP_Upgrader::create_lock( 'core_updater', 15 * MINUTE_IN_SECONDS );
		if ( ! $lock ) {
			return new WP_Error( 'locked', $this->strings['locked'] );
		}

		$download = $this->download_package( $current->packages->$to_download );
		if ( is_wp_error( $download ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $download;
		}

		$working_dir = $this->unpack_package( $download );
		if ( is_wp_error( $working_dir ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return $working_dir;
		}

		// Copy update-core.php from the new version into place.
		$distro = self::get_update_directory_root( $working_dir );
		if ( ! $wp_filesystem->copy(
			$working_dir . $distro . 'wp-admin/includes/update-core.php',
			$wp_dir . 'wp-admin/includes/update-core.php',
			true
		) ) {
			$wp_filesystem->delete($working_dir, true);
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_for_update_core_file', __( 'The update cannot be installed because we will be unable to copy some files. This is usually due to inconsistent file permissions.' ), 'wp-admin/includes/update-core.php' );
		}
		$wp_filesystem->chmod($wp_dir . 'wp-admin/includes/update-core.php', FS_CHMOD_FILE);

		require_once( ABSPATH . 'wp-admin/includes/update-core.php' );

		if ( ! function_exists( 'update_core' ) ) {
			WP_Upgrader::release_lock( 'core_updater' );
			return new WP_Error( 'copy_failed_space', $this->strings['copy_failed_space'] );
		}

		$result = update_core( $working_dir, $wp_dir );

		// In the event of an issue, we may be able to roll back.
		if ( $parsed_args['attempt_rollback'] && $current->packages->rollback && ! $parsed_args['do_rollback'] ) {
			$try_rollback = false;
			if ( is_wp_error( $result ) ) {
				$error_code = $result->get_error_code();
				/*
				 * Not all errors are equal. These codes are critical: copy_failed__copy_dir,
				 * mkdir_failed__copy_dir, copy_failed__copy_dir_retry, and disk_full.
				 * do_rollback allows for update_core() to trigger a rollback if needed.
				 */
				if ( false !== strpos( $error_code, 'do_rollback' ) )
					$try_rollback = true;
				elseif ( false !== strpos( $error_code, '__copy_dir' ) )
					$try_rollback = true;
				elseif ( 'disk_full' === $error_code )
					$try_rollback = true;
			}

			if ( $try_rollback ) {
				/** This filter is documented in wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $result );

				/** This filter is documented in wp-admin/includes/update-core.php */
				apply_filters( 'update_feedback', $this->strings['start_rollback'] );

				$rollback_result = $this->upgrade( $current, array_merge( $parsed_args, array( 'do_rollback' => true ) ) );

				$original_result = $result;
				$result = new WP_Error( 'rollback_was_required', $this->strings['rollback_was_required'], (object) array( 'update' => $original_result, 'rollback' => $rollback_result ) );
			}
		}

		/** This action is documented in wp-admin/includes/class-wp-upgrader.php */
		do_action( 'upgrader_process_complete', $this, array( 'action' => 'update', 'type' => 'core' ) );

		// Clear the current updates
		delete_site_transient( 'update_core' );

		if ( ! $parsed_args['do_rollback'] ) {
			$stats = array(
				'update_type'      => $current->response,
				'success'          => true,
				'fs_method'        => $wp_filesystem->method,
				'fs_method_forced' => defined( 'FS_METHOD' ) || has_filter( 'filesystem_method' ),
				'fs_method_direct' => !empty( $GLOBALS['_wp_filesystem_direct_method'] ) ? $GLOBALS['_wp_filesystem_direct_method'] : '',
				'time_taken'       => time() - $start_time,
				'reported'         => $wp_version,
				'attempted'        => $current->version,
			);

			if ( is_wp_error( $result ) ) {
				$stats['success'] = false;
				// Did a rollback occur?
				if ( ! empty( $try_rollback ) ) {
					$stats['error_code'] = $original_result->get_error_code();
					$stats['error_data'] = $original_result->get_error_data();
					// Was the rollback successful? If not, collect its error too.
					$stats['rollback'] = ! is_wp_error( $rollback_result );
					if ( is_wp_error( $rollback_result ) ) {
						$stats['rollback_code'] = $rollback_result->get_error_code();
						$stats['rollback_data'] = $rollback_result->get_error_data();
					}
				} else {
					$stats['error_code'] = $result->get_error_code();
					$stats['error_data'] = $result->get_error_data();
				}
			}

			wp_version_check( $stats );
		}

		WP_Upgrader::release_lock( 'core_updater' );

		return $result;
	}

	/**
	 * Determines if the current ClassicPress Core version should update to an
	 * offered version or not.
	 *
	 * @since WP-3.7.0
	 *
	 * @static
	 *
	 * @param string $offered_ver The offered version, of the format x.y.z.
	 * @return bool True if we should update to the offered version, otherwise false.
	 */
	public static function should_update_to_version( $offered_ver ) {
		include( ABSPATH . WPINC . '/version.php' ); // $cp_version; // x.y.z

		return self::auto_update_enabled_for_versions(
			$cp_version,
			$offered_ver,
			defined( 'WP_AUTO_UPDATE_CORE' ) ? WP_AUTO_UPDATE_CORE : null
		);
	}

	/**
	 * Determines if an automatic update should be applied for a given set of
	 * versions and auto-update constants.
	 *
	 * @since 1.0.0-rc1
	 *
	 * @param string $cp_version       The current version of ClassicPress.
	 * @param string $offered_ver      The proposed version of ClassicPress.
	 * @param mixed  $auto_update_core The automatic update settings (the value
	 *                                 of the WP_AUTO_UPDATE_CORE constant).
	 * @return bool Whether to apply the proposed update automatically.
	 */
	public static function auto_update_enabled_for_versions(
		$cp_version,
		$offered_ver,
		$auto_update_core
	) {
		// 1: If we're already on that version, not much point in updating?
		if ( $offered_ver == $cp_version ) {
			return false;
		}

		// 2: If we're running a newer version, that's a nope
		if ( version_compare( $cp_version, $offered_ver, '>' ) ) {
			return false;
		}

		$failure_data = get_site_option( 'auto_core_update_failed' );
		if ( $failure_data ) {
			// If this was a critical update failure, cannot update.
			if ( ! empty( $failure_data['critical'] ) ) {
				return false;
			}

			// Don't claim we can update on update-core.php if we have a
			// non-critical failure logged.
			if (
				$cp_version == $failure_data['current'] &&
				false !== strpos( $offered_ver, '.1.next.minor' )
			) {
				return false;
			}

			// Cannot update if we're retrying the same A to B update that
			// caused a non-critical failure.  Some non-critical failures do
			// allow retries, like download_failed.  WP-3.7.1 => WP-3.7.2
			// resulted in files_not_writable, if we are still on WP-3.7.1 and
			// still trying to update to WP-3.7.2.
			if (
				empty( $failure_data['retry'] ) &&
				$cp_version == $failure_data['current'] &&
				$offered_ver == $failure_data['attempted']
			) {
				return false;
			}
		}

		// that concludes all the sanity checks, now we can do some work

		$ver_current = preg_split( '/[.\+-]/', $cp_version  );
		$ver_offered = preg_split( '/[.\+-]/', $offered_ver );

		// Defaults:
		$upgrade_night = true;
		$upgrade_patch = true;
		$upgrade_minor = true;

		// WP_AUTO_UPDATE_CORE = true (all), 'minor', false.
		if ( ! is_null( $auto_update_core ) ) {
			if ( false === $auto_update_core ) {
				// Defaults to turned off, unless a filter allows it
				$upgrade_night = $upgrade_patch = $upgrade_minor = false;
			} elseif ( true === $auto_update_core ) {
				// default
			} elseif ( 'minor' === $auto_update_core ) {
				// Only minor updates for core
				$upgrade_patch = false;
			} elseif ( 'patch' == $auto_update_core ) {
				// Only patch updates for core
				$upgrade_minor = false;
			}
		}

		// 3: 1.0.0-beta2+nightly.20181019 -> 1.0.0-beta2+nightly.20181020
		if ( strpos( $cp_version, 'nightly' ) ) {
			$bld_current = intval( $ver_current[ count( $ver_current ) - 1 ] );
			$bld_offered = intval( $ver_offered[ count( $ver_offered ) - 1 ] );

			// we don't care about any of the other version parts
			if ( $bld_current < $bld_offered ) {
				/**
				 * Filters whether to enable automatic core updates for nightly releases.
				 *
				 * @since 1.0.0-rc1
				 *
				 * @param bool $upgrade_nightly Whether to enable automatic updates for
				 *                              nightly releases.
				 */
				return apply_filters( 'allow_nightly_auto_core_updates', $upgrade_night );
			} else {
				return false;
			}
		}

		if ( count($ver_current) == count($ver_offered) && $ver_current < $ver_offered ) {
			// updating at the same level - this is the 99% case

			// Major version updates (1.2.3 -> 2.0.0, 1.2.3 -> 2.3.4)
			if ( $ver_current[0] < $ver_offered[0] ) {
				/**
				 * Filters whether to enable major automatic core updates.
				 *
				 * @since 1.0.0-rc1 Hard-code 'false' - should never auto-update major versions
				 * @since WP-3.7.0
				 *
				 * @param bool $upgrade_major Whether to enable major automatic core updates.
				 */
				return apply_filters( 'allow_major_auto_core_updates', false );
			}

			// Minor updates (1.1.3 -> 1.2.0)
			if ( $ver_current[1] < $ver_offered[1] ) {
				/**
				 * Filters whether to enable minor automatic core updates.
				 *
				 * @since WP-3.7.0
				 *
				 * @param bool $upgrade_minor Whether to enable minor automatic core updates.
				 */
				return apply_filters( 'allow_minor_auto_core_updates', $upgrade_minor );
			}

			// Patch updates (1.0.0 -> 1.0.1 -> 1.0.2)
			if ( $ver_current[2]  < $ver_offered[2] ) {
				/**
				 * Filters whether to enable pitch automatic core updates.
				 *
				 * @since 1.0.0-rc1
				 *
				 * @param bool $upgrade_patch Whether to enable patch automatic core updates.
				 */
				return apply_filters( 'allow_patch_auto_core_updates', $upgrade_patch );
			}

			/**
			 * Filters whether to enable pre-release automatic core updates.
			 *
			 * @since 1.0.0-rc1
			 *
			 * @param bool $upgrade_patch Whether to enable pre-release automatic core updates.
			 */
			return apply_filters( 'allow_prerelease_auto_core_updates', false );

		} else {
			// if we're here we're dealing with pre-to-rel or rel-to-pre
		}


		// If we're still not sure, we don't want it
		return false;
	}

	/**
	 * Compare the disk file checksums against the expected checksums.
	 *
	 * @since WP-3.7.0
	 *
	 * @global string $wp_version
	 * @global string $wp_local_package
	 *
	 * @return bool True if the checksums match, otherwise false.
	 */
	public function check_files() {
		global $wp_version, $wp_local_package;

		$checksums = get_core_checksums( $wp_version, isset( $wp_local_package ) ? $wp_local_package : 'en_US' );

		if ( ! is_array( $checksums ) )
			return false;

		foreach ( $checksums as $file => $checksum ) {
			// Skip files which get updated
			if ( 'wp-content' == substr( $file, 0, 10 ) )
				continue;
			if ( ! file_exists( ABSPATH . $file ) || md5_file( ABSPATH . $file ) !== $checksum )
				return false;
		}

		return true;
	}
}
