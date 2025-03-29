/* eslint consistent-this: [ "error", "control" ] */
/* global ajaxurl, IMAGE_WIDGET, console */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var dialog = document.getElementById( 'widget-modal' ),
		closeButton = document.getElementById( 'media-modal-close' );

	/**
	 * Open the media select frame to chose an item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectMedia( widget ) {
		var mediaUploader,
			embedded = false,
			currentImageId = widget.querySelector( '[data-property="attachment_id"]' ).value;

		if ( mediaUploader ) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media( {
			multiple: false,
			library: {
				type: 'image'
			},
			states: [
				new wp.media.controller.Library( {
					library: wp.media.query({ type: 'image' }),
					multiple: false,
					priority: 20
				} ),
				new wp.media.controller.Embed( {
					id: 'embed',
					priority: 30
				} )
			]
		} );

		// This runs once the modal is open
		mediaUploader.on( 'ready', function() {
			var separator = document.createElement( 'div' ),
				addButton = mediaUploader.el.querySelector( '.media-button-select' ),
				libraryButton = mediaUploader.el.querySelector( '#menu-item-library' ),
				selection = mediaUploader.state().get( 'selection' ),
				attachment = wp.media.attachment( currentImageId );

			separator.className = 'separator';
			separator.setAttribute( 'role', 'presentation' );
			libraryButton.after( separator );

			// Prevent duplicate separator lines
			if ( mediaUploader.el.querySelectorAll( '.separator' ).length > 1 ) {
				mediaUploader.el.querySelectorAll( '.separator' )[1].remove();
			}

			// Set the current image as selected when opening the media library
			attachment.fetch();
			selection.add( attachment ? [attachment] : [] );
			if ( attachment.id === '0' ) {
				addButton.setAttribute( 'disabled', true );
			}

			// Insert image from URL
			mediaUploader.el.querySelector( '#menu-item-embed' ).addEventListener( 'click', function() {
				var embedAlt, embedCaption, embedLinkTo, embedButtons,
					embed = document.createElement( 'div' ),
					linkType = widget.querySelector( '[data-property="link_type"]' ).value;

				embed.className = 'media-embed';
				embed.innerHTML = '<span class="embed-url">' +
					'<input id="embed-url-field" type="url" aria-label="' + mediaUploader.el.querySelector( 'h1' ).textContent + '" placeholder="https://" value="' + widget.querySelector( '[data-property="url"]' ).value + '">' +
					'<span class="spinner"></span>' +
					'</span>' +
					'<div class="embed-media-settings">' +
					'<div class="wp-clearfix">' +
					'<div class="thumbnail">' +
					'<img src="" draggable="false" alt="">' +
					'</div>' +
					'</div>' +
					'<span class="setting alt-text has-description">' +
					'<label for="embed-image-settings-alt-text" class="name">Alternative Text</label>' +
					'<textarea id="embed-image-settings-alt-text" data-setting="alt" aria-describedby="alt-text-description">' + widget.querySelector( '[data-property="alt"]' ).value + '</textarea>' +
					'</span>' +
					'<p class="description" id="alt-text-description"><a href="https://www.w3.org/WAI/tutorials/images/decision-tree/" target="_blank">Learn how to describe the purpose of the image<span class="screen-reader-text"> (opens in a new tab)</span></a>. Leave empty if the image is purely decorative.</p>' +
					'<span class="setting caption">' +
					'<label for="embed-image-settings-caption" class="name">Caption</label>' +
					'<textarea id="embed-image-settings-caption" data-setting="caption">' + widget.querySelector( '[data-property="caption"]' ).value + '</textarea>' +
					'</span>' +
					'<fieldset class="setting-group">' +
					'<legend class="name">Link To</legend>' +
					'<span class="setting link-to">' +
					'<span class="button-group button-large" data-setting="link">' +
					'<button class="button" value="file" aria-pressed="false">Image URL</button>' +
					'<button class="button" value="custom" aria-pressed="false">Custom URL</button>' +
					'<button class="button" value="none" aria-pressed="false">None</button>' +
					'</span>' +
					'</span>' +
					'<span class="setting hidden">' +
					'<label for="embed-image-settings-link-to-custom" class="name">URL</label>' +
					'<input type="text" id="embed-image-settings-link-to-custom" class="link-to-custom" data-setting="linkUrl" value="' + widget.querySelector( '[data-property="link_url"]' ).value + '">' +
					'</span>' +
					'</fieldset>' +
					'</div>';

				if ( embedded === false ) {
					mediaUploader.el.querySelector( '.media-frame-content' ).append( embed );
					embedded = true;
				}
				if ( mediaUploader.el.querySelector( '.uploader-inline' ) ) {
					mediaUploader.el.querySelector( '.uploader-inline' ).remove();
				}
				if ( mediaUploader.el.querySelector( '.attachments-browser' ) ) {
					mediaUploader.el.querySelector( '.attachments-browser' ).remove();
				}

				mediaUploader.el.querySelector( '#embed-url-field' ).addEventListener( 'change', function( e ) {
					var fields   = mediaUploader.el.querySelectorAll( '.embed-media-settings input, .embed-media-settings textarea' ),
						url      = e.target.value,
						fileType = url.split( '.' ).pop(),
						error    = document.createElement( 'div' ),
						message  = mediaUploader.el.querySelector( '#message' ),
						img      = new Image();

					// Check if URL is valid
					img.src = url;
					img.onerror = function() {
						if ( message ) {
							message.remove();
						}
						error.id = 'message';
						error.className = 'notice-error is-dismissible';
						error.innerHTML = '<p style="color:#fff;background:red;font-size:1em;padding:0.5em 1em;">' + IMAGE_WIDGET.wrong_url + '</p>';
						e.target.before( error );

						// Onerror event fires twice, so remove duplicate message
						if ( mediaUploader.el.querySelectorAll( '.notice-error' )[1] ) {
							mediaUploader.el.querySelectorAll( '.notice-error' )[1].remove();
						}

						// Disable other fields until the issue is corrected
						addButton.setAttribute( 'disabled', true );
						fields.forEach( function( input ) {
							input.setAttribute( 'disabled', true );
						} );
					};
					img.onload = function() {
						widget.querySelector( '[data-property="width"]' ).value = img.width;
						widget.querySelector( '[data-property="height"]' ).value = img.height;
					};

					// Load image
					embed.querySelector( '.media-embed img').src = url;
					widget.querySelector( '[data-property="url"]' ).value = url;
					if ( IMAGE_WIDGET.image_file_types.includes( fileType.toLowerCase() ) ) {
						if ( message ) {
							message.remove();
							fields.forEach( function( input ) {
								input.removeAttribute( 'disabled' );
							} );
						}

						// Set attachment ID to zero
						widget.querySelector( '[data-property="attachment_id"]' ).value = 0;

						// Activate Add to widget button
						if ( url != '' && addButton.disabled ) {
							addButton.removeAttribute( 'disabled' );
							addButton.textContent = IMAGE_WIDGET.add_to_widget;
						}
					} else {
						if ( message ) {
							message.remove();
						}
						error.id = 'message';
						error.className = 'notice-error is-dismissible';
						error.innerHTML = '<p style="color:#fff;background:red;font-size:1em;padding:0.5em 1em;">' + IMAGE_WIDGET.unsupported_file_type + '</p>';
						e.target.before( error );

						// Disable other fields until the issue is corrected
						addButton.setAttribute( 'disabled', true );
						fields.forEach( function( input ) {
							input.setAttribute( 'disabled', true );
						} );
					}
				} );

				// Update values in hidden fields
				embedAlt     = mediaUploader.el.querySelector( '#embed-image-settings-alt-text' );
				embedCaption = mediaUploader.el.querySelector( '#embed-image-settings-caption' );
				embedLinkTo  = mediaUploader.el.querySelector( '#embed-image-settings-link-to-custom' );
				embedButtons = mediaUploader.el.querySelectorAll ( '.link-to button' );

				embedAlt.addEventListener( 'change', function() {
					widget.querySelector( '[data-property="alt"]' ).value = embedAlt.value;
				} );

				embedCaption.addEventListener( 'change', function() {
					widget.querySelector( '[data-property="caption"]' ).value = embedCaption.value;
				} );

				// Set type and URL of image hyperlink
				embedButtons.forEach( function( embedButton ) {
					if ( embedButton.value === linkType ) {
						embedButton.classList.add( 'active' );
						embedButton.setAttribute( 'aria-pressed', true );
						if ( linkType === 'custom' ) {
							embedLinkTo.parentNode.classList.remove( 'hidden' );
						} else {
							embedLinkTo.parentNode.classList.add( 'hidden' );
						}
					} else {
						embedButton.classList.remove( 'active' );
						embedButton.setAttribute( 'aria-pressed', false );
					}

					embedButton.addEventListener( 'click', function() {
						embedButtons.forEach( function( link ) {
							link.classList.remove( 'active' );
							link.setAttribute( 'aria-pressed', false );
						} );
						embedButton.classList.add( 'active' );
						embedButton.setAttribute( 'aria-pressed', true );
						widget.querySelector( '[data-property="link_type"]' ).value = embedButton.value;
						if ( embedButton.value === 'custom' ) {
							embedLinkTo.parentNode.classList.remove( 'hidden' );
							embedLinkTo.addEventListener( 'change', function() {
								widget.querySelector( '[data-property="link_url"]' ).value = embedLinkTo.value;
							} );
						} else {
							embedLinkTo.parentNode.classList.add( 'hidden' );
							embedLinkTo.value = '';
							if ( embedButton.value === 'file' ) {
								widget.querySelector( '[data-property="link_url"]' ).value = mediaUploader.el.querySelector( '#embed-url-field' ).value;
							} else {
								widget.querySelector( '[data-property="link_url"]' ).value = '';
							}
						}

						// Activate Add to widget button
						if ( mediaUploader.el.querySelector( '#embed-url-field' ).value != '' && addButton.disabled ) {
							addButton.removeAttribute( 'disabled' );
							addButton.textContent = IMAGE_WIDGET.add_to_widget;
						}
					} );
				} );
			} );

			libraryButton.addEventListener( 'click', function() {
				embedded = false;
				addButton.textContent = IMAGE_WIDGET.add_to_widget;
			} );
		} );

		// Insert image from media library
		mediaUploader.on( 'select', function() {
			var formData,
				attachment = mediaUploader.state().get( 'selection' ) ? mediaUploader.state().get( 'selection' ).first().toJSON() : '',
				image      = document.createElement( 'img' ),
				alt        = widget.querySelector( 'input[data-property="alt"]' ).value,
				buttons	   = document.createElement( 'div' ),
				fieldset   = document.createElement( 'fieldset' ),
				number     = widget.querySelector( '.widget_number' ).value,
				nonce      = widget.querySelector( '.edit-media' ) ? widget.querySelector( '.edit-media' ).dataset.editNonce : '';

				// Obtain correct nonce when adding image for the first time according to whether using Customizer or not
				if ( nonce === '' ) {
					if ( document.body.className.includes( ' wp-customizer' ) ) {
						nonce = document.getElementById( '_ajax_linking_nonce' ).value;
					} else {
						nonce = document.getElementById( '_wpnonce_widgets' ).value;
					}
				}

			image.className = 'attachment-thumb';
			image.src = attachment ? attachment.url : widget.querySelector( '[data-property="url"]' ).value;
			image.setAttribute( 'draggable', false );
			image.alt = alt;
			if ( alt === '' ) {
				image.setAttribute( 'aria-label', IMAGE_WIDGET.aria_label + image.src.split( '/' ).pop() );
			}

			// Add Edit and Replace buttons
			buttons.className = 'media-widget-buttons';
			buttons.innerHTML = '<button type="button" class="button edit-media">' + IMAGE_WIDGET.edit_image + '</button>' +
				'<button type="button" class="button change-media select-media">' + IMAGE_WIDGET.replace_image + '</button>';

			// Add Link field
			fieldset.className = 'media-widget-image-link';
			fieldset.innerHTML = '<label for="widget-media_image-' + number + '-link_url">Link to:</label>' +
				'<input id="widget-media_image-' + number + '-link_url" name="widget-media_image[' + number + '][link_url]" class="widefat" type="url" value="" placeholder="https://" data-property="link_url">';

			// Insert image according to whether this is a new insertion or replacement
			if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
				widget.querySelector( '.media_image' ).prepend( image );
				widget.querySelector( '.attachment-media-view' ).remove();
				widget.querySelector( '.media-widget-preview' ).after( buttons );
				buttons.after( fieldset );
			} else { // replacement
				widget.querySelector( '.attachment-thumb' ).replaceWith( image );
			}

			// Update values in other hidden fields for uploads from media library
			if ( attachment !== '' ) {
				widget.querySelector( '[data-property="attachment_id"]' ).value = attachment.id;
				widget.querySelector( '[data-property="url"]' ).value = attachment.url;

				// Use the Fetch API to get details of the image
				formData = new FormData();
				formData.append( 'action', 'get-attachment' );
				formData.append( '_ajax_nonce', nonce );
				formData.append( 'id', attachment.id );

				fetch( ajaxurl, {
					method: 'POST',
					credentials: 'same-origin',
					body: formData
				} )
				.then( function( response ) {
					if ( response.ok ) {
						return response.json();
					}
					throw new Error( 'Network response was not ok' );
				} )
				.then( function( result ) {
					var sizeOptions = '',
						sizes = result.data.sizes,
						sizesInput = widget.querySelector( '[data-property="size_options"]' );

					// Update available size options in hidden field
					for ( var dimension in sizes ) {
						sizeOptions += '<option value="' + dimension + '">' + dimension[0].toUpperCase() + dimension.slice(1) + ' – ' + sizes[dimension].width + ' x ' + sizes[dimension].height + '</option>';
					}
					sizesInput.value = sizeOptions;
				} )
				.catch( function( error ) {
					console.error( 'Error:', error );
				} );
			}

			// Activate Save/Publish button
			if ( document.body.className.includes( 'widgets-php' ) ) {
				widget.classList.add( 'widget-dirty' );
			}
			widget.dispatchEvent( new Event( 'change' ) );
		} );

		mediaUploader.open();
	}

	/**
	 * Open the media frame to modify the selected item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editMedia( widget ) {
		var	imageSize, linkTo, linkToUrl, editOriginal, widthField, heightField, customSizeField,
			size            = widget.querySelector( 'input[data-property="size"]' ).value,
			width           = widget.querySelector( 'input[data-property="width"]' ).value,
			height          = widget.querySelector( 'input[data-property="height"]' ).value,
			caption         = widget.querySelector( 'input[data-property="caption"]' ).value,
			alt             = widget.querySelector( 'input[data-property="alt"]' ).value,
			linkType        = widget.querySelector( 'input[data-property="link_type"]' ).value,
			linkUrl         = widget.querySelector( 'input[data-property="link_url"]' ).value,
			imageClasses    = widget.querySelector( 'input[data-property="image_classes"]' ).value,
			linkClasses     = widget.querySelector( 'input[data-property="link_classes"]' ).value,
			linkRel         = widget.querySelector( 'input[data-property="link_rel"]' ).value,
			linkTargetBlank = widget.querySelector( 'input[data-property="link_target_blank"]' ).value,
			linkImageTitle  = widget.querySelector( 'input[data-property="link_image_title"]' ).value,
			attachmentId    = widget.querySelector( 'input[data-property="attachment_id"]' ).value,
			url             = widget.querySelector( 'input[data-property="url"]' ).value,
			sizeOptions     = widget.querySelector( 'input[data-property="size_options"]' ).value,
			template        = document.getElementById( 'tmpl-edit-image-modal' ),
			clone           = template.content.cloneNode( true );

		// Append cloned template and establish new variables
		dialog.querySelector( '.media-modal' ).append( clone );
		imageSize       = dialog.querySelector( '#image-details-size' );
		linkTo          = dialog.querySelector( '#image-details-link-to' );
		linkToUrl       = dialog.querySelector( '#link-to-url' );
		editOriginal    = dialog.querySelector( '#edit-original' );
		widthField      = dialog.querySelector( '#image-details-size-width' );
		heightField     = dialog.querySelector( '#image-details-size-height' );
		customSizeField = imageSize.closest( 'fieldset' ).querySelector( '.custom-size' );

		// Set available sizes select dropdown
		imageSize.insertAdjacentHTML( 'afterbegin', sizeOptions );
		if ( size === 'custom' ) {
			customSizeField.classList.remove( 'hidden' );
			widthField.value = width;
			heightField.value = height;
		} else {
			customSizeField.classList.add( 'hidden' );
			widthField.value = '';
			heightField.value = '';
		}
		imageSize.querySelector( 'option[value="' + size + '"]' ).setAttribute( 'selected', true );

		imageSize.addEventListener( 'change', function() {
			var selectedOption = imageSize.options[imageSize.selectedIndex];
			imageSize.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'custom' ) {
				customSizeField.classList.remove( 'hidden' );
				widthField.value = width;
				heightField.value = height;
			} else {
				customSizeField.classList.add( 'hidden' );
				widthField.value = '';
				heightField.value = '';
			}
		} );

		// Show or hide Custom URL depending on link type
		if ( linkType ) {
			linkTo.querySelector( 'option[selected]' ).removeAttribute( 'selected' );
			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				if ( option.value === linkType ) {
					option.setAttribute( 'selected', 'selected' );
				}
			} );
			if ( linkType === 'custom' ) {
				linkToUrl.removeAttribute( 'hidden' );
				linkToUrl.removeAttribute( 'inert' );
			}
		}

		// Widget-specific details
		dialog.querySelector( '#image-details-alt-text' ).value = alt;
		dialog.querySelector( '#image-details-caption' ).value = caption;
		dialog.querySelector( '#image-details-title-attribute' ).value = linkImageTitle;
		dialog.querySelector( '#image-details-link-to-custom' ).value = linkUrl;
		dialog.querySelector( '#image-details-css-class' ).value = imageClasses;
		dialog.querySelector( '#image-details-link-target' ).value = linkTargetBlank;
		dialog.querySelector( '#image-details-link-rel' ).value = linkRel;
		dialog.querySelector( '#image-details-link-css-class' ).value = linkClasses;

		// Add correct links
		dialog.querySelector( '.column-image img' ).src = url;
		if ( editOriginal != null ) {
			editOriginal.setAttribute( 'data-href', editOriginal.dataset.href.replace( 'item=xxx', 'item=' + attachmentId ) );
			editOriginal.setAttribute( 'data-widget-id', widget.id );
		}
		dialog.showModal();

		// Show and hide URL field if Custom URL chosen or deselected
		linkTo.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'custom' ) {
				linkToUrl.removeAttribute( 'hidden' );
				linkToUrl.removeAttribute( 'inert' );
			} else {
				linkToUrl.setAttribute( 'hidden', true );
				linkToUrl.setAttribute( 'inert', true );
			}
		} );

		// Update image details
		dialog.querySelector( '.media-button-select' ).addEventListener( 'click', function() {
			updateImageDetails( widget );
		} );
	}

	/**
	 * Trigger change event in widget to enable update of image details.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateImageDetails( widget ) {
		var size = dialog.querySelector( '#image-details-size' ),
			selectedOption = size.options[size.selectedIndex].text;

		widget.querySelector( '[data-property="alt"]' ).value = dialog.querySelector( '#image-details-alt-text' ).value,
		widget.querySelector( '[data-property="caption"]' ).value = dialog.querySelector( '#image-details-caption' ).value,
		widget.querySelector( '[data-property="size"]' ).value = size.value,
		widget.querySelector( '[data-property="link_image_title"]' ).value = dialog.querySelector( '#image-details-title-attribute' ).value,
		widget.querySelector( '[data-property="link_type"]' ).value = dialog.querySelector( '#image-details-link-to' ).value,
		widget.querySelector( '[data-property="link_url"]' ).value = dialog.querySelector( '#image-details-link-to-custom' ).value,
		widget.querySelector( '[data-property="image_classes"]' ).value = dialog.querySelector( '#image-details-css-class' ).value,
		widget.querySelector( '[data-property="link_target_blank"]' ).value = dialog.querySelector( '#image-details-link-target' ).checked ? '_blank' : '',
		widget.querySelector( '[data-property="link_rel"]' ).value = dialog.querySelector( '#image-details-link-rel' ).value,
		widget.querySelector( '[data-property="link_classes"]' ).value = dialog.querySelector( '#image-details-link-css-class' ).value;

		if ( dialog.querySelector( '#image-details-size' ).value === 'custom' ) {
			widget.querySelector( '[data-property="width"]' ).value = dialog.querySelector( '#image-details-size-width' ).value;
			widget.querySelector( '[data-property="height"]' ).value = dialog.querySelector( '#image-details-size-height' ).value;
		} else {
			widget.querySelector( '[data-property="width"]' ).value = parseInt( selectedOption.split( ' – ' )[1] );
			widget.querySelector( '[data-property="height"]' ).value = parseInt( selectedOption.split( ' x ' )[1] );
		}

		dialog.close();
		dialog.querySelector( '#image-modal-content' ).remove();

		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.dispatchEvent( new Event( 'change' ) );
	}

	/**
	 * Insert a new media frame within the modal to enable editing of image.
	 *
	 * @abstract
	 * @return {void}
	 */
	function imageEdit( widgetID ) {
		var formData,
			attachmentID = document.querySelector( '#' + widgetID + ' input[data-property="attachment_id"]' ).value,
			editButton = document.querySelector( '#' + widgetID + ' .edit-media' ),
			nonce = editButton.dataset.editNonce;

		formData = new FormData();
		formData.append( 'action', 'image-editor' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'postid', attachmentID );
		formData.append( 'do', 'rotate-cw' );

		// Make the fetch request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			dialog.querySelector( '.media-embed' ).style.display = 'none';
			dialog.querySelector( '.image-details' ).insertAdjacentHTML( 'beforeend', result.data.html );

			// Cancel
			dialog.querySelector( '.imgedit-cancel-btn' ).addEventListener( 'click', function() {
				dialog.querySelector( '.media-embed' ).style.display = '';
			} );

			// Submit changes
			dialog.querySelector( '.imgedit-submit-btn' ).addEventListener( 'click', function() {
				document.getElementById( widgetID ).dispatchEvent( new Event( 'change' ) );
				closeButton.click();
				editButton.focus();
			} );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
		} );
	}

	/**
	 * Handle clicks on Add, Edit, and Replace Image buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var base,
			widget = e.target.closest( '.widget' );

		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_image' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editMedia( widget );
				}
			}

			// Set focus after closing modal using Escape key
			document.addEventListener( 'keydown', function( e ) {
				var details = widget.querySelector( 'details' );
				if ( dialog.hasAttribute( 'open' ) && e.key === 'Escape' ) {
					dialog.close();
					dialog.querySelector( '#image-modal-content' ).remove();
					setTimeout( function() {
						details.open = true;
					}, 100 );
					details.addEventListener( 'toggle', function( e ) {
						if ( e.target.open === true ) {
							widget.querySelector( '.edit-media' ).focus();
						}
					} );
				}
			} );
		} else if ( e.target.id === 'edit-original' ) {
			imageEdit( e.target.dataset.widgetId );
		}
	} );

	/**
	 * Close modal by clicking button.
	 *
	 * @abstract
	 * @return {void}
	 */
	closeButton.addEventListener( 'click', function() {
		dialog.close();
		if ( dialog.querySelector( '#image-modal-content' ) ) {
			dialog.querySelector( '#image-modal-content' ).remove();
		}
		if ( dialog.querySelector( '#new-image-modal' ) ) {
			dialog.querySelector( '#new-image-modal' ).remove();
		}
	} );
} );
