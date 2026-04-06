/**
 * cpCropper.js  (Cropper.js v2 / web-components edition)
 * Vanilla JS image cropper for the ClassicPress Customizer.
 *
 * Dependency: cropperjs@next  (https://fengyuanchen.github.io/cropperjs/)
 *   <script src="https://unpkg.com/cropperjs@next"></script>
 */
/* global ajaxurl, console, Cropper, CROPPER */

( function ( window, document ) {
	'use strict';

	let dialog        = null;
	let currentOpts   = {};
	let cropperCanvas = null;   // <cropper-canvas> element
	let cropperImage  = null;   // <cropper-image> element
	let cropperSel    = null;   // <cropper-selection> element

	// ─── Build ───────────────────────────────────────────────────────────────

	function buildDialog() {
		if ( dialog ) {
			return;
		}

		dialog = document.createElement( 'dialog' );
		dialog.id = 'cp-cropper-dialog';
		dialog.innerHTML =
			'<div id="cp-cropper-modal" class="widget-modal-container">' +
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
						// In v2 we inject the <cropper-canvas> tree here at open() time.
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
		dialog.querySelector( '#cp-cropper-skip'  ).addEventListener( 'click', skipCrop );
		dialog.querySelector( '#cp-cropper-apply' ).addEventListener( 'click', applyCrop );

		dialog.addEventListener( 'cancel', function ( e ) {
			e.preventDefault();
			close();
		} );

		dialog.addEventListener( 'click', function ( e ) {
			if ( e.target === dialog ) {
				close();
			}
		} );
	}

	// ─── Build / open tree ────────────────────────────────────────────────────────────────

	function buildCropperTree() {
		destroyCropper();

		const body = dialog.querySelector( '#cp-cropper-body' );

		cropperCanvas = document.createElement( 'cropper-canvas' );
		cropperCanvas.setAttribute( 'background', '' );

		cropperImage = document.createElement( 'cropper-image' );
		cropperImage.setAttribute( 'src', currentOpts.imageUrl );
		cropperImage.setAttribute( 'alt', CROPPER.image_to_crop );
		cropperImage.setAttribute( 'scalable', '' );

		const shade = document.createElement( 'cropper-shade' );

		cropperSel = document.createElement( 'cropper-selection' );
		cropperSel.setAttribute( 'initial-coverage', '1' );
		cropperSel.setAttribute( 'movable', '' );
		cropperSel.setAttribute( 'resizable', '' );
		cropperSel.setAttribute( 'contain', '' );
		if ( currentOpts.aspectRatio != null ) {
			cropperSel.setAttribute( 'aspect-ratio', currentOpts.aspectRatio );
		}

		const grid = document.createElement( 'cropper-grid' );
		grid.setAttribute( 'role', 'none' );

		const moveHandle = document.createElement( 'cropper-handle' );
		moveHandle.setAttribute( 'action', 'move' );
		moveHandle.setAttribute( 'theme-color', 'rgba(255, 255, 255, 0.35)' );

		const handleActions = [
			'n-resize', 'e-resize', 's-resize', 'w-resize',
			'ne-resize', 'nw-resize', 'se-resize', 'sw-resize'
		];
		handleActions.forEach( function ( action ) {
			const h = document.createElement( 'cropper-handle' );
			h.setAttribute( 'action', action );
			h.setAttribute( 'plain', '' );
			cropperSel.appendChild( h );
		} );

		cropperSel.appendChild( grid );
		cropperSel.appendChild( moveHandle );

		cropperCanvas.appendChild( cropperImage );
		cropperCanvas.appendChild( shade );
		cropperCanvas.appendChild( cropperSel );

		body.appendChild( cropperCanvas );
	}

	function open( opts ) {
		buildDialog();
		currentOpts = opts;

		// Remove any previous error.
		const errEl = dialog.querySelector( '#cp-cropper-error' );
		if ( errEl ) {
			errEl.remove();
		}

		setLoading( false );
		buildCropperTree();
		dialog.showModal();

		window.addEventListener( 'resize', buildCropperTree );
	}

	// ─── Close / destroy ─────────────────────────────────────────────────────

	function destroyCropper() {
		if ( cropperCanvas ) {
			cropperCanvas.remove();
			cropperCanvas = null;
			cropperImage  = null;
			cropperSel    = null;
		}
	}

	function close() {
		window.removeEventListener( 'resize', buildCropperTree );
		destroyCropper();
		if ( dialog ) {
			dialog.close();
		}
		currentOpts = {};
	}

	// ─── Skip ────────────────────────────────────────────────────────────────

	function skipCrop() {
		currentOpts.onSelect( {
			id  : currentOpts.attachmentId,
			url : currentOpts.imageUrl
		} );
		close();
	}

	// ─── Apply ───────────────────────────────────────────────────────────────

	function applyCrop() {
		if ( ! cropperSel || ! cropperImage ) {
			return;
		}

		// In v2, getCropBoxData() is gone. The selection exposes its position
		// and size as plain properties (in canvas/display-space pixels).
		const selX = cropperSel.x;
		const selY = cropperSel.y;
		const selW = cropperSel.width;
		const selH = cropperSel.height;

		// $getTransform() returns the CSS matrix applied to <cropper-image>.
		// matrix[0] is the X scale factor (display px → natural px ratio).
		cropperImage.$ready( function () {

			const transform = cropperImage.$getTransform();
			// transform = [a, b, c, d, e, f]  (CSS matrix)
			// a = scaleX, d = scaleY, e = translateX, f = translateY
			const scaleX = transform[ 0 ];   // display-to-natural ratio on X
			const scaleY = transform[ 3 ];   // display-to-natural ratio on Y
			const transX = transform[ 4 ];   // image left offset in display px
			const transY = transform[ 5 ];   // image top  offset in display px

			// Map selection from canvas-space into natural image-space.
			// (selX/selY are relative to the canvas origin; transX/transY
			//  is where the image origin sits inside the canvas.)
			const x1 = Math.round( ( selX - transX ) / scaleX );
			const y1 = Math.round( ( selY - transY ) / scaleY );
			const w  = Math.round( selW / scaleX );
			const h  = Math.round( selH / scaleY );

			const dstWidth  = Math.max( w, currentOpts.minWidth  || w );
			const dstHeight = Math.max( h, currentOpts.minHeight || h );

			setLoading( true );

			const body = new URLSearchParams( {
				action                    : 'crop-image',
				nonce                     : currentOpts.nonce,
				id                        : currentOpts.attachmentId,
				context                   : currentOpts.context,
				'cropDetails[x1]'         : x1,
				'cropDetails[y1]'         : y1,
				'cropDetails[width]'      : w,
				'cropDetails[height]'     : h,
				'cropDetails[dst_width]'  : dstWidth,
				'cropDetails[dst_height]' : dstHeight
			} );

			fetch( ajaxurl, {
				method      : 'POST',
				credentials : 'same-origin',
				headers     : { 'Content-Type': 'application/x-www-form-urlencoded' },
				body        : body.toString()
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
					showError( ( data.data && data.data.message ) ? data.data.message : 'An error occurred while cropping the image.' );
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

		} );
	}

	// ─── Helpers ─────────────────────────────────────────────────────────────

	function setLoading( isLoading ) {
		dialog.querySelector( '#cp-cropper-spinner' ).hidden    = ! isLoading;
		dialog.querySelector( '#cp-cropper-apply'   ).disabled  = isLoading;
		dialog.querySelector( '#cp-cropper-skip'    ).disabled  = isLoading;
	}

	function showError( message ) {
		let errEl = dialog.querySelector( '#cp-cropper-error' );
		if ( ! errEl ) {
			errEl    = document.createElement( 'p' );
			errEl.id = 'cp-cropper-error';
			dialog.querySelector( '.widget-modal-footer' ).prepend( errEl );
		}
		errEl.textContent = message;
	}

	window.cpCropper = { open, close };

} ( window, document ) );
