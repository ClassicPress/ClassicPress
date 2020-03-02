<?php
/**
 * Customize API: WP_Customize_Image_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since WP-4.4.0
 */

/**
 * Customize Image Control class.
 *
 * @since WP-3.4.0
 *
 * @see WP_Customize_Upload_Control
 */
class WP_Customize_Image_Control extends WP_Customize_Upload_Control {
	public $type = 'image';
	public $mime_type = 'image';

	/**
	 * @since WP-3.4.2
	 * @deprecated WP-4.1.0
	 */
	public function prepare_control() {}

	/**
	 * @since WP-3.4.0
	 * @deprecated WP-4.1.0
	 *
	 * @param string $id
	 * @param string $label
	 * @param mixed $callback
	 */
	public function add_tab( $id, $label, $callback ) {
		_deprecated_function( __METHOD__, 'WP-4.1.0' );
    }

	/**
	 * @since WP-3.4.0
	 * @deprecated WP-4.1.0
	 *
	 * @param string $id
	 */
	public function remove_tab( $id ) {
		_deprecated_function( __METHOD__, 'WP-4.1.0' );
    }

	/**
	 * @since WP-3.4.0
	 * @deprecated WP-4.1.0
	 *
	 * @param string $url
	 * @param string $thumbnail_url
	 */
	public function print_tab_image( $url, $thumbnail_url = null ) {
		_deprecated_function( __METHOD__, 'WP-4.1.0' );
    }
}
