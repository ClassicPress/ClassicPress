<?php

/**
 * @group admin-bar
 * @group toolbar
 * @group admin
 */
class Tests_AdminBar extends WP_UnitTestCase {
	protected static $editor_id;
	protected static $admin_id;
	protected static $no_role_id;
	protected static $post_id;
	protected static $blog_id;

	protected static $user_ids = array();

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
	}

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$editor_id  = $factory->user->create( array( 'role' => 'editor' ) );
		self::$user_ids[] = self::$editor_id;
		self::$admin_id   = $factory->user->create( array( 'role' => 'administrator' ) );
		self::$user_ids[] = self::$admin_id;
		self::$no_role_id = $factory->user->create( array( 'role' => '' ) );
		self::$user_ids[] = self::$no_role_id;
	}

	/**
	 * @ticket 21117
	 */
	public function test_content_post_type() {
		wp_set_current_user( self::$editor_id );

		register_post_type( 'content', array( 'show_in_admin_bar' => true ) );

		$admin_bar = new WP_Admin_Bar();

		wp_admin_bar_new_content_menu( $admin_bar );

		$nodes = $admin_bar->get_nodes();
		$this->assertFalse( $nodes['new-content']->parent );
		$this->assertSame( 'new-content', $nodes['add-new-content']->parent );

		_unregister_post_type( 'content' );
	}

	/**
	 * @ticket 21117
	 */
	public function test_merging_existing_meta_values() {
		wp_set_current_user( self::$editor_id );

		$admin_bar = new WP_Admin_Bar();

		$admin_bar->add_node(
			array(
				'id'   => 'test-node',
				'meta' => array( 'class' => 'test-class' ),
			)
		);

		$node1 = $admin_bar->get_node( 'test-node' );
		$this->assertSame( array( 'class' => 'test-class' ), $node1->meta );

		$admin_bar->add_node(
			array(
				'id'   => 'test-node',
				'meta' => array( 'some-meta' => 'value' ),
			)
		);

		$node2 = $admin_bar->get_node( 'test-node' );
		$this->assertSame(
			array(
				'class'     => 'test-class',
				'some-meta' => 'value',
			),
			$node2->meta
		);
	}

	/**
	 * @ticket 25162
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role() {
		$this->assertFalse( user_can( self::$no_role_id, 'read' ) );

		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name    = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account   = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info    = $wp_admin_bar->get_node( 'user-info' );
		$node_edit_profile = $wp_admin_bar->get_node( 'edit-profile' );

		// Site menu points to the home page instead of the admin URL.
		$this->assertSame( home_url( '/' ), $node_site_name->href );

		// No profile links as the user doesn't have any permissions on the site.
		$this->assertFalse( $node_my_account->href );
		$this->assertFalse( $node_user_info->href );
		$this->assertNull( $node_edit_profile );
	}

	/**
	 * @ticket 25162
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_role() {
		$this->assertTrue( user_can( self::$editor_id, 'read' ) );

		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name    = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account   = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info    = $wp_admin_bar->get_node( 'user-info' );
		$node_edit_profile = $wp_admin_bar->get_node( 'edit-profile' );

		// Site menu points to the admin URL.
		$this->assertSame( admin_url( '/' ), $node_site_name->href );

		$profile_url = admin_url( 'profile.php' );

		// Profile URLs point to profile.php.
		$this->assertSame( $profile_url, $node_my_account->href );
		$this->assertSame( $profile_url, $node_user_info->href );
		$this->assertSame( $profile_url, $node_edit_profile->href );
	}

	/**
	 * @ticket 25162
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role_on_blog() {
		$blog_id = self::factory()->blog->create(
			array(
				'user_id' => self::$admin_id,
			)
		);

		$this->assertTrue( user_can( self::$admin_id, 'read' ) );
		$this->assertTrue( user_can( self::$editor_id, 'read' ) );

		$this->assertTrue( is_user_member_of_blog( self::$admin_id, $blog_id ) );
		$this->assertFalse( is_user_member_of_blog( self::$editor_id, $blog_id ) );

		wp_set_current_user( self::$editor_id );

		switch_to_blog( $blog_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name    = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account   = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info    = $wp_admin_bar->get_node( 'user-info' );
		$node_edit_profile = $wp_admin_bar->get_node( 'edit-profile' );

		// Get primary blog.
		$primary = get_active_blog_for_user( self::$editor_id );
		$this->assertIsObject( $primary );

		// No Site menu as the user isn't a member of this blog.
		$this->assertNull( $node_site_name );

		$primary_profile_url = get_admin_url( $primary->blog_id, 'profile.php' );

		// Ensure the user's primary blog is not the same as the main site.
		$this->assertNotEquals( $primary_profile_url, admin_url( 'profile.php' ) );

		// Profile URLs should go to the user's primary blog.
		$this->assertSame( $primary_profile_url, $node_my_account->href );
		$this->assertSame( $primary_profile_url, $node_user_info->href );
		$this->assertSame( $primary_profile_url, $node_edit_profile->href );

		restore_current_blog();
	}

	/**
	 * @ticket 25162
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_links_for_users_with_no_role_on_network() {
		$this->assertTrue( user_can( self::$admin_id, 'read' ) );
		$this->assertFalse( user_can( self::$no_role_id, 'read' ) );

		$blog_id = self::factory()->blog->create(
			array(
				'user_id' => self::$admin_id,
			)
		);

		$this->assertTrue( is_user_member_of_blog( self::$admin_id, $blog_id ) );
		$this->assertFalse( is_user_member_of_blog( self::$no_role_id, $blog_id ) );
		$this->assertTrue( is_user_member_of_blog( self::$no_role_id, get_current_blog_id() ) );

		// Remove `$nobody` from the current blog, so they're not a member of any blog.
		$removed = remove_user_from_blog( self::$no_role_id, get_current_blog_id() );

		$this->assertTrue( $removed );
		$this->assertFalse( is_user_member_of_blog( self::$no_role_id, get_current_blog_id() ) );

		wp_set_current_user( self::$no_role_id );

		switch_to_blog( $blog_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_site_name    = $wp_admin_bar->get_node( 'site-name' );
		$node_my_account   = $wp_admin_bar->get_node( 'my-account' );
		$node_user_info    = $wp_admin_bar->get_node( 'user-info' );
		$node_edit_profile = $wp_admin_bar->get_node( 'edit-profile' );

		// Get primary blog.
		$primary = get_active_blog_for_user( self::$no_role_id );
		$this->assertNull( $primary );

		// No Site menu as the user isn't a member of this site.
		$this->assertNull( $node_site_name );

		$user_profile_url = user_admin_url( 'profile.php' );

		$this->assertNotEquals( $user_profile_url, admin_url( 'profile.php' ) );

		// Profile URLs should go to the user's primary blog.
		$this->assertSame( $user_profile_url, $node_my_account->href );
		$this->assertSame( $user_profile_url, $node_user_info->href );
		$this->assertSame( $user_profile_url, $node_edit_profile->href );

		restore_current_blog();
	}

	protected function get_standard_admin_bar() {
		global $wp_admin_bar;

		_wp_admin_bar_init();

		$this->assertTrue( is_admin_bar_showing() );
		$this->assertInstanceOf( 'WP_Admin_Bar', $wp_admin_bar );

		do_action_ref_array( 'admin_bar_menu', array( &$wp_admin_bar ) );

		return $wp_admin_bar;
	}

	/**
	 * @ticket 32495
	 *
	 * @dataProvider data_admin_bar_nodes_with_tabindex_meta
	 *
	 * @param array  $node_data     The data for a node, passed to `WP_Admin_Bar::add_node()`.
	 * @param string $expected_html The expected HTML when admin menu is rendered.
	 */
	public function test_admin_bar_with_tabindex_meta( $node_data, $expected_html ) {
		$admin_bar = new WP_Admin_Bar();
		$admin_bar->add_node( $node_data );
		$admin_bar_html = get_echo( array( $admin_bar, 'render' ) );
		$this->assertStringContainsString( $expected_html, $admin_bar_html );
	}

	/**
	 * Data provider for test_admin_bar_with_tabindex_meta().
	 *
	 * @return array {
	 *     @type array {
	 *         @type array  $node_data     The data for a node, passed to `WP_Admin_Bar::add_node()`.
	 *         @type string $expected_html The expected HTML when admin bar is rendered.
	 *     }
	 * }
	 */
	public function data_admin_bar_nodes_with_tabindex_meta() {
		return array(
			array(
				// No tabindex.
				array(
					'id' => 'test-node',
				),
				'<div class="ab-item ab-empty-item">',
			),
			array(
				// Empty string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '' ),
				),
				'<div class="ab-item ab-empty-item">',
			),
			array(
				// Integer 1 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '1' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="1">',
			),
			array(
				// Integer -1 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '-1' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="-1">',
			),
			array(
				// Integer 0 as string.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => '0' ),
				),
				'<div class="ab-item ab-empty-item" tabindex="0">',
			),
			array(
				// Integer, 0.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => 0 ),
				),
				'<div class="ab-item ab-empty-item" tabindex="0">',
			),
			array(
				// Integer, 2.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => 2 ),
				),
				'<div class="ab-item ab-empty-item" tabindex="2">',
			),
			array(
				// Boolean, false.
				array(
					'id'   => 'test-node',
					'meta' => array( 'tabindex' => false ),
				),
				'<div class="ab-item ab-empty-item">',
			),
		);
	}

	/**
	 * @ticket 22247
	 */
	public function test_admin_bar_has_edit_link_for_existing_posts() {
		wp_set_current_user( self::$editor_id );

		$post = array(
			'post_author'  => self::$editor_id,
			'post_status'  => 'publish',
			'post_content' => 'Post Content',
			'post_title'   => 'Post Title',
		);
		$id   = wp_insert_post( $post );

		// Set queried object to the newly created post.
		global $wp_the_query;
		$wp_the_query->queried_object = (object) array(
			'ID'        => $id,
			'post_type' => 'post',
		);

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_edit = $wp_admin_bar->get_node( 'edit' );
		$this->assertNotNull( $node_edit );
	}

	/**
	 * @ticket 22247
	 */
	public function test_admin_bar_has_no_edit_link_for_non_existing_posts() {
		wp_set_current_user( self::$editor_id );

		// Set queried object to a non-existing post.
		global $wp_the_query;
		$wp_the_query->queried_object = (object) array(
			'ID'        => 0,
			'post_type' => 'post',
		);

		$wp_admin_bar = $this->get_standard_admin_bar();

		$node_edit = $wp_admin_bar->get_node( 'edit' );
		$this->assertNull( $node_edit );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_if_no_static_front_page() {
		set_current_screen( 'edit-post' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_contains_view_archive_link_if_static_front_page() {
		update_option( 'show_on_front', 'page' );
		set_current_screen( 'edit-post' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNotNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_pages() {
		set_current_screen( 'edit-page' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 37949
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_role() {
		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertSame( admin_url( 'about.php' ), $wp_logo_node->href );
		$this->assertArrayNotHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertNotNull( $about_node );
	}

	/**
	 * @ticket 37949
	 * @group ms-excluded
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_no_role() {
		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertFalse( $wp_logo_node->href );
		$this->assertArrayHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertSame( 0, $wp_logo_node->meta['tabindex'] );
		$this->assertNull( $about_node );
	}

	/**
	 * @ticket 37949
	 * @group multisite
	 * @group ms-required
	 */
	public function test_admin_bar_contains_correct_about_link_for_users_with_no_role_in_multisite() {
		// User is not a member of a site.
		remove_user_from_blog( self::$no_role_id, get_current_blog_id() );

		wp_set_current_user( self::$no_role_id );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$wp_logo_node = $wp_admin_bar->get_node( 'wp-logo' );
		$about_node   = $wp_admin_bar->get_node( 'about' );

		$this->assertNotNull( $wp_logo_node );
		$this->assertSame( user_admin_url( 'about.php' ), $wp_logo_node->href );
		$this->assertArrayNotHasKey( 'tabindex', $wp_logo_node->meta );
		$this->assertNotNull( $about_node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_non_public_cpt() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => false,
				'has_archive'       => true,
				'show_in_admin_bar' => true,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_cpt_without_archive() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => true,
				'has_archive'       => false,
				'show_in_admin_bar' => true,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 34113
	 */
	public function test_admin_bar_has_no_archives_link_for_cpt_not_shown_in_admin_bar() {
		register_post_type(
			'foo-non-public',
			array(
				'public'            => true,
				'has_archive'       => true,
				'show_in_admin_bar' => false,
			)
		);

		set_current_screen( 'edit-foo-non-public' );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'archive' );

		unregister_post_type( 'foo-non-public' );

		$this->assertNull( $node );
	}

	public function map_meta_cap_grant_create_users( $caps, $cap ) {
		if ( 'create_users' === $cap ) {
			$caps = array( 'exist' );
		}

		return $caps;
	}

	public function map_meta_cap_deny_create_users( $caps, $cap ) {
		if ( 'create_users' === $cap ) {
			$caps = array( 'do_not_allow' );
		}

		return $caps;
	}

	public function map_meta_cap_grant_promote_users( $caps, $cap ) {
		if ( 'promote_users' === $cap ) {
			$caps = array( 'exist' );
		}

		return $caps;
	}

	public function map_meta_cap_deny_promote_users( $caps, $cap ) {
		if ( 'promote_users' === $cap ) {
			$caps = array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_exists_for_user_with_create_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_grant_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_promote_users' ), 10, 2 );

		$this->assertTrue( current_user_can( 'create_users' ) );
		$this->assertFalse( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		// 'create_users' is sufficient in single- and multisite.
		$this->assertNotEmpty( $node );
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_existence_for_user_with_promote_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_grant_promote_users' ), 10, 2 );

		$this->assertFalse( current_user_can( 'create_users' ) );
		$this->assertTrue( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		if ( is_multisite() ) {
			$this->assertNotEmpty( $node );
		} else {
			// 'promote_users' is insufficient in single-site.
			$this->assertNull( $node );
		}
	}

	/**
	 * @ticket 39252
	 */
	public function test_new_user_link_does_not_exist_for_user_without_create_or_promote_users() {
		wp_set_current_user( self::$admin_id );

		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_create_users' ), 10, 2 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap_deny_promote_users' ), 10, 2 );

		$this->assertFalse( current_user_can( 'create_users' ) );
		$this->assertFalse( current_user_can( 'promote_users' ) );

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'new-user' );

		$this->assertNull( $node );
	}

	/**
	 * @ticket 30937
	 * @covers ::wp_admin_bar_customize_menu
	 */
	public function test_customize_link() {
		global $wp_customize;
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$uuid = wp_generate_uuid4();
		$this->go_to( home_url( "/?customize_changeset_uuid=$uuid" ) );
		wp_set_current_user( self::$admin_id );

		self::factory()->post->create(
			array(
				'post_type'   => 'customize_changeset',
				'post_status' => 'auto-draft',
				'post_name'   => $uuid,
			)
		);
		$wp_customize = new WP_Customize_Manager(
			array(
				'changeset_uuid' => $uuid,
			)
		);
		$wp_customize->start_previewing_theme();

		$wp_admin_bar = $this->get_standard_admin_bar();
		$node         = $wp_admin_bar->get_node( 'customize' );
		$this->assertNotEmpty( $node );

		$parsed_url   = wp_parse_url( $node->href );
		$query_params = array();
		wp_parse_str( $parsed_url['query'], $query_params );
		$this->assertSame( $uuid, $query_params['changeset_uuid'] );
		$this->assertStringNotContainsString( 'changeset_uuid', $query_params['url'] );
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 */
	public function test_my_sites_network_menu_for_regular_user() {
		wp_set_current_user( self::$editor_id );

		$wp_admin_bar = $this->get_standard_admin_bar();

		$nodes = $wp_admin_bar->get_nodes();
		foreach ( $this->get_my_sites_network_menu_items() as $id => $cap ) {
			$this->assertArrayNotHasKey( $id, $nodes, sprintf( 'Menu item %s must not display for a regular user.', $id ) );
		}
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 */
	public function test_my_sites_network_menu_for_super_admin() {
		wp_set_current_user( self::$editor_id );

		grant_super_admin( self::$editor_id );
		$wp_admin_bar = $this->get_standard_admin_bar();
		revoke_super_admin( self::$editor_id );

		$nodes = $wp_admin_bar->get_nodes();
		foreach ( $this->get_my_sites_network_menu_items() as $id => $cap ) {
			$this->assertArrayHasKey( $id, $nodes, sprintf( 'Menu item %s must display for a super admin.', $id ) );
		}
	}

	/**
	 * @ticket 39082
	 * @group ms-required
	 */
	public function test_my_sites_network_menu_for_regular_user_with_network_caps() {
		global $current_user;

		$network_user_caps = array( 'manage_network', 'manage_network_themes', 'manage_network_plugins' );

		wp_set_current_user( self::$editor_id );

		foreach ( $network_user_caps as $network_cap ) {
			$current_user->add_cap( $network_cap );
		}
		$wp_admin_bar = $this->get_standard_admin_bar();
		foreach ( $network_user_caps as $network_cap ) {
			$current_user->remove_cap( $network_cap );
		}

		$nodes = $wp_admin_bar->get_nodes();
		foreach ( $this->get_my_sites_network_menu_items() as $id => $cap ) {
			if ( in_array( $cap, $network_user_caps, true ) ) {
				$this->assertArrayHasKey( $id, $nodes, sprintf( 'Menu item %1$s must display for a user with the %2$s cap.', $id, $cap ) );
			} else {
				$this->assertArrayNotHasKey( $id, $nodes, sprintf( 'Menu item %1$s must not display for a user without the %2$s cap.', $id, $cap ) );
			}
		}
	}

	private function get_my_sites_network_menu_items() {
		return array(
			'my-sites-super-admin' => 'manage_network',
			'network-admin'        => 'manage_network',
			'network-admin-d'      => 'manage_network',
			'network-admin-s'      => 'manage_sites',
			'network-admin-u'      => 'manage_network_users',
			'network-admin-t'      => 'manage_network_themes',
			'network-admin-p'      => 'manage_network_plugins',
			'network-admin-o'      => 'manage_network_options',
		);
	}
}
