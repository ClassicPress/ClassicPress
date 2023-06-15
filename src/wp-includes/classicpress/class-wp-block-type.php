<?php
/**
 * Blocks API: WP_Block_Type class Polyfill
 *
 * @package ClassicPress
 * @subpackage Blocks
 * @since CP-2.0.0
 */

/**
 * Core class representing a block type.
 *
 * @since CP-2.0.0
 *
 * @see register_block_type()
 */

class WP_Block_Type {
	public function __set( $name, $value ) {
		WP_Compat::using_block_function();
	}

	public function __get( $name ) {
		WP_Compat::using_block_function();
		return false;
	}

	public function __isset( $name ) {
		WP_Compat::using_block_function();
		return false;
	}

	public function __unset( $name ) {
		WP_Compat::using_block_function();
		return false;
	}

	public function __call( $name, $arguments ) {
		WP_Compat::using_block_function();
		return false;
	}

	public static function __callstatic( $name, $arguments ) {
		WP_Compat::using_block_function();
		return false;
	}
}
