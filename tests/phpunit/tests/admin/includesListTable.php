<?php

/**
 * @group admin
 */
class Tests_Admin_includesListTable extends WP_UnitTestCase {
	protected static $top = array();
	protected static $children = array();
	protected static $grandchildren = array();
	protected static $post_ids = array();

	/**
	 * @var WP_Posts_List_Table
	 */
	protected $table;

	function setUp() {
		parent::setUp();
		$this->table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => 'edit-page' ) );
	}

	public static function wpSetUpBeforeClass( $factory ) {
		// note that our top/children/grandchildren arrays are 1-indexed

		// create top level pages
		$num_posts = 5;
		foreach ( range( 1, $num_posts ) as $i ) {
			$p = $factory->post->create_and_get( array(
				'post_type'  => 'page',
				'post_title' => sprintf( 'Top Level Page %d', $i ),
			) );

			self::$top[ $i ] = $p;
			self::$post_ids[] = $p->ID;
		}

		// create child pages
		$num_children = 3;
		foreach ( self::$top as $top => $top_page ) {
			foreach ( range( 1, $num_children ) as $i ) {
				$p = $factory->post->create_and_get( array(
					'post_type'   => 'page',
					'post_parent' => $top_page->ID,
					'post_title'  => sprintf( 'Child %d', $i ),
				) );

				self::$children[ $top ][ $i ] = $p;
				self::$post_ids[] = $p->ID;
			}
		}

		// create grand-child pages for the third and fourth top-level pages
		$num_grandchildren = 3;
		foreach ( range( 3, 4 ) as $top ) {
			foreach ( self::$children[ $top ] as $child => $child_page ) {
				foreach ( range( 1, $num_grandchildren ) as $i ) {
					$p = $factory->post->create_and_get( array(
						'post_type'   => 'page',
						'post_parent' => $child_page->ID,
						'post_title'  => sprintf( 'Grandchild %d', $i ),
					) );

					self::$grandchildren[ $top ][ $child ][ $i ] = $p;
					self::$post_ids[] = $p->ID;
				}
			}
		}
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_list_hierarchical_pages_first_page() {
		$this->_test_list_hierarchical_page( array(
			'paged'          => 1,
			'posts_per_page' => 2,
		), array(
			self::$top[1]->ID,
			self::$children[1][1]->ID,
		) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_list_hierarchical_pages_second_page() {
		$this->_test_list_hierarchical_page( array(
			'paged'          => 2,
			'posts_per_page' => 2,
		), array(
			self::$top[1]->ID,
			self::$children[1][2]->ID,
			self::$children[1][3]->ID,
		) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_search_hierarchical_pages_first_page() {
		$this->_test_list_hierarchical_page( array(
			'paged'          => 1,
			'posts_per_page' => 2,
			's'              => 'Child',
		), array(
			self::$children[1][1]->ID,
			self::$children[1][2]->ID,
		) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_search_hierarchical_pages_second_page() {
		$this->_test_list_hierarchical_page( array(
			'paged'          => 2,
			'posts_per_page' => 2,
			's'              => 'Top',
		), array(
			self::$top[3]->ID,
			self::$top[4]->ID,
		) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_grandchildren_hierarchical_pages_first_page() {
		// page 6 is the first page with grandchildren
		$this->_test_list_hierarchical_page( array(
			'paged'          => 6,
			'posts_per_page' => 2,
		), array(
			self::$top[3]->ID,
			self::$children[3][1]->ID,
			self::$grandchildren[3][1][1]->ID,
			self::$grandchildren[3][1][2]->ID,
		) );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/15459
	 */
	function test_grandchildren_hierarchical_pages_second_page() {
		// page 7 is the second page with grandchildren
		$this->_test_list_hierarchical_page( array(
			'paged'          => 7,
			'posts_per_page' => 2,
		), array(
			self::$top[3]->ID,
			self::$children[3][1]->ID,
			self::$grandchildren[3][1][3]->ID,
			self::$children[3][2]->ID,
		) );
	}

	/**
	 * Helper function to test the output of a page which uses `WP_Posts_List_Table`.
	 *
	 * @param array $args         Query args for the list of pages.
	 * @param array $expected_ids Expected IDs of pages returned.
	 */
	protected function _test_list_hierarchical_page( array $args, array $expected_ids ) {
		$matches = array();

		$_REQUEST['paged']   = $args['paged'];
		$GLOBALS['per_page'] = $args['posts_per_page'];

		$args = array_merge( array(
			'post_type' => 'page',
		), $args );

		// Mimic the behaviour of `wp_edit_posts_query()`:
		if ( ! isset( $args['orderby'] ) ) {
			$args['orderby']                = 'menu_order title';
			$args['order']                  = 'asc';
			$args['posts_per_page']         = -1;
			$args['posts_per_archive_page'] = -1;
		}

		$pages = new WP_Query( $args );

		ob_start();
		$this->table->set_hierarchical_display( true );
		$this->table->display_rows( $pages->posts );
		$output = ob_get_clean();

		// Clean up.
		unset( $_REQUEST['paged'] );
		unset( $GLOBALS['per_page'] );

		preg_match_all( '|<tr[^>]*>|', $output, $matches );

		$this->assertCount( count( $expected_ids ), array_keys( $matches[0] ) );

		foreach ( $expected_ids as $id ) {
			$this->assertContains( sprintf( 'id="post-%d"', $id ), $output );
		}
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37407
	 */
	function test_filter_button_should_not_be_shown_if_there_are_no_posts() {
		// Set post type to a non-existent one.
		$this->table->screen->post_type = 'foo';

		ob_start();
		$this->table->extra_tablenav( 'top' );
		$output = ob_get_clean();

		$this->assertNotContains( 'id="post-query-submit"', $output );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37407
	 */
	function test_months_dropdown_should_not_be_shown_if_there_are_no_posts() {
		// Set post type to a non-existent one.
		$this->table->screen->post_type = 'foo';

		ob_start();
		$this->table->extra_tablenav( 'top' );
		$output = ob_get_clean();

		$this->assertNotContains( 'id="filter-by-date"', $output );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/37407
	 */
	function test_category_dropdown_should_not_be_shown_if_there_are_no_posts() {
		// Set post type to a non-existent one.
		$this->table->screen->post_type = 'foo';

		ob_start();
		$this->table->extra_tablenav( 'top' );
		$output = ob_get_clean();

		$this->assertNotContains( 'id="cat"', $output );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38341
	 */
	public function test_empty_trash_button_should_not_be_shown_if_there_are_no_posts() {
		// Set post type to a non-existent one.
		$this->table->screen->post_type = 'foo';

		ob_start();
		$this->table->extra_tablenav( 'top' );
		$output = ob_get_clean();

		$this->assertNotContains( 'id="delete_all"', $output );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/38341
	 */
	public function test_empty_trash_button_should_not_be_shown_if_there_are_no_comments() {
		$table = _get_list_table( 'WP_Comments_List_Table', array( 'screen' => 'edit-comments' ) );

		ob_start();
		$table->extra_tablenav( 'top' );
		$output = ob_get_clean();

		$this->assertNotContains( 'id="delete_all"', $output );
	}
}
