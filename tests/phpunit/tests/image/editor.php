<?php

require_once dirname( __FILE__ ) . '/base.php';

/**
 * Test the WP_Image_Editor base class
 * @group image
 * @group media
 */
class Tests_Image_Editor extends WP_Image_UnitTestCase {
	public $editor_engine = 'WP_Image_Editor_Mock';

	/**
	 * Setup test fixture
	 */
	public function setup() {
		require_once( ABSPATH . WPINC . '/class-wp-image-editor.php' );

		include_once( DIR_TESTDATA . '/../includes/mock-image-editor.php' );

		parent::setUp();
	}

	/**
	 * Test wp_get_image_editor() where load returns true
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_get_editor_load_returns_true() {
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		$this->assertInstanceOf( 'WP_Image_Editor_Mock', $editor );
	}

	/**
	 * Test wp_get_image_editor() where load returns false
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_get_editor_load_returns_false() {
		WP_Image_Editor_Mock::$load_return = new WP_Error();

		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		$this->assertInstanceOf( 'WP_Error', $editor );

		WP_Image_Editor_Mock::$load_return = true;
	}

	/**
	 * Return integer of 95 for testing.
	 */
	public function return_integer_95() {
		return 95;
	}

	/**
	 * Return integer of 100 for testing.
	 */
	public function return_integer_100() {
		return 100;
	}

	/**
	 * Test test_quality
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_set_quality() {

		// Get an editor
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );
		$editor->set_mime_type( "image/jpeg" ); // Ensure mime-specific filters act properly.

		// Check default value
		$this->assertEquals( 82, $editor->get_quality() );

		// Ensure the quality filters do not have precedence if created after editor instantiation.
		$func_100_percent = array( $this, 'return_integer_100' );
		add_filter( 'wp_editor_set_quality', $func_100_percent );
		$this->assertEquals( 82, $editor->get_quality() );

		$func_95_percent = array( $this, 'return_integer_95' );
		add_filter( 'jpeg_quality', $func_95_percent );
		$this->assertEquals( 82, $editor->get_quality() );

		// Ensure set_quality() works and overrides the filters
		$this->assertTrue( $editor->set_quality( 75 ) );
		$this->assertEquals( 75, $editor->get_quality() );

		// Get a new editor to clear default quality state
		unset( $editor );
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );
		$editor->set_mime_type( "image/jpeg" ); // Ensure mime-specific filters act properly.

		// Ensure jpeg_quality filter applies if it exists before editor instantiation.
		$this->assertEquals( 95, $editor->get_quality() );

		// Get a new editor to clear jpeg_quality state
		remove_filter( 'jpeg_quality', $func_95_percent );
		unset( $editor );
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		// Ensure wp_editor_set_quality filter applies if it exists before editor instantiation.
		$this->assertEquals( 100, $editor->get_quality() );

		// Clean up
		remove_filter( 'wp_editor_set_quality', $func_100_percent );
	}

	/**
	 * Test generate_filename
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_generate_filename() {

		// Get an editor
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		$property = new ReflectionProperty( $editor, 'size' );
		$property->setAccessible( true );
		$property->setValue( $editor, array(
			'height' => 50,
			'width'  => 100
		));

		// Test with no parameters
		$this->assertEquals( 'canola-100x50.jpg', basename( $editor->generate_filename() ) );

		// Test with a suffix only
		$this->assertEquals( 'canola-new.jpg', basename( $editor->generate_filename( 'new' ) ) );

		// Test with a destination dir only
		$this->assertEquals(trailingslashit( realpath( get_temp_dir() ) ), trailingslashit( realpath( dirname( $editor->generate_filename( null, get_temp_dir() ) ) ) ) );

		// Test with a suffix only
		$this->assertEquals( 'canola-100x50.png', basename( $editor->generate_filename( null, null, 'png' ) ) );

		// Combo!
		$this->assertEquals( trailingslashit( realpath( get_temp_dir() ) ) . 'canola-new.png', $editor->generate_filename( 'new', realpath( get_temp_dir() ), 'png' ) );
	}

	/**
	 * Test get_size
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_get_size() {

		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		// Size should be false by default
		$this->assertNull( $editor->get_size() );

		// Set a size
		$size = array(
			'height' => 50,
			'width'  => 100
		);
		$property = new ReflectionProperty( $editor, 'size' );
		$property->setAccessible( true );
		$property->setValue( $editor, $size );

		$this->assertEquals( $size, $editor->get_size() );
	}

	/**
	 * Test get_suffix
	 * @see https://core.trac.wordpress.org/ticket/6821
	 */
	public function test_get_suffix() {
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		// Size should be false by default
		$this->assertFalse( $editor->get_suffix() );

		// Set a size
		$size = array(
			'height' => 50,
			'width'  => 100
		);
		$property = new ReflectionProperty( $editor, 'size' );
		$property->setAccessible( true );
		$property->setValue( $editor, $size );

		$this->assertEquals( '100x50', $editor->get_suffix() );
	}

	/**
	 * Test wp_get_webp_info.
	 *
	 * @ticket 35725
	 * @dataProvider _test_wp_get_webp_info
	 *
	 */
	public function test_wp_get_webp_info( $file, $expected ) {
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) || ! $editor->supports_mime_type( 'image/webp' ) ) {
			$this->markTestSkipped( sprintf( 'Skipping test: no WebP support in the editor engine %s on this system.', $this->editor_engine ) );
		} else {
			$file_data = wp_get_webp_info( $file );
			$this->assertSame( $file_data, $expected );
		}
	}

	/**
	 * Data provider for test_wp_get_webp_info();
	 */
	public function _test_wp_get_webp_info() {
		return array(
			// Standard JPEG.
			array(
				DIR_TESTDATA . '/images/test-image.jpg',
				array(
					'width'  => false,
					'height' => false,
					'type'   => false,
				),
			),
			// Standard GIF.
			array(
				DIR_TESTDATA . '/images/test-image.gif',
				array(
					'width'  => false,
					'height' => false,
					'type'   => false,
				),
			),
			// Animated WebP.
			array(
				DIR_TESTDATA . '/images/webp-animated.webp',
				array(
					'width'  => 100,
					'height' => 100,
					'type'   => 'animated-alpha',
				),
			),
			// Lossless WebP.
			array(
				DIR_TESTDATA . '/images/webp-lossless.webp',
				array(
					'width'  => 1200,
					'height' => 675,
					'type'   => 'lossless',
				),
			),
			// Lossy WebP.
			array(
				DIR_TESTDATA . '/images/webp-lossy.webp',
				array(
					'width'  => 1200,
					'height' => 675,
					'type'   => 'lossy',
				),
			),
			// Transparent WebP.
			array(
				DIR_TESTDATA . '/images/webp-transparent.webp',
				array(
					'width'  => 1200,
					'height' => 675,
					'type'   => 'animated-alpha',
				),
			),
		);
	}

}
