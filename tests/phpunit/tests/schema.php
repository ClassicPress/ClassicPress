<?php
/**
 * Test database schema defaults
 *
 * @group option
 */
class Tests_Schema extends WP_UnitTestCase {
	public function test_default_avatar_option() {
		$setting = get_option( 'show_avatars' );

		$this->assertEquals( '0', $setting );
		$this->assertNotEquals( '1', $setting );
	}
}
