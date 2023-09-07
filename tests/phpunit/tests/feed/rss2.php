<?php

/**
 * Test the RSS 2.0 feed by generating a feed, parsing it, and checking that the
 * parsed contents match the contents of the posts stored in the database.  Since
 * we're using a real XML parser, this confirms that the feed is valid, well formed,
 * and contains the right stuff.
 *
 * @group feed
 */
class Tests_Feed_RSS2 extends WP_UnitTestCase {
	public static $user_id;
	public static $posts;
	public static $category;
	public static $post_date;

	private $post_count;
	private $excerpt_only;

	/**
	 * Setup a new user and attribute some posts.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		update_option( 'default_comment_status', 'open' );
		// Create a user.
		self::$user_id = $factory->user->create(
			array(
				'role'         => 'author',
				'user_login'   => 'test_author',
				'display_name' => 'Test A. Uthor',
			)
		);

		// Create a taxonomy.
		self::$category = $factory->category->create_and_get(
			array(
				'name' => 'Foo Category',
				'slug' => 'foo',
			)
		);

		// Set a predictable time for testing date archives.
		self::$post_date = strtotime( '2003-05-27 10:07:53' );

		$count = get_option( 'posts_per_rss' ) + 1;

		self::$posts = array();
		// Create a few posts.
		for ( $i = 1; $i <= $count; $i++ ) {
			self::$posts[] = $factory->post->create(
				array(
					'post_author'  => self::$user_id,
					// Separate post dates 5 seconds apart.
					'post_date'    => gmdate( 'Y-m-d H:i:s', self::$post_date + ( 5 * $i ) ),
					'post_content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec velit massa, ultrices eu est suscipit, mattis posuere est. Donec vitae purus lacus. Cras vitae odio odio.',
					'post_excerpt' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
				)
			);
		}

		// Assign a category to those posts.
		foreach ( self::$posts as $post ) {
			wp_set_object_terms( $post, self::$category->slug, 'category' );
		}

		// Assign a tagline option.
		update_option( 'blogdescription', 'Just another WordPress site' );
	}

	/**
	 * Setup.
	 */
	public function set_up() {
		parent::set_up();

		$this->post_count   = (int) get_option( 'posts_per_rss' );
		$this->excerpt_only = get_option( 'rss_use_excerpt' );
		// This seems to break something.
		update_option( 'use_smilies', false );

		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		create_initial_taxonomies();
	}

	/**
	 * Tear down.
	 */
	public static function wpTearDownAfterClass() {
		update_option( 'default_comment_status', 'closed' );
		delete_option( 'blogdescription' );
	}

	/**
	 * This is a bit of a hack used to buffer feed content.
	 */
	private function do_rss2() {
		ob_start();
		// Nasty hack! In the future it would better to leverage do_feed( 'rss2' ).
		global $post;
		try {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@require ABSPATH . 'wp-includes/feed-rss2.php';
			$out = ob_get_clean();
		} catch ( Exception $e ) {
			$out = ob_get_clean();
			throw($e);
		}
		return $out;
	}

	/**
	 * Test the <rss> element to make sure its present and populated
	 * with the expected child elements and attributes.
	 */
	public function test_rss_element() {
		$this->go_to( '/?feed=rss2' );
		$feed = $this->do_rss2();
		$xml  = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );

		$this->assertSame( '2.0', $rss[0]['attributes']['version'] );
		$this->assertSame( 'http://purl.org/rss/1.0/modules/content/', $rss[0]['attributes']['xmlns:content'] );
		$this->assertSame( 'http://wellformedweb.org/CommentAPI/', $rss[0]['attributes']['xmlns:wfw'] );
		$this->assertSame( 'http://purl.org/dc/elements/1.1/', $rss[0]['attributes']['xmlns:dc'] );

		// RSS should have exactly one child element (channel).
		$this->assertCount( 1, $rss[0]['child'] );
	}

	/**
	 * [test_channel_element description]
	 *
	 * @return [type] [description]
	 */
	public function test_channel_element() {
		$this->go_to( '/?feed=rss2' );
		$feed = $this->do_rss2();
		$xml  = xml_to_array( $feed );

		// Get the rss -> channel element.
		$channel = xml_find( $xml, 'rss', 'channel' );

		// The channel should be free of attributes.
		$this->assertArrayNotHasKey( 'attributes', $channel[0] );

		// Verify the channel is present and contains a title child element.
		$title = xml_find( $xml, 'rss', 'channel', 'title' );
		$this->assertSame( get_option( 'blogname' ), $title[0]['content'] );

		$desc = xml_find( $xml, 'rss', 'channel', 'description' );
		$this->assertSame( get_option( 'blogdescription' ), $desc[0]['content'] );

		$link = xml_find( $xml, 'rss', 'channel', 'link' );
		$this->assertSame( get_option( 'siteurl' ), $link[0]['content'] );

		$pubdate = xml_find( $xml, 'rss', 'channel', 'lastBuildDate' );
		$this->assertSame( strtotime( get_lastpostmodified() ), strtotime( $pubdate[0]['content'] ) );
	}

	/**
	 * Test that translated feeds have a valid listed date.
	 *
	 * @ticket 39141
	 */
	public function test_channel_pubdate_element_translated() {
		$original_locale = $GLOBALS['wp_locale'];
		/* @var WP_Locale $locale */
		$locale = clone $GLOBALS['wp_locale'];

		$locale->weekday[2]                           = 'Tuesday_Translated';
		$locale->weekday_abbrev['Tuesday_Translated'] = 'Tue_Translated';

		$GLOBALS['wp_locale'] = $locale;

		$this->go_to( '/?feed=rss2' );
		$feed = $this->do_rss2();

		// Restore original locale.
		$GLOBALS['wp_locale'] = $original_locale;

		$xml = xml_to_array( $feed );

		// Verify the date is untranslated.
		$pubdate = xml_find( $xml, 'rss', 'channel', 'lastBuildDate' );
		$this->assertStringNotContainsString( 'Tue_Translated', $pubdate[0]['content'] );
	}

	public function test_item_elements() {
		$this->go_to( '/?feed=rss2' );
		$feed = $this->do_rss2();
		$xml  = xml_to_array( $feed );

		// Get all the <item> child elements of the <channel> element.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );

		// Verify we are displaying the correct number of posts.
		$this->assertCount( $this->post_count, $items );

		// We really only need to test X number of items unless the content is different.
		$items = array_slice( $items, 1 );

		// Check each of the desired entries against the known post data.
		foreach ( $items as $key => $item ) {

			// Get post for comparison.
			$guid = xml_find( $items[ $key ]['child'], 'guid' );
			preg_match( '/\?p=(\d+)/', $guid[0]['content'], $matches );
			$post = get_post( $matches[1] );

			// Title.
			$title = xml_find( $items[ $key ]['child'], 'title' );
			$this->assertSame( $post->post_title, $title[0]['content'] );

			// Link.
			$link = xml_find( $items[ $key ]['child'], 'link' );
			$this->assertSame( get_permalink( $post ), $link[0]['content'] );

			// Comment link.
			$comments_link = xml_find( $items[ $key ]['child'], 'comments' );
			$this->assertSame( get_permalink( $post ) . '#respond', $comments_link[0]['content'] );

			// Pub date.
			$pubdate = xml_find( $items[ $key ]['child'], 'pubDate' );
			$this->assertSame( strtotime( $post->post_date_gmt ), strtotime( $pubdate[0]['content'] ) );

			// Author.
			$creator = xml_find( $items[ $key ]['child'], 'dc:creator' );
			$user    = new WP_User( $post->post_author );
			$this->assertSame( $user->display_name, $creator[0]['content'] );

			// Categories (perhaps multiple).
			$categories = xml_find( $items[ $key ]['child'], 'category' );
			$cats       = array();
			foreach ( get_the_category( $post->ID ) as $term ) {
				$cats[] = $term->name;
			}

			$tags = get_the_tags( $post->ID );
			if ( $tags ) {
				foreach ( get_the_tags( $post->ID ) as $term ) {
					$cats[] = $term->name;
				}
			}
			$cats = array_filter( $cats );
			// Should be the same number of categories.
			$this->assertSame( count( $cats ), count( $categories ) );

			// ..with the same names.
			foreach ( $cats as $id => $cat ) {
				$this->assertSame( $cat, $categories[ $id ]['content'] );
			}

			// GUID.
			$guid = xml_find( $items[ $key ]['child'], 'guid' );
			$this->assertSame( 'false', $guid[0]['attributes']['isPermaLink'] );
			$this->assertSame( $post->guid, $guid[0]['content'] );

			// Description / Excerpt.
			if ( ! empty( $post->post_excerpt ) ) {
				$description = xml_find( $items[ $key ]['child'], 'description' );
				$this->assertSame( trim( $post->post_excerpt ), trim( $description[0]['content'] ) );
			}

			// Post content.
			if ( ! $this->excerpt_only ) {
				$content = xml_find( $items[ $key ]['child'], 'content:encoded' );
				$this->assertSame( trim( apply_filters( 'the_content', $post->post_content ) ), trim( $content[0]['content'] ) );
			}

			// Comment RSS.
			$comment_rss = xml_find( $items[ $key ]['child'], 'wfw:commentRss' );
			$this->assertSame( html_entity_decode( get_post_comments_feed_link( $post->ID ) ), $comment_rss[0]['content'] );
		}
	}

	/**
	 * @ticket 9134
	 */
	public function test_items_comments_closed() {
		add_filter( 'comments_open', '__return_false' );

		$this->go_to( '/?feed=rss2' );
		$feed = $this->do_rss2();
		$xml  = xml_to_array( $feed );

		// Get all the rss -> channel -> item elements.
		$items = xml_find( $xml, 'rss', 'channel', 'item' );

		// Check each of the items against the known post data.
		foreach ( $items as $key => $item ) {
			// Get post for comparison.
			$guid = xml_find( $items[ $key ]['child'], 'guid' );
			preg_match( '/\?p=(\d+)/', $guid[0]['content'], $matches );
			$post = get_post( $matches[1] );

			// Comment link.
			$comments_link = xml_find( $items[ $key ]['child'], 'comments' );
			$this->assertEmpty( $comments_link );

			// Comment RSS.
			$comment_rss = xml_find( $items[ $key ]['child'], 'wfw:commentRss' );
			$this->assertEmpty( $comment_rss );
		}

		remove_filter( 'comments_open', '__return_false' );
	}

	/*
	 * Check to make sure we are rendering feed templates for the home feed.
	 * e.g. https://example.com/feed/
	 *
	 * @ticket 30210
	 */
	public function test_valid_home_feed_endpoint() {
		// An example of a valid home feed endpoint.
		$this->go_to( 'feed/' );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed' );

		// Queries performed on valid feed endpoints should contain posts.
		$this->assertTrue( have_posts() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/*
	 * Check to make sure we are rendering feed templates for the taxonomy feeds.
	 * e.g. https://example.com/category/foo/feed/
	 *
	 * @ticket 30210
	 */
	public function test_valid_taxonomy_feed_endpoint() {
		// An example of an valid taxonomy feed endpoint.
		$this->go_to( 'category/foo/feed/' );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed', 'is_archive', 'is_category' );

		// Queries performed on valid feed endpoints should contain posts.
		$this->assertTrue( have_posts() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/*
	 * Check to make sure we are rendering feed templates for the main comment feed.
	 * e.g. https://example.com/comments/feed/
	 *
	 * @ticket 30210
	 */
	public function test_valid_main_comment_feed_endpoint() {
		// Generate a bunch of comments.
		foreach ( self::$posts as $post ) {
			self::factory()->comment->create_post_comments( $post, 3 );
		}

		// An example of an valid main comment feed endpoint.
		$this->go_to( 'comments/feed/' );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed', 'is_comment_feed' );

		// Queries performed on valid feed endpoints should contain comments.
		$this->assertTrue( have_comments() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/*
	 * Check to make sure we are rendering feed templates for the date archive feeds.
	 * e.g. https://example.com/2003/05/27/feed/
	 *
	 * @ticket 30210
	 */
	public function test_valid_archive_feed_endpoint() {
		// An example of an valid date archive feed endpoint.
		$this->go_to( '2003/05/27/feed/' );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed', 'is_archive', 'is_day', 'is_date' );

		// Queries performed on valid feed endpoints should contain posts.
		$this->assertTrue( have_posts() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/*
	 * Check to make sure we are rendering feed templates for single post comment feeds.
	 * e.g. https://example.com/2003/05/27/post-name/feed/
	 *
	 * @ticket 30210
	 */
	public function test_valid_single_post_comment_feed_endpoint() {
		// An example of an valid date archive feed endpoint.
		$this->go_to( get_post_comments_feed_link( self::$posts[0] ) );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed', 'is_comment_feed', 'is_single', 'is_singular' );

		// Queries performed on valid feed endpoints should contain posts.
		$this->assertTrue( have_posts() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/*
	 * Check to make sure we are rendering feed templates for the search archive feeds.
	 * e.g. https://example.com/?s=Lorem&feed=rss
	 *
	 * @ticket 30210
	 */
	public function test_valid_search_feed_endpoint() {
		// An example of an valid search feed endpoint.
		$this->go_to( '?s=Lorem&feed=rss' );

		// Verify the query object is a feed.
		$this->assertQueryTrue( 'is_feed', 'is_search' );

		// Queries performed on valid feed endpoints should contain posts.
		$this->assertTrue( have_posts() );

		// Check to see if we have the expected XML output from the feed template.
		$feed = $this->do_rss2();

		$xml = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss = xml_find( $xml, 'rss' );

		// There should only be one <rss> child element.
		$this->assertCount( 1, $rss );
	}

	/**
	 * Test <rss> element has correct last build date.
	 *
	 * @ticket 4575
	 *
	 * @dataProvider data_test_get_feed_build_date
	 */
	public function test_get_feed_build_date( $url, $element ) {
		$this->go_to( $url );
		$feed = $this->do_rss2();
		$xml  = xml_to_array( $feed );

		// Get the <rss> child element of <xml>.
		$rss             = xml_find( $xml, $element );
		$last_build_date = $rss[0]['child'][0]['child'][4]['content'];
		$this->assertSame( strtotime( get_feed_build_date( 'r' ) ), strtotime( $last_build_date ) );
	}


	public function data_test_get_feed_build_date() {
		return array(
			array( '/?feed=rss2', 'rss' ),
			array( '/?feed=commentsrss2', 'rss' ),
		);
	}

	/**
	 * Test that the Last-Modified is a post's date when a more recent comment exists,
	 * but the "withcomments=1" query var is not passed.
	 *
	 * @ticket 47968
	 *
	 * @covers WP::send_headers
	 */
	public function test_feed_last_modified_should_be_a_post_date_when_withcomments_is_not_passed() {
		$last_week = gmdate( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
		$yesterday = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) );

		// Create a post dated last week.
		$post_id = self::factory()->post->create( array( 'post_date' => $last_week ) );

		// Create a comment dated yesterday.
		self::factory()->comment->create(
			array(
				'comment_post_ID' => $post_id,
				'comment_date'    => $yesterday,
			)
		);

		// The Last-Modified header should have the post's date when "withcomments" is not passed.
		add_filter(
			'wp_headers',
			function ( $headers ) use ( $last_week ) {
				$this->assertSame(
					strtotime( $headers['Last-Modified'] ),
					strtotime( $last_week ),
					'Last-Modified was not the date of the post'
				);
				return $headers;
			}
		);

		$this->go_to( '/?feed=rss2' );
	}

	/**
	 * Test that the Last-Modified is a comment's date when a more recent comment exists,
	 * and the "withcomments=1" query var is passed.
	 *
	 * @ticket 47968
	 *
	 * @covers WP::send_headers
	 */
	public function test_feed_last_modified_should_be_the_date_of_a_comment_that_is_the_latest_update_when_withcomments_is_passed() {
		$last_week = gmdate( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
		$yesterday = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) );

		// Create a post dated last week.
		$post_id = self::factory()->post->create( array( 'post_date' => $last_week ) );

		// Create a comment dated yesterday.
		self::factory()->comment->create(
			array(
				'comment_post_ID' => $post_id,
				'comment_date'    => $yesterday,
			)
		);

		// The Last-Modified header should have the comment's date when "withcomments=1" is passed.
		add_filter(
			'wp_headers',
			function ( $headers ) use ( $yesterday ) {
				$this->assertSame(
					strtotime( $headers['Last-Modified'] ),
					strtotime( $yesterday ),
					'Last-Modified was not the date of the comment'
				);
				return $headers;
			}
		);

		$this->go_to( '/?feed=rss2&withcomments=1' );
	}

	/**
	 * Test that the Last-Modified is the latest post's date when an earlier post and comment exist,
	 * and the "withcomments=1" query var is passed.
	 *
	 * @ticket 47968
	 *
	 * @covers WP::send_headers
	 */
	public function test_feed_last_modified_should_be_the_date_of_a_post_that_is_the_latest_update_when_withcomments_is_passed() {
		$last_week = gmdate( 'Y-m-d H:i:s', strtotime( '-1 week' ) );
		$yesterday = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
		$today     = gmdate( 'Y-m-d H:i:s' );

		// Create a post dated last week.
		$post_id = self::factory()->post->create( array( 'post_date' => $last_week ) );

		// Create a comment dated yesterday.
		self::factory()->comment->create(
			array(
				'comment_post_ID' => $post_id,
				'comment_date'    => $yesterday,
			)
		);

		// Create a post dated today.
		self::factory()->post->create( array( 'post_date' => $today ) );

		// The Last-Modified header should have the date from today's post when it is the latest update.
		add_filter(
			'wp_headers',
			function ( $headers ) use ( $today ) {
				$this->assertSame(
					strtotime( $headers['Last-Modified'] ),
					strtotime( $today ),
					'Last-Modified was not the date of the most recent post'
				);
				return $headers;
			}
		);

		$this->go_to( '/?feed=rss2&withcomments=1' );
	}
}
