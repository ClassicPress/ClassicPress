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
		$comment_1 = self::factory()->comment->create_and_get(
			array(
				'comment_post_ID'  => $this->post_id,
				'comment_author'   => 'A ClassicPress Commenter',
				'comment_content'  => 'Hi, this is a comment used for testing!',
				'comment_approved' => 1,
			)
		);

		$comment_2 = self::factory()->comment->create_and_get(
			array(
				'comment_post_ID'  => $this->post_id,
				'comment_author'   => 'A Different ClassicPress Commenter',
				'comment_content'  => 'Hi, this is another comment used for testing!',
				'comment_approved' => 1,
			)
		);

		$comment_walker = new Walker_Comment();
		$test_comment = new Walker_Comment_Helper();

		$this->assertTrue( method_exists( 'Walker_Comment', 'comment' ) );
		$this->assertTrue( method_exists( 'Walker_Comment', 'html5_comment' ) );

		$args = array(
			'style'       => 'div',
			'avatar_size' => 0,
			'max_depth'   => 1,
		);

		// Render 2 comments using HTML5 function
		$html5_comment  = $test_comment->exposed_html5_comment( $comment_1, 1, $args );
		$html5_comment .= $test_comment->exposed_html5_comment( $comment_2, 1, $args );

		// Render 2 comments using wrapper on the original function name
		$original_comment  = $test_comment->exposed_comment( $comment_1, 1, $args );
		$original_comment .= $test_comment->exposed_comment( $comment_2, 1, $args );

		$this->assertSame( $original_comment, $html5_comment );
	}
}

// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class Walker_Comment_Helper extends Walker_Comment {
	/**
	 * Expose parent protected `comment()` function for testing
	 */
	public function exposed_comment( $comment, $args, $depth ) {
		// use output buffer to capture rendered comment.
		ob_start();
		$this->comment( $comment, $args, $depth );
		return ob_get_clean();
	}

	/**
	 * Expose parent protected `html5_comment()` function for testing
	 */
	public function exposed_html5_comment( $comment, $args, $depth ) {
		// use output buffer to capture rendered comment.
		ob_start();
		$this->html5_comment( $comment, $args, $depth );
		return ob_get_clean();
	}
}
