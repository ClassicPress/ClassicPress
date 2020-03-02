<?php
/**
 * Security Administration Screen.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __( 'Security' );

require_once( ABSPATH . 'wp-admin/admin-header.php' );

$tabs = [
	'users'      => [
		'title'   => __( 'For Users' ),
		'classes' => [ 'nav-tab' ],
	],
	'developers' => [
		'title'   => __( 'For Developers' ),
		'classes' => [ 'nav-tab' ],
	],
];

$active_tab = 'users'; // default
if ( isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ) {
	$active_tab = $_GET['tab'];
}

$tabs[ $active_tab ]['classes'][] = 'nav-tab-active';

?>
<div class="wrap">
	<h1><?php echo esc_html( $title ); ?></h1>
	<h2 class="nav-tab-wrapper">
<?php
$url = ( is_network_admin() )
	? network_admin_url( 'security.php' )
	: admin_url( 'security.php' );
foreach ( $tabs as $tab => $info ) {
	printf(
		'<a href="%s?tab=%s" class="%s">%s</a>',
		$url,
		$tab,
		join( ' ', $info['classes'] ),
		$info['title']
	);
}
?>
	</h2>
<?php
switch ( $active_tab ) :
	case 'users':
		?>

	<div class="card">
		<h2><?php _e( '"Security First"' ); ?></h2>
		<p><?php _e( 'Security is important to business and it&#8217;s important to us here at ClassicPress. By bringing security forward to a place of greater prominence within the admin interface, we create a more streamlined experience for both users and developers.' ); ?></p>
		<p><?php _e( 'As ClassicPress continues to evolve, the Security page will become the hub for all security features for ClassicPress core and 3<sup>rd</sup> party plugins that choose to support it.' ); ?></p>
		<p>
		<?php
		printf(
			/* translators: canonical link to security forum */
			__( 'Watch this page and the ClassicPress <a href="%s" rel="noopener" target="_blank">Security forum</a> for more news as development continues.' ),
			'https://link.classicpress.net/forum/security'
		);
		?>
		</p>
	</div>

	<div class="card">
		<?php
		$security_pages = empty( $submenu['security.php'] )
			? []
			: array_slice( $submenu['security.php'], 1 );
		echo '<h3>';
		esc_html_e( 'Plugin Security Settings' );
		echo "</h3>\n";
		if ( count( $security_pages ) ) {
			echo '<ul class="ul-disc">' . "\n";
			foreach ( $security_pages as $page ) {
				// TODO: show as
				// "<a>Menu Title</a> provided by <strong>Plugin Name</strong>"
				// if "Menu Title" and "Plugin Name" are different
				list( $menu_title, $cap, $page_slug, $page_title ) = $page;
				printf(
					'<li><a href="%s">%s</a></li>' . "\n",
					esc_attr( admin_url( 'security.php?page=' . $page_slug ) ),
					esc_html( $menu_title )
				);
			}
			echo '</ul>' . "\n";
		} else {
			echo '<p><strong>';
			esc_html_e( 'No registered security settings yet!' );
			echo "</p></strong>\n";
			echo '<p>';
			esc_html_e( 'Install plugins that add their own security settings according to the ClassicPress guidelines, and their settings pages will be listed here and in the Security menu on the left.' );
			echo "</p>\n";
		}
		echo '<p>';
		printf(
			/* translators: link that describes how to contact plugin authors about the ClassicPress security page */
			__( 'If you have plugins installed and activated with security-related settings that aren&#8217;t appearing here, <a href="%s" rel="noopener" target="_blank">contact the authors</a> and ask them to add support for the ClassicPress security page.' ),
			'https://link.classicpress.net/security-page/contact-plugin-authors'
		);
		echo "</p>\n";
		?>
	</div>
		<?php

		break;
	case 'developers':
		/**
		 * HTML generated using hilite.me:
		 *   + The opening PHP tag is required to get the highlighting to work,
		 *     but it's been removed here for clarity
		 *   + "tab-size: 4;" was added to the CSS for the containing element
		 *   + The "default" colour scheme was used
		 */
		?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2><span><?php _e( 'Overview' ); ?></span></h2>
						<div class="inside">
							<div>
								<p><?php _e( 'The Security page is the first visible step in improving the overall approach to security in ClassicPress. Its purpose is to solve two related problems: <strong>discovery</strong> and <strong>auditing</strong>.' ); ?></p>
								<p><?php _e( 'Most plugins are organised around their core purpose; after all, that purpose is the reason they were installed. Unfortunately this leads to poor discoverability of security-related settings &mdash; they may be in there somewhere, there may be none at all &mdash; without looking through everything there&#8217;s no way to know.' ); ?></p>
								<p><?php _e( 'Currently, auditing the overall security profile of a ClassicPress site is impractical if there are more than a few plugins. Having all security-related settings from all plugins in one place means those settings can be audited far more easily, as the time taken will be proportional to the number of settings, not the number of places to look for those settings.' ); ?></p>
							</div>
						</div>
					</div>
				</div>
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2><span><?php _e( 'API' ); ?></span></h2>
						<div class="inside">
							<p><?php _e( 'There is just one new function: <code>add_security_page()</code>. It works similarly to the other <code>add_xxx_page()</code> helpers, with two important changes:' ); ?></p>
							<p>
								<ul class="ul-disc">
									<li><?php _e( 'there is no <code>$capability</code> argument - it is always <code>manage_options</code>' ); ?></li>
									<li><?php _e( 'the <code>$menu_slug</code> must match an active plugin or mu-plugin slug.' ); ?></li>
								</ul>
							</p>
							<p><?php _e( 'The function also adds a link from your plugin&#8217;s action row in the plugins list directly to your security page.' ); ?></p>
							<!-- HTML generated using hilite.me -->
							<div style="background: #f8f8f8; overflow:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%; tab-size: 4; -moz-tab-size: 4; -o-tab-size: 4;"><span style="color: #BA2121; font-style: italic">/**</span>
<span style="color: #BA2121; font-style: italic"> * Add submenu page to the Security main menu.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * The function which is hooked in to handle the output of the page must check</span>
<span style="color: #BA2121; font-style: italic"> * that the user has the required capability as well.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $page_title The text to be displayed in the title tags of</span>
<span style="color: #BA2121; font-style: italic">                               the page when the menu is selected.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_title The text to be used for the menu.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_slug  The slug name to refer to this menu by; must </span>
<span style="color: #BA2121; font-style: italic">                               match an active plugin or mu-plugin slug.</span>
<span style="color: #BA2121; font-style: italic"> * @param callable $function   The function to be called to output the content</span>
<span style="color: #BA2121; font-style: italic">                               for this page.</span>
<span style="color: #BA2121; font-style: italic"> * @return false|string The resulting page&#39;s hook_suffix, or false if the user</span>
<span style="color: #BA2121; font-style: italic">                        does not have the 'manage_options' capability.</span>
<span style="color: #BA2121; font-style: italic"> */</span>
<span style="color: #008000; font-weight: bold">function</span> <span style="color: #0000FF">add_security_page</span>(
	<span style="color: #19177C">$page_title</span>,
	<span style="color: #19177C">$menu_title</span>,
	<span style="color: #19177C">$menu_slug</span>,
	<span style="color: #19177C">$function</span> <span style="color: #666666">=</span> <span style="color: #BA2121">&#39;&#39;</span>
)
</pre>
							</div>
						</div>
					</div>
				</div>
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<h2><span><?php _e( 'Usage' ); ?></span></h2>
						<div class="inside">
							<p><?php _e( 'You will need to change the logic in your plugin to check for <code>add_security_page()</code>. For example:' ); ?></p>
							<!-- HTML generated using hilite.me -->
							<div style="background: #f8f8f8; overflow:auto;width:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%; tab-size: 4; -moz-tab-size: 4; -o-tab-size: 4;"><span style="color: #008000; font-weight: bold">if</span> ( <span style="color: #008000">function_exists</span>( <span style="color: #BA2121">&#39;\add_security_page&#39;</span> ) ) {
	add_security_page(
		<span style="color: #BA2121">&#39;My Prodigious Plugin&#39;</span>,
		<span style="color: #BA2121">&#39;My Menu Title&#39;</span>,
		<span style="color: #BA2121">&#39;my-prodigious-plugin&#39;</span>,
		<span style="color: #BA2121">&#39;my-output-function&#39;</span>
	);
} <span style="color: #008000; font-weight: bold">else</span> {
	<span style="color: #408080; font-style: italic">// existing code</span>
}
</pre>
							</div>
							<p><?php _e( 'You will also need to change the logic on your settings pages, but that is outside the scope of this guide. However, you should remember that the idea is to <strong>move</strong> security-related settings, <strong>not</strong> to <em>duplicate</em> them.' ); ?></p>
						</div>
						<h2><span><?php _e( 'Security Plugins' ); ?></span></h2>
						<div class="inside">
							<p><?php _e( 'If your plugin has nothing but security-related settings it may be more useful to provide a summary of the current settings, with links to where they can be changed.' ); ?></p>
						</div>
					</div>
				</div>
			</div>
			<div id="postbox-container-1" class="postbox-container">
				<div class="meta-box-sortables">
					<div class="postbox">
						<h2><span><?php _e( 'Support and Documentation' ); ?></span></h2>
						<div class="inside">
							<p>
								<ul>
									<li><a href="https://link.classicpress.net/support/security-page" rel="noopener" target="_blank"><?php _e( 'Security Page forum' ); ?></a></li>
									<li><a href="https://link.classicpress.net/security-page" rel="noopener" target="_blank"><?php _e( 'Documentation' ); ?></a></li>
								</ul>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>

		<?php
		break;
endswitch;
?>
</div>
<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );

