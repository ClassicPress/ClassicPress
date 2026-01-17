<?php
/**
 * Customize API: WP_Customize_Media_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Media Control class.
 *
 * @since 4.2.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Media_Control extends WP_Customize_Control {
	/**
	 * Control type.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $type = 'media';

	/**
	 * Media control mime type.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Button labels.
	 *
	 * @since 4.2.0
	 * @var array
	 */
	public $button_labels = array();

	/**
	 * Constructor.
	 *
	 * @since 4.1.0
	 * @since 4.2.0 Moved from WP_Customize_Upload_Control.
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

		$this->button_labels = wp_parse_args( $this->button_labels, $this->get_default_button_labels() );
	}

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Moved from WP_Customize_Upload_Control.
	 */
	public function enqueue() {
		wp_enqueue_media();
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 3.4.0
	 * @since 4.2.0 Moved from WP_Customize_Upload_Control.
	 *
	 * @see WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['label']         = html_entity_decode( $this->label, ENT_QUOTES, get_bloginfo( 'charset' ) );
		$this->json['mime_type']     = $this->mime_type;
		$this->json['button_labels'] = $this->button_labels;
		$this->json['canUpload']     = current_user_can( 'upload_files' );

		$value = $this->value();

		if ( is_object( $this->setting ) ) {
			if ( $this->setting->default ) {
				// Fake an attachment model - needs all fields used by template.
				// Note that the default value must be a URL, NOT an attachment ID.
				$ext  = substr( $this->setting->default, -3 );
				$type = in_array( $ext, array( 'jpg', 'png', 'gif', 'bmp', 'webp', 'avif' ), true ) ? 'image' : 'document';

				$default_attachment = array(
					'id'    => 1,
					'url'   => $this->setting->default,
					'type'  => $type,
					'icon'  => wp_mime_type_icon( $type ),
					'title' => wp_basename( $this->setting->default ),
				);

				if ( 'image' === $type ) {
					$default_attachment['sizes'] = array(
						'full' => array( 'url' => $this->setting->default ),
					);
				}

				$this->json['defaultAttachment'] = $default_attachment;
			}

			if ( $value && $this->setting->default && $value === $this->setting->default ) {
				// Set the default as the attachment.
				$this->json['attachment'] = $this->json['defaultAttachment'];
			} elseif ( $value ) {
				$this->json['attachment'] = wp_prepare_attachment_for_js( $value );
			}
		}
	}

	/**
	 * Render content for this control from PHP.
	 *
	 * @since CP-2.7.0
	 * @since 4.2.0 Moved from WP_Customize_Upload_Control.
	 */
	public function render_content() {
		$attachment_id  = (int) $this->value(); // Media control stores attachment ID
		$description_id = uniqid( 'customize-media-control-description' );
		$described_by   = $this->description ? ' aria-describedby="' . description_id . '" ' : '';
		$default_id     = (int) $this->setting->default;

		if ( $attachment_id ) {
			$title       = get_the_title( $attachment_id );
			$mime_type   = get_post_mime_type( $attachment_id );
			$icon        = wp_mime_type_icon( $mime_type );
			$src         = wp_get_attachment_url( $attachment_id );
			$icon_html   = wp_get_attachment_image( $attachment_id, 'thumbnail', true );
			$meta        = wp_get_attachment_metadata( $attachment_id );
			$orientation = ( $meta['height'] > $meta['width'] ) ? 'portrait' : 'landscape';
			$sizes       = $meta['sizes'] ?? []; // Array of thumbnail/medium/large
			$alt_text    = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			$album       = get_post_meta( $attachment_id, 'album', true );
			$artist      = get_post_meta( $attachment_id, 'artist', true );
		}
		?>

		<span class="customize-control-title">
			<?php esc_html_e( $this->label ); ?>
		</span>
		<div class="customize-control-notifications-container">
			<ul></ul>
		</div>

		<?php
		if ( $this->description ) {
			?>
			<span id="<?php esc_attr_e( $description_id ); ?>" class="description customize-control-description">
				<?php echo wp_kses_post( $this->description ); ?>
			</span>
			<?php
		}

		if ( $attachment_id && $src ) {
			?>
			<div class="attachment-media-view attachment-media-view-<?php esc_attr_e( $mime_type ); ?> <?php esc_attr_e( $orientation ); ?>">
				<div class="thumbnail thumbnail-<?php esc_attr_e( $mime_type ); ?>">
					<?php
					if ( 'image' === $mime_type && $sizes && $sizes['medium'] ) {
						$medium_src = wp_get_attachment_image_url( $attachment_id, 'medium' );
						?>
						<img class="attachment-thumb"
							src="<?php echo esc_url( $medium_src ); ?>"
							draggable="false"
							alt="<?php esc_attr_e( $alt_text ); ?>"
						>
						<?php
					} elseif ( 'image' === $mime_type && $sizes && $sizes['full'] ) {
						$full_src = wp_get_attachment_image_url( $attachment_id, 'full' );
						?>						
						<img class="attachment-thumb"
							src="<?php echo esc_url( $full_src ); ?>"
							draggable="false"
							alt="<?php esc_attr_e( $alt_text ); ?>"
						>
						<?php
					} elseif ( 'audio' === $mime_type ) {
						if ( $src && $src !== $icon ) {
							?>
							<img src="<?php echo esc_url( $src ); ?>"
								class="thumbnail"
								draggable="false"
								alt="<?php esc_attr_e( $alt_text ); ?>"
							>
							<?php
						} else {
							?>
							<img src="<?php esc_attr_e( $icon ); ?>"
								class="attachment-thumb type-icon"
								draggable="false"
								alt="<?php esc_attr_e( $alt_text ); ?>"
							>
							<?php
						}
						?>
						<p class="attachment-meta attachment-meta-title">
							&#8220;<?php esc_html_e( $title ); ?>&#8221;
						</p>
						<?php
						if ( $album ) {
							?>
							<p class="attachment-meta">
								<em><?php esc_html_e( $album ); ?></em>
							</p>
							<?php
						}
						if ( $artist ) {
							?>
							<p class="attachment-meta">
								<?php esc_html_e( $artist ); ?>
							</p>
							<?php
						}
						?>
						<audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
							<source type="<?php esc_attr_e( $mime_type ); ?>" src="<?php echo esc_url( $src ); ?>">
						</audio>
						<?php
					} elseif ( 'video' === $mime_type ) {
						?>
						<div class="wp-media-wrapper wp-video">
							<video controls class="wp-video-shortcode" preload="metadata"
								<?php
								if ( $src && $src !== $icon ) {
									?>
									poster="<?php echo esc_url( $src ); ?>"
									<?php
								}
								?>
							>
								<source type="<?php esc_attr_e( $mime_type ); ?>" src="<?php echo esc_url( $src ); ?>">
							</video>
						</div>
						<?php
					} else {
						?>
						<img class="attachment-thumb type-icon icon"
							src="<?php esc_attr_e( $icon ); ?>"
							draggable="false"
							alt="<?php esc_attr_e( $alt_text ); ?>"
						>
						<p class="attachment-title">
							<?php esc_html_e( $title ); ?>
						</p>
						<?php
					}
					?>
				</div>
				<div class="actions">
					<?php
					if ( current_user_can( 'upload_files' ) ) {
						?>
						<button type="button" class="button remove-button">
							<?php esc_html_e( $this->button_labels['remove'] ); ?>
						</button>
						<button type="button"
							class="button upload-button control-focus"<?php esc_attr_e( $described_by ); ?>
							data-required-type="<?php esc_attr_e( $this->mime_type ); ?>"
						>
							<?php esc_html_e( $this->button_labels['change'] ); ?>
						</button>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="attachment-media-view">
				<div class="actions">
					<?php
					if ( current_user_can( 'upload_files' ) ) {
						?>
						<button type="button"
							class="upload-button button-add-media"<?php esc_attr_e( $described_by ); ?>
							data-required-type="<?php esc_attr_e( $this->mime_type ); ?>"
						>
							<?php esc_html_e( $this->button_labels['select'] ); ?>
						</button>
						<?php
						if ( $default_id ) {
							?>
							<button type="button" class="button default-button">
								<?php esc_html_e( $this->button_labels['change'] ); ?>
							</button>
							<?php
						}
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Redundant JS template.
	 *
	 * @since CP-2.7.0
	 */
	public function content_template() {}

	/**
	 * Get default button labels.
	 *
	 * Provides an array of the default button labels based on the mime type of the current control.
	 *
	 * @since 4.9.0
	 *
	 * @return string[] An associative array of default button labels keyed by the button name.
	 */
	public function get_default_button_labels() {
		// Get just the mime type and strip the mime subtype if present.
		$mime_type = ! empty( $this->mime_type ) ? strtok( ltrim( $this->mime_type, '/' ), '/' ) : 'default';

		switch ( $mime_type ) {
			case 'video':
				return array(
					'select'       => __( 'Select video' ),
					'change'       => __( 'Change video' ),
					'default'      => __( 'Default' ),
					'remove'       => __( 'Remove' ),
					'placeholder'  => __( 'No video selected' ),
					'frame_title'  => __( 'Select video' ),
					'frame_button' => __( 'Choose video' ),
				);
			case 'audio':
				return array(
					'select'       => __( 'Select audio' ),
					'change'       => __( 'Change audio' ),
					'default'      => __( 'Default' ),
					'remove'       => __( 'Remove' ),
					'placeholder'  => __( 'No audio selected' ),
					'frame_title'  => __( 'Select audio' ),
					'frame_button' => __( 'Choose audio' ),
				);
			case 'image':
				return array(
					'select'       => __( 'Select image' ),
					'site_icon'    => __( 'Select Site Icon' ),
					'change'       => __( 'Change image' ),
					'default'      => __( 'Default' ),
					'remove'       => __( 'Remove' ),
					'placeholder'  => __( 'No image selected' ),
					'frame_title'  => __( 'Select image' ),
					'frame_button' => __( 'Choose image' ),
				);
			default:
				return array(
					'select'       => __( 'Select file' ),
					'change'       => __( 'Change file' ),
					'default'      => __( 'Default' ),
					'remove'       => __( 'Remove' ),
					'placeholder'  => __( 'No file selected' ),
					'frame_title'  => __( 'Select file' ),
					'frame_button' => __( 'Choose file' ),
				);
		} // End switch().
	}
}
