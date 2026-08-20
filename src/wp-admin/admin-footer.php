<?php
/**
 * ClassicPress Administration Template Footer
 *
 * @package ClassicPress
 * @subpackage Administration
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * @global string $hook_suffix
 */
global $hook_suffix;
?>

<div class="clear"></div></div><!-- wpbody-content -->
<div class="clear"></div></div><!-- wpbody -->
<div class="clear"></div></div><!-- wpcontent -->

<div id="wpfooter" role="contentinfo">
	<?php
	/**
	 * Fires after the opening tag for the admin footer.
	 *
	 * @since 2.5.0
	 */
	do_action( 'in_admin_footer' );
	?>
	<p id="footer-left" class="alignleft">
		<?php
		$text = sprintf(
			/* translators: %s: https://www.classicpress.net/ */
			__( 'Thank you for creating with <a href="%s">ClassicPress</a>.' ),
			__( 'https://www.classicpress.net/' )
		);

		/**
		 * Filters the "Thank you" text displayed in the admin footer.
		 *
		 * @since 2.8.0
		 *
		 * @param string $text The content that will be printed.
		 */
		echo apply_filters( 'admin_footer_text', '<span id="footer-thankyou">' . $text . '</span>' );
		?>
	</p>
	<p id="footer-upgrade" class="alignright">
		<?php
		/**
		 * Filters the version/update text displayed in the admin footer.
		 *
		 * ClassicPress prints the current version and update information,
		 * using core_update_footer() at priority 10.
		 *
		 * @since 2.3.0
		 *
		 * @see core_update_footer()
		 *
		 * @param string $content The content that will be printed.
		 */
		echo apply_filters( 'update_footer', '' );
		?>
	</p>
	<div class="clear"></div>
</div>
<?php
/**
 * Prints scripts or data before the default footer scripts.
 *
 * @since 1.2.0
 *
 * @param string $data The data to print.
 */
do_action( 'admin_footer', '' );

/**
 * Prints scripts and data queued for the footer.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 4.6.0
 */
do_action( "admin_print_footer_scripts-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

/**
 * Prints any scripts and data queued for the footer.
 *
 * @since 2.8.0
 */
do_action( 'admin_print_footer_scripts' );

/**
 * Prints scripts or data after the default footer scripts.
 *
 * The dynamic portion of the hook name, `$hook_suffix`,
 * refers to the global hook suffix of the current page.
 *
 * @since 2.8.0
 */
do_action( "admin_footer-{$hook_suffix}" ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

// get_site_option() won't exist when auto upgrading from <= 2.7.
if ( function_exists( 'get_site_option' )
	&& false === get_site_option( 'can_compress_scripts' )
) {
	compression_test();
}

// Get the maximum upload size.
$max_upload_size = wp_max_upload_size();
if ( ! $max_upload_size ) {
	$max_upload_size = 0;
}

// Get a list of allowed mime types.
$allowed_mimes = get_allowed_mime_types();
$mimes_list = implode( ',', $allowed_mimes );

// Get the user's preferred items per page.
$user_id = get_current_user_id();
$per_page = get_user_meta( $user_id, 'media_grid_per_page', true );
if ( empty( $per_page ) || $per_page < 1 ) {
	$per_page = 80;
}

// Get the user's capabilities.
$readonly = current_user_can( 'edit_posts' ) ? '' : 'readonly';
$no_edit  = $readonly === 'readonly' ? 'hidden' : '';
$disabled = $readonly === 'readonly' ? 'disabled' : '';
$hidden   = current_user_can( 'delete_posts' ) ? '' : 'hidden';

// Fetch media items.
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$attachment_args = array(
	'post_type'      => 'attachment',
	'post_status'    => 'inherit',
	'posts_per_page' => $per_page,
	'paged'          => $paged,
);
$attachments = new WP_Query( $attachment_args );

$total_pages = ( $attachments->max_num_pages ) ? (int) $attachments->max_num_pages : 1;
$prev_page   = ( $paged === 1 ) ? $paged : $paged - 1;
$next_page   = ( $paged === $total_pages ) ? $paged : $paged + 1;

$attachment_url_options = '<option value="none" selected>' . esc_html__( 'None' ) . '</option>';
$attachment_url_options .= '<option value="file">' . esc_html__( 'Image URL' ) . '</option>';
if ( '1' === get_option( 'wp_attachment_pages_enabled' ) ) {
	$attachment_url_options .= '<option value="post">' . esc_html__( 'Attachment Page' ) . '</option>';
}
$attachment_url_options .= '<option value="custom">' . esc_html__( 'Custom URL' ) . '</option>';

$media_categories = get_terms(
	array(
		'taxonomy'   => 'media_category',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);

$media_tags = get_terms(
	array(
		'taxonomy'   => 'media_post_tag',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	)
);
?>

<div class="clear"></div></div><!-- wpwrap -->

<?php if ( current_user_can( 'publish_posts' ) && ! in_array( $hook_suffix, array( 'widgets.php', 'upload.php' ) ) ) : ?>

	<!-- Admin Media Modal. Access restricted to editors and above. -->
	<dialog id="admin-media-modal" class="admin-media-modal">
		<div id="admin-modal-container" class="admin-modal-container">

			<aside class="admin-modal-left-sidebar">
				<div class="admin-modal-left-sticky">
					<h3 class="admin-modal-left-heading"><?php esc_html_e( 'Actions' ); ?></h3>
					<div class="admin-modal-left-tablist" role="tablist" aria-orientation="vertical">
						<button id="admin-modal-item-add"
							type="button"
							role="tab"
							class="admin-modal-item active"
							aria-selected="true"
						>
							<?php esc_html_e( 'Add image' ); ?>
						</button>
						<div role="presentation" class="separator"></div>
						<button id="admin-modal-item-embed"
							type="button"
							role="tab"
							class="admin-modal-item"
							aria-selected="false"
							aria-controls="insert-from-url-panel"
						>
							<?php esc_html_e( 'Insert from URL' ); ?>
						</button>
					</div>
				</div>
			</aside>

			<div class="admin-modal-main">

				<header class="admin-modal-header">
					<div class="admin-modal-headings">
						<div id="admin-modal-title" class="admin-modal-title">
							<h2><?php esc_html_e( 'Media Library' ); ?></h2>
						</div>
						<details class="admin-modal-details" hidden>
							<summary><?php esc_html_e( 'Menu' ); ?></summary>
						</details>
						<button id="admin-modal-close" type="button" class="admin-modal-close" autofocus>
							<span id="admin-modal-icon" class="admin-modal-icon">
								<span class="screen-reader-text"><?php esc_html_e( 'Close dialog' ); ?></span>
							</span>
						</button>
					</div>
					<div class="admin-modal-header-buttons">
						<div role="tablist" aria-orientation="horizontal" class="admin-modal-router">

							<?php if ( current_user_can( 'upload_files' ) ) {
								?>

								<button type="button"
									role="tab"
									class="media-menu-item"
									id="menu-item-upload"
									aria-selected="false"
									aria-controls="uploader-inline"
								>
									<?php esc_html_e( 'Upload files' ); ?>
								</button>

								<?php
							}
							?>

							<button type="button"
								role="tab"
								class="media-menu-item active"
								id="menu-item-browse"
								aria-selected="true"
								aria-controls="media-library-grid"
							>
								<?php esc_html_e( 'Media Library' ); ?>
							</button>
						</div>

						<div class="admin-modal-pages">
							<span class="displaying-num">
								<?php echo $attachments->post_count < $per_page ? $attachments->post_count : $attachments->post_count % $total_pages; ?>
								<?php esc_html_e( 'items' ); ?>
								</span>
							<span class="pagination-links">
								<button type="button" class="first-page button" data-page="1" disabled inert>
									<span class="screen-reader-text"><?php esc_html_e( 'First page' ); ?></span>
									<span aria-hidden="true">«</span>
								</button>
								<button type="button" class="prev-page button" data-page="1" disabled inert>
									<span class="screen-reader-text"><?php esc_html_e( 'Previous page' ); ?></span>
									<span aria-hidden="true">‹</span>
								</button>
								<span class="paging-input">
									<label for="current-page-selector" class="screen-reader-text"><?php esc_html_e( 'Current Page' ); ?></label>
									<input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="4" aria-describedby="table-paging">
									<span id="table-paging" class="tablenav-paging-text"> <?php esc_html_e( 'of' ); ?> <span class="total-pages"><?php esc_html_e( $total_pages ); ?></span></span>
								</span>
								<button type="button" class="next-page button" data-page="<?php echo $next_page; ?>"
									<?php
									if ( $paged === $next_page ) {
										echo 'disabled inert';
									}
									?>
								>
									<span class="screen-reader-text"><?php esc_html_e( 'Next page' ); ?></span>
									<span aria-hidden="true">›</span>
								</button>
								<button type="button" class="last-page button" data-page="<?php echo $total_pages; ?>"
									<?php
									if ( $paged === $next_page ) {
										echo 'disabled inert';
									}
									?>
								>
									<span class="screen-reader-text"><?php esc_html_e( 'Last page' ); ?></span>
									<span aria-hidden="true">»</span>
								</button>
							</span>
						</div>
					</div>
				</header>

				<div class="admin-modal-body">
					<article id="admin-modal-content" class="admin-modal-content">

						<?php if ( current_user_can( 'upload_files' ) ) {
							?>

							<div id="uploader-inline" class="uploader-inline" role="tabpanel" data-allowed-mimes="<?php echo esc_attr( $mimes_list ); ?>" hidden inert>
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
							</div><!-- end #uploader-inline -->

							<?php
						}
						?>

						<div class="admin-modal-tabpanel" role="tabpanel">
							<section class="media-library-select-section">
								<div class="media-library-select-div">
									<div>
										<h3 class="admin-modal-filter-heading"><?php esc_html_e( 'Filter media' ); ?></h3>
										<fieldset>

											<?php
											// Select dropdown boxes
											$list_table = _get_list_table( 'WP_Media_List_Table' );
											$list_table->months_dropdown( 'attachment' );
											$list_table->media_categories_dropdown( 'attachment' );
											?>

										</fieldset>
									</div>

									<div class="admin-modal-search-form">
										<label for="admin-modal-search-input" class="admin-modal-search-label"><?php esc_html_e( 'Search' ); ?></label>
										<input type="search" id="admin-modal-search-input" class="search">
									</div>
								</div>
							</section>

							<section class="media-library-grid-section">
								<ul id="admin-modal-grid" class="admin-modal-grid">
									<?php // populated by JS after call to fetch API ?>
								</ul>
								<p class="load-more-count">
									<?php
									printf(
										'%d %s %d %s',
										$attachments->post_count < $per_page ? $attachments->post_count : $attachments->post_count % $total_pages,
										'of',
										$attachments->post_count,
										'items',
									);
									?>
								</p>
								<p class="no-media" hidden>
									<?php // populated, if applicable, by JS after call to fetch API ?>
								</p>
							</section>
						</div>

						<aside class="admin-modal-right-sidebar">
							<div class="admin-modal-right-sidebar-info" hidden>
								<h3><?php esc_html_e( 'Attachment Details' ); ?></h3>
								<fieldset class="admin-modal-attachment-info">
									<div class="thumbnail thumbnail-image">
										<img src="<?php echo esc_url( includes_url() . 'images/blank.gif' ); ?>" draggable="false" alt="">
									</div>
									<div class="details">
										<div class="filename"><strong><span class="screen-reader-text"><?php esc_html_e( 'File name:' ); ?></span> <span class="attachment-filename"></span></strong></div>
										<div class="uploaded">
											<span class="screen-reader-text"><?php esc_html_e( 'Uploaded on:' ); ?></span> <span class="attachment-date"></span>
										</div>
										<div class="file-size">
											<span class="screen-reader-text"><?php esc_html_e( 'File size:' ); ?></span> <span class="attachment-filesize"></span>
										</div>
										<div class="dimensions">
											<span class="screen-reader-text"><?php esc_html_e( 'Dimensions:' ); ?></span> <span class="attachment-dimensions"></span>
										</div>
										<div <?php echo $no_edit; ?>>
											<a id="edit-more" href=""><?php esc_html_e( 'Edit details' ); ?></a>
										</div>
										<div <?php echo $hidden; ?>>
											<button type="button" class="button-link delete-attachment"><?php esc_html_e( 'Delete permanently' ); ?></button>
										</div>
										<div class="compat-meta"></div>
									</div>							
								</fieldset>

								<fieldset class="admin-modal-descriptions">
									<div class="setting alt-text has-description" data-setting="alt">
										<label for="attachment-details-alt-text" class="name"><?php esc_html_e( 'Alt Text' ); ?></label>
										<textarea id="attachment-details-alt-text" aria-describedby="alt-text-description" <?php echo $readonly; ?>></textarea>
									</div>
									<p class="description" id="alt-text-description">
										<a href="https://www.w3.org/WAI/tutorials/images/decision-tree" target="_blank" rel="noopener">
											<?php esc_html_e( 'Learn how to describe the purpose of the image' ); ?>
											<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)' ); ?></span>
										</a>
										<?php esc_html_e( '. Leave empty if the image is purely decorative.' ); ?>
									</p>

									<div class="setting" data-setting="title">
										<label for="attachment-details-title" class="name"><?php esc_html_e( 'Title' ); ?></label>
										<input type="text" id="attachment-details-title" value="" <?php echo $readonly; ?>>
									</div>

									<span class="settings-save-status" role="status">
										<span id="details-saved" class="success hidden" aria-hidden="true"><?php esc_html_e( 'Saved!' ); ?></span>
									</span>

									<div class="setting" data-setting="caption">
										<label for="attachment-details-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
										<textarea id="attachment-details-caption" <?php echo $readonly; ?>></textarea>
									</div>

									<div class="setting" data-setting="description">
										<label for="attachment-details-description" class="name"><?php esc_html_e( 'Description' ); ?></label>
										<textarea id="attachment-details-description" <?php echo $readonly; ?>></textarea>
									</div>

									<div class="setting" data-setting="url">
										<label for="attachment-details-copy-link" class="name" style="padding:8px 1em 0 0;"><?php esc_html_e( 'File URL' ); ?></label>
										<input type="text" class="attachment-details-copy-link" id="attachment-details-copy-link" value="" readonly>
									</div>
									<div class="copy-to-clipboard-container">
										<button type="button" class="button button-small copy-attachment-url media-library" data-clipboard-target="#attachment-details-copy-link"><?php esc_html_e( 'Copy URL to clipboard' ); ?></button>
										<span class="success hidden" aria-hidden="true"><?php esc_html_e( 'Copied!' ); ?></span>
									</div>

									<div class="attachment-compat"></div>
									<datalist id="admin-modal-media-categories">

										<?php
										foreach ( $media_categories as $media_category ) {
											?>

											<option value="<?php echo esc_attr( $media_category->name ); ?>">
												<?php echo esc_html( $media_category->name ); ?>
											</option>

											<?php
										}
										?>

									</datalist>
									<datalist id="admin-modal-media-tags">

										<?php
										foreach ( $media_tags as $media_tag ) {
											?>

											<option value="<?php echo esc_attr( $media_tag->name ); ?>">
												<?php echo esc_html( $media_tag->name ); ?>
											</option>

											<?php
										}
										?>

									</datalist>
									<span class="setting settings-save-status" role="status">
										<span id="tax-saved" class="success hidden" aria-hidden="true">
											<?php esc_html_e( 'Taxonomy updated successfully!' ); ?>
										</span>
									</span>
								</fieldset>
							</div>

						
							<?php if ( current_user_can( 'upload_files' ) ) {
								?>

								<div class="media-uploader-status" hidden>
									<h3><?php esc_html_e( 'Uploading' ); ?></h3>
									<fieldset class="media-progress-bar">
									<div></div>
									</fieldset>
									<fieldset class="upload-details">
										<span class="upload-count">
											<span class="upload-index"></span> / <span class="upload-total"></span>
										</span>
										<span class="upload-detail-separator">–</span>
										<span class="upload-filename"></span>
									</fieldset>
									<div class="upload-errors"></div>
									<button type="button" class="button upload-dismiss-errors"><?php esc_html_e( 'Dismiss errors' ); ?></button>
								</div>

								<?php
							}
							?>

						</aside>
					</article>

					<?php if ( current_user_can( 'upload_files' ) ) {
						?>

						<article id="insert-from-url-panel" class="insert-from-url-panel" hidden inert>
							<div class="admin-modal-url-container">
								<div class="admin-modal-media-embed">
									<div class="admin-modal-embed-url">
										<input id="admin-modal-embed-url-field" type="url" aria-labelledby="media-frame-title-2">
									</div>

									<div id="admin-modal-url-settings" class="admin-modal-url-media-settings">
										<div class="wp-clearfix">
											<div class="thumbnail"></div>
										</div>

										<div class="setting alt-text has-description">
											<label for="embed-image-settings-alt-text" class="name"><?php esc_html_e( 'Alternative Text' ); ?></label>
											<textarea id="embed-image-settings-alt-text" data-setting="alt" aria-describedby="alt-text-description" <?php echo $readonly; ?>></textarea>
										</div>
										<p class="description" id="alt-text-description"><a href="https://www.w3.org/WAI/tutorials/images/decision-tree" target="_blank" rel="noopener"><?php esc_html_e( 'Learn how to describe the purpose of the image' ); ?> <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)' ); ?></span></a>. <?php esc_html_e( 'Leave empty if the image is purely decorative.' ); ?></p>

										<div class="setting caption">
											<label for="embed-image-settings-caption" class="name"><?php esc_html_e( 'Caption' ); ?></label>
											<textarea id="embed-image-settings-caption" data-setting="caption" <?php echo $readonly; ?>></textarea>
										</div>

										<fieldset class="setting-group">
											<legend class="name"><?php esc_html_e( 'Link To' ); ?></legend>
											<div class="setting link-to">
												<select id="link-to" name="link-to" data-setting="link" <?php echo $disabled; ?>>
													<?php echo $attachment_url_options; ?>
												</select>
											</div>
											<div id="link-to-url" hidden>
												<label for="embed-image-settings-link-to-custom" class="name"><?php esc_html_e( 'URL' ); ?></label>
												<input type="url" id="embed-image-settings-link-to-custom" class="link-to-custom" data-setting="linkUrl" <?php echo $readonly; ?>>
											</div>
										</fieldset>
									</div>

								</div>
							</div>
						</article><!-- end #insert-from-url-panel -->

						<?php
					}
					?>

				</div>

				<footer class="admin-modal-footer">
					<div class="admin-modal-footer-selection">
						<div class="admin-modal-footer-selection-info">
							<span class="count"></span>
							<br>
							<button type="button" class="button-link clear-selection">
								<?php esc_html_e( 'Clear' ); ?>
							</button>
						</div>
						<div class="admin-modal-footer-selection-view">
							<ul tabindex="-1"></ul>
						</div>
					</div>
					<div class="admin-modal-footer-buttons">
						<button id="admin-modal-select-button" type="button" class="button media-button button-primary button-large media-button-insert" disabled>
							<?php
							if ( $hook_suffix === 'appearance_page_custom-header' ) {
								esc_html_e( 'Set as Header' );
							} elseif ( $hook_suffix === 'appearance_page_custom-background' ) {
								esc_html_e( 'Set as Background' );
							}
							?>
						</button>
					</div>
				</footer>
			</div>
		</div>
	</dialog>
	<!-- End of Admin Media Modal. -->

<?php endif; ?>

<script>if(typeof wpOnload==='function')wpOnload();</script>
</body>
</html>
