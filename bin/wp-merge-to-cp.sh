#/usr/bin/env bash

# First parameter is your fork
# Second parameter is the changeset number

if [ ! -d "ClassicPress" ]; then
    # Download ClassicPress from your fork
    git clone git@github.com:$1/ClassicPress.git
    cd ClassicPress

    # Download original ClassicPress
    git remote add cp git@github.com:ClassicPress/ClassicPress.git

    # Download WordPress dev
    git remote add wp https://github.com/WordPress/wordpress-develop
else
    cd ClassicPress
fi
git fetch cp
git fetch wp

# Switch to ClassicPress branch
git fetch origin
git checkout develop

# Sync your fork with the original
git merge cp/develop
git push origin develop
git checkout origin/develop -B develop

# Create branch with the changeset from WordPress
git checkout -b merge/wp-r$2

# Get the commit from WP git log
commit=`git log wp/master --grep=$2 --oneline --pretty=format:'%h' -n 1`
if [ -z "$var" ]; then
    git cherry-pick $commit
else
    echo "Commit not found"
fi
