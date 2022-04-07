<?php
/**
 * @group dependencies
 * @group scripts
 */
class Tests_Dependencies_Styles extends WP_UnitTestCase {
	public $old_wp_styles;
	public static $asset_version;
	public $classicpress_asset_version_calls = array();
	public $classicpress_asset_version_override = null;

	public static function setUpBeforeClass() {
		self::$asset_version = classicpress_asset_version( 'style' );
		parent::setUpBeforeClass();
	}

	function setUp() {
		parent::setUp();
		if ( empty( $GLOBALS['wp_styles'] ) )
			$GLOBALS['wp_styles'] = null;
		$this->old_wp_styles = $GLOBALS['wp_styles'];
		remove_action( 'wp_default_styles', 'wp_default_styles' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		add_filter(
			'classicpress_asset_version',
			array( $this, 'classicpress_asset_version_handler' ),
			10, 4
		);
		$this->classicpress_asset_version_calls = array();
		$GLOBALS['wp_styles'] = new WP_Styles();
		$GLOBALS['wp_styles']->default_version = self::$asset_version;
	}

	function tearDown() {
		$GLOBALS['wp_styles'] = $this->old_wp_styles;
		add_action( 'wp_default_styles', 'wp_default_styles' );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_filter(
			'classicpress_asset_version',
			array( $this, 'classicpress_asset_version_handler' ),
			10
		);
		parent::tearDown();
	}

	function classicpress_asset_version_handler( $version, $type, $handle ) {
		if ( is_null( $this->classicpress_asset_version_override ) ) {
			$return = $version;
		} else {
			$return = $this->classicpress_asset_version_override;
		}
		array_push( $this->classicpress_asset_version_calls, array(
			'version' => $version,
			'type'    => $type,
			'handle'  => $handle,
			'return'  => $return,
		) );
		return $return;
	}

	/**
	 * Test versioning
	 * @see https://core.trac.wordpress.org/ticket/11315
	 */
	function test_wp_enqueue_style() {
		wp_enqueue_style('no-deps-no-version', 'example.com' );
		wp_enqueue_style('no-deps-version', 'example.com', array(), 1.2);
		wp_enqueue_style('no-deps-null-version', 'example.com', array(), null);
		wp_enqueue_style('no-deps-null-version-print-media', 'example.com', array(), null, 'print');
		$ver = self::$asset_version;
		$expected  = "<link rel='stylesheet' id='no-deps-no-version-css'  href='http://example.com?ver=$ver' type='text/css' media='all' />\n";
		$expected .= "<link rel='stylesheet' id='no-deps-version-css'  href='http://example.com?ver=1.2' type='text/css' media='all' />\n";
		$expected .= "<link rel='stylesheet' id='no-deps-null-version-css'  href='http://example.com' type='text/css' media='all' />\n";
		$expected .= "<link rel='stylesheet' id='no-deps-null-version-print-media-css'  href='http://example.com' type='text/css' media='print' />\n";

		$this->assertEquals($expected, get_echo('wp_print_styles'));

		// No styles left to print
		$this->assertEquals("", get_echo('wp_print_styles'));

		// 'classicpress_asset_version' filter called as expected
		$this->assertEquals( array(
			array(
				'version' => $ver,
				'type'    => 'style',
				'handle'  => 'no-deps-no-version',
				'return'  => $ver,
			),
			array(
				'version' => 1.2,
				'type'    => 'style',
				'handle'  => 'no-deps-version',
				'return'  => 1.2,
			),
			array(
				'version' => '',
				'type'    => 'style',
				'handle'  => 'no-deps-null-version',
				'return'  => '',
			),
			array(
				'version' => '',
				'type'    => 'style',
				'handle'  => 'no-deps-null-version-print-media',
				'return'  => '',
			),
		), $this->classicpress_asset_version_calls );
	}

	function test_wp_enqueue_style_override_default_version() {
		$ver = 'aaaa';
		$GLOBALS['wp_styles']->default_version = $ver;
		wp_enqueue_style( 'override-default-version', 'example.com' );
		$expected  = "<link rel='stylesheet' id='override-default-version-css'  href='http://example.com?ver=$ver' type='text/css' media='all' />\n";
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
		$this->assertEquals( array(
			array(
				'version' => $ver,
				'type'    => 'style',
				'handle'  => 'override-default-version',
				'return'  => $ver,
			),
		), $this->classicpress_asset_version_calls );
	}

	function test_wp_enqueue_style_override_default_version_and_filter() {
		$ver = 'bbbb';
		$GLOBALS['wp_styles']->default_version = 'aaaa';
		$this->classicpress_asset_version_override = $ver;
		wp_enqueue_style( 'override-default-version-and-filter', 'example.com' );
		$expected = "<link rel='stylesheet' id='override-default-version-and-filter-css'  href='http://example.com?ver=$ver' type='text/css' media='all' />\n";
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
		$this->assertEquals( array(
			array(
				'version' => $GLOBALS['wp_styles']->default_version,
				'type'    => 'style',
				'handle'  => 'override-default-version-and-filter',
				'return'  => $ver,
			),
		), $this->classicpress_asset_version_calls );
	}

	function test_wp_enqueue_style_filter_default_version() {
		$ver = 'cccc';
		$this->classicpress_asset_version_override = $ver;
		wp_enqueue_style( 'filter-default-version', 'example.com' );
		$expected = "<link rel='stylesheet' id='filter-default-version-css'  href='http://example.com?ver=$ver' type='text/css' media='all' />\n";
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
		$this->assertEquals( array(
			array(
				'version' => $GLOBALS['wp_styles']->default_version,
				'type'    => 'style',
				'handle'  => 'filter-default-version',
				'return'  => $ver,
			),
		), $this->classicpress_asset_version_calls );
	}

	function test_wp_enqueue_style_filter_declared_version() {
		$this->classicpress_asset_version_override = 'oooo';
		wp_enqueue_style( 'filter-declared-version', 'example.com', array(), 'dddd' );
		$expected = "<link rel='stylesheet' id='filter-declared-version-css'  href='http://example.com?ver=oooo' type='text/css' media='all' />\n";
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
		$this->assertEquals( array(
			array(
				'version' => 'dddd',
				'type'    => 'style',
				'handle'  => 'filter-declared-version',
				'return'  => 'oooo',
			),
		), $this->classicpress_asset_version_calls );
	}

	function test_wp_enqueue_style_filter_null_version() {
		$this->classicpress_asset_version_override = 'oooo';
		wp_enqueue_style( 'filter-null-version', 'example.com', array(), null );
		$expected = "<link rel='stylesheet' id='filter-null-version-css'  href='http://example.com?ver=oooo' type='text/css' media='all' />\n";
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
		$this->assertEquals( array(
			array(
				'version' => '',
				'type'    => 'style',
				'handle'  => 'filter-null-version',
				'return'  => 'oooo',
			),
		), $this->classicpress_asset_version_calls );
	}

	/**
	 * Test the different protocol references in wp_enqueue_style
	 * @global WP_Styles $wp_styles
	 * @see https://core.trac.wordpress.org/ticket/16560
	 */
	public function test_protocols() {
		// Init
		global $wp_styles;
		$base_url_backup = $wp_styles->base_url;
		$wp_styles->base_url = 'http://example.com/wordpress';
		$expected = '';
		$ver = self::$asset_version;

		// Try with an HTTP reference
		wp_enqueue_style( 'reset-css-http', 'http://yui.yahooapis.com/2.8.1/build/reset/reset-min.css' );
		$expected  .= "<link rel='stylesheet' id='reset-css-http-css'  href='http://yui.yahooapis.com/2.8.1/build/reset/reset-min.css?ver=$ver' type='text/css' media='all' />\n";

		// Try with an HTTPS reference
		wp_enqueue_style( 'reset-css-https', 'http://yui.yahooapis.com/2.8.1/build/reset/reset-min.css' );
		$expected  .= "<link rel='stylesheet' id='reset-css-https-css'  href='http://yui.yahooapis.com/2.8.1/build/reset/reset-min.css?ver=$ver' type='text/css' media='all' />\n";

		// Try with an automatic protocol reference (//)
		wp_enqueue_style( 'reset-css-doubleslash', '//yui.yahooapis.com/2.8.1/build/reset/reset-min.css' );
		$expected  .= "<link rel='stylesheet' id='reset-css-doubleslash-css'  href='//yui.yahooapis.com/2.8.1/build/reset/reset-min.css?ver=$ver' type='text/css' media='all' />\n";

		// Try with a local resource and an automatic protocol reference (//)
		$url = '//my_plugin/style.css';
		wp_enqueue_style( 'plugin-style', $url );
		$expected  .= "<link rel='stylesheet' id='plugin-style-css'  href='$url?ver=$ver' type='text/css' media='all' />\n";

		// Try with a bad protocol
		wp_enqueue_style( 'reset-css-ftp', 'ftp://yui.yahooapis.com/2.8.1/build/reset/reset-min.css' );
		$expected  .= "<link rel='stylesheet' id='reset-css-ftp-css'  href='{$wp_styles->base_url}ftp://yui.yahooapis.com/2.8.1/build/reset/reset-min.css?ver=$ver' type='text/css' media='all' />\n";

		// Go!
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );

		// No styles left to print
		$this->assertEquals( '', get_echo( 'wp_print_styles' ) );

		// Cleanup
		$wp_styles->base_url = $base_url_backup;
	}

	/**
	 * Test if inline styles work
	 * @see https://core.trac.wordpress.org/ticket/24813
	 */
	public function test_inline_styles() {

		$style  = ".thing {\n";
		$style .= "\tbackground: red;\n";
		$style .= "}";

		$expected  = "<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "<style id='handle-inline-css' type='text/css'>\n";
		$expected .= "$style\n";
		$expected .= "</style>\n";

		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );
		wp_add_inline_style( 'handle', $style );

		// No styles left to print
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
	}

	/**
	 * Test if inline styles work with concatination
	 * @global WP_Styles $wp_styles
	 * @see https://core.trac.wordpress.org/ticket/24813
	 */
	public function test_inline_styles_concat() {

		global $wp_styles;

		$wp_styles->do_concat = true;
		$wp_styles->default_dirs = array( '/wp-admin/', '/wp-includes/css/' ); // Default dirs as in wp-includes/script-loader.php

		$style  = ".thing {\n";
		$style .= "\tbackground: red;\n";
		$style .= "}";

		$expected  = "<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "<style id='handle-inline-css' type='text/css'>\n";
		$expected .= "$style\n";
		$expected .= "</style>\n";

		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );
		wp_add_inline_style( 'handle', $style );

		wp_print_styles();
		$this->assertEquals( $expected, $wp_styles->print_html );

	}

	/**
	 * Test if multiple inline styles work
	 * @see https://core.trac.wordpress.org/ticket/24813
	 */
	public function test_multiple_inline_styles() {

		$style1  = ".thing1 {\n";
		$style1 .= "\tbackground: red;\n";
		$style1 .= "}";

		$style2  = ".thing2 {\n";
		$style2 .= "\tbackground: blue;\n";
		$style2 .= "}";

		$expected  = "<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "<style id='handle-inline-css' type='text/css'>\n";
		$expected .= "$style1\n";
		$expected .= "$style2\n";
		$expected .= "</style>\n";

		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );
		wp_add_inline_style( 'handle', $style1 );
		wp_add_inline_style( 'handle', $style2 );

		// No styles left to print
		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );

	}

	/**
	 * Test if a plugin doing it the wrong way still works
	 *
	 * @expectedIncorrectUsage wp_add_inline_style
	 * @see https://core.trac.wordpress.org/ticket/24813
	 */
	public function test_plugin_doing_inline_styles_wrong() {

		$style  = "<style id='handle-inline-css' type='text/css'>\n";
		$style .= ".thing {\n";
		$style .= "\tbackground: red;\n";
		$style .= "}\n";
		$style .= "</style>";

		$expected  = "<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "$style\n";

		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );

		wp_add_inline_style( 'handle', $style );

		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );

	}

	/**
	 * Test to make sure <style> tags aren't output if there are no inline styles.
	 * @see https://core.trac.wordpress.org/ticket/24813
	 */
	public function test_unnecessary_style_tags() {

		$expected  = "<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";

		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );

		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );

	}

	/**
	 * Test to make sure that inline styles attached to conditional
	 * stylesheets are also conditional.
	 */
	public function test_conditional_inline_styles_are_also_conditional() {
		$expected = <<<CSS
<!--[if IE]>
<link rel='stylesheet' id='handle-css'  href='http://example.com?ver=1' type='text/css' media='all' />
<style id='handle-inline-css' type='text/css'>
a { color: blue; }
</style>
<![endif]-->

CSS;
		wp_enqueue_style( 'handle', 'http://example.com', array(), 1 );
		wp_style_add_data( 'handle', 'conditional', 'IE' );
		wp_add_inline_style( 'handle', 'a { color: blue; }' );

		$this->assertEqualsIgnoreEOL( $expected, get_echo( 'wp_print_styles' ) );
	}

	/**
	 * Testing 'wp_register_style' return boolean success/failure value.
	 *
	 * @see https://core.trac.wordpress.org/ticket/31126
	 */
	function test_wp_register_style() {
		$this->assertTrue( wp_register_style( 'duplicate-handler', 'http://example.com' ) );
		$this->assertFalse( wp_register_style( 'duplicate-handler', 'http://example.com' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35229
	 */
	function test_wp_add_inline_style_for_handle_without_source() {
		$style  = "a { color: blue; }";

		$expected  = "<link rel='stylesheet' id='handle-one-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "<link rel='stylesheet' id='handle-two-css'  href='http://example.com?ver=1' type='text/css' media='all' />\n";
		$expected .= "<style id='handle-three-inline-css' type='text/css'>\n";
		$expected .= "$style\n";
		$expected .= "</style>\n";

		wp_register_style( 'handle-one', 'http://example.com', array(), 1 );
		wp_register_style( 'handle-two', 'http://example.com', array(), 1 );
		wp_register_style( 'handle-three', false, array( 'handle-one', 'handle-two' ) );

		wp_enqueue_style( 'handle-three' );
		wp_add_inline_style( 'handle-three', $style );

		$this->assertEquals( $expected, get_echo( 'wp_print_styles' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35921
	 * @dataProvider data_styles_with_media
	 */
	function test_wp_enqueue_style_with_media( $expected, $media ) {
		wp_enqueue_style( 'handle', 'http://example.com', array(), 1, $media );
		$this->assertContains( $expected, get_echo( 'wp_print_styles' ) );
	}

	function data_styles_with_media() {
		return array(
			array(
				"media='all'",
				'all'
			),
			array(
				"media='(orientation: portrait)'",
				'(orientation: portrait)'
			),
			array(
				"media='(max-width: 640px)'",
				'(max-width: 640px)'
			),
			array(
				"media='print and (min-width: 25cm)'",
				'print and (min-width: 25cm)'
			),
			array(
				"media='screen and (color), projection and (color)'",
				'screen and (color), projection and (color)'
			),
			array(
				"media='not screen and (color)'",
				'not screen and (color)'
			),
		);
	}
}
