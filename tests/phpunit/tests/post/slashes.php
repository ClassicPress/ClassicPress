<?php

/**
 * @group post
 * @group slashes
 * @ticket 21767
 */
class Tests_Post_Slashes extends WP_UnitTestCase {
<<<<<<< HEAD
	public function set_up() {
		parent::set_up();

		$this->author_id = self::factory()->user->create( array( 'role' => 'editor' ) );

		wp_set_current_user( $this->author_id );

		// it is important to test with both even and odd numbered slashes as
		// kses does a strip-then-add slashes in some of its function calls
		$this->slash_1 = 'String with 1 slash \\';
		$this->slash_2 = 'String with 2 slashes \\\\';
		$this->slash_3 = 'String with 3 slashes \\\\\\';
		$this->slash_4 = 'String with 4 slashes \\\\\\\\';
		$this->slash_5 = 'String with 5 slashes \\\\\\\\\\';
		$this->slash_6 = 'String with 6 slashes \\\\\\\\\\\\';
		$this->slash_7 = 'String with 7 slashes \\\\\\\\\\\\\\';
=======

	/*
	 * It is important to test with both even and odd numbered slashes,
	 * as KSES does a strip-then-add slashes in some of its function calls.
	 */

	const SLASH_1 = 'String with 1 slash \\';
	const SLASH_2 = 'String with 2 slashes \\\\';
	const SLASH_3 = 'String with 3 slashes \\\\\\';
	const SLASH_4 = 'String with 4 slashes \\\\\\\\';
	const SLASH_5 = 'String with 5 slashes \\\\\\\\\\';
	const SLASH_6 = 'String with 6 slashes \\\\\\\\\\\\';
	const SLASH_7 = 'String with 7 slashes \\\\\\\\\\\\\\';

	protected static $author_id;
	protected static $post_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$author_id = $factory->user->create( array( 'role' => 'editor' ) );
		self::$post_id   = $factory->post->create();
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$author_id );
>>>>>>> bafecbeab5 (Code Modernization: Remove dynamic properties in `Tests_*_Slashes`.)
	}

	/**
	 * Tests the controller function that expects slashed data
	 *
	 */
	public function test_edit_post() {
		$id = self::factory()->post->create();

		$_POST               = array();
<<<<<<< HEAD
		$_POST['post_ID']    = $id;
		$_POST['post_title'] = $this->slash_1;
		$_POST['content']    = $this->slash_5;
		$_POST['excerpt']    = $this->slash_7;
		$_POST               = add_magic_quotes( $_POST ); // the edit_post() function will strip slashes
=======
		$_POST['post_ID']    = $post_id;
		$_POST['post_title'] = self::SLASH_1;
		$_POST['content']    = self::SLASH_5;
		$_POST['excerpt']    = self::SLASH_7;

		$_POST = add_magic_quotes( $_POST ); // The edit_post() function will strip slashes.
>>>>>>> bafecbeab5 (Code Modernization: Remove dynamic properties in `Tests_*_Slashes`.)

		$post_id = edit_post();
		$post    = get_post( $post_id );

		$this->assertSame( self::SLASH_1, $post->post_title );
		$this->assertSame( self::SLASH_5, $post->post_content );
		$this->assertSame( self::SLASH_7, $post->post_excerpt );

		$_POST               = array();
<<<<<<< HEAD
		$_POST['post_ID']    = $id;
		$_POST['post_title'] = $this->slash_2;
		$_POST['content']    = $this->slash_4;
		$_POST['excerpt']    = $this->slash_6;
		$_POST               = add_magic_quotes( $_POST );
=======
		$_POST['post_ID']    = $post_id;
		$_POST['post_title'] = self::SLASH_2;
		$_POST['content']    = self::SLASH_4;
		$_POST['excerpt']    = self::SLASH_6;

		$_POST = add_magic_quotes( $_POST ); // The edit_post() function will strip slashes.
>>>>>>> bafecbeab5 (Code Modernization: Remove dynamic properties in `Tests_*_Slashes`.)

		$post_id = edit_post();
		$post    = get_post( $post_id );

		$this->assertSame( self::SLASH_2, $post->post_title );
		$this->assertSame( self::SLASH_4, $post->post_content );
		$this->assertSame( self::SLASH_6, $post->post_excerpt );
	}

	/**
	 * Tests the model function that expects slashed data
	 *
	 */
	public function test_wp_insert_post() {
		$id   = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_title'   => self::SLASH_1,
				'post_content' => self::SLASH_3,
				'post_excerpt' => self::SLASH_5,
				'post_type'    => 'post',
				'slashed'      => false,
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( self::SLASH_1 ), $post->post_title );
		$this->assertSame( wp_unslash( self::SLASH_3 ), $post->post_content );
		$this->assertSame( wp_unslash( self::SLASH_5 ), $post->post_excerpt );

		$id   = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_title'   => self::SLASH_2,
				'post_content' => self::SLASH_4,
				'post_excerpt' => self::SLASH_6,
				'post_type'    => 'post',
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( self::SLASH_2 ), $post->post_title );
		$this->assertSame( wp_unslash( self::SLASH_4 ), $post->post_content );
		$this->assertSame( wp_unslash( self::SLASH_6 ), $post->post_excerpt );
	}

	/**
	 * Tests the model function that expects slashed data
	 *
	 */
	public function test_wp_update_post() {
		$id = self::factory()->post->create();

		wp_update_post(
			array(
<<<<<<< HEAD
				'ID'           => $id,
				'post_title'   => $this->slash_1,
				'post_content' => $this->slash_3,
				'post_excerpt' => $this->slash_5,
=======
				'ID'           => $post_id,
				'post_title'   => self::SLASH_1,
				'post_content' => self::SLASH_3,
				'post_excerpt' => self::SLASH_5,
>>>>>>> bafecbeab5 (Code Modernization: Remove dynamic properties in `Tests_*_Slashes`.)
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( self::SLASH_1 ), $post->post_title );
		$this->assertSame( wp_unslash( self::SLASH_3 ), $post->post_content );
		$this->assertSame( wp_unslash( self::SLASH_5 ), $post->post_excerpt );

		wp_update_post(
			array(
<<<<<<< HEAD
				'ID'           => $id,
				'post_title'   => $this->slash_2,
				'post_content' => $this->slash_4,
				'post_excerpt' => $this->slash_6,
=======
				'ID'           => $post_id,
				'post_title'   => self::SLASH_2,
				'post_content' => self::SLASH_4,
				'post_excerpt' => self::SLASH_6,
>>>>>>> bafecbeab5 (Code Modernization: Remove dynamic properties in `Tests_*_Slashes`.)
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( self::SLASH_2 ), $post->post_title );
		$this->assertSame( wp_unslash( self::SLASH_4 ), $post->post_content );
		$this->assertSame( wp_unslash( self::SLASH_6 ), $post->post_excerpt );
	}

	/**
	 * @ticket 27550
	 */
	public function test_wp_trash_untrash() {
		$post = array(
			'post_title'   => self::SLASH_1,
			'post_content' => self::SLASH_3,
			'post_excerpt' => self::SLASH_5,
		);
		$id   = wp_insert_post( wp_slash( $post ) );

		$trashed = wp_trash_post( $id );
		$this->assertNotEmpty( $trashed );

		$post = get_post( $id );

		$this->assertSame( self::SLASH_1, $post->post_title );
		$this->assertSame( self::SLASH_3, $post->post_content );
		$this->assertSame( self::SLASH_5, $post->post_excerpt );

		$untrashed = wp_untrash_post( $id );
		$this->assertNotEmpty( $untrashed );

		$post = get_post( $id );

		$this->assertSame( self::SLASH_1, $post->post_title );
		$this->assertSame( self::SLASH_3, $post->post_content );
		$this->assertSame( self::SLASH_5, $post->post_excerpt );
	}
}
