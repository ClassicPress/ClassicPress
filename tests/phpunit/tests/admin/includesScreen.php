<?php

/**
 * @group admin
 * @group adminScreen
 */
class Tests_Admin_includesScreen extends WP_UnitTestCase {
	public $core_screens = array(
		'index.php'                            => array(
			'base' => 'dashboard',
			'id'   => 'dashboard',
		),
		'edit.php'                             => array(
			'base'      => 'edit',
			'id'        => 'edit-post',
			'post_type' => 'post',
		),
		'post-new.php'                         => array(
			'action'    => 'add',
			'base'      => 'post',
			'id'        => 'post',
			'post_type' => 'post',
		),
		'edit-tags.php'                        => array(
			'base'      => 'edit-tags',
			'id'        => 'edit-post_tag',
			'post_type' => 'post',
			'taxonomy'  => 'post_tag',
		),
		'edit-tags.php?taxonomy=post_tag'      => array(
			'base'      => 'edit-tags',
			'id'        => 'edit-post_tag',
			'post_type' => 'post',
			'taxonomy'  => 'post_tag',
		),
		'edit-tags.php?taxonomy=category'      => array(
			'base'      => 'edit-tags',
			'id'        => 'edit-category',
			'post_type' => 'post',
			'taxonomy'  => 'category',
		),
		'upload.php'                           => array(
			'base'      => 'upload',
			'id'        => 'upload',
			'post_type' => 'attachment',
		),
		'media-new.php'                        => array(
			'action' => 'add',
			'base'   => 'media',
			'id'     => 'media',
		),
		'edit.php?post_type=page'              => array(
			'base'      => 'edit',
			'id'        => 'edit-page',
			'post_type' => 'page',
		),
		'link-manager.php'                     => array(
			'base' => 'link-manager',
			'id'   => 'link-manager',
		),
		'link-add.php'                         => array(
			'action' => 'add',
			'base'   => 'link',
			'id'     => 'link',
		),
		'edit-tags.php?taxonomy=link_category' => array(
			'base'      => 'edit-tags',
			'id'        => 'edit-link_category',
			'taxonomy'  => 'link_category',
			'post_type' => '',
		),
		'edit-comments.php'                    => array(
			'base' => 'edit-comments',
			'id'   => 'edit-comments',
		),
		'themes.php'                           => array(
			'base' => 'themes',
			'id'   => 'themes',
		),
		'widgets.php'                          => array(
			'base' => 'widgets',
			'id'   => 'widgets',
		),
		'nav-menus.php'                        => array(
			'base' => 'nav-menus',
			'id'   => 'nav-menus',
		),
		'plugins.php'                          => array(
			'base' => 'plugins',
			'id'   => 'plugins',
		),
		'users.php'                            => array(
			'base' => 'users',
			'id'   => 'users',
		),
		'user-new.php'                         => array(
			'action' => 'add',
			'base'   => 'user',
			'id'     => 'user',
		),
		'profile.php'                          => array(
			'base' => 'profile',
			'id'   => 'profile',
		),
		'tools.php'                            => array(
			'base' => 'tools',
			'id'   => 'tools',
		),
		'import.php'                           => array(
			'base' => 'import',
			'id'   => 'import',
		),
		'export.php'                           => array(
			'base' => 'export',
			'id'   => 'export',
		),
		'options-general.php'                  => array(
			'base' => 'options-general',
			'id'   => 'options-general',
		),
		'options-writing.php'                  => array(
			'base' => 'options-writing',
			'id'   => 'options-writing',
		),
	);

	public function tear_down() {
		unset( $GLOBALS['wp_taxonomies']['old-or-new'] );
		parent::tear_down();
	}

	public function test_set_current_screen_with_hook_suffix() {
		global $current_screen;

		foreach ( $this->core_screens as $hook_name => $screen ) {
			$_GET               = array();
			$_POST              = array();
			$_REQUEST           = array();
			$GLOBALS['taxnow']  = '';
			$GLOBALS['typenow'] = '';
			$screen             = (object) $screen;
			$hook               = parse_url( $hook_name );

			if ( ! empty( $hook['query'] ) ) {
				$args = wp_parse_args( $hook['query'] );
				if ( isset( $args['taxonomy'] ) ) {
					$GLOBALS['taxnow']    = $args['taxonomy'];
					$_GET['taxonomy']     = $args['taxonomy'];
					$_POST['taxonomy']    = $args['taxonomy'];
					$_REQUEST['taxonomy'] = $args['taxonomy'];
				}
				if ( isset( $args['post_type'] ) ) {
					$GLOBALS['typenow']    = $args['post_type'];
					$_GET['post_type']     = $args['post_type'];
					$_POST['post_type']    = $args['post_type'];
					$_REQUEST['post_type'] = $args['post_type'];
				} elseif ( isset( $screen->post_type ) ) {
					$GLOBALS['typenow']    = $screen->post_type;
					$_GET['post_type']     = $screen->post_type;
					$_POST['post_type']    = $screen->post_type;
					$_REQUEST['post_type'] = $screen->post_type;
				}
			}

			$GLOBALS['hook_suffix'] = $hook['path'];
			set_current_screen();

			$this->assertSame( $screen->id, $current_screen->id, $hook_name );
			$this->assertSame( $screen->base, $current_screen->base, $hook_name );
			if ( isset( $screen->action ) ) {
				$this->assertSame( $screen->action, $current_screen->action, $hook_name );
			}
			if ( isset( $screen->post_type ) ) {
				$this->assertSame( $screen->post_type, $current_screen->post_type, $hook_name );
			} else {
				$this->assertEmpty( $current_screen->post_type, $hook_name );
			}
			if ( isset( $screen->taxonomy ) ) {
				$this->assertSame( $screen->taxonomy, $current_screen->taxonomy, $hook_name );
			}

			$this->assertTrue( $current_screen->in_admin() );
			$this->assertTrue( $current_screen->in_admin( 'site' ) );
			$this->assertFalse( $current_screen->in_admin( 'network' ) );
			$this->assertFalse( $current_screen->in_admin( 'user' ) );
			$this->assertFalse( $current_screen->in_admin( 'garbage' ) );

			// With convert_to_screen(), the same ID should return the exact $current_screen.
			$this->assertSame( $current_screen, convert_to_screen( $screen->id ), $hook_name );

			// With convert_to_screen(), the hook_suffix should return the exact $current_screen.
			// But, convert_to_screen() cannot figure out ?taxonomy and ?post_type.
			if ( empty( $hook['query'] ) ) {
				$this->assertSame( $current_screen, convert_to_screen( $GLOBALS['hook_suffix'] ), $hook_name );
			}
		}
	}

	public function test_post_type_as_hookname() {
		$screen = convert_to_screen( 'page' );
		$this->assertSame( $screen->post_type, 'page' );
		$this->assertSame( $screen->base, 'post' );
		$this->assertSame( $screen->id, 'page' );
	}

	public function test_post_type_with_special_suffix_as_hookname() {
		register_post_type( 'value-add' );
		$screen = convert_to_screen( 'value-add' ); // The '-add' part is key.
		$this->assertSame( $screen->post_type, 'value-add' );
		$this->assertSame( $screen->base, 'post' );
		$this->assertSame( $screen->id, 'value-add' );

		$screen = convert_to_screen( 'edit-value-add' ); // The '-add' part is key.
		$this->assertSame( $screen->post_type, 'value-add' );
		$this->assertSame( $screen->base, 'edit' );
		$this->assertSame( $screen->id, 'edit-value-add' );
	}

	public function test_taxonomy_with_special_suffix_as_hookname() {
		register_taxonomy( 'old-or-new', 'post' );
		$screen = convert_to_screen( 'edit-old-or-new' ); // The '-new' part is key.
		$this->assertSame( $screen->taxonomy, 'old-or-new' );
		$this->assertSame( $screen->base, 'edit-tags' );
		$this->assertSame( $screen->id, 'edit-old-or-new' );
	}

	public function test_post_type_with_edit_prefix() {
		register_post_type( 'edit-some-thing' );
		$screen = convert_to_screen( 'edit-some-thing' );
		$this->assertSame( $screen->post_type, 'edit-some-thing' );
		$this->assertSame( $screen->base, 'post' );
		$this->assertSame( $screen->id, 'edit-some-thing' );

		$screen = convert_to_screen( 'edit-edit-some-thing' );
		$this->assertSame( $screen->post_type, 'edit-some-thing' );
		$this->assertSame( $screen->base, 'edit' );
		$this->assertSame( $screen->id, 'edit-edit-some-thing' );
	}

	public function test_post_type_edit_collisions() {
		register_post_type( 'comments' );
		register_post_type( 'tags' );

		// Sorry, core wins here.
		$screen = convert_to_screen( 'edit-comments' );
		$this->assertSame( $screen->base, 'edit-comments' );

		// The post type wins here. convert_to_screen( $post_type ) is only relevant for meta boxes anyway.
		$screen = convert_to_screen( 'comments' );
		$this->assertSame( $screen->base, 'post' );

		// Core wins.
		$screen = convert_to_screen( 'edit-tags' );
		$this->assertSame( $screen->base, 'edit-tags' );

		$screen = convert_to_screen( 'tags' );
		$this->assertSame( $screen->base, 'post' );
	}

	public function test_help_tabs() {
		$tab      = __FUNCTION__;
		$tab_args = array(
			'title'    => 'Help!',
			'id'       => $tab,
			'content'  => 'Some content',
			'callback' => false,
		);

		set_current_screen( 'edit.php' );
		$screen = get_current_screen();
		$screen->add_help_tab( $tab_args );
		$this->assertSame(
			$screen->get_help_tab( $tab ),
			array(
				'title'    => 'Help!',
				'id'       => $tab,
				'content'  => 'Some content',
				'callback' => false,
				'priority' => 10,
			)
		);

		$tabs = $screen->get_help_tabs();
		$this->assertArrayHasKey( $tab, $tabs );

		$screen->remove_help_tab( $tab );
		$this->assertNull( $screen->get_help_tab( $tab ) );

		$screen->remove_help_tabs();
		$this->assertSame( $screen->get_help_tabs(), array() );
	}

	/**
	 * @ticket 19828
	 */
	public function test_help_tabs_priority() {
		$tab_1      = 'tab1';
		$tab_1_args = array(
			'title'    => 'Help!',
			'id'       => $tab_1,
			'content'  => 'Some content',
			'callback' => false,
			'priority' => 10,
		);

		$tab_2      = 'tab2';
		$tab_2_args = array(
			'title'    => 'Help!',
			'id'       => $tab_2,
			'content'  => 'Some content',
			'callback' => false,
			'priority' => 2,
		);
		$tab_3      = 'tab3';
		$tab_3_args = array(
			'title'    => 'help!',
			'id'       => $tab_3,
			'content'  => 'some content',
			'callback' => false,
			'priority' => 40,
		);
		$tab_4      = 'tab4';
		$tab_4_args = array(
			'title'    => 'help!',
			'id'       => $tab_4,
			'content'  => 'some content',
			'callback' => false,
			// Don't include a priority.
		);

		set_current_screen( 'edit.php' );
		$screen = get_current_screen();

		// Add help tabs.

		$screen->add_help_tab( $tab_1_args );
		$this->assertSame( $screen->get_help_tab( $tab_1 ), $tab_1_args );

		$screen->add_help_tab( $tab_2_args );
		$this->assertSame( $screen->get_help_tab( $tab_2 ), $tab_2_args );

		$screen->add_help_tab( $tab_3_args );
		$this->assertSame( $screen->get_help_tab( $tab_3 ), $tab_3_args );

		$screen->add_help_tab( $tab_4_args );
		// Priority is added with the default for future calls.
		$tab_4_args['priority'] = 10;
		$this->assertSame( $screen->get_help_tab( $tab_4 ), $tab_4_args );

		$tabs = $screen->get_help_tabs();
		$this->assertCount( 4, $tabs );
		$this->assertArrayHasKey( $tab_1, $tabs );
		$this->assertArrayHasKey( $tab_2, $tabs );
		$this->assertArrayHasKey( $tab_3, $tabs );
		$this->assertArrayHasKey( $tab_4, $tabs );

		// Test priority order.

		$this->assertSame(
			array(
				$tab_2 => $tab_2_args,
				$tab_1 => $tab_1_args,
				$tab_4 => $tab_4_args,
				$tab_3 => $tab_3_args,
			),
			$tabs
		);

		$screen->remove_help_tab( $tab_1 );
		$this->assertNull( $screen->get_help_tab( $tab_1 ) );
		$this->assertCount( 3, $screen->get_help_tabs() );

		$screen->remove_help_tab( $tab_2 );
		$this->assertNull( $screen->get_help_tab( $tab_2 ) );
		$this->assertCount( 2, $screen->get_help_tabs() );

		$screen->remove_help_tab( $tab_3 );
		$this->assertNull( $screen->get_help_tab( $tab_3 ) );
		$this->assertCount( 1, $screen->get_help_tabs() );

		$screen->remove_help_tab( $tab_4 );
		$this->assertNull( $screen->get_help_tab( $tab_4 ) );
		$this->assertCount( 0, $screen->get_help_tabs() );

		$screen->remove_help_tabs();
		$this->assertSame( array(), $screen->get_help_tabs() );
	}

	/**
	 * @ticket 25799
	 */
	public function test_options() {
		$option      = __FUNCTION__;
		$option_args = array(
			'label'   => 'Option',
			'default' => 10,
			'option'  => $option,
		);

		set_current_screen( 'edit.php' );
		$screen = get_current_screen();

		$screen->add_option( $option, $option_args );
		$this->assertSame( $screen->get_option( $option ), $option_args );

		$options = $screen->get_options();
		$this->assertArrayHasKey( $option, $options );

		$screen->remove_option( $option );
		$this->assertNull( $screen->get_option( $option ) );

		$screen->remove_options();
		$this->assertSame( $screen->get_options(), array() );
	}

	public function test_in_admin() {
		set_current_screen( 'edit.php' );
		$this->assertTrue( get_current_screen()->in_admin() );
		$this->assertTrue( get_current_screen()->in_admin( 'site' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'network' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'user' ) );

		set_current_screen( 'dashboard-network' );
		$this->assertTrue( get_current_screen()->in_admin() );
		$this->assertFalse( get_current_screen()->in_admin( 'site' ) );
		$this->assertTrue( get_current_screen()->in_admin( 'network' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'user' ) );

		set_current_screen( 'dashboard-user' );
		$this->assertTrue( get_current_screen()->in_admin() );
		$this->assertFalse( get_current_screen()->in_admin( 'site' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'network' ) );
		$this->assertTrue( get_current_screen()->in_admin( 'user' ) );

		set_current_screen( 'front' );
		$this->assertFalse( get_current_screen()->in_admin() );
		$this->assertFalse( get_current_screen()->in_admin( 'site' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'network' ) );
		$this->assertFalse( get_current_screen()->in_admin( 'user' ) );
	}
}
