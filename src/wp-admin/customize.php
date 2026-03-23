<?php
/**
 * Theme Customize Screen.
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since CP-2.8.0
 */
declare( strict_types = 1 ); // Forces exact type matching

define( 'IFRAME_REQUEST', true );

/** Load ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'customize' ) ) {
	wp_die(
		'<h1>' . esc_html__( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . esc_html__( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
		403
	);
}

/**
 * @global WP_Customize_Manager $wp_customize
 */
global $wp_customize;

$args = array();
if ( isset( $_GET['theme'] ) ) { // live preview
	$args['theme'] = sanitize_key( $_GET['theme'] );
}

$wp_customize->setup_theme();
$wp_customize->register_controls();

$preview_url = add_query_arg(
	array(
		'customize_changeset_uuid'    => $wp_customize->changeset_uuid(),
		'customize_theme'             => $wp_customize->theme()->stylesheet,
		'customize_messenger_channel' => 'preview-0',
	),
	home_url( '/' )
);

// Handle form submission (supports both the disabled publish button and the default "save" submit).
if ( isset( $_POST['cp_publish_submit'] ) || isset( $_POST['save'] ) ) {

	// Security: nonce + capability
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'save-customize_' . $wp_customize->get_stylesheet() ) ) {
		wp_die( esc_html__( 'Security check failed.' ), 403 );
	}

	if ( ! current_user_can( 'customize' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.' ), 403 );
	}

	// Collect submitted values without blanket sanitize_text_field()
	// Let registered settings sanitize via their sanitize_callback.
	$submitted = isset( $_POST['customize_post_value'] ) ? (array) wp_unslash( $_POST['customize_post_value'] ) : array();
	foreach ( $submitted as $setting_id => $raw_value ) {
		$setting = $wp_customize->get_setting( $setting_id );
		if ( $setting ) {
			// This ensures that validation/sanitization and live preview behavior are consistent.
			$wp_customize->set_post_value( $setting_id, $raw_value );
		}
	}

	// Make sure we’re saving to the expected changeset UUID.
	$uuid = isset( $_POST['customize_changeset_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['customize_changeset_uuid'] ) ) : '';
	if ( $uuid && $uuid !== $wp_customize->changeset_uuid() ) {
		$wp_customize->set_changeset_uuid( $uuid );
	}

	// Save the changeset in-process (no loopback HTTP).
	$result = $wp_customize->save_changeset_post(
		array(
			'status' => 'publish', // Or 'draft' / 'pending' / 'future'
			'title'  => 'PHP publish from customize.php',
		)
	);

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), 500 );
	}

	// Redirect back to customize.php with the UUID.
	wp_safe_redirect(
		add_query_arg(
			'customize_changeset_uuid',
			$wp_customize->changeset_uuid(),
			admin_url( 'customize.php' )
		)
	);
	exit;
}

// Themes
$installed_themes = wp_prepare_themes_for_js();
$count_themes     = count( $installed_themes );

// Menus
$locations      = get_registered_nav_menus(); // slug => human label
$menu_locations = get_nav_menu_locations();   // slug => menu ID
$menus          = wp_get_nav_menus( array( 'fields' => 'id=>name' ) );

// Controls
$controls = $wp_customize->get_controls_data_by_section();

// Breadcrumbs for middle sections
$breadcrumb_parents = isset( $wp_customize->cp_breadcrumb_parents ) ? $wp_customize->cp_breadcrumb_parents : array();

// Build top-level items: panels + sections without panel.
$top_items = array();

// Build mid-level sections
$middle_sections = array();

// Panels
$panels = $wp_customize->panels();
foreach ( $panels as $panel ) {
	$top_items[ $panel->id ] = array(
		'id'       => $panel->id,
		'title'    => $panel->title,
		'priority' => $panel->priority,
		'type'     => 'panel',
	);
}

// Sections
$sections          = $wp_customize->sections();
$sections_by_id    = array();
$sections_by_panel = array();
foreach ( $sections as $section ) {
	$sections_by_id[ $section->id ] = $section;
	$sections_by_panel[ $section->panel ? $section->panel : '_root' ][] = $section;

	if ( ! $section->panel ) {
		$top_items[ $section->id ] = array(
			'id'       => $section->id,
			'title'    => $section->title,
			'priority' => $section->priority,
			'type'     => 'section',
		);
	} else {
		if ( str_starts_with( $section->id, 'sidebar-widgets-' ) || str_starts_with( $section->id, 'nav_menu[' ) ) {
			continue;
		}
		if ( in_array( $section->id, array( 'installed_themes', 'wporg_themes', 'menu_locations', 'add_menu' ), true ) ) {
			continue;
		}
		$middle_sections[ $section->id ] = array(
			'id'       => $section->id,
			'title'    => $section->title,
			'priority' => $section->priority,
			'type'     => 'section',
		);
	}
}

// Sort by priority
uasort(
	$top_items,
	static function ( $a, $b ) {
		$ap = isset( $a['priority'] ) ? (int) $a['priority'] : 999;
		$bp = isset( $b['priority'] ) ? (int) $b['priority'] : 999;
		return $ap <=> $bp;
	}
);

/**
 * Collect widgets in a name-keyed array for sorting.
 */
$available_widgets = array();
$widgets_panel = isset( $panels['widgets'] ) ? $panels['widgets'] : null;
if ( $widgets_panel ) {
	require_once( ABSPATH . 'wp-admin/includes/widgets.php' );
	global $wp_registered_widgets, $wp_registered_widget_controls;

	foreach ( $wp_registered_widgets as $id => $widget ) {
		if ( empty( $widget['name'] ) ) {
			continue;
		}

		// Derive id_base
		$id_base = _get_widget_id_base( $id );
		$option_name = 'widget_' . preg_replace( '/__i__|%d/', '', $id_base );
		$all_widget_settings[ $id_base ] = get_option( $option_name, array() );
	}
	ksort( $available_widgets, SORT_NATURAL | SORT_FLAG_CASE );
}

/**
 * Fires when Customizer controls are initialized, before scripts are enqueued.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_init' );

/**
 * Enqueue styles and scripts.
 *
 * Also create global JS object to which to write changes.
 *
 * @since CP-2.8.0
 */
wp_enqueue_style( 'customize-controls' );
wp_enqueue_style( 'customize-preview' );
wp_enqueue_script( 'heartbeat' );
wp_enqueue_script( 'customize-controls' );
wp_add_inline_script(
	'customize-controls',
	'window.updatedControls = window.updatedControls || {};',
	'before'
);
wp_enqueue_script( 'customize-nav-menus' );
wp_enqueue_script( 'customize-widgets' );
wp_enqueue_script( 'theme' );

/**
 * Enqueue Customizer control scripts.
 *
 * @since 3.4.0
 */
do_action( 'customize_controls_enqueue_scripts' );

// Let's roll.
header( 'Content-Type: ' . get_option( 'html_type' ) . '; charset=' . get_option( 'blog_charset' ) );
send_nosniff_header();
nocache_headers();

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

$admin_title = sprintf( $wp_customize->get_document_title_template(), __( 'Loading…' ) );

?>
<title><?php echo esc_html( $admin_title ); ?></title>

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

<body class="<?php echo esc_attr( $body_class ); ?>">

<h1 class="screen-reader-text"><?php esc_html_e( 'Customizer' ); ?></h1>

<div class="wp-full-overlay preview-desktop expanded" aria-labelledby="customizer-title">
	<div id="customizer-sidebar-container">
		<h2 id="customizer-title" class="screen-reader-text">
			<?php printf( esc_html__( 'Customizing: %s' ), esc_html( get_bloginfo( 'name', 'display' ) ) ); ?>
		</h2>
		<form id="customize-controls" class="wrap wp-full-overlay-sidebar" style="position: static;z-index: 5;"
			action="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"
			method="post"
			accept-charset="<?php bloginfo( 'charset' ); ?>"
			inert <?php // prevent early interaction with form before page loaded ?>
		>
			<div id="customize-header-actions" class="wp-full-overlay-header" style="position: static;">

				<?php
				$compatible_wp  = is_wp_version_compatible( $wp_customize->theme()->get( 'RequiresWP' ) );
				$compatible_php = is_php_version_compatible( $wp_customize->theme()->get( 'RequiresPHP' ) );

				if ( $compatible_wp && $compatible_php ) {
					$save_text = $wp_customize->is_theme_active() ? __( 'Published' ) : __( 'Activate &amp; Publish' );
					?>
					<div id="customize-save-button-wrapper" class="customize-save-button-wrapper" disabled>
						<input type="submit" name="save" id="save" class="button button-primary save"
							value="<?php esc_attr_e( 'Published' ); ?>"
							disabled
						>
						<button id="publish-settings"
							class="publish-settings button-primary button dashicons dashicons-admin-generic"
							aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>"
							aria-expanded="false" style="display: none;"
							name="cp_publish_submit"
							value="1"
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
				<button type="button" class="customize-controls-preview-toggle">
					<span class="controls"><?php esc_html_e( 'Customize' ); ?></span>
					<span class="preview"><?php esc_html_e( 'Preview' ); ?></span>
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
						<!-- Outer panel and sections are not implemented, but it's here as a placeholder to avoid any side-effect in api.Section. -->
					</ul>
				</div>
			</div><!-- #customize-sidebar-outer-content -->

			<div id="widgets-right" class="wp-clearfix" style="overflow-y: scroll;max-height: calc(100vh - 90px);">
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
								esc_html_e( 'The Customizer allows you to preview changes to your site before publishing them. You can navigate to different pages on your site within the preview. Edit shortcuts are shown for some editable elements. The Customizer is intended for use with non-block themes.' );
								?>
							</p>
							<p>
								<a href="https://wordpress.org/documentation/article/customizer/">
									<?php esc_html_e( 'Documentation on Customizer' ); ?>
								</a>
							</p>
						</div>
					</div>
					<div id="customize-theme-controls">
						<ul id="customize-pane-parent" class="customize-pane-parent">
							<li id="accordion-section-themes" class="accordion-section control-panel-themes"
								aria-owns="sub-accordion-section-themes"
							>
								<h3 class="accordion-section-title" tabindex="0">
									<span class="customize-action">
										<?php
										if ( $wp_customize->get_stylesheet() === cp_get_true_active_stylesheet() ) {
											esc_html_e( 'Active theme' );
										} else {
											esc_html_e( 'Previewing theme' );
										}
										?>
									</span>
								
									<?php
									echo wp_get_theme()['Name'];

									if ( current_user_can( 'switch_themes' ) ) {
										?>

										<button type="button" class="button change-theme" aria-label="Change theme">
											<?php esc_html_e( 'Change' ); ?>
										</button>
										<?php

									}
									?>

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

								$top_object = $item['type'] === 'section'
									? $wp_customize->get_section( $item['id'] )
									: $wp_customize->get_panel( $item['id'] );

								if ( $top_object ) {
									$top_object->maybe_render();
								}
							}
							?>
						</ul>

						<ul id="sub-accordion-section-themes"
							class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-themes current-panel"
							data-parent-id="customize-pane-parent"
							style="display: none;"
						>
							<li class="panel-meta customize-info accordion-section">
								<button class="customize-panel-back" tabindex="0" type="button">
									<span class="screen-reader-text">
										<?php esc_html_e( 'Back' ); ?>
									</span>
								</button>
								<div class="accordion-section-title">
									<span class="preview-notice">
										<?php
										printf(
											/* translators: %s: Themes panel title in the Customizer. */
											__( 'You are browsing %s' ),
											'<strong class="panel-title">' . __( 'Themes' ) . '</strong>'
										); // Separate strings for consistency with other panels.
										?>
									</span>
									
									<?php
									if ( current_user_can( 'install_themes' ) && ! is_multisite() ) {
										?>
										<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false">
											<span class="screen-reader-text">
												<?php
												/* translators: Hidden accessibility text. */
												esc_html_e( 'Help' );
												?>
											</span>
										</button>
										<?php
									}
									?>

								</div>
									
								<?php
								if ( current_user_can( 'install_themes' ) && ! is_multisite() ) {
									?>

									<div class="description customize-panel-description">
										<p>
											<?php esc_html_e( 'Looking for a theme? You can search or browse the WordPress.org theme directory, install and preview themes, then activate them right here.' ); ?>
										</p>
										<p>
											<?php esc_html_e( 'While previewing a new theme, you can continue to tailor things like widgets and menus, and explore theme-specific options.' ); ?>
										</p>
									</div>

									<?php
								}
								?>

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
													<?php echo esc_html( $feature_group ); ?>
												</legend>
												<div class="filter-group-feature">
													<?php
													foreach ( $features as $feature => $feature_name ) {
														?>
														<input id="filter-id-<?php echo esc_attr( $feature ); ?>"
															type="checkbox"
															value="<?php echo esc_attr( $feature ); ?>"
														> 
														<label for="filter-id-<?php echo esc_attr( $feature ); ?>">
															<?php echo esc_html( $feature_name ); ?>
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
														<?php esc_html_e( 'Search themes&hellip;' ); ?>
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
															<?php echo number_format_i18n( $count_themes ); ?>
														</span>
														<?php esc_html_e( 'themes' ); ?>
													</span>
													<button type="button" class="button feature-filter-toggle" aria-expanded="false">
														<span class="filter-count-0">
															<?php esc_html_e( 'Filter themes' ); ?>
														</span>
														<span class="filter-count-filters">
															<?php printf( __( 'Filter themes (%s)' ), '<span class="theme-filter-count">0</span>' ); ?>
														</span>
													</button>												
												</div>
											</div>
											<div class="error unexpected-error" style="display: none; ">
												<p>

												<?php
												printf(
													/* translators: %s is an HTML link to the support forums. */
													__( 'An unexpected error occurred. Something may be wrong with WordPress.org, ClassicPress.net or this server’s configuration. If you continue to have problems, please try the %s.' ),
													sprintf(
														'<a href="%s">%s</a>',
														esc_url( 'https://forums.classicpress.net/c/support/' ),
														esc_html__( 'support forums' )
													)
												);
												?>

												</p>
											</div>
											<ul class="themes" style="overflow-y: scroll;max-height: 100vh;">

												<?php
												$themes_control = new WP_Customize_Theme_Control( $wp_customize, 'themes', array() );
												$themes_control->render_content();
												?>

											</ul>
										</div>											
									</div>
								</div>
							</li>
						</ul>

						<?php
						// The Menus panel.
						$menus_panel = isset( $panels['nav_menus'] ) ? $panels['nav_menus'] : null;
						if ( $menus_panel ) :
							?>

							<ul id="sub-accordion-panel-nav_menus"
								class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-nav_menus"
								data-parent-id="customize-pane-parent"
								style="display: none;"
							>

								<?php
								$nav_menus_panel = $wp_customize->get_panel( 'nav_menus' );
								error_log(print_r($nav_menus_panel, true));
								?>

								<li class="panel-meta customize-info accordion-section">
									<button class="customize-panel-back" tabindex="0" type="button">
										<span class="screen-reader-text">
											<?php esc_html_e( 'Back' ); ?>
										</span>
									</button>
									<div class="accordion-section-title">
										<span class="preview-notice">
											<?php esc_html_e( 'You are browsing' ); ?>
										</span>
										<strong class="panel-title">
											<?php echo esc_html( $nav_menus_panel->title ); ?>
										</strong>
									</div>

									<?php
									if ( ! empty( $nav_menus_panel->description ) ) {
										?>

										<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
											<span class="screen-reader-text">
												<?php
												/* translators: Hidden accessibility text. */
												esc_html_e( 'Help' );
												?>
											</span>
										</button>

										<div class="description customize-panel-description">
											<?php echo wp_kses_post( $nav_menus_panel->description ); ?>
										</div>

										<?php
									}
									?>

								</li>

								<?php
								// Each individual menu section (assigned-to-menu-location, etc.).
								foreach ( $sections_by_panel['nav_menus'] as $section ) {

									// Individual menus, e.g. nav_menu[2], nav_menu[primary], etc.
									if ( ! str_starts_with( $section->id, 'nav_menu[' ) ) {
										continue;
									}

									$current_location_label = '';
									$menu_key = substr( $section->id, strlen( 'nav_menu[' ), -1 ); // Strip the prefix and trailing bracket.

									// If it's numeric, treat as menu ID.
									if ( is_numeric( $menu_key ) ) {
										$menu_id = (int) $menu_key;

										// Find which locations point to this menu ID.
										$attached_locations = array();
										foreach ( $menu_locations as $loc_slug => $loc_menu_id ) {
											if ( (int) $loc_menu_id === $menu_id && isset( $locations[ $loc_slug ] ) ) {
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

									<li id="accordion-section-<?php echo esc_attr( $section->id ); ?>"
										class="accordion-section control-section control-section-nav_menu control-subsection menu assigned-to-menu-location"
										aria-owns="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>"
									>
										<h3 class="accordion-section-title" tabindex="0">
											<?php echo esc_html( $section->title ); ?>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Press return or enter to open this section' ); ?>
											</span>
											<?php
											if ( $current_location_label ) {
												?>
												<span class="menu-in-location">
													<?php echo esc_html( $current_location_label ); ?>
												</span>
												<?php
											}
											?>

										</h3>
									</li>

									<?php
								}
								if ( isset( $sections_by_id['add_menu'] ) ) { // The “Add a Menu” section.
									$add_menu_section = $sections_by_id['add_menu'];
									?>
									<li id="accordion-section-<?php echo esc_attr( $add_menu_section->id ); ?>"
										class="accordion-section control-section control-section-new_menu control-subsection"
										aria-owns="sub-accordion-section-<?php echo esc_attr( $add_menu_section->id ); ?>"
										data-setting-id=<?php echo esc_attr( $add_menu_section->id ); ?>"
									>
										<?php
										if ( empty( $menus ) ) {
											?>
											<p class="add-new-menu-notice">
												<?php esc_html_e( 'It does not look like your site has any menus yet. Want to build one? Click the button to start.' ); ?>
											</p>
											<p class="add-new-menu-notice">
												<?php _e( 'You&#8217;ll create a menu, assign it a location, and add menu items like links to pages and categories. If your theme has multiple menu areas, you might need to create more than one.' ); ?>
											</p>
											<?php
										}
										?>
										<h3>
											<button id="customize-add-menu-button" type="button" class="button customize-add-menu-button">
												<?php esc_html_e( 'Create New Menu' ); ?>
											</button>
										</h3>
									</li>
									<?php
								}
								if ( isset( $sections_by_id['menu_locations'] ) ) { // Menu Locations.
									$menu_locations_section = $sections_by_id['menu_locations'];
									?>
									<li id="accordion-section-<?php echo esc_attr( $menu_locations_section->id ); ?>"
										class="accordion-section control-section control-section-nav_menu_locations control-subsection"
										aria-owns="sub-accordion-section-<?php echo esc_attr( $menu_locations_section->id ); ?>"
									>
										<span class="customize-control-title customize-section-title-menu_locations-heading">
											<?php esc_html_e( 'Menu Locations' ); ?>
										</span>
										<div class="customize-control-description customize-section-title-menu_locations-description">
											<?php echo wp_kses_post( $menu_locations_section->description ); ?>
										</div>
										<h3 class="accordion-section-title" tabindex="0">
											<?php echo esc_html( $menu_locations_section->title ); ?>
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

						// The Widgets panel.
						$widgets_panel = isset( $panels['widgets'] ) ? $panels['widgets'] : null;
						if ( $widgets_panel ) :
							?>

							<ul id="sub-accordion-panel-widgets"
								class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-section control-panel control-panel-widgets"
								data-parent-id="customize-pane-parent"
								style="display: none;"
							>

								<?php
								$widget_panel = $wp_customize->get_panel( 'widgets' );
								$widget_panel->render_content();

								// Each widget area section under the Widgets panel.
								foreach ( $sections_by_panel['widgets'] as $section ) {
									?>
									<li id="accordion-section-<?php echo esc_attr( $section->id ); ?>"
										class="accordion-section control-section control-section-widgets control-subsection"
										aria-owns="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>"
									>
										<h3 class="accordion-section-title" tabindex="0">
											<?php echo esc_html( $section->title ); ?>
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

						// Remaining sub-accordions
						foreach ( $top_items as $item ) {
							if ( in_array( $item['id'], array( 'themes', 'nav_menus', 'widgets' ), true ) ) {
								continue;
							}
							if ( $item['type'] === 'panel' ) {
								?>
								<ul id="sub-accordion-panel-<?php echo esc_attr( $item['id'] ); ?>"
									class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-<?php echo esc_attr( $item['id'] ); ?>"
									data-parent-id="customize-pane-parent"
									style="display: none;"
								>
									<li class="panel-meta customize-info accordion-section cannot-expand">
										<button class="customize-panel-back" tabindex="0" type="button">
											<span class="screen-reader-text">
												<?php esc_html_e( 'Back' ); ?>
											</span>
										</button>
										<div class="accordion-section-title">
											<span class="preview-notice">

												<?php
												/* translators: %s: Panel title. */
												printf( __( 'You are customizing %s' ), '<strong class="panel-title">' . esc_html( $item['title'] ) . '</strong>' );
												?>

											</span>
											<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
												<span class="screen-reader-text">
													<?php esc_html_e( 'Help' ); ?>
												</span>
											</button>
										</div>

										<?php
										if ( ! empty( $item['description'] ) ) {
											?>

											<div class="description customize-panel-description">
												<?php echo wp_kses_post( $item['description'] ); ?>
											</div>

											<?php
										}
										?>

										<div class="customize-control-notifications-container" style="display: none;">
											<ul></ul>
										</div>
									</li>

									<?php
									// Render child sections
									foreach ( $sections_by_panel[ $item['id'] ] as $section ) {
										$section->maybe_render();
									}
									?>

								</ul>

								<?php
								foreach ( $middle_sections as $middle_section ) {
									$parent_title = '';
									if ( isset( $breadcrumb_parents[ $middle_section['id'] ] ) ) {
										$parent_title = $breadcrumb_parents[ $middle_section['id'] ];
									}
									?>

									<ul id="sub-accordion-section-<?php echo esc_attr( $middle_section['id'] ); ?>"
										class="customize-pane-child accordion-section-content accordion-section control-section control-section-default"
										data-parent-id="customize-pane-parent"
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
															esc_html( $parent_title )
														);
														?>
													</span>
													<?php echo esc_html( $middle_section['title'] ); ?>
												</h3>
												<div class="customize-control-notifications-container" style="display: none;">
													<ul></ul>
												</div>
											</div>
											
											<?php
											if ( ! empty( $middle_section['description'] ) ) {
												?>

												<div class="description customize-section-description">
													<?php echo wp_kses_post( $middle_section['description'] ); ?>
												</div>

												<?php
											}
											?>

										</li>

										<?php
										// Render controls that belong to this section-panel hybrid container.
										if ( isset( $controls[ $middle_section['id'] ] ) ) {
											foreach ( $controls[ $middle_section['id'] ] as $control_data ) {
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
							} elseif ( $item['type'] === 'section' ) {
								?>

								<ul id="sub-accordion-section-<?php echo esc_attr( $item['id'] ); ?>"
									class="customize-pane-child accordion-section-content accordion-section control-section control-section-default"
									data-id="<?php echo esc_attr( $item['id'] ); ?>"
									data-parent-id="customize-pane-parent"
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
													<?php esc_html_e( 'Customizing' ); ?>
												</span>
												<?php echo esc_html( $item['title'] ); ?>
											</h3>
											<div class="customize-control-notifications-container" style="display: none;">
												<ul></ul>
											</div>
										</div>

										<?php
										if ( $item['id'] === 'header_image' ) { // description of header_image is hard-coded in core
											?>

											<div class="description customize-section-description">

												<?php
												global $cp_header_image_section_description;
												echo wp_kses_post( $cp_header_image_section_description );
												?>

											</div>

											<?php
										} elseif ( ! empty( $item['description'] ) ) {
											?>

											<div class="description customize-section-description">
												<?php echo wp_kses_post( $item['description'] ); ?>
											</div>

											<?php
										}
										?>

									</li>

									<?php
									if ( isset( $controls[ $item['id'] ] ) ) {
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
						}
						?>

						<ul id="sub-accordion-section-add_menu"
							class="customize-pane-child accordion-section-content accordion-section control-section control-section-new_menu menu open"
							data-parent-id="sub-accordion-panel-nav_menus"
							style="display: none;"
						>
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
								<label for="menu-title">
									<span class="customize-control-title">
										<?php esc_html_e( 'Menu Name' ); ?>
									</span>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
								</label>
								<input id="menu-title" type="text" class="menu-name-field live-update-section-title" aria-describedby="add_menu-description">
								<p id="add_menu-description">
									<?php esc_html_e( 'If your theme has multiple menus, giving them clear names will help you manage them.' ); ?>
								</p>
							</li>
							<li id="customize-control-add_menu-locations" class="customize-control customize-control-nav_menu_locations">
								<ul class="menu-location-settings">
									<li class="customize-control assigned-menu-locations-title">
										<span class="customize-control-title">
											<?php esc_html_e( 'Menu Locations' ); ?>
										</span>
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
												<input id="customize-nav-menu-control-location-<?php echo esc_attr( $menu_locations[ $key ] ); ?>"
													type="checkbox"
													data-menu-id="<?php echo esc_attr( $menu_locations[ $key ] ); ?>"
													data-location-id="<?php echo esc_attr( $location ); ?>"
													class="menu-location"
												>
												<label for="customize-nav-menu-control-location-<?php echo esc_attr( $menu_locations[ $key ] ); ?>">
													<?php echo esc_html( $location ); ?>
													<span class="theme-location-set">

														<?php
														if ( isset( $menus[ $menu_locations[ $key ] ] ) ) {
															printf(
																wp_kses(
																	/* translators: %s is the current menu name wrapped in a span. */
																	__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)' ),
																	array( 'span' => array( 'class' => true ) )
																),
																esc_html( $menus[ $menu_locations[ $key ] ] )
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
									<?php esc_html_e( 'Click &#8220;Next&#8221; to start adding links to your new menu.' ); ?>
								</p>
								<button id="customize-new-menu-submit" type="button" class="button" aria-describedby="customize-new-menu-submit-description">
									<?php esc_html_e( 'Next' ); ?>
								</button>
							</li>
						</ul>

						<?php
						// Render controls for each nav_menus section (locations + individual menus).
						foreach ( $sections_by_panel['nav_menus'] as $section ) {

							// Extract menu term ID from section ID: nav_menu[123] => 123
							$menu_id = 0;
							if ( str_starts_with( $section->id, 'nav_menu[' ) ) {
								$menu_id = absint( substr( $section->id, strlen( 'nav_menu[' ), -1 ) );
							}

							$section_class = 'control-section-nav_menu';
							if ( 'nav_menu_locations' === $section->id || 'nav_menus[locations]' === $section->id ) {
								$section_class = 'control-section-nav_menu_locations';
							}
							?>

							<ul id="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>"
								class="customize-pane-child accordion-section-content accordion-section control-section <?php echo esc_attr( $section_class ); ?> menu assigned-to-menu-location"
								data-id="<?php echo esc_attr( $section->id ); ?>"
								data-parent-id="sub-accordion-panel-nav_menus"
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
											<?php echo esc_html( $section->title ); ?>
										</h3>
										<div class="customize-control-notifications-container" style="display:none;">
											<ul></ul>
										</div>
									</div>

									<?php
									if ( ! empty( $section->description ) ) {
										?>

										<div class="description customize-section-description">
											<?php echo wp_kses_post( $section->description ); ?>
										</div>

										<?php
									}
									?>

								</li>

								<?php
								// Menu items - only for nav_menu[ID] sections
								if ( $menu_id && str_starts_with( $section->id, 'nav_menu[' ) ) {
									?>
									
									<li id="customize-control-nav_menu-<?php echo $menu_id; ?>-name"
										class="customize-control customize-control-nav_menu_name no-drag"
										data-setting-id="nav_menu[<?php echo $menu_id; ?>]"
									>
										<label for="menu-name-title-<?php echo $menu_id; ?>">
											<span class="customize-control-title">
												<?php esc_html_e( 'Menu Name' ); ?>
											</span>
											<div class="customize-control-notifications-container" style="display: none;">
												<ul></ul>
											</div>
										</label>
										<input id="menu-name-title-<?php echo $menu_id; ?>"
											type="text"
											class="menu-name-field live-update-section-title"
											value="<?php echo esc_attr( $menus[ $menu_id ] ); ?>"
										>
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
										$depths = array();
										foreach ( $menu_items as $key => $menu_item ) {
											$move_class = '';
											if ( $key === 0 ) {
												$move_class .= 'move-up-disabled move-left-disabled move-right-disabled';
											}
											if ( $key === count( $menu_items ) - 1 ) {
												$move_class .= ' move-down-disabled';
											}

											$parent_id = (int) $menu_item->menu_item_parent ?? 0;
											if ( $parent_id === 0 && $key !== 0 ) {
												$move_class .= ' move-left-disabled';
											}

											// $depth = 0 for top-level; otherwise parent depth + 1
											// 11 is highest depth
											$depth = ( $parent_id === 0 ) ? 0 : ( ( $depths[ $parent_id ] ?? 0 ) + 1 );
											if ( ( $depth === 11 && $key !== 0 ) ) {
												$move_class .= ' move-right-disabled';
											}

											$depths[ $menu_item->ID ] = $depth;
											?>

											<li id="customize-control-nav_menu_item-<?php echo esc_attr( $menu_item->ID ); ?>"
												class="customize-control customize-control-nav_menu_item menu-item menu-item-depth-<?php echo absint( $depth ); ?> menu-item-custom menu-item-edit-inactive <?php echo esc_attr( $move_class ); ?>"
												data-setting-id="nav_menu_item[<?php echo esc_attr( $menu_item->ID ); ?>]"
											>

												<?php
												// Individual menu items are not pre-registered and so need dynamic instantiation
												$nav_menu_item_control = new WP_Customize_Nav_Menu_Item_Control(
													$wp_customize,
													'nav_menu_item[' . $menu_item->ID . ']',
													array(
														'label'    => $menu_item->post_title ? $menu_item->post_title : $menu_item->title,
														'section'  => 'nav_menu_items[' . $menu_id . ']',  // Menu section
													)
												);
												$nav_menu_item_control->render_content();
												?>

											</li>

											<?php
										}
									}
									?>
										
									<li id="customize-control-nav_menu-<?php echo $menu_id; ?>"
										class="customize-control customize-control-nav_menu no-drag"
										data-menu-id="<?php echo $menu_id; ?>"
									>
										<div class="customize-control-notifications-container" style="display: none;">
											<ul></ul>
										</div>
										<p class="new-menu-item-invitation" style="display: none;">
											<?php esc_html_e( 'Time to add some links! Click “Add Items” to start putting pages, categories, and custom links in your menu. Add as many things as you would like.' ); ?>
										</p>
										<div class="customize-control-nav_menu-buttons">
											<button type="button"
												class="button add-new-menu-item"
												aria-label="<?php esc_attr_e( 'Add or remove menu items' ); ?>"
												aria-expanded="false"
												aria-controls="available-menu-items"
											>
												<?php esc_html_e( 'Add Items' ); ?>
											</button>
											<button type="button"
												class="button-link reorder-toggle"
												aria-label="<?php esc_attr_e( 'Reorder menu items' ); ?>"
												aria-describedby="reorder-items-desc-<?php echo $menu_id; ?>"
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
										<p class="screen-reader-text" id="reorder-items-desc-<?php echo $menu_id; ?>">
											<?php esc_html_e( 'When in reorder mode, additional controls to reorder menu items will be available in the items list above.' ); ?>
										</p>
									</li>
									<li id="customize-control-nav_menu-<?php echo $menu_id; ?>-locations" class="customize-control customize-control-nav_menu_locations no-drag">
										<ul class="menu-location-settings" data-menu-id="<?php echo $menu_id; ?>">
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
												if ( isset( $menu_locations[ $key ] ) ) {
													?>
											
													<li class="customize-control customize-control-checkbox assigned-menu-location no-drag"
														data-setting-id="<?php echo esc_attr( 'nav_menu_locations[' . $key . ']' ); ?>"
													>
														<span class="customize-inside-control-row">
															<input id="customize-nav-menu-control-location-<?php echo esc_attr( $key ); ?>"
																type="checkbox"
																data-location-id="<?php echo esc_attr( $key ); ?>"
																class="menu-location"
																value="<?php echo checked( $menu_locations[ $key ], $menu_id, false ) ? $menu_id : ''; ?>"
																<?php checked( $menu_locations[ $key ], $menu_id ); ?>
															>
															<label for="customize-nav-menu-control-location-<?php echo esc_attr( $menu_locations[ $key ] ); ?>">
																<?php echo esc_html( $location ); ?>
																<span class="theme-location-set">

																	<?php
																	if ( isset( $menus[ $menu_locations[ $key ] ] ) ) {
																		printf(
																			wp_kses(
																				/* translators: %s is the current menu name wrapped in a span. */
																				__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)' ),
																				array( 'span' => array( 'class' => true ) )
																			),
																			esc_html( $menus[ $menu_locations[ $key ] ] )
																		);
																	}
																	?>

																</span>
															</label>
														</span>
													</li>

													<?php
												}
											}
											?>

										</ul>
									</li>
									<li id="customize-control-nav_menu-<?php echo $menu_id; ?>-auto_add"
										class="customize-control customize-control-nav_menu_auto_add no-drag"
										data-setting-id="nav_menu[<?php echo $menu_id; ?>]"
									>
										<span class="customize-control-title">
											<?php esc_html_e( 'Menu Options' ); ?>
										</span>
										<div class="customize-control-notifications-container" style="display: none;">
											<ul></ul>
										</div>
										<span class="customize-inside-control-row">
											<input id="customize-nav-menu-auto-add-control-<?php echo $menu_id; ?>" type="checkbox" class="auto_add">
											<label for="customize-nav-menu-auto-add-control-<?php echo $menu_id; ?>">
												<?php esc_html_e( 'Automatically add new top-level pages to this menu' ); ?>
											</label>
										</span>
									</li>
									<li id="customize-control-nav_menu-<?php echo $menu_id; ?>-delete"
										class="customize-control customize-control-undefined no-drag"
										data-setting-id="nav_menu[<?php echo $menu_id; ?>]"
									>
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
									// Menu locations
								} elseif ( $section->id === 'menu_locations' ) {
									foreach ( $controls[ $section->id ] as $control_data ) {
										$field_id    = $control_data['id'];
										$field_type  = $control_data['type'];
										$setting_id  = $control_data['setting_id'];
										?>

										<li id="customize-control-<?php echo esc_attr( $field_id ); ?>"
											class="customize-control customize-control-<?php echo esc_attr( $field_type ); ?>"
											data-setting-id="<?php echo esc_attr( $setting_id ); ?>"
										>
											<div class="customize-control-inner">

												<?php
												$choices = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );
												$choices = array( 0 => '— Select —' ) + $choices;  // Format for select

												$nav_menu_location_control = $wp_customize->get_control( $field_id );
												$nav_menu_location_control->render_content();
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
						foreach ( $sections_by_panel['widgets'] as $section ) {

							// No controls collected for this section?
							if ( empty( $controls[ $section->id ] ) ) {
								continue;
							}
							?>

							<ul id="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>"
								class="customize-pane-child accordion-section-content accordion-section control-section control-section-sidebar"
								data-id="<?php echo esc_attr( $section->sidebar_id ); ?>"
								data-parent-id="sub-accordion-panel-widgets"
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
											<?php echo esc_html( $section->title ); ?>
										</h3>
										<div class="customize-control-notifications-container" style="display:none;">
											<ul></ul>
										</div>
									</div>

									<?php
									if ( ! empty( $section->description ) ) {
										?>

										<div class="description customize-section-description">
											<?php echo wp_kses_post( $section->description ); ?>
										</div>

										<?php
									}
									?>
								</li>

								<?php
								$index = 0;
								foreach ( $controls[ $section->id ] as $control_data ) {
									$field_id    = $control_data['id'];
									$field_type  = $control_data['type'];
									$field_label = $control_data['label'];
									$setting_id  = $control_data['setting_id'];
									$widget_id   = isset( $section->controls[ $index ]->widget_id ) ? $section->controls[ $index ]->widget_id : '';

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

									<li id="customize-control-widget-<?php echo esc_attr( $widget_id ); ?>"
										class="customize-control customize-control-widget_form<?php echo esc_attr( $first_last_widget ); ?> widget-rendered"
										data-setting-id="<?php echo esc_attr( $setting_id ? $setting_id : $field_id ); ?>"
									>
										<div id="widget-<?php echo esc_attr( $index . '_' . $widget_id ); ?>" class="widget">
											<details class="widget-top" name="<?php echo esc_attr( $section->sidebar_id ); ?>">
												<summary class="widget-title">
													<h3>
														<?php echo esc_html( $field_label ); ?>
														<span class="in-widget-title"></span>
													</h3>
													<div class="widget-reorder-nav">
														<span class="move-widget" tabindex="0" style="">
															<?php echo esc_html( $field_label ); ?>:
															<?php esc_html_e( 'Move to another area&hellip;' ); ?>
														</span>
														<span class="move-widget-down" tabindex="0">
															<?php echo esc_html( $field_label ); ?>:
															<?php esc_html_e( 'Move down' ); ?>
														</span>
														<span class="move-widget-up" tabindex="-1">
															<?php echo esc_html( $field_label ); ?>:
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
															<?php echo esc_html( $field_label ); ?>
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
													continue;
												}

												$widget_settings_base = isset( $all_widget_settings[ $widget_id_base ] )
													? $all_widget_settings[ $widget_id_base ]
													: array();

												$field_value = isset( $widget_settings_base[ $widget_number ] )
													? $widget_settings_base[ $widget_number ]
													: array();

												$widget_obj->id = $widget_id;
												$widget_obj->number = $widget_number;
												$widget_settings = is_array( $field_value ) ? $field_value : array();
												?>

												<div class="widget-inside">
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<div class="form">
														<div class="widget-content">
															<?php $widget_obj->form( $field_value ); ?>
														</div>
														<input type="hidden" name="widget-id" class="widget-id" value="<?php echo esc_attr( $widget_id ); ?>">
														<input type="hidden" name="id_base" class="id_base" value="<?php echo esc_attr( $widget_id_base ); ?>">
														<input type="hidden" name="widget-width" class="widget-width" value="auto">
														<input type="hidden" name="widget-height" class="widget-height" value="auto">
														<input type="hidden" name="widget_number" class="widget_number" value="<?php echo esc_attr( $widget_number ); ?>">
														<input type="hidden" name="multi_number" class="multi_number" value="">
														<input type="hidden" name="sidebar_id" class="sidebar_id" value="<?php echo esc_attr( $section->id ); ?>">
														<input type="hidden" name="add_new" class="add_new" value="">

														<?php
														/**
														 * Fires near the end of the widget control form.
														 *
														 * @param WP_Widget $widget_obj      Widget instance.
														 * @param null      $return          Return null if new fields are added.
														 * @param array     $widget_settings An array of the widget’s settings.
														 */
														do_action( 'in_widget_form', $widget_obj, $return = null, $widget_settings );
														?>

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
																	id="widget-<?php echo esc_attr( $widget_id ); ?>-savewidget"
																	class="button button-primary widget-control-save right"
																	value="Save"
																>
																<span class="spinner"></span>
															</div>
															<br class="clear">
														</div>
													</div><!-- .form -->
												</div><!-- .widget-inside -->
											</details>
										</div>
									</li>
									<?php
								}
								?>
								<li id="customize-control-sidebars_widgets-<?php echo esc_attr( $section->id ); ?>"
									class="customize-control customize-control-sidebar_widgets no-drag"
								>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
									<button type="button" class="button add-new-widget" aria-expanded="false" aria-controls="widgets-left">
										<?php esc_html_e( 'Add a Widget' ); ?>
									</button>
									<button type="button"
										class="button-link reorder-toggle"
										aria-label="<?php esc_attr_e( 'Reorder widgets' ); ?>"
										aria-describedby="reorder-widgets-desc-sidebars_widgets-<?php echo esc_attr( $section->id ); ?>"
									>
										<span class="reorder"><?php esc_html_e( 'Reorder' ); ?></span>
										<span class="reorder-done"><?php esc_html_e( 'Done' ); ?></span>
									</button>
									<p class="screen-reader-text" id="reorder-widgets-desc-sidebars_widgets-<?php echo esc_attr( $section->id ); ?>">
										<?php esc_html_e( 'When in reorder mode, additional controls to reorder widgets will be available in the widgets list above.' ); ?>
									</p>
								</li>
							</ul>

							<?php
						}
						?>

						<ul id="menu-to-edit"
							class="customize-pane-child accordion-section-content accordion-section control-section control-section-nav_menu menu open"
							data-instruction="<?php esc_html_e( 'Press return or enter to open this section' ); ?>"
							data-parent-id="sub-accordion-panel-nav_menus"
							style="display: none;"
						>
							<!-- li elements are added via template id="tmpl-new-nav-menu" below -->
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

			<input type="hidden"
				id="customize_changeset_uuid"
				name="customize_changeset_uuid"
				value="<?php echo esc_attr( $wp_customize->changeset_uuid() ); ?>"
			>
			<input type="hidden"
				id="theme_stylesheet"
				name="theme_stylesheet"
				value="<?php echo esc_attr( wp_get_theme()->get_stylesheet() ); ?>"
			>
			<input type="hidden"
				id="customize_form_stage"
				name="customize_form_stage"
				value="php-first-paint"
			>

			<?php
			wp_nonce_field( 'save-customize_' . $wp_customize->get_stylesheet(), 'customizer_nonce', false );
			wp_nonce_field( 'save-sidebar-widgets', '_wpnonce_widgets', false );
			wp_nonce_field( 'update-widget', 'nonce', false );
			?>
		</form><!-- #customize-controls -->

		<?php
		// Display available widgets.
		$widgets = $wp_customize->widgets;
		$widgets->output_widget_control_templates();

		// Display nav menus.
		$nav_menus = $wp_customize->nav_menus;
		$nav_menus->available_items_template();
		?>

	</div>

	<div id="customize-preview" class="wp-full-overlay-main iframe-ready">
		<div id="customize-preview-loading" aria-live="polite">
			<span class="spinner is-active"></span>
			<span class="customize-preview-loading-text">
				<?php esc_html_e( 'Setting up your live preview. This might take a while.' ); ?>
			</span>
		</div>
		<iframe title="<?php esc_attr_e( 'Site Preview' ); ?>"
			name="customize-preview-0"
			onmousewheel=""
			src="<?php echo esc_url( $preview_url ); ?>"
			style="position: relative;z-index: 1;"
			sandbox="allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-popups allow-popups-to-escape-sandbox allow-presentation allow-same-origin allow-scripts"
		></iframe>
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
					<button id="menu-item-add" type="button" role="tab" class="media-menu-item active" aria-selected="true">
						<?php esc_html_e( 'Add media' ); ?>
					</button>
					<button id="menu-item-gallery" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden>
						<?php esc_html_e( 'Create gallery' ); ?>
						</button>
					<button id="menu-item-playlist" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden>
						<?php esc_html_e( 'Create audio playlist' ); ?>
					</button>
					<button id="menu-item-video-playlist" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden>
						<?php esc_html_e( 'Create video playlist' ); ?>
					</button>
					<button id="menu-item-featured-image" type="button" role="tab" class="media-menu-item" aria-selected="false" hidden>
						<?php esc_html_e( 'Featured image' ); ?>
					</button>
					<div role="presentation" class="separator"></div>
					<button id="menu-item-embed" type="button" role="tab" class="media-menu-item" aria-selected="false" aria-controls="insert-from-url-panel" hidden>
						<?php esc_html_e( 'Insert from URL' ); ?>
					</button>
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

<!-- Template for creation of new nav menu items -->
<template id="tmpl-new-menu-item">
	<li class="customize-control customize-control-nav_menu_item menu-item menu-item-depth-0 menu-item-custom menu-item-edit-inactive move-left-disabled move-down-disabled" data-setting-id="">
		<div class="menu-item-bar">
			<details class="menu-item-handle" name="new-menu-item">
				<summary>
					<span class="item-title" aria-hidden="true">
						<span class="menu-item-title"></span>
					</span>
					<div class="menu-item-reorder-nav">

						<?php
						printf(
							'<button type="button" class="menus-move-up">%1$s</button><button type="button" class="menus-move-down">%2$s</button><button type="button" class="menus-move-left">%3$s</button><button type="button" class="menus-move-right">%4$s</button>',
							__( 'Move up' ),
							__( 'Move down' ),
							__( 'Move one level up' ),
							__( 'Move one level down' )
						);
						?>

					</div>
					<span class="item-type" aria-hidden="true"></span>
					<span class="item-controls">
						<button type="button" class="button-link item-delete submitdelete deletion">
							<span class="screen-reader-text">
								<?php esc_html_e( 'Remove Menu Item:' ); ?>
							</span>
						</button>
					</span>
				</summary>

				<div id="menu-item-settings-" class="menu-item-settings">
					<p class="field-url description description-thin" hidden>
						<label for="edit-menu-item-url--">
							<?php esc_html_e( 'URL' ); ?>
						</label>
						<input id="edit-menu-item-url--" class="widefat code edit-menu-item-url" type="text" name="menu-item-url">
					</p>
					<p class="description description-thin">
						<label for="edit-menu-item-title--">
							<?php esc_html_e( 'Navigation Label' ); ?>
						</label>
						<input type="text" id="edit-menu-item-title--" placeholder="" class="widefat edit-menu-item-title" name="menu-item-title">
					</p>
					<p class="field-link-target description description-thin">
						<label for="edit-menu-item-target--">
							<?php esc_html_e( 'Open link in a new tab' ); ?>
						</label>
						<input id="edit-menu-item-target--" type="checkbox" class="edit-menu-item-target" value="_blank" name="menu-item-target">
					</p>
					<p class="field-title-attribute field-attr-title description description-thin">
						<label for="edit-menu-item-attr-title--">
							<?php esc_html_e( 'Title Attribute' ); ?>
						</label>
						<input id="edit-menu-item-attr-title--" type="text" class="widefat edit-menu-item-attr-title" name="menu-item-attr-title">
					</p>
					<p class="field-css-classes description description-thin">
						<label for="edit-menu-item-classes--">
							<?php esc_html_e( 'CSS Classes' ); ?>
						</label>
						<input id="edit-menu-item-classes--" type="text" class="widefat code edit-menu-item-classes" name="menu-item-classes">
					</p>
					<p class="field-xfn description description-thin">
						<label for="edit-menu-item-xfn--">
							<?php esc_html_e( 'Link Relationship (XFN)' ); ?>
						</label>
						<input id="edit-menu-item-xfn--" type="text" class="widefat code edit-menu-item-xfn" name="menu-item-xfn">
					</p>
					<p class="field-description description description-thin">
						<label for="edit-menu-item-description--">
							<?php esc_html_e( 'Description' ); ?>
						</label>
						<textarea id="edit-menu-item-description--"
							class="widefat edit-menu-item-description"
							rows="3"
							cols="20"
							name="menu-item-description"
							aria-describedby="edit-menu-item-description"
						>
						</textarea>
						<span id="edit-menu-item-description" class="description">
							<?php esc_html_e( 'The description will be displayed in the menu if the active theme supports it.' ); ?>
						</span>
					</p>

					<?php
					/**
					 * Creates an output buffer to convert mustache-style attributes added to nav menu
					 * items by plugins into appropriate HTML.
					 *
					 * @since CP-2.8.0
					 */
					ob_start();

					/**
					 * Fires at the end of the form field template for nav menu items in the customizer.
					 *
					 * Additional fields can be rendered here.
					 *
					 * @since 5.4.0
					 */
					do_action( 'wp_nav_menu_item_custom_fields_customize_template' );
					$plugin_template = ob_get_clean();

					if ( $plugin_template ) { // Replace mustache-style placeholders with actual values.
						$plugin_template = str_replace( '{{ data.menu_item_id }}', 'brand-new', $plugin_template );
						echo $plugin_template;
					}
					?>

					<div class="menu-item-actions description-thin submitbox">
						<p class="link-to-original">
							<?php esc_html_e( 'Original:' ); ?>
							<a class="original-link" href=""></a>
						</p>
						<button type="button" class="button-link button-link-delete item-delete submitdelete deletion">
							<?php esc_html_e( 'Remove' ); ?>
						</button>
						<span class="spinner"></span>
					</div>
					<input type="hidden" name="menu-item-db-id[brand-new]" class="menu-item-data-db-id" value="0">
					<input type="hidden" name="menu-item-object-id[brand-new]" class="menu-item-data-object-id" value="0">
					<input type="hidden" name="menu-item-object[brand-new]" class="menu-item-data-object" value="">
					<input type="hidden" name="menu-item-parent-id[brand-new]" class="menu-item-data-parent-id" value="0">
					<input type="hidden" name="menu-item-position[brand-new]" class="menu-item-data-position" value="">
					<input type="hidden" name="menu-item-type[brand-new]" class="menu-item-data-type" value="">
					<input type="hidden" name="menu-item-menu-id[brand-new]" class="menu-item-data-menu-id" value="0">

				</div><!-- .menu-item-settings-->
				<ul class="menu-item-transport"></ul>
			</details>
		</div>
	</li>
</template>

<!-- Template for creation of new nav menus -->
<template id="tmpl-new-nav-menu">
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
				<span class="new-menu-title">
					<?php esc_html_e( 'New Menu' ); ?>
				</span>
			</h3>
			<div class="customize-control-notifications-container" style="display: none;">
				<ul></ul>
			</div>
		</div>
	</li>
	<li id="customize-control-nav_menu-brand-new-name"
		class="customize-control customize-control-nav_menu_name no-drag"
		data-setting-id="nav_menu[brand-new]"
	>
		<label for="menu-name-title-brand-new">
			<span class="customize-control-title">
				<?php esc_html_e( 'Menu Name' ); ?>
			</span>
			<div class="customize-control-notifications-container" style="display: none;">
				<ul></ul>
			</div>
		</label>
		<input id="menu-name-title-brand-new"
			type="text"
			class="menu-name-field live-update-section-title"
			value=""
		>
	</li>
	<!-- Add nav menu items -->
	<li id="customize-control-nav_menu-brand-new"
		class="customize-control customize-control-nav_menu no-drag"
		data-menu-id="brand-new"
	><?php // Look at nav-menu-control.php ?>
		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>
		<p class="new-menu-item-invitation" style="display: none;">
			<?php esc_html_e( 'Time to add some links! Click “Add Items” to start putting pages, categories, and custom links in your menu. Add as many things as you would like.' ); ?>
		</p>
		<div class="customize-control-nav_menu-buttons">
			<button type="button"
				class="button add-new-menu-item"
				aria-label="<?php esc_attr_e( 'Add or remove menu items' ); ?>"
				aria-expanded="false"
				aria-controls="available-menu-items"
			>
				<?php esc_html_e( 'Add Items' ); ?>
			</button>
			<button type="button"
				class="button-link reorder-toggle"
				aria-label="<?php esc_attr_e( 'Reorder menu items' ); ?>"
				aria-describedby="reorder-items-desc-brand-new"
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
		<p class="screen-reader-text" id="reorder-items-desc-brand-new">
			<?php esc_html_e( 'When in reorder mode, additional controls to reorder menu items will be available in the items list above.' ); ?>
		</p>
	</li>
	<li id="customize-control-nav_menu-brand-new-locations" class="customize-control customize-control-nav_menu_locations no-drag">
		<ul class="menu-location-settings" data-menu-id="brand-new">
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
			foreach ( $locations as $slug => $location ) {
				?>

				<li class="customize-control customize-control-checkbox assigned-menu-location no-drag"
					data-setting-id="<?php echo esc_attr( 'nav_menu_locations[' . $slug . ']' ); ?>"
				>
					<span class="customize-inside-control-row">
						<input id="customize-nav-menu-control-location-<?php echo esc_attr( $menu_locations[ $slug ] ); ?>"
							type="checkbox"
							data-location-id="<?php echo esc_attr( $slug ); ?>"
							class="menu-location"
							value=""
						>
						<label for="customize-nav-menu-control-location-<?php echo esc_attr( $menu_locations[ $slug ] ); ?>">
							<?php echo esc_html( $location ); ?>
							<span class="theme-location-set">

								<?php
								if ( isset( $menus[ $menu_locations[ $slug ] ] ) ) {
									printf(
										wp_kses(
											/* translators: %s is the current menu name wrapped in a span. */
											__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)' ),
											array( 'span' => array( 'class' => true ) )
										),
										esc_html( $menus[ $menu_locations[ $slug ] ] )
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
	<li id="customize-control-nav_menu-brand-new-auto_add"
		class="customize-control customize-control-nav_menu_auto_add no-drag"
		data-setting-id="nav_menu[brand-new]"
	>
		<span class="customize-control-title">
			<?php esc_html_e( 'Menu Options' ); ?>
		</span>
		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>
		<span class="customize-inside-control-row">
			<input id="customize-nav-menu-auto-add-control-brand-new" type="checkbox" class="auto_add">
			<label for="customize-nav-menu-auto-add-control-brand-new">
				<?php esc_html_e( 'Automatically add new top-level pages to this menu' ); ?>
			</label>
		</span>
	</li>
	<li id="customize-control-nav_menu-brand-new-delete"
		class="customize-control customize-control-undefined no-drag"
		data-setting-id="nav_menu[brand-new]"
	>
		<div class="customize-control-notifications-container" style="display: none;">
			<ul></ul>
		</div>
		<div class="menu-delete-item">
			<button type="button" class="button-link button-link-delete">
				<?php esc_html_e( 'Delete Menu' ); ?>
			</button>
		</div>
	</li>
</template>

<!-- Template for moving widget to different sidebar -->
<template id="tmpl-change-sidebar">
	<div id="move-widget-area" class="move-widget-area active" style="margin-top:-10px;margin-bottom:10px;">
		<p class="description">
			<?php esc_html_e( 'Select an area to move this widget into:' ); ?>
		</p>
		<ul class="widget-area-select">
			
			<?php
			global $wp_registered_sidebars;
			foreach ( $wp_registered_sidebars as $sidebar ) {
				?>

				<li class="" data-id="<?php echo esc_attr( $sidebar['id'] ); ?>" tabindex="0">
					<div>
						<strong>
							<?php echo esc_attr( $sidebar['name'] ); ?>
						</strong>
					</div>
					<div>
						<?php echo esc_attr( $sidebar['description'] ); ?>
					</div>
				</li>

				<?php
			}
			?>

		</ul>
		<div class="move-widget-actions">
			<button class="move-widget-btn button" type="button" disabled="">
				<?php esc_html_e( 'Move' ); ?>
			</button>
		</div>
	</div>
</template>

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

/**
 * Fire admin scripts in the footer
 *
 * @since CP-2.8.0
 */
do_action( 'admin_print_footer_scripts' );
?>

</body>
</html>
