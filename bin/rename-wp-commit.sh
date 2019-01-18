#/usr/bin/env bash

# First parameter is the changeset number

# To save the changes
git commit

# Get the data to print
commit=`git log wp/master --grep="https://develop.svn.wordpress.org/trunk@$1" --oneline --pretty=format:'%h' -n 1`
OLD_MSG=`git log --format=%B -n1`
changeset=`echo $OLD_MSG | tail -n1 | cut -d "@" -f2 | cut -d " " -f1`
message=$(printf "#WP-$1: $OLD_MSG\n\n----\nMerges https:\/\/core.trac.wordpress.org\/changeset\/$changeset \/ WordPress\/wordpress-develop@$commit to ClassicPress.")
git commit --amend -m"$message"

# Push the branch and get ready for the pull request!
git push origin merge/wp-r"$1"
