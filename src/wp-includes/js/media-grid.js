document.addEventListener( 'DOMContentLoaded', function() {
	var pond, itemID, focusID,
		{ FilePond } = window, // import FilePond
		queryParams = new URLSearchParams( window.location.search ),
		addNew = document.querySelector( '.page-title-action' ),
		uploader = document.querySelector( '.uploader-inline' ),
		close = document.querySelector( '.close' ),
		uploadCatSelect = document.getElementById( 'upload-category' ),
		inputElement = document.getElementById( 'filepond' ),
		ajaxurl	= document.getElementById( 'ajax-url' ).value,		
		body = document.body,
		dialog = document.getElementById( 'media-modal' ),
		leftIcon = document.getElementById( 'left-dashicon' ),
		rightIcon = document.getElementById( 'right-dashicon' ),
		closeButton = document.getElementById( 'dialog-close-button' ),
		dateFilter = document.getElementById( 'filter-by-date' ),
		typeFilter = document.getElementById( 'filter-by-type' ),
		search = document.getElementById( 'media-search-input' ),
		mediaCatSelect = document.getElementById( 'taxonomy=media_category&term' ),
		mediaGrid = document.querySelector( '#media-grid ul' );

	// Update details within modal
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

	// Update attachment details
	function updateDetails( input, id ) {
		var successTimeout,
			data = new FormData();

		data.append( 'action', 'save-attachment' );
		data.append( 'id', id );
		data.append( 'nonce', document.getElementById( 'media-' + id ).dataset.updateNonce );

		// Append metadata fields
		if ( input.parentNode.dataset.setting === 'alt' ) {
			data.append( 'changes[alt]', input.value );
		} else if ( input.parentNode.dataset.setting === 'title' ) {
			data.append( 'changes[title]', input.value );
		} else if ( input.parentNode.dataset.setting === 'caption' ) {
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
				document.getElementById( 'details-saved' ).classList.remove( 'hidden' );
				document.getElementById( 'details-saved' ).setAttribute( 'aria-hidden', 'false' );

				// Hide success visual feedback after 3 seconds.
				successTimeout = setTimeout( function() {
					document.getElementById( 'details-saved' ).classList.add( 'hidden' );
					document.getElementById( 'details-saved' ).setAttribute( 'aria-hidden', 'true' );
				}, 3000 );
			} else {
				console.error( _wpMediaGridSettings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaGridSettings.error, error );
		} );
	}

	// Update media categories and tags
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
				console.error( _wpMediaGridSettings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaGridSettings.error, error );
		} );
	}

	// Delete attachment from within modal
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
			if ( result === 1 ) { // success
				if ( document.getElementById( 'media-' + id ).previousElementSibling != null ) {
					focusID = document.getElementById( 'media-' + id ).previousElementSibling.id;
				} else if ( document.getElementById( 'media-' + id ).nextElementSibling != null ) {
					focusID = document.getElementById( 'media-' + id ).nextElementSibling.id;
				} else {
					focusID = addNew.id;
				}
				document.getElementById( 'media-' + id ).remove();
				closeButton.click();
			} else {
				console.log( _wpMediaGridSettings.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaGridSettings.error, error );
		} );
	}

	// Open modal
	function openModalDialog( item ) {
		var id = item.id.replace( 'media-', '' ),
			title = item.getAttribute( 'aria-label' ),
			date = item.dataset.date,
			filename = item.dataset.filename,
			filetype = item.dataset.filetype,
			mime = item.dataset.mime,
			size = item.dataset.size,
			width = item.dataset.width,
			height = item.dataset.height,
			caption = item.dataset.caption,
			description = item.dataset.description,
			taxes = item.dataset.taxes,
			tags = item.dataset.tags,
			url = item.dataset.url,
			alt = item.querySelector( 'img' ).getAttribute( 'alt' ),
			link = item.dataset.link,
			orientation = item.dataset.orientation ? ' ' + item.dataset.orientation : '',
			menuOrder = item.dataset.menuOrder, 
			prev = item.previousElementSibling ? item.previousElementSibling.id : '',
			next = item.nextElementSibling ? item.nextElementSibling.id : '';

		// Modify current URL
		queryParams.set( 'item', id );
		history.replaceState( null, null, '?' + queryParams.toString() );

		// Set menu_order, media_category, and media_post_tag field IDs correctly
		setAddedMediaFields( id );

		// Populate modal with attachment details
		dialog.querySelector( '.attachment-date' ).textContent = date;
		dialog.querySelector( '.attachment-filename' ).textContent = filename;
		dialog.querySelector( '.attachment-filetype' ).textContent = mime;
		dialog.querySelector( '.attachment-filesize' ).textContent = size;
		dialog.querySelector( '.attachment-dimensions' ).textContent = width + ' ' + _wpMediaGridSettings.by + ' ' + height + ' ' + _wpMediaGridSettings.pixels;
		dialog.querySelector( '.attachment-media-view' ).className = 'attachment-media-view' + orientation;

		dialog.querySelector( '#attachment-details-two-column-alt-text').textContent = alt;
		dialog.querySelector( '#attachment-details-two-column-title').value = title;
		dialog.querySelector( '#attachment-details-two-column-caption').textContent = caption;
		dialog.querySelector( '#attachment-details-two-column-description').textContent = description;
		dialog.querySelector( '#attachment-details-two-column-copy-link').value = url;

		dialog.querySelector( '#menu-order').value = menuOrder;
		dialog.querySelector( '#attachments-' + id + '-media_category').value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag').value = tags;

		if ( filetype === 'audio' ) {
			dialog.querySelector( '#media-image' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-audio' ).removeAttribute( 'hidden' );
			dialog.querySelector( 'audio.wp-audio-shortcode' ).src = url;
			dialog.querySelector( 'audio' ).setAttribute( 'type', mime );
		} else if ( filetype === 'video' ) {
			dialog.querySelector( '.wp-video' ).removeAttribute( 'style' );
			dialog.querySelector( '#media-image' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-audio' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).removeAttribute( 'hidden' );
			dialog.querySelector( 'video.wp-video-shortcode' ).src = url;
			dialog.querySelector( 'video' ).setAttribute( 'type', mime );
		} else {
			dialog.querySelector( '#media-audio' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-video' ).setAttribute( 'hidden', true );
			dialog.querySelector( '#media-image' ).removeAttribute( 'hidden' );
				
			if ( filetype === 'image' ) {
				dialog.querySelector( '.thumbnail-image img' ).src = url;
				dialog.querySelector( '.edit-attachment' ).style.display = '';
			} else {
				dialog.querySelector( '.thumbnail-image img' ).src = item.querySelector( 'img' ).src;
				dialog.querySelector( '.edit-attachment' ).style.display = 'none';
			}
		}
		dialog.querySelector( '.thumbnail-image img' ).setAttribute( 'alt', alt );

		dialog.querySelector( '#view-attachment').href = link;
		dialog.querySelector( '#edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + id + '&action=edit' );
		dialog.querySelector( '#download-file' ).href = url;
		leftIcon.setAttribute( 'data-prev', prev );
		rightIcon.setAttribute( 'data-next', next );

		if ( prev === '' ) {
			leftIcon.disabled = true;
		} else {
			leftIcon.disabled = false;
		}

		if ( next === '' ) {
			rightIcon.disabled = true;
		} else {
			rightIcon.disabled = false;
		}

		// Show modal
		dialog.classList.add( 'modal-loading' );
		dialog.showModal();

		// Delete media item
		dialog.querySelector( '.delete-attachment' ).addEventListener( 'click', function() {
			if ( confirm( _wpMediaGridSettings.confirm_delete ) ) {
				deleteItem( id );
			}
		} );
			
		/* Update media attachment details */
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

	/* Select and unselect media items for deletion */
	function selectItemForDeletion( item ) {
		var deleteButton = document.querySelector( '.delete-selected-button' );
		if ( item.className.includes( 'selected' ) ) {
			item.classList.remove( 'selected' );
			item.setAttribute( 'aria-checked', false );

			// Disable delete button if no media items are selected
			if ( document.querySelector( '.media-item.selected' ) == null ) {
				deleteButton.setAttribute( 'disabled', true );
			}
		} else {
			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );

			// Enable delete button
			if ( deleteButton.disabled ) {
				deleteButton.removeAttribute( 'disabled' );
			}
		}
	}

	/* Populate media items within grid */
	function populateGridItem( attachment ) {
		var gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">';

		if ( attachment.type === 'application' ) {
			if ( attachment.subtype === 'vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaGridSettings.includes_url + 'images/media/spreadsheet.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else if ( attachment.subtype === 'zip' ) {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaGridSettings.includes_url + 'images/media/archive.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			} else {
				image = '<div class="icon"><div class="centered"><img src="' + _wpMediaGridSettings.includes_url + 'images/media/document.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
			}
		} else if ( attachment.type === 'audio' ) {
			image = '<div class="icon"><div class="centered"><img src="' + _wpMediaGridSettings.includes_url + 'images/media/audio.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		} else if ( attachment.type === 'video' ) {
			image = '<div class="icon"><div class="centered"><img src="' + _wpMediaGridSettings.includes_url + 'images/media/video.png' + '" draggable="false" alt=""></div><div class="filename"><div>' + attachment.title + '</div></div></div>';
		}	

		gridItem.className = 'media-item';
		gridItem.id = 'media-' + attachment.id;
		gridItem.setAttribute( 'tabindex', 0 );
		gridItem.setAttribute( 'role', 'checkbox' );
		gridItem.setAttribute( 'aria-checked', 'false' );
		gridItem.setAttribute( 'aria-label', attachment.title );
		gridItem.setAttribute( 'data-date', attachment.dateFormatted );
		gridItem.setAttribute( 'data-url', attachment.url );
		gridItem.setAttribute( 'data-filename', attachment.filename );
		gridItem.setAttribute( 'data-filetype', attachment.type );
		gridItem.setAttribute( 'data-mime', attachment.mime );
		gridItem.setAttribute( 'data-width', attachment.width );
		gridItem.setAttribute( 'data-height', attachment.height );
		gridItem.setAttribute( 'data-size', attachment.filesizeHumanReadable )
		gridItem.setAttribute( 'data-caption', attachment.caption );
		gridItem.setAttribute( 'data-description', attachment.description );
		gridItem.setAttribute( 'data-link', attachment.link );
		gridItem.setAttribute( 'data-orientation', attachment.orientation );
		gridItem.setAttribute( 'data-menu-order', attachment.menuOrder );
		gridItem.setAttribute( 'data-taxes', attachment.media_cats );
		gridItem.setAttribute( 'data-tags', attachment.media_tags );
		gridItem.setAttribute( 'data-update-nonce', attachment.nonces.update );
		gridItem.setAttribute( 'data-delete-nonce', attachment.nonces.delete );
		gridItem.setAttribute( 'data-edit-nonce', attachment.nonces.edit );

		gridItem.innerHTML = '<div class="select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype + '">' + 
			'<div class="media-thumbnail">' + image + '</div>' +
			'</div>' +
			'<button type="button" class="check" tabindex="-1">' +
			'<span class="media-modal-icon"></span>' +
			'<span class="screen-reader-text">' + _wpMediaGridSettings.deselect + '></span>' +
			'</button>';

		return gridItem;
	}

	/* Update items displayed according to dropdown selections */
	function updateGrid() {
		var date  = dateFilter.value,
			type  = typeFilter.value,
			count = document.querySelector( '.load-more-count' ).textContent;

		// Create URLSearchParams object
		const params = new URLSearchParams( {
			'action': 'query-attachments',
			'query[posts_per_page]': document.getElementById( 'media_grid_per_page' ).value,
			'query[monthnum]': date ? parseInt( date.substr( 4, 2 ), 10 ) : 0,
			'query[year]': date ? parseInt( date.substr( 0, 4 ), 10 ) : 0,
			'query[post_mime_type]': type || '',
			'query[s]': search ? search.value : '',
			'query[media_category_name]': mediaCatSelect ? mediaCatSelect.value : '',
			'_ajax_nonce': document.getElementById( 'media_grid_nonce' ).value,
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
				if ( result.data.length === 0 ) {

					// Clear existing grid
					mediaGrid.innerHTML = '';

					// Update the count at the bottom of the page
					document.querySelector( '.load-more-count' ).setAttribute( 'hidden', true );
					document.querySelector( '.no-media' ).removeAttribute( 'hidden' );
				} else {

					// Clear existing grid
					mediaGrid.innerHTML = '';

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						var gridItem = populateGridItem( attachment );
						mediaGrid.appendChild( gridItem );
					} );

					// Open modal to show details about file, or select files for deletion
					document.querySelectorAll( '.media-item' ).forEach( function( item ) {
						item.addEventListener( 'click', function() {
							if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
								openModalDialog( item );
							} else {
								selectItemForDeletion( item );
							}
						} );
					} );

					// Update the count at the bottom of the page
					document.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					document.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					document.querySelector( '.load-more-count' ).textContent = count.replace( /[0-9]+/g, result.data.length );
				}
			} else {
				console.error( _wpMediaGridSettings.failed_update, result.data.message );
			}
		} )
		.catch( function( error ) {
			console.error( _wpMediaGridSettings.error, error );
		} );
	}

	function removeImageEditWrap() {
		if ( document.querySelector( '.imgedit-wrap' ) != null ) {
			document.querySelector( '.imgedit-wrap' ).remove();
			document.querySelector( '.attachment-details' ).removeAttribute( 'hidden' );
			document.querySelector( '.attachment-details' ).removeAttribute( 'inert' );
		}
	}

	// Open modal automatically if URL contains media item ID as query param
	if ( queryParams.has( 'item' ) ) {
		itemID = parseInt( queryParams.get( 'item' ) );
		if ( itemID != null && document.getElementById( 'media-' + itemID ) != null ) {
			setTimeout( function() {
				document.getElementById( 'media-' + itemID ).click();
				if ( queryParams.has( 'mode' ) && queryParams.get( 'mode' ) === 'edit' ) {
					document.querySelector( '.edit-attachment' ).click();
				}
			} );
		}
	}

	// Enable the setting of the media upload category
	if ( uploadCatSelect != null ) {

		if ( uploadCatSelect.value == '' ) {
			addNew.setAttribute( 'inert', true );
			addNew.setAttribute( 'disabled', true );

			// Prevent uploading file into browser window
			window.addEventListener( 'dragover', function( e ) {
				e.preventDefault();
			}, false );
			window.addEventListener( 'drop', function( e ) {
				e.preventDefault();
			}, false );
		}

		// Set up variables when a change of upload category is made.
		uploadCatSelect.addEventListener( 'change', function( e ) {
			var div,
				dismissible = document.querySelector( '.is-dismissible' ),
				uploadCatFolder = new URLSearchParams( {
					action: 'media-cat-upload',
					option: 'media_cat_upload_folder',
					media_cat_upload_value: e.target.value,
					media_cat_upload_nonce: document.getElementById( 'media_cat_upload_nonce' ).value
				} );

			// Prevent accumulation of notices.
			if ( dismissible != null ) {
				dismissible.remove();
			}

			if ( uploadCatSelect.value == '' ) {
				addNew.setAttribute( 'inert', true );
				addNew.setAttribute( 'disabled', true );

				// Prevent uploading file into browser window
				window.addEventListener( 'dragover', function( e ) {
					e.preventDefault();
				}, false );
				window.addEventListener( 'drop', function( e ) {
					e.preventDefault();
				}, false );
			} else {
				addNew.removeAttribute( 'inert' );
				addNew.removeAttribute( 'disabled' );
			}

			// Update upload category.
			fetch( ajaxurl, {
				method: 'POST',
				body: uploadCatFolder,
				credentials: 'same-origin'
			} )
			.then( function( response ) {
				if ( response.ok ) {
					return response.json(); // no errors
				}
				throw new Error( response.status );
			} )
			.then( function( response ) {
				if ( response.success ) {
					if ( response.data.value == '' ) {
						div = document.createElement( 'div' );
						div.id = 'message';
						div.className = 'notice notice-error is-dismissible';
						div.innerHTML = '<p>' + response.data.message + '</p><button class="notice-dismiss" type="button"></button>';
						document.querySelector( '.page-title-action' ).after( div );
					} else {
						div = document.createElement( 'div' );
						div.id = 'message';
						div.className = 'updated notice notice-success is-dismissible';
						div.innerHTML = '<p>' + response.data.message + '</p><button class="notice-dismiss" type="button"></button>';
						document.querySelector( '.page-title-action' ).after( div );

						// Update selected attribute in DOM.
						uploadCatSelect.childNodes.forEach( function( option ) {
							if ( option.value === e.target.value ) {
								option.setAttribute( 'selected', true );
							} else {
								option.removeAttribute( 'selected' );
							}
						} );
					}
				}
			} )
			.catch( function( error ) {
				div = document.createElement( 'div' );
				div.id = 'message';
				div.className = 'notice notice-error is-dismissible';
				div.innerHTML = '<p>' + error + '</p><button class="notice-dismiss" type="button"></button>';
				document.querySelector( '.page-title-action' ).after( div );
			} );
		} );

		// Make notices dismissible.
		document.addEventListener( 'click', function( e ) {
			if ( e.target.className === 'notice-dismiss' ) {
				document.querySelector( '.is-dismissible' ).remove();
			}
		} );
	}

	// Add event listeners for changing the selection of items displayed
	dateFilter.addEventListener( 'change', updateGrid );
	typeFilter.addEventListener( 'change', updateGrid );
	mediaCatSelect.addEventListener( 'change', updateGrid );
	search.addEventListener( 'input', function() {
		var searchtimer;
		clearTimeout( searchtimer );
		searchtimer = setTimeout( updateGrid, 200 );
	} );

	// Open and close file upload area by clicking Add New button
	addNew.addEventListener( 'click', function() {
		if ( ! uploader.hasAttribute( 'hidden' ) ) {
			uploader.setAttribute( 'inert', true );
			uploader.setAttribute( 'hidden', true );
			addNew.setAttribute( 'aria-expanded', false );
		} else {
			uploader.removeAttribute( 'inert' );
			uploader.removeAttribute( 'hidden' );
			addNew.setAttribute( 'aria-expanded', true );
		}
	} );

	// Close file upload area by clicking X button
	close.addEventListener( 'click', function() {
		uploader.setAttribute( 'inert', true );
		uploader.setAttribute( 'hidden', true );
		addNew.setAttribute( 'aria-expanded', false );
	} );

	// Set functions for Escape and Enter keys
	document.addEventListener( 'keyup', function( e ) {
		if ( e.key === 'Escape' ) {
			queryParams.delete( 'item' );			
			queryParams.delete( 'mode' );
			history.replaceState( null, null, location.href.split('?')[0] ); // reset URL params
			close.click(); // close file upload area
			if ( focusID != null ) { // set focus correctly
				document.getElementById( focusID ).focus();
				focusID = null; // reset focusID
			}
			removeImageEditWrap();
		} else if ( e.key === 'Enter' && e.target.className === 'media-item' ) {
			e.target.click(); // open modal
		}
	} );

	/* Open modal to show details about file, or select files for deletion */
	document.querySelectorAll( '.media-item' ).forEach( function( item ) {
		item.addEventListener( 'click', function() {
			if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
				openModalDialog( item );
			} else {
				selectItemForDeletion( item );
			}
		} );
	} );

	/* Close modal by clicking button */
	closeButton.addEventListener( 'click', function() {
		queryParams.delete( 'item' );		
		queryParams.delete( 'mode' );
		history.replaceState( null, null, location.href.split('?')[0] ); // reset URL params
		dialog.classList.remove( 'modal-loading' );
		dialog.close();
		if ( focusID != null ) { // set focus correctly
			document.getElementById( focusID ).focus();
			focusID = null; // reset focusID
		}
		removeImageEditWrap();
	} );

	leftIcon.addEventListener( 'click', function() {
		var id = leftIcon.dataset.prev;
		focusID = id; // set focusID for when modal is closed
		document.getElementById( id ).click();
		removeImageEditWrap();
	} );

	rightIcon.addEventListener( 'click', function() {
		var id = rightIcon.dataset.next;
		focusID = id; // set focusID for when modal is closed
		document.getElementById( id ).click();
		removeImageEditWrap();
	} );

	// Edit image
	document.querySelector( '.edit-attachment' ).addEventListener( 'click', function( e ) {
		var itemID = parseInt( queryParams.get( 'item' ) ),
			item = document.getElementById( 'media-' + itemID ),
			width = item.dataset.width,
			height = item.dataset.height,
			nonce = item.dataset.editNonce,
			action = 'rotate-cw', // or any other valid action e.g. save, scale, restore 
			target = 'full'; // or 'thumbnail', etc.

		// Construct the FormData object
		var formData = new FormData();
		formData.append( 'action', 'image-editor' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'postid', itemID );
		formData.append( 'do', action );
		formData.append( 'target', target );
		formData.append( 'context', 'edit-attachment' );

		// Make the fetch request
		fetch( ajaxurl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
		.then( function( response ) {
			if ( response.ok ) {
				return response.json(); // no errors
			}
			throw new Error( response.status );
		} )
		.then( function( result ) {
			document.querySelector( '.attachment-details' ).setAttribute( 'hidden', true );
			document.querySelector( '.attachment-details' ).setAttribute( 'inert', true );
			document.querySelector( '.media-frame-content' ).insertAdjacentHTML( 'beforeend', result.data.html );

			// Modify current URL
			queryParams.set( 'mode', 'edit' );
			history.replaceState( null, null, '?' + queryParams.toString() );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
		} );
	} );

	// Bulk select media items
	document.querySelector( '.select-mode-toggle-button' ).addEventListener( 'click', function( e ) {
		var toolbar = document.querySelector( '.cp-media-toolbar' ),
			deleteButton = document.querySelector( '.delete-selected-button' );

		if ( toolbar.className.includes( 'media-toolbar-mode-select' ) ) {
			document.querySelectorAll( '.media-item' ).forEach( function( item ) {
				if ( item.className.includes( 'selected' ) ) {
					item.classList.remove( 'selected' );
					item.setAttribute( 'aria-checked', false );
				}
			} );

			e.target.textContent = 'Bulk select';
			dateFilter.style.display = '';
			typeFilter.style.display = '';
			mediaCatSelect.style.display = '';
			deleteButton.classList.add( 'hidden' );
			toolbar.classList.remove( 'media-toolbar-mode-select' );
		} else {
			e.target.textContent = 'Cancel';
			dateFilter.style.display = 'none';
			typeFilter.style.display = 'none';
			mediaCatSelect.style.display = 'none';
			deleteButton.classList.remove( 'hidden' );
			toolbar.classList.add( 'media-toolbar-mode-select' );

			deleteButton.addEventListener( 'click', function() {
				if ( confirm( _wpMediaGridSettings.confirm_multiple ) ) {
					document.querySelectorAll( '.media-item.selected' ).forEach( function( deleteSelect ) {
						deleteItem( deleteSelect.id.replace( 'media-', '' ) );
					} );
				} 
				document.querySelector( '.select-mode-toggle-button' ).click();
			} );
		}
	} );

	/**
	 * Copies the attachment URL to the clipboard.
	 *
	 * @since CP-2.2.0
	 *
	 * @param {MouseEvent} event A click event.
	 *
	 * @return {void}
	 */
	document.querySelector( '.copy-attachment-url' ).addEventListener( 'click', function( e ) {
		var successTimeout,
			copyText = e.target.parentNode.previousElementSibling.value,
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
		clearTimeout( successTimeout );
		e.target.nextElementSibling.classList.remove( 'hidden' );
		e.target.nextElementSibling.setAttribute( 'aria-hidden', 'false' );
		input.remove();

		// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
		successTimeout = setTimeout( function() {
			e.target.nextElementSibling.classList.add( 'hidden' );
			e.target.nextElementSibling.setAttribute( 'aria-hidden', 'true' );
		}, 3000 );
		
	} );

	/* Upload files using FilePond */
	// Register FilePond plugins
	FilePond.registerPlugin(
		FilePondPluginFileValidateSize,
		FilePondPluginFileValidateType,
		FilePondPluginFileRename,
		FilePondPluginImagePreview
	);

	// Create a FilePond instance
	pond = FilePond.create( inputElement, {
		allowMultiple: true,
		server: {
			process: function( fieldName, file, metadata, load, error, progress, abort, transfer, options ) {

				// Create FormData
				var formData = new FormData();
				formData.append( 'async-upload', file, file.name );
				formData.append( 'action', 'upload-attachment' );
				formData.append( '_wpnonce', document.getElementById( '_wpnonce' ).value );

				// Use Fetch to upload the file
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
					var gridItem;
					if ( result.success ) {
						load( result.data );						
						gridItem = populateGridItem( result.data );
						mediaGrid.prepend( gridItem );

						// Open modal to show details about file, or select file for deletion
						gridItem.addEventListener( 'click', function() {
							if ( document.querySelector( '.media-toolbar-mode-select' ) == null ) {
								openModalDialog( gridItem );
							} else {
								selectItemForDeletion( gridItem );
							}
						} );
					} else {
						error( _wpMediaGridSettings.upload_failed );
					}
				} )
				.catch( function( err ) {
					error( _wpMediaGridSettings.upload_failed );
					console.error( _wpMediaGridSettings.error, err );
				} );

				// Return an abort function
				return {
					abort: function() {
						// This function is called when the user aborts the upload
						abort();
					}
				};
			},
			maxFileSize: document.getElementById( 'filepond' ).dataset.maxFileSize,
		},
		labelTapToUndo: _wpMediaGridSettings.tap_close,
		fileRenameFunction: ( file ) =>
			new Promise( function( resolve ) {
				resolve( window.prompt( _wpMediaGridSettings.new_filename, file.name ) );
			} ),
		acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
		labelFileTypeNotAllowed: _wpMediaGridSettings.invalid_type,
		fileValidateTypeLabelExpectedTypes: _wpMediaGridSettings.check_types,
	} );

} );
