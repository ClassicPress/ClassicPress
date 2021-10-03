<?php

/**
 * @group formatting
 */
class Tests_Formatting_LikeEscape extends WP_UnitTestCase {
	/**
	 * @see https://core.trac.wordpress.org/ticket/10041
	 * @expectedDeprecated like_escape
	 */
	function test_like_escape() {

		$inputs = array(
			'howdy%', //Single Percent
			'howdy_', //Single Underscore
			'howdy\\', //Single slash
			'howdy\\howdy%howdy_', //The works
		);
		$expected = array(
			"howdy\\%",
			'howdy\\_',
			'howdy\\',
			'howdy\\howdy\\%howdy\\_'
		);

<<<<<<< HEAD
		foreach ($inputs as $key => $input) {
			$this->assertEquals($expected[$key], like_escape($input));
=======
		foreach ( $inputs as $key => $input ) {
			$this->assertSame( $expected[ $key ], like_escape( $input ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		}
	}
}
