<?php

require_once dirname( dirname( __FILE__ ) ) . '/canonical.php';

/**
 * @group canonical
 * @group rewrite
 * @group query
 */
class Tests_Canonical_NoRewrite extends WP_Canonical_UnitTestCase {

	// These test cases are run against the test handler in WP_Canonical

	public function set_up() {
		global $wp_rewrite;

		parent::set_up();

		$wp_rewrite->init();
		$wp_rewrite->set_permalink_structure( '' );
		$wp_rewrite->flush_rules();
	}

	/**
	 * @dataProvider data
	 */
	function test( $test_url, $expected ) {
		$this->assertCanonical( $test_url, $expected );
	}

	function data() {
		/* Format:
		 * [0]: $test_url,
		 * [1]: expected results: Any of the following can be used
		 *      array( 'url': expected redirection location, 'qv': expected query vars to be set via the rewrite AND $_GET );
		 *      array( expected query vars to be set, same as 'qv' above )
		 *      (string) expected redirect location
		 */
		return array(
			array( '/?p=123', '/?p=123' ),

			// This post_type arg should be stripped, because p=1 exists, and does not have post_type= in its query string
			array( '/?post_type=fake-cpt&p=1', '/?p=1' ),

			// Strip an existing but incorrect post_type arg
			array( '/?post_type=page&page_id=1', '/?p=1' ),

			// Trailing spaces and punctuation in query string args.
			array( '/?p=358 ',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // space
			array( '/?p=358%20',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded space
			array( '/?p=358!',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // exclamation mark
			array( '/?p=358%21',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded exclamation mark
			array( '/?p=358"',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // double quote
			array( '/?p=358%22',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded double quote
			array( '/?p=358\'',         array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // single quote
			array( '/?p=358%27',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded single quote
			array( '/?p=358(',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // opening bracket
			array( '/?p=358%28',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded opening bracket
			array( '/?p=358)',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // closing bracket
			array( '/?p=358%29',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded closing bracket
			array( '/?p=358,',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // comma
			array( '/?p=358%2C',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded comma
			array( '/?p=358.',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // period
			array( '/?p=358%2E',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded period
			array( '/?p=358;',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // semicolon
			array( '/?p=358%3B',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded semicolon
			array( '/?p=358{',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // opening curly bracket
			array( '/?p=358%7B',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded opening curly bracket
			array( '/?p=358}',          array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // closing curly bracket
			array( '/?p=358%7D',        array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded closing curly bracket
			array( '/?p=358%E2%80%9C',  array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded opening curly quote
			array( '/?p=358%E2%80%9D',  array( 'url' => '/?p=358', 'qv' => array( 'p' => '358' ) ) ), // encoded closing curly quote

			// Trailing spaces and punctuation in permalinks.
			array( '/page/2/ ',         '/page/2/' ), // space
			array( '/page/2/%20',       '/page/2/' ), // encoded space
			array( '/page/2/!',         '/page/2/' ), // exclamation mark
			array( '/page/2/%21',       '/page/2/' ), // encoded exclamation mark
			array( '/page/2/"',         '/page/2/' ), // double quote
			array( '/page/2/%22',       '/page/2/' ), // encoded double quote
			array( '/page/2/\'',        '/page/2/' ), // single quote
			array( '/page/2/%27',       '/page/2/' ), // encoded single quote
			array( '/page/2/(',         '/page/2/' ), // opening bracket
			array( '/page/2/%28',       '/page/2/' ), // encoded opening bracket
			array( '/page/2/)',         '/page/2/' ), // closing bracket
			array( '/page/2/%29',       '/page/2/' ), // encoded closing bracket
			array( '/page/2/,',         '/page/2/' ), // comma
			array( '/page/2/%2C',       '/page/2/' ), // encoded comma
			array( '/page/2/.',         '/page/2/' ), // period
			array( '/page/2/%2E',       '/page/2/' ), // encoded period
			array( '/page/2/;',         '/page/2/' ), // semicolon
			array( '/page/2/%3B',       '/page/2/' ), // encoded semicolon
			array( '/page/2/{',         '/page/2/' ), // opening curly bracket
			array( '/page/2/%7B',       '/page/2/' ), // encoded opening curly bracket
			array( '/page/2/}',         '/page/2/' ), // closing curly bracket
			array( '/page/2/%7D',       '/page/2/' ), // encoded closing curly bracket
			array( '/page/2/%E2%80%9C', '/page/2/' ), // encoded opening curly quote
			array( '/page/2/%E2%80%9D', '/page/2/' ), // encoded closing curly quote

			array( '/?page_id=1', '/?p=1' ), // redirect page_id to p (should cover page_id|p|attachment_id to one another
			array( '/?page_id=1&post_type=revision', '/?p=1' ),

			array( '/?feed=rss2&p=1', '/?feed=rss2&p=1' ),
			array( '/?feed=rss&p=1', '/?feed=rss2&p=1' ),
		);
	}
}
