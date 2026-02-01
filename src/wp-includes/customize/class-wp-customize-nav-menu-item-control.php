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
	 * @since CP-2.7.0
	 */
	public function render_content() {
		$item     = $this->value();
		$title    = $item['title'] ?: $item['original_title'];
		$no_title = $title ? '' : 'no-title';
		$untitled = _x( '(no label)', 'missing menu item navigation label' );
		?>

		<div class="menu-item-bar">
			<details class="menu-item-handle">

				<summary>
					<span class="item-title" aria-hidden="true">
						<span class="menu-item-title<?php echo $no_title; ?>">
							<?php esc_html_e( $title ?: $untitled ); ?>
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
						<?php esc_html_e( $item['type_label'] ); ?>
					</span>
					<span class="item-controls">
						<button type="button" class="button-link item-delete submitdelete deletion">
							<span class="screen-reader-text">
								<?php
									/* translators: 1: Title of a menu item, 2: Type of a menu item. */
									printf( __( 'Remove Menu Item: %1$s (%2$s)' ), esc_html__( $title ?: $untitled ), esc_html__( $item['type_label'] ) );
								?>
							</span>
						</button>
					</span>
				</summary>

				<div class="menu-item-settings" id="menu-item-settings-<?php esc_attr_e( $item['object_id'] ); ?>">

					<?php
					if ( 'custom' === $item['type'] ) {
						?>
						<p class="field-url description description-thin">
							<label for="edit-menu-item-url-<?php esc_attr_e( $item['object_id'] ); ?>">
								<?php esc_html_e( 'URL' ); ?><br>
								<input class="widefat code edit-menu-item-url"
									type="text"
									id="edit-menu-item-url-<?php esc_attr_e( $item['object_id'] ); ?>"
									name="menu-item-url"
								>
							</label>
						</p>
						<?php
					}
					?>

					<p class="description description-thin">
						<label for="edit-menu-item-title-<?php esc_attr_e( $item['object_id'] ); ?>">
							<?php esc_html_e( 'Navigation Label' ); ?><br>
							<input type="text" id="edit-menu-item-title-<?php esc_attr_e( $item['object_id'] ); ?>" placeholder="<?php esc_attr_e( $item['original_title'] ); ?>" class="widefat edit-menu-item-title" name="menu-item-title">
						</label>
					</p>
					<p class="field-link-target description description-thin">
						<label for="edit-menu-item-target-<?php esc_attr_e( $item['object_id'] ); ?>">
							<input type="checkbox" id="edit-menu-item-target-<?php esc_attr_e( $item['object_id'] ); ?>" class="edit-menu-item-target" value="_blank" name="menu-item-target">
							<?php esc_html_e( 'Open link in a new tab' ); ?>
						</label>
					</p>
					<p class="field-title-attribute field-attr-title description description-thin">
						<label for="edit-menu-item-attr-title-<?php esc_attr_e( $item['object_id'] ); ?>">
							<?php esc_html_e( 'Title Attribute' ); ?><br>
							<input type="text" id="edit-menu-item-attr-title-<?php esc_attr_e( $item['object_id'] ); ?>" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title">
						</label>
					</p>
					<p class="field-css-classes description description-thin">
						<label for="edit-menu-item-classes-<?php esc_attr_e( $item['object_id'] ); ?>">
							<?php esc_html_e( 'CSS Classes' ); ?><br>
							<input type="text" id="edit-menu-item-classes-<?php esc_attr_e( $item['object_id'] ); ?>" class="widefat code edit-menu-item-classes" name="menu-item-classes">
						</label>
					</p>
					<p class="field-xfn description description-thin">
						<label for="edit-menu-item-xfn-<?php esc_attr_e( $item['object_id'] ); ?>">
							<?php esc_html_e( 'Link Relationship (XFN)' ); ?><br>
							<input type="text" id="edit-menu-item-xfn-<?php esc_attr_e( $item['object_id'] ); ?>" class="widefat code edit-menu-item-xfn" name="menu-item-xfn">
						</label>
					</p>
					<p class="field-description description description-thin">
						<label for="edit-menu-item-description-<?php esc_attr_e( $item['object_id'] ); ?>">
							<?php esc_html_e( 'Description' ); ?><br>
							<textarea id="edit-menu-item-description-<?php esc_attr_e( $item['object_id'] ); ?>" class="widefat edit-menu-item-description" rows="3" cols="20" name="menu-item-description">
								<?php echo $item['description']; ?>
							</textarea>
							<span class="description">
								<?php esc_html_e( 'The description will be displayed in the menu if the active theme supports it.' ); ?>
							</span>
						</label>
					</p>

					<?php
					/**
					 * Fires at the end of the form field template for nav menu items in the customizer.
					 *
					 * Additional fields can be rendered here and managed in JavaScript.
					 *
					 * @since 5.4.0
					 */
					do_action( 'wp_nav_menu_item_custom_fields_customize_template' );
					?>

					<div class="menu-item-actions description-thin submitbox">

						<?php
						if ( ( 'post_type' === $item['type'] || 'taxonomy' === $item['type'] ) && '' !== $item['original_title'] ) {
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
					<input type="hidden" name="menu-item-db-id[<?php esc_attr_e( $item['object_id'] ); ?>]" class="menu-item-data-db-id" value="<?php esc_attr_e( $item['object_id'] ); ?>">
					<input type="hidden" name="menu-item-parent-id[<?php esc_attr_e( $item['object_id'] ); ?>]" class="menu-item-data-parent-id" value="<?php esc_attr_e( $item['menu_item_parent'] ); ?>">
				</div><!-- .menu-item-settings-->
				<ul class="menu-item-transport"></ul>

			</details>
		</div>

		<?php
	}

	/**
	 * Redundant JS/Underscore template for the control UI.
	 *
	 * @since CP-2.7.0
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
