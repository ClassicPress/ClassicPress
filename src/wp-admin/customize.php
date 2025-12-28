<?php
/**
 * Theme Customize Screen.
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since CP-2.7.0
 */

define( 'IFRAME_REQUEST', true );

/** Load ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'customize' ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
		403
	);
}

/**
 * @global WP_Customize_Manager $wp_customize
 */
global $wp_customize;

// Preview URL
if ( isset( $_GET['theme'] ) ) { // live preview
	$requested_theme = sanitize_key( $_GET['theme'] );
	if ( wp_get_theme( $requested_theme )->exists() && $requested_theme !== get_transient( 'core_true_stylesheet' ) ) {
		$args = array();
		$args['theme'] = $requested_theme;
		$wp_customize = new WP_Customize_Manager( $args );	
	}
}
$preview_url = add_query_arg(
    array(
        'customize_changeset_uuid'    => $wp_customize->changeset_uuid(),
        'customize_theme'             => $wp_customize->theme()->stylesheet,
        'customize_messenger_channel' => 'preview-0',
    ),
    home_url( '/' )
);

$wp_customize->register_controls();

// Menus
$locations      = get_registered_nav_menus(); // slug => human label
$menu_locations = get_nav_menu_locations();   // slug => menu ID
$menus_by_id    = wp_get_nav_menus();         // list of WP_Term
$menus_index    = array();
foreach ( $menus_by_id as $menu_term ) {
    $menus_index[ $menu_term->term_id ] = $menu_term;
}

$unique_nav_id  = uniqid( 'customize-control-nav_menu--', true );

// Panels, sections, and controls
$panels   = $wp_customize->panels();
$sections = $wp_customize->sections();
$controls = $wp_customize->controls_data_by_section;

// Build top-level items: panels + sections without panel.
$top_items = array();

foreach ( $panels as $panel ) {
	$top_items[ $panel->id ] = array(
		'id'       => $panel->id,
		'title'	   => $panel->title,
		'priority' => $panel->priority,
		'type'     => 'panel',
	);
}

foreach ( $sections as $section ) {
	if ( ! $section->panel ) {
		$top_items[ $section->id ] = array(
			'id'       => $section->id,
			'title'    => $section->title,
			'priority' => $section->priority,
			'type'	   => 'section',
		);
	}
}

// Sort by priority
uasort(
	$top_items,
	static function ( $a, $b ) {
		$ap = isset ( $a['priority'] ) ? (int) $a['priority'] : 999;
		$bp = isset ( $b['priority'] ) ? (int) $b['priority'] : 999;
		return $ap <=> $bp;
	}
);

/**
 * Widgets
 */
require_once ABSPATH . 'wp-admin/includes/widgets.php'; // ensures helpers & globals are set

global $wp_registered_widgets, $wp_registered_widget_controls;

// Collect widgets in a name-keyed array for sorting.
$available_widgets = array();

foreach ( $wp_registered_widgets as $id => $widget ) {
	if ( empty( $widget['name'] ) ) {
		continue;
	}

	// Derive id_base
	$id_base = _get_widget_id_base( $id );

	// Get the control (admin form) for this widget, if there is one.
	$control = isset( $wp_registered_widget_controls[ $id ] )
		? $wp_registered_widget_controls[ $id ]
		: null;

	$available_widgets[ $widget['name'] . '|' . $id ] = array(
		'id'      => $id,
		'id_base' => $id_base,
		'name'    => $widget['name'],
		'desc'    => isset( $widget['description'] ) ? $widget['description'] : '',
		'control' => $control,
	);
}
// Sort alphabetically by name (and id as tiebreaker).
ksort( $available_widgets, SORT_NATURAL | SORT_FLAG_CASE );

/**
 * Menus
 */
$nav_menus = $wp_customize->nav_menus;
$nav_menu_item_types = $nav_menus->available_item_types();

// Reorder to ensure Pages first, then Posts, then taxonomies.
$ordered_types = array();
foreach ( $nav_menu_item_types as $type ) {
	$key = $type['type'] . ':' . $type['object'];

	// Canonical order: Pages → Posts → Categories → Tags → CPTs → other taxonomies
	$priority = match( $key ) {
		'post_type:page'    => 0,
		'post_type:post'    => 10,
		'taxonomy:category' => 20,
		'taxonomy:post_tag' => 30,
		default             => 999,
	};

	$ordered_types[ $priority . '|' . $key ] = $type;
}
ksort( $ordered_types ); // Sort by priority
$nav_menu_item_types = array_values( $ordered_types );

/**
 * Fires when Customizer controls are initialized, before scripts are enqueued.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_init' );

wp_enqueue_script( 'heartbeat' );
wp_enqueue_script( 'customize-controls' );
wp_enqueue_style( 'customize-controls' );

/**
 * Enqueue Customizer control scripts.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_enqueue_scripts' );

// Let's roll.
header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );

wp_user_settings();
_wp_admin_html_begin();

$body_class = 'wp-core-ui wp-customizer js';

if ( wp_is_mobile() ) :
	$body_class .= ' mobile';
	add_filter( 'admin_viewport_meta', '_customizer_mobile_viewport_meta' );
endif;

if ( $wp_customize->is_ios() ) {
	$body_class .= ' ios';
}

if ( is_rtl() ) {
	$body_class .= ' rtl';
}
$body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_user_locale() ) ) ) . ' ready sticky-menu';

$admin_title = sprintf( $wp_customize->get_document_title_template(), __( 'Loading&hellip;' ) );

?>
<title><?php esc_html_e( $admin_title ); ?></title>

<script>
var ajaxurl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php', 'relative' ) ); ?>,
	pagenow = 'customize';
</script>

<?php
/**
 * Fires when Customizer control styles are printed.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_print_styles' );

/**
 * Fires when Customizer control scripts are printed.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_print_scripts' );

/**
 * Fires in head section of Customizer controls.
 *
 * @since 5.5.0
 */
do_action( 'customize_controls_head' );
wp_print_scripts();
?>
</head>

<body class="<?php esc_attr_e( $body_class ); ?>">

<h1 class="screen-reader-text"><?php esc_html_e( 'Customizer' ); ?></h1>

<div class="wp-full-overlay preview-desktop expanded" aria-labelledby="customizer-title">
	<div id="customizer-sidebar-container">
		<h2 id="customizer-title" class="screen-reader-text">
			<?php esc_html_e( 'Customizing: ' .  get_bloginfo( 'name' ) ); ?>
		</h2>
		<form id="customize-controls" class="wrap wp-full-overlay-sidebar" style="position: static;">
			<div id="customize-header-actions" class="wp-full-overlay-header" style="position: static;">

				<?php
				$compatible_wp  = is_wp_version_compatible( $wp_customize->theme()->get( 'RequiresWP' ) );
				$compatible_php = is_php_version_compatible( $wp_customize->theme()->get( 'RequiresPHP' ) );

				if ( $compatible_wp && $compatible_php ) {
					$save_text = $wp_customize->is_theme_active() ? __( 'Published' ) : __( 'Activate &amp; Publish' );
					?>
					<div id="customize-save-button-wrapper" class="customize-save-button-wrapper" disabled>
						<input type="submit" name="save" id="save" class="button button-primary save"
							value="<?php esc_html_e( 'Published' ); ?>"
							disabled
						>
						<button id="publish-settings"
							class="publish-settings button-primary button dashicons dashicons-admin-generic"
							aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>"
							aria-expanded="false" style="display: none;"
						></button>
					</div>
					<?php
				} else {
					$save_text = _x( 'Cannot Activate', 'theme' );
					?>
					<div id="customize-save-button-wrapper" class="customize-save-button-wrapper disabled" >
						<button class="button button-primary disabled"
							aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>"
							aria-expanded="false"
							disabled
						>
							<?php echo $save_text; ?>
						</button>
					</div>
					<?php
				}
				?>
				<span class="spinner"></span>
				<button type="button" class="customize-controls-preview-toggle">
					<span class="controls"><?php _e( 'Customize' ); ?></span>
					<span class="preview"><?php _e( 'Preview' ); ?></span>
				</button>
				<a class="customize-controls-close" href="<?php echo esc_url( $wp_customize->get_return_url() ); ?>">
					<span class="screen-reader-text">
						<?php
						/* translators: Hidden accessibility text. */
						esc_html_e( 'Close the Customizer and go back to the previous page' );
						?>
					</span>
				</a>
			</div><!-- #customize-header-actions -->

			<div id="customize-sidebar-outer-content">
				<div id="customize-outer-theme-controls">
					<ul class="customize-outer-pane-parent">
						<!-- Outer panel and sections are not implemented, but its here as a placeholder to avoid any side-effect in api.Section. -->
					</ul>
				</div>
			</div><!-- #customize-sidebar-outer-content -->

			<div id="widgets-right" class="wp-clearfix"  style="overflow-y: scroll;max-height: calc(100vh - 90px);">
				<!-- For Widget Customizer, many widgets try to look for instances under div#widgets-right, so we have to add that ID to a container div in the Customizer for compat -->
				<div id="customize-notifications-area" class="customize-control-notifications-container">
					<ul></ul>
				</div>
				<div class="wp-full-overlay-sidebar-content" tabindex="-1">
					<div id="customize-info" class="accordion-section customize-info" style="position: relative;">
						<div class="accordion-section-title">
							<span class="preview-notice">
								<?php
									/* translators: %s: The site/panel title in the Customizer. */
									printf( __( 'You are customizing %s' ), '<strong class="panel-title site-title">' . get_bloginfo( 'name', 'display' ) . '</strong>' );
								?>
							</span>
							<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
								<span class="screen-reader-text">
									<?php
									/* translators: Hidden accessibility text. */
									esc_html_e( 'Help' );
									?>
								</span>
							</button>
						</div>
						<div class="customize-panel-description">
							<p>
								<?php
								_e( 'The Customizer allows you to preview changes to your site before publishing them. You can navigate to different pages on your site within the preview. Edit shortcuts are shown for some editable elements. The Customizer is intended for use with non-block themes.' );
								?>
							</p>
							<p>
								<?php
								_e( '<a href="https://wordpress.org/documentation/article/customizer/">Documentation on Customizer</a>' );
								?>
							</p>
						</div>
					</div>
					<div id="customize-theme-controls">
						<ul class="customize-pane-parent">
							<li id="accordion-section-themes" class="accordion-section control-panel-themes"
								aria-owns="sub-accordion-section-themes"
							>
								<h3 class="accordion-section-title" tabindex="0">
									<span class="customize-action">
										<?php
										if ( is_customize_preview() ) {
											esc_html_e( 'Previewing theme' );
										} else {
											esc_html_e( 'Active theme' );
										}
										?>
									</span>
									<?php esc_html_e( $top_items['themes']['title'] ); ?>
									<button type="button" class="button change-theme" aria-label="Change theme">
										<?php esc_html_e( 'Change' ); ?>
									</button>
								</h3>
							</li>
							<li id="accordion-section-publish_settings"
								class="accordion-section control-section control-section-outer"
								aria-owns="sub-accordion-section-publish_settings"
							>
								<h3 class="accordion-section-title" tabindex="0">
									<?php esc_html_e( 'Publish Settings' ); ?>
									<span class="screen-reader-text">
										<?php esc_html_e( 'Press return or enter to open this section' ); ?>
									</span>
								</h3>
							</li>

							<?php
							foreach ( $top_items as $item ) {
								if ( $item['id'] === 'themes' ) { // Don't repeat the active theme
									continue;
								}			
								if ( $item['type'] === 'section'  ) {
									?>
									<li id="accordion-section-<?php esc_attr_e( $item['id'] ); ?>"
										class="accordion-section control-section control-section-default"
										aria-owns="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>"
									>
										<h3 class="accordion-section-title" tabindex="0">
											<?php esc_html_e( $item['title'] ); ?>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Press return or enter to open this section' ); ?>
											</span>
										</h3>
									</li>
									<?php
								} else { // panel
									?>
									<li id="accordion-panel-<?php esc_attr_e( $item['id'] ); ?>"
										class="accordion-section control-section control-panel control-panel-<?php esc_attr_e( $item['id'] ); ?>"
										aria-owns="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>"
									>
										<h3 class="accordion-section-title" tabindex="0">
											<?php esc_html_e( $item['title'] ); ?>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Press return or enter to open this section' ); ?>
											</span>
										</h3>
									</li>
								<?php
								}
							}
							?>
						</ul>

						<ul id="sub-accordion-section-themes" class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-themes current-panel" style="display: none;">
							<li class="panel-meta customize-info accordion-section">
								<button class="customize-panel-back" tabindex="0" type="button">
									<span class="screen-reader-text"><?php esc_html_e( 'Back' ); ?></span>
								</button>
								<div class="accordion-section-title">
									<span class="preview-notice">
										<?php esc_html_e( 'You are browsing' ); ?>
										<strong class="panel-title">
											<?php esc_html_e( 'Themes' ); ?>
										</strong>
									</span>
									<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Help' ); ?>
										</span>
									</button>
								</div>
								<div class="description customize-panel-description">
									<?php echo wp_kses_post( $panels['themes']->description ); ?></p>
								</div>
								<div class="customize-control-notifications-container" style="display: none;">
									<ul></ul>
								</div>
							</li>
							<li id="accordion-section-installed_themes" class="theme-section control-subsection">
								<button type="button" class="customize-themes-section-title themes-section-installed_themes selected" aria-expanded="true">
									<?php esc_html_e( 'Installed themes' ); ?>
								</button>
							</li>
							<li id="accordion-section-wporg_themes" class="theme-section control-subsection">
								<button type="button" class="customize-themes-section-title themes-section-wporg_themes" aria-expanded="false">
									<?php esc_html_e( 'WordPress.org themes' ); ?>
								</button>
							</li>
							<li class="customize-themes-full-container-container">
								<div class="customize-themes-full-container animate" style="top: 0; right: 0;border: 0;height: 100vh;min-width: calc(100% - max(18%, 300px));" open>
									<div class="customize-themes-notifications"></div>
									<div class="filter-drawer">
										<?php
										// Tags to filter list of themes.
										// Use the core list, rather than the .org API, due to inconsistencies
										// and to ensure tags are translated.
										$feature_list = get_theme_feature_list( false );

										foreach ( $feature_list as $feature_group => $features ) {
											?>
											<fieldset class="filter-group">
												<legend>
													<?php esc_html_e( $feature_group ); ?>
												</legend>
												<div class="filter-group-feature">
													<?php
													foreach ( $features as $feature => $feature_name ) {
														?>
														<input type="checkbox" id="filter-id-<?php esc_attr_e( $feature ); ?>" value="<?php esc_attr_e( $feature ); ?>"> 
														<label for="filter-id-' . $feature . '">
															<?php esc_html_e( $feature_name ); ?>
														</label>
														<?php
													}
													?>
												</div>
											</fieldset>
											<?php
										}
										?>
									</div>
									<div class="customize-themes-section themes-section-installed_themes control-section-content themes-php current-section">											
										<div class="theme-browser rendered local">
											<div class="customize-preview-header themes-filter-bar">
												<button type="button" class="button button-primary customize-section-back customize-themes-mobile-back" style="display: none;">
													<?php esc_html_e( 'Go to theme sources' ); ?>
												</button>
												<div class="themes-filter-container">
													<label for="installed_themes-themes-filter" class="screen-reader-text">
														<?php esc_html_e( 'Search themes…' ); ?>
													</label>
													<input type="search" id="installed_themes-themes-filter"
														placeholder="<?php esc_attr_e( 'Search themes…' ); ?>"
														aria-describedby="installed_themes-live-search-desc"
														class="wp-filter-search wp-filter-search-themes"
													>
													<div class="search-icon" aria-hidden="true"></div>
													<span id="installed_themes-live-search-desc" class="screen-reader-text">
														<?php esc_html_e( 'The search results will be updated as you type.' ); ?>
													</span>
												</div>
												<div class="filter-themes-count">
													<span class="themes-displayed">
														<span class="theme-count">
															<?php echo absint( $count_themes ); ?>
														</span>
														<?php esc_html_e( 'themes' ); ?>
													</span>
													<button type="button" class="button feature-filter-toggle" aria-expanded="false">
														<span class="filter-count-0"><?php esc_html_e( 'Filter themes' ); ?></span>
														<span class="filter-count-filters">
															<?php esc_html_e( 'Filter themes (<span class="theme-filter-count">0</span>)' ); ?>
														</span>
													</button>												
												</div>
											</div>
											<div class="error unexpected-error" style="display: none; ">
												<p><?php esc_html_e( 'An unexpected error occurred. Something may be wrong with WordPress.org, ClassicPress.net or this server’s configuration. If you continue to have problems, please try the <a href="https://forums.classicpress.net/c/support/">support forums</a>' ); ?>.</p>
											</div>
											<ul class="themes" style="overflow-y: scroll;max-height: 100vh;">
												<?php
												// Create a bare Theme Control instance and render it.
												$tmp = new WP_Customize_Theme_Control( $wp_customize, 'tmp', array() );
												$tmp->render_content();
												?>
											</ul>
										</div>											
									</div>
								</div>
							</li>
						</ul>

						<?php
						// The Menus panel.
						$menus_panel = isset ( $panels['nav_menus'] ) ? $panels['nav_menus'] : null;
						if ( $menus_panel ) :
							?>

							<ul id="sub-accordion-panel-nav_menus" class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-nav_menus" style="display: none;">
								<li class="panel-meta customize-info accordion-section">
									<button class="customize-panel-back" type="button" tabindex="0">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Back' ); ?>
										</span>
									</button>
									<div class="accordion-section-title">
										<span class="preview-notice">
											<?php
											printf(
												/* translators: %s: Panel title. */
												esc_html__( 'You are customizing %s' ),
												'<strong class="panel-title">' . esc_html( $menus_panel->title ) . '</strong>'
											);
											?>
										</span>
										<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Help' ); ?>
											</span>
										</button>
									</div>
									<div class="description customize-panel-description">
										<?php echo wp_kses_post( $menus_panel->description ); ?></p>
									</div>
									<div class="customize-control-notifications-container" style="display:none;">
										<ul></ul>
									</div>
								</li>
								<li class="customize-control-title customize-section-title-nav_menus-heading">
									<?php esc_html_e( $menus_panel->title ); ?>
								</li>

								<?php
								// Each individual menu section (assigned-to-menu-location, etc.).
								foreach ( $sections as $section ) {
									if ( $section->panel === 'nav_menus' ) {

										// Individual menus, e.g. nav_menu[2], nav_menu[primary], etc.
										if ( 0 !== strpos( $section->id, 'nav_menu[' ) ) {
											continue;
										}

										$current_location_label = '';
										$menu_key = substr( $section->id, strlen( 'nav_menu[' ), -1 ); // Strip the prefix and trailing bracket.

										// If it's numeric, treat as menu ID.
										if ( ctype_digit( (string) $menu_key ) ) {
											$menu_id = (int) $menu_key;

											// Find which locations point to this menu ID.
											$attached_locations = array();
											foreach ( $menu_locations as $loc_slug => $loc_menu_id ) {
												if ( (int) $loc_menu_id === $menu_id && isset ( $locations[ $loc_slug ] ) ) {
													$attached_locations[] = $locations[ $loc_slug ];
												}
											}

											if ( $attached_locations ) {
												$current_location_label = sprintf(
													/* translators: %s: comma-separated list of locations. */
													__( '(Currently set to: %s)' ),
													implode( ', ', $attached_locations )
												);
											}
										}
										?>
										<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>"
											class="accordion-section control-section control-section-nav_menu control-subsection assigned-to-menu-location"
											aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
										>
											<h3 class="accordion-section-title" tabindex="0">
												<?php esc_html_e( $section->title ); ?>
												<span class="screen-reader-text">
													<?php esc_html_e( 'Press return or enter to open this section' ); ?>
												</span>
												<?php
												if ( $current_location_label ) {
													?>
													<span class="menu-in-location">
														<?php esc_html_e( $current_location_label ); ?>
													</span>
													<?php
												}
												?>
											</h3>
										</li>
										<?php
									}
								}
								foreach ( $sections as $section ) { // The “Add a Menu” section.
									if ( $section->id !== 'add_menu' ) {
										continue;
									}
									?>
									<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>"
										class="accordion-section control-section control-section-new_menu control-subsection"
										aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
									>
										<?php
										if ( empty ( $menus_by_id ) ) {
											?>
											<p class="add-new-menu-notice">
												<?php esc_html_e( 'It does not look like your site has any menus yet. Want to build one? Click the button to start.' ); ?>
											</p>
											<?php
										}
										?>
										<h3>
											<button type="button" class="button customize-add-menu-button">
												<?php esc_html_e( 'Create New Menu' ); ?>
											</button>
										</h3>
									</li>
									<?php
								}
								foreach ( $sections as $section ) { // Menu Locations.
									if ( $section->id !== 'menu_locations' ) {
										continue;
									}
									?>
									<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>"
										class="accordion-section control-section control-section-nav_menu_locations control-subsection"
										aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
									>
										<span class="customize-control-title customize-section-title-menu_locations-heading">
											<?php esc_html_e( 'Menu Locations' ); ?>
										</span>
										<div class="customize-control-description customize-section-title-menu_locations-description">
											<?php echo wp_kses_post( $section->description ); ?>
										</div>
										<h3 class="accordion-section-title" tabindex="0">
											<?php esc_html_e( $section->title ); ?>
											<span class="screen-reader-text"><?php esc_html_e( 'Press return or enter to open this section' ); ?></span>
										</h3>
									</li>
									<?php
								}
								?>
							</ul>
						<?php
						endif;

						// The Widgets panel.
						$widgets_panel = isset ( $panels['widgets'] ) ? $panels['widgets'] : null;
						if ( $widgets_panel ) :
							?>

							<ul id="sub-accordion-panel-widgets"
								class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-section control-panel control-panel-widgets"
								style="display: none;"
							>
								<li class="panel-meta customize-info accordion-section">
									<button class="customize-panel-back" type="button" tabindex="0">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Back' ); ?>
										</span>
									</button>
									<div class="accordion-section-title">
										<span class="preview-notice">
											<?php
											printf(
												/* translators: %s: Panel title. */
												esc_html__( 'You are customizing %s' ),
												'<strong class="panel-title">' . esc_html( $widgets_panel->title ) . '</strong>'
											);
											?>
										</span>
										<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Help' ); ?>
											</span>
										</button>
									</div>
									<div class="description customize-panel-description">
										<?php echo wp_kses_post( $widgets_panel->description ); ?>
									</div>
									<div class="customize-control-notifications-container" style="display:none;">
										<ul></ul>
									</div>
									<div class="no-widget-areas-rendered-notice" style="display: none;"></div>
								</li>

								<?php
								// Each widget area section under the Widgets panel.
								foreach ( $sections as $section ) {
									if ( $section->panel !== 'widgets' ) {
										continue;
									}
									?>
									<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>"
										class="accordion-section control-section control-section-widgets control-subsection"
										aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
									>
										<h3 class="accordion-section-title" tabindex="0">
											<?php esc_html_e( $section->title ); ?>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Press return or enter to open this section' ); ?>
											</span>
										</h3>
									</li>
									<?php
								}
								?>
							</ul>
							<?php
						endif;

						foreach ( $top_items as $item ) {
							// Skip items with bespoke sub‑accordion implementations.
							if ( in_array( $item['id'], array( 'themes', 'nav_menus', 'widgets' ), true ) ) {
								continue;
							}
							if ( 'section' !== $item['type'] ) { // i.e. do not process panels here
								continue;
							}
							?>

							<ul id="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>"
								class="customize-pane-child accordion-section-content accordion-section control-section control-section-default"
								style="display: none;"
							>
								<li class="customize-section-description-container section-meta no-drag">
									<div class="customize-section-title">
										<button class="customize-section-back" tabindex="0">
											<span class="screen-reader-text"><?php esc_html_e( 'Back' ); ?></span>
										</button>
										<h3>
											<span class="customize-action">
												<?php esc_html_e( 'Customizing' ); ?>
											</span>
											<?php esc_html_e( $item['title'] ); ?>
										</h3>
										<div class="customize-control-notifications-container" style="display: none;">
											<ul></ul>
										</div>
									</div>
									<?php
									if ( $item['id'] === 'header_image' ) { // to account for the description being hard-coded in core
										?>
										<div class="description customize-section-description">
											<?php
											global $cp_header_image_section_description;
											echo wp_kses_post( $cp_header_image_section_description );
											?>
										</div>
										<?php
									}
									?>
								</li>

								<?php
								if ( isset ( $controls[ $item['id'] ] ) ) {
									// Sort in ascending order of priority
									usort( $controls[ $item['id'] ], function( $a, $b ) {
										return $a['priority'] - $b['priority'];
									} );
									foreach ( $controls[ $item['id'] ] as $control_data ) {
										$control = $wp_customize->get_control( $control_data['id'] );
										if ( $control ) {
											$control->maybe_render();
										}
									}
								}
								?>
							</ul>
							<?php
						}
						?>

						<ul id="sub-accordion-section-add_menu" class="customize-pane-child accordion-section-content accordion-section control-section control-section-new_menu open" style="display: none;">
							<li class="customize-section-description-container section-meta no-drag ">
								<div class="customize-section-title">
									<button class="customize-section-back" tabindex="0">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Back' ); ?>
										</span>
									</button>
									<h3>
										<span class="customize-action">
											<?php
											printf(
												/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
												__( 'Customizing &#9656; %s' ),
												__( 'Menus' )
											);
											?>
										</span>
										<?php esc_html_e( 'New Menu' ); ?>
									</h3>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
								</div>
							</li>
							<li id="customize-control-add_menu-name" class="customize-control customize-control-nav_menu_name">
								<label>
									<span class="customize-control-title"><?php esc_html_e( 'Menu Name' ); ?></span>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
									<input type="text" class="menu-name-field live-update-section-title" aria-describedby="add_menu-description">
								</label>
								<p id="add_menu-description"><?php esc_html_e( 'If your theme has multiple menus, giving them clear names will help you manage them.' ); ?></p>
							</li>
							<li id="customize-control-add_menu-locations" class="customize-control customize-control-nav_menu_locations">
								<ul class="menu-location-settings">
									<li class="customize-control assigned-menu-locations-title">
										<span class="customize-control-title"><?php esc_html_e( 'Menu Locations' ); ?></span>
										<div class="customize-control-notifications-container" style="display: none;">
											<ul></ul>
										</div>
										<p>
											<?php esc_html_e( 'Where do you want this menu to appear?' ); ?>
											<?php
											printf(
												/* translators: 1: Documentation URL, 2: Additional link attributes, 3: Accessibility text. */
												__( '(If you plan to use a menu <a href="%1$s" %2$s>widget%3$s</a>, skip this step.)' ),
												__( 'https://wordpress.org/documentation/article/manage-wordpress-widgets/' ),
												' class="external-link" target="_blank"',
												sprintf(
													'<span class="screen-reader-text"> %s</span>',
													/* translators: Hidden accessibility text. */
													__( '(opens in a new tab)' )
												)
											);
											?>
										</p>
									</li>

									<?php
									foreach ( $locations as $key => $location ) {
										?>
										<li class="customize-control customize-control-checkbox assigned-menu-location">
											<span class="customize-inside-control-row">
												<input id="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>"
													type="checkbox"
													data-menu-id="<?php esc_attr_e( $unique_nav_id ); ?>"
													data-location-id="<?php esc_attr_e( $location ); ?>"
													class="menu-location"
												>
												<label for="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>">
													<?php esc_html_e( $location ); ?>
													<span class="theme-location-set">
														<?php
														if ( isset ( $menus_index[$menu_locations[$key]] ) ) {
															printf(
																__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)</span>' ),
																/* translators: Name of menu. */
																$menus_index[$menu_locations[$key]]->name
															);
														}
														?>
													</span>
												</label>
											</span>
										</li>
										<?php
									}
									?>
								</ul>
							</li>
							<li id="customize-control-add_menu-submit" class="customize-control customize-control-undefined">
								<div class="customize-control-notifications-container" style="display: none;">
									<ul></ul>
								</div>
								<p id="customize-new-menu-submit-description">
									<?php esc_html_e( 'Click “Next” to start adding links to your new menu.' ); ?>
								</p>
								<button id="customize-new-menu-submit" type="button" class="button" aria-describedby="customize-new-menu-submit-description">
									<?php esc_html_e( 'Next' ); ?>
								</button>
							</li>
						</ul>

						<?php
						/**
						 * Calculate menu item depth by walking up the parent chain.
						 */
						function get_menu_item_depth( $menu_items, $item_id ) {
							$depth = 0;
							$current_id = $item_id;

							while ( true ) {
								$parent_id = 0;
								foreach ( $menu_items as $item ) {
									if ( $item->ID === $current_id ) {
										$parent_id = (int) $item->menu_item_parent;
										break;
									}
								}

								if ( $parent_id === 0 || $parent_id === $current_id ) {
									break;
								}

								$current_id = $parent_id;
								$depth++;
							}

							return $depth;
						}

						// Render controls for each nav_menus section (locations + individual menus).
						foreach ( $sections as $section ) {
							if ( $section->panel !== 'nav_menus' ) {
								continue;
							}

							// Skip if there are no controls collected for this section.
							if ( empty( $controls[ $section->id ] ) ) {
								continue;
							}

							// Extract menu term ID from section ID: nav_menu[123] => 123
							$menu_id = 0;
							if ( 0 === strpos( $section->id, 'nav_menu[' ) ) {
								$menu_id = substr( $section->id, strlen( 'nav_menu[' ), -1 );
							}
    
							$section_class = 'control-section-nav_menu';
							if ( 'nav_menu_locations' === $section->id || 'nav_menus[locations]' === $section->id ) {
								$section_class = 'control-section-nav_menu_locations';
							}
							?>

							<ul id="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
								class="customize-pane-child accordion-section-content accordion-section control-section <?php esc_attr_e( $section_class ); ?> assigned-to-menu-location field-title-attribute-active"
								style="display: none;"
							>
								<li class="customize-section-description-container section-meta no-drag">
									<div class="customize-section-title">
										<button class="customize-section-back" type="button" tabindex="0">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Back' ); ?>
											</span>
										</button>
										<h3>
											<span class="customize-action">
												<?php
												printf(
													/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
													__( 'Customizing &#9656; %s' ),
													__( 'Menus' )
												);
												?>
											</span>
											<?php esc_html_e( $section->title ); ?>
										</h3>
										<div class="customize-control-notifications-container" style="display:none;">
											<ul></ul>
										</div>
									</div>
									<?php
									if ( ! empty( $section->description ) ) :
										?>
										<div class="description customize-section-description">
											<?php echo wp_kses_post( $section->description ); ?>
										</div>
									<?php
									endif;
									?>
								</li>
        
								<?php
								foreach ( $controls[ $section->id ] as $control_data ) {
									$field_id    = $control_data['setting_id'] ?: $control_data['id'];
									$field_value = $control_data['value'];
									$field_type  = $control_data['type'];
									$field_label = $control_data['label'];
									$description = $control_data['description'];
									$priority    = $control_data['priority'];

									// Memu items - only for nav_menu[ID] sections
									if ( $menu_id && 0 === strpos( $section->id, 'nav_menu[' ) ) {
										?>
										<li id="customize-control-nav_menu-<?php esc_attr_e( $menu_id ); ?>-name"
											class="customize-control customize-control-nav_menu_name no-drag"
										>
											<label>
												<span class="customize-control-title">
													<?php esc_html_e( 'Menu Name' ); ?>
												</span>
												<div class="customize-control-notifications-container" style="display: none;">
													<ul></ul>
												</div>
												<input type="text"
													class="menu-name-field live-update-section-title"
													value="<?php esc_attr_e( $menus_index[$menu_id]->name ); ?>"
												>
											</label>
										</li>

										<?php
										$menu_items = wp_get_nav_menu_items( $menu_id );
										if ( empty( $menu_items ) ) {
											?>
											<li class="no-items-message">
												<p><?php esc_html_e( 'This menu is currently empty. Add items using the Add Items panel.' ); ?></p>
											</li>
											<?php
										} else {
											$depth_map = array();
											foreach ( $menu_items as $menu_item ) {
												$depth_map[ $menu_item->ID ] = get_menu_item_depth( $menu_items, $menu_item->ID );
												?>
												<li id="customize-control-nav_menu_item-<?php esc_attr_e( $menu_item->ID ); ?>"
													class="customize-control customize-control-nav_menu_item menu-item menu-item-depth-<?php echo absint( $depth_map[ $menu_item->ID ] ); ?> menu-item-custom menu-item-edit-inactive move-left-disabled move-up-disabled move-right-disabled move-down-disabled"
												>
													<div class="menu-item-bar">
														<details class="menu-item-handle">
															<summary>
																<span class="item-title" aria-hidden="true">
																	<span class="menu-item-title">
																		<?php esc_html_e( $menu_item->post_title ?: $menu_item->title ); ?>
																	</span>
																</span>
																<span class="item-controls">
																	<button type="button" class="button-link item-delete submitdelete deletion">
																		<span class="screen-reader-text">
																			<?php esc_html_e( 'Remove Menu Item:' ); ?>
																			<?php esc_html_e( $menu_item->post_title ?: $menu_item->title ); ?>
																			(<?php esc_html_e( $menu_item->type_label ); ?>)	
																		</span>
																	</button>
																</span>
																<div class="menu-item-reorder-nav">
																	<button type="button" class="menus-move-up" tabindex="-1" aria-hidden="true">
																		<?php esc_html_e( 'Move up' ); ?>
																	</button>
																	<button type="button" class="menus-move-down" tabindex="-1" aria-hidden="true">
																		<?php esc_html_e( 'Move down' ); ?>
																	</button>
																	<button type="button" class="menus-move-left" tabindex="-1" aria-hidden="true">
																		<?php esc_html_e( 'Move one level up' ); ?>
																	</button>
																	<button type="button" class="menus-move-right" tabindex="-1" aria-hidden="true">
																		<?php esc_html_e( 'Move one level down' ); ?>
																	</button>
																</div>
																<span class="item-type" aria-hidden="true">
																	<?php esc_html_e( $menu_item->type_label ); ?>
																</span>
															</summary>
															<div id="menu-item-settings-<?php esc_attr_e( $menu_item->ID ); ?>" class="menu-item-settings">
																<div class="customize-control-notifications-container" style="display: none;">
																	<ul></ul>
																</div>
																<p class="field-url description description-thin">
																	<label for="edit-menu-item-url-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'URL' ); ?>
																		<br>
																		<input type="text" id="edit-menu-item-url-<?php echo absint( $menu_item->ID ); ?>"
																			class="widefat code edit-menu-item-url"
																			name="menu-item[<?php echo absint( $menu_item->ID ); ?>][menu-item-url]"
																			value="<?php echo esc_url( $menu_item->url ); ?>"
																		>
																	</label>
																</p>
																<p class="description description-thin">
																	<label for="edit-menu-item-title-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'Navigation Label' ); ?>
																		<br>
																		<input id="edit-menu-item-title-<?php echo absint( $menu_item->ID ); ?>"
																			class="widefat edit-menu-item-title"
																			name="menu-item-title"
																			placeholder=""
																		>
																	</label>
																</p>
																<p class="field-link-target description description-thin">
																	<label for="edit-menu-item-target-<?php echo absint( $menu_item->ID ); ?>">
																		<input type="checkbox"
																			id="edit-menu-item-target-<?php echo absint( $menu_item->ID ); ?>"
																			name="menu-item[<?php echo absint( $menu_item->ID ); ?>][menu-item-target]"
																			value="_blank" <?php checked( '_blank', $menu_item->target ); ?>
																		>
																		<?php esc_html_e( 'Link Target' ); ?>
																	</label>
																</p>
																<p class="field-title-attribute field-attr-title description description-thin">
																	<label for="edit-menu-item-title-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'Title Attribute' ); ?>
																		<br>
																		<input type="text" id="edit-menu-item-title-<?php echo (int) $menu_item->ID; ?>"
																			class="widefat edit-menu-item-title"
																			name="menu-item[<?php echo absint( $menu_item->ID ); ?>][menu-item-title]"
																			value="<?php esc_html_e( $menu_item->post_title ?: $menu_item->title ); ?>"
																		>
																	</label>
																</p>
																<p class="field-css-classes description description-thin">
																	<label for="edit-menu-item-classes-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'CSS Classes' ); ?>
																		<br>
																		<input type="text" id="edit-menu-item-classes-<?php echo absint( $menu_item->ID ); ?>"
																			class="widefat code edit-menu-item-classes"
																			name="menu-item-classes"
																		>
																	</label>
																</p>
																<p class="field-xfn description description-thin">
																	<label for="edit-menu-item-xfn-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'Link Relationship (XFN)' ); ?>
																		<br>
																		<input type="text" id="edit-menu-item-xfn-<?php echo absint( $menu_item->ID ); ?>"
																			class="widefat code edit-menu-item-xfn"
																			name="menu-item-xfn"
																		>
																	</label>
																</p>
																<p class="field-description description description-thin">
																	<label for="edit-menu-item-description-<?php echo absint( $menu_item->ID ); ?>">
																		<?php esc_html_e( 'Description' ); ?>
																		<br>
																		<textarea id="edit-menu-item-description-<?php echo absint( $menu_item->ID ); ?>"
																			class="widefat edit-menu-item-description"
																			name="menu-item[<?php echo absint( $menu_item->ID ); ?>][menu-item-description]"
																			rows="3"
																		>
																			<?php echo esc_textarea( $menu_item->post_content ); ?>
																		</textarea>
																	</label>
																</p>

																<?php
																/**
																 * Fires after the display of menu item custom fields.
																 * 
																 * @param string  $item_id   Menu item ID.
																 * @param WP_Post $item      Menu item data object.
																 * @param array   $args      Arguments from Walker_Nav_Menu_Edit.
																 * @param int     $menu_id   Menu ID.
																 */
																do_action( 'wp_nav_menu_item_custom_fields', (string) $menu_item->ID, $menu_item, $depth_map[ $menu_item->ID ], (object) array(), $menu_id );
																?>

																<button type="button" class="button-link button-link-delete item-delete submitdelete deletion">
																	<?php esc_html_e( 'Remove' ); ?>
																</button>
																<span class="spinner"></span>
															</div>
															<!-- .menu-item-settings -->
															<input type="hidden"
																name="menu-item-db-id[<?php echo absint( $menu_item->ID ); ?>]"
																class="menu-item-data-db-id"
																value="<?php echo absint( $menu_item->ID ); ?>"
															>
															<input type="hidden"
																name="menu-item-parent-id[<?php echo absint( $menu_item->ID ); ?>]"
																class="menu-item-data-parent-id"
																value="<?php echo absint( $menu_item->post_parent ); ?>"
															>
															<ul class="menu-item-transport"></ul>
														</details>
													</div>
												</li>
												<?php
											}
										}
										?>
										<li id="customize-control-nav_menu-<?php esc_attr_e( $menu_id ); ?>"
											class="customize-control customize-control-nav_menu no-drag"
										><?php // Look at nav-menu-control.php ?>
											<div class="customize-control-notifications-container" style="display: none;">
												<ul></ul>
											</div>
											<p class="new-menu-item-invitation" style="display: none;">
												<?php esc_html_e( 'Time to add some links! Click “Add Items” to start putting pages, categories, and custom links in your menu. Add as many things as you would like.' );?>
											</p>
											<div class="customize-control-nav_menu-buttons">
												<button type="button"
													class="button add-new-menu-item"
													aria-label="<?php esc_html_e( 'Add or remove menu items' ); ?>"
													aria-expanded="false"
													aria-controls="available-menu-items"
												>
													<?php esc_html_e( 'Add Items' ); ?>
												</button>
												<button type="button"
													class="button-link reorder-toggle"
													aria-label="Reorder menu items"
													aria-describedby="reorder-items-desc-19"
													style="display: none;"
												>
													<span class="reorder">
														<?php esc_html_e( 'Reorder' ); ?>
													</span>
													<span class="reorder-done">
														<?php esc_html_e( 'Done' ); ?>
													</span>
												</button>
											</div>
											<p class="screen-reader-text" id="reorder-items-desc-19">
												<?php esc_html_e( 'When in reorder mode, additional controls to reorder menu items will be available in the items list above.' ); ?>
											</p>
										</li>
										<li id="customize-control-nav_menu-<?php esc_attr_e( $menu_id ); ?>-locations" class="customize-control customize-control-nav_menu_locations no-drag">
											<ul class="menu-location-settings">
												<li class="customize-control assigned-menu-locations-title no-drag">
													<span class="customize-control-title">
														<?php esc_html_e( 'Menu Locations' ); ?>
													</span>
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<p>
														<?php esc_html_e( 'Here’s where this menu appears. If you would like to change that, pick another location.' ); ?>
													</p>
												</li>
												
												<?php
												foreach ( $locations as $key => $location ) {
													?>
													<li class="customize-control customize-control-checkbox assigned-menu-location no-drag">
														<span class="customize-inside-control-row">
															<input id="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>"
																type="checkbox"
																data-menu-id="<?php esc_attr_e( $menu_id ); ?>"
																data-location-id="<?php esc_attr_e( $location ); ?>"
																class="menu-location"
																<?php checked( $menu_locations[$key], $menu_id ); ?>
															>
															<label for="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>">
																<?php esc_html_e( $location ); ?>
																<span class="theme-location-set">
																	<?php
																	if ( isset ( $menus_index[$menu_locations[$key]] ) ) {
																		printf(
																			__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)</span>' ),
																			/* translators: Name of menu. */
																			$menus_index[$menu_locations[$key]]->name
																		);
																	}
																	?>
																</span>
															</label>
														</span>
													</li>
													<?php
												}
												?>
											</ul>
										</li>
										<li id="customize-control-nav_menu-<?php esc_attr_e( $menu_id ); ?>-auto_add" class="customize-control customize-control-nav_menu_auto_add no-drag">
											<span class="customize-control-title">
												<?php esc_html_e( 'Menu Options' ); ?>
											</span>
											<div class="customize-control-notifications-container" style="display: none;">
												<ul></ul>
											</div>
											<span class="customize-inside-control-row">
												<input id="customize-nav-menu-auto-add-control-22" type="checkbox" class="auto_add">
												<label for="customize-nav-menu-auto-add-control-22">
													<?php esc_html_e( 'Automatically add new top-level pages to this menu' ); ?>
												</label>
											</span>
										</li>
										<li id="customize-control-nav_menu-<?php esc_attr_e( $menu_id ); ?>-delete" class="customize-control customize-control-undefined no-drag">
											<div class="customize-control-notifications-container" style="display: none;">
												<ul></ul>
											</div>
											<div class="menu-delete-item">
												<button type="button" class="button-link button-link-delete">
													<?php esc_html_e( 'Delete Menu' ); ?>
												</button>
											</div>
										</li>
										<?php
										// Skip rest of controls loop for menu sections
										break;
            
									// Menu locations
									} elseif ( 'nav_menu_location' === $field_type ) {
										$value_hidden_class    = $field_value ? ' hidden' : '';
										$no_value_hidden_class = empty( $field_value ) ? ' hidden' : '';
										?>
										<li id="customize-control-<?php esc_attr_e( $field_id ); ?>"
											class="customize-control customize-control-<?php esc_attr_e( $field_type ); ?>"
										>
											<div class="customize-control-inner">
												<?php
												if ( ! empty( $field_label ) ) {
													?>
													<label class="customize-control-title" for="<?php esc_attr_e( $field_id ); ?>">
														<?php esc_html_e( $field_label ); ?>
													</label>
													<?php
												}
												?>
												<select id="<?php esc_attr_e( $field_id ); ?>" data-customize-setting-link="<?php esc_attr_e( $field_id ); ?>">
													<option value="0">— Select —</option>
													<?php
													foreach ( $menus_index as $menu ) {
														?>
														<option value="<?php esc_attr_e( $menu->term_id ); ?>" <?php selected( $field_value, $menu->term_id ); ?>>
															<?php esc_html_e( $menu->name ); ?>
														</option>
														<?php
													}
													?>
												</select>
												<button type="button"
													class="button-link create-menu<?php echo $value_hidden_class; ?>"
													data-location-id="<?php esc_attr_e( substr( $field_id, strlen( 'nav_menu_locations[' ), -1 ) ); ?>"
													aria-label="<?php esc_attr_e( 'Create a menu for this location' ); ?>"
												>
													<?php esc_html_e( '+ Create New Menu' ); ?>
												</button>
												<button type="button"
													class="button-link edit-menu<?php echo $no_value_hidden_class; ?>"
													aria-label="<?php esc_attr_e( 'Edit selected menu' ); ?>"
												>
													<?php esc_html_e( 'Edit Menu' ); ?>
												</button>
												<?php
												if ( ! empty( $description ) ) {
													?>
													<div class="description customize-control-description">
														<?php echo wp_kses_post( $description ); ?>
													</div>
													<?php
												}
												?>
											</div>
										</li>
										<?php
									}
								}
								?>
							</ul>
							<?php
						}

						// Render widget controls for each widget area (sidebar) section.
						foreach ( $sections as $section ) {
							if ( $section->panel !== 'widgets' ) {
								continue;
							}

							// No controls collected for this section?
							if ( empty ( $controls[ $section->id ] ) ) {
								continue;
							}
							?>

							<ul id="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>"
								class="customize-pane-child accordion-section-content accordion-section control-section control-section-sidebar"
								style="display: none;"
							>
								<li class="customize-section-description-container section-meta no-drag">
									<div class="customize-section-title">
										<button class="customize-section-back" type="button" tabindex="0">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Back' ); ?>
											</span>
										</button>
										<h3>
											<span class="customize-action">
												<?php
												printf(
													/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
													__( 'Customizing &#9656; %s' ),
													__( 'Widgets' )
												);
												?>
											</span>
											<?php esc_html_e( $section->title ); ?>
										</h3>
										<div class="customize-control-notifications-container" style="display:none;">
											<ul></ul>
										</div>
									</div>

									<?php
									if ( ! empty ( $section->description ) ) :
									?>
										<div class="description customize-section-description">
											<?php echo wp_kses_post( $section->description ); ?>
										</div>
									<?php
									endif;
									?>
								</li>

								<?php
								$index = 0;
								foreach ( $controls[ $section->id ] as $control_data ) {
									$field_id    = $control_data['id'];
									$field_value = $control_data['value'];
									$field_type  = $control_data['type'];									
									$field_label = $control_data['label'];
									$widget_id   = isset( $section->controls[$index]->widget_id ) ? $section->controls[$index]->widget_id : '';

									if ( $widget_id === '' ) {
										continue;
									}
									if ( $field_type !== 'widget_form' ) { // 'sidebar-widgets' 
										continue;
									}

									$first_last_widget = '';
									if ( $index === 0 ) {
										if ( $index === count( $section->controls ) - 2 ) { // 2 allows for both counting from 0 and $widget_id === ''
											$first_last_widget = ' first-widget last-widget';
										} else {
											$first_last_widget = ' first-widget';
										}
									} elseif ( $index === count( $section->controls ) - 2 ) {
										$first_last_widget = ' last-widget';
									}
									?>

									<li id="customize-control-widget-<?php esc_attr_e( $widget_id ); ?>"
										class="customize-control customize-control-widget_form<?php esc_attr_e( $first_last_widget ); ?> widget-rendered"
									>
										<div id="widget-<?php esc_attr_e( $index . '_' . $widget_id ); ?>" class="widget">
											<details class="widget-top">
												<summary class="widget-title">
													<h3>
														<?php esc_html_e( $field_label ); ?>
														<span class="in-widget-title"></span>
													</h3>
													<div class="widget-reorder-nav">
														<span class="move-widget" tabindex="0" style="">
															<?php esc_html_e( $field_label ); ?>
															<?php esc_html_e( 'Move to another area.' ); ?>
														</span>
														<span class="move-widget-down" tabindex="0">
															<?php esc_html_e( $field_label ); ?>
															<?php esc_html_e( ' Move down' ); ?>
														</span>
														<span class="move-widget-up" tabindex="-1">
															<?php esc_html_e( $field_label ); ?>
															<?php esc_html_e( 'Move up' ); ?>
														</span>
													</div>
												</summary>
												<div class="widget-title-action">
													<a class="widget-control-edit hide-if-js" href="#">
														<span class="edit">
															<?php esc_html_e( 'Edit' ); ?>
														</span>
														<span class="add">
															<?php esc_html_e( 'Add' ); ?>
														</span>
														<span class="screen-reader-text">
															<?php esc_html_e( $field_label ); ?>
														</span>
													</a>
												</div>

												<?php
												$widget_id_split = explode( '-', $widget_id );
												$widget_id_base  = $widget_id_split[0];
												$widget_number   = $widget_id_split[1];
												$index++;

												// Find widget by ID base in factory
												global $wp_widget_factory;
												$widget_obj = null;
												foreach ( $wp_widget_factory->widgets as $id_base => $obj ) {
													if ( $obj->id_base === $widget_id_base ) {
														$widget_obj = $obj;
														break;
													}
												}
												if ( ! $widget_obj ) {
													return;
												}
												$widget_settings = is_array( $field_value ) ? $field_value : array();
												?>

												<div class="widget-inside">
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<div class="form">
														<div class="widget-content">
															<?php $widget_obj->form( $widget_settings ); ?>
														</div>
														<input type="hidden" name="id_base" class="id_base" value="<?php esc_attr_e( $widget_id_base ); ?>">
														<input type="hidden" name="widget_number" class="widget_number" value="<?php esc_attr_e( $widget_number ); ?>">
														<input type="hidden" name="widget_id" class="widget_id" value="<?php esc_attr_e( $widget_id ); ?>">
														<input type="hidden" name="sidebar_id" class="sidebar_id" value="<?php esc_attr_e( $section->id ); ?>">
														<input type="hidden" name="multi_number" class="multi_number" value="">
														<input type="hidden" name="width" class="width" value="auto">
														<input type="hidden" name="height" class="height" value="auto">
														<input type="hidden" name="add_new" class="add_new" value="">
														<div class="widget-control-actions">
															<div class="alignleft">
																<button type="button" class="button-link button-link-delete widget-control-remove">
																	<?php esc_html_e( 'Delete' ); ?>
																</button>
																<span class="widget-control-close-wrapper">
																	|
																	<button type="button" class="button-link widget-control-close">
																		<?php esc_html_e( 'Done' ); ?>
																	</button>
																</span>
															</div>
															<div class="alignright">
																<input type="submit"
																	name="savewidget"
																	id="widget-<?php esc_attr_e( $widget_id_base ); ?>-__i__-savewidget"
																	class="button button-primary widget-control-save right"
																	value="Save"
																>
																<span class="spinner"></span>
															</div>
															<br class="clear">
														</div>
													</div><!-- .form -->

													<?php
													/**
													 * Fires at the end of the widget control form.
													 * 
													 * @param WP_Widget $widget_obj      Widget instance.
													 * @param null      $return          Return null if new fields are added.
													 * @param array     $widget_settings An array of the widget’s settings.
													 */
													do_action( 'in_widget_form', $widget_obj, $return = null, $widget_settings );
													?>

												</div><!-- .widget-inside -->
											</details>
										</div>
									</li>
									<?php
								}
								?>
								<li id="customize-control-sidebars_widgets-sidebar1"
									class="customize-control customize-control-sidebar_widgets no-drag"
								>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
									<button type="button" class="button add-new-widget" aria-expanded="false" aria-controls="widgets-left">
										<?php esc_html_e( 'Add a Widget' ); ?>
									</button>
									<button type="button" class="button-link reorder-toggle" aria-label="Reorder widgets" aria-describedby="reorder-widgets-desc-sidebars_widgets-sidebar1">
										<span class="reorder"><?php esc_html_e( 'Reorder' ); ?></span>
										<span class="reorder-done"><?php esc_html_e( 'Done' ); ?></span>
									</button>
									<p class="screen-reader-text" id="reorder-widgets-desc-sidebars_widgets-sidebar1">
										<?php esc_html_e( 'When in reorder mode, additional controls to reorder widgets will be available in the widgets list above.' ); ?>
									</p>
								</li>
							</ul>

							<?php
						}
						?>

						<ul id="menu-to-edit"
							class="customize-pane-child accordion-section-content accordion-section control-section control-section-nav_menu field-title-attribute-active menu open"
							style="display: none;"
						>
							<li class="customize-section-description-container section-meta no-drag">
								<div class="customize-section-title">
									<button class="customize-section-back" tabindex="0">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Back' ); ?>
										</span>
									</button>
									<h3>
										<span class="customize-action">
											<?php
											printf(
												/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
												__( 'Customizing &#9656; %s' ),
												__( 'Menus' )
											);
											?>
										</span>
										<?php esc_html_e( 'New Menu' ); ?>
									</h3>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
								</div>
							</li>
							<!-- Further <li> blocks added with JS by moving menus in and out of here as required -->
						</ul>
					</div>
				</div>
			</div>

			<div id="customize-footer-actions" class="wp-full-overlay-footer">
				<button type="button" class="collapse-sidebar button"
					aria-expanded="true"
					aria-label="<?php esc_html_e( 'Hide Controls' ); ?>"
				>
					<span class="collapse-sidebar-arrow"></span>
					<span class="collapse-sidebar-label">
						<?php esc_html_e( 'Hide Controls' ); ?>
					</span>
				</button>
				<div class="devices-wrapper">
					<div class="devices">
						<button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
							<span class="screen-reader-text">
								<?php esc_html_e( 'Enter desktop preview mode' ); ?>
							</span>
						</button>
						<button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
							<span class="screen-reader-text">
								<?php esc_html_e( 'Enter tablet preview mode' ); ?>
							</span>
						</button>
						<button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
							<span class="screen-reader-text">
								<?php esc_html_e( 'Enter mobile preview mode' ); ?>
							</span>
						</button>
					</div>
				</div>
			</div>

			<?php
			// Hidden field placeholder to align with the idea that this sidebar will
			// eventually submit changes (stage 2+).
			?>
			<input type="hidden" name="customize_form_stage" value="php-first-paint">
		</form><!-- #customize-controls -->

		<div id="widgets-left">
			<!-- compatibility with JS which looks for widget templates here -->
			<div id="available-widgets">
				<div id="available-widgets-filter">
					<label class="screen-reader-text" for="widgets-search">
						<?php esc_html_e( 'Search Widgets' ); ?>
					</label>
					<input type="text"
						id="widgets-search"
						placeholder="<?php esc_attr_e( 'Search widgets…' ); ?>"
						aria-describedby="widgets-search-desc"
					>
					<div class="search-icon" aria-hidden="true"></div>
					<button type="button" class="clear-results">
						<span class="screen-reader-text">
							<?php esc_html_e( 'Clear Results' ); ?>
						</span>
					</button>
					<p class="screen-reader-text" id="widgets-search-desc">
						<?php esc_html_e( 'The search results will be updated as you type.' ); ?>
					</p>
				</div>
				<ul id="available-widgets-list">
					<?php
					$number = 0;
					foreach ( $available_widgets as $widget_data ) {
						$id       = $widget_data['id'];
						$id_base  = $widget_data['id_base'];
						$name     = $widget_data['name'];
						$desc     = $widget_data['desc'];
						$control  = $widget_data['control'];
						$tpl_id   = $id_base . '-' . ++$number;
						?>
						<li id="widget-tpl-<?php esc_attr_e( $tpl_id ); ?>"
							class="widget-tpl <?php esc_attr_e( $tpl_id ); ?>"
							data-widget-id="<?php esc_attr_e( $tpl_id ); ?>"
							data-id_base="<?php esc_attr_e( $id_base ); ?>"
							tabindex="0"
							style="display: list-item;"
						>
							<div id="widget-<?php esc_attr_e( $number . '_' . $id_base ); ?>-__i__" class="widget">
								<details class="widget-top">
									<summary class="widget-title">
										<h3>
											<?php esc_html_e( $name ); ?>
										</h3>
									</summary>
									<div class="widget-title-action">
										<a class="widget-control-edit hide-if-js" href="<?php echo esc_url( admin_url( 'customize.php?url=' ) ); ?>">
											<span class="edit">
												<?php esc_attr_e( 'Edit' ); ?>
											</span>
											<span class="add">
												<?php esc_attr_e( 'Add' ); ?>
											</span>
											<span class="screen-reader-text">
												<?php esc_attr_e( 'Audio' ); ?>
											</span>
										</a>
									</div>

									<div class="widget-inside">
										<div class="form">
											<div class="widget-content">
												<?php
												if ( $control && ! empty( $control['callback'] ) && is_callable( $control['callback'] ) ) {
													$widget_args = array(
														'widget_id'   => $control['id'],
														'widget_name' => $control['name'],
													);
													call_user_func( $control['callback'], $widget_args, $control );
												} else {
													?>
													<p>
														<?php esc_html_e( 'This widget has no configurable options.' ); ?>
													</p>
													<?php
												}
												wp_nonce_field( 'save-sidebar-widgets', '_wpnonce' );
												?>
											</div>

											<input type="hidden" name="widget-id" value="<?php esc_attr_e( $id ); ?>">
											<input type="hidden" name="id_base" value="<?php esc_attr_e( $id_base ); ?>">
											<input type="hidden" name="widget_number" value="<?php esc_attr_e( $number ); ?>">
											<input type="hidden" name="widget-width" class="widget-width" value="auto">
											<input type="hidden" name="widget-height" class="widget-height" value="auto">
											<input type="hidden" name="multi_number" class="multi_number" value="">
											<input type="hidden" name="add_new" value="">

											<div class="widget-control-actions">
												<div class="alignleft">
													<button type="button" class="button-link button-link-delete widget-control-remove">
														<?php esc_html_e( 'Delete' ); ?>
													</button>
													<span class="widget-control-close-wrapper">
														|
														<button type="button" class="button-link widget-control-close">
															<?php esc_html_e( 'Done' ); ?>
														</button>
													</span>
												</div>
												<div class="alignright">
													<input type="submit"
														name="savewidget"
														id="widget-<?php esc_attr_e( $id_base ); ?>-__i__-savewidget"
														class="button button-primary widget-control-save right"
														value="Save"
													>
													<span class="spinner"></span>
												</div>
												<br class="clear">
											</div>
										</div><!-- .form -->
									</div>
								</details>
								<?php
								if ( $desc ) {
									?>
									<div class="widget-description">
										<?php esc_html_e( $desc ); ?>
									</div>
									<?php
								}
								?>
							</div>
						</li>
						<?php
					}
					?>
				</ul><!-- #available-widgets-list -->
			</div><!-- #available-widgets -->
		</div><!-- #widgets-left -->

		<ul id="available-menu-items" class="accordion-container">
			<div class="customize-section-title">
				<button type="button" class="customize-section-back" tabindex="-1">
					<span class="screen-reader-text">
						<?php esc_html_e( 'Back' ); ?>
					</span>
				</button>
				<h3>
					<span class="customize-action">
						<?php
						printf(
							/* translators: &#9656; is the unicode right-pointing triangle. %s: Section title in the Customizer. */
							__( 'Customizing &#9656; %s' ),
							__( 'Menus' )
						);
						?>
					</span>
					<?php esc_html_e( 'Add Menu Items' ); ?>
				</h3>
			</div>

			<li id="available-menu-items-search" class="accordion-section cannot-expand">
				<div class="accordion-section-title">
					<label class="screen-reader-text" for="menu-items-search">
						<?php esc_html_e( 'Search Menu Items' ); ?>
					</label>
					<input type="text" id="menu-items-search" placeholder="Search menu items…" aria-describedby="menu-items-search-desc">
					<p class="screen-reader-text" id="menu-items-search-desc">
						<?php esc_html_e( 'The search results will be updated as you type.' ); ?>
					</p>
					<span class="spinner"></span>
				</div>
				<div class="search-icon" aria-hidden="true"></div>
				<button type="button" class="clear-results">
					<span class="screen-reader-text">
						<?php esc_html_e( 'Clear Results' ); ?>
					</span>
				</button>
				<ul class="accordion-section-content available-menu-items-list" data-type="search"></ul>
			</li>

			<li id="new-custom-menu-item" class="accordion-section">
				<details>
					<summary class="accordion-section-title">
						<?php esc_html_e( 'Custom Links' ); ?>
					</summary>
					<div class="accordion-section-content customlinkdiv" style="max-height: 132px;">
						<input type="hidden" value="custom" id="custom-menu-item-type" name="menu-item[-1][menu-item-type]">
						<p id="menu-item-url-wrap" class="wp-clearfix">
							<label class="howto" for="custom-menu-item-url">
								<?php esc_html_e( 'URL' ); ?>
							</label>
							<input id="custom-menu-item-url" name="menu-item[-1][menu-item-url]" type="text" class="code menu-item-textbox" placeholder="https://">
						</p>
						<p id="menu-item-name-wrap" class="wp-clearfix">
							<label class="howto" for="custom-menu-item-name">
								<?php esc_html_e( 'Link Text' ); ?>
							</label>
							<input id="custom-menu-item-name" name="menu-item[-1][menu-item-title]" type="text" class="regular-text menu-item-textbox">
						</p>
						<p class="button-controls">
							<span class="add-to-menu">
								<input type="submit" class="button submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-custom-menu-item" id="custom-menu-item-submit">
								<span class="spinner"></span>
							</span>
						</p>
					</div>
				</details>
			</li>

			<?php
			foreach ( $nav_menu_item_types as $item_type ) {
				$items = $nav_menus->load_available_items_query( $item_type['type'], $item_type['object'], 0, '' );
				if ( empty( $items ) ) {
					continue;
				}
				?>
				<li id="available-menu-items-<?php esc_attr_e( $item_type['type'] . '-' . $item_type['object'] ); ?>" class="accordion-section">
					<details>
						<summary class="accordion-section-title">
							<?php esc_html_e( $item_type['type_label'] ); ?>
						</summary>
						<div class="accordion-section-content" style="max-height: 132px;">
							<div class="new-content-item">
								<label for="create-item-input-<?php esc_attr_e( $item_type['object'] ); ?>" class="screen-reader-text">
									<?php esc_html_e( 'Add New' ); ?>
									<?php esc_html_e( $item_type['type_label'] ); ?>
								</label>
								<input type="text"
									id="create-item-input-<?php esc_attr_e( $item_type['object'] ); ?>"
									class="create-item-input"
									placeholder="<?php esc_attr_e( 'Add New' ); ?> <?php esc_attr_e( $item_type['type_label'] ); ?>"
								>
								<button type="button" class="button add-content">
									<?php esc_html_e( 'Add' ); ?>
								</button>
							</div>
							<ul class="available-menu-items-list"
								data-type="<?php esc_attr_e( $item_type['type'] ); ?>"
								data-object="<?php esc_attr_e( $item_type['object'] ); ?>"
								data-type_label="<?php esc_attr_e( $item_type['type_label'] ); ?>"
								style="max-height: 72px;"
							>
								<?php foreach ( $items as $item ) {						
									?>
									<li id="<?php esc_attr_e( $item['id'] ); ?>"
										class="menu-item-tpl"
										data-menu-item-id="<?php esc_attr_e( $item['id'] ); ?>"
									>
										<div class="menu-item-bar">
											<div class="menu-item-handle">
												<button type="button" class="button-link item-add">
													<span class="screen-reader-text">
														<?php esc_html_e( 'Add to menu:' ); ?>
														<?php esc_html_e( $item['title'] ); ?>
														<?php _e( '(' . $item['type_label'] . ')' ); ?>
													</span>
												</button>
												<span class="item-split">
													<span class="item-title" aria-hidden="true">
														<span class="menu-item-title">
															<?php esc_html_e( $item['title'] ); ?>
														</span>
													</span>
													<span class="item-type" aria-hidden="true">
														<?php esc_html_e( $item['type_label'] ); ?>
													</span>
												</span>
											</div>
										</div>
									</li>
									<?php
								}
								?>
							</ul>
						</div>
					</details>
				</li>
				<?php
			}
			?>
		</ul><!-- #available-menu-items -->
	</div>

	<div id="customize-preview" class="wp-full-overlay-main iframe-ready">
		<iframe title="<?php esc_attr_e( 'Site Preview' ); ?>" name="customize-preview-0" onmousewheel="" src="<?php echo esc_url( $preview_url ); ?>" style="position: relative;z-index: 1;"></iframe>
	</div>
</div><!-- .wp-full-overlay expanded preview-desktop -->

<?php
/**
 * Enables the modal for themes
 */
customize_themes_print_templates();
?>

<?php /* Enables the modal for media widgets */ ?>
<dialog id="widget-modal">
	<div id="media-widget-modal" class="widget-modal-container">
		<aside class="widget-modal-left-sidebar">
			<div class="widget-modal-left-sticky">
				<h3 class="widget-modal-left-heading"><?php esc_html_e( 'Actions' ); ?></h3>
				<div class="widget-modal-left-tablist" role="tablist" aria-orientation="vertical">
					<button id="menu-item-add" type="button" role="tab" class="media-menu-item active" aria-selected="true"><?php esc_html_e( 'Add media' ); ?></button>
					<button id="menu-item-gallery" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden><?php esc_html_e( 'Create gallery' ); ?></button>
					<button id="menu-item-playlist" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden><?php esc_html_e( 'Create audio playlist' ); ?></button>
					<button id="menu-item-video-playlist" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden><?php esc_html_e( 'Create video playlist' ); ?></button>
					<button id="menu-item-featured-image" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden><?php esc_html_e( 'Featured image' ); ?></button>
					<div role="presentation" class="separator"></div>
					<button id="menu-item-embed" type="button" role="tab" class="media-menu-item" aria-selected="false" aria-controls="insert-from-url-panel" hidden><?php esc_html_e( 'Insert from URL' ); ?></button>
				</div>
			</div>
		</aside>
		<div class="widget-modal-main">
			<header class="widget-modal-header">
				<div class="widget-modal-headings">
					<div id="widget-modal-title" class="widget-modal-title">
						<h2><?php esc_html_e( 'Media Library' ); ?></h2>
					</div>
					<details class="widget-modal-details" hidden>
						<summary><?php esc_html_e( 'Menu' ); ?></summary>
					</details>
					<button id="widget-modal-close" type="button" class="widget-modal-close" autofocus>
						<span id="widget-modal-icon" class="widget-modal-icon">
							<span class="screen-reader-text"><?php esc_html_e( 'Close dialog' ); ?></span>
						</span>
					</button>
				</div>
			</header>
		</div>
	</div>
</dialog>

<?php
/**
 * Renders the template for uploading files to widgets
 *
 * @since CP-2.5.0
 */
echo cp_render_widget_upload_template();

/**
 * Renders the template for the media image widget
 *
 * @since CP-2.5.0
 */
echo cp_render_media_image_template();

/**
 * Renders the template for the media gallery widget
 *
 * @since CP-2.5.0
 */
echo cp_render_media_gallery_template();

/**
 * Renders the template for the media audio widget
 *
 * @since CP-2.5.0
 */
echo cp_render_media_audio_template();

/**
 * Renders the template for the media video widget
 *
 * @since CP-2.5.0
 */
echo cp_render_media_video_template();
?>

</body>
</html>
