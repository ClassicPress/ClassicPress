<?php
/**
 * Customize API: WP_Customize_Background_Image_Setting class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since WP-4.4.0
 */

/**
 * Customizer Background Image Setting class.
 *
 * @since WP-3.4.0
 *
 * @see WP_Customize_Setting
 */
final class WP_Customize_Background_Image_Setting extends WP_Customize_Setting {
	public $id = 'background_image_thumb';

	/**
	 * @since WP-3.4.0
	 *
	 * @param $value
	 */
	public function update( $value ) {
		remove_theme_mod( 'background_image_thumb' );
	}
}
