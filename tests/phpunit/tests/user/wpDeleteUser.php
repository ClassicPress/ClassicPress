<?php

/**
 * @group user
 */
class Tests_User_wpDeleteUser extends WP_UnitTestCase {

	/**
	 * Test that usermeta cache is cleared after user deletion.
	 *
	 * @ticket 19500
	 */
	public function test_get_blogs_of_user() {
		// Logged out users don't have blogs.
		$this->assertSame( array(), get_blogs_of_user( 0 ) );

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$blogs   = get_blogs_of_user( $user_id );
		$this->assertSame( array( 1 ), array_keys( $blogs ) );

		// Non-existent users don't have blogs.
		self::delete_user( $user_id );

		$user = new WP_User( $user_id );
		$this->assertFalse( $user->exists(), 'WP_User->exists' );
		$this->assertSame( array(), get_blogs_of_user( $user_id ) );
	}

	/**
	 * Test that usermeta cache is cleared after user deletion.
	 *
	 * @ticket 19500
	 */
	public function test_is_user_member_of_blog() {
		$old_current = get_current_user_id();

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->assertTrue( is_user_member_of_blog() );
		$this->assertTrue( is_user_member_of_blog( 0, 0 ) );
		$this->assertTrue( is_user_member_of_blog( 0, get_current_blog_id() ) );
		$this->assertTrue( is_user_member_of_blog( $user_id ) );
		$this->assertTrue( is_user_member_of_blog( $user_id, get_current_blog_id() ) );

		// Will only remove the user from the current site in multisite; this is desired
		// and will achieve the desired effect with is_user_member_of_blog().
		wp_delete_user( $user_id );

		$this->assertFalse( is_user_member_of_blog( $user_id ) );
		$this->assertFalse( is_user_member_of_blog( $user_id, get_current_blog_id() ) );

		wp_set_current_user( $old_current );
	}

	public function test_delete_user() {
		$user_id = self::factory()->user->create( array( 'role' => 'author' ) );
		$user    = new WP_User( $user_id );

		$post = array(
			'post_author'  => $user_id,
			'post_status'  => 'publish',
			'post_content' => 'Post content',
			'post_title'   => 'Post Title',
			'post_type'    => 'post',
		);

		// Insert a post and make sure the ID is OK.
		$post_id = wp_insert_post( $post );
		$this->assertIsNumeric( $post_id );
		$this->assertGreaterThan( 0, $post_id );

		$post = get_post( $post_id );
		$this->assertSame( $post_id, $post->ID );

		$post = array(
			'post_author'  => $user_id,
			'post_status'  => 'publish',
			'post_content' => 'Post content',
			'post_title'   => 'Post Title',
			'post_type'    => 'nav_menu_item',
		);

		// Insert a post and make sure the ID is OK.
		$nav_id = wp_insert_post( $post );
		$this->assertIsNumeric( $nav_id );
		$this->assertGreaterThan( 0, $nav_id );

		$post = get_post( $nav_id );
		$this->assertSame( $nav_id, $post->ID );

		wp_delete_user( $user_id );
		$user = new WP_User( $user_id );
		if ( is_multisite() ) {
			$this->assertTrue( $user->exists() );
		} else {
			$this->assertFalse( $user->exists() );
		}

		$this->assertNotNull( get_post( $post_id ) );
		$this->assertSame( 'trash', get_post( $post_id )->post_status );
		// 'nav_menu_item' is `delete_with_user = false` so the nav post should remain published.
		$this->assertNotNull( get_post( $nav_id ) );
		$this->assertSame( 'publish', get_post( $nav_id )->post_status );
		wp_delete_post( $nav_id, true );
		$this->assertNull( get_post( $nav_id ) );
		wp_delete_post( $post_id, true );
		$this->assertNull( get_post( $post_id ) );
	}

	/**
	 * @ticket 20447
	 */
	public function test_wp_delete_user_reassignment_clears_post_caches() {
		$user_id  = self::factory()->user->create();
		$reassign = self::factory()->user->create();
		$post_id  = self::factory()->post->create( array( 'post_author' => $user_id ) );

		get_post( $post_id ); // Ensure this post is in the cache.

		wp_delete_user( $user_id, $reassign );

		$post = get_post( $post_id );
		$this->assertEquals( $reassign, $post->post_author );
	}

	/**
	 * @group ms-excluded
	 */
	public function test_numeric_string_user_id() {
		$u = self::factory()->user->create();

		$u_string = (string) $u;
		$this->assertTrue( wp_delete_user( $u_string ) );
		$this->assertFalse( get_user_by( 'id', $u ) );
	}

	/**
	 * @ticket 33800
	 */
	public function test_should_return_false_for_non_numeric_string_user_id() {
		$this->assertFalse( wp_delete_user( 'abcde' ) );
	}

	/**
	 * @ticket 33800
	 * @group ms-excluded
	 */
	public function test_should_return_false_for_object_user_id() {
		$u_obj = self::factory()->user->create_and_get();
		$this->assertFalse( wp_delete_user( $u_obj ) );
		$this->assertSame( $u_obj->ID, username_exists( $u_obj->user_login ) );
	}
}
