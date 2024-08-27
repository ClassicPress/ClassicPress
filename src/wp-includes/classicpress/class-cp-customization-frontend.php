<?php
/**
 * ClassicPress Frontend Customization class
 *
 * @package ClassicPress
 * @subpackage admin
 * @since CP-2.3.0
 */

class CP_Customization_Frontend {
	public function __construct() {
		add_action( 'init', array( $this, 'disable_emojis' ) );
	}

	public function disable_emojis() {
		if ( get_option( 'disable_emojis', 0 ) !== '1' ) {
			return;
		}

		add_filter( 'tiny_mce_plugins', array( $this, 'disable_emoji_tinymce' ) );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_hint' ), 10, 2 );

		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		remove_filter( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_head', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	public function disable_emoji_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		}
		return array();
	}

	public function remove_emoji_hint( $urls, $relation ) {
		if ( 'dns-prefetch' === $relation ) {
			$wp_url = 'https://s.w.org/images/core/emoji/';
			$cp_url = 'https://twemoji.classicpress.net';
			foreach ( $urls as $key => $url ) {
				if ( str_starts_with( $url, $wp_url ) || str_starts_with( $url, $cp_url ) ) {
					unset( $urls[ $key ] );
				}
			}
		}
		return $urls;
	}
}

$cp_customization_frontend = new CP_Customization_Frontend();
