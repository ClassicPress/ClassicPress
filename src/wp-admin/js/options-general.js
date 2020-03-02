/**
 * Custom Logo Image upload
 * 
 * @Since CP-1.2.0
 */
var login_custom_logo = document.getElementById( 'login_custom_logo' );
var hidden_image_field = document.getElementById( 'hidden-image-field' );
var image_upload_button = document.getElementById( 'image-upload-field' );
var image_delete_button = document.getElementById( 'image-delete-field' );

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