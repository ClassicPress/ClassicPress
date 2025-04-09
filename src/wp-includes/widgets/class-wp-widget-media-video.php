<?php
/**
 * Widget API: WP_Widget_Media_Video class
 *
 * @package ClassicPress
 * @subpackage Widgets
 * @since 4.8.0
 */

/**
 * Core class that implements a video widget.
 *
 * @since 4.8.0
 *
 * @see WP_Widget_Media
 * @see WP_Widget
 */
class WP_Widget_Media_Video extends WP_Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 */
	public function __construct() {
		parent::__construct(
			'media_video',
			__( 'Video' ),
			array(
				'description' => __( 'Displays a video from the media library or from YouTube, Vimeo, or another provider.' ),
				'mime_type'   => 'video',
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
				'type'                  => 'string',
				'enum'                  => array( 'none', 'auto', 'metadata' ),
				'default'               => 'metadata',
				'description'           => __( 'Preload' ),
				'should_preview_update' => false,
			),
			'loop'    => array(
				'type'                  => 'boolean',
				'default'               => false,
				'description'           => __( 'Loop' ),
				'should_preview_update' => false,
			),
			'content' => array(
				'type'                  => 'string',
				'default'               => '',
				'sanitize_callback'     => 'wp_kses_post',
				'description'           => __( 'Tracks (subtitles, captions, descriptions, chapters, or metadata)' ),
				'should_preview_update' => false,
			),
		);

		foreach ( wp_get_video_extensions() as $video_extension ) {
			$schema[ $video_extension ] = array(
				'type'        => 'string',
				'default'     => '',
				'format'      => 'uri',
				/* translators: %s: Video extension. */
				'description' => sprintf( __( 'URL to the %s video source file' ), $video_extension ),
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

		$src = $instance['url'];
		if ( $attachment ) {
			$src = wp_get_attachment_url( $attachment->ID );
		}

		if ( empty( $src ) ) {
			return;
		}

		$youtube_pattern = '#^https?://(?:www\.)?(?:youtube\.com/watch|youtu\.be/)#';
		$vimeo_pattern   = '#^https?://(.+\.)?vimeo\.com/.*#';

		if ( $attachment || preg_match( $youtube_pattern, $src ) || preg_match( $vimeo_pattern, $src ) ) {
			add_filter( 'wp_video_shortcode', array( $this, 'inject_video_max_width_style' ) );

			echo wp_video_shortcode(
				array_merge(
					$instance,
					compact( 'src' )
				),
				$instance['content']
			);

			remove_filter( 'wp_video_shortcode', array( $this, 'inject_video_max_width_style' ) );
		} else {
			echo $this->inject_video_max_width_style( wp_oembed_get( $src ) );
		}
	}

	/**
	 * Inject max-width and remove height for videos too constrained to fit inside sidebars on frontend.
	 *
	 * @since 4.8.0
	 *
	 * @param string $html Video shortcode HTML output.
	 * @return string HTML Output.
	 */
	public function inject_video_max_width_style( $html ) {
		$html = preg_replace( '/\sheight="\d+"/', '', $html );
		$html = preg_replace( '/\swidth="\d+"/', '', $html );
		$html = preg_replace( '/(?<=width:)\s*\d+px(?=;?)/', '100%', $html );
		return $html;
	}

	/**
	 * Outputs the settings update form.
	 *
	 * Now renders immediately with PHP instead of just-in-time JavaScript
	 *
	 * @since CP-2.5.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$attachment_id = ! empty( $instance['attachment_id'] ) ? $instance['attachment_id'] : 0;
		$url           = ! empty( $instance['url'] ) ? $instance['url'] : '';
		$preload       = ! empty( $instance['preload'] ) ? $instance['preload'] : 'metadata';
		$loop          = ! empty( $instance['loop'] ) ? true : false;
		$content       = ! empty( $instance['content'] ) ? $instance['content'] : '';
		$mp4           = ! empty( $instance['mp4'] ) ? $instance['mp4'] : '';
		$m4v           = ! empty( $instance['m4v'] ) ? $instance['m4v'] : '';
		$webm          = ! empty( $instance['webm'] ) ? $instance['webm'] : '';
		$ogv           = ! empty( $instance['ogv'] ) ? $instance['ogv'] : '';
		$flv           = ! empty( $instance['flv'] ) ? $instance['flv'] : '';

		if ( $url === '' ) {
			if ( $attachment_id ) {
				$url = wp_get_attachment_url( $attachment_id );
			} elseif ( $mp4 ) {
				$url = $mp4;
			} elseif ( $m4v ) {
				$url = $m4v;
			} elseif ( $webm ) {
				$url = $webm;
			} elseif ( $ogv ) {
				$url = $ogv;
			} elseif ( $flv ) {
				$url = $flv;
			}
		}
		?>

		<div class="media-widget-control">
			<fieldset>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
				<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" class="widefat" value="<?php echo esc_attr( $title ); ?>">
			</fieldset>

			<?php
			if ( $url ) {
				?>

				<div class="media-widget-preview media_video populated"><?php echo wp_video_shortcode( array( 'src' => $url ) ); ?></div>

				<fieldset class="media-widget-buttons">
					<button type="button" class="button edit-media"><?php esc_html_e( 'Edit Video' ); ?></button>
					<button type="button" class="button change-media select-media"><?php esc_html_e( 'Replace Video' ); ?></button>
				</fieldset>

				<?php
			} else {
				?>

				<fieldset class="attachment-media-view">
					<button type="button" class="select-media button-add-media"><?php esc_html_e( 'Add Video' ); ?></button>
				</fieldset>

				<?php
			}
			?>

		</div>

		<input id="<?php echo esc_attr( $this->get_field_id( 'preload' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'preload' ) ); ?>" type="hidden" data-property="preload" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $preload ) ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'loop' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'loop' ) ); ?>" type="hidden" data-property="loop" class="media-widget-instance-property" value="<?php echo esc_attr( $loop ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'content' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'content' ) ); ?>" type="hidden" data-property="content" class="media-widget-instance-property" value="<?php echo esc_attr( $content ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'mp4' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mp4' ) ); ?>" type="hidden" data-property="mp4" class="media-widget-instance-property" value="<?php echo esc_attr( $mp4 ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'm4v' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'm4v' ) ); ?>" type="hidden" data-property="m4v" class="media-widget-instance-property" value="<?php echo esc_attr( $m4v ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'webm' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'webm' ) ); ?>" type="hidden" data-property="webm" class="media-widget-instance-property" value="<?php echo esc_attr( $webm ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'ogv' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ogv' ) ); ?>" type="hidden" data-property="ogv" class="media-widget-instance-property" value="<?php echo esc_attr( $ogv ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'flv' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'flv' ) ); ?>" type="hidden" data-property="flv" class="media-widget-instance-property" value="<?php echo esc_attr( $flv ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'attachment_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'attachment_id' ) ); ?>" type="hidden" data-property="attachment_id" class="media-widget-instance-property" value="<?php echo esc_attr( $attachment_id ); ?>">
		<input id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="hidden" data-property="url" class="media-widget-instance-property" value="<?php echo esc_url( $url ); ?>">

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
		$instance['url']           = ! empty( $new_instance['url'] ) ? sanitize_url( $new_instance['url'] ) : '';
		$instance['preload']       = ! empty( $new_instance['preload'] ) ? sanitize_text_field( $new_instance['preload'] ) : 'metadata';
		$instance['loop']          = ! empty( $new_instance['loop'] ) ? true : false;
		$instance['content']       = ! empty( $new_instance['content'] ) ? sanitize_text_field( $new_instance['content'] ) : '';
		$instance['mp4']           = ! empty( $new_instance['mp4'] ) ? sanitize_url( $new_instance['mp4'] ) : '';
		$instance['m4v']           = ! empty( $new_instance['m4v'] ) ? sanitize_url( $new_instance['m4v'] ) : '';
		$instance['webm']          = ! empty( $new_instance['webm'] ) ? sanitize_url( $new_instance['webm'] ) : '';
		$instance['ogv']           = ! empty( $new_instance['ogv'] ) ? sanitize_url( $new_instance['ogv'] ) : '';
		$instance['flv']           = ! empty( $new_instance['flv'] ) ? sanitize_url( $new_instance['flv'] ) : '';

		return $instance;
	}

	/**
	 * Enqueue preview scripts.
	 *
	 * These scripts normally are enqueued just-in-time when a video shortcode is used.
	 * In the customizer, however, widgets can be dynamically added and rendered via
	 * selective refresh, and so it is important to unconditionally enqueue them in
	 * case a widget does get added.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_preview_scripts() {
		/** This filter is documented in wp-includes/media.php */
		if ( 'mediaelement' === apply_filters( 'wp_video_shortcode_library', 'mediaelement' ) ) {
			wp_enqueue_style( 'wp-mediaelement' );
			wp_enqueue_script( 'mediaelement-vimeo' );
			wp_enqueue_script( 'wp-mediaelement' );
		}
	}

	/**
	 * Loads the required scripts and styles for the widget control.
	 *
	 * @since 4.8.0
	 */
	public function enqueue_admin_scripts() {
		parent::enqueue_admin_scripts();
		wp_enqueue_style( 'wp-mediaelement' );
		wp_enqueue_script( 'mediaelement-vimeo' );
		wp_enqueue_script( 'wp-mediaelement' );

		wp_enqueue_script( 'media-video-widget' );
		wp_localize_script(
			'media-video-widget',
			'VIDEO_WIDGET',
			array(
				'no_video_selected'          => __( 'No video selected' ),
				'add_video'                  => _x( 'Add Video', 'label for button in the media widget' ),
				'replace_video'              => _x( 'Replace Video', 'label for button in the media widget; should preferably not be longer than ~13 characters long' ),
				'edit_video'                 => _x( 'Edit Video', 'label for button in the media widget; should preferably not be longer than ~13 characters long' ),
				'add_to_widget'              => __( 'Add to Widget' ),
				'insert_from_url'            => __( 'Insert from URL' ),
				'missing_attachment'         => sprintf(
					/* translators: %s: URL to media library. */
					__( 'That file cannot be found. Check your <a href="%s">media library</a> and make sure it was not deleted.' ),
					esc_url( admin_url( 'upload.php' ) )
				),
				'unsupported_file_type'      => __( 'Looks like this is not the correct kind of file. Please link to an appropriate file instead.' ),
				'add_subtitles'              => __( 'Add Subtitles' ),
				'remove_video_source'        => __( 'Remove video source' ),
			)
		);
	}
}
