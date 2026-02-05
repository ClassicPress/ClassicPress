<?php

/**
 * @group option
 */
class Tests_Option_CacheOption extends WP_UnitTestCase {
	private static $cp_settings;
	private static $object_cache_file;

	public static function wpSetUpBeforeClass() {
		require_once ABSPATH . WPINC . '/classicpress/class-cp-settings.php';
		self::$cp_settings = new CP_Settings();

		$wp_content_dir    = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		self::$object_cache_file = $wp_content_dir . '/object-cache.php';
	}

	/**
	 * @covers ::_cp_maybe_install_apcu_object_cache
	 */
	public function test_object_cache_installed() {
		if ( ! extension_loaded( 'apcu' ) ) {
			$this->markTestSkipped( 'APCu extension is not available.' );
		}

		if ( file_exists( self::$object_cache_file ) ) {
			$this->markTestSkipped( 'Leave original object cache in place for Memcached tests.' );
		}

		update_option( 'cp_object_cache', 1 );

		$this->assertSame( 1, get_option( 'cp_object_cache' ) );

		self::$cp_settings->_cp_maybe_install_apcu_object_cache();
		$this->assertTrue( file_exists( self::$object_cache_file ) );

		update_option( 'cp_object_cache', 0 );

		$this->assertSame( 0, get_option( 'cp_object_cache' ) );

		self::$cp_settings->_cp_maybe_install_apcu_object_cache();

		$this->assertFalse( file_exists( self::$object_cache_file ) );
	}
}
