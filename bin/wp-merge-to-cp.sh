#!/usr/bin/env bash

# First parameter is the changeset number
# Second parameter is the WP branch

find_wp_remote=$(git remote -v | grep WordPress/wordpress-develop | tail -n1 | awk '{print $1;}')
if [ -z "$find_wp_remote" ]; then
    echo "Adding WP remote"
    # Download WordPress dev
    git remote add wp https://github.com/WordPress/wordpress-develop > /dev/null
    find_wp_remote='wp'
fi
find_cp_remote=$(git remote -v | grep ClassicPress/ClassicPress | tail -n1 | awk '{print $1;}')
if [ -z "$find_cp_remote" ]; then
    echo "Adding CP remote"
    # Download WordPress dev
    git remote add cp git@github.com:ClassicPress/ClassicPress.git > /dev/null
    find_cp_remote='wp'
fi
git fetch "$find_cp_remote"

# Switch to ClassicPress branch
echo "Sync your local repo"
git fetch origin > /dev/null
echo "Switch to develop branch"
git checkout develop > /dev/null

# Sync your fork with the original
echo "Merge remote CP to your fork"
git merge "$find_cp_remote"/develop > /dev/null
git push origin develop > /dev/null
git checkout origin/develop -B develop > /dev/null

branch="merge/wp-r$1"
# If branch already exist, remove so the process start from a clean status
if [ ! -z $(git branch --list "$branch") ]; then
    echo "Remove branch for this changeset because exists"
	git checkout -D "$branch" > /dev/null
fi

# Create branch with the changeset from WordPress
echo "Create branch for changeset $1"
git checkout -b "$branch" > /dev/null

# Get the commit from WP git log
commit=$(git log "$find_wp_remote"/"$2" --grep="^git-svn-id: https://develop.svn.wordpress.org/(trunk|\\d\\.\\d)@$1" --oneline --pretty=format:'%h' -n 1)
if [ -z "$commit" ]; then
    echo "Backport the changeset"
    git cherry-pick "$commit"
else
    echo "Commit not found"
fi
