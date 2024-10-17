<?php

/**
 * WordPress database access abstraction class.
 *
 * Original code from {@link http://php.justinvincent.com Justin Vincent (justin@visunet.ie)}
 *
 * @package ClassicPress
 * @subpackage Database
 * @since 0.71
 */

/**
 * @since 0.71
 */
define( 'EZSQL_VERSION', 'WP1.25' );

/**
 * @since 0.71
 */
define( 'OBJECT', 'OBJECT' );
// phpcs:ignore Generic.NamingConventions.UpperCaseConstantName.ConstantNotUpperCase
define( 'object', 'OBJECT' ); // Back compat.

/**
 * @since 2.5.0
 */
define( 'OBJECT_K', 'OBJECT_K' );

/**
 * @since 0.71
 */
define( 'ARRAY_A', 'ARRAY_A' );

/**
 * @since 0.71
 */
define( 'ARRAY_N', 'ARRAY_N' );

/**
 * WordPress database access abstraction class.
 *
 * This class is used to interact with a database without needing to use raw SQL statements.
 * By default, WordPress uses this class to instantiate the global $wpdb object, providing
 * access to the WordPress database.
 *
 * It is possible to replace this class with your own by setting the $wpdb global variable
 * in wp-content/db.php file to your class. The wpdb class will still be included, so you can
 * extend it or simply use your own.
 *
 * @link https://developer.wordpress.org/reference/classes/wpdb/
 *
 * @since 0.71
 */
#[AllowDynamicProperties]
class wpdb {

  /**
   * Whether to show SQL/DB errors.
   *
   * Default is to show errors if both WP_DEBUG and WP_DEBUG_DISPLAY evaluate to true.
   *
   * @since 0.71
   *
   * @var bool
   */
  public $show_errors = false;

  /**
   * Whether to suppress errors during the DB bootstrapping. Default false.
   *
   * @since 2.5.0
   *
   * @var bool
   */
  public $suppress_errors = false;

  /**
   * The error encountered during the last query.
   *
   * @since 2.5.0
   *
   * @var string
   */
  public $last_error = '';

  /**
   * The number of queries made.
   *
   * @since 1.2.0
   *
   * @var int
   */
  public $num_queries = 0;

  /**
   * Count of rows returned by the last query.
   *
   * @since 0.71
   *
   * @var int
   */
  public $num_rows = 0;

  /**
   * Count of rows affected by the last query.
   *
   * @since 0.71
   *
   * @var int
   */
  public $rows_affected = 0;

  /**
   * The ID generated for an AUTO_INCREMENT column by the last query (usually INSERT).
   *
   * @since 0.71
   *
   * @var int
   */
  public $insert_id = 0;

  /**
   * The last query made.
   *
   * @since 0.71
   *
   * @var string
   */
  public $last_query;

  /**
   * Results of the last query.
   *
   * @since 0.71
   *
   * @var stdClass[]|null
   */
  public $last_result;

  /**
   * Database query result.
   *
   * Possible values:
   *
   * - `mysqli_result` instance for successful SELECT, SHOW, DESCRIBE, or EXPLAIN queries
   * - `true` for other query types that were successful
   * - `null` if a query is yet to be made or if the result has since been flushed
   * - `false` if the query returned an error
   *
   * @since 0.71
   *
   * @var