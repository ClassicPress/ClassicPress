/**
 * @output wp-admin/js/admin-media-modal.js
 */

/* global ajaxurl, wpAjax */

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
		closeButton = modal ? modal.querySelector( '.admin-modal-close' ) : null,
		chooseLink = document.getElementById( 'choose-from-library-link' );

	let selectedAttachment = null;

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

	function selectItem( gridItem, attachment ) {
		const items = grid.querySelectorAll( '.media-item' ),
			isSelected = gridItem.classList.contains( 'selected' ),
			sidebarInfo = modal.querySelector( '.admin-modal-right-sidebar-info' );

		items.forEach( function( item ) {
			item.classList.remove( 'selected' );
			item.setAttribute( 'aria-checked', 'false' );
			item.querySelector( '.check' ).style.display = 'none';
			sidebarInfo.setAttribute( 'hidden', 'true' );
		} );

		if ( isSelected ) {
			gridItem.classList.remove( 'selected' );
			gridItem.setAttribute( 'aria-checked', 'false' );
			gridItem.querySelector( '.check' ).style.display = 'none';
			sidebarInfo.setAttribute( 'hidden', 'true' );
			selectedAttachment = null;
			selectButton.disabled = true;
		} else {
			gridItem.classList.add( 'selected' );
			gridItem.setAttribute( 'aria-checked', 'true' );
			gridItem.querySelector( '.check' ).style.display = 'block';
			sidebarInfo.removeAttribute( 'hidden' );
			selectedAttachment = attachment;

			document.getElementById( 'attachment-details-alt-text' ).value = gridItem.querySelector( 'img' ).alt;
			document.getElementById( 'attachment-details-title' ).value = gridItem.getAttribute( 'aria-label' );
			document.getElementById( 'attachment-details-caption' ).value = gridItem.dataset.caption;
			document.getElementById( 'attachment-details-description' ).value = gridItem.dataset.description;
			document.getElementById( 'attachment-details-copy-link' ).value = gridItem.dataset.url;

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
