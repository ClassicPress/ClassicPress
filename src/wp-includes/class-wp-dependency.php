<?php
/**
 * Dependencies API: _WP_Dependency class
 *
 * @since WP-4.7.0
 *
 * @package ClassicPress
 * @subpackage Dependencies
 */

/**
 * Class _WP_Dependency
 *
 * Helper class to register a handle and associated data.
 *
 * @access private
 * @since WP-2.6.0
 */
class _WP_Dependency {
	/**
	 * The handle name.
	 *
	 * @since WP-2.6.0
	 * @var null
	 */
	public $handle;

	/**
	 * The handle source.
	 *
	 * @since WP-2.6.0
	 * @var null
	 */
	public $src;

	/**
	 * An array of handle dependencies.
	 *
	 * @since WP-2.6.0
	 * @var array
	 */
	public $deps = array();

	/**
	 * The handle version.
	 *
	 * Used for cache-busting.
	 *
	 * @since WP-2.6.0
	 * @var bool|string
	 */
	public $ver = false;

	/**
	 * Additional arguments for the handle.
	 *
	 * @since WP-2.6.0
	 * @var null
	 */
	public $args = null;  // Custom property, such as $in_footer or $media.

	/**
	 * Extra data to supply to the handle.
	 *
	 * @since WP-2.6.0
	 * @var array
	 */
	public $extra = array();

	/**
	 * Setup dependencies.
	 *
	 * @since WP-2.6.0
	 */
	public function __construct() {
		@list( $this->handle, $this->src, $this->deps, $this->ver, $this->args ) = func_get_args();
		if ( ! is_array($this->deps) )
			$this->deps = array();
	}

	/**
	 * Add handle data.
	 *
	 * @since WP-2.6.0
	 *
	 * @param string $name The data key to add.
	 * @param mixed  $data The data value to add.
	 * @return bool False if not scalar, true otherwise.
	 */
	public function add_data( $name, $data ) {
		if ( !is_scalar($name) )
			return false;
		$this->extra[$name] = $data;
		return true;
	}

}
