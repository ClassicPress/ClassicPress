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
if ( ! defined('ABSPATH' ) ) {
	die();
}

class PepperPassword {
	/*
	 * Edit this file and enter a `pepper` between the quotes below, then activate this plugin within ClassicPress
	 * Be aware, chaning the `pepper`, or deactivating this plugin  will invalidate all stored password hashes.
	 * Users would therefore be required to perform a password reset to login.
	 */
	private $pepper = '';

	/**
	 * Constructor.
	 *
	 * No properties; move straight to initialization.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
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
		add_filter( 'cp_pepper_password', array( $this, 'cp_pepper' ) );
	}

	public function cp_pepper() {
		return $this->pepper;
	}
}

new PepperPassword;
