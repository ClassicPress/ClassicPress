<?php

require_once ABSPATH . 'wp-admin/includes/dashboard.php';

/**
 * @group admin
 */
class Tests_Admin_includesDashboard extends WP_UnitTestCase {
	function set_up() {
		add_filter( 'pre_http_request', [ $this, 'override_features_api_request' ], 10, 3 );
		parent::set_up();
	}

	function tear_down() {
		remove_filter( 'pre_http_request', [ $this, 'override_features_api_request' ], 10 );
		parent::tear_down();
	}

	function override_features_api_request( $preempt, $r, $url ) {
		if ( $url !== 'https://api-v1.classicpress.net/features/1.0/' ) {
			// Not a request we're interested in; do not override.
			return $preempt;
		}

		return [
			'headers'       => [],
			'body'          => file_get_contents(
				dirname( dirname( __DIR__ ) ) . '/data/admin/features.json'
			),
			'response'      => [
				'code'    => 200,
				'message' => 'OK',
			],
			'cookies'       => [],
			'http_response' => null,
		];
	}

	function get_cp_dashboard_petitions_output() {
		// TODO copied from cp_dashboard_petitions()
		$feeds = [
			'trending' => [
				'title' => __( 'Trending' ),
			],
			'most-wanted' => [
				'title' => __( 'Most Wanted' ),
			],
			'recent' => [
				'title' => __( 'Recent' ),
			],
		];
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
