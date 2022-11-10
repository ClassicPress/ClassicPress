<?php

/**
 * Test cp_attributes().
 *
 * @group functions.php
 */
class Tests_Formatting_CpAttributes extends WP_UnitTestCase {

	function test_attributes_removes_quotes_in_string() {
		$this->assertSame( 'role="main"', cp_attributes( 'body', 'role="main"' ) );
		$this->assertSame( 'role="main"', cp_attributes( 'div', "role='main'" ) );
		$this->assertSame( 'role="main" class="test"', cp_attributes( 'div', 'role="main" class="test"' ) );
		$this->assertSame( 'id="main" class="test"', cp_attributes( 'div', "id='main' class='test'" ) );
		$this->assertSame( 'title="O&#039;toole"', cp_attributes( 'img', 'title="O\'toole"' ) );
		$this->assertSame( 'title="O&#039;toole"', cp_attributes( 'img', "title='O'toole'" ) );
		$this->assertSame( 'role="main" class="test"', cp_attributes( 'main', 'role=\'main\' class="test"' ) );
		$this->assertSame( 'id="main" class="site-main" role="main"', cp_attributes( 'main', 'id=main&class=site-main&role="main"' ) );
	}

	function test_attributes_empty() {
		$this->assertSame( '', cp_attributes( 'div', array() ) );
		$this->assertSame( '', cp_attributes( 'span', '' ) );
	}

	function test_attributes_empty_value() {
		$this->assertSame( 'disabled=""', cp_attributes( 'input', array( 'disabled' => '' ) ) );
		$this->assertSame( 'disabled=""', cp_attributes( 'input', 'disabled' ) );
	}

	// This is used as a filter to verify the context.
	function context_getter( $attrs, $element, $context ) {
		$attrs['context'] = $context;
		return $attrs;
	}

	function test_attributes_empty_context_finds_caller_name() {
		add_filter( 'cp_attributes', array( $this, 'context_getter' ), 10, 3 );
		$this->assertSame( 'context="passed context"', cp_attributes( 'p', '', 'passed context' ) );
		$this->assertSame( 'context="test_attributes_empty_context_finds_caller_name"', cp_attributes( 'p', '' ) );
		remove_filter( 'cp_attributes', array( $this, 'context_getter' ), 10 );
	}

	function test_attributes_escape_src_url() {
		$this->assertSame(
			'id="main" src="http://example.org/one?z=5&#038;x=3" data-s="example.org/one?z=5&amp;x=3"',
			cp_attributes(
				'iframe',
				array(
					'id'     => 'main',
					'src'    => 'example.org/one?z=5&x=3',
					'data-s' => 'example.org/one?z=5&x=3',
				)
			)
		);
	}

	function test_attributes_escape_href_url() {
		$this->assertSame(
			'title="0.25&quot; in height" href="http://example.org/one?z=5&#038;x=3" data-h="example.org/one?x=5&amp;y=3"',
			cp_attributes(
				'a',
				array(
					'title'  => '0.25" in height',
					'href'   => 'example.org/one?z=5&x=3',
					'data-h' => 'example.org/one?x=5&y=3',
				)
			)
		);
	}

	function test_attributes_remove_duplicate() {
		$this->assertSame(
			'font="latin latin2" class="one two"',
			cp_attributes(
				'section',
				array(
					'font'  => array( 'latin', 'latin2' ),
					'class' => array( 'one', 'two', 'one' ),
				)
			)
		);
	}

	function test_attributes_escape_special_characters() {
		$this->assertSame(
			'title="2 &lt; 3" data="John &amp; Sons"',
			cp_attributes(
				'nav',
				array(
					'title' => '2 < 3',
					'data' => 'John & Sons',
				)
			)
		);
	}

}
