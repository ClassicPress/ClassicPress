<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_wp_uploadFile extends WP_XMLRPC_UnitTestCase {

	public function tearDown() {
		$this->remove_added_uploads();

		parent::tearDown();
	}

	function test_valid_attachment() {
		$this->make_user_by_role( 'editor' );

		// create attachment
		$filename = ( DIR_TESTDATA . '/images/a2-small.jpg' );
		$contents = file_get_contents( $filename );
		$data     = array(
			'name' => 'a2-small.jpg',
			'type' => 'image/jpeg',
			'bits' => $contents,
		);

		$result = $this->myxmlrpcserver->mw_newMediaObject( array( 0, 'editor', 'editor', $data ) );
		$this->assertNotIXRError( $result );

		// check data types
		$this->assertInternalType( 'string', $result['id'] );
		$this->assertStringMatchesFormat( '%d', $result['id'] );
		$this->assertInternalType( 'string', $result['file'] );
		$this->assertInternalType( 'string', $result['url'] );
		$this->assertInternalType( 'string', $result['type'] );
	}
}
