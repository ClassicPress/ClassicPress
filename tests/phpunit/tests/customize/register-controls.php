<?php
/**
 * Tests for repeated registration of default Customizer controls.
 *
 * @package ClassicPress
 */

/**
 * @group customize
 */
class Tests_Customize_Register_Controls extends WP_UnitTestCase {

	/**
	 * Theme callbacks must survive a subsequent default registration pass.
	 *
	 * @dataProvider data_theme_callback_priorities
	 * @covers WP_Customize_Manager::register_controls
	 *
	 * @param int $priority Theme callback priority.
	 */
	public function test_repeated_registration_preserves_theme_section_changes( $priority ) {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager  = new WP_Customize_Manager();
		$callback = static function ( $customize ) use ( $manager ) {
			if ( $customize !== $manager ) {
				return;
			}
			$customize->get_section( 'colors' )->description        = 'Theme contrast guidance.';
			$customize->get_section( 'colors' )->priority           = 45;
			$customize->get_section( 'title_tagline' )->description = 'Theme identity guidance.';
		};
		add_action( 'customize_register', $callback, $priority );

		try {
			do_action( 'customize_register', $manager );
			$colors   = $manager->get_section( 'colors' );
			$identity = $manager->get_section( 'title_tagline' );
			$this->assertSame( 'Theme contrast guidance.', $colors->description );

			// customize.php can request registration again after customize_register.
			$manager->register_controls();

			$this->assertSame( $colors, $manager->get_section( 'colors' ) );
			$this->assertSame( 'Theme contrast guidance.', $colors->description );
			$this->assertSame( 45, $colors->priority );
			$this->assertSame( $identity, $manager->get_section( 'title_tagline' ) );
			$this->assertSame( 'Theme identity guidance.', $identity->description );
		} finally {
			remove_action( 'customize_register', $callback, $priority );
		}
	}

	/**
	 * Theme callbacks can run at the default priority or after dynamic settings.
	 *
	 * @return array[] Callback priorities.
	 */
	public function data_theme_callback_priorities() {
		return array(
			'default priority' => array( 10 ),
			'late priority'    => array( 20 ),
		);
	}

	/**
	 * Core setting and control extensions must not be replaced either.
	 *
	 * @covers WP_Customize_Manager::register_controls
	 */
	public function test_repeated_registration_preserves_setting_and_control() {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->register_controls();
		$setting              = $manager->get_setting( 'blogname' );
		$control              = $manager->get_control( 'blogname' );
		$control->description = 'Site title guidance.';
		$manager->register_controls();

		$this->assertSame( $setting, $manager->get_setting( 'blogname' ) );
		$this->assertSame( $control, $manager->get_control( 'blogname' ) );
		$this->assertSame( 'Site title guidance.', $control->description );
	}

	/**
	 * Explicit section replacement through the public API remains supported.
	 *
	 * @covers WP_Customize_Manager::add_section
	 */
	public function test_explicit_section_replacement_remains_supported() {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->register_controls();
		$original = $manager->get_section( 'colors' );
		$updated  = $manager->add_section( 'colors', array( 'description' => 'Explicit replacement.' ) );

		$this->assertNotSame( $original, $updated );
		$this->assertSame( $updated, $manager->get_section( 'colors' ) );
		$this->assertSame( 'Explicit replacement.', $updated->description );
	}

	/**
	 * The guard belongs to each manager, not to the process or the action.
	 *
	 * @covers WP_Customize_Manager::register_controls
	 */
	public function test_multiple_managers_register_independently() {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$first = new WP_Customize_Manager();
		$first->register_controls();
		$colors              = $first->get_section( 'colors' );
		$colors->description = 'First manager guidance.';

		$second = new WP_Customize_Manager();
		do_action( 'customize_register', $second );

		$this->assertSame( $colors, $first->get_section( 'colors' ) );
		$this->assertSame( 'First manager guidance.', $colors->description );
		$this->assertInstanceOf( 'WP_Customize_Section', $second->get_section( 'colors' ) );
		$this->assertNotSame( $colors, $second->get_section( 'colors' ) );
		$this->assertNotSame( $first->get_setting( 'blogname' ), $second->get_setting( 'blogname' ) );
		$this->assertNotSame( $first->get_control( 'blogname' ), $second->get_control( 'blogname' ) );
	}

	/**
	 * Explicit public API changes survive subsequent default registration.
	 *
	 * @covers WP_Customize_Manager::register_controls
	 */
	public function test_explicit_replacements_and_removals_are_preserved() {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->register_controls();
		$section = $manager->add_section( 'colors', array( 'description' => 'Replacement section.' ) );
		$setting = $manager->add_setting( 'blogname', array( 'default' => 'Replacement default.' ) );
		$control = $manager->add_control( 'blogname', array( 'label' => 'Replacement label.' ) );
		$manager->remove_section( 'title_tagline' );
		$manager->remove_setting( 'blogdescription' );
		$manager->remove_control( 'blogdescription' );
		$manager->register_controls();

		$this->assertSame( $section, $manager->get_section( 'colors' ) );
		$this->assertSame( $setting, $manager->get_setting( 'blogname' ) );
		$this->assertSame( $control, $manager->get_control( 'blogname' ) );
		$this->assertNull( $manager->get_section( 'title_tagline' ) );
		$this->assertNull( $manager->get_setting( 'blogdescription' ) );
		$this->assertNull( $manager->get_control( 'blogdescription' ) );
	}

	/**
	 * An interrupted registration must not be marked as complete.
	 *
	 * @covers WP_Customize_Manager::register_controls
	 */
	public function test_registration_can_retry_after_an_exception() {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager  = new WP_Customize_Manager();
		$callback = static function () {
			throw new RuntimeException( 'Registration interrupted.' );
		};
		add_filter( 'pre_option_blogname', $callback );

		try {
			$manager->register_controls();
			$this->fail( 'The option filter should interrupt registration.' );
		} catch ( RuntimeException $exception ) {
			$this->assertSame( 'Registration interrupted.', $exception->getMessage() );
		} finally {
			remove_filter( 'pre_option_blogname', $callback );
		}

		$this->assertNull( $manager->get_setting( 'blogname' ) );
		$manager->register_controls();
		$this->assertInstanceOf( 'WP_Customize_Setting', $manager->get_setting( 'blogname' ) );
		$this->assertInstanceOf( 'WP_Customize_Control', $manager->get_control( 'custom_css' ) );
	}
}
