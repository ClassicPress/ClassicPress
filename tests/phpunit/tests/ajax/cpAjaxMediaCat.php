<?php

/**
 * Admin Ajax functions to be tested.
 */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

/**
 * Class for testing Media Category Meta AJAX functionality.
 *
 * @group ajax
 *
 * @covers ::wp_ajax_media_cat_upload
 */
class Tests_Ajax_cpAjaxMediaCat extends WP_Ajax_UnitTestCase {

	/**
	 * Test the AJAX response when Media Category is left empty.
	 */
	public function test_wp_ajax_media_cat_upload_empty() {
		// Become an administrator.
		$this->_setRole( 'administrator' );

		$_POST = array(
			'media_cat_upload_nonce' => wp_create_nonce( 'media-cat-upload' ),
			'media_cat_upload_value' => '',
		);

		// Make the request.
		try {
			$this->_handleAjax( 'media-cat-upload' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertTrue( $response->success );
		$this->assertSame( '', $response->data->value );
		$this->assertStringContainsString( 'choose', $response->data->message );
	}

	/**
	 * Test the AJAX response when Media Category is populates with `images`.
	 * Also check that the database option is set as expected.
	 */
	public function test_wp_ajax_media_cat_upload_new_value() {
		// Become an administrator.
		$this->_setRole( 'administrator' );

		$_POST = array(
			'media_cat_upload_nonce' => wp_create_nonce( 'media-cat-upload' ),
			'media_cat_upload_value' => 'images',
		);

		// Make the request.
		try {
			$this->_handleAjax( 'media-cat-upload' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response );

		$this->assertTrue( $response->success );
		$this->assertSame( 'images', $response->data->value );
		$this->assertStringContainsString( 'updated', $response->data->message );

		$upload_folder = get_option( 'media_cat_upload_folder' );
		$this->assertSame( '/images', $upload_folder );
	}

	/**
	 * Test AJAX submission without nonce
	 *
	 * Expects test to fail.
	 */
	public function test_wp_ajax_media_cat_upload_no_nonce() {
		// Become an administrator.
		$this->_setRole( 'administrator' );

		$_POST = array(
			'media_cat_upload_value' => 'images',
		);

		$this->expectException( 'WPAjaxDieStopException' );
		$this->expectExceptionMessage( '-1' );

		// Make the request.
		$this->_handleAjax( 'media-cat-upload' );
	}
}
