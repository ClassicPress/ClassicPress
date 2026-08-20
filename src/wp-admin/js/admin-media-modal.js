/**
 * @output wp-admin/js/admin-media-modal.js
 */

/* global ajaxurl, wpAjax, Coloris, _cpCustomLogo, _cpAdminMediaModalStrings,
_cpFilepondLabels, FilePondPluginFileValidateSize, FilePondPluginFileValidateType,
FilePondPluginFileRename, FilePondPluginImagePreview, cpCropper, console */

/**
 * Admin Media Modal - Reusable media selection component.
 *
 * @since CP-2.8.0
 *
 * @param {string} attachmentAction The AJAX action to call when selecting.
 * @param {string} sizeParam The image size parameter.
 *
 * @return {void}
 */
function AdminMediaModal( attachmentAction, sizeParam, headerImage ) {
	'use strict';

	const modal = document.getElementById( 'admin-media-modal' ),
		grid = document.getElementById( 'admin-modal-grid' ),
		selectButton = document.getElementById( 'admin-modal-select-button' ),
		closeButton = modal.querySelector( '.admin-modal-close' ),
		chooseLink = document.getElementById( 'choose-from-library-link' ),
		itemUpload = document.getElementById( 'menu-item-upload' ),
		itemBrowse = document.getElementById( 'menu-item-browse' ),
		gridPanel = modal.querySelector( '.admin-modal-tabpanel' ),
		rightSidebar = modal.querySelector( '.admin-modal-right-sidebar' ),
		rightSidebarInfo = modal.querySelector( '.admin-modal-right-sidebar-info' ),
		modalPages = modal.querySelector( '.admin-modal-pages' ),
		uploadPanel = document.getElementById( 'uploader-inline' ),
		modalButtons = modal.querySelector( '.admin-modal-header-buttons' ),
		insertFromUrl = document.getElementById( 'admin-modal-item-embed' ),
		addImage = document.getElementById( 'admin-modal-item-add' ),
		footer = document.querySelector( '.admin-modal-footer' );

	let pond, selectedAttachment = null;

	function loadAttachments() {
		const params = new URLSearchParams( {
			action: 'query-attachments',
			'query[post_mime_type]': 'image',
			'query[paged]': 1
		} );

		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( ! response.ok ) {
				throw new Error( response.status );
			}
			return response.json();
		} )
		.then( function( result ) {
			if ( result.success ) {
				populateGrid( result.data );
			}
		} )
		.catch( function( error ) {
			console.error( wpAjax.broken + ':', error );
		} );
	}

	function populateGrid( attachments ) {
		grid.replaceChildren();

		if ( attachments.length === 0 ) {
			const p = document.createElement( 'p' );
			p.textContent = 'No images found.';
			grid.append( p );
			return;
		}

		attachments.forEach( function( attachment ) {
			populateGridItem( attachment );
		} );
	}

	/**
	 * Populate media items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	function populateGridItem( attachment, prepend ) {
		var selected = '',
			gridItem = document.createElement( 'li' ),
			image = document.createElement( 'img' ),
			preview = document.createElement( 'div' ),
			thumb = document.createElement( 'div' ),
			button = document.createElement( 'button' ),
			spanIcon = document.createElement( 'span' ),
			spanSR = document.createElement( 'span' );

		image.src= attachment.url;
		image.alt= attachment.alt;
		preview.className = 'select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype;
		thumb.className = 'media-thumbnail';
		button.type = 'button';
		button.className = 'check';
		button.tabindex = '-1';
		spanIcon.className = 'media-modal-icon';
		spanSR.className = 'screen-reader-text';
		spanSR.textContent = _cpFilepondLabels.labelButtonDeselect;

		gridItem.className = 'media-item' + selected;
		gridItem.id = 'media-' + attachment.id;
		gridItem.setAttribute( 'tabindex', 0 );
		gridItem.setAttribute( 'role', 'checkbox' );
		gridItem.setAttribute( 'aria-checked', selected ? 'true' : 'false' );
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

		thumb.append( image );
		button.append( spanIcon, spanSR );
		preview.append( thumb, button );
		gridItem.append( preview );

		if ( prepend ) {
			grid.prepend( gridItem );
		} else {
			grid.append( gridItem );
		}

		gridItem.addEventListener( 'click', function() {
			selectItem( gridItem, attachment );
		} );
	}

	function selectItem( gridItem, attachment ) {
		const items = grid.querySelectorAll( '.media-item' ),
			isSelected = gridItem.classList.contains( 'selected' );

		items.forEach( function( item ) {
			item.classList.remove( 'selected' );
			item.setAttribute( 'aria-checked', 'false' );
			item.querySelector( '.check' ).style.display = 'none';
			rightSidebarInfo.setAttribute( 'hidden', 'true' );
		} );

		if ( isSelected ) {
			gridItem.classList.remove( 'selected' );
			gridItem.setAttribute( 'aria-checked', 'false' );
			gridItem.querySelector( '.check' ).style.display = 'none';
			rightSidebarInfo.setAttribute( 'hidden', 'true' );
			selectedAttachment = null;
			selectButton.disabled = true;
		} else {
			gridItem.classList.add( 'selected' );
			gridItem.setAttribute( 'aria-checked', 'true' );
			gridItem.querySelector( '.check' ).style.display = 'block';
			rightSidebarInfo.removeAttribute( 'hidden' );
			selectedAttachment = attachment;
			setAddedMediaFields( attachment );

			// Populate modal with attachment details
			modal.querySelector( '.attachment-date' ).textContent = attachment.dateFormatted;
			modal.querySelector( '.attachment-filename' ).textContent = attachment.filename;
			modal.querySelector( '.attachment-filesize' ).textContent = attachment.filesizeHumanReadable;
			modal.querySelector( '.attachment-dimensions' ).textContent = attachment.width + ' ' + _cpAdminMediaModalStrings.by + ' ' + attachment.height + ' ' + _cpAdminMediaModalStrings.pixels;
			document.getElementById( 'edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + attachment.id + '&action=edit' );
			modal.querySelector( '.admin-modal-attachment-info img' ).src = attachment.url;
			modal.querySelector( '.admin-modal-attachment-info img' ).alt = attachment.alt;
			document.getElementById( 'attachment-details-alt-text' ).value = attachment.alt;
			document.getElementById( 'attachment-details-title' ).value = attachment.title;
			document.getElementById( 'attachment-details-caption' ).value = attachment.caption;
			document.getElementById( 'attachment-details-description' ).value = attachment.description;
			document.getElementById( 'attachment-details-copy-link' ).value = attachment.url;

			selectButton.disabled = false;
		}
	}

	function handleSelect() {
		const nonceField = document.getElementById( '_wpnonce' );
		let params;

		if ( ! selectedAttachment ) {
			return;
		}

		if ( headerImage ) {
			modal.close();

			window.CustomHeaderCrop = {
				id: selectedAttachment.id,
				url: selectedAttachment.url,
				width: selectedAttachment.width,
				height: selectedAttachment.height,
				nonce: selectedAttachment.nonces.edit
			};

			document.dispatchEvent( new CustomEvent( 'custom-header-attachment-selected' ) );
			return;
		}

		params = new URLSearchParams( {
			action: attachmentAction,
			attachment_id: selectedAttachment.id,
			_ajax_nonce: nonceField ? nonceField.value : '',
			size: sizeParam || 'full'
		} );

		fetch( ajaxurl, {
			method: 'POST',
			body: params,
			credentials: 'same-origin'
		} )
		.then( function( response ) {
			if ( ! response.ok ) {
				throw new Error( response.status );
			}
			window.location.reload();
		} )
		.catch( function( error ) {
			console.error( wpAjax.broken + ':', error );
		} );
	}

	function browseMediaLibrary( e ) {
		e.preventDefault();
		insertFromUrl.classList.remove ( 'active' );
		insertFromUrl.setAttribute( 'aria-selected', 'false' );
		itemUpload.classList.remove( 'active' );
		itemUpload.setAttribute( 'aria-selected', 'false' );
		addImage.classList.add( 'active' );
		addImage.setAttribute( 'aria-selected', 'true' );
		itemBrowse.classList.add ( 'active' );
		itemBrowse.setAttribute( 'aria-selected', 'true' );
		uploadPanel.setAttribute( 'hidden', 'true' );
		uploadPanel.setAttribute( 'inert', 'true' );
		gridPanel.removeAttribute( 'hidden' );
		gridPanel.removeAttribute( 'inert' );
		rightSidebar.removeAttribute( 'hidden' );
		rightSidebar.removeAttribute( 'inert' );
		modalPages.removeAttribute( 'hidden' );
		modalPages.removeAttribute( 'inert' );
		footer.style.display = '';
	}

	function uploadFile( e ) {
		e.preventDefault();
		addImage.classList.remove( 'active' );
		addImage.setAttribute( 'aria-selected', 'false' );
		itemBrowse.classList.remove( 'active' );
		itemBrowse.setAttribute( 'aria-selected', 'false' );
		insertFromUrl.classList.add ( 'active' );
		insertFromUrl.setAttribute( 'aria-selected', 'true' );
		itemUpload.classList.add ( 'active' );
		itemUpload.setAttribute( 'aria-selected', 'true' );
		uploadPanel.removeAttribute( 'hidden' );
		uploadPanel.removeAttribute( 'inert' );
		gridPanel.setAttribute( 'hidden', 'true' );
		gridPanel.setAttribute( 'inert', 'true' );
		rightSidebar.setAttribute( 'hidden', 'true' );
		rightSidebar.setAttribute( 'inert', 'true' );
		modalPages.setAttribute( 'hidden', 'true' );
		modalPages.setAttribute( 'inert', 'true' );
		footer.style.display = 'none';
		goFilepond();
	}

	/**
	 * Update the grid with new images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateGrid( paged ) {
		var dateFilter = document.getElementById( 'filter-by-date' ),
			mediaCatSelect = document.getElementById( 'taxonomy=media_category&term' ),
			search = document.getElementById( 'admin-modal-search-input' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[monthnum]': dateFilter.value ? parseInt( dateFilter.value.substr( 4, 2 ), 10 ) : 0,
				'query[year]': dateFilter.value ? parseInt( dateFilter.value.substr( 0, 4 ), 10 ) : 0,
				'query[post_mime_type]': 'image',
				'query[s]': search.value ? search.value : '',
				'query[paged]': paged ? paged : 1,
				'query[media_category_name]': mediaCatSelect.value ? mediaCatSelect.value : ''
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

				// Show relevant button and clear grid
				modal.querySelector( '.admin-modal-grid' ).replaceChildren();

				if ( result.data.length === 0 ) {

					// Reset pagination
					modal.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						pageLink.setAttribute( 'data-page', 1 );
						pageLink.setAttribute( 'disabled', 'true' );
						pageLink.setAttribute( 'inert', 'true' );
					} );

					modal.querySelector( '#current-page-selector' ).setAttribute( 'value', 1 );
					modal.querySelector( '.total-pages' ).textContent = 1;
					modal.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, 0 );

					// Update the count at the bottom of the page
					modal.querySelector( '.load-more-count' ).setAttribute( 'hidden', 'true' );
					modal.querySelector( '.no-media' ).removeAttribute( 'hidden' );
				} else {

					// Populate grid with new items
					result.data.forEach( function( attachment ) {
						populateGridItem( attachment );
					} );

					// Reset pagination
					modal.querySelectorAll( '.pagination-links button' ).forEach( function( pageLink ) {
						if ( pageLink.className.includes( 'first-page' ) || pageLink.className.includes( 'prev-page' ) ) {
							if ( paged === 1 ) {
								pageLink.setAttribute( 'disabled', 'true' );
								pageLink.setAttribute( 'inert', 'true' );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
								if ( pageLink.className.includes( 'prev-page' ) ) {
									if ( ( parseInt( paged ) - 1 ) < 1 ) {
										pageLink.setAttribute( 'data-page', 1 );
									} else {
										pageLink.setAttribute( 'data-page', parseInt( paged ) - 1 );
									}
								}
							}
						} else if ( pageLink.className.includes( 'next-page' ) ) {
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'data-page', paged );
								pageLink.setAttribute( 'disabled', 'true' );
								pageLink.setAttribute( 'inert', 'true' );
							} else {
								pageLink.setAttribute( 'data-page', parseInt( paged ) + 1 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'disabled', 'true' );
								pageLink.setAttribute( 'inert', 'true' );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					modal.querySelector( '#current-page-selector' ).setAttribute( 'value', paged ? paged : 1 );
					modal.querySelector( '#current-page-selector' ).value = paged ? paged : 1;
					modal.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					modal.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Update the count at the bottom of the page
					modal.querySelector( '.no-media' ).setAttribute( 'hidden', 'true' );
					modal.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					modal.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + 'of' + ' ' + result.headers.total_posts + ' ' + _cpAdminMediaModalStrings.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( error );
		} );

		modal.showModal();
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
			var saved = document.getElementById( 'details-saved' );

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
				console.error( _cpAdminMediaModalStrings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _cpAdminMediaModalStrings.error, error );
		} );
	}

	/**
	 * Update details within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setAddedMediaFields( attachment ) {
		const form = document.createElement( 'form' ),
			id = attachment.id,
			cats = attachment.media_cats.toString(),
			tags = attachment.media_tags.toString();

		form.className = 'compat-item';
		form.innerHTML = '<input type="hidden" id="menu-order" name="attachments[' + id + '][menu_order]" value="0">' +
			'<p class="media-types media-types-required-info"><span class="required-field-message">Required fields are marked <span class="required">*</span></span></p>' +
			'<div class="setting" data-setting="media_category">' +
				'<label for="attachments-' + id + '-media_category" style="width:30%;">' +
					'<span class="alignleft">Media Categories</span>' +
				'</label>' +
				'<input list="admin-modal-media-categories" type="text" class="text" id="attachments-' + id + '-media_category" name="attachments[' + id + '][media_category]" value="' + cats + '">' +
			'</div>' +
			'<div class="setting" data-setting="media_post_tag">' +
				'<label for="attachments-' + id + '-media_post_tag">' +
					'<span class="alignleft">Media Tags</span>' +
				'</label>' +
				'<input list="admin-modal-media-tags" type="text" class="text" id="attachments-' + id + '-media_post_tag" name="attachments[' + id + '][media_post_tag]" value="' + tags + '">' +
			'</div>';

		if ( document.querySelector( '.compat-item' ) != null ) {
			document.querySelector( '.compat-item' ).remove();
		}
		document.querySelector( '.attachment-compat' ).append( form );
		
		form.querySelectorAll( 'input' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				updateMediaTaxOrTag( input, id ); // Update media categories and tags
			} );
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
				console.error( _cpAdminMediaModalStrings.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( _cpAdminMediaModalStrings.error, error );
		} );
	}

	// Delete attachment from within modal
	function deleteItem( id ) {
		var mediaItem = document.getElementById( id );
		if ( ! mediaItem ) {
			return;
		}
		var data = new URLSearchParams( {
			action: 'delete-post',
			_ajax_nonce: mediaItem.dataset.deleteNonce,
			id: id.replace( 'media-', '' )
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
				if ( mediaItem.previousElementSibling ) {
					document.getElementById( mediaItem.previousElementSibling.id ).focus();
				} else if ( mediaItem.nextElementSibling ) {
					document.getElementById( mediaItem.nextElementSibling.id ).focus();
				} else {
					closeModal();
				}
				mediaItem.remove();
				rightSidebarInfo.setAttribute( 'hidden', 'true' );
				resetDataOrdering( 'minus' );
			} else {
				console.log( _cpAdminMediaModalStrings.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( _cpAdminMediaModalStrings.error, error );
		} );
	}

	// Open modal on choose link click
	chooseLink.addEventListener( 'click', function( e ) {
		e.preventDefault();
		modal.showModal();
		loadAttachments();
	} );

	// Close on close button
	closeButton.addEventListener( 'click', function() {
		modal.close();
		selectedAttachment = null;
		selectButton.disabled = true;
	} );

	// Handle select button
	selectButton.addEventListener( 'click', handleSelect );

	// Enable file upload
	itemUpload.addEventListener( 'click', uploadFile );
	insertFromUrl.addEventListener( 'click', uploadFile );

	// Browse media library
	itemBrowse.addEventListener( 'click', browseMediaLibrary );
	addImage.addEventListener( 'click', browseMediaLibrary );

	// Update media attachment details
	rightSidebarInfo.querySelectorAll( 'input, textarea' ).forEach( function( input ) {
		input.addEventListener( 'change', function() {
			updateDetails( input, document.querySelector( '.media-item.selected' ).dataset.id );
		} );
	} );

	// Delete attachment
	modal.querySelector( '.delete-attachment' ).addEventListener( 'click', function() {
		const id = document.querySelector( '.media-item.selected' ).id;
		if ( id && window.confirm( _cpAdminMediaModalStrings.confirm_delete ) ) {
			deleteItem( id );
		}
	} );

	/**
	 * Enable searching for items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	modal.addEventListener( 'change', function( e ) {
		// Do not run if file sought from widget
		if ( e.target.closest( '.widget' ) ) {
			return;
		}

		if ( e.target.id === 'filter-by-date' ) {
			updateGrid( 1 );
		} else if ( e.target.id === 'taxonomy=media_category&term' ) {
			updateGrid( 1 );
		} else if ( e.target.id === 'current-page-selector' ) {
			updateGrid( e.target.value );
		} else if ( e.target.id === 'admin-modal-search-input' ) {
			updateGrid( 1 );
			rightSidebarInfo.setAttribute( 'hidden', 'true' );
		}
	} );

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
	document.querySelector( '.copy-attachment-url' ).addEventListener( 'click', function( e ) {
		var button = e.target,
			copyAttachmentURLSuccessTimeout,
			copyText = document.getElementById( 'attachment-details-copy-link' ).value,
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
		input.remove();

		button.nextElementSibling.classList.remove( 'hidden' );

		// Hide success visual feedback after 3 seconds since last success and unfocus the trigger.
		copyAttachmentURLSuccessTimeout = setTimeout( function() {
			button.nextElementSibling.classList.add( 'hidden' );
		}, 3000 );

		// Handle success audible feedback.
		wp.a11y.speak( wp.i18n.__( 'The file URL has been copied to your clipboard' ) );
	} );

	/**
	 * Upload files using FilePond
	 */
	function goFilepond() {

		// Register FilePond plugins
		FilePond.registerPlugin(
			FilePondPluginFileValidateSize,
			FilePondPluginFileValidateType,
			FilePondPluginFileRename,
			FilePondPluginImagePreview
		);

		// Create a FilePond instance
		pond = FilePond.create( modal.querySelector( '#filepond' ), {
			allowMultiple: true,
			server: {
				process: function( fieldName, file, metadata, load, error, progress, abort ) {

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
							populateGridItem( result.data, 'prepend' );
						} else {
							error( _cpFilepondLabels.labelFileProcessingError );
						}
					} )
					.catch( function( err ) {
						error( _cpFilepondLabels.labelFileProcessingError );
						console.error( _cpFilepondLabels.labelFileProcessingError, err );
					} );

					// Return an abort function
					return {
						abort: function() {
							// This function is called when the user aborts the upload
							abort();
						}
					};
				},
				maxFileSize: modal.querySelector( '#ajax-url' ).dataset.maxFileSize
			},
			onprocessfile: ( error, file ) => { // Called when an individual file upload completes
				if ( ! error ) {
					setTimeout( function() {
						pond.removeFile( file.id );
					}, 100 );
					resetDataOrdering( 'plus' );
				}
			},
			onprocessfiles: () => { // Called when all files in the queue have finished uploading
				itemBrowse.click();
				setTimeout( function() {
					rightSidebarInfo.setAttribute( 'hidden', 'true' );
					grid.querySelector( 'li' ).focus();
				}, 500 );
			},
			labelTapToUndo: _cpFilepondLabels.labelTapToClose,
			fileRenameFunction: ( file ) =>
				new Promise( function( resolve ) {
					const newName = window.prompt(
						_cpFilepondLabels.labelNewFileName,
						file.name
					);
					resolve( newName === null ? file.name : newName );
				}
			),
			acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
			labelFileTypeNotAllowed: _cpFilepondLabels.labelInvalidType,
			fileValidateTypeLabelExpectedTypes: _cpFilepondLabels.labelCheckTypes
		} );
	}

	// Reset ordering of media items
	function resetDataOrdering( sign ) {
		var items = document.querySelectorAll( '.media-item' ),
			count = document.querySelector( '.load-more-count' ).textContent.split( ' ' ),
			newTotal = sign === 'minus' ? parseInt( count[2], 10 ) - 1 : parseInt( count[2], 10 ) + 1;

		items.forEach( function( item, index ) {
			item.setAttribute( 'data-order', parseInt( index + 1 ) );
		} );

		document.querySelector( '.load-more-count' ).textContent = items.length + ' ' + _cpAdminMediaModalStrings.of + ' ' + newTotal + ' ' + _cpAdminMediaModalStrings.items;
		document.querySelector( '.displaying-num' ).textContent = items.length + ' ' + _cpAdminMediaModalStrings.items;
	}
}
