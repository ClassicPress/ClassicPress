<?php
/**
 * Test database schema defaults
 *
 * @group option
 */
class Tests_Schema extends WP_UnitTestCase {
	public function test_default_avatar_option() {
		$setting = get_option( 'show_avatars' );

		$this->assertSame( '0', $setting );
		$this->assertNotSame( '1', $setting );
	}

	public function test_comments_off_by_default() {
		$setting = get_option( 'default_comment_status' );

		$this->assertSame( 'closed', $setting );
		$this->assertNotSame( 'open', $setting );
	}

	/**
	* Test default blocks_compatibility_level value
	*/
	public function test_default_blocks_compatibility_level() {
		$setting = get_option( 'blocks_compatibility_level' );
		$this->assertSame( $setting, '1' );
	}
}
