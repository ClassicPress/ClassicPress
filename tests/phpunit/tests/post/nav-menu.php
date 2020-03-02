<?php
/**
 * @group post
 * @group navmenus
 */
class Test_Nav_Menus extends WP_UnitTestCase {
	/**
	 * @var int
	 */
	public $menu_id;

	function setUp() {
		parent::setUp();

		$this->menu_id = wp_create_nav_menu( rand_str() );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/32464
	 */
	public function test_wp_nav_menu_empty_container() {
		$tag_id = self::factory()->tag->create();

		wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'post_tag',
			'menu-item-object-id' => $tag_id,
			'menu-item-status' => 'publish'
		) );

		$menu = wp_nav_menu( array(
			'echo' => false,
			'container' => '',
			'menu' => $this->menu_id
		) );

		$this->assertEquals( 0, strpos( $menu, '<ul' ) );
	}

	function test_wp_get_associated_nav_menu_items() {
		$tag_id = self::factory()->tag->create();
		$cat_id = self::factory()->category->create();
		$post_id = self::factory()->post->create();
		$post_2_id = self::factory()->post->create();
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$tag_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'post_tag',
			'menu-item-object-id' => $tag_id,
			'menu-item-status' => 'publish'
		) );

		$cat_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'category',
			'menu-item-object-id' => $cat_id,
			'menu-item-status' => 'publish'
		) );

		$post_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'post',
			'menu-item-object-id' => $post_id,
			'menu-item-status' => 'publish'
		) );

		// Item without menu-item-object arg
		$post_2_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object-id' => $post_2_id,
			'menu-item-status' => 'publish'
		) );

		$page_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-status' => 'publish'
		) );

		$tag_items = wp_get_associated_nav_menu_items( $tag_id, 'taxonomy', 'post_tag' );
		$this->assertEqualSets( array( $tag_insert ), $tag_items );
		$cat_items = wp_get_associated_nav_menu_items( $cat_id, 'taxonomy', 'category' );
		$this->assertEqualSets( array( $cat_insert ), $cat_items );
		$post_items = wp_get_associated_nav_menu_items( $post_id );
		$this->assertEqualSets( array( $post_insert ), $post_items );
		$post_2_items = wp_get_associated_nav_menu_items( $post_2_id );
		$this->assertEqualSets( array( $post_2_insert ), $post_2_items );
		$page_items = wp_get_associated_nav_menu_items( $page_id );
		$this->assertEqualSets( array( $page_insert ), $page_items );

		wp_delete_term( $tag_id, 'post_tag' );
		$tag_items = wp_get_associated_nav_menu_items( $tag_id, 'taxonomy', 'post_tag' );
		$this->assertEqualSets( array(), $tag_items );

		wp_delete_term( $cat_id, 'category' );
		$cat_items = wp_get_associated_nav_menu_items( $cat_id, 'taxonomy', 'category' );
		$this->assertEqualSets( array(), $cat_items );

		wp_delete_post( $post_id, true );
		$post_items = wp_get_associated_nav_menu_items( $post_id );
		$this->assertEqualSets( array(), $post_items );

		wp_delete_post( $post_2_id, true );
		$post_2_items = wp_get_associated_nav_menu_items( $post_2_id );
		$this->assertEqualSets( array(), $post_2_items );

		wp_delete_post( $page_id, true );
		$page_items = wp_get_associated_nav_menu_items( $page_id );
		$this->assertEqualSets( array(), $page_items );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/27113
	 */
	function test_orphan_nav_menu_item() {

		// Create an orphan nav menu item
		$custom_item_id = wp_update_nav_menu_item( 0, 0, array(
			'menu-item-type'      => 'custom',
			'menu-item-title'     => 'Wordpress.org',
			'menu-item-link'      => 'http://wordpress.org',
			'menu-item-status'    => 'publish'
		) );

		// Confirm it saved properly
		$custom_item = wp_setup_nav_menu_item( get_post( $custom_item_id ) );
		$this->assertEquals( 'Wordpress.org', $custom_item->title );

		// Update the orphan with an associated nav menu
		wp_update_nav_menu_item( $this->menu_id, $custom_item_id, array(
			'menu-item-title'     => 'WordPress.org',
			) );
		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		$custom_item = wp_filter_object_list( $menu_items, array( 'db_id' => $custom_item_id ) );
		$custom_item = array_pop( $custom_item );
		$this->assertEquals( 'WordPress.org', $custom_item->title );

	}

	public function test_wp_get_nav_menu_items_with_taxonomy_term() {
		register_taxonomy( 'wptests_tax', 'post', array( 'hierarchical' => true ) );
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax' ) );
		$child_terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax', 'parent' => $t ) );

		$term_menu_item = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'wptests_tax',
			'menu-item-object-id' => $t,
			'menu-item-status' => 'publish'
		) );

		$term = get_term( $t, 'wptests_tax' );

		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		$this->assertSame( $term->name, $menu_items[0]->title );
		$this->assertEquals( $t, $menu_items[0]->object_id );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/13910
	 */
	function test_wp_get_nav_menu_name() {
		// Register a nav menu location.
		register_nav_menu( 'primary', 'Primary Navigation' );

		// Create a menu with a title.
		$menu = wp_create_nav_menu( 'My Menu' );

		// Assign the menu to the `primary` location.
		$locations = get_nav_menu_locations();
		$menu_obj = wp_get_nav_menu_object( $menu );
		$locations['primary'] = $menu_obj->term_id;
		set_theme_mod( 'nav_menu_locations', $locations );

		$this->assertEquals( 'My Menu', wp_get_nav_menu_name( 'primary' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/29460
	 */
	function test_orderby_name_by_default() {
		// We are going to create a random number of menus (min 2, max 10)
		$menus_no = rand( 2, 10 );

		for ( $i = 0; $i <= $menus_no; $i++ ) {
			wp_create_nav_menu( rand_str() );
		}

		// This is the expected array of menu names
		$expected_nav_menus_names = wp_list_pluck(
			get_terms( 'nav_menu',  array( 'hide_empty' => false, 'orderby' => 'name' ) ),
			'name'
		);

		// And this is what we got when calling wp_get_nav_menus()
		$nav_menus_names = wp_list_pluck( wp_get_nav_menus(), 'name' );

		$this->assertEquals( $nav_menus_names, $expected_nav_menus_names );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35324
	 */
	function test_wp_setup_nav_menu_item_for_post_type_archive() {

		$post_type_slug = rand_str( 12 );
		$post_type_description = rand_str();
		register_post_type( $post_type_slug ,array(
			'public' => true,
			'has_archive' => true,
			'description' => $post_type_description,
			'label' => $post_type_slug
		));

		$post_type_archive_item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type_archive',
			'menu-item-object' => $post_type_slug,
			'menu-item-description' => $post_type_description,
			'menu-item-status' => 'publish'
		) );
		$post_type_archive_item = wp_setup_nav_menu_item( get_post( $post_type_archive_item_id ) );

		$this->assertEquals( $post_type_slug , $post_type_archive_item->title );
		$this->assertEquals( $post_type_description , $post_type_archive_item->description );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35324
	 */
	function test_wp_setup_nav_menu_item_for_post_type_archive_no_description() {

		$post_type_slug = rand_str( 12 );
		$post_type_description = '';
		register_post_type( $post_type_slug ,array(
			'public' => true,
			'has_archive' => true,
			'label' => $post_type_slug
		));

		$post_type_archive_item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type_archive',
			'menu-item-object' => $post_type_slug,
			'menu-item-status' => 'publish'
		) );
		$post_type_archive_item = wp_setup_nav_menu_item( get_post( $post_type_archive_item_id ) );

		$this->assertEquals( $post_type_slug , $post_type_archive_item->title );
		$this->assertEquals( $post_type_description , $post_type_archive_item->description ); //fail!!!
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35324
	 */
	function test_wp_setup_nav_menu_item_for_post_type_archive_custom_description() {

		$post_type_slug = rand_str( 12 );
		$post_type_description = rand_str();
		register_post_type( $post_type_slug ,array(
			'public' => true,
			'has_archive' => true,
			'description' => $post_type_description,
			'label' => $post_type_slug
		));

		$menu_item_description = rand_str();

		$post_type_archive_item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type_archive',
			'menu-item-object' => $post_type_slug,
			'menu-item-description' => $menu_item_description,
			'menu-item-status' => 'publish'
		) );
		$post_type_archive_item = wp_setup_nav_menu_item( get_post( $post_type_archive_item_id ) );

		$this->assertEquals( $post_type_slug , $post_type_archive_item->title );
		$this->assertEquals( $menu_item_description , $post_type_archive_item->description );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35324
	 */
	function test_wp_setup_nav_menu_item_for_unknown_post_type_archive_no_description() {

		$post_type_slug = rand_str( 12 );

		$post_type_archive_item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type'   => 'post_type_archive',
			'menu-item-object' => $post_type_slug,
			'menu-item-status' => 'publish'
		) );
		$post_type_archive_item = wp_setup_nav_menu_item( get_post( $post_type_archive_item_id ) );

		$this->assertEmpty( $post_type_archive_item->description );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/19038
	 */
	function test_wp_setup_nav_menu_item_for_trashed_post() {
		$post_id = self::factory()->post->create( array(
			'post_status' => 'trash',
		) );

		$menu_item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'post',
			'menu-item-object-id' => $post_id,
			'menu-item-status'    => 'publish',
		) );

		$menu_item = wp_setup_nav_menu_item( get_post( $menu_item_id ) );

		$this->assertTrue( ! _is_valid_nav_menu_item( $menu_item ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35206
	 */
	function test_wp_nav_menu_whitespace_options() {
		$post_id1 = self::factory()->post->create();
		$post_id2 = self::factory()->post->create();
		$post_id3 = self::factory()->post->create();
		$post_id4 = self::factory()->post->create();

		$post_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'post',
			'menu-item-object-id' => $post_id1,
			'menu-item-status' => 'publish'
		) );

		$post_inser2 = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'post',
			'menu-item-object-id' => $post_id2,
			'menu-item-status' => 'publish'
		) );

		$post_insert3 = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'post',
			'menu-item-parent-id' => $post_insert,
			'menu-item-object-id' => $post_id3,
			'menu-item-status' => 'publish'
		) );

		$post_insert4 = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'post',
			'menu-item-parent-id' => $post_insert,
			'menu-item-object-id' => $post_id4,
			'menu-item-status' => 'publish'
		) );

		// No whitespace suppression.
		$menu = wp_nav_menu( array(
			'echo' => false,
			'menu' => $this->menu_id,
		) );

		// The markup should include whitespace between <li>s
		$this->assertRegExp( '/\s<li.*>|<\/li>\s/U', $menu );
		$this->assertNotRegExp( '/<\/li><li.*>/U', $menu );


		// Whitepsace suppressed.
		$menu = wp_nav_menu( array(
			'echo'         => false,
			'item_spacing' => 'discard',
			'menu'         => $this->menu_id,
		) );

		// The markup should not include whitespace around <li>s
		$this->assertNotRegExp( '/\s<li.*>|<\/li>\s/U', $menu );
		$this->assertRegExp( '/><li.*>|<\/li></U', $menu );
	}

	/*
	 * Confirm `wp_nav_menu()` and `Walker_Nav_Menu` passes an $args object to filters.
	 *
	 * `wp_nav_menu()` is unique in that it uses an $args object rather than an array.
	 * This has been the case for some time and should be maintained for reasons of
	 * backward compatibility.
	 *
	 * @see https://core.trac.wordpress.org/ticket/24587
	 */
	function test_wp_nav_menu_filters_are_passed_args_object() {
		$tag_id = self::factory()->tag->create();

		$tag_insert = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'post_tag',
			'menu-item-object-id' => $tag_id,
			'menu-item-status' => 'publish',
		) );

		/*
		 * The tests take place in a range of filters to ensure the passed
		 * arguments are an object.
		 */
		// In function.
		add_filter( 'pre_wp_nav_menu',          array( $this, '_confirm_second_param_args_object' ), 10, 2 );
		add_filter( 'wp_nav_menu_objects',      array( $this, '_confirm_second_param_args_object' ), 10, 2 );
		add_filter( 'wp_nav_menu_items',        array( $this, '_confirm_second_param_args_object' ), 10, 2 );

		// In walker.
		add_filter( 'nav_menu_item_args',       array( $this, '_confirm_nav_menu_item_args_object' ) );

		add_filter( 'nav_menu_css_class',       array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		add_filter( 'nav_menu_item_id',         array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		add_filter( 'nav_menu_link_attributes', array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		add_filter( 'nav_menu_item_title',      array( $this, '_confirm_third_param_args_object' ), 10, 3 );

		add_filter( 'walker_nav_menu_start_el', array( $this, '_confirm_forth_param_args_object' ), 10, 4 );

		wp_nav_menu( array(
			'echo' => false,
			'menu' => $this->menu_id,
		) );
		wp_delete_term( $tag_id, 'post_tag' );

		/*
		 * Remove test filters.
		 */
		// In function.
		remove_filter( 'pre_wp_nav_menu',          array( $this, '_confirm_second_param_args_object' ), 10, 2 );
		remove_filter( 'wp_nav_menu_objects',      array( $this, '_confirm_second_param_args_object' ), 10, 2 );
		remove_filter( 'wp_nav_menu_items',        array( $this, '_confirm_second_param_args_object' ), 10, 2 );

		// In walker.
		remove_filter( 'nav_menu_item_args',       array( $this, '_confirm_nav_menu_item_args_object' ) );

		remove_filter( 'nav_menu_css_class',       array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		remove_filter( 'nav_menu_item_id',         array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		remove_filter( 'nav_menu_link_attributes', array( $this, '_confirm_third_param_args_object' ), 10, 3 );
		remove_filter( 'nav_menu_item_title',      array( $this, '_confirm_third_param_args_object' ), 10, 3 );

		remove_filter( 'walker_nav_menu_start_el', array( $this, '_confirm_forth_param_args_object' ), 10, 4 );

	}

	/**
	 * Run tests required to confrim Walker_Nav_Menu receives an $args object.
	 */
	function _confirm_nav_menu_item_args_object( $args ) {
		$this->assertTrue( is_object( $args ) );
		return $args;
	}

	function _confirm_second_param_args_object( $ignored_1, $args ) {
		$this->assertTrue( is_object( $args ) );
		return $ignored_1;
	}

	function _confirm_third_param_args_object( $ignored_1, $ignored_2, $args ) {
		$this->assertTrue( is_object( $args ) );
		return $ignored_1;
	}

	function _confirm_forth_param_args_object( $ignored_1, $ignored_2, $ignored_3, $args ) {
		$this->assertTrue( is_object( $args ) );
		return $ignored_1;
	}


	/**
	 * @see https://core.trac.wordpress.org/ticket/35272
	 */
	function test_no_front_page_class_applied() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Home Page' ) );

		wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-status' => 'publish',
		));

		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		_wp_menu_item_classes_by_context( $menu_items );

		$classes = $menu_items[0]->classes;

		$this->assertNotContains( 'menu-item-home', $classes );
	}


	/**
	 * @see https://core.trac.wordpress.org/ticket/35272
	 */
	function test_class_applied_to_front_page_item() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Home Page' ) );
		update_option( 'page_on_front', $page_id );

		wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'post_type',
			'menu-item-object' => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-status' => 'publish',
		));

		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		_wp_menu_item_classes_by_context( $menu_items );

		$classes = $menu_items[0]->classes;

		delete_option( 'page_on_front' );

		$this->assertContains( 'menu-item-home', $classes );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35272
	 */
	function test_class_not_applied_to_taxonomies_with_same_id_as_front_page_item() {
		global $wpdb;

		$new_id = 35272;

		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Home Page' ) );
		$tag_id = self::factory()->tag->create();

		$wpdb->query( "UPDATE $wpdb->posts SET ID=$new_id WHERE ID=$page_id" );
		$wpdb->query( "UPDATE $wpdb->terms SET term_id=$new_id WHERE term_id=$tag_id" );
		$wpdb->query( "UPDATE $wpdb->term_taxonomy SET term_id=$new_id WHERE term_id=$tag_id" );

		update_option( 'page_on_front', $new_id );

		wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type' => 'taxonomy',
			'menu-item-object' => 'post_tag',
			'menu-item-object-id' => $new_id,
			'menu-item-status' => 'publish',
		) );

		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		_wp_menu_item_classes_by_context( $menu_items );

		$classes = $menu_items[0]->classes;

		$this->assertNotContains( 'menu-item-home', $classes );
	}

	/**
	 * Test _wp_delete_customize_changeset_dependent_auto_drafts.
	 *
	 * @covers _wp_delete_customize_changeset_dependent_auto_drafts()
	 */
	function test_wp_delete_customize_changeset_dependent_auto_drafts() {
		$auto_draft_post_id = $this->factory()->post->create( array(
			'post_status' => 'auto-draft',
		) );
		$draft_post_id = $this->factory()->post->create( array(
			'post_status' => 'draft',
		) );
		$private_post_id = $this->factory()->post->create( array(
			'post_status' => 'private',
		) );

		$nav_created_post_ids = array(
			$auto_draft_post_id,
			$draft_post_id,
			$private_post_id,
		);
		$data = array(
			'nav_menus_created_posts' => array(
				'value' => $nav_created_post_ids,
			),
		);
		wp_set_current_user( self::factory()->user->create( array(
			'role' => 'administrator',
		) ) );
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$wp_customize = new WP_Customize_Manager();
		do_action( 'customize_register', $wp_customize );
		$wp_customize->save_changeset_post( array(
			'data' => $data,
		) );
		$this->assertEquals( 'auto-draft', get_post_status( $auto_draft_post_id ) );
		$this->assertEquals( 'draft', get_post_status( $draft_post_id ) );
		$this->assertEquals( 'private', get_post_status( $private_post_id ) );
		wp_delete_post( $wp_customize->changeset_post_id(), true );
		$this->assertFalse( get_post_status( $auto_draft_post_id ) );
		$this->assertEquals( 'trash', get_post_status( $draft_post_id ) );
		$this->assertEquals( 'private', get_post_status( $private_post_id ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/39800
	 */
	function test_parent_ancestor_for_post_archive() {

		register_post_type( 'books', array( 'label' => 'Books', 'public' => true, 'has_archive' => true ) );

		$first_page_id  = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Top Level Page' ) );
		$second_page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Second Level Page' ) );


		$first_menu_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $first_page_id,
			'menu-item-status'    => 'publish',
		));

		$second_menu_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type'      => 'post_type',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $second_page_id,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $first_menu_id
		));

		wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-type'      => 'post_type_archive',
			'menu-item-object'    => 'books',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $second_menu_id
		));

		$this->go_to( get_post_type_archive_link( 'books' ) );

		$menu_items = wp_get_nav_menu_items( $this->menu_id );
		_wp_menu_item_classes_by_context( $menu_items );

		$top_page_menu_item       = $menu_items[0];
		$secondary_page_menu_item = $menu_items[1];
		$post_archive_menu_item   = $menu_items[2];

		$this->assertFalse( $top_page_menu_item->current_item_parent );
		$this->assertTrue( $top_page_menu_item->current_item_ancestor );
		$this->assertContains( 'current-menu-ancestor', $top_page_menu_item->classes );

		$this->assertTrue( $secondary_page_menu_item->current_item_parent );
		$this->assertTrue( $secondary_page_menu_item->current_item_ancestor  );
		$this->assertContains( 'current-menu-parent', $secondary_page_menu_item->classes );
		$this->assertContains( 'current-menu-ancestor', $secondary_page_menu_item->classes );

		$this->assertFalse( $post_archive_menu_item->current_item_parent );
		$this->assertFalse( $post_archive_menu_item->current_item_ancestor  );

		$this->assertNotContains( 'current-menu-parent', $post_archive_menu_item->classes );
		$this->assertNotContains( 'current-menu-ancestor', $post_archive_menu_item->classes );
	}

}
