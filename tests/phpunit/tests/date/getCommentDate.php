<?php

/**
 * @group date
 * @group datetime
 * @group comment
 */
class Tests_Date_Get_Comment_Date extends WP_UnitTestCase {

	/**
	 * @ticket 51184
	 */
	function test_get_comment_date_returns_correct_time_with_comment_id() {
		$c = self::factory()->comment->create( array( 'comment_date' => '2020-08-29 01:51:00' ) );

		$this->assertEquals( 'August 29, 2020', get_comment_date( 'F j, Y', $c ) );
	}

	/**
	 * @ticket 51184
	 */
	function test_get_comment_date_returns_correct_time_with_empty_format() {
		$c = self::factory()->comment->create( array( 'comment_date' => '2020-08-29 01:51:00' ) );

		$this->assertEquals( 'August 29, 2020', get_comment_date( '', $c ) );
		$this->assertEquals( 'August 29, 2020', get_comment_date( false, $c ) );
	}

	/**
	 * @ticket 51184
	 */
	function test_get_comment_time_returns_correct_time() {
		$c = self::factory()->comment->create( array( 'comment_date' => '2020-08-29 01:51:00' ) );

		$GLOBALS['comment'] = get_comment( $c );
		$this->assertEquals( '1:51 am', get_comment_time( 'g:i a' ) );
	}

	/**
	 * @ticket 51184
	 */
	function test_get_comment_time_returns_correct_time_with_empty_format() {
		$c = self::factory()->comment->create( array( 'comment_date' => '2020-08-29 01:51:00' ) );

		$GLOBALS['comment'] = get_comment( $c );
		$this->assertEquals( '1:51 am', get_comment_time( '' ) );
		$this->assertEquals( '1:51 am', get_comment_time( false ) );
	}
}
