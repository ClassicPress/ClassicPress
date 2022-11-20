<?php

/**
 * @group post
 */
class Tests_Post_GetPostsByAuthorSql extends WP_UnitTestCase {

	public function test_post_type_post() {
		$maybe_string = get_posts_by_author_sql( 'post' );
		$this->assertStringContainsString( "post_type = 'post'", $maybe_string );
	}

	public function test_post_type_page() {
		$maybe_string = get_posts_by_author_sql( 'page' );
		$this->assertStringContainsString( "post_type = 'page'", $maybe_string );
	}

	public function test_non_existent_post_type() {
		$maybe_string = get_posts_by_author_sql( 'non_existent_post_type' );
		$this->assertStringContainsString( '1 = 0', $maybe_string );
	}

	public function test_multiple_post_types() {
		register_post_type( 'foo' );
		register_post_type( 'bar' );

		$maybe_string = get_posts_by_author_sql( array( 'foo', 'bar' ) );
		$this->assertStringContainsString( "post_type = 'foo'", $maybe_string );
		$this->assertStringContainsString( "post_type = 'bar'", $maybe_string );

		_unregister_post_type( 'foo' );
		_unregister_post_type( 'bar' );
	}

	public function test_full_true() {
		$maybe_string = get_posts_by_author_sql( 'post', true );
		$this->assertMatchesRegularExpression( '/^WHERE /', $maybe_string );
	}

	public function test_full_false() {
		$maybe_string = get_posts_by_author_sql( 'post', false );
		$this->assertDoesNotMatchRegularExpression( '/^WHERE /', $maybe_string );
	}

	public function test_post_type_clause_should_be_included_when_full_is_true() {
		$maybe_string = get_posts_by_author_sql( 'post', true );
		$this->assertStringContainsString( "post_type = 'post'", $maybe_string );
	}

	public function test_post_type_clause_should_be_included_when_full_is_false() {
		$maybe_string = get_posts_by_author_sql( 'post', false );
		$this->assertStringContainsString( "post_type = 'post'", $maybe_string );
	}

	public function test_post_author_should_create_post_author_clause() {
		$maybe_string = get_posts_by_author_sql( 'post', true, 1 );
		$this->assertStringContainsString( 'post_author = 1', $maybe_string );
	}

	public function test_public_only_true_should_not_allow_any_private_posts_for_loggedin_user() {
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create();
		wp_set_current_user( $u );

		$maybe_string = get_posts_by_author_sql( 'post', true, $u, true );
		$this->assertStringNotContainsString( "post_status = 'private'", $maybe_string );

		wp_set_current_user( $current_user );
	}

	public function test_public_only_should_default_to_false() {
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create();
		wp_set_current_user( $u );

		$this->assertSame( get_posts_by_author_sql( 'post', true, $u, false ), get_posts_by_author_sql( 'post', true, $u ) );

		wp_set_current_user( $current_user );
	}

	public function test_public_only_false_should_allow_current_user_access_to_own_private_posts_when_current_user_matches_post_author() {
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create();
		wp_set_current_user( $u );

		$maybe_string = get_posts_by_author_sql( 'post', true, $u, false );
		$this->assertStringContainsString( "post_status = 'private'", $maybe_string );

		wp_set_current_user( $current_user );
	}

	public function test_public_only_false_should_not_allow_access_to_private_posts_if_current_user_is_not_post_author() {
		$current_user = get_current_user_id();
		$u1           = self::factory()->user->create();
		$u2           = self::factory()->user->create();
		wp_set_current_user( $u1 );

		$maybe_string = get_posts_by_author_sql( 'post', true, $u2, false );
		$this->assertStringNotContainsString( "post_status = 'private'", $maybe_string );

		wp_set_current_user( $current_user );
	}

	public function test_public_only_false_should_allow_current_user_access_to_own_private_posts_when_post_author_is_not_provided() {
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create();
		wp_set_current_user( $u );

		$maybe_string = get_posts_by_author_sql( 'post', true, $u, false );
		$this->assertStringContainsString( "post_status = 'private'", $maybe_string );
		$this->assertStringContainsString( "post_author = $u", $maybe_string );

		wp_set_current_user( $current_user );
	}

	public function test_administrator_should_have_access_to_private_posts_when_public_only_is_false() {
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $u );

		$maybe_string = get_posts_by_author_sql( 'post', true, null, false );
		$this->assertStringContainsString( "post_status = 'private'", $maybe_string );
		$this->assertStringNotContainsString( 'post_author', $maybe_string );

		wp_set_current_user( $current_user );
	}

	public function test_user_has_access_only_to_private_posts_for_certain_post_types() {
		register_post_type( 'foo', array( 'capabilities' => array( 'read_private_posts' => 'read_private_foo' ) ) );
		register_post_type( 'bar', array( 'capabilities' => array( 'read_private_posts' => 'read_private_bar' ) ) );
		register_post_type( 'baz', array( 'capabilities' => array( 'read_private_posts' => 'read_private_baz' ) ) );
		$current_user = get_current_user_id();
		$u            = self::factory()->user->create( array( 'role' => 'editor' ) );
		$editor_role  = get_role( 'editor' );
		$editor_role->add_cap( 'read_private_baz' );
		wp_set_current_user( $u );

		$maybe_string = get_posts_by_author_sql( array( 'foo', 'bar', 'baz' ) );

		$editor_role->remove_cap( 'read_private_baz' );

		$this->assertStringNotContainsString( "post_type = 'foo' AND ( post_status = 'publish' OR post_status = 'private' )", $maybe_string );
		$this->assertStringNotContainsString( "post_type = 'bar' AND ( post_status = 'publish' OR post_status = 'private' )", $maybe_string );
		$this->assertStringContainsString( "post_type = 'baz' AND ( post_status = 'publish' OR post_status = 'private' )", $maybe_string );

		_unregister_post_type( 'foo' );
		_unregister_post_type( 'bar' );
		_unregister_post_type( 'baz' );
		wp_set_current_user( $current_user );
	}
}
