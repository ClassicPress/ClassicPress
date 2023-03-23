<?php

/**
 * @group link
 * @covers ::get_dashboard_url
 */
class Tests_Link_GetDashboardUrl extends WP_UnitTestCase {
	public static $user_id = false;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	public static function wpTearDownAfterClass() {
		if ( is_multisite() ) {
			wpmu_delete_user( self::$user_id );
		} else {
			wp_delete_user( self::$user_id );
		}
	}

	/**
	 * @ticket 39065
	 */
	public function test_get_dashboard_url_for_current_site_user() {
		$this->assertSame( admin_url(), get_dashboard_url( self::$user_id ) );
	}

	/**
	 * @ticket 39065
	 */
	public function test_get_dashboard_url_for_user_with_no_sites() {
		add_filter( 'get_blogs_of_user', '__return_empty_array' );

		$expected = is_multisite() ? user_admin_url() : admin_url();

		$this->assertSame( $expected, get_dashboard_url( self::$user_id ) );
	}

	/**
	 * @ticket 39065
	 * @group ms-required
	 */
	public function test_get_dashboard_url_for_network_administrator_with_no_sites() {
		grant_super_admin( self::$user_id );

		add_filter( 'get_blogs_of_user', '__return_empty_array' );

		$expected = admin_url();
		$result   = get_dashboard_url( self::$user_id );

		revoke_super_admin( self::$user_id );

		$this->assertSame( $expected, $result );
	}

	/**
	 * @ticket 39065
	 * @group ms-required
	 */
	public function test_get_dashboard_url_for_administrator_of_different_site() {
		$site_id = self::factory()->blog->create( array( 'user_id' => self::$user_id ) );

		remove_user_from_blog( self::$user_id, get_current_blog_id() );

		$expected = get_admin_url( $site_id );
		$result   = get_dashboard_url( self::$user_id );

		remove_user_from_blog( self::$user_id, $site_id );
		add_user_to_blog( get_current_blog_id(), self::$user_id, 'administrator' );

		wp_delete_site( $site_id );

		$this->assertSame( $expected, $result );
	}
}
