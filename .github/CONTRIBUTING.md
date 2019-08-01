Before contributing to this repository, please read our [Democracy page](https://www.classicpress.net/democracy/) to understand how we make decisions about what is included in ClassicPress, and our [roadmap](https://www.classicpress.net/roadmap/) to see what's already planned for the next couple of releases.

The **petitions process** mentioned in that document is very important to help us prioritize new features based on the needs of our users. There are some [exceptions](https://www.classicpress.net/democracy/#democracy-exceptions) to this process for minor changes and bugfixes, but generally speaking it is a good idea to create an issue or a forum thread, or ask in Slack, before undertaking a change.

Also, please be sure to follow our [code of conduct](https://www.classicpress.net/democracy/#democracy-conduct) in all interactions with ClassicPress community members.

## Communication channels

We encourage you to join and ask any questions you have about contributing.

- [Slack](https://www.classicpress.net/join-slack/) - great for real-time chat, posting screenshots and asking questions about anything you're stuck on. Or just socializing.
- [Petitions website](https://petitions.classicpress.net/) - for proposing new features or major changes for consideration by the community.
- [GitHub issues](https://github.com/ClassicPress/ClassicPress/issues) - for proposing or discussing bugfixes, or minor improvements. Generally it is a good idea to create a petition for anything that may take a significant amount of time or that may have backwards compatibility implications.
- [Forums](https://forums.classicpress.net/) - a lot of good info and discussion here too

## Review criteria

When evaluating bug fixes and other code changes in pull requests (PRs), we look for these things, ideally all of them:

  1. The change impacts existing ClassicPress users. Otherwise, there are literally thousands of things we could look at, but we need to prioritize our development time. Right now the best tool we have for this is the [petitions site](https://petitions.classicpress.net/).
  2. The change is not going to break backward compatibility, and has minimal effects on the existing plugin and theme ecosystem. A good way to evaluate the effects of a change on plugins or themes is to do a search on https://wpdirectory.net. (Major changes are also a possibility but require a planning effort around when and how they will be released, as well as agreement from the community per our [democratic process](https://www.classicpress.net/democracy/).
  3. The change has automated tests.
  4. We understand the code change very well or can ask questions of someone who understands it very well.

If your change meets all of these criteria then we will most likely accept it.

We take a lot of care with our platform, and ClassicPress is maintained by volunteers in our free time. This means there may be some delays in merging pull requests.

## Fixing Bugs
Most of the bugs are shared via the [issues page](https://github.com/ClassicPress/ClassicPress/issues). 

## New Feature Requests
We have a number of [feature requests or petitions from our petitions website](https://petitions.classicpress.net/) submitted by different users and upvoted. It is desirable to start with the [planned](https://petitions.classicpress.net/?view=planned), [most wanted](https://petitions.classicpress.net/?view=most-wanted) requests or even submit PRs with your own suggested changes (However, the latter will be subject to review so as to keep inline with the projects direction).

## Backporting Changesets from WordPress
ClassicPress version 1.0.0 is a fork of WordPress 4.9.x however, since WordPress 5.0 a number of changes have been made to the core not limited to performance, bug fixes or new features. 

ClassicPress is commited to keeping compatibility of WordPress of WordPress 4.9. We however need help choosing, testing and documenting changesets to commit to the core with the backwards compatibility in mind. Below are some guidelines:

1. ***Evaluate the changeset***
For any changes in functions/methods, a check on https://wpdirectory.net/ is needed to evaluate how many plugins/themes use the function in question and are affected by the proposed changeset. Wpdirectory provides a platform to search the code in all the plugins hosted on the WordPress Repository.

1. ***Pick the branch***
Selecting the right branch is important since 4.9.x is not developed actively except for security issues.

1. ***Documenting***
Properly written supporting documentation for the changeset is important. This will guide the maintainers to evaluate the tests, consider the challenges the changeset seeks to solve or if the problem exists in ClassicPress posing a problem to the users.

***Note:*** ClassicPress seeks to improve the exisiting code quality, the documentation and tests. This might result in some delays in merging a pull request for merging. 
