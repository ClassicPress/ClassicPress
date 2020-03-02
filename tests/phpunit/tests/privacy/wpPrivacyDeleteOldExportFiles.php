<?php
/**
 * Define a class to test `wp_privacy_delete_old_export_files()`.
 *
 * @package WordPress
 * @subpackage UnitTests
 * @since 4.9.6
 */

/**
 * Test cases for `wp_privacy_delete_old_export_files()`.
 *
 * @group privacy
 * @covers wp_privacy_delete_old_export_files
 *
 * @since 4.9.6
 */
class Tests_Privacy_WpPrivacyDeleteOldExportFiles extends WP_UnitTestCase {
	/**
	 * Path to the index file that blocks directory listing on poorly-configured servers.
	 *
	 * @since 4.9.6
	 *
	 * @var string $index_path
	 */
	protected static $index_path;

	/**
	 * Path to an export file that is past the expiration date.
	 *
	 * @since 4.9.6
	 *
	 * @var string $expired_export_file
	 */
	protected static $expired_export_file;

	/**
	 * Path to an export file that is active.
	 *
	 * @since 4.9.6
	 *
	 * @var string $expired_export_file
	 */
	protected static $active_export_file;

	/**
	 * Create fixtures that are shared by multiple test cases.
	 *
	 * @param WP_UnitTest_Factory $factory The base factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		$exports_dir = wp_privacy_exports_dir();

		if ( ! is_dir( $exports_dir ) ) {
			wp_mkdir_p( $exports_dir );
		}

		self::$index_path          = $exports_dir . 'index.html';
		self::$expired_export_file = $exports_dir . 'wp-personal-data-file-user-at-example-com-0123456789abcdef.zip';
		self::$active_export_file  = $exports_dir . 'wp-personal-data-file-user-at-example-com-fedcba9876543210.zip';
	}

	/**
	 * Perform setup operations that are shared across all tests.
	 */
	public function setUp() {
		parent::setUp();

		touch( self::$index_path, time() - 30 * WEEK_IN_SECONDS );
		touch( self::$expired_export_file, time() - 5 * DAY_IN_SECONDS );
		touch( self::$active_export_file, time() - 2 * DAY_IN_SECONDS );
	}

	/**
	 * Restore the system state to what it was before this case was setup.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_file( self::$expired_export_file );
		wp_delete_file( self::$active_export_file );
	}

	/**
	 * The function should not throw notices when the exports directory doesn't exist.
	 *
	 * @since 4.9.6
	 */
	public function test_non_existent_folders_should_not_cause_errors() {
		add_filter( 'wp_privacy_exports_dir', array( $this, 'filter_bad_exports_dir' ) );
		wp_privacy_delete_old_export_files();
		remove_filter( 'wp_privacy_exports_dir', array( $this, 'filter_bad_exports_dir' ) );

		/*
		 * The test will automatically fail if the function triggers a notice,
		 * so this dummy assertion is just for accurate stats.
		 */
		$this->assertTrue( true );
	}

	/**
	 * Return the path to a non-existent folder.
	 *
	 * @since 4.9.6
	 *
	 * @param string $exports_dir The default personal data export directory.
	 *
	 * @return string The path to a folder that doesn't exist.
	 */
	public function filter_bad_exports_dir( $exports_dir ) {
		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . 'invalid-12345';
	}

	/**
	 * The function should delete files that are past the expiration date.
	 *
	 * @since 4.9.6
	 */
	public function test_expired_files_should_be_deleted() {
		wp_privacy_delete_old_export_files();

		$this->assertFalse( file_exists( self::$expired_export_file ) );
	}

	/**
	 * The function should not delete files that are not past the expiration date.
	 *
	 * @since 4.9.6
	 */
	public function test_unexpired_files_should_not_be_deleted() {
		wp_privacy_delete_old_export_files();

		$this->assertTrue( file_exists( self::$active_export_file ) );
	}

	/**
	 * The function should never delete the index file, even if it's past the expiration date.
	 *
	 * @since 4.9.6
	 */
	public function test_index_file_should_never_be_deleted() {
		wp_privacy_delete_old_export_files();

		$this->assertTrue( file_exists( self::$index_path ) );
	}
}
