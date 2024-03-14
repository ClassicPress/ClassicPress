<?php
/**
 * ClassicPress Site Health block compatibility debug class
 *
 * @package ClassicPress
 * @subpackage admin
 * @since CP-2.0.0
 */

class CP_Debug_Compat {

	public function __construct() {
		add_action( 'update_option_blocks_compatibility_level', array( $this, 'clean_options' ), 10, 2 );
		add_action( 'using_block_function', array( $this, 'log' ) );
		add_action( 'init', array( $this, 'add_to_site_health' ) );
	}

	public function add_to_site_health() {
		add_filter( 'site_status_tests', array( $this, 'add_site_status_tests' ) );
		add_filter( 'debug_information', array( $this, 'add_debug_information' ) );
	}

	public function add_debug_information( $args ) {
		$options = $this->get_options();

		$item_types = array(
			'plugins'       => 'Plugin',
			'themes'        => 'Theme',
			'parent_themes' => 'Parent Theme',
		);

		$fields = array();
		foreach ( $item_types as $key => $description ) {
			foreach ( $options['data'][ $key ] as $item => $value ) {
				$functions = wp_kses( $this->implode( $value ) . '.', array() );
				$fields[ $item ] = array(
					'label' => $description . ': ' . $item,
					'value' => $functions,
					'debug' => 'Plugin uses ' . $functions,
				);
			}
		}

		$args['dc-blocks'] = array(
			'label'       => esc_html__( 'Block Compatibility' ),
			'description' => esc_html__( 'Plugins and themes using block functions.' ),
			'show_count'  => true,
			'fields'      => $fields,
		);

		return $args;
	}

	public function add_site_status_tests( $tests ) {
		$tests['direct']['dc_plugins_blocks'] = array(
			'label' => esc_html__( 'Plugins using block functions' ),
			'test'  => array( $this, 'test_plugin' ),
		);
		$tests['direct']['dc_themes_blocks'] = array(
			'label' => esc_html__( 'Themes using block functions' ),
			'test'  => array( $this, 'test_theme' ),
		);
		return $tests;
	}

	public function test_plugin() {
		$options = $this->get_options();
		$result = array(
			'label'       => esc_html__( 'Plugins using block functions' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Compatibility',
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'No plugins are using block functions.' ),
			),
			'actions'     => '',
			'test'        => 'dc_plugins_blocks',
		);
		if ( $options['data']['plugins'] === array() ) {
			return $result;
		}
		$action  = esc_html__( 'Plugins on this list may have issues.' );
		$action .= ' <a href="https://docs.classicpress.net/user-guides/using-classicpress/site-health-screen/#block-compatibility">' . esc_html__( 'Learn more.' ) . '</a>';
		$result = array(
			'label'       => esc_html__( 'Plugins using block functions' ),
			'status'      => 'recommended',
			'badge'       => array(
				'label' => 'Compatibility',
				'color' => 'orange',
			),
			'description' => $this->list_items( $options, 'plugins' ),
			'actions'     => $action,
			'test'        => 'dc_plugins_blocks',
		);
		return $result;
	}

	public function test_theme() {
		$options = $this->get_options();
		$result = array(
			'label'       => esc_html__( 'Themes using block functions' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => 'Compatibility',
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				esc_html__( 'No themes are using block functions.' ),
			),
			'actions'     => '',
			'test'        => 'dc_themes_blocks',
		);
		$themes = array_merge( $options['data']['themes'], $options['data']['parent_themes'] );
		if ( $themes === array() ) {
			return $result;
		}
		$action  = esc_html__( 'Themes on this list may have issues.' );
		$action .= ' <a href="https://docs.classicpress.net/user-guides/using-classicpress/site-health-screen/#block-compatibility">' . esc_html__( 'Learn more.' ) . '</a>';
		$result = array(
			'label'       => esc_html__( 'Themes using block functions' ),
			'status'      => 'recommended',
			'badge'       => array(
				'label' => 'Compatibility',
				'color' => 'orange',
			),
			'description' => $this->list_items( $options, 'themes' ) . $this->list_items( $options, 'parent_themes' ),
			'actions'     => $action,
			'test'        => 'dc_themes_blocks',
		);
		return $result;
	}

	private function list_items( $options, $type ) {
		$response = '';
		foreach ( $options['data'][ $type ] as $who => $what ) {
			$response .= sprintf(
				wp_kses(
					/* translators: %1$s is the plugin/theme name, %b$s is a comma separated list of functions */
					'<p><b>%1$s</b> is using: %2$s.</p>',
					array(
						'b' => array(),
						'p' => array(),
					)
				),
				esc_html( $who ),
				wp_kses(
					$this->implode( $what ),
					array(
						'code' => array(),
					)
				)
			);
		}
		return $response;
	}

	private function implode( $list ) {
		$result = '';
		foreach ( $list as $element ) {
			$result .= '<code>' . $element . '</code>, ';
		}
		return rtrim( $result, ', ' );
	}

	private function get_options() {
		$default = array(
			'db_version' => '2',
			'data'       => array(
				'themes'        => array(),
				'parent_themes' => array(),
				'plugins'       => array(),
			),
		);
		$options = get_option( 'cp_dc_options', $default );
		return $options;
	}

	private static function plugin_folder( $path ) {
		return preg_replace( '~^' . preg_quote( WP_PLUGIN_DIR ) . preg_quote( DIRECTORY_SEPARATOR ) . '([^' . preg_quote( DIRECTORY_SEPARATOR ) . ']*).*~', '$1', $path );
	}


	public function log( $trace ) {
		$options = $this->get_options();
		$func    = $trace[1]['function'];

		if ( str_starts_with( $trace[1]['file'], realpath( get_stylesheet_directory() ) ) ) {
			// Theme
			if ( ! isset( $options['data']['themes'][ wp_get_theme()->get( 'Name' ) ] ) || ! in_array( $func, $options['data']['themes'][ wp_get_theme()->get( 'Name' ) ] ) ) {
				$options['data']['themes'][ wp_get_theme()->get( 'Name' ) ][] = $func;
			}
		} elseif ( str_starts_with( $trace[1]['file'], realpath( get_template_directory() ) ) ) {
			// Child theme
			if ( ! isset( $options['data']['parent_themes'][ wp_get_theme()->parent()->get( 'Name' ) ] ) || ! in_array( $func, $options['data']['parent_themes'][ wp_get_theme()->parent()->get( 'Name' ) ] ) ) {
				$options['data']['parent_themes'][ wp_get_theme()->parent()->get( 'Name' ) ][] = $func;
			}
		} else {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
			$files  = array_column( $trace, 'file' );
			$files = array_map(
				function ( $path ) {
					return self::plugin_folder( $path );
				},
				$files
			);
			$active_paths = wp_get_active_and_valid_plugins();
			$active = array_map(
				function ( $path ) {
					return self::plugin_folder( $path );
				},
				$active_paths
			);
			$plugins = array_intersect( $files, $active );
			$plugin  = array_pop( $plugins );
			$active_paths = array_filter(
				$active_paths,
				function ( $path ) use ( $plugin ) {
					return str_starts_with( $path, WP_PLUGIN_DIR . '/' . $plugin );
				}
			);
			$plugin_path = array_pop( $active_paths );
			$plugin_data = get_plugin_data( $plugin_path );
			$plugin_name = $plugin_data['Name'];
			if ( ! isset( $options['data']['plugins'][ $plugin_name ] ) || ! in_array( $func, $options['data']['plugins'][ $plugin_name ] ) ) {
				$options['data']['plugins'][ $plugin_name ][] = $func;
			}
		}

		update_option( 'cp_dc_options', $options );
	}

	public static function clean_options() {
		delete_option( 'cp_dc_options' );
	}
}

new CP_Debug_Compat();
