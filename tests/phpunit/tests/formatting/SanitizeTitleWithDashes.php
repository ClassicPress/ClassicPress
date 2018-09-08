<?php

/**
 * @group formatting
 */
class Tests_Formatting_SanitizeTitleWithDashes extends WP_UnitTestCase {
	function test_strips_html() {
		$input = "Captain <strong>Awesome</strong>";
		$expected = "captain-awesome";
		$this->assertEquals($expected, sanitize_title($input));
	}

	function test_strips_unencoded_percent_signs() {
		$this->assertEquals("fran%c3%a7ois", sanitize_title_with_dashes("fran%c3%a7%ois"));
	}

	function test_makes_title_lowercase() {
		$this->assertEquals("abc", sanitize_title_with_dashes("ABC"));
	}

	function test_replaces_any_amount_of_whitespace_with_one_hyphen() {
		$this->assertEquals("a-t", sanitize_title_with_dashes("a          t"));
		$this->assertEquals("a-t", sanitize_title_with_dashes("a    \n\n\nt"));
	}

	function test_replaces_any_number_of_hyphens_with_one_hyphen() {
		$this->assertEquals("a-t-t", sanitize_title_with_dashes("a----t----t"));
	}

	function test_trims_trailing_hyphens() {
		$this->assertEquals("a-t-t", sanitize_title_with_dashes("a----t----t----"));
	}

	function test_handles_non_entity_ampersands() {
		$this->assertEquals("penn-teller-bull", sanitize_title_with_dashes("penn & teller bull"));
	}

	public function test_strips_nbsp_ndash_and_amp() {
		$this->assertEquals("no-entities-here", sanitize_title_with_dashes("No &nbsp; Entities &ndash; Here &amp;"));
	}

	public function test_strips_encoded_ampersand() {
		$this->assertEquals("one-two", sanitize_title_with_dashes("One &amp; Two", '', 'save'));
	}

	public function test_strips_url_encoded_ampersand() {
		$this->assertEquals("one-two", sanitize_title_with_dashes("One &#123; Two;", '', 'save'));
	}

	public function test_strips_trademark_symbol() {
		$this->assertEquals("one-two", sanitize_title_with_dashes("One Two™;", '', 'save'));
	}

	public function test_strips_unencoded_ampersand_followed_by_encoded_ampersand() {
		$this->assertEquals("one-two", sanitize_title_with_dashes("One &&amp; Two;", '', 'save'));
	}

	public function test_strips_unencoded_ampersand_when_not_surrounded_by_spaces() {
		$this->assertEquals("onetwo", sanitize_title_with_dashes("One&Two", '', 'save'));
	}

	function test_replaces_nbsp() {
		$this->assertEquals("dont-break-the-space", sanitize_title_with_dashes("don't break the space", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31790
	 */
	function test_replaces_nbsp_entities() {
		$this->assertEquals("dont-break-the-space", sanitize_title_with_dashes("don't&nbsp;break&#160;the&nbsp;space", '', 'save'));
	}

	function test_replaces_ndash_mdash() {
		$this->assertEquals("do-the-dash", sanitize_title_with_dashes("Do – the Dash", '', 'save'));
		$this->assertEquals("do-the-dash", sanitize_title_with_dashes("Do the — Dash", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31790
	 */
	function test_replaces_ndash_mdash_entities() {
		$this->assertEquals("do-the-dash", sanitize_title_with_dashes("Do &ndash; the &#8211; Dash", '', 'save'));
		$this->assertEquals("do-the-dash", sanitize_title_with_dashes("Do &mdash; the &#8212; Dash", '', 'save'));
	}

	function test_replaces_iexcel_iquest() {
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("Just ¡a Slug", '', 'save'));
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("Just a Slug¿", '', 'save'));
	}

	function test_replaces_angle_quotes() {
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("‹Just a Slug›", '', 'save'));
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("«Just a Slug»", '', 'save'));
	}

	function test_replaces_curly_quotes() {
		$this->assertEquals("hey-its-curly-joe", sanitize_title_with_dashes("Hey its “Curly Joe”", '', 'save'));
		$this->assertEquals("hey-its-curly-joe", sanitize_title_with_dashes("Hey its ‘Curly Joe’", '', 'save'));
		$this->assertEquals("hey-its-curly-joe", sanitize_title_with_dashes("Hey its „Curly Joe“", '', 'save'));
		$this->assertEquals("hey-its-curly-joe", sanitize_title_with_dashes("Hey its ‚Curly Joe‛", '', 'save'));
		$this->assertEquals("hey-its-curly-joe", sanitize_title_with_dashes("Hey its „Curly Joe‟", '', 'save'));
	}

	function test_replaces_copy_reg_deg_trade() {
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("Just © a Slug", '', 'save'));
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("® Just a Slug", '', 'save'));
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("Just a ° Slug", '', 'save'));
		$this->assertEquals("just-a-slug", sanitize_title_with_dashes("Just ™ a Slug", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/10792
	 */
	function test_replaces_forward_slash() {
		$this->assertEquals("songs-by-lennon-mccartney", sanitize_title_with_dashes("songs by Lennon/McCartney", '', 'save'));
		$this->assertEquals("songs-by-lennon-mccartney", sanitize_title_with_dashes("songs by Lennon//McCartney", '', 'save'));
		$this->assertEquals("songs-by-lennon-mccartney", sanitize_title_with_dashes("songs by Lennon///McCartney", '', 'save'));
		$this->assertEquals("songs-by-lennon-mccartney", sanitize_title_with_dashes("songs by Lennon/-McCartney", '', 'save'));
		$this->assertEquals("songs-by-lennon-mccartney", sanitize_title_with_dashes("//songs by Lennon/McCartney", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/19820
	 */
	function test_replaces_multiply_sign() {
		$this->assertEquals("6x7-is-42", sanitize_title_with_dashes("6×7 is 42", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/20772
	 */
	function test_replaces_standalone_diacritic() {
		$this->assertEquals("aaaa", sanitize_title_with_dashes("āáǎà", '', 'save'));
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22395
	 */
	function test_replaces_acute_accents() {
		$this->assertEquals("aaaa", sanitize_title_with_dashes("ááa´aˊ", '', 'save'));
	}

}
