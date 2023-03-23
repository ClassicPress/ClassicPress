<?php
/**
 * Test WP_Meta_Query, in wp-includes/meta.php
 *
 * See tests/post/query.php for tests that involve post queries.
 *
 * @group meta
 */
class Tests_Meta_Query extends WP_UnitTestCase {

	public function test_empty_meta_query_param() {
		$query = new WP_Meta_Query();
		$this->assertNull( $query->relation );
	}

	public function test_default_relation() {
		$query = new WP_Meta_Query( array( array( 'key' => 'abc' ) ) );
		$this->assertSame( 'AND', $query->relation );
	}

	public function test_set_relation() {

		$query = new WP_Meta_Query(
			array(
				array( 'key' => 'abc' ),
				'relation' => 'AND',
			)
		);

		$this->assertSame( 'AND', $query->relation );

		$query = new WP_Meta_Query(
			array(
				array( 'key' => 'abc' ),
				'relation' => 'OR',
			)
		);

		$this->assertSame( 'OR', $query->relation );
	}

	/**
	 * Non-arrays should not be added to the queries array.
	 */
	public function test_invalid_query_clauses() {
		$query = new WP_Meta_Query(
			array(
				'foo', // Empty string.
				5,     // int
				false, // bool
				array(),
			)
		);

		$this->assertSame( array(), $query->queries );
	}

	/**
	 * Test all key only meta queries use the same INNER JOIN when using relation=OR
	 *
	 * @ticket 19729
	 */
	public function test_single_inner_join_for_keys_only() {

		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array( 'key' => 'abc' ),
				array( 'key' => 'def' ),
				'relation' => 'OR',
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID' );

		$this->assertSame( 1, substr_count( $sql['join'], 'INNER JOIN' ) );

		// Also check mixing key and key => value.

		$query = new WP_Meta_Query(
			array(
				array( 'key' => 'abc' ),
				array( 'key' => 'def' ),
				array(
					'key'   => 'ghi',
					'value' => 'abc',
				),
				'relation' => 'OR',
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID' );

		$this->assertSame( 1, substr_count( $sql['join'], 'INNER JOIN' ) );
	}

	/**
	 * WP_Query-style query must be at index 0 for order_by=meta_value to work.
	 */
	public function test_parse_query_vars_simple_query_index_0() {
		$qv = array(
			'meta_query'   => array(
				array(
					'key'     => 'foo1',
					'compare' => 'baz1',
					'value'   => 'bar1',
				),
			),
			'meta_key'     => 'foo',
			'meta_compare' => 'bar',
			'meta_value'   => 'baz',
		);

		$query = new WP_Meta_Query();
		$query->parse_query_vars( $qv );

		$expected0 = array(
			'key'     => 'foo',
			'compare' => 'bar',
			'value'   => 'baz',
		);
		$this->assertSame( $expected0, $query->queries[0] );

		$expected1 = array(
			array(
				'key'     => 'foo1',
				'compare' => 'baz1',
				'value'   => 'bar1',
			),
			'relation' => 'OR',
		);
		$this->assertSame( $expected1, $query->queries[1] );
	}

	/**
	 * When no meta_value is provided, no 'value' should be set in the parsed queries.
	 */
	public function test_parse_query_vars_with_no_meta_value() {
		$qv = array(
			'meta_key'     => 'foo',
			'meta_type'    => 'bar',
			'meta_compare' => '=',
		);

		$query = new WP_Meta_Query();
		$query->parse_query_vars( $qv );

		$this->assertArrayNotHasKey( 'value', $query->queries[0] );
	}

	/**
	 * WP_Query sets meta_value to '' by default. It should be removed by parse_query_vars().
	 */
	public function test_parse_query_vars_with_default_meta_compare() {
		$qv = array(
			'meta_key'     => 'foo',
			'meta_type'    => 'bar',
			'meta_compare' => '=',
			'meta_value'   => '',
		);

		$query = new WP_Meta_Query();
		$query->parse_query_vars( $qv );

		$this->assertArrayNotHasKey( 'value', $query->queries[0] );
	}

	/**
	 * Test the conversion between "WP_Query" style meta args (meta_value=x&meta_key=y)
	 * to a meta query array.
	 */
	public function test_parse_query_vars() {

		$query = new WP_Meta_Query();

		// Just meta_value.
		$expected = array(
			array(
				'key' => 'abc',
			),
			'relation' => 'OR',
		);
		$query->parse_query_vars(
			array(
				'meta_key' => 'abc',
			)
		);
		$this->assertSame( $expected, $query->queries );

		// meta_key & meta_value.
		$expected = array(
			array(
				'key'   => 'abc',
				'value' => 'def',
			),
			'relation' => 'OR',
		);
		$query->parse_query_vars(
			array(
				'meta_key'   => 'abc',
				'meta_value' => 'def',
			)
		);
		$this->assertSame( $expected, $query->queries );

		// meta_compare.
		$expected = array(
			array(
				'key'     => 'abc',
				'compare' => '=>',
			),
			'relation' => 'OR',
		);
		$query->parse_query_vars(
			array(
				'meta_key'     => 'abc',
				'meta_compare' => '=>',
			)
		);
		$this->assertSame( $expected, $query->queries );
	}

	/**
	 * @ticket 23033
	 */
	public function test_get_cast_for_type() {
		$query = new WP_Meta_Query();
		$this->assertSame( 'BINARY', $query->get_cast_for_type( 'BINARY' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'CHAR' ) );
		$this->assertSame( 'DATE', $query->get_cast_for_type( 'DATE' ) );
		$this->assertSame( 'DATETIME', $query->get_cast_for_type( 'DATETIME' ) );
		$this->assertSame( 'SIGNED', $query->get_cast_for_type( 'SIGNED' ) );
		$this->assertSame( 'UNSIGNED', $query->get_cast_for_type( 'UNSIGNED' ) );
		$this->assertSame( 'TIME', $query->get_cast_for_type( 'TIME' ) );
		$this->assertSame( 'SIGNED', $query->get_cast_for_type( 'NUMERIC' ) );
		$this->assertSame( 'NUMERIC(10)', $query->get_cast_for_type( 'NUMERIC(10)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'NUMERIC( 10)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'NUMERIC( 10 )' ) );
		$this->assertSame( 'NUMERIC(10, 5)', $query->get_cast_for_type( 'NUMERIC(10, 5)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'NUMERIC(10,  5)' ) );
		$this->assertSame( 'NUMERIC(10,5)', $query->get_cast_for_type( 'NUMERIC(10,5)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'NUMERIC( 10, 5 )' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'NUMERIC(10, 5 )' ) );
		$this->assertSame( 'DECIMAL', $query->get_cast_for_type( 'DECIMAL' ) );
		$this->assertSame( 'DECIMAL(10)', $query->get_cast_for_type( 'DECIMAL(10)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'DECIMAL( 10 )' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'DECIMAL( 10)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'DECIMAL(10 )' ) );
		$this->assertSame( 'DECIMAL(10, 5)', $query->get_cast_for_type( 'DECIMAL(10, 5)' ) );
		$this->assertSame( 'DECIMAL(10,5)', $query->get_cast_for_type( 'DECIMAL(10,5)' ) );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'DECIMAL(10,  5)' ) );

		$this->assertSame( 'CHAR', $query->get_cast_for_type() );
		$this->assertSame( 'CHAR', $query->get_cast_for_type( 'ANYTHING ELSE' ) );
	}

	public function test_sanitize_query_single_query() {
		$expected = array(
			array(
				'key'   => 'foo',
				'value' => 'bar',
			),
			'relation' => 'OR',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_multiple_first_order_queries_relation_default() {
		$expected = array(
			array(
				'key'   => 'foo',
				'value' => 'bar',
			),
			array(
				'key'   => 'foo2',
				'value' => 'bar2',
			),
			'relation' => 'AND',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_multiple_first_order_queries_relation_or() {
		$expected = array(
			array(
				'key'   => 'foo',
				'value' => 'bar',
			),
			array(
				'key'   => 'foo2',
				'value' => 'bar2',
			),
			'relation' => 'OR',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
				'relation' => 'OR',
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_multiple_first_order_queries_relation_or_lowercase() {
		$expected = array(
			array(
				'key'   => 'foo',
				'value' => 'bar',
			),
			array(
				'key'   => 'foo2',
				'value' => 'bar2',
			),
			'relation' => 'OR',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
				'relation' => 'or',
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_multiple_first_order_queries_invalid_relation() {
		$expected = array(
			array(
				'key'   => 'foo',
				'value' => 'bar',
			),
			array(
				'key'   => 'foo2',
				'value' => 'bar2',
			),
			'relation' => 'AND',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
				'relation' => 'FOO',
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_single_query_which_is_a_nested_query() {
		$expected = array(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
				'relation' => 'AND',
			),
			'relation' => 'OR',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					array(
						'key'   => 'foo',
						'value' => 'bar',
					),
					array(
						'key'   => 'foo2',
						'value' => 'bar2',
					),
				),
			)
		);

		$this->assertSame( $expected, $found );
	}

	public function test_sanitize_query_multiple_nested_queries() {
		$expected = array(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'key'   => 'foo2',
					'value' => 'bar2',
				),
				'relation' => 'AND',
			),
			array(
				array(
					'key'   => 'foo3',
					'value' => 'bar3',
				),
				array(
					'key'   => 'foo4',
					'value' => 'bar4',
				),
				'relation' => 'AND',
			),
			'relation' => 'OR',
		);

		$q     = new WP_Meta_Query();
		$found = $q->sanitize_query(
			array(
				array(
					array(
						'key'   => 'foo',
						'value' => 'bar',
					),
					array(
						'key'   => 'foo2',
						'value' => 'bar2',
					),
				),
				array(
					array(
						'key'   => 'foo3',
						'value' => 'bar3',
					),
					array(
						'key'   => 'foo4',
						'value' => 'bar4',
					),
				),
				'relation' => 'OR',
			)
		);

		$this->assertSame( $expected, $found );
	}

	/**
	 * Invalid $type will fail to get a table from _get_meta_table()
	 */
	public function test_get_sql_invalid_type() {
		$query = new WP_Meta_Query();
		$this->assertFalse( $query->get_sql( 'foo', 'foo', 'foo' ) );
	}

	/**
	 * @ticket 22096
	 */
	public function test_empty_value_sql() {
		global $wpdb;

		$query = new WP_Meta_Query();

		$the_complex_query['meta_query'] = array(
			array(
				'key'   => 'my_first_key',
				'value' => 'my_amazing_value',
			),
			array(
				'key'     => 'my_second_key',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'my_third_key',
				'value'   => array(),
				'compare' => 'IN',
			),
		);

		$query->parse_query_vars( $the_complex_query );

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 3, substr_count( $sql['join'], 'JOIN' ) );
	}

	/**
	 * @ticket 22967
	 */
	public function test_null_value_sql() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'abc',
					'value'   => null,
					'compare' => '=',
				),
			)
		);
		$sql   = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value = ''" ) );
	}

	/**
	 * "key only queries" are queries that don't need to match a value, so
	 * they can be grouped together into a single clause without JOINs
	 */
	public function test_get_sql_key_only_queries() {
		global $wpdb;

		$query1 = new WP_Meta_Query(
			array(
				// Empty 'compare'.
				array(
					'key' => 'foo',
				),

				// Non-empty 'compare'.
				array(
					'key'     => 'bar',
					'compare' => '<',
				),

				// NOT EXISTS.
				array(
					'key'     => 'baz',
					'compare' => 'NOT EXISTS',
				),

				// Has a value.
				array(
					'key'   => 'barry',
					'value' => 'foo',
				),

				// Has no key.
				array(
					'value' => 'bar',
				),

				'relation' => 'OR',
			)
		);

		$sql = $query1->get_sql( 'post', $wpdb->posts, 'ID', $this );

		// 'foo' and 'bar' should be queried against the non-aliased table.
		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_key = 'foo'" ) );
		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_key = 'bar'" ) );

		// NOT EXISTS compare queries are not key-only so should not be non-aliased.
		$this->assertSame( 0, substr_count( $sql['where'], "$wpdb->postmeta.meta_key = 'baz'" ) );

		// 'AND' queries don't have key-only queries.
		$query2 = new WP_Meta_Query(
			array(
				// Empty 'compare'.
				array(
					'key' => 'foo',
				),

				// Non-empty 'compare'.
				array(
					'key'     => 'bar',
					'compare' => '<',
				),

				'relation' => 'AND',
			)
		);

		$sql = $query2->get_sql( 'post', $wpdb->posts, 'ID', $this );

		// Only 'foo' should be queried against the non-aliased table.
		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_key = 'foo'" ) );
		$this->assertSame( 0, substr_count( $sql['where'], "$wpdb->postmeta.meta_key = 'bar'" ) );
	}

	/**
	 * Key-only and regular queries should have the key trimmed
	 */
	public function test_get_sql_trim_key() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key' => '  foo  ',
				),
				array(
					'key'   => '  bar  ',
					'value' => 'value',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "meta_key = 'foo'" ) );
		$this->assertSame( 1, substr_count( $sql['where'], "meta_key = 'bar'" ) );
	}

	public function test_convert_null_value_to_empty_string() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => null,
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value = ''" ) );
	}

	public function test_get_sql_convert_lowercase_compare_to_uppercase() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'value'   => 'bar',
					'compare' => 'regExp',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], 'REGEXP' ) );
	}

	public function test_get_sql_empty_meta_compare_with_array_value() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => array( 'bar', 'baz' ),
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value IN" ) );
	}

	public function test_get_sql_empty_meta_compare_with_non_array_value() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value =" ) );
	}

	public function test_get_sql_invalid_meta_compare() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'value'   => 'bar',
					'compare' => 'INVALID COMPARE',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value =" ) );
	}

	/**
	 * Verifies only that meta_type_key is passed. See query/metaQuery.php for more complete tests.
	 *
	 * @ticket 43446
	 */
	public function test_meta_type_key_should_be_passed_to_meta_query() {
		$posts = self::factory()->post->create_many( 3 );

		add_post_meta( $posts[0], 'AAA_FOO_AAA', 'abc' );
		add_post_meta( $posts[1], 'aaa_bar_aaa', 'abc' );
		add_post_meta( $posts[2], 'aaa_foo_bbb', 'abc' );
		add_post_meta( $posts[2], 'aaa_foo_aaa', 'abc' );

		$q = new WP_Query(
			array(
				'meta_key'         => 'AAA_foo_.*',
				'meta_compare_key' => 'REGEXP',
				'fields'           => 'ids',
			)
		);

		$this->assertSameSets( array( $posts[0], $posts[2] ), $q->posts );

		$q = new WP_Query(
			array(
				'meta_key'         => 'AAA_FOO_.*',
				'meta_compare_key' => 'REGEXP',
				'meta_type_key'    => 'BINARY',
				'fields'           => 'ids',
			)
		);

		$this->assertSameSets( array( $posts[0] ), $q->posts );
	}

	/**
	 * This is the clause that ensures that empty arrays are not valid queries.
	 */
	public function test_get_sql_null_value_and_empty_key_should_not_have_table_join() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		// There should be no JOIN against an aliased table.
		$this->assertSame( 0, substr_count( $sql['join'], 'AS mt' ) );
	}

	public function test_get_sql_compare_array_comma_separated_values() {
		global $wpdb;

		// Single value.
		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'compare' => 'IN',
					'value'   => 'bar',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "('bar')" ) );

		// Multiple values, no spaces.
		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'compare' => 'IN',
					'value'   => 'bar,baz',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "('bar','baz')" ) );

		// Multiple values, spaces.
		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'compare' => 'IN',
					'value'   => 'bar,baz,   barry',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "('bar','baz','barry')" ) );
	}

	public function test_get_sql_compare_array() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'foo',
					'compare' => 'IN',
					'value'   => array( 'bar', 'baz' ),
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "('bar','baz')" ) );
	}

	/**
	 * Non-array values are trimmed. @todo Why?
	 */
	public function test_get_sql_trim_string_value() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => '  bar  ',
				),
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		$this->assertSame( 1, substr_count( $sql['where'], "$wpdb->postmeta.meta_value = 'bar'" ) );
	}

	public function test_not_exists() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'exclude',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'exclude',
					'compare' => '!=',
					'value'   => '1',
				),
				'relation' => 'OR',
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );
		$this->assertStringNotContainsString( "{$wpdb->postmeta}.meta_key = 'exclude'\nOR", $sql['where'] );
		$this->assertStringContainsString( "{$wpdb->postmeta}.post_id IS NULL", $sql['where'] );
	}

	public function test_empty_compare() {
		global $wpdb;

		$query = new WP_Meta_Query(
			array(
				array(
					'key'     => 'exclude',
					'compare' => '',
				),
				array(
					'key'     => 'exclude',
					'compare' => '!=',
					'value'   => '1',
				),
				'relation' => 'OR',
			)
		);

		$sql = $query->get_sql( 'post', $wpdb->posts, 'ID', $this );

		// Use regex because we don't care about the whitespace before OR.
		$this->assertMatchesRegularExpression( "/{$wpdb->postmeta}\.meta_key = \'exclude\'\s+OR/", $sql['where'] );
		$this->assertStringNotContainsString( "{$wpdb->postmeta}.post_id IS NULL", $sql['where'] );
	}

	/**
	 * @ticket 32592
	 */
	public function test_has_or_relation_should_return_false() {
		$q = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'relation' => 'AND',
					array(
						'key'   => 'foo1',
						'value' => 'bar',
					),
					array(
						'key'   => 'foo2',
						'value' => 'bar',
					),
				),
				'relation' => 'AND',
			)
		);

		$this->assertFalse( $q->has_or_relation() );
	}

	/**
	 * @ticket 32592
	 */
	public function test_has_or_relation_should_return_true_for_top_level_or() {
		$q = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'relation' => 'AND',
					array(
						'key'   => 'foo1',
						'value' => 'bar',
					),
					array(
						'key'   => 'foo2',
						'value' => 'bar',
					),
				),
				'relation' => 'OR',
			)
		);

		$this->assertTrue( $q->has_or_relation() );
	}

	/**
	 * @ticket 32592
	 */
	public function test_has_or_relation_should_return_true_for_nested_or() {
		$q = new WP_Meta_Query(
			array(
				array(
					'key'   => 'foo',
					'value' => 'bar',
				),
				array(
					'relation' => 'OR',
					array(
						'key'   => 'foo1',
						'value' => 'bar',
					),
					array(
						'key'   => 'foo2',
						'value' => 'bar',
					),
				),
				'relation' => 'AND',
			)
		);

		$this->assertTrue( $q->has_or_relation() );
	}
}
