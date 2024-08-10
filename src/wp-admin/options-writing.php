<?php
/**
 * Writing settings administration panel.
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
$title       = __( 'Writing Settings' );
$parent_file = 'options-general.php';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => '<p>' . __( 'You can submit content in several different ways; this screen holds the settings for all of them. The top section controls the editor within the dashboard, while the rest control external publishing methods. For more information on any of these methods, use the documentation links.' ) . '</p>' .
			'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>',
	)
);

/** This filter is documented in wp-admin/options.php */
if ( apply_filters( 'enable_post_by_email_configuration', true ) ) {
	get_current_screen()->add_help_tab(
		array(
			'id'      => 'options-postemail',
			'title'   => __( 'Post Via Email' ),
			'content' => '<p>' . __( 'Post via email settings allow you to send your ClassicPress installation an email with the content of your post. You must set up a secret email account with POP3 access to use this, and any mail received at this address will be posted, so it&#8217;s a good idea to keep this address very secret.' ) . '</p>',
		)
	);
}

/** This filter is documented in wp-admin/options-writing.php */
if ( apply_filters( 'enable_update_services_configuration', true ) ) {
	get_current_screen()->add_help_tab(
		array(
			'id'      => 'options-services',
			'title'   => __( 'Update Services' ),
			'content' => '<p>' . __( 'If desired, ClassicPress will automatically alert various services of your new posts.' ) . '</p>',
		)
	);
}

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/settings-writing-screen/">Documentation on Writing Settings</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

require_once ABSPATH . 'wp-admin/admin-header.php';
?>

<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<form method="post" action="options.php">
<?php settings_fields( 'writing' ); ?>

<table class="form-table" role="presentation">
<?php if ( get_site_option( 'initial_db_version' ) < 32453 ) : ?>
<tr>
<th scope="row"><?php _e( 'Formatting' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span>
	<?php
	/* translators: Hidden accessibility text. */
	_e( 'Formatting' );
	?>
</span></legend>
<label for="use_smilies">
<input name="use_smilies" type="checkbox" id="use_smilies" value="1" <?php checked( '1', get_option( 'use_smilies' ) ); ?>>
	<?php _e( 'Convert emoticons like <code>:-)</code> and <code>:-P</code> to graphics on display' ); ?></label><br>
<label for="use_balanceTags"><input name="use_balanceTags" type="checkbox" id="use_balanceTags" value="1" <?php checked( '1', get_option( 'use_balanceTags' ) ); ?>> <?php _e( 'ClassicPress should correct invalidly nested XHTML automatically' ); ?></label>
</fieldset></td>
</tr>
<?php endif; ?>
<tr>
<th scope="row"><label for="default_category"><?php _e( 'Default Post Category' ); ?></label></th>
<td>
<?php
wp_dropdown_categories(
	array(
		'hide_empty'   => 0,
		'name'         => 'default_category',
		'orderby'      => 'name',
		'selected'     => get_option( 'default_category' ),
		'hierarchical' => true,
	)
);
?>
</td>
</tr>
<?php
$post_formats = get_post_format_strings();
unset( $post_formats['standard'] );
?>
<tr>
<th scope="row"><label for="default_post_format"><?php _e( 'Default Post Format' ); ?></label></th>
<td>
	<select name="default_post_format" id="default_post_format">
		<option value="0"><?php echo get_post_format_string( 'standard' ); ?></option>
<?php foreach ( $post_formats as $format_slug => $format_name ) : ?>
		<option<?php selected( get_option( 'default_post_format' ), $format_slug ); ?> value="<?php echo esc_attr( $format_slug ); ?>"><?php echo esc_html( $format_name ); ?></option>
<?php endforeach; ?>
	</select>
</td>
</tr>
<?php
if ( get_option( 'link_manager_enabled' ) ) :
	?>
<tr>
<th scope="row"><label for="default_link_category"><?php _e( 'Default Link Category' ); ?></label></th>
<td>
	<?php
	wp_dropdown_categories(
		array(
			'hide_empty'   => 0,
			'name'         => 'default_link_category',
			'orderby'      => 'name',
			'selected'     => get_option( 'default_link_category' ),
			'hierarchical' => true,
			'taxonomy'     => 'link_category',
		)
	);
	?>
</td>
</tr>
<?php endif; ?>

<?php
do_settings_fields( 'writing', 'default' );
do_settings_fields( 'writing', 'remote_publishing' ); // A deprecated section.
?>
</table>

<?php
/**
 * Filters whether to enable the Update Services section in the Writing settings screen.
 *
 * @since 3.0.0
 *
 * @param bool $enable Whether to enable the Update Services settings area. Default true.
 */
if ( apply_filters( 'enable_update_services_configuration', true ) ) {
	?>
<h2 class="title"><?php _e( 'Update Services' ); ?></h2>

	<?php if ( 1 == get_option( 'blog_public' ) ) : ?>

	<p><label for="ping_sites">
		<?php
		printf(
			/* translators: %s: Documentation URL. */
			__( 'When you publish a new post, ClassicPress automatically notifies the following site update services. For more about this, see the <a href="%s">Update Services</a> documentation article. Separate multiple service URLs with line breaks.' ),
			__( 'https://wordpress.org/documentation/article/update-services/' )
		);
		?>
	</label></p>

	<textarea name="ping_sites" id="ping_sites" class="large-text code" rows="3"><?php echo esc_textarea( get_option( 'ping_sites' ) ); ?></textarea>

	<?php else : ?>

	<p>
		<?php
		printf(
			/* translators: 1: Documentation URL, 2: URL to Reading Settings screen. */
			__( 'ClassicPress is not notifying any <a href="%1$s">Update Services</a> because of your site&#8217;s <a href="%2$s">visibility settings</a>.' ),
			__( 'https://wordpress.org/documentation/article/update-services/' ),
			'options-reading.php'
		);
		?>
	</p>

	<?php endif; ?>
<?php } // enable_update_services_configuration ?>

<?php do_settings_sections( 'writing' ); ?>

<?php submit_button(); ?>
</form>
</div>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
