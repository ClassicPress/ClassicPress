<?php

/**
 * Test the remove_all_filters method of WP_Hook
 *
 * @group hooks
 */
class Tests_WP_Hook_Remove_All_Filters extends WP_UnitTestCase {

	public function test_remove_all_filters() {
		$callback      = '__return_null';
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback, $priority, $accepted_args );

		$hook->remove_all_filters();

		$this->assertFalse( $hook->has_filters() );
	}

	public function test_remove_all_filters_with_priority() {
		$callback_one  = '__return_null';
		$callback_two  = '__return_false';
		$hook          = new WP_Hook();
		$tag           = __FUNCTION__;
		$priority      = rand( 1, 100 );
		$accepted_args = rand( 1, 100 );

		$hook->add_filter( $tag, $callback_one, $priority, $accepted_args );
		$hook->add_filter( $tag, $callback_two, $priority + 1, $accepted_args );

		$hook->remove_all_filters( $priority );

		$this->assertFalse( $hook->has_filter( $tag, $callback_one ) );
		$this->assertTrue( $hook->has_filters() );
<<<<<<< HEAD
		$this->assertSame( $priority + 1, $hook->has_filter( $tag, $callback_two ) );
=======
		$this->assertSame( $priority + 1, $hook->has_filter( $hook_name, $callback_two ) );
		$this->check_priority_exists( $hook, $priority + 1 );
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
