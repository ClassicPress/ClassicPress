<?php

abstract class WP_Test_REST_TestCase extends WP_UnitTestCase {
	protected function assertErrorResponse( $code, $response, $status = null ) {

		if ( is_a( $response, 'WP_REST_Response' ) ) {
			$response = $response->as_error();
		}

<<<<<<< HEAD
		$this->assertInstanceOf( 'WP_Error', $response );
		$this->assertEquals( $code, $response->get_error_code() );
=======
		$this->assertWPError( $response );
		$this->assertSame( $code, $response->get_error_code() );
>>>>>>> 62d5c54b67 (Tests: Replace most instances of `assertEquals()` in `phpunit/includes/` with `assertSame()`.)

		if ( null !== $status ) {
			$data = $response->get_error_data();
			$this->assertArrayHasKey( 'status', $data );
			$this->assertSame( $status, $data['status'] );
		}
	}
}
