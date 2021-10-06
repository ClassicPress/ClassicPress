<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_wp_newComment extends WP_XMLRPC_UnitTestCase {

	function test_valid_comment() {
		$this->make_user_by_role( 'administrator' );
		$post = self::factory()->post->create_and_get();
 
		$result = $this->myxmlrpcserver->wp_newComment( array( 1, 'administrator', 'administrator', $post->ID, array(
			'content' => rand_str( 100 )
		) ) );
 
		$this->assertNotIXRError( $result );
	}
 
	function test_empty_comment() {
		$this->make_user_by_role( 'administrator' );
		$post = self::factory()->post->create_and_get();
 
		$result = $this->myxmlrpcserver->wp_newComment( array( 1, 'administrator', 'administrator', $post->ID, array(
			'content' => ''
		) ) );
 
		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

	function test_new_comment_post_closed() {
		$this->make_user_by_role( 'administrator' );
		$post = self::factory()->post->create_and_get( array(
			'comment_status' => 'closed'
		) );

		$this->assertSame( 'closed', $post->comment_status );

		$result = $this->myxmlrpcserver->wp_newComment( array( 1, 'administrator', 'administrator', $post->ID, array(
			'content' => rand_str( 100 ),
		) ) );

		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

	function test_new_comment_duplicated() {
		$this->make_user_by_role( 'administrator' );
		$post = self::factory()->post->create_and_get();

		$comment_args = array( 1, 'administrator', 'administrator', $post->ID, array(
			'content' => rand_str( 100 ),
		) );

		// First time it's a valid comment
		$result = $this->myxmlrpcserver->wp_newComment( $comment_args  );
		$this->assertNotIXRError( $result );

		// Run second time for duplication error
		$result = $this->myxmlrpcserver->wp_newComment( $comment_args );

		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

<<<<<<< HEAD
=======
	/**
	 * Ensure anonymous comments can be made via XML-RPC.
	 *
	 * @ticket 51595
	 */
	function test_allowed_anon_comments() {
		add_filter( 'xmlrpc_allow_anonymous_comments', '__return_true' );

		$comment_args = array(
			1,
			'',
			'',
			self::$posts['publish']->ID,
			array(
				'author'       => 'WordPress',
				'author_email' => 'noreply@wordpress.org',
				'content'      => 'Test Anon Comments',
			),
		);

		$result = $this->myxmlrpcserver->wp_newComment( $comment_args );
		$this->assertNotIXRError( $result );
		$this->assertIsInt( $result );
	}

	/**
	 * Ensure anonymous XML-RPC comments require a valid email.
	 *
	 * @ticket 51595
	 */
	function test_anon_comments_require_email() {
		add_filter( 'xmlrpc_allow_anonymous_comments', '__return_true' );

		$comment_args = array(
			1,
			'',
			'',
			self::$posts['publish']->ID,
			array(
				'author'       => 'WordPress',
				'author_email' => 'noreply at wordpress.org',
				'content'      => 'Test Anon Comments',
			),
		);

		$result = $this->myxmlrpcserver->wp_newComment( $comment_args );
		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

	/**
	 * Ensure valid users don't use the anon flow.
	 *
	 * @ticket 51595
	 */
	function test_username_avoids_anon_flow() {
		add_filter( 'xmlrpc_allow_anonymous_comments', '__return_true' );

		$comment_args = array(
			1,
			'administrator',
			'administrator',
			self::$posts['publish']->ID,
			array(
				'author'       => 'WordPress',
				'author_email' => 'noreply at wordpress.org',
				'content'      => 'Test Anon Comments',
			),
		);

		$result  = $this->myxmlrpcserver->wp_newComment( $comment_args );
		$comment = get_comment( $result );
		$user_id = get_user_by( 'login', 'administrator' )->ID;

		$this->assertSame( $user_id, (int) $comment->user_id );
	}

	/**
	 * Ensure users can only comment on posts they're permitted to access.
	 *
	 * @dataProvider data_comments_observe_post_permissions
	 *
	 * @param string $post_key      Post identifier from the self::$posts array.
	 * @param string $username      Username leaving comment.
	 * @param bool   $expected      Expected result. True: successfull comment. False: Refused comment.
	 * @param string $anon_callback Optional. Allow anonymous comment callback. Default __return_false.
	 */
	function test_comments_observe_post_permissions( $post_key, $username, $expected, $anon_callback = '__return_false' ) {
		add_filter( 'xmlrpc_allow_anonymous_comments', $anon_callback );

		$comment_args = array(
			1,
			$username,
			$username,
			self::$posts[ $post_key ]->ID,
			array(
				'author'       => 'WordPress',
				'author_email' => 'noreply@wordpress.org',
				'content'      => 'Test Comment',
			),
		);

		$result = $this->myxmlrpcserver->wp_newComment( $comment_args );
		if ( $expected ) {
			$this->assertIsInt( $result );
			return;
		}

		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

	/**
	 * Data provider for test_comments_observe_post_permissions.
	 *
	 * @return array[] {
	 *     @type string Post identifier from the self::$posts array.
	 *     @type string Username leaving comment.
	 *     @type bool   Expected result. True: successfull comment. False: Refused comment.
	 *     @type string Optional. Allow anonymous comment callback. Default __return_false.
	 * }
	 */
	function data_comments_observe_post_permissions() {
		return array(
			// 0: Post author, password protected public post.
			array(
				'password',
				'administrator',
				true,
			),
			// 1: Low privileged non-author, password protected public post.
			array(
				'password',
				'contributor',
				false,
			),
			// 2: Anonymous user, password protected public post.
			array(
				'password',
				'', // Anonymous user.
				false,
			),
			// 3: Anonymous user, anon comments allowed, password protected public post.
			array(
				'password',
				'', // Anonymous user.
				false,
				'__return_true',
			),

			// 4: Post author, private post.
			array(
				'private',
				'administrator',
				true,
			),
			// 5: Low privileged non-author, private post.
			array(
				'private',
				'contributor',
				false,
			),
			// 6: Anonymous user, private post.
			array(
				'private',
				'', // Anonymous user.
				false,
			),
			// 7: Anonymous user, anon comments allowed, private post.
			array(
				'private',
				'', // Anonymous user.
				false,
				'__return_true',
			),

			// 8: High privileged non-author, private post.
			array(
				'private_contributor',
				'administrator',
				true,
			),
			// 9: Low privileged author, private post.
			array(
				'private_contributor',
				'contributor',
				true,
			),
			// 10: Anonymous user, private post.
			array(
				'private_contributor',
				'', // Anonymous user.
				false,
			),
			// 11: Anonymous user, anon comments allowed, private post.
			array(
				'private_contributor',
				'', // Anonymous user.
				false,
				'__return_true',
			),
		);
	}
>>>>>>> bca693b190 (Build/Test Tools: Replace `assertInternalType()` usage in unit tests.)
}
