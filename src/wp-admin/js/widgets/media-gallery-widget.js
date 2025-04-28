/* eslint consistent-this: [ "error", "control" ] */
/* global ajaxurl, GALLERY_WIDGET, Sortable, console */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var addButton,
		selectedIds = [],
		dialog = document.getElementById( 'widget-modal' );

	/**
	 * Update details within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setAddedMediaFields( id ) {
		var form = document.createElement( 'form' );
		form.className = 'compat-item';
		form.innerHTML = '<input type="hidden" id="menu-order" name="attachments[' + id + '][menu_order]" value="0">' +
			'<p class="media-types media-types-required-info"><span class="required-field-message">Required fields are marked <span class="required">*</span></span></p>' +
			'<div class="setting" data-setting="media_category">' +
				'<label for="attachments-' + id + '-media_category" style="width:30%;">' +
					'<span class="alignleft">Media Categories</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_category" name="attachments[' + id + '][media_category]" value="">' +
			'</div>' +
			'<div class="setting" data-setting="media_post_tag">' +
				'<label for="attachments-' + id + '-media_post_tag">' +
					'<span class="alignleft">Media Tags</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_post_tag" name="attachments[' + id + '][media_post_tag]" value="">' +
			'</div>';

		if ( document.querySelector( '.compat-item' ) != null ) {
			document.querySelector( '.compat-item' ).remove();
		}
		document.querySelector( '.attachment-compat' ).append( form );
	}

	/**
	 * Update attachment details.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateDetails( input, id ) {
		var successTimeout,
			data = new FormData();

		data.append( 'action', 'save-attachment' );
		data.append( 'id', id );
		data.append( 'nonce', document.getElementById( 'media-' + id ).dataset.updateNonce );

		// Append metadata fields
		if ( input.parentNode.dataset.setting === 'alt' || input.id === 'embed-image-settings-alt-text' ) {
			data.append( 'changes[alt]', input.value );
		} else if ( input.parentNode.dataset.setting === 'title' ) {
			data.append( 'changes[title]', input.value );
		} else if ( input.parentNode.dataset.setting === 'caption' || input.id === 'embed-image-settings-caption' ) {
			data.append( 'changes[caption]', input.value );
		} else if ( input.parentNode.dataset.setting === 'description' ) {
			data.append( 'changes[description]', input.value );
		}

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			var saved = dialog.querySelector( '#details-saved' );

			if ( result.success ) {

				// Update data attributes
				if ( input.parentNode.dataset.setting === 'alt' ) {
					document.getElementById( 'media-' + id ).querySelector( 'img' ).setAttribute( 'alt', input.value );
				} else if ( input.parentNode.dataset.setting === 'title' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'aria-label', input.value );
				} else if ( input.parentNode.dataset.setting === 'caption' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-caption', input.value );
				} else if ( input.parentNode.dataset.setting === 'description' ) {
					document.getElementById( 'media-' + id ).setAttribute( 'data-description', input.value );
				}

				// Show success visual feedback.
				clearTimeout( successTimeout );
				saved.classList.remove( 'hidden' );
				saved.setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					saved.classList.add( 'hidden' );
					saved.setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( GALLERY_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Update media categories and tags.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateMediaTaxOrTag( input, id ) {
		var successTimeout, newTaxes,
			data = new FormData(),
			taxonomy = input.getAttribute( 'name' ).replace( 'attachments[' + id + '][' , '' ).replace( ']', '' );

		data.append( 'action', 'save-attachment-compat' );
		data.append( 'nonce', document.getElementById( 'media-' + id ).dataset.updateNonce );
		data.append( 'id', id );
		data.append( 'taxonomy', taxonomy );
		data.append( 'attachments[' + id + '][' + taxonomy + ']', input.value );

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {
				if ( taxonomy === 'media_category' ) {
					newTaxes = result.data.media_cats.join( ', ' );
					input.value = newTaxes;
					document.getElementById( 'media-' + id ).setAttribute( 'data-taxes', newTaxes );
				} else if ( taxonomy === 'media_tag' ) {
					newTaxes = result.data.media_tags.join( ', ' );
					input.value = newTaxes;
					document.getElementById( 'media-' + id ).setAttribute( 'data-tags', newTaxes );
				}

				// Show success visual feedback.
				clearTimeout( successTimeout );
				document.getElementById( 'tax-saved' ).classList.remove( 'hidden' );
				document.getElementById( 'tax-saved' ).setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					document.getElementById( 'tax-saved' ).classList.add( 'hidden' );
					document.getElementById( 'tax-saved' ).setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( GALLERY_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Handles media list copy media URL button.
	 *
	 * Uses Clipboard API (with execCommand fallback for sites
	 * on neither https nor localhost).
	 *
	 * @since CP-2.5.0
	 *
	 * @param {MouseEvent} event A click event.
	 * @return {void}
	 */
	function copyToClipboard( button ) {
		var copyAttachmentURLSuccessTimeout,
			copyText = dialog.querySelector( '#attachment-details-copy-link' ).value,
			input = document.createElement( 'input' );

		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( copyText );
		} else {
			document.body.append( input );
			input.value = copyText;
			input.select();
			document.execCommand( 'copy' );
		}

		// Show success visual feedback.
		clearTimeout( copyAttachmentURLSuccessTimeout );
		button.nextElementSibling.classList.remove( 'hidden' );
		input.remove();

		// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
		copyAttachmentURLSuccessTimeout = setTimeout( function() {
			button.nextElementSibling.classList.add( 'hidden' );
		}, 3000 );

		// Handle success audible feedback.
		wp.a11y.speak( wp.i18n.__( 'The file URL has been copied to your clipboard' ) );
	}

	/**
	 * Delete attachment from within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function deleteItem( id ) {
		var data = new URLSearchParams( {
			action: 'delete-post',
			_ajax_nonce: document.getElementById( 'media-' + id ).dataset.deleteNonce,
			id: id
		} );

		fetch( ajaxurl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result === 1 ) {
				closeModal();
			} else {
				console.log( GALLERY_WIDGET.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Select and deselect media items for adding to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectItemToAdd( item, widget, clicked ) {
		var selectedItems = dialog.querySelectorAll( '.widget-modal-grid .selected' ),
			id = item.dataset.id,
			title = item.getAttribute( 'aria-label' ),
			date = item.dataset.date,
			filename = item.dataset.filename,
			size = item.dataset.size,
			width = item.dataset.width,
			height = item.dataset.height,
			caption = item.dataset.caption,
			description = item.dataset.description,
			taxes = item.dataset.taxes,
			tags = item.dataset.tags,
			url = item.dataset.url,
			sizes = item.dataset.sizes,
			sizeOptions = '',
			sizesObject = JSON.parse( sizes ),
			alt = item.querySelector( 'img' ).getAttribute( 'alt' ),
			updateNonce = item.dataset.updateNonce,
			deleteNonce = item.dataset.deleteNonce,
			linkType = widget.querySelector( '[data-property="link_type"]' ).value,
			linkUrl = widget.querySelector( '[data-property="link_url"]' ),
			inputs = dialog.querySelectorAll( '.widget-modal-right-sidebar input, .widget-modal-right-sidebar textarea, .widget-modal-media-embed input, .widget-modal-media-embed textarea' ),
			selects = dialog.querySelectorAll( '.widget-modal-right-sidebar select, .widget-modal-media-embed select' ),
			linkTo = dialog.querySelector( '#attachment-display-settings-link-to' ),
			linkToCustom = dialog.querySelector( '#attachment-display-settings-link-to-custom' );

		// Update available size options in hidden field
		for ( var dimension in sizesObject ) {
			sizeOptions += '<option value="' + dimension + '">' + dimension[0].toUpperCase() + dimension.slice(1) + ' – ' + sizesObject[dimension].width + ' x ' + sizesObject[dimension].height + '</option>';
		}

		// Set menu_order, media_category, and media_post_tag field IDs correctly
		setAddedMediaFields( id );

		// Populate modal with attachment details
		dialog.querySelector( '.attachment-date' ).textContent = date;
		dialog.querySelector( '.attachment-filename' ).textContent = filename;
		dialog.querySelector( '.attachment-filesize' ).textContent = size;
		dialog.querySelector( '.attachment-dimensions' ).textContent = width + ' ' + GALLERY_WIDGET.by + ' ' + height + ' ' + GALLERY_WIDGET.pixels;
		dialog.querySelector( '#edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + id + '&action=edit' );
		dialog.querySelector( '#attachment-details-description').textContent = description;
		dialog.querySelector( '#attachment-details-copy-link').value = url;

		if ( clicked === true ) {
			dialog.querySelector( '#attachment-details-alt-text').textContent = alt;
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = caption;
			dialog.querySelector( '.widget-modal-attachment-info img' ).src = url;
			dialog.querySelector( '.widget-modal-attachment-info img' ).alt = alt;
		} else {
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = widget.querySelector( '[data-property="caption"]' ) ? widget.querySelector( '[data-property="caption"]' ).value : '';
			dialog.querySelector( '.widget-modal-attachment-info img' ).src = widget.querySelector( '[data-property="url"]' ) ? widget.querySelector( '[data-property="url"]' ).value : '';

			if ( widget.querySelector( '[data-property="alt"]' ) ) {
				dialog.querySelector( '#attachment-details-alt-text').textContent = widget.querySelector( '[data-property="alt"]' ).value;
				dialog.querySelector( '.widget-modal-attachment-info img' ).alt = widget.querySelector( '[data-property="alt"]' ).value;
			}
		}

		// Set status of items according to user's capabilities
		if ( updateNonce ) {
			inputs.forEach( function( input ) {
				input.removeAttribute( 'readonly' );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.removeAttribute( 'hidden' );
		} else {
			inputs.forEach( function( input ) {
				input.setAttribute( 'readonly', true );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.setAttribute( 'hidden', true );
		}

		if ( deleteNonce ) {
			selects.forEach( function( select ) {
				select.removeAttribute( 'disabled' );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.removeAttribute( 'hidden' );
		} else {
			selects.forEach( function( select ) {
				select.setAttribute( 'disabled', true );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.setAttribute( 'hidden', true );
		}

		// Show or hide Custom URL depending on link type
		linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
			option.removeAttribute( 'selected' );
			if ( option.value === linkType ) {
				option.setAttribute( 'selected', true );
			}
		} );
		if ( linkTo.value === 'none' ) {
			linkToCustom.parentNode.classList.add( 'setting-hidden' );
			linkToCustom.value = '';
		} else {
			if ( linkUrl != null ) {
				linkToCustom.value = linkUrl.value;
			} else {
				linkToCustom.value = '';
			}
			linkToCustom.parentNode.classList.remove( 'setting-hidden' );
		}

		// Show and hide URL field as appropriate
		linkTo.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'none' ) {
				linkToCustom.parentNode.classList.add( 'setting-hidden' );
				linkToCustom.value = '';
			} else {
				if ( selectedOption.value === 'file' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'post' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'custom' ) {
					linkToCustom.value = '';
				}
				linkToCustom.parentNode.classList.remove( 'setting-hidden' );
			}
			addButton.removeAttribute( 'disabled' );
		} );

		dialog.querySelector( '#attachments-' + id + '-media_category').value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag').value = tags;
		dialog.querySelector( '#attachment-display-settings-size' ).innerHTML = sizeOptions;

		dialog.querySelector( '.widget-modal-right-sidebar-info' ).removeAttribute( 'hidden' );

		// Update media attachment details
		dialog.querySelectorAll( '.widget-modal-right-sidebar-info input, .widget-modal-right-sidebar-info textarea' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				if ( input.parentNode.parentNode.className === 'compat-item' ) {
					updateMediaTaxOrTag( input, id ); // Update media categories and tags
				} else {
					updateDetails( input, id );
				}
			} );
		} );

		// Uncheck item if clicked on
		if ( item.className.includes( 'selected' ) ) {
			if ( clicked === false ) {
				item.querySelector( '.check' ).style.display = 'block';
				addButton.setAttribute( 'disabled', true );
			} else {
				item.classList.remove( 'selected' );
				item.setAttribute( 'aria-checked', false );
				item.querySelector( '.check' ).style.display = 'none';
				dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );

				// Disable add to widget button if no media items are selected
				if ( document.querySelector( '.media-item.selected' ) == null ) {
					if ( addButton ) {
						addButton.setAttribute( 'disabled', true );
					}
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
			}
		} else {
			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );
			item.querySelector( '.check' ).style.display = 'block';

			// Enable add to widget button
			if ( addButton ) {
				addButton.removeAttribute( 'disabled' );
			}
		}
	}

	/**
	 * Populate media items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	function populateGridItem( attachment, widget ) {
		var selected = '',
			idsArray = [],
			gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">';

		if ( widget.querySelector( '[data-property="attachment_id"]' ) ) {
			if ( attachment.id == widget.querySelector( '[data-property="attachment_id"]' ).value ) {
				selected = ' selected';
			}
		} else if ( widget.querySelector( '[data-property="ids"]' ) ) {
			idsArray = widget.querySelector( '[data-property="ids"]' ).value.split( ',' );
			if ( idsArray.indexOf( attachment.id ).value ) {
				selected = ' selected';
			}
		}

		gridItem.className = 'media-item' + selected;
		gridItem.id = 'media-' + attachment.id;
		gridItem.setAttribute( 'tabindex', 0 );
		gridItem.setAttribute( 'role', 'checkbox' );
		gridItem.setAttribute( 'aria-checked', selected ? true : false );
		gridItem.setAttribute( 'aria-label', attachment.title );
		gridItem.setAttribute( 'data-id', attachment.id );
		gridItem.setAttribute( 'data-date', attachment.dateFormatted );
		gridItem.setAttribute( 'data-url', attachment.url );
		gridItem.setAttribute( 'data-filename', attachment.filename );
		gridItem.setAttribute( 'data-filetype', attachment.type );
		gridItem.setAttribute( 'data-mime', attachment.mime );
		gridItem.setAttribute( 'data-width', attachment.width );
		gridItem.setAttribute( 'data-height', attachment.height );
		gridItem.setAttribute( 'data-size', attachment.filesizeHumanReadable );
		gridItem.setAttribute( 'data-caption', attachment.caption );
		gridItem.setAttribute( 'data-description', attachment.description );
		gridItem.setAttribute( 'data-link', attachment.link );
		gridItem.setAttribute( 'data-orientation', attachment.orientation );
		gridItem.setAttribute( 'data-menu-order', attachment.menuOrder );
		gridItem.setAttribute( 'data-taxes', attachment.media_cats );
		gridItem.setAttribute( 'data-tags', attachment.media_tags );
		gridItem.setAttribute( 'data-sizes', JSON.stringify( attachment.sizes ) );
		gridItem.setAttribute( 'data-update-nonce', attachment.nonces.update );
		gridItem.setAttribute( 'data-delete-nonce', attachment.nonces.delete );
		gridItem.setAttribute( 'data-edit-nonce', attachment.nonces.edit );

		gridItem.innerHTML = '<div class="select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype + '">' +
			'<div class="media-thumbnail">' + image + '</div>' +
			'</div>' +
			'<button type="button" class="check" tabindex="-1">' +
			'<span class="media-modal-icon"></span>' +
			'<span class="screen-reader-text">' + GALLERY_WIDGET.deselect + '></span>' +
			'</button>';

		return gridItem;
	}

	/**
	 * Populate the grid with images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectMedia( widget ) {
		var template = document.getElementById( 'tmpl-media-grid-modal' ),
			clone = template.content.cloneNode( true ),
			dialogButtons = clone.querySelector( '.widget-modal-header-buttons' ),
			dialogContent = clone.querySelector( '#widget-modal-media-content' ),
			header = dialog.querySelector( 'header' ),
			galleryTemplate = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone = galleryTemplate.content.cloneNode( true ),
			itemAdd = dialog.querySelector( '#menu-item-add' ),
			itemGallery = dialog.querySelector( '#menu-item-gallery' ),
			selectedIds = [],
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': GALLERY_WIDGET.per_page,
				'query[post_mime_type]': 'image',
				'query[paged]': 1
			} );

		// Make AJAX request
		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			if ( result.success ) {

				// Append cloned template and show relevant elements
				header.append( dialogButtons );
				if ( dialog.querySelector( '#widget-modal-media-content' ) == null ) {
					header.after( dialogContent );
				}
				dialog.querySelector( '.widget-modal-title h2' ).textContent = GALLERY_WIDGET.create_gallery;
				dialog.querySelector( '.separator' ).insertAdjacentHTML( 'afterend', galleryClone.querySelector( '#gallery-buttons' ).innerHTML ),
				dialog.querySelector( '.media-library-grid-section' ).after( galleryClone.querySelector( '.media-gallery-grid-section' ) ),
				dialog.querySelector( '.widget-modal-right-sidebar' ).prepend( galleryClone.querySelector( '.widget-modal-gallery-settings' ) );
				dialog.querySelector( 'footer' ).replaceWith( galleryClone.querySelector( 'footer' ) ),

				itemAdd.setAttribute( 'hidden', true );
				itemAdd.setAttribute( 'aria-selected', false );

				itemGallery.textContent = GALLERY_WIDGET.create_gallery;
				itemGallery.removeAttribute( 'hidden' );
				itemGallery.classList.add( 'active' );
				itemGallery.setAttribute( 'aria-selected', true );

				// Set widget ID and values of variables
				dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId = widget.id;
				dialog.querySelector( '#menu-item-embed' ).setAttribute( 'hidden', true );
				addButton = dialog.querySelector( '#gallery-button-new' );
				addButton.classList.remove( 'hidden' );

				if ( result.data.length === 0 ) {

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					dialog.querySelector( '.total-pages' ).textContent = 1;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					dialog.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.no-media' ).removeAttribute( 'hidden' );

				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment, widget );
						dialog.querySelector( '.widget-modal-grid' ).append( gridItem );
					} );

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages !== 1 ) {
								pageLink.setAttribute( 'data-page', 2 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages !== 1 ) {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Open modal to show details about file, or select files for deletion
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectItemToAdd( item, widget, false );
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + GALLERY_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
		dialog.showModal();
	}

	/**
	 * Open the modal to edit a gallery.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editGallery( widget, mode ) {
		var toolbar, itemAdd, itemEdit, galleryAdd, formData,
			template        = document.getElementById( 'tmpl-media-grid-modal' ),
			clone           = template.content.cloneNode( true ),			
			dialogButtons   = clone.querySelector( '.widget-modal-header-buttons' ),
			dialogContent   = clone.querySelector( '#widget-modal-media-content' ),
			header          = dialog.querySelector( 'header' ),
			galleryTemplate = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone    = galleryTemplate.content.cloneNode( true ),
			galleryItems    = [],
			galleryIds      = mode === 'update' ? selectedIds : widget.querySelector( '[data-property="ids"]').value.split( ',' );

		// Append cloned template and show relevant elements
		header.append( dialogButtons );
		header.after( dialogContent );
		dialog.querySelector( '.separator' ).insertAdjacentHTML( 'afterend', galleryClone.querySelector( '#gallery-buttons' ).innerHTML ),
		dialog.querySelector( '.media-library-grid-section' ).after( galleryClone.querySelector( '.media-gallery-grid-section' ) ),
		dialog.querySelector( '.widget-modal-right-sidebar' ).prepend( galleryClone.querySelector( '.widget-modal-gallery-settings' ) );
		dialog.querySelector( 'footer' ).replaceWith( galleryClone.querySelector( 'footer' ) ),
		dialog.querySelector( '#menu-item-embed' ).removeAttribute( 'hidden' );
		dialog.querySelector( '.media-library-select-section').classList.add( 'hidden' );
		dialog.querySelector( '#widget-modal-title h2' ).textContent = GALLERY_WIDGET.edit_gallery;

		dialogButtons.style.display = 'none';
		dialog.querySelector( '#menu-item-embed' ).setAttribute( 'hidden', true );
		dialog.querySelector( '.media-library-grid-section' ).classList.add( 'hidden' );
		dialog.querySelector( '.media-gallery-grid-section' ).classList.remove( 'hidden' );

		itemAdd = dialog.querySelector( '#menu-item-add' );
		itemAdd.classList.remove( 'active' );
		itemAdd.setAttribute( 'hidden', true );
		itemAdd.setAttribute( 'aria-selected', false );

		itemEdit = dialog.querySelector( '#menu-item-gallery-edit' );
		itemEdit.removeAttribute( 'hidden' );
		itemEdit.classList.add ( 'active' );
		itemEdit.setAttribute( 'aria-selected', true );

		dialog.querySelector( '#gallery-button-new' ).classList.add( 'hidden' );
		dialog.querySelector( '#menu-item-gallery-library' ).removeAttribute( 'hidden' );
		dialog.querySelector( '#gallery-button-insert' ).classList.remove( 'hidden' );
		dialog.querySelector( '.widget-modal-gallery-settings' ).removeAttribute( 'hidden' );

		// Load gallery settings
		dialog.querySelector( '#gallery-settings-size' ).value = widget.querySelector( '[data-property="size"]').value;
		dialog.querySelector( '#gallery-settings-columns' ).value = widget.querySelector( '[data-property="columns"]').value;
		dialog.querySelector( '#gallery-settings-link-to' ).value = widget.querySelector( '[data-property="link_type"]').value;
		dialog.querySelector( '#gallery-settings-random-order' ).checked = widget.querySelector( '[data-property="orderby_random"]').value === 'on' ? true : false;
		dialog.querySelector( '.widget-modal-gallery-settings' ).removeAttribute( 'hidden' );

		galleryAdd = dialog.querySelector( '#menu-item-gallery' );
		galleryAdd.classList.add( 'cancel' );
		galleryAdd.textContent = GALLERY_WIDGET.cancel_gallery;
		galleryAdd.removeAttribute( 'hidden' );

		formData = new FormData();
		formData.append( 'action', 'query-attachments' );
		formData.append( 'query[post__in]', galleryIds );
		formData.append( 'query[orderby]', 'post__in' );
		formData.append( 'query[post_mime_type]', 'image' );
		formData.append( 'query[paged]', 1 );

		// Make AJAX request
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
			if ( result.success ) {

				// Set data attributes
				dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId = widget.id;

				// Populate grid with new items
				selectedIds = [];
				result.data.forEach( function( attachment ) {
					var gridItem = populateGridItem( attachment, widget );

					selectedIds.push( gridItem.dataset.id );
					galleryItems.push( gridItem );
				} );

				enableGallerySorting( galleryItems, widget, 'edit' );
				dialog.showModal();
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Enable sorting of chosen gallery items.
	 *
	 * @abstract
	 * @return {void}
	 */
	function enableGallerySorting( galleryItems, widget, mode ) {
		var gallerySortable,
			grid = dialog.querySelector( '#gallery-grid' );

		// Give each item the correct class and attributes
		galleryItems.forEach( function( item ) {
			var input;

			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );
			item.querySelector( '.check' ).style.display = 'block';

			// Add a caption input box
			if ( item.querySelector( '.describe' ) == null ) {
				input = document.createElement( 'input' );
				input.className = 'describe';
				input.type = 'text';
				input.value = item.dataset.caption;
				input.dataset.setting = 'caption';
				input.setAttribute( 'aria-label', 'Caption' );
				input.placeholder = GALLERY_WIDGET.caption + '...';
				item.append( input );
			}

			grid.append( item );
		} );

		// Update captions
		dialog.querySelectorAll( '.describe' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				var item = input.closest( 'li' );
				updateCaption( item.dataset.id, item.dataset.updateNonce, input.value );
			} );
		} );

		// Make gallery grid sortable
		gallerySortable = Sortable.create( grid, {
			group: 'items',
			sort: true,
			handle: 'li',
			fallbackTolerance: 2,

			// Remove an item when clicking on the cross
			onChoose: function( e ) {
				if ( e.explicitOriginalTarget.className === 'check' ) {
					e.item.remove();
				}
				if ( mode === 'edit' ) {
					selectItemToAdd( e.item, widget, true );
				}
			},

			// Re-identify selectedIds after deselection, reselection, or sorting
			onUnchoose: function() {
				setTimeout( function() {
					grid.dispatchEvent( new Event( 'change' ) );
				}, 0 );
			}
		} );

		// Collect selectedIds and enable or disable button to insert items into widget
		grid.addEventListener( 'change', function() {
			var items = grid.querySelectorAll( '.selected' );
			selectedIds = [];
			items.forEach( function( item ) {
				selectedIds.push( item.dataset.id );
			} );
			if ( selectedIds.length === 0 ) {
				dialog.querySelector( '#gallery-button-insert' ).disabled = true;
			} else {
				dialog.querySelector( '#gallery-button-insert' ).disabled = false;
			}
		} );
	}

	/**
	 * Show an updated list of unselected images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateLibrary( widget ) {
		var gridSection,
			updatedItems = [],
			formData = new FormData();

		formData.append( 'action', 'query-attachments' );
		formData.append( 'query[post__not_in]', selectedIds );
		formData.append( 'query[post_mime_type]', 'image' );
		formData.append( 'query[paged]', 1 );

		// Make AJAX request
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
			if ( result.success ) {
				gridSection = dialog.querySelector( '.media-library-grid-section' );
				gridSection.querySelector( 'ul' ).innerHTML = '';
				dialog.querySelector( '.widget-modal-title h2' ).textContent = GALLERY_WIDGET.add_to_gallery;

				if ( result.data.length === 0 ) {

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', true );
						pageLink.setAttribute( 'inert', true );
					} );

					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					dialog.querySelector( '.total-pages' ).textContent = 1;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					dialog.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.no-media' ).removeAttribute( 'hidden' );

				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment, widget );
						gridSection.querySelector( 'ul' ).append( gridItem );

						updatedItems.push( gridItem );
					} );

					// Reset pagination
					dialog.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages !== 1 ) {
								pageLink.setAttribute( 'data-page', 2 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages !== 1 ) {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Show details about file, or select files for deletion
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + GALLERY_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}

				// Set widget ID and values of variables
				dialog.querySelector( '.widget-modal-header-buttons' ).style.display = 'flex';
				dialog.querySelector( '.media-library-select-section' ).classList.remove( 'hidden' );
				gridSection.classList.remove( 'hidden' );
				addButton = dialog.querySelector( '#gallery-button-new' );
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Update captions for gallery images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateCaption( id, nonce, caption ) {
		var formData = new FormData();

		formData.append( 'action', 'save-attachment' );
		formData.append( 'id', id );
		formData.append( 'nonce', nonce );
		formData.append( 'post_id', 0 );
		formData.append( 'changes[caption]', caption );

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
			if ( !result.success ) {
				console.error( GALLERY_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( GALLERY_WIDGET.error, error );
		} );
	}

	/**
	 * Close modal by clicking button.
	 *
	 * @abstract
	 * @return {void}
	 */
	function closeModal() {
		dialog.close();

		if ( dialog.querySelector( '.widget-modal-header-buttons' ) ) {
			dialog.querySelector( '.widget-modal-header-buttons' ).remove();
		}
		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			dialog.querySelector( '#widget-modal-media-content' ).remove();
		}
		if ( dialog.querySelector( '#image-modal-content' ) ) {
			dialog.querySelector( '#image-modal-content' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-footer' ) ) {
			dialog.querySelector( '.widget-modal-footer' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-gallery-edit' ) ) {
			dialog.querySelector( '#menu-item-gallery-edit' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-gallery-library' ) ) {
			dialog.querySelector( '#menu-item-gallery-library' ).remove();
		}
		if ( dialog.querySelector( '.media-gallery-grid-section' ) ) {
			dialog.querySelector( '.media-gallery-grid-section' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-gallery-settings' ) ) {
			dialog.querySelector( '.widget-modal-gallery-settings' ).remove();
		}
		dialog.removeAttribute( 'style' );		
		dialog.querySelector( '.widget-modal-headings' ).removeAttribute( 'style' );
	}

	/**
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var widgetId, widgetEl, base, preview, itemAdd, itemEdit, itemLibrary,
			itemCancel, itemUpload, galleryInsert, galleryUpdate, librarySelect,
			galleryGrid, libraryGrid, libraryItems, content, settings,
			gridSubPanel, uploadSubPanel, ul, fieldset,
			galleryItems = [],
			widget = e.target.closest( '.widget' );

		// Either add an image to an empty gallery widget or edit a gallery in a non-empty widget
		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_gallery' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editGallery( widget, 'renew' );
				}
			}

		// Close the modal
		} else if ( e.target.id === 'widget-modal-close' ) {
			closeModal();

		// Open dialog modal to see images in media library
		} else if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId        = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl        = document.getElementById( widgetId );
			base            = widgetEl.querySelector( '.id_base' );
			preview         = widgetEl.querySelector( '.media-widget-gallery-preview' );

			itemAdd         = dialog.querySelector( '#menu-item-add' );
			itemCancel      = dialog.querySelector( '#menu-item-gallery' );
			itemEdit        = dialog.querySelector( '#menu-item-gallery-edit' );
			itemLibrary     = dialog.querySelector( '#menu-item-gallery-library' );
			itemUpload      = dialog.querySelector( '#menu-item-upload' );

			galleryInsert   = dialog.querySelector( '#gallery-button-insert' );
			galleryUpdate   = dialog.querySelector( '#gallery-button-update' );
			sidebarInfo     = dialog.querySelector( '.widget-modal-right-sidebar-info' );
			sidebarSettings = dialog.querySelector( '.widget-modal-gallery-settings' );

			headerButtons   = dialog.querySelector( '.widget-modal-header-buttons');
			librarySelect   = dialog.querySelector( '.media-library-select-section' );
			libraryGrid     = dialog.querySelector( '.media-library-grid-section' );
			libraryItems    = dialog.querySelectorAll( '.media-library-grid-section li' );
			galleryGrid     = dialog.querySelector( '.media-gallery-grid-section' );
			content         = dialog.querySelector( '.media-frame-content' );

			gridSubPanel    = dialog.querySelectorAll( '#attachments-browser, .media-views-heading, .attachments-wrapper, .media-sidebar, .widgets-modal-pages, .media-frame-toolbar' );
			uploadSubPanel  = dialog.querySelector( '#uploader-inline' );

			if ( e.target.id === 'menu-item-gallery' ) {
				closeModal();
				if ( ! e.target.className.includes( 'cancel' ) ) {
					selectMedia( widgetEl );
					e.target.textContent = GALLERY_WIDGET.create_gallery;
				}

			// Edit a gallery
			} else if ( e.target.id === 'menu-item-gallery-edit' ) {

				// No need to do anything if the edit gallery screen is already visible
				if ( e.target.className.includes( 'active' ) ) {
					return;
				}
				itemLibrary.classList.remove( 'active' );
				itemLibrary.setAttribute( 'aria-selected', false );
				galleryUpdate.classList.add( 'hidden' );
				galleryUpdate.setAttribute( 'disabled', true );
				headerButtons.style.display = 'none';
				librarySelect.classList.add( 'hidden' );
				libraryGrid.classList.add( 'hidden' );

				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				galleryGrid.classList.remove( 'hidden' );
				sidebarSettings.removeAttribute( 'hidden' );
				galleryInsert.classList.remove( 'hidden' );
				galleryInsert.removeAttribute( 'disabled' );

			// Open the library to add images to current gallery
			} else if ( e.target.id === 'menu-item-gallery-library' ) {
				sidebarInfo.setAttribute( 'hidden', true );
				sidebarSettings.setAttribute( 'hidden', true );
				itemEdit.classList.remove( 'active' );
				itemEdit.setAttribute( 'aria-selected', false );
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				itemAdd.classList.remove( 'active' );
				itemAdd.setAttribute( 'aria-selected', false );
				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				galleryInsert.classList.add( 'hidden' );
				galleryInsert.setAttribute( 'disabled', true );
				galleryGrid.classList.add( 'hidden' );
				sidebarInfo.setAttribute( 'hidden', true );
				libraryGrid.classList.remove( 'hidden' );

				updateLibrary( widgetEl );
				galleryUpdate.classList.remove( 'hidden' );
				galleryUpdate.removeAttribute( 'disabled' );

			// Create a new gallery of images
			} else if ( e.target.id === 'gallery-button-new' ) {
				dialog.querySelector( '#widget-modal-title h2' ).textContent = GALLERY_WIDGET.edit_gallery;				
				headerButtons.style.display = 'none';
				sidebarInfo.setAttribute( 'hidden', true );

				e.target.classList.add( 'hidden' );
				itemAdd.classList.remove( 'active' );
				itemAdd.setAttribute( 'aria-selected', false );
				itemAdd.setAttribute( 'hidden', true );
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				libraryGrid.classList.add( 'hidden' );
				librarySelect.classList.add( 'hidden' );

				itemCancel.textContent = GALLERY_WIDGET.cancel_gallery;
				itemCancel.removeAttribute( 'hidden' );
				itemCancel.classList.remove( 'active' );
				itemCancel.setAttribute( 'aria-selected', false );
				itemEdit.removeAttribute( 'hidden' );
				itemEdit.classList.add ( 'active' );
				itemEdit.setAttribute( 'aria-selected', true );
				itemLibrary.removeAttribute( 'hidden' );

				galleryGrid.classList.remove( 'hidden' );
				galleryInsert.classList.remove( 'hidden' );
				dialog.querySelector( '.widget-modal-gallery-settings' ).removeAttribute( 'hidden' );

				libraryItems.forEach( function( item ) {
					if ( item.className.includes( 'selected' ) ) {
						selectedIds.push( item.dataset.id );
						galleryItems.push( item );
					} else {
						item.setAttribute( 'hidden', true );
						item.setAttribute( 'inert', true );
					}
				} );

				enableGallerySorting( galleryItems, widgetEl, 'select' );

			// Insert the gallery into the widget
			} else if ( e.target.id === 'gallery-button-insert' ) {
				if ( preview ) {
					preview.innerHTML = '';
				} else {
					ul = document.createElement( 'ul' );
					ul.className = 'media-widget-gallery-preview';

					fieldset = document.createElement( 'fieldset' );
					fieldset.className = 'media-widget-buttons';
					fieldset.innerHTML = '<button type="button" class="button edit-media selected" data-edit-nonce="' + widgetEl.querySelector( '.select-media' ).dataset.editNonce + '" style="margin-top:0;">' + GALLERY_WIDGET.edit_gallery + '</button>';

					widgetEl.querySelector( '.attachment-media-view' ).replaceWith( ul );
					widgetEl.querySelector( '.media-widget-preview' ).after( fieldset );
				}

				// Update the preview within the media gallery widget
				selectedIds = [];
				dialog.querySelectorAll( '#gallery-grid .selected' ).forEach( function( item ) {
					var li = document.createElement( 'li' );

					selectedIds.push( item.dataset.id );

					li.className = 'gallery-item';
					li.innerHTML = '<div class="gallery-icon"><img alt="' + item.querySelector( 'img' ).alt + '" src="' + item.dataset.url + '" width="150" height="150"></div>';

					widgetEl.querySelector( '.media-widget-gallery-preview' ).append( li );
				} );

				// Update the widget fields
				widgetEl.querySelector( '[data-property="ids"]' ).value = selectedIds.toString();
				widgetEl.querySelector( '[data-property="link_type"]' ).value = dialog.querySelector( '#gallery-settings-link-to' ).value;
				widgetEl.querySelector( '[data-property="columns"]' ).value = dialog.querySelector( '#gallery-settings-columns' ).value;
				widgetEl.querySelector( '[data-property="orderby_random"]' ).value = dialog.querySelector( '#gallery-settings-random-order' ).checked ? 1 : '';
				widgetEl.querySelector( '[data-property="size"]' ).value = dialog.querySelector( '#gallery-settings-size' ).value;

				// Activate Save/Publish button
				if ( document.body.className.includes( 'widgets-php' ) ) {
					widgetEl.classList.add( 'widget-dirty' );
				}
				widgetEl.dispatchEvent( new Event( 'change' ) );
				closeModal();

			// Update a gallery
			} else if ( e.target.id === 'gallery-button-update' ) {
				libraryItems.forEach( function( item ) {
					if ( item.className.includes( 'selected' ) ) {
						selectedIds.push( item.dataset.id );
					}
				} );

				closeModal();
				editGallery( widgetEl, 'update' );

			// Reverse the order of items in the gallery
			} else if ( e.target.className.includes( 'gallery-button-reverse' ) ) {
				dialog.querySelectorAll( '#gallery-grid li:not( [hidden] )' ).forEach( function( item ) {
					item.parentNode.prepend( item );
				} );
				selectedIds.reverse();

			// Delete an item from the media library
			} else if ( e.target.className.includes( 'delete-attachment' ) ) {
				if ( widgetEl.querySelector( '[data-property="ids"]' ) ) {
					libraryItems.forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							if ( ! widgetEl.querySelector( '[data-property="ids"]' ).value.split( ',' ).includes( item.dataset.id ) ) {
								if ( window.confirm( GALLERY_WIDGET.confirm_delete ) ) {
									deleteItem( item.dataset.id );
								}
							}
						}
					} );
				}

			// Copy URL
			} else if ( e.target.className.includes( 'copy-attachment-url' ) ) {
				copyToClipboard( e.target );
			}
		}
	} );

	// Set focus after closing modal using Escape key
	document.addEventListener( 'keydown', function( e ) {
		var widgetId, widget, details, base,
			modal = dialog.querySelector( '#widget-modal-media-content' );

		if ( modal ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;

			if ( widgetId ) {
				widget  = document.getElementById( widgetId );
				details = widget.querySelector( 'details' );
				base    = widget.querySelector( '.id_base' );

				if ( base && base.value === 'media_gallery' ) {
					if ( dialog.open && e.key === 'Escape' ) {
						closeModal();
						setTimeout( function() {
							details.open = true;
						}, 100 );
						details.addEventListener( 'toggle', function( e ) {
							if ( e.target.open ) {
								widget.querySelector( '.edit-media' ) ? widget.querySelector( '.edit-media' ).focus() : widget.querySelector( '.select-media' ).focus();
							}
						} );
					}
				}
			}
		}
	} );
} );
