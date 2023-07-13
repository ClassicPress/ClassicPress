## Running the phpunit tests

0. **Linux and OS X are supported.** Windows users will probably have an easier time using a Linux virtual machine or the "Windows Subsystem for Linux" shell available for recent versions of Windows.

1. **Be sure your PHP installation supports the `mbstring` module.** If you're not sure, run `php -m` and look for `mbstring` in the list. If it's not present, and you're on Ubuntu or Debian Linux, you can run `sudo apt-get install php-mbstring` to fix this.

2. **Clone the ClassicPress `git` repository to your computer, if needed:**

```
git clone https://github.com/ClassicPress/ClassicPress.git
cd ClassicPress
```
3. **Install MySQL and create an empty MySQL database.** The test suite will **delete all data** from all tables for whichever MySQL database is configured. *Use a separate database from any ClassicPress or WordPress installations on your computer*.

4. **Set up a config file for the tests.** In your repository folder (`ClassicPress`), copy `wp-tests-config-sample.php` to `wp-tests-config.php`, and enter your database credentials from the step above. *Use a separate database from any ClassicPress or WordPress installations on your computer*, because data in this database **will be deleted** with each test run. You may want to check everything works at this stage on your `localhost` by following a similar process using copying the `wp-config-sample.php` to `wp-config.php` and accessing the ClassicPress code locally.

5. **Install** the automated test files:

```composer install```

You will need to have the [composer](https://getcomposer.org)  available on your system first.

6. **Run the tests:**

Once the dependencies are installed you can use the following commands:

- to run PHPUnit tests

```
composer phpunit
```

- to run coding standards checks on core files

```
composer phpcs
```

- to run coding standards checks on the test files

```
composer phpcs-tests
```

To execute only particular test file, groups or specific test (useful when debugging):

```
composer phpunit -- tests/phpunit/tests/date/query.php
composer phpunit -- --group=date
composer phpunit -- --filter=test_should_format_date
```

7. **Explore the existing tests** in the `tests/phpunit/tests` directory, look at how they work, edit them and break them, and write your own.

_Note: this documentation is adapted from https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/ which contains more detail, but there are some differences in how automated tests work with recent versions of WordPress._

## Notes

Test cases live in the `tests` subdirectory of this directory.  All files in that directory will be included by default.  Extend the `WP_UnitTestCase` class to ensure your test is run.

Helper files including the base `WP_UnitTestCase` class live in the `includes` subdirectory of this directory.

`phpunit` will initialize and install a (more or less) complete running copy of ClassicPress each time it is run.  This makes it possible to run functional interface and module tests against a fully working database and codebase, as opposed to pure unit tests with mock objects and stubs.  Pure unit tests may be used also, of course.

Changes to the test database will be rolled back after each test is finished, to ensure a clean start next time the tests are run.

phpunit is intended to run at the command line, not via a web server.
