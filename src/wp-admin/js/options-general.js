/**
 * Custom Logo Image upload
 * 
 * @Since CP-1.2.0
 */
var login_custom_logo = document.getElementById( 'login_custom_logo' );
var hidden_image_field = document.getElementById( 'hidden-image-field' );
var image_upload_button = document.getElementById( 'image-upload-button' );
var image_delete_button = document.getElementById( 'image-delete-button' );

var custom_uploader_login_custom_logo = wp.media({
	title: 'Select an Image',
	button: {
		text: 'Use this image'
	},
	multiple: false
});

image_upload_button.addEventListener( 'click', function(){
	if( custom_uploader_login_custom_logo ){
		custom_uploader_login_custom_logo.open();
	}
});

custom_uploader_login_custom_logo.on( 'select', function(){
	var attachment = custom_uploader_login_custom_logo.state().get('selection').first().toJSON();
	login_custom_logo.setAttribute( 'width', '200px' );
	login_custom_logo.setAttribute( 'src', attachment.url );
	login_custom_logo.setAttribute( 'alt', attachment.alt );
	login_custom_logo.setAttribute( 'title', attachment.description );
	hidden_image_field.setAttribute( 'value', JSON.stringify( [ { id: attachment.id, url: attachment.url, alt: attachment.alt, title: attachment.description } ] ) );
});

// Reverse all steps above on removal of image.
// TODO: Not working locally. Hmmmmmm!!!!
image_delete_button.addEventListener( 'click', function(){
	alert('remove');
	login_custom_logo.removeAttribute( 'width', '200px' );
	login_custom_logo.removeAttribute( 'src' );
	login_custom_logo.removeAttribute( 'src' );
	login_custom_logo.removeAttribute( 'alt' );
	login_custom_logo.removeAttribute( 'title' );
	hidden_image_field.removeAttribute( 'value' );
});