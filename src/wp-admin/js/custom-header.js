/**
 * @output wp-admin/js/custom-header.js
 */

/* global AdminMediaModal, cpCropper */

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

		displayHeaderText.addEventListener( 'change', toggleText );
		toggleText();
	}

	initHeaderText();
	AdminMediaModal( 'set-header-image', 'full', 'headerImage' );

	document.addEventListener( 'custom-header-attachment-selected', function() {
		const attachment = window.CustomHeaderCrop;

		if ( ! attachment ) {
			return;
		}

		cpCropper.open( {
			attachmentId: attachment.id,
			imageUrl: attachment.url,
			context: 'custom-header',
			action: 'custom-header-crop',
			nonce: attachment.nonce,
			aspectRatio: attachment.width / attachment.height,
			minWidth: attachment.width,
			minHeight: attachment.height,
			width: attachment.width,
			height: attachment.height,
			onSelect: function( croppedAttachment ) {
				const headerImg = document.querySelector( '#headimg img' );

				if ( headerImg ) {
					headerImg.src = croppedAttachment.url;
				} else {
					const img = document.createElement( 'img' );
					img.src = croppedAttachment.url;
					document.getElementById( 'desc' ).after( img );
				}
			}
		} );
	} );
} );
