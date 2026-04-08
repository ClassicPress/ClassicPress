<?php

/**
 * Tests that the old Requests class is included
 * for plugins or themes that still use it.
 *
 * @group http
 */
class Tests_HTTP_IncludeOldRequestsClass extends WP_UnitTestCase {

	/**
	 * @ticket 57341
	 *
	 * @coversNothing
	 */
	public function test_should_include_old_requests_class() {
		$expectDeprecationMessage = 'The PSR-0 `Requests_...` class names in the Requests library are deprecated.'
		. ' Switch to the PSR-4 `WpOrg\Requests\...` class names at your earliest convenience.';

		try {
			new Requests();
		} catch ( Requests_Exception $e ) {
			throw $e;
		} catch ( \Throwable $e ) {
			$this->assertSame( $expectDeprecationMessage, $e->getMessage() );
		}
	}
}
