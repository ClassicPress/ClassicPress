<?php
declare( strict_types = 1 ); // Forces exact type matching
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
		'<h1>' . esc_html__( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . esc_html__( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
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

$wp_customize->setup_theme();
$wp_customize->register_controls();

/**
 * Process submitted form.
 */
if ( isset( $_POST['cp_publish_submit'] ) ) {
    // Collect essentials from the manager.
    $uuid       = isset( $_POST['customize_changeset_uuid'] ) ? sanitize_text_field( wp_unslash( $_POST['customize_changeset_uuid'] ) ) : '';
    $stylesheet = $wp_customize->get_stylesheet(); // nonce is tied to this
    $nonce      = wp_create_nonce( 'save-customize_' . $stylesheet );

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'save-customize_' . $stylesheet ) ) {
		wp_die( esc_html__( 'Security check failed.' ), 403 );
	}

	if ( ! current_user_can( 'customize' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.' ), 403 );
	}

    // Build changeset payload from submitted form values.
    // Here we assume form posts setting values as: customize_post_value[SETTING_ID]
    $submitted = isset( $_POST['customize_post_value'] ) ? (array) $_POST['customize_post_value'] : array();
    $submitted = map_deep( wp_unslash( $submitted ), 'sanitize_text_field' ); // Unslash first, then sanitize
    $changeset = array();

    foreach ( $submitted as $setting_id => $raw_value ) {
        // Optional: sanitize via the registered setting, if present.
        if ( $setting = $wp_customize->get_setting( $setting_id ) ) {
            $value = $setting->sanitize( wp_unslash( $raw_value ) );
        } else {
            $value = wp_unslash( $raw_value );
        }
        // The save() endpoint expects: setting_id => [ 'value' => ... ]
        $changeset[ $setting_id ] = array( 'value' => $value );
    }

    // Compose POST body for the Ajax save endpoint.
    $body = array(
        'action'                     => 'customize_save',
        'nonce'                      => $nonce,
        'customize_changeset_uuid'   => $uuid,
        'customize_changeset_status' => 'publish', // 'draft'/'pending'/'future' also valid
        'customize_changeset_title'  => 'PHP publish from customize.php',
        'customize_changeset_data'   => wp_json_encode( $changeset ),
    );

    // Carry current admin cookies so the request is authenticated.
    $args = array(
        'timeout' => 15,
        'headers' => array( 'Cookie' => isset( $_SERVER['HTTP_COOKIE'] ) ? $_SERVER['HTTP_COOKIE'] : '' ),
        'body'    => $body,
    );

    // POST to core Ajax handler; this invokes WP_Customize_Manager::save().
    $url      = admin_url( 'admin-ajax.php' );
    $response = wp_remote_post( $url, $args );

    // Handle response (save() returns JSON with success/error).
    if ( is_wp_error( $response ) ) {
        wp_die( esc_html( $response->get_error_message() ), 500 );
    } else {
        $code = wp_remote_retrieve_response_code( $response );
        $json = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $code !== 200 || empty( $json['success'] ) ) {
            error_log( 'Customizer save failed: HTTP ' . $code . ' — ' . print_r( $json, true ) );
        } else {
            // Optional: redirect back to the Customizer with the UUID (same as JS flow).
            wp_safe_redirect( add_query_arg( 'customize_changeset_uuid', $uuid, admin_url( 'customize.php' ) ) );
            error_log( 'Customizer save succeeded: HTTP ' . $code . ' — ' . print_r( $json, true ) );
            exit;
        }
    }
}

// Themes
$installed_themes = wp_prepare_themes_for_js();
$count_themes     = count( $installed_themes );

// Menus
$locations      = get_registered_nav_menus(); // slug => human label
$menu_locations = get_nav_menu_locations();   // slug => menu ID
$menus          = wp_get_nav_menus( array( 'fields' => 'id=>name' ) );

// Panels, sections, and controls
$panels   = $wp_customize->panels();
$sections = $wp_customize->sections();
$controls = $wp_customize->controls_data_by_section;

// Build top-level items: panels + sections without panel.
$top_items = array();

// Build mid-level sections
$middle_sections = array();

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
 * Collect widgets in a name-keyed array for sorting.
 */
$available_widgets = array();
$widgets_panel = isset( $panels['widgets'] ) ? $panels['widgets'] : null;
if ( $widgets_panel ) {
	require_once( ABSPATH . 'wp-admin/includes/widgets.php' );
	global $wp_registered_widgets, $wp_registered_widget_controls;

	// Collect widgets in a name-keyed array for sorting.
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

		$available_widgets[ $widget['name'] . '||' . $id ] = array(
			'id'      => $id,
			'id_base' => $id_base,
			'name'    => $widget['name'],
			'desc'    => isset( $widget['description'] ) ? $widget['description'] : '',
			'control' => $control,
		);
	}
	ksort( $available_widgets, SORT_NATURAL | SORT_FLAG_CASE );
}


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
		<form id="customize-controls" class="wrap wp-full-overlay-sidebar" style="position: static;"
			action="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"
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
							value="<?php esc_html_e( 'Published' ); ?>"
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

								$top_object = $item['type'] === 'section' 
									? $wp_customize->get_section( $item['id'] )
									: $wp_customize->get_panel( $item['id'] );
        
								if ( $top_object ) {
									$top_object->maybe_render();
								}
							}
							?>
						</ul>

						<ul id="sub-accordion-section-themes" class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-themes current-panel" style="display: none;">
							<li class="panel-meta customize-info accordion-section">
								<button class="customize-panel-back" tabindex="0" type="button">
									<span class="screen-reader-text">
										<?php esc_html_e( 'Back' ); ?>
									</span>
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
												// Display the active theme first
												foreach ( $installed_themes as $theme ) {
													if ( $theme['id'] !== get_transient( 'core_true_stylesheet' ) ) {
														continue;
													}
													?>

													<li id="customize-control-installed_theme_<?php esc_attr_e( $theme['id'] ); ?>"
														class="customize-control customize-control-theme"
														data-id="<?php esc_attr_e( $theme['id'] ); ?>"
														data-customize="<?php esc_attr_e( $theme['actions']['customize'] ); ?>"
														data-delete="<?php esc_attr_e( $theme['actions']['delete'] ); ?>"
														data-description="<?php esc_attr_e( $theme['description'] ); ?>"
														data-author="<?php esc_attr_e( $theme['author'] ); ?>"
														data-tags="<?php esc_attr_e( $theme['tags'] ); ?>"
														data-num-ratings=""
														data-version="<?php esc_attr_e( $theme['version'] ); ?>"
														data-wp="<?php esc_attr_e( $theme['compatibleWP'] ); ?>" 
														data-php="<?php esc_attr_e( $theme['compatiblePHP'] ); ?>"
													>
														<div class="customize-control-notifications-container" style="display: none;">
															<ul></ul>
														</div>
														<div class="theme active" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action">
															<div class="theme-screenshot">
																<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_url( $theme['screenshot'][0] ); ?>">
															</div>
															<span class="more-details theme-details"
																id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action"
																aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>"
															>
																<?php esc_html_e( 'Theme Details' ); ?>
															</span>
															<div class="theme-author">
																<?php esc_html_e( 'By' ); ?>
																<?php esc_html_e( $theme['author'] ); ?>
															</div>
															<div class="theme-id-container">
																<h3 class="theme-name" id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-name">
																	<?php esc_html_e( $theme['name'] ); ?>
																</h3>
																<div class="theme-actions">
																	<a href="?theme=<?php esc_attr_e( $theme['id'] ); ?>"
																		class="button button-primary customize-theme"
																		aria-label="<?php esc_attr_e( 'Customize theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>"
																	>
																		<?php esc_html_e( 'Customize' ); ?>
																	</a>
																</div>
															</div>
															<div class="notice notice-success notice-alt">
																<p>
																	<?php esc_html_e( 'Installed' ); ?>
																</p>
															</div>			
														</div>
													</li>

													<?php
													break; // No need to cycle through other themes
												}

												// Now display the rest
												foreach ( $installed_themes as $theme ) {
													if ( $theme['id'] === get_transient( 'core_true_stylesheet' ) ) {
														continue;
													}
													?>

													<li id="customize-control-installed_theme_<?php esc_attr_e( $theme['id'] ); ?>"
														class="customize-control customize-control-theme"
														data-id="<?php esc_attr_e( $theme['id'] ); ?>"
														data-activate="<?php esc_attr_e( $theme['actions']['activate'] ); ?>" 
														data-customize="<?php esc_attr_e( $theme['actions']['customize'] ); ?>"
														data-delete="<?php esc_attr_e( $theme['actions']['delete'] ); ?>"
														data-description="<?php esc_attr_e( $theme['description'] ); ?>"
														data-author="<?php esc_attr_e( $theme['author'] ); ?>"
														data-tags="<?php esc_attr_e( $theme['tags'] ); ?>"
														data-num-ratings=""
														data-version="<?php esc_attr_e( $theme['version'] ); ?>"
														data-wp="<?php esc_attr_e( $theme['compatibleWP'] ); ?>"
														data-php="<?php esc_attr_e( $theme['compatiblePHP'] ); ?>"
													>
														<div class="customize-control-notifications-container" style="display: none;">
															<ul></ul>
														</div>
														<div class="theme" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action">
															<div class="theme-screenshot">
																<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_url( $theme['screenshot'][0] ); ?>">
															</div>
															<span id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action"
																class="more-details theme-details"
																aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>"
															>
																<?php esc_html_e( 'Theme Details' ); ?>
															</span>
															<div class="theme-author">
																<?php esc_html_e( 'By' ); ?>
																<?php esc_html_e( $theme['author'] ); ?>
															</div>
															<div class="theme-id-container">
																<h3 id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-name" class="theme-name">
																	<?php esc_html_e( $theme['name'] ); ?>
																</h3>
																<div class="theme-actions">
																	<a href="?theme=<?php esc_attr_e( $theme['id'] ); ?>"
																		class="button button-primary preview-theme"
																		aria-label="<?php esc_attr_e( 'Live preview theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>"
																	>
																		<?php esc_html_e( 'Live Preview' ); ?>
																	</a>
																</div>
															</div>
															<div class="notice notice-success notice-alt">
																<p><?php esc_html_e( 'Installed' ); ?></p>
															</div>			
														</div>
													</li>

												<?php
												}
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

								<?php
								$nav_menus_panel = $wp_customize->get_panel( 'nav_menus' );
								$nav_menus_panel->render_content();

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
										if ( is_numeric( $menu_key ) ) {
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
											<p class="add-new-menu-notice">
												<?php _e( 'You&#8217;ll create a menu, assign it a location, and add menu items like links to pages and categories. If your theme has multiple menu areas, you might need to create more than one.' ); ?>
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

								<?php
								$widget_panel = $wp_customize->get_panel( 'widgets' );
								$widget_panel->render_content();

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

						// Remaining sub-accordions
						foreach ( $top_items as $item ) {
							if ( in_array( $item['id'], array( 'themes', 'nav_menus', 'widgets' ), true ) ) {
								continue;
							}
							if ( $item['type'] === 'panel' ) {
								$panel = $wp_customize->get_panel( $item['id'] );
								?>
								<ul id="sub-accordion-panel-<?php esc_attr_e( $item['id'] ); ?>"
									class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-<?php esc_attr_e( $item['id'] ); ?>"
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
												printf( __( 'You are customizing %s' ), '<strong class="panel-title">' . esc_html( $panel->title ) . '</strong>' );
												?>

											</span>
											<button type="button" class="customize-help-toggle dashicons dashicons-editor-help" aria-expanded="false">
												<span class="screen-reader-text">
													<?php esc_html_e( 'Help' ); ?>
												</span>
											</button>
										</div>

										<?php
										if ( ! empty( $panel->description ) ) {
											?>
											<div class="description customize-panel-description">
												<?php echo wp_kses_post( $panel->description ); ?>
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
									foreach ( $sections as $section ) {
										if ( $section->panel === $item['id'] ) {
											$section->maybe_render();
										}
									}
									?>

								</ul>

								<?php
								foreach ( $middle_sections as $middle_section ) {
									?>

									<ul id="sub-accordion-section-<?php esc_attr_e( $middle_section['id'] ); ?>"
										class="customize-pane-child accordion-section-content accordion-section control-section control-section-default"
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
															__( $item['title'] )
														);
														?>
													</span>
													<?php esc_html_e( $middle_section['title'] ); ?>
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
										if ( isset( $controls[ $middle_section['id'] ] ) && is_array( $controls[ $middle_section['id'] ] ) ) {
											// Sort controls by priority, lowest first.
											usort(
												$controls[ $middle_section['id'] ],
													static function ( $a, $b ) {
													$ap = isset( $a['priority'] ) ? (int) $a['priority'] : 999;
													$bp = isset( $b['priority'] ) ? (int) $b['priority'] : 999;
													return $ap - $bp;
												}
											);

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

								<ul id="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>"
									class="customize-pane-child accordion-section-content accordion-section control-section control-section-default"
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
									<span class="customize-control-title">
										<?php esc_html_e( 'Menu Name' ); ?>
									</span>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
									<input type="text" class="menu-name-field live-update-section-title" aria-describedby="add_menu-description">
								</label>
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
												<input id="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>"
													type="checkbox"
													data-menu-id="<?php esc_attr_e( $menu_locations[$key] ); ?>"
													data-location-id="<?php esc_attr_e( $location ); ?>"
													class="menu-location"
												>
												<label for="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>">
													<?php esc_html_e( $location ); ?>
													<span class="theme-location-set">
														<?php
														if ( isset ( $menus[$menu_locations[$key]] ) ) {
															printf(
																__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)</span>' ),
																/* translators: Name of menu. */
																$menus[$menu_locations[$key]]
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
									$field_id    = $control_data['id'];
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
													value="<?php esc_attr_e( $menus[$menu_id] ); ?>"
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

													<?php
													// Individual menu items are not pre-registered and so need dynamic instantiation
													$nav_menu_item_control = new WP_Customize_Nav_Menu_Item_Control(
														$wp_customize,
														'nav_menu_item[' . $menu_item->ID . ']',
														array(
															'label'    => $menu_item->post_title ?: $menu_item->title,
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
											<p class="screen-reader-text" id="reorder-items-desc-<?php esc_attr_e( $menu_id ); ?>">
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
																	if ( isset ( $menus[$menu_locations[$key]] ) ) {
																		printf(
																			__( '(Current: <span class="current-menu-location-name-main-nav">%s</span>)</span>' ),
																			/* translators: Name of menu. */
																			$menus[$menu_locations[$key]]
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
												<input id="customize-nav-menu-auto-add-control-<?php esc_attr_e( $menu_id ); ?>" type="checkbox" class="auto_add">
												<label for="customize-nav-menu-auto-add-control-<?php esc_attr_e( $menu_id ); ?>">
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
										?>

										<li id="customize-control-<?php esc_attr_e( $field_id ); ?>"
											class="customize-control customize-control-<?php esc_attr_e( $field_type ); ?>"
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
								<li id="customize-control-sidebars_widgets-<?php esc_attr_e( $section->id ); ?>"
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
										aria-describedby="reorder-widgets-desc-sidebars_widgets-<?php esc_attr_e( $section->id ); ?>"
									>
										<span class="reorder"><?php esc_html_e( 'Reorder' ); ?></span>
										<span class="reorder-done"><?php esc_html_e( 'Done' ); ?></span>
									</button>
									<p class="screen-reader-text" id="reorder-widgets-desc-sidebars_widgets-<?php esc_attr_e( $section->id ); ?>">
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
			<input type="hidden" name="customize_changeset_uuid"
				value="<?php esc_attr_e( $wp_customize->changeset_uuid() ); ?>"
			>
			<input type="hidden" name="nonce"
				value="<?php esc_attr_e( wp_create_nonce( 'save-customize_' . $wp_customize->get_stylesheet() ) ); ?>"
			>
			<input type="hidden" name="customize_form_stage" value="php-first-paint">
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
