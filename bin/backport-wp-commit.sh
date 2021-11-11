#!/usr/bin/env bash

set -e

current_branch=no
verbose=no
wp_changeset=""

for i in "$@"; do
	case "$i" in
		-c|--current-branch)
			current_branch=yes
			;;
		-v|--verbose)
			verbose=yes
			;;
		*)
			if [ -z "$wp_changeset" ]; then
				wp_changeset="$i"
			else
				# Multiple changesets not supported
				wp_changeset="invalid"
				break
			fi
			;;
	esac
done

if [[ ! "$wp_changeset" =~ ^[0-9]+$ ]]; then
	echo "Usage: $0 [-c] [-v] WP_CHANGESET_NUMBER"
	echo
	echo "  WP_CHANGESET_NUMBER   The SVN changeset number to port.  If this change depends"
	echo "                        on other changes, make sure they have already been ported."
	echo "  -c, --current-branch  Apply the commit directly to the current branch instead of"
	echo "                        creating a new branch."
	echo "  -v, --verbose         Show intermediate commands and their output."
	echo
	exit 1
fi

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

color_bold_red=""
color_reset=""
if [ -t 1 ]; then
	color_bold_red=$(echo -ne "\033[1;31m")
	color_reset=$(echo -ne "\033[0m")
fi

cmd() {
	failure_allowed=no
	if [ "$1" = __failure_allowed ]; then
		failure_allowed=yes
		shift
	fi
	tmpfile="${TMPDIR:-/tmp}/backport.$$.log"
	echo "+" "$@" > "$tmpfile"
	"$@" 2>&1 | sed 's/^/> /' >> "$tmpfile"
	retval=${PIPESTATUS[0]}
	if [ $retval -gt 0 -a $failure_allowed = no ] || [ $verbose = yes ]; then
		cat "$tmpfile"
	fi
	rm "$tmpfile"
	return $retval
}

wp_remote=$(git remote -v | grep -i '\bWordPress/wordpress-develop\b' | awk 'END { print $1 }')
if [ -z "$wp_remote" ]; then
	echo "Adding git remote 'wp' for WordPress/wordpress-develop"
	cmd git remote add wp https://github.com/WordPress/wordpress-develop.git
	wp_remote="wp"
else
	echo "Found git remote '$wp_remote' for WordPress/wordpress-develop"
fi

cp_remote=$(git remote -v | grep -i '\bClassicPress/ClassicPress\b' | awk 'END { print $1 }')
if [ -z "$cp_remote" ]; then
	echo "Adding git remote 'cp' for ClassicPress/ClassicPress"
	cmd git remote add cp https://github.com/ClassicPress/ClassicPress.git
	cp_remote="cp"
else
	echo "Found git remote '$cp_remote' for ClassicPress/ClassicPress"
fi

echo "Updating repositories from GitHub: WordPress..."
cmd git fetch "$wp_remote"
echo "Updating repositories from GitHub: ClassicPress..."
cmd git fetch "$cp_remote"

commit_hash=""
# Find the changeset in the WP git log
# Only need to search after branch points, or after ClassicPress was forked
# See: https://github.com/ClassicPress/ClassicPress-backports/blob/e8de096b3/app/Http/Controllers/UpstreamCommitsList.php#L23-L74
for range in \
	'd7b6719f:4.9' \
	'5d477aa7:5.0' \
	'3ec31001:5.1' \
	'dc512708:5.2' \
	'c67b47c66:5.3' \
	'66f510bda:5.4' \
	'ff6114f8:trunk'
do
	start_commit=$(echo "$range" | cut -d: -f1)
	search_branch=$(echo "$range" | cut -d: -f2)
	log_range="$start_commit..$wp_remote/$search_branch"
	if [ "$search_branch" = trunk ]; then
		svn_branch=trunk
	else
		svn_branch="branches/$search_branch"
	fi
	echo "Searching for r$wp_changeset in WP git: $wp_remote/$search_branch"
	# Get the commit from WP git log
	search="^git-svn-id: https://develop.svn.wordpress.org/$svn_branch@$wp_changeset "
	commit_hash=$(git log "$log_range" --grep="$search" --pretty=format:'%H' -n 1)
	if [ ! -z "$commit_hash" ]; then
		commit_short=$(echo "$commit_hash" | cut -c1-10)
		echo
		echo "Found commit: $commit_short on $wp_remote/$search_branch branch"
		if [ $verbose = yes ]; then
			cmd git log -n 1 "$commit_short"
		fi
		echo
		break
	fi
done

if [ -z "$commit_hash" ]; then
	echo "ERROR: WP changeset $wp_changeset not found in any git branch"
	exit 1
fi

if [ $current_branch = no ]; then
	# Create branch with the changeset from WordPress, based on the latest
	# ClassicPress develop branch
	branch="merge/wp-r$wp_changeset"
	if git rev-parse "$branch" > /dev/null 2>&1; then
		if [ -t 1 ]; then
			echo "${color_bold_red}WARNING: Local branch '$branch' already exists!${color_reset}"
			echo "Press Enter to remove it and start over, or Ctrl+C to exit."
			read i
		else
			# Not running interactively, so treat this as an error and abort.
			echo "ERROR: Local branch '$branch' already exists!"
			exit 1
		fi
	else
		echo "Creating branch for port: $branch"
	fi
	cmd git checkout "$cp_remote/develop" -B "$branch"
fi

echo "Backporting commit using git cherry-pick"
set +e
cmd __failure_allowed git cherry-pick --no-commit --find-renames \
	--strategy=recursive --strategy-option=patience --strategy-option=ignore-space-change \
	"$commit_short"
conflict_status=$?
set -e

echo 'Modifying commit message'
# edit .git/MERGE_MSG which will be the commit message:
# - transform [12345] into WP changeset link
# - transform #12345 into WP ticket link
# - mark Props lines as coming from WP so we can identify them later
# - remove 'git-svn-id:' line
# - remove duplicate blank lines
# - add 'Merges ... to ClassicPress' line for tracking backported commits
# - preserve information about conflicting files
perl -w <<PL
	open MSG_R, '<', '.git/MERGE_MSG'
		or die "Can't open .git/MERGE_MSG: \$!";
	open MSG_W, '>', '.git/MERGE_MSG.tmp'
		or die "Can't open .git/MERGE_MSG.tmp: \$!";
	print MSG_W 'WP-r$wp_changeset: ';
	my \$was_blank_line = 0;
	while (<MSG_R>) {
		s,\[(\d+)\],https://core.trac.wordpress.org/changeset/\$1,g;
		s,\[(\d+)-(\d+)\],https://core.trac.wordpress.org/log/?revs=\$1-\$2,g;
		s,#(\d+)\b,https://core.trac.wordpress.org/ticket/\$1,g;
		s,^# Conflicts:,Conflicts:,;
		s,^#\t,- ,;
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
	print MSG_W "\n---\n\n";
	print MSG_W "Merges https://core.trac.wordpress.org/changeset/$wp_changeset / WordPress/wordpress-develop\@$commit_short to ClassicPress.\n";
	close MSG_R;
	close MSG_W;
	rename '.git/MERGE_MSG.tmp', '.git/MERGE_MSG';
PL

# Debug info in case something goes wrong
echo
echo 'git status before:'
git status --porcelain
echo

# Add "both modified" or "added by us/them" files
git status --porcelain | grep -E '^[AU][AU] ' | cut -c4- | while read i; do
	cmd git add "$i"
done

# Remove "deleted by us" or "deleted by them" files
git status --porcelain | grep -E '^[DU][DU] ' | cut -c4- | while read i; do
	cmd git rm "$i"
done

# Debug info in case something goes wrong
echo
echo 'git status after:'
git status --porcelain
echo

# Author information is not preserved because we used
# `git cherry-pick --no-commit` above.
author=$(git show -s --format='%an <%ae>' "$commit_short")
cmd git commit --no-edit --allow-empty --author="$author"
echo

if [ "$conflict_status" -eq 0 ]; then
	if [ $current_branch = no ]; then
		echo "All done!  You can push the changes to GitHub now:"
		echo "git push origin $branch"
	else
		echo "All done!  1 commit was added to your current branch."
	fi
else
	echo "${color_bold_red}=======${color_reset}"
	echo "${color_bold_red}WARNING: Conflict detected!${color_reset}"
	echo "Fix and commit the files that contain <<""<< or >>"">> conflict markers:"
	git log -n 1 \
		| perl -we '
			use if "MSWin32" eq $^O, "Win32::Console::ANSI";
			use Term::ANSIColor qw(:constants);
			my $p = 0;
			my $files_conflicting = "";
			my $files_nonexistent = "";
			while (<>) {
				if (/^\s+Conflicts:$/) {
					$p = 1;
				} elsif (/^\s+$/) {
					$p = 0;
				} elsif ($p) {
					# Look for known renames in WP history after fork
					chomp;
					s/^[\s-]+//;
					my $cp_filename = $_;
					$cp_filename =~ s#^src/js/_enqueues/#src/wp-includes/js/#;
					if ( -e $_ ) {
						$files_conflicting = $files_conflicting .  "    - $_\n";
					} else {
						$files_nonexistent = $files_nonexistent . "    - $_\n";
					}
					if ( $cp_filename ne $_ ) {
						$files_conflicting = $files_conflicting . "      (probable CP path: $cp_filename)\n";
					}
				}
			}
			print $files_conflicting;
			if ( "" ne $files_nonexistent ) {
				print "The following files do not exist in the ClassicPress development code:\n";
				print RED, $files_nonexistent, RESET;
			}
			'
	echo "${color_bold_red}=======${color_reset}"
	echo
	echo "If you're not sure how to do this, just push your changes to GitHub"
	echo "and we can take care of it!"
	echo
	if [ $current_branch = no ]; then
		echo "git push origin $branch"
	else
		echo "1 commit was added to your current branch."
	fi
	# Use a special exit code to indicate conflict
	exit 3
fi
