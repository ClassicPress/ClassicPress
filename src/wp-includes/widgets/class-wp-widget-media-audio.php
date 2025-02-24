<?php
/**
 * Widget API: WP_Widget_Media_Audio class
 *
 * @package ClassicPress
 * @subpackage Widgets
 * @since 4.8.0
 */

/**
 * Core class that implements an audio widget.
 *
 * @since 4.8.0
 *
 * @see WP_Widget_Media
 * @see WP_Widget
 */
class WP_Widget_Media_Audio extends WP_Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 */
	public function __construct() {
		parent::__construct(
			'media_audio',
			__( 'Audio' ),
			array(
				'description' => __( 'Displays an audio player.' ),
				'mime_type'   => 'audio',
			)
		);
	}

	/**
	 * Get schema for properties of a widget instance (item).
	 *
	 * @since 4.8.0
	 *
	 * @see WP_REST_Controller::get_item_schema()
	 * @see WP_REST_Controller::get_additional_fields()
	 * @link https://core.trac.wordpress.org/ticket/35574
	 *
	 * @return array Schema for properties.
	 */
	public function get_instance_schema() {
		$schema = array(
			'preload' => array(
				'type'        => 'string',
				'enum'        => array( 'none', 'auto', 'metadata' ),
				'default'     => 'none',
				'description' => __( 'Preload' ),
			),
			'loop'    => array(
				'type'        => 'boolean',
				'default'     => false,
				'description' => __( 'Loop' ),
			),
		);

		foreach ( wp_get_audio_extensions() as $audio_extension ) {
			$schema[ $audio_extension ] = array(
				'type'        => 'string',
				'default'     => '',
				'format'      => 'uri',
				/* translators: %s: Audio extension. */
				'description' => sprintf( __( 'URL to the %s audio source file' ), $audio_extension ),
			);
		}

		return array_merge( $schema, parent::get_instance_schema() );
	}

	/**
	 * Render the media on the frontend.
	 *
	 * @since 4.8.0
	 *
	 * @param array $instance Widget instance props.
	 */
	public function render_media( $instance ) {
		$instance   = array_merge( wp_list_pluck( $this->get_instance_schema(), 'default' ), $instance );
		$attachment = null;

		if ( $this->is_attachment_with_mime_type( $instance['attachment_id'], $this->widget_options['mime_type'] ) ) {
			$attachment = get_post( $instance['attachment_id'] );
		}

		if ( $attachment ) {
			$src = wp_get_attachment_url( $attachment->ID );
		} else {
			$src = $instance['url'];
		}

		echo wp_audio_shortcode(
			array_merge(
				$instance,
				compact( 'src' )
			)
		);
	}

	/**
	 * Back-end widget form.
	 *
	 * @since CP-2.5.0
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$attachment_id = ! empty( $instance['attachment_id'] ) ? $instance['attachment_id'] : 0;
		$preload       = ! empty( $instance['preload'] ) ? $instance['preload'] : 'none';
		$loop          = ! empty( $instance['loop'] ) ? $instance['loop'] : '';
		$mp3           = ! empty( $instance['mp3'] ) ? $instance['mp3'] : '';
		$ogg           = ! empty( $instance['ogg'] ) ? $instance['ogg'] : '';
		$flac          = ! empty( $instance['flac'] ) ? $instance['flac'] : '';
		$m4a           = ! empty( $instance['m4a'] ) ? $instance['m4a'] : '';
		$wav           = ! empty( $instance['wav'] ) ? $instance['wav'] : '';
		$url           = ! empty( $instance['url'] ) ? $instance['url'] : '';
		$nonce         = wp_create_nonce( 'audio_editor-' . $attachment_id );

		if ( $url === '' ) {
			if ( $attachment_id ) {
				$url = wp_get_attachment_url( $attachment_id );
			} elseif ( $mp3 ) {
				$url = $mp3;
			} elseif ( $ogg ) {
				$url = $ogg;
			} elseif ( $flac ) {
				$url = $flac;
			} elseif ( $m4a ) {
				$url = $m4a;
			} elseif ( $wav ) {
				$url = $wav;
			}
		}
		?>

		<div class="media-widget-control selected">	
			<fieldset>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" class="widefat" type="text" value="<?php echo esc_attr( $title ); ?>">
			</fieldset>

			<?php
			if ( $url ) {
			?>
				<div class="media-widget-preview media_audio populated"><?php echo wp_audio_shortcode( array( 'src' => $url ) ); ?></div>

				<fieldset class="media-widget-buttons">
					<button type="button" class="button edit-media selected" data-edit-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Edit Audio' ); ?></button>
					<button type="button" class="button change-media select-media selected"><?php esc_html_e( 'Replace Audio' ); ?></button>
				</fieldset>

			<?php
			} else {
			?>

				<div class="media-widget-preview media_audio">
					<div class="attachment-media-view">
						<button type="button" class="select-media button-add-media"><?php esc_html_e( 'Add Audio' ); ?></button>
					</div>
				</div>

			<?php
			}
			?>

			<input id="<?php echo esc_attr( $this->get_field_id( 'preload' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'preload' ) ); ?>" type="hidden" data-property="preload" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $preload ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'loop' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'loop' ) ); ?>" type="hidden" data-property="loop" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $loop ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'mp3' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mp3' ) ); ?>" type="hidden" data-property="mp3" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $mp3 ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'ogg' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ogg' ) ); ?>" type="hidden" data-property="ogg" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $ogg ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'flac' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'flac' ) ); ?>" type="hidden" data-property="flac" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $flac ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'm4a' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'm4a' ) ); ?>" type="hidden" data-property="m4a" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $m4a ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'wav' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'wav' ) ); ?>" type="hidden" data-property="wav" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $wav ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'attachment_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'attachment_id' ) ); ?>" type="hidden" data-property="attachment_id" class="media-widget-instance-property" value="<?php echo esc_attr( $attachment_id ); ?>">			
			<input id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="hidden" data-property="url" class="media-widget-instance-property" value="<?php echo esc_url( $url ); ?>">

		</div>

		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @since CP-2.5.0
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title']         = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['attachment_id'] = ! empty( $new_instance['attachment_id'] ) ? absint( $new_instance['attachment_id'] ) : 0;
		$instance['preload']       = ! empty( $new_instance['preload'] ) ? sanitize_text_field( $new_instance['preload'] ) : '';
		$instance['loop']          = ! empty( $new_instance['loop'] ) ? sanitize_text_field( $new_instance['loop'] ) : '';
		$instance['mp3']           = ! empty( $new_instance['mp3'] ) ? sanitize_text_field( $new_instance['mp3'] ) : '';
		$instance['ogg']           = ! empty( $new_instance['ogg'] ) ? sanitize_text_field( $new_instance['ogg'] ) : '';
		$instance['flac']          = ! empty( $new_instance['flac'] ) ? sanitize_text_field( $new_instance['flac'] ) : '';
		$instance['m4a']           = ! empty( $new_instance['m4a'] ) ? sanitize_text_field( $new_instance['m4a'] ) : '';
		$instance['wav']           = ! empty( $new_instance['wav'] ) ? sanitize_text_field( $new_instance['wav'] ) : '';
		$instance['url']           = ! empty( $new_instance['url'] ) ? sanitize_url( $new_instance['url'] ) : '';

		return $instance;
	}

	/**
	 * Enqueue preview scripts.
	 *
	 * These scripts normally are enqueued just-in-time when an audio shortcode is used.
	 * In the customizer, however, widgets can be dynamically added and rendered via
	 * selective refresh, and so it is important to unconditionally enqueue them in
	 * case a widget does get added.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_preview_scripts() {
		/** This filter is documented in wp-includes/media.php */
		if ( 'mediaelement' === apply_filters( 'wp_audio_shortcode_library', 'mediaelement' ) ) {
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_script( 'wp-mediaelement' );
		}
	}

	/**
	 * Loads the required media files for the media manager and scripts for media widgets.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );

		wp_enqueue_script( 'media-audio-widget' );
		wp_localize_script(
			'media-audio-widget',
			'AUDIO_WIDGET',
			array(
				'no_audio_selected'          => __( 'No audio selected' ),
				'add_audio'                  => _x( 'Add Audio', 'label for button in the audio widget' ),
				'add_to_widget'              => __( 'Add to widget' ),
				'replace_audio'              => _x( 'Replace Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'edit_audio'                 => _x( 'Edit Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'missing_attachment'         => sprintf(
					/* translators: %s: URL to media library. */
					__( 'That audio file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.' ),
					esc_url( admin_url( 'upload.php' ) )
				),
				/* translators: %d: Widget count. */
				'media_library_state_multi'  => _n_noop( 'Audio Widget (%d)', 'Audio Widget (%d)' ),
				'media_library_state_single' => __( 'Audio Widget' ),
				'unsupported_file_type'      => __( 'Looks like this is not the correct kind of file. Please link to an audio file instead.' ),
				'insert_from_url'            => __( 'Insert from URL' ),
				'remove_audio_source'        => __( 'Remove audio source' ),
			)
		);
	}
}
