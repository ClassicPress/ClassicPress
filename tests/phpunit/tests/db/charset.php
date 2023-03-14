<?php

/**
 * Test WPDB methods
 *
 * @group wpdb
 * @group security-153
 */
class Tests_DB_Charset extends WP_UnitTestCase {

	/**
	 * Our special WPDB.
	 *
	 * @var resource
	 */
	protected static $_wpdb;

	/**
	 * Whether to expect utf8mb3 instead of utf8 in various commands output.
	 *
	 * @var bool
	 */
	private static $utf8_is_utf8mb3 = false;

	/**
	 * The database server version.
	 *
	 * @var string
	 */
	private static $db_version;

	/**
	 * Full database server information.
	 *
	 * @var string
	 */
	private static $db_server_info;

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once dirname( __DIR__ ) . '/db.php';

		self::$_wpdb = new WpdbExposedMethodsForTesting();

		self::$db_version     = self::$_wpdb->db_version();
		self::$db_server_info = self::$_wpdb->db_server_info();

		// Account for MariaDB version being prefixed with '5.5.5-' on older PHP versions.
		if ( '5.5.5' === self::$db_version && str_contains( self::$db_server_info, 'MariaDB' )
			&& PHP_VERSION_ID < 80016 // PHP 8.0.15 or older.
		) {
			// Strip the '5.5.5-' prefix and set the version to the correct value.
			self::$db_server_info = preg_replace( '/^5\.5\.5-(.*)/', '$1', self::$db_server_info );
			self::$db_version     = preg_replace( '/[^0-9.].*/', '', self::$db_server_info );
		}

		/*
		 * MariaDB 10.6.1 or later and MySQL 8.0.30 or later
		 * use utf8mb3 instead of utf8 in various commands output.
		 */
		if ( str_contains( self::$db_server_info, 'MariaDB' ) && version_compare( self::$db_version, '10.6.1', '>=' )
			|| ! str_contains( self::$db_server_info, 'MariaDB' ) && version_compare( self::$db_version, '8.0.30', '>=' )
		) {
			self::$utf8_is_utf8mb3 = true;
		}
	}

	/**
	 * @ticket 21212
	 */
	public function data_strip_invalid_text() {
		$fields = array(
			'latin1'                                => array(
				// latin1. latin1 never changes.
				'charset'  => 'latin1',
				'value'    => "\xf0\x9f\x8e\xb7",
				'expected' => "\xf0\x9f\x8e\xb7",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'latin1_char_length'                    => array(
				// latin1. latin1 never changes.
				'charset'  => 'latin1',
				'value'    => str_repeat( 'A', 11 ),
				'expected' => str_repeat( 'A', 10 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'latin1_byte_length'                    => array(
				// latin1. latin1 never changes.
				'charset'  => 'latin1',
				'value'    => str_repeat( 'A', 11 ),
				'expected' => str_repeat( 'A', 10 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'ascii'                                 => array(
				// ascii gets special treatment, make sure it's covered.
				'charset'  => 'ascii',
				'value'    => 'Hello World',
				'expected' => 'Hello World',
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'ascii_char_length'                     => array(
				// ascii gets special treatment, make sure it's covered.
				'charset'  => 'ascii',
				'value'    => str_repeat( 'A', 11 ),
				'expected' => str_repeat( 'A', 10 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'ascii_byte_length'                     => array(
				// ascii gets special treatment, make sure it's covered.
				'charset'  => 'ascii',
				'value'    => str_repeat( 'A', 11 ),
				'expected' => str_repeat( 'A', 10 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8'                                  => array(
				// utf8 only allows <= 3-byte chars.
				'charset'  => 'utf8',
				'value'    => "H€llo\xf0\x9f\x98\x88World¢",
				'expected' => 'H€lloWorld¢',
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'utf8_23char_length'                    => array(
				// utf8 only allows <= 3-byte chars.
				'charset'  => 'utf8',
				'value'    => str_repeat( '²３', 10 ),
				'expected' => str_repeat( '²３', 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8_23byte_length'                    => array(
				// utf8 only allows <= 3-byte chars.
				'charset'  => 'utf8',
				'value'    => str_repeat( '²３', 10 ),
				'expected' => '²３²３',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8_3char_length'                     => array(
				// utf8 only allows <= 3-byte chars.
				'charset'  => 'utf8',
				'value'    => str_repeat( '３', 11 ),
				'expected' => str_repeat( '３', 10 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8_3byte_length'                     => array(
				// utf8 only allows <= 3-byte chars.
				'charset'  => 'utf8',
				'value'    => str_repeat( '３', 11 ),
				'expected' => '３３３',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8mb3'                               => array(
				// utf8mb3 should behave the same an utf8.
				'charset'  => 'utf8mb3',
				'value'    => "H€llo\xf0\x9f\x98\x88World¢",
				'expected' => 'H€lloWorld¢',
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'utf8mb3_23char_length'                 => array(
				// utf8mb3 should behave the same an utf8.
				'charset'  => 'utf8mb3',
				'value'    => str_repeat( '²３', 10 ),
				'expected' => str_repeat( '²３', 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8mb3_23byte_length'                 => array(
				// utf8mb3 should behave the same an utf8.
				'charset'  => 'utf8mb3',
				'value'    => str_repeat( '²３', 10 ),
				'expected' => '²３²３',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8mb3_3char_length'                  => array(
				// utf8mb3 should behave the same an utf8.
				'charset'  => 'utf8mb3',
				'value'    => str_repeat( '３', 11 ),
				'expected' => str_repeat( '３', 10 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8mb3_3byte_length'                  => array(
				// utf8mb3 should behave the same an utf8.
				'charset'  => 'utf8mb3',
				'value'    => str_repeat( '３', 10 ),
				'expected' => '３３３',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8mb4'                               => array(
				// utf8mb4 allows 4-byte characters, too.
				'charset'  => 'utf8mb4',
				'value'    => "H€llo\xf0\x9f\x98\x88World¢",
				'expected' => "H€llo\xf0\x9f\x98\x88World¢",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'utf8mb4_234char_length'                => array(
				// utf8mb4 allows 4-byte characters, too.
				'charset'  => 'utf8mb4',
				'value'    => str_repeat( '²３𝟜', 10 ),
				'expected' => '²３𝟜²３𝟜²３𝟜²',
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8mb4_234byte_length'                => array(
				// utf8mb4 allows 4-byte characters, too.
				'charset'  => 'utf8mb4',
				'value'    => str_repeat( '²３𝟜', 10 ),
				'expected' => '²３𝟜',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'utf8mb4_4char_length'                  => array(
				// utf8mb4 allows 4-byte characters, too.
				'charset'  => 'utf8mb4',
				'value'    => str_repeat( '𝟜', 11 ),
				'expected' => str_repeat( '𝟜', 10 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'utf8mb4_4byte_length'                  => array(
				// utf8mb4 allows 4-byte characters, too.
				'charset'  => 'utf8mb4',
				'value'    => str_repeat( '𝟜', 10 ),
				'expected' => '𝟜𝟜',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'koi8r'                                 => array(
				'charset'  => 'koi8r',
				'value'    => "\xfdord\xf2ress",
				'expected' => "\xfdord\xf2ress",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'koi8r_char_length'                     => array(
				'charset'  => 'koi8r',
				'value'    => str_repeat( "\xfd\xf2", 10 ),
				'expected' => str_repeat( "\xfd\xf2", 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'koi8r_byte_length'                     => array(
				'charset'  => 'koi8r',
				'value'    => str_repeat( "\xfd\xf2", 10 ),
				'expected' => str_repeat( "\xfd\xf2", 5 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'hebrew'                                => array(
				'charset'  => 'hebrew',
				'value'    => "\xf9ord\xf7ress",
				'expected' => "\xf9ord\xf7ress",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'hebrew_char_length'                    => array(
				'charset'  => 'hebrew',
				'value'    => str_repeat( "\xf9\xf7", 10 ),
				'expected' => str_repeat( "\xf9\xf7", 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'hebrew_byte_length'                    => array(
				'charset'  => 'hebrew',
				'value'    => str_repeat( "\xf9\xf7", 10 ),
				'expected' => str_repeat( "\xf9\xf7", 5 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'cp1251'                                => array(
				'charset'  => 'cp1251',
				'value'    => "\xd8ord\xd0ress",
				'expected' => "\xd8ord\xd0ress",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'cp1251_no_length'                      => array(
				'charset'  => 'cp1251',
				'value'    => "\xd8ord\xd0ress",
				'expected' => "\xd8ord\xd0ress",
				'length'   => false,
			),
			'cp1251_no_length_ascii'                => array(
				'charset'  => 'cp1251',
				'value'    => 'WordPress',
				'expected' => 'WordPress',
				'length'   => false,
				// Don't set 'ascii' => true/false.
				// That's a different codepath than it being unset
				// even if there's only ASCII in the value.
			),
			'cp1251_char_length'                    => array(
				'charset'  => 'cp1251',
				'value'    => str_repeat( "\xd8\xd0", 10 ),
				'expected' => str_repeat( "\xd8\xd0", 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'cp1251_byte_length'                    => array(
				'charset'  => 'cp1251',
				'value'    => str_repeat( "\xd8\xd0", 10 ),
				'expected' => str_repeat( "\xd8\xd0", 5 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'tis620'                                => array(
				'charset'  => 'tis620',
				'value'    => "\xccord\xe3ress",
				'expected' => "\xccord\xe3ress",
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			),
			'tis620_char_length'                    => array(
				'charset'  => 'tis620',
				'value'    => str_repeat( "\xcc\xe3", 10 ),
				'expected' => str_repeat( "\xcc\xe3", 5 ),
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			),
			'tis620_byte_length'                    => array(
				'charset'  => 'tis620',
				'value'    => str_repeat( "\xcc\xe3", 10 ),
				'expected' => str_repeat( "\xcc\xe3", 5 ),
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			),
			'ujis_with_utf8_connection'             => array(
				'charset'            => 'ujis',
				'connection_charset' => 'utf8',
				'value'              => '自動下書き',
				'expected'           => '自動下書き',
				'length'             => array(
					'type'   => 'byte',
					'length' => 100,
				),
			),
			'ujis_with_utf8_connection_char_length' => array(
				'charset'            => 'ujis',
				'connection_charset' => 'utf8',
				'value'              => '自動下書き',
				'expected'           => '自動下書',
				'length'             => array(
					'type'   => 'char',
					'length' => 4,
				),
			),
			'ujis_with_utf8_connection_byte_length' => array(
				'charset'            => 'ujis',
				'connection_charset' => 'utf8',
				'value'              => '自動下書き',
				'expected'           => '自動',
				'length'             => array(
					'type'   => 'byte',
					'length' => 6,
				),
			),
			'false'                                 => array(
				// False is a column with no character set (i.e. a number column).
				'charset'  => false,
				'value'    => 100,
				'expected' => 100,
				'length'   => false,
			),
		);

		if ( function_exists( 'mb_convert_encoding' ) ) {
			// big5 is a non-Unicode multibyte charset.
			$utf8      = "a\xe5\x85\xb1b"; // UTF-8 Character 20849.
			$big5      = mb_convert_encoding( $utf8, 'BIG-5', 'UTF-8' );
			$conv_utf8 = mb_convert_encoding( $big5, 'UTF-8', 'BIG-5' );
			// Make sure PHP's multibyte conversions are working correctly.
			$this->assertNotEquals( $utf8, $big5 );
			$this->assertSame( $utf8, $conv_utf8 );

			$fields['big5'] = array(
				'charset'  => 'big5',
				'value'    => $big5,
				'expected' => $big5,
				'length'   => array(
					'type'   => 'char',
					'length' => 100,
				),
			);

			$fields['big5_char_length'] = array(
				'charset'  => 'big5',
				'value'    => str_repeat( $big5, 10 ),
				'expected' => str_repeat( $big5, 3 ) . 'a',
				'length'   => array(
					'type'   => 'char',
					'length' => 10,
				),
			);

			$fields['big5_byte_length'] = array(
				'charset'  => 'big5',
				'value'    => str_repeat( $big5, 10 ),
				'expected' => str_repeat( $big5, 2 ) . 'a',
				'length'   => array(
					'type'   => 'byte',
					'length' => 10,
				),
			);
		}

		// The data above is easy to edit. Now, prepare it for the data provider.
		$data_provider     = array();
		$multiple          = array();
		$multiple_expected = array();
		foreach ( $fields as $test_case => $field ) {
			$expected          = $field;
			$expected['value'] = $expected['expected'];
			unset( $expected['expected'], $field['expected'], $expected['connection_charset'] );

			// We're keeping track of these for our multiple-field test.
			$multiple[]          = $field;
			$multiple_expected[] = $expected;

			// strip_invalid_text() expects an array of fields. We're testing one field at a time.
			$data     = array( $field );
			$expected = array( $expected );

			// First argument is field data. Second is expected. Third is the message.
			$data_provider[] = array( $data, $expected, $test_case );
		}

		return $data_provider;
	}

	/**
	 * @dataProvider data_strip_invalid_text
	 * @ticket 21212
	 *
	 * @covers wpdb::strip_invalid_text
	 */
	public function test_strip_invalid_text( $data, $expected, $message ) {
		$charset = self::$_wpdb->charset;
		if ( isset( $data[0]['connection_charset'] ) ) {
			$new_charset = $data[0]['connection_charset'];
			unset( $data[0]['connection_charset'] );
		} else {
			$new_charset = $data[0]['charset'];
		}

		if ( 'utf8mb4' === $new_charset && ! self::$_wpdb->has_cap( 'utf8mb4' ) ) {
			$this->markTestSkipped( "The current MySQL server doesn't support the utf8mb4 character set." );
		}

		if ( 'big5' === $new_charset && 'byte' === $data[0]['length']['type']
			&& str_contains( self::$db_server_info, 'MariaDB' )
		) {
			$this->markTestSkipped( "MariaDB doesn't support this data set. See https://core.trac.wordpress.org/ticket/33171." );
		}

		self::$_wpdb->charset = $new_charset;
		self::$_wpdb->set_charset( self::$_wpdb->dbh, $new_charset );

		$actual = self::$_wpdb->strip_invalid_text( $data );

		self::$_wpdb->charset = $charset;
		self::$_wpdb->set_charset( self::$_wpdb->dbh, $charset );

		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * @ticket 21212
	 *
	 * @covers wpdb::process_fields
	 */
	public function test_process_fields_failure() {
		global $wpdb;

		$charset = $wpdb->get_col_charset( $wpdb->posts, 'post_content' );
		if ( 'utf8' !== $charset && 'utf8mb4' !== $charset ) {
			$this->markTestSkipped( 'This test requires a utf8 character set.' );
		}

		// \xf0\xff\xff\xff is invalid in utf8 and utf8mb4.
		$data = array( 'post_content' => "H€llo\xf0\xff\xff\xffWorld¢" );
		$this->assertFalse( self::$_wpdb->process_fields( $wpdb->posts, $data, null ) );
	}

	/**
	 * @ticket 21212
	 */
	public function data_process_field_charsets() {
		if ( $GLOBALS['wpdb']->charset ) {
			$charset = $GLOBALS['wpdb']->charset;
		} else {
			$charset = $GLOBALS['wpdb']->get_col_charset( $GLOBALS['wpdb']->posts, 'post_content' );
		}

		// 'value' and 'format' are $data, 'charset' ends up as part of $expected.

		$no_string_fields = array(
			'post_parent'   => array(
				'value'   => 10,
				'format'  => '%d',
				'charset' => false,
			),
			'comment_count' => array(
				'value'   => 0,
				'format'  => '%d',
				'charset' => false,
			),
		);

		$all_ascii_fields = array(
			'post_content' => array(
				'value'   => 'foo foo foo!',
				'format'  => '%s',
				'charset' => $charset,
			),
			'post_excerpt' => array(
				'value'   => 'bar bar bar!',
				'format'  => '%s',
				'charset' => $charset,
			),
		);

		// This is the same data used in process_field_charsets_for_nonexistent_table().
		$non_ascii_string_fields = array(
			'post_content' => array(
				'value'   => '¡foo foo foo!',
				'format'  => '%s',
				'charset' => $charset,
			),
			'post_excerpt' => array(
				'value'   => '¡bar bar bar!',
				'format'  => '%s',
				'charset' => $charset,
			),
		);

		$vars = get_defined_vars();
		unset( $vars['charset'] );
		foreach ( $vars as $var_name => $var ) {
			$data     = $var;
			$expected = $var;
			foreach ( $data as &$datum ) {
				// 'charset' and 'ascii' are part of the expected return only.
				unset( $datum['charset'], $datum['ascii'] );
			}

			$vars[ $var_name ] = array( $data, $expected, $var_name );
		}

		return array_values( $vars );
	}

	/**
	 * @dataProvider data_process_field_charsets
	 * @ticket 21212
	 *
	 * @covers wpdb::process_field_charsets
	 */
	public function test_process_field_charsets( $data, $expected, $message ) {
		$actual = self::$_wpdb->process_field_charsets( $data, $GLOBALS['wpdb']->posts );
		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * The test this test depends on first verifies that this
	 * would normally work against the posts table.
	 *
	 * @ticket 21212
	 * @depends test_process_field_charsets
	 */
	public function test_process_field_charsets_on_nonexistent_table() {
		$data = array(
			'post_content' => array(
				'value'  => '¡foo foo foo!',
				'format' => '%s',
			),
		);
		self::$_wpdb->suppress_errors( true );
		$this->assertFalse( self::$_wpdb->process_field_charsets( $data, 'nonexistent_table' ) );
		self::$_wpdb->suppress_errors( false );
	}

	/**
	 * @ticket 21212
	 *
	 * @covers wpdb::check_ascii
	 */
	public function test_check_ascii() {
		$ascii = "\0\t\n\r '" . '!"#$%&()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
		$this->assertTrue( self::$_wpdb->check_ascii( $ascii ) );
	}

	/**
	 * @ticket 21212
	 *
	 * @covers wpdb::check_ascii
	 */
	public function test_check_ascii_false() {
		$this->assertFalse( self::$_wpdb->check_ascii( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ¡©«' ) );
	}

	/**
	 * @ticket 21212
	 *
	 * @covers wpdb::strip_invalid_text_for_column
	 */
	public function test_strip_invalid_text_for_column() {
		global $wpdb;

		$charset = $wpdb->get_col_charset( $wpdb->posts, 'post_content' );
		if ( 'utf8' !== $charset && 'utf8mb4' !== $charset ) {
			$this->markTestSkipped( 'This test requires a utf8 character set.' );
		}

		// Invalid 3-byte and 4-byte sequences.
		$value    = "H€llo\xe0\x80\x80World\xf0\xff\xff\xff¢";
		$expected = 'H€lloWorld¢';
		$actual   = $wpdb->strip_invalid_text_for_column( $wpdb->posts, 'post_content', $value );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * Set of table definitions for testing wpdb::get_table_charset and wpdb::get_column_charset
	 *
	 * @var array
	 */
	protected $table_and_column_defs = array(
		array(
			'definition'      => '( a INT, b FLOAT )',
			'table_expected'  => false,
			'column_expected' => array(
				'a' => false,
				'b' => false,
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET big5, b TEXT CHARACTER SET big5 )',
			'table_expected'  => 'big5',
			'column_expected' => array(
				'a' => 'big5',
				'b' => 'big5',
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET big5, b BINARY )',
			'table_expected'  => 'binary',
			'column_expected' => array(
				'a' => 'big5',
				'b' => false,
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET latin1, b BLOB )',
			'table_expected'  => 'binary',
			'column_expected' => array(
				'a' => 'latin1',
				'b' => false,
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET latin1, b TEXT CHARACTER SET koi8r )',
			'table_expected'  => 'koi8r',
			'column_expected' => array(
				'a' => 'latin1',
				'b' => 'koi8r',
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET utf8mb3, b TEXT CHARACTER SET utf8mb3 )',
			'table_expected'  => 'utf8',
			'column_expected' => array(
				'a' => 'utf8',
				'b' => 'utf8',
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET utf8, b TEXT CHARACTER SET utf8mb4 )',
			'table_expected'  => 'utf8',
			'column_expected' => array(
				'a' => 'utf8',
				'b' => 'utf8mb4',
			),
		),
		array(
			'definition'      => '( a VARCHAR(50) CHARACTER SET big5, b TEXT CHARACTER SET koi8r )',
			'table_expected'  => 'ascii',
			'column_expected' => array(
				'a' => 'big5',
				'b' => 'koi8r',
			),
		),
	);

	/**
	 * @ticket 21212
	 */
	public function data_test_get_table_charset() {
		$table_name = 'test_get_table_charset';

		$vars = array();
		foreach ( $this->table_and_column_defs as $i => $value ) {
			$this_table_name = $table_name . '_' . $i;
			$drop            = "DROP TABLE IF EXISTS $this_table_name";
			$create          = "CREATE TABLE $this_table_name {$value['definition']}";
			$vars[]          = array( $drop, $create, $this_table_name, $value['table_expected'] );
		}

		return $vars;
	}

	/**
	 * @dataProvider data_test_get_table_charset
	 * @ticket 21212
	 *
	 * @covers wpdb::get_table_charset
	 */
	public function test_get_table_charset( $drop, $create, $table, $expected_charset ) {
		self::$_wpdb->query( $drop );

		if ( ! self::$_wpdb->has_cap( 'utf8mb4' ) && preg_match( '/utf8mb[34]/i', $create ) ) {
			$this->markTestSkipped( "This version of MySQL doesn't support utf8mb4." );
		}

		self::$_wpdb->query( $create );

		$charset = self::$_wpdb->get_table_charset( $table );
		$this->assertSame( $expected_charset, $charset );

		$charset = self::$_wpdb->get_table_charset( strtoupper( $table ) );
		$this->assertSame( $expected_charset, $charset );

		self::$_wpdb->query( $drop );
	}

	/**
	 * @ticket 21212
	 */
	public function data_test_get_column_charset() {
		$table_name = 'test_get_column_charset';

		$vars = array();
		foreach ( $this->table_and_column_defs as $i => $value ) {
			$this_table_name = $table_name . '_' . $i;
			$drop            = "DROP TABLE IF EXISTS $this_table_name";
			$create          = "CREATE TABLE $this_table_name {$value['definition']}";
			$vars[]          = array( $drop, $create, $this_table_name, $value['column_expected'] );
		}

		return $vars;
	}

	/**
	 * @dataProvider data_test_get_column_charset
	 * @ticket 21212
	 *
	 * @covers wpdb::get_col_charset
	 */
	public function test_get_column_charset( $drop, $create, $table, $expected_charset ) {
		self::$_wpdb->query( $drop );

		if ( ! self::$_wpdb->has_cap( 'utf8mb4' ) && preg_match( '/utf8mb[34]/i', $create ) ) {
			$this->markTestSkipped( "This version of MySQL doesn't support utf8mb4." );
		}

		self::$_wpdb->query( $create );

		foreach ( $expected_charset as $column => $charset ) {
			if ( self::$utf8_is_utf8mb3 && 'utf8' === $charset ) {
				$charset = 'utf8mb3';
			}

			$this->assertSame( $charset, self::$_wpdb->get_col_charset( $table, $column ) );
			$this->assertSame( $charset, self::$_wpdb->get_col_charset( strtoupper( $table ), strtoupper( $column ) ) );
		}

		self::$_wpdb->query( $drop );
	}

	/**
	 * @dataProvider data_test_get_column_charset
	 * @ticket 21212
	 *
	 * @covers wpdb::get_col_charset
	 */
	public function test_get_column_charset_non_mysql( $drop, $create, $table, $columns ) {
		self::$_wpdb->query( $drop );

		if ( ! self::$_wpdb->has_cap( 'utf8mb4' ) && preg_match( '/utf8mb[34]/i', $create ) ) {
			$this->markTestSkipped( "This version of MySQL doesn't support utf8mb4." );
		}

		self::$_wpdb->is_mysql = false;

		self::$_wpdb->query( $create );

		$columns = array_keys( $columns );
		foreach ( $columns as $column => $charset ) {
			$this->assertFalse( self::$_wpdb->get_col_charset( $table, $column ) );
		}

		self::$_wpdb->query( $drop );

		self::$_wpdb->is_mysql = true;
	}

	/**
	 * @dataProvider data_test_get_column_charset
	 * @ticket 33501
	 *
	 * @covers wpdb::get_col_charset
	 */
	public function test_get_column_charset_is_mysql_undefined( $drop, $create, $table, $columns ) {
		self::$_wpdb->query( $drop );

		if ( ! self::$_wpdb->has_cap( 'utf8mb4' ) && preg_match( '/utf8mb[34]/i', $create ) ) {
			$this->markTestSkipped( "This version of MySQL doesn't support utf8mb4." );
		}

		unset( self::$_wpdb->is_mysql );

		self::$_wpdb->query( $create );

		$columns = array_keys( $columns );
		foreach ( $columns as $column => $charset ) {
			$this->assertFalse( self::$_wpdb->get_col_charset( $table, $column ) );
		}

		self::$_wpdb->query( $drop );

		self::$_wpdb->is_mysql = true;
	}

	/**
	 * @ticket 21212
	 */
	public function data_strip_invalid_text_from_query() {
		$table_name = 'strip_invalid_text_from_query_table';
		$data       = array(
			'utf8 + binary'  => array(
				// Binary tables don't get stripped.
				'create'   => '( a VARCHAR(50) CHARACTER SET utf8, b BINARY )',
				'query'    => "('foo\xf0\x9f\x98\x88bar', 'foo')",
				'expected' => "('foo\xf0\x9f\x98\x88bar', 'foo')",
			),
			'utf8 + utf8mb4' => array(
				// utf8/utf8mb4 tables default to utf8.
				'create'   => '( a VARCHAR(50) CHARACTER SET utf8, b VARCHAR(50) CHARACTER SET utf8mb4 )',
				'query'    => "('foo\xf0\x9f\x98\x88bar', 'foo')",
				'expected' => "('foobar', 'foo')",
			),
		);

		$i = 0;

		foreach ( $data as &$value ) {
			$this_table_name = $table_name . '_' . $i++;

			$value['create']   = "CREATE TABLE $this_table_name {$value['create']}";
			$value['query']    = "INSERT INTO $this_table_name VALUES {$value['query']}";
			$value['expected'] = "INSERT INTO $this_table_name VALUES {$value['expected']}";
			$value['drop']     = "DROP TABLE IF EXISTS $this_table_name";
		}
		unset( $value );

		return $data;
	}

	/**
	 * @dataProvider data_strip_invalid_text_from_query
	 * @ticket 21212
	 *
	 * @covers wpdb::strip_invalid_text_from_query
	 */
	public function test_strip_invalid_text_from_query( $create, $query, $expected, $drop ) {
		self::$_wpdb->query( $drop );

		if ( ! self::$_wpdb->has_cap( 'utf8mb4' ) && preg_match( '/utf8mb[34]/i', $create ) ) {
			$this->markTestSkipped( "This version of MySQL doesn't support utf8mb4." );
		}

		self::$_wpdb->query( $create );

		$return = self::$_wpdb->strip_invalid_text_from_query( $query );
		$this->assertSame( $expected, $return );

		self::$_wpdb->query( $drop );
	}

	/**
	 * @ticket 32104
	 */
	public function data_dont_strip_text_from_schema_queries() {
		// An obviously invalid and fake table name.
		$table_name = "\xff\xff\xff\xff";

		$queries = array(
			"SHOW CREATE TABLE $table_name",
			"DESCRIBE $table_name",
			"DESC $table_name",
			"EXPLAIN SELECT * FROM $table_name",
			"CREATE $table_name( a VARCHAR(100))",
		);

		foreach ( $queries as &$query ) {
			$query = array( $query );
		}
		unset( $query );

		return $queries;
	}

	/**
	 * @dataProvider data_dont_strip_text_from_schema_queries
	 * @ticket 32104
	 *
	 * @covers wpdb::strip_invalid_text_from_query
	 */
	public function test_dont_strip_text_from_schema_queries( $query ) {
		$return = self::$_wpdb->strip_invalid_text_from_query( $query );
		$this->assertSame( $query, $return );
	}

	/**
	 * @ticket 21212
	 *
	 * @covers wpdb::query
	 */
	public function test_invalid_characters_in_query() {
		global $wpdb;

		$charset = $wpdb->get_col_charset( $wpdb->posts, 'post_content' );
		if ( 'utf8' !== $charset && 'utf8mb4' !== $charset ) {
			$this->markTestSkipped( 'This test requires a utf8 character set.' );
		}

		$this->assertFalse( $wpdb->query( "INSERT INTO {$wpdb->posts} (post_content) VALUES ('foo\xf0\xff\xff\xffbar')" ) );
	}

	/**
	 * @ticket 21212
	 */
	public function data_table_collation_check() {
		$table_name = 'table_collation_check';
		$data       = array(
			'utf8_bin'                   => array(
				// utf8_bin tables don't need extra sanity checking.
				'create'   => '( a VARCHAR(50) COLLATE utf8_bin )',
				'expected' => true,
			),
			'utf8_general_ci'            => array(
				// Neither do utf8_general_ci tables.
				'create'   => '( a VARCHAR(50) COLLATE utf8_general_ci )',
				'expected' => true,
			),
			'utf8_unicode_ci'            => array(
				// utf8_unicode_ci tables do.
				'create'   => '( a VARCHAR(50) COLLATE utf8_unicode_ci )',
				'expected' => false,
			),
			'utf8_bin + big5_chinese_ci' => array(
				// utf8_bin tables don't need extra sanity checking,
				// except for when they're not just utf8_bin.
				'create'   => '( a VARCHAR(50) COLLATE utf8_bin, b VARCHAR(50) COLLATE big5_chinese_ci )',
				'expected' => false,
			),
			'utf8_bin + int'             => array(
				// utf8_bin tables don't need extra sanity checking
				// when the other columns aren't strings.
				'create'   => '( a VARCHAR(50) COLLATE utf8_bin, b INT )',
				'expected' => true,
			),
		);

		$i = 0;

		foreach ( $data as &$value ) {
			$this_table_name = $table_name . '_' . $i++;

			$value['create']      = "CREATE TABLE $this_table_name {$value['create']}";
			$value['query']       = "SELECT * FROM $this_table_name WHERE a='\xf0\x9f\x98\x88'";
			$value['drop']        = "DROP TABLE IF EXISTS $this_table_name";
			$value['always_true'] = array(
				"SELECT * FROM $this_table_name WHERE a='foo'",
				"SHOW FULL TABLES LIKE $this_table_name",
				"DESCRIBE $this_table_name",
				"DESC $this_table_name",
				"EXPLAIN SELECT * FROM $this_table_name",
			);
		}
		unset( $value );

		return $data;
	}


	/**
	 * @dataProvider data_table_collation_check
	 * @ticket 21212
	 *
	 * @covers wpdb::check_safe_collation
	 */
	public function test_table_collation_check( $create, $expected, $query, $drop, $always_true ) {
		self::$_wpdb->query( $drop );

		self::$_wpdb->query( $create );

		$return = self::$_wpdb->check_safe_collation( $query );
		$this->assertSame(
			$expected,
			$return,
			sprintf(
				"wpdb::check_safe_collation() should return %s for this query.\n" .
				"Table: %s\n" .
				'Query: %s',
				$expected ? 'true' : 'false',
				$create,
				$query
			)
		);

		foreach ( $always_true as $true_query ) {
			$return = self::$_wpdb->check_safe_collation( $true_query );
			$this->assertTrue(
				$return,
				sprintf(
					"wpdb::check_safe_collation() should return true for this query.\n" .
					"Table: %s\n" .
					'Query: %s',
					$create,
					$true_query
				)
			);
		}

		self::$_wpdb->query( $drop );
	}

	/**
	 * @covers wpdb::strip_invalid_text_for_column
	 */
	public function test_strip_invalid_text_for_column_bails_if_ascii_input_too_long() {
		global $wpdb;

		// TEXT column.
		$stripped = $wpdb->strip_invalid_text_for_column( $wpdb->comments, 'comment_content', str_repeat( 'A', 65536 ) );
		$this->assertSame( 65535, strlen( $stripped ) );

		// VARCHAR column.
		$stripped = $wpdb->strip_invalid_text_for_column( $wpdb->comments, 'comment_agent', str_repeat( 'A', 256 ) );
		$this->assertSame( 255, strlen( $stripped ) );
	}

	/**
	 * @ticket 32279
	 *
	 * @covers wpdb::strip_invalid_text_from_query
	 */
	public function test_strip_invalid_text_from_query_cp1251_is_safe() {
		$tablename = 'test_cp1251_query_' . rand_str( 5 );
		if ( ! self::$_wpdb->query( "CREATE TABLE $tablename ( a VARCHAR(50) ) DEFAULT CHARSET 'cp1251'" ) ) {
			$this->markTestSkipped( "Test requires the 'cp1251' charset." );
		}

		$safe_query     = "INSERT INTO $tablename( `a` ) VALUES( 'safe data' )";
		$stripped_query = self::$_wpdb->strip_invalid_text_from_query( $safe_query );

		self::$_wpdb->query( "DROP TABLE $tablename" );

		$this->assertSame( $safe_query, $stripped_query );
	}

	/**
	 * @ticket 34708
	 *
	 * @covers wpdb::strip_invalid_text_from_query
	 */
	public function test_no_db_charset_defined() {
		$tablename = 'test_cp1251_query_' . rand_str( 5 );
		if ( ! self::$_wpdb->query( "CREATE TABLE $tablename ( a VARCHAR(50) ) DEFAULT CHARSET 'cp1251'" ) ) {
			$this->markTestSkipped( "Test requires the 'cp1251' charset." );
		}

		$charset              = self::$_wpdb->charset;
		self::$_wpdb->charset = '';

		$safe_query     = "INSERT INTO $tablename( `a` ) VALUES( 'safe data' )";
		$stripped_query = self::$_wpdb->strip_invalid_text_from_query( $safe_query );

		self::$_wpdb->query( "DROP TABLE $tablename" );

		self::$_wpdb->charset = $charset;

		$this->assertSame( $safe_query, $stripped_query );
	}

	/**
	 * @ticket 36649
	 *
	 * @covers wpdb::set_charset
	 */
	public function test_set_charset_changes_the_connection_collation() {
		self::$_wpdb->set_charset( self::$_wpdb->dbh, 'utf8', 'utf8_general_ci' );
		$results  = self::$_wpdb->get_results( "SHOW VARIABLES WHERE Variable_name='collation_connection'" );
		$expected = self::$utf8_is_utf8mb3 ? 'utf8mb3_general_ci' : 'utf8_general_ci';
		$this->assertSame( $expected, $results[0]->Value, "Collation should be set to $expected." );

		self::$_wpdb->set_charset( self::$_wpdb->dbh, 'utf8mb4', 'utf8mb4_unicode_ci' );
		$results = self::$_wpdb->get_results( "SHOW VARIABLES WHERE Variable_name='collation_connection'" );
		$this->assertSame( 'utf8mb4_unicode_ci', $results[0]->Value, 'Collation should be set to utf8mb4_unicode_ci.' );

		self::$_wpdb->set_charset( self::$_wpdb->dbh );
	}

	/**
	 * @ticket 54841
	 */
	public function test_mariadb_supports_utf8mb4_520() {
		global $wpdb;

		// utf8mb4_520 is available in MariaDB since version 10.2.
		if ( ! str_contains( self::$db_server_info, 'MariaDB' )
			|| version_compare( self::$db_version, '10.2', '<' )
		) {
			$this->markTestSkipped( 'This test requires MariaDB 10.2 or later.' );
		}

		$this->assertTrue( $wpdb->has_cap( 'utf8mb4_520' ) );
	}
}
