<?php
/**
 * Revisions administration panel
 *
 * Requires wp-admin/includes/revision.php.
 *
 * @package ClassicPress
 * @subpackage Administration
 * @since 2.6.0
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

require ABSPATH . 'wp-admin/includes/revision.php';

/**
 * @global int    $revision Optional. The revision ID.
 * @global string $action   The action to take.
 *                          Accepts 'restore', 'view' or 'edit'.
 * @global int    $from     The revision to compare from.
 * @global int    $to       Optional, required if revision missing. The revision to compare to.
 */
wp_reset_vars( array( 'revision', 'action', 'from', 'to' ) );

$revision_id = absint( $revision );

$from = is_numeric( $from ) ? absint( $from ) : null;
if ( ! $revision_id ) {
	$revision_id = absint( $to );
}
$redirect = 'edit.php';

switch ( $action ) {
	case 'restore':
		$revision = wp_get_post_revision( $revision_id );
		if ( ! $revision ) {
			break;
		}

		if ( ! current_user_can( 'edit_post', $revision->post_parent ) ) {
			break;
		}

		$post = get_post( $revision->post_parent );
		if ( ! $post ) {
			break;
		}

		// Don't restore if revisions are disabled and this is not an autosave.
		if ( ! wp_revisions_enabled( $post ) && ! wp_is_post_autosave( $revision ) ) {
			$redirect = 'edit.php?post_type=' . $post->post_type;
			break;
		}

		// Don't restore if the post is locked.
		if ( wp_check_post_lock( $post->ID ) ) {
			break;
		}

		check_admin_referer( "restore-post_{$revision->ID}" );

		/*
		 * Ensure the global $post remains the same after revision is restored.
		 * Because wp_insert_post() and wp_transition_post_status() are called
		 * during the process, plugins can unexpectedly modify $post.
		 */
		$backup_global_post = clone $post;

		wp_restore_post_revision( $revision->ID );

		// Restore the global $post as it was before.
		$post = $backup_global_post;

		$redirect = add_query_arg(
			array(
				'message'  => 5,
				'revision' => $revision->ID,
			),
			get_edit_post_link( $post->ID, 'url' )
		);
		break;
	case 'view':
	case 'edit':
	default:
		$revision = wp_get_post_revision( $revision_id );
		if ( ! $revision ) {
			break;
		}

		$post = get_post( $revision->post_parent );
		if ( ! $post ) {
			break;
		}

		if ( ! current_user_can( 'read_post', $revision->ID ) || ! current_user_can( 'edit_post', $revision->post_parent ) ) {
			break;
		}

		// Bail if revisions are disabled and this is not an autosave.
		if ( ! wp_revisions_enabled( $post ) && ! wp_is_post_autosave( $revision ) ) {
			$redirect = 'edit.php?post_type=' . $post->post_type;
			break;
		}

		$post_edit_link = get_edit_post_link();
		$post_title     = '<a href="' . $post_edit_link . '">' . _draft_or_post_title() . '</a>';
		/* translators: %s: Post title. */
		$h1             = sprintf( __( 'Compare Revisions of &#8220;%s&#8221;' ), $post_title );
		$return_to_post = '<a href="' . $post_edit_link . '">' . __( '&larr; Go to editor' ) . '</a>';
		// Used in the HTML title tag.
		$title = __( 'Revisions' );

		$redirect = false;
		break;
}

// Empty post_type means either malformed object found, or no valid parent was found.
if ( ! $redirect && empty( $post->post_type ) ) {
	$redirect = 'edit.php';
}

if ( ! empty( $redirect ) ) {
	wp_redirect( $redirect );
	exit;
}

// This is so that the correct "Edit" menu item is selected.
if ( ! empty( $post->post_type ) && 'post' !== $post->post_type ) {
	$parent_file = 'edit.php?post_type=' . $post->post_type;
} else {
	$parent_file = 'edit.php';
}
$submenu_file = $parent_file;

wp_enqueue_script( 'revisions' );
wp_localize_script( 'revisions', '_wpRevisionsSettings', wp_prepare_revisions_for_js( $post, $revision_id, $from ) );

/* Revisions Help Tab */

$revisions_overview  = '<p>' . __( 'This screen is used for managing your content revisions.' ) . '</p>';
$revisions_overview .= '<p>' . __( 'Revisions are saved copies of your post or page, which are periodically created as you update your content. The red text on the left shows the content that was removed. The green text on the right shows the content that was added.' ) . '</p>';
$revisions_overview .= '<p>' . __( 'From this screen you can review, compare, and restore revisions:' ) . '</p>';
$revisions_overview .= '<ul><li>' . __( 'To navigate between revisions, <strong>drag the slider handle left or right</strong> or <strong>use the Previous or Next buttons</strong>.' ) . '</li>';
$revisions_overview .= '<li>' . __( 'Compare two different revisions by <strong>selecting the &#8220;Compare any two revisions&#8221; box</strong> to the side.' ) . '</li>';
$revisions_overview .= '<li>' . __( 'To restore a revision, <strong>click Restore This Revision</strong>.' ) . '</li></ul>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'revisions-overview',
		'title'   => __( 'Overview' ),
		'content' => $revisions_overview,
	)
);

$revisions_sidebar  = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';
$revisions_sidebar .= '<p>' . __( '<a href="https://wordpress.org/documentation/article/revisions/">Revisions Management</a>' ) . '</p>';
$revisions_sidebar .= '<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>';

get_current_screen()->set_help_sidebar( $revisions_sidebar );

require_once ABSPATH . 'wp-admin/admin-header.php';

/*
 * Get all revisions in ascending order
 *
 * Data tooltip uses special characters that are replaced by JavaScript when the tooltip is shown
 *
 * @since CP-2.1.0
 */
$prepared = wp_prepare_revisions_for_js( $post, $revision_id, $from = null );
$count = count( $prepared['revisionIds'] ) - 1;
$revisions_list = '<input id="revisions-list" value="' . implode( ', ', $prepared['revisionIds'] ) . '" hidden>';

$ticks = '<datalist id="ticks">';
foreach ( $prepared['revisionData'] as $key => $revision ) {
	$avatar = $revision['author']['avatar'];

	$type = __( 'Revision by ' );
	if ( $revision['autosave'] === true ) {
		$type = '{{' . __( 'Autosave by ' );
	} elseif ( $key === $count ) {
		$type = __( 'Current Revision by ' );
	}

	$author = $revision['author']['name'];
	$time_ago = $revision['timeAgo'];

	$date_short = ' (' . $revision['dateShort'] . ')';
	if ( $revision['autosave'] === true ) {
		$date_short = '}} (' . $revision['dateShort'] . ')';
	}

	$ticks .= '<option data-tooltip="' . $type . '[[' . $author . ']]' . $time_ago . $date_short . '" value="' . $key . '">' . $revision['dateShort'] . '</option>';
}
$ticks .= '</datalist>';
?>

<div class="wrap">
	<h1 class="long-header"><?php echo $h1; ?></h1>
	<?php echo $return_to_post; ?>

	<fieldset class="range-container">
		<span id="current-tooltip"></span>
		<div class="sliders-control">
				<?php //echo json_encode( $tooltips ); ?>
				<?php echo $ticks; ?>
			<div class="from-slider-wrapper">
				<label for="from-slider" class="screen-reader-text"><?php esc_html_e( 'Earlier Revision' ); ?></label>
				<input id="from-slider" class="cp-slider" type="range" step="1" min="0" max="<?php echo $count; ?>" list="ticks">
			</div>

			<div class="to-slider-wrapper">
				<label for="to-slider" class="screen-reader-text"><?php esc_html_e( 'Later Revision' ); ?></label>
				<input id="to-slider" class="cp-slider" type="range" step="1" min="0" max="<?php echo $count; ?>" list="ticks">
				<?php echo $revisions_list; ?>
			</div>
		</div>
	</fieldset>
</div>
<?php
wp_print_revision_templates();

require_once ABSPATH . 'wp-admin/admin-footer.php';
