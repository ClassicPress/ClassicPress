<?php
/**
 * Customize API: WP_Customize_Cropped_Image_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Cropped Image Control class.
 *
 * @since 4.3.0
 *
 * @see WP_Customize_Image_Control
 */
class WP_Customize_Cropped_Image_Control extends WP_Customize_Image_Control {

	/**
	 * Control type.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public $type = 'cropped_image';

	/**
	 * Suggested width for cropped image.
	 *
	 * @since 4.3.0
	 * @var int
	 */
	public $width = 150;

	/**
	 * Suggested height for cropped image.
	 *
	 * @since 4.3.0
	 * @var int
	 */
	public $height = 150;

	/**
	 * Whether the width is flexible.
	 *
	 * @since 4.3.0
	 * @var bool
	 */
	public $flex_width = false;

	/**
	 * Whether the height is flexible.
	 *
	 * @since 4.3.0
	 * @var bool
	 */
	public $flex_height = false;

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 4.3.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'customize-views' );

		parent::enqueue();
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 4.3.0
	 *
	 * @see WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$this->json['width']       = absint( $this->width );
		$this->json['height']      = absint( $this->height );
		$this->json['flex_width']  = absint( $this->flex_width );
		$this->json['flex_height'] = absint( $this->flex_height );
	}

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.7.0
	 */
	public function render_content() {//error_log(print_r(get_theme_mod( 'custom_logo' ), true));
		$login_custom_image_state = (int) get_option( 'login_custom_image_state' );
		$login_custom_image_id    = (int) get_option( 'login_custom_image_id' );
		if ( $login_custom_image_state < 0 || $login_custom_image_state > 2 ) {
			$login_custom_image_state = 0;
		}
		if ( $login_custom_image_id ) {
			$login_custom_image_src = wp_get_attachment_image_url( $login_custom_image_id, 'full' );
			$alt_text = get_post_meta( $login_custom_image_id, '_wp_attachment_image_alt', true );
		} else {
			$custom_logo_id = get_theme_mod( 'custom_logo' );
			$login_custom_image_src = wp_get_attachment_image_url( $custom_logo_id , 'full' );
			$alt_text = get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true );
		}

		$class = 'attachment-media-view';
		if ( ! empty( $login_custom_image_src ) ) {
			$class = 'attachment-media-view attachment-media-view-image landscape';
		}
		?>

		<span class="customize-control-title">
			<?php esc_html_e( $this->label ); ?>
		</span>
		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>
		<div class="<?php esc_attr_e( $class ); ?>">

			<?php
			if ( empty( $login_custom_image_src ) ) {
				if ( current_user_can( 'upload_files' ) ) {
					?>
					<div class="actions">
						<button class="upload-button button-add-media"
							type="button"
							data-required-type="<?php esc_attr_e( $this->mime_type ); ?>"
						>
							<?php esc_html_e( $this->button_labels['select'] ); ?>
						</button>
					</div>
					<?php
				}
			} else {
				?>
				<div class="thumbnail thumbnail-image">
					<img class="attachment-thumb"
						src="<?php echo esc_url( $login_custom_image_src ); ?>"
						draggable="false"
						alt="<?php esc_attr_e( $alt_text ); ?>"
					>
				</div>
				<div class="actions">
					<?php
					if ( current_user_can( 'upload_files' ) ) {
						?>
						<button type="button" class="button remove-button">
							<?php esc_html_e( $this->button_labels['remove'] ); ?>
						</button>
						<button type="button"
							class="button"
							data-required-type="<?php esc_attr_e( $this->mime_type ); ?>"
						>
							<?php esc_html_e( $this->button_labels['change'] ); ?>
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
}
