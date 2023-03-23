<?php

/**
 * @group option
 */
class Tests_Option_SiteOption extends WP_UnitTestCase {
	public function __return_foo() {
		return 'foo';
	}

	/**
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_returns_false_if_option_does_not_exist() {
		$this->assertFalse( get_site_option( 'doesnotexist' ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::delete_site_option
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_returns_false_after_deletion() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		delete_site_option( $key );
		$this->assertFalse( get_site_option( $key ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_returns_value() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		$this->assertSame( $value, get_site_option( $key ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::update_site_option
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_returns_updated_value() {
		$key       = __FUNCTION__;
		$value     = __FUNCTION__ . '_1';
		$new_value = __FUNCTION__ . '_2';
		add_site_option( $key, $value );
		update_site_option( $key, $new_value );
		$this->assertSame( $new_value, get_site_option( $key ) );
	}

	/**
	 * @covers ::add_filter
	 * @covers ::get_site_option
	 * @covers ::remove_filter
	 */
	public function test_get_site_option_does_not_exist_returns_filtered_default_with_no_default_provided() {
		add_filter( 'default_site_option_doesnotexist', array( $this, '__return_foo' ) );
		$site_option = get_site_option( 'doesnotexist' );
		remove_filter( 'default_site_option_doesnotexist', array( $this, '__return_foo' ) );
		$this->assertSame( 'foo', $site_option );
	}

	/**
	 * @covers ::add_filter
	 * @covers ::get_site_option
	 * @covers ::remove_filter
	 */
	public function test_get_site_option_does_not_exist_returns_filtered_default_with_default_provided() {
		add_filter( 'default_site_option_doesnotexist', array( $this, '__return_foo' ) );
		$site_option = get_site_option( 'doesnotexist', 'bar' );
		remove_filter( 'default_site_option_doesnotexist', array( $this, '__return_foo' ) );
		$this->assertSame( 'foo', $site_option );
	}

	/**
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_does_not_exist_returns_provided_default() {
		$this->assertSame( 'bar', get_site_option( 'doesnotexist', 'bar' ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::get_site_option
	 */
	public function test_get_site_option_exists_does_not_return_provided_default() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		$this->assertSame( $value, get_site_option( $key, 'foo' ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::add_filter
	 * @covers ::get_site_option
	 * @covers ::remove_filter
	 */
	public function test_get_site_option_exists_does_not_return_filtered_default() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		add_filter( 'default_site_option_' . $key, array( $this, '__return_foo' ) );
		$site_option = get_site_option( $key );
		remove_filter( 'default_site_option_' . $key, array( $this, '__return_foo' ) );
		$this->assertSame( $value, $site_option );
	}

	/**
	 * @covers ::add_site_option
	 */
	public function test_add_site_option_returns_true_for_new_option() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		$this->assertTrue( add_site_option( $key, $value ) );
	}

	/**
	 * @covers ::add_site_option
	 */
	public function test_add_site_option_returns_false_for_existing_option() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		$this->assertFalse( add_site_option( $key, $value ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::update_site_option
	 */
	public function test_update_site_option_returns_false_for_same_value() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		$this->assertFalse( update_site_option( $key, $value ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::update_site_option
	 */
	public function test_update_site_option_returns_true_for_new_value() {
		$key       = 'key';
		$value     = 'value1';
		$new_value = 'value2';
		add_site_option( $key, $value );
		$this->assertTrue( update_site_option( $key, $new_value ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::delete_site_option
	 */
	public function test_delete_site_option_returns_true_if_option_exists() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		$this->assertTrue( delete_site_option( $key ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::delete_site_option
	 */
	public function test_delete_site_option_returns_false_if_option_does_not_exist() {
		$key   = __FUNCTION__;
		$value = __FUNCTION__;
		add_site_option( $key, $value );
		delete_site_option( $key );
		$this->assertFalse( delete_site_option( $key ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::get_site_option
	 */
	public function test_site_option_add_and_get_serialized_array() {
		$key   = __FUNCTION__;
		$value = array(
			'foo' => true,
			'bar' => true,
		);
		add_site_option( $key, $value );
		$this->assertSame( $value, get_site_option( $key ) );
	}

	/**
	 * @covers ::add_site_option
	 * @covers ::get_site_option
	 */
	public function test_site_option_add_and_get_serialized_object() {
		$key        = __FUNCTION__;
		$value      = new stdClass();
		$value->foo = true;
		$value->bar = true;
		add_site_option( $key, $value );
		$this->assertEquals( $value, get_site_option( $key ) );
	}

	/**
	 * Ensure update_site_option() will add options with false-y values.
	 *
	 * @ticket 15497
	 *
	 * @covers ::update_site_option
	 * @covers ::get_site_option
	 */
	public function test_update_adds_falsey_value() {
		$key   = __FUNCTION__;
		$value = 0;

		delete_site_option( $key );
		$this->assertTrue( update_site_option( $key, $value ) );
		$this->flush_cache(); // Ensure we're getting the value from the DB.
		$this->assertEquals( $value, get_site_option( $key ) );
	}

	/**
	 * Ensure get_site_option() doesn't cache the default value for non-existent options.
	 *
	 * @ticket 18955
	 *
	 * @covers ::get_site_option
	 */
	public function test_get_doesnt_cache_default_value() {
		$option  = __FUNCTION__;
		$default = 'a default';

		$this->assertSame( get_site_option( $option, $default ), $default );
		$this->assertFalse( get_site_option( $option ) );
	}
}
