<?php
/**
 * Customize API: WP_Customize_Background_Image_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Background Image Control class.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Image_Control
 */
class WP_Customize_Background_Image_Control extends WP_Customize_Image_Control {

	/**
	 * Customize control type.
	 *
	 * @since 4.1.0
	 * @var string
	 */
	public $type = 'background';

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
	 * @uses WP_Customize_Image_Control::__construct()
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 */
	public function __construct( $manager ) {
		parent::__construct(
			$manager,
			'background_image',
			array(
				'label'   => __( 'Background Image' ),
				'section' => 'background_image',
			)
		);
	}

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 4.1.0
	 */
	public function enqueue() {
		parent::enqueue();

		$custom_background = get_theme_support( 'custom-background' );
		wp_localize_script(
			'customize-controls',
			'_wpCustomizeBackground',
			array(
				'defaults' => ! empty( $custom_background[0] ) ? $custom_background[0] : array(),
				'nonces'   => array(
					'add' => wp_create_nonce( 'background-add' ),
				),
			)
		);
	}

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$background_img = get_theme_mod( 'background_image' ); //get_background_image();
		$attachment_id  = $background_img ? attachment_url_to_postid( $background_img ) : '';

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
		if ( $background_img ) {
			?>

			<div class="attachment-media-view attachment-media-view-image landscape">
				<div class="thumbnail thumbnail-image">
					<img class="attachment-thumb" src="<?php echo esc_url( $background_img ); ?>" draggable="false" alt="">					
				</div>
				<input type="hidden" value="<?php echo esc_attr( $attachment_id ); ?>">

				<?php
				if ( current_user_can( 'upload_files' ) ) {
					?>
					<div class="actions" <?php $this->link(); ?>
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php esc_attr_e( 'Select Image' ); ?>"
						data-full="<?php esc_attr_e( 'Change Image' ); ?>"
					>
						<button type="button" class="button remove-button">
							<?php esc_html_e( 'Remove' ); ?>
						</button>
						<button type="button" class="button control-focus select-button">
							<?php esc_html_e( 'Change Image' ); ?>
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

					<div class="actions" <?php $this->link(); ?>
						data-required-type="<?php echo esc_attr( $this->mime_type ); ?>"
						data-empty="<?php esc_attr_e( 'Select Image' ); ?>"
						data-full="<?php esc_attr_e( 'Change Image' ); ?>"
					>
						<button type="button" class="upload-button button select-button">
							<?php esc_html_e( 'Select Image' ); ?>
						</button>

						<?php
						if ( $this->setting->default ) {
							?>
							<button type="button" class="button default">
								<?php esc_html_e( 'Default' ); ?>
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
}
