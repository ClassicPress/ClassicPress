<?php

/**
 * @group general
 * @group template
 * @ticket 34292
 * @covers ::wp_resource_hints
 */
class Tests_General_wpResourceHints extends WP_UnitTestCase {
	private $old_wp_scripts;
	private $old_wp_styles;

	public function set_up() {
		parent::set_up();
		$this->old_wp_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;
		$this->old_wp_styles  = isset( $GLOBALS['wp_styles'] ) ? $GLOBALS['wp_styles'] : null;

		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		remove_action( 'wp_default_styles', 'wp_default_styles' );

		$GLOBALS['wp_scripts']                  = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = get_bloginfo( 'version' );
		$GLOBALS['wp_styles']                   = new WP_Styles();
		$GLOBALS['wp_styles']->default_version  = get_bloginfo( 'version' );
	}

	public function tear_down() {
		$GLOBALS['wp_scripts'] = $this->old_wp_scripts;
		$GLOBALS['wp_styles']  = $this->old_wp_styles;
		parent::tear_down();
	}

	public function test_dns_prefetching() {
		$expected = "<link rel='dns-prefetch' href='//wordpress.org' />\n" .
					"<link rel='dns-prefetch' href='//google.com' />\n" .
					"<link rel='dns-prefetch' href='//make.wordpress.org' />\n";

		add_filter( 'wp_resource_hints', array( $this, 'add_dns_prefetch_domains' ), 10, 2 );

		$actual = get_echo( 'wp_resource_hints' );

		remove_filter( 'wp_resource_hints', array( $this, 'add_dns_prefetch_domains' ) );

		$this->assertSame( $expected, $actual );
	}

	public function add_dns_prefetch_domains( $hints, $method ) {
		if ( 'dns-prefetch' === $method ) {
			$hints[] = 'http://wordpress.org';
			$hints[] = 'https://wordpress.org';
			$hints[] = 'htps://wordpress.org'; // Invalid URLs should be skipped.
			$hints[] = 'https://google.com';
			$hints[] = '//make.wordpress.org';
			$hints[] = 'https://wordpress.org/plugins/';
		}

		return $hints;
	}

	/**
	 * @ticket 37652
	 */
	public function test_preconnect() {
		$expected = "<link rel='preconnect' href='//wordpress.org' />\n" .
					"<link rel='preconnect' href='https://make.wordpress.org' />\n" .
					"<link rel='preconnect' href='http://google.com' />\n" .
					"<link rel='preconnect' href='http://w.org' />\n";

		add_filter( 'wp_resource_hints', array( $this, 'add_preconnect_domains' ), 10, 2 );

		$actual = get_echo( 'wp_resource_hints' );

		remove_filter( 'wp_resource_hints', array( $this, 'add_preconnect_domains' ) );

		$this->assertSame( $expected, $actual );
	}

	public function add_preconnect_domains( $hints, $method ) {
		if ( 'preconnect' === $method ) {
			$hints[] = '//wordpress.org';
			$hints[] = 'https://make.wordpress.org';
			$hints[] = 'htps://example.com'; // Invalid URLs should be skipped.
			$hints[] = 'http://google.com';
			$hints[] = 'w.org';
		}

		return $hints;
	}

	public function test_prerender() {
		$expected = "<link rel='prerender' href='https://make.wordpress.org/great-again' />\n" .
					"<link rel='prerender' href='http://jobs.wordpress.net' />\n" .
					"<link rel='prerender' href='//core.trac.wordpress.org' />\n";

		add_filter( 'wp_resource_hints', array( $this, 'add_prerender_urls' ), 10, 2 );

		$actual = get_echo( 'wp_resource_hints' );

		remove_filter( 'wp_resource_hints', array( $this, 'add_prerender_urls' ) );

		$this->assertSame( $expected, $actual );
	}

	public function add_prerender_urls( $hints, $method ) {
		if ( 'prerender' === $method ) {
			$hints[] = 'https://make.wordpress.org/great-again';
			$hints[] = 'http://jobs.wordpress.net';
			$hints[] = '//core.trac.wordpress.org';
			$hints[] = 'htps://wordpress.org'; // Invalid URLs should be skipped.
		}

		return $hints;
	}

	public function test_parse_url_dns_prefetch() {
		$expected = "<link rel='dns-prefetch' href='//make.wordpress.org' />\n";

		add_filter( 'wp_resource_hints', array( $this, 'add_dns_prefetch_long_urls' ), 10, 2 );

		$actual = get_echo( 'wp_resource_hints' );

		remove_filter( 'wp_resource_hints', array( $this, 'add_dns_prefetch_long_urls' ) );

		$this->assertSame( $expected, $actual );
	}

	public function add_dns_prefetch_long_urls( $hints, $method ) {
		if ( 'dns-prefetch' === $method ) {
			$hints[] = 'http://make.wordpress.org/wp-includes/css/editor.css';
		}

		return $hints;
	}

	public function test_dns_prefetch_styles() {
		$expected = "<link rel='dns-prefetch' href='//fonts.googleapis.com' />\n";

		$args = array(
			'family' => 'Open+Sans:400',
			'subset' => 'latin',
		);

		wp_enqueue_style( 'googlefonts', add_query_arg( $args, '//fonts.googleapis.com/css' ) );

		$actual = get_echo( 'wp_resource_hints' );

		wp_dequeue_style( 'googlefonts' );

		$this->assertSame( $expected, $actual );

	}

	public function test_dns_prefetch_scripts() {
		$expected = "<link rel='dns-prefetch' href='//fonts.googleapis.com' />\n";

		$args = array(
			'family' => 'Open+Sans:400',
			'subset' => 'latin',
		);

		wp_enqueue_script( 'googlefonts', add_query_arg( $args, '//fonts.googleapis.com/css' ) );

		$actual = get_echo( 'wp_resource_hints' );

		wp_dequeue_style( 'googlefonts' );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 37385
	 */
	public function test_dns_prefetch_scripts_does_not_include_registered_only() {
		$expected   = '';
		$unexpected = "<link rel='dns-prefetch' href='//wordpress.org' />\n";

		wp_register_script( 'jquery-elsewhere', 'https://wordpress.org/wp-includes/js/jquery/jquery.js' );

		$actual = get_echo( 'wp_resource_hints' );

		wp_deregister_script( 'jquery-elsewhere' );

		$this->assertSame( $expected, $actual );
		$this->assertStringNotContainsString( $unexpected, $actual );
	}

	/**
	 * @ticket 37502
	 */
	public function test_deregistered_scripts_are_ignored() {
		$expected = '';

		wp_enqueue_script( 'test-script', 'http://example.org/script.js' );
		wp_deregister_script( 'test-script' );

		$actual = get_echo( 'wp_resource_hints' );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 37652
	 */
	public function test_malformed_urls() {
		$expected = '';

		// Errant colon.
		add_filter( 'wp_resource_hints', array( $this, 'add_malformed_url_errant_colon' ), 10, 2 );
		$actual = get_echo( 'wp_resource_hints' );
		remove_filter( 'wp_resource_hints', array( $this, 'add_malformed_url_errant_colon' ) );
		$this->assertSame( $expected, $actual );

		// Unsupported Scheme.
		add_filter( 'wp_resource_hints', array( $this, 'add_malformed_url_unsupported_scheme' ), 10, 2 );
		$actual = get_echo( 'wp_resource_hints' );
		remove_filter( 'wp_resource_hints', array( $this, 'add_malformed_url_unsupported_scheme' ) );
		$this->assertSame( $expected, $actual );
	}

	public function add_malformed_url_errant_colon( $hints, $method ) {
		if ( 'preconnect' === $method ) {
			$hints[] = '://core.trac.wordpress.org/ticket/37652';
		}

		return $hints;
	}

	public function add_malformed_url_unsupported_scheme( $hints, $method ) {
		if ( 'preconnect' === $method ) {
			$hints[] = 'git://develop.git.wordpress.org/';
		}

		return $hints;
	}

	/**
	 * @ticket 38121
	 */
	public function test_custom_attributes() {
		$expected = "<link rel='preconnect' href='https://make.wordpress.org' />\n" .
					"<link crossorigin as='image' pr='0.5' href='https://example.com/foo.jpeg' rel='prefetch' />\n" .
					"<link crossorigin='use-credentials' as='style' href='https://example.com/foo.css' rel='prefetch' />\n" .
					"<link href='http://wordpress.org' rel='prerender' />\n";

		add_filter( 'wp_resource_hints', array( $this, 'add_url_with_attributes' ), 10, 2 );

		$actual = get_echo( 'wp_resource_hints' );

		remove_filter( 'wp_resource_hints', array( $this, 'add_url_with_attributes' ) );

		$this->assertSame( $expected, $actual );
	}

	public function add_url_with_attributes( $hints, $method ) {
		// Ignore hints with missing href attributes.
		$hints[] = array(
			'rel' => 'foo',
		);

		if ( 'preconnect' === $method ) {
			// Should ignore rel attributes.
			$hints[] = array(
				'rel'  => 'foo',
				'href' => 'https://make.wordpress.org/great-again',
			);
		} elseif ( 'prefetch' === $method ) {
			$hints[] = array(
				'crossorigin',
				'as'   => 'image',
				'pr'   => 0.5,
				'href' => 'https://example.com/foo.jpeg',
			);
			$hints[] = array(
				'crossorigin' => 'use-credentials',
				'as'          => 'style',
				'href'        => 'https://example.com/foo.css',
			);
		} elseif ( 'prerender' === $method ) {
			// Ignore invalid attributes.
			$hints[] = array(
				'foo'  => 'bar',
				'bar'  => 'baz',
				'href' => 'http://wordpress.org',
			);
		}

		return $hints;
	}
}
