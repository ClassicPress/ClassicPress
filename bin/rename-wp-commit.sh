#!/usr/bin/env bash

# First parameter is the changeset number
# Second parameter is the WP branch

echo "Commit the change"
# To save the changes
git commit --no-edit > /dev/null 2>&1

find_wp_remote=$(git remote -v | grep WordPress/wordpress-develop | tail -n1 | awk '{print $1;}')

# Set the WP branch where search
search_branch="trunk"
if [[ "$2" -ne "master" || "$2" -ne "trunk" ]]; then
    search_branch="$2"
fi

echo "Change the commit text"
# Get the data to print
search="^git-svn-id: https://develop.svn.wordpress.org/$search_branch@$1"
commit=$(git log "$find_wp_remote"/"$2" --grep="$search" --oneline --pretty=format:'%h' -n 1)
OLD_MSG=$(git log --format=%B -n1)
message=$(printf "#WP-$1: $OLD_MSG\n\n----\nMerges https://core.trac.wordpress.org/changeset/$1 / WordPress\/wordpress-develop@$commit to ClassicPress.")
git commit --amend -m"$message" > /dev/null 2>&1

echo "Push new branch"
# Push the branch and get ready for the pull request!
#git push origin merge/wp-r"$1" > /dev/null 2>&1
