<?php
/**
 * @group dependencies
 * @group scripts
 * @covers ::wp_enqueue_script
 * @covers ::wp_register_script
 * @covers ::wp_print_scripts
 * @covers ::wp_script_add_data
 * @covers ::wp_add_inline_script
 * @covers ::wp_set_script_translations
 */
class Tests_Dependencies_Scripts extends WP_UnitTestCase {
	protected $old_wp_scripts;
	private static $asset_version;
	private $classicpress_asset_version_calls    = array();
	private $classicpress_asset_version_override = null;

	protected $wp_scripts_print_translations_output;

	public static function set_up_before_class() {
		self::$asset_version = classicpress_asset_version( 'script' );
		parent::set_up_before_class();
	}

	public function set_up() {
		parent::set_up();
		$this->old_wp_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		remove_action( 'wp_default_scripts', 'wp_default_packages' );

		$this->wp_scripts_print_translations_output  = <<<JS
<script type='text/javascript' id='__HANDLE__-js-translations'>
( function( domain, translations ) {
	var localeData = translations.locale_data[ domain ] || translations.locale_data.messages;
	localeData[""].domain = domain;
	wp.i18n.setLocaleData( localeData, domain );
} )( "__DOMAIN__", __JSON_TRANSLATIONS__ );
</script>
JS;
		$this->wp_scripts_print_translations_output .= "\n";

		add_filter(
			'classicpress_asset_version',
			array( $this, 'classicpress_asset_version_handler' ),
			10,
			4
		);
		$this->classicpress_asset_version_calls = array();

		$GLOBALS['wp_scripts']                  = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = self::$asset_version;
	}

	public function tear_down() {
		$GLOBALS['wp_scripts'] = $this->old_wp_scripts;
		add_action( 'wp_default_scripts', 'wp_default_scripts' );
		remove_filter(
			'classicpress_asset_version',
			array( $this, 'classicpress_asset_version_handler' ),
			10
		);
		parent::tear_down();
	}

	public function classicpress_asset_version_handler( $version, $type, $handle ) {
		if ( is_null( $this->classicpress_asset_version_override ) ) {
			$return = $version;
		} else {
			$return = $this->classicpress_asset_version_override;
		}
		array_push(
			$this->classicpress_asset_version_calls,
			array(
				'version' => $version,
				'type'    => $type,
				'handle'  => $handle,
				'return'  => $return,
			)
		);
		return $return;
	}

	/**
	 * Test versioning
	 *
	 * @ticket 11315
	 */
	public function test_wp_enqueue_script() {
		wp_enqueue_script( 'no-deps-no-version', 'example.com', array() );
		wp_enqueue_script( 'empty-deps-no-version', 'example.com' );
		wp_enqueue_script( 'empty-deps-version', 'example.com', array(), 1.2 );
		wp_enqueue_script( 'empty-deps-null-version', 'example.com', array(), null );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' src='http://example.com?ver=$ver' id='no-deps-no-version-js'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com?ver=$ver' id='empty-deps-no-version-js'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com?ver=1.2' id='empty-deps-version-js'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='empty-deps-null-version-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );

		// 'classicpress_asset_version' filter called as expected
		$this->assertSame(
			array(
				array(
					'version' => $ver,
					'type'    => 'script',
					'handle'  => 'no-deps-no-version',
					'return'  => $ver,
				),
				array(
					'version' => $ver,
					'type'    => 'script',
					'handle'  => 'empty-deps-no-version',
					'return'  => $ver,
				),
				array(
					'version' => 1.2,
					'type'    => 'script',
					'handle'  => 'empty-deps-version',
					'return'  => 1.2,
				),
				array(
					'version' => '',
					'type'    => 'script',
					'handle'  => 'empty-deps-null-version',
					'return'  => '',
				),
			),
			$this->classicpress_asset_version_calls
		);
	}

	/**
	 * @ticket 42804
	 */
	public function test_wp_enqueue_script_with_html5_support_does_not_contain_type_attribute() {
		add_theme_support( 'html5', array( 'script' ) );

		$GLOBALS['wp_scripts']                  = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = classicpress_asset_version( 'script' );

		wp_enqueue_script( 'empty-deps-no-version', 'example.com' );

		$ver      = classicpress_asset_version( 'style' );
		$expected = "<script src='http://example.com?ver=$ver' id='empty-deps-no-version-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Test the different protocol references in wp_enqueue_script
	 *
	 * @global WP_Scripts $wp_scripts
	 * @ticket 16560
	 */
	public function test_protocols() {
		// Init.
		global $wp_scripts;
		$base_url_backup      = $wp_scripts->base_url;
		$wp_scripts->base_url = 'http://example.com/wordpress';
		$expected             = '';
		$ver                  = self::$asset_version;

		// Try with an HTTP reference.
		wp_enqueue_script( 'jquery-http', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected .= "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver' id='jquery-http-js'></script>\n";

		// Try with an HTTPS reference.
		wp_enqueue_script( 'jquery-https', 'https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected .= "<script type='text/javascript' src='https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver' id='jquery-https-js'></script>\n";

		// Try with an automatic protocol reference (//).
		wp_enqueue_script( 'jquery-doubleslash', '//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected .= "<script type='text/javascript' src='//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver' id='jquery-doubleslash-js'></script>\n";

		// Try with a local resource and an automatic protocol reference (//).
		$url = '//my_plugin/script.js';
		wp_enqueue_script( 'plugin-script', $url );
		$expected .= "<script type='text/javascript' src='$url?ver=$ver' id='plugin-script-js'></script>\n";

		// Try with a bad protocol.
		wp_enqueue_script( 'jquery-ftp', 'ftp://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js' );
		$expected .= "<script type='text/javascript' src='{$wp_scripts->base_url}ftp://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js?ver=$ver' id='jquery-ftp-js'></script>\n";

		// Go!
		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );

		// Cleanup.
		$wp_scripts->base_url = $base_url_backup;
	}

	/**
	 * Test script concatenation.
	 */
	public function test_script_concatenation() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/directory/' );

		wp_enqueue_script( 'one', '/directory/script.js' );
		wp_enqueue_script( 'two', '/directory/script.js' );
		wp_enqueue_script( 'three', '/directory/script.js' );

		wp_print_scripts();
		$print_scripts = get_echo( '_print_scripts' );

		$ver      = self::$asset_version;
		$expected = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=one,two,three&amp;ver={$ver}'></script>\n";

		$this->assertSame( $expected, $print_scripts );
	}

	/**
	 * Testing `wp_script_add_data` with the data key.
	 *
	 * @ticket 16024
	 */
	public function test_wp_script_add_data_with_data_key() {
		// Enqueue and add data.
		wp_enqueue_script( 'test-only-data', 'example.com', array(), null );
		wp_script_add_data( 'test-only-data', 'data', 'testing' );
		$expected  = "<script type='text/javascript' id='test-only-data-js-extra'>\n/* <![CDATA[ */\ntesting\n/* ]]> */\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-only-data-js'></script>\n";

		// Go!
		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Testing `wp_script_add_data` with the conditional key.
	 *
	 * @ticket 16024
	 */
	public function test_wp_script_add_data_with_conditional_key() {
		// Enqueue and add conditional comments.
		wp_enqueue_script( 'test-only-conditional', 'example.com', array(), null );
		wp_script_add_data( 'test-only-conditional', 'conditional', 'gt IE 7' );
		$expected = "<!--[if gt IE 7]>\n<script type='text/javascript' src='http://example.com' id='test-only-conditional-js'></script>\n<![endif]-->\n";

		// Go!
		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Testing `wp_script_add_data` with both the data & conditional keys.
	 *
	 * @ticket 16024
	 */
	public function test_wp_script_add_data_with_data_and_conditional_keys() {
		// Enqueue and add data plus conditional comments for both.
		wp_enqueue_script( 'test-conditional-with-data', 'example.com', array(), null );
		wp_script_add_data( 'test-conditional-with-data', 'data', 'testing' );
		wp_script_add_data( 'test-conditional-with-data', 'conditional', 'lt IE 9' );
		$expected  = "<!--[if lt IE 9]>\n<script type='text/javascript' id='test-conditional-with-data-js-extra'>\n/* <![CDATA[ */\ntesting\n/* ]]> */\n</script>\n<![endif]-->\n";
		$expected .= "<!--[if lt IE 9]>\n<script type='text/javascript' src='http://example.com' id='test-conditional-with-data-js'></script>\n<![endif]-->\n";

		// Go!
		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Testing `wp_script_add_data` with an anvalid key.
	 *
	 * @ticket 16024
	 */
	public function test_wp_script_add_data_with_invalid_key() {
		// Enqueue and add an invalid key.
		wp_enqueue_script( 'test-invalid', 'example.com', array(), null );
		wp_script_add_data( 'test-invalid', 'invalid', 'testing' );
		$expected = "<script type='text/javascript' src='http://example.com' id='test-invalid-js'></script>\n";

		// Go!
		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );

		// No scripts left to print.
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Testing 'wp_register_script' return boolean success/failure value.
	 *
	 * @ticket 31126
	 */
	public function test_wp_register_script() {
		$this->assertTrue( wp_register_script( 'duplicate-handler', 'http://example.com' ) );
		$this->assertFalse( wp_register_script( 'duplicate-handler', 'http://example.com' ) );
	}

	/**
	 * @ticket 35229
	 */
	public function test_wp_register_script_with_handle_without_source() {
		$expected  = "<script type='text/javascript' src='http://example.com?ver=1' id='handle-one-js'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com?ver=2' id='handle-two-js'></script>\n";

		wp_register_script( 'handle-one', 'http://example.com', array(), 1 );
		wp_register_script( 'handle-two', 'http://example.com', array(), 2 );
		wp_register_script( 'handle-three', false, array( 'handle-one', 'handle-two' ) );

		wp_enqueue_script( 'handle-three' );

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 35643
	 */
	public function test_wp_enqueue_script_footer_alias() {
		wp_register_script( 'foo', false, array( 'bar', 'baz' ), '1.0', true );
		wp_register_script( 'bar', home_url( 'bar.js' ), array(), '1.0', true );
		wp_register_script( 'baz', home_url( 'baz.js' ), array(), '1.0', true );

		wp_enqueue_script( 'foo' );

		$header = get_echo( 'wp_print_head_scripts' );
		$footer = get_echo( 'wp_print_footer_scripts' );

		$this->assertEmpty( $header );
		$this->assertStringContainsString( home_url( 'bar.js' ), $footer );
		$this->assertStringContainsString( home_url( 'baz.js' ), $footer );
	}

	/**
	 * Test mismatch of groups in dependencies outputs all scripts in right order.
	 *
	 * @ticket 35873
	 *
	 * @covers WP_Dependencies::add
	 * @covers WP_Dependencies::enqueue
	 * @covers WP_Dependencies::do_items
	 */
	public function test_group_mismatch_in_deps() {
		$scripts = new WP_Scripts();
		$scripts->add( 'one', 'one', array(), 'v1', 1 );
		$scripts->add( 'two', 'two', array( 'one' ) );
		$scripts->add( 'three', 'three', array( 'two' ), 'v1', 1 );

		$scripts->enqueue( array( 'three' ) );

		$this->expectOutputRegex( '/^(?:<script[^>]+><\/script>\\n){7}$/' );

		$scripts->do_items( false, 0 );
		$this->assertContains( 'one', $scripts->done );
		$this->assertContains( 'two', $scripts->done );
		$this->assertNotContains( 'three', $scripts->done );

		$scripts->do_items( false, 1 );
		$this->assertContains( 'one', $scripts->done );
		$this->assertContains( 'two', $scripts->done );
		$this->assertContains( 'three', $scripts->done );

		$scripts = new WP_Scripts();
		$scripts->add( 'one', 'one', array(), 'v1', 1 );
		$scripts->add( 'two', 'two', array( 'one' ), 'v1', 1 );
		$scripts->add( 'three', 'three', array( 'one' ) );
		$scripts->add( 'four', 'four', array( 'two', 'three' ), 'v1', 1 );

		$scripts->enqueue( array( 'four' ) );

		$scripts->do_items( false, 0 );
		$this->assertContains( 'one', $scripts->done );
		$this->assertNotContains( 'two', $scripts->done );
		$this->assertContains( 'three', $scripts->done );
		$this->assertNotContains( 'four', $scripts->done );

		$scripts->do_items( false, 1 );
		$this->assertContains( 'one', $scripts->done );
		$this->assertContains( 'two', $scripts->done );
		$this->assertContains( 'three', $scripts->done );
		$this->assertContains( 'four', $scripts->done );
	}

	/**
	 * @ticket 35873
	 */
	public function test_wp_register_script_with_dependencies_in_head_and_footer() {
		wp_register_script( 'parent', '/parent.js', array( 'child-head' ), null, true );            // In footer.
		wp_register_script( 'child-head', '/child-head.js', array( 'child-footer' ), null, false ); // In head.
		wp_register_script( 'child-footer', '/child-footer.js', array(), null, true );              // In footer.

		wp_enqueue_script( 'parent' );

		$header = get_echo( 'wp_print_head_scripts' );
		$footer = get_echo( 'wp_print_footer_scripts' );

		$expected_header  = "<script type='text/javascript' src='/child-footer.js' id='child-footer-js'></script>\n";
		$expected_header .= "<script type='text/javascript' src='/child-head.js' id='child-head-js'></script>\n";
		$expected_footer  = "<script type='text/javascript' src='/parent.js' id='parent-js'></script>\n";

		$this->assertSame( $expected_header, $header );
		$this->assertSame( $expected_footer, $footer );
	}

	/**
	 * @ticket 35956
	 */
	public function test_wp_register_script_with_dependencies_in_head_and_footer_in_reversed_order() {
		wp_register_script( 'child-head', '/child-head.js', array(), null, false );                      // In head.
		wp_register_script( 'child-footer', '/child-footer.js', array(), null, true );                   // In footer.
		wp_register_script( 'parent', '/parent.js', array( 'child-head', 'child-footer' ), null, true ); // In footer.

		wp_enqueue_script( 'parent' );

		$header = get_echo( 'wp_print_head_scripts' );
		$footer = get_echo( 'wp_print_footer_scripts' );

		$expected_header  = "<script type='text/javascript' src='/child-head.js' id='child-head-js'></script>\n";
		$expected_footer  = "<script type='text/javascript' src='/child-footer.js' id='child-footer-js'></script>\n";
		$expected_footer .= "<script type='text/javascript' src='/parent.js' id='parent-js'></script>\n";

		$this->assertSame( $expected_header, $header );
		$this->assertSame( $expected_footer, $footer );
	}

	/**
	 * @ticket 35956
	 */
	public function test_wp_register_script_with_dependencies_in_head_and_footer_in_reversed_order_and_two_parent_scripts() {
		wp_register_script( 'grandchild-head', '/grandchild-head.js', array(), null, false );             // In head.
		wp_register_script( 'child-head', '/child-head.js', array(), null, false );                       // In head.
		wp_register_script( 'child-footer', '/child-footer.js', array( 'grandchild-head' ), null, true ); // In footer.
		wp_register_script( 'child2-head', '/child2-head.js', array(), null, false );                     // In head.
		wp_register_script( 'child2-footer', '/child2-footer.js', array(), null, true );                  // In footer.
		wp_register_script( 'parent-footer', '/parent-footer.js', array( 'child-head', 'child-footer', 'child2-head', 'child2-footer' ), null, true ); // In footer.
		wp_register_script( 'parent-header', '/parent-header.js', array( 'child-head' ), null, false );   // In head.

		wp_enqueue_script( 'parent-footer' );
		wp_enqueue_script( 'parent-header' );

		$header = get_echo( 'wp_print_head_scripts' );
		$footer = get_echo( 'wp_print_footer_scripts' );

		$expected_header  = "<script type='text/javascript' src='/child-head.js' id='child-head-js'></script>\n";
		$expected_header .= "<script type='text/javascript' src='/grandchild-head.js' id='grandchild-head-js'></script>\n";
		$expected_header .= "<script type='text/javascript' src='/child2-head.js' id='child2-head-js'></script>\n";
		$expected_header .= "<script type='text/javascript' src='/parent-header.js' id='parent-header-js'></script>\n";

		$expected_footer  = "<script type='text/javascript' src='/child-footer.js' id='child-footer-js'></script>\n";
		$expected_footer .= "<script type='text/javascript' src='/child2-footer.js' id='child2-footer-js'></script>\n";
		$expected_footer .= "<script type='text/javascript' src='/parent-footer.js' id='parent-footer-js'></script>\n";

		$this->assertSame( $expected_header, $header );
		$this->assertSame( $expected_footer, $footer );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_returns_bool() {
		$this->assertFalse( wp_add_inline_script( 'test-example', 'console.log("before");', 'before' ) );
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		$this->assertTrue( wp_add_inline_script( 'test-example', 'console.log("before");', 'before' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_unknown_handle() {
		$this->assertFalse( wp_add_inline_script( 'test-invalid', 'console.log("before");', 'before' ) );
		$this->assertSame( '', get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_before() {
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );

		$expected  = "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_after() {
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected  = "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_before_and_after() {
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected  = "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 44551
	 */
	public function test_wp_add_inline_script_before_for_handle_without_source() {
		wp_register_script( 'test-example', '' );
		wp_enqueue_script( 'test-example' );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );

		$expected = "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 44551
	 */
	public function test_wp_add_inline_script_after_for_handle_without_source() {
		wp_register_script( 'test-example', '' );
		wp_enqueue_script( 'test-example' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected = "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 44551
	 */
	public function test_wp_add_inline_script_before_and_after_for_handle_without_source() {
		wp_register_script( 'test-example', '' );
		wp_enqueue_script( 'test-example' );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected  = "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_multiple() {
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected  = "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_localized_data_is_added_first() {
		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_localize_script( 'test-example', 'testExample', array( 'foo' => 'bar' ) );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		$expected  = "<script type='text/javascript' id='test-example-js-extra'>\n/* <![CDATA[ */\nvar testExample = {\"foo\":\"bar\"};\n/* ]]> */\n</script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_before_with_concat() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/directory/' );

		wp_enqueue_script( 'one', '/directory/one.js' );
		wp_enqueue_script( 'two', '/directory/two.js' );
		wp_enqueue_script( 'three', '/directory/three.js' );

		wp_add_inline_script( 'one', 'console.log("before one");', 'before' );
		wp_add_inline_script( 'two', 'console.log("before two");', 'before' );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' id='one-js-before'>\nconsole.log(\"before one\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/directory/one.js?ver={$ver}' id='one-js'></script>\n";
		$expected .= "<script type='text/javascript' id='two-js-before'>\nconsole.log(\"before two\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/directory/two.js?ver={$ver}' id='two-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/directory/three.js?ver={$ver}' id='three-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_before_with_concat2() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/directory/' );

		wp_enqueue_script( 'one', '/directory/one.js' );
		wp_enqueue_script( 'two', '/directory/two.js' );
		wp_enqueue_script( 'three', '/directory/three.js' );

		wp_add_inline_script( 'one', 'console.log("before one");', 'before' );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' id='one-js-before'>\nconsole.log(\"before one\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/directory/one.js?ver={$ver}' id='one-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/directory/two.js?ver={$ver}' id='two-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/directory/three.js?ver={$ver}' id='three-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_after_with_concat() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/directory/' );

		wp_enqueue_script( 'one', '/directory/one.js' );
		wp_enqueue_script( 'two', '/directory/two.js' );
		wp_enqueue_script( 'three', '/directory/three.js' );
		wp_enqueue_script( 'four', '/directory/four.js' );

		wp_add_inline_script( 'two', 'console.log("after two");' );
		wp_add_inline_script( 'three', 'console.log("after three");' );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=one&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' src='/directory/two.js?ver={$ver}' id='two-js'></script>\n";
		$expected .= "<script type='text/javascript' id='two-js-after'>\nconsole.log(\"after two\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/directory/three.js?ver={$ver}' id='three-js'></script>\n";
		$expected .= "<script type='text/javascript' id='three-js-after'>\nconsole.log(\"after three\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/directory/four.js?ver={$ver}' id='four-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 14853
	 */
	public function test_wp_add_inline_script_after_and_before_with_concat_and_conditional() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/wp-admin/js/', '/wp-includes/js/' ); // Default dirs as in wp-includes/script-loader.php.

		$expected_localized  = "<!--[if gte IE 9]>\n";
		$expected_localized .= "<script type='text/javascript' id='test-example-js-extra'>\n/* <![CDATA[ */\nvar testExample = {\"foo\":\"bar\"};\n/* ]]> */\n</script>\n";
		$expected_localized .= "<![endif]-->\n";

		$expected  = "<!--[if gte IE 9]>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";
		$expected .= "<![endif]-->\n";

		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_localize_script( 'test-example', 'testExample', array( 'foo' => 'bar' ) );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_add_inline_script( 'test-example', 'console.log("after");' );
		wp_script_add_data( 'test-example', 'conditional', 'gte IE 9' );

		$this->assertSame( $expected_localized, get_echo( 'wp_print_scripts' ) );
		$this->assertSame( $expected, $wp_scripts->print_html );
		$this->assertTrue( $wp_scripts->do_concat );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_after_with_concat_and_core_dependency() {
		global $wp_scripts;

		wp_default_scripts( $wp_scripts );

		$wp_scripts->base_url  = '';
		$wp_scripts->do_concat = true;

		$ver      = self::$asset_version;
		$expected = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core,jquery-migrate&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";

		wp_enqueue_script( 'test-example', 'http://example.com', array( 'jquery' ), null );
		wp_add_inline_script( 'test-example', 'console.log("after");' );

		wp_print_scripts();
		$print_scripts = get_echo( '_print_scripts' );

		$this->assertSame( $expected, $print_scripts );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_after_with_concat_and_conditional_and_core_dependency() {
		global $wp_scripts;

		wp_default_scripts( $wp_scripts );

		$wp_scripts->base_url  = '';
		$wp_scripts->do_concat = true;

		$ver      = self::$asset_version;
		$expected = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core,jquery-migrate&amp;ver={$ver}'></script>\n";
		$expected .= "<!--[if gte IE 9]>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-after'>\nconsole.log(\"after\");\n</script>\n";
		$expected .= "<![endif]-->\n";

		wp_enqueue_script( 'test-example', 'http://example.com', array( 'jquery' ), null );
		wp_add_inline_script( 'test-example', 'console.log("after");' );
		wp_script_add_data( 'test-example', 'conditional', 'gte IE 9' );

		wp_print_scripts();
		$print_scripts = get_echo( '_print_scripts' );

		$this->assertSame( $expected, $print_scripts );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_before_with_concat_and_core_dependency() {
		global $wp_scripts;

		wp_default_scripts( $wp_scripts );
		wp_default_packages( $wp_scripts );

		$wp_scripts->base_url  = '';
		$wp_scripts->do_concat = true;

		$ver      = self::$asset_version;
		$expected = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core,jquery-migrate&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";

		wp_enqueue_script( 'test-example', 'http://example.com', array( 'jquery' ), null );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );

		wp_print_scripts();
		$print_scripts = get_echo( '_print_scripts' );

		$this->assertSame( $expected, $print_scripts );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_before_after_concat_with_core_dependency() {
		global $wp_scripts;

		wp_default_scripts( $wp_scripts );
		wp_default_packages( $wp_scripts );

		$wp_scripts->base_url  = '';
		$wp_scripts->do_concat = true;

		$ver       = self::$asset_version;
		$suffix    = wp_scripts_get_suffix();
		$expected  = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core,jquery-migrate,wp-dom-ready,wp-hooks&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example-js-before'>\nconsole.log(\"before\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/dist/i18n.js?ver={$ver}' id='wp-i18n-js'></script>\n";
		$expected .= "<script type='text/javascript' id='wp-i18n-js-after'>\n";
		$expected .= "wp.i18n.setLocaleData( { 'text direction\u0004ltr': [ 'ltr' ] } );\n";
		$expected .= "</script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/dist/a11y.js?ver={$ver}' id='wp-a11y-js'></script>\n";
		$expected .= "<script type='text/javascript' src='http://example2.com' id='test-example2-js'></script>\n";
		$expected .= "<script type='text/javascript' id='test-example2-js-after'>\nconsole.log(\"after\");\n</script>\n";

		wp_enqueue_script( 'test-example', 'http://example.com', array( 'jquery' ), null );
		wp_add_inline_script( 'test-example', 'console.log("before");', 'before' );
		wp_enqueue_script( 'test-example2', 'http://example2.com', array( 'wp-a11y' ), null );
		wp_add_inline_script( 'test-example2', 'console.log("after");', 'after' );

		// Effectively ignore the output until retrieving it later via `getActualOutput()`.
		$this->expectOutputRegex( '`.`' );

		wp_print_scripts();
		_print_scripts();
		$print_scripts = $this->getActualOutput();

		$this->assertSameIgnoreEOL( $expected, $print_scripts );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_customize_dependency() {
		global $wp_scripts;

		wp_default_scripts( $wp_scripts );
		wp_default_packages( $wp_scripts );

		$wp_scripts->base_url  = '';
		$wp_scripts->do_concat = true;

		$expected_tail  = "<script type='text/javascript' src='/customize-dependency.js' id='customize-dependency-js'></script>\n";
		$expected_tail .= "<script type='text/javascript' id='customize-dependency-js-after'>\n";
		$expected_tail .= "tryCustomizeDependency()\n";
		$expected_tail .= "</script>\n";

		$handle = 'customize-dependency';
		wp_enqueue_script( $handle, '/customize-dependency.js', array( 'customize-controls' ), null );
		wp_add_inline_script( $handle, 'tryCustomizeDependency()' );

		// Effectively ignore the output until retrieving it later via `getActualOutput()`.
		$this->expectOutputRegex( '`.`' );

		wp_print_scripts();
		_print_scripts();
		$print_scripts = $this->getActualOutput();

		$tail = substr( $print_scripts, strrpos( $print_scripts, "<script type='text/javascript' src='/customize-dependency.js' id='customize-dependency-js'>" ) );
		$this->assertSame( $expected_tail, $tail );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_after_for_core_scripts_with_concat_is_limited_and_falls_back_to_no_concat() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/wp-admin/js/', '/wp-includes/js/' ); // Default dirs as in wp-includes/script-loader.php.

		wp_enqueue_script( 'one', '/wp-includes/js/script.js' );
		wp_enqueue_script( 'two', '/wp-includes/js/script2.js', array( 'one' ) );
		wp_add_inline_script( 'one', 'console.log("after one");', 'after' );
		wp_enqueue_script( 'three', '/wp-includes/js/script3.js' );
		wp_enqueue_script( 'four', '/wp-includes/js/script4.js' );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' src='/wp-includes/js/script.js?ver={$ver}' id='one-js'></script>\n";
		$expected .= "<script type='text/javascript' id='one-js-after'>\nconsole.log(\"after one\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/script2.js?ver={$ver}' id='two-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/script3.js?ver={$ver}' id='three-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/script4.js?ver={$ver}' id='four-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * @ticket 36392
	 */
	public function test_wp_add_inline_script_before_third_core_script_prints_two_concat_scripts() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/wp-admin/js/', '/wp-includes/js/' ); // Default dirs as in wp-includes/script-loader.php.

		wp_enqueue_script( 'one', '/wp-includes/js/script.js' );
		wp_enqueue_script( 'two', '/wp-includes/js/script2.js', array( 'one' ) );
		wp_enqueue_script( 'three', '/wp-includes/js/script3.js' );
		wp_add_inline_script( 'three', 'console.log("before three");', 'before' );
		wp_enqueue_script( 'four', '/wp-includes/js/script4.js' );

		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=one,two&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' id='three-js-before'>\nconsole.log(\"before three\");\n</script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/script3.js?ver={$ver}' id='three-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/wp-includes/js/script4.js?ver={$ver}' id='four-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Testing `wp_enqueue_code_editor` with file path.
	 *
	 * @ticket 41871
	 * @covers ::wp_enqueue_code_editor
	 */
	public function test_wp_enqueue_code_editor_when_php_file_will_be_passed() {
		$real_file              = WP_PLUGIN_DIR . '/hello.php';
		$wp_enqueue_code_editor = wp_enqueue_code_editor( array( 'file' => $real_file ) );
		$this->assertNonEmptyMultidimensionalArray( $wp_enqueue_code_editor );

		$this->assertSameSets( array( 'codemirror', 'csslint', 'jshint', 'htmlhint' ), array_keys( $wp_enqueue_code_editor ) );
		$this->assertSameSets(
			array(
				'autoCloseBrackets',
				'autoCloseTags',
				'continueComments',
				'direction',
				'extraKeys',
				'indentUnit',
				'indentWithTabs',
				'inputStyle',
				'lineNumbers',
				'lineWrapping',
				'matchBrackets',
				'matchTags',
				'mode',
				'styleActiveLine',
				'gutters',
			),
			array_keys( $wp_enqueue_code_editor['codemirror'] )
		);
		$this->assertEmpty( $wp_enqueue_code_editor['codemirror']['gutters'] );

		$this->assertSameSets(
			array(
				'errors',
				'box-model',
				'display-property-grouping',
				'duplicate-properties',
				'known-properties',
				'outline-none',
			),
			array_keys( $wp_enqueue_code_editor['csslint'] )
		);

		$this->assertSameSets(
			array(
				'boss',
				'curly',
				'eqeqeq',
				'eqnull',
				'es3',
				'expr',
				'immed',
				'noarg',
				'nonbsp',
				'onevar',
				'quotmark',
				'trailing',
				'undef',
				'unused',
				'browser',
				'globals',
			),
			array_keys( $wp_enqueue_code_editor['jshint'] )
		);

		$this->assertSameSets(
			array(
				'tagname-lowercase',
				'attr-lowercase',
				'attr-value-double-quotes',
				'doctype-first',
				'tag-pair',
				'spec-char-escape',
				'id-unique',
				'src-not-empty',
				'attr-no-duplication',
				'alt-require',
				'space-tab-mixed-disabled',
				'attr-unsafe-chars',
			),
			array_keys( $wp_enqueue_code_editor['htmlhint'] )
		);
	}

	/**
	 * Testing `wp_enqueue_code_editor` with `compact`.
	 *
	 * @ticket 41871
	 * @covers ::wp_enqueue_code_editor
	 */
	public function test_wp_enqueue_code_editor_when_generated_array_by_compact_will_be_passed() {
		$file                   = '';
		$wp_enqueue_code_editor = wp_enqueue_code_editor( compact( 'file' ) );
		$this->assertNonEmptyMultidimensionalArray( $wp_enqueue_code_editor );

		$this->assertSameSets( array( 'codemirror', 'csslint', 'jshint', 'htmlhint' ), array_keys( $wp_enqueue_code_editor ) );
		$this->assertSameSets(
			array(
				'continueComments',
				'direction',
				'extraKeys',
				'indentUnit',
				'indentWithTabs',
				'inputStyle',
				'lineNumbers',
				'lineWrapping',
				'mode',
				'styleActiveLine',
				'gutters',
			),
			array_keys( $wp_enqueue_code_editor['codemirror'] )
		);
		$this->assertEmpty( $wp_enqueue_code_editor['codemirror']['gutters'] );

		$this->assertSameSets(
			array(
				'errors',
				'box-model',
				'display-property-grouping',
				'duplicate-properties',
				'known-properties',
				'outline-none',
			),
			array_keys( $wp_enqueue_code_editor['csslint'] )
		);

		$this->assertSameSets(
			array(
				'boss',
				'curly',
				'eqeqeq',
				'eqnull',
				'es3',
				'expr',
				'immed',
				'noarg',
				'nonbsp',
				'onevar',
				'quotmark',
				'trailing',
				'undef',
				'unused',
				'browser',
				'globals',
			),
			array_keys( $wp_enqueue_code_editor['jshint'] )
		);

		$this->assertSameSets(
			array(
				'tagname-lowercase',
				'attr-lowercase',
				'attr-value-double-quotes',
				'doctype-first',
				'tag-pair',
				'spec-char-escape',
				'id-unique',
				'src-not-empty',
				'attr-no-duplication',
				'alt-require',
				'space-tab-mixed-disabled',
				'attr-unsafe-chars',
			),
			array_keys( $wp_enqueue_code_editor['htmlhint'] )
		);
	}

	/**
	 * Testing `wp_enqueue_code_editor` with `array_merge`.
	 *
	 * @ticket 41871
	 * @covers ::wp_enqueue_code_editor
	 */
	public function test_wp_enqueue_code_editor_when_generated_array_by_array_merge_will_be_passed() {
		$wp_enqueue_code_editor = wp_enqueue_code_editor(
			array_merge(
				array(
					'type'       => 'text/css',
					'codemirror' => array(
						'indentUnit' => 2,
						'tabSize'    => 2,
					),
				),
				array()
			)
		);

		$this->assertNonEmptyMultidimensionalArray( $wp_enqueue_code_editor );

		$this->assertSameSets( array( 'codemirror', 'csslint', 'jshint', 'htmlhint' ), array_keys( $wp_enqueue_code_editor ) );
		$this->assertSameSets(
			array(
				'autoCloseBrackets',
				'continueComments',
				'direction',
				'extraKeys',
				'gutters',
				'indentUnit',
				'indentWithTabs',
				'inputStyle',
				'lineNumbers',
				'lineWrapping',
				'lint',
				'matchBrackets',
				'mode',
				'styleActiveLine',
				'tabSize',
			),
			array_keys( $wp_enqueue_code_editor['codemirror'] )
		);

		$this->assertSameSets(
			array(
				'errors',
				'box-model',
				'display-property-grouping',
				'duplicate-properties',
				'known-properties',
				'outline-none',
			),
			array_keys( $wp_enqueue_code_editor['csslint'] )
		);

		$this->assertSameSets(
			array(
				'boss',
				'curly',
				'eqeqeq',
				'eqnull',
				'es3',
				'expr',
				'immed',
				'noarg',
				'nonbsp',
				'onevar',
				'quotmark',
				'trailing',
				'undef',
				'unused',
				'browser',
				'globals',
			),
			array_keys( $wp_enqueue_code_editor['jshint'] )
		);

		$this->assertSameSets(
			array(
				'tagname-lowercase',
				'attr-lowercase',
				'attr-value-double-quotes',
				'doctype-first',
				'tag-pair',
				'spec-char-escape',
				'id-unique',
				'src-not-empty',
				'attr-no-duplication',
				'alt-require',
				'space-tab-mixed-disabled',
				'attr-unsafe-chars',
			),
			array_keys( $wp_enqueue_code_editor['htmlhint'] )
		);
	}

	/**
	 * Testing `wp_enqueue_code_editor` with `array`.
	 *
	 * @ticket 41871
	 * @covers ::wp_enqueue_code_editor
	 */
	public function test_wp_enqueue_code_editor_when_simple_array_will_be_passed() {
		$wp_enqueue_code_editor = wp_enqueue_code_editor(
			array(
				'type'       => 'text/css',
				'codemirror' => array(
					'indentUnit' => 2,
					'tabSize'    => 2,
				),
			)
		);

		$this->assertNonEmptyMultidimensionalArray( $wp_enqueue_code_editor );

		$this->assertSameSets( array( 'codemirror', 'csslint', 'jshint', 'htmlhint' ), array_keys( $wp_enqueue_code_editor ) );
		$this->assertSameSets(
			array(
				'autoCloseBrackets',
				'continueComments',
				'direction',
				'extraKeys',
				'gutters',
				'indentUnit',
				'indentWithTabs',
				'inputStyle',
				'lineNumbers',
				'lineWrapping',
				'lint',
				'matchBrackets',
				'mode',
				'styleActiveLine',
				'tabSize',
			),
			array_keys( $wp_enqueue_code_editor['codemirror'] )
		);

		$this->assertSameSets(
			array(
				'errors',
				'box-model',
				'display-property-grouping',
				'duplicate-properties',
				'known-properties',
				'outline-none',
			),
			array_keys( $wp_enqueue_code_editor['csslint'] )
		);

		$this->assertSameSets(
			array(
				'boss',
				'curly',
				'eqeqeq',
				'eqnull',
				'es3',
				'expr',
				'immed',
				'noarg',
				'nonbsp',
				'onevar',
				'quotmark',
				'trailing',
				'undef',
				'unused',
				'browser',
				'globals',
			),
			array_keys( $wp_enqueue_code_editor['jshint'] )
		);

		$this->assertSameSets(
			array(
				'tagname-lowercase',
				'attr-lowercase',
				'attr-value-double-quotes',
				'doctype-first',
				'tag-pair',
				'spec-char-escape',
				'id-unique',
				'src-not-empty',
				'attr-no-duplication',
				'alt-require',
				'space-tab-mixed-disabled',
				'attr-unsafe-chars',
			),
			array_keys( $wp_enqueue_code_editor['htmlhint'] )
		);
	}

	/**
	 * @ticket 52534
	 * @covers ::wp_localize_script
	 *
	 * @dataProvider data_wp_localize_script_data_formats
	 *
	 * @param mixed  $l10n_data Localization data passed to wp_localize_script().
	 * @param string $expected  Expected transformation of localization data.
	 */
	public function test_wp_localize_script_data_formats( $l10n_data, $expected ) {
		if ( ! is_array( $l10n_data ) ) {
			$this->setExpectedIncorrectUsage( 'WP_Scripts::localize' );
		}

		wp_enqueue_script( 'test-example', 'example.com', array(), null );
		wp_localize_script( 'test-example', 'testExample', $l10n_data );

		$expected  = "<script type='text/javascript' id='test-example-js-extra'>\n/* <![CDATA[ */\nvar testExample = {$expected};\n/* ]]> */\n</script>\n";
		$expected .= "<script type='text/javascript' src='http://example.com' id='test-example-js'></script>\n";

		$this->assertSame( $expected, get_echo( 'wp_print_scripts' ) );
	}

	/**
	 * Data provider for test_wp_localize_script_data_formats().
	 *
	 * @return array[] {
	 *     Array of arguments for test.
	 *
	 *     @type mixed  $l10n_data Localization data passed to wp_localize_script().
	 *     @type string $expected  Expected transformation of localization data.
	 * }
	 */
	public function data_wp_localize_script_data_formats() {
		return array(
			// Officially supported formats.
			array( array( 'array value, no key' ), '["array value, no key"]' ),
			array( array( 'foo' => 'bar' ), '{"foo":"bar"}' ),
			array( array( 'foo' => array( 'bar' => 'foobar' ) ), '{"foo":{"bar":"foobar"}}' ),
			array( array( 'foo' => 6.6 ), '{"foo":"6.6"}' ),
			array( array( 'foo' => 6 ), '{"foo":"6"}' ),
			array( array(), '[]' ),

			// Unofficially supported format.
			array( 'string', '"string"' ),

			// Unsupported formats.
			array( 1.5, '1.5' ),
			array( 1, '1' ),
			array( false, '[""]' ),
			array( null, 'null' ),
		);
	}

	/**
	 * @ticket 55628
	 * @covers ::wp_set_script_translations
	 */
	public function test_wp_external_wp_i18n_print_order() {
		global $wp_scripts;

		$wp_scripts->do_concat    = true;
		$wp_scripts->default_dirs = array( '/default/' );

		// wp-i18n script in a non-default directory.
		wp_register_script( 'wp-i18n', '/plugins/wp-i18n.js', array(), null );
		// Script in default dir that's going to be concatenated.
		wp_enqueue_script( 'jquery-core', '/default/jquery-core.js', array(), null );
		// Script in default dir that depends on wp-i18n.
		wp_enqueue_script( 'common', '/default/common.js', array(), null );
		wp_set_script_translations( 'common' );

		$print_scripts = get_echo(
			function () {
				wp_print_scripts();
				_print_scripts();
			}
		);

		// The non-default script should end concatenation and maintain order.
		$ver       = self::$asset_version;
		$expected  = "<script type='text/javascript' src='/wp-admin/load-scripts.php?c=0&amp;load%5Bchunk_0%5D=jquery-core&amp;ver={$ver}'></script>\n";
		$expected .= "<script type='text/javascript' src='/plugins/wp-i18n.js' id='wp-i18n-js'></script>\n";
		$expected .= "<script type='text/javascript' src='/default/common.js' id='common-js'></script>\n";

		$this->assertSame( $expected, $print_scripts );
	}
}
