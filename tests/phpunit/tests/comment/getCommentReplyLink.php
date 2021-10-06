<?php

/**
 * @group comment
 */
class Tests_Comment_GetCommentReplyLink extends WP_UnitTestCase {
	/**
	 * @see https://core.trac.wordpress.org/ticket/38170
	 */
	public function test_should_return_null_when_max_depth_is_less_than_depth() {
		$args = array(
			'depth' => 5,
			'max_depth' => 4,
		);

		$this->assertNull( get_comment_reply_link( $args ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38170
	 */
	public function test_should_return_null_when_default_max_depth_is_less_than_depth() {
		$args = array(
			'depth' => 5,
		);

		$this->assertNull( get_comment_reply_link( $args ) );
	}
}
