<?php
/**
 * @group shortcode
 */
class Tests_Shortcode extends WP_UnitTestCase {

	protected $shortcodes = array( 'test-shortcode-tag', 'footag', 'bartag', 'baztag', 'dumptag', 'hyphen', 'hyphen-foo', 'hyphen-foo-bar', 'url', 'img' );

	private $atts    = null;
	private $content = null;
	private $tagname = null;

	private $filter_atts_out   = null;
	private $filter_atts_pairs = null;
	private $filter_atts_atts  = null;

	public function set_up() {
		parent::set_up();

		foreach ( $this->shortcodes as $shortcode ) {
			add_shortcode( $shortcode, array( $this, 'shortcode_' . str_replace( '-', '_', $shortcode ) ) );
		}

		$this->atts              = null;
		$this->content           = null;
		$this->tagname           = null;
		$this->filter_atts_out   = null;
		$this->filter_atts_pairs = null;
		$this->filter_atts_atts  = null;
	}

	public function tear_down() {
		global $shortcode_tags;
		foreach ( $this->shortcodes as $shortcode ) {
			unset( $shortcode_tags[ $shortcode ] );
		}
		parent::tear_down();
	}

	public function shortcode_test_shortcode_tag( $atts, $content = null, $tagname = null ) {
		$this->atts              = $atts;
		$this->content           = $content;
		$this->tagname           = $tagname;
		$this->filter_atts_out   = null;
		$this->filter_atts_pairs = null;
		$this->filter_atts_atts  = null;
	}

	// [footag foo="bar"]
	public function shortcode_footag( $atts ) {
		$foo = isset( $atts['foo'] ) ? $atts['foo'] : '';
		return "foo = $foo";
	}

	// [bartag foo="bar"]
	public function shortcode_bartag( $atts ) {
		$processed_atts = shortcode_atts(
			array(
				'foo' => 'no foo',
				'baz' => 'default baz',
			),
			$atts,
			'bartag'
		);

		return "foo = {$processed_atts['foo']}";
	}

	// [baztag]content[/baztag]
	public function shortcode_baztag( $atts, $content = '' ) {
		return 'content = ' . do_shortcode( $content );
	}

	public function shortcode_dumptag( $atts ) {
		$out = '';
		foreach ( $atts as $k => $v ) {
			$out .= "$k = $v\n";
		}
		return $out;
	}

	public function shortcode_hyphen() {
		return __FUNCTION__;
	}

	public function shortcode_hyphen_foo() {
		return __FUNCTION__;
	}

	public function shortcode_hyphen_foo_bar() {
		return __FUNCTION__;
	}

	public function shortcode_url() {
		return 'http://www.wordpress.org/';
	}

	public function shortcode_img( $atts ) {
		$out = '<img';
		foreach ( $atts as $k => $v ) {
			$out .= " $k=\"$v\"";
		}
		$out .= ' />';

		return $out;
	}

	public function test_noatts() {
		do_shortcode( '[test-shortcode-tag /]' );
		$this->assertSame( '', $this->atts );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_one_att() {
		do_shortcode( '[test-shortcode-tag foo="asdf" /]' );
		$this->assertSame( array( 'foo' => 'asdf' ), $this->atts );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_not_a_tag() {
		$out = do_shortcode( '[not-a-shortcode-tag]' );
		$this->assertSame( '[not-a-shortcode-tag]', $out );
	}

	/**
	 * @ticket 17657
	 */
	public function test_tag_hyphen_not_tag() {
		$out = do_shortcode( '[dumptag-notreal]' );
		$this->assertSame( '[dumptag-notreal]', $out );
	}

	public function test_tag_underscore_not_tag() {
		$out = do_shortcode( '[dumptag_notreal]' );
		$this->assertSame( '[dumptag_notreal]', $out );
	}

	public function test_tag_not_tag() {
		$out = do_shortcode( '[dumptagnotreal]' );
		$this->assertSame( '[dumptagnotreal]', $out );
	}

	/**
	 * @ticket 17657
	 */
	public function test_tag_hyphen() {
		$this->assertSame( 'shortcode_hyphen', do_shortcode( '[hyphen]' ) );
		$this->assertSame( 'shortcode_hyphen_foo', do_shortcode( '[hyphen-foo]' ) );
		$this->assertSame( 'shortcode_hyphen_foo_bar', do_shortcode( '[hyphen-foo-bar]' ) );
		$this->assertSame( '[hyphen-baz]', do_shortcode( '[hyphen-baz]' ) );
		$this->assertSame( '[hyphen-foo-bar-baz]', do_shortcode( '[hyphen-foo-bar-baz]' ) );
	}

	/**
	 * @ticket 9405
	 */
	public function test_attr_hyphen() {
		do_shortcode( '[test-shortcode-tag foo="foo" foo-bar="foo-bar" foo-bar-="foo-bar-" -foo-bar="-foo-bar" -foo-bar-="-foo-bar-" foo-bar-baz="foo-bar-baz" -foo-bar-baz="-foo-bar-baz" foo--bar="foo--bar" /]' );
		$expected_attrs = array(
			'foo'          => 'foo',
			'foo-bar'      => 'foo-bar',
			'foo-bar-'     => 'foo-bar-',
			'-foo-bar'     => '-foo-bar',
			'-foo-bar-'    => '-foo-bar-',
			'foo-bar-baz'  => 'foo-bar-baz',
			'-foo-bar-baz' => '-foo-bar-baz',
			'foo--bar'     => 'foo--bar',
		);
		$this->assertSame( $expected_attrs, $this->atts );
	}

	public function test_two_atts() {
		do_shortcode( '[test-shortcode-tag foo="asdf" bar="bing" /]' );
		$this->assertSame(
			array(
				'foo' => 'asdf',
				'bar' => 'bing',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_noatts_enclosing() {
		do_shortcode( '[test-shortcode-tag]content[/test-shortcode-tag]' );
		$this->assertSame( '', $this->atts );
		$this->assertSame( 'content', $this->content );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_one_att_enclosing() {
		do_shortcode( '[test-shortcode-tag foo="bar"]content[/test-shortcode-tag]' );
		$this->assertSame( array( 'foo' => 'bar' ), $this->atts );
		$this->assertSame( 'content', $this->content );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_two_atts_enclosing() {
		do_shortcode( '[test-shortcode-tag foo="bar" baz="bing"]content[/test-shortcode-tag]' );
		$this->assertSame(
			array(
				'foo' => 'bar',
				'baz' => 'bing',
			),
			$this->atts
		);
		$this->assertSame( 'content', $this->content );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_unclosed() {
		$out = do_shortcode( '[test-shortcode-tag]' );
		$this->assertSame( '', $out );
		$this->assertSame( '', $this->atts );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_positional_atts_num() {
		$out = do_shortcode( '[test-shortcode-tag 123]' );
		$this->assertSame( '', $out );
		$this->assertSame( array( 0 => '123' ), $this->atts );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_positional_atts_url() {
		$out = do_shortcode( '[test-shortcode-tag https://www.youtube.com/watch?v=72xdCU__XCk]' );
		$this->assertSame( '', $out );
		$this->assertSame( array( 0 => 'https://www.youtube.com/watch?v=72xdCU__XCk' ), $this->atts );
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_positional_atts_quotes() {
		$out = do_shortcode( '[test-shortcode-tag "something in quotes" "something else"]' );
		$this->assertSame( '', $out );
		$this->assertSame(
			array(
				0 => 'something in quotes',
				1 => 'something else',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_positional_atts_mixed() {
		$out = do_shortcode( '[test-shortcode-tag 123 https://wordpress.org/ 0 "foo" bar]' );
		$this->assertSame( '', $out );
		$this->assertSame(
			array(
				0 => '123',
				1 => 'https://wordpress.org/',
				2 => '0',
				3 => 'foo',
				4 => 'bar',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_positional_and_named_atts() {
		$out = do_shortcode( '[test-shortcode-tag 123 url=https://wordpress.org/ foo bar="baz"]' );
		$this->assertSame( '', $out );
		$this->assertSame(
			array(
				0     => '123',
				'url' => 'https://wordpress.org/',
				1     => 'foo',
				'bar' => 'baz',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	public function test_footag_default() {
		$out = do_shortcode( '[footag]' );
		$this->assertSame( 'foo = ', $out );
	}

	public function test_footag_val() {
		$val = rand_str();
		$out = do_shortcode( '[footag foo="' . $val . '"]' );
		$this->assertSame( 'foo = ' . $val, $out );
	}

	public function test_nested_tags() {
		$out      = do_shortcode( '[baztag][dumptag abc="foo" def=123 https://wordpress.org/][/baztag]' );
		$expected = "content = abc = foo\ndef = 123\n0 = https://wordpress.org\n";
		$this->assertSame( $expected, $out );
	}

	/**
	 * @ticket 6518
	 */
	public function test_tag_escaped() {
		$out = do_shortcode( '[[footag]] [[bartag foo="bar"]]' );
		$this->assertSame( '[footag] [bartag foo="bar"]', $out );

		$out = do_shortcode( '[[footag /]] [[bartag foo="bar" /]]' );
		$this->assertSame( '[footag /] [bartag foo="bar" /]', $out );

		$out = do_shortcode( '[[baztag foo="bar"]the content[/baztag]]' );
		$this->assertSame( '[baztag foo="bar"]the content[/baztag]', $out );

		// Double escaped.
		$out = do_shortcode( '[[[footag]]] [[[bartag foo="bar"]]]' );
		$this->assertSame( '[[footag]] [[bartag foo="bar"]]', $out );
	}

	public function test_tag_not_escaped() {
		// These have square brackets on either end but aren't actually escaped.
		$out = do_shortcode( '[[footag] [bartag foo="bar"]]' );
		$this->assertSame( '[foo =  foo = bar]', $out );

		$out = do_shortcode( '[[footag /] [bartag foo="bar" /]]' );
		$this->assertSame( '[foo =  foo = bar]', $out );

		$out = do_shortcode( '[[baztag foo="bar"]the content[/baztag]' );
		$this->assertSame( '[content = the content', $out );

		$out = do_shortcode( '[[not-a-tag]]' );
		$this->assertSame( '[[not-a-tag]]', $out );

		$out = do_shortcode( '[[[footag] [bartag foo="bar"]]]' );
		$this->assertSame( '[[foo =  foo = bar]]', $out );
	}

	public function test_mixed_tags() {
		$in       = <<<EOF
So this is a post with [footag foo="some stuff"] and a bunch of tags.

[bartag]

[baztag]
Here's some content
on more than one line
[/baztag]

[bartag foo=1] [baztag] [footag foo="2"] [baztag]

[baztag]
more content
[/baztag]

EOF;
		$expected = <<<EOF
So this is a post with foo = some stuff and a bunch of tags.

foo = no foo

content =
Here's some content
on more than one line


foo = 1 content =  foo = 2 content =
content =
more content

EOF;
		$out      = do_shortcode( $in );
		$this->assertSame( strip_ws( $expected ), strip_ws( $out ) );
	}

	/**
	 * @ticket 6562
	 */
	public function test_utf8_whitespace_1() {
		// NO-BREAK SPACE: U+00A0.
		do_shortcode( "[test-shortcode-tag foo=\"bar\" \xC2\xA0baz=\"123\"]" );
		$this->assertSame(
			array(
				'foo' => 'bar',
				'baz' => '123',
			),
			$this->atts
		);
		$this->assertSame( '', $this->content );
	}

	/**
	 * @ticket 6562
	 */
	public function test_utf8_whitespace_2() {
		// ZERO WIDTH SPACE: U+200B.
		do_shortcode( "[test-shortcode-tag foo=\"bar\" \xE2\x80\x8Babc=\"def\"]" );
		$this->assertSame(
			array(
				'foo' => 'bar',
				'abc' => 'def',
			),
			$this->atts
		);
		$this->assertSame( '', $this->content );
	}

	/**
	 * @ticket 14050
	 */
	public function test_shortcode_unautop() {
		// A blank line is added at the end, so test with it already there.
		$test_string = "[footag]\n";
		$this->assertSame( $test_string, shortcode_unautop( wpautop( $test_string ) ) );
	}

	public function data_test_strip_shortcodes() {
		return array(
			array( 'before', 'before[gallery]' ),
			array( 'after', '[gallery]after' ),
			array( 'beforeafter', 'before[gallery]after' ),
			array( 'before[after', 'before[after' ),
			array( 'beforeafter', 'beforeafter' ),
			array( 'beforeafter', 'before[gallery id="123" size="medium"]after' ),
			array( 'before[unregistered_shortcode]after', 'before[unregistered_shortcode]after' ),
			array( 'beforeafter', 'before[footag]after' ),
			array( 'before  after', 'before [footag]content[/footag] after' ),
			array( 'before  after', 'before [footag foo="123"]content[/footag] after' ),
		);
	}

	/**
	 * @ticket 10326
	 *
	 * @dataProvider data_test_strip_shortcodes
	 *
	 * @param string $expected  Expected output.
	 * @param string $content   Content to run strip_shortcodes() on.
	 */
	public function test_strip_shortcodes( $expected, $content ) {
		$this->assertSame( $expected, strip_shortcodes( $content ) );
	}

	/**
	 * @ticket 37767
	 */
	public function test_strip_shortcodes_filter() {
		add_filter( 'strip_shortcodes_tagnames', array( $this, 'filter_strip_shortcodes_tagnames' ) );
		$this->assertSame( 'beforemiddle [footag]after', strip_shortcodes( 'before[gallery]middle [footag]after' ) );
		remove_filter( 'strip_shortcodes_tagnames', array( $this, 'filter_strip_shortcodes_tagnames' ) );
	}

	public function filter_strip_shortcodes_tagnames() {
		return array( 'gallery' );
	}

	// Store passed in shortcode_atts_{$shortcode} args.
	public function filter_atts( $out, $pairs, $atts ) {
		$this->filter_atts_out   = $out;
		$this->filter_atts_pairs = $pairs;
		$this->filter_atts_atts  = $atts;
		return $out;
	}

	// Filter shortcode atts in various ways.
	public function filter_atts2( $out, $pairs, $atts ) {
		// If foo attribute equals "foo1", change it to be default value.
		if ( isset( $out['foo'] ) && 'foo1' === $out['foo'] ) {
			$out['foo'] = $pairs['foo'];
		}

		// If baz attribute is set, remove it.
		if ( isset( $out['baz'] ) ) {
			unset( $out['baz'] );
		}

		$this->filter_atts_out = $out;
		return $out;
	}

	public function test_shortcode_atts_filter_passes_original_arguments() {
		add_filter( 'shortcode_atts_bartag', array( $this, 'filter_atts' ), 10, 3 );

		do_shortcode( '[bartag foo="foo1" /]' );
		$this->assertSame(
			array(
				'foo' => 'foo1',
				'baz' => 'default baz',
			),
			$this->filter_atts_out
		);
		$this->assertSame(
			array(
				'foo' => 'no foo',
				'baz' => 'default baz',
			),
			$this->filter_atts_pairs
		);
		$this->assertSame( array( 'foo' => 'foo1' ), $this->filter_atts_atts );

		remove_filter( 'shortcode_atts_bartag', array( $this, 'filter_atts' ), 10, 3 );
	}

	public function test_shortcode_atts_filtering() {
		add_filter( 'shortcode_atts_bartag', array( $this, 'filter_atts2' ), 10, 3 );

		$out = do_shortcode( '[bartag foo="foo1" baz="baz1" /]' );
		$this->assertSame( array( 'foo' => 'no foo' ), $this->filter_atts_out );
		$this->assertSame( 'foo = no foo', $out );

		$out = do_shortcode( '[bartag foo="foo2" /]' );
		$this->assertSame( 'foo = foo2', $out );

		remove_filter( 'shortcode_atts_bartag', array( $this, 'filter_atts2' ), 10, 3 );
	}

	/**
	 * Check that shortcode_unautop() will always recognize spaces around shortcodes.
	 *
	 * @ticket 22692
	 */
	public function test_spaces_around_shortcodes() {
		$nbsp = "\xC2\xA0";

		$input = array();

		$input[] = '<p>[gallery ids="37,15,11"]</p>';
		$input[] = '<p> [gallery ids="37,15,11"] </p>';
		$input[] = "<p> {$nbsp}[gallery ids=\"37,15,11\"] {$nbsp}</p>";
		$input[] = '<p> &nbsp;[gallery ids="37,15,11"] &nbsp;</p>';

		$output = '[gallery ids="37,15,11"]';

		foreach ( $input as $in ) {
			$this->assertSame( $output, shortcode_unautop( $in ) );
		}
	}

	/**
	 * Check for bugginess using normal input with latest patches.
	 *
	 * @dataProvider data_escaping
	 */
	public function test_escaping( $input, $output ) {
		return $this->assertSame( $output, do_shortcode( $input ) );
	}

	public function data_escaping() {
		return array(
			array(
				'<!--[if lt IE 7]>',
				'<!--[if lt IE 7]>',
			),
			array(
				'1 <a href="[test-shortcode-tag]"> 2 <a href="[test-shortcode-tag]" >',
				'1 <a href=""> 2 <a href="" >',
			),
			array(
				'1 <a noise="[test-shortcode-tag]"> 2 <a noise=" [test-shortcode-tag] " >',
				'1 <a noise="[test-shortcode-tag]"> 2 <a noise=" [test-shortcode-tag] " >',
			),
			array(
				'[gallery title="<div>hello</div>"]',
				'',
			),
			array(
				'[caption caption="test" width="2"]<div>hello</div>[/caption]',
				'<div style="width: 12px" class="wp-caption alignnone"><div>hello</div><p class="wp-caption-text">test</p></div>',
			),
			array(
				'<div [gallery]>',
				'<div >',
			),
			array(
				'<div [[gallery]]>',
				'<div [gallery]>',
			),
			array(
				'<[[gallery]]>',
				'<[gallery]>',
			),
			array(
				'<div style="selector:url([[gallery]])">',
				'<div style="selector:url([[gallery]])">',
			),
			array(
				'[gallery]<div>Hello</div>[/gallery]',
				'',
			),
			array(
				'[url]',
				'http://www.wordpress.org/',
			),
			array(
				'<a href="[url]">',
				'<a href="http://www.wordpress.org/">',
			),
			array(
				'<a href=[url] >',
				'<a href=http://www.wordpress.org/ >',
			),
			array(
				'<a href="[url]plugins/">',
				'<a href="http://www.wordpress.org/plugins/">',
			),
			array(
				'<a href="bad[url]">',
				'<a href="//www.wordpress.org/">',
			),
			array(
				'<a onclick="bad[url]">',
				'<a onclick="bad[url]">',
			),
		);
	}

	/**
	 * Check for bugginess using normal input with latest patches.
	 *
	 * @dataProvider data_escaping2
	 */
	public function test_escaping2( $input, $output ) {
		return $this->assertSame( $output, strip_shortcodes( $input ) );
	}

	public function data_escaping2() {
		return array(
			array(
				'<!--[if lt IE 7]>',
				'<!--[if lt IE 7]>',
			),
			array(
				'[gallery title="<div>hello</div>"]',
				'',
			),
			array(
				'[caption caption="test" width="2"]<div>hello</div>[/caption]',
				'',
			),
			array(
				'<div [gallery]>',   // Shortcodes will never be stripped inside elements.
				'<div [gallery]>',
			),
			array(
				'<div [[gallery]]>', // Shortcodes will never be stripped inside elements.
				'<div [[gallery]]>',
			),
			array(
				'<[[gallery]]>',
				'<[[gallery]]>',
			),
			array(
				'[gallery]<div>Hello</div>[/gallery]',
				'',
			),
		);
	}

	/**
	 * @ticket 26343
	 */
	public function test_has_shortcode() {
		$content = 'This is a blob with [gallery] in it';
		$this->assertTrue( has_shortcode( $content, 'gallery' ) );

		add_shortcode( 'foo', '__return_false' );
		$content_nested = 'This is a blob with [foo] [gallery] [/foo] in it';
		$this->assertTrue( has_shortcode( $content_nested, 'gallery' ) );
		remove_shortcode( 'foo' );
	}

	/**
	 * Make sure invalid shortcode names are not allowed.
	 *
	 * @dataProvider data_registration_bad
	 * @expectedIncorrectUsage add_shortcode
	 */
	public function test_registration_bad( $input, $expected ) {
		$this->sub_registration( $input, $expected );
	}

	/**
	 * Make sure valid shortcode names are allowed.
	 *
	 * @dataProvider data_registration_good
	 */
	public function test_registration_good( $input, $expected ) {
		$this->sub_registration( $input, $expected );
	}

	private function sub_registration( $input, $expected ) {
		add_shortcode( $input, '' );
		$actual = shortcode_exists( $input );
		$this->assertSame( $expected, $actual );
		if ( $actual ) {
			remove_shortcode( $input );
		}
	}

	public function data_registration_bad() {
		return array(
			array(
				'<html>',
				false,
			),
			array(
				'[shortcode]',
				false,
			),
			array(
				'bad/',
				false,
			),
			array(
				'/bad',
				false,
			),
			array(
				'bad space',
				false,
			),
			array(
				'&amp;',
				false,
			),
			array(
				'',
				false,
			),
		);
	}

	public function data_registration_good() {
		return array(
			array(
				'good!',
				true,
			),
			array(
				'plain',
				true,
			),
			array(
				'unreserved!#$%()*+,-.;?@^_{|}~chars',
				true,
			),
		);
	}

	/**
	 * Automated performance testing of the main regex.
	 *
	 * @dataProvider data_whole_posts
	 */
	public function test_pcre_performance( $input ) {
		$regex  = '/' . get_shortcode_regex() . '/';
		$result = benchmark_pcre_backtracking( $regex, $input, 'match_all' );
		return $this->assertLessThan( 200, $result );
	}

	public function data_whole_posts() {
		require_once DIR_TESTDATA . '/formatting/whole-posts.php';
		return data_whole_posts();
	}

	/**
	 * Ensure the shortcode attribute regex is the same in both the PHP and JS implementations.
	 *
	 * @ticket 34191
	 * @ticket 51734
	 */
	public function test_php_and_js_shortcode_attribute_regexes_match() {
		// This test uses the source file by default but will use the built file if it exists.
		// This allows the test to run using either the src or build directory.
		$file_src   = ABSPATH . 'js/_enqueues/wp/shortcode.js';
		$file_build = ABSPATH . 'wp-includes/js/shortcode.js';

		$this->assertTrue( file_exists( $file_src ) || file_exists( $file_build ) );

		$path = $file_src;

		if ( file_exists( $file_build ) ) {
			$path = $file_build;
		}

		$file    = file_get_contents( $path );
		$matched = preg_match( '|\s+pattern = (\/.+\/)g;|', $file, $matches );
		$php     = get_shortcode_atts_regex();

		$this->assertSame( 1, $matched );

		$js = str_replace( "\'", "'", $matches[1] );
		$this->assertSame( $php, $js );

	}

	/**
	 * @ticket 34939
	 *
	 * Test the (not recommended) [shortcode=XXX] format
	 */
	public function test_unnamed_attribute() {
		$out      = do_shortcode( '[dumptag=https://wordpress.org/]' );
		$expected = "0 = =https://wordpress.org\n";
		$this->assertSame( $expected, $out );
	}

	/**
	 * @ticket 36306
	 */
	public function test_smilies_arent_converted() {
		$out      = apply_filters( 'the_content', '[img alt="Hello :-) World"]' );
		$expected = "<img alt=\"Hello :-) World\" />\n";
		$this->assertSame( $expected, $out );
	}

	/**
	 * @ticket 37906
	 */
	public function test_pre_do_shortcode_tag() {
		// Does nothing if no filters are set up.
		$str = 'pre_do_shortcode_tag';
		add_shortcode( $str, array( $this, 'shortcode_pre_do_shortcode_tag' ) );
		$result_nofilter = do_shortcode( "[{$str}]" );
		$this->assertSame( 'foo', $result_nofilter );

		// Short-circuit with filter.
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_bar' ) );
		$result_filter = do_shortcode( "[{$str}]" );
		$this->assertSame( 'bar', $result_filter );

		// Respect priority.
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_p11' ), 11 );
		$result_priority = do_shortcode( "[{$str}]" );
		$this->assertSame( 'p11', $result_priority );

		// Pass arguments.
		$arr = array(
			'output' => 'p11',
			'key'    => $str,
			'atts'   => array(
				'a' => 'b',
				'c' => 'd',
			),
			'm'      => array(
				"[{$str} a='b' c='d']",
				'',
				$str,
				" a='b' c='d'",
				'',
				'',
				'',
			),
		);
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_attr' ), 12, 4 );
		$result_atts = do_shortcode( "[{$str} a='b' c='d']" );
		$this->assertSame( wp_json_encode( $arr ), $result_atts );

		remove_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_attr' ), 12, 4 );
		remove_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_p11' ), 11 );
		remove_filter( 'pre_do_shortcode_tag', array( $this, 'filter_pre_do_shortcode_tag_bar' ) );
		remove_shortcode( $str );
	}

	public function shortcode_pre_do_shortcode_tag( $atts = array(), $content = '' ) {
		return 'foo';
	}

	public function filter_pre_do_shortcode_tag_bar() {
		return 'bar';
	}

	public function filter_pre_do_shortcode_tag_p11() {
		return 'p11';
	}

	public function filter_pre_do_shortcode_tag_attr( $output, $key, $atts, $m ) {
		$arr = array(
			'output' => $output,
			'key'    => $key,
			'atts'   => $atts,
			'm'      => $m,
		);
		return wp_json_encode( $arr );
	}

	/**
	 * @ticket 32790
	 */
	public function test_do_shortcode_tag_filter() {
		// Does nothing if no filters are set up.
		$str = 'do_shortcode_tag';
		add_shortcode( $str, array( $this, 'shortcode_do_shortcode_tag' ) );
		$result_nofilter = do_shortcode( "[{$str}]" );
		$this->assertSame( 'foo', $result_nofilter );

		// Modify output with filter.
		add_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_replace' ) );
		$result_filter = do_shortcode( "[{$str}]" );
		$this->assertSame( 'fee', $result_filter );

		// Respect priority.
		add_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_generate' ), 11 );
		$result_priority = do_shortcode( "[{$str}]" );
		$this->assertSame( 'foobar', $result_priority );

		// Pass arguments.
		$arr = array(
			'output' => 'foobar',
			'key'    => $str,
			'atts'   => array(
				'a' => 'b',
				'c' => 'd',
			),
			'm'      => array(
				"[{$str} a='b' c='d']",
				'',
				$str,
				" a='b' c='d'",
				'',
				'',
				'',
			),
		);
		add_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_attr' ), 12, 4 );
		$result_atts = do_shortcode( "[{$str} a='b' c='d']" );
		$this->assertSame( wp_json_encode( $arr ), $result_atts );

		remove_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_attr' ), 12 );
		remove_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_generate' ), 11 );
		remove_filter( 'do_shortcode_tag', array( $this, 'filter_do_shortcode_tag_replace' ) );
		remove_shortcode( $str );
	}

	public function shortcode_do_shortcode_tag( $atts = array(), $content = '' ) {
		return 'foo';
	}

	public function filter_do_shortcode_tag_replace( $output ) {
		return str_replace( 'oo', 'ee', $output );
	}

	public function filter_do_shortcode_tag_generate( $output ) {
		return 'foobar';
	}

	public function filter_do_shortcode_tag_attr( $output, $key, $atts, $m ) {
		$arr = array(
			'output' => $output,
			'key'    => $key,
			'atts'   => $atts,
			'm'      => $m,
		);
		return wp_json_encode( $arr );
	}

	/**
	 * @ticket 37304
	 *
	 * Test 'value' syntax for empty attributes
	 */
	public function test_empty_single_quote_attribute() {
		$out = do_shortcode( '[test-shortcode-tag a="foo" b=\'bar\' c=baz foo \'bar\' "baz" ]test empty atts[/test-shortcode-tag]' );
		$this->assertSame(
			array(
				'a' => 'foo',
				'b' => 'bar',
				'c' => 'baz',
				0   => 'foo',
				1   => 'bar',
				2   => 'baz',
			),
			$this->atts
		);
	}

	/**
	 * @ticket 37304
	 */
	public function test_positional_atts_single_quotes() {
		$out = do_shortcode( "[test-shortcode-tag 'something in quotes' 'something else']" );
		$this->assertSame( '', $out );
		$this->assertSame(
			array(
				0 => 'something in quotes',
				1 => 'something else',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}

	/**
	 * @ticket 37304
	 */
	public function test_positional_atts_mixed_quotes() {
		$out = do_shortcode( "[test-shortcode-tag 'something in quotes' \"something else\" 123 foo bar='baz' example=\"test\" ]" );
		$this->assertSame( '', $out );
		$this->assertSame(
			array(
				0         => 'something in quotes',
				1         => 'something else',
				2         => '123',
				3         => 'foo',
				'bar'     => 'baz',
				'example' => 'test',
			),
			$this->atts
		);
		$this->assertSame( 'test-shortcode-tag', $this->tagname );
	}
}
