<?php
/**
 * Admin Ajax functions to be tested.
 */
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

/**
 * Testing Ajax handler for installing, updating, and deleting themes.
 *
 * @group ajax
 *
 * @covers ::wp_ajax_update_theme
 */
class Tests_Ajax_wpAjaxUpdateTheme extends WP_Ajax_UnitTestCase {
	private $orig_theme_dir;
	private $theme_root;

	public function set_up() {
		parent::set_up();

		$this->theme_root     = DIR_TESTDATA . '/themedir1';
		$this->orig_theme_dir = $GLOBALS['wp_theme_directories'];

		// /themes is necessary as theme.php functions assume /themes is the root if there is only one root.
		$GLOBALS['wp_theme_directories'] = array( WP_CONTENT_DIR . '/themes', $this->theme_root );

		add_filter( 'theme_root', array( $this, 'filter_theme_root' ) );
		add_filter( 'stylesheet_root', array( $this, 'filter_theme_root' ) );
		add_filter( 'template_root', array( $this, 'filter_theme_root' ) );

		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );
	}

	public function tear_down() {
		$GLOBALS['wp_theme_directories'] = $this->orig_theme_dir;
		remove_filter( 'theme_root', array( $this, 'filter_theme_root' ) );
		remove_filter( 'stylesheet_root', array( $this, 'filter_theme_root' ) );
		remove_filter( 'template_root', array( $this, 'filter_theme_root' ) );
		wp_clean_themes_cache();
		unset( $GLOBALS['wp_themes'] );

		parent::tear_down();
	}

	/**
	 * Replace the normal theme root dir with our pre-made test dir.
	 */
	public function filter_theme_root() {
		return $this->theme_root;
	}

	public function test_missing_slug() {
		$_POST['_ajax_nonce'] = wp_create_nonce( 'updates' );

		// Make the request.
		try {
			$this->_handleAjax( 'update-theme' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response.
		$response = json_decode( $this->_last_response, true );

		$expected = array(
			'success' => false,
			'data'    => array(
				'slug'         => '',
				'errorCode'    => 'no_theme_specified',
				'errorMessage' => 'No theme specified.',
			),
		);

		$this->assertSameSets( $expected, $response );
	}

	public function test_missing_capability() {
		$_POST['_ajax_nonce'] = wp_create_nonce( 'updates' );
		$_POST['slug']        = 'foo';

		// Make the request.
		try {
			$this->_handleAjax( 'update-theme' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		// Get the response.
		$response = json_decode( $this->_last_response, true );

		$expected = array(
			'success' => false,
			'data'    => array(
				'update'       => 'theme',
				'slug'         => 'foo',
				'oldVersion'   => '',
				'newVersion'   => '',
				'errorMessage' => 'Sorry, you are not allowed to update themes for this site.',
			),
		);

		$this->assertSameSets( $expected, $response );
	}

	/**
	 * @group ms-excluded
	 */
	public function test_update_theme() {
		$this->_setRole( 'administrator' );

		$_POST['_ajax_nonce'] = wp_create_nonce( 'updates' );
		$_POST['slug']        = 'twentyten';

		// Prevent wp_update_themes() from running.
		wp_installing( true );

		// Make the request.
		try {
			$this->_handleAjax( 'update-theme' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		wp_installing( false );

		// Get the response.
		$response = json_decode( $this->_last_response, true );

		$theme    = wp_get_theme( 'twentyten' );
		$expected = array(
			'success' => false,
			'data'    => array(
				'update'       => 'theme',
				'slug'         => 'twentyten',
				'oldVersion'   => $theme->get( 'Version' ),
				'newVersion'   => '',
				'debug'        => array( 'The theme is at the latest version.' ),
				'errorMessage' => 'The theme is at the latest version.',
			),
		);

		$this->assertSameSets( $expected, $response );
	}

	/**
	 * @group ms-excluded
	 */
	public function test_uppercase_theme_slug() {
		$this->_setRole( 'administrator' );

		$_POST['_ajax_nonce'] = wp_create_nonce( 'updates' );
		$_POST['slug']        = 'camelCase';

		// Prevent wp_update_themes() from running.
		wp_installing( true );

		// Make the request.
		try {
			$this->_handleAjax( 'update-theme' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		wp_installing( false );

		// Get the response.
		$response = json_decode( $this->_last_response, true );

		$expected = array(
			'success' => false,
			'data'    => array(
				'update'       => 'theme',
				'slug'         => 'camelCase',
				'oldVersion'   => '1.0',
				'newVersion'   => '',
				'debug'        => array( 'The theme is at the latest version.' ),
				'errorMessage' => 'The theme is at the latest version.',
			),
		);

		$this->assertSameSets( $expected, $response );
	}
}
