<?php

/**
 * @group  comment
 *
 * @covers ::comment_form
 */
class Tests_Comment_CommentForm extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up();
		update_option( 'default_comment_status', 'open' );
	}

	public function tear_down() {
		update_option( 'default_comment_status', 'closed' );
		parent::tear_down();
	}

	public function test_default_markup_for_submit_button_and_wrapper() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit'  => 'foo-name',
			'id_submit'    => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label">';
		$hidden = get_comment_id_fields( $p );
		$this->assertMatchesRegularExpression( '|<p class="form\-submit">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	public function test_custom_submit_button() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit'   => 'foo-name',
			'id_submit'     => 'foo-id',
			'class_submit'  => 'foo-class',
			'label_submit'  => 'foo-label',
			'submit_button' => '<input name="custom-%1$s" type="submit" id="custom-%2$s" class="custom-%3$s" value="custom-%4$s">',
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="custom-foo-name" type="submit" id="custom-foo-id" class="custom-foo-class" value="custom-foo-label">';
		$this->assertStringContainsString( $button, $form );
	}

	public function test_custom_submit_field() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit'  => 'foo-name',
			'id_submit'    => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
			'submit_field' => '<p class="my-custom-submit-field">%1$s %2$s</p>',
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label">';
		$hidden = get_comment_id_fields( $p );
		$this->assertMatchesRegularExpression( '|<p class="my\-custom\-submit\-field">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	/**
	 * @ticket 32312
	 */
	public function test_submit_button_and_submit_field_should_fall_back_on_defaults_when_filtered_defaults_do_not_contain_the_keys() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit'  => 'foo-name',
			'id_submit'    => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
		);

		add_filter( 'comment_form_defaults', array( $this, 'filter_comment_form_defaults' ) );
		$form = get_echo( 'comment_form', array( $args, $p ) );
		remove_filter( 'comment_form_defaults', array( $this, 'filter_comment_form_defaults' ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label">';
		$hidden = get_comment_id_fields( $p );
		$this->assertMatchesRegularExpression( '|<p class="form\-submit">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	public function filter_comment_form_defaults( $defaults ) {
		unset( $defaults['submit_field'] );
		unset( $defaults['submit_button'] );
		return $defaults;
	}

	/**
	 * @ticket 44126
	 */
	public function test_fields_should_include_cookies_consent() {
		$p = self::factory()->post->create();

		add_filter( 'option_show_comments_cookies_opt_in', '__return_true' );

		$args = array(
			'fields' => array(
				'author' => 'Hello World!',
			),
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		remove_filter( 'option_show_comments_cookies_opt_in', '__return_true' );

		$this->assertMatchesRegularExpression( '|<p class="comment\-form\-cookies\-consent">.*?</p>|', $form );
	}

	/**
	 * @ticket 47975
	 */
	public function test_aria_describedby_email_notes_should_not_be_added_if_no_email_notes() {
		$p = self::factory()->post->create();

		$form_with_aria = get_echo( 'comment_form', array( array(), $p ) );

		$this->assertStringContainsString( 'aria-describedby="email-notes"', $form_with_aria );

		$args = array(
			'comment_notes_before' => '',
		);

		$form_without_aria = get_echo( 'comment_form', array( $args, $p ) );

		$this->assertStringNotContainsString( 'aria-describedby="email-notes"', $form_without_aria );
	}

	/**
	 * @ticket 16576
	 */
	public function test_custom_fields_shown_default_fields_hidden_for_logged_in_users() {
		$p = self::factory()->post->create();

		$user_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'testuser',
				'user_email' => 'test@example.com',
			)
		);

		wp_set_current_user( $user_id );
		$this->assertTrue( is_user_logged_in() );

		$args = array(
			'fields' => array(
				'author'       => '<p><label for="author">Name</label><input type="text" name="author" id="author" /></p>',
				'email'        => '<p><label for="email">Email</label><input type="email" name="email" id="email" /></p>',
				'url'          => '<p><label for="url">Website</label><input type="url" name="url" id="url" /></p>',
				'cookies'      => '<p><input type="checkbox" name="wp-comment-cookies-consent" id="wp-comment-cookies-consent" /><label for="wp-comment-cookies-consent">Save my details</label></p>',
				'custom_field' => '<p><label for="custom_field">Custom Field</label><input type="text" name="custom_field" id="custom_field" /></p>',
				'department'   => '<p><label for="department">Department</label><select name="department" id="department"><option value="sales">Sales</option></select></p>',
			),
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		// Custom fields should be present
		$this->assertStringContainsString( 'name="custom_field"', $form );
		$this->assertStringContainsString( 'name="department"', $form );
		$this->assertStringContainsString( 'Custom Field', $form );
		$this->assertStringContainsString( 'Department', $form );

		// Default fields should NOT be present
		$this->assertStringNotContainsString( 'name="author"', $form );
		$this->assertStringNotContainsString( 'name="email"', $form );
		$this->assertStringNotContainsString( 'name="url"', $form );
		$this->assertStringNotContainsString( 'wp-comment-cookies-consent', $form );

		wp_set_current_user( 0 );
	}

	/**
	 * @ticket 16576
	 */
	public function test_all_fields_displayed_for_non_logged_in_users() {
		$p = self::factory()->post->create();

		wp_set_current_user( 0 );
		$this->assertFalse( is_user_logged_in() );

		$args = array(
			'fields' => array(
				'author'       => '<p><label for="author">Name</label><input type="text" name="author" id="author" /></p>',
				'email'        => '<p><label for="email">Email</label><input type="email" name="email" id="email" /></p>',
				'url'          => '<p><label for="url">Website</label><input type="url" name="url" id="url" /></p>',
				'custom_field' => '<p><label for="custom_field">Custom Field</label><input type="text" name="custom_field" id="custom_field" /></p>',
			),
		);

		$form = get_echo( 'comment_form', array( $args, $p ) );

		// All fields should be present for non-logged-in users
		$this->assertStringContainsString( 'name="author"', $form );
		$this->assertStringContainsString( 'name="email"', $form );
		$this->assertStringContainsString( 'name="url"', $form );
		$this->assertStringContainsString( 'name="custom_field"', $form );
	}
}
