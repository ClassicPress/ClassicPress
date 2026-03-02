<?php

/**
 * @group post
 * @covers ::post_password_required
 */
class Tests_Post_PostPasswordRequired extends WP_UnitTestCase {
	/**
	 * @var PasswordHash
	 */
	protected static $wp_hasher;
	const INVALID_MESSAGE = 'Password errata.';

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		self::$wp_hasher = new PasswordHash( 8, true );
	}

	public function test_post_password_required() {
		$password = 'password';

		// Create a post with a password:
		$post_id = self::factory()->post->create(
			array(
				'post_password' => $password,
			)
		);

		// Password is required:
		$this->assertTrue( post_password_required( $post_id ) );
	}

	public function test_post_password_not_required_with_valid_cookie() {
		$password = 'password';

		// Create a post with a password:
		$post_id = self::factory()->post->create(
			array(
				'post_password' => $password,
			)
		);

		// Set the cookie:
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = wp_hash_password( $password );

		// Check if the password is required:
		$required = post_password_required( $post_id );

		// Clear the cookie:
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );

		// Password is not required:
		$this->assertFalse( $required );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_post_password_hashed_with_phpass_remains_valid() {
		$password = 'password';

		// Create a post with a password:
		$post_id = self::factory()->post->create(
			array(
				'post_password' => $password,
			)
		);

		// Set the cookie with the phpass hash:
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = self::$wp_hasher->HashPassword( $password );

		// Check if the password is required:
		$required = post_password_required( $post_id );

		// Clear the cookie:
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );

		// Password is not required as it remains valid when hashed with phpass:
		$this->assertFalse( $required );
	}

	public function test_post_password_invalid_message() {
		// Create a post with a password:
		$post_id = self::factory()->post->create(
			array(
				'post_password' => 'password',
			)
		);

		// Set the cookie with the wrong phpass hash:
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = self::$wp_hasher->HashPassword( 'wrong-password' );

		// Set the referer to the same page:
		$_SERVER['HTTP_REFERER'] = get_permalink( $post_id );

		// Go to the page:
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'Invalid password.', get_the_content() );

		// Clear the cookie:
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
	}

	public function filter_post_password_invalid_message_filter() {
		return self::INVALID_MESSAGE;
	}

	public function test_post_password_invalid_message_filter() {
		// Create a post with a password:
		$post_id = self::factory()->post->create(
			array(
				'post_password' => 'password',
			)
		);

		// Set the cookie with the wrong phpass hash:
		$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = self::$wp_hasher->HashPassword( 'wrong-password' );

		// Set the referer to the same page:
		$_SERVER['HTTP_REFERER'] = get_permalink( $post_id );

		// Add the filter:
		add_filter( 'the_password_form_incorrect_password', array( $this, 'filter_post_password_invalid_message_filter' ) );

		// Go to the page:
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( self::INVALID_MESSAGE, get_the_content() );

		// Clear the cookie:
		unset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );

		// Remove the filter:
		add_filter( 'the_password_form_incorrect_password', array( $this, 'filter_post_password_invalid_message_filter' ) );
	}
}
