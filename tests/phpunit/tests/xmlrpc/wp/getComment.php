<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_wp_getComment extends WP_XMLRPC_UnitTestCase {
	protected static $post_id;
	protected static $parent_comment_id;
	protected static $parent_comment_data;
	protected static $child_comment_id;
	protected static $child_comment_data;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$post_id = $factory->post->create();

		self::$parent_comment_data = array(
			'comment_post_ID'      => self::$post_id,
			'comment_author'       => 'Test commenter',
			'comment_author_url'   => 'http://example.com/',
			'comment_author_email' => 'example@example.com',
			'comment_content'      => rand_str( 100 ),
		);
		self::$parent_comment_id   = wp_insert_comment( self::$parent_comment_data );

		self::$child_comment_data = array(
			'comment_post_ID'      => self::$post_id,
			'comment_author'       => 'Test commenter 2',
			'comment_author_url'   => 'http://example.org/',
			'comment_author_email' => 'example@example.org',
			'comment_parent'       => self::$parent_comment_id,
			'comment_content'      => rand_str( 100 ),
		);
		self::$child_comment_id   = wp_insert_comment( self::$child_comment_data );
	}

	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->wp_getComment( array( 1, 'username', 'password', self::$parent_comment_id ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_incapable_user() {
		$this->make_user_by_role( 'contributor' );

		$result = $this->myxmlrpcserver->wp_getComment( array( 1, 'contributor', 'contributor', self::$parent_comment_id ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_valid_comment() {
		$this->make_user_by_role( 'editor' );

		$result = $this->myxmlrpcserver->wp_getComment( array( 1, 'editor', 'editor', self::$parent_comment_id ) );
		$this->assertNotIXRError( $result );

		// Check data types
		$this->assertInternalType( 'string', $result['user_id'] );
		$this->assertInternalType( 'string', $result['comment_id'] );
		$this->assertInstanceOf( 'IXR_Date', $result['date_created_gmt'] );
		$this->assertInternalType( 'string', $result['parent'] );
		$this->assertInternalType( 'string', $result['status'] );
		$this->assertInternalType( 'string', $result['content'] );
		$this->assertInternalType( 'string', $result['link'] );
		$this->assertInternalType( 'string', $result['post_id'] );
		$this->assertInternalType( 'string', $result['post_title'] );
		$this->assertInternalType( 'string', $result['author'] );
		$this->assertInternalType( 'string', $result['author_url'] );
		$this->assertInternalType( 'string', $result['author_email'] );
		$this->assertInternalType( 'string', $result['author_ip'] );
		$this->assertInternalType( 'string', $result['type'] );

		// Check expected values
		$this->assertStringMatchesFormat( '%d', $result['user_id'] );
		$this->assertStringMatchesFormat( '%d', $result['comment_id'] );
		$this->assertStringMatchesFormat( '%d', $result['parent'] );
		$this->assertStringMatchesFormat( '%d', $result['post_id'] );
		$this->assertEquals( self::$parent_comment_id, $result['comment_id'] );
		$this->assertEquals( 0, $result['parent'] );
		$this->assertEquals( self::$parent_comment_data['comment_content'], $result['content'] );
		$this->assertEquals( self::$post_id, $result['post_id'] );
		$this->assertEquals( self::$parent_comment_data['comment_author'], $result['author'] );
		$this->assertEquals( self::$parent_comment_data['comment_author_url'], $result['author_url'] );
		$this->assertEquals( self::$parent_comment_data['comment_author_email'], $result['author_email'] );
	}

	function test_valid_child_comment() {
		$this->make_user_by_role( 'editor' );

		$result = $this->myxmlrpcserver->wp_getComment( array( 1, 'editor', 'editor', self::$child_comment_id ) );
		$this->assertNotIXRError( $result );

		$this->assertEquals( self::$child_comment_id, $result['comment_id'] );
		$this->assertEquals( self::$parent_comment_id, $result['parent'] );
	}

	function test_invalid_id() {
		$this->make_user_by_role( 'editor' );

		$result = $this->myxmlrpcserver->wp_getComment( array( 1, 'editor', 'editor', 123456789 ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 404, $result->code );
	}
}
