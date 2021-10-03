<?php

/**
 * @group pomo
 */
class Tests_POMO_PO extends WP_UnitTestCase {
	function setUp() {
		require_once ABSPATH . '/wp-includes/pomo/po.php';
		// not so random wordpress.pot string -- multiple lines
		$this->mail = "Your new ClassicPress blog has been successfully set up at:

%1\$s

You can log in to the administrator account with the following information:

Username: %2\$s
Password: %3\$s

We hope you enjoy your new blog. Thanks!

--The ClassicPress Team
http://wordpress.org/
";
		$this->mail    = str_replace( "\r\n", "\n", $this->mail );
	$this->po_mail = '""
"Your new ClassicPress blog has been successfully set up at:\n"
"\n"
"%1$s\n"
"\n"
"You can log in to the administrator account with the following information:\n"
"\n"
"Username: %2$s\n"
"Password: %3$s\n"
"\n"
"We hope you enjoy your new blog. Thanks!\n"
"\n"
"--The ClassicPress Team\n"
"http://wordpress.org/\n"';
		$this->a90 = str_repeat("a", 90);
		$this->po_a90 = "\"$this->a90\"";
    }

	function test_prepend_each_line() {
		$po = new PO();
<<<<<<< HEAD
		$this->assertEquals('baba_', $po->prepend_each_line('', 'baba_'));
		$this->assertEquals('baba_dyado', $po->prepend_each_line('dyado', 'baba_'));
		$this->assertEquals("# baba\n# dyado\n# \n", $po->prepend_each_line("baba\ndyado\n\n", '# '));
=======
		$this->assertSame( 'baba_', $po->prepend_each_line( '', 'baba_' ) );
		$this->assertSame( 'baba_dyado', $po->prepend_each_line( 'dyado', 'baba_' ) );
		$this->assertSame( "# baba\n# dyado\n# \n", $po->prepend_each_line( "baba\ndyado\n\n", '# ' ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_poify() {
		$po = new PO();
<<<<<<< HEAD
		//simple
		$this->assertEquals('"baba"', $po->poify('baba'));
		//long word
		$this->assertEquals($this->po_a90, $po->poify($this->a90));
		// tab
		$this->assertEquals('"ba\tba"', $po->poify("ba\tba"));
		// do not add leading empty string of one-line string ending on a newline
		$this->assertEquals('"\\\\a\\\\n\\n"', $po->poify("\a\\n\n"));
		// backslash
		$this->assertEquals('"ba\\\\ba"', $po->poify('ba\\ba'));
		// random wordpress.pot string
		$src = 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.';
		$this->assertEquals("\"Categories can be selectively converted to tags using the <a href=\\\"%s\\\">category to tag converter</a>.\"", $po->poify($src));
=======
		// Simple.
		$this->assertSame( '"baba"', $po->poify( 'baba' ) );
		// Long word.
		$this->assertSame( $this->po_a90, $po->poify( $this->a90 ) );
		// Tab.
		$this->assertSame( '"ba\tba"', $po->poify( "ba\tba" ) );
		// Do not add leading empty string of one-line string ending on a newline.
		$this->assertSame( '"\\\\a\\\\n\\n"', $po->poify( "\a\\n\n" ) );
		// Backslash.
		$this->assertSame( '"ba\\\\ba"', $po->poify( 'ba\\ba' ) );
		// Random wordpress.pot string.
		$src = 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.';
		$this->assertSame( '"Categories can be selectively converted to tags using the <a href=\\"%s\\">category to tag converter</a>."', $po->poify( $src ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$this->assertSameIgnoreEOL( $this->po_mail, $po->poify( $this->mail ) );
	}

	function test_unpoify() {
		$po = new PO();
<<<<<<< HEAD
		$this->assertEquals('baba', $po->unpoify('"baba"'));
		$this->assertEquals("baba\ngugu", $po->unpoify('"baba\n"'."\t\t\t\n".'"gugu"'));
		$this->assertEquals($this->a90, $po->unpoify($this->po_a90));
		$this->assertEquals('\\t\\n', $po->unpoify('"\\\\t\\\\n"'));
		// wordwrapped
		$this->assertEquals( 'babadyado', $po->unpoify( "\"\"\n\"baba\"\n\"dyado\"" ) );
		$this->assertEqualsIgnoreEOL( $this->mail, $po->unpoify( $this->po_mail ) );
=======
		$this->assertSame( 'baba', $po->unpoify( '"baba"' ) );
		$this->assertSame( "baba\ngugu", $po->unpoify( '"baba\n"' . "\t\t\t\n" . '"gugu"' ) );
		$this->assertSame( $this->a90, $po->unpoify( $this->po_a90 ) );
		$this->assertSame( '\\t\\n', $po->unpoify( '"\\\\t\\\\n"' ) );
		// Wordwrapped.
		$this->assertSame( 'babadyado', $po->unpoify( "\"\"\n\"baba\"\n\"dyado\"" ) );
		$this->assertSameIgnoreEOL( $this->mail, $po->unpoify( $this->po_mail ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_export_entry() {
		$po = new PO();
<<<<<<< HEAD
		$entry = new Translation_Entry(array('singular' => 'baba'));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", $po->export_entry($entry));
		// plural
=======
		$entry = new Translation_Entry( array( 'singular' => 'baba' ) );
		$this->assertSame( "msgid \"baba\"\nmsgstr \"\"", $po->export_entry( $entry ) );
		// Plural.
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
		$entry = new Translation_Entry(
			array(
				'singular' => 'baba',
				'plural'   => 'babas',
			)
		);
		$this->assertSameIgnoreEOL(
			'msgid "baba"
msgid_plural "babas"
msgstr[0] ""
msgstr[1] ""',
			$po->export_entry( $entry )
		);
		$entry = new Translation_Entry(
			array(
				'singular'            => 'baba',
				'translator_comments' => "baba\ndyado",
			)
		);
		$this->assertSameIgnoreEOL(
			'#  baba
#  dyado
msgid "baba"
msgstr ""',
			$po->export_entry( $entry )
		);
		$entry = new Translation_Entry(
			array(
				'singular'           => 'baba',
				'extracted_comments' => 'baba',
			)
		);
		$this->assertSameIgnoreEOL(
			'#. baba
msgid "baba"
msgstr ""', $po->export_entry($entry));
		$entry = new Translation_Entry(array(
			'singular' => 'baba',
				'extracted_comments' => 'baba',
				'references'         => range( 1, 29 ),
			)
		);
		$this->assertSameIgnoreEOL(
			'#. baba
#: 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28
#: 29
msgid "baba"
<<<<<<< HEAD
msgstr ""', $po->export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array()));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", $po->export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array('куку', 'буку')));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"куку\"", $po->export_entry($entry));
=======
msgstr ""',
			$po->export_entry( $entry )
		);
		$entry = new Translation_Entry(
			array(
				'singular'     => 'baba',
				'translations' => array(),
			)
		);
		$this->assertSame( "msgid \"baba\"\nmsgstr \"\"", $po->export_entry( $entry ) );

		$entry = new Translation_Entry(
			array(
				'singular'     => 'baba',
				'translations' => array( 'куку', 'буку' ),
			)
		);
		$this->assertSame( "msgid \"baba\"\nmsgstr \"куку\"", $po->export_entry( $entry ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$entry = new Translation_Entry(
			array(
				'singular'     => 'baba',
				'plural'       => 'babas',
				'translations' => array( 'кукубуку' ),
			)
		);
		$this->assertSameIgnoreEOL(
			'msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"', $po->export_entry($entry));

		$entry = new Translation_Entry(
			array(
				'singular'     => 'baba',
				'plural'       => 'babas',
				'translations' => array( 'кукубуку', 'кукуруку', 'бабаяга' ),
			)
		);
		$this->assertSameIgnoreEOL(
			'msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', $po->export_entry($entry));
		// context
		$entry = new Translation_Entry(
			array(
				'context'      => 'ctxt',
				'singular'     => 'baba',
				'plural'       => 'babas',
				'translations' => array( 'кукубуку', 'кукуруку', 'бабаяга' ),
				'flags'        => array( 'fuzzy', 'php-format' ),
			)
		);
		$this->assertSameIgnoreEOL(
			'#, fuzzy, php-format
msgctxt "ctxt"
msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', $po->export_entry($entry));
    }

	function test_export_entries() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po = new PO();
<<<<<<< HEAD
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export_entries());
=======
		$po->add_entry( $entry );
		$po->add_entry( $entry2 );
		$this->assertSame( "msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export_entries() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_export_headers() {
		$po = new PO();
<<<<<<< HEAD
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$this->assertEquals("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"", $po->export_headers());
=======
		$po->set_header( 'Project-Id-Version', 'WordPress 2.6-bleeding' );
		$po->set_header( 'POT-Creation-Date', '2008-04-08 18:00+0000' );
		$this->assertSame( "msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"", $po->export_headers() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_export() {
		$po = new PO();
<<<<<<< HEAD
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export(false));
		$this->assertEquals("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"\n\nmsgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export());
=======
		$entry  = new Translation_Entry( array( 'singular' => 'baba' ) );
		$entry2 = new Translation_Entry( array( 'singular' => 'dyado' ) );
		$po->set_header( 'Project-Id-Version', 'WordPress 2.6-bleeding' );
		$po->set_header( 'POT-Creation-Date', '2008-04-08 18:00+0000' );
		$po->add_entry( $entry );
		$po->add_entry( $entry2 );
		$this->assertSame( "msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export( false ) );
		$this->assertSame( "msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"\n\nmsgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export() );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}


	function test_export_to_file() {
		$po = new PO();
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$po->add_entry($entry);
		$po->add_entry($entry2);

		$temp_fn = $this->temp_filename();
<<<<<<< HEAD
		$po->export_to_file($temp_fn, false);
		$this->assertEquals($po->export(false), file_get_contents($temp_fn));

		$temp_fn2 = $this->temp_filename();
		$po->export_to_file($temp_fn2);
		$this->assertEquals($po->export(), file_get_contents($temp_fn2));
=======
		$po->export_to_file( $temp_fn, false );
		$this->assertSame( $po->export( false ), file_get_contents( $temp_fn ) );

		$temp_fn2 = $this->temp_filename();
		$po->export_to_file( $temp_fn2 );
		$this->assertSame( $po->export(), file_get_contents( $temp_fn2 ) );
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)
	}

	function test_import_from_file() {
		$po = new PO();
<<<<<<< HEAD
		$res = $po->import_from_file(DIR_TESTDATA . '/pomo/simple.po');
		$this->assertEquals(true, $res);

		$this->assertEquals(array('Project-Id-Version' => 'WordPress 2.6-bleeding', 'Plural-Forms' => 'nplurals=2; plural=n != 1;'), $po->headers);
=======
		$res = $po->import_from_file( DIR_TESTDATA . '/pomo/simple.po' );
		$this->assertTrue( $res );

		$this->assertSame(
			array(
				'Project-Id-Version' => 'WordPress 2.6-bleeding',
				'Plural-Forms'       => 'nplurals=2; plural=n != 1;',
			),
			$po->headers
		);
>>>>>>> 164b22cf6a (Tests: First pass at using `assertSame()` instead of `assertEquals()` in most of the unit tests.)

		$simple_entry = new Translation_Entry(array('singular' => 'moon',));
		$this->assertEquals($simple_entry, $po->entries[$simple_entry->key()]);

		$all_types_entry = new Translation_Entry(array('singular' => 'strut', 'plural' => 'struts', 'context' => 'brum',
			'translations' => array('ztrut0', 'ztrut1', 'ztrut2')));
		$this->assertEquals($all_types_entry, $po->entries[$all_types_entry->key()]);

		$multiple_line_entry = new Translation_Entry(array('singular' => 'The first thing you need to do is tell Blogger to let ClassicPress access your account. You will be sent back here after providing authorization.', 'translations' => array("baba\ndyadogugu")));
		$this->assertEquals($multiple_line_entry, $po->entries[$multiple_line_entry->key()]);

		$multiple_line_all_types_entry = new Translation_Entry(array('context' => 'context', 'singular' => 'singular',
			'plural' => 'plural', 'translations' => array('translation0', 'translation1', 'translation2')));
		$this->assertEquals($multiple_line_all_types_entry, $po->entries[$multiple_line_all_types_entry->key()]);

		$comments_entry = new Translation_Entry(array('singular' => 'a', 'translator_comments' => "baba\nbrubru",
			'references' => array('wp-admin/x.php:111', 'baba:333', 'baba'), 'extracted_comments' => "translators: buuu",
			'flags' => array('fuzzy')));
		$this->assertEquals($comments_entry, $po->entries[$comments_entry->key()]);

		$end_quote_entry = new Translation_Entry(array('singular' => 'a"'));
		$this->assertEquals($end_quote_entry, $po->entries[$end_quote_entry->key()]);
	}

	function test_import_from_entry_file_should_give_false() {
		$po = new PO();
		$this->assertFalse( $po->import_from_file( DIR_TESTDATA . '/pomo/empty.po' ) );
	}

	function test_import_from_file_with_windows_line_endings_should_work_as_with_unix_line_endings() {
		$po = new PO();
		$this->assertTrue( $po->import_from_file( DIR_TESTDATA . '/pomo/windows-line-endings.po' ) );
		$this->assertSame( 1, count( $po->entries ) );
	}

	//TODO: add tests for bad files
}
?>
