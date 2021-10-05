<?php

/**
 * @group option
 */
class Tests_Option_Registration extends WP_UnitTestCase {
	public function test_register() {
		register_setting( 'test_group', 'test_option' );

		$registered = get_registered_settings();
		$this->assertArrayHasKey( 'test_option', $registered );

		$args = $registered['test_option'];
		$this->assertSame( 'test_group', $args['group'] );

		// Check defaults.
		$this->assertSame( 'string', $args['type'] );
		$this->assertFalse( $args['show_in_rest'] );
		$this->assertSame( '', $args['description'] );
	}

	public function test_register_with_callback() {
		register_setting( 'test_group', 'test_option', array( $this, 'filter_registered_setting' ) );

		$filtered = apply_filters( 'sanitize_option_test_option', 'smart', 'test_option', 'smart' );
		$this->assertSame( 'S-M-R-T', $filtered );
	}

	public function test_register_with_array() {
		register_setting( 'test_group', 'test_option', array(
			'sanitize_callback' => array( $this, 'filter_registered_setting' ),
		));

		$filtered = apply_filters( 'sanitize_option_test_option', 'smart', 'test_option', 'smart' );
		$this->assertSame( 'S-M-R-T', $filtered );
	}

	public function filter_registered_setting() {
		return 'S-M-R-T';
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38176
	 */
	public function test_register_with_default() {
		register_setting( 'test_group', 'test_default', array(
			'default' => 'Fuck Cancer'
		));

		$this->assertSame( 'Fuck Cancer', get_option( 'test_default' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38176
	 */
	public function test_register_with_default_override() {
		register_setting( 'test_group', 'test_default', array(
			'default' => 'Fuck Cancer'
		));

		$this->assertSame( 'Fuck Leukemia', get_option( 'test_default', 'Fuck Leukemia' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38930
	 */
	public function test_add_option_with_no_options_cache() {
		register_setting( 'test_group', 'test_default', array(
			'default' => 'My Default :)',
		));
		wp_cache_delete( 'notoptions', 'options' );
		$this->assertTrue( add_option( 'test_default', 'hello' ) );
		$this->assertSame( 'hello', get_option( 'test_default' ) );
	}

	/**
	 * @expectedDeprecated register_setting
	 */
	public function test_register_deprecated_group_misc() {
		register_setting( 'misc', 'test_option' );
	}

	/**
	 * @expectedDeprecated register_setting
	 */
	public function test_register_deprecated_group_privacy() {
		register_setting( 'privacy', 'test_option' );
	}
}
