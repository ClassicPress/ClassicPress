<?php

/**
 * Test the remove_filter method of WP_Hook
 *
 * @group hooks
 */
class Tests_WP_Hook_Remove_Filter extends WP_UnitTestCase {

	public function test_remove_filter_with_function() {
		$callback      = '__return_null';
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback, $priority, $accepted_args );
		$hook->remove_filter( $tag, $callback, $priority );

		$this->assertFalse( isset( $hook->callbacks[ $priority ] ) );
	}

	public function test_remove_filter_with_object() {
		$a             = new MockAction();
		$callback      = array( $a, 'action' );
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback, $priority, $accepted_args );
		$hook->remove_filter( $tag, $callback, $priority );

		$this->assertFalse( isset( $hook->callbacks[ $priority ] ) );
	}

	public function test_remove_filter_with_static_method() {
		$callback      = array( 'MockAction', 'action' );
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback, $priority, $accepted_args );
		$hook->remove_filter( $tag, $callback, $priority );

		$this->assertFalse( isset( $hook->callbacks[ $priority ] ) );
	}

	public function test_remove_filters_with_another_at_same_priority() {
		$callback_one  = '__return_null';
		$callback_two  = '__return_false';
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback_one, $priority, $accepted_args );
		$hook->add_filter( $tag, $callback_two, $priority, $accepted_args );

		$hook->remove_filter( $tag, $callback_one, $priority );

		$this->assertCount( 1, $hook->callbacks[ $priority ] );
	}

	public function test_remove_filter_with_another_at_different_priority() {
		$callback_one  = '__return_null';
		$callback_two  = '__return_false';
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback_one, $priority, $accepted_args );
		$hook->add_filter( $tag, $callback_two, $priority + 1, $accepted_args );

		$hook->remove_filter( $tag, $callback_one, $priority );
		$this->assertFalse( isset( $hook->callbacks[ $priority ] ) );
		$this->assertCount( 1, $hook->callbacks[ $priority + 1 ] );
<<<<<<< HEAD
=======
		$this->check_priority_exists( $hook, $priority + 1, 'Should priority of 3' );
	}

	protected function check_priority_non_existent( $hook, $priority ) {
		$priorities = $this->get_priorities( $hook );

		$this->assertNotContains( $priority, $priorities );
	}

	protected function check_priority_exists( $hook, $priority ) {
		$priorities = $this->get_priorities( $hook );

		$this->assertContains( $priority, $priorities );
	}

	protected function get_priorities( $hook ) {
		$reflection          = new ReflectionClass( $hook );
		$reflection_property = $reflection->getProperty( 'priorities' );
		if ( PHP_VERSION_ID < 80100 ) {
			$reflection_property->setAccessible( true );
		}

		return $reflection_property->getValue( $hook );
>>>>>>> cbb79cabb6 (Code Modernization: Address reflection no-op function deprecations in PHP 8.5.)
	}
}
