/**
 * @output wp-admin/js/custom-background.js
 */

/* global ajaxurl, Coloris, wpAjax, wp */

/**
 * Registers all events for customizing the background.
 *
 * @since 3.0.0
 * @since CP-2.8.0 Rewritten to use vanilla JavaScript and Coloris color picker.
 *
 * @return {void}
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	const bgImage = document.getElementById( 'custom-background-image' );

	/**
	 * Instantiates the Coloris color picker and binds the change and clear events.
	 *
	 * @since 3.5.0
	 * @since x.x.x Uses Coloris instead of wp-color-picker.
	 *
	 * @return {void}
	 */
	function initColorPicker() {
		const colorInput = document.getElementById( 'background-color' );
	
		if ( ! colorInput ) {
			return;
		}

		Coloris( {
			alpha: false,
			format: 'hex',
			a11y: {
				open: 'Open color picker',
				close: 'Close color picker',
				clear: 'Clear the selected color',
				marker: 'Saturation: {s}. Brightness: {v}.',
				hueSlider: 'Hue slider',
				alphaSlider: 'Opacity slider',
				input: 'Color value field',
				format: 'Color format',
				swatch: 'Color swatch',
				instruction: 'Saturation and brightness selector. Use up, down, left and right arrow keys to select.'
			},
			swatches: [
				'#264653',
				'#2a9d8f',
				'#e9c46a',
				'#f4a261',
				'#e76f51',
				'#d62828',
				'#000080',
				'#0077bb',
				'#0096c7',
				'#00b4d8',
				'#0077b6'
			],
			clearButton: true,
			onChange: ( color, inputEl ) => {
				let effectiveColor = color || inputEl.dataset.defaultColor || '';

				if ( bgImage ) {
					bgImage.style.backgroundColor = effectiveColor;
				}
			}
		} );
	}

	/**
	 * Alters the background size CSS property whenever the background size input has changed.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundSize() {
		const select = document.querySelector( 'select[name="background-size"]' );
		if ( ! select || ! bgImage ) {
			return;
		}

		select.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundSize = e.target.value;
		} );
	}

	/**
	 * Alters the background position CSS property whenever the background position input has changed.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundPosition() {
		const input = document.querySelector( 'input[name="background-position"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundPosition = e.target.value;
		} );
	}

	/**
	 * Alters the background repeat CSS property whenever the background repeat input has changed.
	 *
	 * @since 3.0.0
	 *
	 * @return {void}
	 */
	function initBackgroundRepeat() {
		const input = document.querySelector( 'input[name="background-repeat"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundRepeat = e.target.checked ? 'repeat' : 'no-repeat';
		} );
	}

	/**
	 * Alters the background attachment CSS property whenever the background attachment input has changed.
	 *
	 * @since 4.7.0
	 *
	 * @return {void}
	 */
	function initBackgroundAttachment() {
		const input = document.querySelector( 'input[name="background-attachment"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function( e ) {
			bgImage.style.backgroundAttachment = e.target.checked ? 'scroll' : 'fixed';
		} );
	}

	/**
	 * Opens a media grid from which to choose a background image.
	 *
	 * @since CP-2.8.0
	 *
	 * @return {void}
	 */
	function initMediaSelector() {
		const chooseLink = document.getElementById( 'choose-from-library-link' ),
			modal = document.getElementById( 'admin-media-modal' ),
			grid = document.getElementById( 'admin-modal-grid' ),
			selectButton = document.getElementById( 'admin-modal-select-button' ),
			closeButton = modal.querySelector( '.admin-modal-close' );

		let selectedAttachment = null;

		if ( ! chooseLink || ! modal ) {
			return;
		}

		/**
		 * Loads attachments from the media library.
		 *
		 * @return {void}
		 */
		function loadAttachments() {
			const params = new URLSearchParams( {
				action: 'query-attachments',
				'query[posts_per_page]': 80,
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

		/**
		 * Populates the grid with attachment thumbnails.
		 *
		 * @param {Array} attachments Array of attachment objects.
		 *
		 * @return {void}
		 */
		function populateGrid( attachments ) {
			grid.replaceChildren();

			if ( attachments.length === 0 ) {
				const p = document.createElement( 'p' );
				p.textContent = 'No images found.';
				grid.append( p );
				return;
			}

			attachments.forEach( function( attachment ) {
				const gridItem = document.createElement( 'li' ),
					div  = document.createElement( 'div' ),
					thumb = document.createElement( 'div' ),
					img = document.createElement( 'img' ),
					button = document.createElement( 'button' ),
					spanIcon = document.createElement( 'span' ),
					spanSR = document.createElement( 'span' );

				gridItem.id = 'media-' + attachment.id;
				gridItem.className = 'media-item';
				gridItem.setAttribute( 'data-id', attachment.id );
				gridItem.setAttribute( 'tabindex', '0' );
				gridItem.setAttribute( 'role', 'checkbox' );
				gridItem.setAttribute( 'aria-checked', 'false' );
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

				div.className = 'select-attachment-preview type-image subtype-webp';
				thumb.className = 'media-thumbnail';
				img.src = attachment.url;
				img.alt = attachment.alt;
				thumb.append( img );
				div.append( thumb );
				button.type = 'button';
				button.className = 'check';
				button.tabindex = '-1';
				spanIcon.classname = 'media-modal-icon';
				spanSR.className = 'screen-reader-text';
				spanSR.textContent = 'Deselect';
				button.append( spanIcon, spanSR );
				gridItem.append( div, button );

				gridItem.addEventListener( 'click', function() {
					selectItem( gridItem, attachment );
				} );

				grid.append( gridItem );
			} );
		}

		/**
		 * Selects or deselects a media item.
		 *
		 * @param {HTMLElement} item The grid item element.
		 * @param {Object} attachment The attachment data.
		 *
		 * @return {void}
		 */
		function selectItem( gridItem, attachment ) {
			const items = grid.querySelectorAll( '.media-item' ),
				selectButton = document.getElementById( 'admin-modal-select-button' ),
				isSelected = gridItem.classList.contains( 'selected' ),
				sidebarInfo = modal.querySelector( '.admin-modal-right-sidebar-info' );

			// Deselect all items
			items.forEach( function( item ) {
				item.classList.remove( 'selected' );
				item.setAttribute( 'aria-checked', 'false' );
				item.querySelector( '.check' ).style.display = 'none';
				sidebarInfo.setAttribute( 'hidden', 'true' );
			} );

			// If the clicked gridItem was already selected, deselect it
			if ( isSelected ) {
				gridItem.classList.remove( 'selected' );
				gridItem.setAttribute( 'aria-checked', 'false' );
				gridItem.querySelector( '.check' ).style.display = 'none';
				sidebarInfo.setAttribute( 'hidden', 'true' );
				selectedAttachment = null;

				// Disable the select button
				selectButton.disabled = true;
			} else {
				// Select the clicked gridItem
				gridItem.classList.add( 'selected' );
				gridItem.setAttribute( 'aria-checked', 'true' );
				gridItem.querySelector( '.check' ).style.display = 'block';
				sidebarInfo.removeAttribute( 'hidden' );
				selectedAttachment = attachment;

				// Fill out the sidebar info.
				document.getElementById( 'attachment-details-alt-text' ).value = gridItem.querySelector( 'img' ).alt;
				document.getElementById( 'attachment-details-title' ).value = gridItem.getAttribute( 'aria-label' );
				document.getElementById( 'attachment-details-caption' ).value = gridItem.dataset.caption;
				document.getElementById( 'attachment-details-description' ).value = gridItem.dataset.description;
				document.getElementById( 'attachment-details-copy-link' ).value = gridItem.dataset.url;

				// Enable the select button
				selectButton.disabled = false;
			}
		}

		/**
		 * Handles the select button click.
		 *
		 * @return {void}
		 */
		function handleSelect() {
			if ( ! selectedAttachment ) {
				return;
			}

			const nonceField = document.getElementById( '_wpnonce' ),
				params = new URLSearchParams( {
					action: 'set-background-image',
					attachment_id: selectedAttachment.id,
					_ajax_nonce: nonceField ? nonceField.value : '',
					size: 'full'
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

		// Open modal on choose link click
		chooseLink.addEventListener( 'click', function( event ) {
			event.preventDefault();
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
	}

	// Initialize all components.
	initColorPicker();
	initBackgroundSize();
	initBackgroundPosition();
	initBackgroundRepeat();
	initBackgroundAttachment();
	initMediaSelector();
} );
