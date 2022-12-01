<?php
/**
 * @group login
 */
class Tests_Login extends WP_UnitTestCase {
	public function set_up() {
		// Something about these tests (@runInSeparateProcess maybe?) requires
		// the object cache to be (re)initialized.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_init();
		}
		parent::set_up();
		reset_phpmailer_instance();
	}

	public function tear_down() {
		reset_phpmailer_instance();
		parent::tear_down();
	}

	public function test_reset_password() {
		$_POST['user_login'] = 'admin';
		retrieve_password();

		$mailer = tests_retrieve_phpmailer_instance();

		$regex = (
			'/^http:\/\/'
			. preg_quote( WP_TESTS_DOMAIN, '/' )
			. '\/wp-login\.php\?action=rp\&key=[a-zA-Z0-9]{20}\&login='
			. preg_quote( $_POST['user_login'], '/' )
			. '$/mi'
		);

		$test = preg_match( $regex, $mailer->get_sent()->body );

		$this->assertEquals( $test, 1 );
	}

	public function test_reset_password_for_dotted_username() {
		$user = self::factory()->user->create_and_get(
			array(
				'user_login' => 'user1.',
			)
		);

		$_POST['user_login'] = $user->user_login;
		retrieve_password();

		$mailer = tests_retrieve_phpmailer_instance();

		$regex = (
			'/^http:\/\/'
			. preg_quote( WP_TESTS_DOMAIN, '/' )
			. '\/wp-login\.php\?action=rp\&key=[a-zA-Z0-9]{20}\&login='
			. preg_quote( $_POST['user_login'], '/' )
			. '$/mi'
		);

		$test = preg_match( $regex, $mailer->get_sent()->body );

		$this->assertNotEquals( $test, 1 );

		$encoded_user_login = str_replace( '.', '%2e', rawurlencode( $user->user_login ) );

		$regex = (
			'/^http:\/\/'
			. preg_quote( WP_TESTS_DOMAIN, '/' )
			. '\/wp-login\.php\?action=rp\&key=[a-zA-Z0-9]{20}\&login='
			. preg_quote( $encoded_user_login, '/' )
			. '$/mi'
		);

		$test = preg_match( $regex, $mailer->get_sent()->body );

		$this->assertEquals( $test, 1 );
	}
}
