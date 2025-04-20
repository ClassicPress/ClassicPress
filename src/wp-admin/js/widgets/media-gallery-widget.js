/* eslint consistent-this: [ "error", "control" ] */
/* global ajaxurl, GALLERY_WIDGET, console */

/**
 * @since CP 2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var addButton, gallerySortable,
		selectedIds = [],
		dialog = document.getElementById( 'widget-modal' ),
		closeButton = document.getElementById( 'media-modal-close' );

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
			'<span class="setting" data-setting="media_category">' +
				'<label for="attachments-' + id + '-media_category">' +
					'<span class="alignleft">Media Categories</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_category" name="attachments[' + id + '][media_category]" value="">' +
			'</span>' +
			'<span class="setting" data-setting="media_post_tag">' +
				'<label for="attachments-' + id + '-media_post_tag">' +
					'<span class="alignleft">Media Tags</span>' +
				'</label>' +
				'<input type="text" class="text" id="attachments-' + id + '-media_post_tag" name="attachments[' + id + '][media_post_tag]" value="">' +
			'</span>';

		if ( document.querySelector( '.compat-item' ) != null ) {
			document.querySelector( '.compat-item' ).remove();
		}
		document.querySelector( '.attachment-compat' ).append( form );
	}

	/**
	 * Delete attachment from within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function deleteItem( id ) {console.log(id);
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
				closeButton.click();
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
		var selectedItems = document.querySelectorAll( '.selected' ),
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
			alt = item.querySelector( 'img' ).alt,
			updateNonce = item.dataset.updateNonce,
			deleteNonce = item.dataset.deleteNonce,
			linkType = widget.querySelector( '[data-property="link_type"]' ).value,
			linkUrl = widget.querySelector( '[data-property="link_url"]' ),
			inputs = dialog.querySelectorAll( '.media-sidebar input, .media-sidebar textarea' ),
			selects = dialog.querySelectorAll( '.media-sidebar select' ),
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
		dialog.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );

		if ( clicked === true ) {
			dialog.querySelector( '#attachment-details-alt-text').textContent = alt;
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = caption;
		} else {
			dialog.querySelector( '#attachment-details-alt-text').textContent = widget.querySelector( '[data-property="alt"]' ).value;
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = widget.querySelector( '[data-property="caption"]' ).value;
		}

		// Set status of items according to user's capabilities
		if ( updateNonce == null ) {
			inputs.forEach( function( input ) {
				input.setAttribute( 'readonly', true );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.setAttribute( 'hidden', true );
		} else {
			inputs.forEach( function( input ) {
				input.removeAttribute( 'readonly' );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.removeAttribute( 'hidden' );
		}

		if ( deleteNonce == null ) {
			selects.forEach( function( select ) {
				select.setAttribute( 'disabled', true );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.setAttribute( 'hidden', true );
		} else {
			selects.forEach( function( select ) {
				select.removeAttribute( 'disabled' );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.removeAttribute( 'hidden' );
		}

		// Show or hide Custom URL depending on link type
		linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
			option.removeAttribute( 'selected' );
			if ( option.value === linkType ) {
				option.setAttribute( 'selected', true );
			}
		} );
		if ( linkTo.value === 'none' ) {
			linkToCustom.parentNode.classList.add( 'hidden' );
			linkToCustom.value = '';
		} else {
			if ( linkUrl != null ) {
				linkToCustom.value = linkUrl.value;
			} else {
				linkToCustom.value = '';
			}
			linkToCustom.parentNode.classList.remove( 'hidden' );
		}

		// Show and hide URL field as appropriate
		linkTo.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'none' ) {
				linkToCustom.parentNode.classList.add( 'hidden' );
				linkToCustom.value = '';
			} else {
				if ( selectedOption.value === 'file' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'post' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'custom' ) {
					linkToCustom.value = '';
				}
				linkToCustom.parentNode.classList.remove( 'hidden' );
			}
			addButton.removeAttribute( 'disabled' );
		} );

		dialog.querySelector( '#attachments-' + id + '-media_category').value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag').value = tags;
		dialog.querySelector( '#attachment-display-settings-size' ).innerHTML = sizeOptions;

		dialog.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );

		// Update media attachment details
		dialog.querySelectorAll( '.settings input, .settings textarea' ).forEach( function( input ) {
			input.addEventListener( 'blur', function() {
				if ( input.parentNode.parentNode.className === 'compat-item' ) {
					updateMediaTaxOrTag( input, id ); // Update media categories and tags
				} else {
					updateDetails( input, id );
				}
			} );
		} );
	}

	/**
	 * Populate media items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	function populateGridItem( attachment, widget ) {
		var gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">',
			idsArray = widget.querySelector( '[data-property="ids"]' ).value.split( ',' ),
			selected = idsArray.indexOf( attachment.id ).value ? ' selected' : '';

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
	 * Show the grid of media items when that view is selected.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setupGridView() {
		var itemGallery    = dialog.querySelector( '#menu-item-gallery' );
			itemUpload     = dialog.querySelector( '#menu-item-upload' );
			itemBrowse     = dialog.querySelector( '#menu-item-browse' );
			router         = dialog.querySelector( '.media-frame-router' );
			mediaToolbar   = dialog.querySelector( '.media-toolbar' );
			gridView       = dialog.querySelector( '.widgets-media-grid-view' );
			loadMore       = dialog.querySelector( '.load-more-wrapper' );
			content        = dialog.querySelector( '.media-frame-content' );

		itemUpload.classList.remove( 'active' );
		itemUpload.setAttribute( 'aria-selected', false );
		itemBrowse.classList.remove( 'active' );
		itemBrowse.setAttribute( 'aria-selected', false );
		itemGallery.classList.add ( 'active' );
		itemGallery.setAttribute( 'aria-selected', true );
		router.classList.remove( 'hidden' );
		mediaToolbar.classList.remove( 'hidden' );
		gridView.classList.remove( 'hidden' );
		loadMore.classList.remove( 'hidden' );
		content.style.top = '84px';
	}

	/**
	 * Populate the grid with images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function selectMedia( widget ) {
		var template     = document.getElementById( 'tmpl-media-grid-modal' ),
			clone        = template.content.cloneNode( true ),
			gallery      = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone = gallery.content.cloneNode( true ),
			params       = new URLSearchParams( {
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
				dialog.querySelector( '.media-modal' ).append( clone );
				dialog.querySelector( '.media-toolbar').after( galleryClone.querySelector( '.media-toolbar-gallery' ) );
				dialog.querySelector( '.attachment-details' ).before( galleryClone.querySelector( '.collection-settings' ) );
				dialog.querySelector( '.separator' ).after( galleryClone.querySelector( '#menu-item-gallery-library' ) );
				dialog.querySelector( '.separator' ).after( galleryClone.querySelector( '#menu-item-gallery-edit' ) );

				dialog.querySelector( '#menu-item-gallery' ).textContent = GALLERY_WIDGET.create_gallery;
				dialog.querySelector( '#menu-item-gallery' ).removeAttribute( 'hidden' );
				checkWindowWidth();

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
						dialog.querySelector( '#widgets-media-grid ul' ).append( gridItem );
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
							item.focus();
							addButton.setAttribute( 'disabled', true );
						}

						// Update items only on the media library grid, not the gallery grid
						item.addEventListener( 'click', function() {
							if ( item.parentNode.id === 'gallery-grid' ) {
								return;
							}

							selectItemToAdd( item, widget, true );

							if ( item.className.includes( 'selected' ) ) {
								item.classList.remove( 'selected' );
								item.setAttribute( 'aria-checked', false );
								dialog.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );

								// Disable add to widget button if no media items are selected
								if ( document.querySelector( '.selected' ) == null ) {
									addButton.setAttribute( 'disabled', true );
								}
							} else {
								item.classList.add( 'selected' );
								item.setAttribute( 'aria-checked', true );
								dialog.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );

								// Enable add to widget button
								addButton.removeAttribute( 'disabled' );
							}
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + GALLERY_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
					
					dialog.querySelector( '.media-frame-toolbar .media-toolbar-secondary' ).prepend( galleryClone.querySelector( '.media-selection' ) );

					dialog.querySelector( '.media-frame-actions-heading' ).nextElementSibling.innerHTML = galleryClone.querySelector( '.media-toolbar-primary.search-form' ).outerHTML;
				}

				// Set widget ID and values of variables
				dialog.querySelector( '#new-image-modal' ).dataset.widgetId = widget.id;
				addButton = dialog.querySelector( '#create-new-gallery' );
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
	function editGallery( widget ) {
		var toolbar, itemAdd, itemEdit, galleryAdd, formData,
			template     = document.getElementById( 'tmpl-media-grid-modal' ),
			clone        = template.content.cloneNode( true ),
			gallery      = document.getElementById( 'tmpl-edit-gallery-modal' ),
			galleryClone = gallery.content.cloneNode( true ),
			galleryItems = [],
			selectedIds  = widget.querySelector( '[data-property="ids"]').value.split( ',' );

		// Append cloned template and show relevant elements
		dialog.querySelector( '.media-modal' ).append( clone );
		dialog.querySelector( '.media-frame-router').classList.add( 'hidden' );
		dialog.querySelector( '.media-frame-content').style.top = '50px';
		dialog.querySelector( '#media-frame-title h2' ).textContent = GALLERY_WIDGET.edit_gallery;

		toolbar = dialog.querySelector( '.media-toolbar');
		toolbar.classList.add( 'hidden' );
		toolbar.after( galleryClone.querySelector( '.media-toolbar-gallery' ) );

		dialog.querySelector( '.attachment-details' ).before( galleryClone.querySelector( '.collection-settings' ) );
		dialog.querySelector( '.separator' ).after( galleryClone.querySelector( '#menu-item-gallery-library' ) );
		dialog.querySelector( '.separator' ).after( galleryClone.querySelector( '#menu-item-gallery-edit' ) );
		dialog.querySelector( '.media-toolbar .media-button-primary' ).replaceWith( galleryClone.querySelector( '.media-toolbar-primary' ) );
		dialog.querySelector( '.media-toolbar-gallery' ).classList.remove( 'hidden' );

		itemAdd = dialog.querySelector( '#menu-item-add' );
		itemAdd.classList.remove( 'active' );
		itemAdd.setAttribute( 'aria-selected', false );

		itemEdit = dialog.querySelector( '#menu-item-gallery-edit' );
		itemEdit.removeAttribute( 'hidden' );
		itemEdit.classList.add ( 'active' );
		itemEdit.setAttribute( 'aria-selected', true );		

		dialog.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );
		dialog.querySelector( '#create-new-gallery' ).classList.add( 'hidden' );
		dialog.querySelectorAll( '.media-toolbar-primary' )[1].style.top = '110px';
		dialog.querySelector( '#menu-item-gallery-library' ).removeAttribute( 'hidden' );
		dialog.querySelector( '#gallery-button-insert' ).classList.remove( 'hidden' );

		// Load gallery settings
		dialog.querySelector( '#gallery-settings-size' ).value = widget.querySelector( '[data-property="size"]').value;
		dialog.querySelector( '#gallery-settings-columns' ).value = widget.querySelector( '[data-property="columns"]').value;
		dialog.querySelector( '#gallery-settings-link-to' ).value = widget.querySelector( '[data-property="link_type"]').value;
		dialog.querySelector( '#gallery-settings-random-order' ).checked = widget.querySelector( '[data-property="orderby_random"]').value === 'on' ? true : false;
		dialog.querySelector( '.collection-settings' ).removeAttribute( 'hidden' );

		galleryAdd = dialog.querySelector( '#menu-item-gallery' );
		galleryAdd.classList.add( 'cancel' );
		galleryAdd.innerHTML = '&larr; ' + GALLERY_WIDGET.cancel_gallery;
		galleryAdd.removeAttribute( 'hidden' );
		checkWindowWidth();

		formData = new FormData();
		formData.append( 'action', 'query-attachments' );
		formData.append( 'query[post__in]', selectedIds );
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

				// Set widget ID as data attribute of dialog
				dialog.querySelector( '#new-image-modal' ).dataset.widgetId = widget.id;

				// Populate grid with new items
				result.data.forEach( function( attachment ) {
					var gridItem = populateGridItem( attachment, widget );

					selectedIds.push( gridItem.dataset.id );
					galleryItems.push( gridItem );
				} );

				enableGallerySorting( galleryItems, widget );
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
	function enableGallerySorting( galleryItems, widget ) {
		var grid = dialog.querySelector( '#gallery-grid' );

		galleryItems.forEach( function( item ) {
			var input, gallerySortable;

			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );

			if ( item.querySelector( '.describe' ) == null ) {				
				input = document.createElement( 'input' );
				input.className = 'describe';
				input.type = 'text';
				input.value = item.dataset.caption;
				input.dataset.setting = 'caption';
				input.setAttribute( 'aria-label', 'Caption' );
				input.placeholder = 'Caption...';
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

		gallerySortable = Sortable.create( grid, {
			group: 'items',
			sort: true,
			handle: 'li',
			fallbackTolerance: 2,

			// Select, deselect, or remove an item when clicked on
			onChoose: function( e ) {
				var item = e.item;
				if ( e.explicitOriginalTarget.className === 'check' ) {
					item.remove();
				}

				selectItemToAdd( item, widget, true );

				if ( item.className.includes( 'selected' ) ) {
					item.className = 'media-item';
					item.setAttribute( 'aria-checked', false );
					dialog.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );
				} else {
					item.className = 'media-item selected';
					item.setAttribute( 'aria-checked', true );
					dialog.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );
					dialog.querySelector( '#gallery-button-insert' ).disabled = false;
				}

				dialog.querySelectorAll( '.selected' ).forEach( function( selected ) {
					selectedIds.push( selected.dataset.id );
				} );
				
				if ( grid.querySelector( '.selected' ) == null ) {
					dialog.querySelector( '#gallery-button-insert' ).disabled = true;
				}
			},

			// Re-order selectedIds when element dropped
			onEnd: function() {
				selectedIds = [];
				dialog.querySelectorAll( '.selected' ).forEach( function( selected ) {
					selectedIds.push( selected.dataset.id );
				} );
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
		var updatedItems = [];

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
				var grid = dialog.querySelector( '#widgets-media-grid ul' );
				grid.innerHTML = '';

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
						dialog.querySelector( '#widgets-media-grid ul' ).append( gridItem );
						
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

							// Applicable only to the media library grid, not the gallery grid
							if ( item.parentNode.id === 'gallery-grid' ) {
								return;
							}

							selectItemToAdd( item, widget, true );

							if ( item.className.includes( 'selected' ) ) {
								item.classList.remove( 'selected' );
								item.setAttribute( 'aria-checked', false );
								dialog.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );

								// Disable add to widget button if no media items are selected
								if ( document.querySelector( '.selected' ) == null ) {
									addButton.setAttribute( 'disabled', true );
								}
							} else {
								item.classList.add( 'selected' );
								item.setAttribute( 'aria-checked', true );
								dialog.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );

								// Enable add to widget button
								addButton.removeAttribute( 'disabled' );
							}
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + GALLERY_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}

				// Set widget ID and values of variables
				dialog.querySelector( '#new-image-modal' ).dataset.widgetId = widget.id;
				addButton = dialog.querySelector( '#create-new-gallery' );
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
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var base, page, widgetId, itemAdd, itemBrowse, itemUpload, editButton,
			libraryButton, router, mediaToolbar, gridView, loadMore, content,
			gridSubPanel, uploadSubPanel, urlPanel, frameTitle, preview, ul,
			galleryItems = [],
			widget = e.target.closest( '.widget' );

		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_gallery' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editGallery( widget );
				}
			}

		// Open dialog modal to see images in media library
		} else if ( dialog.querySelector( '#new-image-modal' ) ) {
			widgetId       = dialog.querySelector( '#new-image-modal' ).dataset.widgetId;
			widgetEl       = document.getElementById( widgetId );
			base           = widgetEl.querySelector( '.id_base' );
			preview        = widgetEl.querySelector( '.media-widget-gallery-preview' );

			itemAdd        = dialog.querySelector( '#menu-item-gallery' );
			itemEdit       = dialog.querySelector( '#menu-item-gallery-edit' );
			itemLibrary    = dialog.querySelector( '#menu-item-gallery-library' );
			itemBrowse     = dialog.querySelector( '#menu-item-browse' );
			itemUpload     = dialog.querySelector( '#menu-item-upload' );

			galleryInsert  = dialog.querySelector( '#gallery-button-insert' );
			galleryUpdate  = dialog.querySelector( '#gallery-button-update' );

			router         = dialog.querySelector( '.media-frame-router' );
			mediaToolbar   = dialog.querySelector( '.media-toolbar' );
			toolbarGallery = dialog.querySelector( '.media-toolbar-gallery' );
			widgetsGrid    = dialog.querySelector( '#widgets-media-grid' );
			gridViewItems  = dialog.querySelectorAll( '.widgets-media-grid-view li' );
			loadMore       = dialog.querySelector( '.load-more-wrapper' );
			content        = dialog.querySelector( '.media-frame-content' );
			collections    = dialog.querySelector( '.collection-settings' );
			attachDetails  = dialog.querySelector( '.attachment-details' );

			gridSubPanel   = dialog.querySelectorAll( '#attachments-browser, .media-views-heading, .attachments-wrapper, .media-sidebar, .widgets-modal-pages, .media-frame-toolbar' );
			uploadSubPanel = dialog.querySelector( '#uploader-inline' );
			frameTitle     = dialog.querySelector( '#media-frame-title' );

			if ( e.target.id === 'menu-item-gallery' ) {
				if ( e.target.className.includes( 'cancel' ) ) {
					closeButton.click();
				} else {
					setupGridView();
				}
			} else if ( e.target.id === 'menu-item-browse' ) {
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				itemAdd.classList.remove( 'active' );
				itemAdd.setAttribute( 'aria-selected', false );
				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				uploadSubPanel.setAttribute( 'hidden', true );
				uploadSubPanel.setAttribute( 'inert', true );
				widgetsGrid.removeAttribute( 'hidden' );
				collections.classList.add( 'hidden' );
				attachDetails.removeAttribute( 'hidden' );

				// Turn off sorting
				if ( gallerySortable ) {
					gallerySortable.option( 'disabled', true );
				}
				
				gridViewItems.forEach( function( item ) {
					item.removeAttribute( 'hidden' );
					item.removeAttribute( 'inert' );
				} );

				gridSubPanel.forEach( function ( element ) {
					element.removeAttribute( 'hidden' );
					element.removeAttribute( 'inert' );
				} );
			} else if ( e.target.id === 'create-new-gallery' ) {				
				dialog.querySelector( '#media-frame-title h2' ).textContent = GALLERY_WIDGET.edit_gallery;

				e.target.classList.add( 'hidden' );
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				itemBrowse.classList.remove( 'active' );
				itemBrowse.setAttribute( 'aria-selected', false );
				widgetsGrid.setAttribute( 'hidden', true );
				itemAdd.textContent = '&larr; ' + GALLERY_WIDGET.cancel_gallery;
				itemAdd.classList.add( 'cancel' );
				itemEdit.removeAttribute( 'hidden' );
				itemEdit.classList.add ( 'active' );
				itemEdit.setAttribute( 'aria-selected', true );
				itemLibrary.removeAttribute( 'hidden' );

				toolbarGallery.classList.remove( 'hidden' );				
				dialog.querySelectorAll( '.media-toolbar-primary' )[1].style.right = '1em';
				galleryInsert.classList.remove( 'hidden' );
				attachDetails.setAttribute( 'hidden', true );
				collections.classList.remove( 'hidden' );
				
				router.classList.add( 'hidden' );
				mediaToolbar.classList.add( 'hidden' );
				loadMore.classList.add( 'hidden' );
				content.style.top = '50px';

				gridViewItems.forEach( function( item ) {
					if ( item.className.includes( 'selected' ) ) {
						selectedIds.push( item.dataset.id );
						galleryItems.push( item );
					} else {
						item.setAttribute( 'hidden', true );
						item.setAttribute( 'inert', true );
					}
				} );

				enableGallerySorting( galleryItems, widgetEl );
			} else if ( e.target.id === 'menu-item-gallery-library' ) {
				itemEdit.classList.remove( 'active' );
				itemEdit.setAttribute( 'aria-selected', false );
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				itemAdd.classList.remove( 'active' );
				itemAdd.setAttribute( 'aria-selected', false );
				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				dialog.querySelector( '.media-toolbar-gallery' ).setAttribute( 'hidden', true );
				dialog.querySelector( '#widgets-media-grid' ).removeAttribute( 'hidden' );

				updateLibrary( widgetEl );
				setupGridView();

				dialog.querySelector( '#menu-item-browse' ).click();
				galleryInsert.classList.add( 'hidden' );
				galleryInsert.setAttribute( 'disabled', true );
				galleryUpdate.classList.remove( 'hidden' );
				galleryUpdate.removeAttribute( 'disabled' );

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
				closeButton.click();
			} else if ( e.target.id === 'gallery-button-update' ) {
				dialog.querySelector( '#create-new-gallery' ).click();
				mediaToolbar.classList.add( 'hidden' );
				toolbarGallery.removeAttribute( 'hidden' );
				widgetsGrid.querySelector( 'ul' ).innerHTML = '';
				galleryUpdate.classList.add( 'hidden' );
				galleryUpdate.setAttribute( 'disabled', true );
				itemLibrary.classList.remove( 'active' );
				itemLibrary.setAttribute( 'aria-selected', false );
				galleryInsert.classList.remove( 'hidden' );
				galleryInsert.removeAttribute( 'disabled' );
			} else if ( e.target.id === 'menu-item-gallery-edit' ) {
				itemLibrary.classList.remove( 'active' );
				itemLibrary.setAttribute( 'aria-selected', false );
				galleryUpdate.classList.add( 'hidden' );
				galleryUpdate.setAttribute( 'disabled', true );
				dialog.querySelector( '#widgets-media-grid' ).setAttribute( 'hidden', true );
				attachDetails.setAttribute( 'hidden', true );

				router.classList.add( 'hidden' );
				mediaToolbar.classList.add( 'hidden' );
				loadMore.classList.add( 'hidden' );
				content.style.top = '50px';
				gallerySortable.option( 'disabled', false ); // turn sorting back on

				dialog.querySelector( '.media-toolbar-gallery' ).removeAttribute( 'hidden' );
				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				galleryInsert.classList.remove( 'hidden' );
				galleryInsert.removeAttribute( 'disabled' );
			} else if ( e.target.className.includes( 'gallery-button-reverse' ) ) {
				dialog.querySelectorAll( '.widgets-media-grid-view li:not( [hidden] )' ).forEach( function( item ) {
					item.parentNode.prepend( item );
				} );
				selectedIds.reverse();
			} else if ( e.target.className.includes( 'delete-attachment' ) ) {
				if ( widgetEl.querySelector( '[data-property="ids"]' ) ) {
					gridViewItems.forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							if ( ! widgetEl.querySelector( '[data-property="ids"]' ).value.split( ',' ).includes( item.dataset.id ) ) {
								if ( window.confirm( GALLERY_WIDGET.confirm_delete ) ) {
									deleteItem( item.dataset.id );
								}
							}
						}
					} );
				}
			}
		}
	} );

	/**
	 * Close modal by clicking button.
	 *
	 * @abstract
	 * @return {void}
	 */
	closeButton.addEventListener( 'click', function( e ) {
		dialog.close();
		if ( dialog.querySelector( '#image-modal-content' ) ) {
			dialog.querySelector( '#image-modal-content' ).remove();
		}
		if ( dialog.querySelector( '#new-image-modal' ) ) {
			dialog.querySelector( '#new-image-modal' ).remove();
		}
	} );

	// Set focus after closing modal using Escape key
	dialog.addEventListener( 'keydown', function( e ) {
		var widgetId, widget, details, base,
			modal = dialog.querySelector( '#new-image-modal' );

		if ( modal ) {
			widgetId = dialog.querySelector( '#new-image-modal' ).dataset.widgetId;

			if ( widgetId ) {
				widget  = document.getElementById( widgetId );
				details = widget.querySelector( 'details' );
				base    = widget.querySelector( '.id_base' );

				if ( base && base.value === 'media_gallery' ) {
					if ( dialog.open && e.key === 'Escape' ) {
						closeButton.click();
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

	/* Enable choosing of panel on narrow screen */
	function checkWindowWidth() {
		var itemAdd, itemEdit, itemLibrary, details;
		if ( window.innerWidth < 901 ) {
			itemAdd = dialog.querySelector( '#menu-item-gallery' );
			itemAdd.removeAttribute( 'hidden' );

			details = dialog.querySelector( 'details' );
			details.append( itemAdd );

			if ( dialog.querySelector( '#media-frame-title h2' ).textContent === GALLERY_WIDGET.edit_gallery ) {
				itemEdit = dialog.querySelector( '#menu-item-gallery-edit' );
				itemEdit.removeAttribute( 'hidden' );
				details.append( itemEdit );

				itemLibrary = dialog.querySelector( '#menu-item-gallery-library' );
				itemLibrary.removeAttribute( 'hidden' );
				details.append( itemLibrary );
				dialog.querySelector( 'summary' ).style.marginLeft = '8em';
			}

			details.removeAttribute( 'hidden' );
			itemAdd.addEventListener( 'click', function() {
				dialog.querySelector( '.details-panel' ).style.marginTop = '-35px';
			} );
		}
	}
} );
