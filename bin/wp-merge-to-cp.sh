#!/usr/bin/env bash

# First parameter is your fork
# Second parameter is the changeset number
# Third parameter is the WP branch

find_wp_remote=$(git remote -v | grep WordPress/wordpress-develop | tail -n1 | awk '{print $1;}')
if [ -z "$find_wp_remote" ]; then
    # Download WordPress dev
    git remote add wp https://github.com/WordPress/wordpress-develop
    find_wp_remote='wp'
fi
git fetch "$find_wp_remote"

# Switch to ClassicPress branch
git fetch origin
git checkout develop

# Sync your fork with the original
git merge cp/develop
git push origin develop
git checkout origin/develop -B develop

branch="merge/wp-r$2"
# If branch already exist, remove so the process start from a clean status
if [ ! -z $(git branch --list "$branch") ]; then
	git checkout -D "$branch"
fi

# Create branch with the changeset from WordPress
git checkout -b "$branch"

# Get the commit from WP git log
commit=$(git log "$find_wp_remote"/"$3" --grep="^git-svn-id: https://develop.svn.wordpress.org/(trunk|\\d\\.\\d)@$2" --oneline --pretty=format:'%h' -n 1)
if [ -z "$commit" ]; then
    git cherry-pick "$commit"
else
    echo "Commit not found"
fi
