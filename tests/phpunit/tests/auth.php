<?php

/**
 * @group pluggable
 * @group auth
 */
class Tests_Auth extends WP_UnitTestCase {
	// Class User values assigned to constants.
	const USER_EMAIL = 'test@password.com';
	const USER_LOGIN = 'password-user';
	const USER_PASS  = 'password';

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var WP_User
	 */
	protected static $_user;

	/**
	 * @var int
	 */
	protected static $user_id;

	/**
	 * @var PasswordHash
	 */
	protected static $wp_hasher;

	protected static $bcrypt_length_limit = 72;

	protected static $phpass_length_limit = 4096;

	/**
	 * Action hook.
	 */
	protected $nonce_failure_hook = 'wp_verify_nonce_failed';

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$_user = $factory->user->create_and_get(
			array(
				'user_login' => self::USER_LOGIN,
				'user_email' => self::USER_EMAIL,
				'user_pass'  => self::USER_PASS,
			)
		);

		self::$user_id = self::$_user->ID;

		require_once ABSPATH . WPINC . '/class-phpass.php';
		self::$wp_hasher = new PasswordHash( 8, true );
	}

	public function set_up() {
		parent::set_up();

		$this->user = clone self::$_user;
		wp_set_current_user( self::$user_id );
		update_site_option( 'using_application_passwords', 1 );

		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $GLOBALS['wp_rest_application_password_status'], $GLOBALS['wp_rest_application_password_uuid'] );
	}

	public function tear_down() {
		// Cleanup all the global state.
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $GLOBALS['wp_rest_application_password_status'], $GLOBALS['wp_rest_application_password_uuid'] );

		// Cleanup manual auth cookie test.
		unset( $_COOKIE[ AUTH_COOKIE ] );
		unset( $_COOKIE[ SECURE_AUTH_COOKIE ] );

		parent::tear_down();
	}

	public function test_auth_cookie_valid() {
		$cookie = wp_generate_auth_cookie( self::$user_id, time() + 3600, 'auth' );
		$this->assertSame( self::$user_id, wp_validate_auth_cookie( $cookie, 'auth' ) );
	}

	public function test_auth_cookie_invalid() {
		// 3600 or less and +3600 may occur in wp_validate_auth_cookie(),
		// as an ajax test may have defined DOING_AJAX, failing the test.

		$cookie = wp_generate_auth_cookie( self::$user_id, time() - 7200, 'auth' );
		$this->assertFalse( wp_validate_auth_cookie( $cookie, 'auth' ), 'expired cookie' );

		$cookie = wp_generate_auth_cookie( self::$user_id, time() + 3600, 'auth' );
		$this->assertFalse( wp_validate_auth_cookie( $cookie, 'logged_in' ), 'wrong auth scheme' );

		$cookie          = wp_generate_auth_cookie( self::$user_id, time() + 3600, 'auth' );
		list($a, $b, $c) = explode( '|', $cookie );
		$cookie          = $a . '|' . ( $b + 1 ) . '|' . $c;
		$this->assertFalse( wp_validate_auth_cookie( self::$user_id, 'auth' ), 'altered cookie' );
	}

	public function test_auth_cookie_scheme() {
		// Arbitrary scheme name.
		$cookie = wp_generate_auth_cookie( self::$user_id, time() + 3600, 'foo' );
		$this->assertSame( self::$user_id, wp_validate_auth_cookie( $cookie, 'foo' ) );

		// Wrong scheme name - should fail.
		$cookie = wp_generate_auth_cookie( self::$user_id, time() + 3600, 'foo' );
		$this->assertFalse( wp_validate_auth_cookie( $cookie, 'bar' ) );
	}

	/**
	 * @ticket 23494
	 */
	public function test_password_trimming() {
		$passwords_to_test = array(
			'a password with no trailing or leading spaces',
			'a password with trailing spaces ',
			' a password with leading spaces',
			' a password with trailing and leading spaces ',
		);

		foreach ( $passwords_to_test as $password_to_test ) {
			wp_set_password( $password_to_test, $this->user->ID );
			$authed_user = wp_authenticate( $this->user->user_login, $password_to_test );

			$this->assertNotWPError( $authed_user );
			$this->assertInstanceOf( 'WP_User', $authed_user );
			$this->assertSame( $this->user->ID, $authed_user->ID );
		}
	}

	/**
	 * Tests hooking into wp_set_password().
	 *
	 * @ticket 57436
	 * @ticket 61541
	 *
	 * @covers ::wp_set_password
	 */
	public function test_wp_set_password_action() {
		$action = new MockAction();

		$previous_user_pass = get_user_by( 'id', $this->user->ID )->user_pass;

		add_action( 'wp_set_password', array( $action, 'action' ), 10, 3 );
		wp_set_password( 'A simple password', $this->user->ID );

		$this->assertSame( 1, $action->get_call_count() );

		// Check that the old data passed through the hook is correct.
		$this->assertSame( $previous_user_pass, $action->get_args()[0][2]->user_pass );
	}

	/**
	 * Test wp_hash_password trims whitespace
	 *
	 * This is similar to test_password_trimming but tests the "lower level"
	 * wp_hash_password function
	 *
	 * @ticket 24973
	 */
	public function test_wp_hash_password_trimming() {

		$password = ' pass with leading whitespace';
		$this->assertTrue( wp_check_password( 'pass with leading whitespace', wp_hash_password( $password ) ) );

		$password = 'pass with trailing whitespace ';
		$this->assertTrue( wp_check_password( 'pass with trailing whitespace', wp_hash_password( $password ) ) );

		$password = ' pass with whitespace ';
		$this->assertTrue( wp_check_password( 'pass with whitespace', wp_hash_password( $password ) ) );

		$password = "pass with new line \n";
		$this->assertTrue( wp_check_password( 'pass with new line', wp_hash_password( $password ) ) );

		$password = "pass with vertical tab o_O\x0B";
		$this->assertTrue( wp_check_password( 'pass with vertical tab o_O', wp_hash_password( $password ) ) );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_phpass_hash() {
		$password = 'password';
		$hash     = self::$wp_hasher->HashPassword( $password );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * Ensure wp_check_password() remains compatible with an increase to the default bcrypt cost.
	 *
	 * The test verifies this by reducing the cost used to generate the hash, therefore mimicing a hash
	 * which was generated prior to the default cost being increased.
	 *
	 * Notably the bcrypt cost may get increased in PHP 8.4: https://wiki.php.net/rfc/bcrypt_cost_2023 .
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_hash_with_increased_bcrypt_cost() {
		$password = 'password';
		add_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
		$options  = cp_hash_password_options();
		--$options['cost'];
		$hash     = password_hash( trim( $password ), PASSWORD_BCRYPT, $options );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
		remove_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
	}

	/**
	 * Ensure wp_check_password() remains compatible with a reduction of the default bcrypt cost.
	 *
	 * The test verifies this by increasing the cost used to generate the hash, therefore mimicing a hash
	 * which was generated prior to the default cost being reduced.
	 *
	 * A reduction of the cost is unlikely to occur but is fully supported.
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_hash_with_reduced_bcrypt_cost() {
		$password = 'password';
		add_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
		$options  = cp_hash_password_options();
		++$options['cost'];
		$hash     = password_hash( trim( $password ), PASSWORD_BCRYPT, $options );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
		remove_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_hash_with_default_bcrypt_cost() {
		$password = 'password';
		add_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
		$options  = cp_hash_password_options();
		$hash     = password_hash( trim( $password ), PASSWORD_BCRYPT, $options );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
		remove_filter( 'cp_password_algorithm', self::set_default_bcrypt() );
	}

	/**
	 * Ensure wp_check_password() is compatible with Argon2i hashes.
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_argon2i_hash() {
		if ( ! defined( 'PASSWORD_ARGON2I' ) ) {
			$this->fail( 'Argon2i is not supported.' );
		}

		$password = 'password';
		$hash     = password_hash( trim( $password ), PASSWORD_ARGON2I );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * Ensure wp_check_password() is compatible with Argon2id hashes.
	 *
	 * @requires PHP >= 7.3
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_argon2id_hash() {
		if ( ! defined( 'PASSWORD_ARGON2ID' ) ) {
			$this->fail( 'Argon2id is not supported.' );
		}

		$password = 'password';
		$hash     = password_hash( trim( $password ), PASSWORD_ARGON2ID );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_supports_md5_hash() {
		$password = 'password';
		$hash     = md5( $password );
		$this->assertTrue( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_wp_check_password_does_not_support_plain_text() {
		$password = 'password';
		$hash     = $password;
		$this->assertFalse( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 *
	 * @dataProvider data_empty_values
	 * @param mixed $value
	 */
	public function test_wp_check_password_does_not_support_empty_hash( $value ) {
		$password = 'password';
		$hash     = $value;
		$this->assertFalse( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 *
	 * @dataProvider data_empty_values
	 * @param mixed $value
	 */
	public function test_wp_check_password_does_not_support_empty_password( $value ) {
		$password = $value;
		$hash     = $value;
		$this->assertFalse( wp_check_password( $password, $hash ) );
		$this->assertSame( 1, did_filter( 'check_password' ) );
	}

	public function data_empty_values() {
		return array(
			// Integer zero:
			array( 0 ),
			// String zero:
			array( '0' ),
			// Zero-length string:
			array( '' ),
			// Null byte character:
			array( "\0" ),
			// Asterisk values:
			array( '*' ),
			array( '*0' ),
			array( '*1' ),
		);
	}

	/**
	 * @ticket 29217
	 */
	public function test_wp_verify_nonce_with_empty_arg() {
		$this->assertFalse( wp_verify_nonce( '' ) );
		$this->assertFalse( wp_verify_nonce( null ) );
	}

	/**
	 * @ticket 29542
	 */
	public function test_wp_verify_nonce_with_integer_arg() {
		$this->assertFalse( wp_verify_nonce( 1 ) );
	}

	/**
	 * @ticket 24030
	 */
	public function test_wp_nonce_verify_failed() {
		$nonce = substr( md5( uniqid() ), 0, 10 );
		$count = did_action( $this->nonce_failure_hook );

		wp_verify_nonce( $nonce, 'nonce_test_action' );

		$this->assertSame( ( $count + 1 ), did_action( $this->nonce_failure_hook ) );
	}

	/**
	 * @ticket 24030
	 */
	public function test_wp_nonce_verify_success() {
		$nonce = wp_create_nonce( 'nonce_test_action' );
		$count = did_action( $this->nonce_failure_hook );

		wp_verify_nonce( $nonce, 'nonce_test_action' );

		$this->assertSame( $count, did_action( $this->nonce_failure_hook ) );
	}

	/**
	 * @ticket 36361
	 */
	public function test_check_admin_referer_with_no_action_triggers_doing_it_wrong() {
		$this->setExpectedIncorrectUsage( 'check_admin_referer' );

		// A valid nonce needs to be set so the check doesn't die().
		$_REQUEST['_wpnonce'] = wp_create_nonce( -1 );
		$result               = check_admin_referer();
		$this->assertSame( 1, $result );

		unset( $_REQUEST['_wpnonce'] );
	}

	public function test_check_admin_referer_with_default_action_as_string_not_doing_it_wrong() {
		// A valid nonce needs to be set so the check doesn't die().
		$_REQUEST['_wpnonce'] = wp_create_nonce( '-1' );
		$result               = check_admin_referer( '-1' );
		$this->assertSame( 1, $result );

		unset( $_REQUEST['_wpnonce'] );
	}

	/**
	 * @ticket 36361
	 */
	public function test_check_ajax_referer_with_no_action_triggers_doing_it_wrong() {
		$this->setExpectedIncorrectUsage( 'check_ajax_referer' );

		// A valid nonce needs to be set so the check doesn't die().
		$_REQUEST['_wpnonce'] = wp_create_nonce( -1 );
		$result               = check_ajax_referer();
		$this->assertSame( 1, $result );

		unset( $_REQUEST['_wpnonce'] );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_password_is_hashed_with_bcrypt() {
		$password = 'password';

		// Set the user password.
		wp_set_password( $password, self::$user_id );

		// Ensure the password is hashed with bcrypt.
		$this->assertStringStartsWith( '$2y$', get_userdata( self::$user_id )->user_pass );

		// Authenticate.
		$user = wp_authenticate( $this->user->user_login, $password );

		// Verify correct password.
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_invalid_password_at_bcrypt_length_limit_is_rejected() {
		$limit = str_repeat( 'a', self::$bcrypt_length_limit );

		// Set the user password to the bcrypt limit.
		wp_set_password( $limit, self::$user_id );

		$user = wp_authenticate( $this->user->user_login, 'aaaaaaaa' );
		// Wrong password.
		$this->assertWPError( $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_invalid_password_beyond_bcrypt_length_limit_is_rejected() {
		$limit = str_repeat( 'a', self::$bcrypt_length_limit + 1 );

		// Set the user password beyond the bcrypt limit.
		wp_set_password( $limit, self::$user_id );

		$user = wp_authenticate( $this->user->user_login, 'aaaaaaaa' );
		// Wrong password.
		$this->assertWPError( $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_valid_password_at_bcrypt_length_limit_is_accepted() {
		$limit = str_repeat( 'a', self::$bcrypt_length_limit );

		// Set the user password to the bcrypt limit.
		wp_set_password( $limit, self::$user_id );

		// Authenticate.
		$user = wp_authenticate( $this->user->user_login, $limit );

		// Correct password.
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_valid_password_beyond_bcrypt_length_limit_is_accepted() {
		$limit = str_repeat( 'a', self::$bcrypt_length_limit + 1 );

		// Set the user password beyond the bcrypt limit.
		wp_set_password( $limit, self::$user_id );

		// Authenticate.
		$user = wp_authenticate( $this->user->user_login, $limit );

		// Correct password depite its length.
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @see https://core.trac.wordpress.org/changeset/30466
	 */
	public function test_invalid_password_at_phpass_length_limit_is_rejected() {
		$limit = str_repeat( 'a', self::$phpass_length_limit );

		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( $limit, self::$user_id );

		// Authenticate.
		$user = wp_authenticate( $this->user->user_login, 'aaaaaaaa' );

		// Wrong password.
		$this->assertInstanceOf( 'WP_Error', $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	public function test_valid_password_at_phpass_length_limit_is_accepted() {
		$limit = str_repeat( 'a', self::$phpass_length_limit );

		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( $limit, self::$user_id );

		// Authenticate.
		$user = wp_authenticate( $this->user->user_login, $limit );

		// Correct password.
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	public function test_too_long_password_at_phpass_length_limit_is_rejected() {
		$limit = str_repeat( 'a', self::$phpass_length_limit );

		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( $limit, self::$user_id );

		// Authenticate with a password that is one character too long.
		$user = wp_authenticate( $this->user->user_login, $limit . 'a' );

		// Wrong password.
		$this->assertInstanceOf( 'WP_Error', $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	public function test_too_long_password_beyond_phpass_length_limit_is_rejected() {
		// One char too many.
		$too_long = str_repeat( 'a', self::$phpass_length_limit + 1 );

		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( $too_long, self::$user_id );

		$user = get_user_by( 'id', self::$user_id );
		// Password broken by setting it to be too long.
		$this->assertSame( '*', $user->data->user_pass );

		// Password is not accepted.
		$user = wp_authenticate( $this->user->user_login, '*' );
		$this->assertInstanceOf( 'WP_Error', $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	/**
	 * @dataProvider data_empty_values
	 * @param mixed $value
	 */
	public function test_empty_password_is_rejected_by_bcrypt( $value ) {
		// Set the user password.
		wp_set_password( 'password', self::$user_id );

		$user = wp_authenticate( $this->user->user_login, $value );
		$this->assertInstanceOf( 'WP_Error', $user );
	}

	/**
	 * @dataProvider data_empty_values
	 * @param mixed $value
	 */
	public function test_empty_password_is_rejected_by_phpass( $value ) {
		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( 'password', self::$user_id );

		$user = wp_authenticate( $this->user->user_login, $value );
		$this->assertInstanceOf( 'WP_Error', $user );
	}

	public function test_incorrect_password_is_rejected_by_phpass() {
		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( 'password', self::$user_id );

		$user = wp_authenticate( $this->user->user_login, 'aaaaaaaa' );

		// Wrong password.
		$this->assertInstanceOf( 'WP_Error', $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	public function test_too_long_password_is_rejected_by_phpass() {
		$limit = str_repeat( 'a', self::$phpass_length_limit );

		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( 'password', self::$user_id );

		$user = wp_authenticate( $this->user->user_login, $limit . 'a' );

		// Password broken by setting it to be too long.
		$this->assertInstanceOf( 'WP_Error', $user );
		$this->assertSame( 'incorrect_password', $user->get_error_code() );
	}

	/**
	 * @ticket 45746
	 */
	public function test_user_activation_key_is_saved() {
		$user = get_userdata( $this->user->ID );
		$key  = get_password_reset_key( $user );

		// A correctly saved key should be accepted.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertNotWPError( $check );
		$this->assertInstanceOf( 'WP_User', $check );
		$this->assertSame( $this->user->ID, $check->ID );
	}

	/**
	 * @ticket 32429
	 */
	public function test_user_activation_key_is_checked() {
		global $wpdb;

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => strtotime( '-1 hour' ) . ':' . wp_hash_password( $key ),
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// A valid key should be accepted.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertNotWPError( $check );
		$this->assertInstanceOf( 'WP_User', $check );
		$this->assertSame( $this->user->ID, $check->ID );

		// An invalid key should be rejected.
		$check = check_password_reset_key( 'key', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );

		// An empty key should be rejected.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );

		// A truncated key should be rejected.
		$partial = substr( $key, 0, 10 );
		$check   = check_password_reset_key( $partial, $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
	}

	/**
	 * @ticket 32429
	 */
	public function test_expired_user_activation_key_is_rejected() {
		global $wpdb;

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => strtotime( '-48 hours' ) . ':' . wp_hash_password( $key ),
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// An expired but otherwise valid key should be rejected.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
		$this->assertSame( 'expired_key', $check->get_error_code() );
	}

	/**
	 * @ticket 32429
	 */
	public function test_empty_user_activation_key_fails_key_check() {
		// An empty user_activation_key should not allow any key to be accepted.
		$check = check_password_reset_key( 'key', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );

		// An empty user_activation_key should not allow an empty key to be accepted.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
	}

	/**
	 * @ticket 32429
	 */
	public function test_legacy_user_activation_key_is_rejected() {
		global $wpdb;

		// A legacy user_activation_key is one without the `time()` prefix introduced in WordPress 4.3.

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => wp_hash_password( $key ),
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// A legacy user_activation_key should not be accepted.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
		$this->assertSame( 'expired_key', $check->get_error_code() );

		// An empty key with a legacy user_activation_key should be rejected.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
		$this->assertSame( 'invalid_key', $check->get_error_code() );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_phpass_user_activation_key_is_allowed() {
		global $wpdb;

		// A legacy user_activation_key is one hashed using phpass between WordPress 4.3 and x.y.z.

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => strtotime( '-1 hour' ) . ':' . self::$wp_hasher->HashPassword( $key ),
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// A legacy phpass user_activation_key should remain valid.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertNotWPError( $check );
		$this->assertInstanceOf( 'WP_User', $check );
		$this->assertSame( $this->user->ID, $check->ID );

		// An empty key with a legacy user_activation_key should be rejected.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertWPError( $check );
		$this->assertSame( 'invalid_key', $check->get_error_code() );
	}

	/**
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_expired_phpass_user_activation_key_is_rejected() {
		global $wpdb;

		// A legacy user_activation_key is one hashed using phpass between WordPress 4.3 and x.y.z.

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => strtotime( '-48 hours' ) . ':' . self::$wp_hasher->HashPassword( $key ),
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// A legacy phpass user_activation_key should still be subject to an expiry check.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertWPError( $check );
		$this->assertSame( 'expired_key', $check->get_error_code() );

		// An empty key with a legacy user_activation_key should be rejected.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertWPError( $check );
		$this->assertSame( 'invalid_key', $check->get_error_code() );
	}

	/**
	 * The `wp_password_needs_rehash()` function is just a wrapper around `password_needs_rehash()`, but this ensures
	 * that it works as expected.
	 *
	 * Notably the bcrypt cost may get increased in PHP 8.4: https://wiki.php.net/rfc/bcrypt_cost_2023 .
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function check_password_needs_rehashing() {
		$password = 'password';

		// Current password hashing algorithm.
		$hash = wp_hash_password( $password );
		$this->assertFalse( wp_password_needs_rehash( $hash ) );

		// A future upgrade from a previously lower cost.
		$default = self::get_default_bcrypt_cost();
		$opts    = array(
			// Reducing the cost mimics an increase in the default cost.
			'cost' => $default - 1,
		);
		$hash    = password_hash( $password, PASSWORD_BCRYPT, $opts );
		$this->assertTrue( wp_password_needs_rehash( $hash ) );

		// Previous phpass algorithm.
		$hash = self::$wp_hasher->HashPassword( $password );
		$this->assertTrue( wp_password_needs_rehash( $hash ) );

		// o_O md5.
		$hash = md5( $password );
		$this->assertTrue( wp_password_needs_rehash( $hash ) );
	}

	/**
	 * @ticket 32429
	 * @ticket 24783
	 */
	public function test_plaintext_user_activation_key_is_rejected() {
		global $wpdb;

		// A plaintext user_activation_key is one stored before hashing was introduced in WordPress 3.7.

		$key = wp_generate_password( 20, false );
		$wpdb->update(
			$wpdb->users,
			array(
				'user_activation_key' => $key,
			),
			array(
				'ID' => $this->user->ID,
			)
		);
		clean_user_cache( $this->user );

		// A plaintext user_activation_key should not allow an otherwise valid key to be accepted.
		$check = check_password_reset_key( $key, $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );

		// A plaintext user_activation_key should not allow an empty key to be accepted.
		$check = check_password_reset_key( '', $this->user->user_login );
		$this->assertInstanceOf( 'WP_Error', $check );
	}

	/**
	 * Ensure users can log in using both their username and their email address.
	 *
	 * @ticket 9568
	 */
	public function test_log_in_using_email() {
		$this->assertInstanceOf( 'WP_User', wp_authenticate( self::USER_EMAIL, self::USER_PASS ) );
		$this->assertInstanceOf( 'WP_User', wp_authenticate( self::USER_LOGIN, self::USER_PASS ) );
	}

	/**
	 * @ticket 60700
	 */
	public function test_authenticate_filter() {
		add_filter( 'authenticate', '__return_null', 20 );
		$this->assertInstanceOf( 'WP_Error', wp_authenticate( self::USER_LOGIN, self::USER_PASS ) );
		add_filter( 'authenticate', '__return_false', 20 );
		$this->assertInstanceOf( 'WP_Error', wp_authenticate( self::USER_LOGIN, self::USER_PASS ) );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_wp_user_object() {
		$result = wp_authenticate_username_password( self::$_user, '', '' );
		$this->assertSame( $result->ID, self::$user_id );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_login_and_password() {
		$result = wp_authenticate_username_password( null, self::USER_LOGIN, self::USER_PASS );
		$this->assertSame( self::$user_id, $result->ID );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_null_password() {
		$result = wp_authenticate_username_password( null, self::USER_LOGIN, null );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_null_login() {
		$result = wp_authenticate_username_password( null, null, self::USER_PASS );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_invalid_login() {
		$result = wp_authenticate_username_password( null, 'invalidlogin', self::USER_PASS );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_username_password_with_invalid_password() {
		$result = wp_authenticate_username_password( null, self::USER_LOGIN, 'invalidpassword' );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_wp_user_object() {
		$result = wp_authenticate_email_password( self::$_user, '', '' );
		$this->assertSame( self::$user_id, $result->ID );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_login_and_password() {
		$result = wp_authenticate_email_password( null, self::USER_EMAIL, self::USER_PASS );
		$this->assertSame( self::$user_id, $result->ID );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_null_password() {
		$result = wp_authenticate_email_password( null, self::USER_EMAIL, null );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_null_email() {
		$result = wp_authenticate_email_password( null, null, self::USER_PASS );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_invalid_email() {
		$result = wp_authenticate_email_password( null, 'invalid@example.com', self::USER_PASS );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_email_password_with_invalid_password() {
		$result = wp_authenticate_email_password( null, self::USER_EMAIL, 'invalidpassword' );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_cookie_with_wp_user_object() {
		$result = wp_authenticate_cookie( $this->user, null, null );
		$this->assertSame( self::$user_id, $result->ID );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_cookie_with_null_params() {
		$result = wp_authenticate_cookie( null, null, null );
		$this->assertNull( $result );
	}

	/**
	 * @ticket 36476
	 */
	public function test_wp_authenticate_cookie_with_invalid_cookie() {
		$_COOKIE[ AUTH_COOKIE ]        = 'invalid_cookie';
		$_COOKIE[ SECURE_AUTH_COOKIE ] = 'secure_invalid_cookie';

		$result = wp_authenticate_cookie( null, null, null );
		$this->assertInstanceOf( 'WP_Error', $result );
	}

	/**
	 * @ticket 38744
	 */
	public function test_wp_signon_using_email_with_an_apostrophe() {
		$user_args = array(
			'user_email' => "mail\'@example.com",
			'user_pass'  => 'password',
		);
		self::factory()->user->create( $user_args );

		$_POST['log'] = $user_args['user_email'];
		$_POST['pwd'] = $user_args['user_pass'];
		$this->assertInstanceOf( 'WP_User', wp_signon() );
	}

	/**
	 * Tests that PHP 8.1 "passing null to non-nullable" deprecation notices
	 * are not thrown when `user_login` and `user_password` parameters are empty.
	 *
	 * The notices that we should not see:
	 * `Deprecated: preg_replace(): Passing null to parameter #3 ($subject) of type array|string is deprecated`.
	 * `Deprecated: trim(): Passing null to parameter #1 ($string) of type string is deprecated`.
	 *
	 * @ticket 56850
	 */
	public function test_wp_signon_does_not_throw_deprecation_notices_with_default_parameters() {
		$error = wp_signon();
		$this->assertWPError( $error, 'The result should be an instance of WP_Error.' );

		$error_codes = $error->get_error_codes();
		$this->assertContains( 'empty_username', $error_codes, 'The "empty_username" error code should be present.' );
		$this->assertContains( 'empty_password', $error_codes, 'The "empty_password" error code should be present.' );
	}

	/**
	 * HTTP Auth headers are used to determine the current user.
	 *
	 * @ticket 42790
	 *
	 * @covers ::wp_validate_application_password
	 */
	public function test_application_password_authentication() {
		$user_id = self::factory()->user->create(
			array(
				'user_login' => 'http_auth_login',
				'user_pass'  => 'http_auth_pass', // Shouldn't be allowed for API login.
			)
		);

		// Create a new app-only password.
		list( $user_app_password, $item ) = WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => 'phpunit' ) );

		// Fake a REST API request.
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		// Fake an HTTP Auth request with the regular account password first.
		$_SERVER['PHP_AUTH_USER'] = 'http_auth_login';
		$_SERVER['PHP_AUTH_PW']   = 'http_auth_pass';

		$this->assertNull(
			wp_validate_application_password( null ),
			'Regular user account password should not be allowed for API authentication'
		);
		$this->assertNull( rest_get_authenticated_app_password() );

		// Now try with an App password instead.
		$_SERVER['PHP_AUTH_PW'] = $user_app_password;

		$this->assertSame(
			$user_id,
			wp_validate_application_password( null ),
			'Application passwords should be allowed for API authentication'
		);
		$this->assertSame( $item['uuid'], rest_get_authenticated_app_password() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_respects_existing_user() {
		$user = wp_authenticate_application_password( self::$_user, self::$_user->user_login, 'password' );
		$this->assertNotWPError( $user );
		$this->assertSame( self::$_user, $user );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_is_rejected_if_not_api_request() {
		add_filter( 'application_password_is_api_request', '__return_false' );

		$user = wp_authenticate_application_password( null, self::$_user->user_login, 'password' );
		$this->assertNotWPError( $user );
		$this->assertNull( $user );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_invalid_username() {
		add_filter( 'application_password_is_api_request', '__return_true' );

		$error = wp_authenticate_application_password( null, 'idonotexist', 'password' );
		$this->assertWPError( $error );
		$this->assertSame( 'invalid_username', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_invalid_email() {
		add_filter( 'application_password_is_api_request', '__return_true' );

		$error = wp_authenticate_application_password( null, 'idonotexist@example.org', 'password' );
		$this->assertWPError( $error );
		$this->assertSame( 'invalid_email', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_not_allowed() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_false' );

		$error = wp_authenticate_application_password( null, self::$_user->user_login, 'password' );
		$this->assertWPError( $error );
		$this->assertSame( 'application_passwords_disabled', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_not_allowed_for_user() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
		add_filter( 'wp_is_application_passwords_available_for_user', '__return_false' );

		$error = wp_authenticate_application_password( null, self::$_user->user_login, 'password' );
		$this->assertWPError( $error );
		$this->assertSame( 'application_passwords_disabled_for_user', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_incorrect_password() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		$error = wp_authenticate_application_password( null, self::$_user->user_login, 'password' );
		$this->assertWPError( $error );
		$this->assertSame( 'incorrect_password', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_custom_errors() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		add_action(
			'wp_authenticate_application_password_errors',
			static function ( WP_Error $error ) {
				$error->add( 'my_code', 'My Error' );
			}
		);

		list( $password ) = WP_Application_Passwords::create_new_application_password( self::$user_id, array( 'name' => 'phpunit' ) );

		$error = wp_authenticate_application_password( null, self::$_user->user_login, $password );
		$this->assertWPError( $error );
		$this->assertSame( 'my_code', $error->get_error_code() );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_by_username() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		list( $password ) = WP_Application_Passwords::create_new_application_password( self::$user_id, array( 'name' => 'phpunit' ) );

		$user = wp_authenticate_application_password( null, self::$_user->user_login, $password );
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_by_email() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		list( $password ) = WP_Application_Passwords::create_new_application_password( self::$user_id, array( 'name' => 'phpunit' ) );

		$user = wp_authenticate_application_password( null, self::$_user->user_email, $password );
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @ticket 42790
	 */
	public function test_authenticate_application_password_chunked() {
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		list( $password ) = WP_Application_Passwords::create_new_application_password( self::$user_id, array( 'name' => 'phpunit' ) );

		$user = wp_authenticate_application_password( null, self::$_user->user_email, WP_Application_Passwords::chunk_password( $password ) );
		$this->assertNotWPError( $user );
		$this->assertInstanceOf( WP_User::class, $user );
		$this->assertSame( self::$user_id, $user->ID );
	}

	/**
	 * @ticket 51939
	 */
	public function test_authenticate_application_password_returns_null_if_not_in_use() {
		delete_site_option( 'using_application_passwords' );

		$authenticated = wp_authenticate_application_password( null, 'idonotexist', 'password' );
		$this->assertNotWPError( $authenticated );
		$this->assertNull( $authenticated );
	}

	/**
	 * @ticket 52003
	 *
	 * @covers ::wp_validate_application_password
	 */
	public function test_application_passwords_does_not_attempt_auth_if_missing_password() {
		WP_Application_Passwords::create_new_application_password( self::$user_id, array( 'name' => 'phpunit' ) );

		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		$_SERVER['PHP_AUTH_USER'] = self::$_user->user_login;
		unset( $_SERVER['PHP_AUTH_PW'] );

		$this->assertNull( wp_validate_application_password( null ) );
	}

	/**
	 * @ticket 53386
	 * @dataProvider data_application_passwords_can_use_capability_checks_to_determine_feature_availability
	 */
	public function test_application_passwords_can_use_capability_checks_to_determine_feature_availability( $role, $authenticated ) {
		$user = self::factory()->user->create_and_get( array( 'role' => $role ) );

		list( $password ) = WP_Application_Passwords::create_new_application_password( $user->ID, array( 'name' => 'phpunit' ) );

		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
		add_filter(
			'wp_is_application_passwords_available_for_user',
			static function ( $available, WP_User $user ) {
				return user_can( $user, 'edit_posts' );
			},
			10,
			2
		);

		$_SERVER['PHP_AUTH_USER'] = $user->user_login;
		$_SERVER['PHP_AUTH_PW']   = $password;

		unset( $GLOBALS['current_user'] );
		$current = get_current_user_id();

		if ( $authenticated ) {
			$this->assertSame( $user->ID, $current );
		} else {
			$this->assertSame( 0, $current );
		}
	}

	public function data_application_passwords_can_use_capability_checks_to_determine_feature_availability() {
		return array(
			'allowed'     => array( 'editor', true ),
			'not allowed' => array( 'subscriber', false ),
		);
	}

	/**
	 * Test that direct `wp_check_password()` use with old password retains login password
	 *
	 * @see GitHub Issue #1585
	 */
	public function test_login_password_not_changed_on_direct_wp_check_password_use() {
		$user_details = array(
			'user_login' => 'user_name',
			'user_pass'  => 'user_password',
		);

		// Create author user and add capabilities
		$user_id = self::factory()->user->create( $user_details );

		$user_obj = get_user_by( 'id', $user_id );
		wp_set_current_user( 0 );

		// Use the password and ensure it works
		$this->assertTrue( wp_check_password( $user_details['user_pass'], $user_obj->data->user_pass ) );
		$auth_user = wp_authenticate_username_password( $user_obj, $user_details['user_login'], $user_details['user_pass'] );
		$this->assertNotInstanceOf( 'WP_Error', $auth_user );

		// Simulate plugin use of `wp_check_password()`
		$plugin_password = wp_generate_password();
		$plugin_password_hash = self::$wp_hasher->HashPassword( $plugin_password );

		$plugin_check = wp_check_password( $plugin_password, $plugin_password_hash, $user_id );
		$this->assertTrue( $plugin_check );
		$plugin_user_obj = get_user_by( 'id', $user_id );
		$this->assertSame( $user_obj->data->user_pass, $plugin_user_obj->data->user_pass );
		$this->assertNotSame( $user_obj->data->user_pass, $plugin_password_hash );
		$this->assertNotSame( $plugin_user_obj->data->user_pass, $plugin_password_hash );
	}

	/**
	 * Test that use of old Application password retains login password
	 *
	 * @covers Tests_Auth::set_application_password_with_phpass
	 *
	 * @see GitHub Issue #1585
	 */
	public function test_login_password_not_changed_on_old_application_password_use() {
		$user_details = array(
			'user_login' => 'user_name',
			'user_pass'  => 'user_password',
		);

		// Create author user and add capabilities
		$user_id = self::factory()->user->create( $user_details );

		$user_obj = get_user_by( 'id', $user_id );

		// Set an application password with the old phpass algorithm.
		$uuid = self::set_application_password_with_phpass( 'password', $user_id );

		$hash = WP_Application_Passwords::get_user_application_password( $user_id, $uuid )['password'];

		wp_set_current_user( 0 );

		// Fake a REST API request.
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		$auth_user = wp_authenticate_application_password( null, $user_details['user_login'], 'password' );

		$after_auth_user = get_user_by( 'id', $user_id );

		$this->assertSame( $user_id, $auth_user->ID );
		$this->assertSame( $user_id, $after_auth_user->ID );
		$this->assertSame( $user_obj->data->user_pass, $auth_user->data->user_pass );
		$this->assertSame( $user_obj->data->user_pass, $after_auth_user->data->user_pass );
	}

	/**
	 * Test the tests
	 *
	 * @covers Tests_Auth::set_user_password_with_phpass
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_set_user_password_with_phpass() {
		// Set the user password with the old phpass algorithm.
		self::set_user_password_with_phpass( 'password', self::$user_id );

		// Ensure the password is hashed with phpass.
		$hash = get_userdata( self::$user_id )->user_pass;
		$this->assertStringStartsWith( '$P$', $hash );
	}

	private static function set_user_password_with_phpass( string $password, int $user_id ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->users,
			array(
				'user_pass' => self::$wp_hasher->HashPassword( $password ),
			),
			array(
				'ID' => $user_id,
			)
		);
		clean_user_cache( $user_id );
	}

	/**
	 * Test the tests
	 *
	 * @covers Tests_Auth::set_application_password_with_phpass
	 *
	 * @ticket 21022
	 * @ticket 50027
	 */
	public function test_set_application_password_with_phpass() {
		// Set an application password with the old phpass algorithm.
		$uuid = self::set_application_password_with_phpass( 'password', self::$user_id );

		// Ensure the password is hashed with phpass.
		$hash = WP_Application_Passwords::get_user_application_password( self::$user_id, $uuid )['password'];
		$this->assertStringStartsWith( '$P$', $hash );
	}

	private static function set_application_password_with_phpass( string $password, int $user_id ) {
		$uuid = wp_generate_uuid4();
		$item = array(
			'uuid'      => $uuid,
			'app_id'    => '',
			'name'      => 'Test',
			'password'  => self::$wp_hasher->HashPassword( $password ),
			'created'   => time(),
			'last_used' => null,
			'last_ip'   => null,
		);

		$saved = update_user_meta(
			$user_id,
			WP_Application_Passwords::USERMETA_KEY_APPLICATION_PASSWORDS,
			array( $item )
		);

		if ( ! $saved ) {
			throw new Exception( 'Could not save application password.' );
		}

		update_network_option( get_main_network_id(), WP_Application_Passwords::OPTION_KEY_IN_USE, true );

		return $uuid;
	}

	private static function set_default_bcrypt() {
		return PASSWORD_BCRYPT;
	}
}
