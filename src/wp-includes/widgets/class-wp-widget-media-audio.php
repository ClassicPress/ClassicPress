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
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" class="widefat" type="text" value="<?php echo esc_attr( $title ); ?>">
			</fieldset>

			<?php
			if ( file_exists( $url ) ) {
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
			<input id="<?php echo esc_attr( $this->get_field_id( 'reset_widget' ) ); ?>" name="reset_widget" type="hidden" class="reset_widget" value="0">

		</div>

		<?php
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

		// Identify permitted audio file types
		$audio_file_types = array( 'mp3', 'ogg', 'flac', 'm4a', 'wav' );

		$user_id = get_current_user_id();
		$per_page = get_user_meta( $user_id, 'media_grid_per_page', true );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 80;
		}

		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'wp-mediaelement' );

		wp_enqueue_script( 'media-audio-widget' );
		wp_localize_script(
			'media-audio-widget',
			'AUDIO_WIDGET',
			array(
				'no_audio_selected'          => __( 'No audio selected' ),
				'add_audio'                  => _x( 'Add Audio', 'label for button in the audio widget' ),
				'add_media'                  => _x( 'Add Media', 'label for button in the audio widget' ),
				'add_to_widget'              => __( 'Add to widget' ),
				'replace_audio'              => _x( 'Replace Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'edit_audio'                 => _x( 'Edit Audio', 'label for button in the audio widget; should preferably not be longer than ~13 characters long' ),
				'missing_attachment'         => sprintf(
					/* translators: %s: URL to media library. */
					__( 'That audio file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.' ),
					esc_url( admin_url( 'upload.php' ) )
				),
				/* translators: %d: Widget count. */
				'media_library'              => __( 'Media Library' ),
				'audio_details'              => __( 'Audio Details' ),
				'details_button'             => __( 'Audio details' ),
				'media_library_state_multi'  => _n_noop( 'Audio Widget (%d)', 'Audio Widget (%d)' ),
				'media_library_state_single' => __( 'Audio Widget' ),
				'unsupported_file_type'      => __( 'Looks like this is not the correct kind of file. Please link to an audio file instead.' ),
				'wrong_url'                  => __( 'No file exists at that URL' ),
				'insert_from_url'            => __( 'Insert from URL' ),
				'remove_audio_source'        => __( 'Remove audio source' ),
				'artist'                     => __( 'Artist' ),
				'album'                      => __( 'Album' ),
				'cancel_edit'                => __( 'Cancel edit' ),
				'save'                       => __( 'Save' ),
				'media_items'                => __( 'media items' ),
				'includes_url'               => includes_url(),
				'per_page'                   => $per_page,
				'audio_file_types'           => $audio_file_types,
				'of'                         => __( 'of' ),
			)
		);
	}
}

/**
 * Renders the template for the modal content for the media audio widget
 *
 * @since CP-2.5.0
 *
 * @return string
 */
function cp_render_media_audio_template() {
	ob_start();
	?>

	<template id="tmpl-edit-audio-modal">
		<div id="audio-modal-content" style="padding: 2em;">
			<div class="modal-audio-details">
				<div class="audio-embed" style="width:100%;">
					<audio class="wp_audio_shortcode" controls="" style="width:100%;">
						<source src="<?php echo esc_url( includes_url() . 'js/mediaelement/blank.mp3' ); ?>" type="audio/mp3">
					</audio>
					<div class="setting" style="margin-top: 1em;">
						<label for="audio-details-source" class="name">MP3</label>
						<input type="text" id="audio-details-source" readonly="" data-setting="mp3" value="" style="width:100%;">
					</div>
					<button type="button" class="button-link remove-setting" style="display:block;color: #a00; padding: 5px 0;"><?php esc_html_e( 'Remove audio source' ); ?></button>

					<fieldset class="setting-group setting preload" style="margin: 1em 0;display: flex;">						
						<label for="preload" class="name" style="margin: 6px 1em 0 0;"><?php esc_html_e( 'Preload' ); ?></label>
						<select id="preload" name="link-type" data-setting="preload">
							<option value="auto"><?php esc_html_e( 'Auto' ); ?></option>
							<option value="metadata"><?php esc_html_e( 'Metadata' ); ?></option>
							<option value="" selected><?php esc_html_e( 'None' ); ?></option>
						</select>
					</fieldset>

					<div class="setting-group" style="margin: 1em 0;display: flex;">
						<div class="setting checkbox-setting">
							<input type="checkbox" id="audio-details-loop" data-setting="loop">
							<label for="audio-details-loop" class="checkbox-label"><?php esc_html_e( 'Loop' ); ?></label>
						</div>
					</div>
				</div>
			</div>
		</div>

		<footer class="widget-modal-footer">
			<div class="widget-modal-footer-buttons" style="padding-right: 2em;">
				<button id="audio-button-update" type="button" class="button media-button button-primary button-large media-button-select" disabled><?php esc_html_e( 'Update' ); ?></button>
			</div>
		</footer>
	</template>

	<?php
	return ob_get_clean();
}
