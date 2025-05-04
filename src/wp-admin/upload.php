<?php
/**
 * Media Library administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'upload_files' ) ) {
	wp_die( __( 'Sorry, you are not allowed to upload files.' ) );
}

$message = '';
if ( ! empty( $_GET['posted'] ) ) {
	$message                = __( 'Media file updated.' );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'posted' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['posted'] );
}

if ( ! empty( $_GET['attached'] ) && absint( $_GET['attached'] ) ) {
	$attached = absint( $_GET['attached'] );
	if ( 1 === $attached ) {
		$message = __( 'Media file attached.' );
	} else {
		/* translators: %s: Number of media files. */
		$message = _n( '%s media file attached.', '%s media files attached.', $attached );
	}
	$message                = sprintf( $message, $attached );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['detach'], $_GET['attached'] );
}

if ( ! empty( $_GET['detach'] ) && absint( $_GET['detach'] ) ) {
	$detached = absint( $_GET['detach'] );
	if ( 1 === $detached ) {
		$message = __( 'Media file detached.' );
	} else {
		/* translators: %s: Number of media files. */
		$message = _n( '%s media file detached.', '%s media files detached.', $detached );
	}
	$message                = sprintf( $message, $detached );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'detach', 'attached' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['detach'], $_GET['attached'] );
}

if ( ! empty( $_GET['deleted'] ) && absint( $_GET['deleted'] ) ) {
	$deleted = absint( $_GET['deleted'] );
	if ( 1 === $deleted ) {
		$message = __( 'Media file permanently deleted.' );
	} else {
		/* translators: %s: Number of media files. */
		$message = _n( '%s media file permanently deleted.', '%s media files permanently deleted.', $deleted );
	}
	$message                = sprintf( $message, $deleted );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'deleted' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['deleted'] );
}

if ( ! empty( $_GET['trashed'] ) && absint( $_GET['trashed'] ) ) {
	$trashed = absint( $_GET['trashed'] );
	if ( 1 === $trashed ) {
		$message = __( 'Media file moved to the Trash.' );
	} else {
		/* translators: %s: Number of media files. */
		$message = _n( '%s media file moved to the Trash.', '%s media files moved to the Trash.', $trashed );
	}
	$message                = sprintf( $message, $trashed );
	$message               .= ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids=' . ( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), 'bulk-media' ) ) . '">' . __( 'Undo' ) . '</a>';
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'trashed' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['trashed'] );
}

if ( ! empty( $_GET['untrashed'] ) && absint( $_GET['untrashed'] ) ) {
	$untrashed = absint( $_GET['untrashed'] );
	if ( 1 === $untrashed ) {
		$message = __( 'Media file restored from the Trash.' );
	} else {
		/* translators: %s: Number of media files. */
		$message = _n( '%s media file restored from the Trash.', '%s media files restored from the Trash.', $untrashed );
	}
	$message                = sprintf( $message, $untrashed );
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'untrashed' ), $_SERVER['REQUEST_URI'] );
	unset( $_GET['untrashed'] );
}

$messages[1] = __( 'Media file updated.' );
$messages[2] = __( 'Media file permanently deleted.' );
$messages[3] = __( 'Error saving media file.' );
$messages[4] = __( 'Media file moved to the Trash.' ) . ' <a href="' . esc_url( wp_nonce_url( 'upload.php?doaction=undo&action=untrash&ids=' . ( isset( $_GET['ids'] ) ? $_GET['ids'] : '' ), 'bulk-media' ) ) . '">' . __( 'Undo' ) . '</a>';
$messages[5] = __( 'Media file restored from the Trash.' );

if ( ! empty( $_GET['message'] ) && isset( $messages[ $_GET['message'] ] ) ) {
	$message                = $messages[ $_GET['message'] ];
	$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message' ), $_SERVER['REQUEST_URI'] );
}

$mode  = get_user_option( 'media_library_mode', get_current_user_id() ) ? get_user_option( 'media_library_mode', get_current_user_id() ) : 'grid';
$modes = array( 'grid', 'list' );

if ( isset( $_GET['mode'] ) && in_array( $_GET['mode'], $modes, true ) ) {
	$mode = $_GET['mode'];
	update_user_option( get_current_user_id(), 'media_library_mode', $mode );
}

// Get the maximum upload size.
$max_upload_size = wp_max_upload_size();
if ( ! $max_upload_size ) {
	$max_upload_size = 0;
}

// Get a list of allowed mime types.
$allowed_mimes = get_allowed_mime_types();
$mimes_list    = implode( ',', $allowed_mimes );

wp_enqueue_style( 'cp-filepond-image-preview' );
wp_enqueue_style( 'cp-filepond' );
wp_enqueue_style( 'media-grid' );
wp_enqueue_script( 'media-grid' );

remove_action( 'admin_head', 'wp_admin_canonical_url' );

wp_localize_script(
	'media-grid',
	'_wpMediaLibSettings',
	array(
		'by'               => __( 'by' ),
		'pixels'           => __( 'pixels' ),
		'deselect'         => __( 'Deselect' ),
		'failed_update'    => __( 'Failed to update media:' ),
		'error'            => __( 'Error:' ),
		'upload_failed'    => __( 'Upload failed' ),
		'tap_close'        => __( 'Tap to close' ),
		'new_filename'     => __( 'Enter new filename' ),
		'invalid_type'     => __( 'Invalid file type' ),
		'check_types'      => __( 'Check the list of accepted file types.' ),
		'delete_failed'    => __( 'Failed to delete attachment.' ),
		'confirm_delete'   => __( "You are about to permanently delete this item from your site.\nThis action cannot be undone.\n'Cancel' to stop, 'OK' to delete." ),
		'confirm_multiple' => __( "You are about to permanently delete these items from your site.\nThis action cannot be undone.\n'Cancel' to stop, 'OK' to delete." ),
		'includes_url'     => includes_url(),
		'webp_editable'    => wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ),
		'avif_editable'    => wp_image_editor_supports( array( 'mime_type' => 'image/avif' ) ),
		'heic_editable'    => wp_image_editor_supports( array( 'mime_type' => 'image/heic' ) ),
	)
);

if ( 'grid' === $mode ) {
	// Styles and scripts since CP-2.3.0
	wp_enqueue_style( 'mediaelement-player' );
	wp_enqueue_script( 'wp-mediaelement' );

	add_screen_option(
		'per_page',
		array(
			'label'   => __( 'Number of items per page:' ),
			'default' => get_option( 'posts_per_page' ) ? get_option( 'posts_per_page' ) : 80,
			'option'  => 'media_grid_per_page',
		)
	);

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'overview',
			'title'   => __( 'Overview' ),
			'content' =>
				'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first.' ) . '</p>' .
				'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>' .
				'<p>' . __( 'To delete media items, click the Bulk Select button at the top of the screen. Select any items you wish to delete, then click the Delete Selected button. Clicking the Cancel Selection button takes you back to viewing your media.' ) . '</p>',
		)
	);

	get_current_screen()->add_help_tab(
		array(
			'id'      => 'attachment-details',
			'title'   => __( 'Attachment Details' ),
			'content' =>
				'<p>' . __( 'Clicking an item will display an Attachment Details dialog, which allows you to preview media and make quick edits. Any changes you make to the attachment details will be automatically saved.' ) . '</p>' .
				'<p>' . __( 'Use the arrow buttons at the top of the dialog, the left and right arrow keys on your keyboard, or swipe left and right on a touch device to navigate between media items.' ) . '</p>' .
				'<p>' . __( 'You can also delete individual items and access the extended edit screen from the details dialog.' ) . '</p>',
		)
	);

	get_current_screen()->set_help_sidebar(
		'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
		'<p>' . __( '<a href="https://wordpress.org/documentation/article/media-library-screen/">Documentation on Media Library</a>' ) . '</p>' .
		'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
	);

	// Used in the HTML title tag.
	$title       = __( 'Media Library' );
	$parent_file = 'upload.php';

	// Get the user's preferred items per page.
	$user_id  = get_current_user_id();
	$per_page = get_user_meta( $user_id, 'media_grid_per_page', true );
	if ( empty( $per_page ) || $per_page < 1 ) {
		$per_page = 80;
	}
	// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
	// Fetch media items.
	$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	$attachment_args = array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => $per_page,
		'paged'          => $paged,
	);
	$attachments = new WP_Query( $attachment_args );
	// phpcs:enable
	$total_pages = ( $attachments->max_num_pages ) ? (int) $attachments->max_num_pages : 1;
	$prev_page   = ( $paged === 1 ) ? $paged : $paged - 1;
	$next_page   = ( $paged === $total_pages ) ? $paged : $paged + 1;

	require_once ABSPATH . 'wp-admin/admin-header.php';

	/**
	 * This action is fired before the title is printed to the page.
	 *
	 * @since CP-2.3.0
	 */
	do_action( 'cp_media_before_title' );
	?>
	<div class="wrap" id="wp-media-grid" data-search="<?php _admin_search_query(); ?>">
		<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

		<?php
		/**
		 * This action is fired after the title is printed to the page.
		 *
		 * @since CP-2.3.0
		 */
		do_action( 'cp_media_after_title' );

		if ( current_user_can( 'upload_files' ) ) {
			?>
			<button type="button" id="add-new" class="page-title-action aria-button-if-js" aria-expanded="false"><?php echo esc_html_x( 'Add New', 'file' ); ?></button>
			<?php
			/**
			 * Enable selection of media category.
			 *
			 * @since CP-2.2.0
			 */
			echo cp_select_upload_media_category();

			/**
			 * This action is fired after the media category upload
			 * select dropdown is printed to the page.
			 *
			 * @since CP-2.3.0
			 */
			do_action( 'cp_media_after_select_upload_media_category' );
		}
		?>

		<div class="uploader-inline" data-allowed-mimes="<?php echo esc_attr( $mimes_list ); ?>" hidden inert>
			<button type="button" class="close dashicons dashicons-no">
				<span class="screen-reader-text">Close uploader</span>
			</button>

			<input type="file" id="filepond" class="filepond" name="filepond" multiple data-allow-reorder="true">
			<input id="ajax-url" value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-max-file-size="<?php echo esc_attr( size_format( $max_upload_size ) ); ?>" hidden>
			<?php wp_nonce_field( 'media-form' ); ?>

			<div class="post-upload-ui" id="post-upload-info">
				<p class="max-upload-size">

					<?php
					/* translators: %s: Maximum allowed file size. */
					printf( __( 'Maximum upload file size: %s.' ), esc_html( size_format( $max_upload_size ) ) );
					?>

				</p>
			</div>
		</div>

		<hr class="wp-header-end">

		<?php
		if ( ! empty( $message ) ) {
			wp_admin_notice(
				$message,
				array(
					'id'                 => 'message',
					'additional_classes' => array( 'updated' ),
					'dismissible'        => true,
				)
			);
		}

		$js_required_message = sprintf(
			/* translators: %s: List view URL. */
			__( 'The grid view for the Media Library requires JavaScript. <a href="%s">Switch to the list view</a>.' ),
			'upload.php?mode=list'
		);
		wp_admin_notice(
			$js_required_message,
			array(
				'additional_classes' => array( 'error', 'hide-if-js' ),
			)
		);
		?>

		<div class="cp-media-toolbar wp-filter" style="margin-bottom:0">
			<div class="media-toolbar-secondary">
				<h2 class="media-attachments-filter-heading screen-reader-text"><?php esc_html_e( 'Filter media' ); ?></h2>
				<div class="view-switch media-grid-view-switch">
					<a href="<?php echo admin_url( 'upload.php?mode=list' ); ?>" class="view-list">
						<span class="screen-reader-text"><?php esc_html_e( 'List view' ); ?></span>
					</a>
					<a href="<?php echo admin_url( 'upload.php?mode=grid' ); ?>" class="view-grid current" aria-current="page">
						<span class="screen-reader-text"><?php esc_html_e( 'Grid view' ); ?></span>
					</a>
				</div>

				<?php
				/* This function is documented in wp-admin/includes/media.php */
				echo cp_media_filters();
				?>

				<div class="media-toolbar-primary search-form">
					<label for="media-search-input"><?php esc_html_e( 'Search' ); ?></label>
					<input type="search" id="media-search-input" name="s" class="search" value="">
				</div>

				<?php wp_nonce_field( 'media_grid', 'media_grid_nonce' ); ?>

			</div>
		</div>

		<?php
		/**
		 * This action is fired before pagination is printed to the page.
		 *
		 * @since CP-2.3.0
		 */
		do_action( 'cp_media_before_pagination' );
		?>

		<div class="tablenav top">
			<div class="alignleft actions"></div>
			<h2 class="screen-reader-text"><?php esc_html_e( 'Media items navigation' ); ?></h2>
				<div class="tablenav-pages">
					<span class="displaying-num">

						<?php
						/* translators: %s: Number of media items showing */
						printf( __( '%s items' ), esc_html( count( $attachments->posts ) ) );
						?>

					</span>
					<span class="pagination-links">
						<a class="first-page button" href="<?php echo admin_url( '/upload.php?paged=1' ); ?>"
							<?php
							if ( $paged === 1 ) {
								echo 'disabled inert';
							}
							?>
						>
							<span class="screen-reader-text"><?php esc_html_e( 'First page' ); ?></span><span aria-hidden="true">«</span>
						</a>
						<a class="prev-page button" href="<?php echo admin_url( '/upload.php?paged=' . $prev_page ); ?>"
							<?php
							if ( $paged === 1 ) {
								echo 'disabled inert';
							}
							?>
						>
							<span class="screen-reader-text"><?php esc_html_e( 'Previous page' ); ?></span><span aria-hidden="true">‹</span>
						</a>
						<span class="paging-input">
							<label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Current Page' ); ?></label>
							<input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo esc_attr( $paged ); ?>" size="4" aria-describedby="table-paging">
							<span id="table-paging" class="tablenav-paging-text"> <?php esc_html_e( 'of' ); ?> <span class="total-pages"><?php echo esc_html( $total_pages ); ?></span></span>
						</span>
						<a class="next-page button" href="<?php echo admin_url( '/upload.php?paged=' . $next_page ); ?>"
							<?php
							if ( $paged === $next_page ) {
								echo 'disabled inert';
							}
							?>
						>
							<span class="screen-reader-text"><?php esc_html_e( 'Next page' ); ?></span><span aria-hidden="true">›</span>
						</a>
						<a class="last-page button" href="<?php echo admin_url( '/upload.php?paged=' . $total_pages ); ?>"
							<?php
							if ( $paged === $next_page ) {
								echo 'disabled inert';
							}
							?>
						>
							<span class="screen-reader-text"><?php esc_html_e( 'Last page' ); ?></span><span aria-hidden="true">»</span>
						</a>
					</span>
				</div>
			<br class="clear">
		</div>

		<?php
		/**
		 * This action is fired before the media grid is printed to the page.
		 *
		 * @since CP-2.3.0
		 */
		do_action( 'cp_media_before_grid' );
		?>

		<div id="media-grid">
			<ul class="media-grid-view">

				<?php
				foreach ( $attachments->posts as $key => $attachment ) {
					$meta         = wp_prepare_attachment_for_js( $attachment->ID );
					$date         = $meta['dateFormatted'];
					$author       = $meta['authorName'];
					$author_link  = ! empty( $meta['authorLink'] ) ? $meta['authorLink'] : '';
					$url          = $meta['url'];
					$width        = ! empty( $meta['width'] ) ? $meta['width'] : '';
					$height       = ! empty( $meta['height'] ) ? $meta['height'] : '';
					$file_name    = $meta['filename'];
					$file_type    = $meta['type'];
					$subtype      = $meta['subtype'];
					$mime_type    = $meta['mime'];
					$size         = ! empty( $meta['filesizeHumanReadable'] ) ? $meta['filesizeHumanReadable'] : '';
					$alt          = $meta['alt'];
					$caption      = $meta['caption'];
					$description  = $meta['description'];
					$link         = $meta['link'];
					$orientation  = ! empty( $meta['orientation'] ) ? $meta['orientation'] : 'landscape';
					$menu_order   = $meta['menuOrder'];
					$media_cats   = $meta['media_cats'] ? implode( ', ', $meta['media_cats'] ) : '';
					$media_tags   = $meta['media_tags'] ? implode( ', ', $meta['media_tags'] ) : '';
					$update_nonce = $meta['nonces']['update'];
					$delete_nonce = $meta['nonces']['delete'];
					$edit_nonce   = $meta['nonces']['edit'];
					$image        = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '">';

					// Use an icon if the file uploaded is not an image
					if ( $file_type === 'application' ) {
						if ( $subtype === 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) {
							$image = '<div class="icon"><div class="centered"><img src="' . esc_url( includes_url() . 'images/media/spreadsheet.png' ) . '" draggable="false" alt=""></div><div class="filename"><div>' . esc_html( $attachment->post_title ) . '</div></div></div>';
						} elseif ( $subtype === 'zip' ) {
							$image = '<div class="icon"><div class="centered"><img src="' . esc_url( includes_url() . 'images/media/archive.png' ) . '" draggable="false" alt=""></div><div class="filename"><div>' . esc_html( $attachment->post_title ) . '</div></div></div>';
						} else {
							$image = '<div class="icon"><div class="centered"><img src="' . esc_url( includes_url() . 'images/media/document.png' ) . '" draggable="false" alt=""></div><div class="filename"><div>' . esc_html( $attachment->post_title ) . '</div></div></div>';
						}
					} elseif ( $file_type === 'audio' ) {
						$image = '<div class="icon"><div class="centered"><img src="' . esc_url( includes_url() . 'images/media/audio.png' ) . '" draggable="false" alt=""></div><div class="filename"><div>' . esc_html( $attachment->post_title ) . '</div></div></div>';
					} elseif ( $file_type === 'video' ) {
						$image = '<div class="icon"><div class="centered"><img src="' . esc_url( includes_url() . 'images/media/video.png' ) . '" draggable="false" alt=""></div><div class="filename"><div>' . esc_html( $attachment->post_title ) . '</div></div></div>';
					}
					?>

					<li class="media-item" id="media-<?php echo esc_attr( $attachment->ID ); ?>" tabindex="0" role="checkbox" aria-checked="false"
						aria-label="<?php echo esc_attr( $attachment->post_title ); ?>"
						data-id ="<?php echo esc_attr( $attachment->ID ); ?>"
						data-date="<?php echo esc_attr( $date ); ?>"
						data-url="<?php echo esc_url( $url ); ?>"
						data-filename="<?php echo esc_attr( $file_name ); ?>"
						data-filetype="<?php echo esc_attr( $file_type ); ?>"
						data-mime="<?php echo esc_attr( $mime_type ); ?>"
						data-width="<?php echo esc_attr( $width ); ?>"
						data-height="<?php echo esc_attr( $height ); ?>"
						data-size="<?php echo esc_attr( $size ); ?>"
						data-caption="<?php echo esc_attr( $caption ); ?>"
						data-description="<?php echo esc_attr( $description ); ?>"
						data-link="<?php echo esc_attr( $link ); ?>"
						data-author="<?php echo esc_attr( $author ); ?>"
						data-author-link="<?php echo esc_attr( $author_link ); ?>"
						data-orientation="<?php echo esc_attr( $orientation ); ?>"
						data-menu-order="<?php echo esc_attr( $menu_order ); ?>"
						data-taxes="<?php echo esc_attr( $media_cats ); ?>"
						data-tags="<?php echo esc_attr( $media_tags ); ?>"
						data-order="<?php echo esc_attr( $key + 1 ); ?>"
						data-update-nonce="<?php echo $update_nonce; ?>"
						data-delete-nonce="<?php echo $delete_nonce; ?>"
						data-edit-nonce="<?php echo $edit_nonce; ?>"
						>

						<div class="select-attachment-preview <?php echo esc_attr( 'type-' . $file_type . ' subtype-' . $subtype . ' ' . $orientation ); ?>">
							<div class="media-thumbnail">
								<?php echo $image; ?>
							</div>
						</div>
						<button type="button" class="check" tabindex="-1">
							<span class="media-modal-icon"></span>
							<span class="screen-reader-text"><?php esc_html_e( 'Deselect' ); ?></span>
						</button>
					</li>

					<?php
				}
				?>

			</ul>
			<div class="load-more-wrapper">
				<p class="load-more-count">

					<?php
					/* translators: %s: 1. Number of media items showing 2. Total number of media items. */
					printf( __( 'Showing %s of %s media items' ), esc_html( count( $attachments->posts ) ), esc_html( $attachments->found_posts ) );
					?>

				</p>
				<p class="no-media" hidden>
					<?php esc_html_e( 'No media items found.' ); ?>
				</p>
			</div>
		</div>
	</div>

	<?php
	/**
	 * This action is fired after the media grid is printed to the page.
	 *
	 * @since CP-2.3.0
	 */
	do_action( 'cp_media_after_grid' );
	?>

	<!-- Modal markup -->
	<dialog id="media-modal" class="media-modal wp-core-ui file-details-modal">
		<div class="media-modal-content">

			<div class="edit-attachment-frame mode-select hide-menu hide-router">
				<div class="edit-media-header">
					<div class="media-navigation">
						<button type="button" id="left-dashicon" class="left dashicons">
							<span class="screen-reader-text"><?php esc_html_e( 'Edit previous media item' ); ?></span>
						</button>
						<div class="media-contextual-pagination"><span id="current-media-item" aria-hidden="true"></span>&nbsp;/&nbsp;<span id="total-media-items"></span></div>
						<button type="button" id="right-dashicon" class="right dashicons">
							<span class="screen-reader-text"><?php esc_html_e( 'Edit next media item' ); ?></span>
						</button>
					</div>
					<button type="button" id="dialog-close-button" class="dashicons-no dashicons" autofocus>
						<span class="screen-reader-text"><?php esc_html_e( 'Close dialog' ); ?></span>
					</button>
				</div>
				<div class="media-frame-title">
					<h2><?php esc_html_e( 'Attachment details' ); ?></h2>
				</div>
				<div class="media-frame-content">
					<div class="attachment-details save-ready">
						<div class="attachment-media-view">
							<h3 class="screen-reader-text"><?php esc_html_e( 'Attachment Preview' ); ?></h3>
							<div class="media-navigation" aria-label="<?php esc_html_e( 'Media Navigation' ); ?>">
								<button type="button" id="left-dashicon-mobile" class="left dashicons">
									<span class="screen-reader-text"><?php esc_html_e( 'Edit previous media item' ); ?></span>
								</button>
								<div class="media-contextual-pagination"><span id="current-media-item-mobile" aria-hidden="true"></span>&nbsp;/&nbsp;<span id="total-media-items-mobile"></span></div>
								<button type="button" id="right-dashicon-mobile" class="right dashicons">
									<span class="screen-reader-text"><?php esc_html_e( 'Edit next media item' ); ?></span>
								</button>
							</div>
							<div id="media-image" class="thumbnail thumbnail-image">
								<img class="details-image" src="" draggable="false" alt="">
								<div class="attachment-actions">
									<button type="button" class="button edit-attachment"><?php esc_html_e( 'Edit Image' ); ?></button>
								</div>
							</div>
							<div id="media-audio" class="thumbnail" hidden>
								<?php
								// Uses a blank audio file as a placeholder
								echo wp_audio_shortcode( array( 'src' => includes_url() . 'js/mediaelement/blank.mp3' ) );
								?>
							</div>
							<div id="media-video" class="thumbnail" hidden>
								<?php
								// Uses a blank video file as a placeholder
								echo wp_video_shortcode( array( 'src' => includes_url() . 'js/mediaelement/blank.mp4' ) );
								?>
							</div>
						</div>
						<div class="attachment-info">
							<div class="details">
								<h3 class="screen-reader-text"><?php esc_html_e( 'Details' ); ?></h3>
								<div class="uploaded"><strong><?php esc_html_e( 'Uploaded on:' ); ?></strong> <span class="attachment-date"></div>
								<div class="uploaded-by">
									<strong><?php esc_html_e( 'Uploaded by:' ); ?></strong> <a href=""></a>
								</div>

								<div class="filename"><strong><?php esc_html_e( 'File name:' ); ?></strong> <span class="attachment-filename"></span></div>
								<div class="file-type"><strong><?php esc_html_e( 'File type:' ); ?></strong> <span class="attachment-filetype"></span></div>
								<div class="file-size"><strong><?php esc_html_e( 'File size:' ); ?></strong> <span class="attachment-filesize"></div>
								<div class="dimensions"><strong><?php esc_html_e( 'Dimensions:' ); ?></strong> <span class="attachment-dimensions"></div>

								<div class="compat-meta"></div>
							</div>

							<?php
							/**
							 * This action is fired after the details list
							 * within the dialog modal is printed to the page.
							 *
							 * @since CP-2.3.0
							 */
							do_action( 'cp_media_modal_after_details' );
							?>

							<div class="settings">
								<span class="setting alt-text has-description" data-setting="alt">
									<label for="attachment-details-two-column-alt-text" class="name"><?php esc_html_e( 'Alternative Text' ); ?></label>
									<textarea id="attachment-details-two-column-alt-text" aria-describedby="alt-text-description"></textarea>
								</span>
								<p class="description" id="alt-text-description"><a href="https://www.w3.org/WAI/tutorials/images/decision-tree" target="_blank" rel="noopener"><?php esc_html_e( 'Learn how to describe the purpose of the image' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)' ); ?></span></a><?php esc_html_e( '. Leave empty if the image is purely decorative.' ); ?></p>
								<span class="setting" data-setting="title">
									<label for="attachment-details-two-column-title" class="name"><?php esc_html_e( 'Title' ); ?></label>
									<input type="text" id="attachment-details-two-column-title" value="">
								</span>
								<span class="setting settings-save-status" role="status">
									<span id="details-saved" class="success hidden" aria-hidden="true"><?php esc_html_e( 'Saved!' ); ?></span>
								</span>
								<span class="setting" data-setting="caption">
									<label for="attachment-details-two-column-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
									<textarea id="attachment-details-two-column-caption"></textarea>
								</span>
								<span class="setting" data-setting="description">
									<label for="attachment-details-two-column-description" class="name"><?php esc_html_e( 'Description' ); ?></label>
									<textarea id="attachment-details-two-column-description"></textarea>
								</span>
								<span class="setting" data-setting="url">
									<label for="attachment-details-two-column-copy-link" class="name"><?php esc_html_e( 'File URL' ); ?></label>
									<input type="text" class="attachment-details-copy-link" id="attachment-details-two-column-copy-link" value="" readonly="">
									<span class="copy-to-clipboard-container">
										<button type="button" class="button button-small copy-attachment-url media-library" data-clipboard-target="#attachment-details-two-column-copy-link"><?php esc_html_e( 'Copy URL to clipboard' ); ?></button>
										<span class="success hidden" aria-hidden="true"><?php esc_html_e( 'Copied!' ); ?></span>
									</span>
								</span>

								<?php
								/**
								 * This action is fired before the inputs
								 * and textareas within the dialog modal
								 * are printed to the page.
								 *
								 * @since CP-2.3.0
								 */
								do_action( 'cp_media_modal_before_media_menu_order' );
								?>

								<div class="attachment-compat"></div>
								<span class="setting settings-save-status" role="status">
									<span id="tax-saved" class="success hidden" aria-hidden="true"><?php esc_html_e( 'Taxonomy updated successfully!' ); ?></span>
								</span>

								<?php
								/**
								 * This action is fired after the post tags
								 * list within the dialog modal is printed
								 * to the page.
								 *
								 * @since CP-2.3.0
								 */
								do_action( 'cp_media_modal_after_media_post_tags' );
								?>

							</div>
							<div class="actions">
								<a id="view-attachment" class="view-attachment" href=""><?php esc_html_e( 'View attachment page' ); ?></a>
								<span class="links-separator">|</span>
								<a id="edit-more" href=""><?php esc_html_e( 'Edit more details' ); ?></a>
								<span class="links-separator">|</span>
								<a id="download-file" href="" download=""><?php esc_html_e( 'Download file' ); ?></a>
								<span class="links-separator">|</span>
								<button type="button" class="button-link delete-attachment"><?php esc_html_e( 'Delete permanently' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</dialog>

	<?php
	require_once ABSPATH . 'wp-admin/admin-footer.php';
	exit;
}

$wp_list_table = _get_list_table( 'WP_Media_List_Table' );
$pagenum       = $wp_list_table->get_pagenum();

// Handle bulk actions.
$doaction = $wp_list_table->current_action();

if ( $doaction ) {
	check_admin_referer( 'bulk-media' );

	$post_ids = array();

	if ( 'delete_all' === $doaction ) {
		$post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND post_status = 'trash'" );
		$doaction = 'delete';
	} elseif ( isset( $_REQUEST['media'] ) ) {
		$post_ids = $_REQUEST['media'];
	} elseif ( isset( $_REQUEST['ids'] ) ) {
		$post_ids = explode( ',', $_REQUEST['ids'] );
	}
	$post_ids = array_map( 'intval', (array) $post_ids );

	$location = 'upload.php';
	$referer  = wp_get_referer();
	if ( $referer ) {
		if ( str_contains( $referer, 'upload.php' ) ) {
			$location = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'message', 'ids', 'posted' ), $referer );
		}
	}

	switch ( $doaction ) {
		case 'detach':
			wp_media_attach_action( $_REQUEST['parent_post_id'], 'detach' );
			break;

		case 'attach':
			wp_media_attach_action( $_REQUEST['found_post_id'] );
			break;

		case 'edit':
			if ( empty( $post_ids ) ) {
				break;
			}
			foreach ( $post_ids as $post_id ) {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					wp_die( __( 'Sorry, you are not allowed to edit this item.' ) );
				}
			}
			bulk_edit_attachments( $_REQUEST );
			break;

		case 'trash':
			if ( empty( $post_ids ) ) {
				break;
			}
			foreach ( $post_ids as $post_id ) {
				if ( ! current_user_can( 'delete_post', $post_id ) ) {
					wp_die( __( 'Sorry, you are not allowed to move this item to the Trash.' ) );
				}

				if ( ! wp_trash_post( $post_id ) ) {
					wp_die( __( 'Error in moving the item to Trash.' ) );
				}
			}
			$location = add_query_arg(
				array(
					'trashed' => count( $post_ids ),
					'ids'     => implode( ',', $post_ids ),
				),
				$location
			);
			break;
		case 'untrash':
			if ( empty( $post_ids ) ) {
				break;
			}
			foreach ( $post_ids as $post_id ) {
				if ( ! current_user_can( 'delete_post', $post_id ) ) {
					wp_die( __( 'Sorry, you are not allowed to restore this item from the Trash.' ) );
				}

				if ( ! wp_untrash_post( $post_id ) ) {
					wp_die( __( 'Error in restoring the item from Trash.' ) );
				}
			}
			$location = add_query_arg( 'untrashed', count( $post_ids ), $location );
			break;
		case 'delete':
			if ( empty( $post_ids ) ) {
				break;
			}
			foreach ( $post_ids as $post_id_del ) {
				if ( ! current_user_can( 'delete_post', $post_id_del ) ) {
					wp_die( __( 'Sorry, you are not allowed to delete this item.' ) );
				}

				if ( ! wp_delete_attachment( $post_id_del ) ) {
					wp_die( __( 'Error in deleting the attachment.' ) );
				}
			}
			$location = add_query_arg( 'deleted', count( $post_ids ), $location );
			break;
		default:
			$screen = get_current_screen()->id;

			/** This action is documented in wp-admin/edit.php */
			$location = apply_filters( "handle_bulk_actions-{$screen}", $location, $doaction, $post_ids ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	wp_redirect( $location );
	exit;
} elseif ( ! empty( $_GET['_wp_http_referer'] ) ) {
	wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
	exit;
}

$wp_list_table->prepare_items();

// Used in the HTML title tag.
$title       = __( 'Media Library' );
$parent_file = 'upload.php';

wp_enqueue_script( 'media' );

add_screen_option( 'per_page' );

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' =>
				'<p>' . __( 'All the files you&#8217;ve uploaded are listed in the Media Library, with the most recent uploads listed first. You can use the Screen Options tab to customize the display of this screen.' ) . '</p>' .
				'<p>' . __( 'You can narrow the list by file type/status or by date using the dropdown menus above the media table.' ) . '</p>' .
				'<p>' . __( 'You can view your media in a simple visual grid or a list with columns. Switch between these views using the icons to the left above the media.' ) . '</p>',
	)
);
get_current_screen()->add_help_tab(
	array(
		'id'      => 'actions-links',
		'title'   => __( 'Available Actions' ),
		'content' =>
				'<p>' . __( 'Hovering over a row reveals action links that allow you to manage media items. You can perform the following actions:' ) . '</p>' .
				'<ul>' .
					'<li>' . __( '<strong>Edit</strong> takes you to a simple screen to edit that individual file&#8217;s metadata. You can also reach that screen by clicking on the media file name or thumbnail.' ) . '</li>' .
					'<li>' . __( '<strong>Delete Permanently</strong> will delete the file from the media library (as well as from any posts to which it is currently attached).' ) . '</li>' .
					'<li>' . __( '<strong>View</strong> will take you to a public display page for that file.' ) . '</li>' .
					'<li>' . __( '<strong>Copy URL</strong> copies the URL for the media file to your clipboard.' ) . '</li>' .
					'<li>' . __( '<strong>Download file</strong> downloads the original media file to your device.' ) . '</li>' .
				'</ul>',
	)
);
get_current_screen()->add_help_tab(
	array(
		'id'      => 'attaching-files',
		'title'   => __( 'Attaching Files' ),
		'content' =>
				'<p>' . __( 'If a media file has not been attached to any content, you will see that in the Uploaded To column, and can click on Attach to launch a small popup that will allow you to search for existing content and attach the file.' ) . '</p>',
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/media-library-screen/">Documentation on Media Library</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

get_current_screen()->set_screen_reader_content(
	array(
		'heading_views'      => __( 'Filter media items list' ),
		'heading_pagination' => __( 'Media items list navigation' ),
		'heading_list'       => __( 'Media items list' ),
	)
);

require_once ABSPATH . 'wp-admin/admin-header.php';

/**
	* This action is fired before the title is printed to the page.
	*
	* @since CP-2.5.0
	*/
do_action( 'cp_media_before_title' );
?>

<div class="wrap">
<h1 class="wp-heading-inline"><?php echo esc_html( $title ); ?></h1>

<?php
/**
	* This action is fired after the title is printed to the page.
	*
	* @since CP-2.5.0
	*/
do_action( 'cp_media_after_title' );

if ( current_user_can( 'upload_files' ) ) {
	?>
	<a href="<?php echo esc_url( admin_url( 'media-new.php' ) ); ?>" class="page-title-action"><?php echo esc_html_x( 'Add New', 'file' ); ?></a>
	<?php
	/**
	 * Enable selection of media category.
	 *
	 * @since CP-2.2.0
	 */
	echo cp_select_upload_media_category();

	/**
		* This action is fired after the media category upload
		* select dropdown is printed to the page.
		*
		* @since CP-2.5.0
		*/
	do_action( 'cp_media_after_select_upload_media_category' );
}

if ( isset( $_REQUEST['s'] ) && strlen( $_REQUEST['s'] ) ) {
	echo '<span class="subtitle">';
	printf(
		/* translators: %s: Search query. */
		__( 'Search results for: %s' ),
		'<strong>' . get_search_query() . '</strong>'
	);
	echo '</span>';
}
?>

<hr class="wp-header-end">

<?php
if ( ! empty( $message ) ) {
	wp_admin_notice(
		$message,
		array(
			'id'                 => 'message',
			'additional_classes' => array( 'updated' ),
			'dismissible'        => true,
		)
	);
}
?>

<form id="posts-filter" method="get">

<?php $wp_list_table->views(); ?>

<?php $wp_list_table->display(); ?>

<div id="ajax-response"></div>
<?php find_posts_div(); ?>
</form>

<?php
/**
	* This action is fired before the media list is printed to the page.
	*
	* @since CP-2.5.0
	*/
do_action( 'cp_media_before_list' );

if ( $wp_list_table->has_items() ) {
	$wp_list_table->inline_edit();
}
?>
</div>

<?php
/**
	* This action is fired after the media list is printed to the page.
	*
	* @since CP-2.5.0
	*/
do_action( 'cp_media_after_list' );

require_once ABSPATH . 'wp-admin/admin-footer.php';
