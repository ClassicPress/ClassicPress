/* eslint consistent-this: [ "error", "control" ] */
/* global ajaxurl, IMAGE_WIDGET, console, FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginFileRename, FilePondPluginImagePreview */

/**
 * @since CP 2.5.0
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
				console.error( IMAGE_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
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
				console.error( IMAGE_WIDGET.failed_update, result.data.error );
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
		} );
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
				console.log( IMAGE_WIDGET.delete_failed );
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
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
		dialog.querySelector( '.attachment-dimensions' ).textContent = width + ' ' + IMAGE_WIDGET.by + ' ' + height + ' ' + IMAGE_WIDGET.pixels;
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
			'<span class="screen-reader-text">' + IMAGE_WIDGET.deselect + '></span>' +
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
				'query[posts_per_page]': IMAGE_WIDGET.per_page,
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
				header.after( dialogContent );
				dialog.querySelector( '#menu-item-embed' ).removeAttribute( 'hidden' );
				dialog.querySelector( '#menu-item-add' ).textContent = IMAGE_WIDGET.add_image;
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
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + IMAGE_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
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
				'query[posts_per_page]': IMAGE_WIDGET.per_page,
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
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + IMAGE_WIDGET.of + ' ' + result.headers.total_posts + ' media items';
				}
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
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
			img      = new Image(),
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId,
			widget   = document.getElementById( widgetId ),
			fields   = dialog.querySelectorAll( '#widget-modal-url-settings input, #widget-modal-url-settings textarea' );

		img.src = url;
		img.onerror = function() {
			if ( message ) {
				message.remove();
			}
			error.id = 'message';
			error.className = 'notice-error is-dismissible';
			error.innerHTML = '<p style="color:#fff;background:red;font-size:1em;padding:0.5em 1em;margin-top:-1px;">' + IMAGE_WIDGET.wrong_url + '</p>';
			dialog.querySelector( '#widget-modal-embed-url-field' ).before( error );

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
		if ( dialog.querySelector( '.widget-modal-media-embed .thumbnail img') ) {
			dialog.querySelector( '.widget-modal-media-embed .thumbnail img').remove(); // avoid duplicates
		}
		dialog.querySelector( '.widget-modal-media-embed .thumbnail').append( img );
		dialog.querySelector( '.widget-modal-media-embed img').src = url;
		widget.querySelector( '[data-property="url"]' ).value = url;
		if ( IMAGE_WIDGET.image_file_types.includes( fileType.toLowerCase() ) ) {
			if ( message ) {
				message.remove();
				fields.forEach( function( input ) {
					input.removeAttribute( 'disabled' );
				} );
			}
		} else {
			if ( message ) {
				message.remove();
			}
			error.id = 'message';
			error.className = 'notice-error';
			error.innerHTML = '<p style="color:#fff;background:red;font-size:16px;padding:0.5em 1em;margin-top:-1px;">' + IMAGE_WIDGET.unsupported_file_type + '</p>';
			dialog.querySelector( '#widget-modal-embed-url-field' ).before( error );

			// Disable other fields until the issue is corrected
			addButton.setAttribute( 'disabled', true );
			fields.forEach( function( input ) {
				input.setAttribute( 'disabled', true );
			} );
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
			linkType = widget.querySelector( '[data-property="link_type"]' ).value,
			originalUrl = widget.querySelector( '[data-property="url"]' ).value,
			embedAlt = dialog.querySelector( '#embed-image-settings-alt-text' ),
			embedCaption = dialog.querySelector( '#embed-image-settings-caption' ),
			embedLinkTo = dialog.querySelector( '#link-to' ),
			embedLinkUrl = dialog.querySelector( '#embed-image-settings-link-to-custom' );

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

		// Update values and trigger Add to Widget button
		embedAlt.addEventListener( 'change', function() {
			widget.querySelector( '[data-property="alt"]' ).value = embedAlt.value;
			addButton.removeAttribute( 'disabled' );
		} );

		embedCaption.addEventListener( 'change', function() {
			widget.querySelector( '[data-property="caption"]' ).value = embedCaption.value;
			addButton.removeAttribute( 'disabled' );
		} );

		// Show or hide Custom URL depending on link type
		if ( linkType ) {
			embedLinkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				if ( option.value === linkType ) {
					option.setAttribute( 'selected', 'selected' );
				}
			} );
			if ( linkType === 'none' || widget.querySelector( '[data-property="link_url"]' ) == null ) {
				embedLinkUrl.parentNode.setAttribute( 'hidden', true );
				embedLinkUrl.value = '';
			} else {
				embedLinkUrl.value = widget.querySelector( '[data-property="link_url"]' ).value;
				embedLinkUrl.parentNode.removeAttribute( 'hidden' );
			}
		} else {
			embedLinkTo.value = 'none';
			embedLinkUrl.value = '';
		}

		// Show and hide URL field if Custom URL chosen or deselected
		embedLinkTo.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			embedLinkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'none' ) {
				embedLinkUrl.parentNode.setAttribute( 'hidden', true );
				embedLinkUrl.value = '';
			} else {
				embedLinkUrl.parentNode.removeAttribute( 'hidden' );
			}
			addButton.removeAttribute( 'disabled' );
		} );

		// Trigger Add to Widget button if Custom URL value changed
		embedLinkUrl.addEventListener( 'change', function() {
			addButton.removeAttribute( 'disabled' );
		} );
	}

	/**
	 * Add image to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function addItemToWidget( widget ) {
		var imageElement, source, sizesObject, selectedItem,
			sizeOptions = '',
			linkUrl = '',
			preview = widget.querySelector( '.media-widget-preview' ),
			buttons	= document.createElement( 'fieldset' ),
			fieldset = document.createElement( 'fieldset' ),
			number = widget.querySelector( '.widget_number' ).value;

		// Add from URL
		if ( ! dialog.querySelector( '#insert-from-url-panel' ).hasAttribute( 'hidden' ) ) {
			source = dialog.querySelector( '#widget-modal-embed-url-field' ).value;
			imageElement = document.createElement( 'img' );
			imageElement.src = source;
			imageElement.setAttribute( 'draggable', false );
			imageElement.alt = dialog.querySelector( '#embed-image-settings-alt-text' ).value;
			if ( imageElement.alt === '' ) {
				imageElement.setAttribute( 'aria-label', IMAGE_WIDGET.aria_label + source.split( '/' ).pop() );
			}

			imageElement.onload = function() {
				widget.querySelector( '[data-property="width"]' ).value = imageElement.width;
				widget.querySelector( '[data-property="height"]' ).value = imageElement.height;
			};

			linkUrl = dialog.querySelector( '#embed-image-settings-link-to-custom' ).value;
			widget.querySelector( '[data-property="url"]' ).value = source;
			widget.querySelector( '[data-property="attachment_id"]' ).value = 0;
			widget.querySelector( '[data-property="alt"]' ).value = dialog.querySelector( '#embed-image-settings-alt-text' ).value;
			widget.querySelector( '[data-property="caption"]' ).value = dialog.querySelector( '#embed-image-settings-caption' ).value;
			widget.querySelector( '[data-property="link_type"]' ).value = dialog.querySelector( '#link-to' ).value ? dialog.querySelector( '#link-to' ).value : 'none';

		// Add from Media Library
		} else if ( ! dialog.querySelector( '#media-library-grid' ).hasAttribute( 'hidden' ) ) {
			selectedItem = dialog.querySelector( '.widget-modal-grid .selected' );
			imageElement = selectedItem.querySelector( 'img' );
			sizesObject = JSON.parse( selectedItem.dataset.sizes );
			for ( var dimension in sizesObject ) {
				sizeOptions += '<option value="' + dimension + '">' + dimension[0].toUpperCase() + dimension.slice(1) + ' – ' + sizesObject[dimension].width + ' x ' + sizesObject[dimension].height + '</option>';
			}

			// Add values to div.widget-content fields
			linkUrl = dialog.querySelector( '.link-to-custom' ).value;
			widget.querySelector( '[data-property="attachment_id"]' ).value = selectedItem.dataset.id;
			widget.querySelector( '[data-property="alt"]' ).value = dialog.querySelector( '#attachment-details-alt-text' ).textContent;
			widget.querySelector( '[data-property="caption"]' ).value = dialog.querySelector( '#attachment-details-caption' ).textContent;
			widget.querySelector( '[data-property="url"]' ).value = selectedItem.dataset.url;
			widget.querySelector( '[data-property="size"]' ).value = dialog.querySelector( '.size' ).value;
			widget.querySelector( '[data-property="link_type"]' ).value = dialog.querySelector( '.link-to' ).value;
			widget.querySelector( '[data-property="size_options"]' ).value = sizeOptions;
		}

		// Add Edit and Replace buttons
		buttons.className = 'media-widget-buttons';
		buttons.innerHTML = '<button type="button" class="button edit-media" data-edit-nonce="' + selectedItem.dataset.editNonce + '">' + IMAGE_WIDGET.edit_image + '</button>' +
			'<button type="button" class="button change-media select-media">' + IMAGE_WIDGET.replace_image + '</button>';

		// Add Link field
		fieldset.className = 'media-widget-image-link';
		fieldset.innerHTML = '<label for="widget-media_image-' + number + '-link_url">Link to:</label>' +
			'<input id="widget-media_image-' + number + '-link_url" name="widget-media_image[' + number + '][link_url]" class="widefat" type="url" value="' + linkUrl + '" placeholder="https://" data-property="link_url">';

		// Insert image according to whether this is a new insertion or replacement
		imageElement.className = 'attachment-thumb';
		if ( widget.querySelector( '.attachment-media-view' ) !== null ) {
			preview.prepend( imageElement );
			preview.classList.add( 'populated' );
			widget.querySelector( '.attachment-media-view' ).remove();
			preview.after( buttons );
			buttons.after( fieldset );
		} else { // replacement
			widget.querySelector( '.attachment-thumb' ).replaceWith( imageElement );
		}

		// Activate Save/Publish button
		if ( document.body.className.includes( 'widgets-php' ) ) {
			widget.classList.add( 'widget-dirty' );
		}
		widget.dispatchEvent( new Event( 'change' ) );

		closeModal();
	}

	/**
	 * Open the media frame to modify the selected item.
	 *
	 * @abstract
	 * @return {void}
	 */
	function editMedia( widget ) {
		var	header, imageSize, linkTo, linkToCustom, editOriginal,
			widthField, heightField, customSizeField, updateButton,
			size            = widget.querySelector( '[data-property="size"]' ).value,
			width           = widget.querySelector( '[data-property="width"]' ).value,
			height          = widget.querySelector( '[data-property="height"]' ).value,
			caption         = widget.querySelector( '[data-property="caption"]' ).value,
			alt             = widget.querySelector( '[data-property="alt"]' ).value,
			linkType        = widget.querySelector( '[data-property="link_type"]' ).value,
			linkUrl         = widget.querySelector( '[data-property="link_url"]' ).value,
			imageClasses    = widget.querySelector( '[data-property="image_classes"]' ).value,
			linkClasses     = widget.querySelector( '[data-property="link_classes"]' ).value,
			linkRel         = widget.querySelector( '[data-property="link_rel"]' ).value,
			linkTargetBlank = widget.querySelector( '[data-property="link_target_blank"]' ).value,
			linkImageTitle  = widget.querySelector( '[data-property="link_image_title"]' ).value,
			attachmentId    = widget.querySelector( '[data-property="attachment_id"]' ).value,
			url             = widget.querySelector( '[data-property="url"]' ).value,
			sizeOptions     = widget.querySelector( '[data-property="size_options"]' ).value,
			template        = document.getElementById( 'tmpl-edit-image-modal' ),
			clone           = template.content.cloneNode( true );

		// Append cloned template and establish new variables
		header = dialog.querySelector( 'header' );
		header.after( clone.querySelector( '#image-modal-content' ) );
		dialog.querySelector( '.widget-modal-main' ).append( clone.querySelector( 'footer' ) );
		dialog.style.width = '85%';
		dialog.style.height = '85%';
		header.querySelector( '.widget-modal-headings' ).style.padding = '0 0.5em 0 0.75em';
		header.querySelector( '#widget-modal-title h2' ).textContent = IMAGE_WIDGET.image_details;
		dialog.querySelector( '#image-modal-content' ).dataset.widgetId = widget.id;
		dialog.querySelector( '.widget-modal-left-sidebar' ).classList.add( 'hidden' );
		checkWindowWidth();

		imageSize       = dialog.querySelector( '#image-details-size' );
		linkTo          = dialog.querySelector( '#image-details-link-to' );
		linkToCustom    = dialog.querySelector( '#image-details-link-to-custom' );
		editOriginal    = dialog.querySelector( '#edit-original' );
		widthField      = dialog.querySelector( '#image-details-size-width' );
		heightField     = dialog.querySelector( '#image-details-size-height' );
		updateButton    = dialog.querySelector( '#media-button-update' );
		customSizeField = imageSize.closest( 'fieldset' ).querySelector( '.custom-size' );

		// Set available sizes select dropdown
		imageSize.insertAdjacentHTML( 'afterbegin', sizeOptions );
		if ( size === 'custom' ) {
			widthField.value = width;
			heightField.value = height;
			customSizeField.classList.remove( 'hidden' );
		} else {
			customSizeField.classList.add( 'hidden' );
			widthField.value = '';
			heightField.value = '';
		}

		// Show the edit image button only if the image is stored in the media library
		if ( attachmentId == 0 ) {
			editOriginal.parentNode.setAttribute( 'hidden', true );
			editOriginal.parentNode.setAttribute( 'inert', true );
		} else {
			imageSize.querySelector( 'option[value="' + size + '"]' ).setAttribute( 'selected', true );
		}

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
			updateButton.removeAttribute( 'disabled' ); // trigger Update button
		} );

		// Show or hide Custom URL depending on link type
		linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
			option.removeAttribute( 'selected' );
			if ( option.value === linkType ) {
				option.setAttribute( 'selected', true );
			}
		} );
		if ( linkType !== 'none' ) {
			linkToCustom.parentNode.style.display = 'block';
			if ( linkType === 'file' ) {
				linkToCustom.value = url;
			} else if ( linkType === 'post' ) {
				linkToCustom.value = url;
			} else if ( linkType === 'custom' ) {
				linkToCustom.value = linkUrl;
			}
		} else {
			linkTo.value = 'none';
			linkToCustom.parentNode.style.display = 'none';
			linkToCustom.value = '';
		}

		// Widget-specific details
		dialog.querySelector( '#image-details-alt-text' ).value = alt;
		dialog.querySelector( '#image-details-caption' ).value = caption;
		dialog.querySelector( '#image-details-title-attribute' ).value = linkImageTitle;
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

		// Show and hide URL field as appropriate
		linkTo.addEventListener( 'change', function() {
			var selectedOption = this[this.selectedIndex];
			linkTo.querySelectorAll( 'option' ).forEach( function( option ) {
				option.removeAttribute( 'selected' );
			} );
			selectedOption.setAttribute( 'selected', true );
			if ( selectedOption.value === 'none' ) {
				linkToCustom.parentNode.style.display = 'none';
				linkToCustom.value = '';
			} else {
				if ( selectedOption.value === 'file' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'post' ) {
					linkToCustom.value = url;
				} else if ( selectedOption.value === 'custom' ) {
					linkToCustom.value = linkUrl;
				}
				linkToCustom.parentNode.style.display = 'block';
			}
			updateButton.removeAttribute( 'disabled' ); // trigger update button
		} );

		// Trigger update button when other changes made to inputs or textareas
		dialog.querySelectorAll( '#image-modal-content textarea, #image-modal-content input' ).forEach( function( input ) {
			input.addEventListener( 'change', function() {
				updateButton.removeAttribute( 'disabled' );
			} );
		} );
	}

	/**
	 * Enable update of image details.
	 *
	 * @abstract
	 * @return {void}
	 */
	function updateImageDetails( widgetId ) {
		var size = dialog.querySelector( '#image-details-size' ),
			selectedOption = size.options[size.selectedIndex].text,
			widget = document.getElementById( widgetId );

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

		if ( size.value === 'custom' ) {
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
	function imageEdit( widgetId ) {
		var formData,
			attachmentId = document.querySelector( '#' + widgetId + ' [data-property="attachment_id"]' ).value,
			nonce = document.querySelector( '#' + widgetId + ' .edit-media' ).dataset.editNonce;

		formData = new FormData();
		formData.append( 'action', 'image-editor' );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'postid', attachmentId );
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
			dialog.querySelector( '.modal-image-details' ).insertAdjacentHTML( 'beforeend', result.data.html );

			// Cancel
			dialog.querySelector( '.imgedit-cancel-btn' ).addEventListener( 'click', function() {
				dialog.querySelector( '.media-embed' ).style.display = '';
			} );

			// Submit changes
			dialog.querySelector( '.imgedit-submit-btn' ).addEventListener( 'click', function() {
				document.getElementById( widgetId ).dispatchEvent( new Event( 'change' ) );
				closeModal();
			} );
		} )
		.catch( function( error ) {
			console.error( 'Error:', error );
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
		dialog.removeAttribute( 'style' );
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
			if ( base && base.value === 'media_image' && e.target.tagName === 'BUTTON' ) {
				if ( e.target.className.includes( 'select-media' ) ) {
					selectMedia( widget );
				} else if ( e.target.className.includes( 'edit-media' ) ) {
					editMedia( widget );
				}
			}

		// Edit the image in a widget
		} else if ( e.target.id === 'edit-original' ) {
			imageEdit( e.target.dataset.widgetId );

		// Close the modal
		} else if ( e.target.id === 'widget-modal-close' ) {
			closeModal();

		// Update an edited image
		} if ( e.target.id === 'media-button-update' ) {
			updateImageDetails( dialog.querySelector( '#edit-original' ).dataset.widgetId );

		// Set variables for the rest of the options below
		} else if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId     = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			widgetEl     = document.getElementById( widgetId );
			base         = widgetEl.querySelector( '.id_base' );

			// Only run on a media image widget
			if ( base && base.value === 'media_image' ) {
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
					dialog.querySelector( 'h2' ).textContent = IMAGE_WIDGET.insert_from_url;
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

				// Search for a new image to add to a widget
				} else if ( e.target.id === 'menu-item-add' ) {
					dialog.querySelector( 'h2' ).textContent = IMAGE_WIDGET.media_library;
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

				// Browse the library of uploaded images
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
							if ( window.confirm( IMAGE_WIDGET.confirm_delete ) ) {
								deleteItem( dialog.querySelector( '.widget-modal-grid .selected' ).dataset.id );
							}
						}
					}
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
		var widgetId;
		if ( dialog.querySelector( '#widget-modal-media-content' ) ) {
			widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			if ( e.target.id === 'filter-by-date' ) {
				updateGrid( document.getElementById( widgetId ), 1 );
			} else if ( e.target.className === 'postform' ) {
				updateGrid( document.getElementById( widgetId ), 1 );
			} else if ( e.target.id === 'current-page-selector' ) {
				updateGrid( document.getElementById( widgetId ), e.target.value );
			} else if ( e.target.id === 'widget-modal-search-input' ) {
				updateGrid( document.getElementById( widgetId ), 1 );
				dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
			}
		} else if ( dialog.querySelector( '#image-modal-content' ) ) {
			widgetId = dialog.querySelector( '#image-modal-content' ).dataset.widgetId;
		}
	} );

	// Set focus after closing modal using Escape key
	dialog.addEventListener( 'keydown', function( e ) {
		var widgetId, widget, details, base,
			modal = dialog.querySelector( '#media-widget-modal' ),
			method = 'select';

		if ( modal ) {
			if ( dialog.querySelector( '#image-modal-content' ) ) {
				method = 'edit';
				widgetId = dialog.querySelector( '#image-modal-content' ).dataset.widgetId;
			} else {
				widgetId = dialog.querySelector( '#widget-modal-media-content' ).dataset.widgetId;
			}

			if ( widgetId ) {
				widget  = document.getElementById( widgetId );
				details = widget.querySelector( 'details' );
				base    = widget.querySelector( '.id_base' );

				if ( base && base.value === 'media_image' ) {
					if ( dialog.open && e.key === 'Escape' ) {
						closeModal();
						setTimeout( function() {
							details.open = true;
						}, 100 );
						details.addEventListener( 'toggle', function( e ) {
							if ( e.target.open ) {
								if ( method === 'edit' ) {
									widget.querySelector( '.edit-media' ).focus();
								} else {
									widget.querySelector( '.select-media' ).focus();
								}
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
							error( IMAGE_WIDGET.upload_failed );
						}
					} )
					.catch( function( err ) {
						error( IMAGE_WIDGET.upload_failed );
						console.error( IMAGE_WIDGET.error, err );
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
			labelTapToUndo: IMAGE_WIDGET.tap_close,
			fileRenameFunction: ( file ) =>
				new Promise( function( resolve ) {
					resolve( window.prompt( IMAGE_WIDGET.new_filename, file.name ) );
				} ),
			acceptedFileTypes: document.querySelector( '.uploader-inline' ).dataset.allowedMimes.split( ',' ),
			labelFileTypeNotAllowed: IMAGE_WIDGET.invalid_type,
			fileValidateTypeLabelExpectedTypes: IMAGE_WIDGET.check_types
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
