<?php

/**
 * Retrieves and creates the wp-config.php file.
 *
 * The permissions for the base directory must allow for writing files in order
 * for the wp-config.php to be created using this page. Once the config file has
 * been created, the user is moved to install.php to complete the installation.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/**
 * We are installing.
 */
define('WP_INSTALLING', true);

/**
 * We are blissfully unaware of anything.
 */
define('WP_SETUP_CONFIG', true);

/**
 * Disable error reporting
 *
 * Set this to error_reporting( -1 ) for debugging
 */
error_reporting( 0 );

// Everything relies on ABSPATH; make sure it's defined.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( dirname( __FILE__ ) ) . '/' );
}

// WP settings.
require( ABSPATH . 'wp-settings.php' );

/** Load ClassicPress Administration Upgrade API */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/** Load ClassicPress Translation Installation API */
require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

// Prevent browser caching.
nocache_headers();

// Check for wp-config-sample.php file up to one level up from here.
$config_file = false;
if ( file_exists( ABSPATH . 'wp-config-sample.php' ) ) {
	$config_file = file( ABSPATH . 'wp-config-sample.php' );
} elseif ( file_exists( dirname( ABSPATH ) . '/wp-config-sample.php' ) ) {
	$config_file = file( dirname( ABSPATH ) . '/wp-config-sample.php' );
}

// No wp-config-sample.php found? Bail.
if ( ! $config_file ) {
	setup_config_display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Sample Config File Missing' ) . '</h1>';
	echo '<p>' . sprintf(
		/* translators: 1: wp-config-sample.php, 2: link to the contents of this file */
		__( 'A %s file was not found. Please upload it to the root of your ClassicPress installation, or one level higher, and then try again. Need a <a href="%s" target="_blank" rel="noopener">fresh copy</a>?' ),
		'<code>wp-config-sample.php</code>',
		esc_url( 'https://raw.githubusercontent.com/ClassicPress/ClassicPress-release/master/wp-config-sample.php' )
	) . '</p>';
	setup_config_display_footer();
}

// Does wp-config.php file already exist? Bail.
if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
	setup_config_display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Config File Found' ) . '</h1>';
	echo '<p>' . sprintf(
		/* translators: 1: wp-config.php, 2: link to install.php */
		__( 'A %1$s file was found in your ClassicPress installation. If you are trying to reinstall ClassicPress, you must first delete that file.</p><p> If you created and uploaded your own config file, you can <a href="%2$s">continue installing</a>.' ),
		'<code>wp-config.php</code>',
		'install.php'
	) . '</p>';
	setup_config_display_footer();
}

// Check if wp-config.php exists above the root directory but is not part of another installation
if ( @file_exists( ABSPATH . '../wp-config.php' ) && ! @file_exists( ABSPATH . '../wp-settings.php' ) ) {
	setup_config_display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Config File Found' ) . '</h1>';
	echo '<p>' . sprintf(
			/* translators: 1: wp-config.php 2: install.php */
			__( 'A %1$s file was found one level above your ClassicPress installation. If you are trying to reinstall ClassicPress, you must first delete that file.</p><p> If you created and uploaded your own config file, you can <a href="%2$s">continue installing</a>.' ),
			'<code>wp-config.php</code>',
			'install.php'
		) . '</p>';
	setup_config_display_footer();
}

// Determine which language pack to use.
$language = '';
if ( ! empty( $_REQUEST['language'] ) ) {
	$language = preg_replace( '/[^a-zA-Z0-9_]/', '', $_REQUEST['language'] );
} elseif ( isset( $GLOBALS['wp_local_package'] ) ) {
	$language = $GLOBALS['wp_local_package'];
}

// Get current step.
$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : -1;

// React to current step.
switch ( $step ) {

	// Just getting started? Display the language picker.
	case -1:
		if ( wp_can_install_language_pack() && empty( $language ) && ( $languages = wp_get_available_translations() ) ) {
			setup_config_display_header( 'language-chooser' );
			echo '<h1 class="screen-reader-text">Select a default language</h1>';
			echo '<form id="setup" method="post" action="?step=1">';
			wp_install_language_form( $languages );
			echo '</form>';
			break; // end switch ( $step ), case -1
		}

	// Notably, there is no longer a step 0 here.

	// Display the database setup screen.
	case 1:

		// Ensure language is loaded.
		if ( ! empty( $language ) ) {
			$loaded_language = wp_download_language_pack( $language );
			if ( $loaded_language ) {
				load_default_textdomain( $loaded_language );
				$GLOBALS['wp_locale'] = new WP_Locale();
			}
		}

		// Print the page header.
		setup_config_display_header();

		// Create a string depicting step 2.
		$step_2 = 'setup-config.php?step=2';
		$step_2 .= ( isset( $_REQUEST['noapi'] ) ) ? '&amp;noapi' : '';
		$step_2 .= ( ! empty( $loaded_language ) ) ? '&amp;language=' . $loaded_language : '';

		// Screen reader text; form open.
		echo '<h1 class="screen-reader-text">' . __( 'Database setup' ) . '</h1>';
		echo '<form method="post" action="setup-config.php?step=2">';

		// Title.
		echo '<h2>' . __( 'Database Setup' ) . '</h2>';

		// Description.
		echo '<p>' .  sprintf(
			/* translators: link to support forums for more help */
			__( 'To get started, fill in your database information. If you don&#8217;t have this information, it can be requested from your web host. Need more <a href="%s" target="_blank" rel="noopener">help</a>?' ),
			'https://forums.classicpress.net/c/support'
		) . '</p>';

		// Database settings inputs.
		echo '<table class="form-table">';
		echo '	<tr>';
		echo '		<th scope="row"><label for="dbname">' . __( 'Database Name' ) . '</label></th>';
		echo '		<td><input name="dbname" id="dbname" type="text" size="25" value="classicpress" /></td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<th scope="row"><label for="uname">' . __( 'Database Username' ) . '</label></th>';
		echo '		<td><input name="uname" id="uname" type="text" size="25" value="' . htmlspecialchars( _x( 'username', 'example username' ), ENT_QUOTES ) . '" /></td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<th scope="row"><label for="pwd">' . __( 'Database Password' ) . '</label></th>';
		echo '		<td><input name="pwd" id="pwd" type="text" size="25" value="1ejm127$69%" autocomplete="off" /></td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<th scope="row"><label for="dbhost">' . __( 'Database Host' ) . '</label></th>';
		echo '		<td><input name="dbhost" id="dbhost" type="text" size="25" value="localhost" /></td>';
		echo '	</tr>';
		echo '	<tr>';
		echo '		<th scope="row"><label for="prefix">' . __( 'Table Prefix' ) . '</label></th>';
		echo '		<td><input name="prefix" id="prefix" type="text" value="cp_" size="25" /> ' .
			sprintf(
				'<a href="%s" target="_blank" rel="noopener">' . __( 'Learn More' ) . '</a>',
				esc_url('https://docs.classicpress.net/installing-classicpress/#installation-steps')
			) .
			'</td>';
		echo '	</tr>';
		echo '</table>';

		// Allow disabling calls to the salts API.
		if ( isset( $_GET['noapi'] ) ) {
			echo '<input name="noapi" type="hidden" value="1" />';
		}

		// Set a hidden lang arg.
		echo '<input type="hidden" name="language" value="' . esc_attr( $language ) . '" />';
		// Add a submit button and close the form.
		echo '<p class="step"><input name="submit" type="submit" value="' . htmlspecialchars( __( 'Continue' ), ENT_QUOTES ) . '" class="button button-primary button-hero cp-button" /></p>';
		echo '</form>';

		break; // end case 1

	// Display the site setup (title/admin/SEO) screen.
	case 2:

		// Handle language.
		load_default_textdomain( $language );
		$GLOBALS['wp_locale'] = new WP_Locale();

		// Get and pare submitted data.
		$dbname = trim( wp_unslash( $_POST[ 'dbname' ] ) );
		$uname = trim( wp_unslash( $_POST[ 'uname' ] ) );
		$pwd = trim( wp_unslash( $_POST[ 'pwd' ] ) );
		$dbhost = trim( wp_unslash( $_POST[ 'dbhost' ] ) );
		$prefix = trim( wp_unslash( $_POST[ 'prefix' ] ) );

		// Base of setup URL.
		$step_1 = 'setup-config.php?step=1';

		// Append to step 1, as needed.
		if ( isset( $_REQUEST['noapi'] ) ) {
			$step_1 .= '&amp;noapi';
		}

		// Base of installation URL.
		$install = 'install.php';

		// Language check.
		if ( ! empty( $language ) ) {
			// Append language to setup URL and install URL.
			$step_1 .= '&amp;language=' . $language;
			$install .= '?language=' . $language;
		} else {
			// Only append to the install URL.
			$install .= '?language=en_US';
		}

		// A link to "go back" when an error occurs.
		$tryagain_link = '<a href="' . $step_1 . '" onclick="javascript:history.go(-1);return false;" class="button button-secondary">' . __( 'Try Again' ) . '</a>';

		// Is database prefix unacceptable? Bail with linkback.
		if ( empty( $prefix ) ) {
			setup_config_display_header( 'cp-installation-error' );
			echo '<h1>' . __( 'Missing Table Prefix' ) . '</h1>';
			echo '<p>' . __( 'The table prefix field cannot be empty.' ) . '</p>';
			echo '<p class="step">' . $tryagain_link . '</p>';
			setup_config_display_footer();
		}

		// Prefix is more than letters, numbers, underscores? Bail with linkback.
		if ( preg_match( '|[^a-z0-9_]|i', $prefix ) ) {
			setup_config_display_header( 'cp-installation-error' );
			echo '<h1>' . __( 'Invalid Table Prefix' ) . '</h1>';
			echo '<p>' . __( 'The table prefix field can only contain numbers, letters, and underscores.' ) . '</p>';
			echo '<p class="step">' . $tryagain_link . '</p>';
			setup_config_display_footer();
		}

		/**#@+
		 * @ignore
		 */
		define( 'DB_NAME', $dbname );
		define( 'DB_USER', $uname );
		define( 'DB_PASSWORD', $pwd );
		define( 'DB_HOST', $dbhost );
		/**#@-*/

		// Kill and resurrect the database object with passed values.
		unset( $wpdb );
		require_wp_db();

		/*
		 * The wpdb constructor bails when WP_SETUP_CONFIG is set, so we must
		 * fire this manually. We'll fail here if the values are no good.
		 */

		// Test the connection.
		$wpdb->db_connect();

		// Were there problems connecting to the database? Bail with linkback.
		if ( ! empty( $wpdb->error ) ) {
			setup_config_display_header( 'cp-installation-error' );
			echo $wpdb->error->get_error_message();
			echo '<p class="step">' . $tryagain_link . '</p>';
			setup_config_display_footer();
		}

		// Is the prefix a MySQL value (e.g. a number) by itself? Bail.
		$errors = $wpdb->hide_errors();
		$wpdb->query( "SELECT $prefix" );
		$wpdb->show_errors( $errors );
		if ( ! $wpdb->last_error ) {
			setup_config_display_header( 'cp-installation-error' );
			echo '<h1>' . __( 'Invalid Table Prefix' ) . '</h1>';
			echo '<p>' . __( 'Your table prefix seems to be invalid. Try a different prefix using only letters, numbers, and underscores.' ) . '</p>';
			echo '<p class="step">' . $tryagain_link . '</p>';
			setup_config_display_footer();
		}

		// Generate keys and salts using secure CSPRNG; fallback to API if enabled; further fallback to original wp_generate_password().
		try {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
			$max = strlen( $chars ) - 1;
			for ( $i = 0; $i < 8; $i++ ) {
				$key = '';
				for ( $j = 0; $j < 64; $j++ ) {
					$key .= substr( $chars, random_int( 0, $max ), 1 );
				}
				$secret_keys[] = $key;
			}
		} catch ( Exception $ex ) {
			$no_api = isset( $_POST['noapi'] );

			if ( ! $no_api ) {
				$secret_keys = wp_remote_get( 'https://api.classicpress.net/secret-key/1.0/salt/' );
			}

			if ( $no_api || is_wp_error( $secret_keys ) ) {
				$secret_keys = array();
				for ( $i = 0; $i < 8; $i++ ) {
					$secret_keys[] = wp_generate_password( 64, true, true );
				}
			} else {
				$secret_keys = explode( "\n", wp_remote_retrieve_body( $secret_keys ) );
				array_shift( $secret_keys ); // the first line just contains "<pre>"
				foreach ( $secret_keys as $k => $v ) {
					$secret_keys[ $k ] = substr( $v, 29, 64 );
				}
			}
		}

		$key = 0;
		foreach ( $config_file as $line_num => $line ) {
			if ( '$table_prefix  =' == substr( $line, 0, 16 ) ) {
				$config_file[ $line_num ] = '$table_prefix  = \'' . addcslashes( $prefix, "\\'" ) . "';\r\n";
				continue;
			}

			if ( ! preg_match( '/^define\(\'([A-Z_]+)\',([ ]+)/', $line, $match ) ) {
				continue;
			}

			$constant = $match[1];
			$padding  = $match[2];

			switch ( $constant ) {
				case 'DB_NAME'     :
				case 'DB_USER'     :
				case 'DB_PASSWORD' :
				case 'DB_HOST'     :
					$config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'" . addcslashes( constant( $constant ), "\\'" ) . "');\r\n";
					break;
				case 'DB_CHARSET'  :
					if ( 'utf8mb4' === $wpdb->charset || ( ! $wpdb->charset && $wpdb->has_cap( 'utf8mb4' ) ) ) {
						$config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'utf8mb4');\r\n";
					}
					break;
				case 'AUTH_KEY'         :
				case 'SECURE_AUTH_KEY'  :
				case 'LOGGED_IN_KEY'    :
				case 'NONCE_KEY'        :
				case 'AUTH_SALT'        :
				case 'SECURE_AUTH_SALT' :
				case 'LOGGED_IN_SALT'   :
				case 'NONCE_SALT'       :
					$config_file[ $line_num ] = "define('" . $constant . "'," . $padding . "'" . $secret_keys[ $key++ ] . "');\r\n";
					break;
			}
		}
		unset( $line );

		// Is config file writable? No?!?!
		if ( ! is_writable( ABSPATH ) ) {
			// Page head.
			setup_config_display_header( 'cp-installation-error' );
			// Page heading.
			echo '<h1>' . __( 'Config File Permissions Insufficient' ) . '</h1>';
			// Error description.
			/* translators: %s: wp-config.php */
			echo '<p>' . sprintf( __( 'The %s file is not writable.' ), '<code>wp-config.php</code>' ) . '</p>';
			// Error solution.
			/* translators: %s: wp-config.php */
			echo '<p>' . sprintf( __( 'You can create the %s file manually and paste the following text into it.' ), '<code>wp-config.php</code>' ) . '</p>';
			// Text version of config file for manual copy paste. Populated.
			echo '<textarea id="wp-config" cols="98" rows="15" class="code" readonly="readonly">';
			foreach ( $config_file as $line ) {
				echo htmlentities( $line, ENT_COMPAT, 'UTF-8' );
			}
			echo '</textarea>';
			// Closing note.
			echo '<p>' . __( 'After you&#8217;ve done that, click &#8220;Continue&#8221;.' ) . '</p>';
			// Link to continue.
			echo '<p class="step"><a href="' . $install . '" class="button button-primary button-hero cp-button">' . __( 'Continue' ) . '</a></p>';
			// Add footer scripts only relevant to this situation.
			echo '<script>(function(){ if ( ! /iPad|iPod|iPhone/.test( navigator.userAgent ) ) { var el = document.getElementById("wp-config"); el.focus(); el.select(); } })();</script>';
			// Close body/html tags; die.
			setup_config_display_footer();

		} // end ( ! is_writable( ABSPATH ) )

		/*
		 * If this file doesn't exist, then we are using the wp-config-sample.php
		 * file one level up, which is for the develop repo.
		 */

		// Get path to config file.
		if ( file_exists( ABSPATH . 'wp-config-sample.php' ) ) {
			$path_to_wp_config = ABSPATH . 'wp-config.php';
		} else {
			$path_to_wp_config = dirname( ABSPATH ) . '/wp-config.php';
		}

		// Write to the config file.
		$handle = fopen( $path_to_wp_config, 'w' );
		foreach ( $config_file as $line ) {
			fwrite( $handle, $line );
		}
		fclose( $handle );

		// Set file permissions.
		chmod( $path_to_wp_config, 0666 );

		// Redirect to success screen.
		wp_redirect( $install );

		// Kill the script. With fire.
		exit;

		// For completeness; end switch ( $step ) case 2
		break;

} // end switch ( $step )

// Print language chooser script.
wp_print_scripts( 'language-chooser' );

// Close the body/html tags; die.
setup_config_display_footer();

/**
 * Display setup wp-config.php file header.
 *
 * @ignore
 * @since WP-2.3.0
 *
 * @global string    $wp_local_package
 * @global WP_Locale $wp_locale
 *
 * @param string|array $body_classes
 */
function setup_config_display_header( $body_classes = array() ) {
	// Make sure we're working with an array.
	$body_classes = (array) $body_classes;
	// Add core ui class.
	$body_classes[] = 'cp-installation';
	$body_classes[] = 'wp-core-ui';
	// Add rtl class, if needed.
	if ( is_rtl() ) {
		$body_classes[] = 'rtl';
	}
	// Set the content type.
	header( 'Content-Type: text/html; charset=utf-8' );
	// Print out the page header.
	echo '<!DOCTYPE html>' . "\n";
	echo '<html xmlns="http://www.w3.org/1999/xhtml" ' . get_language_attributes() . '>' . "\n";
	echo '<head>' . "\n";
	echo '<meta name="viewport" content="width=device-width" />' . "\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n";
	echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	echo '<title>' . __( 'ClassicPress &rsaquo; Setup Configuration File' ) . '</title>' . "\n";
	wp_admin_css( 'install', true );
	echo '</head>' . "\n";
	echo '<body class="' . implode( ' ', $body_classes ) . '">' . "\n";
	// Add the ClassicPress logo.
	echo '<p id="logo"><a href="' . esc_url( 'https://www.classicpress.net/' ) . '" tabindex="-1">' . __( 'ClassicPress' ) . '</a></p>' . "\n";
}

/**
 * Close body/html tags; end script execution.
 */
function setup_config_display_footer() {
	echo "\n" . '</body>' . "\n";
	echo '</html>';
	die();
}
