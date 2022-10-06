<?php

/**
 * @group xmlrpc
 */
class Tests_XMLRPC_wp_getTaxonomies extends WP_XMLRPC_UnitTestCase {

	function test_invalid_username_password() {
		$result = $this->myxmlrpcserver->wp_getTaxonomies( array( 1, 'username', 'password' ) );
		$this->assertIXRError( $result );
		$this->assertSame( 403, $result->code );
	}

	function test_taxonomy_validated() {
		$this->make_user_by_role( 'editor' );

		$result = $this->myxmlrpcserver->wp_getTaxonomies( array( 1, 'editor', 'editor' ) );
		$this->assertNotIXRError( $result );
	}
}
