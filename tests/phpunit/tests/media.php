<?php

/**
 * @group media
 * @group shortcode
 */
class Tests_Media extends WP_UnitTestCase {

	const CAPTION           = 'A simple caption.';
	const ALTERNATE_CAPTION = 'Alternate caption.';

	const HTML_CONTENT = <<<'CAP'
A <strong class='classy'>bolded</strong> <em>caption</em> with a <a href="#">link</a>.
CAP;
	const IMG_CONTENT  = <<<'CAP'
<img src="pic.jpg" id='anId' alt="pic"/>
CAP;

	const IMG_NAME = 'image.jpg';
	const IMG_URL  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . self::IMG_NAME;
	const IMG_META = array(
		'width'  => 100,
		'height' => 100,
		'sizes'  => '',
	);

	protected static $large_id;
	protected static $_sizes;
	protected static $large_filename = 'test-image-large.jpg';
	protected static $post_ids;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$_sizes                          = wp_get_additional_image_sizes();
		$GLOBALS['_wp_additional_image_sizes'] = array();

		$filename       = DIR_TESTDATA . '/images/' . self::$large_filename;
		self::$large_id = $factory->attachment->create_upload_object( $filename );

		$post_statuses = array( 'publish', 'future', 'draft', 'auto-draft', 'trash' );
		foreach ( $post_statuses as $post_status ) {
			$date = '';
			if ( 'future' === $post_status ) {
				date_format( date_create( '+1 year' ), 'Y-m-d H:i:s' );
			}

			self::$post_ids[ $post_status ] = $factory->post->create(
				array(
					'post_status' => 'trash' === $post_status ? 'publish' : $post_status,
					'post_date'   => $date,
					'post_name'   => "$post_status-post",
				)
			);

			// Attachments without media.
			self::$post_ids[ "$post_status-attachment" ] = $factory->attachment->create_object(
				array(
					'post_parent' => self::$post_ids[ $post_status ],
					'post_status' => 'inherit',
					'post_name'   => "$post_status-attachment",
					'post_date'   => $date,
				)
			);
		}

		// Trash the trash post.
		wp_trash_post( self::$post_ids['trash'] );
	}

	public static function wpTearDownAfterClass() {
		$GLOBALS['_wp_additional_image_sizes'] = self::$_sizes;
	}

	public static function tear_down_after_class() {
		wp_delete_attachment( self::$large_id, true );
		parent::tear_down_after_class();
	}

	/**
	 * Ensures that the static content media count and related filter are reset between tests.
	 */
	public function set_up() {
		parent::set_up();

		$this->reset_content_media_count();
		$this->reset_omit_loading_attr_filter();
	}

	public function test_img_caption_shortcode_added() {
		global $shortcode_tags;
		$this->assertSame( 'img_caption_shortcode', $shortcode_tags['caption'] );
		$this->assertSame( 'img_caption_shortcode', $shortcode_tags['wp_caption'] );
	}

	public function test_img_caption_shortcode_with_empty_params() {
		$result = img_caption_shortcode( array() );
		$this->assertSame( '', $result );
	}

	/**
	 * @ticket 33981
	 */
	public function test_img_caption_shortcode_with_empty_params_but_content() {
		$result = img_caption_shortcode( array(), self::CAPTION );
		$this->assertSame( self::CAPTION, $result );
	}

	/**
	 * @ticket 33981
	 */
	public function test_img_caption_shortcode_short_circuit_filter() {
		add_filter( 'img_caption_shortcode', array( $this, 'return_alt_caption' ) );

		$result = img_caption_shortcode( array(), self::CAPTION );
		$this->assertSame( self::ALTERNATE_CAPTION, $result );
	}

	/**
	 * Filter used in test_img_caption_shortcode_short_circuit_filter()
	 */
	public function return_alt_caption() {
		return self::ALTERNATE_CAPTION;
	}

	/**
	 * @ticket 33981
	 */
	public function test_img_caption_shortcode_empty_width() {
		$result = img_caption_shortcode(
			array(
				'width' => 0,
			),
			self::CAPTION
		);
		$this->assertSame( self::CAPTION, $result );
	}

	/**
	 * @ticket 33981
	 */
	public function test_img_caption_shortcode_empty_caption() {
		$result = img_caption_shortcode(
			array(
				'caption' => '',
			)
		);
		$this->assertSame( '', $result );
	}

	/**
	 * @ticket 33981
	 */
	public function test_img_caption_shortcode_empty_caption_and_content() {
		$result = img_caption_shortcode(
			array(
				'caption' => '',
			),
			self::CAPTION
		);
		$this->assertSame( self::CAPTION, $result );
	}

	public function test_img_caption_shortcode_with_old_format() {
		$result = img_caption_shortcode(
			array(
				'width'   => 20,
				'caption' => self::CAPTION,
			)
		);

		$this->assertSame( 2, substr_count( $result, 'wp-caption' ) );
		$this->assertSame( 1, substr_count( $result, 'alignnone' ) );
		$this->assertSame( 1, substr_count( $result, self::CAPTION ) );

		$this->assertSame( 1, substr_count( $result, 'width: 30' ) );
	}

	public function test_img_caption_shortcode_with_old_format_id_and_align() {
		$result = img_caption_shortcode(
			array(
				'width'   => 20,
				'caption' => self::CAPTION,
				'id'      => '"myId',
				'align'   => '&myAlignment',
			)
		);
		$this->assertSame( 1, substr_count( $result, 'wp-caption &amp;myAlignment' ) );
		$this->assertSame( 1, substr_count( $result, 'id="myId"' ) );
		$this->assertSame( 1, substr_count( $result, self::CAPTION ) );
	}

	public function test_img_caption_shortcode_with_old_format_and_class() {
		$result = img_caption_shortcode(
			array(
				'width'   => 20,
				'class'   => 'some-class another-class',
				'caption' => self::CAPTION,
			)
		);
		$this->assertSame( 1, substr_count( $result, 'wp-caption alignnone some-class another-class' ) );
	}

	public function test_new_img_caption_shortcode_with_html_caption() {
		$result = img_caption_shortcode(
			array(
				'width'   => 20,
				'caption' => self::HTML_CONTENT,
			)
		);

		$this->assertSame( 1, substr_count( $result, self::HTML_CONTENT ) );
	}

	public function test_new_img_caption_shortcode_new_format() {
		$result       = img_caption_shortcode(
			array( 'width' => 20 ),
			self::IMG_CONTENT . self::HTML_CONTENT
		);
		$img_preg     = preg_quote( self::IMG_CONTENT );
		$content_preg = preg_quote( self::HTML_CONTENT );

		$this->assertSame( 1, preg_match_all( "~{$img_preg}.*wp-caption-text~", $result ) );
		$this->assertSame( 1, preg_match_all( "~wp-caption-text.*{$content_preg}~", $result ) );
	}

	public function test_new_img_caption_shortcode_new_format_and_linked_image() {
		$linked_image = "<a href='#'>" . self::IMG_CONTENT . '</a>';
		$result       = img_caption_shortcode(
			array( 'width' => 20 ),
			$linked_image . self::HTML_CONTENT
		);
		$img_preg     = preg_quote( $linked_image );
		$content_preg = preg_quote( self::HTML_CONTENT );

		$this->assertSame( 1, preg_match_all( "~{$img_preg}.*wp-caption-text~", $result ) );
		$this->assertSame( 1, preg_match_all( "~wp-caption-text.*{$content_preg}~", $result ) );
	}

	public function test_new_img_caption_shortcode_new_format_and_linked_image_with_newline() {
		$linked_image = "<a href='#'>" . self::IMG_CONTENT . '</a>';
		$result       = img_caption_shortcode(
			array( 'width' => 20 ),
			$linked_image . "\n\n" . self::HTML_CONTENT
		);
		$img_preg     = preg_quote( $linked_image );
		$content_preg = preg_quote( self::HTML_CONTENT );

		$this->assertSame( 1, preg_match_all( "~{$img_preg}.*wp-caption-text~", $result ) );
		$this->assertSame( 1, preg_match_all( "~wp-caption-text.*{$content_preg}~", $result ) );
	}

	/**
	 * @ticket 34595
	 */
	public function test_img_caption_shortcode_has_aria_describedby() {
		$result = img_caption_shortcode(
			array(
				'width' => 20,
				'id'    => 'myId',
			),
			self::IMG_CONTENT . self::HTML_CONTENT
		);

		$this->assertSame( 1, substr_count( $result, 'aria-describedby="caption-myId"' ) );
	}

	public function test_add_remove_oembed_provider() {
		wp_oembed_add_provider( 'http://foo.bar/*', 'http://foo.bar/oembed' );
		$this->assertTrue( wp_oembed_remove_provider( 'http://foo.bar/*' ) );
		$this->assertFalse( wp_oembed_remove_provider( 'http://foo.bar/*' ) );
	}

	/**
	 * @ticket 23776
	 */
	public function test_autoembed_empty() {
		global $wp_embed;

		$content = '';

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $content, $result );
	}

	/**
	 * @ticket 23776
	 *
	 * @group external-http
	 */
	public function test_autoembed_no_paragraphs_around_urls() {
		global $wp_embed;

		$content = <<<EOF
$ my command
First line.

http://example.com/1/
http://example.com/2/
Last line.

<pre>http://some.link/
http://some.other.link/</pre>
EOF;

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $content, $result );
	}

	public function data_autoembed() {
		return array(

			// Should embed.
			array(
				'https://w.org',
				'[embed]',
			),
			array(
				'test
 https://w.org
test',
				'test
 [embed]
test',
			),
			array(
				'<p class="test">https://w.org</p>',
				'<p class="test">[embed]</p>',
			),
			array(
				'<p> https://w.org </p>',
				'<p> [embed] </p>',
			),
			array(
				'<p>test
https://w.org
test</p>',
				'<p>test
[embed]
test</p>',
			),
			array(
				'<p>https://w.org
</p>',
				'<p>[embed]
</p>',
			),

			// Should NOT embed.
			array(
				'test https://w.org</p>',
			),
			array(
				'<span>https://w.org</a>',
			),
			array(
				'<pre>https://w.org
</p>',
			),
			array(
				'<a href="https://w.org">
https://w.org</a>',
			),
		);
	}

	/**
	 * @dataProvider data_autoembed
	 */
	public function test_autoembed( $content, $result = null ) {
		$wp_embed = new Test_Autoembed();

		$this->assertSame( $wp_embed->autoembed( $content ), $result ? $result : $content );
	}

	public function test_wp_prepare_attachment_for_js() {
		// Attachment without media.
		$id   = wp_insert_attachment(
			array(
				'post_status'           => 'publish',
				'post_title'            => 'Prepare',
				'post_content_filtered' => 'Prepare',
				'post_type'             => 'post',
			)
		);
		$post = get_post( $id );

		$prepped = wp_prepare_attachment_for_js( $post );
		$this->assertIsArray( $prepped );
		$this->assertSame( 0, $prepped['uploadedTo'] );
		$this->assertSame( '', $prepped['mime'] );
		$this->assertSame( '', $prepped['type'] );
		$this->assertSame( '', $prepped['subtype'] );
		// #21963, there will be a GUID always, so there will be a URL.
		$this->assertNotEquals( '', $prepped['url'] );
		$this->assertSame( site_url( 'wp-includes/images/media/default.png' ), $prepped['icon'] );

		// Fake a mime.
		$post->post_mime_type = 'image/jpeg';
		$prepped              = wp_prepare_attachment_for_js( $post );
		$this->assertSame( 'image/jpeg', $prepped['mime'] );
		$this->assertSame( 'image', $prepped['type'] );
		$this->assertSame( 'jpeg', $prepped['subtype'] );

		// Fake a mime without a slash. See #WP22532.
		$post->post_mime_type = 'image';
		$prepped              = wp_prepare_attachment_for_js( $post );
		$this->assertSame( 'image', $prepped['mime'] );
		$this->assertSame( 'image', $prepped['type'] );
		$this->assertSame( '', $prepped['subtype'] );

		// Test that if author is not found, we return "(no author)" as `display_name`.
		// The previously used test post contains no author, so we can reuse it.
		$this->assertSame( '(no author)', $prepped['authorName'] );

		// Test that if author has HTML entities in display_name, they're decoded correctly.
		$html_entity_author = self::factory()->user->create(
			array(
				'display_name' => 'You &amp; Me',
			)
		);
		$post->post_author  = $html_entity_author;
		$prepped            = wp_prepare_attachment_for_js( $post );
		$this->assertSame( 'You & Me', $prepped['authorName'] );
	}

	/**
	 * @ticket 38965
	 */
	public function test_wp_prepare_attachment_for_js_without_image_sizes() {
		// Create the attachement post.
		$id = wp_insert_attachment(
			array(
				'post_title'     => 'Attachment Title',
				'post_type'      => 'attachment',
				'post_parent'    => 0,
				'post_mime_type' => 'image/jpeg',
				'guid'           => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/test-image.jpg',
			)
		);

		// Add attachment metadata without sizes.
		wp_update_attachment_metadata(
			$id,
			array(
				'width'  => 50,
				'height' => 50,
				'file'   => 'test-image.jpg',
			)
		);

		$prepped = wp_prepare_attachment_for_js( get_post( $id ) );

		$this->assertArrayHasKey( 'sizes', $prepped );
	}

	/**
	 * @ticket 19067
	 * @expectedDeprecated wp_convert_bytes_to_hr
	 */
	public function test_wp_convert_bytes_to_hr() {
		$kb = 1024;
		$mb = $kb * 1024;
		$gb = $mb * 1024;
		$tb = $gb * 1024;

		// Test if boundaries are correct.
		$this->assertSame( '1TB', wp_convert_bytes_to_hr( $tb ) );
		$this->assertSame( '1GB', wp_convert_bytes_to_hr( $gb ) );
		$this->assertSame( '1MB', wp_convert_bytes_to_hr( $mb ) );
		$this->assertSame( '1KB', wp_convert_bytes_to_hr( $kb ) );

		$this->assertSame( '1 TB', size_format( $tb ) );
		$this->assertSame( '1 GB', size_format( $gb ) );
		$this->assertSame( '1 MB', size_format( $mb ) );
		$this->assertSame( '1 KB', size_format( $kb ) );

		// Now some values around.
		$hr = wp_convert_bytes_to_hr( $tb + $tb / 2 + $mb );
		$this->assertEqualsWithDelta( 1.50000095367, (float) str_replace( ',', '.', $hr ), 0.0001, 'The values should be equal' );

		$hr = wp_convert_bytes_to_hr( $tb - $mb - $kb );
		$this->assertEqualsWithDelta( 1023.99902248, (float) str_replace( ',', '.', $hr ), 0.0001, 'The values should be equal' );

		$hr = wp_convert_bytes_to_hr( $gb + $gb / 2 + $mb );
		$this->assertEqualsWithDelta( 1.5009765625, (float) str_replace( ',', '.', $hr ), 0.0001, 'The values should be equal' );

		$hr = wp_convert_bytes_to_hr( $gb - $mb - $kb );
		$this->assertEqualsWithDelta( 1022.99902344, (float) str_replace( ',', '.', $hr ), 0.0001, 'The values should be equal' );

		// Edge.
		$this->assertSame( '-1B', wp_convert_bytes_to_hr( -1 ) );
		$this->assertSame( '0B', wp_convert_bytes_to_hr( 0 ) );
	}

	/**
	 * @ticket 22960
	 */
	public function test_get_attached_images() {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			self::IMG_NAME,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$images = get_attached_media( 'image', $post_id );
		$this->assertEqualSets( $images, array( $attachment_id => get_post( $attachment_id ) ) );
	}

	/**
	 * @ticket 22960
	 */
	public function test_post_galleries_images() {
		$ids1      = array();
		$ids1_srcs = array();
		foreach ( range( 1, 6 ) as $i ) {
			$attachment_id = self::factory()->attachment->create_object(
				"image$i.jpg",
				0,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
			$metadata      = array_merge( array( 'file' => "image$i.jpg" ), self::IMG_META );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			$ids1[]      = $attachment_id;
			$ids1_srcs[] = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . "image$i.jpg";
		}

		$ids2      = array();
		$ids2_srcs = array();
		foreach ( range( 4, 6 ) as $i ) {
			$attachment_id = self::factory()->attachment->create_object(
				"image$i.jpg",
				0,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
			$metadata      = array_merge( array( 'file' => "image$i.jpg" ), self::IMG_META );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			$ids2[]      = $attachment_id;
			$ids2_srcs[] = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . "image$i.jpg";
		}

		$ids1_joined = join( ',', array_slice( $ids1, 0, 3 ) );
		$ids2_joined = join( ',', array_slice( $ids2, 3, 3 ) );

		$blob    = <<<BLOB
[gallery ids="$ids1_joined"]

[gallery ids="$ids2_joined"]
BLOB;
		$post_id = self::factory()->post->create( array( 'post_content' => $blob ) );
		$srcs    = get_post_galleries_images( $post_id );
		$this->assertSameSetsWithIndex( $srcs, array( array_slice( $ids1_srcs, 0, 3 ), array_slice( $ids2_srcs, 3, 3 ) ) );
	}

	/**
	 * @ticket 22960
	 */
	public function test_post_gallery_images() {
		$ids1      = array();
		$ids1_srcs = array();
		foreach ( range( 1, 3 ) as $i ) {
			$attachment_id = self::factory()->attachment->create_object(
				"image$i.jpg",
				0,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
			$metadata      = array_merge( array( 'file' => "image$i.jpg" ), self::IMG_META );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			$ids1[]      = $attachment_id;
			$ids1_srcs[] = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . "image$i.jpg";
		}

		$ids2      = array();
		$ids2_srcs = array();
		foreach ( range( 4, 6 ) as $i ) {
			$attachment_id = self::factory()->attachment->create_object(
				"image$i.jpg",
				0,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
			$metadata      = array_merge( array( 'file' => "image$i.jpg" ), self::IMG_META );
			wp_update_attachment_metadata( $attachment_id, $metadata );
			$ids2[]      = $attachment_id;
			$ids2_srcs[] = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . "image$i.jpg";
		}

		$ids1_joined = implode( ',', $ids1 );
		$ids2_joined = implode( ',', $ids2 );

		$blob    = <<<BLOB
[gallery ids="$ids1_joined"]

[gallery ids="$ids2_joined"]
BLOB;
		$post_id = self::factory()->post->create( array( 'post_content' => $blob ) );
		$srcs    = get_post_gallery_images( $post_id );
		$this->assertSame( $srcs, $ids1_srcs );
	}

	public function test_get_media_embedded_in_content() {
		$object = <<<OBJ
<object src="this" data="that">
	<param name="value"/>
</object>
OBJ;
		$embed  = <<<EMBED
<embed src="something.mp4"/>
EMBED;
		$iframe = <<<IFRAME
<iframe src="youtube.com" width="7000" />
IFRAME;
		$audio  = <<<AUDIO
<audio preload="none">
	<source />
</audio>
AUDIO;
		$video  = <<<VIDEO
<video preload="none">
	<source />
</video>
VIDEO;

		$content = <<<CONTENT
This is a comment
$object

This is a comment
$embed

This is a comment
$iframe

This is a comment
$audio

This is a comment
$video

This is a comment
CONTENT;

		$types    = array( 'object', 'embed', 'iframe', 'audio', 'video' );
		$contents = array_values( compact( $types ) );

		$matches = get_media_embedded_in_content( $content, 'audio' );
		$this->assertSame( array( $audio ), $matches );

		$matches = get_media_embedded_in_content( $content, 'video' );
		$this->assertSame( array( $video ), $matches );

		$matches = get_media_embedded_in_content( $content, 'object' );
		$this->assertSame( array( $object ), $matches );

		$matches = get_media_embedded_in_content( $content, 'embed' );
		$this->assertSame( array( $embed ), $matches );

		$matches = get_media_embedded_in_content( $content, 'iframe' );
		$this->assertSame( array( $iframe ), $matches );

		$matches = get_media_embedded_in_content( $content, $types );
		$this->assertSame( $contents, $matches );
	}

	public function test_get_media_embedded_in_content_order() {
		$audio   = <<<AUDIO
<audio preload="none">
	<source />
</audio>
AUDIO;
		$video   = <<<VIDEO
<video preload="none">
	<source />
</video>
VIDEO;
		$content = $audio . $video;

		$matches1 = get_media_embedded_in_content( $content, array( 'audio', 'video' ) );
		$this->assertSame( array( $audio, $video ), $matches1 );

		$reversed = $video . $audio;
		$matches2 = get_media_embedded_in_content( $reversed, array( 'audio', 'video' ) );
		$this->assertSame( array( $video, $audio ), $matches2 );
	}

	/**
	 * @ticket 35367
	 */
	public function test_wp_audio_shortcode_with_empty_params() {
		$this->assertNull( wp_audio_shortcode( array() ) );
	}

	/**
	 * @ticket 35367
	 */
	public function test_wp_audio_shortcode_with_bad_attr() {
		$this->assertSame(
			'<a class="wp-embedded-audio" href="https://example.com/foo.php">https://example.com/foo.php</a>',
			wp_audio_shortcode(
				array(
					'src' => 'https://example.com/foo.php',
				)
			)
		);
	}

	/**
	 * @ticket 35367
	 */
	public function test_wp_audio_shortcode_attributes() {
		$actual = wp_audio_shortcode(
			array(
				'src' => 'https://example.com/foo.mp3',
			)
		);

		$this->assertStringContainsString( 'src="https://example.com/foo.mp3', $actual );
		$this->assertStringNotContainsString( 'loop', $actual );
		$this->assertStringNotContainsString( 'autoplay', $actual );
		$this->assertStringContainsString( 'preload="none"', $actual );
		$this->assertStringContainsString( 'class="wp-audio-shortcode"', $actual );
		$this->assertStringContainsString( 'style="width: 100%;"', $actual );

		$actual = wp_audio_shortcode(
			array(
				'src'      => 'https://example.com/foo.mp3',
				'loop'     => true,
				'autoplay' => true,
				'preload'  => true,
				'class'    => 'foobar',
				'style'    => 'padding:0;',
			)
		);

		$this->assertStringContainsString( 'src="https://example.com/foo.mp3', $actual );
		$this->assertStringContainsString( 'loop="1"', $actual );
		$this->assertStringContainsString( 'autoplay="1"', $actual );
		$this->assertStringContainsString( 'preload="1"', $actual );
		$this->assertStringContainsString( 'class="foobar"', $actual );
		$this->assertStringContainsString( 'style="padding:0;"', $actual );
	}

	/**
	 * Test [video] shortcode processing
	 */
	public function test_video_shortcode_body() {
		$width  = 720;
		$height = 480;

		$w = empty( $GLOBALS['content_width'] ) ? 640 : $GLOBALS['content_width'];
		if ( $width > $w ) {
			$width = $w;
		}

		$post_id = get_post() ? get_the_ID() : 0;

		$video = <<<VIDEO
[video width="$width" height="480" mp4="http://domain.tld/wp-content/uploads/2013/12/xyz.mp4"]
<!-- WebM/VP8 for Firefox4, Opera, and Chrome -->
<source type="video/webm" src="myvideo.webm" />
<!-- Ogg/Vorbis for older Firefox and Opera versions -->
<source type="video/ogg" src="myvideo.ogv" />
<!-- Optional: Add subtitles for each language -->
<track kind="subtitles" src="subtitles.srt" srclang="en" />
<!-- Optional: Add chapters -->
<track kind="chapters" src="chapters.srt" srclang="en" />
[/video]
VIDEO;

		$h = ceil( ( $height * $width ) / $width );

		$content = apply_filters( 'the_content', $video );

		$expected = '<div style="width: ' . $width . 'px;" class="wp-video">' .
			"<!--[if lt IE 9]><script>document.createElement('video');</script><![endif]-->\n" .
			'<video class="wp-video-shortcode" id="video-' . $post_id . '-1" width="' . $width . '" height="' . $h . '" preload="metadata" controls>' .
			'<source type="video/mp4" src="http://domain.tld/wp-content/uploads/2013/12/xyz.mp4?_=1">' .
			'<!-- WebM/VP8 for Firefox4, Opera, and Chrome --><source type="video/webm" src="myvideo.webm" />' .
			'<!-- Ogg/Vorbis for older Firefox and Opera versions --><source type="video/ogg" src="myvideo.ogv" />' .
			'<!-- Optional: Add subtitles for each language --><track kind="subtitles" src="subtitles.srt" srclang="en" />' .
			'<!-- Optional: Add chapters --><track kind="chapters" src="chapters.srt" srclang="en" />' .
			'<a href="http://domain.tld/wp-content/uploads/2013/12/xyz.mp4">' .
			"http://domain.tld/wp-content/uploads/2013/12/xyz.mp4</a></video></div>\n";

		$this->assertSame( $expected, $content );
	}

	/**
	 * @ticket 35367
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_with_empty_params() {
		$this->assertNull( wp_video_shortcode( array() ) );
	}

	/**
	 * @ticket 35367
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_with_bad_attr() {
		$this->assertSame(
			'<a class="wp-embedded-video" href="https://example.com/foo.php">https://example.com/foo.php</a>',
			wp_video_shortcode(
				array(
					'src' => 'https://example.com/foo.php',
				)
			)
		);
	}

	/**
	 * @ticket 35367
	 * @ticket 54788
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_attributes() {
		$actual = wp_video_shortcode(
			array(
				'src' => 'https://example.com/foo.mp4',
			)
		);

		$this->assertStringContainsString( 'src="https://example.com/foo.mp4', $actual );
		$this->assertStringNotContainsString( 'loop', $actual );
		$this->assertStringNotContainsString( 'autoplay', $actual );
		$this->assertStringNotContainsString( 'muted', $actual );
		$this->assertStringContainsString( 'preload="metadata"', $actual );
		$this->assertStringContainsString( 'width="640"', $actual );
		$this->assertStringContainsString( 'height="360"', $actual );
		$this->assertStringContainsString( 'class="wp-video-shortcode"', $actual );

		$actual = wp_video_shortcode(
			array(
				'src'      => 'https://example.com/foo.mp4',
				'poster'   => 'https://example.com/foo.png',
				'loop'     => true,
				'autoplay' => true,
				'muted'    => true,
				'preload'  => true,
				'width'    => 123,
				'height'   => 456,
				'class'    => 'foobar',
			)
		);

		$this->assertStringContainsString( 'src="https://example.com/foo.mp4', $actual );
		$this->assertStringContainsString( 'poster="https://example.com/foo.png', $actual );
		$this->assertStringContainsString( 'loop="1"', $actual );
		$this->assertStringContainsString( 'autoplay="1"', $actual );
		$this->assertStringContainsString( 'muted', $actual );
		$this->assertStringContainsString( 'preload="1"', $actual );
		$this->assertStringContainsString( 'width="123"', $actual );
		$this->assertStringContainsString( 'height="456"', $actual );
		$this->assertStringContainsString( 'class="foobar"', $actual );
	}

	/**
	 * @ticket 40866
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_youtube_remove_feature() {
		$actual = wp_video_shortcode(
			array(
				'src' => 'https://www.youtube.com/watch?v=72xdCU__XCk&feature=youtu.be',
			)
		);

		$this->assertStringNotContainsString( 'feature=youtu.be', $actual );
	}

	/**
	 * @ticket 40866
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_youtube_force_ssl() {
		$actual = wp_video_shortcode(
			array(
				'src' => 'http://www.youtube.com/watch?v=72xdCU__XCk',
			)
		);

		$this->assertStringContainsString( 'src="https://www.youtube.com/watch?v=72xdCU__XCk', $actual );
	}

	/**
	 * @ticket 40866
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_vimeo_force_ssl_remove_query_args() {
		$actual = wp_video_shortcode(
			array(
				'src' => 'http://vimeo.com/76979871?blah=meh',
			)
		);

		$this->assertStringContainsString( 'src="https://vimeo.com/76979871', $actual );
		$this->assertStringNotContainsString( 'blah=meh', $actual );
	}

	/**
	 * @ticket 40977
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_vimeo_adds_loop() {
		$actual = wp_video_shortcode(
			array(
				'src' => 'http://vimeo.com/76979871',
			)
		);

		$this->assertStringContainsString( 'src="https://vimeo.com/76979871?loop=0', $actual );
	}

	/**
	 * @ticket 40977
	 * @depends test_video_shortcode_body
	 */
	public function test_wp_video_shortcode_vimeo_force_adds_loop_true() {
		$actual = wp_video_shortcode(
			array(
				'src'  => 'http://vimeo.com/76979871',
				'loop' => true,
			)
		);

		$this->assertStringContainsString( 'src="https://vimeo.com/76979871?loop=1', $actual );
	}

	/**
	 * @ticket 26768
	 */
	public function test_add_image_size() {
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		remove_image_size( 'test-size' );

		$this->assertArrayNotHasKey( 'test-size', $_wp_additional_image_sizes );
		add_image_size( 'test-size', 200, 600 );

		$sizes = wp_get_additional_image_sizes();

		// Clean up.
		remove_image_size( 'test-size' );

		$this->assertArrayHasKey( 'test-size', $sizes );
		$this->assertSame( 200, $sizes['test-size']['width'] );
		$this->assertSame( 600, $sizes['test-size']['height'] );
	}

	/**
	 * @ticket 26768
	 */
	public function test_remove_image_size() {
		add_image_size( 'test-size', 200, 600 );
		$this->assertTrue( has_image_size( 'test-size' ) );
		remove_image_size( 'test-size' );
		$this->assertFalse( has_image_size( 'test-size' ) );
	}

	/**
	 * @ticket 26951
	 */
	public function test_has_image_size() {
		add_image_size( 'test-size', 200, 600 );
		$this->assertTrue( has_image_size( 'test-size' ) );

		// Clean up.
		remove_image_size( 'test-size' );
	}

	/**
	 * @ticket 30346
	 */
	public function test_attachment_url_to_postid() {
		$image_path    = '2014/11/' . self::IMG_NAME;
		$attachment_id = self::factory()->attachment->create_object(
			$image_path,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$image_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_path;
		$this->assertSame( $attachment_id, attachment_url_to_postid( $image_url ) );
	}

	/**
	 * @ticket 33109
	 */
	public function test_attachment_url_to_postid_with_different_scheme() {
		$image_path    = '2014/11/' . self::IMG_NAME;
		$attachment_id = self::factory()->attachment->create_object(
			$image_path,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$image_url = 'https://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_path;
		$this->assertSame( $attachment_id, attachment_url_to_postid( $image_url ) );
	}

	/**
	 * @ticket 39768
	 */
	public function test_attachment_url_to_postid_should_be_case_sensitive() {
		$image_path_lower_case    = '2014/11/' . self::IMG_NAME;
		$attachment_id_lower_case = self::factory()->attachment->create_object(
			$image_path_lower_case,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$image_path_upper_case    = '2014/11/' . ucfirst( self::IMG_NAME );
		$attachment_id_upper_case = self::factory()->attachment->create_object(
			$image_path_upper_case,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$image_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_path_upper_case;
		$this->assertSame( $attachment_id_upper_case, attachment_url_to_postid( $image_url ) );
	}

	public function test_attachment_url_to_postid_filtered() {
		$image_path    = '2014/11/' . self::IMG_NAME;
		$attachment_id = self::factory()->attachment->create_object(
			$image_path,
			0,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
		$image_url = 'http://192.168.1.20.com/wp-content/uploads/' . $image_path;
		$this->assertSame( $attachment_id, attachment_url_to_postid( $image_url ) );
		remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );
	}

	public function upload_dir( $dir ) {
		$dir['baseurl'] = 'http://192.168.1.20.com/wp-content/uploads';
		return $dir;
	}

	/**
	 * @ticket 31044
	 */
	public function test_attachment_url_to_postid_with_empty_url() {
		$post_id = attachment_url_to_postid( '' );
		$this->assertIsInt( $post_id );
		$this->assertSame( 0, $post_id );
	}

	/**
	 * @ticket 22768
	 */
	public function test_media_handle_upload_sets_post_excerpt() {
		$iptc_file = DIR_TESTDATA . '/images/test-image-iptc.jpg';

		// Make a copy of this file as it gets moved during the file upload.
		$tmp_name = wp_tempnam( $iptc_file );

		copy( $iptc_file, $tmp_name );

		$_FILES['upload'] = array(
			'tmp_name' => $tmp_name,
			'name'     => 'test-image-iptc.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $iptc_file ),
		);

		$post_id = media_handle_upload(
			'upload',
			0,
			array(),
			array(
				'action'    => 'test_iptc_upload',
				'test_form' => false,
			)
		);

		unset( $_FILES['upload'] );

		$post = get_post( $post_id );

		// Clean up.
		wp_delete_attachment( $post_id, true );

		$this->assertSame( 'This is a comment. / Это комментарий. / Βλέπετε ένα σχόλιο.', $post->post_excerpt );
	}

	/**
	 * @ticket 37989
	 */
	public function test_media_handle_upload_expected_titles() {
		$test_file = DIR_TESTDATA . '/images/test-image.jpg';

		// Make a copy of this file as it gets moved during the file upload.
		$tmp_name = wp_tempnam( $test_file );

		copy( $test_file, $tmp_name );

		$_FILES['upload'] = array(
			'tmp_name' => $tmp_name,
			'name'     => 'This is a test.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $test_file ),
		);

		$post_id = media_handle_upload(
			'upload',
			0,
			array(),
			array(
				'action'    => 'test_upload_titles',
				'test_form' => false,
			)
		);

		unset( $_FILES['upload'] );

		$post = get_post( $post_id );

		// Clean up.
		wp_delete_attachment( $post_id, true );

		$this->assertSame( 'This is a test', $post->post_title );
	}

	/**
	 * @ticket 33016
	 */
	public function test_multiline_cdata() {
		global $wp_embed;

		$content = <<<EOF
<script>// <![CDATA[
_my_function('data');
// ]]>
</script>
EOF;

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $content, $result );
	}

	/**
	 * @ticket 33016
	 */
	public function test_multiline_comment() {
		global $wp_embed;

		$content = <<<EOF
<script><!--
my_function();
// --> </script>
EOF;

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $content, $result );
	}


	/**
	 * @ticket 33016
	 *
	 * @group external-http
	 */
	public function test_multiline_comment_with_embeds() {
		$content = <<<EOF
Start.
[embed]http://www.youtube.com/embed/TEST01YRHA0[/embed]
<script><!--
my_function();
// --> </script>
http://www.youtube.com/embed/TEST02YRHA0
[embed]http://www.example.com/embed/TEST03YRHA0[/embed]
http://www.example.com/embed/TEST04YRHA0
Stop.
EOF;

		$expected = <<<EOF
<p>Start.<br>
https://youtube.com/watch?v=TEST01YRHA0<br>
<script><!--
my_function();
// --> </script><br>
https://youtube.com/watch?v=TEST02YRHA0<br>
<a href="http://www.example.com/embed/TEST03YRHA0">http://www.example.com/embed/TEST03YRHA0</a><br>
http://www.example.com/embed/TEST04YRHA0<br>
Stop.</p>

EOF;

		$result = apply_filters( 'the_content', $content );
		$this->assertSameIgnoreEOL( $expected, $result );
	}

	/**
	 * @ticket 33016
	 */
	public function filter_wp_embed_shortcode_custom( $content, $url ) {
		if ( 'https://www.example.com/?video=1' === $url ) {
			$content = '@embed URL was replaced@';
		}
		return $content;
	}

	/**
	 * @ticket 33016
	 *
	 * @group external-http
	 */
	public function test_oembed_explicit_media_link() {
		global $wp_embed;
		add_filter( 'embed_maybe_make_link', array( $this, 'filter_wp_embed_shortcode_custom' ), 10, 2 );

		$content = <<<EOF
https://www.example.com/?video=1
EOF;

		$expected = <<<EOF
@embed URL was replaced@
EOF;

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $expected, $result );

		$content = <<<EOF
<a href="https://www.example.com/?video=1">https://www.example.com/?video=1</a>
<script>// <![CDATA[
_my_function('data');
myvar = 'Hello world
https://www.example.com/?video=1
do not break this';
// ]]>
</script>
EOF;

		$result = $wp_embed->autoembed( $content );
		$this->assertSame( $content, $result );

		remove_filter( 'embed_maybe_make_link', array( $this, 'filter_wp_embed_shortcode_custom' ), 10 );
	}

	/**
	 * Tests the default output of `wp_get_attachment_image()`.
	 *
	 * @ticket 34635
	 */
	public function test_wp_get_attachment_image_defaults() {
		$image    = image_downsize( self::$large_id, 'thumbnail' );
		$expected = sprintf(
			'<img width="%1$d" height="%2$d" src="%3$s" class="attachment-thumbnail size-thumbnail" alt="" decoding="async" loading="lazy">',
			$image[1],
			$image[2],
			$image[0]
		);

		$this->assertSame( $expected, wp_get_attachment_image( self::$large_id ) );
	}

	/**
	 * @ticket 50801
	 */
	public function test_wp_get_attachment_image_filter_output() {
		$image    = image_downsize( self::$large_id, 'thumbnail' );
		$expected = 'Override wp_get_attachment_image';

		add_filter( 'wp_get_attachment_image', array( $this, 'filter_wp_get_attachment_image' ) );
		$output = wp_get_attachment_image( self::$large_id );
		remove_filter( 'wp_get_attachment_image', array( $this, 'filter_wp_get_attachment_image' ) );

		$this->assertSame( $expected, $output );
	}

	public function filter_wp_get_attachment_image() {
		return 'Override wp_get_attachment_image';
	}

	/**
	 * Test that `wp_get_attachment_image()` returns a proper alt value.
	 *
	 * @ticket 34635
	 */
	public function test_wp_get_attachment_image_with_alt() {
		// Add test alt metadata.
		update_post_meta( self::$large_id, '_wp_attachment_image_alt', 'Some very clever alt text', true );

		$image    = image_downsize( self::$large_id, 'thumbnail' );
		$expected = sprintf(
			'<img width="%1$d" height="%2$d" src="%3$s" class="attachment-thumbnail size-thumbnail" alt="Some very clever alt text" decoding="async" loading="lazy">',
			$image[1],
			$image[2],
			$image[0]
		);

		$this->assertSame( $expected, wp_get_attachment_image( self::$large_id ) );

		// Cleanup.
		update_post_meta( self::$large_id, '_wp_attachment_image_alt', '', true );
	}

	/**
	 * @ticket 33878
	 */
	public function test_wp_get_attachment_image_url() {
		$this->assertFalse( wp_get_attachment_image_url( 0 ) );

		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			self::IMG_NAME,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$image = wp_get_attachment_image_src( $attachment_id, 'thumbnail', false );

		$this->assertSame( $image[0], wp_get_attachment_image_url( $attachment_id ) );
	}

	/**
	 * @ticket 12235
	 */
	public function test_wp_get_attachment_caption() {
		$this->assertFalse( wp_get_attachment_caption( 0 ) );

		$caption = 'This is a caption.';

		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			self::IMG_NAME,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_excerpt'   => $caption,
			)
		);

		$this->assertFalse( wp_get_attachment_caption( $post_id ) );

		$this->assertSame( $caption, wp_get_attachment_caption( $attachment_id ) );
	}

	/**
	 * @ticket 12235
	 */
	public function test_wp_get_attachment_caption_empty() {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_object(
			self::IMG_NAME,
			$post_id,
			array(
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
				'post_excerpt'   => '',
			)
		);

		$this->assertSame( '', wp_get_attachment_caption( $attachment_id ) );
	}

	/**
	 * Helper function to get image size array from size "name".
	 */
	private function get_image_size_array_from_meta( $image_meta, $size_name ) {
		$array = false;

		if ( is_array( $image_meta ) ) {
			if ( 'full' === $size_name && isset( $image_meta['width'] ) && isset( $image_meta['height'] ) ) {
				$array = array( $image_meta['width'], $image_meta['height'] );
			} elseif ( isset( $image_meta['sizes'][ $size_name ]['width'] ) && isset( $image_meta['sizes'][ $size_name ]['height'] ) ) {
				$array = array( $image_meta['sizes'][ $size_name ]['width'], $image_meta['sizes'][ $size_name ]['height'] );
			}
		}

		if ( ! $array ) {
			$this->fail( sprintf( "Could not retrieve image metadata for size '%s'.", $size_name ) );
		}

		return $array;
	}

	/**
	 * Helper function to move the src image to the first position in the expected srcset string.
	 */
	private function src_first( $srcset, $src_url, $src_width ) {
		$src_string    = $src_url . ' ' . $src_width . 'w';
		$src_not_first = ', ' . $src_string;

		if ( strpos( $srcset, $src_not_first ) ) {
			$srcset = str_replace( $src_not_first, '', $srcset );
			$srcset = $src_string . ', ' . $srcset;
		}

		return $srcset;
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_srcset() {
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		$year_month      = gmdate( 'Y/m' );
		$image_meta      = wp_get_attachment_metadata( self::$large_id );
		$uploads_dir_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/';

		// Set up test cases for all expected size names.
		$intermediates = array( 'medium', 'medium_large', 'large', 'full' );

		// Add any soft crop intermediate sizes.
		foreach ( $_wp_additional_image_sizes as $name => $additional_size ) {
			if ( ! $_wp_additional_image_sizes[ $name ]['crop'] || 0 === $_wp_additional_image_sizes[ $name ]['height'] ) {
				$intermediates[] = $name;
			}
		}

		$expected = '';

		foreach ( $image_meta['sizes'] as $name => $size ) {
			// Allow the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, $intermediates, true ) ) {
				$expected .= $uploads_dir_url . $year_month . '/' . $size['file'] . ' ' . $size['width'] . 'w, ';
			}
		}

		$expected = trim( $expected, ' ,' );

		foreach ( $intermediates as $int_size ) {
			$image_url  = wp_get_attachment_image_url( self::$large_id, $int_size );
			$size_array = $this->get_image_size_array_from_meta( $image_meta, $int_size );

			if ( 'full' === $int_size ) {
				// Add the full size image. Expected to be in the srcset when the full size image is used as src.
				$_expected = $uploads_dir_url . $image_meta['file'] . ' ' . $image_meta['width'] . 'w, ' . $expected;
			} else {
				$_expected = $expected;
			}

			$expected_srcset = $this->src_first( $_expected, $image_url, $size_array[0] );
			$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_url, $image_meta ) );
		}
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_srcset_no_date_uploads() {
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		// Disable date organized uploads.
		add_filter( 'upload_dir', '_upload_dir_no_subdir' );

		// Make an image.
		$filename = DIR_TESTDATA . '/images/' . self::$large_filename;
		$id       = self::factory()->attachment->create_upload_object( $filename );

		$image_meta      = wp_get_attachment_metadata( $id );
		$uploads_dir_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/';

		// Set up test cases for all expected size names.
		$intermediates = array( 'medium', 'medium_large', 'large', 'full' );

		foreach ( $_wp_additional_image_sizes as $name => $additional_size ) {
			if ( ! $_wp_additional_image_sizes[ $name ]['crop'] || 0 === $_wp_additional_image_sizes[ $name ]['height'] ) {
				$intermediates[] = $name;
			}
		}

		$expected = '';

		foreach ( $image_meta['sizes'] as $name => $size ) {
			// Allow the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, $intermediates, true ) ) {
				$expected .= $uploads_dir_url . $size['file'] . ' ' . $size['width'] . 'w, ';
			}
		}

		$expected = trim( $expected, ' ,' );

		foreach ( $intermediates as $int_size ) {
			$image_urls[ $int_size ] = wp_get_attachment_image_url( $id, $int_size );
		}

		// Remove the attachment.
		wp_delete_attachment( $id, true );
		remove_filter( 'upload_dir', '_upload_dir_no_subdir' );

		foreach ( $intermediates as $int_size ) {
			$size_array = $this->get_image_size_array_from_meta( $image_meta, $int_size );
			$image_url  = $image_urls[ $int_size ];

			if ( 'full' === $int_size ) {
				// Add the full size image. Expected to be in the srcset when the full size image is used as src.
				$_expected = $uploads_dir_url . $image_meta['file'] . ' ' . $image_meta['width'] . 'w, ' . $expected;
			} else {
				$_expected = $expected;
			}

			$expected_srcset = $this->src_first( $_expected, $image_url, $size_array[0] );
			$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_url, $image_meta ) );
		}
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_srcset_with_edits() {
		// For this test we're going to mock metadata changes from an edit.
		// Start by getting the attachment metadata.
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$image_url  = wp_get_attachment_image_url( self::$large_id, 'medium' );
		$size_array = $this->get_image_size_array_from_meta( $image_meta, 'medium' );

		// Copy hash generation method used in wp_save_image().
		$hash = 'e' . time() . rand( 100, 999 );

		$filename_base = wp_basename( self::$large_filename, '.jpg' );
		$filename_hash = "{$filename_base}-{$hash}";

		// Add the hash to the image URL.
		$image_url = str_replace( $filename_base, $filename_hash, $image_url );

		// Replace file paths for full and medium sizes with hashed versions.
		$image_meta['sizes']['medium']['file']       = str_replace( $filename_base, $filename_hash, $image_meta['sizes']['medium']['file'] );
		$image_meta['sizes']['medium_large']['file'] = str_replace( $filename_base, $filename_hash, $image_meta['sizes']['medium_large']['file'] );
		$image_meta['sizes']['large']['file']        = str_replace( $filename_base, $filename_hash, $image_meta['sizes']['large']['file'] );

		// Calculate a srcset array.
		$sizes = explode( ', ', wp_calculate_image_srcset( $size_array, $image_url, $image_meta ) );

		// Test to confirm all sources in the array include the same edit hash.
		foreach ( $sizes as $size ) {
			$this->assertStringContainsString( $hash, $size );
		}
	}

	/**
	 * @ticket 35106
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_srcset_with_absolute_path_in_meta() {
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		$year_month      = gmdate( 'Y/m' );
		$image_meta      = wp_get_attachment_metadata( self::$large_id );
		$uploads_dir_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/';

		// Set up test cases for all expected size names.
		$intermediates = array( 'medium', 'medium_large', 'large', 'full' );

		// Add any soft crop intermediate sizes.
		foreach ( $_wp_additional_image_sizes as $name => $additional_size ) {
			if ( ! $_wp_additional_image_sizes[ $name ]['crop'] || 0 === $_wp_additional_image_sizes[ $name ]['height'] ) {
				$intermediates[] = $name;
			}
		}

		$expected = '';

		foreach ( $image_meta['sizes'] as $name => $size ) {
			// Allow the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, $intermediates, true ) ) {
				$expected .= $uploads_dir_url . $year_month . '/' . $size['file'] . ' ' . $size['width'] . 'w, ';
			}
		}

		$expected       = trim( $expected, ' ,' );
		$full_size_file = $image_meta['file'];

		// Prepend an absolute path to simulate a pre-2.7 upload.
		$image_meta['file'] = 'H:\home\wordpress\trunk/wp-content/uploads/' . $image_meta['file'];

		foreach ( $intermediates as $int_size ) {
			$image_url  = wp_get_attachment_image_url( self::$large_id, $int_size );
			$size_array = $this->get_image_size_array_from_meta( $image_meta, $int_size );

			if ( 'full' === $int_size ) {
				// Add the full size image. Expected to be in the srcset when the full size image is used as src.
				$_expected = $uploads_dir_url . $full_size_file . ' ' . $image_meta['width'] . 'w, ' . $expected;
			} else {
				$_expected = $expected;
			}

			$expected_srcset = $this->src_first( $_expected, $image_url, $size_array[0] );
			$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_url, $image_meta ) );
		}
	}

	/**
	 * @ticket 33641
	 */
	public function test_wp_calculate_image_srcset_false() {
		$sizes = wp_calculate_image_srcset( array( 400, 300 ), 'file.png', array() );

		// For canola.jpg we should return.
		$this->assertFalse( $sizes );
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_srcset_no_width() {
		$image_url  = wp_get_attachment_image_url( self::$large_id, 'medium' );
		$image_meta = wp_get_attachment_metadata( self::$large_id );

		$size_array = array( 0, 0 );

		$srcset = wp_calculate_image_srcset( $size_array, $image_url, $image_meta );

		// The srcset should be false.
		$this->assertFalse( $srcset );
	}

	/**
	 * @ticket 34955
	 * @ticket 33641
	 */
	public function test_wp_calculate_image_srcset_ratio_variance() {
		// Mock data for this test.
		$size_array = array( 218, 300 );
		$image_src  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test-768x1055-218x300.png';
		$image_meta = array(
			'width'  => 768,
			'height' => 1055,
			'file'   => '2015/12/test-768x1055.png',
			'sizes'  => array(
				'thumbnail'      => array(
					'file'      => 'test-768x1055-150x150.png',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium'         => array(
					'file'      => 'test-768x1055-218x300.png',
					'width'     => 218,
					'height'    => 300,
					'mime-type' => 'image/png',
				),
				'custom-600'     => array(
					'file'      => 'test-768x1055-600x824.png',
					'width'     => 600,
					'height'    => 824,
					'mime-type' => 'image/png',
				),
				'post-thumbnail' => array(
					'file'      => 'test-768x1055-768x510.png',
					'width'     => 768,
					'height'    => 510,
					'mime-type' => 'image/png',
				),
			),
		);

		$uploads_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/';

		$expected_srcset = $uploads_url . 'test-768x1055-218x300.png 218w, ' .
			$uploads_url . 'test-768x1055-600x824.png 600w, ' .
			$uploads_url . 'test-768x1055.png 768w';

		$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_src, $image_meta ) );
	}

	/**
	 * @ticket 35108
	 * @ticket 33641
	 */
	public function test_wp_calculate_image_srcset_include_src() {
		// Mock data for this test.
		$size_array = array( 2000, 1000 );
		$image_src  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test.png';
		$image_meta = array(
			'width'  => 2000,
			'height' => 1000,
			'file'   => '2015/12/test.png',
			'sizes'  => array(
				'thumbnail'    => array(
					'file'      => 'test-150x150.png',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium'       => array(
					'file'      => 'test-300x150.png',
					'width'     => 300,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium_large' => array(
					'file'      => 'test-768x384.png',
					'width'     => 768,
					'height'    => 384,
					'mime-type' => 'image/png',
				),
				'large'        => array(
					'file'      => 'test-1024x512.png',
					'width'     => 1024,
					'height'    => 512,
					'mime-type' => 'image/png',
				),
			),
		);

		$uploads_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/';

		$expected_srcset = $uploads_url . 'test.png 2000w, ' .
			$uploads_url . 'test-300x150.png 300w, ' .
			$uploads_url . 'test-768x384.png 768w, ' .
			$uploads_url . 'test-1024x512.png 1024w';

		$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_src, $image_meta ) );
	}

	/**
	 * @ticket 35480
	 */
	public function test_wp_calculate_image_srcset_corrupted_image_meta() {
		$size_array = array( 300, 150 );
		$image_src  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test-300x150.png';
		$image_meta = array(
			'width'  => 1600,
			'height' => 800,
			'file'   => '2015/12/test.png',
			'sizes'  => array(
				'thumbnail'    => array(
					'file'      => 'test-150x150.png',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium'       => array(
					'file'      => 'test-300x150.png',
					'width'     => 300,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium_large' => array(
					'file'      => 'test-768x384.png',
					'width'     => 768,
					'height'    => 384,
					'mime-type' => 'image/png',
				),
				'large'        => array(
					'file'      => 'test-1024x512.png',
					'width'     => 1024,
					'height'    => 512,
					'mime-type' => 'image/png',
				),
			),
		);

		$srcset = array(
			300  => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test-300x150.png 300w',
			768  => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test-768x384.png 768w',
			1024 => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test-1024x512.png 1024w',
			1600 => 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test.png 1600w',
		);

		// No sizes array.
		$image_meta1 = $image_meta;
		unset( $image_meta1['sizes'] );
		$this->assertFalse( wp_calculate_image_srcset( $size_array, $image_src, $image_meta1 ) );

		// Sizes is string instead of array; only full size available means no srcset.
		$image_meta2          = $image_meta;
		$image_meta2['sizes'] = '';
		$this->assertFalse( wp_calculate_image_srcset( $size_array, $image_src, $image_meta2 ) );

		// File name is incorrect.
		$image_meta3         = $image_meta;
		$image_meta3['file'] = '/';
		$this->assertFalse( wp_calculate_image_srcset( $size_array, $image_src, $image_meta3 ) );

		// File name is incorrect.
		$image_meta4 = $image_meta;
		unset( $image_meta4['file'] );
		$this->assertFalse( wp_calculate_image_srcset( $size_array, $image_src, $image_meta4 ) );

		// Intermediate size is string instead of array.
		$image_meta5                          = $image_meta;
		$image_meta5['sizes']['medium_large'] = '';
		unset( $srcset[768] );
		$expected_srcset = implode( ', ', $srcset );
		$this->assertSame( $expected_srcset, wp_calculate_image_srcset( $size_array, $image_src, $image_meta5 ) );
	}

	/**
	 * @ticket 36549
	 * @ticket 33641
	 */
	public function test_wp_calculate_image_srcset_with_spaces_in_filenames() {
		// Mock data for this test.
		$image_src  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/test image-300x150.png';
		$image_meta = array(
			'width'  => 3000,
			'height' => 1500,
			'file'   => '2015/12/test image.png',
			'sizes'  => array(
				'thumbnail'    => array(
					'file'      => 'test image-150x150.png',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium'       => array(
					'file'      => 'test image-300x150.png',
					'width'     => 300,
					'height'    => 150,
					'mime-type' => 'image/png',
				),
				'medium_large' => array(
					'file'      => 'test image-768x384.png',
					'width'     => 768,
					'height'    => 384,
					'mime-type' => 'image/png',
				),
				'large'        => array(
					'file'      => 'test image-1024x512.png',
					'width'     => 1024,
					'height'    => 512,
					'mime-type' => 'image/png',
				),
			),
		);

		$uploads_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/2015/12/';

		$expected_srcset = $uploads_url . 'test%20image-300x150.png 300w, ' .
			$uploads_url . 'test%20image-768x384.png 768w, ' .
			$uploads_url . 'test%20image-1024x512.png 1024w';

		$this->assertSame( $expected_srcset, wp_calculate_image_srcset( array( 300, 150 ), $image_src, $image_meta ) );
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_get_attachment_image_srcset() {
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = array( $image_meta['width'], $image_meta['height'] ); // Full size.

		$srcset = wp_get_attachment_image_srcset( self::$large_id, $size_array, $image_meta );

		$year_month  = gmdate( 'Y/m' );
		$uploads_dir = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/';

		// Set up test cases for all expected size names.
		$intermediates = array( 'medium', 'medium_large', 'large', 'full' );

		foreach ( $_wp_additional_image_sizes as $name => $additional_size ) {
			if ( ! $_wp_additional_image_sizes[ $name ]['crop'] || 0 === $_wp_additional_image_sizes[ $name ]['height'] ) {
				$intermediates[] = $name;
			}
		}

		$expected = '';

		foreach ( $image_meta['sizes'] as $name => $size ) {
			// Allow the sizes that should be included so we pick up 'medium_large' in 4.4.
			if ( in_array( $name, $intermediates, true ) ) {
				$expected .= $uploads_dir . $year_month . '/' . $size['file'] . ' ' . $size['width'] . 'w, ';
			}
		}

		$expected .= $uploads_dir . $image_meta['file'] . ' ' . $image_meta['width'] . 'w';

		$expected_srcset = $this->src_first( $expected, $uploads_dir . $image_meta['file'], $size_array[0] );

		$this->assertSame( $expected_srcset, $srcset );
	}

	/**
	 * @ticket 33641
	 */
	public function test_wp_get_attachment_image_srcset_single_srcset() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = array( 150, 150 );
		/*
		 * In our tests, thumbnails will only return a single srcset candidate,
		 * so we shouldn't return a srcset value in order to avoid unneeded markup.
		 */
		$sizes = wp_get_attachment_image_srcset( self::$large_id, $size_array, $image_meta );

		$this->assertFalse( $sizes );
	}

	/**
	 * @ticket 33641
	 */
	public function test_wp_get_attachment_image_srcset_invalidsize() {
		$image_meta    = wp_get_attachment_metadata( self::$large_id );
		$invalid_size  = 'nailthumb';
		$original_size = array( 1600, 1200 );

		$srcset = wp_get_attachment_image_srcset( self::$large_id, $invalid_size, $image_meta );

		// Expect a srcset for the original full size image to be returned.
		$expected = wp_get_attachment_image_srcset( self::$large_id, $original_size, $image_meta );

		$this->assertSame( $expected, $srcset );
	}

	/**
	 * @ticket 33641
	 */
	public function test_wp_get_attachment_image_sizes() {
		// Test sizes against the default WP sizes.
		$intermediates = array( 'thumbnail', 'medium', 'medium_large', 'large' );

		// Make sure themes aren't filtering the sizes array.
		remove_all_filters( 'wp_calculate_image_sizes' );

		foreach ( $intermediates as $int_size ) {
			$image = wp_get_attachment_image_src( self::$large_id, $int_size );

			$expected = '(max-width: ' . $image[1] . 'px) 100vw, ' . $image[1] . 'px';
			$sizes    = wp_get_attachment_image_sizes( self::$large_id, $int_size );

			$this->assertSame( $expected, $sizes );
		}
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_calculate_image_sizes() {
		// Test sizes against the default WP sizes.
		$intermediates = array( 'thumbnail', 'medium', 'medium_large', 'large' );
		$image_meta    = wp_get_attachment_metadata( self::$large_id );

		// Make sure themes aren't filtering the sizes array.
		remove_all_filters( 'wp_calculate_image_sizes' );

		foreach ( $intermediates as $int_size ) {
			$size_array             = $this->get_image_size_array_from_meta( $image_meta, $int_size );
			$image_src              = $image_meta['sizes'][ $int_size ]['file'];
			list( $width, $height ) = $size_array;

			$expected = '(max-width: ' . $width . 'px) 100vw, ' . $width . 'px';
			$sizes    = wp_calculate_image_sizes( $size_array, $image_src, $image_meta );

			$this->assertSame( $expected, $sizes );
		}
	}

	/**
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_filter_content_tags_srcset_sizes() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = $this->get_image_size_array_from_meta( $image_meta, 'medium' );

		$srcset = sprintf( 'srcset="%s"', wp_get_attachment_image_srcset( self::$large_id, $size_array, $image_meta ) );
		$sizes  = sprintf( 'sizes="%s"', wp_get_attachment_image_sizes( self::$large_id, $size_array, $image_meta ) );

		// Function used to build HTML for the editor.
		$img                  = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img_no_size_in_class = str_replace( 'size-', '', $img );
		$img_no_width_height  = str_replace( ' width="' . $size_array[0] . '"', '', $img );
		$img_no_width_height  = str_replace( ' height="' . $size_array[1] . '"', '', $img_no_width_height );
		$img_no_size_id       = str_replace( 'wp-image-', 'id-', $img );
		$img_with_sizes_attr  = str_replace( '<img ', '<img sizes="99vw" ', $img );
		$img_xhtml            = str_replace( ' />', '/>', $img );
		$img_html5            = str_replace( ' />', '>', $img );

		// Manually add srcset and sizes to the markup from get_image_tag().
		$respimg                  = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img );
		$respimg_no_size_in_class = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_no_size_in_class );
		$respimg_no_width_height  = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_no_width_height );
		$respimg_with_sizes_attr  = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . '>', $img_with_sizes_attr );
		$respimg_xhtml            = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_xhtml );
		$respimg_html5            = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_html5 );

		$content = '
			<p>Image, standard. Should have srcset and sizes.</p>
			%1$s

			<p>Image, no size class. Should have srcset and sizes.</p>
			%2$s

			<p>Image, no width and height attributes. Should have srcset and sizes (from matching the file name).</p>
			%3$s

			<p>Image, no attachment ID class. Should NOT have srcset and sizes.</p>
			%4$s

			<p>Image, with sizes attribute. Should NOT have two sizes attributes.</p>
			%5$s

			<p>Image, XHTML 1.0 style (no space before the closing slash). Should have srcset and sizes.</p>
			%6$s

			<p>Image, HTML 5.0 style. Should have srcset and sizes.</p>
			%7$s';

		$content_unfiltered = sprintf(
			$content,
			$img,
			$img_no_size_in_class,
			$img_no_width_height,
			$img_no_size_id,
			$img_with_sizes_attr,
			$img_xhtml,
			$img_html5
		);

		$content_filtered = sprintf(
			$content,
			$respimg,
			$respimg_no_size_in_class,
			$respimg_no_width_height,
			$img_no_size_id,
			$respimg_with_sizes_attr,
			$respimg_xhtml,
			$respimg_html5
		);
		$content_filtered = wp_img_tag_add_decoding_attr( $content_filtered, 'the_content' );

		// Do not add width, height, and loading.
		add_filter( 'wp_img_tag_add_width_and_height_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_loading_attr', '__return_false' );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );

		remove_filter( 'wp_img_tag_add_width_and_height_attr', '__return_false' );
		remove_filter( 'wp_img_tag_add_loading_attr', '__return_false' );
	}

	/**
	 * When rendering attributes for responsive images,
	 * we rely on the 'wp-image-*' class to find the image by ID.
	 * The class name may not be consistent with attachment IDs in DB when
	 * working with imported content or when a user has edited
	 * the 'src' attribute manually. To avoid incorrect images
	 * being displayed, ensure we don't add attributes in this case.
	 *
	 * @ticket 34898
	 * @ticket 33641
	 */
	public function test_wp_filter_content_tags_srcset_sizes_wrong() {
		$img = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img = wp_img_tag_add_loading_attr( $img, 'test' );
		$img = wp_img_tag_add_decoding_attr( $img, 'the_content' );

		// Replace the src URL.
		$image_wrong_src = preg_replace( '|src="[^"]+"|', 'src="http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/foo.jpg"', $img );

		$this->assertSame( $image_wrong_src, wp_filter_content_tags( $image_wrong_src ) );
	}

	/**
	 * @ticket 33641
	 */
	public function test_wp_filter_content_tags_srcset_sizes_with_preexisting_srcset() {
		// Generate HTML and add a dummy srcset attribute.
		$img = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img = wp_img_tag_add_loading_attr( $img, 'test' );
		$img = wp_img_tag_add_decoding_attr( $img, 'the_content' );
		$img = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . 'srcset="image2x.jpg 2x">', $img );

		// The content filter should return the image unchanged.
		$this->assertSame( $img, wp_filter_content_tags( $img ) );
	}

	/**
	 * @ticket 55347
	 */
	public function test_wp_filter_content_tags_has_filter() {
		$filter = new MockAction();
		add_filter( 'wp_content_img_tag', array( &$filter, 'filter' ) );
		$img_tag_1 = get_image_tag( self::$large_id, '', '', '', 'medium' );

		wp_filter_content_tags( $img_tag_1 );
		$this->assertSame( 1, $filter->get_call_count() );
	}

	/**
	 * @ticket 55510
	 * @covers ::wp_filter_content_tags
	 */
	public function test_wp_filter_content_tags_handles_duplicate_img_and_iframe_tags_once() {
		$img     = get_image_tag( self::$large_id, '', '', '', 'large' );
		$iframe  = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$content = "$img\n$img\n$iframe\n$iframe";

		// Record how often one of the available img and iframe filters is run.
		// Both images and iframes support lazy-loading, so that's why this is used here.
		$img_filter = new MockAction();
		add_filter( 'wp_img_tag_add_loading_attr', array( &$img_filter, 'filter' ) );
		$iframe_filter = new MockAction();
		add_filter( 'wp_iframe_tag_add_loading_attr', array( &$iframe_filter, 'filter' ) );

		// Ensure the img and iframe filters only ran once because the content is a single duplicated img tag and a
		// single duplicate iframe tag.
		wp_filter_content_tags( $content );
		$this->assertSame( 1, $img_filter->get_call_count() );
		$this->assertSame( 1, $iframe_filter->get_call_count() );
	}

	/**
	 * @ticket 55510
	 * @covers ::wp_filter_content_tags
	 */
	public function test_wp_filter_content_tags_filter_with_identical_image_tags_custom_attributes() {
		$img     = get_image_tag( self::$large_id, '', '', '', 'large' );
		$img     = str_replace( '<img ', '<img srcset="custom" sizes="custom" loading="custom" decoding="custom"', $img );
		$content = "$img\n$img";

		add_filter(
			'wp_content_img_tag',
			function ( $filtered_image ) {
				return "<span>$filtered_image</span>";
			}
		);

		// Ensure there is no duplicate <span> wrapping the image.
		$this->assertStringNotContainsString( '<span><span><img ', wp_filter_content_tags( $content ) );
	}

	/**
	 * @ticket 55510
	 * @covers ::wp_filter_content_tags
	 */
	public function test_wp_filter_content_tags_filter_with_identical_image_tags_disabled_core_filters() {
		$img     = get_image_tag( self::$large_id, '', '', '', 'large' );
		$content = "$img\n$img";

		add_filter( 'wp_img_tag_add_loading_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_width_and_height_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_decoding_attr', '__return_false' );

		add_filter(
			'wp_content_img_tag',
			function ( $filtered_image ) {
				return "<span>$filtered_image</span>";
			}
		);

		// Ensure the output has both instances of the image wrapped with a single <span>.
		$this->assertSame( "<span>$img</span>\n<span>$img</span>", wp_filter_content_tags( $content ) );
	}

	/**
	 * @ticket 33641
	 * @ticket 34528
	 */
	public function test_wp_calculate_image_srcset_animated_gifs() {
		// Mock meta for an animated gif.
		$image_meta = array(
			'width'  => 1200,
			'height' => 600,
			'file'   => 'animated.gif',
			'sizes'  => array(
				'thumbnail' => array(
					'file'      => 'animated-150x150.gif',
					'width'     => 150,
					'height'    => 150,
					'mime-type' => 'image/gif',
				),
				'medium'    => array(
					'file'      => 'animated-300x150.gif',
					'width'     => 300,
					'height'    => 150,
					'mime-type' => 'image/gif',
				),
				'large'     => array(
					'file'      => 'animated-1024x512.gif',
					'width'     => 1024,
					'height'    => 512,
					'mime-type' => 'image/gif',
				),
			),
		);

		$full_src  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_meta['file'];
		$large_src = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_meta['sizes']['large']['file'];

		// Test with soft resized size array.
		$size_array = array( 900, 450 );

		// Full size GIFs should not return a srcset.
		$this->assertFalse( wp_calculate_image_srcset( $size_array, $full_src, $image_meta ) );
		// Intermediate sized GIFs should not include the full size in the srcset.
		$this->assertStringNotContainsString( $full_src, wp_calculate_image_srcset( $size_array, $large_src, $image_meta ) );
	}

	/**
	 * @ticket 35045
	 * @ticket 33641
	 * @requires function imagejpeg
	 */
	public function test_wp_filter_content_tags_schemes() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = $this->get_image_size_array_from_meta( $image_meta, 'medium' );

		$srcset = sprintf( 'srcset="%s"', wp_get_attachment_image_srcset( self::$large_id, $size_array, $image_meta ) );
		$sizes  = sprintf( 'sizes="%s"', wp_get_attachment_image_sizes( self::$large_id, $size_array, $image_meta ) );

		// Build HTML for the editor.
		$img          = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img          = wp_img_tag_add_loading_attr( $img, 'test' );
		$img_https    = str_replace( 'http://', 'https://', $img );
		$img_relative = str_replace( 'http://', '//', $img );

		// Manually add srcset and sizes to the markup from get_image_tag().
		$respimg          = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img );
		$respimg_https    = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_https );
		$respimg_relative = preg_replace( '|<img ([^>]+)>|', '<img $1 ' . $srcset . ' ' . $sizes . '>', $img_relative );

		$content = '
			<p>Image, http: protocol. Should have srcset and sizes.</p>
			%1$s

			<p>Image, https: protocol. Should have srcset and sizes.</p>
			%2$s

			<p>Image, protocol-relative. Should have srcset and sizes.</p>
			%3$s';

		$unfiltered = sprintf(
			$content,
			$img,
			$img_https,
			$img_relative
		);

		$expected = sprintf(
			$content,
			$respimg,
			$respimg_https,
			$respimg_relative
		);
		$expected = wp_img_tag_add_decoding_attr( $expected, 'the_content' );

		$actual = wp_filter_content_tags( $unfiltered );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 34945
	 * @ticket 33641
	 */
	public function test_wp_get_attachment_image_with_https_on() {
		// Mock meta for the image.
		$image_meta = array(
			'width'  => 1200,
			'height' => 600,
			'file'   => 'test.jpg',
			'sizes'  => array(
				'thumbnail' => array(
					'file'   => 'test-150x150.jpg',
					'width'  => 150,
					'height' => 150,
				),
				'medium'    => array(
					'file'   => 'test-300x150.jpg',
					'width'  => 300,
					'height' => 150,
				),
				'large'     => array(
					'file'   => 'test-1024x512.jpg',
					'width'  => 1024,
					'height' => 512,
				),
			),
		);

		// Test using the large file size.
		$size_array = array( 1024, 512 );
		$image_url  = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $image_meta['sizes']['large']['file'];

		$_SERVER['HTTPS'] = 'on';

		$uploads_url = 'https://' . WP_TESTS_DOMAIN . '/wp-content/uploads/';

		$expected = $uploads_url . 'test-1024x512.jpg 1024w, ' .
			$uploads_url . 'test-300x150.jpg 300w, ' .
			$uploads_url . 'test.jpg 1200w';

		$actual = wp_calculate_image_srcset( $size_array, $image_url, $image_meta );

		$this->assertSame( $expected, $actual );
	}

	/**
	 * @ticket 36084
	 */
	public function test_get_image_send_to_editor_defaults() {
		$id      = self::$large_id;
		$caption = '';
		$title   = 'A test title value.';
		$align   = 'left';

		// Calculate attachment data (default is medium).
		$attachment = wp_get_attachment_image_src( $id, 'medium' );

		$html     = '<img src="%1$s" alt="" width="%2$d" height="%3$d" class="align%4$s size-medium wp-image-%5$d">';
		$expected = sprintf(
			$html,
			$attachment[0],
			$attachment[1],
			$attachment[2],
			$align,
			$id
		);

		$this->assertSame( $expected, get_image_send_to_editor( $id, $caption, $title, $align ) );

		$this->assertSame( $expected, get_image_send_to_editor( $id, $caption, $title, $align ) );
	}

	/**
	 * @ticket 36084
	 */
	public function test_get_image_send_to_editor_defaults_with_optional_params() {
		$id      = self::$large_id;
		$caption = 'A test caption.';
		$title   = 'A test title value.';
		$align   = 'left';
		$url     = get_permalink( $id );
		$rel     = true;
		$size    = 'thumbnail';
		$alt     = 'An example alt value.';

		// Calculate attachment data.
		$attachment = wp_get_attachment_image_src( $id, $size );

		$html = '<a href="%1$s" rel="%2$s"><img src="%3$s" alt="%4$s" width="%5$d" height="%6$d" class="size-%8$s wp-image-%9$d"></a>';
		$html = '[caption id="attachment_%9$d" align="align%7$s" width="%5$d"]' . $html . ' %10$s[/caption]';

		$expected = sprintf(
			$html,
			$url,
			'attachment wp-att-' . $id,
			$attachment[0],
			$alt,
			$attachment[1],
			$attachment[2],
			$align,
			$size,
			$id,
			$caption
		);

		$this->assertSame( $expected, get_image_send_to_editor( $id, $caption, $title, $align, $url, $rel, $size, $alt ) );
	}

	/**
	 * @ticket 36084
	 */
	public function test_get_image_send_to_editor_defaults_no_caption_no_rel() {
		$id      = self::$large_id;
		$caption = '';
		$title   = 'A test title value.';
		$align   = 'left';
		$url     = get_permalink( $id );
		$rel     = '';
		$size    = 'thumbnail';
		$alt     = 'An example alt value.';

		// Calculate attachment data.
		$attachment = wp_get_attachment_image_src( $id, $size );

		$html = '<a href="%1$s"><img src="%2$s" alt="%3$s" width="%4$d" height="%5$d" class="align%6$s size-%7$s wp-image-%8$d"></a>';

		$expected = sprintf(
			$html,
			$url,
			$attachment[0],
			$alt,
			$attachment[1],
			$attachment[2],
			$align,
			$size,
			$id
		);

		$this->assertSame( $expected, get_image_send_to_editor( $id, $caption, $title, $align, $url, $rel, $size, $alt ) );
	}

	/**
	 * Tests if wp_get_attachment_image() uses wp_get_attachment_metadata().
	 *
	 * In this way, the meta data can be filtered using the filter
	 * `wp_get_attachment_metadata`.
	 *
	 * The test checks if the image size that is added in the filter is
	 * used in the output of `wp_get_attachment_image()`.
	 *
	 * @ticket 36246
	 * @requires function imagejpeg
	 */
	public function test_wp_get_attachment_image_should_use_wp_get_attachment_metadata() {
		add_filter( 'wp_get_attachment_metadata', array( $this, 'filter_36246' ), 10, 2 );

		remove_all_filters( 'wp_calculate_image_sizes' );

		$basename    = wp_basename( self::$large_filename, '.jpg' );
		$year_month  = gmdate( 'Y/m' );
		$uploads_url = 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/' . $year_month . '/';

		$expected = '<img width="999" height="999" ' .
			'src="' . $uploads_url . 'test-image-testsize-999x999.jpg" ' .
			'class="attachment-testsize size-testsize" alt="" decoding="async" loading="lazy" ' .
			'srcset="' . $uploads_url . 'test-image-testsize-999x999.jpg 999w, ' . $uploads_url . $basename . '-150x150.jpg 150w" ' .
			'sizes="(max-width: 999px) 100vw, 999px">';

		$actual = wp_get_attachment_image( self::$large_id, 'testsize' );

		remove_filter( 'wp_get_attachment_metadata', array( $this, 'filter_36246' ) );

		$this->assertSame( $expected, $actual );
	}

	public function filter_36246( $data, $attachment_id ) {
		$data['sizes']['testsize'] = array(
			'file'      => 'test-image-testsize-999x999.jpg',
			'width'     => 999,
			'height'    => 999,
			'mime-type' => 'image/jpg',
		);
		return $data;
	}

	/**
	 * @ticket 50679
	 */
	public function test_wp_get_attachment_metadata_should_return_false_if_no_attachment() {
		$post_id = self::factory()->post->create();
		$data    = wp_get_attachment_metadata( $post_id );
		$this->assertFalse( $data );
	}

	/**
	 * @ticket 37813
	 */
	public function test_return_type_when_inserting_attachment_with_error_in_data() {
		$data = array(
			'post_status'  => 'publish',
			'post_content' => 'Attachment content',
			'post_title'   => 'Attachment Title',
			'post_date'    => '2012-02-30 00:00:00',
		);

		$attachment_id = wp_insert_attachment( $data, '', 0, true );
		$this->assertWPError( $attachment_id );
		$this->assertSame( 'invalid_date', $attachment_id->get_error_code() );

		$attachment_id = wp_insert_attachment( $data, '', 0 );
		$this->assertSame( 0, $attachment_id );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_get_media_creation_timestamp_video_asf() {
		$metadata = array(
			'fileformat' => 'asf',
			'asf'        => array(
				'file_properties_object' => array(
					'creation_date_unix' => 123,
				),
			),
		);

		$this->assertSame( 123, wp_get_media_creation_timestamp( $metadata ) );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_get_media_creation_timestamp_video_matroska() {
		$metadata = array(
			'fileformat' => 'matroska',
			'matroska'   => array(
				'comments' => array(
					'creation_time' => array(
						'2015-12-24T17:40:09Z',
					),
				),
			),
		);

		$this->assertSame( 1450978809, wp_get_media_creation_timestamp( $metadata ) );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_get_media_creation_timestamp_video_quicktime() {
		$metadata = array(
			'fileformat' => 'quicktime',
			'quicktime'  => array(
				'moov' => array(
					'subatoms' => array(
						array(
							'creation_time_unix' => 1450978805,
						),
					),
				),
			),
		);

		$this->assertSame( 1450978805, wp_get_media_creation_timestamp( $metadata ) );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_get_media_creation_timestamp_video_webm() {
		$metadata = array(
			'fileformat' => 'webm',
			'matroska'   => array(
				'info' => array(
					array(
						'DateUTC_unix' => 1265680539,
					),
				),
			),
		);

		$this->assertSame( 1265680539, wp_get_media_creation_timestamp( $metadata ) );
	}

	/**
	 * Test created timestamp is properly read from an MP4 file.
	 *
	 * This MP4 video file has an AAC audio track, so it can be used to test
	 *`wp_read_audio_metadata()`.
	 *
	 * @ticket 42017
	 */
	public function test_wp_read_audio_metadata_adds_creation_date_with_mp4() {
		$video    = DIR_TESTDATA . '/uploads/small-video.mp4';
		$metadata = wp_read_audio_metadata( $video );

		$this->assertSame( 1269120551, $metadata['created_timestamp'] );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_read_video_metadata_adds_creation_date_with_quicktime() {
		$video    = DIR_TESTDATA . '/uploads/small-video.mov';
		$metadata = wp_read_video_metadata( $video );

		$this->assertSame( 1269120551, $metadata['created_timestamp'] );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_read_video_metadata_adds_creation_date_with_mp4() {
		$video    = DIR_TESTDATA . '/uploads/small-video.mp4';
		$metadata = wp_read_video_metadata( $video );

		$this->assertSame( 1269120551, $metadata['created_timestamp'] );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_read_video_metadata_adds_creation_date_with_mkv() {
		$video    = DIR_TESTDATA . '/uploads/small-video.mkv';
		$metadata = wp_read_video_metadata( $video );

		$this->assertSame( 1269120551, $metadata['created_timestamp'] );
	}

	/**
	 * @ticket 35218
	 */
	public function test_wp_read_video_metadata_adds_creation_date_with_webm() {
		$video    = DIR_TESTDATA . '/uploads/small-video.webm';
		$metadata = wp_read_video_metadata( $video );

		$this->assertSame( 1269120551, $metadata['created_timestamp'] );
	}

	/**
	 * @ticket 10752
	 */
	public function test_media_handle_upload_uses_post_parent_for_directory_date() {
		$iptc_file = DIR_TESTDATA . '/images/test-image-iptc.jpg';

		// Make a copy of this file as it gets moved during the file upload.
		$tmp_name = wp_tempnam( $iptc_file );

		copy( $iptc_file, $tmp_name );

		$_FILES['upload'] = array(
			'tmp_name' => $tmp_name,
			'name'     => 'test-image-iptc.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $iptc_file ),
		);

		$parent_id = self::factory()->post->create( array( 'post_date' => '2010-01-01' ) );

		$post_id = media_handle_upload(
			'upload',
			$parent_id,
			array(),
			array(
				'action'    => 'test_iptc_upload',
				'test_form' => false,
			)
		);

		unset( $_FILES['upload'] );

		$url = wp_get_attachment_url( $post_id );

		$uploads_dir = wp_upload_dir( '2010/01' );

		$expected = $uploads_dir['url'] . '/test-image-iptc.jpg';

		// Clean up.
		wp_delete_attachment( $post_id, true );
		wp_delete_post( $parent_id, true );

		$this->assertSame( $expected, $url );
	}

	/**
	 * @ticket 10752
	 */
	public function test_media_handle_upload_ignores_page_parent_for_directory_date() {
		$iptc_file = DIR_TESTDATA . '/images/test-image-iptc.jpg';

		// Make a copy of this file as it gets moved during the file upload.
		$tmp_name = wp_tempnam( $iptc_file );

		copy( $iptc_file, $tmp_name );

		$_FILES['upload'] = array(
			'tmp_name' => $tmp_name,
			'name'     => 'test-image-iptc.jpg',
			'type'     => 'image/jpeg',
			'error'    => 0,
			'size'     => filesize( $iptc_file ),
		);

		$parent_id = self::factory()->post->create(
			array(
				'post_date' => '2010-01-01',
				'post_type' => 'page',
			)
		);
		$parent    = get_post( $parent_id );

		$post_id = media_handle_upload(
			'upload',
			$parent_id,
			array(),
			array(
				'action'    => 'test_iptc_upload',
				'test_form' => false,
			)
		);

		unset( $_FILES['upload'] );

		$url = wp_get_attachment_url( $post_id );

		$uploads_dir = wp_upload_dir( current_time( 'mysql' ) );

		$expected = $uploads_dir['url'] . '/test-image-iptc.jpg';

		// Clean up.
		wp_delete_attachment( $post_id, true );
		wp_delete_post( $parent_id, true );

		$this->assertSame( $expected, $url );
	}

	/**
	 * @ticket 50367
	 * @requires function imagejpeg
	 */
	public function test_wp_filter_content_tags_width_height() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = $this->get_image_size_array_from_meta( $image_meta, 'medium' );

		$img                 = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img_no_width_height = str_replace( ' width="' . $size_array[0] . '"', '', $img );
		$img_no_width_height = str_replace( ' height="' . $size_array[1] . '"', '', $img_no_width_height );
		$img_no_width        = str_replace( ' width="' . $size_array[0] . '"', '', $img );
		$img_no_height       = str_replace( ' height="' . $size_array[1] . '"', '', $img );

		$hwstring = image_hwstring( $size_array[0], $size_array[1] );

		// Manually add width and height to the markup from get_image_tag().
		$respimg_no_width_height = str_replace( '<img ', '<img ' . $hwstring, $img_no_width_height );

		$content = '
			<p>Image, with width and height. Should NOT be modified.</p>
			%1$s

			<p>Image, no width and height attributes. Should have width, height, srcset and sizes (from matching the file name).</p>
			%2$s

			<p>Image, no width but height attribute. Should NOT be modified.</p>
			%3$s

			<p>Image, no height but width attribute. Should NOT be modified.</p>
			%4$s';

		$content_unfiltered = sprintf(
			$content,
			$img,
			$img_no_width_height,
			$img_no_width,
			$img_no_height
		);

		$content_filtered = sprintf(
			$content,
			$img,
			$respimg_no_width_height,
			$img_no_width,
			$img_no_height
		);
		$content_filtered = wp_img_tag_add_decoding_attr( $content_filtered, 'the_content' );

		// Do not add loading, srcset, and sizes.
		add_filter( 'wp_img_tag_add_loading_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );

		remove_filter( 'wp_img_tag_add_loading_attr', '__return_false' );
		remove_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
	}

	/**
	 * @ticket 44427
	 * @ticket 50367
	 * @ticket 50756
	 * @requires function imagejpeg
	 */
	public function test_wp_filter_content_tags_loading_lazy() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$size_array = $this->get_image_size_array_from_meta( $image_meta, 'medium' );

		$img                    = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img_xhtml              = str_replace( ' />', '/>', $img );
		$img_html5              = str_replace( ' />', '>', $img );
		$img_no_width_height    = str_replace( ' width="' . $size_array[0] . '"', '', $img );
		$img_no_width_height    = str_replace( ' height="' . $size_array[1] . '"', '', $img_no_width_height );
		$iframe                 = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$iframe_no_width_height = '<iframe src="https://www.example.com"></iframe>';

		$lazy_img       = wp_img_tag_add_loading_attr( $img, 'test' );
		$lazy_img_xhtml = wp_img_tag_add_loading_attr( $img_xhtml, 'test' );
		$lazy_img_html5 = wp_img_tag_add_loading_attr( $img_html5, 'test' );
		$lazy_iframe    = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		// The following should not be modified because there already is a 'loading' attribute.
		$img_eager    = str_replace( '">', '" loading="eager">', $img );
		$iframe_eager = str_replace( '">', '" loading="eager">', $iframe );

		$content = '
			<p>Image, standard.</p>
			%1$s
			<p>Image, XHTML 1.0 style (no space before the closing slash).</p>
			%2$s
			<p>Image, HTML 5.0 style.</p>
			%3$s
			<p>Image, with pre-existing "loading" attribute. Should not be modified.</p>
			%4$s
			<p>Image, without dimension attributes. Should not be modified.</p>
			%5$s
			<p>Iframe, standard.</p>
			%6$s
			<p>Iframe, with pre-existing "loading" attribute. Should not be modified.</p>
			%7$s
			<p>Iframe, without dimension attributes. Should not be modified.</p>
			%8$s';

		$content_unfiltered = sprintf(
			$content,
			$img,
			$img_xhtml,
			$img_html5,
			$img_eager,
			$img_no_width_height,
			$iframe,
			$iframe_eager,
			$iframe_no_width_height
		);

		$content_filtered = sprintf(
			$content,
			$lazy_img,
			$lazy_img_xhtml,
			$lazy_img_html5,
			$img_eager,
			$img_no_width_height,
			$lazy_iframe,
			$iframe_eager,
			$iframe_no_width_height
		);
		$content_filtered = wp_img_tag_add_decoding_attr( $content_filtered, 'the_content' );

		// Do not add width, height, srcset, and sizes.
		add_filter( 'wp_img_tag_add_width_and_height_attr', '__return_false' );
		add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );

		remove_filter( 'wp_img_tag_add_width_and_height_attr', '__return_false' );
		remove_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
	}

	/**
	 * @ticket 44427
	 * @ticket 50756
	 */
	public function test_wp_filter_content_tags_loading_lazy_opted_in() {
		$img         = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$lazy_img    = wp_img_tag_add_loading_attr( $img, 'test' );
		$lazy_img    = wp_img_tag_add_decoding_attr( $lazy_img, 'the_content' );
		$iframe      = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$lazy_iframe = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$content = '
			<p>Image, standard.</p>
			%1$s
			<p>Iframe, standard.</p>
			%2$s';

		$content_unfiltered = sprintf( $content, $img, $iframe );
		$content_filtered   = sprintf( $content, $lazy_img, $lazy_iframe );

		// Do not add srcset and sizes while testing.
		add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );

		// Enable globally for all tags.
		add_filter( 'wp_lazy_loading_enabled', '__return_true' );

		$this->assertSame( $content_filtered, wp_filter_content_tags( $content_unfiltered ) );
		remove_filter( 'wp_lazy_loading_enabled', '__return_true' );
		remove_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
	}

	/**
	 * @ticket 44427
	 * @ticket 50756
	 */
	public function test_wp_filter_content_tags_loading_lazy_opted_out() {
		$img    = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img    = wp_img_tag_add_decoding_attr( $img, 'the_content' );
		$iframe = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';

		$content = '
			<p>Image, standard.</p>
			%1$s
			<p>Iframe, standard.</p>
			%2$s';
		$content = sprintf( $content, $img, $iframe );

		// Do not add srcset and sizes while testing.
		add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );

		// Disable globally for all tags.
		add_filter( 'wp_lazy_loading_enabled', '__return_false' );

		$this->assertSame( $content, wp_filter_content_tags( $content ) );
		remove_filter( 'wp_lazy_loading_enabled', '__return_false' );
		remove_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
	}

	/**
	 * @ticket 44427
	 * @ticket 50367
	 */
	public function test_wp_img_tag_add_loading_attr() {
		$img = '<img src="example.png" alt=" width="300" height="225">';
		$img = wp_img_tag_add_loading_attr( $img, 'test' );

		$this->assertStringContainsString( ' loading="lazy"', $img );
	}

	/**
	 * @ticket 44427
	 * @ticket 50367
	 */
	public function test_wp_img_tag_add_loading_attr_without_src() {
		$img = '<img alt=" width="300" height="225">';
		$img = wp_img_tag_add_loading_attr( $img, 'test' );

		$this->assertStringNotContainsString( ' loading=', $img );
	}

	/**
	 * @ticket 44427
	 * @ticket 50367
	 */
	public function test_wp_img_tag_add_loading_attr_with_single_quotes() {
		$img = "<img src='example.png' alt=' width='300' height='225'>";
		$img = wp_img_tag_add_loading_attr( $img, 'test' );

		$this->assertStringNotContainsString( ' loading=', $img );

		// Test specifically that the attribute is not there with double-quotes,
		// to avoid regressions.
		$this->assertStringNotContainsString( ' loading="lazy"', $img );
	}

	/**
	 * @ticket 44427
	 * @ticket 50425
	 */
	public function test_wp_img_tag_add_loading_attr_opt_out() {
		$img = '<img src="example.png" alt=" width="300" height="225">';
		add_filter( 'wp_img_tag_add_loading_attr', '__return_false' );

		$this->assertStringNotContainsString( ' loading=', $img );
	}

	/**
	 * Test that decoding="async" is not applied to img tags with single quotes.
	 *
	 * @ticket 56969
	 */
	public function test_wp_img_tag_add_decoding_attr_with_single_quotes() {
		$img = "<img src='example.png' alt='' width='300' height='225'>";
		$img = wp_img_tag_add_decoding_attr( $img, 'test' );
		$this->assertStringNotContainsString( ' decoding="async"', $img );
	}

	/**
	 * Test that decoding="async" is not applied to img tags inside JSON.
	 *
	 * @ticket 56969
	 */
	public function test_decoding_async_not_applied_to_json() {
		$content = '{"image": "<img src=\"example.png\" alt=\"\" width=\"300\" height=\"225\">"}';
		$content = wp_filter_content_tags( $content );
		$this->assertStringNotContainsString( ' decoding="async"', $content );
	}

	/**
	 * @ticket 50756
	 */
	public function test_wp_iframe_tag_add_loading_attr() {
		$iframe = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$iframe = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$this->assertStringContainsString( ' loading="lazy"', $iframe );
	}

	/**
	 * @ticket 50756
	 */
	public function test_wp_iframe_tag_add_loading_attr_without_src() {
		$iframe = '<iframe width="640" height="360"></iframe>';
		$iframe = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$this->assertStringNotContainsString( ' loading=', $iframe );
	}

	/**
	 * @ticket 50756
	 */
	public function test_wp_iframe_tag_add_loading_attr_with_single_quotes() {
		$iframe = "<iframe src='https://www.example.com' width='640' height='360'></iframe>";
		$iframe = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$this->assertStringNotContainsString( ' loading=', $iframe );

		// Test specifically that the attribute is not there with double-quotes,
		// to avoid regressions.
		$this->assertStringNotContainsString( ' loading="lazy"', $iframe );
	}

	/**
	 * @ticket 50756
	 */
	public function test_wp_iframe_tag_add_loading_attr_opt_out() {
		$iframe = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		add_filter( 'wp_iframe_tag_add_loading_attr', '__return_false' );
		$iframe = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$this->assertStringNotContainsString( ' loading=', $iframe );
	}

	/**
	 * @ticket 52768
	 */
	public function test_wp_iframe_tag_add_loading_attr_skip_wp_embed() {
		$iframe   = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$fallback = '<blockquote>Fallback content.</blockquote>';
		$iframe   = wp_filter_oembed_result( $fallback . $iframe, (object) array( 'type' => 'rich' ), 'https://www.example.com' );
		$iframe   = wp_iframe_tag_add_loading_attr( $iframe, 'test' );

		$this->assertStringNotContainsString( ' loading=', $iframe );
	}

	/**
	 * @ticket 44427
	 * @ticket 50425
	 */
	public function test_wp_get_attachment_image_loading() {
		$img = wp_get_attachment_image( self::$large_id );

		$this->assertStringContainsString( ' loading="lazy"', $img );
	}

	/**
	 * @ticket 44427
	 * @ticket 50425
	 */
	public function test_wp_get_attachment_image_loading_opt_out() {
		add_filter( 'wp_lazy_loading_enabled', '__return_false' );
		$img = wp_get_attachment_image( self::$large_id );

		// There should not be any loading attribute in this case.
		$this->assertStringNotContainsString( ' loading=', $img );
	}

	/**
	 * @ticket 44427
	 * @ticket 50425
	 */
	public function test_wp_get_attachment_image_loading_opt_out_individual() {
		// The default is already tested above, the filter below ensures that
		// lazy-loading is definitely enabled globally for images.
		add_filter( 'wp_lazy_loading_enabled', '__return_true' );

		$img = wp_get_attachment_image( self::$large_id, 'thumbnail', false, array( 'loading' => false ) );

		// There should not be any loading attribute in this case.
		$this->assertStringNotContainsString( ' loading=', $img );
	}

	/**
	 * @ticket 57086
	 *
	 * @dataProvider data_wp_get_attachment_image_decoding_attr
	 *
	 * @covers ::wp_get_attachment_image
	 */
	public function test_wp_get_attachment_image_decoding_attr( $decoding, $expected ) {
		if ( 'no value' === $decoding ) {
			$image = wp_get_attachment_image( self::$large_id, 'thumbnail', false, array() );
		} else {
			$image = wp_get_attachment_image( self::$large_id, 'thumbnail', false, array( 'decoding' => $decoding ) );
		}

		if ( 'no value' === $expected ) {
			$this->assertStringNotContainsString( ' decoding=', $image );
		} else {
			$this->assertStringContainsString( ' decoding="' . esc_attr( $expected ) . '"', $image );
		}
	}

	/**
	 * Data provider for test_wp_get_attachment_image_decoding_attr().
	 *
	 * @return array[]
	 */
	public function data_wp_get_attachment_image_decoding_attr() {
		return array(
			'default'     => array(
				'decoding' => 'no value',
				'expected' => 'async',
			),
			'async'       => array(
				'decoding' => 'async',
				'expected' => 'async',
			),
			'sync'        => array(
				'decoding' => 'sync',
				'expected' => 'sync',
			),
			'auto'        => array(
				'decoding' => 'auto',
				'expected' => 'auto',
			),
			'empty'       => array(
				'decoding' => '',
				'expected' => 'no value',
			),
			'false'       => array(
				'decoding' => false,
				'expected' => 'no value',
			),
			'null'        => array(
				'decoding' => null,
				'expected' => 'no value',
			),
			'zero'        => array(
				'decoding' => 0,
				'expected' => 'no value',
			),
			'zero string' => array(
				'decoding' => '0',
				'expected' => 'no value',
			),
			'zero float'  => array(
				'decoding' => 0.0,
				'expected' => 'no value',
			),
			'invalid'     => array(
				'decoding' => 'invalid',
				'expected' => 'no value',
			),
		);
	}

	/**
	 * @ticket 44427
	 * @ticket 50425
	 * @ticket 50756
	 * @dataProvider data_wp_lazy_loading_enabled_tag_name_defaults
	 *
	 * @param string $tag_name Tag name.
	 * @param bool   $expected Expected return value.
	 */
	public function test_wp_lazy_loading_enabled_tag_name_defaults( $tag_name, $expected ) {
		if ( $expected ) {
			$this->assertTrue( wp_lazy_loading_enabled( $tag_name, 'the_content' ) );
		} else {
			$this->assertFalse( wp_lazy_loading_enabled( $tag_name, 'the_content' ) );
		}
	}

	public function data_wp_lazy_loading_enabled_tag_name_defaults() {
		return array(
			'img => true'            => array( 'img', true ),
			'iframe => true'         => array( 'iframe', true ),
			'arbitrary tag => false' => array( 'blink', false ),
		);
	}

	/**
	 * @ticket 50425
	 * @ticket 53463
	 * @ticket 53675
	 * @dataProvider data_wp_lazy_loading_enabled_context_defaults
	 *
	 * @param string $context  Function context.
	 * @param bool   $expected Expected return value.
	 */
	public function test_wp_lazy_loading_enabled_context_defaults( $context, $expected ) {
		if ( $expected ) {
			$this->assertTrue( wp_lazy_loading_enabled( 'img', $context ) );
		} else {
			$this->assertFalse( wp_lazy_loading_enabled( 'img', $context ) );
		}
	}

	public function data_wp_lazy_loading_enabled_context_defaults() {
		return array(
			'wp_get_attachment_image => true' => array( 'wp_get_attachment_image', true ),
			'the_content => true'             => array( 'the_content', true ),
			'the_excerpt => true'             => array( 'the_excerpt', true ),
			'widget_text_content => true'     => array( 'widget_text_content', true ),
			'get_avatar => true'              => array( 'get_avatar', true ),
			'arbitrary context => true'       => array( 'something_completely_arbitrary', true ),
			'the_post_thumbnail => true'      => array( 'the_post_thumbnail', true ),
		);
	}

	/**
	 * @ticket 50543
	 */
	public function test_wp_image_file_matches_image_meta() {
		$image_meta       = wp_get_attachment_metadata( self::$large_id );
		$image_src_full   = wp_get_attachment_image_url( self::$large_id, 'full' );
		$image_src_medium = wp_get_attachment_image_url( self::$large_id, 'medium' );

		$this->assertTrue( wp_image_file_matches_image_meta( $image_src_full, $image_meta ) );
		$this->assertTrue( wp_image_file_matches_image_meta( $image_src_medium, $image_meta ) );
	}

	/**
	 * @ticket 50543
	 */
	public function test_wp_image_file_matches_image_meta_no_subsizes() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$image_src  = wp_get_attachment_image_url( self::$large_id, 'full' );

		$image_meta['sizes'] = array();

		$this->assertTrue( wp_image_file_matches_image_meta( $image_src, $image_meta ) );
	}

	/**
	 * @ticket 50543
	 */
	public function test_wp_image_file_matches_image_meta_invalid_meta() {
		$image_meta = ''; // Attachment is not an image.
		$image_src  = self::IMG_URL;

		$this->assertFalse( wp_image_file_matches_image_meta( $image_src, $image_meta ) );
	}

	/**
	 * @ticket 50543
	 */
	public function test_wp_image_file_matches_image_meta_different_meta() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$image_src  = self::IMG_URL; // Different image.

		$this->assertFalse( wp_image_file_matches_image_meta( $image_src, $image_meta ) );
	}

	/**
	 * @ticket 50543
	 */
	public function test_wp_image_file_matches_image_meta_original_image() {
		$image_meta = wp_get_attachment_metadata( self::$large_id );
		$image_src  = wp_get_original_image_url( self::$large_id );

		$this->assertTrue( wp_image_file_matches_image_meta( $image_src, $image_meta ) );
	}

	/**
	 * @ticket 22101
	 */
	public function test_gallery_shortcode_when_is_feed_true() {

		$this->go_to( '/?feed=rss2' );

		// Default: Links to image attachment page URL.
		$actual = gallery_shortcode(
			array(
				'ids' => self::$large_id,
			)
		);
		$this->assertStringContainsString( '?attachment_id=', $actual );

		// File: Links to image file URL.
		$actual = gallery_shortcode(
			array(
				'ids'  => self::$large_id,
				'link' => 'file',
			)
		);
		$this->assertSame( 2, substr_count( $actual, '.jpg' ) );

		// None: Does not link.
		$actual = gallery_shortcode(
			array(
				'ids'  => self::$large_id,
				'link' => 'none',
			)
		);
		$this->assertStringNotContainsString( '<a ', $actual );
	}

	/**
	 * Test attachment permalinks based on parent post status.
	 *
	 * @dataProvider data_attachment_permalinks_based_on_parent_status
	 * @ticket 51776
	 *
	 * @param string $post_key     Post as keyed in the shared fixture array.
	 * @param string $expected_url Expected permalink.
	 * @param bool   $expected_404 Whether the page is expected to return a 404 result.
	 *
	 */
	public function test_attachment_permalinks_based_on_parent_status( $post_key, $expected_url, $expected_404 ) {
		$this->set_permalink_structure( '/%postname%' );
		$post = get_post( self::$post_ids[ $post_key ] );

		/*
		 * The dataProvider runs before the fixures are set up, therefore the
		 * post object IDs are placeholders that needs to be replaced.
		 */
		$expected_url = home_url( str_replace( '%ID%', $post->ID, $expected_url ) );

		$this->go_to( get_permalink( $post ) );
		$this->assertSame( $expected_url, get_permalink( $post ) );
		if ( $expected_404 ) {
			$this->assertQueryTrue( 'is_404' );
		} else {
			$this->assertQueryTrue( 'is_attachment', 'is_single', 'is_singular' );
		}
		$this->assertSame( 'attachment', $post->post_type );
	}

	/**
	 * Data provider for test_attachment_permalinks_based_on_parent_status().
	 *
	 * @return array[] {
	 *     @type string $post_key     Post as keyed in the shared fixture array.
	 *     @type string $expected_url Expected permalink.
	 *     $type bool   $expected_404 Whether the page is expected to return a 404 result.
	 * }
	 */
	public function data_attachment_permalinks_based_on_parent_status() {
		return array(
			array( 'draft-attachment', '/?attachment_id=%ID%', true ),
			array( 'publish-attachment', '/publish-post/publish-attachment', false ),
			array( 'future-attachment', '/future-post/future-attachment', false ),
			array( 'auto-draft-attachment', '/?attachment_id=%ID%', true ),
			array( 'trash-attachment', '/?attachment_id=%ID%', false ),
		);
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute value.
	 *
	 * @ticket 53675
	 * @ticket 56930
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default
	 *
	 * @param string $context
	 */
	public function test_wp_get_loading_attr_default( $context ) {
		// Return 'lazy' by default.
		$this->assertSame( 'lazy', wp_get_loading_attr_default( 'test' ) );
		$this->assertSame( 'lazy', wp_get_loading_attr_default( 'wp_get_attachment_image' ) );

		// Return 'lazy' if not in the loop or the main query.
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );

		$query = $this->get_new_wp_query_for_published_post();

		while ( have_posts() ) {
			the_post();

			// Return 'lazy' if in the loop but not in the main query.
			$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );

			// Set as main query.
			$this->set_main_query( $query );

			// For contexts other than for the main content, still return 'lazy' even in the loop
			// and in the main query, and do not increase the content media count.
			$this->assertSame( 'lazy', wp_get_loading_attr_default( 'wp_get_attachment_image' ) );

			// Return `false` in the main query for first three element.
			$this->assertFalse( wp_get_loading_attr_default( $context ), 'Expected first image to not be lazy-loaded.' );
			$this->assertFalse( wp_get_loading_attr_default( $context ), 'Expected second image to not be lazy-loaded.' );
			$this->assertFalse( wp_get_loading_attr_default( $context ), 'Expected third image to not be lazy-loaded.' );

			// Return 'lazy' if in the loop and in the main query for any subsequent elements.
			$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );

			// Yes, for all subsequent elements.
			$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
		}
	}

	public function data_wp_get_loading_attr_default() {
		return array(
			array( 'the_content' ),
			array( 'the_post_thumbnail' ),
		);
	}

	/**
	 * @ticket 53675
	 */
	public function test_wp_omit_loading_attr_threshold_filter() {
		$query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $query );

		// Use the filter to alter the threshold for not lazy-loading to the first five elements.
		$this->force_omit_loading_attr_threshold( 5 );

		while ( have_posts() ) {
			the_post();

			// Due to the filter, now the first five elements should not be lazy-loaded, i.e. return `false`.
			for ( $i = 0; $i < 5; $i++ ) {
				$this->assertFalse( wp_get_loading_attr_default( 'the_content' ) );
			}

			// For following elements, lazy-load them again.
			$this->assertSame( 'lazy', wp_get_loading_attr_default( 'the_content' ) );
		}
	}

	/**
	 * @ticket 53675
	 */
	public function test_wp_filter_content_tags_with_wp_get_loading_attr_default() {
		$img1         = get_image_tag( self::$large_id, '', '', '', 'large' );
		$iframe1      = '<iframe src="https://www.example.com" width="640" height="360"></iframe>';
		$img2         = get_image_tag( self::$large_id, '', '', '', 'medium' );
		$img3         = get_image_tag( self::$large_id, '', '', '', 'thumbnail' );
		$iframe2      = '<iframe src="https://wordpress.org" width="640" height="360"></iframe>';
		$lazy_img2    = wp_img_tag_add_loading_attr( $img2, 'the_content' );
		$lazy_img3    = wp_img_tag_add_loading_attr( $img3, 'the_content' );
		$lazy_iframe2 = wp_iframe_tag_add_loading_attr( $iframe2, 'the_content' );

		// Use a threshold of 2.
		$this->force_omit_loading_attr_threshold( 2 );

		// Following the threshold of 2, the first two content media elements should not be lazy-loaded.
		$content_unfiltered = $img1 . $iframe1 . $img2 . $img3 . $iframe2;
		$content_expected   = $img1 . $iframe1 . $lazy_img2 . $lazy_img3 . $lazy_iframe2;
		$content_expected   = wp_img_tag_add_decoding_attr( $content_expected, 'the_content' );

		$query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $query );

		while ( have_posts() ) {
			the_post();

			add_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
			$content_filtered = wp_filter_content_tags( $content_unfiltered, 'the_content' );
			remove_filter( 'wp_img_tag_add_srcset_and_sizes_attr', '__return_false' );
		}

		// After filtering, the first image should not be lazy-loaded while the other ones should be.
		$this->assertSame( $content_expected, $content_filtered );
	}

	/**
	 * @ticket 53675
	 */
	public function test_wp_omit_loading_attr_threshold() {
		$this->reset_omit_loading_attr_filter();

		// Apply filter, ensure default value of 3.
		$omit_threshold = wp_omit_loading_attr_threshold();
		$this->assertSame( 3, $omit_threshold );

		// Add a filter that changes the value to 1. However, the filter is not applied a subsequent time in a single
		// page load by default, so the value is still 3.
		$this->force_omit_loading_attr_threshold( 1 );

		$omit_threshold = wp_omit_loading_attr_threshold();
		$this->assertSame( 3, $omit_threshold );

		// Only by enforcing a fresh check, the filter gets re-applied.
		$omit_threshold = wp_omit_loading_attr_threshold( true );
		$this->assertSame( 1, $omit_threshold );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute value before loop but after get_header if not main query.
	 *
	 * @ticket 58211
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default_before_and_no_loop
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_before_loop_if_not_main_query( $context ) {
		global $wp_query;

		$wp_query = $this->get_new_wp_query_for_published_post();

		do_action( 'get_header' );

		// Lazy if not main query.
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute value before loop but after get_header in main query but header was not called.
	 *
	 * @ticket 58211
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default_before_and_no_loop
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_before_loop_in_main_query_but_header_not_called( $context ) {
		global $wp_query;

		$wp_query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $wp_query );

		// Lazy if header not called.
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute value before loop but after get_header for main query.
	 *
	 * @ticket 58211
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default_before_and_no_loop
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_before_loop_if_main_query( $context ) {
		global $wp_query;

		$wp_query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $wp_query );

		do_action( 'get_header' );
		$this->assertFalse( wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute value after get_header and after loop.
	 *
	 * @ticket 58211
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default_before_and_no_loop
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_after_loop( $context ) {
		global $wp_query;

		$wp_query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $wp_query );

		do_action( 'get_header' );

		while ( have_posts() ) {
			the_post();
		}
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns the expected loading attribute if no loop.
	 *
	 * @ticket 58211
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_wp_get_loading_attr_default_before_and_no_loop
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_no_loop( $context ) {
		global $wp_query;

		$wp_query = $this->get_new_wp_query_for_published_post();
		$this->set_main_query( $wp_query );

		// Ensure header and footer is called.
		do_action( 'get_header' );
		do_action( 'get_footer' );

		// Load lazy if the there is no loop and footer was called.
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_wp_get_loading_attr_default_before_and_no_loop() {
		return array(
			array( 'wp_get_attachment_image' ),
			array( 'the_post_thumbnail' ),
		);
	}

	/**
	 * @ticket 58089
	 *
	 * @covers ::wp_filter_content_tags
	 * @covers ::wp_get_loading_attr_default
	 */
	public function test_wp_filter_content_tags_does_not_lazy_load_special_images_within_the_content() {
		global $wp_query, $wp_the_query;

		// Force no lazy-loading on the image tag expected in the content.
		$expected_content = wpautop( wp_get_attachment_image( self::$large_id, 'large', false, array( 'loading' => false ) ) );

		// Overwrite post content with an image.
		add_filter(
			'the_content',
			static function () {
				// Replace content with an image tag, i.e. the 'wp_get_attachment_image' context is used while running 'the_content' filter.
				return wp_get_attachment_image( self::$large_id, 'large', false );
			},
			9 // Run before wp_filter_content_tags().
		);

		/*
		 * We have to run a main query loop so that the first 'the_content' context image is not
		 * lazy-loaded.
		 * Without the fix from 58089, the image would still be lazy-loaded since the check for the
		 * separately invoked 'wp_get_attachment_image' context would lead to that.
		 */
		$wp_query     = new WP_Query( array( 'post__in' => array( self::$post_ids['publish'] ) ) );
		$wp_the_query = $wp_query;

		$content = '';
		while ( have_posts() ) {
			the_post();
			$content = get_echo( 'the_content' );
		}

		// Ensure that parsed content has the image without lazy-loading.
		$this->assertSame( $expected_content, $content );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns 'lazy' for special contexts when they're used outside of 'the_content' filter.
	 *
	 * @ticket 58089
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_special_contexts_for_the_content
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_should_return_lazy_for_special_contexts_outside_of_the_content( $context ) {
		$this->assertSame( 'lazy', wp_get_loading_attr_default( $context ) );
	}

	/**
	 * Tests that wp_get_loading_attr_default() returns false for special contexts when they're used within 'the_content' filter.
	 *
	 * @ticket 58089
	 *
	 * @covers ::wp_get_loading_attr_default
	 *
	 * @dataProvider data_special_contexts_for_the_content
	 *
	 * @param string $context Context for the element for which the `loading` attribute value is requested.
	 */
	public function test_wp_get_loading_attr_default_should_return_false_for_special_contexts_within_the_content( $context ) {
		remove_all_filters( 'the_content' );

		$result = null;
		add_filter(
			'the_content',
			function ( $content ) use ( &$result, $context ) {
				$result = wp_get_loading_attr_default( $context );
				return $content;
			}
		);
		apply_filters( 'the_content', '' );
		$this->assertFalse( $result );
	}

	/**
	 * Data provider.
	 *
	 * @return array[]
	 */
	public function data_special_contexts_for_the_content() {
		return array(
			'the_post_thumbnail'      => array( 'context' => 'the_post_thumbnail' ),
			'wp_get_attachment_image' => array( 'context' => 'wp_get_attachment_image' ),
		);
	}

	/**
	 * Tests that the content media count is not affected by `the_excerpt()` calls for posts that contain images.
	 *
	 * @ticket 56588
	 *
	 * @covers ::wp_trim_excerpt
	 */
	public function test_the_excerpt_does_not_affect_content_media_count() {
		global $wp_query, $wp_the_query;

		/*
		 * Use the filter to alter the threshold for not lazy-loading to the first 2 elements,
		 * then use a post that contains exactly 2 images.
		 */
		$this->force_omit_loading_attr_threshold( 2 );
		$post_content  = '<img src="example.jpg" width="800" height="600">';
		$post_content .= '<p>Some text.</p>';
		$post_content .= '<img src="example2.jpg" width="800" height="600">';

		$post_id = self::factory()->post->create(
			array(
				'post_content' => $post_content,
				'post_excerpt' => '',
			)
		);

		$wp_query     = new WP_Query( array( 'post__in' => array( $post_id ) ) );
		$wp_the_query = $wp_query;

		while ( have_posts() ) {
			the_post();

			// Call `the_excerpt()` without generating output.
			get_echo( 'the_excerpt' );
		}

		// The only way to access the value is by calling this function without increasing the value.
		$content_media_count = wp_increase_content_media_count( 0 );

		// Assert that the media count was not increased even though there are 3 images in the post's content.
		$this->assertSame( 0, $content_media_count );
	}

	/**
	 * Tests that the lazy-loading result is not affected by `the_excerpt()` calls for posts that
	 * contain images.
	 *
	 * Printing the excerpt for a post that contains images in its content prior to its featured image should result in
	 * that featured image not being lazy-loaded, since the images in the post content aren't displayed in the excerpt.
	 *
	 * @ticket 56588
	 *
	 * @covers ::wp_trim_excerpt
	 */
	public function test_the_excerpt_does_not_affect_omit_lazy_loading_logic() {
		global $wp_query, $wp_the_query;

		/*
		 * Use the filter to alter the threshold for not lazy-loading to the first 2 elements,
		 * then use a post that contains exactly 2 images.
		 */
		$this->force_omit_loading_attr_threshold( 2 );
		$post_content  = '<img src="example.jpg" width="800" height="600">';
		$post_content .= '<p>Some text.</p>';
		$post_content .= '<img src="example2.jpg" width="800" height="600">';

		$post_id           = self::factory()->post->create(
			array(
				'post_content' => $post_content,
				'post_excerpt' => '',
			)
		);
		$featured_image_id = self::$large_id;
		update_post_meta( $post_id, '_thumbnail_id', $featured_image_id );

		$expected_image_tag = get_the_post_thumbnail( $post_id, 'post-thumbnail', array( 'loading' => false ) );

		$wp_query     = new WP_Query( array( 'post__in' => array( $post_id ) ) );
		$wp_the_query = $wp_query;

		$output = '';
		while ( have_posts() ) {
			the_post();

			// Print excerpt first, then the featured image.
			$output .= get_echo( 'the_excerpt' );
			$output .= get_echo( 'the_post_thumbnail' );
		}

		$this->assertStringContainsString( $expected_image_tag, $output );
	}

	private function reset_content_media_count() {
		// Get current value without increasing.
		$content_media_count = wp_increase_content_media_count( 0 );

		// Decrease it by its current value to "reset" it back to 0.
		wp_increase_content_media_count( - $content_media_count );
	}

	private function reset_omit_loading_attr_filter() {
		// Add filter to "reset" omit threshold back to null (unset).
		add_filter( 'wp_omit_loading_attr_threshold', '__return_null', 100 );

		// Force filter application to re-run.
		wp_omit_loading_attr_threshold( true );

		// Clean up the above filter.
		remove_filter( 'wp_omit_loading_attr_threshold', '__return_null', 100 );
	}

	/**
	 * Test that generated files with the `image_editor_output_format` applied use the correct
	 * quality level based on their mime type.
	 *
	 * @ticket 56442
	 */
	public function test_quality_with_image_conversion_file_sizes() {
		add_filter( 'image_editor_output_format', array( $this, 'image_editor_output_jpeg' ) );
		$temp_dir = get_temp_dir();
		$file     = $temp_dir . '/33772.jpg';
		copy( DIR_TESTDATA . '/images/33772.jpg', $file );

		// Set JPEG output quality very low and WebP quality very high, this should force all generated WebP images to
		// be larger than the the matching generated JPEGs.
		add_filter( 'wp_editor_set_quality', array( $this, 'image_editor_change_quality_low_jpeg' ), 10, 2 );

		$editor = wp_get_image_editor( $file );

		// Verify that the selected editor supports WebP output.
		if ( ! $editor->supports_mime_type( 'image/webp' ) ) {
			$this->markTestSkipped( 'WebP is not supported by the selected image editor.' );
		}

		$attachment_id = self::factory()->attachment->create_object(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => $file,
			)
		);

		add_filter( 'big_image_size_threshold', array( $this, 'add_big_image_size_threshold' ) );

		// Generate all sizes as JPEGs.
		$jpeg_sizes = wp_generate_attachment_metadata( $attachment_id, $file );
		remove_filter( 'image_editor_output_format', array( $this, 'image_editor_output_jpeg' ) );

		// Generate all sizes as WebP.
		add_filter( 'image_editor_output_format', array( $this, 'image_editor_output_webp' ) );
		$webp_sizes = wp_generate_attachment_metadata( $attachment_id, $file );
		remove_filter( 'image_editor_output_format', array( $this, 'image_editor_output_webp' ) );

		// The main (scaled) image: the JPEG should be smaller than the WebP.
		$this->assertLessThan( $webp_sizes['filesize'], $jpeg_sizes['filesize'], 'The JPEG should be smaller than the WebP.' );

		// Sub-sizes: for each size, the JPEGs should be smaller than the WebP.
		$sizes_to_compare = array_intersect_key( $jpeg_sizes['sizes'], $webp_sizes['sizes'] );
		foreach ( $sizes_to_compare as $size => $size_data ) {
			$this->assertLessThan( $webp_sizes['sizes'][ $size ]['filesize'], $jpeg_sizes['sizes'][ $size ]['filesize'] );
		}
	}

	/**
	 * Test that an image size isn't generated if it matches the original image size.
	 *
	 * @ticket 57370
	 */
	public function test_wp_generate_attachment_metadata_doesnt_generate_sizes_for_150_square_image() {
		$temp_dir = get_temp_dir();
		$file     = $temp_dir . '/test-square-150.jpg';
		copy( DIR_TESTDATA . '/images/test-square-150.jpg', $file );

		$attachment_id = self::factory()->attachment->create_object(
			array(
				'post_mime_type' => 'image/jpeg',
				'file'           => $file,
			)
		);

		$metadata = wp_generate_attachment_metadata( $attachment_id, $file );
		$this->assertSame(
			array(),
			$metadata['sizes'],
			'The sizes should be an empty array'
		);
		$this->assertSame(
			'test-square-150.jpg',
			basename( $metadata['file'] ),
			'The file basename should match the given filename'
		);
		$this->assertSame(
			150,
			$metadata['width'],
			'The width should be 150 (integer)'
		);
		$this->assertSame(
			150,
			$metadata['height'],
			'The height should be 150 (integer)'
		);
	}

	/**
	 * Add threshold to create a `-scaled` output image for testing.
	 */
	public function add_big_image_size_threshold() {
		return 1000;
	}

	/**
	 * Output JPEG files.
	 */
	public function image_editor_output_jpeg() {
		return array( 'image/jpeg' => 'image/jpeg' );
	}

	/**
	 * Output WebP files.
	 */
	public function image_editor_output_webp() {
		return array( 'image/jpeg' => 'image/webp' );
	}

	/**
	 * Changes the quality using very low quality for JPEGs and very high quality
	 * for WebPs, used to verify the filter is applying correctly.
	 *
	 * @param int    $quality   Default quality.
	 * @param string $mime_type Image mime-type.
	 * @return int The changed quality.
	 */
	public function image_editor_change_quality_low_jpeg( $quality, $mime_type ) {
		if ( 'image/jpeg' === $mime_type ) {
			return 1;
		} elseif ( 'image/webp' === $mime_type ) {
			return 100;
		} else {
			return 30;
		}
	}

	/**
	 * Change the omit loading attribute threshold value.
	 *
	 * @param int $threshold Threshold value to change.
	 */
	public function force_omit_loading_attr_threshold( $threshold ) {
		add_filter(
			'wp_omit_loading_attr_threshold',
			static function () use ( $threshold ) {
				return $threshold;
			}
		);
	}

	/**
	 * Returns a new WP_Query.
	 *
	 * @global WP_Query $wp_query WordPress Query object.
	 *
	 * @return WP_Query a new query.
	 */
	public function get_new_wp_query_for_published_post() {
		global $wp_query;

		// New query to $wp_query. update global for the loop.
		$wp_query = new WP_Query( array( 'post__in' => array( self::$post_ids['publish'] ) ) );

		return $wp_query;
	}

	/**
	 * Sets a query as main query.
	 *
	 * @global WP_Query $wp_the_query WordPress Query object.
	 *
	 * @param WP_Query $query query to be set as main query.
	 */
	public function set_main_query( $query ) {
		global $wp_the_query;
		$wp_the_query = $query;
	}
}

/**
 * Helper class for `test_autoembed`.
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile
class Test_Autoembed extends WP_Embed {
	public function shortcode( $attr, $url = '' ) {
		return '[embed]';
	}
}
