<?php

/**
 * Tests canonical redirects for objects whose slugs LOOK like feed endpoints,
 * e.g. a tag with the slug "rss" (/tag/rss/) or "feed" (/tag/feed/).
 *
 * @see https://github.com/ClassicPress/ClassicPress/issues/2746
 *
 * @group canonical
 * @group rewrite
 * @group query
 * @group feed
 */
class Tests_Canonical_FeedLikeSlugs extends WP_Canonical_UnitTestCase {

	/**
	 * We test using five problematic endpoints (slugs that have special meaning as endpoints):
	 *   feed, rss, rss2, rdf, atom
	 * Also test using a nonproblematic simple slug as a baseline:
	 *   baselinecontroltag
	 *
	 * @var string[]
	 */
	public static $slugs = array( 'feed', 'rss', 'rss2', 'rdf', 'atom', 'baselinecontroltag' );

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::register_custom_taxonomy();

		foreach ( self::$slugs as $slug ) {
			$author_id = $factory->user->create(
				array(
					'user_login'    => 'canonical-' . $slug,
					'user_nicename' => $slug,
				)
			);

			$post_id = $factory->post->create(
				array(
					'post_name'   => $slug,
					'post_author' => $author_id,
				)
			);

			wp_set_post_tags( $post_id, $slug );
			wp_set_post_categories( $post_id, $factory->category->create( array( 'slug' => $slug ) ) );
			wp_set_object_terms( $post_id, $slug, 'wptests_tax' );
		}
	}

	public function set_up() {
		parent::set_up();

		self::register_custom_taxonomy();
		flush_rewrite_rules();
	}

	/**
	 * Registers a custom taxonomy with pretty permalinks.
	 */
	public static function register_custom_taxonomy() {
		register_taxonomy(
			'wptests_tax',
			'post',
			array(
				'public'  => true,
				'rewrite' => array( 'slug' => 'wptests-tax' ),
			)
		);
	}

	/**
	 * @dataProvider data_canonical_archives_with_feed_like_slugs
	 *
	 * @param string $test_url Test URL.
	 * @param string $expected Expected canonical URL.
	 */
	public function test_canonical_archives_with_feed_like_slugs( $test_url, $expected ) {
		$this->assertCanonical( $test_url, $expected );
	}

	/**
	 * Data provider.
	 *
	 * @return array[] Test URLs and expected canonical URLs.
	 */
	public function data_canonical_archives_with_feed_like_slugs() {
		$data = array();

		foreach ( self::$slugs as $slug ) {
			$bases = array(
				'tag'      => "/tag/{$slug}/",
				'category' => "/category/{$slug}/",
				'author'   => "/author/{$slug}/",
				'tax'      => "/wptests-tax/{$slug}/",
			);

			foreach ( $bases as $type => $base ) {
				$data += array(
					"{$type} {$slug}"                  => array( $base, $base ),
					"{$type} {$slug}, page 1"          => array( "{$base}page/1/", $base ),
					"{$type} {$slug}, ?paged=1"        => array( "{$base}?paged=1", $base ),
					"{$type} {$slug}, feed"            => array( "{$base}feed/", "{$base}feed/" ),
					"{$type} {$slug}, feed/feed"       => array( "{$base}feed/feed/", "{$base}feed/" ),
					"{$type} {$slug}, feed/rss2"       => array( "{$base}feed/rss2/", "{$base}feed/" ),
					"{$type} {$slug}, feed/rss"        => array( "{$base}feed/rss/", "{$base}feed/" ),
					"{$type} {$slug}, feed/rdf"        => array( "{$base}feed/rdf/", "{$base}feed/rdf/" ),
					"{$type} {$slug}, feed/atom"       => array( "{$base}feed/atom/", "{$base}feed/atom/" ),
					"{$type} {$slug}, rss2"            => array( "{$base}rss2/", "{$base}feed/" ),
					"{$type} {$slug}, rss"             => array( "{$base}rss/", "{$base}feed/" ),
					"{$type} {$slug}, rdf"             => array( "{$base}rdf/", "{$base}feed/rdf/" ),
					"{$type} {$slug}, atom"            => array( "{$base}atom/", "{$base}feed/atom/" ),
					"{$type} {$slug}, ?feed=rss2"      => array( "{$base}?feed=rss2", "{$base}feed/" ),
					"{$type} {$slug}, ?feed=atom"      => array( "{$base}?feed=atom", "{$base}feed/atom/" ),
				);
			}

			// Ugly term URLs:
			$query_bases = array(
				'tag'      => "/?tag={$slug}",
				'category' => "/?category_name={$slug}",
				'tax'      => "/?wptests_tax={$slug}",
			);

			foreach ( $query_bases as $type => $query_base ) {
				$base = $bases[ $type ];

				$data += array(
					"{$type} {$slug}, query string"    => array( $query_base, $base ),
					"{$type} {$slug}, query paged"     => array( "{$query_base}&paged=1", $base ),
					"{$type} {$slug}, query feed"      => array( "{$query_base}&feed=rss2", "{$base}feed/" ),
					"{$type} {$slug}, query atom feed" => array( "{$query_base}&feed=atom", "{$base}feed/atom/" ),
				);
			}
		}

		return $data;
	}
}
