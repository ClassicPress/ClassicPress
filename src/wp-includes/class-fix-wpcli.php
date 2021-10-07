<?php

class FixWPCLI {

	function __construct() {

		if (!defined('WP_CLI') || !WP_CLI) {
			return;
		}

		WP_CLI::add_hook('before_invoke:core check-update', [__CLASS__, 'correct_core_check_update']);
	}

	public static function correct_core_check_update() {

		// Check for updates. Bail on error.
		// When playing with versions, an empty array is returned if it's not on api.
		if (($core_updates = get_core_updates()) === false || $core_updates === []) {
			WP_CLI::error('Something went wrong checking for updates.');
			exit;
		}

		// We are on latest.
		if ($core_updates[0]->{'response'} === 'latest') {
			WP_CLI::success('ClassicPress is at the latest version.');
			exit;
		}

		// Standard options.
		$arg_fields = 'version,update_type,package_url';
		$format_fields = 'table';

		// Retrieve command line options and parse them.
		global $argv;
		$current_command = implode(' ', $argv);

		$fields_match = [];
		if (preg_match_all('/ --fields=([a-z,_]+)/', $current_command, $fields_match) > 0) {
			$arg_fields = $fields_match[1][0];
		}

		$field_match = [];
		if (preg_match_all('/ --field=([a-z_]+)/', $current_command, $field_match) > 0) {
			$arg_fields = $field_match[1][0];
		}

		$cp_format_match = [];
		if (preg_match_all('/ --format=([a-z]+)/', $current_command, $format_match) > 0) {
			$format_fields = $format_match[1][0];
		}

		$minor = preg_match('/ --minor */', $current_command);

		$major = preg_match('/ --major */', $current_command);

		// Put $cp_version into scope.
		if (!isset($GLOBALS['cp_version'])) {
			global $cp_version;
			require ABSPATH.WPINC.'/version.php';
		}

		// Prepare output array.
		$table_output = [];

		// Loop in the update list.
		foreach ($core_updates as $index => $update) {

			// Get update type and skip if options tells to.
			$type = WP_CLI\Utils\get_named_sem_ver($update->{'version'}, $cp_version);
			if (($major === 1) && ($type !== 'major')) {
				continue;
			}
			if (($minor === 1) && ($type === 'patch')) {
				continue;
			}

			$table_output[] = [
				'version'     => $update->{'version'},
				'package_url' => $update->{'download'},
				'update_type' => $type,
			];

		}

		// Check if the filters left no updates.
		if (empty($table_output)) {
			WP_CLI::success('ClassicPress is at the latest version.');
			exit;
		}

		// Render output.
		WP_CLI\Utils\format_items($format_fields, $table_output, $arg_fields);

		// Exit to prevent the core check-update command to continue his work.
		exit;
	}

}

new FixWPCLI;
