#!/usr/bin/env bash

# First parameter is the changeset number
# Second parameter is the WP branch

find_wp_remote=$(git remote -v | grep WordPress/wordpress-develop | tail -n1 | awk '{print $1;}')
if [ -z "$find_wp_remote" ]; then
    echo "Adding WP remote"
    # Download WordPress dev
    git remote add wp https://github.com/WordPress/wordpress-develop > /dev/null 2>&1
    find_wp_remote='wp'
fi

find_cp_remote=$(git remote -v | grep ClassicPress/ClassicPress | tail -n1 | awk '{print $1;}')
if [ -z "$find_cp_remote" ]; then
    echo "Adding CP remote"
    # Download WordPress dev
    git remote add cp git@github.com:ClassicPress/ClassicPress.git > /dev/null 2>&1
    find_cp_remote='cp'
fi

echo "Fetch CP/WP git repository"
git fetch "$find_wp_remote" > /dev/null 2>&1
git fetch "$find_cp_remote" > /dev/null 2>&1

# Switch to ClassicPress branch
echo "Sync your local repo"
git fetch origin > /dev/null 2>&1
echo "Switch to develop branch"
git checkout develop > /dev/null 2>&1

# Sync your fork with the original
echo "Merge remote CP to your fork"
git merge "$find_cp_remote"/develop > /dev/null 2>&1
git push origin develop > /dev/null 2>&1
git checkout origin/develop -B develop > /dev/null 2>&1

branch="merge/wp-r$1"
# If branch already exist, remove so the process start from a clean status
if [ ! -z $(git branch --list "$branch") ]; then
    echo "Remove branch for this changeset because exists"
	git branch -D "$branch" > /dev/null 2>&1
fi

# Create branch with the changeset from WordPress
echo "Create branch for changeset $1"
git checkout -b "$branch" > /dev/null 2>&1

# Set the WP branch where search
search_branch="trunk"
if [[ "$2" -ne "master" || "$2" -ne "trunk" ]]; then
    search_branch="$2"
fi

# Get the commit from WP git log
search="^git-svn-id: https://develop.svn.wordpress.org/$search_branch@$1"
commit=$(git log "$find_wp_remote"/"$2" --grep="$search" --oneline --pretty=format:'%h' -n 1)
if [ ! -z "$commit" ]; then
    echo "Backport the changeset"
    git cherry-pick "$commit"
else
    echo "Commit not found"
fi
