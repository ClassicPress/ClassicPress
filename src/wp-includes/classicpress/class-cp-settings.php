<?php
/**
 * ClassicPress Settings class
 *
 * @package ClassicPress
 * @subpackage admin
 * @since CP-2.7.0
 */

class CP_Settings {
	public function __construct() {
		add_action( 'update_option_cp_object_cache', array( $this, 'cp_apcu_cache_option_change' ), 10, 3 );
		add_action( 'add_option_cp_object_cache', array( $this, 'cp_apcu_cache_option_add' ), 10, 1 );
	}

	/**
	 * Trigger code to add or remove object cache handler file on add setting
	 *
	 * @since CP-2.7.0
	 *
	 */
	public function cp_apcu_cache_option_add( $option_name ) {
		if ( $option_name === 'cp_object_cache' ) {
			$this->_cp_maybe_install_apcu_object_cache();
		}
	}

	/**
	 * Trigger code to add or remove object cache handler file on setting change
	 *
	 * @since CP-2.7.0
	 *
	 */
	public function cp_apcu_cache_option_change( $old_value, $new_value, $option_name ) {
		if ( $option_name === 'cp_object_cache' && $new_value !== $old_value ) {
			$this->_cp_maybe_install_apcu_object_cache();
		}
	}

	/**
	 * Installs an object-cache.php file, if one does not already exist, to
	 * make use of the APCu extension as an external object cache.
	 *
	 * @since CP-2.7.0
	 *
	 * @access private
	 */
	public function _cp_maybe_install_apcu_object_cache() {

		// Abort if the apcu extension is not installed.
		if ( ! extension_loaded( 'apcu' ) ) {
			return;
		}

		// Setup the filesystem abstraction
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			WP_Filesystem();
		}

		// Define wp-content/object-cache.php path.
		$wp_content_dir    = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$object_cache_file = $wp_content_dir . '/object-cache.php';

		// Remove object-cache.php file if set by ClassicPress and the user requests.
		$cp_object_cache = absint( get_option( 'cp_object_cache' ) );
		if ( 0 === $cp_object_cache ) {

			// Check if object-cache.php exists using $wp_filesystem.
			if ( $wp_filesystem->exists( $object_cache_file ) ) {

				// Match the header comment block
				$file_data = file_get_contents( $object_cache_file );
				if ( preg_match( '/\/\*.*?\*\//s', $file_data, $matches ) ) {
					$header_block = $matches[0];

					// Look for the Plugin Name line
					if ( preg_match( '/Plugin Name:\s*(.*)/i', $header_block, $plugin_name_match ) ) {
						$plugin_name = trim( $plugin_name_match[1] );

						// Compare to your target string
						if ( $plugin_name === 'WordPress APCu Object Cache Backend' ) {

							// Match found, so clear the object cache ...
							if ( function_exists( 'apcu_clear_cache' ) ) {
								apcu_clear_cache();
							}

							// ... and delete the object cache file.
							$success = $wp_filesystem->delete( $object_cache_file );
							if ( ! $success ) {
								return new WP_Error( 'APCu-cache-file-error', __( 'Failed to delete the file: ' ) . $object_cache_file );
							}
						}
					}
				}
			}
		} else { // Otherwise install object cache

			// Check if object-cache.php exists using $wp_filesystem.
			if ( ! $wp_filesystem->exists( $object_cache_file ) ) {
				$source_file = ABSPATH . WPINC . '/object-cache.php';
				if ( $wp_filesystem->exists( $source_file ) ) {

					// Copy and paste the file using the WP_Filesystem
					if ( ! $wp_filesystem->copy( $source_file, $object_cache_file ) ) {
						return new WP_Error( 'APCu-cache-file-error', __( 'Failed to copy object-cache.php to wp-content folder.' ) );
					}
				}
			}
		}
	}
}

$cp_settings = new CP_Settings();
