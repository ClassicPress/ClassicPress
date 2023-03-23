<?php

if ( is_multisite() ) :

	/**
	 * Test network query functionality in multisite.
	 *
	 * @group ms-network
	 * @group ms-network-query
	 * @group multisite
	 */
	class Tests_Multisite_wpNetworkQuery extends WP_UnitTestCase {
		protected static $network_ids;

		public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
			self::$network_ids = array(
				'wordpress.org/'      => array(
					'domain' => 'wordpress.org',
					'path'   => '/',
				),
				'make.wordpress.org/' => array(
					'domain' => 'make.wordpress.org',
					'path'   => '/',
				),
				'www.wordpress.net/'  => array(
					'domain' => 'www.wordpress.net',
					'path'   => '/',
				),
				'www.w.org/foo/'      => array(
					'domain' => 'www.w.org',
					'path'   => '/foo/',
				),
			);

			foreach ( self::$network_ids as &$id ) {
				$id = $factory->network->create( $id );
			}
			unset( $id );
		}

		public static function wpTearDownAfterClass() {
			global $wpdb;

			foreach ( self::$network_ids as $id ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE site_id = %d", $id ) );
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->site} WHERE id= %d", $id ) );
			}
		}

		public function test_wp_network_query_by_number() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields' => 'ids',
					'number' => 3,
				)
			);

			$this->assertCount( 3, $found );
		}

		public function test_wp_network_query_by_network__in_with_order() {
			$expected = array( self::$network_ids['wordpress.org/'], self::$network_ids['make.wordpress.org/'] );

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'      => 'ids',
					'network__in' => $expected,
					'order'       => 'ASC',
				)
			);

			$this->assertSame( $expected, $found );

			$found = $q->query(
				array(
					'fields'      => 'ids',
					'network__in' => $expected,
					'order'       => 'DESC',
				)
			);

			$this->assertSame( array_reverse( $expected ), $found );
		}

		public function test_wp_network_query_by_network__in_with_single_id() {
			$expected = array( self::$network_ids['wordpress.org/'] );

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'      => 'ids',
					'network__in' => $expected,
				)
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_network__in_with_multiple_ids() {
			$expected = array( self::$network_ids['wordpress.org/'], self::$network_ids['www.wordpress.net/'] );

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'      => 'ids',
					'network__in' => $expected,
				)
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_network__in_and_count_with_multiple_ids() {
			$expected = array( self::$network_ids['wordpress.org/'], self::$network_ids['make.wordpress.org/'] );

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'      => 'ids',
					'count'       => true,
					'network__in' => $expected,
				)
			);

			$this->assertSame( 2, $found );
		}

		public function test_wp_network_query_by_network__not_in_with_single_id() {
			$excluded = array( self::$network_ids['wordpress.org/'] );
			$expected = array_diff( self::$network_ids, $excluded );

			// Exclude main network since we don't have control over it here.
			$excluded[] = 1;

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'          => 'ids',
					'network__not_in' => $excluded,
				)
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_network__not_in_with_multiple_ids() {
			$excluded = array( self::$network_ids['wordpress.org/'], self::$network_ids['www.w.org/foo/'] );
			$expected = array_diff( self::$network_ids, $excluded );

			// Exclude main network since we don't have control over it here.
			$excluded[] = 1;

			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'          => 'ids',
					'network__not_in' => $excluded,
				)
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields' => 'ids',
					'domain' => 'www.w.org',
				)
			);

			$expected = array(
				self::$network_ids['www.w.org/foo/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__in_with_single_domain() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'     => 'ids',
					'domain__in' => array( 'make.wordpress.org' ),
				)
			);

			$expected = array(
				self::$network_ids['make.wordpress.org/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__in_with_multiple_domains() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'     => 'ids',
					'domain__in' => array( 'wordpress.org', 'make.wordpress.org' ),
				)
			);

			$expected = array(
				self::$network_ids['wordpress.org/'],
				self::$network_ids['make.wordpress.org/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__in_with_multiple_domains_and_number() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'     => 'ids',
					'number'     => 1,
					'domain__in' => array( 'wordpress.org', 'make.wordpress.org' ),
				)
			);

			$expected = array(
				self::$network_ids['wordpress.org/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__in_with_multiple_domains_and_number_and_offset() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'     => 'ids',
					'number'     => 1,
					'offset'     => 1,
					'domain__in' => array( 'wordpress.org', 'make.wordpress.org' ),
				)
			);

			$expected = array(
				self::$network_ids['make.wordpress.org/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__not_in_with_single_domain() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'         => 'ids',
					'domain__not_in' => array( 'www.w.org' ),
				)
			);

			$expected = array(
				get_current_site()->id, // Account for the initial network added by the test suite.
				self::$network_ids['wordpress.org/'],
				self::$network_ids['make.wordpress.org/'],
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__not_in_with_multiple_domains() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'         => 'ids',
					'domain__not_in' => array( 'wordpress.org', 'www.w.org' ),
				)
			);

			$expected = array(
				get_current_site()->id, // Account for the initial network added by the test suite.
				self::$network_ids['make.wordpress.org/'],
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__not_in_with_multiple_domains_and_number() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'         => 'ids',
					'number'         => 2,
					'domain__not_in' => array( 'wordpress.org', 'www.w.org' ),
				)
			);

			$expected = array(
				get_current_site()->id, // Account for the initial network added by the test suite.
				self::$network_ids['make.wordpress.org/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_domain__not_in_with_multiple_domains_and_number_and_offset() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'         => 'ids',
					'number'         => 2,
					'offset'         => 1,
					'domain__not_in' => array( 'wordpress.org', 'www.w.org' ),
				)
			);

			$expected = array(
				self::$network_ids['make.wordpress.org/'],
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_path_with_expected_results() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'          => 'ids',
					'path'            => '/',
					'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
				)
			);

			$expected = array(
				self::$network_ids['wordpress.org/'],
				self::$network_ids['make.wordpress.org/'],
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_path_and_number_and_offset_with_expected_results() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'          => 'ids',
					'number'          => 1,
					'offset'          => 2,
					'path'            => '/',
					'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
				)
			);

			$expected = array(
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_path_with_no_expected_results() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields' => 'ids',
					'path'   => '/bar/',
				)
			);

			$this->assertEmpty( $found );
		}

		public function test_wp_network_query_by_search_with_text_in_domain() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields' => 'ids',
					'search' => 'ww.word',
				)
			);

			$expected = array(
				self::$network_ids['www.wordpress.net/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_search_with_text_in_path() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields' => 'ids',
					'search' => 'foo',
				)
			);

			$expected = array(
				self::$network_ids['www.w.org/foo/'],
			);

			$this->assertSameSets( $expected, $found );
		}

		public function test_wp_network_query_by_path_order_by_domain_desc() {
			$q     = new WP_Network_Query();
			$found = $q->query(
				array(
					'fields'          => 'ids',
					'path'            => '/',
					'network__not_in' => get_current_site()->id, // Exclude the initial network added by the test suite.
					'order'           => 'DESC',
					'orderby'         => 'domain',
				)
			);

			$expected = array(
				self::$network_ids['www.wordpress.net/'],
				self::$network_ids['wordpress.org/'],
				self::$network_ids['make.wordpress.org/'],
			);

			$this->assertSame( $expected, $found );
		}

		/**
		 * @ticket 41347
		 */
		public function test_wp_network_query_cache_with_different_fields_no_count() {
			global $wpdb;

			$q                 = new WP_Network_Query();
			$query_1           = $q->query(
				array(
					'fields' => 'all',
					'number' => 3,
					'order'  => 'ASC',
				)
			);
			$number_of_queries = $wpdb->num_queries;

			$query_2 = $q->query(
				array(
					'fields' => 'ids',
					'number' => 3,
					'order'  => 'ASC',
				)
			);

			$this->assertSame( $number_of_queries, $wpdb->num_queries );
		}

		/**
		 * @ticket 41347
		 */
		public function test_wp_network_query_cache_with_different_fields_active_count() {
			global $wpdb;

			$q = new WP_Network_Query();

			$query_1           = $q->query(
				array(
					'fields' => 'all',
					'number' => 3,
					'order'  => 'ASC',
					'count'  => true,
				)
			);
			$number_of_queries = $wpdb->num_queries;

			$query_2 = $q->query(
				array(
					'fields' => 'ids',
					'number' => 3,
					'order'  => 'ASC',
					'count'  => true,
				)
			);
			$this->assertSame( $number_of_queries, $wpdb->num_queries );
		}

		/**
		 * @ticket 41347
		 */
		public function test_wp_network_query_cache_with_same_fields_different_count() {
			global $wpdb;

			$q = new WP_Network_Query();

			$query_1 = $q->query(
				array(
					'fields' => 'ids',
					'number' => 3,
					'order'  => 'ASC',
				)
			);

			$number_of_queries = $wpdb->num_queries;

			$query_2 = $q->query(
				array(
					'fields' => 'ids',
					'number' => 3,
					'order'  => 'ASC',
					'count'  => true,
				)
			);
			$this->assertSame( $number_of_queries + 1, $wpdb->num_queries );
		}

		/**
		 * @ticket 55461
		 */
		public function test_wp_network_query_cache_with_same_fields_same_cache_field() {
			$q                 = new WP_Network_Query();
			$query_1           = $q->query(
				array(
					'fields'               => 'all',
					'number'               => 3,
					'order'                => 'ASC',
					'update_network_cache' => true,
				)
			);
			$number_of_queries = get_num_queries();

			$query_2 = $q->query(
				array(
					'fields'               => 'all',
					'number'               => 3,
					'order'                => 'ASC',
					'update_network_cache' => true,
				)
			);

			$this->assertSame( $number_of_queries, get_num_queries() );
		}

		/**
		 * @ticket 55461
		 */
		public function test_wp_network_query_cache_with_same_fields_different_cache_field() {
			$q                 = new WP_Network_Query();
			$query_1           = $q->query(
				array(
					'fields'               => 'all',
					'number'               => 3,
					'order'                => 'ASC',
					'update_network_cache' => true,
				)
			);
			$number_of_queries = get_num_queries();

			$query_2 = $q->query(
				array(
					'fields'               => 'all',
					'number'               => 3,
					'order'                => 'ASC',
					'update_network_cache' => false,
				)
			);

			$this->assertSame( $number_of_queries, get_num_queries() );
		}

		/**
		 * @ticket 45749
		 * @ticket 47599
		 */
		public function test_networks_pre_query_filter_should_bypass_database_query() {
			global $wpdb;

			add_filter( 'networks_pre_query', array( __CLASS__, 'filter_networks_pre_query' ), 10, 2 );

			$num_queries = $wpdb->num_queries;

			$q       = new WP_Network_Query();
			$results = $q->query( array() );

			remove_filter( 'networks_pre_query', array( __CLASS__, 'filter_networks_pre_query' ), 10, 2 );

			// Make sure no queries were executed.
			$this->assertSame( $num_queries, $wpdb->num_queries );

			// We manually inserted a non-existing site and overrode the results with it.
			$this->assertSame( array( 555 ), $results );

			// Make sure manually setting found_networks doesn't get overwritten.
			$this->assertSame( 1, $q->found_networks );
		}

		public static function filter_networks_pre_query( $networks, $query ) {
			$query->found_networks = 1;

			return array( 555 );
		}

		/**
		 * @ticket 51333
		 */
		public function test_networks_pre_query_filter_should_set_networks_property() {
			add_filter( 'networks_pre_query', array( __CLASS__, 'filter_networks_pre_query_and_set_networks' ), 10, 2 );

			$q       = new WP_Network_Query();
			$results = $q->query( array() );

			remove_filter( 'networks_pre_query', array( __CLASS__, 'filter_networks_pre_query_and_set_networks' ), 10 );

			// Make sure the networks property is the same as the results.
			$this->assertSame( $results, $q->networks );

			// Make sure the network domain is `wordpress.org`.
			$this->assertSame( 'wordpress.org', $q->networks[0]->domain );
		}

		public static function filter_networks_pre_query_and_set_networks( $networks, $query ) {
			return array( get_network( self::$network_ids['wordpress.org/'] ) );
		}
	}

endif;
