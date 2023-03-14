<?php

/**
 * Admin Ajax functions to be tested.
 */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

/**
 * Testing Ajax comment functionality.
 *
 * @package WordPress
 * @subpackage UnitTests
 * @since 3.4.0
 *
 * @group ajax
 *
 * @covers ::wp_ajax_get_comments
 */
class Tests_Ajax_wpAjaxGetComments extends WP_Ajax_UnitTestCase {

	/**
	 * A post with at least one comment.
	 *
	 * @var mixed
	 */
	protected static $comment_post = null;

	/**
	 * A post with no comments.
	 *
	 * @var mixed
	 */
	protected static $no_comment_post = null;

	protected static $comment_ids = array();

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$comment_post    = $factory->post->create_and_get();
		self::$comment_ids     = $factory->comment->create_post_comments( self::$comment_post->ID, 5 );
		self::$no_comment_post = $factory->post->create_and_get();
	}

	/**
	 * Gets comments as a privileged user (administrator).
	 *
	 * Expects test to pass.
	 */
	public function test_as_admin() {

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['_ajax_nonce'] = wp_create_nonce( 'get-comments' );
		$_POST['action']      = 'get-comments';
		$_POST['p']           = self::$comment_post->ID;

		// Make the request.
		try {
			$this->_handleAjax( 'get-comments' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response.
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Check the meta data.
		$this->assertSame( '1', (string) $xml->response[0]->comments['position'] );
		$this->assertSame( '0', (string) $xml->response[0]->comments['id'] );
		$this->assertSame( 'get-comments_0', (string) $xml->response['action'] );

		// Check the payload.
		$this->assertNotEmpty( (string) $xml->response[0]->comments[0]->response_data );

		// And supplemental is empty.
		$this->assertEmpty( (string) $xml->response[0]->comments[0]->supplemental );
	}

	/**
	 * Gets comments as a non-privileged user (subscriber).
	 *
	 * Expects test to fail.
	 */
	public function test_as_subscriber() {

		// Become a subscriber.
		$this->_setRole( 'subscriber' );

		// Set up a default request.
		$_POST['_ajax_nonce'] = wp_create_nonce( 'get-comments' );
		$_POST['action']      = 'get-comments';
		$_POST['p']           = self::$comment_post->ID;

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'get-comments' );
	}

	/**
	 * Gets comments with a bad nonce.
	 *
	 * Expects test to fail.
	 */
	public function test_bad_nonce() {

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['_ajax_nonce'] = wp_create_nonce( uniqid() );
		$_POST['action']      = 'get-comments';
		$_POST['p']           = self::$comment_post->ID;

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'get-comments' );
	}

	/**
	 * Gets comments for an invalid post.
	 *
	 * Bad post IDs are set to 0, this should return valid XML.
	 */
	public function test_invalid_post() {

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['_ajax_nonce'] = wp_create_nonce( 'get-comments' );
		$_POST['action']      = 'get-comments';
		$_POST['p']           = 'b0rk';

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'get-comments' );
	}

	/**
	 * Gets comments for a post with no comments.
	 */
	public function test_post_with_no_comments() {

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['_ajax_nonce'] = wp_create_nonce( 'get-comments' );
		$_POST['action']      = 'get-comments';
		$_POST['p']           = self::$no_comment_post->ID;

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '1' );
		$this->_handleAjax( 'get-comments' );
	}
}
