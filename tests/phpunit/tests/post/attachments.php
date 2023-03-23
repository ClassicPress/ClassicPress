<?php

/**
 * @group post
 * @group media
 * @group upload
 */
class Tests_Post_Attachments extends WP_UnitTestCase {

	public function tear_down() {
		// Remove all uploads.
		$this->remove_added_uploads();
		parent::tear_down();
	}

	public function test_insert_bogus_image() {
		$filename = rand_str() . '.jpg';
		$contents = rand_str();

		$upload = wp_upload_bits( $filename, null, $contents );
		$this->assertEmpty( $upload['error'] );
	}

	public function test_insert_image_no_thumb() {

		// This image is smaller than the thumbnail size so it won't have one.
		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$id = $this->_make_attachment( $upload );

		// Intermediate copies should not exist.
		$this->assertFalse( image_get_intermediate_size( $id, 'thumbnail' ) );
		$this->assertFalse( image_get_intermediate_size( $id, 'medium' ) );
		$this->assertFalse( image_get_intermediate_size( $id, 'medium_large' ) );

		// medium, medium_large, and full size will both point to the original.
		$downsize = image_downsize( $id, 'medium' );
		$this->assertSame( wp_basename( $upload['file'] ), wp_basename( $downsize[0] ) );
		$this->assertSame( 50, $downsize[1] );
		$this->assertSame( 50, $downsize[2] );

		$downsize = image_downsize( $id, 'medium_large' );
		$this->assertSame( wp_basename( $upload['file'] ), wp_basename( $downsize[0] ) );
		$this->assertSame( 50, $downsize[1] );
		$this->assertSame( 50, $downsize[2] );

		$downsize = image_downsize( $id, 'full' );
		$this->assertSame( wp_basename( $upload['file'] ), wp_basename( $downsize[0] ) );
		$this->assertSame( 50, $downsize[1] );
		$this->assertSame( 50, $downsize[2] );
	}

	/**
	 * @requires function imagejpeg
	 */
	public function test_insert_image_thumb_only() {
		update_option( 'medium_size_w', 0 );
		update_option( 'medium_size_h', 0 );

		$filename = ( DIR_TESTDATA . '/images/a2-small.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$id = $this->_make_attachment( $upload );

		// Intermediate copies should exist: thumbnail only.
		$thumb = image_get_intermediate_size( $id, 'thumbnail' );
		$this->assertSame( 'a2-small-150x150.jpg', $thumb['file'] );

		$uploads = wp_upload_dir();
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path'] ) );

		$this->assertFalse( image_get_intermediate_size( $id, 'medium' ) );
		$this->assertFalse( image_get_intermediate_size( $id, 'medium_large' ) );

		// The thumb url should point to the thumbnail intermediate.
		$this->assertSame( $thumb['url'], wp_get_attachment_thumb_url( $id ) );

		// image_downsize() should return the correct images and sizes.
		$downsize = image_downsize( $id, 'thumbnail' );
		$this->assertSame( 'a2-small-150x150.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 150, $downsize[1] );
		$this->assertSame( 150, $downsize[2] );

		// medium, medium_large, and full will both point to the original.
		$downsize = image_downsize( $id, 'medium' );
		$this->assertSame( 'a2-small.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 400, $downsize[1] );
		$this->assertSame( 300, $downsize[2] );

		$downsize = image_downsize( $id, 'medium_large' );
		$this->assertSame( 'a2-small.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 400, $downsize[1] );
		$this->assertSame( 300, $downsize[2] );

		$downsize = image_downsize( $id, 'full' );
		$this->assertSame( 'a2-small.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 400, $downsize[1] );
		$this->assertSame( 300, $downsize[2] );
	}

	/**
	 * @requires function imagejpeg
	 */
	public function test_insert_image_medium_sizes() {
		update_option( 'medium_size_w', 400 );
		update_option( 'medium_size_h', 0 );

		update_option( 'medium_large_size_w', 600 );
		update_option( 'medium_large_size_h', 0 );

		$filename = ( DIR_TESTDATA . '/images/2007-06-17DSC_4173.JPG' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$id      = $this->_make_attachment( $upload );
		$uploads = wp_upload_dir();

		// Intermediate copies should exist: thumbnail and medium.
		$thumb = image_get_intermediate_size( $id, 'thumbnail' );
		$this->assertSame( '2007-06-17DSC_4173-150x150.jpg', $thumb['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path'] ) );

		$medium = image_get_intermediate_size( $id, 'medium' );
		$this->assertSame( '2007-06-17DSC_4173-400x602.jpg', $medium['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $medium['path'] ) );

		$medium_large = image_get_intermediate_size( $id, 'medium_large' );
		$this->assertSame( '2007-06-17DSC_4173-600x904.jpg', $medium_large['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $medium_large['path'] ) );

		// The thumb url should point to the thumbnail intermediate.
		$this->assertSame( $thumb['url'], wp_get_attachment_thumb_url( $id ) );

		// image_downsize() should return the correct images and sizes.
		$downsize = image_downsize( $id, 'thumbnail' );
		$this->assertSame( '2007-06-17DSC_4173-150x150.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 150, $downsize[1] );
		$this->assertSame( 150, $downsize[2] );

		$downsize = image_downsize( $id, 'medium' );
		$this->assertSame( '2007-06-17DSC_4173-400x602.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 400, $downsize[1] );
		$this->assertSame( 602, $downsize[2] );

		$downsize = image_downsize( $id, 'medium_large' );
		$this->assertSame( '2007-06-17DSC_4173-600x904.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 600, $downsize[1] );
		$this->assertSame( 904, $downsize[2] );

		$downsize = image_downsize( $id, 'full' );
		$this->assertSame( '2007-06-17DSC_4173.jpg', wp_basename( $downsize[0] ) );
		$this->assertSame( 680, $downsize[1] );
		$this->assertSame( 1024, $downsize[2] );
	}

	/**
	 * @requires function imagejpeg
	 */
	public function test_insert_image_delete() {
		update_option( 'medium_size_w', 400 );
		update_option( 'medium_size_h', 0 );

		update_option( 'medium_large_size_w', 600 );
		update_option( 'medium_large_size_h', 0 );

		$filename = ( DIR_TESTDATA . '/images/2007-06-17DSC_4173.JPG' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$id      = $this->_make_attachment( $upload );
		$uploads = wp_upload_dir();

		// Check that the file and intermediates exist.
		$thumb = image_get_intermediate_size( $id, 'thumbnail' );
		$this->assertSame( '2007-06-17DSC_4173-150x150.jpg', $thumb['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $thumb['path'] ) );

		$medium = image_get_intermediate_size( $id, 'medium' );
		$this->assertSame( '2007-06-17DSC_4173-400x602.jpg', $medium['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $medium['path'] ) );

		$medium_large = image_get_intermediate_size( $id, 'medium_large' );
		$this->assertSame( '2007-06-17DSC_4173-600x904.jpg', $medium_large['file'] );
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $medium_large['path'] ) );

		$meta     = wp_get_attachment_metadata( $id );
		$original = $meta['file'];
		$this->assertTrue( is_file( $uploads['basedir'] . DIRECTORY_SEPARATOR . $original ) );

		// Now delete the attachment and make sure all files are gone.
		wp_delete_attachment( $id );

		$this->assertFalse( is_file( $thumb['path'] ) );
		$this->assertFalse( is_file( $medium['path'] ) );
		$this->assertFalse( is_file( $medium_large['path'] ) );
		$this->assertFalse( is_file( $original ) );
	}

	/**
	 * GUID should never be empty
	 *
	 * @ticket 18310
	 * @ticket 21963
	 */
	public function test_insert_image_without_guid() {
		// This image is smaller than the thumbnail size so it won't have one.
		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$upload['url'] = '';
		$id            = $this->_make_attachment( $upload );

		$guid = get_the_guid( $id );
		$this->assertNotEmpty( $guid );
	}

	/**
	 * @ticket 21963
	 */
	public function test_update_attachment_fields() {
		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$id = $this->_make_attachment( $upload );

		$attached_file = get_post_meta( $id, '_wp_attached_file', true );

		$post = get_post( $id, ARRAY_A );

		$post['post_title']   = 'title';
		$post['post_excerpt'] = 'caption';
		$post['post_content'] = 'description';

		wp_update_post( $post );

		// Make sure the update didn't remove the attached file.
		$this->assertSame( $attached_file, get_post_meta( $id, '_wp_attached_file', true ) );
	}

	/**
	 * @ticket 29646
	 */
	public function test_update_orphan_attachment_parent() {
		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		$attachment_id = $this->_make_attachment( $upload );

		// Assert that the attachment is an orphan.
		$attachment = get_post( $attachment_id );
		$this->assertSame( $attachment->post_parent, 0 );

		$post_id = wp_insert_post(
			array(
				'post_content' => 'content',
				'post_title'   => 'title',
			)
		);

		// Assert that the attachment has a parent.
		wp_insert_attachment( $attachment, '', $post_id );
		$attachment = get_post( $attachment_id );
		$this->assertSame( $attachment->post_parent, $post_id );
	}

	/**
	 * @ticket 15928
	 */
	public function test_wp_get_attachment_url_should_not_force_https_when_current_page_is_non_ssl_and_siteurl_is_non_ssl() {
		$siteurl = get_option( 'siteurl' );
		update_option( 'siteurl', set_url_scheme( $siteurl, 'http' ) );

		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'off';

		$url = wp_get_attachment_url( $attachment_id );

		$this->assertSame( 'http', parse_url( $url, PHP_URL_SCHEME ) );
	}

	/**
	 * @ticket 15928
	 *
	 * This situation (current request is non-SSL but siteurl is https) should never arise.
	 */
	public function test_wp_get_attachment_url_should_not_force_https_when_current_page_is_non_ssl_and_siteurl_is_ssl() {
		$siteurl = get_option( 'siteurl' );
		update_option( 'siteurl', set_url_scheme( $siteurl, 'https' ) );

		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'off';

		$url = wp_get_attachment_url( $attachment_id );

		$this->assertSame( 'http', parse_url( $url, PHP_URL_SCHEME ) );
	}

	/**
	 * @ticket 15928
	 *
	 * Canonical siteurl is non-SSL, but SSL support is available/optional.
	 */
	public function test_wp_get_attachment_url_should_force_https_with_https_on_same_host_when_siteurl_is_non_ssl_but_ssl_is_available() {
		$siteurl = get_option( 'siteurl' );
		update_option( 'siteurl', set_url_scheme( $siteurl, 'http' ) );

		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'on';

		// Ensure that server host matches the host of wp_upload_dir().
		$upload_dir           = wp_upload_dir();
		$_SERVER['HTTP_HOST'] = parse_url( $upload_dir['baseurl'], PHP_URL_HOST );

		// Test that wp_get_attachemt_url returns with https scheme.
		$url = wp_get_attachment_url( $attachment_id );

		$this->assertSame( 'https', parse_url( $url, PHP_URL_SCHEME ) );
	}

	/**
	 * @ticket 15928
	 */
	public function test_wp_get_attachment_url_with_https_on_same_host_when_siteurl_is_https() {
		$siteurl = get_option( 'siteurl' );
		update_option( 'siteurl', set_url_scheme( $siteurl, 'https' ) );

		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'on';

		// Ensure that server host matches the host of wp_upload_dir().
		$upload_dir           = wp_upload_dir();
		$_SERVER['HTTP_HOST'] = parse_url( $upload_dir['baseurl'], PHP_URL_HOST );

		// Test that wp_get_attachemt_url returns with https scheme.
		$url = wp_get_attachment_url( $attachment_id );

		$this->assertSame( 'https', parse_url( $url, PHP_URL_SCHEME ) );
	}

	/**
	 * @ticket 15928
	 */
	public function test_wp_get_attachment_url_should_not_force_https_when_administering_over_https_but_siteurl_is_not_https() {
		$siteurl = get_option( 'siteurl' );
		update_option( 'siteurl', set_url_scheme( $siteurl, 'http' ) );

		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'on';
		set_current_screen( 'dashboard' );

		$url = wp_get_attachment_url( $attachment_id );

		$this->assertSame( set_url_scheme( $url, 'http' ), $url );
	}

	/**
	 * @ticket 15928
	 */
	public function test_wp_get_attachment_url_should_force_https_when_administering_over_https_and_siteurl_is_https() {
		// Set https upload URL.
		add_filter( 'upload_dir', '_upload_dir_https' );

		$filename = ( DIR_TESTDATA . '/images/test-image.jpg' );
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->assertEmpty( $upload['error'] );

		// Set attachment ID.
		$attachment_id = $this->_make_attachment( $upload );

		$_SERVER['HTTPS'] = 'on';
		set_current_screen( 'dashboard' );

		$url = wp_get_attachment_url( $attachment_id );

		// Cleanup.
		remove_filter( 'upload_dir', '_upload_dir_https' );

		$this->assertSame( 'https', parse_url( $url, PHP_URL_SCHEME ) );
	}

	public function test_wp_attachment_is() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload        = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$attachment_id = $this->_make_attachment( $upload );

		$this->assertTrue( wp_attachment_is_image( $attachment_id ) );
		$this->assertTrue( wp_attachment_is( 'image', $attachment_id ) );
		$this->assertFalse( wp_attachment_is( 'audio', $attachment_id ) );
		$this->assertFalse( wp_attachment_is( 'video', $attachment_id ) );
	}

	public function test_wp_attachment_is_default() {
		// On Multisite, psd is not an allowed mime type by default.
		if ( is_multisite() ) {
			add_filter( 'upload_mimes', array( $this, 'allow_psd_mime_type' ), 10, 2 );
		}

		$filename = DIR_TESTDATA . '/images/test-image.psd';
		$contents = file_get_contents( $filename );

		$upload        = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$attachment_id = $this->_make_attachment( $upload );

		$this->assertFalse( wp_attachment_is_image( $attachment_id ) );
		$this->assertTrue( wp_attachment_is( 'psd', $attachment_id ) );
		$this->assertFalse( wp_attachment_is( 'audio', $attachment_id ) );
		$this->assertFalse( wp_attachment_is( 'video', $attachment_id ) );

		if ( is_multisite() ) {
			remove_filter( 'upload_mimes', array( $this, 'allow_psd_mime_type' ), 10, 2 );
		}
	}

	public function test_upload_mimes_filter_is_applied() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );

		$this->assertFalse( $upload['error'] );

		add_filter( 'upload_mimes', array( $this, 'disallow_jpg_mime_type' ) );

		$upload = wp_upload_bits( wp_basename( $filename ), null, $contents );

		remove_filter( 'upload_mimes', array( $this, 'disallow_jpg_mime_type' ) );

		$this->assertNotEmpty( $upload['error'] );
	}

	public function allow_psd_mime_type( $mimes ) {
		$mimes['psd'] = 'application/octet-stream';
		return $mimes;
	}

	public function disallow_jpg_mime_type( $mimes ) {
		unset( $mimes['jpg|jpeg|jpe'] );
		return $mimes;
	}

	/**
	 * @ticket 33012
	 */
	public function test_wp_mime_type_icon() {
		$icon = wp_mime_type_icon();

		$this->assertStringContainsString( 'images/media/default.png', $icon );
	}

	/**
	 * @ticket 33012
	 */
	public function test_wp_mime_type_icon_video() {
		$icon = wp_mime_type_icon( 'video/mp4' );

		$this->assertStringContainsString( 'images/media/video.png', $icon );
	}
}
