<?php
/**
 * ClassicPress polyfills for blocks.
 *
 * @package ClassicPress
 * @since CP-2.0.0
 */

class WP_Compat {

	public static $blocks_compatibility_level = null;

	public function __construct() {

		if ( null === self::$blocks_compatibility_level ) {
			self::$blocks_compatibility_level = (int) get_option( 'blocks_compatibility_level', 1 );
		}

		add_action( 'update_option_blocks_compatibility_level', array( $this, 'purge_options' ), 10, 2 );

		$this->define_polyfills();

		if ( 1 === self::$blocks_compatibility_level ) {
			return;
		}

		// Define hooks to be used to warn users.
		add_action( 'after_plugin_row', array( $this, 'using_block_function_row' ), 10, 2 );
		add_action( 'upgrader_process_complete', array( $this, 'update_extensions_using_blocks' ), 10, 2 );
		add_action( 'delete_plugin', array( $this, 'delete_plugins_using_blocks' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'using_block_function_theme' ), 10, 0 );
		add_action( 'after_switch_theme', array( $this, 'delete_themes_using_blocks' ), 10, 0 );

		// ClassicPress Site Health block compatibility debug.
		require_once ABSPATH . WPINC . '/classicpress/class-cp-debug-compat.php';
	}

	public function purge_options( $old_value, $value ) {
		if ( 2 === $value ) {
			return;
		}
		delete_option( 'plugins_using_blocks' );
		delete_option( 'theme_using_blocks' );
	}

	/**
	 * Action hooked to after_plugin_row to display plugins that may not work properly.
	 *
	 * @param string $plugin_file
	 * @param array  $plugin_data
	 * @return void
	 */
	public function using_block_function_row( $plugin_file, $plugin_data ) {
		$plugins_using_blocks = get_option( 'plugins_using_blocks', array() );
		if ( ! array_key_exists( dirname( $plugin_file ), $plugins_using_blocks ) && ! array_key_exists( $plugin_file, $plugins_using_blocks ) ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$active        = is_plugin_active( $plugin_file ) ? 'active' : '';
		$shadow        = isset( $plugin_data['new_version'] ) ? 'style="box-shadow: none;"' : '';
		?>
		<tr class="plugin-update-tr <?php echo $active; ?>">
			<td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="plugin-update colspanchange" <?php echo $shadow; ?>>
				<div class="notice inline notice-warning">
					<p>
						<?php
						// Translators: %1$s is the plugin name.
						printf( esc_html__( '%1$s uses block-related functions and may have issues.' ), $plugin_data['Name'] );
						?>
						<a href="https://docs.classicpress.net/user-guides/using-classicpress/settings-general-screen/#blocks-compatibility"><?php _e( 'Learn more' ); ?></a> |
						<a href="https://forums.classicpress.net/new-topic?category=plugins/plugin-support&tags=blocks-compatibility&title=<?php echo urlencode( $plugin_data['Name'] ); ?>+blocks+compatibility"><?php _e( 'Report an issue &rsaquo;' ); ?></a>
					</p>
				</div>
			</td>
		</tr>
		<script>
			document.querySelector('tr[data-plugin="<?php echo $plugin_file; ?>"').classList.add('update');
		</script>
		<?php
	}

	/**
	 * Action hooked to upgrader_process_complete to clean up the list of plugins
	 * that may not work properly.
	 *
	 * @param WP_Upgrader $upgrader
	 * @param array       $options
	 * @return void
	 */
	public function update_extensions_using_blocks( $upgrader, $options ) {
		if ( 'update' !== $options['action'] ) {
			return;
		}

		if ( 'theme' === $options['type'] ) {
			update_option( 'theme_using_blocks', false );
			return;
		}

		if ( 'plugin' !== $options['type'] ) {
			return;
		}

		$plugins_using_blocks = get_option( 'plugins_using_blocks', array() );
		foreach ( $options['plugins'] as $plugin ) {
			if ( array_key_exists( $plugin, $plugins_using_blocks ) ) {
				unset( $plugins_using_blocks[ $plugin ] );
			}
		}
		update_option( 'plugins_using_blocks', $plugins_using_blocks );
	}

	/**
	 * Action hooked to delete_plugin to remove the plugin
	 * that may not work properly.
	 *
	 * @param string       $options
	 * @return void
	 */
	public function delete_plugins_using_blocks( $plugin_file ) {
		$plugins_using_blocks = get_option( 'plugins_using_blocks', array() );
		if ( array_key_exists( $plugin_file, $plugins_using_blocks ) ) {
			unset( $plugins_using_blocks[ $plugin_file ] );
		}
		update_option( 'plugins_using_blocks', $plugins_using_blocks );
	}

	/**
	 * Action hooked to admin_notices to display an admin notice
	 * on themes page.
	 *
	 * @return void
	 */
	public function using_block_function_theme() {
		global $pagenow;
		if ( 'themes.php' !== $pagenow ) {
			return;
		}

		$theme_using_blocks = get_option( 'theme_using_blocks', '0' );
		if ( ! in_array( $theme_using_blocks, array( '1', '2' ), true ) ) {
			return;
		}

		if ( '1' === $theme_using_blocks ) {
			// Translators: %1$s is the theme name.
			$message  = sprintf( esc_html__( '%1$s uses block-related functions and may have issues.' ), wp_get_theme()->get( 'Name' ) );
			$message .= ' <a href="https://docs.classicpress.net/user-guides/using-classicpress/settings-general-screen/#blocks-compatibility">' . __( 'Learn more' ) . '</a> |';
			$message .= ' <a href="https://forums.classicpress.net/new-topic?category=themes/theme-support&tags=blocks-compatibility&title=' . urlencode( wp_get_theme()->get( 'Name' ) ) . '+blocks+compatibility">' . __( 'Report an issue &rsaquo;' ) . '</a>';
		} else {
			// Translators: %1$s is the theme name, %1$s is the parent theme name.
			$message = sprintf( esc_html__( '%1$s parent theme (%2$s) uses block-related functions and may have issues.' ), wp_get_theme()->get( 'Name' ), wp_get_theme()->parent()->get( 'Name' ) );
			$message .= ' <a href="https://docs.classicpress.net/user-guides/using-classicpress/settings-general-screen/#blocks-compatibility">' . __( 'Learn more' ) . '</a> |';
			$message .= ' <a href="https://forums.classicpress.net/new-topic?category=themes/theme-support&tags=blocks-compatibility&title=' . urlencode( wp_get_theme()->parent()->get( 'Name' ) ) . '+blocks+compatibility">' . __( 'Report an issue &rsaquo;' ) . '</a>';
		}

		?>
		<div class="notice notice-warning">
			<p>
			<?php
				echo $message;
			?>
		</div>
		<?php
	}

	/**
	 * Get plugin folder name from a path.
	 *
	 * @param string $path
	 * @return string
	 */
	private static function plugin_folder( $path ) {
		return preg_replace( '~^' . preg_quote( WP_PLUGIN_DIR ) . preg_quote( DIRECTORY_SEPARATOR ) . '([^' . preg_quote( DIRECTORY_SEPARATOR ) . ']*).*~', '$1', $path );
	}

	/**
	 * Action hooked to after_switch_theme to remove the theme
	 * that may not work properly.
	 *
	 * @return void
	 */
	public function delete_themes_using_blocks() {
		update_option( 'theme_using_blocks', false );
	}


	/**
	 * This function have to be called from a polyfill
	 * to map themes and plugins calling those functions.
	 *
	 * Make sure that class WP_Compat exists before calling the function
	 * because it's not defined if Blocks Compatibility option is set to "off".
	 *
	 * @return void
	 */
	public static function using_block_function() {
		if ( 2 !== self::$blocks_compatibility_level ) {
			return;
		}

		$trace = debug_backtrace();

		/*
		 * Fires after WP_Compat::using_block_function() is called.
		 *
		 * @since: CP-2.0.0
		 *
		 * @param array $trace debug_backtrace() output
		 */
		do_action( 'using_block_function', $trace );

		if ( str_starts_with( $trace[1]['file'], realpath( get_stylesheet_directory() ) ) ) {
			// Current theme is calling the function
			update_option( 'theme_using_blocks', '1' );
		} elseif ( str_starts_with( $trace[1]['file'], realpath( get_template_directory() ) ) ) {
			// Parent theme is calling the function
			update_option( 'theme_using_blocks', '2' );
		} else {
			// A plugin is calling the function
			$traces = array_column( $trace, 'file' );
			$traces = array_map(
				function ( $path ) {
					return self::plugin_folder( $path );
				},
				$traces
			);
			$active = wp_get_active_and_valid_plugins();
			$active = array_map(
				function ( $path ) {
					return self::plugin_folder( $path );
				},
				$active
			);
			$plugins = array_intersect( $traces, $active );
			$plugin = array_pop( $plugins );
			if ( null === $plugin ) {
				// Nothing found? Bail.
				return;
			}
			$plugins_using_blocks = get_option( 'plugins_using_blocks', array() );
			if ( ! array_key_exists( plugin_basename( $plugin ), $plugins_using_blocks ) ) {
				$plugins_using_blocks[ plugin_basename( $plugin ) ] = true;
				update_option( 'plugins_using_blocks', $plugins_using_blocks );
			}
		}
	}

	/**
	 * Polyfills that can not be defined elsewhere go here.
	 * that may not work properly.
	 *
	 * @return void
	 */
	private function define_polyfills() {
		// Polyfills that must not have to be defined elsewhere go there.
		if ( ! function_exists( 'register_block_type' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function register_block_type( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'unregister_block_type' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function unregister_block_type( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function register_block_type_from_metadata( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'has_block' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function has_block( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'has_blocks' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function has_blocks( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'register_block_pattern' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function register_block_pattern( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'unregister_block_pattern' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function unregister_block_pattern( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function register_block_pattern_category( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'unregister_block_pattern_category' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function unregister_block_pattern_category( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		if ( ! function_exists( 'wp_is_block_theme' ) ) {
			/**
			 * Polyfill for block functions.
			 *
			 * @since CP-2.0.0
			 *
			 * @return bool False.
			 */
			function wp_is_block_theme( ...$args ) {
				WP_Compat::using_block_function();
				return false;
			}
		}

		// Load WP_Block_Type class file as polyfill.
		require_once ABSPATH . WPINC . '/classicpress/class-wp-block-type.php';
		require_once ABSPATH . WPINC . '/classicpress/class-wp-block-template.php';
	}
}

$wp_compat = new WP_Compat();
