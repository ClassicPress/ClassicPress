<?php
/**
 * @group comment
 */
class Tests_Comment_Template extends WP_UnitTestCase {

	function test_get_comments_number() {
		$post_id = self::factory()->post->create();

		$this->assertSame( 0, get_comments_number( 0 ) );
		$this->assertSame( '0', get_comments_number( $post_id ) );
		$this->assertSame( '0', get_comments_number( get_post( $post_id ) ) );

		self::factory()->comment->create_post_comments( $post_id, 12 );

		$this->assertSame( '12', get_comments_number( $post_id ) );
		$this->assertSame( '12', get_comments_number( get_post( $post_id ) ) );
	}

	function test_get_comments_number_without_arg() {
		$post_id = self::factory()->post->create();
		$permalink = get_permalink( $post_id );
		$this->go_to( $permalink );

		$this->assertSame( '0', get_comments_number() );

		self::factory()->comment->create_post_comments( $post_id, 12 );
		$this->go_to( $permalink );

		$this->assertSame( '12', get_comments_number() );
	}

	/**
<<<<<<< HEAD
	 * @see https://core.trac.wordpress.org/ticket/13651
=======
	 * @ticket 48772
	 */
	function test_get_comments_number_text_with_post_id() {
		$post_id = $this->factory->post->create();
		$this->factory->comment->create_post_comments( $post_id, 6 );

		$comments_number_text = get_comments_number_text( false, false, false, $post_id );

		$this->assertSame( sprintf( _n( '%s Comment', '%s Comments', 6 ), '6' ), $comments_number_text );

		ob_start();
		comments_number( false, false, false, $post_id );
		$comments_number_text = ob_get_clean();

		$this->assertSame( sprintf( _n( '%s Comment', '%s Comments', 6 ), '6' ), $comments_number_text );

	}

	/**
	 * @ticket 13651
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	 */
	function test_get_comments_number_text_declension_with_default_args() {
		$post_id = $this->factory->post->create();
		$permalink = get_permalink( $post_id );
		$this->go_to( $permalink );

		$this->assertSame( __( 'No Comments' ), get_comments_number_text() );

		$this->factory->comment->create_post_comments( $post_id, 1 );
		$this->go_to( $permalink );

		$this->assertSame( __( '1 Comment' ), get_comments_number_text() );

		$this->factory->comment->create_post_comments( $post_id, 1 );
		$this->go_to( $permalink );

		$this->assertSame( sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ), get_comments_number_text() );

	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/13651
	 * @dataProvider data_get_comments_number_text_declension
	 */
	function test_get_comments_number_text_declension_with_custom_args( $number, $input, $output ) {
		$post_id = $this->factory->post->create();
		$permalink = get_permalink( $post_id );

		$this->factory->comment->create_post_comments( $post_id, $number );
		$this->go_to( $permalink );

		add_filter( 'gettext_with_context', array( $this, '_enable_comment_number_declension' ), 10, 4 );

		$this->assertSame( $output, get_comments_number_text( false, false, $input ) );

		remove_filter( 'gettext_with_context', array( $this, '_enable_comment_number_declension' ), 10, 4 );
	}

	function _enable_comment_number_declension( $translation, $text, $context, $domain ) {
		if ( 'Comment number declension: on or off' === $context ) {
			$translation = 'on';
		}

		return $translation;
	}

	/**
	 * Data provider for test_get_comments_number_text_declension_with_custom_args().
	 *
	 * @return array {
	 *     @type array {
	 *         @type int    $comments_number The number of comments passed to get_comments_number_text().
	 *         @type string $input           Custom text for comments number, e.g. '%s Comments'.
	 *         @type string $output          The expected output with the correct plural form of '%s Comments'.
	 *     }
	 * }
	 */
	function data_get_comments_number_text_declension() {
		return array(
			array(
				2,
				'Comments (%)',
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
			array(
				2,
				'2 Comments',
				'2 Comments',
			),
			array(
				2,
				'2 Comments<span class="screen-reader-text"> on Hello world!</span>',
				'2 Comments<span class="screen-reader-text"> on Hello world!</span>',
			),
			array(
				2,
				'2 Comments<span class="screen-reader-text"> on Hello % world!</span>',
				'2 Comments<span class="screen-reader-text"> on Hello 2 world!</span>' // See #WP37103
			),
			array(
				2,
				__( '% Comments', 'twentyten' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
			array(
				2,
				_x( '%', 'comments number', 'twentyeleven' ),
				'2',
			),
			array(
				2,
				__( '<b>%</b> Replies', 'twentyeleven' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '<b>2</b>' ),
			),
			array(
				2,
				__( '% <span class="reply">comments &rarr;</span>', 'twentyeleven' ),
				sprintf( '2 <span class="reply">%s &rarr;</span>', trim( sprintf( _n( '%s Comment', '%s Comments', 2 ), '' ) ) ),
			),
			array(
				2,
				__( '% Replies', 'twentytwelve' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
			array(
				2,
				__( 'View all % comments', 'twentythirteen' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
			array(
				2,
				__( '% Comments', 'twentyfourteen' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
			array(
				2,
				__( '% Comments', 'twentyfifteen' ),
				sprintf( _n( '%s Comment', '%s Comments', 2 ), '2' ),
			),
		);
	}

}
