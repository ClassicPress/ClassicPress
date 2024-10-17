<?php
/**
 * Test WPDB methods
 *
 * @package WordPress
 * @subpackage UnitTests
 */

/**
 * Test WPDB methods
 *
 * @group wpdb
 */
class Tests_DB extends WP_UnitTestCase {

    /**
     * Query log
     *
     * @var array
     */
    protected $_queries = array();

    /**
     * Our special WPDB
     *
     * @var WpdbExposedMethodsForTesting
     */
    protected static $_wpdb;

    /**
     * Set up before class.
     */
    public static function set_up_before_class() {
        parent::set_up_before_class();
        self::$_wpdb = new WpdbExposedMethodsForTesting();
    }

    /**
     * Set up the test fixture
     */
    public function set_up() {
        parent::set_up();
        $this->_queries = array();
        add_filter( 'query', array( $this, 'query_filter' ) );
        self::$_wpdb->last_error = null;
        $GLOBALS['wpdb']->last_error = null;
    }

    /**
     * Log each query
     *
     * @param string $sql SQL query.
     * @return string
     */
    public function query_filter( $sql ) {
        $this->_queries[] = $sql;
        return $sql;
    }

    /**
     * Test that WPDB will reconnect when the DB link dies
     *
     * @ticket 5932
     */
    public function test_db_reconnect() {
        global $wpdb;

        $var = $wpdb->get_var( "SELECT ID FROM $wpdb->users LIMIT 1" );
        $this->assertGreaterThan( 0, $var );

        $wpdb->close();

        $var = $wpdb->get_var( "SELECT ID FROM $wpdb->users LIMIT 1" );

        // Ensure all database handles have been properly reconnected after this test.
        $wpdb->db_connect();
        self::$_wpdb->db_connect();

        $this->assertGreaterThan( 0, $var );
    }

    /**
     * Test that floats formatted as "0,700" get sanitized properly by wpdb
     *
     * @ticket 19861
     */
    public function test_locale_floats() {
        global $wpdb;

        // Save the current locale settings.
        $current_locales = explode( ';', setlocale( LC_ALL, 0 ) );

        // Switch to a locale using comma as a decimal point separator.
        $flag = setlocale( LC_ALL, 'ru_RU.utf8', 'rus', 'fr_FR.utf8', 'fr_FR', 'de_DE.utf8', 'de_DE', 'es_ES.utf8', 'es_ES' );
        
        if ( false === $flag ) {
            $this->markTestSkipped( 'No European locales available for testing.' );
        }

        // Try an update query.
        $wpdb->suppress_errors( true );
        
        $wpdb->update(
            'test_table',
            array( 'float_column' => 0.7 ),
            array( 'meta_id' => 5 ),
            array( '%f' ),
            array( '%d' )
        );
        
        $wpdb->suppress_errors( false );

        // Ensure the float isn't 0,700.
        $this->assertStringContainsString( '0.700', array_pop( $this->_queries ) );

        // Try a prepare.
        $sql = $wpdb->prepare( 'UPDATE test_table SET float_column = %f AND meta_id = %d', 0.7, 5 );
        
        $this->assertStringContainsString( '0.700', $sql );

        // Restore locale settings.
        foreach ( $current_locales as $locale_setting ) {
            if ( false !== strpos( $locale_setting, '=' ) ) {
                list( $category, $locale ) = explode( '=', $locale_setting );
                if ( defined( $category ) ) {
                    setlocale( constant( $category ), $locale );
                }
            } else {
                setlocale( LC_ALL, $locale_setting );
            }
        }
    }

    /**
     * Test esc_like method
     *
     * @ticket 10041
     */
    public function test_esc_like() {
        global $wpdb;

        $inputs   = array(
            'howdy%',              // Single percent.
            'howdy_',              // Single underscore.
            'howdy\\',             // Single slash.
            'howdy\\howdy%howdy_', // The works.
            'howdy\'"[[]*#[^howdy]!+)(*&$#@!~|}{=--`/.,<>?', // Plain text.
        );

        $expected = array(
            'howdy\\%',
            'howdy\\_',
            'howdy\\\\',
            'howdy\\\\howdy\\%howdy\\_',
            'howdy\'"[[]*#[^howdy]!+)(*&$#@!~|}{=--`/.,<>?',
        );

        foreach ( $inputs as $key => $input ) {
            $this->assertSame( $expected[ $key ], $wpdb->esc_like( $input ) );
        }
    }

    /**
     * Test LIKE Queries
     *
     * Make sure $wpdb is fully compatible with esc_like() by testing the identity of various strings.
     * When escaped properly, a string literal is always LIKE itself (1)
     * and never LIKE any other string literal (0) no matter how crazy the SQL looks.
     *
     * @ticket 10041
     * @dataProvider data_like_query
     * @param string $data   The haystack, raw.
     * @param string $like   The like phrase, raw.
     * @param string $result The expected comparison result; '1' = true, '0' = false.
     */
    public function test_like_query( $data, $like, $result ) {
        global $wpdb;

        return $this->assertSame(
            $result, 
            $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT %s LIKE %s', 
                    $data, 
                    $wpdb->esc_like( $like )
                )
            )
        );
    }

    /**
     * Data provider for test_like_query.
     *
     * @return array Test data.
     */
    public function data_like_query() {
        return array(
            array( 'aaa', 'aaa', '1' ),
            array( 'a\\aa', 'a\\aa', '1' ),
            array( 'a%aa', 'a%aa', '1' ),
            array( 'aaaa', 'a%aa', '0' ),
            array( 'a\\%aa', 'a\\%aa', '1' ),
            array( 'a%aa', 'a\\%aa', '0' ),
            array( 'a\\%aa', 'a%aa', '0' ),
            array( 'a_aa', 'a_aa', '1' ),
            array( 'aaaa', 'a_aa', '0' ),
            array(
                'howdy\'"[[]*#[^howdy]!+)(*&$#@!~|}{=--`/.,<>?',
                'howdy\'"[[]*#[^howdy]!+)(*&$#@!~|}{=--`/.,<>?',
                '1'
            ),
        );
    }

    /**
     * Test wpdb supposedly protected properties
     *
     * @ticket 18510
     */
    public function test_wpdb_supposedly_protected_properties() {
        global $wpdb;

        $this->assertNotEmpty( $wpdb->dbh );
        $dbh = $wpdb->dbh;
        $this->assertNotEmpty( $dbh );
        $this->assertTrue( isset( $wpdb->dbh ) ); // Test __isset().
        unset( $wpdb->dbh );
        $this->assertTrue( empty( $wpdb->dbh ) );
        $wpdb->dbh = $dbh;
        $this->assertNotEmpty( $wpdb->dbh );
    }

    /**
     * Test wpdb actually protected properties
     *
     * @ticket 21212
     */
    public function test_wpdb_actually_protected_properties() {
        global $wpdb;

        $new_meta = 'HAHA I HOPE THIS DOESN\'T WORK';

        $col_meta = $wpdb->col_meta;
        $wpdb->col_meta = $new_meta;

        $this->assertNotEquals( $col_meta, $new_meta );
        $this->assertSame( $col_meta, $wpdb->col_meta );
    }

    /**
     * Test wpdb nonexistent properties
     *
     * @ticket 18510
     */
    public function test_wpdb_nonexistent_properties() {
        global $wpdb;

        $this->assertTrue( empty( $wpdb->nonexistent_property ) );
        $wpdb->nonexistent_property = true;
        $this->assertTrue( $wpdb->nonexistent_property );
        $this->assertTrue( isset( $wpdb->nonexistent_property ) );
        unset( $wpdb->nonexistent_property );
        $this->assertTrue( empty( $wpdb->nonexistent_property ) );
    }

    /**
     * Test that an escaped %%f is not altered
     *
     * @ticket 19861
     */
    public function test_double_escaped_placeholders() {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "UPDATE test_table SET string_column='%%f is a float, %%d is an int %d, %%s is a string', field=%s",
            3,
            '4'
        );
        
        $this->assertStringContainsString( $wpdb->placeholder_escape(), $sql );

        $sql = $wpdb->remove_placeholder_escape( $sql );
        
        $this->assertSame(
            "UPDATE test_table SET string_column='%f is a float, %d is an int 3, %s is a string', field='4'",
            $sql
        );
    }

    /**
     * Test that SQL modes are set correctly
     *
     * @ticket 26847
     */
    public function test_set_sql_mode() {
        global $wpdb;

        $current_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );

        $new_modes = array( 'IGNORE_SPACE', 'NO_AUTO_VALUE_ON_ZERO' );

        $wpdb->set_sql_mode( $new_modes );

        $check_new_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );
        
        $this->assertSameSets( $new_modes, explode( ',', $check_new_modes ) );

        $wpdb->set_sql_mode( explode( ',', $current_modes ) );
    }

    /**
     * Test that incompatible SQL modes are blocked
     *
     * @ticket 26847
     */
    public function test_set_incompatible_sql_mode() {
        global $wpdb;

        $current_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );

        $new_modes = array( 'IGNORE_SPACE', 'NO_ZERO_DATE', 'NO_AUTO_VALUE_ON_ZERO' );
        
        $wpdb->set_sql_mode( $new_modes );
        $check_new_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );
        
        $this->assertNotContains( 'NO_ZERO_DATE', explode( ',', $check_new_modes ) );

        $wpdb->set_sql_mode( explode( ',', $current_modes ) );
    }

    /**
     * Test that incompatible SQL modes can be changed
     *
     * @ticket 26847
     */
    public function test_set_allowed_incompatible_sql_mode() {
        global $wpdb;

        $current_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );

        $new_modes = array( 'IGNORE_SPACE', 'ONLY_FULL_GROUP_BY', 'NO_AUTO_VALUE_ON_ZERO' );

        add_filter( 'incompatible_sql_modes', array( $this, 'filter_allowed_incompatible_sql_mode' ), 1, 1 );

        $wpdb->set_sql_mode( $new_modes );

        remove_filter( 'incompatible_sql_modes', array( $this, 'filter_allowed_incompatible_sql_mode' ), 1 );

        $check_new_modes = $wpdb->get_var( 'SELECT @@SESSION.sql_mode;' );
        
        $this->assertContains( 'ONLY_FULL_GROUP_BY', explode( ',', $check_new_modes ) );

        $wpdb->set_sql_mode( explode( ',', $current_modes ) );
    }

    /**
     * Filter for allowing incompatible SQL mode.
     *
     * @param array $modes SQL modes.
     * @return array
     */
    public function filter_allowed_incompatible_sql_mode( $modes ) {
        $pos = array_search( 'ONLY_FULL_GROUP_BY', $modes, true );
        
        $this->assertGreaterThanOrEqual( 0, $pos );

        if ( false === $pos ) {
            return $modes;
        }

        unset( $modes[ $pos ] );
        return $modes;
    }

    /**
     * Test prepare without arguments
     *
     * @ticket 25604
     * @expectedIncorrectUsage wpdb::prepare
     */
    public function test_prepare_without_arguments() {
        global $wpdb;
        
        $id = 0;
        
        $prepared = $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE id = $id", $id );
        
        $this->assertSame( "SELECT * FROM $wpdb->users WHERE id = 0", $prepared );
    }

    /**
     * Test prepare with sprintf
     */
    public function test_prepare_sprintf() {
        global $wpdb;

        $prepared = $wpdb->prepare( "SELECT * FROM $wpdb->users WHERE id = %d AND user_login = %s", 1, 'admin' );
        $this->assertSame( "SELECT * FROM $wpdb->users WHERE id = 1 AND user_login = 'admin'", $prepared );
    }

    /**
     * Test prepare with invalid sprintf args
     *
     * @expectedIncorrectUsage wpdb::prepare
     */
    public function test_prepare_sprintf_invalid_args() {
        global $wpdb;

        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        $prepared = @$wpdb->prepare( "SELECT * FROM $wpdb->users WHERE id = %d AND user_login = %s", 1, array( 'admin' )