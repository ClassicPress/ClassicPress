<?php
/**
 * @group login
 */
class Tests_Login extends WP_UnitTestCase {
	function set_up() {
		parent::set_up();
		reset_phpmailer_instance();
	}

	function tear_down() {
		reset_phpmailer_instance();
		parent::tear_down();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_reset_password() {
		// @runInSeparateProcess requires the object cache to be (re)initialized.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_init();
		}

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
}
