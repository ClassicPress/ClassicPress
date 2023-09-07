<?php

/**
 * @group post
 * @group slashes
 * @ticket 21767
 */
class Tests_Post_Slashes extends WP_UnitTestCase {
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
	}

	/**
	 * Tests the controller function that expects slashed data
	 *
	 */
	public function test_edit_post() {
		$id = self::factory()->post->create();

		$_POST               = array();
		$_POST['post_ID']    = $id;
		$_POST['post_title'] = $this->slash_1;
		$_POST['content']    = $this->slash_5;
		$_POST['excerpt']    = $this->slash_7;
		$_POST               = add_magic_quotes( $_POST ); // the edit_post() function will strip slashes

		$post_id = edit_post();
		$post    = get_post( $post_id );

		$this->assertSame( $this->slash_1, $post->post_title );
		$this->assertSame( $this->slash_5, $post->post_content );
		$this->assertSame( $this->slash_7, $post->post_excerpt );

		$_POST               = array();
		$_POST['post_ID']    = $id;
		$_POST['post_title'] = $this->slash_2;
		$_POST['content']    = $this->slash_4;
		$_POST['excerpt']    = $this->slash_6;
		$_POST               = add_magic_quotes( $_POST );

		$post_id = edit_post();
		$post    = get_post( $post_id );

		$this->assertSame( $this->slash_2, $post->post_title );
		$this->assertSame( $this->slash_4, $post->post_content );
		$this->assertSame( $this->slash_6, $post->post_excerpt );
	}

	/**
	 * Tests the model function that expects slashed data
	 *
	 */
	public function test_wp_insert_post() {
		$id   = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_title'   => $this->slash_1,
				'post_content' => $this->slash_3,
				'post_excerpt' => $this->slash_5,
				'post_type'    => 'post',
				'slashed'      => false,
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( $this->slash_1 ), $post->post_title );
		$this->assertSame( wp_unslash( $this->slash_3 ), $post->post_content );
		$this->assertSame( wp_unslash( $this->slash_5 ), $post->post_excerpt );

		$id   = wp_insert_post(
			array(
				'post_status'  => 'publish',
				'post_title'   => $this->slash_2,
				'post_content' => $this->slash_4,
				'post_excerpt' => $this->slash_6,
				'post_type'    => 'post',
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( $this->slash_2 ), $post->post_title );
		$this->assertSame( wp_unslash( $this->slash_4 ), $post->post_content );
		$this->assertSame( wp_unslash( $this->slash_6 ), $post->post_excerpt );
	}

	/**
	 * Tests the model function that expects slashed data
	 *
	 */
	public function test_wp_update_post() {
		$id = self::factory()->post->create();

		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => $this->slash_1,
				'post_content' => $this->slash_3,
				'post_excerpt' => $this->slash_5,
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( $this->slash_1 ), $post->post_title );
		$this->assertSame( wp_unslash( $this->slash_3 ), $post->post_content );
		$this->assertSame( wp_unslash( $this->slash_5 ), $post->post_excerpt );

		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => $this->slash_2,
				'post_content' => $this->slash_4,
				'post_excerpt' => $this->slash_6,
			)
		);
		$post = get_post( $id );

		$this->assertSame( wp_unslash( $this->slash_2 ), $post->post_title );
		$this->assertSame( wp_unslash( $this->slash_4 ), $post->post_content );
		$this->assertSame( wp_unslash( $this->slash_6 ), $post->post_excerpt );
	}

	/**
	 * @ticket 27550
	 */
	public function test_wp_trash_untrash() {
		$post = array(
			'post_title'   => $this->slash_1,
			'post_content' => $this->slash_3,
			'post_excerpt' => $this->slash_5,
		);
		$id   = wp_insert_post( wp_slash( $post ) );

		$trashed = wp_trash_post( $id );
		$this->assertNotEmpty( $trashed );

		$post = get_post( $id );

		$this->assertSame( $this->slash_1, $post->post_title );
		$this->assertSame( $this->slash_3, $post->post_content );
		$this->assertSame( $this->slash_5, $post->post_excerpt );

		$untrashed = wp_untrash_post( $id );
		$this->assertNotEmpty( $untrashed );

		$post = get_post( $id );

		$this->assertSame( $this->slash_1, $post->post_title );
		$this->assertSame( $this->slash_3, $post->post_content );
		$this->assertSame( $this->slash_5, $post->post_excerpt );
	}
}
