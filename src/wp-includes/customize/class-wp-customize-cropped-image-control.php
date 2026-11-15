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
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$value = $this->value();
		$attachment_id = 0;
		$image_src = '';
		$alt_text = '';

		// Support both attachment IDs and image URLs.
		if ( is_numeric( $value ) ) {
			$attachment_id = absint( $value );

			if ( $attachment_id ) {
				$image_src = wp_get_attachment_image_url( $attachment_id, 'full' );
			}
		} elseif ( is_string( $value ) && '' !== $value ) {
			$image_src     = $value;
			$attachment_id = attachment_url_to_postid( $value );
		}

		// A URL can still be displayed when no attachment ID is found.
		if ( $attachment_id ) {
			$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		}

		$class = 'attachment-media-view';
		if ( ! empty( $image_src ) ) {
			$class = 'attachment-media-view attachment-media-view-image landscape';
		}
		?>

		<span class="customize-control-title">
			<?php echo esc_html( $this->label ); ?>
		</span>
		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>
		<div class="<?php echo esc_attr( $class ); ?>">
			<input type="hidden" value="<?php echo esc_attr( $value ); ?>">

			<?php
			if ( empty( $image_src ) ) {
				if ( current_user_can( 'upload_files' ) ) {
					?>

					<div class="actions"
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php echo esc_attr( $this->button_labels['select'] ); ?>"
						data-full="<?php echo esc_attr( $this->button_labels['change'] ); ?>"
					>
						<button class="upload-button button-add-media select-button" type="button">
							<?php echo esc_html( $this->button_labels['select'] ); ?>
						</button>
					</div>

					<?php
				}
			} else {
				?>

				<div class="thumbnail thumbnail-image">
					<img class="attachment-thumb"
						src="<?php echo esc_url( $image_src ); ?>"
						draggable="false"
						alt="<?php echo esc_attr( $alt_text ); ?>"
					>
				</div>

				<?php
				if ( current_user_can( 'upload_files' ) ) {
					?>

					<div class="actions"
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php echo esc_attr( $this->button_labels['select'] ); ?>"
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
			}
			?>

		</div>
		<?php
	}
}
