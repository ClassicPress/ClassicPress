<?php
/**
 * Security Administration Screen.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

$title = __('Security');

require_once( ABSPATH . 'wp-admin/admin-header.php' );

$tabs = [
    'users' => [
        'title' => __('For Users'),
        'classes' => ['nav-tab']
    ],
    'developers' => [
        'title' => __('For Developers'),
        'classes' => ['nav-tab']
    ]
];
$active_tab = (in_array(@$_GET['tab'], array_keys($tabs)))
    ? $_GET['tab']
    : 'users';
$tabs[$active_tab]['classes'][] = 'nav-tab-active';

?>
<div class="wrap">
  <h1><?php echo esc_html( $title ); ?></h1>
  <h2 class="nav-tab-wrapper">
<?php   foreach ($tabs as $tab => $info): ?>
    <a href="/wp-admin/security.php?tab=<?=$tab?>" class="<?=join(' ', $info['classes'])?>"><?=$info['title']?></a>
<?php   endforeach; ?>
  </h2>
<?php
switch ($active_tab):
    case 'users':
?>

  <div class="card">
    <h2><?php _e( '"Security First"' ); ?></h2>
    <p><?php _e( "Security is important to business and it’s important to us here at ClassicPress. By bringing security forward to a place of greater prominence within the admin interface, we create a more streamlined experience for both users and developers." ); ?></p>
    <p><?php _e( "As ClassicPress continues to evolve, the Security page will become the hub for all security features for ClassicPress core and 3<sup>rd</sup> party plugins that choose to support it." ); ?></p>
    <p><?php printf(
        /* translators: canonical link to security forum */
        __( "Watch this page and the ClassicPress <a href=\"%s\" target=\"_blank\">Security forum</a> for more news as development continues." ),
        'https://forums.classicpress.net/security'
    ); ?></p>
  </div>

<?php
        break;
    case 'developers':
        /**
         * hilite.me
         *   + The opening PHP tag is required to get the highlighting to work, but it's been removed here for clarity
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
                <p>The Security page is the first visible step in improving the overall approach to security in ClassicPress. Its pupose is to solve two related problems: <strong>discovery</strong> and <strong>auditing</strong>.</p>
                <p>Most plugins are organised around their core purpose; after all, that purpose is the reason they were installed. Unfortunately this leads to poor discoverability of security-related settings - they may be in there somewhere, there may be none at all - without looking through everything there&lsquo;s no way to know.</p>
                <p>Currently, auditing the overall security profile of a ClassicPress site is impractical if there are more than a few plugins. Having all security-related settings from all plugins in one place means those settings can be audited far more easily, as the time taken will be proportional to the number of settings, not the number of places to look for those settings.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="meta-box-sortables ui-sortable">
          <div class="postbox">
            <h2><span><?php _e( 'API' ); ?></span></h2>
            <div class="inside">
              <p>There is just one new function: <code>add_security_page()</code>. It works similarly to the other <code>add_xxx_page()</code> helpers, with two important changes:</p>
              <p>
                <ul class="ul-disc">
                  <li>there is no <code>$capability</code> argument - it is always <code>manage_options</code></li>
                  <li>the <code>$menu_slug</code> must match an active plugin slug.</li>
                </ul>
              </p>
              <p>The function also adds a link to your security page to the plugin actions. [This needs to be phrased better - brain fog, suggestions please.]</p>
              <!-- HTML generated using hilite.me -->
              <div style="background: #f8f8f8; overflow:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%"><span style="color: #BA2121; font-style: italic">/**</span>
<span style="color: #BA2121; font-style: italic"> * Add submenu page to the Security main menu.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * The function which is hooked in to handle the output of the page must check</span>
<span style="color: #BA2121; font-style: italic"> * that the user has the required capability as well.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $page_title The text to be displayed in the title tags of</span>
<span style="color: #BA2121; font-style: italic">                               the page when the menu is selected.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_title The text to be used for the menu.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_slug  The slug name to refer to this menu by; must </span>
<span style="color: #BA2121; font-style: italic">                               match an active plugin slug.</span>
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
              <p>You will need to change the logic in your plugin to check for <code>add_security_page()</code>. For example:</p>
              <!-- HTML generated using hilite.me -->
              <div style="background: #f8f8f8; overflow:auto;width:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;"><pre style="margin: 0; line-height: 125%"><span style="color: #008000; font-weight: bold">if</span> ( <span style="color: #008000">function_exists</span>( <span style="color: #BA2121">&#39;\add_security_page&#39;</span> ) ) {
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
              <p>You will also need to change the logic on your settings pages, but that is outside the scope of this guide. However, you should remember that the idea is to <strong>move</strong> security-related settings, <strong>not</strong> to <em>duplicate</em> them.</p>
            </div>
            <h2><span><?php _e( 'Plugin Action Links'); ?></span></h2>
            <div class="inside">
              <p>Many plugins add links to the plugin action links. [As above wrt rephrasing] You can declutter that area for your plugin by using a standard dashicon; for example, the "Settings" link:</p>
              <!-- HTML generated using hilite.me -->
              <div style="background: #f8f8f8; overflow:auto;width:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%"><span style="color: #008000">array_unshift</span>(
    <span style="color: #19177C">$links</span>,
    <span style="color: #008000">sprintf</span>(
        <span style="color: #BA2121">&#39;&lt;a href=&quot;%s?page=my-prodigious-plugin&quot; title=&quot;%s&quot;&gt;%s&lt;/a&gt;&#39;</span>,
        admin_url( <span style="color: #BA2121">&#39;admin.php&#39;</span> ),
        __( <span style="color: #BA2121">&#39;Settings&#39;</span> ),
        <span style="color: #008000">function_exists</span>( <span style="color: #BA2121">&#39;\add_security_page&#39;</span> )
            <span style="color: #666666">?</span> <span style="color: #BA2121">&#39;&lt;span class=&quot;dashicon dashicons-admin-generic&quot;&gt;&lt;/span&gt;&#39;</span>
            <span style="color: #666666">:</span> __( <span style="color: #BA2121">&#39;Settings&#39;</span> )
    )
);
</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div id="postbox-container-1" class="postbox-container">
        <div class="meta-box-sortables">
          <div class="postbox">
            <h2><span><?php esc_attr_e('Sidebar Content Header', 'WpAdminStyle'); ?></span></h2>
            <div class="inside">
              <p>Links to docs and forums go here.</p>
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

