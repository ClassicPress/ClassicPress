<?php

/**
 * @group option
 */
class Tests_Option_CacheOption extends WP_UnitTestCase {
	private static $cp_settings;

	public static function wpSetUpBeforeClass() {
		require_once ABSPATH . WPINC . '/classicpress/class-cp-settings.php';
		self::$cp_settings = new CP_Settings();
	}

	public static function wpTearDownAfterClass() {
		// make sure to tidy up
		$wp_content_dir    = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$object_cache_file = $wp_content_dir . '/object-cache.php';

		if ( file_exists( $object_cache_file ) ) {
			unlink( $object_cache_file );
		}
	}

	/**
	 * @covers ::_cp_maybe_install_apcu_object_cache
	 */
	public function test_object_cache_installed() {
		if ( ! extension_loaded( 'apcu' ) ) {
			$this->markTestSkipped( 'APCu extension is not available.' );
		}

		update_option( 'cp_object_cache', 1 );

		$this->assertSame( 1, get_option( 'cp_object_cache' ) );

		$wp_content_dir    = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$object_cache_file = $wp_content_dir . '/object-cache.php';
		self::$cp_settings->_cp_maybe_install_apcu_object_cache();

		$this->assertTrue( file_exists( $object_cache_file ) );
	}

	/**
	 * @covers ::_cp_maybe_install_apcu_object_cache
	 */
	public function test_object_cache_uninstalled() {
		if ( ! extension_loaded( 'apcu' ) ) {
			$this->markTestSkipped( 'APCu extension is not available.' );
		}

		update_option( 'cp_object_cache', 0 );

		$this->assertSame( 0, get_option( 'cp_object_cache' ) );

		$wp_content_dir    = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$object_cache_file = $wp_content_dir . '/object-cache.php';
		self::$cp_settings->_cp_maybe_install_apcu_object_cache();

		$this->assertFalse( file_exists( $object_cache_file ) );
	}
}
