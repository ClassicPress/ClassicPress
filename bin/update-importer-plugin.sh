#!/usr/bin/env bash

set -e

IMPORTER_PATH=tests/phpunit/data/plugins/wordpress-importer
IMPORTER_GITHUB_URL=https://github.com/WordPress/wordpress-importer

# This script updates the WordPress importer plugin from its latest version on
# GitHub, which is required for some of the automated tests.

# Sanity check: make sure we have a .git directory, and at least one remote
# pointing to a repository named ClassicPress
if [ ! -d .git ] || ! git remote -v | grep -qi '\b/ClassicPress\b'; then
	echo "ERROR: Call this script from within your ClassicPress repository"
	exit 1
fi

# Make sure there are no modified files in the local repository
change_type=""
if ! git diff --exit-code --quiet; then
	change_type="Modified file(s)"
elif ! git diff --cached --exit-code --quiet; then
	change_type="Staged file(s)"
fi
if [ ! -z "$change_type" ]; then
	git status
	echo
	echo "ERROR: $change_type detected"
	echo "ERROR: You must start this script from a clean working tree!"
	exit 1
fi

set -x

rm -rf "$IMPORTER_PATH"
git clone "$IMPORTER_GITHUB_URL" "$IMPORTER_PATH"
revision="$IMPORTER_GITHUB_URL/commit/$(cd "$IMPORTER_PATH"; git rev-parse HEAD | cut -c1-9)"
rm -rf "$IMPORTER_PATH"/{.git,.travis.yml,phpunit*}
git add "$IMPORTER_PATH"
git commit -m "Update importer plugin for automated tests

Revision: $revision"

set +x

echo
echo 'Success!  1 commit was added to your branch:'
echo
git log -n 1
