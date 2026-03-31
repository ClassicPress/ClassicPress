/**
 * cpCropper.js
 * Vanilla JS image cropper for the ClassicPress Customizer.
 * Replaces the Backbone/jQuery crop view from media-views.js.
 *
 * Dependencies: Cropper.js (https://fengyuanchen.github.io/cropperjs/)
 *
 * Typical call site — inside your image-selection handler:
 *
 *   cpCropper.open({
 *     attachmentId : selectedEl.dataset.id,
 *     imageUrl     : selectedEl.dataset.url,
 *     nonce        : selectedEl.dataset.editNonce,  // reads data-edit-nonce
 *     context      : 'site-icon',                   // or 'custom_logo'
 *     aspectRatio  : 1,                             // null = free crop
 *     minWidth     : 512,
 *     minHeight    : 512,
 *     onSelect     : function( attachment ) {
 *       // attachment.id  — new cropped attachment ID
 *       // attachment.url — URL of the cropped image
 *     }
 *   });
 */
/* global ajaxurl, console, CROPPER */

( function( window, document ) {
	'use strict';

	let dialog      = null;
	let cropperInst = null;
	let currentOpts = {};

	function buildDialog() {
		if ( dialog ) {
			return;
		}

		dialog = document.createElement( 'dialog' );
	    dialog.id        = 'cp-cropper-dialog';
		dialog.innerHTML = '<div id="cp-cropper-modal" class="widget-modal-container">' +
			'<div class="widget-modal-main">' +
				'<header class="widget-modal-header">' +
					'<div class="widget-modal-headings">' +
						'<div id="cp-cropper-title" class="widget-modal-title">' +
							'<h2>' + CROPPER.crop_image + '</h2>' +
						'</div>' +
						'<button id="cp-cropper-close" type="button" class="widget-modal-close" autofocus>' +
							'<span id="cp-cropper-modal-icon" class="widget-modal-icon">' +
								'<span class="screen-reader-text">' + CROPPER.close_crop + '</span>' +
							'</span>' +
						'</button>' +
					'</div>' +
				'</header>' +
				'<article id="cp-cropper-body" class="widget-modal-content">' +
					'<img id="cp-cropper-img" src="" alt="' + CROPPER.image_to_crop + '">' +
				'</article>' +
				'<footer class="widget-modal-footer">' +
					'<p id="cp-cropper-skip-note">' + CROPPER.skip_note + '</p>' +
					'<div id="cp-cropper-actions" class="widget-modal-footer-buttons">' +
						'<button type="button" id="cp-cropper-skip"  class="button">' + CROPPER.skipping + '</button>' +
						'<button type="button" id="cp-cropper-apply" class="button button-primary">' + CROPPER.crop_image + '</button>' +
					'</div>' +
				'</footer>' +
			'</div>' +
		'</div>' +
		'<div id="cp-cropper-spinner" hidden aria-live="polite">' +
			'<span class="spinner is-active"></span>' +
			'<span>' + CROPPER.saving + '&hellip;</span>' +
		'</div>';

		document.body.appendChild( dialog );

		dialog.querySelector( '#cp-cropper-close' ).addEventListener( 'click', close );

		dialog.querySelector( '#cp-cropper-skip' ).addEventListener( 'click', skipCrop );

		dialog.querySelector( '#cp-cropper-apply' ).addEventListener( 'click', applyCrop );

		dialog.addEventListener( 'cancel', function( e ) {
			e.preventDefault();
			close();
		} );

		dialog.addEventListener( 'click', function( e ) {
			if ( e.target === dialog ) {
				close();
			}
		} );
	}

	function open( opts ) {
		buildDialog();
		currentOpts = opts;

		const img = dialog.querySelector( '#cp-cropper-img' );
		img.src = opts.imageUrl;

		const errEl = dialog.querySelector( '#cp-cropper-error' );
		if ( errEl ) {
			errEl.remove();
		}

		setLoading( false );

		if ( cropperInst ) {
			cropperInst.destroy();
			cropperInst = null;
		}

		dialog.showModal();

		img.onload = function() {
			cropperInst = new Cropper( img, {
		        viewMode        : 1,
				aspectRatio     : opts.aspectRatio != null ? opts.aspectRatio : NaN,
				autoCropArea    : 1,
				movable         : false,
			    rotatable       : false,
				scalable        : false,
				zoomable        : false,
				guides          : true,
		    	highlight       : true,
				background      : true,
				responsive      : true,
				checkOrientation: false,
			} );
		};
	}

	function close() {
		if ( cropperInst ) {
			cropperInst.destroy();
			cropperInst = null;
		}
		if ( dialog ) {
			dialog.close();
		}
		currentOpts = {};
	}

	function skipCrop() {
		currentOpts.onSelect( {
			id  : currentOpts.attachmentId,
			url : currentOpts.imageUrl,
		} );
		close();
	}

	function applyCrop() {
		if ( ! cropperInst ) {
			return;
		}

		const canvasData  = cropperInst.getCanvasData();
		const cropBoxData = cropperInst.getCropBoxData();
		const imageData   = cropperInst.getImageData();

		const scaleX = imageData.naturalWidth  / imageData.width;
		const scaleY = imageData.naturalHeight / imageData.height;

		const relLeft = cropBoxData.left - canvasData.left;
		const relTop  = cropBoxData.top  - canvasData.top;

	    const x1 = Math.round( relLeft            * scaleX );
		const y1 = Math.round( relTop             * scaleY );
		const w  = Math.round( cropBoxData.width  * scaleX );
		const h  = Math.round( cropBoxData.height * scaleY );

		const dstWidth  = Math.max( w, currentOpts.minWidth  || w );
		const dstHeight = Math.max( h, currentOpts.minHeight || h );

		setLoading( true );

		const body = new URLSearchParams( {
			action                   : 'crop-image',
			nonce                    : currentOpts.nonce,
			id                       : currentOpts.attachmentId,
			context                  : currentOpts.context,
			'cropDetails[x1]'        : x1,
			'cropDetails[y1]'        : y1,
			'cropDetails[width]'     : w,
			'cropDetails[height]'    : h,
		    'cropDetails[dst_width]' : dstWidth,
			'cropDetails[dst_height]': dstHeight,
		} );

		fetch( ajaxurl, {
			method      : 'POST',
			credentials : 'same-origin',
			headers     : { 'Content-Type': 'application/x-www-form-urlencoded' },
			body        : body.toString(),
		} )
		.then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'Network error: ' + response.status );
			}
			return response.json();
		} )
		.then( function ( data ) {
			setLoading( false );
			if ( ! data.success ) {
				showError( ( data.data && data.data.message )
					? data.data.message
					: 'An error occurred while cropping the image.' );
				return;
			}
			currentOpts.onSelect( data.data );
			close();
		} )
		.catch( function ( err ) {
			setLoading( false );
			showError( 'Could not save the cropped image. Please try again.' );
			console.error( '[cpCropper]', err );
		} );
	}

	function setLoading( isLoading ) {
		dialog.querySelector( '#cp-cropper-spinner' ).hidden   = ! isLoading;
		dialog.querySelector( '#cp-cropper-apply'   ).disabled = isLoading;
		dialog.querySelector( '#cp-cropper-skip'    ).disabled = isLoading;
	}

	function showError( message ) {
		let errEl = dialog.querySelector( '#cp-cropper-error' );
		if ( ! errEl ) {
		    errEl    = document.createElement( 'p' );
			errEl.id = 'cp-cropper-error';
			dialog.querySelector( '#cp-cropper-footer' ).prepend( errEl );
		}
		errEl.textContent = message;
	}

	window.cpCropper = { open, close };

} ( window, document ) );
