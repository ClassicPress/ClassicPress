<?php
/**
 * The ClassicPress version string
 *
 * This string is the "base" version of the current source tree.  It may have
 * one of the following semver-compliant formats:
 *
 * - 1.0.0-alpha0+dev  : development version, before any official releases of
 *                       the 1.0.0 series (after the "null release" 1.0.0-alpha0)
 * - 1.0.0-alpha1      : official release 1.0.0-alpha1
 * - 1.0.0-alpha1+dev  : the commit immediately after 1.0.0-alpha1, and any
 *                       development commits before the next release
 * - 1.0.0-beta1       : official release 1.0.0-beta1
 * - 1.0.0-beta1+dev   : the commit immediately after 1.0.0-beta1, and any
 *                       development commits before the next release
 * - 1.0.0             : official release 1.0.0
 * - 1.0.0+dev         : the commit immediately after 1.0.0, and any
 *                       development commits before the 1.0.1 alpha, beta, or
 *                       final release(s)
 *
 * In the source repository this string will always contain the '+dev' suffix.
 * In released builds it will never contain the '+dev' suffix.
 *
 * When nightly (development) builds are created, this suffix is automatically
 * updated to e.g. '+nightly.20181019'.  When alpha, beta, or final release
 * builds are created, the suffix is removed.
 *
 * @global string $cp_version
 */
$cp_version = '2.5.0+dev';

/**
 * The WordPress version string.
 *
 * Holds the current version number for WordPress core. Used to bust caches
 * and to enable development mode for scripts when running from the /src directory.
 *
 * @see classicpress_version()
 *
 * @global string $wp_version
 */
$wp_version = '6.2.6';

/**
 * Holds the WordPress DB revision, increments when changes are made to the WordPress DB schema.
 *
 * @global int $wp_db_version
 */
$wp_db_version = 56657;

/**
 * Holds the ClassicPress DB revision, increments when changes are made to the ClassicPress DB schema.
 * Suggestion: use the number on the Pull Request where version is bumped.
 *
 * @global int $cp_db_version
 */
$cp_db_version = 1446;

/**
 * Holds the TinyMCE version.
 *
 * @global string $tinymce_version
 */
$tinymce_version = '49110-20201110';

/**
 * Holds the required PHP version.
 *
 * @global string $required_php_version
 */
$required_php_version = '7.4.0';

/**
 * Holds the required MySQL version.
 *
 * @global string $required_mysql_version
 */
$required_mysql_version = '5.0';

/**
 * Return the ClassicPress version string.
 *
 * `function_exists( 'classicpress_version' )` is the recommended way for
 * plugins and themes to determine whether they are running under ClassicPress.
 *
 * @since CP-1.0.0
 *
 * @return string The ClassicPress version string.
 */
if ( ! function_exists( 'classicpress_version' ) ) {
	function classicpress_version() {
		global $cp_version;
		return $cp_version;
	}
}

/**
 * Return the ClassicPress version number without any alpha/beta/etc suffixes.
 *
 * @since CP-1.0.0
 *
 * @return string The ClassicPress version number with no suffix.
 */
if ( ! function_exists( 'classicpress_version_short' ) ) {
	function classicpress_version_short() {
		global $cp_version;
		return preg_replace( '#[+-].*$#', '', $cp_version );
	}
}

/**
 * Return whether ClassicPress is running as a source install (the result of
 * cloning the source repository rather than installing a built version).
 *
 * This is mostly supported, but there are a few things that need to work
 * slightly differently or need to be disabled.
 *
 * @since CP-1.0.0
 *
 * @return bool Whether ClassicPress is running as a source install.
 */
if ( ! function_exists( 'classicpress_is_dev_install' ) ) {
	function classicpress_is_dev_install() {
		global $cp_version;
		return substr( $cp_version, -4 ) === '+dev';
	}
}

/**
 * Determine whether there is a ClassicPress update available
 *
 * @since CP-2.5.0
 *
 * @return bool Whether there is a ClassicPress core update available
 */
if ( ! function_exists( 'classicpress_has_update' ) ) {
	function classicpress_has_update() {
		$updates_from_api = get_site_transient( 'update_core' );

		if ( empty( $updates_from_api ) ) {
			return true;
		} elseif ( empty( $updates_from_api->updates ) ) {
			wp_version_check( array(), true );
			$updates_from_api = get_site_transient( 'update_core' );
		}

		if ( empty( $updates_from_api->updates ) || empty( $updates_from_api->updates[0]->current ) || $updates_from_api->version_checked !== $updates_from_api->updates[0]->current ) {
			return true;
		}

		return false;
	}
}
