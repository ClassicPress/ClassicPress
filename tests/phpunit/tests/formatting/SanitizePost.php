<?php
/**
 * @group formatting
 * @group post
 */
class Tests_Formatting_SanitizePost extends WP_UnitTestCase {

	/**
	 * @see https://core.trac.wordpress.org/ticket/22324
	 */
	function test_int_fields() {
		$post = self::factory()->post->create_and_get();
		$int_fields = array(
			'ID'            => 'integer',
			'post_parent'   => 'integer',
			'menu_order'    => 'integer',
			'post_author'   => 'string',
			'comment_count' => 'string',
		);

		foreach ( $int_fields as $field => $type ) {
			$this->assertInternalType( $type, $post->$field, "field $field" );
		}
	}
}
