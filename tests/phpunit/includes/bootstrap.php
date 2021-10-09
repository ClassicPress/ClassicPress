<?php
/**
 * Installs ClassicPress for running the tests and loads ClassicPress and the test libraries
 */

$config_file_path = dirname( dirname( __FILE__ ) );
if ( ! file_exists( $config_file_path . '/wp-tests-config.php' ) ) {
	// Support the config file from the root of the source repository.
	if ( basename( $config_file_path ) === 'phpunit' && basename( dirname( $config_file_path ) ) === 'tests' )
		$config_file_path = dirname( dirname( $config_file_path ) );
}
$config_file_path .= '/wp-tests-config.php';

/*
 * Globalize some ClassicPress variables, because PHPUnit loads this file inside a function
 * See: https://github.com/sebastianbergmann/phpunit/issues/325
 */
global $wpdb, $current_site, $current_blog, $wp_rewrite, $shortcode_tags, $wp, $phpmailer, $wp_theme_directories;

if ( ! is_readable( $config_file_path ) ) {
	echo "ERROR: wp-tests-config.php is missing! Please see wp-tests-config-sample.php for setup instructions.\n";
	exit( 1 );
}
require_once $config_file_path;
require_once dirname( __FILE__ ) . '/functions.php';

if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS && ! is_dir( ABSPATH ) ) {
	echo "Error: The /build/ directory is missing! Please run `npm run build` prior to running PHPUnit.\n";
	exit( 1 );
}

$phpunit_version = tests_get_phpunit_version();

if ( version_compare( $phpunit_version, '5.7.21', '<' ) ) {
	printf(
		"Error: Looks like you're using PHPUnit %s. WordPress requires at least PHPUnit 5.7.21.\n",
		$phpunit_version
	);
	echo "Please use the latest PHPUnit version supported for the PHP version you are running the tests on.\n";
	exit( 1 );
}

/*
 * Load the PHPUnit Polyfills autoloader.
 *
 * The PHPUnit Polyfills are a requirement for the WP test suite.
 *
 * Plugin/theme integration tests can handle this in any of the following ways:
 * - When using a full WP install: run `composer update` for the WP install prior to running the tests.
 * - When using a partial WP test suite install:
 *   - Add a `yoast/phpunit-polyfills` (dev) requirement to their own `composer.json` file.
 *   - And then:
 *     - Either load the PHPUnit Polyfills autoload file prior to running the WP core bootstrap file.
 *     - Or declare a `WP_TESTS_PHPUNIT_POLYFILLS_PATH` constant pointing to the root directory
 *       of the PHPUnit Polyfills installation.
 *       This constant can be declared in the `phpunit.xml[.dist]` file like this:
 *       `<php><const name="WP_TESTS_PHPUNIT_POLYFILLS_PATH" value="path/to/yoast/phpunit-polyfills"/></php>
 *       or can be declared as a PHP constant in the `wp-tests-config.php` file or the plugin/theme
 *       test bootstrap file.
 */
if ( ! class_exists( 'Yoast\PHPUnitPolyfills\Autoload' ) ) {
	// Default location of the autoloader for WP core test runs.
	$phpunit_polyfills_autoloader = __DIR__ . '/../../../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
	$phpunit_polyfills_error      = false;

	// Allow for a custom installation location to be provided for plugin/theme integration tests.
	if ( defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
		$phpunit_polyfills_path = WP_TESTS_PHPUNIT_POLYFILLS_PATH;

		if ( is_string( WP_TESTS_PHPUNIT_POLYFILLS_PATH )
			&& '' !== WP_TESTS_PHPUNIT_POLYFILLS_PATH
		) {
			// Be tolerant to the path being provided including the filename.
			if ( substr( $phpunit_polyfills_path, -29 ) !== 'phpunitpolyfills-autoload.php' ) {
			$phpunit_polyfills_path = rtrim( $phpunit_polyfills_path, '/\\' );
				$phpunit_polyfills_path = $phpunit_polyfills_path . '/phpunitpolyfills-autoload.php';
			}

			$phpunit_polyfills_path = realpath( $phpunit_polyfills_path );
			if ( false !== $phpunit_polyfills_path ) {
				$phpunit_polyfills_autoloader = $phpunit_polyfills_path;
			} else {
				$phpunit_polyfills_error = true;
			}
		} else {
			$phpunit_polyfills_error = true;
		}
	}

	if ( $phpunit_polyfills_error || ! file_exists( $phpunit_polyfills_autoloader ) ) {
		echo 'Error: The PHPUnit Polyfills library is a requirement for running the WP test suite.' . PHP_EOL;
		if ( isset( $phpunit_polyfills_path ) ) {
			printf(
				'The PHPUnit Polyfills autoload file was not found in "%s"' . PHP_EOL,
				WP_TESTS_PHPUNIT_POLYFILLS_PATH
			);
			echo 'Please verify that the file path provided in the WP_TESTS_PHPUNIT_POLYFILLS_PATH constant is correct.' . PHP_EOL;
		} else {
			echo 'You need to run `composer update` before running the tests.' . PHP_EOL;
			echo 'Once the dependencies are installed, you can run the tests using the Composer-installed version of PHPUnit or using a PHPUnit phar file, but the dependencies do need to be installed whichever way the tests are run.' . PHP_EOL;
		}
	exit( 1 );
	}

	require_once $phpunit_polyfills_autoloader;
}
unset( $phpunit_polyfills_autoloader, $phpunit_polyfills_error, $phpunit_polyfills_path );

/*
 * Minimum version of the PHPUnit Polyfills package as declared in `composer.json`.
 * Only needs updating when new polyfill features start being used in the test suite.
 */
$phpunit_polyfills_minimum_version = '1.0.1';
if ( class_exists( '\Yoast\PHPUnitPolyfills\Autoload' )
	&& ( defined( '\Yoast\PHPUnitPolyfills\Autoload::VERSION' ) === false
	|| version_compare( \Yoast\PHPUnitPolyfills\Autoload::VERSION, $phpunit_polyfills_minimum_version, '<' ) )
) {
	printf(
		'Error: Version mismatch detected for the PHPUnit Polyfills. Please ensure that PHPUnit Polyfills %s or higher is loaded.' . PHP_EOL,
		$phpunit_polyfills_minimum_version
	);
	if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
		echo 'Please run `composer update` to install the latest version.' . PHP_EOL;
	}
	exit( 1 );
}
unset( $phpunit_polyfills_minimum_version );

// If running core tests, check if all the required PHP extensions are loaded before running the test suite.
if ( defined( 'WP_RUN_CORE_TESTS' ) && WP_RUN_CORE_TESTS ) {
	$required_extensions = array(
		'gd',
	);
	$missing_extensions  = array();

	foreach ( $required_extensions as $extension ) {
		if ( ! extension_loaded( $extension ) ) {
			$missing_extensions[] = $extension;
		}
	}

	if ( $missing_extensions ) {
		printf(
			"Error: The following required PHP extensions are missing from the testing environment: %s.\n",
			implode( ', ', $missing_extensions )
		);
		echo "Please make sure they are installed and enabled.\n",
		exit( 1 );
	}
}

$required_constants = array(
	'WP_TESTS_DOMAIN',
	'WP_TESTS_EMAIL',
	'WP_TESTS_TITLE',
	'WP_PHP_BINARY',
);
$missing_constants  = array();

foreach ( $required_constants as $constant ) {
	if ( ! defined( $constant ) ) {
		$missing_constants[] = $constant;
	}
}

if ( $missing_constants ) {
	printf(
		"Error: The following required constants are not defined: %s.\n",
		implode( ', ', $missing_constants )
	);
	echo "Please check out `wp-tests-config-sample.php` for an example.\n",
	exit( 1 );
}

tests_reset__SERVER();

define( 'WP_TESTS_TABLE_PREFIX', $table_prefix );
define( 'DIR_TESTDATA', dirname( __FILE__ ) . '/../data' );

define( 'WP_LANG_DIR', DIR_TESTDATA . '/languages' );

// Cron tries to make an HTTP request to the blog, which always fails, because tests are run in CLI mode only
define( 'DISABLE_WP_CRON', true );

define( 'WP_MEMORY_LIMIT', -1 );
define( 'WP_MAX_MEMORY_LIMIT', -1 );

define( 'REST_TESTS_IMPOSSIBLY_HIGH_NUMBER', 99999999 );

$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';

// Should we run in multisite mode?
$multisite = '1' == getenv( 'WP_MULTISITE' );
$multisite = $multisite || ( defined( 'WP_TESTS_MULTISITE') && WP_TESTS_MULTISITE );
$multisite = $multisite || ( defined( 'MULTISITE' ) && MULTISITE );

// Override the PHPMailer
require_once( dirname( __FILE__ ) . '/mock-mailer.php' );
$phpmailer = new MockPHPMailer( true );

if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
	define( 'WP_DEFAULT_THEME', 'default' );
}
$wp_theme_directories = array();

if ( file_exists( DIR_TESTDATA . '/themedir1' ) ) {
	$wp_theme_directories[] = DIR_TESTDATA . '/themedir1';
}

system( WP_PHP_BINARY . ' ' . escapeshellarg( dirname( __FILE__ ) . '/install.php' ) . ' ' . escapeshellarg( $config_file_path ) . ' ' . $multisite, $retval );
if ( 0 !== $retval ) {
	exit( $retval );
}

if ( $multisite ) {
	echo "Running as multisite..." . PHP_EOL;
	defined( 'MULTISITE' ) or define( 'MULTISITE', true );
	defined( 'SUBDOMAIN_INSTALL' ) or define( 'SUBDOMAIN_INSTALL', false );
	$GLOBALS['base'] = '/';
} else {
	echo "Running as single site... To run multisite, use -c tests/phpunit/multisite.xml" . PHP_EOL;
}
unset( $multisite );

$GLOBALS['_wp_die_disabled'] = false;
// Allow tests to override wp_die
tests_add_filter( 'wp_die_handler', '_wp_die_handler_filter' );

// Preset ClassicPress options defined in bootstrap file.
// Used to activate themes, plugins, as well as  other settings.
if(isset($GLOBALS['wp_tests_options'])) {
	function wp_tests_options( $value ) {
		$key = substr( current_filter(), strlen( 'pre_option_' ) );
		return $GLOBALS['wp_tests_options'][$key];
	}

	foreach ( array_keys( $GLOBALS['wp_tests_options'] ) as $key ) {
		tests_add_filter( 'pre_option_'.$key, 'wp_tests_options' );
	}
}

// Load ClassicPress
require_once ABSPATH . '/wp-settings.php';

// Delete any default posts & related data
_delete_all_posts();

// Load class aliases for compatibility with PHPUnit 6+.
if ( version_compare( tests_get_phpunit_version(), '6.0', '>=' ) ) {
	require __DIR__ . '/phpunit6/compat.php';
}

require __DIR__ . '/phpunit-adapter-testcase.php';
require __DIR__ . '/abstract-testcase.php';
require __DIR__ . '/testcase.php';
require __DIR__ . '/testcase-rest-api.php';
require __DIR__ . '/testcase-rest-controller.php';
require __DIR__ . '/testcase-rest-post-type-controller.php';
require __DIR__ . '/testcase-xmlrpc.php';
require __DIR__ . '/testcase-ajax.php';
require __DIR__ . '/testcase-canonical.php';
require __DIR__ . '/exceptions.php';
require __DIR__ . '/utils.php';
require __DIR__ . '/spy-rest-server.php';

/**
 * A class to handle additional command line arguments passed to the script.
 *
 * If it is determined that phpunit was called with a --group that corresponds
 * to an @ticket annotation (such as `phpunit --group 12345` for bugs marked
 * as #WP12345), then it is assumed that known bugs should not be skipped.
 *
 * If WP_TESTS_FORCE_KNOWN_BUGS is already set in wp-tests-config.php, then
 * how you call phpunit has no effect.
 */
class WP_PHPUnit_Util_Getopt {

	function __construct( $argv ) {
		$skipped_groups = array(
			'ajax' => true,
			'ms-files' => true,
			'external-http' => true,
		);

		while ( current( $argv ) ) {
			$option = current( $argv );
			$value  = next( $argv );

			switch ( $option ) {
				case '--exclude-group':
					foreach ( $skipped_groups as $group_name => $skipped ) {
						$skipped_groups[ $group_name ] = false;
					}
					continue 2;
				case '--group':
					$groups = explode( ',', $value );
					foreach ( $groups as $group ) {
						if ( is_numeric( $group ) || preg_match( '/^(UT|Plugin)\d+$/', $group ) ) {
							WP_UnitTestCase::forceTicket( $group );
						}
					}

					foreach ( $skipped_groups as $group_name => $skipped ) {
						if ( in_array( $group_name, $groups ) ) {
							$skipped_groups[ $group_name ] = false;
						}
					}
					continue 2;
			}
		}

		$skipped_groups = array_filter( $skipped_groups );
		foreach ( $skipped_groups as $group_name => $skipped ) {
			echo sprintf( 'Not running %1$s tests. To execute these, use --group %1$s.', $group_name ) . PHP_EOL;
		}

		if ( ! isset( $skipped_groups['external-http'] ) ) {
			echo PHP_EOL;
			echo 'External HTTP skipped tests can be caused by timeouts.' . PHP_EOL;
			echo 'If this changeset includes changes to HTTP, make sure there are no timeouts.' . PHP_EOL;
			echo PHP_EOL;
		}
    }

}
new WP_PHPUnit_Util_Getopt( $_SERVER['argv'] );
