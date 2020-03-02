<?php

if ( is_multisite() ) :

/**
 * Tests specific to sites in multisite.
 *
 * @group ms-site
 * @group multisite
 */
class Tests_Multisite_Site extends WP_UnitTestCase {
	protected $suppress = false;
	protected static $network_ids;
	protected static $site_ids;

	function setUp() {
		global $wpdb;
		parent::setUp();
		$this->suppress = $wpdb->suppress_errors();
	}

	function tearDown() {
		global $wpdb;
		$wpdb->suppress_errors( $this->suppress );
		parent::tearDown();
	}

	public static function wpSetUpBeforeClass( $factory ) {
		self::$network_ids = array(
			'make.wordpress.org/' => array( 'domain' => 'make.wordpress.org', 'path' => '/' ),
		);

		foreach ( self::$network_ids as &$id ) {
			$id = $factory->network->create( $id );
		}
		unset( $id );

		self::$site_ids = array(
			'make.wordpress.org/'     => array( 'domain' => 'make.wordpress.org', 'path' => '/',         'site_id' => self::$network_ids['make.wordpress.org/'] ),
			'make.wordpress.org/foo/' => array( 'domain' => 'make.wordpress.org', 'path' => '/foo/',     'site_id' => self::$network_ids['make.wordpress.org/'] ),
		);

		foreach ( self::$site_ids as &$id ) {
			$id = $factory->blog->create( $id );
		}
		unset( $id );
	}

	public static function wpTearDownAfterClass() {
		global $wpdb;

		foreach( self::$site_ids as $id ) {
			wpmu_delete_blog( $id, true );
		}

		foreach( self::$network_ids as $id ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site} WHERE id= %d", $id ) );
		}
	}

	function test_switch_restore_blog() {
		global $_wp_switched_stack, $wpdb;

		$this->assertEquals( array(), $_wp_switched_stack );
		$this->assertFalse( ms_is_switched() );
		$current_blog_id = get_current_blog_id();
		$this->assertInternalType( 'integer', $current_blog_id );

		wp_cache_set( 'switch-test', $current_blog_id, 'switch-test' );
		$this->assertEquals( $current_blog_id, wp_cache_get( 'switch-test', 'switch-test' ) );

		$blog_id = self::factory()->blog->create();

		$cap_key = wp_get_current_user()->cap_key;
		switch_to_blog( $blog_id );
		$this->assertNotEquals( $cap_key, wp_get_current_user()->cap_key );
		$this->assertEquals( array( $current_blog_id ), $_wp_switched_stack );
		$this->assertTrue( ms_is_switched() );
		$this->assertEquals( $blog_id, $wpdb->blogid );
		$this->assertFalse( wp_cache_get( 'switch-test', 'switch-test' ) );
		wp_cache_set( 'switch-test', $blog_id, 'switch-test' );
		$this->assertEquals( $blog_id, wp_cache_get( 'switch-test', 'switch-test' ) );

		switch_to_blog( $blog_id );
		$this->assertEquals( array( $current_blog_id, $blog_id ), $_wp_switched_stack );
		$this->assertTrue( ms_is_switched() );
		$this->assertEquals( $blog_id, $wpdb->blogid );
		$this->assertEquals( $blog_id, wp_cache_get( 'switch-test', 'switch-test' ) );

		restore_current_blog();
		$this->assertEquals( array( $current_blog_id ), $_wp_switched_stack );
		$this->assertTrue( ms_is_switched() );
		$this->assertEquals( $blog_id, $wpdb->blogid );
		$this->assertEquals( $blog_id, wp_cache_get( 'switch-test', 'switch-test' ) );

		restore_current_blog();
		$this->assertEquals( $cap_key, wp_get_current_user()->cap_key );
		$this->assertEquals( $current_blog_id, get_current_blog_id() );
		$this->assertEquals( array(), $_wp_switched_stack );
		$this->assertFalse( ms_is_switched() );
		$this->assertEquals( $current_blog_id, wp_cache_get( 'switch-test', 'switch-test' ) );

		$this->assertFalse( restore_current_blog() );
	}

	/**
	 * Test the cache keys and database tables setup through the creation of a site.
	 */
	function test_created_site_details() {
		global $wpdb;

		$blog_id = self::factory()->blog->create();

		$this->assertInternalType( 'int', $blog_id );
		$prefix = $wpdb->get_blog_prefix( $blog_id );

		// $get_all = false, only retrieve details from the blogs table
		$details = get_blog_details( $blog_id, false );

		// Combine domain and path for a site specific cache key.
		$key = md5( $details->domain . $details->path );

		$this->assertEquals( $details, wp_cache_get( $blog_id . 'short', 'blog-details' ) );

		// get_blogaddress_by_name()
		$this->assertEquals( 'http://' . $details->domain . $details->path, get_blogaddress_by_name( trim( $details->path, '/' ) ) );

		// These are empty until get_blog_details() is called with $get_all = true
		$this->assertEquals( false, wp_cache_get( $blog_id, 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-lookup' ) );

		// $get_all = true, populate the full blog-details cache and the blog slug lookup cache
		$details = get_blog_details( $blog_id, true );
		$this->assertEquals( $details, wp_cache_get( $blog_id, 'blog-details' ) );
		$this->assertEquals( $details, wp_cache_get( $key, 'blog-lookup' ) );

		// Check existence of each database table for the created site.
		foreach ( $wpdb->tables( 'blog', false ) as $table ) {
			$suppress     = $wpdb->suppress_errors();
			$table_fields = $wpdb->get_results( "DESCRIBE $prefix$table;" );
			$wpdb->suppress_errors( $suppress );

			// The table should exist.
			$this->assertNotEmpty( $table_fields );

			// And the table should not be empty, unless commentmeta, termmeta, or links.
			$result = $wpdb->get_results( "SELECT * FROM $prefix$table LIMIT 1" );
			if ( 'commentmeta' == $table || 'termmeta' == $table || 'links' == $table ) {
				$this->assertEmpty( $result );
			} else {
				$this->assertNotEmpty( $result );
			}
		}

		// update the blog count cache to use get_blog_count()
		wp_update_network_counts();
		$this->assertEquals( 2, (int) get_blog_count() );
	}

	public function test_site_caches_should_invalidate_when_invalidation_is_not_suspended() {
		$site_id = self::factory()->blog->create();

		$details = get_site( $site_id );

		$suspend = wp_suspend_cache_invalidation( false );
		update_blog_details( $site_id, array( 'path' => '/a-non-random-test-path/' ) );
		$new_details = get_site( $site_id );
		wp_suspend_cache_invalidation( $suspend );

		$this->assertNotEquals( $details->path, $new_details->path );
	}

	public function test_site_caches_should_not_invalidate_when_invalidation_is_suspended() {
		$site_id = self::factory()->blog->create();

		$details = get_site( $site_id );

		$suspend = wp_suspend_cache_invalidation();
		update_blog_details( $site_id, array( 'path' => '/a-non-random-test-path/' ) );
		$new_details = get_site( $site_id );
		wp_suspend_cache_invalidation( $suspend );

		$this->assertEquals( $details->path, $new_details->path );
	}

	/**
	 * When a site is flagged as 'deleted', its data should be cleared from cache.
	 */
	function test_data_in_cache_after_wpmu_delete_blog_drop_false() {
		$blog_id = self::factory()->blog->create();

		$details = get_blog_details( $blog_id, false );
		$key = md5( $details->domain . $details->path );

		// Delete the site without forcing a table drop.
		wpmu_delete_blog( $blog_id, false );

		$this->assertEquals( false, wp_cache_get( $blog_id, 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $blog_id . 'short', 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-lookup' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * When a site is flagged as 'deleted', its data should remain in the database.
	 */
	function test_data_in_tables_after_wpmu_delete_blog_drop_false() {
		global $wpdb;

		$blog_id = self::factory()->blog->create();

		// Delete the site without forcing a table drop.
		wpmu_delete_blog( $blog_id, false );

		$prefix = $wpdb->get_blog_prefix( $blog_id );
		foreach ( $wpdb->tables( 'blog', false ) as $table ) {
			$suppress = $wpdb->suppress_errors();
			$table_fields = $wpdb->get_results( "DESCRIBE $prefix$table;" );
			$wpdb->suppress_errors( $suppress );
			$this->assertNotEmpty( $table_fields, $prefix . $table );
		}
	}

	/**
	 * When a site is fully deleted, its data should be cleared from cache.
	 */
	function test_data_in_cache_after_wpmu_delete_blog_drop_true() {
		$blog_id = self::factory()->blog->create();

		$details = get_blog_details( $blog_id, false );
		$key = md5( $details->domain . $details->path );

		// Delete the site and force a table drop.
		wpmu_delete_blog( $blog_id, true );

		$this->assertEquals( false, wp_cache_get( $blog_id, 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $blog_id . 'short', 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-lookup' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * When a site is fully deleted, its data should be removed from the database.
	 */
	function test_data_in_tables_after_wpmu_delete_blog_drop_true() {
		global $wpdb;

		$blog_id = self::factory()->blog->create();

		// Delete the site and force a table drop.
		wpmu_delete_blog( $blog_id, true );

		$prefix = $wpdb->get_blog_prefix( $blog_id );
		foreach ( $wpdb->tables( 'blog', false ) as $table ) {
			$suppress = $wpdb->suppress_errors();
			$table_fields = $wpdb->get_results( "DESCRIBE $prefix$table;" );
			$wpdb->suppress_errors( $suppress );
			$this->assertEmpty( $table_fields );
		}
	}

	/**
	 * When the main site of a network is fully deleted, its data should be cleared from cache.
	 */
	function test_data_in_cache_after_wpmu_delete_blog_main_site_drop_true() {
		$blog_id = 1; // The main site in our test suite has an ID of 1.

		$details = get_blog_details( $blog_id, false );
		$key = md5( $details->domain . $details->path );

		// Delete the site and force a table drop.
		wpmu_delete_blog( $blog_id, true );

		$this->assertEquals( false, wp_cache_get( $blog_id, 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $blog_id . 'short', 'blog-details' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-lookup' ) );
		$this->assertEquals( false, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * When the main site of a network is fully deleted, its data should remain in the database.
	 */
	function test_data_in_tables_after_wpmu_delete_blog_main_site_drop_true() {
		global $wpdb;

		$blog_id = 1; // The main site in our test suite has an ID of 1.

		// Delete the site and force a table drop.
		wpmu_delete_blog( $blog_id, true );

		$prefix = $wpdb->get_blog_prefix( $blog_id );
		foreach ( $wpdb->tables( 'blog', false ) as $table ) {
			$suppress = $wpdb->suppress_errors();
			$table_fields = $wpdb->get_results( "DESCRIBE $prefix$table;" );
			$wpdb->suppress_errors( $suppress );
			$this->assertNotEmpty( $table_fields, $prefix . $table );
		}
	}

	/**
	 * The site count of a network should change when a site is flagged as 'deleted'.
	 */
	function test_network_count_after_wpmu_delete_blog_drop_false() {
		$blog_id = self::factory()->blog->create();

		// Delete the site without forcing a table drop.
		wpmu_delete_blog( $blog_id, false );

		// update the blog count cache to use get_blog_count()
		wp_update_network_counts();
		$this->assertEquals( 1, get_blog_count() );
	}

	/**
	 * The site count of a network should change when a site is fully deleted.
	 */
	function test_blog_count_after_wpmu_delete_blog_drop_true() {
		$blog_id = self::factory()->blog->create();

		// Delete the site and force a table drop.
		wpmu_delete_blog( $blog_id, true );

		// update the blog count cache to use get_blog_count()
		wp_update_network_counts();
		$this->assertEquals( 1, get_blog_count() );
	}

	/**
	 * When a site is deleted with wpmu_delete_blog(), only the files associated with
	 * that site should be removed. When wpmu_delete_blog() is run a second time, nothing
	 * should change with upload directories.
	 */
	function test_upload_directories_after_multiple_wpmu_delete_blog() {
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
		$this->assertFileNotExists( $file2['file'] );

		wpmu_delete_blog( $blog_id, true );

		// The file on the main site should still exist. The file on the deleted site should not.
		$this->assertFileExists( $file1['file'] );
		$this->assertFileNotExists( $file2['file'] );
	}

	function test_wpmu_update_blogs_date() {
		global $wpdb;

		wpmu_update_blogs_date();

		// compare the update time with the current time, allow delta < 2
		$blog = get_site( get_current_blog_id() );
		$current_time = time();
		$time_difference = $current_time - strtotime( $blog->last_updated );
		$this->assertLessThan( 2, $time_difference );
	}

	/**
	 * Provide a counter to determine that hooks are firing when intended.
	 */
	function _action_counter_cb() {
		global $test_action_counter;
		$test_action_counter++;
	}

	/**
	 * Test cached data for a site that does not exist and then again after it exists.
	 *
	 * @see https://core.trac.wordpress.org/ticket/23405
	 */
	function test_get_blog_details_when_site_does_not_exist() {
		// Create an unused site so that we can then assume an invalid site ID.
		$blog_id = self::factory()->blog->create();
		$blog_id++;

		// Prime the cache for an invalid site.
		get_blog_details( $blog_id );

		// When the cache is primed with an invalid site, the value is set to -1.
		$this->assertEquals( -1, wp_cache_get( $blog_id, 'blog-details' ) );

		// Create a site in the invalid site's place.
		self::factory()->blog->create();

		// When a new site is created, its cache is cleared through refresh_blog_details.
		$this->assertFalse( wp_cache_get( $blog_id, 'blog-details' )  );

		$blog = get_blog_details( $blog_id );

		// When the cache is refreshed, it should now equal the site data.
		$this->assertEquals( $blog, wp_cache_get( $blog_id, 'blog-details' ) );
	}

	/**
	 * Updating a field returns the sme value that was passed.
	 */
	function test_update_blog_status() {
		$result = update_blog_status( 1, 'spam', 0 );
		$this->assertEquals( 0, $result );
	}

	/**
	 * Updating an invalid field returns the same value that was passed.
	 */
	function test_update_blog_status_invalid_status() {
		$result = update_blog_status( 1, 'doesnotexist', 'invalid' );
		$this->assertEquals( 'invalid', $result );
	}

	function test_update_blog_status_make_ham_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();
		update_blog_details( $blog_id, array( 'spam' => 1 ) );

		add_action( 'make_ham_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'spam', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->spam );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'spam' stays the same.
		update_blog_status( $blog_id, 'spam', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->spam );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'make_ham_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_make_spam_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();

		add_action( 'make_spam_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'spam', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->spam );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'spam' stays the same.
		update_blog_status( $blog_id, 'spam', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->spam );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'make_spam_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_archive_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();

		add_action( 'archive_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'archived', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->archived );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'archived' stays the same.
		update_blog_status( $blog_id, 'archived', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->archived );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'archive_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_unarchive_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();
		update_blog_details( $blog_id, array( 'archived' => 1 ) );

		add_action( 'unarchive_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'archived', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->archived );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'archived' stays the same.
		update_blog_status( $blog_id, 'archived', 0 );
		$blog = get_site( $blog_id );
		$this->assertEquals( '0', $blog->archived );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'unarchive_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_make_delete_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();

		add_action( 'make_delete_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'deleted', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->deleted );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'deleted' stays the same.
		update_blog_status( $blog_id, 'deleted', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->deleted );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'make_delete_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_make_undelete_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();
		update_blog_details( $blog_id, array( 'deleted' => 1 ) );

		add_action( 'make_undelete_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'deleted', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->deleted );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'deleted' stays the same.
		update_blog_status( $blog_id, 'deleted', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->deleted );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'make_undelete_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_mature_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();

		add_action( 'mature_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'mature', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->mature );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'mature' stays the same.
		update_blog_status( $blog_id, 'mature', 1 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '1', $blog->mature );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'mature_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_unmature_blog_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();
		update_blog_details( $blog_id, array( 'mature' => 1 ) );

		add_action( 'unmature_blog', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'mature', 0 );

		$blog = get_site( $blog_id );
		$this->assertEquals( '0', $blog->mature );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'mature' stays the same.
		update_blog_status( $blog_id, 'mature', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->mature );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'unmature_blog', array( $this, '_action_counter_cb' ), 10 );
	}

	function test_update_blog_status_update_blog_public_action() {
		global $test_action_counter;
		$test_action_counter = 0;

		$blog_id = self::factory()->blog->create();

		add_action( 'update_blog_public', array( $this, '_action_counter_cb' ), 10 );
		update_blog_status( $blog_id, 'public', 0 );

		$blog = get_site( $blog_id );
		$this->assertEquals( '0', $blog->public );
		$this->assertEquals( 1, $test_action_counter );

		// The action should fire if the status of 'mature' stays the same.
		update_blog_status( $blog_id, 'public', 0 );
		$blog = get_site( $blog_id );

		$this->assertEquals( '0', $blog->public );
		$this->assertEquals( 2, $test_action_counter );

		remove_action( 'update_blog_public', array( $this, '_action_counter_cb' ), 10 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/27952
	 */
	function test_posts_count() {
		self::factory()->post->create();
		$post2 = self::factory()->post->create();
		$this->assertEquals( 2, get_site()->post_count );

		wp_delete_post( $post2 );
		$this->assertEquals( 1, get_site()->post_count );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/26410
	 */
	function test_blog_details_cache_invalidation() {
		update_option( 'blogname', 'foo' );
		$details = get_site( get_current_blog_id() );
		$this->assertEquals( 'foo', $details->blogname );

		update_option( 'blogname', 'bar' );
		$details = get_site( get_current_blog_id() );
		$this->assertEquals( 'bar', $details->blogname );
	}

	/**
	 * Test the original and cached responses for a created and then deleted site when
	 * the blog ID is requested through get_blog_id_from_url().
	 */
	function test_get_blog_id_from_url() {
		$blog_id = self::factory()->blog->create();
		$details = get_site( $blog_id );
		$key = md5( $details->domain . $details->path );

		// Test the original response and cached response for the newly created site.
		$this->assertEquals( $blog_id, get_blog_id_from_url( $details->domain, $details->path ) );
		$this->assertEquals( $blog_id, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * Test the case insensitivity of the site lookup.
	 */
	function test_get_blog_id_from_url_is_case_insensitive() {
		$blog_id = self::factory()->blog->create( array( 'domain' => 'example.com', 'path' => '/xyz' ) );
		$details = get_site( $blog_id );

		$this->assertEquals( $blog_id, get_blog_id_from_url( strtoupper( $details->domain ), strtoupper( $details->path ) ) );
	}

	/**
	 * Test the first and cached responses for a site that does not exist.
	 */
	function test_get_blog_id_from_url_that_does_not_exist() {
		$blog_id = self::factory()->blog->create( array( 'path' => '/xyz' ) );
		$details = get_site( $blog_id );

		$this->assertEquals( 0, get_blog_id_from_url( $details->domain, 'foo' ) );
		$this->assertEquals( -1, wp_cache_get( md5( $details->domain . 'foo' ), 'blog-id-cache' ) );
	}

	/**
	 * A blog ID is still available if only the `deleted` flag is set for a site. The same
	 * behavior would be expected if passing `false` explicitly to `wpmu_delete_blog()`.
	 */
	function test_get_blog_id_from_url_with_deleted_flag() {
		$blog_id = self::factory()->blog->create();
		$details = get_site( $blog_id );
		$key = md5( $details->domain . $details->path );
		wpmu_delete_blog( $blog_id );

		$this->assertEquals( $blog_id, get_blog_id_from_url( $details->domain, $details->path ) );
		$this->assertEquals( $blog_id, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * When deleted with the drop parameter as true, the cache will first be false, then set to
	 * -1 after an attempt at `get_blog_id_from_url()` is made.
	 */
	function test_get_blog_id_from_url_after_dropped() {
		$blog_id = self::factory()->blog->create();
		$details = get_site( $blog_id );
		$key = md5( $details->domain . $details->path );
		wpmu_delete_blog( $blog_id, true );

		$this->assertEquals( false, wp_cache_get( $key, 'blog-id-cache' ) );
		$this->assertEquals( 0, get_blog_id_from_url( $details->domain, $details->path ) );
		$this->assertEquals( -1, wp_cache_get( $key, 'blog-id-cache' ) );
	}

	/**
	 * Test with default parameter of site_id as null.
	 */
	function test_is_main_site() {
		$this->assertTrue( is_main_site() );
	}

	/**
	 * Test with a site id of get_current_blog_id(), which should be the same as the
	 * default parameter tested above.
	 */
	function test_current_blog_id_is_main_site() {
		$this->assertTrue( is_main_site( get_current_blog_id() ) );
	}

	/**
	 * Test with a site ID other than the main site to ensure a false response.
	 */
	function test_is_main_site_is_false_with_other_blog_id() {
		$blog_id = self::factory()->blog->create();

		$this->assertFalse( is_main_site( $blog_id ) );
	}

	/**
	 * Test with no passed ID after switching to another site ID.
	 */
	function test_is_main_site_is_false_after_switch_to_blog() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		$this->assertFalse( is_main_site() );

		restore_current_blog();
	}

	function test_switch_upload_dir() {
		$this->assertTrue( is_main_site() );

		$site = get_current_site();

		$info = wp_upload_dir();
		$this->assertEquals( 'http://' . $site->domain . '/wp-content/uploads/' . gmstrftime('%Y/%m'), $info['url'] );
		$this->assertEquals( ABSPATH . 'wp-content/uploads/' . gmstrftime('%Y/%m'), $info['path'] );
		$this->assertEquals( gmstrftime('/%Y/%m'), $info['subdir'] );
		$this->assertEquals( '', $info['error'] );

		$blog_id = self::factory()->blog->create();

		switch_to_blog( $blog_id );
		$info = wp_upload_dir();
		$this->assertEquals( 'http://' . $site->domain . '/wp-content/uploads/sites/' . get_current_blog_id() . '/' . gmstrftime('%Y/%m'), $info['url'] );
		$this->assertEquals( ABSPATH . 'wp-content/uploads/sites/' . get_current_blog_id() . '/' . gmstrftime('%Y/%m'), $info['path'] );
		$this->assertEquals( gmstrftime('/%Y/%m'), $info['subdir'] );
		$this->assertEquals( '', $info['error'] );
		restore_current_blog();

		$info = wp_upload_dir();
		$this->assertEquals( 'http://' . $site->domain . '/wp-content/uploads/' . gmstrftime('%Y/%m'), $info['url'] );
		$this->assertEquals( ABSPATH . 'wp-content/uploads/' . gmstrftime('%Y/%m'), $info['path'] );
		$this->assertEquals( gmstrftime('/%Y/%m'), $info['subdir'] );
		$this->assertEquals( '', $info['error'] );
	}

	/**
	 * Test the primary purpose of get_blog_post(), to retrieve a post from
	 * another site on the network.
	 */
	function test_get_blog_post_from_another_site_on_network() {
		$blog_id = self::factory()->blog->create();
		$post_id = self::factory()->post->create(); // Create a post on the primary site, ID 1.
		$post = get_post( $post_id );
		switch_to_blog( $blog_id );

		// The post created and retrieved on the main site should match the one retrieved "remotely".
		$this->assertEquals( $post, get_blog_post( 1, $post_id ) );

		restore_current_blog();
	}

	/**
	 * If get_blog_post() is used on the same site, it should still work.
	 */
	function test_get_blog_post_from_same_site() {
		$post_id = self::factory()->post->create();

		$this->assertEquals( get_blog_post( 1, $post_id ), get_post( $post_id ) );
	}

	/**
	 * A null response should be returned if an invalid post is requested.
	 */
	function test_get_blog_post_invalid_returns_null() {
		$this->assertNull( get_blog_post( 1, 999999 ) );
	}

	/**
	 * Added as a callback to the domain_exists filter to provide manual results for
	 * the testing of the filter and for a test which does not need the database.
	 */
	function _domain_exists_cb( $exists, $domain, $path, $site_id ) {
		if ( 'foo' == $domain && 'bar/' == $path )
			return 1234;
		else
			return null;
	}

	function test_domain_exists_with_default_site_id() {
		$details = get_site( 1 );

		$this->assertEquals( 1, domain_exists( $details->domain, $details->path ) );
	}

	function test_domain_exists_with_specified_site_id() {
		$details = get_site( 1 );

		$this->assertEquals( 1, domain_exists( $details->domain, $details->path, $details->site_id ) );
	}

	/**
	 * When the domain is valid, but the resulting site does not belong to the specified network,
	 * it is marked as not existing.
	 */
	function test_domain_does_not_exist_with_invalid_site_id() {
		$details = get_site( 1 );

		$this->assertEquals( null, domain_exists( $details->domain, $details->path, 999 ) );
	}

	function test_invalid_domain_does_not_exist_with_default_site_id() {
		$this->assertEquals( null, domain_exists( 'foo', 'bar' ) );
	}

	function test_domain_filtered_to_exist() {
		add_filter( 'domain_exists', array( $this, '_domain_exists_cb' ), 10, 4 );
		$exists = domain_exists( 'foo', 'bar' );
		remove_filter( 'domain_exists', array( $this, '_domain_exists_cb' ), 10, 4 );
		$this->assertEquals( 1234, $exists );
	}

	/**
	 * When a path is passed to domain_exists, it is immediately trailing slashed. A path
	 * value with or without the slash should result in the same return value.
	 */
	function test_slashed_path_in_domain_exists() {
		add_filter( 'domain_exists', array( $this, '_domain_exists_cb' ), 10, 4 );
		$exists1 = domain_exists( 'foo', 'bar' );
		$exists2 = domain_exists( 'foo', 'bar/' );
		remove_filter( 'domain_exists', array( $this, '_domain_exists_cb' ), 10, 4 );

		// Make sure the same result is returned with or without a trailing slash
		$this->assertEquals( $exists1, $exists2 );
	}

	/**
	 * Tests returning an address for a given valid id.
	 */
	function test_get_blogaddress_by_id_with_valid_id() {
		$blogaddress = get_blogaddress_by_id( 1 );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/', $blogaddress );
	}

	/**
	 * Tests returning the appropriate response for a invalid id given.
	 */
	function test_get_blogaddress_by_id_with_invalid_id() {
		$blogaddress = get_blogaddress_by_id( 42 );
		$this->assertEquals( '', $blogaddress );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/14867
	 */
	function test_get_blogaddress_by_id_scheme_reflects_blog_scheme() {
		$blog = self::factory()->blog->create();

		$this->assertSame( 'http', parse_url( get_blogaddress_by_id( $blog ), PHP_URL_SCHEME ) );

		update_blog_option( $blog, 'home', set_url_scheme( get_blog_option( $blog, 'home' ), 'https' ) );

		$this->assertSame( 'https', parse_url( get_blogaddress_by_id( $blog ), PHP_URL_SCHEME ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/14867
	 */
	function test_get_blogaddress_by_id_scheme_is_unaffected_by_request() {
		$blog = self::factory()->blog->create();

		$this->assertFalse( is_ssl() );
		$this->assertSame( 'http', parse_url( get_blogaddress_by_id( $blog ), PHP_URL_SCHEME ) );

		$_SERVER['HTTPS'] = 'on';

		$is_ssl  = is_ssl();
		$address = parse_url( get_blogaddress_by_id( $blog ), PHP_URL_SCHEME );

		$this->assertTrue( $is_ssl );
		$this->assertSame( 'http', $address );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/33620
	 * @dataProvider data_new_blog_url_schemes
	 */
	function test_new_blog_url_schemes( $home_scheme, $siteurl_scheme, $force_ssl_admin ) {
		$current_site = get_current_site();

		$home    = get_option( 'home' );
		$siteurl = get_site_option( 'siteurl' );
		$admin   = force_ssl_admin();

		// Setup:
		update_option( 'home', set_url_scheme( $home, $home_scheme ) );
		update_site_option( 'siteurl', set_url_scheme( $siteurl, $siteurl_scheme ) );
		force_ssl_admin( $force_ssl_admin );

		// Install:
		$new = wpmu_create_blog( $current_site->domain, '/new-blog/', 'New Blog', get_current_user_id() );

		// Reset:
		update_option( 'home', $home );
		update_site_option( 'siteurl', $siteurl );
		force_ssl_admin( $admin );

		// Assert:
		$this->assertNotWPError( $new );
		$this->assertSame( $home_scheme, parse_url( get_blog_option( $new, 'home' ), PHP_URL_SCHEME ) );
		$this->assertSame( $siteurl_scheme, parse_url( get_blog_option( $new, 'siteurl' ), PHP_URL_SCHEME ) );
	}

	function data_new_blog_url_schemes() {
		return array(
			array(
				'https',
				'https',
				false,
			),
			array(
				'http',
				'https',
				false,
			),
			array(
				'https',
				'http',
				false,
			),
			array(
				'http',
				'http',
				false,
			),
			array(
				'http',
				'http',
				true,
			),
		);
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/36918
	 */
	function test_new_blog_locale() {
		$current_site = get_current_site();

		add_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10, 3 );
		update_site_option( 'WPLANG', 'de_DE' );
		remove_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10 );

		// No locale, use default locale.
		add_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10, 3 );
		$blog_id = wpmu_create_blog( $current_site->domain, '/de-de/', 'New Blog', get_current_user_id() );
		remove_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10 );

		$this->assertNotWPError( $blog_id );
		$this->assertSame( 'de_DE', get_blog_option( $blog_id, 'WPLANG' ) );

		// Custom locale.
		add_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10, 3 );
		$blog_id = wpmu_create_blog( $current_site->domain, '/es-es/', 'New Blog', get_current_user_id(), array( 'WPLANG' => 'es_ES' ) );
		remove_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10 );

		$this->assertNotWPError( $blog_id );
		$this->assertSame( 'es_ES', get_blog_option( $blog_id, 'WPLANG' ) );

		// en_US locale.
		add_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10, 3 );
		$blog_id = wpmu_create_blog( $current_site->domain, '/en-us/', 'New Blog', get_current_user_id(), array( 'WPLANG' => '' ) );
		remove_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10 );

		$this->assertNotWPError( $blog_id );
		$this->assertSame( '', get_blog_option( $blog_id, 'WPLANG' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40503
	 */
	function test_different_network_language() {
		$network = get_network( self::$network_ids['make.wordpress.org/'] );

		add_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10, 3 );

		update_network_option( self::$network_ids['make.wordpress.org/'], 'WPLANG', 'wibble' );
		$blog_id = wpmu_create_blog( $network->domain, '/de-de/', 'New Blog', get_current_user_id(), array(), $network->id );

		remove_filter( 'sanitize_option_WPLANG', array( $this, 'filter_allow_unavailable_languages' ), 10 );

		$this->assertSame( get_network_option( self::$network_ids['make.wordpress.org/'], 'WPLANG' ), get_blog_option( $blog_id, 'WPLANG' ) );
	}

	/**
	 * Allows to set the WPLANG option to any language.
	 *
	 * @param string $value          The sanitized option value.
	 * @param string $option         The option name.
	 * @param string $original_value The original value passed to the function.
	 * @return string The orginal value.
	 */
	function filter_allow_unavailable_languages( $value, $option, $original_value ) {
		return $original_value;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/29684
	 */
	public function test_is_main_site_different_network() {
		$this->assertTrue( is_main_site( self::$site_ids['make.wordpress.org/'], self::$network_ids['make.wordpress.org/'] ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/29684
	 */
	public function test_is_main_site_different_network_random_site() {
		$this->assertFalse( is_main_site( self::$site_ids['make.wordpress.org/foo/'], self::$network_ids['make.wordpress.org/'] ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 * @dataProvider data_get_site_caches
	 */
	public function test_clean_blog_cache( $key, $group ) {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		$replacements = array(
			'%blog_id%'         => $site->blog_id,
			'%domain%'          => $site->domain,
			'%path%'            => $site->path,
			'%domain_path_key%' => md5( $site->domain . $site->path ),
		);

		$key = str_replace( array_keys( $replacements ), array_values( $replacements ), $key );

		if ( 'sites' === $group ) { // This needs to be actual data for get_site() lookups.
			wp_cache_set( $key, (object) $site->to_array(), $group );
		} else {
			wp_cache_set( $key, 'something', $group );
		}

		clean_blog_cache( $site );
		$this->assertFalse( wp_cache_get( $key, $group ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 * @dataProvider data_get_site_caches
	 */
	public function test_clean_blog_cache_with_id( $key, $group ) {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		$replacements = array(
			'%blog_id%'         => $site->blog_id,
			'%domain%'          => $site->domain,
			'%path%'            => $site->path,
			'%domain_path_key%' => md5( $site->domain . $site->path ),
		);

		$key = str_replace( array_keys( $replacements ), array_values( $replacements ), $key );

		if ( 'sites' === $group ) { // This needs to be actual data for get_site() lookups.
			wp_cache_set( $key, (object) $site->to_array(), $group );
		} else {
			wp_cache_set( $key, 'something', $group );
		}

		clean_blog_cache( $site->blog_id );
		$this->assertFalse( wp_cache_get( $key, $group ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_resets_last_changed() {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		wp_cache_delete( 'last_changed', 'sites' );

		clean_blog_cache( $site );
		$this->assertNotFalse( wp_cache_get( 'last_changed', 'sites' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_fires_action() {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		$old_count = did_action( 'clean_site_cache' );

		clean_blog_cache( $site );
		$this->assertEquals( $old_count + 1, did_action( 'clean_site_cache' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_bails_on_suspend_cache_invalidation() {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		$old_count = did_action( 'clean_site_cache' );

		$suspend = wp_suspend_cache_invalidation();
		clean_blog_cache( $site );
		wp_suspend_cache_invalidation( $suspend );
		$this->assertEquals( $old_count, did_action( 'clean_site_cache' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_bails_on_empty_input() {
		$old_count = did_action( 'clean_site_cache' );

		clean_blog_cache( null );
		$this->assertEquals( $old_count, did_action( 'clean_site_cache' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_bails_on_non_numeric_input() {
		$old_count = did_action( 'clean_site_cache' );

		clean_blog_cache( 'something' );
		$this->assertEquals( $old_count, did_action( 'clean_site_cache' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_clean_blog_cache_works_with_deleted_site() {
		$site_id = 12345;

		wp_cache_set( $site_id, 'something', 'site-details' );

		clean_blog_cache( $site_id );
		$this->assertFalse( wp_cache_get( $site_id, 'site-details' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 * @dataProvider data_get_site_caches
	 */
	public function test_refresh_blog_details( $key, $group ) {
		$site = get_site( self::$site_ids['make.wordpress.org/'] );

		$replacements = array(
			'%blog_id%'         => $site->blog_id,
			'%domain%'          => $site->domain,
			'%path%'            => $site->path,
			'%domain_path_key%' => md5( $site->domain . $site->path ),
		);

		$key = str_replace( array_keys( $replacements ), array_values( $replacements ), $key );

		if ( 'sites' === $group ) { // This needs to be actual data for get_site() lookups.
			wp_cache_set( $key, (object) $site->to_array(), $group );
		} else {
			wp_cache_set( $key, 'something', $group );
		}

		refresh_blog_details( $site->blog_id );
		$this->assertFalse( wp_cache_get( $key, $group ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_refresh_blog_details_works_with_deleted_site() {
		$site_id = 12345;

		wp_cache_set( $site_id, 'something', 'site-details' );

		refresh_blog_details( $site_id );
		$this->assertFalse( wp_cache_get( $site_id, 'site-details' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40201
	 */
	public function test_refresh_blog_details_uses_current_site_as_default() {
		$site_id = get_current_blog_id();

		wp_cache_set( $site_id, 'something', 'site-details' );

		refresh_blog_details();
		$this->assertFalse( wp_cache_get( $site_id, 'site-details' ) );
	}

	public function data_get_site_caches() {
		return array(
			array( '%blog_id%', 'sites' ),
			array( '%blog_id%', 'site-details' ),
			array( '%blog_id%', 'blog-details' ),
			array( '%blog_id%' . 'short' , 'blog-details' ),
			array( '%domain_path_key%', 'blog-lookup' ),
			array( '%domain_path_key%', 'blog-id-cache' ),
			array( 'current_blog_%domain%', 'site-options' ),
			array( 'current_blog_%domain%%path%', 'site-options' ),
		);
	}
}

endif;
