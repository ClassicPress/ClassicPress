<?php

/**
 * Tests get_theme_starter_content().
 *
 * @group themes
 */
class Tests_Theme_GetThemeStarterContent extends WP_UnitTestCase {

	/**
	 * Testing passing an empty array as starter content.
	 */
	public function test_add_theme_support_empty() {
		add_theme_support( 'starter-content', array() );
		$starter_content = get_theme_starter_content();

		$this->assertEmpty( $starter_content );
	}

	/**
	 * Testing passing nothing as starter content.
	 */
	public function test_add_theme_support_single_param() {
		add_theme_support( 'starter-content' );
		$starter_content = get_theme_starter_content();

		$this->assertEmpty( $starter_content );
	}

	/**
	 * Testing that placeholder starter content gets expanded, that unrecognized placeholders are discarded, and that custom items are recognized.
	 */
	public function test_default_content_sections() {
		/*
		 * All placeholder identifiers should be referenced in this sample starter
		 * content and then tested to ensure they get hydrated in the call to
		 * get_theme_starter_content() to ensure that the starter content
		 * placeholder identifiers remain intact in core.
		 */
		$dehydrated_starter_content = array(
			'widgets'     => array(
				'sidebar-1' => array(
					'text_business_info',
					'text_about'  => array(
						'title' => 'Our Story',
					),
					'archives',
					'calendar',
					'categories',
					'meta',
					'recent-comments',
					'recent-posts',
					'search',
					'unknown',
					'meta_custom' => array(
						'meta',
						array(
							'title' => 'Pre-hydrated meta widget.',
						),
					),
				),
			),
			'nav_menus'   => array(
				'top' => array(
					'name'  => 'Menu Name',
					'items' => array(
						'page_home',
						'page_about',
						'page_blog',
						'page_news',
						'page_contact' => array(
							'title' => 'Email Us',
						),
						'link_email',
						'link_facebook',
						'link_foursquare',
						'link_github',
						'link_instagram',
						'link_linkedin',
						'link_pinterest',
						'link_twitter',
						'link_yelp',
						'link_youtube',
						'link_unknown',
						'link_custom'  => array(
							'title' => 'Custom',
							'url'   => 'https://custom.example.com/',
						),
					),
				),
			),
			'posts'       => array(
				'home',
				'about',
				'contact',
				'blog'   => array(
					'template'     => 'blog.php',
					'post_excerpt' => 'Extended',
				),
				'news',
				'homepage-section',
				'unknown',
				'custom' => array(
					'post_type'  => 'post',
					'post_title' => 'Custom',
					'thumbnail'  => '{{featured-image-logo}}',
				),
			),
			'attachments' => array(
				'featured-image-logo'    => array(
					'post_title'   => 'Title',
					'post_content' => 'Description',
					'post_excerpt' => 'Caption',
					'file'         => DIR_TESTDATA . '/images/waffles.jpg',
				),
				'featured-image-skipped' => array(
					'post_title' => 'Skipped',
				),
			),
			'options'     => array(
				'show_on_front'  => 'page',
				'page_on_front'  => '{{home}}',
				'page_for_posts' => '{{blog}}',
			),
			'theme_mods'  => array(
				'panel_1' => '{{homepage-section}}',
				'panel_2' => '{{about}}',
				'panel_3' => '{{blog}}',
				'panel_4' => '{{contact}}',
			),
		);

		add_theme_support( 'starter-content', $dehydrated_starter_content );

		$hydrated_starter_content = get_theme_starter_content();
		$this->assertSame( $hydrated_starter_content['theme_mods'], $dehydrated_starter_content['theme_mods'] );
		$this->assertSame( $hydrated_starter_content['options'], $dehydrated_starter_content['options'] );
		$this->assertCount( 16, $hydrated_starter_content['nav_menus']['top']['items'], 'Unknown should be dropped, custom should be present.' );
		$this->assertCount( 10, $hydrated_starter_content['widgets']['sidebar-1'], 'Unknown should be dropped.' );
		$this->assertCount( 1, $hydrated_starter_content['attachments'], 'Attachment with missing file is filtered out.' );
		$this->assertArrayHasKey( 'featured-image-logo', $hydrated_starter_content['attachments'] );
		$this->assertSame( $dehydrated_starter_content['attachments']['featured-image-logo'], $hydrated_starter_content['attachments']['featured-image-logo'] );

		foreach ( $hydrated_starter_content['widgets']['sidebar-1'] as $widget ) {
			$this->assertIsArray( $widget );
			$this->assertCount( 2, $widget );
			$this->assertIsString( $widget[0] );
			$this->assertIsArray( $widget[1] );
			$this->assertArrayHasKey( 'title', $widget[1] );
		}
		$this->assertSame( 'text', $hydrated_starter_content['widgets']['sidebar-1'][1][0], 'Core content extended' );
		$this->assertSame( 'Our Story', $hydrated_starter_content['widgets']['sidebar-1'][1][1]['title'], 'Core content extended' );

		foreach ( $hydrated_starter_content['nav_menus']['top']['items'] as $nav_menu_item ) {
			$this->assertIsArray( $nav_menu_item );
			$this->assertTrue( ! empty( $nav_menu_item['object_id'] ) || ! empty( $nav_menu_item['url'] ) );
		}
		$this->assertSame( 'Email Us', $hydrated_starter_content['nav_menus']['top']['items'][4]['title'], 'Core content extended' );

		foreach ( $hydrated_starter_content['posts'] as $key => $post ) {
			$this->assertIsString( $key );
			$this->assertIsNotNumeric( $key );
			$this->assertIsArray( $post );
			$this->assertArrayHasKey( 'post_type', $post );
			$this->assertArrayHasKey( 'post_title', $post );
		}
		$this->assertSame( 'Extended', $hydrated_starter_content['posts']['blog']['post_excerpt'], 'Core content extended' );
		$this->assertSame( 'blog.php', $hydrated_starter_content['posts']['blog']['template'], 'Core content extended' );
		$this->assertSame( '{{featured-image-logo}}', $hydrated_starter_content['posts']['custom']['thumbnail'], 'Core content extended' );
	}

	/**
	 * Testing the filter with the text_credits widget.
	 */
	public function test_get_theme_starter_content_filter() {

		add_theme_support(
			'starter-content',
			array(
				'widgets' => array(
					'sidebar-1' => array(
						'text_about',
					),
				),
			)
		);

		add_filter( 'get_theme_starter_content', array( $this, 'filter_theme_starter_content' ), 10, 2 );
		$starter_content = get_theme_starter_content();

		$this->assertCount( 2, $starter_content['widgets']['sidebar-1'] );
		$this->assertSame( 'Filtered Widget', $starter_content['widgets']['sidebar-1'][1][1]['title'] );
	}

	/**
	 * Filter the append a widget starter content.
	 *
	 * @param array $content Starter content (hydrated).
	 * @param array $config  Starter content config (pre-hydrated).
	 * @return array Filtered starter content.
	 */
	public function filter_theme_starter_content( $content, $config ) {
		$this->assertIsArray( $config );
		$this->assertCount( 1, $config['widgets']['sidebar-1'] );
		$content['widgets']['sidebar-1'][] = array(
			'text',
			array(
				'title' => 'Filtered Widget',
				'text'  => 'Custom ',
			),
		);
		return $content;
	}
}
