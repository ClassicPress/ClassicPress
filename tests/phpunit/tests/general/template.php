<?php
/**
 * A set of unit tests for functions in wp-includes/general-template.php
 *
 * @group general
 * @group template
 * @group site_icon
 */

require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php';

class Tests_General_Template extends WP_UnitTestCase {
	protected $wp_site_icon;
	public $site_icon_id;
	public $site_icon_url;

	public $custom_logo_id;
	public $custom_logo_url;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		/*
		 * Declare theme support for custom logo.
		 *
		 * This ensures that the `site_logo` option gets deleted in
		 * _delete_site_logo_on_remove_theme_mods(), which in turn
		 * prevents the `core/site-logo` block filters from affecting
		 * the custom logo tests.
		 *
		 * Alternatively, these filters can be removed instead:
		 *
		 *     remove_filter( 'theme_mod_custom_logo', '_override_custom_logo_theme_mod' );
		 *     remove_filter( 'pre_set_theme_mod_custom_logo', '_sync_custom_logo_to_site_logo' );
		 */
		add_theme_support( 'custom-logo' );
	}

	public static function wpTearDownAfterClass() {
		remove_theme_support( 'custom-logo' );
	}

	public function set_up() {
		parent::set_up();

		$this->wp_site_icon = new WP_Site_Icon();
	}

	public function tear_down() {
		global $wp_customize;
		$this->remove_custom_logo();
		$this->remove_site_icon();
		$wp_customize = null;

		parent::tear_down();
	}

	/**
	 * @group site_icon
	 * @covers ::get_site_icon_url
	 * @requires function imagejpeg
	 */
	public function test_get_site_icon_url() {
		$this->assertEmpty( get_site_icon_url(), 'Site icon URL should not be set initially.' );

		$this->set_site_icon();
		$this->assertSame( $this->site_icon_url, get_site_icon_url(), 'Site icon URL should be set.' );

		$this->remove_site_icon();
		$this->assertEmpty( get_site_icon_url(), 'Site icon URL should not be set after removal.' );
	}

	/**
	 * @group site_icon
	 * @covers ::site_icon_url
	 * @requires function imagejpeg
	 */
	public function test_site_icon_url() {
		$this->expectOutputString( '' );
		site_icon_url();

		$this->set_site_icon();
		$this->expectOutputString( $this->site_icon_url );
		site_icon_url();
	}

	/**
	 * @group site_icon
	 * @covers ::has_site_icon
	 * @requires function imagejpeg
	 */
	public function test_has_site_icon() {
		$this->assertFalse( has_site_icon(), 'Site icon should not be set initially.' );

		$this->set_site_icon();
		$this->assertTrue( has_site_icon(), 'Site icon should be set.' );

		$this->remove_site_icon();
		$this->assertFalse( has_site_icon(), 'Site icon should not be set after removal.' );
	}

	/**
	 * @group site_icon
	 * @group multisite
	 * @group ms-required
	 * @covers ::has_site_icon
	 */
	public function test_has_site_icon_returns_true_when_called_for_other_site_with_site_icon_set() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		$this->set_site_icon();
		restore_current_blog();

		$this->assertTrue( has_site_icon( $blog_id ) );
	}

	/**
	 * @group site_icon
	 * @group multisite
	 * @group ms-required
	 * @covers ::has_site_icon
	 */
	public function test_has_site_icon_returns_false_when_called_for_other_site_without_site_icon_set() {
		$blog_id = self::factory()->blog->create();

		$this->assertFalse( has_site_icon( $blog_id ) );
	}

	/**
	 * @group site_icon
	 * @covers ::wp_site_icon
	 * @requires function imagejpeg
	 */
	public function test_wp_site_icon() {
		$this->expectOutputString( '' );
		wp_site_icon();

		$this->set_site_icon();
		$output = array(
			sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( get_site_icon_url( 32 ) ) ),
			sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( get_site_icon_url( 192 ) ) ),
			sprintf( '<link rel="apple-touch-icon" href="%s" />', esc_url( get_site_icon_url( 180 ) ) ),
			sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( get_site_icon_url( 270 ) ) ),
			'',
		);
		$output = implode( "\n", $output );

		$this->expectOutputString( $output );
		wp_site_icon();
	}

	/**
	 * @group site_icon
	 * @covers ::wp_site_icon
	 * @requires function imagejpeg
	 */
	public function test_wp_site_icon_with_filter() {
		$this->expectOutputString( '' );
		wp_site_icon();

		$this->set_site_icon();
		$output = array(
			sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( get_site_icon_url( 32 ) ) ),
			sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( get_site_icon_url( 192 ) ) ),
			sprintf( '<link rel="apple-touch-icon" href="%s" />', esc_url( get_site_icon_url( 180 ) ) ),
			sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( get_site_icon_url( 270 ) ) ),
			sprintf( '<link rel="apple-touch-icon" sizes="150x150" href="%s" />', esc_url( get_site_icon_url( 150 ) ) ),
			'',
		);
		$output = implode( "\n", $output );

		$this->expectOutputString( $output );
		add_filter( 'site_icon_meta_tags', array( $this, 'custom_site_icon_meta_tag' ) );
		wp_site_icon();
		remove_filter( 'site_icon_meta_tags', array( $this, 'custom_site_icon_meta_tag' ) );
	}

	/**
	 * @ticket 38377
	 * @group site_icon
	 * @covers ::wp_site_icon
	 */
	public function test_customize_preview_wp_site_icon_empty() {
		global $wp_customize;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$wp_customize = new WP_Customize_Manager();
		$wp_customize->register_controls();
		$wp_customize->start_previewing_theme();

		$this->expectOutputString( '<link rel="icon" href="/favicon.ico" sizes="32x32" />' . "\n" );
		wp_site_icon();
	}

	/**
	 * @ticket 38377
	 * @group site_icon
	 * @covers ::wp_site_icon
	 */
	public function test_customize_preview_wp_site_icon_dirty() {
		global $wp_customize;
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$wp_customize = new WP_Customize_Manager();
		$wp_customize->register_controls();
		$wp_customize->start_previewing_theme();

		$attachment_id = $this->insert_attachment();
		$wp_customize->set_post_value( 'site_icon', $attachment_id );
		$wp_customize->get_setting( 'site_icon' )->preview();
		$output = array(
			sprintf( '<link rel="icon" href="%s" sizes="32x32" />', esc_url( wp_get_attachment_image_url( $attachment_id, 32 ) ) ),
			sprintf( '<link rel="icon" href="%s" sizes="192x192" />', esc_url( wp_get_attachment_image_url( $attachment_id, 192 ) ) ),
			sprintf( '<link rel="apple-touch-icon" href="%s" />', esc_url( wp_get_attachment_image_url( $attachment_id, 180 ) ) ),
			sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( wp_get_attachment_image_url( $attachment_id, 270 ) ) ),
			'',
		);
		$output = implode( "\n", $output );
		$this->expectOutputString( $output );
		wp_site_icon();
	}

	/**
	 * Builds and retrieves a custom site icon meta tag.
	 *
	 * @since 4.3.0
	 *
	 * @param $meta_tags
	 * @return array
	 */
	public function custom_site_icon_meta_tag( $meta_tags ) {
		$meta_tags[] = sprintf( '<link rel="apple-touch-icon" sizes="150x150" href="%s" />', esc_url( get_site_icon_url( 150 ) ) );

		return $meta_tags;
	}

	/**
	 * Sets a site icon in options for testing.
	 *
	 * @since 4.3.0
	 */
	private function set_site_icon() {
		if ( ! $this->site_icon_id ) {
			add_filter( 'intermediate_image_sizes_advanced', array( $this->wp_site_icon, 'additional_sizes' ) );
			$this->insert_attachment();
			remove_filter( 'intermediate_image_sizes_advanced', array( $this->wp_site_icon, 'additional_sizes' ) );
		}

		update_option( 'site_icon', $this->site_icon_id );
	}

	/**
	 * Removes the site icon from options.
	 *
	 * @since 4.3.0
	 */
	private function remove_site_icon() {
		delete_option( 'site_icon' );
	}

	/**
	 * Inserts an attachment for testing site icons.
	 *
	 * @since 4.3.0
	 */
	private function insert_attachment() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );

		$upload              = wp_upload_bits( wp_basename( $filename ), null, $contents );
		$this->site_icon_url = $upload['url'];

		// Save the data.
		$this->site_icon_id = $this->_make_attachment( $upload );
		return $this->site_icon_id;
	}

	/**
	 * @group custom_logo
	 * @covers ::has_custom_logo
	 *
	 * @since 4.5.0
	 */
	public function test_has_custom_logo() {
		$this->assertFalse( has_custom_logo(), 'Custom logo should not be set initially.' );

		$this->set_custom_logo();
		$this->assertTrue( has_custom_logo(), 'Custom logo should be set.' );

		$this->remove_custom_logo();
		$this->assertFalse( has_custom_logo(), 'Custom logo should not be set after removal.' );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 * @group ms-required
	 * @covers ::has_custom_logo
	 */
	public function test_has_custom_logo_returns_true_when_called_for_other_site_with_custom_logo_set() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );
		$this->set_custom_logo();
		restore_current_blog();

		$this->assertTrue( has_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 * @group ms-required
	 * @covers ::has_custom_logo
	 */
	public function test_has_custom_logo_returns_false_when_called_for_other_site_without_custom_logo_set() {
		$blog_id = self::factory()->blog->create();

		$this->assertFalse( has_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 * @covers ::get_custom_logo
	 *
	 * @since 4.5.0
	 */
	public function test_get_custom_logo() {
		$this->assertEmpty( get_custom_logo(), 'Custom logo should not be set initially.' );

		$this->set_custom_logo();
		$custom_logo = get_custom_logo();
		$this->assertNotEmpty( $custom_logo, 'Custom logo markup should not be empty.' );
		$this->assertIsString( $custom_logo, 'Custom logo markup should be a string.' );

		$this->remove_custom_logo();
		$this->assertEmpty( get_custom_logo(), 'Custom logo should not be set after removal.' );
	}

	/**
	 * @group custom_logo
	 * @group multisite
	 * @group ms-required
	 * @covers ::get_custom_logo
	 */
	public function test_get_custom_logo_returns_logo_when_called_for_other_site_with_custom_logo_set() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		$this->set_custom_logo();

		$custom_logo_attr = array(
			'class'   => 'custom-logo',
			'loading' => false,
		);

		// If the logo alt attribute is empty, use the site title.
		$image_alt = get_post_meta( $this->custom_logo_id, '_wp_attachment_image_alt', true );
		if ( empty( $image_alt ) ) {
			$custom_logo_attr['alt'] = get_bloginfo( 'name', 'display' );
		}

		$home_url = get_home_url( $blog_id, '/' );
		$image    = wp_get_attachment_image( $this->custom_logo_id, 'full', false, $custom_logo_attr );
		restore_current_blog();

		$expected_custom_logo = '<a href="' . $home_url . '" class="custom-logo-link" rel="home">' . $image . '</a>';
		$this->assertSame( $expected_custom_logo, get_custom_logo( $blog_id ) );
	}

	/**
	 * @group custom_logo
	 * @covers ::the_custom_logo
	 *
	 * @since 4.5.0
	 */
	public function test_the_custom_logo() {
		$this->expectOutputString( '' );
		the_custom_logo();

		$this->set_custom_logo();

		$custom_logo_attr = array(
			'class'   => 'custom-logo',
			'loading' => false,
		);

		// If the logo alt attribute is empty, use the site title.
		$image_alt = get_post_meta( $this->custom_logo_id, '_wp_attachment_image_alt', true );
		if ( empty( $image_alt ) ) {
			$custom_logo_attr['alt'] = get_bloginfo( 'name', 'display' );
		}

		$image = wp_get_attachment_image( $this->custom_logo_id, 'full', false, $custom_logo_attr );

		$this->expectOutputString( '<a href="http://' . WP_TESTS_DOMAIN . '/" class="custom-logo-link" rel="home">' . $image . '</a>' );
		the_custom_logo();
	}

	/**
	 * @ticket 38768
	 * @group custom_logo
	 * @covers ::the_custom_logo
	 */
	public function test_the_custom_logo_with_alt() {
		$this->set_custom_logo();

		$image_alt = 'My alt attribute';

		update_post_meta( $this->custom_logo_id, '_wp_attachment_image_alt', $image_alt );

		$image = wp_get_attachment_image(
			$this->custom_logo_id,
			'full',
			false,
			array(
				'class'   => 'custom-logo',
				'loading' => false,
			)
		);

		$this->expectOutputString( '<a href="http://' . WP_TESTS_DOMAIN . '/" class="custom-logo-link" rel="home">' . $image . '</a>' );
		the_custom_logo();
	}

	/**
	 * Sets a custom logo in options for testing.
	 *
	 * @since 4.5.0
	 */
	private function set_custom_logo() {
		if ( ! $this->custom_logo_id ) {
			$this->insert_custom_logo();
		}

		set_theme_mod( 'custom_logo', $this->custom_logo_id );
	}

	/**
	 * Removes the custom logo from options.
	 *
	 * @since 4.5.0
	 */
	private function remove_custom_logo() {
		remove_theme_mod( 'custom_logo' );
	}

	/**
	 * Inserts an attachment for testing custom logos.
	 *
	 * @since 4.5.0
	 */
	private function insert_custom_logo() {
		$filename = DIR_TESTDATA . '/images/test-image.jpg';
		$contents = file_get_contents( $filename );
		$upload   = wp_upload_bits( wp_basename( $filename ), null, $contents );

		// Save the data.
		$this->custom_logo_url = $upload['url'];
		$this->custom_logo_id  = $this->_make_attachment( $upload );
		return $this->custom_logo_id;
	}

	/**
	 * @ticket 38253
	 * @group ms-required
	 * @covers ::get_site_icon_url
	 */
	public function test_get_site_icon_url_preserves_switched_state() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		$expected = $GLOBALS['_wp_switched_stack'];

		get_site_icon_url( 512, '', $blog_id );

		$result = $GLOBALS['_wp_switched_stack'];

		restore_current_blog();

		$this->assertSame( $expected, $result );
	}

	/**
	 * @ticket 38253
	 * @group ms-required
	 * @covers ::has_custom_logo
	 */
	public function test_has_custom_logo_preserves_switched_state() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		$expected = $GLOBALS['_wp_switched_stack'];

		has_custom_logo( $blog_id );

		$result = $GLOBALS['_wp_switched_stack'];

		restore_current_blog();

		$this->assertSame( $expected, $result );
	}

	/**
	 * @ticket 38253
	 * @group ms-required
	 * @covers ::get_custom_logo
	 */
	public function test_get_custom_logo_preserves_switched_state() {
		$blog_id = self::factory()->blog->create();
		switch_to_blog( $blog_id );

		$expected = $GLOBALS['_wp_switched_stack'];

		get_custom_logo( $blog_id );

		$result = $GLOBALS['_wp_switched_stack'];

		restore_current_blog();

		$this->assertSame( $expected, $result );
	}

	/**
	 * @ticket 40969
	 *
	 * @covers ::get_header
	 */
	public function test_get_header_returns_nothing_on_success() {
		$this->expectOutputRegex( '/Header/' );

		// The `get_header()` function must not return anything
		// due to themes in the wild that may echo its return value.
		$this->assertNull( get_header() );
	}

	/**
	 * @ticket 40969
	 *
	 * @covers ::get_footer
	 */
	public function test_get_footer_returns_nothing_on_success() {
		$this->expectOutputRegex( '/Footer/' );

		// The `get_footer()` function must not return anything
		// due to themes in the wild that may echo its return value.
		$this->assertNull( get_footer() );
	}

	/**
	 * @ticket 40969
	 *
	 * @covers ::get_sidebar
	 */
	public function test_get_sidebar_returns_nothing_on_success() {
		$this->expectOutputRegex( '/Sidebar/' );

		// The `get_sidebar()` function must not return anything
		// due to themes in the wild that may echo its return value.
		$this->assertNull( get_sidebar() );
	}

	/**
	 * @ticket 40969
	 *
	 * @covers ::get_template_part
	 */
	public function test_get_template_part_returns_nothing_on_success() {
		$this->expectOutputRegex( '/Template Part/' );

		// The `get_template_part()` function must not return anything
		// due to themes in the wild that echo its return value.
		$this->assertNull( get_template_part( 'template', 'part' ) );
	}

	/**
	 * @ticket 40969
	 *
	 * @covers ::get_template_part
	 */
	public function test_get_template_part_returns_false_on_failure() {
		$this->assertFalse( get_template_part( 'non-existing-template' ) );
	}

	/**
	 * @ticket 21676
	 *
	 * @covers ::get_template_part
	 */
	public function test_get_template_part_passes_arguments_to_template() {
		$this->expectOutputRegex( '/{"foo":"baz"}/' );

		get_template_part( 'template', 'part', array( 'foo' => 'baz' ) );
	}

	/**
	 * @ticket 44183
	 *
	 * @covers ::get_the_archive_title
	 */
	public function test_get_the_archive_title_is_correct_for_author_queries() {
		$user_with_posts    = self::factory()->user->create_and_get(
			array(
				'role' => 'author',
			)
		);
		$user_with_no_posts = self::factory()->user->create_and_get(
			array(
				'role' => 'author',
			)
		);

		self::factory()->post->create(
			array(
				'post_author' => $user_with_posts->ID,
			)
		);

		// Simplify the assertion by removing the default archive title prefix:
		add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

		$this->go_to( get_author_posts_url( $user_with_posts->ID ) );
		$title_when_posts = get_the_archive_title();

		$this->go_to( get_author_posts_url( $user_with_no_posts->ID ) );
		$title_when_no_posts = get_the_archive_title();

		// Ensure the title is correct both when the user has posts and when they dont:
		$this->assertSame( $user_with_posts->display_name, $title_when_posts );
		$this->assertSame( $user_with_no_posts->display_name, $title_when_no_posts );
	}
}
