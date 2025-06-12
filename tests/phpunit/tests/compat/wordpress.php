<?php

/**
 * @group compat
 *
 * @covers WordPress Block Editor compatibility
 */
class Tests_Compat_wordpress extends WP_UnitTestCase {

	public static function wpSetUpBeforeClass() {
		// Use 'Troubleshooting' mode for deeper testing
		update_option( 'blocks_compatibility_level', '2' );
		WP_Compat::$blocks_compatibility_level = 2;
	}

	public static function wpTearDownAfterClass() {
		update_option( 'blocks_compatibility_level', '1' );
		WP_Compat::$blocks_compatibility_level = 1;
	}

	/**
	* Test blocks_compatibility_level value
	*/
	public function test_default_blocks_compatibility_level() {
		$option = get_option( 'blocks_compatibility_level' );
		$this->assertSame( $option, '2' );
		$this->assertSame( WP_Compat::$blocks_compatibility_level, 2 );
	}

	/**
	 * Test that polyfill functions are available
	 */
	public function test_defined_polyfills() {
		$this->assertTrue( function_exists( 'register_block_type' ) );
		$this->assertTrue( function_exists( 'unregister_block_type' ) );
		$this->assertTrue( function_exists( 'register_block_type_from_metadata' ) );
		$this->assertTrue( function_exists( 'has_block' ) );
		$this->assertTrue( function_exists( 'has_blocks' ) );
		$this->assertTrue( function_exists( 'register_block_pattern' ) );
		$this->assertTrue( function_exists( 'unregister_block_pattern' ) );
		$this->assertTrue( function_exists( 'register_block_pattern_category' ) );
		$this->assertTrue( function_exists( 'unregister_block_pattern_category' ) );
		$this->assertTrue( function_exists( 'wp_is_block_theme' ) );
		$this->assertTrue( function_exists( 'parse_blocks' ) );
		$this->assertTrue( function_exists( 'get_dynamic_block_names' ) );
		$this->assertTrue( function_exists( 'get_block_theme_folders' ) );
	}

	/**
	 * Test that functions that may cause errors are not polyfilled
	 */
	public function test_undefined_polyfills() {
		$this->assertFalse( function_exists( 'wp_print_community_events_markup' ), 'Do not break FAIR plugin. See https://github.com/fairpm/fair-plugin/pull/65' );
	}

	/**
	 * Test the WP_Block_Type class exists
	 */
	public function test_class_polyfills() {
		$this->assertTrue( class_exists( 'WP_Block_Type' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__set' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__get' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__isset' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__unset' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__call' ) );
		$this->assertTrue( method_exists( 'WP_Block_Type', '__callstatic' ) );
		$this->assertFalse( method_exists( 'WP_Block_Type', 'get_attributes' ) );
		$this->assertFalse( method_exists( 'WP_Block_Type', 'render' ) );
	}

	/**
	 * Test block functions in theme is detected
	 */
	public function test_block_theme_detected() {
		$setting = get_option( 'theme_using_blocks' );
		$this->assertFalse( $setting );

		switch_theme( 'block-theme' );

		$theme = wp_get_theme();
		$this->assertSame( $theme->stylesheet, 'block-theme' );

		require_once get_stylesheet_directory() . '/functions.php';
		block_theme_register_block();

		$setting = get_option( 'theme_using_blocks' );
		$this->assertSame( $setting, '1' );

		switch_theme( WP_DEFAULT_THEME );
	}

	/**
	 * Test block functions in plugin is detected
	 */
	public function test_block_plugin_detected() {
		$setting = get_option( 'plugins_using_blocks' );
		$this->assertFalse( $setting );

		activate_plugin( 'block-plugin.php' );

		block_plugin_register_block();

		$setting = get_option( 'plugins_using_blocks' );
		$this->assertNotFalse( $setting );
		$this->assertTrue( array_key_exists( 'block-plugin.php', $setting ) );

		deactivate_plugins( 'block-plugin.php' );
		update_option( 'plugins_using_blocks', false );
	}
}
