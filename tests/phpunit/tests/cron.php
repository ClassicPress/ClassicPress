<?php

/**
 * Test the cron scheduling functions
 *
 * @group cron
 */
class Tests_Cron extends WP_UnitTestCase {
<<<<<<< HEAD
	function set_up() {
		parent::set_up();
		// Make sure the schedule is clear.
=======
	/**
	 * @var array Cron array for testing preflight filters.
	 */
	private $preflight_cron_array;

	/**
	 * @var int Timestamp of now() + 30 minutes;
	 */
	private $plus_thirty_minutes;

	function setUp() {
		parent::setUp();
		// make sure the schedule is clear
>>>>>>> a32ea2d35d (Cron: Add hooks and a function to allow hijacking cron implementation.)
		_set_cron_array( array() );
		$this->preflight_cron_array = array();
		$this->plus_thirty_minutes = strtotime( '+30 minutes' );
	}

	function tear_down() {
		// Make sure the schedule is clear.
		_set_cron_array( array() );
		parent::tear_down();
	}

	function test_wp_get_schedule_empty() {
		// nothing scheduled
		$hook = __FUNCTION__;
		$this->assertFalse( wp_get_schedule( $hook ) );
	}

	function test_schedule_event_single() {
		// schedule an event and make sure it's returned by wp_next_scheduled
		$hook      = __FUNCTION__;
		$timestamp = strtotime( '+1 hour' );

		$scheduled = wp_schedule_single_event( $timestamp, $hook );
		$this->assertTrue( $scheduled );
		$this->assertSame( $timestamp, wp_next_scheduled( $hook ) );

		// It's a non-recurring event.
		$this->assertFalse( wp_get_schedule( $hook ) );

	}

	function test_schedule_event_single_args() {
		// schedule an event with arguments and make sure it's returned by wp_next_scheduled
		$hook      = 'event';
		$timestamp = strtotime( '+1 hour' );
		$args      = array( 'foo' );

		$scheduled = wp_schedule_single_event( $timestamp, $hook, $args );

		$this->assertTrue( $scheduled );
		// This returns the timestamp only if we provide matching args.
		$this->assertSame( $timestamp, wp_next_scheduled( $hook, $args ) );
		// These don't match so return nothing.
		$this->assertFalse( wp_next_scheduled( $hook ) );
		$this->assertFalse( wp_next_scheduled( $hook, array( 'bar' ) ) );

		// It's a non-recurring event.
		$this->assertFalse( wp_get_schedule( $hook, $args ) );
	}

	function test_schedule_event() {
		// schedule an event and make sure it's returned by wp_next_scheduled
		$hook      = __FUNCTION__;
		$recur     = 'hourly';
		$timestamp = strtotime( '+1 hour' );

		$scheduled = wp_schedule_event( $timestamp, $recur, $hook );

		$this->assertTrue( $scheduled );
		// It's scheduled for the right time.
		$this->assertSame( $timestamp, wp_next_scheduled( $hook ) );
		// It's a recurring event.
		$this->assertSame( $recur, wp_get_schedule( $hook ) );
	}

	function test_schedule_event_args() {
		// schedule an event and make sure it's returned by wp_next_scheduled
		$hook      = 'event';
		$timestamp = strtotime( '+1 hour' );
		$recur     = 'hourly';
		$args      = array( 'foo' );

		$scheduled = wp_schedule_event( $timestamp, 'hourly', $hook, $args );

		$this->assertTrue( $scheduled );
		// This returns the timestamp only if we provide matching args.
		$this->assertSame( $timestamp, wp_next_scheduled( $hook, $args ) );
		// These don't match so return nothing.
		$this->assertFalse( wp_next_scheduled( $hook ) );
		$this->assertFalse( wp_next_scheduled( $hook, array( 'bar' ) ) );

		$this->assertSame( $recur, wp_get_schedule( $hook, $args ) );

	}

	function test_unschedule_event() {
		// schedule an event and make sure it's returned by wp_next_scheduled
		$hook      = __FUNCTION__;
		$timestamp = strtotime( '+1 hour' );

		wp_schedule_single_event( $timestamp, $hook );
		$this->assertSame( $timestamp, wp_next_scheduled( $hook ) );

		// Now unschedule it and make sure it's gone.
		$unscheduled = wp_unschedule_event( $timestamp, $hook );
		$this->assertTrue( $unscheduled );
		$this->assertFalse( wp_next_scheduled( $hook ) );
	}

	function test_clear_schedule() {
		$hook = __FUNCTION__;
		$args = array( 'arg1' );

		// schedule several events with and without arguments
		wp_schedule_single_event( strtotime( '+1 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+2 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+3 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+4 hour' ), $hook, $args );

		// make sure they're returned by wp_next_scheduled()
		$this->assertTrue( wp_next_scheduled( $hook ) > 0 );
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the no args events and make sure it's gone
		$hook_unscheduled = wp_clear_scheduled_hook( $hook );
		$this->assertSame( 2, $hook_unscheduled );
		$this->assertFalse( wp_next_scheduled( $hook ) );
		// the args events should still be there
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the args events and make sure they're gone too
		// note: wp_clear_scheduled_hook() expects args passed directly, rather than as an array
		wp_clear_scheduled_hook( $hook, $args );
		$this->assertFalse( wp_next_scheduled( $hook, $args ) );
	}

	function test_clear_undefined_schedule() {
		$hook = __FUNCTION__;
		$args = array( 'arg1' );

		wp_schedule_single_event( strtotime( '+1 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+2 hour' ), $hook, $args );

		// clear the schedule for no args events and ensure no events are cleared.
		$hook_unscheduled = wp_clear_scheduled_hook( $hook );
		$this->assertSame( 0, $hook_unscheduled );
	}

	function test_clear_schedule_multiple_args() {
		$hook = __FUNCTION__;
		$args = array( 'arg1', 'arg2' );

		// schedule several events with and without arguments
		wp_schedule_single_event( strtotime( '+1 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+2 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+3 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+4 hour' ), $hook, $args );

		// make sure they're returned by wp_next_scheduled()
		$this->assertTrue( wp_next_scheduled( $hook ) > 0 );
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the no args events and make sure it's gone
		wp_clear_scheduled_hook( $hook );
		$this->assertFalse( wp_next_scheduled( $hook ) );
		// the args events should still be there
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the args events and make sure they're gone too
		// note: wp_clear_scheduled_hook() used to expect args passed directly, rather than as an array pre WP 3.0
		wp_clear_scheduled_hook( $hook, $args );
		$this->assertFalse( wp_next_scheduled( $hook, $args ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/10468
	 */
	function test_clear_schedule_new_args() {
		$hook       = __FUNCTION__;
		$args       = array( 'arg1' );
		$multi_hook = __FUNCTION__ . '_multi';
		$multi_args = array( 'arg2', 'arg3' );

		// schedule several events with and without arguments
		wp_schedule_single_event( strtotime( '+1 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+2 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+3 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+4 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+5 hour' ), $multi_hook, $multi_args );
		wp_schedule_single_event( strtotime( '+6 hour' ), $multi_hook, $multi_args );

		// make sure they're returned by wp_next_scheduled()
		$this->assertTrue( wp_next_scheduled( $hook ) > 0 );
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the no args events and make sure it's gone
		wp_clear_scheduled_hook( $hook );
		$this->assertFalse( wp_next_scheduled( $hook ) );
		// the args events should still be there
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule for the args events and make sure they're gone too
		// wp_clear_scheduled_hook() should take args as an array like the other functions.
		wp_clear_scheduled_hook( $hook, $args );
		$this->assertFalse( wp_next_scheduled( $hook, $args ) );

		// clear the schedule for the args events and make sure they're gone too
		// wp_clear_scheduled_hook() should take args as an array like the other functions and does from WP 3.0
		wp_clear_scheduled_hook( $multi_hook, $multi_args );
		$this->assertFalse( wp_next_scheduled( $multi_hook, $multi_args ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/18997
	 */
	function test_unschedule_hook() {
		$hook = __FUNCTION__;
		$args = array( rand_str() );

		// schedule several events with and without arguments.
		wp_schedule_single_event( strtotime( '+1 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+2 hour' ), $hook );
		wp_schedule_single_event( strtotime( '+3 hour' ), $hook, $args );
		wp_schedule_single_event( strtotime( '+4 hour' ), $hook, $args );

		// make sure they're returned by wp_next_scheduled().
		$this->assertTrue( wp_next_scheduled( $hook ) > 0 );
		$this->assertTrue( wp_next_scheduled( $hook, $args ) > 0 );

		// clear the schedule and make sure it's gone.
		$unschedule_hook = wp_unschedule_hook( $hook );
		$this->assertSame( 4, $unschedule_hook );
		$this->assertFalse( wp_next_scheduled( $hook ) );
	}

	function test_unschedule_undefined_hook() {
		$hook           = __FUNCTION__;
		$unrelated_hook = __FUNCTION__ . '_two';

		// Attempt to clear schedule on non-existant hook.
		$unschedule_hook = wp_unschedule_hook( $hook );
		$this->assertSame( 0, $unschedule_hook );
		$this->assertFalse( wp_next_scheduled( $hook ) );

		// Repeat tests with populated cron array.
		wp_schedule_single_event( strtotime( '+1 hour' ), $unrelated_hook );
		wp_schedule_single_event( strtotime( '+2 hour' ), $unrelated_hook );

		$unschedule_hook = wp_unschedule_hook( $hook );
		$this->assertSame( 0, $unschedule_hook );
		$this->assertFalse( wp_next_scheduled( $hook ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/6966
	 */
	function test_duplicate_event() {
		// duplicate events close together should be skipped
		$hook = __FUNCTION__;
		$args = array( 'arg1' );
		$ts1  = strtotime( '+5 minutes' );
		$ts2  = strtotime( '+3 minutes' );

		// first one works
		$this->assertTrue( wp_schedule_single_event( $ts1, $hook, $args ) );
		// second one is ignored
		$this->assertFalse( wp_schedule_single_event( $ts2, $hook, $args ) );

		// The next event should be at +5 minutes, not +3.
		$this->assertSame( $ts1, wp_next_scheduled( $hook, $args ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/6966
	 */
	function test_not_duplicate_event() {
		// duplicate events far apart should work normally
		$hook = __FUNCTION__;
		$args = array( 'arg1' );
		$ts1  = strtotime( '+30 minutes' );
		$ts2  = strtotime( '+3 minutes' );

		// first one works
		$this->assertTrue( wp_schedule_single_event( $ts1, $hook, $args ) );
		// second works too
		$this->assertTrue( wp_schedule_single_event( $ts2, $hook, $args ) );

		// The next event should be at +3 minutes, even though that one was scheduled second.
		$this->assertSame( $ts2, wp_next_scheduled( $hook, $args ) );
		wp_unschedule_event( $ts2, $hook, $args );
		// Following event at +30 minutes should be there too.
		$this->assertSame( $ts1, wp_next_scheduled( $hook, $args ) );
	}

	function test_not_duplicate_event_reversed() {
		// duplicate events far apart should work normally regardless of order
		$hook = __FUNCTION__;
		$args = array( 'arg1' );
		$ts1  = strtotime( '+3 minutes' );
		$ts2  = strtotime( '+30 minutes' );

		// first one works
		$this->assertTrue( wp_schedule_single_event( $ts1, $hook, $args ) );
		// second works too
		$this->assertTrue( wp_schedule_single_event( $ts2, $hook, $args ) );

		// The next event should be at +3 minutes.
		$this->assertSame( $ts1, wp_next_scheduled( $hook, $args ) );
		wp_unschedule_event( $ts1, $hook, $args );
		// Following event should be there too.
		$this->assertSame( $ts2, wp_next_scheduled( $hook, $args ) );
	}

	/**
	 * Ensure the pre_scheduled_event filter prevents
	 * modification of the cron_array_option.
	 *
	 * @ticket 32656
	 */
	function test_pre_schedule_event_filter() {
		$hook = __FUNCTION__;
		$args = array( 'arg1' );
		$ts1  = strtotime( '+30 minutes' );
		$ts2  = strtotime( '+3 minutes' );

		$expected = _get_cron_array();

		add_filter( 'pre_schedule_event', array( $this, '_filter_pre_schedule_event_filter' ), 10, 2 );

		$this->assertTrue( wp_schedule_single_event( $ts1, $hook, $args ) );
		$this->assertTrue( wp_schedule_event( $ts2, 'hourly', $hook ) );

		// Check cron option is unchanged.
		$this->assertSame( $expected, _get_cron_array() );

		$expected_preflight[ $ts2 ][ $hook ][ md5( serialize( array() ) ) ] = array(
			'schedule' => 'hourly',
			'interval' => HOUR_IN_SECONDS,
			'args'     => array(),
		);

		$expected_preflight[ $ts1 ][ $hook ][ md5( serialize( $args ) ) ] = array(
			'schedule' => false,
			'interval' => 0,
			'args'     => $args,
		);

		$this->assertSame( $expected_preflight, $this->preflight_cron_array );
	}

	/**
	 * Filter the scheduling of events to use the preflight array.
	 */
	function _filter_pre_schedule_event_filter( $null, $event ) {
		$key = md5( serialize( $event->args ) );

		$this->preflight_cron_array[ $event->timestamp ][ $event->hook ][ $key ] = array(
			'schedule' => $event->schedule,
			'interval' => isset( $event->interval ) ? $event->interval : 0,
			'args'     => $event->args,
		);
		uksort( $this->preflight_cron_array, 'strnatcasecmp' );
		return true;
	}

	/**
	 * Ensure the pre_reschedule_event filter prevents
	 * modification of the cron_array_option.
	 *
	 * @ticket 32656
	 */
	function test_pre_reschedule_event_filter() {
		$hook = __FUNCTION__;
		$ts1  = strtotime( '+30 minutes' );

		// Add an event
		$this->assertTrue( wp_schedule_event( $ts1, 'hourly', $hook ) );
		$expected = _get_cron_array();

		// Add preflight filter.
		add_filter( 'pre_reschedule_event', '__return_true' );

		// Reschedule event with preflight filter in place.
		wp_reschedule_event( $ts1, 'daily', $hook );

		// Check cron option is unchanged.
		$this->assertSame( $expected, _get_cron_array() );
	}

	/**
	 * Ensure the pre_unschedule_event filter prevents
	 * modification of the cron_array_option.
	 *
	 * @ticket 32656
	 */
	function test_pre_unschedule_event_filter() {
		$hook = __FUNCTION__;
		$ts1  = strtotime( '+30 minutes' );

		// Add an event
		$this->assertTrue( wp_schedule_event( $ts1, 'hourly', $hook ) );
		$expected = _get_cron_array();

		// Add preflight filter.
		add_filter( 'pre_unschedule_event', '__return_true' );

		// Unschedule event with preflight filter in place.
		wp_unschedule_event( $ts1, $hook );

		// Check cron option is unchanged.
		$this->assertSame( $expected, _get_cron_array() );
	}

	/**
	 * Ensure the clearing scheduled hooks filter prevents
	 * modification of the cron_array_option.
	 *
	 * @ticket 32656
	 */
	function test_pre_clear_scheduled_hook_filters() {
		$hook = __FUNCTION__;
		$ts1  = strtotime( '+30 minutes' );

		// Add an event
		$this->assertTrue( wp_schedule_event( $ts1, 'hourly', $hook ) );
		$expected = _get_cron_array();

		// Add preflight filters.
		add_filter( 'pre_clear_scheduled_hook', '__return_true' );
		add_filter( 'pre_unschedule_hook', '__return_zero' );

		// Unschedule event with preflight filter in place.
		wp_clear_scheduled_hook( $hook );

		// Check cron option is unchanged.
		$this->assertSame( $expected, _get_cron_array() );

		// Unschedule all events with preflight filter in place.
		wp_unschedule_hook( $hook );

		// Check cron option is unchanged.
		$this->assertSame( $expected, _get_cron_array() );
	}

	/**
	 * Ensure the preflight hooks for scheduled events
	 * return a filtered value as expected.
	 *
	 * @ticket 32656
	 */
	function test_pre_scheduled_event_hooks() {
		add_filter( 'pre_get_scheduled_event', array( $this, 'filter_pre_scheduled_event_hooks' ) );
		add_filter( 'pre_next_scheduled', array( $this, 'filter_pre_scheduled_event_hooks' ) );

		$actual = wp_get_scheduled_event( 'preflight_event', array(), $this->plus_thirty_minutes );
		$actual2 = wp_next_scheduled( 'preflight_event', array() );

		$expected = (object) array(
			'hook'      => 'preflight_event',
			'timestamp' => $this->plus_thirty_minutes,
			'schedule'  => false,
			'args'      => array(),
		);

		$this->assertEquals( $expected, $actual );
		$this->assertEquals( $expected, $actual2 );
	}

	function filter_pre_scheduled_event_hooks() {
		return (object) array(
			'hook'      => 'preflight_event',
			'timestamp' => $this->plus_thirty_minutes,
			'schedule'  => false,
			'args'      => array(),
		);
	}
}
