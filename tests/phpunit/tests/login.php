<?php
/**
 * @group login
 */
class Tests_Login extends WP_UnitTestCase {
	function setUp() {
		if ( wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Not testable with an external object cache.' );
		} else {
			self::$ignore_files = true;
			parent::setUp();
			reset_phpmailer_instance();
		}
	}

	function tearDown() {
		reset_phpmailer_instance();
		parent::tearDown();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_reset_password() {
		ob_start();
		include_once( ABSPATH . '/wp-login.php' );
		$_POST['user_login'] = 'admin';
		retrieve_password();

		$mailer = tests_retrieve_phpmailer_instance();
		ob_end_clean();

		$regex = '/^http:\/\/' . WP_TESTS_DOMAIN . '\/wp-login\.php\?action=rp\&key=[a-zA-Z0-9]{20}\&login=' . $_POST['user_login'] . '$/mi';

		$test = preg_match( $regex, $mailer->get_sent()->body );

		$this->assertEquals( $test, 1 );
	}
}
