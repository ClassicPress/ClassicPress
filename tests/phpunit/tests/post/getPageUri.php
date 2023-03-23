<?php

/**
 * @group post
 */
class Tests_Post_GetPageUri extends WP_UnitTestCase {

	/**
	 * @ticket 22883
	 */
	public function test_get_page_uri_with_stdclass_post_object() {
		$post_id = self::factory()->post->create( array( 'post_name' => 'get-page-uri-post-name' ) );

		// Mimick an old stdClass post object, missing the ancestors field.
		$post_array = (object) get_post( $post_id, ARRAY_A );
		unset( $post_array->ancestors );

		// Dummy assertion. If this test fails, it will actually error out on an E_WARNING.
		$this->assertSame( 'get-page-uri-post-name', get_page_uri( $post_array ) );
	}

	/**
	 * @ticket 24491
	 */
	public function test_get_page_uri_with_nonexistent_post() {
		global $wpdb;
		$post_id = $wpdb->get_var( "SELECT MAX(ID) FROM $wpdb->posts" ) + 1;
		$this->assertFalse( get_page_uri( $post_id ) );
	}

	/**
	 * @ticket 15963
	 */
	public function test_get_post_uri_check_orphan() {
		$parent_id = self::factory()->post->create( array( 'post_name' => 'parent' ) );
		$child_id  = self::factory()->post->create(
			array(
				'post_name'   => 'child',
				'post_parent' => $parent_id,
			)
		);

		// Check the parent for good measure.
		$this->assertSame( 'parent', get_page_uri( $parent_id ) );

		// Try the child normally.
		$this->assertSame( 'parent/child', get_page_uri( $child_id ) );

		// Now delete the parent from the database and check.
		wp_delete_post( $parent_id, true );
		$this->assertSame( 'child', get_page_uri( $child_id ) );
	}

	/**
	 * @ticket 36174
	 */
	public function test_get_page_uri_with_a_draft_parent_with_empty_slug() {
		$parent_id = self::factory()->post->create( array( 'post_name' => 'parent' ) );
		$child_id  = self::factory()->post->create(
			array(
				'post_name'   => 'child',
				'post_parent' => $parent_id,
			)
		);

		wp_update_post(
			array(
				'ID'          => $parent_id,
				'post_name'   => '',
				'post_status' => 'draft',
			)
		);

		$this->assertSame( 'child', get_page_uri( $child_id ) );
	}

	/**
	 * @ticket 26284
	 */
	public function test_get_page_uri_without_argument() {
		$post_id = self::factory()->post->create(
			array(
				'post_title' => 'Blood Orange announces summer tour dates',
				'post_name'  => 'blood-orange-announces-summer-tour-dates',
			)
		);
		$post    = get_post( $post_id );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertSame( 'blood-orange-announces-summer-tour-dates', get_page_uri() );
	}
}
