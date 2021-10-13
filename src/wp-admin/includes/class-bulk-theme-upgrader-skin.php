<?php
/**
 * Upgrader API: Bulk_Plugin_Upgrader_Skin class
 *
 * @package ClassicPress
 * @subpackage Upgrader
 * @since WP-4.6.0
 */

/**
 * Bulk Theme Upgrader Skin for ClassicPress Theme Upgrades.
 *
 * @since WP-3.0.0
 * @since WP-4.6.0 Moved to its own file from wp-admin/includes/class-wp-upgrader-skins.php.
 *
 * @see Bulk_Upgrader_Skin
 */
class Bulk_Theme_Upgrader_Skin extends Bulk_Upgrader_Skin {
	public $theme_info = array(); // Theme_Upgrader::bulk() will fill this in.

	public function add_strings() {
		parent::add_strings();
		$this->upgrader->strings['skin_before_update_header'] = __('Updating Theme %1$s (%2$d/%3$d)');
	}

	/**
	 *
	 * @param string $title
	 */
	public function before($title = '') {
		parent::before( $this->theme_info->display('Name') );
	}

	/**
	 *
	 * @param string $title
	 */
	public function after($title = '') {
		parent::after( $this->theme_info->display('Name') );
		$this->decrement_update_count( 'theme' );
	}

	/**
	 */
	public function bulk_footer() {
		parent::bulk_footer();

		$update_actions = array(
			'themes_page'  => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'themes.php' ),
				__( 'Go to Themes page' )
			),
			'updates_page' => sprintf(
				'<a href="%s" target="_parent">%s</a>',
				self_admin_url( 'update-core.php' ),
				__( 'Go to ClassicPress Updates page' )
			),
		);
		if ( ! current_user_can( 'switch_themes' ) && ! current_user_can( 'edit_theme_options' ) )
			unset( $update_actions['themes_page'] );

		/**
		 * Filters the list of action links available following bulk theme updates.
		 *
		 * @since WP-3.0.0
		 *
		 * @param array $update_actions Array of theme action links.
		 * @param array $theme_info     Array of information for the last-updated theme.
		 */
		$update_actions = apply_filters( 'update_bulk_theme_complete_actions', $update_actions, $this->theme_info );

		if ( ! empty($update_actions) )
			$this->feedback(implode(' | ', (array)$update_actions));
	}
}
