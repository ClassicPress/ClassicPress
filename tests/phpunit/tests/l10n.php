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
	 * * @see https://core.trac.wordpress.org/ticket/38632
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
}
