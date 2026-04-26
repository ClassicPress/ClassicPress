<?php
/**
 * Customize API: WP_Customize_Code_Editor_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.9.0
 */

/**
 * Customize Code Editor Control class.
 *
 * @since 4.9.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Code_Editor_Control extends WP_Customize_Control {

	/**
	 * Customize control type.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $type = 'code_editor';

	/**
	 * Type of code that is being edited.
	 *
	 * @since 4.9.0
	 * @var string
	 */
	public $code_type = '';

	/**
	 * Code editor settings.
	 *
	 * @see wp_enqueue_code_editor()
	 * @since 4.9.0
	 * @var array|false
	 */
	public $editor_settings = array();

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 4.9.0
	 */
	public function enqueue() {
		$this->editor_settings = wp_enqueue_code_editor(
			array_merge(
				array(
					'type'       => $this->code_type,
					'codemirror' => array(
						'indentUnit' => 2,
						'tabSize'    => 2,
					),
				),
				$this->editor_settings
			)
		);
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 4.9.0
	 *
	 * @see WP_Customize_Control::json()
	 *
	 * @return array Array of parameters passed to the JavaScript.
	 */
	public function json() {
		$json                    = parent::json();
		$json['editor_settings'] = $this->editor_settings;
		$json['input_attrs']     = $this->input_attrs;
		return $json;
	}

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$element_id_prefix = 'el' . uniqid();
		if ( $this->label ) {
			?>

			<label for="<?php echo esc_attr( $element_id_prefix . '_editor' ); ?>" class="customize-control-title screen-reader-text">
				<?php echo esc_html( $this->label ); ?>
			</label>

			<?php
		}

		$section_description = '';
		if ( $this->section ) {
			$section = $this->manager->get_section( $this->section );
			if ( $section && $section->description ) {
				$section_description = $section->description;
			}
		}

		if ( $this->description || $section->description ) {
			?>

			<div class="description customize-section-description" style="display:none;">
				<?php echo wp_kses_post( $this->description ? $this->description : $section_description ); ?>
			</div>

			<?php
		}
		?>

		<div class="customize-control-notifications-container">
			<ul></ul>
		</div>

		<?php
		// Merge default class "code" with any input_attrs from JS/JSON
		$input_attrs = (array) $this->input_attrs;
		if ( isset( $input_attrs['class'] ) ) {
			$input_attrs['class'] .= ' code';
		} else {
			$input_attrs['class'] = 'code';
		}
		?>

		<textarea id="<?php echo esc_attr( $element_id_prefix . '_editor' ); ?>" 

			<?php
			// Print all input attributes
			foreach ( $input_attrs as $key => $value ) {
				printf(
					' %s="%s"',
					esc_attr( $key ),
					esc_attr( $value )
				);
			}

			// Link the textarea to the setting, like $this->link() would
			$this->link();
			?>
		>
			<?php
			// Initial content value
			echo esc_textarea( $this->value() );
			?>

		</textarea>

		<?php
	}

	/**
	 * Redundant JS template.
	 *
	 * @since CP-2.8.0
	 */
	public function content_template() {}
}
