<?php
/**
 * @package ClassicPress
 * @subpackage Theme_Compat
 * @deprecated WP-3.0.0
 *
 * This file is here for backward compatibility with old themes and will be removed in a future version
 */
_deprecated_file(
	/* translators: %s: template name */
	sprintf( __( 'Theme without %s' ), basename( __FILE__ ) ),
	'WP-3.0.0',
	null,
	/* translators: %s: template name */
	sprintf( __( 'Please include a %s template in your theme.' ), basename( __FILE__ ) )
);
?>

<hr />
<div id="footer" role="contentinfo">
<!-- Having the "powered by" link on your site is a great way to support ClassicPress. -->
	<p>
		<?php
		printf(
			/* translators: 1: blog name, 2: ClassicPress */
			__( '%1$s is proudly powered by %2$s' ),
			get_bloginfo('name'),
			'<a href="https://www.classicpress.net/">ClassicPress</a>'
		);
		?>
	</p>
</div>
</div>

<!-- Gorgeous design by Michael Heilemann - http://binarybonsai.com/kubrick/ -->
<?php /* "Just what do you think you're doing Dave?" */ ?>

		<?php wp_footer(); ?>
</body>
</html>
