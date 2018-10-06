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

if [ ! -x "$(which js-beautify)" ] || [ ! -x "$(which css-beautify)" ]; then
	echo "Install js-beautify and css-beautify first:"
	echo "npm install -g js-beautify"
	exit 1
fi

unminify_build_dir() {
	dir="$1"
	find "$dir" -name '*.min.css' | while read i; do
		css-beautify -r "$i"
	done
	find "$dir" -name '*.min.js' | while read i; do
		js-beautify -r "$i"
	done
}

rm -rf build/ build-branch/ build-unminified/ build-branch-unminified/ build-compare.diff

git checkout "$branch"
grunt build
mv build/ build-branch/
cp -var build-branch/ build-branch-unminified/

unminify_build_dir build-branch-unminified/

git checkout "$(git merge-base origin/master $branch)"
grunt build
cp -var build/ build-unminified/

unminify_build_dir build-unminified/

# Changes in version.php (timestamp) are normal
rm -v build-unminified/wp-includes/version.php
rm -v build-branch-unminified/wp-includes/version.php
diff -ur build-unminified/ build-branch-unminified/ > build-compare.diff

wc -l build-compare.diff

git checkout "$branch"
