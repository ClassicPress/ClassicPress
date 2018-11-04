<?php
/**
 * Customize API: WP_Customize_New_Menu_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since WP-4.4.0
 * @deprecated WP-4.9.0 This file is no longer used as of the menu creation UX introduced in https://core.trac.wordpress.org/ticket/40104.
 */

/**
 * Customize control class for new menus.
 *
 * @since WP-4.3.0
 * @deprecated WP-4.9.0 This class is no longer used as of the menu creation UX introduced in https://core.trac.wordpress.org/ticket/40104.
 *
 * @see WP_Customize_Control
 */
class WP_Customize_New_Menu_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @since WP-4.3.0
	 * @var string
	 */
	public $type = 'new_menu';

	/**
	 * Constructor.
	 *
	 * @since WP-4.9.0
	 *
	 * @param WP_Customize_Manager $manager Manager.
	 * @param string               $id      ID.
	 * @param array                $args    Args.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		_deprecated_file( basename( __FILE__ ), 'WP-4.9.0' ); // @todo Move this outside of class in WP-5.0, and remove its require_once() from class-wp-customize-control.php. See https://core.trac.wordpress.org/ticket/42364.
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Render the control's content.
	 *
	 * @since WP-4.3.0
	 */
	public function render_content() {
		?>
		<button type="button" class="button button-primary" id="create-new-menu-submit"><?php _e( 'Create Menu' ); ?></button>
		<span class="spinner"></span>
		<?php
	}
}
