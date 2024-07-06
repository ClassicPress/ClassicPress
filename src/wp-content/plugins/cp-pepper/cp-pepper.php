<?php
/*
 * Plugin Name:       ClassicPress Pepper for Passwords
 * Plugin URI:        https://github.com/ClassicPress/ClassicPress
 * Description:       For enhanced security add a `pepper` to password hashing.
 * Version:           1.0
 * Requires at least: 4.9.15
 * Requires PHP:      7.4
 * Requires CP:       2.2
 * Author:            The ClassicPress Team
 * Author URI:        https://www.classicpress.net/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
*/

// Declare the namespace.
namespace ClassicPress\PepperPassword;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class PepperPassword {

	private $screen      = '';
	private $pepper_file = '';
	const SLUG           = 'cp-pepper';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->pepper_file = __DIR__ . '/pepper.php';
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
		add_filter( 'cp_pepper_password', array( $this, 'get_pepper' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	/**
	 * Activation hook.
	 *
	 * If the pepper file does not exists, create it.
	 *
	 * @since 1.0.0
	 */
	public function activate() {
		if ( file_exists( $this->pepper_file ) ) {
			return;
		}
		$this->set_pepper( '' );
	}

	/**
	 * Menu creation.
	 *
	 * Register the menu under options-general and add the regenerate action.
	 *
	 * @since 1.0.0
	 */
	public function create_settings_menu() {
		$this->screen = add_submenu_page(
			'options-general.php',
			esc_html__( 'Pepper' ),
			esc_html__( 'Pepper' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_menu' ),
		);
		add_action( 'load-' . $this->screen, array( $this, 'regenerate_action' ) );
	}

	/**
	 * Save the pepper.
	 *
	 * @since 1.0.0
	 *
	 * @param string The pepper string
	 * @return bool  True on success, false on failure.
	 */
	private function set_pepper( $pepper ) {
		$content = '<?php
namespace ClassicPress\PepperPassword;
$current_pepper = \'' . $pepper . '\';
';
		// $wp_filesystem is not initiated when called in an action
		return (bool) file_put_contents( $this->pepper_file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
	}

	/**
	 * Get the pepper.
	 *
	 * @since 1.0.0
	 *
	 * @return string Pepper string on success, empty on failure.
	 */
	private function get_pepper() {
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
			wp_die( esc_html__( 'Unauthorized.' ) );
		};

		if ( false === ( $creds = request_filesystem_credentials( admin_url( 'options-general.php?page=' . self::SLUG ), '', false, false, null ) ) ) {
			return; // Await filesystem access
		}

		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( admin_url( 'options-general.php?page=' . self::SLUG ), '', true, false, null );
			return;
		}

		global $wp_filesystem;

		echo '<div class="wrap">';
		echo '<div class="cp-pepper-general">';
		echo '<h1>' . esc_html__( 'ClassicPress Pepper' ) . '</h1>';

		if ( false === $wp_filesystem->is_writable( dirname( $this->pepper_file ) ) ) {
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p>' . esc_html( 'Error: Cannot write the pepper file.' ) . '</p>';
			echo '<p><code>' . esc_html( $this->pepper_file ) . '</code></p>';
			if ( $wp_filesystem->is_readable( $this->pepper_file ) ) {
				echo '<p>' . esc_html( 'You can edit the file manually to add or chenge the pepper.' ) . '</p>';
			}
			echo '</div></div></div>';
			return;
		}

		$pepper = $this->get_pepper();

		$notice = get_transient( 'cp_pepper_regenerate_response' );
		if ( $notice !== false ) {
			delete_transient( 'cp_pepper_regenerate_response' );
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p>' . esc_html( $notice ) . '</p>';
			echo '</div>';
		}

		echo '<h3>' . esc_html__( 'Create or renew a pepper for the password storing algorithm' ) . '</h3>';
		echo '<p>' . esc_html__( 'Note that changing the pepper, or deactivating this plugin, will invalidate all stored password hashes and will mean that every user will need to reset their password.' ) . '</p>';

		$message = $pepper === '' ? esc_html__( 'ClassicPress is not currently using a Pepper.' ) : esc_html__( 'ClassicPress is currently using a Pepper.' );
		$button  = $pepper === '' ? esc_html__( 'Enable Pepper' ) : esc_html__( 'Renew Pepper' );

		echo '<p>' . esc_html( $message ) . '</p>';
		echo '<form action="' . esc_url_raw( add_query_arg( array( 'action' => 'regenerate' ), admin_url( 'options-general.php?page=' . self::SLUG ) ) ) . '" method="POST">';
		wp_nonce_field( 'regenerate', '_cppepper' );
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
		 * Filter used to change autogenerated pepper lenght.
		 *
		 * @since CP-2.2.0
		 *
		 * @param  int  Length of the pepper string.
		 */
		$length = (int) apply_filters( 'cp_pepper_length', 32 );

		return wp_generate_password( $length, false, false );
	}

	/**
	 * Regenerate action handler.
	 *
	 * Generate and save in the options table the new pepper.
	 *
	 * @since 1.0.0
	 */
	public function regenerate_action() {
		if ( ! isset( $_GET['action'] ) ) {
			return false;
		}
		if ( $_GET['action'] !== 'regenerate' ) {
			return false;
		}
		if ( ! check_admin_referer( 'regenerate', '_cppepper' ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$pepper_result = $this->get_pepper() === '' ? esc_html__( 'Pepper enabled.' ) : esc_html__( 'Pepper renewed.' );
		set_transient( 'cp_pepper_regenerate_response', $pepper_result, 30 );

		$pepper = $this->random_pepper();
		$this->set_pepper( $pepper );

		$sendback = remove_query_arg( array( 'action', '_cppepper' ), wp_get_referer() );
		wp_safe_redirect( $sendback );
		exit;
	}
}

new PepperPassword();
