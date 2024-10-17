<?php

/**
 * Specifies the names of objects that will be stored in the object-relationships table.
 *
 * @since CP-2.2.0
 *
 * @return array  $objects  Filtered array of recognized relationship objects.
 */
function cp_recognized_relationship_objects() {

	// Names of post types (including custom post types).
	$post_types = get_post_types();

	// Names of taxonomies (including custom taxonomies).
	$taxonomies = get_taxonomies();

	// Merge arrays and add comments, users, and thumbnails.
	$objects = array_merge( $post_types, $taxonomies );
	$objects['comment']   = 'comment';
	$objects['user']      = 'user';
	$objects['thumbnail'] = 'thumbnail';
	/**
	 * Filter enabling modification of the list of recognized relationship objects.
	 *
	 * @since CP-2.2.0
	 *
	 * @param  array  $objects  Default array of recognized relationship objects.
	 *
	 * @return array  $objects  Filtered array of recognized relationship objects.
	 */
	return apply_filters( 'recognized_relationship_objects', $objects );
}
add_action( 'init', 'cp_recognized_relationship_objects' );


/**
 * Check if a relationship exists between two recognized objects.
 * Relationship is bi-directional so the objects may be given in either order.
 *
 * Objects must be among the array produced by cp_recognized_relationship_objects().
 *
 * @since CP-2.2.0
 *
 * @param  int      $left_object_id        ID of first object.
 * @param  string   $left_object_type      Name of type of first object.
 * @param  string   $right_object_type     Name of type of second object.
 * @param  int      $right_object_id       ID of second object.
 *
 * @return int|WP_Error  $relationship_id  Relationship ID or 0 if none exists.
 *                                         WP_Error when a param of an incorrect type is specified.
 */
function cp_object_relationship_exists( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	// Error if $left_object_id is not a positive integer.
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		} elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		/* translators: %s: String, 0, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 1 to a positive integer; %s given.' ), $item );

		return new WP_Error( 'left_object_id', $message );
	}

	// Error if $right_object_id is not a positive integer.
	if ( filter_var( $right_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $right_object_id === 0 ) {
			$item = 0;
		} elseif ( is_object( $right_object_id ) ) {
			$item = 'object';
		} elseif ( is_array( $right_object_id ) ) {
			$item = 'array';
		}

		/* translators: %s: String, 0, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 4 to a positive integer; %s given.' ), $item );

		return new WP_Error( 'right_object_id', $message );
	}

	// Get array, and create list, of recognized relationship objects.
	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	// Error if $left_object_type is not a non-null string of an appropriate value.
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 2 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'left_object_type', $message );
	}

	// Error if $right_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 3 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'right_object_type', $message );
	}

	// Good to go, so query database table.
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';
	$relationship_id = 0;

	$relationship_array = array(
		'left_object_id'    => $left_object_id,
		'left_object_type'  => $left_object_type,
		'right_object_type' => $right_object_type,
		'right_object_id'   => $right_object_id,
	);

	// Check if this relationship already exists.
	$sql1 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s AND right_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	// If so, return the relationship ID as an integer.
	$row = $wpdb->get_row( $sql1 );
	if ( is_object( $row ) ) {
		$relationship_id = (int) $row->relationship_id;
	}

	if ( empty( $relationship_id ) ) {

		// Also query database table right to left if no match so far.
		$sql2 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s AND left_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		// If this relationship exists, return the relationship ID as an integer.
		$row = $wpdb->get_row( $sql2 );
		if ( is_object( $row ) ) {
			$relationship_id = (int) $row->relationship_id;
		}
	}

	// Hook when search for pre-existing relationship completed.
	do_action( 'existing_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	// Return relationship ID (which will be 0 if none exists).
	return $relationship_id;
}


/**
 * Create a relationship between two recognized objects.
 *
 * Objects must be among the array produced by cp_recognized_relationship_objects().
 *
 * @since CP-2.2.0
 *
 * @param  int      $left_object_id     ID of first object.
 * @param  string   $left_object_type   Name of type of first object.
 * @param  string   $right_object_type  Name of type of second object.
 * @param  int      $right_object_id    ID of second object.
 *
 * @return int      $relationship_id    Relationship ID of a relationship if it already exists.
 *                                      Otherwise relationship ID of newly-created relationship.
 */
function cp_add_object_relationship( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	// Check if relationship already exists: if so return relationship ID.
	$relationship_id = cp_object_relationship_exists( $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	if ( absint( $relationship_id ) === 0 ) {

		// Relationship does not exist, so insert it now.
		global $wpdb;
		$table_name = $wpdb->prefix . 'object_relationships';

		$relationship_array = array(
			'left_object_id'    => $left_object_id,
			'left_object_type'  => $left_object_type,
			'right_object_type' => $right_object_type,
			'right_object_id'   => $right_object_id,
		);

		// $wpdb->insert sanitizes data.
		$added = $wpdb->insert( $table_name, $relationship_array );
		$relationship_id = $wpdb->insert_id;

		// Hook after relationship added.
		do_action( 'added_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );
	}

	// Return relationship ID of newly-inserted relationship.
	return $relationship_id;
}


/**
 * Get IDs of all the recognized objects related to the known object.
 *
 * Objects must be among the array produced by cp_recognized_relationship_objects().
 *
 * @since CP-2.2.0
 *
 * @param  int      $left_object_id     ID of first object.
 * @param  string   $left_object_type   Name of type of first object.
 * @param  string   $right_object_type  Name of type of second object.
 *
 * @return array|WP_Error  $target_ids  ID of each object with which the first object has a relationship.
 *                                      WP_Error when a param of an incorrect type is specified.
 */
function cp_get_object_relationship_ids( $left_object_id, $left_object_type, $right_object_type ) {

	// Error if $left_object_id is not a positive integer
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		} elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		/* translators: %s: String, 0, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 1 to a positive integer; %s given.' ), $item );

		return new WP_Error( 'left_object_id', $message );
	}

	// Get array, and create list, of recognized relationship objects.
	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	// Error if $left_object_type is not a non-null string of an appropriate value.
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 2 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'left_object_type', $message );
	}

	// Error if $right_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 3 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'left_object_type', $message );
	}

	// Good to go, so query database table.
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';

	// Query database table from left to right.
	$sql1 = $wpdb->prepare( "SELECT right_object_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s", $left_object_id, $left_object_type, $right_object_type );

	// Query database table from right to left.
	$sql2 = $wpdb->prepare( "SELECT left_object_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s", $left_object_id, $left_object_type, $right_object_type );

	// Results are in the form of two objects.
	$rows1 = $wpdb->get_results( $sql1 );
	$rows2 = $wpdb->get_results( $sql2 );

	// Create array of target object IDs, starting with an empty array.
	$target_ids = array();
	if ( ! empty( $rows1 ) ) {
		foreach ( $rows1 as $row ) {
			$target_ids[] = (int) $row->right_object_id; // cast each one as an integer
		}
	}

	if ( ! empty( $rows2 ) ) {
		foreach ( $rows2 as $row ) {
			$target_ids[] = (int) $row->left_object_id; // cast each one as an integer
		}
	}

	// Return the array of integers or an empty array.
	return $target_ids;
}


/**
 * Delete a relationship between two recognized objects.
 *
 * Objects must be among the array produced by cp_recognized_relationship_objects().
 *
 * @since CP-2.2.0
 *
 * @param  int      $left_object_id        ID of first object.
 * @param  string   $left_object_type      Name of type of first object.
 * @param  string   $right_object_type     Name of type of second object.
 * @param  int      $right_object_id       ID of second object.
 *
 * @return int|WP_Error  $relationship_id  ID of deleted relationship, or 0 if no relationship found.
 *                                         WP_Error when a param of an incorrect type is specified.
 */
function cp_delete_object_relationship( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	// Error if $left_object_id is not a positive integer.
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		} elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		/* translators: %s: String, 0, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 1 to a positive integer; %s given.' ), $item );

		return new WP_Error( 'left_object_id', $message );
	}

	// Error if $right_object_id is not a positive integer
	if ( filter_var( $right_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $right_object_id === 0 ) {
			$item = 0;
		} elseif ( is_object( $right_object_id ) ) {
			$item = 'object';
		} elseif ( is_array( $right_object_id ) ) {
			$item = 'array';
		}

		/* translators: %s: String, 0, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 4 to a positive integer; %s given.' ), $item );

		return new WP_Error( 'right_object_id', $message );
	}

	// Get array, and create list, of recognized relationship objects.
	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	// Error if $left_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 2 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'left_object_type', $message );
	}

	// Error if $right_object_type is not a non-null string of an appropriate value.
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		} elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		} elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		/* translators: 1: List of recognized relationship objects, 2: Integer, object, or array. */
		$message = sprintf( __( 'cp_add_object_relationship() expects parameter 3 to be one of %s; %s given.' ), $object_list, $item );

		return new WP_Error( 'left_object_type', $message );
	}

	// Good to go, so query database table.
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';
	$relationship_id = 0;

	// $wpdb->query does not sanitize data, so use $wpdb->prepare.
	$sql1 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s AND right_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	// Get the relationship ID as an integer.
	$row = $wpdb->get_row( $sql1 );
	if ( is_object( $row ) ) {
		$relationship_id = (int) $row->relationship_id;
	}

	if ( empty( $relationship_id ) ) {

		$sql2 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s AND left_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		// Get the relationship ID as an integer.
		$row = $wpdb->get_row( $sql2 );
		if ( is_object( $row ) ) {
			$relationship_id = (int) $row->relationship_id;
		}
	}

	// If relationship found.
	if ( ! empty( $relationship_id ) ) {

		// Hook before relationship is deleted.
		do_action( 'pre_delete_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		// Delete relationship.
		$wpdb->delete( $table_name, array( 'relationship_id' => $relationship_id ), array( '%d' ) );

		// Delete relationship meta.
		cp_delete_relationship_meta_when_relationship_deleted( $relationship_id );

		// Hook after relationship is deleted.
		do_action( 'deleted_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );
	}

	// Return ID of deleted relationship or 0 if no relationship found.
	return $relationship_id;
}


/**
 * Delete all object relationship metadata when the relationship to which it relates is deleted.
 *
 * Objects must be among the array produced by cp_recognized_relationship_objects().
 *
 * @since CP-2.2.0
 *
 * @param  integer  $relationship_id ID of deleted relationship.
 */
function cp_delete_relationship_meta_when_relationship_deleted( $relationship_id ) {
	$metas = cp_get_relationship_meta( $relationship_id );

	foreach ( $metas as $meta_key => $meta_value ) {
		cp_delete_relationship_meta( $relationship_id, $meta_key );
	}
}

/**
 * The metadata functions below are generated automatically by ClassicPress.
 */

/**
 * Adds metadata to a relationship.
 *
 * @since CP-2.2.0
 *
 * @param int    $relationship_id  Relationship ID.
 * @param string $meta_key         Metadata name.
 * @param mixed  $meta_value       Metadata value. Must be serializable if non-scalar.
 * @param bool   $unique           Optional. Whether the same key should not be added.
 *                                 Default false.
 * @return int|false|WP_Error      Meta ID on success, false on failure.
 *                                 WP_Error when term_id is ambiguous between relationships.
 */
function cp_add_relationship_meta( $relationship_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value, $unique );
}

/**
 * Removes metadata matching criteria from a relationship.
 *
 * @since CP-2.2.0
 *
 * @param int    $relationship_id  Relationship ID.
 * @param string $meta_key         Metadata name.
 * @param mixed  $meta_value       Optional. Metadata value. If provided,
 *                                 rows will only be removed that match the value.
 *                                 Must be serializable if non-scalar. Default empty.
 * @return bool  True on success, false on failure.
 */
function cp_delete_relationship_meta( $relationship_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value );
}

/**
 * Retrieves metadata for a relationship.
 *
 * @since CP-2.2.0
 *
 * @param int    $relationship_id  Relationship ID.
 * @param string $meta_key         Optional. The meta key to retrieve. By default,
 *                                 returns data for all keys. Default empty.
 * @param bool   $single           Optional. Whether to return a single value.
 *                                 This parameter has no effect if `$key` is not specified.
 *                                 Default false.
 * @return mixed An array of values if `$single` is false.
 *               The value of the meta field if `$single` is true.
 *               False for an invalid `$term_id` (non-numeric, zero, or negative value).
 *               An empty string if a valid but non-existing term ID is passed.
 */
function cp_get_relationship_meta( $relationship_id, $meta_key = '', $single = '' ) {
	return get_metadata( 'object_relationship', $relationship_id, $meta_key, $single );
}

/**
 * Updates relationship metadata.
 *
 * Use the `$prev_value` parameter to differentiate between meta fields with the same key and relationship ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since CP-2.2.0
 *
 * @param int    $relationship_id  Relationship ID.
 * @param string $meta_key         Metadata key.
 * @param mixed  $meta_value       Metadata value. Must be serializable if non-scalar.
 * @param mixed  $prev_value       Optional. Previous value to check before updating.
 *                                 If specified, only update existing metadata entries with
 *                                 this value. Otherwise, update all entries. Default empty.
 * @return int|bool|WP_Error       Meta ID if the key didn't exist. true on successful update,
 *                                 false on failure or if the value passed to the function
 *                                 is the same as the one that is already in the database.
 *                                 WP_Error when relationship_id is ambiguous between relationships.
 */
function cp_update_relationship_meta( $relationship_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value, $prev_value );
}
