<?php
/**
 * Unit tests covering WP_REST_Posts meta functionality.
 *
 * @package ClassicPress
 * @subpackage REST API
 */

 /**
  * @group restapi
  */
class WP_Test_REST_Post_Meta_Fields extends WP_Test_REST_TestCase {
	protected static $wp_meta_keys_saved;
	protected static $post_id;
	protected static $cpt_post_id;

	public static function wpSetUpBeforeClass( $factory ) {
		register_post_type( 'cpt', array(
			'show_in_rest' => true,
			'supports'     => array( 'custom-fields' ),
		) );

		self::$wp_meta_keys_saved = $GLOBALS['wp_meta_keys'];
		self::$post_id = $factory->post->create();
		self::$cpt_post_id        = $factory->post->create( array( 'post_type' => 'cpt' ) );
	}

	public static function wpTearDownAfterClass() {
		$GLOBALS['wp_meta_keys'] = self::$wp_meta_keys_saved;
		wp_delete_post( self::$post_id, true );
		wp_delete_post( self::$cpt_post_id, true );

		unregister_post_type( 'cpt' );
	}

	public function setUp() {
		parent::setUp();

		register_meta( 'post', 'test_single', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		));
		register_meta( 'post', 'test_multi', array(
			'show_in_rest' => true,
			'single' => false,
			'type' => 'string',
		));
		register_meta( 'post', 'test_bad_auth', array(
			'show_in_rest' => true,
			'single' => true,
			'auth_callback' => '__return_false',
			'type' => 'string',
		));
		register_meta( 'post', 'test_bad_auth_multi', array(
			'show_in_rest' => true,
			'single' => false,
			'auth_callback' => '__return_false',
			'type' => 'string',
		));
		register_meta( 'post', 'test_no_rest', array() );
		register_meta( 'post', 'test_rest_disabled', array(
			'show_in_rest' => false,
			'type' => 'string',
		));
		register_meta( 'post', 'test_custom_schema', array(
			'single' => true,
			'type' => 'integer',
			'show_in_rest' => array(
				'schema' => array(
					'type' => 'number',
				),
			),
		));
		register_meta( 'post', 'test_custom_schema_multi', array(
			'single' => false,
			'type' => 'integer',
			'show_in_rest' => array(
				'schema' => array(
					'type' => 'number',
				),
			),
		));
		register_meta( 'post', 'test_invalid_type', array(
			'single' => true,
			'type' => 'lalala',
			'show_in_rest' => true,
		));
		register_meta( 'post', 'test_no_type', array(
			'single' => true,
			'type' => null,
			'show_in_rest' => true,
		));

		register_meta( 'post', 'test_custom_name', array(
			'single' => true,
			'type' => 'string',
			'show_in_rest' => array(
				'name'	=> 'new_name',
			),
		));

		register_meta( 'post', 'test_custom_name_multi', array(
			'single' => false,
			'type' => 'string',
			'show_in_rest' => array(
				'name'	=> 'new_name_multi',
			),
		));

		register_post_type( 'cpt', array(
			'show_in_rest' => true,
			'supports'     => array( 'custom-fields' ),
		) );

		register_post_meta( 'cpt', 'test_cpt_single', array(
			'show_in_rest'   => true,
			'single'         => true,
		) );

		register_post_meta( 'cpt', 'test_cpt_multi', array(
			'show_in_rest'   => true,
			'single'         => false,
		) );

		// Register 'test_single' on subtype to override for bad auth.
		register_post_meta( 'cpt', 'test_single', array(
			'show_in_rest'   => true,
			'single'         => true,
			'auth_callback'  => '__return_false',
		) );

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

	protected function grant_write_permission() {
		// Ensure we have write permission.
		$user = $this->factory->user->create( array(
			'role' => 'editor',
		));
		wp_set_current_user( $user );
	}

	public function test_get_value() {
		add_post_meta( self::$post_id, 'test_single', 'testvalue' );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'meta', $data );

		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'test_single', $meta );
		$this->assertEquals( 'testvalue', $meta['test_single'] );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_get_multi_value() {
		add_post_meta( self::$post_id, 'test_multi', 'value1' );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'test_multi', $meta );
		$this->assertInternalType( 'array', $meta['test_multi'] );
		$this->assertContains( 'value1', $meta['test_multi'] );

		// Check after an update.
		add_post_meta( self::$post_id, 'test_multi', 'value2' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertContains( 'value1', $meta['test_multi'] );
		$this->assertContains( 'value2', $meta['test_multi'] );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_get_unregistered() {
		add_post_meta( self::$post_id, 'test_unregistered', 'value1' );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayNotHasKey( 'test_unregistered', $meta );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_get_registered_no_api_access() {
		add_post_meta( self::$post_id, 'test_no_rest', 'for_the_wicked' );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayNotHasKey( 'test_no_rest', $meta );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_get_registered_api_disabled() {
		add_post_meta( self::$post_id, 'test_rest_disabled', 'sleepless_nights' );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayNotHasKey( 'test_rest_disabled', $meta );
	}

	public function test_get_value_types() {
		register_meta( 'post', 'test_string', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		));
		register_meta( 'post', 'test_number', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'number',
		));
		register_meta( 'post', 'test_bool', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'boolean',
		));

		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server;
		do_action( 'rest_api_init' );

		add_post_meta( self::$post_id, 'test_string', 42 );
		add_post_meta( self::$post_id, 'test_number', '42' );
		add_post_meta( self::$post_id, 'test_bool', 1 );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$meta = (array) $data['meta'];

		$this->assertArrayHasKey( 'test_string', $meta );
		$this->assertInternalType( 'string', $meta['test_string'] );
		$this->assertSame( '42', $meta['test_string'] );

		$this->assertArrayHasKey( 'test_number', $meta );
		$this->assertInternalType( 'float', $meta['test_number'] );
		$this->assertSame( 42.0, $meta['test_number'] );

		$this->assertArrayHasKey( 'test_bool', $meta );
		$this->assertInternalType( 'boolean', $meta['test_bool'] );
		$this->assertSame( true, $meta['test_bool'] );
	}

	public function test_get_value_custom_name() {
		add_post_meta( self::$post_id, 'test_custom_name', 'janet' );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'meta', $data );

		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'new_name', $meta );
		$this->assertEquals( 'janet', $meta['new_name'] );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_set_value() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_single', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_single' => 'test_value',
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_single', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 'test_value', $meta[0] );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'test_single', $meta );
		$this->assertEquals( 'test_value', $meta['test_single'] );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_set_duplicate_single_value() {
		// Start with an existing metakey and value.
		$values = update_post_meta( self::$post_id, 'test_single', 'test_value' );
		$this->assertEquals( 'test_value', get_post_meta( self::$post_id, 'test_single', true ) );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_single' => 'test_value',
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_single', true );
		$this->assertNotEmpty( $meta );
		$this->assertEquals( 'test_value', $meta );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'test_single', $meta );
		$this->assertEquals( 'test_value', $meta['test_single'] );
	}

	/**
	 * @depends test_set_value
	 */
	public function test_set_value_unauthenticated() {
		$data = array(
			'meta' => array(
				'test_single' => 'test_value',
			),
		);

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_edit', $response, 401 );

		// Check that the value wasn't actually updated.
		$this->assertEmpty( get_post_meta( self::$post_id, 'test_single', false ) );
	}

	/**
	 * @depends test_set_value
	 */
	public function test_set_value_blocked() {
		$data = array(
			'meta' => array(
				'test_bad_auth' => 'test_value',
			),
		);

		$this->grant_write_permission();

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_update', $response, 403 );
		$this->assertEmpty( get_post_meta( self::$post_id, 'test_bad_auth', false ) );
	}

	/**
	 * @depends test_set_value
	 */
	public function test_set_value_db_error() {
		$data = array(
			'meta' => array(
				'test_single' => 'test_value',
			),
		);

		$this->grant_write_permission();

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		/**
		 * Disable showing error as the below is going to intentionally
		 * trigger a DB error.
		 */
		global $wpdb;
		$wpdb->suppress_errors = true;
		add_filter( 'query', array( $this, 'error_insert_query' ) );

		$response = $this->server->dispatch( $request );
		remove_filter( 'query', array( $this, 'error_insert_query' ) );
		$wpdb->show_errors = true;

		$this->assertErrorResponse( 'rest_meta_database_error', $response, 500 );
	}

	public function test_set_value_invalid_type() {
		$values = get_post_meta( self::$post_id, 'test_invalid_type', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_invalid_type' => 'test_value',
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEmpty( get_post_meta( self::$post_id, 'test_invalid_type', false ) );
	}

	public function test_set_value_multiple() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_multi' => array( 'val1' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 'val1', $meta[0] );

		// Add another value.
		$data = array(
			'meta' => array(
				'test_multi' => array( 'val1', 'val2' ),
			),
		);
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 2, $meta );
		$this->assertContains( 'val1', $meta );
		$this->assertContains( 'val2', $meta );
	}

	/**
	 * Test removing only one item with duplicate items.
	 */
	public function test_set_value_remove_one() {
		add_post_meta( self::$post_id, 'test_multi', 'c' );
		add_post_meta( self::$post_id, 'test_multi', 'n' );
		add_post_meta( self::$post_id, 'test_multi', 'n' );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_multi' => array( 'c', 'n' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 2, $meta );
		$this->assertContains( 'c', $meta );
		$this->assertContains( 'n', $meta );
	}

	/**
	 * @depends test_set_value_multiple
	 */
	public function test_set_value_multiple_unauthenticated() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertEmpty( $values );

		wp_set_current_user( 0 );

		$data = array(
			'meta' => array(
				'test_multi' => array( 'val1' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_edit', $response, 401 );

		$meta = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertEmpty( $meta );
	}

	public function test_set_value_invalid_value() {
		register_meta( 'post', 'my_meta_key', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		));

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'my_meta_key' => array( 'c', 'n' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_set_value_invalid_value_multiple() {
		register_meta( 'post', 'my_meta_key', array(
			'show_in_rest' => true,
			'single' => false,
			'type' => 'string',
		));

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'my_meta_key' => array( array( 'a' ) ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_set_value_sanitized() {
		register_meta( 'post', 'my_meta_key', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'integer',
		));

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'my_meta_key' => '1', // Set to a string.
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 1, $data['meta']['my_meta_key'] );
	}

	public function test_set_value_csv() {
		register_meta( 'post', 'my_meta_key', array(
			'show_in_rest' => true,
			'single' => false,
			'type' => 'integer',
		));

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'my_meta_key' => '1,2,3', // Set to a string.
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( array( 1, 2, 3 ), $data['meta']['my_meta_key'] );
	}

	/**
	 * @depends test_set_value_multiple
	 */
	public function test_set_value_multiple_blocked() {
		$data = array(
			'meta' => array(
				'test_bad_auth_multi' => array( 'test_value' ),
			),
		);

		$this->grant_write_permission();

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_update', $response, 403 );
		$this->assertEmpty( get_post_meta( self::$post_id, 'test_bad_auth_multi', false ) );
	}

	public function test_add_multi_value_db_error() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_multi' => array( 'val1' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		/**
		 * Disable showing error as the below is going to intentionally
		 * trigger a DB error.
		 */
		global $wpdb;
		$wpdb->suppress_errors = true;
		add_filter( 'query', array( $this, 'error_insert_query' ) );

		$response = $this->server->dispatch( $request );
		remove_filter( 'query', array( $this, 'error_insert_query' ) );
		$wpdb->show_errors = true;

		$this->assertErrorResponse( 'rest_meta_database_error', $response, 500 );
	}

	/**
	 * @depends test_get_value
	 */
	public function test_set_value_single_custom_schema() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_custom_schema', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_custom_schema' => 3,
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_schema', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 3, $meta[0] );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'test_custom_schema', $meta );
		$this->assertEquals( 3, $meta['test_custom_schema'] );
	}

	public function test_set_value_multiple_custom_schema() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_custom_schema_multi', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_custom_schema_multi' => array( 2 ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_schema_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 2, $meta[0] );

		// Add another value.
		$data = array(
			'meta' => array(
				'test_custom_schema_multi' => array( 2, 8 ),
			),
		);
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_schema_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 2, $meta );
		$this->assertContains( 2, $meta );
		$this->assertContains( 8, $meta );
	}

	/**
	 * @depends test_get_value_custom_name
	 */
	public function test_set_value_custom_name() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_custom_name', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'new_name' => 'janet',
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_name', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 'janet', $meta[0] );

		$data = $response->get_data();
		$meta = (array) $data['meta'];
		$this->assertArrayHasKey( 'new_name', $meta );
		$this->assertEquals( 'janet', $meta['new_name'] );
	}

	public function test_set_value_custom_name_multiple() {
		// Ensure no data exists currently.
		$values = get_post_meta( self::$post_id, 'test_custom_name_multi', false );
		$this->assertEmpty( $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'new_name_multi' => array( 'janet' ),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_name_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 1, $meta );
		$this->assertEquals( 'janet', $meta[0] );

		// Add another value.
		$data = array(
			'meta' => array(
				'new_name_multi' => array( 'janet', 'graeme' ),
			),
		);
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_name_multi', false );
		$this->assertNotEmpty( $meta );
		$this->assertCount( 2, $meta );
		$this->assertContains( 'janet', $meta );
		$this->assertContains( 'graeme', $meta );
	}

	/**
	 * @ticket 38989
	 */
	public function test_set_value_invalid_meta_string_request_type() {
		update_post_meta( self::$post_id, 'test_single', 'So I tied an onion to my belt, which was the style at the time.' );
		$post_original = get_post( self::$post_id );

		$this->grant_write_permission();

		$data = array(
			'title' => 'Ignore this title',
			'meta'  => 'Not an array.',
		);

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );

		// The meta value should not have changed.
		$current_value = get_post_meta( self::$post_id, 'test_single', true );
		$this->assertEquals( 'So I tied an onion to my belt, which was the style at the time.', $current_value );

		// Ensure the post title update was not processed.
		$post_updated = get_post( self::$post_id );
		$this->assertEquals( $post_original->post_title, $post_updated->post_title );
	}

	/**
	 * @ticket 38989
	 */
	public function test_set_value_invalid_meta_float_request_type() {
		update_post_meta( self::$post_id, 'test_single', 'Now, to take the ferry cost a nickel, and in those days, nickels had pictures of bumblebees on them.' );
		$post_original = get_post( self::$post_id );

		$this->grant_write_permission();

		$data = array(
			'content' => 'Ignore this content.',
			'meta'    => 1.234,
		);

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );

		// The meta value should not have changed.
		$current_value = get_post_meta( self::$post_id, 'test_single', true );
		$this->assertEquals( 'Now, to take the ferry cost a nickel, and in those days, nickels had pictures of bumblebees on them.', $current_value );

		// Ensure the post content update was not processed.
		$post_updated = get_post( self::$post_id );
		$this->assertEquals( $post_original->post_content, $post_updated->post_content );
	}

	public function test_remove_multi_value_db_error() {
		add_post_meta( self::$post_id, 'test_multi', 'val1' );
		$values = get_post_meta( self::$post_id, 'test_multi', false );
		$this->assertEquals( array( 'val1' ), $values );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_multi' => array(),
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		/**
		 * Disable showing error as the below is going to intentionally
		 * trigger a DB error.
		 */
		global $wpdb;
		$wpdb->suppress_errors = true;
		add_filter( 'query', array( $this, 'error_delete_query' ) );

		$response = $this->server->dispatch( $request );
		remove_filter( 'query', array( $this, 'error_delete_query' ) );
		$wpdb->show_errors = true;

		$this->assertErrorResponse( 'rest_meta_database_error', $response, 500 );
	}


	public function test_delete_value() {
		add_post_meta( self::$post_id, 'test_single', 'val1' );
		$current = get_post_meta( self::$post_id, 'test_single', true );
		$this->assertEquals( 'val1', $current );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_single' => null,
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_single', false );
		$this->assertEmpty( $meta );
	}

	/**
	 * @depends test_delete_value
	 */
	public function test_delete_value_blocked() {
		add_post_meta( self::$post_id, 'test_bad_auth', 'val1' );
		$current = get_post_meta( self::$post_id, 'test_bad_auth', true );
		$this->assertEquals( 'val1', $current );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_bad_auth' => null,
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_delete', $response, 403 );

		$meta = get_post_meta( self::$post_id, 'test_bad_auth', true );
		$this->assertEquals( 'val1', $meta );
	}

	/**
	 * @depends test_delete_value
	 */
	public function test_delete_value_db_error() {
		add_post_meta( self::$post_id, 'test_single', 'val1' );
		$current = get_post_meta( self::$post_id, 'test_single', true );
		$this->assertEquals( 'val1', $current );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'test_single' => null,
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );
		/**
		 * Disable showing error as the below is going to intentionally
		 * trigger a DB error.
		 */
		global $wpdb;
		$wpdb->suppress_errors = true;
		add_filter( 'query', array( $this, 'error_delete_query' ) );

		$response = $this->server->dispatch( $request );
		remove_filter( 'query', array( $this, 'error_delete_query' ) );
		$wpdb->show_errors = true;

		$this->assertErrorResponse( 'rest_meta_database_error', $response, 500 );
	}

	public function test_delete_value_custom_name() {
		add_post_meta( self::$post_id, 'test_custom_name', 'janet' );
		$current = get_post_meta( self::$post_id, 'test_custom_name', true );
		$this->assertEquals( 'janet', $current );

		$this->grant_write_permission();

		$data = array(
			'meta' => array(
				'new_name' => null,
			),
		);
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( $data );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$meta = get_post_meta( self::$post_id, 'test_custom_name', false );
		$this->assertEmpty( $meta );
	}

	public function test_get_schema() {
		$request = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$schema = $data['schema'];

		$this->assertArrayHasKey( 'meta', $schema['properties'] );
		$meta_schema = $schema['properties']['meta']['properties'];

		$this->assertArrayHasKey( 'test_single', $meta_schema );
		$this->assertEquals( 'string', $meta_schema['test_single']['type'] );

		$this->assertArrayHasKey( 'test_multi', $meta_schema );
		$this->assertEquals( 'array', $meta_schema['test_multi']['type'] );
		$this->assertArrayHasKey( 'items', $meta_schema['test_multi'] );
		$this->assertEquals( 'string', $meta_schema['test_multi']['items']['type'] );

		$this->assertArrayHasKey( 'test_custom_schema', $meta_schema );
		$this->assertEquals( 'number', $meta_schema['test_custom_schema']['type'] );

		$this->assertArrayNotHasKey( 'test_no_rest', $meta_schema );
		$this->assertArrayNotHasKey( 'test_rest_disabled', $meta_schema );
		$this->assertArrayNotHasKey( 'test_invalid_type', $meta_schema );
		$this->assertArrayNotHasKey( 'test_no_type', $meta_schema );
	}

	/**
	 * @ticket 38323
	 * @dataProvider data_get_subtype_meta_value
	 */
	public function test_get_subtype_meta_value( $post_type, $meta_key, $single, $in_post_type ) {
		$post_id  = self::$post_id;
		$endpoint = 'posts';
		if ( 'cpt' === $post_type ) {
			$post_id  = self::$cpt_post_id;
			$endpoint = 'cpt';
		}

		$meta_value = 'testvalue';

		add_post_meta( $post_id, $meta_key, $meta_value );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/%s/%d', $endpoint, $post_id ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertArrayHasKey( 'meta', $data );
		$this->assertInternalType( 'array', $data['meta'] );

		if ( $in_post_type ) {
			$expected_value = $meta_value;
			if ( ! $single ) {
				$expected_value = array( $expected_value );
			}

			$this->assertArrayHasKey( $meta_key, $data['meta'] );
			$this->assertEquals( $expected_value, $data['meta'][ $meta_key ] );
		} else {
			$this->assertArrayNotHasKey( $meta_key, $data['meta'] );
		}
	}

	public function data_get_subtype_meta_value() {
		return array(
			array( 'cpt', 'test_cpt_single', true, true ),
			array( 'cpt', 'test_cpt_multi', false, true ),
			array( 'cpt', 'test_single', true, true ),
			array( 'cpt', 'test_multi', false, true ),
			array( 'post', 'test_cpt_single', true, false ),
			array( 'post', 'test_cpt_multi', false, false ),
			array( 'post', 'test_single', true, true ),
			array( 'post', 'test_multi', false, true ),
		);
	}

	/**
	 * @ticket 38323
	 * @dataProvider data_set_subtype_meta_value
	 */
	public function test_set_subtype_meta_value( $post_type, $meta_key, $single, $in_post_type, $can_write ) {
		$post_id  = self::$post_id;
		$endpoint = 'posts';
		if ( 'cpt' === $post_type ) {
			$post_id  = self::$cpt_post_id;
			$endpoint = 'cpt';
		}

		$meta_value = 'value_to_set';

		$this->grant_write_permission();

		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/%s/%d', $endpoint, $post_id ) );
		$request->set_body_params( array(
			'meta' => array(
				$meta_key => $meta_value,
			),
		) );

		$response = rest_get_server()->dispatch( $request );
		if ( ! $can_write ) {
			$this->assertEquals( 403, $response->get_status() );
			$this->assertEmpty( get_post_meta( $post_id, $meta_key, $single ) );
			return;
		}

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'meta', $data );
		$this->assertInternalType( 'array', $data['meta'] );

		if ( $in_post_type ) {
			$expected_value = $meta_value;
			if ( ! $single ) {
				$expected_value = array( $expected_value );
			}

			$this->assertEquals( $expected_value, get_post_meta( $post_id, $meta_key, $single ) );
			$this->assertArrayHasKey( $meta_key, $data['meta'] );
			$this->assertEquals( $expected_value, $data['meta'][ $meta_key ] );
		} else {
			$this->assertEmpty( get_post_meta( $post_id, $meta_key, $single ) );
			$this->assertArrayNotHasKey( $meta_key, $data['meta'] );
		}
	}

	public function data_set_subtype_meta_value() {
		$data = $this->data_get_subtype_meta_value();

		foreach ( $data as $index => $dataset ) {
			$can_write = true;

			// This combination is not writable because of an auth callback of '__return_false'.
			if ( 'cpt' === $dataset[0] && 'test_single' === $dataset[1] ) {
				$can_write = false;
			}

			$data[ $index ][] = $can_write;
		}

		return $data;
	}

	/**
	 * Internal function used to disable an insert query which
	 * will trigger a wpdb error for testing purposes.
	 */
	public function error_insert_query( $query ) {
		if ( strpos( $query, 'INSERT' ) === 0 ) {
			$query = '],';
		}
		return $query;
	}

	/**
	 * Internal function used to disable an insert query which
	 * will trigger a wpdb error for testing purposes.
	 */
	public function error_delete_query( $query ) {
		if ( strpos( $query, 'DELETE' ) === 0 ) {
			$query = '],';
		}
		return $query;
	}
}
