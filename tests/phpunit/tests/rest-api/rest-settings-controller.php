<?php
/**
 * Unit tests covering WP_Test_REST_Settings_Controller functionality.
 *
 * @package ClassicPress
 * @subpackage REST API
 */

/**
 * @group restapi
 */
class WP_Test_REST_Settings_Controller extends WP_Test_REST_Controller_Testcase {
	
	protected static $administrator;
	protected static $author;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$administrator = $factory->user->create( array(
			'role' => 'administrator',
		) );

		self::$author        = $factory->user->create(
			array(
				'role' => 'author',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$administrator );
		self::delete_user( self::$author );
	}

	public function setUp() {
		parent::setUp();
		$this->endpoint = new WP_REST_Settings_Controller();
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wp/v2/settings', $routes );
	}

	public function test_get_item() {
		/** Individual settings can't be gotten **/
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings/title' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_context_param() {
	}

	public function test_get_item_is_not_public_not_authenticated() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_get_item_is_not_public_no_permission() {
		wp_set_current_user( self::$author );
		$request  = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 403, $response->get_status() );
	}

	public function test_get_items() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$actual = array_keys( $data );

		$expected = array(
			'title',
			'description',
			'login_custom_image_state',
			'login_custom_image_id',
			'timezone',
			'date_format',
			'time_format',
			'start_of_week',
			'language',
			'use_smilies',
			'default_category',
			'default_post_format',
			'posts_per_page',
			'default_ping_status',
			'default_comment_status',
		);

		if ( ! is_multisite() ) {
			$expected[] = 'url';
			$expected[] = 'email';
		}

		sort( $expected );
		sort( $actual );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected, $actual );
	}

	public function test_get_item_value_is_cast_to_type() {
		wp_set_current_user( self::$administrator );
		update_option( 'posts_per_page', 'invalid_number' ); // this is cast to (int) 1
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $data['posts_per_page'] );
	}

	public function test_get_item_with_custom_setting() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'name'   => 'mycustomsettinginrest',
				'schema' => array(
					'enum'    => array( 'validvalue1', 'validvalue2' ),
					'default' => 'validvalue1',
				),
			),
			'type'         => 'string',
		) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'mycustomsettinginrest', $data );
		$this->assertEquals( 'validvalue1', $data['mycustomsettinginrest'] );

		update_option( 'mycustomsetting', 'validvalue2' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'validvalue2', $data['mycustomsettinginrest'] );

		unregister_setting( 'somegroup', 'mycustomsetting' );
	}

	public function test_get_item_with_custom_array_setting() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'    => 'array',
					'items'   => array(
						'type' => 'integer',
					),
				),
			),
			'type'         => 'array',
		) );

		// Array is cast to correct types.
		update_option( 'mycustomsetting', array( '1', '2' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 1, 2 ), $data['mycustomsetting'] );

		// Empty array works as expected.
		update_option( 'mycustomsetting', array() );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array(), $data['mycustomsetting'] );

		// Invalid value
		update_option( 'mycustomsetting', array( array( 1 ) ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( null, $data['mycustomsetting'] );

		// No option value
		delete_option( 'mycustomsetting' );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( null, $data['mycustomsetting'] );

		unregister_setting( 'somegroup', 'mycustomsetting' );
	}

	public function test_get_item_with_custom_object_setting() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'    => 'object',
					'properties' => array(
						'a' => array(
							'type' => 'integer',
						),
					),
				),
			),
			'type'         => 'object',
		) );

		// We have to re-register the route, as the args changes based off registered settings.
		$this->server->override_by_default = true;
		$this->endpoint->register_routes();

		// Object is cast to correct types.
		update_option( 'mycustomsetting', array( 'a' => '1' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 'a' => 1 ), $data['mycustomsetting'] );

		// Empty array works as expected.
		update_option( 'mycustomsetting', array() );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array(), $data['mycustomsetting'] );

		// Invalid value
		update_option( 'mycustomsetting', array( 'a' => 1, 'b' => 2 ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( null, $data['mycustomsetting'] );

		unregister_setting( 'somegroup', 'mycustomsetting' );
	}

	public function get_setting_custom_callback( $result, $name, $args ) {
		switch ( $name ) {
			case 'mycustomsetting1':
				return 'filtered1';
		}
		return $result;
	}

	public function test_get_item_with_filter() {
		wp_set_current_user( self::$administrator );

		add_filter( 'rest_pre_get_setting', array( $this, 'get_setting_custom_callback' ), 10, 3 );

		register_setting( 'somegroup', 'mycustomsetting1', array(
			'show_in_rest' => array(
				'name'   => 'mycustomsettinginrest1',
			),
			'type'         => 'string',
		) );

		register_setting( 'somegroup', 'mycustomsetting2', array(
			'show_in_rest' => array(
				'name'   => 'mycustomsettinginrest2',
			),
			'type'         => 'string',
		) );

		update_option( 'mycustomsetting1', 'unfiltered1' );
		update_option( 'mycustomsetting2', 'unfiltered2' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		$this->assertArrayHasKey( 'mycustomsettinginrest1', $data );
		$this->assertEquals( 'unfiltered1', $data['mycustomsettinginrest1'] );

		$this->assertArrayHasKey( 'mycustomsettinginrest2', $data );
		$this->assertEquals( 'unfiltered2', $data['mycustomsettinginrest2'] );

		unregister_setting( 'somegroup', 'mycustomsetting' );
		remove_all_filters( 'rest_pre_get_setting' );
	}

	public function test_get_item_with_invalid_value_array_in_options() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'name'   => 'mycustomsettinginrest',
				'schema' => array(
					'enum'    => array( 'validvalue1', 'validvalue2' ),
					'default' => 'validvalue1',
				),
			),
			'type'         => 'string',
		) );

		update_option( 'mycustomsetting', array( 'A sneaky array!' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( null, $data['mycustomsettinginrest'] );
	}

	public function test_get_item_with_invalid_object_array_in_options() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'name'   => 'mycustomsettinginrest',
				'schema' => array(
					'enum'    => array( 'validvalue1', 'validvalue2' ),
					'default' => 'validvalue1',
				),
			),
			'type'         => 'string',
		) );

		update_option( 'mycustomsetting', (object) array( 'A sneaky array!' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/settings' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( null, $data['mycustomsettinginrest'] );
		unregister_setting( 'somegroup', 'mycustomsetting' );
	}


	public function test_create_item() {
	}

	public function test_update_item() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'title', 'The new title!' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'The new title!', $data['title'] );
		$this->assertEquals( get_option( 'blogname' ), $data['title'] );
	}

	/**
	 * @dataProvider data_update_custom_login_image_options
	 */
	public function test_update_custom_login_image_state( $value ) {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'login_custom_image_state', $value );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $value, $data['login_custom_image_state'] );
		$this->assertSame( $value ? $value : '0', get_option( 'login_custom_image_state' ) );
		$this->assertSame( 0, $data['login_custom_image_id'] );
		$this->assertSame( '0', get_option( 'login_custom_image_id' ) );
	}

	/**
	 * @dataProvider data_update_custom_login_image_options
	 */
	public function test_update_custom_login_image_id( $value ) {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'login_custom_image_id', $value );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 0, $data['login_custom_image_state'] );
		$this->assertSame( '0', get_option( 'login_custom_image_state' ) );
		$this->assertSame( $value, $data['login_custom_image_id'] );
		$this->assertSame( $value ? $value : '0', get_option( 'login_custom_image_id' ) );
	}

	public function data_update_custom_login_image_options() {
		return [
			[ 0 ],
			[ 1 ],
			[ 2 ],
		];
	}

	public function update_setting_custom_callback( $result, $name, $value, $args ) {
		if ( 'title' === $name && 'The new title!' === $value ) {
			// Do not allow changing the title in this case
			return true;
		}

		return false;
	}

	public function test_update_item_with_array() {
		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'integer',
					),
				),
			),
			'type'         => 'array',
		) );

		// We have to re-register the route, as the args changes based off registered settings.
		$this->server->override_by_default = true;
		$this->endpoint->register_routes();
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( '1', '2' ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 1, 2 ), $data['mycustomsetting'] );
		$this->assertEquals( array( 1, 2 ), get_option( 'mycustomsetting' ) );

		// Setting an empty array.
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array() );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array(), $data['mycustomsetting'] );
		$this->assertEquals( array(), get_option( 'mycustomsetting' ) );

		// Setting an invalid array.
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( 'invalid' ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
		unregister_setting( 'somegroup', 'mycustomsetting' );
	}

	public function test_update_item_with_nested_object() {
		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'a' => array(
							'type' => 'object',
							'properties' => array(
								'b' => array(
									'type' => 'number',
								),
							),
						),
					),
				),
			),
			'type'         => 'object',
		) );

		// We have to re-register the route, as the args changes based off registered settings.
		$this->server->override_by_default = true;
		$this->endpoint->register_routes();
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( 'a' => array( 'b' => 1, 'c' => 1 ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_update_item_with_object() {
		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(
						'a' => array(
							'type' => 'integer',
						),
					),
				),
			),
			'type'         => 'object',
		) );

		// We have to re-register the route, as the args changes based off registered settings.
		$this->server->override_by_default = true;
		$this->endpoint->register_routes();
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( 'a' => 1 ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 'a' => 1 ), $data['mycustomsetting'] );
		$this->assertEquals( array( 'a' => 1 ), get_option( 'mycustomsetting' ) );

		// Setting an empty object.
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array() );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array(), $data['mycustomsetting'] );
		$this->assertEquals( array(), get_option( 'mycustomsetting' ) );

		// Provide more keys.
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( 'a' => 1, 'b' => 2 ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );

		// Setting an invalid object.
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', array( 'a' => 'invalid' ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
		unregister_setting( 'somegroup', 'mycustomsetting' );
	}

	public function test_update_item_with_filter() {
		wp_set_current_user( self::$administrator );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'title', 'The old title!' );
		$request->set_param( 'description', 'The old description!' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'The old title!', $data['title'] );
		$this->assertEquals( 'The old description!', $data['description'] );
		$this->assertEquals( get_option( 'blogname' ), $data['title'] );
		$this->assertEquals( get_option( 'blogdescription' ), $data['description'] );

		add_filter( 'rest_pre_update_setting', array( $this, 'update_setting_custom_callback' ), 10, 4 );

		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'title', 'The new title!' );
		$request->set_param( 'description', 'The new description!' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'The old title!', $data['title'] );
		$this->assertEquals( 'The new description!', $data['description'] );
		$this->assertEquals( get_option( 'blogname' ), $data['title'] );
		$this->assertEquals( get_option( 'blogdescription' ), $data['description'] );

		remove_all_filters( 'rest_pre_update_setting' );
	}

	public function test_update_item_with_invalid_type() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'title', array( 'rendered' => 'This should fail.' ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_update_item_with_integer() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'posts_per_page', 11 );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_update_item_with_invalid_float_for_integer() {
		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'posts_per_page', 10.5 );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * Setting an item to "null" will essentially restore it to it's default value.
	 */
	public function test_update_item_with_null() {
		update_option( 'posts_per_page', 9 );

		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'posts_per_page', null );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, $data['posts_per_page'] );
	}

	public function test_update_item_with_invalid_enum() {
		update_option( 'posts_per_page', 9 );

		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'default_ping_status', 'open&closed' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_update_item_with_invalid_stored_value_in_options() {
		wp_set_current_user( self::$administrator );

		register_setting( 'somegroup', 'mycustomsetting', array(
			'show_in_rest' => true,
			'type'         => 'string',
		) );
		update_option( 'mycustomsetting', array( 'A sneaky array!' ) );

		wp_set_current_user( self::$administrator );
		$request = new WP_REST_Request( 'PUT', '/wp/v2/settings' );
		$request->set_param( 'mycustomsetting', null );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_stored_value', $response, 500 );
	}

	public function test_delete_item() {
		/** Settings can't be deleted **/
		$request = new WP_REST_Request( 'DELETE', '/wp/v2/settings/title' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_prepare_item() {
	}

	public function test_get_item_schema() {
	}
}
