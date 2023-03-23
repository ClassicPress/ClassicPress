<?php

/**
 * Tests WP_Customize_Nav_Menus.
 *
 * @group customize
 */
class Test_WP_Customize_Nav_Menus extends WP_UnitTestCase {

	/**
	 * Instance of WP_Customize_Manager which is reset for each test.
	 *
	 * @var WP_Customize_Manager
	 */
	public $wp_customize;

	/**
	 * Set up a test case.
	 *
	 * @see WP_UnitTestCase_Base::set_up()
	 */
	public function set_up() {
		parent::set_up();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		global $wp_customize;
		$this->wp_customize = new WP_Customize_Manager();
		$wp_customize       = $this->wp_customize;
	}

	/**
	 * Delete the $wp_customize global when cleaning up scope.
	 */
	public function clean_up_global_scope() {
		global $wp_customize;
		$wp_customize = null;
		parent::clean_up_global_scope();
	}

	/**
	 * Filter to add custom menu item types.
	 *
	 * @param array $items Menu item types.
	 * @return array Menu item types.
	 */
	public function filter_item_types( $items ) {
		$items[] = array(
			'title'      => 'Custom',
			'type_label' => 'Custom Type',
			'type'       => 'custom_type',
			'object'     => 'custom_object',
		);

		return $items;
	}

	/**
	 * Filter to add custom menu items.
	 *
	 * @param array  $items       The menu items.
	 * @param string $object_type The object type (e.g. taxonomy).
	 * @param string $object_name The object name (e.g. category).
	 * @return array Menu items.
	 */
	public function filter_items( $items, $object_type, $object_name ) {
		$items[] = array(
			'id'         => 'custom-1',
			'title'      => 'Cool beans',
			'type'       => $object_type,
			'type_label' => 'Custom Label',
			'object'     => $object_name,
			'url'        => home_url( '/cool-beans/' ),
			'classes'    => 'custom-menu-item cool-beans',
		);

		return $items;
	}

	/**
	 * Test constructor.
	 *
	 * @see WP_Customize_Nav_Menus::__construct()
	 */
	public function test_construct() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );
		$this->assertInstanceOf( 'WP_Customize_Manager', $menus->manager );

		$this->assertTrue( add_filter( 'customize_refresh_nonces', array( $menus, 'filter_nonces' ) ) );
		$this->assertTrue( add_action( 'wp_ajax_load-available-menu-items-customizer', array( $menus, 'ajax_load_available_items' ) ) );
		$this->assertTrue( add_action( 'wp_ajax_search-available-menu-items-customizer', array( $menus, 'ajax_search_available_items' ) ) );
		$this->assertTrue( add_action( 'wp_ajax_customize-nav-menus-insert-auto-draft', array( $menus, 'ajax_insert_auto_draft_post' ) ) );
		$this->assertTrue( add_action( 'customize_controls_enqueue_scripts', array( $menus, 'enqueue_scripts' ) ) );
		$this->assertTrue( add_action( 'customize_register', array( $menus, 'customize_register' ) ) );
		$this->assertTrue( add_filter( 'customize_dynamic_setting_args', array( $menus, 'filter_dynamic_setting_args' ) ) );
		$this->assertTrue( add_filter( 'customize_dynamic_setting_class', array( $menus, 'filter_dynamic_setting_class' ) ) );
		$this->assertTrue( add_action( 'customize_controls_print_footer_scripts', array( $menus, 'print_templates' ) ) );
		$this->assertTrue( add_action( 'customize_controls_print_footer_scripts', array( $menus, 'available_items_template' ) ) );
		$this->assertTrue( add_action( 'customize_preview_init', array( $menus, 'customize_preview_init' ) ) );
		$this->assertTrue( add_action( 'customize_preview_init', array( $menus, 'make_auto_draft_status_previewable' ) ) );
		$this->assertTrue( add_action( 'customize_save_nav_menus_created_posts', array( $menus, 'save_nav_menus_created_posts' ) ) );
		$this->assertTrue( add_filter( 'customize_dynamic_partial_args', array( $menus, 'customize_dynamic_partial_args' ) ) );
	}

	/**
	 * Test that the load_available_items_query method returns a WP_Error object.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_wp_error() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Invalid post type $obj_name.
		$items = $menus->load_available_items_query( 'post_type', 'invalid' );
		$this->assertInstanceOf( 'WP_Error', $items );
		$this->assertSame( 'nav_menus_invalid_post_type', $items->get_error_code() );

		// Invalid taxonomy $obj_name.
		$items = $menus->load_available_items_query( 'taxonomy', 'invalid' );
		$this->assertInstanceOf( 'WP_Error', $items );
		$this->assertSame( 'invalid_taxonomy', $items->get_error_code() );
	}

	/**
	 * Test the load_available_items_query method maybe returns the home page item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_maybe_returns_home() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Expected menu item array.
		$expected = array(
			'id'         => 'home',
			'title'      => _x( 'Home', 'nav menu home label' ),
			'type'       => 'custom',
			'type_label' => __( 'Custom Link' ),
			'object'     => '',
			'url'        => home_url(),
		);

		// Create pages.
		self::factory()->post->create_many( 12, array( 'post_type' => 'page' ) );

		// Home is included in menu items when page is zero.
		$items = $menus->load_available_items_query( 'post_type', 'page', 0 );
		$this->assertContains( $expected, $items );

		// Home is not included in menu items when page is larger than zero.
		$items = $menus->load_available_items_query( 'post_type', 'page', 1 );
		$this->assertNotEmpty( $items );
		$this->assertNotContains( $expected, $items );
	}

	/**
	 * Test the load_available_items_query method returns post item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_post_item_with_page_number() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Create page.
		$post_id = self::factory()->post->create( array( 'post_title' => 'Post Title' ) );

		// Create pages.
		self::factory()->post->create_many( 10 );

		// Expected menu item array.
		$expected = array(
			'id'         => "post-{$post_id}",
			'title'      => 'Post Title',
			'type'       => 'post_type',
			'type_label' => 'Post',
			'object'     => 'post',
			'object_id'  => (int) $post_id,
			'url'        => get_permalink( (int) $post_id ),
		);

		// Offset the query and get the second page of menu items.
		$items = $menus->load_available_items_query( 'post_type', 'post', 1 );
		$this->assertContains( $expected, $items );
	}

	/**
	 * Test the load_available_items_query method returns page item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_page_item() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Create page.
		$page_id = self::factory()->post->create(
			array(
				'post_title' => 'Page Title',
				'post_type'  => 'page',
			)
		);

		// Expected menu item array.
		$expected = array(
			'id'         => "post-{$page_id}",
			'title'      => 'Page Title',
			'type'       => 'post_type',
			'type_label' => 'Page',
			'object'     => 'page',
			'object_id'  => (int) $page_id,
			'url'        => get_permalink( (int) $page_id ),
		);

		$items = $menus->load_available_items_query( 'post_type', 'page', 0 );
		$this->assertContains( $expected, $items );
	}

	/**
	 * Test the load_available_items_query method returns post item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_post_item() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Create post.
		$post_id = self::factory()->post->create( array( 'post_title' => 'Post Title' ) );

		// Expected menu item array.
		$expected = array(
			'id'         => "post-{$post_id}",
			'title'      => 'Post Title',
			'type'       => 'post_type',
			'type_label' => 'Post',
			'object'     => 'post',
			'object_id'  => (int) $post_id,
			'url'        => get_permalink( (int) $post_id ),
		);

		$items = $menus->load_available_items_query( 'post_type', 'post', 0 );
		$this->assertContains( $expected, $items );
	}

	/**
	 * Test the load_available_items_query method returns term item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_term_item() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Create term.
		$term_id = self::factory()->category->create( array( 'name' => 'Term Title' ) );

		// Expected menu item array.
		$expected = array(
			'id'         => "term-{$term_id}",
			'title'      => 'Term Title',
			'type'       => 'taxonomy',
			'type_label' => 'Category',
			'object'     => 'category',
			'object_id'  => (int) $term_id,
			'url'        => get_term_link( (int) $term_id, 'category' ),
		);

		$items = $menus->load_available_items_query( 'taxonomy', 'category', 0 );
		$this->assertContains( $expected, $items );
	}

	/**
	 * Test the load_available_items_query method returns custom item.
	 *
	 * @see WP_Customize_Nav_Menus::load_available_items_query()
	 */
	public function test_load_available_items_query_returns_custom_item() {
		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'filter_item_types' ) );
		add_filter( 'customize_nav_menu_available_items', array( $this, 'filter_items' ), 10, 4 );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		// Expected menu item array.
		$expected = array(
			'id'         => 'custom-1',
			'title'      => 'Cool beans',
			'type'       => 'custom_type',
			'type_label' => 'Custom Label',
			'object'     => 'custom_object',
			'url'        => home_url( '/cool-beans/' ),
			'classes'    => 'custom-menu-item cool-beans',
		);

		$items = $menus->load_available_items_query( 'custom_type', 'custom_object', 0 );
		$this->assertContains( $expected, $items );
	}

	/**
	 * Test the search_available_items_query method.
	 *
	 * @see WP_Customize_Nav_Menus::search_available_items_query()
	 */
	public function test_search_available_items_query() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );
		do_action( 'customize_register', $this->wp_customize );

		// Create posts.
		$post_ids   = array();
		$post_ids[] = self::factory()->post->create( array( 'post_title' => 'Search & Test' ) );
		$post_ids[] = self::factory()->post->create( array( 'post_title' => 'Some Other Title' ) );

		// Create terms.
		$term_ids   = array();
		$term_ids[] = self::factory()->category->create( array( 'name' => 'Dogs Are Cool' ) );
		$term_ids[] = self::factory()->category->create( array( 'name' => 'Cats Drool' ) );

		// Test empty results.
		$expected = array();
		$results  = $menus->search_available_items_query(
			array(
				'pagenum' => 1,
				's'       => 'This Does NOT Exist',
			)
		);
		$this->assertSame( $expected, $results );

		// Test posts.
		foreach ( $post_ids as $post_id ) {
			$expected = array(
				'id'         => 'post-' . $post_id,
				'title'      => html_entity_decode( get_the_title( $post_id ) ),
				'type'       => 'post_type',
				'type_label' => get_post_type_object( 'post' )->labels->singular_name,
				'object'     => 'post',
				'object_id'  => (int) $post_id,
				'url'        => get_permalink( (int) $post_id ),
			);
			wp_set_object_terms( $post_id, $term_ids, 'category' );
			$search  = $post_id === $post_ids[0] ? 'test & search' : 'other title';
			$s       = sanitize_text_field( wp_unslash( $search ) );
			$results = $menus->search_available_items_query(
				array(
					'pagenum' => 1,
					's'       => $s,
				)
			);
			$this->assertSame( $expected, $results[0] );
		}

		// Test terms.
		foreach ( $term_ids as $term_id ) {
			$term     = get_term_by( 'id', $term_id, 'category' );
			$expected = array(
				'id'         => 'term-' . $term_id,
				'title'      => $term->name,
				'type'       => 'taxonomy',
				'type_label' => get_taxonomy( 'category' )->labels->singular_name,
				'object'     => 'category',
				'object_id'  => (int) $term_id,
				'url'        => get_term_link( (int) $term_id, 'category' ),
			);
			$s        = sanitize_text_field( wp_unslash( $term->name ) );
			$results  = $menus->search_available_items_query(
				array(
					'pagenum' => 1,
					's'       => $s,
				)
			);
			$this->assertSame( $expected, $results[0] );
		}

		// Test filtered results.
		$results = $menus->search_available_items_query(
			array(
				'pagenum' => 1,
				's'       => 'cat',
			)
		);
		$this->assertCount( 2, $results ); // Category terms Cats Drool and Uncategorized.
		$count = $this->filter_count_customize_nav_menu_searched_items;
		add_filter( 'customize_nav_menu_searched_items', array( $this, 'filter_search' ), 10, 2 );
		$results = $menus->search_available_items_query(
			array(
				'pagenum' => 1,
				's'       => 'cat',
			)
		);
		$this->assertSame( $count + 1, $this->filter_count_customize_nav_menu_searched_items );
		$this->assertIsArray( $results );
		$this->assertCount( 3, $results );
		remove_filter( 'customize_nav_menu_searched_items', array( $this, 'filter_search' ), 10 );

		// Test home.
		$title   = _x( 'Home', 'nav menu home label' );
		$results = $menus->search_available_items_query(
			array(
				'pagenum' => 1,
				's'       => $title,
			)
		);
		$this->assertCount( 1, $results );
		$this->assertSame( 'home', $results[0]['id'] );
		$this->assertSame( 'custom', $results[0]['type'] );
	}

	/*
	 * Tests that the search_available_items_query method should return term items
	 * not assigned to any posts.
	 *
	 * @ticket 45298
	 */
	public function test_search_available_items_query_should_return_unassigned_term_items() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		register_taxonomy(
			'wptests_tax',
			'post',
			array(
				'labels' => array(
					'name' => 'Tests Taxonomy',
				),
			)
		);

		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
				'name'     => 'foobar',
			)
		);

		// Expected menu item array.
		$expected = array(
			'title'      => 'foobar',
			'id'         => "term-{$term_id}",
			'type'       => 'taxonomy',
			'type_label' => 'Tests Taxonomy',
			'object'     => 'wptests_tax',
			'object_id'  => (int) $term_id,
			'url'        => get_term_link( (int) $term_id, '' ),
		);

		$results = $menus->search_available_items_query(
			array(
				'pagenum' => 1,
				's'       => 'foo',
			)
		);

		$this->assertSameSets( $expected, $results[0] );
	}

	/**
	 * Count for number of times customize_nav_menu_searched_items filtered.
	 *
	 * @var int
	 */
	protected $filter_count_customize_nav_menu_searched_items = 0;

	/**
	 * Filter to search menu items.
	 *
	 * @param array $items Items.
	 * @param array $args {
	 *     Search args.
	 *
	 *     @type int    $pagenum Page number.
	 *     @type string $s       Search string.
	 * }
	 * @return array Items.
	 */
	public function filter_search( $items, $args ) {
		$this->assertIsArray( $items );
		$this->assertIsArray( $args );
		$this->assertArrayHasKey( 's', $args );
		$this->assertArrayHasKey( 'pagenum', $args );
		$this->filter_count_customize_nav_menu_searched_items += 1;

		if ( 'cat' === $args['s'] ) {
			array_unshift(
				$items,
				array(
					'id'         => 'home',
					'title'      => 'COOL CAT!',
					'type'       => 'custom',
					'type_label' => __( 'Custom Link' ),
					'object'     => '',
					'url'        => home_url( '/cool-cat' ),
				)
			);
		}
		return $items;
	}

	/**
	 * Test the enqueue method.
	 *
	 * @see WP_Customize_Nav_Menus::enqueue_scripts()
	 */
	public function test_enqueue_scripts() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );
		$menus->enqueue_scripts();
		$this->assertTrue( wp_script_is( 'customize-nav-menus' ) );

		wp_dequeue_style( 'customize-nav-menus' );
		wp_dequeue_script( 'customize-nav-menus' );
	}

	/**
	 * Test the filter_dynamic_setting_args method.
	 *
	 * @see WP_Customize_Nav_Menus::filter_dynamic_setting_args()
	 */
	public function test_filter_dynamic_setting_args() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$expected = array( 'type' => 'nav_menu_item' );
		$results  = $menus->filter_dynamic_setting_args( $this->wp_customize, 'nav_menu_item[123]' );
		$this->assertSame( $expected['type'], $results['type'] );

		$expected = array( 'type' => 'nav_menu' );
		$results  = $menus->filter_dynamic_setting_args( $this->wp_customize, 'nav_menu[123]' );
		$this->assertSame( $expected['type'], $results['type'] );
	}

	/**
	 * Test the filter_dynamic_setting_class method.
	 *
	 * @see WP_Customize_Nav_Menus::filter_dynamic_setting_class()
	 */
	public function test_filter_dynamic_setting_class() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$expected = 'WP_Customize_Nav_Menu_Item_Setting';
		$results  = $menus->filter_dynamic_setting_class( 'WP_Customize_Setting', 'nav_menu_item[123]', array( 'type' => 'nav_menu_item' ) );
		$this->assertSame( $expected, $results );

		$expected = 'WP_Customize_Nav_Menu_Setting';
		$results  = $menus->filter_dynamic_setting_class( 'WP_Customize_Setting', 'nav_menu[123]', array( 'type' => 'nav_menu' ) );
		$this->assertSame( $expected, $results );
	}

	/**
	 * Test the customize_register method.
	 *
	 * @see WP_Customize_Nav_Menus::customize_register()
	 */
	public function test_customize_register() {
		do_action( 'customize_register', $this->wp_customize );
		$menu_id = wp_create_nav_menu( 'Primary' );
		$post_id = self::factory()->post->create( array( 'post_title' => 'Hello World' ) );
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'post',
				'menu-item-object-id' => $post_id,
				'menu-item-title'     => 'Hello World',
				'menu-item-status'    => 'publish',
			)
		);
		do_action( 'customize_register', $this->wp_customize );
		$this->assertInstanceOf( 'WP_Customize_Nav_Menu_Item_Setting', $this->wp_customize->get_setting( "nav_menu_item[$item_id]" ) );
		$this->assertSame( 'Primary', $this->wp_customize->get_section( "nav_menu[$menu_id]" )->title );
		$this->assertSame( 'Hello World', $this->wp_customize->get_control( "nav_menu_item[$item_id]" )->label );

		$nav_menus_created_posts_setting = $this->wp_customize->get_setting( 'nav_menus_created_posts' );
		$this->assertInstanceOf( 'WP_Customize_Filter_Setting', $nav_menus_created_posts_setting );
		$this->assertSame( 'postMessage', $nav_menus_created_posts_setting->transport );
		$this->assertSame( array(), $nav_menus_created_posts_setting->default );
		$this->assertSame( array( $this->wp_customize->nav_menus, 'sanitize_nav_menus_created_posts' ), $nav_menus_created_posts_setting->sanitize_callback );
	}

	/**
	 * Test the intval_base10 method.
	 *
	 * @see WP_Customize_Nav_Menus::intval_base10()
	 */
	public function test_intval_base10() {

		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$this->assertSame( 2, $menus->intval_base10( 2 ) );
		$this->assertSame( 4, $menus->intval_base10( 4.1 ) );
		$this->assertSame( 4, $menus->intval_base10( '4' ) );
		$this->assertSame( 4, $menus->intval_base10( '04' ) );
		$this->assertSame( 42, $menus->intval_base10( +42 ) );
		$this->assertSame( -42, $menus->intval_base10( -42 ) );
		$this->assertSame( 26, $menus->intval_base10( 0x1A ) );
		$this->assertSame( 0, $menus->intval_base10( array() ) );
	}

	/**
	 * Test the available_item_types method.
	 *
	 * @see WP_Customize_Nav_Menus::available_item_types()
	 */
	public function test_available_item_types() {

		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$expected = array(
			array(
				'title'      => 'Posts',
				'type_label' => __( 'Post' ),
				'type'       => 'post_type',
				'object'     => 'post',
			),
			array(
				'title'      => 'Pages',
				'type_label' => __( 'Page' ),
				'type'       => 'post_type',
				'object'     => 'page',
			),
			array(
				'title'      => 'Categories',
				'type_label' => __( 'Category' ),
				'type'       => 'taxonomy',
				'object'     => 'category',
			),
			array(
				'title'      => 'Tags',
				'type_label' => __( 'Tag' ),
				'type'       => 'taxonomy',
				'object'     => 'post_tag',
			),
		);

		if ( current_theme_supports( 'post-formats' ) ) {
			$expected[] = array(
				'title'      => 'Format',
				'type_label' => __( 'Format' ),
				'type'       => 'taxonomy',
				'object'     => 'post_format',
			);
		}

		$this->assertSame( $expected, $menus->available_item_types() );

		register_taxonomy( 'wptests_tax', array( 'post' ), array( 'labels' => array( 'name' => 'Foo' ) ) );
		$expected[] = array(
			'title'      => 'Foo',
			'type_label' => 'Foo',
			'type'       => 'taxonomy',
			'object'     => 'wptests_tax',
		);

		$this->assertSame( $expected, $menus->available_item_types() );

		$expected[] = array(
			'title'      => 'Custom',
			'type_label' => 'Custom Type',
			'type'       => 'custom_type',
			'object'     => 'custom_object',
		);

		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'filter_item_types' ) );
		$this->assertSame( $expected, $menus->available_item_types() );
		remove_filter( 'customize_nav_menu_available_item_types', array( $this, 'filter_item_types' ) );

	}

	/**
	 * Test insert_auto_draft_post method.
	 *
	 * @covers WP_Customize_Nav_Menus::insert_auto_draft_post
	 */
	public function test_insert_auto_draft_post() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$r = $menus->insert_auto_draft_post( array() );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'unknown_post_type', $r->get_error_code() );

		// Non-existent post types allowed as of #39610.
		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Non-existent',
				'post_type'  => 'nonexistent',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		$this->assertSame( $this->wp_customize->changeset_uuid(), get_post_meta( $r->ID, '_customize_changeset_uuid', true ) );

		$r = $menus->insert_auto_draft_post( array( 'post_type' => 'post' ) );
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'empty_title', $r->get_error_code() );

		$r = $menus->insert_auto_draft_post(
			array(
				'post_status' => 'publish',
				'post_title'  => 'Bad',
				'post_type'   => 'post',
			)
		);
		$this->assertInstanceOf( 'WP_Error', $r );
		$this->assertSame( 'status_forbidden', $r->get_error_code() );

		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Hello World',
				'post_type'  => 'post',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		$this->assertSame( 'Hello World', $r->post_title );
		$this->assertSame( '', $r->post_name );
		$this->assertSame( 'hello-world', get_post_meta( $r->ID, '_customize_draft_post_name', true ) );
		$this->assertSame( $this->wp_customize->changeset_uuid(), get_post_meta( $r->ID, '_customize_changeset_uuid', true ) );
		$this->assertSame( 'post', $r->post_type );

		$r = $menus->insert_auto_draft_post(
			array(
				'post_title'   => 'Hello World',
				'post_type'    => 'post',
				'post_name'    => 'greetings-world',
				'post_content' => 'Hi World',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		$this->assertSame( 'Hello World', $r->post_title );
		$this->assertSame( 'post', $r->post_type );
		$this->assertSame( '', $r->post_name );
		$this->assertSame( 'greetings-world', get_post_meta( $r->ID, '_customize_draft_post_name', true ) );
		$this->assertSame( $this->wp_customize->changeset_uuid(), get_post_meta( $r->ID, '_customize_changeset_uuid', true ) );
		$this->assertSame( 'Hi World', $r->post_content );
	}

	/**
	 * Test the print_templates method.
	 *
	 * @see WP_Customize_Nav_Menus::print_templates()
	 */
	public function test_print_templates() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		ob_start();
		$menus->print_templates();
		$template = ob_get_clean();

		$expected = sprintf(
			'<button type="button" class="menus-move-up">%1$s</button><button type="button" class="menus-move-down">%2$s</button><button type="button" class="menus-move-left">%3$s</button><button type="button" class="menus-move-right">%4$s</button>',
			esc_html( 'Move up' ),
			esc_html( 'Move down' ),
			esc_html( 'Move one level up' ),
			esc_html( 'Move one level down' )
		);

		$this->assertStringContainsString( $expected, $template );
	}

	/**
	 * Test the available_items_template method.
	 *
	 * @see WP_Customize_Nav_Menus::available_items_template()
	 */
	public function test_available_items_template() {
		add_filter( 'customize_nav_menu_available_item_types', array( $this, 'filter_item_types' ) );
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		ob_start();
		$menus->available_items_template();
		$template = ob_get_clean();

		$expected = sprintf( 'Customizing &#9656; %s', esc_html( $this->wp_customize->get_panel( 'nav_menus' )->title ) );

		$this->assertStringContainsString( $expected, $template );

		$post_types = get_post_types( array( 'show_in_nav_menus' => true ), 'object' );
		if ( $post_types ) {
			foreach ( $post_types as $type ) {
				$this->assertStringContainsString( 'available-menu-items-post_type-' . esc_attr( $type->name ), $template );
				$this->assertMatchesRegularExpression( '#<h4 class="accordion-section-title".*>\s*' . esc_html( $type->labels->name ) . '#', $template );
				$this->assertStringContainsString( 'data-type="post_type"', $template );
				$this->assertStringContainsString( 'data-object="' . esc_attr( $type->name ) . '"', $template );
				$this->assertStringContainsString( 'data-type_label="' . esc_attr( $type->labels->singular_name ) . '"', $template );
			}
		}

		$taxonomies = get_taxonomies( array( 'show_in_nav_menus' => true ), 'object' );
		if ( $taxonomies ) {
			foreach ( $taxonomies as $tax ) {
				$this->assertStringContainsString( 'available-menu-items-taxonomy-' . esc_attr( $tax->name ), $template );
				$this->assertMatchesRegularExpression( '#<h4 class="accordion-section-title".*>\s*' . esc_html( $tax->labels->name ) . '#', $template );
				$this->assertStringContainsString( 'data-type="taxonomy"', $template );
				$this->assertStringContainsString( 'data-object="' . esc_attr( $tax->name ) . '"', $template );
				$this->assertStringContainsString( 'data-type_label="' . esc_attr( $tax->labels->singular_name ) . '"', $template );
			}
		}

		$this->assertStringContainsString( 'available-menu-items-custom_type', $template );
		$this->assertMatchesRegularExpression( '#<h4 class="accordion-section-title".*>\s*Custom#', $template );
		$this->assertStringContainsString( 'data-type="custom_type"', $template );
		$this->assertStringContainsString( 'data-object="custom_object"', $template );
		$this->assertStringContainsString( 'data-type_label="Custom Type"', $template );
	}

	/**
	 * Test WP_Customize_Nav_Menus::customize_dynamic_partial_args().
	 *
	 * @see WP_Customize_Nav_Menus::customize_dynamic_partial_args()
	 */
	public function test_customize_dynamic_partial_args() {
		do_action( 'customize_register', $this->wp_customize );

		$args = apply_filters( 'customize_dynamic_partial_args', false, 'nav_menu_instance[68b329da9893e34099c7d8ad5cb9c940]' );
		$this->assertIsArray( $args );
		$this->assertSame( 'nav_menu_instance', $args['type'] );
		$this->assertSame( array( $this->wp_customize->nav_menus, 'render_nav_menu_partial' ), $args['render_callback'] );
		$this->assertTrue( $args['container_inclusive'] );

		$args = apply_filters( 'customize_dynamic_partial_args', array( 'fallback_refresh' => false ), 'nav_menu_instance[4099c7d8ad5cb9c94068b329da9893e3]' );
		$this->assertIsArray( $args );
		$this->assertSame( 'nav_menu_instance', $args['type'] );
		$this->assertSame( array( $this->wp_customize->nav_menus, 'render_nav_menu_partial' ), $args['render_callback'] );
		$this->assertTrue( $args['container_inclusive'] );
		$this->assertFalse( $args['fallback_refresh'] );
	}

	/**
	 * Test the customize_preview_init method.
	 *
	 * @see WP_Customize_Nav_Menus::customize_preview_init()
	 */
	public function test_customize_preview_init() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$menus->customize_preview_init();
		$this->assertSame( 10, has_action( 'wp_enqueue_scripts', array( $menus, 'customize_preview_enqueue_deps' ) ) );
		$this->assertSame( 1000, has_filter( 'wp_nav_menu_args', array( $menus, 'filter_wp_nav_menu_args' ) ) );
		$this->assertSame( 10, has_filter( 'wp_nav_menu', array( $menus, 'filter_wp_nav_menu' ) ) );
	}

	/**
	 * Test make_auto_draft_status_previewable.
	 *
	 * @covers WP_Customize_Nav_Menus::make_auto_draft_status_previewable
	 */
	public function test_make_auto_draft_status_previewable() {
		global $wp_post_statuses;
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );
		$menus->make_auto_draft_status_previewable();
		$this->assertTrue( $wp_post_statuses['auto-draft']->protected );
	}

	/**
	 * Test sanitize_nav_menus_created_posts.
	 *
	 * @covers WP_Customize_Nav_Menus::sanitize_nav_menus_created_posts
	 */
	public function test_sanitize_nav_menus_created_posts() {
		$menus                 = new WP_Customize_Nav_Menus( $this->wp_customize );
		$contributor_user_id   = self::factory()->user->create( array( 'role' => 'contributor' ) );
		$author_user_id        = self::factory()->user->create( array( 'role' => 'author' ) );
		$administrator_user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$contributor_post_id   = self::factory()->post->create(
			array(
				'post_status' => 'auto-draft',
				'post_title'  => 'Contributor Post',
				'post_type'   => 'post',
				'post_author' => $contributor_user_id,
			)
		);
		$author_post_id        = self::factory()->post->create(
			array(
				'post_status' => 'auto-draft',
				'post_title'  => 'Author Post',
				'post_type'   => 'post',
				'post_author' => $author_user_id,
			)
		);
		$administrator_post_id = self::factory()->post->create(
			array(
				'post_status' => 'auto-draft',
				'post_title'  => 'Admin Post',
				'post_type'   => 'post',
				'post_author' => $administrator_user_id,
			)
		);

		$draft_post_id = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'post_title'  => 'Draft',
				'post_author' => $administrator_user_id,
			)
		);

		$private_post_id = self::factory()->post->create(
			array(
				'post_status' => 'private',
				'post_title'  => 'Private',
				'post_author' => $administrator_user_id,
			)
		);

		$value = array(
			'bad',
			$contributor_post_id,
			$author_post_id,
			$administrator_post_id,
			$draft_post_id,
			$private_post_id,
		);

		wp_set_current_user( $contributor_user_id );
		$sanitized = $menus->sanitize_nav_menus_created_posts( $value );
		$this->assertSame( array(), $sanitized );

		wp_set_current_user( $author_user_id );
		$sanitized = $menus->sanitize_nav_menus_created_posts( $value );
		$this->assertSame( array( $author_post_id ), $sanitized );

		wp_set_current_user( $administrator_user_id );
		$sanitized = $menus->sanitize_nav_menus_created_posts( $value );
		$this->assertSame( array( $contributor_post_id, $author_post_id, $administrator_post_id, $draft_post_id ), $sanitized );
	}

	/**
	 * Test save_nav_menus_created_posts.
	 *
	 * @covers WP_Customize_Nav_Menus::save_nav_menus_created_posts
	 */
	public function test_save_nav_menus_created_posts() {
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );
		do_action( 'customize_register', $this->wp_customize );

		$post_ids = array();

		// Auto-draft.
		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Auto Draft',
				'post_type'  => 'post',
				'post_name'  => 'auto-draft-1',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		$post_ids[] = $r->ID;

		// Draft.
		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Draft',
				'post_type'  => 'post',
				'post_name'  => 'auto-draft-2',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		wp_update_post(
			array(
				'ID'          => $r->ID,
				'post_status' => 'draft',
			)
		);
		$post_ids[] = $r->ID;

		$drafted_post_ids = $post_ids;

		// Private (this will exclude it from being considered a stub).
		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Private',
				'post_type'  => 'post',
				'post_name'  => 'auto-draft-3',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		wp_update_post(
			array(
				'ID'          => $r->ID,
				'post_status' => 'private',
			)
		);
		$post_ids[]      = $r->ID;
		$private_post_id = $r->ID;

		// Trashed (this will exclude it from being considered a stub).
		$r = $menus->insert_auto_draft_post(
			array(
				'post_title' => 'Trash',
				'post_type'  => 'post',
				'post_name'  => 'auto-draft-4',
			)
		);
		$this->assertInstanceOf( 'WP_Post', $r );
		wp_trash_post( $r->ID );
		$post_ids[]      = $r->ID;
		$trashed_post_id = $r->ID;

		$pre_published_post_id = self::factory()->post->create( array( 'post_status' => 'publish' ) );

		$setting_id = 'nav_menus_created_posts';
		$this->wp_customize->set_post_value( $setting_id, array_merge( $post_ids, array( $pre_published_post_id ) ) );
		$setting = $this->wp_customize->get_setting( $setting_id );
		$this->assertInstanceOf( 'WP_Customize_Filter_Setting', $setting );
		$this->assertSame( array( $menus, 'sanitize_nav_menus_created_posts' ), $setting->sanitize_callback );
		$this->assertSame( $drafted_post_ids, $setting->post_value() );
		$this->assertArrayNotHasKey( $private_post_id, $post_ids );
		$this->assertArrayNotHasKey( $trashed_post_id, $post_ids );

		$this->assertSame( 'auto-draft', get_post_status( $drafted_post_ids[0] ) );
		$this->assertSame( 'draft', get_post_status( $drafted_post_ids[1] ) );
		foreach ( $drafted_post_ids as $post_id ) {
			$this->assertEmpty( get_post( $post_id )->post_name );
			$this->assertNotEmpty( get_post_meta( $post_id, '_customize_draft_post_name', true ) );
		}

		$save_action_count = did_action( 'customize_save_nav_menus_created_posts' );
		$setting->save();
		$this->assertSame( $save_action_count + 1, did_action( 'customize_save_nav_menus_created_posts' ) );
		foreach ( $drafted_post_ids as $post_id ) {
			$this->assertSame( 'publish', get_post_status( $post_id ) );
			$this->assertMatchesRegularExpression( '/^auto-draft-\d+$/', get_post( $post_id )->post_name );
			$this->assertEmpty( get_post_meta( $post_id, '_customize_draft_post_name', true ) );
		}

		$this->assertSame( 'private', get_post_status( $private_post_id ) );
		$this->assertSame( 'trash', get_post_status( $trashed_post_id ) );

		// Ensure that unique slugs were assigned.
		$posts      = array_map( 'get_post', $drafted_post_ids );
		$post_names = wp_list_pluck( $posts, 'post_name' );
		$this->assertSameSets( $post_names, array_unique( $post_names ) );
	}

	/**
	 * Test the filter_wp_nav_menu_args method.
	 *
	 * @see WP_Customize_Nav_Menus::filter_wp_nav_menu_args()
	 */
	public function test_filter_wp_nav_menu_args() {
		do_action( 'customize_register', $this->wp_customize );
		$menus   = $this->wp_customize->nav_menus;
		$menu_id = wp_create_nav_menu( 'Foo' );

		$results = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'menu'        => $menu_id,
				'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
		$this->assertArrayHasKey( 'customize_preview_nav_menus_args', $results );
		$this->assertTrue( $results['can_partial_refresh'] );

		$results = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => false,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => new Walker_Nav_Menu(),
				'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
		$this->assertFalse( $results['can_partial_refresh'] );
		$this->assertArrayHasKey( 'customize_preview_nav_menus_args', $results );
		$this->assertSame( 'wp_page_menu', $results['fallback_cb'] );

		$nav_menu_term = get_term( wp_create_nav_menu( 'Bar' ) );
		$results       = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'menu'        => $nav_menu_term,
				'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
		$this->assertTrue( $results['can_partial_refresh'] );
		$this->assertArrayHasKey( 'customize_preview_nav_menus_args', $results );
		$this->assertSame( $nav_menu_term->term_id, $results['customize_preview_nav_menus_args']['menu'] );

		$results = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'menu'        => $menu_id,
				'container'   => 'div',
				'items_wrap'  => '%3$s',
			)
		);
		$this->assertTrue( $results['can_partial_refresh'] );

		$results = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'menu'        => $menu_id,
				'container'   => false,
				'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);
		$this->assertTrue( $results['can_partial_refresh'] );

		$results = $menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'menu'        => $menu_id,
				'container'   => false,
				'items_wrap'  => '%3$s',
			)
		);
		$this->assertFalse( $results['can_partial_refresh'] );
	}

	/**
	 * Test the filter_wp_nav_menu method.
	 *
	 * @covers WP_Customize_Nav_Menus::filter_wp_nav_menu
	 * @covers WP_Customize_Nav_Menus::filter_wp_nav_menu_args
	 */
	public function test_filter_wp_nav_menu() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$original_args = array(
			'echo'        => true,
			'menu'        => wp_create_nav_menu( 'Foo' ),
			'fallback_cb' => 'wp_page_menu',
			'walker'      => '',
			'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		);

		// Add global namespace prefix to check #41488.
		$original_args['fallback_cb'] = '\\' . $original_args['fallback_cb'];

		$args = $menus->filter_wp_nav_menu_args( $original_args );

		ob_start();
		wp_nav_menu( $args );
		$nav_menu_content = ob_get_clean();

		$result = $menus->filter_wp_nav_menu( $nav_menu_content, (object) $args );

		$this->assertStringContainsString( sprintf( ' data-customize-partial-id="nav_menu_instance[%s]"', $args['customize_preview_nav_menus_args']['args_hmac'] ), $result );
		$this->assertStringContainsString( ' data-customize-partial-type="nav_menu_instance"', $result );
		$this->assertTrue( (bool) preg_match( '/data-customize-partial-placement-context="(.+?)"/', $result, $matches ) );
		$context = json_decode( html_entity_decode( $matches[1] ), true );

		foreach ( $original_args as $key => $value ) {
			$this->assertArrayHasKey( $key, $context );
			$this->assertSame( $value, $context[ $key ] );
		}

		$this->assertTrue( $context['can_partial_refresh'] );
	}

	/**
	 * Test the customize_preview_enqueue_deps method.
	 *
	 * @see WP_Customize_Nav_Menus::customize_preview_enqueue_deps()
	 */
	public function test_customize_preview_enqueue_deps() {
		do_action( 'customize_register', $this->wp_customize );
		$menus = new WP_Customize_Nav_Menus( $this->wp_customize );

		$menus->customize_preview_enqueue_deps();

		$this->assertTrue( wp_script_is( 'customize-preview-nav-menus' ) );
	}

	/**
	 * Test WP_Customize_Nav_Menus::export_preview_data() method.
	 *
	 * @see WP_Customize_Nav_Menus::export_preview_data()
	 */
	public function test_export_preview_data() {
		ob_start();
		$this->wp_customize->nav_menus->export_preview_data();
		$html = ob_get_clean();
		$this->assertTrue( (bool) preg_match( '/_wpCustomizePreviewNavMenusExports = ({.+})/s', $html, $matches ) );
		$exported_data = json_decode( $matches[1], true );
		$this->assertArrayHasKey( 'navMenuInstanceArgs', $exported_data );
	}

	/**
	 * Test WP_Customize_Nav_Menus::render_nav_menu_partial() method.
	 *
	 * @see WP_Customize_Nav_Menus::render_nav_menu_partial()
	 */
	public function test_render_nav_menu_partial() {
		$this->wp_customize->nav_menus->customize_preview_init();

		$menu = wp_create_nav_menu( 'Foo' );
		wp_update_nav_menu_item(
			$menu,
			0,
			array(
				'menu-item-type'   => 'custom',
				'menu-item-title'  => 'WordPress.org',
				'menu-item-url'    => 'https://wordpress.org',
				'menu-item-status' => 'publish',
			)
		);

		$nav_menu_args = $this->wp_customize->nav_menus->filter_wp_nav_menu_args(
			array(
				'echo'        => true,
				'menu'        => $menu,
				'fallback_cb' => 'wp_page_menu',
				'walker'      => '',
				'items_wrap'  => '<ul id="%1$s" class="%2$s">%3$s</ul>',
			)
		);

		$partial_id = sprintf( 'nav_menu_instance[%s]', $nav_menu_args['customize_preview_nav_menus_args']['args_hmac'] );
		$partials   = $this->wp_customize->selective_refresh->add_dynamic_partials( array( $partial_id ) );
		$this->assertNotEmpty( $partials );
		$partial = array_shift( $partials );
		$this->assertSame( $partial_id, $partial->id );

		$missing_args_hmac_args = array_merge(
			$nav_menu_args['customize_preview_nav_menus_args'],
			array( 'args_hmac' => null )
		);
		$this->assertFalse( $partial->render( $missing_args_hmac_args ) );

		$args_hmac_mismatch_args = array_merge(
			$nav_menu_args['customize_preview_nav_menus_args'],
			array( 'args_hmac' => strrev( $nav_menu_args['customize_preview_nav_menus_args']['args_hmac'] ) )
		);
		$this->assertFalse( $partial->render( $args_hmac_mismatch_args ) );

		$rendered = $partial->render( $nav_menu_args['customize_preview_nav_menus_args'] );
		$this->assertStringContainsString( 'data-customize-partial-type="nav_menu_instance"', $rendered );
		$this->assertStringContainsString( 'WordPress.org', $rendered );
	}
}
