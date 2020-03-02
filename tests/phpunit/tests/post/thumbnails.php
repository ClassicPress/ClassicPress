<?php

/**
 * @group post
 * @group media
 */
class Tests_Post_Thumbnail_Template extends WP_UnitTestCase {
	protected static $post;
	protected static $different_post;
	protected static $attachment_id;

	protected $current_size_filter_data = null;
	protected $current_size_filter_result = null;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$post           = $factory->post->create_and_get();
		self::$different_post = $factory->post->create_and_get();

		$file = DIR_TESTDATA . '/images/canola.jpg';
		self::$attachment_id = $factory->attachment->create_upload_object( $file, self::$post->ID, array(
			'post_mime_type' => 'image/jpeg',
		) );
	}

	public static function tearDownAfterClass() {
		wp_delete_post( self::$attachment_id, true );
		parent::tearDownAfterClass();
	}

	function test_has_post_thumbnail() {
		$this->assertFalse( has_post_thumbnail( self::$post ) );
		$this->assertFalse( has_post_thumbnail( self::$post->ID ) );
		$this->assertFalse( has_post_thumbnail() );

		$GLOBALS['post'] = self::$post;

		$this->assertFalse( has_post_thumbnail() );

		unset( $GLOBALS['post'] );

		set_post_thumbnail( self::$post, self::$attachment_id );

		$this->assertTrue( has_post_thumbnail( self::$post ) );
		$this->assertTrue( has_post_thumbnail( self::$post->ID ) );
		$this->assertFalse( has_post_thumbnail() );

		$GLOBALS['post'] = self::$post;

		$this->assertTrue( has_post_thumbnail() );
	}

	function test_get_post_thumbnail_id() {
		$this->assertEmpty( get_post_thumbnail_id( self::$post ) );
		$this->assertEmpty( get_post_thumbnail_id( self::$post->ID ) );
		$this->assertEmpty( get_post_thumbnail_id() );

		set_post_thumbnail( self::$post, self::$attachment_id );

		$this->assertEquals( self::$attachment_id, get_post_thumbnail_id( self::$post ) );
		$this->assertEquals( self::$attachment_id, get_post_thumbnail_id( self::$post->ID ) );

		$GLOBALS['post'] = self::$post;

		$this->assertEquals( self::$attachment_id, get_post_thumbnail_id() );
	}

	function test_update_post_thumbnail_cache() {
		set_post_thumbnail( self::$post, self::$attachment_id );

		$WP_Query = new WP_Query( array(
			'post_type' => 'any',
			'post__in'  => array( self::$post->ID ),
			'orderby'   => 'post__in',
		) );

		$this->assertFalse( $WP_Query->thumbnails_cached );

		update_post_thumbnail_cache( $WP_Query );

		$this->assertTrue( $WP_Query->thumbnails_cached );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12235
	 */
	function test_get_the_post_thumbnail_caption() {
		$this->assertEquals( '', get_the_post_thumbnail_caption() );

		$caption = 'This is a caption.';

		$post_id = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object( 'image.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
			'post_excerpt'   => $caption,
		) );

		set_post_thumbnail( $post_id, $attachment_id );

		$this->assertEquals( $caption, get_the_post_thumbnail_caption( $post_id ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12235
	 */
	function test_get_the_post_thumbnail_caption_empty() {
		$post_id = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object( 'image.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
			'post_excerpt'   => '',
		) );

		set_post_thumbnail( $post_id, $attachment_id );

		$this->assertEquals( '', get_the_post_thumbnail_caption( $post_id ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12235
	 */
	function test_the_post_thumbnail_caption() {
		$caption = 'This is a caption.';

		$post_id = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object( 'image.jpg', $post_id, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
			'post_excerpt'   => $caption,
		) );

		set_post_thumbnail( $post_id, $attachment_id );

		ob_start();
		the_post_thumbnail_caption( $post_id );

		$this->assertEquals( $caption, ob_get_clean() );
	}

	function test_get_the_post_thumbnail() {
		$this->assertEquals( '', get_the_post_thumbnail() );
		$this->assertEquals( '', get_the_post_thumbnail( self::$post ) );
		set_post_thumbnail( self::$post, self::$attachment_id );

		$expected = wp_get_attachment_image( self::$attachment_id, 'post-thumbnail', false, array(
			'class' => 'attachment-post-thumbnail size-post-thumbnail wp-post-image'
		) );

		$this->assertEquals( $expected, get_the_post_thumbnail( self::$post ) );

		$GLOBALS['post'] = self::$post;

		$this->assertEquals( $expected, get_the_post_thumbnail() );
	}

	function test_the_post_thumbnail() {
		ob_start();
		the_post_thumbnail();
		$actual = ob_get_clean();

		$this->assertEquals( '', $actual );

		$GLOBALS['post'] = self::$post;

		ob_start();
		the_post_thumbnail();
		$actual = ob_get_clean();

		$this->assertEquals( '', $actual );

		set_post_thumbnail( self::$post, self::$attachment_id );

		$expected = wp_get_attachment_image( self::$attachment_id, 'post-thumbnail', false, array(
			'class' => 'attachment-post-thumbnail size-post-thumbnail wp-post-image'
		) );

		ob_start();
		the_post_thumbnail();
		$actual = ob_get_clean();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/33070
	 */
	function test_get_the_post_thumbnail_url() {
		$this->assertFalse( has_post_thumbnail( self::$post ) );
		$this->assertFalse( get_the_post_thumbnail_url() );
		$this->assertFalse( get_the_post_thumbnail_url( self::$post ) );

		set_post_thumbnail( self::$post, self::$attachment_id );

		$this->assertFalse( get_the_post_thumbnail_url() );
		$this->assertEquals( wp_get_attachment_url( self::$attachment_id ), get_the_post_thumbnail_url( self::$post ) );

		$GLOBALS['post'] = self::$post;

		$this->assertEquals( wp_get_attachment_url( self::$attachment_id ), get_the_post_thumbnail_url() );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/33070
	 */
	function test_get_the_post_thumbnail_url_with_invalid_post() {
		set_post_thumbnail( self::$post, self::$attachment_id );

		$this->assertNotFalse( get_the_post_thumbnail_url( self::$post->ID ) );

		$deleted = wp_delete_post( self::$post->ID, true );
		$this->assertNotEmpty( $deleted );

		$this->assertFalse( get_the_post_thumbnail_url( self::$post->ID ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/33070
	 */
	function test_the_post_thumbnail_url() {
		$GLOBALS['post'] = self::$post;

		ob_start();
		the_post_thumbnail_url();
		$actual = ob_get_clean();

		$this->assertEmpty( $actual );

		ob_start();
		the_post_thumbnail_url();
		$actual = ob_get_clean();

		$this->assertEmpty( $actual );

		set_post_thumbnail( self::$post, self::$attachment_id );

		ob_start();
		the_post_thumbnail_url();
		$actual = ob_get_clean();

		$this->assertEquals( wp_get_attachment_url( self::$attachment_id ), $actual );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12922
	 */
	function test__wp_preview_post_thumbnail_filter() {
		$old_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

		$GLOBALS['post'] = self::$post;
		$_REQUEST['_thumbnail_id'] = self::$attachment_id;
		$_REQUEST['preview_id'] = self::$post->ID;

		$result = _wp_preview_post_thumbnail_filter( '', self::$post->ID, '_thumbnail_id' );

		// Clean up.
		$GLOBALS['post'] = $old_post;
		unset( $_REQUEST['_thumbnail_id'] );
		unset( $_REQUEST['preview_id'] );

		$this->assertEquals( self::$attachment_id, $result );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37697
	 */
	function test__wp_preview_post_thumbnail_filter_secondary_post() {
		$old_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

		$secondary_post = self::factory()->post->create( array(
				'post_stauts' => 'publish',
			)
		);

		$GLOBALS['post'] = self::$post;
		$_REQUEST['_thumbnail_id'] = self::$attachment_id;
		$_REQUEST['preview_id'] = $secondary_post;

		$result = _wp_preview_post_thumbnail_filter( '', self::$post->ID, '_thumbnail_id' );

		// Clean up.
		$GLOBALS['post'] = $old_post;
		unset( $_REQUEST['_thumbnail_id'] );
		unset( $_REQUEST['preview_id'] );

		$this->assertEmpty( $result );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/12922
	 */
	function test_insert_post_with_post_thumbnail() {
		$post_id = wp_insert_post( array(
			'ID'            => self::$post->ID,
			'post_status'   => 'publish',
			'post_content'  => 'Post content',
			'post_title'    => 'Post Title',
			'_thumbnail_id' => self::$attachment_id,
		) );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$this->assertEquals( self::$attachment_id, $thumbnail_id );

		$post_id = wp_insert_post( array(
			'ID'            => $post_id,
			'post_status'   => 'publish',
			'post_content'  => 'Post content',
			'post_title'    => 'Post Title',
			'_thumbnail_id' => - 1, // -1 removes post thumbnail.
		) );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$this->assertEmpty( $thumbnail_id );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37658
	 */
	function test_insert_attachment_with_post_thumbnail() {
		// Audio files support featured images.
		$post_id = wp_insert_post( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_content'   => 'Post content',
			'post_title'     => 'Post Title',
			'post_mime_type' => 'audio/mpeg',
			'post_parent'    => 0,
			'file'           => DIR_TESTDATA . '/audio/test-noise.mp3', // File does not exist, but does not matter here.
			'_thumbnail_id'  => self::$attachment_id,
		) );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$this->assertEquals( self::$attachment_id, $thumbnail_id );

		// Images do not support featured images.
		$post_id = wp_insert_post( array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_content'   => 'Post content',
			'post_title'     => 'Post Title',
			'post_mime_type' => 'image/jpeg',
			'post_parent'    => 0,
			'file'           => DIR_TESTDATA . '/images/canola.jpg',
			'_thumbnail_id'  => self::$attachment_id,
		) );

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$this->assertEmpty( $thumbnail_id );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39030
	 */
	function test_post_thumbnail_size_filter_simple() {
		$this->current_size_filter_data = 'medium';

		add_filter( 'post_thumbnail_size', array( $this, 'filter_post_thumbnail_size' ), 10, 2 );

		// This filter is used to capture the $size result.
		add_filter( 'post_thumbnail_html', array( $this, 'filter_set_post_thumbnail_size_result' ), 10, 4 );
		get_the_post_thumbnail( self::$post );

		$result = $this->current_size_filter_result;

		$this->current_size_filter_data   = null;
		$this->current_size_filter_result = null;

		$this->assertSame( 'medium', $result );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39030
	 * @dataProvider data_post_thumbnail_size_filter_complex
	 */
	function test_post_thumbnail_size_filter_complex( $which_post, $expected ) {
		$this->current_size_filter_data = array(
			self::$post->ID           => 'medium',
			self::$different_post->ID => 'thumbnail',
		);

		$post = $which_post === 1 ? self::$different_post : self::$post;

		add_filter( 'post_thumbnail_size', array( $this, 'filter_post_thumbnail_size' ), 10, 2 );

		// This filter is used to capture the $size result.
		add_filter( 'post_thumbnail_html', array( $this, 'filter_set_post_thumbnail_size_result' ), 10, 4 );
		get_the_post_thumbnail( $post );

		$result = $this->current_size_filter_result;

		$this->current_size_filter_data   = null;
		$this->current_size_filter_result = null;

		$this->assertSame( $expected, $result );
	}

	function data_post_thumbnail_size_filter_complex() {
		return array(
			array( 0, 'medium' ),
			array( 1, 'thumbnail' ),
		);
	}

	function filter_post_thumbnail_size( $size, $post_id ) {
		if ( is_array( $this->current_size_filter_data ) && isset( $this->current_size_filter_data[ $post_id ] ) ) {
			return $this->current_size_filter_data[ $post_id ];
		}

		if ( is_string( $this->current_size_filter_data ) ) {
			return $this->current_size_filter_data;
		}

		return $size;
	}

	function filter_set_post_thumbnail_size_result( $html, $post_id, $post_thumbnail_id, $size ) {
		$this->current_size_filter_result = $size;

		return $html;
	}
}
