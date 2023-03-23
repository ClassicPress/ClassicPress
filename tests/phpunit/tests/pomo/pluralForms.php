<?php

/**
 * @group pomo
 */
class PluralFormsTest extends WP_UnitTestCase {
	/**
	 * Parenthesize plural expression.
	 *
	 * Legacy workaround for PHP's flipped precedence order for ternary.
	 *
	 * @param string $expression the expression without parentheses
	 * @return string the expression with parentheses added
	 */
	protected static function parenthesize_plural_expression( $expression ) {
		$expression .= ';';
		$res         = '';
		$depth       = 0;
		for ( $i = 0; $i < strlen( $expression ); ++$i ) {
			$char = $expression[ $i ];
			switch ( $char ) {
				case '?':
					$res .= ' ? (';
					$depth++;
					break;
				case ':':
					$res .= ') : (';
					break;
				case ';':
					$res  .= str_repeat( ')', $depth ) . ';';
					$depth = 0;
					break;
				default:
					$res .= $char;
			}
		}
		return rtrim( $res, ';' );
	}

	/**
	 * @ticket 41562
	 * @group external-http
	 */
	public function test_locales_provider() {
		$locales = self::locales_provider();

		$this->assertNotEmpty( $locales, 'Unable to retrieve GP_Locales file' );
	}

	public static function locales_provider() {
		if ( ! class_exists( 'GP_Locales' ) ) {
			$filename = download_url( 'https://raw.githubusercontent.com/GlotPress/GlotPress-WP/develop/locales/locales.php' );
			if ( is_wp_error( $filename ) ) {
				return array();
			}
			require_once $filename;
		}

		$locales            = GP_Locales::locales();
		$plural_expressions = array();
		foreach ( $locales as $slug => $locale ) {
			$plural_expression = $locale->plural_expression;
			if ( 'n != 1' !== $plural_expression ) {
				$plural_expressions[] = array( $slug, $locale->nplurals, $plural_expression );
			}
		}

		return $plural_expressions;
	}

	/**
	 * @ticket 41562
	 * @dataProvider locales_provider
	 * @group external-http
	 */
	public function test_regression( $lang, $nplurals, $expression ) {
		require_once dirname( dirname( __DIR__ ) ) . '/includes/plural-form-function.php';

		$parenthesized = self::parenthesize_plural_expression( $expression );
		$old_style     = tests_make_plural_form_function( $nplurals, $parenthesized );
		$plural_forms  = new Plural_Forms( $expression );

		$generated_old = array();
		$generated_new = array();

		foreach ( range( 0, 200 ) as $i ) {
			$generated_old[] = $old_style( $i );
			$generated_new[] = $plural_forms->get( $i );
		}

		$this->assertSame( $generated_old, $generated_new );
	}

	public static function simple_provider() {
		return array(
			array(
				// Simple equivalence.
				'n != 1',
				array(
					-1 => 1,
					0  => 1,
					1  => 0,
					2  => 1,
					5  => 1,
					10 => 1,
				),
			),
			array(
				// Ternary.
				'n ? 1 : 2',
				array(
					-1 => 1,
					0  => 2,
					1  => 1,
					2  => 1,
				),
			),
			array(
				// Comparison.
				'n > 1 ? 1 : 2',
				array(
					-2 => 2,
					-1 => 2,
					0  => 2,
					1  => 2,
					2  => 1,
					3  => 1,
				),
			),
			array(
				'n > 1 ? n > 2 ? 1 : 2 : 3',
				array(
					-2 => 3,
					-1 => 3,
					0  => 3,
					1  => 3,
					2  => 2,
					3  => 1,
					4  => 1,
				),
			),
		);
	}

	/**
	 * @ticket 41562
	 * @dataProvider simple_provider
	 */
	public function test_simple( $expression, $expected ) {
		$plural_forms = new Plural_Forms( $expression );
		$actual       = array();
		foreach ( array_keys( $expected ) as $num ) {
			$actual[ $num ] = $plural_forms->get( $num );
		}

		$this->assertSame( $expected, $actual );
	}

	public function data_exceptions() {
		return array(
			array(
				'n # 2',              // Invalid expression to parse.
				'Unknown symbol "#"', // Expected exception message.
				false,                // Whether to call the get() method or not.
			),
			array(
				'n & 1',
				'Unknown operator "&"',
				false,
			),
			array(
				'((n)',
				'Mismatched parentheses',
				false,
			),
			array(
				'(n))',
				'Mismatched parentheses',
				false,
			),
			array(
				'n : 2',
				'Missing starting "?" ternary operator',
				false,
			),
			array(
				'n ? 1',
				'Unknown operator "?"',
				true,
			),
			array(
				'n n',
				'Too many values remaining on the stack',
				true,
			),
		);
	}

	/**
	 * Ensures that an exception is thrown when an invalid plural form is encountered.
	 *
	 * @ticket 41562
	 * @dataProvider data_exceptions
	 */
	public function test_exceptions( $expression, $expected_message, $call_get ) {
		$this->expectException( 'Exception' );
		$this->expectExceptionMessage( $expected_message );

		$plural_forms = new Plural_Forms( $expression );
		if ( $call_get ) {
			$plural_forms->get( 1 );
		}
	}

	/**
	 * @ticket 41562
	 */
	public function test_cache() {
		$mock = $this->getMockBuilder( 'Plural_Forms' )
			->setMethods( array( 'execute' ) )
			->setConstructorArgs( array( 'n != 1' ) )
			->getMock();

		$mock->expects( $this->once() )
			->method( 'execute' )
			->with( $this->identicalTo( 2 ) )
			->will( $this->returnValue( 1 ) );

		$first  = $mock->get( 2 );
		$second = $mock->get( 2 );
		$this->assertSame( $first, $second );
	}
}
