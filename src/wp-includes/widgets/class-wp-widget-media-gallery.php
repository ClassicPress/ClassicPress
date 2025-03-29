<?php
/**
 * Widget API: WP_Widget_Media_Gallery class
 *
 * @package ClassicPress
 * @subpackage Widgets
 * @since 4.9.0
 */

/**
 * Core class that implements a gallery widget.
 *
 * @since 4.9.0
 *
 * @see WP_Widget_Media
 * @see WP_Widget
 */
class WP_Widget_Media_Gallery extends WP_Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 */
	public function __construct() {
		parent::__construct(
			'media_gallery',
			__( 'Gallery' ),
			array(
				'description' => __( 'Displays an image gallery.' ),
				'mime_type'   => 'image',
			)
		);
	}

	/**
	 * Get schema for properties of a widget instance (item).
	 *
	 * @since 4.9.0
	 *
	 * @see WP_REST_Controller::get_item_schema()
	 * @see WP_REST_Controller::get_additional_fields()
	 * @link https://core.trac.wordpress.org/ticket/35574
	 *
	 * @return array Schema for properties.
	 */
	public function get_instance_schema() {
		$schema = array(
			'title'          => array(
				'type'                  => 'string',
				'default'               => '',
				'sanitize_callback'     => 'sanitize_text_field',
				'description'           => __( 'Title for the widget' ),
				'should_preview_update' => false,
			),
			'ids'            => array(
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'default'           => array(),
				'sanitize_callback' => 'wp_parse_id_list',
			),
			'columns'        => array(
				'type'    => 'integer',
				'default' => 3,
				'minimum' => 1,
				'maximum' => 9,
			),
			'size'           => array(
				'type'    => 'string',
				'enum'    => array_merge( get_intermediate_image_sizes(), array( 'full', 'custom' ) ),
				'default' => 'thumbnail',
			),
			'link_type'      => array(
				'type'                  => 'string',
				'enum'                  => array( 'post', 'file', 'none' ),
				'default'               => 'post',
				'media_prop'            => 'link',
				'should_preview_update' => false,
			),
			'orderby_random' => array(
				'type'                  => 'boolean',
				'default'               => false,
				'media_prop'            => '_orderbyRandom',
				'should_preview_update' => false,
			),
		);

		/** This filter is documented in wp-includes/widgets/class-wp-widget-media.php */
		$schema = apply_filters( "widget_{$this->id_base}_instance_schema", $schema, $this );

		return $schema;
	}

	/**
	 * Render the media on the frontend.
	 *
	 * @since 4.9.0
	 *
	 * @param array $instance Widget instance props.
	 */
	public function render_media( $instance ) {
		$instance = array_merge( wp_list_pluck( $this->get_instance_schema(), 'default' ), $instance );

		$shortcode_atts = array_merge(
			$instance,
			array(
				'link' => $instance['link_type'],
			)
		);

		// @codeCoverageIgnoreStart
		if ( $instance['orderby_random'] ) {
			$shortcode_atts['orderby'] = 'rand';
		}

		// @codeCoverageIgnoreEnd
		echo gallery_shortcode( $shortcode_atts );
	}

	/**
	 * Whether the widget has content to show.
	 *
	 * @since 4.9.0
	 * @access protected
	 *
	 * @param array $instance Widget instance props.
	 * @return bool Whether widget has content.
	 */
	protected function has_content( $instance ) {
		if ( ! empty( $instance['ids'] ) ) {
			$attachments = wp_parse_id_list( $instance['ids'] );
			foreach ( $attachments as $attachment ) {
				if ( 'attachment' !== get_post_type( $attachment ) ) {
					return false;
				}
			}
			return true;
		}
		return false;
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
		$instance = array_merge( wp_list_pluck( $this->get_instance_schema(), 'default' ), $instance );

		$title             = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$ids               = ! empty( $instance['ids'] ) ? $instance['ids'] : '';
		$columns           = ! empty( $instance['columns'] ) ? $instance['columns'] : 3;
		$size              = ! empty( $instance['size'] ) ? $instance['size'] : 'thumbnail';
		$link_type         = ! empty( $instance['link_type'] ) ? $instance['link_type'] : 'post';
		$orderby_random    = ! empty( $instance['orderby_random'] ) ? $instance['orderby_random'] : false;
		$nonce             = wp_create_nonce( '_wpnonce' );
		?>

		<div class="media-widget-control selected">
			<fieldset>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" data-property="title" class="widefat title" value="<?php echo esc_attr( $title ); ?>">
			</fieldset>

			<?php
			if ( $ids ) {
				$exploded = explode( ',', $ids );
				$gallery_html = '<ul class="gallery media-widget-gallery-preview">';
				foreach ( $exploded as $id ) {
					$attributes = '';
					$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail', false );
					$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );

					// Create an aria-label attribute if the image has no alt attribute.
					if ( $thumbnail[0] && $alt === '' ) {
						$aria_label = esc_attr(
							sprintf(
								/* translators: %s: The image file name. */
								__( 'The current image has no alternative text. The file name is: %s' ),
								basename( $thumbnail[0] )
							)
						);
						$attributes .= ' aria-label="' . $aria_label . '"';
					}

					$gallery_html .= '<li class="gallery-item">';
					$gallery_html .= '<div class="gallery-icon">';
					$gallery_html .= '<img alt="' . $alt . '" src="' . $thumbnail[0] . '" width="150" height="150"' . $attributes . '>';
					$gallery_html .= '</div>';
					$gallery_html .= '</li>';
				}
				$gallery_html .= '</ul>';
				?>

				<div class="media-widget-preview media_gallery"><?php echo $gallery_html; ?></div>

				<fieldset class="media-widget-buttons">
					<button type="button" class="button edit-media selected" data-edit-nonce="<?php echo esc_attr( $nonce ); ?>" style="margin-top:0;"><?php esc_html_e( 'Edit Gallery' ); ?></button>
				</fieldset>

				<?php
			} else {
				?>

				<div class="media-widget-preview media_gallery">
					<div class="attachment-media-view">
						<button type="button" class="select-media button-add-media"><?php esc_html_e( 'Add Images' ); ?></button>
					</div>
				</div>

				<?php
			}
			?>

			<input id="<?php echo esc_attr( $this->get_field_id( 'ids' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ids' ) ); ?>" type="hidden" data-property="ids" class="media-widget-instance-property" value="<?php echo esc_attr( $ids ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'columns' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'columns' ) ); ?>" type="hidden" data-property="columns" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $columns ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'size' ) ); ?>" type="hidden" data-property="size" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $size ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_type' ) ); ?>" type="hidden" data-property="link_type" class="media-widget-instance-property" value="<?php echo esc_attr( $link_type ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'orderby_random' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'orderby_random' ) ); ?>" type="hidden" data-property="orderby_random" class="media-widget-instance-property" value="<?php echo esc_attr( $orderby_random ); ?>">

		</div>
		<?php
	}

	/**
	 * Loads the required media files for the media manager and scripts for media widgets.
	 *
	 * @since 4.9.0
	 */
	public function enqueue_admin_scripts() {

		// Identify permitted image file types
		$image_file_types = array();
		$allowed_mime_types = get_allowed_mime_types();
		foreach ( $allowed_mime_types as $key => $mime ) {
			if ( str_contains( $mime, 'image/' ) ) {
				$extensions = explode( '|', $key );
				foreach ( $extensions as $extension ) {
					$image_file_types[] = $extension;
				}
			}
		}

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'media-gallery-widget' );
		wp_localize_script(
			'media-gallery-widget',
			'GALLERY_WIDGET',
			array(
				'edit_gallery' => __( 'Edit Gallery' ),
			)
		);
	}
}
