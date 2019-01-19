#!/usr/bin/env bash

set -e

wp_changeset="$1"
if [[ ! "$wp_changeset" =~ ^[0-9]+$ ]]; then
	echo "Usage: $0 WP_CHANGESET_NUMBER"
	exit 1
fi

if [ ! -d .git ]; then
	echo "Call this script from within your ClassicPress repository"
	exit 1
fi

cmd() {
	echo "+" "$@"
	"$@" 2>&1 | sed 's/^/> /'
	return ${PIPESTATUS[0]}
}

wp_remote=$(git remote -v | grep '\bWordPress/wordpress-develop\b' | awk 'END { print $1 }')
if [ -z "$wp_remote" ]; then
	echo "Adding WordPress/wordpress-develop git remote: wp"
	cmd git remote add wp https://github.com/WordPress/wordpress-develop.git
	wp_remote="wp"
else
	echo "Found WordPress/wordpress-develop git remote: $wp_remote"
fi

cp_remote=$(git remote -v | grep '\bClassicPress/ClassicPress\b' | awk 'END { print $1 }')
if [ -z "$cp_remote" ]; then
	echo "Adding ClassicPress/ClassicPress git remote: cp"
	cmd git remote add cp https://github.com/ClassicPress/ClassicPress.git
	cp_remote="cp"
else
	echo "Found ClassicPress/ClassicPress git remote: $cp_remote"
fi

echo "Updating repositories from GitHub"
cmd git fetch "$wp_remote"
cmd git fetch "$cp_remote"

commit_hash=
# Find the changeset in the WP git log
# Only need to search after branch points, or after ClassicPress was forked
# See: https://github.com/ClassicPress/ClassicPress-Bots/blob/4c1a9f2f/app/Http/Controllers/UpstreamCommitsList.php#L10-L36
for range in 'd7b6719f:4.9' '5d477aa7:5.0' 'ff6114f8:master'; do
	start_commit=$(echo "$range" | cut -d: -f1)
	search_branch=$(echo "$range" | cut -d: -f2)
	log_range="$start_commit..$wp_remote/$search_branch"
	if [ "$search_branch" = master ]; then
		svn_branch=trunk
	else
		svn_branch="$search_branch"
	fi
	echo "Searching for r$wp_changeset in WP git: $wp_remote/$search_branch"
	# Get the commit from WP git log
	search="^git-svn-id: https://develop.svn.wordpress.org/branches/$svn_branch@$wp_changeset "
	commit_hash=$(git log "$log_range" --grep="$search" --pretty=format:'%H' -n 1)
	if [ ! -z "$commit_hash" ]; then
		commit_short=$(echo "$commit_hash" | cut -c1-8)
		echo
		echo "Found commit: $commit_short"
		cmd git log -n 1 --stat "$commit_short"
		echo
		break
	fi
done

if [ -z "$commit_hash" ]; then
	echo "WP changeset $wp_changeset not found in any git branch"
	exit 1
fi

# Create branch with the changeset from WordPress, based on the latest
# ClassicPress develop branch
branch="merge/wp-r$wp_changeset"
if git rev-parse "$branch" > /dev/null 2>&1; then
	echo "Local branch '$branch' already exists!"
	echo "Press Enter to remove it and start over, or Ctrl+C to exit."
	read i
else
	echo "Creating branch for port: $branch"
fi
cmd git checkout "$cp_remote/develop" -B "$branch"

echo "=== IMPORTANT ==="
echo "=== Include this line in your PR message:"
echo "Merges https://core.trac.wordpress.org/changeset/$wp_changeset / WordPress/wordpress-develop@$commit_short to ClassicPress."
echo "=== IMPORTANT ==="

echo git cherry-pick "$commit_short"
