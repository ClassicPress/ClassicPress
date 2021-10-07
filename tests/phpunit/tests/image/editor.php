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
<<<<<<< HEAD
	public function setup() {
		require_once( ABSPATH . WPINC . '/class-wp-image-editor.php' );
=======
	public function set_up() {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)

		include_once( DIR_TESTDATA . '/../includes/mock-image-editor.php' );

<<<<<<< HEAD
		parent::setUp();
=======
		// This needs to come after the mock image editor class is loaded.
		parent::set_up();
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
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

		// Check default value.
		$this->assertSame( 82, $editor->get_quality() );

		// Ensure the quality filters do not have precedence if created after editor instantiation.
		$func_100_percent = array( $this, 'return_integer_100' );
		add_filter( 'wp_editor_set_quality', $func_100_percent );
		$this->assertSame( 82, $editor->get_quality() );

		$func_95_percent = array( $this, 'return_integer_95' );
		add_filter( 'jpeg_quality', $func_95_percent );
		$this->assertSame( 82, $editor->get_quality() );

		// Ensure set_quality() works and overrides the filters
		$this->assertTrue( $editor->set_quality( 75 ) );
		$this->assertSame( 75, $editor->get_quality() );

		// Get a new editor to clear default quality state
		unset( $editor );
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );
		$editor->set_mime_type( "image/jpeg" ); // Ensure mime-specific filters act properly.

		// Ensure jpeg_quality filter applies if it exists before editor instantiation.
		$this->assertSame( 95, $editor->get_quality() );

		// Get a new editor to clear jpeg_quality state
		remove_filter( 'jpeg_quality', $func_95_percent );
		unset( $editor );
		$editor = wp_get_image_editor( DIR_TESTDATA . '/images/canola.jpg' );

		// Ensure wp_editor_set_quality filter applies if it exists before editor instantiation.
		$this->assertSame( 100, $editor->get_quality() );

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

		// Test with no parameters.
		$this->assertSame( 'canola-100x50.jpg', wp_basename( $editor->generate_filename() ) );

		// Test with a suffix only.
		$this->assertSame( 'canola-new.jpg', wp_basename( $editor->generate_filename( 'new' ) ) );

		// Test with a destination dir only.
		$this->assertSame( trailingslashit( realpath( get_temp_dir() ) ), trailingslashit( realpath( dirname( $editor->generate_filename( null, get_temp_dir() ) ) ) ) );

		// Test with a suffix only.
		$this->assertSame( 'canola-100x50.png', wp_basename( $editor->generate_filename( null, null, 'png' ) ) );

		// Combo!
		$this->assertSame( trailingslashit( realpath( get_temp_dir() ) ) . 'canola-new.png', $editor->generate_filename( 'new', realpath( get_temp_dir() ), 'png' ) );
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

		$this->assertSame( $size, $editor->get_size() );
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

		$this->assertSame( '100x50', $editor->get_suffix() );
	}
}
