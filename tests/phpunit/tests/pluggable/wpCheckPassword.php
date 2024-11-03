<?php
/**
 * @group pluggable
 * @group password
 */
class Tests_Pluggable_wpCheckPassword extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_password_check_passes_valid_password_and_hash() {
		$valid_password = 'validpassword';
		$hash           = wp_hash_password( $valid_password );
		$this->assertTrue( wp_check_password( $valid_password, $hash ) );
	}

	public function test_password_check_fails_invalid_password() {
		$valid_password = 'validpassword';
		$hash           = wp_hash_password( $valid_password );
		$this->assertFalse( wp_check_password( 'invalidpassword', $hash ) );
	}

	public function test_password_check_fails_invalid_hash() {
		$valid_password = 'validpassword';
		$hash           = wp_hash_password( $valid_password );
		$this->assertFalse( wp_check_password( $valid_password, 'invalidhash' ) );
	}

	public function test_wp_check_password_returns_false_when_password_is_empty() {
		$this->assertFalse( wp_check_password( '', 'hash' ) );
	}

	public function test_wp_check_password_does_not_reset_password() {
		$valid_password = 'validpassword';
		$hash           = wp_hash_password( $valid_password );
		$this->assertTrue( wp_check_password( $valid_password, $hash ) );
	}

	public function test_wp_check_password_does_not_reset_application_password() {
		$valid_password = 'validpassword';
		$original_hash  = wp_hash_password( $valid_password );

		// create a user and set their password
		$user_id = $this->factory()->user->create(
			array(
				'user_pass' => $valid_password,
			)
		);

		// get the stored user password hash
		$user                = get_user_by( 'id', $user_id );
		$stored_hash_initial = $user->user_pass;

		// check the password, not passing the user
		$this->assertTrue( wp_check_password( $valid_password, $original_hash ) );
		// check the stored password hash passes for the password
		$this->assertTrue( wp_check_password( $valid_password, $stored_hash_initial ) );

		$other_pass      = 'otherpassword';
		$other_pass_hash = wp_hash_password( $other_pass );
		$this->assertTrue( wp_check_password( $other_pass, $other_pass_hash, $user_id ) );

		// get updated user password hash
		$user               = get_user_by( 'id', $user_id );
		$stored_hash_latest = $user->user_pass;
		$this->assertSame( $stored_hash_initial, $stored_hash_latest );
	}
}
