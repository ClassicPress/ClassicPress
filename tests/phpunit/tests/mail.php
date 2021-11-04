<?php
/**
 * @group pluggable
 * @group mail
 */
class Tests_Mail extends WP_UnitTestCase {
	function setUp() {
		parent::setUp();
		reset_phpmailer_instance();
	}

	function tearDown() {
		reset_phpmailer_instance();
		parent::tearDown();
	}

	/**
	 * Send a mail with a 1000 char long line.
	 *
	 * `PHPMailer::createBody()` will set `$this->Encoding = 'quoted-printable'` (away from its default of 8bit)
	 * when it encounters a line longer than 999 characters. But PHPMailer doesn't clean up after itself / presets
	 * all variables, which means that following tests would fail. To solve this issue we set `$this->Encoding`
	 * back to 8bit in `MockPHPMailer::preSend`.
	 *
	 */
	function test_wp_mail_break_it() {
		$content = str_repeat( 'A', 1000 );
		$this->assertTrue( wp_mail( WP_TESTS_EMAIL, 'Looong line testing', $content ) );
	}

	function test_wp_mail_custom_boundaries() {
		$to = 'user@example.com';
		$subject = 'Test email with custom boundaries';
		$headers  = '' . "\n";
		$headers .= 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-Type: multipart/mixed; boundary="----=_Part_4892_25692638.1192452070893"' . "\n";
		$headers .= "\n";
		$body  = "\n";
		$body .= '------=_Part_4892_25692638.1192452070893' . "\n";
		$body .= 'Content-Type: text/plain; charset=ISO-8859-1' . "\n";
		$body .= 'Content-Transfer-Encoding: 7bit' . "\n";
		$body .= 'Content-Disposition: inline' . "\n";
		$body .= "\n";
		$body .= 'Here is a message with an attachment of a binary file.' . "\n";
		$body .= "\n";
		$body .= '------=_Part_4892_25692638.1192452070893' . "\n";
		$body .= 'Content-Type: image/x-icon; name=favicon.ico' . "\n";
		$body .= 'Content-Transfer-Encoding: base64' . "\n";
		$body .= 'Content-Disposition: attachment; filename=favicon.ico' . "\n";
		$body .= "\n";
		$body .= 'AAABAAEAEBAAAAAAAABoBQAAFgAAACgAAAAQAAAAIAAAAAEACAAAAAAAAAAAAAAAAAAAAAAAAAAA' . "\n";
		$body .= 'AAAAAAAAAAAAAACAAACAAAAAgIAAgAAAAIAAgACAgAAAwMDAAICAgAAAAP8AAP8AAAD//wD/AAAA' . "\n";
		$body .= '/wD/AP//AAD///8A//3/AP39/wD6/f8A+P3/AP/8/wD9/P8A+vz/AP/7/wD/+v8A/vr/APz6/wD4' . "\n";
		$body .= '+v8A+/n/APP5/wD/+P8A+vj/AO/4/wDm+P8A2fj/AP/3/wD/9v8A9vb/AP/1/wD69f8A9PT/AO30' . "\n";
		$body .= '/wD/8/8A//L/APnx/wD28P8A///+APj//gD2//4A9P/+AOP//gD//f4A6f/9AP///AD2//wA8//8' . "\n";
		$body .= 'APf9/AD///sA/v/7AOD/+wD/+vsA9/X7APr/+gDv/voA///5AP/9+QD/+/kA+e35AP//+ADm//gA' . "\n";
		$body .= '4f/4AP/9+AD0+/gA///3APv/9wDz//cA8f/3AO3/9wD/8fcA//32AP369gDr+vYA8f/1AOv/9QD/' . "\n";
		$body .= '+/UA///0APP/9ADq//QA///zAP/18wD///IA/fzyAP//8QD///AA9//wAPjw8AD//+8A8//vAP//' . "\n";
		$body .= '7gD9/+4A9v/uAP/u7gD//+0A9v/tAP7/6wD/+eoA///pAP//6AD2/+gA//nnAP/45wD38eYA/fbl' . "\n";
		$body .= 'AP/25AD29uQA7N/hAPzm4AD/690AEhjdAAAa3AAaJdsA//LXAC8g1gANH9YA+dnTAP/n0gDh5dIA' . "\n";
		$body .= 'DyjSABkk0gAdH9EABxDRAP/l0AAAJs4AGRTOAPPczQAAKs0AIi7MAA4UywD56soA8tPKANTSygD/' . "\n";
		$body .= '18kA6NLHAAAjxwDj28QA/s7CAP/1wQDw3r8A/9e8APrSrwDCtqoAzamjANmPiQDQj4YA35mBAOme' . "\n";
		$body .= 'fgDHj3wA1qR6AO+sbwDpmm8A2IVlAKmEYgCvaFoAvHNXAEq2VgA5s1UAPbhQAFWtTwBStU0ARbNN' . "\n";
		$body .= 'AEGxTQA7tEwAObZIAEq5RwDKdEYAULhDANtuQgBEtTwA1ls3ALhgMQCxNzEA2FsvAEC3LQB0MCkA' . "\n";
		$body .= 'iyYoANZTJwDLWyYAtjMlALE6JACZNSMAuW4iANlgIgDoWCEAylwgAMUuIAD3Vh8A52gdALRCHQCx' . "\n";
		$body .= 'WhwAsEkcALU4HACMOBwA0V4bAMYyGgCPJRoA218ZAJM7FwC/PxYA0msVAM9jFQD2XBUAqioVAIAf' . "\n";
		$body .= 'FQDhYRQAujMTAMUxEwCgLBMAnxIPAMsqDgCkFgsA6GMHALE2BAC9JQAAliIAAFYTAAAAAAAAAAAA' . "\n";
		$body .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' . "\n";
		$body .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/' . "\n";
		$body .= '//8AsbGxsbGxsbGxsbGxsbGxd7IrMg8PDw8PDw8PUBQeJXjQYE9PcKPM2NfP2sWhcg+BzTE7dLjb' . "\n";
		$body .= 'mG03YWaV4JYye8MPbsLZlEouKRRCg9SXMoW/U53enGRAFzCRtNO7mTiAyliw30gRTg9VbJCKfYs0' . "\n";
		$body .= 'j9VmuscfLTFbIy8SOhA0Inq5Y77GNBMYIxQUJzM2Vxx2wEmfyCYWMRldXCg5MU0aicRUms58SUVe' . "\n";
		$body .= 'RkwjPBRSNIfBMkSgvWkyPxVHFIaMSx1/0S9nkq7WdWo1a43Jt2UqgtJERGJ5m6K8y92znpNWIYS1' . "\n";
		$body .= 'UQ89Mmg5cXNaX0EkGyyI3KSsp6mvpaqosaatq7axsQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' . "\n";
		$body .= 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' . "\n";
		$body .= '------=_Part_4892_25692638.1192452070893--' . "\n";
		$body .= "\n";

		wp_mail( $to, $subject, $body, $headers );

		$mailer = tests_retrieve_phpmailer_instance();

		// We need some better assertions here but these catch the failure for now.
		$this->assertEqualsIgnoreEOL( $body, $mailer->get_sent()->body );
		$this->assertTrue( strpos( iconv_mime_decode_headers( ( $mailer->get_sent()->header ) )['Content-Type'][0], 'boundary="----=_Part_4892_25692638.1192452070893"' ) > 0 );
		$this->assertTrue( strpos( $mailer->get_sent()->header, 'charset=' ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/17305
	 */
	function test_wp_mail_rfc2822_addresses() {
		$to        = 'Name <address@tld.com>';
		$from      = 'Another Name <another_address@different-tld.com>';
		$cc        = 'The Carbon Guy <cc@cc.com>';
		$bcc       = 'The Blind Carbon Guy <bcc@bcc.com>';
		$subject   = 'RFC2822 Testing';
		$message   = 'My RFC822 Test Message';
		$headers[] = "From: {$from}";
		$headers[] = "CC: {$cc}";
		$headers[] = "BCC: {$bcc}";

		wp_mail( $to, $subject, $message, $headers );

		// WordPress 3.2 and later correctly split the address into the two parts and send them seperately to PHPMailer
		// Earlier versions of PHPMailer were not touchy about the formatting of these arguments.

		//retrieve the mailer instance
		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( 'address@tld.com',        $mailer->get_recipient( 'to' )->address );
		$this->assertEquals( 'Name',                   $mailer->get_recipient( 'to' )->name );
		$this->assertEquals( 'cc@cc.com',              $mailer->get_recipient( 'cc' )->address );
		$this->assertEquals( 'The Carbon Guy',         $mailer->get_recipient( 'cc' )->name );
		$this->assertEquals( 'bcc@bcc.com',            $mailer->get_recipient( 'bcc' )->address );
		$this->assertEquals( 'The Blind Carbon Guy',   $mailer->get_recipient( 'bcc' )->name );
		$this->assertEqualsIgnoreEOL( $message . "\n", $mailer->get_sent()->body );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/17305
	 */
	function test_wp_mail_multiple_rfc2822_to_addresses() {
		$to      = 'Name <address@tld.com>, Another Name <another_address@different-tld.com>';
		$subject = 'RFC2822 Testing';
		$message = 'My RFC822 Test Message';

		wp_mail( $to, $subject, $message );

		// WordPress 3.2 and later correctly split the address into the two parts and send them seperately to PHPMailer
		// Earlier versions of PHPMailer were not touchy about the formatting of these arguments.
		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( 'address@tld.com',                   $mailer->get_recipient( 'to' )->address );
		$this->assertEquals( 'Name',                              $mailer->get_recipient( 'to' )->name );
		$this->assertEquals( 'another_address@different-tld.com', $mailer->get_recipient( 'to', 0, 1 )->address );
		$this->assertEquals( 'Another Name',                      $mailer->get_recipient( 'to', 0, 1 )->name );
		$this->assertEqualsIgnoreEOL( $message . "\n",            $mailer->get_sent()->body );
	}

	function test_wp_mail_multiple_to_addresses() {
		$to      = 'address@tld.com, another_address@different-tld.com';
		$subject = 'RFC2822 Testing';
		$message = 'My RFC822 Test Message';

		wp_mail( $to, $subject, $message );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( 'address@tld.com',                   $mailer->get_recipient( 'to' )->address );
		$this->assertEquals( 'another_address@different-tld.com', $mailer->get_recipient( 'to', 0, 1 )->address );
		$this->assertEqualsIgnoreEOL( $message . "\n",            $mailer->get_sent()->body );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/18463
	 */
	function test_wp_mail_to_address_no_name() {
		$to      = '<address@tld.com>';
		$subject = 'RFC2822 Testing';
		$message = 'My RFC822 Test Message';

		wp_mail( $to, $subject, $message );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( 'address@tld.com',        $mailer->get_recipient( 'to' )->address );
		$this->assertEqualsIgnoreEOL( $message . "\n", $mailer->get_sent()->body );

	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/23642
	 */
	function test_wp_mail_return_value() {
		// No errors
		$this->assertTrue( wp_mail( 'valid@address.com', 'subject', 'body' ) );

		// Non-fatal errors
		$this->assertTrue( wp_mail( 'valid@address.com', 'subject', 'body', "Cc: invalid-address\nBcc: @invalid.address", ABSPATH . '/non-existant-file.html' ) );

		// Fatal errors
		$this->assertFalse( wp_mail( 'invalid.address', 'subject', 'body', '', array() ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_valid_from_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'From: Foo <bar@example.com>';
		$expected = 'From: Foo <bar@example.com>';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_empty_from_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'From: ';
		$expected = 'From: ClassicPress <classicpress@' . WP_TESTS_DOMAIN . '>';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_empty_from_name_for_the_from_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'From: <classicpress@example.com>';
		$expected = 'From: ClassicPress <classicpress@example.com>';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_valid_content_type_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'Content-Type: text/html; charset=iso-8859-1';
		$expected = 'Content-Type: text/html; charset=iso-8859-1';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_empty_content_type_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'Content-Type: ';
		$expected = 'Content-Type: text/plain; charset=UTF-8';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/30266
	 */
	public function test_wp_mail_with_empty_charset_for_the_content_type_header() {
		$to       = 'address@tld.com';
		$subject  = 'Testing';
		$message  = 'Test Message';
		$headers  = 'Content-Type: text/plain;';
		$expected = 'Content-Type: text/plain; charset=UTF-8';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertTrue( strpos( $mailer->get_sent()->header, $expected ) > 0 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/43542
	 */
	public function test_wp_mail_does_not_duplicate_mime_version_header() {
		$to       = 'user@example.com';
		$subject  = 'Test email with a MIME-Version header';
		$message  = 'The MIME-Version header should not be duplicated.';
		$headers  = 'MIME-Version: 1.0';
		$expected = 'MIME-Version: 1.0';

		wp_mail( $to, $subject, $message, $headers );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertEquals( 1, substr_count( $mailer->get_sent()->header, $expected ) );
	}

	function wp_mail_quoted_printable( $mailer ) {
		$mailer->Encoding = 'quoted-printable';
	}

	function wp_mail_set_text_message( $mailer ) {
		$mailer->AltBody = 'Wörld';
	}

	/**
	 * > If an entity is of type "multipart" the Content-Transfer-Encoding is
	 * > not permitted to have any value other than "7bit", "8bit" or
	 * > "binary".
	 * https://tools.ietf.org/html/rfc2045#section-6.4
	 *
	 * > "Content-Transfer-Encoding: 7BIT" is assumed if the
	 * > Content-Transfer-Encoding header field is not present.
	 * https://tools.ietf.org/html/rfc2045#section-6.1
	 *
	 * @see https://core.trac.wordpress.org/ticket/28039
	 */
	function test_wp_mail_content_transfer_encoding_in_quoted_printable_multipart() {
		add_action( 'phpmailer_init', array( $this, 'wp_mail_quoted_printable' ) );
		add_action( 'phpmailer_init', array( $this, 'wp_mail_set_text_message' ) );

		wp_mail(
			'user@example.com',
			'Hello',
			'<p><strong>Wörld</strong></p>',
			'Content-Type: text/html'
		);

		$this->assertNotContains( 'quoted-printable', $GLOBALS['phpmailer']->mock_sent[0]['header'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/21659
	 */
	public function test_wp_mail_addresses_arent_encoded() {
		$to      = 'Lukáš To <to@example.org>';
		$subject = 'Testing https://core.trac.wordpress.org/ticket/21659';
		$message = 'Only the name should be encoded, not the address.';

		$headers = array(
			'From'     => 'From: Lukáš From <from@example.org>',
			'Cc'       => 'Cc: Lukáš CC <cc@example.org>',
			'Bcc'      => 'Bcc: Lukáš BCC <bcc@example.org>',
			'Reply-To' => 'Reply-To: Lukáš Reply-To <reply_to@example.org>',
		);

		$expected = array(
			'To'       => 'To: =?UTF-8?B?THVrw6HFoSBUbw==?= <to@example.org>',
			'From'     => 'From: =?UTF-8?Q?Luk=C3=A1=C5=A1_From?= <from@example.org>',
			'Cc'       => 'Cc: =?UTF-8?B?THVrw6HFoSBDQw==?= <cc@example.org>',
			'Bcc'      => 'Bcc: =?UTF-8?B?THVrw6HFoSBCQ0M=?= <bcc@example.org>',
			'Reply-To' => 'Reply-To: =?UTF-8?Q?Luk=C3=A1=C5=A1_Reply-To?= <reply_to@example.org>',
		);

		wp_mail( $to, $subject, $message, array_values( $headers ) );

		$mailer        = tests_retrieve_phpmailer_instance();
		$sent_headers  = preg_split( "/\r\n|\n|\r/", $mailer->get_sent()->header );
		$headers['To'] = "To: $to";

		foreach ( $headers as $header => $value ) {
			$target_headers = preg_grep( "/^$header:/", $sent_headers );
			$this->assertEquals( $expected[ $header ], array_pop( $target_headers ) );
		}
	}

	/**
	 * Test that the Sender field in the SMTP envelope is not set by Core.
	 *
	 * Correctly setting the Sender requires knowledge that is not available
	 * to Core. An incorrect value will often lead to messages being rejected
	 * by the receiving MTA, so it's the admin's responsibility to
	 * set it correctly.
	 *
	 * @see https://core.trac.wordpress.org/ticket/37736
	 */
	public function test_wp_mail_sender_not_set() {
		wp_mail( 'user@example.org', 'Testing the Sender field', 'The Sender field should not have been set.' );

		$mailer = tests_retrieve_phpmailer_instance();

		$this->assertEquals( '', $mailer->Sender );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35598
	 */
	public function test_phpmailer_exception_thrown() {
		$to       = 'an_invalid_address';
		$subject  = 'Testing';
		$message  = 'Test Message';

		$ma = new MockAction();
		add_action( 'wp_mail_failed', array( &$ma, 'action' ) );

		wp_mail( $to, $subject, $message );

		$this->assertEquals( 1, $ma->get_call_count() );

		$expected_error_data = array(
			'to'          => array( 'an_invalid_address' ),
			'subject'     => 'Testing',
			'message'     => 'Test Message',
			'headers'     => array(),
			'attachments' => array(),
			'phpmailer_exception_code' => 2,
		);

		//Retrieve the arguments passed to the 'wp_mail_failed' hook callbacks
		$all_args = $ma->get_args();
		$call_args = array_pop( $all_args );

		$this->assertEquals( 'wp_mail_failed', $call_args[0]->get_error_code() );
		$this->assertEquals( $expected_error_data, $call_args[0]->get_error_data() );
	}

	/**
	 * Test for short-circuiting wp_mail().
	 *
	 * @see https://core.trac.wordpress.org/ticket/35069
	 */
	public function test_wp_mail_can_be_shortcircuited() {
		$result1 = wp_mail( WP_TESTS_EMAIL, 'Foo', 'Bar' );

		add_filter( 'pre_wp_mail', '__return_false' );
		$result2 = wp_mail( WP_TESTS_EMAIL, 'Foo', 'Bar' );
		remove_filter( 'pre_wp_mail', '__return_false' );

		$this->assertTrue( $result1 );
		$this->assertFalse( $result2 );
	}
}
