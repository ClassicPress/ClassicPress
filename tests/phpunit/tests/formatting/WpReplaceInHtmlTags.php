<?php

/**
 * @group formatting
 */
class Tests_Formatting_WpReplaceInTags extends WP_UnitTestCase {
	/**
	 * Check for expected behavior of new function wp_replace_in_html_tags().
	 *
	 * @dataProvider data_wp_replace_in_html_tags
	 */
	function test_wp_replace_in_html_tags( $input, $output ) {
<<<<<<< HEAD
		return $this->assertEquals( $output, wp_replace_in_html_tags( $input, array( "\n" => " " ) ) );
=======
		return $this->assertSame( $output, wp_replace_in_html_tags( $input, array( "\n" => ' ' ) ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function data_wp_replace_in_html_tags() {
		return array(
			array(
				"Hello \n World",
				"Hello \n World",
			),
			array(
				"<Hello \n World>",
				"<Hello   World>",
			),
			array(
				"<!-- Hello \n World -->",
				"<!-- Hello   World -->",
			),
			array(
				"<!-- Hello <\n> World -->",
				"<!-- Hello < > World -->",
			),
		);
	}
}
?>