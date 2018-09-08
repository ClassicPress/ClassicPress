<?php
/**
 * The ClassicPress version string
 *
 * @global string $cp_version
 */
$cp_version = '1.0.0-alpha';

/**
 * Return the ClassicPress version string.
 *
 * `function_exists( 'classicpress_version' )` is the recommended way for
 * plugins and themes to determine whether they are running under ClassicPress.
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
 * The WordPress version string
 *
 * This is still used internally for various core and plugin functions, and to
 * keep compatibility checks working as intended.  The ClassicPress version is
 * stored separately.
 *
 * @see classicpress_version()
 *
 * @global string $wp_version
 */
$wp_version = '4.9.9-alpha-43554-src';

/**
 * Holds the WordPress DB revision, increments when changes are made to the WordPress DB schema.
 *
 * @global int $wp_db_version
 */
$wp_db_version = 38590;

/**
 * Holds the TinyMCE version
 *
 * @global string $tinymce_version
 */
$tinymce_version = '4800-20180716';

/**
 * Holds the required PHP version
 *
 * @global string $required_php_version
 */
$required_php_version = '5.2.4';

/**
 * Holds the required MySQL version
 *
 * @global string $required_mysql_version
 */
$required_mysql_version = '5.0';
