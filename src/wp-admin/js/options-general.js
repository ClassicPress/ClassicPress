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
		title: window.cpOptionsGeneralStrings.selectAnImage,
		button: {
			text: window.cpOptionsGeneralStrings.useThisImage
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
		if ( $choices.filter( '[value=0]:checked' ).length ) {
			$choices.filter( '[value=1]' ).prop( 'checked', true );
		}
	} );

	$clear.on( 'click', function() {
		$img.addClass( 'hidden' );
		$img.removeAttr( 'src' );
		$img.removeAttr( 'alt' );
		$img.removeAttr( 'title' );
		$input.attr( 'value', '' );
		$choices.each( function() {
			if ( this.value === '0' ) {
				$( this ).prop( 'checked', true );
			} else {
				$( this ).prop( 'disabled', true );
			}
		} );
	} );
} );
