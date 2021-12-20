# Contributing to ClassicPress

ClassicPress is a volunteer-driven open source project that thrives when we have multiple contributors working towards a common goal.

We invite all contributors to not only submit pull requests (_PRs_) that are in line with the goals and direction of the project, but also to discuss and review other contributors' issues and pull requests. This will help us make the most of our limited time and review all contributions quickly.

Before contributing to this repository, please read our [Democracy page](https://www.classicpress.net/democracy/) to understand how we make decisions about what is included in ClassicPress, and our [roadmap](https://www.classicpress.net/roadmap/) to see what's already planned for the next couple of releases.

The **petitions process** mentioned in our Democracy document is very important to help us prioritize new features based on the needs of our users. There are some [exceptions](https://www.classicpress.net/democracy/#democracy-exceptions) to this process for minor changes and bugfixes, but generally speaking it is a good idea to search or ask in one of our communication channels (see below) before undertaking a change, because most changes should go through the petitions process.

Also, please be sure to follow our [code of conduct](https://www.classicpress.net/democracy/#democracy-conduct) in all interactions with ClassicPress community members.

## Table of Contents

- [Communication channels](#communication-channels)
- [Review criteria](#review-criteria)
- [What to work on?](#what-to-work-on)
- [Setting up a local development environment](#setting-up-a-local-development-environment)
- [Tips for good PRs](#tips-for-good-prs)
- [How to review a PR](#how-to-review-a-pr)
  - [Merging PRs](#merging-prs)
- [Automated tests](#automated-tests)
- [Backporting changes from WordPress](#backporting-changes-from-wordpress)
  - [Making a backport PR](#making-a-backport-pr)
  - [Tips for a good backport PR](#tips-for-a-good-backport-pr)

## Communication channels

We encourage you to join and ask any questions you have about contributing.

- [Slack](https://www.classicpress.net/join-slack/) - great for real-time chat, posting screenshots and asking questions about anything you're stuck on. Or just socializing.
- [Petitions](https://forums.classicpress.net/c/governance/petitions/77) - for proposing new features or major changes for consideration by the community.
- [GitHub issues](https://github.com/ClassicPress/ClassicPress/issues) - for proposing or discussing bugfixes, or minor improvements. Generally it is a good idea to create a petition for anything that may take a significant amount of time or that may have backwards compatibility implications.
- [Forums](https://forums.classicpress.net/) - for posting questions and searching for solutions. The forums are our most active community channel other than Slack.

## Review criteria

When evaluating bug fixes and other code changes in pull requests (_PRs_), we look for these things, ideally all of them:

1. The change impacts or is likely to impact existing ClassicPress users. Otherwise, there are literally thousands of things we could look at, but we need to prioritize our development time. Right now the best tool we have for this is [petitions](https://forums.classicpress.net/c/governance/petitions/77).
2. The change is not going to break backward compatibility, and has minimal effects on the existing plugin and theme ecosystem. A good way to evaluate the effects of a change on plugins or themes is to do a search on [wpdirectory](https://wpdirectory.net). (Major changes are also a possibility but require a planning effort around when and how they will be released, as well as agreement from the community per our [democratic process](https://www.classicpress.net/democracy/).)
3. The change has automated tests.
4. We understand the code change very well or can ask questions of someone who understands it very well.

If your change meets all of these criteria then we will most likely accept it.

## What to work on?

If you're not sure where to start contributing, here are some ideas:

- [Set up a local development environment](#setting-up-a-local-development-environment) and try out ClassicPress on your own computer.
- Review and test [existing PRs](https://github.com/ClassicPress/ClassicPress/pulls), especially looking at how well they fit the criteria described above. Let us know how you tested the changes and what you found. We need to be thorough and careful with any changes we make, and the more eyes we can get on our PRs the better. Screenshots and text instructions documenting the status and testing of all PRs are very useful, as well as videos and gifs if applicable. These help us know the status and completion of each PR so that we can be thorough and careful with any changes we make, and the more eyes we can get on our PRs the better.
- Take a look at issues with the [`help wanted`](https://github.com/ClassicPress/ClassicPress/labels/help%20wanted) or [`good first issue`](https://github.com/ClassicPress/ClassicPress/labels/good%20first%20issue) labels.
- Submit PRs based on our [planned milestones](https://github.com/ClassicPress/ClassicPress/milestones), or exploratory PRs with your own suggested changes. Please remember these will be subject to review to make sure they are in line with the project's direction.

## Setting up a local development environment

1. Make sure you have [git](https://git-scm.com/), [Apache](http://httpd.apache.org/) and [MySQL](https://www.mysql.com/)/MariaDB installed and working on your computer.
2. Fork ClassicPress to your GitHub account using the GitHub website.
3. Clone your ClassicPress fork to your computer using the following command:

   ```
   git clone https://github.com/YOUR_GITHUB_USERNAME/ClassicPress
   ```

   Run this `git clone` command from within the webroot directory of your Apache webserver, or otherwise point your webserver at the resulting directory.
4. Change to the ClassicPress repository: `cd ClassicPress`
5. Add the main ClassicPress repository so that you can pull changes from it: `git remote add upstream https://github.com/ClassicPress/ClassicPress`
6. Run `git remote -v` and confirm that you have your own fork set as `origin` and the main ClassicPress repository set as `upstream`. The rest of this document assumes you have things set up this way.
7. Create a MySQL database to connect with your CP instance.
8. In your browser, go to the `src` directory on your localhost instance of CP to run the setup. (You can also configure the `wp-config.php` file yourself instead.)
9. Use normal `git` commands to check out a branch, and you will immediately be able to see the changes from that branch in your web browser _(see [How to review a PR](#how-to-review-a-pr) below)_.

At this point you have a working local development environment. Here are some further steps for more advanced usage:

- Set up `phpunit` to run and develop automated tests _(see [Automated tests](#automated-tests) below)_.
- Set up `grunt` to run the pre-commit checks, make your own builds of ClassicPress, and perform other miscellaneous build and development tasks:
  - Set up [`nvm`](https://github.com/nvm-sh/nvm) or a similar program to manage Node versions.
  - Run `nvm install` or use your version manager to switch to the current version of Node used by ClassicPress. Run this step periodically.
  - Run `npm install` to install/update the ClassicPress `npm` dependencies. Run this step periodically.
  - When changing Node versions, you may need to run `npm install -g grunt-cli` to make the `grunt` command work.
  - Run `grunt build` to create your own build of ClassicPress (in the `build` directory) or run `grunt precommit:verify` to test whether any other files may need to be updated for your PR. There are many other `grunt` commands available in the `Gruntfile.js` file.

## Tips for good PRs

- A good pull request (PR) should be for a single, specific change. The change should be explained using the template provided on GitHub.
- Any new or modified code should have automated tests, especially if the way it works is at all complicated.
- It is always a good idea to look at the "Files" view on GitHub after submitting your PR to verify that the changes look as expected. Generally, there should be no "extra" changes that are not related to the purpose of your PR like reformatting or re-aligning files. Such changes are best done in a separate PR just for that purpose. If you see something that looks out of place, you can make an edit to fix it and push a new commit to your PR.
- Generally it is best to only use one pull request for each change, even if the initial code needs revision after review and feedback. Closing the initial pull request and opening a new one makes it more difficult to follow the history of the change, and it is much better to just update the existing PR in response to any feedback received.
- To be accepted, a PR **must** pass the automated tests which are run using GitHub Actions. Sometimes the tests experience unrelated failures, we will be happy to help resolve these. Usually, when this happens we start a separate PR to resolve the failure, and once that is merged, your PR will need to be updated as per the next bullet point.
- You can always refresh your PR branch against the latest ClassicPress code using the following sequence of commands:

  ```
  git checkout your-pr-branch
  git fetch upstream
  git merge upstream/develop
  git push origin your-pr-branch
  ```

## How to review a PR

1. See the instructions on [setting up a local development environment](#setting-up-local-testing-and-dev-environment) above.
2. Set up a remote link to the user who submitted the PR. For example, if GitHub user `bahiirwa` submitted the PR:
   ```
   git remote add bahiirwa https://github.com/bahiirwa/ClassicPress
   git fetch bahiirwa
   ```
3. Look at the PR on the GitHub web interface and note the _branch name_ that was used to submit it. For example: `bahiirwas-cool-pr`
4. Checkout of the branch with the changes you want to test using
   ```
   git checkout -b bahiirwas-cool-pr <name-of-remote>/bahiirwas-cool-pr
   ```
5. Run tests as the PR suggests, or stress test the PR trying to confirm whether the change works as intended.
6. Submit your feedback in the comment section of the same PR on the CP Github repo. Screenshots, gifs, video and text instructions documenting the tests are very useful to document your testing.
7. Thank you for your time and effort in helping us review PRs.

### Merging PRs

_This section only applies to **core committers** - people who have access to merge changes to ClassicPress._

See [MAINTAINING.md](MAINTAINING.md) for some guidelines for maintaining ClassicPress.

## Automated tests

Any change that introduces new code or changes behavior should have automated tests. These tests mostly use [PHPUnit](https://phpunit.de/) to verify the behavior of the many thousands of lines of PHP code in ClassicPress.

If you're not familiar with automated tests, the concept is basically **code that runs other code** and verifies its behavior.

Documentation for running and updating our existing tests, as well as the code for the tests themselves, can be found in the [`tests/phpunit`](../tests/phpunit) subdirectory of this repository.

## Backporting changes from WordPress

ClassicPress version `1.0.0` is a fork of [WordPress `4.9.x`](https://github.com/ClassicPress/ClassicPress/tree/LAST_WP_COMMIT). Since then, a number of changes have been made to WordPress for performance, bugfixes or new features.

ClassicPress `1.x.x` is committed to keeping backward compatibility with the WordPress 4.9 branch. However, we're also open to merging bugfixes and enhancements from later versions of WordPress, **as long as the review criteria listed above are met**.

Changes must be proposed and reviewed **individually** - long lists of tickets or changesets from individual WordPress versions don't give us what we need in order to plan and understand each change well.

If you're not sure about any of that, then it's a good idea to ask first. A good way is to create an issue for the specific change you're interested in, along with links to the relevant WordPress changesets and tickets, and any other information you have about how the change works.

There are some changes that we already know we want to backport because they fit into our plans for future versions of ClassicPress. You can find some examples under the [`WP backport` label](https://github.com/ClassicPress/ClassicPress/labels/WP%20backport).

You can see a list of all WordPress changes since the fork, along with information about which ones have already been included in ClassicPress, at [backports.classicpress.net](https://backports.classicpress.net).

### Making a backport PR

When you're ready to backport a code change:

1. Identify the WordPress **changeset number** that you'd like to port such as `43123`.
2. Run `bin/backport-wp-commit.sh` script in your terminal/command prompt to apply the change to your code:

   ```
   bin/backport-wp-commit.sh CHANGESET_NUMBER
   ```

   This will create a new branch and apply the WordPress changeset to it. If you're porting multiple changesets, you can create a new `git` branch first and use the `-c` option to this script to apply each changeset to your current branch instead:

   ```
   bin/backport-wp-commit.sh -c CHANGESET_NUMBER
   ```

   Using this script for all backports saves time for you and for the maintainers. It uses a standardized format for commit messages, which makes it possible for us to track which WordPress changes we've already included.

   **Pay close attention to the output of this script** and let us know if you see anything strange or confusing!
3. Resolve merge conflicts (if any) by editing the conflicting files, running `git add` and then `git commit`. If you cannot resolve the conflicts, ask for help in the [**#core** Slack channel](https://www.classicpress.net/join-slack/) or just push your branch as-is and we'll take care of it!
4. Repeat steps 2 and 3 for any further WordPress changesets that are related to this PR.
5. Push your branch to your fork on GitHub using `git push origin merge/wp-rCHANGESET_NUMBER` or the name of your current branch.
6. Use the GitHub website to make a PR against the `develop` branch for review.

### Tips for a good backport PR

- Give your pull request a clear title that explains in a few words what change or feature you are backporting from WordPress.
- In the body text of the PR, explain what the specific change is, how it works and why ClassicPress in particular should adopt it.
- Often there is a lot of good discussion about each change in the relevant WordPress Trac tickets. It makes the maintainers' job much easier to see this discussion summarized and linked.
- Explain what testing has been done on your PR and what may be left to do. Screenshots and text instructions documenting the changes and how to test them are very useful. Those tell other contributors looking at the PR how to verify the changes. "This was tested in WordPress" **is not enough** to meet our review criteria, see [Review Criteria](#review-criteria) above.
- Look through the WordPress tickets and commits associated with these changes. Make sure there are no other changes to commit, no outstanding issues with the WordPress changes mentioned in Trac tickets, and summarize your findings on your PR.
- If there are merge conflicts during the backport, check if they have been resolved correctly by comparing the final changes from the full PR against the corresponding WP changeset(s). The code changes to be applied to ClassicPress should be basically the same as the code changes that were applied to WordPress.
