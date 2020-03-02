<?php

/**
 * @group user
 * @group post
 */
class Tests_User_CountUserPosts extends WP_UnitTestCase {
	static $user_id;
	static $post_ids = array();

	public static function wpSetUpBeforeClass( $factory ) {
		self::$user_id = $factory->user->create( array(
			'role' => 'author',
			'user_login' => 'count_user_posts_user',
			'user_email' => 'count_user_posts_user@example.com',
		) );

		self::$post_ids = $factory->post->create_many( 4, array(
			'post_author' => self::$user_id,
			'post_type'   => 'post',
		) );
		self::$post_ids = array_merge( self::$post_ids, $factory->post->create_many( 3, array(
			'post_author' => self::$user_id,
			'post_type'   => 'wptests_pt',
		) ) );
		self::$post_ids = array_merge( self::$post_ids, $factory->post->create_many( 2, array(
			'post_author' => 12345,
			'post_type'   => 'wptests_pt',
		) ) );

		self::$post_ids[] = $factory->post->create( array(
			'post_author' => 12345,
			'post_type'   => 'wptests_pt',
		) );
	}

	public function setUp() {
		parent::setUp();
		register_post_type( 'wptests_pt' );
	}

	public function tearDown() {
		_unregister_post_type( 'wptests_pt' );
		parent::tearDown();
	}

	public function test_count_user_posts_post_type_should_default_to_post() {
		$this->assertEquals( 4, count_user_posts( self::$user_id ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/21364
	 */
	public function test_count_user_posts_post_type_post() {
		$this->assertEquals( 4, count_user_posts( self::$user_id, 'post' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/21364
	 */
	public function test_count_user_posts_post_type_cpt() {
		$this->assertEquals( 3, count_user_posts( self::$user_id, 'wptests_pt' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/32243
	 */
	public function test_count_user_posts_with_multiple_post_types() {
		$this->assertEquals( 7, count_user_posts( self::$user_id, array( 'wptests_pt', 'post' ) ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/32243
	 */
	public function test_count_user_posts_should_ignore_non_existent_post_types() {
		$this->assertEquals( 4, count_user_posts( self::$user_id, array( 'foo', 'post' ) ) );
	}
}
