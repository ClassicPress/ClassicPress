<?php

/**
 * @group l10n
 * @group i18n
 */
class Tests_L10n extends WP_UnitTestCase {

	/**
	 * @see https://core.trac.wordpress.org/ticket/35961
	 */
	function test_n_noop() {
		$text_domain   = 'text-domain';
		$nooped_plural = _n_noop( '%s post', '%s posts', $text_domain );

		$this->assertNotEmpty( $nooped_plural['domain'] );
		$this->assertSame( '%s posts', translate_nooped_plural( $nooped_plural, 0, $text_domain ) );
		$this->assertSame( '%s post', translate_nooped_plural( $nooped_plural, 1, $text_domain ) );
		$this->assertSame( '%s posts', translate_nooped_plural( $nooped_plural, 2, $text_domain ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35961
	 */
	function test_nx_noop() {
		$text_domain   = 'text-domain';
		$nooped_plural = _nx_noop( '%s post', '%s posts', 'my-context', $text_domain );

		$this->assertNotEmpty( $nooped_plural['domain'] );
		$this->assertNotEmpty( $nooped_plural['context'] );
		$this->assertSame( '%s posts', translate_nooped_plural( $nooped_plural, 0, $text_domain ) );
		$this->assertSame( '%s post', translate_nooped_plural( $nooped_plural, 1, $text_domain ) );
		$this->assertSame( '%s posts', translate_nooped_plural( $nooped_plural, 2, $text_domain ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35073
	 */
	function test_before_last_bar() {
		$this->assertSame( 'no-bar-at-all', before_last_bar( 'no-bar-at-all' ) );
		$this->assertSame( 'before-last-bar', before_last_bar( 'before-last-bar|after-bar' ) );
		$this->assertSame( 'first-before-bar|second-before-bar', before_last_bar( 'first-before-bar|second-before-bar|after-last-bar' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35950
	 */
	function test_get_available_languages() {
		$array = get_available_languages();
		$this->assertIsArray( $array );

		$array = get_available_languages( '.' );
		$this->assertEmpty( $array );

		$array = get_available_languages( DIR_TESTDATA . '/languages/' );
		$this->assertSame( array( 'de_DE', 'en_GB', 'es_ES' ), $array );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35284
	 */
	function test_wp_get_installed_translations_for_core() {
		$installed_translations = wp_get_installed_translations( 'core' );
		$this->assertIsArray( $installed_translations );
		$textdomains_expected = array( 'admin', 'admin-network', 'continents-cities', 'default' );
		$this->assertSameSets( $textdomains_expected, array_keys( $installed_translations ) );

		$this->assertNotEmpty( $installed_translations['default']['en_GB'] );
		$data_en_gb = $installed_translations['default']['en_GB'];
		$this->assertSame( '2016-10-26 00:01+0200', $data_en_gb['PO-Revision-Date'] );
		$this->assertSame( 'Development (4.4.x)', $data_en_gb['Project-Id-Version'] );
		$this->assertSame( 'Poedit 1.8.10', $data_en_gb['X-Generator'] );

		$this->assertNotEmpty( $installed_translations['admin']['es_ES'] );
		$data_es_es = $installed_translations['admin']['es_ES'];
		$this->assertSame( '2016-10-25 18:29+0200', $data_es_es['PO-Revision-Date'] );
		$this->assertSame( 'Administration', $data_es_es['Project-Id-Version'] );
		$this->assertSame( 'Poedit 1.8.10', $data_es_es['X-Generator'] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35294
	 */
	function test_wp_dropdown_languages() {
		$args   = array(
			'id'           => 'foo',
			'name'         => 'bar',
			'languages'    => array( 'de_DE' ),
			'translations' => $this->wp_dropdown_languages_filter(),
			'selected'     => 'de_DE',
			'echo'         => false,
		);
		$actual = wp_dropdown_languages( $args );

		$this->assertStringContainsString( 'id="foo"', $actual );
		$this->assertStringContainsString( 'name="bar"', $actual );
		$this->assertStringContainsString( '<option value="" lang="en" data-installed="1">English (United States)</option>', $actual );
		$this->assertStringContainsString( '<option value="de_DE" lang="de" selected=\'selected\' data-installed="1">Deutsch</option>', $actual );
		$this->assertStringContainsString( '<option value="it_IT" lang="it">Italiano</option>', $actual );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38632
	 */
	function test_wp_dropdown_languages_site_default() {
		$args   = array(
			'id'                       => 'foo',
			'name'                     => 'bar',
			'languages'                => array( 'de_DE' ),
			'translations'             => $this->wp_dropdown_languages_filter(),
			'selected'                 => 'de_DE',
			'echo'                     => false,
			'show_option_site_default' => true,
		);
		$actual = wp_dropdown_languages( $args );

		$this->assertStringContainsString( 'id="foo"', $actual );
		$this->assertStringContainsString( 'name="bar"', $actual );
		$this->assertStringContainsString( '<option value="site-default" data-installed="1">Site Default</option>', $actual );
		$this->assertStringContainsString( '<option value="" lang="en" data-installed="1">English (United States)</option>', $actual );
		$this->assertStringContainsString( '<option value="de_DE" lang="de" selected=\'selected\' data-installed="1">Deutsch</option>', $actual );
		$this->assertStringContainsString( '<option value="it_IT" lang="it">Italiano</option>', $actual );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38632
	 */
	function test_wp_dropdown_languages_en_US_selected() {
		$args   = array(
			'id'           => 'foo',
			'name'         => 'bar',
			'languages'    => array( 'de_DE' ),
			'translations' => $this->wp_dropdown_languages_filter(),
			'selected'     => 'en_US',
			'echo'         => false,
		);
		$actual = wp_dropdown_languages( $args );

		$this->assertStringContainsString( 'id="foo"', $actual );
		$this->assertStringContainsString( 'name="bar"', $actual );
		$this->assertStringContainsString( '<option value="" lang="en" data-installed="1" selected=\'selected\'>English (United States)</option>', $actual );
		$this->assertStringContainsString( '<option value="de_DE" lang="de" data-installed="1">Deutsch</option>', $actual );
		$this->assertStringContainsString( '<option value="it_IT" lang="it">Italiano</option>', $actual );
	}

	/**
	 * We don't want to call the API when testing.
	 *
	 * @return array
	 */
	function wp_dropdown_languages_filter() {
		return array(
			'de_DE' => array(
				'language'    => 'de_DE',
				'native_name' => 'Deutsch',
				'iso'         => array( 'de' ),
			),
			'it_IT' => array(
				'language'    => 'it_IT',
				'native_name' => 'Italiano',
				'iso'         => array( 'it', 'ita' ),
			),
		);
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35284
	 */
	function test_wp_get_pomo_file_data() {
		$file  = DIR_TESTDATA . '/pomo/empty.po';
		$array = wp_get_pomo_file_data( $file );
		$this->assertArrayHasKey( 'POT-Creation-Date', $array );
		$this->assertArrayHasKey( 'PO-Revision-Date', $array );
		$this->assertArrayHasKey( 'Project-Id-Version', $array );
		$this->assertArrayHasKey( 'X-Generator', $array );

		$file  = DIR_TESTDATA . '/pomo/mo.pot';
		$array = wp_get_pomo_file_data( $file );
		$this->assertNotEmpty( $array['POT-Creation-Date'] );
		$this->assertNotEmpty( $array['PO-Revision-Date'] );
		$this->assertNotEmpty( $array['Project-Id-Version'] );
		$this->assertArrayHasKey( 'X-Generator', $array );

		$file  = DIR_TESTDATA . '/languages/es_ES.po';
		$array = wp_get_pomo_file_data( $file );
		$this->assertArrayHasKey( 'POT-Creation-Date', $array );
		$this->assertNotEmpty( $array['PO-Revision-Date'] );
		$this->assertNotEmpty( $array['Project-Id-Version'] );
		$this->assertNotEmpty( $array['X-Generator'] );
	}
<<<<<<< HEAD
=======

	/**
	 * @ticket 44541
	 */
	public function test_length_of_excerpt_should_be_counted_by_words() {
		global $post;

		switch_to_locale( 'en_US' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
		);

		$post = $this->factory()->post->create_and_get( $args );
		setup_postdata( $post );

		$expect = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat [&hellip;]</p>\n";
		the_excerpt();

		restore_previous_locale();

		$this->expectOutputString( $expect );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_excerpt_should_be_counted_by_chars() {
		global $post;

		switch_to_locale( 'ja_JP' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
		);

		$post = $this->factory()->post->create_and_get( $args );
		setup_postdata( $post );

		$expect = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore  [&hellip;]</p>\n";
		the_excerpt();

		restore_previous_locale();

		$this->expectOutputString( $expect );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_excerpt_should_be_counted_by_chars_in_japanese() {
		global $post;

		switch_to_locale( 'ja_JP' );

		$args = array(
			'post_content' => str_repeat( 'あ', 200 ),
			'post_excerpt' => '',
		);

		$post = $this->factory()->post->create_and_get( $args );
		setup_postdata( $post );

		$expect = '<p>' . str_repeat( 'あ', 110 ) . " [&hellip;]</p>\n";
		the_excerpt();

		restore_previous_locale();

		$this->expectOutputString( $expect );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_excerpt_rss_should_be_counted_by_words() {
		global $post;

		switch_to_locale( 'en_US' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
		);

		$post = $this->factory()->post->create_and_get( $args );
		setup_postdata( $post );

		$expect = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat [&#8230;]';
		the_excerpt_rss();

		restore_previous_locale();

		$this->expectOutputString( $expect );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_excerpt_rss_should_be_counted_by_chars() {
		global $post;

		switch_to_locale( 'ja_JP' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
		);

		$post = $this->factory()->post->create_and_get( $args );
		setup_postdata( $post );

		$expect = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore  [&#8230;]';

		the_excerpt_rss();

		restore_previous_locale();

		$this->expectOutputString( $expect );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_draft_should_be_counted_by_words() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		switch_to_locale( 'en_US' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
			'post_status'  => 'draft',
		);

		$this->factory()->post->create( $args );

		// Effectively ignore the output until retrieving it later via `getActualOutput()`.
		$this->expectOutputRegex( '`.`' );

		$expect = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do&hellip;';
		wp_dashboard_recent_drafts();

		$actual = $this->getActualOutput();

		restore_previous_locale();

		$this->assertMatchesRegularExpression( '/' . $expect . '/', $actual );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_draft_should_be_counted_by_chars() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		switch_to_locale( 'ja_JP' );

		$args = array(
			'post_content' => $this->long_text,
			'post_excerpt' => '',
			'post_status'  => 'draft',
		);

		$post = $this->factory()->post->create( $args );

		// Effectively ignore the output until retrieving it later via `getActualOutput()`.
		$this->expectOutputRegex( '`.`' );

		$expect = 'Lorem ipsum dolor sit amet, consectetur &hellip;';
		wp_dashboard_recent_drafts();

		$actual = $this->getActualOutput();

		restore_previous_locale();

		$this->assertMatchesRegularExpression( '/' . $expect . '/', $actual );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_draft_should_be_counted_by_chars_in_japanese() {
		require_once ABSPATH . 'wp-admin/includes/dashboard.php';

		switch_to_locale( 'ja_JP' );

		$args = array(
			'post_content' => str_repeat( 'あ', 200 ),
			'post_excerpt' => '',
			'post_status'  => 'draft',
		);

		$this->factory()->post->create( $args );

		// Effectively ignore the output until retrieving it later via `getActualOutput()`.
		$this->expectOutputRegex( '`.`' );

		$expect = str_repeat( 'あ', 40 ) . '&hellip;';
		wp_dashboard_recent_drafts();

		$actual = $this->getActualOutput();

		restore_previous_locale();

		$this->assertMatchesRegularExpression( '/' . $expect . '/', $actual );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_comment_excerpt_should_be_counted_by_words() {
		switch_to_locale( 'en_US' );

		$args            = array(
			'comment_content' => $this->long_text,
		);
		$comment_id      = $this->factory()->comment->create( $args );
		$expect          = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut&hellip;';
		$comment_excerpt = get_comment_excerpt( $comment_id );

		restore_previous_locale();

		$this->assertSame( $expect, $comment_excerpt );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_comment_excerpt_should_be_counted_by_chars() {
		switch_to_locale( 'ja_JP' );

		$args            = array(
			'comment_content' => $this->long_text,
		);
		$comment_id      = $this->factory()->comment->create( $args );
		$expect          = 'Lorem ipsum dolor sit amet, consectetur &hellip;';
		$comment_excerpt = get_comment_excerpt( $comment_id );

		restore_previous_locale();

		$this->assertSame( $expect, $comment_excerpt );
	}

	/**
	 * @ticket 44541
	 */
	public function test_length_of_comment_excerpt_should_be_counted_by_chars_in_Japanese() {
		switch_to_locale( 'ja_JP' );

		$args            = array(
			'comment_content' => str_repeat( 'あ', 200 ),
		);
		$comment_id      = $this->factory()->comment->create( $args );
		$expect          = str_repeat( 'あ', 40 ) . '&hellip;';
		$comment_excerpt = get_comment_excerpt( $comment_id );

		restore_previous_locale();

		$this->assertSame( $expect, $comment_excerpt );
	}
>>>>>>> 6717df2b48 (Tests: Remove unexpected output in `wp_dashboard_recent_drafts()` tests on PHP 8.1.)
}
