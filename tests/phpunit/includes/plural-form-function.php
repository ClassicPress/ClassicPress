<?php

/**
 * Legacy plural form function.
 *
 * @param int $nplurals
 * @param string $expression
 */
function tests_make_plural_form_function( $nplurals, $expression ) {
<<<<<<< HEAD
	$expression = str_replace( 'n', '$n', $expression );
	$func_body = "
		\$index = (int)($expression);
		return (\$index < $nplurals)? \$index : $nplurals - 1;";

	return create_function( '$n', $func_body );
=======
	$closure = function ( $n ) use ( $nplurals, $expression ) {
		$expression = str_replace( 'n', $n, $expression );

		// phpcs:ignore Squiz.PHP.Eval -- This is test code, not production.
		$index = (int) eval( 'return ' . $expression . ';' );

		return ( $index < $nplurals ) ? $index : $nplurals - 1;
	};

	return $closure;
>>>>>>> f0733600c9 (Code Modernization: Change `create_function()` in `phpunit/includes/plural-form-function.php` to closure.)
}
