<?php

// save and fetch posts to make sure content is properly filtered.
// these tests don't care what code is responsible for filtering or how it is called, just that it happens when a post is saved.

/**
 * @group post
 * @group formatting
 */
class Tests_Post_Filtering extends WP_UnitTestCase {
<<<<<<< HEAD
	function setUp() {
		parent::setUp();
		update_option('use_balanceTags', 1);
=======
	function set_up() {
		parent::set_up();
		update_option( 'use_balanceTags', 1 );
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
		kses_init_filters();

	}

	function tear_down() {
		kses_remove_filters();
		parent::tear_down();
	}

	// a simple test to make sure unclosed tags are fixed
	function test_post_content_unknown_tag() {

		$content = <<<EOF
<foobar>no such tag</foobar>
EOF;

		$expected = <<<EOF
no such tag
EOF;

		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		$post = get_post($id);

		$this->assertSame( $expected, $post->post_content );
	}

	// a simple test to make sure unbalanced tags are fixed
	function test_post_content_unbalanced_tag() {

		$content = <<<EOF
<i>italics
EOF;

		$expected = <<<EOF
<i>italics</i>
EOF;

		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		$post = get_post($id);

		$this->assertSame( $expected, $post->post_content );
	}

	// test kses filtering of disallowed attribute
	function test_post_content_disallowed_attr() {

		$content = <<<EOF
<img src='foo' width='500' href='shlorp' />
EOF;

		$expected = <<<EOF
<img src='foo' width='500' />
EOF;

		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		$post = get_post($id);

		$this->assertSame( $expected, $post->post_content );
	}

	/**
	 * test kses bug. xhtml does not require space before closing empty element
	 * @see https://core.trac.wordpress.org/ticket/12394
	 */
	function test_post_content_xhtml_empty_elem() {
		$content = <<<EOF
<img src='foo' width='500' height='300'/>
EOF;

		$expected = <<<EOF
<img src='foo' width='500' height='300' />
EOF;

		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		$post = get_post($id);

		$this->assertSame( $expected, $post->post_content );
	}

	// make sure unbalanced tags are untouched when the balance option is off
	function test_post_content_nobalance_nextpage_more() {

		update_option('use_balanceTags', 0);

		$content = <<<EOF
<em>some text<!--nextpage-->
that's continued after the jump</em>
<!--more-->
<p>and the next page
<!--nextpage-->
breaks the graf</p>
EOF;

		$id = self::factory()->post->create( array( 'post_content' => $content ) );
		$post = get_post($id);

		$this->assertSame( $content, $post->post_content );
	}
}
