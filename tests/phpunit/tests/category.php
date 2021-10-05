<?php
/**
 * Validate Category API
 *
 * Notes:
 * cat_is_ancestor_of is validated under test\term\term_is_ancestor_of
 *
 * @group category.php
 */
class Tests_Category extends WP_UnitTestCase {

	function tearDown() {
		_unregister_taxonomy( 'test_tax_cat' );
		parent::tearDown();
	}

	/**
	 * Validate get_all_category_ids
	 *
	 * @expectedDeprecated get_all_category_ids
	 */
	function test_get_all_category_ids() {
		// create categories
		self::factory()->category->create_many( 2 );

		// create new taxonomy to ensure not included
		register_taxonomy( 'test_tax_cat', 'post' );
		wp_insert_term( "test1", 'test_tax_cat' );

		// Validate length is 1 + created due to uncategorized
		$cat_ids = get_all_category_ids();
		$this->assertSame( 3, count( $cat_ids ) );
	}

	/**
	 * Validate get_category_by_slug function
	 */
	function test_get_category_by_slug() {

		// create Test Categories
		$testcat = self::factory()->category->create_and_get(
			array(
				'slug' => 'testcat',
				'name' => 'Test Category 1'
			)
		);
		$testcat2 = self::factory()->category->create_and_get(
			array(
				'slug' => 'testcat2',
				'name' => 'Test Category 2'
			)
		);

		// validate category is returned by slug
		$ret_testcat = get_category_by_slug( 'testcat' );
		$this->assertSame( $testcat->term_id, $ret_testcat->term_id );
		$ret_testcat = get_category_by_slug( 'TeStCaT' );
		$this->assertSame( $testcat->term_id, $ret_testcat->term_id );

		// validate unknown category returns false
		$this->assertFalse( get_category_by_slug( 'testcat3' ) );

	}

	/**
	 * Validate _make_cat_compat function
	 */
	function test__make_cat_compat() {

		// create Test Categories and Array Representations
		$testcat_array = array(
			'slug' => 'testmcc',
			'name' => 'Test MCC',
			'description' => 'Category Test'
		);
		$testcat = self::factory()->category->create_and_get( $testcat_array );
		$testcat_array['term_id'] = $testcat->term_id;

		$testcat2_array = array(
			'slug' => 'testmcc',
			'name' => 'Test MCC',
			'description' => 'Category Test',
			'parent' => $testcat->term_id
		);
		$testcat2 = self::factory()->category->create_and_get( $testcat2_array );
		$testcat2_array['term_id'] = $testcat2->term_id;

		// unset properties to enable validation of object
		unset( $testcat->cat_ID );
		unset( $testcat->category_count );
		unset( $testcat->category_description );
		unset( $testcat->cat_name );
		unset( $testcat->category_nicename );
		unset( $testcat->category_parent );

		unset( $testcat2->cat_ID );
		unset( $testcat2->category_count );
		unset( $testcat2->category_description );
		unset( $testcat2->cat_name );
		unset( $testcat2->category_nicename );
		unset( $testcat2->category_parent );

		// make Compatible
		_make_cat_compat( $testcat );
		_make_cat_compat( $testcat2 );
		_make_cat_compat( $testcat_array );
		_make_cat_compat( $testcat2_array );

		// Validate compatibility object.
		$this->assertSame( $testcat->cat_ID, $testcat->term_id );
		$this->assertSame( $testcat->category_count, $testcat->count );
		$this->assertSame( $testcat->category_description, $testcat->description );
		$this->assertSame( $testcat->cat_name, $testcat->name );
		$this->assertSame( $testcat->category_nicename, $testcat->slug );
		$this->assertSame( $testcat->category_parent, $testcat->parent );

		// Validate compatibility object with parent.
		$this->assertSame( $testcat->cat_ID, $testcat->term_id );
		$this->assertSame( $testcat->category_count, $testcat->count );
		$this->assertSame( $testcat->category_description, $testcat->description );
		$this->assertSame( $testcat->cat_name, $testcat->name );
		$this->assertSame( $testcat->category_nicename, $testcat->slug );
		$this->assertSame( $testcat->category_parent, $testcat->parent );

		// Validate compatibility array.
		$this->assertSame( $testcat_array['cat_ID'], $testcat_array['term_id'] );
		$this->assertSame( $testcat_array['category_count'], $testcat_array['count'] );
		$this->assertSame( $testcat_array['category_description'], $testcat_array['description'] );
		$this->assertSame( $testcat_array['cat_name'], $testcat_array['name'] );
		$this->assertSame( $testcat_array['category_nicename'], $testcat_array['slug'] );
		$this->assertSame( $testcat_array['category_parent'], $testcat_array['parent'] );

		// Validate compatibility array with parent.
		$this->assertSame( $testcat_array['cat_ID'], $testcat_array['term_id'] );
		$this->assertSame( $testcat_array['category_count'], $testcat_array['count'] );
		$this->assertSame( $testcat_array['category_description'], $testcat_array['description'] );
		$this->assertSame( $testcat_array['cat_name'], $testcat_array['name'] );
		$this->assertSame( $testcat_array['category_nicename'], $testcat_array['slug'] );
		$this->assertSame( $testcat_array['category_parent'], $testcat_array['parent'] );
	}

	/**
	 * Validate get_cat_name function
	 */
	function test_get_cat_name() {

		// create Test Category
		$testcat = self::factory()->category->create_and_get(
			array(
				'slug' => 'testcat',
				'name' => 'Test Category 1'
			)
		);

		// Validate.
		$this->assertSame( $testcat->name, get_cat_name( $testcat->term_id ) );
		$this->assertSame( '', get_cat_name( -1 ) );
		$this->assertSame( '', get_cat_name( $testcat->term_id + 100 ) );

	}

	/**
	 * Validate get_cat_name function
	 */
	function test_get_cat_ID() {

		// create Test Category
		$testcat = self::factory()->category->create_and_get(
			array(
				'slug' => 'testcat',
				'name' => 'Test Category 1'
			)
		);

		// Validate.
		$this->assertSame( $testcat->term_id, get_cat_ID( $testcat->name ) );
		$this->assertSame( 0, get_cat_ID( 'NO CAT' ) );
		$this->assertSame( 0, get_cat_ID( 12 ) );

	}

	/**
	 * Validate get_category_by_path function
	 */
	function test_get_category_by_path() {

		// create Test Categories
		$root_id = self::factory()->category->create(
			array(
				'slug' => 'root',
			)
		);
		$root_cat_id = self::factory()->category->create(
			array(
				'slug' => 'cat',
				'parent' => $root_id
			)
		);
		$root_cat_cat_id = self::factory()->category->create(
			array(
				'slug' => 'cat', //note this is modified on create
				'parent' => $root_cat_id
			)
		);
		$root_path_id = self::factory()->category->create(
			array(
				'slug' => 'path',
				'parent' => $root_id
			)
		);
		$root_path_cat_id = self::factory()->category->create(
			array(
				'slug' => 'cat', //note this is modified on create
				'parent' => $root_path_id
			)
		);
		$root_level_id = self::factory()->category->create(
			array(
				'slug' => 'level-1',
				'parent' => $root_id
			)
		);
		$root_level_cat_id = self::factory()->category->create(
			array(
				'slug' => 'cat', //note this is modified on create
				'parent' => $root_level_id
			)
		);

		// Validate Full Match
		$ret_cat = get_category_by_path( '/root/level-1', true );
		$this->assertSame( $root_level_id, $ret_cat->term_id );
		$this->assertNull( get_category_by_path( 'level-1', true ) );
		$this->assertNull( get_category_by_path( 'nocat/nocat/', true) );

		// Validate Partial Match
		$ret_cat = get_category_by_path( 'level-1', false );
		$this->assertSame( $root_level_id, $ret_cat->term_id );
		$ret_cat = get_category_by_path( 'root/cat/level-1', false );
		$this->assertSame( $root_level_id, $ret_cat->term_id );
		$ret_cat = get_category_by_path( 'root$2Fcat%20%2Flevel-1', false );
		$this->assertSame( $root_level_id, $ret_cat->term_id );
		$this->assertNull( get_category_by_path( 'nocat/nocat/', false ) );
	}
}
