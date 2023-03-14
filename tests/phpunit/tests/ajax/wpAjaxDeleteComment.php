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
 * @covers ::wp_ajax_delete_comment
 */
class Tests_Ajax_wpAjaxDeleteComment extends WP_Ajax_UnitTestCase {

	/**
	 * List of comments.
	 *
	 * @var array
	 */
	protected static $comments = array();

	/**
	 * ID of a post.
	 *
	 * @var int
	 */
	protected static $post_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$post_id = $factory->post->create();

		$comment_ids    = $factory->comment->create_post_comments( self::$post_id, 8 );
		self::$comments = array_map( 'get_comment', $comment_ids );
	}

	/**
	 * Clears the POST actions in between requests.
	 */
	protected function _clear_post_action() {
		unset( $_POST['trash'] );
		unset( $_POST['untrash'] );
		unset( $_POST['spam'] );
		unset( $_POST['unspam'] );
		unset( $_POST['delete'] );
		$this->_last_response = '';
	}

	/*
	 * Test prototype
	 */

	/**
	 * Tests as a privileged user (administrator).
	 *
	 * Expects test to pass.
	 *
	 * @covers ::_wp_ajax_delete_comment_response
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param string     $action  Action: 'trash', 'untrash', etc.
	 */
	public function _test_as_admin( $comment, $action ) {

		// Reset request.
		$this->_clear_post_action();

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'delete-comment_' . $comment->comment_ID );
		$_POST[ $action ]     = 1;
		$_POST['_total']      = count( self::$comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		try {
			$this->_handleAjax( 'delete-comment' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response.
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Ensure everything is correct.
		$this->assertSame( $comment->comment_ID, (string) $xml->response[0]->comment['id'] );
		$this->assertSame( 'delete-comment_' . $comment->comment_ID, (string) $xml->response['action'] );
		$this->assertGreaterThanOrEqual( time() - 10, (int) $xml->response[0]->comment[0]->supplemental[0]->time[0] );
		$this->assertLessThanOrEqual( time(), (int) $xml->response[0]->comment[0]->supplemental[0]->time[0] );

		// 'trash', 'spam', 'delete' should make the total go down.
		if ( in_array( $action, array( 'trash', 'spam', 'delete' ), true ) ) {
			$total = $_POST['_total'] - 1;

			// 'unspam', 'untrash' should make the total go up.
		} elseif ( in_array( $action, array( 'untrash', 'unspam' ), true ) ) {
			$total = $_POST['_total'] + 1;
		}

		// The total is calculated based on a page break -OR- a random number. Let's look for both possible outcomes.
		$comment_count = wp_count_comments( 0 );
		$recalc_total  = $comment_count->total_comments;

		// Check for either possible total.
		$message = sprintf( 'returned value: %1$d $total: %2$d  $recalc_total: %3$d', (int) $xml->response[0]->comment[0]->supplemental[0]->total[0], $total, $recalc_total );
		$this->assertContains( (int) $xml->response[0]->comment[0]->supplemental[0]->total[0], array( $total, $recalc_total ), $message );
	}

	/**
	 * Tests as a non-privileged user (subscriber).
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param string     $action  Action: 'trash', 'untrash', etc.
	 */
	public function _test_as_subscriber( $comment, $action ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'subscriber' );

		// Set up the $_POST request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'delete-comment_' . $comment->comment_ID );
		$_POST[ $action ]     = 1;
		$_POST['_total']      = count( self::$comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'delete-comment' );
	}


	/**
	 * Tests with a bad nonce.
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param string     $action  Action: 'trash', 'untrash', etc.
	 */
	public function _test_with_bad_nonce( $comment, $action ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'administrator' );

		// Set up the $_POST request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( uniqid() );
		$_POST[ $action ]     = 1;
		$_POST['_total']      = count( self::$comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'delete-comment' );
	}

	/**
	 * Tests with a bad ID.
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param string     $action  Action: 'trash', 'untrash', etc.
	 */
	public function _test_with_bad_id( $comment, $action ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'administrator' );

		// Set up the $_POST request.
		$_POST['id']          = 12346789;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'delete-comment_12346789' );
		$_POST[ $action ]     = 1;
		$_POST['_total']      = count( self::$comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request, look for a timestamp in the exception.
		try {
			$this->_handleAjax( 'delete-comment' );
			$this->fail( 'Expected exception: WPAjaxDieStopException' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( 10, strlen( $e->getMessage() ) );
			$this->assertIsNumeric( $e->getMessage() );
		} catch ( Exception $e ) {
			$this->fail( 'Unexpected exception type: ' . get_class( $e ) );
		}
	}

	/**
	 * Tests doubling the action (e.g. trash a trashed comment).
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @param string     $action  Action: 'trash', 'untrash', etc.
	 */
	public function _test_double_action( $comment, $action ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'administrator' );

		// Set up the $_POST request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'delete-comment_' . $comment->comment_ID );
		$_POST[ $action ]     = 1;
		$_POST['_total']      = count( self::$comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		try {
			$this->_handleAjax( 'delete-comment' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$this->_last_response = '';

		// Force delete the comment.
		if ( 'delete' === $action ) {
			wp_delete_comment( $comment->comment_ID, true );
		}

		// Make the request again, look for a timestamp in the exception.
		try {
			$this->_handleAjax( 'delete-comment' );
			$this->fail( 'Expected exception: WPAjaxDieStopException' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertSame( 10, strlen( $e->getMessage() ) );
			$this->assertIsNumeric( $e->getMessage() );
		} catch ( Exception $e ) {
			$this->fail( 'Unexpected exception type: ' . get_class( $e ) );
		}
	}

	/**
	 * Deletes a comment as an administrator (expects success).
	 *
	 * @covers ::_wp_ajax_delete_comment_response
	 */
	public function test_ajax_comment_trash_actions_as_administrator() {
		// Test trash/untrash.
		$this->_test_as_admin( self::$comments[0], 'trash' );
		$this->_test_as_admin( self::$comments[0], 'untrash' );

		// Test spam/unspam.
		$this->_test_as_admin( self::$comments[1], 'spam' );
		$this->_test_as_admin( self::$comments[1], 'unspam' );

		// Test delete.
		$this->_test_as_admin( self::$comments[2], 'delete' );
	}

	/**
	 * Deletes a comment as a subscriber (expects permission denied).
	 */
	public function test_ajax_comment_trash_actions_as_subscriber() {
		// Test trash/untrash.
		$this->_test_as_subscriber( self::$comments[0], 'trash' );
		$this->_test_as_subscriber( self::$comments[0], 'untrash' );

		// Test spam/unspam.
		$this->_test_as_subscriber( self::$comments[1], 'spam' );
		$this->_test_as_subscriber( self::$comments[1], 'unspam' );

		// Test delete.
		$this->_test_as_subscriber( self::$comments[2], 'delete' );
	}

	/**
	 * Deletes a comment with no ID.
	 *
	 * @covers ::_wp_ajax_delete_comment_response
	 */
	public function test_ajax_trash_comment_no_id() {
		// Test trash/untrash.
		$this->_test_as_admin( self::$comments[0], 'trash' );
		$this->_test_as_admin( self::$comments[0], 'untrash' );

		// Test spam/unspam.
		$this->_test_as_admin( self::$comments[1], 'spam' );
		$this->_test_as_admin( self::$comments[1], 'unspam' );

		// Test delete.
		$this->_test_as_admin( self::$comments[2], 'delete' );
	}

	/**
	 * Deletes a comment with a bad nonce.
	 */
	public function test_ajax_trash_comment_bad_nonce() {
		// Test trash/untrash.
		$this->_test_with_bad_nonce( self::$comments[0], 'trash' );
		$this->_test_with_bad_nonce( self::$comments[0], 'untrash' );

		// Test spam/unspam.
		$this->_test_with_bad_nonce( self::$comments[1], 'spam' );
		$this->_test_with_bad_nonce( self::$comments[1], 'unspam' );

		// Test delete.
		$this->_test_with_bad_nonce( self::$comments[2], 'delete' );
	}

	/**
	 * Tests trashing an already trashed comment, etc.
	 */
	public function test_ajax_trash_double_action() {
		// Test trash/untrash.
		$this->_test_double_action( self::$comments[0], 'trash' );
		$this->_test_double_action( self::$comments[0], 'untrash' );

		// Test spam/unspam.
		$this->_test_double_action( self::$comments[1], 'spam' );
		$this->_test_double_action( self::$comments[1], 'unspam' );

		// Test delete.
		$this->_test_double_action( self::$comments[2], 'delete' );
	}
}
