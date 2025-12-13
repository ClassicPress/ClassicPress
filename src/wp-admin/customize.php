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

$wp_customize->setup_theme();
$wp_customize->register_controls();

/**
 * @since CP-2.7.0
 */
$cp_controls_by_section = $wp_customize->get_all_controls_data();
$installed_themes = wp_get_themes();
$count_themes = count( $installed_themes );

// Collect panels and sections.
$panels   = $wp_customize->panels();
$sections = $wp_customize->sections();

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

// Sort by priority like core.
uasort(
	$top_items,
	static function ( $a, $b ) {
		$ap = isset( $a['priority'] ) ? (int) $a['priority'] : 999;
		$bp = isset( $b['priority'] ) ? (int) $b['priority'] : 999;
		return $ap <=> $bp;
	}
);

// Preview URL.
$preview_url = $wp_customize->get_preview_url();

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
<title><?php echo esc_html( $admin_title ); ?></title>
<?php

do_action( 'admin_enqueue_scripts', 'customize.php' );
do_action( 'admin_print_styles', 'customize.php' );
do_action( 'admin_head', 'customize.php' );

/**
 * Fires in head section of Customizer controls.
 *
 * @since 5.5.0
 */
do_action( 'customize_controls_head' );
?>
</head>
<body class="<?php echo esc_attr( $body_class ); ?>">
<div class="wp-full-overlay expanded preview-desktop">

	<form id="customize-controls" class="wrap wp-full-overlay-sidebar">
		<div id="customize-header-actions" class="wp-full-overlay-header">

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
					<button class="button button-primary disabled" aria-label="<?php esc_attr_e( 'Publish Settings' ); ?>" aria-expanded="false" disabled><?php echo $save_text; ?></button>
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

		<div id="widgets-right" class="wp-clearfix"><!-- For Widget Customizer, many widgets try to look for instances under div#widgets-right, so we have to add that ID to a container div in the Customizer for compat -->
			<div id="customize-notifications-area" class="customize-control-notifications-container">
				<ul></ul>
			</div>
			<div class="wp-full-overlay-sidebar-content" tabindex="-1">
				<div id="customize-info" class="accordion-section customize-info">
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
							<?php
							esc_html_e( '<a href="https://wordpress.org/documentation/article/customizer/">Documentation on Customizer</a>' );
							?>
						</p>
					</div>
				</div>

				<div id="customize-theme-controls">
					<ul class="customize-pane-parent">
						<li id="accordion-section-themes" class="accordion-section control-panel-themes" aria-owns="sub-accordion-section-themes" style="">
							<h3 class="accordion-section-title">
								<span class="customize-action"><?php esc_html_e( 'Active theme' ); ?></span>
								<?php esc_html_e( $top_items['themes']['title'] ); ?>
								<button type="button" class="button change-theme" aria-label="Change theme"><?php esc_html_e( 'Change' ); ?></button>
							</h3>
							<ul id="sub-accordion-section-themes">
								<li class="panel-meta customize-info accordion-section ">
									<button class="customize-panel-back" tabindex="0" type="button">
										<span class="screen-reader-text"><?php esc_html_e( 'Back' ); ?></span>
									</button>
									<div class="accordion-section-title">
										<span class="preview-notice"><?php esc_html_e( 'You are browsing' ); ?> <strong class="panel-title"><?php esc_html_e( 'Themes' ); ?></strong></span>
										<button class="customize-help-toggle dashicons dashicons-editor-help" type="button" aria-expanded="false"><span class="screen-reader-text"><?php esc_html_e( 'Help' ); ?></span></button>
									</div>
									<div class="description customize-panel-description">
										<p><?php esc_html_e( 'Looking for a theme? You can search or browse the WordPress.org theme directory, install and preview themes, then activate them right here.' ); ?></p>
										<p><?php esc_html_e( 'While previewing a new theme, you can continue to tailor things like widgets and menus, and explore theme-specific options.' ); ?></p>
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
									<div class="customize-themes-full-container animate" style="display:none;">
										<div class="customize-themes-notifications"></div>
										<div class="customize-themes-section themes-section-installed_themes control-section-content themes-php current-section">											
											<div class="theme-overlay" tabindex="0" role="dialog" aria-label="<?php esc_attr_e( 'Theme Details' ); ?>"></div>
											<div class="theme-browser rendered">
												<div class="customize-preview-header themes-filter-bar">
													<button type="button" class="button button-primary customize-section-back customize-themes-mobile-back"><?php esc_html_e( 'Go to theme sources' ); ?></button>
													<div class="themes-filter-container">
														<label for="installed_themes-themes-filter" class="screen-reader-text"><?php esc_html_e( 'Search themes…' ); ?></label>
														<input type="search" id="installed_themes-themes-filter" placeholder="<?php esc_attr_e( 'Search themes…' ); ?>" aria-describedby="installed_themes-live-search-desc" class="wp-filter-search wp-filter-search-themes">
														<div class="search-icon" aria-hidden="true"></div>
														<span id="installed_themes-live-search-desc" class="screen-reader-text">
															<?php esc_html_e( 'The search results will be updated as you type.' ); ?>
														</span>
													</div>
													<div class="filter-themes-count">
														<span class="themes-displayed" style="">
															<span class="theme-count"><?php esc_html_e( $count_themes ); ?></span>
															<?php esc_html_e( 'themes' ); ?>
														</span>
													</div>
												</div>
												<div class="error unexpected-error" style="display: none; ">
													<p><?php esc_html_e( 'An unexpected error occurred. Something may be wrong with WordPress.org, ClassicPress.net or this server’s configuration. If you continue to have problems, please try the <a href="https://forums.classicpress.net/c/support/">support forums</a>' ); ?>.</p>
												</div>
												<ul class="themes">

													<?php
													// Display the active theme first
													foreach( $installed_themes as $theme ) {
														if ( $theme->name === $top_items['themes']['title'] ) {
															?>

															<li id="customize-control-installed_theme_<?php esc_attr_e( $theme->get_stylesheet() ); ?>" class="customize-control customize-control-theme">
																<div class="customize-control-notifications-container" style="display: none;">
																	<ul></ul>
																</div>
																<div class="theme active" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-action">
																	<div class="theme-screenshot">
																		<img data-src="<?php echo esc_url( $theme->get_screenshot() ); ?>" alt="" src="<?php echo esc_url( $theme->get_screenshot() ); ?>">
																	</div>
																	<span class="more-details theme-details" id="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-action" aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme->name ); ?>"><?php esc_html_e( 'Theme Details' ); ?></span>
																	<div class="theme-author"><?php esc_html_e( 'By' ); ?> <?php esc_html_e( $theme['Author'] ); ?></div>
																	<div class="theme-id-container">
																		<h3 class="theme-name" id="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-name">
																			<span><?php esc_html_e( 'Previewing:' ); ?></span> <?php esc_html_e( $theme->name ); ?>
																		</h3>
																		<div class="theme-actions">
																			<button type="button" class="button button-primary customize-theme" aria-label="<?php esc_attr_e( 'Customize theme:' ); ?> <?php esc_html_e( $theme->name ); ?>"><?php esc_html_e( 'Customize' ); ?></button>
																		</div>
																	</div>
																	<div class="notice notice-success notice-alt">
																		<p><?php esc_html_e( 'Installed' ); ?></p>
																	</div>			
																</div>
															</li>

															<?php
														}
													}
							
													// Now display the rest
													foreach( $installed_themes as $theme ) {
														if ( $theme->name !== $top_items['themes']['title'] ) {
															?>

															<li id="customize-control-installed_theme_<?php esc_attr_e( $theme->get_stylesheet() ); ?>" class="customize-control customize-control-theme">
																<div class="customize-control-notifications-container" style="display: none;">
																	<ul></ul>
																</div>
																<div class="theme" tabindex="0" aria-describedby="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-action">
																	<div class="theme-screenshot">
																		<img data-src="<?php echo esc_url( $theme->get_screenshot() ); ?>" alt="" src="<?php echo esc_url( $theme->get_screenshot() ); ?>">
																	</div>
																	<span class="more-details theme-details" id="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-action" aria-label="<?php esc_attr_e( 'Details for theme:' ); ?> <?php esc_html_e( $theme->name ); ?>"><?php esc_html_e( 'Theme Details' ); ?></span>
																	<div class="theme-author"><?php esc_html_e( 'By' ); ?> <?php esc_html_e( $theme['Author'] ); ?></div>
																	<div class="theme-id-container">
																		<h3 class="theme-name" id="installed_themes-<?php esc_attr_e( $theme->get_stylesheet() ); ?>-name">
																			<span><?php esc_html_e( 'Previewing:' ); ?></span> <?php esc_html_e( $theme->name ); ?>
																		</h3>
																		<div class="theme-actions">
																			<button type="button" class="button button-primary customize-theme" aria-label="<?php esc_attr_e( 'Customize theme:' ); ?> <?php esc_html_e( $theme->name ); ?>"><?php esc_html_e( 'Customize' ); ?></button>
																		</div>
																	</div>
																	<div class="notice notice-success notice-alt">
																		<p><?php esc_html_e( 'Installed' ); ?></p>
																	</div>			
																</div>
														
															</li>

															<?php
														}
													}
													?>

												</ul>
											</div>											
										</div>
										<div class="customize-themes-section themes-section-wporg_themes control-section-content themes-php">
											<div class="theme-overlay" tabindex="0" role="dialog" aria-label="<?php esc_attr_e( 'Theme Details' ); ?>"></div>
											<div class="theme-browser rendered">
											
											</div>											
										</div>
									</div>
								</li>
							</ul>
						</li>
						
						<li id="accordion-section-publish_settings" class="accordion-section control-section control-section-outer" aria-owns="sub-accordion-section-publish_settings" style="">
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

							$item_id	= $item['id'];
							$item_title = $item['title'];
							$item_type  = $item['type']; // panel | section
							?>

							<li id="accordion-section-<?php esc_attr_e( $item_id ); ?>" class="accordion-section control-section
								<?php echo $item_type === 'panel' ? 'control-panel' : 'control-section-default'; ?>
								aria-owns="sub-accordion-section-<?php esc_attr_e( $item_id ); ?>">
								<h3 class="accordion-section-title" tabindex="0">
									<?php esc_html_e( $item_title ); ?>
									<span class="screen-reader-text"><?php esc_html_e( 'Press return or enter to open this section' ); ?></span>
								</h3>

								<?php
								if ( 'panel' === $item_type ) {

									// For a panel, list child sections.
									foreach ( $sections as $section ) {
										if ( $section->panel === $item_id ) {
											?>

											<ul id="sub-accordion-panel-<?php esc_attr_e( $section->panel ); ?>" class="customize-pane-child accordion-sub-container control-panel-content accordion-section control-panel-<?php esc_attr_e( $section->panel ); ?>">
												<li class="customize-section-description-container section-meta no-drag">
													<div class="customize-section-title">
														<button class="customize-section-back" tabindex="0">
															<span class="screen-reader-text">Back</span>
														</button>
														<h3>
															<span class="customize-action">Customizing</span>Site Identity
														</h3>
														<div class="customize-control-notifications-container" style="display: none;">
															<ul></ul>
														</div>
													</div>
													<span class="customize-section-label"><?php esc_html( $section->title ); ?></span>

													<?php
													// Controls inside this section.
													$sid = $section->id;
													if ( isset( $cp_controls_by_section[ $sid ] ) ) {
														foreach ( $cp_controls_by_section[ $sid ] as $control_data ) {
															$field_name  = $control_data['setting_id'] ?: $control_data['id'];
															$field_value = $control_data['value'];
															$type		= $control_data['type'];

															echo '<div class="customize-control customize-control-' . esc_attr( $type ) . '">';
															echo '<label class="customize-control-label" for="' . esc_attr( $field_name ) . '">';
															echo esc_html( $control_data['label'] ?: $control_data['id'] );
															echo '</label>';

															// Very simple type-to-input mapping.
															if ( in_array( $type, [ 'text', 'url', 'email', 'number' ], true ) ) {
																echo '<input type="text" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="regular-text" />';
															} elseif ( 'checkbox' === $type ) {
																$checked = $field_value ? ' checked="checked"' : '';
																echo '<input type="checkbox" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="1"' . $checked . ' />';
															} elseif ( 'textarea' === $type ) {
																echo '<textarea id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" rows="4" class="large-text">' . esc_textarea( (string) $field_value ) . '</textarea>';
															} elseif ( 'color' === $type ) {
																echo '<input type="color" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( (string) $field_value ) . '" />';
															} else {
																// Fallback generic input.
																//echo '<input type="text" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( (string) $field_value ) . '" class="regular-text" />';
															}

															if ( ! empty( $control_data['description'] ) ) {
																echo '<p class="description">' . esc_html( $control_data['description'] ) . '</p>';
															}

															echo '</div>';
														}
													}
													?>
												</li>
											</ul>

											<?php
										} else {
											// Top-level section: list its own controls.
											$sid = $item_id;
											echo '<div class="customize-section">';
											echo '<span class="customize-section-label">' . esc_html( $item_title ) . '</span>';

											if ( isset( $cp_controls_by_section[ $sid ] ) ) {
												foreach ( $cp_controls_by_section[ $sid ] as $control_data ) {
													$field_name  = $control_data['setting_id'] ?: $control_data['id'];
													$field_value = $control_data['value'];
													$type		= $control_data['type'];

													echo '<div class="customize-control customize-control-' . esc_attr( $type ) . '">';
													echo '<label class="customize-control-label" for="' . esc_attr( $field_name ) . '">';
													echo esc_html( $control_data['label'] ?: $control_data['id'] );
													echo '</label>';

													// Very simple type-to-input mapping.
													if ( in_array( $type, [ 'text', 'url', 'email', 'number' ], true ) ) {
														echo '<input type="text" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_value ) . '" class="regular-text" />';
													} elseif ( 'checkbox' === $type ) {
														$checked = $field_value ? ' checked="checked"' : '';
														echo '<input type="checkbox" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="1"' . $checked . ' />';
													} elseif ( 'textarea' === $type ) {
														echo '<textarea id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" rows="4" class="large-text">' . esc_textarea( (string) $field_value ) . '</textarea>';
													} elseif ( 'color' === $type ) {
														echo '<input type="color" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( (string) $field_value ) . '" />';
													} else {
														// Fallback generic input.
														echo '<input type="text" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( (string) $field_value ) . '" class="regular-text" />';
													}

													if ( ! empty( $control_data['description'] ) ) {
														echo '<p class="description">' . esc_html( $control_data['description'] ) . '</p>';
													}

													echo '</div>';
												}
											}
											echo '</div>';
										}
									}
								}
								?>

							</li>

						<?php
						}
						?>

					</ul>
				</div>
			</div>
		</div>

		<div id="customize-footer-actions" class="wp-full-overlay-footer">
			<button type="button" class="collapse-sidebar button" aria-expanded="true" aria-label="Hide Controls">
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
		<iframe title="<?php esc_attr_e( 'Site Preview' ); ?>" name="customize-preview-0" onmousewheel="" src="<?php echo esc_url( $preview_url ); ?>"></iframe>
	</div>

</div><!-- .wp-full-overlay expanded preview-desktop -->

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
