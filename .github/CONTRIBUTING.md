# Our [Code of Conduct](https://www.classicpress.net/democracy/#democracy-conduct) and [exceptions](https://www.classicpress.net/democracy/#democracy-exceptions)
Our code of conduct is simple: Be good to one another, take criticism well and follow the democratic process. When contributing to this repository, please note we have a [code of conduct](https://www.classicpress.net/democracy/#democracy-conduct) with some [exceptions](https://www.classicpress.net/democracy/#democracy-exceptions). 

Our request is that you follow these guidelines in all your interactions with the project. 

# Start helping
You can start helping by submitting pull requests(PRs) to the develop branch. This is not limited to:

## Documentation
Good documentation will make the work of making ClassicPress a better software much faster and a better user experience.

## Fixing Bugs
Most of the bugs are shared via the [issues page](https://github.com/ClassicPress/ClassicPress/issues). When evaluating bug fixes pull requests(PRs), we look for, if not all these things

  - The change impacts existing ClassicPress users. (Otherwise, there are literally thousands of things we could look at, but we need to prioritize our development time.)
  - The change is not going to break any other use cases.
  - The change has automated tests.
  - We understand the change very well or can ask questions of someone who understands it very well.

## New Feature Requests
We have a number of [feature requests or petitions from our petitions website](https://petitions.classicpress.net/) submitted by different users and upvoted. It is desirable to start with the [planned](https://petitions.classicpress.net/?view=planned) or the [most wanted](https://petitions.classicpress.net/?view=most-wanted) or even submit PRs with your own suggested changes.

## Backporting Changesets from WordPress
ClassicPress version 1.0.0 is a fork of WordPress 4.9.x however, since WordPress 5.0 a number of changes have been made to the core not limited to performance, bug fixes or new features. ClassicPress is commited to keeping compatibility of WordPress of WordPress 4.9. We however need help choosing, testing and documenting changesets to commit to the core with the backwards compatibility in mind.

The focus of ClassicPress is to be business oriented and avoid Gutenberg so one of the first check is to avoid changeset that cover Gutenberg or the Editor component. Below are some guidelines:

1. ***Evaluate the changeset***
First, check https://wpdirectory.net/ to evaluate how many plugins/themes are affected by this changeset. 

1. ***Pick the branch***
Selecting the right branch is important since 4.9.x is not developed actively except for security issues. ~~The other options is to check the 5.x branches but it is not a pre-requisite to do a check about the code if it exists in ClassicPress. The backport script will create a branch with the changeset. A test will be done about change to check if it is supported. Some times  manual changes are required.~~

1. ***Documenting***
Properly written supporting documentation for the changeset is important. This will guide the mainatainers to evaluate the tests, consider the challenges the changeset seeks to solve or if the problem exists in ClassicPress posing a problem to the users.

***Note:*** ClassicPress seeks to improve the exisiting code quality, the documentation and tests. This might result in some delays in merging a pull request for merging. 
