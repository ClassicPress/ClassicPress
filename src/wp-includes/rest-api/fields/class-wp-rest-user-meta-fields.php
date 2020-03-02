<?php
/**
 * REST API: WP_REST_User_Meta_Fields class
 *
 * @package ClassicPress
 * @subpackage REST_API
 * @since WP-4.7.0
 */

/**
 * Core class used to manage meta values for users via the REST API.
 *
 * @since WP-4.7.0
 *
 * @see WP_REST_Meta_Fields
 */
class WP_REST_User_Meta_Fields extends WP_REST_Meta_Fields {

	/**
	 * Retrieves the object meta type.
	 *
	 * @since WP-4.7.0
	 *
	 * @return string The user meta type.
	 */
	protected function get_meta_type() {
		return 'user';
	}

	/**
	 * Retrieves the object meta subtype.
	 *
	 * @since WP-4.9.8
	 *
	 * @return string 'user' There are no subtypes.
	 */
	protected function get_meta_subtype() {
		return 'user';
	}

	/**
	 * Retrieves the type for register_rest_field().
	 *
	 * @since WP-4.7.0
	 *
	 * @return string The user REST field type.
	 */
	public function get_rest_field_type() {
		return 'user';
	}
}
