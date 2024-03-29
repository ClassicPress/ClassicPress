<?php

/**
 * test wp-includes/theme.php
 *
 * @group themes
 */
class Tests_Theme extends WP_UnitTestCase {
	protected $theme_slug     = 'twentyseventeen';
	protected $theme_name     = 'Twenty Seventeen';
	protected $default_themes = array(
		'twentyseventeen',
		'classicpress-seventeen',
		'the-classicpress-theme',
	);

	/**
	 * Original theme directory.
	 *
	 * @var string[]
	 */
	private $orig_theme_dir;

	public function set_up() {
		global $wp_theme_directories;

		parent::set_up();

		$this->orig_theme_dir = $wp_theme_directories;
		$wp_theme_directories = array( WP_CONTENT_DIR . '/themes' );

		add_filter( 'extra_theme_headers', array( $this, 'theme_data_extra_headers' ) );
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
	}

	public function tear_down() {
		global $wp_theme_directories;

		$wp_theme_directories = $this->orig_theme_dir;

		remove_filter( 'extra_theme_headers', array( $this, 'theme_data_extra_headers' ) );
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );

		parent::tear_down();
	}

	public function test_wp_get_themes_default() {
		$themes = wp_get_themes();
		$this->assertInstanceOf( 'WP_Theme', $themes[ $this->theme_slug ] );
		$this->assertSame( $this->theme_name, $themes[ $this->theme_slug ]->get( 'Name' ) );

		$single_theme = wp_get_theme( $this->theme_slug );
		$this->assertSame( $single_theme->get( 'Name' ), $themes[ $this->theme_slug ]->get( 'Name' ) );
		$this->assertEquals( $themes[ $this->theme_slug ], $single_theme );
	}

	/**
	 * @expectedDeprecated get_theme
	 * @expectedDeprecated get_themes
	 */
	public function test_get_themes_default() {
		$themes = get_themes();
		$this->assertInstanceOf( 'WP_Theme', $themes[ $this->theme_name ] );
		$this->assertSame( $themes[ $this->theme_name ], get_theme( $this->theme_name ) );

		$this->assertSame( $this->theme_name, $themes[ $this->theme_name ]['Name'] );
		$this->assertSame( $this->theme_name, $themes[ $this->theme_name ]->Name );
		$this->assertSame( $this->theme_name, $themes[ $this->theme_name ]->name );
	}

	/**
	 * @expectedDeprecated get_theme
	 * @expectedDeprecated get_themes
	 */
	public function test_get_theme() {
		$themes = get_themes();
		foreach ( array_keys( $themes ) as $name ) {
			$theme = get_theme( $name );
			// WP_Theme implements ArrayAccess. Even ArrayObject returns false for is_array().
			$this->assertFalse( is_array( $theme ) );
			$this->assertInstanceOf( 'WP_Theme', $theme );
			$this->assertSame( $theme, $themes[ $name ] );
		}
	}

	public function test_wp_get_theme() {
		$themes = wp_get_themes();
		foreach ( $themes as $theme ) {
			$this->assertInstanceOf( 'WP_Theme', $theme );
			$this->assertFalse( $theme->errors() );
			$_theme = wp_get_theme( $theme->get_stylesheet() );
			// This primes internal WP_Theme caches for the next assertion (headers_sanitized, textdomain_loaded).
			$this->assertSame( $theme->get( 'Name' ), $_theme->get( 'Name' ) );
			$this->assertEquals( $theme, $_theme );
		}
	}

	/**
	 * @expectedDeprecated get_themes
	 */
	public function test_get_themes_contents() {
		$themes = get_themes();
		// Generic tests that should hold true for any theme.
		foreach ( $themes as $k => $theme ) {
			// Don't run these checks for custom themes.
			if ( empty( $theme['Author'] ) || false === strpos( $theme['Author'], 'WordPress' ) ) {
				continue;
			}

			$this->assertSame( $theme['Name'], $k );
			$this->assertNotEmpty( $theme['Title'] );

			// Important attributes should all be set.
			$default_headers = array(
				'Title'          => 'Theme Title',
				'Version'        => 'Version',
				'Parent Theme'   => 'Parent Theme',
				'Template Dir'   => 'Template Dir',
				'Stylesheet Dir' => 'Stylesheet Dir',
				'Template'       => 'Template',
				'Stylesheet'     => 'Stylesheet',
				'Screenshot'     => 'Screenshot',
				'Description'    => 'Description',
				'Author'         => 'Author',
				'Tags'           => 'Tags',
				// Introduced in WordPress 2.9.
				'Theme Root'     => 'Theme Root',
				'Theme Root URI' => 'Theme Root URI',
			);
			foreach ( $default_headers as $name => $value ) {
				$this->assertArrayHasKey( $name, $theme );
			}

			// Make the tests work both for WordPress 2.8.5 and WordPress 2.9-rare.
			$dir = isset( $theme['Theme Root'] ) ? '' : WP_CONTENT_DIR;

			// Important attributes should all not be empty as well.
			$this->assertNotEmpty( $theme['Description'] );
			$this->assertNotEmpty( $theme['Author'] );
			$this->assertGreaterThan( 0, version_compare( $theme['Version'], 0 ) );
			$this->assertNotEmpty( $theme['Template'] );
			$this->assertNotEmpty( $theme['Stylesheet'] );

			// Template files should all exist.
			$this->assertIsArray( $theme['Template Files'] );
			$this->assertNotEmpty( $theme['Template Files'] );
			foreach ( $theme['Template Files'] as $file ) {
				$this->assertFileIsReadable( $dir . $file );
			}

			// CSS files should all exist.
			$this->assertIsArray( $theme['Stylesheet Files'] );
			$this->assertNotEmpty( $theme['Stylesheet Files'] );
			foreach ( $theme['Stylesheet Files'] as $file ) {
				$this->assertFileIsReadable( $dir . $file );
			}

			$this->assertDirectoryExists( $dir . $theme['Template Dir'] );
			$this->assertDirectoryExists( $dir . $theme['Stylesheet Dir'] );

			$this->assertSame( 'publish', $theme['Status'] );

			$this->assertFileIsReadable( $dir . $theme['Stylesheet Dir'] . '/' . $theme['Screenshot'] );
		}
	}

	public function test_wp_get_theme_contents() {
		$theme = wp_get_theme( $this->theme_slug );

		$this->assertSame( $this->theme_name, $theme->get( 'Name' ) );
		$this->assertNotEmpty( $theme->get( 'Description' ) );
		$this->assertNotEmpty( $theme->get( 'Author' ) );
		$this->assertNotEmpty( $theme->get( 'Version' ) );
		$this->assertNotEmpty( $theme->get( 'AuthorURI' ) );
		$this->assertNotEmpty( $theme->get( 'ThemeURI' ) );
		$this->assertSame( $this->theme_slug, $theme->get_stylesheet() );
		$this->assertSame( $this->theme_slug, $theme->get_template() );

		$this->assertSame( 'publish', $theme->get( 'Status' ) );

		$this->assertSame( WP_CONTENT_DIR . '/themes/' . $this->theme_slug, $theme->get_stylesheet_directory(), 'get_stylesheet_directory' );
		$this->assertSame( WP_CONTENT_DIR . '/themes/' . $this->theme_slug, $theme->get_template_directory(), 'get_template_directory' );
		$this->assertSame( content_url( 'themes/' . $this->theme_slug ), $theme->get_stylesheet_directory_uri(), 'get_stylesheet_directory_uri' );
		$this->assertSame( content_url( 'themes/' . $this->theme_slug ), $theme->get_template_directory_uri(), 'get_template_directory_uri' );
	}

	/**
	 * Make sure we update the default theme list to include the latest default theme.
	 *
	 * @ticket 29925
	 */
	public function test_default_theme_in_default_theme_list() {
		$latest_default_theme = WP_Theme::get_core_default_theme();
		if ( ! $latest_default_theme->exists() || 'twenty' !== substr( $latest_default_theme->get_stylesheet(), 0, 6 ) ) {
			$this->fail( 'No Twenty* series default themes are installed.' );
		}
		$this->assertContains( $latest_default_theme->get_stylesheet(), $this->default_themes );
	}

	public function test_default_themes_have_textdomain() {
		foreach ( $this->default_themes as $theme ) {
			if ( wp_get_theme( $theme )->exists() ) {
				$this->assertSame( $theme, wp_get_theme( $theme )->get( 'TextDomain' ) );
			}
		}
	}

	/**
	 * @ticket 20897
	 * @expectedDeprecated get_theme_data
	 */
	public function test_extra_theme_headers() {
		$wp_theme = wp_get_theme( $this->theme_slug );
		$this->assertNotEmpty( $wp_theme->get( 'License' ) );
		$path_to_style_css = $wp_theme->get_theme_root() . '/' . $wp_theme->get_stylesheet() . '/style.css';
		$this->assertFileExists( $path_to_style_css );
		$theme_data = get_theme_data( $path_to_style_css );
		$this->assertArrayHasKey( 'License', $theme_data );
		$this->assertArrayNotHasKey( 'Not a Valid Key', $theme_data );
		$this->assertNotEmpty( $theme_data['License'] );
		$this->assertSame( $theme_data['License'], $wp_theme->get( 'License' ) );
	}

	public function theme_data_extra_headers() {
		return array( 'License' );
	}

	/**
	 * @expectedDeprecated get_themes
	 * @expectedDeprecated get_current_theme
	 */
	public function test_switch_theme() {
		$themes = get_themes();

		if ( PHP_VERSION_ID < 80000 ) {
			unset( $themes['The ClassicPress Theme'] );
		}

		// Switch to each theme in sequence.
		// Do it twice to make sure we switch to the first theme, even if it's our starting theme.
		// Do it a third time to ensure switch_theme() works with one argument.

		for ( $i = 0; $i < 3; $i++ ) {
			foreach ( $themes as $name => $theme ) {
				// Switch to this theme.
				if ( 2 === $i ) {
					switch_theme( $theme['Template'], $theme['Stylesheet'] );
				} else {
					switch_theme( $theme['Stylesheet'] );
				}

				$this->assertSame( $name, get_current_theme() );

				// Make sure the various get_* functions return the correct values.
				$this->assertSame( $theme['Template'], get_template() );
				$this->assertSame( $theme['Stylesheet'], get_stylesheet() );

				$root_fs = get_theme_root();
				$this->assertTrue( is_dir( $root_fs ) );

				$root_uri = get_theme_root_uri();
				$this->assertNotEmpty( $root_uri );

				$this->assertSame( $root_fs . '/' . get_stylesheet(), get_stylesheet_directory() );
				$this->assertSame( $root_uri . '/' . get_stylesheet(), get_stylesheet_directory_uri() );
				$this->assertSame( $root_uri . '/' . get_stylesheet() . '/style.css', get_stylesheet_uri() );
				// $this->assertSame( $root_uri . '/' . get_stylesheet(), get_locale_stylesheet_uri() );

				$this->assertSame( $root_fs . '/' . get_template(), get_template_directory() );
				$this->assertSame( $root_uri . '/' . get_template(), get_template_directory_uri() );

				// get_query_template()

				// Template file that doesn't exist.
				$this->assertSame( '', get_query_template( 'nonexistant' ) );

				// Template files that do exist.
				/*
				foreach ( $theme['Template Files'] as $path ) {
					$file = basename($path, '.php');
					FIXME: untestable because get_query_template() uses TEMPLATEPATH.
					$this->assertSame('', get_query_template($file));
				}
				*/

				// These are kind of tautologies but at least exercise the code.
				$this->assertSame( get_404_template(), get_query_template( '404' ) );
				$this->assertSame( get_archive_template(), get_query_template( 'archive' ) );
				$this->assertSame( get_author_template(), get_query_template( 'author' ) );
				$this->assertSame( get_category_template(), get_query_template( 'category' ) );
				$this->assertSame( get_date_template(), get_query_template( 'date' ) );
				$this->assertSame( get_home_template(), get_query_template( 'home', array( 'home.php', 'index.php' ) ) );
				$this->assertSame( get_privacy_policy_template(), get_query_template( 'privacy_policy', array( 'privacy-policy.php' ) ) );
				$this->assertSame( get_page_template(), get_query_template( 'page' ) );
				$this->assertSame( get_search_template(), get_query_template( 'search' ) );
				$this->assertSame( get_single_template(), get_query_template( 'single' ) );
				$this->assertSame( get_attachment_template(), get_query_template( 'attachment' ) );

				$this->assertSame( get_tag_template(), get_query_template( 'tag' ) );

				// nb: This probably doesn't run because WP_INSTALLING is defined.
				$this->assertTrue( validate_current_theme() );
			}
		}
	}

	public function test_switch_theme_bogus() {
		// Try switching to a theme that doesn't exist.
		$template = 'some_template';
		$style    = 'some_style';
		update_option( 'template', $template );
		update_option( 'stylesheet', $style );

		$theme = wp_get_theme();
		$this->assertSame( $style, (string) $theme );
		$this->assertNotFalse( $theme->errors() );
		$this->assertFalse( $theme->exists() );

		// These return the bogus name - perhaps not ideal behavior?
		$this->assertSame( $template, get_template() );
		$this->assertSame( $style, get_stylesheet() );
	}

	/**
	 * Test _wp_keep_alive_customize_changeset_dependent_auto_drafts.
	 *
	 * @covers ::_wp_keep_alive_customize_changeset_dependent_auto_drafts
	 */
	public function test_wp_keep_alive_customize_changeset_dependent_auto_drafts() {
		$nav_created_post_ids = self::factory()->post->create_many(
			2,
			array(
				'post_status' => 'auto-draft',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
			)
		);
		$data                 = array(
			'nav_menus_created_posts' => array(
				'value' => $nav_created_post_ids,
			),
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$wp_customize = new WP_Customize_Manager();
		do_action( 'customize_register', $wp_customize );

		// The post_date for auto-drafts is bumped to match the changeset post_date whenever it is modified
		// to keep them from from being garbage collected by wp_delete_auto_drafts().
		$wp_customize->save_changeset_post(
			array(
				'data' => $data,
			)
		);
		$this->assertSame( get_post( $wp_customize->changeset_post_id() )->post_date, get_post( $nav_created_post_ids[0] )->post_date );
		$this->assertSame( get_post( $wp_customize->changeset_post_id() )->post_date, get_post( $nav_created_post_ids[1] )->post_date );
		$this->assertSame( 'auto-draft', get_post_status( $nav_created_post_ids[0] ) );
		$this->assertSame( 'auto-draft', get_post_status( $nav_created_post_ids[1] ) );

		// Stubs transition to drafts when changeset is saved as a draft.
		$wp_customize->save_changeset_post(
			array(
				'status' => 'draft',
				'data'   => $data,
			)
		);
		$this->assertSame( 'draft', get_post_status( $nav_created_post_ids[0] ) );
		$this->assertSame( 'draft', get_post_status( $nav_created_post_ids[1] ) );

		// Status remains unchanged for stub that the user broke out of the changeset.
		wp_update_post(
			array(
				'ID'          => $nav_created_post_ids[1],
				'post_status' => 'private',
			)
		);
		$wp_customize->save_changeset_post(
			array(
				'status' => 'draft',
				'data'   => $data,
			)
		);
		$this->assertSame( 'draft', get_post_status( $nav_created_post_ids[0] ) );
		$this->assertSame( 'private', get_post_status( $nav_created_post_ids[1] ) );

		// Draft stub is trashed when the changeset is trashed.
		$wp_customize->trash_changeset_post( $wp_customize->changeset_post_id() );
		$this->assertSame( 'trash', get_post_status( $nav_created_post_ids[0] ) );
		$this->assertSame( 'private', get_post_status( $nav_created_post_ids[1] ) );
	}
}
