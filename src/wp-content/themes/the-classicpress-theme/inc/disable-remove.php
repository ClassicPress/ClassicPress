<?php
/* REMOVE FEEDS */
add_filter( 'feed_links_show_posts_feed', '__return_false' );
add_filter( 'feed_links_show_comments_feed', '__return_false' );
remove_action( 'wp_head', 'feed_links_extra', 3 );

/* REMOVE EDIT LINKS */
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' ); // Windows Live Writer

/* REMOVE OTHER LINKS */
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );

/* REMOVE WP VERSION */
remove_action( 'wp_head', 'wp_generator' );

/* REMOVE HINTS for DNS PREFETCH, etc. */
remove_action( 'wp_head', 'wp_resource_hints', 2 );

/* REMOVE EMOJI (with thanks to Ryan Hellyer) */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
function kts_disable_emoji_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	} else {
		return array();
	}
} // remove from TinyMCE
add_filter( 'tiny_mce_plugins', 'kts_disable_emoji_tinymce' );
add_filter( 'emoji_svg_url', '__return_false' ); // remove DNS prefetch
add_filter( 'option_use_smilies', '__return_false' ); // remove smilies

function kts_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' == $relation_type ) {
		// Strip out any URLs referencing the WordPress.org emoji location
		$emoji_svg_url_bit = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $key => $url ) {
			if ( strpos( $url, $emoji_svg_url_bit ) !== false ) {
				unset( $urls[ $key ] );
			}
		}
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'kts_disable_emojis_remove_dns_prefetch', 10, 2 );

/* REMOVE USELESS PARTS OF REST API */
remove_action( 'rest_api_init', 'wp_oembed_register_route' );
remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
add_filter( 'rest_jsonp_enabled', '__return_false' );

function kts_disable_rest_api_endpoints( $endpoints ) {
	$endpoints_to_remove = array(
		'/oembed/1.0',
		'/wp/v2/media',
		'/wp/v2/users',
		'/wp/v2/menu-items',
		'/wp/v2/topics',
		'/wp/v2/types',
		'/wp/v2/statuses',
		'/wp/v2/taxonomies',
		'/wp/v2/categories',
		'/wp/v2/tags',
		'/wp/v2/comments',
		'/wp/v2/menus',
		'/wp/v2/search',
		'/wp/v2/settings',
		'/wp/v2/themes',
		'/wp/v2/plugins',
		'/wp/v2/sidebars',
		'/wp/v2/menu-locations',
		'/wp/v2/blocks',
		'/wp/v2/oembed',
		'/wp/v2/block-renderer',
		'kts-message',
	);

	foreach ( $endpoints as $key => $object ) {
		foreach ( $endpoints_to_remove as $rem_endpoint ) { // $base_endpoint = '/wp/v2/' . $rem_endpoint;
			if ( stripos( $key, $rem_endpoint ) !== false ) {
				unset( $endpoints[ $key ] );
			}
		}
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'kts_disable_rest_api_endpoints' );

/* REMOVE <p> FROM AROUND IMAGES */
# http://css-tricks.com/snippets/wordpress/remove-paragraph-tags-from-around-images/)
function kts_filter_ptags_on_images( $content ) {
	return preg_replace( '/<p>\s*(<a .*>)?\s*(<img .* \/>)\s*(<\/a>)?\s*<\/p>/iU', '\1\2\3', $content );
}
add_filter( 'the_content', 'kts_filter_ptags_on_images' );
add_filter( 'use_default_gallery_style', '__return_false' );

/* REMOVE UNWANTED WIDGETS */
function kts_remove_default_widgets() {
	unregister_widget( 'WP_Widget_Pages' );
	unregister_widget( 'WP_Widget_Calendar' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Widget_Links' );
	unregister_widget( 'WP_Widget_Meta' );
	unregister_widget( 'WP_Widget_Search' );
	unregister_widget( 'WP_Widget_Categories' );
	unregister_widget( 'WP_Widget_Recent_Posts' );
	unregister_widget( 'WP_Widget_Recent_Comments' );
	unregister_widget( 'WP_Widget_RSS' );
	unregister_widget( 'WP_Widget_Tag Cloud' );
	unregister_widget( 'WP_Nav_Menu_Widget' );
}
add_action( 'widgets_init', 'kts_remove_default_widgets' );

/* DISABLE INTERNAL PINGBACKS */
function kts_internal_pingbacks( &$links ) {
	# Unset each internal ping
	foreach ( $links as $l => $link ) {
		if ( 0 === strpos( $link, get_option( 'home' ) ) ) {
			unset( $links[ $l ] );
		}
	}
}
add_action( 'pre_ping', 'kts_internal_pingbacks', 10, 1 );
add_filter( 'pings_open', '__return_false', 20, 2 );

/* SET PINGBACK URI TO BLANK FOR BLOGINFO */
function kts_pingback_url( $output, $show ) {
	if ( $show == 'pingback_url' ) {
		$output = '';
	}
	return $output;
}
add_filter( 'bloginfo', 'kts_pingback_url', 1, 2 );
add_filter( 'bloginfo_url', 'kts_pingback_url', 1, 2 );

/* DISABLE XML-RPC & REMOVE FROM HEADERS */
function kts_remove_x_pingback( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}
add_filter( 'wp_headers', 'kts_remove_x_pingback' );
add_filter( 'xmlrpc_enabled', '__return_false', 10, 1 );
add_filter( 'pre_option_enable_xmlrpc', '__return_zero' );
add_filter( 'pre_update_option_enable_xmlrpc', '__return_false' );
add_filter( 'enable_post_by_email_configuration', '__return_false', 10, 1 );

function kts_remove_xmlrpc_methods( $methods ) {
	# Unset Pingback Ping
	unset( $methods['pingback.ping'] );
	unset( $methods['pingback.extensions.getPingbacks'] );
	# Unset discovery of existing users
	unset( $methods['wp.getUsersBlogs'] );
	# Unset list of available methods
	unset( $methods['system.multicall'] );
	unset( $methods['system.listMethods'] );
	# Unset list of capabilities
	unset( $methods['system.getCapabilities'] );
	return $methods;
}
add_filter( 'xmlrpc_methods', 'kts_remove_xmlrpc_methods' );

/* REMOVE CUSTOM CSS FROM CUSTOMIZER */
function kts_remove_css_section( $wp_customize ) {
	$wp_customize->remove_section( 'custom_css' );
}
add_action( 'customize_register', 'kts_remove_css_section', 15 );

/* REMOVE ITEMS FROM ADMINBAR */
function kts_disable_bar_stuff() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'wp-logo' );
	$wp_admin_bar->remove_menu( 'search' );
}
add_action( 'wp_before_admin_bar_render', 'kts_disable_bar_stuff' );
