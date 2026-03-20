<?php
/**
 * Customize API: WP_Customize_Site_Icon_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Site Icon control class.
 *
 * Used only for custom functionality in JavaScript.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Cropped_Image_Control
 */
class WP_Customize_Site_Icon_Control extends WP_Customize_Cropped_Image_Control {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'site_icon';

	/**
	 * Constructor.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 *                                      See WP_Customize_Control::__construct() for information
	 *                                      on accepted arguments. Default empty array.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		add_action( 'customize_controls_print_styles', 'wp_site_icon', 99 );
	}

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.8.0
	 */
	function render_content() {
		if ( $this->label ) {
			?>

			<span class="customize-control-title">
				<?php echo esc_html( $this->label ); ?>
			</span>

			<?php
		}
		?>

		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>

		<?php
		$attachment_id    = (int) $this->value();
		$default_icon_url = get_site_icon_url();  // Theme default favicon
		$image_url        = '';

		if ( $attachment_id ) {
			?>
			<div class="attachment-media-view">
				<?php
				$full_size = wp_get_attachment_image_src( $attachment_id, 'full' );
				$image_url = $full_size ? $full_size[0] : wp_get_attachment_url( $attachment_id );
				?>
				<div class="site-icon-preview customizer">
					<div class="favicon-preview">
						<img src="<?php echo esc_url( admin_url( 'images/' . ( is_rtl() ? 'browser-rtl.png' : 'browser.png' ) ) ); ?>"
							class="browser-preview"
							width="182"
							alt=""
						>
						<div class="favicon">
							<img src="<?php echo esc_url( $image_url ); ?>"
								alt="<?php esc_attr_e( 'Preview as a browser icon' ); ?>"
							>
						</div>
						<span class="browser-title" aria-hidden="true">
							<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
						</span>
					</div>
					<img class="app-icon-preview"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php esc_attr_e( 'Preview as an app icon' ); ?>"
					>
				</div>
				<input type="hidden" value="<?php echo esc_attr( $attachment_id ); ?>">

				<?php
				if ( current_user_can( 'upload_files' ) ) {
					?>

					<div class="actions"
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php echo esc_attr( $this->button_labels['site_icon'] ); ?>"
						data-full="<?php echo esc_attr( $this->button_labels['change'] ); ?>"
					>
						<button type="button" class="button remove-button">
							<?php echo esc_html( $this->button_labels['remove'] ); ?>
						</button>
						<button type="button" class="button select-button">
							<?php echo esc_html( $this->button_labels['change'] ); ?>
						</button>
					</div>

					<?php
				}
				?>

			</div>

			<?php
		} else {
			?>

			<div class="attachment-media-view">
				<input type="hidden" value="<?php echo esc_attr( $attachment_id ); ?>">

				<?php
				if ( current_user_can( 'upload_files' ) ) {
					?>

					<div class="actions"
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php echo esc_attr( $this->button_labels['site_icon'] ); ?>"
						data-full="<?php echo esc_attr( $this->button_labels['change'] ); ?>"
					>
						<button type="button" class="upload-button button-add-media select-button">
							<?php echo esc_html( $this->button_labels['site_icon'] ); ?>
						</button>

						<?php
						if ( $default_icon_url ) {
							?>
							<button type="button" class="button default-button">
								<?php echo esc_html( $this->button_labels['default'] ); ?>
							</button>
							<?php
						}
						?>

					</div>
					<?php
				}
				?>

			</div>
			<?php
		}

		if ( $this->description  ) {
			?>

			<div class="description customize-control-description">
				<?php echo wp_kses_post( $this->description ); ?>
			</div>

			<?php
		}
	}

	/**
	 * Redundant JS template function.
	 *
	 * @since CP_2.8.0
	 */
	public function content_template() {}
}
