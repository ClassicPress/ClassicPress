/**
 * cpCropper.js  (Cropper.js v2 / web-components edition)
 * Vanilla JS image cropper for the ClassicPress Customizer.
 *
 * Dependency: cropperjs@next  (https://fengyuanchen.github.io/cropperjs/)
 *   <script src="https://unpkg.com/cropperjs@next"></script>
 */
/* global ajaxurl, console, CROPPER */

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

		// Constrain initial selection to the image bounds.
		cropperImage.$ready( function() {
			const shadowRoot = cropperImage.$getShadowRoot();
			const imgEl      = shadowRoot.querySelector( 'img' );
			const natW       = imgEl ? imgEl.naturalWidth  : 0;
			const natH       = imgEl ? imgEl.naturalHeight : 0;

			if ( ! natW || ! natH ) {
				return;
			}

			const canvasRect = cropperCanvas.getBoundingClientRect();
			const imgRect    = ( imgEl || cropperImage ).getBoundingClientRect();
			const dispW      = imgRect.width;
			const dispH      = imgRect.height;
			const offX       = imgRect.left - canvasRect.left;
			const offY       = imgRect.top  - canvasRect.top;

			var selW, selH;

			if ( currentOpts.aspectRatio != null ) {
				// Fit the selection to the displayed image, respecting the aspect ratio.
				var ratio = currentOpts.aspectRatio; // width / height
				if ( dispW / dispH > ratio ) {
					// Image is wider than the ratio — constrain by height.
					selH = dispH;
					selW = selH * ratio;
				} else {
					// Image is taller than the ratio — constrain by width.
					selW = dispW;
					selH = selW / ratio;
				}
			} else {
				// No ratio set — fall back to the original square behaviour.
				selW = selH = Math.min( dispW, dispH );
			}

			cropperSel.$change(
				offX + ( dispW - selW ) / 2,
				offY + ( dispH - selH ) / 2,
				selW,
				selH
			);

			var isClamping = false;

			cropperSel.addEventListener( 'change', function ( e ) {
				if ( isClamping ) {
					return;
				}

				const canvasRect = cropperCanvas.getBoundingClientRect();
				const imgRect    = ( shadowRoot.querySelector( 'img' ) || cropperImage ).getBoundingClientRect();

				const minX = imgRect.left - canvasRect.left;
				const minY = imgRect.top  - canvasRect.top;
				const maxX = minX + imgRect.width;
				const maxY = minY + imgRect.height;

				let { x, y, width, height } = cropperSel;

				// Clamp position only — don't touch width/height.
				x = Math.max( minX, Math.min( x, maxX - width ) );
				y = Math.max( minY, Math.min( y, maxY - height ) );

				if ( x !== cropperSel.x || y !== cropperSel.y ) {
					e.preventDefault();
					isClamping = true;
					cropperSel.$change( x, y, width, height );
					isClamping = false;
				}
			} );
		} );

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

			const shadowRoot = cropperImage.$getShadowRoot();
			const imgEl      = shadowRoot.querySelector( 'img' );
			const natW       = imgEl ? imgEl.naturalWidth  : 512;
			const natH       = imgEl ? imgEl.naturalHeight : 512;

			const canvasRect = cropperCanvas.getBoundingClientRect();
			const imgRect    = ( imgEl || cropperImage ).getBoundingClientRect();

			// Size of the image as currently displayed in the canvas (CSS px).
			const dispW = imgRect.width;
			const dispH = imgRect.height;

			// Scale: natural pixels per display pixel.
			const scaleX = natW / dispW;
			const scaleY = natH / dispH;

			// Image origin relative to canvas origin (in display px).
			const offX = imgRect.left - canvasRect.left;
			const offY = imgRect.top  - canvasRect.top;

			// Snap near-zero origins to 0.
			const x1 = snap( Math.round( ( selX - offX ) * scaleX ), 0, 1 );
			const y1 = snap( Math.round( ( selY - offY ) * scaleY ), 0, 1 );

			// Clamp so crop never exceeds image bounds.
			// Snap width/height to natW/natH when within 1px.
			const safeW = snap( Math.min( Math.round( selW * scaleX ), natW - x1 ), natW - x1, 1 );
			const safeH = snap( Math.min( Math.round( selH * scaleY ), natH - y1 ), natH - y1, 1 );

			const dstWidth  = currentOpts.minWidth  ? currentOpts.minWidth  : safeW;
			const dstHeight = currentOpts.minHeight ? currentOpts.minHeight : safeH;

			setLoading( true );

			const body = new URLSearchParams( {
				action                    : 'crop-image',
				nonce                     : currentOpts.nonce,
				id                        : currentOpts.attachmentId,
				context                   : currentOpts.context,
				'cropDetails[x1]'         : x1,
				'cropDetails[y1]'         : y1,
				'cropDetails[width]'      : safeW,
				'cropDetails[height]'     : safeH,
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

	function snap( value, target, tolerance ) {
		return Math.abs( value - target ) <= tolerance ? target : value;
	}

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
