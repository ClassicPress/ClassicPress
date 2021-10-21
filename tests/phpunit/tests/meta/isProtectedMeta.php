<?php

/**
 * @group meta
 * @covers ::is_protected_meta
 */
class Tests_Meta_isProtectedMeta extends WP_UnitTestCase {

	/**
	 * @dataProvider protected_data
	 */
	public function test_protected( $key ) {
		$this->assertTrue( is_protected_meta( $key ) );
	}

	public function protected_data() {
		$protected_keys = array(
			array( '_wp_attachment' ),
		);
		for ( $i = 0, $max = 31; $i < $max; $i ++ ) {
			$protected_keys[] = array( chr( $i ) . '_wp_attachment' );
		}
		for ( $i = 127, $max = 159; $i <= $max; $i ++ ) {
			$protected_keys[] = array( chr( $i ) . '_wp_attachment' );
		}
		$protected_keys[] = array( chr( 95 ) . '_wp_attachment' );

		return $protected_keys;
	}

	/**
	 * @dataProvider unprotected_data
	 */
	public function test_unprotected( $key ) {
		$this->assertFalse( is_protected_meta( $key ) );
	}

	public function unprotected_data() {
		$unprotected_keys = array(
			array( 'singleword' ),
			array( 'two_words' ),
			array( 'ąŌ_not_so_protected_meta' ),
		);

		for ( $i = 32, $max = 94; $i <= $max; $i ++ ) {
			$unprotected_keys[] = array( chr( $i ) . '_wp_attachment' );
		}
		for ( $i = 96, $max = 126; $i <= $max; $i ++ ) {
			$unprotected_keys[] = array( chr( $i ) . '_wp_attachment' );
		}

		return $unprotected_keys;
	}

}
