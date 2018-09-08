<?php

/**
 * @group comment
 */
class Tests_Comment_CommentForm extends WP_UnitTestCase {
	public function test_default_markup_for_submit_button_and_wrapper() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit' => 'foo-name',
			'id_submit' => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
		);
		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label" />';
		$hidden = get_comment_id_fields( $p );
		$this->assertRegExp( '|<p class="form\-submit">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	public function test_custom_submit_button() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit' => 'foo-name',
			'id_submit' => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
			'submit_button' => '<input name="custom-%1$s" type="submit" id="custom-%2$s" class="custom-%3$s" value="custom-%4$s" />'
		);
		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="custom-foo-name" type="submit" id="custom-foo-id" class="custom-foo-class" value="custom-foo-label" />';
		$this->assertContains( $button, $form );
	}

	public function test_custom_submit_field() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit' => 'foo-name',
			'id_submit' => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
			'submit_field' => '<p class="my-custom-submit-field">%1$s %2$s</p>'
		);
		$form = get_echo( 'comment_form', array( $args, $p ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label" />';
		$hidden = get_comment_id_fields( $p );
		$this->assertRegExp( '|<p class="my\-custom\-submit\-field">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/32312
	 */
	public function test_submit_button_and_submit_field_should_fall_back_on_defaults_when_filtered_defaults_do_not_contain_the_keys() {
		$p = self::factory()->post->create();

		$args = array(
			'name_submit' => 'foo-name',
			'id_submit' => 'foo-id',
			'class_submit' => 'foo-class',
			'label_submit' => 'foo-label',
		);

		add_filter( 'comment_form_defaults', array( $this, 'filter_comment_form_defaults' ) );
		$form = get_echo( 'comment_form', array( $args, $p ) );
		remove_filter( 'comment_form_defaults', array( $this, 'filter_comment_form_defaults' ) );

		$button = '<input name="foo-name" type="submit" id="foo-id" class="foo-class" value="foo-label" />';
		$hidden = get_comment_id_fields( $p );
		$this->assertRegExp( '|<p class="form\-submit">\s*' . $button . '\s*' . $hidden . '\s*|', $form );
	}

	public function filter_comment_form_defaults( $defaults ) {
		unset( $defaults['submit_field'] );
		unset( $defaults['submit_button'] );
		return $defaults;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/44126
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

		$this->assertRegExp( '|<p class="comment\-form\-cookies\-consent">.*?</p>|', $form );
	}
}
