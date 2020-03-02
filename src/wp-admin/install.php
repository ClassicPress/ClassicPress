<?php
/**
 * ClassicPress Installer
 *
 *
 *
 * @package ClassicPress
 * @subpackage Administration
 */

// Sanity check. Is there a saner way to do this?
if ( false ) {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Error: PHP is not running</title>
</head>
<body class="cp-installation wp-core-ui">
	<p id="logo"><a href="https://www.classicpress.net/">ClassicPress</a></p>
	<h1>Error: PHP is not running</h1>
	<p>ClassicPress requires that your web server is running PHP. Your server does not have PHP installed, or PHP is turned off.</p>
</body>
</html>
<?php
}

/**
 * We are installing ClassicPress.
 *
 * @since WP-1.5.1
 * @var bool
 */
define( 'WP_INSTALLING', true );

/** Load ClassicPress Bootstrap */
require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

/** Load ClassicPress Administration Upgrade API */
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

/** Load ClassicPress Translation Install API */
require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

/** Load wpdb */
require_once( ABSPATH . WPINC . '/wp-db.php' );

// Prevent browser caching.
nocache_headers();

// Is ClassicPress already installed? If so, offer login link, bail.
if ( is_blog_installed() ) {
	display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'ClassicPress Already Installed' ) . '</h1>';
	echo '<p>' . __( 'It seems that ClassicPress is already installed. If you are trying to reinstall ClassicPress, you must first delete the database tables associated with the previous installation.' ) . '</p>';
	echo '<p class="step"><a href="' . esc_url( wp_login_url() ) . '" class="button button-secondary">' . __( 'Log In' ) . '</a></p>';
	display_footer();
}

/**
 * @global string $cp_version
 * @global string $required_php_version
 * @global string $required_mysql_version
 * @global wpdb   $wpdb
 */
global $cp_version, $required_php_version, $required_mysql_version;

// Get and check for sufficient versions of PHP and MySQL.
$php_version   = phpversion();
$mysql_version = $wpdb->db_version();
$php_compat    = version_compare( $php_version, $required_php_version, '>=' );
$mysql_compat  = version_compare( $mysql_version, $required_mysql_version, '>=' ) || file_exists( WP_CONTENT_DIR . '/db.php' );

// Generate the CP download URL.
$cp_download_url = 'https://github.com/ClassicPress/ClassicPress-release';
if ( ! strstr( $cp_version, '+' ) ) {
	$cp_download_url .= '/releases/' . $cp_version;
}

// Insufficient PHP and/or MySQL? Set a flag with an error message.
if ( ! $mysql_compat && ! $php_compat ) {
	/* translators: 1: ClassicPress version number, 2: Minimum required PHP version number, 3: Minimum required MySQL version number, 4: Current PHP version number, 5: Current MySQL version number */
	$compat = sprintf(
		__( '<a href="' . $cp_download_url . '">ClassicPress %1$s</a> requires PHP version %2$s or higher and MySQL version %3$s or higher. You are running PHP version %4$s and MySQL version %5$s.' ),
		$cp_version,
		$required_php_version,
		$requires_mysql_version,
		$php_version,
		$mysql_version
	);
} elseif ( ! $php_compat ) {
	/* translators: 1: ClassicPress version number, 2: Minimum required PHP version number, 3: Current PHP version number */
	$compat = sprintf(
		__( '<a href="' . $cp_download_url . '">ClassicPress %1$s</a> requires PHP version %2$s or higher. You are running version %3$s.' ),
		$cp_version,
		$required_php_version,
		$php_version
	);
} elseif ( ! $mysql_compat ) {
	/* translators: 1: ClassicPress version number, 2: Minimum required MySQL version number, 3: Current MySQL version number */
	$compat = sprintf(
		__( '<a href="' . $cp_download_url . '">ClassicPress %1$s</a> requires MySQL version %2$s or higher. You are running version %3$s.' ),
		$cp_version,
		$required_mysql_version,
		$mysql_version
	);
}

// Flag set for insufficient PHP and/or MySQL? Notify user, bail.
if ( ! $mysql_compat || ! $php_compat ) {
	display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Insufficient Requirements' ) . '</h1>';
	echo '<p>' . $compat . '</p>';
	display_footer();
}

// Is the table prefix empty? If so, bail.
if ( ! is_string( $wpdb->base_prefix ) || '' === $wpdb->base_prefix ) {
	display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Configuration Error' ) . '</h1>';
	echo '<p>' . sprintf(
		/* translators: %s: wp-config.php */
		__( 'Your %s file has an empty database table prefix, which is not supported.' ),
		'<code>wp-config.php</code>'
		) . '</p>';
	display_footer();
}

// Set error message if DO_NOT_UPGRADE_GLOBAL_TABLES isn't set as it will break install.
if ( defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ) {
	display_header( 'cp-installation-error' );
	echo '<h1>' . __( 'Configuration Error' ) . '</h1>';
	echo '<p>' . sprintf(
		/* translators: %s: DO_NOT_UPGRADE_GLOBAL_TABLES */
		__( 'The constant %s cannot be defined when installing ClassicPress.' ),
		'<code>DO_NOT_UPGRADE_GLOBAL_TABLES</code>'
		) . '</p>';
	display_footer();
}

/**
 * @global string    $wp_local_package
 * @global WP_Locale $wp_locale
 */
$language = '';
if ( ! empty( $_REQUEST['language'] ) ) {
	$language = preg_replace( '/[^a-zA-Z0-9_]/', '', $_REQUEST['language'] );
} elseif ( isset( $GLOBALS['wp_local_package'] ) ) {
	$language = $GLOBALS['wp_local_package'];
}

// Initialize an array of scripts to print.
$scripts_to_print = array( 'jquery' );

// Determine the current installation step.
$step = isset( $_GET['step'] ) ? (int) $_GET['step'] : 0;

// React to the current step.
switch($step) {

	// Just getting started? Display the language picker.
	case 0:
		if ( wp_can_install_language_pack() && empty( $language ) && ( $languages = wp_get_available_translations() ) ) {
			$scripts_to_print[] = 'language-chooser';
			display_header( 'language-chooser' );
			echo '<form id="setup" method="post" action="?step=1">';
			wp_install_language_form( $languages );
			echo '</form>';
			break;
		}

	// Display the final setup screen (site title, admin user).
	case 1:
		// If language is present, ensure it's loaded.
		if ( ! empty( $language ) ) {
			$loaded_language = wp_download_language_pack( $language );
			if ( $loaded_language ) {
				load_default_textdomain( $loaded_language );
				$GLOBALS['wp_locale'] = new WP_Locale();
			}
		}
		// Add another script... Is this used?
		$scripts_to_print[] = 'user-profile';
		// Display the page header.
		display_header();
		// Add a main title.
		echo '<h1>' . __( 'Admin Setup' ) .'</h1>';
		// Add descriptive text.
		echo '<p>' . __( 'Give your site a great title and fill out your administrator account details. Take note of your username and password – you will need these again in a moment.' ) . '</p>';
		// Print the setup form.
		display_setup_form();
		// I rest my case.
		break;

	// Process the final setup step and redirect.
	case 2:
		// Ensure a language is loaded.
		if ( ! empty( $language ) && load_default_textdomain( $language ) ) {
			$loaded_language = $language;
			$GLOBALS['wp_locale'] = new WP_Locale();
		} else {
			$loaded_language = 'en_US';
		}
		// Was there a database error? If so, bail with error message.
		if ( ! empty( $wpdb->error ) ) {
			display_header( 'cp-installation-error' );
			echo '<h1>' . __( 'Configuration Error' ) . '</h1>';
			echo '<p>' . $wpdb->error->get_error_message() . '</p>';
			display_footer();
		}
		// Add another script to the mix... Is this used?
		$scripts_to_print[] = 'user-profile';
		// Print the branded page header.
		display_header();
		// Get and clean submitted data.
		$weblog_title = isset( $_POST['weblog_title'] ) ? trim( wp_unslash( $_POST['weblog_title'] ) ) : '';
		$user_name = isset($_POST['user_name']) ? trim( wp_unslash( $_POST['user_name'] ) ) : '';
		$admin_password = isset($_POST['admin_password']) ? wp_unslash( $_POST['admin_password'] ) : '';
		$admin_password_check = isset($_POST['admin_password2']) ? wp_unslash( $_POST['admin_password2'] ) : '';
		$admin_email  = isset( $_POST['admin_email'] ) ? trim( wp_unslash( $_POST['admin_email'] ) ) : '';
		$public       = isset( $_POST['blog_public'] ) ? (int) $_POST['blog_public'] : 1;
		// Initialize an error flag.
		$error = false;
		// Validate input; toggle error flag if any problems validating.
		if ( empty( $user_name ) ) {
			$error = __( 'Please provide a valid username.' );
		} elseif ( $user_name !== sanitize_user( $user_name, true ) ) {
			$error = __( 'The username you provided has invalid characters.' );
		} elseif ( $admin_password !== $admin_password_check ) {
			$error = __( 'Your passwords do not match. Please try again.' );
		} elseif ( empty( $admin_email ) ) {
			$error = __( 'You must provide an email address.' );
		} elseif ( ! is_email( $admin_email ) ) {
			$error = __( 'Sorry, that isn&#8217;t a valid email address. Email addresses look like <code>username@example.com</code>.' );
		}
		if ( $error ) {
			display_setup_form( $error );
		}
		/**
		 * No validation errors? Great. Inform user of success; show username;
		 * include password note; link to login page.
		 */
		if ( $error === false ) {
			$wpdb->show_errors();
			$result = wp_install( $weblog_title, $user_name, $admin_email, $public, '', wp_slash( $admin_password ), $loaded_language );
			echo '<h1>' . __( 'Installation Complete' ) . '</h1>' . "\n";
			echo '<p>' . __( 'Your ClassicPress site is ready to go!' ) . '</p>' . "\n";
			echo '<table class="form-table install-success">' . "\n";
			echo '<tr>' . "\n";
			echo '<th>' . __( 'Username' ) . '</th>' . "\n";
			echo '<td><code>' . esc_html( $user_name ) . '</code></td>' . "\n";
			echo '</tr>' . "\n";
			echo '<tr>' . "\n";
			echo '<th>' . __( 'Password' ) . '</th>' . "\n";
			echo '<td>';
			if ( ! empty( $result['password'] ) && empty( $admin_password_check ) ) {
				echo '<p><code>' . esc_html( $result['password'] ) . '</code></p>';
			}
			echo '<p>' . $result['password_message'] . '</p>' . "\n";
			echo '</td>' . "\n";
			echo '</tr>' . "\n";
			echo '</table>' . "\n";

			echo '<p class="step"><a href="' . esc_url( wp_login_url() ) . '" class="button button-primary button-hero cp-button">' . __( 'Log In' ) . '</a></p>' . "\n";
		}
		break; // switch( $step ) end case 2

} // switch( $step )

// Add footer scripts; close body/html tags; die.
display_footer( $scripts_to_print );

/**
 * Display ClassicPress-branded installation header.
 *
 * @since WP-2.5.0
 *
 * @param string|array $body_classes
 */
function display_header( $body_classes = array() ) {

	// Make sure we're working with an array.
	$body_classes = (array) $body_classes;
	// Add core ui and cp installation classes.
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
	echo '<html xmlns="http://www.w3.org/1999/xhtml"' . get_language_attributes() . '>' . "\n";
	echo '<head>' . "\n";
	echo '<meta name="viewport" content="width=device-width" />' . "\n";
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . "\n";
	echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
	echo '<title>' . __( 'ClassicPress &rsaquo; Installation' ) . '</title>' . "\n";
	wp_admin_css( 'install', true );
	wp_admin_css( 'dashicons', true ); // For?
	echo '</head>' . "\n";
	echo '<body class="' . implode( ' ', $body_classes ) . '">' . "\n";

	// Add the linked ClassicPress logo.
	echo '<p id="logo"><a href="' . esc_url( 'https://www.classicpress.net/' ) . '" tabindex="-1">' . __( 'ClassicPress' ) . '</a></p>' . "\n";
}

/**
 * Display installer setup form.
 *
 * @since WP-2.8.0
 *
 * @global wpdb $wpdb ClassicPress database abstraction object.
 *
 * @param string|null $error
 */
function display_setup_form( $error = null ) {

	// Check for users table.
	global $wpdb;
	$sql = $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $wpdb->users ) );
	$user_table = ( $wpdb->get_var( $sql ) != null );

	// Ensure that sites appear in search engines by default.
	$blog_public = 1;

	// Get and parse submitted data.
	if ( isset( $_POST['weblog_title'] ) ) {
		$blog_public = isset( $_POST['blog_public'] ) ? 1 : 0;
	}
	$weblog_title = isset( $_POST['weblog_title'] ) ? trim( wp_unslash( $_POST['weblog_title'] ) ) : '';
	$user_name = isset($_POST['user_name']) ? trim( wp_unslash( $_POST['user_name'] ) ) : '';
	$admin_email  = isset( $_POST['admin_email']  ) ? trim( wp_unslash( $_POST['admin_email'] ) ) : '';

	// Print input error, if any.
	if ( ! is_null( $error ) ) {
		echo '<h1>' . __( 'Admin Setup' ) . '</h1>' . "\n";
		echo '<p class="message">' . $error . '</p>' . "\n";
	}

	// Form for site title and admin user setup.
	echo '<form id="setup" method="post" action="install.php?step=2" novalidate="novalidate">' . "\n";
	echo '<table class="form-table">' . "\n";
	// Site title.
	echo '	<tr>' . "\n";
	echo '		<th scope="row"><label for="weblog_title">' . __( 'Site Title' ) . '</label></th>' . "\n";
	echo '		<td><input name="weblog_title" type="text" id="weblog_title" size="25" value="' . esc_attr( $weblog_title ) . '" /></td>' . "\n";
	echo '	</tr>' . "\n";
	// Admin username.
	echo '	<tr>' . "\n";
	echo '		<th scope="row"><label for="user_login">' . __( 'Admin Username' ) . '</label></th>' . "\n";
	echo '		<td>';
	// Desired user already exists?
	if ( $user_table ) {
		_e( 'User(s) already exists.' );
		echo '<input name="user_name" type="hidden" value="admin" />';
	} else { // ...no?
		echo '<input name="user_name" type="text" id="user_login" size="25" value="' . esc_attr( sanitize_user( $user_name, true ) ) . '" />';
		echo '<p class="description" style="max-width:300px;">' . __( 'Letters, numbers, spaces, underscores, hyphens, periods, and the @ symbol only. Usernames cannot be changed.' ) . '</p>';
	}
	echo '</td>' . "\n";
	echo '	</tr>' . "\n";
	// Non-existing user? Great. Add the admin password fields.
	if ( ! $user_table ) {
		// Create a random password to suggest.
		$initial_password = isset( $_POST['admin_password'] ) ? stripslashes( $_POST['admin_password'] ) : wp_generate_password( 18 );
		// Admin password.
		echo '	<tr class="form-field form-required user-pass1-wrap">' . "\n";
		echo '		<th scope="row">' . "\n";
		echo '			<label for="pass1">' . __( 'Admin Password' ) . '</label>' . "\n";
		echo '		</th>' . "\n";
		echo '		<td>' . "\n";
		echo '			<div>' . "\n";
		echo '				<input type="password" name="admin_password" id="pass1" class="regular-text" autocomplete="off" data-reveal="1" data-pw="' . esc_attr( $initial_password ) . '" aria-describedby="pass-strength-result" />' . "\n";
		echo '				<button type="button" class="button wp-hide-pw hide-if-no-js" data-start-masked="' . (int) isset( $_POST['admin_password'] ) . '" data-toggle="0" aria-label="' . esc_attr( 'Hide password' ) . '">' . "\n";
		echo '					<span class="dashicons dashicons-hidden"></span>' . "\n";
		echo '					<span class="text">' . __( 'Hide' ) . '</span>' . "\n";
		echo '				</button>' . "\n";
		echo '				<div id="pass-strength-result" aria-live="polite"></div>' . "\n";
		echo '			</div>' . "\n";
		echo '		</td>' . "\n";
		echo '	</tr>' . "\n";
		// Repeat password.
		echo '	<tr class="form-field form-required user-pass2-wrap hide-if-js">' . "\n";
		echo '		<th scope="row">' . "\n";
		echo '			<label for="pass2">' . __( 'Repeat Password' );
		echo '				<span class="description">' . __( '(required)' ) . '</span>' . "\n";
		echo '			</label>' . "\n";
		echo '		</th>' . "\n";
		echo '		<td>' . "\n";
		echo '			<input name="admin_password2" type="password" id="pass2" autocomplete="off" />' . "\n";
		echo '		</td>' . "\n";
		echo '	</tr>' . "\n";
		// Weak password confirmation.
		echo '	<tr class="pw-weak">' . "\n";
		echo '		<th scope="row">' . __( 'Confirm Password' ) . '</th>' . "\n";
		echo '		<td>' . "\n";
		echo '			<label>' . "\n";
		echo '			<input type="checkbox" name="pw_weak" class="pw-checkbox" />' . __( 'Confirm use of weak password' );
		echo '			</label>' . "\n";
		echo '		</td>' . "\n";
		echo '	</tr>' . "\n";
	} // end ( ! $user_table )
	// Admin email.
	echo '	<tr>' . "\n";
	echo '		<th scope="row"><label for="admin_email">' . __( 'Admin Email' ) . '</label></th>' . "\n";
	echo '		<td><input name="admin_email" type="email" id="admin_email" size="25" value="' . esc_attr( $admin_email ) . '" />' . "\n";
	echo '			<p class="description">' . __( 'Double-check your email address.' ) . '</p></td>' . "\n";
	echo '	</tr>' . "\n";
	// Search engine privacy.
	echo '	<tr>' . "\n";
	echo '		<th scope="row"><label for="blog_public">' . ( has_action( 'blog_privacy_selector' ) ? __( 'Site Visibility' ) : __( 'Search Engine Visibility' ) ) . '</label></th>' . "\n";
	echo '		<td>' . "\n";
	echo '			<fieldset>' . "\n";
	echo '				<legend class="screen-reader-text"><span>' . ( has_action( 'blog_privacy_selector' ) ? __( 'Site Visibility' ) : __( 'Search Engine Visibility' ) ) . '</span></legend>' . "\n";
	if ( has_action( 'blog_privacy_selector' ) ) {
		echo '				<input id="blog_public" type="radio" name="blog_public" value="1" ' . checked( 1, $blog_public, false ) . ' />' . "\n";
		echo '				<label for="blog_public">' . __( 'Allow search engines to index this site' ) . '</label><br/>' . "\n";
		echo '				<input id="blog-norobots" type="radio" name="blog_public" value="0" ' . checked( 0, $blog_public, false ) . ' />' . "\n";
		echo '				<label for="blog-norobots">' . __( 'Discourage search engines from indexing this site' ) . '</label>' . "\n";
		echo '				<p class="description">' . __( 'Note: Neither of these options blocks access to your site &mdash; it is up to search engines to honor your request.' ) . '</p>' . "\n";
		/** This action is documented in wp-admin/options-reading.php */
		do_action( 'blog_privacy_selector' );
	} else {
		echo '				<input name="blog_public" type="checkbox" id="blog_public" value="0" ' . checked( 0, $blog_public, false ) . ' /> ';
		echo '<label for="blog_public">' . __( 'Discourage search engines from indexing this site' ) . '</label>' . "\n";
	}
	echo '			</fieldset>' . "\n";
	echo '		</td>' . "\n";
	echo '	</tr>' . "\n";
	// Close the table; add a submit button; add hidden lang arg; close form.
	echo '</table>' . "\n";
	echo '<p class="step"><input name="submit" id="submit" type="submit" value="' . htmlspecialchars( __( 'Install ClassicPress' ), ENT_QUOTES ) . '" class="button button-primary button-hero cp-button" /></p>' . "\n";
	echo '<input type="hidden" name="language" value="' . ( isset( $_REQUEST['language'] ) ? esc_attr( $_REQUEST['language'] ) : '' ) . '" />' . "\n";
	echo '</form>' . "\n";
}

/**
 * Add footer scripts; close body/html tags; kill script with fire.
 */
function display_footer( $scripts_to_print = array( ) ) {
	if ( ! wp_is_mobile() ) {
		echo '<script type="text/javascript">var t = document.getElementById("weblog_title"); if (t){ t.focus(); }</script>' . "\n";
	}
	wp_print_scripts( (array) $scripts_to_print );
	echo '<script type="text/javascript">jQuery( function( $ ) { $( ".hide-if-no-js" ).removeClass( "hide-if-no-js" ); } ); </script>' . "\n";
	echo "\n" . '</body>' . "\n";
	echo '</html>';
	die();
}
