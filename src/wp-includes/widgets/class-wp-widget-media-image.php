<?php
/**
 * Widget API: WP_Widget_Media_Image class
 *
 * @package ClassicPress
 * @subpackage Widgets
 * @since 4.8.0
 */

/**
 * Core class that implements an image widget.
 *
 * @since 4.8.0
 *
 * @see WP_Widget_Media
 * @see WP_Widget
 */
class WP_Widget_Media_Image extends WP_Widget_Media {

	/**
	 * Constructor.
	 *
	 * @since 4.8.0
	 */
	public function __construct() {
		parent::__construct(
			'media_image',
			__( 'Image' ),
			array(
				'description' => __( 'Displays an image.' ),
				'mime_type'   => 'image',
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
		return array_merge(
			array(
				'size'              => array(
					'type'        => 'string',
					'enum'        => array_merge( get_intermediate_image_sizes(), array( 'full', 'custom' ) ),
					'default'     => 'medium',
					'description' => __( 'Size' ),
				),
				'width'             => array( // Via 'customWidth', only when size=custom; otherwise via 'width'.
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
					'description' => __( 'Width' ),
				),
				'height'            => array( // Via 'customHeight', only when size=custom; otherwise via 'height'.
					'type'        => 'integer',
					'minimum'     => 0,
					'default'     => 0,
					'description' => __( 'Height' ),
				),
				'caption'           => array(
					'type'                  => 'string',
					'default'               => '',
					'sanitize_callback'     => 'wp_kses_post',
					'description'           => __( 'Caption' ),
					'should_preview_update' => false,
				),
				'alt'               => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => __( 'Alternative Text' ),
				),
				'link_type'         => array(
					'type'                  => 'string',
					'enum'                  => array( 'none', 'file', 'post', 'custom' ),
					'default'               => 'custom',
					'media_prop'            => 'link',
					'description'           => __( 'Link To' ),
					'should_preview_update' => true,
				),
				'link_url'          => array(
					'type'                  => 'string',
					'default'               => '',
					'format'                => 'uri',
					'media_prop'            => 'linkUrl',
					'description'           => __( 'URL' ),
					'should_preview_update' => true,
				),
				'image_classes'     => array(
					'type'                  => 'string',
					'default'               => '',
					'sanitize_callback'     => array( $this, 'sanitize_token_list' ),
					'media_prop'            => 'extraClasses',
					'description'           => __( 'Image CSS Class' ),
					'should_preview_update' => false,
				),
				'link_classes'      => array(
					'type'                  => 'string',
					'default'               => '',
					'sanitize_callback'     => array( $this, 'sanitize_token_list' ),
					'media_prop'            => 'linkClassName',
					'should_preview_update' => false,
					'description'           => __( 'Link CSS Class' ),
				),
				'link_rel'          => array(
					'type'                  => 'string',
					'default'               => '',
					'sanitize_callback'     => array( $this, 'sanitize_token_list' ),
					'media_prop'            => 'linkRel',
					'description'           => __( 'Link Rel' ),
					'should_preview_update' => false,
				),
				'link_target_blank' => array(
					'type'                  => 'boolean',
					'default'               => false,
					'media_prop'            => 'linkTargetBlank',
					'description'           => __( 'Open link in a new tab' ),
					'should_preview_update' => false,
				),
				'image_title'       => array(
					'type'                  => 'string',
					'default'               => '',
					'sanitize_callback'     => 'sanitize_text_field',
					'media_prop'            => 'title',
					'description'           => __( 'Image Title Attribute' ),
					'should_preview_update' => false,
				),

				/*
				 * There are two additional properties exposed by the PostImage modal
				 * that don't seem to be relevant, as they may only be derived read-only
				 * values:
				 * - originalUrl
				 * - aspectRatio
				 * - height (redundant when size is not custom)
				 * - width (redundant when size is not custom)
				 */
			),
			parent::get_instance_schema()
		);
	}

	/**
	 * Render the media on the frontend.
	 *
	 * @since 4.8.0
	 *
	 * @param array $instance Widget instance props.
	 */
	public function render_media( $instance ) {
		$instance = array_merge( wp_list_pluck( $this->get_instance_schema(), 'default' ), $instance );
		$instance = wp_parse_args(
			$instance,
			array(
				'size' => 'thumbnail',
			)
		);

		$attachment = null;

		if ( $this->is_attachment_with_mime_type( $instance['attachment_id'], $this->widget_options['mime_type'] ) ) {
			$attachment = get_post( $instance['attachment_id'] );
		}

		if ( $attachment ) {
			$caption = '';
			if ( ! isset( $instance['caption'] ) ) {
				$caption = $attachment->post_excerpt;
			} elseif ( trim( $instance['caption'] ) ) {
				$caption = $instance['caption'];
			}

			$image_attributes = array(
				'class' => sprintf( 'image wp-image-%d %s', $attachment->ID, $instance['image_classes'] ),
				'style' => 'max-width: 100%; height: auto;',
			);

			if ( ! empty( $instance['image_title'] ) ) {
				$image_attributes['title'] = $instance['image_title'];
			}

			if ( ! empty( $instance['alt'] ) ) {
				$image_attributes['alt'] = $instance['alt'];
			}

			$size = $instance['size'];

			if ( 'custom' === $size || ! in_array( $size, array_merge( get_intermediate_image_sizes(), array( 'full' ) ), true ) ) {
				$size  = array( $instance['width'], $instance['height'] );
				$width = $instance['width'];
			} else {
				$caption_size = _wp_get_image_size_from_meta( $instance['size'], wp_get_attachment_metadata( $attachment->ID ) );
				$width        = empty( $caption_size[0] ) ? 0 : $caption_size[0];
			}

			$image_attributes['class'] .= sprintf( ' attachment-%1$s size-%1$s', is_array( $size ) ? implode( 'x', $size ) : $size );

			$image = wp_get_attachment_image( $attachment->ID, $size, false, $image_attributes );

		} else {
			if ( empty( $instance['url'] ) ) {
				return;
			}

			$instance['size'] = 'custom';
			$caption          = $instance['caption'];
			$width            = $instance['width'];
			$classes          = 'image ' . $instance['image_classes'];

			if ( 0 === $instance['width'] ) {
				$instance['width'] = '';
			}
			if ( 0 === $instance['height'] ) {
				$instance['height'] = '';
			}

			$attr = array(
				'class'    => $classes,
				'src'      => $instance['url'],
				'alt'      => $instance['alt'],
				'width'    => $instance['width'],
				'height'   => $instance['height'],
				'decoding' => 'async',
			);

			$loading_optimization_attr = wp_get_loading_optimization_attributes(
				'img',
				$attr,
				'widget_media_image'
			);

			$attr = array_merge( $attr, $loading_optimization_attr );

			$attr  = array_map( 'esc_attr', $attr );
			$image = '<img';

			foreach ( $attr as $name => $value ) {
				$image .= ' ' . $name . '="' . $value . '"';
			}

			$image .= '>';
		} // End if().

		$url = '';
		if ( 'file' === $instance['link_type'] ) {
			$url = $attachment ? wp_get_attachment_url( $attachment->ID ) : $instance['url'];
		} elseif ( $attachment && 'post' === $instance['link_type'] ) {
			$url = get_attachment_link( $attachment->ID );
		} elseif ( 'custom' === $instance['link_type'] && ! empty( $instance['link_url'] ) ) {
			$url = $instance['link_url'];
		}

		if ( $url ) {
			$link = sprintf( '<a href="%s"', esc_url( $url ) );
			if ( ! empty( $instance['link_classes'] ) ) {
				$link .= sprintf( ' class="%s"', esc_attr( $instance['link_classes'] ) );
			}
			if ( ! empty( $instance['link_rel'] ) ) {
				$link .= sprintf( ' rel="%s"', esc_attr( $instance['link_rel'] ) );
			}
			if ( ! empty( $instance['link_target_blank'] ) ) {
				$link .= ' target="_blank"';
			}
			$link .= '>';
			$link .= $image;
			$link .= '</a>';
			$image = wp_targeted_link_rel( $link );
		}

		if ( $caption ) {
			$image = img_caption_shortcode(
				array(
					'width'   => $width,
					'caption' => $caption,
				),
				$image
			);
		}

		echo $image;
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
		$attachment_id     = ! empty( $instance['attachment_id'] ) ? $instance['attachment_id'] : 0;
		$size              = ! empty( $instance['size'] ) ? $instance['size'] : 'full';
		$alt               = ! empty( $instance['alt'] ) ? $instance['alt'] : '';
		$link_type         = ! empty( $instance['link_type'] ) ? $instance['link_type'] : '';
		$link_url          = ! empty( $instance['link_url'] ) ? $instance['link_url'] : '';
		$caption           = ! empty( $instance['caption'] ) ? $instance['caption'] : '';
		$url               = ! empty( $instance['url'] ) ? $instance['url'] : '';
		$width             = ! empty( $instance['width'] ) ? absint( $instance['width'] ) : 0;
		$height            = ! empty( $instance['height'] ) ? absint( $instance['height'] ) : 0;
		$image_classes     = ! empty( $instance['image_classes'] ) ? $instance['image_classes'] : '';
		$link_classes      = ! empty( $instance['link_classes'] ) ? $instance['link_classes'] : '';
		$link_rel          = ! empty( $instance['link_rel'] ) ? $instance['link_rel'] : '';
		$link_target_blank = ! empty( $instance['link_target_blank'] ) ? '_blank' : '';
		$link_image_title  = ! empty( $instance['link_image_title'] ) ? $instance['link_image_title'] : '';
		$size_options      = ! empty( $instance['size_options'] ) ? $instance['size_options'] : '';

		$attributes        = 'alt="' . $alt . '"';
		$aria_label        = '';

		if ( $attachment_id && $url === '' ) {
			$url = wp_get_attachment_url( $attachment_id );
		}

		// Create an aria-label attribute if the image has no alt attribute.
		if ( $url && $alt === '' ) {
			$aria_label = esc_attr(
				sprintf(
					/* translators: %s: The image file name. */
					__( 'The current image has no alternative text. The file name is: %s' ),
					basename( $url )
				)
			);
			$attributes .= ' aria-label="' . $aria_label . '"';
		}

		/**
		 * Filter media image attributes within media image widget
		 *
		 * @since CP-2.5.0
		 *
		 * @param string $attributes    The default attributes.
		 * @param string $alt           The default alt attribute.
		 * @param string $aria-label    The default aria-label attribute.
		 * @param int    $attachment_id The attachment ID.
		 * @param string $url           The image file URL.
		 */
		$attributes = apply_filters( 'cp_media_image_widget_image_attributes', $attributes, $alt, $aria_label, $attachment_id, $url );

		$image_html = '';
		if ( $url ) {
			if ( $attachment_id === 0 ) {
				$attachment_id = attachment_url_to_postid( $url );
			}
			$image_html = '<img class="attachment-thumb" src="' . esc_url( $url ) . '" draggable="false" ' . $attributes . '>';
			if ( $caption !== '' ) {
				$image_html = '<figure style="margin:auto;">' . $image_html . '<figcaption>' . esc_html( $caption ) . '</figcaption></figure>';
			}
		} else {
			$image_html = '<div class="notice-error notice-alt" style="border-left: 3px solid #d63638;"><p style="padding: 0.5em 0">' . __( 'Unable to preview media due to an unknown error.' ) . '</p></div>';
		}

		$size_options = '';
		if ( $attachment_id !== 0 ) {
			$sizes_array = wp_get_attachment_metadata( $attachment_id );
			foreach ( $sizes_array['sizes'] as $key => $option ) {
				$size_options .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $size, false ) . '>' . esc_html( ucfirst( $key ) . ' &ndash; ' . $option['width'] . ' x ' . $option['height'] ) . '</option>';
			}
			$size_options .= '<option value="full"' . selected( 'full', $size, false ) . '>' . esc_html( 'Full &ndash; ' . $sizes_array['width'] . ' x ' . $sizes_array['height'] ) . '</option>';
		}
		?>

		<div class="media-widget-control selected">
			<fieldset>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:' ); ?></label>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" class="widefat" type="text" value="<?php echo esc_attr( $title ); ?>">
			</fieldset>

			<?php
			if ( $url ) {
				$nonce = wp_create_nonce( 'image_editor-' . $attachment_id );
				?>

				<div class="media-widget-preview media_image populated"><?php echo $image_html; ?></div>

				<fieldset class="media-widget-buttons">
					<button type="button" class="button edit-media selected" data-edit-nonce="<?php echo esc_attr( $nonce ); ?>"><?php esc_html_e( 'Edit Image' ); ?></button>
					<button type="button" class="button change-media select-media selected"><?php esc_html_e( 'Replace Image' ); ?></button>
				</fieldset>

				<fieldset class="media-widget-image-link">
					<label for="<?php echo $this->get_field_id( 'link_url' ); ?>"><?php esc_html_e( 'Link to:' ); ?></label>
					<input id="<?php echo $this->get_field_id( 'link_url' ); ?>" name="<?php echo $this->get_field_name( 'link_url' ); ?>" class="widefat" type="url" value="<?php echo esc_url( $link_url ); ?>" placeholder="https://" data-property="link_url">
				</fieldset>

				<?php
			} else {
				?>

				<div class="media-widget-preview media_image">
					<div class="attachment-media-view">
						<button type="button" class="select-media button-add-media"><?php esc_html_e( 'Add Image' ); ?></button>
					</div>
				</div>

				<?php
			}
			?>

			<input id="<?php echo esc_attr( $this->get_field_id( 'size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'size' ) ); ?>" type="hidden" data-property="size" class="media-widget-instance-property" value="<?php echo esc_attr( esc_attr( $size ) ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'width' ) ); ?>" type="hidden" data-property="width" class="media-widget-instance-property" value="<?php echo esc_attr( $width ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'height' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'height' ) ); ?>" type="hidden" data-property="height" class="media-widget-instance-property" value="<?php echo esc_attr( $height ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'caption' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'caption' ) ); ?>" type="hidden" data-property="caption" class="media-widget-instance-property" value="<?php echo esc_attr( $caption ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'alt' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'alt' ) ); ?>" type="hidden" data-property="alt" class="media-widget-instance-property" value="<?php echo esc_attr( $alt ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_type' ) ); ?>" type="hidden" data-property="link_type" class="media-widget-instance-property" value="<?php echo esc_attr( $link_type ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'image_classes' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'image_classes' ) ); ?>" type="hidden" data-property="image_classes" class="media-widget-instance-property" value="<?php echo esc_attr( $image_classes ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_classes' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_classes' ) ); ?>" type="hidden" data-property="link_classes" class="media-widget-instance-property" value="<?php echo esc_attr( $link_classes ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_rel' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_rel' ) ); ?>" type="hidden" data-property="link_rel" class="media-widget-instance-property" value="<?php echo esc_url( $link_rel ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_target_blank' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_target_blank' ) ); ?>" type="hidden" data-property="link_target_blank" class="media-widget-instance-property" value="<?php echo esc_attr( $link_target_blank ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'link_image_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_image_title' ) ); ?>" type="hidden" data-property="link_image_title" class="media-widget-instance-property" value="<?php echo esc_attr( $link_image_title ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'attachment_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'attachment_id' ) ); ?>" type="hidden" data-property="attachment_id" class="media-widget-instance-property" value="<?php echo esc_attr( $attachment_id ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="hidden" data-property="url" class="media-widget-instance-property" value="<?php echo esc_url( $url ); ?>">
			<input id="<?php echo esc_attr( $this->get_field_id( 'size_options' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'size_options' ) ); ?>" type="hidden" data-property="size_options" class="media-widget-instance-property" value="<?php echo esc_attr( $size_options ); ?>">

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
		$instance['title']             = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['attachment_id']     = ! empty( $new_instance['attachment_id'] ) ? absint( $new_instance['attachment_id'] ) : 0;
		$instance['url']               = ! empty( $new_instance['url'] ) ? sanitize_url( $new_instance['url'] ) : '';
		$instance['size']              = ! empty( $new_instance['size'] ) ? sanitize_text_field( $new_instance['size'] ) : '';
		$instance['width']             = ! empty( $new_instance['width'] ) ? absint( $new_instance['width'] ) : 0;
		$instance['height']            = ! empty( $new_instance['height'] ) ? absint( $new_instance['height'] ) : 0;
		$instance['caption']           = ! empty( $new_instance['caption'] ) ? wp_kses_post( $new_instance['caption'] ) : '';
		$instance['alt']               = ! empty( $new_instance['alt'] ) ? sanitize_text_field( $new_instance['alt'] ) : '';
		$instance['link_type']         = ! empty( $new_instance['link_type'] ) ? sanitize_text_field( $new_instance['link_type'] ) : '';
		$instance['link_url']          = ! empty( $new_instance['link_url'] ) ? sanitize_url( $new_instance['link_url'] ) : '';
		$instance['image_classes']     = ! empty( $new_instance['image_classes'] ) ? $this->sanitize_token_list( $new_instance['image_classes'] ) : '';
		$instance['link_classes']      = ! empty( $new_instance['link_classes'] ) ? $this->sanitize_token_list( $new_instance['link_classes'] ) : '';
		$instance['link_rel']          = ! empty( $new_instance['link_rel'] ) ? $this->sanitize_token_list( $new_instance['link_rel'] ) : '';
		$instance['link_target_blank'] = ! empty( $new_instance['link_target_blank'] ) ? '_blank' : '';
		$instance['link_image_title']  = ! empty( $new_instance['link_image_title'] ) ? sanitize_text_field( $new_instance['link_image_title'] ) : '';
		$instance['size_options']      = ! empty( $new_instance['size_options'] ) ? sanitize_text_field( $new_instance['size_options'] ) : '';

		return $instance;
	}

	/**
	 * Loads the required media files for the media manager and scripts for media widgets.
	 *
	 * @since 4.8.0
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

		wp_enqueue_script( 'media-image-widget' );
		wp_localize_script(
			'media-image-widget',
			'IMAGE_WIDGET',
			array(
				'replace_image'              => _x( 'Replace Image', 'label for button in the image widget; should preferably not be longer than ~13 characters long' ),
				'edit_image'                 => _x( 'Edit Image', 'label for button in the image widget; should preferably not be longer than ~13 characters long' ),
				'add_to_widget'              => __( 'Add to Widget' ),
				'unsupported_file_type'      => __( 'Looks like this is not the correct kind of file. Please link to an appropriate file instead.' ),
				'aria_label'                 => __( 'The current image has no alternative text. The file name is: ' ),
				'image_file_types'           => $image_file_types,
				'wrong_url'                  => __( 'No file exists at the URL provided.' ),
			)
		);
	}
}

/**
 * Renders the template for the modal content for the media image widget
 *
 * @since CP-2.5.0
 *
 * @return string
 */
function cp_render_media_image_template() {
	ob_start();
	?>

	<template id="tmpl-edit-image-modal">
		<div id="image-modal-content" class="media-modal-content">
			<div class="media-frame mode-select wp-core-ui media-widget hide-menu hide-router">

				<div class="media-frame-title" id="media-frame-title">
					<h2><?php esc_html_e( 'Image details' ); ?></h2>
				</div>

				<div class="media-frame-tab-panel">
					<div class="media-frame-content">
						<div class="image-details">
							<div class="media-embed">
								<div class="embed-media-settings">
									<div class="column-settings">
										<span class="setting alt-text has-description">
											<label for="image-details-alt-text" class="name"><?php esc_html_e( 'Alternative Text' ); ?></label>
											<textarea id="image-details-alt-text" data-setting="alt" aria-describedby="alt-text-description"></textarea>
										</span>
										<p class="description" id="alt-text-description"><a href="https://www.w3.org/WAI/tutorials/images/decision-tree" target="_blank" rel="noopener"><?php esc_html_e( 'Learn how to describe the purpose of the image' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)' ); ?></span></a><?php esc_html_e( '. Leave empty if the image is purely decorative.' ); ?></p>

										<span class="setting caption">
											<label for="image-details-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
											<textarea id="image-details-caption" data-setting="caption"></textarea>
										</span>

										<h3><?php esc_html_e( 'Display Settings' ); ?></h3>
										<fieldset>
											<span class="setting size">
												<label for="image-details-size" class="name"><?php _e( 'Size' ); ?></label>
												<select id="image-details-size" class="size" name="size" data-setting="size">
													<option value="custom">Custom Size</option>
												</select>
											</span>

											<div class="custom-size wp-clearfix hidden">
												<span class="custom-size-setting">
													<label for="image-details-size-width"><?php esc_html_e( 'Width' ); ?></label>
													<input type="number" id="image-details-size-width" aria-describedby="image-size-desc" data-setting="customWidth" step="1" value="">
												</span>
												<span class="sep" aria-hidden="true">×</span>
												<span class="custom-size-setting">
													<label for="image-details-size-height"><?php esc_html_e( 'Height' ); ?></label>
													<input type="number" id="image-details-size-height" aria-describedby="image-size-desc" data-setting="customHeight" step="1" value="">
												</span>
												<p id="image-size-desc" class="description"><?php esc_html_e( 'Image size in pixels' ); ?></p>
											</div>

											<div class="setting">
												<label for="image-details-link-to" class="name" style="margin-top:17px;"><?php esc_html_e( 'Link To' ); ?></label>
												<div class="setting link-to" style="display:inline-block;">
													<select id="image-details-link-to" name="link-type" data-setting="link">
														<option value="none" selected><?php esc_html_e( 'None' ); ?></option>
														<option value="file"><?php esc_html_e( 'Image URL' ); ?></option>
														<option value="custom"><?php esc_html_e( 'Custom URL' ); ?></option>
													</select>
												</div>
												<div id="link-to-url" hidden inert>
													<label for="image-details-link-to-custom" class="name"><?php esc_html_e( 'URL' ); ?></label>
													<input type="url" id="image-details-link-to-custom" class="link-to-custom" style="margin-left: 0;" data-setting="linkUrl">
												</div>
											</div>
										</fieldset>

										<details class="advanced-section">
											<summary><h3><?php esc_html_e( 'Advanced Options' ); ?></h3></summary>
											<div class="advanced-settings">
												<div class="advanced-image">
													<span class="setting title-text">
														<label for="image-details-title-attribute" class="name"><?php esc_html_e( 'Image Title Attribute' ); ?></label>
														<input type="text" id="image-details-title-attribute" data-setting="title" value="">
													</span>
													<span class="setting extra-classes">
														<label for="image-details-css-class" class="name"><?php esc_html_e( 'Image CSS Class' ); ?> </label>
														<input type="text" id="image-details-css-class" data-setting="extraClasses" value="">
													</span>
												</div>
												<div class="advanced-link">
													<span class="setting link-target">
														<input type="checkbox" id="image-details-link-target" data-setting="linkTargetBlank" value="_blank">
														<label for="image-details-link-target" class="checkbox-label"><?php esc_html_e( 'Open link in a new tab' ); ?></label>
													</span>
													<span class="setting link-rel">
														<label for="image-details-link-rel" class="name"><?php esc_html_e( 'Link Rel' ); ?></label>
														<input type="text" id="image-details-link-rel" data-setting="linkRel" value="">
													</span>
													<span class="setting link-class-name">
														<label for="image-details-link-css-class" class="name"><?php esc_html_e( 'Link CSS Class' ); ?></label>
														<input type="text" id="image-details-link-css-class" data-setting="linkClassName" value="">
													</span>
												</div>
											</div>
										</details>
									</div>
									<div class="column-image">
										<div class="image">
											<img src="<?php echo esc_url( includes_url() . 'images/blank.gif' ); ?>" draggable="false" alt="">

											<div class="actions">
												<button id="edit-original" type="button" class="edit-attachment button" data-href="<?php echo esc_url( home_url( '/wp-admin/post.php?item=xxx&mode=edit' ) ); ?>"><?php esc_html_e( 'Edit Original' ); ?></button>
											</div>

										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<h2 class="media-frame-actions-heading screen-reader-text"><?php esc_html_e( 'Selected media actions' ); ?></h2>
				<div class="media-frame-toolbar">
					<div class="media-toolbar">
						<div class="media-toolbar-primary search-form">
							<button type="button" class="button media-button button-primary button-large media-button-select"><?php esc_html_e( 'Update' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</template>

	<?php
	return ob_get_clean();
}
