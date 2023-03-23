<?php
/**
 * Unit tests covering WP_Widget_RSS functionality.
 *
 * @package    WordPress
 * @subpackage widgets
 */

/**
 * Test wp-includes/widgets/class-wp-widget-rss.php
 *
 * @group widgets
 */
class Tests_Widgets_wpWidgetRss extends WP_UnitTestCase {

	/**
	 * @ticket 53278
	 * @covers WP_Widget_RSS::widget
	 * @dataProvider data_url_unhappy_path
	 *
	 * @param mixed $url When null, unsets 'url' arg, else, sets to given value.
	 */
	public function test_url_unhappy_path( $url ) {
		$widget   = new WP_Widget_RSS();
		$args     = array(
			'before_title'  => '<h2>',
			'after_title'   => "</h2>\n",
			'before_widget' => '<section id="widget_rss-5" class="widget widget_rss">',
			'after_widget'  => "</section>\n",
		);
		$instance = array(
			'title' => 'Foo',
			'url'   => $url,
		);

		if ( is_null( $url ) ) {
			unset( $instance['ur'] );
		}

		$this->expectOutputString( '' );

		$widget->widget( $args, $instance );
	}

	public function data_url_unhappy_path() {
		return array(
			'when unset'         => array(
				'url' => null,
			),
			'when empty string'  => array(
				'url' => '',
			),
			'when boolean false' => array(
				'url' => false,
			),
		);
	}

	public function mocked_rss_response() {
		$single_value_headers = array(
			'Content-Type' => 'application/rss+xml; charset=UTF-8',
			'link'         => '<https://wordpress.org/news/wp-json/>; rel="https://api.w.org/"',
		);

		return array(
			'headers'  => new WpOrg\Requests\Utility\CaseInsensitiveDictionary( $single_value_headers ),
			'body'     => file_get_contents( DIR_TESTDATA . '/feed/wordpress-org-news.xml' ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}
}
