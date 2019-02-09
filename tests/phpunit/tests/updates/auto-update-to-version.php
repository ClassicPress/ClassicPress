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
	}

	public function test_auto_update_prerelease_to_final() {
		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.0', true
		) );

		$this->assertFalse( Core_Upgrader::auto_update_enabled_for_versions(
			'1.0.0-beta1', '1.0.1', true
		) );
	}
}
