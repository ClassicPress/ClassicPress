<?php
/**
 * The base configuration for ClassicPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package ClassicPress
 */

/*----------------------------------------------------*
 * BEGIN-CONFIG - Add your configuration changes BELOW
 * ---------------------------------------------------*/
/** MySQL settings - You can get this info from your web host **/
/** The name of the database, user and password for ClassicPress **/

define('DB_NAME', 'database_name_here');
define('DB_USER', 'username_here');
define('DB_PASSWORD', 'password_here');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.classicpress.net/secret-key/1.0/salt/ ClassicPress.net secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since WP-2.6.0
 */
define('AUTH_KEY',         'put your unique phrase here');
define('SECURE_AUTH_KEY',  'put your unique phrase here');
define('LOGGED_IN_KEY',    'put your unique phrase here');
define('NONCE_KEY',        'put your unique phrase here');
define('AUTH_SALT',        'put your unique phrase here');
define('SECURE_AUTH_SALT', 'put your unique phrase here');
define('LOGGED_IN_SALT',   'put your unique phrase here');
define('NONCE_SALT',       'put your unique phrase here');

/**#@-*/

// Add/uncomment any OVERRIDES here for the default config
// settings found below "END-CONFIG" as well as any additions
// your ClassicPress installation needs.
//
// Here is example syntax for overrides/additions you might
// use (after removing the leading // to uncomment them and
// changing their settings to your specific needs, of course.
//
// ** Define location of database server, if not localhost
// define( 'DB_HOST', 'mysql.your-webhost.com' );
//
// ** Define the charset and collation used in the database
// ** for this ClassicPress installation
// define( 'DB_CHARSET', 'utf8mb4' );
// define( 'DB_COLLATE', 'utf8mb4_unicode_ci' );
//
// ** Define the prefix for all database table names
// ** for this ClassicPress installation
// $table_prefix = 'abc_'
//
// ** Define where WordPress core files are located
// ** relative to the web root.
// define( 'CP_CORE_PATH', '/core' );
//
// ** Define where site-specific files such as plugins
// ** and themes are located, relative to the web root.
// define( 'CP_CONTENT_PATH', '/app' );
//
// ** Show errors in browser that would otherwise be suppressed.
// define( 'WP_DEBUG', true );
//
// ** Shortcut for @ini_set( 'log_errors', 1 ) and
// ** ini_set( 'error_log', __DIR__.CP_CONTENT_PATH.'/debug.log' );
// define( 'WP_DEBUG_LOG', true );
//
// ** Shortcut for @ini_set( 'display_errors', 1 );
// define( 'WP_DEBUG_DISPLAY', true );
//
// ** Serve non-minified version of ClassicPress' Javascript files and
// ** any Javascript files from plugins and themes that respect this
// ** settting in order to simplify front-end debugging.
// define( 'SCRIPT_DEBUG', true );
//
// ** Specify if WordPress should concatonate Javascript into fewer
// ** <link> references to improve performance.
// define( 'CONCATENATE_SCRIPTS', true );
//
// ** Log PHP errors, Display PHP errors to browser, and write them to a
// ** text file in a subdirectory in the content directory, respectively.
// @ini_set( 'log_errors', 'On' );
// @ini_set( 'display_errors', 'On' );
// @ini_set( 'error_log', __DIR__ . CP_CONTENT_PATH . '/logs/php_error.log' );
//
// ** Set website root URL and URL for ClassicPress core files, respectively. **
// define( 'WP_HOME', https://www.example.com' );
// define( 'WP_SITEURL', 'https://www.example.com' . CP_CORE_PATH );
//
// ** Set an alternate location for your plugins. **
// define( 'WP_PLUGIN_DIR', __DIR__ . '/src/cp-plugins' );
// define( 'WP_PLUGIN_URL', WP_HOME . '/cp-plugins' );
//
// ** Set an alternate location for your uploaded media. **
// define( 'UPLOADS', '../cp-media' );
//
// ** Set an alternate location for your uploaded media. **
// define( 'AUTOSAVE_INTERVAL', 600 ); // 600 seconds/10 minutes
// define( 'WP_POST_REVISIONS', 10 );
//
// ** Increase front-end memory and admin memory, respectively. **
// define( 'WP_MEMORY_LIMIT', '128M' );
// define( 'WP_MAX_MEMORY_LIMIT', '256M' );
//
// ** Use CP_CONTENT_PATH . '/advanced-cache.php' **
// define( 'WP_CACHE', true );
//
// ** Useful when multiple WordPress sites with different $table_prefix
// ** want to share the same user table, e.g. for multi-tenancy.
// define( 'CUSTOM_USER_TABLE', 'shared_cp_users' );
// define( 'CUSTOM_USER_META_TABLE', 'shared_cp_usermeta' );
//
// ** Useful if you want to chance the default language and/or
// ** support multiple languages.
// define( 'WPLANG', 'de_DE' );
// define( 'WP_LANG_DIR', CP_CONTENT_PATH . '/languages' );
//
// ** Collect up all the queries run during a page load for analysis.
// define( 'SAVEQUERIES', true );
//
// ** Override default file permissions, if necessary.
// define( 'FS_CHMOD_DIR', ( 0755 & ~ umask() ) );
// define( 'FS_CHMOD_FILE', ( 0644 & ~ umask() ) );
//
// ** Used to support upgrades and when the web server
// ** does not have permissions to write to the plugin
// ** directory.
// define( 'FS_METHOD', 'ftpext' ); // "direct", "ssh2", "ftpext", or "ftpsockets".
// define( 'FTP_BASE', __DIR__ . CP_CORE_PATH );
// define( 'FTP_CONTENT_DIR', __DIR__ . CP_CONTENT_PATH );
// define( 'FTP_PLUGIN_DIR ', WP_PLUGIN_DIR );
// define( 'FTP_PUBKEY', '/home/username/.ssh/id_rsa.pub' );
// define( 'FTP_PRIKEY', '/home/username/.ssh/id_rsa' );
// define( 'FTP_USER', 'username' );
// define( 'FTP_PASS', 'password' );
// define( 'FTP_HOST', 'ftp.example.org' );
// define( 'FTP_SSL', true );
//
// ** Control the psuedo-cron built into ClassicPress **
// ** This allows disabling so an external cron service like
// ** SetCronJob.com can be used,
// define( 'DISABLE_WP_CRON', true );
// ** This allows cron to run on a redirect rather than at
// ** the end of a page load. Only use as last resort.
// define( 'ALTERNATE_WP_CRON', true );
// ** Ensure cron can only one once ever "n" seconds.
// define( 'WP_CRON_LOCK_TIMEOUT', 300 );
//
// ** Delete trashed posts ever "n" days **
// define( 'EMPTY_TRASH_DAYS', 30 );
//
// ** Stop end-users from adding plugins or changing themes **
// define( 'DISALLOW_FILE_MODS', true );
//
// ** Stop editing of plugins or themes from within the admin **
// define( 'DISALLOW_FILE_EDIT', true );
//
// ** Secure the wp-admin and login via HTTPS, respectively **
// define( 'FORCE_SSL_ADMIN', true );
// define( 'FORCE_SSL_LOGIN', true );
//
// ** Block outgoing HTTP requests, except for those whitelisted **
// define( 'WP_HTTP_BLOCK_EXTERNAL', true );
// define( 'WP_ACCESSIBLE_HOSTS', 'api.classicpress.org,*.github.com' );
//
// ** Disable automatic updater from updating core, plugins, themes, etc.
// define( 'AUTOMATIC_UPDATER_DISABLED', true );
//
// ** Disable automatic update of specific types of core updates **
// ** Disable all core updates
// define( 'WP_AUTO_UPDATE_CORE', false );
//
// ** Enable all core updates, including minor and major
// define( 'WP_AUTO_UPDATE_CORE', true );
//
// ** Enable core updates for minor releases (default):
// define( 'WP_AUTO_UPDATE_CORE', 'minor' );
//
// ** Overwrite image edits rather than keep all edits
// define( 'IMAGE_EDIT_OVERWRITE', true );
//
// ** No unfiltered HTML, even Administrator and Editor roles
// define( 'DISALLOW_UNFILTERED_HTML', true );
//
// ** Set the theme ClassicPress will default to
// define('WP_DEFAULT_THEME', 'my-custom-theme');;
//
// NOTE: The above are not all potential options. Plugins and
// themes often have their own config settings, and there are
// a several more obscure settings buried in ClassicPress.
// So check the docs and/or Google to discover more.
//

/*----------------------------------------------------*
 *  END-CONFIG - Add your configuration changes ABOVE
 *  What follows is ClassicPress default config.
 * ---------------------------------------------------*/

/** MySQL hostname */
if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', 'localhost' );
}

/** Database Charset to use in creating database tables. */
if ( ! defined( 'DB_CHARSET' ) ) {
	define( 'DB_CHARSET', 'utf8' );
}

/** The Database Collate type. Don't change this if in doubt. */
if ( ! defined( 'DB_COLLATE' ) ) {
	define( 'DB_COLLATE', '' );
}
/**
 * ClassicPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
if ( ! isset( $table_prefix ) ) {
	$table_prefix = 'cp_';
}

/**
 * For developers: ClassicPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/**
 * Allow developer to specify that ClassicPress core is stored
 * somewhere else besides the root, such as '/classicpress',
 * '/cp', '/core', or elsewhere. To use this feature define()
 * `CP_CORE_PATH` at the top of this file.
 */
if ( ! defined( 'CP_CORE_PATH' ) ) {
	define( 'CP_CORE_PATH',  '/' );
}

/**
 * Allow developer to specify that the ClassicPress "content"
 * directory is located somewhere else besides '/wp-content',
 * such as '/content', '/app', or other. To use this feature define()
 * `CP_CONTENT_PATH` at the top of this file.
 */
if ( ! defined( 'CP_CONTENT_PATH' ) ) {
	define( 'CP_CONTENT_PATH',  '/wp-content' );
}

/** Absolute path to the ClassicPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . CP_CORE_PATH );
}

/* That's it, you are all done! Happy Pressing. */

/** Sets up ClassicPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
