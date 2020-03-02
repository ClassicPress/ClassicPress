<?php
/**
 * User API: WP_User class
 *
 * @package ClassicPress
 * @subpackage Users
 * @since WP-4.4.0
 */

/**
 * Core class used to implement the WP_User object.
 *
 * @since WP-2.0.0
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int    $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 */
class WP_User {
	/**
	 * User data container.
	 *
	 * @since WP-2.0.0
	 * @var object
	 */
	public $data;

	/**
	 * The user's ID.
	 *
	 * @since WP-2.1.0
	 * @var int
	 */
	public $ID = 0;

	/**
	 * The individual capabilities the user has been given.
	 *
	 * @since WP-2.0.0
	 * @var array
	 */
	public $caps = array();

	/**
	 * User metadata option name.
	 *
	 * @since WP-2.0.0
	 * @var string
	 */
	public $cap_key;

	/**
	 * The roles the user is part of.
	 *
	 * @since WP-2.0.0
	 * @var array
	 */
	public $roles = array();

	/**
	 * All capabilities the user has, including individual and role based.
	 *
	 * @since WP-2.0.0
	 * @var array
	 */
	public $allcaps = array();

	/**
	 * The filter context applied to user data fields.
	 *
	 * @since WP-2.9.0
	 * @var string
	 */
	public $filter = null;

	/**
	 * The site ID the capabilities of this user are initialized for.
	 *
	 * @since WP-4.9.0
	 * @var int
	 */
	private $site_id = 0;

	/**
	 * @static
	 * @since WP-3.3.0
	 * @var array
	 */
	private static $back_compat_keys;

	/**
	 * Constructor.
	 *
	 * Retrieves the userdata and passes it to WP_User::init().
	 *
	 * @since WP-2.0.0
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 *
	 * @param int|string|stdClass|WP_User $id User's ID, a WP_User object, or a user object from the DB.
	 * @param string $name Optional. User's username
	 * @param int $site_id Optional Site ID, defaults to current site.
	 */
	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		if ( ! isset( self::$back_compat_keys ) ) {
			$prefix = $GLOBALS['wpdb']->prefix;
			self::$back_compat_keys = array(
				'user_firstname' => 'first_name',
				'user_lastname' => 'last_name',
				'user_description' => 'description',
				'user_level' => $prefix . 'user_level',
				$prefix . 'usersettings' => $prefix . 'user-settings',
				$prefix . 'usersettingstime' => $prefix . 'user-settings-time',
			);
		}

		if ( $id instanceof WP_User ) {
			$this->init( $id->data, $site_id );
			return;
		} elseif ( is_object( $id ) ) {
			$this->init( $id, $site_id );
			return;
		}

		if ( ! empty( $id ) && ! is_numeric( $id ) ) {
			$name = $id;
			$id = 0;
		}

		if ( $id ) {
			$data = self::get_data_by( 'id', $id );
		} else {
			$data = self::get_data_by( 'login', $name );
		}

		if ( $data ) {
			$this->init( $data, $site_id );
		} else {
			$this->data = new stdClass;
		}
	}

	/**
	 * Sets up object properties, including capabilities.
	 *
	 * @since  WP-3.3.0
	 *
	 * @param object $data    User DB row object.
	 * @param int    $site_id Optional. The site ID to initialize for.
	 */
	public function init( $data, $site_id = '' ) {
		$this->data = $data;
		$this->ID = (int) $data->ID;

		$this->for_site( $site_id );
	}

	/**
	 * Return only the main user fields
	 *
	 * @since WP-3.3.0
	 * @since WP-4.4.0 Added 'ID' as an alias of 'id' for the `$field` parameter.
	 *
	 * @static
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 *
	 * @param string $field The field to query against: 'id', 'ID', 'slug', 'email' or 'login'.
	 * @param string|int $value The field value
	 * @return object|false Raw user object
	 */
	public static function get_data_by( $field, $value ) {
		global $wpdb;

		// 'ID' is an alias of 'id'.
		if ( 'ID' === $field ) {
			$field = 'id';
		}

		if ( 'id' == $field ) {
			// Make sure the value is numeric to avoid casting objects, for example,
			// to int 1.
			if ( ! is_numeric( $value ) )
				return false;
			$value = intval( $value );
			if ( $value < 1 )
				return false;
		} else {
			$value = trim( $value );
		}

		if ( !$value )
			return false;

		switch ( $field ) {
			case 'id':
				$user_id = $value;
				$db_field = 'ID';
				break;
			case 'slug':
				$user_id = wp_cache_get($value, 'userslugs');
				$db_field = 'user_nicename';
				break;
			case 'email':
				$user_id = wp_cache_get($value, 'useremail');
				$db_field = 'user_email';
				break;
			case 'login':
				$value = sanitize_user( $value );
				$user_id = wp_cache_get($value, 'userlogins');
				$db_field = 'user_login';
				break;
			default:
				return false;
		}

		if ( false !== $user_id ) {
			if ( $user = wp_cache_get( $user_id, 'users' ) )
				return $user;
		}

		if ( !$user = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $wpdb->users WHERE $db_field = %s", $value
		) ) )
			return false;

		update_user_caches( $user );

		return $user;
	}

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since WP-3.3.0
	 *
	 * @param string $key User meta key to check if set.
	 * @return bool Whether the given user meta key is set.
	 */
	public function __isset( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', 'WP-2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
			$key = 'ID';
		}

		if ( isset( $this->data->$key ) )
			return true;

		if ( isset( self::$back_compat_keys[ $key ] ) )
			$key = self::$back_compat_keys[ $key ];

		return metadata_exists( 'user', $this->ID, $key );
	}

	/**
	 * Magic method for accessing custom fields.
	 *
	 * @since WP-3.3.0
	 *
	 * @param string $key User meta key to retrieve.
	 * @return mixed Value of the given user meta key (if set). If `$key` is 'id', the user ID.
	 */
	public function __get( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', 'WP-2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
			return $this->ID;
		}

		if ( isset( $this->data->$key ) ) {
			$value = $this->data->$key;
		} else {
			if ( isset( self::$back_compat_keys[ $key ] ) )
				$key = self::$back_compat_keys[ $key ];
			$value = get_user_meta( $this->ID, $key, true );
		}

		if ( $this->filter ) {
			$value = sanitize_user_field( $key, $value, $this->ID, $this->filter );
		}

		return $value;
	}

	/**
	 * Magic method for setting custom user fields.
	 *
	 * This method does not update custom fields in the database. It only stores
	 * the value on the WP_User instance.
	 *
	 * @since WP-3.3.0
	 *
	 * @param string $key   User meta key.
	 * @param mixed  $value User meta value.
	 */
	public function __set( $key, $value ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', 'WP-2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
			$this->ID = $value;
			return;
		}

		$this->data->$key = $value;
	}

	/**
	 * Magic method for unsetting a certain custom field.
	 *
	 * @since WP-4.4.0
	 *
	 * @param string $key User meta key to unset.
	 */
	public function __unset( $key ) {
		if ( 'id' == $key ) {
			_deprecated_argument( 'WP_User->id', 'WP-2.1.0',
				sprintf(
					/* translators: %s: WP_User->ID */
					__( 'Use %s instead.' ),
					'<code>WP_User->ID</code>'
				)
			);
		}

		if ( isset( $this->data->$key ) ) {
			unset( $this->data->$key );
		}

		if ( isset( self::$back_compat_keys[ $key ] ) ) {
			unset( self::$back_compat_keys[ $key ] );
		}
	}

	/**
	 * Determine whether the user exists in the database.
	 *
	 * @since WP-3.4.0
	 *
	 * @return bool True if user exists in the database, false if not.
	 */
	public function exists() {
		return ! empty( $this->ID );
	}

	/**
	 * Retrieve the value of a property or meta key.
	 *
	 * Retrieves from the users and usermeta table.
	 *
	 * @since WP-3.3.0
	 *
	 * @param string $key Property
	 * @return mixed
	 */
	public function get( $key ) {
		return $this->__get( $key );
	}

	/**
	 * Determine whether a property or meta key is set
	 *
	 * Consults the users and usermeta tables.
	 *
	 * @since WP-3.3.0
	 *
	 * @param string $key Property
	 * @return bool
	 */
	public function has_prop( $key ) {
		return $this->__isset( $key );
	}

	/**
	 * Return an array representation.
	 *
	 * @since WP-3.5.0
	 *
	 * @return array Array representation.
	 */
	public function to_array() {
		return get_object_vars( $this->data );
	}

	/**
	 * Makes private/protected methods readable for backward compatibility.
	 *
	 * @since WP-4.3.0
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( '_init_caps' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
	 * Set up capability object properties.
	 *
	 * Will set the value for the 'cap_key' property to current database table
	 * prefix, followed by 'capabilities'. Will then check to see if the
	 * property matching the 'cap_key' exists and is an array. If so, it will be
	 * used.
	 *
	 * @since WP-2.1.0
	 * @deprecated WP-4.9.0 Use WP_User::for_site()
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 *
	 * @param string $cap_key Optional capability key
	 */
	protected function _init_caps( $cap_key = '' ) {
		global $wpdb;

		_deprecated_function( __METHOD__, 'WP-4.9.0', 'WP_User::for_site()' );

		if ( empty( $cap_key ) ) {
			$this->cap_key = $wpdb->get_blog_prefix( $this->site_id ) . 'capabilities';
		} else {
			$this->cap_key = $cap_key;
		}

		$this->caps = $this->get_caps_data();

		$this->get_role_caps();
	}

	/**
	 * Retrieve all of the role capabilities and merge with individual capabilities.
	 *
	 * All of the capabilities of the roles the user belongs to are merged with
	 * the users individual roles. This also means that the user can be denied
	 * specific roles that their role might have, but the specific user isn't
	 * granted permission to.
	 *
	 * @since WP-2.0.0
	 *
	 * @return array List of all capabilities for the user.
	 */
	public function get_role_caps() {
		$switch_site = false;
		if ( is_multisite() && $this->site_id != get_current_blog_id() ) {
			$switch_site = true;

			switch_to_blog( $this->site_id );
		}

		$wp_roles = wp_roles();

		//Filter out caps that are not role names and assign to $this->roles
		if ( is_array( $this->caps ) )
			$this->roles = array_filter( array_keys( $this->caps ), array( $wp_roles, 'is_role' ) );

		//Build $allcaps from role caps, overlay user's $caps
		$this->allcaps = array();
		foreach ( (array) $this->roles as $role ) {
			$the_role = $wp_roles->get_role( $role );
			$this->allcaps = array_merge( (array) $this->allcaps, (array) $the_role->capabilities );
		}
		$this->allcaps = array_merge( (array) $this->allcaps, (array) $this->caps );

		if ( $switch_site ) {
			restore_current_blog();
		}

		return $this->allcaps;
	}

	/**
	 * Add role to user.
	 *
	 * Updates the user's meta data option with capabilities and roles.
	 *
	 * @since WP-2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function add_role( $role ) {
		if ( empty( $role ) ) {
			return;
		}

		$this->caps[$role] = true;
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires immediately after the user has been given a new role.
		 *
		 * @since WP-4.3.0
		 *
		 * @param int    $user_id The user ID.
		 * @param string $role    The new role.
		 */
		do_action( 'add_user_role', $this->ID, $role );
	}

	/**
	 * Remove role from user.
	 *
	 * @since WP-2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function remove_role( $role ) {
		if ( !in_array($role, $this->roles) )
			return;
		unset( $this->caps[$role] );
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires immediately after a role as been removed from a user.
		 *
		 * @since WP-4.3.0
		 *
		 * @param int    $user_id The user ID.
		 * @param string $role    The removed role.
		 */
		do_action( 'remove_user_role', $this->ID, $role );
	}

	/**
	 * Set the role of the user.
	 *
	 * This will remove the previous roles of the user and assign the user the
	 * new one. You can set the role to an empty string and it will remove all
	 * of the roles from the user.
	 *
	 * @since WP-2.0.0
	 *
	 * @param string $role Role name.
	 */
	public function set_role( $role ) {
		if ( 1 == count( $this->roles ) && $role == current( $this->roles ) )
			return;

		foreach ( (array) $this->roles as $oldrole )
			unset( $this->caps[$oldrole] );

		$old_roles = $this->roles;
		if ( !empty( $role ) ) {
			$this->caps[$role] = true;
			$this->roles = array( $role => true );
		} else {
			$this->roles = false;
		}
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();

		/**
		 * Fires after the user's role has changed.
		 *
		 * @since WP-2.9.0
		 * @since WP-3.6.0 Added $old_roles to include an array of the user's previous roles.
		 *
		 * @param int    $user_id   The user ID.
		 * @param string $role      The new role.
		 * @param array  $old_roles An array of the user's previous roles.
		 */
		do_action( 'set_user_role', $this->ID, $role, $old_roles );
	}

	/**
	 * Choose the maximum level the user has.
	 *
	 * Will compare the level from the $item parameter against the $max
	 * parameter. If the item is incorrect, then just the $max parameter value
	 * will be returned.
	 *
	 * Used to get the max level based on the capabilities the user has. This
	 * is also based on roles, so if the user is assigned the Administrator role
	 * then the capability 'level_10' will exist and the user will get that
	 * value.
	 *
	 * @since WP-2.0.0
	 *
	 * @param int $max Max level of user.
	 * @param string $item Level capability name.
	 * @return int Max Level.
	 */
	public function level_reduction( $max, $item ) {
		if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
			$level = intval( $matches[1] );
			return max( $max, $level );
		} else {
			return $max;
		}
	}

	/**
	 * Update the maximum user level for the user.
	 *
	 * Updates the 'user_level' user metadata (includes prefix that is the
	 * database table prefix) with the maximum user level. Gets the value from
	 * the all of the capabilities that the user has.
	 *
	 * @since WP-2.0.0
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 */
	public function update_user_level_from_caps() {
		global $wpdb;
		$this->user_level = array_reduce( array_keys( $this->allcaps ), array( $this, 'level_reduction' ), 0 );
		update_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level', $this->user_level );
	}

	/**
	 * Add capability and grant or deny access to capability.
	 *
	 * @since WP-2.0.0
	 *
	 * @param string $cap Capability name.
	 * @param bool $grant Whether to grant capability to user.
	 */
	public function add_cap( $cap, $grant = true ) {
		$this->caps[$cap] = $grant;
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();
	}

	/**
	 * Remove capability from user.
	 *
	 * @since WP-2.0.0
	 *
	 * @param string $cap Capability name.
	 */
	public function remove_cap( $cap ) {
		if ( ! isset( $this->caps[ $cap ] ) ) {
			return;
		}
		unset( $this->caps[ $cap ] );
		update_user_meta( $this->ID, $this->cap_key, $this->caps );
		$this->get_role_caps();
		$this->update_user_level_from_caps();
	}

	/**
	 * Remove all of the capabilities of the user.
	 *
	 * @since WP-2.1.0
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 */
	public function remove_all_caps() {
		global $wpdb;
		$this->caps = array();
		delete_user_meta( $this->ID, $this->cap_key );
		delete_user_meta( $this->ID, $wpdb->get_blog_prefix() . 'user_level' );
		$this->get_role_caps();
	}

	/**
	 * Whether the user has a specific capability.
	 *
	 * While checking against a role in place of a capability is supported in part, this practice is discouraged as it
	 * may produce unreliable results.
	 *
	 * @since WP-2.0.0
	 *
	 * @see map_meta_cap()
	 *
	 * @param string $cap           Capability name.
	 * @param int    $object_id,... Optional. ID of a specific object to check against if `$cap` is a "meta" capability.
	 *                              Meta capabilities such as `edit_post` and `edit_user` are capabilities used by
	 *                              by the `map_meta_cap()` function to map to primitive capabilities that a user or
	 *                              role has, such as `edit_posts` and `edit_others_posts`.
	 * @return bool Whether the user has the given capability, or, if `$object_id` is passed, whether the user has
	 *              the given capability for that object.
	 */
	public function has_cap( $cap ) {
		if ( is_numeric( $cap ) ) {
			_deprecated_argument( __FUNCTION__, 'WP-2.0.0', __( 'Usage of user levels is deprecated. Use capabilities instead.' ) );
			$cap = $this->translate_level_to_cap( $cap );
		}

		$args = array_slice( func_get_args(), 1 );
		$args = array_merge( array( $cap, $this->ID ), $args );
		$caps = call_user_func_array( 'map_meta_cap', $args );

		// Multisite super admin has all caps by definition, Unless specifically denied.
		if ( is_multisite() && is_super_admin( $this->ID ) ) {
			if ( in_array('do_not_allow', $caps) )
				return false;
			return true;
		}

		/**
		 * Dynamically filter a user's capabilities.
		 *
		 * @since WP-2.0.0
		 * @since WP-3.7.0 Added the user object.
		 *
		 * @param array   $allcaps An array of all the user's capabilities.
		 * @param array   $caps    Actual capabilities for meta capability.
		 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
		 * @param WP_User $user    The user object.
		 */
		$capabilities = apply_filters( 'user_has_cap', $this->allcaps, $caps, $args, $this );

		// Everyone is allowed to exist.
		$capabilities['exist'] = true;

		// Nobody is allowed to do things they are not allowed to do.
		unset( $capabilities['do_not_allow'] );

		// Must have ALL requested caps.
		foreach ( (array) $caps as $cap ) {
			if ( empty( $capabilities[ $cap ] ) )
				return false;
		}

		return true;
	}

	/**
	 * Convert numeric level to level capability name.
	 *
	 * Prepends 'level_' to level number.
	 *
	 * @since WP-2.0.0
	 *
	 * @param int $level Level number, 1 to 10.
	 * @return string
	 */
	public function translate_level_to_cap( $level ) {
		return 'level_' . $level;
	}

	/**
	 * Set the site to operate on. Defaults to the current site.
	 *
	 * @since WP-3.0.0
	 * @deprecated WP-4.9.0 Use WP_User::for_site()
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 *
	 * @param int $blog_id Optional. Site ID, defaults to current site.
	 */
	public function for_blog( $blog_id = '' ) {
		_deprecated_function( __METHOD__, 'WP-4.9.0', 'WP_User::for_site()' );

		$this->for_site( $blog_id );
	}

	/**
	 * Sets the site to operate on. Defaults to the current site.
	 *
	 * @since WP-4.9.0
	 *
	 * @global wpdb $wpdb ClassicPress database abstraction object.
	 *
	 * @param int $site_id Site ID to initialize user capabilities for. Default is the current site.
	 */
	public function for_site( $site_id = '' ) {
		global $wpdb;

		if ( ! empty( $site_id ) ) {
			$this->site_id = absint( $site_id );
		} else {
			$this->site_id = get_current_blog_id();
		}

		$this->cap_key = $wpdb->get_blog_prefix( $this->site_id ) . 'capabilities';

		$this->caps = $this->get_caps_data();

		$this->get_role_caps();
	}

	/**
	 * Gets the ID of the site for which the user's capabilities are currently initialized.
	 *
	 * @since WP-4.9.0
	 *
	 * @return int Site ID.
	 */
	public function get_site_id() {
		return $this->site_id;
	}

	/**
	 * Gets the available user capabilities data.
	 *
	 * @since WP-4.9.0
	 *
	 * @return array User capabilities array.
	 */
	private function get_caps_data() {
		$caps = get_user_meta( $this->ID, $this->cap_key, true );

		if ( ! is_array( $caps ) ) {
			return array();
		}

		return $caps;
	}
}
