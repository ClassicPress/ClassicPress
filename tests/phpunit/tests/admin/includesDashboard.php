<?php

require_once ABSPATH . 'wp-admin/includes/dashboard.php';

/**
 * @group admin
 */
class Tests_Admin_includesDashboard extends WP_UnitTestCase {
	function setUp() {
		add_filter( 'pre_http_request', array( $this, 'override_features_api_request' ), 10, 3 );
		parent::setUp();
	}

	function tearDown() {
		remove_filter( 'pre_http_request', array( $this, 'override_features_api_request' ), 10 );
		parent::tearDown();
	}

	function override_features_api_request( $preempt, $r, $url ) {
		if ( $url !== 'https://api-v1.classicpress.net/features/1.0/' ) {
			// Not a request we're interested in; do not override.
			return $preempt;
		}

		return array(
			'headers'       => array(),
			'body'          => file_get_contents(
				dirname( dirname( __DIR__ ) ) . '/data/admin/features.json'
			),
			'response'      => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'       => array(),
			'http_response' => null,
		);
	}

	function get_cp_dashboard_petitions_output() {
		// TODO copied from cp_dashboard_petitions()
		$feeds = array(
			'trending'    => array(
				'title' => __( 'Trending' ),
			),
			'most-wanted' => array(
				'title' => __( 'Most Wanted' ),
			),
			'recent'      => array(
				'title' => __( 'Recent' ),
			),
		);
		ob_start();
		cp_dashboard_petitions_output( 0, $feeds );
		return ob_get_clean();
	}

	function test_cp_dashboard_petitions_output() {
		$output = $this->get_cp_dashboard_petitions_output();

		$this->assertContains(
			'<div id="trending" class="petitions-pane active">',
			$output
		);
		$this->assertContains(
			'<div id="most-wanted" class="petitions-pane">',
			$output
		);
		$this->assertContains(
			'<div id="recent" class="petitions-pane">',
			$output
		);
	}
}
