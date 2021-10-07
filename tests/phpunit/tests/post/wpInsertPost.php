<?php

/**
 * @group post
 */
<<<<<<< HEAD
class Tests_WPInsertPost extends WP_UnitTestCase {
=======
class Tests_Post_wpInsertPost extends WP_UnitTestCase {

	protected static $user_ids = array(
		'administrator' => null,
		'contributor'   => null,
	);

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_ids = array(
			'administrator' => $factory->user->create(
				array(
					'role' => 'administrator',
				)
			),
			'contributor'   => $factory->user->create(
				array(
					'role' => 'contributor',
				)
			),
		);

		$role = get_role( 'administrator' );
		$role->add_cap( 'publish_mapped_meta_caps' );
		$role->add_cap( 'publish_unmapped_meta_caps' );
	}

	static function tear_down_after_class() {
		$role = get_role( 'administrator' );
		$role->remove_cap( 'publish_mapped_meta_caps' );
		$role->remove_cap( 'publish_unmapped_meta_caps' );

		parent::tear_down_after_class();
	}

	function set_up() {
		parent::set_up();

		register_post_type(
			'mapped_meta_caps',
			array(
				'capability_type' => array( 'mapped_meta_cap', 'mapped_meta_caps' ),
				'map_meta_cap'    => true,
			)
		);

		register_post_type(
			'unmapped_meta_caps',
			array(
				'capability_type' => array( 'unmapped_meta_cap', 'unmapped_meta_caps' ),
				'map_meta_cap'    => false,
			)
		);

		register_post_type(
			'no_admin_caps',
			array(
				'capability_type' => array( 'no_admin_cap', 'no_admin_caps' ),
				'map_meta_cap'    => false,
			)
		);
	}
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)

	/**
	 * @see https://core.trac.wordpress.org/ticket/11863
	 */
	function test_trashing_a_post_should_add_trashed_suffix_to_post_name() {
		$trashed_about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish'
		) );
		wp_trash_post( $trashed_about_page_id );
		$this->assertSame( 'about__trashed', get_post( $trashed_about_page_id )->post_name );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/11863
	 */
	public function test_trashed_suffix_should_be_added_to_post_with__trashed_in_slug() {
		$trashed_about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish',
			'post_name' => 'foo__trashed__foo',
		) );
		wp_trash_post( $trashed_about_page_id );
		$this->assertSame( 'foo__trashed__foo__trashed', get_post( $trashed_about_page_id )->post_name );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/11863
	 */
	function test_trashed_posts_original_post_name_should_be_reassigned_after_untrashing() {
		$about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish'
		) );
		wp_trash_post( $about_page_id );

		wp_untrash_post( $about_page_id );
		$this->assertSame( 'about', get_post( $about_page_id )->post_name );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/11863
	 */
	function test_creating_a_new_post_should_add_trashed_suffix_to_post_name_of_trashed_posts_with_the_desired_slug() {
		$trashed_about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'trash'
		) );

		$about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish'
		) );

		$this->assertSame( 'about__trashed', get_post( $trashed_about_page_id )->post_name );
		$this->assertSame( 'about', get_post( $about_page_id )->post_name );
	}

	/**
	* @see https://core.trac.wordpress.org/ticket/11863
	*/
	function test_untrashing_a_post_with_a_stored_desired_post_name_should_get_its_post_name_suffixed_if_another_post_has_taken_the_desired_post_name() {
		$about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish'
		) );
		wp_trash_post( $about_page_id );

		$another_about_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_title' => 'About',
			'post_status' => 'publish'
		) );

		wp_untrash_post( $about_page_id );

		$this->assertSame( 'about', get_post( $another_about_page_id )->post_name );
		$this->assertSame( 'about-2', get_post( $about_page_id )->post_name );
	}
}
