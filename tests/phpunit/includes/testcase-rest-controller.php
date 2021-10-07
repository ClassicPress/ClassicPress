<?php

abstract class WP_Test_REST_Controller_Testcase extends WP_Test_REST_TestCase {

	protected $server;

	public function set_up() {
		parent::set_up();
		add_filter( 'rest_url', array( $this, 'filter_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

<<<<<<< HEAD
	public function tearDown() {
		parent::tearDown();
=======
	public function tear_down() {
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
		remove_filter( 'rest_url', array( $this, 'test_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
<<<<<<< HEAD
=======
		parent::tear_down();
>>>>>>> ddb409edca (Build/Test Tools: Implement use of the `void` solution.)
	}

	abstract public function test_register_routes();

	abstract public function test_context_param();

	abstract public function test_get_items();

	abstract public function test_get_item();

	abstract public function test_create_item();

	abstract public function test_update_item();

	abstract public function test_delete_item();

	abstract public function test_prepare_item();

	abstract public function test_get_item_schema();

	public function filter_rest_url_for_leading_slash( $url, $path ) {
		if ( is_multisite() ) {
			return $url;
		}

		// Make sure path for rest_url has a leading slash for proper resolution.
		$this->assertTrue( 0 === strpos( $path, '/' ), 'REST API URL should have a leading slash.' );

		return $url;
	}
}
