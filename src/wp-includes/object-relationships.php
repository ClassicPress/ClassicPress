<?php

/* SPECIFY OBJECTS WHOSE RELATIONSHIPS WILL BE STORED IN THIS TABLE */
 function cp_recognized_relationship_objects() {

	# Names of core objects
	 $objects = array(
		'comment',
		'post',
		'taxonomy',
		'user'
	);

	# Get names of taxonomies and add them to $objects array
	$taxonomies = get_taxonomies();
	foreach( $taxonomies as $taxonomy ) {
		$objects[] = $taxonomy;
	}

	# Add filter to enable modification of list of recognized relationship objects
	return apply_filters( 'recognized_relationship_objects', $objects );
}


/* CHECK IF BI-DIRECTIONAL RELATIONSHIP EXISTS BETWEEN TWO OBJECTS */
function cp_object_relationship_exists( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	# Error if $left_object_id is not a positive integer
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		}
		elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_id', __( 'cp_add_object_relationship() expects parameter 1 to be a positive integer, ' . $item . ' given.' ) );
	}

	# Error if $right_object_id is not a positive integer
	if ( filter_var( $right_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $right_object_id === 0 ) {
			$item = 0;
		}
		elseif ( is_object( $right_object_id ) ) {
			$item = 'object';
		}
		elseif ( is_array( $right_object_id ) ) {
			$item = 'array';
		}

		return new WP_Error( 'right_object_id', __( 'cp_add_object_relationship() expects parameter 4 to be a positive integer, ' . $item . ' given.' ) );
	}

	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	# Error if $left_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_type', __( 'cp_add_object_relationship() expects parameter 2 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Error if $right_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'right_object_type', __( 'cp_add_object_relationship() expects parameter 3 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Good to go!
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';
	$relationship_id = 0;

	$relationship_array = array(
		'left_object_id'	=> $left_object_id,
		'left_object_type'	=> $left_object_type,
		'right_object_type' => $right_object_type,
		'right_object_id'	=> $right_object_id
	);

	# Check if this relationship already exists
	$sql1 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s AND right_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	# If so, return the relationship ID as an integer
	$row = $wpdb->get_row( $sql1 );
	if ( is_object( $row ) ) {
		$relationship_id = (int) $row->relationship_id;
	}

	if ( ! empty( $relationship_id ) ) {

		# Hook when pre-existing relationship found
		do_action( 'existing_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		return $relationship_id;

	}

	else {

		# Also query database table right to left if no match so far
		$sql2 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s AND left_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		# If this relationship exists, return the relationship ID as an integer
		$row = $wpdb->get_row( $sql2 );
		if ( is_object( $row ) ) {
			$relationship_id = (int) $row->relationship_id;
		}

		if ( ! empty( $relationship_id ) ) {

			# Hook when pre-existing relationship found
			do_action( 'existing_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		}
	}

	# Return relationship ID (which will be 0 if none exists)
	return $relationship_id;
}


/* ADD BI-DIRECTIONAL RELATIONSHIP BETWEEN TWO OBJECTS */
function cp_add_object_relationship( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	# Check if relationship already exists: if so return relationship ID
	$relationship_id = cp_object_relationship_exists( $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	if ( absint( $relationship_id ) === 0 ) {

		# Relationship does not exist, so insert it now
		global $wpdb;
		$table_name = $wpdb->prefix . 'object_relationships';

		$relationship_array = array(
			'left_object_id'	=> $left_object_id,
			'left_object_type'	=> $left_object_type,
			'right_object_type' => $right_object_type,
			'right_object_id'	=> $right_object_id
		);

		# $wpdb->insert sanitizes data
		$added = $wpdb->insert( $table_name, $relationship_array );
		$relationship_id = $wpdb->insert_id;

		# Hook after relationship added
		do_action( 'added_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );
	}

	# Return relationship ID of newly-inserted relationship
	return $relationship_id;
}


/* GET IDs OF RELATED OBJECTS */
function cp_get_object_relationship_ids( $left_object_id, $left_object_type, $right_object_type ) {

	# Error if $left_object_id is not a positive integer
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		}
		elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_id', __( 'cp_add_object_relationship() expects parameter 1 to be a positive integer, ' . $item . ' given.' ) );
	}

	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	# Error if $left_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_type', __( 'cp_add_object_relationship() expects parameter 2 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Error if $right_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_type', __( 'cp_add_object_relationship() expects parameter 3 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Good to go!
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';

	# Query database table from left to right
	$sql1 = $wpdb->prepare( "SELECT right_object_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s", $left_object_id, $left_object_type, $right_object_type );

	# Query database table right to left
	$sql2 = $wpdb->prepare( "SELECT left_object_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s", $left_object_id, $left_object_type, $right_object_type );

	# Results are in the form of two objects
	$rows1 = $wpdb->get_results( $sql1 );
	$rows2 = $wpdb->get_results( $sql2 );

	# Create array of target object IDs, starting with empty array
	$target_ids = [];
	if ( ! empty( $rows1 ) ) {
		foreach( $rows1 as $row ) {
			$target_ids[] = (int) $row->right_object_id; // cast each one as an integer
		}
	}

	if ( ! empty( $rows2 ) ) {
		foreach( $rows2 as $row ) {
			$target_ids[] = (int) $row->left_object_id; // cast each one as an integer
		}
	}

	# Return the above array
	return $target_ids;
}


/* DELETE BI-DIRECTIONAL RELATIONSHIP BETWEEN TWO OBJECTS */
function cp_delete_object_relationship( $left_object_id, $left_object_type, $right_object_type, $right_object_id ) {

	# Error if $left_object_id is not a positive integer
	if ( filter_var( $left_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $left_object_id === 0 ) {
			$item = 0;
		}
		elseif ( is_object( $left_object_id ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_id ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_id', __( 'cp_add_object_relationship() expects parameter 1 to be a positive integer, ' . $item . ' given.' ) );
	}

	# Error if $right_object_id is not a positive integer
	if ( filter_var( $right_object_id, FILTER_VALIDATE_INT ) === false ) {

		$item = 'string';
		if ( $right_object_id === 0 ) {
			$item = 0;
		}
		elseif ( is_object( $right_object_id ) ) {
			$item = 'object';
		}
		elseif ( is_array( $right_object_id ) ) {
			$item = 'array';
		}

		return new WP_Error( 'right_object_id', __( 'cp_add_object_relationship() expects parameter 4 to be a positive integer, ' . $item . ' given.' ) );
	}

	$recognized_relationship_objects = cp_recognized_relationship_objects();
	$object_list = implode( ', ', $recognized_relationship_objects );

	# Error if $left_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $left_object_type, $recognized_relationship_objects ) ) {

		$item = $left_object_type;
		if ( is_int( $left_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $left_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $left_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_type', __( 'cp_add_object_relationship() expects parameter 2 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Error if $right_object_type is not a non-null string of an appropriate value
	if ( ! in_array( $right_object_type, $recognized_relationship_objects ) ) {

		$item = $right_object_type;
		if ( is_int( $right_object_type ) ) {
			$item = 'integer';
		}
		elseif ( is_object( $right_object_type ) ) {
			$item = 'object';
		}
		elseif ( is_array( $right_object_type ) ) {
			$item = 'array';
		}

		return new WP_Error( 'left_object_type', __( 'cp_add_object_relationship() expects parameter 3 to be one of ' . $object_list . ', ' . $item . ' given.' ) );
	}

	# Good to go!
	global $wpdb;
	$table_name = $wpdb->prefix . 'object_relationships';
	$relationship_id = 0;

	# $wpdb->query does not sanitize data, so use $wpdb->prepare
	$sql1 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE left_object_id = %d AND left_object_type = %s AND right_object_type = %s AND right_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

	# Get the relationship ID as an integer
	$row = $wpdb->get_row( $sql1 );
	if ( is_object( $row ) ) {
		$relationship_id = (int) $row->relationship_id;
	}

	if ( ! empty( $relationship_id ) ) {

		# Hook before relationship deleted
		do_action( 'pre_delete_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		# Delete relationship
		$wpdb->delete( $table_name, ['relationship_id' => $relationship_id], ['%d'] );
	}

	else { // nothing deleted so far
		
		$sql2 = $wpdb->prepare( "SELECT relationship_id FROM $table_name WHERE right_object_id = %d AND right_object_type = %s AND left_object_type = %s AND left_object_id = %d", $left_object_id, $left_object_type, $right_object_type, $right_object_id );

		# Get the relationship ID as an integer
		$row = $wpdb->get_row( $sql2 );
		if ( is_object( $row ) ) {
			$relationship_id = (int) $row->relationship_id;
		}

		if ( ! empty( $relationship_id ) ) {

			# Hook before relationship deleted
			do_action( 'pre_delete_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );

			# Delete relationship
			$wpdb->delete( $table_name, ['relationship_id' => $relationship_id], ['%d'] );
		}
	}

	# If a relationship got deleted
	if ( ! empty( $relationship_id ) ) {

		# Hook after relationship deleted
		do_action( 'deleted_object_relationship', $relationship_id, $left_object_id, $left_object_type, $right_object_type, $right_object_id );
	}
}


/* DELETE ALL RELATIONSHIP META WHEN RELATIONSHIP DELETED */
function cp_delete_relationship_meta_when_relationship_deleted( $relationship_id ) {
	$metas = cp_get_relationship_meta( $relationship_id );

	foreach( $metas as $meta_key => $meta_value ) {
		cp_delete_relationship_meta( $relationship_id, $meta_key );
	}
}
add_action( 'deleted_object_relationship', 'cp_delete_relationship_meta_when_relationship_deleted' );


/* META FUNCTIONS */
function cp_add_relationship_meta( $relationship_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value, $unique );
}

function cp_update_relationship_meta( $relationship_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value, $prev_value );
}

function cp_delete_relationship_meta( $relationship_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'object_relationship', $relationship_id, $meta_key, $meta_value );
}

function cp_get_relationship_meta( $relationship_id, $meta_key = '', $single = '' ) {
	return get_metadata( 'object_relationship', $relationship_id, $meta_key, $single );
}
