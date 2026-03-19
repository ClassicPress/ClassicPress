/**
 * @output wp-admin/js/customize-controls.js
 *
 * @since CP-2.8.0
 */

/* eslint consistent-this: [ "error", "control" ] */
/* global wp, _wpCustomizeControlsL10n, _wpCustomizeHeader,
 * _wpCustomizeBackground, updatedControls, _updatedControlsWatcher,
 * MediaElementPlayer, console, confirm,  ajaxurl, IMAGE_WIDGET, console,
 * FilePondPluginFileValidateSize, FilePondPluginFileValidateType,
 * FilePondPluginFileRename, FilePondPluginImagePreview
 */
document.addEventListener( 'DOMContentLoaded', function() {
	var addButton, pond, leftSidebar, customizeButton, currentMenuId,
		observer, intersectionObserver, orgThemes, localThemes, newUrl,
		i = 1,
		{ FilePond } = window, // import FilePond
		dialog = document.getElementById( 'widget-modal' ),
		installedThemesHTML = document.querySelector( '.themes')?.innerHTML,
		reducedMotionMediaQuery = window.matchMedia( '(prefers-reduced-motion: reduce)' ),
		isReducedMotion = reducedMotionMediaQuery.matches,
		isCollapsed = document.querySelector( '.wp-full-overlay' )?.classList.contains( 'collapsed' ),
		form = document.querySelector( 'form' ),
		inputs = form.querySelectorAll( 'input, select, textarea' ),
		saveButton = form.querySelector( '#save' ),
		devicesWrapper = document.querySelector( '.devices' ),
		buttons = devicesWrapper?.querySelectorAll( 'button[data-device]' ),
		previewFrame = document.getElementById( 'customize-preview' ),
		queryParams = new URLSearchParams( window.location.search ),
		addMenuButtons = document.querySelectorAll( '.add-new-menu-item' ),
		availableMenuItems = document.getElementById( 'available-menu-items' ),
		addWidgetButtons = document.querySelectorAll( '.add-new-widget' ),
		newMenuItemIDs = [],
		menuToEdit = document.getElementById( 'menu-to-edit' );

	// Go direct to appropriate Customizer panel if its hash is specified in the URL
	const hash = window.location.hash.replace( '#', '' );
	if ( hash ) {
		[...document.getElementById( 'customize-theme-controls' ).children].forEach( function( child ) {
			child.style.display = 'none';
		} );

		document.getElementById( hash ).style.display = 'block';
		setTimeout( function() {
			document.getElementById( hash ).querySelector( 'button' ).focus();
		}, 0 );
	}

	// Delete redundant query args from browser URL
	history.replaceState( null, null, '?' + queryParams.toString() );
	if ( queryParams.get( 'theme' ) ) {
		if ( queryParams.get( 'theme' ) === _wpCustomizeControlsL10n.activeTheme ) { // active theme
			history.replaceState( null, '', window.location.pathname );
		} else {
			queryParams.delete( 'return' );
			newUrl = window.location.pathname + ( queryParams.toString() ? '?' + queryParams.toString() : '' ) + ( hash ? '#' + hash : '' );
			history.replaceState( null, '', newUrl );
			saveButton.disabled = false;
			saveButton.value = _wpCustomizeControlsL10n.activate;
		}
		setTimeout( function() {
			document.getElementById( 'customize-pane-parent' ).querySelector( 'button' ).focus();
		}, 0 );
	} else {
		queryParams.delete( 'return' );
		newUrl = window.location.pathname + ( queryParams.toString() ? '?' + queryParams.toString() : '' ) + ( hash ? '#' + hash : '' );
		history.replaceState( null, '', newUrl );
	}

	// Remove inert attribute to enable interactions with form
	form.removeAttribute( 'inert' );

	// Limit motion where appropriate
	reducedMotionMediaQuery.addEventListener( 'change', function handleReducedMotionChange( event ) {
		isReducedMotion = event.matches;
	} );

	/**
	 * Show AYS dialog when there are unsaved widget changes.
	 *
	 * Note that browsers do not permit the display of a custom message.
	 */
	window.addEventListener( 'beforeunload', function( e ) {
		if ( saveButton.disabled === false ) {
			e.preventDefault();
		}
	} );

	/**
	 * Helper function copied from jQuery
	 */
	function isVisible( elem ) {
		return !!( elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length );
	}

	/**
	 * Trigger activation of Publish button
	 */
	function activatePublishButton() {
		saveButton.disabled = false;
		saveButton.value = _wpCustomizeControlsL10n.publish;
	}

	/**
	 * Prepare changed object for publication.
	 */
	function inputChanged( input, settingId ) {
		_updatedControlsWatcher[ settingId ] = input.value.trim();
		activatePublishButton();
	}

	inputs.forEach( function( input ) {
		let settingId,
			li = input.closest( 'li' );

		if ( ! li?.hasAttribute( 'data-setting-id' ) || li.className.includes( 'widget-rendered' ) ) {
			return;
		}

		// Do not listen to menu-related changes
		settingId = li.dataset.settingId;
		if ( settingId.startsWith( 'nav_menu_locations[' ) || settingId.startsWith( 'nav_menu[' ) || settingId.startsWith( 'nav_menu_item[' ) ) {
			return;
		}

		input.addEventListener( 'input', function() {
			inputChanged( input, settingId );
		} );

		input.addEventListener( 'change', function() {
			inputChanged( input, settingId );
		} );
	} );

	/**
	 * Prevent tabbing out of dialog form.
	 *
	 * @since CP-2.8.0
	 *
	 * @param event - Event.
	 * @return {void}
	 */
	function constrainTab( event ) {
		var first = form.querySelector( '#customize-save-button-wrapper' ).disabled === false ? form.querySelector( '#customize-save-button-wrapper' ) : form.querySelector( '.customize-controls-close' ),
			last = form.querySelector( '.preview-mobile' );

		event.stopPropagation();
		if ( event.target === last && ! event.shiftKey ) {
			event.preventDefault();
			first.focus();
		} else if ( event.target === first && event.shiftKey ) {
			event.preventDefault();
			last.focus();
		}
	}

	// Keyboard navigation management
	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) {
			if ( isVisible( document.querySelector( '.iris-picker' ) ) ) {
				document.querySelectorAll( '.iris-picker' ).forEach( function( iris ) {
					iris.style.display = 'none';
				} );
			} else if ( isVisible( document.getElementById( 'move-widget-area' ) ) ) {
				document.getElementById( 'move-widget-area' ).remove();
			}
		} else if ( e.key === 'Tab' ) {
			if ( document.querySelector( '.devices-wrapper' ) ) {
				constrainTab( e );
			}
		}
	} );

	/**
	 * Expand and collapse the sidebar.
	 */
	function sidebarCollapseExpand( button ) {
		var overlay = document.querySelector( '.wp-full-overlay' ),
			labelEl = button.querySelector( '.collapse-sidebar-label' );

		// Overlay classes.
		overlay.classList.toggle( 'collapsed' );
		overlay.classList.toggle( 'expanded' );

		// Sidebar / preview.
		document.body.classList.remove( 'adding-menu-items' );
		document.body.classList.remove( 'adding-widget' );
		document.getElementById( 'widgets-left' ).style.display = 'none';
		availableMenuItems.style.display = 'none';
		document.getElementById( 'customizer-sidebar-container' ).classList.toggle( 'collapsed' );
		document.getElementById( 'customize-preview' ).classList.toggle( 'expanded-preview' );
		addMenuButtons.forEach( function( add ) {
			add.setAttribute( 'aria-expanded', false );
		} );
		addWidgetButtons.forEach( function( add ) {
			add.setAttribute( 'aria-expanded', false );
		} );

		// Button ARIA + label text.
		if ( button.getAttribute( 'aria-expanded' ) === 'true' ) {
			button.setAttribute( 'aria-expanded', false );
			labelEl.textContent = 'Show Controls';
		} else {
			button.setAttribute( 'aria-expanded', true );
			labelEl.textContent = 'Hide Controls';
		}
	}

	/**
	 * Enable different device views.
	 */
    buttons.forEach( function( button ) {
        button.addEventListener( 'click', function() {
            var device = button.getAttribute( 'data-device' );

            // Update button active state + aria-pressed
            buttons.forEach(function ( btn ) {
                var isActive = ( btn === button );
                btn.classList.toggle( 'active', isActive );
                btn.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
            } );

            if ( ! previewFrame ) {
                return;
            }

            // Use a data attribute and drive CSS from it
            previewFrame.setAttribute( 'data-device', device );
        } );
    } );

	/**
	 * Code for the Iris color picker.
	 *
	 * Requires jQuery.
	 */
	jQuery( '.color-picker-hue, .color-picker-hex' ).wpColorPicker( { // Iris requires jQuery
        change: function( event, ui ) {
            // Update the input's value in the DOM.
            event.target.setAttribute( 'value', ui.color.toString() );
            _updatedControlsWatcher[event.target.closest( 'li' ).dataset.settingId] = ui.color.toString();

            // Enable Publish.
            activatePublishButton();
        },
        clear: function( event ) {
			_updatedControlsWatcher[event.target.closest( 'li' ).dataset.settingId] = '';
            activatePublishButton();
        }
    } );

	// Focus/click: ensure picker shows.
	document.querySelectorAll( '.color-picker-hue, .color-picker-hex' ).forEach( function( input ) {
		var container = input.closest( '.wp-picker-container' );
		if ( ! container ) {
			return;
		}

		function showPicker() {
			var holder = container.querySelector( '.wp-picker-holder' );
			if ( holder ) {
				holder.style.display = '';
			}
		}

		input.addEventListener( 'focus', showPicker );
		input.addEventListener( 'click', showPicker );
	} );

	/**
	 * Themes
	 */
	document.querySelector( '#installed_themes-themes-filter' )?.addEventListener( 'keyup', _.debounce( function( e ) {
		var localThemes = document.querySelectorAll( '.local .themes li' ),
			count = localThemes.length;

		e.preventDefault();

		if ( e.key === 'Enter' || e.target.value.length > 2 ) { // requires at least 3 characters
			if ( document.querySelector( '.theme-browser' ).classList.contains( 'wp-org' ) ) {
				updateThemes( 'search', e.target.value );
			} else {
				localThemes.forEach( function( theme ) {
					if ( ! theme.id.includes( e.target.value ) ) {
						theme.style.display = 'none';
						count--;
					}
				} );
				document.querySelector( '.filter-themes-count .theme-count' ).textContent = count;
			}
		} else if ( document.querySelector( '.theme-browser' ).classList.contains( 'local' ) ) {
			localThemes.forEach( function( theme ) {
				theme.style.display = '';
			} );
			count = localThemes.length - 1;
			document.querySelector( '.filter-themes-count .theme-count' ).textContent = count;
		}
	}, 500 ) );

	// Update the list of themes from wordpress.org using Intersection Observer
	intersectionObserver = new IntersectionObserver( function( entries ) {
		const isIntersecting = entries[0]?.isIntersecting ?? false;
		if ( isIntersecting ) {
			i++;
			updateThemes( 'browse', 'new' );
		}
	} );

	// Search themes by tags
	document.addEventListener( 'change', function( e ) {
		if ( e.target.tagName === 'INPUT' && e.target.closest( 'fieldset' ) && e.target.closest( 'fieldset' ).classList.contains( 'filter-group' ) ) {
			var tagsToSend = [];
			document.querySelectorAll( '.filter-drawer input' ).forEach( function( tag ) {
				if ( tag.checked ) {
					tagsToSend.push( tag.value );
				}
			} );
			updateThemes( 'tag', tagsToSend );
		}
	} );

	// Update themes listed from wp.org
	function updateThemes( updateType, updateValue ) {
		var themesGrid = document.querySelector( '.themes' ),

			// Create URLSearchParams object
			params = new URLSearchParams( {
				'action': 'query-themes',
				'request[per_page]': 100,
				'request[page]': i
			} );

		if ( updateType === 'browse' ) {
			params.append( 'request[browse]', updateValue ); // popular or new
		} else if ( updateType === 'search' ) {
			params.append( 'request[search]', updateValue );
		} else if ( updateType === 'tag' ) {
			params.append( 'request[tag]', updateValue ); // array
		}

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
			if ( i === 1 ) {
				themesGrid.innerHTML = ''; // clear the current grid
			}

			// Populate grid with new items
			themesGrid.insertAdjacentHTML( 'beforeend', result.data.html );
			orgThemes = document.querySelectorAll( '.wp-org .themes li' );
			orgThemes.forEach( function( theme ) {
				theme.style.marginRight = '2%';
				theme.stylemarginBottom = '2%';
			} );

			// Update count
			document.querySelector( '.filter-themes-count .theme-count' ).textContent = orgThemes.length;
			if ( orgThemes.length ) {
				intersectionObserver.observe( orgThemes[orgThemes.length - 1] );
			}
		} )
		.catch( function( error ) {
			console.error( error );
		} );
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
	function selectItemToAdd( item, clicked ) {
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
			widgetInputs = dialog.querySelectorAll( '.widget-modal-right-sidebar input, .widget-modal-right-sidebar textarea, .widget-modal-media-embed input, .widget-modal-media-embed textarea' ),
			widgetSelects = dialog.querySelectorAll( '.widget-modal-right-sidebar select, .widget-modal-media-embed select' );

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
		}

		// Set status of items according to user's capabilities
		if ( updateNonce ) {
			widgetInputs.forEach( function( input ) {
				input.removeAttribute( 'readonly' );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.removeAttribute( 'hidden' );
		} else {
			widgetInputs.forEach( function( input ) {
				input.setAttribute( 'readonly', true );
			} );
			dialog.querySelector( '#edit-more' ).parentNode.setAttribute( 'hidden', true );
		}

		if ( deleteNonce ) {
			widgetSelects.forEach( function( select ) {
				select.removeAttribute( 'disabled' );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.removeAttribute( 'hidden' );
		} else {
			widgetSelects.forEach( function( select ) {
				select.setAttribute( 'disabled', true );
			} );
			dialog.querySelector( '.delete-attachment' ).parentNode.setAttribute( 'hidden', true );
		}

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
	function populateGridItem( attachment ) {
		var selected = '',
			idsArray = [],
			gridItem = document.createElement( 'li' ),
			image = '<img src="' + attachment.url + '" alt="' + attachment.alt + '">';

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
	function selectMedia() {
		var template = document.getElementById( 'tmpl-media-grid-modal' ),
			clone = template.content.cloneNode( true ),
			dialogButtons = clone.querySelector( '.widget-modal-header-buttons' ),
			dialogContent = clone.querySelector( '#widget-modal-media-content' ),
			header = dialog.querySelector( 'header' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': IMAGE_WIDGET.per_page,
				'query[post_mime_type]': customizeButton.parentNode.dataset.requiredType,
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

				// Remove left sidebar
				leftSidebar = dialog.querySelector( '.widget-modal-left-sidebar' );
				dialog.querySelector( '.widget-modal-left-sidebar' ).remove();

				// Append cloned template and show relevant elements
				header.append( dialogButtons );
				header.after( dialogContent );
				checkWindowWidth();

				addButton = dialog.querySelector( '#media-button-insert' );
				addButton.textContent = 'Select';
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
						var gridItem = populateGridItem( attachment );
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
							selectItemToAdd( item, false );
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + IMAGE_WIDGET.of + ' ' + result.headers.total_posts + ' ' + IMAGE_WIDGET.media_items;
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
	function updateGrid( paged ) {
		var dateFilter = dialog.querySelector( '#filter-by-date' ),
			mediaCatSelect = dateFilter.nextElementSibling,
			search = dialog.querySelector( '#widget-modal-search-input' ),
			params = new URLSearchParams( {
				'action': 'query-attachments',
				'query[posts_per_page]': IMAGE_WIDGET.per_page,
				'query[monthnum]': dateFilter.value ? parseInt( dateFilter.value.substr( 4, 2 ), 10 ) : 0,
				'query[year]': dateFilter.value ? parseInt( dateFilter.value.substr( 0, 4 ), 10 ) : 0,
				'query[post_mime_type]': customizeButton.parentNode.dataset.requiredType,
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
				addButton = dialog.querySelector( '#media-button-insert' );
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
						var gridItem = populateGridItem( attachment );
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
							selectItemToAdd( item, false );
							item.focus();
						}
						item.addEventListener( 'click', function() {
							selectItemToAdd( item, true );
						} );
					} );

					// Update the count at the bottom of the page
					dialog.querySelector( '.no-media' ).setAttribute( 'hidden', true );
					dialog.querySelector( '.load-more-count' ).removeAttribute( 'hidden' );
					dialog.querySelector( '.load-more-count' ).textContent = result.data.length + ' ' + IMAGE_WIDGET.of + ' ' + result.headers.total_posts + ' ' + IMAGE_WIDGET.media_items;
				}
			}
		} )
		.catch( function( error ) {
			console.error( IMAGE_WIDGET.error, error );
		} );

		dialog.showModal();
	}

	/**
	 * Add image to widget.
	 *
	 * @abstract
	 * @return {void}
	 */
	function addItemToCustomizer() {
		var selectedItem, imageElement, setting, settingId,
			parent = customizeButton.parentNode,			
			removeButton = document.createElement( 'button' ),
			selectButton = document.createElement( 'button' );

		if ( ! parent ) {
			return;
		}

		setting = parent.closest( 'li' );
		settingId = setting.dataset.settingId;

		removeButton.className = 'button remove-button';
		removeButton.type = 'button';
		removeButton.textContent = 'Remove';

		selectButton.className = 'button select-button';
		selectButton.type = 'button';
		selectButton.textContent = parent.dataset.full;

		if ( ! dialog.querySelector( '#media-library-grid' ).hasAttribute( 'hidden' ) ) {
			selectedItem = dialog.querySelector( '.widget-modal-grid .selected' );
			imageElement = selectedItem.querySelector( 'img' );
		}

		// Update header image
		if ( settingId === 'header_image_data' ) {			
			parent.previousElementSibling.querySelector( '.container' ).innerHTML = '';
			parent.previousElementSibling.querySelector( '.container' ).append( imageElement );
			customizeButton.previousElementSibling.style.display = '';
			customizeButton.classList.remove( 'upload-button' );
			parent.previousElementSibling.querySelector( 'input' ).value = selectedItem.dataset.id;
			_updatedControlsWatcher[ settingId ] = {
				attachment_id: parseInt( selectedItem.dataset.id ),
				url: selectedItem.dataset.url,
				thumbnail_url: selectedItem.dataset.sizes?.thumbnail?.url || selectedItem.dataset.url,
				width: selectedItem.dataset.width,
				height: selectedItem.dataset.height,
			};

		// Insert other images according to whether this is a new insertion or replacement
		} else {
			imageElement.className = 'thumbnail thumbnail-image';
			if ( parent.parentNode.querySelector( 'img' ) || parent.parentNode.querySelector( 'video' ) ) {
				parent.parentNode.querySelector( 'img' )?.replaceWith( imageElement );
				parent.parentNode.querySelector( 'video' )?.replaceWith( imageElement );
			} else {
				parent.parentNode.prepend( imageElement );
				parent.prepend( removeButton );
				customizeButton.replaceWith( selectButton );
				setTimeout( function() {
					selectButton.focus();
				} );
			}
			if ( settingId === 'background_image' ) {
				parent.parentNode.querySelector( 'input' ).value = selectedItem.dataset.url;
				_updatedControlsWatcher[ settingId ] = selectedItem.dataset.url;
			} else {
				parent.parentNode.querySelector( 'input' ).value = selectedItem.dataset.id;
				_updatedControlsWatcher[ settingId ] = selectedItem.dataset.id;
			}
		}
		closeModal();
		activatePublishButton();
	}

	/**
	 * Removes media from Customizer.
	 *
	 * @abstract
	 * @return {void}
	 */
	function removeMedia() {
		var button,
			parent = customizeButton.parentNode;

		if ( ! customizeButton.nextElementSibling.id || customizeButton.nextElementSibling.id !== 'header_image-button' ) {
			button = document.createElement( 'button' );
			button.className = 'upload-button button select-button';
			button.type = 'button';
			button.textContent = customizeButton.parentNode.dataset.empty;
			parent.parentNode.querySelector( 'img' )?.remove();
			parent.parentNode.querySelector( 'video' )?.remove();
			parent.parentNode.querySelector( 'input' ).value = '';
			parent.innerHTML = '';
			parent.append( button );
			setTimeout( function() {
				button.focus();
			} );
		} else { // header image
			parent.previousElementSibling.querySelector( 'img' )?.remove();
			parent.previousElementSibling.querySelector( 'video' )?.remove();
			parent.previousElementSibling.querySelector( 'input' ).value = '';
			customizeButton.style.display = 'none';
			customizeButton.nextElementSibling.className = 'upload-button button new select-button';
			setTimeout( function() {
				customizeButton.nextElementSibling.focus();
			} );
		}
		activatePublishButton();
	}

	/**
	 * Insert a new media frame within the modal to enable editing of image.
	 *
	 * @abstract
	 * @return {void}
	 */
	function imageEdit( widgetId ) {
		var formData = new FormData(),
			attachmentId = document.querySelector( '#' + widgetId + ' [data-property="attachment_id"]' ).value,
			nonce = document.querySelector( '#' + widgetId + ' .edit-media' ).dataset.editNonce;

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
		var embed, details;
		if ( dialog.querySelector( '#widget-modal-media-content' ) && window.innerWidth < 901 ) {
			details = dialog.querySelector( 'details' );
			details.removeAttribute( 'hidden' );
			details.addEventListener( 'toggle', function( e ) {
				if ( e.target.open ) {
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
		dialog.querySelector( '#media-widget-modal' ).prepend( leftSidebar );
		dialog.querySelector( '.widget-modal-header-buttons' )?.remove();
		dialog.querySelector( '#widget-modal-media-content' )?.remove();
	}

	/**
	 * Publish updates by clicking Publish button.
	 *
	 * @abstract
	 * @return {void}
	 */
	form.addEventListener( 'submit', async function( e ) {
		let negativeId, menuId, result, newResult,
			entries = Object.entries( updatedControls ),
			navMenuChanges = {},
			submittedChanges = {},
			navMenuNegatives = [], // an array because we need it to be iterable
			navMenuLocations = [],
			navMenuItems = [],
			formData = new FormData(),
			updateData = new FormData();

		// Prevent form submission via PHP
		e.preventDefault();

		window._customizePublishing = true;

		// Prevent accidental form submissions
		if ( e.submitter !== saveButton ) {
			return;
		}

		// Populate arrays if a new menu is being added
		for ( const [key, value] of entries ) {
			if ( key.startsWith( 'nav_menu[-' ) ) {
				navMenuNegatives.push( [key, value] ); // value is the menu name
			}
		}
		if ( navMenuNegatives.length > 0 ) {
			for ( const [key, value] of entries ) {
				if ( key.startsWith( 'nav_menu_locations[' ) ) {
					navMenuLocations.push( [key, value] );
				} else if ( key.startsWith( 'nav_menu_item[' ) ) {
					navMenuItems.push( [key, value] );
				}
			}
		}

		// Create new menus first
		for ( const [key, object] of navMenuNegatives ) {
			negativeId = key.replace( 'nav_menu[', '' ).replace( ']', '' );

			// Build correct object for only this one menu
			navMenuChanges = {};
			navMenuChanges[key] = {
				value: {
					name: object.name,
					auto_add: !! object.auto_add
				}
			};

			// Append values to FormData for POSTing to back-end PHP handler
			formData.append( 'action', 'customize_save' );
			formData.append( 'nonce', document.getElementById( 'customizer_nonce' ).value );
			formData.append( 'customize_theme', document.getElementById( 'theme_stylesheet' ).value );
			formData.append( 'customize_changeset_uuid', document.getElementById( 'customize_changeset_uuid' ).value );
			formData.append( 'customize_changeset_status', 'publish' );
			formData.append( 'customize_changeset_data', JSON.stringify( navMenuChanges ) );

			try {
				const response = await fetch( ajaxurl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				} );

				if ( ! response.ok ) {
					throw new Error( response.status );
				}
				result = await response.json();
			} catch ( err ) {
				console.error( err );
				continue;
			}

			if ( result && result.success ) {
				menuId = result?.data?.nav_menu_updates?.[0]?.term_id;
				if ( menuId ) {

					// Update any menu location currently populated by negativeId
					navMenuLocations.forEach( function( locationArray, index ) {
						if ( locationArray[1] === negativeId ) {
							_updatedControlsWatcher[locationArray[0]] = menuId;
						}
					} );

					// Update any nav_menu_items attached to this menu
					navMenuItems.forEach( function( array, index ) {
						if ( array[1].menu_id === negativeId ) {
							_updatedControlsWatcher[array[0]].menu_id = menuId;
						}
					} );

					// Prevent duplicate submissions
					delete updatedControls[key];
					formData.delete( 'customize_changeset_data' );
					navMenuChanges = {};

					// Update DOM attributes that contain negativeId
					replaceSubstringInAttributes( negativeId, menuId );
				}

				// If the server rolled the changeset UUID, update it before next call
				if ( result.data.next_changeset_uuid ) {
					document.getElementById( 'customize_changeset_uuid' ).value = result.data.next_changeset_uuid;
				}
			}
		}

		// Prepare changeset object
		Object.keys( updatedControls ).forEach( function( settingId ) {
			const item = updatedControls[ settingId ];

			if ( settingId.startsWith( 'nav_menu[' ) && item === 'delete-menu' ) {
				submittedChanges[ settingId ] = {
					value: false // deletes menu
				};
			} else if ( settingId.startsWith( 'nav_menu[' ) ) {
				submittedChanges[ settingId ] = {
					value: {
						name: ( typeof item === 'string' ) ? item : item.name || '',
						description: item.description || '',
						parent: item.parent ? parseInt( item.parent, 10 ) : 0,
						auto_add: !! item.auto_add // default false
					}
				};
			} else if ( settingId.startsWith( 'nav_menu_item[' ) ) {
				submittedChanges[ settingId ] = {
					value: {
						nav_menu_term_id: parseInt( item.nav_menu_term_id, 10 ),
						position: parseInt( item.position, 10 ),
						title: item.title || '',
						url: item.url || '',
						original_title: item.original_title || '',
						menu_item_parent: parseInt( item.menu_item_parent, 10 ) || 0,
						object_id: item.object_id || 0,
						object: item.object || '',
						type: item.type || 'custom',
						type_label: item.type_label || '',
						classes: item.classes || [],
						xfn: item.xfn || '',
						target: item.target || '',
						attr_title: item.attr_title || '',
						description: item.description || '',
						status: item.status || 'publish',
						display_mode: item.display_mode || '',
						roles: item.roles || ''
					}
				};
			} else if ( settingId.startsWith( 'nav_menu_locations[' ) ) {
				submittedChanges[ settingId ] = {
					value: item || ''
				};
			} else { // All other settings
				submittedChanges[ settingId ] = {
					value: item || ''
				};
			}

			if ( newMenuItemIDs.length > 0 ) {
				submittedChanges['nav_menus_created_posts'] = {
					value: newMenuItemIDs
				}
			}
		} );

		// Append new data for POSTing to PHP back-end handler
		updateData.append( 'action', 'customize_save' );
		updateData.append( 'nonce', document.getElementById( 'customizer_nonce' ).value );
		updateData.append( 'customize_theme', document.getElementById( 'theme_stylesheet' ).value );
		updateData.append( 'customize_changeset_uuid', document.getElementById( 'customize_changeset_uuid' ).value );
		updateData.append( 'customize_changeset_status', 'publish' );
		updateData.append( 'customize_changeset_data', JSON.stringify( submittedChanges ) );

		try {
			const response = await fetch( ajaxurl, {
				method: 'POST',
				body: updateData,
				credentials: 'same-origin'
			} );
			if ( ! response.ok ) {
				throw new Error( response.status );
			}
			newResult = await response.json();
		} catch ( err ) {
			console.error( _wpCustomizeControlsL10n.saveBlockedError['plural'] + ':', err );
			saveButton.disabled = false;
			saveButton.value = _wpCustomizeControlsL10n.publish;
		}

		// Update HTML
		if ( newResult && newResult.success ) {
			if ( newResult.data.nav_menu_item_updates ) {
				newResult.data.nav_menu_item_updates.forEach( function( item ) {
					replaceSubstringInAttributes( item.previous_post_id, item.post_id );
				} );
			}

			// Update theme heading
			form.querySelector( '.accordion-section-title .customize-action' ).textContent = _wpCustomizeControlsL10n.active_theme;

			// Reset hidden widget fields
			form.querySelectorAll( '.multi_number' ).forEach( function( multi ) {
				multi.value = '';
			} );
			form.querySelectorAll( '.add_new' ).forEach( function( add ) {
				add.value = '';
			} );

			// Reset form
			saveButton.disabled = true;
			saveButton.value = _wpCustomizeControlsL10n.published;
			document.getElementById( 'customize_changeset_uuid' ).value = newResult.data.next_changeset_uuid;

			// Reset the buffer object and proxy
			Object.keys( updatedControls ).forEach( function( key ) {
				delete updatedControls[ key ];
			} );
			window._customizePublishing = false;
		}
	} );

	/**
	 * Replaces the substring 'brand-new' in new menu attributes with negative integer.
	 * Then replaces the negative integer with menuId on new menu publication.
	 * 
	 * @since CP-2.8.0
	 */
	function replaceSubstringInAttributes( original, replacement ) {
		let all, menu;
		if ( original === null || original === replacement ) {
			return;
		}
		all = menuToEdit.querySelectorAll( '*' );
		if ( all.length < 1 ) { // menu already moved to new place
			menu = document.getElementById( 'sub-accordion-section-nav_menu[' + original + ']' );
			if ( menu ) {
				all = menu.querySelectorAll( '*' ); // locate moved menu and its attributes
				menu.id = 'sub-accordion-section-nav_menu[' + replacement + ']';
			}
		}

		all.forEach( function( el ) {
			const attrs = el.getAttributeNames();
			attrs.forEach( function( attrName ) {
				const value = el.getAttribute( attrName );
				if ( value && value.includes( original ) ) {
					const newValue = value.replaceAll( original, replacement );
					el.setAttribute( attrName, newValue );
				}
			} );
		} );
	}

	/**
	 * Handle clicks on buttons.
	 *
	 * @abstract
	 * @return {void}
	 */
	document.addEventListener( 'click', function( e ) {
		var id, page, itemBrowse, itemUpload, gridPanel, uploadPanel,
			modalButtons, rightSidebar, modalPages, title, navMenuId,
			type, object, objectId, label, url, li, template, clone,
			menuName = '',
			ul = e.target.closest( 'ul' );

		// Abort if this comes from a middle section heading or a widget
		if ( ( e.target.tagName !== 'BUTTON' && e.target.closest( '.customize-section-title' ) ) || e.target.closest( '.widget-rendered' ) ) {
			return;
		}

		// Abort for some menu-related targets
		if ( e.target.closest( 'ul' ) ) {
			if ( e.target.closest( 'ul' ).classList && e.target.closest( 'ul' ).classList.contains( 'menu' ) && ( ! e.target.classList.contains( 'customize-section-back' ) && ! e.target.classList.contains( 'customize-panel-back' ) ) ) {
				return;
			}
		}

		if ( ( e.target.tagName === 'H3' || e.target.classList && e.target.classList.contains( 'change-theme' ) ) && ul ) {
			e.preventDefault();
			id = e.target.closest( 'li' ).id;
			document.body.classList.remove( 'adding-menu-items' );
			document.body.classList.remove( 'adding-widget' );
			document.getElementById( 'widgets-left' ).style.display = 'none';
			availableMenuItems.style.display = 'none';
			addMenuButtons.forEach( function( add ) {
				add.setAttribute( 'aria-expanded', false );
			} );
			addWidgetButtons.forEach( function( add ) {
				add.setAttribute( 'aria-expanded', false );
			} );

			// Go down to the second level
			if ( ul.classList.contains( 'customize-pane-parent' ) ) {
				ul.style.display = 'none';
				document.getElementById( 'customize-info' ).style.display = 'none';
				document.getElementById( 'sub-' + id ).style.display = 'block';
				document.getElementById( 'sub-' + id ).querySelector( 'button' ).focus();
				window.history.pushState( {}, '', _wpCustomizeControlsL10n.customizeUrl + '#sub-' + id );

			// Go down to the third level
			} else if ( ! e.target.closest( 'li' ).classList.contains( 'customize-control-widget_form' ) && ul.classList.contains( 'customize-pane-child' ) ) {
				if ( ! id.startsWith( 'accordion-section-nav_menu[' ) ) { // not a nav menu
					ul.style.display = 'none';
					document.getElementById( 'sub-' + id ).style.display = 'block';
					document.getElementById( 'sub-' + id ).querySelector( 'button' ).focus();
					document.getElementById( 'sub-' + id ).setAttribute( 'data-parent-id', ul.id ); // update
				}
				window.history.pushState( {}, '', _wpCustomizeControlsL10n.customizeUrl + '#sub-' + id );
			}

		//Go up a level
		} else if ( e.target.classList && ( e.target.classList.contains( 'customize-section-back' ) || e.target.classList.contains( 'customize-panel-back' ) ) ) {
			e.preventDefault();
			ul.style.display = 'none';
			document.body.classList.remove( 'adding-menu-items' );
			document.body.classList.remove( 'adding-widget' );
			document.getElementById( 'widgets-left' ).style.display = 'none';
			availableMenuItems.style.display = 'none';
			addMenuButtons.forEach( function( add ) {
				add.setAttribute( 'aria-expanded', false );
			} );
			addWidgetButtons.forEach( function( add ) {
				add.setAttribute( 'aria-expanded', false );
			} );

			document.getElementById( ul.dataset.parentId ).style.display = 'block';
			document.getElementById( ul.dataset.parentId ).querySelector( 'button' ).focus();
			window.history.pushState( {}, '', _wpCustomizeControlsL10n.customizeUrl + '#' + ul.dataset.parentId );

		// Go to widgets panel
		} else if ( e.target.tagName === 'A' && ( e.target.closest( 'li' ).id === 'accordion-section-menu_locations' || ul.id === 'sub-accordion-section-menu_locations' ) ) {
			e.preventDefault();			
			ul.style.display = 'none';
			document.getElementById( 'sub-accordion-panel-widgets' ).style.display = 'block';
			window.history.pushState( {}, '', _wpCustomizeControlsL10n.customizeUrl + '#sub-accordion-panel-widgets' );

		// Add a widget
		} else if ( e.target.classList && e.target.classList.contains( 'add-new-widget' ) ) {
			document.body.classList.toggle( 'adding-widget' );
			if ( e.target.getAttribute( 'aria-expanded' ) === 'false' ) {
				document.getElementById( 'widgets-left' ).style.display = 'block';
				e.target.setAttribute( 'aria-expanded', true );
			} else {
				document.getElementById( 'widgets-left' ).style.display = 'none';
				e.target.setAttribute( 'aria-expanded', false );
			}

		// Reorder widgets
		} else if ( e.target.classList && e.target.className === 'reorder' ) {
			ul.classList.add( 'reordering' );

		// Finish reordering
		} else if ( e.target.classList && e.target.className === 'reorder-done' ) {
			ul.classList.remove( 'reordering' );

		// Open and close description
		} else if ( e.target.classList && e.target.classList.contains( 'customize-help-toggle' ) ) {
			if ( e.target.parentNode.classList.contains( 'open' ) ) {
				e.target.parentNode.classList.remove( 'open' )
				e.target.parentNode.nextElementSibling.style.display = 'none';
				e.target.setAttribute( 'aria-expanded', false );
			} else {
				e.target.parentNode.classList.add( 'open' );
				e.target.parentNode.nextElementSibling.style.display = 'block';
				e.target.setAttribute( 'aria-expanded', true );
			}

		// Browse installed themes
		} else if ( e.target.classList && e.target.classList.contains( 'themes-section-installed_themes' ) ) {
			form.querySelector( '.themes-section-wporg_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			if ( document.querySelector( '.wp-org' ) ) {
				document.querySelector( '.themes').innerHTML = installedThemesHTML;
				document.querySelector( '.theme-browser' ).classList.remove( 'wp-org' );
				document.querySelector( '.theme-browser' ).classList.add( 'local' );
				document.querySelector( '.feature-filter-toggle' ).style.display = 'none';
				document.querySelector( '.filter-themes-count .theme-count' ).textContent = document.querySelectorAll( '.local .themes li' ).length;
			}
			if ( orgThemes ) {
				intersectionObserver.unobserve( orgThemes[orgThemes.length - 1] ); // deactivate Intersection Observer
			}

		// Browse themes at wp.org
		} else if ( e.target.classList && e.target.classList.contains( 'themes-section-wporg_themes' ) ) {
			form.querySelector( '.themes-section-installed_themes' ).classList.remove( 'selected' );
			e.target.classList.add( 'selected' );
			document.querySelector( '.theme-browser' ).classList.remove( 'local' );
			document.querySelector( '.theme-browser' ).classList.add( 'wp-org' );
			document.querySelector( '.feature-filter-toggle' ).style.display = 'inline-block';
			updateThemes( 'browse', 'new' );

		// Select theme tags
		} else if ( e.target.parentNode === document.querySelector( '.feature-filter-toggle' ) ) {
			if ( isVisible( document.querySelector( '.filter-drawer' ) ) ) {
				document.querySelector( '.filter-drawer' ).style.display = 'none';
			} else {
				document.querySelector( '.filter-drawer' ).style.display = 'block';
			}

		// Collapse or expand sidebar
		} else if ( e.target.parentNode.classList && e.target.parentNode.classList.contains( 'collapse-sidebar' ) ) {
			e.preventDefault();
			sidebarCollapseExpand( e.target.parentNode );

		// Remove media file
		} else if ( e.target.tagName === 'BUTTON' && ( e.target.classList.contains( 'remove' ) || e.target.classList.contains( 'remove-button' ) ) ) {
			customizeButton = e.target;
			removeMedia();

		// Add media file
		} else if ( e.target.tagName === 'BUTTON' && e.target.classList.contains( 'select-button' ) ) {
			customizeButton = e.target;
			selectMedia();

		// Edit the image
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
			itemBrowse   = dialog.querySelector( '#menu-item-browse' );
			itemUpload   = dialog.querySelector( '#menu-item-upload' );
			gridPanel    = dialog.querySelector( '#media-library-grid' );
			rightSidebar = dialog.querySelector( '.widget-modal-right-sidebar' );
			modalPages   = dialog.querySelector( '.widget-modal-pages' );
			uploadPanel  = dialog.querySelector( '#uploader-inline' );
			modalButtons = dialog.querySelector( '.widget-modal-header-buttons' );

			// Search or go to a specific page in the media library grid
			if ( e.target.parentNode.className === 'pagination-links' && e.target.tagName === 'BUTTON' ) {
				page = e.target.dataset.page;
				updateGrid( page );
			} else if ( e.target.parentNode.parentNode && e.target.parentNode.parentNode.className === 'pagination-links' && e.target.parentNode.tagName === 'BUTTON' ) {
				page = e.target.parentNode.dataset.page;
				updateGrid( page );

			// Add a new image to a widget via the image's URL
			} else if ( e.target.id === 'menu-item-embed' ) {
				dialog.querySelector( 'h2' ).textContent = IMAGE_WIDGET.insert_from_url;
				itemBrowse.classList.remove( 'active' );
				itemBrowse.setAttribute( 'aria-selected', false );
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				e.target.classList.add ( 'active' );
				e.target.setAttribute( 'aria-selected', true );
				modalButtons.style.display = 'none';
				uploadPanel.setAttribute( 'hidden', true );
				uploadPanel.setAttribute( 'inert', true );
				gridPanel.setAttribute( 'hidden', true );
				gridPanel.setAttribute( 'inert', true );
				rightSidebar.setAttribute( 'hidden', true );
				insertEmbed();

			// Search for a new image to add to a widget
			} else if ( e.target.id === 'menu-item-add' ) {
				dialog.querySelector( 'h2' ).textContent = IMAGE_WIDGET.media_library;
				itemUpload.classList.remove( 'active' );
				itemUpload.setAttribute( 'aria-selected', false );
				itemBrowse.classList.remove( 'active' );
				itemBrowse.setAttribute( 'aria-selected', false );
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

			// Add item to Customizer control
			} else if ( e.target.id === 'media-button-insert' ) {
				addItemToCustomizer();

			// Delete an attachment
			} else if ( e.target.className.includes( 'delete-attachment' ) ) {
				if ( widgetEl.querySelector( '[data-property="attachment_id"]' ) ) {
					if ( dialog.querySelector( '.widget-modal-grid .selected' ).dataset.id != widgetEl.querySelector( '[data-property="attachment_id"]' ).value ) {
						if ( window.confirm( IMAGE_WIDGET.confirm_delete ) ) {
							deleteItem( dialog.querySelector( '.widget-modal-grid .selected' ).dataset.id );
						}
					}
				}

			// Copy URL
			} else if ( e.target.className.includes( 'copy-attachment-url' ) ) {
				copyToClipboard( e.target );
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
		// Do not run if file sought from widget
		if ( e.target.closest( '.widget' ) ) {
			return;
		}

		if ( e.target.id === 'filter-by-date' ) {
			updateGrid( 1 );
		} else if ( e.target.className === 'postform' ) {
			updateGrid( 1 );
		} else if ( e.target.id === 'current-page-selector' ) {
			updateGrid( e.target.value );
		} else if ( e.target.id === 'widget-modal-search-input' ) {
			updateGrid( 1 );
			dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
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
							load( 'finished' );
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
			onprocessfile: ( error, file ) => { // Called when an individual file upload completes
				if ( ! error ) {
					setTimeout( function() {
						pond.removeFile( file.id );
					}, 100 );
					resetDataOrdering();
				}
			},
			onprocessfiles: () => { // Called when all files in the queue have finished uploading
				updateGrid( document.getElementById( widgetId ), 1 );
				dialog.querySelector( '#menu-item-browse' ).click();
				setTimeout( function() {
					dialog.querySelector( '.widget-modal-right-sidebar-info' ).setAttribute( 'hidden', true );
				}, 500 );
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
