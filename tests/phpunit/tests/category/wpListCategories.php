<?php

/**
 * @group taxonomy
 *
 * @covers ::wp_list_categories
 */
class Tests_Category_WpListCategories extends WP_UnitTestCase {
	public function test_class() {
		$c = self::factory()->category->create();

		$found = wp_list_categories(
			array(
				'hide_empty' => false,
				'echo'       => false,
			)
		);

		$this->assertStringContainsString( 'class="cat-item cat-item-' . $c . '"', $found );
	}

	public function test_class_containing_current_cat() {
		$c1 = self::factory()->category->create();
		$c2 = self::factory()->category->create();

		$found = wp_list_categories(
			array(
				'hide_empty'       => false,
				'echo'             => false,
				'current_category' => $c2,
			)
		);

		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*cat-item-' . $c1 . '[^"]*current-cat[^"]*"/', $found );
		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $c2 . '[^"]*current-cat[^"]*"/', $found );
	}

	public function test_class_containing_current_cat_parent() {
		$c1 = self::factory()->category->create();
		$c2 = self::factory()->category->create(
			array(
				'parent' => $c1,
			)
		);

		$found = wp_list_categories(
			array(
				'hide_empty'       => false,
				'echo'             => false,
				'current_category' => $c2,
			)
		);

		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $c1 . '[^"]*current-cat-parent[^"]*"/', $found );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*cat-item-' . $c2 . '[^"]*current-cat-parent[^"]*"/', $found );
	}

	/**
	 * @ticket 33565
	 */
	public function test_current_category_should_accept_an_array_of_ids() {
		$cats = self::factory()->category->create_many( 3 );

		$found = wp_list_categories(
			array(
				'echo'             => false,
				'hide_empty'       => false,
				'current_category' => array( $cats[0], $cats[2] ),
			)
		);

		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $cats[0] . '[^"]*current-cat[^"]*"/', $found );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*cat-item-' . $cats[1] . '[^"]*current[^"]*"/', $found );
		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $cats[2] . '[^"]*current-cat[^"]*"/', $found );
	}

	/**
	 * @ticket 16792
	 */
	public function test_should_not_create_element_when_cat_name_is_filtered_to_empty_string() {
		$c1 = self::factory()->category->create(
			array(
				'name' => 'Test Cat 1',
			)
		);
		$c2 = self::factory()->category->create(
			array(
				'name' => 'Test Cat 2',
			)
		);

		add_filter( 'list_cats', array( $this, 'list_cats_callback' ) );
		$found = wp_list_categories(
			array(
				'hide_empty' => false,
				'echo'       => false,
			)
		);
		remove_filter( 'list_cats', array( $this, 'list_cats_callback' ) );

		$this->assertStringContainsString( "cat-item-$c2", $found );
		$this->assertStringContainsString( 'Test Cat 2', $found );

		$this->assertStringNotContainsString( "cat-item-$c1", $found );
		$this->assertStringNotContainsString( 'Test Cat 1', $found );
	}

	public function list_cats_callback( $cat ) {
		if ( 'Test Cat 1' === $cat ) {
			return '';
		}

		return $cat;
	}

	/**
	 * @ticket 44872
	 */
	public function test_should_create_element_when_cat_name_is_zero() {
		$c = self::factory()->category->create(
			array(
				'name' => '0',
			)
		);

		$found = wp_list_categories(
			array(
				'hide_empty' => false,
				'echo'       => false,
			)
		);

		$this->assertStringContainsString( "cat-item-$c", $found );
		$this->assertStringContainsString( '0', $found );
	}

	public function test_show_option_all_link_should_go_to_home_page_when_show_on_front_is_false() {
		$cats = self::factory()->category->create_many( 2 );

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'category',
			)
		);

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . home_url( '/' ) . "'>All</a></li>", $found );
	}

	public function test_show_option_all_link_should_respect_page_for_posts() {
		$cats = self::factory()->category->create_many( 2 );
		$p    = self::factory()->post->create( array( 'post_type' => 'page' ) );

		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $p );

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'category',
			)
		);

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . get_permalink( $p ) . "'>All</a></li>", $found );
	}

	/**
	 * @ticket 21881
	 */
	public function test_show_option_all_link_should_link_to_post_type_archive_when_taxonomy_does_not_apply_to_posts() {
		register_post_type( 'wptests_pt', array( 'has_archive' => true ) );
		register_post_type( 'wptests_pt2', array( 'has_archive' => true ) );
		register_taxonomy( 'wptests_tax', array( 'foo', 'wptests_pt', 'wptests_pt2' ) );

		$terms = self::factory()->term->create_many(
			2,
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'wptests_tax',
			)
		);

		$pt_archive = get_post_type_archive_link( 'wptests_pt' );

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . $pt_archive . "'>All</a></li>", $found );
	}

	/**
	 * @ticket 21881
	 */
	public function test_show_option_all_link_should_not_link_to_post_type_archive_if_has_archive_is_false() {
		register_post_type( 'wptests_pt', array( 'has_archive' => false ) );
		register_post_type( 'wptests_pt2', array( 'has_archive' => true ) );
		register_taxonomy( 'wptests_tax', array( 'foo', 'wptests_pt', 'wptests_pt2' ) );

		$terms = self::factory()->term->create_many(
			2,
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'wptests_tax',
			)
		);

		$pt_archive = get_post_type_archive_link( 'wptests_pt2' );

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . $pt_archive . "'>All</a></li>", $found );
	}

	public function test_show_option_all_link_should_link_to_post_archive_if_available() {
		register_post_type( 'wptests_pt', array( 'has_archive' => true ) );
		register_post_type( 'wptests_pt2', array( 'has_archive' => true ) );
		register_taxonomy( 'wptests_tax', array( 'foo', 'wptests_pt', 'post', 'wptests_pt2' ) );

		$terms = self::factory()->term->create_many(
			2,
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'wptests_tax',
			)
		);

		$url = home_url( '/' );

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . $url . "'>All</a></li>", $found );
	}

	public function test_show_option_all_link_should_link_to_post_archive_if_no_associated_post_types_have_archives() {
		register_post_type( 'wptests_pt', array( 'has_archive' => false ) );
		register_post_type( 'wptests_pt2', array( 'has_archive' => false ) );
		register_taxonomy( 'wptests_tax', array( 'foo', 'wptests_pt', 'wptests_pt2' ) );

		$terms = self::factory()->term->create_many(
			2,
			array(
				'taxonomy' => 'wptests_tax',
			)
		);

		$found = wp_list_categories(
			array(
				'echo'            => false,
				'show_option_all' => 'All',
				'hide_empty'      => false,
				'taxonomy'        => 'wptests_tax',
			)
		);

		$url = home_url( '/' );

		$this->assertStringContainsString( "<li class='cat-item-all'><a href='" . $url . "'>All</a></li>", $found );
	}

	/**
	 * @ticket 33460
	 */
	public function test_title_li_should_be_shown_by_default_for_empty_lists() {
		$found = wp_list_categories(
			array(
				'echo' => false,
			)
		);

		$this->assertStringContainsString( '<li class="categories">Categories', $found );
	}

	/**
	 * @ticket 33460
	 */
	public function test_hide_title_if_empty_should_be_respected_for_empty_lists_when_true() {
		$found = wp_list_categories(
			array(
				'echo'                => false,
				'hide_title_if_empty' => true,
			)
		);

		$this->assertStringNotContainsString( '<li class="categories">Categories', $found );
	}

	/**
	 * @ticket 33460
	 */
	public function test_hide_title_if_empty_should_be_respected_for_empty_lists_when_false() {
		$found = wp_list_categories(
			array(
				'echo'                => false,
				'hide_title_if_empty' => false,
			)
		);

		$this->assertStringContainsString( '<li class="categories">Categories', $found );
	}

	/**
	 * @ticket 33460
	 */
	public function test_hide_title_if_empty_should_be_ignored_when_category_list_is_not_empty() {
		$cat = self::factory()->category->create();

		$found = wp_list_categories(
			array(
				'echo'                => false,
				'hide_empty'          => false,
				'hide_title_if_empty' => true,
			)
		);

		$this->assertStringContainsString( '<li class="categories">Categories', $found );
	}

	/**
	 * @ticket 38839
	 */
	public function test_hide_title_if_empty_should_not_output_stray_closing_tags() {
		$cat = self::factory()->category->create();

		$found = wp_list_categories(
			array(
				'echo'                => false,
				'show_option_none'    => '',
				'child_of'            => 1,
				'hide_title_if_empty' => true,
			)
		);

		$this->assertStringNotContainsString( '</ul></li>', $found );
	}

	/**
	 * @ticket 12981
	 */
	public function test_exclude_tree_should_be_respected() {
		$c      = self::factory()->category->create();
		$parent = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent',
			)
		);
		$child  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child',
				'parent' => $parent,
			)
		);

		$args = array(
			'echo'         => 0,
			'hide_empty'   => 0,
			'exclude_tree' => $parent,
		);

		$actual = wp_list_categories( $args );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child . '">', $actual );
	}

	/**
	 * @ticket 12981
	 */
	public function test_exclude_tree_should_be_merged_with_exclude() {
		$c       = self::factory()->category->create();
		$parent  = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent',
			)
		);
		$child   = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child',
				'parent' => $parent,
			)
		);
		$parent2 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent2',
			)
		);
		$child2  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child2',
				'parent' => $parent2,
			)
		);

		$args = array(
			'echo'         => 0,
			'hide_empty'   => 0,
			'exclude_tree' => $parent,
		);

		$actual = wp_list_categories(
			array(
				'echo'         => 0,
				'hide_empty'   => 0,
				'exclude'      => $parent,
				'exclude_tree' => $parent2,
			)
		);

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent2 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child2 . '">', $actual );
	}

	/**
	 * @ticket 35156
	 */
	public function test_comma_separated_exclude_tree_should_be_merged_with_exclude() {
		$c       = self::factory()->category->create();
		$parent  = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent',
			)
		);
		$child   = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child',
				'parent' => $parent,
			)
		);
		$parent2 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent2',
			)
		);
		$child2  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child2',
				'parent' => $parent2,
			)
		);
		$parent3 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent3',
			)
		);
		$child3  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child3',
				'parent' => $parent3,
			)
		);
		$parent4 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent4',
			)
		);
		$child4  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child4',
				'parent' => $parent4,
			)
		);

		$args = array(
			'echo'         => 0,
			'hide_empty'   => 0,
			'exclude_tree' => $parent,
		);

		$actual = wp_list_categories(
			array(
				'echo'         => 0,
				'hide_empty'   => 0,
				'exclude'      => "$parent,$parent2",
				'exclude_tree' => "$parent3,$parent4",
			)
		);

		$this->assertStringContainsString( '<li class="cat-item cat-item-' . $c . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent2 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child2 . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent3 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent4 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child3 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child4 . '">', $actual );
	}

	/**
	 * @ticket 35156
	 */
	public function test_array_exclude_tree_should_be_merged_with_exclude() {
		$c       = self::factory()->category->create();
		$parent  = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent',
			)
		);
		$child   = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child',
				'parent' => $parent,
			)
		);
		$parent2 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent2',
			)
		);
		$child2  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child2',
				'parent' => $parent2,
			)
		);
		$parent3 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent3',
			)
		);
		$child3  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child3',
				'parent' => $parent3,
			)
		);
		$parent4 = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent4',
			)
		);
		$child4  = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child4',
				'parent' => $parent4,
			)
		);

		$args = array(
			'echo'         => 0,
			'hide_empty'   => 0,
			'exclude_tree' => $parent,
		);

		$actual = wp_list_categories(
			array(
				'echo'         => 0,
				'hide_empty'   => 0,
				'exclude'      => array( $parent, $parent2 ),
				'exclude_tree' => array( $parent3, $parent4 ),
			)
		);

		$this->assertStringContainsString( '<li class="cat-item cat-item-' . $c . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent2 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child2 . '">', $actual );

		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent3 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $parent4 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child3 . '">', $actual );
		$this->assertStringNotContainsString( '<li class="cat-item cat-item-' . $child4 . '">', $actual );
	}

	/**
	 * @ticket 10676
	 */
	public function test_class_containing_current_cat_ancestor() {
		$parent     = self::factory()->category->create(
			array(
				'name' => 'Parent',
				'slug' => 'parent',
			)
		);
		$child      = self::factory()->category->create(
			array(
				'name'   => 'Child',
				'slug'   => 'child',
				'parent' => $parent,
			)
		);
		$child2     = self::factory()->category->create(
			array(
				'name'   => 'Child 2',
				'slug'   => 'child2',
				'parent' => $parent,
			)
		);
		$grandchild = self::factory()->category->create(
			array(
				'name'   => 'Grand Child',
				'slug'   => 'child',
				'parent' => $child,
			)
		);

		$actual = wp_list_categories(
			array(
				'echo'             => 0,
				'hide_empty'       => false,
				'current_category' => $grandchild,
			)
		);

		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $parent . '[^"]*current-cat-ancestor[^"]*"/', $actual );
		$this->assertMatchesRegularExpression( '/class="[^"]*cat-item-' . $child . '[^"]*current-cat-ancestor[^"]*"/', $actual );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*cat-item-' . $grandchild . '[^"]*current-cat-ancestor[^"]*"/', $actual );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*cat-item-' . $child2 . '[^"]*current-cat-ancestor[^"]*"/', $actual );
	}
}
