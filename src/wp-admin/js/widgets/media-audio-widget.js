/* eslint consistent-this: [ "error", "control" ] */
/* global ajaxurl, AUDIO_WIDGET, console, FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginFileRename, FilePondPluginImagePreview */

/*
 * @since CP-2.5.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var addButton, pond,
		{ FilePond } = window, // import FilePond
		dialog = document.getElementById( 'widget-modal' );

	/**
	 * Update details within modal.
	 *
	 * @abstract
	 * @return {void}
	 */
	function setAddedMetaFields( artist, album ) {
		var fields = document.createElement( 'div' );
		fields.className = 'artist-album';
		fields.innerHTML = '<div class="setting" data-setting="artist">' +
			'<label for="attachment-details-artist" class="name">' + AUDIO_WIDGET.artist + '</label>' +
			'<input type="text" id="attachment-details-artist" value="' + artist + '">' +
			'</div>' +
			'<div class="setting" data-setting="album">' +
			'<label for="attachment-details-album" class="name">' + AUDIO_WIDGET.album + '</label>' +
			'<input type="text" id="attachment-details-album" value="' + album + '">' +
			'</div>';

		if ( document.querySelector( '.artist-album' ) != null ) {
			document.querySelector( '.artist-album' ).remove();
		}
		document.querySelector( '.widget-modal-descriptions .settings-save-status' ).after( fields );
	}

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
		if ( input.parentNode.dataset.setting === 'title' ) {
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
				if ( input.parentNode.dataset.setting === 'title' ) {
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
				console.error( AUDIO_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( AUDIO_WIDGET.error, error );
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
				console.error( AUDIO_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( AUDIO_WIDGET.error, error );
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
				console.log( AUDIO_WIDGET.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( AUDIO_WIDGET.error, error );
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
			caption = item.dataset.caption,
			description = item.dataset.description,
			taxes = item.dataset.taxes,
			tags = item.dataset.tags,
			artist = item.dataset.artist,
			album = item.dataset.album,
			url = item.dataset.url,
			updateNonce = item.dataset.updateNonce,
			deleteNonce = item.dataset.deleteNonce,
			inputs = dialog.querySelectorAll( '.widget-modal-right-sidebar input, .widget-modal-right-sidebar textarea, .widget-modal-media-embed input, .widget-modal-media-embed textarea' ),
			selects = dialog.querySelectorAll( '.widget-modal-right-sidebar select, .widget-modal-media-embed select' ),
			template = document.getElementById( 'tmpl-edit-audio-modal' ),
			clone = template.content.cloneNode( true ),
			audioClone = clone.querySelector( 'audio' );

		// Set artist, album, menu_order, media_category, and media_post_tag field IDs correctly
		audioClone.querySelector( 'source' ).src = url;
		if ( dialog.querySelector( '.wp_audio_shortcode' ) ) {
			dialog.querySelector( '.wp_audio_shortcode' ).replaceWith( audioClone );
		} else {
			dialog.querySelector( '.widget-modal-attachment-info' ).prepend( audioClone );
		}
		dialog.querySelector( '.widget-modal-attachment-info .details' ).style.width = '100%';

		if ( dialog.querySelector( '.alt-text' ) ) {
			dialog.querySelector( '.alt-text' ).remove();
		}
		if ( dialog.querySelector( '#alt-text-description' ) ) {
			dialog.querySelector( '#alt-text-description' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-display-settings' ) ) {
			dialog.querySelector( '.widget-modal-display-settings' ).previousElementSibling.remove();
			dialog.querySelector( '.widget-modal-display-settings' ).remove();
		}
		setAddedMetaFields( artist, album );
		setAddedMediaFields( id );

		// Populate modal with attachment details
		dialog.querySelector( '.attachment-date' ).textContent = date;
		dialog.querySelector( '.attachment-filename' ).textContent = filename;
		dialog.querySelector( '#edit-more' ).href = ajaxurl.replace( 'admin-ajax.php', 'post.php?post=' + id + '&action=edit' );
		dialog.querySelector( '#attachment-details-description').textContent = description;
		dialog.querySelector( '#attachment-details-copy-link').value = url;

		if ( clicked === true ) {
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = caption;
		} else {
			dialog.querySelector( '#attachment-details-title').value = title;
			dialog.querySelector( '#attachment-details-caption').textContent = '';
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

		dialog.querySelector( '#attachments-' + id + '-media_category').value = taxes;
		dialog.querySelector( '#attachments-' + id + '-media_post_tag').value = tags;

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

				// Disable add to widget button if no media items are selected
				if ( document.querySelector( '.media-item.selected' ) == null ) {
					addButton.setAttribute( 'disabled', true );
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
			}
		} else {
			// Prevent selection of multiple items
			if ( selectedItems ) {
				selectedItems.forEach( function( selectedItem ) {
					selectedItem.classList.remove( 'selected' );
					selectedItem.setAttribute( 'aria-checked', false );
					selectedItem.querySelector( '.check' ).style.display = 'none';
				} );
			}

			item.classList.add( 'selected' );
			item.setAttribute( 'aria-checked', true );
			item.querySelector( '.check' ).style.display = 'block';

			// Enable add to widget button
			addButton.removeAttribute( 'disabled' );
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
			gridItem = document.createElement( 'li' );

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
		gridItem.setAttribute( 'data-size', attachment.filesizeHumanReadable );
		gridItem.setAttribute( 'data-caption', attachment.caption );
		gridItem.setAttribute( 'data-description', attachment.description );
		gridItem.setAttribute( 'data-link', attachment.link );
		gridItem.setAttribute( 'data-orientation', attachment.orientation );
		gridItem.setAttribute( 'data-menu-order', attachment.menuOrder );
		gridItem.setAttribute( 'data-taxes', attachment.media_cats );
		gridItem.setAttribute( 'data-tags', attachment.media_tags );
		gridItem.setAttribute( 'data-artist', attachment.meta.artist );
		gridItem.setAttribute( 'data-album', attachment.meta.album );
		gridItem.setAttribute( 'data-update-nonce', attachment.nonces.update );
		gridItem.setAttribute( 'data-delete-nonce', attachment.nonces.delete );
		gridItem.setAttribute( 'data-edit-nonce', attachment.nonces.edit );

		gridItem.innerHTML = '<div class="select-attachment-preview type-' + attachment.type + ' subtype-' + attachment.subtype + '">' +
			'<div class="media-thumbnail">' +
			'<div class="icon">' +
			'<div class="centered">' +
			'<img src="' + AUDIO_WIDGET.includes_url + 'images/media/audio.png" draggable="false" alt="">' +
			'</div>' +
			'<div class="filename">' +
			'<div>' + attachment.filename + '</div>' +
			'</div>' +
			'</div>' +
			'</div>' +
			'</div>' +
			'<button type="button" class="check" tabindex="-1">' +
			'<span class="media-modal-icon"></span>' +
			'<span class="screen-reader-text">' + AUDIO_WIDGET.deselect + '></span>' +
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
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': AUDIO_WIDGET.per_page,
				'query[post_mime_type]': 'audio',
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
				header.after( dialogContent );
				dialog.querySelector( '#menu-item-embed' ).removeAttribute( 'hidden' );
				dialog.querySelector( '#menu-item-add' ).textContent = AUDIO_WIDGET.add_audio;
				dialog.querySelector( '.widget-modal-attachment-info .thumbnail-image' ).remove();
				checkWindowWidth();

				// Set widget ID and values of variables
				dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId = widget.id;
				addButton = dialog.querySelector( '#media-button-insert' );
				dialog.querySelector( '#menu-item-browse' ).click();

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
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + AUDIO_WIDGET.of + ' ' + result.headers.total_posts + ' ' + AUDIO_WIDGET.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( AUDIO_WIDGET.error, error );
		} );
		dialog.showModal();
	}

	/**
	 * Update the grid with new images.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateGrid( widget, paged ) {
		var dateFilter = dialog.querySelector( '#filter-by-date' ),
			mediaCatSelect = dateFilter.nextElementSibling,
			search = dialog.querySelector( '#widget-modal-search-input' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': AUDIO_WIDGET.per_page,
				'query[monthnum]': dateFilter.value ? parseInt( dateFilter.value.substr( 4, 2 ), 10 ) : 0,
				'query[year]': dateFilter.value ? parseInt( dateFilter.value.substr( 0, 4 ), 10 ) : 0,
				'query[post_mime_type]': 'audio',
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

				// Show relevant buttons and clear grid
				addButton = dialog.querySelector( '#media-button-insert' );
				if ( addButton === null ) {
					 addButton = dialog.querySelector( '#gallery-button-update' );
				} else if ( addButton === null ) {
					 addButton = dialog.querySelector( '#gallery-button-new' );
				}

				dialog.querySelector( '.widget-modal-grid' ).innerHTML = '';

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
						if ( pageLink.className.includes( 'first-page' ) || pageLink.className.includes( 'prev-page' ) ) {
							if ( paged === '1' ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
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
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.setAttribute( 'data-page', parseInt( paged ) + 1 );
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						} else if ( pageLink.className.includes( 'last-page' ) ) {
							pageLink.setAttribute( 'data-page', result.headers.max_pages );
							if ( result.headers.max_pages === parseInt( paged ) ) {
								pageLink.setAttribute( 'disabled', true );
								pageLink.setAttribute( 'inert', true );
							} else {
								pageLink.removeAttribute( 'disabled'  );
								pageLink.removeAttribute( 'inert'  );
							}
						}
					} );

					// Update both HTML and DOM
					dialog.querySelector( '#current-page-selector' ).setAttribute( 'value', paged ? paged : 1 );
					dialog.querySelector( '#current-page-selector' ).value = paged ? paged : 1;
					dialog.querySelector( '.total-pages' ).textContent = result.headers.max_pages;
					dialog.querySelector( '.displaying-num' ).textContent = document.querySelector( '.displaying-num' ).textContent.replace( /[0-9]+/, result.headers.total_posts );

					// Open modal to show details about file, or select files for deletion
					if ( dialog.querySelector( '.media-item.selected' ) == null ) {
						addButton.setAttribute( 'disabled', true );
						dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
					}
					dialog.querySelectorAll( '.media-item' ).forEach( function( item ) {
						if ( item.className.includes( 'selected' ) ) {
							selectItemToAdd( item, widget, false );
							item.focus();
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, widget, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + AUDIO_WIDGET.of + ' ' + result.headers.total_posts + ' ' + AUDIO_WIDGET.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( AUDIO_WIDGET.error, error );
		} );

		dialog.showModal();
	}

	/**
	 * Check if image URL is valid and display if possible.
	 *
	 * @abstract
	 * @return {void}
	 */
	function validateImageUrl( url ) {
		var fileType = url.split( '.' ).pop(),
			error    = document.createElement( 'div' ),
			message  = dialog.querySelector( '#message' ),
			audio    = document.createElement( 'audio' ),
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId,
			widget   = document.getElementById( widgetId );

		audio.src = url;
		audio.onerror = function() {
			if ( message ) {
				message.remove();
			}
			error.id = 'message';
			error.className = 'notice-error is-dismissible';
			error.innerHTML = '<p style="color:#fff;background:red;font-size:1em;padding:0.5em 1em;margin-top:-1px;">' + AUDIO_WIDGET.wrong_url + '</p>';
			dialog.querySelector( '#widget-modal-embed-url-field' ).before( error );

			// Disable other fields until the issue is corrected
			addButton.setAttribute( 'disabled', true );
		};

		widget.querySelector( '[data-property="url"]' ).value = url;
		if ( AUDIO_WIDGET.audio_file_types.includes( fileType.toLowerCase() ) ) {
			if ( message ) {
				message.remove();
			}
		} else {
			if ( message ) {
				message.remove();
			}
			error.id = 'message';
			error.className = 'notice-error';
			error.innerHTML = '<p style="color:#fff;background:red;font-size:16px;padding:0.5em 1em;margin-top:-1px;">' + AUDIO_WIDGET.unsupported_file_type + '</p>';
			dialog.querySelector( '#widget-modal-embed-url-field' ).before( error );

			// Disable other fields until the issue is corrected
			addButton.setAttribute( 'disabled', true );
		}
	}

	/**
	 * Insert image from URL.
	 *
	 * @abstract
	 * @return {void}
	 */
	function insertEmbed() {
		var embed = dialog.querySelector( '#widget-modal-embed-url-field' ),
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId,
			widget = document.getElementById( widgetId ),
			originalUrl = widget.querySelector( '[data-property="url"]' ).value;

		dialog.querySelector( '#widget-modal-url-settings' ).style.display = 'none';

		embed.value = originalUrl;
		if ( originalUrl !== '' ) {
			validateImageUrl( originalUrl );
		}

		embed.addEventListener( 'change', function( e ) {
			var url = e.target.value,
				message = dialog.querySelector( '#message' );

			// Activate Add to Widget button if appropriate
			if ( url !== originalUrl ) {
				addButton.removeAttribute( 'disabled' );
				validateImageUrl( url );
			} else {
				if ( message ) {
					message.remove();
				}
				addButton.setAttribute( 'disabled', true );
				if ( url === originalUrl ) {
					validateImageUrl( originalUrl );
				}
			}
		} );
	}

	/**
	 * Add image to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function addItemToWidget( widget ) {
		var selectedItem, fileType,
			editNonce = '',
			audioElement = document.createElement( 'audio' ),
			source = document.createElement( 'source' ),
			fieldset = document.createElement( 'fieldset' );

		// Append source to audio element
		audioElement.className = 'wp_audio_shortcode';
		audioElement.style.width = '100%';
		audioElement.controls = true;
		audioElement.setAttribute( 'draggable', false );

		// Add from URL
		if ( ! dialog.querySelector( '#insert-from-url-panel' ).hasAttribute( 'hidden' ) ) {
			source.src = dialog.querySelector( '#widget-modal-embed-url-field' ).value;
			fileType = source.src.split( '.' ).pop();

			// Add values to widget fields
			widget.querySelector( '[data-property="attachment_id"]' ).value = 0;

		// Add from Media Library
		} else if ( ! dialog.querySelector( '#media-library-grid' ).hasAttribute( 'hidden' ) ) {
			selectedItem = dialog.querySelector( '.widget-modal-grid .selected' );
			editNonce = selectedItem.dataset.editNonce;
			fileType = selectedItem.dataset.url.split( '.' ).pop();
			source.src = selectedItem.dataset.url;

			// Add values to widget fields
			widget.querySelector( '[data-property="attachment_id"]' ).value = selectedItem.dataset.id;
		}

		// Add Edit and Replace buttons
		fieldset.className = 'media-widget-buttons';
		fieldset.innerHTML = '<button type="button" class="button edit-media" data-edit-nonce="' + editNonce + '">' + AUDIO_WIDGET.edit_audio + '</button>' +
			'<button type="button" class="button change-media select-media">' + AUDIO_WIDGET.replace_audio + '</button>';

		// Insert audio according to whether this is a new insertion or replacement
		widget.querySelector( '[data-property="url"]' ).value = source.src;
		widget.querySelector( '[data-property="' + fileType + '"]' ).value = source.src;

		audioElement.append( source );
		widget.querySelector( '.media_audio' ).innerHTML = '';
		widget.querySelector( '.media_audio' ).prepend( audioElement );

		if ( widget.querySelector( '.media-widget-buttons' ) == null ) {
			widget.querySelector( '.media_audio' ).after( fieldset );
		}

		// Activate Save/Publish button
		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.dispatchEvent( new Event( 'change' ) );

		// Explicitly enable Save button (required by some browsers)
		widget.querySelector( '.widget-control-save' ).disabled = false;

		closeModal();
	}

	/**
	 * Update a media audio widget after editing the audio settings.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateAudioToWidget( widget ) {
		if ( dialog.querySelector( '#audio-modal-content audio' ) == null ) {
			widget.querySelector( '[data-property="url"]' ).value = '';
			widget.querySelector( '[data-property="preload"]' ).value = 'none';
			widget.querySelector( '[data-property="loop"]' ).value = '';
			if ( widget.querySelector( '.wp_audio_shortcode' ) ) {
				widget.querySelector( '.wp_audio_shortcode' ).remove();
			} else {
				widget.querySelector( '.wp-audio-shortcode' ).remove();
			}
			widget.querySelector( '.media_audio' ).classList.remove( 'populated' );
			widget.querySelector( '.reset_widget' ).value = '1';
		} else {
			widget.querySelector( '[data-property="url"]' ).value = dialog.querySelector( '#audio-details-source' ).value;
			widget.querySelector( '[data-property="preload"]' ).value = dialog.querySelector( '#preload' ).value;
			widget.querySelector( '[data-property="loop"]' ).value = dialog.querySelector( '#audio-details-loop' ).checked ? '1' : '';
		}

		// Activate Save/Publish button
		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.dispatchEvent( new Event( 'change' ) );

		// Explicitly enable Save button (required by some browsers)
		widget.querySelector( '.widget-control-save' ).disabled = false;

		closeModal();
	}

	/**
	 * Open the dialog to modify the selected item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editAudio( widget ) {
		var	header, preloaded, audioSource,
			preload = widget.querySelector( '[data-property="preload"]' ).value,
			mp3 = widget.querySelector( '[data-property="mp3"]' ).value,
			ogg = widget.querySelector( '[data-property="ogg"]' ).value,
			flac = widget.querySelector( '[data-property="flac"]' ).value,
			m4a = widget.querySelector( '[data-property="m4a"]' ).value,
			wav	= widget.querySelector( '[data-property="wav"]' ).value,
			url	= widget.querySelector( '[data-property="url"]' ).value,
			template = document.getElementById( 'tmpl-edit-audio-modal' ),
			clone = template.content.cloneNode( true ),
			cancelButton = dialog.querySelector( '#menu-item-add' ),
			detailsButton = document.createElement( 'button' );

		if ( url === null ) {
			url = mp3;
		}
		if ( url === null ) {
			url = ogg;
		}
		if ( url === null ) {
			url = flac;
		}
		if ( url === null ) {
			url = m4a;
		}
		if ( url === null ) {
			url = wav;
		}
		if ( url === null ) {
			console.error( AUDIO_WIDGET.no_audio_selected );
			return;
		}

		// Append cloned template and establish new variables
		dialog.style.width = '85%';
		dialog.style.height = '85%';

		header = dialog.querySelector( 'header' );
		header.after( clone.querySelector( '#audio-modal-content' ) );
		header.querySelector( '.widget-modal-headings' ).style.padding = '0 0.5em 0 0.75em';
		header.querySelector( '#widget-modal-title h2' ).textContent = AUDIO_WIDGET.audio_details;

		dialog.querySelector( '.wp_audio_shortcode source' ).src = url;
		audioSource = dialog.querySelector( '#audio-details-source' );
		audioSource.value = url;
		audioSource.previousElementSibling.value = url.split( '.' ).pop().toUpperCase();

		cancelButton.textContent = AUDIO_WIDGET.cancel_edit;
		cancelButton.classList.add( 'cancel' );
		cancelButton.classList.remove( 'active' );
		cancelButton.setAttribute( 'aria-selected', false );

		detailsButton.id = 'menu-item-audio-details';
		detailsButton.className = 'media-menu-item active';
		detailsButton.type = 'button';
		detailsButton.role = 'tab';
		detailsButton.setAttribute( 'aria-selected', true );
		detailsButton.textContent = AUDIO_WIDGET.details_button;
		dialog.querySelector( '.separator' ).after( detailsButton );

		dialog.querySelector( '#menu-item-embed' ).setAttribute( 'hidden', true );
		dialog.querySelector( '#audio-modal-content' ).dataset.widgetId = widget.id;
		dialog.querySelector( '.widget-modal-main' ).append( clone.querySelector( 'footer' ) );

		checkWindowWidth();

		// Widget-specific details
		dialog.querySelector( '#audio-details-loop' ).checked = widget.querySelector( '[data-property="loop"]' ).value === '1' ? true : false;

		dialog.showModal();

		// Trigger update button when other changes made to inputs or textareas
		preloaded = dialog.querySelector( '#preload' );
		preloaded.querySelectorAll( 'option' ).forEach( function( option ) {
			option.removeAttribute( 'selected' );
			if ( option.value === preload ) {
				option.setAttribute( 'selected', true );
			}
		} );
		preloaded.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			preloaded.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			dialog.querySelector( '#audio-button-update' ).disabled = false;
		} );

		dialog.querySelector( '#audio-details-loop' ).addEventListener( 'change', function() {
			dialog.querySelector( '#audio-button-update' ).disabled = false;
		} );

		dialog.querySelectorAll( '.add-media-source' ).forEach( function( source ) {
			var fileType = source.dataset.mime.split( '/' ).pop();
			widget.querySelector( '[data-property="' + fileType + '"]' ).value = source.value;
		} );
	}


	/* Enable choosing of panel on narrow screen */
	function checkWindowWidth() {
		var itemAdd, embed, details;
		if ( dialog.querySelector( '#widget-modal-media-content' ) && window.innerWidth < 901 ) {
			details = dialog.querySelector( 'details' );
			details.removeAttribute( 'hidden' );
			details.addEventListener( 'toggle', function( e ) {
				if ( e.target.open ) {
					itemAdd = dialog.querySelector( '#menu-item-add' );
					itemAdd.removeAttribute( 'hidden' );
					itemAdd.setAttribute( 'aria-selected', true );
					embed = dialog.querySelector( '#menu-item-embed' );
					embed.removeAttribute( 'hidden' );
					details.append( itemAdd );
					details.append( embed );
				}
			} );
		}
	}

	/**
	 * Close modal by clicking button.
	 *
	 * @abstract
	 * @return {void}
	 */
	function closeModal() {
		var menuItemAdd = dialog.querySelector( '#menu-item-add' );

		dialog.close();

		if ( dialog.querySelector( '.widget-modal-header-buttons' ) ) {
			dialog.querySelector( '.widget-modal-header-buttons' ).remove();
		}
		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			dialog.querySelector( '#widget-modal-media-content' ).remove();
		}
		if ( dialog.querySelector( '#audio-modal-content' ) ) {
			dialog.querySelector( '#audio-modal-content' ).remove();
		}
		if ( dialog.querySelector( '.widget-modal-footer' ) ) {
			dialog.querySelector( '.widget-modal-footer' ).remove();
		}
		if ( dialog.querySelector( '#menu-item-audio-details' ) ) {
			dialog.querySelector( '#menu-item-audio-details' ).remove();
		}

		dialog.removeAttribute( 'style' );
		menuItemAdd.textContent = AUDIO_WIDGET.add_media;
		menuItemAdd.classList.remove( 'cancel' );
		menuItemAdd.setAttribute( 'aria-selected', true );
		dialog.querySelector( '.widget-modal-headings' ).removeAttribute( 'style' );
		dialog.querySelector( '.widget-modal-left-sidebar' ).classList.remove( 'hidden' );
	}

	/**
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var base, page, widgetId, widgetEl, itemAdd, itemEmbed, itemBrowse,
			itemUpload, gridPanel, uploadPanel, urlPanel,
			modalButtons, rightSidebar, modalPages,
			widget = e.target.closest( '.widget' );

		// Add, replace, or edit an image in a media image widget
		if ( widget ) {
			base = widget.querySelector( '.id_base' );
			if ( base && base.value === 'media_audio' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editAudio( widget );
				}
			}

		// Close the modal
		} else if ( e.target.id === 'widget-modal-close' ) {
			closeModal();

		// Set variables for when editing an audio widget
		} else if ( dialog.querySelector( '#audio-modal-content' ) ) {
			widgetId = dialog.querySelector( '#audio-modal-content' ).dataset.widgetId;
			widgetEl = document.getElementById( widgetId );

			if ( e.target.id === 'audio-button-update' ) {
				updateAudioToWidget( widgetEl );

			} else if ( e.target.className === 'media-menu-item cancel' ) {
				closeModal();

			} else if ( e.target.className === 'button-link remove-setting'	) {
				e.target.previousElementSibling.remove();
				e.target.parentNode.querySelector( 'audio' ).remove();
				e.target.remove();
				dialog.querySelector( '#audio-button-update' ).disabled = false;
			}

		// Set variables for the rest of the options below
		} else if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl = document.getElementById( widgetId );
			base     = widgetEl.querySelector( '.id_base' );

			// Only run on a media image widget
			if ( base && base.value === 'media_audio' ) {
				itemAdd      = dialog.querySelector( '#menu-item-add' );
				itemEmbed    = dialog.querySelector( '#menu-item-embed' );
				itemBrowse   = dialog.querySelector( '#menu-item-browse' );
				itemUpload   = dialog.querySelector( '#menu-item-upload' );
				gridPanel    = dialog.querySelector( '#media-library-grid' );
				rightSidebar = dialog.querySelector( '.widget-modal-right-sidebar' );
				modalPages   = dialog.querySelector( '.widget-modal-pages' );
				uploadPanel  = dialog.querySelector( '#uploader-inline' );
				urlPanel     = dialog.querySelector( '#insert-from-url-panel' );
				modalButtons = dialog.querySelector( '.widget-modal-header-buttons' );

				// Search or go to a specific page in the media library grid
				if ( e.target.parentNode.className === 'pagination-links' && e.target.tagName === 'BUTTON' ) {
					page = e.target.dataset.page;
					updateGrid( widgetEl, page );
				} else if ( e.target.parentNode.parentNode && e.target.parentNode.parentNode.className === 'pagination-links' && e.target.parentNode.tagName === 'BUTTON' ) {
					page = e.target.parentNode.dataset.page;
					updateGrid( widgetEl, page );

				// Add a new image to a widget via the image's URL
				} else if ( e.target.id === 'menu-item-embed' ) {
					dialog.querySelector( 'h2' ).textContent = AUDIO_WIDGET.insert_from_url;
					itemAdd.classList.remove( 'active' );
					itemAdd.setAttribute( 'aria-selected', false );
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					modalButtons.style.display = 'none';
					uploadPanel.setAttribute( 'hidden', true );
					uploadPanel.setAttribute( 'hidden', true );
					gridPanel.setAttribute( 'hidden', true );
					gridPanel.setAttribute( 'inert', true );
					rightSidebar.setAttribute( 'hidden', true );
					urlPanel.removeAttribute( 'hidden' );
					urlPanel.removeAttribute( 'inert' );
					insertEmbed();

				// Search for a new audio file to add to a widget
				} else if ( e.target.id === 'menu-item-add' ) {
					dialog.querySelector( 'h2' ).textContent = AUDIO_WIDGET.media_library;
					itemEmbed.classList.remove( 'active' );
					itemEmbed.setAttribute( 'aria-selected', false );
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					urlPanel.setAttribute( 'hidden', true );
					urlPanel.setAttribute( 'inert', true );
					if ( dialog.querySelector( '.widget-modal-media-embed .thumbnail img') ) {
						dialog.querySelector( '.widget-modal-media-embed .thumbnail img').remove();
					}
					modalButtons.style.display = 'flex';
					gridPanel.removeAttribute( 'hidden' );
					gridPanel.removeAttribute( 'inert' );
					rightSidebar.removeAttribute( 'hidden' );
					dialog.querySelector( '#menu-item-browse' ).click();

				// Browse the library of uploaded audio files
				} else if ( e.target.id === 'menu-item-browse' ) {
					itemUpload.classList.remove( 'active' );
					itemUpload.setAttribute( 'aria-selected', false );
					itemAdd.classList.add( 'active' );
					itemAdd.setAttribute( 'aria-selected', true );
					itemEmbed.classList.remove( 'active' );
					itemEmbed.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					uploadPanel.setAttribute( 'hidden', true );
					uploadPanel.setAttribute( 'inert', true );
					gridPanel.removeAttribute( 'hidden' );
					gridPanel.removeAttribute( 'inert' );
					rightSidebar.removeAttribute( 'hidden' );
					rightSidebar.removeAttribute( 'inert' );
					modalPages.removeAttribute( 'hidden' );
					modalPages.removeAttribute( 'inert' );

				// Upload a new attachment
				} else if ( e.target.id === 'menu-item-upload' ) {
					itemBrowse.classList.remove( 'active' );
					itemBrowse.setAttribute( 'aria-selected', false );
					itemAdd.classList.remove( 'active' );
					itemAdd.setAttribute( 'aria-selected', false );
					itemEmbed.classList.remove( 'active' );
					itemEmbed.setAttribute( 'aria-selected', false );
					e.target.classList.add ( 'active' );
					e.target.setAttribute( 'aria-selected', true );
					uploadPanel.removeAttribute( 'hidden' );
					uploadPanel.removeAttribute( 'inert' );
					gridPanel.setAttribute( 'hidden', true );
					gridPanel.setAttribute( 'inert', true );
					rightSidebar.setAttribute( 'hidden', true );
					rightSidebar.setAttribute( 'inert', true );
					modalPages.setAttribute( 'hidden', true );
					modalPages.setAttribute( 'inert', true );
					goFilepond( widgetId );

				// Add item to widget
				} else if ( e.target.id === 'media-button-insert' ) {
					addItemToWidget( widgetEl );

				// Delete an attachment
				} else if ( e.target.className.includes( 'delete-attachment' ) ) {
					if ( widgetEl.querySelector( '[data-property="attachment_id"]' ) ) {
						if ( dialog.querySelector( '.widget-modal-grid .selected' ).dataset.id != widgetEl.querySelector( '[data-property="attachment_id"]' ).value ) {
							if ( window.confirm( AUDIO_WIDGET.confirm_delete ) ) {
								deleteItem( dialog.querySelector( '.widget-modal-grid .selected' ).dataset.id );
							}
						}
					}

				// Copy URL
				} else if ( e.target.className.includes( 'copy-attachment-url' ) ) {
					copyToClipboard( e.target );
				}
			}
		}
	} );

	/**
	 * Enable searching for items within grid.
	 *
	 * @abstract
	 * @return {void}
	 */
	dialog.addEventListener( 'change', function( e ) {
		var widgetId, widgetEl, base;
		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl = document.getElementById( widgetId );
			base     = widgetEl.querySelector( '.id_base' );

			// Only run on a media image widget
			if ( base && base.value === 'media_audio' ) {
				if ( e.target.id === 'filter-by-date' ) {
					updateGrid( widgetEl, 1 );
				} else if ( e.target.className === 'postform' ) {
					updateGrid( widgetEl, 1 );
				} else if ( e.target.id === 'current-page-selector' ) {
					updateGrid( widgetEl, e.target.value );
				} else if ( e.target.id === 'widget-modal-search-input' ) {
					updateGrid( widgetEl, 1 );
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}
			}
		}
	} );

	// Set focus after closing modal using Escape key
	dialog.addEventListener( 'keydown', function( e ) {
		var widgetId, widget, details, base,
			modal = dialog.querySelector( '#media-widget-modal' ),
			method = 'select';

		if ( modal ) {
			if ( dialog.querySelector( '#audio-modal-content' ) ) {
				method = 'edit';
				widgetId = dialog.querySelector( '#audio-modal-content' ).dataset.widgetId;
			} else if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
				widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			}

			if ( widgetId ) {
				widget  = document.getElementById( widgetId );
				details = widget.querySelector( 'details' );
				base    = widget.querySelector( '.id_base' );

				if ( base && base.value === 'media_audio' ) {
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

	/**
	 * Upload files using FilePond
	 */
	function goFilepond( widgetId ) {

		// Register FilePond plugins
		FilePond.registerPlugin(
			FilePondPluginFileValidateSize,
			FilePondPluginFileValidateType,
			FilePondPluginFileRename,
			FilePondPluginImagePreview
		);

		// Create a FilePond instance
		pond = FilePond.create( dialog.querySelector( '#filepond' ), {
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
						if ( result.success ) {
							updateGrid( document.getElementById( widgetId ), 1 );
							dialog.querySelector( '#menu-item-browse' ).click();
							setTimeout( function() {
								dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
							}, 500 );
						} else {
							error( AUDIO_WIDGET.upload_failed );
						}
					} )
					.catch( function( err ) {
						error( AUDIO_WIDGET.upload_failed );
						console.error( AUDIO_WIDGET.error, err );
					} );

					// Return an abort function
					return {
						abort: function() {
							// This function is called when the user aborts the upload
							abort();
						}
					};
				},
				maxFileSize: dialog.querySelector( '#ajax-url' ).dataset.maxFileSize
			},
			labelTapToUndo: AUDIO_WIDGET.tap_close,
			fileRenameFunction: ( file ) =>
				new Promise( function( resolve ) {
					resolve( window.prompt( AUDIO_WIDGET.new_filename, file.name ) );
				} ),
			acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
			labelFileTypeNotAllowed: AUDIO_WIDGET.invalid_type,
			fileValidateTypeLabelExpectedTypes: AUDIO_WIDGET.check_types
		} );

		pond.on( 'processfile', function( error, file ) {
			if ( ! error ) {
				setTimeout( function() {
					pond.removeFile( file.id );
				}, 100 );
				resetDataOrdering();
			}
		} );
	}

	// Reset ordering of remaining media items after deletion
	function resetDataOrdering() {
		var items = document.querySelectorAll( '.media-item' ),
			num = document.querySelector( '.displaying-num' ).textContent.split( ' ' ),
			count = document.querySelector( '.load-more-count' ).textContent.split( ' ' ),
			count5;

		items.forEach( function( item, index ) {
			item.setAttribute( 'data-order', parseInt( index + 1 ) );
		} );

		// Reset totals
		if ( 5 in count ) { // allow for different languages
			count5 = ' ' + count[5];
		} else {
			count5 = '';
		}
		document.querySelector( '.load-more-count' ).textContent = count[0] + ' ' + items.length + ' ' + count[2] + ' ' + items.length + ' ' + count[4] + count5;

		document.querySelector( '.displaying-num' ).textContent = items.length + ' ' + num[1];
	}

} );
