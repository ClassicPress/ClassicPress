<?php
/**
 * @group pluggable
 * @group mail
 *
 * @covers ::wp_mail
 */
class Tests_Pluggable_wpCheckPassword extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		reset_phpmailer_instance();
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
		$this->assertTrue( wp_check_password( $valid_password, $hash ) );
	}
}
