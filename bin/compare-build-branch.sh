#!/bin/bash

# Given a branch name, run a build before and after the changes in the branch,
# and compare the contents of the `build/` folder each time.
#
# The most common use for this script is to check for potential issues with an
# upgrade to a JS dependency, but it could come in handy for other things too.

branch="$1"
if [ -z "$branch" ]; then
	echo "Usage: $0 branch-to-compare"
	exit 1
fi

if [ ! -f ~/.nvm/nvm.sh ]; then
	echo "Expected to find nvm at ~/.nvm/nvm.sh"
	exit 1
fi

# exit on error
set -e

unminify_build_dir() {
	dir="$1"
	find "$dir" -name '*.min.css' | while read i; do
		css-beautify -r "$i"
	done
	find "$dir" -name '*.min.js' | while read i; do
		js-beautify -r "$i"
	done
}

. ~/.nvm/nvm.sh --no-use

rm -rf build/ build-branch/ build-unminified/ build-branch-unminified/ build-compare.diff

git fetch origin

git checkout package.json package-lock.json
git checkout "origin/$branch" -B "$branch"

nvm use || nvm install
npm install -g js-beautify
rm -rf node_modules/
npm install
grunt build

mv build/ build-branch/
cp -vaR build-branch/ build-branch-unminified/

unminify_build_dir build-branch-unminified/

git checkout package.json package-lock.json
git checkout "$(git merge-base origin/develop $branch)"

nvm use || nvm install
npm install -g js-beautify
rm -rf node_modules/
npm install
grunt build

cp -vaR build/ build-unminified/

unminify_build_dir build-unminified/

# Ignore changes in `$cp_version` (date)
perl -pi -we 's/(\$cp_version = ).*;$/$1"IGNORED";/' \
	build-unminified/wp-includes/version.php \
	build-branch-unminified/wp-includes/version.php
# Ignore changes in `$default_version` (git hash)
perl -pi -we 's/(\$default_version = ).*;$/$1"IGNORED";/' \
	build-unminified/wp-includes/script-loader.php \
	build-branch-unminified/wp-includes/script-loader.php

( diff -ur build-unminified/ build-branch-unminified/ || true ) > build-compare.diff

echo
echo "Diff results for branch: $branch"
echo "Diff results for branch: $branch" | sed -r 's/./=/g'
wc -l build-compare.diff
echo

git checkout package.json package-lock.json
git checkout "$branch"
