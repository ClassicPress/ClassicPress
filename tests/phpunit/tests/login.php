<?php
/**
 * @group login
 */
class Tests_Login extends WP_UnitTestCase {
	function set_up() {
		// Something about these tests (@runInSeparateProcess maybe?) requires
		// the object cache to be (re)initialized.
		if ( wp_using_ext_object_cache() ) {
			wp_cache_init();
		}
		parent::set_up();
		reset_phpmailer_instance();
	}

	function tear_down() {
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
			. '\r?$/mi'
		);

		$this->assertMatchesRegularExpression( $regex, $mailer->get_sent()->body );
	}
}
