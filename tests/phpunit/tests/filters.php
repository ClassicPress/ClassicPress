<?php

/**
 * Test apply_filters() and related functions
 *
 * @group hooks
 */
class Tests_Filters extends WP_UnitTestCase {

	function test_simple_filter() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

<<<<<<< HEAD
		add_filter($tag, array($a, 'filter'));
		$this->assertEquals($val, apply_filters($tag, $val));

		// only one event occurred for the hook, with empty args
		$this->assertEquals(1, $a->get_call_count());
		// only our hook was called
		$this->assertEquals(array($tag), $a->get_tags());

		$argsvar = $a->get_args();
		$args = array_pop( $argsvar );
		$this->assertEquals(array($val), $args);
=======
		add_filter( $tag, array( $a, 'filter' ) );
		$this->assertSame( $val, apply_filters( $tag, $val ) );

		// Only one event occurred for the hook, with empty args.
		$this->assertSame( 1, $a->get_call_count() );
		// Only our hook was called.
		$this->assertSame( array( $tag ), $a->get_tags() );

		$argsvar = $a->get_args();
		$args    = array_pop( $argsvar );
		$this->assertSame( array( $val ), $args );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_remove_filter() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

<<<<<<< HEAD
		add_filter($tag, array($a, 'filter'));
		$this->assertEquals($val, apply_filters($tag, $val));

		// make sure our hook was called correctly
		$this->assertEquals(1, $a->get_call_count());
		$this->assertEquals(array($tag), $a->get_tags());

		// now remove the filter, do it again, and make sure it's not called this time
		remove_filter($tag, array($a, 'filter'));
		$this->assertEquals($val, apply_filters($tag, $val));
		$this->assertEquals(1, $a->get_call_count());
		$this->assertEquals(array($tag), $a->get_tags());
=======
		add_filter( $tag, array( $a, 'filter' ) );
		$this->assertSame( $val, apply_filters( $tag, $val ) );

		// Make sure our hook was called correctly.
		$this->assertSame( 1, $a->get_call_count() );
		$this->assertSame( array( $tag ), $a->get_tags() );

		// Now remove the filter, do it again, and make sure it's not called this time.
		remove_filter( $tag, array( $a, 'filter' ) );
		$this->assertSame( $val, apply_filters( $tag, $val ) );
		$this->assertSame( 1, $a->get_call_count() );
		$this->assertSame( array( $tag ), $a->get_tags() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

	}

	function test_has_filter() {
			$tag  = __FUNCTION__;
			$func = __FUNCTION__ . '_func';

<<<<<<< HEAD
			$this->assertFalse( has_filter($tag, $func) );
			$this->assertFalse( has_filter($tag) );
			add_filter($tag, $func);
			$this->assertEquals( 10, has_filter($tag, $func) );
			$this->assertTrue( has_filter($tag) );
			remove_filter($tag, $func);
			$this->assertFalse( has_filter($tag, $func) );
			$this->assertFalse( has_filter($tag) );
=======
			$this->assertFalse( has_filter( $tag, $func ) );
			$this->assertFalse( has_filter( $tag ) );
			add_filter( $tag, $func );
			$this->assertSame( 10, has_filter( $tag, $func ) );
			$this->assertTrue( has_filter( $tag ) );
			remove_filter( $tag, $func );
			$this->assertFalse( has_filter( $tag, $func ) );
			$this->assertFalse( has_filter( $tag ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	// one tag with multiple filters
	function test_multiple_filters() {
		$a1 = new MockAction();
		$a2 = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

		// add both filters to the hook
		add_filter($tag, array($a1, 'filter'));
		add_filter($tag, array($a2, 'filter'));

<<<<<<< HEAD
		$this->assertEquals($val, apply_filters($tag, $val));

		// both filters called once each
		$this->assertEquals(1, $a1->get_call_count());
		$this->assertEquals(1, $a2->get_call_count());
=======
		$this->assertSame( $val, apply_filters( $tag, $val ) );

		// Both filters called once each.
		$this->assertSame( 1, $a1->get_call_count() );
		$this->assertSame( 1, $a2->get_call_count() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_filter_args_1() {
		$a = new MockAction();
		$tag  = __FUNCTION__;
		$val  = __FUNCTION__ . '_val';
		$arg1 = __FUNCTION__ . '_arg1';

<<<<<<< HEAD
		add_filter($tag, array($a, 'filter'), 10, 2);
		// call the filter with a single argument
		$this->assertEquals($val, apply_filters($tag, $val, $arg1));

		$this->assertEquals(1, $a->get_call_count());
=======
		add_filter( $tag, array( $a, 'filter' ), 10, 2 );
		// Call the filter with a single argument.
		$this->assertSame( $val, apply_filters( $tag, $val, $arg1 ) );

		$this->assertSame( 1, $a->get_call_count() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		$argsvar = $a->get_args();
		$this->assertSame( array( $val, $arg1 ), array_pop( $argsvar ) );
	}

	function test_filter_args_2() {
		$a1 = new MockAction();
		$a2 = new MockAction();
		$tag  = __FUNCTION__;
		$val  = __FUNCTION__ . '_val';
		$arg1 = __FUNCTION__ . '_arg1';
		$arg2 = __FUNCTION__ . '_arg2';

<<<<<<< HEAD
		// a1 accepts two arguments, a2 doesn't
		add_filter($tag, array($a1, 'filter'), 10, 3);
		add_filter($tag, array($a2, 'filter'));
		// call the filter with two arguments
		$this->assertEquals($val, apply_filters($tag, $val, $arg1, $arg2));

		// a1 should be called with both args
		$this->assertEquals(1, $a1->get_call_count());
=======
		// $a1 accepts two arguments, $a2 doesn't.
		add_filter( $tag, array( $a1, 'filter' ), 10, 3 );
		add_filter( $tag, array( $a2, 'filter' ) );
		// Call the filter with two arguments.
		$this->assertSame( $val, apply_filters( $tag, $val, $arg1, $arg2 ) );

		// $a1 should be called with both args.
		$this->assertSame( 1, $a1->get_call_count() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		$argsvar1 = $a1->get_args();
		$this->assertSame( array( $val, $arg1, $arg2 ), array_pop( $argsvar1 ) );

<<<<<<< HEAD
		// a2 should be called with one only
		$this->assertEquals(1, $a2->get_call_count());
=======
		// $a2 should be called with one only.
		$this->assertSame( 1, $a2->get_call_count() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		$argsvar2 = $a2->get_args();
		$this->assertSame( array( $val ), array_pop( $argsvar2 ) );
	}

	function test_filter_priority() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

<<<<<<< HEAD
		// make two filters with different priorities
		add_filter($tag, array($a, 'filter'), 10);
		add_filter($tag, array($a, 'filter2'), 9);
		$this->assertEquals($val, apply_filters($tag, $val));

		// there should be two events, one per filter
		$this->assertEquals(2, $a->get_call_count());
=======
		// Make two filters with different priorities.
		add_filter( $tag, array( $a, 'filter' ), 10 );
		add_filter( $tag, array( $a, 'filter2' ), 9 );
		$this->assertSame( $val, apply_filters( $tag, $val ) );

		// There should be two events, one per filter.
		$this->assertSame( 2, $a->get_call_count() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$expected = array (
			// filter2 is called first because it has priority 9
			array (
				'filter' => 'filter2',
				'tag' => $tag,
				'args' => array($val)
			),
			// filter 1 is called second
			array (
				'filter' => 'filter',
				'tag' => $tag,
				'args' => array($val)
			),
		);

<<<<<<< HEAD
		$this->assertEquals($expected, $a->get_events());
=======
		$this->assertSame( $expected, $a->get_events() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_all_filter() {
		$a = new MockAction();
		$tag1 = __FUNCTION__ . '_1';
		$tag2 = __FUNCTION__ . '_2';
		$val  = __FUNCTION__ . '_val';

<<<<<<< HEAD
		// add an 'all' filter
		add_filter('all', array($a, 'filterall'));
		// do some filters
		$this->assertEquals($val, apply_filters($tag1, $val));
		$this->assertEquals($val, apply_filters($tag2, $val));
		$this->assertEquals($val, apply_filters($tag1, $val));
		$this->assertEquals($val, apply_filters($tag1, $val));

		// our filter should have been called once for each apply_filters call
		$this->assertEquals(4, $a->get_call_count());
		// the right hooks should have been called in order
		$this->assertEquals(array($tag1, $tag2, $tag1, $tag1), $a->get_tags());
=======
		// Add an 'all' filter.
		add_filter( 'all', array( $a, 'filterall' ) );
		// Apply some filters.
		$this->assertSame( $val, apply_filters( $tag1, $val ) );
		$this->assertSame( $val, apply_filters( $tag2, $val ) );
		$this->assertSame( $val, apply_filters( $tag1, $val ) );
		$this->assertSame( $val, apply_filters( $tag1, $val ) );

		// Our filter should have been called once for each apply_filters call.
		$this->assertSame( 4, $a->get_call_count() );
		// The right hooks should have been called in order.
		$this->assertSame( array( $tag1, $tag2, $tag1, $tag1 ), $a->get_tags() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		remove_filter('all', array($a, 'filterall'));
		$this->assertFalse( has_filter('all', array($a, 'filterall')) );

	}

	function test_remove_all_filter() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

<<<<<<< HEAD
		add_filter('all', array($a, 'filterall'));
		$this->assertTrue( has_filter('all') );
		$this->assertEquals( 10, has_filter('all', array($a, 'filterall')) );
		$this->assertEquals($val, apply_filters($tag, $val));

		// make sure our hook was called correctly
		$this->assertEquals(1, $a->get_call_count());
		$this->assertEquals(array($tag), $a->get_tags());

		// now remove the filter, do it again, and make sure it's not called this time
		remove_filter('all', array($a, 'filterall'));
		$this->assertFalse( has_filter('all', array($a, 'filterall')) );
		$this->assertFalse( has_filter('all') );
		$this->assertEquals($val, apply_filters($tag, $val));
		// call cound should remain at 1
		$this->assertEquals(1, $a->get_call_count());
		$this->assertEquals(array($tag), $a->get_tags());
=======
		add_filter( 'all', array( $a, 'filterall' ) );
		$this->assertTrue( has_filter( 'all' ) );
		$this->assertSame( 10, has_filter( 'all', array( $a, 'filterall' ) ) );
		$this->assertSame( $val, apply_filters( $tag, $val ) );

		// Make sure our hook was called correctly.
		$this->assertSame( 1, $a->get_call_count() );
		$this->assertSame( array( $tag ), $a->get_tags() );

		// Now remove the filter, do it again, and make sure it's not called this time.
		remove_filter( 'all', array( $a, 'filterall' ) );
		$this->assertFalse( has_filter( 'all', array( $a, 'filterall' ) ) );
		$this->assertFalse( has_filter( 'all' ) );
		$this->assertSame( $val, apply_filters( $tag, $val ) );
		// Call cound should remain at 1.
		$this->assertSame( 1, $a->get_call_count() );
		$this->assertSame( array( $tag ), $a->get_tags() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/20920
	 */
	function test_remove_all_filters_should_respect_the_priority_argument() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

		add_filter( $tag, array( $a, 'filter' ), 12 );
		$this->assertTrue( has_filter( $tag ) );

		// Should not be removed.
		remove_all_filters( $tag, 11 );
		$this->assertTrue( has_filter( $tag ) );

		remove_all_filters( $tag, 12 );
		$this->assertFalse( has_filter( $tag ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/9886
	 */
	function test_filter_ref_array() {
		$obj = new stdClass();
		$a = new MockAction();
		$tag = __FUNCTION__;

		add_action($tag, array($a, 'filter'));

		apply_filters_ref_array($tag, array(&$obj));

		$args = $a->get_args();
		$this->assertSame($args[0][0], $obj);
		// just in case we don't trust assertSame
		$obj->foo = true;
		$this->assertFalse( empty($args[0][0]->foo) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12723
	 */
	function test_filter_ref_array_result() {
		$obj = new stdClass();
		$a = new MockAction();
		$b = new MockAction();
		$tag = __FUNCTION__;

		add_action($tag, array($a, 'filter_append'), 10, 2);
		add_action($tag, array($b, 'filter_append'), 10, 2);

		$result = apply_filters_ref_array($tag, array('string', &$obj));

<<<<<<< HEAD
		$this->assertEquals($result, 'string_append_append');
=======
		$this->assertSame( $result, 'string_append_append' );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$args = $a->get_args();
		$this->assertSame($args[0][1], $obj);
		// just in case we don't trust assertSame
		$obj->foo = true;
		$this->assertFalse( empty($args[0][1]->foo) );

		$args = $b->get_args();
		$this->assertSame($args[0][1], $obj);
		// just in case we don't trust assertSame
		$obj->foo = true;
		$this->assertFalse( empty($args[0][1]->foo) );

	}

	function _self_removal($tag) {
		remove_action( $tag, array($this, '_self_removal'), 10, 1 );
		return $tag;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/29070
	 */
	function test_has_filter_after_remove_all_filters() {
		$a = new MockAction();
		$tag = __FUNCTION__;
		$val = __FUNCTION__ . '_val';

		// No priority
		add_filter( $tag, array( $a, 'filter' ), 11 );
		add_filter( $tag, array( $a, 'filter' ), 12 );
		$this->assertTrue( has_filter( $tag ) );

		remove_all_filters( $tag );
		$this->assertFalse( has_filter( $tag ) );

		// Remove priorities one at a time
		add_filter( $tag, array( $a, 'filter' ), 11 );
		add_filter( $tag, array( $a, 'filter' ), 12 );
		$this->assertTrue( has_filter( $tag ) );

		remove_all_filters( $tag, 11 );
		remove_all_filters( $tag, 12 );
		$this->assertFalse( has_filter( $tag ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/10441
	 * @expectedDeprecated tests_apply_filters_deprecated
	 */
	public function test_apply_filters_deprecated() {
		$p = 'Foo';

		add_filter( 'tests_apply_filters_deprecated', array( __CLASS__, 'deprecated_filter_callback' ) );
		$p = apply_filters_deprecated( 'tests_apply_filters_deprecated', array( $p ), 'WP-4.6' );
		remove_filter( 'tests_apply_filters_deprecated', array( __CLASS__, 'deprecated_filter_callback' ) );

		$this->assertSame( 'Bar', $p );
	}

	public static function deprecated_filter_callback( $p ) {
		$p = 'Bar';
		return $p;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/10441
	 * @expectedDeprecated tests_apply_filters_deprecated
	 */
	public function test_apply_filters_deprecated_with_multiple_params() {
		$p1 = 'Foo1';
		$p2 = 'Foo2';

		add_filter( 'tests_apply_filters_deprecated', array( __CLASS__, 'deprecated_filter_callback_multiple_params' ), 10, 2 );
		$p1 = apply_filters_deprecated( 'tests_apply_filters_deprecated', array( $p1, $p2 ), 'WP-4.6' );
		remove_filter( 'tests_apply_filters_deprecated', array( __CLASS__, 'deprecated_filter_callback_multiple_params' ), 10, 2 );

		$this->assertSame( 'Bar1', $p1 );

		// Not passed by reference, so not modified.
		$this->assertSame( 'Foo2', $p2 );
	}

	public static function deprecated_filter_callback_multiple_params( $p1, $p2 ) {
		$p1 = 'Bar1';
		$p2 = 'Bar2';

		return $p1;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/10441
	 */
	public function test_apply_filters_deprecated_without_filter() {
		$val = 'Foobar';

		$this->assertSame( $val, apply_filters_deprecated( 'tests_apply_filters_deprecated', array( $val ), 'WP-4.6' ) );
	}

	private $current_priority;
	/**
	 * @see https://core.trac.wordpress.org/ticket/39007
	 */
	public function test_current_priority() {
		add_action( 'test_current_priority', array( $this, '_current_priority_action' ), 99 );
		do_action( 'test_current_priority' );
		remove_action( 'test_current_priority', array( $this, '_current_priority_action' ), 99 );

		$this->assertSame( 99, $this->current_priority );
	}

	public function _current_priority_action() {
		global $wp_filter;
		$this->current_priority = $wp_filter[ current_filter() ]->current_priority();
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39007
	 */
	public function test_other_priority() {
		add_action( 'test_current_priority', array( $this, '_other_priority_action' ), 99 );
		do_action( 'test_current_priority' );
		remove_action( 'test_current_priority', array( $this, '_other_priority_action' ), 99 );

		$this->assertFalse( $this->current_priority );
	}

	public function _other_priority_action() {
		global $wp_filter;
		$this->current_priority = $wp_filter[ 'the_content' ]->current_priority();
	}
}
