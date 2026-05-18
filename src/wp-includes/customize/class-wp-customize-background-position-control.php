<?php
/**
 * Customize API: WP_Customize_Background_Position_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.7.0
 */

/**
 * Customize Background Position Control class.
 *
 * @since 4.7.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Background_Position_Control extends WP_Customize_Control {

	/**
	 * Type.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $type = 'background_position';

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$options = array(
			array(
				'left top'   => array(
					'label' => __( 'Top Left' ),
					'icon'  => 'dashicons dashicons-arrow-left-alt',
				),
				'center top' => array(
					'label' => __( 'Top' ),
					'icon'  => 'dashicons dashicons-arrow-up-alt',
				),
				'right top'  => array(
					'label' => __( 'Top Right' ),
					'icon'  => 'dashicons dashicons-arrow-right-alt',
				),
			),
			array(
				'left center'   => array(
					'label' => __( 'Left' ),
					'icon'  => 'dashicons dashicons-arrow-left-alt',
				),
				'center center' => array(
					'label' => __( 'Center' ),
					'icon'  => 'background-position-center-icon',
				),
				'right center'  => array(
					'label' => __( 'Right' ),
					'icon'  => 'dashicons dashicons-arrow-right-alt',
				),
			),
			array(
				'left bottom'   => array(
					'label' => __( 'Bottom Left' ),
					'icon'  => 'dashicons dashicons-arrow-left-alt',
				),
				'center bottom' => array(
					'label' => __( 'Bottom' ),
					'icon'  => 'dashicons dashicons-arrow-down-alt',
				),
				'right bottom'  => array(
					'label' => __( 'Bottom Right' ),
					'icon'  => 'dashicons dashicons-arrow-right-alt',
				),
			),
		);
		if ( $this->label ) {
			?>
			<span class="customize-control-title">
				<?php echo esc_attr( $this->label ); ?>
			</span>
			<?php
		}

		if ( $this->description ) {
			?>
			<span class="description customize-control-description">
				<?php echo esc_attr( $this->description ); ?>
			</span>
			<?php
		}
		?>

		<div class="customize-control-content">
			<fieldset>
				<legend class="screen-reader-text">
					<span>
						<?php
						/* translators: Hidden accessibility text. */
						esc_html_e( 'Image Position' );
						?>
					</span>
				</legend>
				<div class="background-position-control">
				<?php foreach ( $options as $group ) : ?>
					<div class="button-group">
					<?php foreach ( $group as $value => $input ) : ?>
						<label>
							<input class="ui-helper-hidden-accessible" name="background-position" type="radio" value="<?php echo esc_attr( $value ); ?>">
							<span class="button display-options position">
								<span class="<?php echo esc_attr( $input['icon'] ); ?>" aria-hidden="true"></span>
							</span>
							<span class="screen-reader-text">
								<?php echo $input['label']; ?>
							</span>
						</label>
					<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				</div>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Redundant JS template.
	 *
	 * @since CP-2.8.0
	 */
	public function content_template() {}
}
