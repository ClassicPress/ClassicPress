<?php
/**
 * General settings administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once __DIR__ . '/admin.php';

/** ClassicPress Translation Installation API */
require_once ABSPATH . 'wp-admin/includes/translation-install.php';

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Sorry, you are not allowed to manage options for this site.' ) );
}

// Used in the HTML title tag.
$title       = __( 'General Settings' );
$parent_file = 'options-general.php';
/* translators: Date and time format for exact current time, mainly about timezones, see https://www.php.net/manual/datetime.format.php */
$timezone_format = _x( 'Y-m-d H:i:s', 'timezone date format' );

// Old (pre-1.2.0) options-general JS
add_action( 'admin_head', 'options_general_add_js' );
// New (1.2.0, custom login image) options-general JS
wp_enqueue_media();
wp_enqueue_script( 'cp-options-general' );

$options_help = '<p>' . __( 'The fields on this screen determine some of the basics of your site setup.' ) . '</p>' .
	'<p>' . __( 'Most themes show the site title at the top of every page, in the title bar of the browser, and as the identifying name for syndicated feeds. Many themes also show the tagline.' ) . '</p>';

if ( ! is_multisite() ) {
	$options_help .= '<p>' . __( 'The ClassicPress URL and the site URL can be the same (example.com) or different; for example, having the ClassicPress core files (example.com/classicpress) in a subdirectory instead of the root directory.' ) . '</p>' .
		'<p>' . __( 'If you want site visitors to be able to register themselves, as opposed to by the site administrator, check the membership box. A default user role can be set for all new users, whether self-registered or registered by the site admin.' ) . '</p>';
}

$options_help .= '<p>' . __( 'You can set the language, and the translation files will be automatically downloaded and installed (available if your filesystem is writable).' ) . '</p>' .
	'<p>' . __( 'UTC means Coordinated Universal Time.' ) . '</p>' .
	'<p>' . __( 'You must click the Save Changes button at the bottom of the screen for new settings to take effect.' ) . '</p>';

get_current_screen()->add_help_tab(
	array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $options_help,
	)
);

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
	'<p>' . __( '<a href="https://wordpress.org/documentation/article/settings-general-screen/">Documentation on General Settings</a>' ) . '</p>' .
	'<p>' . __( '<a href="https://wordpress.org/support/forums/">Support forums</a>' ) . '</p>'
);

require_once ABSPATH . 'wp-admin/admin-header.php';
?>

<div class="wrap">
<h1><?php echo esc_html( $title ); ?></h1>

<form method="post" action="options.php">
<?php settings_fields( 'general' ); ?>

<table class="form-table" role="presentation">

<tr>
<th scope="row"><label for="blogname"><?php _e( 'Site Title' ); ?></label></th>
<td><input name="blogname" type="text" id="blogname" value="<?php form_option( 'blogname' ); ?>" class="regular-text"></td>
</tr>

<?php
/* translators: Site tagline. */
$sample_tagline = __( 'Just another ClassicPress site' );
if ( is_multisite() ) {
	/* translators: %s: Network title. */
	$sample_tagline = sprintf( __( 'Just another %s site' ), get_network()->site_name );
}
?>
<tr>
<th scope="row"><label for="blogdescription"><?php _e( 'Tagline' ); ?></label></th>
<td><input name="blogdescription" type="text" id="blogdescription" aria-describedby="tagline-description" value="<?php form_option( 'blogdescription' ); ?>" class="regular-text" placeholder="<?php echo $sample_tagline; ?>">
<p class="description" id="tagline-description"><?php _e( 'In a few words, explain what this site is about.' ); ?></p></td>
</tr>

<?php if ( current_user_can( 'upload_files' ) ) : ?>
<tr class="hide-if-no-js site-icon-section">
<th scope="row"><?php _e( 'Site Icon' ); ?></th>
<td>
	<?php
	wp_enqueue_media();
	wp_enqueue_script( 'site-icon' );

	$classes_for_upload_button = 'upload-button button-add-media button-add-site-icon';
	$classes_for_update_button = 'button';
	$classes_for_wrapper       = '';

	if ( has_site_icon() ) {
		$classes_for_wrapper         .= ' has-site-icon';
		$classes_for_button           = $classes_for_update_button;
		$classes_for_button_on_change = $classes_for_upload_button;
	} else {
		$classes_for_wrapper         .= ' hidden';
		$classes_for_button           = $classes_for_upload_button;
		$classes_for_button_on_change = $classes_for_update_button;
	}

	// Handle alt text for site icon on page load.
	$site_icon_id           = (int) get_option( 'site_icon' );
	$app_icon_alt_value     = '';
	$browser_icon_alt_value = '';

	if ( $site_icon_id ) {
		$img_alt            = get_post_meta( $site_icon_id, '_wp_attachment_image_alt', true );
		$filename           = wp_basename( get_site_icon_url() );
		$app_icon_alt_value = sprintf(
			/* translators: %s: The selected image filename. */
			__( 'App icon preview: The current image has no alternative text. The file name is: %s' ),
			$filename
		);

		$browser_icon_alt_value = sprintf(
			/* translators: %s: The selected image filename. */
			__( 'Browser icon preview: The current image has no alternative text. The file name is: %s' ),
			$filename
		);

		if ( $img_alt ) {
			$app_icon_alt_value = sprintf(
				/* translators: %s: The selected image alt text. */
				__( 'App icon preview: Current image: %s' ),
				$img_alt
			);

			$browser_icon_alt_value = sprintf(
				/* translators: %s: The selected image alt text. */
				__( 'Browser icon preview: Current image: %s' ),
				$img_alt
			);
		}
	}
	?>


	<div id="site-icon-preview" class="site-icon-preview wp-clearfix settings-page-preview <?php echo esc_attr( $classes_for_wrapper ); ?>">
		<div class="favicon-preview">
			<img src="<?php echo esc_url( admin_url( 'images/' . ( is_rtl() ? 'browser-rtl.png' : 'browser.png' ) ) ); ?>" class="browser-preview" width="182" alt="">
			<div class="favicon">
				<img id="browser-icon-preview" src="<?php site_icon_url(); ?>" alt="<?php echo esc_attr( $browser_icon_alt_value ); ?>">
			</div>
			<span id="site-icon-preview-site-title" class="browser-title" aria-hidden="true"><?php bloginfo( 'name' ); ?></span>
		</div>
		<img id="app-icon-preview" class="app-icon-preview" src="<?php site_icon_url(); ?>" alt="<?php echo esc_attr( $app_icon_alt_value ); ?>">
	</div>

	<input type="hidden" name="site_icon" id="site_icon_hidden_field" value="<?php form_option( 'site_icon' ); ?>" />
	<div class="action-buttons">
		<button type="button"
			id="choose-from-library-button"
			type="button"
			class="<?php echo esc_attr( $classes_for_button ); ?>"
			data-alt-classes="<?php echo esc_attr( $classes_for_button_on_change ); ?>"
			data-size="512"
			data-choose-text="<?php esc_attr_e( 'Choose a Site Icon' ); ?>"
			data-update-text="<?php esc_attr_e( 'Change Site Icon' ); ?>"
			data-update="<?php esc_attr_e( 'Set as Site Icon' ); ?>"
			data-state="<?php echo esc_attr( has_site_icon() ); ?>"

		>
			<?php if ( has_site_icon() ) : ?>
				<?php _e( 'Change Site Icon' ); ?>
			<?php else : ?>
				<?php _e( 'Choose a Site Icon' ); ?>
			<?php endif; ?>
		</button>
		<button
			id="js-remove-site-icon"
			type="button"
			<?php echo has_site_icon() ? 'class="button button-secondary reset"' : 'class="button button-secondary reset hidden"'; ?>
		>
			<?php _e( 'Remove Site Icon' ); ?>
		</button>
	</div>

	<p class="description">
		<?php
			/* translators: %s: Site Icon size in pixels. */
			printf( __( 'The Site Icon is what you see in browser tabs, bookmark bars, and within the WordPress mobile apps. It should be square and at least %s pixels.' ), '<code>512 &times; 512</code>' );
		?>
	</p>

</td>
</tr>

	<?php
endif;
	/* End Site Icon */

if ( ! is_multisite() ) {
	$wp_site_url_class = '';
	$wp_home_class     = '';
	if ( defined( 'WP_SITEURL' ) ) {
		$wp_site_url_class = ' disabled';
	}
	if ( defined( 'WP_HOME' ) ) {
		$wp_home_class = ' disabled';
	}
	?>

<tr>
<th scope="row"><label for="siteurl"><?php _e( 'ClassicPress Address (URL)' ); ?></label></th>
<td><input name="siteurl" type="url" id="siteurl" value="<?php form_option( 'siteurl' ); ?>"<?php disabled( defined( 'WP_SITEURL' ) ); ?> class="regular-text code<?php echo $wp_site_url_class; ?>"></td>
</tr>

<tr>
<th scope="row"><label for="home"><?php _e( 'Site Address (URL)' ); ?></label></th>
<td><input name="home" type="url" id="home" aria-describedby="home-description" value="<?php form_option( 'home' ); ?>"<?php disabled( defined( 'WP_HOME' ) ); ?> class="regular-text code<?php echo $wp_home_class; ?>">
	<?php if ( ! defined( 'WP_HOME' ) ) : ?>
<p class="description" id="home-description">
		<?php
		printf(
			/* translators: %s: Documentation URL. */
			__( 'Enter the same address here unless you <a href="%s">want your site home page to be different from your ClassicPress installation directory</a>.' ),
			__( 'https://wordpress.org/documentation/article/giving-wordpress-its-own-directory/' )
		);
		?>
</p>
<?php endif; ?>
</td>
</tr>

<?php } ?>

<tr>
<th scope="row"><label for="new_admin_email"><?php _e( 'Administration Email Address' ); ?></label></th>
<td><input name="new_admin_email" type="email" id="new_admin_email" aria-describedby="new-admin-email-description" value="<?php form_option( 'admin_email' ); ?>" class="regular-text ltr">
<p class="description" id="new-admin-email-description"><?php _e( 'This address is used for admin purposes. If you change this, an email will be sent to your new address to confirm it. <strong>The new address will not become active until confirmed.</strong>' ); ?></p>
<?php
$new_admin_email = get_option( 'new_admin_email' );
if ( $new_admin_email && get_option( 'admin_email' ) !== $new_admin_email ) :
	?>
	<div class="updated inline">
	<p>
	<?php
		printf(
			/* translators: %s: New admin email. */
			__( 'There is a pending change of the admin email to %s.' ),
			'<code>' . esc_html( $new_admin_email ) . '</code>'
		);
		printf(
			' <a href="%1$s">%2$s</a>',
			esc_url( wp_nonce_url( admin_url( 'options.php?dismiss=new_admin_email' ), 'dismiss-' . get_current_blog_id() . '-new_admin_email' ) ),
			__( 'Cancel' )
		);
	?>
	</p>
	</div>
<?php endif; ?>
</td>
</tr>

<?php
$login_custom_image_state = (int) get_option( 'login_custom_image_state' );
$login_custom_image_id    = (int) get_option( 'login_custom_image_id' );
if ( $login_custom_image_state < 0 || $login_custom_image_state > 2 ) {
	$login_custom_image_state = 0;
}
if ( $login_custom_image_id ) {
	$login_custom_image_src = wp_get_attachment_image_url( $login_custom_image_id, 'full' );
} else {
	$login_custom_image_src = '';
}
$login_custom_image_class = 'hidden';
if ( $login_custom_image_src ) {
	$login_custom_image_class = '';
} else {
	// invalid image
	$login_custom_image_src   = '';
	$login_custom_image_state = 0;
}
?>

<tr id="login_custom_image-row">
<th scope="row"><?php _e( 'Custom Login Image' ); ?></th>
<td>
	<fieldset>
		<legend class="screen-reader-text"><span><?php _e( 'Custom Login Image' ); ?></span></legend>
		<p class="description" id="login_custom_image-description">
			<?php _e( 'If you choose an image here and enable this option, then that image will be shown at the top of the login page instead of the ClassicPress logo.' ); ?>
			<a href="https://link.classicpress.net/docs/custom-login-image">
				<?php _e( 'Learn more.' ); ?>
			</a>
		</p>
		<label>
			<input name="login_custom_image_state" type="radio" value="0" <?php checked( 0, $login_custom_image_state ); ?>>
			<?php _e( 'No custom image: use the <strong>ClassicPress logo</strong>' ); ?>
		</label>
		<br>
		<label>
			<input name="login_custom_image_state" type="radio" value="1"
			<?php
			checked( 1, $login_custom_image_state );
			disabled( $login_custom_image_state === 0 && ! $login_custom_image_src );
			?>
			>
			<?php _e( 'Use my custom image as a <strong>logo</strong>' ); ?>
		</label>
		<br>
		<label>
			<input name="login_custom_image_state" type="radio" value="2"
			<?php
			checked( 2, $login_custom_image_state );
			disabled( $login_custom_image_state === 0 && ! $login_custom_image_src );
			?>
			>
			<?php _e( 'Use my custom image as a <strong>banner</strong>' ); ?>
		</label>
		<p><img id="login_custom_image-img" src="<?php echo esc_attr( $login_custom_image_src ); ?>" class="<?php echo esc_attr( $login_custom_image_class ); ?>"></p>
		<p id="login_custom_image-controls" class="hidden">
			<input type="hidden" id="login_custom_image-input" name="login_custom_image_id" value="<?php form_option( 'login_custom_image_id' ); ?>">
			<input type="button" id="login_custom_image-choose" class="button" value="<?php _e( 'Choose Image' ); ?>">
			<input type="button" id="login_custom_image-clear" class="button" value="<?php _e( 'Clear Image' ); ?>">
		</p>
		<noscript>
			<p class="description">
				<?php _e( 'Changing the custom login image requires JavaScript to be enabled.' ); ?>
			</p>
		</noscript>
	</fieldset>
</td>
</tr>

<?php if ( ! is_multisite() ) { ?>

<tr>
<th scope="row"><?php _e( 'Membership' ); ?></th>
<td> <fieldset><legend class="screen-reader-text"><span>
	<?php
	/* translators: Hidden accessibility text. */
	_e( 'Membership' );
	?>
</span></legend><label for="users_can_register">
<input name="users_can_register" type="checkbox" id="users_can_register" value="1" <?php checked( '1', get_option( 'users_can_register' ) ); ?>>
	<?php _e( 'Anyone can register' ); ?></label>
</fieldset></td>
</tr>

<tr>
<th scope="row"><label for="default_role"><?php _e( 'New User Default Role' ); ?></label></th>
<td>
<select name="default_role" id="default_role"><?php wp_dropdown_roles( get_option( 'default_role' ) ); ?></select>
</td>
</tr>

	<?php
}

$languages    = get_available_languages();
$translations = wp_get_available_translations();
if ( ! is_multisite() && defined( 'WPLANG' ) && '' !== WPLANG && 'en_US' !== WPLANG && ! in_array( WPLANG, $languages, true ) ) {
	$languages[] = WPLANG;
}
if ( ! empty( $languages ) || ! empty( $translations ) ) {
	?>
	<tr>
		<th scope="row"><label for="WPLANG"><?php _e( 'Site Language' ); ?><span class="dashicons dashicons-translation" aria-hidden="true"></span></label></th>
		<td>
			<?php
			$locale = get_locale();
			if ( ! in_array( $locale, $languages, true ) ) {
				$locale = '';
			}

			wp_dropdown_languages(
				array(
					'name'                        => 'WPLANG',
					'id'                          => 'WPLANG',
					'selected'                    => $locale,
					'languages'                   => $languages,
					'translations'                => $translations,
					'show_available_translations' => current_user_can( 'install_languages' ) && wp_can_install_language_pack(),
				)
			);

			// Add note about deprecated WPLANG constant.
			if ( defined( 'WPLANG' ) && ( '' !== WPLANG ) && WPLANG !== $locale ) {
				_deprecated_argument(
					'define()',
					'4.0.0',
					/* translators: 1: WPLANG, 2: wp-config.php */
					sprintf( __( 'The %1$s constant in your %2$s file is no longer needed.' ), 'WPLANG', 'wp-config.php' )
				);
			}
			?>
		</td>
	</tr>
	<?php
}
?>
<tr>
<?php
$current_offset = get_option( 'gmt_offset' );
$tzstring       = get_option( 'timezone_string' );

$check_zone_info = true;

// Remove old Etc mappings. Fallback to gmt_offset.
if ( str_contains( $tzstring, 'Etc/GMT' ) ) {
	$tzstring = '';
}

if ( empty( $tzstring ) ) { // Create a UTC+- zone if no timezone string exists.
	$check_zone_info = false;
	if ( 0 == $current_offset ) {
		$tzstring = 'UTC+0';
	} elseif ( $current_offset < 0 ) {
		$tzstring = 'UTC' . $current_offset;
	} else {
		$tzstring = 'UTC+' . $current_offset;
	}
}

?>
<th scope="row"><label for="timezone_string"><?php _e( 'Timezone' ); ?></label></th>
<td>

<select id="timezone_string" name="timezone_string" aria-describedby="timezone-description">
	<?php echo wp_timezone_choice( $tzstring, get_user_locale() ); ?>
</select>

<p class="description" id="timezone-description">
<?php
	printf(
		/* translators: %s: UTC abbreviation */
		__( 'Choose either a city in the same timezone as you or a %s (Coordinated Universal Time) time offset.' ),
		'<abbr>UTC</abbr>'
	);
	?>
</p>

<p class="timezone-info">
	<span id="utc-time">
	<?php
		printf(
			/* translators: %s: UTC time. */
			__( 'Universal time is %s.' ),
			'<code>' . date_i18n( $timezone_format, false, true ) . '</code>'
		);
		?>
	</span>
<?php if ( get_option( 'timezone_string' ) || ! empty( $current_offset ) ) : ?>
	<span id="local-time">
	<?php
		printf(
			/* translators: %s: Local time. */
			__( 'Local time is %s.' ),
			'<code>' . date_i18n( $timezone_format ) . '</code>'
		);
	?>
	</span>
<?php endif; ?>
</p>

<?php if ( $check_zone_info && $tzstring ) : ?>
<p class="timezone-info">
<span>
	<?php
	$now = new DateTime( 'now', new DateTimeZone( $tzstring ) );
	$dst = (bool) $now->format( 'I' );

	if ( $dst ) {
		_e( 'This timezone is currently in daylight saving time.' );
	} else {
		_e( 'This timezone is currently in standard time.' );
	}
	?>
	<br>
	<?php
	if ( in_array( $tzstring, timezone_identifiers_list( DateTimeZone::ALL_WITH_BC ), true ) ) {
		$transitions = timezone_transitions_get( timezone_open( $tzstring ), time() );

		// 0 index is the state at current time, 1 index is the next transition, if any.
		if ( ! empty( $transitions[1] ) ) {
			echo ' ';
			$message = $transitions[1]['isdst'] ?
				/* translators: %s: Date and time. */
				__( 'Daylight saving time begins on: %s.' ) :
				/* translators: %s: Date and time. */
				__( 'Standard time begins on: %s.' );
			printf(
				$message,
				'<code>' . wp_date( __( 'F j, Y' ) . ' ' . __( 'g:i a' ), $transitions[1]['ts'] ) . '</code>'
			);
		} else {
			_e( 'This timezone does not observe daylight saving time.' );
		}
	}
	?>
	</span>
</p>
<?php endif; ?>
</td>

</tr>
<tr>
<th scope="row"><?php _e( 'Date Format' ); ?></th>
<td>
	<fieldset><legend class="screen-reader-text"><span>
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Date Format' );
		?>
	</span></legend>
<?php
	/**
	 * Filters the default date formats.
	 *
	 * @since 2.7.0
	 * @since 4.0.0 Added ISO date standard YYYY-MM-DD format.
	 *
	 * @param string[] $default_date_formats Array of default date formats.
	 */
	$date_formats = array_unique( apply_filters( 'date_formats', array( __( 'F j, Y' ), 'Y-m-d', 'm/d/Y', 'd/m/Y' ) ) );

	$custom = true;

foreach ( $date_formats as $format ) {
	echo "\t<label><input type='radio' name='date_format' value='" . esc_attr( $format ) . "'";
	if ( get_option( 'date_format' ) === $format ) { // checked() uses "==" rather than "===".
		echo ' checked';
		$custom = false;
	}
	echo '> <span class="date-time-text format-i18n">' . date_i18n( $format ) . '</span><code>' . esc_html( $format ) . "</code></label><br>\n";
}

	echo '<label><input type="radio" name="date_format" id="date_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '> <span class="date-time-text date-time-custom-text">' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' .
			/* translators: Hidden accessibility text. */
			__( 'enter a custom date format in the following field' ) .
		'</span></span></label>' .
		'<label for="date_format_custom" class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Custom date format:' ) .
		'</label>' .
		'<input type="text" name="date_format_custom" id="date_format_custom" value="' . esc_attr( get_option( 'date_format' ) ) . '" class="small-text">' .
		'<br>' .
		'<p><strong>' . __( 'Preview:' ) . '</strong> <span class="example">' . date_i18n( get_option( 'date_format' ) ) . '</span>' .
		"<span class='spinner'></span>\n" . '</p>';
?>
	</fieldset>
</td>
</tr>
<tr>
<th scope="row"><?php _e( 'Time Format' ); ?></th>
<td>
	<fieldset><legend class="screen-reader-text"><span>
		<?php
		/* translators: Hidden accessibility text. */
		_e( 'Time Format' );
		?>
	</span></legend>
<?php
	/**
	 * Filters the default time formats.
	 *
	 * @since 2.7.0
	 *
	 * @param string[] $default_time_formats Array of default time formats.
	 */
	$time_formats = array_unique( apply_filters( 'time_formats', array( __( 'g:i a' ), 'g:i A', 'H:i' ) ) );

	$custom = true;

foreach ( $time_formats as $format ) {
	echo "\t<label><input type='radio' name='time_format' value='" . esc_attr( $format ) . "'";
	if ( get_option( 'time_format' ) === $format ) { // checked() uses "==" rather than "===".
		echo ' checked';
		$custom = false;
	}
	echo '> <span class="date-time-text format-i18n">' . date_i18n( $format ) . '</span><code>' . esc_html( $format ) . "</code></label><br>\n";
}

	echo '<label><input type="radio" name="time_format" id="time_format_custom_radio" value="\c\u\s\t\o\m"';
	checked( $custom );
	echo '> <span class="date-time-text date-time-custom-text">' . __( 'Custom:' ) . '<span class="screen-reader-text"> ' .
			/* translators: Hidden accessibility text. */
			__( 'enter a custom time format in the following field' ) .
		'</span></span></label>' .
		'<label for="time_format_custom" class="screen-reader-text">' .
			/* translators: Hidden accessibility text. */
			__( 'Custom time format:' ) .
		'</label>' .
		'<input type="text" name="time_format_custom" id="time_format_custom" value="' . esc_attr( get_option( 'time_format' ) ) . '" class="small-text">' .
		'<br>' .
		'<p><strong>' . __( 'Preview:' ) . '</strong> <span class="example">' . date_i18n( get_option( 'time_format' ) ) . '</span>' .
		"<span class='spinner'></span>\n" . '</p>';

	echo "\t<p class='date-time-doc'>" . __( '<a href="https://wordpress.org/documentation/article/customize-date-and-time-format/">Documentation on date and time formatting</a>.' ) . "</p>\n";
?>
	</fieldset>
</td>
</tr>
<tr>
<th scope="row"><label for="start_of_week"><?php _e( 'Week Starts On' ); ?></label></th>
<td><select name="start_of_week" id="start_of_week">
<?php
/**
 * @global WP_Locale $wp_locale WordPress date and time locale object.
 */
global $wp_locale;

for ( $day_index = 0; $day_index <= 6; $day_index++ ) :
	$selected = ( get_option( 'start_of_week' ) == $day_index ) ? 'selected' : '';
	echo "\n\t<option value='" . esc_attr( $day_index ) . "' $selected>" . $wp_locale->get_weekday( $day_index ) . '</option>';
endfor;
?>
</select></td>
</tr>


<tr>
<th scope="row"><label for="blocks_compatibility_level"><?php _e( 'Blocks Compatibility' ); ?></label></th>
<td><select name="blocks_compatibility_level" id="blocks_compatibility_level">
<?php
$blocks_compatibility_level = get_option( 'blocks_compatibility_level', 1 );

$blocks_compatibility_level_desc = array(
	_x( 'Off', 'Block Compatibility' ),
	_x( 'On', 'Block Compatibility' ),
	_x( 'Troubleshooting', 'Block Compatibility' ),
);

for ( $index = 0; $index <= 2; $index++ ) {
	echo "\n\t<option value='" . esc_attr( $index ) . "' " . selected( $blocks_compatibility_level, $index, false ) . '>'
	. esc_html( $blocks_compatibility_level_desc[ $index ] ) . '</option>';
}
?>
</select>
<p class="description" id="home-description">
<?php _e( 'ClassicPress is incompatible with the block editor. When blocks compatibility is turned on (default), it allows more plugins and themes to work but block-related features will not work. ' ); ?>
<a href="https://docs.classicpress.net/user-guides/using-classicpress/settings-general-screen/#blocks-compatibility">
<?php _e( 'Learn more.' ); ?>
</a>
</p>
</td>
</tr>


<?php do_settings_fields( 'general', 'default' ); ?>
</table>

<?php do_settings_sections( 'general' ); ?>

<?php submit_button(); ?>
</form>

</div>

<?php require_once ABSPATH . 'wp-admin/admin-footer.php'; ?>
