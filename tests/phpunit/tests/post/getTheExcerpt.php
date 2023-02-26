<?php

/**
 * @group post
 * @group formatting
 */
class Tests_Post_GetTheExcerpt extends WP_UnitTestCase {

	/**
	 * @ticket 27246
	 */
	public function test_the_excerpt_invalid_post() {
		$this->assertSame( '', get_echo( 'the_excerpt' ) );
		$this->assertSame( '', get_the_excerpt() );
	}

	/**
	 * @ticket 27246
	 * @expectedDeprecated get_the_excerpt
	 */
	public function test_the_excerpt_deprecated() {
		$this->assertSame( '', get_the_excerpt( true ) );
		$this->assertSame( '', get_the_excerpt( false ) );
	}

	/**
	 * @ticket 27246
	 */
	public function test_the_excerpt() {
		$GLOBALS['post'] = self::factory()->post->create_and_get( array( 'post_excerpt' => 'Post excerpt' ) );
		$this->assertSame( "<p>Post excerpt</p>\n", get_echo( 'the_excerpt' ) );
		$this->assertSame( 'Post excerpt', get_the_excerpt() );
	}

	/**
	 * @ticket 27246
	 * @ticket 35486
	 */
	public function test_the_excerpt_password_protected_post() {
		$post = self::factory()->post->create_and_get(
			array(
				'post_excerpt'  => 'Post excerpt',
				'post_password' => '1234',
			)
		);
		$this->assertSame( 'There is no excerpt because this is a protected post.', get_the_excerpt( $post ) );

		$GLOBALS['post'] = $post;
		$this->assertSame( "<p>There is no excerpt because this is a protected post.</p>\n", get_echo( 'the_excerpt' ) );
	}

	/**
	 * @ticket 27246
	 */
	public function test_the_excerpt_specific_post() {
		$GLOBALS['post'] = self::factory()->post->create_and_get( array( 'post_excerpt' => 'Foo' ) );
		$post_id         = self::factory()->post->create( array( 'post_excerpt' => 'Bar' ) );
		$this->assertSame( 'Bar', get_the_excerpt( $post_id ) );
	}

	/**
	 * @ticket 42814
	 */
	public function test_should_fall_back_on_post_content_if_excerpt_is_empty_and_post_is_inferred_from_context() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'Foo',
				'post_excerpt' => '',
			)
		);

		$q = new WP_Query(
			array(
				'p' => $post_id,
			)
		);

		while ( $q->have_posts() ) {
			$q->the_post();
			$found = get_the_excerpt();
		}

		$this->assertSame( 'Foo', $found );
	}

	/**
	 * @ticket 42814
	 */
	public function test_should_fall_back_on_post_content_if_excerpt_is_empty_and_post_is_provided() {
		$GLOBALS['post'] = self::factory()->post->create_and_get(
			array(
				'post_content' => 'Foo',
				'post_excerpt' => '',
			)
		);
		$this->assertSame( 'Foo', get_the_excerpt( $GLOBALS['post'] ) );
	}

	/**
	 * @ticket 42814
	 */
	public function test_should_respect_post_parameter_in_the_loop() {
		$p1 = self::factory()->post->create_and_get( array( 'post_excerpt' => 'Foo' ) );
		$p2 = self::factory()->post->create_and_get( array( 'post_excerpt' => 'Bar' ) );
		$q  = new WP_Query(
			array(
				'p' => $p1->ID,
			)
		);

		while ( $q->have_posts() ) {
			$q->the_post();
			$found = get_the_excerpt( $p2 );
		}

		$this->assertSame( 'Bar', $found );
	}

	/**
	 * @ticket 42814
	 */
	public function test_should_respect_post_parameter_in_the_loop_when_falling_back_on_post_content() {
		$p1 = self::factory()->post->create_and_get(
			array(
				'post_content' => 'Foo',
				'post_excerpt' => '',
			)
		);
		$p2 = self::factory()->post->create_and_get(
			array(
				'post_content' => 'Bar',
				'post_excerpt' => '',
			)
		);
		$q  = new WP_Query(
			array(
				'p' => $p1->ID,
			)
		);

		while ( $q->have_posts() ) {
			$q->the_post();
			$found = get_the_excerpt( $p2 );
		}

		$this->assertSame( 'Bar', $found );
	}
}
