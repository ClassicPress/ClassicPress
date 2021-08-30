<?php
/**
 * REST API functions.
 *
 * @package ClassicPress
 * @subpackage REST API
 */

require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . WPINC . '/rest-api.php';

/**
 * @group restapi
 */
class Tests_REST_API extends WP_UnitTestCase {
	public function setUp() {
		// Override the normal server with our spying server.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		parent::setUp();
	}

	public function tearDown() {
		remove_filter( 'wp_rest_server_class', array( $this, 'filter_wp_rest_server_class' ) );
		parent::tearDown();
	}

	/**
	 * Checks that the main classes are loaded.
	 */
	function test_rest_api_active() {
		$this->assertTrue( class_exists( 'WP_REST_Server' ) );
		$this->assertTrue( class_exists( 'WP_REST_Request' ) );
		$this->assertTrue( class_exists( 'WP_REST_Response' ) );
		$this->assertTrue( class_exists( 'WP_REST_Posts_Controller' ) );
	}

	/**
	 * The rest_api_init hook should have been registered with init, and should
	 * have a default priority of 10.
	 */
	function test_init_action_added() {
		$this->assertEquals( 10, has_action( 'init', 'rest_api_init' ) );
	}

	public function test_add_extra_api_taxonomy_arguments() {
		$taxonomy = get_taxonomy( 'category' );
		$this->assertTrue( $taxonomy->show_in_rest );
		$this->assertEquals( 'categories', $taxonomy->rest_base );
		$this->assertEquals( 'WP_REST_Terms_Controller', $taxonomy->rest_controller_class );

		$taxonomy = get_taxonomy( 'post_tag' );
		$this->assertTrue( $taxonomy->show_in_rest );
		$this->assertEquals( 'tags', $taxonomy->rest_base );
		$this->assertEquals( 'WP_REST_Terms_Controller', $taxonomy->rest_controller_class );
	}

	public function test_add_extra_api_post_type_arguments() {
		$post_type = get_post_type_object( 'post' );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertEquals( 'posts', $post_type->rest_base );
		$this->assertEquals( 'WP_REST_Posts_Controller', $post_type->rest_controller_class );

		$post_type = get_post_type_object( 'page' );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertEquals( 'pages', $post_type->rest_base );
		$this->assertEquals( 'WP_REST_Posts_Controller', $post_type->rest_controller_class );

		$post_type = get_post_type_object( 'attachment' );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertEquals( 'media', $post_type->rest_base );
		$this->assertEquals( 'WP_REST_Attachments_Controller', $post_type->rest_controller_class );
	}

	/**
	 * Check that a single route is canonicalized.
	 *
	 * Ensures that single and multiple routes are handled correctly.
	 */
	public function test_route_canonicalized() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'GET' ),
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		// Check the route was registered correctly.
		$endpoints = $GLOBALS['wp_rest_server']->get_raw_endpoint_data();
		$this->assertArrayHasKey( '/test-ns/test', $endpoints );

		// Check the route was wrapped in an array.
		$endpoint = $endpoints['/test-ns/test'];
		$this->assertArrayNotHasKey( 'callback', $endpoint );
		$this->assertArrayHasKey( 'namespace', $endpoint );
		$this->assertEquals( 'test-ns', $endpoint['namespace'] );

		// Grab the filtered data.
		$filtered_endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$this->assertArrayHasKey( '/test-ns/test', $filtered_endpoints );
		$endpoint = $filtered_endpoints['/test-ns/test'];
		$this->assertCount( 1, $endpoint );
		$this->assertArrayHasKey( 'callback', $endpoint[0] );
		$this->assertArrayHasKey( 'methods',  $endpoint[0] );
		$this->assertArrayHasKey( 'args',     $endpoint[0] );
	}

	/**
	 * Check that a single route is canonicalized.
	 *
	 * Ensures that single and multiple routes are handled correctly.
	 */
	public function test_route_canonicalized_multiple() {
		register_rest_route( 'test-ns', '/test', array(
			array(
<<<<<<< HEAD
				'methods'  => array( 'GET' ),
				'callback' => '__return_null',
			),
			array(
				'methods'  => array( 'POST' ),
				'callback' => '__return_null',
			),
		) );
=======
				array(
					'methods'             => array( 'GET' ),
					'callback'            => '__return_null',
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => array( 'POST' ),
					'callback'            => '__return_null',
					'permission_callback' => '__return_true',
				),
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		// Check the route was registered correctly.
		$endpoints = $GLOBALS['wp_rest_server']->get_raw_endpoint_data();
		$this->assertArrayHasKey( '/test-ns/test', $endpoints );

		// Check the route was wrapped in an array.
		$endpoint = $endpoints['/test-ns/test'];
		$this->assertArrayNotHasKey( 'callback', $endpoint );
		$this->assertArrayHasKey( 'namespace', $endpoint );
		$this->assertEquals( 'test-ns', $endpoint['namespace'] );

		$filtered_endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$endpoint = $filtered_endpoints['/test-ns/test'];
		$this->assertCount( 2, $endpoint );

		// Check for both methods.
		foreach ( array( 0, 1 ) as $key ) {
			$this->assertArrayHasKey( 'callback', $endpoint[ $key ] );
			$this->assertArrayHasKey( 'methods',  $endpoint[ $key ] );
			$this->assertArrayHasKey( 'args',     $endpoint[ $key ] );
		}
	}

	/**
	 * Check that routes are merged by default.
	 */
	public function test_route_merge() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'GET' ),
			'callback' => '__return_null',
		) );
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'POST' ),
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		// Check both routes exist.
		$endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$endpoint = $endpoints['/test-ns/test'];
		$this->assertCount( 2, $endpoint );
	}

	/**
	 * Check that we can override routes.
	 */
	public function test_route_override() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'      => array( 'GET' ),
			'callback'     => '__return_null',
			'should_exist' => false,
		) );
		register_rest_route( 'test-ns', '/test', array(
			'methods'      => array( 'POST' ),
			'callback'     => '__return_null',
			'should_exist' => true,
		), true );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
				'should_exist'        => false,
			)
		);
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
				'should_exist'        => true,
			),
			true
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		// Check we only have one route.
		$endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$endpoint = $endpoints['/test-ns/test'];
		$this->assertCount( 1, $endpoint );

		// Check it's the right one.
		$this->assertArrayHasKey( 'should_exist', $endpoint[0] );
		$this->assertTrue( $endpoint[0]['should_exist'] );
	}

	/**
	 * Test that we reject routes without namespaces
	 *
	 * @expectedIncorrectUsage register_rest_route
	 */
	public function test_route_reject_empty_namespace() {
<<<<<<< HEAD
		register_rest_route( '', '/test-empty-namespace', array(
			'methods'      => array( 'POST' ),
			'callback'     => '__return_null',
		), true );
=======
		register_rest_route(
			'',
			'/test-empty-namespace',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			),
			true
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)
		$endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$this->assertFalse( isset( $endpoints['/test-empty-namespace'] ) );
	}

	/**
	 * Test that we reject empty routes
	 *
	 * @expectedIncorrectUsage register_rest_route
	 */
	public function test_route_reject_empty_route() {
<<<<<<< HEAD
		register_rest_route( '/test-empty-route', '', array(
			'methods'      => array( 'POST' ),
			'callback'     => '__return_null',
		), true );
=======
		register_rest_route(
			'/test-empty-route',
			'',
			array(
				'methods'             => array( 'POST' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			),
			true
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)
		$endpoints = $GLOBALS['wp_rest_server']->get_routes();
		$this->assertFalse( isset( $endpoints['/test-empty-route'] ) );
	}

	/**
	 * The rest_route query variable should be registered.
	 */
	function test_rest_route_query_var() {
		rest_api_init();
		$this->assertTrue( in_array( 'rest_route', $GLOBALS['wp']->public_query_vars ) );
	}

	public function test_route_method() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'GET' ),
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertEquals( $routes['/test-ns/test'][0]['methods'], array( 'GET' => true ) );
	}

	/**
	 * The 'methods' arg should accept a single value as well as array.
	 */
	public function test_route_method_string() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => 'GET',
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => 'GET',
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertEquals( $routes['/test-ns/test'][0]['methods'], array( 'GET' => true ) );
	}

	/**
	 * The 'methods' arg should accept a single value as well as array.
	 */
	public function test_route_method_array() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'GET', 'POST' ),
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET', 'POST' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertEquals( $routes['/test-ns/test'][0]['methods'], array( 'GET' => true, 'POST' => true ) );
	}

	/**
	 * The 'methods' arg should a comma seperated string.
	 */
<<<<<<< HEAD
	public function test_route_method_comma_seperated() {
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => 'GET,POST',
			'callback' => '__return_null',
		) );
=======
	public function test_route_method_comma_separated() {
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => 'GET,POST',
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$routes = $GLOBALS['wp_rest_server']->get_routes();

		$this->assertEquals( $routes['/test-ns/test'][0]['methods'], array( 'GET' => true, 'POST' => true ) );
	}

	public function test_options_request() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => 'GET,POST',
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => 'GET,POST',
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$request = new WP_REST_Request( 'OPTIONS', '/test-ns/test' );
		$response = rest_handle_options_request( null, $GLOBALS['wp_rest_server'], $request );
		$response = rest_send_allow_header( $response, $GLOBALS['wp_rest_server'], $request );
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Allow', $headers );

		$this->assertEquals( 'GET, POST', $headers['Allow'] );
	}

	/**
	 * Ensure that the OPTIONS handler doesn't kick in for non-OPTIONS requests.
	 */
	public function test_options_request_not_options() {
<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => 'GET,POST',
			'callback' => '__return_true',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => 'GET,POST',
				'callback'            => '__return_true',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$request = new WP_REST_Request( 'GET', '/test-ns/test' );
		$response = rest_handle_options_request( null, $GLOBALS['wp_rest_server'], $request );

		$this->assertNull( $response );
	}

	/**
	 * Ensure that result fields are not whitelisted if no request['_fields'] is present.
	 */
	public function test_rest_filter_response_fields_no_request_filter() {
		$response = new WP_REST_Response();
		$response->set_data( array( 'a' => true ) );
		$request = array();

		$response = rest_filter_response_fields( $response, null, $request );
		$this->assertEquals( array( 'a' => true ), $response->get_data() );
	}

	/**
	 * Ensure that result fields are whitelisted if request['_fields'] is present.
	 */
	public function test_rest_filter_response_fields_single_field_filter() {
		$response = new WP_REST_Response();
		$response->set_data( array(
			'a' => 0,
			'b' => 1,
			'c' => 2,
		) );
		$request = array(
			'_fields' => 'b'
		);

		$response = rest_filter_response_fields( $response, null, $request );
		$this->assertEquals( array( 'b' => 1 ), $response->get_data() );
	}

	/**
	 * Ensure that multiple comma-separated fields may be whitelisted with request['_fields'].
	 */
	public function test_rest_filter_response_fields_multi_field_filter() {
		$response = new WP_REST_Response();
		$response->set_data( array(
			'a' => 0,
			'b' => 1,
			'c' => 2,
			'd' => 3,
			'e' => 4,
			'f' => 5,
		) );
		$request = array(
			'_fields' => 'b,c,e'
		);

		$response = rest_filter_response_fields( $response, null, $request );
		$this->assertEquals( array(
			'b' => 1,
			'c' => 2,
			'e' => 4,
		), $response->get_data() );
	}

	/**
	 * Ensure that multiple comma-separated fields may be whitelisted
	 * with request['_fields'] using query parameter array syntax.
	 */
	public function test_rest_filter_response_fields_multi_field_filter_array() {
		$response = new WP_REST_Response();

		$response->set_data( array(
			'a' => 0,
			'b' => 1,
			'c' => 2,
			'd' => 3,
			'e' => 4,
			'f' => 5,
		) );
		$request = array(
			'_fields' => array( 'b', 'c', 'e' )
		);

		$response = rest_filter_response_fields( $response, null, $request );
		$this->assertEquals( array(
			'b' => 1,
			'c' => 2,
			'e' => 4,
		), $response->get_data() );
	}

	/**
	 * Ensure that request['_fields'] whitelists apply to items in response collections.
	 */
	public function test_rest_filter_response_fields_numeric_array() {
		$response = new WP_REST_Response();
		$response->set_data( array(
			array(
				'a' => 0,
				'b' => 1,
				'c' => 2,
			),
			array(
				'a' => 3,
				'b' => 4,
				'c' => 5,
			),
			array(
				'a' => 6,
				'b' => 7,
				'c' => 8,
			),
		) );
		$request = array(
			'_fields' => 'b,c'
		);

		$response = rest_filter_response_fields( $response, null, $request );
		$this->assertEquals( array(
			array(
				'b' => 1,
				'c' => 2,
			),
			array(
				'b' => 4,
				'c' => 5,
			),
			array(
				'b' => 7,
				'c' => 8,
			),
		), $response->get_data() );
	}

	/**
	 * The get_rest_url function should return a URL consistently terminated with a "/",
	 * whether the blog is configured with pretty permalink support or not.
	 */
	public function test_rest_url_generation() {
		// In pretty permalinks case, we expect a path of wp-json/ with no query.
		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/wp-json/', get_rest_url() );

		// In index permalinks case, we expect a path of index.php/wp-json/ with no query.
		$this->set_permalink_structure( '/index.php/%year%/%monthnum%/%day%/%postname%/' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/index.php/wp-json/', get_rest_url() );

		// In non-pretty case, we get a query string to invoke the rest router.
		$this->set_permalink_structure( '' );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '/index.php?rest_route=/', get_rest_url() );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34299
	 */
	public function test_rest_url_scheme() {
		$_SERVER['SERVER_NAME'] = parse_url( home_url(), PHP_URL_HOST );
		$_siteurl = get_option( 'siteurl' );

		set_current_screen( 'edit.php' );
		$this->assertTrue( is_admin() );

		// Test an HTTP URL
		unset( $_SERVER['HTTPS'] );
		$url = get_rest_url();
		$this->assertSame( 'http', parse_url( $url, PHP_URL_SCHEME ) );

		// Test an HTTPS URL
		$_SERVER['HTTPS'] = 'on';
		$url = get_rest_url();
		$this->assertSame( 'https', parse_url( $url, PHP_URL_SCHEME ) );

		// Switch to an admin request on a different domain name
		$_SERVER['SERVER_NAME'] = 'admin.example.org';
		update_option( 'siteurl', 'http://admin.example.org' );
		$this->assertNotEquals( $_SERVER['SERVER_NAME'], parse_url( home_url(), PHP_URL_HOST ) );

		// // Test an HTTP URL
		unset( $_SERVER['HTTPS'] );
		$url = get_rest_url();
		$this->assertSame( 'http', parse_url( $url, PHP_URL_SCHEME ) );

		// // Test an HTTPS URL
		$_SERVER['HTTPS'] = 'on';
		$url = get_rest_url();
		$this->assertSame( 'http', parse_url( $url, PHP_URL_SCHEME ) );

		// Reset
		update_option( 'siteurl', $_siteurl );
		set_current_screen( 'front' );

	}

	public function jsonp_callback_provider() {
		return array(
			// Standard names
			array( 'Springfield', true ),
			array( 'shelby.ville', true ),
			array( 'cypress_creek', true ),
			array( 'KampKrusty1', true ),

			// Invalid names
			array( 'ogden-ville', false ),
			array( 'north haverbrook', false ),
			array( "Terror['Lake']", false ),
			array( 'Cape[Feare]', false ),
			array( '"NewHorrorfield"', false ),
			array( 'Scream\\ville', false ),
		);
	}

	/**
	 * @dataProvider jsonp_callback_provider
	 */
	public function test_jsonp_callback_check( $callback, $valid ) {
		$this->assertEquals( $valid, wp_check_jsonp_callback( $callback ) );
	}

	public function rest_date_provider() {
		return array(
			// Valid dates with timezones
			array( '2017-01-16T11:30:00-05:00', gmmktime( 11, 30,  0,  1, 16, 2017 ) + 5 * HOUR_IN_SECONDS ),
			array( '2017-01-16T11:30:00-05:30', gmmktime( 11, 30,  0,  1, 16, 2017 ) + 5.5 * HOUR_IN_SECONDS ),
			array( '2017-01-16T11:30:00-05'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) + 5 * HOUR_IN_SECONDS ),
			array( '2017-01-16T11:30:00+05'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) - 5 * HOUR_IN_SECONDS ),
			array( '2017-01-16T11:30:00-00'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00+00'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00Z'     , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),

			// Valid dates without timezones
			array( '2017-01-16T11:30:00'      , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),

			// Invalid dates (TODO: support parsing partial dates as ranges, see https://core.trac.wordpress.org/ticket/38641)
			array( '2017-01-16T11:30:00-5', false ),
			array( '2017-01-16T11:30', false ),
			array( '2017-01-16T11', false ),
			array( '2017-01-16T', false ),
			array( '2017-01-16', false ),
			array( '2017-01', false ),
			array( '2017', false ),
		);
	}

	/**
	 * @dataProvider rest_date_provider
	 */
	public function test_rest_parse_date( $string, $value ) {
		$this->assertEquals( $value, rest_parse_date( $string ) );
	}

	public function rest_date_force_utc_provider() {
		return array(
			// Valid dates with timezones
			array( '2017-01-16T11:30:00-05:00', gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00-05:30', gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00-05'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00+05'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00-00'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00+00'   , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),
			array( '2017-01-16T11:30:00Z'     , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),

			// Valid dates without timezones
			array( '2017-01-16T11:30:00'      , gmmktime( 11, 30,  0,  1, 16, 2017 ) ),

			// Invalid dates (TODO: support parsing partial dates as ranges, see https://core.trac.wordpress.org/ticket/38641)
			array( '2017-01-16T11:30:00-5', false ),
			array( '2017-01-16T11:30', false ),
			array( '2017-01-16T11', false ),
			array( '2017-01-16T', false ),
			array( '2017-01-16', false ),
			array( '2017-01', false ),
			array( '2017', false ),
		);
	}

	/**
	 * @dataProvider rest_date_force_utc_provider
	 */
	public function test_rest_parse_date_force_utc( $string, $value ) {
		$this->assertEquals( $value, rest_parse_date( $string, true ) );
	}

	public function filter_wp_rest_server_class( $class_name ) {
		return 'Spy_REST_Server';
	}

	public function test_register_rest_route_without_server() {
		$GLOBALS['wp_rest_server'] = null;
		add_filter( 'wp_rest_server_class', array( $this, 'filter_wp_rest_server_class' ) );

<<<<<<< HEAD
		register_rest_route( 'test-ns', '/test', array(
			'methods'  => array( 'GET' ),
			'callback' => '__return_null',
		) );
=======
		register_rest_route(
			'test-ns',
			'/test',
			array(
				'methods'             => array( 'GET' ),
				'callback'            => '__return_null',
				'permission_callback' => '__return_true',
			)
		);
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)

		$routes = $GLOBALS['wp_rest_server']->get_routes();
		$this->assertEquals( $routes['/test-ns/test'][0]['methods'], array( 'GET' => true ) );
	}
<<<<<<< HEAD
=======

	function test_rest_preload_api_request_with_method() {
		$rest_server               = $GLOBALS['wp_rest_server'];
		$GLOBALS['wp_rest_server'] = null;

		$preload_paths = array(
			'/wp/v2/types',
			array( '/wp/v2/media', 'OPTIONS' ),
		);

		$preload_data = array_reduce(
			$preload_paths,
			'rest_preload_api_request',
			array()
		);

		$this->assertSame( array_keys( $preload_data ), array( '/wp/v2/types', 'OPTIONS' ) );
		$this->assertTrue( isset( $preload_data['OPTIONS']['/wp/v2/media'] ) );

		$GLOBALS['wp_rest_server'] = $rest_server;
	}

	/**
	 * @ticket 40614
	 */
	function test_rest_ensure_request_accepts_path_string() {
		$request = rest_ensure_request( '/wp/v2/posts' );
		$this->assertInstanceOf( 'WP_REST_Request', $request );
		$this->assertEquals( '/wp/v2/posts', $request->get_route() );
		$this->assertEquals( 'GET', $request->get_method() );
	}

	/**
	 * @dataProvider _dp_rest_parse_embed_param
	 */
	public function test_rest_parse_embed_param( $expected, $embed ) {
		$this->assertEquals( $expected, rest_parse_embed_param( $embed ) );
	}

	public function _dp_rest_parse_embed_param() {
		return array(
			array( true, '' ),
			array( true, null ),
			array( true, '1' ),
			array( true, 'true' ),
			array( true, array() ),
			array( array( 'author' ), 'author' ),
			array( array( 'author', 'replies' ), 'author,replies' ),
			array( array( 'author', 'replies' ), 'author,replies ' ),
			array( array( 'wp:term' ), 'wp:term' ),
			array( array( 'wp:term', 'wp:attachment' ), 'wp:term,wp:attachment' ),
			array( array( 'author' ), array( 'author' ) ),
			array( array( 'author', 'replies' ), array( 'author', 'replies' ) ),
			array( array( 'https://api.w.org/term' ), 'https://api.w.org/term' ),
			array( array( 'https://api.w.org/term', 'https://api.w.org/attachment' ), 'https://api.w.org/term,https://api.w.org/attachment' ),
			array( array( 'https://api.w.org/term', 'https://api.w.org/attachment' ), array( 'https://api.w.org/term', 'https://api.w.org/attachment' ) ),
		);
	}

	/**
	 * @ticket 48819
	 *
	 * @dataProvider _dp_rest_filter_response_by_context
	 */
	public function test_rest_filter_response_by_context( $schema, $data, $expected ) {
		$this->assertEquals( $expected, rest_filter_response_by_context( $data, $schema, 'view' ) );
	}

	/**
	 * @ticket 49749
	 */
	public function test_register_route_with_invalid_namespace() {
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		register_rest_route(
			'/my-namespace/v1/',
			'/my-route',
			array(
				'callback'            => '__return_true',
				'permission_callback' => '__return_true',
			)
		);

		$routes = rest_get_server()->get_routes( 'my-namespace/v1' );
		$this->assertCount( 2, $routes );

		$this->assertTrue( rest_do_request( '/my-namespace/v1/my-route' )->get_data() );
	}

	/**
	 * @ticket 50075
	 */
	public function test_register_route_with_missing_permission_callback_top_level_route() {
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		$registered = register_rest_route(
			'my-ns/v1',
			'/my-route',
			array(
				'callback' => '__return_true',
			)
		);

		$this->assertTrue( $registered );
	}

	/**
	 * @ticket 50075
	 */
	public function test_register_route_with_missing_permission_callback_single_wrapped_route() {
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		$registered = register_rest_route(
			'my-ns/v1',
			'/my-route',
			array(
				array(
					'callback' => '__return_true',
				),
			)
		);

		$this->assertTrue( $registered );
	}


	/**
	 * @ticket 50075
	 */
	public function test_register_route_with_missing_permission_callback_multiple_wrapped_route() {
		$this->setExpectedIncorrectUsage( 'register_rest_route' );

		$registered = register_rest_route(
			'my-ns/v1',
			'/my-route',
			array(
				array(
					'callback' => '__return_true',
				),
				array(
					'callback'            => '__return_true',
					'permission_callback' => '__return_true',
				),
			)
		);

		$this->assertTrue( $registered );
	}

	public function _dp_rest_filter_response_by_context() {
		return array(
			'default'                => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'first'  => array(
							'type'    => 'string',
							'context' => array( 'view', 'edit' ),
						),
						'second' => array(
							'type'    => 'string',
							'context' => array( 'edit' ),
						),
					),
				),
				array(
					'first'  => 'a',
					'second' => 'b',
				),
				array( 'first' => 'a' ),
			),
			'keeps missing context'  => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'first'  => array(
							'type'    => 'string',
							'context' => array( 'view', 'edit' ),
						),
						'second' => array(
							'type' => 'string',
						),
					),
				),
				array(
					'first'  => 'a',
					'second' => 'b',
				),
				array(
					'first'  => 'a',
					'second' => 'b',
				),
			),
			'removes empty context'  => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'first'  => array(
							'type'    => 'string',
							'context' => array( 'view', 'edit' ),
						),
						'second' => array(
							'type'    => 'string',
							'context' => array(),
						),
					),
				),
				array(
					'first'  => 'a',
					'second' => 'b',
				),
				array( 'first' => 'a' ),
			),
			'nested properties'      => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'parent' => array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'properties' => array(
								'child'  => array(
									'type'    => 'string',
									'context' => array( 'view', 'edit' ),
								),
								'hidden' => array(
									'type'    => 'string',
									'context' => array( 'edit' ),
								),
							),
						),
					),
				),
				array(
					'parent' => array(
						'child'  => 'hi',
						'hidden' => 'there',
					),
				),
				array( 'parent' => array( 'child' => 'hi' ) ),
			),
			'grand child properties' => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'parent' => array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'properties' => array(
								'child' => array(
									'type'       => 'object',
									'context'    => array( 'view', 'edit' ),
									'properties' => array(
										'grand'  => array(
											'type'    => 'string',
											'context' => array( 'view', 'edit' ),
										),
										'hidden' => array(
											'type'    => 'string',
											'context' => array( 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				array(
					'parent' => array(
						'child' => array(
							'grand' => 'hi',
						),
					),
				),
				array( 'parent' => array( 'child' => array( 'grand' => 'hi' ) ) ),
			),
			'array'                  => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'arr' => array(
							'type'    => 'array',
							'context' => array( 'view', 'edit' ),
							'items'   => array(
								'type'       => 'object',
								'context'    => array( 'view', 'edit' ),
								'properties' => array(
									'visible' => array(
										'type'    => 'string',
										'context' => array( 'view', 'edit' ),
									),
									'hidden'  => array(
										'type'    => 'string',
										'context' => array( 'edit' ),
									),
								),
							),
						),
					),
				),
				array(
					'arr' => array(
						array(
							'visible' => 'hi',
							'hidden'  => 'there',
						),
					),
				),
				array( 'arr' => array( array( 'visible' => 'hi' ) ) ),
			),
			'additional properties'  => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'additional' => array(
							'type'                 => 'object',
							'context'              => array( 'view', 'edit' ),
							'properties'           => array(
								'a' => array(
									'type'    => 'string',
									'context' => array( 'view', 'edit' ),
								),
								'b' => array(
									'type'    => 'string',
									'context' => array( 'edit' ),
								),
							),
							'additionalProperties' => array(
								'type'    => 'string',
								'context' => array( 'edit' ),
							),
						),
					),
				),
				array(
					'additional' => array(
						'a' => '1',
						'b' => '2',
						'c' => '3',
					),
				),
				array( 'additional' => array( 'a' => '1' ) ),
			),
			'multiple types object'  => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'multi' => array(
							'type'       => array( 'object', 'string' ),
							'context'    => array( 'view', 'edit' ),
							'properties' => array(
								'a' => array(
									'type'    => 'string',
									'context' => array( 'view', 'edit' ),
								),
								'b' => array(
									'type'    => 'string',
									'context' => array( 'edit' ),
								),
							),
						),
					),
				),
				array(
					'multi' => array(
						'a' => '1',
						'b' => '2',
					),
				),
				array( 'multi' => array( 'a' => '1' ) ),
			),
			'multiple types array'   => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'multi' => array(
							'type'    => array( 'array', 'string' ),
							'context' => array( 'view', 'edit' ),
							'items'   => array(
								'type'       => 'object',
								'context'    => array( 'view', 'edit' ),
								'properties' => array(
									'visible' => array(
										'type'    => 'string',
										'context' => array( 'view', 'edit' ),
									),
									'hidden'  => array(
										'type'    => 'string',
										'context' => array( 'edit' ),
									),
								),
							),
						),
					),
				),
				array(
					'multi' => array(
						array(
							'visible' => '1',
							'hidden'  => '2',
						),
					),
				),
				array( 'multi' => array( array( 'visible' => '1' ) ) ),
			),
			'grand child properties does not traverses missing context' => array(
				array(
					'$schema'    => 'http://json-schema.org/draft-04/schema#',
					'type'       => 'object',
					'properties' => array(
						'parent' => array(
							'type'       => 'object',
							'context'    => array( 'view', 'edit' ),
							'properties' => array(
								'child' => array(
									'type'       => 'object',
									'properties' => array(
										'grand'  => array(
											'type'    => 'string',
											'context' => array( 'view', 'edit' ),
										),
										'hidden' => array(
											'type'    => 'string',
											'context' => array( 'edit' ),
										),
									),
								),
							),
						),
					),
				),
				array(
					'parent' => array(
						'child' => array(
							'grand'  => 'hi',
							'hidden' => 'there',
						),
					),
				),
				array(
					'parent' => array(
						'child' => array(
							'grand'  => 'hi',
							'hidden' => 'there',
						),
					),
				),
			),
		);
	}

	function test_rest_ensure_response_accepts_wp_error_and_returns_wp_error() {
		$response = rest_ensure_response( new WP_Error() );
		$this->assertInstanceOf( 'WP_Error', $response );
	}

	/**
	 * @dataProvider rest_ensure_response_data_provider
	 *
	 * @param mixed $response      The response passed to rest_ensure_response().
	 * @param mixed $expected_data The expected data a response should include.
	 */
	function test_rest_ensure_response_returns_instance_of_wp_rest_response( $response, $expected_data ) {
		$response_object = rest_ensure_response( $response );
		$this->assertInstanceOf( 'WP_REST_Response', $response_object );
		$this->assertSame( $expected_data, $response_object->get_data() );
	}

	/**
	 * Data provider for test_rest_ensure_response_returns_instance_of_wp_rest_response().
	 *
	 * @return array
	 */
	function rest_ensure_response_data_provider() {
		return array(
			array( null, null ),
			array( array( 'chocolate' => 'cookies' ), array( 'chocolate' => 'cookies' ) ),
			array( 123, 123 ),
			array( true, true ),
			array( 'chocolate', 'chocolate' ),
			array( new WP_HTTP_Response( 'http' ), 'http' ),
			array( new WP_REST_Response( 'rest' ), 'rest' ),
		);
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post_non_post() {
		$this->assertEquals( '', rest_get_route_for_post( 'garbage' ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post_invalid_post_type() {
		register_post_type( 'invalid' );
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'invalid' ) );
		unregister_post_type( 'invalid' );

		$this->assertEquals( '', rest_get_route_for_post( $post ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post_non_rest() {
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'custom_css' ) );
		$this->assertEquals( '', rest_get_route_for_post( $post ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post_custom_controller() {
		$post = self::factory()->post->create_and_get( array( 'post_type' => 'wp_block' ) );
		$this->assertEquals( '', rest_get_route_for_post( $post ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post() {
		$post = self::factory()->post->create_and_get();
		$this->assertEquals( '/wp/v2/posts/' . $post->ID, rest_get_route_for_post( $post ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_media() {
		$post = self::factory()->attachment->create_and_get();
		$this->assertEquals( '/wp/v2/media/' . $post->ID, rest_get_route_for_post( $post ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_post_id() {
		$post = self::factory()->post->create_and_get();
		$this->assertEquals( '/wp/v2/posts/' . $post->ID, rest_get_route_for_post( $post->ID ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_term_non_term() {
		$this->assertEquals( '', rest_get_route_for_term( 'garbage' ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_term_invalid_term_type() {
		register_taxonomy( 'invalid', 'post' );
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'invalid' ) );
		unregister_taxonomy( 'invalid' );

		$this->assertEquals( '', rest_get_route_for_term( $term ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_term_non_rest() {
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'post_format' ) );
		$this->assertEquals( '', rest_get_route_for_term( $term ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_term() {
		$term = self::factory()->term->create_and_get();
		$this->assertEquals( '/wp/v2/tags/' . $term->term_id, rest_get_route_for_term( $term ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_category() {
		$term = self::factory()->category->create_and_get();
		$this->assertEquals( '/wp/v2/categories/' . $term->term_id, rest_get_route_for_term( $term ) );
	}

	/**
	 * @ticket 49116
	 */
	public function test_rest_get_route_for_term_id() {
		$term = self::factory()->term->create_and_get();
		$this->assertEquals( '/wp/v2/tags/' . $term->term_id, rest_get_route_for_term( $term->term_id ) );
	}

	/**
	 * @ticket 50300
	 *
	 * @dataProvider _dp_rest_is_object
	 *
	 * @param bool  $expected Expected result of the check.
	 * @param mixed $value    The value to check.
	 */
	public function test_rest_is_object( $expected, $value ) {
		$is_object = rest_is_object( $value );

		if ( $expected ) {
			$this->assertTrue( $is_object );
		} else {
			$this->assertFalse( $is_object );
		}
	}

	public function _dp_rest_is_object() {
		return array(
			array(
				true,
				'',
			),
			array(
				true,
				new stdClass(),
			),
			array(
				true,
				new JsonSerializable_Object( array( 'hi' => 'there' ) ),
			),
			array(
				true,
				array( 'hi' => 'there' ),
			),
			array(
				true,
				array(),
			),
			array(
				true,
				array( 'a', 'b' ),
			),
			array(
				false,
				new Basic_Object(),
			),
			array(
				false,
				new JsonSerializable_Object( 'str' ),
			),
			array(
				false,
				'str',
			),
			array(
				false,
				5,
			),
		);
	}

	/**
	 * @ticket 50300
	 *
	 * @dataProvider _dp_rest_sanitize_object
	 *
	 * @param array $expected Expected sanitized version.
	 * @param mixed $value    The value to sanitize.
	 */
	public function test_rest_sanitize_object( $expected, $value ) {
		$sanitized = rest_sanitize_object( $value );
		$this->assertEquals( $expected, $sanitized );
	}

	public function _dp_rest_sanitize_object() {
		return array(
			array(
				array(),
				'',
			),
			array(
				array( 'a' => '1' ),
				(object) array( 'a' => '1' ),
			),
			array(
				array( 'hi' => 'there' ),
				new JsonSerializable_Object( array( 'hi' => 'there' ) ),
			),
			array(
				array( 'hi' => 'there' ),
				array( 'hi' => 'there' ),
			),
			array(
				array(),
				array(),
			),
			array(
				array( 'a', 'b' ),
				array( 'a', 'b' ),
			),
			array(
				array(),
				new Basic_Object(),
			),
			array(
				array(),
				new JsonSerializable_Object( 'str' ),
			),
			array(
				array(),
				'str',
			),
			array(
				array(),
				5,
			),
		);
	}

	/**
	 * @ticket 50300
	 *
	 * @dataProvider _dp_rest_is_array
	 *
	 * @param bool  $expected Expected result of the check.
	 * @param mixed $value    The value to check.
	 */
	public function test_rest_is_array( $expected, $value ) {
		$is_array = rest_is_array( $value );

		if ( $expected ) {
			$this->assertTrue( $is_array );
		} else {
			$this->assertFalse( $is_array );
		}
	}

	public function _dp_rest_is_array() {
		return array(
			array(
				true,
				'',
			),
			array(
				true,
				array( 'a', 'b' ),
			),
			array(
				true,
				array(),
			),
			array(
				true,
				'a,b,c',
			),
			array(
				true,
				'a',
			),
			array(
				true,
				5,
			),
			array(
				false,
				new stdClass(),
			),
			array(
				false,
				new JsonSerializable_Object( array( 'hi' => 'there' ) ),
			),
			array(
				false,
				array( 'hi' => 'there' ),
			),
			array(
				false,
				new Basic_Object(),
			),
			array(
				false,
				new JsonSerializable_Object( 'str' ),
			),
			array(
				false,
				null,
			),
		);
	}

	/**
	 * @ticket 50300
	 *
	 * @dataProvider _dp_rest_sanitize_array
	 *
	 * @param array $expected Expected sanitized version.
	 * @param mixed $value    The value to sanitize.
	 */
	public function test_rest_sanitize_array( $expected, $value ) {
		$sanitized = rest_sanitize_array( $value );
		$this->assertEquals( $expected, $sanitized );
	}

	public function _dp_rest_sanitize_array() {
		return array(
			array(
				array(),
				'',
			),
			array(
				array( 'a', 'b' ),
				array( 'a', 'b' ),
			),
			array(
				array(),
				array(),
			),
			array(
				array( 'a', 'b', 'c' ),
				'a,b,c',
			),
			array(
				array( 'a' ),
				'a',
			),
			array(
				array( 'a', 'b' ),
				'a,b,',
			),
			array(
				array( '5' ),
				5,
			),
			array(
				array(),
				new stdClass(),
			),
			array(
				array(),
				new JsonSerializable_Object( array( 'hi' => 'there' ) ),
			),
			array(
				array( 'there' ),
				array( 'hi' => 'there' ),
			),
			array(
				array(),
				new Basic_Object(),
			),
			array(
				array(),
				new JsonSerializable_Object( 'str' ),
			),
			array(
				array(),
				null,
			),
		);
	}

	/**
	 * @ticket 50300
	 *
	 * @dataProvider _dp_get_best_type_for_value
	 *
	 * @param string $expected The expected best type.
	 * @param mixed  $value    The value to test.
	 * @param array  $types    The list of available types.
	 */
	public function test_get_best_type_for_value( $expected, $value, $types ) {
		$this->assertEquals( $expected, rest_get_best_type_for_value( $value, $types ) );
	}

	public function _dp_get_best_type_for_value() {
		return array(
			array(
				'array',
				array( 'hi' ),
				array( 'array' ),
			),
			array(
				'object',
				array( 'hi' => 'there' ),
				array( 'object' ),
			),
			array(
				'integer',
				5,
				array( 'integer' ),
			),
			array(
				'number',
				4.0,
				array( 'number' ),
			),
			array(
				'boolean',
				true,
				array( 'boolean' ),
			),
			array(
				'string',
				'str',
				array( 'string' ),
			),
			array(
				'null',
				null,
				array( 'null' ),
			),
			array(
				'string',
				'',
				array( 'array', 'string' ),
			),
			array(
				'string',
				'',
				array( 'object', 'string' ),
			),
			array(
				'string',
				'Hello',
				array( 'object', 'string' ),
			),
			array(
				'object',
				array( 'hello' => 'world' ),
				array( 'object', 'string' ),
			),
			array(
				'number',
				'5.0',
				array( 'number', 'string' ),
			),
			array(
				'string',
				'5.0',
				array( 'string', 'number' ),
			),
			array(
				'boolean',
				'false',
				array( 'boolean', 'string' ),
			),
			array(
				'string',
				'false',
				array( 'string', 'boolean' ),
			),
			array(
				'string',
				'a,b',
				array( 'string', 'array' ),
			),
			array(
				'array',
				'a,b',
				array( 'array', 'string' ),
			),
		);
	}
>>>>>>> 74cc64d74e (REST API: Issue a _doing_it_wrong when registering a route without a permission callback.)
}
