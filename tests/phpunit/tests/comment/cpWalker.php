<?php

/**
 * @group comment
 *
 * @covers ::wp_list_comments
 */
class Tests_Comment_Walker_Functions extends WP_UnitTestCase {

	/**
	 * Comment post ID.
	 *
	 * @var int
	 */
	private $post_id;

	public function set_up() {
		parent::set_up();

		$this->post_id = self::factory()->post->create();
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/issues/1645
	 */
	public function test_comment_wrapper_of_html5_comment() {
		$comment = self::factory()->comment->create_and_get(
			array(
				'comment_post_ID'  => $this->post_id,
				'comment_author'   => 'A ClassicPress Commenter',
				'comment_content'  => 'Hi, this is a comment used for testing!',
				'comment_approved' => 1,
			)
		);

		$comment_walker = new Walker_Comment();
		$test_comment = new Walker_Comment_Helper();

		$args = array(
			'style'       => 'div',
			'avatar_size' => 0,
			'max_depth'   => 1,
		);

		$this->assertSame( $test_comment->exposed_comment( $comment, 1, $args ), $test_comment->exposed_html5_comment( $comment, 1, $args ) );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class Walker_Comment_Helper extends Walker_Comment {
	/**
	 * Expose parent protected `comment()` function for testing
	 */
	public function exposed_comment( $comment, $args, $depth ) {
		// use output buffer to capture rendered comment, render twice to account for odd/even rendering
		ob_start();
		$this->comment( $comment, $args, $depth );
		$this->comment( $comment, $args, $depth );
		return ob_get_clean();
	}

	/**
	 * Expose parent protected `html5_comment()` function for testing
	 */
	public function exposed_html5_comment( $comment, $args, $depth ) {
		// use output buffer to capture rendered comment, render twice to account for odd/even rendering
		ob_start();
		$this->html5_comment( $comment, $args, $depth );
		$this->html5_comment( $comment, $args, $depth );
		return ob_get_clean();
	}
}
