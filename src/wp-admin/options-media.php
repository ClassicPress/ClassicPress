<?php
/**
 * Media settings administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
}

// Used in the HTML title tag.
$title       = __( 'Media Settings' );
$parent_file = 'options-general.php';

$media_options_help = '<p>' . __( 'You can set maximum sizes for images inserted into your written content; you can also insert an image as Full Size.' ) . '</p>';

if ( ! is_multisite()
	&& ( get_option( 'upload_url_path' )
		|| get_option( 'upload_path' ) && 'wp-content/uploads' !== get_option( 'upload_path' ) )
) {
	$media_options_help .= '<p>' . __( 'Uploading Files allows you to choose the folder and path for storing your uploaded files.' ) . '</p>';
}

$media_options_help .= '<p>' . __( 'You can choose how you would like uploads to be organized after uploading.' ) . '</p>';

$media_options_help .= '<p>' . __( 'Media attachments, including image, audio, video files, can have Attachment Pages if this is supported by your Theme. You can choose to enable or disable Attachment Pages.' ) . '</p>';

$media_options_help .= '<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $media_options_help,
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/settings-media-screen/">Documentation on Media Settings</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

require_once ABSPATH . 'wp-admin/admin-header.php';

/**
 * Get upload organization preference.
 *
 * @since CP-2.2.0
 *
 * New options based on year and media category added.
 */
$storefolders = (int) get_option( 'uploads_use_yearmonth_folders' );

$media_attribute = $media_requires = '';

$media_terms = get_terms(
	array(
		'taxonomy'   => 'media_category',
		'hide_empty' => false,
	)
);

if ( empty( $media_terms ) ) {
	$media_attribute = 'disabled';
	$media_requires = ' <em>' . __( 'This option requires that at least one media category has been created.' ) . '</em> <a href="' . admin_url( 'edit-tags.php?taxonomy=media_category&post_type=attachment' ) . '">' . __( 'Create one now.' ) . '</a>';
}

/**
 * Get attachment page preference.
 *
 * @since CP-2.2.0
 *
 */
$attachment_pages_enabled = get_option( 'wp_attachment_pages_enabled' );
?>

<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<form action="options.php" method="post">
<?php settings_fields( 'media' ); ?>

<h2 class="title"><?php _e( 'Image sizes' ); ?></h2>
<p><?php _e( 'The sizes listed below determine the maximum dimensions in pixels to use when adding an image to the Media Library.' ); ?></p>

<table class="form-table" role="presentation">
<tr>
<th scope="row"><?php _e( 'Thumbnail size' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span>
	<?php
	/* translators: Hidden accessibility text. */
	_e( 'Thumbnail size' );
	?>
</span></legend>
<label for="thumbnail_size_w"><?php _e( 'Width' ); ?></label>
<input name="thumbnail_size_w" type="number" step="1" min="0" id="thumbnail_size_w" value="<?php form_option( 'thumbnail_size_w' ); ?>" class="small-text">
<br>
<label for="thumbnail_size_h"><?php _e( 'Height' ); ?></label>
<input name="thumbnail_size_h" type="number" step="1" min="0" id="thumbnail_size_h" value="<?php form_option( 'thumbnail_size_h' ); ?>" class="small-text">
</fieldset>
<input name="thumbnail_crop" type="checkbox" id="thumbnail_crop" value="1" <?php checked( '1', get_option( 'thumbnail_crop' ) ); ?>>
<label for="thumbnail_crop"><?php _e( 'Crop thumbnail to exact dimensions (normally thumbnails are proportional)' ); ?></label>
</td>
</tr>

<tr>
<th scope="row"><?php _e( 'Medium size' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span>
	<?php
	/* translators: Hidden accessibility text. */
	_e( 'Medium size' );
	?>
</span></legend>
<label for="medium_size_w"><?php _e( 'Max Width' ); ?></label>
<input name="medium_size_w" type="number" step="1" min="0" id="medium_size_w" value="<?php form_option( 'medium_size_w' ); ?>" class="small-text">
<br>
<label for="medium_size_h"><?php _e( 'Max Height' ); ?></label>
<input name="medium_size_h" type="number" step="1" min="0" id="medium_size_h" value="<?php form_option( 'medium_size_h' ); ?>" class="small-text">
</fieldset></td>
</tr>

<tr>
<th scope="row"><?php _e( 'Large size' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span>
	<?php
	/* translators: Hidden accessibility text. */
	_e( 'Large size' );
	?>
</span></legend>
<label for="large_size_w"><?php _e( 'Max Width' ); ?></label>
<input name="large_size_w" type="number" step="1" min="0" id="large_size_w" value="<?php form_option( 'large_size_w' ); ?>" class="small-text">
<br>
<label for="large_size_h"><?php _e( 'Max Height' ); ?></label>
<input name="large_size_h" type="number" step="1" min="0" id="large_size_h" value="<?php form_option( 'large_size_h' ); ?>" class="small-text">
</fieldset></td>
</tr>

<?php do_settings_fields( 'media', 'default' ); ?>
</table>

<?php
/**
 * @global array $wp_settings
 */
if ( isset( $GLOBALS['wp_settings']['media']['embeds'] ) ) :
	?>
<h2 class="title"><?php _e( 'Embeds' ); ?></h2>
<table class="form-table" role="presentation">
	<?php do_settings_fields( 'media', 'embeds' ); ?>
</table>
<?php endif; ?>

<?php if ( ! is_multisite() ) : ?>
<h2 class="title"><?php _e( 'Uploading Files' ); ?></h2>
<table class="form-table" role="presentation">
	<?php
	/*
	 * If upload_url_path is not the default (empty),
	 * or upload_path is not the default ('wp-content/uploads' or empty),
	 * they can be edited, otherwise they're locked.
	 */
	if ( get_option( 'upload_url_path' )
		|| get_option( 'upload_path' ) && 'wp-content/uploads' !== get_option( 'upload_path' ) ) :
		?>
<tr>
<th scope="row"><label for="upload_path"><?php _e( 'Store uploads in this folder' ); ?></label></th>
<td><input name="upload_path" type="text" id="upload_path" value="<?php echo esc_attr( get_option( 'upload_path' ) ); ?>" class="regular-text code">
<p class="description">
		<?php
		/* translators: %s: wp-content/uploads */
		printf( __( 'Default is %s' ), '<code>wp-content/uploads</code>' );
		?>
</p>
</td>
</tr>

<tr>
<th scope="row"><label for="upload_url_path"><?php _e( 'Full URL path to files' ); ?></label></th>
<td><input name="upload_url_path" type="text" id="upload_url_path" value="<?php echo esc_attr( get_option( 'upload_url_path' ) ); ?>" class="regular-text code">
<p class="description"><?php _e( 'Configuring this is optional. By default, it should be blank.' ); ?></p>
</td>
</tr>
<tr>
<td colspan="2" class="td-full">
<?php else : ?>
<tr>
<th scope="row"><?php _e( 'How do you want your uploads organized?' ); ?></th>
<td class="td-full uploads_use_yearmonth_folders">
<?php endif; ?>

<input id="uploads_use_one_folder" name="uploads_use_yearmonth_folders" type="radio" value="0"<?php checked( 0, $storefolders ); ?>>
<label for="uploads_use_one_folder"><?php _e( 'Store all uploads in the same folder.' ); ?></label><br>

<input id="uploads_use_year_folders" type="radio" name="uploads_use_yearmonth_folders" value="2"<?php checked( 2, $storefolders ); ?>>
<label for="uploads_use_year_folders"><?php _e( 'Organize uploads into year-based folders.' ); ?></label><br>

<input id="uploads_use_yearmonth_folders" type="radio" name="uploads_use_yearmonth_folders" value="1"<?php checked( 1, $storefolders ); ?>>
<label for="uploads_use_yearmonth_folders"><?php _e( 'Organize uploads into month- and year-based folders.' ); ?></label><br>

<input id="uploads_use_category_folders" type="radio" name="uploads_use_yearmonth_folders" value="3"
	<?php checked( 3, $storefolders ); ?>
	<?php echo esc_attr( $media_attribute ); ?>>
<label for="uploads_use_category_folders">
	<?php _e( 'Organize uploads according to media category.' ); ?>
	<?php echo $media_requires; ?>
</label>
<p class="description"><?php _e( 'By default uploads are organized into month- and year-based folders.' ); ?></p>
</td>
</tr>

	<?php do_settings_fields( 'media', 'uploads' ); ?>
</table>
<?php endif; ?>

<?php if ( ! is_multisite() ) : ?>
<h2 class="title"><?php _e( 'Attachment Pages' ); ?></h2>
<table class="form-table" role="presentation">

<tr>
<th scope="row"><?php _e( 'Do you want to enable media attachment pages' ); ?></th>
<td class="td-full attachment-pages">

<input type="hidden" name="wp_attachment_pages_enabled" value="0">
<input id="attachment-pages" name="wp_attachment_pages_enabled" type="checkbox" value="1"<?php checked( '1', $attachment_pages_enabled ); ?>>
<label for="attachment-pages"><?php _e( 'Media attachment pages enabled' ); ?></label>
<p class="description"><?php _e( 'A media attachment page displays information about a particular media file you have uploaded to your site.' ); ?></p>
</td>
</tr>

	<?php do_settings_fields( 'media', 'attachments' ); ?>
</table>
<?php endif; ?>

<?php do_settings_sections( 'media' ); ?>

<?php submit_button(); ?>

</form>

</div>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
