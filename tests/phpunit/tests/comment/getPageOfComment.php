<?php

/**
 * @group comment
 *
 * @covers ::get_page_of_comment
 */
class Tests_Comment_GetPageOfComment extends WP_UnitTestCase {

	public function test_last_comment() {
		$p = self::factory()->post->create();

		// Page 4.
		$comment_last = self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-24 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-23 00:00:00' ) );

		// Page 3.
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-22 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-21 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-20 00:00:00' ) );

		// Page 2.
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-19 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-18 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-17 00:00:00' ) );

		// Page 1.
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-16 00:00:00' ) );
		self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-15 00:00:00' ) );
		$comment_first = self::factory()->comment->create_post_comments( $p, 1, array( 'comment_date' => '2013-09-14 00:00:00' ) );

		$this->assertSame( 4, get_page_of_comment( $comment_last[0], array( 'per_page' => 3 ) ) );
		$this->assertSame( 2, get_page_of_comment( $comment_last[0], array( 'per_page' => 10 ) ) );

		$this->assertSame( 1, get_page_of_comment( $comment_first[0], array( 'per_page' => 3 ) ) );
		$this->assertSame( 1, get_page_of_comment( $comment_first[0], array( 'per_page' => 10 ) ) );
	}

	public function test_type_pings() {
		$p   = self::factory()->post->create();
		$now = time();

		$trackbacks = array();
		for ( $i = 0; $i <= 3; $i++ ) {
			$trackbacks[ $i ] = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $p,
					'comment_type'     => 'trackback',
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
				)
			);
			$now             -= 10 * $i;
		}

		$pingbacks = array();
		for ( $i = 0; $i <= 6; $i++ ) {
			$pingbacks[ $i ] = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $p,
					'comment_type'     => 'pingback',
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
				)
			);
			$now            -= 10 * $i;
		}

		$this->assertSame(
			2,
			get_page_of_comment(
				$trackbacks[0],
				array(
					'per_page' => 2,
					'type'     => 'trackback',
				)
			)
		);
		$this->assertSame(
			3,
			get_page_of_comment(
				$pingbacks[0],
				array(
					'per_page' => 2,
					'type'     => 'pingback',
				)
			)
		);
		$this->assertSame(
			5,
			get_page_of_comment(
				$trackbacks[0],
				array(
					'per_page' => 2,
					'type'     => 'pings',
				)
			)
		);
	}

	/**
	 * @ticket 11334
	 */
	public function test_subsequent_calls_should_hit_cache() {
		global $wpdb;

		$p = self::factory()->post->create();
		$c = self::factory()->comment->create( array( 'comment_post_ID' => $p ) );

		// Prime cache.
		$page_1 = get_page_of_comment( $c, array( 'per_page' => 3 ) );

		$num_queries = $wpdb->num_queries;
		$page_2      = get_page_of_comment( $c, array( 'per_page' => 3 ) );

		$this->assertSame( $page_1, $page_2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket 11334
	 */
	public function test_cache_hits_should_be_sensitive_to_comment_type() {
		global $wpdb;

		$p       = self::factory()->post->create();
		$comment = self::factory()->comment->create(
			array(
				'comment_post_ID' => $p,
				'comment_type'    => 'comment',
			)
		);

		$now        = time();
		$trackbacks = array();
		for ( $i = 0; $i <= 5; $i++ ) {
			$trackbacks[ $i ] = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $p,
					'comment_type'     => 'trackback',
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - ( 10 * $i ) ),
				)
			);
		}

		// Prime cache for trackbacks.
		$page_trackbacks = get_page_of_comment(
			$trackbacks[1],
			array(
				'per_page' => 3,
				'type'     => 'trackback',
			)
		);
		$this->assertSame( 2, $page_trackbacks );

		$num_queries   = $wpdb->num_queries;
		$page_comments = get_page_of_comment(
			$comment,
			array(
				'per_page' => 3,
				'type'     => 'comment',
			)
		);
		$this->assertSame( 1, $page_comments );

		$this->assertNotEquals( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket 11334
	 */
	public function test_cache_should_be_invalidated_when_comment_is_approved() {
		$p = self::factory()->post->create();
		$c = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_approved' => 0,
			)
		);

		// Prime cache.
		$page_1 = get_page_of_comment( $c, array( 'per_page' => 3 ) );

		// Approve comment.
		wp_set_comment_status( $c, 'approve' );

		$this->assertFalse( wp_cache_get( $c, 'comment_pages' ) );
	}

	/**
	 * @ticket 11334
	 */
	public function test_cache_should_be_invalidated_when_comment_is_deleted() {
		$p = self::factory()->post->create();
		$c = self::factory()->comment->create( array( 'comment_post_ID' => $p ) );

		// Prime cache.
		$page_1 = get_page_of_comment( $c, array( 'per_page' => 3 ) );

		// Trash comment.
		wp_trash_comment( $c );

		$this->assertFalse( wp_cache_get( $c, 'comment_pages' ) );
	}

	/**
	 * @ticket 11334
	 */
	public function test_cache_should_be_invalidated_when_comment_is_spammed() {
		$p = self::factory()->post->create();
		$c = self::factory()->comment->create( array( 'comment_post_ID' => $p ) );

		// Prime cache.
		$page_1 = get_page_of_comment( $c, array( 'per_page' => 3 ) );

		// Spam comment.
		wp_spam_comment( $c );

		$this->assertFalse( wp_cache_get( $c, 'comment_pages' ) );
	}

	/**
	 * @ticket 11334
	 */
	public function test_cache_should_be_invalidated_when_older_comment_is_published() {
		$now = time();

		$p  = self::factory()->post->create();
		$c1 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
			)
		);
		$c2 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 20 ),
			)
		);
		$c3 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_approved' => 0,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 30 ),
			)
		);

		$this->assertSame( 1, get_page_of_comment( $c1, array( 'per_page' => 2 ) ) );

		wp_set_comment_status( $c3, '1' );

		$this->assertSame( 2, get_page_of_comment( $c1, array( 'per_page' => 2 ) ) );
	}

	/**
	 * @ticket 34057
	 */
	public function test_query_should_be_limited_to_comments_on_the_proper_post() {
		$posts = self::factory()->post->create_many( 2 );

		$now        = time();
		$comments_0 = array();
		$comments_1 = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$comments_0[] = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $posts[0],
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - ( $i * 60 ) ),
				)
			);
			$comments_1[] = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $posts[1],
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - ( $i * 60 ) ),
				)
			);
		}

		$found_0 = get_page_of_comment( $comments_0[0], array( 'per_page' => 2 ) );
		$this->assertSame( 3, $found_0 );

		$found_1 = get_page_of_comment( $comments_1[1], array( 'per_page' => 2 ) );
		$this->assertSame( 2, $found_1 );
	}

	/**
	 * @ticket 13939
	 */
	public function test_only_top_level_comments_should_be_included_in_older_count() {
		$post = self::factory()->post->create();

		$now              = time();
		$comment_parents  = array();
		$comment_children = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$parent                = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $post,
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - ( $i * 60 ) ),
				)
			);
			$comment_parents[ $i ] = $parent;

			$child                  = self::factory()->comment->create(
				array(
					'comment_post_ID'  => $post,
					'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - ( $i * 59 ) ),
					'comment_parent'   => $parent,
				)
			);
			$comment_children[ $i ] = $child;
		}

		$page_1_indicies = array( 2, 3, 4 );
		$page_2_indicies = array( 0, 1 );

		$args = array(
			'per_page'  => 3,
			'max_depth' => 2,
		);

		foreach ( $page_1_indicies as $p1i ) {
			$this->assertSame( 1, (int) get_page_of_comment( $comment_parents[ $p1i ], $args ) );
			$this->assertSame( 1, (int) get_page_of_comment( $comment_children[ $p1i ], $args ) );
		}

		foreach ( $page_2_indicies as $p2i ) {
			$this->assertSame( 2, (int) get_page_of_comment( $comment_parents[ $p2i ], $args ) );
			$this->assertSame( 2, (int) get_page_of_comment( $comment_children[ $p2i ], $args ) );
		}
	}

	/**
	 * @ticket 13939
	 */
	public function test_comments_per_page_option_should_be_fallback_when_query_var_is_not_available() {
		$now = time();

		$p  = self::factory()->post->create();
		$c1 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
			)
		);
		$c2 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 20 ),
			)
		);
		$c3 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 30 ),
			)
		);

		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 2 );

		$this->assertSame( 2, get_page_of_comment( $c1 ) );
	}

	/**
	 * @ticket 31101
	 * @ticket 39280
	 */
	public function test_should_ignore_comment_order() {
		$now = time();

		$p  = self::factory()->post->create();
		$c1 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
			)
		);
		$c2 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 20 ),
			)
		);
		$c3 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 30 ),
			)
		);
		$c4 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 40 ),
			)
		);

		update_option( 'comment_order', 'desc' );
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 1 );

		$this->assertSame( 2, get_page_of_comment( $c3 ) );
	}

	/**
	 * @ticket 31101
	 * @ticket 39280
	 */
	public function test_should_ignore_default_comment_page() {
		$now = time();

		$p  = self::factory()->post->create();
		$c1 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now ),
			)
		);
		$c2 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 20 ),
			)
		);
		$c3 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 30 ),
			)
		);
		$c4 = self::factory()->comment->create(
			array(
				'comment_post_ID'  => $p,
				'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', $now - 40 ),
			)
		);

		update_option( 'default_comment_page', 'newest' );
		update_option( 'page_comments', 1 );
		update_option( 'comments_per_page', 1 );

		$this->assertSame( 2, get_page_of_comment( $c3 ) );
	}

	/**
	 * @ticket 8973
	 */
	public function test_page_number_when_unapproved_comments_are_included_for_current_commenter() {
		$post         = self::factory()->post->create();
		$comment_args = array(
			'comment_post_ID'      => $post,
			'comment_approved'     => 0,
			'comment_author_email' => 'foo@bar.test',
			'comment_author'       => 'Foo',
			'comment_author_url'   => 'https://bar.test',
		);

		for ( $i = 1; $i < 4; $i++ ) {
			self::factory()->comment->create(
				array_merge(
					$comment_args,
					array(
						'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - ( $i * 1000 ) ),
					)
				)
			);
		}

		$new_unapproved = self::factory()->comment->create(
			$comment_args
		);

		add_filter( 'wp_get_current_commenter', array( $this, 'get_current_commenter' ) );

		$page     = get_page_of_comment( $new_unapproved, array( 'per_page' => 3 ) );
		$comments = get_comments(
			array(
				'number'             => 3,
				'paged'              => $page,
				'post_id'            => $post,
				'status'             => 'approve',
				'include_unapproved' => array( 'foo@bar.test' ),
				'orderby'            => 'comment_date_gmt',
				'order'              => 'ASC',
			)
		);

		remove_filter( 'wp_get_current_commenter', array( $this, 'get_current_commenter' ) );

		$this->assertContains( (string) $new_unapproved, wp_list_pluck( $comments, 'comment_ID' ) );
	}

	public function get_current_commenter() {
		return array(
			'comment_author_email' => 'foo@bar.test',
			'comment_author'       => 'Foo',
			'comment_author_url'   => 'https://bar.test',
		);
	}

	/**
	 * @ticket 8973
	 */
	public function test_page_number_when_unapproved_comments_are_included_for_current_user() {
		$current_user = get_current_user_id();
		$post         = self::factory()->post->create();
		$user         = self::factory()->user->create_and_get();
		$comment_args = array(
			'comment_post_ID'      => $post,
			'comment_approved'     => 0,
			'comment_author_email' => $user->user_email,
			'comment_author'       => $user->display_name,
			'comment_author_url'   => $user->user_url,
			'user_id'              => $user->ID,
		);

		for ( $i = 1; $i < 4; $i++ ) {
			self::factory()->comment->create(
				array_merge(
					$comment_args,
					array(
						'comment_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - ( $i * 1000 ) ),
					)
				)
			);
		}

		$new_unapproved = self::factory()->comment->create(
			$comment_args
		);

		wp_set_current_user( $user->ID );

		$page     = get_page_of_comment( $new_unapproved, array( 'per_page' => 3 ) );
		$comments = get_comments(
			array(
				'number'             => 3,
				'paged'              => $page,
				'post_id'            => $post,
				'status'             => 'approve',
				'include_unapproved' => array( $user->ID ),
				'orderby'            => 'comment_date_gmt',
				'order'              => 'ASC',
			)
		);

		$this->assertContains( (string) $new_unapproved, wp_list_pluck( $comments, 'comment_ID' ) );

		wp_set_current_user( $current_user );
	}
}
