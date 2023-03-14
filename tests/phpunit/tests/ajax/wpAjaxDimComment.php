<?php

/**
 * Admin Ajax functions to be tested.
 */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

/**
 * Testing Ajax comment functionality
 *
 * @package WordPress
 * @subpackage UnitTests
 * @since 3.4.0
 *
 * @group ajax
 *
 * @covers ::wp_ajax_dim_comment
 */
class Tests_Ajax_wpAjaxDimComment extends WP_Ajax_UnitTestCase {

	/**
	 * List of comments.
	 *
	 * @var array
	 */
	protected $_comments = array();

	/**
	 * Sets up the test fixture.
	 */
	public function set_up() {
		parent::set_up();
		$post_id         = self::factory()->post->create();
		$this->_comments = self::factory()->comment->create_post_comments( $post_id, 15 );
		$this->_comments = array_map( 'get_comment', $this->_comments );
	}

	/**
	 * Clears the POST actions in between requests.
	 */
	protected function _clear_post_action() {
		unset( $_POST['id'] );
		unset( $_POST['new'] );
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
	 * @param WP_Comment $comment Comment object.
	 */
	public function _test_as_admin( $comment ) {

		// Reset request.
		$this->_clear_post_action();

		// Become an administrator.
		$this->_setRole( 'administrator' );

		// Set up a default request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'approve-comment_' . $comment->comment_ID );
		$_POST['_total']      = count( $this->_comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Save the comment status.
		$prev_status = wp_get_comment_status( $comment->comment_ID );

		// Make the request.
		try {
			$this->_handleAjax( 'dim-comment' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response.
		$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

		// Ensure everything is correct.
		$this->assertSame( $comment->comment_ID, (string) $xml->response[0]->comment['id'] );
		$this->assertSame( 'dim-comment_' . $comment->comment_ID, (string) $xml->response['action'] );
		$this->assertGreaterThanOrEqual( time() - 10, (int) $xml->response[0]->comment[0]->supplemental[0]->time[0] );
		$this->assertLessThanOrEqual( time(), (int) $xml->response[0]->comment[0]->supplemental[0]->time[0] );

		// Check the status.
		$current = wp_get_comment_status( $comment->comment_ID );
		if ( in_array( $prev_status, array( 'unapproved', 'spam' ), true ) ) {
			$this->assertSame( 'approved', $current );
		} else {
			$this->assertSame( 'unapproved', $current );
		}

		// The total is calculated based on a page break -OR- a random number. Let's look for both possible outcomes.
		$comment_count = wp_count_comments( 0 );
		$recalc_total  = $comment_count->total_comments;

		// Delta is not specified, it will always be 1 lower than the request.
		$total = $_POST['_total'] - 1;

		// Check for either possible total.
		$this->assertContains( (int) $xml->response[0]->comment[0]->supplemental[0]->total[0], array( $total, $recalc_total ) );
	}

	/**
	 * Tests as a non-privileged user (subscriber).
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 */
	public function _test_as_subscriber( $comment ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'subscriber' );

		// Set up the $_POST request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'approve-comment_' . $comment->comment_ID );
		$_POST['_total']      = count( $this->_comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'dim-comment' );
	}

	/**
	 * Tests with a bad nonce.
	 *
	 * Expects test to fail.
	 *
	 * @param WP_Comment $comment Comment object.
	 */
	public function _test_with_bad_nonce( $comment ) {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'administrator' );

		// Set up the $_POST request.
		$_POST['id']          = $comment->comment_ID;
		$_POST['_ajax_nonce'] = wp_create_nonce( uniqid() );
		$_POST['_total']      = count( $this->_comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request.
		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );
		$this->_handleAjax( 'dim-comment' );
	}

	/**
	 * Tests with a bad ID.
	 *
	 * Expects test to fail.
	 */
	public function test_with_bad_id() {

		// Reset request.
		$this->_clear_post_action();

		// Become a subscriber.
		$this->_setRole( 'administrator' );

		// Set up the $_POST request.
		$_POST['id']          = 12346789;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'dim-comment_12346789' );
		$_POST['_total']      = count( $this->_comments );
		$_POST['_per_page']   = 100;
		$_POST['_page']       = 1;
		$_POST['_url']        = admin_url( 'edit-comments.php' );

		// Make the request, look for a timestamp in the exception.
		try {
			$this->_handleAjax( 'dim-comment' );
			$this->fail( 'Expected exception: WPAjaxDieContinueException' );
		} catch ( WPAjaxDieContinueException $e ) {

			// Get the response.
			$xml = simplexml_load_string( $this->_last_response, 'SimpleXMLElement', LIBXML_NOCDATA );

			// Ensure everything is correct.
			$this->assertSame( '0', (string) $xml->response[0]->comment['id'] );
			$this->assertSame( 'dim-comment_0', (string) $xml->response['action'] );
			$this->assertStringContainsString( 'Comment ' . $_POST['id'] . ' does not exist', $this->_last_response );

		} catch ( Exception $e ) {
			$this->fail( 'Unexpected exception type: ' . get_class( $e ) );
		}
	}

	/**
	 * Dims a comment as an administrator (expects success).
	 */
	public function test_ajax_comment_dim_actions_as_administrator() {
		$comment = array_pop( $this->_comments );
		$this->_test_as_admin( $comment );
		$this->_test_as_admin( $comment );
	}

	/**
	 * Dims a comment as a subscriber (expects permission denied).
	 */
	public function test_ajax_comment_dim_actions_as_subscriber() {
		$comment = array_pop( $this->_comments );
		$this->_test_as_subscriber( $comment );
	}

	/**
	 * Dims a comment with no ID.
	 */
	public function test_ajax_dim_comment_no_id() {
		$comment = array_pop( $this->_comments );
		$this->_test_as_admin( $comment );
	}

	/**
	 * Dims a comment with a bad nonce.
	 */
	public function test_ajax_dim_comment_bad_nonce() {
		$comment = array_pop( $this->_comments );
		$this->_test_with_bad_nonce( $comment );
	}
}
