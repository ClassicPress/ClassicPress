/**
 * @file Revisions list interface functions.
 *
 * @since CP-2.6.0.
 *
 * @output wp-admin/js/revisions.js
 */

/* global console, ajaxurl */

document.addEventListener( 'DOMContentLoaded', function() {
	var dialog = document.getElementById( 'modal-revision' ),
		closeButton = document.getElementById( 'modal-revision-close-button' ),
		modalButton = document.getElementById( 'modal-revision-button' );

	// Open modal to view Revision on revisions-list.php
	document.querySelectorAll( 'table button.page-title-action' ).forEach( function( button ) {
		button.addEventListener( 'click', function( e ) {
			var data = new URLSearchParams( {
				action: 'get-revision',
				id: e.target.closest( 'tr' ).querySelector( '.id span' ).textContent
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
				dialog.querySelector( 'h2' ).textContent = result.data.title;
				dialog.querySelector( '#modal-revision-content-inner' ).innerHTML = result.data.content;
				dialog.showModal();
			} )
			.catch( function( error ) {
				console.error( error );
			} );
		} );
	} );

	/* Close modal by hitting Escape key, and trap focus in modal */
	document.addEventListener( 'keydown', function( e ) {
		if ( e.key === 'Escape' ) {
			closeModalDialog();
		} else if ( e.key === 'Tab' ) {
			if ( e.shiftKey ) {
				if ( e.target === closeButton ) {
					e.preventDefault();
					modalButton.focus();
				}
			} else if ( e.target === modalButton ) {
				e.preventDefault();
				closeButton.focus();
			}
		}
	} );

	/* Close modal by clicking button */
	function closeModalDialog() {
		document.body.style.overflow = '';
		dialog.close();
		dialog.querySelector( 'h2' ).textContent = '';
		dialog.querySelector( '#modal-revision-content-inner' ).innerHTML = '';
	}
	closeButton.addEventListener( 'click', closeModalDialog );
	modalButton.addEventListener( 'click', closeModalDialog );
} );
