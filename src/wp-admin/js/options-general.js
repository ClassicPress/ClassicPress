/**
 * Custom Login Image upload
 *
 * @since 1.2.0
 */
jQuery( document ).ready( function( $ ) {
	var $row      = $( '#login_custom_image-row' );
	var $choices  = $( 'input[name=login_custom_image_state]' );
	var $img      = $( '#login_custom_image-img' );
	var $controls = $( '#login_custom_image-controls' );
	var $input    = $( '#login_custom_image-input' );
	var $choose   = $( '#login_custom_image-choose' );
	var $clear    = $( '#login_custom_image-clear' );

	// Show controls for this setting
	$controls.removeClass( 'hidden' );

	// Enable all choices. An inline notice will be shown if an invalid choice
	// is selected.
	$choices.prop( 'disabled', false );

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
		$row.find( '.login_custom_image-notice' ).remove();
		var attachment = uploader.state().get( 'selection' ).first().toJSON();
		$img.removeClass( 'hidden' );
		$img.attr( 'src', attachment.url );
		$img.attr( 'alt', attachment.alt );
		$img.attr( 'title', attachment.description );
		$input.val( attachment.id );
		if ( $choices.filter( '[value=0]:checked' ).length ) {
			$choices.filter( '[value=1]' ).prop( 'checked', true );
		}
	} );

	$clear.on( 'click', function() {
		$row.find( '.login_custom_image-notice' ).remove();
		$img.addClass( 'hidden' );
		$img.removeAttr( 'src' );
		$img.removeAttr( 'alt' );
		$img.removeAttr( 'title' );
		$input.val( '' );
		$choices.filter( '[value=0]' ).prop( 'checked', true );
	} );

	$choices.on( 'click', function() {
		$row.find( '.login_custom_image-notice' ).remove();
		var inputVal = $input.val();
		if ( $( this ).val() !== '0' && ( inputVal === '' || inputVal === '0' ) ) {
			var $notice = $( '<div class="notice error login_custom_image-notice">' );
			$notice.text( window.cpOptionsGeneralStrings.chooseAnImage );
			$( this ).closest( 'label' ).append( $notice );
		}
	} );

	// If the user clicks an invalid choice then refreshes the page, that
	// choice will still be selected. Make sure the notice shows in this case.
	$choices.filter( ':checked' ).trigger( 'click' );
} );
