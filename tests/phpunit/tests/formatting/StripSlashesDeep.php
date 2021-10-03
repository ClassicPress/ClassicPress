<?php

/**
 * @group formatting
 */
class Tests_Formatting_StripSlashesDeep extends WP_UnitTestCase {
	/**
	 * @see https://core.trac.wordpress.org/ticket/18026
	 */
	function test_preserves_original_datatype() {

<<<<<<< HEAD
		$this->assertEquals( true, stripslashes_deep( true ) );
		$this->assertEquals( false, stripslashes_deep( false ) );
		$this->assertEquals( 4, stripslashes_deep( 4 ) );
		$this->assertEquals( 'foo', stripslashes_deep( 'foo' ) );
		$arr = array( 'a' => true, 'b' => false, 'c' => 4, 'd' => 'foo' );
		$arr['e'] = $arr; // Add a sub-array
		$this->assertEquals( $arr, stripslashes_deep( $arr ) ); // Keyed array
		$this->assertEquals( array_values( $arr ), stripslashes_deep( array_values( $arr ) ) ); // Non-keyed
=======
		$this->assertTrue( stripslashes_deep( true ) );
		$this->assertFalse( stripslashes_deep( false ) );
		$this->assertSame( 4, stripslashes_deep( 4 ) );
		$this->assertSame( 'foo', stripslashes_deep( 'foo' ) );
		$arr      = array(
			'a' => true,
			'b' => false,
			'c' => 4,
			'd' => 'foo',
		);
		$arr['e'] = $arr; // Add a sub-array.
		$this->assertSame( $arr, stripslashes_deep( $arr ) ); // Keyed array.
		$this->assertSame( array_values( $arr ), stripslashes_deep( array_values( $arr ) ) ); // Non-keyed.
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$obj = new stdClass;
		foreach ( $arr as $k => $v )
			$obj->$k = $v;
<<<<<<< HEAD
		$this->assertEquals( $obj, stripslashes_deep( $obj ) );
=======
		}
		$this->assertSame( $obj, stripslashes_deep( $obj ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_strips_slashes() {
		$old = "I can\'t see, isn\'t that it?";
		$new = "I can't see, isn't that it?";
<<<<<<< HEAD
		$this->assertEquals( $new, stripslashes_deep( $old ) );
		$this->assertEquals( $new, stripslashes_deep( "I can\\'t see, isn\\'t that it?" ) );
		$this->assertEquals( array( 'a' => $new ), stripslashes_deep( array( 'a' => $old ) ) ); // Keyed array
		$this->assertEquals( array( $new ), stripslashes_deep( array( $old ) ) ); // Non-keyed
=======
		$this->assertSame( $new, stripslashes_deep( $old ) );
		$this->assertSame( $new, stripslashes_deep( "I can\\'t see, isn\\'t that it?" ) );
		$this->assertSame( array( 'a' => $new ), stripslashes_deep( array( 'a' => $old ) ) ); // Keyed array.
		$this->assertSame( array( $new ), stripslashes_deep( array( $old ) ) ); // Non-keyed.
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$obj_old = new stdClass;
		$obj_old->a = $old;
		$obj_new = new stdClass;
		$obj_new->a = $new;
		$this->assertEquals( $obj_new, stripslashes_deep( $obj_old ) );
	}

	function test_permits_escaped_slash() {
		$txt = "I can't see, isn\'t that it?";
		$this->assertSame( $txt, stripslashes_deep( "I can\'t see, isn\\\'t that it?" ) );
		$this->assertSame( $txt, stripslashes_deep( "I can\'t see, isn\\\\\'t that it?" ) );
	}
}
