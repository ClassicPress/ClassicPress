<?php

/**
 * @group option
 */
class Tests_Option_Transient extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		if ( wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is not in use.' );
		}
	}

	/**
	 * @covers ::get_transient
	 * @covers ::set_transient
	 * @covers ::delete_transient
	 */
	public function test_the_basics() {
		$key    = 'key1';
		$value  = 'value1';
		$value2 = 'value2';

		$this->assertFalse( get_transient( 'doesnotexist' ) );
		$this->assertTrue( set_transient( $key, $value ) );
		$this->assertSame( $value, get_transient( $key ) );
		$this->assertFalse( set_transient( $key, $value ) );
		$this->assertTrue( set_transient( $key, $value2 ) );
		$this->assertSame( $value2, get_transient( $key ) );
		$this->assertTrue( delete_transient( $key ) );
		$this->assertFalse( get_transient( $key ) );
		$this->assertFalse( delete_transient( $key ) );
	}

	/**
	 * @covers ::get_transient
	 * @covers ::set_transient
	 * @covers ::delete_transient
	 */
	public function test_serialized_data() {
		$key   = rand_str();
		$value = array(
			'foo' => true,
			'bar' => true,
		);

		$this->assertTrue( set_transient( $key, $value ) );
		$this->assertSame( $value, get_transient( $key ) );

		$value = (object) $value;
		$this->assertTrue( set_transient( $key, $value ) );
		$this->assertEquals( $value, get_transient( $key ) );
		$this->assertTrue( delete_transient( $key ) );
	}

	/**
	 * @ticket 22807
	 *
	 * @covers ::get_option
	 * @covers ::set_transient
	 * @covers ::update_option
	 */
	public function test_transient_data_with_timeout() {
		$key   = rand_str();
		$value = rand_str();

		$this->assertFalse( get_option( '_transient_timeout_' . $key ) );
		$now = time();

		$this->assertTrue( set_transient( $key, $value, 100 ) );

		// Ensure the transient timeout is set for 100-101 seconds in the future.
		$this->assertGreaterThanOrEqual( $now + 100, get_option( '_transient_timeout_' . $key ) );
		$this->assertLessThanOrEqual( $now + 101, get_option( '_transient_timeout_' . $key ) );

		// Update the timeout to a second in the past and watch the transient be invalidated.
		update_option( '_transient_timeout_' . $key, $now - 1 );
		$this->assertFalse( get_transient( $key ) );
	}

	/**
	 * @ticket 22807
	 *
	 * @covers ::set_transient
	 * @covers ::get_transient
	 * @covers ::get_option
	 * @covers ::update_option
	 */
	public function test_transient_add_timeout() {
		$key    = rand_str();
		$value  = rand_str();
		$value2 = rand_str();
		$this->assertTrue( set_transient( $key, $value ) );
		$this->assertSame( $value, get_transient( $key ) );

		$this->assertFalse( get_option( '_transient_timeout_' . $key ) );

		$now = time();
		// Add timeout to existing timeout-less transient.
		$this->assertTrue( set_transient( $key, $value2, 1 ) );
		$this->assertGreaterThanOrEqual( $now, get_option( '_transient_timeout_' . $key ) );

		update_option( '_transient_timeout_' . $key, $now - 1 );
		$this->assertFalse( get_transient( $key ) );
	}

	/**
	 * If get_option( $transient_timeout ) returns false, don't bother trying to delete the transient.
	 *
	 * @ticket 30380
	 *
	 * @covers ::set_transient
	 * @covers ::get_transient
	 */
	public function test_nonexistent_key_dont_delete_if_false() {
		// Create a bogus a transient.
		$key = 'test_transient';
		set_transient( $key, 'test', 60 * 10 );
		$this->assertSame( 'test', get_transient( $key ) );

		// Useful variables for tracking.
		$transient_timeout = '_transient_timeout_' . $key;

		// Mock an action for tracking action calls.
		$a = new MockAction();

		// Make sure the timeout option returns false.
		add_filter( 'option_' . $transient_timeout, '__return_false' );

		// Add some actions to make sure options are _not_ deleted.
		add_action( 'delete_option', array( $a, 'action' ) );

		// Act.
		get_transient( $key );

		// Make sure 'delete_option' was not called for both the transient and the timeout.
		$this->assertSame( 0, $a->get_call_count() );
	}

	/**
	 * @ticket 30380
	 *
	 * @covers ::set_transient
	 * @covers ::get_transient
	 */
	public function test_nonexistent_key_old_timeout() {
		// Create a transient.
		$key = 'test_transient';
		set_transient( $key, 'test', 60 * 10 );
		$this->assertSame( 'test', get_transient( $key ) );

		// Make sure the timeout option returns false.
		$timeout          = '_transient_timeout_' . $key;
		$transient_option = '_transient_' . $key;
		add_filter( 'option_' . $timeout, '__return_zero' );

		// Mock an action for tracking action calls.
		$a = new MockAction();

		// Add some actions to make sure options are deleted.
		add_action( 'delete_option', array( $a, 'action' ) );

		// Act.
		get_transient( $key );

		// Make sure 'delete_option' was called for both the transient and the timeout.
		$this->assertSame( 2, $a->get_call_count() );

		$expected = array(
			array(
				'action'    => 'action',
				'hook_name' => 'delete_option',
				'tag'       => 'delete_option', // Back compat.
				'args'      => array( $transient_option ),
			),
			array(
				'action'    => 'action',
				'hook_name' => 'delete_option',
				'tag'       => 'delete_option', // Back compat.
				'args'      => array( $timeout ),
			),
		);
		$this->assertSame( $expected, $a->get_events() );
	}
}
