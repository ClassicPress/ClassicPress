<?php
/**
 * Unit tests covering WP_REST_Posts_Controller functionality.
 *
 * @package ClassicPress
 * @subpackage REST API
 */

/**
 * @group restapi
 */
class WP_Test_REST_Posts_Controller extends WP_Test_REST_Post_Type_Controller_Testcase {
	protected static $post_id;

	protected static $superadmin_id;
	protected static $editor_id;
	protected static $author_id;
	protected static $contributor_id;

	protected static $supported_formats;

	protected $forbidden_cat;
	protected $posts_clauses;

	public static function wpSetUpBeforeClass( $factory ) {
		self::$post_id = $factory->post->create();

		self::$superadmin_id = $factory->user->create( array(
			'role'       => 'administrator',
			'user_login' => 'superadmin',
		) );
		self::$editor_id = $factory->user->create( array(
			'role' => 'editor',
		) );
		self::$author_id = $factory->user->create( array(
			'role' => 'author',
		) );
		self::$contributor_id = $factory->user->create( array(
			'role' => 'contributor',
		) );

		if ( is_multisite() ) {
			update_site_option( 'site_admins', array( 'superadmin' ) );
		}

		// Only support 'post' and 'gallery'
		self::$supported_formats = get_theme_support( 'post-formats' );
		add_theme_support( 'post-formats', array( 'post', 'gallery' ) );
	}

	public static function wpTearDownAfterClass() {
		// Restore theme support for formats.
		if ( self::$supported_formats ) {
			add_theme_support( 'post-formats', self::$supported_formats );
		} else {
			remove_theme_support( 'post-formats' );
		}

		wp_delete_post( self::$post_id, true );

		self::delete_user( self::$superadmin_id );
		self::delete_user( self::$editor_id );
		self::delete_user( self::$author_id );
		self::delete_user( self::$contributor_id );
	}

	public function setUp() {
		parent::setUp();
		register_post_type( 'youseeme', array( 'supports' => array(), 'show_in_rest' => true ) );
		add_filter( 'rest_pre_dispatch', array( $this, 'wpSetUpBeforeRequest' ), 10, 3 );
		add_filter( 'posts_clauses', array( $this, 'save_posts_clauses' ), 10, 2 );
	}

	public function wpSetUpBeforeRequest( $result, $server, $request ) {
		$this->posts_clauses = array();
		return $result;
	}

	public function save_posts_clauses( $orderby, $query ) {
		array_push( $this->posts_clauses, $orderby );
		return $orderby;
	}

	public function assertPostsClause( $clause, $pattern ) {
		global $wpdb;
		$expected_clause = str_replace( '{posts}', $wpdb->posts, $pattern );
		$this->assertCount( 1, $this->posts_clauses );
		$this->assertEquals( $expected_clause, $wpdb->remove_placeholder_escape( $this->posts_clauses[0][ $clause ] ) );
	}

	public function assertPostsOrderedBy( $pattern ) {
		$this->assertPostsClause( 'orderby', $pattern );
	}

	public function assertPostsWhere( $pattern ) {
		$this->assertPostsClause( 'where', $pattern );
	}

	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/wp/v2/posts', $routes );
		$this->assertCount( 2, $routes['/wp/v2/posts'] );
		$this->assertArrayHasKey( '/wp/v2/posts/(?P<id>[\d]+)', $routes );
		$this->assertCount( 3, $routes['/wp/v2/posts/(?P<id>[\d]+)'] );
	}

	public function test_context_param() {
		// Collection
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
		// Single
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts/' . self::$post_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'view', $data['endpoints'][0]['args']['context']['default'] );
		$this->assertEquals( array( 'view', 'embed', 'edit' ), $data['endpoints'][0]['args']['context']['enum'] );
	}

	public function test_registered_query_params() {
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$keys = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals( array(
			'after',
			'author',
			'author_exclude',
			'before',
			'categories',
			'categories_exclude',
			'context',
			'exclude',
			'include',
			'offset',
			'order',
			'orderby',
			'page',
			'per_page',
			'search',
			'slug',
			'status',
			'sticky',
			'tags',
			'tags_exclude',
			), $keys );
	}

	public function test_registered_get_item_params() {
		$request = new WP_REST_Request( 'OPTIONS', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$keys = array_keys( $data['endpoints'][0]['args'] );
		sort( $keys );
		$this->assertEquals( array( 'context', 'id', 'password' ), $keys );
	}

	public function test_get_items() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );

		$this->check_get_posts_response( $response );
	}

	/**
	 * A valid query that returns 0 results should return an empty JSON list.
	 *
	 * @issue 862
	 */
	public function test_get_items_empty_query() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_query_params( array(
			'author' => REST_TESTS_IMPOSSIBLY_HIGH_NUMBER,
		) );
		$response = $this->server->dispatch( $request );

		$this->assertEmpty( $response->get_data() );
		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_get_items_author_query() {
		$this->factory->post->create( array( 'post_author' => self::$editor_id ) );
		$this->factory->post->create( array( 'post_author' => self::$author_id ) );
		// All 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response->get_data() ) );
		// 2 of 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'author', array( self::$editor_id, self::$author_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$this->assertEqualSets( array( self::$editor_id, self::$author_id ), wp_list_pluck( $data, 'author' ) );
		// 1 of 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'author', self::$editor_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( self::$editor_id, $data[0]['author'] );
	}

	public function test_get_items_author_exclude_query() {
		$this->factory->post->create( array( 'post_author' => self::$editor_id ) );
		$this->factory->post->create( array( 'post_author' => self::$author_id ) );
		// All 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $response->get_data() ) );
		// 1 of 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'author_exclude', array( self::$editor_id, self::$author_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
		$this->assertNotEquals( self::$editor_id, $data[0]['author'] );
		$this->assertNotEquals( self::$author_id, $data[0]['author'] );
		// 2 of 3 posts
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'author_exclude', self::$editor_id );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$this->assertNotEquals( self::$editor_id, $data[0]['author'] );
		$this->assertNotEquals( self::$editor_id, $data[1]['author'] );
		// invalid author_exclude errors
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'author_exclude', 'invalid' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_include_query() {
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		// Orderby=>desc
		$request->set_param( 'include', array( $id1, $id3 ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$this->assertEquals( $id3, $data[0]['id'] );
		$this->assertPostsOrderedBy( '{posts}.post_date DESC' );
		// Orderby=>include
		$request->set_param( 'orderby', 'include' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$this->assertEquals( $id1, $data[0]['id'] );
		$this->assertPostsOrderedBy( "FIELD( {posts}.ID, $id1,$id3 )" );
		// Invalid include should error
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'include', 'invalid' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_orderby_author_query() {
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_author' => self::$editor_id ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_author' => self::$editor_id ) );
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_author' => self::$author_id ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'include', array( $id1, $id2, $id3 ) );
		$request->set_param( 'orderby', 'author' );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( self::$author_id, $data[0]['author'] );
		$this->assertEquals( self::$editor_id, $data[1]['author'] );
		$this->assertEquals( self::$editor_id, $data[2]['author'] );

		$this->assertPostsOrderedBy( '{posts}.post_author DESC' );
	}

	public function test_get_items_orderby_modified_query() {
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		$this->update_post_modified( $id1, '2016-04-20 4:26:20' );
		$this->update_post_modified( $id2, '2016-02-01 20:24:02' );
		$this->update_post_modified( $id3, '2016-02-21 12:24:02' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'include', array( $id1, $id2, $id3 ) );
		$request->set_param( 'orderby', 'modified' );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $id1, $data[0]['id'] );
		$this->assertEquals( $id3, $data[1]['id'] );
		$this->assertEquals( $id2, $data[2]['id'] );

		$this->assertPostsOrderedBy( '{posts}.post_modified DESC' );
	}

	public function test_get_items_orderby_parent_query() {
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_type' => 'page' ) );
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_type' => 'page' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_type' => 'page', 'post_parent' => $id1 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/pages' );
		$request->set_param( 'include', array( $id1, $id2, $id3 ) );
		$request->set_param( 'orderby', 'parent' );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $id3, $data[0]['id'] );
		// Check ordering. Default ORDER is DESC.
		$this->assertEquals( $id1, $data[0]['parent'] );
		$this->assertEquals( 0, $data[1]['parent'] );
		$this->assertEquals( 0, $data[2]['parent'] );

		$this->assertPostsOrderedBy( '{posts}.post_parent DESC' );
	}

	public function test_get_items_exclude_query() {
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertTrue( in_array( $id1, wp_list_pluck( $data, 'id' ), true ) );
		$this->assertTrue( in_array( $id2, wp_list_pluck( $data, 'id' ), true ) );

		$request->set_param( 'exclude', array( $id2 ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertTrue( in_array( $id1, wp_list_pluck( $data, 'id' ), true ) );
		$this->assertFalse( in_array( $id2, wp_list_pluck( $data, 'id' ), true ) );

		$request->set_param( 'exclude', "$id2" );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertTrue( in_array( $id1, wp_list_pluck( $data, 'id' ), true ) );
		$this->assertFalse( in_array( $id2, wp_list_pluck( $data, 'id' ), true ) );

		$request->set_param( 'exclude', 'invalid' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_search_query() {
		for ( $i = 0;  $i < 5;  $i++ ) {
			$this->factory->post->create( array( 'post_status' => 'publish' ) );
		}
		$this->factory->post->create( array( 'post_title' => 'Search Result', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 7, count( $response->get_data() ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'search', 'Search Result' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'Search Result', $data[0]['title']['rendered'] );
	}

	public function test_get_items_slug_query() {
		$this->factory->post->create( array( 'post_title' => 'Apple', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Banana', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'slug', 'apple' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
		$this->assertEquals( 'Apple', $data[0]['title']['rendered'] );
	}

	public function test_get_items_multiple_slugs_array_query() {
		$this->factory->post->create( array( 'post_title' => 'Apple', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Banana', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Peach', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'slug', array( 'banana', 'peach' ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$titles = array(
			$data[0]['title']['rendered'],
			$data[1]['title']['rendered'],
		);
		sort( $titles );
		$this->assertEquals( array( 'Banana', 'Peach' ), $titles );
	}

	public function test_get_items_multiple_slugs_string_query() {
		$this->factory->post->create( array( 'post_title' => 'Apple', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Banana', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Peach', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'slug', 'apple,banana' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$titles = array(
			$data[0]['title']['rendered'],
			$data[1]['title']['rendered'],
		);
		sort( $titles );
		$this->assertEquals( array( 'Apple', 'Banana' ), $titles );
	}

	public function test_get_items_status_query() {
		wp_set_current_user( 0 );
		$this->factory->post->create( array( 'post_status' => 'draft' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'publish' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response->get_data() ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'draft' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
		wp_set_current_user( self::$editor_id );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'draft' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response->get_data() ) );
	}

	public function test_get_items_multiple_statuses_string_query() {
		wp_set_current_user( self::$editor_id );

		$this->factory->post->create( array( 'post_status' => 'draft' ) );
		$this->factory->post->create( array( 'post_status' => 'private' ) );
		$this->factory->post->create( array( 'post_status' => 'publish' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'status', 'draft,private' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$statuses = array(
			$data[0]['status'],
			$data[1]['status'],
		);
		sort( $statuses );
		$this->assertEquals( array( 'draft', 'private' ), $statuses );
	}

	public function test_get_items_multiple_statuses_array_query() {
		wp_set_current_user( self::$editor_id );

		$this->factory->post->create( array( 'post_status' => 'draft' ) );
		$this->factory->post->create( array( 'post_status' => 'pending' ) );
		$this->factory->post->create( array( 'post_status' => 'publish' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'status', array( 'draft', 'pending' ) );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 2, count( $data ) );
		$statuses = array(
			$data[0]['status'],
			$data[1]['status'],
		);
		sort( $statuses );
		$this->assertEquals( array( 'draft', 'pending' ), $statuses );
	}

	public function test_get_items_multiple_statuses_one_invalid_query() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'context', 'edit' );
		$request->set_param( 'status', array( 'draft', 'nonsense' ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_invalid_status_query() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'invalid' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_status_without_permissions() {
		$draft_id = $this->factory->post->create( array(
			'post_status' => 'draft',
		) );
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$all_data = $response->get_data();
		foreach ( $all_data as $post ) {
			$this->assertNotEquals( $draft_id, $post['id'] );
		}
	}

	public function test_get_items_order_and_orderby() {
		$this->factory->post->create( array( 'post_title' => 'Apple Pie', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Apple Sauce', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Apple Cobbler', 'post_status' => 'publish' ) );
		$this->factory->post->create( array( 'post_title' => 'Apple Coffee Cake', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'search', 'Apple' );
		// order defaults to 'desc'
		$request->set_param( 'orderby', 'title' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'Apple Sauce', $data[0]['title']['rendered'] );
		$this->assertPostsOrderedBy( '{posts}.post_title DESC' );
		// order=>asc
		$request->set_param( 'order', 'asc' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'Apple Cobbler', $data[0]['title']['rendered'] );
		$this->assertPostsOrderedBy( '{posts}.post_title ASC' );
		// order=>asc,id should fail
		$request->set_param( 'order', 'asc,id' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
		// orderby=>content should fail (invalid param test)
		$request->set_param( 'order', 'asc' );
		$request->set_param( 'orderby', 'content' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_with_orderby_include_without_include_param() {
		$this->factory->post->create( array( 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'include' );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_orderby_include_missing_include', $response, 400 );
	}

	public function test_get_items_with_orderby_id() {
		$id1 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_date' => '2016-01-13 02:26:48' ) );
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_date' => '2016-01-12 02:26:48' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish', 'post_date' => '2016-01-11 02:26:48' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'id' );
		$request->set_param( 'include', array( $id1, $id2, $id3 ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		// Default ORDER is DESC.
		$this->assertEquals( $id3, $data[0]['id'] );
		$this->assertEquals( $id2, $data[1]['id'] );
		$this->assertEquals( $id1, $data[2]['id'] );
		$this->assertPostsOrderedBy( '{posts}.ID DESC' );
	}

	public function test_get_items_with_orderby_slug() {
		$id1 = $this->factory->post->create( array( 'post_title' => 'ABC', 'post_name' => 'xyz', 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_title' => 'XYZ', 'post_name' => 'abc', 'post_status' => 'publish' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'slug' );
		$request->set_param( 'include', array( $id1, $id2 ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		// Default ORDER is DESC.
		$this->assertEquals( 'xyz', $data[0]['slug'] );
		$this->assertEquals( 'abc', $data[1]['slug'] );
		$this->assertPostsOrderedBy( '{posts}.post_name DESC' );
	}

	public function test_get_items_with_orderby_slugs() {
		$slugs = array( 'burrito', 'taco', 'chalupa' );
		foreach ( $slugs as $slug ) {
			$this->factory->post->create( array( 'post_title' => $slug, 'post_name' => $slug, 'post_status' => 'publish' ) );
		}

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'include_slugs' );
		$request->set_param( 'slug', array( 'taco', 'chalupa', 'burrito' ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'taco', $data[0]['slug'] );
		$this->assertEquals( 'chalupa', $data[1]['slug'] );
		$this->assertEquals( 'burrito', $data[2]['slug'] );
	}

	public function test_get_items_with_orderby_relevance() {
		$id1 = $this->factory->post->create( array( 'post_title' => 'Title is more relevant', 'post_content' => 'Content is', 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_title' => 'Title is', 'post_content' => 'Content is less relevant', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'relevance' );
		$request->set_param( 'search', 'relevant' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data );
		$this->assertEquals( $id1, $data[0]['id'] );
		$this->assertEquals( $id2, $data[1]['id'] );
		$this->assertPostsOrderedBy( '{posts}.post_title LIKE \'%relevant%\' DESC, {posts}.post_date DESC' );
	}

	public function test_get_items_with_orderby_relevance_two_terms() {
		$id1 = $this->factory->post->create( array( 'post_title' => 'Title is more relevant', 'post_content' => 'Content is', 'post_status' => 'publish' ) );
		$id2 = $this->factory->post->create( array( 'post_title' => 'Title is', 'post_content' => 'Content is less relevant', 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'relevance' );
		$request->set_param( 'search', 'relevant content' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertCount( 2, $data );
		$this->assertEquals( $id1, $data[0]['id'] );
		$this->assertEquals( $id2, $data[1]['id'] );
		$this->assertPostsOrderedBy( '(CASE WHEN {posts}.post_title LIKE \'%relevant content%\' THEN 1 WHEN {posts}.post_title LIKE \'%relevant%\' AND {posts}.post_title LIKE \'%content%\' THEN 2 WHEN {posts}.post_title LIKE \'%relevant%\' OR {posts}.post_title LIKE \'%content%\' THEN 3 WHEN {posts}.post_excerpt LIKE \'%relevant content%\' THEN 4 WHEN {posts}.post_content LIKE \'%relevant content%\' THEN 5 ELSE 6 END), {posts}.post_date DESC' );
	}

	public function test_get_items_with_orderby_relevance_missing_search() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'orderby', 'relevance' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_no_search_term_defined', $response, 400 );
	}

	public function test_get_items_offset_query() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id4 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'offset', 1 );
		$response = $this->server->dispatch( $request );
		$this->assertCount( 3, $response->get_data() );
		// 'offset' works with 'per_page'
		$request->set_param( 'per_page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertCount( 2, $response->get_data() );
		// 'offset' takes priority over 'page'
		$request->set_param( 'page', 2 );
		$response = $this->server->dispatch( $request );
		$this->assertCount( 2, $response->get_data() );
		// Invalid 'offset' should error
		$request->set_param( 'offset', 'moreplease' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_tags_query() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id4 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$tag = wp_insert_term( 'My Tag', 'post_tag' );

		wp_set_object_terms( $id1, array( $tag['term_id'] ), 'post_tag' );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'tags', array( $tag['term_id'] ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( $id1, $data[0]['id'] );
	}

	public function test_get_items_tags_exclude_query() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id4 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$tag = wp_insert_term( 'My Tag', 'post_tag' );

		wp_set_object_terms( $id1, array( $tag['term_id'] ), 'post_tag' );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'tags_exclude', array( $tag['term_id'] ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 3, $data );
		$this->assertEquals( $id4, $data[0]['id'] );
		$this->assertEquals( $id3, $data[1]['id'] );
		$this->assertEquals( $id2, $data[2]['id'] );
	}

	public function test_get_items_tags_and_categories_query() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id4 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$tag = wp_insert_term( 'My Tag', 'post_tag' );
		$category = wp_insert_term( 'My Category', 'category' );

		wp_set_object_terms( $id1, array( $tag['term_id'] ), 'post_tag' );
		wp_set_object_terms( $id2, array( $tag['term_id'] ), 'post_tag' );
		wp_set_object_terms( $id1, array( $category['term_id'] ), 'category' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'tags', array( $tag['term_id'] ) );
		$request->set_param( 'categories', array( $category['term_id'] ) );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 1, $response->get_data() );

		$request->set_param( 'tags', array( 'my-tag' ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_tags_and_categories_exclude_query() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id4 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$tag = wp_insert_term( 'My Tag', 'post_tag' );
		$category = wp_insert_term( 'My Category', 'category' );

		wp_set_object_terms( $id1, array( $tag['term_id'] ), 'post_tag' );
		wp_set_object_terms( $id2, array( $tag['term_id'] ), 'post_tag' );
		wp_set_object_terms( $id1, array( $category['term_id'] ), 'category' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'tags', array( $tag['term_id'] ) );
		$request->set_param( 'categories_exclude', array( $category['term_id'] ) );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( $id2, $data[0]['id'] );

		$request->set_param( 'tags_exclude', array( 'my-tag' ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_sticky() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		update_option( 'sticky_posts', array( $id2 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', true );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 1, $response->get_data() );

		$posts = $response->get_data();
		$post = $posts[0];
		$this->assertEquals( $id2, $post['id'] );

		$request->set_param( 'sticky', 'nothanks' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_sticky_with_include() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		update_option( 'sticky_posts', array( $id2 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', true );
		$request->set_param( 'include', array( $id1 ) );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 0, $response->get_data() );

		// FIXME Since this request returns zero posts, the query is executed twice.
		$this->assertCount( 2, $this->posts_clauses );
		$this->posts_clauses = array_slice( $this->posts_clauses, 0, 1 );

		$this->assertPostsWhere( " AND {posts}.ID IN (0) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );

		update_option( 'sticky_posts', array( $id1, $id2 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', true );
		$request->set_param( 'include', array( $id1 ) );

		$response = $this->server->dispatch( $request );

		$this->assertCount( 1, $response->get_data() );

		$posts = $response->get_data();
		$post = $posts[0];
		$this->assertEquals( $id1, $post['id'] );

		$this->assertPostsWhere( " AND {posts}.ID IN ($id1) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_sticky_no_sticky_posts() {
		$id1 = self::$post_id;

		update_option( 'sticky_posts', array() );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', true );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 0, $response->get_data() );

		// FIXME Since this request returns zero posts, the query is executed twice.
		$this->assertCount( 2, $this->posts_clauses );
		$this->posts_clauses = array_slice( $this->posts_clauses, 0, 1 );

		$this->assertPostsWhere( " AND {posts}.ID IN (0) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_sticky_with_include_no_sticky_posts() {
		$id1 = self::$post_id;

		update_option( 'sticky_posts', array() );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', true );
		$request->set_param( 'include', array( $id1 ) );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 0, $response->get_data() );

		// FIXME Since this request returns zero posts, the query is executed twice.
		$this->assertCount( 2, $this->posts_clauses );
		$this->posts_clauses = array_slice( $this->posts_clauses, 0, 1 );

		$this->assertPostsWhere( " AND {posts}.ID IN (0) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_not_sticky() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		update_option( 'sticky_posts', array( $id2 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', false );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 1, $response->get_data() );

		$posts = $response->get_data();
		$post = $posts[0];
		$this->assertEquals( $id1, $post['id'] );

		$this->assertPostsWhere( " AND {posts}.ID NOT IN ($id2) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_not_sticky_with_exclude() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		update_option( 'sticky_posts', array( $id2 ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', false );
		$request->set_param( 'exclude', array( $id3 ) );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 1, $response->get_data() );

		$posts = $response->get_data();
		$post = $posts[0];
		$this->assertEquals( $id1, $post['id'] );

		$this->assertPostsWhere( " AND {posts}.ID NOT IN ($id3,$id2) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_not_sticky_with_exclude_no_sticky_posts() {
		$id1 = self::$post_id;
		$id2 = $this->factory->post->create( array( 'post_status' => 'publish' ) );
		$id3 = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		update_option( 'sticky_posts', array() );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'sticky', false );
		$request->set_param( 'exclude', array( $id3 ) );

		$response = $this->server->dispatch( $request );
		$this->assertCount( 2, $response->get_data() );

		$posts = $response->get_data();
		$ids = wp_list_pluck( $posts, 'id' );
		sort( $ids );
		$this->assertEquals( array( $id1, $id2 ), $ids );

		$this->assertPostsWhere( " AND {posts}.ID NOT IN ($id3) AND {posts}.post_type = 'post' AND (({posts}.post_status = 'publish'))" );
	}

	public function test_get_items_pagination_headers() {
		// Start of the index
		for ( $i = 0; $i < 49; $i++ ) {
			$this->factory->post->create( array(
				'post_title'   => "Post {$i}",
				) );
		}
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$headers = $response->get_headers();
		$this->assertEquals( 50, $headers['X-WP-Total'] );
		$this->assertEquals( 5, $headers['X-WP-TotalPages'] );
		$next_link = add_query_arg( array(
			'page'    => 2,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertFalse( stripos( $headers['Link'], 'rel="prev"' ) );
		$this->assertContains( '<' . $next_link . '>; rel="next"', $headers['Link'] );
		// 3rd page
		$this->factory->post->create( array(
				'post_title'   => 'Post 51',
				) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'page', 3 );
		$response = $this->server->dispatch( $request );
		$headers = $response->get_headers();
		$this->assertEquals( 51, $headers['X-WP-Total'] );
		$this->assertEquals( 6, $headers['X-WP-TotalPages'] );
		$prev_link = add_query_arg( array(
			'page'    => 2,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertContains( '<' . $prev_link . '>; rel="prev"', $headers['Link'] );
		$next_link = add_query_arg( array(
			'page'    => 4,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertContains( '<' . $next_link . '>; rel="next"', $headers['Link'] );
		// Last page
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'page', 6 );
		$response = $this->server->dispatch( $request );
		$headers = $response->get_headers();
		$this->assertEquals( 51, $headers['X-WP-Total'] );
		$this->assertEquals( 6, $headers['X-WP-TotalPages'] );
		$prev_link = add_query_arg( array(
			'page'    => 5,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertContains( '<' . $prev_link . '>; rel="prev"', $headers['Link'] );
		$this->assertFalse( stripos( $headers['Link'], 'rel="next"' ) );

		// Out of bounds
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'page', 8 );
		$response = $this->server->dispatch( $request );
		$headers = $response->get_headers();
		$this->assertErrorResponse( 'rest_post_invalid_page_number', $response, 400 );

		// With query params.
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_query_params( array( 'per_page' => 5, 'page' => 2 ) );
		$response = $this->server->dispatch( $request );
		$headers = $response->get_headers();
		$this->assertEquals( 51, $headers['X-WP-Total'] );
		$this->assertEquals( 11, $headers['X-WP-TotalPages'] );
		$prev_link = add_query_arg( array(
			'per_page' => 5,
			'page'     => 1,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertContains( '<' . $prev_link . '>; rel="prev"', $headers['Link'] );
		$next_link = add_query_arg( array(
			'per_page' => 5,
			'page'     => 3,
			), rest_url( '/wp/v2/posts' ) );
		$this->assertContains( '<' . $next_link . '>; rel="next"', $headers['Link'] );
	}

	public function test_get_items_private_status_query_var() {
		// Private query vars inaccessible to unauthorized users
		wp_set_current_user( 0 );
		$draft_id = $this->factory->post->create( array( 'post_status' => 'draft' ) );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'status', 'draft' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );

		// But they are accessible to authorized users
		wp_set_current_user( self::$editor_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( $draft_id, $data[0]['id'] );
	}

	public function test_get_items_invalid_per_page() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_query_params( array( 'per_page' => -1 ) );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39061
	 */
	public function test_get_items_invalid_max_pages() {
		// Out of bounds
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'page', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_post_invalid_page_number', $response, 400 );
	}

	public function test_get_items_invalid_context() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'context', 'banana' );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_invalid_date() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'after', rand_str() );
		$request->set_param( 'before', rand_str() );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_get_items_valid_date() {
		$post1 = $this->factory->post->create( array( 'post_date' => '2016-01-15T00:00:00Z' ) );
		$post2 = $this->factory->post->create( array( 'post_date' => '2016-01-16T00:00:00Z' ) );
		$post3 = $this->factory->post->create( array( 'post_date' => '2016-01-17T00:00:00Z' ) );

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_param( 'after', '2016-01-15T00:00:00Z' );
		$request->set_param( 'before', '2016-01-17T00:00:00Z' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( $post2, $data[0]['id'] );
	}

	public function test_get_items_all_post_formats() {
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$formats = array_values( get_post_format_slugs() );

		$this->assertEquals( $formats, $data['schema']['properties']['format']['enum'] );
	}

	public function test_get_item() {
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$this->check_get_post_response( $response, 'view' );
	}

	public function test_get_item_links() {
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$links = $response->get_links();

		$this->assertEquals( rest_url( '/wp/v2/posts/' . self::$post_id ), $links['self'][0]['href'] );
		$this->assertEquals( rest_url( '/wp/v2/posts' ), $links['collection'][0]['href'] );

		$this->assertEquals( rest_url( '/wp/v2/types/' . get_post_type( self::$post_id ) ), $links['about'][0]['href'] );

		$replies_url = rest_url( '/wp/v2/comments' );
		$replies_url = add_query_arg( 'post', self::$post_id, $replies_url );
		$this->assertEquals( $replies_url, $links['replies'][0]['href'] );

		$this->assertEquals( rest_url( '/wp/v2/posts/' . self::$post_id . '/revisions' ), $links['version-history'][0]['href'] );
		$this->assertEquals( 0, $links['version-history'][0]['attributes']['count'] );
		$this->assertFalse( isset( $links['predecessor-version'] ) );

		$attachments_url = rest_url( '/wp/v2/media' );
		$attachments_url = add_query_arg( 'parent', self::$post_id, $attachments_url );
		$this->assertEquals( $attachments_url, $links['https://api.w.org/attachment'][0]['href'] );

		$term_links = $links['https://api.w.org/term'];
		$tag_link = $cat_link = $format_link = null;
		foreach ( $term_links as $link ) {
			if ( 'post_tag' === $link['attributes']['taxonomy'] ) {
				$tag_link = $link;
			} elseif ( 'category' === $link['attributes']['taxonomy'] ) {
				$cat_link = $link;
			} elseif ( 'post_format' === $link['attributes']['taxonomy'] ) {
				$format_link = $link;
			}
		}
		$this->assertNotEmpty( $tag_link );
		$this->assertNotEmpty( $cat_link );
		$this->assertNull( $format_link );

		$tags_url = add_query_arg( 'post', self::$post_id, rest_url( '/wp/v2/tags' ) );
		$this->assertEquals( $tags_url, $tag_link['href'] );

		$category_url = add_query_arg( 'post', self::$post_id, rest_url( '/wp/v2/categories' ) );
		$this->assertEquals( $category_url, $cat_link['href'] );
	}

	public function test_get_item_links_predecessor() {
		wp_update_post(
			array(
				'post_content' => 'This content is marvelous.',
				'ID'           => self::$post_id,
			)
		);
		$revisions  = wp_get_post_revisions( self::$post_id );
		$revision_1 = array_pop( $revisions );

		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = rest_get_server()->dispatch( $request );

		$links = $response->get_links();

		$this->assertEquals( rest_url( '/wp/v2/posts/' . self::$post_id . '/revisions' ), $links['version-history'][0]['href'] );
		$this->assertEquals( 1, $links['version-history'][0]['attributes']['count'] );

		$this->assertEquals( rest_url( '/wp/v2/posts/' . self::$post_id . '/revisions/' . $revision_1->ID ), $links['predecessor-version'][0]['href'] );
		$this->assertEquals( $revision_1->ID, $links['predecessor-version'][0]['attributes']['id'] );
	}

	public function test_get_item_links_no_author() {
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$links = $response->get_links();
		$this->assertFalse( isset( $links['author'] ) );
		wp_update_post( array( 'ID' => self::$post_id, 'post_author' => self::$author_id ) );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$links = $response->get_links();
		$this->assertEquals( rest_url( '/wp/v2/users/' . self::$author_id ), $links['author'][0]['href'] );
	}

	public function test_get_post_draft_status_not_authenicated() {
		$draft_id = $this->factory->post->create( array(
			'post_status' => 'draft',
		) );
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $draft_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden', $response, 401 );
	}

	public function test_get_post_invalid_id() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_invalid_id', $response, 404 );
	}

	public function test_get_post_list_context_with_permission() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_query_params( array(
			'context' => 'edit',
		) );

		wp_set_current_user( self::$editor_id );

		$response = $this->server->dispatch( $request );

		$this->check_get_posts_response( $response, 'edit' );
	}

	public function test_get_post_list_context_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
		$request->set_query_params( array(
			'context' => 'edit',
		) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden_context', $response, 401 );
	}

	public function test_get_post_context_without_permission() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_query_params( array(
			'context' => 'edit',
		) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_forbidden_context', $response, 401 );
	}

	public function test_get_post_with_password() {
		$post_id = $this->factory->post->create( array(
			'post_password' => '$inthebananastand',
		) );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$response = $this->server->dispatch( $request );

		$this->check_get_post_response( $response, 'view' );

		$data = $response->get_data();
		$this->assertEquals( '', $data['content']['rendered'] );
		$this->assertTrue( $data['content']['protected'] );
		$this->assertEquals( '', $data['excerpt']['rendered'] );
		$this->assertTrue( $data['excerpt']['protected'] );
	}

	public function test_get_post_with_password_using_password() {
		$post_id = $this->factory->post->create( array(
			'post_password' => '$inthebananastand',
			'post_content'  => 'Some secret content.',
			'post_excerpt'  => 'Some secret excerpt.',
		) );

		$post = get_post( $post_id );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$request->set_param( 'password', '$inthebananastand' );
		$response = $this->server->dispatch( $request );

		$this->check_get_post_response( $response, 'view' );

		$data = $response->get_data();
		$this->assertEquals( wpautop( $post->post_content ), $data['content']['rendered'] );
		$this->assertTrue( $data['content']['protected'] );
		$this->assertEquals( wpautop( $post->post_excerpt ), $data['excerpt']['rendered'] );
		$this->assertTrue( $data['excerpt']['protected'] );
	}

	public function test_get_post_with_password_using_incorrect_password() {
		$post_id = $this->factory->post->create( array(
			'post_password' => '$inthebananastand',
		) );

		$post = get_post( $post_id );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$request->set_param( 'password', 'wrongpassword' );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_incorrect_password', $response, 403 );
	}

	public function test_get_post_with_password_without_permission() {
		$post_id = $this->factory->post->create( array(
			'post_password' => '$inthebananastand',
			'post_content'  => 'Some secret content.',
			'post_excerpt'  => 'Some secret excerpt.',
		) );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->check_get_post_response( $response, 'view' );
		$this->assertEquals( '', $data['content']['rendered'] );
		$this->assertTrue( $data['content']['protected'] );
		$this->assertEquals( '', $data['excerpt']['rendered'] );
		$this->assertTrue( $data['excerpt']['protected'] );
	}

	public function test_get_item_read_permission_custom_post_status_not_authenticated() {
		register_post_status( 'testpubstatus', array( 'public' => true ) );
		register_post_status( 'testprivtatus', array( 'public' => false ) );
		// Public status
		wp_update_post( array( 'ID' => self::$post_id, 'post_status' => 'testpubstatus' ) );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		// Private status
		wp_update_post( array( 'ID' => self::$post_id, 'post_status' => 'testprivtatus' ) );
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	public function test_prepare_item() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_query_params( array( 'context' => 'edit' ) );
		$response = $this->server->dispatch( $request );

		$this->check_get_post_response( $response, 'edit' );
	}

	public function test_prepare_item_limit_fields() {
		wp_set_current_user( self::$editor_id );
		$endpoint = new WP_REST_Posts_Controller( 'post' );
		$request  = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'id,slug' );
		$obj      = get_post( self::$post_id );
		$response = $endpoint->prepare_item_for_response( $obj, $request );
		$this->assertEquals( array(
			'id',
			'slug',
		), array_keys( $response->get_data() ) );
	}

	public function test_create_item() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->check_create_post_response( $response );
	}

	public function post_dates_provider() {
		$all_statuses = array(
			'draft',
			'publish',
			'future',
			'pending',
			'private',
		);

		$cases_short = array(
			'set date without timezone' => array(
				'statuses' => $all_statuses,
				'params'   => array(
					'timezone_string' => 'America/New_York',
					'date'            => '2016-12-12T14:00:00',
				),
				'results' => array(
					'date'            => '2016-12-12T14:00:00',
					'date_gmt'        => '2016-12-12T19:00:00',
				),
			),
			'set date_gmt without timezone' => array(
				'statuses' => $all_statuses,
				'params'   => array(
					'timezone_string' => 'America/New_York',
					'date_gmt'        => '2016-12-12T19:00:00',
				),
				'results' => array(
					'date'            => '2016-12-12T14:00:00',
					'date_gmt'        => '2016-12-12T19:00:00',
				),
			),
			'set date with timezone' => array(
				'statuses' => array( 'draft', 'publish' ),
				'params'   => array(
					'timezone_string' => 'America/New_York',
					'date'            => '2016-12-12T18:00:00-01:00',
				),
				'results' => array(
					'date'            => '2016-12-12T14:00:00',
					'date_gmt'        => '2016-12-12T19:00:00',
				),
			),
			'set date_gmt with timezone' => array(
				'statuses' => array( 'draft', 'publish' ),
				'params'   => array(
					'timezone_string' => 'America/New_York',
					'date_gmt'        => '2016-12-12T18:00:00-01:00',
				),
				'results' => array(
					'date'            => '2016-12-12T14:00:00',
					'date_gmt'        => '2016-12-12T19:00:00',
				),
			),
		);

		$cases = array();
		foreach ( $cases_short as $description => $case ) {
			foreach ( $case['statuses'] as $status ) {
				$cases[ $description . ', status=' . $status ] = array(
					$status,
					$case['params'],
					$case['results'],
				);
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider post_dates_provider
	 */
	public function test_create_post_date( $status, $params, $results ) {
		wp_set_current_user( self::$editor_id );
		update_option( 'timezone_string', $params['timezone_string'] );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_param( 'status', $status );
		$request->set_param( 'title', 'not empty' );
		if ( isset( $params['date'] ) ) {
			$request->set_param( 'date', $params['date'] );
		}
		if ( isset( $params['date_gmt'] ) ) {
			$request->set_param( 'date_gmt', $params['date_gmt'] );
		}
		$response = $this->server->dispatch( $request );

		update_option( 'timezone_string', '' );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$post = get_post( $data['id'] );

		$this->assertEquals( $results['date'], $data['date'] );
		$post_date = str_replace( 'T', ' ', $results['date'] );
		$this->assertEquals( $post_date, $post->post_date );

		$this->assertEquals( $results['date_gmt'], $data['date_gmt'] );
		$post_date_gmt = str_replace( 'T', ' ', $results['date_gmt'] );
		$this->assertEquals( $post_date_gmt, $post->post_date_gmt );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38698
	 */
	public function test_create_item_with_template() {
		wp_set_current_user( self::$editor_id );
		add_filter( 'theme_post_templates', array( $this, 'filter_theme_post_templates' ) );

		// reregister the route as we now have a template available.
		$GLOBALS['wp_rest_server']->override_by_default = true;
		$controller = new WP_REST_Posts_Controller( 'post' );
		$controller->register_routes();
		$GLOBALS['wp_rest_server']->override_by_default = false;

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'template' => 'post-my-test-template.php',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$post_template = get_page_template_slug( get_post( $data['id'] ) );

		remove_filter( 'theme_post_templates', array( $this, 'filter_theme_post_templates' ) );

		$this->assertEquals( 'post-my-test-template.php', $data['template'] );
		$this->assertEquals( 'post-my-test-template.php', $post_template );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38698
	 */
	public function test_create_item_with_template_none_available() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'template' => 'post-my-test-template.php',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38877
	 */
	public function test_create_item_with_template_none() {
		wp_set_current_user( self::$editor_id );
		add_filter( 'theme_post_templates', array( $this, 'filter_theme_post_templates' ) );
		update_post_meta( self::$post_id, '_wp_page_template', 'post-my-test-template.php' );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'template' => '',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$post_template = get_page_template_slug( get_post( $data['id'] ) );

		$this->assertEquals( '', $data['template'] );
		$this->assertEquals( '', $post_template );
	}

	public function test_rest_create_item() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->add_header( 'content-type', 'application/json' );
		$params = $this->set_post_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_create_post_response( $response );
	}

	public function test_create_post_invalid_id() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'id' => '3',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_exists', $response, 400 );
	}

	public function test_create_post_as_contributor() {
		wp_set_current_user( self::$contributor_id );
		update_option( 'timezone_string', 'America/Chicago' );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			// This results in a special `post_date_gmt` value of
			// '0000-00-00 00:00:00'.  See https://core.trac.wordpress.org/ticket/38883.
			'status' => 'pending',
		) );

		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$post = get_post( $data['id'] );
		$this->assertEquals( '0000-00-00 00:00:00', $post->post_date_gmt );
		$this->assertNotEquals( '0000-00-00T00:00:00', $data['date_gmt'] );

		$this->check_create_post_response( $response );

		update_option( 'timezone_string', '' );
	}

	public function test_create_post_sticky() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'sticky' => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$new_data = $response->get_data();
		$this->assertEquals( true, $new_data['sticky'] );
		$post = get_post( $new_data['id'] );
		$this->assertEquals( true, is_sticky( $post->ID ) );
	}

	public function test_create_post_sticky_as_contributor() {
		wp_set_current_user( self::$contributor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'sticky' => true,
			'status' => 'pending',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_assign_sticky', $response, 403 );
	}

	public function test_create_post_other_author_without_permission() {
		wp_set_current_user( self::$author_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data(array(
			'author' => self::$editor_id,
		));
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_edit_others', $response, 403 );
	}

	public function test_create_post_without_permission() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'draft',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_create', $response, 401 );
	}

	public function test_create_post_draft() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'draft',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'draft', $data['status'] );
		$this->assertEquals( 'draft', $new_post->post_status );
		// Confirm dates are shimmed for gmt_offset
		$post_modified_gmt = date( 'Y-m-d H:i:s', strtotime( $new_post->post_modified ) + ( get_option( 'gmt_offset' ) * 3600 ) );
		$post_date_gmt = date( 'Y-m-d H:i:s', strtotime( $new_post->post_date ) + ( get_option( 'gmt_offset' ) * 3600 ) );

		$this->assertEquals( mysql_to_rfc3339( $post_modified_gmt ), $data['modified_gmt'] );
		$this->assertEquals( mysql_to_rfc3339( $post_date_gmt ), $data['date_gmt'] );
	}

	public function test_create_post_private() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'private',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'private', $data['status'] );
		$this->assertEquals( 'private', $new_post->post_status );
	}

	public function test_create_post_private_without_permission() {
		wp_set_current_user( self::$author_id );
		$user = wp_get_current_user();
		$user->add_cap( 'publish_posts', false );
		// Flush capabilities, https://core.trac.wordpress.org/ticket/28374
		$user->get_role_caps();
		$user->update_user_level_from_caps();

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'private',
			'author' => self::$author_id,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_publish', $response, 403 );
	}

	public function test_create_post_publish_without_permission() {
		wp_set_current_user( self::$author_id );
		$user = wp_get_current_user();
		$user->add_cap( 'publish_posts', false );
		// Flush capabilities, https://core.trac.wordpress.org/ticket/28374
		$user->get_role_caps();
		$user->update_user_level_from_caps();

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'publish',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_publish', $response, 403 );
	}

	public function test_create_post_invalid_status() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'status' => 'teststatus',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_create_post_with_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'format' => 'gallery',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'gallery', $data['format'] );
		$this->assertEquals( 'gallery', get_post_format( $new_post->ID ) );
	}

	public function test_create_post_with_standard_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'format' => 'standard',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'standard', $data['format'] );
		$this->assertFalse( get_post_format( $new_post->ID ) );
	}

	public function test_create_post_with_invalid_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'format' => 'testformat',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * Test with a valid format, but one unsupported by the theme.
	 *
	 * https://core.trac.wordpress.org/ticket/38610
	 */
	public function test_create_post_with_unsupported_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'format' => 'link',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'link', $data['format'] );
	}

	public function test_create_update_post_with_featured_media() {

		$file = DIR_TESTDATA . '/images/canola.jpg';
		$this->attachment_id = $this->factory->attachment->create_object( $file, 0, array(
			'post_mime_type' => 'image/jpeg',
			'menu_order' => rand( 1, 100 ),
		) );

		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'featured_media' => $this->attachment_id,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( $this->attachment_id, $data['featured_media'] );
		$this->assertEquals( $this->attachment_id, (int) get_post_thumbnail_id( $new_post->ID ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $new_post->ID );
		$params = $this->set_post_data( array(
			'featured_media' => 0,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 0, $data['featured_media'] );
		$this->assertEquals( 0, (int) get_post_thumbnail_id( $new_post->ID ) );
	}

	public function test_create_post_invalid_author() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'author' => -1,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_author', $response, 400 );
	}

	public function test_create_post_invalid_author_without_permission() {
		wp_set_current_user( self::$author_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'author' => self::$editor_id,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_edit_others', $response, 403 );
	}

	public function test_create_post_with_password() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password' => 'testing',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( 'testing', $data['password'] );
	}

	public function test_create_post_with_falsy_password() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password' => '0',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$this->assertEquals( '0', $data['password'] );
	}

	public function test_create_post_with_empty_string_password_and_sticky() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password' => '',
			'sticky'   => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( '', $data['password'] );
	}

	public function test_create_post_with_password_and_sticky_fails() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password' => '123',
			'sticky'   => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_field', $response, 400 );
	}

	public function test_create_post_custom_date() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'date' => '2010-01-01T02:00:00Z',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$time = gmmktime( 2, 0, 0, 1, 1, 2010 );
		$this->assertEquals( '2010-01-01T02:00:00', $data['date'] );
		$this->assertEquals( $time, strtotime( $new_post->post_date ) );
	}

	public function test_create_post_custom_date_with_timezone() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'date' => '2010-01-01T02:00:00-10:00',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$time = gmmktime( 12, 0, 0, 1, 1, 2010 );

		$this->assertEquals( '2010-01-01T12:00:00', $data['date'] );
		$this->assertEquals( '2010-01-01T12:00:00', $data['modified'] );

		$this->assertEquals( $time, strtotime( $new_post->post_date ) );
		$this->assertEquals( $time, strtotime( $new_post->post_modified ) );
	}

	public function test_create_post_with_db_error() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params  = $this->set_post_data( array() );
		$request->set_body_params( $params );

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

		$this->assertErrorResponse( 'db_insert_error', $response, 500 );
	}

	public function test_create_post_with_invalid_date() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'date' => '2010-60-01T02:00:00Z',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_create_post_with_invalid_date_gmt() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'date_gmt' => '2010-60-01T02:00:00',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_create_post_with_quotes_in_title() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'title' => "Rob O'Rourke's Diary",
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( "Rob O'Rourke's Diary", $new_data['title']['raw'] );
	}

	public function test_create_post_with_categories() {
		wp_set_current_user( self::$editor_id );
		$category = wp_insert_term( 'Test Category', 'category' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password'   => 'testing',
			'categories' => array(
				$category['term_id']
			),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( array( $category['term_id'] ), $data['categories'] );
	}

	public function test_create_post_with_categories_as_csv() {
		wp_set_current_user( self::$editor_id );
		$category = wp_insert_term( 'Chicken', 'category' );
		$category2 = wp_insert_term( 'Ribs', 'category' );
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'categories' => $category['term_id'] . ',' . $category2['term_id'],
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( array( $category['term_id'], $category2['term_id'] ), $data['categories'] );
	}

	public function test_create_post_with_invalid_categories() {
		wp_set_current_user( self::$editor_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password'   => 'testing',
			'categories' => array(
				REST_TESTS_IMPOSSIBLY_HIGH_NUMBER
			),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$this->assertEquals( array(), $data['categories'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38505
	 */
	public function test_create_post_with_categories_that_cannot_be_assigned_by_current_user() {
		$cats = self::factory()->category->create_many( 2 );
		$this->forbidden_cat = $cats[1];

		wp_set_current_user( self::$editor_id );
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$params = $this->set_post_data( array(
			'password'   => 'testing',
			'categories' => $cats,
		) );
		$request->set_body_params( $params );

		add_filter( 'map_meta_cap', array( $this, 'revoke_assign_term' ), 10, 4 );
		$response = $this->server->dispatch( $request );
		remove_filter( 'map_meta_cap', array( $this, 'revoke_assign_term' ), 10, 4 );

		$this->assertErrorResponse( 'rest_cannot_assign_term', $response, 403 );
	}

	public function revoke_assign_term( $caps, $cap, $user_id, $args ) {
		if ( 'assign_term' === $cap && isset( $args[0] ) && $this->forbidden_cat == $args[0] ) {
			$caps = array( 'do_not_allow' );
		}
		return $caps;
	}

	public function test_update_item() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->add_header( 'content-type', 'application/x-www-form-urlencoded' );
		$params = $this->set_post_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->check_update_post_response( $response );
		$new_data = $response->get_data();
		$this->assertEquals( self::$post_id, $new_data['id'] );
		$this->assertEquals( $params['title'], $new_data['title']['raw'] );
		$this->assertEquals( $params['content'], $new_data['content']['raw'] );
		$this->assertEquals( $params['excerpt'], $new_data['excerpt']['raw'] );
		$post = get_post( self::$post_id );
		$this->assertEquals( $params['title'], $post->post_title );
		$this->assertEquals( $params['content'], $post->post_content );
		$this->assertEquals( $params['excerpt'], $post->post_excerpt );
	}

	public function test_update_item_no_change() {
		wp_set_current_user( self::$editor_id );
		$post = get_post( self::$post_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_param( 'author', $post->post_author );

		// Run twice to make sure that the update still succeeds even if no DB
		// rows are updated.
		$response = $this->server->dispatch( $request );
		$this->check_update_post_response( $response );

		$response = $this->server->dispatch( $request );
		$this->check_update_post_response( $response );
	}

	public function test_rest_update_post() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->add_header( 'content-type', 'application/json' );
		$params = $this->set_post_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_update_post_response( $response );
		$new_data = $response->get_data();
		$this->assertEquals( self::$post_id, $new_data['id'] );
		$this->assertEquals( $params['title'], $new_data['title']['raw'] );
		$this->assertEquals( $params['content'], $new_data['content']['raw'] );
		$this->assertEquals( $params['excerpt'], $new_data['excerpt']['raw'] );
		$post = get_post( self::$post_id );
		$this->assertEquals( $params['title'], $post->post_title );
		$this->assertEquals( $params['content'], $post->post_content );
		$this->assertEquals( $params['excerpt'], $post->post_excerpt );
	}

	public function test_rest_update_post_raw() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->add_header( 'content-type', 'application/json' );
		$params = $this->set_raw_post_data();
		$request->set_body( wp_json_encode( $params ) );
		$response = $this->server->dispatch( $request );

		$this->check_update_post_response( $response );
		$new_data = $response->get_data();
		$this->assertEquals( self::$post_id, $new_data['id'] );
		$this->assertEquals( $params['title']['raw'], $new_data['title']['raw'] );
		$this->assertEquals( $params['content']['raw'], $new_data['content']['raw'] );
		$this->assertEquals( $params['excerpt']['raw'], $new_data['excerpt']['raw'] );
		$post = get_post( self::$post_id );
		$this->assertEquals( $params['title']['raw'], $post->post_title );
		$this->assertEquals( $params['content']['raw'], $post->post_content );
		$this->assertEquals( $params['excerpt']['raw'], $post->post_excerpt );
	}

	public function test_update_post_without_extra_params() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data();
		unset( $params['type'] );
		unset( $params['name'] );
		unset( $params['author'] );
		unset( $params['status'] );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->check_update_post_response( $response );
	}

	public function test_update_post_without_permission() {
		wp_set_current_user( self::$editor_id );
		$user = wp_get_current_user();
		$user->add_cap( 'edit_published_posts', false );
		// Flush capabilities, https://core.trac.wordpress.org/ticket/28374
		$user->get_role_caps();
		$user->update_user_level_from_caps();

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data();
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_edit', $response, 403 );
	}

	public function test_update_post_sticky_as_contributor() {
		wp_set_current_user( self::$contributor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'sticky' => true,
			'status' => 'pending',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_edit', $response, 403 );
	}

	public function test_update_post_invalid_id() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', REST_TESTS_IMPOSSIBLY_HIGH_NUMBER ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_invalid_id', $response, 404 );
	}

	public function test_update_post_invalid_route() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/pages/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_invalid_id', $response, 404 );
	}

	public function test_update_post_with_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'format' => 'gallery',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'gallery', $data['format'] );
		$this->assertEquals( 'gallery', get_post_format( $new_post->ID ) );
	}

	public function test_update_post_with_standard_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'format' => 'standard',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$new_post = get_post( $data['id'] );
		$this->assertEquals( 'standard', $data['format'] );
		$this->assertFalse( get_post_format( $new_post->ID ) );
	}

	public function test_update_post_with_invalid_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'format' => 'testformat',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	/**
	 * Test with a valid format, but one unsupported by the theme.
	 *
	 * https://core.trac.wordpress.org/ticket/38610
	 */
	public function test_update_post_with_unsupported_format() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'format' => 'link',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'link', $data['format'] );
	}

	public function test_update_post_ignore_readonly() {
		wp_set_current_user( self::$editor_id );

		$new_content = rand_str();
		$expected_modified = current_time( 'mysql' );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'modified' => '2010-06-01T02:00:00Z',
			'content'  => $new_content,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		// The readonly modified param should be ignored, request should be a success.
		$data = $response->get_data();
		$new_post = get_post( $data['id'] );

		$this->assertEquals( $new_content, $data['content']['raw'] );
		$this->assertEquals( $new_content, $new_post->post_content );

		// The modified date should equal the current time.
		$this->assertEquals( date( 'Y-m-d', strtotime( mysql_to_rfc3339( $expected_modified ) ) ), date( 'Y-m-d', strtotime( $data['modified'] ) ) );
		$this->assertEquals( date( 'Y-m-d', strtotime( $expected_modified ) ), date( 'Y-m-d', strtotime( $new_post->post_modified ) ) );
	}

	/**
	 * @dataProvider post_dates_provider
	 */
	public function test_update_post_date( $status, $params, $results ) {
		wp_set_current_user( self::$editor_id );
		update_option( 'timezone_string', $params['timezone_string'] );

		$post_id = $this->factory->post->create( array( 'post_status' => $status ) );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', $post_id ) );
		if ( isset( $params['date'] ) ) {
			$request->set_param( 'date', $params['date'] );
		}
		if ( isset( $params['date_gmt'] ) ) {
			$request->set_param( 'date_gmt', $params['date_gmt'] );
		}
		$response = $this->server->dispatch( $request );

		update_option( 'timezone_string', '' );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$post = get_post( $data['id'] );

		$this->assertEquals( $results['date'], $data['date'] );
		$post_date = str_replace( 'T', ' ', $results['date'] );
		$this->assertEquals( $post_date, $post->post_date );

		$this->assertEquals( $results['date_gmt'], $data['date_gmt'] );
		$post_date_gmt = str_replace( 'T', ' ', $results['date_gmt'] );
		$this->assertEquals( $post_date_gmt, $post->post_date_gmt );
	}

	public function test_update_post_with_invalid_date() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'date' => rand_str(),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_update_post_with_invalid_date_gmt() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'date_gmt' => rand_str(),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );
	}

	public function test_empty_post_date_gmt_shimmed_using_post_date() {
		global $wpdb;

		wp_set_current_user( self::$editor_id );
		update_option( 'timezone_string', 'America/Chicago' );

		// Need to set dates using wpdb directly because `wp_update_post` and
		// `wp_insert_post` have additional validation on dates.
		$post_id = $this->factory->post->create();
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => '2016-02-23 12:00:00',
				'post_date_gmt' => '0000-00-00 00:00:00',
			),
			array(
				'ID' => $post_id,
			),
			array( '%s', '%s' ),
			array( '%d' )
		);
		wp_cache_delete( $post_id, 'posts' );

		$post = get_post( $post_id );
		$this->assertEquals( $post->post_date,     '2016-02-23 12:00:00' );
		$this->assertEquals( $post->post_date_gmt, '0000-00-00 00:00:00' );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '2016-02-23T12:00:00', $data['date'] );
		$this->assertEquals( '2016-02-23T18:00:00', $data['date_gmt'] );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$request->set_param( 'date', '2016-02-23T13:00:00' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertEquals( '2016-02-23T13:00:00', $data['date'] );
		$this->assertEquals( '2016-02-23T19:00:00', $data['date_gmt'] );

		$post = get_post( $post_id );
		$this->assertEquals( $post->post_date,     '2016-02-23 13:00:00' );
		$this->assertEquals( $post->post_date_gmt, '2016-02-23 19:00:00' );

		update_option( 'timezone_string', '' );
	}

	public function test_update_post_slug() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'slug' => 'sample-slug',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$new_data = $response->get_data();
		$this->assertEquals( 'sample-slug', $new_data['slug'] );
		$post = get_post( $new_data['id'] );
		$this->assertEquals( 'sample-slug', $post->post_name );
	}

	public function test_update_post_slug_accented_chars() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'slug' => 'tęst-acceńted-chäræcters',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$new_data = $response->get_data();
		$this->assertEquals( 'test-accented-charaecters', $new_data['slug'] );
		$post = get_post( $new_data['id'] );
		$this->assertEquals( 'test-accented-charaecters', $post->post_name );
	}

	public function test_update_post_sticky() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'sticky' => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$new_data = $response->get_data();
		$this->assertEquals( true, $new_data['sticky'] );
		$post = get_post( $new_data['id'] );
		$this->assertEquals( true, is_sticky( $post->ID ) );

		// Updating another field shouldn't change sticky status
		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'title'       => 'This should not reset sticky',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$new_data = $response->get_data();
		$this->assertEquals( true, $new_data['sticky'] );
		$post = get_post( $new_data['id'] );
		$this->assertEquals( true, is_sticky( $post->ID ) );
	}

	public function test_update_post_excerpt() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( array(
			'excerpt' => 'An Excerpt',
		) );

		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( 'An Excerpt', $new_data['excerpt']['raw'] );
	}

	public function test_update_post_empty_excerpt() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( array(
			'excerpt' => '',
		) );

		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( '', $new_data['excerpt']['raw'] );
	}

	public function test_update_post_content() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( array(
			'content' => 'Some Content',
		) );

		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( 'Some Content', $new_data['content']['raw'] );
	}

	public function test_update_post_empty_content() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( array(
			'content' => '',
		) );

		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( '', $new_data['content']['raw'] );
	}

	public function test_update_post_with_empty_password() {
		wp_set_current_user( self::$editor_id );
		wp_update_post( array(
			'ID'            => self::$post_id,
			'post_password' => 'foo',
		) );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'password' => '',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( '', $data['password'] );
	}

	public function test_update_post_with_password_and_sticky_fails() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'password' => '123',
			'sticky'   => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_field', $response, 400 );
	}

	public function test_update_stick_post_with_password_fails() {
		wp_set_current_user( self::$editor_id );

		stick_post( self::$post_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'password' => '123',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_field', $response, 400 );
	}

	public function test_update_password_protected_post_with_sticky_fails() {
		wp_set_current_user( self::$editor_id );

		wp_update_post( array( 'ID' => self::$post_id, 'post_password' => '123' ) );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'sticky' => true,
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_field', $response, 400 );
	}

	public function test_update_post_with_quotes_in_title() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'title' => "Rob O'Rourke's Diary",
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( "Rob O'Rourke's Diary", $new_data['title']['raw'] );
	}

	public function test_update_post_with_categories() {

		wp_set_current_user( self::$editor_id );
		$category = wp_insert_term( 'Test Category', 'category' );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'title' => 'Tester',
			'categories' => array(
				$category['term_id'],
			),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( array( $category['term_id'] ), $new_data['categories'] );
		$categories_path = '';
		$links = $response->get_links();
		foreach ( $links['https://api.w.org/term'] as $link ) {
			if ( 'category' === $link['attributes']['taxonomy'] ) {
				$categories_path = $link['href'];
			}
		}
		$query = parse_url( $categories_path, PHP_URL_QUERY );
		parse_str( $query, $args );
		$request = new WP_REST_Request( 'GET', $args['rest_route'] );
		unset( $args['rest_route'] );
		$request->set_query_params( $args );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertEquals( 'Test Category', $data[0]['name'] );
	}

	public function test_update_post_with_empty_categories() {

		wp_set_current_user( self::$editor_id );
		$category = wp_insert_term( 'Test Category', 'category' );
		wp_set_object_terms( self::$post_id, $category['term_id'], 'category' );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'title' => 'Tester',
			'categories' => array(),
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );
		$new_data = $response->get_data();
		$this->assertEquals( array(), $new_data['categories'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38505
	 */
	public function test_update_post_with_categories_that_cannot_be_assigned_by_current_user() {
		$cats = self::factory()->category->create_many( 2 );
		$this->forbidden_cat = $cats[1];

		wp_set_current_user( self::$editor_id );
		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'password'   => 'testing',
			'categories' => $cats,
		) );
		$request->set_body_params( $params );

		add_filter( 'map_meta_cap', array( $this, 'revoke_assign_term' ), 10, 4 );
		$response = $this->server->dispatch( $request );
		remove_filter( 'map_meta_cap', array( $this, 'revoke_assign_term' ), 10, 4 );

		$this->assertErrorResponse( 'rest_cannot_assign_term', $response, 403 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38698
	 */
	public function test_update_item_with_template() {
		wp_set_current_user( self::$editor_id );
		add_filter( 'theme_post_templates', array( $this, 'filter_theme_post_templates' ) );

		// reregister the route as we now have a template available.
		$GLOBALS['wp_rest_server']->override_by_default = true;
		$controller = new WP_REST_Posts_Controller( 'post' );
		$controller->register_routes();
		$GLOBALS['wp_rest_server']->override_by_default = false;

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'template' => 'post-my-test-template.php',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$post_template = get_page_template_slug( get_post( $data['id'] ) );

		$this->assertEquals( 'post-my-test-template.php', $data['template'] );
		$this->assertEquals( 'post-my-test-template.php', $post_template );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38877
	 */
	public function test_update_item_with_template_none() {
		wp_set_current_user( self::$editor_id );
		add_filter( 'theme_post_templates', array( $this, 'filter_theme_post_templates' ) );
		update_post_meta( self::$post_id, '_wp_page_template', 'post-my-test-template.php' );

		// reregister the route as we now have a template available.
		$GLOBALS['wp_rest_server']->override_by_default = true;
		$controller = new WP_REST_Posts_Controller( 'post' );
		$controller->register_routes();
		$GLOBALS['wp_rest_server']->override_by_default = false;

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'template' => '',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();
		$post_template = get_page_template_slug( get_post( $data['id'] ) );

		$this->assertEquals( '', $data['template'] );
		$this->assertEquals( '', $post_template );
	}

	/**
	 * Test update_item() with same template that no longer exists.
	 *
	 * @covers WP_REST_Posts_Controller::check_template()
	 * @see https://core.trac.wordpress.org/ticket/39996
	 */
	public function test_update_item_with_same_template_that_no_longer_exists() {

		wp_set_current_user( self::$editor_id );

		update_post_meta( self::$post_id, '_wp_page_template', 'post-my-invalid-template.php' );

		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$params = $this->set_post_data( array(
			'template' => 'post-my-invalid-template.php',
		) );
		$request->set_body_params( $params );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$post_template = get_page_template_slug( get_post( $data['id'] ) );

		$this->assertEquals( 'post-my-invalid-template.php', $post_template );
		$this->assertEquals( 'post-my-invalid-template.php', $data['template'] );
	}

	public function verify_post_roundtrip( $input = array(), $expected_output = array() ) {
		// Create the post
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		foreach ( $input as $name => $value ) {
			$request->set_param( $name, $value );
		}
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 201, $response->get_status() );
		$actual_output = $response->get_data();

		// Compare expected API output to actual API output
		$this->assertEquals( $expected_output['title']['raw']       , $actual_output['title']['raw'] );
		$this->assertEquals( $expected_output['title']['rendered']  , trim( $actual_output['title']['rendered'] ) );
		$this->assertEquals( $expected_output['content']['raw']     , $actual_output['content']['raw'] );
		$this->assertEquals( $expected_output['content']['rendered'], trim( $actual_output['content']['rendered'] ) );
		$this->assertEquals( $expected_output['excerpt']['raw']     , $actual_output['excerpt']['raw'] );
		$this->assertEquals( $expected_output['excerpt']['rendered'], trim( $actual_output['excerpt']['rendered'] ) );

		// Compare expected API output to WP internal values
		$post = get_post( $actual_output['id'] );
		$this->assertEquals( $expected_output['title']['raw']  , $post->post_title );
		$this->assertEquals( $expected_output['content']['raw'], $post->post_content );
		$this->assertEquals( $expected_output['excerpt']['raw'], $post->post_excerpt );

		// Update the post
		$request = new WP_REST_Request( 'PUT', sprintf( '/wp/v2/posts/%d', $actual_output['id'] ) );
		foreach ( $input as $name => $value ) {
			$request->set_param( $name, $value );
		}
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$actual_output = $response->get_data();

		// Compare expected API output to actual API output
		$this->assertEquals( $expected_output['title']['raw']       , $actual_output['title']['raw'] );
		$this->assertEquals( $expected_output['title']['rendered']  , trim( $actual_output['title']['rendered'] ) );
		$this->assertEquals( $expected_output['content']['raw']     , $actual_output['content']['raw'] );
		$this->assertEquals( $expected_output['content']['rendered'], trim( $actual_output['content']['rendered'] ) );
		$this->assertEquals( $expected_output['excerpt']['raw']     , $actual_output['excerpt']['raw'] );
		$this->assertEquals( $expected_output['excerpt']['rendered'], trim( $actual_output['excerpt']['rendered'] ) );

		// Compare expected API output to WP internal values
		$post = get_post( $actual_output['id'] );
		$this->assertEquals( $expected_output['title']['raw']  , $post->post_title );
		$this->assertEquals( $expected_output['content']['raw'], $post->post_content );
		$this->assertEquals( $expected_output['excerpt']['raw'], $post->post_excerpt );
	}

	public static function post_roundtrip_provider() {
		return array(
			array(
				// Raw values.
				array(
					'title'   => '\o/ ¯\_(ツ)_/¯',
					'content' => '\o/ ¯\_(ツ)_/¯',
					'excerpt' => '\o/ ¯\_(ツ)_/¯',
				),
				// Expected returned values.
				array(
					'title' => array(
						'raw'      => '\o/ ¯\_(ツ)_/¯',
						'rendered' => '\o/ ¯\_(ツ)_/¯',
					),
					'content' => array(
						'raw'      => '\o/ ¯\_(ツ)_/¯',
						'rendered' => '<p>\o/ ¯\_(ツ)_/¯</p>',
					),
					'excerpt' => array(
						'raw'      => '\o/ ¯\_(ツ)_/¯',
						'rendered' => '<p>\o/ ¯\_(ツ)_/¯</p>',
					),
				)
			),
			array(
				// Raw values.
				array(
					'title'   => '\\\&\\\ &amp; &invalid; < &lt; &amp;lt;',
					'content' => '\\\&\\\ &amp; &invalid; < &lt; &amp;lt;',
					'excerpt' => '\\\&\\\ &amp; &invalid; < &lt; &amp;lt;',
				),
				// Expected returned values.
				array(
					'title' => array(
						'raw'      => '\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;',
						'rendered' => '\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;',
					),
					'content' => array(
						'raw'      => '\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;',
						'rendered' => '<p>\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;</p>',
					),
					'excerpt' => array(
						'raw'      => '\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;',
						'rendered' => '<p>\\\&amp;\\\ &amp; &amp;invalid; &lt; &lt; &amp;lt;</p>',
					),
				),
			),
			array(
				// Raw values.
				array(
					'title'   => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
					'content' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
					'excerpt' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				),
				// Expected returned values.
				array(
					'title' => array(
						'raw'      => 'div <strong>strong</strong> oh noes',
						'rendered' => 'div <strong>strong</strong> oh noes',
					),
					'content' => array(
						'raw'      => '<div>div</div> <strong>strong</strong> oh noes',
						'rendered' => "<div>div</div>\n<p> <strong>strong</strong> oh noes</p>",
					),
					'excerpt' => array(
						'raw'      => '<div>div</div> <strong>strong</strong> oh noes',
						'rendered' => "<div>div</div>\n<p> <strong>strong</strong> oh noes</p>",
					),
				)
			),
			array(
				// Raw values.
				array(
					'title'   => '<a href="#" target="_blank" rel="noopener noreferrer" data-unfiltered=true>link</a>',
					'content' => '<a href="#" target="_blank" rel="noopener noreferrer" data-unfiltered=true>link</a>',
					'excerpt' => '<a href="#" target="_blank" rel="noopener noreferrer" data-unfiltered=true>link</a>',
				),
				// Expected returned values.
				array(
					'title' => array(
						'raw'      => '<a href="#">link</a>',
						'rendered' => '<a href="#">link</a>',
					),
					'content' => array(
						'raw'      => '<a href="#" target="_blank" rel="noopener noreferrer">link</a>',
						'rendered' => '<p><a href="#" target="_blank" rel="noopener noreferrer">link</a></p>',
					),
					'excerpt' => array(
						'raw'      => '<a href="#" target="_blank" rel="noopener noreferrer">link</a>',
						'rendered' => '<p><a href="#" target="_blank" rel="noopener noreferrer">link</a></p>',
					),
				)
			),
		);
	}

	/**
	 * @dataProvider post_roundtrip_provider
	 */
	public function test_post_roundtrip_as_author( $raw, $expected ) {
		wp_set_current_user( self::$author_id );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );
		$this->verify_post_roundtrip( $raw, $expected );
	}

	public function test_post_roundtrip_as_editor_unfiltered_html() {
		wp_set_current_user( self::$editor_id );
		if ( is_multisite() ) {
			$this->assertFalse( current_user_can( 'unfiltered_html' ) );
			$this->verify_post_roundtrip( array(
				'title'   => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'content' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'excerpt' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
			), array(
				'title' => array(
					'raw'      => 'div <strong>strong</strong> oh noes',
					'rendered' => 'div <strong>strong</strong> oh noes',
				),
				'content' => array(
					'raw'      => '<div>div</div> <strong>strong</strong> oh noes',
					'rendered' => "<div>div</div>\n<p> <strong>strong</strong> oh noes</p>",
				),
				'excerpt' => array(
					'raw'      => '<div>div</div> <strong>strong</strong> oh noes',
					'rendered' => "<div>div</div>\n<p> <strong>strong</strong> oh noes</p>",
				),
			) );
		} else {
			$this->assertTrue( current_user_can( 'unfiltered_html' ) );
			$this->verify_post_roundtrip( array(
				'title'   => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'content' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'excerpt' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
			), array(
				'title' => array(
					'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
					'rendered' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				),
				'content' => array(
					'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
					'rendered' => "<div>div</div>\n<p> <strong>strong</strong> <script>oh noes</script></p>",
				),
				'excerpt' => array(
					'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
					'rendered' => "<div>div</div>\n<p> <strong>strong</strong> <script>oh noes</script></p>",
				),
			) );
		}
	}

	public function test_post_roundtrip_as_superadmin_unfiltered_html() {
		wp_set_current_user( self::$superadmin_id );
		$this->assertTrue( current_user_can( 'unfiltered_html' ) );
		$this->verify_post_roundtrip( array(
			'title'   => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
			'content' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
			'excerpt' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
		), array(
			'title' => array(
				'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'rendered' => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
			),
			'content' => array(
				'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'rendered' => "<div>div</div>\n<p> <strong>strong</strong> <script>oh noes</script></p>",
			),
			'excerpt' => array(
				'raw'      => '<div>div</div> <strong>strong</strong> <script>oh noes</script>',
				'rendered' => "<div>div</div>\n<p> <strong>strong</strong> <script>oh noes</script></p>",
			),
		) );
	}

	public function test_delete_item() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Deleted post' ) );
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'DELETE', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$request->set_param( 'force', 'false' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'Deleted post', $data['title']['raw'] );
		$this->assertEquals( 'trash', $data['status'] );
	}

	public function test_delete_item_skip_trash() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Deleted post' ) );
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'DELETE', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$request['force'] = true;
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertNotEmpty( $data['previous'] );
	}

	public function test_delete_item_already_trashed() {
		$post_id = $this->factory->post->create( array( 'post_title' => 'Deleted post' ) );
		wp_set_current_user( self::$editor_id );
		$request = new WP_REST_Request( 'DELETE', sprintf( '/wp/v2/posts/%d', $post_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$response = $this->server->dispatch( $request );
		$this->assertErrorResponse( 'rest_already_trashed', $response, 410 );
	}

	public function test_delete_post_invalid_id() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/posts/' . REST_TESTS_IMPOSSIBLY_HIGH_NUMBER );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_invalid_id', $response, 404 );
	}

	public function test_delete_post_invalid_post_type() {
		$page_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'DELETE', '/wp/v2/posts/' . $page_id );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_post_invalid_id', $response, 404 );
	}

	public function test_delete_post_without_permission() {
		wp_set_current_user( self::$author_id );

		$request = new WP_REST_Request( 'DELETE', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_cannot_delete', $response, 403 );
	}

	public function test_register_post_type_invalid_controller() {

		register_post_type( 'invalid-controller', array( 'show_in_rest' => true, 'rest_controller_class' => 'Fake_Class_Baba' ) );
		create_initial_rest_routes();
		$routes = $this->server->get_routes();
		$this->assertFalse( isset( $routes['/wp/v2/invalid-controller'] ) );
		_unregister_post_type( 'invalid-controller' );

	}

	public function test_get_item_schema() {
		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];
		$this->assertEquals( 24, count( $properties ) );
		$this->assertArrayHasKey( 'author', $properties );
		$this->assertArrayHasKey( 'comment_status', $properties );
		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'date', $properties );
		$this->assertArrayHasKey( 'date_gmt', $properties );
		$this->assertArrayHasKey( 'excerpt', $properties );
		$this->assertArrayHasKey( 'featured_media', $properties );
		$this->assertArrayHasKey( 'guid', $properties );
		$this->assertArrayHasKey( 'format', $properties );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'link', $properties );
		$this->assertArrayHasKey( 'meta', $properties );
		$this->assertArrayHasKey( 'modified', $properties );
		$this->assertArrayHasKey( 'modified_gmt', $properties );
		$this->assertArrayHasKey( 'password', $properties );
		$this->assertArrayHasKey( 'ping_status', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'sticky', $properties );
		$this->assertArrayHasKey( 'template', $properties );
		$this->assertArrayHasKey( 'title', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'tags', $properties );
		$this->assertArrayHasKey( 'categories', $properties );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39805
	 */
	public function test_get_post_view_context_properties() {
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_param( 'context', 'view' );
		$response = $this->server->dispatch( $request );
		$keys = array_keys( $response->get_data() );
		sort( $keys );

		$expected_keys = array(
			'author',
			'categories',
			'comment_status',
			'content',
			'date',
			'date_gmt',
			'excerpt',
			'featured_media',
			'format',
			'guid',
			'id',
			'link',
			'meta',
			'modified',
			'modified_gmt',
			'ping_status',
			'slug',
			'status',
			'sticky',
			'tags',
			'template',
			'title',
			'type',
		);

		$this->assertEquals( $expected_keys, $keys );
	}

	public function test_get_post_edit_context_properties() {
		wp_set_current_user( self::$editor_id );

		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_param( 'context', 'edit' );
		$response = $this->server->dispatch( $request );
		$keys = array_keys( $response->get_data() );
		sort( $keys );

		$expected_keys = array(
			'author',
			'categories',
			'comment_status',
			'content',
			'date',
			'date_gmt',
			'excerpt',
			'featured_media',
			'format',
			'guid',
			'id',
			'link',
			'meta',
			'modified',
			'modified_gmt',
			'password',
			'ping_status',
			'slug',
			'status',
			'sticky',
			'tags',
			'template',
			'title',
			'type',
		);

		$this->assertEquals( $expected_keys, $keys );
	}

	public function test_get_post_embed_context_properties() {
		$request = new WP_REST_Request( 'GET', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_param( 'context', 'embed' );
		$response = $this->server->dispatch( $request );
		$keys = array_keys( $response->get_data() );
		sort( $keys );

		$expected_keys = array(
			'author',
			'date',
			'excerpt',
			'featured_media',
			'id',
			'link',
			'slug',
			'title',
			'type',
		);

		$this->assertEquals( $expected_keys, $keys );
	}

	public function test_status_array_enum_args() {
		$request = new WP_REST_Request( 'GET', '/wp/v2' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$list_posts_args = $data['routes']['/wp/v2/posts']['endpoints'][0]['args'];
		$status_arg = $list_posts_args['status'];
		$this->assertEquals( 'array', $status_arg['type'] );
		$this->assertEquals( array(
			'type' => 'string',
			'enum' => array(
				'publish',
				'future',
				'draft',
				'pending',
				'private',
				'trash',
				'auto-draft',
				'inherit',
				'request-pending',
				'request-confirmed',
				'request-failed',
				'request-completed',
				'any',
			),
		), $status_arg['items'] );
	}

	public function test_get_additional_field_registration() {

		$schema = array(
			'type'        => 'integer',
			'description' => 'Some integer of mine',
			'enum'        => array( 1, 2, 3, 4 ),
			'context'     => array( 'view', 'edit' ),
		);

		register_rest_field( 'post', 'my_custom_int', array(
			'schema'          => $schema,
			'get_callback'    => array( $this, 'additional_field_get_callback' ),
			'update_callback' => array( $this, 'additional_field_update_callback' ),
		) );

		$request = new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' );

		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'my_custom_int', $data['schema']['properties'] );
		$this->assertEquals( $schema, $data['schema']['properties']['my_custom_int'] );

		wp_set_current_user( 1 );

		$post_id = $this->factory->post->create();

		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );

		$response = $this->server->dispatch( $request );
		$this->assertArrayHasKey( 'my_custom_int', $response->data );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post_id );
		$request->set_body_params(array(
			'my_custom_int' => 123,
		));

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 123, get_post_meta( $post_id, 'my_custom_int', true ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/posts' );
		$request->set_body_params(array(
			'my_custom_int' => 123,
			'title' => 'hello',
		));

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 123, $response->data['my_custom_int'] );

		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = array();
	}

	public function test_additional_field_update_errors() {
		$schema = array(
			'type'        => 'integer',
			'description' => 'Some integer of mine',
			'enum'        => array( 1, 2, 3, 4 ),
			'context'     => array( 'view', 'edit' ),
		);

		register_rest_field( 'post', 'my_custom_int', array(
			'schema'          => $schema,
			'get_callback'    => array( $this, 'additional_field_get_callback' ),
			'update_callback' => array( $this, 'additional_field_update_callback' ),
		) );

		wp_set_current_user( self::$editor_id );
		// Check for error on update.
		$request = new WP_REST_Request( 'POST', sprintf( '/wp/v2/posts/%d', self::$post_id ) );
		$request->set_body_params( array(
			'my_custom_int' => 'returnError',
		) );

		$response = $this->server->dispatch( $request );

		$this->assertErrorResponse( 'rest_invalid_param', $response, 400 );

		global $wp_rest_additional_fields;
		$wp_rest_additional_fields = array();
	}

	public function additional_field_get_callback( $object ) {
		return get_post_meta( $object['id'], 'my_custom_int', true );
	}

	public function additional_field_update_callback( $value, $post ) {
		if ( 'returnError' === $value ) {
			return new WP_Error( 'rest_invalid_param', 'Testing an error.', array( 'status' => 400 ) );
		}
		update_post_meta( $post->ID, 'my_custom_int', $value );
	}

	public function test_publish_action_ldo_registered() {

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$publish = wp_list_filter( $schema['links'], array( 'rel' => 'https://api.w.org/action-publish' ) );

		$this->assertCount( 1, $publish, 'LDO found on schema.' );
	}

	public function test_sticky_action_ldo_registered_for_posts() {

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$publish = wp_list_filter( $schema['links'], array( 'rel' => 'https://api.w.org/action-sticky' ) );

		$this->assertCount( 1, $publish, 'LDO found on schema.' );
	}

	public function test_sticky_action_ldo_not_registered_for_non_posts() {

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/pages' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$publish = wp_list_filter( $schema['links'], array( 'rel' => 'https://api.w.org/action-sticky' ) );

		$this->assertCount( 0, $publish, 'LDO found on schema.' );
	}

	public function test_author_action_ldo_registered_for_post_types_with_author_support() {

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$publish = wp_list_filter( $schema['links'], array( 'rel' => 'https://api.w.org/action-assign-author' ) );

		$this->assertCount( 1, $publish, 'LDO found on schema.' );
	}

	public function test_author_action_ldo_not_registered_for_post_types_without_author_support() {

		remove_post_type_support( 'post', 'author' );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$publish = wp_list_filter( $schema['links'], array( 'rel' => 'https://api.w.org/action-assign-author' ) );

		$this->assertCount( 0, $publish, 'LDO found on schema.' );
	}

	public function test_term_action_ldos_registered() {

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'OPTIONS', '/wp/v2/posts' ) );
		$data     = $response->get_data();
		$schema   = $data['schema'];

		$this->assertArrayHasKey( 'links', $schema );
		$rels = array_flip( wp_list_pluck( $schema['links'], 'rel' ) );

		$this->assertArrayHasKey( 'https://api.w.org/action-assign-categories', $rels );
		$this->assertArrayHasKey( 'https://api.w.org/action-create-categories', $rels );
		$this->assertArrayHasKey( 'https://api.w.org/action-assign-tags', $rels );
		$this->assertArrayHasKey( 'https://api.w.org/action-create-tags', $rels );

		$this->assertArrayNotHasKey( 'https://api.w.org/action-assign-post_format', $rels );
		$this->assertArrayNotHasKey( 'https://api.w.org/action-create-post_format', $rels );
		$this->assertArrayNotHasKey( 'https://api.w.org/action-assign-nav_menu', $rels );
		$this->assertArrayNotHasKey( 'https://api.w.org/action-create-nav_menu', $rels );
	}

	public function test_action_links_only_available_in_edit_context() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'view' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-publish', $links );
	}

	public function test_publish_action_link_exists_for_author() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-publish', $links );
	}

	public function test_publish_action_link_does_not_exist_for_contributor() {

		wp_set_current_user( self::$contributor_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$contributor_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-publish', $links );
	}

	public function test_sticky_action_exists_for_editor() {

		wp_set_current_user( self::$editor_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-sticky', $links );
	}

	public function test_sticky_action_does_not_exist_for_author() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-sticky', $links );
	}

	public function test_sticky_action_does_not_exist_for_non_post_posts() {

		wp_set_current_user( self::$editor_id );

		$post = self::factory()->post->create(
			array(
				'post_author' => self::$author_id,
				'post_type'   => 'page',
			)
		);
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-sticky', $links );
	}


	public function test_assign_author_action_exists_for_editor() {

		wp_set_current_user( self::$editor_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-assign-author', $links );
	}

	public function test_assign_author_action_does_not_exist_for_author() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-assign-author', $links );
	}

	public function test_assign_author_action_does_not_exist_for_post_types_without_author_support() {

		remove_post_type_support( 'post', 'author' );

		wp_set_current_user( self::$editor_id );

		$post = self::factory()->post->create();
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-assign-author', $links );
	}

	public function test_create_term_action_exists_for_editor() {

		wp_set_current_user( self::$editor_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-create-categories', $links );
		$this->assertArrayHasKey( 'https://api.w.org/action-create-tags', $links );
		$this->assertArrayNotHasKey( 'https://api.w.org/action-create-post_format', $links );
	}

	public function test_create_term_action_non_hierarchical_exists_for_author() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-create-tags', $links );
	}

	public function test_create_term_action_hierarchical_does_not_exists_for_author() {

		wp_set_current_user( self::$author_id );

		$post = self::factory()->post->create( array( 'post_author' => self::$author_id ) );
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayNotHasKey( 'https://api.w.org/action-create-categories', $links );
	}

	public function test_assign_term_action_exists_for_contributor() {

		wp_set_current_user( self::$contributor_id );

		$post = self::factory()->post->create(
			array(
				'post_author' => self::$contributor_id,
				'post_status' => 'draft',
			)
		);
		$this->assertGreaterThan( 0, $post );

		$request = new WP_REST_Request( 'GET', "/wp/v2/posts/{$post}" );
		$request->set_query_params( array( 'context' => 'edit' ) );

		$response = rest_get_server()->dispatch( $request );
		$links    = $response->get_links();

		$this->assertArrayHasKey( 'https://api.w.org/action-assign-categories', $links );
		$this->assertArrayHasKey( 'https://api.w.org/action-assign-tags', $links );
	}

	public function tearDown() {
		_unregister_post_type( 'youseeeme' );
		if ( isset( $this->attachment_id ) ) {
			$this->remove_added_uploads();
		}
		remove_filter( 'rest_pre_dispatch', array( $this, 'wpSetUpBeforeRequest' ), 10, 3 );
		remove_filter( 'posts_clauses', array( $this, 'save_posts_clauses' ), 10, 2 );
		parent::tearDown();
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

	public function filter_theme_post_templates( $post_templates ) {
		return array(
			'post-my-test-template.php' => 'My Test Template',
		);
	}
}
