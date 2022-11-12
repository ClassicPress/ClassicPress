<?php
/**
 * ClassicPress Cron API
 *
 * @package ClassicPress
 */

/**
 * Schedules an event to run only once.
 *
 * Schedules an event which will execute once by the ClassicPress actions core at
 * a time which you specify. The action will fire off when someone visits your
 * ClassicPress site, if the schedule time has passed.
 *
 * Note that scheduling an event to occur within 10 minutes of an existing event
 * with the same action hook will be ignored unless you pass unique `$args` values
 * for each scheduled event.
 *
 * Use wp_next_scheduled() to prevent duplicate events.
 *
 * Use wp_schedule_event() to schedule a recurring event.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to boolean indicating success or failure,
 *                 {@see pre_schedule_event} filter added to short-circuit the function.
 *
 * @link https://codex.wordpress.org/Function_Reference/wp_schedule_single_event
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to next run the event.
 * @param string $hook       Action hook to execute when the event is run.
 * @param array  $args       Optional. Array containing each separate argument to pass to the hook's callback function.
 * @return bool True if event successfully scheduled. False for failure.
 */
function wp_schedule_single_event( $timestamp, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => $timestamp,
		'schedule'  => false,
		'args'      => $args,
	);

	/**
	 * Filter to preflight or hijack scheduling an event.
	 *
	 * Returning a non-null value will short-circuit adding the event to the
	 * cron array, causing the function to return the filtered value instead.
	 *
	 * Both single events and recurring events are passed through this filter;
	 * single events have `$event->schedule` as false, whereas recurring events
	 * have this set to a recurrence from {@see wp_get_schedules}. Recurring
	 * events also have the integer recurrence interval set as `$event->interval`.
	 *
	 * For plugins replacing wp-cron, it is recommended you check for an
	 * identical event within ten minutes and apply the {@see schedule_event}
	 * filter to check if another plugin has disallowed the event before scheduling.
	 *
	 * Return true if the event was scheduled, false if not.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|bool $pre   Value to return instead. Default null to continue adding the event.
	 * @param stdClass  $event {
	 *     An object containing an event's data.
	 *
	 *     @type string       $hook      Action hook to execute when the event is run.
	 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
	 *     @type string|false $schedule  How often the event should subsequently recur.
	 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
	 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
	 * }
	 */
	$pre = apply_filters( 'pre_schedule_event', null, $event );
	if ( null !== $pre ) {
		return $pre;
	}

	// Don't schedule a duplicate if there's already an identical event due within 10 minutes of it
	$next = wp_next_scheduled( $hook, $args );
	if ( $next && abs( $next - $timestamp ) <= 10 * MINUTE_IN_SECONDS ) {
		return false;
	}

	/**
	 * Filters a single event before it is scheduled.
	 *
	 * @since WP-3.1.0
	 *
	 * @param stdClass $event {
	 *     An object containing an event's data.
	 *
	 *     @type string       $hook      Action hook to execute when event is run.
	 *     @type int          $timestamp Unix timestamp (UTC) for when to run the event.
	 *     @type string|false $schedule  How often the event should recur. See `wp_get_schedules()`.
	 *     @type array        $args      Arguments to pass to the hook's callback function.
	 * }
	 */
	$event = apply_filters( 'schedule_event', $event );

	// A plugin disallowed this event
	if ( ! $event ) {
		return false;
	}

	$key = md5( serialize( $event->args ) );

	$crons = _get_cron_array();
	$crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
		'schedule' => $event->schedule,
		'args'     => $event->args,
	);
	uksort( $crons, 'strnatcasecmp' );
	return _set_cron_array( $crons );
}

/**
 * Schedule a recurring event.
 *
 * Schedules a hook which will be executed by the ClassicPress actions core on a
 * specific interval, specified by you. The action will trigger when someone
 * visits your ClassicPress site, if the scheduled time has passed.
 *
 * Valid values for the recurrence are hourly, daily, and twicedaily. These can
 * be extended using the {@see 'cron_schedules'} filter in wp_get_schedules().
 *
 * Use wp_schedule_single_event() to schedule a non-recurring event.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to boolean indicating success or failure,
 *                 {@see pre_schedule_event} filter added to short-circuit the function.
 *
 * @link https://codex.wordpress.org/Function_Reference/wp_schedule_event
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to next run the event.
 * @param string $recurrence How often the event should subsequently recur. See wp_get_schedules() for accepted values.
 * @param string $hook       Action hook to execute when the event is run.
 * @param array  $args       Optional. Array containing each separate argument to pass to the hook's callback function.
 * @return bool True if event successfully scheduled. False for failure.
 */
function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	$schedules = wp_get_schedules();

	if ( ! isset( $schedules[ $recurrence ] ) ) {
		return false;
	}

	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => $timestamp,
		'schedule'  => $recurrence,
		'args'      => $args,
		'interval'  => $schedules[ $recurrence ]['interval'],
	);

	/** This filter is documented in wp-includes/cron.php */
	$pre = apply_filters( 'pre_schedule_event', null, $event );
	if ( null !== $pre ) {
		return $pre;
	}

	/** This filter is documented in wp-includes/cron.php */
	$event = apply_filters( 'schedule_event', $event );

	// A plugin disallowed this event
	if ( ! $event ) {
		return false;
	}

	$key = md5( serialize( $event->args ) );

	$crons = _get_cron_array();
	if ( ! is_array( $crons ) ) {
		$crons = array();
	}

	$crons[ $event->timestamp ][ $event->hook ][ $key ] = array(
		'schedule' => $event->schedule,
		'args'     => $event->args,
		'interval' => $event->interval,
	);
	uksort( $crons, 'strnatcasecmp' );
	return _set_cron_array( $crons );
}

/**
 * Reschedule a recurring event.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to boolean indicating success or failure,
 *                 {@see pre_reschedule_event} filter added to short-circuit the function.
 *
 * @param int    $timestamp  Unix timestamp (UTC) for when to next run the event.
 * @param string $recurrence How often the event should subsequently recur. See wp_get_schedules() for accepted values.
 * @param string $hook       Action hook to execute when the event is run.
 * @param array  $args       Optional. Array containing each separate argument to pass to the hook's callback function.
 * @return bool True if event successfully rescheduled. False for failure.
 */
function wp_reschedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	$schedules = wp_get_schedules();
	$interval  = 0;

	// First we try to get the interval from the schedule.
	if ( isset( $schedules[ $recurrence ] ) ) {
		$interval = $schedules[ $recurrence ]['interval'];
	}

	// Now we try to get it from the saved interval in case the schedule disappears.
	if ( 0 === $interval ) {
		$scheduled_event = wp_get_scheduled_event( $hook, $args, $timestamp );
		if ( $scheduled_event && isset( $scheduled_event->interval ) ) {
			$interval = $scheduled_event->interval;
		}
	}

	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => $timestamp,
		'schedule'  => $recurrence,
		'args'      => $args,
		'interval'  => $interval,
	);

	/**
	 * Filter to preflight or hijack rescheduling of events.
	 *
	 * Returning a non-null value will short-circuit the normal rescheduling
	 * process, causing the function to return the filtered value instead.
	 *
	 * For plugins replacing wp-cron, return true if the event was successfully
	 * rescheduled, false if not.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|bool $pre   Value to return instead. Default null to continue adding the event.
	 * @param stdClass  $event {
	 *     An object containing an event's data.
	 *
	 *     @type string       $hook      Action hook to execute when the event is run.
	 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
	 *     @type string|false $schedule  How often the event should subsequently recur.
	 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
	 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
	 * }
	 */
	$pre = apply_filters( 'pre_reschedule_event', null, $event );
	if ( null !== $pre ) {
		return $pre;
	}

	// Now we assume something is wrong and fail to schedule
	if ( 0 == $interval ) {
		return false;
	}

	$now = time();

	if ( $timestamp >= $now ) {
		$timestamp = $now + $interval;
	} else {
		$timestamp = $now + ( $interval - ( ( $now - $timestamp ) % $interval ) );
	}

	return wp_schedule_event( $timestamp, $recurrence, $hook, $args );
}

/**
 * Unschedule a previously scheduled event.
 *
 * The $timestamp and $hook parameters are required so that the event can be
 * identified.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to boolean indicating success or failure,
 *                 {@see pre_unschedule_event} filter added to short-circuit the function.
 *
 * @param int    $timestamp Unix timestamp (UTC) of the event.
 * @param string $hook      Action hook of the event.
 * @param array  $args      Optional. Array containing each separate argument to pass to the hook's callback function.
 *                          Although not passed to a callback, these arguments are used to uniquely identify the
 *                          event, so they should be the same as those used when originally scheduling the event.
 * @return bool True if event successfully unscheduled. False for failure.
 */
function wp_unschedule_event( $timestamp, $hook, $args = array() ) {
	// Make sure timestamp is a positive integer
	if ( ! is_numeric( $timestamp ) || $timestamp <= 0 ) {
		return false;
	}

	/**
	 * Filter to preflight or hijack unscheduling of events.
	 *
	 * Returning a non-null value will short-circuit the normal unscheduling
	 * process, causing the function to return the filtered value instead.
	 *
	 * For plugins replacing wp-cron, return true if the event was successfully
	 * unscheduled, false if not.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|bool $pre       Value to return instead. Default null to continue unscheduling the event.
	 * @param int       $timestamp Timestamp for when to run the event.
	 * @param string    $hook      Action hook, the execution of which will be unscheduled.
	 * @param array     $args      Arguments to pass to the hook's callback function.
	 */
	$pre = apply_filters( 'pre_unschedule_event', null, $timestamp, $hook, $args );
	if ( null !== $pre ) {
		return $pre;
	}

	$crons = _get_cron_array();
	$key   = md5( serialize( $args ) );
	unset( $crons[ $timestamp ][ $hook ][ $key ] );
	if ( empty( $crons[ $timestamp ][ $hook ] ) ) {
		unset( $crons[ $timestamp ][ $hook ] );
	}
	if ( empty( $crons[ $timestamp ] ) ) {
		unset( $crons[ $timestamp ] );
	}
	return _set_cron_array( $crons );
}

/**
 * Unschedules all events attached to the hook with the specified arguments.
 *
 * Warning: This function may return Boolean FALSE, but may also return a non-Boolean
 * value which evaluates to FALSE. For information about casting to booleans see the
 * {@link https://php.net/manual/en/language.types.boolean.php PHP documentation}. Use
 * the `===` operator for testing the return value of this function.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to indicate success or failure,
 *                 {@see pre_clear_scheduled_hook} filter added to short-circuit the function.
 *
 * @param string $hook Action hook, the execution of which will be unscheduled.
 * @param array $args Optional. Arguments that were to be passed to the hook's callback function.
 * @return bool|int On success an integer indicating number of events unscheduled (0 indicates no
 *                  events were registered with the hook and arguments combination), false if
 *                  unscheduling one or more events fail.
 */
function wp_clear_scheduled_hook( $hook, $args = array() ) {
	// Backward compatibility.
	// Previously, this function took the arguments as discrete vars rather than an array like the rest of the API.
	if ( ! is_array( $args ) ) {
		_deprecated_argument( __FUNCTION__, 'WP-3.0.0', __( 'This argument has changed to an array to match the behavior of the other cron functions.' ) );
		$args = array_slice( func_get_args(), 1 ); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection
	}

	/**
	 * Filter to preflight or hijack clearing a scheduled hook.
	 *
	 * Returning a non-null value will short-circuit the normal unscheduling
	 * process, causing the function to return the filtered value instead.
	 *
	 * For plugins replacing wp-cron, return the number of events successfully
	 * unscheduled (zero if no events were registered with the hook) or false
	 * if unscheduling one or more events fails.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|array $pre  Value to return instead. Default null to continue unscheduling the event.
	 * @param string     $hook Action hook, the execution of which will be unscheduled.
	 * @param array      $args Arguments to pass to the hook's callback function.
	 */
	$pre = apply_filters( 'pre_clear_scheduled_hook', null, $hook, $args );
	if ( null !== $pre ) {
		return $pre;
	}

	// This logic duplicates wp_next_scheduled()
	// It's required due to a scenario where wp_unschedule_event() fails due to update_option() failing,
	// and, wp_next_scheduled() returns the same schedule in an infinite loop.
	$crons = _get_cron_array();
	if ( empty( $crons ) ) {
		return 0;
	}

	$results = array();
	$key = md5( serialize( $args ) );
	foreach ( $crons as $timestamp => $cron ) {
		if ( isset( $cron[ $hook ][ $key ] ) ) {
			$results[] = wp_unschedule_event( $timestamp, $hook, $args );
		}
	}
	if ( in_array( false, $results, true ) ) {
		return false;
	}
	return count( $results );
}

/**
 * Unschedules all events attached to the hook.
 *
 * Can be useful for plugins when deactivating to clean up the cron queue.
 *
 * Warning: This function may return Boolean FALSE, but may also return a non-Boolean
 * value which evaluates to FALSE. For information about casting to booleans see the
 * {@link https://php.net/manual/en/language.types.boolean.php PHP documentation}. Use
 * the `===` operator for testing the return value of this function.
 *
 * @since WP-4.9.0
 * @since WP-5.0.0 Return value added to indicate success or failure.
 *
 * @param string $hook Action hook, the execution of which will be unscheduled.
 * @return bool|int On success an integer indicating number of events unscheduled (0 indicates no
 *                  events were registered on the hook), false if unscheduling fails.
 */
function wp_unschedule_hook( $hook ) {
	/**
	 * Filter to preflight or hijack clearing all events attached to the hook.
	 *
	 * Returning a non-null value will short-circuit the normal unscheduling
	 * process, causing the function to return the filtered value instead.
	 *
	 * For plugins replacing wp-cron, return the number of events successfully
	 * unscheduled (zero if no events were registered with the hook) or false
	 * if unscheduling one or more events fails.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|array $pre  Value to return instead. Default null to continue unscheduling the hook.
	 * @param string     $hook Action hook, the execution of which will be unscheduled.
	 */
	$pre = apply_filters( 'pre_unschedule_hook', null, $hook );
	if ( null !== $pre ) {
		return $pre;
	}

	$crons = _get_cron_array();
	if ( empty( $crons ) ) {
		return 0;
	}

	$results = array();
	foreach ( $crons as $timestamp => $args ) {
		if ( ! empty( $crons[ $timestamp ][ $hook ] ) ) {
			$results[] = count( $crons[ $timestamp ][ $hook ] );
		}
		unset( $crons[ $timestamp ][ $hook ] );

		if ( empty( $crons[ $timestamp ] ) ) {
			unset( $crons[ $timestamp ] );
		}
	}

	/*
	 * If the results are empty (zero events to unschedule), no attempt
	 * to update the cron array is required.
	 */
	if ( empty( $results ) ) {
		return 0;
	}
	if ( _set_cron_array( $crons ) ) {
		return array_sum( $results );
	}
	return false;
}

/**
 * Retrieve a scheduled event.
 *
 * Retrieve the full event object for a given event.
 *
 * @since WP-5.0.0
 *
 * @param string   $hook      Action hook of the event.
 * @param array    $args      Optional. Array containing each separate argument to pass to the hook's callback function.
 *                            Although not passed to a callback, these arguments are used to uniquely identify the
 *                            event, so they should be the same as those used when originally scheduling the event.
 * @param int|null $timestamp Optional. Unix timestamp (UTC) of the event. If not specified, the next scheduled event is returned.
 * @return bool|object The event object. False if the event does not exist.
 */
function wp_get_scheduled_event( $hook, $args = array(), $timestamp = null ) {
	if ( ! $timestamp ) {
		// Get the next scheduled event.
		$timestamp = wp_next_scheduled( $hook, $args );
	}

	/**
	 * Filter to preflight or hijack retrieving a scheduled event.
	 *
	 * Returning a non-null value will short-circuit the normal process,
	 * returning the filtered value instead.
	 *
	 * Return false if the event does not exist, otherwise an event object
	 * should be returned.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|bool $pre       Value to return instead. Default null to continue retrieving the event.
	 * @param string    $hook      Action hook of the event.
	 * @param array     $args      Array containing each separate argument to pass to the hook's callback function.
	 *                             Although not passed to a callback, these arguments are used to uniquely identify the
	 *                             event.
	 * @param int       $timestamp Unix timestamp (UTC) of the event.
	 */
	$pre = apply_filters( 'pre_get_scheduled_event', null, $hook, $args, $timestamp );
	if ( null !== $pre ) {
		return $pre;
	}

	$crons = _get_cron_array();
	$key   = md5( serialize( $args ) );

	if ( ! $timestamp || ! isset( $crons[ $timestamp ] ) ) {
		// No such event.
		return false;
	}

	if ( ! isset( $crons[ $timestamp ][ $hook ] ) || ! isset( $crons[ $timestamp ][ $hook ][ $key ] ) ) {
		return false;
	}

	$event = (object) array(
		'hook'      => $hook,
		'timestamp' => $timestamp,
		'schedule'  => $crons[ $timestamp ][ $hook ][ $key ]['schedule'],
		'args'      => $args,
	);

	if ( isset( $crons[ $timestamp ][ $hook ][ $key ]['interval'] ) ) {
		$event->interval = $crons[ $timestamp ][ $hook ][ $key ]['interval'];
	}

	return $event;
}

/**
 * Retrieve the next timestamp for an event.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 {@see pre_next_scheduled} and {@see next_scheduled} filters added.
 *
 * @param string $hook Action hook to execute when event is run.
 * @param array $args Optional. Arguments to pass to the hook's callback function.
 * @return false|int The Unix timestamp of the next time the scheduled event will occur.
 */
function wp_next_scheduled( $hook, $args = array() ) {
	/**
	 * Filter to preflight or hijack retrieving the next scheduled event timestamp.
	 *
	 * Returning a non-null value will short-circuit the normal retrieval
	 * process, causing the function to return the filtered value instead.
	 *
	 * Pass the timestamp of the next event if it exists, false if not.
	 *
	 * @since WP-5.0.0
	 *
	 * @param null|bool $pre       Value to return instead. Default null to continue unscheduling the event.
	 * @param string    $hook      Action hook of the event.
	 * @param array     $args      Arguments to pass to the hook's callback function.
	 */
	$pre = apply_filters( 'pre_next_scheduled', null, $hook, $args );
	if ( null !== $pre ) {
		return $pre;
	}

	$crons = _get_cron_array();
	$key   = md5( serialize( $args ) );
	$next  = false;

	if ( ! empty( $crons ) ) {
		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[ $hook ][ $key ] ) ) {
					$next = $timestamp;
					break;
			}
		}
	}

	/**
	 * Filter the next scheduled event timestamp.
	 *
	 * @since WP-5.0.0
	 *
	 * @param int|bool $next The UNIX timestamp when the scheduled event will next occur, or false if not found.
	 * @param string   $hook Action hook to execute when cron is run.
	 * @param array    $args Arguments to be passed to the callback function. Used for deduplicating events.
	 */
	return apply_filters( 'next_scheduled', $next, $hook, $args );
}

/**
 * Sends a request to run cron through HTTP request that doesn't halt page loading.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return values added.
 *
 * @param int $gmt_time Optional. Unix timestamp (UTC). Default 0 (current time is used).
 * @return bool True if spawned, false if no events spawned.
 */
function spawn_cron( $gmt_time = 0 ) {
	if ( ! $gmt_time ) {
		$gmt_time = microtime( true );
	}

	if ( defined( 'DOING_CRON' ) || isset( $_GET['doing_wp_cron'] ) ) {
		return false;
	}

	/*
	 * Get the cron lock, which is a Unix timestamp of when the last cron was spawned
	 * and has not finished running.
	 *
	 * Multiple processes on multiple web servers can run this code concurrently,
	 * this lock attempts to make spawning as atomic as possible.
	 */
	$lock = get_transient( 'doing_cron' );

	if ( $lock > $gmt_time + 10 * MINUTE_IN_SECONDS ) {
		$lock = 0;
	}

	// don't run if another process is currently running it or more than once every 60 sec.
	if ( $lock + WP_CRON_LOCK_TIMEOUT > $gmt_time ) {
		return false;
	}

	//sanity check
	$crons = _get_cron_array();
	if ( ! is_array( $crons ) ) {
		return false;
	}

	$keys = array_keys( $crons );
	if ( isset( $keys[0] ) && $keys[0] > $gmt_time ) {
		return false;
	}

	if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
		if ( 'GET' !== $_SERVER['REQUEST_METHOD'] || defined( 'DOING_AJAX' ) || defined( 'XMLRPC_REQUEST' ) ) {
			return false;
		}

		$doing_wp_cron = sprintf( '%.22F', $gmt_time );
		set_transient( 'doing_cron', $doing_wp_cron );

		ob_start();
		wp_redirect( add_query_arg( 'doing_wp_cron', $doing_wp_cron, wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		echo ' ';

		// flush any buffers and send the headers
		wp_ob_end_flush_all();
		flush();

		include_once ABSPATH . 'wp-cron.php';
		return true;
	}

	// Set the cron lock with the current unix timestamp, when the cron is being spawned.
	$doing_wp_cron = sprintf( '%.22F', $gmt_time );
	set_transient( 'doing_cron', $doing_wp_cron );

	/**
	 * Filters the cron request arguments.
	 *
	 * @since WP-3.5.0
	 * @since WP-4.5.0 The `$doing_wp_cron` parameter was added.
	 *
	 * @param array $cron_request_array {
	 *     An array of cron request URL arguments.
	 *
	 *     @type string $url  The cron request URL.
	 *     @type int    $key  The 22 digit GMT microtime.
	 *     @type array  $args {
	 *         An array of cron request arguments.
	 *
	 *         @type int  $timeout   The request timeout in seconds. Default .01 seconds.
	 *         @type bool $blocking  Whether to set blocking for the request. Default false.
	 *         @type bool $sslverify Whether SSL should be verified for the request. Default false.
	 *     }
	 * }
	 * @param string $doing_wp_cron The unix timestamp of the cron lock.
	 */
	$cron_request = apply_filters(
		'cron_request',
		array(
			'url'  => add_query_arg( 'doing_wp_cron', $doing_wp_cron, site_url( 'wp-cron.php' ) ),
			'key'  => $doing_wp_cron,
			'args' => array(
				'timeout'   => 0.01,
				'blocking'  => false,
				/** This filter is documented in wp-includes/class-wp-http-streams.php */
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			),
		),
		$doing_wp_cron
	);

	$result = wp_remote_post( $cron_request['url'], $cron_request['args'] );
	return ! is_wp_error( $result );
}

/**
 * Run scheduled callbacks or spawn cron for all scheduled events.
 *
 * Warning: This function may return Boolean FALSE, but may also return a non-Boolean
 * value which evaluates to FALSE. For information about casting to booleans see the
 * {@link https://php.net/manual/en/language.types.boolean.php PHP documentation}. Use
 * the `===` operator for testing the return value of this function.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value added to indicate success or failure.
 *
 * @return bool|int On success an integer indicating number of events spawned (0 indicates no
 *                  events needed to be spawned), false if spawning fails for one or more events.
 */
function wp_cron() {
	// Prevent infinite loops caused by lack of wp-cron.php
	if ( strpos( $_SERVER['REQUEST_URI'], '/wp-cron.php' ) !== false || ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
		return 0;
	}

	$crons = _get_cron_array();
	if ( false === $crons ) {
		return 0;
	}

	$gmt_time = microtime( true );
	$keys     = array_keys( $crons );
	if ( isset( $keys[0] ) && $keys[0] > $gmt_time ) {
		return 0;
	}

	$schedules = wp_get_schedules();
	$results   = array();
	foreach ( $crons as $timestamp => $cronhooks ) {
		if ( $timestamp > $gmt_time ) {
			break;
		}
		foreach ( (array) $cronhooks as $hook => $args ) {
			if ( isset( $schedules[ $hook ]['callback'] ) && ! call_user_func( $schedules[ $hook ]['callback'] ) ) {
				continue;
			}
			$results[] = spawn_cron( $gmt_time );
			break 2;
		}
	}

	if ( in_array( false, $results, true ) ) {
		return false;
	}
	return count( $results );
}

/**
 * Retrieve supported event recurrence schedules.
 *
 * The default supported recurrences are 'hourly', 'twicedaily', and 'daily'. A plugin may
 * add more by hooking into the {@see 'cron_schedules'} filter. The filter accepts an array
 * of arrays. The outer array has a key that is the name of the schedule or for
 * example 'weekly'. The value is an array with two keys, one is 'interval' and
 * the other is 'display'.
 *
 * The 'interval' is a number in seconds of when the cron job should run. So for
 * 'hourly', the time is 3600 or 60*60. For weekly, the value would be
 * 60*60*24*7 or 604800. The value of 'interval' would then be 604800.
 *
 * The 'display' is the description. For the 'weekly' key, the 'display' would
 * be `__( 'Once Weekly' )`.
 *
 * For your plugin, you will be passed an array. you can easily add your
 * schedule by doing the following.
 *
 *     // Filter parameter variable name is 'array'.
 *     $array['weekly'] = array(
 *         'interval' => 604800,
 *         'display'  => __( 'Once Weekly' )
 *     );
 *
 *
 * @since WP-2.1.0
 *
 * @return array
 */
function wp_get_schedules() {
	$schedules = array(
		'hourly'     => array(
			'interval' => HOUR_IN_SECONDS,
			'display'  => __( 'Once Hourly' ),
		),
		'twicedaily' => array(
			'interval' => 12 * HOUR_IN_SECONDS,
			'display'  => __( 'Twice Daily' ),
		),
		'daily'      => array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'Once Daily' ),
		),
	);
	/**
	 * Filters the non-default cron schedules.
	 *
	 * @since WP-2.1.0
	 *
	 * @param array $new_schedules An array of non-default cron schedules. Default empty.
	 */
	return array_merge( apply_filters( 'cron_schedules', array() ), $schedules );
}

/**
 * Retrieve the recurrence schedule for an event.
 *
 * @see wp_get_schedules() for available schedules.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 {@see get_schedule} filter added.
 *
 * @param string $hook Action hook to identify the event.
 * @param array $args Optional. Arguments passed to the event's callback function.
 * @return string|false False, if no schedule. Schedule name on success.
 */
function wp_get_schedule( $hook, $args = array() ) {
	$schedule = false;
	$event    = wp_get_scheduled_event( $hook, $args );

	if ( $event ) {
		$schedule = $event->schedule;
	}

	/**
	 * Filter the schedule for a hook.
	 *
	 * @since WP-5.0.0
	 *
	 * @param string|bool $schedule Schedule for the hook. False if not found.
	 * @param string      $hook     Action hook to execute when cron is run.
	 * @param array       $args     Optional. Arguments to pass to the hook's callback function.
	 */
	return apply_filters( 'get_schedule', $schedule, $hook, $args );
}

//
// Private functions
//

/**
 * Retrieve cron info array option.
 *
 * @since WP-2.1.0
 * @access private
 *
 * @return false|array CRON info array.
 */
function _get_cron_array() {
	$cron = get_option( 'cron' );
	if ( ! is_array( $cron ) ) {
		return false;
	}

	if ( ! isset( $cron['version'] ) ) {
		$cron = _upgrade_cron_array( $cron );
	}

	unset( $cron['version'] );

	return $cron;
}

/**
 * Updates the CRON option with the new CRON array.
 *
 * @since WP-2.1.0
 * @since WP-5.0.0 Return value modified to outcome of {@see update_option}.
 *
 * @access private
 *
 * @param array $cron Cron info array from _get_cron_array().
 * @return bool True if cron array updated, false on failure.
 */
function _set_cron_array( $cron ) {
	$cron['version'] = 2;
	return update_option( 'cron', $cron );
}

/**
 * Upgrade a Cron info array.
 *
 * This function upgrades the Cron info array to version 2.
 *
 * @since WP-2.1.0
 * @access private
 *
 * @param array $cron Cron info array from _get_cron_array().
 * @return array An upgraded Cron info array.
 */
function _upgrade_cron_array( $cron ) {
	if ( isset( $cron['version'] ) && 2 == $cron['version'] ) {
		return $cron;
	}

	$new_cron = array();

	foreach ( (array) $cron as $timestamp => $hooks ) {
		foreach ( (array) $hooks as $hook => $args ) {
			$key                                     = md5( serialize( $args['args'] ) );
			$new_cron[ $timestamp ][ $hook ][ $key ] = $args;
		}
	}

	$new_cron['version'] = 2;
	update_option( 'cron', $new_cron );
	return $new_cron;
}
