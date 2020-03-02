<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_mw_newPost extends WP_XMLRPC_UnitTestCase {

	function test_invalid_username_password() {
		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'username', 'password', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 403, $result->code );
	}

	function test_incapable_user() {
		$this->make_user_by_role( 'subscriber' );

		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'subscriber', 'subscriber', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_no_content() {
		$this->make_user_by_role( 'author' );

		$post = array();
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 500, $result->code );
		$this->assertEquals( 'Content, title, and excerpt are empty.', $result->message );
	}

	function test_basic_content() {
		$this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertStringMatchesFormat( '%d', $result );
	}

	function test_ignore_id() {
		$this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test', 'ID' => 103948 );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertNotEquals( '103948', $result );
	}

	function test_capable_publish() {
		$this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test', 'post_status' => 'publish' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotIXRError( $result );
	}

	function test_incapable_publish() {
		$this->make_user_by_role( 'contributor' );

		$post = array( 'title' => 'Test', 'post_status' => 'publish' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'contributor', 'contributor', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_capable_other_author() {
		$this->make_user_by_role( 'editor' );
		$other_author_id = $this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test', 'wp_author_id' => $other_author_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertNotIXRError( $result );
	}

	function test_incapable_other_author() {
		$this->make_user_by_role( 'contributor' );
		$other_author_id = $this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test', 'wp_author_id' => $other_author_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'contributor', 'contributor', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 401, $result->code );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/20356
	 */
	function test_invalid_author() {
		$this->make_user_by_role( 'editor' );

		$post = array( 'title' => 'Test', 'wp_author_id' => 99999999 );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 404, $result->code );
	}

	function test_empty_author() {
		$my_author_id = $this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$out = get_post( $result );
		$this->assertEquals( $my_author_id, $out->post_author );
		$this->assertEquals( 'Test', $out->post_title );
	}

	function test_post_thumbnail() {
		add_theme_support( 'post-thumbnails' );

		$this->make_user_by_role( 'author' );

		// create attachment
		$filename = ( DIR_TESTDATA.'/images/a2-small.jpg' );
		$attachment_id = self::factory()->attachment->create_upload_object( $filename );

		$post = array( 'title' => 'Post Thumbnail Test', 'wp_post_thumbnail' => $attachment_id );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertEquals( $attachment_id, get_post_meta( $result, '_thumbnail_id', true ) );

		remove_theme_support( 'post-thumbnails' );
	}

	function test_incapable_set_post_type_as_page() {
		$this->make_user_by_role( 'author' );

		$post = array( 'title' => 'Test', 'post_type' => 'page' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'author', 'author', $post ) );
		$this->assertIXRError( $result );
		$this->assertEquals( 401, $result->code );
	}

	function test_capable_set_post_type_as_page() {
		$this->make_user_by_role( 'editor' );

		$post = array( 'title' => 'Test', 'post_type' => 'page' );
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$out = get_post( $result );
		$this->assertEquals( 'Test', $out->post_title );
		$this->assertEquals( 'page', $out->post_type );
	}


	/**
	 * @see https://core.trac.wordpress.org/ticket/16985
	 */
	function test_draft_post_date() {
		$this->make_user_by_role( 'editor' );

		$post = array(
			'title' => 'Test',
			'post_type' => 'post',
			'post_status' => 'draft'
		);
		$result = $this->myxmlrpcserver->mw_newPost( array( 1, 'editor', 'editor', $post ) );
		$this->assertNotIXRError( $result );
		$this->assertStringMatchesFormat( '%d', $result );

		$out = get_post( $result );
		$this->assertEquals( 'post', $out->post_type );
		$this->assertEquals( 'draft', $out->post_status );
		$this->assertEquals( '0000-00-00 00:00:00', $out->post_date_gmt );
	}
}
