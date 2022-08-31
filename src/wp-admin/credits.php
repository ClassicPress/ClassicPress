<?php
/**
 * Credits administration panel.
 *
 * @package ClassicPress
 * @subpackage Administration
 */

/** ClassicPress Administration Bootstrap */
require_once dirname( __FILE__ ) . '/admin.php';

$title = __( 'Credits' );

require ABSPATH . 'wp-admin/admin-header.php';
?>
<div class="wrap about-wrap full-width-layout">

<h1><?php _e( 'Welcome to ClassicPress!' ); ?></h1>

<p class="about-text">
	<?php printf( __( 'Version %s' ), classicpress_version() ); ?>
	<?php classicpress_dev_version_info(); ?>
</p>
<p class="about-text">
	<?php
	printf(
		/* translators: link to "business-focused CMS" article */
		__( 'Thank you for using ClassicPress, the <a href="%s">CMS for Creators</a>.' ),
		'https://link.classicpress.net/the-cms-for-creators'
	);
	?>
	<br>
	<?php _e( 'Stable. Lightweight. Instantly Familiar.' ); ?>
</p>

<div class="wp-badge"></div>

<h2 class="nav-tab-wrapper wp-clearfix">
	<a href="about.php" class="nav-tab"><?php _e( 'About' ); ?></a>
	<a href="credits.php" class="nav-tab nav-tab-active"><?php _e( 'Credits' ); ?></a>
	<a href="freedoms.php" class="nav-tab"><?php _e( 'Freedoms' ); ?></a>
	<a href="freedoms.php?privacy-notice" class="nav-tab"><?php _e( 'Privacy' ); ?></a>
</h2>

<div class="about-wrap-content">
<?php

echo '<p class="about-description">' . sprintf(
	/* translators: %s: https://www.classicpress.net/contributors/ */
	__( 'ClassicPress is created by a <a href="%1$s">worldwide team</a> of passionate individuals.' ),
	'https://www.classicpress.net/contributors/'
) . '</p>';

<<<<<<< HEAD
echo '<p class="about-description">' . sprintf(
	/* translators: %s: https://www.classicpress.net/get-involved/ */
	__( 'Interested in helping out with development? <a href="%s">Get involved in ClassicPress</a>.' ),
	'https://www.classicpress.net/get-involved/'
) . '</p>';
=======
foreach ( $credits['groups'] as $group_slug => $group_data ) {
	if ( $group_data['name'] ) {
		if ( 'Translators' == $group_data['name'] ) {
			// Considered a special slug in the API response. (Also, will never be returned for en_US.)
			$title = _x( 'Translators', 'Translate this to be the equivalent of English Translators in your language for the credits page Translators section' );
		} elseif ( isset( $group_data['placeholders'] ) ) {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			$title = vsprintf( translate( $group_data['name'] ), $group_data['placeholders'] );
		} else {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
			$title = translate( $group_data['name'] );
		}

		echo '<h3 class="wp-people-group">' . esc_html( $title ) . "</h3>\n";
	}

	if ( ! empty( $group_data['shuffle'] ) ) {
		shuffle( $group_data['data'] ); // We were going to sort by ability to pronounce "hierarchical," but that wouldn't be fair to Matt.
	}

	switch ( $group_data['type'] ) {
		case 'list':
			array_walk( $group_data['data'], '_wp_credits_add_profile_link', $credits['data']['profiles'] );
			echo '<p class="wp-credits-list">' . wp_sprintf( '%l.', $group_data['data'] ) . "</p>\n\n";
			break;
		case 'libraries':
			array_walk( $group_data['data'], '_wp_credits_build_object_link' );
			echo '<p class="wp-credits-list">' . wp_sprintf( '%l.', $group_data['data'] ) . "</p>\n\n";
			break;
		default:
			$compact = 'compact' == $group_data['type'];
			$classes = 'wp-people-group ' . ( $compact ? 'compact' : '' );
			echo '<ul class="' . $classes . '" id="wp-people-group-' . $group_slug . '">' . "\n";
			foreach ( $group_data['data'] as $person_data ) {
				echo '<li class="wp-person" id="wp-person-' . esc_attr( $person_data[2] ) . '">' . "\n\t";
				echo '<a href="' . esc_url( sprintf( $credits['data']['profiles'], $person_data[2] ) ) . '" class="web">';
				$size   = 'compact' == $group_data['type'] ? 30 : 60;
				$data   = get_avatar_data( $person_data[1] . '@md5.gravatar.com', array( 'size' => $size ) );
				$size  *= 2;
				$data2x = get_avatar_data( $person_data[1] . '@md5.gravatar.com', array( 'size' => $size ) );
				echo '<img src="' . esc_url( $data['url'] ) . '" srcset="' . esc_url( $data2x['url'] ) . ' 2x" class="gravatar" alt="" />' . "\n";
				echo esc_html( $person_data[0] ) . "</a>\n\t";
				if ( ! $compact ) {
					// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.NonSingularStringLiteralText
					echo '<span class="title">' . translate( $person_data[3] ) . "</span>\n";
				}
				echo "</li>\n";
			}
			echo "</ul>\n";
			break;
	}
}
>>>>>>> 8cc49330a4 (Coding Standards: Fix the minor `WordPress.WP.I18n` violations.)

?>
</div>
</div>
<?php

require ABSPATH . 'wp-admin/admin-footer.php';

return;
