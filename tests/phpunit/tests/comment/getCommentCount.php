<?php

/**
 * @group comment
 *
 * @covers ::get_comment_count
 */
class Tests_Comment_GetCommentCount extends WP_UnitTestCase {
	protected static $comment_post = null;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$comment_post = $factory->post->create_and_get();
	}

	public function test_get_comment_count() {
		$count = get_comment_count(
			array(
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 0, $count['awaiting_moderation'] );
		$this->assertSame( 0, $count['spam'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 0, $count['post-trashed'] );
		$this->assertSame( 0, $count['total_comments'] );
		$this->assertSame( 0, $count['all'] );
	}

	public function test_get_comment_count_approved() {
		self::factory()->comment->create(
			array(
				'comment_approved' => 1,
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 1, $count['approved'] );
		$this->assertSame( 0, $count['awaiting_moderation'] );
		$this->assertSame( 0, $count['spam'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 0, $count['post-trashed'] );
		$this->assertSame( 1, $count['total_comments'] );
	}

	public function test_get_comment_count_awaiting() {
		self::factory()->comment->create(
			array(
				'comment_approved' => 0,
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 1, $count['awaiting_moderation'] );
		$this->assertSame( 0, $count['spam'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 0, $count['post-trashed'] );
		$this->assertSame( 1, $count['total_comments'] );
	}

	public function test_get_comment_count_spam() {
		self::factory()->comment->create(
			array(
				'comment_approved' => 'spam',
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 0, $count['awaiting_moderation'] );
		$this->assertSame( 1, $count['spam'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 0, $count['post-trashed'] );
		$this->assertSame( 1, $count['total_comments'] );
	}

	public function test_get_comment_count_trash() {
		self::factory()->comment->create(
			array(
				'comment_approved' => 'trash',
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 0, $count['awaiting_moderation'] );
		$this->assertSame( 0, $count['spam'] );
		$this->assertSame( 1, $count['trash'] );
		$this->assertSame( 0, $count['post-trashed'] );
		$this->assertSame( 0, $count['total_comments'] );
	}

	public function test_get_comment_count_post_trashed() {
		self::factory()->comment->create(
			array(
				'comment_approved' => 'post-trashed',
				'comment_post_ID'  => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 0, $count['awaiting_moderation'] );
		$this->assertSame( 0, $count['spam'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 1, $count['post-trashed'] );
		$this->assertSame( 0, $count['total_comments'] );
	}

	/**
	 * @ticket 19901
	 *
	 * @covers ::get_comment_count
	 */
	public function test_get_comment_count_validate_cache_comment_deleted() {

		$comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID' => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 1, $count['total_comments'] );

		wp_delete_comment( $comment_id, true );

		$count = get_comment_count();

		$this->assertSame( 0, $count['total_comments'] );
	}

	/**
	 * @ticket 19901
	 *
	 * @covers ::get_comment_count
	 */
	public function test_get_comment_count_validate_cache_post_deleted() {

		$post_id = self::factory()->post->create();

		$comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID' => $post_id,
			)
		);

		$count = get_comment_count( $post_id );

		$this->assertSame( 1, $count['total_comments'] );

		wp_delete_post( $post_id, true );

		$count = get_comment_count( $post_id );

		$this->assertSame( 0, $count['total_comments'] );
	}

	/**
	 * @ticket 19901
	 *
	 * @covers ::get_comment_count
	 */
	public function test_get_comment_count_validate_cache_comment_status() {
		$comment_id = self::factory()->comment->create(
			array(
				'comment_post_ID' => self::$comment_post->ID,
			)
		);

		$count = get_comment_count();

		$this->assertSame( 1, $count['approved'] );
		$this->assertSame( 0, $count['trash'] );
		$this->assertSame( 1, $count['total_comments'] );

		wp_set_comment_status( $comment_id, 'trash' );

		$count = get_comment_count();

		$this->assertSame( 0, $count['approved'] );
		$this->assertSame( 1, $count['trash'] );
		$this->assertSame( 0, $count['total_comments'] );
	}
}
