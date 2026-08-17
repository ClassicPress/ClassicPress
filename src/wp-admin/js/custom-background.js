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

	var frame,
		bgImage = document.getElementById( 'custom-background-image' );

	/**
	 * Instantiates the Coloris color picker and binds the change and clear events.
	 *
	 * @since 3.5.0
	 * @since x.x.x Uses Coloris instead of wp-color-picker.
	 *
	 * @return {void}
	 */
	function initColorPicker() {
		var colorInput = document.getElementById( 'background-color' );
	
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
			onChange: (color, inputEl) => {
				var effectiveColor = color || inputEl.dataset.defaultColor || '';

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
		var select = document.querySelector( 'select[name="background-size"]' );
		if ( ! select || ! bgImage ) {
			return;
		}

		select.addEventListener( 'change', function() {
			bgImage.style.backgroundSize = this.value;
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
		var input = document.querySelector( 'input[name="background-position"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function() {
			bgImage.style.backgroundPosition = this.value;
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
		var input = document.querySelector( 'input[name="background-repeat"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function() {
			bgImage.style.backgroundRepeat = this.checked ? 'repeat' : 'no-repeat';
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
		var input = document.querySelector( 'input[name="background-attachment"]' );
		if ( ! input || ! bgImage ) {
			return;
		}

		input.addEventListener( 'change', function() {
			bgImage.style.backgroundAttachment = this.checked ? 'scroll' : 'fixed';
		} );
	}

	/**
	 * Binds the event for opening the WP Media dialog.
	 *
	 * @since 3.5.0
	 *
	 * @return {void}
	 */
	function initMediaSelector() {
		var chooseLink = document.getElementById( 'choose-from-library-link' );
		if ( ! chooseLink ) {
			return;
		}

		chooseLink.addEventListener( 'click', function( event ) {
			var $el = this;

			event.preventDefault();

			// If the media frame already exists, reopen it.
			if ( frame ) {
				frame.open();
				return;
			}

			// Create the media frame.
			frame = wp.media.frames.customBackground = wp.media( {
				// Set the title of the modal.
				title: $el.getAttribute( 'data-choose' ),

				// Tell the modal to show only images.
				library: {
					type: 'image'
				},

				// Customize the submit button.
				button: {
					// Set the text of the button.
					text: $el.getAttribute( 'data-update' ),
					/*
					 * Tell the button not to close the modal, since we're
					 * going to refresh the page when the image is selected.
					 */
					close: false
				}
			} );

			/**
			 * When an image is selected, run a callback.
			 *
			 * @since 3.5.0
			 *
			 * @return {void}
			 */
			frame.on( 'select', function() {

				// Grab the selected attachment.
				var attachment = frame.state().get( 'selection' ).first(),
					nonceField = document.getElementById( '_wpnonce' ),
					nonceValue = nonceField ? nonceValue : '';

				// Run an Ajax request to set the background image.
				fetch( ajaxurl, {
					method: 'POST',
					body: 'action=set-background-image' +
						'&attachment_id=' + encodeURIComponent( attachment.id ) +
						'&_ajax_nonce=' + encodeURIComponent( nonceValue ) +
						'&size=full'
				} )
				.then( function( response ) {
					if ( response.ok ) {
						// When the request completes successfully, reload the window.
						window.location.reload();
					} else {
						console.error( wpAjax.broken, response.status );
					}
				} )
				.catch( function( error ) {
					console.error( error );
				} );
			} );

			// Finally, open the modal.
			frame.open();
		} );
	}

	// Initialize all components when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function() {
			initColorPicker();
			initBackgroundSize();
			initBackgroundPosition();
			initBackgroundRepeat();
			initBackgroundAttachment();
			initMediaSelector();
		} );
	} else {
		initColorPicker();
		initBackgroundSize();
		initBackgroundPosition();
		initBackgroundRepeat();
		initBackgroundAttachment();
		initMediaSelector();
	}
} );
