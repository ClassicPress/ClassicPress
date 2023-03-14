<?php
/**
 * @group comment
 *
 * @covers ::get_comment_author_email_link
 */
class Tests_Comment_GetCommentAuthorEmailLink extends WP_UnitTestCase {
	public static $comment;

	public function set_up() {
		parent::set_up();

		// Fake the 'comment' global.
		$GLOBALS['comment'] = self::$comment;

		// Remove obfuscation for testing purposes.
		remove_filter( 'comment_email', 'antispambot' );
	}

	public function tear_down() {
		unset( $GLOBALS['comment'] );
		parent::tear_down();
	}

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$comment = $factory->comment->create_and_get(
			array(
				'comment_author_email' => 'foo@example.org',
			)
		);
	}

	public function test_global_comment_with_default_parameters() {
		$expected = '<a href="mailto:foo@example.org">foo@example.org</a>';

		$this->assertSame( $expected, get_comment_author_email_link() );
	}

	/**
	 * @ticket 36571
	 */
	public function test_all_parameters() {
		unset( $GLOBALS['comment'] );

		$linktext = 'linktext';
		$before   = 'before';
		$after    = 'after';
		$comment  = self::factory()->comment->create_and_get(
			array(
				'comment_author_email' => $email = 'baz@example.org',
			)
		);

		$expected = sprintf( '%1$s<a href="mailto:%2$s">%3$s</a>%4$s', $before, $email, $linktext, $after );

		$this->assertSame( $expected, get_comment_author_email_link( $linktext, $before, $after, $comment ) );
	}

	public function test_all_parameters_with_global_comment() {
		$linktext = 'linktext';
		$before   = 'before';
		$after    = 'after';

		$expected = sprintf( '%1$s<a href="mailto:foo@example.org">%2$s</a>%3$s', $before, $linktext, $after );

		$this->assertSame( $expected, get_comment_author_email_link( $linktext, $before, $after ) );
	}

	public function test_linktext() {
		$expected = sprintf( '<a href="mailto:foo@example.org">%1$s</a>', $linktext = 'linktext' );

		$this->assertSame( $expected, get_comment_author_email_link( $linktext ) );
	}

	public function test_before() {
		$expected = sprintf( '%1$s<a href="mailto:foo@example.org">foo@example.org</a>', $before = 'before' );

		$this->assertSame( $expected, get_comment_author_email_link( '', $before ) );
	}

	public function test_after() {
		$expected = sprintf( '<a href="mailto:foo@example.org">foo@example.org</a>%1$s', $after = 'after' );

		$this->assertSame( $expected, get_comment_author_email_link( '', '', $after ) );
	}

	/**
	 * @ticket 36571
	 */
	public function test_comment_param_should_override_global() {
		$comment = self::factory()->comment->create_and_get(
			array(
				'comment_author_email' => $email = 'bar@example.org',
			)
		);

		$expected = sprintf( '<a href="mailto:%1$s">%2$s</a>', $email, $email );

		$this->assertSame( $expected, get_comment_author_email_link( '', '', '', $comment ) );
	}
}
