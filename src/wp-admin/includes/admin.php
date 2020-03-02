<?php
/**
 * Core Administration API
 *
 * @package ClassicPress
 * @subpackage Administration
 * @since WP-2.3.0
 */

if ( ! defined('WP_ADMIN') ) {
	/*
	 * This file is being included from a file other than wp-admin/admin.php, so
	 * some setup was skipped. Make sure the admin message catalog is loaded since
	 * load_default_textdomain() will not have done so in this context.
	 */
	load_textdomain( 'default', WP_LANG_DIR . '/admin-' . get_locale() . '.mo' );
}

/** ClassicPress Administration Hooks */
require_once(ABSPATH . 'wp-admin/includes/admin-filters.php');

/** ClassicPress Bookmark Administration API */
require_once(ABSPATH . 'wp-admin/includes/bookmark.php');

/** ClassicPress Comment Administration API */
require_once(ABSPATH . 'wp-admin/includes/comment.php');

/** ClassicPress Administration File API */
require_once(ABSPATH . 'wp-admin/includes/file.php');

/** ClassicPress Image Administration API */
require_once(ABSPATH . 'wp-admin/includes/image.php');

/** ClassicPress Media Administration API */
require_once(ABSPATH . 'wp-admin/includes/media.php');

/** ClassicPress Import Administration API */
require_once(ABSPATH . 'wp-admin/includes/import.php');

/** ClassicPress Misc Administration API */
require_once(ABSPATH . 'wp-admin/includes/misc.php');

/** ClassicPress Options Administration API */
require_once(ABSPATH . 'wp-admin/includes/options.php');

/** ClassicPress Plugin Administration API */
require_once(ABSPATH . 'wp-admin/includes/plugin.php');

/** ClassicPress Post Administration API */
require_once(ABSPATH . 'wp-admin/includes/post.php');

/** ClassicPress Administration Screen API */
require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
require_once(ABSPATH . 'wp-admin/includes/screen.php');

/** ClassicPress Taxonomy Administration API */
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');

/** ClassicPress Template Administration API */
require_once(ABSPATH . 'wp-admin/includes/template.php');

/** ClassicPress List Table Administration API and base class */
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table-compat.php');
require_once(ABSPATH . 'wp-admin/includes/list-table.php');

/** ClassicPress Theme Administration API */
require_once(ABSPATH . 'wp-admin/includes/theme.php');

/** ClassicPress User Administration API */
require_once(ABSPATH . 'wp-admin/includes/user.php');

/** ClassicPress Site Icon API */
require_once(ABSPATH . 'wp-admin/includes/class-wp-site-icon.php');

/** ClassicPress Update Administration API */
require_once(ABSPATH . 'wp-admin/includes/update.php');

/** ClassicPress Deprecated Administration API */
require_once(ABSPATH . 'wp-admin/includes/deprecated.php');

/** ClassicPress Multisite support API */
if ( is_multisite() ) {
	require_once(ABSPATH . 'wp-admin/includes/ms-admin-filters.php');
	require_once(ABSPATH . 'wp-admin/includes/ms.php');
	require_once(ABSPATH . 'wp-admin/includes/ms-deprecated.php');
}
