<?php

/**
 * @group taxonomy
 */
class Tests_Term_Query extends WP_UnitTestCase {
	/**
	 * @see https://core.trac.wordpress.org/ticket/37545
	 */
	public function test_taxonomy_should_accept_single_taxonomy_as_string() {
		register_taxonomy( 'wptests_tax_1', 'post' );
		register_taxonomy( 'wptests_tax_2', 'post' );

		$term_1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );
		$term_2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_2' ) );

		$q = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_2',
			'fields' => 'ids',
			'hide_empty' => false,
		) );

		$this->assertEqualSets( array( $term_2 ), $q->terms );
	}

	public function test_taxonomy_should_accept_taxonomy_array() {
		register_taxonomy( 'wptests_tax_1', 'post' );
		register_taxonomy( 'wptests_tax_2', 'post' );

		$term_1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );
		$term_2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_2' ) );

		$q = new WP_Term_Query( array(
			'taxonomy' => array( 'wptests_tax_2' ),
			'fields' => 'ids',
			'hide_empty' => false,
		) );

		$this->assertEqualSets( array( $term_2 ), $q->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37074
	 */
	public function test_term_taxonomy_id_single() {
		global $wpdb;

		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax' ) );

		// Manually change the term_taxonomy_id to something else.
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_taxonomy_id' => 12345 ),
			array( 'term_id' => $terms[0] )
		);

		$q = new WP_Term_Query( array(
			'term_taxonomy_id' => 12345,
			'fields' => 'ids',
			'hide_empty' => false,
		) );

		$this->assertEqualSets( array( $terms[0] ), $q->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37074
	 */
	public function test_term_taxonomy_id_array() {
		global $wpdb;

		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 3, array( 'taxonomy' => 'wptests_tax' ) );

		// Manually change the term_taxonomy_id to something else.
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_taxonomy_id' => 12345 ),
			array( 'term_id' => $terms[0] )
		);

		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_taxonomy_id' => 6789 ),
			array( 'term_id' => $terms[2] )
		);

		$q = new WP_Term_Query( array(
			'term_taxonomy_id' => array( 12345, 6789 ),
			'fields' => 'ids',
			'hide_empty' => false,
		) );

		$this->assertEqualSets( array( $terms[0], $terms[2] ), $q->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37151
	 */
	public function test_order_by_meta_value_num() {
		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 3, array( 'taxonomy' => 'wptests_tax' ) );

		add_term_meta( $terms[0], 'foo', 10 );
		add_term_meta( $terms[1], 'foo', 1 );
		add_term_meta( $terms[2], 'foo', 100 );

		$q = new WP_Term_Query( array(
			'taxonomy' => array( 'wptests_tax' ),
			'fields' => 'ids',
			'hide_empty' => false,
			'meta_key' => 'foo',
			'orderby' => 'meta_value_num',
		) );

		$found = array_map( 'intval', $q->terms );
		$this->assertSame( array( $terms[1], $terms[0], $terms[2] ), $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37378
	 */
	public function test_order_by_keyword_should_not_be_duplicated_when_filtered() {
		register_taxonomy( 'wptests_tax', 'post' );

		add_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );
		$q = new WP_Term_Query( array(
			'taxonomy' => array( 'wptests_tax' ),
			'orderby' => 'name',
		) );
		remove_filter( 'terms_clauses', array( $this, 'filter_terms_clauses' ) );

		$this->assertContains( 'ORDER BY tt.term_id', $q->request );
		$this->assertNotContains( 'ORDER BY ORDER BY', $q->request );
	}

	public function filter_terms_clauses( $clauses ) {
		$clauses['orderby'] = 'ORDER BY tt.term_id';
		return $clauses;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 */
	public function test_order_by_term_order_should_fall_back_on_term_id_when_relationship_table_is_not_being_joined() {
		register_taxonomy( 'wptests_tax', 'post' );
		$terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax' ) );
		sort( $terms );

		$q = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax',
			'orderby' => 'term_order',
			'fields' => 'ids',
			'hide_empty' => false,
		) );

		$this->assertSame( $terms, $q->get_terms() );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37591
	 */
	public function test_terms_is_set() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		$q1 = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'hide_empty' => false
		) );

		$this->assertNotEmpty( $q1->terms );

		$q2 = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'hide_empty' => false
		) );

		$this->assertNotEmpty( $q2->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/23261
	 * @see https://core.trac.wordpress.org/ticket/37904
	 */
	public function test_orderby_include_with_comma_separated_list() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		$t1 = self::factory()->term->create_and_get( array( 'taxonomy' => 'wptests_tax_1' ) );
		$t2 = self::factory()->term->create_and_get( array( 'taxonomy' => 'wptests_tax_1' ) );

		$query = new WP_Term_Query( array(
			'include' => "{$t1->term_id},{$t2->term_id}",
			'orderby' => 'include',
			'hide_empty' => false,
		) );
		$terms = $query->get_terms();

		$this->assertEquals( array( $t1, $t2 ), $terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 */
	public function test_object_ids_single() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		$p = self::factory()->post->create();
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		wp_set_object_terms( $p, array( $t ), 'wptests_tax_1' );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => $p,
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array( $t ), $query->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 */
	public function test_object_ids_array() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		$p = self::factory()->post->create();
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		wp_set_object_terms( $p, array( $t ), 'wptests_tax_1' );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => array( $p ),
			'fields' => 'ids',
		) );

		$this->assertEqualSets( array( $t ), $query->terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 */
	public function test_duplicates_should_be_removed_for_fields_all() {
		register_taxonomy( 'wptests_tax_1', 'post' );
		$posts = self::factory()->post->create_many( 2 );
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		foreach ( $posts as $p ) {
			wp_set_object_terms( $p, array( $t ), 'wptests_tax_1' );
		}

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => $posts,
			'fields' => 'all',
		) );

		$this->assertSame( 1, count( $query->terms ) );
		$this->assertSame( $t, reset( $query->terms )->term_id );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 */
	public function test_duplicates_should_not_be_removed_for_fields_all_with_object_id() {
		register_taxonomy( 'wptests_tax_1', 'post' );
		$posts = self::factory()->post->create_many( 2 );
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		foreach ( $posts as $p ) {
			wp_set_object_terms( $p, array( $t ), 'wptests_tax_1' );
		}

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => $posts,
			'fields' => 'all_with_object_id',
		) );

		$this->assertSame( 2, count( $query->terms ) );
		foreach ( $query->terms as $term ) {
			$this->assertSame( $t, $term->term_id );
		}
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/44221
	 */
	public function test_all_with_object_id_should_return_term_objects() {
		register_taxonomy( 'wptests_tax_1', 'post' );
		$posts = self::factory()->post->create_many( 2 );
		$t     = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax_1' ) );

		foreach ( $posts as $p ) {
			wp_set_object_terms( $p, array( $t ), 'wptests_tax_1' );
		}

		$query = new WP_Term_Query();
		$args = array(
			'taxonomy'   => 'wptests_tax_1',
			'object_ids' => $posts,
			'fields'     => 'all_with_object_id',
		);

		$terms = $query->query( $args );
		$this->assertNotEmpty( $terms );
		foreach ( $terms as $term ) {
			$this->assertInstanceOf( 'WP_Term', $term );
			$this->assertObjectHasAttribute( 'object_id', $term );
		}

		// Run again to check the cached response.
		$terms = $query->query( $args );
		$this->assertNotEmpty( $terms );
		foreach ( $terms as $term ) {
			$this->assertInstanceOf( 'WP_Term', $term );
			$this->assertObjectHasAttribute( 'object_id', $term );
		}
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37198
	 * @group cache
	 */
	public function test_object_ids_cache_should_be_invalidated_by_term_relationship_change() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		$p = self::factory()->post->create();
		$terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax_1' ) );

		wp_set_object_terms( $p, array( $terms[0] ), 'wptests_tax_1' );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => $p,
			'fields' => 'ids',
		) );
		$found = $query->get_terms();

		$this->assertEqualSets( array( $terms[0] ), $found );

		wp_set_object_terms( $p, array( $terms[1] ), 'wptests_tax_1' );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'object_ids' => $p,
			'fields' => 'ids',
		) );
		$found = $query->get_terms();

		$this->assertEqualSets( array( $terms[1] ), $found );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38295
	 * @group cache
	 */
	public function test_count_query_should_be_cached() {
		global $wpdb;

		register_taxonomy( 'wptests_tax_1', 'post' );

		$terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax_1' ) );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'fields' => 'count',
			'hide_empty' => false,
		) );
		$count = $query->get_terms();
		$this->assertEquals( 2, $count );

		$num_queries = $wpdb->num_queries;

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'fields' => 'count',
			'hide_empty' => false,
		) );
		$count = $query->get_terms();
		$this->assertEquals( 2, $count );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38295
	 * @group cache
	 */
	public function test_count_query_cache_should_be_invalidated_with_incrementor_bump() {
		register_taxonomy( 'wptests_tax_1', 'post' );

		$terms = self::factory()->term->create_many( 2, array( 'taxonomy' => 'wptests_tax_1' ) );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'fields' => 'count',
			'hide_empty' => false,
		) );
		$count = $query->get_terms();
		$this->assertEquals( 2, $count );

		wp_delete_term( $terms[0], 'wptests_tax_1' );

		$query = new WP_Term_Query( array(
			'taxonomy' => 'wptests_tax_1',
			'fields' => 'count',
			'hide_empty' => false,
		) );
		$count = $query->get_terms();
		$this->assertEquals( 1, $count );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40496
	 */
	public function test_get_the_terms_should_respect_taxonomy_orderby() {
		register_taxonomy( 'wptests_tax', 'post', array(
			'sort' => true,
			'args' => array(
				'orderby' => 'term_order',
			),
		) );
		$term_ids = self::factory()->term->create_many( 2, array(
			'taxonomy' => 'wptests_tax',
		) );
		$post_id = self::factory()->post->create();
		wp_set_object_terms( $post_id, array( $term_ids[0], $term_ids[1] ), 'wptests_tax' );
		$terms = get_the_terms( $post_id, 'wptests_tax' );
		$this->assertEquals( array( $term_ids[0], $term_ids[1] ), wp_list_pluck( $terms, 'term_id' ) );
		// Flip the order
		wp_set_object_terms( $post_id, array( $term_ids[1], $term_ids[0] ), 'wptests_tax' );
		$terms = get_the_terms( $post_id, 'wptests_tax' );
		$this->assertEquals( array( $term_ids[1], $term_ids[0] ), wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40496
	 */
	public function test_wp_get_object_terms_should_respect_taxonomy_orderby() {
		register_taxonomy( 'wptests_tax', 'post', array(
			'sort' => true,
			'args' => array(
				'orderby' => 'term_order',
			),
		) );
		$term_ids = self::factory()->term->create_many( 2, array(
			'taxonomy' => 'wptests_tax',
		) );
		$post_id = self::factory()->post->create();
		wp_set_object_terms( $post_id, array( $term_ids[0], $term_ids[1] ), 'wptests_tax' );
		$terms = wp_get_object_terms( $post_id, array( 'category', 'wptests_tax' ) );
		$this->assertEquals( array( $term_ids[0], $term_ids[1], 1 ), wp_list_pluck( $terms, 'term_id' ) );
		// Flip the order
		wp_set_object_terms( $post_id, array( $term_ids[1], $term_ids[0] ), 'wptests_tax' );
		$terms = wp_get_object_terms( $post_id, array( 'category', 'wptests_tax' ) );
		$this->assertEquals( array( $term_ids[1], $term_ids[0], 1 ), wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/41293
	 */
	public function test_should_allow_same_args_with_the_get_terms() {
		register_post_type( 'wptests_pt' );
		register_taxonomy( 'wptests_tax', 'wptests_pt' );
		$t1 = self::factory()->term->create( array(
			'taxonomy' => 'wptests_tax',
			'name' => 'foo',
			'slug' => 'bar',
		) );
		$t2 = self::factory()->term->create( array(
			'taxonomy' => 'wptests_tax',
			'name' => 'bar',
			'slug' => 'foo',
		) );

		$p = self::factory()->post->create( array(
			'post_type' => 'wptests_pt',
		) );

		wp_set_object_terms( $p, array( $t1, $t2 ), 'wptests_tax' );

		$expected = wp_get_post_terms( $p, 'wptests_tax', array(
			'fields' => 'ids',
		) );

		$found1 = array_keys( wp_get_object_terms( $p, 'wptests_tax', array(
			'fields' => 'id=>parent',
		) ) );

		$found2 = array_keys( wp_get_object_terms( $p, 'wptests_tax', array(
			'fields' => 'id=>slug',
		) ) );

		$found3 = array_keys( wp_get_object_terms( $p, 'wptests_tax', array(
			'fields' => 'id=>name',
		) ) );

		$this->assertSame( $expected, $found1 );
		$this->assertSame( $expected, $found2 );
		$this->assertSame( $expected, $found3 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/42691
	 */
	public function test_null_term_object_should_be_discarded() {
		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 3, array(
			'taxonomy' => 'wptests_tax',
		) );

		$this->term_id = $terms[1];

		add_filter( 'get_term', array( $this, 'filter_term_to_null' ) );
		$found = get_terms( array(
			'taxonomy'   => 'wptests_tax',
			'hide_empty' => false,
		) );
		remove_filter( 'get_term', array( $this, 'filter_term_to_null' ) );

		$expected = array( $terms[0], $terms[2] );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'term_id' ) );
	}

	public function filter_term_to_null( $term ) {
		if ( $this->term_id === $term->term_id ) {
			return null;
		}

		return $term;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/42691
	 */
	public function test_error_term_object_should_be_discarded() {
		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 3, array(
			'taxonomy' => 'wptests_tax',
		) );

		$this->term_id = $terms[1];

		add_filter( 'get_term', array( $this, 'filter_term_to_wp_error' ) );
		$found = get_terms( array(
			'taxonomy'   => 'wptests_tax',
			'hide_empty' => false,
		) );
		remove_filter( 'get_term', array( $this, 'filter_term_to_wp_error' ) );

		$expected = array( $terms[0], $terms[2] );

		$this->assertEqualSets( $expected, wp_list_pluck( $found, 'term_id' ) );
	}

	public function filter_term_to_wp_error( $term ) {
		if ( $this->term_id === $term->term_id ) {
			return new WP_Error( 'foo' );
		}

		return $term;
	}
}
