<?php

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

// Clean up URL
$_SERVER['REQUEST_URI'] = remove_query_arg( array( '_wp_http_referer' ), $_SERVER['REQUEST_URI'] );

// Check permissions, nonce, and validate input!
$post_id = 0;
if ( isset( $_GET['post_parent'] ) ) {
	$post_id = absint( $_GET['post_parent'] );
}

if ( $post_id === 0 ) {
	wp_die( __( 'No post.' ) );
}

if ( ! current_user_can( 'read_post', $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( 'delete_post', $post_id ) ) {
	wp_die( __( 'Sorry, you are not allowed to review these revisions.' ) );
}

// Set variable for later check for messages
$revision_id = 0;

// Set up CP_Post_Revisions_List_Table class
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once ABSPATH . 'wp-admin/includes/class-cp-post-revisions-list-table.php';
$list_table = new CP_Post_Revisions_List_Table();

// Verify nonce
if ( empty( $_GET['_wpnonce'] ) ) {
	wp_die( __( 'You cannot do this.' ) );
}

if ( ! empty( $_GET['action'] ) ) {
	if ( $_GET['action'] === 'delete' && ! empty( $_GET['revision_id'] ) ) { // delete individual revision
		$revision_id = $_GET['revision_id'];
		if ( absint( $revision_id ) === 0 ) {
			wp_die( __( 'No revision ID.' ) );
		}

		// Delete specific revision
		check_admin_referer( 'delete_revision_' . $revision_id ); // check nonce
		wp_delete_post_revision( $revision_id );
	} elseif ( $_GET['action'] === 'bulk-delete' && ! empty( $_GET['revision_ids'] ) ) { // bulk delete revisions
		$revision_ids = array_map( 'absint', $_GET['revision_ids'] );
		if ( in_array( 0, $revision_ids, true ) ) {
			wp_die( __( 'Incorrect revision ID.' ) );
		}

		// Process bulk deletions
		check_admin_referer( 'bulk-revisions-list' ); // check nonce
		$list_table->process_bulk_action();
	}
} else { // load page after following link from revision.php
	check_admin_referer( 'revisions-list' ); // check nonce
}

// Prepare items for display after processing
$list_table->prepare_items();

// Used in the HTML title tag.
$title = esc_html__( 'Revisions List' );

// Get last revision
$revisions = wp_get_post_revisions( $post_id );
$last_revision = reset( $revisions );

// This is so that the correct "Edit" menu item is selected.
$post = get_post( $post_id );
if ( ! empty( $post->post_type ) && 'post' !== $post->post_type ) {
	$parent_file = 'edit.php?post_type=' . $post->post_type;
} else {
	$parent_file = 'edit.php';
}
$submenu_file = $parent_file;

wp_enqueue_script( 'revisions-list' );

/* Revisions Help Tab */
$revisions_overview  = '<p>' . __( 'This screen enables the deletion of unwanted content revisions. Note that, however many revisions you delete, the post itself will not be deleted.' ) . '</p>';
$revisions_overview .= '<p>' . __( 'Revisions are saved copies of your post or page, which are periodically created as you update your content.' ) . '</p>';
$revisions_overview .= '<p>' . __( 'From this screen you can review a revision by clicking on the corresponding View button.' ) . '</p>';
$revisions_overview .= '<p>' . __( 'To delete a revision, click on the corresponding Delete link.' ) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $revisions_overview,
	)
);

get_current_screen()->add_help_tab(
	array(
		'id'      => 'bulk-actions',
		'title'   => __( 'Bulk actions' ),
		'content' => '<p>' . __( 'You can also delete multiple revisions at once. Select the revisions you want to act on using the checkboxes, then select the delete action from the Bulk actions menu and click Apply.' ) . '</p>',
	)
);

$revisions_sidebar  = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';
$revisions_sidebar .= '<p>' . __( '<a href="https://wordpress.org/documentation/article/revisions/">Revisions Management</a>' ) . '</p>';
$revisions_sidebar .= '<p>' . __( '<a href="https://forums.classicpress.net/c/support/">Support forums</a>' ) . '</p>';

get_current_screen()->set_help_sidebar( $revisions_sidebar );

// Enable pagination of revisions list
add_screen_option(
	'per_page',
	array(
		'default' => 20,
		'option'  => 'revision_' . $post->post_type . '_per_page',
	)
);

$bulk_counts = array(
	'deleted' => isset( $_GET['revision_ids'] ) ? count( array_map( 'absint', $_GET['revision_ids'] ) ) : 0,
);

$bulk_messages         = array();
$bulk_messages['post'] = array(
	/* translators: %s: Number of posts. */
	'deleted' => _n( '%s revision permanently deleted.', '%s revisions permanently deleted.', $bulk_counts['deleted'] ),
);
$bulk_messages['page'] = array(
	/* translators: %s: Number of pages. */
	'deleted' => _n( '%s revision permanently deleted.', '%s revisions permanently deleted.', $bulk_counts['deleted'] ),
);

/**
 * Filters the bulk action updated messages.
 *
 * By default, custom post types use the messages for the 'post' post type.
 *
 * @since CP-2.6.0
 *
 * @param array[] $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
 *                               keyed with 'deleted'.
 * @param int[]   $bulk_counts   Array of item counts for each message, used to build internationalized strings.
 */
$bulk_messages = apply_filters( 'bulk_revision_updated_messages', $bulk_messages, $bulk_counts );
$bulk_counts   = array_filter( $bulk_counts );

// Output admin header, page markup, form, and hidden input fields
require_once ABSPATH . 'wp-admin/admin-header.php';

// If we have a bulk message to issue:
$messages = array();
foreach ( $bulk_counts as $message => $count ) {
	if ( isset( $bulk_messages[ $post->post_type ][ $message ] ) ) {
		$messages[] = sprintf( $bulk_messages[ $post->post_type ][ $message ], number_format_i18n( $count ) );
	} elseif ( isset( $bulk_messages['post'][ $message ] ) ) {
		$messages[] = sprintf( $bulk_messages['post'][ $message ], number_format_i18n( $count ) );
	}
}

if ( $messages ) {
	wp_admin_notice(
		implode( ' ', $messages ),
		array(
			'id'                 => 'message',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
}
unset( $messages );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'List of Revisions of ' ); ?>“<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) ); ?>"><?php esc_html_e( get_post( $post_id )->post_title ); ?></a>”</h1>
	<div class="return-to-post">
		<a href="<?php echo esc_url( admin_url( 'revision.php?revision=' . $last_revision->ID ) ); ?>"><?php esc_html_e( '&larr; Go to revisions' ); ?></a>
	</div>

	<hr class="wp-header-end">

	<?php
	if ( $revision_id !== 0 ) {
		echo '<div class="notice notice-success is-dismissible">';
		echo sprintf( '<p>' . __( 'Revision ID %1$d successfully deleted.' ) . '</p>', $revision_id );
		echo '</div>';
	}
	?>

	<form method="get">
		<input type="hidden" name="post_parent" value="<?php esc_attr_e( $post_id ); ?>">
		<?php $list_table->display(); ?>
	</form>
</div>

<?php
require_once ABSPATH . 'wp-admin/admin-footer.php';
