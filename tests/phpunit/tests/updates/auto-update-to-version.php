<?php
/**
 * Test the logic for whether an automatic update should be applied between two versions.
 *
 * @package ClassicPress
 * @subpackage UnitTests
 * @since 1.0.0-rc1
 */

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/class-core-upgrader.php';

/**
 * Test the logic for whether automatic updates should be enabled.
 */
class Tests_Auto_Update_To_Version extends WP_UnitTestCase {
	public function test_parse_valid_versions() {
		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => null,
				'pre_number' => null,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.0.0' )
		);

		$this->assertSame(
			[
				'major'      => 11,
				'minor'      => 22,
				'patch'      => 33,
				'pre_type'   => null,
				'pre_number' => null,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '011.022.033' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => 'alpha',
				'pre_number' => 2,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.0.0-alpha2' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => 'beta',
				'pre_number' => 2,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.0.0-beta2' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => 'rc',
				'pre_number' => 1,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.0.0-rc1' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => 'beta',
				'pre_number' => 2,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.0.0-beta2+migration.20181220' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => 'beta',
				'pre_number' => 2,
				'nightly'    => '20190209',
			],
			Core_Upgrader::parse_version_string( '1.0.0-beta2+nightly.20190209' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 0,
				'patch'      => 0,
				'pre_type'   => null,
				'pre_number' => null,
				'nightly'    => '20190226',
			],
			Core_Upgrader::parse_version_string( '1.0.0+nightly.20190226' )
		);

		$this->assertSame(
			[
				'major'      => 1,
				'minor'      => 2,
				'patch'      => 3,
				'pre_type'   => null,
				'pre_number' => null,
				'nightly'    => null,
			],
			Core_Upgrader::parse_version_string( '1.2.3+migration.20191231' )
		);
	}

	public function test_parse_invalid_versions() {
		foreach ( [
			'1.0.a',
			'1.a.0',
			'a.0.0',
			'1.0-0',
			'1.0.0+alpha1',
			'1.0.0-zeta1',
			'1.0.0+build.20190209',
			'1.0.0-build.20190209',
			'1.2.3+migration.201912031',
			'1.0.0-beta2+build.20190209',
			'1.0.0-beta2+migration.20190209+migration.20190209',
			'1.0.0-beta2+nightly.20190209+migration.20190209',
			'1.0.0-beta2+migration.20190209+nightly.20190209',
			'1.0.0-beta2+nightly.20190209+nightly.20190209',
		] as $invalid_version ) {
			$this->assertNull(
				Core_Upgrader::parse_version_string( $invalid_version )
			);
		}
	}

	public function test_auto_update_same() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.0-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+nightly.20190206', '1.0.0-beta2+nightly.20190206', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '1.0.0-beta2+migration.20181220', true
		) );
	}

	public function test_auto_update_basic() {
		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.0.1', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.1.0', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.1.1', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1', '1.1.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '2.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '2.0.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '2.1.0', true
		) );
	}

	public function test_auto_update_invalid() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0', '1.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.a.0', '1.a.1', true
		) );
	}

	public function test_auto_update_from_migration_build() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '1.0.0-rc1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '1.0.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '1.1.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+migration.20181220', '2.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+migration.20190229', '1.0.0', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+migration.20190229', '1.0.1', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+migration.20190229', '1.1.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+migration.20190229', '2.0.0', true
		) );
	}

	public function test_auto_update_newer_nightly() {
		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1+nightly.20181216', '1.0.0-beta1+nightly.20181217', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1+nightly.20181217', '1.0.0-beta2+nightly.20181218', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+nightly.20190219', '1.0.0-rc1+nightly.20190220', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-rc1+nightly.20190220', '1.0.0+nightly.20190226', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+nightly.20190226', '1.0.0+nightly.20190227', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+nightly.20190227', '1.0.1+nightly.20190331', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+nightly.20190227', '1.1.0+nightly.20190430', true
		) );

		$this->assertTrue( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1+nightly.20190331', '1.1.0+nightly.20190430', true
		) );
	}

	public function test_auto_update_older_nightly() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1+nightly.20181217', '1.0.0-beta1+nightly.20181216', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2+nightly.20181218', '1.0.0-beta1+nightly.20181217', true
		) );
	}

	public function test_auto_update_between_nightly_and_release() {
		// This should never happen, but just in case...

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+nightly.20190227', '1.0.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.0.1+nightly.20190331', true
		) );
	}

	public function test_auto_updates_disabled() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.0.1', false
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1', '1.1.0', false
		) );
	}

	public function test_auto_update_to_older() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.1.0', '1.0.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0', '1.0.0-beta2', true
		) );
	}

	public function test_auto_update_between_prereleases_of_same_release() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-alpha1', '1.0.0-alpha2', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-alpha2', '1.0.0-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.0-beta2', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2', '1.0.0-rc1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2', '1.0.0-rc2', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-rc1', '1.0.0-beta2', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta2', '1.0.0-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.0-alpha2', true
		) );
	}

	public function test_auto_update_between_prereleases_of_different_releases() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.1-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-rc1', '1.0.1-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.1-rc1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1-beta1', '1.0.0-rc1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1-rc1', '1.0.0-beta1', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.1-beta1', '1.0.0-rc1', true
		) );
	}

	public function test_auto_update_prerelease_to_final() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.1', true
		) );
	}

	public function test_auto_update_unrecognized_to_final() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-rc1+build.20190225', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0+build.20190228', '1.0.1', true
		) );
	}
}
