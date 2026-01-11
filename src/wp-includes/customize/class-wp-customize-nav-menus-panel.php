<?php
/**
 * Customize API: WP_Customize_Nav_Menus_Panel class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Nav Menus Panel Class
 *
 * Needed to add screen options.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Panel
 */
class WP_Customize_Nav_Menus_Panel extends WP_Customize_Panel {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menus';

	/**
	 * Render screen options for Menus.
	 *
	 * @since 4.3.0
	 */
	public function render_screen_options() {
		// Adds the screen options.
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		add_filter( 'manage_nav-menus_columns', 'wp_nav_menu_manage_columns' );

		// Display screen options.
		$screen = WP_Screen::get( 'nav-menus.php' );
		$screen->render_screen_options( array( 'wrap' => false ) );
	}

	/**
	 * Returns the advanced options for the nav menus page.
	 *
	 * Link title attribute added as it's a relatively advanced concept for new users.
	 *
	 * @since 4.3.0
	 * @deprecated 4.5.0 Deprecated in favor of wp_nav_menu_manage_columns().
	 */
	public function wp_nav_menu_manage_columns() {
		_deprecated_function( __METHOD__, '4.5.0', 'wp_nav_menu_manage_columns' );
		require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
		return wp_nav_menu_manage_columns();
	}

	/**
	 * PHP rendering for this panel's content (but not its container).
	 *
	 * @since CP-2.7.0
	 */
	public function render_content() {
		$panels      = $this->manager->panels();
		$menus_panel = isset ( $panels['nav_menus'] ) ? $panels['nav_menus'] : null;
		if ( $menus_panel ) :
			$cannot_expand = $menus_panel->description ? '' : ' cannot-expand';
			?>

			<li class="panel-meta customize-info accordion-section<?php echo $cannot_expand; ?>">
				<button type="button" class="customize-panel-back" tabindex="-1">
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						esc_html_e( 'Back' );
						?>
					</span>
				</button>
				<div class="accordion-section-title">
					<span class="preview-notice">
						<?php
						/* translators: %s: The site/panel title in the Customizer. */
						printf( __( 'You are customizing %s' ), '<strong class="panel-title">' . esc_html( $menus_panel->title ) . '</strong>' );
						?>
					</span>
					<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
						<span class="screen-reader-text">
							<?php
							/* translators: Hidden accessibility text. */
							esc_html_e( 'Help' );
							?>
						</span>
					</button>
					<button type="button" class="customize-screen-options-toggle" aria-expanded="false">
						<span class="screen-reader-text">
							<?php
							/* translators: Hidden accessibility text. */
							esc_html_e( 'Menu Options' );
							?>
						</span>
					</button>
				</div>

				<?php
				if ( $menus_panel->description ) {
					?>

					<div class="description customize-panel-description">
						<?php echo wp_kses_post( $menus_panel->description ); ?>
					</div>

					<?php
				}
				?>

				<div id="screen-options-wrap">
					<?php $this->render_screen_options(); ?>
				</div>
			</li>

			<?php
		endif;
		// NOTE: The following is a workaround for an inability to treat (and thus label) a list of sections as a whole.
		?>

		<li class="customize-control-title customize-section-title-nav_menus-heading">
			<?php esc_html_e( 'Menus' ); ?>
		</li>

		<?php
	}

	/**
	 * Redundant Underscore (JS) template for this panel's content (but not its container).
	 *
	 * Class variables for this panel class are available in the `data` JS object;
	 * export custom variables by overriding WP_Customize_Panel::json().
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Panel::print_template()
	 */
	protected function content_template() {}
}
