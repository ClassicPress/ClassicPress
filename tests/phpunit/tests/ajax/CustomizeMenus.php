<?php
/**
 * Testing ajax customize menus functionality
 *
 * @package    ClassicPress
 * @subpackage UnitTests
 * @since      WP-4.3.0
 * @group      ajax
 */
class Tests_Ajax_CustomizeMenus extends WP_Ajax_UnitTestCase {

	/**
	 * Instance of WP_Customize_Manager which is reset for each test.
	 *
	 * @var WP_Customize_Manager
	 */
	public $wp_customize;

	/**
	 * Set up the test fixture.
	 */
	public function setUp() {
		parent::setUp();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		global $wp_customize;
		$this->wp_customize = new WP_Customize_Manager();
		$wp_customize = $this->wp_customize;
	}

	/**
	 * Helper to keep it DRY
	 *
	 * @param string $action Action.
	 */
	protected function make_ajax_call( $action ) {
		// Make the request.
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
	}

	/**
	 * Testing capabilities check for ajax_load_available_items method
	 *
	 * @dataProvider data_ajax_load_available_items_cap_check
	 *
	 * @param string $role              The role we're checking caps against.
	 * @param array  $expected_results  Expected results.
	 */
	function test_ajax_load_available_items_cap_check( $role, $expected_results ) {

		if ( 'administrator' != $role ) {
			// If we're not an admin, we should get a wp_die(-1).
			$this->setExpectedException( 'WPAjaxDieStopException' );
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );

		$_POST = array(
			'action'                => 'load-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		);

		$this->make_ajax_call( 'load-available-menu-items-customizer' );

		// If we are an admin, we should get a proper response.
		if ( 'administrator' === $role ) {
			// Get the results.
			$response = json_decode( $this->_last_response, true );

			$this->assertSame( $expected_results, $response );
		}

	}

	/**
	 * Data provider for test_ajax_load_available_items_cap_check().
	 *
	 * Provides various post_args to induce error messages in the that can be
	 * compared to the expected_results.
	 *
	 * @since WP-4.3.0
	 *
	 * @return array {
	 *     @type array {
	 *         @string string $role             The role that will test caps for.
	 *         @array  array  $expected_results The expected results from the ajax call.
	 *     }
	 * }
	 */
	function data_ajax_load_available_items_cap_check() {
		return array(
			array(
				'subscriber',
				array(),
			),
			array(
				'contributor',
				array(),
			),
			array(
				'author',
				array(),
			),
			array(
				'editor',
				array(),
			),
			array(
				'administrator',
				array(
					'success' => false,
					'data'    => 'nav_menus_missing_type_or_object_parameter',
				),
			),
		);
	}

	/**
	 * Testing the error messaging for ajax_load_available_items
	 *
	 * @dataProvider data_ajax_load_available_items_error_messages
	 *
	 * @param array $post_args POST args.
	 * @param mixed $expected_results Expected results.
	 */
	function test_ajax_load_available_items_error_messages( $post_args, $expected_results ) {

		$_POST = array_merge( array(
			'action'                => 'load-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		), $post_args );

		// Make the request.
		$this->make_ajax_call( 'load-available-menu-items-customizer' );

		// Get the results.
		$response = json_decode( $this->_last_response, true );

		$this->assertSame( $expected_results, $response );
	}

	/**
	 * Data provider for test_ajax_load_available_items_error_message().
	 *
	 * Provides various post_args to induce error messages in the that can be
	 * compared to the expected_results.
	 *
	 * @since WP-4.3.0
	 *
	 * @return array {
	 *     @type array {
	 *         @array array $post_args        The arguments that will merged with the $_POST array.
	 *         @array array $expected_results The expected results from the ajax call.
	 *     }
	 * }
	 */
	function data_ajax_load_available_items_error_messages() {
		return array(
			// Testing empty obj_type and type.
			array(
				array(
					'type'     => '',
					'object'   => '',
				),
				array(
					'success'  => false,
					'data'     => 'nav_menus_missing_type_or_object_parameter',
				),
			),
			// Testing empty obj_type.
			array(
				array(
					'type'     => 'post_type',
					'object'   => '',
				),
				array(
					'success'  => false,
					'data'     => 'nav_menus_missing_type_or_object_parameter',
				),
			),
			// Testing empty type.
			array(
				array(
					'type'     => '',
					'object'   => 'post',
				),
				array(
					'success'  => false,
					'data'     => 'nav_menus_missing_type_or_object_parameter',
				),
			),
			// Testing empty type of a bulk request.
			array(
				array(
					'item_types' => array(
						array(
							'type'     => 'post_type',
							'object'   => 'post',
						),
						array(
							'type'     => 'post_type',
							'object'   => '',
						),
					),
				),
				array(
					'success'  => false,
					'data'     => 'nav_menus_missing_type_or_object_parameter',
				),
			),
			// Testing incorrect type option.
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'invalid',
				),
				array(
					'success'  => false,
					'data'     => 'nav_menus_invalid_post_type',
				),
			),
		);
	}

	/**
	 * Testing the success status.
	 *
	 * @dataProvider data_ajax_load_available_items_success_status
	 *
	 * @param array $post_args       POST args.
	 * @param array $success_status  Success status.
	 */
	function test_ajax_load_available_items_success_status( $post_args, $success_status ) {

		$_POST = array_merge( array(
			'action'                => 'load-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		), $post_args );

		// Make the request.
		$this->make_ajax_call( 'load-available-menu-items-customizer' );

		// Get the results.
		$response = json_decode( $this->_last_response, true );
		$this->assertSame( $success_status, $response['success'] );

	}

	/**
	 * Data provider for test_ajax_load_available_items_success_status().
	 *
	 * Provides various post_args to retrieve results and compare against
	 * the success status.
	 *
	 * @since WP-4.3.0
	 *
	 * @return array {
	 *     @type array {
	 *         @type array $post_args      The arguments that will merged with the $_POST array.
	 *         @type bool  $success_status The expected success status.
	 *     }
	 * }
	 */
	function data_ajax_load_available_items_success_status() {
		return array(
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'post',
				),
				true,
			),
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'page',
				),
				true,
			),
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'custom',
				),
				false,
			),
			array(
				array(
					'type'     => 'taxonomy',
					'object'   => 'post_tag',
				),
				true,
			),
			// Testing a bulk request.
			array(
				array(
					'item_types' => array(
						array(
							'type'     => 'post_type',
							'object'   => 'post',
						),
						array(
							'type'     => 'post_type',
							'object'   => 'page',
						),
					),
				),
				true,
			),
		);
	}

	/**
	 * Testing the array structure for a single item
	 *
	 * @dataProvider data_ajax_load_available_items_structure
	 *
	 * @param array $post_args POST args.
	 */
	function test2_ajax_load_available_items_structure( $post_args ) {
		do_action( 'customize_register', $this->wp_customize );

		$expected_keys = array(
			'id',
			'title',
			'type',
			'type_label',
			'object',
			'object_id',
			'url',
		);

		// Create some terms and pages.
		self::factory()->term->create_many( 5 );
		self::factory()->post->create_many( 5, array( 'post_type' => 'page' ) );
		$auto_draft_post = $this->wp_customize->nav_menus->insert_auto_draft_post( array( 'post_title' => 'Test Auto Draft', 'post_type' => 'post' ) );
		$this->wp_customize->set_post_value( 'nav_menus_created_posts', array( $auto_draft_post->ID ) );
		$this->wp_customize->get_setting( 'nav_menus_created_posts' )->preview();

		$_POST = array_merge( array(
			'action'                => 'load-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		), $post_args );

		// Make the request.
		$this->make_ajax_call( 'load-available-menu-items-customizer' );

		// Get the results.
		$response = json_decode( $this->_last_response, true );

		$this->assertNotEmpty( current( $response['data']['items'] ) );

		// Get the second index to avoid the home page edge case.
		$first_prop = current( $response['data']['items'] );
		$test_item = $first_prop[1];

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $test_item );
			$this->assertNotEmpty( $test_item[ $key ] );
		}

		// Special test for the home page.
		if ( 'page' === $test_item['object'] ) {
			$first_prop = current( $response['data']['items'] );
			$home = $first_prop[0];
			foreach ( $expected_keys as $key ) {
				if ( 'object_id' !== $key ) {
					$this->assertArrayHasKey( $key, $home );
					if ( 'object' !== $key ) {
						$this->assertNotEmpty( $home[ $key ] );
					}
				}
			}
		} elseif ( 'post' === $test_item['object'] ) {
			$item_ids = wp_list_pluck( $response['data']['items']['post_type:post'], 'id' );
			$this->assertContains( 'post-' . $auto_draft_post->ID, $item_ids );
		}
	}

	/**
	 * Data provider for test_ajax_load_available_items_structure().
	 *
	 * Provides various post_args to return a list of items to test the array structure of.
	 *
	 * @since WP-4.3.0
	 *
	 * @return array {
	 *     @type array {
	 *         @type array $post_args The arguments that will merged with the $_POST array.
	 *     }
	 * }
	 */
	function data_ajax_load_available_items_structure() {
		return array(
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'post',
				),
			),
			array(
				array(
					'type'     => 'post_type',
					'object'   => 'page',
				),
			),
			array(
				array(
					'type'     => 'taxonomy',
					'object'   => 'post_tag',
				),
			),
		);
	}

	/**
	 * Testing the error messages for ajax_search_available_items
	 *
	 * @dataProvider data_ajax_search_available_items_caps_check
	 *
	 * @param string $role             Role.
	 * @param array  $expected_results Expected results.
	 */
	function test_ajax_search_available_items_caps_check( $role, $expected_results ) {

		if ( 'administrator' != $role ) {
			// If we're not an admin, we should get a wp_die(-1).
			$this->setExpectedException( 'WPAjaxDieStopException' );
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );

		$_POST = array(
			'action'                => 'search-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		);

		$this->make_ajax_call( 'search-available-menu-items-customizer' );

		// If we are an admin, we should get a proper response.
		if ( 'administrator' === $role ) {
			// Get the results.
			$response = json_decode( $this->_last_response, true );

			$this->assertSame( $expected_results, $response );
		}
	}

	/**
	 * Data provider for test_ajax_search_available_items_caps_check().
	 *
	 * Provides various post_args to induce error messages in the that can be
	 * compared to the expected_results.
	 *
	 * @since WP-4.3.0
	 *
	 * @todo Make this more DRY
	 *
	 * @return array {
	 *     @type array {
	 *         @string string $role             The role that will test caps for.
	 *         @array  array  $expected_results The expected results from the ajax call.
	 *     }
	 * }
	 */
	function data_ajax_search_available_items_caps_check() {
		return array(
			array(
				'subscriber',
				array(),
			),
			array(
				'contributor',
				array(),
			),
			array(
				'author',
				array(),
			),
			array(
				'editor',
				array(),
			),
			array(
				'administrator',
				array(
					'success' => false,
					'data'    => 'nav_menus_missing_search_parameter',
				),
			),
		);
	}

	/**
	 * Testing the results of various searches
	 *
	 * @dataProvider data_ajax_search_available_items_results
	 *
	 * @param array $post_args        POST args.
	 * @param array $expected_results Expected results.
	 */
	function test_ajax_search_available_items_results( $post_args, $expected_results ) {
		do_action( 'customize_register', $this->wp_customize );

		self::factory()->post->create_many( 5, array( 'post_title' => 'Test Post' ) );
		$included_auto_draft_post = $this->wp_customize->nav_menus->insert_auto_draft_post( array( 'post_title' => 'Test Included Auto Draft', 'post_type' => 'post' ) );
		$excluded_auto_draft_post = $this->wp_customize->nav_menus->insert_auto_draft_post( array( 'post_title' => 'Excluded Auto Draft', 'post_type' => 'post' ) );
		$this->wp_customize->set_post_value( 'nav_menus_created_posts', array( $included_auto_draft_post->ID, $excluded_auto_draft_post->ID ) );
		$this->wp_customize->get_setting( 'nav_menus_created_posts' )->preview();

		$_POST = array_merge( array(
			'action'                => 'search-available-menu-items-customizer',
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		), $post_args );

		$this->make_ajax_call( 'search-available-menu-items-customizer' );

		$response = json_decode( $this->_last_response, true );

		if ( isset( $post_args['search'] ) && 'test' === $post_args['search'] ) {
			$this->assertsame( true, $response['success'] );
			$this->assertSame( 6, count( $response['data']['items'] ) );
			$item_ids = wp_list_pluck( $response['data']['items'], 'id' );
			$this->assertContains( 'post-' . $included_auto_draft_post->ID, $item_ids );
			$this->assertNotContains( 'post-' . $excluded_auto_draft_post->ID, $item_ids );
		} else {
			$this->assertSame( $expected_results, $response );
		}
	}

	/**
	 * Data provider for test_ajax_search_available_items_results().
	 *
	 * Provides various post_args to test the results.
	 *
	 * @since WP-4.3.0
	 *
	 * @return array {
	 *     @type array {
	 *         @string string $post_args        The args that will be passed to ajax.
	 *         @array  array  $expected_results The expected results from the ajax call.
	 *     }
	 * }
	 */
	function data_ajax_search_available_items_results() {
		return array(
			array(
				array(),
				array(
					'success' => false,
					'data'    => 'nav_menus_missing_search_parameter',
				),
			),
			array(
				array(
					'search'  => 'all_the_things',
				),
				array(
					'success' => false,
					'data'    => array(
						'message' => 'No results found.',
					),
				),
			),
			array(
				array(
					'search'  => 'test',
				),
				array(
					'success' => true,
					array(),
				),
			),
		);
	}

	/**
	 * Testing successful ajax_insert_auto_draft_post() call.
	 *
	 * @covers WP_Customize_Nav_Menus::ajax_insert_auto_draft_post()
	 */
	function test_ajax_insert_auto_draft_post_success() {
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
			'params' => array(
				'post_type' => 'post',
				'post_title' => 'Hello World',
			),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'post_id', $response['data'] );
		$this->assertArrayHasKey( 'url', $response['data'] );
		$post = get_post( $response['data']['post_id'] );
		$this->assertEquals( 'Hello World', $post->post_title );
		$this->assertEquals( 'post', $post->post_type );
		$this->assertEquals( '', $post->post_name );
		$this->assertEquals( 'hello-world', get_post_meta( $post->ID, '_customize_draft_post_name', true ) );
		$this->assertEquals( $this->wp_customize->changeset_uuid(), get_post_meta( $post->ID, '_customize_changeset_uuid', true ) );
	}

	/**
	 * Testing unsuccessful ajax_insert_auto_draft_post() call.
	 *
	 * @covers WP_Customize_Nav_Menus::ajax_insert_auto_draft_post()
	 */
	function test_ajax_insert_auto_draft_failures() {
		// No nonce.
		$_POST = array();
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'bad_nonce', $response['data'] );

		// Bad nonce.
		$_POST = wp_slash( array(
			'customize-menus-nonce' => 'bad',
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'bad_nonce', $response['data'] );

		// Bad nonce.
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'customize_not_allowed', $response['data'] );

		// Missing params.
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'missing_params', $response['data'] );

		// insufficient_post_permissions.
		register_post_type( 'privilege', array( 'capability_type' => 'privilege' ) );
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
			'params' => array(
				'post_type' => 'privilege',
			),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'insufficient_post_permissions', $response['data'] );

		// insufficient_post_permissions.
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
			'params' => array(
				'post_type' => 'non-existent',
			),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'missing_post_type_param', $response['data'] );

		// missing_post_title.
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
			'params' => array(
				'post_type' => 'post',
				'post_title' => '    ',
			),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'missing_post_title', $response['data'] );

		// illegal_params.
		$_POST = wp_slash( array(
			'customize-menus-nonce' => wp_create_nonce( 'customize-menus' ),
			'params' => array(
				'post_type' => 'post',
				'post_title' => 'OK',
				'post_name' => 'bad',
				'post_content' => 'bad',
			),
		) );
		$this->_last_response = '';
		$this->make_ajax_call( 'customize-nav-menus-insert-auto-draft' );
		$response = json_decode( $this->_last_response, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'illegal_params', $response['data'] );
	}
}
