<?php

require_once dirname( __FILE__ ) . '/base.php';

/**
 * @group filesystem
 * @group wp-filesystem
 */
class WP_Filesystem_find_folder_UnitTestCases extends WP_Filesystem_UnitTestCase {

	function test_ftp_has_root_access() {
		global $wp_filesystem;
		$fs = $wp_filesystem;
		$fs->init('
			/var/www/classicpress/
			/var/www/classicpress/wp-includes/
			/var/www/classicpress/index.php
		');

		$path = $fs->find_folder( '/var/www/classicpress/' );
		$this->assertSame( '/var/www/classicpress/', $path );

		$path = $fs->find_folder( '/this/directory/doesnt/exist/' );
		$this->assertFalse( $path );

	}

	function test_sibling_classicpress_in_subdir() {
		global $wp_filesystem;
		$fs = $wp_filesystem;
		$fs->init('
			/www/example.com/classicpress/
			/www/example.com/classicpress/wp-includes/
			/www/example.com/classicpress/index.php
			/www/cp.example.com/classicpress/
			/www/cp.example.com/classicpress/wp-includes/
			/www/cp.example.com/classicpress/wp-content/
			/www/cp.example.com/classicpress/index.php
			/www/index.php
		');

		$path = $fs->find_folder( '/var/www/example.com/classicpress/' );
		$this->assertSame( '/www/example.com/classicpress/', $path );

		$path = $fs->find_folder( '/var/www/cp.example.com/classicpress/wp-content/' );
		$this->assertSame( '/www/cp.example.com/classicpress/wp-content/', $path );

	}

	/**
	 * Two ClassicPress installations, with one contained within the other
	 * FTP / = /var/www/example.com/ on Disk
	 * example.com at /
	 * cp.example.com at /cp.example.com/classicpress/
	 */
	function test_subdir_of_another() {
		global $wp_filesystem;
		$fs = $wp_filesystem;
		$fs->init('
			/cp.example.com/index.php
			/cp.example.com/classicpress/
			/cp.example.com/classicpress/wp-includes/
			/cp.example.com/classicpress/index.php
			/wp-includes/
			/index.php
		');

		$path = $fs->abspath( '/var/www/example.com/cp.example.com/classicpress/' );
		$this->assertSame( '/cp.example.com/classicpress/', $path );

		$path = $fs->abspath( '/var/www/example.com/' );
		$this->assertSame( '/', $path );

	}

	/**
	 * Test the ClassicPress ABSPATH containing TWO tokens (www) of which exists in the current FTP home.
	 *
	 * @see https://core.trac.wordpress.org/ticket/20934
	 */
	function test_multiple_tokens_in_path1() {
		global $wp_filesystem;
		$fs = $wp_filesystem;
		$fs->init('
			# www.example.com
			/example.com/www/index.php
			/example.com/www/wp-includes/
			/example.com/www/wp-content/plugins/

			# sub.example.com
			/example.com/sub/index.php
			/example.com/sub/wp-includes/
			/example.com/sub/wp-content/plugins/
		');

		// www.example.com
		$path = $fs->abspath( '/var/www/example.com/www/' );
		$this->assertSame( '/example.com/www/', $path );

		// sub.example.com
		$path = $fs->abspath( '/var/www/example.com/sub/' );
		$this->assertSame( '/example.com/sub/', $path );

		// sub.example.com - Plugins
		$path = $fs->find_folder( '/var/www/example.com/sub/wp-content/plugins/' );
		$this->assertSame( '/example.com/sub/wp-content/plugins/', $path );
	}

}
