<?php
/**
 * Unit tests covering WP_REST_Controller functionality
 *
 * @package ClassicPress
 * @subpackage REST API
 */

/**
 * @group restapi
 */
class WP_Test_REST_Controller extends WP_Test_REST_TestCase {

<<<<<<< HEAD
	public function setUp() {
		parent::setUp();
		$this->request = new WP_REST_Request( 'GET', '/wp/v2/testroute', array(
=======
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		// Load the WP_REST_Test_Controller class if not already loaded.
		require_once __DIR__ . '/rest-test-controller.php';
	}

	public function set_up() {
		parent::set_up();
		$this->request = new WP_REST_Request(
			'GET',
			'/wp/v2/testroute',
			array(
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
			'args'     => array(
				'someinteger'     => array(
					'type'        => 'integer',
				),
				'someboolean'     => array(
					'type'        => 'boolean',
				),
				'somestring'      => array(
					'type'        => 'string',
				),
				'someenum'        => array(
					'type'        => 'string',
					'enum'        => array( 'a' ),
				),
				'somedate'        => array(
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'someemail'       => array(
					'type'        => 'string',
					'format'      => 'email',
				),
			),
<<<<<<< HEAD
		));
=======
				),
			)
		);
	}

	public function tear_down() {
		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = array();

		parent::tear_down();
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
	}

	public function test_validate_schema_type_integer() {

		$this->assertTrue(
			rest_validate_request_arg( '123', $this->request, 'someinteger' )
		);

		$this->assertErrorResponse(
			'rest_invalid_param',
			rest_validate_request_arg( 'abc', $this->request, 'someinteger' )
		);
	}

	public function test_validate_schema_type_boolean() {

		$this->assertTrue(
			rest_validate_request_arg( true, $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( false, $this->request, 'someboolean' )
		);

		$this->assertTrue(
			rest_validate_request_arg( 'true', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( 'TRUE', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( 'false', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( 'False', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( '1', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( '0', $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( 1, $this->request, 'someboolean' )
		);
		$this->assertTrue(
			rest_validate_request_arg( 0, $this->request, 'someboolean' )
		);

		// Check sanitize testing.
		$this->assertSame(
			false,
			rest_sanitize_request_arg( 'false', $this->request, 'someboolean' )
		);
		$this->assertSame(
			false,
			rest_sanitize_request_arg( '0', $this->request, 'someboolean' )
		);
		$this->assertSame(
			false,
			rest_sanitize_request_arg( 0, $this->request, 'someboolean' )
		);
		$this->assertSame(
			false,
			rest_sanitize_request_arg( 'FALSE', $this->request, 'someboolean' )
		);
		$this->assertSame(
			true,
			rest_sanitize_request_arg( 'true', $this->request, 'someboolean' )
		);
		$this->assertSame(
			true,
			rest_sanitize_request_arg( '1', $this->request, 'someboolean' )
		);
		$this->assertSame(
			true,
			rest_sanitize_request_arg( 1, $this->request, 'someboolean' )
		);
		$this->assertSame(
			true,
			rest_sanitize_request_arg( 'TRUE', $this->request, 'someboolean' )
		);

		$this->assertErrorResponse(
			'rest_invalid_param',
			rest_validate_request_arg( '123', $this->request, 'someboolean' )
		);
	}

	public function test_validate_schema_type_string() {

		$this->assertTrue(
			rest_validate_request_arg( '123', $this->request, 'somestring' )
		);

		$this->assertErrorResponse(
			'rest_invalid_param',
			rest_validate_request_arg( array( 'foo' => 'bar' ), $this->request, 'somestring' )
		);
	}

	public function test_validate_schema_enum() {

		$this->assertTrue(
			rest_validate_request_arg( 'a', $this->request, 'someenum' )
		);

		$this->assertErrorResponse(
			'rest_invalid_param',
			rest_validate_request_arg( 'd', $this->request, 'someenum' )
		);
	}

	public function test_validate_schema_format_email() {

		$this->assertTrue(
			rest_validate_request_arg( 'joe@foo.bar', $this->request, 'someemail' )
		);

		$this->assertErrorResponse(
			'rest_invalid_email',
			rest_validate_request_arg( 'd', $this->request, 'someemail' )
		);
	}

	public function test_validate_schema_format_date_time() {

		$this->assertTrue(
			rest_validate_request_arg( '2010-01-01T12:00:00', $this->request, 'somedate' )
		);

		$this->assertErrorResponse(
			'rest_invalid_date',
			rest_validate_request_arg( '2010-18-18T12:00:00', $this->request, 'somedate' )
		);
	}

	public function test_get_endpoint_args_for_item_schema_description() {
		$controller = new WP_REST_Test_Controller();
		$args       = $controller->get_endpoint_args_for_item_schema();
		$this->assertSame( 'A pretty string.', $args['somestring']['description'] );
		$this->assertFalse( isset( $args['someinteger']['description'] ) );
	}

	public function test_get_endpoint_args_for_item_schema_arg_options() {

		$controller = new WP_REST_Test_Controller();
		$args       = $controller->get_endpoint_args_for_item_schema();

		$this->assertFalse( $args['someargoptions']['required'] );
		$this->assertSame( '__return_true', $args['someargoptions']['sanitize_callback'] );
	}

	public function test_get_endpoint_args_for_item_schema_default_value() {

		$controller = new WP_REST_Test_Controller();

		$args = $controller->get_endpoint_args_for_item_schema();

		$this->assertSame( 'a', $args['somedefault']['default'] );
	}

	public function test_get_fields_for_response() {
		$controller = new WP_REST_Test_Controller();
		$request    = new WP_REST_Request( 'GET', '/wp/v2/testroute' );
		$fields     = $controller->get_fields_for_response( $request );
		$this->assertSame(
			array(
				'somestring',
				'someinteger',
				'someboolean',
				'someurl',
				'somedate',
				'someemail',
				'someenum',
				'someargoptions',
				'somedefault',
			),
			$fields
		);
	}
}
