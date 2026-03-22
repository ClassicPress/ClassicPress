<?php
/**
 * Customize API: WP_Customize_Nav_Menu_Item_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize control to represent the name field for a given menu.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Nav_Menu_Item_Control extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'nav_menu_item';

	/**
	 * The nav menu item setting.
	 *
	 * @since 4.3.0
	 * @var WP_Customize_Nav_Menu_Item_Setting
	 */
	public $setting;

	/**
	 * Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      The control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 *                                      See WP_Customize_Control::__construct() for information
	 *                                      on accepted arguments. Default empty array.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Render the control's content via PHP.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$item      = $this->value();
		$item_id   = absint( str_replace( array( 'nav_menu_item[', ']' ), '', $this->id ) );
		$title     = $item['title'] ? $item['title'] : $item['original_title'];
		$no_title  = $title ? '' : 'no-title';
		$untitled  = _x( '(no label)', 'missing menu item navigation label' );
		?>

		<div class="menu-item-bar">
			<details class="menu-item-handle" name="menu-<?php echo absint( $item['nav_menu_term_id'] ); ?>">

				<summary>
					<span class="item-title" aria-hidden="true">
						<span class="menu-item-title<?php echo $no_title; ?>">
							<?php echo esc_html( $title ? $title : $untitled ); ?>
						</span>
					</span>
					<div class="menu-item-reorder-nav">

						<?php
						printf(
							'<button type="button" class="menus-move-up">%1$s</button><button type="button" class="menus-move-down">%2$s</button><button type="button" class="menus-move-left">%3$s</button><button type="button" class="menus-move-right">%4$s</button>',
							__( 'Move up' ),
							__( 'Move down' ),
							__( 'Move one level up' ),
							__( 'Move one level down' )
						);
						?>

					</div>
					<span class="item-type" aria-hidden="true">
						<?php echo esc_html( $item['type_label'] ); ?>
					</span>
					<span class="item-controls">
						<button type="button" class="button-link item-delete submitdelete deletion">
							<span class="screen-reader-text">
								<?php
									/* translators: 1: Title of a menu item, 2: Type of a menu item. */
									printf( __( 'Remove Menu Item: %1$s (%2$s)' ), esc_html__( $title ? $title : $untitled ), esc_html__( $item['type_label'] ) );
								?>
							</span>
						</button>
					</span>
				</summary>

				<div class="menu-item-settings" id="menu-item-settings-<?php echo $item_id; ?>">

					<?php
					if ( 'custom' === $item['type'] ) {
						?>
						<p class="field-url description description-thin">
							<label for="edit-menu-item-url-<?php echo $item_id; ?>">
								<?php esc_html_e( 'URL' ); ?>
								<br>
								<input class="widefat code edit-menu-item-url"
									type="text"
									id="edit-menu-item-url-<?php echo $item_id; ?>"
									name="menu-item-url"
									value="<?php echo esc_html( $item['url'] ); ?>"
								>
							</label>
						</p>
						<?php
					}
					?>

					<p class="description description-thin">
						<label for="edit-menu-item-title-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Navigation Label' ); ?>
							<br>
							<input type="text"
								id="edit-menu-item-title-<?php echo $item_id; ?>"
								placeholder="<?php echo esc_attr( $item['original_title'] ); ?>"
								class="widefat edit-menu-item-title"
								name="menu-item-title"
								value="<?php esc_html( $item['title'] ) ? echo esc_html( $item['title'] ) : esc_html( $item['original_title'] ); ?>"
							>
						</label>
					</p>
					<p class="field-link-target description description-thin">
						<label for="edit-menu-item-target-<?php echo $item_id; ?>">
							<input type="checkbox"
								id="edit-menu-item-target-<?php echo $item_id; ?>"
								class="edit-menu-item-target"
								value="_blank"
								name="menu-item-target"
								value="<?php echo esc_html( $item['target'] ); ?>"
							>
							<?php esc_html_e( 'Open link in a new tab' ); ?>
						</label>
					</p>
					<p class="field-title-attribute field-attr-title description description-thin">
						<label for="edit-menu-item-attr-title-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Title Attribute' ); ?>
							<br>
							<input type="text"
								id="edit-menu-item-attr-title-<?php echo $item_id; ?>"
								class="widefat edit-menu-item-attr-title"
								name="menu-item-attr-title"
								value="<?php echo esc_html( $item['attr_title'] ); ?>"
							>
						</label>
					</p>
					<p class="field-css-classes description description-thin">
						<label for="edit-menu-item-classes-<?php echo $item_id; ?>">
							<?php esc_html_e( 'CSS Classes' ); ?>
							<br>
							<input type="text"
								id="edit-menu-item-classes-<?php echo $item_id; ?>"
								class="widefat code edit-menu-item-classes"
								name="menu-item-classes"
								value="<?php echo esc_html( $item['classes'] ); ?>"
							>
						</label>
					</p>
					<p class="field-xfn description description-thin">
						<label for="edit-menu-item-xfn-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Link Relationship (XFN)' ); ?>
							<br>
							<input type="text"
								id="edit-menu-item-xfn-<?php echo $item_id; ?>"
								class="widefat code edit-menu-item-xfn"
								name="menu-item-xfn"
								value="<?php echo esc_html( $item['xfn'] ); ?>"
							>
						</label>
					</p>
					<p class="field-description description description-thin">
						<label for="edit-menu-item-description-<?php echo $item_id; ?>">
							<?php esc_html_e( 'Description' ); ?>
							<br>
							<textarea id="edit-menu-item-description-<?php echo $item_id; ?>"
								class="widefat edit-menu-item-description"
								rows="3"
								cols="20"
								name="menu-item-description"
							>
								<?php echo wp_kses_post( $item['description'] ); ?>
							</textarea>
							<span class="description">
								<?php esc_html_e( 'The description will be displayed in the menu if the active theme supports it.' ); ?>
							</span>
						</label>
					</p>

					<?php
					/**
					 * Creates an output buffer to convert mustache-style
					 * attributes added to nav menu items by themes or
					 * plugins into appropriate HTML.
					 */
					ob_start();

					/**
					 * Fires at the end of the form field template for nav menu items in the customizer.
					 *
					 * Additional fields can be rendered here.
					 *
					 * @since 5.4.0
					 *
					 * Parameters $item_id and $item added
					 *
					 * @since CP-2.8.0
					 *
					 * @param int     $item_id   The actual menu item ID.
					 * @param object  $item      The menu item object.
					 */
					do_action( 'wp_nav_menu_item_custom_fields_customize_template', $item_id, $item );
					$plugin_template = ob_get_clean();

					if ( $plugin_template ) { // Replace mustache-style placeholders with actual values.
						$plugin_template = str_replace( '{{ data.menu_item_id }}', $item_id, $plugin_template );

						// Mods for Nav Menu Roles plugin
						// Get current values (DB + preview, emulating plugin logic).
						$mode = get_post_meta( $item_id, '_nav_menu_role_display_mode', true ) ? get_post_meta( $item_id, '_nav_menu_role_display_mode', true ) : 'show';
						$roles = get_post_meta( $item_id, '_nav_menu_role', true ) ? (array) get_post_meta( $item_id, '_nav_menu_role', true ) : array();

						// Inject values (Display Mode + Target Audience + Roles)
						$plugin_template = cp_nav_menu_roles_injector( $plugin_template, $mode, $roles, $item_id );

						echo $plugin_template;
					}
					?>

					<div class="menu-item-actions description-thin submitbox">

						<?php
						if ( $item['original_title'] !== '' && in_array( $item['type'], array( 'post_type', 'taxonomy' ), true ) ) {
							?>

							<p class="link-to-original">
								<?php
									/* translators: Nav menu item original title. %s: Original title. */
									printf( __( 'Original: %s' ), '<a class="original-link" href="' . esc_url( $item['url'] ) . '">' . esc_html__( $item['original_title'] ) . '</a>' );
								?>
							</p>

							<?php
						}
						?>

						<button type="button" class="button-link button-link-delete item-delete submitdelete deletion">
							<?php esc_html_e( 'Remove' ); ?>
						</button>
						<span class="spinner"></span>
					</div>
					<input type="hidden" name="menu-item-db-id[<?php echo $item_id; ?>]" class="menu-item-data-db-id" value="<?php echo $item_id; ?>">
					<input type="hidden" name="menu-item-object-id[<?php echo $item_id; ?>]" class="menu-item-data-object-id" value="<?php echo absint( $item['object_id'] ); ?>">
					<input type="hidden" name="menu-item-object[<?php echo $item_id; ?>]" class="menu-item-data-object" value="<?php echo esc_attr( $item['object'] ); ?>">
					<input type="hidden" name="menu-item-parent-id[<?php echo $item_id; ?>]" class="menu-item-data-parent-id" value="<?php echo absint( $item['menu_item_parent'] ); ?>">
					<input type="hidden" name="menu-item-position[<?php echo $item_id; ?>]" class="menu-item-data-position" value="<?php echo absint( $item['position'] ); ?>">
					<input type="hidden" name="menu-item-type[<?php echo $item_id; ?>]" class="menu-item-data-type" value="<?php echo esc_attr( $item['type'] ); ?>">
					<input type="hidden" name="menu-item-menu-id[<?php echo $item_id; ?>]" class="menu-item-data-menu-id" value="<?php echo absint( $item['nav_menu_term_id'] ); ?>">
				</div><!-- .menu-item-settings-->
				<ul class="menu-item-transport"></ul>

			</details>
		</div>

		<?php
	}

	/**
	 * Redundant JS/Underscore template for the control UI.
	 *
	 * @since CP-2.8.0
	 */
	public function content_template() {}

	/**
	 * Return parameters for this control.
	 *
	 * @since 4.3.0
	 *
	 * @return array Exported parameters.
	 */
	public function json() {
		$exported                 = parent::json();
		$exported['menu_item_id'] = $this->setting->post_id;

		return $exported;
	}
}

/**
 * Injector function to ensure the functioning of the Nav Menu Roles plugin.
 */
function cp_nav_menu_roles_injector( $template, $mode, $roles, $item_id ) {

	// Display Mode.
	$show_input = sprintf(
		'name="nav-menu-display-mode[%d]" id="edit-menu-item-role_display_mode_show-%d" value="show"',
		$item_id,
		$item_id
	);
	$hide_input = sprintf(
		'name="nav-menu-display-mode[%d]" id="edit-menu-item-role_display_mode_hide-%d" value="hide"',
		$item_id,
		$item_id
	);

	$template = str_replace( $show_input, $show_input . checked( 'show', $mode, false ), $template );
	$template = str_replace( $hide_input, $hide_input . checked( 'hide', $mode, false ), $template );

	// Target Audience (Logged In/Out/Everyone).
	$target_value = ! empty( $roles ) ? 'in' : '';
	$template = str_replace( 'value="in"', 'value="in"' . checked( 'in',  $target_value, false ), $template );
	$template = str_replace( 'value="out"', 'value="out"' . checked( 'out', $target_value, false ), $template );
	$template = str_replace( 'value=""', 'value=""' . checked( '', $target_value, false ), $template );

	// Roles checkboxes.
	global $wp_roles;
	$display_roles = apply_filters( 'nav_menu_roles', $wp_roles->role_names ?? array() );
	asort( $display_roles );
	foreach ( array_keys( $display_roles ) as $role_key ) {
		$value_match = 'value="' . esc_attr( $role_key ) . '"';
		$is_selected = in_array( $role_key, $roles, true );
		$template = str_replace( $value_match, $value_match . checked( $is_selected, true, false ), $template );
	}

	return $template;
}
