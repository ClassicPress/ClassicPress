#!/usr/bin/env perl

use strict;
use warnings;

use File::Basename;

chdir dirname $0;
chdir '..';

chdir 'build';
my $changed = '';
if ( system( 'git diff --quiet --exit-code' ) > 0 ) {
	$changed = 'file(s) modified';
} elsif ( system( 'git diff --quiet --cached --exit-code' ) > 0 ) {
	$changed = 'file(s) staged';
} elsif ( `git ls-files -o --exclude-standard | wc -l` > 0 ) {
	$changed = 'untracked file(s)';
}
if ( $changed ) {
	print "$changed:\n\n";
	print `git status | sed 's/^/  /'`;
	die "\nDo the commit for the release before running this script!\n";
}
chdir '..';

my $ok = 1;

my $diff = `bash -c 'diff -u <( cd src/; git ls-files ) <( cd build/; git ls-files )'`;
for ( split /\r?\n/, $diff ) {
	next unless /^([+-])([^+-].*)$/;
	my $op = $1;
	my $fn = $2;
	if ( $op eq '+' ) {
		# File present in build/ but not in src/
		next if $fn eq 'wp-config-sample.php';
		next if $fn eq 'wp-includes/js/tinymce/wp-tinymce.js.gz';
		next if $fn eq 'wp-includes/js/wp-emoji-release.min.js';
		next if $fn =~ /^wp-admin\/css\/colors\/[a-z]+\/colors(-rtl)?(\.min)?\.css$/;
		# Minified and RTL files
		if ( $fn =~ /(\.min\.(js|css)|-rtl(\.min)?\.css)$/ ) {
			my $fn_src = $fn;
			$fn_src =~ s/\.min\.(js|css)$/.$1/;
			$fn_src =~ s/-rtl\.css$/.css/;
			next if -f "src/$fn_src";
		}
	} else {
		# File present in src/ but not in build/
		next if $fn eq 'wp-content/plugins/hello.php';
		next if $fn eq 'wp-includes/js/backbone.js';
		next if $fn eq 'wp-includes/js/underscore.js';
		next if $fn eq 'wp-includes/js/jquery/jquery.masonry.js';
		next if $fn eq 'wp-includes/js/tinymce/tinymce.js';
		next if $fn =~ /^wp-includes\/js\/jquery\/ui\/.*\.js$/;
		next if $fn =~ /^wp-includes\/js\/media\/.*\.js$/;
		next if $fn =~ /^wp-includes\/js\/media\/.*\.ejs$/;
	}

	# If we get here, there's a problem
	if ( $ok ) {
		print "--- only in src/\n+++ only in build/\n";
		$ok = 0;
	}
	print "$op $fn\n";
}

if ( $ok ) {
	print "Release files look OK\n";
} else {
	print "\n";
	die "Unexpected file(s) added to and/or removed from build!\n";
}
