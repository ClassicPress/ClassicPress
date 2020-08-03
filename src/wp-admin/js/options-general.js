/**
 * Custom Logo Image upload
 * 
 * @Since CP-1.2.0
 */
var login_custom_image_id = document.getElementById( 'login_custom_image_id' );
var hidden_image_field = document.getElementById( 'hidden-image-field' );
var image_upload_button = document.getElementById( 'image-upload-button' );
var image_delete_button = document.getElementById( 'image-delete-button' );

var uploader_login_custom_image = wp.media({
	title: 'Select an Image',
	button: {
		text: 'Use this image'
	},
	multiple: false
});

image_upload_button.addEventListener( 'click', function(){
	if( uploader_login_custom_image ){
		uploader_login_custom_image.open();
	}
});

uploader_login_custom_image.on( 'select', function(){
	var attachment = uploader_login_custom_image.state().get('selection').first().toJSON();
	login_custom_image_id.setAttribute( 'width', '200px' );
	login_custom_image_id.setAttribute( 'src', attachment.url );
	login_custom_image_id.setAttribute( 'alt', attachment.alt );
	login_custom_image_id.setAttribute( 'title', attachment.description );
	hidden_image_field.setAttribute( 'value', JSON.stringify( [ { id: attachment.id, url: attachment.url, alt: attachment.alt, title: attachment.description } ] ) );
});

// Reverse all steps above on removal of image.
// TODO: Not working locally. Hmmmmmm!!!!
image_delete_button.addEventListener( 'click', function(){
	alert('remove');
	login_custom_image_id.removeAttribute( 'width', '200px' );
	login_custom_image_id.removeAttribute( 'src' );
	login_custom_image_id.removeAttribute( 'src' );
	login_custom_image_id.removeAttribute( 'alt' );
	login_custom_image_id.removeAttribute( 'title' );
	hidden_image_field.removeAttribute( 'value' );
});