<?php
/**
 * @group media
 *
 * @covers ::get_post_galleries
 */
class Tests_Media_GetPostGalleries extends WP_UnitTestCase {

	const IMG_META = array(
		'width'  => 100,
		'height' => 100,
		'sizes'  => '',
	);

	/**
	 * Tests that an empty array is returned for a post that does not exist.
	 *
	 * @ticket 43826
	 */
	public function test_returns_empty_array_with_non_existent_post() {
		$galleries = get_post_galleries( 99999, false );
		$this->assertEmpty( $galleries );
	}

	/**
	 * Tests that an empty array is returned for a post that has no gallery.
	 *
	 * @ticket 43826
	 */
	public function test_returns_empty_array_with_post_with_no_gallery() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<p>A post with no gallery</p>',
			)
		);

		$galleries = get_post_galleries( $post_id, false );
		$this->assertEmpty( $galleries );
	}

	/**
	 * Tests that no srcs are returned for a shortcode gallery
	 * in a post with no attached images.
	 *
	 * @ticket 39304
	 *
	 * @group shortcode
	 */
	public function test_returns_no_srcs_with_shortcode_in_post_with_no_attached_images() {
		// Set up an unattached image.
		self::factory()->attachment->create_object(
			array(
				'file'           => 'test.jpg',
				'post_parent'    => 0,
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$post_id = self::factory()->post->create(
			array(
				'post_content' => '[gallery]',
			)
		);

		$galleries = get_post_galleries( $post_id, false );

		// The method can return an empty array.
		$this->assertNotEmpty(
			$galleries,
			'The galleries array is empty.'
		);

		/*
		 * This prevents future changes from causing
		 * backwards compatibility breaks.
		 */
		$this->assertArrayHasKey(
			'src',
			$galleries[0],
			'A src key does not exist.'
		);

		$this->assertEmpty(
			$galleries[0]['src'],
			'The src key is not empty.'
		);
	}

	/**
	 * Tests that HTML is returned for a shortcode gallery.
	 *
	 * @ticket 43826
	 *
	 * @group shortcode
	 */
	public function test_returns_html_with_shortcode_gallery() {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => 'I have no gallery',
			)
		);

		$post_id_two = self::factory()->post->create(
			array(
				'post_content' => "[gallery id='$post_id']",
			)
		);

		self::factory()->attachment->create_object(
			array(
				'file'           => 'test.jpg',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$expected  = 'src="http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/test.jpg"';
		$galleries = get_post_galleries( $post_id_two );

		// The method can return an empty array.
		$this->assertNotEmpty(
			$galleries,
			'The galleries array is empty.'
		);

		/*
		 * The method can return an array of arrays
		 * instead of an array of strings.
		 */
		$this->assertIsString(
			$galleries[0],
			'Did not return the data as a string.'
		);

		$this->assertStringContainsString(
			$expected,
			$galleries[0],
			'The returned data did not contain a src attribute with the expected image URL.'
		);
	}

	/**
	 * Tests that the global post object does not override
	 * a provided post ID with a shortcode gallery.
	 *
	 * @ticket 39304
	 *
	 * @group shortcode
	 */
	public function test_respects_post_id_with_shortcode_gallery() {
		$global_post_id = self::factory()->post->create(
			array(
				'post_content' => 'Global Post',
			)
		);
		$post_id        = self::factory()->post->create(
			array(
				'post_content' => '[gallery]',
			)
		);
		self::factory()->attachment->create_object(
			array(
				'file'           => 'test.jpg',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);
		$expected_srcs = array(
			'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/test.jpg',
		);

		// Set the global $post context to the other post.
		$GLOBALS['post'] = get_post( $global_post_id );

		$galleries = get_post_galleries( $post_id, false );

		// The method can return an empty array.
		$this->assertNotEmpty(
			$galleries,
			'The galleries array is empty.'
		);

		/*
		 * This prevents future changes from causing
		 * backwards compatibility breaks.
		 */
		$this->assertArrayHasKey(
			'src',
			$galleries[0],
			'A src key does not exist.'
		);

		$this->assertSameSetsWithIndex(
			$expected_srcs,
			$galleries[0]['src'],
			'The expected and actual srcs are not the same.'
		);
	}

	/**
	 * Tests that the gallery only contains images specified in
	 * the shortcode's id attribute.
	 *
	 * @ticket 39304
	 *
	 * @group shortcode
	 */
	public function test_respects_shortcode_id_attribute() {
		$post_id     = self::factory()->post->create(
			array(
				'post_content' => 'No gallery defined',
			)
		);
		$post_id_two = self::factory()->post->create(
			array(
				'post_content' => "[gallery id='$post_id']",
			)
		);
		self::factory()->attachment->create_object(
			array(
				'file'           => 'test.jpg',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);
		$expected_srcs = array(
			'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/test.jpg',
		);

		$galleries = get_post_galleries( $post_id_two, false );

		// Set the global $post context.
		$GLOBALS['post']               = get_post( $post_id_two );
		$galleries_with_global_context = get_post_galleries( $post_id_two, false );

		// Check that the global post state doesn't affect the results.
		$this->assertSameSetsWithIndex(
			$galleries,
			$galleries_with_global_context,
			'The global post state affected the results.'
		);

		// The method can return an empty array.
		$this->assertNotEmpty(
			$galleries,
			'The galleries array is empty.'
		);

		/*
		 * The method can return an array of strings
		 * instead of an array of arrays.
		 */
		$this->assertIsArray(
			$galleries[0],
			'The returned data does not contain an array.'
		);

		/*
		 * This prevents future changes from causing
		 * backwards compatibility breaks.
		 */
		$this->assertArrayHasKey(
			'src',
			$galleries[0],
			'A src key does not exist.'
		);

		$this->assertSameSetsWithIndex(
			$expected_srcs,
			$galleries[0]['src'],
			'The expected and actual srcs are not the same.'
		);
	}
}
