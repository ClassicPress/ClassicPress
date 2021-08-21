<?php
/**
 * @group admin
 */
class Tests_Admin_includesTemplate extends WP_UnitTestCase {
	function test_equal() {
		$this->assertEquals(' selected=\'selected\'', selected('foo','foo',false));
		$this->assertEquals(' checked=\'checked\'', checked('foo','foo',false));

		$this->assertEquals(' selected=\'selected\'', selected('1',1,false));
		$this->assertEquals(' checked=\'checked\'', checked('1',1,false));

		$this->assertEquals(' selected=\'selected\'', selected('1',true,false));
		$this->assertEquals(' checked=\'checked\'', checked('1',true,false));

		$this->assertEquals(' selected=\'selected\'', selected(1,1,false));
		$this->assertEquals(' checked=\'checked\'', checked(1,1,false));

		$this->assertEquals(' selected=\'selected\'', selected(1,true,false));
		$this->assertEquals(' checked=\'checked\'', checked(1,true,false));

		$this->assertEquals(' selected=\'selected\'', selected(true,true,false));
		$this->assertEquals(' checked=\'checked\'', checked(true,true,false));

		$this->assertEquals(' selected=\'selected\'', selected('0',0,false));
		$this->assertEquals(' checked=\'checked\'', checked('0',0,false));

		$this->assertEquals(' selected=\'selected\'', selected(0,0,false));
		$this->assertEquals(' checked=\'checked\'', checked(0,0,false));

		$this->assertEquals(' selected=\'selected\'', selected('',false,false));
		$this->assertEquals(' checked=\'checked\'', checked('',false,false));

		$this->assertEquals(' selected=\'selected\'', selected(false,false,false));
		$this->assertEquals(' checked=\'checked\'', checked(false,false,false));
	}

	function test_notequal() {
		$this->assertEquals('', selected('0','',false));
		$this->assertEquals('', checked('0','',false));

		$this->assertEquals('', selected(0,'',false));
		$this->assertEquals('', checked(0,'',false));

		$this->assertEquals('', selected(0,false,false));
		$this->assertEquals('', checked(0,false,false));
	}

	public function test_add_meta_box() {
		global $wp_meta_boxes;

		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', 'post' );
		
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['post']['advanced']['default'] );
	}

	public function test_remove_meta_box() {
		global $wp_meta_boxes;

		// Add a meta box to remove.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', $current_screen = 'post' );

		// Confirm it's there.
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes[ $current_screen ]['advanced']['default'] );

		// Remove the meta box.
		remove_meta_box( 'testbox1', $current_screen, 'advanced' );

		// Check that it was removed properly (The meta box should be set to false once that it has been removed)
		$this->assertFalse( $wp_meta_boxes[ $current_screen ]['advanced']['default']['testbox1'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15000
	 */
	public function test_add_meta_box_on_multiple_screens() {
		global $wp_meta_boxes;

		// Add a meta box to three different post types
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', array( 'post', 'comment', 'attachment' ) );

		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['post']['advanced']['default'] ); 
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['comment']['advanced']['default'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['attachment']['advanced']['default'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15000
	 */
	public function test_remove_meta_box_from_multiple_screens() {
		global $wp_meta_boxes;

		// Add a meta box to three different screens.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', array( 'post', 'comment', 'attachment' ) );

		// Remove meta box from posts.
		remove_meta_box( 'testbox1', 'post', 'advanced' );

		// Check that we have removed the meta boxes only from posts
		$this->assertFalse( $wp_meta_boxes['post']['advanced']['default']['testbox1'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['comment']['advanced']['default'] );
		$this->assertArrayHasKey( 'testbox1', $wp_meta_boxes['attachment']['advanced']['default'] );

		// Remove the meta box from the other screens.
		remove_meta_box( 'testbox1', array( 'comment', 'attachment' ), 'advanced' );

		$this->assertFalse( $wp_meta_boxes['comment']['advanced']['default']['testbox1'] );
		$this->assertFalse( $wp_meta_boxes['attachment']['advanced']['default']['testbox1'] );
	}

<<<<<<< HEAD
}
=======
	/**
	 * @ticket 50019
	 */
	public function test_add_meta_box_with_previously_removed_box_and_sorted_priority() {
		global $wp_meta_boxes;

		// Add a meta box to remove.
		add_meta_box( 'testbox1', 'Test Metabox', '__return_false', $current_screen = 'post' );

		// Remove the meta box.
		remove_meta_box( 'testbox1', $current_screen, 'advanced' );

		// Attempt to re-add the meta box with the 'sorted' priority.
		add_meta_box( 'testbox1', null, null, $current_screen, 'advanced', 'sorted' );

		// Check that the meta box was not re-added.
		$this->assertFalse( $wp_meta_boxes[ $current_screen ]['advanced']['default']['testbox1'] );
	}

	/**
	 * Test calling get_settings_errors() with variations on where it gets errors from.
	 *
	 * @ticket 42498
	 * @covers ::get_settings_errors()
	 * @global array $wp_settings_errors
	 */
	public function test_get_settings_errors_sources() {
		global $wp_settings_errors;

		$blogname_error        = array(
			'setting' => 'blogname',
			'code'    => 'blogname',
			'message' => 'Capital P dangit!',
			'type'    => 'error',
		);
		$blogdescription_error = array(
			'setting' => 'blogdescription',
			'code'    => 'blogdescription',
			'message' => 'Too short',
			'type'    => 'error',
		);

		$wp_settings_errors = null;
		$this->assertSame( array(), get_settings_errors( 'blogname' ) );

		// Test getting errors from transient.
		$_GET['settings-updated'] = '1';
		set_transient( 'settings_errors', array( $blogname_error ) );
		$wp_settings_errors = null;
		$this->assertSame( array( $blogname_error ), get_settings_errors( 'blogname' ) );

		// Test getting errors from transient and from global.
		$_GET['settings-updated'] = '1';
		set_transient( 'settings_errors', array( $blogname_error ) );
		$wp_settings_errors = null;
		add_settings_error( $blogdescription_error['setting'], $blogdescription_error['code'], $blogdescription_error['message'], $blogdescription_error['type'] );
		$this->assertEqualSets( array( $blogname_error, $blogdescription_error ), get_settings_errors() );

		$wp_settings_errors = null;
	}

	/**
	 * @ticket 44941
	 * @covers ::settings_errors()
	 * @global array $wp_settings_errors
	 * @dataProvider settings_errors_css_classes_provider
	 */
	public function test_settings_errors_css_classes( $type, $expected ) {
		global $wp_settings_errors;

		add_settings_error( 'foo', 'bar', 'Capital P dangit!', $type );

		ob_start();
		settings_errors();
		$output = ob_get_clean();

		$wp_settings_errors = null;

		$expected = sprintf( 'notice %s settings-error is-dismissible', $expected );

		$this->assertContains( $expected, $output );
		$this->assertNotContains( 'notice-notice-', $output );
	}

	public function settings_errors_css_classes_provider() {
		return array(
			array( 'error', 'notice-error' ),
			array( 'success', 'notice-success' ),
			array( 'warning', 'notice-warning' ),
			array( 'info', 'notice-info' ),
			array( 'updated', 'notice-success' ),
			array( 'notice-error', 'notice-error' ),
			array( 'error my-own-css-class hello world', 'error my-own-css-class hello world' ),
		);
	}

}
>>>>>>> efb6e805da (Administration: Avoid a PHP 7.4 notice in `add_meta_box()` when attempting to re-add a previously removed box.)
