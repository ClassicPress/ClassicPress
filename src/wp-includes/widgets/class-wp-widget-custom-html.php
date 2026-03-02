<?php
/**
 * Widget API: WP_Widget_Custom_HTML class
 *
 * @package ClassicPress
 * @subpackage Widgets
 * @since 4.8.1
 */

/**
 * Core class used to implement a Custom HTML widget.
 *
 * @since 4.8.1
 *
 * @see WP_Widget
 */
class WP_Widget_Custom_HTML extends WP_Widget {

	/**
	 * Whether or not the widget has been registered yet.
	 *
	 * @since 4.9.0
	 * @var bool
	 */
	protected $registered = false;

	/**
	 * Default instance.
	 *
	 * @since 4.8.1
	 * @var array
	 */
	protected $default_instance = array(
		'title'   => '',
		'content' => '',
	);

	/**
	 * Sets up a new Custom HTML widget instance.
	 *
	 * @since 4.8.1
	 */
	public function __construct() {
		$widget_ops  = array(
			'classname'                   => 'widget_custom_html',
			'description'                 => __( 'Arbitrary HTML code.' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		$control_ops = array(
			'width'  => 400,
			'height' => 350,
		);
		parent::__construct( 'custom_html', __( 'Custom HTML' ), $widget_ops, $control_ops );
	}

	/**
	 * Add hooks for enqueueing assets when registering all widget instances of this widget class.
	 *
	 * @since 4.9.0
	 *
	 * @param int $number Optional. The unique order number of this widget instance
	 *                    compared to other instances of the same class. Default -1.
	 */
	public function _register_one( $number = -1 ) {
		parent::_register_one( $number );
		if ( $this->registered ) {
			return;
		}
		$this->registered = true;

		// Note that the widgets component in the customizer will also do
		// the 'admin_print_scripts-widgets.php' action in WP_Customize_Widgets::print_scripts().
		add_action( 'admin_print_scripts-widgets.php', array( $this, 'enqueue_admin_scripts' ) );

		// Note this action is used to ensure the help text is added to the end.
		add_action( 'admin_head-widgets.php', array( 'WP_Widget_Custom_HTML', 'add_help_text' ) );
	}

	/**
	 * Filters gallery shortcode attributes.
	 *
	 * Prevents all of a site's attachments from being shown in a gallery displayed on a
	 * non-singular template where a $post context is not available.
	 *
	 * @since 4.9.0
	 *
	 * @param array $attrs Attributes.
	 * @return array Attributes.
	 */
	public function _filter_gallery_shortcode_attrs( $attrs ) {
		if ( ! is_singular() && empty( $attrs['id'] ) && empty( $attrs['include'] ) ) {
			$attrs['id'] = -1;
		}
		return $attrs;
	}

	/**
	 * Outputs the content for the current Custom HTML widget instance.
	 *
	 * @since 4.8.1
	 *
	 * @global WP_Post $post Global post object.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Custom HTML widget instance.
	 */
	public function widget( $args, $instance ) {
		global $post;

		// Override global $post so filters (and shortcodes) apply in a consistent context.
		$original_post = $post;
		if ( is_singular() ) {
			// Make sure post is always the queried object on singular queries (not from another sub-query that failed to clean up the global $post).
			$post = get_queried_object();
		} else {
			// Nullify the $post global during widget rendering to prevent shortcodes from running with the unexpected context on archive queries.
			$post = null;
		}

		// Prevent dumping out all attachments from the media library.
		add_filter( 'shortcode_atts_gallery', array( $this, '_filter_gallery_shortcode_attrs' ) );

		$instance = array_merge( $this->default_instance, $instance );

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		// Prepare instance data that looks like a normal Text widget.
		$simulated_text_widget_instance = array_merge(
			$instance,
			array(
				'text'   => isset( $instance['content'] ) ? $instance['content'] : '',
				'filter' => false, // Because wpautop is not applied.
				'visual' => false, // Because it wasn't created in TinyMCE.
			)
		);
		unset( $simulated_text_widget_instance['content'] ); // Was moved to 'text' prop.

		/** This filter is documented in wp-includes/widgets/class-wp-widget-text.php */
		$content = apply_filters( 'widget_text', $instance['content'], $simulated_text_widget_instance, $this );

		// Adds 'noopener' relationship, without duplicating values, to all HTML A elements that have a target.
		$content = wp_targeted_link_rel( $content );

		/**
		 * Filters the content of the Custom HTML widget.
		 *
		 * @since 4.8.1
		 *
		 * @param string                $content  The widget content.
		 * @param array                 $instance Array of settings for the current widget.
		 * @param WP_Widget_Custom_HTML $widget   Current Custom HTML widget instance.
		 */
		$content = apply_filters( 'widget_custom_html_content', $content, $instance, $this );

		// Restore post global.
		$post = $original_post;
		remove_filter( 'shortcode_atts_gallery', array( $this, '_filter_gallery_shortcode_attrs' ) );

		// Inject the Text widget's container class name alongside this widget's class name for theme styling compatibility.
		$args['before_widget'] = preg_replace( '/(?<=\sclass=["\'])/', 'widget_text ', $args['before_widget'] );

		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo '<div class="textwidget custom-html-widget">'; // The textwidget class is for theme styling compatibility.
		echo $content;
		echo '</div>';
		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Custom HTML widget instance.
	 *
	 * @since 4.8.1
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Settings to save or bool false to cancel saving.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array_merge( $this->default_instance, $old_instance );
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		if ( current_user_can( 'unfiltered_html' ) ) {
			$instance['content'] = $new_instance['content'];
		} else {
			$instance['content'] = wp_kses_post( $new_instance['content'] );
		}
		return $instance;
	}

	/**
	 * Loads the required scripts and styles for the widget control.
	 *
	 * @since CP-2.3.0
	 */
	public function enqueue_admin_scripts() {
		if ( ! isset( $this->number ) ) {
			return;
		}

		wp_enqueue_code_editor( array( 'type' => 'text/html' ) );
		if ( 'false' !== wp_get_current_user()->syntax_highlighting ) {
			wp_enqueue_script( 'wp-codemirror' );
		}
		wp_enqueue_script( 'custom-html-widgets' );
	}

	/**
	 * Outputs the Custom HTML widget settings form.
	 *
	 * @since CP-2.3.0
	 *
	 * @param array $instance Current instance.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->default_instance );
		?>

		<fieldset>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" class="widefat" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</fieldset>
		<fieldset>
			<label for="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>"><?php _e( 'Content:' ); ?></label>
			<textarea id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>" class="widefat code content" rows="16" cols="20"><?php echo esc_textarea( $instance['content'] ); ?></textarea>
		</fieldset>

		<?php
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$probably_unsafe_html = array( 'script', 'iframe', 'form', 'input', 'style' );
			$allowed_html         = wp_kses_allowed_html( 'post' );
			$disallowed_html      = array_diff( $probably_unsafe_html, array_keys( $allowed_html ) );
			if ( ! empty( $disallowed_html ) ) {
				?>

				<fieldset>
					<?php _e( 'Some HTML tags are not permitted, including:' ); ?>
					<code><?php echo implode( '</code>, <code>', $disallowed_html ); ?></code>
				</fieldset>

				<?php
			}
		}
	}

	/**
	 * Add help text to widgets admin screen.
	 *
	 * @since 4.9.0
	 */
	public static function add_help_text() {
		$screen = get_current_screen();

		$content  = '<p>';
		$content .= __( 'Use the Custom HTML widget to add arbitrary HTML code to your widget areas.' );
		$content .= '</p>';

		if ( 'false' !== wp_get_current_user()->syntax_highlighting ) {
			$content .= '<p>';
			$content .= sprintf(
				/* translators: 1: Link to user profile, 2: Additional link attributes, 3: Accessibility text. */
				__( 'The edit field automatically highlights code syntax. You can disable this in your <a href="%1$s" %2$s>user profile%3$s</a> to work in plain text mode.' ),
				esc_url( get_edit_profile_url() ),
				'class="external-link" target="_blank"',
				sprintf(
					'<span class="screen-reader-text"> %s</span>',
					/* translators: Hidden accessibility text. */
					__( '(opens in a new tab)' )
				)
			);
			$content .= '</p>';

			$content .= '<p id="editor-keyboard-trap-help-1">' . __( 'When using a keyboard to navigate:' ) . '</p>';
			$content .= '<ul>';
			$content .= '<li id="editor-keyboard-trap-help-2">' . __( 'In the editing area, the Tab key enters a tab character.' ) . '</li>';
			$content .= '<li id="editor-keyboard-trap-help-3">' . __( 'To move away from this area, press the Esc key followed by the Tab key.' ) . '</li>';
			$content .= '<li id="editor-keyboard-trap-help-4">' . __( 'Screen reader users: when in forms mode, you may need to press the Esc key twice.' ) . '</li>';
			$content .= '</ul>';
		}

		$screen->add_help_tab(
			array(
				'id'      => 'custom_html_widget',
				'title'   => __( 'Custom HTML Widget' ),
				'content' => $content,
			)
		);
	}
}
