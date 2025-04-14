<?php
/*
 * Plugin Name:       ClassicPress Pepper for Passwords
 * Plugin URI:        https://directory.classicpress.net/plugins/classicpress-pepper-for-passwords/
 * Description:       For enhanced security add a `pepper` to password hashing.
 * Version:           2.0.0
 * Requires at least: 4.9.15
 * Requires PHP:      7.4
 * Requires CP:       2.2
 * Author:            The ClassicPress Team
 * Author URI:        https://www.classicpress.net/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cp-pepper
 * Domain Path:       /languages
 * Network:           true
*/

namespace ClassicPress\PepperPassword;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * PepperPassword class
 */
class PepperPassword {

	/**
	 * Screen identifier
	 *
	 * @var string
	 */
	private $screen = '';

	/**
	 * Pepper file path
	 *
	 * @var string
	 */
	private $pepper_file = '';

	/**
	 * Plugin slug
	 */
	const SLUG = 'cp-pepper';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->pepper_file = WP_CONTENT_DIR . '/pepper.php';
		$this->init();
	}

	/**
	 * Plugin initialization.
	 *
	 * Register actions and filters to hook the plugin into the system.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'create_settings_menu' ), 100 );
		add_action( 'network_admin_menu', array( $this, 'create_settings_menu' ), 100 );
		add_filter( 'plugin_action_links', array( $this, 'create_settings_link' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'create_settings_link' ), 10, 2 );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_filter( 'cp_pepper_password', array( $this, 'get_pepper' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		add_action( 'admin_init', array( $this, 'migrate' ) );
	}

	/**
	 * Activation hook.
	 *
	 * Attempt to migrate pepper file.
	 * If the pepper file does not exist, create it.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		$this->migrate();
		if ( file_exists( $this->pepper_file ) ) {
			return;
		}
		$this->set_pepper( '' );
	}

	/**
	 *
	 * Migrate existing pepper file in plugin directory if applicable.
	 *
	 * @since 2.0.0
	 */
	public function migrate() {
		// Only attempt to migrate once
		if ( false === get_option( 'cp_pepper_ok' ) ) {
			update_option( 'cp_pepper_ok', true );

			ob_start();
			if ( false === ( $creds = request_filesystem_credentials( admin_url(), '', false, false, null ) ) ) {
				return; // Await filesystem access
			}

			if ( ! WP_Filesystem( $creds ) ) {
				request_filesystem_credentials( admin_url(), '', true, false, null );
				return;
			}
			ob_end_flush();

			global $wp_filesystem;
			if ( true === $wp_filesystem->exists( __DIR__ . '/pepper.php' ) && false === $wp_filesystem->exists( $this->pepper_file ) ) {
				if ( $wp_filesystem->move( __DIR__ . '/pepper.php', $this->pepper_file ) ) {
					do_action( 'cp_pepper_file_migrated' );
				}
			}
		}
	}

	/**
	 * Menu creation.
	 *
	 * Register the menu under Settings in Network Admin screen in multisite mode, or under Options->General in single-site mode.
	 * Add the generate action.
	 *
	 * @since 1.0.0
	 */
	public function create_settings_menu() {
		if ( is_network_admin() ) {
			$this->screen = add_submenu_page(
				'settings.php',
				esc_html__( 'Pepper', 'cp-pepper' ),
				esc_html__( 'Pepper', 'cp-pepper' ),
				'manage_options',
				self::SLUG,
				array( $this, 'render_menu' ),
			);
		} else {
			if ( ! is_multisite() ) {
				$this->screen = add_submenu_page(
					'options-general.php',
					esc_html__( 'Pepper', 'cp-pepper' ),
					esc_html__( 'Pepper', 'cp-pepper' ),
					'manage_options',
					self::SLUG,
					array( $this, 'render_menu' ),
				);
			}
		}
		add_action( 'load-' . $this->screen, array( $this, 'generate_action' ) );
	}

	/**
	 * Add Settings Link.
	 *
	 * Adds a link to the Settings page to the plugin row for simpler navigation.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $links        The array of plugin action links.
	 * @param string $plugin_file_name The path to the plugin file.
	 * @return array The modified array of plugin action links.
	 */
	public function create_settings_link( $links, $plugin_file_name ) {
		if ( strpos( $plugin_file_name, basename( __FILE__ ) ) !== false ) {
			if ( is_multisite() ) {
				if ( is_network_admin() ) {
					$setting_link = '<a href="' . network_admin_url( 'settings.php?page=' . self::SLUG ) . '">' . esc_html__( 'Settings', 'cp-pepper' ) . '</a>';
					array_unshift( $links, $setting_link );
				}
			} else {
				$setting_link = '<a href="' . admin_url( 'options-general.php?page=' . self::SLUG ) . '">' . esc_html__( 'Settings', 'cp-pepper' ) . '</a>';
				array_unshift( $links, $setting_link );
			}
		}

		return $links;
	}

	/**
	 * Enable localization.
	 *
	 * @since 1.0.3
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'cp-pepper', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Save the pepper.
	 *
	 * @since 1.0.0
	 *
	 * @param string $pepper The pepper string.
	 * @return bool True on success, false on failure.
	 */
	private function set_pepper( $pepper ) {
		$content = '<?php
namespace ClassicPress\PepperPassword;
$current_pepper = \'' . $pepper . '\';
';

		ob_start();
		if ( false === ( $creds = request_filesystem_credentials( admin_url(), '', false, false, null ) ) ) {
			return; // Await filesystem access
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( admin_url(), '', true, false, null );
			return;
		}
		ob_end_flush();

		global $wp_filesystem;
		return (bool) $wp_filesystem->put_contents( $this->pepper_file, $content );
	}

	/**
	 * Get the pepper.
	 *
	 * @since 1.0.0
	 *
	 * @return string Pepper string on success, empty on failure.
	 */
	public function get_pepper() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		ob_start();
		if ( false === ( $creds = request_filesystem_credentials( admin_url(), '', false, false, null ) ) ) {
			return; // Await filesystem access
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( admin_url(), '', true, false, null );
			return;
		}
		ob_end_flush();

		global $wp_filesystem;
		if ( $wp_filesystem->exists( $this->pepper_file ) ) {
			$pepper = $wp_filesystem->get_contents( $this->pepper_file );
		}

		if ( empty( $pepper ) ) {
			return '';
		}

		$match = preg_match( '/\$current_pepper = \'([a-zA-Z0-9]*)\';/', $pepper, $matches );

		if ( $match !== 1 ) {
			return '';
		}
		return $matches[1];
	}

	/**
	 * Menu renderer.
	 *
	 * Render the menu.
	 *
	 * @since 1.0.0
	 */
	public function render_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'cp-pepper' ) );
		}

		$url = is_multisite() ? network_admin_url( 'settings.php?page=' . self::SLUG ) : admin_url( 'options-general.php?page=' . self::SLUG );

		if ( false === ( $creds = request_filesystem_credentials( $url, '', false, false, null ) ) ) {
			return; // Await filesystem access
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( $url, '', true, false, null );
			return;
		}

		global $wp_filesystem;

		echo '<div class="wrap">';
		echo '<div class="cp-pepper-general">';
		echo '<h1>' . esc_html__( 'ClassicPress Pepper', 'cp-pepper' ) . '</h1>';

		if ( false === $wp_filesystem->is_writable( dirname( $this->pepper_file ) ) ) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p>' . esc_html__( 'Error: Cannot write the pepper file.', 'cp-pepper' ) . '</p>';
			echo '<p><code>' . esc_html( $this->pepper_file ) . '</code></p>';
			if ( $wp_filesystem->is_readable( $this->pepper_file ) ) {
				echo '<p>' . esc_html__( 'You can edit the file manually to add or change the pepper.', 'cp-pepper' ) . '</p>';
			}
			echo '</div></div></div>';
			return;
		}

		$pepper = $this->get_pepper();

		$notice = get_transient( 'cp_pepper_generate_response' );
		if ( $notice !== false ) {
			delete_transient( 'cp_pepper_generate_response' );
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p>' . esc_html( $notice ) . '</p>';
			echo '</div>';
		}

		echo '<h3>' . esc_html__( 'Create or renew a pepper for the password storing algorithm', 'cp-pepper' ) . '</h3>';
		echo '<p>' . esc_html__( 'Note that changing the pepper, or deactivating this plugin, will invalidate all stored password hashes and will mean that every user will need to reset their password.', 'cp-pepper' ) . '</p>';

		$message = $pepper === '' ? esc_html__( 'ClassicPress is not currently using a Pepper.', 'cp-pepper' ) : esc_html__( 'ClassicPress is currently using a Pepper.', 'cp-pepper' );
		$button  = $pepper === '' ? esc_html__( 'Enable Pepper', 'cp-pepper' ) : esc_html__( 'Renew Pepper', 'cp-pepper' );

		echo '<p>' . esc_html( $message ) . '</p>';
		echo '<form action="' . esc_url_raw( add_query_arg( array( 'action' => 'generate' ), $url ) ) . '" method="POST">';
		wp_nonce_field( 'generate', '_cppepper' );
		echo '<input type="submit" class="button button-primary" id="submit_button" value="' . esc_html( $button ) . '"></input> ';
		echo '</form></div></div>';
	}

	/**
	 * Generate a random pepper.
	 *
	 * @since 1.0.0
	 *
	 * @return string Random pepper string
	 */
	private function random_pepper() {
		/**
		 * Filter used to change autogenerated pepper length.
		 *
		 * @since CP-2.2.0
		 *
		 * @param int $length Length of the pepper string.
		 */
		$length = (int) apply_filters( 'cp_pepper_length', 32 );

		return wp_generate_password( $length, false, false );
	}

	/**
	 * Generate action handler.
	 *
	 * Generate pepper, store transient results and redirect.
	 *
	 * @since 1.0.0
	 */
	public function generate_action() {
		if ( ! isset( $_GET['action'] ) ) {
			return false;
		}
		if ( $_GET['action'] !== 'generate' ) {
			return false;
		}
		if ( ! check_admin_referer( 'generate', '_cppepper' ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$pepper_result = $this->get_pepper() === '' ? esc_html__( 'Pepper enabled.', 'cp-pepper' ) : esc_html__( 'Pepper renewed.', 'cp-pepper' );
		set_transient( 'cp_pepper_generate_response', $pepper_result, 30 );

		$pepper = $this->random_pepper();
		$this->set_pepper( $pepper );

		$sendback = remove_query_arg( array( 'action', '_cppepper' ), wp_get_referer() );
		wp_safe_redirect( $sendback );
		exit;
	}
}

new PepperPassword();
