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
                <p>Currenty, auditing the overall security profile of a ClassicPress site is impractical if there are more than a few plugins. Having all the security-related settings from all the plugins in one place means those settings can be autidted far more easily, as the time taken will be proportional to the number of settings, not the number of places to look for those settings.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="meta-box-sortables ui-sortable">
          <div class="postbox">
            <h2><span><?php _e( 'API' ); ?></span></h2>
            <div class="inside">
              <p>Notes: I left $capability for consistency; we should probably force it to 'manage_options' or just drop it completely - discuss.</p>
              <!-- HTML generated using hilite.me -->
              <div style="background: #f8f8f8; overflow:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%"><span style="color: #BA2121; font-style: italic">/**</span>
<span style="color: #BA2121; font-style: italic"> * Add submenu page to the Security main menu.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * This function takes a capability which will be used to determine whether</span>
<span style="color: #BA2121; font-style: italic"> * or not a page is included in the menu.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * The function which is hooked in to handle the output of the page must check</span>
<span style="color: #BA2121; font-style: italic"> * that the user has the required capability as well.</span>
<span style="color: #BA2121; font-style: italic"> *</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $page_title The text to be displayed in the title tags of</span>
<span style="color: #BA2121; font-style: italic">                               the page when the menu is selected.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_title The text to be used for the menu.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $capability The capability required for this menu to be</span>
<span style="color: #BA2121; font-style: italic">                               displayed to the user.</span>
<span style="color: #BA2121; font-style: italic"> * @param string   $menu_slug  The slug name to refer to this menu by (should</span>
<span style="color: #BA2121; font-style: italic">                               be unique for this menu).</span>
<span style="color: #BA2121; font-style: italic"> * @param callable $function   The function to be called to output the content</span>
<span style="color: #BA2121; font-style: italic">                               for this page.</span>
<span style="color: #BA2121; font-style: italic"> * @return false|string The resulting page&#39;s hook_suffix, or false if the user</span>
<span style="color: #BA2121; font-style: italic">                        does not have the capability required.</span>
<span style="color: #BA2121; font-style: italic"> */</span>
<span style="color: #008000; font-weight: bold">function</span> <span style="color: #0000FF">add_security_page</span>(
    <span style="color: #19177C">$page_title</span>,
    <span style="color: #19177C">$menu_title</span>,
    <span style="color: #19177C">$capability</span>,
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
              <p>Lipsum</p>
              <!-- HTML generated using hilite.me -->
              <div style="background: #f8f8f8; overflow:auto;width:auto;border:solid gray;border-width:.1em .1em .1em .8em;padding:.2em .6em;">
<pre style="margin: 0; line-height: 125%"><span style="color: #0000FF">add_security_page</span>(
    <span style="color: #BA2121">&#39;My Prodigious Plugin&#39;</span>,
    <span style="color: #BA2121">&#39;My Menu Title&#39;</span>,
    <span style="color: #BA2121">&#39;manage_options&#39;</span>,
    <span style="color: #BA2121">&#39;my-menu-slug&#39;</span>,
    <span style="color: #BA2121">&#39;my-output-function&#39;</span>
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

