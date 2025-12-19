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

$wp_customize->setup_theme();
$wp_customize->register_controls();

/**
 * @since CP-2.7.0
 */
// Themes
$installed_themes = wp_prepare_themes_for_js();
$count_themes     = count( $installed_themes );

// Menus
$locations      = get_registered_nav_menus(); // slug => human label
$menu_locations = get_nav_menu_locations();   // slug => menu ID
$menus_by_id    = wp_get_nav_menus();         // list of WP_Term
$menus_index    = array();
foreach ( $menus_by_id as $menu_term ) {
    $menus_index[ $menu_term->term_id ] = $menu_term;
}

$unique_nav_id  = uniqid( 'customize-control-nav_menu--', true );
$unique_add_id  = uniqid( 'customize-nav-menu-auto-add-control-' );
$unique_loc_id  = uniqid( 'customize-nav-menu-control-location-' );

// Panels, sections, and controls
$panels   = $wp_customize->panels();
$sections = $wp_customize->sections();
$controls = $wp_customize->get_all_controls_data();

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
<div class="wp-full-overlay preview-desktop" aria-labelledby="customizer-modal-title" style="display: flex;justify-content: space-between;min-width: 100%;position: static;margin: 0;" open>

	<h2 id="customizer-modal-title" class="screen-reader-text"><?php esc_html_e( 'Customizing: ' .  get_bloginfo( 'name' ) ); ?></h2>
	<form id="customize-controls" class="wrap wp-full-overlay-sidebar" style="position: static;width: 18%;">
		<div id="customize-header-actions" class="wp-full-overlay-header" style="position: static;">

			<?php
			$compatible_wp  = is_wp_version_compatible( $wp_customize->theme()->get( 'RequiresWP' ) );
			$compatible_php = is_php_version_compatible( $wp_customize->theme()->get( 'RequiresPHP' ) );

			if ( $compatible_wp && $compatible_php ) {
				$save_text = $wp_customize->is_theme_active() ? __( 'Published' ) : __( 'Activate &amp; Publish' );
				?>
				<div id="customize-save-button-wrapper" class="customize-save-button-wrapper" disabled>
					<input type="submit" name="save" id="save" class="button button-primary save" value="<?php esc_html_e( 'Published' ); ?>" disabled>
					<button id="publish-settings" class="publish-settings button-primary button dashicons dashicons-admin-generic" aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>" aria-expanded="false" style="display: none;"></button>
				</div>
				<?php
			} else {
				$save_text = _x( 'Cannot Activate', 'theme' );
				?>

				<div id="customize-save-button-wrapper" class="customize-save-button-wrapper disabled" >
					<button class="button button-primary disabled" aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>" aria-expanded="false" disabled>
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

		<div id="widgets-right" class="wp-clearfix"  style="overflow-y: scroll;max-height: calc(100vh - 90px);"><!-- For Widget Customizer, many widgets try to look for instances under div#widgets-right, so we have to add that ID to a container div in the Customizer for compat -->
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
						<li id="accordion-section-themes" class="accordion-section control-panel-themes" aria-owns="sub-accordion-section-themes">
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
						
						<li id="accordion-section-publish_settings" class="accordion-section control-section control-section-outer" aria-owns="sub-accordion-section-publish_settings">
							<h3 class="accordion-section-title" tabindex="0">
								<?php esc_html_e( 'Publish Settings' ); ?>
								<span class="screen-reader-text"><?php esc_html_e( 'Press return or enter to open this section' ); ?></span>
							</h3>
						</li>

						<?php
						foreach ( $top_items as $item ) {
							if ( $item['id'] === 'themes' ) { // Don't repeat the active theme
								continue;
							}			
							if ( $item['type'] === 'section'  ) {
								?>
								<li id="accordion-section-<?php esc_attr_e( $item['id'] ); ?>" class="accordion-section control-section control-section-default" aria-owns="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>">
									<h3 class="accordion-section-title" tabindex="0">
										<?php esc_html_e( $item['title'] ); ?>
										<span class="screen-reader-text"><?php esc_html_e( 'Press return or enter to open this section' ); ?></span>
									</h3>
								</li>
								<?php
							} else { // panel
								?>
								<li id="accordion-panel-<?php esc_attr_e( $item['id'] ); ?>" class="accordion-section control-section control-panel control-panel-<?php esc_attr_e( $item['id'] ); ?>" aria-owns="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>">
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
							<button type="button" class="customize-themes-section-title themes-section-installed_themes selected" aria-expanded="true"><?php esc_html_e( 'Installed themes' ); ?></button>
						</li>
						<li id="accordion-section-wporg_themes" class="theme-section control-subsection">
							<button type="button" class="customize-themes-section-title themes-section-wporg_themes" aria-expanded="false"><?php esc_html_e( 'WordPress.org themes' ); ?></button>
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
								</div>
								<div class="customize-themes-section themes-section-installed_themes control-section-content themes-php current-section">											
									<div class="theme-browser rendered local">
										<div class="customize-preview-header themes-filter-bar">
											<button type="button" class="button button-primary customize-section-back customize-themes-mobile-back" style="display: none;"><?php esc_html_e( 'Go to theme sources' ); ?></button>
											<div class="themes-filter-container">
												<label for="installed_themes-themes-filter" class="screen-reader-text"><?php esc_html_e( 'Search themes…' ); ?></label>
												<input type="search" id="installed_themes-themes-filter" placeholder="<?php esc_attr_e( 'Search themes…' ); ?>" aria-describedby="installed_themes-live-search-desc" class="wp-filter-search wp-filter-search-themes">
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
														Filter themes (<span class="theme-filter-count">0</span>)
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
												<li id="customize-control-installed_theme_<?php esc_attr_e( $theme['id'] ); ?>" class="customize-control customize-control-theme" data-id="<?php esc_attr_e( $theme['id'] ); ?>" data-customize="<?php esc_attr_e( $theme['actions']['customize'] ); ?>" data-delete="<?php esc_attr_e( $theme['actions']['delete'] ); ?>" data-description="<?php esc_attr_e( $theme['description'] ); ?>" data-author="<?php esc_attr_e( $theme['author'] ); ?>" data-tags="<?php esc_attr_e( $theme['tags'] ); ?>" data-num-ratings="" data-version="<?php esc_attr_e( $theme['version'] ); ?>" data-wp="<?php esc_attr_e( $theme['compatibleWP'] ); ?>" data-php="<?php esc_attr_e( $theme['compatiblePHP'] ); ?>">
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<div class="theme active" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action">
														<div class="theme-screenshot">
															<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_url( $theme['screenshot'][0] ); ?>">
														</div>
														<span class="more-details theme-details" id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action" aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>">
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
																<button type="button" class="button button-primary customize-theme" aria-label="<?php esc_attr_e( 'Customize theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>">
																	<?php esc_html_e( 'Customize' ); ?>
																</button>
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
												<li id="customize-control-installed_theme_<?php esc_attr_e( $theme['id'] ); ?>" class="customize-control customize-control-theme" data-id="<?php esc_attr_e( $theme['id'] ); ?>" data-activate="<?php esc_attr_e( $theme['actions']['activate'] ); ?>" data-customize="<?php esc_attr_e( $theme['actions']['customize'] ); ?>" data-delete="<?php esc_attr_e( $theme['actions']['delete'] ); ?>" data-description="<?php esc_attr_e( $theme['description'] ); ?>" data-author="<?php esc_attr_e( $theme['author'] ); ?>" data-tags="<?php esc_attr_e( $theme['tags'] ); ?>" data-num-ratings="" data-version="<?php esc_attr_e( $theme['version'] ); ?>" data-wp="<?php esc_attr_e( $theme['compatibleWP'] ); ?>" data-php="<?php esc_attr_e( $theme['compatiblePHP'] ); ?>">
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<div class="theme" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action">
														<div class="theme-screenshot">
															<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_url( $theme['screenshot'][0] ); ?>">
														</div>
														<span id="installed_themes-<?php esc_attr_e( $theme['id'] ); ?>-action" class="more-details theme-details" aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>">
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
																<button type="button" class="button button-primary preview-theme" aria-label="<?php esc_attr_e( 'Live preview theme:' ); ?> <?php esc_html_e( $theme['name'] ); ?>">
																	<?php esc_html_e( 'Live Preview' ); ?>
																</button>
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
							<li class="customize-control-title customize-section-title-nav_menus-heading"><?php esc_html_e( $menus_panel->title ); ?></li>

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
									<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>" class="accordion-section control-section control-section-nav_menu control-subsection assigned-to-menu-location" aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>">
										<h3 class="accordion-section-title" tabindex="0">
											<?php esc_html_e( $section->title ); ?>
											<span class="screen-reader-text">
												<?php esc_html_e( 'Press return or enter to open this section' ); ?>
											</span>
											<?php
											if ( $current_location_label ) :
												?>
												<span class="menu-in-location">
													<?php esc_html_e( $current_location_label ); ?>
												</span>
												<?php
											endif;
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
								<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>" class="accordion-section control-section control-section-new_menu control-subsection" aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>">
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
										<button type="button" class="button customize-add-menu-button"><?php esc_html_e( 'Create New Menu' ); ?></button>
									</h3>
								</li>
								<?php
							}
							foreach ( $sections as $section ) { // Menu Locations.
								if ( $section->id !== 'menu_locations' ) {
									continue;
								}
								?>
								<li id="accordion-section-<?php esc_attr_e( $section->id ); ?>" class="accordion-section control-section control-section-nav_menu_locations control-subsection" aria-owns="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>">
									<span class="customize-control-title customize-section-title-menu_locations-heading"><?php esc_html_e( 'Menu Locations' ); ?></span>
									<div class="customize-control-description customize-section-title-menu_locations-description"><?php echo wp_kses_post( $section->description ); ?></div>
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

						<ul id="sub-accordion-panel-widgets" class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-section control-panel control-panel-widgets" style="display: none;">
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
								<li id="accordion-section-<?php echo esc_attr( $section->id ); ?>" class="accordion-section control-section control-section-widgets control-subsection" aria-owns="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>">
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

					foreach ( $top_items as $item ) {
						// Skip items with bespoke sub‑accordion implementations.
						if ( in_array( $item['id'], array( 'themes', 'nav_menus', 'widgets' ), true ) ) {
							continue;
						}
						if ( 'section' === $item['type'] ) {
							// Default section handling.
							?>

							<ul id="sub-accordion-section-<?php esc_attr_e( $item['id'] ); ?>" class="customize-pane-child accordion-section-content accordion-section control-section control-section-default" style="display: none;">
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
								</li>

								<?php
								if ( isset ( $controls[ $item['id'] ] ) ) {
									foreach ( $controls[ $item['id'] ] as $control_data ) {
										$field_name  = $control_data['setting_id'] ?: $control_data['id'];
										$field_value = $control_data['value'];
										$type        = $control_data['type'];
										?>

										<li id="customize-control-<?php esc_attr_e( $field_name ); ?>" class="customize-control customize-control-text">
											<div class="customize-control customize-control-<?php esc_attr_e( $type ); ?>">
												<?php
												if ( 'site_icon' !== $type ) {
													?>
													<label class="customize-control-title" for="<?php esc_attr_e( $field_name ); ?>">
														<?php esc_html_e( $control_data['label'] ?: $control_data['id'] ); ?>
													</label>
													<?php
												}
												// Very simple type-to-input mapping. error_log(print_r($control_data, true));
												if ( in_array( $type, array( 'text', 'url', 'email', 'number' ), true ) ) {
													?>
													<input type="text" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="<?php esc_attr_e( $field_value ); ?>" class="regular-text">
													<?php
												} elseif ( 'checkbox' === $type ) {
													$checked = $field_value ? ' checked="checked"' : '';
													?>
													<input type="checkbox" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="1"' . $checked . ' style="margin: 0;">
													<?php
												} elseif ( 'textarea' === $type ) {
													?>
													<textarea id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" rows="4" class="large-text"><?php echo esc_textarea( (string) $field_value ); ?></textarea>
													<?php
												} elseif ( 'color' === $type ) {
													$raw_value = (string) $field_value;

													// If it looks like a bare 3/6-digit hex, prefix with #.
													if ( preg_match( '/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $raw_value ) ) {
														$color_value = '#' . $raw_value;
													} else {
														$color_value = $raw_value;
													}
													?>
													<div class="customize-control-content">
														<input type="text" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" class="cp-color-picker" value="<?php esc_attr_e( $color_value ); ?>" data-default-color="<?php esc_attr_e( $color_value ); ?>" placeholder="<?php esc_attr_e( 'Select Color' ); ?>">
													</div>
													<?php
												} elseif ( 'site_icon' === $type ) {
													?>
													<span class="customize-control-title"><?php esc_html_e( $control_data['label'] ); ?></span>
													<div class="customize-control-notifications-container" style="display: none;">
														<ul></ul>
													</div>
													<?php
												} elseif ( 'cropped_image' === $type ) {
													?>
													<div class="attachment-media-view">
														<div class="site-icon-preview wp-clearfix customize-control-site_icon">
															<div class="favicon-preview">
																<img src="<?php echo esc_url( admin_url( '/images/browser.png' ) ); ?>" class="browser-preview" alt="">
																<div class="favicon">
																	<?php
																	if ( get_site_icon_url() !== '' ) {
																		?>

																		<img src="<?php echo esc_url( get_site_icon_url() ); ?>" alt="<?php esc_attr_e( 'Preview as a browser icon' ); ?>">';

																		<?php
																	}
																	?>
																</div>
																<span class="browser-title" aria-hidden="true"><?php esc_html_e( get_bloginfo( 'name' ) ); ?></span>
															</div>
															<?php
															if ( get_site_icon_url() !== '' ) {
																echo '<img class="app-icon-preview" src="' . esc_url( get_site_icon_url() ) . '" alt="' . esc_attr( 'Preview as an app icon' ) . '">';
															}
															?>
														</div>
														<div class="actions">	
															<button type="button" class="button remove-button"><?php esc_html_e( 'Remove' ); ?></button>
															<button type="button" class="button upload-button"><?php esc_html_e( 'Change image' ); ?></button>
														</div>
													</div>

													<?php
												} else {
													// Fallback generic input.
													?>
													<input type="text" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="<?php //esc_attr_e( $field_value ); ?>" class="regular-text">
													<?php
												}
												if ( ! empty ( $control_data['description'] ) ) {
													?>
													<div class="description customize-control-description"><?php echo wp_kses_post( $control_data['description'] ); ?></div>
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
						} else { // panel
							// Default panel handling (for panels other than nav_menus).
							foreach ( $sections as $section ) {
								if ( $section->panel === $item['id'] ) {
									?>

									<ul id="sub-accordion-panel-<?php esc_attr_e( $section->panel ); ?>" class="customize-pane-child accordion-section-content accordion-section control-section control-section-default" style="display: none;">
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
												<div class="customize-control-notifications-container"></div>
											</div>
										</li>
										<span class="customize-section-label"><?php esc_html( $section->title ); ?></span>

										<?php
										// Controls inside this section.
										if ( isset ( $controls[ $section->id ] ) ) {
											foreach ( $controls[ $section->id ] as $control_data ) {
												$field_name  = $control_data['setting_id'] ?: $control_data['id'];
												$field_value = $control_data['value'];
												$type        = $control_data['type'];
												?>

												<li class="customize-control customize-control-<?php esc_attr_e( $type ); ?>">
													<label class="customize-control-title" for="<?php esc_attr_e( $field_name ); ?>">
														<?php esc_html_e( $control_data['label'] ?: $control_data['id'] ); ?>
													</label>

													<?php
													// Very simple type-to-input mapping.
													if ( in_array( $type, array( 'text', 'url', 'email', 'number' ), true ) ) {
														?>
														<input type="text" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="<?php esc_attr_e( $field_value ); ?>" class="regular-text">
														<?php
													} elseif ( 'checkbox' === $type ) {
														$checked = $field_value ? ' checked="checked"' : '';
														?>
														<input type="checkbox" id="' . esc_attr( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="1"' . $checked . '>
														<?php
													} elseif ( 'textarea' === $type ) {
														?>
														<textarea id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" rows="4" class="large-text"><?php echo esc_textarea( (string) $field_value ); ?></textarea>
														<?php
													} elseif ( 'color' === $type ) {
														$raw_value = (string) $field_value;

														// If it looks like a bare 3/6-digit hex, prefix with #.
														if ( preg_match( '/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $raw_value ) ) {
															$color_value = '#' . $raw_value;
														} else {
															$color_value = $raw_value;
														}
														?>
														<input type="color" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="<?php esc_attr_e( $color_value ); ?>">
														<?php
													} else {
														// Fallback generic input.
														/* ?>

														<input type="text" id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" value="<?php esc_attr_e( (string) $field_value ); ?>" class="regular-text">

														<?php */
													}
													if ( ! empty ( $control_data['description'] ) ) {
														?>
														<div class="description"><?php echo wp_kses_post( $control_data['description'] ); ?></div>
														<?php
													}
													?>
												</li>
												<?php
											}
										}
										?>
									</ul>
									<?php
								}
							}
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
											<input id="customize-nav-menu-control-location-<?php esc_attr_e( $menu_locations[$key] ); ?>" type="checkbox" data-menu-id="<?php esc_attr_e( $unique_nav_id ); ?>" data-location-id="<?php esc_attr_e( $location ); ?>" class="menu-location">
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
													} else {
														esc_html_e( '(Currently empty)' );
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
					// Render controls for each nav_menus section (locations + individual menus).
					foreach ( $sections as $section ) {
						if ( $section->panel !== 'nav_menus' ) {
							continue;
						}

						// Skip if there are no controls collected for this section.
						if ( empty ( $controls[ $section->id ] ) ) {
							continue;
						}

						$section_class = 'control-section-nav_menu';
						if ( 'nav_menu_locations' === $section->id || 'nav_menus[locations]' === $section->id ) {
							$section_class = 'control-section-nav_menu_locations';
						} elseif ( 0 === strpos( $section->id, 'nav_menus[' ) ) {
							$section_class = 'control-section-new_menu';
						}
						?>

						<ul id="sub-accordion-section-<?php esc_attr_e( $section->id ); ?>" class="customize-pane-child accordion-section-content accordion-section control-section <?php echo esc_attr( $section_class ); ?>" style="display: none;">
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
								if ( ! empty ( $section->description ) ) {
									?>
									<div class="description customize-section-description">
										<?php echo wp_kses_post( $section->description ); ?>
									</div>
									<?php
								}
								?>
							</li>
							<?php
							foreach ( $controls[ $section->id ] as $control_data ) {
								$field_name  = $control_data['setting_id'] ?: $control_data['id'];
								$field_value = $control_data['value'];
								$type        = $control_data['type'];
								?>
								<li id="customize-control-<?php echo esc_attr( $field_name ); ?>" class="customize-control customize-control-<?php echo esc_attr( $type ); ?>">
									<div class="customize-control-inner">
										<?php
										if ( ! empty ( $control_data['label'] ) ) {
											?>
											<label class="customize-control-title" for="<?php esc_attr_e( $field_name ); ?>">
												<?php esc_html_e( $control_data['label'] ); ?>
											</label>
										<?php
										}
										// Very simple type-to-input mapping; you can refine per-widget type later.
										if ( in_array( $type, array( 'text', 'url', 'email', 'number' ), true ) ) {
											?>
											<input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( (string) $field_value ); ?>" class="regular-text">
											<?php
										} elseif ( 'checkbox' === $type ) {
											$checked = $field_value ? ' checked="checked"' : '';
											?>
											<input type="checkbox" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="1"<?php echo $checked; ?>>
											<?php
										} elseif ( 'textarea' === $type ) {
											?>
											<textarea id="<?php esc_attr_e( $field_name ); ?>" name="<?php esc_attr_e( $field_name ); ?>" rows="4" class="large-text">
												<?php echo esc_textarea( (string) $field_value ); ?>
											</textarea>
											<?php
										} elseif ( 'color' === $type ) {
											$raw_value = (string) $field_value;
											if ( preg_match( '/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $raw_value ) ) {
												$color_value = '#' . $raw_value;
											} else {
												$color_value = $raw_value;
											}
											?>
											<input id="<?php esc_attr_e( $field_name ); ?>" type="color" name="<?php esc_attr_e( $field_name ); ?>" value="<?php echo esc_attr( $color_value ); ?>">
											<?php
										} elseif ( 'nav_menu_location' === $type ) {										
											$value_hidden_class    = '';
											$no_value_hidden_class = '';
											if ( $field_value ) {
												$value_hidden_class = ' hidden';
											} else {
												$no_value_hidden_class = ' hidden';
											}
											?>
											<select id="<?php esc_attr_e( $field_name ); ?>" data-customize-setting-link="<?php esc_attr_e( $field_name ); ?>">
												<option value="0">— Select —</option>
												<?php
												foreach ( $menus_index as $menu ) {
													echo '<option value="' . esc_attr( $menu->term_id ) . '"' . selected( $field_value, $menu->term_id, false ) . '>' . $menu->name . '</option>';
												}
												?>
											</select>
											<button type="button" class="button-link create-menu<?php echo $value_hidden_class; ?>" data-location-id="<?php esc_attr_e( substr( $field_name, strlen( 'nav_menu_locations[' ), -1 ) ); ?>" aria-label="<?php esc_attr_e( 'Create a menu for this location' ); ?>">
												<?php _e( '+ Create New Menu' ); ?>
											</button>
											<button type="button" class="button-link edit-menu<?php echo $no_value_hidden_class; ?>" aria-label="<?php esc_attr_e( 'Edit selected menu' ); ?>"><?php _e( 'Edit Menu' ); ?></button>
											<?php
										} else {
											// Fallback generic input.
											?>
											<input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php // echo esc_attr( (string) $field_value ); ?>" class="regular-text">
											<?php
										}

										if ( ! empty ( $control_data['description'] ) ) {
											?>
											<div class="description customize-control-description">
												<?php echo wp_kses_post( $control_data['description'] ); ?>
											</div>
											<?php
										}
										?>
									</div>
								</li>
								<?php
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

						<ul id="sub-accordion-section-<?php echo esc_attr( $section->id ); ?>" class="customize-pane-child accordion-section-content accordion-section control-section control-section-sidebar" style="display: none;">
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
							foreach ( $controls[ $section->id ] as $control_data ) {
								$field_name  = $control_data['setting_id'] ?: $control_data['id'];
								$field_value = $control_data['value'];
								$type        = $control_data['type'];
								?>

								<li id="customize-control-<?php echo esc_attr( $field_name ); ?>" class="customize-control customize-control-<?php echo esc_attr( $type ); ?>">
									<div class="customize-control-inner">
										<?php
										if ( ! empty ( $control_data['label'] ) ) {
											?>
											<label class="customize-control-title" for="<?php echo esc_attr( $field_name ); ?>">
												<?php echo esc_html( $control_data['label'] ); ?>
											</label>
											<?php
										}
										// Very simple type-to-input mapping; you can refine per-widget type later.
										if ( in_array( $type, array( 'text', 'url', 'email', 'number' ), true ) ) {
											?>
											<input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( (string) $field_value ); ?>" class="regular-text">
											<?php
										} elseif ( 'checkbox' === $type ) {
											$checked = $field_value ? ' checked="checked"' : '';
											?>
											<input type="checkbox" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="1"<?php echo $checked; ?>>
											<?php
										} elseif ( 'textarea' === $type ) {
											?>
											<textarea id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" rows="4" class="large-text">
												<?php echo esc_textarea( (string) $field_value ); ?>
											</textarea>
											<?php
										} elseif ( 'color' === $type ) {
											$raw_value = (string) $field_value;
											if ( preg_match( '/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $raw_value ) ) {
												$color_value = '#' . $raw_value;
											} else {
												$color_value = $raw_value;
											}
											?>
											<input type="color" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $color_value ); ?>">
											<?php
										} else {
											// Fallback generic input.
											?>
											<input type="text" id="<?php echo esc_attr( $field_name ); ?>" name="<?php echo esc_attr( $field_name ); ?>" value="<?php // echo esc_attr( (string) $field_value ); ?>" class="regular-text">
											<?php
										}
										if ( ! empty ( $control_data['description'] ) ) {
											?>
											<div class="description customize-control-description">
												<?php echo wp_kses_post( $control_data['description'] ); ?>
											</div>
											<?php
										}
										?>
									</div>
								</li>
								<?php
							}
							?>
						</ul>

						<ul id="menu-to-edit" class="customize-pane-child accordion-section-content accordion-section control-section control-section-nav_menu field-title-attribute-active menu open" style="display: none;">
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
							<li id="<?php esc_attr_e( $unique_nav_id ); ?>-name" class="customize-control customize-control-nav_menu_name no-drag">
								<label>
									<span class="customize-control-title">
										<?php esc_html_e( 'Menu Name' ); ?>
									</span>
									<div class="customize-control-notifications-container" style="display: none;">
										<ul></ul>
									</div>
									<input type="text" class="menu-name-field live-update-section-title">
								</label>
							</li>
							<li id="<?php esc_attr_e( $unique_nav_id ); ?>" class="customize-control customize-control-nav_menu no-drag">
								<div class="customize-control-notifications-container" style="display: none;">
									<ul></ul>
								</div>
								<p class="new-menu-item-invitation">
									<?php
									printf(
										/* translators: %s: "Add Items" button text. */
										__( 'Time to add some links! Click &#8220;%s&#8221; to start putting pages, categories, and custom links in your menu. Add as many things as you would like.' ),
										__( 'Add Items' )
									);
									?>
								</p>
								<div class="customize-control-nav_menu-buttons">
									<button type="button" class="button add-new-menu-item" aria-label="<?php esc_attr_e( 'Add or remove menu items' ); ?>" aria-expanded="false" aria-controls="available-menu-items">
										<?php esc_html_e( 'Add Items' ); ?>
									</button>
									<button type="button" class="button-link reorder-toggle" aria-label="<?php esc_attr_e( 'Reorder menu items' ); ?>" aria-describedby="reorder-items-desc-<?php esc_attr_e( $unique_nav_id ); ?>">
										<span class="reorder"><?php esc_html_e( 'Reorder' ); ?></span>
										<span class="reorder-done"><?php esc_html_e( 'Done' ); ?></span>
									</button>
								</div>
								<p class="screen-reader-text" id="reorder-items-desc-<?php esc_attr_e( $unique_nav_id ); ?>">
									<?php
									/* translators: Hidden accessibility text. */
									_e( 'When in reorder mode, additional controls to reorder menu items will be available in the items list above.' );
									?>
								</p>
							</li>
							<li id="<?php esc_attr_e( $unique_nav_id ); ?>-locations" class="customize-control customize-control-nav_menu_locations no-drag">
								<?php
								if ( current_theme_supports( 'menus' ) ) {
									?>
									<ul class="menu-location-settings">
										<li class="customize-control assigned-menu-locations-title no-drag">
											<span class="customize-control-title">
												<?php esc_html_e( 'Menu Locations' ); ?>
											</span>
											<p id="customize-menu-where">
												<?php echo _x( 'Where do you want this menu to appear?', 'menu locations' ); ?>
												<?php
												printf(
													/* translators: 1: Documentation URL, 2: Additional link attributes, 3: Accessibility text. */
													_x( '(If you plan to use a menu <a href="%1$s" %2$s>widget%3$s</a>, skip this step.)', 'menu locations' ),
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
											<p id="customize-menu-here">
												<?php echo _x( 'Here&#8217;s where this menu appears. If you would like to change that, pick another location.', 'menu locations' ); ?>
											</p>
										</li>

										<?php
										foreach ( $locations as $location => $description ) {
											?>
											<li class="customize-control customize-control-checkbox assigned-menu-location no-drag">
												<span class="customize-inside-control-row">
													<input id="<?php esc_attr_e( $unique_loc_id ); ?>" type="checkbox" data-menu-id="<?php esc_attr_e( $unique_nav_id ); ?>" data-location-id="<?php esc_attr_e( $location ); ?>" class="menu-location">
													<label for="<?php esc_attr_e( $unique_loc_id ); ?>">
														<?php esc_html_e( $description ); ?>
														<span class="theme-location-set">
															<?php
															printf(
																/* translators: %s: Menu name. */
																_x( '(Current: %s)', 'menu location' ),
																'<span class="current-menu-location-name-' . esc_attr( $location ) . '"></span>'
															);
															?>
														</span>
													</label>
												</span>
											</li>
											<?php
										}
										?>
									</ul>
									<?php
								}
								?>
							</li>
							<li id="<?php esc_attr_e( $unique_nav_id ); ?>-auto_add" class="customize-control customize-control-nav_menu_auto_add no-drag">
								<span class="customize-control-title">
									<?php esc_html_e( 'Menu Options' ); ?>
								</span>
								<span class="customize-inside-control-row">
									<input id="<?php esc_attr_e( $unique_add_id  ); ?>" type="checkbox" class="auto_add">
									<label for="<?php esc_attr_e( $unique_add_id  ); ?>">
										<?php esc_html_e( 'Automatically add new top-level pages to this menu' ); ?>
									</label>
								</span>
							</li>
							<li id="<?php esc_attr_e( $unique_nav_id ); ?>-delete" class="customize-control customize-control-undefined no-drag">
								<div class="customize-control-notifications-container" style="display: none;">
									<ul></ul>
								</div>
								<div class="menu-delete-item">
									<button type="button" class="button-link button-link-delete">
										<?php esc_html_e( 'Delete Menu' ); ?>
									</button>
								</div>
							</li>
						</ul>

						<?php
					}
					?>
				</div>
			</div>
		</div>

		<div id="customize-footer-actions" class="wp-full-overlay-footer">
			<button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="<?php esc_html_e( 'Hide Controls' ); ?>">
				<span class="collapse-sidebar-arrow"></span>
				<span class="collapse-sidebar-label"><?php esc_html_e( 'Hide Controls' ); ?></span>
			</button>
			<div class="devices-wrapper">
				<div class="devices">
					<button type="button" class="preview-desktop active" aria-pressed="true" data-device="desktop">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter desktop preview mode' ); ?></span>
					</button>
					<button type="button" class="preview-tablet" aria-pressed="false" data-device="tablet">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter tablet preview mode' ); ?></span>
					</button>
					<button type="button" class="preview-mobile" aria-pressed="false" data-device="mobile">
						<span class="screen-reader-text"><?php esc_html_e( 'Enter mobile preview mode' ); ?></span>
					</button>
				</div>
			</div>
		</div>

		<?php
		// Hidden field placeholder to align with the idea that this sidebar will
		// eventually submit changes (stage 2+).
		?>
		<input type="hidden" name="customize_form_stage" value="php-first-paint">
	</form><!-- /#customize-controls -->

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
