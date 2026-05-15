<?php
/**
 * Customize API: WP_Customize_Theme_Control class
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since 4.4.0
 */

/**
 * Customize Theme Control class.
 *
 * @since 4.2.0
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Theme_Control extends WP_Customize_Control {

	/**
	 * Customize control type.
	 *
	 * @since 4.2.0
	 * @var string
	 */
	public $type = 'theme';

	/**
	 * Theme object.
	 *
	 * @since 4.2.0
	 * @var WP_Theme
	 */
	public $theme;

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 4.2.0
	 *
	 * @see WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();
		$this->json['theme'] = $this->theme;
	}

	/**
	 * Render the control content from PHP.
	 *
	 * @since CP-2.8.0
	 */
	public function render_content() {
		$installed_themes  = wp_prepare_themes_for_js();
		$active_stylesheet = cp_get_current_active_stylesheet();

		// Display the active theme first
		foreach ( $installed_themes as $theme ) {
			if ( $theme['id'] !== $active_stylesheet ) {
				continue;
			}

			/* translators: %s: Theme name. */
			$details_label   = sprintf( __( 'Details for theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$customize_label = sprintf( __( 'Customize theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$preview_label   = sprintf( __( 'Live preview theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$install_label   = sprintf( __( 'Install and preview theme: %s' ), $theme['name'] );
			?>
		
			<li id="customize-control-installed_theme_<?php echo esc_attr( $theme['id'] ); ?>"
				class="theme active"
				data-id="<?php echo esc_attr( $theme['id'] ); ?>"
				data-activate-nonce="<?php echo esc_attr( $theme['actions']['activate'] ); ?>"
				data-customize="<?php echo esc_attr( $theme['actions']['customize'] ); ?>"
				data-delete-nonce="<?php echo esc_attr( $theme['actions']['delete'] ); ?>"
				data-description="<?php echo esc_attr( $theme['description'] ); ?>"
				data-author="<?php echo esc_attr( $theme['author'] ); ?>"
				data-tags="<?php echo esc_attr( $theme['tags'] ); ?>"
				data-num-ratings=""
				data-version="<?php echo esc_attr( $theme['version'] ); ?>"
				data-compatible-wp="<?php echo esc_attr( $theme['compatibleWP'] ); ?>"
				data-compatible-php="<?php echo esc_attr( $theme['compatiblePHP'] ); ?>"
				data-has-update="<?php echo esc_attr( $theme['hasUpdate'] ); ?>"
				data-update="<?php echo esc_attr( $theme['update'] ); ?>"
				data-update-response="<?php echo esc_attr( $theme['updateResponse']['compatibleWP'] . '-' . $theme['updateResponse']['compatiblePHP'] ); ?>"
				aria-describedby="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-action"
			>
				<div class="customize-control-notifications-container" style="display: none;">
					<ul></ul>
				</div>

				<?php
				if ( $theme['screenshot'] && $theme['screenshot'][0] ) {
					?>
					<div class="theme-screenshot">
						<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_attr( $theme['screenshot'][0] . '?ver=' . $theme['version'] ); ?>">
					</div>
					<?php
				} else {
					?>
					<div class="theme-screenshot blank"></div>
					<?php
				}
				?>

				<span class="more-details"
					id="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-action"
					aria-label="<?php echo esc_attr( $details_label ); ?>"
				>
					<?php esc_html_e( 'Theme Details' ); ?>
				</span>
				<div class="theme-author">

					<?php
					/* translators: Theme author name. */
					printf( _x( 'By %s', 'theme author' ), esc_html__( $theme['author'] ) );
					?>

				</div>

				<?php $this->notify_updates( $theme ); ?>

				<div class="theme-id-container">
					<h3 class="theme-name" id="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-name">
						<span><?php _ex( 'Previewing:', 'theme' ); ?></span> <?php echo esc_html( $theme['name'] ); ?>
					</h3>
					<div class="theme-actions">
						<a href="<?php echo esc_url( add_query_arg( 'theme', $theme['id'], admin_url( 'customize.php' ) ) ); ?>"
							class="button button-primary customize-theme"
							aria-label="<?php echo esc_attr( $customize_label ); ?>"
						>
							<?php esc_html_e( 'Customize' ); ?>
						</a>
					</div>
				</div>

				<?php
				wp_admin_notice(
					_x( 'Installed', 'theme' ),
					array(
						'type'               => 'success',
						'additional_classes' => array( 'notice-alt' ),
					)
				);
				?>
			</li>

			<?php
			break; // No need to cycle through other themes
		}

		// Now display the rest
		foreach ( $installed_themes as $theme ) {
			if ( $theme['id'] === $active_stylesheet ) {
				continue;
			}

			/* translators: %s: Theme name. */
			$details_label   = sprintf( __( 'Details for theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$customize_label = sprintf( __( 'Customize theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$preview_label   = sprintf( __( 'Live preview theme: %s' ), $theme['name'] );
			/* translators: %s: Theme name. */
			$install_label   = sprintf( __( 'Install and preview theme: %s' ), $theme['name'] );
			?>

			<li id="customize-control-installed_theme_<?php echo esc_attr( $theme['id'] ); ?>"
				class="theme"
				data-id="<?php echo esc_attr( $theme['id'] ); ?>"
				data-activate-nonce="<?php echo esc_attr( $theme['actions']['activate'] ); ?>" 
				data-customize="<?php echo esc_attr( $theme['actions']['customize'] ); ?>"
				data-delete-nonce="<?php echo isset( $theme['actions']['delete'] ) ? esc_attr( $theme['actions']['delete'] ) : ''; ?>"
				data-description="<?php echo esc_attr( $theme['description'] ); ?>"
				data-author="<?php echo esc_attr( $theme['author'] ); ?>"
				data-tags="<?php echo esc_attr( $theme['tags'] ); ?>"
				data-num-ratings=""
				data-version="<?php echo esc_attr( $theme['version'] ); ?>"
				data-compatible-wp="<?php echo esc_attr( $theme['compatibleWP'] ); ?>"
				data-compatible-php="<?php echo esc_attr( $theme['compatiblePHP'] ); ?>"
				data-has-update="<?php echo esc_attr( $theme['hasUpdate'] ); ?>"
				data-update="<?php echo esc_attr( $theme['update'] ); ?>"
				data-update-response="<?php echo esc_attr( $theme['updateResponse']['compatibleWP'] . '-' . $theme['updateResponse']['compatiblePHP'] ); ?>"
				aria-describedby="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-action"
			>
				<div class="customize-control-notifications-container" style="display: none;">
					<ul></ul>
				</div>

				<?php
				if ( $theme['screenshot'] && $theme['screenshot'][0] ) {
					?>
					<div class="theme-screenshot">
						<img src="<?php echo esc_url( $theme['screenshot'][0] ); ?>" alt="" data-src="<?php echo esc_attr( $theme['screenshot'][0] . '?ver=' . $theme['version'] ); ?>">
					</div>
					<?php
				} else {
					?>
					<div class="theme-screenshot blank"></div>
					<?php
				}
				?>

				<span id="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-action"
					class="more-details"
					aria-label="<?php echo esc_attr( $details_label ); ?>"
				>
					<?php esc_html_e( 'Theme Details' ); ?>
				</span>
				<div class="theme-author">
					<?php
					/* translators: Theme author name. */
					printf( _x( 'By %s', 'theme author' ), esc_html__( $theme['author'] ) );
					?>
				</div>

				<?php
				$this->notify_updates( $theme );

				if ( ! $theme['actions']['customize'] || ! $theme['updateResponse']['compatibleCP'] ) {
					?>

					<div class="theme-id-container">
						<h3 class="theme-name" id="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-name">
							<?php echo esc_html( $theme['name'] ); ?>
						</h3>
						<div class="theme-actions">

							<button type="button"
								class="button button-primary preview-theme"
								aria-label="<?php echo esc_attr( $preview_label ); ?>"
								data-slug="<?php echo esc_attr( $theme['id'] ); ?>"
							>
								<?php esc_html_e( 'Incompatible Theme' ); ?>
							</button>

						</div>
					</div>

					<?php
					$customizer_not_supported_message = __( 'This theme doesn\'t support ClassicPress.' );
					wp_admin_notice(
						$customizer_not_supported_message,
						array(
							'type'               => 'error',
							'additional_classes' => array( 'notice-alt' ),
						)
					);
				} else {
					?>

					<div class="theme-id-container">
						<h3 class="theme-name" id="installed_themes-<?php echo esc_attr( $theme['id'] ); ?>-name">
							<?php echo esc_html( $theme['name'] ); ?>
						</h3>
						<div class="theme-actions">

						<?php
						if ( $theme['updateResponse']['compatibleWP'] && $theme['updateResponse']['compatiblePHP'] && $theme['updateResponse']['compatibleCP'] ) {
							if ( $theme['actions']['customize'] ) {
								/* translators: %s: Theme name. */
								$aria_label = sprintf( _x( 'Live preview theme %s', 'theme' ), esc_html__( $theme['name'] ) );
								?>
								<a href="<?php echo esc_url( add_query_arg( 'theme', $theme['id'], admin_url( 'customize.php' ) ) ); ?>"
									class="button button-primary preview-theme"
									aria-label="<?php echo esc_attr( $aria_label ); ?>"
								>
									<?php esc_html_e( 'Live Preview' ); ?>
								</a>
								<?php
							}
						} else {
							?>

							<button type="button"
								class="button button-primary disabled"
								aria-label="<?php echo esc_attr( $preview_label ); ?>"
							>
								<?php esc_html_e( 'Live Preview' ); ?>
							</button>
							<?php
						}
						?>

					</div>

					<?php
					wp_admin_notice(
						_x( 'Installed', 'theme' ),
						array(
							'type'               => 'success',
							'additional_classes' => array( 'notice-alt' ),
						)
					);
				}
				?>

			</li>

			<?php
		}
	}

	/**
	 * Redundant JS template.
	 *
	 * @since CP-2.8.0
	 */
	public function content_template() {}

	/**
	 * Display update messages and explanations.
	 *
	 * @since CP-2.8.0
	 */
	protected function notify_updates( $theme ) {
		$cp_has_update = classicpress_has_update();
		ob_start();

		if ( $theme['hasUpdate'] ) {
			if ( $theme['updateResponse']['compatibleWP'] && $theme['updateResponse']['compatiblePHP'] ) {
				?>

				<div class="update-message notice inline notice-warning notice-alt" data-slug="<?php echo esc_attr( $theme['id'] ); ?>">
					<p>

						<?php
						if ( is_multisite() ) {
							_e( 'New version available.' );
						} else {
							printf(
								/* translators: %s: "Update now" button. */
								__( 'New version available. %s' ),
								'<button class="button-link update-theme" type="button">' . __( 'Update now' ) . '</button>'
							);
						}
						?>

					</p>
				</div>

				<?php
			} else {
				?>

				<div class="update-message notice inline notice-error notice-alt" data-slug="<?php echo esc_attr( $theme['id'] ); ?>">
					<p>

						<?php
						if ( ! $theme['updateResponse']['compatibleWP'] && ! $theme['updateResponse']['compatiblePHP'] ) {
							printf(
								/* translators: %s: Theme name. */
								__( 'There is a new version of %s available, but it does not work with your versions of ClassicPress and PHP.' ),
								esc_html__( $theme['name'] )
							);
							if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
								if ( $cp_has_update ) {
									printf(
										/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
										' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
										self_admin_url( 'update-core.php' ),
										esc_url( wp_get_update_php_url() )
									);
								} else {
									printf(
										/* translators: %s: URL to Update PHP page. */
										' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
										esc_url( wp_get_update_php_url() )
									);
								}
								wp_update_php_annotation( '</p><p><em>', '</em>' );
							} elseif ( current_user_can( 'update_core' ) && $cp_has_update ) {
								printf(
									/* translators: %s: URL to WordPress Updates screen. */
									' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
									self_admin_url( 'update-core.php' )
								);
							} elseif ( current_user_can( 'update_php' ) ) {
								printf(
									/* translators: %s: URL to Update PHP page. */
									' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
									esc_url( wp_get_update_php_url() )
								);
								wp_update_php_annotation( '</p><p><em>', '</em>' );
							}
						} else if ( ! $theme['updateResponse']['compatibleWP'] ) {
							printf(
								/* translators: %s: Theme name. */
								__( 'There is a new version of %s available, but it does not work with your version of ClassicPress.' ),
								esc_html__( $theme['name'] )
							);
							if ( current_user_can( 'update_core' ) && $cp_has_update ) {
								printf(
									/* translators: %s: URL to WordPress Updates screen. */
									' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
									self_admin_url( 'update-core.php' )
								);
							}
						} else if ( ! $theme['updateResponse']['compatiblePHP'] ) {
							printf(
								/* translators: %s: Theme name. */
								__( 'There is a new version of %s available, but it does not work with your version of PHP.' ),
								esc_html__( $theme['name'] )
							);
							if ( current_user_can( 'update_php' ) ) {
								printf(
									/* translators: %s: URL to Update PHP page. */
									' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
									esc_url( wp_get_update_php_url() )
								);
								wp_update_php_annotation( '</p><p><em>', '</em>' );
							}
						}
						?>

					</p>
				</div>

				<?php
			}
		}

		if ( ! $theme['updateResponse']['compatibleWP'] || ! $theme['updateResponse']['compatiblePHP'] || ! $theme['updateResponse']['compatibleCP'] ) {
			?>

			<div class="notice notice-error notice-alt">
				<p>

					<?php
					if ( ! $theme['updateResponse']['compatibleWP'] && ! $theme['updateResponse']['compatiblePHP'] ) {
						_e( 'This theme does not work with your versions of ClassicPress and PHP.' );
						if ( current_user_can( 'update_core' ) && current_user_can( 'update_php' ) ) {
							if ( $cp_has_update ) {
								printf(
									/* translators: 1: URL to WordPress Updates screen, 2: URL to Update PHP page. */
									' ' . __( '<a href="%1$s">Please update ClassicPress</a>, and then <a href="%2$s">learn more about updating PHP</a>.' ),
									self_admin_url( 'update-core.php' ),
									esc_url( wp_get_update_php_url() )
								);
							} else {
								printf(
									/* translators: %s: URL to Update PHP page. */
									' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
									esc_url( wp_get_update_php_url() )
								);
							}
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						} elseif ( current_user_can( 'update_core' ) && $cp_has_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						} elseif ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
					} else if ( ! $theme['updateResponse']['compatibleCP'] ) {
						_e( "FSE themes don't work with ClassicPress." );
					} else if ( ! $theme['updateResponse']['compatibleWP'] ) {
						_e( 'This theme does not work with your version of ClassicPress.' );
						if ( current_user_can( 'update_core' ) && $cp_has_update ) {
							printf(
								/* translators: %s: URL to WordPress Updates screen. */
								' ' . __( '<a href="%s">Please update ClassicPress</a>.' ),
								self_admin_url( 'update-core.php' )
							);
						}
					} else if ( ! $theme['updateResponse']['compatiblePHP'] ) {
						_e( 'This theme does not work with your version of PHP.' );
						if ( current_user_can( 'update_php' ) ) {
							printf(
								/* translators: %s: URL to Update PHP page. */
								' ' . __( '<a href="%s">Learn more about updating PHP</a>.' ),
								esc_url( wp_get_update_php_url() )
							);
							wp_update_php_annotation( '</p><p><em>', '</em>' );
						}
					}
					?>

				</p>
			</div>

			<?php
		}
		echo ob_get_clean();
	}
}
