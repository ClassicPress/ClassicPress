<?php

if ( is_multisite() ) :

	/**
	 * Tests specific to the ms_files_rewriting option in multisite.
	 *
	 * The ms-files group tag must be used for these tests to run as the constants
	 * set in ms_upload_constants() conflict with a non ms-files configuration.
	 *
	 * @group ms-files
	 * @group multisite
	 */
	class Tests_Multisite_msFilesRewriting extends WP_UnitTestCase {

		public function set_up() {
			parent::set_up();
			update_site_option( 'ms_files_rewriting', 1 );
			ms_upload_constants();
		}

		public function test_switch_upload_dir() {
			$this->assertTrue( is_main_site() );

			$site = get_current_site();
			$date = date_format( date_create( 'now' ), 'Y/m' );

			$user_id  = self::factory()->user->create( array( 'role' => 'administrator' ) );
			$blog_id2 = self::factory()->blog->create( array( 'user_id' => $user_id ) );
			$info     = wp_upload_dir();
			$this->assertSame( 'http://' . $site->domain . '/wp-content/uploads/' . $date, $info['url'] );
			$this->assertSame( ABSPATH . 'wp-content/uploads/' . $date, $info['path'] );
			$this->assertSame( '/' . $date, $info['subdir'] );
			$this->assertFalse( $info['error'] );

			switch_to_blog( $blog_id2 );
			$info2 = wp_upload_dir();
			$this->assertNotEquals( $info, $info2 );
			$this->assertSame( get_option( 'siteurl' ) . '/wp-content/blogs.dir/' . get_current_blog_id() . '/files/' . $date, $info2['url'] );
			$this->assertSame( ABSPATH . 'wp-content/blogs.dir/' . get_current_blog_id() . '/files/' . $date, $info2['path'] );
			$this->assertSame( '/' . $date, $info2['subdir'] );
			$this->assertFalse( $info2['error'] );
			restore_current_blog();
		}

		/**
		 * When a site is deleted with wpmu_delete_blog(), only the files associated with
		 * that site should be removed. When wpmu_delete_blog() is run a second time, nothing
		 * should change with upload directories.
		 */
		public function test_upload_directories_after_multiple_wpmu_delete_blog_with_ms_files() {
			$filename = __FUNCTION__ . '.jpg';
			$contents = __FUNCTION__ . '_contents';

			// Upload a file to the main site on the network.
			$file1 = wp_upload_bits( $filename, null, $contents );

			$blog_id = self::factory()->blog->create();

			switch_to_blog( $blog_id );
			$file2 = wp_upload_bits( $filename, null, $contents );
			restore_current_blog();

			wpmu_delete_blog( $blog_id, true );

			// The file on the main site should still exist. The file on the deleted site should not.
			$this->assertFileExists( $file1['file'] );
			$this->assertFileDoesNotExist( $file2['file'] );

			wpmu_delete_blog( $blog_id, true );

			// The file on the main site should still exist. The file on the deleted site should not.
			$this->assertFileExists( $file1['file'] );
			$this->assertFileDoesNotExist( $file2['file'] );

			unlink( $file1['file'] );
		}
	}

endif;
