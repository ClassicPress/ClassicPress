<?php
/**
 * Multisite administration functions.
 *
 * @package ClassicPress
 * @subpackage Multisite
 * @since WP-3.0.0
 */

/**
 * Determine if uploaded file exceeds space quota.
 *
 * @since WP-3.0.0
 *
 * @param array $file $_FILES array for a given file.
 * @return array $_FILES array with 'error' key set if file exceeds quota. 'error' is empty otherwise.
 */
function check_upload_size( $file ) {
	if ( get_site_option( 'upload_space_check_disabled' ) )
		return $file;

	if ( $file['error'] != '0' ) // there's already an error
		return $file;

	if ( defined( 'WP_IMPORTING' ) )
		return $file;

	$space_left = get_upload_space_available();

	$file_size = filesize( $file['tmp_name'] );
	if ( $space_left < $file_size ) {
		/* translators: 1: Required disk space in kilobytes */
		$file['error'] = sprintf( __( 'Not enough space to upload. %1$s KB needed.' ), number_format( ( $file_size - $space_left ) / KB_IN_BYTES ) );
	}

	if ( $file_size > ( KB_IN_BYTES * get_site_option( 'fileupload_maxk', 1500 ) ) ) {
		/* translators: 1: Maximum allowed file size in kilobytes */
		$file['error'] = sprintf( __( 'This file is too big. Files must be less than %1$s KB in size.' ), get_site_option( 'fileupload_maxk', 1500 ) );
	}

	if ( upload_is_user_over_quota( false ) ) {
		$file['error'] = __( 'You have used your space quota. Please delete files before uploading.' );
	}

	if ( $file['error'] != '0' && ! isset( $_POST['html-upload'] ) && ! wp_doing_ajax() ) {
		wp_die( $file['error'] . ' <a href="javascript:history.go(-1)">' . __( 'Back' ) . '</a>' );
	}

	return $file;
}

/**
 * Delete a site.
 *
 * @since WP-3.0.0
 *
 * @global wpdb $wpdb ClassicPress database abstraction object.
 *
 * @param int  $blog_id Site ID.
 * @param bool $drop    True if site's database tables should be dropped. Default is false.
 */
function wpmu_delete_blog( $blog_id, $drop = false ) {
	global $wpdb;

	$switch = false;
	if ( get_current_blog_id() != $blog_id ) {
		$switch = true;
		switch_to_blog( $blog_id );
	}

	$blog = get_site( $blog_id );
	/**
	 * Fires before a site is deleted.
	 *
	 * @since WP-MU (3.0.0)
	 *
	 * @param int  $blog_id The site ID.
	 * @param bool $drop    True if site's table should be dropped. Default is false.
	 */
	do_action( 'delete_blog', $blog_id, $drop );

	$users = get_users( array( 'blog_id' => $blog_id, 'fields' => 'ids' ) );

	// Remove users from this blog.
	if ( ! empty( $users ) ) {
		foreach ( $users as $user_id ) {
			remove_user_from_blog( $user_id, $blog_id );
		}
	}

	update_blog_status( $blog_id, 'deleted', 1 );

	$current_network = get_network();

	// If a full blog object is not available, do not destroy anything.
	if ( $drop && ! $blog ) {
		$drop = false;
	}

	// Don't destroy the initial, main, or root blog.
	if ( $drop && ( 1 == $blog_id || is_main_site( $blog_id ) || ( $blog->path == $current_network->path && $blog->domain == $current_network->domain ) ) ) {
		$drop = false;
	}

	$upload_path = trim( get_option( 'upload_path' ) );

	// If ms_files_rewriting is enabled and upload_path is empty, wp_upload_dir is not reliable.
	if ( $drop && get_site_option( 'ms_files_rewriting' ) && empty( $upload_path ) ) {
		$drop = false;
	}

	if ( $drop ) {
		$uploads = wp_get_upload_dir();

		$tables = $wpdb->tables( 'blog' );
		/**
		 * Filters the tables to drop when the site is deleted.
		 *
		 * @since WP-MU (3.0.0)
		 *
		 * @param array $tables  The site tables to be dropped.
		 * @param int   $blog_id The ID of the site to drop tables for.
		 */
		$drop_tables = apply_filters( 'wpmu_drop_tables', $tables, $blog_id );

		foreach ( (array) $drop_tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `$table`" );
		}

		$wpdb->delete( $wpdb->blogs, array( 'blog_id' => $blog_id ) );

		/**
		 * Filters the upload base directory to delete when the site is deleted.
		 *
		 * @since WP-MU (3.0.0)
		 *
		 * @param string $uploads['basedir'] Uploads path without subdirectory. @see wp_upload_dir()
		 * @param int    $blog_id            The site ID.
		 */
		$dir = apply_filters( 'wpmu_delete_blog_upload_dir', $uploads['basedir'], $blog_id );
		$dir = rtrim( $dir, DIRECTORY_SEPARATOR );
		$top_dir = $dir;
		$stack = array($dir);
		$index = 0;

		while ( $index < count( $stack ) ) {
			// Get indexed directory from stack
			$dir = $stack[$index];

			$dh = @opendir( $dir );
			if ( $dh ) {
				while ( ( $file = @readdir( $dh ) ) !== false ) {
					if ( $file == '.' || $file == '..' )
						continue;

					if ( @is_dir( $dir . DIRECTORY_SEPARATOR . $file ) ) {
						$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
					} elseif ( @is_file( $dir . DIRECTORY_SEPARATOR . $file ) ) {
						@unlink( $dir . DIRECTORY_SEPARATOR . $file );
					}
				}
				@closedir( $dh );
			}
			$index++;
		}

		$stack = array_reverse( $stack ); // Last added dirs are deepest
		foreach ( (array) $stack as $dir ) {
			if ( $dir != $top_dir)
			@rmdir( $dir );
		}

		clean_blog_cache( $blog );
	}

	/**
	 * Fires after the site is deleted from the network.
	 *
	 * @since WP-4.8.0
	 *
	 * @param int  $blog_id The site ID.
	 * @param bool $drop    True if site's tables should be dropped. Default is false.
	 */
	do_action( 'deleted_blog', $blog_id, $drop );

	if ( $switch )
		restore_current_blog();
}

/**
 * Delete a user from the network and remove from all sites.
 *
 * @since WP-3.0.0
 *
 * @todo Merge with wp_delete_user() ?
 *
 * @global wpdb $wpdb ClassicPress database abstraction object.
 *
 * @param int $id The user ID.
 * @return bool True if the user was deleted, otherwise false.
 */
function wpmu_delete_user( $id ) {
	global $wpdb;

	if ( ! is_numeric( $id ) ) {
		return false;
	}

	$id = (int) $id;
	$user = new WP_User( $id );

	if ( !$user->exists() )
		return false;

	// Global super-administrators are protected, and cannot be deleted.
	$_super_admins = get_super_admins();
	if ( in_array( $user->user_login, $_super_admins, true ) ) {
		return false;
	}

	/**
	 * Fires before a user is deleted from the network.
	 *
	 * @since WP-MU (3.0.0)
	 * @since WP-5.5.0 Added the `$user` parameter.
	 *
	 * @param int     $id   ID of the user about to be deleted from the network.
	 * @param WP_User $user WP_User object of the user about to be deleted from the network.
	 */
	do_action( 'wpmu_delete_user', $id, $user );

	$blogs = get_blogs_of_user( $id );

	if ( ! empty( $blogs ) ) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog->userblog_id );
			remove_user_from_blog( $id, $blog->userblog_id );

			$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_author = %d", $id ) );
			foreach ( (array) $post_ids as $post_id ) {
				wp_delete_post( $post_id );
			}

			// Clean links
			$link_ids = $wpdb->get_col( $wpdb->prepare( "SELECT link_id FROM $wpdb->links WHERE link_owner = %d", $id ) );

			if ( $link_ids ) {
				foreach ( $link_ids as $link_id )
					wp_delete_link( $link_id );
			}

			restore_current_blog();
		}
	}

	$meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $id ) );
	foreach ( $meta as $mid )
		delete_metadata_by_mid( 'user', $mid );

	$wpdb->delete( $wpdb->users, array( 'ID' => $id ) );

	clean_user_cache( $user );

	/** This action is documented in wp-admin/includes/user.php */
	do_action( 'deleted_user', $id, null, $user );

	return true;
}

/**
 * Check whether a site has used its allotted upload space.
 *
 * @since WP-MU (3.0.0)
 *
 * @param bool $echo Optional. If $echo is set and the quota is exceeded, a warning message is echoed. Default is true.
 * @return bool True if user is over upload space quota, otherwise false.
 */
function upload_is_user_over_quota( $echo = true ) {
	if ( get_site_option( 'upload_space_check_disabled' ) )
		return false;

	$space_allowed = get_space_allowed();
	if ( ! is_numeric( $space_allowed ) ) {
		$space_allowed = 10; // Default space allowed is 10 MB
	}
	$space_used = get_space_used();

	if ( ( $space_allowed - $space_used ) < 0 ) {
		if ( $echo )
			_e( 'Sorry, you have used your space allocation. Please delete some files to upload more files.' );
		return true;
	} else {
		return false;
	}
}

/**
 * Displays the amount of disk space used by the current site. Not used in core.
 *
 * @since WP-MU (3.0.0)
 */
function display_space_usage() {
	$space_allowed = get_space_allowed();
	$space_used = get_space_used();

	$percent_used = ( $space_used / $space_allowed ) * 100;

	if ( $space_allowed > 1000 ) {
		$space = number_format( $space_allowed / KB_IN_BYTES );
		/* translators: Gigabytes */
		$space .= __( 'GB' );
	} else {
		$space = number_format( $space_allowed );
		/* translators: Megabytes */
		$space .= __( 'MB' );
	}
	?>
	<strong><?php
		/* translators: Storage space that's been used. 1: Percentage of used space, 2: Total space allowed in megabytes or gigabytes */
		printf( __( 'Used: %1$s%% of %2$s' ), number_format( $percent_used ), $space );
	?></strong>
	<?php
}

/**
 * Get the remaining upload space for this site.
 *
 * @since WP-MU (3.0.0)
 *
 * @param int $size Current max size in bytes
 * @return int Max size in bytes
 */
function fix_import_form_size( $size ) {
	if ( upload_is_user_over_quota( false ) ) {
		return 0;
	}
	$available = get_upload_space_available();
	return min( $size, $available );
}

/**
 * Displays the site upload space quota setting form on the Edit Site Settings screen.
 *
 * @since WP-3.0.0
 *
 * @param int $id The ID of the site to display the setting for.
 */
function upload_space_setting( $id ) {
	switch_to_blog( $id );
	$quota = get_option( 'blog_upload_space' );
	restore_current_blog();

	if ( !$quota )
		$quota = '';

	?>
	<tr>
		<th><label for="blog-upload-space-number"><?php _e( 'Site Upload Space Quota' ); ?></label></th>
		<td>
			<input type="number" step="1" min="0" style="width: 100px" name="option[blog_upload_space]" id="blog-upload-space-number" aria-describedby="blog-upload-space-desc" value="<?php echo $quota; ?>" />
			<span id="blog-upload-space-desc"><span class="screen-reader-text"><?php _e( 'Size in megabytes' ); ?></span> <?php _e( 'MB (Leave blank for network default)' ); ?></span>
		</td>
	</tr>
	<?php
}

/**
 * Update the status of a user in the database.
 *
 * Used in core to mark a user as spam or "ham" (not spam) in Multisite.
 *
 * @since WP-3.0.0
 *
 * @global wpdb $wpdb ClassicPress database abstraction object.
 *
 * @param int    $id         The user ID.
 * @param string $pref       The column in the wp_users table to update the user's status
 *                           in (presumably user_status, spam, or deleted).
 * @param int    $value      The new status for the user.
 * @param null   $deprecated Deprecated as of 3.0.2 and should not be used.
 * @return int   The initially passed $value.
 */
function update_user_status( $id, $pref, $value, $deprecated = null ) {
	global $wpdb;

	if ( null !== $deprecated )
		_deprecated_argument( __FUNCTION__, 'WP-3.0.2' );

	$wpdb->update( $wpdb->users, array( sanitize_key( $pref ) => $value ), array( 'ID' => $id ) );

	$user = new WP_User( $id );
	clean_user_cache( $user );

	if ( $pref == 'spam' ) {
		if ( $value == 1 ) {
			/**
			 * Fires after the user is marked as a SPAM user.
			 *
			 * @since WP-3.0.0
			 *
			 * @param int $id ID of the user marked as SPAM.
			 */
			do_action( 'make_spam_user', $id );
		} else {
			/**
			 * Fires after the user is marked as a HAM user. Opposite of SPAM.
			 *
			 * @since WP-3.0.0
			 *
			 * @param int $id ID of the user marked as HAM.
			 */
			do_action( 'make_ham_user', $id );
		}
	}

	return $value;
}

/**
 * Cleans the user cache for a specific user.
 *
 * @since WP-3.0.0
 *
 * @param int $id The user ID.
 * @return bool|int The ID of the refreshed user or false if the user does not exist.
 */
function refresh_user_details( $id ) {
	$id = (int) $id;

	if ( !$user = get_userdata( $id ) )
		return false;

	clean_user_cache( $user );

	return $id;
}

/**
 * Returns the language for a language code.
 *
 * @since WP-3.0.0
 *
 * @param string $code Optional. The two-letter language code. Default empty.
 * @return string The language corresponding to $code if it exists. If it does not exist,
 *                then the first two letters of $code is returned.
 */
function format_code_lang( $code = '' ) {
	$code = strtolower( substr( $code, 0, 2 ) );
	$lang_codes = array(
		'aa' => 'Afar', 'ab' => 'Abkhazian', 'af' => 'Afrikaans', 'ak' => 'Akan', 'sq' => 'Albanian', 'am' => 'Amharic', 'ar' => 'Arabic', 'an' => 'Aragonese', 'hy' => 'Armenian', 'as' => 'Assamese', 'av' => 'Avaric', 'ae' => 'Avestan', 'ay' => 'Aymara', 'az' => 'Azerbaijani', 'ba' => 'Bashkir', 'bm' => 'Bambara', 'eu' => 'Basque', 'be' => 'Belarusian', 'bn' => 'Bengali',
		'bh' => 'Bihari', 'bi' => 'Bislama', 'bs' => 'Bosnian', 'br' => 'Breton', 'bg' => 'Bulgarian', 'my' => 'Burmese', 'ca' => 'Catalan; Valencian', 'ch' => 'Chamorro', 'ce' => 'Chechen', 'zh' => 'Chinese', 'cu' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic', 'cv' => 'Chuvash', 'kw' => 'Cornish', 'co' => 'Corsican', 'cr' => 'Cree',
		'cs' => 'Czech', 'da' => 'Danish', 'dv' => 'Divehi; Dhivehi; Maldivian', 'nl' => 'Dutch; Flemish', 'dz' => 'Dzongkha', 'en' => 'English', 'eo' => 'Esperanto', 'et' => 'Estonian', 'ee' => 'Ewe', 'fo' => 'Faroese', 'fj' => 'Fijjian', 'fi' => 'Finnish', 'fr' => 'French', 'fy' => 'Western Frisian', 'ff' => 'Fulah', 'ka' => 'Georgian', 'de' => 'German', 'gd' => 'Gaelic; Scottish Gaelic',
		'ga' => 'Irish', 'gl' => 'Galician', 'gv' => 'Manx', 'el' => 'Greek, Modern', 'gn' => 'Guarani', 'gu' => 'Gujarati', 'ht' => 'Haitian; Haitian Creole', 'ha' => 'Hausa', 'he' => 'Hebrew', 'hz' => 'Herero', 'hi' => 'Hindi', 'ho' => 'Hiri Motu', 'hu' => 'Hungarian', 'ig' => 'Igbo', 'is' => 'Icelandic', 'io' => 'Ido', 'ii' => 'Sichuan Yi', 'iu' => 'Inuktitut', 'ie' => 'Interlingue',
		'ia' => 'Interlingua (International Auxiliary Language Association)', 'id' => 'Indonesian', 'ik' => 'Inupiaq', 'it' => 'Italian', 'jv' => 'Javanese', 'ja' => 'Japanese', 'kl' => 'Kalaallisut; Greenlandic', 'kn' => 'Kannada', 'ks' => 'Kashmiri', 'kr' => 'Kanuri', 'kk' => 'Kazakh', 'km' => 'Central Khmer', 'ki' => 'Kikuyu; Gikuyu', 'rw' => 'Kinyarwanda', 'ky' => 'Kirghiz; Kyrgyz',
		'kv' => 'Komi', 'kg' => 'Kongo', 'ko' => 'Korean', 'kj' => 'Kuanyama; Kwanyama', 'ku' => 'Kurdish', 'lo' => 'Lao', 'la' => 'Latin', 'lv' => 'Latvian', 'li' => 'Limburgan; Limburger; Limburgish', 'ln' => 'Lingala', 'lt' => 'Lithuanian', 'lb' => 'Luxembourgish; Letzeburgesch', 'lu' => 'Luba-Katanga', 'lg' => 'Ganda', 'mk' => 'Macedonian', 'mh' => 'Marshallese', 'ml' => 'Malayalam',
		'mi' => 'Maori', 'mr' => 'Marathi', 'ms' => 'Malay', 'mg' => 'Malagasy', 'mt' => 'Maltese', 'mo' => 'Moldavian', 'mn' => 'Mongolian', 'na' => 'Nauru', 'nv' => 'Navajo; Navaho', 'nr' => 'Ndebele, South; South Ndebele', 'nd' => 'Ndebele, North; North Ndebele', 'ng' => 'Ndonga', 'ne' => 'Nepali', 'nn' => 'Norwegian Nynorsk; Nynorsk, Norwegian', 'nb' => 'Bokmål, Norwegian, Norwegian Bokmål',
		'no' => 'Norwegian', 'ny' => 'Chichewa; Chewa; Nyanja', 'oc' => 'Occitan, Provençal', 'oj' => 'Ojibwa', 'or' => 'Oriya', 'om' => 'Oromo', 'os' => 'Ossetian; Ossetic', 'pa' => 'Panjabi; Punjabi', 'fa' => 'Persian', 'pi' => 'Pali', 'pl' => 'Polish', 'pt' => 'Portuguese', 'ps' => 'Pushto', 'qu' => 'Quechua', 'rm' => 'Romansh', 'ro' => 'Romanian', 'rn' => 'Rundi', 'ru' => 'Russian',
		'sg' => 'Sango', 'sa' => 'Sanskrit', 'sr' => 'Serbian', 'hr' => 'Croatian', 'si' => 'Sinhala; Sinhalese', 'sk' => 'Slovak', 'sl' => 'Slovenian', 'se' => 'Northern Sami', 'sm' => 'Samoan', 'sn' => 'Shona', 'sd' => 'Sindhi', 'so' => 'Somali', 'st' => 'Sotho, Southern', 'es' => 'Spanish; Castilian', 'sc' => 'Sardinian', 'ss' => 'Swati', 'su' => 'Sundanese', 'sw' => 'Swahili',
		'sv' => 'Swedish', 'ty' => 'Tahitian', 'ta' => 'Tamil', 'tt' => 'Tatar', 'te' => 'Telugu', 'tg' => 'Tajik', 'tl' => 'Tagalog', 'th' => 'Thai', 'bo' => 'Tibetan', 'ti' => 'Tigrinya', 'to' => 'Tonga (Tonga Islands)', 'tn' => 'Tswana', 'ts' => 'Tsonga', 'tk' => 'Turkmen', 'tr' => 'Turkish', 'tw' => 'Twi', 'ug' => 'Uighur; Uyghur', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'uz' => 'Uzbek',
		've' => 'Venda', 'vi' => 'Vietnamese', 'vo' => 'Volapük', 'cy' => 'Welsh','wa' => 'Walloon','wo' => 'Wolof', 'xh' => 'Xhosa', 'yi' => 'Yiddish', 'yo' => 'Yoruba', 'za' => 'Zhuang; Chuang', 'zu' => 'Zulu' );

	/**
	 * Filters the language codes.
	 *
	 * @since WP-MU (3.0.0)
	 *
	 * @param array  $lang_codes Key/value pair of language codes where key is the short version.
	 * @param string $code       A two-letter designation of the language.
	 */
	$lang_codes = apply_filters( 'lang_codes', $lang_codes, $code );
	return strtr( $code, $lang_codes );
}

/**
 * Synchronize category and post tag slugs when global terms are enabled.
 *
 * @since WP-3.0.0
 *
 * @param object $term     The term.
 * @param string $taxonomy The taxonomy for `$term`. Should be 'category' or 'post_tag', as these are
 *                         the only taxonomies which are processed by this function; anything else
 *                         will be returned untouched.
 * @return object|array Returns `$term`, after filtering the 'slug' field with sanitize_title()
 *                      if $taxonomy is 'category' or 'post_tag'.
 */
function sync_category_tag_slugs( $term, $taxonomy ) {
	if ( global_terms_enabled() && ( $taxonomy == 'category' || $taxonomy == 'post_tag' ) ) {
		if ( is_object( $term ) ) {
			$term->slug = sanitize_title( $term->name );
		} else {
			$term['slug'] = sanitize_title( $term['name'] );
		}
	}
	return $term;
}

/**
 * Displays an access denied message when a user tries to view a site's dashboard they
 * do not have access to.
 *
 * @since WP-3.2.0
 * @access private
 */
function _access_denied_splash() {
	if ( ! is_user_logged_in() || is_network_admin() )
		return;

	$blogs = get_blogs_of_user( get_current_user_id() );

	if ( wp_list_filter( $blogs, array( 'userblog_id' => get_current_blog_id() ) ) )
		return;

	$blog_name = get_bloginfo( 'name' );

	if ( empty( $blogs ) )
		wp_die( sprintf( __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ), $blog_name ), 403 );

	$output = '<p>' . sprintf( __( 'You attempted to access the "%1$s" dashboard, but you do not currently have privileges on this site. If you believe you should be able to access the "%1$s" dashboard, please contact your network administrator.' ), $blog_name ) . '</p>';
	$output .= '<p>' . __( 'If you reached this screen by accident and meant to visit one of your own sites, here are some shortcuts to help you find your way.' ) . '</p>';

	$output .= '<h3>' . __('Your Sites') . '</h3>';
	$output .= '<table>';

	foreach ( $blogs as $blog ) {
		$output .= '<tr>';
		$output .= "<td>{$blog->blogname}</td>";
		$output .= '<td><a href="' . esc_url( get_admin_url( $blog->userblog_id ) ) . '">' . __( 'Visit Dashboard' ) . '</a> | ' .
			'<a href="' . esc_url( get_home_url( $blog->userblog_id ) ). '">' . __( 'View Site' ) . '</a></td>';
		$output .= '</tr>';
	}

	$output .= '</table>';

	wp_die( $output, 403 );
}

/**
 * Checks if the current user has permissions to import new users.
 *
 * @since WP-3.0.0
 *
 * @param string $permission A permission to be checked. Currently not used.
 * @return bool True if the user has proper permissions, false if they do not.
 */
function check_import_new_users( $permission ) {
	if ( ! current_user_can( 'manage_network_users' ) ) {
		return false;
	}

	return true;
}
// See "import_allow_fetch_attachments" and "import_attachment_size_limit" filters too.

/**
 * Generates and displays a drop-down of available languages.
 *
 * @since WP-3.0.0
 *
 * @param array  $lang_files Optional. An array of the language files. Default empty array.
 * @param string $current    Optional. The current language code. Default empty.
 */
function mu_dropdown_languages( $lang_files = array(), $current = '' ) {
	$flag = false;
	$output = array();

	foreach ( (array) $lang_files as $val ) {
		$code_lang = basename( $val, '.mo' );

		if ( $code_lang == 'en_US' ) { // American English
			$flag = true;
			$ae = __( 'American English' );
			$output[$ae] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . $ae . '</option>';
		} elseif ( $code_lang == 'en_GB' ) { // British English
			$flag = true;
			$be = __( 'British English' );
			$output[$be] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . $be . '</option>';
		} else {
			$translated = format_code_lang( $code_lang );
			$output[$translated] = '<option value="' . esc_attr( $code_lang ) . '"' . selected( $current, $code_lang, false ) . '> ' . esc_html ( $translated ) . '</option>';
		}

	}

	if ( $flag === false ) // ClassicPress english
		$output[] = '<option value=""' . selected( $current, '', false ) . '>' . __( 'English' ) . "</option>";

	// Order by name
	uksort( $output, 'strnatcasecmp' );

	/**
	 * Filters the languages available in the dropdown.
	 *
	 * @since WP-MU (3.0.0)
	 *
	 * @param array $output     HTML output of the dropdown.
	 * @param array $lang_files Available language files.
	 * @param string $current   The current language code.
	 */
	$output = apply_filters( 'mu_dropdown_languages', $output, $lang_files, $current );

	echo implode( "\n\t", $output );
}

/**
 * Displays an admin notice to upgrade all sites after a core upgrade.
 *
 * @since WP-3.0.0
 *
 * @global int    $wp_db_version The version number of the database.
 * @global string $pagenow
 *
 * @return false False if the current user is not a super admin.
 */
function site_admin_notice() {
	global $wp_db_version, $pagenow;

	if ( ! current_user_can( 'upgrade_network' ) ) {
		return false;
	}

	if ( 'upgrade.php' == $pagenow ) {
		return;
	}

	if ( get_site_option( 'wpmu_upgrade_site' ) != $wp_db_version ) {
		echo "<div class='update-nag'>" . sprintf( __( 'Thank you for Updating! Please visit the <a href="%s">Upgrade Network</a> page to update all your sites.' ), esc_url( network_admin_url( 'upgrade.php' ) ) ) . "</div>";
	}
}

/**
 * Avoids a collision between a site slug and a permalink slug.
 *
 * In a subdirectory installation this will make sure that a site and a post do not use the
 * same subdirectory by checking for a site with the same name as a new post.
 *
 * @since WP-3.0.0
 *
 * @param array $data    An array of post data.
 * @param array $postarr An array of posts. Not currently used.
 * @return array The new array of post data after checking for collisions.
 */
function avoid_blog_page_permalink_collision( $data, $postarr ) {
	if ( is_subdomain_install() )
		return $data;
	if ( $data['post_type'] != 'page' )
		return $data;
	if ( !isset( $data['post_name'] ) || $data['post_name'] == '' )
		return $data;
	if ( !is_main_site() )
		return $data;

	$post_name = $data['post_name'];
	$c = 0;
	while( $c < 10 && get_id_from_blogname( $post_name ) ) {
		$post_name .= mt_rand( 1, 10 );
		$c ++;
	}
	if ( $post_name != $data['post_name'] ) {
		$data['post_name'] = $post_name;
	}
	return $data;
}

/**
 * Handles the display of choosing a user's primary site.
 *
 * This displays the user's primary site and allows the user to choose
 * which site is primary.
 *
 * @since WP-3.0.0
 */
function choose_primary_blog() {
	?>
	<table class="form-table">
	<tr>
	<?php /* translators: My sites label */ ?>
		<th scope="row"><label for="primary_blog"><?php _e( 'Primary Site' ); ?></label></th>
		<td>
		<?php
		$all_blogs = get_blogs_of_user( get_current_user_id() );
		$primary_blog = get_user_meta( get_current_user_id(), 'primary_blog', true );
		if ( count( $all_blogs ) > 1 ) {
			$found = false;
			?>
			<select name="primary_blog" id="primary_blog">
				<?php foreach ( (array) $all_blogs as $blog ) {
					if ( $primary_blog == $blog->userblog_id )
						$found = true;
					?><option value="<?php echo $blog->userblog_id ?>"<?php selected( $primary_blog, $blog->userblog_id ); ?>><?php echo esc_url( get_home_url( $blog->userblog_id ) ) ?></option><?php
				} ?>
			</select>
			<?php
			if ( !$found ) {
				$blog = reset( $all_blogs );
				update_user_meta( get_current_user_id(), 'primary_blog', $blog->userblog_id );
			}
		} elseif ( count( $all_blogs ) == 1 ) {
			$blog = reset( $all_blogs );
			echo esc_url( get_home_url( $blog->userblog_id ) );
			if ( $primary_blog != $blog->userblog_id ) // Set the primary blog again if it's out of sync with blog list.
				update_user_meta( get_current_user_id(), 'primary_blog', $blog->userblog_id );
		} else {
			echo "N/A";
		}
		?>
		</td>
	</tr>
	</table>
	<?php
}

/**
 * Whether or not we can edit this network from this page.
 *
 * By default editing of network is restricted to the Network Admin for that `$network_id`.
 * This function allows for this to be overridden.
 *
 * @since WP-3.1.0
 *
 * @param int $network_id The network ID to check.
 * @return bool True if network can be edited, otherwise false.
 */
function can_edit_network( $network_id ) {
	if ( $network_id == get_current_network_id() )
		$result = true;
	else
		$result = false;

	/**
	 * Filters whether this network can be edited from this page.
	 *
	 * @since WP-3.1.0
	 *
	 * @param bool $result     Whether the network can be edited from this page.
	 * @param int  $network_id The network ID to check.
	 */
	return apply_filters( 'can_edit_network', $result, $network_id );
}

/**
 * Thickbox image paths for Network Admin.
 *
 * @since WP-3.1.0
 *
 * @access private
 */
function _thickbox_path_admin_subfolder() {
?>
<script type="text/javascript">
var tb_pathToImage = "<?php echo esc_js( includes_url( 'js/thickbox/loadingAnimation.gif', 'relative' ) ); ?>";
</script>
<?php
}

/**
 *
 * @param array $users
 */
function confirm_delete_users( $users ) {
	$current_user = wp_get_current_user();
	if ( ! is_array( $users ) || empty( $users ) ) {
		return false;
	}
	?>
	<h1><?php esc_html_e( 'Users' ); ?></h1>

	<?php if ( 1 == count( $users ) ) : ?>
		<p><?php _e( 'You have chosen to delete the user from all networks and sites.' ); ?></p>
	<?php else : ?>
		<p><?php _e( 'You have chosen to delete the following users from all networks and sites.' ); ?></p>
	<?php endif; ?>

	<form action="users.php?action=dodelete" method="post">
	<input type="hidden" name="dodelete" />
	<?php
	wp_nonce_field( 'ms-users-delete' );
	$site_admins = get_super_admins();
	$admin_out = '<option value="' . esc_attr( $current_user->ID ) . '">' . $current_user->user_login . '</option>'; ?>
	<table class="form-table">
	<?php foreach ( ( $allusers = (array) $_POST['allusers'] ) as $user_id ) {
		if ( $user_id != '' && $user_id != '0' ) {
			$delete_user = get_userdata( $user_id );

			if ( ! current_user_can( 'delete_user', $delete_user->ID ) ) {
				wp_die( sprintf( __( 'Warning! User %s cannot be deleted.' ), $delete_user->user_login ) );
			}

			if ( in_array( $delete_user->user_login, $site_admins ) ) {
				wp_die( sprintf( __( 'Warning! User cannot be deleted. The user %s is a network administrator.' ), '<em>' . $delete_user->user_login . '</em>' ) );
			}
			?>
			<tr>
				<th scope="row"><?php echo $delete_user->user_login; ?>
					<?php echo '<input type="hidden" name="user[]" value="' . esc_attr( $user_id ) . '" />' . "\n"; ?>
				</th>
			<?php $blogs = get_blogs_of_user( $user_id, true );

			if ( ! empty( $blogs ) ) {
				?>
				<td><fieldset><p><legend><?php printf(
					/* translators: user login */
					__( 'What should be done with content owned by %s?' ),
					'<em>' . $delete_user->user_login . '</em>'
				); ?></legend></p>
				<?php
				foreach ( (array) $blogs as $key => $details ) {
					$blog_users = get_users( array( 'blog_id' => $details->userblog_id, 'fields' => array( 'ID', 'user_login' ) ) );
					if ( is_array( $blog_users ) && !empty( $blog_users ) ) {
						$user_site = "<a href='" . esc_url( get_home_url( $details->userblog_id ) ) . "'>{$details->blogname}</a>";
						$user_dropdown = '<label for="reassign_user" class="screen-reader-text">' . __( 'Select a user' ) . '</label>';
						$user_dropdown .= "<select name='blog[$user_id][$key]' id='reassign_user'>";
						$user_list = '';
						foreach ( $blog_users as $user ) {
							if ( ! in_array( $user->ID, $allusers ) ) {
								$user_list .= "<option value='{$user->ID}'>{$user->user_login}</option>";
							}
						}
						if ( '' == $user_list ) {
							$user_list = $admin_out;
						}
						$user_dropdown .= $user_list;
						$user_dropdown .= "</select>\n";
						?>
						<ul style="list-style:none;">
							<li><?php printf( __( 'Site: %s' ), $user_site ); ?></li>
							<li><label><input type="radio" id="delete_option0" name="delete[<?php echo $details->userblog_id . '][' . $delete_user->ID ?>]" value="delete" checked="checked" />
							<?php _e( 'Delete all content.' ); ?></label></li>
							<li><label><input type="radio" id="delete_option1" name="delete[<?php echo $details->userblog_id . '][' . $delete_user->ID ?>]" value="reassign" />
							<?php _e( 'Attribute all content to:' ); ?></label>
							<?php echo $user_dropdown; ?></li>
						</ul>
						<?php
					}
				}
				echo "</fieldset></td></tr>";
			} else {
				?>
				<td><fieldset><p><legend><?php _e( 'User has no sites or content and will be deleted.' ); ?></legend></p>
			<?php } ?>
			</tr>
		<?php
		}
	}

	?>
	</table>
	<?php
	/** This action is documented in wp-admin/users.php */
	do_action( 'delete_user_form', $current_user, $allusers );

	if ( 1 == count( $users ) ) : ?>
		<p><?php _e( 'Once you hit &#8220;Confirm Deletion&#8221;, the user will be permanently removed.' ); ?></p>
	<?php else : ?>
		<p><?php _e( 'Once you hit &#8220;Confirm Deletion&#8221;, these users will be permanently removed.' ); ?></p>
	<?php endif;

	submit_button( __('Confirm Deletion'), 'primary' );
	?>
	</form>
	<?php
	return true;
}

/**
 * Print JavaScript in the header on the Network Settings screen.
 *
 * @since WP-4.1.0
 */
function network_settings_add_js() {
?>
<script type="text/javascript">
jQuery(document).ready( function($) {
	var languageSelect = $( '#WPLANG' );
	$( 'form' ).submit( function() {
		// Don't show a spinner for English and installed languages,
		// as there is nothing to download.
		if ( ! languageSelect.find( 'option:selected' ).data( 'installed' ) ) {
			$( '#submit', this ).after( '<span class="spinner language-install-spinner is-active" />' );
		}
	});
});
</script>
<?php
}

/**
 * Outputs the HTML for a network's "Edit Site" tabular interface.
 *
 * @since WP-4.6.0
 *
 * @param $args {
 *     Optional. Array or string of Query parameters. Default empty array.
 *
 *     @type int    $blog_id  The site ID. Default is the current site.
 *     @type array  $links    The tabs to include with (label|url|cap) keys.
 *     @type string $selected The ID of the selected link.
 * }
 */
function network_edit_site_nav( $args = array() ) {

	/**
	 * Filters the links that appear on site-editing network pages.
	 *
	 * Default links: 'site-info', 'site-users', 'site-themes', and 'site-settings'.
	 *
	 * @since WP-4.6.0
	 *
	 * @param array $links {
	 *     An array of link data representing individual network admin pages.
	 *
	 *     @type array $link_slug {
	 *         An array of information about the individual link to a page.
	 *
	 *         $type string $label Label to use for the link.
	 *         $type string $url   URL, relative to `network_admin_url()` to use for the link.
	 *         $type string $cap   Capability required to see the link.
	 *     }
	 * }
	 */
	$links = apply_filters( 'network_edit_site_nav_links', array(
		'site-info'     => array( 'label' => __( 'Info' ),     'url' => 'site-info.php',     'cap' => 'manage_sites' ),
		'site-users'    => array( 'label' => __( 'Users' ),    'url' => 'site-users.php',    'cap' => 'manage_sites' ),
		'site-themes'   => array( 'label' => __( 'Themes' ),   'url' => 'site-themes.php',   'cap' => 'manage_sites' ),
		'site-settings' => array( 'label' => __( 'Settings' ), 'url' => 'site-settings.php', 'cap' => 'manage_sites' )
	) );

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'blog_id'  => isset( $_GET['blog_id'] ) ? (int) $_GET['blog_id'] : 0,
		'links'    => $links,
		'selected' => 'site-info',
	) );

	// Setup the links array
	$screen_links = array();

	// Loop through tabs
	foreach ( $r['links'] as $link_id => $link ) {

		// Skip link if user can't access
		if ( ! current_user_can( $link['cap'], $r['blog_id'] ) ) {
			continue;
		}

		// Link classes
		$classes = array( 'nav-tab' );

		// Selected is set by the parent OR assumed by the $pagenow global
		if ( $r['selected'] === $link_id || $link['url'] === $GLOBALS['pagenow'] ) {
			$classes[] = 'nav-tab-active';
		}

		// Escape each class
		$esc_classes = implode( ' ', $classes );

		// Get the URL for this link
		$url = add_query_arg( array( 'id' => $r['blog_id'] ), network_admin_url( $link['url'] ) );

		// Add link to nav links
		$screen_links[ $link_id ] = '<a href="' . esc_url( $url ) . '" id="' . esc_attr( $link_id ) . '" class="' . $esc_classes . '">' . esc_html( $link['label'] ) . '</a>';
	}

	// All done!
	echo '<h2 class="nav-tab-wrapper wp-clearfix">';
	echo implode( '', $screen_links );
	echo '</h2>';
}

/**
 * Returns the arguments for the help tab on the Edit Site screens.
 *
 * @since WP-4.9.0
 *
 * @return array Help tab arguments.
 */
function get_site_screen_help_tab_args() {
	return array(
		'id'      => 'overview',
		'title'   => __('Overview'),
		'content' =>
			'<p>' . __('The menu is for editing information specific to individual sites, particularly if the admin area of a site is unavailable.') . '</p>' .
			'<p>' . __('<strong>Info</strong> &mdash; The site URL is rarely edited as this can cause the site to not work properly. The Registered date and Last Updated date are displayed. Network admins can mark a site as archived, spam, deleted and mature, to remove from public listings or disable.') . '</p>' .
			'<p>' . __('<strong>Users</strong> &mdash; This displays the users associated with this site. You can also change their role, reset their password, or remove them from the site. Removing the user from the site does not remove the user from the network.') . '</p>' .
			'<p>' . sprintf( __('<strong>Themes</strong> &mdash; This area shows themes that are not already enabled across the network. Enabling a theme in this menu makes it accessible to this site. It does not activate the theme, but allows it to show in the site&#8217;s Appearance menu. To enable a theme for the entire network, see the <a href="%s">Network Themes</a> screen.' ), network_admin_url( 'themes.php' ) ) . '</p>' .
			'<p>' . __('<strong>Settings</strong> &mdash; This page shows a list of all settings associated with this site. Some are created by ClassicPress and others are created by plugins you activate. Note that some fields are grayed out and say Serialized Data. You cannot modify these values due to the way the setting is stored in the database.') . '</p>'
	);
}

/**
 * Returns the content for the help sidebar on the Edit Site screens.
 *
 * @since WP-4.9.0
 *
 * @return string Help sidebar content.
 */
function get_site_screen_help_sidebar_content() {
	return '<p><strong>' . __('For more information:') . '</strong></p>' .
		'<p>' . __('<a href="https://codex.wordpress.org/Network_Admin_Sites_Screen">Documentation on Site Management</a>') . '</p>' .
		'<p>' . __('<a href="https://docs.classicpress.net/faq-support/">Support</a>') . '</p>';
}
