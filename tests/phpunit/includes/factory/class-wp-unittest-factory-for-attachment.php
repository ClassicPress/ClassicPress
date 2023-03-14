<?php

/**
 * Unit test factory for attachments.
 *
 * Note: The below @method notations are defined solely for the benefit of IDEs,
 * as a way to indicate expected return values from the given factory methods.
 *
 * @method int|WP_Error     create( $args = array(), $generation_definitions = null )
 * @method WP_Post|WP_Error create_and_get( $args = array(), $generation_definitions = null )
 * @method (int|WP_Error)[] create_many( $count, $args = array(), $generation_definitions = null )
 */
class WP_UnitTest_Factory_For_Attachment extends WP_UnitTest_Factory_For_Post {

	/**
	 * Create an attachment fixture.
	 *
	 * @since UT (3.7.0)
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param array $args {
	 *     Array of arguments. Accepts all arguments that can be passed to
	 *     wp_insert_attachment(), in addition to the following:
	 *     @type int    $post_parent ID of the post to which the attachment belongs.
	 *     @type string $file        Path of the attached file.
	 * }
	 * @param int   $legacy_parent Deprecated.
	 * @param array $legacy_args   Deprecated.
	 *
	 * @return int|WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	public function create_object( $args, $legacy_parent = 0, $legacy_args = array() ) {
		// Backward compatibility for legacy argument format.
		if ( is_string( $args ) ) {
			$file                = $args;
			$args                = $legacy_args;
			$args['post_parent'] = $legacy_parent;
			$args['file']        = $file;
		}

		$r = array_merge(
			array(
				'file'        => '',
				'post_parent' => 0,
			),
			$args
		);

		return wp_insert_attachment( $r, $r['file'], $r['post_parent'], true );
	}

	/**
	 * Saves an attachment.
	 *
	 * @since 4.4.0
	 * @since 6.2.0 Returns a WP_Error object on failure.
	 *
	 * @param string $file           The file name to create attachment object for.
	 * @param int    $parent_post_id ID of the post to attach the file to.
	 *
	 * @return int|WP_Error The attachment ID on success, WP_Error object on failure.
	 */
	public function create_upload_object( $file, $parent_post_id = 0 ) {
		$contents = file_get_contents( $file );
		$upload   = wp_upload_bits( wp_basename( $file ), null, $contents );

		$type = '';
		if ( ! empty( $upload['type'] ) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ( $mime ) {
				$type = $mime['type'];
			}
		}

		$attachment = array(
			'post_title'     => wp_basename( $upload['file'] ),
			'post_content'   => '',
			'post_type'      => 'attachment',
			'post_parent'    => $parent_post_id,
			'post_mime_type' => $type,
			'guid'           => $upload['url'],
		);

		// Save the data.
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_post_id, true );

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		wp_update_attachment_metadata(
			$attachment_id,
			wp_generate_attachment_metadata( $attachment_id, $upload['file'] )
		);

		return $attachment_id;
	}
}
