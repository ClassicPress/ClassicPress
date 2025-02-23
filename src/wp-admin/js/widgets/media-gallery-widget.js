/**
 * @output wp-admin/js/widgets/media-gallery-widget.js
 */

/* eslint consistent-this: [ "error", "control" ] */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {

	/**
	 * Open the media select frame to chose an item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectMedia( widget ) {
		var mediaUploader,
			embedded = false;

		if ( mediaUploader ) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media( {
			frame: 'post',
			state: 'gallery',
			title: wp.media.view.l10n.createGalleryTitle,
			button: {
				text: wp.media.view.l10n.createGalleryButton
			},
			multiple: true,
			library: {
				type: 'image'
			}
		} );

		// Remove irrelevant buttons
		mediaUploader.on( 'ready', function() {
			if ( mediaUploader.el.querySelector( '#menu-item-insert' ) ) {
				mediaUploader.el.querySelector( '#menu-item-insert' ).remove();
			}
			if ( mediaUploader.el.querySelector( '#menu-item-playlist' ) ) {
				mediaUploader.el.querySelector( '#menu-item-playlist' ).remove();
			}
			if ( mediaUploader.el.querySelector( '#menu-item-video-playlist' ) ) {
				mediaUploader.el.querySelector( '#menu-item-video-playlist' ).remove();
			}
			if ( mediaUploader.el.querySelector( '#menu-item-embed' ) ) {
				mediaUploader.el.querySelector( '#menu-item-embed' ).remove();
			}
		} );

		mediaUploader.on( 'update', function( selection ) {
			var ul, fieldset,
				imageIds = selection.map( function( attachment ) {
				return attachment.id;
			} );

			ul = document.createElement( 'ul' );
			ul.className = 'media-widget-gallery-preview';

			fieldset = document.createElement( 'fieldset' );
			fieldset.className = 'media-widget-buttons';
			fieldset.innerHTML = '<button type="button" class="button edit-media selected" data-edit-nonce="" style="margin-top:0;">Edit Gallery</button>';

			widget.querySelector( 'input[data-property="ids"]').value = imageIds.join( ',' );
			widget.querySelector( '.attachment-media-view' ).replaceWith( ul );
			updateGalleryPreview( widget.querySelector( '.media-widget-gallery-preview' ), imageIds );
			widget.querySelector( '.media-widget-preview' ).after( fieldset );

			// Activate Save/Publish button
			if ( document.body.className.includes( 'widgets-php' ) ) {
				widget.classList.add( 'widget-dirty' );
			}
			widget.dispatchEvent( new Event( 'change' ) );
		} );

		mediaUploader.open();
	}

	/**
	 * Open the media select frame to edit a gallery.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editMedia( widget, ids ) {
		var editGallery = wp.media.gallery.edit( '[gallery ids="' + ids + '"]' ),
			state = editGallery.state();

		// Load gallery settings
		state.frame.el.querySelector( '#gallery-settings-size' ).value = widget.querySelector( 'input[data-property="size"]').value;
		state.frame.el.querySelector( '#gallery-settings-columns' ).value = widget.querySelector( 'input[data-property="columns"]').value;
		state.frame.el.querySelector( '#gallery-settings-link-to' ).value = widget.querySelector( 'input[data-property="link_type"]').value;
		state.frame.el.querySelector( '#gallery-settings-random-order' ).checked = widget.querySelector( 'input[data-property="orderby_random"]').value === 'on' ? true : false;

		// Indicate selected images
		editGallery.on( 'update', function( selection ) {
			var editGallery = wp.media.gallery.frame.el,
				imageIds = selection.map( function( attachment ) {
					return attachment.id;
				} );

			// Update widget with new images selection
			widget.querySelector( 'input[data-property="ids"]').value = imageIds.join( ',' );
			updateGalleryPreview( widget.querySelector( '.media-widget-gallery-preview' ), imageIds );

			// Update gallery settings
			widget.querySelector( 'input[data-property="size"]').value = editGallery.querySelector( '#gallery-settings-size' ).value;
			widget.querySelector( 'input[data-property="columns"]').value = editGallery.querySelector( '#gallery-settings-columns' ).value;
			widget.querySelector( 'input[data-property="link_type"]').value = editGallery.querySelector( '#gallery-settings-link-to' ).value;
			widget.querySelector( 'input[data-property="orderby_random"]').value = editGallery.querySelector( '#gallery-settings-random-order' ).checked === true ? 'on' : '';

			// Activate Save/Publish button
			if ( document.body.className.includes( 'widgets-php' ) ) {
				widget.classList.add( 'widget-dirty' );
			}
			widget.dispatchEvent( new Event( 'change' ) );
		} );
	}

	/**
	 * Update the preview within the media gallery widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateGalleryPreview( previewDiv, attachmentIds ) {
		previewDiv.innerHTML = '';
		attachmentIds.forEach( function( id ) {
			wp.media.attachment( id ).fetch().then( function() {
				var url = wp.media.attachment( id ).get( 'url' ),
					li  = document.createElement( 'li' );

				li.className = 'gallery-item';
				li.innerHTML = '<div class="gallery-icon"><img alt="" src="' + url + '" width="150" height="150"></div>';
				previewDiv.append( li );
			} );
		} );
	}

	/**
	 * Handle clicks on Add Images and Edit Gallery buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var base, ids,
			widget = e.target.closest( '.widget' );

		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_gallery' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					ids = widget.querySelector( 'input[data-property="ids"]' ).value;
					editMedia( widget, ids );
				}
			}
		}
	} );
} );
