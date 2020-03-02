<?php

/**
 * @group taxonomy
 */
class Tests_Term_GetTheTerms extends WP_UnitTestCase {
	protected $taxonomy = 'category';
	protected static $post_ids = array();

	public static function wpSetUpBeforeClass( $factory ) {
		self::$post_ids = $factory->post->create_many( 5 );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/22560
	 */
	function test_object_term_cache() {
		$post_id = self::$post_ids[0];

		$terms_1 = array('foo', 'bar', 'baz');
		$terms_2 = array('bar', 'bing');

		// Cache should be empty after a set.
		$tt_1 = wp_set_object_terms( $post_id, $terms_1, $this->taxonomy );
		$this->assertEquals( 3, count($tt_1) );
		$this->assertFalse( wp_cache_get( $post_id, $this->taxonomy . '_relationships') );

		// wp_get_object_terms() does not prime the cache.
		wp_get_object_terms( $post_id, $this->taxonomy, array('fields' => 'names', 'orderby' => 't.term_id') );
		$this->assertFalse( wp_cache_get( $post_id, $this->taxonomy . '_relationships') );

		// get_the_terms() does prime the cache.
		$terms = get_the_terms( $post_id, $this->taxonomy );
		$cache = wp_cache_get( $post_id, $this->taxonomy . '_relationships');
		$this->assertInternalType( 'array', $cache );

		// Cache should be empty after a set.
		$tt_2 = wp_set_object_terms( $post_id, $terms_2, $this->taxonomy );
		$this->assertEquals( 2, count($tt_2) );
		$this->assertFalse( wp_cache_get( $post_id, $this->taxonomy . '_relationships') );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/24189
	 */
	function test_object_term_cache_when_term_changes() {
		$post_id = self::$post_ids[0];
		$tag_id = self::factory()->tag->create( array(
			'name' => 'Amaze Tag',
			'description' => 'My Amazing Tag'
		) );

		$tt_1 = wp_set_object_terms( $post_id, $tag_id, 'post_tag' );

		$terms = get_the_terms( $post_id, 'post_tag' );
		$this->assertEquals( $tag_id, $terms[0]->term_id );
		$this->assertEquals( 'My Amazing Tag', $terms[0]->description );

		$_updated = wp_update_term( $tag_id, 'post_tag', array(
			'description' => 'This description is even more amazing!'
		) );

		$_new_term = get_term( $tag_id, 'post_tag' );
		$this->assertEquals( $tag_id, $_new_term->term_id );
		$this->assertEquals( 'This description is even more amazing!', $_new_term->description );

		$terms = get_the_terms( $post_id, 'post_tag' );
		$this->assertEquals( $tag_id, $terms[0]->term_id );
		$this->assertEquals( 'This description is even more amazing!', $terms[0]->description );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34262
	 */
	public function test_get_the_terms_should_return_wp_term_objects_from_cache() {
		$p = self::$post_ids[0];
		register_taxonomy( 'wptests_tax', 'post' );
		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax' ) );
		wp_set_object_terms( $p, $t, 'wptests_tax' );

		// Prime the cache.
		get_the_terms( $p, 'wptests_tax' );

		$cached = get_the_terms( $p, 'wptests_tax' );

		$this->assertNotEmpty( $cached );
		$this->assertSame( $t, (int) $cached[0]->term_id );
		$this->assertInstanceOf( 'WP_Term', $cached[0] );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31086
	 */
	public function test_get_the_terms_should_return_zero_indexed_array_when_cache_is_empty() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p = self::$post_ids[0];
		wp_set_object_terms( $p, array( 'foo', 'bar' ), 'wptests_tax' );

		$found = get_the_terms( $p, 'wptests_tax' );

		$this->assertEqualSets( array( 0, 1 ), array_keys( $found ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/31086
	 */
	public function test_get_the_terms_should_return_zero_indexed_array_when_cache_is_primed() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p = self::$post_ids[0];
		wp_set_object_terms( $p, array( 'foo', 'bar' ), 'wptests_tax' );

		// Prime cache.
		update_object_term_cache( array( $p ), array( 'post' ) );

		$found = get_the_terms( $p, 'wptests_tax' );

		$this->assertEqualSets( array( 0, 1 ), array_keys( $found ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/35180
	 * @see https://core.trac.wordpress.org/ticket/28922
	 */
	public function test_get_the_terms_should_return_results_ordered_by_name_when_pulling_from_cache() {
		register_taxonomy( 'wptests_tax', 'post' );
		$p = self::$post_ids[0];

		$t1 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax', 'name' => 'fff' ) );
		$t2 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax', 'name' => 'aaa' ) );
		$t3 = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax', 'name' => 'zzz' ) );

		wp_set_object_terms( $p, array( $t1, $t2, $t3 ), 'wptests_tax' );
		update_object_term_cache( $p, 'post' );

		$found = get_the_terms( $p, 'wptests_tax' );

		$this->assertSame( array( $t2, $t1, $t3 ), wp_list_pluck( $found, 'term_id' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/34723
	 */
	function test_get_the_terms_should_return_wp_error_when_taxonomy_is_unregistered() {
		$p = self::$post_ids[0];
		$terms = get_the_terms( $p, 'this-taxonomy-does-not-exist' );
		$this->assertWPError( $terms );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/36814
	 */
	public function test_count_should_not_be_improperly_cached() {
		register_taxonomy( 'wptests_tax', 'post' );

		$t = self::factory()->term->create( array( 'taxonomy' => 'wptests_tax' ) );

		wp_set_object_terms( self::$post_ids[0], $t, 'wptests_tax' );

		$terms = get_the_terms( self::$post_ids[0], 'wptests_tax' );
		$this->assertSame( 1, $terms[0]->count );

		wp_set_object_terms( self::$post_ids[1], $t, 'wptests_tax' );

		$terms = get_the_terms( self::$post_ids[0], 'wptests_tax' );
		$this->assertSame( 2, $terms[0]->count );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/36814
	 */
	public function test_uncached_terms_should_be_primed_with_a_single_query() {
		global $wpdb;

		register_taxonomy( 'wptests_tax', 'post' );

		$terms = self::factory()->term->create_many( 3, array( 'taxonomy' => 'wptests_tax' ) );

		wp_set_object_terms( self::$post_ids[0], $terms, 'wptests_tax' );

		get_the_terms( self::$post_ids[0], 'wptests_tax' );

		// Clean cache for two of the terms.
		clean_term_cache( array( $terms[0], $terms[1] ), 'wptests_tax', false );

		$num_queries = $wpdb->num_queries;
		$found = get_the_terms( self::$post_ids[0], 'wptests_tax' );

		$this->assertEqualSets( $terms, wp_list_pluck( $found, 'term_id' ) );

		$num_queries++;
		$this->assertSame( $num_queries, $wpdb->num_queries );

	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40306
	 */
	public function test_term_cache_should_be_invalidated_on_set_object_terms() {
		register_taxonomy( 'wptests_tax', 'post' );

		// Temporarily disable term counting, which performs its own cache invalidation.
		wp_defer_term_counting( true );

		// Create Test Category.
		$term_id = self::factory()->term->create( array(
			'taxonomy' => 'wptests_tax',
		) );

		$post_id = self::factory()->post->create();

		// Prime cache.
		get_the_terms( $post_id, 'wptests_tax' );

		wp_set_object_terms( $post_id, $term_id, 'wptests_tax' );

		$terms = get_the_terms( $post_id, 'wptests_tax' );

		// Re-activate term counting so this doesn't affect other tests.
		wp_defer_term_counting( false );

		$this->assertTrue( is_array( $terms ) );
		$this->assertSame( array( $term_id ), wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/40306
	 */
	public function test_term_cache_should_be_invalidated_on_remove_object_terms() {
		register_taxonomy( 'wptests_tax', 'post' );

		// Create Test Category.
		$term_ids = self::factory()->term->create_many( 2, array(
			'taxonomy' => 'wptests_tax',
		) );

		$post_id = self::factory()->post->create();

		wp_set_object_terms( $post_id, $term_ids, 'wptests_tax' );

		// Prime cache.
		get_the_terms( $post_id, 'wptests_tax' );

		// Temporarily disable term counting, which performs its own cache invalidation.
		wp_defer_term_counting( true );

		wp_remove_object_terms( $post_id, $term_ids[0], 'wptests_tax' );

		$terms = get_the_terms( $post_id, 'wptests_tax' );

		// Re-activate term counting so this doesn't affect other tests.
		wp_defer_term_counting( false );

		$this->assertTrue( is_array( $terms ) );
		$this->assertSame( array( $term_ids[1] ), wp_list_pluck( $terms, 'term_id' ) );
	}
}
