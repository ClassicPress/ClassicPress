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

	public function setUp() {
		parent::setUp();
		wp_set_current_user( self::$author_id );
	}

	/**
	 * @dataProvider data_canonical
	 */
	function test_canonical( $test_url, $expected, $expected_doing_it_wrong = array() ) {

		if ( false !== strpos( $test_url, '%d' ) ) {
			if ( false !== strpos( $test_url, '/?author=%d' ) )
				$test_url = sprintf( $test_url, self::$author_id );
			if ( false !== strpos( $test_url, '?cat=%d' ) )
				$test_url = sprintf( $test_url, self::$terms[ $expected['url'] ] );
		}

		$this->assertCanonical( $test_url, $expected, $expected_doing_it_wrong );
	}

	function data_canonical() {
		/* Data format:
		 * [0]: Test URL.
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 * [2]: (optional) Array of class/function names expected to throw `_doing_it_wrong()` notices.
		 */

		// Please Note: A few test cases are commented out below, Look at the test case following it, in most cases it's simply showing 2 options for the "proper" redirect.
		return array(
			// Categories

			array( '?cat=%d', array( 'url' => '/category/parent/' ) ),
			array( '?cat=%d', array( 'url' => '/category/parent/child-1/' ) ),
			array( '?cat=%d', array( 'url' => '/category/parent/child-1/child-2/' ) ), // no children
			array( '/category/uncategorized/', array( 'url' => '/category/uncategorized/', 'qv' => array( 'category_name' => 'uncategorized' ) ) ),
			array( '/category/uncategorized/page/2/', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ) ),
			array( '/category/uncategorized/?paged=2', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ) ),
			array( '/category/uncategorized/?paged=2&category_name=uncategorized', array( 'url' => '/category/uncategorized/page/2/', 'qv' => array( 'category_name' => 'uncategorized', 'paged' => 2) ) ),

			// Categories & Intersections with other vars
			array( '/category/uncategorized/?tag=post-formats', array( 'url' => '/category/uncategorized/?tag=post-formats', 'qv' => array('category_name' => 'uncategorized', 'tag' => 'post-formats') ) ),
			array( '/?category_name=cat-a,cat-b', array( 'url' => '/?category_name=cat-a,cat-b', 'qv' => array( 'category_name' => 'cat-a,cat-b' ) ) ),

			// Taxonomies with extra Query Vars
			array( '/category/cat-a/page/1/?test=one%20two', '/category/cat-a/?test=one%20two' ), // Extra query vars should stay encoded

			// Categories with Dates
			array( '/2008/04/?cat=1', array( 'url' => '/2008/04/?cat=1', 'qv' => array('cat' => '1', 'year' => '2008', 'monthnum' => '04' ) ) ),
//			array( '/2008/?category_name=cat-a', array( 'url' => '/2008/?category_name=cat-a', 'qv' => array('category_name' => 'cat-a', 'year' => '2008' ) ) ),

			// Pages
			array( '/child-page-1/', '/parent-page/child-page-1/' ),
			array( '/?page_id=144', '/parent-page/child-page-1/' ),
			array( '/abo', '/about/' ),
			array( '/parent/child1/grandchild/', '/parent/child1/grandchild/' ),
			array( '/parent/child2/grandchild/', '/parent/child2/grandchild/' ),

			// Posts
			array( '?p=587', '/2008/06/02/post-format-test-audio/' ),
			array( '/?name=images-test', '/2008/09/03/images-test/' ),
			// Incomplete slug should resolve and remove the ?name= parameter
			array( '/?name=images-te', '/2008/09/03/images-test/' ),
			// Page slug should resolve to post slug and remove the ?pagename= parameter
			array( '/?pagename=images-test', '/2008/09/03/images-test/' ),

			array( '/2008/06/02/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2008/06/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2008/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ),
			array( '/2010/post-format-test-au/', '/2008/06/02/post-format-test-audio/' ), // A Year the post is not in
			array( '/post-format-test-au/', '/2008/06/02/post-format-test-audio/'),

			array( '/2008/09/03/images-test/3/', array( 'url' => '/2008/09/03/images-test/3/', 'qv' => array( 'name' => 'images-test', 'year' => '2008', 'monthnum' => '09', 'day' => '03', 'page' => '3' ) ) ),
			array( '/2008/09/03/images-test/?page=3', '/2008/09/03/images-test/3/' ),
			array( '/2008/09/03/images-te?page=3', '/2008/09/03/images-test/3/' ),

			// Comments
			array( '/2008/03/03/comment-test/?cpage=2', '/2008/03/03/comment-test/comment-page-2/' ),

			// Attachments
			array( '/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),
			array( '/2008/06/10/post-format-test-gallery/?attachment_id=611', '/2008/06/10/post-format-test-gallery/canola2/' ),

			// Dates
			array( '/?m=2008', '/2008/' ),
			array( '/?m=200809', '/2008/09/' ),
			array( '/?m=20080905', '/2008/09/05/' ),

			array( '/2008/?day=05', '/2008/?day=05' ), // no redirect
			array( '/2008/09/?day=05', '/2008/09/05/' ),
			array( '/2008/?monthnum=9', '/2008/09/' ),

			array( '/?year=2008', '/2008/' ),

			array( '/2012/13/', '/2012/' ),
			array( '/2012/11/51/', '/2012/11/', array( 'WP_Date_Query' ) ),

			// Authors
			array( '/?author=%d', '/author/canonical-author/' ),
//			array( '/?author=%d&year=2008', '/2008/?author=3' ),
//			array( '/author/canonical-author/?year=2008', '/2008/?author=3' ), //Either or, see previous testcase.

			// Feeds
			array( '/?feed=atom', '/feed/atom/' ),
			array( '/?feed=rss2', '/feed/' ),
			array( '/?feed=comments-rss2', '/comments/feed/'),
			array( '/?feed=comments-atom', '/comments/feed/atom/'),

			// Feeds (per-post)
			array( '/2008/03/03/comment-test/?feed=comments-atom', '/2008/03/03/comment-test/feed/atom/' ),
			array( '/?p=149&feed=comments-atom', '/2008/03/03/comment-test/feed/atom/' ),

			// Index
			array( '/?paged=1', '/' ),
			array( '/page/1/', '/' ),
			array( '/page1/', '/' ),
			array( '/?paged=2', '/page/2/' ),
			array( '/page2/', '/page/2/' ),

			// Misc
			array( '/2008%20', '/2008' ),
			array( '//2008////', '/2008/' ),

			// Todo: Endpoints (feeds, trackbacks, etc), More fuzzed mixed query variables, comment paging, Home page (Static)
		);
	}
<<<<<<< HEAD
=======

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
			function() {
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
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
}
