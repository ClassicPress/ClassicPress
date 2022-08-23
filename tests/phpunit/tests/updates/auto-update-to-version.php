<?php
/**
 * Test the logic for whether an automatic update should be applied between two versions.
 *
 * @package ClassicPress
 * @subpackage UnitTests
 * @since 1.0.0
 */

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-core-upgrader.php';

/**
 * Test the logic for whether automatic updates should be enabled.
 */
class Tests_Auto_Update_To_Version extends WP_UnitTestCase {
	public function tear_down() {
		remove_all_filters( 'allow_nightly_auto_core_updates' );
		remove_all_filters( 'allow_dev_auto_core_updates' );
		remove_all_filters( 'allow_major_auto_core_updates' );
		remove_all_filters( 'allow_minor_auto_core_updates' );
		remove_all_filters( 'allow_patch_auto_core_updates' );
		parent::tear_down();
	}

	public function test_parse_valid_versions() {
		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => false,
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.0.0' )
		);

		$this->assertSame(
			array(
				'major'      => 11,
				'minor'      => 22,
				'patch'      => 33,
				'prerelease' => false,
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '011.022.033' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => 'alpha2',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.0.0-alpha2' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => 'beta2',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.0.0-beta2' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => 'rc1',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.0.0-rc1' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => 'beta2',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.0.0-beta2+migration.20181220' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => 'beta2',
				'nightly'    => '20190209',
			),
			Core_Upgrader::parse_version_string( '1.0.0-beta2+nightly.20190209' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'prerelease' => false,
				'nightly'    => '20190226',
			),
			Core_Upgrader::parse_version_string( '1.0.0+nightly.20190226' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 2,
				'patch'      => 3,
				'prerelease' => false,
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.2.3+migration.20191231' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 2,
				'patch'      => 3,
				'prerelease' => 'zeta4',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.2.3-zeta4' )
		);

		$this->assertSame(
			array(
				'major'      => 1,
				'minor'      => 2,
				'patch'      => 3,
				'prerelease' => 'test',
				'nightly'    => false,
			),
			Core_Upgrader::parse_version_string( '1.2.3-test' )
		);
	}

	public function test_parse_invalid_versions() {
		foreach ( array(
			'1.0.a',
			'1.a.0',
			'a.0.0',
			'1.0-0',
			'1.0.0+alpha1',
			'1.0.0-épsilon1',
			'1.0.0+build.20190209',
			'1.0.0-build.20190209',
			'1.2.3+migration.201912031',
			'1.0.0-beta2+build.20190209',
			'1.0.0-beta2+migration.20190209+migration.20190209',
			'1.0.0-beta2+nightly.20190209+migration.20190209',
			'1.0.0-beta2+migration.20190209+nightly.20190209',
			'1.0.0-beta2+nightly.20190209+nightly.20190209',
		) as $invalid_version ) {
			$this->assertNull(
				Core_Upgrader::parse_version_string( $invalid_version )
			);
		}
	}

	public function test_auto_update_same() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+nightly.20190206',
				'1.0.0-beta2+nightly.20190206',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.0-beta2+migration.20181220',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.0-beta2+migration.20181221',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.0-beta2',
				true
			)
		);
	}

	public function test_auto_update_basic() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.1.0',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.1.1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'2.0.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'2.0.1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'2.1.0',
				true
			)
		);
	}

	/**
	 * Simulate automatic updates being disabled via:
	 *
	 * define( WP_AUTO_UPDATE_CORE, false );
	 */
	public function test_auto_updates_constant_false() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				false // prerelease
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				false // nightly
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1',
				false // patch
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				false // minor
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				false // major
			)
		);
	}

	/**
	 * Simulate automatic updates being fully enabled via:
	 *
	 * define( WP_AUTO_UPDATE_CORE, true );
	 */
	public function test_auto_updates_constant_true() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				true // prerelease
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				true // nightly
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1',
				true // patch
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true // minor
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				true // major
			)
		);
	}

	/**
	 * Simulate automatic updates being enabled for minor versions via:
	 *
	 * define( WP_AUTO_UPDATE_CORE, 'minor' );
	 */
	public function test_auto_updates_constant_minor() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				'minor' // prerelease
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				'minor' // nightly
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1',
				'minor' // patch
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				'minor' // minor
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				'minor' // major
			)
		);
	}

	/**
	 * Simulate automatic updates being limited to patch versions via:
	 *
	 * define( WP_AUTO_UPDATE_CORE, 'patch' );
	 */
	public function test_auto_updates_constant_patch() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				'patch' // prerelease
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				'patch' // nightly
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1',
				'patch' // patch
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				'patch' // minor
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				'patch' // major
			)
		);
	}

	public function test_auto_update_invalid() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0',
				'1.1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.a.0',
				'1.a.1',
				true
			)
		);
	}

	public function test_auto_update_from_migration_build() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.0-rc1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.0',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.0.1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'1.1.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+migration.20181220',
				'2.0.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+migration.20190229',
				'1.0.0',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+migration.20190229',
				'1.0.1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+migration.20190229',
				'1.1.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+migration.20190229',
				'2.0.0',
				true
			)
		);
	}

	public function test_auto_update_newer_nightly() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181216',
				'1.0.0-beta1+nightly.20181217',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181217',
				'1.0.0-beta2+nightly.20181218',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+nightly.20190219',
				'1.0.0-rc1+nightly.20190220',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-rc1+nightly.20190220',
				'1.0.0+nightly.20190226',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.0+nightly.20190227',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190227',
				'1.0.1+nightly.20190331',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190227',
				'1.1.0+nightly.20190430',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1+nightly.20190331',
				'1.1.0+nightly.20190430',
				true
			)
		);
	}

	public function test_auto_update_newer_nightly_different_major() {
		// This will allow us to run 1 nightly build per major version branch
		// in the future.  We should probably never upgrade automatically to a
		// nightly build of a newer major version, so this situation needs to
		// pass both the "nightly" and the "major" version filters.

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181218',
				'2.0.0-alpha0+nightly.20190630',
				true
			)
		);

		add_filter( 'allow_major_auto_core_updates', '__return_true' );

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181218',
				'2.0.0-alpha0+nightly.20190630',
				true
			)
		);

		add_filter( 'allow_nightly_auto_core_updates', '__return_false' );

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181218',
				'2.0.0-alpha0+nightly.20190630',
				true
			)
		);
	}

	public function test_auto_update_older_nightly() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1+nightly.20181217',
				'1.0.0-beta1+nightly.20181216',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2+nightly.20181218',
				'1.0.0-beta1+nightly.20181217',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.2+nightly.20190701',
				'2.0.0-alpha0+nightly.20190630',
				true
			)
		);
	}

	public function test_auto_update_between_nightly_and_release() {
		// This should never happen, but just in case...

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190227',
				'1.0.1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.1+nightly.20190331',
				true
			)
		);
	}

	public function test_auto_update_to_older() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.1.0',
				'1.0.1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'2.1.0',
				'1.4.3',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0',
				'1.0.0-beta2',
				true
			)
		);
	}

	public function test_auto_update_between_prereleases_of_same_release() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-alpha1',
				'1.0.0-alpha2',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-alpha2',
				'1.0.0-beta1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-rc1',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-rc2',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-rc1',
				'1.0.0-beta2',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-beta1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-alpha2',
				true
			)
		);
	}

	public function test_auto_update_between_prereleases_of_different_releases() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.1-beta1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-rc1',
				'1.0.1-beta1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.1-rc1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1-beta1',
				'1.0.0-rc1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1-rc1',
				'1.0.0-beta1',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1-beta1',
				'1.0.0-rc1',
				true
			)
		);
	}

	public function test_auto_update_prerelease_to_final() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0',
				true
			)
		);

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.1',
				true
			)
		);
	}

	public function test_auto_update_unrecognized_to_final() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-rc1+build.20190225',
				'1.0.0',
				true
			)
		);

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+build.20190228',
				'1.0.1',
				true
			)
		);
	}

	public function test_filter_allow_nightly_auto_core_updates_1() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				true
			)
		);

		add_filter( 'allow_nightly_auto_core_updates', '__return_false' );

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				true
			)
		);
	}

	public function test_filter_allow_nightly_auto_core_updates_2() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				false
			)
		);

		add_filter( 'allow_nightly_auto_core_updates', '__return_true' );

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0+nightly.20190226',
				'1.0.1+nightly.20190331',
				false
			)
		);
	}

	public function test_filter_allow_prerelease_auto_core_updates() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				true
			)
		);
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-beta2',
				true
			)
		);
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-beta1',
				true
			)
		);

		add_filter( 'allow_dev_auto_core_updates', '__return_false' );

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta1',
				'1.0.0-beta2',
				true
			)
		);
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-beta2',
				true
			)
		);
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.0-beta2',
				'1.0.0-beta1',
				true
			)
		);
	}

	public function test_filter_allow_major_auto_core_updates() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				true // major
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);

		add_filter( 'allow_major_auto_core_updates', '__return_true' );

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'2.0.0',
				true // major
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);
	}

	public function test_filter_allow_minor_auto_core_updates_1() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);

		add_filter( 'allow_minor_auto_core_updates', '__return_false' );

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);
	}

	public function test_filter_allow_minor_auto_core_updates_2() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				false
			)
		);

		add_filter( 'allow_minor_auto_core_updates', '__return_true' );

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				false
			)
		);
	}

	public function test_filter_allow_patch_auto_core_updates_1() {
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);

		add_filter( 'allow_patch_auto_core_updates', '__return_false' );

		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				true
			)
		);
		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.1.0',
				true
			)
		);
	}

	public function test_filter_allow_patch_auto_core_updates_2() {
		$this->assertFalse(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				false
			)
		);

		add_filter( 'allow_patch_auto_core_updates', '__return_true' );

		$this->assertTrue(
			Core_Upgrader::_auto_update_enabled_for_versions(
				'1.0.1',
				'1.0.2',
				false
			)
		);
	}
}
