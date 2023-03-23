<?php

/**
 * @group formatting
 *
 * @covers ::_wp_specialchars
 */
class Tests_Formatting_wpSpecialchars extends WP_UnitTestCase {
	public function test_wp_specialchars_basics() {
		$html = '&amp;&lt;hello world&gt;';
		$this->assertSame( $html, _wp_specialchars( $html ) );

		$double = '&amp;amp;&amp;lt;hello world&amp;gt;';
		$this->assertSame( $double, _wp_specialchars( $html, ENT_NOQUOTES, false, true ) );
	}

	public function test_allowed_entity_names() {
		global $allowedentitynames;

		// Allowed entities should be unchanged.
		foreach ( $allowedentitynames as $ent ) {
			if ( 'apos' === $ent ) {
				// But for some reason, PHP doesn't allow &apos;
				continue;
			}
			$ent = '&' . $ent . ';';
			$this->assertSame( $ent, _wp_specialchars( $ent ) );
		}
	}

	public function test_not_allowed_entity_names() {
		$ents = array( 'iacut', 'aposs', 'pos', 'apo', 'apo?', 'apo.*', '.*apo.*', 'apos ', ' apos', ' apos ' );

		foreach ( $ents as $ent ) {
			$escaped = '&amp;' . $ent . ';';
			$ent     = '&' . $ent . ';';
			$this->assertSame( $escaped, _wp_specialchars( $ent ) );
		}
	}

	public function test_optionally_escapes_quotes() {
		$source = "\"'hello!'\"";
		$this->assertSame( '"&#039;hello!&#039;"', _wp_specialchars( $source, 'single' ) );
		$this->assertSame( "&quot;'hello!'&quot;", _wp_specialchars( $source, 'double' ) );
		$this->assertSame( '&quot;&#039;hello!&#039;&quot;', _wp_specialchars( $source, true ) );
		$this->assertSame( $source, _wp_specialchars( $source ) );
	}

	/**
	 * Check some of the double-encoding features for entity references.
	 *
	 * @ticket 17780
	 * @dataProvider data_double_encoding
	 */
	public function test_double_encoding( $input, $output ) {
		return $this->assertSame( $output, _wp_specialchars( $input, ENT_NOQUOTES, false, true ) );
	}

	public function data_double_encoding() {
		return array(
			array(
				'This & that, this &amp; that, &#8212; &quot; &QUOT; &Uacute; &nbsp; &#34; &#034; &#0034; &#x00022; &#x22; &dollar; &times;',
				'This &amp; that, this &amp;amp; that, &amp;#8212; &amp;quot; &amp;QUOT; &amp;Uacute; &amp;nbsp; &amp;#34; &amp;#034; &amp;#0034; &amp;#x00022; &amp;#x22; &amp;dollar; &amp;times;',
			),
			array(
				'&& &&amp; &amp;&amp; &amp;;',
				'&amp;&amp; &amp;&amp;amp; &amp;amp;&amp;amp; &amp;amp;;',
			),
			array(
				'&garbage; &***; &aaaa; &0000; &####; &;;',
				'&amp;garbage; &amp;***; &amp;aaaa; &amp;0000; &amp;####; &amp;;;',
			),
		);
	}

	/**
	 * Check some of the double-encoding features for entity references.
	 *
	 * @ticket 17780
	 * @dataProvider data_no_double_encoding
	 */
	public function test_no_double_encoding( $input, $output ) {
		return $this->assertSame( $output, _wp_specialchars( $input, ENT_NOQUOTES, false, false ) );
	}

	public function data_no_double_encoding() {
		return array(
			array(
				'This & that, this &amp; that, &#8212; &quot; &QUOT; &Uacute; &nbsp; &#34; &#034; &#0034; &#x00022; &#x22; &dollar; &times;',
				'This &amp; that, this &amp; that, &#8212; &quot; &amp;QUOT; &Uacute; &nbsp; &#034; &#034; &#034; &#x22; &#x22; &amp;dollar; &times;',
			),
			array(
				'&& &&amp; &amp;&amp; &amp;;',
				'&amp;&amp; &amp;&amp; &amp;&amp; &amp;;',
			),
			array(
				'&garbage; &***; &aaaa; &0000; &####; &;;',
				'&amp;garbage; &amp;***; &amp;aaaa; &amp;0000; &amp;####; &amp;;;',
			),
		);
	}
}
