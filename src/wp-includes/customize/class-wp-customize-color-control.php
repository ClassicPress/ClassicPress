<?php
/**
 * Customize API: WP_Customize_Color_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Color Control class.
 *
 * @since 3.4.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Color_Control extends WP_Customize_Control {
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type = 'color';

	/**
	 * Statuses.
	 *
	 * @var array
	 */
	public $statuses;

	/**
	 * Mode.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $mode = 'full';

	/**
	 * Constructor.
	 *
	 * @since 3.4.0
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
		$this->statuses = array( '' => __( 'Default' ) );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Enqueue scripts/styles for the color picker.
	 *
	 * @since 3.4.0
	 */
	public function enqueue() {
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_style( 'wp-color-picker' );
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 3.4.0
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['statuses']     = $this->statuses;
		$this->json['defaultValue'] = $this->setting->default;
		$this->json['mode']         = $this->mode;
	}

	/**
	 * Render the control content from PHP on load.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$is_hue_slider = ( 'hue' === $this->mode );

		if ( $this->label ) {
			?>

			<span id="<?php echo esc_attr( $this->id ); ?>-label" class="customize-control-title">
				<?php echo esc_html( $this->label ); ?>
			</span>

			<?php
		}
		if ( $this->description ) {
			?>

			<span class="description customize-control-description">
				<?php echo wp_kses_post( $this->description ); ?>
			</span>

			<?php
		}
		?>

		<div class="customize-control-content">

			<?php
			if ( $is_hue_slider ) {
				?>

				<label for="cp-hue-slider" class="screen-reader-text">
					<?php esc_html_e( 'Custom Color Hue' ); ?>
				</label>
				<input type="range" min="0" max="359"
					id="cp-hue-slider"
					class="hue-slider"
					value="<?php echo esc_attr( $this->value() ); ?>"
					<?php $this->link(); ?>
				>

				<?php
			} else {
				$default_value = '#RRGGBB';
				$default_value_attr = '';
				$is_hue_slider = ( 'hue' === $this->mode );

				if ( $this->setting->default && is_string( $this->setting->default ) ) {
					$default_value = ( '#' !== substr( $this->setting->default, 0, 1 ) )
						? '#' . $this->setting->default
						: $this->setting->default;
					$default_value_attr = ' data-default-color="' . esc_attr( $default_value ) . '"';
				}

				// Allow for inconsistencies between themes over whether they include the # in a hex color string
				$color_value = str_replace( '#', '', $this->value() );
				?>

				<input class="color-picker-hex"
					type="text"
					aria-labelledby="<?php echo esc_attr( $this->id ); ?>-label"
					maxlength="7"
					placeholder="<?php echo esc_attr( $default_value ); ?>"
					value="#<?php echo esc_attr( $color_value ); ?>"
					<?php echo $default_value_attr; // data-default-color ?>
					data-coloris
					<?php $this->link(); ?>
				>

				<?php
			}
			?>

			</label>
		</div>
		<?php
	}

	/**
	 * JS template no longer required.
	 *
	 * @since CP-2.8.0
	 */
	public function content_template() {}
}
