/** Next steps
 * 1. Sanitize the data custom_image_data in hidden field
 * 2. Save the data as a json string
 * 3. Retrieve the data as an option and append the HTML
 *
 * if( isset( $_POST[ 'hidden-image-field' ] ) ) {
 * $image_data = json_decode( stripslashes( $_POST[ 'hidden-image-field' ] ) );
 *
 * if( is_object( $image_data[0] ) ) {
 * 		$image_data = array( 'id' => $image_data[0]->id, 'src' => $image_data[0]->url, 'description' => 			$image_data[0]  ->description, 'alt' => $image_data[0]->alt );
 * } else {
 * 		$image_data = [];
 * }
 *
 * update_option( 'login_custom_image_id' );
 *
 * var_dump(get_option( 'login_custom_image_id' ));
 */

/**
 * Custom Login Image upload
 *
 * @since 1.2.0
 */
jQuery( document ).ready( function( $ ) {
	var $choices  = $( 'input[name=login_custom_image_state]' );
	var $img      = $( '#login_custom_image-img' );
	var $controls = $( '#login_custom_image-controls' );
	var $input    = $( '#login_custom_image-input' );
	var $choose   = $( '#login_custom_image-choose' );
	var $clear    = $( '#login_custom_image-clear' );

	// Show controls for this setting
	$controls.removeClass( 'hidden' );

	var uploader = wp.media( {
		title: 'Select an image',
		button: {
			text: 'Use this image'
		},
		multiple: false
	} );

	$choose.on( 'click', function() {
		uploader.open();
	} );

	uploader.on( 'select', function() {
		var attachment = uploader.state().get( 'selection' ).first().toJSON();
		$img.removeClass( 'hidden' );
		$img.attr( 'src', attachment.url );
		$img.attr( 'alt', attachment.alt );
		$img.attr( 'title', attachment.description );
		$choices.prop( 'disabled', false );
		$input.attr( 'value', attachment.id );
	} );

	// Reverse all steps above on removal of image.
	// TODO: Not working locally. Hmmmmmm!!!!
	$clear.on( 'click', function() {
		$img.addClass( 'hidden' );
		$img.removeAttr( 'src' );
		$img.removeAttr( 'alt' );
		$img.removeAttr( 'title' );
		$input.attr( 'value', '' );
		$choices.each( function( el ) {
			if ( el.value === '0' ) {
				$( el ).click();
			} else {
				$( el ).prop( 'disabled', true );
			}
		} );
	} );
} );
