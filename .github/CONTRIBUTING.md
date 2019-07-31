When considering to contribute to this repository, please note:
- [Our code of conduct](https://www.classicpress.net/democracy/#democracy-conduct)
- [Exceptions to contributions](https://www.classicpress.net/democracy/#democracy-exceptions). 

Our request is that you follow these guidelines stated in the documents above in all your interactions with the project. 

# Start helping
We encourage you to join our [slack channels](https://join.slack.com/t/classicpress/shared_invite/enQtNDIwNjY2OTg1MjAxLWJiM2U2NmY3ZjFlZjQ4Zjk2OGI4ZTg3NzY1ZTU3NzI3OTRjMTU0YzAzOWUyZmZlODgyOWE1YTViYjcwY2Y5YzI) for meetings to start guided PRs or get a detailed background of the project. The other avenues for PR direction are [issues on Github](https://github.com/ClassicPress/ClassicPress/issues), [forums](https://forums.classicpress.net/) and the [petitions](https://petitions.classicpress.net/) website.

***Considerations***

When evaluating bug fixes and all pull requests(PRs), we look for, if not all these things

  - The change impacts existing ClassicPress users. (Otherwise, there are literally thousands of things we could look at, but we need to prioritize our development time.)
  - The change is not going to break backward compatibility ( apart from major changes which need a community backed petition and minor changes which should usually be discussed beforehand in Slack meetings or forums).
  - The change has automated tests.
  - We understand the change very well or can ask questions of someone who understands it very well.
  
You can start helping by submitting pull requests(PRs) to the develop branch. These are not limited to:

## Documentation
Good documentation will make the work of making ClassicPress a better software much faster and a better user experience.

## Fixing Bugs
Most of the bugs are shared via the [issues page](https://github.com/ClassicPress/ClassicPress/issues). 

## New Feature Requests
We have a number of [feature requests or petitions from our petitions website](https://petitions.classicpress.net/) submitted by different users and upvoted. It is desirable to start with the [planned](https://petitions.classicpress.net/?view=planned), [most wanted](https://petitions.classicpress.net/?view=most-wanted) requests or even submit PRs with your own suggested changes (However, the latter will be subject to review so as to keep inline with the projects direction).

## Backporting Changesets from WordPress
ClassicPress version 1.0.0 is a fork of WordPress 4.9.x however, since WordPress 5.0 a number of changes have been made to the core not limited to performance, bug fixes or new features. 

ClassicPress is commited to keeping compatibility of WordPress of WordPress 4.9. We however need help choosing, testing and documenting changesets to commit to the core with the backwards compatibility in mind. Below are some guidelines:

1. ***Evaluate the changeset***
For any changes in functions/methods, a check on https://wpdirectory.net/ is needed to evaluate how many plugins/themes use the function in question and are affected by the proposed changeset. wpdirectory provides a platform to search code in all the plugins in the WordPress Repository.

1. ***Pick the branch***
Selecting the right branch is important since 4.9.x is not developed actively except for security issues.

1. ***Documenting***
Properly written supporting documentation for the changeset is important. This will guide the maintainers to evaluate the tests, consider the challenges the changeset seeks to solve or if the problem exists in ClassicPress posing a problem to the users.

***Note:*** ClassicPress seeks to improve the exisiting code quality, the documentation and tests. This might result in some delays in merging a pull request for merging. 
