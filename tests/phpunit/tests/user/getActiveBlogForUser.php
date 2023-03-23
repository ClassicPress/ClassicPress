<?php

if ( is_multisite() ) :

	/**
	 * Tests specific to users in multisite.
	 *
	 * @group user
	 * @group ms-user
	 * @group multisite
	 */
	class Tests_User_GetActiveBlogForUser extends WP_UnitTestCase {
		public static $user_id = false;

		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			self::$user_id = $factory->user->create();
		}

		public static function wpTearDownAfterClass() {
			wpmu_delete_user( self::$user_id );

			global $wp_rewrite;
			$wp_rewrite->init();
		}

		/**
		 * @ticket 38355
		 */
		public function test_get_active_blog_for_user_with_no_sites() {
			$current_site_id = get_current_blog_id();

			remove_user_from_blog( self::$user_id, $current_site_id );

			$result = get_active_blog_for_user( self::$user_id );

			$this->assertNull( $result );
		}

		/**
		 * @ticket 38355
		 */
		public function test_get_active_blog_for_user_with_primary_site() {
			$site_id_one = self::factory()->blog->create( array( 'user_id' => self::$user_id ) );
			$site_id_two = self::factory()->blog->create( array( 'user_id' => self::$user_id ) );

			$sites           = get_blogs_of_user( self::$user_id );
			$site_ids        = array_keys( $sites );
			$primary_site_id = $site_ids[1];

			update_user_meta( self::$user_id, 'primary_blog', $primary_site_id );

			$result = get_active_blog_for_user( self::$user_id );

			wp_delete_site( $site_id_one );
			wp_delete_site( $site_id_two );

			$this->assertSame( $primary_site_id, $result->id );
		}

		/**
		 * @ticket 38355
		 */
		public function test_get_active_blog_for_user_without_primary_site() {
			$sites           = get_blogs_of_user( self::$user_id );
			$site_ids        = array_keys( $sites );
			$primary_site_id = $site_ids[0];

			delete_user_meta( self::$user_id, 'primary_blog' );

			$result = get_active_blog_for_user( self::$user_id );

			wp_delete_site( $primary_site_id );

			$this->assertSame( $primary_site_id, $result->id );
		}

		/**
		 * @ticket 38355
		 */
		public function test_get_active_blog_for_user_with_spam_site() {
			$current_site_id = get_current_blog_id();

			$site_id = self::factory()->blog->create(
				array(
					'user_id' => self::$user_id,
					'spam'    => 1,
				)
			);

			add_user_to_blog( $site_id, self::$user_id, 'subscriber' );
			update_user_meta( self::$user_id, 'primary_blog', $site_id );

			$result = get_active_blog_for_user( self::$user_id );

			wp_delete_site( $site_id );

			$this->assertSame( $current_site_id, $result->id );
		}
	}

endif;
