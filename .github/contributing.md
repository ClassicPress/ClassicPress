You can help fix bugs by submitting Pull Requests. Most of the bugs are shared via the [issues page](https://github.com/ClassicPress/ClassicPress/issues). 

## Fixing Bugs
When evaluating bug fixes pull requests(PRs), we look for, if not all these things

  - The change impacts existing ClassicPress users. (Otherwise, there are literally thousands of things we could look at, but we need to prioritize our development time.)
  - The change is not going to break any other use cases.
  - The change has automated tests.
  - We understand the change very well or can ask questions of someone who understands it very well.

## Backporting Changesets from WordPress
WordPress since 5.0 changed a lot of things and to keep the compatibility on new features or performances but also for bugfixes and this require a selection of the various changesets.

The backport script automatize the process on do a pull request but the big question is how to chosen the right changeset? Also how to test it and document it?


Evaluate the changeset

The focus of ClassicPress is to be business oriented and avoid Gutenberg so one of the first check is to avoid changeset that cover Gutenberg or the Editor component.


Pick the branch

Select the right branch is important and since 4.9.x is not developed actively except security issues can be used only for this reason.

The other options is to check the 5.x branches but is required to do a check about the code if exist in ClassicPress.

The backport script create a branch with the changeset and is possible to do a test about if the change is supported and in case if are required manual changes.


Documenting

Before open a pull request we need to validate the changeset in ClassicPress because:

    Often there aren't unit tests in a changeset
    It is missing documentation/code about how to replicate the issue that is patching
    Test if the issues is really present in ClassicPress

ClassicPress want work on improving the code quality, the documentation and tests so we can thrust at 100% every changeset/ticket without this 3 informations in every pull request for merging.

As every patch as maintainer we need a way to replicate and test it so only a pull request is not enough.


About backward compatibility

This open a big discussion about the various point of view about backward compatibility of ClassicPress:

    Compatibility with WP 4.9.x for version 1.x
    Bugfix that improve CP 1.x from WP 5.x+
    Improve code consistence of WP 4.9.x
    Backport of new features from WP 5.x 

As the branch 1.x is focused on backward compatibility we need to be careful on implementing changeset and we need to check this 4 area to give them priority about CP integration.


Tip: check on https://wpdirectory.net/ if how many plugins/themes are affected by this changeset.
