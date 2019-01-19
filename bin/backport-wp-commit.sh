#!/usr/bin/env bash

set -e

wp_changeset="$1"
if [[ ! "$wp_changeset" =~ ^[0-9]+$ ]]; then
	echo "Usage: $0 WP_CHANGESET_NUMBER"
	exit 1
fi

# Sanity check: make sure we have a .git directory, and at least one remote
# pointing to a repository named ClassicPress
if [ ! -d .git ] || ! git remote -v | grep -q '\b/ClassicPress\b'; then
	echo "ERROR: Call this script from within your ClassicPress repository"
	exit 1
fi

# Make sure there are no modified files in the local repository
change_type=
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

cmd() {
	echo "+" "$@"
	"$@" 2>&1 | sed 's/^/> /'
	return ${PIPESTATUS[0]}
}

wp_remote=$(git remote -v | grep '\bWordPress/wordpress-develop\b' | awk 'END { print $1 }')
if [ -z "$wp_remote" ]; then
	echo "Adding git remote 'wp' for WordPress/wordpress-develop"
	cmd git remote add wp https://github.com/WordPress/wordpress-develop.git
	wp_remote="wp"
else
	echo "Found git remote '$wp_remote' for WordPress/wordpress-develop"
fi

cp_remote=$(git remote -v | grep '\bClassicPress/ClassicPress\b' | awk 'END { print $1 }')
if [ -z "$cp_remote" ]; then
	echo "Adding git remote 'cp' for ClassicPress/ClassicPress"
	cmd git remote add cp https://github.com/ClassicPress/ClassicPress.git
	cp_remote="cp"
else
	echo "Found git remote '$cp_remote' for ClassicPress/ClassicPress"
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
		svn_branch="branches/$search_branch"
	fi
	echo "Searching for r$wp_changeset in WP git: $wp_remote/$search_branch"
	# Get the commit from WP git log
	search="^git-svn-id: https://develop.svn.wordpress.org/$svn_branch@$wp_changeset "
	commit_hash=$(git log "$log_range" --grep="$search" --pretty=format:'%H' -n 1)
	if [ ! -z "$commit_hash" ]; then
		commit_short=$(echo "$commit_hash" | cut -c1-8)
		echo
		echo "Found commit: $commit_short on $wp_remote/$search_branch branch"
		cmd git log -n 1 "$commit_short"
		echo
		break
	fi
done

if [ -z "$commit_hash" ]; then
	echo "ERROR: WP changeset $wp_changeset not found in any git branch"
	exit 1
fi

# Create branch with the changeset from WordPress, based on the latest
# ClassicPress develop branch
branch="merge/wp-r$wp_changeset"
if git rev-parse "$branch" > /dev/null 2>&1; then
	echo "WARNING: Local branch '$branch' already exists!"
	echo "Press Enter to remove it and start over, or Ctrl+C to exit."
	read i
else
	echo "Creating branch for port: $branch"
fi
cmd git checkout "$cp_remote/develop" -B "$branch"

set +e
cmd git cherry-pick --no-commit "$commit_short"
conflict_status=$?
set -e

edit_merge_msg() {
	echo 'Modifying commit message'
	# edit .git/MERGE_MSG which will be the commit message:
	# - transform [12345] into WP changeset link
	# - transform #12345 into WP ticket link
	# - mark Props lines as coming from WP so we can identify them later
	# - remove 'git-svn-id:' line
	# - remove duplicate blank lines
	perl -w <<PL
		open MSG_R, '<', '.git/MERGE_MSG'
			or die "Can't open .git/MERGE_MSG: \$!";
		open MSG_W, '>', '.git/MERGE_MSG.tmp'
			or die "Can't open .git/MERGE_MSG.tmp: \$!";
		print MSG_W 'WP-r$wp_changeset: ';
		my \$was_blank_line = 0;
		while (<MSG_R>) {
			s,\[(\d+)\],https://core.trac.wordpress.org/changeset/\$1,g;
			s,#(\d+)\b,https://core.trac.wordpress.org/ticket/\$1,g;
			s,^# Conflicts:,Conflicts:,;
			s,^#\t,  ,;
			s,\b(Props|Unprops)(\s*),WP:\$1\$2,g;
			if (/\S/) {
				if (!/^git-svn-id:/) {
					\$was_blank_line = 0;
					print MSG_W \$_;
				}
			} else {
				print MSG_W \$_ if !\$was_blank_line;
				\$was_blank_line = 1;
			}
		}
		print MSG_W "----\n";
		print MSG_W "Merges https://core.trac.wordpress.org/changeset/$wp_changeset / WordPress/wordpress-develop\@$commit_short to ClassicPress.\n";
		close MSG_R;
		close MSG_W;
		rename '.git/MERGE_MSG.tmp', '.git/MERGE_MSG';
PL
}

if [ "$conflict_status" -eq 0 ]; then
	edit_merge_msg
	cmd git commit --no-edit
	echo
	echo "All done!  You can push the changes to GitHub now:"
	echo "git push origin $branch"
else
	# Apparently `git cherry-pick --no-commit` doesn't use the git sequencer,
	# but we should because then `git status` shows you the next steps
	echo "Redoing cherry-pick now that we know there's a conflict"
	cmd git reset --hard
	echo
	set +e
	cmd git cherry-pick "$commit_short"
	set -e
	echo
	edit_merge_msg
	echo
	echo "======="
	echo "WARNING: Conflict detected!"
	echo "Fix and commit the files marked as 'both modified' before proceeding:"
	echo "======="
	echo
	git status
	echo
	echo "After resolving the conflict(s), commit and push the changes to GitHub:"
	echo "git add ."
	echo "git cherry-pick --continue"
	echo "git push origin $branch"
fi
