<?php

class WP_Tests_Exception extends PHPUnit_Framework_Exception {

}

/**
 * General exception for wp_die().
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class WPDieException extends Exception {}

/**
 * Exception for cases of wp_die(), for Ajax tests.
 *
 * This means there was an error (no output, and a call to wp_die).
 *
 * @package    ClassicPress
 * @subpackage Unit Tests
 * @since      3.4.0
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class WPAjaxDieStopException extends WPDieException {}

/**
 * Exception for cases of wp_die(), for Ajax tests.
 *
 * This means the execution of the Ajax function should be halted, but the unit test
 * can continue. The function finished normally and there was no error (output happened,
 * but wp_die was called to end execution). This is used with WP_Ajax_Response::send().
 *
 * @package    ClassicPress
 * @subpackage Unit Tests
 * @since      3.4.0
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class WPAjaxDieContinueException extends WPDieException {}
