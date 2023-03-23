<?php

/**
 * @group taxonomy
 */
class Tests_Term_WpSetObjectTerms extends WP_UnitTestCase {
	protected $taxonomy        = 'category';
	protected static $post_ids = array();

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$post_ids = $factory->post->create_many( 5 );
	}

	/**
	 * @ticket 26570
	 */
	public function test_set_object_terms() {
		$non_hier = rand_str( 10 );
		$hier     = rand_str( 10 );

		// Register taxonomies.
		register_taxonomy( $non_hier, array() );
		register_taxonomy( $hier, array( 'hierarchical' => true ) );

		// Create a post.
		$post_id = self::$post_ids[0];

		/*
		 * Set a single term (non-hierarchical) by ID.
		 */
		$tag = wp_insert_term( 'Foo', $non_hier );
		$this->assertFalse( has_term( $tag['term_id'], $non_hier, $post_id ) );

		wp_set_object_terms( $post_id, $tag['term_id'], $non_hier );
		$this->assertTrue( has_term( $tag['term_id'], $non_hier, $post_id ) );

		/*
		 * Set a single term (non-hierarchical) by slug.
		 */
		$tag = wp_insert_term( 'Bar', $non_hier );
		$tag = get_term( $tag['term_id'], $non_hier );

		$this->assertFalse( has_term( $tag->slug, $non_hier, $post_id ) );

		wp_set_object_terms( $post_id, $tag->slug, $non_hier );
		$this->assertTrue( has_term( $tag->slug, $non_hier, $post_id ) );

		/*
		 * Set a single term (hierarchical) by ID.
		 */
		$cat = wp_insert_term( 'Baz', $hier );
		$this->assertFalse( has_term( $cat['term_id'], $hier, $post_id ) );

		wp_set_object_terms( $post_id, $cat['term_id'], $hier );
		$this->assertTrue( has_term( $cat['term_id'], $hier, $post_id ) );

		/*
		 * Set a single term (hierarchical) by slug.
		 */
		$cat = wp_insert_term( 'Qux', $hier );
		$cat = get_term( $cat['term_id'], $hier );

		$this->assertFalse( has_term( $cat->slug, $hier, $post_id ) );

		wp_set_object_terms( $post_id, $cat->slug, $hier );
		$this->assertTrue( has_term( $cat->slug, $hier, $post_id ) );

		/*
		 * Set an array of term IDs (non-hierarchical) by ID.
		 */
		$tag1 = wp_insert_term( '_tag1', $non_hier );
		$this->assertFalse( has_term( $tag1['term_id'], $non_hier, $post_id ) );

		$tag2 = wp_insert_term( '_tag2', $non_hier );
		$this->assertFalse( has_term( $tag2['term_id'], $non_hier, $post_id ) );

		$tag3 = wp_insert_term( '_tag3', $non_hier );
		$this->assertFalse( has_term( $tag3['term_id'], $non_hier, $post_id ) );

		wp_set_object_terms( $post_id, array( $tag1['term_id'], $tag2['term_id'], $tag3['term_id'] ), $non_hier );
		$this->assertTrue( has_term( array( $tag1['term_id'], $tag2['term_id'], $tag3['term_id'] ), $non_hier, $post_id ) );

		/*
		 * Set an array of term slugs (hierarchical) by slug.
		 */
		$cat1 = wp_insert_term( '_cat1', $hier );
		$cat1 = get_term( $cat1['term_id'], $hier );
		$this->assertFalse( has_term( $cat1->slug, $hier, $post_id ) );

		$cat2 = wp_insert_term( '_cat2', $hier );
		$cat2 = get_term( $cat2['term_id'], $hier );
		$this->assertFalse( has_term( $cat2->slug, $hier, $post_id ) );

		$cat3 = wp_insert_term( '_cat3', $hier );
		$cat3 = get_term( $cat3['term_id'], $hier );
		$this->assertFalse( has_term( $cat3->slug, $hier, $post_id ) );

		wp_set_object_terms( $post_id, array( $cat1->slug, $cat2->slug, $cat3->slug ), $hier );
		$this->assertTrue( has_term( array( $cat1->slug, $cat2->slug, $cat3->slug ), $hier, $post_id ) );
	}

	public function test_set_object_terms_by_id() {
		$ids = self::$post_ids;

		$terms = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$term   = "term_{$i}";
			$result = wp_insert_term( $term, $this->taxonomy );
			$this->assertIsArray( $result );
			$term_id[ $term ] = $result['term_id'];
		}

		foreach ( $ids as $id ) {
			$tt = wp_set_object_terms( $id, array_values( $term_id ), $this->taxonomy );
			// Should return three term taxonomy IDs.
			$this->assertCount( 3, $tt );
		}

		// Each term should be associated with every post.
		foreach ( $term_id as $term => $id ) {
			$actual = get_objects_in_term( $id, $this->taxonomy );
			$this->assertSame( $ids, array_map( 'intval', $actual ) );
		}

		// Each term should have a count of 5.
		foreach ( array_keys( $term_id ) as $term ) {
			$t = get_term_by( 'name', $term, $this->taxonomy );
			$this->assertSame( 5, $t->count );
		}
	}

	public function test_set_object_terms_by_name() {
		$ids = self::$post_ids;

		$terms = array(
			'term0',
			'term1',
			'term2',
		);

		foreach ( $ids as $id ) {
			$tt = wp_set_object_terms( $id, $terms, $this->taxonomy );
			// Should return three term taxonomy IDs.
			$this->assertCount( 3, $tt );
			// Remember which term has which term_id.
			for ( $i = 0; $i < 3; $i++ ) {
				$term                    = get_term_by( 'name', $terms[ $i ], $this->taxonomy );
				$term_id[ $terms[ $i ] ] = (int) $term->term_id;
			}
		}

		// Each term should be associated with every post.
		foreach ( $term_id as $term => $id ) {
			$actual = get_objects_in_term( $id, $this->taxonomy );
			$this->assertSame( $ids, array_map( 'intval', $actual ) );
		}

		// Each term should have a count of 5.
		foreach ( $terms as $term ) {
			$t = get_term_by( 'name', $term, $this->taxonomy );
			$this->assertSame( 5, $t->count );
		}
	}

	public function test_set_object_terms_invalid() {
		// Bogus taxonomy.
		$result = wp_set_object_terms( self::$post_ids[0], array( 'foo' ), 'invalid-taxonomy' );
		$this->assertWPError( $result );
	}

	public function test_wp_set_object_terms_append_true() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p  = self::$post_ids[0];
		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);
		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$added1 = wp_set_object_terms( $p, array( $t1 ), 'wptests_tax' );
		$this->assertNotEmpty( $added1 );
		$this->assertSameSets( array( $t1 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		$added2 = wp_set_object_terms( $p, array( $t2 ), 'wptests_tax', true );
		$this->assertNotEmpty( $added2 );
		$this->assertSameSets( array( $t1, $t2 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		_unregister_taxonomy( 'wptests_tax' );
	}

	public function test_wp_set_object_terms_append_false() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p  = self::$post_ids[0];
		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);
		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$added1 = wp_set_object_terms( $p, array( $t1 ), 'wptests_tax' );
		$this->assertNotEmpty( $added1 );
		$this->assertSameSets( array( $t1 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		$added2 = wp_set_object_terms( $p, array( $t2 ), 'wptests_tax', false );
		$this->assertNotEmpty( $added2 );
		$this->assertSameSets( array( $t2 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		_unregister_taxonomy( 'wptests_tax' );
	}

	public function test_wp_set_object_terms_append_default_to_false() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p  = self::$post_ids[0];
		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);
		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$added1 = wp_set_object_terms( $p, array( $t1 ), 'wptests_tax' );
		$this->assertNotEmpty( $added1 );
		$this->assertSameSets( array( $t1 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		$added2 = wp_set_object_terms( $p, array( $t2 ), 'wptests_tax' );
		$this->assertNotEmpty( $added2 );
		$this->assertSameSets( array( $t2 ), wp_get_object_terms( $p, 'wptests_tax', array( 'fields' => 'ids' ) ) );

		_unregister_taxonomy( 'wptests_tax' );
	}

	/**
	 * Set some terms on an object; then change them while leaving one intact.
	 */
	public function test_change_object_terms_by_id() {
		$post_id = self::$post_ids[0];

		// First set: 3 terms.
		$terms_1 = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$term   = "term_{$i}";
			$result = wp_insert_term( $term, $this->taxonomy );
			$this->assertIsArray( $result );
			$terms_1[ $i ] = $result['term_id'];
		}

		// Second set: one of the original terms, plus one new term.
		$terms_2    = array();
		$terms_2[0] = $terms_1[1];

		$term       = 'term';
		$result     = wp_insert_term( $term, $this->taxonomy );
		$terms_2[1] = $result['term_id'];

		// Set the initial terms.
		$tt_1 = wp_set_object_terms( $post_id, $terms_1, $this->taxonomy );
		$this->assertCount( 3, $tt_1 );

		// Make sure they're correct.
		$terms = wp_get_object_terms(
			$post_id,
			$this->taxonomy,
			array(
				'fields'  => 'ids',
				'orderby' => 'term_id',
			)
		);
		$this->assertSame( $terms_1, $terms );

		// Change the terms.
		$tt_2 = wp_set_object_terms( $post_id, $terms_2, $this->taxonomy );
		$this->assertCount( 2, $tt_2 );

		// Make sure they're correct.
		$terms = wp_get_object_terms(
			$post_id,
			$this->taxonomy,
			array(
				'fields'  => 'ids',
				'orderby' => 'term_id',
			)
		);
		$this->assertSame( $terms_2, $terms );

		// Make sure the term taxonomy ID for 'bar' matches.
		$this->assertSame( $tt_1[1], $tt_2[0] );

	}

	/**
	 * Set some terms on an object; then change them while leaving one intact.
	 */
	public function test_change_object_terms_by_name() {
		$post_id = self::$post_ids[0];

		$terms_1 = array( 'foo', 'bar', 'baz' );
		$terms_2 = array( 'bar', 'bing' );

		// Set the initial terms.
		$tt_1 = wp_set_object_terms( $post_id, $terms_1, $this->taxonomy );
		$this->assertCount( 3, $tt_1 );

		// Make sure they're correct.
		$terms = wp_get_object_terms(
			$post_id,
			$this->taxonomy,
			array(
				'fields'  => 'names',
				'orderby' => 'term_id',
			)
		);
		$this->assertSame( $terms_1, $terms );

		// Change the terms.
		$tt_2 = wp_set_object_terms( $post_id, $terms_2, $this->taxonomy );
		$this->assertCount( 2, $tt_2 );

		// Make sure they're correct.
		$terms = wp_get_object_terms(
			$post_id,
			$this->taxonomy,
			array(
				'fields'  => 'names',
				'orderby' => 'term_id',
			)
		);
		$this->assertSame( $terms_2, $terms );

		// Make sure the term taxonomy ID for 'bar' matches.
		$this->assertEquals( $tt_1[1], $tt_2[0] );

	}

	public function test_should_create_term_that_does_not_exist() {
		register_taxonomy( 'wptests_tax', 'post' );

		$this->assertFalse( get_term_by( 'slug', 'foo', 'wptests_tax' ) );

		$tt_ids = wp_set_object_terms( self::$post_ids[0], 'foo', 'wptests_tax' );

		$this->assertNotEmpty( $tt_ids );
		$term = get_term_by( 'term_taxonomy_id', $tt_ids[0] );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( 'foo', $term->slug );
	}

	public function test_should_find_existing_term_by_slug_match() {
		register_taxonomy( 'wptests_tax', 'post' );

		$t = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
				'slug'     => 'foo',
				'name'     => 'Bar',
			)
		);

		$tt_ids = wp_set_object_terms( self::$post_ids[0], 'foo', 'wptests_tax' );

		$this->assertNotEmpty( $tt_ids );
		$term = get_term_by( 'term_taxonomy_id', $tt_ids[0] );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $t, $term->term_id );
	}

	public function test_should_find_existing_term_by_name_match() {
		register_taxonomy( 'wptests_tax', 'post' );

		$t = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
				'slug'     => 'foo',
				'name'     => 'Bar',
			)
		);

		$tt_ids = wp_set_object_terms( self::$post_ids[0], 'Bar', 'wptests_tax' );

		$this->assertNotEmpty( $tt_ids );
		$term = get_term_by( 'term_taxonomy_id', $tt_ids[0] );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $t, $term->term_id );
	}

	public function test_should_give_precedence_to_slug_match_over_name_match() {
		register_taxonomy( 'wptests_tax', 'post' );

		$t1 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
				'slug'     => 'foo',
				'name'     => 'Bar',
			)
		);

		$t2 = self::factory()->term->create(
			array(
				'taxonomy' => 'wptests_tax',
				'slug'     => 'bar',
				'name'     => 'Foo',
			)
		);

		$tt_ids = wp_set_object_terms( self::$post_ids[0], 'Bar', 'wptests_tax' );

		$this->assertNotEmpty( $tt_ids );
		$term = get_term_by( 'term_taxonomy_id', $tt_ids[0] );
		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $t2, $term->term_id );
	}

	public function test_non_existent_integers_should_be_ignored() {
		register_taxonomy( 'wptests_tax', 'post' );

		$tt_ids = wp_set_object_terms( self::$post_ids[0], 12345, 'wptests_tax' );

		$this->assertSame( array(), $tt_ids );
	}
}
