/**
 * @output wp-admin/js/custom-header.js
 */

/* global Coloris, AdminMediaModal, cpCropper */

document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	/**
	 * Initializes the header text color picker and text visibility toggle.
	 *
	 * @return {void}
	 */
	function initHeaderText() {
		const textColor = document.getElementById( 'text-color' ),
			displayHeaderText = document.getElementById( 'display-header-text' ),
			headerTextFields = document.querySelectorAll( '.displaying-header-text' );

		if ( ! textColor || ! displayHeaderText ) {
			return;
		}

		function pickColor( color ) {
			document.getElementById( 'name' ).style.color = color;
			document.getElementById( 'desc' ).style.color = color;
			textColor.value = color;
		}

		function toggleText() {
			const checked = displayHeaderText.checked;

			headerTextFields.forEach( function( field ) {
				field.hidden = ! checked;
			} );

			if ( ! checked ) {
				return;
			}

			if ( '' === textColor.value.replace( '#', '' ) ) {
				pickColor( textColor.dataset.defaultColor || '' );
			} else {
				pickColor( textColor.value );
			}
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
			onChange: function( color, inputEl ) {
				pickColor(
					color || inputEl.dataset.defaultColor || ''
				);
			}
		} );

		displayHeaderText.addEventListener( 'change', toggleText );
		toggleText();
	}

	/**
	 * Initializes image cropping on step 2.
	 *
	 * @return {void}
	 */
	function initImageCrop() {
		const image = document.getElementById( 'upload' ),
			x1 = document.getElementById( 'x1' ),
			y1 = document.getElementById( 'y1' ),
			width = document.getElementById( 'width' ),
			height = document.getElementById( 'height' ),
			attachmentId = document.getElementById( 'attachment_id' ),
			nonce = document.querySelector( 'input[name="_wpnonce"]' );

		if ( ! image || ! x1 || ! y1 || ! width || ! height || ! attachmentId || ! nonce ) {
			return;
		}

		const cropWidth = parseInt( width.value, 10 ),
			cropHeight = parseInt( height.value, 10 );

		cpCropper.open( {
			attachmentId: attachmentId.value,
			imageUrl: image.src,
			context: 'custom-header',
			nonce: nonce.value,
			aspectRatio: cropWidth / cropHeight,
			minWidth: cropWidth,
			minHeight: cropHeight,
			width: cropWidth,
			height: cropHeight,
			onSelect: function() {
				/*
				 * The cropper submits or completes the crop operation.
				 * No additional handling is required here.
				 */
			}
		} );
	}

	/**
	 * Initializes the media selector on step 1.
	 *
	 * @return {void}
	 */
	function initMediaSelector() {
		const chooseButton = document.getElementById( 'choose-from-library-link' ),
			updateLink = chooseButton.dataset.updateLink;

		if ( ! chooseButton ) {
			return;
		}

		AdminMediaModal( null, null, function( attachment ) {
			const url = new URL( updateLink, window.location.origin );
			url.searchParams.set( 'file', attachment.id );
			window.location.href = url.toString();
		} );
	}

	initHeaderText();
	initImageCrop();
	initMediaSelector();
} );
