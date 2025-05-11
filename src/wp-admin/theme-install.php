<?php
/**
 * Install theme administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';
require ABSPATH . 'wp-admin/includes/theme-install.php';

$updates_from_api = get_site_transient( 'update_core' );
$cp_needs_update  = isset( $updates_from_api->updates ) && is_array( $updates_from_api->updates ) && ! empty( $updates_from_api->updates );

wp_reset_vars( array( 'tab' ) );

if ( ! current_user_can( 'install_themes' ) ) {
	wp_die( __( 'Sorry, you are not allowed to install themes on this site.' ) );
}

if ( is_multisite() && ! is_network_admin() ) {
	wp_redirect( network_admin_url( 'theme-install.php' ) );
	exit;
}

// Used in the HTML title tag.
$title       = __( 'Add Themes' );
$parent_file = 'themes.php';

if ( ! is_network_admin() ) {
	$submenu_file = 'themes.php';
}

$installed_themes = search_theme_directories();

if ( false === $installed_themes ) {
	$installed_themes = array();
}

foreach ( $installed_themes as $theme_slug => $theme_data ) {
	// Ignore child themes.
	if ( str_contains( $theme_slug, '/' ) ) {
		unset( $installed_themes[ $theme_slug ] );
	}
}

wp_localize_script(
	'theme',
	'_wpThemeSettings',
	array(
		'themes'          => false,
		'settings'        => array(
			'isInstall'  => true,
			'canInstall' => current_user_can( 'install_themes' ),
			'installURI' => current_user_can( 'install_themes' ) ? self_admin_url( 'theme-install.php' ) : null,
			'adminUrl'   => parse_url( self_admin_url(), PHP_URL_PATH ),
		),
		'l10n'            => array(
			'addNew'              => __( 'Add New Theme' ),
			'search'              => __( 'Search Themes' ),
			'searchPlaceholder'   => __( 'Search themes...' ), // Placeholder (no ellipsis).
			'upload'              => __( 'Upload Theme' ),
			'back'                => __( 'Back' ),
			'error'               => sprintf(
				/* translators: %s: Support forums URL. */
				__( 'An unexpected error occurred. Something may be wrong with WordPress.org, ClassicPress.net, or this server&#8217;s configuration. If you continue to have problems, please try the <a href="%s">support forums</a>.' ),
				__( 'https://wordpress.org/support/forums/' )
			),
			'tryAgain'            => __( 'Try Again' ),
			/* translators: %d: Number of themes. */
			'themesFound'         => __( 'Number of Themes found: %d' ),
			'noThemesFound'       => __( 'No themes found. Try a different search.' ),
			'collapseSidebar'     => __( 'Collapse Sidebar' ),
			'expandSidebar'       => __( 'Expand Sidebar' ),
			/* translators: Hidden accessibility text. */
			'selectFeatureFilter' => __( 'Select one or more Theme features to filter by' ),
			'version'             => __( 'Version' ),
			'ratings'             => __( 'ratings' ),
		),
		'installedThemes' => array_keys( $installed_themes ),
		'activeTheme'     => get_stylesheet(),
	)
);

wp_enqueue_script( 'theme' );
wp_enqueue_script( 'updates' );

if ( $tab ) {
	/**
	 * Fires before each of the tabs are rendered on the Install Themes page.
	 *
	 * The dynamic portion of the hook name, `$tab`, refers to the current
	 * theme installation tab.
	 *
	 * Possible hook names include:
	 *
	 *  - `install_themes_pre_block-themes`
	 *  - `install_themes_pre_dashboard`
	 *  - `install_themes_pre_featured`
	 *  - `install_themes_pre_new`
	 *  - `install_themes_pre_search`
	 *  - `install_themes_pre_updated`
	 *  - `install_themes_pre_upload`
	 *
	 * @since 2.8.0
	 * @since 6.1.0 Added the `install_themes_pre_block-themes` hook name.
	 */
	do_action( "install_themes_pre_{$tab}" );
}

$help_overview =
	'<p>' . sprintf(
		/* translators: %s: Theme Directory URL. */
		__( 'You can find additional themes for your site by using the Theme Browser/Installer on this screen, which will display themes from the <a href="%s">WordPress Theme Directory</a>. These themes are designed and developed by third parties, are available free of charge, and are compatible with the license WordPress uses.' ),
		__( 'https://wordpress.org/themes/' )
	) . '</p>' .
	'<p>' . __( 'You can Search for themes by keyword, author, or tag, or can get more specific and search by criteria listed in the feature filter.' ) . ' <span id="live-search-desc">' . __( 'The search results will be updated as you type.' ) . '</span></p>' .
	'<p>' . __( 'Alternately, you can browse the themes that are Popular or Latest. When you find a theme you like, you can preview it or install it.' ) . '</p>' .
	'<p>' . sprintf(
		/* translators: %s: /wp-content/themes */
		__( 'You can Upload a theme manually if you have already downloaded its ZIP archive onto your computer (make sure it is from a trusted and original source). You can also do it the old-fashioned way and copy a downloaded theme&#8217;s folder via FTP into your %s directory.' ),
		'<code>/wp-content/themes</code>'
	) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $help_overview,
	)
);

$help_installing =
	'<p>' . __( 'Once you have generated a list of themes, you can preview and install any of them. Click on the thumbnail of the theme you are interested in previewing. It will open up in a full-screen Preview page to give you a better idea of how that theme will look.' ) . '</p>' .
	'<p>' . __( 'To install the theme so you can preview it with your site&#8217;s content and customize its theme options, click the "Install" button at the top of the left-hand pane. The theme files will be downloaded to your website automatically. When this is complete, the theme is now available for activation, which you can do by clicking the "Activate" link, or by navigating to your Manage Themes screen and clicking the "Live Preview" link under any installed theme&#8217;s thumbnail image.' ) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'installing',
		'title'   => __( 'Previewing and Installing' ),
		'content' => $help_installing,
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/appearance-themes-screen/#install-themes">Documentation on Adding New Themes</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/block-themes/">Documentation on Block Themes</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

require_once ABSPATH . 'wp-admin/admin-header.php';

// Make call to AJAX endpoint to get first 20 themes.
$response = wp_remote_post( admin_url( 'admin-ajax.php' ),
	array(
		'body' => array(
			'action'  => 'query-themes',
			'request' => array(
				'per_page' => 100,
				'page' => 1,
				'browse' => 'popular',
			),
		),
		'cookies' => $_COOKIE, // Maintain session
	),
);
$body = '';
$themes = '';
$count = '';
if ( ! is_wp_error( $response ) ) {
	$body = json_decode( wp_remote_retrieve_body( $response ) );
	$themes = $body->data->html;
	$count = $body->data->count;
}

?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

	<?php

	/**
	 * Filters the tabs shown on the Add Themes screen.
	 *
	 * This filter is for backward compatibility only, for the suppression of the upload tab.
	 *
	 * @since 2.8.0
	 *
	 * @param string[] $tabs Associative array of the tabs shown on the Add Themes screen. Default is 'upload'.
	 */
	$tabs = apply_filters( 'install_themes_tabs', array( 'upload' => __( 'Upload Theme' ) ) );
	if ( ! empty( $tabs['upload'] ) && current_user_can( 'upload_themes' ) ) {
		echo ' <button type="button" class="upload-view-toggle page-title-action hide-if-no-js" aria-expanded="false">' . __( 'Upload Theme' ) . '</button>';
	}
	?>

	<hr class="wp-header-end">

	<div class="error hide-if-js">
		<p><?php _e( 'The Theme Installer screen requires JavaScript.' ); ?></p>
	</div>

	<div class="upload-theme">
	<?php install_themes_upload(); ?>
	</div>

	<h2 class="screen-reader-text hide-if-no-js">
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Filter themes list' );
		?>
	</h2>

	<div class="wp-filter hide-if-no-js">
		<div class="filter-count">
			<span class="count theme-count"><?php esc_html_e( $count ); ?></span>
		</div>

		<ul class="filter-links">
			<li><a href="<?php echo esc_url( admin_url( 'theme-install.php?browse=popular' ) ); ?>" data-sort="popular"><?php _ex( 'Popular', 'themes' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'theme-install.php?browse=new' ) ); ?>" data-sort="new"><?php _ex( 'Latest', 'themes' ); ?></a></li>
		</ul>

		<button type="button" class="button drawer-toggle" aria-expanded="false"><?php esc_html_e( 'Feature Filter' ); ?></button>

		<form class="search-form">
			<fieldset class="search-box">
				<label for="wp-filter-search-input"><?php esc_html_e( 'Search Themes' ); ?></label>
				<input type="search" aria-describedby="live-search-desc" id="wp-filter-search-input" class="wp-filter-search">
			</fieldset>
		</form>

		<div class="filter-drawer">
			<div class="buttons">
				<button type="button" class="apply-filters button"><?php _e( 'Apply Filters' ); ?><span></span></button>
				<button type="button" class="clear-filters button" aria-label="<?php esc_attr_e( 'Clear current filters' ); ?>"><?php _e( 'Clear' ); ?></button>
			</div>
		<?php
		// Use the core list, rather than the .org API, due to inconsistencies
		// and to ensure tags are translated.
		$feature_list = get_theme_feature_list( false );

		foreach ( $feature_list as $feature_group => $features ) {
			echo '<fieldset class="filter-group">';
			echo '<legend>' . esc_html( $feature_group ) . '</legend>';
			echo '<div class="filter-group-feature">';
			foreach ( $features as $feature => $feature_name ) {
				$feature = esc_attr( $feature );
				echo '<input type="checkbox" id="filter-id-' . $feature . '" value="' . $feature . '"> ';
				echo '<label for="filter-id-' . $feature . '">' . esc_html( $feature_name ) . '</label>';
			}
			echo '</div>';
			echo '</fieldset>';
		}
		?>
			<div class="buttons">
				<button type="button" class="apply-filters button"><?php _e( 'Apply Filters' ); ?><span></span></button>
				<button type="button" class="clear-filters button" aria-label="<?php esc_attr_e( 'Clear current filters' ); ?>"><?php _e( 'Clear' ); ?></button>
			</div>
			<div class="filtered-by">
				<span><?php _e( 'Filtering by:' ); ?></span>
				<div class="tags"></div>
				<button type="button" class="button-link edit-filters"><?php _e( 'Edit Filters' ); ?></button>
			</div>
		</div>
	</div>
	<h2 class="screen-reader-text hide-if-no-js">
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Themes list' );
		?>
	</h2>
	<div class="theme-browser content-filterable">
		<ul class="themes"><?php echo $themes; ?></ul>
	</div>

	<p class="no-themes"><?php _e( 'No themes found. Try a different search.' ); ?></p>

	<dialog class="theme-install-overlay wp-full-overlay expanded no-navigation iframe-ready">
		<div class="theme-install-container">
			<div class="wp-full-overlay-sidebar">
				<div class="wp-full-overlay-header">
					<button class="close-full-overlay" autofocus>
						<span class="screen-reader-text"><?php _e( 'Close' ); ?></span>
					</button>
					<button class="previous-theme">
						<span class="screen-reader-text"><?php _e( 'Previous theme' ); ?></span>
					</button>
					<button class="next-theme">
						<span class="screen-reader-text"><?php _e( 'Next theme' ); ?></span>
					</button>

					<a href="" class="button button-primary theme-install"><?php _e( 'Install' ); ?></a>
				</div>
				<div class="wp-full-overlay-sidebar-content">
					<div class="install-theme-info">
						<h3 class="theme-name"></h3>
						<span class="theme-by"></span>

						<div class="theme-screenshot">
							<img class="theme-screenshot" src="" alt="">
						</div>

						<div class="theme-details">						
							<div class="theme-rating">
								<a class="num-ratings" href=""></a>
							</div>
							<div class="theme-version"></div>
							<div class="theme-description"></div>
						</div>
					</div>
				</div>
				<div class="wp-full-overlay-footer">
					<button type="button" class="collapse collapse-sidebar button" aria-expanded="true" aria-label="<?php _e( 'Collapse Sidebar' ); ?>">
						<span class="collapse collapse-sidebar-arrow"></span>
						<span class="collapse collapse-sidebar-label"><?php _e( 'Collapse' ); ?></span>
					</button>
				</div>
			</div>

			<div id="theme-preview-main" class="wp-full-overlay-main">
				<iframe src="" title="<?php _e( 'Preview' ); ?>"></iframe>
			</div>
		</div>
	</dialog>

<?php
if ( $tab ) {
	/**
	 * Fires at the top of each of the tabs on the Install Themes page.
	 *
	 * The dynamic portion of the hook name, `$tab`, refers to the current
	 * theme installation tab.
	 *
	 * Possible hook names include:
	 *
	 *  - `install_themes_block-themes`
	 *  - `install_themes_dashboard`
	 *  - `install_themes_featured`
	 *  - `install_themes_new`
	 *  - `install_themes_search`
	 *  - `install_themes_updated`
	 *  - `install_themes_upload`
	 *
	 * @since 2.8.0
	 * @since 6.1.0 Added the `install_themes_block-themes` hook name.
	 *
	 * @param int $paged Number of the current page of results being viewed.
	 */
	do_action( "install_themes_{$tab}", $paged );
}
?>
</div>

<?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();

require_once ABSPATH . 'wp-admin/admin-footer.php';
