<?php

/**
 * Test functions in wp-includes/user.php
 *
 * @group user
 */
class Tests_User_WpDropdownUsers extends WP_UnitTestCase {
	private $check_user_query_vars_calls = 0;

	public function set_up() {
		$this->check_user_query_vars_calls = 0;
		parent::set_up();
	}

	public function tear_down() {
		remove_action( 'pre_get_users', array( $this, 'check_user_query_vars' ) );
		parent::tear_down();
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31251
	 */
	public function test_default_value_of_show_should_be_display_name() {

		// create a user with a different display_name
		$u = $this->factory->user->create(
			array(
				'user_login'   => 'foo',
				'display_name' => 'Foo Person',
			)
		);

		$found = wp_dropdown_users(
			array(
				'echo' => false,
			)
		);

		$expected = "<option value='$u'>Foo Person</option>";

		$this->assertStringContainsString( $expected, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31251
	 */
	public function test_show_should_display_display_name_show_is_specified_as_empty() {

		// create a user with a different display_name
		$u = $this->factory->user->create(
			array(
				'user_login'   => 'foo',
				'display_name' => 'Foo Person',
			)
		);

		// Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
		$found = wp_dropdown_users(
			array(
				'echo' => false,
				'show' => '',
			)
		);

		$expected = "<option value='$u'>Foo Person</option>";

		$this->assertStringContainsString( $expected, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31251
	 */
	public function test_show_should_display_user_property_when_the_value_of_show_is_a_valid_user_property() {

		// create a user with a different display_name
		$u = $this->factory->user->create(
			array(
				'user_login'   => 'foo',
				'display_name' => 'Foo Person',
			)
		);

		// Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
		$found = wp_dropdown_users(
			array(
				'echo' => false,
				'show' => 'user_login',
			)
		);

		$expected = "<option value='$u'>foo</option>";

		$this->assertStringContainsString( $expected, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31251
	 */
	public function test_show_display_name_with_login() {

		// create a user with a different display_name
		$u = $this->factory->user->create(
			array(
				'user_login'   => 'foo',
				'display_name' => 'Foo Person',
			)
		);

		// Get the result of a non-default, but acceptable input for 'show' parameter to wp_dropdown_users().
		$found = wp_dropdown_users(
			array(
				'echo' => false,
				'show' => 'display_name_with_login',
			)
		);

		$expected = "<option value='$u'>Foo Person (foo)</option>";

		$this->assertStringContainsString( $expected, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31251
	 */
	public function test_include_selected() {
		$users = self::factory()->user->create_many( 2 );

		$found = wp_dropdown_users(
			array(
				'echo'             => false,
				'include'          => $users[0],
				'selected'         => $users[1],
				'include_selected' => true,
				'show'             => 'user_login',
			)
		);

		$user1 = get_userdata( $users[1] );
		$this->assertStringContainsString( $user1->user_login, $found );
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/572
	 */
	public function test_invalid_user() {
		global $wpdb;

		$users = self::factory()->user->create_many( 2 );

		$invalid_user_id = $wpdb->get_var( "SELECT MAX(ID) + 3 FROM {$wpdb->users}" );

		$found = wp_dropdown_users(
			array(
				'echo'             => false,
				'include'          => $users,
				'selected'         => $invalid_user_id,
				'include_selected' => true,
			)
		);

		$user0 = get_userdata( $users[0] );
		$user1 = get_userdata( $users[1] );

		$this->assertStringContainsString( "<select name='user' id='user' class=''>", $found );
		$this->assertStringContainsString( "<option value='{$user0->ID}'>{$user0->display_name}</option>", $found );
		$this->assertStringContainsString( "<option value='{$user1->ID}'>{$user1->display_name}</option>", $found );
		$this->assertStringContainsString( "<option value='{$invalid_user_id}' selected='selected'>(Invalid user: ID={$invalid_user_id})</option>", $found );
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/791
	 */
	public function test_value_field_and_select_multiple() {
		$users = self::factory()->user->create_many( 2 );

		$found = wp_dropdown_users(
			array(
				'name'             => 'multiusers',
				'echo'             => false,
				'include'          => $users[0],
				'selected'         => $users[1],
				'include_selected' => true,
				'show'             => 'user_nicename',
				'value_field'      => 'user_login',
				'select_multiple'  => true,
			)
		);

		$user0 = get_userdata( $users[0] );
		$user1 = get_userdata( $users[1] );

		$this->assertStringContainsString( "<select name='multiusers[]' id='multiusers' class='' multiple>", $found );
		$this->assertStringContainsString( "<option value='{$user0->user_login}'>{$user0->user_nicename}</option>", $found );
		$this->assertStringContainsString( "<option value='{$user1->user_login}' selected='selected'>{$user1->user_nicename}</option>", $found );
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/791
	 */
	public function test_value_field_added_to_query() {
		$users = self::factory()->user->create_many( 2 );

		$found = wp_dropdown_users(
			array(
				'echo'        => false,
				'include'     => $users,
				'selected'    => $users[0],
				'show'        => 'user_login',
				// The user_nicename field is not included in the `get_users()`
				// query by default.
				'value_field' => 'user_nicename',
			)
		);

<<<<<<< HEAD
		$user0 = get_userdata( $users[0] );
		$user1 = get_userdata( $users[1] );
		$this->assertStringContainsString( "<option value='{$user0->user_nicename}' selected='selected'>$user0->user_login</option>", $found );
		$this->assertStringContainsString( "<option value='{$user1->user_nicename}'>$user1->user_login</option>", $found );
	}

	public function check_user_query_vars( $user_query ) {
		$this->assertSame(
			$user_query->query_vars['fields'],
			array_unique( $user_query->query_vars['fields'] ),
			'Each queried user field should be listed only once.'
		);
		$this->check_user_query_vars_calls++;
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/791
	 */
	public function test_value_field_added_to_query_only_once() {
		$users = self::factory()->user->create_many( 2 );

		add_action( 'pre_get_users', array( $this, 'check_user_query_vars' ) );

		$found = wp_dropdown_users(
			array(
				'echo'        => false,
				'include'     => $users,
				'selected'    => $users[0],
				// When a field is requested for multiple "purposes" it should
				// still be passed to `WP_User_Query` only once.
				'show'        => 'user_nicename',
				'value_field' => 'user_nicename',
			)
		);

		$user0 = get_userdata( $users[0] );
		$user1 = get_userdata( $users[1] );
		$this->assertStringContainsString( "<option value='{$user0->user_nicename}' selected='selected'>$user0->user_nicename</option>", $found );
		$this->assertStringContainsString( "<option value='{$user1->user_nicename}'>$user1->user_nicename</option>", $found );
		$this->assertSame( $this->check_user_query_vars_calls, 1 );
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/791
	 */
	public function test_invalid_value_field() {
		$users = self::factory()->user->create_many( 2 );

		$found = wp_dropdown_users(
			array(
				'echo'             => false,
				'include'          => $users[0],
				'selected'         => $users[1],
				'include_selected' => true,
				'show'             => 'user_login',
				// Querying users by this field is not supported, so the dropdown
				// should fall back to the default field of 'ID'.
				'value_field'      => 'display_name',
			)
		);

		$user1 = get_userdata( $users[1] );
		$this->assertStringContainsString( "<option value='{$user1->ID}' selected='selected'>$user1->user_login</option>", $found );
	}

	/**
	 * @see https://github.com/ClassicPress/ClassicPress/pull/572
	 * @see https://github.com/ClassicPress/ClassicPress/pull/791
	 */
	public function test_invalid_user_with_value_field() {
		global $wpdb;

		$users = self::factory()->user->create_many( 2 );

		$invalid_user_id = $wpdb->get_var( "SELECT MAX(ID) + 3 FROM {$wpdb->users}" );

		$found = wp_dropdown_users(
			array(
				'echo'             => false,
				'include'          => $users,
				'selected'         => $invalid_user_id,
				'include_selected' => true,
				'value_field'      => 'user_login',
			)
		);

		$user0 = get_userdata( $users[0] );
		$user1 = get_userdata( $users[1] );

		$this->assertStringContainsString( "<select name='user' id='user' class=''>", $found );
		$this->assertStringContainsString( "<option value='{$user0->user_login}'>{$user0->display_name}</option>", $found );
		$this->assertStringContainsString( "<option value='{$user1->user_login}'>{$user1->display_name}</option>", $found );
		$this->assertStringNotContainsString( 'Invalid user:', $found );
=======
		$this->assertStringNotContainsString( (string) PHP_INT_MAX, $found );
>>>>>>> c70fe62ed1 (Tests: Replace `assertContains()` with `assertStringContainsString()` when used with strings.)
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38135
	 */
	public function test_role() {
		$u1 = self::factory()->user->create_and_get( array( 'role' => 'subscriber' ) );
		$u2 = self::factory()->user->create_and_get( array( 'role' => 'author' ) );

		$found = wp_dropdown_users(
			array(
				'echo' => false,
				'role' => 'author',
				'show' => 'user_login',
			)
		);

		$this->assertStringNotContainsString( $u1->user_login, $found );
		$this->assertStringContainsString( $u2->user_login, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38135
	 */
	public function test_role__in() {
		$u1 = self::factory()->user->create_and_get( array( 'role' => 'subscriber' ) );
		$u2 = self::factory()->user->create_and_get( array( 'role' => 'author' ) );

		$found = wp_dropdown_users(
			array(
				'echo'     => false,
				'role__in' => array( 'author', 'editor' ),
				'show'     => 'user_login',
			)
		);

		$this->assertStringNotContainsString( $u1->user_login, $found );
		$this->assertStringContainsString( $u2->user_login, $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38135
	 */
	public function test_role__not_in() {
		$u1 = self::factory()->user->create_and_get( array( 'role' => 'subscriber' ) );
		$u2 = self::factory()->user->create_and_get( array( 'role' => 'author' ) );

		$found = wp_dropdown_users(
			array(
				'echo'         => false,
				'role__not_in' => array( 'subscriber', 'editor' ),
				'show'         => 'user_login',
			)
		);

		$this->assertStringNotContainsString( $u1->user_login, $found );
		$this->assertStringContainsString( $u2->user_login, $found );
	}
}
