<?php

/**
 * @group phpunit
 */
class Tests_TestHelpers extends WP_UnitTestCase {
	/**
	 * @ticket 30522
	 */
	public function data_assertSameSets() {
		return array(
			array(
				array( 1, 2, 3 ), // test expected
				array( 1, 2, 3 ), // test actual
				false,             // exception expected
			),
			array(
				array( 1, 2, 3 ),
				array( 2, 3, 1 ),
				false,
			),
			array(
				array( 1, 2, 3 ),
				array( 1, 2, 3, 4 ),
				true,
			),
			array(
				array( 1, 2, 3, 4 ),
				array( 1, 2, 3 ),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 3, 4, 2, 1 ),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 1, 2, 3, 3 ),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 2, 3, 1, 3 ),
				true,
			),
		);
	}

	/**
	 * @dataProvider data_assertSameSets
	 * @ticket 30522
	 */
	public function test_assertSameSets( $expected, $actual, $exception ) {
		if ( $exception ) {
			try {
				$this->assertSameSets( $expected, $actual );
			} catch ( PHPUnit_Framework_ExpectationFailedException $ex ) {
				return;
			}

			$this->fail();
		} else {
			$this->assertSameSets( $expected, $actual );
		}
	}

	/**
	 * @ticket 30522
	 */
	public function data_assertSameSetsWithIndex() {
		return array(
			array(
				array( 1, 2, 3 ), // test expected
				array( 1, 2, 3 ), // test actual
				false,             // exception expected
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				false,
			),
			array(
				array( 1, 2, 3 ),
				array( 2, 3, 1 ),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'b' => 2,
					'c' => 3,
					'a' => 1,
				),
				false,
			),
			array(
				array( 1, 2, 3 ),
				array( 1, 2, 3, 4 ),
				true,
			),
			array(
				array( 1, 2, 3, 4 ),
				array( 1, 2, 3 ),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 4,
				),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 4,
				),
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 3, 4, 2, 1 ),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'c' => 3,
					'b' => 2,
					'd' => 4,
					'a' => 1,
				),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 1, 2, 3, 3 ),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
					'd' => 3,
				),
				true,
			),
			array(
				array( 1, 2, 3 ),
				array( 2, 3, 1, 3 ),
				true,
			),
			array(
				array(
					'a' => 1,
					'b' => 2,
					'c' => 3,
				),
				array(
					'c' => 3,
					'b' => 2,
					'd' => 3,
					'a' => 1,
				),
				true,
			),
		);
	}
	/**
	 * @dataProvider data_assertSameSetsWithIndex
	 * @ticket 30522
	 */
	public function test_assertSameSetsWithIndex( $expected, $actual, $exception ) {
		if ( $exception ) {
			try {
				$this->assertSameSetsWithIndex( $expected, $actual );
			} catch ( PHPUnit_Framework_ExpectationFailedException $ex ) {
				return;
			}

			$this->fail();
		} else {
			$this->assertSameSetsWithIndex( $expected, $actual );
		}
	}

	public function test__unregister_post_status() {
		register_post_status( 'foo' );
		_unregister_post_status( 'foo' );

		$stati = get_post_stati();

		$this->assertFalse( isset( $stati['foo'] ) );
	}

	/**
	 * @ticket 28486
	 */
	public function test_setExpectedDeprecated() {
		$this->setExpectedDeprecated( 'Tests_TestHelpers::mock_deprecated' );
		$this->assertTrue( $this->mock_deprecated() );
	}

	/**
	 * @ticket 28486
	 */
	public function test_setExpectedIncorrectUsage() {
		$this->setExpectedIncorrectUsage( 'Tests_TestHelpers::mock_incorrect_usage' );
		$this->assertTrue( $this->mock_incorrect_usage() );
	}

	/**
	 * @ticket 31417
	 */
	public function test_go_to_should_go_to_home_page_when_passing_the_untrailingslashed_home_url() {
		$this->assertFalse( is_home() );
		$home = untrailingslashit( get_option( 'home' ) );
		$this->go_to( $home );
		$this->assertTrue( is_home() );
	}

	protected function mock_deprecated() {
		_deprecated_function( __METHOD__, 'WP-2.5' );
		return true;
	}

	protected function mock_incorrect_usage() {
		_doing_it_wrong( __METHOD__, __( 'Incorrect usage test' ), 'WP-2.5' );
		return true;
	}

	/**
	 * @ticket 36166
	 */
	public function test_die_handler_should_handle_wp_error() {
		$this->expectException( 'WPDieException' );

		wp_die( new WP_Error( 'test', 'test' ) );
	}

	/**
	 * This test is just a setup for the one that follows.
	 *
	 * @ticket 38196
	 */
	public function test_setup_postdata_globals_should_be_reset_on_teardown__setup() {
		$post                = self::factory()->post->create_and_get();
		$GLOBALS['wp_query'] = new WP_Query();
		$GLOBALS['wp_query']->setup_postdata( $post );
		$this->assertNotEmpty( $post );
	}

	/**
	 * @ticket 38196
	 */
	public function test_setup_postdata_globals_should_be_reset_on_teardown() {
		$globals = array( 'post', 'id', 'authordata', 'currentday', 'currentmonth', 'page', 'pages', 'multipage', 'more', 'numpages' );

		foreach ( $globals as $global ) {
			$this->assertTrue( ! isset( $GLOBALS[ $global ] ), $global );
		}
	}
}
