<?php
/**
 * Customize API: WP_Customize_Header_Image_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Header Image Control class.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Image_Control
 */
class WP_Customize_Header_Image_Control extends WP_Customize_Image_Control {

	/**
	 * Customize control type.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $type = 'header';

	/**
	 * Uploaded header images.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $uploaded_headers;

	/**
	 * Default header images.
	 *
	 * @since 3.9.0
	 * @var string
	 */
	public $default_headers;

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 */
	public function __construct( $manager ) {
		parent::__construct(
			$manager,
			'header_image',
			array(
				'label'    => __( 'Header Image' ),
				'settings' => array(
					'default' => 'header_image',
					'data'    => 'header_image_data',
				),
				'section'  => 'header_image',
				'removed'  => 'remove-header',
				'get_url'  => 'get_header_image',
			)
		);
	}

	/**
	 */
	public function enqueue() {
		wp_enqueue_media();
		wp_enqueue_script( 'customize-views' );

		$this->prepare_control();

		wp_localize_script(
			'customize-views',
			'_wpCustomizeHeader',
			array(
				'data'     => array(
					'width'         => absint( get_theme_support( 'custom-header', 'width' ) ),
					'height'        => absint( get_theme_support( 'custom-header', 'height' ) ),
					'flex-width'    => absint( get_theme_support( 'custom-header', 'flex-width' ) ),
					'flex-height'   => absint( get_theme_support( 'custom-header', 'flex-height' ) ),
					'currentImgSrc' => $this->get_current_image_src(),
				),
				'nonces'   => array(
					'add'    => wp_create_nonce( 'header-add' ),
					'remove' => wp_create_nonce( 'header-remove' ),
				),
				'uploads'  => $this->uploaded_headers,
				'defaults' => $this->default_headers,
			)
		);

		parent::enqueue();
	}

	/**
	 * @global Custom_Image_Header $custom_image_header
	 *
	 * @since CP-2.7.0
	 *
	 * Redundant since CP_2.7.0
	 */
	public function prepare_control() {}

	/**
	 * Redundant JS templates.
	 *
	 * @since CP-2.7.0
	 */
	public function print_header_image_template() {}

	/**
	 * @return string|void
	 */
	public function get_current_image_src() {
		$src = $this->value();
		if ( isset( $this->get_url ) ) {
			$src = call_user_func( $this->get_url, $src );
			return $src;
		}
	}

	/**
	 * Render the template via PHP.
	 *
	 * @since CP-2.7.0
	 */
	public function render_content() {
		global $custom_image_header;

		// Process default headers and uploaded headers.
		$custom_image_header->process_default_headers();
		$this->default_headers  = $custom_image_header->get_default_header_images();
		$this->uploaded_headers = $custom_image_header->get_uploaded_header_images();

		$visibility   = $this->get_current_image_src() ? '' : ' style="display:none" ';
		$width        = absint( get_theme_support( 'custom-header', 'width' ) );
		$height       = absint( get_theme_support( 'custom-header', 'height' ) );
		$header_image = get_header_image(); // Current header image
		?>
		<div class="customize-control-content">
			<?php
			if ( current_theme_supports( 'custom-header', 'video' ) ) {
				echo '<span class="customize-control-title">' . $this->label . '</span>';
			}
			?>
			<div class="customize-control-notifications-container"></div>
			<p class="customizer-section-intro customize-control-description">
				<?php
				if ( current_theme_supports( 'custom-header', 'video' ) ) {
					_e( 'Click &#8220;Add new image&#8221; to upload an image file from your computer. Your theme works best with an image that matches the size of your video &#8212; you&#8217;ll be able to crop your image once you upload it for a perfect fit.' );
				} elseif ( $width && $height ) {
					printf(
						/* translators: %s: Header size in pixels. */
						__( 'Click &#8220;Add new image&#8221; to upload an image file from your computer. Your theme works best with an image with a header size of %s pixels &#8212; you&#8217;ll be able to crop your image once you upload it for a perfect fit.' ),
						sprintf( '<strong>%s &times; %s</strong>', $width, $height )
					);
				} elseif ( $width ) {
					printf(
						/* translators: %s: Header width in pixels. */
						__( 'Click &#8220;Add new image&#8221; to upload an image file from your computer. Your theme works best with an image with a header width of %s pixels &#8212; you&#8217;ll be able to crop your image once you upload it for a perfect fit.' ),
						sprintf( '<strong>%s</strong>', $width )
					);
				} else {
					printf(
						/* translators: %s: Header height in pixels. */
						__( 'Click &#8220;Add new image&#8221; to upload an image file from your computer. Your theme works best with an image with a header height of %s pixels &#8212; you&#8217;ll be able to crop your image once you upload it for a perfect fit.' ),
						sprintf( '<strong>%s</strong>', $height )
					);
				}
				?>
			</p>
			<div class="current">
				<label for="header_image-button">
					<span class="customize-control-title">
						<?php _e( 'Current header' ); ?>
					</span>
				</label>
				<div class="container">
				</div>
			</div>
			<div class="actions">
				<?php
				if ( current_user_can( 'upload_files' ) ) {
					?>
					<button type="button"<?php echo $visibility; ?> class="button remove" aria-label="<?php esc_attr_e( 'Hide header image' ); ?>">
						<?php _e( 'Hide image' ); ?>
					</button>
					<button type="button" class="upload-button button new" id="header_image-button" aria-label="<?php esc_attr_e( 'Add new header image' ); ?>" <?php $this->link(); ?>>
						<?php _e( 'Add image' ); ?>
					</button>
					<?php
				}
				?>
			</div>
			<div class="choices">

				<?php
				if ( ! empty( $this->uploaded_headers ) ) {
					?>

					<span class="customize-control-title header-previously-uploaded">
						<?php _ex( 'Previously uploaded', 'custom headers' ); ?>
					</span>
					<div class="uploaded">
						<div class="list">
							<?php
							foreach ( array_slice( $this->uploaded_headers, 0, 4 ) as $header ) {
								?>
								<div class="header-image-item">
									<img src="<?php echo esc_url( $header['url'] ); ?>" 
										alt="<?php esc_attr_e( $header['label'] ); ?>"
									>
									<span class="title">
										<?php esc_html_e( $header['label'] ); ?>
									</span>
									<button class="choice" data-customize-url="<?php echo esc_url( $header['url'] ); ?>">
										<?php esc_html_e( $this->button_labels['frame_button'] ); ?>
									</button>
								</div>
								<?php
							}
							?>
						</div>
					</div>

					<?php
				}
				if ( ! empty( $this->default_headers ) ) {
					?>

					<span class="customize-control-title header-default">
						<?php _ex( 'Suggested', 'custom headers' ); ?>
					</span>
					<div class="default">
						<div class="list">
							<?php
							foreach ( array_slice( $this->default_headers, 0, 4 ) as $header ) {
								?>
								<div class="default-header-image">
									<img src="<?php echo esc_url( $header['url'] ); ?>">
									<span>
										<?php echo esc_html( $header['label'] ); ?>
									</span>
									<button class="choice" data-customize-url="<?php echo esc_url( $header['url'] ); ?>">
										<?php esc_html_e( $this->button_labels['frame_button'] ); ?>
									</button>
								</div>
								<?php
							}
							?>
						</div>
					</div>

					<?php
				}
				?>

			</div>
		</div>

		<?php
	}
}
