<?php
/**
 * Tests Canonical redirections.
 *
 * In the process of doing so, it also tests WP, WP_Rewrite and WP_Query, A fail here may show a bug in any one of these areas.
 *
 * @group canonical
 * @group rewrite
 * @group query
 */
class Tests_Canonical extends WP_Canonical_UnitTestCase {

	public function set_up() {
		parent::set_up();
		wp_set_current_user( self::$author_id );
	}

	/**
	 * @dataProvider data_canonical
	 */
	public function test_canonical( $test_url, $expected, $ticket = 0, $expected_doing_it_wrong = array() ) {

		if ( false !== strpos( $test_url, '%d' ) ) {
			if ( false !== strpos( $test_url, '/?author=%d' ) ) {
				$test_url = sprintf( $test_url, self::$author_id );
			}
			if ( false !== strpos( $test_url, '?cat=%d' ) ) {
				$test_url = sprintf( $test_url, self::$terms[ $expected['url'] ] );
			}
		}

		$this->assertCanonical( $test_url, $expected, $ticket, $expected_doing_it_wrong );
	}

	public function data_canonical() {
		/*
		 * Data format:
		 * [0]: Test URL.
		 * [1]: Expected results: Any of the following can be used.
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [2]: (optional) The ticket the test refers to, Can be skipped if unknown.
		 * [3]: (optional) Array of class/function names expected to throw `_doing_it_wrong()` notices.
		 */

		// Please Note: A few test cases are commented out below, look at the test case following it.
		// In most cases it's simply showing 2 options for the "proper" redirect.
		return array(
			// Categories.
			array( '?cat=%d', array( 'url' => '/category/parent/' ), 15256 ),
			array( '?cat=%d', array( 'url' => '/category/parent/child-1/' ), 15256 ),
			array( '?cat=%d', array( 'url' => '/category/parent/child-1/child-2/' ) ), // No children.
			array(
				'/category/uncategorized/',
				array(
					'url' => '/category/uncategorized/',
					'qv'  => array( 'category_name' => 'uncategorized' ),
				),
			),
			array(
				'/category/uncategorized/page/2/',
				array(
					'url' => '/category/uncategorized/page/2/',
					'qv'  => array(
						'category_name' => 'uncategorized',
						'paged'         => 2,
					),
				),
			),
			array(
				'/category/uncategorized/?paged=2',
				array(
					'url' => '/category/uncategorized/page/2/',
					'qv'  => array(
						'category_name' => 'uncategorized',
						'paged'         => 2,
					),
				),
			),
			array(
				'/category/uncategorized/?paged=2&category_name=uncategorized',
				array(
					'url' => '/category/uncategorized/page/2/',
					'qv'  => array(
						'category_name' => 'uncategorized',
						'paged'         => 2,
					),
				),
				17174,
			),

			// Categories & intersections with other vars.
			array(
				'/category/uncategorized/?tag=post-formats',
				array(
					'url' => '/category/uncategorized/?tag=post-formats',
					'qv'  => array(
						'category_name' => 'uncategorized',
						'tag'           => 'post-formats',
					),
				),
			),
			array(
				'/?category_name=cat-a,cat-b',
				array(
					'url' => '/?category_name=cat-a,cat-b',
					'qv'  => array( 'category_name' => 'cat-a,cat-b' ),
				),
			),

			// Taxonomies with extra query vars.
			array( '/category/cat-a/page/1/?test=one%20two', '/category/cat-a/?test=one%20two', 18086 ), // Extra query vars should stay encoded.

			// Categories with dates.
			array(
				'/2008/04/?cat=1',
				array(
					'url' => '/2008/04/?cat=1',
					'qv'  => array(
						'cat'      => '1',
						'year'     => '2008',
						'monthnum' => '04',
					),
				),
				17661,
			),
			/*
			array(
				'/2008/?category_name=cat-a',
					array(
						'url' => '/2008/?category_name=cat-a',
						'qv'  => array(
							'category_name' => 'cat-a',
							'year'          => '2008'
						)
					)
			),
			*/

			// Pages.
			array( '/child-page-1/', '/parent-page/child-page-1/' ),
			array( '/?page_id=144', '/parent-page/child-page-1/' ),
			array( '/abo', '/about/' ),
			array( '/parent/child1/grandchild/', '/parent/child1/grandchild/' ),
			array( '/parent/child2/grandchild/', '/parent/child2/grandchild/' ),

			// Posts.
			array( '?p=587', '/2008/06/02/post-format-test-audio/' ),
			array( '/?name=images-test', '/2008/09/03/images-test/' ),
			// Incomplete slug should resolve and remove the ?name= parameter.
			array( '/?name=images-te', '/2008/09/03/images-test/', 20374 ),
			// Page slug should resolve to post slug and remove the ?pagename= parameter.
			array( '/?pagename=images-test', '/2008/09/03/images-test/', 20374 ),

			array( '/2008/06/02/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2008/06/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2008/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2010/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ), // A year the post is not in.
			array( '/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),

			// Pagination.
			array(
				'/2008/09/03/multipage-post-test/3/',
				array(
					'url' => '/2008/09/03/multipage-post-test/3/',
					'qv'  => array(
						'name'     => 'multipage-post-test',
						'year'     => '2008',
						'monthnum' => '09',
						'day'      => '03',
						'page'     => '3',
					),
				),
			),
			array( '/2008/09/03/multipage-post-test/?page=3', '/2008/09/03/multipage-post-test/3/' ),
			array( '/2008/09/03/multipage-post-te?page=3', '/2008/09/03/multipage-post-test/3/' ),

			array( '/2008/09/03/non-paged-post-test/3/', '/2008/09/03/non-paged-post-test/' ),
			array( '/2008/09/03/non-paged-post-test/?page=3', '/2008/09/03/non-paged-post-test/' ),

			// Comments.
			array( '/2008/03/03/comment-test/?cpage=2', '/2008/03/03/comment-test/comment-page-2/' ),

			// Attachments.
			array( '/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),
			array( '/2008/06/10/post-format-test-gallery/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),

			// Dates.
			array( '/?m=2008', '/2008/' ),
			array( '/?m=200809', '/2008/09/' ),
			array( '/?m=20080905', '/2008/09/05/' ),

			array( '/2008/?day=05', '/2008/?day=05' ), // No redirect.
			array( '/2008/09/?day=05', '/2008/09/05/' ),
			array( '/2008/?monthnum=9', '/2008/09/' ),

			array( '/?year=2008', '/2008/' ),

			array( '/2012/13/', '/2012/' ),
			array( '/2012/11/51/', '/2012/11/', 0, array( 'WP_Date_Query' ) ),

			// Authors.
			array( '/?author=%d', '/author/canonical-author/' ),
			// array( '/?author=%d&year=2008', '/2008/?author=3'),
			// array( '/author/canonical-author/?year=2008', '/2008/?author=3'), // Either or, see previous testcase.

			// Feeds.
			array( '/?feed=atom', '/feed/atom/' ),
			array( '/?feed=rss2', '/feed/' ),
			array( '/?feed=comments-rss2', '/comments/feed/' ),
			array( '/?feed=comments-atom', '/comments/feed/atom/' ),

			// Feeds (per-post).
			array( '/2008/03/03/comment-test/?feed=comments-atom', '/2008/03/03/comment-test/feed/atom/' ),
			array( '/?p=149&feed=comments-atom', '/2008/03/03/comment-test/feed/atom/' ),

			// Index.
			array( '/?paged=1', '/' ),
			array( '/page/1/', '/' ),
			array( '/page1/', '/' ),
			array( '/?paged=2', '/page/2/' ),
			array( '/page2/', '/page/2/' ),

			// Misc.
			array( '/2008%20', '/2008' ),
			array( '//2008////', '/2008/' ),

			// @todo Endpoints (feeds, trackbacks, etc). More fuzzed mixed query variables, comment paging, Home page (static).
		);
	}

	/**
	 * @ticket 16557
	 */
	public function test_do_redirect_guess_404_permalink() {
		// Test disable do_redirect_guess_404_permalink().
		add_filter( 'do_redirect_guess_404_permalink', '__return_false' );
		$this->go_to( '/child-page-1' );
		$this->assertFalse( redirect_guess_404_permalink() );
	}

	/**
	 * @ticket 16557
	 */
	public function test_pre_redirect_guess_404_permalink() {
		// Test short-circuit filter.
		add_filter(
			'pre_redirect_guess_404_permalink',
			static function() {
				return 'wp';
			}
		);
		$this->go_to( '/child-page-1' );
		$this->assertSame( 'wp', redirect_guess_404_permalink() );
	}

	/**
	 * @ticket 16557
	 */
	public function test_strict_redirect_guess_404_permalink() {
		$post = self::factory()->post->create(
			array(
				'post_title' => 'strict-redirect-guess-404-permalink',
			)
		);

		$this->go_to( 'strict-redirect' );

		// Test default 'non-strict' redirect guess.
		$this->assertSame( get_permalink( $post ), redirect_guess_404_permalink() );

		// Test 'strict' redirect guess.
		add_filter( 'strict_redirect_guess_404_permalink', '__return_true' );
		$this->assertFalse( redirect_guess_404_permalink() );
	}

	/**
	 * Ensure public posts with custom public statuses are guessed.
	 *
	 * @ticket 47911
	 * @dataProvider data_redirect_guess_404_permalink_with_custom_statuses
	 *
	 * @covers ::redirect_guess_404_permalink
	 */
	public function test_redirect_guess_404_permalink_with_custom_statuses( $status_args, $redirects ) {
		register_post_status( 'custom', $status_args );

		$post = self::factory()->post->create(
			array(
				'post_title'  => 'custom-status-public-guess-404-permalink',
				'post_status' => 'custom',
			)
		);

		$this->go_to( 'custom-status-public-guess-404-permalink' );

		$expected = $redirects ? get_permalink( $post ) : false;

		$this->assertSame( $expected, redirect_guess_404_permalink() );
	}

	/**
	 * Data provider for test_redirect_guess_404_permalink_with_custom_statuses().
	 *
	 * return array[] {
	 *    array Arguments used to register custom status
	 *    bool  Whether the 404 link is expected to redirect
	 * }
	 */
	public function data_redirect_guess_404_permalink_with_custom_statuses() {
		return array(
			'public status'                      => array(
				'status_args' => array( 'public' => true ),
				'redirects'   => true,
			),
			'private status'                     => array(
				'status_args' => array( 'public' => false ),
				'redirects'   => false,
			),
			'internal status'                    => array(
				'status_args' => array( 'internal' => true ),
				'redirects'   => false,
			),
			'protected status'                   => array(
				'status_args' => array( 'protected' => true ),
				'redirects'   => false,
			),
			'protected status flagged as public' => array(
				'status_args' => array(
					'protected' => true,
					'public'    => true,
				),
				'redirects'   => false,
			),
		);
	}

	/**
	 * Ensure multiple post types do not throw a notice.
	 *
	 * @ticket 43056
	 */
	public function test_redirect_guess_404_permalink_post_types() {
		/*
		 * Sample-page is intentionally missspelt as sample-pag to ensure
		 * the 404 post permalink guessing runs.
		 *
		 * Please do not correct the apparent typo.
		 */

		// String format post type.
		$this->assertCanonical( '/?name=sample-pag&post_type=page', '/sample-page/' );
		// Array formatted post type or types.
		$this->assertCanonical( '/?name=sample-pag&post_type[]=page', '/sample-page/' );
		$this->assertCanonical( '/?name=sample-pag&post_type[]=page&post_type[]=post', '/sample-page/' );
	}

	/**
	 * @ticket 43745
	 */
	public function test_utf8_query_keys_canonical() {
		$p = self::factory()->post->create(
			array(
				'post_type' => 'page',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $p );

		$this->go_to( get_permalink( $p ) );

		$url = redirect_canonical( add_query_arg( '%D0%BA%D0%BE%D0%BA%D0%BE%D0%BA%D0%BE', 1, site_url( '/' ) ), false );
		$this->assertNull( $url );

		delete_option( 'page_on_front' );
	}

	/**
	 * Ensure NOT EXISTS queries do not trigger not-countable or undefined array key errors.
	 *
	 * @ticket 55955
	 */
	public function test_feed_canonical_with_not_exists_query() {
		// Set a NOT EXISTS tax_query on the global query.
		$global_query        = $GLOBALS['wp_query'];
		$GLOBALS['wp_query'] = new WP_Query(
			array(
				'post_type' => 'post',
				'tax_query' => array(
					array(
						'taxonomy' => 'post_format',
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		$url = redirect_canonical( get_term_feed_link( self::$terms['/category/parent/'] ), false );
		// Restore original global.
		$GLOBALS['wp_query'] = $global_query;

		$this->assertNull( $url );
	}
}
