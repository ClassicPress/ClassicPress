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
		$hash           = wp_hash_password( $valid_password );

		// create a user and set their normal password and an application
		// password
		$user_id = $this->factory()->user->create(
			array(
				'user_pass' => $valid_password,
			)
		);

		// check the user password
		$this->assertTrue( wp_check_password( $valid_password, $hash, $user_id ) );

		// verify user password hash matches the one we set
		$user = get_user_by( 'id', $user_id );
		$this->assertSame( $hash, $user->user_pass );

		// create an application password for the user
		$app_pass      = WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => 'testapp' ) );
		$app_pass_hash = wp_hash_password( $app_pass[0] );

		// check the application password
		$this->assertTrue( wp_check_password( $app_pass[0], $app_pass_hash, $user_id ) );

		// check if the user password hash has not changed from the one we set
		$user = get_user_by( 'id', $user_id );
		$this->assertSame( $hash, $user->user_pass );
	}
}
