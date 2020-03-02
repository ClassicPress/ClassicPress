<?php
/**
 * Customize API: WP_Customize_Sidebar_Section class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since WP-4.4.0
 */

/**
 * Customizer section representing widget area (sidebar).
 *
 * @since WP-4.1.0
 *
 * @see WP_Customize_Section
 */
class WP_Customize_Sidebar_Section extends WP_Customize_Section {

	/**
	 * Type of this section.
	 *
	 * @since WP-4.1.0
	 * @var string
	 */
	public $type = 'sidebar';

	/**
	 * Unique identifier.
	 *
	 * @since WP-4.1.0
	 * @var string
	 */
	public $sidebar_id;

	/**
	 * Gather the parameters passed to client JavaScript via JSON.
	 *
	 * @since WP-4.1.0
	 *
	 * @return array The array to be exported to the client as JSON.
	 */
	public function json() {
		$json = parent::json();
		$json['sidebarId'] = $this->sidebar_id;
		return $json;
	}

	/**
	 * Whether the current sidebar is rendered on the page.
	 *
	 * @since WP-4.1.0
	 *
	 * @return bool Whether sidebar is rendered.
	 */
	public function active_callback() {
		return $this->manager->widgets->is_sidebar_rendered( $this->sidebar_id );
	}
}
