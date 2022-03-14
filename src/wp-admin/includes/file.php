<?php
/**
 * Filesystem API: Top-level functionality
 *
 * Functions for reading, writing, modifying, and deleting files on the file system.
 * Includes functionality for theme-specific files as well as operations for uploading,
 * archiving, and rendering output when necessary.
 *
 * @package ClassicPress
 * @subpackage Filesystem
 * @since WP-2.3.0
 */

/** The descriptions for theme files. */
$wp_file_descriptions = array(
	'functions.php'         => __( 'Theme Functions' ),
	'header.php'            => __( 'Theme Header' ),
	'footer.php'            => __( 'Theme Footer' ),
	'sidebar.php'           => __( 'Sidebar' ),
	'comments.php'          => __( 'Comments' ),
	'searchform.php'        => __( 'Search Form' ),
	'404.php'               => __( '404 Template' ),
	'link.php'              => __( 'Links Template' ),
	// Archives
	'index.php'             => __( 'Main Index Template' ),
	'archive.php'           => __( 'Archives' ),
	'author.php'            => __( 'Author Template' ),
	'taxonomy.php'          => __( 'Taxonomy Template' ),
	'category.php'          => __( 'Category Template' ),
	'tag.php'               => __( 'Tag Template' ),
	'home.php'              => __( 'Posts Page' ),
	'search.php'            => __( 'Search Results' ),
	'date.php'              => __( 'Date Template' ),
	// Content
	'singular.php'          => __( 'Singular Template' ),
	'single.php'            => __( 'Single Post' ),
	'page.php'              => __( 'Single Page' ),
	'front-page.php'        => __( 'Homepage' ),
	// Attachments
	'attachment.php'        => __( 'Attachment Template' ),
	'image.php'             => __( 'Image Attachment Template' ),
	'video.php'             => __( 'Video Attachment Template' ),
	'audio.php'             => __( 'Audio Attachment Template' ),
	'application.php'       => __( 'Application Attachment Template' ),
	// Embeds
	'embed.php'             => __( 'Embed Template' ),
	'embed-404.php'         => __( 'Embed 404 Template' ),
	'embed-content.php'     => __( 'Embed Content Template' ),
	'header-embed.php'      => __( 'Embed Header Template' ),
	'footer-embed.php'      => __( 'Embed Footer Template' ),
	// Stylesheets
	'style.css'             => __( 'Stylesheet' ),
	'editor-style.css'      => __( 'Visual Editor Stylesheet' ),
	'editor-style-rtl.css'  => __( 'Visual Editor RTL Stylesheet' ),
	'rtl.css'               => __( 'RTL Stylesheet' ),
	// Other
	'my-hacks.php'          => __( 'my-hacks.php (legacy hacks support)' ),
	'.htaccess'             => __( '.htaccess (for rewrite rules )' ),
	// Deprecated files
	'wp-layout.css'         => __( 'Stylesheet' ),
	'wp-comments.php'       => __( 'Comments Template' ),
	'wp-comments-popup.php' => __( 'Popup Comments Template' ),
	'comments-popup.php'    => __( 'Popup Comments' ),
);

/**
 * Get the description for standard ClassicPress theme files and other various standard
 * ClassicPress files
 *
 * @since WP-1.5.0
 *
 * @global array $wp_file_descriptions Theme file descriptions.
 * @global array $allowed_files        List of allowed files.
 * @param string $file Filesystem path or filename
 * @return string Description of file from $wp_file_descriptions or basename of $file if description doesn't exist.
 *                Appends 'Page Template' to basename of $file if the file is a page template
 */
function get_file_description( $file ) {
	global $wp_file_descriptions, $allowed_files;

	$dirname = pathinfo( $file, PATHINFO_DIRNAME );

	$file_path = $allowed_files[ $file ];
	if ( isset( $wp_file_descriptions[ basename( $file ) ] ) && '.' === $dirname ) {
		return $wp_file_descriptions[ basename( $file ) ];
	} elseif ( file_exists( $file_path ) && is_file( $file_path ) ) {
		$template_data = implode( '', file( $file_path ) );
		if ( preg_match( '|Template Name:(.*)$|mi', $template_data, $name ) ) {
			return sprintf( __( '%s Page Template' ), _cleanup_header_comment( $name[1] ) );
		}
	}

	return trim( basename( $file ) );
}

/**
 * Get the absolute filesystem path to the root of the ClassicPress installation
 *
 * @since WP-1.5.0
 *
 * @return string Full filesystem path to the root of the ClassicPress installation
 */
function get_home_path() {
	$home    = set_url_scheme( get_option( 'home' ), 'http' );
	$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
	if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
		$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
		$pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
		$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
		$home_path = trailingslashit( $home_path );
	} else {
		$home_path = ABSPATH;
	}

	return str_replace( '\\', '/', $home_path );
}

/**
 * Returns a listing of all files in the specified folder and all subdirectories up to 100 levels deep.
 * The depth of the recursiveness can be controlled by the $levels param.
 *
 * @since WP-2.6.0
 * @since WP-4.9.0 Added the `$exclusions` parameter.
 *
 * @param string $folder Optional. Full path to folder. Default empty.
 * @param int    $levels Optional. Levels of folders to follow, Default 100 (PHP Loop limit).
 * @param array  $exclusions Optional. List of folders and files to skip.
 * @return bool|array False on failure, Else array of files
 */
function list_files( $folder = '', $levels = 100, $exclusions = array() ) {
	if ( empty( $folder ) ) {
		return false;
	}

	$folder = trailingslashit( $folder );

	if ( ! $levels ) {
		return false;
	}

	$files = array();

	$dir = @opendir( $folder );
	if ( $dir ) {
		while ( ( $file = readdir( $dir ) ) !== false ) {
			// Skip current and parent folder links.
			if ( in_array( $file, array( '.', '..' ), true ) ) {
				continue;
			}

			// Skip hidden and excluded files.
			if ( '.' === $file[0] || in_array( $file, $exclusions, true ) ) {
				continue;
			}

			if ( is_dir( $folder . $file ) ) {
				$files2 = list_files( $folder . $file, $levels - 1 );
				if ( $files2 ) {
					$files = array_merge($files, $files2 );
				} else {
					$files[] = $folder . $file . '/';
				}
			} else {
				$files[] = $folder . $file;
			}
		}
	}
	@closedir( $dir );

	return $files;
}

/**
 * Get list of file extensions that are editable in plugins.
 *
 * @since WP-4.9.0
 *
 * @param string $plugin Plugin.
 * @return array File extensions.
 */
function wp_get_plugin_file_editable_extensions( $plugin ) {

	$editable_extensions = array(
		'bash',
		'conf',
		'css',
		'diff',
		'htm',
		'html',
		'http',
		'inc',
		'include',
		'js',
		'json',
		'jsx',
		'less',
		'md',
		'patch',
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'phps',
		'phtml',
		'sass',
		'scss',
		'sh',
		'sql',
		'svg',
		'text',
		'txt',
		'xml',
		'yaml',
		'yml',
	);

	/**
	 * Filters file type extensions editable in the plugin editor.
	 *
	 * @since WP-2.8.0
	 * @since WP-4.9.0 Adds $plugin param.
	 *
	 * @param string $plugin Plugin file.
	 * @param array $editable_extensions An array of editable plugin file extensions.
	 */
	$editable_extensions = (array) apply_filters( 'editable_extensions', $editable_extensions, $plugin );

	return $editable_extensions;
}

/**
 * Get list of file extensions that are editable for a given theme.
 *
 * @param WP_Theme $theme Theme.
 * @return array File extensions.
 */
function wp_get_theme_file_editable_extensions( $theme ) {

	$default_types = array(
		'bash',
		'conf',
		'css',
		'diff',
		'htm',
		'html',
		'http',
		'inc',
		'include',
		'js',
		'json',
		'jsx',
		'less',
		'md',
		'patch',
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'phps',
		'phtml',
		'sass',
		'scss',
		'sh',
		'sql',
		'svg',
		'text',
		'txt',
		'xml',
		'yaml',
		'yml',
	);

	/**
	 * Filters the list of file types allowed for editing in the Theme editor.
	 *
	 * @since WP-4.4.0
	 *
	 * @param array    $default_types List of file types. Default types include 'php' and 'css'.
	 * @param WP_Theme $theme         The current Theme object.
	 */
	$file_types = apply_filters( 'wp_theme_editor_filetypes', $default_types, $theme );

	// Ensure that default types are still there.
	return array_unique( array_merge( $file_types, $default_types ) );
}

/**
 * Print file editor templates (for plugins and themes).
 *
 * @since WP-4.9.0
 */
function wp_print_file_editor_templates() {
	?>
	<script type="text/html" id="tmpl-wp-file-editor-notice">
		<div class="notice inline notice-{{ data.type || 'info' }} {{ data.alt ? 'notice-alt' : '' }} {{ data.dismissible ? 'is-dismissible' : '' }} {{ data.classes || '' }}">
			<# if ( 'php_error' === data.code ) { #>
				<p>
					<?php
					printf(
						/* translators: %$1s is line number and %1$s is file path. */
						__( 'Your PHP code changes were rolled back due to an error on line %1$s of file %2$s. Please fix and try saving again.' ),
						'{{ data.line }}',
						'{{ data.file }}'
					);
					?>
				</p>
				<pre>{{ data.message }}</pre>
			<# } else if ( 'file_not_writable' === data.code ) { #>
				<p><?php _e( 'You need to make this file writable before you can save your changes. See <a href="https://codex.wordpress.org/Changing_File_Permissions">the Codex</a> for more information.' ); ?></p>
			<# } else { #>
				<p>{{ data.message || data.code }}</p>

				<# if ( 'lint_errors' === data.code ) { #>
					<p>
						<# var elementId = 'el-' + String( Math.random() ); #>
						<input id="{{ elementId }}"  type="checkbox">
						<label for="{{ elementId }}"><?php _e( 'Update anyway, even though it might break your site?' ); ?></label>
					</p>
				<# } #>
			<# } #>
			<# if ( data.dismissible ) { #>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e( 'Dismiss' ); ?></span></button>
			<# } #>
		</div>
	</script>
	<?php
}

/**
 * Attempt to edit a file for a theme or plugin.
 *
 * When editing a PHP file, loopback requests will be made to the admin and the homepage
 * to attempt to see if there is a fatal error introduced. If so, the PHP change will be
 * reverted.
 *
 * @since WP-4.9.0
 *
 * @param array $args {
 *     Args. Note that all of the arg values are already unslashed. They are, however,
 *     coming straight from $_POST and are not validated or sanitized in any way.
 *
 *     @type string $file       Relative path to file.
 *     @type string $plugin     Plugin being edited.
 *     @type string $theme      Theme being edited.
 *     @type string $newcontent New content for the file.
 *     @type string $nonce      Nonce.
 * }
 * @return true|WP_Error True on success or `WP_Error` on failure.
 */
function wp_edit_theme_plugin_file( $args ) {
	if ( empty( $args['file'] ) ) {
		return new WP_Error( 'missing_file' );
	}
	$file = $args['file'];
	if ( 0 !== validate_file( $file ) ) {
		return new WP_Error( 'bad_file' );
	}

	if ( ! isset( $args['newcontent'] ) ) {
		return new WP_Error( 'missing_content' );
	}
	$content = $args['newcontent'];

	if ( ! isset( $args['nonce'] ) ) {
		return new WP_Error( 'missing_nonce' );
	}

	$plugin = null;
	$theme = null;
	$real_file = null;
	if ( ! empty( $args['plugin'] ) ) {
		$plugin = $args['plugin'];

		if ( ! current_user_can( 'edit_plugins' ) ) {
			return new WP_Error( 'unauthorized', __( 'Sorry, you are not allowed to edit plugins for this site.' ) );
		}

		if ( ! wp_verify_nonce( $args['nonce'], 'edit-plugin_' . $file ) ) {
			return new WP_Error( 'nonce_failure' );
		}

		if ( ! array_key_exists( $plugin, get_plugins() ) ) {
			return new WP_Error( 'invalid_plugin' );
		}

		if ( 0 !== validate_file( $file, get_plugin_files( $plugin ) ) ) {
			return new WP_Error( 'bad_plugin_file_path', __( 'Sorry, that file cannot be edited.' ) );
		}

		$editable_extensions = wp_get_plugin_file_editable_extensions( $plugin );

		$real_file = WP_PLUGIN_DIR . '/' . $file;

		$is_active = in_array(
			$plugin,
			(array) get_option( 'active_plugins', array() ),
			true
		);

	} elseif ( ! empty( $args['theme'] ) ) {
		$stylesheet = $args['theme'];
		if ( 0 !== validate_file( $stylesheet ) ) {
			return new WP_Error( 'bad_theme_path' );
		}

		if ( ! current_user_can( 'edit_themes' ) ) {
			return new WP_Error( 'unauthorized', __( 'Sorry, you are not allowed to edit templates for this site.' ) );
		}

		$theme = wp_get_theme( $stylesheet );
		if ( ! $theme->exists() ) {
			return new WP_Error( 'non_existent_theme', __( 'The requested theme does not exist.' ) );
		}

		$real_file = $theme->get_stylesheet_directory() . '/' . $file;
		if ( ! wp_verify_nonce( $args['nonce'], 'edit-theme_' . $real_file . $stylesheet ) ) {
			return new WP_Error( 'nonce_failure' );
		}

		if ( $theme->errors() && 'theme_no_stylesheet' === $theme->errors()->get_error_code() ) {
			return new WP_Error(
				'theme_no_stylesheet',
				__( 'The requested theme does not exist.' ) . ' ' . $theme->errors()->get_error_message()
			);
		}

		$editable_extensions = wp_get_theme_file_editable_extensions( $theme );

		$allowed_files = array();
		foreach ( $editable_extensions as $type ) {
			switch ( $type ) {
				case 'php':
					$allowed_files = array_merge( $allowed_files, $theme->get_files( 'php', -1 ) );
					break;
				case 'css':
					$style_files = $theme->get_files( 'css', -1 );
					$allowed_files['style.css'] = $style_files['style.css'];
					$allowed_files = array_merge( $allowed_files, $style_files );
					break;
				default:
					$allowed_files = array_merge( $allowed_files, $theme->get_files( $type, -1 ) );
					break;
			}
		}

		// Compare based on relative paths
		if ( 0 !== validate_file( $file, array_keys( $allowed_files ) ) ) {
			return new WP_Error( 'disallowed_theme_file', __( 'Sorry, that file cannot be edited.' ) );
		}

		$is_active = ( get_stylesheet() === $stylesheet || get_template() === $stylesheet );
	} else {
		return new WP_Error( 'missing_theme_or_plugin' );
	}

	// Ensure file is real.
	if ( ! is_file( $real_file ) ) {
		return new WP_Error( 'file_does_not_exist', __( 'No such file exists! Double check the name and try again.' ) );
	}

	// Ensure file extension is allowed.
	$extension = null;
	if ( preg_match( '/\.([^.]+)$/', $real_file, $matches ) ) {
		$extension = strtolower( $matches[1] );
		if ( ! in_array( $extension, $editable_extensions, true ) ) {
			return new WP_Error( 'illegal_file_type', __( 'Files of this type are not editable.' ) );
		}
	}

	$previous_content = file_get_contents( $real_file );

	if ( ! is_writeable( $real_file ) ) {
		return new WP_Error( 'file_not_writable' );
	}

	$f = fopen( $real_file, 'w+' );
	if ( false === $f ) {
		return new WP_Error( 'file_not_writable' );
	}

	$written = fwrite( $f, $content );
	fclose( $f );
	if ( false === $written ) {
		return new WP_Error( 'unable_to_write', __( 'Unable to write to file.' ) );
	}
	if ( 'php' === $extension && function_exists( 'opcache_invalidate' ) ) {
		opcache_invalidate( $real_file, true );
	}

	if ( $is_active && 'php' === $extension ) {

		$scrape_key = md5( rand() );
		$transient = 'scrape_key_' . $scrape_key;
		$scrape_nonce = strval( rand() );
		set_transient( $transient, $scrape_nonce, 60 ); // It shouldn't take more than 60 seconds to make the two loopback requests.

		$cookies = wp_unslash( $_COOKIE );
		$scrape_params = array(
			'wp_scrape_key' => $scrape_key,
			'wp_scrape_nonce' => $scrape_nonce,
		);
		$headers = array(
			'Cache-Control' => 'no-cache',
		);

		// Include Basic auth in loopback requests.
		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			$headers['Authorization'] = 'Basic ' . base64_encode( wp_unslash( $_SERVER['PHP_AUTH_USER'] ) . ':' . wp_unslash( $_SERVER['PHP_AUTH_PW'] ) );
		}

		// Make sure PHP process doesn't die before loopback requests complete.
		@set_time_limit( 300 );

		// Time to wait for loopback requests to finish.
		$timeout = 100;

		$needle_start = "###### wp_scraping_result_start:$scrape_key ######";
		$needle_end = "###### wp_scraping_result_end:$scrape_key ######";

		// Attempt loopback request to editor to see if user just whitescreened themselves.
		if ( $plugin ) {
			$url = add_query_arg( compact( 'plugin', 'file' ), admin_url( 'plugin-editor.php' ) );
		} elseif ( isset( $stylesheet ) ) {
			$url = add_query_arg(
				array(
					'theme' => $stylesheet,
					'file' => $file,
				),
				admin_url( 'theme-editor.php' )
			);
		} else {
			$url = admin_url();
		}
		$url = add_query_arg( $scrape_params, $url );
		$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );
		$body = wp_remote_retrieve_body( $r );
		$scrape_result_position = strpos( $body, $needle_start );

		$loopback_request_failure = array(
			'code' => 'loopback_request_failed',
			'message' => __( 'Unable to communicate back with site to check for fatal errors, so the PHP change was reverted. You will need to upload your PHP file change by some other means, such as by using SFTP.' ),
		);
		$json_parse_failure = array(
			'code' => 'json_parse_error',
		);

		$result = null;
		if ( false === $scrape_result_position ) {
			$result = $loopback_request_failure;
		} else {
			$error_output = substr( $body, $scrape_result_position + strlen( $needle_start ) );
			$error_output = substr( $error_output, 0, strpos( $error_output, $needle_end ) );
			$result = json_decode( trim( $error_output ), true );
			if ( empty( $result ) ) {
				$result = $json_parse_failure;
			}
		}

		// Try making request to homepage as well to see if visitors have been whitescreened.
		if ( true === $result ) {
			$url = home_url( '/' );
			$url = add_query_arg( $scrape_params, $url );
			$r = wp_remote_get( $url, compact( 'cookies', 'headers', 'timeout' ) );
			$body = wp_remote_retrieve_body( $r );
			$scrape_result_position = strpos( $body, $needle_start );

			if ( false === $scrape_result_position ) {
				$result = $loopback_request_failure;
			} else {
				$error_output = substr( $body, $scrape_result_position + strlen( $needle_start ) );
				$error_output = substr( $error_output, 0, strpos( $error_output, $needle_end ) );
				$result = json_decode( trim( $error_output ), true );
				if ( empty( $result ) ) {
					$result = $json_parse_failure;
				}
			}
		}

		delete_transient( $transient );

		if ( true !== $result ) {

			// Roll-back file change.
			file_put_contents( $real_file, $previous_content );
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $real_file, true );
			}

			if ( ! isset( $result['message'] ) ) {
				$message = __( 'Something went wrong.' );
			} else {
				$message = $result['message'];
				unset( $result['message'] );
			}
			return new WP_Error( 'php_error', $message, $result );
		}
	}

	if ( $theme instanceof WP_Theme ) {
		$theme->cache_delete();
	}

	return true;
}


/**
 * Returns a filename of a Temporary unique file.
 * Please note that the calling function must unlink() this itself.
 *
 * The filename is based off the passed parameter or defaults to the current unix timestamp,
 * while the directory can either be passed as well, or by leaving it blank, default to a writable temporary directory.
 *
 * @since WP-2.6.0
 *
 * @param string $filename Optional. Filename to base the Unique file off. Default empty.
 * @param string $dir      Optional. Directory to store the file in. Default empty.
 * @return string a writable filename
 */
function wp_tempnam( $filename = '', $dir = '' ) {
	if ( empty( $dir ) ) {
		$dir = get_temp_dir();
	}

	if ( empty( $filename ) || '.' == $filename || '/' == $filename || '\\' == $filename ) {
		$filename = time();
	}

	// Use the basename of the given file without the extension as the name for the temporary directory
	$temp_filename = basename( $filename );
	$temp_filename = preg_replace( '|\.[^.]*$|', '', $temp_filename );

	// If the folder is falsey, use its parent directory name instead.
	if ( ! $temp_filename ) {
		return wp_tempnam( dirname( $filename ), $dir );
	}

	// Suffix some random data to avoid filename conflicts
	$temp_filename .= '-' . wp_generate_password( 6, false );
	$temp_filename .= '.tmp';
	$temp_filename = $dir . wp_unique_filename( $dir, $temp_filename );

	$fp = @fopen( $temp_filename, 'x' );
	if ( ! $fp && is_writable( $dir ) && file_exists( $temp_filename ) ) {
		return wp_tempnam( $filename, $dir );
	}
	if ( $fp ) {
		fclose( $fp );
	}

	return $temp_filename;
}

/**
 * Makes sure that the file that was requested to be edited is allowed to be edited.
 *
 * Function will die if you are not allowed to edit the file.
 *
 * @since WP-1.5.0
 *
 * @param string $file          File the user is attempting to edit.
 * @param array  $allowed_files Optional. Array of allowed files to edit, $file must match an entry exactly.
 * @return string|null
 */
function validate_file_to_edit( $file, $allowed_files = array() ) {
	$code = validate_file( $file, $allowed_files );

	if (!$code )
		return $file;

	switch ( $code ) {
		case 1 :
			wp_die( __( 'Sorry, that file cannot be edited.' ) );

		// case 2 :
		// wp_die( __('Sorry, can&#8217;t call files with their real path.' ));

		case 3 :
			wp_die( __( 'Sorry, that file cannot be edited.' ) );
	}
}

/**
 * Handle PHP uploads in ClassicPress, sanitizing file names, checking extensions for mime type,
 * and moving the file to the appropriate directory within the uploads directory.
 *
 * @access private
 * @since WP-4.0.0
 *
 * @see wp_handle_upload_error
 *
 * @param array       $file      Reference to a single element of $_FILES. Call the function once for each uploaded file.
 * @param array|false $overrides An associative array of names => values to override default variables. Default false.
 * @param string      $time      Time formatted in 'yyyy/mm'.
 * @param string      $action    Expected value for $_POST['action'].
 * @return array On success, returns an associative array of file attributes. On failure, returns
 *               $overrides['upload_error_handler'](&$file, $message ) or array( 'error'=>$message ).
 */
function _wp_handle_upload( &$file, $overrides, $time, $action ) {
	// The default error handler.
	if ( ! function_exists( 'wp_handle_upload_error' ) ) {
		function wp_handle_upload_error( &$file, $message ) {
			return array( 'error' => $message );
		}
	}

	/**
	 * Filters the data for a file before it is uploaded to ClassicPress.
	 *
	 * The dynamic portion of the hook name, `$action`, refers to the post action.
	 *
	 * @since WP-2.9.0 as 'wp_handle_upload_prefilter'.
	 * @since WP-4.0.0 Converted to a dynamic hook with `$action`.
	 *
	 * @param array $file An array of data for a single file.
	 */
	$file = apply_filters( "{$action}_prefilter", $file );

	// You may define your own function and pass the name in $overrides['upload_error_handler']
	$upload_error_handler = 'wp_handle_upload_error';
	if ( isset( $overrides['upload_error_handler'] ) ) {
		$upload_error_handler = $overrides['upload_error_handler'];
	}

	// You may have had one or more 'wp_handle_upload_prefilter' functions error out the file. Handle that gracefully.
	if ( isset( $file['error'] ) && ! is_numeric( $file['error'] ) && $file['error'] ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $file['error'] ) );
	}

	// Install user overrides. Did we mention that this voids your warranty?

	// You may define your own function and pass the name in $overrides['unique_filename_callback']
	$unique_filename_callback = null;
	if ( isset( $overrides['unique_filename_callback'] ) ) {
		$unique_filename_callback = $overrides['unique_filename_callback'];
	}

	/*
	 * This may not have orignially been intended to be overrideable,
	 * but historically has been.
	 */
	if ( isset( $overrides['upload_error_strings'] ) ) {
		$upload_error_strings = $overrides['upload_error_strings'];
	} else {
		// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
		$upload_error_strings = array(
			false,
			__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.' ),
			__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.' ),
			__( 'The uploaded file was only partially uploaded.' ),
			__( 'No file was uploaded.' ),
			'',
			__( 'Missing a temporary folder.' ),
			__( 'Failed to write file to disk.' ),
			__( 'File upload stopped by extension.' )
		);
	}

	// All tests are on by default. Most can be turned off by $overrides[{test_name}] = false;
	$test_form = isset( $overrides['test_form'] ) ? $overrides['test_form'] : true;
	$test_size = isset( $overrides['test_size'] ) ? $overrides['test_size'] : true;

	// If you override this, you must provide $ext and $type!!
	$test_type = isset( $overrides['test_type'] ) ? $overrides['test_type'] : true;
	$mimes = isset( $overrides['mimes'] ) ? $overrides['mimes'] : false;

	// A correct form post will pass this test.
	if ( $test_form && ( ! isset( $_POST['action'] ) || ( $_POST['action'] != $action ) ) ) {
		return call_user_func_array( $upload_error_handler, array( &$file, __( 'Invalid form submission.' ) ) );
	}
	// A successful upload will pass this test. It makes no sense to override this one.
	if ( isset( $file['error'] ) && $file['error'] > 0 ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $upload_error_strings[ $file['error'] ] ) );
	}

	$test_file_size = 'wp_handle_upload' === $action ? $file['size'] : filesize( $file['tmp_name'] );
	// A non-empty file will pass this test.
	if ( $test_size && ! ( $test_file_size > 0 ) ) {
		if ( is_multisite() ) {
			$error_msg = __( 'File is empty. Please upload something more substantial.' );
		} else {
			$error_msg = __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.' );
		}
		return call_user_func_array( $upload_error_handler, array( &$file, $error_msg ) );
	}

	// A properly uploaded file will pass this test. There should be no reason to override this one.
	$test_uploaded_file = 'wp_handle_upload' === $action ? @ is_uploaded_file( $file['tmp_name'] ) : @ is_file( $file['tmp_name'] );
	if ( ! $test_uploaded_file ) {
		return call_user_func_array( $upload_error_handler, array( &$file, __( 'Specified file failed upload test.' ) ) );
	}

	// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
	if ( $test_type ) {
		$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
		$ext = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
		$type = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
		$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

		// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect
		if ( $proper_filename ) {
			$file['name'] = $proper_filename;
		}
		if ( ( ! $type || !$ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
			return call_user_func_array( $upload_error_handler, array( &$file, __( 'Sorry, this file type is not permitted for security reasons.' ) ) );
		}
		if ( ! $type ) {
			$type = $file['type'];
		}
	} else {
		$type = '';
	}

	/*
	 * A writable uploads dir will pass this test. Again, there's no point
	 * overriding this one.
	 */
	if ( ! ( ( $uploads = wp_upload_dir( $time ) ) && false === $uploads['error'] ) ) {
		return call_user_func_array( $upload_error_handler, array( &$file, $uploads['error'] ) );
	}

	$filename = wp_unique_filename( $uploads['path'], $file['name'], $unique_filename_callback );

	// Move the file to the uploads dir.
	$new_file = $uploads['path'] . "/$filename";

 	/**
	 * Filters whether to short-circuit moving the uploaded file after passing all checks.
	 *
	 * If a non-null value is passed to the filter, moving the file and any related error
	 * reporting will be completely skipped.
	 *
	 * @since WP-4.9.0
	 *
	 * @param string $move_new_file If null (default) move the file after the upload.
	 * @param string $file          An array of data for a single file.
	 * @param string $new_file      Filename of the newly-uploaded file.
	 * @param string $type          File type.
	 */
	$move_new_file = apply_filters( 'pre_move_uploaded_file', null, $file, $new_file, $type );

	if ( null === $move_new_file ) {
		if ( 'wp_handle_upload' === $action ) {
			$move_new_file = @ move_uploaded_file( $file['tmp_name'], $new_file );
		} else {
			// use copy and unlink because rename breaks streams.
			$move_new_file = @ copy( $file['tmp_name'], $new_file );
			unlink( $file['tmp_name'] );
		}

		if ( false === $move_new_file ) {
			if ( 0 === strpos( $uploads['basedir'], ABSPATH ) ) {
				$error_path = str_replace( ABSPATH, '', $uploads['basedir'] ) . $uploads['subdir'];
			} else {
				$error_path = basename( $uploads['basedir'] ) . $uploads['subdir'];
			}
			return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved to %s.' ), $error_path ) );
		}
	}

	// Set correct file permissions.
	$stat = stat( dirname( $new_file ));
	$perms = $stat['mode'] & 0000666;
	@ chmod( $new_file, $perms );

	// Compute the URL.
	$url = $uploads['url'] . "/$filename";

	if ( is_multisite() ) {
		delete_transient( 'dirsize_cache' );
	}

	/**
	 * Filters the data array for the uploaded file.
	 *
	 * @since WP-2.1.0
	 *
	 * @param array  $upload {
	 *     Array of upload data.
	 *
	 *     @type string $file Filename of the newly-uploaded file.
	 *     @type string $url  URL of the uploaded file.
	 *     @type string $type File type.
	 * }
	 * @param string $context The type of upload action. Values include 'upload' or 'sideload'.
	 */
	return apply_filters( 'wp_handle_upload', array(
		'file' => $new_file,
		'url'  => $url,
		'type' => $type
	), 'wp_handle_sideload' === $action ? 'sideload' : 'upload' );
}

/**
 * Wrapper for _wp_handle_upload().
 *
 * Passes the {@see 'wp_handle_upload'} action.
 *
 * @since WP-2.0.0
 *
 * @see _wp_handle_upload()
 *
 * @param array      $file      Reference to a single element of `$_FILES`. Call the function once for
 *                              each uploaded file.
 * @param array|bool $overrides Optional. An associative array of names=>values to override default
 *                              variables. Default false.
 * @param string     $time      Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array On success, returns an associative array of file attributes. On failure, returns
 *               $overrides['upload_error_handler'](&$file, $message ) or array( 'error'=>$message ).
 */
function wp_handle_upload( &$file, $overrides = false, $time = null ) {
	/*
	 *  $_POST['action'] must be set and its value must equal $overrides['action']
	 *  or this:
	 */
	$action = 'wp_handle_upload';
	if ( isset( $overrides['action'] ) ) {
		$action = $overrides['action'];
	}

	return _wp_handle_upload( $file, $overrides, $time, $action );
}

/**
 * Wrapper for _wp_handle_upload().
 *
 * Passes the {@see 'wp_handle_sideload'} action.
 *
 * @since WP-2.6.0
 *
 * @see _wp_handle_upload()
 *
 * @param array      $file      An array similar to that of a PHP `$_FILES` POST array
 * @param array|bool $overrides Optional. An associative array of names=>values to override default
 *                              variables. Default false.
 * @param string     $time      Optional. Time formatted in 'yyyy/mm'. Default null.
 * @return array On success, returns an associative array of file attributes. On failure, returns
 *               $overrides['upload_error_handler'](&$file, $message ) or array( 'error'=>$message ).
 */
function wp_handle_sideload( &$file, $overrides = false, $time = null ) {
	/*
	 *  $_POST['action'] must be set and its value must equal $overrides['action']
	 *  or this:
	 */
	$action = 'wp_handle_sideload';
	if ( isset( $overrides['action'] ) ) {
		$action = $overrides['action'];
	}
	return _wp_handle_upload( $file, $overrides, $time, $action );
}


/**
 * Downloads a URL to a local temporary file using the ClassicPress HTTP Class.
 * Please note, That the calling function must unlink() the file.
 *
 * @since WP-2.5.0
 *
 * @param string $url the URL of the file to download
 * @param int $timeout The timeout for the request to download the file default 300 seconds
 * @return mixed WP_Error on failure, string Filename on success.
 */
function download_url( $url, $timeout = 300 ) {
	//WARNING: The file is not automatically deleted, The script must unlink() the file.
	if ( ! $url )
		return new WP_Error('http_no_url', __('Invalid URL Provided.'));

	$url_filename = basename( parse_url( $url, PHP_URL_PATH ) );

	$tmpfname = wp_tempnam( $url_filename );
	if ( ! $tmpfname )
		return new WP_Error('http_no_file', __('Could not create Temporary file.'));

	$response = wp_safe_remote_get( $url, array( 'timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname ) );

	if ( is_wp_error( $response ) ) {
		unlink( $tmpfname );
		return $response;
	}

	if ( 200 != wp_remote_retrieve_response_code( $response ) ){
		unlink( $tmpfname );
		return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
	}

	$content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );
	if ( $content_md5 ) {
		$md5_check = verify_file_md5( $tmpfname, $content_md5 );
		if ( is_wp_error( $md5_check ) ) {
			unlink( $tmpfname );
			return $md5_check;
		}
	}

	return $tmpfname;
}

/**
 * Calculates and compares the MD5 of a file to its expected value.
 *
 * @since WP-3.7.0
 *
 * @param string $filename The filename to check the MD5 of.
 * @param string $expected_md5 The expected MD5 of the file, either a base64 encoded raw md5, or a hex-encoded md5
 * @return bool|object WP_Error on failure, true on success, false when the MD5 format is unknown/unexpected
 */
function verify_file_md5( $filename, $expected_md5 ) {
	if ( 32 == strlen( $expected_md5 ) )
		$expected_raw_md5 = pack( 'H*', $expected_md5 );
	elseif ( 24 == strlen( $expected_md5 ) )
		$expected_raw_md5 = base64_decode( $expected_md5 );
	else
		return false; // unknown format

	$file_md5 = md5_file( $filename, true );

	if ( $file_md5 === $expected_raw_md5 )
		return true;

	return new WP_Error( 'md5_mismatch', sprintf( __( 'The checksum of the file (%1$s) does not match the expected checksum value (%2$s).' ), bin2hex( $file_md5 ), bin2hex( $expected_raw_md5 ) ) );
}

/**
 * Unzips a specified ZIP file to a location on the Filesystem via the ClassicPress Filesystem Abstraction.
 * Assumes that WP_Filesystem() has already been called and set up. Does not extract a root-level __MACOSX directory, if present.
 *
 * Attempts to increase the PHP Memory limit to 256M before uncompressing,
 * However, The most memory required shouldn't be much larger than the Archive itself.
 *
 * @since WP-2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param string $file Full path and filename of zip archive
 * @param string $to Full path on the filesystem to extract archive to
 * @return mixed WP_Error on failure, True on success
 */
function unzip_file($file, $to) {
	global $wp_filesystem;

	if ( ! $wp_filesystem || !is_object($wp_filesystem) )
		return new WP_Error('fs_unavailable', __('Could not access filesystem.'));

	// Unzip can use a lot of memory, but not this much hopefully.
	wp_raise_memory_limit( 'admin' );

	$needed_dirs = array();
	$to = trailingslashit($to);

	// Determine any parent dir's needed (of the upgrade directory)
	if ( ! $wp_filesystem->is_dir($to) ) { //Only do parents if no children exist
		$path = preg_split('![/\\\]!', untrailingslashit($to));
		for ( $i = count($path); $i >= 0; $i-- ) {
			if ( empty($path[$i]) )
				continue;

			$dir = implode('/', array_slice($path, 0, $i+1) );
			if ( preg_match('!^[a-z]:$!i', $dir) ) // Skip it if it looks like a Windows Drive letter.
				continue;

			if ( ! $wp_filesystem->is_dir($dir) )
				$needed_dirs[] = $dir;
			else
				break; // A folder exists, therefor, we dont need the check the levels below this
		}
	}

	/**
	 * Filters whether to use ZipArchive to unzip archives.
	 *
	 * @since WP-3.0.0
	 *
	 * @param bool $ziparchive Whether to use ZipArchive. Default true.
	 */
	if ( class_exists( 'ZipArchive', false ) && apply_filters( 'unzip_file_use_ziparchive', true ) ) {
		$result = _unzip_file_ziparchive($file, $to, $needed_dirs);
		if ( true === $result ) {
			return $result;
		} elseif ( is_wp_error($result) ) {
			if ( 'incompatible_archive' != $result->get_error_code() )
				return $result;
		}
	}
	// Fall through to PclZip if ZipArchive is not available, or encountered an error opening the file.
	return _unzip_file_pclzip($file, $to, $needed_dirs);
}

/**
 * This function should not be called directly, use unzip_file instead. Attempts to unzip an archive using the ZipArchive class.
 * Assumes that WP_Filesystem() has already been called and set up.
 *
 * @since WP-3.0.0
 * @see unzip_file
 * @access private
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param string $file Full path and filename of zip archive
 * @param string $to Full path on the filesystem to extract archive to
 * @param array $needed_dirs A partial list of required folders needed to be created.
 * @return mixed WP_Error on failure, True on success
 */
function _unzip_file_ziparchive($file, $to, $needed_dirs = array() ) {
	global $wp_filesystem;

	$z = new ZipArchive();

	$zopen = $z->open( $file, ZIPARCHIVE::CHECKCONS );
	if ( true !== $zopen )
		return new WP_Error( 'incompatible_archive', __( 'Incompatible Archive.' ), array( 'ziparchive_error' => $zopen ) );

	$uncompressed_size = 0;

	for ( $i = 0; $i < $z->numFiles; $i++ ) {
		if ( ! $info = $z->statIndex($i) )
			return new WP_Error( 'stat_failed_ziparchive', __( 'Could not retrieve file from archive.' ) );

		if ( '__MACOSX/' === substr($info['name'], 0, 9) ) // Skip the OS X-created __MACOSX directory
			continue;

		// Don't extract invalid files:
		if ( 0 !== validate_file( $info['name'] ) ) {
			continue;
		}

		$uncompressed_size += $info['size'];

		if ( '/' === substr( $info['name'], -1 ) ) {
			// Directory.
			$needed_dirs[] = $to . untrailingslashit( $info['name'] );
		} elseif ( '.' !== $dirname = dirname( $info['name'] ) ) {
			// Path to a file.
			$needed_dirs[] = $to . untrailingslashit( $dirname );
		}
	}

	/*
	 * disk_free_space() could return false. Assume that any falsey value is an error.
	 * A disk that has zero free bytes has bigger problems.
	 * Require we have enough space to unzip the file and copy its contents, with a 10% buffer.
	 */
	if ( wp_doing_cron() ) {
		$available_space = @disk_free_space( WP_CONTENT_DIR );
		if ( $available_space && ( $uncompressed_size * 2.1 ) > $available_space )
			return new WP_Error( 'disk_full_unzip_file', __( 'Could not copy files. You may have run out of disk space.' ), compact( 'uncompressed_size', 'available_space' ) );
	}

	$needed_dirs = array_unique($needed_dirs);
	foreach ( $needed_dirs as $dir ) {
		// Check the parent folders of the folders all exist within the creation array.
		if ( untrailingslashit($to) == $dir ) // Skip over the working directory, We know this exists (or will exist)
			continue;
		if ( strpos($dir, $to) === false ) // If the directory is not within the working directory, Skip it
			continue;

		$parent_folder = dirname($dir);
		while ( !empty($parent_folder) && untrailingslashit($to) != $parent_folder && !in_array($parent_folder, $needed_dirs) ) {
			$needed_dirs[] = $parent_folder;
			$parent_folder = dirname($parent_folder);
		}
	}
	asort($needed_dirs);

	// Create those directories if need be:
	foreach ( $needed_dirs as $_dir ) {
		// Only check to see if the Dir exists upon creation failure. Less I/O this way.
		if ( ! $wp_filesystem->mkdir( $_dir, FS_CHMOD_DIR ) && ! $wp_filesystem->is_dir( $_dir ) ) {
			return new WP_Error( 'mkdir_failed_ziparchive', __( 'Could not create directory.' ), substr( $_dir, strlen( $to ) ) );
		}
	}
	unset($needed_dirs);

	for ( $i = 0; $i < $z->numFiles; $i++ ) {
		if ( ! $info = $z->statIndex($i) )
			return new WP_Error( 'stat_failed_ziparchive', __( 'Could not retrieve file from archive.' ) );

		if ( '/' == substr($info['name'], -1) ) // directory
			continue;

		if ( '__MACOSX/' === substr($info['name'], 0, 9) ) // Don't extract the OS X-created __MACOSX directory files
			continue;

		// Don't extract invalid files:
		if ( 0 !== validate_file( $info['name'] ) ) {
			continue;
		}

		$contents = $z->getFromIndex($i);
		if ( false === $contents )
			return new WP_Error( 'extract_failed_ziparchive', __( 'Could not extract file from archive.' ), $info['name'] );

		if ( ! $wp_filesystem->put_contents( $to . $info['name'], $contents, FS_CHMOD_FILE) )
			return new WP_Error( 'copy_failed_ziparchive', __( 'Could not copy file.' ), $info['name'] );
	}

	$z->close();

	return true;
}

/**
 * This function should not be called directly, use unzip_file instead. Attempts to unzip an archive using the PclZip library.
 * Assumes that WP_Filesystem() has already been called and set up.
 *
 * @since WP-3.0.0
 * @see unzip_file
 * @access private
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param string $file Full path and filename of zip archive
 * @param string $to Full path on the filesystem to extract archive to
 * @param array $needed_dirs A partial list of required folders needed to be created.
 * @return mixed WP_Error on failure, True on success
 */
function _unzip_file_pclzip($file, $to, $needed_dirs = array()) {
	global $wp_filesystem;

	mbstring_binary_safe_encoding();

	require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');

	$archive = new PclZip($file);

	$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);

	reset_mbstring_encoding();

	// Is the archive valid?
	if ( !is_array($archive_files) )
		return new WP_Error('incompatible_archive', __('Incompatible Archive.'), $archive->errorInfo(true));

	if ( 0 == count($archive_files) )
		return new WP_Error( 'empty_archive_pclzip', __( 'Empty archive.' ) );

	$uncompressed_size = 0;

	// Determine any children directories needed (From within the archive)
	foreach ( $archive_files as $file ) {
		if ( '__MACOSX/' === substr($file['filename'], 0, 9) ) // Skip the OS X-created __MACOSX directory
			continue;

		$uncompressed_size += $file['size'];

		$needed_dirs[] = $to . untrailingslashit( $file['folder'] ? $file['filename'] : dirname($file['filename']) );
	}

	/*
	 * disk_free_space() could return false. Assume that any falsey value is an error.
	 * A disk that has zero free bytes has bigger problems.
	 * Require we have enough space to unzip the file and copy its contents, with a 10% buffer.
	 */
	if ( wp_doing_cron() ) {
		$available_space = @disk_free_space( WP_CONTENT_DIR );
		if ( $available_space && ( $uncompressed_size * 2.1 ) > $available_space )
			return new WP_Error( 'disk_full_unzip_file', __( 'Could not copy files. You may have run out of disk space.' ), compact( 'uncompressed_size', 'available_space' ) );
	}

	$needed_dirs = array_unique($needed_dirs);
	foreach ( $needed_dirs as $dir ) {
		// Check the parent folders of the folders all exist within the creation array.
		if ( untrailingslashit($to) == $dir ) // Skip over the working directory, We know this exists (or will exist)
			continue;
		if ( strpos($dir, $to) === false ) // If the directory is not within the working directory, Skip it
			continue;

		$parent_folder = dirname($dir);
		while ( !empty($parent_folder) && untrailingslashit($to) != $parent_folder && !in_array($parent_folder, $needed_dirs) ) {
			$needed_dirs[] = $parent_folder;
			$parent_folder = dirname($parent_folder);
		}
	}
	asort($needed_dirs);

	// Create those directories if need be:
	foreach ( $needed_dirs as $_dir ) {
		// Only check to see if the dir exists upon creation failure. Less I/O this way.
		if ( ! $wp_filesystem->mkdir( $_dir, FS_CHMOD_DIR ) && ! $wp_filesystem->is_dir( $_dir ) )
			return new WP_Error( 'mkdir_failed_pclzip', __( 'Could not create directory.' ), substr( $_dir, strlen( $to ) ) );
	}
	unset($needed_dirs);

	// Extract the files from the zip
	foreach ( $archive_files as $file ) {
		if ( $file['folder'] )
			continue;

		if ( '__MACOSX/' === substr($file['filename'], 0, 9) ) // Don't extract the OS X-created __MACOSX directory files
			continue;

		// Don't extract invalid files:
		if ( 0 !== validate_file( $file['filename'] ) ) {
			continue;
		}

		if ( ! $wp_filesystem->put_contents( $to . $file['filename'], $file['content'], FS_CHMOD_FILE) )
			return new WP_Error( 'copy_failed_pclzip', __( 'Could not copy file.' ), $file['filename'] );
	}
	return true;
}

/**
 * Copies a directory from one location to another via the ClassicPress Filesystem Abstraction.
 * Assumes that WP_Filesystem() has already been called and setup.
 *
 * @since WP-2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param string $from source directory
 * @param string $to destination directory
 * @param array $skip_list a list of files/folders to skip copying
 * @return mixed WP_Error on failure, True on success.
 */
function copy_dir($from, $to, $skip_list = array() ) {
	global $wp_filesystem;

	$dirlist = $wp_filesystem->dirlist($from);

	$from = trailingslashit($from);
	$to = trailingslashit($to);

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( in_array( $filename, $skip_list ) )
			continue;

		if ( 'f' == $fileinfo['type'] ) {
			if ( ! $wp_filesystem->copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) ) {
				// If copy failed, chmod file to 0644 and try again.
				$wp_filesystem->chmod( $to . $filename, FS_CHMOD_FILE );
				if ( ! $wp_filesystem->copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) )
					return new WP_Error( 'copy_failed_copy_dir', __( 'Could not copy file.' ), $to . $filename );
			}
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->is_dir($to . $filename) ) {
				if ( !$wp_filesystem->mkdir($to . $filename, FS_CHMOD_DIR) )
					return new WP_Error( 'mkdir_failed_copy_dir', __( 'Could not create directory.' ), $to . $filename );
			}

			// generate the $sub_skip_list for the subdirectory as a sub-set of the existing $skip_list
			$sub_skip_list = array();
			foreach ( $skip_list as $skip_item ) {
				if ( 0 === strpos( $skip_item, $filename . '/' ) )
					$sub_skip_list[] = preg_replace( '!^' . preg_quote( $filename, '!' ) . '/!i', '', $skip_item );
			}

			$result = copy_dir($from . $filename, $to . $filename, $sub_skip_list);
			if ( is_wp_error($result) )
				return $result;
		}
	}
	return true;
}

/**
 * Initialises and connects the ClassicPress Filesystem Abstraction classes.
 * This function will include the chosen transport and attempt connecting.
 *
 * Plugins may add extra transports, And force ClassicPress to use them by returning
 * the filename via the {@see 'filesystem_method_file'} filter.
 *
 * @since WP-2.5.0
 *
 * @global WP_Filesystem_Base $wp_filesystem Subclass
 *
 * @param array|false  $args                         Optional. Connection args, These are passed directly to
 *                                                   the `WP_Filesystem_*()` classes. Default false.
 * @param string|false $context                      Optional. Context for get_filesystem_method(). Default false.
 * @param bool         $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable. Default false.
 * @return null|bool false on failure, true on success.
 */
function WP_Filesystem( $args = false, $context = false, $allow_relaxed_file_ownership = false ) {
	global $wp_filesystem;

	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');

	$method = get_filesystem_method( $args, $context, $allow_relaxed_file_ownership );

	if ( ! $method )
		return false;

	if ( ! class_exists( "WP_Filesystem_$method" ) ) {

		/**
		 * Filters the path for a specific filesystem method class file.
		 *
		 * @since WP-2.6.0
		 *
		 * @see get_filesystem_method()
		 *
		 * @param string $path   Path to the specific filesystem method class file.
		 * @param string $method The filesystem method to use.
		 */
		$abstraction_file = apply_filters( 'filesystem_method_file', ABSPATH . 'wp-admin/includes/class-wp-filesystem-' . $method . '.php', $method );

		if ( ! file_exists($abstraction_file) )
			return;

		require_once($abstraction_file);
	}
	$method = "WP_Filesystem_$method";

	$wp_filesystem = new $method($args);

	//Define the timeouts for the connections. Only available after the construct is called to allow for per-transport overriding of the default.
	if ( ! defined('FS_CONNECT_TIMEOUT') )
		define('FS_CONNECT_TIMEOUT', 30);
	if ( ! defined('FS_TIMEOUT') )
		define('FS_TIMEOUT', 30);

	if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
		return false;

	if ( !$wp_filesystem->connect() )
		return false; //There was an error connecting to the server.

	// Set the permission constants if not already set.
	if ( ! defined('FS_CHMOD_DIR') )
		define('FS_CHMOD_DIR', ( fileperms( ABSPATH ) & 0777 | 0755 ) );
	if ( ! defined('FS_CHMOD_FILE') )
		define('FS_CHMOD_FILE', ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 ) );

	return true;
}

/**
 * Determines which method to use for reading, writing, modifying, or deleting
 * files on the filesystem.
 *
 * The priority of the transports are: Direct, SSH2, FTP PHP Extension, FTP Sockets
 * (Via Sockets class, or `fsockopen()`). Valid values for these are: 'direct', 'ssh2',
 * 'ftpext' or 'ftpsockets'.
 *
 * The return value can be overridden by defining the `FS_METHOD` constant in `wp-config.php`,
 * or filtering via {@see 'filesystem_method'}.
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants
 *
 * Plugins may define a custom transport handler, See WP_Filesystem().
 *
 * @since WP-2.5.0
 *
 * @global callable $_wp_filesystem_direct_method
 *
 * @param array  $args                         Optional. Connection details. Default empty array.
 * @param string $context                      Optional. Full path to the directory that is tested
 *                                             for being writable. Default empty.
 * @param bool   $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable.
 *                                             Default false.
 * @return string The transport to use, see description for valid return values.
 */
function get_filesystem_method( $args = array(), $context = '', $allow_relaxed_file_ownership = false ) {
	$method = defined('FS_METHOD') ? FS_METHOD : false; // Please ensure that this is either 'direct', 'ssh2', 'ftpext' or 'ftpsockets'

	if ( ! $context ) {
		$context = WP_CONTENT_DIR;
	}

	// If the directory doesn't exist (wp-content/languages) then use the parent directory as we'll create it.
	if ( WP_LANG_DIR == $context && ! is_dir( $context ) ) {
		$context = dirname( $context );
	}

	$context = trailingslashit( $context );

	if ( ! $method ) {

		$temp_file_name = $context . 'temp-write-test-' . time();
		$temp_handle = @fopen($temp_file_name, 'w');
		if ( $temp_handle ) {

			// Attempt to determine the file owner of the ClassicPress files, and that of newly created files
			$wp_file_owner = $temp_file_owner = false;
			if ( function_exists('fileowner') ) {
				$wp_file_owner = @fileowner( __FILE__ );
				$temp_file_owner = @fileowner( $temp_file_name );
			}

			if ( $wp_file_owner !== false && $wp_file_owner === $temp_file_owner ) {
				// ClassicPress is creating files as the same owner as the ClassicPress files,
				// this means it's safe to modify & create new files via PHP.
				$method = 'direct';
				$GLOBALS['_wp_filesystem_direct_method'] = 'file_owner';
			} elseif ( $allow_relaxed_file_ownership ) {
				// The $context directory is writable, and $allow_relaxed_file_ownership is set, this means we can modify files
				// safely in this directory. This mode doesn't create new files, only alter existing ones.
				$method = 'direct';
				$GLOBALS['_wp_filesystem_direct_method'] = 'relaxed_ownership';
			}

			@fclose($temp_handle);
			@unlink($temp_file_name);
		}
 	}

	if ( ! $method && isset($args['connection_type']) && 'ssh' == $args['connection_type'] && extension_loaded('ssh2') && function_exists('stream_get_contents') ) $method = 'ssh2';
	if ( ! $method && extension_loaded('ftp') ) $method = 'ftpext';
	if ( ! $method && ( extension_loaded('sockets') || function_exists('fsockopen') ) ) $method = 'ftpsockets'; //Sockets: Socket extension; PHP Mode: FSockopen / fwrite / fread

	/**
	 * Filters the filesystem method to use.
	 *
	 * @since WP-2.6.0
	 *
	 * @param string $method  Filesystem method to return.
	 * @param array  $args    An array of connection details for the method.
	 * @param string $context Full path to the directory that is tested for being writable.
	 * @param bool   $allow_relaxed_file_ownership Whether to allow Group/World writable.
	 */
	return apply_filters( 'filesystem_method', $method, $args, $context, $allow_relaxed_file_ownership );
}

/**
 * Displays a form to the user to request for their FTP/SSH details in order
 * to connect to the filesystem.
 *
 * All chosen/entered details are saved, excluding the password.
 *
 * Hostnames may be in the form of hostname:portnumber (eg: wordpress.org:2467)
 * to specify an alternate FTP/SSH port.
 *
 * Plugins may override this form by returning true|false via the {@see 'request_filesystem_credentials'} filter.
 *
 * @since WP-2.5.0
 * @since WP-4.6.0 The `$context` parameter default changed from `false` to an empty string.
 *
 * @global string $pagenow
 *
 * @param string $form_post                    The URL to post the form to.
 * @param string $type                         Optional. Chosen type of filesystem. Default empty.
 * @param bool   $error                        Optional. Whether the current request has failed to connect.
 *                                             Default false.
 * @param string $context                      Optional. Full path to the directory that is tested for being
 *                                             writable. Default empty.
 * @param array  $extra_fields                 Optional. Extra `POST` fields to be checked for inclusion in
 *                                             the post. Default null.
 * @param bool   $allow_relaxed_file_ownership Optional. Whether to allow Group/World writable. Default false.
 *
 * @return bool False on failure, true on success.
 */
function request_filesystem_credentials( $form_post, $type = '', $error = false, $context = '', $extra_fields = null, $allow_relaxed_file_ownership = false ) {
	global $pagenow;

	/**
	 * Filters the filesystem credentials form output.
	 *
	 * Returning anything other than an empty string will effectively short-circuit
	 * output of the filesystem credentials form, returning that value instead.
	 *
	 * @since WP-2.5.0
	 * @since WP-4.6.0 The `$context` parameter default changed from `false` to an empty string.
	 *
	 * @param mixed  $output                       Form output to return instead. Default empty.
	 * @param string $form_post                    The URL to post the form to.
	 * @param string $type                         Chosen type of filesystem.
	 * @param bool   $error                        Whether the current request has failed to connect.
	 *                                             Default false.
	 * @param string $context                      Full path to the directory that is tested for
	 *                                             being writable.
	 * @param bool   $allow_relaxed_file_ownership Whether to allow Group/World writable.
	 *                                             Default false.
	 * @param array  $extra_fields                 Extra POST fields.
	 */
	$req_cred = apply_filters( 'request_filesystem_credentials', '', $form_post, $type, $error, $context, $extra_fields, $allow_relaxed_file_ownership );
	if ( '' !== $req_cred )
		return $req_cred;

	if ( empty($type) ) {
		$type = get_filesystem_method( array(), $context, $allow_relaxed_file_ownership );
	}

	if ( 'direct' == $type )
		return true;

	if ( is_null( $extra_fields ) )
		$extra_fields = array( 'version', 'locale' );

	$credentials = get_option('ftp_credentials', array( 'hostname' => '', 'username' => ''));

	$submitted_form = wp_unslash( $_POST );

	// Verify nonce, or unset submitted form field values on failure
	if ( ! isset( $_POST['_fs_nonce'] ) || ! wp_verify_nonce( $_POST['_fs_nonce'], 'filesystem-credentials' ) ) {
		unset(
			$submitted_form['hostname'],
			$submitted_form['username'],
			$submitted_form['password'],
			$submitted_form['public_key'],
			$submitted_form['private_key'],
			$submitted_form['connection_type']
		);
	}

	// If defined, set it to that, Else, If POST'd, set it to that, If not, Set it to whatever it previously was(saved details in option)
	$credentials['hostname'] = defined('FTP_HOST') ? FTP_HOST : (!empty($submitted_form['hostname']) ? $submitted_form['hostname'] : $credentials['hostname']);
	$credentials['username'] = defined('FTP_USER') ? FTP_USER : (!empty($submitted_form['username']) ? $submitted_form['username'] : $credentials['username']);
	$credentials['password'] = defined('FTP_PASS') ? FTP_PASS : (!empty($submitted_form['password']) ? $submitted_form['password'] : '');

	// Check to see if we are setting the public/private keys for ssh
	$credentials['public_key'] = defined('FTP_PUBKEY') ? FTP_PUBKEY : (!empty($submitted_form['public_key']) ? $submitted_form['public_key'] : '');
	$credentials['private_key'] = defined('FTP_PRIKEY') ? FTP_PRIKEY : (!empty($submitted_form['private_key']) ? $submitted_form['private_key'] : '');

	// Sanitize the hostname, Some people might pass in odd-data:
	$credentials['hostname'] = preg_replace('|\w+://|', '', $credentials['hostname']); //Strip any schemes off

	if ( strpos($credentials['hostname'], ':') ) {
		list( $credentials['hostname'], $credentials['port'] ) = explode(':', $credentials['hostname'], 2);
		if ( ! is_numeric($credentials['port']) )
			unset($credentials['port']);
	} else {
		unset($credentials['port']);
	}

	if ( ( defined( 'FTP_SSH' ) && FTP_SSH ) || ( defined( 'FS_METHOD' ) && 'ssh2' == FS_METHOD ) ) {
		$credentials['connection_type'] = 'ssh';
	} elseif ( ( defined( 'FTP_SSL' ) && FTP_SSL ) && 'ftpext' == $type ) { //Only the FTP Extension understands SSL
		$credentials['connection_type'] = 'ftps';
	} elseif ( ! empty( $submitted_form['connection_type'] ) ) {
		$credentials['connection_type'] = $submitted_form['connection_type'];
	} elseif ( ! isset( $credentials['connection_type'] ) ) { //All else fails (And it's not defaulted to something else saved), Default to FTP
		$credentials['connection_type'] = 'ftp';
	}
	if ( ! $error &&
			(
				( !empty($credentials['password']) && !empty($credentials['username']) && !empty($credentials['hostname']) ) ||
				( 'ssh' == $credentials['connection_type'] && !empty($credentials['public_key']) && !empty($credentials['private_key']) )
			) ) {
		$stored_credentials = $credentials;
		if ( !empty($stored_credentials['port']) ) //save port as part of hostname to simplify above code.
			$stored_credentials['hostname'] .= ':' . $stored_credentials['port'];

		unset($stored_credentials['password'], $stored_credentials['port'], $stored_credentials['private_key'], $stored_credentials['public_key']);
		if ( ! wp_installing() ) {
			update_option( 'ftp_credentials', $stored_credentials );
		}
		return $credentials;
	}
	$hostname = isset( $credentials['hostname'] ) ? $credentials['hostname'] : '';
	$username = isset( $credentials['username'] ) ? $credentials['username'] : '';
	$public_key = isset( $credentials['public_key'] ) ? $credentials['public_key'] : '';
	$private_key = isset( $credentials['private_key'] ) ? $credentials['private_key'] : '';
	$port = isset( $credentials['port'] ) ? $credentials['port'] : '';
	$connection_type = isset( $credentials['connection_type'] ) ? $credentials['connection_type'] : '';

	if ( $error ) {
		$error_string = __('<strong>ERROR:</strong> There was an error connecting to the server, Please verify the settings are correct.');
		if ( is_wp_error($error) )
			$error_string = esc_html( $error->get_error_message() );
		echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
	}

	$types = array();
	if ( extension_loaded('ftp') || extension_loaded('sockets') || function_exists('fsockopen') )
		$types[ 'ftp' ] = __('FTP');
	if ( extension_loaded('ftp') ) //Only this supports FTPS
		$types[ 'ftps' ] = __('FTPS (SSL)');
	if ( extension_loaded('ssh2') && function_exists('stream_get_contents') )
		$types[ 'ssh' ] = __('SSH2');

	/**
	 * Filters the connection types to output to the filesystem credentials form.
	 *
	 * @since WP-2.9.0
	 * @since WP-4.6.0 The `$context` parameter default changed from `false` to an empty string.
	 *
	 * @param array  $types       Types of connections.
	 * @param array  $credentials Credentials to connect with.
	 * @param string $type        Chosen filesystem method.
	 * @param object $error       Error object.
	 * @param string $context     Full path to the directory that is tested
	 *                            for being writable.
	 */
	$types = apply_filters( 'fs_ftp_connection_types', $types, $credentials, $type, $error, $context );

?>
<form action="<?php echo esc_url( $form_post ) ?>" method="post">
<div id="request-filesystem-credentials-form" class="request-filesystem-credentials-form">
<?php
// Print a H1 heading in the FTP credentials modal dialog, default is a H2.
$heading_tag = 'h2';
if ( 'plugins.php' === $pagenow || 'plugin-install.php' === $pagenow ) {
	$heading_tag = 'h1';
}
echo "<$heading_tag id='request-filesystem-credentials-title'>" . __( 'Connection Information' ) . "</$heading_tag>";
?>
<p id="request-filesystem-credentials-desc"><?php
	$label_user = __('Username');
	$label_pass = __('Password');
	_e('To perform the requested action, ClassicPress needs to access your web server.');
	echo ' ';
	if ( ( isset( $types['ftp'] ) || isset( $types['ftps'] ) ) ) {
		if ( isset( $types['ssh'] ) ) {
			_e('Please enter your FTP or SSH credentials to proceed.');
			$label_user = __('FTP/SSH Username');
			$label_pass = __('FTP/SSH Password');
		} else {
			_e('Please enter your FTP credentials to proceed.');
			$label_user = __('FTP Username');
			$label_pass = __('FTP Password');
		}
		echo ' ';
	}
	_e('If you do not remember your credentials, you should contact your web host.');
?></p>
<label for="hostname">
	<span class="field-title"><?php _e( 'Hostname' ) ?></span>
	<input name="hostname" type="text" id="hostname" aria-describedby="request-filesystem-credentials-desc" class="code" placeholder="<?php esc_attr_e( 'example: www.wordpress.org' ) ?>" value="<?php echo esc_attr($hostname); if ( !empty($port) ) echo ":$port"; ?>"<?php disabled( defined('FTP_HOST') ); ?> />
</label>
<div class="ftp-username">
	<label for="username">
		<span class="field-title"><?php echo $label_user; ?></span>
		<input name="username" type="text" id="username" value="<?php echo esc_attr($username) ?>"<?php disabled( defined('FTP_USER') ); ?> />
	</label>
</div>
<div class="ftp-password">
	<label for="password">
		<span class="field-title"><?php echo $label_pass; ?></span>
		<input name="password" type="password" id="password" value="<?php if ( defined('FTP_PASS') ) echo '*****'; ?>"<?php disabled( defined('FTP_PASS') ); ?> />
		<em><?php if ( ! defined('FTP_PASS') ) _e( 'This password will not be stored on the server.' ); ?></em>
	</label>
</div>
<fieldset>
<legend><?php _e( 'Connection Type' ); ?></legend>
<?php
	$disabled = disabled( ( defined( 'FTP_SSL' ) && FTP_SSL ) || ( defined( 'FTP_SSH' ) && FTP_SSH ), true, false );
	foreach ( $types as $name => $text ) : ?>
	<label for="<?php echo esc_attr( $name ) ?>">
		<input type="radio" name="connection_type" id="<?php echo esc_attr( $name ) ?>" value="<?php echo esc_attr( $name ) ?>"<?php checked( $name, $connection_type ); echo $disabled; ?> />
		<?php echo $text; ?>
	</label>
<?php
	endforeach;
?>
</fieldset>
<?php
if ( isset( $types['ssh'] ) ) {
	$hidden_class = '';
	if ( 'ssh' != $connection_type || empty( $connection_type ) ) {
		$hidden_class = ' class="hidden"';
	}
?>
<fieldset id="ssh-keys"<?php echo $hidden_class; ?>>
<legend><?php _e( 'Authentication Keys' ); ?></legend>
<label for="public_key">
	<span class="field-title"><?php _e('Public Key:') ?></span>
	<input name="public_key" type="text" id="public_key" aria-describedby="auth-keys-desc" value="<?php echo esc_attr($public_key) ?>"<?php disabled( defined('FTP_PUBKEY') ); ?> />
</label>
<label for="private_key">
	<span class="field-title"><?php _e('Private Key:') ?></span>
	<input name="private_key" type="text" id="private_key" value="<?php echo esc_attr($private_key) ?>"<?php disabled( defined('FTP_PRIKEY') ); ?> />
</label>
<p id="auth-keys-desc"><?php _e( 'Enter the location on the server where the public and private keys are located. If a passphrase is needed, enter that in the password field above.' ) ?></p>
</fieldset>
<?php
}

foreach ( (array) $extra_fields as $field ) {
	if ( isset( $submitted_form[ $field ] ) )
		echo '<input type="hidden" name="' . esc_attr( $field ) . '" value="' . esc_attr( $submitted_form[ $field ] ) . '" />';
}
?>
	<p class="request-filesystem-credentials-action-buttons">
		<?php wp_nonce_field( 'filesystem-credentials', '_fs_nonce', false, true ); ?>
		<button class="button cancel-button" data-js-action="close" type="button"><?php _e( 'Cancel' ); ?></button>
		<?php submit_button( __( 'Proceed' ), '', 'upgrade', false ); ?>
	</p>
</div>
</form>
<?php
	return false;
}

/**
 * Print the filesystem credentials modal when needed.
 *
 * @since WP-4.2.0
 */
function wp_print_request_filesystem_credentials_modal() {
	$filesystem_method = get_filesystem_method();
	ob_start();
	$filesystem_credentials_are_stored = request_filesystem_credentials( self_admin_url() );
	ob_end_clean();
	$request_filesystem_credentials = ( $filesystem_method != 'direct' && ! $filesystem_credentials_are_stored );
	if ( ! $request_filesystem_credentials ) {
		return;
	}
	?>
	<div id="request-filesystem-credentials-dialog" class="notification-dialog-wrap request-filesystem-credentials-dialog">
		<div class="notification-dialog-background"></div>
		<div class="notification-dialog" role="dialog" aria-labelledby="request-filesystem-credentials-title" tabindex="0">
			<div class="request-filesystem-credentials-dialog-content">
				<?php request_filesystem_credentials( site_url() ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Generate a single group for the personal data export report.
 *
 * @since WP-4.9.6
 *
 * @param array $group_data {
 *     The group data to render.
 *
 *     @type string $group_label  The user-facing heading for the group, e.g. 'Comments'.
 *     @type array  $items        {
 *         An array of group items.
 *
 *         @type array  $group_item_data  {
 *             An array of name-value pairs for the item.
 *
 *             @type string $name   The user-facing name of an item name-value pair, e.g. 'IP Address'.
 *             @type string $value  The user-facing value of an item data pair, e.g. '50.60.70.0'.
 *         }
 *     }
 * }
 * @return string The HTML for this group and its items.
 */
function wp_privacy_generate_personal_data_export_group_html( $group_data ) {
	$allowed_tags      = array(
		'a' => array(
			'href'   => array(),
			'target' => array()
		),
		'br' => array()
	);
	$allowed_protocols = array( 'http', 'https' );
	$group_html        = '';

	$group_html .= '<h2>' . esc_html( $group_data['group_label'] ) . '</h2>';
	$group_html .= '<div>';

	foreach ( (array) $group_data['items'] as $group_item_id => $group_item_data ) {
		$group_html .= '<table>';
		$group_html .= '<tbody>';

		foreach ( (array) $group_item_data as $group_item_datum ) {
			$value = $group_item_datum['value'];
			// If it looks like a link, make it a link
			if ( false === strpos( $value, ' ' ) && ( 0 === strpos( $value, 'http://' ) || 0 === strpos( $value, 'https://' ) ) ) {
				$value = '<a href="' . esc_url( $value ) . '">' . esc_html( $value ) . '</a>';
			}

			$group_html .= '<tr>';
			$group_html .= '<th>' . esc_html( $group_item_datum['name'] ) . '</th>';
			$group_html .= '<td>' . wp_kses( $value, $allowed_tags, $allowed_protocols ) . '</td>';
			$group_html .= '</tr>';
		}

		$group_html .= '</tbody>';
		$group_html .= '</table>';
	}

	$group_html .= '</div>';

	return $group_html;
}

/**
 * Generate the personal data export file.
 *
 * @since WP-4.9.6
 *
 * @param int $request_id The export request ID.
 */
function wp_privacy_generate_personal_data_export_file( $request_id ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_send_json_error( __( 'Unable to generate export file. ZipArchive not available.' ) );
	}

	// Get the request data.
	$request = wp_get_user_request_data( $request_id );

	if ( ! $request || 'export_personal_data' !== $request->action_name ) {
		wp_send_json_error( __( 'Invalid request ID when generating export file.' ) );
	}

	$email_address = $request->email;

	if ( ! is_email( $email_address ) ) {
		wp_send_json_error( __( 'Invalid email address when generating export file.' ) );
	}

	// Create the exports folder if needed.
	$exports_dir = wp_privacy_exports_dir();
	$exports_url = wp_privacy_exports_url();

	if ( ! wp_mkdir_p( $exports_dir ) ) {
		wp_send_json_error( __( 'Unable to create export folder.' ) );
	}

	// Protect export folder from browsing.
	$index_pathname = $exports_dir . 'index.html';
	if ( ! file_exists( $index_pathname ) ) {
		$file = fopen( $index_pathname, 'w' );
		if ( false === $file ) {
			wp_send_json_error( __( 'Unable to protect export folder from browsing.' ) );
		}
		fwrite( $file, '<!-- Silence is golden. -->' );
		fclose( $file );
	}

	$stripped_email       = str_replace( '@', '-at-', $email_address );
	$stripped_email       = sanitize_title( $stripped_email ); // slugify the email address
	$obscura              = wp_generate_password( 32, false, false );
	$file_basename        = 'wp-personal-data-file-' . $stripped_email . '-' . $obscura;
	$html_report_filename = $file_basename . '.html';
	$html_report_pathname = wp_normalize_path( $exports_dir . $html_report_filename );
	$file = fopen( $html_report_pathname, 'w' );
	if ( false === $file ) {
		wp_send_json_error( __( 'Unable to open export file (HTML report) for writing.' ) );
	}

	$title = sprintf(
		/* translators: %s: user's e-mail address */
		__( 'Personal Data Export for %s' ),
		$email_address
	);

	// Open HTML.
	fwrite( $file, "<!DOCTYPE html>\n" );
	fwrite( $file, "<html>\n" );

	// Head.
	fwrite( $file, "<head>\n" );
	fwrite( $file, "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />\n" );
	fwrite( $file, "<style type='text/css'>" );
	fwrite( $file, "body { color: black; font-family: Arial, sans-serif; font-size: 11pt; margin: 15px auto; width: 860px; }" );
	fwrite( $file, "table { background: #f0f0f0; border: 1px solid #ddd; margin-bottom: 20px; width: 100%; }" );
	fwrite( $file, "th { padding: 5px; text-align: left; width: 20%; }" );
	fwrite( $file, "td { padding: 5px; }" );
	fwrite( $file, "tr:nth-child(odd) { background-color: #fafafa; }" );
	fwrite( $file, "</style>" );
	fwrite( $file, "<title>" );
	fwrite( $file, esc_html( $title ) );
	fwrite( $file, "</title>" );
	fwrite( $file, "</head>\n" );

	// Body.
	fwrite( $file, "<body>\n" );

	// Heading.
	fwrite( $file, "<h1>" . esc_html__( 'Personal Data Export' ) . "</h1>" );

	// And now, all the Groups.
	$groups = get_post_meta( $request_id, '_export_data_grouped', true );

	// First, build an "About" group on the fly for this report.
	$about_group = array(
		/* translators: Header for the About section in a personal data export. */
		'group_label' => _x( 'About', 'personal data group label' ),
		'items'       => array(
			'about-1' => array(
				array(
					'name'  => _x( 'Report generated for', 'email address' ),
					'value' => $email_address,
				),
				array(
					'name'  => _x( 'For site', 'website name' ),
					'value' => get_bloginfo( 'name' ),
				),
				array(
					'name'  => _x( 'At URL', 'website URL' ),
					'value' => get_bloginfo( 'url' ),
				),
				array(
					'name'  => _x( 'On', 'date/time' ),
					'value' => current_time( 'mysql' ),
				),
			),
		),
	);

	// Merge in the special about group.
	$groups = array_merge( array( 'about' => $about_group ), $groups );

	// Now, iterate over every group in $groups and have the formatter render it in HTML.
	foreach ( (array) $groups as $group_id => $group_data ) {
		fwrite( $file, wp_privacy_generate_personal_data_export_group_html( $group_data ) );
	}

	fwrite( $file, "</body>\n" );

	// Close HTML.
	fwrite( $file, "</html>\n" );
	fclose( $file );

	/*
	 * Now, generate the ZIP.
	 *
	 * If an archive has already been generated, then remove it and reuse the
	 * filename, to avoid breaking any URLs that may have been previously sent
	 * via email.
	 */
	$error            = false;
	$archive_url      = get_post_meta( $request_id, '_export_file_url', true );
	$archive_pathname = get_post_meta( $request_id, '_export_file_path', true );

	if ( empty( $archive_pathname ) || empty( $archive_url ) ) {
		$archive_filename = $file_basename . '.zip';
		$archive_pathname = $exports_dir . $archive_filename;
		$archive_url      = $exports_url . $archive_filename;

		update_post_meta( $request_id, '_export_file_url', $archive_url );
		update_post_meta( $request_id, '_export_file_path', wp_normalize_path( $archive_pathname ) );
	}

	if ( ! empty( $archive_pathname ) && file_exists( $archive_pathname ) ) {
		wp_delete_file( $archive_pathname );
	}

	$zip = new ZipArchive;
	if ( true === $zip->open( $archive_pathname, ZipArchive::CREATE ) ) {
		if ( ! $zip->addFile( $html_report_pathname, 'index.html' ) ) {
			$error = __( 'Unable to add data to export file.' );
		}

		$zip->close();

		if ( ! $error ) {
			/**
			 * Fires right after all personal data has been written to the export file.
			 *
			 * @since WP-4.9.6
			 *
			 * @param string $archive_pathname     The full path to the export file on the filesystem.
			 * @param string $archive_url          The URL of the archive file.
			 * @param string $html_report_pathname The full path to the personal data report on the filesystem.
			 * @param int    $request_id           The export request ID.
			 */
			do_action( 'wp_privacy_personal_data_export_file_created', $archive_pathname, $archive_url, $html_report_pathname, $request_id );
		}
	} else {
		$error = __( 'Unable to open export file (archive) for writing.' );
	}

	// And remove the HTML file.
	unlink( $html_report_pathname );

	if ( $error ) {
		wp_send_json_error( $error );
	}
}

/**
 * Send an email to the user with a link to the personal data export file
 *
 * @since WP-4.9.6
 *
 * @param int $request_id The request ID for this personal data export.
 * @return true|WP_Error True on success or `WP_Error` on failure.
 */
function wp_privacy_send_personal_data_export_email( $request_id ) {
	// Get the request data.
	$request = wp_get_user_request_data( $request_id );

	if ( ! $request || 'export_personal_data' !== $request->action_name ) {
		return new WP_Error( 'invalid', __( 'Invalid request ID when sending personal data export email.' ) );
	}

	/** This filter is documented in wp-includes/functions.php */
	$expiration      = apply_filters( 'wp_privacy_export_expiration', 3 * DAY_IN_SECONDS );
	$expiration_date = date_i18n( get_option( 'date_format' ), time() + $expiration );

/* translators: Do not translate EXPIRATION, LINK, SITENAME, SITEURL: those are placeholders. */
$email_text = __(
'Hello,

Your request for an export of personal data has been completed. You may
download your personal data by clicking on the link below. For privacy
and security, we will automatically delete the file on ###EXPIRATION###,
so please download it before then.

###LINK###

Regards,
All at ###SITENAME###
###SITEURL###'
);

	/**
	 * Filters the text of the email sent with a personal data export file.
	 *
	 * The following strings have a special meaning and will get replaced dynamically:
	 * ###EXPIRATION###         The date when the URL will be automatically deleted.
	 * ###LINK###               URL of the personal data export file for the user.
	 * ###SITENAME###           The name of the site.
	 * ###SITEURL###            The URL to the site.
	 *
	 * @since WP-4.9.6
	 *
	 * @param string $email_text     Text in the email.
	 * @param int    $request_id     The request ID for this personal data export.
	 */
	$content = apply_filters( 'wp_privacy_personal_data_email_content', $email_text, $request_id );

	$email_address = $request->email;
	$export_file_url = get_post_meta( $request_id, '_export_file_url', true );
	$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	$site_url = home_url();

	$content = str_replace( '###EXPIRATION###', $expiration_date, $content );
	$content = str_replace( '###LINK###', esc_url_raw( $export_file_url ), $content );
	$content = str_replace( '###EMAIL###', $email_address, $content );
	$content = str_replace( '###SITENAME###', $site_name, $content );
	$content = str_replace( '###SITEURL###', esc_url_raw( $site_url ), $content );

	$mail_success = wp_mail(
		$email_address,
		sprintf(
			__( '[%s] Personal Data Export' ),
			$site_name
		),
		$content
	);

	if ( ! $mail_success ) {
		return new WP_Error( 'error', __( 'Unable to send personal data export email.' ) );
	}

	return true;
}

/**
 * Intercept personal data exporter page ajax responses in order to assemble the personal data export file.
 * @see wp_privacy_personal_data_export_page
 * @since WP-4.9.6
 *
 * @param array  $response        The response from the personal data exporter for the given page.
 * @param int    $exporter_index  The index of the personal data exporter. Begins at 1.
 * @param string $email_address   The email address of the user whose personal data this is.
 * @param int    $page            The page of personal data for this exporter. Begins at 1.
 * @param int    $request_id      The request ID for this personal data export.
 * @param bool   $send_as_email   Whether the final results of the export should be emailed to the user.
 * @param string $exporter_key    The slug (key) of the exporter.
 * @return array The filtered response.
 */
function wp_privacy_process_personal_data_export_page( $response, $exporter_index, $email_address, $page, $request_id, $send_as_email, $exporter_key ) {
	/* Do some simple checks on the shape of the response from the exporter.
	 * If the exporter response is malformed, don't attempt to consume it - let it
	 * pass through to generate a warning to the user by default ajax processing.
	 */
	if ( ! is_array( $response ) ) {
		return $response;
	}

	if ( ! array_key_exists( 'done', $response ) ) {
		return $response;
	}

	if ( ! array_key_exists( 'data', $response ) ) {
		return $response;
	}

	if ( ! is_array( $response['data'] ) ) {
		return $response;
	}

	// Get the request data.
	$request = wp_get_user_request_data( $request_id );

	if ( ! $request || 'export_personal_data' !== $request->action_name ) {
		wp_send_json_error( __( 'Invalid request ID when merging exporter data.' ) );
	}

	$export_data = array();

	// First exporter, first page? Reset the report data accumulation array.
	if ( 1 === $exporter_index && 1 === $page ) {
		update_post_meta( $request_id, '_export_data_raw', $export_data );
	} else {
		$export_data = get_post_meta( $request_id, '_export_data_raw', true );
	}

	// Now, merge the data from the exporter response into the data we have accumulated already.
	$export_data = array_merge( $export_data, $response['data'] );
	update_post_meta( $request_id, '_export_data_raw', $export_data );

	// If we are not yet on the last page of the last exporter, return now.
	/** This filter is documented in wp-admin/includes/ajax-actions.php */
	$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
	$is_last_exporter = $exporter_index === count( $exporters );
	$exporter_done = $response['done'];
	if ( ! $is_last_exporter || ! $exporter_done ) {
		return $response;
	}

	// Last exporter, last page - let's prepare the export file.

	// First we need to re-organize the raw data hierarchically in groups and items.
	$groups = array();
	foreach ( (array) $export_data as $export_datum ) {
		$group_id    = $export_datum['group_id'];
		$group_label = $export_datum['group_label'];
		if ( ! array_key_exists( $group_id, $groups ) ) {
			$groups[ $group_id ] = array(
				'group_label' => $group_label,
				'items'       => array(),
			);
		}

		$item_id = $export_datum['item_id'];
		if ( ! array_key_exists( $item_id, $groups[ $group_id ]['items'] ) ) {
			$groups[ $group_id ]['items'][ $item_id ] = array();
		}

		$old_item_data = $groups[ $group_id ]['items'][ $item_id ];
		$merged_item_data = array_merge( $export_datum['data'], $old_item_data );
		$groups[ $group_id ]['items'][ $item_id ] = $merged_item_data;
	}

	// Then save the grouped data into the request.
	delete_post_meta( $request_id, '_export_data_raw' );
	update_post_meta( $request_id, '_export_data_grouped', $groups );

	/**
	 * Generate the export file from the collected, grouped personal data.
	 *
	 * @since WP-4.9.6
	 *
	 * @param int $request_id The export request ID.
	 */
	do_action( 'wp_privacy_personal_data_export_file', $request_id );

	// Clear the grouped data now that it is no longer needed.
	delete_post_meta( $request_id, '_export_data_grouped' );

	// If the destination is email, send it now.
	if ( $send_as_email ) {
		$mail_success = wp_privacy_send_personal_data_export_email( $request_id );
		if ( is_wp_error( $mail_success ) ) {
			wp_send_json_error( $mail_success->get_error_message() );
		}
	} else {
		// Modify the response to include the URL of the export file so the browser can fetch it.
		$export_file_url = get_post_meta( $request_id, '_export_file_url', true );
		if ( ! empty( $export_file_url ) ) {
			$response['url'] = $export_file_url;
		}
	}

	// Update the request to completed state.
	_wp_privacy_completed_request( $request_id );

	return $response;
}
