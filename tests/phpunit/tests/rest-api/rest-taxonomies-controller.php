<?php
/**
 * Unit tests covering WP_REST_Taxonomies_Controller functionality.
 *
 * @package ClassicPress
 * @subpackage REST API
 */

/**
 * @group restapi
 */
class WP_Test_REST_Taxonomies_Controller extends WP_Test_REST_Controller_Testcase {

	protected static $contributor_id;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$contributor_id = $factory->user->create(
			array(
				'role' => 'contributor',
			)
		);
	}

	public static function wpTearDownAfterClass() {
		self::delete_user( self::$contributor_id );
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wp/v2/taxonomies', $routes );
		$this->assertArrayHasKey( '/wp/v2/taxonomies/(?P<taxonomy>[\w-]+)', $routes );
	}

	public function test_context_param() {
		// Collection
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSameSets( array( 'view', 'edit', 'embed' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single.
		$request  = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies/post_tag' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertSame( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertSameSets( array( 'view', 'edit', 'embed' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_get_items() {
		$request    = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_taxonomies( '', 'objects' ) );
		$this->assertSame( count( $taxonomies ), count( $data ) );
		$this->assertSame( 'Categories', $data['category']['name'] );
		$this->assertSame( 'category', $data['category']['slug'] );
		$this->assertTrue( $data['category']['hierarchical'] );
		$this->assertSame( 'Tags', $data['post_tag']['name'] );
		$this->assertSame( 'post_tag', $data['post_tag']['slug'] );
		$this->assertFalse( $data['post_tag']['hierarchical'] );
		$this->assertSame( 'tags', $data['post_tag']['rest_base'] );
	}

	public function test_get_items_context_edit() {
		wp_set_current_user( self::$contributor_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'context', 'edit' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_taxonomies( '', 'objects' ) );
		$this->assertSame( count( $taxonomies ), count( $data ) );
		$this->assertSame( 'Categories', $data['category']['name'] );
		$this->assertSame( 'category', $data['category']['slug'] );
		$this->assertTrue( $data['category']['hierarchical'] );
		$this->assertSame( 'Tags', $data['post_tag']['name'] );
		$this->assertSame( 'post_tag', $data['post_tag']['slug'] );
		$this->assertFalse( $data['post_tag']['hierarchical'] );
		$this->assertSame( 'tags', $data['post_tag']['rest_base'] );
	}

	public function test_get_items_invalid_permission_for_context() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_cannot_view', $response, 401 );
	}

	public function test_get_taxonomies_for_type() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'type', 'post' );
		$response = $this->server->dispatch( $request );
		$this->check_taxonomies_for_type_response( 'post', $response );
	}

	public function test_get_taxonomies_for_invalid_type() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies' );
		$request->set_param( 'type', 'wingding' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( '{}', json_encode( $data ) );
	}

	public function test_get_item() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$response = $this->server->dispatch( $request );
		$this->check_taxonomy_object_response( 'view', $response );
	}

	public function test_get_item_edit_context() {
		$editor_id = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->check_taxonomy_object_response( 'edit', $response );
	}

	public function test_get_item_invalid_permission_for_context() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/category' );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden_context', $response, 401 );
	}

	public function test_get_invalid_taxonomy() {
		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/invalid' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_taxonomy_invalid', $response, 404 );
	}

	public function test_get_non_public_taxonomy_not_authenticated() {
		register_taxonomy( 'api-private', 'post', array( 'public' => false ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/api-private' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	public function test_get_non_public_taxonomy_no_permission() {
		wp_set_current_user( self::$contributor_id );
		register_taxonomy( 'api-private', 'post', array( 'public' => false ) );

		$request  = new WP_REST_Request( 'GET', '/wp/v2/taxonomies/api-private' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_forbidden', $response, 403 );
	}

	public function test_create_item() {
		/** Taxonomies can't be created **/
		$request  = new WP_REST_Request( 'POST', '/wp/v2/taxonomies' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_update_item() {
		/** Taxonomies can't be updated **/
		$request  = new WP_REST_Request( 'POST', '/wp/v2/taxonomies/category' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_delete_item() {
		/** Taxonomies can't be deleted **/
		$request  = new WP_REST_Request( 'DELETE', '/wp/v2/taxonomies/category' );
		$response = $this->server->dispatch( $request );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_prepare_item() {
		$tax      = get_taxonomy( 'category' );
		$endpoint = new WP_REST_Taxonomies_Controller;
		$request  = new WP_REST_Request;
		$request->set_param( 'context', 'edit' );
		$response = $endpoint->prepare_item_for_response( $tax, $request );
		$this->check_taxonomy_object( 'edit', $tax, $response->get_data(), $response->get_links() );
	}

	public function test_prepare_item_limit_fields() {
		$tax      = get_taxonomy( 'category' );
		$request  = new WP_REST_Request;
		$endpoint = new WP_REST_Taxonomies_Controller;
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'id,name' );
		$response = $endpoint->prepare_item_for_response( $tax, $request );
		$this->assertSame(
			array(
				// 'id' doesn't exist in this context.
				'name',
			),
			array_keys( $response->get_data() )
		);
	}

	public function test_get_item_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', '/wp/v2/taxonomies' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertSame( 9, count( $properties ) );
		$this->assertArrayHasKey( 'capabilities', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'hierarchical', $properties );
		$this->assertArrayHasKey( 'labels', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'show_cloud', $properties );
		$this->assertArrayHasKey( 'types', $properties );
		$this->assertArrayHasKey( 'rest_base', $properties );
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Utility function for use in get_public_taxonomies
	 */
	private function is_public( $taxonomy ) {
		return ! empty( $taxonomy->show_in_rest );
	}
	/**
	 * Utility function to filter down to only public taxonomies
	 */
	private function get_public_taxonomies( $taxonomies ) {
		// Pass through array_values to re-index after filtering
		return array_values( array_filter( $taxonomies, array( $this, 'is_public' ) ) );
	}

	protected function check_taxonomy_object( $context, $tax_obj, $data, $links ) {
		$this->assertSame( $tax_obj->label, $data['name'] );
		$this->assertSame( $tax_obj->name, $data['slug'] );
		$this->assertSame( $tax_obj->description, $data['description'] );
		$this->assertSame( $tax_obj->hierarchical, $data['hierarchical'] );
		$this->assertSame( $tax_obj->rest_base, $data['rest_base'] );
		$this->assertSame( rest_url( 'wp/v2/taxonomies' ), $links['collection'][0]['href'] );
		$this->assertArrayHasKey( 'https://api.w.org/items', $links );
		if ( 'edit' === $context ) {
			$this->assertSame( $tax_obj->cap, $data['capabilities'] );
			$this->assertSame( $tax_obj->labels, $data['labels'] );
			$this->assertSame( $tax_obj->show_tagcloud, $data['show_cloud'] );
		} else {
			$this->assertFalse( isset( $data['capabilities'] ) );
			$this->assertFalse( isset( $data['labels'] ) );
			$this->assertFalse( isset( $data['show_cloud'] ) );
		}
	}

	protected function check_taxonomy_object_response( $context, $response ) {
		$this->assertSame( 200, $response->get_status() );
		$data     = $response->get_data();
		$category = get_taxonomy( 'category' );
		$this->check_taxonomy_object( $context, $category, $data, $response->get_links() );
	}

	protected function check_taxonomies_for_type_response( $type, $response ) {
		$this->assertSame( 200, $response->get_status() );
		$data       = $response->get_data();
		$taxonomies = $this->get_public_taxonomies( get_object_taxonomies( $type, 'objects' ) );
		$this->assertSame( count( $taxonomies ), count( $data ) );
	}

}
