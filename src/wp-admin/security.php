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

?>
<div class="wrap">
  <h1><?php echo esc_html( $title ); ?></h1>
  <hr class="wp-header-end">
  <h2 class="nav-tab-wrapper wp-clearfix"><a class="nav-tab nav-tab-active" href="#">General</a></h2>
  <div class="card">
    <h2><?php _e( '"Security First"' ); ?></h2>
    <p><?php _e( "Security is important to business and it’s important to us here at ClassicPress. By bringing security forward to a place of greater prominence within the admin interface, we create a more streamlined experience for both users and developers." ); ?></p>
    <p><?php _e( "As ClassicPress continues to evolve, the Security page will become the hub for all security features for ClassicPress core and 3<sup>rd</sup> party plugins that choose to support it." ); ?></p>
    <p><?php _e( "Watch this page and the ClassicPress Security forum for more news as development continues. If you’d like to contribute in this area, please connect with us <a href=\"https://forums.classicpress.net/security\" target=\"_blank\">here</a>." ); ?></p>
  </div>
</div>
<?php
include( ABSPATH . 'wp-admin/admin-footer.php' );

