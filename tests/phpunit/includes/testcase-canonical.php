<?php

abstract class WP_Canonical_UnitTestCase extends WP_UnitTestCase {
	public static $old_current_user;
	public static $author_id;
	public static $post_ids    = array();
	public static $comment_ids = array();
	public static $term_ids    = array();
	public static $terms       = array();
	public static $old_options = array();

	/**
	 * This can be defined in a subclass of this class which contains its own data() method.
	 * Those tests will be run against the specified permastruct.
	 */
	public $structure = '/%year%/%monthnum%/%day%/%postname%/';

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::generate_shared_fixtures( $factory );
	}

	public static function wpTearDownAfterClass() {
		self::delete_shared_fixtures();
	}

	public function set_up() {
		parent::set_up();

		update_option( 'page_comments', true );
		update_option( 'comments_per_page', 5 );
		update_option( 'posts_per_page', 5 );

		$this->set_permalink_structure( $this->structure );
		create_initial_taxonomies();
	}

	/**
	 * Generate fixtures to be shared between canonical tests.
	 *
	 * Abstracted here because it's invoked by wpSetUpBeforeClass() in more than one class.
	 *
	 * @since 4.1.0
	 */
	public static function generate_shared_fixtures( WP_UnitTest_Factory $factory ) {
		self::$old_current_user = get_current_user_id();
		self::$author_id        = $factory->user->create( array( 'user_login' => 'canonical-author' ) );

		/*
		 * Also set in self::set_up(), but we must configure here to make sure that
		 * post authorship is properly attributed for fixtures.
		 */
		wp_set_current_user( self::$author_id );

		// Already created by install defaults:
		// $factory->term->create( array( 'taxonomy' => 'category', 'name' => 'uncategorized' ) );

		self::$post_ids[] = $factory->post->create(
			array(
				'import_id'  => 587,
				'post_title' => 'post-format-test-audio',
				'post_date'  => '2008-06-02 00:00:00',
			)
		);

		$gallery_post_id = $factory->post->create(
			array(
				'post_title' => 'post-format-test-gallery',
				'post_date'  => '2008-06-10 00:00:00',
			)
		);

		self::$post_ids[] = $gallery_post_id;

		self::$post_ids[] = $factory->post->create(
			array(
				'import_id'   => 611,
				'post_type'   => 'attachment',
				'post_title'  => 'canola2',
				'post_parent' => $gallery_post_id,
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_title' => 'images-test',
				'post_date'  => '2008-09-03 00:00:00',
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_title'   => 'multipage-post-test',
				'post_date'    => '2008-09-03 00:00:00',
				'post_content' => 'Page 1 <!--nextpage--> Page 2 <!--nextpage--> Page 3',
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_title' => 'non-paged-post-test',
				'post_date'  => '2008-09-03 00:00:00',
			)
		);

		$comment_post_id = $factory->post->create(
			array(
				'import_id'  => 149,
				'post_title' => 'comment-test',
				'post_date'  => '2008-03-03 00:00:00',
			)
		);

		self::$post_ids[]  = $comment_post_id;
		self::$comment_ids = $factory->comment->create_post_comments( $comment_post_id, 15 );

		self::$post_ids[] = $factory->post->create( array( 'post_date' => '2008-09-05 00:00:00' ) );

		self::$post_ids[] = $factory->post->create( array( 'import_id' => 123 ) );
		self::$post_ids[] = $factory->post->create( array( 'import_id' => 1 ) );
		self::$post_ids[] = $factory->post->create( array( 'import_id' => 358 ) );

		self::$post_ids[] = $factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'sample-page',
			)
		);

		self::$post_ids[] = $factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'about',
			)
		);

		$parent_page_id = $factory->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'parent-page',
			)
		);

		self::$post_ids[] = $parent_page_id;

		self::$post_ids[] = $factory->post->create(
			array(
				'import_id'   => 144,
				'post_type'   => 'page',
				'post_title'  => 'child-page-1',
				'post_parent' => $parent_page_id,
			)
		);

		$parent_page_id = $factory->post->create(
			array(
				'post_name' => 'parent',
				'post_type' => 'page',
			)
		);

		self::$post_ids[] = $parent_page_id;

		$child_id_1 = $factory->post->create(
			array(
				'post_name'   => 'child1',
				'post_type'   => 'page',
				'post_parent' => $parent_page_id,
			)
		);

		self::$post_ids[] = $child_id_1;

		$child_id_2 = $factory->post->create(
			array(
				'post_name'   => 'child2',
				'post_type'   => 'page',
				'post_parent' => $parent_page_id,
			)
		);

		self::$post_ids[] = $child_id_2;

		$grandchild_id_1 = $factory->post->create(
			array(
				'post_name'   => 'grandchild',
				'post_type'   => 'page',
				'post_parent' => $child_id_1,
			)
		);

		self::$post_ids[] = $grandchild_id_1;

		$grandchild_id_2 = $factory->post->create(
			array(
				'post_name'   => 'grandchild',
				'post_type'   => 'page',
				'post_parent' => $child_id_2,
			)
		);

		self::$post_ids[] = $grandchild_id_2;

		$cat1 = $factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'parent',
			)
		);

		self::$terms['/category/parent/'] = $cat1;

		self::$term_ids[ $cat1 ] = 'category';

		$cat2 = $factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'child-1',
				'parent'   => self::$terms['/category/parent/'],
			)
		);

		self::$terms['/category/parent/child-1/'] = $cat2;

		self::$term_ids[ $cat2 ] = 'category';

		$cat3 = $factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'child-2',
				'parent'   => self::$terms['/category/parent/child-1/'],
			)
		);

		self::$terms['/category/parent/child-1/child-2/'] = $cat3;

		self::$term_ids[ $cat3 ] = 'category';

		$cat4 = $factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'cat-a',
			)
		);

		self::$term_ids[ $cat4 ] = 'category';

		$cat5 = $factory->term->create(
			array(
				'taxonomy' => 'category',
				'name'     => 'cat-b',
			)
		);

		self::$term_ids[ $cat5 ] = 'category';

		$tag1 = $factory->term->create(
			array(
				'name' => 'post-formats',
			)
		);

		self::$term_ids[ $tag1 ] = 'post_tag';
	}

	/**
	 * Clean up shared fixtures.
	 *
	 * @since 4.1.0
	 */
	public static function delete_shared_fixtures() {
		self::$author_id   = null;
		self::$post_ids    = array();
		self::$comment_ids = array();
		self::$term_ids    = array();
		self::$terms       = array();
	}

	/**
	 * Assert that a given URL is the same a the canonical URL generated by WP.
	 *
	 * @since 4.1.0
	 *
	 * @param string $test_url                Raw URL that will be run through redirect_canonical().
	 * @param string $expected                Expected string.
	 * @param int    $ticket                  Optional. Trac ticket number.
	 * @param array  $expected_doing_it_wrong Array of class/function names expected to throw _doing_it_wrong() notices.
	 */
	public function assertCanonical( $test_url, $expected, $ticket = 0, $expected_doing_it_wrong = array() ) {
		$this->expected_doing_it_wrong = array_merge( $this->expected_doing_it_wrong, (array) $expected_doing_it_wrong );

		$ticket_ref = ( $ticket > 0 ) ? 'Ticket #' . $ticket : '';

		if ( is_string( $expected ) ) {
			$expected = array( 'url' => $expected );
		} elseif ( is_array( $expected ) && ! isset( $expected['url'] ) && ! isset( $expected['qv'] ) ) {
			$expected = array( 'qv' => $expected );
		}

		if ( ! isset( $expected['url'] ) && ! isset( $expected['qv'] ) ) {
			$this->fail( 'No valid expected output was provided' );
		}

		$this->go_to( home_url( $test_url ) );

		// Does the redirect match what's expected?
		$can_url        = $this->get_canonical( $test_url );
		$parsed_can_url = parse_url( $can_url );

		// Just test the path and query if present.
		if ( isset( $expected['url'] ) ) {
			$this->assertSame( $expected['url'], $parsed_can_url['path'] . ( ! empty( $parsed_can_url['query'] ) ? '?' . $parsed_can_url['query'] : '' ), $ticket_ref );
		}

		// If the test data doesn't include expected query vars, then we're done here.
		if ( ! isset( $expected['qv'] ) ) {
			return;
		}

		// "make" that the request and check the query is correct.
		$this->go_to( $can_url );

		// Are all query vars accounted for, and correct?
		global $wp;

		$query_vars = array_diff( $wp->query_vars, $wp->extra_query_vars );
		if ( ! empty( $parsed_can_url['query'] ) ) {
			parse_str( $parsed_can_url['query'], $_qv );

			// $_qv should not contain any elements which are set in $query_vars already
			// (i.e. $_GET vars should not be present in the Rewrite).
			$this->assertSame( array(), array_intersect( $query_vars, $_qv ), 'Query vars are duplicated from the Rewrite into $_GET; ' . $ticket_ref );

			$query_vars = array_merge( $query_vars, $_qv );
		}

		$this->assertEquals( $expected['qv'], $query_vars );
	}

	/**
	 * Get the canonical URL given a raw URL.
	 *
	 * @param string $test_url Should be relative to the site "front", ie /category/uncategorized/
	 *                         as opposed to http://example.com/category/uncategorized/
	 * @return $can_url Returns the original $test_url if no canonical can be generated, otherwise returns
	 *                  the fully-qualified URL as generated by redirect_canonical().
	 */
	public function get_canonical( $test_url ) {
		$test_url = home_url( $test_url );

		$can_url = redirect_canonical( $test_url, false );
		if ( ! $can_url ) {
			return $test_url; // No redirect will take place for this request.
		}

		return $can_url;
	}
}
